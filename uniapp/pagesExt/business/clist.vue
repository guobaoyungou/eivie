<template>
<view class="container">
	<block v-if="isload">
		<view @tap.stop="goto" data-url="/pagesExt/business/blist" class="search-container">
			<view class="search-box">
				<image class="img" :src="pre_url+'/static/img/search_ico.png'"></image>
				<view class="search-text">搜索感兴趣的商家</view>
			</view>
		</view>
		<view class="content-container">
			<view class="nav_left">
				<scroll-view scroll-y style="height:100%;">
					<view :class="'nav_left_items ' + (curIndex == -1 ? 'active' : '')" @tap="switchRightTab" data-index="-1" data-id="0"><view class="before" :style="{background:t('color1')}"></view>全部</view>
					<block v-for="(item, index) in clist" :key="index">
						<view :class="'nav_left_items ' + (curIndex == index ? 'active' : '')" @tap="switchRightTab" :data-index="index" :data-id="item.id"><view class="before" :style="{background:t('color1')}"></view>{{item.name}}</view>
					</block>
				</scroll-view>
			</view>
			<view class="nav_right">
				<view class="nav_right-content">
					<scroll-view class="classify-box" scroll-y="true" @scrolltolower="scrolltolower">
						<view v-for="(item, index) in datalist" :key="index" class="item" @tap="goto" :data-url="'/pagesExt/business/index?id=' + item.id">
							<image class="logo" :src="item.logo"></image>
							<view class="detail">
								<view class="f1">{{item.name}}</view>
								<view class="f2" v-if="show_business_tel == 1 && item.tel"><block v-if="item.tel">电话：<text style="font-weight:bold">{{item.tel}}</text></block></view>
								<view class="f4">地址：<text style="font-weight:bold">{{item.address}}</text></view>
								<view class="f4" :style="{color:t('color1')}" v-if="item.activity_time && item.activity_time_status==1" >
									活动时间：<text class="x1" >{{item.activity_time}}</text>
								</view>
								<view class="ratio-list flex">
									<view class="ratio-label flex-y-center" v-if="item.rate_back && item.rate_back > 0" :style="{color:t('color1'),borderColor:t('color1')}">
										<view class="label" :style="{backgroundColor:t('color1')}">返</view>
										<view class="t1">{{item.rate_back}}%</view>
									</view>
									<view class="ratio-label flex-y-center" v-if="item.scoredkmaxpercent && item.scoredkmaxpercent > 0"  :style="{color:t('color1'),borderColor:t('color1')}">
										<view class="label" :style="{backgroundColor:t('color1')}">积</view>
										<view class="t1">{{item.scoredkmaxpercent}}%</view>
									</view>
								</view>
								
								<view class="f3" v-if="item.juli">距离：{{item.juli}}</view>
								<view class="f1" v-if="item.show_maidan ==1" style="justify-content: flex-end;display: flex;">
									<view class="tomaidan" @tap.stop="goto" :data-url="'/pagesB/maidan/pay?bid='+item.id" :style="{background:'linear-gradient(90deg,'+t('color1')+' 0%, rgba('+t('color1rgb')+',0.8) 100%)'}">在线买单</view>
								</view>
							</view>
						</view>
						<nomore text="没有更多商家了" v-if="nomore"></nomore>
						<nodata text="暂无相关商家" v-if="nodata"></nodata>
					</scroll-view>
				</view>
			</view>
		</view>
	</block>
	<loading v-if="loading" loadstyle="left:62.5%"></loading>
	<dp-tabbar :opt="opt" @getmenuindex="getmenuindex"></dp-tabbar>
	<popmsg ref="popmsg"></popmsg>
	<wxxieyi></wxxieyi>
</view>

<!-- <view class="container" :class="menuindex>-1?'tabbarbot':''">
	<view class="nav_left">
    <block v-for="(item, index) in clist" :key="index">
      <view :class="'nav_left_items ' + (curIndex == index ? 'active' : '')" @tap="switchRightTab" :data-index="index" :data-id="item.id">
      {{item.name}}
      </view>
    </block>
  </view>
  <view class="nav_right">
    <scroll-view class="classify-box" scroll-y="true" @scrolltolower="scrolltolower">
			<view v-for="(item, index) in datalist" :key="index" class="item" @tap="goto" :data-url="'/pagesExt/business/index?id=' + item.id">
				<image :src="item.logo"></image>
				<view class="detail">
					<view class="f1">{{item.name}}</view>
					<view class="f2"><block v-if="item.tel"><text class="fa fa-phone"></text> {{item.tel}}</block></view>
					<view class="f4"><text class="fa fa-map-marker"></text> {{item.address}}</view>
					<view class="f3" v-if="item.juli">距离：{{item.juli}}</view>
				</view>
			</view>
			<nomore v-if="nomore"></nomore>
			<nodata v-if="nodata"></nodata>
    </scroll-view>
  </view>
	<loading v-if="loading"></loading>
	<dp-tabbar :opt="opt" @getmenuindex="getmenuindex"></dp-tabbar>
	<popmsg ref="popmsg"></popmsg>
</view> -->
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
			
			nodata:false,
			nomore:false,
      pagenum: 1,
      datalist: [],
      clist: [],
      curIndex: -1,
      curCid: 0,
      nomore: false,
      longitude: "",
      latitude: "",
      blist: "",
      show_business_tel:1, //列表是否显示商家电话
	  pre_url:app.globalData.pre_url,
    };
  },

  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.curCid = this.opt.cid || 0;
		this.getdata();
  },
	onPullDownRefresh: function () {
		this.getdata();
	},
  methods: {
		getdata: function () {
			var that = this;
			that.pagenum = 1;
			that.prolist = [];
			that.loading = true;
			app.get('ApiBusiness/clist', {}, function (res) {
				that.loading = false;
				that.clist = res.clist;
				that.loaded();
				app.getLocation(function (res) {
					var latitude = res.latitude;
					var longitude = res.longitude;
					that.longitude = longitude;
					that.latitude = latitude;
					that.getblist();
				},
				function () {
					that.getblist();
				});
			});
		},
    scrolltolower: function () {
      if (!this.nodata && !this.nomore) {
        this.pagenum = this.pagenum + 1;
        this.getblist();
      }
    },
    getblist: function () {
      var that = this;
      var pagenum = that.pagenum;
      var cid = that.curCid;
      var longitude = that.longitude;
      var latitude = that.latitude;
			that.loading = true;
			that.nodata = false;
			that.nomore = false;
      app.post('ApiBusiness/clist', {longitude: longitude,latitude: latitude,pagenum: pagenum,cid: cid}, function (res) {
				that.loading = false;
				uni.stopPullDownRefresh();
        var data = res.data;
        that.show_business_tel = res.show_business_tel;
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
    //事件处理函数
    switchRightTab: function (e) {
      var that = this;
      var id = e.currentTarget.dataset.id;
      var index = parseInt(e.currentTarget.dataset.index);
      that.curIndex = index;
      that.curCid = id;
      that.pagenum = 1;
      that.blist = [];
      this.getblist();
    }
  }
};
</script>
<style>
page {height:100%;}
.container{width: 100%;height:100%;max-width:640px;background-color: #fff;color: #939393;display: flex;flex-direction:column}
.search-container {width: 100%;height: 94rpx;padding: 16rpx 23rpx 14rpx 23rpx;background-color: #fff;position: relative;overflow: hidden;border-bottom:1px solid #f5f5f5}
.search-box {display:flex;align-items:center;height:60rpx;border-radius:30rpx;border:0;background-color:#f7f7f7;flex:1}
.search-box .img{width:24rpx;height:24rpx;margin-right:10rpx;margin-left:30rpx}
.search-box .search-text {font-size:24rpx;color:#C2C2C2;width: 100%;}

.content-container{flex:1;height:100%;display:flex;overflow: hidden;}

.nav_left{width: 25%;height:100%;background: #ffffff;overflow-y:scroll;}
.nav_left .nav_left_items{line-height:50rpx;color:#999999;font-size:24rpx;position: relative;padding:25rpx 30rpx;}
.nav_left .nav_left_items.active{background: #fff;color:#222222;font-size:28rpx;font-weight:bold}
.nav_left .nav_left_items .before{display:none;position:absolute;top:50%;margin-top:-12rpx;left:10rpx;height:24rpx;border-radius:4rpx;width:8rpx}
.nav_left .nav_left_items.active .before{display:block}

.nav_right{width: 75%;height:100%;display:flex;flex-direction:column;background: #f6f6f6;box-sizing: border-box;padding:20rpx 20rpx 0 20rpx}
.nav_right-content{background: #ffffff;padding:0 20rpx;height:100%}
.classify-box{padding: 0 0 20rpx 0;width: 100%;height:calc(100% - 60rpx);overflow-y: scroll; border-top:1px solid #F5F6F8;}
.classify-box .nav_right_items{ width:100%;border-bottom:1px #f4f4f4 solid;  padding:16rpx 0;  box-sizing:border-box;  position:relative; }

.nav_right .item{display:flex;width: 100%; padding:16rpx 0px;border-bottom: 1px #f5f5f5 solid;position:relative}
.nav_right .item:last-child{ border-bottom: 0; }
.nav_right .item .logo{ width: 160rpx; height: 160rpx;}
.nav_right .item .detail{width:100%;overflow:hidden;display:flex;flex-direction:column;margin-left:14rpx;flex:1}
.nav_right .item .detail .f1{color:#323232;font-weight:bold;font-size:28rpx;line-height:30rpx;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:1;overflow:hidden;}
.nav_right .item .detail .f2{margin-top:6rpx;height: 40rpx;line-height: 40rpx;color: #888;font-size:24rpx;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:1;overflow:hidden;}
.nav_right .item .detail .f3{margin-top:6rpx;height: 40rpx;line-height: 40rpx;color: #31C88E;font-size:24rpx;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:1;overflow:hidden;}
.nav_right .item .detail .f4{margin-top:6rpx;line-height: 40rpx;color: #999;font-size:24rpx;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:2;overflow:hidden;}
.nav_right .item .detail .f5{margin-top:6rpx;display:flex;height: 35rpx;line-height: 35rpx;font-size:24rpx;color: #ff4246;font-size: 22rpx;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:1;overflow:hidden;}
/*
.nomore-footer-tips{background:#ffffff}
.container {position:fixed;width: 100%;max-width:640px;background-color: #fff;color: #939393;top: 0;bottom: 0;}
.nav_left{overflow-y: scroll;width: 25%;height: 100%;background: #f5f5f5; box-sizing: border-box;text-align: center;position: absolute; top:var(--window-top); left: 0;}
.nav_left .nav_left_items{height:100rpx;line-height:100rpx;color:#666666;padding:0;border-bottom: 1px solid #E6E6E6;font-size:28rpx;position: relative;border-right:1px solid #E6E6E6}
.nav_left .nav_left_items.active{background: #fff;color:#FC4343;border-left:3px solid #FC4343}

.nav_right{position: absolute;display:flex;flex-direction:column;top: var(--window-top);right: 0;flex: 1;width:75%;height: 100%;padding:0px 20rpx 20rpx 20rpx ;background: #fff;box-sizing: border-box;overflow-y: hidden;}

.classify-box{ width: 100%;overflow-y: scroll; height:100%;}
.nav_right_items{ width:100%;border-bottom:1px #eeeeee solid;  padding:16rpx 0;  box-sizing:border-box;  position:relative; }

.nav_right .item{display:flex;width: 100%; padding:16rpx 0px;border-bottom: 1px #f5f5f5 solid;position:relative}
.nav_right .item:last-child{ border-bottom: 0; }
.nav_right .item .logo{ width: 160rpx; height: 160rpx;}
.nav_right .item .detail{width:100%;overflow:hidden;display:flex;flex-direction:column;margin-left:14rpx;flex:1}
.nav_right .item .detail .f1{height: 40rpx;line-height: 40rpx;color: #111;}
.nav_right .item .detail .f2{height: 40rpx;line-height: 40rpx;color: #888;overflow: hidden;font-size: 24rpx;}
.nav_right .item .detail .f3{height: 40rpx;line-height: 40rpx;color: #31C88E;overflow: hidden;font-size: 24rpx;}
.nav_right .item .detail .f4{height: 40rpx;line-height: 40rpx;color: #999;overflow: hidden;font-size: 24rpx;}
.nav_right .item .detail .f5{display:flex;height: 35rpx;line-height: 35rpx;color: #ff4246;font-size: 22rpx}
*/
.tomaidan{
	width: 150rpx;
	font-size: 28rpx;
	color: #fff;
	padding: 10rpx 0rpx;
	border-radius: 60rpx;
	font-weight: normal;
	text-align: center;
}
/*返利 和 积分显示*/
.ratio-list{padding-top: 10rpx;}
.ratio-label{height: 40rpx;border-radius: 10rpx;width:160rpx;border: 2rpx solid;margin-right:20rpx;}
.ratio-label .label{width: 55rpx ;height: 40rpx;line-height: 40rpx;border-radius: 10rpx 20rpx 5rpx 10rpx;color: #fff;text-align: center;}
.ratio-label .t1{text-align: center;width: 65%;font-size: 28rpx;}
</style>