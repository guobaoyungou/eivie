<template>
<view class="container">
	<block v-if="isload">
		<view class="banner"><image :src="pic" mode="widthFix"></image></view>
		<block v-for="(item, index) in datalist" :key="index">
		<view :data-url="'product?id=' + item.id" @tap="goto" class="product-item2">
			<view class="product-pic">
					<image :src="item.pic" mode="widthFix"></image>
			</view> 
			<view class="product-info">
					<view class="p1">{{item.name}}</view>
					<view class="p2">
						<view v-for="(join, index2) in item.joinlist" :key="index2" class="t1">
							<image :src="join.headimg"></image>
						</view>
						<view class="t1" v-if="item.saleing>7">
							<image :src="pre_url+'/static/img/moreuser.png'"></image>
						</view>
						<view class="t2">{{item.saleing}}人正在参加</view>
					</view>
					<view class="p3">
							<view class="t1">
								<text class="x1"><text style="font-size:22rpx">最低￥</text>{{item.min_price}}</text>
								<text class="x2">￥{{item.sell_price}}</text>
							</view>
							<button class="t2" @tap="todetail" :data-id="item.id">去砍价</button>
					</view>
			</view>
		</view>
		</block>
		<nomore v-if="nomore" textcolor="#faa" linecolor="#f66"></nomore>
		<nodata v-if="nodata"></nodata>
		<button class="covermy" @tap="goto" data-url="orderlist">我的订单</button>
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
			
			bid:'',
      pagenum: 1,
      st: '',
      datalist: [],
      nomore: false,
      nodata: false,
			pic:'',
      pics: "",
			clist:[],
			pre_url: app.globalData.pre_url,
    };
  },
  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.bid = this.opt.bid || '';
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
			that.nomore = false;
			that.nodata = false;
      app.post('ApiKanjia/index', {bid:that.bid,pagenum: pagenum}, function (res) {
				that.loading = false;
        var data = res.data;
        var data = res.datalist;
        if (pagenum == 1) {
					that.pic = res.pic;
					that.pics = res.pics;
					that.clist = res.clist;
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
    }
  }
}
</script>
<style>
page{background:#F34343}
.container{background:#F34343}

.swiper {width: 100%;height: 375rpx;margin-bottom: 20rpx;}
.swiper image {width: 100%;height: 375rpx;overflow: hidden;}

.banner{width:100%;}
.banner image{width:100%;height:auto}

.category{width: 100%;padding-top: 10px;padding-bottom: 10px;flex-direction:row;white-space: nowrap; display:flex;}
.category .item{width: 150rpx;display: inline-block; text-align: center;}
.category .item image{width: 80rpx;height: 80rpx;margin: 0 auto;border-radius: 50%;}
.category .item .t1{display: block;color: #666;}

.product-item2 {display:flex;height: 240rpx; background: #fff; padding:30rpx 20rpx;margin: 0 20rpx;margin-bottom:20rpx;border-radius:20rpx}
.product-item2 .product-pic {width: 180rpx;height: 180rpx; background: #ffffff;overflow:hidden}
.product-item2 .product-pic image{width: 100%;height:180rpx;}
.product-item2 .product-info {width:570rpx;padding: 5rpx 10rpx;flex:1}
.product-item2 .product-info .p1 {word-break: break-all;text-overflow: ellipsis;overflow: hidden;display: block;height: 80rpx;line-height: 40rpx;font-size: 30rpx;color:#111111}
.product-item2 .product-info .p2{padding-left:10rpx;height:60rpx;display:flex;align-items:center}
.product-item2 .product-info .p2 .t1{margin-left: -10rpx;height:40rpx;}
.product-item2 .product-info .p2 .t1 image{width:40rpx;height:40rpx;border-radius: 50%;border:2px solid #fff;}
.product-item2 .product-info .p2 .t2{font-size:24rpx;color:#787878}
.product-item2 .product-info .p3{font-size: 32rpx;height:40rpx;line-height: 40rpx;display:flex;align-items:center}
.product-item2 .product-info .p3 .t1{flex:auto}
.product-item2 .product-info .p3 .t1 .x1{color: #f40;}
.product-item2 .product-info .p3 .t1 .x2{margin-left: 10rpx;font-size: 26rpx;color: #888;text-decoration: line-through;}
.product-item2 .product-info .p3 .t2{padding:0 16rpx;font-size: 22rpx;height:44rpx;line-height:44rpx;overflow: hidden;color:#fff;background:#ee4f4f;border:0;border-radius:20rpx;}
.product-item2 .product-info .p3 button:after{border:0}

.covermy{position:absolute;z-index:99999;cursor:pointer;display:flex;flex-direction:column;align-items:center;justify-content:center;overflow:hidden;z-index:9999;top:350rpx;right:0;color:#fff;background-color:rgba(17,17,17,0.3);width:140rpx;height:60rpx;font-size:26rpx;border-radius:30rpx 0px 0px 30rpx;}

.nomore-footer-tips{background:#F34343;color:#fff}
</style>