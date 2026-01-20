<template>
	<view>
		<block v-if="isload">
			<view class="banner" v-if="set && set.pic"><image :src="set.pic" mode="widthFix"></image></view>
			<view class="content" v-if="set && set.shuoming">
				<parse :content="set.shuoming" />
			</view>
			<form @submit="subform">
				<view class="form-box">
					<view class="title">
						<view class="line">
							<text>上传发票和照片</text>
						</view>
						<!-- <view class="socre-tips">您的{{t('积分')}}最多可抵用：{{userinfo.discount_money}}元</view> -->
					</view>
					<view class="form-field">
						<view class="form-field-title">上传发票截图<text style="color:red"> *</text></view>
					</view>
					<view class="flex" style="flex-wrap:wrap;padding-bottom:20rpx;">
						<view v-for="(item, index) in invoicepic" :key="index" class="layui-imgbox">
							<view class="layui-imgbox-close" @tap="removeimg" :data-index="index" data-field="invoicepic">
								<image :src="pre_url+'/static/img/ico-del.png'"></image>
							</view>
							<view class="layui-imgbox-img">
								<image :src="item" @tap="previewImage" :data-url="item" mode="widthFix"></image>
							</view>
						</view>
						<view class="uploadbtn" :style="'background:url('+pre_url+'/static/img/shaitu_icon.png) no-repeat 60rpx;background-size:80rpx 80rpx;background-color:#F3F3F3;'" @tap="uploadimg" data-field="invoicepic"></view>
					</view>
					<input type="text" hidden="true" name="invoicepic" :value="invoicepic.join(',')" maxlength="-1"></input>
					
					<view class="form-field">
						<view class="form-field-title">上传消费照片<text style="color:red"> *</text></view>
						<view class="upload-tips" v-if="set && set.upload_tips">提示：{{set.upload_tips}}</view>
					</view>
					<view class="flex" style="flex-wrap:wrap;padding-bottom:20rpx;">
						<view v-for="(item, index) in consumepic" :key="index" class="layui-imgbox">
							<view class="layui-imgbox-close" @tap="removeimg" :data-index="index" data-field="consumepic">
								<image :src="pre_url+'/static/img/ico-del.png'"></image>
							</view>
							<view class="layui-imgbox-img">
								<image :src="item" @tap="previewImage" :data-url="item" mode="widthFix"></image>
							</view>
						</view>
						<view class="uploadbtn" :style="'background:url('+pre_url+'/static/img/shaitu_icon.png) no-repeat 60rpx;background-size:80rpx 80rpx;background-color:#F3F3F3;'" @tap="uploadimg" data-field="consumepic"></view>
					</view>
					<input type="text" hidden="true" name="consumepic" :value="consumepic.join(',')" maxlength="-1"></input>
				</view>
			
				<view class="withdrawtype">
					<view class="f1">选择收款方式：</view>
					<view class="f2">
						<!-- <view class="item" v-if="sysset.withdraw_weixin==1" @tap.stop="changeradio" data-paytype="微信钱包">
								<view class="t1"><image class="img" :src="pre_url+'/static/img/withdraw-weixin.png'"/>微信钱包</view>
								<view class="radio" :style="paytype=='微信钱包' ? 'background:'+t('color1')+';border:0' :''"><image class="radio-img" :src="pre_url+'/static/img/checkd.png'"/></view>
						</view> -->
						<label class="item" v-if="sysset.withdraw_aliaccount==1" @tap.stop="changeradio" data-paytype="支付宝">
								<view class="t1"><image class="img" :src="pre_url+'/static/img/withdraw-alipay.png'"/>支付宝</view>
								<view class="radio" :style="paytype=='支付宝' ? 'background:'+t('color1')+';border:0' : ''"><image class="radio-img" :src="pre_url+'/static/img/checkd.png'"/></view>
						</label>
						<label class="item" v-if="sysset.withdraw_bankcard==1" @tap.stop="changeradio" data-paytype="银行卡">
								<view class="t1"><image class="img" :src="pre_url+'/static/img/withdraw-cash.png'"/>银行卡</view>
								<view class="radio" :style="paytype=='银行卡' ? 'background:'+t('color1')+';border:0' : ''"><image class="radio-img" :src="pre_url+'/static/img/checkd.png'"/></view>
						</label>
				    <label class="item" v-if="sysset.withdraw_paycode==1" @tap.stop="changeradio" data-paytype="收款码">
				    		<view class="t1"><image class="img" :src="pre_url+'/static/img/withdraw-cash.png'"/>收款码</view>
				    		<view class="radio" :style="paytype=='收款码' ? 'background:'+t('color1')+';border:0' : ''"><image class="radio-img" :src="pre_url+'/static/img/checkd.png'"/></view>
				    </label>
					</view>
					<view class="banklist" v-if="selectbank && paytype=='银行卡' && bank">
						  <view class="f1">默认银行卡：{{bank.bankname}} {{bank.bankcardnum}}</view>
							<view class="t2" @tap="goto" data-url="/pagesA/banklist/bank?fromPage=commission">修改</view>
					</view>
				</view>
								
				<view v-if="paytype=='支付宝'" class="textbtn" @tap="goto" data-url="/pagesExt/my/setaliaccount">设置支付宝账户<image :src="pre_url+'/static/img/arrowright.png'" /></view>
				<view v-if="paytype=='银行卡' && !sysset.withdraw_huifu" class="textbtn" @tap="goto" data-url="/pagesExt/my/setbankinfo">设置银行卡账户<image :src="pre_url+'/static/img/arrowright.png'" /></view>
				<view v-if="paytype=='收款码'" class="textbtn" @tap="goto" data-url="/pagesB/my/setpaycode">设置收款码<image :src="pre_url+'/static/img/arrowright.png'" /></view>
				<button class="btn" :style="{background:t('color1')}" @tap="formSubmit">提交</button>
				<view style="width:100%;height:100rpx"></view>
			</form>
		</block>

		<loading v-if="loading"></loading>
		<dp-tabbar :opt="opt"></dp-tabbar>
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
				pre_url:app.globalData.pre_url,
				set:'',
				sysset:'',
				paytype:'',
				userinfo:[],
				invoicepic:[],
				consumepic:[]
			};
		},

		onLoad: function(opt) {
			this.getdata();
			this.opt = app.getopts(opt);
		},
		onPullDownRefresh: function() {
			this.getdata();
		},
		methods: {
			getdata: function() {
				var that = this;
				that.loading = true;
				app.get('ApiInvoiceBaoxiao/index', {}, function(res) {
					var sysset = res.sysset;
					that.set = res.set;
					that.sysset = sysset;
					that.userinfo = res.userinfo;
					that.selectbank = res.selectbank;
					that.bank = res.bank;
					that.loading = false;
					that.loaded();
				});
			},
			changeradio: function (e) {
			  var that = this;
			  var paytype = e.currentTarget.dataset.paytype;
			  that.paytype = paytype;
			},
			uploadimg:function(e){
				var that = this;
				var ext = [];
				var field= e.currentTarget.dataset.field
				var pics = that[field]
				if(!pics) pics = [];
				if(field == 'invoicepic'){
					ext =  ['png','jpg','jpeg','jpe','bmp','gif','tiff','tif','webp'];  //允许的图片格式
				}
				uni.chooseImage({
					count:1,
					sizeType: ['original', 'compressed'],
					sourceType: ['album', 'camera'],
					success: function(res) {
						var tempFilePaths = res.tempFilePaths,
							imageUrls = [];
						var uploadednum = 0;
						for (var i = 0; i < tempFilePaths.length; i++) {
							imageUrls.push('');
							app.showLoading('上传中');
							uni.uploadFile({
								url: app.globalData.baseurl + 'ApiImageupload/uploadImg/aid/' + app
									.globalData.aid + '/platform/' + app.globalData.platform +
									'/session_id/' +
									app.globalData.session_id+'/sortnum/'+i,
								filePath: tempFilePaths[i],
								name: 'file',
								success: function(res) {
									app.showLoading(false);
									if(typeof res.data == 'string'){
										//兼容微信小程序
										var data = JSON.parse(res.data);
									}else{
										//兼容百度小程序
										var data = res.data;
									}
									if (data.status == 1) {
										if(ext.length > 0){
											var extension = data.url.split('.').pop();
											if (extension && !ext.includes(extension)) {
												console.log(extension);
												uni.showToast({ title: '图片格式不支持上传', icon: 'none' });
												return;
											}
										}
										that[field].push(data.url);
									} else {
										app.alert(data.msg);
									}
								},
								fail: function(res) {
									app.showLoading(false);
									app.alert(res.errMsg);
								}
							});
						}
					},
					fail: function(res) { //alert(res.errMsg);
					}
				});
			},
			removeimg:function(e){
				var that = this;
				var index= e.currentTarget.dataset.index
				var field= e.currentTarget.dataset.field
				var pics = that[field]
				pics.splice(index,1)
			},
			formSubmit: function () {
			  var that = this;
			  var paytype = this.paytype;
				var consumepic = that.consumepic;
				var invoicepic = that.invoicepic;
				if(invoicepic.length <= 0){
					return app.alert('请上传发票截图');
				}
				
				if(consumepic.length <= 0 ){
					return app.alert('请上传消费照片');
				}
			
			  if (paytype == '支付宝' && !this.userinfo.aliaccount) {
			    app.alert('请先设置支付宝账号', function () {
			      app.goto('/pagesExt/my/setaliaccount');
			    });
			    return;
			  }
				if(this.selectbank){
						if (paytype == '银行卡' && !this.bank){
							app.alert('请先设置完整银行卡信息', function () {
							  app.goto('/pagesA/banklist/bankadd');
							});
						}
				}else{
					if (paytype == '银行卡' && (!this.userinfo.bankname || !this.userinfo.bankcarduser || !this.userinfo.bankcardnum)) {
					  app.alert('请先设置完整银行卡信息', function () {
					    app.goto('/pagesExt/my/setbankinfo');
					  });
					  return;
					}
				}
				app.showLoading('提交中');
				app.post('ApiInvoiceBaoxiao/submit', {consumepic: consumepic,invoicepic:invoicepic,paytype: paytype}, function (rs) {
					app.showLoading(false);
					if (rs.status == 0) {
						app.error(rs.msg);
						return;
					} else {
						app.success(rs.msg);
						setTimeout(function () {
							app.goto('recordlist','reLaunch')
						}, 500);
					}
				});
			}
		}
	}
</script>
<style>
	
	.banner{width:100%;}
	.banner image{width:100%;height:auto}
	.content{min-height:200rpx; margin: 0 24rpx; padding: 2rpx 24rpx 0 24rpx; border-radius: 10rpx; background: #fff;}
	.form-box { padding: 2rpx 24rpx 0 24rpx; background: #fff; margin: 0 24rpx; border-radius: 10rpx}
	.form-box .title{text-align: center;line-height: 60rpx;}
	.form-box .title .socre-tips{color: #1d9b24;font-weight: bold;}
	.line::before,.line::after{content: "";display: inline-block;width: 28%;margin: 5px 1%;text-align: center;border-bottom: 1px solid #ccc;}
	.form-box .title text{padding:0 30rpx}
	.form-field {margin: 40rpx 0;}
	.form-field .form-field-title{font-weight: bold;}
	.form-field .upload-tips{margin-top: 20rpx;color: #1d9b24;font-size: 24rpx;}
	.uploadbtn {position: relative;height: 200rpx;width: 200rpx}

	.layui-imgbox {
		margin-right: 16rpx;
		margin-bottom: 10rpx;
		font-size: 24rpx;
		position: relative;
	}

	.layui-imgbox-img {
		display: block;
		width: 200rpx;
		height: 200rpx;
		padding: 2px;
		border: #d3d3d3 1px solid;
		background-color: #f6f6f6;
		overflow: hidden
	}

	.layui-imgbox-img>image {
		max-width: 100%;
	}
	
	.uploadbtn {
		position: relative;
		height: 200rpx;
		width: 200rpx
	}
	
	.withdrawtype{width:94%;margin:20rpx 3%;border-radius:10rpx;display:flex;flex-direction:column;margin-top:20rpx;background:#fff}
	.withdrawtype .f1{height:100rpx;line-height:100rpx;padding:0 30rpx;color:#333333;font-weight:bold}
	
	
	.withdrawtype .f2{padding:0 30rpx}
	.withdrawtype .f2 .item{border-bottom:1px solid #f5f5f5;height:100rpx;display:flex;align-items:center}
	.withdrawtype .f2 .item:last-child{border-bottom:0}
	.withdrawtype .f2 .item .t1{flex:1;display:flex;align-items:center;color:#333}
	.withdrawtype .f2 .item .t1 .img{width:44rpx;height:44rpx;margin-right:40rpx}
	
	.withdrawtype .f2 .item .radio{flex-shrink:0;width: 36rpx;height: 36rpx;background: #FFFFFF;border: 3rpx solid #BFBFBF;border-radius: 50%;margin-right:10rpx}
	.withdrawtype .f2 .item .radio .radio-img{width:100%;height:100%}
	
	.btn{ height:100rpx;line-height: 100rpx;width:90%;margin:0 auto;border-radius:50rpx;margin-top:30rpx;color: #fff;font-size: 30rpx;font-weight:bold}
	.textbtn {width:100%;margin-top:40rpx;text-align:center;color:#999;display:flex;align-items:center;justify-content:center}
	.textbtn image {width:30rpx;height:30rpx}
	
	.tips-box{width:94%;margin:0 3%;padding: 20rpx 0;border-top:1px solid #F0F0F0;}
	.tips{color:#8C8C8C;font-size:28rpx;line-height: 50rpx;}
	
	.banklist{ padding:0 20rpx 20rpx;margin-left: 10rpx;display: flex; width: 100%; }
	.banklist .t2{ line-height: 90rpx;width: 80rpx;text-align: right;}
</style>