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
		<!-- car_hailing start -->
		
			<view  class="module" v-else-if="item.type=='car_hailing'" >
				<view class="module_data" @tap="goto" :data-url="'/carhailing/product?id='+item.product.id+'&dateIndex=0	'">
					<image :src="item.product.pic" class="module_img" alt=""/>
					<view class="module_content">
						<view class="module_title">{{item.product.name}}</view>
						<view class="module_item"  v-if="item.product.cid ==2"> <view class="module_time">{{item.product.starttime}}~{{item.product.endtime}}</view></view>
						<view class="module_item" v-if="item.product.cid != 2 ">
							<text v-if="item.product.cid==1">租车</text><text v-else>包车</text>费用：{{item.product.sell_price}}元 /天</view>
						<view class="module_item" v-else>拼车费用：{{item.product.sell_price}}元 /人</view>
						<view class="module_item" v-if="area !==''">所在城市：{{area}}</view>
					</view>
					<view v-if="item.product.cid ==2">
						<view class="module_btn module_end" v-if="item.product.isend">已结束</view>
						<view class="module_btn" v-else-if="item.product.leftnum > 0">预约</view>
						<view class="module_btn module_end" v-else>满员</view>
					</view>
					<view v-else>
						<view class="module_btn " ><text v-if="item.product.cid ==1">租车</text><text v-if="item.product.cid ==3">包车</text></view>
					</view>
				</view>
				<view class="module_num" v-if="item.product.cid ==2">
					<view class="module_lable">
						<view>当前</view>
						<view>预约</view>
					</view>
					<view class="module_view">
						<block v-for="(item2,index2) in item.product.yyorderlist">
							<image :src="item2.headimg"/>
						</block>
					</view>
					<view class="module_tag" v-if="item.product.leftnum > 0">剩余{{item.product.leftnum}}个名额</view>
					<view class="module_tag module_end" v-else>满员</view>
				</view>
			</view>
		
		<!-- car_hailing end -->
		<view v-else :data-url="(item.type=='shop'?'/pages/':(item.type=='yueke' ||item.type=='cycle' ?'/pagesExt/': (item.type=='huodongbaoming'?'/pagesB/':'/activity/')))+ item.type + '/product?id=' + item.proid" @tap="goto" class="product-item2">
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
								<text class="t1" :style="{color:t('color1')}"><text style="font-size:22rpx">￥</text>{{item.type=='kecheng'?item.product.price:item.product.sell_price}}</text>
								<text class="t2" v-if="item.product.market_price*1 > item.product.sell_price*1">￥{{item.product.market_price}}</text>
					</view>
					<view class="p3">
							<text class="t1" v-if="item.product.sales>0">已售<text style="font-size:24rpx;color:#f40;padding:0 2rpx;">{{item.product.sales}}</text>件</text>
							<text class="t2" v-else-if="item.product.sellpoint">{{item.product.sellpoint}}</text>
					</view>
			</view>
		</view>
		<view class="foot">
			<text class="flex1">收藏时间：{{item.createtime}}</text>
			<text class="btn" @tap="favoritedel" :data-id="item.id">取消收藏</text>
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
			nodata:false,
      nomore: false,
	  area:''
    };
  },
  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.area = app.getCache('user_current_area_show');
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
			that.loading = true;
			that.nomore = false;
			that.nodata = false;
      app.post('ApiMy/favorite', {pagenum: pagenum}, function (res) {
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
      var id = e.currentTarget.dataset.id;
      app.post('ApiMy/favoritedel', {id: id}, function (data) {
        app.success(data.msg);
        setTimeout(function () {
			that.getdata();
			that.onLoad();
        }, 1000);
      });
    }
  }
};
</script>
<style>
.item{ width:94%;margin:0 3%;padding:0 20rpx;background:#fff;margin-top:20rpx;border-radius:20rpx}
.product-item2 {display:flex;padding: 20rpx 0;border-bottom:1px solid #E6E6E6}
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

.module{
		position: relative;
		width: 700rpx;
		padding: 30rpx 30rpx 0px 30rpx;
		box-sizing: border-box;
		border-radius: 20rpx;
		margin: 0 auto 0rpx auto;
		background: #fff;
	}
	.module_data{
		display: flex;
	}
	.module_img{
		height: 130rpx;
		width: 130rpx;
		margin-right: 30rpx;
	}
	.module_content{
		flex: 1;
	}
	.module_btn{
		height: 65rpx;
		padding: 0 40rpx;
		color: #fff;
		font-size: 28rpx;
		display: flex;
		align-items: center;
		justify-content: center;
		border-radius: 100rpx;
		background: #0993fe;
	}
	.module_title{
		font-size: 28rpx;
		color: #333;
	}
	.module_item{
		margin-top: 10rpx;
		color: #999;
		display: flex;
		align-items: center;
		font-size: 24rpx;
	}
	.module_time{
		padding: 0 10rpx;
		height: 35rpx;
		line-height: 33rpx;
		font-size: 22rpx;
		color: #d55c5f;
		border: 1rpx solid #d55c5f;
	}
	.module_num{
		display: flex;
		align-items: center;
		margin-top: 20rpx;
	}
	.module_lable{
		font-size: 24rpx;
		color: #666;
		line-height: 24rpx;
		border-right: 1px solid #e0e0e0;
		padding: 0 15rpx 0 0;
		margin-right: 15rpx;
	}
	.module_view{
		display: flex;
		flex: 1;
		align-items: center;
	}
	.module_view image{
		height: 60rpx;
		width: 60rpx;
		border-radius: 100rpx;
		margin-right: 10rpx;
	}
	.module_tag{
		height: 50rpx;
		background: #fefae8;
		color: #b37e4b;
		font-size: 24rpx;
		padding: 0 10rpx;
		line-height: 50rpx;
	}
	.module_end{
		color: #999;
		background: #f0f0f0;
	}
</style>