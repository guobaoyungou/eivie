<template>
<view class="container">
	<block v-if="isload">
		<view class="topfix">
			<view class="head-total" style="background-color: #fd2936;color: #feef2f;">
				<view class="data-search flex-xy-center">
          <view class="header-back-but" @tap="goback">
            <image :src="`${pre_url}/static/img/hotel/fanhui.png`"></image>
          </view>
					<view class="data-btn" :class="{selected: datetype === 'today'}" @tap="changeDateType('today')">今日</view>
					<view class="data-btn" :class="{selected: datetype === 'week'}" @tap="changeDateType('week')">近7天</view>
					<view class="data-btn" :class="{selected: datetype === 'month'}" @tap="changeDateType('month')">本月</view>
					<view class="data-btn" :class="{selected: datetype === 'all'}" @tap="changeDateType('all')">累计</view>
				</view>
				<view class="pv-box">
					<view class="pv-title" :style="{background:'url('+pre_url+'/static/img/countbj.png) no-repeat',backgroundSize: '70%',backgroundPosition: 'center'}">业绩统计(PV)</view>
					<view class="pv">{{count.rangli_count}}</view>
				</view>
				<view class="invite-business flex-xy-center">
					<view class="invite-btn" @tap="goto" :data-url="yqpath">→ 邀请商家入驻</view>
				</view>
				<view class="tongji flex-y-center">
					<view class="stat-item">
						<view>商家数量</view>
						<view class="price">{{count.business_count}}</view>
					</view>
					<view class="stat-item mb0">
						<view>推广业绩</view>
						<view class="price">{{count.commission_count}}</view>
					</view>
					<view class="stat-item">
						<view>订单数量</view>
						<view class="price">{{count.order_count}}</view>
					</view>
				</view>
			</view>
			<dd-tab :itemdata="['累计让利优先','让利比例优先']" :itemst="['1','2']" :st="st" :isfixed="false" @changetab="changetab"></dd-tab>
		</view>
		<block>
			<view class="ind_business">
				<block v-if="st==1">
					<view v-for="(item, index) in datalist" :key="index" @tap="goto" :data-url=" '/pagesExt/business/index?id=' + item.id">
						<view class="ind_busbox">
							<view class="flex1 flex-row">
								<view style="width: 42%;">
									<view class="ind_buspic flex0">
										<image :src="item.logo"></image>
									</view>
								</view>
								<view class="flex1">
									<view style="display: flex;justify-content: space-between;align-items: center;">
										<view class="bus_title">{{item.name}}</view>
									</view>
									<view class="bus_score">
										<view style="display:flex;align-items:center;">
											<image class="img" v-for="(item2,index2) in [0,1,2,3,4]" :src="pre_url+'/static/img/star' + (item.comment_score>item2?'2native':'') + '.png'"/>
											<view class="txt">{{item.comment_score}}分</view>
										</view>
									</view>
							
									<view class="bus_address" v-if="item.address" @tap.stop="openLocation" :data-latitude="item.latitude" :data-longitude="item.longitude" :data-company="item.name" :data-address="item.address">
										<image :src="pre_url+'/static/img/b_addr.png'" style="width:26rpx;height:26rpx;margin-right:10rpx"/>
										<text class="x1">{{item.address}}</text>
										<text class="x2">{{item.juli}}</text>
									</view>
									<view class="bus_address" v-if="item.tel" @tap.stop="phone" :data-phone="item.tel">
										<image :src="pre_url+'/static/img/b_tel.png'" style="width:26rpx;height:26rpx;margin-right:10rpx"/>
										<text class="x1">联系电话：{{item.tel}}</text>
									</view>
								</view>
							</view>
							<view class="flex">
								<view class="activecoin flex rl-ratio">
									<text class="rl-title">让利比例</text>
									<text  class="rl-number">{{item.newscore_ratio}}%</text>
								</view>
								<view class="activecoin flex rl-total">
									<text class="rl-title">累计让利</text>
									<text class="rl-number">{{item.newscore_total}}</text>
								</view>
							</view>
						</view>
					</view>
				</block>
				<block v-if="st==2">
					<view v-for="(item, index) in datalist" :key="index" @tap="goto" :data-url=" '/pagesExt/business/index?id=' + item.id">
						<view class="ind_busbox">
							<view class="flex1 flex-row">
								<view style="width: 42%;">
									<view class="ind_buspic flex0">
										<image :src="item.logo"></image>
									</view>
								</view>
								<view class="flex1">
									<view style="display: flex;justify-content: space-between;align-items: center;">
										<view class="bus_title">{{item.name}}</view>
									</view>
									<view class="bus_score">
										<view style="display:flex;align-items:center;">
											<image class="img" v-for="(item2,index2) in [0,1,2,3,4]" :src="pre_url+'/static/img/star' + (item.comment_score>item2?'2native':'') + '.png'"/>
											<view class="txt">{{item.comment_score}}分</view>
										</view>
									</view>
							
									<view class="bus_address" v-if="item.address" @tap.stop="openLocation" :data-latitude="item.latitude" :data-longitude="item.longitude" :data-company="item.name" :data-address="item.address">
										<image :src="pre_url+'/static/img/b_addr.png'" style="width:26rpx;height:26rpx;margin-right:10rpx"/>
										<text class="x1">{{item.address}}</text>
									</view>
									<view class="bus_address" v-if="item.tel" @tap.stop="phone" :data-phone="item.tel">
										<image :src="pre_url+'/static/img/b_tel.png'" style="width:26rpx;height:26rpx;margin-right:10rpx"/>
										<text class="x1">联系电话：{{item.tel}}</text>
									</view>
								</view>
							</view>
							<view class="flex">
								<view class="activecoin flex rl-ratio">
									<text class="rl-title">让利比例</text>
									<text  class="rl-number">{{item.newscore_ratio}}%</text>
								</view>
								<view class="activecoin flex rl-total">
									<text class="rl-title">累计让利</text>
									<text class="rl-number">{{item.newscore_total}}</text>
								</view>
							</view>
						</view>
					</view>
				</block>
				<nodata v-if="nodata"></nodata>
				<nomore v-if="nomore"></nomore>
			</view>
		</block>
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
			st: '1',
			count:{},
			pagenum: 1,
			datalist: [],
			nodata: false,
			nomore: false,
			pre_url:app.globalData.pre_url,
			yqpath:'',
      datetype: 'today'
    };
  },

  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.getdata();
  },
	onPullDownRefresh: function () {
		this.pagenum = 1;
		this.datalist = [];
		this.getdata();
	},
  onReachBottom: function () {
    if (!this.nodata && !this.nomore ) {
      this.pagenum = this.pagenum + 1;
      this.getdata();
    }
  },
  methods: {
		getdata: function () {
			var that = this;
      var st = that.st;
      var pagenum = that.pagenum;
      var datetype = that.datetype;
      that.loading = true;
      that.nodata = false;
      that.nomore = false;
      app.get('ApiAgent/tjbusiness',{st:st,pagenum: pagenum,date:datetype},function(res){
        that.loading = false;
        var data = res.datalist;
        if (pagenum == 1) {
          if(st == 1){
            that.count = res.count;
            that.yqpath = res.invite_business;
          }
          that.datalist = data;
          if (data.length == 0) {
            that.nodata = true;
            console.log(data);
            console.log('no data');
          }
          that.loaded();
        }else{
          if (data.length == 0) {
            that.nomore = true;
          } else {
            var datalist = that.datalist;
            var newdata = datalist.concat(data);
            that.datalist = newdata;
          }
        }
      })
		},
    changeDateType: function(type) {
      this.datetype = type;
      this.pagenum = 1;
      this.getdata();
    },
    changetab: function (st) {
      this.pagenum = 1;
      this.st = st;
      this.datalist = [];
      uni.pageScrollTo({
        scrollTop: 0,
        duration: 0
      });
      this.getdata();
    },
		openLocation:function(e){
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
  }
};
</script>
<style>
.topfix{width: 100%;background: #f9f9f9;top:var(--window-top);z-index:11;}

.head-total .data-search{padding: 30rpx 0;position: relative;}
.head-total .data-search .selected{background-color: #ffff00 !important;}
.head-total .data-search .data-btn{padding: 5rpx 10rpx;color: #000;background-color: #fe7f25;margin-right: 20rpx;border-radius: 10rpx;}
.head-total .pv-box{text-align: center;margin-top: 20rpx;}
.head-total .pv-box .pv-title{font-size: 30rpx;}
.head-total .pv-box .pv{font-size: 60rpx;padding: 30rpx 0;font-weight: bold;}
.head-total .invite-business .invite-btn{width:250rpx;background-color: #000;padding: 20rpx;border-radius: 10rpx;text-align: center;position: relative;}
.head-total .invite-business::before,.head-total .invite-business::after {content: "";width: 150rpx;height: 2rpx;background-color: currentColor;}
.head-total .tongji{height: 160rpx;color: #ffff00;justify-content: space-evenly;text-align: center;}
.head-total .tongji .price{font-size: 40rpx;font-weight: 700;color: #fff;margin-top: 10rpx;}

.ind_business {width: 100%;margin-top: 20rpx;font-size:26rpx;padding:0 24rpx}
.ind_business .ind_busbox{ width:100%;background: #fff;padding:20rpx;overflow: hidden; margin-bottom:20rpx;border-radius:8rpx;position:relative}
.ind_business .ind_buspic{ width:180rpx;height:180rpx;}
.ind_business .ind_buspic image{ width: 100%;height:100%;border-radius: 8rpx;object-fit: cover;}
.ind_business .bus_title{ font-size: 30rpx; color: #222;font-weight:bold;line-height:46rpx;white-space: nowrap;max-width: 380rpx;overflow: hidden;text-overflow: ellipsis;}
.ind_business .bus_score{font-size: 24rpx;color:#FC5648;display:flex;align-items:center;justify-content: space-between;}
.ind_business .bus_score .img{width:24rpx;height:24rpx;margin-right:10rpx}
.ind_business .bus_score .txt{margin-left:20rpx}
.ind_business .bus_address{color:#999;font-size: 22rpx;height:36rpx;line-height: 36rpx;margin-top:6rpx;display:flex;align-items:center;-webkit-box-orient:vertical;-webkit-line-clamp:1;overflow:hidden;}
.ind_business .bus_address .x1{text-overflow: ellipsis;white-space: nowrap;width: 60%;overflow: hidden;flex: 1;}
.ind_business .bus_address .x2{padding-left:20rpx}
.ind_business .activecoin{background: #ffdbdd;color: #fd2936;border-radius: 10rpx;margin-top: 20rpx;}
.ind_business .activecoin .rl-title{background: #fd2936;color:#fff;border-radius: 10rpx;width: 150rpx;text-align: center;}
.ind_business .activecoin .rl-number{margin-left: 20rpx;font-weight: bold;}
.ind_business .rl-ratio{width: 40%;margin-right: 3%;}
.ind_business .rl-total{flex: 1;}
.header-back-but{position: absolute;left: 40rpx}
.header-back-but image{width: 40rpx;height: 45rpx;}
</style>