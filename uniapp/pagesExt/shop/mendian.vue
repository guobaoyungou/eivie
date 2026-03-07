<template>
<view>
	<block>
		<view class="container nodiydata" v-if="isload">
			<view class="bg-red" :style="{background:t('color1')}"></view>
			<view class="topcontent">
				<view class="logo"><image class="img" :src="info.pic"/></view>
				<view class="title">{{info.name}}</view>
				<view class="desc">
					<block v-if="info.subname">{{info.subname}}</block>
				</view>
				<view class="tel" :style="{background:'linear-gradient(90deg,'+t('color1')+' 0%, rgba('+t('color1rgb')+',0.8) 100%)'}"><view @tap="phone" :data-phone="info.tel" class="tel_online"><image class="img" :src="pre_url+'/static/img/telwhite.png'"/>联系门店</view></view>
				<view class="address" @tap="openLocation" :data-latitude="info.latitude" :data-longitude="info.longitude" :data-company="info.name" :data-address="info.address">
					<image class="f1" :src="pre_url+'/static/img/shop_addr.png'"/>
					<view class="f2">{{info.address}}</view>
					<image class="f3" :src="pre_url+'/static/img/arrowright.png'" />
				</view>
				<view class="img-view">
					<view v-if="pics.length>0" v-for="(item, index) in pics" :key="index">
						<image :src="item" mode="widthFix" class="image" @tap="previewImage" :data-url="item"/>
					</view>
					<!-- <view id="content_picpreview" class="flex" style="flex-wrap:wrap;padding-top:20rpx">
						<view v-for="(item, index) in detail.refund_pics" :key="index" class="layui-imgbox">
							<view class="layui-imgbox-img"><image :src="item" @tap="previewImage" :data-url="item" mode="widthFix"></image></view>
						</view>
					</view> -->
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
			pics:[],
			pagenum: 1,
			datalist: [],
			topbackhide: false,
			nomore: false,
			nodata:false,

			title: "",
			sysset: "",
			pageinfo: "",
			pagecontent: "",
			pre_url: app.globalData.pre_url,
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
			app.get('ApiShop/mendian', {id: id}, function (res) {
				that.loading = false;
				that.info = res.info;
				that.pics = res.info.pics;
				
				that.loaded({title:that.info.name,pic:that.info.logo});

				that.isload = 1;
				uni.setNavigationBarTitle({
					title: that.info.name
				});
			});
		},
		openLocation:function(e){
			//console.log(e)
			var latitude = parseFloat(e.currentTarget.dataset.latitude)
			var longitude = parseFloat(e.currentTarget.dataset.longitude)
			var address = e.currentTarget.dataset.address
			uni.openLocation({
			 latitude:latitude,
			 longitude:longitude,
			 name:address,
			 scale: 13,
			 address:address,
		 })		
		},
		phone:function(e) {
			var phone = e.currentTarget.dataset.phone;
			uni.makePhoneCall({
				phoneNumber: phone,
				fail: function () {
				}
			});
		},
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
.nodiydata .topcontent .desc {display:flex;align-items:center; margin: 10rpx 0;}
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