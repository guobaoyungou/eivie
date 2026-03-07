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
	
	<!-- 生成结果 -->
	<view class="result-section" v-if="result && result.outputs && result.outputs.length > 0">
		<view class="section-title">生成结果</view>
		<view class="result-grid">
			<block v-for="(output, idx) in result.outputs" :key="idx">
				<image 
					v-if="output.type != 'video'" 
					:src="output.url" 
					class="result-image" 
					mode="aspectFill"
					@tap="previewImage(output.url)"
					@longpress="saveImage(output.url)"
				></image>
				<view v-else class="video-item">
					<video 
						:src="output.url" 
						class="result-video" 
						controls
						:poster="output.thumbnail"
					></video>
				</view>
			</block>
		</view>
		<view class="save-tip">长按图片可保存到相册</view>
	</view>
	
	<!-- 加载中 -->
	<view class="loading-section" v-if="polling && (!result || !result.outputs || result.outputs.length == 0)">
		<view class="loading-animation">
			<view class="loading-circle"></view>
		</view>
		<view class="loading-text">正在生成中，请稍候...</view>
		<view class="loading-tip">{{generationType == 1 ? '图片生成通常需要10-30秒' : '视频生成通常需要1-3分钟'}}</view>
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
			generationType: 1
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
			if (!this.result) return '生成中...';
			var status = this.result.status;
			if (status == 2) return '生成完成';
			if (status == 3) return '生成失败';
			if (status == 1) return '生成中...';
			return '等待处理';
		},
		
		statusDesc() {
			if (!this.result) return '正在为您生成中，请稍候...';
			var status = this.result.status;
			if (status == 2) return '长按图片可保存到相册';
			if (status == 3) return '生成失败，您可以申请退款';
			if (status == 1) return '正在为您生成中，请稍候...';
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
		// 页面显示时继续轮询
		if (this.result && this.result.status == 1) {
			this.startPolling();
		}
	},
	
	onHide() {
		this.stopPolling();
	},
	
	methods: {
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
					
					// 如果完成或失败，停止轮询
					if (res.data.status == 2 || res.data.status == 3) {
						that.stopPolling();
						return;
					}
				}
				
				// 继续轮询
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
										uni.showToast({ title: '保存成功', icon: 'success' });
									},
									fail: function() {
										uni.showToast({ title: '保存失败', icon: 'none' });
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
				url: '/pagesZ/generation/orderlist'
			});
		},
		
		goBack() {
			// 返回首页
			uni.switchTab({
				url: '/pages/index/index'
			});
		}
	}
};
</script>

<style>
.container { background: #f5f5f5; min-height: 100vh; }

.status-header { padding: 60rpx 30rpx; text-align: center; color: #fff; }
.status-header.processing { background: linear-gradient(135deg, #2196F3, #64B5F6); }
.status-header.success { background: linear-gradient(135deg, #4CAF50, #81C784); }
.status-header.failed { background: linear-gradient(135deg, #F44336, #E57373); }
.status-icon { font-size: 80rpx; margin-bottom: 20rpx; }
.status-text { font-size: 36rpx; font-weight: bold; margin-bottom: 10rpx; }
.status-desc { font-size: 26rpx; opacity: 0.9; }

.result-section { background: #fff; margin: 20rpx; border-radius: 16rpx; padding: 30rpx; }
.section-title { font-size: 30rpx; font-weight: bold; color: #333; margin-bottom: 20rpx; }
.result-grid { display: flex; flex-wrap: wrap; gap: 16rpx; }
.result-image { width: calc(33.33% - 12rpx); aspect-ratio: 1; border-radius: 8rpx; background: #f5f5f5; }
.video-item { width: 100%; }
.result-video { width: 100%; border-radius: 8rpx; }
.save-tip { text-align: center; font-size: 24rpx; color: #999; margin-top: 20rpx; }

.loading-section { background: #fff; margin: 20rpx; border-radius: 16rpx; padding: 60rpx 30rpx; text-align: center; }
.loading-animation { width: 100rpx; height: 100rpx; margin: 0 auto 30rpx; }
.loading-circle { width: 100%; height: 100%; border: 6rpx solid #f0f0f0; border-top-color: #FF6B00; border-radius: 50%; animation: spin 1s linear infinite; }
@keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
.loading-text { font-size: 30rpx; color: #333; margin-bottom: 12rpx; }
.loading-tip { font-size: 24rpx; color: #999; }

.bottom-bar { position: fixed; bottom: 0; left: 0; right: 0; background: #fff; display: flex; gap: 20rpx; padding: 20rpx 30rpx; box-shadow: 0 -2rpx 10rpx rgba(0,0,0,0.05); padding-bottom: calc(20rpx + env(safe-area-inset-bottom)); }
.btn-secondary { flex: 1; height: 88rpx; line-height: 88rpx; text-align: center; font-size: 30rpx; border-radius: 44rpx; background: #fff; color: #FF6B00; border: 1px solid #FF6B00; }
.btn-primary { flex: 1; height: 88rpx; line-height: 88rpx; text-align: center; font-size: 30rpx; border-radius: 44rpx; background: linear-gradient(135deg, #FF6B00, #FF9500); color: #fff; font-weight: bold; }
</style>
