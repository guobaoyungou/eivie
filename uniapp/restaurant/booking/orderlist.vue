<template>
<view class="container">
	<block v-if="isload">
		<dd-tab :itemdata="['全部','待付款','待审核','已完成','退款']" :itemst="['all','0','1','3','10']" :st="st" :isfixed="true" @changetab="changetab"></dd-tab>
		<view class="order-content">
			<block>
				<view class="order-box" v-for="(item2, idx) in datalist" :key="idx">
					<block>
						<view class="content" @tap="goto" :data-url="'detail?id=' + item2.id">
							<view class="pic">
								<image :src="item2.binfo.logo" class="img"></image>
							</view>
							<view class="detail">
								<text class="t1">{{item2.binfo.name}}</text>
								<text class="t2">预约桌台：{{item2.tableName}}</text>
								<text class="t2">预约时间：{{item2.booking_time}}</text>
							</view>
						</view>
					</block>
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

			st: 'all',
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
	onShow:function (opt) {
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
      app.post('ApiRestaurantBooking/orderlist', {st: st,pagenum: pagenum}, function (res) {
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
		changetab: function (st) {
		  this.st = st;
		  uni.pageScrollTo({
		    scrollTop: 0,
		    duration: 0
		  });
		  this.getdata(false);
		},
  }
};
</script>
<style>
.container{ width:100%;margin-top:90rpx; }
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

.order-box .bottom{ width:100%; padding:20rpx; border-top: 0 #f4f4f4 solid; color: #555;}
.op{ display:flex;justify-content:flex-end;align-items:center;width:100%; padding:20rpx; border-top: 0 #f4f4f4 solid; color: #555; position: fixed; bottom: 0; left: 0; background-color: #fff;}

.btn1{margin-left:20rpx;width:200rpx;height:60rpx;line-height:60rpx;color:#fff;border-radius:44rpx;text-align:center;font-weight:bold}
.btn2{margin-left:20rpx;width:200rpx;height:60rpx;line-height:60rpx;color:#333;background:#fff;border:1px solid #cdcdcd;font-weight:bold;border-radius:44rpx;text-align:center}
</style>