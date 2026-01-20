<template>
<view class="container">
	<block v-if="isload">
		<form @submit="topay">

		<view class="buydata">
      <view class="btitle">
      	<image class="img" :src="pre_url+'/static/img/ico-shop.png'" />{{business.name}}
      </view>
			<view class="bcontent">
				<view class="product">
          <block v-for="(item, index) in prodata" :key="index">
            <view class="item flex">
              <view class="img">
                <image class="img" :src="item.pic"></image>
              </view>
              <view class="info flex1">
                <view class="f1">{{item.name}}</view>
                <view class="f3">
                  ￥{{item.sell_price}}
                  <text style="padding-left:20rpx"> × 1</text>
                </view>
              </view>
            </view>
          </block>
				</view>

				<view class="price">
					<text class="f1">订单金额</text>
					<text class="f2">¥{{product_price}}</text>
				</view>
			</view>
		</view>

		<view style="width: 100%;height:calc(110rpx + env(safe-area-inset-bottom));"></view>
		<view class="footer flex notabbarbot">
			<view class="text1 flex1">总计：
				<text style="font-weight:bold;font-size:36rpx">￥{{totalprice}}</text>
			</view>
			<button class="op" form-type="submit" :style="{background:'linear-gradient(-90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'}">提交订单</button>
		</view>
		</form>

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

			business:{},
      totalprice: '0.00',
      product_price: 0,
      isload: 0,
      prodata:{},
      product: "",
      totalnum: "",
      bid:0,
      gbid:0

    };
  },

  onLoad: function (opt) {
		var that = this;
		var opt  = app.getopts(opt);
		if(opt && opt.bid){
		  that.bid = opt.bid;
		}
    if(opt && opt.gbid){
      that.gbid = opt.gbid;
    }
		that.opt = opt;
		that.getdata();
  },
	onPullDownRefresh: function () {
		this.getdata();
	},
  methods: {
		getdata: function () {
			var that = this; //获取产品信息
			that.loading = true;
			app.get('ApiGiftBag/buy', {gbid: that.gbid,num: that.opt.num,bid: that.bid}, function (res) {
				that.loading = false;
				if (res.status == 1) {
					that.business = res.business
					
					that.product  = res.product;
					that.prodata  = res.prodata;
					
					that.product_price = res.product_price;
          that.totalprice    = res.product_price;
					that.totalnum      = res.totalnum;
					that.loaded();
				}else{
          app.alert(res.msg, function(){
          	app.goback()
          });
          return;
        }
			});
		},
    //提交并支付
    topay: function (e) {
      var that = this;

			app.showLoading('提交中');
      app.post('ApiGiftBag/createOrder', {
        gbid:that.gbid,
        num: 1,
        bid:that.bid,
      }, function (data) {
				app.showLoading(false);
        if (data.status == 0) {
          app.error(data.msg);
          return;
        }
        app.goto('/pagesExt/pay/pay?id=' + data.payorderid);
      });
    },
  }
}
</script>
<style>

.buydata{width:94%;margin:0 3%;background:#fff;margin-bottom:20rpx;border-radius:20rpx;}

.btitle{width:100%;padding:20rpx 20rpx;display:flex;align-items:center;color:#111111;font-weight:bold;font-size:30rpx}
.btitle .img{width:34rpx;height:34rpx;margin-right:10rpx}

.bcontent{width:100%;padding:0 20rpx}

.product{width:100%;border-bottom:1px solid #f4f4f4} 
.product .item{width:100%; padding:20rpx 0;background:#fff;border-bottom:1px #ededed dashed;}
.product .item:last-child{border:none}
.product .info{padding-left:20rpx;}
.product .info .f1{color: #222222;font-weight:bold;font-size:26rpx;line-height:36rpx;margin-bottom:10rpx;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:2;overflow:hidden;}
.product .info .f2{color: #999999; font-size:24rpx}
.product .info .f3{color: #FF4C4C; font-size:28rpx;display:flex;align-items:center;margin-top:10rpx}
.product .img{ width:140rpx;height:140rpx}
.collage_icon{ color:#fe7203;border:1px solid #feccaa;display:flex;align-items:center;font-size:20rpx;padding:0 6rpx;margin-left:6rpx}

.price{width:100%;padding:20rpx 0;background:#fff;display:flex;align-items:center}
.price .f1{color:#333}
.price .f2{ color:#111;font-weight:bold;text-align:right;flex:1}
.price .f3{width: 24rpx;height:24rpx;}

.scoredk{width:94%;margin:0 3%;margin-bottom:20rpx;border-radius:20rpx;padding:24rpx 20rpx; background: #fff;display:flex;align-items:center}
.scoredk .f1{color:#333333}
.scoredk .f2{ color: #999999;text-align:right;flex:1}

.remark{width: 100%;padding:16rpx 0;background: #fff;display:flex;align-items:center}
.remark .f1{color:#333;width:200rpx}
.remark input{ border:0px solid #eee;height:70rpx;padding-left:10rpx;text-align:right}

.footer {width: 96%;background: #fff;margin-top: 5px;position: fixed;left: 0px;bottom: 0px;padding: 0 2%;display: flex;align-items: center;z-index: 8;box-sizing:content-box}
.footer .text1 {height:110rpx;line-height:110rpx;color: #2a2a2a;font-size: 30rpx;}
.footer .text1  text{color: #e94745;font-size: 32rpx;}
.footer .op{width: 200rpx;height:80rpx;line-height:80rpx;color: #fff;text-align: center;font-size: 30rpx;border-radius:44rpx}

.storeitem{width: 100%;padding:20rpx 0;display:flex;flex-direction:column;color:#333}
.storeitem .panel{width: 100%;height:60rpx;line-height:60rpx;font-size:28rpx;color:#333;margin-bottom:10rpx;display:flex}
.storeitem .panel .f1{color:#333}
.storeitem .panel .f2{ color:#111;font-weight:bold;text-align:right;flex:1}
.storeitem .radio-item{display:flex;width:100%;color:#000;align-items: center;background:#fff;border-bottom:0 solid #eee;padding:8rpx 20rpx;}
.storeitem .radio-item:last-child{border:0}
.storeitem .radio-item .f1{color:#666;flex:1}
.storeitem .radio{flex-shrink:0;width: 32rpx;height: 32rpx;background: #FFFFFF;border: 2rpx solid #BFBFBF;border-radius: 50%;margin-left:30rpx}
.storeitem .radio .radio-img{width:100%;height:100%}

.pstime-item{display:flex;border-bottom: 1px solid #f5f5f5;padding:20rpx 30rpx;}
.pstime-item .radio{flex-shrink:0;width: 32rpx;height: 32rpx;background: #FFFFFF;border: 2rpx solid #BFBFBF;border-radius: 50%;margin-right:30rpx}
.pstime-item .radio .radio-img{width:100%;height:100%}

.uni-popup-dialog {width: 300px;border-radius: 5px;background-color: #fff;}
.uni-dialog-title {display: flex;flex-direction: row;justify-content: center;padding-top: 15px;padding-bottom: 5px;}
.uni-dialog-title-text {font-size: 16px;font-weight: 500;}
.uni-dialog-content {display: flex;flex-direction: row;justify-content: center;align-items: center;padding: 5px 15px 15px 15px;}
.uni-dialog-content-text {font-size: 14px;color: #6e6e6e;}
.uni-dialog-button-group {display: flex;flex-direction: row;border-top-color: #f5f5f5;border-top-style: solid;border-top-width: 1px;}
.uni-dialog-button {display: flex;flex: 1;flex-direction: row;justify-content: center;align-items: center;height: 45px;}
.uni-border-left {border-left-color: #f0f0f0;border-left-style: solid;border-left-width: 1px;}
.uni-dialog-button-text {font-size: 14px;}
.uni-button-color {color: #007aff;}
</style>