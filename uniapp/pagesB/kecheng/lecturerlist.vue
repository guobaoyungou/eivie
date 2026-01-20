<template>
<view>
	<block v-if="isload">
    <view class="content">
      <view v-for="(item, index) in datalist" :key="index" class="item" :style="index+1>=datalen?'border:0':''" @tap="goto" :data-url="'/pagesB/kecheng/lecturercenter?id='+item.id">
        <view style="display: flex;align-items: center;justify-content: space-between;">
          <view style="display: flex;align-items: center;">
            <view class="headimg">
              <image :src="item.headimg" style="width: 100%;height: 100%;"></image>
            </view>
            <view style="width: 500rpx;overflow: hidden;white-space: nowrap;text-overflow: ellipsis;">
              {{item.nickname}}
            </view>
          </view>
        </view>
        <view style="margin-top: 20rpx;line-height: 40rpx;white-space: pre-wrap;word-wrap: break-word;">{{item.shortdesc}}</view>
      </view>
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
			menuindex:-1,
			pre_url:app.globalData.pre_url,
      datalist: [],
      datalen:0,
      pagenum: 1,
      nodata:false,
      nomore: false,
    };
  },
  onLoad: function (opt) {
    this.opt = app.getopts(opt);
  },
  onShow:function(){
    this.getdata();
  },
  onPullDownRefresh: function () {
  	this.getdata(true);
  },
  onReachBottom: function () {
    if (!this.nodata && !this.nomore) {
      this.pagenum = this.pagenum + 1;
      this.getdata(true);
    }
  },
  methods: {
		getdata: function (loadmore= false) {
      var that = this;
			if(!loadmore){
				that.pagenum = 1;
				that.datalist = [];
			}
		  var pagenum = that.pagenum;
			that.loading = true;
			app.post('ApiKecheng/lecturerlist', {pagenum: pagenum}, function (res) {
				that.loading = false;
        if(res.status == 1){
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
          that.datalen = that.datalist.length;
        } else {
          if (res.msg) {
            app.alert(res.msg, function() {
              if (res.url) app.goto(res.url);
            });
          } else if (res.url) {
            app.goto(res.url);
          } else {
            app.alert('您无查看权限');
          }
        }
			});
		},
  }
}
</script>
<style>
  .headcontent{width: 100%;padding: 20rpx 0;}
  .content{width: 710rpx;margin: 0 auto;}
  .content .item{padding: 20rpx;background-color: #fff;border-radius: 8rpx;margin-top:20rpx;}
  .item1{display: flex;justify-content: space-between;padding: 20rpx 0;}
  .title{width: 440rpx;font-size:30rpx;word-break: break-all;text-overflow: ellipsis;overflow: hidden;display: -webkit-box;-webkit-box-orient: vertical;-webkit-line-clamp: 3;height: 140rpx;line-height: 45rpx;}
  .btn{height:60rpx;line-height:60rpx;color:#333;background:#fff;border-radius:3px;text-align:center;padding: 0 15rpx;flex-shrink: 0;margin: 0 0 0 15rpx;}
  .headimg{width: 100rpx;height: 100rpx;background-color: #f1f1f1;border-radius: 50% 50%;margin-right: 10rpx;overflow: hidden;}
  .send{width: 120rpx;text-align: center;color: #fff;line-height: 60rpx;border-radius: 60rpx;}
</style>