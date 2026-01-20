<template>
<view class="container">
	<block v-if="isload">
		<view class="topfix">
		</view>
		<block v-if="datalist && datalist.length>0">
		<view class="content">
			<view v-for="(item, index) in datalist" :key="index" class="item">
				<view class="f1"><text>{{item.ordernum}}</text></view>
				<view class="f2">
					<view class="t1">
						<text class="x1">{{item.name}}</text>
						<text class="x2">{{item.createtime}}</text>
						<view class="x3"><image :src="item.headimg"></image>{{item.nickname}}</view>
					</view>
					<view class="t2">
						<!-- <text class="x1">+{{item.commission}}</text> -->
						<text class="dior-sp6 yfk" v-if="item.status==1 || item.status==2">已付款</text>
						<text class="dior-sp6 dfk" v-if="item.status==0">待付款</text>
						<text class="dior-sp6 ywc">已完成</text>
						<text class="dior-sp6 ygb" v-if="item.status==4">已关闭</text>
						<text class="dior-sp6 ygb" v-if="item.refund_money > 0">退款/售后</text>
					</view>
				</view>
			</view>
		</view>
		</block>
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

      st: 0,
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
    if (!this.nodata && !this.nomore) {
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
			app.get('ApiAgent/orderMendian',{st:st,pagenum: pagenum},function(res){
				that.loading = false;
				var data = res.datalist;
        if (pagenum == 1) {
					that.commissionyj = res.commissionyj;
					that.count = res.count;
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
.content .item .f2 .t1 .x2{ color:#999}
.content .item .f2 .t1 .x3{display:flex;align-items:center}
.content .item .f2 .t1 .x3 image{width:40rpx;height:40rpx;border-radius:50%;margin-right:4px}
.content .item .f2 .t2{ width:300rpx;text-align:right;display:flex;flex-direction:column;}
.content .item .f2 .t2 .x1{color: #000;height:44rpx;line-height: 44rpx;overflow: hidden;font-size:36rpx;}
.content .item .f2 .t2 .x2{height:44rpx;line-height: 44rpx;overflow: hidden;}

.dfk{color: #ff9900;}
.yfk{color: red;}
.ywc{color: #ff6600;}
.ygb{color: #aaaaaa;}

</style>