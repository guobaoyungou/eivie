<template>
<view class="container">
	<view v-for="(item, index) in datalist" :key="index" class="item">
		<view :data-url="'/pages/shop/product?id=' + item.proid" @tap="goto" class="product-item2">
			<view class="product-pic">
				<image :src="item.product.pic" mode="widthFix"></image>
			</view> 
			<view class="product-info">
				<view class="p1">{{item.product.name}}</view>
				<view class="p2">
					<text class="t1" :style="{color:t('color1')}"><text style="font-size:22rpx">￥</text>{{item.product.sell_price}}</text>
				</view>
			</view>
		</view>
		<view class="foot">
			<text class="flex1">下单时间：{{dateFormat(item.product.createtime)}}</text>
		</view>
	</view>
	

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
			opt:{},
			loading:false,
      isload: false,
			menuindex:-1,
      datalist: [],
			nodata:false,
			tid:0
    };
  },

  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.tid = this.opt.tid || 0;
		this.getdata();
  },
	onPullDownRefresh: function () {
		this.getdata();
	},
  methods: {
    getdata: function () {
      var that = this;
			that.loading = true;
      app.post('ApiJipinLog/products', {tid:that.tid}, function (res) {
				that.loading = false;
				if (res.status == 1) {
					var data = res.data;
					that.datalist = data;
					if (data.length == 0) {
						that.nodata = true;
					}
					that.loaded();
        } else {
          if (res.msg) {
            app.alert(res.msg, function() {
              if (res.url) app.goto(res.url);
            });
          } else if (res.url) {
            app.goto(res.url);
          } else {
            app.alert('您无查看权限');
          }
        }
      });
    }
  }
};
</script>
<style>
.item{ width:94%;margin:0 3%;padding:0 20rpx;background:#fff;margin-top:20rpx;border-radius:20rpx}
.product-item2 {display:flex;padding: 20rpx 0;border-bottom:1px solid #E6E6E6;}
.product-item2 .product-pic {width: 140rpx;height: 140rpx; background: #ffffff;overflow:hidden}
.product-item2 .product-pic image{width: 100%;height:100%;}
.product-item2 .product-info {flex:1;padding: 5rpx 10rpx;}
.product-item2 .product-info .p1 {word-break: break-all;text-overflow: ellipsis;overflow: hidden;display: block;height: 80rpx;line-height: 40rpx;font-size: 30rpx;color:#111111}
.product-item2 .product-info .p2{font-size: 32rpx;height:40rpx;line-height: 40rpx}
.product-item2 .product-info .p2 .t2 {margin-left: 10rpx;font-size: 26rpx;color: #888;text-decoration: line-through;}
.product-item2 .product-info .p3{font-size: 24rpx;height:50rpx;line-height:50rpx;overflow:hidden}
.product-item2 .product-info .p3 .t1{color:#aaa;font-size:24rpx}
.product-item2 .product-info .p3 .t2{color:#888;font-size:24rpx;}
.foot{ display:flex;align-items:center;width:100%;height:100rpx;line-height:100rpx;color:#999999;font-size:24rpx;}
.foot .btn{ padding:2rpx 10rpx;height:50rpx;line-height:50rpx;color:#FF4C4C}
</style>