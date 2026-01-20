<template>
<view class="container">
	<block v-if="isload">
		<view class="orderinfo">
			<view class="item">
				<text class="t1">{{t('会员')}}信息</text>
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
		<view class="orderinfo" v-if="canrefund">
			<view class="item">
				<text class="t1">已退款金额</text>
				<text class="t2">￥{{detail.refund_money}}</text>
			</view>
			<view class="item">
				<text class="t1">剩余可退</text>
				<text class="t2">￥{{detail.can_refund_money}}</text>
			</view>
			<view class="item option"  v-if="detail.can_refund_money>0">
				<view class="btn" :style="{background:t('color1')}" @tap="toggleRefund">退 款</view>
			</view>
		</view>
		<view v-if="isshowrefund" class="popup__container popup__refund">
			<view class="popup__overlay" @tap.stop="toggleRefund"></view>
			<view class="popup__modal">
				<view class="popup__title">
					<text class="popup__title-text">备注信息</text>
					<image :src="pre_url+'/static/img/close.png'" class="popup__close" style="width:36rpx;height:36rpx" @tap.stop="toggleRefund"/>
				</view>
				<view class="popup__content">
					<view class="form-item">
						<view class="label">退款金额<text class="tips">(可退金额:￥{{detail.can_refund_money}})</text></view>
						<view class="input flex-input">
							<input  type="digit" v-model="money" placeholder-style="font-size:26rpx;color:#999" placeholder="请填写备注信息"/>
							<text @tap="allmoney" class="alltxt" :style="{color:t('color1')}">全部</text>
						</view>
					</view>
					<view class="form-item">
						<text class="label">退款备注</text>
						<textarea class="textarea" v-model="remark" placeholder-style="font-size:26rpx;color:#999" placeholder="请填写备注信息"></textarea>
					</view>
				</view>
				<view class="popup__bottom">
					<button class="refund-confirm" @tap="refundConfirm" :style="{background:t('color1')}">确 定</button>
				</view>
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
			canrefund:false,
			refundmoney:0,
			isshowrefund:false,
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
			app.get('ApiAdminMaidan/maidandetail', {id: that.opt.id}, function (res) {
				that.loading= false;
				that.detail = res.detail;
				that.couponrecord = res.couponrecord;
				that.mendian = res.mendian;
				that.member = res.member;
				that.canrefund = res.canrefund;
				that.refundmoney = res.refundmoney;
				that.loaded();
			});
		},
		toggleRefund:function(){
			this.isshowrefund = !this.isshowrefund
		},
		allmoney:function(){
			this.money = this.detail.can_refund_money
		},
		refundConfirm:function(){
			var that = this;
			if(that.money==0 || that.money<0){
				app.error('请正确填写退款金额');return;
			}
			app.showLoading('提交中...');
			app.post('ApiAdminMaidan/maidanrefund', {money:that.money,remark:that.remark,id:that.opt.id}, function(res) {
				app.showLoading(false);
				if (res.status==1) {
					that.isshowrefund = false;
					that.money = 0;
					that.remark = '';
					app.success(res.msg);
					that.getdata();
				}  else {
					app.error(res.msg);
				}
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