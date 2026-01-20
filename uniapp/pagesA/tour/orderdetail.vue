<template>
<view class="container">
	<block v-if="isload">
		<view class="ordertop" :style="'background:url(' + pre_url + '/static/img/ordertop.png);background-size:100%'">
			<view class="f1" v-if="detail.status==0">
				<view class="t1">等待买家付款</view>
			</view>
			<view class="f1" v-if="detail.status==1">
				<view class="t1">{{detail.paytypeid==4 ? '已选择'+detail.paytype : '已成功付款'}}</view>
        <view class="t2" v-if="detail.freight_type!=1">我们会尽快为您发货</view>
        <view class="t2" v-if="detail.freight_type==1">请尽快前往自提地点取货</view>
			</view>
			<view class="f1" v-if="detail.status==2">
				<view class="t1">订单已发货</view>
				<text class="t2" user-select="true" selectable="true">发货信息：{{detail.express_com}} {{detail.express_no}}</text>
			</view>
			<view class="f1" v-if="detail.status==3">
				<view class="t1">订单已完成</view>
			</view>
			<view class="f1" v-if="detail.status==4">
				<view class="t1">订单已取消</view>
			</view>
		</view>
		<view class="address">
			<view class="img">
				<image :src="pre_url+'/static/img/address3.png'"></image>
			</view>
			<view class="info">
				<text class="t1" user-select="true" selectable="true">{{detail.linkman}} {{detail.tel}}</text>
				<text class="t2" v-if="detail.freight_type!=1" user-select="true" selectable="true">地址：{{detail.area}}{{detail.address}}</text>
				<text class="t2" v-if="detail.freight_type==1" @tap="openMendian" :data-storeinfo="storeinfo" :data-latitude="storeinfo.latitude" :data-longitude="storeinfo.longitude" user-select="true" selectable="true">取货地点：{{storeinfo.name}} - {{storeinfo.address}}</text>
			</view>
		</view>
		
		<view class="product">
			<view class="content">
				<view @tap="goto" :data-url="'product?id=' + detail.proid">
					<image :src="detail.propic"></image>
				</view>
				<view class="detail">
					<text class="t1">{{detail.proname}}</text>
					<view class="t3"><text class="x1 flex1">￥{{detail.sell_price}}</text><text class="x2">×{{detail.num}}</text></view>
				</view>
			</view>
		</view>
		
		<view class="orderinfo" v-if="(detail.status==3 || detail.status==2) && (detail.freight_type==3 || detail.freight_type==4)">
			<view class="item flex-col">
				<text class="t1" style="color:#111">发货信息</text>
				<text class="t2" style="text-align:left;margin-top:10rpx;padding:0 10rpx" user-select="true" selectable="true">{{detail.freight_content}}</text>
			</view>
		</view>

		<view class="orderinfo">
			<view class="item">
				<text class="t1">订单编号</text>
				<text class="t2" user-select="true" selectable="true">{{detail.ordernum}}</text>
			</view>
			<view class="item">
				<text class="t1">下单时间</text>
				<text class="t2">{{detail.createtime}}</text>
			</view>
			<view class="item" v-if="detail.status>0 && detail.paytime">
				<text class="t1">支付时间</text>
				<text class="t2">{{detail.paytime}}</text>
			</view>
			<view class="item" v-if="detail.status>1 && detail.send_time">
				<text class="t1">发货时间</text>
				<text class="t2">{{detail.send_time}}</text>
			</view>
			<view class="item" v-if="detail.status==3 && detail.collect_time">
				<text class="t1">收货时间</text>
				<text class="t2">{{detail.collect_time}}</text>
			</view>
		</view>
		<view class="orderinfo">
			<view class="item">
				<text class="t1">商品金额</text>
				<text class="t2 red">¥{{detail.product_price}}</text>
			</view>
			<view class="item">
				<text class="t1">配送方式</text>
				<text class="t2">{{detail.freight_text}}</text>
			</view>
			<view class="item" v-if="detail.freight_time">
				<text class="t1">{{detail.freight_type!=1?'配送':'提货'}}时间</text>
				<text class="t2">{{detail.freight_time}}</text>
			</view>
      <view class="item" v-if="detail.tour_code && detail.tour_code_decprice">
      	<text class="t1">优惠金额</text>
      	<text class="t2 red">-¥{{detail.tour_code_decprice}}</text>
      </view>
			<view class="item">
				<text class="t1">实付款</text>
				<text class="t2 red">¥{{detail.totalprice}}</text>
			</view>

			<view class="item">
				<text class="t1">订单状态</text>
				<text class="t2" v-if="detail.status==0">未付款</text>
				<block v-if="detail.status==1">
          <text v-if="detail.freight_type!=1" class="t2">待发货</text>
          <text v-if="detail.freight_type==1" class="t2">待提货</text>
				</block>
				<text class="t2" v-if="detail.status==2">已发货</text>
				<text class="t2" v-if="detail.status==3">已收货</text>
				<text class="t2" v-if="detail.status==4">已关闭</text>
			</view>
			<view class="item" v-if="detail.refund_status>0">
				<text class="t1">退款状态</text>
				<text class="t2 red" v-if="detail.refund_status==1">审核中,¥{{detail.refund_money}}</text>
				<text class="t2 red" v-if="detail.refund_status==2">已退款,¥{{detail.refund_money}}</text>
				<text class="t2 red" v-if="detail.refund_status==3">已驳回,¥{{detail.refund_money}}</text>
			</view>
		</view>
		
		<view class="orderinfo" v-if="(detail.formdata).length > 0">
			<view class="item" v-for="item in detail.formdata" :key="index">
				<text class="t1">{{item[0]}}</text>
				<view class="t2" v-if="item[2]=='upload'"><image :src="item[1]" style="width:400rpx;height:auto" mode="widthFix" @tap="previewImage" :data-url="item[1]"/></view>
				<text class="t2" v-else user-select="true" selectable="true">{{item[1]}}</text>
			</view>
		</view>

		<view style="width:100%;height:calc(160rpx + env(safe-area-inset-bottom));"></view>
		<view class="bottom notabbarbot" v-if="detail.status!=3">
			<block v-if="detail.status==0">
				<view class="btn2" @tap="toclose" :data-id="detail.id">关闭订单</view>
				<view class="btn1" @tap="goto" :data-url="'/pagesExt/pay/pay?id=' + detail.payorderid">去付款</view>
			</block>
			<block v-if="detail.status==1">
				<view v-if="detail.can_refund && (detail.refund_status==0 || detail.refund_status==3)" class="btn2" @tap="goto" :data-url="'refund?orderid=' + detail.id + '&price=' + detail.totalprice">申请退款</view>
			</block>
      <view class="btn2" v-if="detail.can_lookupimg" @tap.stop="goto" :data-url="'lookupimg?orderid=' + detail.id">上传图片</view>
      <block v-if="detail.status>=1 && detail.status<=3 && detail.can_lookalbum">
        <view class="btn2" @tap.stop="goto" :data-url="'lookalbum?orderid=' + detail.id">查看相册</view>
      </block>

			<block v-if="detail.status==2 || detail.status==3">
				<view class="btn2" @tap="goto" :data-url="'/pagesExt/order/logistics?express_com=' + detail.express_com + '&express_no=' + detail.express_no" v-if="detail.freight_type!=3 && detail.freight_type!=4">查看物流</view>
			</block>
			<block v-if="detail.status==2">
				<view class="btn1" @tap="orderCollect" :data-id="detail.id">确认收货</view>
			</block>
			<block v-if="detail.status==3">
				<view class="btn2">已完成</view>
			</block>
			<block v-if="detail.status==4">
				<view class="btn2" @tap="todel" :data-id="detail.id">删除订单</view>
			</block>
		</view>
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
			pre_url:app.globalData.pre_url,
      
      id:0,
			detail:{},
			storeinfo:{},
			invoice:0,
    }
  },

  onLoad: function (opt) {
		var that = this;
		var opt  = app.getopts(opt);
		if(opt && opt.trid){
		  that.trid = opt.trid;
		}else{
		  that.trid = app.globalData.trid
		}
    if(opt && opt.id){
      that.id = opt.id;
    }
		that.opt = opt;
  },
  onShow:function(){
    var that = this;
    that.getdata();
  },
	onPullDownRefresh: function () {
		this.getdata();
	},
  methods: {
		getdata: function (option) {
			var that = this;
			that.loading = true;
			app.get('ApiTour/orderdetail', {id: that.id}, function (res) {
				that.loading = false;
				that.detail = res.detail;
				that.storeinfo = res.storeinfo;

				that.loaded();
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
		showhxqr:function(){
			this.$refs.dialogHxqr.open();
		},
		closeHxqr:function(){
			this.$refs.dialogHxqr.close();
		},
		openMendian: function(e) {
			var storeinfo = e.currentTarget.dataset.storeinfo;
			app.goto('/pages/shop/mendian?id=' + storeinfo.id);
		}
  }
};
</script>
<style>
.ordertop{width:100%;height:220rpx;padding:50rpx 0 0 70rpx}
.ordertop .f1{color:#fff}
.ordertop .f1 .t1{font-size:32rpx;height:60rpx;line-height:60rpx}
.ordertop .f1 .t2{font-size:24rpx}

.address{ display:flex;width: 100%; padding: 20rpx 3%; background: #FFF;}
.address .img{width:40rpx}
.address image{width:40rpx; height:40rpx;}
.address .info{flex:1;display:flex;flex-direction:column;}
.address .info .t1{font-size:28rpx;font-weight:bold;color:#333}
.address .info .t2{font-size:24rpx;color:#999}

.product{width:94%;margin:0 3%;border-radius:8rpx;margin-top:16rpx;padding: 14rpx 3%;background: #FFF;}
.product .content{display:flex;position:relative;width: 100%; padding:16rpx 0px;border-bottom: 1px #e5e5e5 dashed;position:relative}
.product .content:last-child{ border-bottom: 0; }
.product .content image{ width: 140rpx; height: 140rpx;}
.product .content .detail{display:flex;flex-direction:column;margin-left:14rpx;flex:1}
.product .content .detail .t1{font-size:26rpx;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:2;overflow:hidden;}
.product .content .detail .t2{color: #999;font-size: 26rpx;margin-top: 10rpx;}
.product .content .detail .t3{display:flex;color: #ff4246;margin-top: 10rpx;}
.product .content .detail .t4{margin-top: 10rpx;}
.product .content .detail .x1{ flex:1}
.product .content .detail .x2{ width:100rpx;font-size:32rpx;text-align:right;margin-right:8rpx}
.product .content .comment{position:absolute;top:64rpx;right:10rpx;border: 1px #ffc702 solid; border-radius:10rpx;background:#fff; color: #ffc702;  padding: 0 10rpx; height: 46rpx; line-height: 46rpx;}

.orderinfo{width:94%;margin:0 3%;border-radius:8rpx;margin-top:16rpx;padding: 14rpx 3%;background: #FFF;}
.orderinfo .item{display:flex;width:100%;padding:20rpx 0;border-bottom:1px dashed #ededed;}
.orderinfo .item:last-child{ border-bottom: 0;}
.orderinfo .item .t1{width:200rpx;}
.orderinfo .item .t2{flex:1;text-align:right}
.orderinfo .item .red{color:red}

.bottom{ width: 100%;height:calc(92rpx + env(safe-area-inset-bottom));padding: 0 20rpx;background: #fff; position: fixed; bottom: 0px; left: 0px;display:flex;justify-content:flex-end;align-items:center;}
.btn1{margin-left:20rpx;width:160rpx;height:60rpx;line-height:60rpx;color:#fff;background:#FB4343;border-radius:3px;text-align:center}
.btn2{margin-left:20rpx;width:160rpx;height:60rpx;line-height:60rpx;color:#333;background:#fff;border:1px solid #cdcdcd;border-radius:3px;text-align:center}
.btn3{font-size:24rpx;width:120rpx;height:50rpx;line-height:50rpx;color:#333;background:#fff;border:1px solid #cdcdcd;border-radius:3px;text-align:center}

.hxqrbox{background:#fff;padding:50rpx;position:relative;border-radius:20rpx}
.hxqrbox .img{width:400rpx;height:400rpx}
.hxqrbox .txt{color:#666;margin-top:20rpx;font-size:26rpx;text-align:center}
.hxqrbox .close{width:50rpx;height:50rpx;position:absolute;bottom:-100rpx;left:50%;margin-left:-25rpx;border:1px solid rgba(255,255,255,0.5);border-radius:50%;padding:8rpx}
</style>