<template>
<view class="container">
	<block v-if="isload">
		<view class="myscore" :style="{background:t('color1')}">
			<view class="f1">
				我的冻结{{t('佣金')}}
			</view>
			<view class="f2">{{myforzengxcommission}}</view>
		</view>
    <dd-tab :itemdata="['全部','释放中','已结束']" :itemst="['all','0','1']" :st="st" :showstatus="showstatus" :isfixed="false" @changetab="changetab"></dd-tab>
		<view class="content" id="datalist">
			<block v-for="(item, index) in datalist" :key="index"> 
			<view class="item">
				<view class="f1">
					<view class="t1">总冻结{{t('佣金')}}：{{item.commission}}</view>
          <view class="t1">未解冻{{t('佣金')}}：{{item.commission2}}</view>
          <block v-if="item.showsendmonth">
            <view  class="t1">总释放月数：{{item.sendmonth}}</view>
            <view  class="t1">已释放月数：{{item.sendmonth2}}</view>
          </block>
          <view class="t1">
            状态：
            <text v-if="item.status == 1">已结束</text>
            <text v-if="item.status == 0" style="color: green;">释放中</text>
          </view>
					<view class="t2">{{item.createtime}}</view>
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
      pagenum: 1,
      nodata: false,
      nomore: false,
      
      st: 'all',
      showstatus:[1,1,1],
      datalist: [],
      myforzengxcommission: 0,
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
      app.post('ApiMy/forzengxcommissionlog', {st: st,pagenum: pagenum}, function (res) {
				that.loading = false;
        var data = res.data;
        if (pagenum == 1) {
					uni.setNavigationBarTitle({
						title: '冻结'+that.t('佣金')
					});
					that.myforzengxcommission = res.myforzengxcommission;
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
      this.st = e;
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

.myscore .item {min-width: 50%;color: #FFFFFF;padding: 0 10%;margin-top: 30rpx;}
.myscore .item .label{font-size: 26rpx}
.myscore .item .value{font-size: 44rpx;margin-top: 10rpx;font-weight: bold;}

.myscore  .btn{height: 50rpx;text-align: center;border: 1px solid #e6e6e6;border-radius: 10rpx;color: #fff;font-size: 24rpx;font-weight: bold;display: inline-flex;align-items: center;justify-content: center;margin-left: 10rpx;padding: 0 10rpx;}

.content{ width:94%;margin:0 3%;}
.content .item{width:100%;margin:20rpx 0;background:#fff;border-radius:5px;padding:20rpx 20rpx;display:flex;align-items:center}
.content .item .f1{flex:1;display:flex;flex-direction:column}
.content .item .f1 .t1{color:#000000;font-size:30rpx;word-break:break-all;overflow:hidden;text-overflow:ellipsis;line-height: 45rpx;}
.content .item .f1 .t2{color:#666666}
.content .item .f1 .t3{color:#666666}
.content .item .f2{ flex:1;font-size:36rpx;text-align:right}
.content .item .f2 .t1{color:#03bc01}
.content .item .f2 .t2{color:#000000}
.content .item .f3{ flex:1;font-size:32rpx;text-align:right}
.content .item .f3 .t1{color:#03bc01}
.content .item .f3 .t2{color:#000000}
</style>