<template>
<view class="container">
	<block v-if="isload">
		<!-- 封面区域 -->
		<view class="cover-section">
			<image v-if="detail.generation_type == 1" :src="detail.cover_image || '/static/img/placeholder.png'" class="cover-image" mode="aspectFill"></image>
			<video v-else :src="detail.cover_image" class="cover-video" :controls="false" :show-play-btn="true" :muted="true" objectFit="cover"></video>
		</view>
		
		<!-- 模板信息 -->
		<view class="info-section">
			<view class="title-row">
				<text class="template-name">{{detail.template_name}}</text>
				<text class="use-count">已用{{detail.use_count || 0}}次</text>
			</view>
			<view class="desc" v-if="detail.description">{{detail.description}}</view>
			
			<!-- 价格信息 -->
			<view class="price-row">
				<view class="price-info">
					<text class="price-label">价格：</text>
					<text class="price score-price" v-if="detail.score_pay_enabled">{{detail.price_in_score}} 积分</text>
					<text class="price" v-else>¥{{detail.price}}</text>
					<text class="price-unit">{{detail.price_unit_text}}</text>
				</view>
				<view v-if="detail.is_member_price" class="member-tag">会员价</view>
			</view>
			
			<!-- 会员价格对比 -->
			<view class="member-prices" v-if="detail.all_prices && detail.all_prices.length > 0">
				<view class="member-price-item" v-for="(item, idx) in detail.all_prices" :key="idx">
					<text class="level-name">{{item.level_name}}：</text>
					<text class="level-price">¥{{item.price}}</text>
				</view>
			</view>
		</view>
		
		<!-- 底部操作栏 -->
		<view class="bottom-bar">
			<view class="price-display">
				<text class="total-label">合计：</text>
				<text class="total-price score-price" v-if="detail.score_pay_enabled">{{detail.price_in_score}} 积分</text>
				<text class="total-price" v-else>¥{{detail.price}}</text>
			</view>
			<view class="btn-primary" @tap="goCreate">立即使用</view>
		</view>
		<view style="height: 120rpx;"></view>
	</block>
	
	<loading v-if="loading"></loading>
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
			detail: null
		};
	},
	
	onLoad(opt) {
		this.opt = app.getopts(opt);
		this.getdata();
	},
	
	onPullDownRefresh() {
		this.getdata();
	},
	
	methods: {
		getdata() {
			var that = this;
			that.loading = true;
			
			app.get('ApiAivideo/scene_template_detail', { 
				template_id: that.opt.id 
			}, function(res) {
				that.loading = false;
				that.isload = true;
				uni.stopPullDownRefresh();
				
				if (res.status == 1) {
					that.detail = res.data;
					// 设置页面标题
					uni.setNavigationBarTitle({
						title: res.data.template_name || '模板详情'
					});
				} else {
					app.alert(res.msg);
				}
			});
		},
		
		goCreate() {
			var that = this;
			if (!that.detail) return;
			
			uni.navigateTo({
				url: '/pagesZ/generation/create?id=' + that.detail.id + '&type=' + (that.opt.type || that.detail.generation_type || 1)
			});
		}
	}
};
</script>

<style>
.container { background: #f5f5f5; min-height: 100vh; }

.cover-section { width: 100%; height: 500rpx; background: #000; }
.cover-image { width: 100%; height: 100%; }
.cover-video { width: 100%; height: 100%; }

.info-section { background: #fff; margin: 20rpx; border-radius: 16rpx; padding: 30rpx; }
.title-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16rpx; }
.template-name { font-size: 36rpx; font-weight: bold; color: #333; flex: 1; }
.use-count { font-size: 24rpx; color: #999; flex-shrink: 0; }
.desc { font-size: 28rpx; color: #666; line-height: 1.6; margin-bottom: 20rpx; }

.price-row { display: flex; align-items: center; margin-top: 20rpx; padding-top: 20rpx; border-top: 1px solid #f5f5f5; }
.price-info { flex: 1; display: flex; align-items: baseline; }
.price-label { font-size: 28rpx; color: #666; }
.price { font-size: 40rpx; color: #FF6B00; font-weight: bold; }
.price-unit { font-size: 24rpx; color: #999; margin-left: 8rpx; }
.member-tag { font-size: 22rpx; color: #fff; background: linear-gradient(135deg, #FF9800, #FFB74D); padding: 4rpx 12rpx; border-radius: 20rpx; }

.member-prices { display: flex; flex-wrap: wrap; gap: 16rpx; margin-top: 20rpx; padding-top: 16rpx; border-top: 1px dashed #eee; }
.member-price-item { display: flex; align-items: center; background: #f9f9f9; padding: 8rpx 16rpx; border-radius: 8rpx; }
.level-name { font-size: 24rpx; color: #666; }
.level-price { font-size: 26rpx; color: #FF6B00; font-weight: bold; }

.bottom-bar { position: fixed; bottom: 0; left: 0; right: 0; background: #fff; display: flex; align-items: center; padding: 20rpx 30rpx; box-shadow: 0 -2rpx 10rpx rgba(0,0,0,0.05); padding-bottom: calc(20rpx + env(safe-area-inset-bottom)); }
.price-display { flex: 1; }
.total-label { font-size: 28rpx; color: #666; }
.total-price { font-size: 40rpx; color: #FF6B00; font-weight: bold; }
.btn-primary { background: linear-gradient(135deg, #FF6B00, #FF9500); color: #fff; font-size: 32rpx; font-weight: bold; padding: 24rpx 60rpx; border-radius: 44rpx; }

/* 积分价格 */
.score-price { color: #FF6B00; }
</style>
