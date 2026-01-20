<template>
	<view class="dp-form" v-if="detail">
		<view style="width: 700rpx;margin: 0 auto;padding-bottom: 40rpx;">
			<form @submit="formSubmit">
				<view class="item" style="margin-top: 20rpx;">
					<view class="dp-form-item">
						<block v-if="detail.key=='separate'">
							<view class="dp-form-separate">{{detail.val1}}</view>
						</block>
						<view v-if="detail.key!='separate'" class="label">{{detail.val1}}<text v-if="detail.val3==1"
								style="color:red"> *</text></view>
						<block v-if="detail.key=='input'">

							<text v-if="detail.val5" style="margin-right:10rpx">{{detail.val5}}</text>
							<!-- 授权不可编辑S -->
							<block v-if="detail.val4==2 && detail.val6==1 && (platform =='mp' || platform == 'wx')">
								<input :type="(detail.val4==1 || detail.val4==2) ? 'digit' : 'text'" disabled="true"
									:name="'form'+index" class="input disabled" :placeholder="detail.val2"
									placeholder-style="font-size:28rpx" style="background-color:#efefef" :value="detail.content"
									@input="setfield" :data-formindex="'form'+index" />
								<button class="authtel" open-type="getPhoneNumber" type="primary" @getphonenumber="getPhoneNumber"
									:data-index="index">获取手机号码</button>
							</block>
							<!-- 授权不可编辑E -->
							<block v-else>
								<input :type="(detail.val4==1 || detail.val4==2) ? 'digit' : 'text'" readonly :name="'form'+index"
									class="input" :placeholder="detail.val2" placeholder-style="font-size:28rpx" :value="detail.content"
									@input="setfield" :data-formindex="'form'+index" />
							</block>

						</block>

						<block v-if="detail.key=='usercard'">
							<text v-if="detail.val5" style="margin-right:10rpx">{{detail.val5}}</text>
							<input type="idcard" readonly :name="'form'+index" class="input" :placeholder="detail.val2"
								placeholder-style="font-size:28rpx" :value="detail.content" @input="setfield"
								:data-formindex="'form'+index" />
						</block>
						<block v-if="detail.key=='textarea'">
							<textarea :name="'form'+index" class='textarea' :placeholder="detail.val2"
								placeholder-style="font-size:28rpx" :value="detail.content" @input="setfield"
								:data-formindex="'form'+index" />
						</block>
						<block v-if="detail.key=='radio'">
							<radio-group class="flex" :name="'form'+index" style="flex-wrap:wrap" @change="setfield"
								:data-formindex="'form'+index">
								<label v-for="(item1,index1) in detail.val2" :key="item1.id" class="flex-y-center">
									<radio class="radio" :value="item1"
										:checked="detail.content && detail.content==item1 ? true : false" />{{item1}}
								</label>
							</radio-group>
						</block>
						<block v-if="detail.key=='checkbox'">
							<checkbox-group :name="'form'+index" class="flex" style="flex-wrap:wrap" @change="setfield"
								:data-formindex="'form'+index">
								<label v-for="(item1,index1) in detail.val2" :key="item1.id" class="flex-y-center">
									<checkbox class="checkbox" :value="item1"
										:checked="detail.content && inArray(item1,detail.content) ? true : false" />{{item1}}
								</label>
							</checkbox-group>
						</block>
						<block v-if="detail.key=='selector'">
							<picker class="picker" mode="selector" :name="'form'+index" :value="editorFormdata[index]"
								:range="detail.val2" @change="editorBindPickerChange" :data-index="index"
								:data-formindex="'form'+index">
								<view v-if="editorFormdata[index] || editorFormdata[index]===0"> {{detail.val2[editorFormdata[index]]}}
								</view>
								<view v-else>请选择</view>
							</picker>
						</block>
						<block v-if="detail.key=='time'">
							<picker class="picker" mode="time" :name="'form'+index" :value="detail.content" :start="detail.val2[0]"
								:end="detail.val2[1]" :range="detail.val2" @change="editorBindPickerChange" :data-index="index"
								:data-formindex="'form'+index">
								<view v-if="editorFormdata[index]">{{editorFormdata[index]}}</view>
								<view v-else>请选择</view>
							</picker>
						</block>
						<block v-if="detail.key=='date'">
							<picker class="picker" mode="date" :name="'form'+index" :value="detail.content" :start="detail.val2[0]"
								:end="detail.val2[1]" :range="detail.val2" @change="editorBindPickerChange" :data-index="index"
								:data-formindex="'form'+index">
								<view v-if="editorFormdata[index]">{{editorFormdata[index]}}</view>
								<view v-else>请选择</view>
							</picker>
						</block>

						<block v-if="detail.key=='region'">
							<uni-data-picker :localdata="items" popup-title="请选择省市区" :placeholder="detail.content || '请选择省市区'"
								@change="onchange" :data-formindex="'form'+index"></uni-data-picker>
							<!-- <picker class="picker" mode="region" :name="'form'+index" value="" @change="editorBindPickerChange" :data-index="index">
                                <view v-if="editorFormdata[index]">{{editorFormdata[index]}}</view> 
                                <view v-else>请选择省市区</view>
                            </picker> -->
							<input type="text" style="display:none" :name="'form'+index"
								:value="regiondata ? regiondata : detail.content" />
						</block>
						<block v-if="detail.key=='upload'">
							<input type="text" style="display:none" :name="'form'+index" :value="editorFormdata[index]" />
							<view class="flex" style="flex-wrap:wrap;padding-top:20rpx">
								<view class="dp-form-imgbox" v-if="editorFormdata[index]">
									<view class="dp-form-imgbox-close" @tap="removeimg" :data-index="index"
										:data-formindex="'form'+index">
										<image :src="pre_url+'/static/img/ico-del.png'" class="image"></image>
									</view>
									<view class="dp-form-imgbox-img">
										<image class="image" :src="editorFormdata[index]" @click="previewImage"
											:data-url="editorFormdata[index]" mode="widthFix" :data-index="index" />
									</view>
								</view>
								<view v-else class="dp-form-uploadbtn"
									:style="{background:'url('+pre_url+'/static/img/shaitu_icon.png) no-repeat 60rpx',backgroundSize:'80rpx 80rpx',backgroundColor:'#F3F3F3'}"
									@click="editorChooseImage" :data-index="index" :data-formindex="'form'+index"></view>
							</view>
						</block>
						<!-- #ifdef H5 || MP-WEIXIN -->
						<block v-if="detail.key=='upload_file'">
							<input type="text" style="display:none" :name="'form'+index" :value="editorFormdata[index]" />
							<view class="flex" style="flex-wrap:wrap;padding-top:20rpx">
								<view class="dp-form-imgbox" v-if="editorFormdata[index]">
									<view class="dp-form-imgbox-close" @tap="removeimg" :data-index="index"
										:data-formindex="'form'+index">
										<image :src="pre_url+'/static/img/ico-del.png'" class="image"></image>
									</view>
									<view
										style="overflow: hidden;white-space: pre-wrap;word-wrap: break-word;color: #4786BC;width: 450rpx;"
										@tap="download" :data-file="editorFormdata[index]">
										{{editorFormdata[index]}}
									</view>
								</view>
								<view v-else class="dp-form-uploadbtn"
									:style="{background:'url('+pre_url+'/static/img/shaitu_icon.png) no-repeat 60rpx',backgroundSize:'80rpx 80rpx',backgroundColor:'#F3F3F3'}"
									@click="chooseFile" :data-index="index" :data-formindex="'form'+index"></view>
							</view>
						</block>
						<!-- #endif -->
					</view>
				</view>
				<button class="set-btn" form-type="submit"
					:style="{background:'linear-gradient(90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'}">
					保存
				</button>
			</form>
		</view>
		<wxxieyi></wxxieyi>
	</view>
</template>
<script>
	var app = getApp();
	export default {
		data() {
			return {
				opt: {},
				fromrecord: 0,
				orderid: 0,
				pre_url: getApp().globalData.pre_url,

				data: '',
				editorFormdata: [],
				test: 'test',
				regiondata: '',
				items: [],
				tmplids: [],
				submitDisabled: false,
				formvaldata: {},
				authphone: '',
				platform: '',

				index: '',
				detail: '',
				from: 'set',
			}
		},
		onLoad: function(opt) {
			var that = this;
			var opt = app.getopts(opt);
			if (opt && opt.index >= 0) {
				that.opt = opt;
				that.index = opt.index;
				that.from = opt.from || 'set';
				that.platform = app.getplatform();
				that.getarea();
				that.getdata();
			}
		},
		methods: {
			getarea: function() {
				var that = this;
				app.get(app.globalData.pre_url + '/static/area.json', {
					id: 0
				}, function(res) {
					that.items = res;
				});
			},
			getdata: function() {
				var that = this;
				var index = that.index;
				var url = that.from == "set" ? "ApiMy/otherset" : "ApiMy/registset";
				var mid = '';
				if(that.opt && that.opt.mid){
					mid = that.opt.mid;
				}
				app.get(url, {
					mid:mid,
					index: index
				}, function(res) {
					if (res.status == 1) {
						uni.setNavigationBarTitle({
							title: res.detail.val1
						});
						that.detail = res.detail;
					} else {
						app.alert(res.msg);
					}
				});
			},
			onchange(e) {
				const value = e.detail.value
				this.regiondata = value[0].text + ',' + value[1].text + ',' + value[2].text;
			},
			setfield: function(e) {
				var field = e.currentTarget.dataset.formindex;
				var value = e.detail.value;
				this.formvaldata[field] = value;
			},
			editorChooseImage: function(e) {
				var that = this;
				var index = e.currentTarget.dataset.index;
				var tplindex = e.currentTarget.dataset.tplindex;
				var editorFormdata = this.editorFormdata;
				if (!editorFormdata) editorFormdata = [];
				app.chooseImage(function(data) {
					editorFormdata[index] = data[0];
					console.log(editorFormdata)

					that.$set(that.editorFormdata, index, data[0]);
					that.editorFormdata = editorFormdata
					that.test = Math.random();

					var field = e.currentTarget.dataset.formindex;
					that.formvaldata[field] = data[0];

				})
			},
			removeimg: function(e) {
				var that = this;
				var index = e.currentTarget.dataset.index;
				var tplindex = e.currentTarget.dataset.tplindex;
				var field = e.currentTarget.dataset.formindex;
				var editorFormdata = this.editorFormdata;
				if (!editorFormdata) editorFormdata = [];
				editorFormdata[index] = '';

				that.$set(that.editorFormdata, index, '');
				that.editorFormdata = editorFormdata
				that.test = Math.random();
				that.formvaldata[field] = '';
			},
			editorBindPickerChange: function(e) {
				var index = e.currentTarget.dataset.index;
				var tplindex = e.currentTarget.dataset.tplindex;
				var val = e.detail.value;
				var editorFormdata = this.editorFormdata;
				if (!editorFormdata) editorFormdata = [];
				// editorFormdata[index] = val;
				this.$set(this.editorFormdata, index, val);
				console.log(editorFormdata)
				this.editorFormdata = editorFormdata
				this.test = Math.random();

				var field = e.currentTarget.dataset.formindex;
				this.formvaldata[field] = val;
			},
			getPhoneNumber: function(e) {
				var that = this
				var index = e.currentTarget.dataset.index;
				var field = 'form' + index;
				if (that.authphone) {
					that.test = Math.random()
					that.detail.content = that.authphone;
					that.formvaldata[field] = that.authphone;
					return true;
				}
				if (e.detail.errMsg == "getPhoneNumber:fail user deny") {
					app.error('请同意授权获取手机号');
					return;
				}
				if (!e.detail.iv || !e.detail.encryptedData) {
					app.error('请同意授权获取手机号');
					return;
				}
				wx.login({
					success(res1) {
						console.log('res1')
						console.log(res1);
						var code = res1.code;
						//用户允许授权
						app.post('ApiIndex/authphone', {
							iv: e.detail.iv,
							encryptedData: e.detail.encryptedData,
							code: code,
							pid: app.globalData.pid
						}, function(res2) {
							if (res2.status == 1) {
								that.authphone = res2.tel;
								that.test = Math.random()
								that.detail.content = that.authphone;
								that.formvaldata[field] = that.authphone;
							} else {
								app.error(res2.msg);
							}
							return;
						})
					}
				});
			},
			download: function(e) {
				var that = this;
				var file = e.currentTarget.dataset.file;
				// #ifdef H5
				window.location.href = file;
				// #endif

				// #ifdef MP-WEIXIN
				uni.downloadFile({
					url: file,
					success: (res) => {
						var filePath = res.tempFilePath;
						if (res.statusCode === 200) {
							uni.openDocument({
								filePath: filePath,
								showMenu: true,
								success: function(res) {
									console.log('打开文档成功');
								}
							});
						}
					}
				});
				// #endif
			},
			chooseFile: function(e) {
				var that = this;
				var index = e.currentTarget.dataset.index;
				var field = e.currentTarget.dataset.formindex;

				var editorFormdata = this.editorFormdata;
				if (!editorFormdata) editorFormdata = [];

				var up_url = app.globalData.baseurl + 'ApiImageupload/uploadImg/aid/' + app.globalData.aid + '/platform/' + app
					.globalData.platform + '/session_id/' + app.globalData.session_id;

				// #ifdef H5
				uni.chooseFile({
					count: 1, //默认100
					success: function(res) {
						console.log(res);
						const tempFilePaths = res.tempFiles;

						//for (var i = 0; i < tempFilePaths.length; i++) {
						app.showLoading('上传中');
						console.log(tempFilePaths[0]);
						uni.uploadFile({
							url: up_url,
							filePath: tempFilePaths[0]['path'],
							name: 'file',
							success: function(res) {
								app.showLoading(false);
								var data = JSON.parse(res.data);
								if (data.status == 1) {
									that.formvaldata[field] = data.url;

									editorFormdata[index] = data.url;

									that.$set(that.editorFormdata, index, data.url)
									that.editorFormdata = editorFormdata;
								} else {
									app.alert(data.msg);
								}
							},
							fail: function(res) {
								app.showLoading(false);
								app.alert(res.errMsg);
							}
						});
						//}
					}
				});
				// #endif
				// #ifdef MP-WEIXIN
				wx.chooseMessageFile({
					count: 1,
					type: 'file',
					success(res) {
						// tempFilePath可以作为 img 标签的 src 属性显示图片
						const tempFilePaths = res.tempFiles
						console.log(tempFilePaths);


						//for (var i = 0; i < tempFilePaths.length; i++) {
						app.showLoading('上传中');
						console.log(tempFilePaths[0]);
						uni.uploadFile({
							url: up_url,
							filePath: tempFilePaths[0]['path'],
							name: 'file',
							success: function(res) {
								app.showLoading(false);
								var data = JSON.parse(res.data);
								if (data.status == 1) {
									that.formvaldata[field] = data.url;

									editorFormdata[index] = data.url;

									that.$set(that.editorFormdata, index, data.url)
									that.editorFormdata = editorFormdata;

								} else {
									app.alert(data.msg);
								}
							},
							fail: function(res) {
								app.showLoading(false);
								app.alert(res.errMsg);
							}
						});
						//}
					},
					complete(res) {
						console.log(res)
					}
				})
				// #endif
			},

			formSubmit: function(e) {
				var that = this;
				var detail = that.detail;
				var index = that.index;
				var formvaldata = that.formvaldata;
				var mid = '';
				if(that.opt && that.opt.mid){
					mid = that.opt.mid;
				}
				if (detail.key == 'region') {
					formvaldata['form' + index] = that.regiondata;
				}
				if (detail.key != 'separate' && detail.val3 == 1 && (formvaldata['form' + index] === '' || formvaldata['form' +
						index] === null || formvaldata['form' + index] === undefined || formvaldata['form' + index].length == 0)) {
					app.alert(detail.val1 + ' 必填' + detail.val2 + detail.val4);
					return;
				}
				if (detail.key == 'switch') {
					if (formvaldata['form' + index] == false) {
						formvaldata['form' + index] = '否'
					} else {
						formvaldata['form' + index] = '是'
					}
				}
				if (detail.key == 'selector') {
					formvaldata['form' + index] = detail.val2[formvaldata['form' + index]]
				}
				if (detail.key == 'input' && detail.val4 && (formvaldata['form' + index] !== '' && formvaldata['form' +
					index])) {
					if (detail.val4 == '2') { //手机号
						if (!app.isPhone(formvaldata['form' + index])) {
							app.alert(detail.val1 + ' 格式错误');
							return;
						}
					}
					if (detail.val4 == '3') { //身份证号
						if (!/(^\d{15}$)|(^\d{18}$)|(^\d{17}(\d|X|x)$)/.test(formvaldata['form' + index])) {
							app.alert(detail.val1 + ' 格式错误');
							return;
						}
					}
					if (detail.val4 == '4') { //邮箱
						if (!/^(.+)@(.+)$/.test(formvaldata['form' + index])) {
							app.alert(detail.val1 + ' 格式错误');
							return;
						}
					}
				}
				app.showLoading('提交中');
				var url = that.from == "set" ? "ApiMy/otherset" : "ApiMy/registset";
				app.post(url, {
					mid:mid,
					index: index,
					content: formvaldata['form' + index]
				}, function(data) {
					app.showLoading(false);
					if (data.status == 1) {
						app.success(data.msg);
						setTimeout(function() {
							app.goback(true);
						}, 1000);
					} else {
						app.error(data.msg);
					}
				});
			},
		}
	}
</script>
<style>
	.dp-form {
		height: auto;
		position: relative;
		width: 100%;
	}

	.dp-form .radio {
		transform: scale(.7);
	}

	.dp-form .checkbox {
		transform: scale(.7);
	}

	.dp-form-item {
		width: 100%;
		padding: 10rpx 0px;
		display: flex;
		align-items: center;
	}

	.dp-form-item:last-child {
		border: 0
	}

	.dp-form-item .label {
		line-height: 70rpx;
		width: 140rpx;
		margin-right: 10px;
		flex-shrink: 0
	}

	.dp-form-item .input {
		height: 70rpx;
		line-height: 70rpx;
		overflow: hidden;
		flex: 1;
		border: 1px solid #eee;
		padding: 0 8rpx;
		border-radius: 2px;
		background: #fff;
		flex: 1;
	}

	.dp-form-item .textarea {
		height: 180rpx;
		line-height: 40rpx;
		overflow: hidden;
		flex: 1;
		border: 1px solid #eee;
		border-radius: 2px;
		padding: 8rpx
	}

	.dp-form-item .radio {
		height: 70rpx;
		line-height: 70rpx;
		display: flex;
		align-items: center
	}

	.dp-form-item .radio2 {
		display: flex;
		align-items: center;
	}

	.dp-form-item .radio .myradio {
		margin-right: 10rpx;
		display: inline-block;
		border: 1px solid #aaa;
		background: #fff;
		height: 32rpx;
		width: 32rpx;
		border-radius: 50%
	}

	.dp-form-item .checkbox {
		height: 70rpx;
		line-height: 70rpx;
		display: flex;
		align-items: center
	}

	.dp-form-item .checkbox2 {
		display: flex;
		align-items: center;
		height: 40rpx;
		line-height: 40rpx;
	}

	.dp-form-item .checkbox .mycheckbox {
		margin-right: 10rpx;
		display: inline-block;
		border: 1px solid #aaa;
		background: #fff;
		height: 32rpx;
		width: 32rpx;
		border-radius: 2px
	}

	.dp-form-item .layui-form-switch {}

	.dp-form-item .picker {
		line-height: 70rpx;
		flex: 1;
	}

	.dp-form-item2 {
		width: 100%;
		border-bottom: 1px #ededed solid;
		padding: 10rpx 0px;
		display: flex;
		flex-direction: column;
		align-items: flex-start;
	}

	.dp-form-item2:last-child {
		border: 0
	}

	.dp-form-item2 .label {
		height: 70rpx;
		line-height: 70rpx;
		width: 100%;
		margin-right: 10px;
	}

	.dp-form-item2 .value {
		display: flex;
		justify-content: flex-start;
		width: 100%;
		flex: 1;
	}

	.dp-form-item2 .input {
		height: 70rpx;
		line-height: 70rpx;
		overflow: hidden;
		width: 100%;
		border: 1px solid #eee;
		padding: 0 8rpx;
		border-radius: 2px;
		background: #fff;
		flex: 1;
	}

	.dp-form-item2 .textarea {
		height: 180rpx;
		line-height: 40rpx;
		overflow: hidden;
		width: 100%;
		border: 1px solid #eee;
		border-radius: 2px;
		padding: 8rpx
	}

	.dp-form-item2 .radio {
		height: 70rpx;
		line-height: 70rpx;
		display: flex;
		align-items: center;
	}

	.dp-form-item2 .radio2 {
		display: flex;
		align-items: center;
	}

	.dp-form-item2 .radio .myradio {
		margin-right: 10rpx;
		display: inline-block;
		border: 1px solid #aaa;
		background: #fff;
		height: 32rpx;
		width: 32rpx;
		border-radius: 50%
	}

	.dp-form-item2 .checkbox {
		height: 70rpx;
		line-height: 70rpx;
		display: flex;
		align-items: center;
	}

	.dp-form-item2 .checkbox2 {
		display: flex;
		align-items: center;
		height: 40rpx;
		line-height: 40rpx;
	}

	.dp-form-item2 .checkbox .mycheckbox {
		margin-right: 10rpx;
		display: inline-block;
		border: 1px solid #aaa;
		background: #fff;
		height: 32rpx;
		width: 32rpx;
		border-radius: 2px
	}

	.dp-form-item2 .layui-form-switch {}

	.dp-form-item2 .picker {
		height: 70rpx;
		line-height: 70rpx;
		flex: 1;
		width: 100%;
	}

	.dp-form-btn {
		margin: 50rpx auto;
		background: #1684FC;
		color: #fff;
		text-align: center;
		line-height: 100rpx;
		border-radius: 8rpx;
	}

	.flex-y-center {
		margin-right: 20rpx;
	}

	.dp-form-imgbox {
		margin-right: 16rpx;
		margin-bottom: 10rpx;
		font-size: 24rpx;
		position: relative;
	}

	.dp-form-imgbox-close {
		position: absolute;
		display: block;
		width: 32rpx;
		height: 32rpx;
		right: -16rpx;
		top: -16rpx;
		color: #999;
		font-size: 32rpx;
		background: #fff;
		z-index: 9;
		border-radius: 50%
	}

	.dp-form-imgbox-close .image {
		width: 100%;
		height: 100%
	}

	.dp-form-imgbox-img {
		display: block;
		width: 200rpx;
		height: 200rpx;
		padding: 2px;
		border: #d3d3d3 1px solid;
		background-color: #f6f6f6;
		overflow: hidden
	}

	.dp-form-imgbox-img>.image {
		max-width: 100%;
	}

	.dp-form-imgbox-repeat {
		position: absolute;
		display: block;
		width: 32rpx;
		height: 32rpx;
		line-height: 28rpx;
		right: 2px;
		bottom: 2px;
		color: #999;
		font-size: 30rpx;
		background: #fff
	}

	.dp-form-uploadbtn {
		position: relative;
		height: 200rpx;
		width: 200rpx
	}

	.dp-form-separate {
		width: 100%;
		padding: 20rpx;
		text-align: center;
		padding: 20rpx;
		font-size: 36rpx;
		font-weight: 500;
		color: #454545;
	}

	.authtel {
		border-radius: 10rpx;
		line-height: 68rpx;
		margin-left: 10rpx;
		padding: 0 10rpx;
	}

	.input.disabled {
		background: #EFEFEF;
	}

	.item {
		background-color: #fff;
		padding: 30rpx;
		overflow: hidden;
		border-radius: 12rpx;
	}

	.item_name {
		width: 320rpx;
		float: left;
		line-height: 40rpx;
	}

	.item_content {
		width: 320rpx;
		float: left;
		line-height: 40rpx;
		text-align: right;
	}

	.set-btn {
		width: 90%;
		margin: 60rpx 5%;
		height: 96rpx;
		line-height: 96rpx;
		border-radius: 48rpx;
		color: #FFFFFF;
		font-weight: bold;
	}
</style>