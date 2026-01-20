<template>
<view class="container">
	<block v-if="isload">
		<view v-if="pid<=0" class="mydedamount" :style="{background:t('color1')}">
			<view class="f1">
				我的抵扣金
			</view>
			<view class="item flex-bt flex-y-center">
				<view class="value">{{mydedamount}}</view>
			</view> 
		</view>
		<view class="content" id="datalist">
			<block v-for="(item, index) in datalist" :key="index"> 
			<view class="item">
				<view class="f2" style="display: flex;justify-content: space-between;">
					<view class="t1" style="color: #000;">
            变动金额：
            <text v-if="item.dedamount>0" style="color: green;">+{{item.dedamount}}</text>
            <text v-else style="color: red;">{{item.dedamount}}</text>
          </view>
					<view v-if="pid<=0 && item.dedamount>0" class="t2" style="color: #000;">剩余变动金额：{{item.dedamount2}}</view>
				</view>
        <view class="f1">
        	<view class="t2">来源：{{item.bname}}</view>
        </view>
        <view class="f1">
        	<view class="t2">{{item.remark}}</view>
        </view>
        <view class="f1">
        	<view class="t2">{{item.createtime}}</view>
        </view>
        <view class="f1" v-if="pid<=0 && item.dedamount>0">
        	<view class="btn2" @tap='goto' :data-url="'dedamountlog?pid='+item.id" style="margin: 0 auto;">查看变动金额使用记录</view>
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
      mydedamount: 0,
      nodata: false,
      nomore: false,
			set:{},
      
      pid:0,
    };
  },

  onLoad: function (opt) {
		this.opt = app.getopts(opt);
    this.pid = this.opt.pid || 0;
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
      app.post('ApiMy/dedamountlog', {pagenum: pagenum,pid:that.pid}, function (res) {
				that.loading = false;
        if(that.pid>0){
          uni.setNavigationBarTitle({
          	title: '剩余变动金额变动记录'
          });
        }
        var data = res.data;
        if (pagenum == 1) {
					that.mydedamount = res.mydedamount;
					that.datalist = data;
					that.set = res.set;
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
.mydedamount{width:94%;margin:20rpx 3%;border-radius: 10rpx 56rpx 10rpx 10rpx;position:relative;display:flex;flex-direction:column;padding:70rpx 0}
.mydedamount .f1{margin:0 0 0 60rpx;color:rgba(255,255,255,0.8);font-size:24rpx;}
.mydedamount .f2{margin:20rpx 0 0 60rpx;color:#fff;font-size:64rpx;font-weight:bold; position: relative;}

.mydedamount .item {min-width: 50%;color: #FFFFFF;padding: 0 10%;margin-top: 30rpx;}
.mydedamount .item .label{font-size: 26rpx}
.mydedamount .item .value{font-size: 44rpx;margin-top: 10rpx;font-weight: bold;}

.mydedamount  .btn{height: 50rpx;text-align: center;border: 1px solid #e6e6e6;border-radius: 10rpx;color: #fff;font-size: 24rpx;font-weight: bold;display: inline-flex;align-items: center;justify-content: center;margin-left: 10rpx;padding: 0 10rpx;}

.content{ width:94%;margin:0 3%;}
.content .item{width:100%;margin:20rpx 0;background:#fff;border-radius:5px;padding:20rpx 20rpx;line-height: 40rpx;}
.content .item .f1{flex:1;display:flex;flex-direction:column;margin: 20rpx auto;}
.content .item .f1 .t1{color:#000000;font-size:30rpx;word-break:break-all;overflow:hidden;text-overflow:ellipsis;}
.content .item .f1 .t2{color:#666666}
.content .item .f1 .t3{color:#666666}
.content .item .f2{ flex:1;font-size:32rpx;text-align:right}
.content .item .f2 .t1{color:#03bc01}
.content .item .f2 .t2{color:#000000}
.content .item .f3{ flex:1;font-size:32rpx;text-align:right}
.content .item .f3 .t1{color:#03bc01}
.content .item .f3 .t2{color:#000000}

.data-empty{background:#fff}
.btn-mini {right: 32rpx;top: 28rpx;width: 100rpx;height: 50rpx;text-align: center;border: 1px solid #e6e6e6;border-radius: 10rpx;color: #fff;font-size: 24rpx;font-weight: bold;display: inline-flex;align-items: center;justify-content: center;position: absolute;}
.btn-xs{min-width: 100rpx;height: 50rpx;line-height: 50rpx;text-align: center;border: 1px solid #e6e6e6;border-radius: 10rpx;color: #fff;font-size: 24rpx;font-weight: bold;margin-bottom:10rpx;padding: 0 20rpx;}

.btn2{margin-left:20rpx; margin-top: 10rpx;max-width:380rpx;height:60rpx;line-height:60rpx;color:#333;background:#fff;border:1px solid #cdcdcd;border-radius:3px;text-align:center;padding: 0 20rpx;}

</style>