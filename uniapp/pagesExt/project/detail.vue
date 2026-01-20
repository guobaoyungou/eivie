<template>
<view>
	<block v-if="isload">
		<form @submit="subform">
			<view class="apply_box">
				<view class="topcontent flex">
					<view class="logo"><image class="img" :src="info.logo"/></view>
					<view class="desc">
						<view class="f2">{{info.content}}</view>
					</view>
				</view>
				<view class="apply_item" style="border-bottom:0"><text>邀请二维码</text></view>
				<view class="flex" style="flex-wrap:wrap;padding-bottom:20rpx;justify-content: center;">
					<view v-for="(item, index) in pic" :key="index" class="layui-imgbox" v-if="pic.length>0">
						<view class="layui-imgbox-img"><image :src="item" @tap="previewImage" :data-url="item" mode="widthFix"></image></view>
					</view>
					<view v-if="pic.length==0">{{info.tips}}</view>
				</view>
				<input type="text" hidden="true" name="pic" :value="pic.join(',')" maxlength="-1"></input>
				<!-- <view class="apply_item" v-if="info.tel">
					<view>联系手机号</view>
					<view class="flex-y-center"><input type="text" name="tel" :value="info.tel" ></input></view>
				</view> -->
			</view>
			
			<view style="padding:30rpx 0">
				<button class="set-btn" @tap="goto" :data-url="'record?pid='+info.id" :style="'background:linear-gradient(90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'">上传我的邀请码</button>
			</view>
		</form>
		
		
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
			pic:[],
			pics:[],
			logo:[],
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
			let id = this.opt.id;
			that.loading = true;
			app.get('ApiProject/detail', {id:id}, function (res) {
				that.loading = false;
				uni.setNavigationBarTitle({
					title: res.info.name
				});

				var pics = res.info ? res.info.pics : '';
				if (pics) {
					pics = pics.split(',');
				} else {
					pics = [];
				}
				that.info = res.info
				that.logo = res.info.logo ? [res.info.logo] : [];
				that.pic = res.info.pic ? [res.info.pic] : [];
				that.pics = pics;
				that.loaded();
			});
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

.apply_item{ line-height: 100rpx; display: flex;justify-content: center;border-bottom:1px solid #eee }
.apply_item .upload_pic{ margin:50rpx 0;background: #F3F3F3;width:90rpx;height:90rpx; text-align: center  }
.apply_item .upload_pic image{ width: 32rpx;height: 32rpx; }
.set-btn{width: 90%;margin:0 5%;height:96rpx;line-height:96rpx;border-radius:48rpx;color:#FFFFFF;font-weight:bold;}

.uploadbtn{position:relative;height:200rpx;width:200rpx}

 .topcontent{padding: 20rpx 0;margin-bottom:20rpx; background: #fff;isplay:flex;border-radius:16rpx;position:relative;z-index:2;}
 .topcontent .logo{width:160rpx;height:160rpx;border:2px solid rgba(255,255,255,0.5);border-radius:50%;}
 .topcontent .logo .img{width:100%;height:100%;border-radius:50%;}

 .topcontent .title {color:#222222;font-size:36rpx;font-weight:bold;margin-top:12rpx}
 .topcontent .desc {display:flex;align-items:center;margin-left: 10rpx;flex: 1;}
 .topcontent .desc .f1{ margin:20rpx 0; font-size: 24rpx;color:#FC5648;display:flex;align-items:center}
 .topcontent .desc .f1 .img{ width:24rpx;height:24rpx;margin-right:10rpx;}
 .topcontent .desc .f2{ margin:10rpx 0;font-size: 24rpx;color:#666;}
</style>