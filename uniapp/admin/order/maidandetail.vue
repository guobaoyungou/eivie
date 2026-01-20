<template>
<view class="container">
	<block v-if="isload">
		<view class="orderinfo">
			<view class="item">
				<text class="t1">下单人</text>
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
      member: {},
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
			app.get('ApiAdminOrder/maidandetail', {id: that.opt.id}, function (res) {
				that.loading= false;
				that.detail = res.detail;
				that.couponrecord = res.couponrecord;
				that.mendian = res.mendian;
				that.member = res.member;
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