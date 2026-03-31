<template>
	<view class="uni-image-upload" :style="{
		backgroundColor: params.bgcolor || '#ffffff',
		margin: (params.margin_y || 0) * 2.2 + 'rpx ' + (params.margin_x || 0) * 2.2 + 'rpx 0',
		padding: (params.padding_y || 0) * 2.2 + 'rpx ' + (params.padding_x || 0) * 2.2 + 'rpx',
		opacity: disabled ? 0.5 : 1
	}">
		<!-- 图片预览网格 -->
		<view class="uni-image-upload__grid" :style="{
			gridTemplateColumns: 'repeat(' + columns + ', 1fr)'
		}">
			<!-- 已上传图片列表 -->
			<view class="uni-image-upload__item" v-for="(url, index) in innerValue" :key="'img_' + index">
				<image class="uni-image-upload__img" :src="url" mode="aspectFill" @tap="onPreview(index)"></image>
				<view v-if="showSource && fileSources[url]" class="uni-image-upload__source-tag">
					<text class="uni-image-upload__source-tag-text">{{ fileSources[url] }}</text>
				</view>
				<view v-if="deletable && !disabled" class="uni-image-upload__delete" @tap.stop="onDelete(index)">
					<text class="uni-image-upload__delete-icon">×</text>
				</view>
			</view>

			<!-- 上传中的文件 -->
			<view class="uni-image-upload__item" v-for="(file, fIdx) in uploadingFiles" :key="'up_' + fIdx">
				<image class="uni-image-upload__img" :src="file.tempPath" mode="aspectFill"></image>
				<!-- 上传中遮罩 -->
				<view v-if="file.status === 'uploading'" class="uni-image-upload__mask">
					<text class="uni-image-upload__progress-text">{{ file.progress }}%</text>
				</view>
				<!-- 上传失败遮罩 -->
				<view v-if="file.status === 'failed'" class="uni-image-upload__mask uni-image-upload__mask--failed" @tap.stop="onRetry(fIdx)">
					<text class="uni-image-upload__retry-icon">↻</text>
					<text class="uni-image-upload__retry-text">点击重试</text>
				</view>
				<!-- 失败状态删除按钮 -->
				<view v-if="file.status === 'failed'" class="uni-image-upload__delete" @tap.stop="onRemoveUploading(fIdx)">
					<text class="uni-image-upload__delete-icon">×</text>
				</view>
			</view>

			<!-- 添加按钮 -->
			<view v-if="showAddBtn" class="uni-image-upload__add" :class="{'uni-image-upload__add--active': addBtnPressed}" @tap="onAddTap"
				@touchstart="addBtnPressed = true" @touchend="addBtnPressed = false" @touchcancel="addBtnPressed = false">
				<text class="uni-image-upload__add-icon">+</text>
				<text class="uni-image-upload__add-text">上传图片</text>
				<text class="uni-image-upload__add-count">{{ innerValue.length + uploadingFiles.length }}/{{ maxCount }}</text>
			</view>
		</view>
	</view>
</template>

<script>
	var app = getApp();

	export default {
		name: 'uni-image-upload',
		props: {
			value: {
				type: Array,
				default: function() { return []; }
			},
			maxCount: {
				type: Number,
				default: 9
			},
			maxSize: {
				type: Number,
				default: 10
			},
			uploadUrl: {
				type: String,
				default: ''
			},
			showSource: {
				type: Boolean,
				default: false
			},
			deletable: {
				type: Boolean,
				default: true
			},
			previewable: {
				type: Boolean,
				default: true
			},
			disabled: {
				type: Boolean,
				default: false
			},
			columns: {
				type: Number,
				default: 4
			},
			acceptFormats: {
				type: Array,
				default: function() { return ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp']; }
			},
			enableCamera: {
				type: Boolean,
				default: true
			},
			cameraPosition: {
				type: String,
				default: 'back'
			},
			enableAlbum: {
				type: Boolean,
				default: true
			},
			enableChat: {
				type: Boolean,
				default: true
			},
			// dp-camera 兼容 params
			params: {
				type: Object,
				default: function() { return {}; }
			}
		},
		data: function() {
			return {
				innerValue: [],
				uploadingFiles: [],
				permissionDenied: false,
				addBtnPressed: false,
				fileSources: {}
			};
		},
		computed: {
			showAddBtn: function() {
				if (this.disabled) return false;
				return (this.innerValue.length + this.uploadingFiles.length) < this.maxCount;
			},
			remaining: function() {
				return this.maxCount - this.innerValue.length - this.uploadingFiles.length;
			},
			resolvedUploadUrl: function() {
				if (this.uploadUrl) return this.uploadUrl;
				if (this.params.upload_url) return this.params.upload_url;
				return app.globalData.baseurl + 'ApiImageupload/uploadImg/aid/' + app.globalData.aid + '/platform/' + app.globalData.platform + '/session_id/' + app.globalData.session_id;
			},
			resolvedMaxCount: function() {
				if (this.params.max_count) return parseInt(this.params.max_count) || this.maxCount;
				return this.maxCount;
			},
			resolvedMaxSize: function() {
				if (this.params.max_size) return parseInt(this.params.max_size) || this.maxSize;
				return this.maxSize;
			},
			resolvedColumns: function() {
				if (this.params.columns) return parseInt(this.params.columns) || this.columns;
				return this.columns;
			},
			resolvedCameraPosition: function() {
				return this.params.device_position || this.cameraPosition;
			},
			resolvedEnableAlbum: function() {
				if (this.params.enable_album !== undefined) return this.params.enable_album;
				return this.enableAlbum;
			},
			resolvedEnableChat: function() {
				if (this.params.enable_chat !== undefined) return this.params.enable_chat;
				return this.enableChat;
			}
		},
		watch: {
			value: {
				handler: function(val) {
					if (JSON.stringify(val) !== JSON.stringify(this.innerValue)) {
						this.innerValue = val ? [].concat(val) : [];
					}
				},
				immediate: true,
				deep: true
			}
		},
		methods: {
			/**
			 * 触发 value 同步
			 */
			_emitValue: function() {
				this.$emit('input', [].concat(this.innerValue));
			},

			/**
			 * 点击添加按钮
			 */
			onAddTap: function() {
				if (this.disabled) return;
				if (this.remaining <= 0) {
					this.$emit('exceed', { maxCount: this.resolvedMaxCount, currentCount: this.innerValue.length });
					uni.showToast({ title: '最多上传 ' + this.resolvedMaxCount + ' 张图片', icon: 'none' });
					return;
				}
				var that = this;
				uni.getNetworkType({
					success: function(res) {
						if (res.networkType === 'none') {
							uni.showToast({ title: '当前无网络，请检查网络后重试', icon: 'none' });
							return;
						}
						that._ensurePrivacyAuthorized();
					},
					fail: function() {
						that._ensurePrivacyAuthorized();
					}
				});
			},

			/**
			 * 主动检查微信隐私授权（使用 wx.requirePrivacyAuthorize 触发原生弹窗）
			 */
			_ensurePrivacyAuthorized: function() {
				// #ifdef MP-WEIXIN
				var that = this;
				if (typeof wx !== 'undefined' && wx.requirePrivacyAuthorize) {
					wx.requirePrivacyAuthorize({
						success: function() {
							that._showActionSheet();
						},
						fail: function() {
							// 用户拒绝隐私授权，静默忽略
							console.log('[uni-image-upload] privacy authorize rejected');
						}
					});
					return;
				}
				// #endif
				this._showActionSheet();
			},



			/**
			 * 构建并显示 ActionSheet
			 */
			_showActionSheet: function() {
				var that = this;
				var items = [];
				var actions = [];

				if (that.enableCamera) {
					items.push('拍照');
					actions.push('camera');
				}
				if (that.resolvedEnableAlbum) {
					items.push('从手机相册选择');
					actions.push('album');
				}

				// 微信端增加会话选择
				// #ifdef MP-WEIXIN
				if (that.resolvedEnableChat) {
					items.push('从微信会话选择');
					actions.push('wechat_chat');
				}
				// #endif

				// QQ端增加会话选择
				// #ifdef MP-QQ
				if (that.resolvedEnableChat) {
					items.push('从QQ会话选择');
					actions.push('qq_chat');
				}
				// #endif

				if (items.length === 0) {
					uni.showToast({ title: '未启用任何图片来源', icon: 'none' });
					return;
				}

				// 只有一个选项时直接执行
				if (items.length === 1) {
					that._executeAction(actions[0]);
					return;
				}

				// #ifdef MP-WEIXIN
				wx.showActionSheet({
					itemList: items,
					success: function(res) {
						that._executeAction(actions[res.tapIndex]);
					}
				});
				return;
				// #endif

				// #ifdef MP-QQ
				qq.showActionSheet({
					itemList: items,
					success: function(res) {
						that._executeAction(actions[res.tapIndex]);
					}
				});
				return;
				// #endif

				// #ifndef MP-WEIXIN || MP-QQ
				uni.showActionSheet({
					itemList: items,
					success: function(res) {
						that._executeAction(actions[res.tapIndex]);
					}
				});
				// #endif
			},

			/**
			 * 执行选择动作
			 */
			_executeAction: function(action) {
				var that = this;
				switch(action) {
					case 'camera':
						that._chooseFromCamera();
						break;
					case 'album':
						that._chooseFromAlbum();
						break;
					case 'wechat_chat':
						that._chooseFromWxChat();
						break;
					case 'qq_chat':
						that._chooseFromQQChat();
						break;
				}
			},

			/**
			 * 拍照
			 */
			_chooseFromCamera: function() {
				var that = this;

				// #ifdef MP-WEIXIN
				// 隐私已在 onAddTap 中授权，直接调用 chooseMedia，由系统自动处理相机权限弹窗
				wx.chooseMedia({
					count: 1,
					mediaType: ['image'],
					sourceType: ['camera'],
					camera: that.resolvedCameraPosition,
					success: function(r) {
						that._processFiles([r.tempFiles[0].tempFilePath], 'camera');
					},
					fail: function(err) {
						that._handleFail(err, 'chooseMedia_camera');
					}
				});
				return;
				// #endif

				// #ifdef MP-QQ
				qq.chooseImage({
					count: 1,
					sourceType: ['camera'],
					success: function(r) {
						that._processFiles(r.tempFilePaths, 'camera');
					},
					fail: function(err) {
						that._handleFail(err, 'qq_camera');
					}
				});
				return;
				// #endif

				// #ifdef APP-PLUS
				that._checkAppCameraPermission(function() {
					uni.chooseImage({
						count: 1,
						sizeType: ['original', 'compressed'],
						sourceType: ['camera'],
						success: function(r) {
							that._processFiles(r.tempFilePaths, 'camera');
						},
						fail: function(err) {
							that._handleFail(err, 'app_camera');
						}
					});
				});
				return;
				// #endif

				// #ifdef H5
				uni.chooseImage({
					count: 1,
					sizeType: ['original', 'compressed'],
					sourceType: ['camera'],
					success: function(r) {
						that._processFiles(r.tempFilePaths, 'camera');
					},
					fail: function(err) {
						that._handleFail(err, 'h5_camera');
					}
				});
				return;
				// #endif

				// #ifndef MP-WEIXIN || MP-QQ || APP-PLUS || H5
				uni.chooseImage({
					count: 1,
					sizeType: ['original', 'compressed'],
					sourceType: ['camera'],
					success: function(r) {
						that._processFiles(r.tempFilePaths, 'camera');
					},
					fail: function(err) {
						that._handleFail(err, 'other_camera');
					}
				});
				// #endif
			},

			/**
			 * 从相册选择
			 */
			_chooseFromAlbum: function() {
				var that = this;
				var cnt = that.remaining;

				// #ifdef MP-WEIXIN
				wx.chooseMedia({
					count: cnt,
					mediaType: ['image'],
					sourceType: ['album'],
					success: function(r) {
						var paths = [];
						for (var i = 0; i < r.tempFiles.length; i++) {
							paths.push(r.tempFiles[i].tempFilePath);
						}
						that._processFiles(paths, 'album');
					},
					fail: function(err) {
						that._handleFail(err, 'chooseMedia_album');
					}
				});
				return;
				// #endif

				// #ifdef MP-QQ
				qq.chooseImage({
					count: cnt,
					sourceType: ['album'],
					success: function(r) {
						that._processFiles(r.tempFilePaths, 'album');
					},
					fail: function(err) {
						that._handleFail(err, 'qq_album');
					}
				});
				return;
				// #endif

				// #ifdef APP-PLUS
				that._checkAppStoragePermission(function() {
					uni.chooseImage({
						count: cnt,
						sizeType: ['original', 'compressed'],
						sourceType: ['album'],
						success: function(r) {
							that._processFiles(r.tempFilePaths, 'album');
						},
						fail: function(err) {
							that._handleFail(err, 'app_album');
						}
					});
				});
				return;
				// #endif

				// #ifndef MP-WEIXIN || MP-QQ || APP-PLUS
				uni.chooseImage({
					count: cnt,
					sizeType: ['original', 'compressed'],
					sourceType: ['album'],
					success: function(r) {
						that._processFiles(r.tempFilePaths, 'album');
					},
					fail: function(err) {
						that._handleFail(err, 'other_album');
					}
				});
				// #endif
			},

			/**
			 * 从微信会话选择
			 */
			_chooseFromWxChat: function() {
				// #ifdef MP-WEIXIN
				var that = this;
				wx.chooseMessageFile({
					count: that.remaining,
					type: 'image',
					success: function(r) {
						if (r.tempFiles && r.tempFiles.length > 0) {
							var paths = [];
							for (var i = 0; i < r.tempFiles.length; i++) {
								var fileInfo = r.tempFiles[i];
								var vResult = that._validateFile(fileInfo);
								if (vResult.valid) {
									paths.push(fileInfo.path);
								} else {
									uni.showToast({ title: vResult.msg, icon: 'none' });
								}
							}
							if (paths.length > 0) {
								that._processFiles(paths, 'chat');
							}
						}
					},
					fail: function(err) {
						that._handleFail(err, 'chooseMessageFile');
					}
				});
				// #endif
			},

			/**
			 * 从QQ会话选择
			 */
			_chooseFromQQChat: function() {
				// #ifdef MP-QQ
				var that = this;
				qq.chooseMessageFile({
					count: that.remaining,
					type: 'image',
					success: function(r) {
						if (r.tempFiles && r.tempFiles.length > 0) {
							var paths = [];
							for (var i = 0; i < r.tempFiles.length; i++) {
								var fileInfo = r.tempFiles[i];
								var vResult = that._validateFile(fileInfo);
								if (vResult.valid) {
									paths.push(fileInfo.path);
								} else {
									uni.showToast({ title: vResult.msg, icon: 'none' });
								}
							}
							if (paths.length > 0) {
								that._processFiles(paths, 'chat');
							}
						}
					},
					fail: function(err) {
						that._handleFail(err, 'qq_chooseMessageFile');
					}
				});
				// #endif
			},

			/**
			 * 处理选中的文件（校验 + 上传）
			 */
			_processFiles: function(filePaths, source) {
				var that = this;
				for (var i = 0; i < filePaths.length; i++) {
					if (that.showSource) {
						var sourceLabels = { camera: '拍照', album: '相册', chat: '会话' };
						that.$set(that.fileSources, filePaths[i], sourceLabels[source] || source);
					}
					that._uploadFile(filePaths[i], i, source);
				}
			},

			/**
			 * 文件校验
			 */
			_validateFile: function(file) {
				var maxSizeBytes = this.resolvedMaxSize * 1024 * 1024;
				if (file.size && file.size > maxSizeBytes) {
					return { valid: false, msg: '文件大小超过 ' + this.resolvedMaxSize + 'MB 限制' };
				}
				var fileName = (file.name || file.path || file.tempFilePath || '').toLowerCase();
				var dotIdx = fileName.lastIndexOf('.');
				var ext = dotIdx > -1 ? fileName.substring(dotIdx + 1) : '';
				if (ext && this.acceptFormats.indexOf(ext) === -1) {
					return { valid: false, msg: '不支持该格式，请选择 ' + this.acceptFormats.join('/') + ' 格式图片' };
				}
				return { valid: true };
			},

			/**
			 * 上传单个文件
			 */
			_uploadFile: function(filePath, sortnum, source) {
				var that = this;
				var fileItem = {
					tempPath: filePath,
					progress: 0,
					status: 'uploading',
					source: source || ''
				};
				that.uploadingFiles.push(fileItem);

				// 仅默认上传地址时追加 sortnum 路径参数，自定义地址不追加
				var uploadUrl = that.resolvedUploadUrl;
				if (!that.uploadUrl && !that.params.upload_url) {
					uploadUrl += '/sortnum/' + sortnum;
				}

				var uploadTask = uni.uploadFile({
					url: uploadUrl,
					filePath: filePath,
					name: 'file',
					success: function(res) {
						try {
							var data;
							if (typeof res.data === 'string') {
								data = JSON.parse(res.data);
							} else {
								data = res.data;
							}

							if (data.status == 1) {
								var url = data.url;
								that.innerValue.push(url);
								that._removeFromUploading(filePath);
								// 记录 source
								if (that.showSource && source) {
									var sourceLabels = { camera: '拍照', album: '相册', chat: '会话' };
									that.$set(that.fileSources, url, sourceLabels[source] || source);
								}
								that._emitValue();
								that.$emit('upload-success', { url: url, index: that.innerValue.length - 1 });
							} else if (data.code == 0 || data.code === 0) {
								var url2 = (data.data && (data.data.src || data.data.url)) || '';
								if (url2) {
									that.innerValue.push(url2);
									that._removeFromUploading(filePath);
									if (that.showSource && source) {
										var sourceLabels2 = { camera: '拍照', album: '相册', chat: '会话' };
										that.$set(that.fileSources, url2, sourceLabels2[source] || source);
									}
									that._emitValue();
									that.$emit('upload-success', { url: url2, index: that.innerValue.length - 1 });
								} else {
									that._markFailed(filePath);
									that.$emit('upload-fail', { filePath: filePath, error: data });
								}
							} else {
								that._markFailed(filePath);
								that.$emit('upload-fail', { filePath: filePath, error: data });
								uni.showToast({ title: data.msg || '上传失败', icon: 'none' });
							}
						} catch(e) {
							that._markFailed(filePath);
							that.$emit('upload-fail', { filePath: filePath, error: { errMsg: e.message } });
							uni.showToast({ title: '上传失败', icon: 'none' });
						}
					},
					fail: function(err) {
						that._markFailed(filePath);
						that.$emit('upload-fail', { filePath: filePath, error: err });
						uni.showToast({ title: '上传失败，请重试', icon: 'none' });
					}
				});

				// 监听进度
				if (uploadTask && uploadTask.onProgressUpdate) {
					uploadTask.onProgressUpdate(function(progressRes) {
						var idx = that._findUploadingIndex(filePath);
						if (idx > -1) {
							that.$set(that.uploadingFiles, idx, Object.assign({}, that.uploadingFiles[idx], { progress: progressRes.progress }));
						}
					});
				}
			},

			_findUploadingIndex: function(filePath) {
				for (var i = 0; i < this.uploadingFiles.length; i++) {
					if (this.uploadingFiles[i].tempPath === filePath) return i;
				}
				return -1;
			},

			_removeFromUploading: function(filePath) {
				var idx = this._findUploadingIndex(filePath);
				if (idx > -1) this.uploadingFiles.splice(idx, 1);
			},

			_markFailed: function(filePath) {
				var idx = this._findUploadingIndex(filePath);
				if (idx > -1) {
					this.$set(this.uploadingFiles, idx, Object.assign({}, this.uploadingFiles[idx], { status: 'failed' }));
				}
			},

			/**
			 * 统一 fail 处理
			 */
			_handleFail: function(err, source) {
				var errMsg = (err && err.errMsg) ? err.errMsg : '';
				if (errMsg.indexOf('cancel') !== -1) return;
				// #ifdef MP-WEIXIN
				if (errMsg.indexOf('privacy') !== -1) {
					// 隐私授权失败，触发原生隐私弹窗并在授权后重试
					this._ensurePrivacyAuthorized();
					return;
				}
				// #endif
				if (source === 'chooseMedia_camera') {
					// #ifdef MP-WEIXIN
					this._guideToSettingWx('scope.camera');
					// #endif
				} else {
					uni.showToast({ title: '选择文件失败，请重试', icon: 'none' });
				}
			},

			// ========== 权限检查 ==========

_guideToSettingWx: function(scope) {
			// #ifdef MP-WEIXIN
			var msgs = {
				'scope.camera': '需要相机权限才能拍照，请前往设置页开启'
			};
			wx.showModal({
				title: '权限提示',
				content: msgs[scope] || '需要授权才能使用此功能，请前往设置页开启',
				showCancel: true,
				confirmText: '去设置',
				success: function(modalRes) {
					if (modalRes.confirm) {
						wx.openSetting();
					}
				}
			});
			// #endif
			// #ifndef MP-WEIXIN
			// H5和其他平台不处理权限引导
			return;
			// #endif
		},

			// #ifdef APP-PLUS
			_checkAppCameraPermission: function(successCb) {
				if (app && app.$store) {
					app.$store.dispatch('requestPermissions', 'CAMERA').then(function(result) {
						if (result === 1) {
							successCb();
						} else {
							uni.showToast({ title: '相机权限被拒绝', icon: 'none' });
						}
					}).catch(function() {
						successCb();
					});
				} else {
					successCb();
				}
			},

			_checkAppStoragePermission: function(successCb) {
				if (app && app.$store) {
					app.$store.dispatch('requestPermissions', 'WRITE_EXTERNAL_STORAGE').then(function(result) {
						if (result === 1) {
							successCb();
						} else {
							uni.showToast({ title: '存储权限被拒绝', icon: 'none' });
						}
					}).catch(function() {
						successCb();
					});
				} else {
					successCb();
				}
			},
			// #endif

			// ========== 用户交互 ==========

			onPreview: function(index) {
				if (!this.previewable || this.disabled) return;
				this.$emit('preview', { url: this.innerValue[index], index: index });
				uni.previewImage({
					current: this.innerValue[index],
					urls: this.innerValue
				});
			},

			onDelete: function(index) {
				if (this.disabled) return;
				var url = this.innerValue[index];
				this.innerValue.splice(index, 1);
				this._emitValue();
				this.$emit('delete', { url: url, index: index });
			},

			onRetry: function(fIdx) {
				var file = this.uploadingFiles[fIdx];
				if (!file) return;
				var filePath = file.tempPath;
				var source = file.source;
				this.uploadingFiles.splice(fIdx, 1);
				this._uploadFile(filePath, 0, source);
			},

			onRemoveUploading: function(fIdx) {
				this.uploadingFiles.splice(fIdx, 1);
			}
		}
	};
</script>

<style>
	.uni-image-upload {
		position: relative;
		width: 100%;
		box-sizing: border-box;
	}

	.uni-image-upload__grid {
		display: grid;
		grid-template-columns: repeat(4, 1fr);
		gap: 16rpx;
	}

	.uni-image-upload__item {
		position: relative;
		width: 100%;
		padding-bottom: 100%;
		border-radius: 12rpx;
		overflow: hidden;
		background-color: #f5f5f5;
	}

	.uni-image-upload__img {
		position: absolute;
		top: 0;
		left: 0;
		width: 100%;
		height: 100%;
	}

	.uni-image-upload__source-tag {
		position: absolute;
		left: 0;
		bottom: 0;
		background-color: rgba(0, 0, 0, 0.5);
		padding: 2rpx 10rpx;
		border-top-right-radius: 8rpx;
	}

	.uni-image-upload__source-tag-text {
		font-size: 20rpx;
		color: #ffffff;
	}

	.uni-image-upload__delete {
		position: absolute;
		top: 4rpx;
		right: 4rpx;
		width: 36rpx;
		height: 36rpx;
		border-radius: 50%;
		background-color: rgba(0, 0, 0, 0.5);
		display: flex;
		align-items: center;
		justify-content: center;
		z-index: 2;
	}

	.uni-image-upload__delete-icon {
		font-size: 24rpx;
		color: #ffffff;
		line-height: 36rpx;
		text-align: center;
	}

	.uni-image-upload__mask {
		position: absolute;
		top: 0;
		left: 0;
		width: 100%;
		height: 100%;
		background-color: rgba(0, 0, 0, 0.5);
		display: flex;
		flex-direction: column;
		align-items: center;
		justify-content: center;
		z-index: 1;
	}

	.uni-image-upload__mask--failed {
		background-color: rgba(0, 0, 0, 0.65);
	}

	.uni-image-upload__progress-text {
		font-size: 26rpx;
		color: #ffffff;
		font-weight: bold;
	}

	.uni-image-upload__retry-icon {
		font-size: 40rpx;
		color: #ffffff;
		margin-bottom: 6rpx;
	}

	.uni-image-upload__retry-text {
		font-size: 20rpx;
		color: #ffffff;
	}

	.uni-image-upload__add {
		position: relative;
		width: 100%;
		padding-bottom: 100%;
		border: 2rpx dashed #cccccc;
		border-radius: 12rpx;
		background-color: #fafafa;
		display: flex;
		flex-direction: column;
		align-items: center;
		justify-content: center;
		transition: opacity 0.15s;
	}

	.uni-image-upload__add--active {
		opacity: 0.7;
	}

	.uni-image-upload__add-icon {
		position: absolute;
		top: 50%;
		left: 50%;
		transform: translate(-50%, -80%);
		font-size: 48rpx;
		color: #999999;
		line-height: 1;
	}

	.uni-image-upload__add-text {
		position: absolute;
		top: 50%;
		left: 50%;
		transform: translate(-50%, 20%);
		font-size: 22rpx;
		color: #999999;
		white-space: nowrap;
	}

	.uni-image-upload__add-count {
		position: absolute;
		bottom: 8rpx;
		right: 10rpx;
		font-size: 20rpx;
		color: #bbbbbb;
	}
</style>
