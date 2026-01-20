<template>
<view class="container">
	<block v-if="isload">
		<view class="topimg" :style="{background:t('color1')}">
			<image class="img" :src="bgurl"/>
		</view>
		<view class="topinfo">
			<view class="flex">
				<view class="myscore"><text class="t1" :style="{color:t('color1')}">{{score}}</text><text class="t2">我的{{t('积分')}}</text></view>
				<view class="scorelog" @tap="goto" data-url="/pagesExt/my/scorelog" :style="{color:t('color1'),background:'rgba('+t('color1rgb')+',0.2)'}">{{t('积分')}}明细<text class="iconfont iconjiantou"></text></view>
				<view class="orderlog" @tap="goto" data-url="orderlist">兑换记录<text class="iconfont iconjiantou"></text></view>
			</view>
			<view class="search-container">
				<view class="search-box">
					<image class="img" :src="pre_url+'/static/img/search_ico.png'"/>
					<input :value="keyword" placeholder="商品搜索..." placeholder-style="font-size:24rpx;color:#C2C2C2" type="text" confirm-type="search" @confirm="search"></input>
				</view>
			</view>
		</view>
		<block v-if="clist.length > 0">
		<view class="navbox">
			<block v-for="(item, index) in clist" :key="index">
			<view style="cursor:pointer" @tap="goto" :data-url="item.url?item.url:'prolist?bid='+bid+'&cid='+item.id" class="nav_li">
				<image class="img" :src="item.pic"/>
				<view class="txt">{{item.name}}</view>
			</view>
			</block>
			<view class="nav_li" @tap="goto" :data-url="'prolist?bid='+bid">
				<image :src="pre_url+'/static/img/all.png'" class="img"/>
				<view class="txt">全部</view>
			</view>
		</view>
		</block>

		<view id="datalist">
			<block v-for="(item, index) in datalist" :key="index">
			<view @tap="goto" :data-url="'product?id=' + item.id" class="product-item">
				<view class="itemcontent">
					<view class="product-pic">
						<image :src="item.pic" class="img"/>
					</view> 
					<view class="product-info">
						<view class="p1">{{item.name}}</view>
						<view class="p2"><block v-if="item.sell_price>0">价值{{item.sell_price}}元</block></view>
						<view class="p3">
							<view class="t1 flex">
								<view class="x1" :style="{color:t('color1')}">
									<text style="font-size:13px">
										<block v-if="item.score_price>0">{{item.score_price}}{{t('积分')}}</block>
										<block v-if="item.money_price>0"><block v-if="item.score_price>0">+</block>{{item.money_price}}元</block>
									</text>
								</view>
							</view>
						</view>
					</view>
				</view>
				<view class="itembottom">
					<view class="f1">已兑换<text :style="{color:t('color1')}"> {{item.sales}} </text>件</view>
					<button class="f2" :style="{background:t('color1')}">立即兑换</button>
				</view>
			</view>
			</block>
			<nomore v-if="nomore"></nomore>
			<nodata v-if="nodata"></nodata>
		</view>
	</block>
	<loading v-if="loading"></loading>
	<dp-tabbar :opt="opt"></dp-tabbar>
	<popmsg ref="popmsg"></popmsg>
	<!-- app全屏广告 -->
	<!-- #ifdef APP-PLUS -->
	<ad-fullscreen-video ref="adFullscreenVideo" :adpid="adpidnum" :preload="false" :loadnext="false" 
		:disabled="true" v-slot:default="{loading, error}" @load="onadload" @close="onadclose" @error="onaderror">
	</ad-fullscreen-video>
	<view class="ad-error" v-if="errMsg">{{errMsg}}</view>
	<!-- #endif -->
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
			score:0,
			pics:[],
			clist:[],
      datalist: [],
      pagenum: 1,
      keyword: '',
      nodata: false,
      nomore: false,
      sclist: "",
			bid:0,
			bgurl:'',
			adpidnum:'',
			errMsg:'',
    };
  },
	// #ifdef APP-PLUS
	onReady() {
		this.$refs.adFullscreenVideo.load();
	},
	// #endif
	onShow:function(){
    if(app.globalData.platform=='wx' && app.globalData.hide_home_button==1){
      uni.hideHomeButton();
    }
	},
  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.bid = this.opt.bid || 0;
		this.getdata();
  },
	onPullDownRefresh: function () {
		this.getdata();
	},
  onReachBottom: function () {
    if (!this.nodata && !this.nomore) {
      this.pagenum = this.pagenum + 1;
      this.getdatalist(true);
    }
  },
  methods: {
		onadload(e) {
			// 广告数据加载成功
		},
		onadclose(e) {
			let that = this;
			const detail = e.detail
			// 用户点击了【关闭广告】按钮
			if (detail && detail.isEnded) {
				// 正常播放结束
				app.post('ApiScoreshop/givescore',{adpid:that.adpidnum},function (res) {
					if(res.status == 1){
						uni.showToast({
							icon:'none',
							title:res.msg
						})
					}else{
						uni.showToast({
							icon:'none',
							title:res.msg
						})
					}
				})
			} else {
				// 播放中途退出
				console.log("onClose " + detail.isEnded);
			}
			this.$refs.adFullscreenVideo.load();
		},
		onaderror(e) {
			// 广告加载失败
			this.errMsg = JSON.stringify(e.detail);
		},
    getdata: function () {
      var that = this;
			that.loading = true;
      app.post('ApiScoreshop/index', {bid:that.bid}, function (res) {
				uni.stopPullDownRefresh();
				that.loading = false;
				that.isload = true;
				uni.setNavigationBarTitle({
					title: that.t('积分') + '商城'
				});
        that.clist = res.clist;
				that.pics = res.pics;
				that.score = res.score;
				that.bgurl = res.bgurl;
				// 是否开启app全屏广告
				// #ifdef APP-PLUS
				if(res.adset_show && res.adset_show == 1){
					that.adpidnum = res.adpid;
					setTimeout(() => {
						that.$refs.adFullscreenVideo.show();
					},1000)
				}
				// #endif
				that.loaded();
      });
			that.getdatalist();
    },
    getdatalist: function (loadmore) {
			if(!loadmore){
				this.pagenum = 1;
				this.datalist = [];
			}
      var that = this;
      var pagenum = that.pagenum;
      var keyword = that.keyword;
			that.loading = true;
			that.nodata = false;
			that.nomore = false;
      app.post('ApiScoreshop/getprolist', {bid:that.bid,pagenum: pagenum,keyword: keyword}, function (res) {
				uni.stopPullDownRefresh();
				that.loading = false;
				that.isload = true;
        var data = res.data;
        if (pagenum == 1) {
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
      });
    },
    search: function (e) {
      var keyword = e.detail.value;
      app.goto('prolist?bid='+this.bid+'&keyword='+keyword);
    }
  }
};
</script>
<style>
.topimg{width:100%;height:300rpx;}
.topimg .img{width:100%;height:100%}

.topinfo{width:94%;margin:0 3%;background:#fff;border-radius:16rpx;overflow:hidden;padding:60rpx 20rpx 20rpx 20rpx;margin-top:-100rpx;position:relative;display:flex;flex-direction:column}
.topinfo .myscore{margin-left:40rpx;display:flex;flex-direction:column}
.topinfo .myscore .t1{font-size:48rpx;font-weight:bold}
.topinfo .myscore .t2{color:#999999;font-size:24rpx;margin-top:10rpx}
.topinfo .scorelog{margin-left:120rpx;margin-top:30rpx;height:56rpx;line-height:56rpx;width:172rpx;text-align:center;border-radius:28rpx;font-size:24rpx;font-weight:bold;display:flex;justify-content:center;align-items:center}
.topinfo .orderlog{margin-left:40rpx;margin-top:30rpx;background:rgba(255, 160, 10, 0.2);height:56rpx;line-height:56rpx;width:172rpx;text-align:center;border-radius:28rpx;color:#FFA00A;font-size:24rpx;font-weight:bold;display:flex;justify-content:center;align-items:center}
.search-container {width: 100%;height: 94rpx;padding: 16rpx 23rpx 14rpx 23rpx;background-color: #fff;position: relative;overflow: hidden;margin-top:20rpx}
.search-box {display:flex;align-items:center;height:60rpx;border-radius:30rpx;border:0;background-color:#f7f7f7;flex:1}
.search-box .img{width:24rpx;height:24rpx;margin-right:10rpx;margin-left:30rpx}
.search-box .search-text {font-size:24rpx;color:#C2C2C2;width: 100%;}


.nav_li{width: 25%;text-align: center;box-sizing: border-box;padding:30rpx 0 10rpx;float: left;color:#333}
.nav_li .img{width:80rpx;height: 80rpx;margin-bottom:10rpx;}



.weui-loadmore_line .weui-loadmore__tips{background-color:#f6f6f6}
.swiper-container {width: 100%;} 
.swiper-container image {display: block;width: 100%;}
.category{width: 100%;padding-top: 20rpx;padding-bottom: 20rpx;flex-direction:row;white-space: nowrap; display:flex;background:#fff;overflow-x:scroll;margin-bottom:20rpx}
.category .item{width:150rpx;display: inline-block; text-align: center;}
.category .item image{width:80rpx;height:80rpx;margin: 0 auto;border-radius: 50%;}
.category .item .t1{display: block;color: #666;}
.product-item{display:flex;flex-direction:column;background: #fff; padding:0 20rpx;margin:0 20rpx;margin-top:20rpx;border-radius:20rpx}
.product-item .itemcontent{display:flex;height:220rpx;border-bottom:1px solid #E6E6E6;padding:20rpx 0}
.product-item .product-pic {width: 180rpx;height: 180rpx; background: #ffffff;overflow:hidden}
.product-item .product-pic .img{width: 100%;height:180rpx;}
.product-item .product-info {padding:4rpx 10rpx;flex:1}
.product-item .product-info .p1 {color:#323232;font-weight:bold;font-size:28rpx;line-height:36rpx;margin-bottom:10rpx;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:2;overflow:hidden;height:72rpx}
.product-item .product-info .p2{height:50rpx;display:flex;align-items:center;color:#666;font-size:24rpx}
.product-item .product-info .p3{font-size:32rpx;height:40rpx;line-height:40rpx;display:flex;align-items:center}
.product-item .product-info .p3 .t1{flex:auto;font-size:28rpx;font-weight:bold}
.product-item .product-info .p3 .t1 .x2{margin-left:10rpx;font-size:26rpx;color: #888;}
.product-item .product-info .p3 .t2{padding:0 16rpx;font-size:22rpx;height:44rpx;line-height:44rpx;overflow: hidden;color:#fff;background:#4fee4f;border:0;border-radius:20rpx;}
.product-item .product-info .p3 button:after{border:0}

.product-item .itembottom{width:100%;padding:0 20rpx;display:flex;height:100rpx;align-items:center}
.product-item .itembottom .f1{flex:1;color:#666;font-size:24rpx}
.product-item .itembottom .f2{color:#fff;width:160rpx;height:56rpx;display:flex;align-items:center;justify-content:center;border-radius:8rpx}

.navbox{width:94%;margin:0 3%;margin-top:20rpx;background: #fff;height: auto;overflow: hidden;border-radius: 20rpx;}
.nav_li{width: 25%;text-align: center;box-sizing: border-box;padding:30rpx 0 10rpx;float: left;color:#333}
.nav_li image{width:80rpx;height: 80rpx;margin-bottom:10rpx;}

.plr20{width: 100%;box-sizing: border-box;padding: 0 10rpx;margin-bottom: 10rpx;}
.tj_title{background: #fff;height: 70rpx;width: 100%;box-sizing: border-box;padding: 0 20rpx;line-height: 70rpx;display: flex;align-items: center;border-radius: 10rpx;}
.icon1{width: 40rpx;margin-right: 14rpx}

.weui-search-bar__box{ position: relative}
.weui-icon-search{ position: absolute;width:32rpx; top:12rpx;left:40%}
.weui-search-bar__input{ background: #fff;padding:0 10px; margin:10px; border-radius:5px; text-align: center;}

.topsearch{width:100%;max-width:750px;padding:16rpx 20rpx;background:#f6f6f6}
.topsearch .f1{height:70rpx;border-radius:8rpx;border:1px solid #eeeeee;background-color:#fff;flex:1}
.topsearch .f1 image{width:34rpx;height:34rpx;margin-left:20rpx}
.topsearch .f1 input{height:100%;flex:1;padding:0 20rpx;font-size:24rpx;color:#333;border:0;background:#fff}
.search-btn{color:#5a5a5a;font-size:30rpx;width:60rpx;text-align:center;margin-left:20rpx}

.covermy{position:absolute;z-index:99999;cursor:pointer;display:flex;flex-direction:column;align-items:center;justify-content:center;overflow:hidden;z-index:9999;top:260rpx;right:0;color:#fff;background-color:rgba(17,17,17,0.3);width:140rpx;height:60rpx;font-size:26rpx;border-radius:30rpx 0px 0px 30rpx;}
</style>