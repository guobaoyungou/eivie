<template>
<view>
	<block v-if="isload">
		<view style="color:red;padding:10rpx 30rpx;margin-top:20rpx" v-if="info.id && info.status==2">审核不通过：{{info.reason}}，请修改后再提交</view>
		<view style="color:red;padding:10rpx 30rpx;margin-top:20rpx" v-if="info.id && info.status==0">您已提交申请，请等待审核</view>
		<form @submit="subform">
			<view class="apply_box">
				<view class="apply_item">
					<view>联系人姓名<text style="color:red"> *</text></view>
					<view class="flex-y-center"><input type="text" name="linkman" :value="info.linkman" placeholder="请填写姓名"></input></view>
				</view>
				<view class="apply_item">
					<view>联系人电话<text style="color:red"> *</text></view>
					<view class="flex-y-center"><input type="text" name="linktel" :value="info.linktel" placeholder="请填写手机号码"></input></view>
				</view>
			</view>
			<view class="apply_box">
				<view class="apply_item">
					<view>商家名称<text style="color:red"> *</text></view>
					<view class="flex-y-center"><input type="text" name="name" :value="info.name" placeholder="请输入商家名称"></input></view>
				</view>
				<view class="apply_item">
					<view>商家描述<text style="color:red"> *</text></view>
					<view class="flex-y-center"><input type="text" name="desc" :value="info.desc" placeholder="请输入商家描述"></input></view>
				</view>
				<view class="apply_item">
					<view>主营类目<text style="color:red"> *</text></view>
					<view>
						<picker @change="cateChange" :value="cindex" :range="cateArr">
							<view class="picker">{{cateArr[cindex]}}</view>
						</picker>
					</view>
				</view>
				<view class="apply_item">
					<view>店铺坐标<text style="color:red"> *</text></view>
					<view class="flex-y-center"><input type="text" disabled placeholder="请选择坐标" name="zuobiao" :value="latitude ? latitude+','+longitude:''" @tap="locationSelect"></input></view>
				</view>
				<view class="apply_item">
					<view>店铺地址<text style="color:red"> *</text></view>
					<view class="flex-y-center"><input type="text" name="address" :value="address" placeholder="请输入商家详细地址"></input></view>
				</view>
				<input type="text" hidden="true" name="latitude" :value="latitude"></input>
				<input type="text" hidden="true" name="longitude" :value="longitude"></input>
				<view class="apply_item">
					<view>客服电话<text style="color:red"> *</text></view>
					<view class="flex-y-center"><input type="text" name="tel" :value="info.tel" placeholder="请填写客服电话"></input></view>
				</view>
				<view class="apply_item" style="line-height:50rpx"><textarea name="content" placeholder="请输入商家简介" :value="info.content"></textarea></view>
			</view>
			<view class="apply_box">
				<view class="apply_item" style="border-bottom:0"><text>商家主图<text style="color:red"> *</text></text></view>
				<view class="flex" style="flex-wrap:wrap;padding-bottom:20rpx;">
					<view v-for="(item, index) in pic" :key="index" class="layui-imgbox">
						<view class="layui-imgbox-close" @tap="removeimg" :data-index="index" data-field="pic"><image src="/static/img/ico-del.png"></image></view>
						<view class="layui-imgbox-img"><image :src="item" @tap="previewImage" :data-url="item" mode="widthFix"></image></view>
					</view>
					<view class="uploadbtn" :style="'background:url('+pre_url+'/static/img/shaitu_icon.png) no-repeat 60rpx;background-size:80rpx 80rpx;background-color:#F3F3F3;'" @tap="uploadimg" data-field="pic" v-if="pic.length==0"></view>
				</view>
				<input type="text" hidden="true" name="pic" :value="pic.join(',')" maxlength="-1"></input>
			</view>
			<view class="apply_box">
				<view class="apply_item" style="border-bottom:0"><text>商家照片(3-5张)<text style="color:red"> *</text></text></view>
				<view class="flex" style="flex-wrap:wrap;padding-bottom:20rpx;">
					<view v-for="(item, index) in pics" :key="index" class="layui-imgbox">
						<view class="layui-imgbox-close" @tap="removeimg" :data-index="index" data-field="pics"><image src="/static/img/ico-del.png"></image></view>
						<view class="layui-imgbox-img"><image :src="item" @tap="previewImage" :data-url="item" mode="widthFix"></image></view>
					</view>
					<view class="uploadbtn" :style="'background:url('+pre_url+'/static/img/shaitu_icon.png) no-repeat 60rpx;background-size:80rpx 80rpx;background-color:#F3F3F3;'" @tap="uploadimg" data-field="pics" v-if="pics.length<5"></view>
				</view>
				<input type="text" hidden="true" name="pics" :value="pics.join(',')" maxlength="-1"></input>
			</view>
			<view class="apply_box">
				<view class="apply_item" style="border-bottom:0"><text>证明材料<text style="color:red"> </text></text></view>
				<view class="flex" style="flex-wrap:wrap;padding-bottom:20rpx;">
					<view v-for="(item, index) in zhengming" :key="index" class="layui-imgbox">
						<view class="layui-imgbox-close" @tap="removeimg" :data-index="index" data-field="zhengming"><image src="/static/img/ico-del.png"></image></view>
						<view class="layui-imgbox-img"><image :src="item" @tap="previewImage" :data-url="item" mode="widthFix"></image></view>
					</view>
					<view class="uploadbtn" :style="'background:url('+pre_url+'/static/img/shaitu_icon.png) no-repeat 60rpx;background-size:80rpx 80rpx;background-color:#F3F3F3;'" @tap="uploadimg" data-field="zhengming"></view>
				</view>
				<input type="text" hidden="true" name="zhengming" :value="zhengming.join(',')" maxlength="-1"></input>
			</view>
			<view class="apply_box">
				<view class="apply_item">
					<text>登录账号<text style="color:red"> *</text></text>
					<view class="flex-y-center"><input type="text" name="un" :value="info.un" placeholder="请填写登录账号" autocomplete="off"></input></view>
				</view>
				<view class="apply_item">
					<text>登录密码<text style="color:red"> *</text></text>
					<view class="flex-y-center"><input type="password" name="pwd" :value="info.pwd" placeholder="请填写登录密码" autocomplete="off"></input></view>
				</view>
				<view class="apply_item">
					<text>确认密码<text style="color:red"> *</text></text>
					<view class="flex-y-center"><input type="password" name="repwd" :value="info.repwd" placeholder="请再次填写密码"></input></view>
				</view>
			</view>
			<block v-if="bset.xieyi_show==1">
			<view class="flex-y-center" style="margin-left:20rpx;color:#999" v-if="!info.id || info.status==2">
				<checkbox-group @change="isagreeChange"><label class="flex-y-center"><checkbox value="1" :checked="isagree"></checkbox>阅读并同意</label></checkbox-group>
				<text style="color:#666" @tap="showxieyiFun">《商户入驻协议》</text>
			</view>
			</block>
			<view style="padding:30rpx 0"><button v-if="!info.id || info.status==2" form-type="submit" class="set-btn" :style="'background:linear-gradient(90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'">提交申请</button>
</view>
		</form>
		
		<view id="xieyi" :hidden="!showxieyi" style="width:100%;height:100%;position:fixed;top:0;left:0;z-index:99;background:rgba(0,0,0,0.7)">
			<view style="width:90%;margin:0 auto;height:85%;margin-top:10%;background:#fff;color:#333;padding:5px 10px 50px 10px;position:relative;border-radius:2px">
				<view style="overflow:scroll;height:100%;">
					<parse :content="bset.xieyi"/>
				</view>
				<view style="position:absolute;z-index:9999;bottom:10px;left:0;right:0;margin:0 auto;text-align:center" @tap="hidexieyi">已阅读并同意</view>
			</view>
		</view>
	</block>
	<loading v-if="loading"></loading>
	<dp-tabbar :opt="opt"></dp-tabbar>
	<popmsg ref="popmsg"></popmsg>
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
      cateArr: [],
      cindex: 0,
      isagree: false,
      showxieyi: false,
			pic:[],
			pics:[],
			zhengming:[],
      info: {},
			bset:{},
      latitude: '',
      longitude: '',
      address:''
    };
  },

  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.getdata();
  },
	onPullDownRefresh: function () {
		this.getdata();
	},
  methods: {
		getdata: function () {
			var that = this;
			that.loading = true;
			app.get('ApiBusiness/apply', {}, function (res) {
				that.loading = false;
				if (res.status == 2) {
					app.alert(res.msg, function () {
						app.goto('/admin/index/index', 'redirect');
					})
					return;
				}
				uni.setNavigationBarTitle({
					title: res.title
				});
				var clist = res.clist;
				var cateArr = [];
				for (var i in clist) {
					cateArr.push(clist[i].name);
				}
				var pics = res.info ? res.info.pics : '';
				if (pics) {
					pics = pics.split(',');
				} else {
					pics = [];
				}
				var zhengming = res.info ? res.info.zhengming : '';
				if (zhengming) {
					zhengming = zhengming.split(',');
				} else {
					zhengming = [];
				}
				that.clist = res.clist
				that.bset = res.bset
				that.info = res.info
        that.address = res.info.address;
        that.latitude = res.info.latitude;
        that.longitude = res.info.longitude;
				that.cateArr = cateArr;
				that.pic = res.info.pic ? [res.info.pic] : [];
				that.pics = pics;
				that.zhengming = zhengming;
				that.loaded();
			});
		},
    cateChange: function (e) {
      this.cindex = e.detail.value;
    },
    locationSelect: function () {
      var that = this;
      uni.chooseLocation({
        success: function (res) {
          that.info.address = res.name;
					that.info.latitude = res.latitude;
          that.info.longitude = res.longitude;
          that.address = res.name;
          that.latitude = res.latitude;
          that.longitude = res.longitude;
        }
      });
    },
    subform: function (e) {
      var that = this;
      var info = e.detail.value;
      if (info.linkman == '') {
        app.error('请填写联系人姓名');
        return false;
      }
      if (info.linktel == '') {
        app.error('请填写联系人电话');
        return false;
      }
      if (info.tel == '') {
        app.error('请填写客服电话');
        return false;
      }
      if (info.name == '') {
        app.error('请填写商家名称');
        return false;
      }
      if (info.zuobiao == '') {
        //app.error('请选择店铺坐标');
        //return false;
      }
      if (info.address == '') {
        app.error('请填写店铺地址');
        return false;
      }
      if (info.pic == '') {
        app.error('请上传商家主图');
        return false;
      }
      if (info.pics == '') {
        app.error('请上传商家照片');
        return false;
      }
      if (info.zhengming == '') {//$.error('请上传证明材料');return false;
      }
      if (info.un == '') {
        app.error('请填写登录账号');
        return false;
      }
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
      } //if(!/(^0?1[3|4|5|6|7|8|9][0-9][0-9][0-9][0-9][0-9][0-9][0-9][0-9][0-9]$)/.test(tel)){
      //	dialog('手机号格式错误');return false;
      //}
      info.address = that.address;
      info.latitude = that.latitude;
      info.longitude = that.longitude;
      if (that.bset.xieyi_show == 1 && !that.isagree) {
        app.error('请先阅读并同意商户入驻协议');
        return false;
      }
      info.cid = that.clist[that.cindex].id;
      if (that.info && that.info.id) {
        info.id = that.info.id;
      }
			app.showLoading('提交中');
      app.post("ApiBusiness/apply", {info: info}, function (res) {
				app.showLoading(false);
        app.error(res.msg);
				if(res.status == 1){
					setTimeout(function () {
						app.goto(app.globalData.indexurl);
					}, 1000);
				}
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
				if(field == 'pic') that.pic = pics;
				if(field == 'pics') that.pics = pics;
				if(field == 'zhengming') that.zhengming = pics;
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
			}else if(field == 'zhengming'){
				var pics = that.zhengming
				pics.splice(index,1);
				that.zhengming = pics;
			}
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

.apply_item{ line-height: 100rpx; display: flex;justify-content: space-between;border-bottom:1px solid #eee }
.apply_box .apply_item:last-child{ border:none}
.apply_item input{ width: 100%; border: none;color:#111;font-size:28rpx; text-align: right}
.apply_item input::placeholder{ color:#999999}
.apply_item textarea{ width:100%;min-height:200rpx;padding:20rpx 0;border: none;}
.apply_item .upload_pic{ margin:50rpx 0;background: #F3F3F3;width:90rpx;height:90rpx; text-align: center  }
.apply_item .upload_pic image{ width: 32rpx;height: 32rpx; }
.set-btn{width: 90%;margin:0 5%;height:96rpx;line-height:96rpx;border-radius:48rpx;color:#FFFFFF;font-weight:bold;}

.layui-imgbox{margin-right:16rpx;margin-bottom:10rpx;font-size:24rpx;position: relative;}
.layui-imgbox-close{position: absolute;display: block;width:32rpx;height:32rpx;right:-16rpx;top:-16rpx;z-index:90;color:#999;font-size:32rpx;background:#fff}
.layui-imgbox-close image{width:100%;height:100%}
.layui-imgbox-img{display: block;width:200rpx;height:200rpx;padding:2px;border: #d3d3d3 1px solid;background-color: #f6f6f6;overflow:hidden}
.layui-imgbox-img>image{max-width:100%;}
.layui-imgbox-repeat{position: absolute;display: block;width:32rpx;height:32rpx;line-height:28rpx;right: 2px;bottom:2px;color:#999;font-size:30rpx;background:#fff}
.uploadbtn{position:relative;height:200rpx;width:200rpx}
</style>