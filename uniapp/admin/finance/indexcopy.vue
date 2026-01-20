<template>
<view>
	<block v-if="isload">
	<view class="surverycontent">
		<view class="item">
				 <text class="t1">累计收款</text>
				 <text class="t2">￥{{info.wxpayCount}}</text>
				 <text class="t3">昨日新增：￥{{info.wxpayLastDayCount}}</text>
				 <text class="t3">本月新增：￥{{info.wxpayThisMonthCount}}</text>
		 </view>
		 <view class="item">
				 <text class="t1">累计退款</text>
				 <text class="t2">￥{{info.refundCount}}</text>
				 <text class="t3">昨日新增：￥{{info.refundLastDayCount}}</text>
				 <text class="t3">本月新增：￥{{info.refundThisMonthCount}}</text>
		 </view>
		 <view class="item" v-if="bid == 0">
				 <text class="t1">累计提现</text>
				 <text class="t2">￥{{info.withdrawCount}}</text>
				 <text class="t3">昨日新增：￥{{info.withdrawLastDayCount}}</text>
				 <text class="t3">本月新增：￥{{info.withdrawThisMonthCount}}</text>
		 </view>
		 <view class="item" v-if="bid == 0">
				 <text class="t1">累计{{t('佣金')}}</text>
				 <text class="t2">￥{{info.commissiontotal}}</text>
				 <text class="t3">待提{{t('佣金')}}：￥{{info.commission}}</text>
				 <text class="t3">已提{{t('佣金')}}：￥{{info.commissionwithdraw}}</text>
		 </view>
	</view>
	<block v-if="bid == 0">
	<view class="listcontent">
		<view class="list">
			<view class="item" @tap="goto" data-url="rechargelog">
				<view class="f1"><image :src="pre_url+'/static/img/rechargelog.png'"></image></view>
				<view class="f2">充值记录</view>
				<text class="f3"></text>
				<image src="/static/img/arrowright.png" class="f4"></image>
			</view>
		</view>
		<view class="list">
			<view class="item" @tap="goto" data-url="moneylog">
				<view class="f1"><image :src="pre_url+'/static/img/moneylog.png'"></image></view>
				<view class="f2">{{t('余额')}}明细</view>
				<text class="f3"></text>
				<image src="/static/img/arrowright.png" class="f4"></image>
			</view>
		</view>
		<view class="list" v-if="show && show.scorelog">
			<view class="item" @tap="goto" data-url="scorelog">
				<view class="f1"><image :src="pre_url+'/static/img/moneylog.png'"></image></view>
				<view class="f2">{{t('积分')}}明细</view>
				<text class="f3"></text>
				<image src="/static/img/arrowright.png" class="f4"></image>
			</view>
		</view>
        <view class="list"  v-if="showyuebao_moneylog">
        	<view class="item" @tap="goto" data-url="yuebaolog">
        		<view class="f1"><image :src="pre_url+'/static/img/moneylog.png'"></image></view>
        		<view class="f2">{{t('余额宝')}}明细</view>
        		<text class="f3"></text>
        		<image src="/static/img/arrowright.png" class="f4"></image>
        	</view>
        </view>
		<view class="list">
			<view class="item" @tap="goto" data-url="commissionlog">
				<view class="f1"><image :src="pre_url+'/static/img/commissionlog.png'"></image></view>
				<view class="f2">{{t('佣金')}}明细</view>
				<text class="f3"></text>
				<image src="/static/img/arrowright.png" class="f4"></image>
			</view>
		</view>
		<view class="list">
			<view class="item" @tap="goto" data-url="withdrawlog">
				<view class="f1"><image :src="pre_url+'/static/img/withdrawlog.png'"></image></view>
				<view class="f2">{{t('余额')}}提现列表</view>
				<text class="f3"></text>
				<image src="/static/img/arrowright.png" class="f4"></image>
			</view>
		</view>
        <view class="list"  v-if="showyuebao_withdrawlog">
        	<view class="item" @tap="goto" data-url="yuebaowithdrawlog">
        		<view class="f1"><image :src="pre_url+'/static/img/withdrawlog.png'"></image></view>
        		<view class="f2">{{t('余额宝')}}提现列表</view>
        		<text class="f3"></text>
        		<image src="/static/img/arrowright.png" class="f4"></image>
        	</view>
        </view>
		<view class="list">
			<view class="item" @tap="goto" data-url="comwithdrawlog">
				<view class="f1"><image :src="pre_url+'/static/img/comwithdrawlog.png'"></image></view>
				<view class="f2">{{t('佣金')}}提现列表</view>
				<text class="f3"></text>
				<image src="/static/img/arrowright.png" class="f4"></image>
			</view>
		</view>
	</view>
	</block>
	<block v-if="bid!=0">
	<view class="listcontent">
		<view class="list" v-if="show && show.scorelog">
			<view class="item" @tap="goto" data-url="scorelog">
				<view class="f1"><image :src="pre_url+'/static/img/moneylog.png'"></image></view>
				<view class="f2">{{t('积分')}}明细</view>
				<text class="f3"></text>
				<image src="/static/img/arrowright.png" class="f4"></image>
			</view>
		</view>
		<view class="list" v-if="showbscore">
			<view class="item" @tap="goto" data-url="bscorelog">
				<view class="f1"><image :src="pre_url+'/static/img/moneylog.png'"></image></view>
				<view class="f2">{{t('积分')}}明细</view>
				<text class="f3">剩余{{t('积分')}}{{info.score}}</text>
				<image src="/static/img/arrowright.png" class="f4"></image>
			</view>
		</view>
		<view class="list">
			<view class="item" @tap="goto" data-url="bmoneylog">
				<view class="f1"><image :src="pre_url+'/static/img/moneylog.png'"></image></view>
				<view class="f2">余额明细</view>
				<text class="f3"></text>
				<image src="/static/img/arrowright.png" class="f4"></image>
			</view>
		</view>
		<view class="list">
			<view class="item" @tap="goto" data-url="bwithdraw">
				<view class="f1"><image :src="pre_url+'/static/img/withdrawlog.png'"></image></view>
				<view class="f2">余额提现</view>
				<text class="f3"></text>
				<image src="/static/img/arrowright.png" class="f4"></image>
			</view>
		</view>
		<view class="list">
			<view class="item" @tap="goto" data-url="bwithdrawlog">
				<view class="f1"><image :src="pre_url+'/static/img/comwithdrawlog.png'"></image></view>
				<view class="f2">提现记录</view>
				<text class="f3"></text>
				<image src="/static/img/arrowright.png" class="f4"></image>
			</view>
		</view>
	</view>
	</block>
	<block v-if="showmdmoney">
	<view class="listcontent">
		<view class="list">
			<view class="item" @tap="goto" data-url="mdmoneylog">
				<view class="f1"><image :src="pre_url+'/static/img/moneylog.png'"></image></view>
				<view class="f2">门店余额明细</view>
				<text class="f3"></text>
				<image src="/static/img/arrowright.png" class="f4"></image>
			</view>
		</view>
		<view class="list">
			<view class="item" @tap="goto" data-url="mdwithdraw">
				<view class="f1"><image :src="pre_url+'/static/img/withdrawlog.png'"></image></view>
				<view class="f2">门店余额提现</view>
				<text class="f3"></text>
				<image src="/static/img/arrowright.png" class="f4"></image>
			</view>
		</view>
		<view class="list">
			<view class="item" @tap="goto" data-url="mdwithdrawlog">
				<view class="f1"><image :src="pre_url+'/static/img/comwithdrawlog.png'"></image></view>
				<view class="f2">门店提现记录</view>
				<text class="f3"></text>
				<image src="/static/img/arrowright.png" class="f4"></image>
			</view>
		</view>
	</view>
	</block>

	<block v-if="bid!=0 && showcouponmoney">
	<view class="listcontent">
		<view class="list">
			<view class="item" @tap="goto" data-url="../couponmoney/record">
				<view class="f1"><image :src="pre_url+'/static/img/moneylog.png'"></image></view>
				<view class="f2">补贴券使用明细</view>
				<text class="f3"></text>
				<image src="/static/img/arrowright.png" class="f4"></image>
			</view>
		</view>
		<view class="list">
			<view class="item" @tap="goto" data-url="../couponmoney/withdraw">
				<view class="f1"><image :src="pre_url+'/static/img/withdrawlog.png'"></image></view>
				<view class="f2">补贴券提现</view>
				<text class="f3"></text>
				<image src="/static/img/arrowright.png" class="f4"></image>
			</view>
		</view>
		<view class="list">
			<view class="item" @tap="goto" data-url="../couponmoney/withdrawlog">
				<view class="f1"><image :src="pre_url+'/static/img/comwithdrawlog.png'"></image></view>
				<view class="f2">补贴券提现记录</view>
				<text class="f3"></text>
				<image src="/static/img/arrowright.png" class="f4"></image>
			</view>
		</view>
	</view>
	</block>

	<view class="tabbar">
		<view class="tabbar-bot"></view>
		<view class="tabbar-bar" style="background-color:#ffffff;">
			<view @tap="goto" data-url="../member/index" data-opentype="reLaunch" class="tabbar-item" v-if="auth_data.member">
				<view class="tabbar-image-box">
					<image class="tabbar-icon" :src="pre_url+'/static/img/admin/member.png'"></image>
				</view>
				<view class="tabbar-text">{{t('会员')}}</view>
			</view>
			<view @tap="goto" data-url="../kefu/index" data-opentype="reLaunch" class="tabbar-item" v-if="auth_data.zixun">
				<view class="tabbar-image-box">
					<image class="tabbar-icon" :src="pre_url+'/static/img/admin/zixun.png'"></image>
				</view>
				<view class="tabbar-text">咨询</view>
			</view>
			<view @tap="goto" data-url="../finance/index" data-opentype="reLaunch" class="tabbar-item" v-if="auth_data.finance">
				<view class="tabbar-image-box">
					<image class="tabbar-icon" :src="pre_url+'/static/img/admin/finance2.png'"></image>
				</view>
				<view class="tabbar-text active">财务</view>
			</view>
			<view @tap="goto" data-url="../index/index" data-opentype="reLaunch" class="tabbar-item">
				<view class="tabbar-image-box">
					<image class="tabbar-icon" :src="pre_url+'/static/img/admin/my.png'"></image>
				</view>
				<view class="tabbar-text">我的</view>
			</view>
		</view>
	</view>
	<popmsg ref="popmsg"></popmsg>
	</block>
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
			pre_url:app.globalData.pre_url,
			
			bid:0,
			showmdmoney:0,
      info: {},
      auth_data: {},
      showyuebao_moneylog:false,
      showyuebao_withdrawlog:false,
			showbscore:false,
			showcouponmoney:false,
			show:{}
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
		getdata:function(){
			var that = this
			that.loading = true;
			app.post('ApiAdminFinance/index', {}, function (res) {
				that.loading = false;
				if (res.status == 0) {
					app.alert(res.msg);
					return;
				}
				that.info = res.info;
				that.bid = res.bid;
				that.showmdmoney = res.showmdmoney || 0;
				that.showbscore = res.showbscore || false;
				that.showcouponmoney = res.showcouponmoney || false;
				that.auth_data = res.auth_data;
				that.showyuebao_moneylog    = res.showyuebao_moneylog;
				that.showyuebao_withdrawlog = res.showyuebao_withdrawlog;
				that.show = res.show;
				that.loaded();
			});
		}
  }
};
</script>
<style>
@import "../common.css";
.surverycontent{width: 100%;padding:0 30rpx;display:flex;flex-wrap:wrap;padding-top:20rpx;}
.surverycontent .item{width:49%;background:#fff;margin-bottom:16rpx;padding:10rpx 20rpx;display:flex;flex-direction:column;border-radius:16rpx}
.surverycontent .item:nth-child(odd){margin-right:2%}
.surverycontent .item .t1{width: 100%;color: #222;font-size:28rpx;height: 50rpx;line-height: 50rpx;overflow: hidden;}
.surverycontent .item .t2{width: 100%;color: #FC5648;font-size:44rpx;font-weight:bold;line-height: 70rpx;overflow: hidden;overflow-wrap: break-word;}
.surverycontent .item .t3{width: 100%;color: #999;font-size:28rpx;line-height: 44rpx;overflow: hidden;}
.surverycontent .item .t3 .x2{color:#444;font-weight:bold}
.surverycontent .tips{width: 100%;padding: 30rpx 20rpx;color: #999;}

.listcontent{width: 100%;padding:0 30rpx;}
.list{ width: 100%;background: #fff;margin-top:20rpx;padding:0 20rpx;font-size:30rpx;margin-bottom:20rpx;border-radius:16rpx}
.list .item{ height:100rpx;display:flex;align-items:center;border-bottom:1px solid #eee}
.list .item:last-child{border-bottom:0;margin-bottom:20rpx}
.list .f1{width:50rpx;height:50rpx;line-height:50rpx;display:flex;align-items:center}
.list .f1 image{ width:44rpx;height:44rpx;}
.list .f1 span{ width:40rpx;height:40rpx;font-size:40rpx}
.list .f2{color:#222}
.list .f3{ color: #666;text-align:right;flex:1}
.list .f4{ width: 40rpx; height: 40rpx;}
</style>