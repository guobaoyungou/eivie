<template>
<view>
	<block>
		<view class="container nodiydata" v-if="isload">
			<view class="bg-red" :style="{background:t('color1')}"></view>
			<view class="topcontent">
				<view class="logo"><image class="img" :src="info.headimg"/></view>
				<view class="title">{{info.realname}} <text :style="{color:t('color1')}" style="margin-left:10rpx;font-size:28rpx">{{info.dengji||''}}</text></view>
				<view class="desc">
					{{info.desc}}
				</view>
			</view>

			<view class="contentbox" v-if="info.content">
				<view class="cp_detail" style="padding:20rpx">
					<parse :content="info.content" />
				</view>				
			</view>
			
		</view>
	</block>
	<loading v-if="loading"></loading>
	<dp-tabbar :opt="opt" @getmenuindex="getmenuindex"></dp-tabbar>
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

			isdiy: 0,

			st: 0,
			info:{},
			topbackhide: false,
			nomore: false,
			nodata:false,
		}
	},
	onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.st = this.opt.st || 0;
		this.getdata();
  },
	onPullDownRefresh: function () {
		this.getdata();
	},
	onReachBottom: function () {
		
	},
	onShareAppMessage:function(){
		return this._sharewx({title:this.info.name});
	},
	onPageScroll: function (e) {
		if (this.isdiy == 0) {
			var that = this;
			var scrollY = e.scrollTop;
			if (scrollY > 200 && !that.topbackhide) {
				that.topbackhide = true;
			}
			if (scrollY < 150 && that.topbackhide) {
				that.topbackhide = false;
			}
		}
	},
	methods: {
		getdata: function () {
			var that = this;
			var id = that.opt.id || 0;
			that.loading = true;
			app.get('ApiYueke/workerinfo', {id: id}, function (res) {
				that.loading = false;
				that.info = res.info;
				
				that.loaded({title:that.info.realname,pic:that.info.headimg});

				that.isload = 1;
				uni.setNavigationBarTitle({
					title: that.info.realname
				});
			});
		}
	}
}
</script>
<style>
.container{position:relative}
.nodiydata{display:flex;flex-direction:column}
.bg-red { background-color:#f32e28; height: 300rpx;}
.nodiydata .swiper {width: 100%;height: 400rpx;position:relative;z-index:1}
.nodiydata .swiper .image {width: 100%;height: 400rpx;overflow: hidden;}

.nodiydata .topcontent{width:96%;margin:-120rpx 2% 20rpx ;padding: 24rpx; border-bottom:1px solid #eee; background: #fff;display:flex;flex-direction:column;align-items:center;border-radius:16rpx;position:relative;z-index:2;}
.nodiydata .topcontent .logo{margin-top: -100rpx;width:160rpx;height:160rpx;border:2px solid rgba(255,255,255,0.5);border-radius:50%;}
.nodiydata .topcontent .logo .img{width:100%;height:100%;border-radius:50%;}

.nodiydata .topcontent .title {color:#222222;font-size:36rpx;font-weight:bold;margin-top:12rpx}
.nodiydata .topcontent .desc {display:flex;align-items:center; margin: 20rpx 0;}
.nodiydata .topcontent .tel{font-size:26rpx;color:#fff; padding:12rpx 28rpx; border-radius: 10rpx; font-weight: normal }
.nodiydata .topcontent .tel .img{ width: 28rpx;height: 28rpx; vertical-align: middle;margin-right: 10rpx}
.nodiydata .topcontent .address{width:100%;display:flex;align-items:center;margin-top:20rpx;margin-bottom:20rpx;padding-top:20rpx}
.nodiydata .topcontent .address .f1{width:28rpx;height:28rpx;margin-right:8rpx}
.nodiydata .topcontent .address .f2{flex:1;color:#999999;font-size:26rpx}
.nodiydata .topcontent .address .f3{display: inline-block; width:26rpx; height: 26rpx}
.img-view { display: flex; margin-top: 20rpx; flex-wrap:wrap; flex-direction: row; width: 100%;}
.img-view view { width: 33%; text-align: center;}
.img-view .image { width: 96%;}

.nodiydata .contentbox{width:96%;margin-left:2%;background: #fff;border-radius:16rpx;margin-bottom:32rpx;overflow:hidden}

.nodiydata .shop_tab{display:flex;width: 100%;height:90rpx;border-bottom:1px solid #eee;}
.nodiydata .shop_tab .cptab_text{flex:1;text-align:center;color:#646566;height:90rpx;line-height:90rpx;position:relative}
.nodiydata .shop_tab .cptab_current{color: #323233;}
.nodiydata .shop_tab .after{display:none;position:absolute;left:50%;margin-left:-16rpx;bottom:10rpx;height:3px;border-radius:1.5px;width:32rpx}
.nodiydata .shop_tab .cptab_current .after{display:block;}


.nodiydata .cp_detail{min-height:500rpx}

.nodiydata .nomore-footer-tips{background:#fff!important}

</style>