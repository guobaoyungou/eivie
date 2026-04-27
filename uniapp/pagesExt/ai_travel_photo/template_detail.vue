<template>
<view class="container">
	<!-- 模板信息区 -->
	<view class="template-header" v-if="template.id">
		<view class="cover-wrap">
			<image :src="template.cover_image" mode="aspectFill" class="cover-img" @tap="previewImage" :data-url="template.cover_image" :data-urls="template.images || [template.cover_image]"/>
		</view>
		<view class="info-wrap">
			<view class="tpl-name">{{template.name}}</view>
			<view class="tpl-meta">
				<text class="scene-type" v-if="template.scene_type_text">{{template.scene_type_text}}</text>
				<text class="price" v-if="template.price && template.price > 0">¥{{template.price}}/次</text>
			</view>
			<view class="tpl-desc" v-if="template.description">{{template.description}}</view>
			<view class="tpl-model" v-if="template.model_name">
				<text class="label">AI模型：</text>
				<text class="value">{{template.model_name}}</text>
			</view>
		</view>
	</view>

	<!-- 上传要求说明 -->
	<view class="section" v-if="template.id">
		<view class="section-title">上传要求</view>
		<view class="upload-tips">
			<view class="tip-item">
				<text class="tip-icon">📷</text>
				<text class="tip-text">请上传清晰的{{template.scene_type_text || '人像'}}照片</text>
			</view>
			<view class="tip-item">
				<text class="tip-icon">📐</text>
				<text class="tip-text">支持 JPG、PNG 格式，建议不超过 10MB</text>
			</view>
			<view class="tip-item">
				<text class="tip-icon">💡</text>
				<text class="tip-text">光线充足、面部清晰的照片效果更佳</text>
			</view>
		</view>
	</view>

	<!-- 照片上传区 -->
	<view class="section" v-if="template.id">
		<view class="section-title">上传照片</view>
		<view class="upload-area">
			<view v-if="!uploadedImage" class="upload-btn" @tap="chooseImage">
				<text class="upload-icon">+</text>
				<text class="upload-text">选择照片</text>
			</view>
			<view v-else class="uploaded-preview">
				<image :src="uploadedImage" mode="aspectFill" class="preview-img"/>
				<view class="re-upload" @tap="chooseImage">重新选择</view>
			</view>
		</view>
	</view>

	<!-- 提交合成按钮 -->
	<view class="submit-area" v-if="template.id && !generationId">
		<button class="submit-btn" :disabled="!uploadedImageUrl || submitting" @tap="submitGenerate" :style="{background: submitting ? '#ccc' : ''}">
			{{submitting ? '提交中...' : '开始AI合成'}}
		</button>
	</view>

	<!-- 合成进度区 -->
	<view class="section progress-section" v-if="generationId">
		<view class="section-title">合成进度</view>
		<view class="progress-wrap">
			<view class="progress-status">
				<view class="status-icon" :class="'status-' + taskStatus">
					<text v-if="taskStatus==0">⏳</text>
					<text v-if="taskStatus==1">🔄</text>
					<text v-if="taskStatus==2">✅</text>
					<text v-if="taskStatus==3">❌</text>
				</view>
				<view class="status-text">
					<text v-if="taskStatus==0">等待处理中...</text>
					<text v-if="taskStatus==1">AI正在合成中，请耐心等待...</text>
					<text v-if="taskStatus==2">合成完成！</text>
					<text v-if="taskStatus==3">合成失败</text>
				</view>
			</view>
			<view class="error-msg" v-if="taskStatus==3 && errorMsg">{{errorMsg}}</view>
			<button class="retry-btn" v-if="taskStatus==3" @tap="submitGenerate">重试</button>
		</view>
	</view>

	<!-- 结果展示区 -->
	<view class="section result-section" v-if="taskStatus==2">
		<view class="section-title">合成结果</view>
		<view class="result-wrap">
			<image v-if="resultUrl" :src="watermarkUrl || resultUrl" mode="widthFix" class="result-img" @tap="previewImage" :data-url="watermarkUrl || resultUrl"/>
			<view class="pick-link" v-if="pickUrl" @tap="goToPick">
				<text class="pick-icon">🎨</text>
				<text class="pick-text">查看全部成片 & 付费选片</text>
				<text class="pick-arrow">›</text>
			</view>
		</view>
	</view>

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
			template: {},
			uploadedImage: '', // 本地预览路径
			uploadedImageUrl: '', // 服务器URL
			submitting: false,
			generationId: 0,
			portraitId: 0,
			taskStatus: -1, // -1未提交 0待处理 1处理中 2成功 3失败
			resultUrl: '',
			watermarkUrl: '',
			pickUrl: '',
			pickQrcode: '',
			errorMsg: '',
			pollTimer: null
		}
	},
	onLoad(opt) {
		this.opt = app.getopts(opt);
		this.getTemplateDetail();
	},
	onUnload() {
		this.clearPollTimer();
	},
	methods: {
		getTemplateDetail() {
			var that = this;
			var templateId = that.opt.template_id || 0;
			var bid = that.opt.bid || 0;
			if (!templateId || !bid) {
				app.error('参数错误');
				return;
			}
			that.loading = true;
			app.post('ApiAiTravelPhoto/merchantTemplateDetail', {
				template_id: templateId,
				bid: bid
			}, function(res) {
				that.loading = false;
				if (res.status == 1) {
					that.template = res.data;
					uni.setNavigationBarTitle({
						title: res.data.name || '模板详情'
					});
				} else {
					app.error(res.msg || '加载失败');
				}
			});
		},
		chooseImage() {
			var that = this;
			uni.chooseImage({
				count: 1,
				sizeType: ['compressed'],
				sourceType: ['album', 'camera'],
				success(res) {
					that.uploadedImage = res.tempFilePaths[0];
					that.uploadImage(res.tempFilePaths[0]);
				}
			});
		},
		uploadImage(filePath) {
			var that = this;
			that.loading = true;
			uni.uploadFile({
				url: app.globalData.pre_url + '/?s=ApiImageupload/upload',
				filePath: filePath,
				name: 'file',
				formData: {
					token: app.globalData.token || ''
				},
				success(res) {
					that.loading = false;
					var data = JSON.parse(res.data);
					if (data.status == 1) {
						that.uploadedImageUrl = data.url || data.data;
					} else {
						app.error(data.msg || '上传失败');
						that.uploadedImage = '';
					}
				},
				fail() {
					that.loading = false;
					app.error('上传失败');
					that.uploadedImage = '';
				}
			});
		},
		submitGenerate() {
			var that = this;
			if (!that.uploadedImageUrl) {
				app.error('请先上传照片');
				return;
			}
			that.submitting = true;
			that.taskStatus = -1;
			that.errorMsg = '';
			app.post('ApiAiTravelPhoto/merchantGenerate', {
				template_id: that.opt.template_id,
				bid: that.opt.bid,
				image_url: that.uploadedImageUrl
			}, function(res) {
				that.submitting = false;
				if (res.status == 1) {
					that.generationId = res.data.generation_id;
					that.portraitId = res.data.portrait_id;
					that.taskStatus = 0;
					that.startPolling();
				} else {
					app.error(res.msg || '提交失败');
				}
			});
		},
		startPolling() {
			var that = this;
			that.clearPollTimer();
			that.pollTimer = setInterval(function() {
				that.checkResult();
			}, 3000);
		},
		clearPollTimer() {
			if (this.pollTimer) {
				clearInterval(this.pollTimer);
				this.pollTimer = null;
			}
		},
		checkResult() {
			var that = this;
			app.post('ApiAiTravelPhoto/generationResult', {
				generation_id: that.generationId
			}, function(res) {
				if (res.status == 1) {
					var data = res.data;
					that.taskStatus = data.status;
					if (data.status == 2) {
						// 合成成功
						that.clearPollTimer();
						that.resultUrl = data.result_url || '';
						that.watermarkUrl = data.watermark_url || '';
						that.pickUrl = data.pick_url || '';
						that.pickQrcode = data.pick_qrcode || '';
					} else if (data.status == 3) {
						// 合成失败
						that.clearPollTimer();
						that.errorMsg = data.error_msg || '合成失败，请重试';
					}
					// status 0/1 继续轮询
				}
			});
		},
		goToPick() {
			var that = this;
			if (that.pickUrl) {
				// #ifdef H5
				window.location.href = that.pickUrl;
				// #endif
				// #ifndef H5
				app.goto('/pagesExt/ai_travel_photo/pick?qrcode=' + encodeURIComponent(that.pickUrl));
				// #endif
			}
		},
		previewImage(e) {
			var url = e.currentTarget.dataset.url;
			var urls = e.currentTarget.dataset.urls || [url];
			uni.previewImage({
				current: url,
				urls: urls
			});
		}
	}
}
</script>

<style>
.container{background:#f7f7f8;min-height:100vh;padding-bottom:120rpx}
.template-header{background:#fff;overflow:hidden}
.cover-wrap{width:100%;height:500rpx}
.cover-wrap .cover-img{width:100%;height:100%}
.info-wrap{padding:24rpx 30rpx}
.tpl-name{font-size:36rpx;font-weight:bold;color:#222}
.tpl-meta{display:flex;align-items:center;margin-top:16rpx}
.scene-type{font-size:24rpx;color:#666;background:#f5f5f5;padding:6rpx 20rpx;border-radius:8rpx;margin-right:20rpx}
.price{font-size:32rpx;color:#EF3835;font-weight:bold}
.tpl-desc{font-size:26rpx;color:#999;margin-top:16rpx;line-height:1.6}
.tpl-model{font-size:24rpx;color:#666;margin-top:12rpx}
.tpl-model .label{color:#999}
.tpl-model .value{color:#333}

.section{background:#fff;margin-top:20rpx;padding:24rpx 30rpx}
.section-title{font-size:30rpx;font-weight:bold;color:#222;margin-bottom:20rpx}

.upload-tips{background:#FFF9ED;border-radius:12rpx;padding:20rpx}
.tip-item{display:flex;align-items:center;padding:10rpx 0}
.tip-icon{font-size:28rpx;margin-right:16rpx}
.tip-text{font-size:26rpx;color:#666}

.upload-area{display:flex;justify-content:center;padding:20rpx 0}
.upload-btn{width:300rpx;height:300rpx;border:2rpx dashed #ccc;border-radius:16rpx;display:flex;flex-direction:column;align-items:center;justify-content:center}
.upload-icon{font-size:80rpx;color:#ccc;line-height:1}
.upload-text{font-size:26rpx;color:#999;margin-top:12rpx}
.uploaded-preview{position:relative;width:300rpx}
.preview-img{width:300rpx;height:300rpx;border-radius:16rpx}
.re-upload{text-align:center;font-size:24rpx;color:#5B8FF9;margin-top:12rpx}

.submit-area{padding:30rpx;position:fixed;bottom:0;left:0;right:0;background:#fff;box-shadow:0 -2rpx 12rpx rgba(0,0,0,0.06)}
.submit-btn{width:100%;height:88rpx;line-height:88rpx;background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);color:#fff;font-size:32rpx;font-weight:bold;border-radius:44rpx;text-align:center}
.submit-btn[disabled]{background:#ccc!important}

.progress-wrap{padding:20rpx 0}
.progress-status{display:flex;align-items:center}
.status-icon{font-size:40rpx;margin-right:16rpx}
.status-text{font-size:28rpx;color:#333}
.error-msg{font-size:24rpx;color:#EF3835;margin-top:16rpx;padding:12rpx;background:#FFF2F0;border-radius:8rpx}
.retry-btn{margin-top:20rpx;height:72rpx;line-height:72rpx;background:#5B8FF9;color:#fff;font-size:28rpx;border-radius:36rpx}

.result-wrap{padding:20rpx 0}
.result-img{width:100%;border-radius:12rpx}
.pick-link{display:flex;align-items:center;margin-top:24rpx;padding:24rpx;background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);border-radius:12rpx}
.pick-icon{font-size:36rpx;margin-right:16rpx}
.pick-text{flex:1;font-size:28rpx;color:#fff;font-weight:bold}
.pick-arrow{font-size:36rpx;color:#fff}
</style>
