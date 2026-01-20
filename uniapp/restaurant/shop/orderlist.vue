<template>
<view class="container">
	<block v-if="isload">
		<dd-tab :itemdata="['全部','待付款','已付款','已完成','退款']" :itemst="['all','0','1','3','10']" :st="st" :isfixed="true" @changetab="changetab"></dd-tab>
		<view class="order-content">
			<block v-for="(item, index) in datalist" :key="index">
				<view class="order-box" @tap="goto" :data-url="'orderdetail?id=' + item.id">
					<view class="head">
						<view class="f1" v-if="item.bid!=0 && item.binfo" @tap.stop="goto" :data-url="'/restaurant/shop/index?bid=' + item.bid"><image :src="pre_url+'/static/img/ico-shop.png'"></image> {{item.binfo.name}}</view>
						<view v-else>订单号：{{item.ordernum}}</view>
						<view class="flex1"></view>
						<text v-if="item.status==0" class="st0">待付款</text>
						<text v-if="item.status==1 && item.paytypeid != 4" class="st1">已付款</text>
						<text v-if="item.status==1 && item.paytypeid == 4" class="st1">线下付款</text>
						<text v-if="item.status==12" class="st1">已接单</text>
						<text v-if="item.status==2" class="st2">配送中</text>
						<text v-if="item.status==3" class="st3">已完成</text>
						<text v-if="item.status==4" class="st4">已关闭</text>
					</view>

					<block v-for="(item2, idx) in item.prolist" :key="idx">
						<view class="content" :style="idx+1==item.procount?'border-bottom:none':''">
							<view style="width: 140rpx;height140rpx;overflow:hidden;">
								<image :src="item2.pic"></image>
							</view>
							<view class="detail">
								<text class="t1">{{item2.name}}</text>
								
								<view v-if="(item2.ggtext && item2.ggtext.length)" class="flex-col">
									<block v-for="(item3,index) in item2.ggtext">
										<text class="t2">{{item3}}</text>
									</block>
								</view>
								<text class="t2" v-if="item2.ggname">{{item2.ggname}}{{item2.jltitle?item2.jltitle:''}}</text>
								
								<view class="t3">
									<text class="x1 flex1" v-if="item2.jlprice">￥{{parseFloat(parseFloat(item2.sell_price)+parseFloat(item2.jlprice)).toFixed(2)}}<text v-if="item2.product_type && item2.product_type==1" style="font-size: 20rpx;">/斤</text></text>
									<text class="x1 flex1" v-else>￥{{item2.sell_price}}<text v-if="item2.product_type && item2.product_type==1" style="font-size: 20rpx;">/斤</text></text> 
									<text class="x2">×{{item2.num}}<text v-if="item2.product_type && item2.product_type==1" style="font-size: 20rpx;">斤</text></text>
								</view>
							</view>
						</view>
					</block>
					<view class="bottom">
						<text>共计{{item.procount}}件商品 实付:￥{{item.totalprice}}</text>
						<text v-if="item.refund_status==1" style="color:red"> 退款中￥{{item.refund_money}}</text>
						<text v-if="item.refund_status==2" style="color:red"> 已退款￥{{item.refund_money}}</text>
						<text v-if="item.refund_status==3" style="color:red"> 退款申请已驳回</text>
					</view>
					<view class="op">
						<view @tap.stop="goto" :data-url="'orderdetail?id=' + item.id" class="btn2">详情</view>
						<block v-if="item.status==0">
							<!-- <view class="btn2" @tap.stop="toclose" :data-id="item.id">关闭</view> -->
							<view class="btn1" :style="{background:t('color1')}" @tap.stop="goto" :data-url="'index?tableId='+item.tableid+'&bid='+item.bid" v-if="item.is_bar_table_order ==0 || item.eattype ==1">加菜</view>
							<view class="btn1" v-if="item.eattype ==1" :style="{background:t('color1')}" @tap.stop="toBuy(item)" >去结算</view>
							<view class="btn1" v-else :style="{background:t('color1')}" @tap.stop="toPay" :data-id="item.payorderid" :data-istopay="item.is_topay">去付款</view>
						</block>
						<block v-if="item.status==12 || item.status==2">
							<view v-if="item.refund_status==0 || item.refund_status==3" class="btn2" @tap.stop="goto" :data-url="'refund?orderid=' + item.id + '&price=' + item.totalprice">申请退款</view>
						</block>
						<block v-if="item.status==2">
							<view class="btn1" :style="{background:t('color1')}" @tap.stop="orderCollect" :data-id="item.id" v-if="item.paytypeid!='4'">确认收货</view>
						</block>
					<!-- 	<block v-if="item.status==3 || item.status==4">
							<view class="btn2" @tap.stop="todel" :data-id="item.id">删除订单</view>
						</block> -->
						<block v-if="item.status==3">
							<view v-if="item.iscommentdp==0 " class="btn1" :style="{background:t('color1')}" @tap.stop="goto" :data-url="'commentdp?orderid=' + item.id">评价店铺</view>
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
			nodata:false,
      codtxt: "",
	  pre_url:app.globalData.pre_url,	
    };
  },

  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		if(this.opt && this.opt.st){
			this.st = this.opt.st;
		}
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
      app.post('ApiRestaurantShop/orderlist', {st: st,pagenum: pagenum}, function (res) {
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
      app.confirm('确定要关闭该订单吗?', function () {
				app.showLoading('提交中');
        app.post('ApiRestaurantShop/closeOrder', {orderid: orderid}, function (data) {
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
        app.post('ApiRestaurantShop/delOrder', {orderid: orderid}, function (data) {
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
        app.post('ApiRestaurantShop/orderCollect', {orderid: orderid}, function (data) {
          app.success(data.msg);
          setTimeout(function () {
            that.getdata();
          }, 1000);
        });
      });
    },
	toPay:function(e){
		var payorderid = e.currentTarget.dataset.id;
		var istopay = e.currentTarget.dataset.istopay;
		if(istopay ==1){
			var url ='/pagesExt/pay/pay?id='+payorderid
			app.goto(url);
		}else{
			app.alert('请呼叫服务员结账');
			return;
		}
	},
	toBuy(e){ 
		var orderid = e.id;
		var tableid = e.tableid;
		var bid = e.bid;
		var renshu = e.renshu;
		app.goto('buy?orderid='+orderid+'&tableId='+tableid+'&bid='+bid+'&renshu='+renshu);
	}
  }
};
</script>
<style>
.container{ width:100%;margin-top:90rpx}
.order-content{display:flex;flex-direction:column}
.order-box{ width: 94%;margin:10rpx 3%;padding:6rpx 3%; background: #fff;border-radius:8px}
.order-box .head{ display:flex;width:100%; border-bottom: 1px #f4f4f4 solid; height: 70rpx; line-height: 70rpx; overflow: hidden; color: #999;}
.order-box .head .f1{display:flex;align-items:center;color:#333}
.order-box .head .f1 image{width:34rpx;height:34rpx;margin-right:4px}
.order-box .head .st0{ width: 140rpx; color: #ff8758; text-align: right; }
.order-box .head .st1{ width: 140rpx; color: #ffc702; text-align: right; }
.order-box .head .st2{ width: 140rpx; color: #ff4246; text-align: right; }
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
.order-box .op{ display:flex;justify-content:flex-end;align-items:center;width:100%; padding: 10rpx 0px; border-top: 1px #f4f4f4 solid; color: #555;}

.btn1{margin-left:20rpx;width:160rpx;height:60rpx;line-height:60rpx;color:#fff;border-radius:3px;text-align:center}
.btn2{margin-left:20rpx;width:140rpx;height:60rpx;line-height:60rpx;color:#333;background:#fff;border:1px solid #cdcdcd;border-radius:3px;text-align:center}

</style>