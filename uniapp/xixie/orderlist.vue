<template>
<view class="container">
	<block v-if="isload">
		<dd-tab :itemdata="['全部','待付款','待取货','入库中','清洗中','送货中','已完成']" :itemst="['all','0','1','2','3','4','5']" :st="st" :isfixed="true" @changetab="changetab"></dd-tab>
		<view style="width:100%;height:100rpx"></view>
		<view class="topsearch flex-y-center">
			<view class="f1 flex-y-center">
				<image class="img" src="/static/img/search_ico.png"></image>
				<input :value="keyword" placeholder="输入关键字搜索" placeholder-style="font-size:24rpx;color:#C2C2C2" @confirm="searchConfirm"></input>
			</view>
		</view>
		<view class="order-content">
			<block v-for="(item, index) in datalist" :key="index">
			<view class="order-box" @tap.stop="goto" :data-url="'orderdetail?id=' + item.id">
				<view class="head">
					<text class="flex1">订单号：{{item.ordernum}}</text>
                    <text v-if="item.status==-1" class="st-1">已关闭</text>
					<text v-if="item.status==0" class="st0">待付款</text>
                    <text v-if="item.status==1" class="st1">待取货</text>
					<text v-if="item.status==2" class="st2">入库中</text>
					<text v-if="item.status==3" class="st3">清洗中</text>
					<text v-if="item.status==4" class="st4">送货中</text>
                    <text v-if="item.status==5" class="st5">已完成</text>
				</view>

				<block v-for="(item2, idx) in item.prolist" :key="idx">
					<view class="content" :style="idx+1==item.procount?'border-bottom:none':''">
						<view @tap.stop="goto" >
							<image :src="item2.pic"></image>
						</view>
						<view class="detail">
							<text class="t1">{{item2.name}}</text>
							<view class="t3">
								<text class="x1 flex1">￥{{item2.sell_price}}</text>
								<text class="x2">×{{item2.num}}</text>
							</view>
						</view>
					</view>
				</block>
				<view class="bottom">
					<text>共计{{item.procount}}件商品 实付:￥{{item.totalprice}}  <span v-if="item.balance_price > 0 && item.balance_pay_status == 0"  style="display: block; float: right;">尾款：￥{{item.balance_price}}</span></text>
					<text v-if="item.refund_status==1" style="color:red;padding-left:6rpx">退款中￥{{item.refund_money}}</text>
					<text v-if="item.refund_status==2" style="color:red;padding-left:6rpx">已退款￥{{item.refund_money}}</text>
					<text v-if="item.refund_status==3" style="color:red;padding-left:6rpx">退款申请已驳回</text>
					<block v-if="item.buy_type==1">
						<text style="padding-left:6rpx">上门取件</text>
					</block>
					<block v-if="item.buy_type==2">
						<text style="padding-left:6rpx">送货到店</text>
					</block>
				</view>
				<view class="op">
                    
					<view @tap.stop="goto" :data-url="'orderdetail?id=' + item.id" class="btn2">详情</view>
					<block v-if="item.status==0">
						<view class="btn2" @tap.stop="toclose" :data-id="item.id">关闭订单</view>
						<view class="btn1" :style="{background:t('color1')}" @tap.stop="goto" :data-url="'/pages/pay/pay?id=' + item.payorderid">去付款</view>
					</block>
					<block v-if="item.status==4">
						<view class="btn1" :style="{background:t('color1')}" @tap.stop="orderCollect" :data-id="item.id">确认完成</view>
					</block>
                    <block v-if="item.status>=1 && item.status<=4">
                    	<view v-if="item.refund_status==0 || item.refund_status==3" class="btn2" @tap.stop="goto" :data-url="'refund?orderid=' + item.id + '&price=' + item.totalprice">申请退款</view>
                    </block>
					<block v-if="item.status==-1">
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
        app.post('ApiXixie/orderlist', {st: st,pagenum: pagenum,keyword:that.keyword}, function (res) {
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
            app.post('ApiXixie/closeOrder', {orderid: orderid}, function (data) {
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
        app.post('ApiXixie/delOrder', {orderid: orderid}, function (data) {
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
        app.confirm('确定要完成吗?', function () {
            app.showLoading('提交中');
            app.post('ApiXixie/orderCollect', {orderid: orderid}, function (data) {
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
.order-box .head .st-1{ width: 140rpx; color: #ccc; text-align: right; }
.order-box .head .st0{ width: 140rpx; color: #ff8758; text-align: right; }
.order-box .head .st1{ width: 204rpx; color: #ffc702; text-align: right; }
.order-box .head .st2{ width: 204rpx; color: #ff4246; text-align: right; }
.order-box .head .st3{ width: 140rpx; color: #F3C39D; text-align: right; }
.order-box .head .st4{ width: 140rpx; color: #ECBCCA; text-align: right; }
.order-box .head .st5{ width: 140rpx; color: #bbb; text-align: right; }
.order-box .head .st6{ width: 140rpx; color: #bbb; text-align: right; }

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
.order-box .op{ display:flex;flex-wrap: wrap;justify-content:flex-end;align-items:center;width:100%; padding: 10rpx 0px; border-top: 1px #f4f4f4 solid; color: #555;}

.btn1{margin-left:20rpx;margin-top: 10rpx;width:160rpx;height:60rpx;line-height:60rpx;color:#fff;border-radius:3px;text-align:center}
.btn2{margin-left:20rpx;margin-top: 10rpx;width:160rpx;height:60rpx;line-height:60rpx;color:#333;background:#fff;border:1px solid #cdcdcd;border-radius:3px;text-align:center}

</style>