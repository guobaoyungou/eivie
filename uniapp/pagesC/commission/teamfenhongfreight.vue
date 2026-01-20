<template>
<view class="container">
	<block v-if="isload">
		<view class="topfix">
			<view class="toplabel">
				<text class="t1">分红订单（{{count}}）</text>
				<text class="t2">预计：+{{commissionyj}}元</text>
			</view>
			<dd-tab :itemdata="['待结算','已结算']" :itemst="['1','2']" :st="st" :isfixed="false" @changetab="changetab"></dd-tab>
		</view>
		<view style="margin-top:190rpx"></view>
		<block v-if="datalist && datalist.length>0">
		<view class="content">
			<block v-if="st==1">
			<view v-for="(item, index) in datalist" :key="index" class="item">
				<view class="f1"><text>{{item.ordernum}}</text></view>
				<view class="f2">
					<view class="t1">
						<text class="x1">{{item.name}} ×{{item.num}}</text>
						<text class="x2" style="font-size:28rpx" :style="{color:t('color1')}">￥{{item.real_totalprice}}</text>
						<text class="x2">{{dateFormat(item.createtime)}}</text>
						<view class="x3"><image :src="item.headimg"></image>{{item.nickname}}</view>
					</view>
					<view class="t2">
						<text class="x1">+{{item.commission}}</text>
						<text class="dior-sp6 yfk" v-if="item.status==1 || item.status==2">已付款</text>
						<text class="dior-sp6 dfk" v-if="item.status==0">待付款</text>
						<text class="dior-sp6 ywc" v-if="item.status==3">已完成</text>
						<text class="dior-sp6 ygb" v-if="item.status==4">已关闭</text>
						<text class="dior-sp6 ygb" v-if="item.refund_money > 0">退款/售后</text>
					</view>
				</view>
			</view>
			</block>
			<block v-if="st==2">
			<view v-for="(item, index) in datalist" :key="index" class="item">
				<view class="f1"><text>{{item.remark}} {{dateFormat(item.createtime)}}</text></view>
				<view class="f2">
					<view class="t1">
						<view v-for="(item2, index) in item.oglist" :key="index" class="item2">
							<text class="x1">{{item2.name}} ×{{item2.num}}</text>
							<text class="x2" style="font-size:28rpx" :style="{color:t('color1')}">￥{{item2.real_totalprice}}</text>
							<text class="x2">{{dateFormat(item2.createtime)}}</text>
							<view class="x3"><image :src="item2.headimg"></image>{{item2.nickname}}</view>
						</view>
					</view>
					<view class="t2">
						<text class="x1">+{{item.commission}}</text>
					</view>
				</view>
			</view>
			</block>
		</view>
		</block>
		<view style="width:100%;height:20rpx"></view>
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

      st: '1',
			count:0,
      commissionyj: 0,
      pagenum: 1,
      datalist: [],
      nodata: false,
      nomore: false
    };
  },

  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.getdata();
  },
	onPullDownRefresh: function () {
		this.pagenum = 1;
		this.datalist = [];
		this.getdata();
	},
  onReachBottom: function () {
    if (!this.nodata && !this.nomore && this.st !=1) {
      this.pagenum = this.pagenum + 1;
      this.getdata();
    }
  },
  methods: {
		getdata: function () {
			var that = this;
      var pagenum = that.pagenum;
      var st = that.st;
			that.loading = true;
			that.nodata = false;
      that.nomore = false;
			app.get('ApiAgent/teamfenhongfreight',{st:st,pagenum: pagenum},function(res){
				that.loading = false;
				var data = res.datalist;
        if (pagenum == 1) {
					if(st == 1){
						that.commissionyj = res.commissionyj;
						that.count = res.count;
					}
          that.datalist = data;
          if (data.length == 0) {
            that.nodata = true;
          }
		  uni.setNavigationBarTitle({
		  	title: that.t('运费补贴记录')
		  });
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
      this.pagenum = 1;
      this.st = st;
      this.datalist = [];
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
.topfix{width: 100%;position:relative;position:fixed;background: #f9f9f9;top:var(--window-top);z-index:11;}
.toplabel{width: 100%;background: #f9f9f9;padding: 20rpx 20rpx;border-bottom: 1px #e3e3e3 solid;display:flex;}
.toplabel .t1{color: #666;font-size:30rpx;flex:1}
.toplabel .t2{color: #666;font-size:30rpx;text-align:right}

.content{ width:100%;}
.content .item{width:94%;margin-left:3%;border-radius:10rpx;background: #fff;margin-bottom:16rpx;}
.content .item .f1{width:100%;padding: 16rpx 20rpx;color: #666;border-bottom: 1px #f5f5f5 solid;}
.content .item .f2{display:flex;padding:20rpx;align-items:center}
.content .item .f2 .t1{display:flex;flex-direction:column;flex:auto}
.content .item .f2 .t1 .item2{display:flex;flex-direction:column;flex:auto;margin:10rpx 0;padding:10rpx 0;border-bottom:1px dotted #f5f5f5}
.content .item .f2 .t1 .x2{color:#999;font-size:24rpx;height:40rpx;line-height:40rpx}
.content .item .f2 .t1 .x3{display:flex;align-items:center}
.content .item .f2 .t1 .x3 image{width:40rpx;height:40rpx;border-radius:50%;margin-right:4px}
.content .item .f2 .t2{ width:360rpx;text-align:right;display:flex;flex-direction:column;}
.content .item .f2 .t2 .x1{color: #000;height:44rpx;line-height: 44rpx;overflow: hidden;font-size:36rpx;}
.content .item .f2 .t2 .x2{height:44rpx;line-height: 44rpx;overflow: hidden;}

.dfk{color: #ff9900;}
.yfk{color: red;}
.ywc{color: #ff6600;}
.ygb{color: #aaaaaa;}

</style>