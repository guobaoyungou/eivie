<template>
<view>
	<block v-if="isload">
		<view class="container">
			<swiper v-if="pics.length>0" class="swiper" :indicator-dots="pics[1]?true:false" autoplay="true" :interval="5000">
				<block v-for="(item, index) in pics" :key="index">
					<swiper-item class="swiper-item">
						<image :src="item" mode="widthFix"></image>
					</swiper-item>
				</block>
			</swiper>
			<scroll-view class="category" scroll-x="true" v-if="clist.length>0">
				<block v-for="(item, index) in clist" :key="index">
					<view class="item" @tap="changetab" :data-st="item.id">
						<image :src="item.pic"></image>
						<text class="t1">{{item.name}}</text>
					</view>
				</block>
				<view class="item" @tap="changetab" data-st>
					<image :src="pre_url+'/static/img/all.png'" style="border-radius:0"></image>
					<text class="t1">全部</text>
				</view>
			</scroll-view>
			<view class="datalist">
				<block v-for="(item, index) in datalist" :key="index">
				<view @tap="toDetail" :data-id="item.id" :data-teamid="item.teamid"  :data-type="item.collage_type"  class="collage-product">
					<view class="product-pic">
						<image :src="item.pic" mode="widthFix"></image>
					</view> 
					<view class="product-info">
						<view class="p1">{{item.name}}</view>
						<view class="p2">
							<view class="p2-1">
								<text class="t1" :style="{color:t('color1')}"><text style="font-size:24rpx">￥</text>{{item.sell_price}}</text>
								<text class="t2" v-if="item.market_price*1 > item.sell_price*1">￥{{item.market_price}}</text>
							</view>
						</view>
						<view class="p3">
							<view class="t1">已拼成<text style="font-size:32rpx;color:#f40;padding:0 2rpx;">{{item.sales}}</text>件</view>
							<view class="t2" :style="{borderColor:t('color1'),color:t('color1')}">
								<text class="x1" v-if="!item.collage_type">{{item.teamnum}}人团</text>
								<text class="x2" :style="{backgroundColor:t('color1')}">去拼团</text>
							</view>
						</view>
					</view>
				</view>
				</block>
			</view>
			<nomore v-if="nomore"></nomore>
			<nodata v-if="nodata"></nodata>
		</view>
		<button class="covermy" @tap="goto" data-url="orderlist">我的拼团</button>
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
			
			bid:'',
			pics: [],
      pagenum: 1,
      st: '',
      datalist: [],
      nomore: false,
			nodata:false,
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
      app.post('ApiCollage/index', {bid:that.bid,st: st,pagenum: pagenum}, function (res) {
				that.loading = false;
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
      });
		},
		toDetail:function(e){
			var type = e.currentTarget.dataset.type;
			var teamid = e.currentTarget.dataset.teamid;
			var id = e.currentTarget.dataset.id;
			if(!type){
				app.goto('product?id='+id);
			}else{
				if(teamid >0){
					app.goto('/pagesB/collage/jtteam?teamid='+teamid);
				}else{
					app.goto('product?id='+id);
				}
			}
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
.collage-product {display:flex;height:220rpx; background: #fff; padding:20rpx 20rpx;margin-top: 20rpx;border-radius:20rpx}
.collage-product .product-pic {width: 180rpx;height: 180rpx; background: #ffffff;overflow:hidden}
.collage-product .product-pic image{width: 100%;height:180rpx;}
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
</style>