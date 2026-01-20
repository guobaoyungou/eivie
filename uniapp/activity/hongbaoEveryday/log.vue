<template>
<view class="container">
	<block v-if="isload">
		<view class="myscore" :style="{background:t('color1')}">
			<view class="f1">我的补贴</view>
			<view class="f2">{{money}}<view class="btn-mini" v-if="withdraw" @tap="goto" data-url="withdraw">提现</view></view>
		</view>
			<dd-tab :itemdata="['补贴记录','提现记录']" :itemst="['0','2']" :st="st" :isfixed="false" @changetab="changetab"></dd-tab>
		<view class="content" id="datalist">
			<block v-if="st==0">
			<view class="item" v-for="(item, index) in datalist" :key="index">
				<view class="f1">
						<text class="t1">{{item.remark}}</text>
						<text class="t2">{{item.createtime}}</text>
				</view>
				<view class="f2">
					<block v-if="item.money>0">
						<text class="t1">+{{item.money}}</text>
					</block>
					<block v-else>
						<text class="t2">{{item.money}}</text>
					</block>
				</view>
			</view>
		</block>
		<block v-if="st==2">
		<view v-for="(item, index) in datalist" :key="index" class="item">
			<view class="f1">
					<text class="t1">提现金额:{{item.money}}元 <text v-if="item.score > 0" style="margin-left: 10rpx;">{{t('积分')}}:{{item.score}}</text></text>
					<text class="t2">{{item.createtime}}</text>
			</view>
			<view class="f3">
					<text class="t1" v-if="item.status==0">审核中</text>
					<text class="t1" v-if="item.status==1">已审核</text>
					<text class="t2" v-if="item.status==2">已驳回</text>
					<text class="t1" v-if="item.status==3">已打款</text>
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
      money: 0,
      myscore2: 0,
      nodata: false,
      nomore: false,
			scoreTransfer:false,
			withdraw:false,
    };
  },

  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.st = this.opt.st || 0;
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
      app.post('ApiHongbaoEveryday/log', {st: st,pagenum: pagenum}, function (res) {
				that.loading = false;
        var data = res.datalist;
        if (pagenum == 1) {
					uni.setNavigationBarTitle({
						title: '补贴记录'
					});
					that.money = res.money;
					that.withdraw= res.withdraw;
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
      var st = st;
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
.myscore{width:94%;margin:20rpx 3%;border-radius: 10rpx 56rpx 10rpx 10rpx;position:relative;display:flex;flex-direction:column;padding:70rpx 0}
.myscore .f1{margin:0 0 0 60rpx;color:rgba(255,255,255,0.8);font-size:24rpx;}
.myscore .f2{margin:20rpx 0 0 60rpx;color:#fff;font-size:64rpx;font-weight:bold; position: relative;}

.content{ width:94%;margin:0 3%;}
.content .item{width:100%;margin:20rpx 0;background:#fff;border-radius:5px;padding:20rpx 20rpx;display:flex;align-items:center}
.content .item .f1{display:flex;flex-direction:column; }
.content .item .f1 .t1{color:#000000;font-size:30rpx;word-break:break-all;overflow:hidden;text-overflow:ellipsis;}
.content .item .f1 .t2{color:#666666}
.content .item .f1 .t3{color:#666666}
.content .item .f2{ flex:1;font-size:36rpx;text-align:right}
.content .item .f2 .t1{color:#03bc01}
.content .item .f2 .t2{color:#000000}
.content .item .f3{ flex:1;font-size:30rpx;text-align:right}
.content .item .f3 .t1{color:#03bc01}
.content .item .f3 .t2{color:#000000}

.data-empty{background:#fff}
.btn-mini {right: 32rpx;top: 28rpx;width: 100rpx;height: 50rpx;text-align: center;border: 1px solid #e6e6e6;border-radius: 10rpx;color: #fff;font-size: 24rpx;font-weight: bold;display: inline-flex;align-items: center;justify-content: center;position: absolute;}
</style>