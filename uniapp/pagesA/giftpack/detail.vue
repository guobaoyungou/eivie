<template>
<view class="container">
	<block v-if="isload">

		<view id="scroll_view_tab0">
			<view class="swiper-container">
				<swiper class="swiper" :indicator-dots="false" :autoplay="true" :interval="5000" @change="swiperChange" :current="current"  :style="{ height: swiperHeight + 'px' }">
					<block v-if="product.pic">
						<swiper-item class="swiper-item">
							<view class="swiper-item-view"><image class="img" :src="product.pic" mode="widthFix" @load="loadImg"/></view>
						</swiper-item>
					</block>
				</swiper>
				<view class="imageCount">{{current+1}}</view>
			</view>
			<view class="collage_title">
				<view class="f1">
					<view class="t1">
						<view class="x1">￥</view>
						<view class="x2">{{product.sell_price}}</view>
					</view>
				</view>
			</view>

			<view class="header"> 
				<view class="title">
					<view class="lef">
						<text>{{product.name}}</text>
					</view>
					<view class="t2" style="font-size: 26rpx;color: #777777;">
						<text>已销售{{product.sales}}</text>
					</view>
				</view>
   
			</view>
		</view>

		<view v-if="pagecontent" id="scroll_view_tab2">
			<view class="detail_title"><view class="t1"></view><view class="t2"></view><view class="t0">礼包描述</view><view class="t2"></view><view class="t1"></view></view>
			<view class="detail">
				<parse :content="pagecontent" ></parse>
			</view>
		</view>
    
		 <view style="width:100%;height:110rpx;box-sizing:content-box" class="notabbarbot"></view>
		<view class="bottombar flex-row" :class="menuindex>-1?'tabbarbot':'notabbarbot'"> 
			<!--<view @tap="goto" data-url="/pages/index/index" style="text-align: center;line-height: 100%;width: 50%;">
        返回首页
      </view>0-->
			 <view class="tobuy" :style="{background:t('color1'),margin:'0 auto'}" @tap="tobuy" ><text>立即购买</text></view>
		</view> 

		<view class="posterDialog" v-if="showposter">
			<view class="main">
				<view class="close" @tap="posterDialogClose"><image class="img" :src="pre_url+'/static/img/close.png'"/></view>
				<view class="content">
					<image class="img" :src="posterpic" mode="widthFix" @tap="previewImage" :data-url="posterpic"></image>
				</view>
			</view>
		</view>
	</block>
	<loading v-if="loading"></loading>
	<dp-tabbar :opt="opt" @getmenuindex="getmenuindex"></dp-tabbar>
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
			indexurl:app.globalData.indexurl,
      current: 0,
      pagecontent: "",
      
      product:{},
      bid:0,
      showposter:false,
      giftid:0,
			swiperHeight: '',
			pre_url: app.globalData.pre_url,
    };
  },
  onLoad: function (opt) {
		var that = this;
		var opt  = app.getopts(opt);
		if(opt && opt.bid){
		  that.bid = opt.bid;
		}
    if(opt && opt.id){
      that.giftid = opt.id;
    }
		that.opt = opt;
		that.getdata();
  },
	onPullDownRefresh: function () {
		this.getdata();
	},
  methods: {
		getdata: function () {
			var that = this;
			var id = that.opt.id;
			that.loading = true;
			app.post('ApiGiftPack/detail', {bid:that.bid,giftid:that.giftid}, function (res) {
				that.loading = false;
				if (res.status == 1) {
          if(res.product){
            var pagecontent = res.product.content;
            that.pagecontent = pagecontent;
          }
					that.product = res.product;
					uni.setNavigationBarTitle({
						title: res.product.name
					});
					that.loaded({title:res.product.name,pic:res.product.img});
				}else{
					app.alert(res.msg);
					return;
        }
			});
		},
    swiperChange: function (e) {
      var that = this;
      that.current = e.detail.current;
    },

    tobuy: function (e) {
      var that = this;
      var giftid = that.product.id;
      var bid  = that.bid;
			app.post('ApiGiftPack/createorder', {bid:that.bid,giftid:that.giftid}, function (res) {
				that.loading = false;
				if (res.status == 1) {
			    if(res.payorderid) app.goto('/pagesExt/pay/pay?id=' + res.payorderid,'redirectTo');
				}else{
					app.alert(res.msg);
					return;
			  }
			});
      //app.goto('buy?proid=' + proid + '&num=1&bid='+bid);
    },
    
    shareClick: function () {
      this.sharetypevisible = true;
    },
    handleClickMask: function () {
      this.sharetypevisible = false;
    },
    showPoster: function () {
      var that = this;
      that.showposter = true;
      that.sharetypevisible = false;
      app.showLoading('努力生成中');
			app.post('ApiGiftBag/getposter', {proid: that.product.id}, function (data) {
				app.showLoading(false);
        if (data.status == 0) {
          app.alert(data.msg);
        } else {
          that.posterpic = data.poster;
        }
      });
    },
    posterDialogClose: function () {
      this.showposter = false;;
    },
		showgg1Dialog:function(){
			this.$refs.gg1Dialog.open();
		},
		closegg1Dialog:function(){
			this.$refs.gg1Dialog.close();
		},
		showgg2Dialog:function(){
			this.$refs.gg2Dialog.open();
		},
		closegg2Dialog:function(){
			this.$refs.gg2Dialog.close();
		},
		loadImg() {
			this.getCurrentSwiperHeight('.img');
		},
		swiperChange: function (e) {
			var that = this;
			that.current = e.detail.current
			
			//动态设置swiper的高度，使用nextTick延时设置
			this.$nextTick(() => {
			  this.getCurrentSwiperHeight('.img');
			});
		},
		// 动态获取内容高度
		getCurrentSwiperHeight(element) {
		    let query = uni.createSelectorQuery().in(this);
		    query.selectAll(element).boundingClientRect();
				var imgList = this.product.pic;
		    query.exec((res) => {
		      // 切换到其他页面swiper的change事件仍会触发，这时获取的高度会是0，会导致回到使用swiper组件的页面不显示了
		      if (imgList.length && res[0][this.current].height) {
		        this.swiperHeight = res[0][this.current].height;
		      }
		    });	
		},
	}
}
</script>
<style>
page {position: relative;width: 100%;height: 100%;}
.container{height:100%}

.swiper-container{position:relative}
.swiper {width: 100%;height: 750rpx;overflow: hidden;}
.swiper-item-view{width: 100%;height: 750rpx;}
.swiper .img {width: 100%;height: 750rpx;overflow: hidden;}

.imageCount {width:100rpx;height:50rpx;background-color: rgba(0, 0, 0, 0.3);border-radius:40rpx;line-height:50rpx;color:#fff;text-align:center;font-size:26rpx;position:absolute;right:13px;bottom:20rpx;}

.header {width: 100%;padding: 0 3%;background: #fff;}
.header .title {padding-bottom:20rpx;line-height:70rpx;font-size:32rpx;display:flex;}
.header .title .lef{display:flex;flex-direction:column;justify-content: center;flex:1;color:#222222;font-weight:bold}
.header .title .lef .t2{ font-size:26rpx;color:#999;padding-top:10rpx;font-weight:normal}
.header .title .share{width:88rpx;height:88rpx;padding-left:20rpx;border-left:0 solid #f5f5f5;text-align:center;font-size:24rpx;color:#222;display:flex;flex-direction:column;align-items:center}
.header .title .share image{width:32rpx;height:32rpx;margin-bottom:4rpx}

.header .price{height: 86rpx;overflow: hidden;line-height: 86rpx;border-top: 1px solid #eee;}
.header .price .t1 .x1{ color: #e94745; font-size: 34rpx;}
.header .price .t1 .x2{ color: #939393; margin-left: 10rpx; text-decoration: line-through;font-size:24rpx}
.header .price .t2{color: #aaa; font-size: 24rpx;}

.header .sales_stock{display:flex;justify-content:space-between;height:60rpx;line-height:60rpx;margin-top:30rpx;font-size:24rpx;color:#777777}

.detail{min-height:200rpx;background: #fff; padding:30rpx}

.detail_title{width:100%;display:flex;align-items:center;justify-content:center;margin-top:60rpx;margin-bottom:30rpx}
.detail_title .t0{font-size:28rpx;font-weight:bold;color:#222222;margin:0 20rpx}
.detail_title .t1{width:12rpx;height:12rpx;background:rgba(253, 74, 70, 0.2);transform:rotate(45deg);margin:0 4rpx;margin-top:6rpx}
.detail_title .t2{width:18rpx;height:18rpx;background:rgba(253, 74, 70, 0.4);transform:rotate(45deg);margin:0 4rpx}

.bottombar{ width: 100%; position: fixed;bottom: 0px; left: 0px; background: #fff;height:110rpx;align-items:center;box-sizing:content-box}
.bottombar .cart{width: 50%;font-size:26rpx;color:#707070}
.bottombar .cart .img{ width:50rpx;height:50rpx}
.bottombar .cart .t1{font-size:24rpx;color:#222222;height:30rpx;line-height:30rpx;margin-top:6rpx}
.bottombar .tobuy{ width:60%; height: 86rpx;border-radius:10rpx;color: #fff; background: #df2e24; font-size:28rpx;display:flex;flex-direction:column;align-items:center;justify-content:center;border-radius:45rpx;margin-right:16rpx;padding-right:20rpx}

.collage_title{width:100%;height:90rpx;background: #FFF;display:flex;align-items:center;padding:0 3%}
.collage_title .f1{flex:1;display:flex;flex-direction:column;}
.collage_title .f1 .t1{display:flex;align-items:center;height:60rpx;line-height:60rpx}
.collage_title .f1 .t1 .x1{font-size:28rpx;color:#FE6748}
.collage_title .f1 .t1 .x2{font-size:48rpx;color:#FE6748;padding-right:20rpx}
.collage_title .f1 .t1 .x3{font-size:24rpx;font-weight:bold;height:40rpx;line-height:40rpx;padding:0 20rpx;border-radius:20rpx;background:#fff;color:#ED533A}
.collage_title .f1 .t2{color:rgba(255,255,255,0.6);font-size:20rpx;}
.collage_title .f2{color:#fff;font-size:28rpx;}

.toptabbar_tab{display:flex;width:100%;height:90rpx;background: #fff;top:var(--window-top);z-index:11;position:fixed;border-bottom:1px solid #f3f3f3}
.toptabbar_tab .item{flex:1;font-size:28rpx; text-align:center; color:#666; height: 90rpx; line-height: 90rpx;overflow: hidden;position:relative}
.toptabbar_tab .item .after{display:none;position:absolute;left:50%;margin-left:-16rpx;bottom:10rpx;height:3px;border-radius:1.5px;width:32rpx}
.toptabbar_tab .on{color: #323233;}
.toptabbar_tab .on .after{display:block}

.prolist{width: 100%;height:auto;padding: 8rpx 20rpx;}

.dp-collage-item{height: auto; position: relative;overflow: hidden; padding: 0px; display:flex;flex-wrap:wrap}
.dp-collage-item .item{display: inline-block;position: relative;margin-bottom: 12rpx;background: #fff;border-radius:10rpx;overflow:hidden}
.dp-collage-item .product-pic {width: 100%;height:0;overflow:hidden;background: #ffffff;padding-bottom: 100%;position: relative;}
.dp-collage-item .product-pic .image{position:absolute;top:0;left:0;width: 100%;height:auto}
.dp-collage-item .product-pic .saleimg{ position: absolute;width: 60px;height: auto; top: -3px; left:-3px;}
.dp-collage-item .product-pic .tag{padding: 0 15rpx;line-height: 35rpx;display: inline-block;font-size: 24rpx;color: #fff;background: linear-gradient(to bottom right,#ff88c0,#ec3eda);position: absolute;left: 0;top: 0;border-radius: 0 0 10rpx 0}
.dp-collage-item .product-info {padding:20rpx 20rpx;position: relative;}
.dp-collage-item .product-info .p1 {color:#323232;font-weight:bold;font-size:28rpx;line-height:36rpx;margin-bottom:10rpx;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:2;overflow:hidden;height:72rpx}
.dp-collage-item .product-info .p2{display:flex;align-items:center;overflow:hidden;padding:2px 0}

.dp-collage-item .product-info .total{border-radius: 8rpx;border: 1rpx solid #FF3143;font-size: 24rpx;background: #ffeded;overflow: hidden;}
.dp-collage-item .product-info .total .num{color: #fff;background: #FF3143;padding: 3rpx 8rpx;}
.dp-collage-item .product-info .total .sales{color: #FF3143;padding: 3rpx 8rpx;}
.dp-collage-item .product-info .price{position: relative;margin-top: 15rpx;}
.dp-collage-item .product-info .price .text{color: #FF3143;font-weight: bold;font-size: 30rpx;}
.dp-collage-item .product-info .price .add{height: 50rpx;width: 50rpx;border-radius: 100rpx;background: #FF3143;}
.dp-collage-item .product-info .price .add image{height: 30rpx;width: 30rpx;display: block;}

</style>