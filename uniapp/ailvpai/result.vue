<template>
<view class="container">
	<!-- 状态头部 -->
	<view class="status-header" :class="statusClass">
		<view class="status-icon">
			<text class="iconfont" :class="statusIcon"></text>
		</view>
		<view class="status-text">{{statusText}}</view>
		<view class="status-desc" v-if="statusDesc">{{statusDesc}}</view>
	</view>
	
	<!-- 模板信息卡片 -->
	<view class="template-card" v-if="result && result.template_name">
		<image :src="result.template_cover_image || '/static/img/placeholder.png'" 
		       class="template-cover" mode="aspectFill" />
		<view class="template-info">
			<text class="template-name">{{result.template_name}}</text>
			<text class="template-model" v-if="result.model_name">使用模型: {{result.model_name}}</text>
			<view class="quantity-badge">
				<text class="quantity-text">生成 {{result.output_quantity || 0}} 张</text>
			</view>
		</view>
	</view>
	
	<!-- 原图预览 -->
	<view class="original-section" v-if="result && result.original_images && result.original_images.length > 0">
		<view class="section-header" @tap="toggleOriginal">
			<text class="section-title">参考原图</text>
			<text class="toggle-icon">{{showOriginal ? '收起' : '展开'}}</text>
		</view>
		<view class="original-grid" v-if="showOriginal">
			<image v-for="(img, idx) in result.original_images" :key="idx"
			       :src="img" class="original-image" mode="aspectFill"
			       @tap="previewOriginal(img)" />
		</view>
	</view>
	
	<!-- 生成结果 -->
	<view class="result-section" v-if="result && result.outputs && result.outputs.length > 0">
		<view class="section-title">生成结果 ({{result.output_quantity || 0}}张)</view>
		<view class="result-grid">
			<block v-for="(output, idx) in result.outputs" :key="idx">
				<view class="result-item" v-if="output.type != 'video'">
					<image 
						:src="output.url" 
						class="result-image" 
						mode="aspectFill"
						@tap="previewImage(output.url)"
						@longpress="saveImage(output.url)"
						@error="onImageError(idx)"
						@load="onImageLoad(idx)"
					></image>
					<!-- 图片加载失败时的占位 -->
					<view v-if="output.loadError" class="image-error-overlay" @tap="retryLoad(idx)">
						<text class="error-icon">⚠</text>
						<text class="error-text">加载失败</text>
						<text class="error-retry">点击重试</text>
					</view>
				</view>
				<view v-else class="result-item video-item">
					<video 
						:src="output.url" 
						class="result-video" 
						controls
						:poster="output.thumbnail"
					></video>
				</view>
			</block>
		</view>
		<view class="save-tip">长按图片保存到相册</view>
	</view>
	
	<!-- 错误信息显示 -->
	<view class="error-section" v-if="result && result.status == 3 && result.error_msg">
		<view class="error-card">
			<text class="error-title">生成失败</text>
			<text class="error-detail">{{result.error_msg}}</text>
			<view class="error-actions">
				<view class="btn-error" @tap="goRefund">申请退款</view>
			</view>
		</view>
	</view>
	
	<!-- 加载中 -->
	<view class="loading-section" v-if="polling && (!result || !result.outputs || result.outputs.length == 0)">
		<view class="loading-animation">
			<view class="loading-circle"></view>
		</view>
		<view class="loading-text">正在为你绘制温柔画面…</view>
		<view class="loading-tip">{{generationType == 1 ? '图片创作大约需要10-30秒，请耐心等待' : '视频创作大约需要1-3分钟，请勿退出页面'}}</view>
	</view>
	
	<!-- 底部操作 -->
	<view class="bottom-bar">
		<view class="btn-secondary" @tap="goOrderList">查看订单</view>
		<view class="btn-primary" @tap="goBack">返回首页</view>
	</view>
	<view style="height: 120rpx;"></view>
	
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
			polling: false,
			pollTimer: null,
			pollCount: 0,
			maxPollCount: 60,
			result: null,
			generationType: 1,
			showOriginal: false
		};
	},
	
	computed: {
		statusClass() {
			if (!this.result) return 'processing';
			var status = this.result.status;
			if (status == 2) return 'success';
			if (status == 3) return 'failed';
			return 'processing';
		},
		statusIcon() {
			if (!this.result) return 'icon-loading';
			var status = this.result.status;
			if (status == 2) return 'icon-chenggong';
			if (status == 3) return 'icon-shibai';
			return 'icon-loading';
		},
		statusText() {
			if (!this.result) return '温柔画面生成中…';
			var status = this.result.status;
			if (status == 2) return '你的治愈作品完成啦';
			if (status == 3) return '生成遇到小问题了';
			if (status == 1) return '温柔画面生成中…';
			return '等待处理';
		},
		statusDesc() {
			if (!this.result) return '正在为你绘制温柔画面…';
			var status = this.result.status;
			if (status == 2) return '长按图片保存到相册';
			if (status == 3) return '生成遇到小问题了，您可以申请退款';
			if (status == 1) return '正在为你绘制温柔画面…';
			return '任务已提交，即将开始处理';
		}
	},
	
	onLoad(opt) {
		this.opt = app.getopts(opt);
		this.generationType = parseInt(this.opt.type) || 1;
		this.startPolling();
	},
	
	onUnload() {
		this.stopPolling();
	},
	
	onShow() {
		if (this.result && this.result.status == 1) {
			this.startPolling();
		}
	},
	
	onHide() {
		this.stopPolling();
	},
	
	methods: {
		onImageError(idx) {
			if (this.result && this.result.outputs && this.result.outputs[idx]) {
				this.$set(this.result.outputs[idx], 'loadError', true);
			}
		},
		onImageLoad(idx) {
			if (this.result && this.result.outputs && this.result.outputs[idx]) {
				this.$set(this.result.outputs[idx], 'loadError', false);
			}
		},
		retryLoad(idx) {
			if (!this.result || !this.result.outputs || !this.result.outputs[idx]) return;
			const url = this.result.outputs[idx].url;
			this.$set(this.result.outputs[idx], 'url', '');
			this.$set(this.result.outputs[idx], 'loadError', false);
			this.$nextTick(() => {
				this.$set(this.result.outputs[idx], 'url', url);
			});
		},
		toggleOriginal() {
			this.showOriginal = !this.showOriginal;
		},
		previewOriginal(url) {
			uni.previewImage({ current: url, urls: this.result.original_images });
		},
		goRefund() {
			if (this.opt.order_id) {
				uni.navigateTo({ url: '/ailvpai/refundApply?id=' + this.opt.order_id });
			}
		},
		startPolling() {
			var that = this;
			if (that.polling) return;
			that.polling = true;
			that.pollCount = 0;
			that.fetchResult();
		},
		
		stopPolling() {
			this.polling = false;
			if (this.pollTimer) {
				clearTimeout(this.pollTimer);
				this.pollTimer = null;
			}
		},
		
		fetchResult() {
			var that = this;
			if (!that.polling) return;
			that.pollCount++;
			if (that.pollCount > that.maxPollCount) {
				that.stopPolling();
				return;
			}
			app.get('ApiAivideo/generation_task_result', {
				order_id: that.opt.order_id,
				record_id: that.opt.record_id
			}, function(res) {
				if (res.status == 1) {
					that.result = res.data;
					that.generationType = res.data.generation_type || that.generationType;
					if (res.data.status == 2 || res.data.status == 3) {
						that.stopPolling();
						return;
					}
				}
				if (that.polling) {
					that.pollTimer = setTimeout(function() {
						that.fetchResult();
					}, 3000);
				}
			});
		},
		
		previewImage(url) {
			var urls = [];
			if (this.result && this.result.outputs) {
				this.result.outputs.forEach(function(item) {
					if (item.type != 'video') {
						urls.push(item.url);
					}
				});
			}
			uni.previewImage({
				current: url,
				urls: urls.length > 0 ? urls : [url]
			});
		},
		
		saveImage(url) {
			uni.showActionSheet({
				itemList: ['保存到相册'],
				success: function(res) {
					if (res.tapIndex == 0) {
						uni.downloadFile({
							url: url,
							success: function(downloadRes) {
								uni.saveImageToPhotosAlbum({
									filePath: downloadRes.tempFilePath,
									success: function() {
										uni.showToast({ title: '已保存到相册 ✅', icon: 'success' });
									},
									fail: function() {
										uni.showToast({ title: '保存失败，请开启相册权限', icon: 'none' });
									}
								});
							}
						});
					}
				}
			});
		},
		
		goOrderList() {
			uni.navigateTo({
				url: '/ailvpai/orderlist'
			});
		},
		
		goBack() {
			uni.switchTab({
				url: '/pages/index/index'
			});
		}
	}
};
</script>

<style>
.container { background: #FDFBFF; min-height: 100vh; }
.status-header { padding: 60rpx 30rpx; text-align: center; color: #fff; }
.status-header.processing { background: linear-gradient(135deg, #91C2FF, #B5D8FE); }
.status-header.success { background: linear-gradient(135deg, #91C2FF, #B5D8FE); }
.status-header.failed { background: linear-gradient(135deg, #FFC3D8, #FFA0B8); }
.status-icon { font-size: 80rpx; margin-bottom: 20rpx; }
.status-text { font-size: 36rpx; font-weight: bold; margin-bottom: 10rpx; }
.status-desc { font-size: 26rpx; opacity: 0.9; }

/* 模板信息卡片 */
.template-card { 
	display: flex; background: #fff; margin: 20rpx; 
	border-radius: 24rpx; padding: 24rpx; 
	box-shadow: 0 6rpx 20rpx rgba(0,0,0,0.05); 
}
.template-cover { width: 120rpx; height: 120rpx; border-radius: 16rpx; background: #F5F0FA; }
.template-info { flex: 1; margin-left: 20rpx; display: flex; flex-direction: column; justify-content: space-between; }
.template-name { font-size: 30rpx; color: #333; font-weight: bold; }
.template-model { font-size: 24rpx; color: #999; }
.quantity-badge { 
	display: inline-flex; align-items: center; 
	background: rgba(145,194,255,0.15); padding: 6rpx 16rpx; 
	border-radius: 20rpx; align-self: flex-start; 
}
.quantity-text { font-size: 24rpx; color: #91C2FF; font-weight: bold; }

/* 原图预览 */
.original-section { background: #fff; margin: 20rpx; border-radius: 24rpx; padding: 24rpx; }
.section-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16rpx; }
.section-title { font-size: 30rpx; font-weight: bold; color: #555555; margin: 0; }
.toggle-icon { font-size: 24rpx; color: #91C2FF; }
.original-grid { display: flex; gap: 12rpx; flex-wrap: wrap; }
.original-image { width: 120rpx; height: 120rpx; border-radius: 12rpx; background: #F5F0FA; }

/* 生成结果 */
.result-section { background: #fff; margin: 20rpx; border-radius: 24rpx; padding: 30rpx; box-shadow: 0 6rpx 20rpx rgba(0,0,0,0.05); }
.result-grid { display: flex; flex-wrap: wrap; gap: 16rpx; }
.result-item { position: relative; }
.result-image { width: calc(33.33% - 12rpx); aspect-ratio: 1; border-radius: 16rpx; background: #F5F0FA; }
.video-item { width: 100%; }
.result-video { width: 100%; border-radius: 16rpx; }
.image-error-overlay { 
	position: absolute; top: 0; left: 0; right: 0; bottom: 0; 
	display: flex; flex-direction: column; align-items: center; justify-content: center; 
	background: rgba(245,240,250,0.95); border-radius: 16rpx; 
}
.error-icon { font-size: 48rpx; margin-bottom: 8rpx; }
.error-text { font-size: 24rpx; color: #FF5722; }
.error-retry { font-size: 22rpx; color: #91C2FF; margin-top: 8rpx; }

.save-tip { text-align: center; font-size: 24rpx; color: #999; margin-top: 20rpx; }

/* 错误信息 */
.error-section { background: #fff; margin: 20rpx; border-radius: 24rpx; padding: 30rpx; }
.error-card { text-align: center; }
.error-title { font-size: 32rpx; color: #FF5722; font-weight: bold; display: block; margin-bottom: 16rpx; }
.error-detail { font-size: 26rpx; color: #666; display: block; margin-bottom: 24rpx; }
.error-actions { display: flex; gap: 20rpx; }
.btn-error { flex: 1; height: 72rpx; line-height: 72rpx; text-align: center; font-size: 26rpx; border-radius: 36rpx; background: #FFC3D8; color: #fff; }

/* 加载和底部栏 */
.loading-section { background: #fff; margin: 20rpx; border-radius: 24rpx; padding: 60rpx 30rpx; text-align: center; box-shadow: 0 6rpx 20rpx rgba(0,0,0,0.05); }
.loading-animation { width: 100rpx; height: 100rpx; margin: 0 auto 30rpx; }
.loading-circle { width: 100%; height: 100%; border: 6rpx solid #F0EDF5; border-top-color: #91C2FF; border-radius: 50%; animation: breathe 2s ease-in-out infinite; }
@keyframes breathe { 0% { transform: rotate(0deg) scale(1); opacity: 1; } 50% { transform: rotate(180deg) scale(1.05); opacity: 0.7; } 100% { transform: rotate(360deg) scale(1); opacity: 1; } }
.loading-text { font-size: 30rpx; color: #555555; margin-bottom: 12rpx; }
.loading-tip { font-size: 24rpx; color: #999; }
.bottom-bar { position: fixed; bottom: 0; left: 0; right: 0; background: #fff; display: flex; gap: 20rpx; padding: 20rpx 30rpx; box-shadow: 0 -4rpx 20rpx rgba(0,0,0,0.05); padding-bottom: calc(20rpx + env(safe-area-inset-bottom)); }
.btn-secondary { flex: 1; height: 88rpx; line-height: 88rpx; text-align: center; font-size: 30rpx; border-radius: 40rpx; background: #fff; color: #FFC3D8; border: 1px solid #FFC3D8; }
.btn-primary { flex: 1; height: 88rpx; line-height: 88rpx; text-align: center; font-size: 30rpx; border-radius: 40rpx; background: linear-gradient(135deg, #91C2FF, #B5D8FE); color: #fff; font-weight: bold; box-shadow: 0 8rpx 24rpx rgba(145,194,255,0.3); }
</style>
