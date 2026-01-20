<template>
<view>
	<block v-if="isload">
		<block v-for="(item, index) in datalist" :key="index">
		<view class="daily-box">
			<view class="item" @tap.stop="goto" :data-url="'/pagesB/daily/daily?id=' + item.id">
				<view class="top">
					<view class="left"><image :src="pre_url+'/static/img/daily/yhleft.png'" class="douhao"/></view>
					<view class="content" v-html="item.content"></view>
					<view class="right"><image :src="pre_url+'/static/img/daily/yhright.png'" class="douhao" /></view>
				</view>
				<view class="bottom"  >
					<view class="left">
						<image class="logo" :src="item.shop_logo" />
						<text class="ml20 txt">{{item.shop_name}}</text>
						<text class="ml25 txt">{{item.fabutime}}</text>
					</view>
					<view class="btn">马上去听</view>
				</view>
			</view>
		</view>
		<view style="width:100%;height:20rpx"></view>
	</block>
	</block>
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
			isload: true,
			pre_url:app.globalData.pre_url,
			sysset: [],
			datalist: [],
			pagenum: 1,
			nomore: false,
			nodata: false,
    };
  },

  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		var that = this;

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
		  var pagenum = that.pagenum;
				that.nodata = false;
				that.nomore = false;
				that.loading = true;
		  app.post('ApiDailyjinju/getprolist', {pagenum: pagenum}, function (res) {
					that.loading = false;
					var data = res.datalist;
		    if (pagenum == 1) {
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
		},
	},
};
</script>
<style>
.ml20{ margin-left: 20rpx;}
.ml25{ margin-left: 26px;}
.daily-box{ padding:40rpx; background: #fff;margin:0 20rpx 10rpx 20rpx;margin-top: 26rpx;}
.item .top .douhao{ width: 50rpx; height: 50rpx;}
.item .top .right{ text-align: right;}
.item .content{ padding:0 40rpx}
.bottom{ display: flex; justify-content: space-between; margin-top: 20rpx; align-items: center;}
.bottom .left { display: flex; align-items: center;}
.bottom .left .logo{ width: 82rpx; height: 82rpx; border-radius: 50%;}
.bottom .txt{ color: rgb(149, 149, 149);}
.bottom .btn{ background:rgb(20, 190, 130) ;color: #fff;padding: 0 20rpx 0 14rpx; line-height: 52rpx;    border-radius: 26rpx;}
</style>