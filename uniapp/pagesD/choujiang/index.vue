<template>
	<view class="container">
		<block v-if="isload">
			<view class="banner-container">
				<image :src="set.banner" mode="widthFix" class="bg-image"></image>
				<image :src="set.btpic" class="front-image" mode="widthFix" v-if="set.btpic"></image>
			</view>
			
			<view class="main-wrapper">
				<view class="content-box" v-if="set.drawing_type == 0"  :style="'background-color: '+set.bgcolor">
					<!-- 倒计时 -->
					<view class="countdown-section" :style="'background-color: rgba('+set.theme_color_rgb+',0.3)'">
						<text class="countdown-label">距离开奖还有</text>
						<text class="countdown-time" :style="'color: '+set.theme_color">{{ countdownText }}</text>
					</view>
				</view>
				<dd-tab :itemdata="['待开奖','已开奖','往期中奖']" :itemst="['0','1','2']" :st="st" @changetab="changetab" ></dd-tab>
				<view class="order-container" :style="'background-color: '+set.bgcolor">
					<view v-if="datalist.length > 0" class="order-list">
						<view v-for="(order, index) in datalist" :key="index" class="order-item" :class="{ 'award-item': st == '1' }" @tap="goto" :data-url="'details?id='+order.id">
							<block v-if="st ==0 || st == 1">
								<view class="order-info">
									<view class="field" v-if="st == '1'">
										<text class="label">抽奖结果：</text>
										<text :class="{ 'red': order.status == 2 }">{{order.award_name}}</text>
									</view>
									<view class="field"><text class="label">订单号：</text><text>{{ order.ordernum }}</text></view>
									<view class="field"><text class="label">参与时间：</text><text>{{ order.createtime }}</text></view>
									<view class="field"><text class="label">抽奖码：</text><text>{{ order.code }}</text></view>
									<view class="field" v-if="order.award_type > 0">
										<text class="label">状态：</text>
										<text v-if="order.status == 2">未领取</text>
										<text v-if="order.status == 3">已领取</text>
									</view>
									<view class="field" v-if="order.award_type == 3" @tap="goto" data-url="/pagesExt/coupon/mycoupon">
										<text style="color: #949494;">前往我的优惠券查看</text>
									</view>
								</view>
								<view v-if="st == '1' && order.status == 2 && order.hexiaoqr" class="qrcode-icon">
									<image :src="order.hexiaoqr" @tap="previewImage"  :data-url="order.hexiaoqr"  mode="aspectFit" class="icon-img"></image>
								</view>
							</block>
							<block v-if="st == 2">
								<view class="order-info" @tap="goto" :data-url="'winnerlist?id='+order.id">
									<view class="field"><text class="label">开奖时间：</text><text>{{ order.createtime }}</text></view>
									<view class="field"><text class="label">中奖金额：</text><text>￥{{ order.win_money }}</text></view>
								</view>
							</block>
						</view>
					</view>
					<nomore v-if="nomore"></nomore>
					<nodata v-if="nodata" :text="st == '0' ? '暂没有符合抽奖的订单' : '暂无已开奖订单'"  ></nodata>
				</view>
			</view>
		
			<button class="coverguize"  @tap="changemaskrule">活动规则</button>
			<view id="mask-rule" v-if="showmaskrule">
				<view class="box-rule" :style="'background-color: '+set.theme_color">
					<view class="h2">活动规则说明</view>
					<view id="close-rule" @tap="changemaskrule" :style="'background-image:url('+pre_url+'/static/img/dzp/close.png);background-size:100%'">
					</view>
					<view class="con">
						<view class="text">
							<text decode="true" space="true">{{set.guize}}</text>
					</view>
						</view>
				</view>
			</view>
		</block>
		<loading v-if="loading"></loading>
		<dp-tabbar :opt="opt"></dp-tabbar>
		<popmsg ref="popmsg"></popmsg>
		<wxxieyi></wxxieyi>
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
				pre_url:app.globalData.pre_url,
				st: 0,
				set: [],
				countdownText: '',
				pagenum: 1,
				nomore: false,
				nodata: false,
				datalist: [],
				showmaskrule:false,
				winnerRollingText:''
			};
		},

		onLoad: function(opt) {
			var that = this
			this.opt = app.getopts(opt);
			this.getdata();
		},
		onReachBottom: function() {
			if (!this.nodata && !this.nomore) {
				this.pagenum = this.pagenum + 1;
				this.getdatalist(true);
			}
		},
		methods: {
			getdata: function() {
				var that = this;
				that.loading = true;
				app.get('ApiLirunChoujiang/index', {}, function(res) {
					that.loading = false;
					if (res.status == 0) {
						app.alert(res.msg);
						return;
					}
					that.set = res.set;
					that.bgImage = res.set.banner;
					if (res.set && res.set.remaining_seconds) {
						that.startCountdown(res.set.remaining_seconds);
					}else{
						that.countdownText = '今日已开奖';
					}
					that.getdatalist();
					that.loaded();
					that.loading = false;
				});
			},
			getdatalist: function(loadmore) {
				if (!loadmore) {
					this.pagenum = 1;
					this.datalist = [];
				}
				var that = this;
				var pagenum = that.pagenum;
				var st = that.st;
				that.loading = true;
				that.nomore = false;
				that.nodata = false;
				app.post('ApiLirunChoujiang/choujiangRecord', {st: st,pagenum: pagenum}, function(res) {
					that.loading = false;
					if (res.status == 1) {
						var data = res.data;
						if (pagenum == 1) {
							that.datalist = data;
							if (data.length == 0) {
								that.nodata = true;
							}
							that.loaded();
						} else {
							if (data.length == 0) {
								that.nomore = true;
							} else {
								var datalist = that.datalist;
								var newdata = datalist.concat(data);
								that.datalist = newdata;
							}
						}
					} else {
						if (res.msg) {
							app.alert(res.msg, function() {
								if (res.url) app.goto(res.url);
							});
						} else if (res.url) {
							app.goto(res.url);
						} else {
							app.alert('您无查看权限');
						}
					}
				});
			},
			startCountdown(totalSeconds) {
				// 清除已有定时器
				if (this.countdownTimer) {
					clearInterval(this.countdownTimer);
				}

				// 边界处理
				if (isNaN(totalSeconds) || totalSeconds <= 0) {
					this.countdownText = '已开奖';
					return;
				}

				// 立即显示初始值
				this.updateCountdown(totalSeconds);

				// 启动每秒倒计时
				this.countdownTimer = setInterval(() => {
					totalSeconds--;
					if (totalSeconds <= 0) {
						clearInterval(this.countdownTimer);
						this.countdownText = '已开奖';
						this.getdatalist();
						return;
					}
					this.updateCountdown(totalSeconds);
				}, 1000);
			},

			updateCountdown(seconds) {
				const h = Math.floor(seconds / 3600);
				const m = Math.floor((seconds % 3600) / 60);
				const s = seconds % 60;
				this.countdownText = `${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`;
			},

			onUnload() {
				if (this.countdownTimer) {
					clearInterval(this.countdownTimer);
					this.countdownTimer = null;
				}
			},
			changetab: function(st) {
				this.st = st;
				uni.pageScrollTo({
					scrollTop: 0,
					duration: 0
				});
				this.getdatalist()
			},
			changemaskrule: function() {
				this.showmaskrule = !this.showmaskrule;
			},
		}
	};
</script>

<style>
.container{position:relative;width:100%;display:flex;flex-direction:column;min-height:100vh;overflow:hidden}
.banner-container {position: relative;z-index: 1;width: 100%;height: auto; overflow: hidden;}
.bg-image{width:100%;display:block;z-index: 1;}
.front-image {position: absolute;left: 50%;bottom: 100rpx;transform: translateX(-50%);z-index: 2;width: 100%;max-width: 600rpx;height: auto;display: block;}
.main-wrapper{flex:1;display:flex;flex-direction:column;min-height:0}
.main-wrapper .content-box{position:relative;z-index:3;background-color:#fff;border-top-left-radius:15rpx;border-top-right-radius:15rpx;margin-top:-30rpx;padding:20rpx}
.main-wrapper .content-box .countdown-section{padding:20rpx;text-align:center;}
.main-wrapper .content-box .countdown-label{display:block;font-size:36rpx;color:#333;font-weight:bold;margin-bottom:10rpx}
.main-wrapper .content-box .countdown-time{display:block;font-size:64rpx;font-weight:bold;color:#ff6b6b;letter-spacing:6rpx;text-shadow:0 2rpx 4rpx rgba(0,0,0,0.1)}
.main-wrapper .order-container{flex:1;min-height:0;padding:10rpx;display:flex;flex-direction:column}
.main-wrapper .order-container .order-item{display:flex;flex-direction:row;align-items:center;justify-content:space-between;padding:25rpx;background-color:#ffffff;border-radius:15rpx;margin-bottom:20rpx;font-size:28rpx;line-height:1.4}
.order-item .order-info{flex:1;min-width:0}
.order-item .field{display:flex;margin-bottom:8rpx}
.order-item .field:last-child{margin-bottom:0}
.order-item .label{font-weight:bold;min-width:120rpx;color:#333}
.order-item text{display:block;margin:0}
.order-item .qrcode-icon{width:150rpx;height:150rpx;margin-left:20rpx;flex-shrink:0;display:flex;align-items:center;justify-content:center;background-color:#f8f8f8;border-radius:12rpx}
.main-wrapper .order-container .order-item text{display:block;margin:0}
.award-item{border-left:6rpx solid #ff6b6b}
.claim-btn{background-color:#ff6b6b;color:white;font-size:28rpx;padding:10rpx 20rpx;border-radius:10rpx;margin-top:10rpx;width:auto;display:inline-block}
.order-item .field{display:flex}
.order-item .label{font-weight:bold;min-width:150rpx}
.red{color:red}
.coverguize{position:absolute;z-index:99999;cursor:pointer;display:flex;flex-direction:column;align-items:center;justify-content:center;overflow:hidden;z-index:9999;top:150rpx;right:0;color:#fff;background-color:rgba(17,17,17,0.3);font-size:26rpx;border-radius:30rpx 0px 0px 30rpx;width:55rpx;padding:0.3375rem 0.275rem;word-break:break-all;font-size:26rpx;height:auto;line-height:35rpx}
#mask-rule,#mask{position:fixed;left:0;top:0;z-index:99999;width:100%;height:100%;background-color:rgba(0,0,0,0.85)}
#mask-rule .box-rule{position:relative;margin:30% auto;padding-top:40rpx;width:90%;height:675rpx;border-radius:20rpx;background-color:#f58d40}
#mask-rule .box-rule .star{position:absolute;left:50%;top:-100rpx;margin-left:-130rpx;width:259rpx;height:87rpx}
#mask-rule .box-rule .h2{width:100%;text-align:center;line-height:34rpx;font-size:34rpx;font-weight:normal;color:#fff}
#mask-rule #close-rule{position:absolute;right:34rpx;top:38rpx;width:40rpx;height:40rpx}
#mask-rule .con{overflow:auto;position:relative;margin:40rpx auto;padding-right:15rpx;width:580rpx;height:82%;line-height:48rpx;font-size:26rpx;color:#fff}
#mask-rule .con .text{position:absolute;top:0;left:0;width:inherit;height:auto}
.winner-notice {
	width: 100%;
	padding: 8rpx 0;
	overflow: hidden;
	white-space: nowrap;
	position: relative;
	z-index: 10;
}

.notice-inner {
	display: inline-block;
	animation: scrollWinner 20s linear infinite;
}

.notice-text {
	font-size: 24rpx;
	line-height: 1.4;
	display: inline-block;
}

@keyframes scrollWinner {
	0% {
		transform: translateX(0);
	}
	100% {
		transform: translateX(-50%);
	}
}
</style>