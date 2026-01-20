<template>
<view class="container">
	<block v-if="isload">
		<form @submit="formSubmit" @reset="formReset" report-submit="true">
			<view class="form-content">
				<view class="form-item flex-bt">
					<view class="label">施工前<text style="color:red">*</text></view>
					<view class="flex content_picpreview" style="flex-wrap:wrap;padding-top:20rpx">
						<view v-for="(item, index) in service_before_pics" :key="index" class="layui-imgbox">
							<view class="layui-imgbox-close" @tap="removeimg" :data-index="index" data-field="service_before_pics">
								<image :src="pre_url+'/static/img/ico-del.png'"></image>
							</view>
							<view class="layui-imgbox-img">
								<image :src="item" @tap="previewImage" :data-url="item" mode="widthFix"></image>
							</view>
						</view>
						<view class="uploadbtn" :style="'background:url('+pre_url+'/static/img/shaitu_icon.png) no-repeat 60rpx;background-size:80rpx 80rpx;background-color:#F3F3F3;'" @tap="uploadimg" data-field="service_before_pics"></view>
					</view>
				</view>
				<view class="form-item flex-bt">
					<view class="label">施工中<text style="color:red">*</text></view>
					<view class="flex content_picpreview" style="flex-wrap:wrap;padding-top:20rpx">
						<view v-for="(item, index) in service_pics" :key="index" class="layui-imgbox">
							<view class="layui-imgbox-close" @tap="removeimg" :data-index="index" data-field="service_pics">
								<image :src="pre_url+'/static/img/ico-del.png'"></image>
							</view>
							<view class="layui-imgbox-img">
								<image :src="item" @tap="previewImage" :data-url="item" mode="widthFix"></image>
							</view>
						</view>
						<view class="uploadbtn" :style="'background:url('+pre_url+'/static/img/shaitu_icon.png) no-repeat 60rpx;background-size:80rpx 80rpx;background-color:#F3F3F3;'" @tap="uploadimg" data-field="service_pics"></view>
					</view>
				</view>
				<view class="form-item flex-bt">
					<view class="label">完成施工<text style="color:red">*</text></view>
					<view class="flex content_picpreview" style="flex-wrap:wrap;padding-top:20rpx">
						<view v-for="(item, index) in service_finish_pics" :key="index" class="layui-imgbox">
							<view class="layui-imgbox-close"  @tap="removeimg" :data-index="index" data-field="service_finish_pics">
								<image :src="pre_url+'/static/img/ico-del.png'"></image>
							</view>
							<view class="layui-imgbox-img">
								<image :src="item" @tap="previewImage" :data-url="item" mode="widthFix"></image>
							</view>
						</view>
						<view class="uploadbtn" :style="'background:url('+pre_url+'/static/img/shaitu_icon.png) no-repeat 60rpx;background-size:80rpx 80rpx;background-color:#F3F3F3;'" @tap="uploadimg" data-field="service_finish_pics"></view>
					</view>
				</view>
			</view>
			<button class="btn"  form-type="submit" :style="{background:t('color1')}" >提交</button>
			<view style="padding-top:30rpx"></view>
			<view style="display: none;">{{txt}}</view>
		</form>
	</block>
	<loading v-if="loading"></loading>
	<dp-tabbar :opt="opt"></dp-tabbar>
	<popmsg ref="popmsg"></popmsg>
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
				service_before_pics:[],
				service_pics:[],
				service_finish_pics:[],
				detail:{},
				txt:''
			}
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
			that.loaded();
			app.post('ApiYuyueWorker/getWrkerUploadPics', {id:that.opt.id}, function (res) {
				that.loading = false;
				if(res.status == 1){
					let data = res.data;
					that.service_before_pics = data.service_before_pics;
					that.service_pics = data.service_pics;
					that.service_finish_pics = data.service_finish_pics;
				}
			})
		},
		formSubmit: function (e) {
			var that = this;
			var id = that.opt.id;
			app.showLoading('提交中');
			if(that.service_before_pics.length<1 && that.service_pics.length<1 && that.service_finish_pics.length<1){
				app.error('请上传图片');
				return;
			}
			app.post('ApiYuyueWorker/workerUploadPics', {
				id: id,
				service_before_pics:that.service_before_pics,
				service_pics:that.service_pics,
				service_finish_pics:that.service_finish_pics,
			}, function (res) {
				app.showLoading(false);
				app.alert(res.msg);
				if (res.status == 1) {
					setTimeout(function () {
						app.goback(true);
					}, 1000);
				}
			});
		},
		uploadimg:function(e){
			var that = this;
			var field= e.currentTarget.dataset.field
			var pics = that[field]
			if(!pics) pics = [];
			app.chooseImage(function(urls){
				for(var i=0;i<urls.length;i++){
					pics.push(urls[i]);
				}
				that[field] = pics;
				that.txt = Math.random()
			},1)
		},
		removeimg:function(e){
			var that = this;
			var index= e.currentTarget.dataset.index
			var field= e.currentTarget.dataset.field
			var pics = that[field]
			pics.splice(index,1)
			that[field] = pics;
			that.txt = Math.random()
		},
		}
	}
</script>

<style>
.btn-a { text-align: center; padding: 30rpx; color: rgb(253, 74, 70);}
.text-min { font-size: 24rpx; color: #999;}
.orderinfo{width:96%;margin:0 2%;border-radius:8rpx;margin-top:16rpx;padding: 14rpx 3%;background: #FFF;}
.orderinfo .item{display:flex;width:100%;padding:20rpx 0;border-bottom:1px dashed #ededed;overflow:hidden}
.orderinfo .item:last-child{ border-bottom: 0;}
.orderinfo .item .t1{width:200rpx;flex-shrink:0}
.orderinfo .item .t2{flex:1;text-align:right}
.orderinfo .item .red{color:red}
.orderinfo .item .grey{color:grey}

.form-item4{width:100%;background: #fff; padding: 20rpx 20rpx;margin-top:1px}
.form-item4 .label{ width:150rpx;}
.layui-imgbox{margin-right:16rpx;margin-bottom:10rpx;font-size:24rpx;position: relative;}
.layui-imgbox-img{display: block;width:200rpx;height:200rpx;padding:2px;border: #d3d3d3 1px solid;background-color: #f6f6f6;overflow:hidden}
.layui-imgbox-img>image{max-width:100%;}
.layui-imgbox-repeat{position: absolute;display: block;width:32rpx;height:32rpx;line-height:28rpx;right: 2px;bottom:2px;color:#999;font-size:30rpx;background:#fff}
.uploadbtn{position:relative;height:200rpx;width:200rpx}
	

.form-content{width:94%;margin:16rpx 3%;border-radius:10rpx;display:flex;flex-direction:column;background:#fff;overflow:hidden}
.form-item{ padding: 32rpx 20rpx;border-bottom: 1px solid #e7e2e2;align-items: center;}
.form-item .label{ height:60rpx;line-height:60rpx}
.form-item .input-item{ width:100%;}
.form-item textarea{ width:100%;height:200rpx;border: 1px #eee solid;padding: 20rpx;}
.form-item input{ width:100%;border: 1px #f5f5f5 solid;padding: 10rpx;height:80rpx}
.form-item .mid{ height:80rpx;line-height:80rpx;padding:0 20rpx;}
.btn{ height:100rpx;line-height: 100rpx;width:90%;margin:0 auto;border-radius:50rpx;margin-top:50rpx;color: #fff;font-size: 30rpx;font-weight:bold}
.content_picpreview{max-width: 450rpx;}
</style>
