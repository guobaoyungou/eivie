<template>
<view class="container">
	<view class="qd_guize">
		<view class="gztitle"> — 第<text class="t2">{{bonuscount}}</text>期 奖励排名 — </view>
		<view class="desc">前10名奖励排行榜</view>
		<view class="paiming">
			<view v-for="(item, index) in datalist" :key="index" class="item flex">
				<view class="f1">
						<image :src="item.headimg" class="headimg">
						<text class="t1">{{item.nickname}}</text>
				</view>
				<view class="f2">
						<text class="t2">获得金额</text>
						<text class="t1" style="font-weight: bold;"> {{item.money}} </text>
						<text class="t2">元</text>
				</view>
			</view>
		</view> 
		
		<view class="item item2 flex" v-if="userinfo.money">
			<view class="f1">
					<image :src="userinfo.headimg" class="headimg">
					<text class="t1">{{userinfo.nickname}}</text>
			</view>
			<view class="f2">
					<text class="t2">获得金额</text>
					<text class="t1" style="font-weight: bold;"> {{userinfo.money}} </text>
					<text class="t2">元</text>
			</view>
		</view>
	</view>
	 
	<nomore v-if="nomore"></nomore>
	<nodata v-if="nodata"></nodata>
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

      st: 0,
      datalist: [],
      pagenum: 1,
      nomore: false,
			nodata: false,
			bonuscount:0,
			userinfo:[]
    };
  },

  onLoad: function (opt) {
		this.opt = app.getopts(opt);
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
    changetab: function (e) {
      var st = e.currentTarget.dataset.st;
      this.st = st
      uni.pageScrollTo({
        scrollTop: 0,
        duration: 0
      });
      this.getdata();
    },
    getdata: function (loadmore) {
			if(!loadmore){
				this.pagenum = 1;
				this.datalist = [];
			}
      var that = this;
      var pagenum = that.pagenum;
      var st = that.st;
			this.nomore = false;
			this.nodata = false;
			that.loading = true;
      app.post('ApiSign/getpmlist', {st: st}, function (res) {
				that.loading = false;
        var data = res.data;
          that.datalist = data;
					that.bonuscount = res.bonuscount
					that.userinfo = res.userinfo
          if (data.length == 0) {
            that.nodata = true;
          }
					that.loaded();
 
      });
    }
  }
};
</script>
<style>
.content{ width:94%;margin:0 3%;}
.qd_guize{width:100%;margin:0;padding-bottom:20rpx} 
.qd_guize .gztitle{width:100%;text-align:center;font-size:32rpx;color:#656565;font-weight:bold;margin-top: 20rpx;margin-bottom: 10rpx;}
.gztitle .t1{ font-weight: bold;font-size: 30rpx;}
.desc{width:100%;text-align:center;font-size:26rpx;color:#656565;font-weight:bold;margin-bottom: 20rpx;color:#EE6750 ; }
.paiming{ width:94%;margin:0 3%;background:#fff;border-radius:10px;padding:20rpx 20rpx;}
.item{ line-height: 80rpx;border-bottom: 1px dashed #eee;}
.item .f1 .headimg{ width: 80rpx; height:80rpx;border-radius: 50%;margin: 10rpx 10rpx 10rpx 0;}
.item:last-child{border:0}
.item .f1{flex:1;display:flex;align-items: center;}
.item .f1 .t1{color:#000000;font-size:30rpx;word-break:break-all;overflow:hidden;text-overflow:ellipsis;}
.item .f1 .t2{color:#666666}
.item .f1 .t3{color:#666666}
.item .f2{ text-align:right;font-size:30rpx;align-items: center;display: flex; }
.item .f2 .t1{color:#03bc01}
.item .f2 .t2{color:#000000}

.item2{ padding:40rpx}
</style>