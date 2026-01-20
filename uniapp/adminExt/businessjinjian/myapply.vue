<template>
	<view class="body">
		<block v-if="isload">
			<view class="header"
				:style="{background:'linear-gradient(180deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0) 100%)'}">
				进件记录
			</view>
			<view class="container">
					<block>
						<!-- 审核结果 -->
						<!-- 微信审核 -->
						<view class="box box-tips" v-if="form.wx_applyment_id">
							<view class="form-title">微信审核</view>
							<view class="form-item">
								<view class="form-label">状态</view>
								<view class="form-value">
									<view class="st_tag">{{form.wx_status_txt}}</view>
								</view>
							</view>
							<view class="form-item" v-if="form.wx_status == 'APPLYMENT_STATE_TO_BE_SIGNED' ||  form.wx_status == 'APPLYMENT_STATE_TO_BE_CONFIRMED' || form.wx_status == 'APPLYMENT_STATE_AUDITING'">
								<view class="form-label">签约二维码</view>
								<view class="form-value">
									<view class="st_tag"><image :src="form.sign_url_qrcode" mode="widthFix" style="width: 400rpx;height: 400rpx;"></image></view>
								</view>
							</view>
							<view class="form-item" v-if="form.wx_status != 'APPLYMENT_STATE_FINISHED' && form.wx_reason">
								<view class="form-label">原因</view>
								<view class="form-value">
									<view class="st_tag">{{form.wx_reason}}</view>
								</view>
							</view>
						</view>
						<!-- 支付宝审核 -->
						<view class="box box-tips" v-if="form.ali_applyment_id">
							<view class="form-title">支付宝审核</view>
							<view class="form-item">
								<view class="form-label">申请总体状态</view>
								<view class="form-value" v-if="form.ali_status == '-1'">
									<view class="st_tag">失败</view>
									<view class="st_tips" v-if="form.ali_errmsg">{{form.ali_errmsg}}</view>
								</view>
								<view class="form-value" v-else>
									<view class="st_tag" v-if="form.ali_status == '99'">已完结</view>
									<view class="st_tag" v-if="form.ali_status == '031'">审核中</view>
								</view>
							</view>
							<view class="form-item">
								<view class="form-label">二级商户确认状态</view>
								<view class="form-value">
									<view class="st_tag" v-if="form.ali_sub_confirm">{{form.ali_sub_confirm}}</view>
								</view>
							</view>
							<view class="form-item" v-if="form.ali_status =='99'">
								<view class="form-label">商户号</view>
								<view class="form-value">
									<view class="st_tag" v-if="form.ali_mchid">{{form.ali_mchid}}</view>
								</view>
							</view>
						</view>
					</block>
					<view class="form-opt">
						<button class="btn" v-if="form.ali_status == '-1' || form.wx_status=='APPLYMENT_STATE_EDITTING' || form.wx_status == 'APPLYMENT_STATE_REJECTED'" :style="{background:t('color1')}" @tap="goto" :data-url="'apply?id='+form.id">修改</button>
					</view>
			</view>
		</block>
		<loading v-if="loading"></loading>
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
				isload: false,
				pre_url: app.globalData.pre_url,
				step:1,
				id: 0,
				set:{},
				detail: {},
				form: {},
				txt: 1,
			}
		},
		onLoad: function(opt) {
			var that = this;
			var opt = app.getopts(opt);
			that.opt = opt;
			that.id = that.opt.id || 0
			that.getdata();
		},
		onPullDownRefresh: function() {
			this.getdata();
		},
		methods: {
			getdata: function() {
				var that = this;
				var id = that.opt.id;
				that.loading = true;
				app.get('ApiAdminBusinessJinjian/myapply', {}, function(res) {
					that.loading = false;
					if(res.status==2){
						app.alert(res.msg,function(){
							app.goto('apply');
						})
					}else if (res.status == 1) {
						that.form = res.data;
						that.loaded();
					} else {
						app.alert(res.msg);
					}
				})
			}
		}
	}
</script>
<style>
	page{position:relative;width:100%;height:100%}
	.flex-sb{display:flex;justify-content:space-between;align-items:center}
	.header{height:260rpx;position:absolute;top:0;width:100%;padding-top:70rpx;text-align:center;font-weight:bold;font-size:32rpx}
	.container{position:absolute;width:100%;top:160rpx;border-radius:16rpx;padding-bottom:100rpx}
	.box{background:#FFFFFF;border-radius:24rpx;padding:20rpx;width:92%;margin:0 4% 20rpx 4%}
	.content{line-height:40rpx;font-size:24rpx}
	.placeholder{font-size:24rpx;color:#BBBBBB}
	.form-item{display:flex;align-items:center;margin-bottom:20rpx}
	.form-title{font-size:32rpx;padding-bottom:20rpx;border-bottom:1rpx solid #f0f0f0;margin-bottom:30rpx}
	.form-label{flex-shrink:0;width:65px;text-align:right;margin-right:20rpx;color:#999}
	.form-value{flex:1;padding:0 10rpx}
	.form-upload{}
	.uploadrow{display:flex;align-items:center}
	.uploadbtn{width:150rpx;height:60rpx;line-height:60rpx;text-align:center;border:1rpx solid #F0F0F0;border-radius:6rpx;color:#666}
	.uploadbtn1{width:150rpx;height:150rpx}
	.uploadtip{font-size:20rpx;color:#999;padding-left:10rpx}
	.form-upload .imgitem{width:240rpx;height:150rpx;margin-right:10rpx;background:#f5f5f5;display:flex;align-items:center;justify-content:center;margin-bottom:10rpx}
	.form-upload .imgitem image{max-width:100%;max-height:100%}
	.picker-range{border:1rpx solid #F0F0F0;height:60rpx;line-height:60rpx;border-radius:6rpx;padding:0 10rpx}
	.imgbox{display:flex;align-items:center;flex-wrap:wrap}
	.imgbox .imgitem-pics{width:150rpx;height:150rpx;margin:8rpx;background:#F0F0F0}
	.imgbox .imgitem-pics image{max-width:100%;max-height:100%}
	.form-radio{border:none}
	.form-radio label{margin-right:20rpx}
	.form-value .form-select,.form-value input,.form-value .picker{font-size:24rpx;border-radius:8rpx;height:70rpx;line-height:70rpx;border:1rpx solid #f0f0f0;padding:0 10rpx;flex:1}
	.radio-row{display:flex;flex-direction:column;width:100%}
	.form-label .required{color:#ff2400}
	.form-tips{font-size:24rpx;flex-shrink:0;color:#999}
	.form-tips-block{font-size:24rpx;flex-shrink:0;color:#999;background:#f8f8f8;padding:20rpx;margin-top:-30rpx;margin-bottom:20rpx}
	.form-opt{position:fixed;bottom:0;width:92%;left:4%;background:#F6F6F6;height:120rpx;display:flex;align-items:center;justify-content:space-between;font-size:24rpx;color:#333;z-index:100}
	.btn{text-align:center;border-radius:50rpx;color:#FFFFFF;flex:1;height:84rpx;line-height:84rpx}
	.btn.btn0{background:#bbb;color:#FFFFFF}
	.st_tips{background:#f6f6f6;padding:12rpx;font-size:24rpx;border-radius:10rpx;color:#515151;line-height:36rpx}
</style>
