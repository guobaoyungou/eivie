<template>
<view>
	<block v-if="isload">
		<view style="color:red;padding:10rpx 30rpx;margin-top:20rpx" v-if="info.id && info.shstatus==2">审核不通过：{{info.reason}}，请修改后再提交</view>
		<view style="color:red;padding:10rpx 30rpx;margin-top:20rpx" v-else-if="info.id && info.shstatus==0">您已提交申请，请等待审核</view>
		<form @submit="subform">
			<view class="apply_box">
				<view class="apply_item">
					<view>姓名<text style="color:red"> *</text></view>
					<view class="flex-y-center"><input type="text" name="realname" :value="info.realname" placeholder="请填写姓名"></input></view>
				</view>
				<view class="apply_item">
					<view>手机号<text style="color:red"> *</text></view>
					<view class="flex-y-center"><input type="text" name="tel" :value="info.tel" placeholder="请填写手机号码"></input></view>
				</view>
			</view>
		
			<view class="apply_box">
				<block>
					<view class="apply_item" style="border-bottom:0"><view>头像</view></view>
					<view class="flex" style="flex-wrap:wrap;padding-bottom:20rpx;">
						<view v-for="(item, index) in pic" :key="index" class="layui-imgbox">
							<view class="layui-imgbox-close" @tap="removeimg" :data-index="index" data-field="pic"><image :src="pre_url+'/static/img/ico-del.png'"></image></view>
							<view class="layui-imgbox-img"><image :src="item" @tap="previewImage" :data-url="item" mode="widthFix"></image></view>
						</view>
						<view class="uploadbtn" :style="'background:url('+pre_url+'/static/img/shaitu_icon.png) no-repeat 60rpx;background-size:80rpx 80rpx;background-color:#F3F3F3;'" @tap="uploadimg" data-field="pic" v-if="pic.length==0"></view>
					</view>
					<input type="text" hidden="true" name="pic" :value="pic.join(',')" maxlength="-1"></input>
				</block>	
				
				<block>
					<view class="apply_item">
						<view>简介</view>
						<view class="flex-y-center"><input type="text" name="desc"  :value="info.desc"  :placeholder="t('教练')+'简介'"></input> </view>
					</view>	
				</block>
			</view>
		
			<view class="apply_box" v-if="!info">
				<view class="apply_item">
					<view>设置登录账号<text style="color:red"> *</text></view>
					<view class="flex-y-center"><input type="text" name="un" :value="info.un" placeholder="请填写登录账号" autocomplete="off"></input></view>
				</view>
				<view class="apply_item">
					<view>设置登录密码<text style="color:red"> *</text></view>
					<view class="flex-y-center"><input type="password" name="pwd" :value="info.pwd" placeholder="请填写登录密码" autocomplete="off"></input></view>
				</view>
				<view class="apply_item">
					<view>确认密码<text style="color:red"> *</text></view>
					<view class="flex-y-center"><input type="password" name="repwd"  placeholder="请再次填写密码"></input></view>
				</view>
			</view>
			
			
			<view style="padding:30rpx 0">
				<button v-if="!info || info.shstatus==2" form-type="submit" class="set-btn" :style="'background:linear-gradient(90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'">提交申请</button>
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
			regiondata:'',
			pre_url:app.globalData.pre_url,
      datalist: [],
      info: [],
			bid:'',
			pic:[],
    };
  },

  onLoad: function (opt) {
		this.getdata();
		this.opt = app.getopts(opt);
		this.type = this.opt.type || 0;
  },
	onPullDownRefresh: function () {
		this.getdata();
	},
  methods: {
		
		getdata: function () {
			var that = this;
			that.loading = true;
			app.get('ApiYueke/apply', {}, function (res) {
				that.loading = false;
				if (res.status == 2) {
					app.success(res.msg);
					setTimeout(function () {
					  return app.goto('/pagesExt/yueke/workerlogin', 'redirect');
					}, 1000);
				}
				that.info = res.data;
				if(res.data && res.data.pic){
					that.pic = res.data.pic;
				}
				
				that.loaded();
			});
			
		},
    subform: function (e) {
      var that = this;
      var info = e.detail.value;
      if (info.realname == '') {
        app.error('请填写姓名');
        return false;
      }

      if (info.tel == '') {
        app.error('请填写手机号');
        return false;
      }

      if (info.un == '') {
        app.error('请填写登录账号');
        return false;
      }
			if (that.info == null || that.info.id === undefined) {
				if (info.pwd == '') {
					app.error('请填写登录密码');
					return false;
				}
				var pwd = info.pwd;
				if (pwd.length < 6) {
					app.error('密码不能小于6位');
					return false;
				}
				if (info.repwd != info.pwd) {
					app.error('两次输入密码不一致');
					return false;
				}
			}
			
      if (that.info && that.info.id) {
					info.id = that.info.id;
      }

			app.showLoading('提交中');
      app.post("ApiYueke/apply", {info: info}, function (res) {
				app.showLoading(false);
        app.error(res.msg);
				that.getdata()
      });
    },
    isagreeChange: function (e) {
      console.log(e.detail.value);
      var val = e.detail.value;
      if (val.length > 0) {
        this.isagree = true;
      } else {
        this.isagree = false;
      }
    },
    showxieyiFun: function () {
      this.showxieyi = true;
    },
    hidexieyi: function () {
      this.showxieyi = false;
			this.isagree = true;
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
				if(field == 'codepic') that.codepic = pics;
				if(field == 'otherpic') that.otherpic = pics;
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
		toggleMaskLocation(){
			this.$nextTick(()=>{
				this.$refs["cityPicker"].show();
			})
		},
		getpickerParentValue(data){
			console.log(data.map(o=>{return o.text}));  //获取地址的value值
			this.provincedata=data;
			this.addressByPcrs=data.map(o=>{return o.text}).join(",")
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



.clist-item{display:flex;border-bottom: 1px solid #f5f5f5;padding:20rpx 30rpx;}
.clist-item .radio{flex-shrink:0;width: 32rpx;height: 32rpx;background: #FFFFFF;border: 2rpx solid #BFBFBF;border-radius: 50%;margin-right:30rpx}
.clist-item .radio .radio-img{width:100%;height:100%;display:block}

</style>