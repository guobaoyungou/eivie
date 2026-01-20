<template>
<view class="container">
	<block v-if="isload">
		<dd-tab :itemdata="['全部','已发放','未发放']" :itemst="['0','1','2']" :st="st" :isfixed="false" @changetab="changetab"></dd-tab>
		<view class="content">
			<view v-for="(item, index) in datalist" :key="index" class="item">
			<view class="f2">
				<view class="t1">
					<text class="x1">{{item.remark}}</text>
					<text class="x2">产生时间：{{dateFormat(item.createtime,'Y-m-d H:i')}}</text>
					<view class="x3" v-if="item.frommid>0">来源：<image :src="item.fromheadimg"></image>{{item.fromnickname}}</view>
				</view>
				<view class="t2">
					<text class="x1">+{{item.commission}}</text>
					<block v-if="item.status==0">
						<text class="dior-sp6 yfk" v-if="item.orderstatus==1 || item.orderstatus==2">已付款</text>
						<text class="dior-sp6 dfk" v-if="item.orderstatus==0">待付款</text>
						<text class="dior-sp6 ywc" v-if="item.orderstatus==3">待发放</text>
						<text class="dior-sp6 ygb" v-if="item.orderstatus==4">已关闭</text>
					</block>
					<block v-if="item.status==1">
						<text class="dior-sp6 yfk">已发放</text>
					</block>
				</view>
			</view>
				<!--商品信息-->
				<view class="goodsinfo  flex" v-if="item.goods && item.goods.name">
					<view class="x1 flex">
						<image :src="item.goods.pic" class="img"></image>
						<view style="margin-left: 20rpx;">
							<view class="x1t name" >{{item.goods.name}}</view>
							<view class="x1t" style="color: red;">￥{{item.goods.sell_price}}</view>
							
							<view class="x1t" style="color: #616161;">{{item.goods.createtime}}</view>
						</view>
						
					</view>
					<view>共{{item.goods.num}}件</view>
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
      st: 0,
      datalist: [],
			textset:{},
      pagenum: 1,
    };
  },

  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.st = this.opt.st || 0;
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
      var st = that.st;
      var pagenum = that.pagenum;
			that.loading = true;
			that.nodata = false;
      that.nomore = false;
      app.post('ApiAgent/commissionrecord', {st: st,pagenum: pagenum}, function (res) {
				that.loading = false;
        var data = res.data;
        if (pagenum == 1) {
					that.textset = app.globalData.textset;
					uni.setNavigationBarTitle({
						title: that.t('佣金') + '记录'
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

.goodsinfo{display:flex;flex:auto; justify-content: space-between;align-items: center;border-bottom: 1px #f5f5f5 solid;padding-bottom: 20rpx;padding: 0 20rpx 20rpx 20rpx;}
.goodsinfo .img{width: 140rpx;height: 140rpx;}
.goodsinfo .x1{align-items: center;}
.goodsinfo .x1 .x1t{line-height: 50rpx;font-size: 26rpx;}
.goodsinfo .x1 .name{color: #616161;font-size: 26rpx;width: 80%;}
.goodsinfo .x2{ color:#999}
.goodsinfo .x3{display:flex;align-items:center}

.dfk{color: #ff9900;}
.yfk{color: red;}
.ywc{color: #ff6600;}
.ygb{color: #aaaaaa;}

.data-empty{background:#fff}
</style>