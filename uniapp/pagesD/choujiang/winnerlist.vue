<template>
<view class="container">
	<block v-if="isload">
		<view class="order-content">
			<block v-for="(item, index) in datalist" :key="index">
				<view class="order-box">	
					<view class="head flex flex-y-center">
						<view class="head-top-view flex flex-y-center">
							<view class="head-title">中奖用户：{{item.nickname}}</view>
						</view>
					</view>
					<view class="head flex flex-y-center">
						<view class="head-top-view flex flex-y-center" style="width: 100%;">
							<view class="head-title">获得奖品：{{item.award_name}}</view>
						</view>
					</view>
					<view class="head flex flex-y-center">
						<view class="head-top-view flex flex-y-center" style="width: 100%;">
							<view class="head-title">参与时间：{{item.createtime}}</view>
						</view>
					</view>
				</view>
			</block>
		</view>
		<nomore v-if="nomore"></nomore>
		<nodata v-if="nodata"></nodata>
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
      st: 'all',
      datalist: [],
      pagenum: 1,
      nomore: false,
      nodata: false,
      info:[],
      show_rebate:0,
      pre_url:app.globalData.pre_url,
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
    getdata: function (loadmore) {
      if(!loadmore){
        this.pagenum = 1;
        this.datalist = [];
      }
      var that = this;
      var pagenum = that.pagenum;
      var id = that.opt.id || 0;

      that.nodata = false;
      that.nomore = false;
      that.loading = true;
      app.post('ApiLirunChoujiang/winnerList', {id: id,pagenum: pagenum}, function (res) {
        that.loading = false;
        that.info = res;
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
    }
  }
};
</script>
<style>
.container{ width:100%;}
.container .container-top{position: fixed;background-color:#fff;width: 100%;padding: 16rpx 3%;color: #333;justify-content: space-between;flex-wrap: wrap;line-height:50rpx;z-index: 11;}
.container .container-top .top-content{ display:flex; justify-content:left;align-items: center;white-space:nowrap;overflow: hidden;margin: 0 30rpx;}
.container .container-top .top-content .t1{text-align: left;width: 50%;overflow: hidden;}
.container .container-tab {top: 156rpx;}
.container .container-tab .dd-tab2{top: 156rpx;}

.order-content{display:flex;flex-direction:column}
.order-box{ width: 94%;margin:10rpx 3%;padding:6rpx 3%; background: #fff;border-radius:8px}
.order-box .head{width:100%; height: 70rpx; line-height: 70rpx;justify-content: space-between;}
.order-box .head .head-top-view{color:#333;width:calc(100% - 130rpx);justify-content: flex-start;}
.order-box .head .head-top-view .head-title{width: calc(100% - 40rpx);white-space: nowrap;color:#333}
.order-box .head .head-top-view image{width:34rpx;height:34rpx;margin-right:4px}
.order-box .head .st0{ width: 150rpx; color: #ff8758; text-align: right;}
.order-box .content{}
.order-box .content .option-content{padding: 3px 0px;}
.progress-box {width: 100%;}
.option-content{line-height: 50rpx;text-align: center;color: #333;}
.head .arrowright{width: 26rpx;height: 26rpx;margin-left: 5rpx;}
</style>