<template>
<view>
	<block v-if="isload">
		<view class="container">
			<view class="datalist">
				<block v-for="(item, index) in datalist" :key="index">
				<view class="collage-product"  @tap="goto" :data-url="'/activity/shortvideo/detail?id=' + item.vid">
          <view class="product-pic">
          	<image :src="item.pic" mode="widthFix"></image>
          </view>
					<view class="product-info">
						<view class="p1">{{item.name}}</view>
            <view class="p2" style="display: flex;;color: #888;">
              <view  style="width: 100%;">次数：<text style="color: #000;">{{item.num}}</text></view>
              <view  style="width: 100%">进度：<text style="color: #000;">{{item.jindu}}%</text></view>
            </view>
					</view>
				</view>
				</block>
			</view>
			<nomore v-if="nomore"></nomore>
			<nodata v-if="nodata"></nodata>
		</view>
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
			menuindex:-1,
			pre_url:app.globalData.pre_url,

      pagenum: 1,
      datalist: [],
      nomore: false,
			nodata:false,
      mid:0,
    };
  },
  onLoad: function (opt) {
    var that = this;
    var opt  = app.getopts(opt);
    if(opt && opt.mid){
      that.mid = opt.mid;
    }
		that.opt = opt;
		that.getdata();
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
			var that = this;
			if(!loadmore){
				this.pagenum = 1;
				this.datalist = [];
			}
			var pagenum = that.pagenum;
			that.loading = true;
			that.nodata = false;
      that.nomore = false;
      app.post('ApiAgent/shortvideolog', {pagenum: pagenum,mid:that.mid}, function (res) {
				that.loading = false;
        if(res.status == 1){
          var data = res.datalist;
          if (pagenum == 1) {
          	that.pics = res.pics;
          	that.clist = res.clist;
            that.datalist = data;
            if (data.length == 0) {
              that.nodata = true;
            }
          }else{
            if (data.length == 0) {
              that.nomore = true;
            } else {
              var datalist = that.datalist;
              var newdata = datalist.concat(data);
              that.datalist = newdata;
            }
          }
          that.loaded();
        }else{
          app.alert(res.msg);
        }
        
      });
		}
	}
}
</script>
<style>
.container{background:#f4f4f4}
.swiper {width:94%;margin:0 3%;height: 350rpx;margin-top: 20rpx;border-radius:20rpx;overflow:hidden}
.swiper image {width: 100%;height: 350rpx;overflow: hidden;}

.category{width:94%;margin:0 3%;padding-top: 10px;padding-bottom: 10px;flex-direction:row;white-space: nowrap; display:flex;}
.category .item{width: 150rpx;display: inline-block; text-align: center;}
.category .item image{width: 80rpx;height: 80rpx;margin: 0 auto;border-radius: 50%;}
.category .item .t1{display: block;color: #666;}

.datalist{width:94%;margin:0 3%;}
.collage-product {display:flex;min-height:200rpx; background: #fff; padding:20rpx 20rpx;margin-top: 20rpx;border-radius:20rpx}
.collage-product .product-pic {width: 140rpx;height: 140rpx; background: #ffffff;overflow:hidden}
.collage-product .product-pic image{width: 100%;height:140rpx;}
.collage-product .product-info {padding: 5rpx 10rpx;flex:1}
.collage-product .product-info .p1 {color:#323232;font-weight:bold;font-size:28rpx;line-height:36rpx;margin-bottom:10rpx;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:2;overflow:hidden;height:72rpx}
.collage-product .product-info .p2{font-size: 32rpx;height:40rpx;line-height: 40rpx}
.collage-product .product-info .p2 .t1{color: #f40;}
.collage-product .product-info .p2 .t2 {margin-left: 10rpx;font-size: 26rpx;color: #888;text-decoration: line-through;}
.collage-product .product-info .p3{font-size: 24rpx;height:50rpx;line-height:50rpx;overflow:hidden;display:flex;}
.collage-product .product-info .p3 .t1{color:#aaa;font-size:24rpx;flex:1}
.collage-product .product-info .p3 .t2{height: 50rpx;line-height: 50rpx;overflow: hidden;border: 1px #FF3143 solid;border-radius:8rpx;}
.collage-product .product-info .p3 .t2 .x1{padding: 10rpx 24rpx;}
.collage-product .product-info .p3 .t2 .x2{padding: 14rpx 24rpx;background: #FF3143;color:#fff;}

.covermy{position:absolute;z-index:99999;cursor:pointer;display:flex;flex-direction:column;align-items:center;justify-content:center;overflow:hidden;z-index:9999;top:260rpx;right:0;color:#fff;background-color:rgba(17,17,17,0.3);width:140rpx;height:60rpx;font-size:26rpx;border-radius:30rpx 0px 0px 30rpx;}
</style>