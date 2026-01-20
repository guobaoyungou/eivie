<template>
<scroll-view class="pageContainer">
		<view v-if="isload">
      <view class="container nodiydata" v-if="data.hxqrcode">
        <view class="topcontent">
          <view class="logo"><image class="img" :src="data.hxqrcode" @tap="previewImage" :data-url="data.hxqrcode" mode="widthFix"/></view>
        </view>
        <view class="topcontent1">
          <view class="title" style="margin-top: 20rpx;font-size:42rpx;font-weight:bold">向{{t('门店')}}出示二维码提货</view>
          <view class="title" style="margin-top: 40rpx;font-size:28rpx;margin-top: 20rpx;" v-if="data.mendiantel">{{t('门店')}}客服：<text  class="tel" @tap="calltel" :data-tel="data.mendiantel" style="color: #1E9FFF">{{data.mendiantel}}</text></view>
        </view>
        <view class="topcontent1">
          <button class="btn" @tap.stop="goback">返回</button>
        </view>
      </view>
      <nodata v-if="nodata"></nodata>
		</view>
	<popmsg ref="popmsg"></popmsg>
	<loading v-if="loading" ></loading>
	<wxxieyi></wxxieyi>
</scroll-view> 
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
			platform: app.globalData.platform,
      data:[],
			pagenum: 1,
			datalist: [],
			nomore: false,
			nodata:false,
      keyword:'',
		}
	},
	onLoad: function (opt) {
		this.opt = app.getopts(opt);
    this.st = this.opt.st || 0;
    uni.setNavigationBarTitle({
      title: this.t('门店') + '核销二维码'
    });
		this.getdata();
  },
	onShow:function() {
		if(app.globalData.platform=='wx' && app.globalData.hide_home_button==1){
			uni.hideHomeButton();
		}
	},
	onPullDownRefresh: function () {
		this.getdata();
	},
	onReachBottom: function () {
		if (this.isdiy == 0) {
			if (!this.nodata && !this.nomore) {
				this.pagenum = this.pagenum + 1;
				this.getDataList(true);
			}
		}
	},
	onPageScroll: function (e) {
		uni.$emit('onPageScroll',e);
	},
	onShareAppMessage:function(){
		//#ifdef MP-TOUTIAO
		console.log(shareOption);
			return {
				
				title: this.video_title,
				channel: "video",
				extra: {
				        hashtag_list: this.video_tag,
				      },
				success: () => {
					console.log("分享成功");
				},
				 fail: (res) => {
				    console.log(res);
				    // 可根据 res.errCode 处理失败case
				  },
			};
		//#endif
		
		return this._sharewx({title:this.business.name});
	},
	onShareTimeline:function(){
		var sharewxdata = this._sharewx({title:this.business.name,pic:this.business.logo});
		var query = (sharewxdata.path).split('?')[1]+'&seetype=circle';
		return {
			title: sharewxdata.title,
			imageUrl: sharewxdata.imageUrl,
			query: query
		}
	},
	methods: {
		getdata: function () {
			var that = this;

			that.loading = true;
			app.post('ApiMendian/hxqrcode', {keyword:that.keyword}, function (res) {
				that.loading = false;
        if(res.status == 0){
          app.alert(res.msg);
          return;
        }
        that.isload = true;
        if(!res.data.hxqrcode){
          that.nodata = true;
        }
				that.data = res.data;

			});
		},
    previewImage: function (e) {
      var imgurl = e.currentTarget.dataset.url
      var imgurls = e.currentTarget.dataset.urls
      if (!imgurls) imgurls = imgurl;
      if(!imgurls) return;
      if (typeof (imgurls) == 'string') imgurls = imgurls.split(',');
      uni.previewImage({
        current: imgurl,
        urls: imgurls
      })
    },
    searchConfirm:function(e){
      this.keyword = e.detail.value;
      this.getdata(false);
    },
    //打电话
    calltel: function (e) {
      var tel = e.currentTarget.dataset.tel;
      wx.makePhoneCall({
        phoneNumber: tel
      })
    },
    gobakc: function (e) {
      // 返回上一页
      uni.navigateBack({
        delta: 1 // 返回的页面数，默认为1
      });
    },
	}
}
</script>
<style>
	@import url("../../pages/index/location.css");
	.pageContainer{
		/* position: absolute; */
		width: 100%;
		height: 100%;
	}
.container{position:relative}
.nodiydata{display:flex;flex-direction:column}
.nodiydata .topcontent{width:94%;margin-left:3%;padding: 40rpx; border-bottom:1px solid #eee;margin-bottom:20rpx; background: #fff;display:flex;flex-direction:column;align-items:center;border-radius:16rpx;position:relative;z-index:2;margin-top: 20rpx;height: 700rpx}
.nodiydata .topcontent1{width:94%;margin-left:3%;padding: 40rpx; margin-bottom:20rpx; display:flex;flex-direction:column;align-items:center;border-radius:16rpx;position:relative;z-index:2;}
.nodiydata .topcontent .logo{width:500rpx;height:500rpx;border:2px solid rgba(255,255,255,0.5);margin-top: 50rpx}
.nodiydata .topcontent .logo .img{width:100%;height:100%;}
.nodiydata .topcontent .title {color:#222222;margin-top:12rpx}
.nodiydata .topcontent .desc .f1 .img{ width:24rpx;height:24rpx;margin-right:10rpx;}
.nodiydata .topcontent .tel{font-size:28rpx;color:#fff; padding:16rpx 40rpx; border-radius: 60rpx; font-weight: normal }
.nodiydata .topcontent .tel1{margin-left:60rpx;font-size:28rpx;color:#fff; padding:16rpx 40rpx; border-radius: 60rpx; font-weight: normal }
.nodiydata .topcontent .tel .img{ width: 28rpx;height: 28rpx; vertical-align: middle;margin-right: 10rpx}
.nodiydata .topcontent .address{width:100%;display:flex;align-items:center;padding-top:20rpx}
.nodiydata .comment .item .f1 .t3 .img{width:24rpx;height:24rpx;margin-left:10rpx}
.nodiydata .comment .item .score image{ width: 140rpx; height: 50rpx; vertical-align: middle;  margin-bottom:6rpx; margin-right: 6rpx;}
.nodiydata .comment .item .f2 .t2 image{width:100rpx;height:100rpx;margin:10rpx;}
.contentbox .free_product .right .title{ color: #222222; font-weight: bold;}
.contentbox .free_product .hexiao button{ color: #fff; border-radius: 50rpx;padding:0 40rpx; height: 60rpx; line-height: 60rpx;}
.contentbox .pic image{ width: 130rpx; height: 130rpx; border-radius: 10rpx;}
.topsearch{width:94%;margin:10rpx 3%;}
.topsearch .f1{height:60rpx;border-radius:30rpx;border:0;background-color:#fff;flex:1}
.topsearch .f1 .img{width:24rpx;height:24rpx;margin-left:10px}
.topsearch .f1 input{height:100%;flex:1;padding:0 20rpx;font-size:28rpx;color:#333;}
.topcontent1 .btn{ height: 88rpx;line-height: 88rpx;background: #FC4343;width:80%;margin:0 auto;border-radius:10rpx;margin-top:80rpx;color: #fff;font-size: 36rpx;}
</style>