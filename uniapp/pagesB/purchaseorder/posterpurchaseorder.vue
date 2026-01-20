<template>
	<view class="container">
		<view class="head flex-bt">
			<view>有效期：{{expiring_date}}</view>
			<view class="totla-sum">
				<text>合计数量：</text>
				<text class="total-number">{{count}}</text>
			</view>
		</view>
		<view class="content">
			<view v-for="(item, index) in datalist" :key="index" class="item">			
				<view :data-url="'/pages/shop/product?id=' + item.proid+'&show_image=0'" @tap="goto" class="product-item2">
					<view class="product-pic">
						<image :src="item.pic" mode="widthFix"></image>
					</view>
					<view class="product-info">
						<view class="p1">{{item.name}}</view>
						<view class="p3">
							<text class="t2">规格：</text>
							<text class="t2">{{item.ggname}}</text> 
						</view>
						<view class="p3">
							<text class="t2">数量：</text>
							<text class="t2">{{item.num}}</text> 
						</view>
					</view>
				</view>
			</view>
		</view>
		<nomore v-if="nomore"></nomore>
		<nodata v-if="nodata"></nodata>
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
				opt: {},
				loading: false,
				isload: false,
				menuindex: -1,

				datalist: [],
				pagenum: 1,
				nomore: false,
				nodata: false,
				expiring_date:'',
				count:0,
				mid: 0
			};
		},

		onLoad: function(opt) {
			this.opt = app.getopts(opt);
			this.mid = this.opt.mid || 0;
			this.getdata();
		},
		onPullDownRefresh: function() {
			this.getdata();
		},
		onReachBottom: function() {
			if (!this.nodata && !this.nomore) {
				this.pagenum = this.pagenum + 1;
				this.getdata(true);
			}
		},
		onShow() {
			uni.hideHomeButton();
		},
		methods: {
			getdata: function(loadmore) {
				if (!loadmore) {
					this.pagenum = 1;
					this.datalist = [];
				}
				var that = this;
				var pagenum = that.pagenum;
				var st = that.st;
				var keyword = that.keyword;
				that.nodata = false;
				that.nomore = false;
				that.loading = true;
				app.post('ApiPurchaseOrder/posterPurchaseOrder', {
					orderid: that.opt.orderid,
					pagenum: pagenum
				}, function(res) {
					that.loading = false;
					if(res.status == 0){
						return app.alert(res.msg);
					}
					var data = res.data;
					that.order = res.order;
					that.count = res.count;
					if(res.order && res.order.expiring_date){
						that.expiring_date = res.order.expiring_date;
					}
					if (pagenum == 1) {
						that.datalist = data;
				
						if (data.length == 0) {
							that.nodata = true;
						}
						that.loaded();
					} else {
						if (data.length == 0) {
							that.nomore = true;
						} else {
							var datalist = that.datalist;
							var newdata = datalist.concat(data);
							that.datalist = newdata;
						}
					}
				});
			},
			searchChange: function (e) {
			  this.keyword = e.detail.value;
			},
			searchConfirm: function (e) {
			  var that = this;
			  var keyword = e.detail.value;
			  that.keyword = keyword;
			  that.getdata();
			},
			openLocation:function(e){
				var latitude = parseFloat(e.currentTarget.dataset.latitude)
				var longitude = parseFloat(e.currentTarget.dataset.longitude)
				var address = e.currentTarget.dataset.address
				uni.openLocation({
				 latitude:latitude,
				 longitude:longitude,
				 name:address,
				 scale: 13
			 })		
			},
			phone:function(e) {
				var phone = e.currentTarget.dataset.phone;
				uni.makePhoneCall({
					phoneNumber: phone,
					fail: function (err) {
						console.log(err);
					}
				});
			}
		}
	};
</script>
<style>
	.head{height: 80rpx;align-items: center;padding: 25rpx;background: #fff;}
	.item{width:94%;margin:0 3%;padding:0 20rpx;background:#fff;margin-top:20rpx;border-radius:20rpx}
	.product-item2{display:flex;padding:20rpx 0;border-bottom:1px solid #E6E6E6}
	.product-item2 .product-pic{width:180rpx;background:#ffffff;overflow:hidden}
	.product-item2 .product-pic image{width:100%;height:100%}
	.product-item2 .product-info{flex:1;padding:5rpx 20rpx}
	.product-item2 .product-info .p1{word-break:break-all;text-overflow:ellipsis;overflow:hidden;display:block;line-height:50rpx;font-size:30rpx;}
	.product-item2 .product-info .p2{font-size:32rpx;height:40rpx;line-height:40rpx}
	.product-item2 .product-info .p2 .t2{margin-left:10rpx;font-size:26rpx;color:#888}
	.product-item2 .product-info .p3{font-size:24rpx;line-height:50rpx;overflow:hidden}
	.product-item2 .product-info .p3 .t1{color:#aaa;font-size:24rpx}
	.product-item2 .product-info .p3 .t2{color:#888;font-size:24rpx}
	.foot{display:flex;align-items:center;width:100%;height:100rpx;line-height:100rpx;color:#999999;font-size:24rpx}
	.foot .btn{padding:2rpx 10rpx;height:50rpx;line-height:50rpx;color:#FF4C4C}
	.total-number{width: 50rpx;margin-left: 10rpx;}
</style>