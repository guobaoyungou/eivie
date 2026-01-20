<template>
<view class="container">
	<block v-if="isload">
		<view class="orderinfo">
			<view class="item">
				<text class="t1">会员信息</text>
				<text class="flex1"></text>
				<image :src="member.headimg" style="width:80rpx;height:80rpx;margin-right:8rpx"/>
				<text  style="height:80rpx;line-height:80rpx">{{member.nickname}}</text>
			</view>
			<view class="item">
				<text class="t1">{{t('会员')}}ID</text>
				<text class="t2">{{detail.mid}}</text>
			</view>
		</view>
		<view class="orderinfo">
			<view class="item">
				<text class="t1">订单编号</text>
				<text class="t2">{{detail.ordernum}}</text>
			</view>
			<view class="item">
				<text class="t1">支付单号</text>
				<text class="t2">{{detail.paynum || ''}}</text>
			</view>
			<view class="item">
				<text class="t1">支付项目</text>
				<text class="t2">{{detail.title}}</text>
			</view>
			<view class="item">
				<text class="t1">支付方式</text>
				<text class="t2">{{detail.paytype}}</text>
			</view>
			<view class="item">
				<text class="t1">支付金额</text>
				<text class="t2">￥{{detail.money}}</text>
			</view>
			<view class="item">
				<text class="t1">发起时间</text>
				<text class="t2">{{detail.createtime}}</text>
			</view>
			<view class="item">
				<text class="t1">付款时间</text>
				<text class="t2">{{detail.paytime}}</text>
			</view>
		</view>
		<view class="orderinfo" v-if="detail.refund_money > 0">
			<view class="item">
				<text class="t1">退款金额</text>
				<text class="t2">￥{{detail.refund_money}}</text>
			</view>
			<view class="item">
				<text class="t1">最后退款时间</text>
				<text class="t2">{{detail.refund_time}}</text>
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
      member: {},
			money:0,
			remark:'',
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
  methods: {
		getdata: function (option) {
			var that = this;
			that.loading= true;
			app.get('ApiAdminFinance/payorderdetail', {id: that.opt.id}, function (res) {
				that.loading= false;
				that.detail = res.detail;
				that.member = res.member;
				that.loaded();
			});
		}
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
.option{display: flex;justify-content: flex-end;}
.btn{width: 180rpx;border-radius: 8rpx;text-align: center;color: #FFF;height: 60rpx;line-height: 60rpx;}
.popup__refund{bottom: 40%;left: 8%;width: 84%;}
.popup__refund .popup__overlay{opacity: 0.6;}
.popup__refund .popup__modal{border-radius: 20rpx;min-height: 360rpx;}
.form-item{display: flex;flex-direction: column;padding:0 30rpx 30rpx 30rpx;}
.form-item .label{margin-bottom: 16rpx;}
.form-item .tips{font-size: 24rpx;color: #999;}
.form-item .input{width: 100%;background:#f6f6f6;padding: 10rpx 10rpx 10rpx 20rpx;border-radius: 8rpx;height: 70rpx;line-height: 70rpx;}
.flex-input{display: flex;justify-content: space-between;align-items: center;}
.flex-input .alltxt{font-size: 26rpx;white-space: nowrap;}
.popup__refund .textarea{padding: 20rpx;border-radius: 8rpx;height: 150rpx;background: #f6f6f6;font-size: 28rpx;}
.refund-confirm{width: 82%;margin-left: 9%;border-radius: 60rpx;height: 80rpx;line-height: 80rpx;color: #FFFFFF;}
</style>