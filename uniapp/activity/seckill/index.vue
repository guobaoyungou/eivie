<template>
<view class="container">
	<block v-if="isload">
		<view class="navbg" :style="'background: linear-gradient(45deg,'+t('color1')+' 0%, rgba('+t('color1rgb')+',0.8) 100%);'"></view>
		<view class="nav">
			<scroll-view scroll-x="true" :scroll-left="top_bar_scroll">
				<view class="f1">
					<block v-for="(item, index) in navlist" :key="index">
					<view :class="'item ' + (selected==index?'active':'')" @tap="changetab" :data-index="index">
							<view class="t3">{{item.seckill_date_m}}</view>
							<view class="t1">{{item.showtime}}</view>
							<view class="t2" v-if="item.active==-1">已结束</view>
							<view class="t2" v-if="item.active==0">已开抢</view>
							<view class="t2" v-if="item.active==1">抢购中</view>
							<view class="t2" v-if="item.active==2">即将开始</view>
					</view>
					</block>
				</view>
			</scroll-view>
		</view>
		<view class="content">
			<view v-for="(item, index) in datalist" :key="index" class="item">
				<image class="f1" mode="widthFix" :src="item.pic" @tap="goto" :data-url="'product?id=' + item.id"></image>
				<view class="f2">
					<text class="t1">{{item.name}}</text>
					<view class="t2" :style="{color:t('color1')}">
						<progress :percent="item.salepercent" backgroundColor="#FFD1C9" activeColor="#FF3143" border-radius="3" class="progress"></progress>
						<text class="x2">{{item.salepercent}}%</text>
					</view>
					<view class="t3">
						<text class="x1" :style="{color:t('color1')}">
							<block v-if="item.usd_sellprice>0">
								<text style="font-size:24rpx">$</text>{{item.usd_sellprice}}
								<text style="font-size: 26rpx;"><text style="font-size:24rpx">￥</text>{{item.sell_price}}</text>
							</block>
							<block v-else>
							<text style="font-size:24rpx">￥</text>{{item.sell_price}}
							</block>
						</text>
							
						<text class="x2">￥{{item.market_price}}</text>
						<button @tap="goto" :data-url="'product?id=' + item.id" class="x3" :style="{background:'linear-gradient(90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'}" v-if="item.starttime <nowtime && (item.seckill_time_length >0?item.seckill_time_length:seckill_duration*3600)+item.starttime*1 > nowtime">立即抢购</button>
						<button @tap="goto" :data-url="'product?id=' + item.id" class="x3 xx1" v-else-if="item.starttime*1+(item.seckill_time_length >0?item.seckill_time_length:seckill_duration*3600) < nowtime">去看看</button>
						<button @tap="goto" :data-url="'product?id=' + item.id" class="x3" v-else :style="{background:t('color2')}">抢先看看</button>
					</view>
				</view>
			</view>
			<view class="item" style="display:block" v-if="nodata"><nodata></nodata></view>
			<nomore v-if="nomore"></nomore>
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
			
			bid:'',
      st: 'all',
      datalist: [],
      pagenum: 1,
      navlist: "",
      activetime: "",
      activeindex: "",
      selected: "",
      top_bar_scroll: "",
      seckill_duration: "",
      nowtime: "",
      nomore: false,
			nodata:false,
    };
  },

  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.st = this.opt.st;
		this.bid = this.opt.bid || '';
		this.getdata();
  },
	onPullDownRefresh: function () {
		this.getdata();
	},
  onReachBottom: function () {
    if (!this.nodata && !this.nomore) {
      this.pagenum = this.pagenum + 1;
      this.getDataList(true);
    }
  },
  methods: {
		getdata: function () {
			var that = this;
			that.loading = true;
			app.post('ApiSeckill/index', {}, function (res) {
				that.loading = false;
				if (res.status == 0) {
					app.alert(res.msg);
				} else {
					that.navlist = res.navlist;
					that.activetime = res.activetime;
					that.activeindex = res.selected;
					that.selected = res.selected;
					that.top_bar_scroll = (res.selected - 2) * uni.getSystemInfoSync().windowWidth / 750 * 150;
					that.seckill_duration = res.seckill_duration;
					that.nowtime = res.nowtime;
					that.getDataList();
				}
				that.loaded();
			});
		},
    changetab: function (e) {
      var index = e.currentTarget.dataset.index;
      uni.pageScrollTo({
        scrollTop: 0,
        duration: 0
      });
      this.selected = index;
      this.getDataList();
    },
    getDataList: function (loadmore) {
			if(!loadmore){
				this.pagenum = 1;
				this.datalist = [];
			}
      var that = this;
      var selected = that.selected;
      var navlist = that.navlist;
      var pagenum = that.pagenum;
			that.nomore = false;
			that.nodata = false;
      app.post('ApiSeckill/getprolist', {bid:that.bid,seckill_date: navlist[selected].seckill_date,seckill_time: navlist[selected].seckill_time,pagenum: pagenum}, function (res) {
        uni.stopPullDownRefresh();
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
    }
  }
};
</script>
<style>
.container{width:100%;overflow:hidden}
.navbg{width: 100%;position:absolute;height:300rpx;}
/* .navbg:after {background: linear-gradient(90deg,rgba(253, 74, 70, 1) 0%,rgba(253, 74, 70, 0.8) 100%);content: '';width: 160%;height:300rpx;position: absolute;left: -30%;top:0;border-radius: 0 0 50% 50%;z-index:1} */
.nav {width: 100%;position:relative;z-index:2;height: 94px;}
.nav>scroll-view {padding-top:15rpx;padding-bottom:15rpx;height: 94px;}
.nav .f1 {flex-grow: 0;flex-shrink: 0;display:flex;align-items:center;color:#fff;position:relative;z-index:2}
.nav .f1 .item{flex-grow: 0;flex-shrink: 0;width:150rpx;text-align:center;padding:16rpx 0;opacity: 0.6;}
.nav .f1 .item .t1 {font-size:34rpx;font-weight:bold}
.nav .f1 .item .t2 {font-size:24rpx}
.nav .f1 .item .t3 {font-size:30rpx}
.nav .f1 .item.active {position: relative;color:#fff;opacity:1}

.content{width:94%;margin-left:3%;position:relative;z-index:3}
.data-empty{background:#fff;border-radius:16rpx}
.content .item{width:100%;display:flex;padding: 20rpx;background:#fff;border-radius:16rpx;margin-bottom:20rpx}
.item .f1{width:200rpx;height:200rpx;margin-right:20rpx;}
.item .f2{position: relative; padding-right: 20rpx;flex:1;display:flex;flex-direction:column}
.item .f2 .t1{font-size:28rpx;font-weight:bold;color: #222;margin-top: 2px;height:80rpx;overflow:hidden}
.item .f2 .t2{width:100%;margin-top:12rpx;display:flex;align-items:center}
.item .f2 .t2 .x2{padding-left:16rpx;font-size:24rpx;font-weight:bold}
.item .f2 .t3{width:100%;margin-top:20rpx;display:flex;align-items:flex-end}
.item .f2 .t3 .x1{font-size:32rpx;font-weight:bold}
.item .f2 .t3 .x2{color:#999999;font-size:24rpx;text-decoration:line-through;padding-left:8rpx}
.item .f2 .t3 .x3{position:absolute;bottom:0;right:0;border: 0;color: #fff;font-size:28rpx;padding:0 28rpx;height:54rpx;line-height:50rpx;border-radius:54rpx;margin:0}
.item .f2 .t3 .x3.xx1{background:#888}

.progress{width:240rpx;font-size:24rpx}
.nomore-footer-tips{background:#fff!important}
</style>