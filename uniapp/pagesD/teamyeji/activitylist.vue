<template>
<view class="container">
	<block v-if="isload">

		<!-- <dd-tab :class="show_rebate?'container-tab':''" :itemdata="['全部','进行中','已结束']" :itemst="['all','1','2']" :st="st" :isfixed="true" @changetab="changetab"></dd-tab>
		<view :style="show_rebate?'height:260rpx;width:100%;':'height:100rpx;width:100%;'"></view>
		 -->
		<view class="order-content">
			<block v-for="(item, index) in datalist" :key="index">
				<view class="order-box" >	
				
				<view class="head flex flex-y-center">
					<view class="head-top-view flex flex-y-center">
						<view class="head-title">活动名称：{{item.name}}</view>
					</view>
					<view class="st0 flex-y-center" v-if="item.jump_detail_status" @tap="goto" :data-url="item.jump_detail_url">查看详情 <image :src="pre_url+'/static/img/arrowright.png'"  class="arrowright"></image></view>
				</view>
				<view class="head flex flex-y-center">
					<view class="head-top-view flex flex-y-center" style="width: 100%;">
						<view class="head-title">活动时间：<text style="font-size: 24rpx;">{{item.starttime}} ~ {{item.endtime}}</text></view>
					</view>
					<!-- <text class="st0">{{item.status}}</text> -->
				</view>
				
				
				<view style="border-bottom: 1px #f4f4f4 solid; "></view>
				
				<view class="content flex-bt">
					<view class="option-content flex"  >
						<view>我的业绩：</view>
						<view style="color: #ff8758;">￥{{item.yeji}}</view>
					</view>
					<view class="option-content flex"  >
						<view><text v-if="item.fafang_fenhong > 0">已发奖励</text><text v-else>预估奖励：</text></view>
						<view style="color: #ff8758;"> <text v-if="item.fafang_fenhong > 0">￥{{item.fafang_fenhong}}</text> <text v-else>￥{{item.fenhong}}</text></view>
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
	onNavigationBarSearchInputConfirmed:function(e){
		this.searchConfirm({detail:{value:e.text}});
	},
  methods: {
    changetab: function (st) {
			this.st = st;
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
			that.nodata = false;
			that.nomore = false;
			that.loading = true;
      app.post('ApiAgent/getTeamYejiActivityList', {status: st,pagenum: pagenum}, function (res) {
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