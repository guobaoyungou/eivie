<template>
<view class="container">
	<block v-if="isload">
		<dd-tab :itemdata="['全部','待付款','充值中','已完成','已关闭']" :itemst="['all','0','1','3','4']" :st="st" :isfixed="true" @changetab="changetab"></dd-tab>
		<view style="width:100%;height:100rpx"></view>
		<view class="order-content flex-col">
			<block v-for="(item, index) in datalist" :key="index">
				<view class="options-view">
					<!-- <view class="img-view"><image :src="typeData.imgurl"></image></view> -->
					<view class="info-view">
						<view class="type-name">{{item.type_name}}</view>
						<view class="recharge-number-text">
							{{item.recharge_number}} | {{item.company}}
						</view>
						<view class="time-text">{{ getTime(item.createtime) }}</view>
					</view>
					<view class="price-view">
						<view class="success-text" v-if="item.status == 0">待付款</view>
						<view class="success-text" v-if="item.status == 2">充值中</view>
						<view class="success-text" v-if="item.status == 4">已关闭</view>
						<view class="success-text" v-if="item.status == 5">已退款</view>
						<view class="success-text" v-if="item.status == 1">充值中</view>
						<view class="success-text" style="color: #e8b200;" v-if="item.status == 3">充值成功</view>
						<view class="price-num flex-y-center">
							<view>￥{{item.totalprice}}</view>
							<view class="discount-text" style='margin-left: 10rpx;' v-if="Number(item.pay_money - item.totalprice).toFixed(2) > 0 && item.status == 0">优惠: -{{Number(item.pay_money - item.totalprice).toFixed(2)}}</view>
						</view>
						<view class="discount-text" v-if="Number(item.pay_money - item.totalprice).toFixed(2) > 0 && item.status != 0">优惠: -{{Number(item.pay_money - item.totalprice).toFixed(2)}}</view>
						<view class="btn1" :style="{background:t('color1')}" @click="topay(item)" v-if="item.status == 0">去支付</view>
					</view>
				</view>
			</block>
		</view>
		<nomore v-if="nomore"></nomore>
		<nodata v-if="nodata"></nodata>
	</block>
	<loading v-if="loading"></loading>
	<popmsg ref="popmsg"></popmsg>
	<dp-tabbar :opt="opt" @getmenuindex="getmenuindex"></dp-tabbar>
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
      st: 'all',
      datalist: [],
      pagenum: 1,
      nomore: false,
      nodata: false,
			loading:false,
			nomore:false,
			nodata:true,
			typeData:{},
			orderListTs:'all'
    };
  },
	computed:{
		getTime(timestamp){
			return function (timestamp){
				// const time = '2023-10-01 12:34:56'; // 时间格式为'年-月-日 时:分:秒'
				// const timestamp = Math.floor(new Date(time).getTime() / 1000); // 将时间转换成时间戳（以秒为单位）
				const dateObj = new Date(timestamp * 1000); 
				const year = dateObj.getFullYear(); 
				const month = (dateObj.getMonth() + 1).toString().padStart(2, '0'); 
				const day = dateObj.getDate().toString().padStart(2, '0'); 
				const hours = dateObj.getHours().toString().padStart(2, '0');
				const minutes = dateObj.getMinutes().toString().padStart(2, '0'); 
				const seconds = dateObj.getSeconds().toString().padStart(2, '0'); 
				const formattedDate = `${year}-${month}-${day} ${hours}:${minutes}:${seconds}`;
				return formattedDate;
			}
		}
	},
  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		if(opt.type){
			this.typeData.type = opt.type;
		}else{
			this.typeData = JSON.parse(decodeURIComponent(opt.item));
		}
		this.getdata(this.typeData.type)
  },
	// onPullDownRefresh: function () {
	// 	this.getdata(this.typeData.type);
	// },
 //  onReachBottom: function () {
 //    if (!this.nodata && !this.nomore) {
 //      this.pagenum = this.pagenum + 1;
 //      this.getdata(true);
 //    }
 //  },
	// onNavigationBarSearchInputConfirmed:function(e){
	// 	this.searchConfirm({detail:{value:e.text}});
	// },
  methods: {
		topay(item) {
			var that = this;
			app.showLoading('提交中');
			if(item.payorderid){
				app.showLoading(false);
				app.goto('/pagesExt/pay/pay?id=' + item.payorderid);
			}
		},
    changetab: function (st) {
			this.st = st;
      uni.pageScrollTo({
        scrollTop: 0,
        duration: 0
      });
      this.getdata(this.typeData.type);
    },
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
      app.post('ApiLivepay/orderlist', {st:this.st,type:this.typeData.type}, function (res) {
				that.loading = false;
				uni.stopPullDownRefresh();
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
    }
  },
	onPullDownRefresh: function(e) {
		this.getdata(this.typeData.type);
	},
};
</script>
<style>
	.order-content{width: 94%;margin:0 auto;padding-bottom: 120rpx;}
	.order-content .options-view{width: 100%;border-radius:16rpx;padding:25rpx 30rpx; background: #fff;align-items: center;margin-top: 20rpx;
	display: flex;align-items: center;justify-content: flex-start;position: relative;}
	.order-content .options-view .img-view{width: 60rpx;height: 60rpx;}
	.order-content .options-view .img-view image{width: 100%;height: 100%;}
	.order-content .options-view .info-view{padding-left:0rpx;width: 60%;}
	.order-content .options-view .info-view .type-name{font-size: 30rpx;color: #333;font-weight: bold;}
	.order-content .options-view .info-view .recharge-number-text{width: 100%;font-size: 24rpx;color: #a2a2a2;padding: 15rpx 0rpx 10rpx;}
	.order-content .options-view .info-view .time-text{font-size: 24rpx;color: #a2a2a2;}
	.price-view{position: absolute;right: 30rpx;top: 15rpx;display:flex;flex-direction: column;align-items: flex-end;}
	.price-view .success-text{color: #999;}
	.price-view .price-num{color: #ff4246;margin-top: 10rpx;}
	.price-view .discount-text{font-size: 24rpx;color: #7d7d7d;}
	.price-view .btn1{margin-top: 10rpx;max-width: 160rpx;height: 50rpx;line-height: 50rpx;color: #fff;border-radius: 3px;text-align: center;padding: 0 20rpx;}
</style>
