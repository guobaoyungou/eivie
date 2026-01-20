<template>
<view class="container">
	<block v-if="isload">
		<view class="myscore" :style="{background:t('color1')}">
			<view class="flex1">
				<view class="f1">商家{{t('积分')}}</view>
				<view class="f2">{{mybscore}}</view>
			</view>
			<view class="flex1">
				<view class="f1">平台{{t('积分')}}</view>
				<view class="f2" @tap="goto" data-url="scorelog">{{myscore}}</view>
			</view>
		</view>
		<view class="content" id="datalist">
			<block v-for="(item, index) in datalist" :key="index"> 
			<view class="item" @tap="goto" :data-url="'bscorelog?bid='+item.bid">
				<view class="f1">
					<image :src="item.logo" style="width:60rpx;height:60rpx;border-radius:10rpx;margin-right:10rpx"/>
					<text class="t1">{{item.name}}</text>
				</view>
				<view class="f2">
					<view class="t1">{{item.score}}<text>{{t('积分')}}</text></view>
					<image :src="pre_url+'/static/img/arrowright.png'" style="width:40rpx;height:40rpx;"/>
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
      st: 0,
      datalist: [],
      pagenum: 1,
			myscore:0,
      mybscore: 0,
      nodata: false,
      nomore: false,
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
      app.post('ApiMy/bscore', {st: st,pagenum: pagenum}, function (res) {
				that.loading = false;
        var data = res.data;
        if (pagenum == 1) {
					uni.setNavigationBarTitle({
						title: '我的' + that.t('积分')
					});
					that.mybscore = res.mybscore;
					that.myscore = res.myscore;
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
    changetab: function (e) {
      var st = e.currentTarget.dataset.st;
      this.st = st;
      uni.pageScrollTo({
        scrollTop: 0,
        duration: 0
      });
      this.getdata();
    },
  }
};
</script>
<style>
.myscore{width:94%;margin:20rpx 3%;border-radius: 10rpx 56rpx 10rpx 10rpx;position:relative;display:flex;padding:70rpx 0}
.myscore .f1{margin:0 0 0 60rpx;color:rgba(255,255,255,0.8);font-size:28rpx;}
.myscore .f2{margin:20rpx 0 0 60rpx;color:#fff;font-size:64rpx;font-weight:bold; position: relative;}

.content{ width:94%;margin:0 3%;}
.content .item{width:100%;margin:20rpx 0;background:#fff;border-radius:5px;padding:20rpx 20rpx;display:flex;align-items:center}
.content .item .f1{flex:1;display:flex;align-items:center}
.content .item .f1 .t1{color:#000000;font-size:30rpx;word-break:break-all;overflow:hidden;text-overflow:ellipsis;}
.content .item .f1 .t2{color:#666666}
.content .item .f1 .t3{color:#666666}
.content .item .f2{font-size:36rpx;text-align:right;display:flex;align-items:center}
.content .item .f2 .t1{color:#03bc01}
.content .item .f2 .t2{color:#000000}

.data-empty{background:#fff}
.btn-mini {right: 32rpx;top: 28rpx;width: 100rpx;height: 50rpx;text-align: center;border: 1px solid #e6e6e6;border-radius: 10rpx;color: #fff;font-size: 24rpx;font-weight: bold;display: inline-flex;align-items: center;justify-content: center;position: absolute;}
</style>