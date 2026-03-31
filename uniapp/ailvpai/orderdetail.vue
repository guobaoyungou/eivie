<template>
<view class="container">
	<block v-if="isload">
		<!-- 状态头部 -->
		<view class="status-header" :class="statusClass">
			<view class="status-icon">
				<text class="iconfont" :class="statusIcon"></text>
			</view>
			<view class="status-text">{{statusText}}</view>
			<view class="status-desc" v-if="statusDesc">{{statusDesc}}</view>
		</view>
		
		<!-- 场景信息 -->
		<view class="section scene-section">
			<image :src="detail.cover_image || '/static/img/placeholder.png'" class="scene-cover" mode="aspectFill"></image>
			<view class="scene-info">
				<text class="scene-name">{{detail.scene_name || detail.template_name || '场景模板'}}</text>
				<text class="scene-type">{{detail.generation_type == 1 ? '照片生成' : '视频生成'}}</text>
			</view>
		</view>
		
		<!-- 订单信息 -->
		<view class="section">
			<view class="section-title">订单信息</view>
			<view class="info-item">
				<text class="label">订单编号</text>
				<text class="value" selectable="true">{{detail.ordernum}}</text>
			</view>
			<view class="info-item">
				<text class="label">创建时间</text>
				<text class="value">{{detail.createtime_text}}</text>
			</view>
			<view class="info-item" v-if="detail.pay_time_text">
				<text class="label">支付时间</text>
				<text class="value">{{detail.pay_time_text}}</text>
			</view>
			<view class="info-item" v-if="detail.paytype">
				<text class="label">支付方式</text>
				<text class="value">{{detail.paytype}}</text>
			</view>
			<!-- 门店信息 -->
			<view class="info-item store-info-row" v-if="detail.show_store_info == 1 && detail.store_info">
				<text class="label">门店</text>
				<view class="value store-value">
					<text>{{detail.store_info.name}}</text>
					<text class="store-tel" v-if="detail.store_info.tel" @tap="callStore">{{detail.store_info.tel}}</text>
				</view>
			</view>
		</view>
		
		<!-- 金额信息 -->
		<view class="section">
			<view class="section-title">金额信息</view>
			<view class="info-item">
				<text class="label">订单金额</text>
				<text class="value price">¥{{detail.total_price}}</text>
			</view>
			<view class="info-item">
				<text class="label">实付金额</text>
				<text class="value price">¥{{detail.pay_price}}</text>
			</view>
			<!-- 佣金信息 -->
			<view class="info-item" v-if="detail.show_commission == 1 && detail.commission_in_score > 0">
				<text class="label">预估佣金</text>
				<text class="value commission-value">{{detail.commission_in_score}} {{detail.score_unit_name || '词元'}}</text>
			</view>
		</view>
		
		<!-- 退款信息 -->
		<view class="section" v-if="detail.refund_status > 0">
			<view class="section-title">退款信息</view>
			<view class="info-item">
				<text class="label">退款状态</text>
				<text class="value" :class="refundStatusClass">{{refundStatusText}}</text>
			</view>
			<view class="info-item" v-if="detail.refund_reason">
				<text class="label">退款原因</text>
				<text class="value">{{detail.refund_reason}}</text>
			</view>
			<view class="info-item" v-if="detail.refund_money > 0">
				<text class="label">退款金额</text>
				<text class="value price">¥{{detail.refund_money}}</text>
			</view>
			<view class="info-item" v-if="detail.refund_checkremark">
				<text class="label">审核备注</text>
				<text class="value">{{detail.refund_checkremark}}</text>
			</view>
		</view>
		
		<!-- 生成结果 -->
		<view class="section" v-if="detail.record && detail.record.outputs && detail.record.outputs.length > 0">
			<view class="section-title">生成结果</view>
			<view class="result-grid">
				<block v-for="(output, idx) in detail.record.outputs" :key="idx">
					<image 
						v-if="output.output_type != 'video'" 
						:src="output.output_url" 
						class="result-image" 
						mode="aspectFill"
						@tap="previewImage(output.output_url)"
					></image>
					<video 
						v-else 
						:src="output.output_url" 
						class="result-video" 
						controls
					></video>
				</block>
			</view>
		</view>
		
		<!-- 底部操作栏 -->
		<view class="bottom-bar" v-if="showBottomBar">
			<view v-if="detail.pay_status == 0" class="btn btn-primary" @tap="goPay">立即支付</view>
			<view v-if="detail.can_refund" class="btn btn-outline" @tap="goRefund">申请退款</view>
			<view v-if="detail.can_cancel_refund" class="btn btn-outline" @tap="cancelRefund">撤销退款</view>
		</view>
		<view style="height:120rpx;" v-if="showBottomBar"></view>
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
	
	computed: {
		statusClass() {
			if (!this.detail) return '';
			if (this.detail.refund_status == 2) return 'refunded';
			if (this.detail.refund_status == 1) return 'pending';
			if (this.detail.pay_status == 0) return 'pending';
			if (this.detail.task_status == 3) return 'failed';
			if (this.detail.task_status == 2) return 'success';
			if (this.detail.task_status == 1) return 'processing';
			return 'pending';
		},
		statusIcon() {
			if (!this.detail) return '';
			if (this.detail.refund_status == 2) return 'icon-tuikuan';
			if (this.detail.refund_status == 1) return 'icon-shenhe';
			if (this.detail.pay_status == 0) return 'icon-zhifu';
			if (this.detail.task_status == 3) return 'icon-shibai';
			if (this.detail.task_status == 2) return 'icon-chenggong';
			if (this.detail.task_status == 1) return 'icon-loading';
			return 'icon-dingdan';
		},
		statusText() {
			if (!this.detail) return '';
			if (this.detail.refund_status == 2) return '已退款';
			if (this.detail.refund_status == 1) return '退款审核中';
			if (this.detail.refund_status == 3) return '退款已驳回';
			if (this.detail.pay_status == 0) return '待支付';
			if (this.detail.pay_status == 2) return '已取消';
			if (this.detail.task_status == 0) return '待处理';
			if (this.detail.task_status == 1) return '生成中';
			if (this.detail.task_status == 2) return '生成完成';
			if (this.detail.task_status == 3) return '生成失败';
			return '';
		},
		statusDesc() {
			if (!this.detail) return '';
			if (this.detail.refund_status == 1) return '您的退款申请正在审核中，请耐心等待';
			if (this.detail.refund_status == 3) return this.detail.refund_checkremark || '您的退款申请已被驳回';
			if (this.detail.pay_status == 0) return '请在30分钟内完成支付';
			if (this.detail.task_status == 1) return '正在为您生成中，请稍候...';
			if (this.detail.task_status == 3) return '生成失败，您可以申请退款';
			return '';
		},
		refundStatusClass() {
			if (!this.detail) return '';
			if (this.detail.refund_status == 2) return 'success';
			if (this.detail.refund_status == 1) return 'warning';
			if (this.detail.refund_status == 3) return 'danger';
			return '';
		},
		refundStatusText() {
			if (!this.detail) return '';
			var map = { 0: '无退款', 1: '待审核', 2: '已退款', 3: '已驳回' };
			return map[this.detail.refund_status] || '';
		},
		showBottomBar() {
			if (!this.detail) return false;
			return this.detail.pay_status == 0 || this.detail.can_refund || this.detail.can_cancel_refund;
		}
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
			
			app.get('ApiAivideo/generation_order_detail', { order_id: that.opt.id }, function(res) {
				that.loading = false;
				that.isload = true;
				uni.stopPullDownRefresh();
				
				if (res.status == 1) {
					that.detail = res.data;
				} else {
					app.alert(res.msg);
				}
			});
		},
		
		callStore() {
			if (this.detail && this.detail.store_info && this.detail.store_info.tel) {
				uni.makePhoneCall({ phoneNumber: this.detail.store_info.tel });
			}
		},
		
		previewImage(url) {
			var urls = [];
			if (this.detail.record && this.detail.record.outputs) {
				this.detail.record.outputs.forEach(function(item) {
					if (item.output_type != 'video') {
						urls.push(item.output_url);
					}
				});
			}
			uni.previewImage({
				current: url,
				urls: urls.length > 0 ? urls : [url]
			});
		},
		
		goPay() {
			uni.navigateTo({
				url: '/pages/pay/pay?ordernum=' + this.detail.ordernum + '&tablename=generation'
			});
		},
		
		goRefund() {
			uni.navigateTo({
				url: '/ailvpai/refundApply?id=' + this.detail.id
			});
		},
		
		cancelRefund() {
			var that = this;
			app.confirm('确定要撤销退款申请吗？', function() {
				app.showLoading('处理中');
				app.post('ApiAivideo/generation_refund_cancel', { order_id: that.detail.id }, function(res) {
					app.showLoading(false);
					if (res.status == 1) {
						app.success('撤销成功');
						that.getdata();
					} else {
						app.alert(res.msg);
					}
				});
			});
		}
	}
};
</script>

<style>
.container { background: #FDFBFF; min-height: 100vh; }
.status-header { padding: 60rpx 30rpx; text-align: center; color: #fff; }
.status-header.pending { background: linear-gradient(135deg, #FFC3D8, #FFD6E5); }
.status-header.processing { background: linear-gradient(135deg, #91C2FF, #B5D8FE); }
.status-header.success { background: linear-gradient(135deg, #91C2FF, #B5D8FE); }
.status-header.failed { background: linear-gradient(135deg, #FFC3D8, #FFA0B8); }
.status-header.refunded { background: linear-gradient(135deg, #9E9E9E, #BDBDBD); }
.status-icon { font-size: 80rpx; margin-bottom: 20rpx; }
.status-text { font-size: 36rpx; font-weight: bold; margin-bottom: 10rpx; }
.status-desc { font-size: 26rpx; opacity: 0.9; }
.section { background: #fff; margin: 20rpx; border-radius: 24rpx; padding: 30rpx; box-shadow: 0 6rpx 20rpx rgba(0,0,0,0.05); }
.section-title { font-size: 30rpx; font-weight: bold; color: #555555; margin-bottom: 20rpx; padding-bottom: 20rpx; border-bottom: 1px solid #F0EDF5; }
.scene-section { display: flex; align-items: center; }
.scene-cover { width: 160rpx; height: 160rpx; border-radius: 16rpx; flex-shrink: 0; background: #F5F0FA; }
.scene-info { flex: 1; margin-left: 24rpx; }
.scene-name { font-size: 32rpx; color: #555555; font-weight: bold; display: block; margin-bottom: 12rpx; }
.scene-type { font-size: 26rpx; color: #999; }
.info-item { display: flex; justify-content: space-between; padding: 16rpx 0; }
.info-item .label { font-size: 28rpx; color: #666; }
.info-item .value { font-size: 28rpx; color: #555555; text-align: right; flex: 1; margin-left: 30rpx; }
.info-item .value.price { color: #91C2FF; font-weight: bold; }
.info-item .value.success { color: #4CAF50; }
.info-item .value.warning { color: #FFC3D8; }
.info-item .value.danger { color: #FFA0B8; }
.result-grid { display: flex; flex-wrap: wrap; gap: 16rpx; }
.result-image { width: calc(33.33% - 12rpx); aspect-ratio: 1; border-radius: 16rpx; background: #F5F0FA; }
.result-video { width: 100%; border-radius: 16rpx; }
.bottom-bar { position: fixed; bottom: 0; left: 0; right: 0; background: #fff; padding: 20rpx 30rpx; display: flex; gap: 20rpx; box-shadow: 0 -4rpx 20rpx rgba(0,0,0,0.05); padding-bottom: calc(20rpx + env(safe-area-inset-bottom)); }
.btn { flex: 1; height: 88rpx; line-height: 88rpx; text-align: center; font-size: 30rpx; border-radius: 40rpx; }
.btn-primary { background: linear-gradient(135deg, #91C2FF, #B5D8FE); color: #fff; box-shadow: 0 8rpx 24rpx rgba(145,194,255,0.3); }
.btn-outline { background: #fff; color: #FFC3D8; border: 1px solid #FFC3D8; }

/* 门店信息行 */
.store-value { display: flex; flex-direction: column; align-items: flex-end; }
.store-tel { font-size: 24rpx; color: #91C2FF; margin-top: 4rpx; }

/* 佣金信息 */
.commission-value { color: #F59E0B; font-weight: bold; }
</style>
