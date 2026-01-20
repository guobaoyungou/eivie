<template>
<view class="container">
	<block v-if="isload">
		<view class="orderinfo">
			<view class="item">
				<text class="t1">订单编号</text>
				<text class="t2">{{detail.ordernum}}</text>
			</view>
			<view class="item"  v-if="detail.paynum">
				<text class="t1">支付流水</text>
				<text class="t2">{{detail.paynum}}</text>
			</view>
			<view class="item" v-if="mendian">
				<text class="t1">付款门店</text>
				<text class="t2">{{mendian.name}}</text>
			</view>
			<view class="item">
				<text class="t1">付款金额</text>
				<text class="t2">￥{{detail.money}}</text>
			</view>
			<view class="item">
				<text class="t1">实付金额</text>
				<text class="t2" style="font-size:32rpx;color:#e94745">￥{{detail.paymoney}}</text>
			</view>
			<view class="item" v-if="detail.canrefund">
				<text class="t1">退款金额</text>
				<text class="t2" style="color: #ff9b00;">￥{{detail.refund_money}}</text>
			</view>
			<view class="item">
				<text class="t1">付款方式</text>
				<text class="t2">{{detail.paytype}}</text>
			</view>
			<view class="item">
				<text class="t1">状态</text>
				<text class="t2" v-if="detail.status==1" style="color:green">已付款</text>
				<text class="t2" v-else style="color:red">未付款</text>
			</view>
			<view class="item" v-if="detail.status>0 && detail.paytime">
				<text class="t1">付款时间</text>
				<text class="t2">{{detail.paytime}}</text>
			</view>
			<view class="item" v-if="detail.disprice>0">
				<text class="t1">{{t('会员')}}折扣</text>
				<text class="t2">-￥{{detail.disprice}}</text>
			</view>
			<view class="item" v-if="detail.scoredk>0">
				<text class="t1">{{t('积分')}}抵扣</text>
				<text class="t2">-￥{{detail.scoredk}}</text>
			</view>
			<view class="item" v-if="detail.couponrid">
				<text class="t1">{{t('优惠券')}}抵扣</text>
				<text class="t2">-￥{{detail.couponmoney}}</text>
			</view>
			<view class="item" v-if="couponrecord">
				<text class="t1">{{t('优惠券')}}名称</text>
				<text class="t2">{{couponrecord.couponname}}</text>
			</view>
      <view class="item" v-if="dec_money">
      	<text class="t1">{{t('余额')}}名称</text>
      	<text class="t2">{{dec_money}}</text>
      </view>
      <view class="item" v-if="detail.dedamount_dkmoney && detail.dedamount_dkmoney>0">
      	<text class="t1">抵扣金</text>
      	<text class="t2">-￥{{detail.dedamount_dkmoney}}</text>
      </view>
      <view class="item" v-if="detail.combine_money && detail.combine_money > 0">
      	<text class="t1">{{t('余额')}}已付</text>
      	<text class="t2 red">-¥{{detail.combine_money}}</text>
      </view>
      <view class="item" v-if="detail.paytypeid == 2 && detail.combine_wxpay && detail.combine_wxpay > 0">
      	<text class="t1">微信已付</text>
      	<text class="t2 red">-¥{{detail.combine_wxpay}}</text>
      </view>
      <view class="item" v-if="(detail.paytypeid == 3 || (detail.paytypeid>=302 && detail.paytypeid<=330)) && detail.combine_alipay && detail.combine_alipay > 0">
      	<text class="t1">支付宝已付</text>
      	<text class="t2 red">-¥{{detail.combine_alipay}}</text>
      </view>
			<view class="item">
				<text class="t1">订单备注</text>
				<text class="t2">{{detail.remark}}</text>
			</view>
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
			
      detail: {},
      couponrecord: {},
      mendian: {},
    };
  },

  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.getdata();
  },
	onPullDownRefresh: function () {
		this.getdata();
	},
  methods: {
		getdata: function (option) {
			var that = this;
			that.loading= true;
			app.get('ApiMaidanNew/maidandetail', {id: that.opt.id}, function (res) {
				that.loading= false;
				that.detail = res.detail;
				that.couponrecord = res.couponrecord;
				that.mendian = res.mendian;
				that.loaded();
			});
		},
  }
};
</script>
<style>
.orderinfo{ width:94%;margin:20rpx 3%;border-radius:5px;padding:20rpx 20rpx;padding: 14rpx 3%;background: #FFF;}
.orderinfo .item{display:flex;width:100%;padding:20rpx 0;border-bottom:1px dashed #ededed;}
.orderinfo .item:last-child{ border-bottom: 0;}
.orderinfo .item .t1{width:200rpx;}
.orderinfo .item .t2{flex:1;text-align:right}
.orderinfo .item .red{color:red}
</style>