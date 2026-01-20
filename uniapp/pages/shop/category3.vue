<template>
<view class="container">
	<block v-if="isload">
		<view class="view-show">
			<view @tap.stop="goto" :data-url="'/pages/shop/search?bid='+bid" class="search-container">
				<view class="search-box">
					<image class="img" :src="pre_url+'/static/img/search_ico.png'"></image>
					<view class="search-text">搜索感兴趣的商品</view>
				</view>
			</view>
			<view class="content" style="height:calc(100% - 94rpx);overflow:hidden;display:flex">
				<scroll-view class="nav_left" :scrollWithAnimation="animation" scroll-y="true" :class="menuindex>-1?'tabbarbot':'notabbarbot'">
					<block v-for="(item, index) in data" :key="index" >
					<view class="nav_left_items" :class="index===currentActiveIndex?'active':''" @tap="clickRootItem" :data-root-item-id="item.id" :data-root-item-index="index"><view class="before" :style="{background:t('color1')}"></view>{{item.name}}</view>
					</block>
				</scroll-view>
				<view class="nav_right">
					<view class="nav_right-content">
						<scroll-view @scroll="scroll" class="detail-list" :scrollIntoView="scrollToViewId" :scrollWithAnimation="animation" scroll-y="true" :class="menuindex>-1?'tabbarbot':'notabbarbot'">
							<view v-for="(detail, index) in clist" :key="index" class="classification-detail-item">
								<view class="head" :data-id="detail.id" :id="'detail-' + detail.id">
									<view class="txt">{{detail.name}}</view>
									<view class="show-all" @tap="gotoCatproductPage" :data-id="detail.id">查看全部<text class="iconfont iconjiantou"></text></view>
								</view>
								<view class="detail">
									<view v-for="(item, itemIndex) in detail.child" :key="itemIndex" @tap.stop="gotoCatproductPage" class="detail-item" :data-id="item.id" form-type="submit" :style="(itemIndex+1)%3===0?'margin-right: 0':''">
										<image class="img" :src="item.pic"></image>
										<view class="txt">{{item.name}}</view>
									</view>
								</view>
							</view>
							<nodata v-if="nodata"></nodata>
						</scroll-view>
					</view>
				</view>
			</view>
		</view>
	</block>
	<loading v-if="loading" loadstyle="left:62.5%"></loading>
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

      data: [],
      currentActiveIndex: 0,
      animation: true,
      clist: "",
			bid:'',
			nodata: false,
			pre_url:app.globalData.pre_url,
    };
  },

  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.bid = this.opt.bid ? this.opt.bid : '';
		this.getdata();
  },
	onPullDownRefresh: function () {
		this.getdata();
	},
  methods: {
		getdata: function () {
			var that = this;
			that.loading = true;
			app.get('ApiShop/category3', {bid:that.bid}, function (res) {
				that.loading = false;
				that.data = res.data;
				that.loaded();
				that.getdownclist3(res.data[0].id);
			});
		},
    getdownclist3: function (id) {
      var that = this;
			that.loading = true;
			that.nodata = false;
      app.post("ApiShop/getdownclist3", {id: id,bid:that.bid}, function (res) {
				that.loading = false;
        that.clist = res.data;
				if((that.clist).length == 0){
					that.nodata = true;
				}
      });
    },
    clickRootItem: function (t) {
      var e = t.currentTarget.dataset;
      this.currentActiveIndex = e.rootItemIndex;
      var id = e.rootItemId;
      this.getdownclist3(id);
    },
    gotoCatproductPage: function (t) {
      var e = t.currentTarget.dataset;
			if(this.bid){
				app.goto('/pages/shop/prolist?bid='+this.bid+'&cid2=' + e.id);
			}else{
				app.goto('/pages/shop/prolist?cid=' + e.id);
			}
    }
  }
};
</script>
<style>
page {position: relative;width: 100%;height: 100%;}
button {border: 0 solid!important;}
.container{height:100%}
.view-show{background-color: white;line-height: 1;width: 100%;height: 100%;}
.search-container {width: 100%;height: 94rpx;padding: 16rpx 23rpx 14rpx 23rpx;background-color: #fff;position: relative;overflow: hidden;border-bottom:1px solid #f5f5f5}
.search-box {display:flex;align-items:center;height:60rpx;border-radius:30rpx;border:0;background-color:#f7f7f7;flex:1}
.search-box .img{width:24rpx;height:24rpx;margin-right:10rpx;margin-left:30rpx}
.search-box .search-text {font-size:24rpx;color:#C2C2C2;width: 100%;}

.nav_left{width: 25%;height:100%;background: #ffffff;overflow-y:scroll;}
.nav_left .nav_left_items{line-height:50rpx;color:#666666;border-bottom:0px solid #E6E6E6;font-size:28rpx;position: relative;border-right:0 solid #E6E6E6;padding:25rpx 30rpx;}
.nav_left .nav_left_items.active{background: #fff;color:#222222;font-size:28rpx;font-weight:bold}
.nav_left .nav_left_items .before{display:none;position:absolute;top:50%;margin-top:-12rpx;left:10rpx;height:24rpx;border-radius:4rpx;width:8rpx}
.nav_left .nav_left_items.active .before{display:block}

.nav_right{width: 75%;height:100%;display:flex;flex-direction:column;background: #f6f6f6;box-sizing: border-box;padding:20rpx 20rpx 0 20rpx}
.nav_right-content{background: #ffffff;padding:20rpx;height:100%;position:relative}
.detail-list {height:100%;overflow:scroll}
.classification-detail-item {width: 100%;overflow: visible;background:#fff}
.classification-detail-item .head {height: 82rpx;width: 100%;display: flex;align-items:center;justify-content:space-between;}
.classification-detail-item .head .txt {color:#222222;font-weight:bold;font-size:28rpx;width: 75%;white-space: nowrap;overflow: hidden; text-overflow: ellipsis;}
.classification-detail-item .head .show-all {font-size: 22rpx;color:#949494;display:flex;align-items:center}
.classification-detail-item .detail {width:100%;display:flex;flex-wrap:wrap}
.classification-detail-item .detail .detail-item {width:160rpx;height: 160rpx;margin-bottom: 70rpx;}
.classification-detail-item .detail .detail-item .img {width: 112rpx;height: 112rpx;margin-left:24rpx}
.classification-detail-item .detail .detail-item .txt {color:#333;font-size: 28rpx;margin-top:20rpx;text-align: center;white-space: nowrap;word-break: break-all;overflow: hidden;display: -webkit-box;-webkit-line-clamp: 1;-webkit-box-orient: vertical;}

</style>