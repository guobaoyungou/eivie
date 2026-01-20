<template>
<view class="container">
	<block v-if="isload">
		<view class="order-content">
			<block v-for="(item, index) in datalist" :key="index">
				<view class="order-box" @tap="goto" :data-url="'detail?id=' + item.id">
					<view class="head">
						<view class="f1" @tap.stop="goto" :data-url="item.bid!=0?'/pages/business/index?bid=' + item.bid:'/pages/index/index'">
							<image :src="item.binfo.logo"></image>
							<text>{{item.binfo.name}}</text>
							<text class="flex1"></text>
							<text class="iconfont iconjiantou" style="color:#999;font-weight:normal"></text>
						</view>
					</view>
					<block v-for="(item2, idx) in item.prolist" :key="idx">
						<view class="content" :style="idx+1==item.procount?'border-bottom:none':''">
							<view class="pic">
								<image :src="item2.pic" class="img"></image>
							</view>
							<view class="detail">
								<text class="t1">{{item2.name}}</text>
								<text class="t2">数量：{{item2.num}}</text>
								<text class="t2">存入时间：{{date(item2.createtime)}}</text>
							</view>
							<view v-if="item2.status==0" class="takeout st0" :data-orderid="item2.id">审核中</view>
							<view v-if="item2.status==1" class="takeout" @tap="takeout" :data-bid="item.bid" :data-orderid="item2.id"><image src="/static/restaurant/deposit_takeout.png" class="img"/>取出</view>
							<view v-if="item2.status==2" class="takeout st2" :data-orderid="item2.id">已取走</view>
							<view v-if="item2.status==3" class="takeout st3" :data-orderid="item2.id">未通过</view>
							<view v-if="item2.status==4" class="takeout st4" :data-orderid="item2.id">已过期</view>
						</view>
					</block>
					<view class="op">
						<view @tap.stop="goto" :data-url="'orderdetail?bid=' + item.bid" class="btn2">寄存记录</view>
						<view @tap.stop="goto" :data-url="'add?bid=' + item.bid" class="btn2">我要寄存</view>
						<view @tap.stop="takeout" :data-bid="item.bid" data-orderid="0" class="btn1" :style="{background:t('color1')}">一键取出</view>
					</view>
				</view>
			</block>
		</view>
		<nomore v-if="nomore"></nomore>
		<nodata v-if="nodata"></nodata>
	</block>
	<loading v-if="loading"></loading>
	<dp-tabbar :opt="opt"></dp-tabbar>
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
			takeoutshow:false,
    };
  },

  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		if(this.opt && this.opt.st){
			this.st = this.opt.st;
		}
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
      app.post('ApiRestaurantDeposit/orderlist', {st: st,pagenum: pagenum}, function (res) {
				that.loading = false;
        var data = res.datalist;
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
    takeout: function (e) {
      var that = this;
      var orderid = e.currentTarget.dataset.orderid;
      var bid = e.currentTarget.dataset.bid;
      app.confirm('确定要取出吗?', function () {
        app.post('ApiRestaurantDeposit/takeout', {bid:bid,orderid: orderid}, function (data) {
					if(data.status== 0){
						app.alert(data.msg);return;
					}
          app.success(data.msg);
          setTimeout(function () {
            that.getdata();
          }, 1000);
        });
      });
    }
  }
};
</script>
<style>
.container{ width:100%;}
.order-content{display:flex;flex-direction:column}
.order-box{ width: 94%;margin:0 3%;margin-top:20rpx;padding:6rpx 0; background: #fff;border-radius:8px}
.order-box .head{ display:flex;width: 94%;margin:0 3%;border-bottom: 1px #f4f4f4 solid; height:90rpx; line-height: 90rpx; overflow: hidden; color: #999;}
.order-box .head .f1{flex:1;display:flex;align-items:center;color:#222;font-weight:bold}
.order-box .head .f1 image{width:56rpx;height:56rpx;margin-right:20rpx;border-radius:50%}
.order-box .head .st0{ width: 140rpx; color: #ff8758; text-align: right; }
.order-box .head .st1{ width: 140rpx; color: #ffc702; text-align: right; }
.order-box .head .st2{ width: 140rpx; color: #ff4246; text-align: right; }
.order-box .head .st3{ width: 140rpx; color: #999; text-align: right; }
.order-box .head .st4{ width: 140rpx; color: #bbb; text-align: right; }

.order-box .content{display:flex;width: 100%; padding:16rpx 0px 16rpx 20rpx;border-bottom: 0 #f4f4f4 dashed;position:relative}
.order-box .content:last-child{ border-bottom: 0; }
.order-box .content .pic{ width: 120rpx; height: 120rpx;}
.order-box .content .pic .img{ width: 120rpx; height: 120rpx;}
.order-box .content .detail{display:flex;flex-direction:column;margin-left:20rpx;flex:1;margin-top:6rpx}
.order-box .content .detail .t1{font-size:28rpx;font-weight:bold;height:40rpx;line-height:40rpx;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:1;overflow:hidden;}
.order-box .content .detail .t2{height: 36rpx;line-height: 36rpx;color: #999;overflow: hidden;font-size: 22rpx;}
.order-box .content .detail .t3{display:flex;height:40rpx;line-height:40rpx;color: #ff4246;}
.order-box .content .takeout{display:flex;align-items:center;justify-content:center;padding:0 24rpx;height:52rpx;position:absolute;top:50%;margin-top:-26rpx;right:0;border-radius:26rpx 0 0 26rpx;background:#FFE8E1;color:#222222;font-size:24rpx;font-weight:bold}
.order-box .content .takeout .img{width:28rpx;height:28rpx;margin-right:6rpx}
.order-box .content .takeout.st0{color:#f55}
.order-box .content .takeout.st2{background:#F7F7F7;color:#BBBBBB}
.order-box .content .takeout.st3{background:#F7F7F7;color:#888}
.order-box .content .takeout.st4{background:#F7F7F7;color:#808080}

.order-box .bottom{ width:100%; padding:20rpx; border-top: 0 #f4f4f4 solid; color: #555;}
.order-box .op{ display:flex;justify-content:flex-end;align-items:center;width:100%; padding:20rpx; border-top: 0 #f4f4f4 solid; color: #555;}

.btn1{margin-left:20rpx;width:200rpx;height:60rpx;line-height:60rpx;color:#fff;border-radius:44rpx;text-align:center;font-weight:bold}
.btn2{margin-left:20rpx;width:200rpx;height:60rpx;line-height:60rpx;color:#333;background:#fff;border:1px solid #cdcdcd;font-weight:bold;border-radius:44rpx;text-align:center}
</style>