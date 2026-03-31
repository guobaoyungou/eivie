<template>
<view class="dp-photo-generation" :style="{
	backgroundColor: params.style=='fresh' ? (params.bgcolor || themeColors.pageBg) : params.bgcolor,
	margin:params.margin_y*2.2+'rpx '+params.margin_x*2.2+'rpx 0',
	padding:params.padding_y*2.2+'rpx '+params.padding_x*2.2+'rpx',
	width:'calc(100% - '+params.margin_x*2.2*2+'rpx)'
}">
	<!--123排-->
	<dp-product-item v-if="params.style=='1' || params.style=='2' || params.style=='3'" :showstyle="params.style" :data="data" :saleimg="params.saleimg" :showname="params.showname" :showprice="params.showprice" :showsales="params.showsales" :showcart="params.showcart" :cartimg="params.cartimg" :carttext="params.carttext" idfield="proid" :menuindex="menuindex" :probgcolor="params.probgcolor" :params="params" saleslabel="已用" :cover_ratio="coverRatio" :cover_radius="coverRadius" :card_radius="cardRadius" :btn_position="btnPosition" :card_gap="cardGap" :info_padding="infoPadding" detailurl="/pagesZ/generation/create?type=1"></dp-product-item>
	<!--横排-->
	<dp-product-itemlist v-if="params.style=='list'" :data="data" :saleimg="params.saleimg" :showname="params.showname" :showprice="params.showprice" :showsales="params.showsales" :showcart="params.showcart" :cartimg="params.cartimg" :carttext="params.carttext" idfield="proid" :menuindex="menuindex" :probgcolor="params.probgcolor" saleslabel="已用" :cover_ratio="coverRatio" :cover_radius="coverRadius" :card_radius="cardRadius" :btn_position="btnPosition" :info_padding="infoPadding" detailurl="/pagesZ/generation/create?type=1"></dp-product-itemlist>
	<!--左右滑动-->
	<dp-product-itemline v-if="params.style=='line'" :data="data" :saleimg="params.saleimg" :showname="params.showname" :showprice="params.showprice" :showsales="params.showsales" :showcart="params.showcart" :cartimg="params.cartimg" :carttext="params.carttext" idfield="proid" :menuindex="menuindex" :probgcolor="params.probgcolor" saleslabel="已用" :cover_ratio="coverRatio" :cover_radius="coverRadius" :card_radius="cardRadius" :btn_position="btnPosition" :info_padding="infoPadding" detailurl="/pagesZ/generation/create?type=1"></dp-product-itemline>
	<!--瀑布流-->
	<dp-product-waterfall v-if="params.style=='waterfall'" :list="data" :saleimg="params.saleimg" :showname="params.showname" :showprice="params.showprice" :showsales="params.showsales" :showcart="params.showcart" :cartimg="params.cartimg" :carttext="params.carttext" idfield="proid" :menuindex="menuindex" :probgcolor="params.probgcolor" saleslabel="已用" :cover_ratio="coverRatio" :cover_radius="coverRadius" :card_radius="cardRadius" :btn_position="btnPosition" :card_gap="cardGap" :info_padding="infoPadding" detailurl="/pagesZ/generation/create?type=1"></dp-product-waterfall>

	<!-- ====== fresh 双列瀑布流卡片布局（新增） ====== -->
	<view class="fresh-container" v-if="params.style=='fresh'" :style="{backgroundColor: params.bgcolor || themeColors.pageBg}">
		<!-- 搜索吸附栏 -->
		<view class="fresh-search-bar" v-if="params.showSearch != 0 && params.showSearch !== false" :style="{backgroundColor: themeColors.searchBg}">
			<view class="fresh-search-inner" :style="{backgroundColor: themeColors.searchBg}">
				<text class="fresh-search-icon">🔍</text>
				<input class="fresh-search-input" type="text" :placeholder="params.searchPlaceholder || '搜索模板'" :value="searchKeyword" @confirm="onSearchConfirm" @input="onSearchInput" :style="{color: themeColors.titleColor}" placeholder-class="fresh-search-placeholder" />
				<text class="fresh-search-clear" v-if="searchKeyword" @tap="clearSearch">✕</text>
			</view>
		</view>
		<!-- 胶囊筛选标签栏 -->
		<view class="fresh-filter-bar" v-if="params.showFilter != 0 && params.showFilter !== false && filterTagList.length > 0">
			<scroll-view scroll-x class="fresh-filter-scroll" :show-scrollbar="false">
				<view class="fresh-filter-inner">
					<view class="fresh-filter-tag" v-for="(tag, ti) in filterTagList" :key="ti"
						:style="activeFilterTag == tag.value ? {backgroundColor: themeColors.tagActiveBg, color: '#FFFFFF'} : {backgroundColor: themeColors.tagBg, color: themeColors.titleColor}"
						@tap="onFilterTagTap(tag.value)">
						{{tag.label}}
					</view>
				</view>
			</scroll-view>
		</view>
		<!-- 瀑布流区域 -->
		<view class="fresh-waterfall">
			<view class="fresh-waterfall-col fresh-waterfall-left">
				<view class="fresh-card" v-for="(item, ci) in leftColumn" :key="item.id || ci"
					:style="{boxShadow: '0 4rpx 16rpx ' + themeColors.cardShadow}"
					@tap="onFreshCardTap(item)"
					@mouseenter.native="freshHoverIndex = (item.id || item.proid)"
					@mouseleave.native="freshHoverIndex = -1"
				>
					<view class="fresh-card-cover">
						<image class="fresh-card-img" :src="getCardCover(item)" mode="widthFix" lazy-load></image>
						<!-- overlay按钮位置 -->
						<view class="fresh-card-overlay-btn" v-if="freshBtnVisible && freshBtnPosition=='overlay'"
							:style="freshBtnFillStyle"
							@tap.stop="onFreshBtnTap(item)">
							<image class="fresh-card-btn-icon" v-if="params.btnIcon" :src="params.btnIcon" mode="aspectFit"></image>
							<text class="fresh-card-btn-text" style="color:#fff;">{{params.btnText || '立即使用'}}</text>
						</view>
					</view>
					<view class="fresh-card-info">
						<text class="fresh-card-name" :style="{color: themeColors.titleColor}">{{item.name}}</text>
						<text class="fresh-card-hot" v-if="(item.sales || item.use_count) > 0" :style="{color: themeColors.hotColor}">🔥已用{{item.sales || item.use_count}}次</text>
						<view class="fresh-card-price-row">
							<text class="fresh-card-badge-free" v-if="!item.sell_price || item.sell_price == 0" :style="{backgroundColor: themeColors.freeBadgeBg + '22', color: themeColors.freeBadgeBg}">免费</text>
							<text class="fresh-card-badge-paid" v-else :style="{backgroundColor: themeColors.paidBadgeBg + '22', color: themeColors.paidBadgeBg}">
								<template v-if="item.score_pay_enabled">{{item.price_in_score}}积分</template>
								<template v-else>¥{{item.sell_price}}</template>
							</text>
						</view>
						<!-- bottom按钮位置 -->
						<view class="fresh-card-bottom-btn" v-if="freshBtnVisible && freshBtnPosition=='bottom'"
							:class="{'fresh-btn-fill': freshBtnStyle=='fill', 'fresh-btn-outline': freshBtnStyle=='outline', 'fresh-btn-text': freshBtnStyle=='text'}"
							:style="freshBtnComputedStyle"
							@tap.stop="onFreshBtnTap(item)">
							<image class="fresh-card-btn-icon" v-if="params.btnIcon" :src="params.btnIcon" mode="aspectFit"></image>
							<text class="fresh-card-btn-text" :style="{color: freshBtnStyle=='fill' ? '#fff' : themeColors.tagActiveBg}">{{params.btnText || '立即使用'}}</text>
						</view>
					</view>
				</view>
			</view>
			<view class="fresh-waterfall-col fresh-waterfall-right">
				<view class="fresh-card" v-for="(item, ci) in rightColumn" :key="item.id || ci"
					:style="{boxShadow: '0 4rpx 16rpx ' + themeColors.cardShadow}"
					@tap="onFreshCardTap(item)"
					@mouseenter.native="freshHoverIndex = (item.id || item.proid)"
					@mouseleave.native="freshHoverIndex = -1"
				>
					<view class="fresh-card-cover">
						<image class="fresh-card-img" :src="getCardCover(item)" mode="widthFix" lazy-load></image>
						<view class="fresh-card-overlay-btn" v-if="freshBtnVisible && freshBtnPosition=='overlay'"
							:style="freshBtnFillStyle"
							@tap.stop="onFreshBtnTap(item)">
							<image class="fresh-card-btn-icon" v-if="params.btnIcon" :src="params.btnIcon" mode="aspectFit"></image>
							<text class="fresh-card-btn-text" style="color:#fff;">{{params.btnText || '立即使用'}}</text>
						</view>
					</view>
					<view class="fresh-card-info">
						<text class="fresh-card-name" :style="{color: themeColors.titleColor}">{{item.name}}</text>
						<text class="fresh-card-hot" v-if="(item.sales || item.use_count) > 0" :style="{color: themeColors.hotColor}">🔥已用{{item.sales || item.use_count}}次</text>
						<view class="fresh-card-price-row">
							<text class="fresh-card-badge-free" v-if="!item.sell_price || item.sell_price == 0" :style="{backgroundColor: themeColors.freeBadgeBg + '22', color: themeColors.freeBadgeBg}">免费</text>
							<text class="fresh-card-badge-paid" v-else :style="{backgroundColor: themeColors.paidBadgeBg + '22', color: themeColors.paidBadgeBg}">
								<template v-if="item.score_pay_enabled">{{item.price_in_score}}积分</template>
								<template v-else>¥{{item.sell_price}}</template>
							</text>
						</view>
						<view class="fresh-card-bottom-btn" v-if="freshBtnVisible && freshBtnPosition=='bottom'"
							:class="{'fresh-btn-fill': freshBtnStyle=='fill', 'fresh-btn-outline': freshBtnStyle=='outline', 'fresh-btn-text': freshBtnStyle=='text'}"
							:style="freshBtnComputedStyle"
							@tap.stop="onFreshBtnTap(item)">
							<image class="fresh-card-btn-icon" v-if="params.btnIcon" :src="params.btnIcon" mode="aspectFit"></image>
							<text class="fresh-card-btn-text" :style="{color: freshBtnStyle=='fill' ? '#fff' : themeColors.tagActiveBg}">{{params.btnText || '立即使用'}}</text>
						</view>
					</view>
				</view>
			</view>
		</view>
	</view>

	<!--grid7：横排7列自适应-->
	<view class="grid7-container" v-if="params.style=='grid7'">
		<view class="grid7-card" v-for="(item, index) in data" :key="item.id || index"
			:style="{borderRadius: cardRadius + 'rpx'}"
			@mouseenter="onCardHover(index, true)"
			@mouseleave="onCardHover(index, false)"
			@tap="onCardTap(index)">
			<view class="grid7-cover" :style="{paddingBottom: grid7CoverPadding, borderRadius: cardRadius + 'rpx ' + cardRadius + 'rpx 0 0'}">
				<image class="grid7-cover-img" :src="item.pic" mode="aspectFill" :style="{borderRadius: cardRadius + 'rpx ' + cardRadius + 'rpx 0 0'}"></image>
			</view>
			<view class="grid7-info">
				<view class="grid7-name">{{item.name}}</view>
				<view class="grid7-price"><template v-if="item.score_pay_enabled">{{item.price_in_score}} 积分</template><template v-else>{{item.price_tag || '￥'}}{{item.sell_price || item.price || '0.00'}}</template></view>
			</view>
			<!-- 悬浮"制作同款"按钮：H5鼠标悬浮显示，小程序始终显示 -->
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
			<textarea class="popup-prompt-input" v-model="popupPrompt" placeholder="请输入图像描述" :maxlength="2000" :auto-height="!isExpanded" :style="isExpanded ? 'height:200rpx;' : ''"></textarea>
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
				<!-- 比例选择 - 下拉弹出面板 -->
				<view class="popup-param-item" @tap="toggleRatioPanel">
					<text class="popup-param-label">比例</text>
					<text class="popup-param-value">{{popupSelectedRatio}}</text>
					<text class="popup-param-arrow">▼</text>
				</view>
				<!-- 数量选择 - 下拉弹出面板 -->
				<view class="popup-param-item" @tap="toggleQuantityPanel">
					<text class="popup-param-label">数量</text>
					<text class="popup-param-value">{{popupQuantity}}张</text>
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
		<!-- 数量下拉面板 -->
		<view class="popup-dropdown" v-if="showQuantityPanel">
			<view class="popup-dropdown-mask" @tap="showQuantityPanel=false"></view>
			<view class="popup-dropdown-content">
				<view class="popup-dropdown-title">选择数量</view>
				<view class="popup-dropdown-grid">
					<view class="popup-dropdown-item" :class="{'popup-dropdown-active': popupQuantity==q}" v-for="(q, qi) in quantityOptions" :key="qi" @tap="selectPopupQuantity(q)">{{q}}张</view>
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
				// grid7 悬浮状态
				hoverIndex: -1,
				// fresh布局状态
				searchKeyword: '',
				activeFilterTag: 'all',
				leftColumn: [],
				rightColumn: [],
				leftHeight: 0,
				rightHeight: 0,
				freshHoverIndex: -1,
				// 弹窗状态
				showPopup: false,
				isExpanded: false,
				templateDetail: null,
				popupPrompt: '',
				popupRefImages: [],
				popupSelectedModelId: 0,
				popupSelectedRatio: '3:4',
				popupQuantity: 1,
				popupSubmitting: false,
				popupPromptVisible: true,
				popupNeedRefImage: false,
				popupMaxRefImages: 1,
				popupRatioOptions: ['1:1','2:3','3:2','3:4','4:3','9:16','16:9'],
				quantityOptions: [1, 2, 3, 4, 5, 6, 7, 8, 9],
				showRatioPanel: false,
				showQuantityPanel: false,
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
			// grid7封面比例
			grid7CoverPadding() {
				var ratio = this.coverRatio;
				var map = {'1:1':'100%','4:3':'75%','3:4':'133.33%','16:9':'56.25%','9:16':'177.78%'};
				return map[ratio] || '100%';
			},
			// 弹窗标题
			popupTypeTitle() {
				return '图片生成';
			},
			// 是否小程序环境
			isMiniProgram() {
				// #ifdef MP
				return true;
				// #endif
				// #ifndef MP
				return false;
				// #endif
			},
			// ====== fresh布局 computed ======
			themeColors() {
				var theme = this.params.theme || 'fresh';
				var colors = {
					fresh: {
						pageBg: '#F6FBF8', searchBg: '#EDF7F1', tagBg: '#EDF7F1', tagActiveBg: '#7BC4A0',
						btnStart: '#7BC4A0', btnEnd: '#A8DFC4', titleColor: '#2D3A32', subColor: '#8FA99A',
						freeBadgeBg: '#7BC4A0', paidBadgeBg: '#E8A87C', hotColor: '#E8A87C',
						cardShadow: 'rgba(123,196,160,0.08)'
					},
					cream: {
						pageBg: '#FBF8F4', searchBg: '#F5EDE4', tagBg: '#F5EDE4', tagActiveBg: '#C8A882',
						btnStart: '#C8A882', btnEnd: '#E0C9A8', titleColor: '#3E322A', subColor: '#A89580',
						freeBadgeBg: '#A8C490', paidBadgeBg: '#D4937A', hotColor: '#C89070',
						cardShadow: 'rgba(200,168,130,0.08)'
					},
					macaron: {
						pageBg: '#F8F5FB', searchBg: '#F0EBF5', tagBg: '#F0EBF5', tagActiveBg: '#B5A3D1',
						btnStart: '#B5A3D1', btnEnd: '#D1C4E9', titleColor: '#352D42', subColor: '#998DB3',
						freeBadgeBg: '#90B8C4', paidBadgeBg: '#D4A0C4', hotColor: '#C4A090',
						cardShadow: 'rgba(181,163,209,0.08)'
					}
				};
				return colors[theme] || colors['fresh'];
			},
			filterTagList() {
				var tags = this.params.filterTags;
				if (tags && tags.length > 0) return tags;
				return [{label: '全部', value: 'all'}];
			},
			filteredData() {
				var list = this.data;
				if (!list || !list.length) return [];
				var keyword = this.searchKeyword.trim().toLowerCase();
				var tag = this.activeFilterTag;
				var result = [];
				for (var i = 0; i < list.length; i++) {
					var item = list[i];
					if (keyword && item.name && item.name.toLowerCase().indexOf(keyword) === -1) continue;
					if (tag && tag !== 'all' && item.tag !== tag && item.category !== tag) continue;
					result.push(item);
				}
				return result;
			},
			freshBtnVisible() {
				var v = this.params.btnVisible;
				if (v === 0 || v === false || v === '0') return false;
				return true;
			},
			freshBtnStyle() {
				return this.params.btnStyle || 'fill';
			},
			freshBtnPosition() {
				return this.params.btnPosition || 'bottom';
			},
			freshBtnFillStyle() {
				var c = this.themeColors;
				return {background: 'linear-gradient(135deg,' + c.btnStart + ',' + c.btnEnd + ')'};
			},
			freshBtnComputedStyle() {
				var c = this.themeColors;
				var s = this.freshBtnStyle;
				if (s === 'fill') return {background: 'linear-gradient(135deg,' + c.btnStart + ',' + c.btnEnd + ')'};
				if (s === 'outline') return {border: '2rpx solid ' + c.tagActiveBg, background: 'transparent'};
				return {background: 'transparent'};
			}
		},
		watch: {
			filteredData: {
				handler: function(val) {
					this.distributeWaterfall(val);
				},
				immediate: true
			}
		},
		methods: {
			// ====== fresh布局 methods ======
			getCardCover: function(item) {
				if (item.pic && item.pic !== '') return item.pic;
				if (item.cover_image && item.cover_image !== '') return item.cover_image;
				if (item.original_images && item.original_images.length) {
					for (var i = 0; i < item.original_images.length; i++) {
						if (item.original_images[i] && item.original_images[i] !== '') return item.original_images[i];
					}
				}
				if (item.original_image && item.original_image !== '') return item.original_image;
				if (item.effect_images && item.effect_images.length) {
					for (var j = 0; j < item.effect_images.length; j++) {
						if (item.effect_images[j] && item.effect_images[j] !== '') return item.effect_images[j];
					}
				}
				if (item.sample_images && item.sample_images.length) {
					for (var k = 0; k < item.sample_images.length; k++) {
						if (item.sample_images[k] && item.sample_images[k] !== '') return item.sample_images[k];
					}
				}
				return '/static/img/placeholder.png';
			},
			distributeWaterfall: function(list) {
				if (!list || !list.length) {
					this.leftColumn = [];
					this.rightColumn = [];
					this.leftHeight = 0;
					this.rightHeight = 0;
					return;
				}
				var left = [], right = [], lh = 0, rh = 0;
				for (var i = 0; i < list.length; i++) {
					if (lh <= rh) {
						left.push(list[i]);
						lh += 1;
					} else {
						right.push(list[i]);
						rh += 1;
					}
				}
				this.leftColumn = left;
				this.rightColumn = right;
				this.leftHeight = lh;
				this.rightHeight = rh;
			},
			onSearchConfirm: function(e) {
				this.searchKeyword = e.detail.value || '';
			},
			onSearchInput: function(e) {
				this.searchKeyword = e.detail.value || '';
			},
			clearSearch: function() {
				this.searchKeyword = '';
			},
			onFilterTagTap: function(value) {
				this.activeFilterTag = value;
			},
			onFreshCardTap: function(item) {
				if (!this.freshBtnVisible) {
					var templateId = item.proid || item.id;
					app.goto('/ailvpai/create?type=1&template_id=' + templateId);
				}
			},
			onFreshBtnTap: function(item) {
				var templateId = item.proid || item.id;
				app.goto('/ailvpai/create?type=1&template_id=' + templateId);
			},
			// ====== grid7 methods ======
			// grid7: 鼠标悬浮
			onCardHover(index, isHover) {
				this.hoverIndex = isHover ? index : -1;
			},
			// grid7: 卡片点击（小程序端直接打开弹窗）
			onCardTap(index) {
				if (this.isMiniProgram) {
					this.onMakeSame(index);
				}
			},
			// 点击"制作同款"按钮 - 直接跳转到图片生成页面
			onMakeSame(index) {
				var that = this;
				var item = that.data[index];
				if (!item) return;
				var templateId = item.proid || item.id;
				// 直接跳转到图片生成页面
				app.goto('/pagesZ/generation/create?type=1&template_id=' + templateId);
			},
			// 将模板详情应用到弹窗
			applyTemplateToPopup(detail) {
				this.templateDetail = detail;
				// 提示词
				this.popupPrompt = detail.prompt || (detail.default_params && detail.default_params.prompt) || '';
				// 提示词是否可见
				this.popupPromptVisible = detail.prompt_visible !== 0 && detail.prompt_visible !== '0';
				// 参考图
				this.popupRefImages = [];
				var maxImg = parseInt(detail.max_ref_images) || 1;
				if (maxImg > 9) maxImg = 9;
				if (maxImg < 1) maxImg = 1;
				this.popupMaxRefImages = maxImg;
				this.popupNeedRefImage = (maxImg > 0 && detail.need_ref_image !== 0 && detail.need_ref_image !== '0');
				// 比例
				if (detail.model_capability && detail.model_capability.supported_ratios) {
					this.popupRatioOptions = detail.model_capability.supported_ratios;
				} else {
					this.popupRatioOptions = ['1:1','2:3','3:2','3:4','4:3','9:16','16:9'];
				}
				if (detail.default_params && detail.default_params.ratio) {
					this.popupSelectedRatio = detail.default_params.ratio;
				} else {
					this.popupSelectedRatio = '3:4';
				}
				// 数量
				this.popupQuantity = parseInt(detail.output_quantity) || 1;
				// 模型
				if (detail.model_capability && detail.model_capability.model_id) {
					this.popupSelectedModelId = detail.model_capability.model_id;
				}
			},
			// 关闭弹窗
			closePopup() {
				this.showPopup = false;
				this.isExpanded = false;
				this.showRatioPanel = false;
				this.showQuantityPanel = false;
			},
			// 放大/缩小弹窗
			toggleExpand() {
				this.isExpanded = !this.isExpanded;
			},
			// 切换比例面板
			toggleRatioPanel() {
				this.showQuantityPanel = false;
				this.showRatioPanel = !this.showRatioPanel;
			},
			// 切换数量面板
			toggleQuantityPanel() {
				this.showRatioPanel = false;
				this.showQuantityPanel = !this.showQuantityPanel;
			},
			// 选择比例
			selectPopupRatio(r) {
				this.popupSelectedRatio = r;
				this.showRatioPanel = false;
			},
			// 选择数量
			selectPopupQuantity(q) {
				this.popupQuantity = q;
				this.showQuantityPanel = false;
			},
			// 选择参考图
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
			// 上传参考图
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
			// 删除参考图
			removeRefImage(idx) {
				this.popupRefImages.splice(idx, 1);
			},
			// 提交生成
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
					generation_type: 1,
					prompt: that.popupPromptVisible ? that.popupPrompt : (that.templateDetail && that.templateDetail.prompt ? that.templateDetail.prompt : ''),
					ref_images: that.popupRefImages,
					quantity: that.popupQuantity,
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
								url: '/ailvpai/result?order_id=' + resData.order_id
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
.dp-photo-generation{width:100%;height: auto; position: relative;overflow: visible; padding: 0px; background: #fff;}

/* ====== grid7横排7列卡片布局 ====== */
.grid7-container{display:flex;flex-wrap:wrap;gap:12rpx;width:100%;}
.grid7-card{width:calc((100% - 72rpx) / 7);background:#fff;overflow:hidden;position:relative;cursor:pointer;transition:box-shadow 0.2s;border-radius:24rpx;}
.grid7-card:hover{box-shadow:0 4rpx 16rpx rgba(0,0,0,0.12);}
.grid7-cover{width:100%;height:0;overflow:hidden;position:relative;}
.grid7-cover-img{position:absolute;top:0;left:0;width:100%;height:100%;object-fit:cover;}
.grid7-info{padding:8rpx;}
.grid7-name{font-size:22rpx;color:#333;font-weight:bold;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;line-height:32rpx;}
.grid7-price{font-size:22rpx;color:#91C2FF;font-weight:bold;line-height:30rpx;}
.grid7-hover-btn{position:absolute;bottom:0;left:0;right:0;display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg,rgba(145,194,255,0.85),rgba(181,216,254,0.85));padding:10rpx 0;opacity:0;transition:opacity 0.2s;pointer-events:none;}
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
.task-popup-mask{position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.4);z-index:9998;}
.task-popup{position:fixed;bottom:0;left:0;right:0;background:rgba(253,251,255,0.95);border-radius:32rpx 32rpx 0 0;z-index:9999;transition:all 0.3s ease-out;max-height:40vh;overflow-y:auto;padding:24rpx 30rpx;padding-bottom:calc(24rpx + env(safe-area-inset-bottom));box-shadow:0 -4rpx 20rpx rgba(0,0,0,0.08);}
.task-popup.task-popup-expanded{max-height:80vh;}

/* 弹窗行 */
.popup-row{margin-bottom:20rpx;}

/* 第一行：标题栏 */
.popup-header{display:flex;align-items:center;justify-content:space-between;padding-bottom:16rpx;border-bottom:1rpx solid #f0f0f0;}
.popup-type-title{font-size:32rpx;font-weight:bold;color:#555555;}
.popup-header-btns{display:flex;align-items:center;gap:16rpx;}
.popup-btn-expand,.popup-btn-close{width:56rpx;height:56rpx;display:flex;align-items:center;justify-content:center;border-radius:50%;background:#F5F0FA;}
.popup-btn-icon{font-size:28rpx;color:#666;}

/* 第二行：提示词 */
.popup-prompt{position:relative;}
.popup-prompt-input{width:100%;min-height:120rpx;font-size:26rpx;color:#555555;padding:16rpx;background:#F5F0FA;border-radius:12rpx;line-height:1.6;box-sizing:border-box;}
.popup-prompt-count{position:absolute;bottom:8rpx;right:16rpx;font-size:22rpx;color:#999;}

/* 第三行：图片上传 */
.popup-upload-label{font-size:26rpx;color:#555555;font-weight:bold;margin-bottom:12rpx;}
.popup-upload-area{display:flex;flex-wrap:wrap;gap:12rpx;}
.popup-upload-item{width:120rpx;height:120rpx;border-radius:8rpx;overflow:hidden;position:relative;}
.popup-upload-thumb{width:100%;height:100%;object-fit:cover;}
.popup-upload-del{position:absolute;top:0;right:0;width:32rpx;height:32rpx;background:rgba(0,0,0,0.5);color:#fff;font-size:20rpx;display:flex;align-items:center;justify-content:center;border-radius:0 0 0 8rpx;}
.popup-upload-add{width:120rpx;height:120rpx;border:2rpx dashed #ddd;border-radius:8rpx;display:flex;align-items:center;justify-content:center;}
.popup-upload-add-icon{font-size:48rpx;color:#ccc;}

/* 第四行：参数选择+生成按钮 */
.popup-params{display:flex;align-items:center;justify-content:space-between;padding-top:16rpx;border-top:1rpx solid #f0f0f0;}
.popup-param-group{display:flex;gap:16rpx;}
.popup-param-item{display:flex;align-items:center;background:#F5F0FA;border-radius:12rpx;padding:12rpx 20rpx;gap:8rpx;}
.popup-param-label{font-size:24rpx;color:#666;}
.popup-param-value{font-size:24rpx;color:#555555;font-weight:bold;}
.popup-param-arrow{font-size:18rpx;color:#999;}
.popup-generate-btn{background:linear-gradient(135deg,#91C2FF,#B5D8FE);border-radius:40rpx;padding:16rpx 48rpx;box-shadow:0 8rpx 24rpx rgba(145,194,255,0.3);}
.popup-generate-btn.popup-generate-disabled{opacity:0.5;box-shadow:none;}
.popup-generate-text{color:#fff;font-size:28rpx;font-weight:bold;}

/* 下拉面板 */
.popup-dropdown{position:absolute;bottom:0;left:0;right:0;z-index:10000;}
.popup-dropdown-mask{position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.2);}
.popup-dropdown-content{position:relative;background:#fff;border-radius:16rpx 16rpx 0 0;padding:24rpx;z-index:1;}
.popup-dropdown-title{font-size:28rpx;font-weight:bold;color:#555555;margin-bottom:16rpx;}
.popup-dropdown-grid{display:flex;flex-wrap:wrap;gap:12rpx;}
.popup-dropdown-item{padding:12rpx 24rpx;border-radius:8rpx;background:#F5F0FA;font-size:24rpx;color:#555555;}
.popup-dropdown-item.popup-dropdown-active{background:rgba(181,216,254,0.15);color:#91C2FF;font-weight:bold;}

/* ====== fresh 双列瀑布流布局 ====== */
.fresh-container{width:100%;padding:0;}
.fresh-search-bar{position:sticky;top:0;z-index:100;padding:16rpx 16rpx 0;}
.fresh-search-inner{display:flex;align-items:center;height:72rpx;border-radius:36rpx;padding:0 24rpx;}
.fresh-search-icon{font-size:28rpx;margin-right:12rpx;flex-shrink:0;}
.fresh-search-input{flex:1;font-size:26rpx;height:72rpx;line-height:72rpx;background:transparent;border:none;}
.fresh-search-placeholder{color:#999;font-size:26rpx;}
.fresh-search-clear{font-size:24rpx;color:#999;padding:8rpx;flex-shrink:0;}
.fresh-filter-bar{padding:16rpx 16rpx 0;}
.fresh-filter-scroll{width:100%;white-space:nowrap;}
.fresh-filter-inner{display:flex;gap:16rpx;padding:0 0 8rpx;}
.fresh-filter-tag{display:inline-flex;align-items:center;justify-content:center;height:56rpx;padding:0 28rpx;border-radius:28rpx;font-size:24rpx;flex-shrink:0;transition:all 0.2s;}
.fresh-waterfall{display:flex;padding:16rpx;gap:16rpx;}
.fresh-waterfall-col{flex:1;display:flex;flex-direction:column;gap:16rpx;}
.fresh-card{background:#FFFFFF;border-radius:20rpx;overflow:hidden;transition:transform 0.2s,box-shadow 0.2s;}
.fresh-card:active{transform:scale(0.98);opacity:0.9;}
.fresh-card-cover{position:relative;width:100%;overflow:hidden;border-radius:20rpx 20rpx 0 0;background:#f5f5f5;}
.fresh-card-img{width:100%;display:block;}
.fresh-card-overlay-btn{position:absolute;bottom:0;left:0;right:0;display:flex;align-items:center;justify-content:center;height:60rpx;gap:8rpx;}
.fresh-card-info{padding:16rpx;}
.fresh-card-name{font-size:26rpx;font-weight:bold;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;display:block;line-height:36rpx;}
.fresh-card-hot{font-size:22rpx;line-height:32rpx;display:block;margin-top:4rpx;}
.fresh-card-price-row{display:flex;align-items:center;margin-top:8rpx;}
.fresh-card-badge-free,.fresh-card-badge-paid{font-size:20rpx;padding:2rpx 12rpx;border-radius:6rpx;font-weight:bold;}
.fresh-card-bottom-btn{display:flex;align-items:center;justify-content:center;height:60rpx;border-radius:30rpx;margin-top:12rpx;gap:8rpx;}
.fresh-btn-fill{}
.fresh-btn-outline{border:2rpx solid transparent;}
.fresh-btn-text{}
.fresh-card-btn-icon{width:28rpx;height:28rpx;flex-shrink:0;}
.fresh-card-btn-text{font-size:24rpx;font-weight:bold;}
/* 视频播放图标覆盖层 */
.fresh-card-play-icon{position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);width:64rpx;height:64rpx;background:rgba(0,0,0,0.4);border-radius:50%;display:flex;align-items:center;justify-content:center;}
.fresh-card-play-triangle{color:#fff;font-size:24rpx;margin-left:4rpx;}
</style>
