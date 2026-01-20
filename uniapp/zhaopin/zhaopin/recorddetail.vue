<template>
<view style="width:100%" v-if="isload">
	<view class="product-container">
		<view class="product-itemlist" v-if="type==2">
			<view class="item">
					<view class="item1">
							<view class="product-info">
								<view class="p1">
								<text class="zhaoicon">招</text><text class="business" v-if="detail.business_name">{{detail.business_name}}-</text>{{zhaopin.title}}
								</view>
								<view class="p2"><text>薪资范围：</text><text class="number">{{zhaopin.salary}}</text></view>
								<view class="p2">
									<text>招聘岗位：</text>
									<text>{{zhaopin.cname}}</text>
								</view>
							</view>
							<view class="product-info product-info2">
								<view class="p1">
								<text class="qiuicon">求</text>{{qiuzhi.title}}
								</view>
								<view class="p2"><text>期望薪资：</text><text class="number">{{qiuzhi.salary}}</text></view>
								<view class="p2">
									<text>期望岗位：</text>
									<text>{{qiuzhi.cnames}}</text>
								</view>
							</view>
					</view>
			</view>
			<view class="box" v-if="contract_pics.length>0">
				<view class="imgbox">
					<view class="form-imgbox" v-for="(pic, index) in contract_pics" :key="index">
						<view class="form-imgbox-close" v-if="detail.contract_status==0 || detail.contract_status==2" @tap="removeimgPics" :data-index="index">
							<image src="/static/img/ico-del.png"></image>
						</view>
						<view class="form-imgbox-img">
							<image :src="pic" @click="previewImage" :data-url="pic" mode="widthFix" />
						</view>
					</view>
				</view>
			</view>
			<view class="addbtn" @tap="uploadpics" :data-id="detail.id" v-if="detail.contract_status==0 || detail.contract_status==2">{{contract_pics.length>0?'追加':'上传'}}合同</view>
			<view class="done" v-if="detail.contract_status==1">合同已审核</view>
		</view>
		
		<view class="product-itemlist" v-if="type==1">
			<view class="item">
					<view class="item1">
							<view class="product-info">
								<view class="p1">
								<text class="qiuicon">求</text>{{qiuzhi.title}}
								</view>
								<view class="p2"><text>期望薪资：</text><text class="number">{{qiuzhi.salary}}</text></view>
								<view class="p2">
									<text>期望岗位：</text>
									<text>{{qiuzhi.cnames}}</text>
								</view>
							</view>
							<view class="product-info product-info2">
								<view class="p1">
								<text class="zhaoicon">招</text><text class="business" v-if="detail.business_name">{{detail.business_name}}-</text>{{zhaopin.title}}
								</view>
								<view class="p2"><text>薪资范围：</text><text class="number">{{zhaopin.salary}}</text></view>
								<view class="p2">
									<text>招聘岗位：</text>
									<text>{{zhaopin.cname}}</text>
								</view>
							</view>
					</view>
			</view>
			
			<view class="box" v-if="contract_pics.length>0">
				<view class="imgbox">
					<view class="form-imgbox" v-for="(pic, index) in contract_pics" :key="index">
						<!-- <view class="form-imgbox-close" @tap="removeimgPics" :data-index="index">
							<image src="/static/img/ico-del.png"></image>
						</view> -->
						<view class="form-imgbox-img">
							<image :src="pic" @click="previewImage" :data-url="pic" mode="widthFix" />
						</view>
					</view>
				</view>
			</view>
			<view class="done" v-if="detail.contract_status==1">合同已审核</view>
			<view class="done" v-else>{{contract_pics.length==0?'合同待提交':'合同待审核'}}</view>
		</view>
		<!-- qiuzhi end -->
	</view>
	<loading v-if="loading"></loading>
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
			menuindex:-1,
			pre_url:app.globalData.pre_url,
      st:0,
			detail:[],
			contract_pics:[],
			zhaopin:[],
			qiuzhi:[],
			type:0
    };
  },
  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.bid = this.opt.bid ? this.opt.bid : 0;
		this.type = this.opt.type ? this.opt.type : 0;
		this.getdata();
  },
	onPullDownRefresh: function () {
		this.getdata();
	},
  methods: {
		getdata:function(){
			var that = this
			that.loading  = true
			app.get('ApiZhaopin/zhaopinRecordDetail',{
				id:that.opt.id,
				type:that.type
			}, function (res) {
				that.loading = false;
			  that.detail = res.detail
				that.zhaopin = res.detail.zhaopin
				that.qiuzhi = res.detail.qiuzhi
				that.contract_pics = res.detail.contract_pics
			});
			that.loaded()
		},
		uploadpics: function (e) {
			var that = this;
			var id = e.currentTarget.dataset.id;
			var pics = [];
			var contractpics = that.contract_pics;
			app.chooseImage(function(urls){
				for(var i=0;i<urls.length;i++){
					pics.push(urls[i]);
					contractpics.push(urls[i])
				}
				if(pics.length>0){
					app.post("ApiZhaopin/contracntUpload", {id:id,pics:pics.join(',')}, function (data) {
						app.showLoading(false);
					  if (data.status == 1) {
					    app.success(data.msg);
					    that.contact_pics = contractpics
					  } else {
					    app.error(data.msg);
					  }
					});
				}
			},20)
		},
		removeimgPics:function(e){
			var that = this;
			var idx = e.currentTarget.dataset.index;
			var contract_pics = that.contract_pics;
			contract_pics.splice(idx, 1)
			that.loading = true;
			app.post("ApiZhaopin/contracntUpdate", {id:that.opt.id,pics:contract_pics.join(',')}, function (data) {
				that.loading = false;
			  if (data.status == 1) {
			    that.contract_pics = contract_pics
			  } else {
			    app.error(data.msg);
			  }
			});
		},
	
  }
};
</script>
<style>
@import "../common.css";
.topsearch{width:94%;margin:16rpx 3%;margin-top: 20rpx;}
.topsearch .f1{height:60rpx;border-radius:30rpx;border:0;background-color:#fff;flex:1}
.topsearch .f1 .img{width:24rpx;height:24rpx;margin-left:10px}
.topsearch .f1 input{height:100%;flex:1;padding:0 20rpx;font-size:28rpx;color:#333;}
.product-container {width: 100%;margin-top: 20rpx;font-size:26rpx;padding:0;}
.product-itemlist{height: auto; position: relative;overflow: hidden; padding: 0px; }
.product-itemlist .item{width:100%;display: inline-block;margin-bottom: 20rpx;background: #fff;padding: 20rpx;padding-top: 0;}
.product-itemlist .item1{align-items: center;padding: 20rpx;padding-bottom: 0;}
.product-itemlist .product-info {color: #999;font-size: 24rpx;padding-bottom:10rpx;}
.product-itemlist .product-info2 {border-top: 1rpx dashed #e8e8e8;padding-top:10rpx;text-align: right;}
.product-itemlist .product-info .p1 {color:#323232;font-weight:bold;font-size:28rpx;line-height:36rpx;margin-bottom:10rpx;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:2;overflow:hidden;}
.product-itemlist .product-info .number {color:#FF3A69;}
.product-itemlist .product-info .p2 {line-height: 40rpx;}
.product-itemlist .item2{ border-top: 1rpx solid #f6f6f6;; padding-top: 14rpx; justify-content: flex-start; line-height: 36rpx; color: #6c6c6c; font-size: 24rpx;flex-wrap: wrap;}
.product-itemlist .item2 .tagitem{background: #f4f7fe;text-align: center;padding: 2rpx 8rpx;margin-right: 8rpx;white-space: normal;}
.product-itemlist .text2{ color:#FF3A69; width: 128rpx;height: 48rpx;border-radius: 24rpx 0px 0px 24rpx; text-align: center;background: linear-gradient(-90deg, rgba(255, 235, 240, 0.4) 0%, #FDE6EC 100%);}
.option{padding-top: 20rpx;border-top: 1rpx solid #e8e8e8;}
.option .btn{min-width: 120rpx;text-align: center;border: 1rpx solid #e4e4e4;padding: 6rpx 10rpx;margin-left: 10rpx;color: #757575;font-size: 24rpx;}
.option .btn.st1{background: #F05525;color: #FFFFFF;border-color: #fcdacfdb;}

.tosign{width: 100rpx;height: 100rpx;background: #031028;color: #FFFFFF;position: fixed;bottom: 130rpx;right: 10rpx;display:flex;justify-content: center;align-items: center;
border-radius: 50%;flex-direction: column;text-align: center;font-size: 24rpx;}

.product-info .qiuicon{background: #ff9b0540;color: #ff9b05; border-radius: 6rpx;padding: 0 6rpx;font-weight: normal;font-size: 24rpx;margin-right: 4rpx;}
.product-info .zhaoicon{background: #00968833;color: #009688;border-radius: 6rpx;padding: 0 6rpx;font-weight: normal;font-size: 24rpx;}

.box{background: #FFFFFF;padding: 20rpx 0;}
.addbtn{width: 80%;border: 1rpx solid #e4e4e4;padding:20rpx;border-radius: 50rpx;text-align: center;margin: 0 auto;margin-top: 20rpx;background: #FFFFFF;color: #222222;margin-bottom: 20rpx;}
.done{width: 80%;border: 1rpx solid #e4e4e4;padding:20rpx;border-radius: 50rpx;text-align: center;margin: 0 auto;margin-top: 20rpx;background: #E8E8E8;margin-bottom: 20rpx;color: #838383;}
	/* 图片 */
	.imgbox{display: flex;flex-direction: column;justify-content: center;text-align: center;margin-top: 20rpx;}
	.form-imgbox{margin-right:16rpx;margin-bottom:10rpx;font-size:24rpx;position: relative;}
	.form-imgbox-close{position: absolute;display: block;width:40rpx;height:40rpx;right:30rpx;top:-16rpx;color:#999;font-size:32rpx;background:#fff;z-index: 9;border-radius: 50%;}
	.form-imgbox-close image{width:100%;height:100%;z-index: 9;}
	.form-imgbox-img{width: 100%;}
	.form-imgbox-img>image{max-width:100%;}
	.form-imgbox-repeat{position: absolute;display: block;width:32rpx;height:32rpx;line-height:28rpx;right: 2px;bottom:2px;color:#999;font-size:30rpx;background:#fff}
	/* 
	.form-imgbox{display: flex;flex-direction: column;justify-content: center;text-align: center;}
	.form-imgbox-close{position: absolute;display: block;width:32rpx;height:32rpx;right:-16rpx;top:-16rpx;color:#999;font-size:32rpx;background:#fff}
	.form-imgbox-close image{width:100%;height:100%;z-index: 9;}
	.form-imgbox-img{width: 100%;}
	.form-imgbox-img>image{max-width:100%;}
	.form-imgbox-repeat{position: absolute;display: block;width:32rpx;height:32rpx;line-height:28rpx;right: 2px;bottom:2px;color:#999;font-size:30rpx;background:#fff} */
</style>