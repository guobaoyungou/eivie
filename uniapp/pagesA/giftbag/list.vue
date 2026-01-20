<template>
<view>
	<block v-if="isload">
		<view class="container">
			<view class="datalist">
				<block v-for="(item, index) in datalist" :key="index">
				<view :data-url="'detail?id=' + item.id+'&gbid=' + gbid+'&bid='+bid" @tap="goto" class="collage-product">
					<view class="product-pic">
						<image :src="item.pic" mode="widthFix"></image>
					</view> 
					<view class="product-info">
						<view class="p1">{{item.name}}</view>
						<!-- <view class="p2">
							<view class="p2-1">
								<text class="t1" :style="{color:t('color1')}"><text style="font-size:24rpx">￥</text>{{item.sell_price}}</text>
							</view>
						</view> -->
						<view class="p3" style="position: relative;bottom: 0;">
              <text class="t1" :style="{color:t('color1'),fontSize:'28rpx'}"><text style="font-size:24rpx">￥</text>{{item.sell_price}}</text>
							<!-- <view class="t1">已售<text style="font-size:32rpx;color:#f40;padding:0 2rpx;">{{item.sales}}</text>件</view> -->
							<view class="t2" :style="{borderColor:t('color1'),color:t('color1'),position:'absolute',right:'0'}">
								<text class="x2" :style="{backgroundColor:t('color1')}">详情</text>
							</view>
						</view>
             <view v-if="item.shortdesc" class="p1" style="margin-top: 10rpx;">{{item.shortdesc}}</view>
					</view>
				</view>
				</block>
			</view>
			<nomore v-if="nomore"></nomore>
			<nodata v-if="nodata"></nodata>
		</view>
    <view style="width:100%;height:110rpx;box-sizing:content-box" class="notabbarbot"></view>
    <view class="bottombar flex-row" :class="menuindex>-1?'tabbarbot':'notabbarbot'">
    	<!-- <view @tap="goto" data-url="/pages/index/index" style="text-align: center;line-height: 100%;width: 50%;">
        返回首页
      </view> -->
    	<view class="tobuy" :style="{background:t('color1'),margin:'0 auto'}" @tap="tobuy" ><text>立即领取</text></view>
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
      st: '',
      datalist: [],
      nomore: false,
			nodata:false,
      bid:0,
      gbid:0
    };
  },
  onLoad: function (opt) {
    var that = this;
    var opt  = app.getopts(opt);
    if(opt && opt.bid){
      that.bid = opt.bid;
    }
    if(opt && opt.gbid){
      that.gbid = opt.gbid;
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
      var st = that.st;
			that.loading = true;
			that.nodata = false;
      that.nomore = false;
      app.post('ApiGiftBag/list', {st: st,pagenum: pagenum,bid:that.bid,gbid:that.gbid}, function (res) {
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
		},
    changetab: function (e) {
      var st = e.currentTarget.dataset.st;
      this.pagenum = 1;
			this.st = st;
      this.datalist = [];
      uni.pageScrollTo({
        scrollTop: 0,
        duration: 0
      });
      this.getdata();
    },
    tobuy: function (e) {
      var that = this;
      var bid  = that.bid;
      var gbid  = that.gbid;
      app.goto('buy?gbid=' + gbid + '&num=1&bid='+bid);
    },
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
.collage-product .product-info .p3 .t2{height: 50rpx;line-height: 50rpx;overflow: hidden;border: 1px #FF3143 solid;border-radius:10rpx;}
.collage-product .product-info .p3 .t2 .x1{padding: 10rpx 24rpx;}
.collage-product .product-info .p3 .t2 .x2{padding: 14rpx 24rpx;background: #FF3143;color:#fff;}

.covermy{position:absolute;z-index:99999;cursor:pointer;display:flex;flex-direction:column;align-items:center;justify-content:center;overflow:hidden;z-index:9999;top:260rpx;right:0;color:#fff;background-color:rgba(17,17,17,0.3);width:140rpx;height:60rpx;font-size:26rpx;border-radius:30rpx 0px 0px 30rpx;}
.bottombar{ width: 100%; position: fixed;bottom: 0px; left: 0px; background: #fff;height:110rpx;align-items:center;box-sizing:content-box}
.bottombar .cart{width: 50%;font-size:26rpx;color:#707070}
.bottombar .cart .img{ width:50rpx;height:50rpx}
.bottombar .cart .t1{font-size:24rpx;color:#222222;height:30rpx;line-height:30rpx;margin-top:6rpx}
.bottombar .tobuy{ width:50%; height: 90rpx;border-radius:8rpx;color: #fff; background: #df2e24; font-size:28rpx;display:flex;flex-direction:column;align-items:center;justify-content:center;border-radius:45rpx;margin-right:16rpx;padding-right:20rpx}

</style>