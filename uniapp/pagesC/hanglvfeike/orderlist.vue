<template>
<view class="container">
	<block v-if="isload">
		<dd-tab :itemdata="['全部','待付款','待出票','已出票','退款/售后']" :itemst="['all','0','1','2','5']" :st="st" :isfixed="true" @changetab="changetab"></dd-tab>
		<view style="width:100%;height:100rpx"></view>
		<view class="order-content">
			<block v-for="(item, index) in datalist" :key="index">
				<view class="order-box" @tap="goto" :data-url="'orderdetail?id=' + item.id">
					<view class="head flex">
						<view class="flight-name flex flex-y-center">
							<view class="flight-icon"><image :src="pre_url+'/static/img/planeticket/hangban.png'"></image></view>
							<view>{{item.airlineName}}{{item.flightNo}}</view>
						</view>
            <!-- <view class="f1">订单号：{{item.ordernum}} </view> -->
						<view v-if="item.status==0" class="st0 flex-y-center">待付款</view>
						<view v-if="item.status==1" class="st1 flex-y-center">待出票</view>
						<view v-if="item.status==2" class="st2 flex-y-center">已出票</view>
            <view v-if="item.status==3" class="st2 flex-y-center">已完成</view>
						<view v-if="item.status==4" class="st4 flex-y-center">已关闭</view>
					</view>
          <view class="content" style="border-bottom:none">
            <view class="detail">
              <view class="t1">{{item.fromCityname}} - {{item.toCityname}}</view>
              <view class="t1">{{item.fromDate}}({{item.week}}) <text v-if="item.flightdata" style="margin-left: 10rpx;">{{item.flightdata.departTime}} - {{item.flightdata.arriveTime}}</text></view>

							<view class="t1 flex flex-y-center">
								<view class="t1-title">订单号：</view>
								<view>{{item.ordernum}}</view>
							</view>
              <block v-if="!item.ischange">
                <view class="t1"><text class="t1-title">机票类型：</text>普通机票</view>
              </block>
              <block v-else>
                <view class="t1"><text class="t1-title">机票类型：</text><text style="color: #ff8758;">改签机票</text></view>
              </block>
              <view class="t1 flex flex-y-center" v-if="item.change_status!=0">
                <text class="t1-title">改签状态：</text>
                <text v-if="item.change_status==1"  style="color: #ff8758;">待审核</text>
                <text v-if="item.change_status==2"  style="color: green;">已审核</text>
                <text v-if="item.change_status==3"  style="color: #999;">已完成</text>
                <text v-if="item.change_status==-1"  style="color: #bbb;">已取消</text>
                <text v-if="item.change_status==-2"  style="color: #bbb;">已驳回</text>
              </view>
            </view>
          </view>

					<view class="bottom flex">
						<view class="flex-y-center"> 
							<view class="title-text">实付：</view>
              <view v-if="item.ischange && item.status == 0 && item.change_status==1 ">审核中</view>
              <view class="price-text flex flex-y-center" v-else>
								<text style="font-size: 24rpx;">￥</text>{{item.totalprice}}
							</view>
              <view v-if="item.refund_money>0" style="color: red;font-size: 26rpx;">
              	(<text>已退款:</text><text>￥{{item.refund_money}}</text>)
              </view>
						</view>
					</view>
          
					<view class="op">
						<block v-if="([1,2,3]).includes(item.status) && item.invoice">
							<view class="btn2" @tap.stop="goto" :data-url="'/pagesExt/order/invoice?type=hanglvfeike&orderid=' + item.id">发票</view>
						</block>
						<block v-if="item.status==0">
							<view class="btn2" @tap.stop="toclose" :data-id="item.id" :data-ischange="item.ischange">
								<text v-if="item.ischange">取消改签</text>
								<text v-else>关闭订单</text>
							</view>
							<view v-if="item.canpay"  class="btn1" :style="{background:t('color1')}" @tap.stop="goto" :data-url="'/pagesExt/pay/pay?id=' + item.payorderid">去付款</view>
						</block>
            <view v-if="item.canchange" class="btn2" @tap.stop="goto" :data-url="'change?orderid=' + item.id">改签</view>
						<view v-if="item.canrefund" class="btn2" @tap.stop="goto" :data-url="'refund?orderid=' + item.id">退款</view>
            <view v-if="item.refundcount>0" class="btn2" @tap.stop="goto" :data-url="'refundlist?orderid='+ item.id">售后详情</view>
						<block v-if="item.candel">
							<view class="btn2" @tap.stop="todel" :data-id="item.id">删除订单</view>
						</block>
					</view>
				</view>
			</block>
		</view>
		<nomore v-if="nomore"></nomore>
		<nodata v-if="nodata"></nodata>
	</block>
	<loading v-if="loading"></loading>
	<dp-tabbar :opt="opt"></dp-tabbar>
	<popmsg ref="popmsg"></popmsg>
</view>
</template>

<script>
// import { object } from 'prop-types';
var app = getApp();

export default {
  data() {
    return {
			opt:{},
			loading:false,
      isload: false,
			menuindex:-1,
			pre_url: app.globalData.pre_url,
      
      orderid:0,
      st: 'all',
      datalist: [],
      pagenum: 1,
      nomore: false,
			nodata:false,
			keyword:'',
      canrefund:false,

    };
  },

  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		if(this.opt && this.opt.st){
			this.st = this.opt.st;
		}
    this.orderid = this.opt.orderid || 0;
		this.getdata();
  },
	onPullDownRefresh: function () {
		this.getdata();
	},
  onReachBottom: function () {
    if (!this.nodata && !this.nomore) {
      this.pagenum = this.pagenum + 1;
      this.getdata(true);
    }
  },
	onNavigationBarSearchInputConfirmed:function(e){
		this.searchConfirm({detail:{value:e.text}});
	},
  methods: {
    getdata: function (loadmore) {
			if(!loadmore){
				this.pagenum = 1;
				this.datalist = [];
			}
      var that = this;
      var pagenum = that.pagenum;
      var st = that.st;
			that.nodata = false;
			that.nomore = false;
			that.loading = true;
      app.post('ApiHanglvfeike/orderlist', {st: st,pagenum: pagenum,keyword:that.keyword,orderid:that.orderid}, function (res) {
				that.loading = false;
        var data = res.datalist;
        if (pagenum == 1) {
					that.canrefund = res.canrefund;
					that.datalist = data;
          if (data.length == 0) {
            that.nodata = true;
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
      });
    },
    changetab: function (st) {
      this.st = st;
      uni.pageScrollTo({
        scrollTop: 0,
        duration: 0
      });
      this.getdata();
    },
    toclose: function (e) {
      var that = this;
      var orderid = e.currentTarget.dataset.id;
    	var ischange =  e.currentTarget.dataset.ischange;
    	if(ischange){
    		var msg = '确定要取消改签吗？';
    	}else{
    		var msg = '确定要关闭该订单吗？';
    	}
      app.confirm(msg, function () {
    		app.showLoading('提交中');
        app.post('ApiHanglvfeike/closeOrder', {orderid: orderid}, function (data) {
    			app.showLoading(false);
          app.success(data.msg);
          setTimeout(function () {
            that.getdata();
          }, 1000);
        });
      });
    },
    todel: function (e) {
      var that = this;
      var orderid = e.currentTarget.dataset.id;
      app.confirm('确定要删除该订单吗?', function () {
				app.showLoading('删除中');
        app.post('ApiHanglvfeike/delOrder', {orderid: orderid}, function (data) {
					app.showLoading(false);
          app.success(data.msg);
          setTimeout(function () {
            that.getdata();
          }, 1000);
        });
      });
    },
    orderCollect: function (e) {
      var that = this;
      var orderid = e.currentTarget.dataset.id;
			var index = e.currentTarget.dataset.index;
			var orderinfo = that.datalist[index];
      app.confirm('确定要收货吗?', function () {
				app.showLoading('提交中');
        app.post('ApiHanglvfeike/orderCollect', {orderid: orderid}, function (data) {
          app.showLoading(false);
          app.success(data.msg);
          setTimeout(function () {
            that.getdata();
          }, 1000);
        });
      });
    },
  }
};
</script>
<style>
.container{ width:100%;}
.order-content{display:flex;flex-direction:column}
.order-box{ width: 94%;margin:10rpx auto;padding:6rpx 20rpx; background: #fff;border-radius:24rpx}
.order-box .head{ width:100%; border-bottom: 1px #f4f4f4 solid; height: 80rpx; overflow: hidden; justify-content:space-between}
.order-box .head image{width:34rpx;height:34rpx;margin-right:4px}
.order-box .head .st0{ color: #ff8758;font-size: 30rpx;}
.order-box .head .st1{ color: #ffc702;font-size: 30rpx;}
.order-box .head .st2{ color: #ff4246;font-size: 30rpx;}
.order-box .head .st3{ color: #999;font-size: 30rpx;}
.order-box .head .st4{ color: #bbb;font-size: 30rpx;}
.order-box .head .st8{ color: #ff55ff;font-size: 30rpx;}
.order-box .head .flight-name{font-size: 34rpx;font-weight: bold;color: #333;}
.order-box .head .flight-icon{width: 45rpx;height: 45rpx;margin-right: 20rpx;}
.order-box .head .flight-icon image{width: 100%;height: 100%;}

.order-box .content{display:flex;width: 100%; padding:16rpx 0px;border-bottom: 1px #f4f4f4 dashed;position:relative;align-items: center;}
.order-box .content:last-child{ border-bottom: 0; }
.order-box .content image{ width: 140rpx; height: 140rpx;}
.order-box .content .detail{display:flex;flex-direction:column;margin-left:14rpx;flex:1}
.order-box .content .detail .t1{font-size:30rpx; font-weight:blod ;margin:10rpx 0rpx;color: #000;}
.order-box .content .detail .t1-title{color: #818181;}
/* {display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:2;overflow:hidden;} */

.order-box .bottom{ width:100%; padding: 15rpx 0px; border-top: 1px #f4f4f4 solid; color: #333;justify-content: flex-end;}
.order-box .bottom .title-text{font-size: 24rpx;color: #333;}
.order-box .bottom .price-text{font-size: 38rpx;}
.order-box .op{ display:flex;flex-wrap: wrap;justify-content:flex-end;align-items:center;width:100%; padding: 10rpx 0px;color: #555;}

.btn1{margin-left:20rpx; margin-top: 10rpx;width:160rpx;height:60rpx;line-height:60rpx;color:#fff;border-radius:30px;text-align:center;padding: 0 20rpx;}
.btn2{margin-left:20rpx; margin-top: 10rpx;width:160rpx;height:60rpx;line-height:60rpx;color:#000;background:#fff;border:1px solid #cdcdcd;border-radius:30px;text-align:center;padding: 0 20rpx;}

</style>