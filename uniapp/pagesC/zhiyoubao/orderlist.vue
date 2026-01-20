<template>
<view class="container">
	<block v-if="isload">
		<dd-tab :itemdata="['全部','待付款','待发码','待核销','已核销','退款/售后']" :itemst="['all','0','1','2','3','5']" :st="st" :isfixed="true" @changetab="changetab"></dd-tab>
		<view style="width:100%;height:100rpx"></view>
		<view class="order-content">
			<block v-for="(item, index) in datalist" :key="index">
				<view class="order-box" @tap="goto" :data-url="'orderdetail?id=' + item.id">
					<view class="head flex" style="align-items: center;">
						<view>订单号：{{item.ordernum}}</view>
						<view v-if="item.status==0" class="st0 flex-y-center">待付款</view>
						<view v-if="item.status==1" class="st1 flex-y-center">待发码</view>
						<view v-if="item.status==2" class="st2 flex-y-center">待核销</view>
            <view v-if="item.status==3" class="st3 flex-y-center">已核销</view>
						<view v-if="item.status==4" class="st4 flex-y-center">已关闭</view>
					</view>
          <view class="content" style="border-bottom:none">
            <view class="detail">
              
              <view  style="display: flex;margin-bottom: 20rpx;">
                <view v-if="item.pic" class="content-pic">
                  <image :src="item.pic" mode="widthFix" style="width: 100%;border-radius: 10rpx 10rpx;"></image>
                </view>
                <view style="margin-left: 10rpx;">
                  <view class="title">{{item.title}}</view>
                  <view class="title2">日期：{{item.performDate}}</view>
                  <view class="title2">时间：{{item.performTime}}</view>
                </view>
              </view>

              <view style="background-color: #fff;border-bottom: 8rpx;">
                <view style="border-top:2rpx solid #f1f1f1 ;padding: 20rpx;">
                  <view style="color: #191919;font-weight: bold;">{{item.theaterPlaceName}}</view>
                  <view v-if="item.beforeCheckTime && item.beforeCheckTime>0"style="color: #191919;">提前检票时间：{{item.beforeCheckTime}}分钟</view>
                  <view v-if="item.beforeTime && item.beforeTime>0"style="color: #191919;">提前演出入场时间：{{item.beforeTime}}分钟</view>
                  <view v-if="item.timeLong && item.timeLong>0"style="color: #191919;">演出时长：{{item.timeLong}}分钟</view>
                  <view v-if="item.address" style="color: #999;font-size: 26rpx;">地址：{{item.address}}</view>
                </view>
              </view>

            </view>
          </view>

          <block v-for="(item2, index2) in item.ordergoods" :key="index2">
            <view class="content" >
              <view style="color: #191919;font-weight: bold;line-height: 50rpx;">{{item2.areaName}}</view>
              <block v-for="(item3, index3) in item2.certs" :key="index3">
                <view class="detail">
                  <text class="t1">{{item3.fname}}</text>
                  <view v-if="item3.realName || item3.certNo" class="t2">{{item3.realName}} {{item3.certNo}}</view>
                  <view v-if="item.checkCodeType == 2 && item3.status>=1 && item3.status<=4" class="status-view flex flex-y-center">
                    <view v-if="item3.status==1" style="color: #ff8758;">待发码</view>
                    <view v-if="item3.status==2" style="color: green;">待核销</view>
                    <view v-if="item3.status==3" style="color: red;">已核销</view>
                    <view v-if="item3.status==4"  style="color: #bbb;">已驳回</view>
                  </view>

                  <view v-if="item3.refund_status!=0" class="status-view flex flex-y-center" style="justify-content: space-between;">
                    <view v-if="item3.refund_status!=0" style="display: flex;margin-right: 20rpx;">
                      <view style="color: #8D8D8D;font-weight: bold;">退款状态：</view>
                      <text  v-if="item3.refund_status==1" style="color: #ff8758;">审核中</text>
                      <text  v-if="item3.refund_status==2" style="color: green;">已审核</text>
                      <text  v-if="item3.refund_status==3" style="color: red;">已退款</text>
                      <text  v-if="item3.refund_status==4"  style="color: #bbb;">已驳回</text>
                    </view>
                  </view>

                  <block v-if="item3.status==2 && item3.hexiao_qr">
                    <view class="btn2" @tap.stop="showhxqr" :data-hexiao_qr="item3.hexiao_qr" style="position:absolute;top:40rpx;right:10rpx;">核销码</view>
                  </block>
                </view>
              </block>
            </view>
          </block>
          
          <view v-if="item.othermsg" style="line-height: 40rpx;padding: 10rpx 0;">{{item.othermsg}}</view>
					<view class="bottom flex" style="flex-wrap: wrap;">
						<view class="flex-y-center" > 
							<view class="title-text">实付：</view>
              <view class="price-text flex flex-y-center">
								<text style="font-size: 24rpx;">￥</text>{{item.totalprice}}
							</view>
              <view v-if="item.refund_money>0" style="color: red;font-size: 26rpx;">
              	(<text>已退款:</text><text>￥{{item.refund_money}}</text>)
              </view>
						</view>
            <view v-if="item.refund_money>0 && item.refund_reason" style="color: red;font-size: 26rpx;width: 100%;text-align: right;">
              	<text>退款原因：{{item.refund_reason}}</text>
            </view>
					</view>
          
					<view class="op">
						<block v-if="item.status==2 && item.hexiao_qr">
							<view class="btn2" @tap.stop="showhxqr" :data-hexiao_qr="item.hexiao_qr">核销码</view>
						</block>
						<block v-if="item.status==0">
							<view class="btn2" @tap.stop="toclose" :data-id="item.id">
								<text >关闭订单</text>
							</view>
							<view v-if="item.canpay"  class="btn1" :style="{background:t('color1')}" @tap.stop="goto" :data-url="'/pagesExt/pay/pay?id=' + item.payorderid">去付款</view>
						</block>
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
    
    <uni-popup id="dialogHxqr" ref="dialogHxqr" type="dialog">
    	<view class="hxqrbox">
    		<image :src="hexiao_qr" @tap="previewImage" :data-url="hexiao_qr" class="img"/>
    		<view class="txt">请出示核销码给核销员进行核销</view>
    		<view class="close" @tap="closeHxqr">
    			<image :src="pre_url+'/static/img/close2.png'" style="width:100%;height:100%"/>
    		</view>
    	</view>
    </uni-popup>
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
      
      hexiao_qr:'',

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
      app.post('ApiZhiyoubao/orderlist', {st: st,pagenum: pagenum,keyword:that.keyword,orderid:that.orderid}, function (res) {
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
    	var msg = '确定要关闭该订单吗？';
      app.confirm(msg, function () {
    		app.showLoading('提交中');
        app.post('ApiZhiyoubao/closeOrder', {orderid: orderid}, function (data) {
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
        app.post('ApiZhiyoubao/delOrder', {orderid: orderid}, function (data) {
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
        app.post('ApiZhiyoubao/orderCollect', {orderid: orderid}, function (data) {
          app.showLoading(false);
          app.success(data.msg);
          setTimeout(function () {
            that.getdata();
          }, 1000);
        });
      });
    },
    showhxqr:function(e){
    	this.hexiao_qr = e.currentTarget.dataset.hexiao_qr
    	this.$refs.dialogHxqr.open();
    },
    closeHxqr:function(){
    	this.$refs.dialogHxqr.close();
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

.order-box .content{width: 100%;position:relative;}
.order-box .content:last-child{ border-bottom: 0; }
.order-box .content image{ width: 140rpx; height: 140rpx;}
.order-box .content .detail{display:flex;flex-direction:column;flex:1;background-color: #f4f6fa;padding: 20rpx;position: relative;}
.order-box .content .detail .t1{font-size:30rpx; font-weight:blod ;margin:10rpx 0rpx;color: #000;}
.order-box .content .detail .t1-title{color: #818181;}
/* {display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:2;overflow:hidden;} */

.order-box .bottom{ width:100%; padding: 15rpx 0px; border-top: 1px #f4f4f4 solid; color: #333;justify-content: flex-end;}
.order-box .bottom .title-text{font-size: 24rpx;color: #333;}
.order-box .bottom .price-text{font-size: 38rpx;}
.order-box .op{ display:flex;flex-wrap: wrap;justify-content:flex-end;align-items:center;width:100%; padding: 10rpx 0px;color: #555;}

.btn1{margin-left:20rpx; margin-top: 10rpx;width:160rpx;height:60rpx;line-height:60rpx;color:#fff;border-radius:30px;text-align:center;padding: 0 20rpx;}
.btn2{margin-left:20rpx; margin-top: 10rpx;width:160rpx;height:60rpx;line-height:60rpx;color:#000;background:#fff;border:1px solid #cdcdcd;border-radius:30px;text-align:center;padding: 0 20rpx;}

.hxqrbox{background:#fff;padding:50rpx;position:relative;border-radius:20rpx}
.hxqrbox .img{width:400rpx;height:400rpx}
.hxqrbox .txt{color:#666;margin-top:20rpx;font-size:26rpx;text-align:center}
.hxqrbox .close{width:50rpx;height:50rpx;position:absolute;bottom:-100rpx;left:50%;margin-left:-25rpx;border:1px solid rgba(255,255,255,0.5);border-radius:50%;padding:8rpx}

.content{background-color: #fff;border-radius: 8rpx;padding: 10rpx 0;}
.content-pic{width: 200rpx;max-height: 400rpx;border-radius: 4rpx;overflow: hidden;}
.content-title{font-weight: bold;line-height: 50rpx;max-height: 100rpx;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:2;overflow:hidden;word-break: break-all;}
.content-title2{color: #666;line-height: 40rpx;margin-top: 5rpx;}
</style>