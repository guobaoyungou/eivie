<template>
<view class="container">
	<block v-if="isload">
		<view class="myscore" :style="{background:t('color1')}" >
			<view class="f1">我的{{t('银值')}}</view>
			<view class="f2">{{mysilvermoney}}</view>
		</view>
		<view class="content" id="datalist">
			<block v-for="(item, index) in datalist" :key="index"> 
			<view class="item">
				<view class="f1">
						<text class="t1">{{item.remark}}</text>
						<text class="t2">{{item.createtime}}</text>
				</view>
				<view class="f2">
					<block v-if="item.silvermoney>0">
						<text class="t1">+{{item.silvermoney}}</text>
					</block>
					<block v-else>
						<text class="t2">{{item.silvermoney}}</text>
					</block>
				</view>
			</view>
		</block>
		<nodata v-if="nodata"></nodata>
		</view>
		<nomore v-if="nomore"></nomore>
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
      nodata: false,
      nomore: false,
      st: 0,
      pagenum: 1,
      datalist: [],
      mysilvermoney: 0,
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
    app.post('ApiMy/silvermoneylog', {st: st,pagenum: pagenum}, function (res) {
      that.loading = false;
      var data = res.data;
      if (pagenum == 1) {
        uni.setNavigationBarTitle({
          title: that.t('银值') + '明细'
        });
        that.mysilvermoney = res.mysilvermoney;
        that.datalist = data;
        if (data.length == 0) {
          that.nodata = true;
        }
        that.set = res.set;
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
  }
};
</script>
<style>
.myscore{width:94%;margin:20rpx 3%;border-radius: 10rpx 56rpx 10rpx 10rpx;position:relative;display:flex;flex-direction:column;padding:70rpx 0}
.myscore .f1{margin:0 0 0 60rpx;color:rgba(255,255,255,0.8);font-size:24rpx;}
.myscore .f2{margin:20rpx 0 0 60rpx;color:#fff;font-size:64rpx;font-weight:bold; position: relative;}

.content{ width:94%;margin:0 3%;}
.content .item{width:100%;margin:20rpx 0;background:#fff;border-radius:5px;padding:20rpx 20rpx;display:flex;align-items:center}
.content .item .f1{flex:2;display:flex;flex-direction:column}
.content .item .f1 .t1{color:#000000;font-size:30rpx;word-break:break-all;overflow:hidden;text-overflow:ellipsis;}
.content .item .f1 .t2{color:#666666}
.content .item .f1 .t3{color:#666666}
.content .item .f2{ flex:1;font-size:36rpx;text-align:right}
.content .item .f2 .t1{color:#03bc01}
.content .item .f2 .t2{color:#000000}
.content .item .f3{ flex:1;font-size:32rpx;text-align:right}
.content .item .f3 .t1{color:#03bc01}
.content .item .f3 .t2{color:#000000}

.data-empty{background:#fff}
.btn-mini {right: 32rpx;top: 28rpx;width: 100rpx;height: 50rpx;text-align: center;border: 1px solid #e6e6e6;border-radius: 10rpx;color: #fff;font-size: 24rpx;font-weight: bold;display: inline-flex;align-items: center;justify-content: center;position: absolute;}
.btn-xs{min-width: 100rpx;height: 50rpx;line-height: 50rpx;text-align: center;border: 1px solid #e6e6e6;border-radius: 10rpx;color: #fff;font-size: 24rpx;font-weight: bold;margin-bottom:10rpx;padding: 0 20rpx;}
.scoreL{flex: 1;padding-left: 60rpx;color: #FFFFFF;}
.score_txt{color:rgba(255,255,255,0.8);font-size:24rpx;padding-bottom: 14rpx;}
.score{color:#fff;font-size:64rpx;font-weight:bold;}
.scoreR{padding-right: 30rpx;align-self: flex-end;flex-wrap: wrap;}
</style>