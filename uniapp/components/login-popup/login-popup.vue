<template>
	<uni-popup ref="popup" type="center" :maskClick="false" @change="onPopupChange">
		<view class="login-popup-container">
			<!-- 关闭按钮 -->
			<view class="login-popup-close" @tap="close">
				<text class="login-popup-close-icon">✕</text>
			</view>

			<!-- Logo区域 -->
			<view class="login-popup-logo-wrap">
				<image v-if="loginConfig.logo" :src="loginConfig.logo" class="login-popup-logo" mode="aspectFit"></image>
				<image v-else :src="pre_url + '/static/img/touxiang.png'" class="login-popup-logo" mode="aspectFit"></image>
			</view>

			<!-- 提示文案 -->
			<view class="login-popup-title">登录后继续操作</view>
			<view class="login-popup-subtitle" v-if="loginConfig.name">{{loginConfig.name}}</view>

			<!-- 微信授权登录按钮 (仅微信小程序) -->
			<!-- #ifdef MP-WEIXIN -->
			<view class="login-popup-btn-wrap">
				<button class="login-popup-btn login-popup-btn-wx" :disabled="isLoading" @tap="onWxAuthLogin">
					<image :src="pre_url + '/static/img/weixin.png'" class="login-popup-btn-icon" mode="aspectFit"></image>
					<text class="login-popup-btn-text">{{isLoading ? '登录中…' : '微信授权登录'}}</text>
				</button>
			</view>

			<!-- 手机号快捷登录按钮 (仅微信小程序) -->
			<view class="login-popup-btn-wrap" v-if="showPhoneLogin">
				<button class="login-popup-btn login-popup-btn-phone" :disabled="isLoading" open-type="getPhoneNumber" @getphonenumber="onGetPhoneNumber">
					<text class="login-popup-btn-icon-text">📱</text>
					<text class="login-popup-btn-text">手机号快捷登录</text>
				</button>
			</view>
			<!-- #endif -->

			<!-- 非微信小程序环境降级处理 -->
			<!-- #ifndef MP-WEIXIN -->
			<view class="login-popup-btn-wrap">
				<!-- H5微信公众号环境：显示微信授权登录 -->
				<button v-if="isWechatH5" class="login-popup-btn login-popup-btn-wx" :disabled="isLoading" @tap="onWechatH5Login">
					<image :src="pre_url + '/static/img/weixin.png'" class="login-popup-btn-icon" mode="aspectFit"></image>
					<text class="login-popup-btn-text">{{isLoading ? '登录中…' : '微信授权登录'}}</text>
				</button>
				<!-- 非H5微信环境：显示普通登录 -->
				<button v-else class="login-popup-btn login-popup-btn-wx" :disabled="isLoading" @tap="onFallbackLogin">
					<text class="login-popup-btn-text">{{isLoading ? '登录中…' : '立即登录'}}</text>
				</button>
			</view>
			<!-- #endif -->

			<!-- 协议勾选区域 -->
			<view class="login-popup-agreement" v-if="xystatus == 1">
				<checkbox-group @change="onAgreeChange">
					<checkbox style="transform: scale(0.55);" value="1" :checked="isagree" />
					<text class="login-popup-agreement-tip">{{loginConfig.xytipword || '我已阅读并同意'}}</text>
				</checkbox-group>
				<text class="login-popup-agreement-link" @tap="showAgreement">{{loginConfig.xyname || '用户协议'}}</text>
				<text class="login-popup-agreement-tip" v-if="loginConfig.xyname2">和</text>
				<text class="login-popup-agreement-link" @tap="showAgreement2" v-if="loginConfig.xyname2">{{loginConfig.xyname2}}</text>
			</view>

			<!-- 暂不登录 -->
			<view class="login-popup-skip" @tap="close">
				<text class="login-popup-skip-text">暂不登录</text>
			</view>
		</view>
	</uni-popup>
</template>

<script>
var app = getApp();

export default {
	name: 'login-popup',
	data() {
		return {
			showPopup: false,
			isLoading: false,
			isagree: false,
			xystatus: 0,
			loginConfig: {},
			successCallback: null,
			showPhoneLogin: false,
			pre_url: app.globalData.pre_url,
			configLoaded: false
		};
	},
	computed: {
		/**
		 * 判断是否为H5微信环境
		 */
		isWechatH5() {
			// #ifdef H5
			return app.globalData.platform === 'mp';
			// #endif
			// #ifndef H5
			return false;
			// #endif
		}
	},
	methods: {
		/**
		 * 公开方法：打开弹窗并注册成功回调函数
		 * @param {Function} callback 登录成功后要执行的业务回调
		 */
		open(callback) {
			this.successCallback = callback || null;
			this.isLoading = false;
			this.$refs.popup.open();
			this.showPopup = true;
			// 加载登录配置（仅首次）
			if (!this.configLoaded) {
				this.loadLoginConfig();
			}
		},

		/**
		 * 关闭弹窗
		 */
		close() {
			this.isLoading = false;
			this.$refs.popup.close();
			this.showPopup = false;
			this.$emit('close');
		},

		/**
		 * 弹窗状态变化回调
		 */
		onPopupChange(e) {
			if (!e.show) {
				this.showPopup = false;
			}
		},

		/**
		 * 加载登录页面配置信息
		 */
		loadLoginConfig() {
			var that = this;
			app.get('ApiIndex/login', { pid: app.globalData.pid }, function(res) {
				if (res && res.status != 0) {
					that.configLoaded = true;
					that.loginConfig = {
						logo: res.logo || '',
						name: res.name || '',
						xytipword: res.loginset_data ? res.loginset_data.xytipword : '我已阅读并同意',
						xyname: res.xyname || '',
						xycontent: res.xycontent || '',
						xyname2: res.xyname2 || '',
						xycontent2: res.xycontent2 || ''
					};
					that.xystatus = res.xystatus || 0;
					that.showPhoneLogin = res.logintype_8 || false;
				}
			});
		},

		/**
		 * 协议勾选变化
		 */
		onAgreeChange(e) {
			this.isagree = e.detail.value.length > 0;
		},

		/**
		 * 显示用户协议
		 */
		showAgreement() {
			if (this.loginConfig.xycontent) {
				uni.navigateTo({
					url: '/pages/index/webView?url=' + encodeURIComponent(this.loginConfig.xycontent)
				});
			}
		},

		/**
		 * 显示隐私政策
		 */
		showAgreement2() {
			if (this.loginConfig.xycontent2) {
				uni.navigateTo({
					url: '/pages/index/webView?url=' + encodeURIComponent(this.loginConfig.xycontent2)
				});
			}
		},

		/**
		 * 检查协议勾选状态
		 */
		checkAgreement() {
			if (this.xystatus == 1 && !this.isagree) {
				app.error('请先阅读并同意用户协议');
				return false;
			}
			return true;
		},

		/**
		 * 微信授权登录
		 */
		onWxAuthLogin() {
			if (this.isLoading) return;
			if (!this.checkAgreement()) return;

			var that = this;
			that.isLoading = true;

			// #ifdef MP-WEIXIN
			app.authlogin(function(res) {
				that.handleLoginResult(res);
			}, { authlogin: 2 });
			// #endif
		},
		
		/**
		 * H5微信公众号授权登录
		 */
		onWechatH5Login() {
			if (this.isLoading) return;
			if (!this.checkAgreement()) return;

			var that = this;
			that.isLoading = true;

			// #ifdef H5
			app.authlogin(function(res) {
				that.handleLoginResult(res);
			}, { authlogin: 2 });
			// #endif
		},

		/**
		 * 手机号授权登录 (微信小程序 getPhoneNumber 回调)
		 */
		onGetPhoneNumber(e) {
			if (this.isLoading) return;
			if (!this.checkAgreement()) return;

			// #ifdef MP-WEIXIN
			var that = this;

			if (e.detail.errMsg == "getPhoneNumber:fail user deny") {
				app.error('请同意授权获取手机号');
				return;
			}
			if (!e.detail.iv || !e.detail.encryptedData) {
				app.error('请同意授权获取手机号');
				return;
			}

			that.isLoading = true;

			wx.login({
				success: function(res1) {
					var code = res1.code;
					app.post('ApiIndex/wxTelLogin', {
						iv: e.detail.iv,
						encryptedData: e.detail.encryptedData,
						code: code,
						pid: app.globalData.pid,
						yqcode: app.globalData.wxregyqcode || '',
						regbid: app.globalData.regbid
					}, function(res) {
						that.handleLoginResult(res);
					});
				},
				fail: function() {
					that.isLoading = false;
					app.error('微信登录失败，请重试');
				}
			});
			// #endif
		},

		/**
		 * 非微信小程序环境降级处理：跳转到登录页
		 */
		onFallbackLogin() {
			this.close();
			var frompage = encodeURIComponent(app._fullurl());
			app.goto('/pages/index/login?frompage=' + frompage, 'navigate');
		},

		/**
		 * 统一处理登录结果
		 * @param {Object} res 登录接口返回结果
		 */
		handleLoginResult(res) {
			var that = this;
			that.isLoading = false;

			if (res.status == 1) {
				// 登录成功
				if (res.msg) app.success(res.msg);
				that.close();
				that.$emit('login-success', res);
				// 执行业务回调
				if (typeof that.successCallback == 'function') {
					setTimeout(function() {
						that.successCallback();
						that.successCallback = null;
					}, 300);
				}
			} else if (res.status == 2) {
				// 需要绑定手机号
				that.close();
				var frompage = encodeURIComponent(app._fullurl());
				app.goto('/pages/index/login?frompage=' + frompage + '&logintype=4&login_bind=1', 'navigate');
			} else if (res.status == 3) {
				// 需要设置头像昵称
				that.close();
				var frompage = encodeURIComponent(app._fullurl());
				app.goto('/pages/index/login?frompage=' + frompage + '&logintype=5', 'navigate');
			} else if (res.status == 4) {
				// 需要填写邀请码
				that.close();
				var frompage = encodeURIComponent(app._fullurl());
				app.goto('/pages/index/login?frompage=' + frompage + '&logintype=6', 'navigate');
			} else {
				// 登录失败，弹窗内提示错误
				app.error(res.msg || '登录失败，请重试');
				that.$emit('login-fail', res);
			}
		}
	}
};
</script>

<style scoped>
.login-popup-container {
	width: 600rpx;
	background: #fff;
	border-radius: 28rpx;
	padding: 50rpx 40rpx 40rpx;
	position: relative;
	box-shadow: 0 20rpx 60rpx rgba(0, 0, 0, 0.15);
}

.login-popup-close {
	position: absolute;
	top: 20rpx;
	right: 20rpx;
	width: 56rpx;
	height: 56rpx;
	display: flex;
	align-items: center;
	justify-content: center;
	z-index: 10;
}

.login-popup-close-icon {
	font-size: 32rpx;
	color: #999;
}

.login-popup-logo-wrap {
	display: flex;
	justify-content: center;
	margin-bottom: 24rpx;
}

.login-popup-logo {
	width: 140rpx;
	height: 140rpx;
	border-radius: 20rpx;
}

.login-popup-title {
	text-align: center;
	font-size: 34rpx;
	font-weight: bold;
	color: #333;
	margin-bottom: 8rpx;
}

.login-popup-subtitle {
	text-align: center;
	font-size: 26rpx;
	color: #999;
	margin-bottom: 20rpx;
}

.login-popup-btn-wrap {
	margin-top: 30rpx;
}

.login-popup-btn {
	display: flex;
	align-items: center;
	justify-content: center;
	width: 100%;
	height: 96rpx;
	line-height: 96rpx;
	border-radius: 48rpx;
	font-size: 30rpx;
	font-weight: bold;
	border: none;
}

.login-popup-btn::after {
	border: none;
}

.login-popup-btn-wx {
	background: linear-gradient(135deg, #07c160, #06ad56);
	color: #fff;
}

.login-popup-btn-phone {
	background: #f5f5f5;
	color: #333;
}

.login-popup-btn-icon {
	width: 40rpx;
	height: 40rpx;
	margin-right: 12rpx;
}

.login-popup-btn-icon-text {
	font-size: 36rpx;
	margin-right: 12rpx;
}

.login-popup-btn-text {
	font-size: 30rpx;
}

.login-popup-agreement {
	display: flex;
	align-items: center;
	justify-content: center;
	flex-wrap: wrap;
	margin-top: 30rpx;
	font-size: 22rpx;
}

.login-popup-agreement-tip {
	color: #999;
	font-size: 22rpx;
}

.login-popup-agreement-link {
	color: #07c160;
	font-size: 22rpx;
}

.login-popup-skip {
	text-align: center;
	margin-top: 30rpx;
	padding: 10rpx 0;
}

.login-popup-skip-text {
	font-size: 26rpx;
	color: #999;
}
</style>
