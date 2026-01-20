<template>
<view class="container">
	<block v-if="isload">
		<view class="recorddetail">
			
			<view class="item-pics">
				<view class="t1">已上传发票截图</view>
				<view class="flex" style="flex-wrap:wrap;padding-bottom:20rpx;">
					<view v-for="(item, index) in invoicepic" :key="index" class="layui-imgbox">
						<view class="layui-imgbox-close" @tap="removeimg" :data-index="index" data-field="invoicepic" v-if="edit && (detail.status == 0 || detail.status == 2)">
							<image :src="pre_url+'/static/img/ico-del.png'"></image>
						</view>
						<view class="layui-imgbox-img">
							<image :src="item" @tap="previewImage" :data-url="item" mode="widthFix"></image>
						</view>
					</view>
					<view class="uploadbtn" v-if="edit && (detail.status == 0 || detail.status == 2)" :style="'background:url('+pre_url+'/static/img/shaitu_icon.png) no-repeat 60rpx;background-size:80rpx 80rpx;background-color:#F3F3F3;'" @tap="uploadimg" data-field="invoicepic"></view>
				</view>
				<input type="text" hidden="true" name="invoicepic" :value="invoicepic.join(',')" maxlength="-1"></input>
			</view>
			<view class="item-pics">
				<view class="t1">已上传现场消费照片</view>
				<view class="flex" style="flex-wrap:wrap;padding-bottom:20rpx;">
					<view v-for="(item, index) in consumepic" :key="index" class="layui-imgbox">
						<view class="layui-imgbox-close" @tap="removeimg" :data-index="index" data-field="consumepic" v-if="edit && (detail.status == 0 || detail.status == 2)">
							<image :src="pre_url+'/static/img/ico-del.png'"></image>
						</view>
						<view class="layui-imgbox-img">
							<image :src="item" @tap="previewImage" :data-url="item" mode="widthFix"></image>
						</view>
					</view>
					<view class="uploadbtn" v-if="edit && (detail.status == 0 || detail.status == 2)" :style="'background:url('+pre_url+'/static/img/shaitu_icon.png) no-repeat 60rpx;background-size:80rpx 80rpx;background-color:#F3F3F3;'" @tap="uploadimg" data-field="consumepic"></view>
				</view>
				<input type="text" hidden="true" name="consumepic" :value="consumepic.join(',')" maxlength="-1"></input>
			</view>
			<view class="item">
				<text class="t1">提交时间：</text>
				<text class="t2">{{detail.createtime}}</text>
			</view>
			<view class="item" v-if="detail.status > 0">
				<text class="t1">审核时间：</text>
				<text class="t2">{{detail.operatetime}}</text>
			</view>
			<view class="item">
				<text class="t1">当前状态：</text>
				<text class="t2 red" v-if="detail.status == 0">待审核</text>
				<text class="t2 green" v-if="detail.status == 1 && detail.payment_status == 1">已通过</text>
				<text class="t2 yellow" v-if="detail.status == 2">已驳回</text>
				<text class="t2 green"  v-if="detail.status == 1 && detail.payment_status == 2">已打款</text>
				<text class="t2"  v-if="detail.status == 3 && detail.payment_status == 3">已关闭</text>
			</view>
			<view class="item">
				<text class="t1">记录编号：</text>
				<text class="t2">{{detail.id}}</text>
			</view>
			<view class="item">
				<text class="t1">扣除{{t('积分')}}：</text>
				<text class="t2">{{detail.deduct_score}}</text>
			</view>
			<view class="item">
				<text class="t1">打款金额：</text>
				<text class="t2">{{detail.money}}</text>
			</view>
			<view class="item" v-if="detail.status==2">
				<text class="t1">驳回原因：</text>
				<text class="t2" style="color:red">{{detail.reason}}</text>
			</view>
			<button class="btn" @tap="formSubmit" :style="{background:t('color1')}" v-if="edit && (detail.status == 0 || detail.status == 2)" >提交</button>
		</view>
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
			opt:{},
			loading:false,
      isload: false,
			pre_url:app.globalData.pre_url,
			menuindex:-1,
			detail:{},
			edit:0,
			invoicepic:[],
			consumepic:[]
    };
  },
  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.getdata();
  },
	onPullDownRefresh: function () {
		this.getdata();
	},
  methods: {
		getdata: function () {
			var that = this;
			that.loading = true;
			app.get('ApiInvoiceBaoxiao/detail', {id: that.opt.id}, function (res) {
				that.loading = false;
				that.detail = res.data;
				that.invoicepic = that.detail.invoice_pics;
				that.consumepic = that.detail.consume_pics;
				if(res.status == 0){
					app.error(res.msg);
					setTimeout(function () {
						app.goto('recordlist');
					}, 3000);
					return;
				}
				that.loaded();
			});
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
			var consumepic = that.consumepic;
			var invoicepic = that.invoicepic;
			
			if(invoicepic.length <= 0){
				return app.alert('请上传发票截图');
			}
			
			if(consumepic.length <= 0 ){
				return app.alert('请上传消费照片');
			}
			if(that.edit == 0){
				return app.alert('禁止编辑');
			}
			app.showLoading('提交中');
			app.post('ApiInvoiceBaoxiao/submit', {id:that.detail.id,consumepic: consumepic,invoicepic:invoicepic}, function (rs) {
				app.showLoading(false);
				if (rs.status == 0) {
					app.error(rs.msg);
					return;
				} else {
					app.success();
					setTimeout(function () {
						app.goto('recordlist')
					}, 500);
				}
			});
		}
  }
};
</script>
<style>
.container{padding-top:10rpx}
.recorddetail{ width:100%;padding: 14rpx 3%;background: #FFF;}
.recorddetail .item{display:flex;width:100%;padding:20rpx 0;border-bottom:1px dashed #ededed;font-size: 26rpx;}
.recorddetail .item:last-child{ border-bottom: 0;}
.recorddetail .item .t1{width:200rpx;}
.recorddetail .item .t2{flex:1;text-align:right}

.bottom{ width: 100%; height:92rpx;padding: 0 20rpx;background: #fff; position: fixed; bottom: 0px;left: 0px;display:flex;justify-content:flex-end;align-items:center;}

.btn1{margin-left:20rpx;min-width:160rpx;padding: 0 20rpx;height:60rpx;line-height:60rpx;color:#fff;border-radius:3px;text-align:center;}
.btn2{margin-left:20rpx;width:160rpx;height:60rpx;line-height:60rpx;color:#333;background:#fff;border:1px solid #cdcdcd;border-radius:3px;text-align:center;}
.btn3{position:absolute;top:60rpx;right:10rpx;font-size:24rpx;width:120rpx;height:50rpx;line-height:50rpx;color:#333;background:#fff;border:1px solid #cdcdcd;border-radius:3px;text-align:center}

.red{color: red;}
.yellow{color:#ffc107;}
.green{color: green;}

.item-pics{width: 100%;border-bottom: 1px dashed #ededed;}
.item-pics .t1{padding: 20rpx 0;}
.layui-imgbox {margin-right: 30rpx;margin-bottom: 10rpx;font-size: 24rpx;position: relative;}
.layui-imgbox-img {display: block;width: 200rpx;height: 200rpx;padding: 2px;	border: #d3d3d3 1px solid;background-color: #f6f6f6;overflow: hidden}
.layui-imgbox-img>image {max-width: 100%;}
.uploadbtn {position: relative;height: 200rpx;width: 200rpx}
.btn{ height:100rpx;line-height: 100rpx;width:90%;margin:0 auto;border-radius:50rpx;margin-top:30rpx;color: #fff;font-size: 30rpx;font-weight:bold}
</style>