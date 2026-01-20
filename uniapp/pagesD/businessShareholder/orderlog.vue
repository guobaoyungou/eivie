<template>
<view class="container">
	<block v-if="isload">
    <view style="width: 100%;position: fixed;top:0;left: 0;height: 290rpx;">
      <view style="margin-top: 2rpx;display: flex;justify-content: space-between;background-color: #fff;padding: 30rpx 20rpx;">
        <view>累计投资：{{count}}</view>
        <view>股东收入：{{count2}}</view>
      </view>
      <view style="margin-top: 4rpx;">
        <dd-tab :itemdata="['今日','本周','本月','累计']" :itemst="['0','1','2','3']" :st="st" :showstatus="showstatus"  :isfixed="false" @changetab="changetab"></dd-tab>
      </view>
      <view style="margin-top: 4rpx;">
        <dd-tab :itemdata="itemdata2" :itemst="itemst2" :st="st2" :showstatus="showstatus2"  :isfixed="false" @changetab="changetab2" ></dd-tab>
      </view>
    </view>
    
    <view style="width: 100%;height: 290rpx;"></view>
		<view class="content" id="datalist">
			<block v-for="(item, index) in datalist" :key="index"> 
			<view class="item" @tap="goto" :data-url="'maidandetail?id=' + item.id">
				<view class="f1">
						<view class="t1">{{item.paytime}}</view>
						<view class="t2">订单编号：{{item.ordernum}}</view>
						<!-- <view class="t2">支付方式：{{item.paytype}}</view> -->
						<view class="t2" >所属商户：{{item.bname}}</view>
            <view class="t2" v-if="item.fenhongmoney>=0">已分红金额：<text :style="'color:'+t('color1')">￥{{item.fenhongmoney}}</text></view>
            <view class="t2" v-if="item.fenhongmoney2>=0">待分红金额：<text :style="'color:'+t('color1')">￥{{item.fenhongmoney2}}</text></view>
				</view>
				<view class="f2">
					<view class="t1">￥{{item.totalprice}}</view>
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
			menuindex:-1,
			
			canwithdraw:false,
			textset:{},
      
      datalist: [],
      pagenum: 1,
			nodata:false,
      nomore: false,
      
      st: 0,
      showstatus:[1,1,1,1],
      
      st2: 0,
      itemdata2:['商城商品'],
      itemst2:['0'],
      showstatus2:[1,1,1],
      
      count:0,
      count2:0
    };
  },

  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.st = this.opt.st || 0;
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
    getdata: function (loadmore) {
			if(!loadmore){
				this.pagenum = 1;
				this.datalist = [];
			}
      var that = this;
			that.nodata = false;
			that.nomore = false;
			that.loading = true;
      app.post('ApiBusinessShareholder/orderlog', {st:  that.st,st2:  that.st2,pagenum:  that.pagenum}, function (res) {
				that.loading = false;
        var data = res.data;
        if (that.pagenum == 1) {
          that.itemdata2 = res.itemdata2;
          that.itemst2  =  res.itemst2;
					that.datalist = data;
          that.count  =  res.count;
          that.count2 =  res.count2;
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
    },
    changetab2: function (st) {
      this.st2 = st;
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
.topsearch{width:94%;margin:10rpx 3%;}
.topsearch .f1{height:60rpx;border-radius:30rpx;border:0;background-color:#fff;flex:1}
.topsearch .f1 .img{width:24rpx;height:24rpx;margin-left:10px}
.topsearch .f1 input{height:100%;flex:1;padding:0 20rpx;font-size:28rpx;color:#333;}
.content{ width:94%;margin:0 3%;}
.content .item{width:100%;margin:20rpx 0;background:#fff;border-radius:5px;padding:20rpx 20rpx;display:flex;align-items:center}
.content .item:last-child{border:0}
.content .item .f1{flex:1;display:flex;flex-direction:column}
.content .item .f1 .t1{color:#000000;font-size:32rpx;height:50rpx;line-height:50rpx;word-break:break-all;overflow:hidden;text-overflow:ellipsis;}
.content .item .f1 .t2{color:#666666}
.content .item .f1 .t3{color:#666666}
.content .item .f2{ font-size:36rpx;text-align:right}
.content .item .f2 .t1{color:#03bc01}
.content .item .f2 .t2{color:#000000}
.content .item .f3{ flex:1;font-size:32rpx;text-align:right}
.content .item .f3 .t1{color:#03bc01}
.content .item .f3 .t2{color:#000000}
</style>