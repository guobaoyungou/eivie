<template>
<view>
	<block v-if="isload">
		<view style="color:red;padding:10rpx 30rpx;margin-top:20rpx" v-if="info.id && info.checkstatus==-1">审核不通过：{{info.checkreason}}，请修改后再提交</view>
		<view style="color:red;padding:10rpx 30rpx;margin-top:20rpx" v-else-if="info.id && info.checkstatus==0 && info.status==1">您已提交申请，请等待审核</view>
		<view style="color:red;padding:10rpx 30rpx;margin-top:20rpx" v-else-if="info.id && info.checkstatus==0">您已提交申请，请等待审核</view>
		<form @submit="subform">
			<view class="apply_box">
        <view class="apply_item" style="padding: 20rpx 0;">
            <view>头像<text style="color:red"> *</text></view>
            <view class="flex flex-y-center">
              <view v-for="(item, index) in pic" :key="index" class="layui-imgbox">
                <view  v-if="item" @tap="removeimg" :data-index="index" data-field="pic"><image class="layui-imgbox-close" :src="pre_url+'/static/img/ico-del.png'"></image></view>
                <view class="layui-imgbox-img"><image :src="item" @tap="previewImage" :data-url="item" mode="widthFix"></image></view>
              </view>
              <view class="uploadbtn" :style="'background:url('+pre_url+'/static/img/shaitu_icon.png) no-repeat 60rpx;background-size:80rpx 80rpx;background-color:#F3F3F3;'" @tap="uploadimg" data-field="pic" v-if="pic.length==0"></view>
            </view>
            <input type="text" hidden="true" name="pic" :value="pic.join(',')" maxlength="-1"></input>
        </view>
				<view class="apply_item">
					<view>昵称<text style="color:red"> *</text></view>
					<view class="flex-y-center"><input type="text" name="nickname" :value="info.nickname" placeholder="请填写昵称"></input></view>
				</view>
        <view class="apply_item">
        	<view>姓名<text style="color:red"> *</text></view>
        	<view class="flex-y-center"><input type="text" name="realname" :value="info.realname" placeholder="请填写姓名"></input></view>
        </view>
				<view class="apply_item">
					<view>手机号<text style="color:red"> *</text></view>
					<view class="flex-y-center">
            <input type="text" name="tel" :value="info.tel" @input="changetel" placeholder="请填写手机号码"></input>
          </view>
				</view>
        <view v-if="needsms && cansendsms" class="apply_item">
        	<view>验证码<text style="color:red"> *</text></view>
        	<view class="flex-y-center">
            <input type="text" name="smscode" value="" placeholder="请输入验证码" style="width: 200rpx;margin-right: 10rpx;"></input>
            <text class="code1" :style="'color:'+t('color1')" @tap="smscode">{{smsdjs||'获取验证码'}}</text>
          </view>
        </view>
        <view class="apply_item">
          <view>简介</view>
          <view class="flex-y-center"><input type="text" name="shortdesc"  :value="info.shortdesc"  placeholder="请填写简介"></input> </view>
        </view>
        <block v-if="canpwd">
          <view class="apply_item">
            <view>登录密码<text style="color:red"> *</text></view>
            <view class="flex-y-center"><input type="password" name="pwd" value="" :placeholder="opttype == 1?'不修改不需要填写':'请填写登录密码'" autocomplete="off"></input></view>
          </view>
          <view class="apply_item">
            <view>确认密码<text style="color:red"> *</text></view>
            <view class="flex-y-center"><input type="password" name="repwd" :placeholder="opttype == 1?'不修改不需要填写':'请再次填写密码'"></input></view>
          </view>
        </block>
        <block v-if="backstage">
          <view class="apply_item" style="height: auto;padding: 20rpx 0;">
            <view style="width: 180rpx;">后台登录地址</view>
            <view style="line-height: 35rpx;word-break: break-all;width: 500rpx;">
              <view @tap="copy" :data-text='backstage' style="word-break: break-all;">{{backstage}} 点击复制</view>
              <view style="word-break: break-all;color: #999;font-size: 24rpx;margin-top: 10rpx;">手机号就是登录账号</view>
            </view>
          </view>
        </block>
			</view>
			<view v-if="cansub" style="padding:30rpx 0">
        <button form-type="submit" class="set-btn" :style="'background:linear-gradient(90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'">
          <text v-if="!info.id || info.checkstatus==-1">提交申请</text>
          <text v-if="info.id && info.checkstatus ==1">保存修改</text>
        </button>
      </view>

		</form>
	</block>
	<loading v-if="loading"></loading>
	<dp-tabbar :opt="opt"></dp-tabbar>
	<popmsg ref="popmsg"></popmsg>
	<wxxieyi></wxxieyi>
</view>
</template>

<script>
var app = getApp();
export default {
  data() {
    return {
			opt:{},
			loading:false,
      isload: false,
			menuindex:-1,
			pre_url:app.globalData.pre_url,
      datalist: [],
      pagenum: 1,

      info: [],
			pic:[],
			items:[],
			pics:[],
      opttype:0,
      canpwd:false,
      cansub:false,
      backstage:'',//后台登录地址
      needsms:false,//是否需要发送验证码
      cansendsms:false,//能否发送验证码
      smsdjs:'',
      tel:'',
      hqing: 0,
    };
  },
  onLoad: function (opt) {
    var that = this;
		that.opt = app.getopts(opt);
    that.opttype= that.opt.opttype || 0;
    if(!that.opttype || that.opttype == 0){
      that.needsms = true;
      uni.setNavigationBarTitle({
      	title: '申请讲师'
      });
    }else{
      uni.setNavigationBarTitle({
      	title: '讲师信息'
      });
    }
    that.getdata();
  },
  methods: {
		getdata: function () {
			var that = this;
			that.loading = true;
			app.get('ApiKecheng/lecturerapply', {opttype:that.opttype}, function (res) {
				that.loading = false;
        if(res.status == 1){
          that.info = res.info
          if(res.canpwd){
            that.canpwd = res.canpwd
          }
          if(res.cansub){
            that.cansub = res.cansub
          }
          if(res.backstage){
            that.backstage = res.backstage
          }
          if(res.cansendsms){
            that.cansendsms = res.cansendsms
          }
          var pic = res.info ? res.info.headimg : '';
          if (pic) {
           	pic = pic.split(',');
          } else {
           	pic = [];
          }
          that.pic = pic;
          that.loaded();
        }else if (res.status == 2) {
					app.goto(res.tourl, 'redirect');
					return;
				} else {
          if (res.msg) {
            app.alert(res.msg, function() {
              if (res.url) app.goto(res.url);
            });
          } else if (res.url) {
            app.goto(res.url);
          } else {
            app.alert('您无查看权限');
          }
        }
			});
		},
    subform: function (e) {
      var that = this;
      var info = e.detail.value;
      if (!info.realname || info.realname == '') {
        app.error('请填写姓名');
        return;
      }

      if (!info.tel || info.tel == '') {
        app.error('请填写手机号');
        return;
      }

      // if (info.un == '') {
      //   app.error('请填写登录账号');
      //   return;
      // }
      
      if (that.opttype == 0 && ( !info.pwd || info.pwd == '')){
        app.error('请填写登录密码');
        return;
      }
      if(info.pwd){
        if (info.pwd.length < 6) {
          app.error('密码不能小于6位');
          return;
        }
        if (info.repwd != info.pwd) {
          app.error('两次输入密码不一致');
          return;
        }
      }

      if(that.info && that.info.id) {
					info.id = that.info.id;
      }

			app.showLoading('提交中');
      app.post("ApiKecheng/lecturerapply", {info: info,opttype:that.opttype}, function (res) {
				app.showLoading(false);
        app.error(res.msg);
				if(res.status == 1){
					setTimeout(function () {
						if(res.tourl){
							app.goto(res.tourl,'reLaunch');
						}else{
              app.goback();
            }
					}, 1000);
				}
      });
    },
		uploadimg:function(e){
			var that = this;
			var field= e.currentTarget.dataset.field
			var pics = that[field]
			if(!pics) pics = [];
			app.chooseImage(function(urls){
				for(var i=0;i<urls.length;i++){
					pics.push(urls[i]);
				}
				if(field == 'headimg') that.headimg = pics;
			},1)
		},
		removeimg:function(e){
			var that = this;
			var index= e.currentTarget.dataset.index
			var field= e.currentTarget.dataset.field
			if(field == 'pic'){
				var pics = that.pic
				pics.splice(index,1);
				that.pic = pics;
			}else if(field == 'pics'){
				var pics = that.pics
				pics.splice(index,1);
				that.pics = pics;
			}
		},
    changetel:function(e){
      var that = this;
      var info = that.info;
      var opttype = that.opttype;
      var tel  = e.detail.value;
      that.tel = tel
      if(opttype == 1){
        if(tel != info.tel){
          that.needsms = true
        }else{
          that.needsms = false
        }
      }
    },
    smscode: function () {
      var that = this;
      if (that.hqing == 1) return;
      that.hqing = 1;
      var tel = that.tel;
      if (tel == '') {
        app.alert('请输入手机号码');
        that.hqing = 0;
        return false;
      }
      if (!app.isPhone(tel)) {
        app.alert("手机号码有误，请重填");
        that.hqing = 0;
        return false;
      }
      app.post("ApiIndex/sendsms", {tel: tel}, function (data) {
        if (data.status != 1) {
          app.alert(data.msg);return;
        }
      });
      var time = 120;
      var interval1 = setInterval(function () {
        time--;
        if (time < 0) {
          that.smsdjs = '重新获取';
          that.hqing = 0;
          clearInterval(interval1);
        } else if (time >= 0) {
          that.smsdjs = time + '秒';
        }
      }, 1000);
    },
  }
}
</script>
<style>
radio{transform: scale(0.6);}
checkbox{transform: scale(0.6);}
.apply_box{ padding:2rpx 24rpx 0 24rpx; background: #fff;margin: 24rpx;border-radius: 10rpx}
.apply_title { background: #fff}
.apply_title .qr_goback{ width:18rpx;height:32rpx; margin-left:24rpx;     margin-top: 34rpx;}
.apply_title .qr_title{ font-size: 36rpx; color: #242424;   font-weight:bold;margin: 0 auto; line-height: 100rpx;}

.apply_item{ line-height: 100rpx; display: flex;justify-content: space-between;border-bottom:1px solid #eee;align-items: center; }
.apply_item1{  display: flex;justify-content: space-between;}
.apply_box .apply_item:last-child{ border:none}
.apply_item input{ width: 100%; border: none;color:#111;font-size:28rpx; text-align: right;}
.apply_item input::placeholder{ color:#999999}
.apply_item textarea{ width:100%;min-height:200rpx;padding:20rpx 0;border: none;}
.apply_item .upload_pic{ margin:50rpx 0;background: #F3F3F3;width:90rpx;height:90rpx; text-align: center  }
.apply_item .upload_pic image{ width: 32rpx;height: 32rpx; }
.set-btn{width: 90%;margin:0 5%;height:96rpx;line-height:96rpx;border-radius:48rpx;color:#FFFFFF;font-weight:bold;}

.layui-imgbox{margin-right:16rpx;margin-bottom:10rpx;font-size:24rpx;position: relative;}
.layui-imgbox-img{display: block;width:200rpx;height:200rpx;padding:2px;border: #d3d3d3 1px solid;background-color: #f6f6f6;overflow:hidden}
.layui-imgbox-img>image{max-width:100%;}
.layui-imgbox-repeat{position: absolute;display: block;width:32rpx;height:32rpx;line-height:28rpx;right: 2px;bottom:2px;color:#999;font-size:30rpx;background:#fff}
.uploadbtn{position:relative;height:200rpx;width:200rpx}
</style>