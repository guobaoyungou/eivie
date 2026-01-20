<template>
<view class="container">
	<block v-if="isload">
		<view class="search flex" >
			<view class="paytype" @tap="choosePaytype"><input type="text" placeholder="支付类型" name="time" :value="paytypestr"></view>
			<uni-datetime-picker v-model="datetimerange" type="datetimerange" @change="timeChange" rangeSeparator="至" />
		</view>
		<view class="content" >
			<view class="item">
				<view style="line-height: 60rpx;">开始时间：{{data.logintime}}</view>
				<view style="line-height: 60rpx;">结束时间：{{data.jiaobantime}}</view>
			</view>
			<view class="item">
				<view class="title">订单数量：{{data.total_ordercount}}</view>
				<view class="flex item-view">
					<view>收银机订单数：{{data.cashdesk_ordercount}}</view>
					<view>线上订单数：{{data.online_ordercount}}</view>
				</view>
			</view>
			<view class="item">
				<view class="title">收银机营业额：￥{{data.today_total_money}}</view>
				<view class="flex item-view">
					<view v-show="paytypeid =='0' || paytypeid==''">现金支付：￥{{data.today_cash_money}}</view>
					<view v-show="paytypeid =='1' || paytypeid==''">余额支付：￥{{data.today_yue_money}}</view>
					<view v-show="paytypeid =='2' || paytypeid==''">微信支付：￥{{data.today_wx_money}}</view>
					<view v-show="paytypeid =='3' || paytypeid=='' ">支付宝支付：￥{{data.today_alipay_money}}</view>
					<view v-show="paytypeid =='81' || paytypeid=='' ">随行付支付：￥{{data.today_sxf_money}}</view>
					<block v-if="Object.keys(data.today_custom_pay_list).length >0">
						<view v-for="(item,index) in data.today_custom_pay_list" v-if="paytypeid == item.paytypeid || paytypeid==''">{{item.title}}支付：{{item.money}}</view>
					</block>
					<block v-if="data.cashpay_show && (paytypeid =='0' || paytypeid=='' )">
						<view >混合支付微信：￥{{data.mix_wx_pay}}</view>
						<view >混合支付支付宝：￥{{data.mix_alipay_pay}}</view>
						<view v-if="data.sxfpay_show">混合支付随行付：￥{{data.mix_sxf_pay}}</view>
					</block>
				</view>
			</view>
			<view class="item">
				<view class="title">线上营业额：￥{{data.online_total_money}}</view>
				<view class="flex item-view">
					<view v-show="paytypeid =='1' || paytypeid=='' ">余额支付：￥{{data.online_yue_money}}</view>
					<view v-show="paytypeid =='2' || paytypeid=='' ">微信线上支付：￥{{data.online_wx_money}}</view>
					<view v-show="paytypeid =='3' || paytypeid=='' ">支付宝线上支付：￥{{data.online_alipay_money}}</view>
					<view v-show="paytypeid =='0' || paytypeid=='' ">后台补录：￥{{data.online_admin_money}}</view>
				</view>
			</view>
			<view class="item">
				<view class="title">优惠金额（不参与其他统计）：-￥{{data.youhui_total}}</view>
				<view class="flex item-view">
					
				</view>
			</view>
			<view class="item" v-if="data.recharge_show">
				<view class="title">会员储值 (预付款)收款小计：￥{{data.recharge_total_money}}</view>
				<view class="flex item-view">
					<view v-show="paytypeid =='0' || paytypeid==''">现金支付：￥{{data.recharge_cash_money}}</view>
					<view v-show="paytypeid =='2' || paytypeid=='' ">微信线上支付：￥{{data.recharge_wx_money}}</view>
					<view v-show="paytypeid =='3' || paytypeid=='' ">支付宝线上支付：￥{{data.recharge_alipay_money}}</view>
					<view v-show="data.sxfpay_show && (paytypeid =='81' || paytypeid=='' )">随行付：￥{{data.recharge_sxf_money}}</view>
				</view>
			</view>
			<view class="item">
				<view class="title">退款总额：￥{{data.refund_total_money}}</view>
				<view class="flex item-view">
					<view v-show="paytypeid =='0' || paytypeid==''">现金退款：￥{{data.refund_cash_money}}</view>
					<view v-show="paytypeid =='1' || paytypeid==''">余额退款：￥{{data.refund_yue_money}}</view>
					<view v-show="paytypeid =='2' || paytypeid=='' ">微信退款：￥{{data.refund_wx_money}}</view>
					<view v-show="paytypeid =='3' || paytypeid=='' ">支付宝退款：￥{{data.refund_alipay_money}}</view>
					<view v-show="data.sxfpay_show && (paytypeid =='81' || paytypeid=='' )">随行付退款：￥{{data.refund_sxf_money}}</view>
					<block v-if="Object.keys(data.refund_custom_list).length >0">
						<view v-for="(item,index) in data.refund_custom_list" v-if="paytypeid == item.paytypeid || paytypeid==''">{{item.title}}退款：{{item.refund_money}}</view>
					</block>
					<block v-if="data.cashpay_show && (paytypeid =='0' || paytypeid=='' )">
						<view >混合支付微信退款：￥{{data.mix_refund_wx_pay}}</view>
						<view >混合支付支付宝退款：￥{{data.mix_refund_alipay_pay}}</view>
						<view v-if="data.sxfpay_show">混合支付随行付退款：￥{{data.mix_refund_sxf_pay}}</view>
					</block>
				</view>
			</view>
			<view class="item">
				<view class="title">汇总（以下数据均扣除退款金额）</view>
				<view class="flex item-view">
					<view >营业额汇总（包含会员储值与会员消费）：￥{{data.all_yingyee_money}}</view>
					<view >营业额汇总（仅不含会员储值）：￥{{data.yingyee_money}}</view>
					<view >收款汇总（仅不含会员余额消费）：￥{{data.total_in_money}}</view>
					<!-- <view >收款汇总：￥{{data.all_total_in_money}}</view> -->
					<view >余额支付汇总（线上+线下）：￥{{data.all_yue_money}}</view>
				</view>
			</view>
		</view>
		<view style="height: 80rpx;"></view>
		<button class="savebtn" @tap="wifiPrint" :style="'background:linear-gradient(90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'" >打印</button>
	</block>
	<view v-if="showPaytype" class="popup__container">
		<view class="popup__overlay" @tap.stop="hidePaytypeDialog"></view>
		<view class="popup__modal">
			<view class="popup__title">
				<text class="popup__title-text">请选择支付方式</text>
				<image :src="pre_url+'/static/img/close.png'" class="popup__close" style="width:36rpx;height:36rpx" @tap.stop="hidePaytypeDialog"/>
			</view>
			<view class="popup__content">
				<view class="pstime-item" :key="index" @tap="paytypeRadioChange" :data-index="-1">
					<view class="flex1">全部</view>
					<view class="radio" :style="''"><image class="radio-img" :src="pre_url+'/static/img/checkd.png'"/></view>
				</view>
				<view class="pstime-item" v-for="(item, index) in paytypelist" :key="index" @tap="paytypeRadioChange" :data-index="index">
					<view class="flex1">{{item.title}}</view>
					<view class="radio" :style="''"><image class="radio-img" :src="pre_url+'/static/img/checkd.png'"/></view>
				</view>
			</view>
		</view>
	</view>
	</block>
	<loading v-if="loading"></loading>
</view>
</template>

<script>
var app = getApp();
import uniDatetimePicker from './uni-datetime-picker/uni-datetime-picker.vue'
export default {
	components: {
		uniDatetimePicker
	},
	data() {
		return {
			opt:{},
			loading:false,
			isload: false,
			menuindex:-1,
			pre_url:app.globalData.pre_url,
			data: [],
			paytypestr :'支付方式',
			showPaytype:false,
			paytypelist:[],
			paytypeid:'',
			starttime:'',
			endtime:'',
			rangetime:'',
			datetimerange: [],
		};
	},
	onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.getpaytype();
		this.getdata();
	},
	onPullDownRefresh: function () {
		this.getdata();
	},
  methods: {
    getdata: function () {
		var that = this;	
		that.loading = true;
		var paytypeid = that.paytypeid;
		var ctime =that.datetimerange
		app.post('ApiAdminFinance/gettradereport', {paytypeid: paytypeid,ctime: ctime,isprint:0}, function (res) {
			var data = res.data;
			that.data = data;
			that.loading = false;
			that.loaded();
      });
    },
	getpaytype:function(){
		var that = this;	
		app.post('ApiAdminFinance/getpaytypelist', {}, function (res) {
			that.loading = false;
			that.paytypelist =  res.data;		
		});
	},
	choosePaytype:function(){
		this.showPaytype = true;
	},
	hidePaytypeDialog:function(){
		this.showPaytype = false;
	},
	paytypeRadioChange:function(e){
		var index = e.currentTarget.dataset.index;
		console.log(index);
		if(index >=0){
			var patypedata = this.paytypelist[index];
			this.paytypeid = patypedata['id'];
			this.paytypestr = patypedata['title'];
		}else{
			this.paytypeid = '';
			this.paytypestr = '全部'
		}
	
		this.showPaytype = false;
		this.getdata();
	},
	timeChange:function(){
		this.getdata();
	},
	wifiPrint:function(){
		var that = this;
		that.loading = true;
		var paytypeid = that.paytypeid;
		var ctime =that.datetimerange
		app.post('ApiAdminFinance/gettradereport', {paytypeid: paytypeid,ctime: ctime,isprint:1}, function (res) {
			that.loading = false;
			app.success(res.msg);
		});
	}
  }
};
</script>
<style>
.container{padding: 20rpx;}
.search{justify-content: flex-start;margin-bottom: 20rpx;}
.search .paytype{ width: 40%;overflow:hidden;border-radius: 4px;border: 1px solid #e5e5e5}
.search .paytype input{height: 70rpx;line-height: 70rpx;text-align: center;background-color: #fff;color: #666;font-size: 28rpx;}
.search .timesearch{width: 40%;}
.content{ width:94%;margin:0 3%;}
.content .item{margin-bottom: 20rpx;}
.content .item .title{font-size: 32rpx;font-weight: 700;line-height: 70rpx;border-bottom: 1rpx solid #bdbdbd;margin-bottom: 10rpx;}
.content .item .item-view {flex-wrap: wrap;justify-content: space-between}
.content .item .item-view view{line-height: 60rpx;padding: 10rpx 10rpx;min-width: 40%;}

.pstime-item{display:flex;border-bottom: 1px solid #f5f5f5;padding:20rpx 30rpx;}
.pstime-item .radio{flex-shrink:0;width: 32rpx;height: 32rpx;background: #FFFFFF;border: 2rpx solid #BFBFBF;border-radius: 50%;margin-right:30rpx}
.pstime-item .radio .radio-img{width:100%;height:100%}
.savebtn{ width: 90%; height:80rpx; line-height: 80rpx; text-align:center;border-radius:8rpx; color: #fff;font-weight:bold;margin: 0 5%; border: none; position: fixed; bottom: 30rpx;}
</style>