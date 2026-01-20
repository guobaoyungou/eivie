<template>
<view class="container">
	<block v-if="isload">
		<view class="content">
			<view v-for="(item, index) in datalist" :key="index" class="item">
			<view class="f2">
				<view class="t1">
					<text class="x1">发放期数：第{{item.send_circle}}期</text>
					<text class="x2">发放时间：{{dateFormat(item.send_time,'Y-m-d H:i')}}</text>
				</view>
				<view class="t2">
					<text class="x1">+{{item.send_num}}</text>
				</view>
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
			
		nodata: false,
		nomore: false,
		datalist: [],
		textset:{},
		pagenum: 1,
		pid:0
    };
  },

  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.pid = this.opt.pid || 0;
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
			that.loading = true;
			that.nodata = false;
      that.nomore = false;
      app.post('ApiAgent/commissionbutielog', {pagenum: pagenum,pid:that.pid}, function (res) {
				that.loading = false;
        var data = res.data;
        if (pagenum == 1) {
					that.textset = app.globalData.textset;
					uni.setNavigationBarTitle({
						title: that.t('分销补贴') + '发放记录'
					});
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
    changetab: function (st) {
      this.st = st;
      uni.pageScrollTo({
        scrollTop: 0,
        duration: 0
      });
      this.getdata();
    }
  }
};
</script>
<style>



.content{ width:100%;}
.content .item{width:94%;margin-left:3%;border-radius:10rpx;background: #fff;margin-bottom:16rpx;}
.content .item .f1{width:100%;padding: 16rpx 20rpx;color: #666;border-bottom: 1px #f5f5f5 solid;}
.content .item .f2{display:flex;padding:20rpx;align-items:center}
.content .item .f2 .t1{display:flex;flex-direction:column;flex:auto}
.content .item .f2 .t1 .x2{ color:#666;font-size:24rpx;padding:8rpx 0}
.content .item .f2 .t1 .x3{display:flex;align-items:center}
.content .item .f2 .t1 .x3 image{width:40rpx;height:40rpx;border-radius:50%;margin-right:4px}
.content .item .f2 .t2{ width:200rpx;text-align:right;display:flex;flex-direction:column;}
.content .item .f2 .t2 .x1{color: #000;height:44rpx;line-height: 44rpx;overflow: hidden;font-size:36rpx;}
.content .item .f2 .t2 .x2{height:44rpx;line-height: 44rpx;overflow: hidden;}

.dfk{color: #ff9900;}
.yfk{color: red;}
.ywc{color: #ff6600;}
.ygb{color: #aaaaaa;}

.data-empty{background:#fff}
</style>