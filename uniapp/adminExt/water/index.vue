<template>
<view class="container">
	<!-- #ifndef H5 -->
<!--	<view class="navigation">-->
<!--		<view class='navcontent' :style="{marginTop:navigationMenu.top+'px',width:(navigationMenu.right)+'px'}">-->
<!--			<view class="header-location-top" :style="{height:navigationMenu.height+'px'}">-->
<!--				<view class="header-page-title" style="color:#000000;">扫码打水管理</view>-->
<!--			</view>-->
<!--		</view>-->
<!--	</view>-->
	<!-- #endif -->

	<block v-if="isload">
		
	<view class="surverycontent" >
		<view class="item">
				<view class="t2"><text>今日</text></view>
				<view class="t3">销售金额：￥{{info.d_xiaoshou}}</view>
				<text class="t3">实收金额：￥{{info.d_shishou}}</text>
				<text class="t3">退款金额：￥{{info.d_tuikuan}}</text>
		 </view>
		 <view class="item" >
				<view class="t2"><text>本月</text></view>
				<view class="t3">销售金额：￥{{info.m_xiaoshou}}</view>
				<text class="t3">实收金额：￥{{info.m_shishou}}</text>
				<text class="t3">退款金额：￥{{info.m_tuikuan}}</text>
		 </view>

	</view>

	<view class="listcontent">
		<view class="list">
			<view class="item" @tap="goto" data-url="/adminExt/water/deviceList">
				<view class="f1"><image :src="pre_url+'/static/img/admin/financenbg1.png'"></image></view>
				<view class="f2">设备管理</view>
				<text class="f3"></text>
				<image :src="pre_url+'/static/img/admin/financejiantou.png'" class="f4"></image>
			</view>
      <view class="item" @tap="goto" data-url="/adminExt/water/orderList">
        <view class="f1"><image :src="pre_url+'/static/img/admin/financenbg2.png'"></image></view>
        <view class="f2">打水订单</view>
        <text class="f3"></text>
        <image :src="pre_url+'/static/img/admin/financejiantou.png'" class="f4"></image>
      </view>
		</view>
	</view>
	</block>
	<popmsg ref="popmsg"></popmsg>

	<loading v-if="loading"></loading>
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
			pre_url:app.globalData.pre_url,
			navigationMenu:{},
			bid:0,
			showmdmoney:0,
      info: {},
      auth_data: {},
      wxauth_data: {},
			auth_data_menu: [],
      showyuebao_moneylog:false,
      showyuebao_withdrawlog:false,
			showbscore:false,
			showcouponmoney:false,
			show:{},
			platform: app.globalData.platform,
			statusBarHeight: 20,
			mdid:0,
			index_data:[]
    };
  },
  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.getdata();
		var sysinfo = uni.getSystemInfoSync();
		this.statusBarHeight = sysinfo.statusBarHeight;
		this.wxNavigationBarMenu();
  },
	onPullDownRefresh: function () {
		this.getdata();
	},
  methods: {
		wxNavigationBarMenu:function(){
			if(this.platform=='wx'){
				//胶囊菜单信息
				this.navigationMenu = wx.getMenuButtonBoundingClientRect()
			}
		},
		getdata:function(){
			var that = this
			that.loading = true;
			app.post('ApiAdminWaterHappyti/index', {}, function (res) {
				that.loading = false;
				if (res.status == 0) {
					app.alert(res.msg);
					return;
				}
				that.info = res.info;
				that.bid = res.bid;
				that.loaded();
			});
		}
  }
};
</script>
<style>
//@import "../common.css";
.container{ width:100%;display:flex;flex-direction:column}
.content{ width:94%;margin:0 3% 20rpx 3%;}
page{background: #fff;}
.surverycontent{width: 100%;padding:20rpx 24rpx 30rpx;display:flex;flex-wrap:wrap;background: #fff;padding-top:20rpx;display:flex;justify-content: space-between;}
.surverycontent .item{width:340rpx;background: linear-gradient(to right,#ddeafe,#e6effe);margin-bottom:20rpx;padding:26rpx 30rpx;display:flex;flex-direction:column;border-radius:20rpx;}
.surverycontent .item .t1{width: 100%;color: #121212;font-size:24rpx;display: flex;align-items: center;justify-content: space-between;}
.surverycontent .item .t1 image{width: 25rpx;height: 25rpx;}
.surverycontent .item .t2{width: 100%;color: #222;font-size:36rpx;font-weight:bold;overflow-wrap: break-word;display: flex;align-items: flex-end;justify-content: flex-start;padding: 15rpx 0rpx;}
.surverycontent .item .t2 .price-unit{font-size: 24rpx;color: #222;font-weight:none;padding-bottom: 6rpx;margin-left: 5rpx;}
.surverycontent .item .t3{width: 100%;color: #999;font-size:24rpx;display: flex;align-items: center;flex-wrap: wrap;}
.surverycontent .item .t3:nth-first{margin-bottom: 10rpx;}
.surverycontent .item .t3 .price-color{color: #0060FF;display: flex;align-items: center;display: flex;align-items: center;}
.surverycontent .item .t3 .price-color image{width: 20rpx;height: 24rpx;margin-left: 10rpx;}
.listcontent{width: 100%;padding:0 40rpx;background: #fff;position: relative;top:-20rpx;}
.listcontent .title-view{position: relative;color: #242424;font-size: 30rpx;text-align: center;padding: 40rpx 0rpx 28rpx;font-weight: bold;}
.listcontent .title-view::before{content:" ";width:120rpx;height: 8rpx;border-radius: 8rpx;background: rgba(50, 143, 255, 0.2);display: block;position: absolute;left: 50%;margin-left: -60rpx;top: 68rpx;}
.list{ width: 100%;background: #fff;}
.divider-line{width: 670rpx;height: 8rpx;background: #F2F3F4;margin: 20rpx 0rpx;}
.list {margin-bottom: 20rpx;}
.list .item{ height:100rpx;line-height:100rpx;display:flex;align-items:center;}
.list .f1{width:56rpx;height:56rpx;line-height:56rpx;display:flex;align-items:center}
.list .f1 image{ width:56rpx;height:56rpx;}
.list .f1 span{ width:40rpx;height:40rpx;font-size:40rpx}
.list .f2{font-size: 28rpx;color:#222;font-weight: bold;margin-left: 20rpx;}
.list .f3{ color: #979797;text-align:right;flex:1;font-size: 24rpx;margin-right: 20rpx;display: flex;justify-content:flex-end;}
.list .f3-price{color: #2A6DF7;}
.list .f3 .f3-tisp{width: 28rpx;height: 28rpx;background: #EB4237;color: #fff;font-size: 24rpx;border-radius: 50%;text-align: center;line-height: 28rpx;}
.list .f4{ width: 40rpx; height: 40rpx;}
.navigation {width: 100%;padding-bottom:10px;overflow: hidden;}
.navcontent {display: flex;align-items: center;padding-left: 10px;}
.header-location-top{position: relative;display: flex;justify-content: center;align-items: center;flex:1;}
.header-back-but{position: absolute;left:0;display: flex;align-items: center;width: 40rpx;height: 45rpx;overflow: hidden;}
.header-back-but image{width: 40rpx;height: 45rpx;} 
.header-page-title{font-size: 36rpx;font-weight: bold;}
</style>
