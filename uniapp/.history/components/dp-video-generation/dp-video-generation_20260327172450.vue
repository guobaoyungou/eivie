<template>
<view class="dp-video-generation" :style="{
	backgroundColor:params.bgcolor,
	margin:params.margin_y*2.2+'rpx '+params.margin_x*2.2+'rpx 0',
	padding:params.padding_y*2.2+'rpx '+params.padding_x*2.2+'rpx',
	width:'calc(100% - '+params.margin_x*2.2*2+'rpx)'
}">
	<!--123排-->
	<dp-product-item v-if="params.style=='1' || params.style=='2' || params.style=='3'" :showstyle="params.style" :data="data" :saleimg="params.saleimg" :showname="params.showname" :showprice="params.showprice" :showsales="params.showsales" :showcart="params.showcart" :cartimg="params.cartimg" :carttext="params.carttext" idfield="proid" :menuindex="menuindex" :probgcolor="params.probgcolor" :params="params" saleslabel="已用" covertype="video" :cover_ratio="coverRatio" :cover_radius="coverRadius" :card_radius="cardRadius" :btn_position="btnPosition" :card_gap="cardGap" :info_padding="infoPadding" detailurl="/pagesZ/generation/create?type=2"></dp-product-item>
	<!--横排-->
	<dp-product-itemlist v-if="params.style=='list'" :data="data" :saleimg="params.saleimg" :showname="params.showname" :showprice="params.showprice" :showsales="params.showsales" :showcart="params.showcart" :cartimg="params.cartimg" :carttext="params.carttext" idfield="proid" :menuindex="menuindex" :probgcolor="params.probgcolor" saleslabel="已用" covertype="video" :cover_ratio="coverRatio" :cover_radius="coverRadius" :card_radius="cardRadius" :btn_position="btnPosition" :info_padding="infoPadding" detailurl="/pagesZ/generation/create?type=2"></dp-product-itemlist>
	<!--左右滑动-->
	<dp-product-itemline v-if="params.style=='line'" :data="data" :saleimg="params.saleimg" :showname="params.showname" :showprice="params.showprice" :showsales="params.showsales" :showcart="params.showcart" :cartimg="params.cartimg" :carttext="params.carttext" idfield="proid" :menuindex="menuindex" :probgcolor="params.probgcolor" saleslabel="已用" covertype="video" :cover_ratio="coverRatio" :cover_radius="coverRadius" :card_radius="cardRadius" :btn_position="btnPosition" :info_padding="infoPadding" detailurl="/pagesZ/generation/create?type=2"></dp-product-itemline>
	<!--瀑布流-->
	<dp-product-waterfall v-if="params.style=='waterfall'" :list="data" :saleimg="params.saleimg" :showname="params.showname" :showprice="params.showprice" :showsales="params.showsales" :showcart="params.showcart" :cartimg="params.cartimg" :carttext="params.carttext" idfield="proid" :menuindex="menuindex" :probgcolor="params.probgcolor" saleslabel="已用" covertype="video" :cover_ratio="coverRatio" :cover_radius="coverRadius" :card_radius="cardRadius" :btn_position="btnPosition" :card_gap="cardGap" :info_padding="infoPadding" detailurl="/pagesZ/generation/create?type=2"></dp-product-waterfall>

	<!--grid7：横排7列自适应-->
	<view class="grid7-container" v-if="params.style=='grid7'">
		<view class="grid7-card" v-for="(item, index) in data" :key="item.id || index"
			:style="{borderRadius: cardRadius + 'rpx'}"
			@mouseenter="onCardHover(index, true)"
			@mouseleave="onCardHover(index, false)"
			@tap="onCardTap(index)">
			<view class="grid7-cover" :style="{paddingBottom: grid7CoverPadding, borderRadius: cardRadius + 'rpx ' + cardRadius + 'rpx 0 0'}">
				<image class="grid7-cover-img" :src="item.pic" mode="aspectFill" :style="{borderRadius: cardRadius + 'rpx ' + cardRadius + 'rpx 0 0'}"></image>
				<!-- 视频播放图标 -->
				<view class="grid7-play-icon">
					<text class="grid7-play-triangle">▶</text>
				</view>
			</view>
			<view class="grid7-info">
				<view class="grid7-name">{{item.name}}</view>
				<view class="grid7-price">¥{{item.sell_price || item.price || '0.00'}}</view>
			</view>
			<!-- 悬浮"制作同款"按钮 -->
			<view class="grid7-hover-btn" :class="{'grid7-hover-show': hoverIndex === index || isMiniProgram}" @tap.stop="onMakeSame(index)">
				<text class="grid7-hover-text">制作同款</text>
			</view>
		</view>
	</view>

	<!-- 底部任务弹窗遮罩 -->
	<view class="task-popup-mask" v-if="showPopup" @tap="closePopup"></view>
	<!-- 底部任务弹窗 -->
	<view class="task-popup" :class="{'task-popup-show': showPopup, 'task-popup-expanded': isExpanded}" v-if="showPopup">
		<!-- 第一行：类型标题栏 -->
		<view class="popup-row popup-header">
			<text class="popup-type-title">{{popupTypeTitle}}</text>
			<view class="popup-header-btns">
				<view class="popup-btn-expand" @tap="toggleExpand">
					<text class="popup-btn-icon">{{isExpanded ? '⇩' : '⇧'}}</text>
				</view>
				<view class="popup-btn-close" @tap="closePopup">
					<text class="popup-btn-icon">✕</text>
				</view>
			</view>
		</view>
		<!-- 第二行：提示词输入 -->
		<view class="popup-row popup-prompt" v-if="popupPromptVisible">
			<textarea class="popup-prompt-input" v-model="popupPrompt" placeholder="请输入视频描述" :maxlength="2000" :auto-height="!isExpanded" :style="isExpanded ? 'height:200rpx;' : ''"></textarea>
			<text class="popup-prompt-count">{{popupPrompt.length}}/2000</text>
		</view>
		<!-- 第三行：图片上传 -->
		<view class="popup-row popup-upload" v-if="popupNeedRefImage">
			<view class="popup-upload-label">参考图片</view>
			<view class="popup-upload-area">
				<view class="popup-upload-item" v-for="(img, imgIdx) in popupRefImages" :key="imgIdx">
					<image class="popup-upload-thumb" :src="img" mode="aspectFill"></image>
					<view class="popup-upload-del" @tap.stop="removeRefImage(imgIdx)">✕</view>
				</view>
				<view class="popup-upload-add" v-if="popupRefImages.length < popupMaxRefImages" @tap="chooseRefImage">
					<text class="popup-upload-add-icon">+</text>
				</view>
			</view>
		</view>
		<!-- 第四行：参数选择 + 生成按钮 -->
		<view class="popup-row popup-params">
			<view class="popup-param-group">
				<!-- 比例选择 -->
				<view class="popup-param-item" @tap="toggleRatioPanel">
					<text class="popup-param-label">比例</text>
					<text class="popup-param-value">{{popupSelectedRatio}}</text>
					<text class="popup-param-arrow">▼</text>
				</view>
			</view>
			<view class="popup-generate-btn" :class="{'popup-generate-disabled': popupSubmitting}" @tap="submitPopupGeneration">
				<text class="popup-generate-text">{{popupSubmitting ? '提交中...' : '立即生成'}}</text>
			</view>
		</view>
		<!-- 比例下拉面板 -->
		<view class="popup-dropdown" v-if="showRatioPanel">
			<view class="popup-dropdown-mask" @tap="showRatioPanel=false"></view>
			<view class="popup-dropdown-content">
				<view class="popup-dropdown-title">选择比例</view>
				<view class="popup-dropdown-grid">
					<view class="popup-dropdown-item" :class="{'popup-dropdown-active': popupSelectedRatio==r}" v-for="(r, ri) in popupRatioOptions" :key="ri" @tap="selectPopupRatio(r)">{{r}}</view>
				</view>
			</view>
		</view>
	</view>
</view>
</template>
<script>
var app = getApp();
	export default {
		props: {
			menuindex:{default:-1},
			params:{},
			data:{}
		},
		data() {
			return {
				hoverIndex: -1,
				showPopup: false,
				isExpanded: false,
				templateDetail: null,
				popupPrompt: '',
				popupRefImages: [],
				popupSelectedModelId: 0,
				popupSelectedRatio: '16:9',
				popupSubmitting: false,
				popupPromptVisible: true,
				popupNeedRefImage: false,
				popupMaxRefImages: 1,
				popupRatioOptions: ['1:1','16:9','9:16','4:3','3:4'],
				showRatioPanel: false,
				currentTemplateId: 0
			};
		},
		computed: {
			// 封面比例，默认3:4
			coverRatio() {
				return this.params.cover_ratio || '3:4';
			},
			// 封面圆角，默认8rpx
			coverRadius() {
				return this.params.cover_radius !== undefined ? this.params.cover_radius : 8;
			},
			// 卡片圆角，默认8rpx
			cardRadius() {
				return this.params.card_radius !== undefined ? this.params.card_radius : 8;
			},
			// 按钮位置，默认bottom-right
			btnPosition() {
				return this.params.btn_position || 'bottom-right';
			},
			// 卡片间距，默认12rpx
			cardGap() {
				return this.params.card_gap !== undefined ? this.params.card_gap : 12;
			},
			// 信息区内边距，默认12rpx
			infoPadding() {
				return this.params.info_padding !== undefined ? this.params.info_padding : 12;
			},
			grid7CoverPadding() {
				var ratio = this.coverRatio;
				var map = {'1:1':'100%','4:3':'75%','3:4':'133.33%','16:9':'56.25%','9:16':'177.78%'};
				return map[ratio] || '133.33%';
			},
			popupTypeTitle() {
				return '视频生成';
			},
			isMiniProgram() {
				// #ifdef MP
				return true;
				// #endif
				// #ifndef MP
				return false;
				// #endif
			}
		},
		methods: {
			onCardHover(index, isHover) {
				this.hoverIndex = isHover ? index : -1;
			},
			onCardTap(index) {
				if (this.isMiniProgram) {
					this.onMakeSame(index);
				}
			},
			onMakeSame(index) {
				var that = this;
				var item = that.data[index];
				if (!item) return;
				var templateId = item.proid || item.id;
				// 直接跳转到视频生成页面
				app.goto('/pagesZ/generation/create?type=2&template_id=' + templateId);
			},
			applyTemplateToPopup(detail) {
				this.templateDetail = detail;
				this.popupPrompt = detail.prompt || (detail.default_params && detail.default_params.prompt) || '';
				this.popupPromptVisible = detail.prompt_visible !== 0 && detail.prompt_visible !== '0';
				this.popupRefImages = [];
				var maxImg = parseInt(detail.max_ref_images) || 1;
				if (maxImg > 9) maxImg = 9;
				if (maxImg < 1) maxImg = 1;
				this.popupMaxRefImages = maxImg;
				this.popupNeedRefImage = (maxImg > 0 && detail.need_ref_image !== 0 && detail.need_ref_image !== '0');
				if (detail.model_capability && detail.model_capability.supported_ratios) {
					this.popupRatioOptions = detail.model_capability.supported_ratios;
				} else {
					this.popupRatioOptions = ['1:1','16:9','9:16','4:3','3:4'];
				}
				if (detail.default_params && detail.default_params.ratio) {
					this.popupSelectedRatio = detail.default_params.ratio;
				} else {
					this.popupSelectedRatio = '16:9';
				}
				if (detail.model_capability && detail.model_capability.model_id) {
					this.popupSelectedModelId = detail.model_capability.model_id;
				}
			},
			closePopup() {
				this.showPopup = false;
				this.isExpanded = false;
				this.showRatioPanel = false;
			},
			toggleExpand() {
				this.isExpanded = !this.isExpanded;
			},
			toggleRatioPanel() {
				this.showRatioPanel = !this.showRatioPanel;
			},
			selectPopupRatio(r) {
				this.popupSelectedRatio = r;
				this.showRatioPanel = false;
			},
			chooseRefImage() {
				var that = this;
				var remaining = that.popupMaxRefImages - that.popupRefImages.length;
				if (remaining <= 0) return;
				uni.chooseImage({
					count: remaining,
					sizeType: ['compressed'],
					sourceType: ['album', 'camera'],
					success: function(res) {
						var files = res.tempFilePaths;
						for (var i = 0; i < files.length; i++) {
							if (that.popupRefImages.length < that.popupMaxRefImages) {
								that.uploadRefImage(files[i]);
							}
						}
					}
				});
			},
			uploadRefImage(tempPath) {
				var that = this;
				var uploadUrl = app.globalData.pre_url + '/Upload/upload';
				uni.uploadFile({
					url: uploadUrl,
					filePath: tempPath,
					name: 'file',
					success: function(uploadRes) {
						try {
							var data = typeof uploadRes.data === 'string' ? JSON.parse(uploadRes.data) : uploadRes.data;
							if (data.status == 1 && data.url) {
								that.popupRefImages.push(data.url);
							} else {
								that.popupRefImages.push(tempPath);
							}
						} catch(e) {
							that.popupRefImages.push(tempPath);
						}
					},
					fail: function() {
						that.popupRefImages.push(tempPath);
					}
				});
			},
			removeRefImage(idx) {
				this.popupRefImages.splice(idx, 1);
			},
			submitPopupGeneration() {
				var that = this;
				if (that.popupSubmitting) return;
				// 登录检测
				if (!app.globalData.mid || app.globalData.mid == 0) {
					var currentPage = encodeURIComponent('/pages/index/index');
					app.goto('/pages/index/login?frompage=' + currentPage);
					return;
				}
				// 校验
				if (that.popupPromptVisible && (!that.popupPrompt || that.popupPrompt.trim().length < 2)) {
					app.alert('请填写提示词（至少2个字符）');
					return;
				}
				if (that.popupNeedRefImage && that.popupRefImages.length == 0) {
					app.alert('请上传参考图片');
					return;
				}
				that.popupSubmitting = true;
				app.showLoading('提交中');
				var postData = {
					template_id: that.currentTemplateId,
					generation_type: 2,
					prompt: that.popupPromptVisible ? that.popupPrompt : (that.templateDetail && that.templateDetail.prompt ? that.templateDetail.prompt : ''),
					ref_images: that.popupRefImages,
					quantity: 1,
					ratio: that.popupSelectedRatio,
					quality: 'hd',
					bid: 0
				};
				app.post('ApiAivideo/create_generation_order', postData, function(res) {
					app.showLoading(false);
					that.popupSubmitting = false;
					if (res.status == 1) {
						var resData = res.data;
						if (resData.need_pay) {
							that.closePopup();
							uni.navigateTo({
								url: '/pages/pay/pay?ordernum=' + resData.ordernum + '&tablename=generation'
							});
						} else {
							that.closePopup();
							uni.redirectTo({
								url: '/pagesZ/generation/result?order_id=' + resData.order_id
							});
						}
					} else {
						// 余额/积分不足处理
						var errorType = res.error_type || 'normal';
						if (errorType == 'score_insufficient') {
							var extra = res.extra || {};
							uni.showModal({
								title: '积分不足',
								content: '当前可用积分 ' + (extra.current_score || 0) + '，本次需要 ' + (extra.required_score || 0) + ' 积分',
								cancelText: '关闭',
								confirmText: '购买创作会员',
								success: function(modalRes) {
									if (modalRes.confirm) {
										app.goto('/pagesExt/money/recharge');
									}
								}
							});
						} else if (errorType == 'balance_insufficient') {
							var extra = res.extra || {};
							uni.showModal({
								title: '余额不足',
								content: '当前余额 ￥' + (extra.current_balance || 0) + '，还需 ￥' + (extra.need_amount || 0),
								cancelText: '关闭',
								confirmText: '去充值',
								success: function(modalRes) {
									if (modalRes.confirm) {
										app.goto('/pagesExt/money/recharge');
									}
								}
							});
						} else {
							app.alert(res.msg || '生成失败');
						}
					}
				});
			}
		}
	}
</script>
<style>
.dp-video-generation{width:100%;height: auto; position: relative;overflow: visible; padding: 0px; background: #fff;}

/* ====== grid7横排7列卡片布局 ====== */
.grid7-container{display:flex;flex-wrap:wrap;gap:12rpx;width:100%;}
.grid7-card{width:calc((100% - 72rpx) / 7);background:#fff;overflow:hidden;position:relative;cursor:pointer;transition:box-shadow 0.2s;}
.grid7-card:hover{box-shadow:0 4rpx 16rpx rgba(0,0,0,0.12);}
.grid7-cover{width:100%;height:0;overflow:hidden;position:relative;}
.grid7-cover-img{position:absolute;top:0;left:0;width:100%;height:100%;object-fit:cover;}
.grid7-play-icon{position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);width:48rpx;height:48rpx;background:rgba(0,0,0,0.5);border-radius:50%;display:flex;align-items:center;justify-content:center;}
.grid7-play-triangle{color:#fff;font-size:20rpx;margin-left:4rpx;}
.grid7-info{padding:8rpx;}
.grid7-name{font-size:22rpx;color:#333;font-weight:bold;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;line-height:32rpx;}
.grid7-price{font-size:22rpx;color:#FF6B00;font-weight:bold;line-height:30rpx;}
.grid7-hover-btn{position:absolute;bottom:0;left:0;right:0;display:flex;align-items:center;justify-content:center;background:rgba(0,0,0,0.6);padding:10rpx 0;opacity:0;transition:opacity 0.2s;pointer-events:none;}
.grid7-hover-btn.grid7-hover-show{opacity:1;pointer-events:auto;}
.grid7-hover-text{color:#fff;font-size:22rpx;font-weight:bold;}

/* 响应式断点 */
@media screen and (max-width: 1199px) {
	.grid7-card{width:calc((100% - 48rpx) / 5);}
}
@media screen and (max-width: 767px) {
	.grid7-card{width:calc((100% - 36rpx) / 4);}
}
@media screen and (max-width: 479px) {
	.grid7-card{width:calc((100% - 24rpx) / 3);}
}

/* ====== 底部任务弹窗 ====== */
.task-popup-mask{position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.5);z-index:9998;}
.task-popup{position:fixed;bottom:0;left:0;right:0;background:#fff;border-radius:24rpx 24rpx 0 0;z-index:9999;transition:all 0.3s ease-out;max-height:40vh;overflow-y:auto;padding:24rpx 30rpx;padding-bottom:calc(24rpx + env(safe-area-inset-bottom));box-shadow:0 -4rpx 20rpx rgba(0,0,0,0.1);}
.task-popup.task-popup-expanded{max-height:80vh;}
.popup-row{margin-bottom:20rpx;}
.popup-header{display:flex;align-items:center;justify-content:space-between;padding-bottom:16rpx;border-bottom:1rpx solid #f0f0f0;}
.popup-type-title{font-size:32rpx;font-weight:bold;color:#333;}
.popup-header-btns{display:flex;align-items:center;gap:16rpx;}
.popup-btn-expand,.popup-btn-close{width:56rpx;height:56rpx;display:flex;align-items:center;justify-content:center;border-radius:50%;background:#f5f5f5;}
.popup-btn-icon{font-size:28rpx;color:#666;}
.popup-prompt{position:relative;}
.popup-prompt-input{width:100%;min-height:120rpx;font-size:26rpx;color:#333;padding:16rpx;background:#f8f8f8;border-radius:12rpx;line-height:1.6;box-sizing:border-box;}
.popup-prompt-count{position:absolute;bottom:8rpx;right:16rpx;font-size:22rpx;color:#999;}
.popup-upload-label{font-size:26rpx;color:#333;font-weight:bold;margin-bottom:12rpx;}
.popup-upload-area{display:flex;flex-wrap:wrap;gap:12rpx;}
.popup-upload-item{width:120rpx;height:120rpx;border-radius:8rpx;overflow:hidden;position:relative;}
.popup-upload-thumb{width:100%;height:100%;object-fit:cover;}
.popup-upload-del{position:absolute;top:0;right:0;width:32rpx;height:32rpx;background:rgba(0,0,0,0.5);color:#fff;font-size:20rpx;display:flex;align-items:center;justify-content:center;border-radius:0 0 0 8rpx;}
.popup-upload-add{width:120rpx;height:120rpx;border:2rpx dashed #ddd;border-radius:8rpx;display:flex;align-items:center;justify-content:center;}
.popup-upload-add-icon{font-size:48rpx;color:#ccc;}
.popup-params{display:flex;align-items:center;justify-content:space-between;padding-top:16rpx;border-top:1rpx solid #f0f0f0;}
.popup-param-group{display:flex;gap:16rpx;}
.popup-param-item{display:flex;align-items:center;background:#f5f5f5;border-radius:12rpx;padding:12rpx 20rpx;gap:8rpx;}
.popup-param-label{font-size:24rpx;color:#666;}
.popup-param-value{font-size:24rpx;color:#333;font-weight:bold;}
.popup-param-arrow{font-size:18rpx;color:#999;}
.popup-generate-btn{background:linear-gradient(135deg,#FF6B00,#FF9500);border-radius:40rpx;padding:16rpx 48rpx;}
.popup-generate-btn.popup-generate-disabled{opacity:0.6;}
.popup-generate-text{color:#fff;font-size:28rpx;font-weight:bold;}
.popup-dropdown{position:absolute;bottom:0;left:0;right:0;z-index:10000;}
.popup-dropdown-mask{position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.3);}
.popup-dropdown-content{position:relative;background:#fff;border-radius:16rpx 16rpx 0 0;padding:24rpx;z-index:1;}
.popup-dropdown-title{font-size:28rpx;font-weight:bold;color:#333;margin-bottom:16rpx;}
.popup-dropdown-grid{display:flex;flex-wrap:wrap;gap:12rpx;}
.popup-dropdown-item{padding:12rpx 24rpx;border-radius:8rpx;background:#f5f5f5;font-size:24rpx;color:#333;}
.popup-dropdown-item.popup-dropdown-active{background:#FFF0E0;color:#FF6B00;font-weight:bold;}
</style>
