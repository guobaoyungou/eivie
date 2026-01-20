<template>
	<view>
		<block v-if="isload">
			<view class="container2">
				<image :src="set.banner" style="width:100%;height:auto" mode="widthFix" v-if="set && set.banner"></image>
				<view class="form">
					<form @submit="formSubmit">
						<view class="form-box">
							<view class="form-item">
								<view class="f1"><text style="color:red"> *</text>姓名：</view>
								<view class="f2"><input type="text" name="name" value="" placeholder="请填写姓名" placeholder-style="color:#888"></input></view>
							</view>
							<view class="form-item">
								<view class="f1"><text style="color:red"> *</text>身份证：</view>
								<view class="f2"><input type="text" name="idcard" value="" placeholder="请填写身份证号码" placeholder-style="color:#888"></input></view>
							</view>	
							<view class="form-item">
								<view class="f1"><text style="color:red"> *</text>手机号：</view>
								<view class="f2"><input type="text" name="mobile" value="" placeholder="请填写手机号" placeholder-style="color:#888"></input></view>
							</view>	
						</view>	
						<view class="form-box form-desc" v-if="set && set.desc"> {{set.desc}}</view>
						<button v-if="!isSubmit" class="savebtn" style="background: #999;">确认提交</button>
						<button v-else class="savebtn" :style="'background:linear-gradient(90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'" form-type="submit">提交</button>
					</form>
				</view>
			</view>
		</block>
		<loading v-if="loading"></loading>
		<dp-tabbar :opt="opt" @getmenuindex="getmenuindex"></dp-tabbar>
		<popmsg ref="popmsg"></popmsg>
		<wxxieyi></wxxieyi>
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
				set: '',
				cid:0,
				isSubmit:true
			};
		},
		onLoad: function(opt) {
			this.opt = app.getopts(opt);
			this.cid = this.opt.cid || 0;
		},
		onShow() {
			this.getdata();
		},
		methods: {
			getdata: function() {
				var that = this;
				that.loading = true;
				app.post('ApiChinaumsSubsidy/index', {type:0,cid:that.cid}, function(res) {
					that.loading = false;
					if(res.status == 1){
						let data = res.data;
						if(data && data.set){
							that.set = data.set;
						}
						if(data && data.isApply){
							that.isSubmit = false;
							uni.showToast({
								title: data.tipsText,
								duration: 3000,
								icon: 'error'
							});
						}
					}
					that.loaded();
				});
			},
			formSubmit: function (e) {
			  var that = this;
			  var formdata = e.detail.value;
				if(!formdata.name) return app.error('请填写姓名');
				
				// 验证身份证号
				var idCardReg = /(^\d{15}$)|(^\d{18}$)|(^\d{17}(\d|X|x)$)/;
				if (!formdata.idcard) {
					return app.error('请填写身份证号码');
				}
				if (!idCardReg.test(formdata.idcard)) {
					return app.error('请输入正确的身份证号码');
				}
		
				// 验证手机号
				var mobileReg = /^1\d{10}$/;
				if (!formdata.mobile) {
					return app.error('请填写手机号');
				}
				if (!mobileReg.test(formdata.mobile)) {
					return app.error('请输入正确格式的手机号');
				}
				formdata['cid'] = that.cid;
				that.isSubmit = false;
				app.showLoading('提交中');
			  app.post('ApiChinaumsSubsidy/apply', {formdata}, function (res) {
			    if (res.status == 1) {
						app.showLoading(false);
						app.success(res.msg);
						setTimeout(function () {
							app.goto('index','reLaunch');
						}, 500);
			    } else {
						that.isSubmit = true;
			      app.error(res.msg);
			    }
			  });
			},
		}
	};
</script>
<style>
	.container2{width:100%;padding:20rpx;background:#fff;height: 100vh;}
	.form-box{ width:710rpx;margin:0 auto;padding:2rpx 24rpx 0 24rpx; background: #fff;border-radius: 10rpx;}
	.form-item{ line-height: 100rpx; display: flex;justify-content: space-between;border-bottom:1px solid #eee }
	.form-item .f1{color:#222;width:150rpx;flex-shrink:0}
	.form-item .f2{display:flex;align-items:center;flex: 1;}
	.form-item input{ width: 100%; border: none;color:#111;font-size:28rpx;}
	.form-desc{margin-top: 20rpx;font-size: 26rpx;line-height: 45rpx;}
	.savebtn{ width: 90%; height:96rpx; line-height: 96rpx; text-align:center;border-radius:48rpx; color: #fff;font-weight:bold;margin: 0 5%; margin-top:60rpx; border: none; }
</style>