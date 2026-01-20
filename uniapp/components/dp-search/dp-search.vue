<template>
<view class="dp-search" :style="{
	backgroundColor:params.bgcolor,
	margin:(params.margin_y*2.2)+'rpx '+(params.margin_x*2.2)+'rpx',
	padding:(params.padding_y*2.2)+'rpx '+(params.padding_x*2.2)+'rpx'
}">
	<view v-if="!params.openmore || params.openmore<=0 " class="dp-search-search"	:style="{borderColor:params.bordercolor,borderRadius:params.borderradius+'px'}">
		<view class="dp-search-search-f1" :style="{backgroundImage:`url(${pre_url}/static/img/search_ico.png)`}"></view>
		<view class="dp-search-search-f2">
			<input class="dp-search-search-input" @confirm="searchgoto" @input="inputKeyword" :data-url="params.hrefurl" name="keyword"
				:placeholder="params.placeholder|| '输入关键字在店铺内搜索'" placeholder-style="color:#aaa;font-size:28rpx" :style="params.color?'color:'+params.color:''" />
		</view>
		<view class="dp-search-search-f3" v-if="params.image_search==1" @tap="goto" :data-url="'/pagesExt/shop/imgsearch?bid='+params.bid" :style="'background-image:url('+pre_url+'/static/img/camera.png)'"></view>
		<view class="dp-search-search-scanCode" @tap="scanQRCode" v-if="params.scan_code==1" :style="'background-image:url('+pre_url+'/static/img/scan-icon2.png)'"></view>
		<view v-if="params.btn==1" class="search-but-class" @tap="searchgoto" :data-url="params.hrefurl" 
		:style="{backgroundColor:t('color1'),borderRadius:params.borderradius+'px'}">
				搜索
		</view>
	</view>
	<view v-else style="display: flex;">
			<view  style="width: 140rpx;overflow: hidden;">
					<!--搜索列表s-->
					<picker  @change="dataChange"  :value="data_index" :range="data" range-key='title1' style="line-height: 72rpx;background-color: #fff;padding-left: 10rpx;overflow: hidden;border: 0;">
							<view style="width:80rpx;white-space: nowrap;overflow: hidden;float: left;">{{data_name}}</view>
							<image :src="pre_url+'/static/img/hdsanjiao.png'" style="width: 26rpx;height: 26rpx;float: right;margin-top: 26rpx;"></image>
					</picker>
					<!--搜索列表e-->
			</view>
			<view class="dp-search-search" :style="{borderColor:params.bordercolor,borderRadius:params.borderradius+'px',width:'calc(100% - 140rpx)'}">
					<view class="dp-search-search-f1" :style="{backgroundImage:`url(${pre_url}/static/img/search_ico.png)`}"></view>
					<view class="dp-search-search-f2">
						<input class="dp-search-search-input" @confirm="searchgoto" @input="inputKeyword" :data-url="data_hrefurl" name="keyword"
							:placeholder="data_placeholder?data_placeholder:params.placeholder|| '输入关键字在店铺内搜索'" placeholder-style="color:#aaa;font-size:28rpx" :style="params.color?'color:'+params.color:''" />
					</view>
					<view class="dp-search-search-f3" v-if="params.image_search==1" @tap="goto" :data-url="data_hrefurl" :style="'background-image:url('+pre_url+'/static/img/camera.png)'"></view>
					<view class="dp-search-search-scanCode" @tap="scanQRCode" v-if="params.scan_code==1" :style="'background-image:url('+pre_url+'/static/img/scan-icon2.png)'"></view>
			</view>
			<view v-if="params.btn==1">
					<view  @tap="searchgoto" :data-url="data_hrefurl" style="width: 100rpx;text-align: center;float: right;line-height:72rpx;background: #f0f0f0;">
							搜索
					</view>
			</view>
	</view>
</view>
</template>
<script>
	var app =getApp();
	export default {
		data(){
			return {
				pre_url:getApp().globalData.pre_url,
        
				data_index:0,
				data_name:'',//类型名称
				data_placeholder:'',//搜索提示
				data_hrefurl:'',
				keyword:''
			}
		},
		
		props: {
			params: {
				bid:{default:0}
				},
			data: {}
		},
		mounted:function(){
				var that = this;
				console.log(that.params)
				if(that.data){
						that.data_name        = that.data[0]['title1'];
						that.data_placeholder = that.data[0]['title2'];
						that.data_hrefurl     = that.data[0]['hrefurl'];
						console.log(that.data[0]['hrefurl'])
				}
		},
		methods:{
			searchgoto:function(e){
				var that = this;
				var keyword = that.keyword;
				var url = e.currentTarget.dataset.url;
				if (url.indexOf('?') > 0) {
						url += '&keyword='+keyword;
				}else{
						url += '?keyword='+keyword;
				}
				var opentype = e.currentTarget.dataset.opentype
				app.goto(url,opentype);
			},
			dataChange:function(e){
					var that = this;
					var data = that.data;
					var data_index  = e.detail.value;
					that.data_index = data_index;
					that.data_name        = data[data_index]['title1'];
					that.data_placeholder = data[data_index]['title2'];
					that.data_hrefurl     = data[data_index]['hrefurl'];
			},
			inputKeyword:function(e){
					var that = this;
					that.keyword = e.detail.value;
			},
			scanQRCode:function(e){
				//调用二维码扫描接口
				if(app.globalData.platform == 'h5'){
					// #ifdef H5
					app.alert('请使用微信扫一扫功能扫码');return;
					// #endif
				}else if(app.globalData.platform == 'mp'){
					// #ifdef H5
					var jweixin = require('jweixin-module');
					jweixin.ready(function () {   //需在用户可能点击分享按钮前就先调用
						jweixin.scanQRCode({
							needResult: 1, // 默认为0，扫描结果由微信处理，1则直接返回扫描结果，
							scanType: ["qrCode","barCode"], // 可以指定扫二维码还是一维码，默认二者都有
							success: function (res) {
								var content = res.resultStr; // 当needResult 为 1 时，扫码返回的结果
								if(content.indexOf(',') > 0){
									content = content.split(',')[1];
								}
								app.post('ApiShop/scanCodeSearchGoods', {specs_number:content}, function (d) {
									if (d.status == 1) {
										app.goto('pages/shop/product?id='+d.product_id);
									}else{
										app.alert(d.msg);
										return;
									}
								});
							},
							fail:function(err){
								app.error(err.errMsg);
							}
						});
					});
					// #endif
				}else{
					// #ifndef H5
					uni.scanCode({
						//{result: "6923450656860", rawData: "NjkyMzQ1MDY1Njg2MA==", codeVersion: , errMsg: "scanCode:ok", scanType: "EAN_13"}
						success: function(res){
							console.log(res);
							var content = res.result;
							if(!content){
							  app.alert('请扫描正确的商品码');
							  return;
							}
							app.post('ApiShop/scanCodeSearchGoods', {specs_number:content}, function (d) {
								if (d.status == 1) {
									app.goto('pages/shop/product?id='+d.product_id);
								}else{
									app.alert(d.msg);
									return;
								}
							});
						},
						fail:function(err){
						  console.error('扫码失败：', err);
						}
					});
					// #endif
				}
			}
		}
	}
</script>
<style>
.dp-search {padding:20rpx;height: auto; position: relative;}
.dp-search-search {height:72rpx;background: #fff;border: 1px solid #c0c0c0;border-radius: 6rpx;overflow: hidden;display:flex;align-items: center;}
.dp-search-search-f1 {height:72rpx;width:72rpx;color: #666;border: 0px;padding: 0px;margin: 0px;background-size:30rpx;background-position: center;background-repeat: no-repeat;}
.dp-search-search-f2{height: 72rpx;flex:1}
.dp-search-search-f3 {height:72rpx;width:72rpx;color: #666;border: 0px;padding: 0px;margin: 0px;background-position: center;background-repeat: no-repeat; background-size:40rpx;}
.dp-search-search-input {height:72rpx;width: 100%;border: 0px;padding: 0px;margin: 0px;outline: none;color: #666;}
.dp-search-search-scanCode{height:72rpx;width:72rpx;color: #666;border: 0px;padding: 0px;margin: 0px;background-position: center;background-repeat: no-repeat; background-size:30rpx;}
.search-but-class{width: 100rpx;text-align: center;color: #fff;font-size: 26rpx;height: 60rpx;line-height: 60rpx;margin-right:4rpx;}
</style>
