<template>
<view class="container">
	<block v-if="isload">
		<view class="main">
			<block v-for="(item, index) in datalist" :key="index">
			<view class="item" @tap.stop="goto" :data-url="'main?id=' + item.id">
				<view class="left">
					<view class="img"><image :src="item.pic"></image></view>
					<view class="info">
						<view class="title">{{item.name}}</view>
						<view class="desc">{{item.desc}}</view>
					</view>
				</view>

				<view class="opt">
					点击评测
					<text class="iconfont iconjiantou" style="color:#999;font-weight:normal;font-size: 26rpx;"></text>
				</view>
			</view>
			</block>
		</view>
		<nomore v-if="nomore"></nomore>
		<nodata v-if="nodata"></nodata>
	</block>
	<loading v-if="loading"></loading>
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
      datalist: [],
      pagenum: 1,
      nomore: false,
      nodata: false,
			keyword:'',
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
      var st = that.st;
			that.nodata = false;
			that.nomore = false;
			that.loading = true;
      app.post('ApiHealth/index', {pagenum: pagenum,keyword:that.keyword}, function (res) {
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
    }
  }
};
</script>
<style>
.container{ width:100%;}
.main{padding: 30rpx;}
.item{display: flex;justify-content: space-between;align-items: center;background: #FFFFFF;border-radius: 10rpx;margin-bottom: 30rpx;padding:20rpx;}
.item .left{display: flex;align-items: center;justify-content: flex-start;}
.item .img{width: 120rpx;height: 120rpx;border-radius: 10rpx;}
.item .img image{max-width:100%;max-height: 100%;border-radius: 10rpx;}
.item .info{padding: 0 20rpx;}
.item .info .title{font-size: 28rpx;font-weight: bold;}
.item .info .desc{font-size: 24rpx;line-height: 40rpx;color: #999;max-height: 80rpx;overflow: hidden;text-overflow: ellipsis;}
.item .opt{color: #999;font-size: 24rpx;flex-shrink: 0;}
</style>