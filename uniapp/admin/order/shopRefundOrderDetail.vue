<template>
<view class="container">
	<block v-if="isload">
		<view class="ordertop" :style="'background:url(' + pre_url + '/static/img/ordertop_refund.jpg);background-size:100%'">
			<view class="f1" v-if="detail.refund_status==0">
				<view class="t1">已取消</view>
			</view>
			<view class="f1" v-if="detail.refund_status==1">
				<view class="t1">待审核</view>
			</view>
			<view class="f1" v-if="detail.refund_status==2">
				<view class="t1">审核通过，已退款</view>
			</view>
			<view class="f1" v-if="detail.refund_status==3">
				<view class="t1">驳回</view>
			</view>
			<view class="f1" v-if="detail.refund_status==4">
				<view class="t1">审核通过，待退货</view>
				<view class="t2">联系买家进行退货</view>
			</view>
		</view>
		<!-- <view class="address">
			<view class="img">
				<image src="/static/img/address3.png"></image>
			</view>
			<view class="info">
				<text class="t1">{{detail.linkman}} {{detail.tel}}</text>
				<text class="t2" v-if="detail.freight_type!=1 && detail.freight_type!=3">地址：{{detail.area}}{{detail.address}}</text>
				<text class="t2" v-if="detail.freight_type==1" @tap="openLocation" :data-address="storeinfo.address" :data-latitude="storeinfo.latitude" :data-longitude="storeinfo.longitude">取货地点：{{storeinfo.name}} - {{storeinfo.address}}</text>
			</view>
		</view> -->
		<view class="product">
			<view v-for="(item, idx) in prolist" :key="idx" class="content">
				<view @tap="goto" :data-url="'/pages/shop/product?id=' + item.proid">
					<image :src="item.pic"></image>
				</view>
				<view class="detail">
					<text class="t1">{{item.name}}</text>
					<text class="t2">{{item.ggname}}</text>
					<view class="t3"><text class="x1 flex1">￥{{item.sell_price}}</text><text class="x2">×{{item.refund_num}}</text></view>
				</view>
			</view>
		</view>
		
		<view class="orderinfo">
			<view class="item">
				<text class="t1">下单人</text>
				<text class="flex1"></text>
				<image :src="detail.headimg" style="width:80rpx;height:80rpx;margin-right:8rpx"/>
				<text  style="height:80rpx;line-height:80rpx">{{detail.nickname}}</text>
			</view>
			<view class="item">
				<text class="t1">{{t('会员')}}ID</text>
				<text class="t2">{{detail.mid}}</text>
			</view>
		</view>
		<view class="orderinfo">
			<view class="item">
				<text class="t1">类型</text>
				<text class="t2 red">{{detail.refund_type_label}}</text>
			</view>
			<view class="item">
				<text class="t1">退货单编号</text>
				<text class="t2" user-select="true" selectable="true">{{detail.refund_ordernum}}</text>
			</view>
			<view class="item">
				<text class="t1">申请退款金额</text>
				<text class="t2 red">¥{{detail.refund_money}}</text>
			</view>
			<view class="item">
				<text class="t1">本单已退款金额</text>
				<text class="t2 red">¥{{detail.refundMoneyTotal}}</text>
			</view>
			<view class="item">
				<text class="t1">申请时间</text>
				<text class="t2">{{detail.createtime}}</text>
			</view>
			<view class="item" v-if="detail.refund_status>0">
				<text class="t1">退款状态</text>
				<text class="t2 grey" v-if="detail.refund_status==0">已取消</text>
				<text class="t2 red" v-if="detail.refund_status==1">待审核</text>
				<text class="t2 red" v-if="detail.refund_status==2">已退款</text>
				<text class="t2 red" v-if="detail.refund_status==3">已驳回</text>
				<text class="t2 red" v-if="detail.refund_status==4">审核通过，待退货</text>
			</view>
			<view class="item" v-if="detail.refund_status>0">
				<text class="t1">退款原因</text>
				<text class="t2 red">{{detail.refund_reason}}</text>
			</view>
			<view class="item">
				<text class="t1">图片</text>
				<text class="t2" v-if="detail.refund_pics && detail.refund_pics.length > 0"><block v-for="item in detail.refund_pics"><image :src="item" class="imageMin" mode="widthFix" @tap="previewImage" :data-url="item"/></block></text>
				<text class="t2" v-else>无</text>
			</view>
			<view class="item" v-if="detail.refund_checkremark">
				<text class="t1">审核备注</text>
				<text class="t2 red">{{detail.refund_checkremark}}</text>
			</view>
		</view>
		<view class="orderinfo">
			<view class="item">
				<text class="t1">订单编号</text>
				<text class="t2" user-select="true" selectable="true">{{order.ordernum}}</text>
			</view>
			<view class="item">
				<text class="t1">下单时间</text>
				<text class="t2">{{order.createtime}}</text>
			</view>
			<view class="item" v-if="order.status>0 && order.paytypeid!='4' && order.paytime">
				<text class="t1">支付时间</text>
				<text class="t2">{{order.paytime}}</text>
			</view>
			<view class="item" v-if="order.status>0 && order.paytime">
				<text class="t1">支付方式</text>
				<text class="t2">{{order.paytype}}</text>
			</view>
			<view class="item" v-if="order.status>1 && order.send_time">
				<text class="t1">发货时间</text>
				<text class="t2">{{order.send_time}}</text>
			</view>
			<view class="item" v-if="order.status==3 && order.collect_time">
				<text class="t1">收货时间</text>
				<text class="t2">{{order.collect_time}}</text>
			</view>
		</view>
		<view class="orderinfo">
      <view class="item" v-if="order.issource && order.source && order.source == 'supply_zhenxin'">
      	<text class="t1">商品来源</text>
      	<text class="t2">甄新汇选</text>
      </view>
			<view class="item">
				<text class="t1">商品金额</text>
				<text class="t2 red">¥{{order.product_price}}</text>
			</view>
			<view class="item" v-if="order.leveldk_money > 0">
				<text class="t1">{{t('会员')}}折扣</text>
				<text class="t2 red">-¥{{order.leveldk_money}}</text>
			</view>
			<view class="item" v-if="order.manjian_money > 0">
				<text class="t1">满减活动</text>
				<text class="t2 red">-¥{{order.manjian_money}}</text>
			</view>
			<view class="item">
				<text class="t1">配送方式</text>
				<text class="t2">{{order.freight_text}}</text>
			</view>
			<view class="item" v-if="order.freight_type==1 && order.freight_price > 0">
				<text class="t1">服务费</text>
				<text class="t2 red">+¥{{order.freight_price}}</text>
			</view>
			<view class="item" v-if="order.freight_time">
				<text class="t1">{{order.freight_type!=1?'配送':'提货'}}时间</text>
				<text class="t2">{{order.freight_time}}</text>
			</view>
			<view class="item" v-if="order.coupon_money > 0">
				<text class="t1">{{t('优惠券')}}抵扣</text>
				<text class="t2 red">-¥{{order.coupon_money}}</text>
			</view>
			
			<view class="item" v-if="order.scoredk_money > 0">
				<text class="t1">{{t('积分')}}抵扣</text>
				<text class="t2 red">-¥{{order.scoredk_money}}</text>
			</view>
      <view class="item" v-if="order.dec_money > 0">
        <text class="t1">{{t('余额')}}抵扣</text>
        <text class="t2 red">-¥{{order.dec_money}}</text>
      </view>
			<view class="item">
				<text class="t1">实付款</text>
				<text class="t2 red">¥{{order.totalprice}}</text>
			</view>
      <view class="item" v-if="order.combine_money && order.combine_money > 0">
      	<text class="t1">{{t('余额')}}已付</text>
      	<text class="t2 red">-¥{{order.combine_money}}</text>
      </view>
      <view class="item" v-if="order.paytypeid == 2 && order.combine_wxpay && order.combine_wxpay > 0">
      	<text class="t1">微信已付</text>
      	<text class="t2 red">-¥{{order.combine_wxpay}}</text>
      </view>
      <view class="item" v-if="(order.paytypeid == 3 || (order.paytypeid>=302 && order.paytypeid<=330)) && order.combine_alipay && order.combine_alipay > 0">
      	<text class="t1">支付宝已付</text>
      	<text class="t2 red">-¥{{order.combine_alipay}}</text>
      </view>

			<view class="item">
				<text class="t1">订单状态</text>
				<text class="t2" v-if="order.status==0">未付款</text>
				<text class="t2" v-if="order.status==1">已付款</text>
				<text class="t2" v-if="order.status==2">已发货</text>
				<text class="t2" v-if="order.status==3">已收货</text>
				<text class="t2" v-if="order.status==4">已关闭</text>
			</view>
		</view>

		<view style="width:100%;height:calc(160rpx + env(safe-area-inset-bottom));"></view>

		<view class="bottom notabbarbot">
			<block v-if="detail.refund_status==1 && detail.cancheck">
				<view class="btn2" @tap="refundnopassShow" :data-id="detail.id">驳回</view>
				<view v-if="detail.refund_type == 'refund'" class="btn2" @tap="refundpass" :data-id="detail.id">退款通过</view>
				<view v-else-if="detail.refund_type == 'return'" class="btn2" @tap="returnpass" :data-id="detail.id">退货退款通过</view>
			</block>
			<view v-if="detail.refund_type == 'return' && detail.refund_status==4 && detail.cancheck" class="btn2" @tap="refundpass" :data-id="detail.id" :data-title="'确定要收到退货并退款吗？'">收货并退款</view>
			<!-- <view class="btn2" @tap="setremark" :data-id="detail.id">设置备注</view> -->
		</view>
		<uni-popup id="dialogSetremark" ref="dialogSetremark" type="dialog">
			<uni-popup-dialog mode="input" title="确定要驳回申请吗？" :value="detail.remark" placeholder="请输入备注" @confirm="refundnopass"></uni-popup-dialog>
		</uni-popup>
	

	</block>
	<loading v-if="loading"></loading>
	<dp-tabbar :opt="opt"></dp-tabbar>
	<popmsg ref="popmsg"></popmsg>
</view>
</template>

<script>
var app = getApp();
var interval = null;

export default {
  data() {
    return {
			opt:{},
			loading:false,
      isload: false,
			menuindex:-1,
			
			pre_url:app.globalData.pre_url,
      order: {},
      detail: "",
      prolist: ""
    };
  },

  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.getdata();
  },
	onPullDownRefresh: function () {
		this.getdata();
	},
  onUnload: function () {
    clearInterval(interval);
  },
  methods: {
		getdata: function () {
			var that = this;
			that.loading = true;
			app.get('ApiAdminOrder/shopRefundOrderDetail', {id: that.opt.id}, function (res) {
				that.loading = false;
				that.detail = res.detail;
				that.order = res.order;
				that.prolist = res.prolist;
				that.loaded();
			});
		},
		setremark:function(){
			this.$refs.dialogSetremark.open();
		},
		setremarkconfirm: function (done, remark) {
			this.$refs.dialogSetremark.close();
			var that = this
			app.post('ApiAdminOrder/setremark', { type:'shop',orderid: that.detail.id,content:remark }, function (res) {
				app.success(res.msg);
				setTimeout(function () {
					that.getdata();
				}, 1000)
			})
    },
		refundnopassShow: function (e) {
			this.$refs.dialogSetremark.open();
		},
		refundnopass: function (done, remark) {
			this.$refs.dialogSetremark.close();
			var that = this;
			var orderid = that.detail.id;
			app.showLoading();
			app.post('ApiAdminOrder/refundnopass', { type:'shop',orderid: orderid,remark:remark, release:'2106'}, function (data) {
				app.showLoading(false);
				app.success(data.msg);
				setTimeout(function () {
					that.getdata();
				}, 1000)
			})
		},
		refundpass: function (e) {
			var that = this;
			var orderid = e.currentTarget.dataset.id;
			var title = e.currentTarget.dataset.title;
			app.confirm(title ? title : '确定要审核通过并退款吗?', function () {
				app.showLoading('提交中');
				app.post('ApiAdminOrder/refundpass', { type:'shop',orderid: orderid, release:'2106' }, function (data) {
					app.showLoading(false);
					app.success(data.msg);
					setTimeout(function () {
						that.getdata();
					}, 1000)
				})
			});
		},
		returnpass: function (e) {
			var that = this;
			var orderid = e.currentTarget.dataset.id
			app.confirm('确定同意买家退货退款吗?', function () {
				app.showLoading('提交中');
				app.post('ApiAdminOrder/returnpass', { type:'shop',orderid: orderid, release:'2106' }, function (data) {
					app.showLoading(false);
					app.success(data.msg);
					setTimeout(function () {
						that.getdata();
					}, 1000)
				})
			});
		},
  }
};
</script>
<style>
.imageMin {width:130rpx;height:auto; margin-right: 6rpx;}
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
.product .content{display:flex;position:relative;width: 100%; padding:16rpx 0px;border-bottom: 1px #e5e5e5 dashed;}
.product .content:last-child{ border-bottom: 0; }
.product .content image{ width: 140rpx; height: 140rpx;}
.product .content .detail{display:flex;flex-direction:column;margin-left:14rpx;flex:1}
.product .content .detail .t1{font-size:26rpx;line-height:36rpx;margin-bottom:10rpx;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:2;overflow:hidden;}
.product .content .detail .t2{height: 46rpx;line-height: 46rpx;color: #999;overflow: hidden;font-size: 26rpx;}
.product .content .detail .t3{display:flex;height:40rpx;line-height:40rpx;color: #ff4246;}
.product .content .detail .x1{ flex:1}
.product .content .detail .x2{ width:100rpx;font-size:32rpx;text-align:right;margin-right:8rpx}
.product .content .comment{position:absolute;top:64rpx;right:10rpx;border: 1px #ffc702 solid; border-radius:10rpx;background:#fff; color: #ffc702;  padding: 0 10rpx; height: 46rpx; line-height: 46rpx;}
.product .content .comment2{position:absolute;top:64rpx;right:10rpx;border: 1px #ffc7c2 solid; border-radius:10rpx;background:#fff; color: #ffc7c2;  padding: 0 10rpx; height: 46rpx; line-height: 46rpx;}

.orderinfo{width:94%;margin:0 3%;border-radius:8rpx;margin-top:16rpx;margin-top:10rpx;padding: 14rpx 3%;background: #FFF;}
.orderinfo .item{display:flex;width:100%;padding:20rpx 0;border-bottom:1px dashed #ededed;}
.orderinfo .item:last-child{ border-bottom: 0;}
.orderinfo .item .t1{width:200rpx;}
.orderinfo .item .t2{flex:1;text-align:right}
.orderinfo .item .red{color:red}

.bottom{ width: 100%; height:calc(92rpx + env(safe-area-inset-bottom));padding: 0 20rpx;background: #fff; position: fixed; bottom: 0px;left: 0px;display:flex;justify-content:flex-end;align-items:center;}

.btn1{margin-left:20rpx;width:160rpx;height:60rpx;line-height:60rpx;color:#fff;border-radius:3px;text-align:center}
.btn2{margin-left:20rpx;padding:0 30rpx;height:60rpx;line-height:60rpx;color:#333;background:#fff;border:1px solid #cdcdcd;border-radius:3px;text-align:center}
.btn3{position:absolute;top:60rpx;right:10rpx;font-size:24rpx;width:120rpx;height:50rpx;line-height:50rpx;color:#333;background:#fff;border:1px solid #cdcdcd;border-radius:3px;text-align:center}

.btitle{ width:100%;height:100rpx;background:#fff;padding:0 20rpx;border-bottom:1px solid #f5f5f5}
.btitle .comment{border: 1px #ffc702 solid;border-radius:10rpx;background:#fff; color: #ffc702;  padding: 0 10rpx; height: 46rpx; line-height: 46rpx;}
.btitle .comment2{border: 1px #ffc7c0 solid;border-radius:10rpx;background:#fff; color: #ffc7c0;  padding: 0 10rpx; height: 46rpx; line-height: 46rpx;}

.picker{display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:1;overflow:hidden;}

.uni-popup-dialog {width: 300px;border-radius: 5px;background-color: #fff;}
.uni-dialog-title {display: flex;flex-direction: row;justify-content: center;padding-top: 15px;padding-bottom: 5px;}
.uni-dialog-title-text {font-size: 16px;font-weight: 500;}
.uni-dialog-content {display: flex;flex-direction: row;justify-content: center;align-items: center;padding: 5px 15px 15px 15px;}
.uni-dialog-content-text {font-size: 14px;color: #6e6e6e;}
.uni-dialog-button-group {display: flex;flex-direction: row;border-top-color: #f5f5f5;border-top-style: solid;border-top-width: 1px;}
.uni-dialog-button {display: flex;flex: 1;flex-direction: row;justify-content: center;align-items: center;height: 45px;}
.uni-border-left {border-left-color: #f0f0f0;border-left-style: solid;border-left-width: 1px;}
.uni-dialog-button-text {font-size: 14px;}
.uni-button-color {color: #007aff;}

.layui-imgbox{margin-right:16rpx;margin-bottom:10rpx;font-size:24rpx;position: relative;}
.layui-imgbox-img{display: block;width:200rpx;height:200rpx;padding:2px;border: #d3d3d3 1px solid;background-color: #f6f6f6;overflow:hidden}
.layui-imgbox-img>image{max-width:100%;}
.layui-imgbox-repeat{position: absolute;display: block;width:32rpx;height:32rpx;line-height:28rpx;right: 2px;bottom:2px;color:#999;font-size:30rpx;background:#fff}
.uploadbtn{position:relative;height:200rpx;width:200rpx}
</style>