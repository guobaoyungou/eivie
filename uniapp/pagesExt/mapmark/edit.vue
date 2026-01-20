<template>
<view :style="'background:'+bgcolor">
	<block v-if="isload">
		<form @submit="subform">
			<view class="form-box" :style="'background:'+bgcolor">
				<view class="form-item">
					<view class="f1"><text style="color:red"> *</text>标注名称</view>
					<view class="f2"><input type="text" name="name" placeholder="请填写标注名称" placeholder-style="color:#888"></input></view>
				</view>
				<view class="form-item">
					<view class="f1"><text style="color:red"> *</text>经营类型</view>
					<view class="f2"><input type="text" name="shop_type" placeholder="请填写经营类型" placeholder-style="color:#888"></input></view>
				</view>
				<view class="form-item">
					<view class="f1"><text style="color:red"> *</text>营业电话</view>
					<view class="f2"><input type="text" name="shop_tel" placeholder="请填写营业电话" placeholder-style="color:#888"></input></view>
				</view>
				<view class="form-item">
					<view class="f1"><text style="color:red"> *</text>营业时间</view>
					<view class="f2"><input type="text" name="shop_time" placeholder="请填写营业时间" placeholder-style="color:#888"></input></view>
				</view>
				<view class="form-item">
					<view class="f1"><text style="color:red"> *</text>详细经营地址</view>
					<view class="f2"><input type="text" name="address" placeholder="请填写详细经营地址" placeholder-style="color:#888"></input></view>
				</view>
				<view class="form-item">
					<view class="f1"><text style="color:red"> *</text>联系电话</view>
					<view class="f2"><input type="text" name="mobile" placeholder="请填写联系电话" placeholder-style="color:#888"></input></view>
				</view>
			</view>
			<view class="form-box" :style="'background:'+bgcolor">
				<view class="form-item flex-col">
					<view class="f1"><text style="color:red"> *</text>上传营业执照</view>
					<view class="f2" style="flex-wrap:wrap">
						<view v-for="(item, index) in license_img" :key="index" class="layui-imgbox">
							<view class="layui-imgbox-close" @tap="removeimg" :data-index="index" data-field="license_img"><image style="display:block" :src="pre_url+'/static/img/ico-del.png'"></image></view>
							<view class="layui-imgbox-img"><image :src="item" @tap="previewImage" :data-url="item" mode="widthFix"></image></view>
						</view>
						<view class="uploadbtn" :style="'background:url('+pre_url+'/static/img/shaitu_icon.png) no-repeat 60rpx;background-size:80rpx 80rpx;background-color:#F3F3F3;'" @tap="uploadimg" data-field="license_img" data-pernum="9" v-if="license_img.length<5"></view>
					</view>
					<input type="text" hidden="true" name="license_img" :value="license_img.join(',')" maxlength="-1"></input>
				</view>
				<view class="form-item flex-col">
					<view class="f1"><text style="color:red"> *</text>门面照片</view>
					<view class="f2" style="flex-wrap:wrap">
						<view v-for="(item, index) in shop_img" :key="index" class="layui-imgbox">
							<view class="layui-imgbox-close" @tap="removeimg" :data-index="index" data-field="shop_img"><image style="display:block" :src="pre_url+'/static/img/ico-del.png'"></image></view>
							<view class="layui-imgbox-img"><image :src="item" @tap="previewImage" :data-url="item" mode="widthFix"></image></view>
						</view>
						<view class="uploadbtn" :style="'background:url('+pre_url+'/static/img/shaitu_icon.png) no-repeat 60rpx;background-size:80rpx 80rpx;background-color:#F3F3F3;'" @tap="uploadimg" data-field="shop_img" data-pernum="9" v-if="shop_img.length<5"></view>
					</view>
					<input type="text" hidden="true" name="shop_img" :value="shop_img.join(',')" maxlength="-1"></input>
				</view>
			</view>
			<view class="form-box">
				<view class="form-item flex-col">
					<view class="f1">标注平台</view>
					<view class="f2" style="line-height:30px">
						<checkbox-group class="radio-group" name="cids" @change="bindGettjChange" >
							<label v-for="item in map_cats" :key="item.id"><checkbox :value="''+item.id" :checked="inArray(item.id,inArray)?true:false"></checkbox> {{item.name}}</label>
						</checkbox-group>
					</view>
				</view>
			</view>
			<button class="savebtn" :style="'background:linear-gradient(90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'" form-type="submit">
				提交
				<text v-if="total_pay>0">（需支付：{{total_pay}}元)</text>
				</button>
			<view style="height:50rpx"></view>
		</form>
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
		isload:false,
		loading:false,
		pre_url:app.globalData.pre_url,
		license_img:[],
		shop_img:[],
		map_cats:[],
		cids:[],
		total_pay:0,
		bgcolor:'#fff'
    };
  },

  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.getdata();
  },
  methods: {
		getdata:function(){
			var that = this;
			that.loading = true;
			app.get('ApiMapmark/getCatgory',{}, function (res) {
				that.loading = false;
				that.map_cats = res.data.datalist;
				that.bgcolor = res.data.bgcolor
				that.loaded();
			});
		},

		subform: function (e) {
		  var that = this;
		  var formdata = e.detail.value;
			if(!formdata.name){
				app.alert('请填写标准名称');
				return;
			}
			if(!formdata.shop_type){
				app.alert('请填写经营类型');
				return;
			}
			if(!formdata.shop_tel){
				app.alert('请填写营业电话');
				return;
			}
			if(!formdata.shop_time){
				app.alert('请填写营业时间');
				return;
			}
			if(!formdata.address){
				app.alert('请填写详细经营地址');
				return;
			}
			if(!formdata.mobile){
				app.alert('请填写联系电话');
				return;
			}
			if(!formdata.license_img){
				app.alert('请上传营业执照');
				return;
			}
			if(!formdata.shop_img){
				app.alert('请上传门面照片');
				return;
			}

		  app.post('ApiMapmark/createOrder', formdata, function (res) {
			if (res.status == 0) {
			  app.error(res.msg);
			  return;
			} else {
			  app.success(res.msg);
			}
			if(res.data.payorderid){
				app.goto('/pagesExt/pay/pay?id=' + res.data.payorderid);
			}
		  });
		},
		bindGettjChange:function(e){
			console.log(e);
			console.log(e.detail.value);
			this.cids = e.detail.value;
			console.log(this.cids)
			var that = this;
			app.post('ApiMapmark/getCatMoney', {cids:this.cids}, function (res) {
				if (res.status == 0) {
				  app.error(res.msg);
				} else {
				  that.total_pay = res.data
				}
			});

		},
		uploadimg:function(e){
			var that = this;
			var pernum = parseInt(e.currentTarget.dataset.pernum);
			if(!pernum) pernum = 1;
			var field= e.currentTarget.dataset.field
			var pics = that[field]
			if(!pics) pics = [];
			app.chooseImage(function(urls){
				for(var i=0;i<urls.length;i++){
					pics.push(urls[i]);
				}
				if(field == 'license_img') that.license_img = pics;
				if(field == 'shop_img') that.shop_img = pics;
			},pernum);
		},
		removeimg:function(e){
			var that = this;
			var index= e.currentTarget.dataset.index
			var field= e.currentTarget.dataset.field
			if(field == 'license_img'){
				var pics = that.license_img
				pics.splice(index,1);
				that.license_img = pics;
			}else if(field == 'shop_img'){
				var pics = that.shop_img
				pics.splice(index,1);
				that.shop_img = pics;
			}
		},
  }
};
</script>
<style>
radio{transform: scale(0.6);}
checkbox{transform: scale(0.6);}
.form-box{ padding:2rpx 24rpx 0 24rpx; background: #fff;margin: 24rpx;border-radius: 10rpx}
.form-item{ line-height: 100rpx; display: flex;justify-content: space-between;border-bottom:1px solid #eee }
.form-item .f1{color:#222;width:200rpx;flex-shrink:0}
.form-item .f2{display:flex;align-items:center}
.form-box .form-item:last-child{ border:none}
.form-box .flex-col{padding-bottom:20rpx}
.form-item input{ width: 100%; border: none;color:#111;font-size:28rpx; text-align: right}
.form-item textarea{ width:100%;min-height:200rpx;padding:20rpx 0;border: none;}
.form-item .upload_pic{ margin:50rpx 0;background: #F3F3F3;width:90rpx;height:90rpx; text-align: center  }
.form-item .upload_pic image{ width: 32rpx;height: 32rpx; }
.savebtn{ width: 90%; height:96rpx; line-height: 96rpx; text-align:center;border-radius:48rpx; color: #fff;font-weight:bold;margin: 0 5%; margin-top:60rpx; border: none; }

.ggtitle{height:60rpx;line-height:60rpx;color:#111;font-weight:bold;font-size:26rpx;display:flex;border-bottom:1px solid #f4f4f4}
.ggtitle .t1{width:200rpx;}
.ggcontent{line-height:60rpx;margin-top:10rpx;color:#111;font-size:26rpx;display:flex}
.ggcontent .t1{width:200rpx;display:flex;align-items:center;flex-shrink:0}
.ggcontent .t1 .edit{width:40rpx;height:40rpx}
.ggcontent .t2{display:flex;flex-wrap:wrap;align-items:center}
.ggcontent .ggname{background:#f55;color:#fff;height:40rpx;line-height:40rpx;padding:0 20rpx;border-radius:8rpx;margin-right:20rpx;margin-bottom:10rpx;font-size:24rpx;position:relative}
.ggcontent .ggname .close{position:absolute;top:-14rpx;right:-14rpx;background:#fff;height:28rpx;width:28rpx;border-radius:14rpx}
.ggcontent .ggnameadd{background:#ccc;font-size:36rpx;color:#fff;height:40rpx;line-height:40rpx;padding:0 20rpx;border-radius:8rpx;margin-right:20rpx;margin-left:10rpx;position:relative}
.ggcontent .ggadd{font-size:26rpx;color:#558}

.ggbox{line-height:50rpx;}


.layui-imgbox{margin-right:16rpx;margin-bottom:10rpx;font-size:24rpx;position: relative;}
.layui-imgbox-img{display: block;width:200rpx;height:200rpx;padding:2px;border: #d3d3d3 1px solid;background-color: #f6f6f6;overflow:hidden}
.layui-imgbox-img>image{max-width:100%;}
.layui-imgbox-repeat{position: absolute;display: block;width:32rpx;height:32rpx;line-height:28rpx;right: 2px;bottom:2px;color:#999;font-size:30rpx;background:#fff}
.uploadbtn{position:relative;height:200rpx;width:200rpx}


.clist-item{display:flex;border-bottom: 1px solid #f5f5f5;padding:20rpx 30rpx;}
.radio{flex-shrink:0;width: 32rpx;height: 32rpx;background: #FFFFFF;border: 2rpx solid #BFBFBF;border-radius: 50%;margin-right:30rpx}
.radio .radio-img{width:100%;height:100%;display:block}

.freightitem{width:100%;height:60rpx;display:flex;align-items:center;margin-left:40rpx}
.freightitem .f1{color:#666;flex:1}

.detailop{display:flex;line-height:60rpx}
.detailop .btn{border:1px solid #ccc;margin-right:10rpx;padding:0 16rpx;color:#222;border-radius:10rpx}
.detaildp{position:relative;line-height:50rpx}
.detaildp .op{width:100%;display:flex;justify-content:flex-end;font-size:24rpx;height:60rpx;line-height:60rpx;margin-top:10rpx}
.detaildp .op .btn{background:rgba(0,0,0,0.4);margin-right:10rpx;padding:0 10rpx;color:#fff}
.detaildp .detailbox{border:2px dashed #00a0e9}

.uni-popup-dialog {width: 300px;border-radius: 5px;background-color: #fff;}
.uni-dialog-title {display: flex;flex-direction: row;justify-content: center;padding-top: 15px;padding-bottom: 5px;}
.uni-dialog-title-text {font-size: 16px;font-weight: 500;}
.uni-dialog-content {display: flex;flex-direction: row;justify-content: center;align-items: center;padding: 5px 15px 15px 15px;}
.uni-dialog-content-text {font-size: 14px;color: #6e6e6e;}
.uni-dialog-button-group {display: flex;flex-direction: row;border-top-color: #f5f5f5;border-top-style: solid;border-top-width: 1px;}
.uni-dialog-button {display: flex;flex: 1;flex-direction: row;justify-content: center;align-items: center;height: 45px;}
.uni-border-left {border-left-color: #f0f0f0;border-left-style: solid;border-left-width: 1px;}
.uni-dialog-button-text {font-size: 14px;}
.uni-button-color {color: #007aff;}
</style>
