<template>
<view class="container">
	<block v-if="isload">
		<form @submit="topay">
		<view class="address-add">
			<view class="linkitem">
				<label style="color: red;" v-if="contact_require==1"> * </label><text class="f1">联 系 人：</text>
				<input type="text" class="input" :value="linkman" placeholder="请输入您的姓名" @input="inputLinkman" placeholder-style="color:#626262;font-size:28rpx;"/>
			</view>
			<view class="linkitem">
				<label style="color: red;" v-if="contact_require==1"> * </label><text class="f1">联系电话：</text>
				<input type="text" class="input" :value="tel" placeholder="请输入您的手机号" @input="inputTel" placeholder-style="color:#626262;font-size:28rpx;"/>
			</view>
		</view>
		<view class="buydata">
			<view class="btitle"><image class="img" :src="pre_url+'/static/img/ico-shop.png'"/>{{business.name}}</view>
			<view class="bcontent">
				<view class="product">
					<view class="item flex">
						<view class="img">
							<image class="img" v-if="guige.pic" :src="guige.pic"></image>
							<image class="img" v-else :src="product.pic"></image>
						</view>
						<view class="info flex1">
							<view class="f1">{{product.name}}</view>
							<view class="f2">规格：{{guige.name}}</view>
							<view class="f2">位置：<text v-for="(item, index) in basan_data" :key="index" style="margin-right: 10rpx;">{{item.name}}</text></view>
							<view class="f3">￥{{guige.sell_price}}<text style="padding-left:20rpx"> × {{sum}}</text></view>
							
						</view>
					</view>
				</view>
				<view class="price">
					<text class="f1">商品金额</text>
					<text class="f2">¥{{product_price}}</text>
				</view>
				<view style="display:none">{{test}}</view>
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
	<wxxieyi></wxxieyi>
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
			test:'test',
		
			business:{},
      productList: [],
      address: [],
      needaddress: 1,
      linkman: '',
      tel: '',
      totalprice: '0.00',
      product_price: 0,
      couponvisible: false,
      pstimeDialogShow: false,
      pstimeIndex: -1,
      product: "",
      guige: "",
      userinfo: "",
      buytype: "",
      weight: "",
			contact_require:0,
      teampid:0,
			sum:0,
			basan_data:""
    };
  },

  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.getdata();
  },
	onPullDownRefresh: function () {
		this.getdata();
	},
  methods: {
		getdata: function () {
			var that = this; //获取产品信息
			that.loading = true;
			app.get('ApiFishPond/buy', {proid: that.opt.proid,ggid: that.opt.ggid,basan: that.opt.basan}, function (res) {
				that.loading = false;
				if (res.status == 0) {
					app.alert(res.msg, function(){
						app.goback()
					});
					return;
				}
				var product = res.product;
				var userinfo = res.userinfo;
				that.product = product;
				that.guige = res.guige;
				that.userinfo = userinfo;
				that.address = res.address;
				that.linkman = res.linkman;
				that.tel = res.tel;
				that.business = res.business;
				that.basan_data = res.basan_data;

				var product_price = res.product_price;
				that.product_price = res.product_price;
				that.sum = res.basan_data_sum;
				that.totalprice = res.totalprice;
				that.loaded();
				//根据商品信息，更新联系人填写要求
				that.contact_require = 1;
			});
		},
    inputLinkman: function (e) {
      this.linkman = e.detail.value
    },
    inputTel: function (e) {
      this.tel = e.detail.value
    },
    //提交并支付
    topay: function (e) {
      var that = this;
      var buytype = this.buytype;
      var linkman = this.linkman;
      var tel = this.tel;
	  
			if(linkman.trim() == '' || tel.trim() == ''){
				return app.error("请填写联系人信息");
			}
			if(tel.trim()!= '' && !app.isPhone(tel)){
				return app.error("请填写正确的手机号");
			}

      var needaddress = that.needaddress;

			app.showLoading('提交中');
      app.post('ApiFishPond/createOrder', {
        proid: that.opt.proid,
        ggid: that.opt.ggid,
				basan: that.opt.basan,
        linkman: linkman,
        tel: tel
      }, function (data) {
				app.showLoading(false);
        if (data.status == 0) {
          return;
        }
        app.goto('/pagesExt/pay/pay?id=' + data.payorderid,'redirectTo');
      });
    }
  }
}
</script>
<style>
.address-add{ width:94%;margin:20rpx 3%;background:#fff;border-radius:20rpx;padding: 20rpx 3%;min-height:140rpx;}
.address-add .f1{margin-right:20rpx}
.address-add .f1 .img{ width: 66rpx; height: 66rpx; }
.address-add .f2{ color: #666; }
.address-add .f3{ width: 26rpx; height: 26rpx;}

.linkitem{width: 100%;padding:1px 0;background: #fff;display:flex;align-items:center}
.linkitem .f1{width:160rpx;color:#111111}
.linkitem .input{height:50rpx;padding-left:10rpx;color:#222222;font-weight:bold;font-size:28rpx;flex:1}

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


.price{width:100%;padding:20rpx 0;background:#fff;display:flex;align-items:center}
.price .f1{color:#333}
.price .f2{ color:#111;font-weight:bold;text-align:right;flex:1}
.price .f3{width: 24rpx;height:24rpx;}


.footer {width: 96%;background: #fff;margin-top: 5px;position: fixed;left: 0px;bottom: 0px;padding: 0 2%;display: flex;align-items: center;z-index: 8;box-sizing:content-box}
.footer .text1 {height:110rpx;line-height:110rpx;color: #2a2a2a;font-size: 30rpx;}
.footer .text1  text{color: #e94745;font-size: 32rpx;}
.footer .op{width: 200rpx;height:80rpx;line-height:80rpx;color: #fff;text-align: center;font-size: 30rpx;border-radius:44rpx}

</style>