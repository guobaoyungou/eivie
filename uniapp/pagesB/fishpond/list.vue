<template>
	<view class="container">
		<view class="search-container">
			<view class="topsearch flex-y-center">
				<view class="f1 flex-y-center">
					<image class="img" :src="pre_url+'/static/img/search_ico.png'"></image>
					<input :value="keyword" placeholder="搜索你感兴趣的鱼塘" placeholder-style="font-size:24rpx;color:#C2C2C2" confirm-type="search"  @confirm="searchConfirm" @input="searchChange"></input>
				</view>
			</view>
		</view>
		<view class="content">
			<view v-for="(item, index) in datalist" :key="index" class="item">			
				<view :data-url="'detail?id=' + item.id" @tap="goto" class="product-item2">
					<view class="product-info">
						<view class="p1">{{item.name}}</view>
						<view class="p3">
							<text class="iconfont icondingwei" style="color:#888;"></text>
							<text class="t2" v-if="item.address" @tap.stop="openLocation" :data-latitude="item.latitude" :data-longitude="item.longitude" :data-company="item.name" :data-address="item.address">{{item.address}}</text> 
						</view>
						<view class="p2" >
							<image class="img-tel" :src="pre_url+'/static/img/b_tel.png'" />
							<text class="t2" @tap.stop="phone" :data-phone="item.tel">{{item.tel}}</text> 
						</view>
					</view>
					<view class="product-pic">
						<image :src="item.pic" mode="widthFix"></image>
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
				mid: 0,
				pre_url: app.globalData.pre_url,
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
				app.post('ApiFishPond/getlist', {
					st: st,
					pagenum: pagenum,
					mid: that.mid,
					keyword: keyword
				}, function(res) {
					that.loading = false;
					var data = res.data;
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
	.search-container {
		position: fixed;
		width: 100%;
		background: #fff;
		z-index: 9;
		top: var(--window-top)
	}

	.topsearch {
		width: 100%;
		padding: 16rpx 20rpx;
	}

	.topsearch .f1 {
		height: 60rpx;
		border-radius: 30rpx;
		border: 0;
		background-color: #f7f7f7;
		flex: 1
	}

	.topsearch .f1 .img {
		width: 24rpx;
		height: 24rpx;
		margin-left: 10px
	}

	.topsearch .f1 input {
		height: 100%;
		flex: 1;
		padding: 0 20rpx;
		font-size: 28rpx;
		color: #333;
	}

	.topsearch .search-btn {
		display: flex;
		align-items: center;
		color: #5a5a5a;
		font-size: 30rpx;
		width: 60rpx;
		text-align: center;
		margin-left: 20rpx
	}

	.search-navbar {
		display: flex;
		text-align: center;
		align-items: center;
		padding: 5rpx 0
	}

	.search-navbar-item {
		flex: 1;
		height: 70rpx;
		line-height: 70rpx;
		position: relative;
		font-size: 28rpx;
		font-weight: bold;
		color: #323232
	}

	.search-navbar-item .iconshangla {
		position: absolute;
		top: -4rpx;
		padding: 0 6rpx;
		font-size: 20rpx;
		color: #7D7D7D
	}

	.search-navbar-item .icondaoxu {
		position: absolute;
		top: 8rpx;
		padding: 0 6rpx;
		font-size: 20rpx;
		color: #7D7D7D
	}

	.search-navbar-item .iconshaixuan {
		margin-left: 10rpx;
		font-size: 22rpx;
		color: #7d7d7d
	}

	.content {
		margin-top: 115rpx;
	}

	.item {
		width: 94%;
		margin: 0 3%;
		padding: 0 20rpx;
		background: #fff;
		margin-top: 20rpx;
		border-radius: 20rpx
	}

	.product-item2 {
		display: flex;
		padding: 20rpx 0;
		border-bottom: 1px solid #E6E6E6;
	}

	.product-item2 .product-pic {
		width: 180rpx;
		height: 180rpx;
		background: #ffffff;
		overflow: hidden
	}

	.product-item2 .product-pic image {
		width: 100%;
		height: 100%;
	}

	.product-item2 .product-info {
		flex: 1;
		padding: 5rpx 10rpx;
	}

	.product-item2 .product-info .p1 {
		word-break: break-all;
		text-overflow: ellipsis;
		overflow: hidden;
		display: block;
		height: 80rpx;
		line-height: 40rpx;
		font-size: 30rpx;
		color: #111111
	}

	.product-item2 .product-info .p2 {
		font-size: 32rpx;
		height: 40rpx;
		line-height: 40rpx
	}

	.product-item2 .product-info .p2 .t2 {
		margin-left: 10rpx;
		font-size: 26rpx;
		color: #888;
	}

	.product-item2 .product-info .p3 {
		font-size: 24rpx;
		line-height: 50rpx;
		overflow: hidden
	}

	.product-item2 .product-info .p3 .t1 {
		color: #aaa;
		font-size: 24rpx
	}

	.product-item2 .product-info .p3 .t2 {
		color: #888;
		font-size: 24rpx;
		margin-left: 10rpx;
	}

	.foot {
		display: flex;
		align-items: center;
		width: 100%;
		height: 100rpx;
		line-height: 100rpx;
		color: #999999;
		font-size: 24rpx;
	}

	.foot .btn {
		padding: 2rpx 10rpx;
		height: 50rpx;
		line-height: 50rpx;
		color: #FF4C4C
	}
	.img-tel{
		width: 24rpx;
		height: 24rpx;
		transform: scaleX(-1);
	}
</style>