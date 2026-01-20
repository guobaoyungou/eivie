<template>
<view>
	<block v-if="isload">
		<view class="banner" :style="{background:'linear-gradient(180deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0) 100%)'}">
		</view>
		<view class="user">
			<image :src="userinfo.headimg" background-size="cover"/>
			<view class="info" v-if="set && set.parent_show == 1">
				<view>
					<view class="nickname">{{userinfo.nickname}}</view>
					<view>{{t('推荐人')}}：{{userinfo.pid > 0 ? userinfo.pnickname : '无'}}</view>
				</view>
			</view>
			<view class="info" v-else>
				 <text class="nickname">{{userinfo.nickname}}</text>
			</view>
		</view>
		<view class="contentdata">
			<view class="data">
				<view class="data_title flex-y-center"><image class="data_icon" :src="pre_url+'/static/imgsrc/commission_m1.png'"/>我的业绩</view>
				<view class="data_text">
					可提现业绩(元)
				</view>
				<view class="data_price flex-y-center flex-bt">
					<text>{{userinfo.rechargeyj_money}}</text>
					<view @tap="goto" data-url="withdraw" v-if="rechargeyj_withdraw==1" class="data_btn flex-xy-center" :style="{background:'linear-gradient(90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'}">去提现<image :src="pre_url+'/static/imgsrc/commission_dw.png'"/></view>
				</view>
				<view class="data_module flex">
					<view class="flex1">
						<view class="data_lable">已提现业绩(元)</view>
						<view class="data_value">{{txmoney}}</view>
					</view>
				</view>
			</view>
			<view class="list">
				<view class="item" @tap="goto" data-url="/activity/commission/poster">
					<image :src="pre_url+'/static/imgsrc/commission_i4.png'"></image>
					<view class="flex1">
						<view class="title">
							分享海报
						</view>
						<view class="text">
							邀请好友享收益
						</view>
					</view>
				</view>
				<view class="item" @tap="goto" data-url="log" >
					<image :src="pre_url+'/static/imgsrc/commission_i3.png'"></image>
					<view class="flex1">
						<view class="title">
							业绩明细
						</view>
						<view class="text">
							查看明细
						</view>
					</view>
				</view>
			</view>
		</view>
		<view style="width:100%;height:20rpx"></view>
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
			
      hiddenmodalput: true,
      userinfo: [],
      txmoney: 0,
      rechargeyj_withdraw: 0,
      money: 0,
			set:{}
    };
  },

  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		var that = this;
		this.getdata();
  },
	onPullDownRefresh: function () {
		this.getdata();
	},
  methods: {
		getdata: function () {
			var that = this;
			that.loading = true;
			app.get('ApiMy/getRechargeyj', {}, function (res) {
				that.loading = false;
				uni.setNavigationBarColor({
					frontColor: '#ffffff', 
					backgroundColor: that.t('color1') 
				});
				that.userinfo = res.userinfo;
				that.set = res.set;
				that.txmoney     = res.txmoney;
				that.rechargeyj_withdraw = res.rechargeyj_withdraw;
				that.loaded();
			});
		},
    cancel: function () {
      this.hiddenmodalput = true;
    },
  }
};
</script>
<style>
.banner{position: absolute;width: 100%;height: 900rpx;}
.user{ display:flex;width:100%;padding:40rpx 45rpx 0 45rpx;color:#fff;position:relative}
.user image{ width:80rpx;height:80rpx;border-radius:50%;margin-right:20rpx}
.user .info{display:flex;align-items: center;}
.user .info .nickname{font-size:32rpx;font-weight:bold;}
.user .set{ width:70rpx;height:100rpx;line-height:100rpx;font-size:40rpx;text-align:center}
.user .set image{width:50rpx;height:50rpx;border-radius:0}

.contentdata{display:flex;flex-direction:column;width:100%;padding:0 30rpx;position:relative;margin-bottom:20rpx}

.data{background:#fff;padding:30rpx;margin-top:30rpx;border-radius:16rpx}
.data_title{font-size: 28rpx;color: #333;font-weight: bold;}
.data_detail{font-size: 24rpx;font-family: Source Han Sans CN;font-weight: 400;color: #999999;font-weight: normal;}
.data_detail image{height: 24rpx;width: 24rpx;margin-left: 10rpx;}
.data_icon{height: 35rpx;width: 35rpx;margin-right: 15rpx;}
.data_text{font-size: 26;color: #999;margin-top: 60rpx;}
.data_price{font-size: 64rpx;color: #333;font-weight: bold;margin-top: 10rpx;}
.data_btn{height: 56rpx;padding: 0 30rpx;font-size: 24rpx;color: #fff;font-weight: normal;border-radius: 100rpx;}
.data_btn image{height: 24rpx;width: 24rpx;margin-left: 6rpx;}
.data_module{margin-top: 60rpx;}
.data_lable{font-size: 26;color: #999;}
.data_value{font-size: 44rpx;font-weight: bold;color: #333;margin-top: 10rpx;}

.list{ background: #fff;margin-top:30rpx;padding:30rpx;border-radius:16rpx;display: grid;grid-template-columns: repeat(2, 1fr);grid-column-gap: 10rpx;grid-row-gap: 50rpx;}
.list .item{ display:flex;align-items:center;}
.list image{ height: 72rpx;width: 72rpx;margin-right: 20rpx; }
.list .title{font-size: 28rpx;font-family: Source Han Sans CN;font-weight: 500;color: #121212;}
.list .text{font-size: 24rpx;font-family: Source Han Sans CN;font-weight: 400;color: #999999;margin-top: 10rpx;}
</style>