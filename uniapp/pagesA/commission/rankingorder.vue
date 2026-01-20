<template>
<view class="container">
	<block v-if="isload">
		<view class="order-content">
			<block v-for="(item, index) in datalist" :key="index">
				<view class="order-box" @tap="goto" :data-url="'detail?id=' + item.id">
					<view class="head" style="justify-content: space-between;">
						<view class="f1" v-if="item.ordernum" >订单号:{{item.ordernum}}</view>
						<view class="f1" v-else><image :src="item.binfo.logo" class="logo-row"></image> {{item.binfo.name}}</view>
					</view>
					<block v-for="(item2, idx) in item.prolist" :key="idx">
						<view class="content" :style="idx+1==item.procount?'border-bottom:none':''">
							<view @tap.stop="goto" :data-url="'/pages/shop/product?id=' + item2.proid">
								<image :src="item2.pic" mode='aspectFill'></image>
							</view>
							<view class="detail">
								<text class="t1">{{item2.name}}</text>
								<text class="t2">{{item2.ggname}}</text>
								<block>
									<view class="t3" v-if="item2.product_type && item2.product_type==2">
										<text class="x1 flex1">{{item2.real_sell_price}}元/斤</text>
										<text class="x2">×{{item2.real_total_weight}}斤</text>
									</view>
									<view class="t3" v-else>
										<text class="x1 flex1">
                      ￥{{item2.sell_price}}
                    </text>
										<text class="x2">×{{item2.num}}</text>
									</view>
								</block>
							</view>
						</view>
					</block>
          <view v-if="item.mid || item.nickname" class="bottom" style="display: flex;">
            <view v-if="item.mid">ID:{{item.mid}}</view>
            <view v-if="item.nickname" style="margin-left: 10rpx;">{{item.nickname}}</view>
          </view>
					<view class="bottom">
						<view>
              共计{{item.procount}}件商品 实付:￥{{item.totalprice}}  
            </view>
					</view>
					<view class="bottom" v-if="item.tips!=''">
						<text style="color:red">{{item.tips}}</text>
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

      datalist: [],
      pagenum: 1,
      nomore: false,
			nodata:false,

      id:0,
      sorttype:0,
    };
  },

  onLoad: function (opt) {
    var that = this;
		var opt = app.getopts(opt);
    that.opt = opt;
    that.id  = opt.id || 0;
    that.sorttype  = opt.sorttype || 0;
		that.getdata();
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
      var sorttype = that.sorttype;
      var data = {
        id:that.id,
        pagenum: pagenum,
        sorttype:sorttype,
      }
      app.post('ApiAgent/rankingorder', data, function (res) {
				that.loading = false;
        if(res.status == 1){
          var data = res.datalist;
          if (pagenum == 1) {
          	that.datalist  = data;
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
        }else{
          app.alert(res.msg)
        }
      });
    },
  }
};
</script>
<style>
.container{ width:100%;}
.topsearch{width:94%;margin:10rpx 3%;}
.topsearch .f1{height:60rpx;border-radius:30rpx;border:0;background-color:#fff;flex:1}
.topsearch .f1 .img{width:24rpx;height:24rpx;margin-left:10px}
.topsearch .f1 input{height:100%;flex:1;padding:0 20rpx;font-size:28rpx;color:#333;}
.order-content{display:flex;flex-direction:column}
.order-box{ width: 94%;margin:10rpx 3%;padding:6rpx 3%; background: #fff;border-radius:8px}
.order-box .head{ display:flex;width:100%; border-bottom: 1px #f4f4f4 solid; height: 70rpx; line-height: 70rpx; overflow: hidden; color: #999;}
.order-box .head .f1{display:flex;align-items:center;color:#333}
.order-box .head image{width:34rpx;height:34rpx;margin-right:4px}
.order-box .head .st0{ width: 140rpx; color: #ff8758; text-align: right; }
.order-box .head .st1{ width: 140rpx; color: #ffc702; text-align: right; }
.order-box .head .st2{ width: 140rpx; color: #ff4246; text-align: right; }
.order-box .head .st3{ width: 140rpx; color: #999; text-align: right; }
.order-box .head .st4{ width: 140rpx; color: #bbb; text-align: right; }

.order-box .content{display:flex;width: 100%; padding:16rpx 0px;border-bottom: 1px #f4f4f4 dashed;position:relative;align-items: center;}
.order-box .content:last-child{ border-bottom: 0; }
.order-box .content image{ width: 140rpx; height: 140rpx;}
.order-box .content .detail{display:flex;flex-direction:column;margin-left:14rpx;flex:1}
.order-box .content .detail .t1{font-size:26rpx;line-height:36rpx;margin-bottom:10rpx;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:2;overflow:hidden;}
.order-box .content .detail .t2{height: 44rpx;line-height: 44rpx;color: #999;overflow: hidden;font-size: 24rpx;}
.order-box .content .detail .t3{display:flex;height:40rpx;line-height:40rpx;color: #ff4246;}
.order-box .content .detail .x1{ flex:1}
.order-box .content .detail .x2{ width:110rpx;font-size:32rpx;text-align:right;margin-right:8rpx}

.order-box .bottom{ width:100%; padding: 10rpx 0px; border-top: 1px #f4f4f4 solid; color: #555;}
.order-box .op{ display:flex;flex-wrap: wrap;justify-content:flex-end;align-items:center;width:100%; padding: 10rpx 0px; border-top: 1px #f4f4f4 solid; color: #555;}

.btn1{margin-left:20rpx; margin-top: 10rpx;max-width:160rpx;height:60rpx;line-height:60rpx;color:#fff;border-radius:3px;text-align:center;padding: 0 20rpx;}
.btn2{margin-left:20rpx; margin-top: 10rpx;max-width:160rpx;height:60rpx;line-height:60rpx;color:#333;background:#fff;border:1px solid #cdcdcd;border-radius:3px;text-align:center;padding: 0 20rpx;}

</style>