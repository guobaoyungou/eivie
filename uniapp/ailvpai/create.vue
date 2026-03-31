<template>
<view class="container">
	<block v-if="isload">
		<!-- 模板对比展示区域 -->
		<view class="section compare-section" v-if="detail">
			<view class="compare-container">
				<!-- 左侧：原始照片 -->
				<view class="compare-side">
					<view class="compare-label">原图</view>
					<swiper class="compare-swiper" v-if="originalImages.length > 1" autoplay circular indicator-dots :indicator-color="'rgba(255,255,255,0.4)'" indicator-active-color="#91C2FF" :interval="3000">
						<swiper-item v-for="(img, idx) in originalImages" :key="'orig_'+idx">
							<image :src="img" class="compare-image" mode="aspectFill" @tap="previewImage(originalImages, idx)"></image>
						</swiper-item>
					</swiper>
					<view class="compare-single" v-else>
						<image :src="originalImages[0] || '/static/img/placeholder.png'" class="compare-image" mode="aspectFill" @tap="previewImage(originalImages, 0)"></image>
					</view>
				</view>
				<!-- 中间分隔 -->
				<view class="compare-divider">
					<text class="compare-vs">VS</text>
				</view>
				<!-- 右侧：效果图 -->
				<view class="compare-side">
					<view class="compare-label effect-label">效果图 ✨</view>
					<swiper class="compare-swiper" v-if="effectImages.length > 1" autoplay circular indicator-dots :indicator-color="'rgba(255,255,255,0.4)'" indicator-active-color="#91C2FF" :interval="3000">
						<swiper-item v-for="(img, idx) in effectImages" :key="'eff_'+idx">
							<image :src="img" class="compare-image" mode="aspectFill" @tap="previewImage(effectImages, idx)"></image>
						</swiper-item>
					</swiper>
					<view class="compare-single" v-else>
						<image :src="effectImages[0] || '/static/img/placeholder.png'" class="compare-image" mode="aspectFill" @tap="previewImage(effectImages, 0)"></image>
					</view>
				</view>
			</view>
			<!-- 模板名称和价格 -->
			<view class="compare-info">
				<text class="compare-tpl-name">{{detail.template_name}}</text>
				<text class="compare-tpl-price" v-if="scorePayEnabled">{{priceInScore}} {{scoreUnitName}}</text>
				<text class="compare-tpl-price" v-else>¥{{detail.price}}</text>
			</view>
		</view>

		<!-- 提示词编辑区域（折叠/展开） -->
		<view class="section" v-if="promptVisible && prompt">
			<view class="prompt-header" @tap="togglePrompt">
				<text class="section-title" style="margin-bottom:0;">创作提示词</text>
				<text class="prompt-arrow">{{promptExpanded ? '∧' : '∨'}}</text>
			</view>
			<!-- 折叠态：预览 -->
			<view class="prompt-preview" v-if="!promptExpanded" @tap="togglePrompt">{{prompt}}</view>
			<!-- 展开态：可编辑 -->
			<view v-if="promptExpanded">
				<textarea class="prompt-textarea" v-model="prompt" placeholder="请输入描述，越详细效果越好" maxlength="2000" :auto-height="true" :style="{maxHeight:'300rpx'}"></textarea>
				<view class="prompt-footer">
					<text class="prompt-char-count">{{prompt.length}}/2000</text>
					<text class="prompt-optimize-btn" @tap="onOptimizePrompt">✨ 优化提示词</text>
				</view>
			</view>
		</view>
		
		<!-- 参考图上传 -->
		<view class="section" v-if="needRefImage">
			<view class="section-title">
				<text class="required">*</text> 参考图片
			</view>
			<uni-image-upload
				v-model="refImages"
				:maxCount="maxImages"
				:maxSize="10"
				:columns="4"
				:enableCamera="true"
				:enableAlbum="true"
				:enableChat="true"
				cameraPosition="back"
			></uni-image-upload>
		</view>
		
		<!-- 生成数量（图片生成） -->
		<view class="section" v-if="generationType == 1">
			<view class="section-title">治愈画面数量</view>
			<scroll-view class="option-scroll" scroll-x :show-scrollbar="false">
				<view class="option-scroll-inner">
					<view class="count-chip" :class="{active: quantity == item}" v-for="(item, idx) in countOptions" :key="idx" @tap="selectCount(item)">
						<text class="chip-label">{{item}}张</text>
					</view>
				</view>
			</scroll-view>
		</view>
		
		<!-- 输出比例选择（图片生成） -->
		<view class="section" v-if="generationType == 1 && ratioOptions.length > 0">
			<view class="section-title">画面比例</view>
			<scroll-view class="option-scroll" scroll-x :show-scrollbar="false">
				<view class="option-scroll-inner">
					<view class="count-chip" :class="{active: ratio == item}" v-for="(item, idx) in ratioOptions" :key="idx" @tap="selectRatio(item)">
						<text class="chip-label">{{item}}</text>
					</view>
				</view>
			</scroll-view>
		</view>
		
		<!-- 输出质量选择（图片生成） -->
		<view class="section" v-if="generationType == 1">
			<view class="section-title">画面清晰度</view>
			<scroll-view class="option-scroll" scroll-x :show-scrollbar="false">
				<view class="option-scroll-inner">
					<view class="count-chip" :class="{active: quality == item.value}" v-for="(item, idx) in qualityOptions" :key="idx" @tap="selectQuality(item.value)">
						<text class="chip-label">{{item.label}}</text>
					</view>
				</view>
			</scroll-view>
		</view>
		
		<!-- 证件照拍照指引弹窗 -->
		<view class="guide-mask" v-if="showIdPhotoGuide" @tap="closeIdPhotoGuide">
			<view class="guide-popup" @tap.stop>
				<view class="guide-title">证件照拍摄指引</view>
				<view class="guide-subtitle">{{idPhotoTypeName}}拍摄要求</view>
				<view class="guide-content">
					<view class="guide-col">
						<view class="guide-label correct">✅ 正确示例</view>
						<view class="guide-tips">
							<text v-for="(tip, i) in idPhotoCorrectTips" :key="i" class="guide-tip-item">• {{tip}}</text>
						</view>
					</view>
					<view class="guide-col">
						<view class="guide-label wrong">❌ 错误示例</view>
						<view class="guide-tips">
							<text v-for="(tip, i) in idPhotoWrongTips" :key="i" class="guide-tip-item">• {{tip}}</text>
						</view>
					</view>
				</view>
				<view class="guide-btn" @tap="confirmIdPhotoGuide">我知道了</view>
			</view>
		</view>
		
		<!-- 底部操作栏 -->
		<view class="bottom-bar">
			<view class="price-display">
				<text class="total-label">合计：</text>
				<text class="total-price score-price" v-if="scorePayEnabled">{{totalPriceInScore}} {{scoreUnitName}}</text>
				<text class="total-price" v-else>¥{{totalPrice}}</text>
			</view>
			<view class="btn-primary" :class="{disabled: submitting || !selectedTemplateId}" @tap="submitGeneration">
				{{submitting ? '正在为你绘制温柔画面…' : '开始生成 ✨'}}
			</view>
		</view>
		<view style="height: 120rpx;"></view>
	</block>
	
	<!-- 余额/积分不足弹窗 -->
	<view class="insufficient-mask" v-if="showInsufficientPopup" @tap="closeInsufficientPopup">
		<view class="insufficient-popup" @tap.stop>
			<view class="insufficient-icon">{{insufficientType == 'score_insufficient' ? '⭐' : '💰'}}</view>
			<view class="insufficient-title">{{insufficientTitle}}</view>
			<view class="insufficient-msg">{{insufficientMsg}}</view>
			<view class="insufficient-btn" @tap="onInsufficientAction">{{insufficientBtnText}}</view>
			<view class="insufficient-close" @tap="closeInsufficientPopup">关闭</view>
		</view>
	</view>

	<loading v-if="loading"></loading>
	<!-- 登录弹窗组件 -->
	<login-popup ref="loginPopup" @login-success="onLoginSuccess" @close="onLoginPopupClose"></login-popup>
	<!-- #ifdef MP-WEIXIN -->
	<wxxieyi></wxxieyi>
	<!-- #endif -->
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
			detail: null,
			prompt: '',
			refImages: [],
			quantity: 1,
			submitting: false,
			generationType: 1,
			needRefImage: false,
			maxImages: 1,
			countOptions: [1, 2, 3, 4, 5, 6, 7, 8, 9],
			selectedTemplateId: 0,
			ratio: '1:1',
			quality: 'hd',
			ratioOptions: [],
			qualityOptions: [
				{ label: '温柔清晰', value: 'standard' },
				{ label: '高清细腻', value: 'hd' },
				{ label: '超清极致', value: 'ultra' }
			],
			promptVisible: true,
			promptExpanded: false,
			showIdPhotoGuide: false,
			idPhotoGuideShownMap: {},
			showInsufficientPopup: false,
			insufficientType: '',
			insufficientTitle: '',
			insufficientMsg: '',
			insufficientBtnText: '',
			insufficientExtra: {},
			scorePayEnabled: false,
			priceInScore: 0,
			scoreUnitName: '词元'
		};
	},
	
	computed: {
		totalPrice() {
			if (!this.detail) return '0.00';
			var price = parseFloat(this.detail.price) || 0;
			if (this.generationType == 1) {
				return (price * this.quantity).toFixed(2);
			}
			return price.toFixed(2);
		},
		totalPriceInScore() {
			if (!this.priceInScore) return 0;
			if (this.generationType == 1) {
				return this.priceInScore * this.quantity;
			}
			return this.priceInScore;
		},

		originalImages() {
			if (!this.detail) return [];
			if (this.detail.original_images && this.detail.original_images.length > 0) {
				var filtered = this.detail.original_images.filter(function(img) { return img && img !== ''; });
				if (filtered.length > 0) return filtered;
			}
			if (this.detail.original_image && this.detail.original_image !== '') {
				return [this.detail.original_image];
			}
			if (this.detail.ref_image && this.detail.ref_image !== '') {
				return [this.detail.ref_image];
			}
			if (this.detail.ref_images && this.detail.ref_images.length > 0) {
				var filteredRef = this.detail.ref_images.filter(function(img) { return img && img !== ''; });
				if (filteredRef.length > 0) return filteredRef;
			}
			return [this.detail.cover_image || '/static/img/placeholder.png'];
		},
		effectImages() {
			if (!this.detail) return [];
			if (this.detail.effect_images && this.detail.effect_images.length > 0) {
				var filtered = this.detail.effect_images.filter(function(img) { return img && img !== ''; });
				if (filtered.length > 0) return filtered;
			}
			if (this.detail.effect_image && this.detail.effect_image !== '') {
				return [this.detail.effect_image];
			}
			if (this.detail.sample_images && this.detail.sample_images.length > 0) {
				var filteredSample = this.detail.sample_images.filter(function(img) { return img && img !== ''; });
				if (filteredSample.length > 0) return filteredSample;
			}
			return [this.detail.cover_image || '/static/img/placeholder.png'];
		},
		idPhotoTypeName() {
			if (!this.detail || !this.detail.is_id_photo) return '';
			return this.detail.id_photo_type_name || '';
		},
		generationUploadUrlUnused() {
			return '';
		},
		idPhotoCorrectTips() {
			var typeMap = {
				1: ['正面免冠、白色背景', '五官清晰完整'],
				2: ['白色背景、不露齿', '露双耳'],
				3: ['白色背景、免冠', '面部居中'],
				4: ['纯色背景（红/蓝/白）', '肩部以上'],
				5: ['纯色背景（红/蓝/白）', '肩部以上']
			};
			var t = this.detail ? (this.detail.id_photo_type || 0) : 0;
			return typeMap[t] || ['正面免冠、五官清晰完整', '背景纯色、光线均匀'];
		},
		idPhotoWrongTips() {
			var typeMap = {
				1: ['戴帽/墨镜、背景杂乱', '照片模糊'],
				2: ['背景非白色、表情夸张', '头发遮脸'],
				3: ['侧脸、逆光', '分辨率过低'],
				4: ['半身照、背景渐变', '化妆过度'],
				5: ['半身照、光线不均', '表情不自然']
			};
			var t = this.detail ? (this.detail.id_photo_type || 0) : 0;
			return typeMap[t] || ['模糊/遮挡面部', '背景杂乱/曝光不均'];
		}
	},
	
	onLoad(opt) {
		this.opt = app.getopts(opt);
		this.generationType = parseInt(this.opt.type) || 1;
		this.selectedTemplateId = parseInt(this.opt.template_id) || parseInt(this.opt.id) || 0;
		this.getdata();
	},
	
	methods: {
		getdata() {
			var that = this;
			that.loading = true;
			
			if (!that.selectedTemplateId) {
				app.alert('模板参数异常');
				setTimeout(function() { uni.navigateBack(); }, 1500);
				return;
			}
			
			app.get('ApiAivideo/scene_template_detail', {
				template_id: that.selectedTemplateId
			}, function(res) {
				that.loading = false;
				if (res.status == 1) {
					that.applyTemplateDetail(res.data);
					that.isload = true;
				} else {
					app.alert(res.msg);
				}
			});
		},
		
		applyTemplateDetail(detail) {
			var that = this;
			that.detail = detail;
			that.promptVisible = (detail.prompt_visible !== 0 && detail.prompt_visible !== '0');
			var defaultQuantity = parseInt(detail.output_quantity) || 1;
			if (defaultQuantity > 9) defaultQuantity = 9;
			if (defaultQuantity < 1) defaultQuantity = 1;
			that.quantity = defaultQuantity;
			var maxImages = parseInt(detail.max_ref_images) || 1;
			if (maxImages > 9) maxImages = 9;
			if (maxImages < 1) maxImages = 1;
			that.maxImages = maxImages;
			that.needRefImage = (maxImages > 0 && detail.need_ref_image !== 0 && detail.need_ref_image !== '0');
			if (detail.prompt) {
				that.prompt = detail.prompt;
			}
			if (detail.model_capability && detail.model_capability.supported_ratios) {
				that.ratioOptions = detail.model_capability.supported_ratios;
			} else {
				that.ratioOptions = ['1:1','2:3','3:2','3:4','4:3','9:16','16:9','4:5','5:4','21:9'];
			}
			if (detail.default_params && detail.default_params.ratio) {
				that.ratio = detail.default_params.ratio;
			} else {
				that.ratio = '1:1';
			}
			that.quality = 'hd';
			if (detail.is_id_photo && !that.idPhotoGuideShownMap[detail.id]) {
				that.showIdPhotoGuide = true;
			}
			that.scorePayEnabled = detail.score_pay_enabled || false;
			that.priceInScore = detail.price_in_score || 0;
			that.scoreUnitName = detail.score_unit_name || '词元';
			var title = that.generationType == 1 ? '图片生成' : '视频生成';
			uni.setNavigationBarTitle({ title: title });
		},
		
		loadTemplateDetail(templateId) {
			var that = this;
			app.showLoading('温柔加载中…');
			app.get('ApiAivideo/scene_template_detail', {
				template_id: templateId
			}, function(res) {
				app.showLoading(false);
				if (res.status == 1) {
					that.applyTemplateDetail(res.data);
				} else {
					app.alert(res.msg);
				}
			});
		},
		
		togglePrompt() {
			this.promptExpanded = !this.promptExpanded;
		},
		
		onOptimizePrompt() {
			uni.showToast({ title: '功能即将上线', icon: 'none' });
		},
		
		previewImage(images, index) {
			if (!images || images.length === 0) return;
			uni.previewImage({
				urls: images,
				current: images[index] || images[0]
			});
		},
		
		selectCount(count) { this.quantity = count; },
		selectRatio(r) { this.ratio = r; },
		selectQuality(q) { this.quality = q; },
		
		closeIdPhotoGuide() { this.showIdPhotoGuide = false; },
		confirmIdPhotoGuide() {
			var tplId = this.selectedTemplateId;
			this.idPhotoGuideShownMap[tplId] = true;
			this.showIdPhotoGuide = false;
		},
		
		closeInsufficientPopup() { this.showInsufficientPopup = false; },
		onInsufficientAction() {
			this.showInsufficientPopup = false;
			if (this.insufficientType == 'balance_insufficient' || this.insufficientType == 'score_insufficient') {
				uni.navigateTo({ url: '/pagesExt/money/recharge' });
			}
		},
		
		/**
		 * 登录检查并执行业务操作
		 * 如果已登录，直接执行 callback；如果未登录，打开登录弹窗
		 */
		checkLoginAndDo(callback) {
			if (app.globalData.mid > 0) {
				// 已登录，直接执行
				typeof callback == 'function' && callback();
			} else {
				// 未登录，打开登录弹窗
				this.$refs.loginPopup.open(callback);
			}
		},

		/**
		 * 登录成功回调
		 */
		onLoginSuccess(res) {
			console.log('login-popup 登录成功', res);
		},

		/**
		 * 登录弹窗关闭回调（用户点击"暂不登录"）
		 */
		onLoginPopupClose() {
			// 重置提交状态
			this.submitting = false;
		},

		submitGeneration() {
			var that = this;
			if (that.submitting) return;
			if (!that.selectedTemplateId) return app.alert('请选择场景模板');
			if (that.promptVisible) {
				if (!that.prompt || that.prompt.trim().length < 2) {
					return app.alert('请填写提示词（至少2个字符）');
				}
			}
			if (that.needRefImage && that.refImages.length == 0) {
				return app.alert('请上传参考图片');
			}

			// 登录检查：未登录则弹出登录弹窗，登录成功后自动继续提交
			that.checkLoginAndDo(function() {
				that.doSubmitGeneration();
			});
		},

		/**
		 * 实际执行生成订单提交
		 */
		doSubmitGeneration() {
			var that = this;
			that.submitting = true;
			app.showLoading('正在为你绘制温柔画面…');
			var postData = {
				template_id: that.selectedTemplateId,
				generation_type: that.generationType,
				prompt: that.promptVisible ? that.prompt : (that.detail.prompt || ''),
				ref_images: that.refImages,
				quantity: that.quantity,
				ratio: that.ratio,
				quality: that.quality,
				bid: that.opt.bid || 0
			};
			app.post('ApiAivideo/create_generation_order', postData, function(res) {
				app.showLoading(false);
				that.submitting = false;
				if (res.status == 1) {
					var data = res.data;
					if (data.need_pay) {
						uni.navigateTo({
							url: '/pages/pay/pay?ordernum=' + data.ordernum + '&tablename=generation'
						});
					} else {
						uni.redirectTo({
							url: '/ailvpai/result?order_id=' + data.order_id
						});
					}
				} else {
					var errorType = res.error_type || 'normal';
					if (errorType == 'score_insufficient') {
						var extra = res.extra || {};
						that.showInsufficientPopup = true;
						that.insufficientType = 'score_insufficient';
						that.insufficientTitle = that.scoreUnitName + '不足';
						that.insufficientMsg = '当前可用' + that.scoreUnitName + ' ' + (extra.current_score || 0) + '，本次需要 ' + (extra.required_score || 0) + ' ' + that.scoreUnitName;
						that.insufficientBtnText = '购买创作会员';
						that.insufficientExtra = extra;
					} else if (errorType == 'balance_insufficient') {
						var extra2 = res.extra || {};
						that.showInsufficientPopup = true;
						that.insufficientType = 'balance_insufficient';
						that.insufficientTitle = '余额不足';
						that.insufficientMsg = '当前余额 ￥' + (extra2.current_balance || 0) + '，还需 ￥' + (extra2.need_amount || 0);
						that.insufficientBtnText = '去充值';
						that.insufficientExtra = extra2;
					} else {
						app.alert(res.msg);
					}
				}
			});
		}
	}
};
</script>

<style>
.container { background: #FDFBFF; min-height: 100vh; }
/* 对比展示区域 */
.compare-section { padding: 30rpx 20rpx; }
.compare-container { display: flex; align-items: stretch; gap: 0; }
.compare-side { flex: 1; display: flex; flex-direction: column; }
.compare-label { font-size: 24rpx; color: #666666; text-align: center; margin-bottom: 12rpx; font-weight: bold; }
.compare-label.effect-label { color: #91C2FF; }
.compare-swiper { width: 100%; height: 400rpx; border-radius: 16rpx; overflow: hidden; }
.compare-single { width: 100%; height: 400rpx; border-radius: 16rpx; overflow: hidden; }
.compare-image { width: 100%; height: 400rpx; border-radius: 16rpx; background: #F5F0FA; }
.compare-divider { display: flex; align-items: center; justify-content: center; width: 60rpx; flex-shrink: 0; }
.compare-vs { font-size: 24rpx; color: #ccc; font-weight: bold; background: #FDFBFF; border-radius: 50%; width: 48rpx; height: 48rpx; line-height: 48rpx; text-align: center; }
.compare-info { display: flex; align-items: center; justify-content: space-between; margin-top: 20rpx; padding: 0 8rpx; }
.compare-tpl-name { font-size: 30rpx; color: #555555; font-weight: bold; flex: 1; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.compare-tpl-price { font-size: 30rpx; color: #91C2FF; font-weight: bold; flex-shrink: 0; margin-left: 16rpx; }
/* 提示词编辑区域 */
.prompt-header { display: flex; align-items: center; justify-content: space-between; }
.prompt-arrow { font-size: 28rpx; color: #999; padding: 10rpx; }
.prompt-preview { font-size: 26rpx; color: #666666; line-height: 1.6; margin-top: 16rpx; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; text-overflow: ellipsis; }
.prompt-textarea { width: 100%; min-height: 120rpx; max-height: 300rpx; font-size: 28rpx; line-height: 1.6; color: #555555; padding: 20rpx; background: #F5F0FA; border-radius: 16rpx; margin-top: 16rpx; box-sizing: border-box; }
.prompt-footer { display: flex; align-items: center; justify-content: space-between; margin-top: 12rpx; }
.prompt-char-count { font-size: 24rpx; color: #999; }
.prompt-optimize-btn { font-size: 24rpx; color: #91C2FF; border: 1rpx solid #91C2FF; border-radius: 30rpx; padding: 8rpx 20rpx; }
.section { background: #fff; margin: 20rpx; border-radius: 24rpx; padding: 30rpx; box-shadow: 0 6rpx 20rpx rgba(0,0,0,0.05); }
.section-title { font-size: 30rpx; font-weight: bold; color: #555555; margin-bottom: 20rpx; }
.required { color: #FFA0B8; margin-right: 4rpx; }

.option-scroll { margin-top: 0; width: 100%; }
.option-scroll-inner { display: inline-flex; gap: 20rpx; padding: 4rpx 4rpx; }
.count-chip { display: inline-flex; align-items: center; justify-content: center; padding: 14rpx 36rpx; border-radius: 40rpx; border: 2rpx solid #F0EDF5; background: #fafafa; flex-shrink: 0; transition: all 0.2s; }
.count-chip.active { border-color: #91C2FF; background: rgba(181,216,254,0.1); }
.chip-label { font-size: 26rpx; color: #666; white-space: nowrap; }
.count-chip.active .chip-label { color: #91C2FF; font-weight: bold; }
.bottom-bar { position: fixed; bottom: 0; left: 0; right: 0; background: #fff; display: flex; align-items: center; padding: 20rpx 30rpx; box-shadow: 0 -4rpx 20rpx rgba(0,0,0,0.05); padding-bottom: calc(20rpx + env(safe-area-inset-bottom)); }
.price-display { flex: 1; }
.total-label { font-size: 28rpx; color: #666; }
.total-price { font-size: 40rpx; color: #91C2FF; font-weight: bold; }
.btn-primary { background: linear-gradient(135deg, #91C2FF, #B5D8FE); color: #fff; font-size: 32rpx; font-weight: bold; padding: 24rpx 60rpx; border-radius: 40rpx; box-shadow: 0 8rpx 24rpx rgba(145,194,255,0.3); }
.btn-primary.disabled { opacity: 0.5; box-shadow: none; }
.score-price { color: #91C2FF; }
.insufficient-mask { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.4); z-index: 9999; display: flex; align-items: center; justify-content: center; }
.insufficient-popup { width: 560rpx; background: #fff; border-radius: 24rpx; padding: 50rpx 40rpx; text-align: center; }
.insufficient-icon { font-size: 80rpx; margin-bottom: 20rpx; }
.insufficient-title { font-size: 36rpx; font-weight: bold; color: #555555; margin-bottom: 16rpx; }
.insufficient-msg { font-size: 28rpx; color: #666; line-height: 1.6; margin-bottom: 40rpx; }
.insufficient-btn { background: linear-gradient(135deg, #91C2FF, #B5D8FE); color: #fff; font-size: 32rpx; font-weight: bold; padding: 24rpx 0; border-radius: 40rpx; margin-bottom: 20rpx; box-shadow: 0 8rpx 24rpx rgba(145,194,255,0.3); }
.insufficient-close { font-size: 28rpx; color: #999; padding: 10rpx 0; }
.guide-mask { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.4); z-index: 999; display: flex; align-items: center; justify-content: center; }
.guide-popup { width: 620rpx; background: #fff; border-radius: 24rpx; padding: 40rpx 36rpx; }
.guide-title { font-size: 34rpx; font-weight: bold; color: #555555; text-align: center; margin-bottom: 10rpx; }
.guide-subtitle { font-size: 26rpx; color: #999; text-align: center; margin-bottom: 30rpx; }
.guide-content { display: flex; gap: 20rpx; margin-bottom: 30rpx; }
.guide-col { flex: 1; }
.guide-label { font-size: 26rpx; font-weight: bold; margin-bottom: 12rpx; padding: 8rpx 0; text-align: center; border-radius: 8rpx; }
.guide-label.correct { color: #52c41a; background: #f6ffed; }
.guide-label.wrong { color: #FFA0B8; background: #fff2f0; }
.guide-tip-item { display: block; font-size: 24rpx; color: #666; line-height: 1.8; }
.guide-btn { background: linear-gradient(135deg, #91C2FF, #B5D8FE); color: #fff; font-size: 30rpx; font-weight: bold; text-align: center; padding: 22rpx 0; border-radius: 40rpx; box-shadow: 0 8rpx 24rpx rgba(145,194,255,0.3); }
</style>
