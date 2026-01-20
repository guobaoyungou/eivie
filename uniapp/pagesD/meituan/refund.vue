<template>
<view class="container">
	<block v-if="isload">
		<form @submit="formSubmit" @reset="formReset" report-submit="true">
			<view class="form-content">
				<view class="product">
					<view class="content">
						<view @tap="goto" :data-url="'/pagesD/meituan/product?id=' + product.id">
							<image :src="product.pic" class="productimg"></image>
						</view>
						<view class="detail">
							<text class="t1">{{product.name}}</text>
							<text class="t2">数量：{{detail.num}}</text>
							<view class="t3">
								<text class="x1 flex1">￥{{detail.sell_price}}</text>
								<view class="num-wrap">
									<view class="addnum">
										<view class="minus" @tap="gwcminus">
											<image class="img" :src="pre_url+'/static/img/cart-minus.png'"/>
										</view>
										<input class="input" type="number" v-model="refundNum" @blur="gwcinput"></input>
										<view class="plus" @tap="gwcplus">
											<image class="img" :src="pre_url+'/static/img/cart-plus.png'"/>
										</view>
									</view>
									<view class="text-desc">申请数量：最多可申请{{detail.canRefundNum}}件</view>
								</view>
							</view>
						</view>
					</view>
				</view>
			</view>
			<view class="form-content">
				<view class="form-item">
					<text class="label">退款原因</text>
					<view class="input-item">
						<textarea placeholder="请输入原因" placeholder-style="color:#999;" name="reason" @input="reasonInput"></textarea>
					</view>
				</view>
				<view class="form-item">
					<text class="label">退款金额(元)</text>
					<view class="flex">
						<input name="money" @input="moneyInput" type="digit" :value="money" placeholder="请输入退款金额" placeholder-style="color:#999;"></input>
					</view>
				</view>
			</view>
			<button class="btn" @tap="formSubmit" :style="{background:t('color1')}">确定</button>
			<view style="padding-top:30rpx"></view>
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
      orderid: '',
	    ogid:0,
      totalprice: 0,
			order:{},
			detail: {},
			refundNum:0,
			money:'',
			reason:'',
			totalcanrefundnum:0,
      buydialogShow:false,
      proid:0,
      newpro: {},
			product:[],
    };
  },

  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.orderid = this.opt.id;
		this.pre_url = app.globalData.pre_url;
		this.getdata();
  },
	onPullDownRefresh: function () {
		this.getdata();
	},
  methods: {
		getdata: function () {
			var that = this;
			that.loading = true;
			app.post('ApiMeituanProduct/refundinit', {orderid: that.orderid}, function (res) {
				that.loading = false;
				if(res.status == 0) {
					app.alert(res.msg,function(){
						app.goback();return;
					})
				}
		
				that.detail = res.detail;
				that.product = res.product;
				that.totalprice = that.detail.returnTotalprice;
				that.refundNum = that.detail.canRefundNum;
				if(that.totalprice){
					that.money = (that.totalprice).toFixed(2);
				}
				that.loaded();
			});
		},
    formSubmit: function () {
      var that = this;
			if(that.isloading) return;
      var orderid = that.orderid;
      var reason = that.reason;
      var money = parseFloat(that.money);
			var refundNum = that.refundNum;

      if (reason == '') {
        app.alert('请填写退款原因');
        return;
      }

      if (money < 0 || money > parseFloat(that.totalprice)){
        app.alert('退款金额有误');
        return;
      }
			that.isloading = 1;
			app.showLoading('提交中');
      app.post('ApiMeituanProduct/refund', {orderid: orderid,reason: reason,money: money,num:refundNum}, function (res) {
				app.showLoading(false);
        app.alert(res.msg);
        if (res.status == 1) {
          that.subscribeMessage(function () {
            setTimeout(function () {
              app.goto('orderdetail?id='+that.orderid);
            }, 1000);
          });
        }else{
					that.isloading = 0;
        }
      });
    },
		gwcplus: function (e) {
			var maxnum = this.detail.canRefundNum;
			this.refundNum = Math.min(this.refundNum + 1, maxnum);
			this.calculate();
		},
		gwcminus: function (e) {
		    this.refundNum = Math.max(this.refundNum - 1, 1);
		    this.calculate();
		},
		//输入
		gwcinput: function (e) {
		  var maxnum = this.detail.canRefundNum;
		  var num = parseInt(e.currentTarget.dataset.num);
		  if (isNaN(num)) {
					num = 1;
			}

		  num = Math.max(Math.min(num, maxnum), 1);
			this.refundNum = num;
			this.calculate();
		},
    calculate: function () {
			var that = this;
			var refundTotal = that.detail.returnTotalprice;
			var canRefundNum = that.detail.canRefundNum;
			var total = 0;
			if (canRefundNum === 0) {
				total = 0;
			} else {
				// 按比例计算退款金额并保留两位小数
				total = parseFloat((refundTotal / canRefundNum) * that.refundNum);
			}
			if(total > that.detail.returnTotalprice) total = that.detail.returnTotalprice;
			total = total.toFixed(2);
			this.money = total;
		},
		moneyInput: function (e) {
			var newmoney = parseFloat(e.detail.value);
			if (newmoney <= 0 || newmoney > parseFloat(this.totalprice)) {
			  app.error('最大退款金额:'+this.totalprice);
			  return;
			}
			this.money = newmoney;
		},
		reasonInput: function (e) {
			this.reason = e.detail.value;
		}
  }
};
</script>
<style>
	.num-wrap {position: absolute;right: 0;bottom:24rpx;}
	.num-wrap .text-desc { margin-bottom: -60rpx; color: #999; font-size: 24rpx; text-align: right;}
	.addnum {position: absolute;right: 0;bottom:0rpx;font-size: 30rpx;color: #666;width:auto;display:flex;align-items:center}
	.addnum .plus {width:48rpx;height:36rpx;background:#F6F8F7;display:flex;align-items:center;justify-content:center}
	.addnum .minus {width:48rpx;height:36rpx;background:#F6F8F7;display:flex;align-items:center;justify-content:center}
	.product .addnum .img{width:24rpx;height:24rpx}
	.addnum .i {padding: 0 20rpx;color:#2B2B2B;font-weight:bold;font-size:24rpx}
	.addnum .input{flex:1;width:70rpx;border:0;text-align:center;color:#2B2B2B;font-size:24rpx}

	.form-item4{width:100%;background: #fff; padding: 20rpx 20rpx;margin-top:1px}
	.form-item4 .label{ width:150rpx;}
	.layui-imgbox{margin-right:16rpx;margin-bottom:10rpx;font-size:24rpx;position: relative;}
	.layui-imgbox-img{display: block;width:200rpx;height:200rpx;padding:2px;border: #d3d3d3 1px solid;background-color: #f6f6f6;overflow:hidden}
	.layui-imgbox-img>image{max-width:100%;}
	.layui-imgbox-repeat{position: absolute;display: block;width:32rpx;height:32rpx;line-height:28rpx;right: 2px;bottom:2px;color:#999;font-size:30rpx;background:#fff}
	.uploadbtn{position:relative;height:200rpx;width:200rpx}

.product{width:94%;margin:0 3%;border-radius:8rpx;margin-top:16rpx;padding: 14rpx 3%;background: #FFF;}
.product .content{display:flex;position:relative;width: 100%; padding:16rpx 0px;border-bottom: 1px #e5e5e5 dashed; height: 196rpx;}
.product .content:last-child{ border-bottom: 0; }
.product .content .productimg{ width: 140rpx; height: 140rpx;}
.product .content .detail{display:flex;flex-direction:column;margin-left:14rpx;flex:1}
.product .content .detail .t1{font-size:26rpx;line-height:36rpx;height:72rpx;margin-bottom:10rpx;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:2;overflow:hidden;}
.product .content .detail .t2{height: 46rpx;line-height: 46rpx;color: #999;overflow: hidden;font-size: 26rpx;}
.product .content .detail .t3{display:flex;height:40rpx;line-height:40rpx;color: #ff4246; position: relative;}
.product .content .detail .x1{ flex:1}
.product .content .detail .x2{ width:100rpx;font-size:32rpx;text-align:right;margin-right:8rpx}
.product .content .comment{position:absolute;top:64rpx;right:10rpx;border: 1px #ffc702 solid; border-radius:10rpx;background:#fff; color: #ffc702;  padding: 0 10rpx; height: 46rpx; line-height: 46rpx;}
.product .content .comment2{position:absolute;top:64rpx;right:10rpx;border: 1px #ffc7c2 solid; border-radius:10rpx;background:#fff; color: #ffc7c2;  padding: 0 10rpx; height: 46rpx; line-height: 46rpx;}

.form-content{width:94%;margin:16rpx 3%;border-radius:10rpx;display:flex;flex-direction:column;background:#fff;overflow:hidden}
.form-item{ width:100%;padding: 32rpx 20rpx;}
.form-item .label{ width:100%;height:60rpx;line-height:60rpx}
.form-item .input-item{ width:100%;}
.form-item textarea{ width:100%;height:200rpx;border: 1px #eee solid;padding: 20rpx;}
.form-item input{ width:100%;border: 1px #f5f5f5 solid;padding: 10rpx;height:80rpx}
.form-item .mid{ height:80rpx;line-height:80rpx;padding:0 20rpx;}
.btn{ height:100rpx;line-height: 100rpx;width:90%;margin:0 auto;border-radius:50rpx;margin-top:50rpx;color: #fff;font-size: 30rpx;font-weight:bold}
.radio{flex-shrink:0;width: 32rpx;height: 32rpx;background: #FFFFFF;border: 2rpx solid #BFBFBF;border-radius: 50%;margin-right:30rpx;margin-top: 9%;}
.radio .radio-img{width:100%;height:100%}
.but-left-info .radio{flex-shrink:0;width: 32rpx;height: 32rpx;background: #FFFFFF;border: 2rpx solid #BFBFBF;border-radius: 50%;margin-right:10rpx}
.but-left-info .radio .radio-img{width:100%;height:100%}
.but-left-info .text0{color:#666666;font-size:24rpx;}
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
.danhao-input-view{border: 1px #eee solid;display: flex;align-items: center;flex: 1;}
.danhao-input-view image{width: 60rpx;height: 60rpx;}
  .btn-class{height:45rpx;line-height:45rpx;color:#333;background:#fff;border:1px solid #cdcdcd;border-radius:3px;text-align:center;padding: 0 15rpx;flex-shrink: 0;margin: 0 0 0 10rpx;font-size:24rpx;}
</style>

