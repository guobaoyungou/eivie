<template>
<view class="container">
	<view v-for="(item, index) in datalist" :key="index" class="item">
		<view v-if="item.type=='zhaopin'" :data-url="'/zhaopin/zhaopin/detail?id='+item.id" @tap="goto" class="product-item2">
			<view class="product-pic">
					<image :src="item.product.thumb" mode="widthFix"></image>
			</view> 
			<view class="product-info">
				<view class="p2">{{item.product.title}}</view>
					<view class="p3">
								<text class="t1">{{item.product.cname}}</text>
					</view>
					<view class="p3">
						<text class="t1">{{item.product.salary}}</text>
					</view>
			</view>
		</view>
		<!-- zhaopin end -->
		<view v-else-if="item.type=='qiuzhi'" :data-url="'/zhaopin/qiuzhi/detail?id='+item.id" @tap="goto" class="product-item2">
			<view class="product-pic">
					<image :src="item.product.thumb" mode="widthFix"></image>
			</view>
			<view class="product-info">
				<view class="p2">{{item.product.title}}</view>
					<view class="p3">
							<text class="t1">{{item.product.name}}/{{item.product.age}}岁/{{item.product.sex==1?'男':'女'}}/{{item.product.has_job==1?'在职':'离职'}}</text>
					</view>
					<view class="p3">
						<text class="t1">{{item.product.cnames}}</text>
					</view>
			</view>
		</view>
		<!-- qiuzhi end -->
		<view v-else :data-url="(item.type=='shop'?'/pages/':'/activity/')+ item.type + '/product?id=' + item.proid" @tap="goto" class="product-item2">
			<view class="product-pic">
				<image :src="item.product.pic" mode="widthFix"></image>
			</view> 
			<view class="product-info" v-if="item.type == 'scoreshop'">
				<view class="p1">{{item.product.name}}</view>
				<view class="p2">
					<text class="t1" :style="{color:t('color1')}">{{item.product.score_price}}{{t('积分')}}</text>
					<text class="t2">市场价￥{{item.product.sell_price}}</text>
				</view>
				<view class="p3">
					<text class="t1" v-if="item.product.sales>0">已兑换<text style="font-size:24rpx;color:#f40;padding:0 2rpx;">{{item.product.sales}}</text>件</text>
					<text class="t2" v-else-if="item.product.sellpoint">{{item.product.sellpoint}}</text>
				</view>
			</view>
			<view class="product-info" v-else>
				<view class="p1">{{item.product.name}}</view>
				<view class="p2">
					<text class="t1" :style="{color:t('color1')}"><text style="font-size:22rpx">￥</text>{{item.product.sell_price}}</text>
					<text class="t2" v-if="item.product.market_price*1 > item.product.sell_price*1">￥{{item.product.market_price}}</text>
				</view>
				<view class="p3">
					<text class="t1" v-if="item.product.sales>0">已售<text style="font-size:24rpx;color:#f40;padding:0 2rpx;">{{item.product.sales}}</text>件</text>
					<text class="t2" v-else-if="item.product.sellpoint">{{item.product.sellpoint}}</text>
				</view>
			</view>
		</view>
		<view class="foot">
			<text class="flex1">浏览时间：{{item.createtime}}</text>
			<text class="btn" v-if="item.ismine" @tap="favoritedel" :data-id="item.proid" :data-type="item.type">删除</text>
		</view>
	</view>
	
	<nomore v-if="nomore"></nomore>
	<nodata v-if="nodata"></nodata>
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

      datalist: [],
      pagenum: 1,
      nomore: false,
			nodata:false,
			mid:0
    };
  },

  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.mid = this.opt.mid || 0;
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
      app.post('ApiMy/history', {st: st,pagenum: pagenum,mid:that.mid}, function (res) {
				that.loading = false;
        var data = res.data;
        if (pagenum == 1) {
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
    favoritedel: function (e) {
      var that = this;
      var proid = e.currentTarget.dataset.id;
      var type = e.currentTarget.dataset.type;
      uni.showActionSheet({
        itemList: ['删除该项', '清空全部'],
        success: function (res) {
					if(res.tapIndex >= 0){
						if (res.tapIndex == 1) {
							proid = 'all';
						}
						app.post('ApiMy/historydel', {proid: proid,type: type}, function (data) {
							app.success(data.msg);
							setTimeout(function () {
								that.getdata();
							}, 1000);
						});
					}
        }
      });
    }
  }
};
</script>
<style>
.item{ width:94%;margin:0 3%;padding:0 20rpx;background:#fff;margin-top:20rpx;border-radius:20rpx}
.product-item2 {display:flex;padding: 20rpx 0;border-bottom:1px solid #E6E6E6;}
.product-item2 .product-pic {width: 180rpx;height: 180rpx; background: #ffffff;overflow:hidden}
.product-item2 .product-pic image{width: 100%;height:100%;}
.product-item2 .product-info {flex:1;padding: 5rpx 10rpx;}
.product-item2 .product-info .p1 {word-break: break-all;text-overflow: ellipsis;overflow: hidden;display: block;height: 80rpx;line-height: 40rpx;font-size: 30rpx;color:#111111}
.product-item2 .product-info .p2{font-size: 32rpx;height:40rpx;line-height: 40rpx}
.product-item2 .product-info .p2 .t2 {margin-left: 10rpx;font-size: 26rpx;color: #888;text-decoration: line-through;}
.product-item2 .product-info .p3{font-size: 24rpx;height:50rpx;line-height:50rpx;overflow:hidden}
.product-item2 .product-info .p3 .t1{color:#aaa;font-size:24rpx}
.product-item2 .product-info .p3 .t2{color:#888;font-size:24rpx;}
.foot{ display:flex;align-items:center;width:100%;height:100rpx;line-height:100rpx;color:#999999;font-size:24rpx;}
.foot .btn{ padding:2rpx 10rpx;height:50rpx;line-height:50rpx;color:#FF4C4C}
</style>