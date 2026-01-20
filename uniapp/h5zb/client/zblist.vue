<template>
<view class="container">
	<block v-if="isload">
		<view class="topsearch">
			<view class="f1 flex-y-center">
				<image class="img" :src="pre_url+'/static/img/search_ico.png'"></image>
				<input :value="keyword" placeholder="搜索感兴趣的直播" placeholder-style="font-size:24rpx;color:#C2C2C2" @confirm="searchConfirm"></input>
			</view>
		</view>
		<view class="listbox">
			<view class="list" v-for="(item,index) in datalist" :key="index" @tap="goto" :data-url="'main?id='+item.id">
				<view class="status_tag">{{item.status_txt}}</view>
				<view class="pic">
					<image class="image" :src="item.pic" mode="widthFix"/>
				</view>
				<view class="time"><text class="t">{{item.starttime}}</text></view>
				<view class="info">
					<view class="p1">{{item.name}}</view>
					<view class="p3" v-if="item.type==0"><image :src="item.headimg" class="headimg"></image><text>{{item.nickname}}</text></view>
				</view>
			</view>
		</view>
	</block>
	<nodata v-if="nodata"></nodata>
	<nomore v-if="nomore"></nomore>
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
			nodata:false,
			nomore:false,
			keyword:'',
      datalist: [],
      pagenum: 1,
	  pre_url:app.globalData.pre_url,
    };
  },
  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		if(this.opt.keyword) {
			this.keyword = this.opt.keyword;
		}
    this.getdata();
  },
	onPullDownRefresh: function () {
		this.getdata();
	},
  onReachBottom: function () {
    if (!this.nomore && !this.nodata) {
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
      var keyword = that.keyword;
			that.loading = true;
			that.nodata = false;
			that.nomore = false;
      app.post('ApiH5zb/zblist', {pagenum: pagenum,keyword:keyword}, function (res) {
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
    },
    searchConfirm: function (e) {
      var that = this;
      var keyword = e.detail.value;
      that.keyword = keyword
      that.getdata();
    }
  }
};
</script>
<style>
	.topsearch{width:100%;padding:20rpx 20rpx;background:#fff}
	.topsearch .f1{height:70rpx;border-radius:35rpx;border:0;background-color:#f5f5f5;flex:1;overflow:hidden}
	.topsearch .f1 image{width:30rpx;height:30rpx;margin-left:10px}
	.topsearch .f1 input{height:100%;flex:1;padding:0 20rpx;font-size:28rpx;color:#333;background-color:#f5f5f5;}
	.listbox{
		margin: 0 auto;
		padding: 14rpx;
		width: 100%;
		column-count: 2; 
		column-gap: 14rpx; }
 .list{
	 position: relative;
			break-inside: avoid;
			background: #fff;
			margin-bottom: 20rpx;
			border-radius: 16rpx;
	}
	.status_tag{position: absolute;top: 4rpx;right: 4rpx;z-index: 99;background: #00000047;color: #fff;font-size: 22rpx;padding: 4rpx 10rpx;border-radius: 4rpx;}
	.list .pic{overflow: hidden;border-radius: 16rpx 16rpx;}
	.list .image{
		max-width: 100%;
	}
	.list .time{text-align: center;position: relative;top: -54rpx;background: #00000047;color: #fff;padding: 4rpx 20rpx;}
	.list .info{position: relative;top: -40rpx;padding: 0 20rpx;}
	.list .info .p1{font-size: 30rpx;}
	.list .info .p3{display: flex;align-items: center;margin-top: 14rpx;color: #999;}
	.list .info .headimg{width: 50rpx;height: 50rpx;border-radius: 50%;margin-right: 20rpx;}
</style>