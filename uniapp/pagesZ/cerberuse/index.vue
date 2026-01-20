<template>
<view class="container">
	<block v-if="isload">
		<view class="topsearch flex-y-center">
			<view class="f1 flex-y-center">
				<image class="img" :src="pre_url+'/static/img/search_ico.png'"></image>
				<input :value="keyword" placeholder="搜索感兴趣的商品" placeholder-style="font-size:24rpx;color:#C2C2C2" @confirm="searchConfirm" @input="searchChange"></input>
			</view>
		</view>
		<view class="dp-product-item">
			<view class="item" v-for="(item,index) in datalist" style="width: 48%; margin:15rpx  1%" :key="item.id" @click="goto" :data-url="'detail?type=1&id='+item.id">
				<view class="product-pic">
					<image class="image" :src="item.pic" mode="widthFix"/>
				</view>
				<view class="product-info">
					<view class="p1" >{{item.title}}</view>
					<view class="p2">
						<view class="p2-1" >
							<text class="t1" :style="{color:t('color1')}"><text style="font-size:24rpx">￥</text>{{item.price}} <text style="font-size: 24rpx;">/小时</text> </text>
						</view>
					</view>  
		         </view>
			</view>
		</view>
	</block>
	<nodata v-if="nodata"></nodata>
	<nomore v-if="nomore"></nomore>
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
		menuindex:-1,

		nodata: false,
		nomore: false,
		st: 0,
		datalist: [],
		textset:{},
		pagenum: 1,
		keyword:'',
		pre_url:app.globalData.pre_url,
    };
  },

	onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.st = this.opt.st || 0;
		var that = this;
		this.getdata();
	},
	onPullDownRefresh: function () {
		this.getdata();
	},
	onReachBottom: function () {
		if (!this.nodata && !this.nomore) {
		  this.pagenum = this.pagenum + 1;
		  this.getdata(true);
		}
	},
  methods: {
    getdata: function (loadmore) {
		if(!loadmore){
			this.pagenum = 1;
			this.datalist = [];
		}
		var that = this;
		var st = that.st;
		var pagenum = that.pagenum;
		that.loading = true;
		that.nodata = false;
		that.nomore = false;
		app.post('ApiCerberuse/getlist', {st: st,pagenum: pagenum,keyword:that.keyword}, function (res) {
			that.loading = false;
			var data = res.data;
			if (pagenum == 1) {
				that.datalist = data;
				if (data.length == 0) {
					that.nodata = true;
				}
				that.loaded();
			}else{
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
    changetab: function (st) {
      this.st = st;
      uni.pageScrollTo({
        scrollTop: 0,
        duration: 0
      });
      this.getdata();
    },
	searchConfirm:function(e){
		this.keyword = e.detail.value;
		this.getdata(false);
	},
	searchChange: function (e) {
	  this.keyword = e.detail.value;
	},
  }
};
</script>
<style>
.topsearch{width:100%;padding:16rpx 10rpx;}
.topsearch .f1{height:60rpx;border-radius:30rpx;border:0;background-color:#fff;flex:1}
.topsearch .f1 .img{width:24rpx;height:24rpx;margin-left:10px}
.topsearch .f1 input{height:100%;flex:1;padding:0 20rpx;font-size:28rpx;color:#333;}
.topsearch .search-btn{display:flex;align-items:center;color:#5a5a5a;font-size:30rpx;width:60rpx;text-align:center;margin-left:20rpx}
.container{width: 100%;padding: 0 12px;}
.dp-product-item{height: auto; position: relative;overflow: hidden;  display:flex;flex-wrap:wrap}
.dp-product-item .item{display: inline-block;position: relative;margin-bottom: 12rpx;background: #fff;border-radius:10rpx;overflow:hidden}
.dp-product-item .product-pic {width: 100%;height:0;overflow:hidden;background: #ffffff;padding-bottom: 100%;position: relative;}
.dp-product-item .product-pic .image{position:absolute;top:0;left:0;width: 100%;height:auto}
.dp-product-item .product-pic .saleimg{ position: absolute;width: 60px;height: auto; top: -3px; left:-3px;}
.dp-product-item .product-info {padding:20rpx 20rpx;position: relative;}
.dp-product-item .product-info .p1 {color:#323232;font-weight:bold;font-size:28rpx;line-height:36rpx;margin-bottom:10rpx;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:2;overflow:hidden;height:72rpx}
.dp-product-item .product-info .p2{display:flex;align-items:center;overflow:hidden;padding:2px 0}
.dp-product-item .product-info .p2-1{flex-grow:1;flex-shrink:1;height:40rpx;line-height:40rpx;overflow:hidden;white-space: nowrap}
.dp-product-item .product-info .p2-1 .t1{font-size:36rpx;}
.dp-product-item .product-info .p2-1 .t2 {margin-left:10rpx;font-size:24rpx;color: #aaa;text-decoration: line-through;/*letter-spacing:-1px*/}
.dp-product-item .product-info .p2-1 .t3 {margin-left:10rpx;font-size:22rpx;color: #999;}
.dp-product-item .product-info .p2-2{font-size:20rpx;height:40rpx;line-height:40rpx;text-align:right;padding-left:20rpx;color:#999}
.dp-product-item .product-info .p3{color:#999999;font-size:20rpx;margin-top:10rpx;justify-content: space-between;display:flex;}
.dp-product-item .product-info .p3 .p3-1{height:40rpx;line-height:40rpx;border:0 #FF3143 solid;border-radius:10rpx;color:#FF3143;padding:0 24rpx;font-size:24rpx}
.dp-product-item .product-info .p3 .p3-2{color:#999999;font-size:20rpx;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:1;}
.dp-product-item .product-info .p4{width:48rpx;height:48rpx;border-radius:50%;position:absolute;display:relative;bottom:10rpx;right:20rpx;text-align:center;}
.dp-product-item .product-info .p4 .icon_gouwuche{font-size:28rpx;height:48rpx;line-height:48rpx}
.dp-product-item .product-info .p4 .img{width:100%;height:100%}

</style>