<template>
<view>
	<block v-if="isload">
		<view class="container">
			<view class="topsearch flex-y-center">
				<view class="f1 flex-y-center">
					<image class="img" :src="pre_url+'/static/img/search_ico.png'"></image>
					<input :value="keyword" placeholder="输入商品名称" placeholder-style="font-size:24rpx;color:#C2C2C2" @confirm="searchConfirm" @input="searchChange"></input>
				</view>
			</view>
			<view class="datalist flex" >
				<block v-for="(item, index) in datalist" :key="index">
				<view @tap="goto" :data-url="'/activity/collage/product?id='+item.id" class="collage-product">
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
								<view class="x1" v-if="!item.collage_type">{{item.teamnum}}人团</view>
								<view class="x2" :style="{backgroundColor:t('color1')}">去拼团</view>
							</view>
						</view>
					</view>
				</view>
				</block>
			</view>
			<nomore v-if="nomore"></nomore>
			<nodata v-if="nodata"></nodata>
		</view>
		<button class="covermy" @tap="goto" data-url="/activity/collage/orderlist">我的拼团</button>
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
			keyword: '',
			bid:'',
			pics: [],
      pagenum: 1,
      st: '',
      datalist: [],
      nomore: false,
			nodata:false,
			linktype:'product',
			pre_url:app.globalData.pre_url,
    };
  },
  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.bid = this.opt.bid || '';
		this.cid = this.opt.cid || '';
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
			var keyword = that.keyword;
			that.loading = true;
			that.nodata = false;
      that.nomore = false;
      app.post('ApiCollage/prolist', {cid:that.cid,bid:that.bid,keyword: keyword,st: st,pagenum: pagenum}, function (res) {
				that.loading = false;
        var data = res.data;
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
		searchChange: function (e) {
		  this.keyword = e.detail.value;
		},
		searchConfirm: function (e) {
		  var that = this;
		  var keyword = e.detail.value;
		  that.keyword = keyword;
		  that.getdata();
		}
	}
}
</script>
<style>
.container{background:#f4f4f4}

.topsearch{width:94%;margin:16rpx 3%;}
.topsearch .f1{height:60rpx;border-radius:30rpx;border:0;background-color:#fff;flex:1}
.topsearch .f1 .img{width:24rpx;height:24rpx;margin-left:10px}
.topsearch .f1 input{height:100%;flex:1;padding:0 20rpx;font-size:28rpx;color:#333;}


.swiper {width:94%;margin:0 3%;height: 350rpx;margin-top: 20rpx;border-radius:20rpx;overflow:hidden}
.swiper image {width: 100%;overflow: hidden;}

.category{width:94%;margin:0 3%;padding-top: 10px;padding-bottom: 10px;flex-direction:row;white-space: nowrap; display:flex;}
.category .item{width: 150rpx;display: inline-block; text-align: center;}
.category .item image{width: 80rpx;height: 80rpx;margin: 0 auto;border-radius: 50%;}
.category .item .t1{display: block;color: #666;}

.datalist{width:96%;margin:0 3%; flex-wrap: wrap;}
.collage-product { background: #fff; width: 47%;margin-right:20rpx;padding:20rpx 20rpx;margin-top: 20rpx;border-radius:20rpx}
.collage-product .product-pic { background: #ffffff;overflow:hidden; position: relative;}
.collage-product .product-pic .desc{ display: flex; width: 100%; flex-wrap: wrap; position: absolute; bottom: 0; background: #FF3143; opacity: 0.7; color:#fff;font-size: 20rpx;height:80rpx; padding:5rpx 10rpx}
.collage-product .product-pic image{width: 100%;height:180rpx;}
.collage-product .product-info {padding: 5rpx 10rpx;flex:1}
.collage-product .product-info .p1 {color:#222;font-weight:bold;font-size:28rpx;line-height:36rpx;margin-bottom:10rpx;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:2;overflow:hidden;height:72rpx}
.collage-product .product-info .p2{font-size: 32rpx;height:40rpx;line-height: 40rpx; margin-bottom: 10rpx; }
.collage-product .product-info .p2 .t1{color: #FF3143;font-size: 36rpx;font-weight: bold; }
.collage-product .product-info .p2 .t2 {margin-left: 10rpx;font-size: 26rpx;color: #888;text-decoration: line-through;}

.collage-product .product-info .p3{font-size: 24rpx;line-height:50rpx;overflow:hidden;}
.collage-product .product-info .p3 .t1{color:#aaa;font-size:24rpx;flex:1}
.collage-product .product-info .p3 .t2{height: 50rpx;line-height: 50rpx;overflow: hidden;border: 1px #FF3143 solid;border-radius:10rpx;width: 280rpx;display: flex;justify-content: space-between;text-align: center;}
.collage-product .product-info .p3 .t2 .x1{width: 50%;}
.collage-product .product-info .p3 .t2 .x2{background: #FF3143;color:#fff;width: 50%;}
.collage-product .product-info .p3 .team_text{ padding:0rpx 10rpx; margin-right:10rpx;border-radius: 4rpx; color: #FF3143;  background:#FFDED9 ; font-size: 20rpx; display: inline-block;}
.covermy{position:absolute;z-index:99999;cursor:pointer;display:flex;flex-direction:column;align-items:center;justify-content:center;overflow:hidden;z-index:9999;top:260rpx;right:0;color:#fff;background-color:rgba(17,17,17,0.3);width:140rpx;height:60rpx;font-size:26rpx;border-radius:30rpx 0px 0px 30rpx;}


</style>