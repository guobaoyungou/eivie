<template>
<view>
  <view v-if="isload" class="content">
    <view v-for="(item, index) in datalist" :key="index" class="task">
      <view @tap="goto" :data-url="'logdetail?logid='+item.id">
        <view >
          任务时间：{{item.daytime}}
        </view>
        <view class="taskprogress">
          任务进度: <progress :percent="item.taskprogress" show-info stroke-width="4" border-radius="4" style="width: 360rpx;margin-left: 4rpx;"/>
        </view>
        <view v-if="item.status>=0 && (item.givescore>0 || (item.givecouponid>0 && item.givecouponname)) " >
          完成奖励：
          <text v-if="item.givescore>0" > +{{item.givescore}}{{t('积分')}}</text>
          <text v-if="item.givecouponid>0 && item.givecouponname"  :style="item.givescore>0 ?'margin-left: 10rpx;':''"> +{{item.givecouponname}}{{t('优惠券')}}</text>
        </view>
        <view >
          任务状态：
          <text v-if="item.status == -1" style="color: #999;">已关闭</text>
          <text v-if="item.status == 0">未完成</text>
          <text v-if="item.status == 1" style="color: green;">已完成</text>
        </view>
      </view>
      <image class="imgback" :src="`${pre_url}/static/img/location/right-black.png`" ></image>
    </view>
    
    <nomore v-if="nomore"></nomore>
    <nodata v-if="nodata"></nodata>
  </view>
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

      datalist: [],
      pagenum: 1,
			nodata:false,
      nomore: false,

    };
  },
  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.getdata();
  },
	onPullDownRefresh: function () {
		this.pagenum = 1;
		this.datalist = [];
		this.getdata();
	},
  onReachBottom: function () {
    if (!this.nodata && !this.nomore) {
      this.pagenum = this.pagenum + 1;
      this.getdata();
    }
  },
  methods: {
    getdata: function () {
      var that = this;
      var pagenum = that.pagenum;
			that.loading = true;
			that.nomore = false;
			that.nodata = false;
      app.post('ApiTask/log', {pagenum: pagenum}, function (res) {
				that.loading = false;
        var data = res.data;
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
  .content{width: 710rpx;margin: 0 auto;}
  .task{padding: 20rpx;margin-bottom: 20rpx;line-height: 70rpx;background-color: #fff;border-radius: 8rpx;display: flex;justify-content: space-between;align-items: center;}
  .imgback{width: 40rpx;height: 40rpx;}
  .taskprogress{display: flex;justify-content: space-between;}
</style>