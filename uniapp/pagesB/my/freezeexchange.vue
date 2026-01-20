<template>
	<view class="container">
		<block v-if="isload">
			<form @submit="formSubmit">
				<view class="mymoney" :style="{background:t('color1')}">
					<view class="f1">我的可兑换{{t('冻结账户')}}</view>
					<view class="f2">{{freeze_credit}}</view>
					<view class="f3" @tap="goto" data-url="exchangelog"><text>兑换记录</text><text class="iconfont iconjiantou"
							style="font-size:20rpx"></text></view>
				</view>
				<block v-if="wallet.length>0">
					<view class="content2">
						<view class="item2">
							<view class="f1">兑换数量</view>
						</view>
						<view class="item3">
							<view class="f1">￥</view>
							<view class="f2"><input class="input" type="digit" name="money" value="" placeholder="请输入兑换数量"
									placeholder-style="color:#999;font-size:40rpx" @input="moneyinput"></input></view>
						</view>
						<view class="tips-box">
							<view class="tips">
								兑换需消耗{{t('积分')}},1{{t('积分')}}={{sysset.score2money}}{{t('冻结账户')}}
							</view>
						</view>
					</view>
					<view class="withdrawtype">
						<view class="f1">选择兑换方式：</view>
						<view class="f2">
							<view class="item" v-for="item in wallet" @tap.stop="changeradio" :data-paytype="item.type">
								<view class="t1">
									<image v-if="item.img != ''" class="img" :src="item.img" />{{item.title}}
								</view>
								<view class="radio" :style="paytype==item.type ? 'background:'+t('color1')+';border:0' :''">
									<image class="radio-img" :src="pre_url+'/static/img/checkd.png'" />
								</view>
							</view>
						</view>
					</view>
					<button class="btn" :style="{background:t('color1')}" @tap="formSubmit">立即兑换</button>
				</block>
				<block v-else>
					<view style="text-align: center; margin-top: 100rpx; font-size: 38rpx;">抱歉，暂不支持兑换</view>
				</block>

			</form>
			<view class="withdraw_desc" v-if="sysset.withdraw_desc">
				<view class="title">说明</view>
				<text>{{sysset.withdraw_desc}}</text>
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
				opt: {},
				loading: false,
				isload: false,
				menuindex: -1,
				userinfo: [],
				money: 0,
				sysset: false,
				paytype: 0,
				bid: 0,
				wallet: [],
				freeze_credit: 0,
				pre_url: app.globalData.pre_url,
			};
		},

		onLoad: function(opt) {
			this.opt = app.getopts(opt);
			if (this.opt && this.opt.bid) {
				this.bid = this.opt.bid || 0;
			}
			var that = this;
			this.getdata();
		},
		onPullDownRefresh: function() {
			this.getdata();
		},
		methods: {
			getdata: function() {
				var that = this;
				that.loading = true;
				app.get('ApiQueueFree/exchangeInfo', {
					bid: that.bid
				}, function(res) {
					that.loading = false;
					uni.setNavigationBarTitle({
						title: that.t('冻结账户') + '兑换'
					});
					var sysset = res.sysset;
					that.sysset = sysset;
					that.freeze_credit = res.freeze_credit;
					that.wallet = res.wallet;
					that.loaded();
				});
			},

			moneyinput: function(e) {
				var usermoney = parseFloat(this.freeze_credit);
				var money = parseFloat(e.detail.value);
				if (money < 0) {
					app.error('必须大于0');
				} else if (money > usermoney) {
					app.error('可提现' + this.t('冻结账户') + '不足');
				}
				this.money = money;
			},
			changeradio: function(e) {
				var that = this;
				var paytype = e.currentTarget.dataset.paytype;
				that.paytype = paytype;
			},
			formSubmit: function() {

				var that = this;
				var usermoney = parseFloat(this.freeze_credit);
				var money = parseFloat(that.money);
				var paytype = this.paytype;
				if (isNaN(money) || money <= 0) {
					app.error('兑换金额必须大于0');
					return;
				}
				if (money > usermoney) {
					app.error(that.t('冻结账户') + '不足');
					return;
				}

				if (paytype == 0) {
					app.alert('请选择兑换到账方式');
					return;
				}
				app.showLoading('提交中');
				app.post('ApiQueueFree/subExchange', {
					money: money,
					paytype: paytype,
				}, function(data) {
					app.showLoading(false);
					if (data.status == 0) {
						app.error(data.msg);
						return;
					} else {
						app.success(data.msg);
						setTimeout(function() {
							app.goto('exchangelog');
						}, 1000);
					}
				});
			}
		}
	};
</script>
<style>
	.container {
		display: flex;
		flex-direction: column;
		padding-bottom: 40rpx;
	}

	.mymoney {
		width: 94%;
		margin: 20rpx 3%;
		border-radius: 10rpx 10rpx 10rpx 10rpx;
		position: relative;
		display: flex;
		flex-direction: column;
		padding: 70rpx 0
	}

	.mymoney .f1 {
		margin: 0 0 0 60rpx;
		color: rgba(255, 255, 255, 0.8);
		font-size: 24rpx;
	}

	.mymoney .f2 {
		margin: 20rpx 0 0 60rpx;
		color: #fff;
		font-size: 64rpx;
		font-weight: bold
	}

	.mymoney .f3 {
		height: 56rpx;
		padding: 0 10rpx 0 20rpx;
		border-radius: 28rpx 0px 0px 28rpx;
		background: rgba(255, 255, 255, 0.2);
		font-size: 20rpx;
		font-weight: bold;
		color: #fff;
		display: flex;
		align-items: center;
		position: absolute;
		top: 94rpx;
		right: 0
	}

	.content2 {
		width: 94%;
		margin: 10rpx 3%;
		border-radius: 10rpx;
		display: flex;
		flex-direction: column;
		background: #fff
	}

	.content2 .item1 {
		display: flex;
		width: 100%;
		border-bottom: 1px solid #F0F0F0;
		padding: 0 30rpx
	}

	.content2 .item1 .f1 {
		flex: 1;
		font-size: 32rpx;
		color: #333333;
		font-weight: bold;
		height: 120rpx;
		line-height: 120rpx
	}

	.content2 .item1 .f2 {
		color: #FC4343;
		font-size: 44rpx;
		font-weight: bold;
		height: 120rpx;
		line-height: 120rpx
	}

	.content2 .item2 {
		display: flex;
		width: 100%;
		padding: 0 30rpx;
		padding-top: 10rpx
	}

	.content2 .item2 .f1 {
		height: 80rpx;
		line-height: 80rpx;
		color: #999999;
		font-size: 28rpx
	}

	.content2 .item3 {
		display: flex;
		width: 100%;
		padding: 0 30rpx;
		padding-bottom: 20rpx
	}

	.content2 .item3 .f1 {
		height: 100rpx;
		line-height: 100rpx;
		font-size: 60rpx;
		color: #333333;
		font-weight: bold;
		margin-right: 20rpx
	}

	.content2 .item3 .f2 {
		display: flex;
		align-items: center;
		font-size: 60rpx;
		color: #333333;
		font-weight: bold
	}

	.content2 .item3 .f2 .input {
		font-size: 60rpx;
		height: 100rpx;
		line-height: 100rpx;
	}

	.content2 .item4 {
		display: flex;
	}

	.withdrawtype {
		width: 94%;
		margin: 20rpx 3%;
		border-radius: 10rpx;
		display: flex;
		flex-direction: column;
		margin-top: 20rpx;
		background: #fff
	}

	.withdrawtype .f1 {
		height: 100rpx;
		line-height: 100rpx;
		padding: 0 30rpx;
		color: #333333;
		font-weight: bold
	}


	.withdrawtype .f2 {
		padding: 0 30rpx
	}

	.withdrawtype .f2 .item {
		border-bottom: 1px solid #f5f5f5;
		height: 100rpx;
		display: flex;
		align-items: center
	}

	.withdrawtype .f2 .item:last-child {
		border-bottom: 0
	}

	.withdrawtype .f2 .item .t1 {
		flex: 1;
		display: flex;
		align-items: center;
		color: #333
	}

	.withdrawtype .f2 .item .t1 .img {
		width: 44rpx;
		height: 44rpx;
		margin-right: 40rpx
	}

	.withdrawtype .f2 .item .radio {
		flex-shrink: 0;
		width: 36rpx;
		height: 36rpx;
		background: #FFFFFF;
		border: 3rpx solid #BFBFBF;
		border-radius: 50%;
		margin-right: 10rpx
	}

	.withdrawtype .f2 .item .radio .radio-img {
		width: 100%;
		height: 100%
	}

	.btn {
		height: 100rpx;
		line-height: 100rpx;
		width: 90%;
		margin: 0 auto;
		border-radius: 50rpx;
		margin-top: 30rpx;
		color: #fff;
		font-size: 30rpx;
		font-weight: bold
	}

	.textbtn {
		width: 100%;
		margin-top: 40rpx;
		text-align: center;
		color: #999;
		display: flex;
		align-items: center;
		justify-content: center
	}

	.textbtn image {
		width: 30rpx;
		height: 30rpx
	}

	.withdraw_desc {
		padding: 30rpx;
	}

	.withdraw_desc .title {
		font-size: 30rpx;
		color: #5E5E5E;
		font-weight: bold;
		padding: 10rpx 0;
	}

	.withdraw_desc text {
		width: 100%;
		line-height: 46rpx;
		font-size: 24rpx;
		color: #222222;
	}

	.tips-box {
		width: 94%;
		margin: 0 3%;
		padding: 20rpx 0;
		border-top: 1px solid #F0F0F0;
	}

	.tips {
		color: #8C8C8C;
		font-size: 28rpx;
		line-height: 50rpx;
	}

	.banklist {
		padding: 0 20rpx 20rpx;
		margin-left: 10rpx;
		display: flex;
		width: 100%;
	}

	.banklist .t2 {
		line-height: 90rpx;
		width: 80rpx;
		text-align: right;
	}
</style>