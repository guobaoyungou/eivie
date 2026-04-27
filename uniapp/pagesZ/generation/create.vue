
<template>
<view class="container">
	<block v-if="isload">
		<!-- 模板原图+效果图对比展示区域 -->
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
		<view class="section" v-if="generationType == 1 && countOptions.length > 0">
			<view class="section-title">治愈画面数量</view>
			<scroll-view class="option-scroll" scroll-x :show-scrollbar="false">
				<view class="option-scroll-inner">
					<view class="count-chip" :class="{active: quantity == item}" v-for="(item, idx) in countOptions" :key="idx" @tap="selectCount(item)">
						<text class="chip-label">{{item}}张</text>
					</view>
				</view>
			</scroll-view>
			<view class="generation-limit-hint" v-if="generationLimitHint">
				<text class="hint-text">{{generationLimitHint}}</text>
			</view>
		</view>
		<!-- 参考图过多无法生成提示 -->
		<view class="section" v-if="generationType == 1 && refImageOverflow">
			<view class="generation-limit-warning">⚠️ 参考图数量已达上限，请减少参考图后再选择生成数量</view>
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
		
		<!-- 佣金提示条 -->
		<view class="commission-bar" v-if="detail && detail.share_show_commission && detail.commission_enabled && parseFloat(detail.share_commission_amount) > 0" @tap="openSharePopup">
			<view class="commission-bar-left">
				<text class="commission-bar-icon">💰</text>
				<text class="commission-bar-text">{{detail.share_commission_desc}}</text>
			</view>
			<view class="commission-bar-btn">立即分享</view>
		</view>
		
		<!-- 底部操作栏 -->
		<view class="bottom-bar">
			<view class="price-display">
				<text class="total-label">合计：</text>
				<text class="total-price score-price" v-if="scorePayEnabled">{{totalPriceInScore}} {{scoreUnitName}}</text>
				<text class="total-price" v-else>¥{{totalPrice}}</text>
			</view>
			<view class="btn-primary" :class="{disabled: submitting || !selectedTemplateId || refImageOverflow}" @tap="submitGeneration">
				{{submitting ? '正在为你绘制温柔画面…' : '开始生成 ✨'}}
			</view>
		</view>
		<view style="height: 120rpx;"></view>
		
		<!-- 分享弹窗 -->
		<view v-if="sharetypevisible" class="popup__container">
			<view class="popup__overlay" @tap.stop="closeSharePopup"></view>
			<view class="popup__modal" style="height:320rpx;min-height:320rpx">
				<view class="popup__content">
					<view class="sharetypecontent">
						<!-- #ifdef APP-PLUS -->
						<view class="f1" @tap="shareApp">
							<image class="img" :src="pre_url+'/static/img/sharefriends.png'"/>
							<text class="t1">分享给好友</text>
						</view>
						<!-- #endif -->
						<!-- #ifdef MP-WEIXIN -->
						<button class="f1" open-type="share">
							<image class="img" :src="pre_url+'/static/img/sharefriends.png'"/>
							<text class="t1">分享给好友</text>
						</button>
						<!-- #endif -->
						<!-- #ifdef H5 -->
						<view class="f1" @tap="shareH5">
							<image class="img" :src="pre_url+'/static/img/sharefriends.png'"/>
							<text class="t1">分享给好友</text>
						</view>
						<!-- #endif -->
						<view class="f2" @tap="showPoster">
							<image class="img" :src="pre_url+'/static/img/sharepic.png'"/>
							<text class="t1">生成分享海报</text>
						</view>
					</view>
				</view>
			</view>
		</view>
		
		<!-- 海报预览弹窗 -->
		<view class="posterDialog" v-if="showposter">
			<view class="main">
				<view class="close" @tap="posterDialogClose">✕</view>
				<view class="content">
					<image class="img" :src="posterpic" mode="widthFix" @tap="previewImage([posterpic], 0)"></image>
				</view>
			</view>
		</view>
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

	<!-- 登录弹窗 -->
	<login-popup ref="loginPopup" @login-success="onLoginSuccess"></login-popup>

	<loading v-if="loading"></loading>
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
			maxImages: 1, // 默认1张，由模板 max_ref_images 控制
			countOptions: [1, 2, 3, 4, 5, 6, 7, 8, 9],
			generationLimits: null, // 模型组图约束信息
			refImageOverflow: false, // 参考图超出限制
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
			// 余额/积分不足弹窗
			showInsufficientPopup: false,
			insufficientType: '',
			insufficientTitle: '',
			insufficientMsg: '',
			insufficientBtnText: '',
			insufficientExtra: {},
			// 积分支付模式
			scorePayEnabled: false,
			priceInScore: 0,
			scoreUnitName: '词元',
			// 分享赚佣金
			pid: 0,
			sharetypevisible: false,
			showposter: false,
			posterpic: '',
			pre_url: app.globalData.pre_url
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
		
		// 生成数量提示文案
		generationLimitHint() {
			var limits = this.generationLimits;
			if (!limits || !limits.supports_group) return '';
			var refCount = this.refImages ? this.refImages.length : 0;
			if (refCount === 0) {
				return '当前模型最多可生成 ' + limits.text_only_max_output + ' 张组图';
			} else if (refCount === 1) {
				return '上传1张参考图，最多可生成 ' + limits.single_image_max_output + ' 张';
			} else {
				var maxCount = (limits.input_output_sum_limit || 15) - refCount;
				if (maxCount < 1) return '';
				return '已上传 ' + refCount + ' 张参考图，最多还可生成 ' + maxCount + ' 张';
			}
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
			// 已废弃，使用组件默认上传地址
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
		this.$watch('refImages', function(newVal) {
			this.recalcCountOptions();
		}, { deep: true });
		this.generationType = parseInt(this.opt.type) || 1;
		// 支持 id 和 template_id 两种参数名
		this.selectedTemplateId = parseInt(this.opt.template_id || this.opt.id) || 0;
		
		// 解析 pid 参数（来自分享链接）
		var pid = parseInt(this.opt.pid) || 0;
		if (!pid && this.opt.scene) {
			// 解析 scene 格式：id_{id}-pid_{mid}
			var sceneStr = decodeURIComponent(this.opt.scene);
			var pidMatch = sceneStr.match(/pid_(\d+)/);
			if (pidMatch) {
				pid = parseInt(pidMatch[1]) || 0;
			}
			// 同时从 scene 中解析 template_id
			if (!this.selectedTemplateId) {
				var idMatch = sceneStr.match(/id_(\d+)/);
				if (idMatch) {
					this.selectedTemplateId = parseInt(idMatch[1]) || 0;
				}
			}
		}
		this.pid = pid;
		
		this.getdata();
	},
	
	onShareAppMessage: function() {
		var that = this;
		if (!that.detail) return {};
		var mid = app.globalData.mid || 0;
		return {
			title: that.detail.template_name || '来看看这个神奇的AI创作',
			imageUrl: that.detail.cover_image || '',
			path: '/pagesZ/generation/create?id=' + that.selectedTemplateId + '&type=' + that.generationType + '&pid=' + mid
		};
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
			
			// 加载模板详情
			app.get('ApiAivideo/scene_template_detail', {
				template_id: that.selectedTemplateId
			}, function(res) {
				that.loading = false;
				// 数据安全校验：确保res存在
				if (!res) {
					app.alert('网络异常，请稍后重试');
					return;
				}
				if (res.status == 1 && res.data) {
					that.applyTemplateDetail(res.data);
					that.isload = true;
				} else {
					var msg = res.msg || '获取模板详情失败';
					app.alert(msg);
				}
			});
		},
		
		applyTemplateDetail(detail) {
			var that = this;
			that.detail = detail;
			
			// 提示词可见性
			that.promptVisible = (detail.prompt_visible !== 0 && detail.prompt_visible !== '0');
			
			// 默认生成张数
			var defaultQuantity = parseInt(detail.output_quantity) || 1;
			if (defaultQuantity < 1) defaultQuantity = 1;
			
			// 最大上传数量，由模板配置控制，默认1
			var maxImages = parseInt(detail.max_ref_images) || 1;
			if (maxImages > 9) maxImages = 9;
			if (maxImages < 1) maxImages = 1;
			that.maxImages = maxImages;
			
			// 是否需要上传参考图（max_ref_images > 0 且模板要求参考图）
			that.needRefImage = (maxImages > 0 && detail.need_ref_image !== 0 && detail.need_ref_image !== '0');
			
			// ===== 组图约束：动态 countOptions =====
			var limits = (detail.model_capability && detail.model_capability.generation_limits) ? detail.model_capability.generation_limits : null;
			that.generationLimits = limits;
			
			if (limits && limits.supports_group) {
				// 支持组图，初始化时按无参考图计算
				var maxCount = limits.text_only_max_output || 15;
				that.updateCountOptions(maxCount);
				if (defaultQuantity > maxCount) defaultQuantity = maxCount;
			} else if (limits && !limits.supports_group && limits.max_total <= 1) {
				// 不支持组图，固定1张
				that.countOptions = [1];
				defaultQuantity = 1;
			} else {
				// 无约束信息，使用默认 [1..9]
				that.countOptions = [1, 2, 3, 4, 5, 6, 7, 8, 9];
				if (defaultQuantity > 9) defaultQuantity = 9;
			}
			that.quantity = defaultQuantity;
			
			// 默认提示词
			if (detail.prompt) {
				that.prompt = detail.prompt;
			}
			
			// 设置比例选项
			if (detail.model_capability && detail.model_capability.supported_ratios) {
				that.ratioOptions = detail.model_capability.supported_ratios;
			} else {
				that.ratioOptions = ['1:1','2:3','3:2','3:4','4:3','9:16','16:9','4:5','5:4','21:9'];
			}
			
			// 默认比例
			if (detail.default_params && detail.default_params.ratio) {
				that.ratio = detail.default_params.ratio;
			} else {
				that.ratio = '1:1';
			}
			
			// 重置质量为默认值
			that.quality = 'hd';
			
			// 证件照模板自动弹出拍照指引
			if (detail.is_id_photo && !that.idPhotoGuideShownMap[detail.id]) {
				that.showIdPhotoGuide = true;
			}
			
			// 积分支付信息
			that.scorePayEnabled = detail.score_pay_enabled || false;
			that.priceInScore = detail.price_in_score || 0;
			that.scoreUnitName = detail.score_unit_name || '词元';
			
			// 设置标题
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
				// 数据安全校验
				if (!res) {
					app.alert('网络异常，请稍后重试');
					return;
				}
				if (res.status == 1 && res.data) {
					that.applyTemplateDetail(res.data);
				} else {
					var msg = res.msg || '获取模板详情失败';
					app.alert(msg);
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
		
		selectCount(count) {
			this.quantity = count;
		},
		
		/**
		 * 根据最大可选数量更新 countOptions 数组
		 */
		updateCountOptions(maxCount) {
			if (maxCount < 1) maxCount = 1;
			var arr = [];
			for (var i = 1; i <= maxCount; i++) {
				arr.push(i);
			}
			this.countOptions = arr;
		},
		
		/**
		 * 参考图数量变化时重算可用生成数量
		 */
		recalcCountOptions() {
			var limits = this.generationLimits;
			if (!limits || !limits.supports_group) {
				this.refImageOverflow = false;
				return;
			}
			
			var refCount = this.refImages ? this.refImages.length : 0;
			var maxCount = 1;
			
			if (refCount === 0) {
				maxCount = limits.text_only_max_output || 15;
			} else if (refCount === 1) {
				maxCount = limits.single_image_max_output || 14;
			} else {
				maxCount = (limits.input_output_sum_limit || 15) - refCount;
			}
			
			if (maxCount < 1) {
				// 参考图过多，无法生成
				this.refImageOverflow = true;
				this.countOptions = [];
				return;
			}
			
			this.refImageOverflow = false;
			this.updateCountOptions(maxCount);
			
			// 当前 quantity 超出新的最大值时自动下调
			if (this.quantity > maxCount) {
				this.quantity = maxCount;
			}
		},
		
		selectRatio(r) {
			this.ratio = r;
		},
		
		selectQuality(q) {
			this.quality = q;
		},
		
		closeIdPhotoGuide() {
			this.showIdPhotoGuide = false;
		},
		
		confirmIdPhotoGuide() {
			var tplId = this.selectedTemplateId;
			this.idPhotoGuideShownMap[tplId] = true;
			this.showIdPhotoGuide = false;
		},
		
		closeInsufficientPopup() {
			this.showInsufficientPopup = false;
		},
		
		onInsufficientAction() {
			this.showInsufficientPopup = false;
			if (this.insufficientType == 'balance_insufficient') {
				uni.navigateTo({ url: '/pagesExt/money/recharge' });
			} else if (this.insufficientType == 'score_insufficient') {
				uni.navigateTo({ url: '/pagesExt/money/recharge' });
			}
		},
		
		/**
		 * 登录检查并执行操作
		 * @param {Function} callback - 登录成功后执行的回调函数
		 */
		checkLoginAndDo(callback) {
			var that = this;
			// 检查是否已登录
			if (!app.globalData.mid || app.globalData.mid == 0) {
				// 未登录，打开登录弹窗
				if (that.$refs.loginPopup) {
					that.$refs.loginPopup.open(callback);
				} else {
					// 降级处理：跳转到登录页
					var frompage = encodeURIComponent(app._fullurl());
					app.goto('/pages/index/login?frompage=' + frompage, 'navigate');
				}
				return false;
			}
			// 已登录，直接执行回调
			if (typeof callback === 'function') {
				callback();
			}
			return true;
		},
		
		/**
		 * 登录成功回调
		 */
		onLoginSuccess(res) {
			var that = this;
			// 更新全局用户信息
			if (res && res.data && res.data.mid) {
				app.globalData.mid = res.data.mid;
			}
			// 登录成功后会自动执行之前传入的callback，这里不需要额外处理
		},
		
		submitGeneration() {
			var that = this;
			
			if (that.submitting) return;
			if (!that.selectedTemplateId) return app.alert('请选择场景模板');
			
			if (that.needRefImage && that.refImages.length == 0) {
				return app.alert('请上传参考图片');
			}
			
			// 检查登录状态，未登录则弹出登录弹窗
			that.checkLoginAndDo(function() {
				that.doSubmitGeneration();
			});
		},
		
		/**
		 * 执行创建订单操作（已确认登录）
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
				bid: that.opt.bid || 0,
				pid: that.pid || 0
			};
			
			app.post('ApiAivideo/create_generation_order', postData, function(res) {
				app.showLoading(false);
				that.submitting = false;
				
				// 数据安全校验
				if (!res) {
					app.alert('网络异常，请稍后重试');
					return;
				}
				
				if (res.status == 1 && res.data) {
					var data = res.data;
					
					if (data.need_pay) {
						// 需要支付
						uni.navigateTo({
							url: '/pages/pay/pay?ordernum=' + data.ordernum + '&tablename=generation'
						});
					} else {
						// 免费/积分已支付，直接跳到结果页
						uni.redirectTo({
							url: '/pagesZ/generation/result?order_id=' + data.order_id
						});
					}
				} else {
					// 处理余额/积分不足
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
						var extra = res.extra || {};
						that.showInsufficientPopup = true;
						that.insufficientType = 'balance_insufficient';
						that.insufficientTitle = '余额不足';
						that.insufficientMsg = '当前余额 ￥' + (extra.current_balance || 0) + '，还需 ￥' + (extra.need_amount || 0);
						that.insufficientBtnText = '去充值';
						that.insufficientExtra = extra;
					} else {
						var msg = res.msg || '创建订单失败';
						app.alert(msg);
					}
				}
			});
		},
		
		// ===== 分享功能 =====
		openSharePopup() {
			this.sharetypevisible = true;
		},
		closeSharePopup() {
			this.sharetypevisible = false;
		},
		shareH5() {
			app.error('点击右上角发送给好友或分享到朋友圈');
			this.sharetypevisible = false;
		},
		shareApp() {
			var that = this;
			var mid = app.globalData.mid || 0;
			uni.showActionSheet({
				itemList: ['发送给微信好友', '分享到微信朋友圈'],
				success: function(res) {
					if (res.tapIndex >= 0) {
						var scene = res.tapIndex == 0 ? 'WXSceneSession' : 'WXSenceTimeline';
						uni.share({
							provider: 'weixin',
							type: 0,
							scene: scene,
							title: that.detail.template_name || 'AI创作模板',
							href: app.globalData.pre_url + '/h5/' + app.globalData.aid + '.html#/pagesZ/generation/create?id=' + that.selectedTemplateId + '&type=' + that.generationType + '&pid=' + mid,
							imageUrl: that.detail.cover_image || ''
						});
					}
				}
			});
			that.sharetypevisible = false;
		},
		showPoster() {
			var that = this;
			that.showposter = true;
			that.sharetypevisible = false;
			app.showLoading('努力生成中');
			app.post('ApiAivideo/getposter', { template_id: that.selectedTemplateId }, function(data) {
				app.showLoading(false);
				// 数据安全校验
				if (!data) {
					app.alert('网络异常，请稍后重试');
					that.showposter = false;
					return;
				}
				if (data.status == 0) {
					var msg = data.msg || '生成海报失败';
					app.alert(msg);
					that.showposter = false;
				} else if (data.poster) {
					that.posterpic = data.poster;
				} else {
					app.alert('生成海报失败');
					that.showposter = false;
				}
			});
		},
		posterDialogClose() {
			this.showposter = false;
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


/* 横向滚动选择 - 通用 */
.option-scroll { margin-top: 0; width: 100%; }
.option-scroll-inner { display: inline-flex; gap: 20rpx; padding: 4rpx 4rpx; }

/* 生成张数/比例/质量 - 横向滑动 chip */
.count-chip { display: inline-flex; align-items: center; justify-content: center; padding: 14rpx 36rpx; border-radius: 40rpx; border: 2rpx solid #F0EDF5; background: #fafafa; flex-shrink: 0; transition: all 0.2s; }
.count-chip.active { border-color: #91C2FF; background: rgba(181,216,254,0.1); }

/* chip 文字 */
.chip-label { font-size: 26rpx; color: #666; white-space: nowrap; }
.count-chip.active .chip-label { color: #91C2FF; font-weight: bold; }

/* 生成数量提示 */
.generation-limit-hint { margin-top: 12rpx; padding: 0 8rpx; }
.generation-limit-hint .hint-text { font-size: 24rpx; color: #999; }
.generation-limit-warning { font-size: 26rpx; color: #F59E0B; padding: 16rpx 20rpx; background: #FFF8E1; border-radius: 12rpx; text-align: center; }

.bottom-bar { position: fixed; bottom: 0; left: 0; right: 0; background: #fff; display: flex; align-items: center; padding: 20rpx 30rpx; box-shadow: 0 -4rpx 20rpx rgba(0,0,0,0.05); padding-bottom: calc(20rpx + env(safe-area-inset-bottom)); }
.price-display { flex: 1; }
.total-label { font-size: 28rpx; color: #666; }
.total-price { font-size: 40rpx; color: #91C2FF; font-weight: bold; }
.btn-primary { background: linear-gradient(135deg, #91C2FF, #B5D8FE); color: #fff; font-size: 32rpx; font-weight: bold; padding: 24rpx 60rpx; border-radius: 40rpx; box-shadow: 0 8rpx 24rpx rgba(145,194,255,0.3); }
.btn-primary.disabled { opacity: 0.5; box-shadow: none; }

/* 积分价格 */
.score-price { color: #91C2FF; }

/* 余额/积分不足弹窗 */
.insufficient-mask { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.4); z-index: 9999; display: flex; align-items: center; justify-content: center; }
.insufficient-popup { width: 560rpx; background: #fff; border-radius: 24rpx; padding: 50rpx 40rpx; text-align: center; }
.insufficient-icon { font-size: 80rpx; margin-bottom: 20rpx; }
.insufficient-title { font-size: 36rpx; font-weight: bold; color: #555555; margin-bottom: 16rpx; }
.insufficient-msg { font-size: 28rpx; color: #666; line-height: 1.6; margin-bottom: 40rpx; }
.insufficient-btn { background: linear-gradient(135deg, #91C2FF, #B5D8FE); color: #fff; font-size: 32rpx; font-weight: bold; padding: 24rpx 0; border-radius: 40rpx; margin-bottom: 20rpx; box-shadow: 0 8rpx 24rpx rgba(145,194,255,0.3); }
.insufficient-close { font-size: 28rpx; color: #999; padding: 10rpx 0; }

/* 证件照拍照指引弹窗 */
.guide-mask { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.4); z-index: 999; display: flex; align-items: center; justify-content: center; }

/* 佣金提示条 */
.commission-bar { display: flex; align-items: center; justify-content: space-between; margin: 20rpx; padding: 20rpx 24rpx; background: linear-gradient(135deg, #FFF8E1, #FFF3CD); border-radius: 16rpx; }
.commission-bar-left { display: flex; align-items: center; flex: 1; }
.commission-bar-icon { font-size: 32rpx; margin-right: 12rpx; }
.commission-bar-text { font-size: 26rpx; color: #F59E0B; font-weight: bold; }
.commission-bar-btn { font-size: 24rpx; color: #fff; background: linear-gradient(135deg, #F59E0B, #F97316); padding: 10rpx 24rpx; border-radius: 30rpx; flex-shrink: 0; margin-left: 16rpx; }

/* 分享弹窗 */
.popup__container { position: fixed; top: 0; left: 0; right: 0; bottom: 0; z-index: 9998; }
.popup__overlay { position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.4); }
.popup__modal { position: absolute; bottom: 0; left: 0; right: 0; background: #fff; border-radius: 24rpx 24rpx 0 0; padding: 30rpx; padding-bottom: calc(30rpx + env(safe-area-inset-bottom)); z-index: 9999; }
.popup__content { }
.sharetypecontent { display: flex; justify-content: center; gap: 80rpx; padding: 30rpx 0; }
.sharetypecontent .f1, .sharetypecontent .f2 { display: flex; flex-direction: column; align-items: center; background: none; border: none; padding: 0; line-height: normal; }
.sharetypecontent .f1::after, .sharetypecontent .f2::after { border: none; }
.sharetypecontent .img { width: 96rpx; height: 96rpx; margin-bottom: 16rpx; }
.sharetypecontent .t1 { font-size: 24rpx; color: #666; }

/* 海报弹窗 */
.posterDialog { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.7); z-index: 9999; display: flex; align-items: center; justify-content: center; }
.posterDialog .main { width: 580rpx; position: relative; }
.posterDialog .close { position: absolute; top: -60rpx; right: 0; color: #fff; font-size: 40rpx; padding: 10rpx; z-index: 10; }
.posterDialog .content { border-radius: 16rpx; overflow: hidden; }
.posterDialog .content .img { width: 100%; }
.guide-popup { width: 620rpx; background: #fff; border-radius: 24rpx; padding: 40rpx 36rpx; }
.guide-title { font-size: 34rpx; font-weight: bold; color: #555555; text-align: center; margin-bottom: 10rpx; }
.guide-subtitle { font-size: 26rpx; color: #999; text-align: center; margin-bottom: 30rpx; }
.guide-content { display: flex; gap: 20rpx; margin-bottom: 30rpx; }
.guide-col { flex: 1; }
.guide-label { font-size: 26rpx; font-weight: bold; margin-bottom: 12rpx; padding: 8rpx 0; text-align: center; border-radius: 8rpx; }
.guide-label.correct { color: #52c41a; background: #f6ffed; }
.guide-label.wrong { color: #FFA0B8; background: #fff2f0; }
.guide-tips { }
.guide-tip-item { display: block; font-size: 24rpx; color: #666; line-height: 1.8; }
.guide-btn { background: linear-gradient(135deg, #91C2FF, #B5D8FE); color: #fff; font-size: 30rpx; font-weight: bold; text-align: center; padding: 22rpx 0; border-radius: 40rpx; box-shadow: 0 8rpx 24rpx rgba(145,194,255,0.3); }
</style>
