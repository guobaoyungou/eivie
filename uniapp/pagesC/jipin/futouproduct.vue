<template>
<view class="container">
	<view v-for="(item, index) in datalist" :key="index" class="item">
		<view  class="product-item2">
			<view class="product-pic" :data-url="'/pages/shop/product?id=' + item.id" @tap="goto">
				<image :src="item.pic" mode="widthFix"></image>
			</view> 
			<view class="product-info">
				<view class="p1">{{item.name}}</view>
				<view class="p2" style="display: flex;justify-content: space-between;">
					<text class="t1" :style="{color:t('color1')}"><text style="font-size:22rpx">￥</text>{{item.sell_price}}</text>
	
					<view class="f3" @tap.stop="setdefault" :data-id="item.id">
						<view class="flex-y-center">
							<view class="radio" :style="item.isdefault ? 'border:0;background:'+t('color1') : ''"><image class="radio-img" :src="pre_url+'/static/img/checkd.png'"/></view>
							<view class="mrtxt">{{item.isdefault ? '默认商品' : '设为默认'}}</view>
						</view>
					</view>

				</view>
				<view class="p3">
					<text class="t1" v-if="item.sales>0">已售<text style="font-size:24rpx;color:#f40;padding:0 2rpx;">{{item.sales}}</text>件</text>
				</view>
			</view>
		</view>
<!-- 		<view class="foot">
			<text class="flex1">所属商家：{{item.bname}}</text>
		</view> -->
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
			pre_url:app.globalData.pre_url,
			loading:false,
      isload: false,
			menuindex:-1,
      datalist: [],
			nodata:false,
			id:0
    };
  },

  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.id = this.opt.id || 0;
		this.getdata();
  },
	onPullDownRefresh: function () {
		this.getdata();
	},
  methods: {
    getdata: function () {
      var that = this;
			that.loading = true;
      app.post('ApiJipinLog/futouproduct', {id:that.id}, function (res) {
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
    },
		//选择默认商品
		setdefault: function (e) {
		  var that = this;
		  var fromPage = this.opt.fromPage;
			var proid = e.currentTarget.dataset.id;
		  app.post('ApiJipinLog/setdefault', {id:that.id,proid:proid}, function (data) {
		    if (fromPage) {
		      app.goback(true);
		    } else {
		      that.getdata();
		    }
		  });
		},
  }
};
</script>
<style>
.item{ width:94%;margin:0 3%;padding:0 20rpx;background:#fff;margin-top:20rpx;border-radius:20rpx}
.product-item2 {display:flex;padding: 20rpx 0;}
.product-item2 .product-pic {width: 166rpx;height: 166rpx; background: #ffffff;overflow:hidden}
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

.product-info .radio{flex-shrink:0;width: 36rpx;height: 36rpx;background: #FFFFFF;border: 3rpx solid #BFBFBF;border-radius: 50%;}
.product-info .radio .radio-img{width:100%;height:100%}
.product-info .mrtxt{color:#2B2B2B;font-size:26rpx;margin-left:10rpx}
.product-info .del{font-size:24rpx}
</style>