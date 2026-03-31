<template>
<view class="container">
	<block v-if="isload">
		<!-- 模板选择区域 -->
		<view class="section">
			<view class="section-title">选择模板</view>
			<view class="search-box">
				<text class="iconfont icon-search search-icon" style="font-size:28rpx;color:#999;"></text>
				<input class="search-input" v-model="searchKeyword" placeholder="搜索模板名称" @input="onSearchInput" />
				<text v-if="searchKeyword" class="search-clear" @tap="searchKeyword = ''">×</text>
			</view>
			<scroll-view class="template-scroll" scroll-x :show-scrollbar="false" v-if="filteredTemplateList.length > 0">
				<view class="template-scroll-inner">
					<view class="template-card-item" :class="{active: selectedTemplateId == item.id}" v-for="(item, idx) in filteredTemplateList" :key="item.id" @tap="onSelectTemplate(item)">
						<image :src="item.cover_image || '/static/img/placeholder.png'" class="template-card-cover" mode="aspectFill"></image>
						<text class="template-card-name">{{item.template_name}}</text>
						<text class="template-card-price" v-if="scorePayEnabled">{{item.price_in_score || '-'}} 积分</text>
						<text class="template-card-price" v-else>¥{{item.price}}</text>
					</view>
				</view>
			</scroll-view>
			<view class="empty-template" v-else>
				<text class="empty-text">{{searchKeyword ? '未找到匹配模板' : '暂无可用模板'}}</text>
			</view>
		</view>
		
		<!-- 提示词输入（仅当 prompt_visible=1 时显示） -->
		<view class="section" v-if="promptVisible">
			<view class="section-title">
				<text class="required">*</text> 提示词
			</view>
			<textarea class="prompt-input" v-model="prompt" placeholder="请输入图像/视频描述，越详细生成效果越好" :maxlength="2000" auto-height></textarea>
			<view class="char-count">{{prompt.length}}/2000</view>
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
				:uploadUrl="generationUploadUrl"
				:columns="4"
				:enableCamera="true"
				:enableAlbum="true"
				:enableChat="true"
				cameraPosition="back"
			></uni-image-upload>
		</view>
		
		<!-- 生成数量（图片生成） -->
		<view class="section" v-if="generationType == 1">
			<view class="section-title">生成张数</view>
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
			<view class="section-title">输出比例</view>
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
			<view class="section-title">输出质量选择</view>
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
				<text class="total-price score-price" v-if="scorePayEnabled">{{totalPriceInScore}} 积分</text>
				<text class="total-price" v-else>¥{{totalPrice}}</text>
			</view>
			<view class="btn-primary" :class="{disabled: submitting || !selectedTemplateId}" @tap="submitGeneration">
				{{submitting ? '提交中...' : '立即生成'}}
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
			templateList: [],
			searchKeyword: '',
			selectedTemplateId: 0,
			ratio: '1:1',
			quality: 'hd',
			ratioOptions: [],
			qualityOptions: [
				{ label: '标准画质', value: 'standard' },
				{ label: '高清画质', value: 'hd' },
				{ label: '超清画质', value: 'ultra' }
			],
			promptVisible: true,
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
			priceInScore: 0
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
		filteredTemplateList() {
			if (!this.searchKeyword) return this.templateList;
			var kw = this.searchKeyword.toLowerCase();
			return this.templateList.filter(function(item) {
				return (item.template_name || '').toLowerCase().indexOf(kw) !== -1;
			});
		},
		idPhotoTypeName() {
			if (!this.detail || !this.detail.is_id_photo) return '';
			return this.detail.id_photo_type_name || '';
		},
		generationUploadUrl() {
			return app.globalData.pre_url + '/Upload/upload';
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
		// 支持 id 和 template_id 两种参数名
		this.selectedTemplateId = parseInt(this.opt.template_id || this.opt.id) || 0;
		this.getdata();
	},
	
	methods: {
		getdata() {
			var that = this;
			that.loading = true;
			
			var loadedCount = 0;
			var totalLoads = 2;
			var checkDone = function() {
				loadedCount++;
				if (loadedCount >= totalLoads) {
					that.loading = false;
					that.isload = true;
				}
			};
			
			// 并行请求：加载模板列表
			app.get('ApiAivideo/scene_template_list', {
				bid: that.opt.bid || 0,
				generation_type: that.generationType
			}, function(res) {
				if (res.status == 1 && res.data && res.data.list) {
					that.templateList = res.data.list;
					// 若URL未传入template_id，默认选中第一项
					if (!that.selectedTemplateId && that.templateList.length > 0) {
						that.selectedTemplateId = that.templateList[0].id;
					}
				}
				checkDone();
			});
			
			// 并行请求：加载当前模板详情
			if (that.selectedTemplateId) {
				app.get('ApiAivideo/scene_template_detail', {
					template_id: that.selectedTemplateId
				}, function(res) {
					if (res.status == 1) {
						that.applyTemplateDetail(res.data);
					} else {
						app.alert(res.msg);
					}
					checkDone();
				});
			} else {
				// 没有指定模板ID，等待列表加载后自动选中第一个并加载详情
				checkDone();
				// 在列表加载完后触发详情加载
				var checkList = setInterval(function() {
					if (that.templateList.length > 0 && that.selectedTemplateId > 0) {
						clearInterval(checkList);
						that.loadTemplateDetail(that.selectedTemplateId);
					}
				}, 100);
				setTimeout(function() { clearInterval(checkList); }, 5000);
			}
		},
		
		applyTemplateDetail(detail) {
			var that = this;
			that.detail = detail;
			
			// 提示词可见性
			that.promptVisible = (detail.prompt_visible !== 0 && detail.prompt_visible !== '0');
			
			// 默认生成张数
			var defaultQuantity = parseInt(detail.output_quantity) || 1;
			if (defaultQuantity > 9) defaultQuantity = 9;
			if (defaultQuantity < 1) defaultQuantity = 1;
			that.quantity = defaultQuantity;
			
			// 最大上传数量，由模板配置控制，默认1
			var maxImages = parseInt(detail.max_ref_images) || 1;
			if (maxImages > 9) maxImages = 9;
			if (maxImages < 1) maxImages = 1;
			that.maxImages = maxImages;
			
			// 是否需要上传参考图（max_ref_images > 0 且模板要求参考图）
			that.needRefImage = (maxImages > 0 && detail.need_ref_image !== 0 && detail.need_ref_image !== '0');
			
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
			
			// 设置标题
			var title = that.generationType == 1 ? '图片生成' : '视频生成';
			uni.setNavigationBarTitle({ title: title });
		},
		
		loadTemplateDetail(templateId) {
			var that = this;
			app.showLoading('加载中');
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
		
		onSelectTemplate(item) {
			if (this.selectedTemplateId == item.id) return;
			this.selectedTemplateId = item.id;
			this.loadTemplateDetail(item.id);
		},
		
		onSearchInput() {
			// 前端筛选由 computed filteredTemplateList 自动处理
		},
		
		selectCount(count) {
			this.quantity = count;
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
		
		submitGeneration() {
			var that = this;
			
			if (that.submitting) return;
			if (!that.selectedTemplateId) return app.alert('请选择场景模板');
			
			// 验证（当提示词可见时才校验）
			if (that.promptVisible) {
				if (!that.prompt || that.prompt.trim().length < 2) {
					return app.alert('请填写提示词（至少2个字符）');
				}
			}
			
			if (that.needRefImage && that.refImages.length == 0) {
				return app.alert('请上传参考图片');
			}
			
			that.submitting = true;
			app.showLoading('提交中');
			
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
						that.insufficientTitle = '积分不足';
						that.insufficientMsg = '当前可用积分 ' + (extra.current_score || 0) + '，本次需要 ' + (extra.required_score || 0) + ' 积分';
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
						app.alert(res.msg);
					}
				}
			});
		}
	}
};
</script>

<style>
.container { background: #f5f5f5; min-height: 100vh; }

/* 模板选择器 - 搜索框 */
.search-box { display: flex; align-items: center; background: #f5f5f5; border-radius: 40rpx; padding: 12rpx 24rpx; margin-bottom: 20rpx; }
.search-icon { margin-right: 12rpx; }
.search-input { flex: 1; font-size: 26rpx; color: #333; height: 48rpx; }
.search-clear { font-size: 36rpx; color: #999; padding: 0 8rpx; }

/* 模板选择器 - 横向滚动卡片 */
.template-scroll { width: 100%; }
.template-scroll-inner { display: inline-flex; gap: 20rpx; padding: 4rpx 4rpx; }
.template-card-item { display: flex; flex-direction: column; align-items: center; width: 180rpx; flex-shrink: 0; border-radius: 16rpx; border: 2rpx solid #eee; background: #fafafa; padding: 12rpx; transition: all 0.2s; }
.template-card-item.active { border-color: #FF6B00; background: #FFF8F2; }
.template-card-cover { width: 156rpx; height: 156rpx; border-radius: 12rpx; background: #f0f0f0; }
.template-card-name { font-size: 22rpx; color: #333; margin-top: 10rpx; width: 156rpx; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; text-align: center; }
.template-card-item.active .template-card-name { color: #FF6B00; font-weight: bold; }
.template-card-price { font-size: 24rpx; color: #FF6B00; font-weight: bold; margin-top: 4rpx; }
.empty-template { padding: 40rpx 0; text-align: center; }
.empty-text { font-size: 26rpx; color: #999; }

.section { background: #fff; margin: 20rpx; border-radius: 16rpx; padding: 30rpx; }
.section-title { font-size: 30rpx; font-weight: bold; color: #333; margin-bottom: 20rpx; }
.required { color: #FF4D4F; margin-right: 4rpx; }

.prompt-input { width: 100%; min-height: 200rpx; font-size: 28rpx; line-height: 1.6; color: #333; padding: 0; background: none; }
.char-count { text-align: right; font-size: 24rpx; color: #999; margin-top: 12rpx; }



/* 横向滚动选择 - 通用 */
.option-scroll { margin-top: 0; width: 100%; }
.option-scroll-inner { display: inline-flex; gap: 20rpx; padding: 4rpx 4rpx; }

/* 生成张数/比例/质量 - 横向滑动 chip */
.count-chip { display: inline-flex; align-items: center; justify-content: center; padding: 14rpx 36rpx; border-radius: 40rpx; border: 2rpx solid #eee; background: #fafafa; flex-shrink: 0; transition: all 0.2s; }
.count-chip.active { border-color: #FF6B00; background: #FFF8F2; }

/* chip 文字 */
.chip-label { font-size: 26rpx; color: #666; white-space: nowrap; }
.count-chip.active .chip-label { color: #FF6B00; font-weight: bold; }

.bottom-bar { position: fixed; bottom: 0; left: 0; right: 0; background: #fff; display: flex; align-items: center; padding: 20rpx 30rpx; box-shadow: 0 -2rpx 10rpx rgba(0,0,0,0.05); padding-bottom: calc(20rpx + env(safe-area-inset-bottom)); }
.price-display { flex: 1; }
.total-label { font-size: 28rpx; color: #666; }
.total-price { font-size: 40rpx; color: #FF6B00; font-weight: bold; }
.btn-primary { background: linear-gradient(135deg, #FF6B00, #FF9500); color: #fff; font-size: 32rpx; font-weight: bold; padding: 24rpx 60rpx; border-radius: 44rpx; }
.btn-primary.disabled { opacity: 0.6; }

/* 积分价格 */
.score-price { color: #FF6B00; }

/* 余额/积分不足弹窗 */
.insufficient-mask { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.6); z-index: 9999; display: flex; align-items: center; justify-content: center; }
.insufficient-popup { width: 560rpx; background: #fff; border-radius: 24rpx; padding: 50rpx 40rpx; text-align: center; }
.insufficient-icon { font-size: 80rpx; margin-bottom: 20rpx; }
.insufficient-title { font-size: 36rpx; font-weight: bold; color: #333; margin-bottom: 16rpx; }
.insufficient-msg { font-size: 28rpx; color: #666; line-height: 1.6; margin-bottom: 40rpx; }
.insufficient-btn { background: linear-gradient(135deg, #FF6B00, #FF9500); color: #fff; font-size: 32rpx; font-weight: bold; padding: 24rpx 0; border-radius: 44rpx; margin-bottom: 20rpx; }
.insufficient-close { font-size: 28rpx; color: #999; padding: 10rpx 0; }

/* 证件照拍照指引弹窗 */
.guide-mask { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.6); z-index: 999; display: flex; align-items: center; justify-content: center; }
.guide-popup { width: 620rpx; background: #fff; border-radius: 24rpx; padding: 40rpx 36rpx; }
.guide-title { font-size: 34rpx; font-weight: bold; color: #333; text-align: center; margin-bottom: 10rpx; }
.guide-subtitle { font-size: 26rpx; color: #999; text-align: center; margin-bottom: 30rpx; }
.guide-content { display: flex; gap: 20rpx; margin-bottom: 30rpx; }
.guide-col { flex: 1; }
.guide-label { font-size: 26rpx; font-weight: bold; margin-bottom: 12rpx; padding: 8rpx 0; text-align: center; border-radius: 8rpx; }
.guide-label.correct { color: #52c41a; background: #f6ffed; }
.guide-label.wrong { color: #ff4d4f; background: #fff2f0; }
.guide-tips { }
.guide-tip-item { display: block; font-size: 24rpx; color: #666; line-height: 1.8; }
.guide-btn { background: linear-gradient(135deg, #FF6B00, #FF9500); color: #fff; font-size: 30rpx; font-weight: bold; text-align: center; padding: 22rpx 0; border-radius: 44rpx; }
</style>
