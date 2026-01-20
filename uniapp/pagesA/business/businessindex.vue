<template>
<view class="pageContainer" v-if="isload">
	<view class="content">
		<swiper v-if="pics.length>0" class="swiper" :indicator-dots="pics[1]?true:false" :autoplay="true" :interval="5000" indicator-color="#dcdcdc" indicator-active-color="#fff">
			<block v-for="(item, index) in pics" :key="index">
				<swiper-item class="swiper-item">
					<image :src="item" mode="widthFix" class="image"/>
				</swiper-item>
			</block>
		</swiper>
		<view class="topcontent">
			<view class="f1 flex">
				<!-- <view class="logo"><image class="img" :src="business.logo"/></view> -->
				<view class="title">{{business.name}}</view>
			</view>
			<view class="f2 flex">
				<view class="t1">电话：{{business.tel}}</view>	
				<view class="button" @tap="phone" :data-phone="business.tel" :style="{background:'rgba('+t('color1rgb')+',0.1)',color:t('color1')}">拨号</view>
			</view>
			<view class="f2 flex">
				<view class="t1">地址：{{business.address}}</view>	
				<view class="button" :style="{background:'rgba('+t('color1rgb')+',0.1)',color:t('color1')}"  @tap="openLocation" :data-latitude="business.latitude" :data-longitude="business.longitude" :data-company="business.name" :data-address="business.address">导航</view>
			</view>
		</view>
		<view class="contentbox">
			<view class="shop_tab">
				<view v-if="bset && bset.show_detail" :class="'cptab_text ' + (st==-1?'cptab_current':'')" @tap="changetab" data-st="-1">相册<view class="after" :style="{background:t('color1')}"></view></view>
				<view v-if="bset && bset.show_detail" :class="'cptab_text ' + (st==0?'cptab_current':'')" @tap="changetab" data-st="0">{{bset && bset.show_detailtext?bset.show_detailtext:'商家详情'}}<view class="after" :style="{background:t('color1')}"></view></view>
					<view v-if="bset && bset.show_product" :class="'cptab_text ' + (st==1?'cptab_current':'')" @tap="changetab" data-st="1">{{bset && bset.show_producttext?bset.show_producttext:'本店商品'}}<view class="after" :style="{background:t('color1')}"></view></view>
			</view>
			<view class="cp_detail" v-if="st==-1" style="padding:20rpx">
				<block v-for="(item, index) in pics">
					<image :src="item" mode="widthFix" class="image"/>
				</block>
			</view>
			<view class="cp_detail" v-if="st==0" style="padding:20rpx">
				<parse :content="business.content"></parse>
			</view>
			<view class="cp_detail" v-if="st==1" style="padding-top:20rpx">
				<dp-product-itemlist :data="datalist" :menuindex="menuindex"></dp-product-itemlist>
				<nomore v-if="nomore"></nomore>
				<nodata v-if="nodata"></nodata>
			</view>
		</view>
	</view>
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
			nomore: false,
			nodata:false,
			pics:[],
			business:[],
			bset:'',
			st:-1,
			pagenum:1,
			datalist:[],
			latitude:'',
			longitude:'',
		}
	},
	onLoad: function (opt) {
		this.opt = app.getopts(opt);
		
		this.getdata();
  },
	onShow:function() {
	
	},
	onPullDownRefresh: function () {
		this.getdata();
	},
	onReachBottom: function () {
		if (!this.nodata && !this.nomore) {
			this.pagenum = this.pagenum + 1;
			this.getDataList(true);
		}
	},
	methods: {
	
		getdata: function () {
			var that = this;
			var id = that.opt.id || 0;
			
			that.loading = true;
			app.get('ApiBusiness/index', {id: id,latitude:that.latitude,longitude:that.longitude}, function (res) {
				that.loading = false;
				that.pics = res.pics;
				that.business = res.business;
				that.bset = res.bset;
				that.loaded();
			});
		},
		changetab: function (e) {
			var st = e.currentTarget.dataset.st;
			this.pagenum = 1;
			this.st = st;
			this.datalist = [];
			uni.pageScrollTo({
				scrollTop: 0,
				duration: 0
			});
			this.getDataList();
		},
		getDataList: function (loadmore) {
			if(!loadmore){
				this.pagenum = 1;
				this.datalist = [];
			}
			var that = this;
			var pagenum = that.pagenum;
			that.loading = true;
			that.nodata = false;
			that.nomore = false;
			app.post('ApiBusiness/getdatalist', {id: that.business.id,st: 0,pagenum: pagenum,yuyue_cid:that.yuyue_cid,mendian_id:that.mendianid}, function (res) {
				that.loading = false;
				uni.stopPullDownRefresh();
        var data = res.data;
        if (pagenum == 1) {
          that.datalist = data;
          if (data.length == 0) {
            that.nodata = true;
          }
        }else{
          if (data.length == 0) {
            that.nomore = true;
          } else {
            var datalist = that.datalist;
            var newdata = datalist.concat(data);
            that.datalist = newdata;
          }
        }
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
			 scale: 13
		 })		
		},
		phone:function(e) {
			var phone = e.currentTarget.dataset.phone;
			console.log(phone);
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
	@import url("../../pages/index/location.css");
	.pageContainer{
		position: absolute;
		width: 100%;
		height: 100%;
	}
	.content .swiper {width: 100%;height: 400rpx;position:relative;z-index:1}
	.content .swiper .image {width: 100%;height: 400rpx;overflow: hidden;}
	.content .topcontent{width:94%;margin-left:3%;padding: 24rpx; border-bottom:1px solid #eee; background: #fff;display:flex;flex-direction:column;border-radius:16rpx;position:relative;z-index:2;}
	.content .topcontent .f1{align-items: center;margin-left: 20prx;}
	.content .topcontent .f1 .logo{width:120rpx;height:120rpx;}
	.content .topcontent .f1 .logo .img{width:100%;height:100%;border-radius:50%;}
	.content .topcontent .f1 .title{color:#222222;font-size:36rpx;font-weight:bold;margin-top:12rpx;width: 79%;overflow: hidden;}
	.content .topcontent .f2{align-items: center;justify-content: space-between;margin: 10rpx 5rpx}
	.content .topcontent .f2 .t1{flex: 1;width: 70%;}
	.content .topcontent .f2 .button{width: 160rpx;font-size:28rpx;color:#fff; border-radius: 10rpx; font-weight: normal;line-height: 60rpx;text-align: center; }
	.content .contentbox{width:94%;margin-left:3%;background: #fff;border-radius:16rpx;margin-bottom:32rpx;overflow:hidden;margin-top: 20rpx;}
	.content .shop_tab{display:flex;width: 100%;height:90rpx;border-bottom:1px solid #eee;}
	.content .shop_tab .cptab_text{flex:1;text-align:center;color:#646566;height:90rpx;line-height:90rpx;position:relative}
	.content .shop_tab .cptab_current{color: #323233;}
	.content .shop_tab .after{display:none;position:absolute;left:50%;margin-left:-16rpx;bottom:10rpx;height:3px;border-radius:1.5px;width:32rpx}
	.content .shop_tab .cptab_current .after{display:block;}
	.content .cp_detail{min-height:500rpx}
</style>