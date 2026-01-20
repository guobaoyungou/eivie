<template>
	<view class="content-page">
		<view class="top-bigview" :style="{backgroundImage:'url('+ pre_url +'/static/img/fenhongbg.png'+')'}"></view>
		<view class="content-view">
			<view class="bonusPool-view" :style="{backgroundImage:'url('+ pre_url +'/static/img/fenhongzong.png'+')'}">
				<view class="poll-price">{{pool_num}}</view>
				<view class="tisp-text">奖金池总金额（元）</view>
			</view>
			<block v-for="(item,index) in level_arr">
				<view class="membership-level-view">
					<view class="level-view">{{item.name}}</view>
					<view class="flex-aw fenhong-price-view">
						<view class="flex-col options-price">
							<view class="title-text">本月分红金额</view>
							<view class="price-text">{{item.level_prize}}</view>
						</view>
						<view class="flex-col options-price">
							<view class="title-text">上月分红金额</view>
							<view class="price-text">{{item.last_month_prize}}</view>
						</view>
					</view>
					<view class="flex-aw bottom-view">
						<view class="flex-col bottom-options">
							<view>分红人数</view>
							<view class="bto-text">{{item.member_count}}</view>
						</view>
						<view class="flex-col bottom-options">
							<view>人均分红</view>
							<view class="bto-text">{{item.avg_prize}}</view>
						</view>
					</view>
				</view>
			</block>
		</view>
	</view>
</template>

<script>
	var app = getApp();
	export default{
		data(){
			return{
				pre_url:app.globalData.pre_url,
				pool_num:0,
				level_arr:[]
			}
		},
		onLoad: function (opt) {
			this.opt = app.getopts(opt);
			this.getdata();
		},
		methods:{
			getdata: function () {
				var that = this;
				app.post('ApiAgent/prize_pool', {}, function (res) {
					that.loading = false;
					that.pool_num = res.pool_num;
					that.level_arr = res.level_arr;
					uni.setNavigationBarTitle({
						title: res.title
					});
					that.loaded();
					
				});
			},
		}
	}
</script>

<style>
	page{background: #ffebe0;}
	.content-page{width: 100%;position: relative;}
	.top-bigview{width: 100%;height:45vh;background-position: center center;background-repeat: no-repeat;background-size: cover;display: flex;align-items: center;justify-content: center;}
	.bonusPool-view{width: 88%;height:800rpx;background-position: center center;background-repeat: no-repeat;background-size: auto;position: relative;display: flex;
	flex-direction: column;align-items: center;}
	.bonusPool-view .poll-price{font-weight: bold;font-size: 88rpx;color: #FF5D1D;margin-top: 30%;}
	.bonusPool-view .tisp-text{color: #9B4C2D;font-size: 40rpx;font-weight: bold;margin-top: 40%;}
	.content-view{width: 100%;position: absolute;top: 60rpx;display: flex;flex-direction: column;align-items: center;}
	.membership-level-view{width: 88%;height:auto;border-radius: 16px;background: #fff;margin-top: 40rpx;position: relative;background: linear-gradient(24deg, rgba(253, 236, 205, 0.8) 0%, rgba(255, 253, 247,1) 100%);}
	.level-view{position: absolute;top: 0px;width: 320rpx;height: 64rpx;border-radius: 0px 0px 16px 16px;left: 50%;margin-left: -160rpx;
background: linear-gradient(180deg, rgba(254, 137, 53, 0.8) 0%, #FE8235 100%);box-shadow: 0px 4px 0px 0px rgba(171, 80, 24, 0.1);text-align: center;line-height: 64rpx;
font-size: 28rpx;color: #fff;font-weight: bold;}
.fenhong-price-view{width: 100%;padding-top: 100rpx;}
.fenhong-price-view .options-price{align-items: center;}
.fenhong-price-view .options-price .title-text{font-size: 28rpx;color: #333;}
.fenhong-price-view .options-price .price-text{font-size: 36rpx;color: #d42323;margin-top: 10rpx;}
.bottom-view{width: 100%;margin-top: 10rpx;justify-content: space-between;}
.bottom-view .bottom-options{align-items: center;padding: 20rpx 30rpx;width: 40%;}
.bottom-options .bto-text{font-size: 28rpx;font-weight: bold;color: #d42323;margin-top: 5rpx;}
</style>