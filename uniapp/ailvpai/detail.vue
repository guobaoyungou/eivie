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
					<text class="price score-price" v-if="detail.score_pay_enabled">{{detail.price_in_score}} {{detail.score_unit_name || '词元'}}</text>
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
			
			<!-- 门店信息区块 -->
			<view class="store-info-block" v-if="detail.show_store_info == 1 && detail.store_info">
				<view class="store-info-row">
					<image v-if="detail.store_info.logo" :src="detail.store_info.logo" class="store-logo" mode="aspectFill"></image>
					<view class="store-detail">
						<text class="store-name">{{detail.store_info.name}}</text>
						<text class="store-address" v-if="detail.store_info.address">{{detail.store_info.address}}</text>
					</view>
					<view class="store-phone" v-if="detail.store_info.tel" @tap="callStore">
						<text class="iconfont icon-dianhua"></text>
					</view>
				</view>
			</view>
			
			<!-- 佣金展示区块 -->
			<view class="commission-block" v-if="detail.show_commission == 1 && detail.commission_in_score > 0">
				<text class="commission-text">预估佣金：{{detail.commission_in_score}} {{detail.score_unit_name || '词元'}}</text>
			</view>
			
			<!-- 分享赚佣金提示条 -->
			<view class="share-commission-bar" v-if="detail.share_show_commission && detail.commission_enabled && parseFloat(detail.share_commission_amount) > 0" @tap="openSharePopup">
				<view class="share-commission-left">
					<text class="share-commission-icon">💰</text>
					<text class="share-commission-text">{{detail.share_commission_desc}}</text>
				</view>
				<view class="share-commission-btn">立即分享</view>
			</view>
			
			<!-- 升级优惠区块 -->
			<view class="upgrade-block" v-if="detail.show_upgrade_discount == 1 && detail.upgrade_info">
				<text class="upgrade-text">升级到{{detail.upgrade_info.next_level_name}}，每次可节省 {{detail.upgrade_info.save_in_score || detail.upgrade_info.save_amount}} {{detail.score_unit_name || '词元'}}</text>
			</view>
		</view>
		
		<!-- 底部操作栏 -->
		<view class="bottom-bar">
			<view class="share-btn" v-if="detail && detail.commission_enabled" @tap="openSharePopup">
				<text class="share-btn-icon">📤</text>
				<text class="share-btn-text">分享</text>
			</view>
			<view class="price-display">
				<text class="total-label">合计：</text>
				<text class="total-price score-price" v-if="detail.score_pay_enabled">{{detail.price_in_score}} {{detail.score_unit_name || '词元'}}</text>
				<text class="total-price" v-else>¥{{detail.price}}</text>
			</view>
			<view class="btn-primary" @tap="goCreate">开始创作 ✨</view>
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
					<image class="img" :src="posterpic" mode="widthFix" @tap="previewPoster"></image>
				</view>
			</view>
		</view>
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
			detail: null,
			sharetypevisible: false,
			showposter: false,
			posterpic: '',
			pre_url: app.globalData.pre_url
		};
	},
	
	onLoad(opt) {
		this.opt = app.getopts(opt);
		this.getdata();
	},
	
	onPullDownRefresh() {
		this.getdata();
	},
	
	onShareAppMessage: function() {
		var that = this;
		if (!that.detail) return {};
		var mid = app.globalData.mid || 0;
		return {
			title: that.detail.template_name || 'AI创作模板',
			imageUrl: that.detail.cover_image || '',
			path: '/ailvpai/detail?id=' + that.opt.id + '&type=' + (that.opt.type || that.detail.generation_type || 1) + '&pid=' + mid
		};
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
			
			var url = '/ailvpai/create?id=' + that.detail.id + '&type=' + (that.opt.type || that.detail.generation_type || 1);
			if (that.opt.pid) {
				url += '&pid=' + that.opt.pid;
			}
			uni.navigateTo({ url: url });
		},
		
		callStore() {
			if (this.detail && this.detail.store_info && this.detail.store_info.tel) {
				uni.makePhoneCall({ phoneNumber: this.detail.store_info.tel });
			}
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
							href: app.globalData.pre_url + '/h5/' + app.globalData.aid + '.html#/ailvpai/detail?id=' + that.opt.id + '&type=' + (that.opt.type || 1) + '&pid=' + mid,
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
			app.post('ApiAivideo/getposter', { template_id: that.opt.id }, function(data) {
				app.showLoading(false);
				if (data.status == 0) {
					app.alert(data.msg);
					that.showposter = false;
				} else {
					that.posterpic = data.poster;
				}
			});
		},
		posterDialogClose() {
			this.showposter = false;
		},
		previewPoster() {
			if (this.posterpic) {
				uni.previewImage({ urls: [this.posterpic], current: this.posterpic });
			}
		}
	}
};
</script>

<style>
.container { background: #FDFBFF; min-height: 100vh; }
.cover-section { width: 100%; height: 500rpx; background: #000; position: relative; }
.cover-section::after { content: ''; position: absolute; bottom: 0; left: 0; right: 0; height: 48rpx; background: #FDFBFF; border-radius: 24rpx 24rpx 0 0; }
.cover-image { width: 100%; height: 100%; }
.cover-video { width: 100%; height: 100%; }
.info-section { background: #fff; margin: 20rpx; border-radius: 24rpx; padding: 30rpx; box-shadow: 0 6rpx 20rpx rgba(0,0,0,0.05); }
.title-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16rpx; }
.template-name { font-size: 36rpx; font-weight: bold; color: #555555; flex: 1; }
.use-count { font-size: 24rpx; color: #999; flex-shrink: 0; }
.desc { font-size: 28rpx; color: #666666; line-height: 1.6; margin-bottom: 20rpx; }
.price-row { display: flex; align-items: center; margin-top: 20rpx; padding-top: 20rpx; border-top: 1px solid #F0EDF5; }
.price-info { flex: 1; display: flex; align-items: baseline; }
.price-label { font-size: 28rpx; color: #666; }
.price { font-size: 40rpx; color: #91C2FF; font-weight: bold; }
.price-unit { font-size: 24rpx; color: #999; margin-left: 8rpx; }
.member-tag { font-size: 22rpx; color: #fff; background: linear-gradient(135deg, #FFC3D8, #FFD6E5); padding: 4rpx 12rpx; border-radius: 20rpx; }
.member-prices { display: flex; flex-wrap: wrap; gap: 16rpx; margin-top: 20rpx; padding-top: 16rpx; border-top: 1px dashed #F0EDF5; }
.member-price-item { display: flex; align-items: center; background: #f9f9f9; padding: 8rpx 16rpx; border-radius: 8rpx; }
.level-name { font-size: 24rpx; color: #666; }
.level-price { font-size: 26rpx; color: #91C2FF; font-weight: bold; }
.bottom-bar { position: fixed; bottom: 0; left: 0; right: 0; background: #fff; display: flex; align-items: center; padding: 20rpx 30rpx; box-shadow: 0 -4rpx 20rpx rgba(0,0,0,0.05); padding-bottom: calc(20rpx + env(safe-area-inset-bottom)); }
.price-display { flex: 1; }
.total-label { font-size: 28rpx; color: #666; }
.total-price { font-size: 40rpx; color: #91C2FF; font-weight: bold; }
.btn-primary { background: linear-gradient(135deg, #91C2FF, #B5D8FE); color: #fff; font-size: 32rpx; font-weight: bold; padding: 24rpx 60rpx; border-radius: 40rpx; box-shadow: 0 8rpx 24rpx rgba(145,194,255,0.3); }
.score-price { color: #91C2FF; }

/* 门店信息 */
.store-info-block { margin-top: 20rpx; padding-top: 20rpx; border-top: 1px dashed #F0EDF5; }
.store-info-row { display: flex; align-items: center; }
.store-logo { width: 80rpx; height: 80rpx; border-radius: 12rpx; flex-shrink: 0; background: #F5F0FA; }
.store-detail { flex: 1; margin-left: 16rpx; }
.store-name { font-size: 28rpx; color: #555; font-weight: bold; display: block; }
.store-address { font-size: 24rpx; color: #999; display: block; margin-top: 6rpx; }
.store-phone { width: 64rpx; height: 64rpx; border-radius: 50%; background: linear-gradient(135deg, #91C2FF, #B5D8FE); display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.store-phone .iconfont { color: #fff; font-size: 32rpx; }

/* 佣金展示 */
.commission-block { margin-top: 20rpx; padding: 16rpx 20rpx; background: linear-gradient(135deg, #FFF8E1, #FFF3CD); border-radius: 12rpx; }
.commission-text { font-size: 26rpx; color: #F59E0B; font-weight: bold; }

/* 升级优惠 */
.upgrade-block { margin-top: 16rpx; padding: 16rpx 20rpx; background: linear-gradient(135deg, #EDE7F6, #E8DEF8); border-radius: 12rpx; }
.upgrade-text { font-size: 26rpx; color: #7C3AED; }

/* 分享赚佣金提示条 */
.share-commission-bar { display: flex; align-items: center; justify-content: space-between; margin-top: 20rpx; padding: 16rpx 20rpx; background: linear-gradient(135deg, #FFF8E1, #FFF3CD); border-radius: 12rpx; }
.share-commission-left { display: flex; align-items: center; flex: 1; }
.share-commission-icon { font-size: 28rpx; margin-right: 8rpx; }
.share-commission-text { font-size: 26rpx; color: #F59E0B; font-weight: bold; }
.share-commission-btn { font-size: 22rpx; color: #fff; background: linear-gradient(135deg, #F59E0B, #F97316); padding: 8rpx 20rpx; border-radius: 24rpx; flex-shrink: 0; margin-left: 12rpx; }

/* 分享按钮 */
.share-btn { display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 0 24rpx; flex-shrink: 0; }
.share-btn-icon { font-size: 36rpx; }
.share-btn-text { font-size: 20rpx; color: #666; margin-top: 4rpx; }

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
</style>
