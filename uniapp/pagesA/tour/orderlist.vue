<template>
<view class="container">
	<block v-if="isload">
		<dd-tab :itemdata="['全部','待付款','待发货','待收货','已完成','退款']" :itemst="['all','0','1','2','3','10']" :st="st" :isfixed="true" @changetab="changetab"></dd-tab>
		<view style="width:100%;height:100rpx"></view>
		<!-- #ifndef H5 || APP-PLUS -->
		<view class="topsearch flex-y-center">
			<view class="f1 flex-y-center">
				<image class="img" :src="pre_url+'/static/img/search_ico.png'"></image>
				<input :value="keyword" placeholder="输入关键字搜索" placeholder-style="font-size:24rpx;color:#C2C2C2" @confirm="searchConfirm"></input>
			</view>
		</view>
		<!--  #endif -->
		<view class="order-content">
			<block v-for="(item, index) in datalist" :key="index">
			<view class="order-box" @tap.stop="goto" :data-url="'orderdetail?id=' + item.id">
				<view class="head">
					<text class="flex1">订单号：{{item.ordernum}}</text>
					<text v-if="item.status==0" class="st0">待付款</text>
					<block v-if="item.status==1">
							<text v-if="item.freight_type!=1" class="st1">待发货</text>
							<text v-if="item.freight_type==1" class="st1">待提货</text>
					</block>
					<text v-if="item.status==2" class="st2">待收货</text>
					<text v-if="item.status==3" class="st3">已完成</text>
					<text v-if="item.status==4" class="st4">已关闭</text>
				</view>

				<view class="content" style="border-bottom:none">
					<view @tap.stop="goto" :data-url="'product?id=' + item.proid">
						<image :src="item.propic"></image>
					</view>
					<view class="detail">
						<text class="t1">{{item.proname}}</text>
						<view class="t3"><text class="x1 flex1">￥{{item.sell_price}}</text><text class="x2">×{{item.num}}</text></view>
					</view>
				</view>
				<view class="bottom">
					<text>共计{{item.num}}件商品 实付:￥{{item.totalprice}}</text>
					<text v-if="item.refund_status==1" style="color:red"> 退款中￥{{item.refund_money}}</text>
					<text v-if="item.refund_status==2" style="color:red"> 已退款￥{{item.refund_money}}</text>
					<text v-if="item.refund_status==3" style="color:red"> 退款申请已驳回</text>
				</view>
				<view class="op">
					<view @tap.stop="goto" :data-url="'orderdetail?id=' + item.id" class="btn2">详情</view>
					<block v-if="item.status==0">
						<view class="btn2" @tap.stop="toclose" :data-id="item.id">关闭订单</view>
						<view class="btn1" :style="{background:t('color1')}" @tap.stop="goto" :data-url="'/pagesExt/pay/pay?id=' + item.payorderid">去付款</view>
					</block>
					<block v-if="item.status==1">
						<view v-if="item.can_refund && (item.refund_status==0 || item.refund_status==3)" class="btn2" @tap.stop="goto" :data-url="'refund?orderid=' + item.id + '&price=' + item.totalprice">申请退款</view>
					</block>
          
          <view class="btn2" v-if="item.can_lookupimg" @tap.stop="goto" :data-url="'lookupimg?orderid=' + item.id">上传图片</view>
          
          <block v-if="item.status>=1 && item.status<=3 && item.can_lookalbum">
            <view class="btn2" @tap.stop="goto" :data-url="'lookalbum?orderid=' + item.id">查看相册</view>
          </block>
          
					<block v-if="item.status==2">
						<view class="btn2" @tap.stop="goto" :data-url="'/pagesExt/order/logistics?express_com=' + item.express_com + '&express_no=' + item.express_no" v-if="item.freight_type!=3 && item.freight_type!=4">查看物流</view>
						<view class="btn1" :style="{background:t('color1')}" @tap.stop="orderCollect" :data-id="item.id">确认收货</view>
					</block>

					<block v-if="item.status==4">
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
var app = getApp();

export default {
  data() {
    return {
			opt:{},
			loading:false,
      isload: false,
			menuindex:-1,

      st: 'all',
      datalist: [],
      pagenum: 1,
      nomore: false,
      nodata: false,
			keyword:'',
			pre_url:app.globalData.pre_url,
    };
  },
  onLoad: function (opt) {
		this.opt = app.getopts(opt);
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
    changetab: function (st) {
      this.st = st;
      uni.pageScrollTo({
        scrollTop: 0,
        duration: 0
      });
      this.getdata();
    },
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
      app.post('ApiTour/orderlist', {st: st,pagenum: pagenum,keyword:that.keyword}, function (res) {
				that.loading = false;
        var data = res.datalist;
        if (pagenum == 1) {
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
    toclose: function (e) {
      var that = this;
      var orderid = e.currentTarget.dataset.id;
      app.confirm('确定要关闭该订单吗?', function () {
				app.showLoading('提交中');
        app.post('ApiTour/closeOrder', {orderid: orderid}, function (data) {
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
        app.post('ApiTour/delOrder', {orderid: orderid}, function (data) {
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
      app.confirm('确定要收货吗?', function () {
				app.showLoading('提交中');
        app.post('ApiTour/orderCollect', {orderid: orderid}, function (data) {
					app.showLoading(false);
          app.success(data.msg);
          setTimeout(function () {
            that.getdata();
          }, 1000);
        });
      });
    },
		searchConfirm:function(e){
			this.keyword = e.detail.value;
      this.getdata(false);
		}
  }
};
</script>
<style>
.container{ width:100%;}
.topsearch{width:94%;margin:10rpx 3%;}
.topsearch .f1{height:60rpx;border-radius:30rpx;border:0;background-color:#fff;flex:1}
.topsearch .f1 .img{width:24rpx;height:24rpx;margin-left:10px}
.topsearch .f1 input{height:100%;flex:1;padding:0 20rpx;font-size:28rpx;color:#333;}
.order-content{display:flex;flex-direction:column}
.order-box{ width: 94%;margin:10rpx 3%;padding:6rpx 3%; background: #fff;border-radius:8px}
.order-box .head{ display:flex;width:100%; border-bottom: 1px #f4f4f4 solid; height: 70rpx; line-height: 70rpx; overflow: hidden; color: #999;}
.order-box .head .f1{display:flex;align-items:center;color:#333}
.order-box .head .f1 image{width:34rpx;height:34rpx;margin-right:4px}
.order-box .head .st0{ width: 140rpx; color: #ff8758; text-align: right; }
.order-box .head .st1{ width: 204rpx; color: #ffc702; text-align: right; }
.order-box .head .st2{ width: 204rpx; color: #ff4246; text-align: right; }
.order-box .head .st3{ width: 140rpx; color: #999; text-align: right; }
.order-box .head .st4{ width: 140rpx; color: #bbb; text-align: right; }

.order-box .content{display:flex;width: 100%; padding:16rpx 0px;border-bottom: 1px #f4f4f4 dashed;position:relative}
.order-box .content:last-child{ border-bottom: 0; }
.order-box .content image{ width: 140rpx; height: 140rpx;}
.order-box .content .detail{display:flex;flex-direction:column;margin-left:14rpx;flex:1}
.order-box .content .detail .t1{font-size:26rpx;line-height:36rpx;margin-bottom:10rpx;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:2;overflow:hidden;}
.order-box .content .detail .t2{height: 46rpx;line-height: 46rpx;color: #999;overflow: hidden;font-size: 26rpx;}
.order-box .content .detail .t3{display:flex;height:40rpx;line-height:40rpx;color: #ff4246;}
.order-box .content .detail .x1{ flex:1}
.order-box .content .detail .x2{ width:100rpx;font-size:32rpx;text-align:right;margin-right:8rpx}

.order-box .bottom{ width:100%; padding: 10rpx 0px; border-top: 1px #f4f4f4 solid; color: #555;}
.order-box .op{ display:flex;flex-wrap: wrap;align-items:center;width:100%; padding: 10rpx 0px; border-top: 1px #f4f4f4 solid; color: #555;}

.btn1{margin-left:20rpx;margin-top: 10rpx;width:160rpx;height:60rpx;line-height:60rpx;color:#fff;border-radius:3px;text-align:center}
.btn2{margin-left:20rpx;margin-top: 10rpx;width:160rpx;height:60rpx;line-height:60rpx;color:#333;background:#fff;border:1px solid #cdcdcd;border-radius:3px;text-align:center}

</style>