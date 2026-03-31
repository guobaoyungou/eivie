<template>
<view class="container">
	<block v-if="isload">
		<!-- 退款金额区域 -->
		<view class="refund-amount-section">
			<view class="amount-label">退款金额</view>
			<view class="amount-value">
				<text class="currency">¥</text>
				<text class="amount">{{detail.pay_price}}</text>
			</view>
			<view class="amount-tip">退款金额将原路退回至您的支付账户</view>
		</view>
		
		<!-- 订单信息 -->
		<view class="section">
			<view class="info-item">
				<text class="label">订单编号</text>
				<text class="value">{{detail.ordernum}}</text>
			</view>
			<view class="info-item">
				<text class="label">场景名称</text>
				<text class="value">{{detail.scene_name || detail.template_name || '场景模板'}}</text>
			</view>
			<view class="info-item">
				<text class="label">支付方式</text>
				<text class="value">{{detail.paytype || '-'}}</text>
			</view>
			<view class="info-item">
				<text class="label">支付金额</text>
				<text class="value price">¥{{detail.pay_price}}</text>
			</view>
		</view>
		
		<!-- 退款原因 -->
		<view class="section">
			<view class="section-title">退款原因 <text class="required">*</text></view>
			<view class="reason-list">
				<view 
					v-for="(item, idx) in reasonList" 
					:key="idx" 
					:class="['reason-item', selectedReason == idx ? 'active' : '']"
					@tap="selectReason(idx)"
				>
					<text>{{item}}</text>
					<text v-if="selectedReason == idx" class="check-icon">✓</text>
				</view>
			</view>
			<view class="textarea-wrap">
				<textarea 
					v-model="reason" 
					placeholder="请补充退款原因（必填）" 
					maxlength="200"
					:auto-height="false"
					class="reason-textarea"
				></textarea>
				<view class="word-count">{{reason.length}}/200</view>
			</view>
		</view>
		
		<!-- 提交按钮 -->
		<view class="submit-section">
			<view class="btn-submit" :class="submitting ? 'disabled' : ''" @tap="submitRefund">
				{{submitting ? '提交中...' : '提交退款申请'}}
			</view>
		</view>
		
		<view style="height: 40rpx;"></view>
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
			submitting: false,
			detail: null,
			orderId: 0,
			reason: '',
			selectedReason: -1,
			reasonList: [
				'生成失败，效果不符合预期',
				'等待时间过长',
				'误操作购买',
				'其他原因'
			]
		};
	},
	
	onLoad(opt) {
		this.opt = app.getopts(opt);
		this.orderId = this.opt.id;
		this.getdata();
	},
	
	methods: {
		getdata() {
			var that = this;
			that.loading = true;
			
			app.get('ApiAivideo/generation_order_detail', { order_id: that.orderId }, function(res) {
				that.loading = false;
				that.isload = true;
				
				if (res.status == 1) {
					that.detail = res.data;
					// 校验是否可以退款
					if (res.data.pay_status != 1) {
						app.alert('该订单未支付，无法申请退款', function() {
							uni.navigateBack();
						});
						return;
					}
					if (res.data.task_status != 3) {
						app.alert('仅生成失败的订单可以申请退款', function() {
							uni.navigateBack();
						});
						return;
					}
					if (res.data.refund_status == 1) {
						app.alert('该订单正在退款审核中，请勿重复申请', function() {
							uni.navigateBack();
						});
						return;
					}
					if (res.data.refund_status == 2) {
						app.alert('该订单已退款', function() {
							uni.navigateBack();
						});
						return;
					}
				} else {
					app.alert(res.msg, function() {
						uni.navigateBack();
					});
				}
			});
		},
		
		selectReason(idx) {
			this.selectedReason = idx;
			if (this.reasonList[idx] != '其他原因') {
				this.reason = this.reasonList[idx];
			} else {
				this.reason = '';
			}
		},
		
		submitRefund() {
			var that = this;
			
			if (that.submitting) return;
			
			var reason = that.reason.trim();
			if (!reason) {
				app.alert('请填写退款原因');
				return;
			}
			
			if (reason.length < 2) {
				app.alert('退款原因至少2个字');
				return;
			}
			
			app.confirm('确定提交退款申请？退款将原路退回至您的支付账户', function() {
				that.submitting = true;
				app.showLoading('提交中');
				
				app.post('ApiAivideo/generation_refund_apply', {
					order_id: that.orderId,
					refund_reason: reason
				}, function(res) {
					app.showLoading(false);
					that.submitting = false;
					
					if (res.status == 1) {
						app.success('退款申请已提交');
						setTimeout(function() {
							// 返回上一页并刷新
							var pages = getCurrentPages();
							if (pages.length >= 2) {
								var prevPage = pages[pages.length - 2];
								if (prevPage && prevPage.$vm && prevPage.$vm.getdata) {
									prevPage.$vm.getdata();
								}
							}
							uni.navigateBack();
						}, 1500);
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

.refund-amount-section { 
	background: linear-gradient(135deg, #91C2FF, #B5D8FE); 
	padding: 60rpx 30rpx; 
	text-align: center; 
	color: #fff; 
}
.amount-label { font-size: 28rpx; opacity: 0.9; margin-bottom: 20rpx; }
.amount-value { display: flex; align-items: baseline; justify-content: center; margin-bottom: 16rpx; }
.amount-value .currency { font-size: 36rpx; margin-right: 8rpx; }
.amount-value .amount { font-size: 72rpx; font-weight: bold; }
.amount-tip { font-size: 24rpx; opacity: 0.8; }

.section { background: #fff; margin: 20rpx; border-radius: 24rpx; padding: 30rpx; box-shadow: 0 6rpx 20rpx rgba(0,0,0,0.05); }
.section-title { font-size: 30rpx; font-weight: bold; color: #555555; margin-bottom: 24rpx; }
.section-title .required { color: #FFA0B8; }

.info-item { display: flex; justify-content: space-between; padding: 16rpx 0; }
.info-item .label { font-size: 28rpx; color: #666; flex-shrink: 0; }
.info-item .value { font-size: 28rpx; color: #555555; text-align: right; flex: 1; margin-left: 30rpx; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.info-item .value.price { color: #91C2FF; font-weight: bold; }

.reason-list { margin-bottom: 24rpx; }
.reason-item { 
	display: flex; 
	justify-content: space-between; 
	align-items: center;
	padding: 24rpx 20rpx; 
	border: 1px solid #F0EDF5; 
	border-radius: 12rpx; 
	margin-bottom: 16rpx;
	font-size: 28rpx;
	color: #555555;
}
.reason-item.active { 
	border-color: #91C2FF; 
	background: rgba(181,216,254,0.1); 
	color: #91C2FF; 
}
.check-icon { color: #91C2FF; font-size: 32rpx; font-weight: bold; }

.textarea-wrap { position: relative; }
.reason-textarea { 
	width: 100%; 
	height: 200rpx; 
	padding: 20rpx; 
	border: 1px solid #F0EDF5; 
	border-radius: 12rpx; 
	font-size: 28rpx; 
	color: #555555;
	box-sizing: border-box;
	background: #F5F0FA;
}
.word-count { 
	position: absolute; 
	right: 20rpx; 
	bottom: 20rpx; 
	font-size: 24rpx; 
	color: #999; 
}

.submit-section { padding: 40rpx 30rpx; }
.btn-submit { 
	height: 96rpx; 
	line-height: 96rpx; 
	text-align: center; 
	background: linear-gradient(135deg, #91C2FF, #B5D8FE); 
	color: #fff; 
	font-size: 32rpx; 
	font-weight: bold;
	border-radius: 40rpx; 
	box-shadow: 0 8rpx 24rpx rgba(145,194,255,0.3);
}
.btn-submit.disabled { opacity: 0.5; box-shadow: none; }
</style>
