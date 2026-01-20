<template>
	<view class="body">
		<block v-if="isload">
			<!-- <view class="header" :style="{background:'linear-gradient(180deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0) 100%)'}">
				审核结果
			</view> -->
			<view class="container">
					<block>
						<!-- 审核结果 -->
						<!-- 微信审核 -->
						<view class="box box-tips result-box">
							<view class="tips-status">
								<image :src="pre_url+'/static/img/success_1.png'" mode="widthFix" v-if="form.status == 'APPLYMENT_STATE_FINISHED'"></image> 
								<image :src="pre_url+'/static/img/error2.png'" mode="widthFix" v-else></image>
							</view>
							<view class="status-text">{{form.status_msg}}</view>
							<view class="time-text mb0" v-if="form.mchid_id">商户号：{{form.mchid_id}}</view>
							<view class="time-text">提交时间：{{dateFormat(form.createtime,'Y-m-d H:i:s')}}</view>
							<view class="back-to-edit-btn" v-if="form.status == 'APPLYMENT_STATE_EDITTING' || form.status == '' || form.status == 'APPLYMENT_STATE_REJECTED'">
								<button class="edit-btn" :style="'color:'+t('color1')+';border:1px solid '+ t('color1')" @tap="goto" data-url="wxjinjian">返回编辑</button>
							</view>
						</view>
						<view class="box box-tips" v-if="form.reason">
							<view class="form-title">
								<view class="strip" :style="{background:t('color1')}"></view>
								<view class="title">驳回原因</view>
							</view>
							<view class="form-reason">
								{{form.reason}}
							</view>
						</view>
						<view class="box box-tips" v-if="form.sign_url_qrcode">
							<view class="form-title">
								<view class="strip" :style="{background:t('color1')}"></view>
								<view class="title">签约二维码</view>
							</view>
							<view class="qrcode-box">
								<view class="qrcode">
									<image :src="form.sign_url_qrcode" mode="widthFix"></image>
								</view>
								<view class="qrcode-tips">请管理员用微信扫描上面二维码进行签约验证</view>
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
				type:0,
				txt: 1,
			}
		},
		onLoad: function(opt) {
			var that = this;
			var opt = app.getopts(opt);
			that.opt = opt;
			that.id = that.opt.id || 0
			that.type = that.opt.type || 0
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
				app.get('ApiAdminBusinessJinjian/result', {type:that.type}, function(res) {
					that.loading = false;
					if(res.status == 2){
						app.alert(res.msg,function(){
							app.goto('index','redirect');
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
	.container{position:absolute;width:100%;padding-bottom:100rpx}
	.box{background:#FFFFFF;border-radius:24rpx;padding:20rpx;width:92%;margin:0 4% 20rpx 4%}
	.content{line-height:40rpx;font-size:24rpx}
	.placeholder{font-size:24rpx;color:#BBBBBB}
	.form-item{display:flex;align-items:center;margin-bottom:20rpx}
	.form-label{flex-shrink:0;width:65px;text-align:right;margin-right:20rpx;color:#999}
	.form-value{flex:1;padding:0 10rpx}
	.radio-row{display:flex;flex-direction:column;width:100%}
	.form-label .required{color:#ff2400}
	.form-tips{font-size:24rpx;flex-shrink:0;color:#999}
	.form-tips-block{font-size:24rpx;flex-shrink:0;color:#999;background:#f8f8f8;padding:20rpx;margin-top:-30rpx;margin-bottom:20rpx}
	.form-opt{position:fixed;bottom:0;width:92%;left:4%;background:#F6F6F6;height:120rpx;display:flex;align-items:center;justify-content:space-between;font-size:24rpx;color:#333;z-index:100}
	.btn{text-align:center;border-radius:50rpx;color:#FFFFFF;flex:1;height:84rpx;line-height:84rpx}
	.btn.btn0{background:#bbb;color:#FFFFFF}
	.st_tips{background:#f6f6f6;padding:12rpx;font-size:24rpx;border-radius:10rpx;color:#515151;line-height:36rpx}
	.result-box{text-align:center;display:flex;flex-direction:column;align-items:center;gap:13px}
	.tips-status{width:100rpx;height:100rpx;display:flex;justify-content:center;align-items:center}
	.tips-status image{width:100%;height:100%}
	.status-text{font-size:30rpx}
	.time-text{color:gray;font-size:24rpx;margin-bottom:20rpx}
	.back-to-edit-btn{display:flex;justify-content:center;margin-bottom:20rpx}
	.edit-btn{background-color:#fff;border-radius:10rpx;padding:0 80rpx;margin-top:20rpx;font-size:28rpx}
	.form-title{display:flex;font-size:32rpx;width:100%;border-bottom:1px #f5f5f5 solid;height:88rpx;line-height:88rpx;overflow:hidden}
	.form-title .strip{margin:24rpx 10rpx;padding:5rpx}
	.form-reason{padding:20rpx;color:#EE5555;margin-top:20rpx}
	.qrcode-box{display:flex;flex-direction:column;align-items:center}
	.qrcode{width:350rpx;height:350rpx}
	.qrcode image{width:100%;height:100%}
	.qrcode-tips{color:#999}
	.mb0{margin-bottom:0}
</style>
