<template>
<view class="container">
	<block v-if="isload">
		<view class="ordertop" :style="'background:url(' + pre_url + '/static/img/ordertop_refund.jpg);background-size:100%'">
			<view class="f1" v-if="detail.refund_status==0">
				<view class="t1">已取消</view>
			</view>
			<view class="f1" v-if="detail.refund_status==1">
				<view class="t1">审核中</view>
			</view>
			<view class="f1" v-if="detail.refund_status==2">
				<view class="t1">审核通过，已退款</view>
			</view>
			<view class="f1" v-if="detail.refund_status==3">
				<view class="t1">驳回</view>
			</view>
      <block v-if="detail.refund_status==4">
        <view class="f1" v-if="!detail.isexpress">
          <view class="t1">审核通过，待退货</view>
          <view class="t2">联系商家进行退货</view>
        </view>
        <view class="f1" v-else>
          <view class="t1">审核通过，已寄回</view>
        </view>
      </block>
      <view class="f1" v-if="shop_order_exchange_product && detail.refund_status==5">
        <view class="t1">商家已收货</view>
      </view>
      <view class="f1" v-if="shop_order_exchange_product && detail.refund_status==6">
        <view class="t1">商家已驳回换货</view>
      </view>
      <view class="f1" v-if="shop_order_exchange_product && detail.refund_status==7">
        <view class="t1">商家已寄出</view>
      </view>
      <view class="f1" v-if="shop_order_exchange_product && detail.refund_status==8">
        <view class="t1">换货完成</view>
      </view>
		</view>
		<view class="btitle flex-y-center" v-if="detail.bid>0 && detail.binfo" @tap="goto" :data-url="'/pagesExt/business/index?id=' + detail.bid">
			<image :src="detail.binfo.logo" style="width:36rpx;height:36rpx;"></image>
			<view class="flex1" decode="true" space="true" style="padding-left:16rpx">{{detail.binfo.name}}</view>
		</view>
		<view class="product">
			<view v-for="(item, idx) in prolist" :key="idx" class="content">
				<view @tap="goto" :data-url="'/pages/shop/product?id=' + item.proid">
					<image :src="item.pic"></image>
				</view>
				<view class="detail">
					<text class="t1">{{item.name}}</text>
					<text class="t2">{{item.ggname}}</text>
					<view class="t3"><text class="x1 flex1">￥{{item.sell_price}}</text><text class="x2">×{{item.refund_num}}</text></view>
				</view>
			</view>
		</view>

    <view class="product" v-if="shop_order_exchange_product && detail.refund_type=='exchange' && newprolist.length > 0">
      <view class="item">
        <text class="t1">换新商品</text>
      </view>
      <view v-for="(item, idx) in newprolist" :key="idx" class="content">
        <view @tap="goto" :data-url="'/pages/shop/product?id=' + item.proid">
          <image :src="item.pic"></image>
        </view>
        <view class="detail">
          <text class="t1">{{item.name}}</text>
          <text class="t2">{{item.ggname}}</text>
          <view class="t3"><text class="x1 flex1">￥{{item.sell_price}}</text><text class="x2">×{{item.num}}</text></view>
        </view>
      </view>
    </view>

		<view class="orderinfo">
			<view class="item">
				<text class="t1">售后单编号</text>
				<text class="t2" user-select="true" selectable="true">{{detail.refund_ordernum}}</text>
			</view>
			<view class="item">
				<text class="t1">订单编号</text>
				<text class="t2" user-select="true" selectable="true" @tap="goto" :data-url="'detail?id='+detail.orderid">{{detail.ordernum}}</text>
			</view>
			<view class="item">
				<text class="t1">申请时间</text>
				<text class="t2">{{detail.createtime}}</text>
			</view>
		</view>
		<view class="orderinfo">
			<view class="item">
				<text class="t1">类型</text>
				<text class="t2 red">{{detail.refund_type_label}}</text>
			</view>
			<view class="item" v-if="detail.refund_type == 'refund' || detail.refund_type == 'return'">
				<text class="t1">申请退款金额</text>
				<text class="t2 red">¥{{detail.refund_money}}</text>
			</view>

			<view class="item">
				<text class="t1">{{markname}}状态</text>
				<text class="t2 grey" v-if="detail.refund_status==0">已取消</text>
				<text class="t2 red" v-if="detail.refund_status==1">审核中</text>
				<text class="t2 red" v-if="detail.refund_status==2">已退款</text>
				<text class="t2 red" v-if="detail.refund_status==3">已驳回</text>
        <block v-if="detail.refund_status==4">
          <text class="t2 red" v-if="!detail.isexpress">审核通过，待退货</text>
          <text class="t2 red" v-else>审核通过，已寄回</text>
        </block>
        <text v-if="shop_order_exchange_product && detail.refund_status==5" class="t2 red">商家已收货</text>
        <text v-if="shop_order_exchange_product && detail.refund_status==6" class="t2 red">商家已驳回</text>
        <text v-if="shop_order_exchange_product && detail.refund_status==7" class="t2 red">商家已寄出</text>
        <text v-if="shop_order_exchange_product && detail.refund_status==8"  class="t2" style="color:green">换货完成</text>
			</view>
      <view class="item" v-if="shop_order_exchange_product && detail.refund_status==6 && detail.exchange_reject_reason">
        <text class="t1">换货驳回原因</text>
        <text class="t2">{{detail.exchange_reject_reason}}</text>
      </view>
			<view class="item">
				<text class="t1">{{markname}}原因</text>
				<text class="t2">{{detail.refund_reason}}</text>
			</view>
			<view class="item" v-if="detail.refund_checkremark">
				<text class="t1">审核备注</text>
				<text class="t2">{{detail.refund_checkremark}}</text>
			</view>
			<view class="item" v-if="detail.refund_pics">
				<text class="t1">图片</text>
				<view id="content_picpreview" class="flex" style="flex-wrap:wrap;padding-top:20rpx">
					<view v-for="(item, index) in detail.refund_pics" :key="index" class="layui-imgbox">
						<view class="layui-imgbox-img"><image :src="item" @tap="previewImage" :data-url="item" mode="widthFix"></image></view>
					</view>
				</view>
			</view>
      <block v-if="detail.refund_status==2 || detail.refund_status==4">
        <view class="item" v-if="detail.return_address">
          <text class="t1">寄回地址</text>
          <text @tap="copy" :data-content="detail.return_province+' '+detail.return_city+' '+detail.return_area+' '+detail.return_address" class="t2">{{detail.return_province}} {{detail.return_city}} {{detail.return_area}} {{detail.return_address}}</text>
        </view>
        <view class="item" v-if="detail.return_name">
          <text class="t1">寄回联系人</text>
          <text @tap="copy" :data-content="detail.return_name+' '+detail.return_tel" class="t2">{{detail.return_name}} {{detail.return_tel}}</text>
        </view>
        <view class="item" v-if="detail.express_com">
          <text class="t1">快递公司</text>
          <text class="t2">{{detail.express_com}}</text>
        </view>
        <view class="item" v-if="detail.express_no">
          <text class="t1">快递单号</text>
          <text class="t2">{{detail.express_no}}</text>
        </view>
      </block>
		</view>
    <view class="orderinfo" v-if="shop_order_exchange_product && detail.refund_type=='exchange' && (detail.refund_status==7 || detail.refund_status==8)">
      <view class="item" v-if="detail.exchange_express_com">
        <text class="t1">换货快递公司</text>
        <text class="t2">{{detail.exchange_express_com}}</text>
      </view>
      <view class="item" v-if="detail.exchange_express_no">
        <text class="t1">换货快递单号</text>
        <text class="t2" style="margin-top: 2rpx">{{detail.exchange_express_no}} </text>
        <view class="btn-class" style="margin-left: 20rpx;" @tap.stop="logistics" :data-expresscom="detail.exchange_express_com" :data-expressno="detail.exchange_express_no" :data-expresscontent="detail.exchange_express_content">查看物流</view>
      </view>
    </view>
		<view class="orderinfo" v-if="detail.freight_type==11">
			<view class="item">
				<text class="t1">发货地址</text>
				<text class="t2">¥{{detail.freight_content.send_address}} - {{detail.freight_content.send_tel}}</text>
			</view>
			<view class="item">
				<text class="t1">收货地址</text>
				<text class="t2">¥{{detail.freight_content.receive_address}} - {{detail.freight_content.receive_tel}}</text>
			</view>
		</view>

    <view style="width:100%;height:calc(160rpx + env(safe-area-inset-bottom));"></view>
		<view class="bottom">
      <block v-if="shop_order_exchange_product && detail.refund_status==7">
        <view class="btn2" @tap="toExchangeConfirm" :data-id="detail.id">确认收货</view>
      </block>
			<block v-if="detail.refund_status==1 || detail.refund_status==4 || detail.refund_status==6">
				<view class="btn2" @tap="toclose" :data-id="detail.id">取消</view>
			</block>
			<view class="btn2" v-if="(detail.refund_status==2 || detail.refund_status==4) && detail.return_address" style="padding: 0 20rpx;" @click="copy" :data-content="detail.return_name+' '+detail.return_tel + ' ' + detail.return_province+detail.return_city+detail.return_area+detail.return_address">复制地址</view>
      <view v-if="detail.refund_status==4 && !detail.isexpress" class="btn2" style="padding: 0 20rpx;" @tap="fahuo" :data-id="detail.id">填写快递单号</view>
		</view>
    <uni-popup id="dialogExpress" ref="dialogExpress" type="dialog">
			<view class="uni-popup-dialog">
				<view class="uni-dialog-title">
					<text class="uni-dialog-title-text">填写快递单号</text>
				</view>
				<view class="uni-dialog-content">
					<view>
						<view class="flex-y-center flex-x-center" style="margin:20rpx 20rpx">
							<text style="font-size:28rpx;color:#000">快递公司：</text>
							<picker @change="expresschange" :value="express_index" :range="expressdata" style="font-size:28rpx;border: 1px #eee solid;padding:10rpx;height:70rpx;border-radius:4px;flex:1">
								<view class="picker">{{expressdata[express_index]}}</view>
							</picker>
						</view> 
						<view class="flex-y-center flex-x-center" style="margin:20rpx 20rpx;">
							<view style="font-size:28rpx;color:#555">快递单号：</view>
							<view class="danhao-input-view">
								<input type="text" v-model="express_no" placeholder="请输入快递单号" @input="setexpressno" style="border:none;outline:none;padding: 10rpx;height:70rpx;border-radius:4px;flex:1;font-size:28rpx;"/>
								<image :src="`${pre_url}/static/img/admin/saoyisao.png`" @click="saoyisao"></image>
							</view>
						</view>
					</view>
				</view>
				<view class="uni-dialog-button-group">
					<view class="uni-dialog-button" @click="dialogExpressClose">
						<text class="uni-dialog-button-text">取消</text>
					</view>
					<view class="uni-dialog-button uni-border-left" @click="refundExpress">
						<text class="uni-dialog-button-text uni-button-color">确定</text>
					</view>
				</view>
			</view>
		</uni-popup>
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
      prodata: '',
      detail: "",
      prolist: "",
      newprolist:"",
      expressdata:[],
      express_index:0,
      express_no:'',
      express_orderid:0,
      shop_order_exchange_product:false,
      markname:'退款',
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
		saoyisao: function (d) {
		  var that = this;
			if(app.globalData.platform == 'h5'){
				app.alert('请使用微信扫一扫功能扫码');return;
			}else if(app.globalData.platform == 'mp'){
				// #ifdef H5
				var jweixin = require('jweixin-module');
				jweixin.ready(function () {   //需在用户可能点击分享按钮前就先调用
					jweixin.scanQRCode({
						needResult: 1, // 默认为0，扫描结果由微信处理，1则直接返回扫描结果，
						scanType: ["qrCode","barCode"], // 可以指定扫二维码还是一维码，默认二者都有
						success: function (res) {
							let serialNumber = res.resultStr; // 当needResult 为 1 时，扫码返回的结果
							let serial = serialNumber.split(",");
							serialNumber = serial[serial.length-1];
							that.express_no = serialNumber;
						},
						fail:function(err){
							app.error(err.errMsg);
						}
					});
				});
				// #endif				
			}else{
				// #ifndef H5
				uni.scanCode({
					success: function (res) {
						that.express_no = res.result;
					},
					fail:function(err){
						app.error(err.errMsg);
					}
				});
				// #endif
			}
		},
		getdata: function () {
			var that = this;
			that.loading = true;
			app.get('ApiOrder/refundDetail', {id: that.opt.id}, function (res) {
				if(res.show_return_component==true){
					app.goto('/pagesZ/order/refundDetail?id='+that.opt.id,'redirect')
					//到退货组件详情
					return;
				}
				that.loading = false;
				that.detail = res.detail;
				that.prolist = res.prolist;
				that.newprolist = res.newprolist;
        that.expressdata = res.expressdata;
        that.shop_order_exchange_product = res.shop_order_exchange_product;
        if(that.detail.refund_type == 'exchange'){
          that.markname = '换货';
        }
				that.loaded();
			});
		},
    toclose: function (e) {
      var that = this;
      var id = e.currentTarget.dataset.id;
      app.confirm('确定要关闭该退款单吗?', function () {
				app.showLoading('提交中');
        app.post('ApiOrder/refundOrderClose', {id: id}, function (data) {
					app.showLoading(false);
          app.success(data.msg);
          setTimeout(function () {
            that.getdata();
          }, 1000);
        });
      });
    },
    orderCollect: function (e) {
      var that = this;
      var orderid = e.currentTarget.dataset.id;
      app.confirm('确定要收货吗?', function () {
				app.showLoading('收货中');
        app.post('ApiOrder/orderCollect', {orderid: orderid}, function (data) {
					app.showLoading(false);
          app.success(data.msg);
          setTimeout(function () {
            that.getdata();
          }, 1000);
        });
      });
    },
		openLocation:function(e){
			var latitude = parseFloat(e.currentTarget.dataset.latitude);
			var longitude = parseFloat(e.currentTarget.dataset.longitude);
			var address = e.currentTarget.dataset.address;
			uni.openLocation({
			 latitude:latitude,
			 longitude:longitude,
			 name:address,
			 scale: 13
			})
		},
    logistics:function(e){
      var express_com = e.currentTarget.dataset.expresscom
      var express_no =  e.currentTarget.dataset.expressno
      var express_content =  e.currentTarget.dataset.expresscontent
      var express_type = '';
      console.log(express_content)
      app.goto('/pagesExt/order/logistics?express_com=' + express_com + '&express_no=' + express_no+'&type='+express_type);
    },
    toExchangeConfirm: function (e) {
      var that = this;
      var orderid = e.currentTarget.dataset.id;
      app.confirm('确定要收货吗?', function () {
        app.showLoading('收货中');
        app.post('ApiOrder/toExchangeConfirm', {orderid: orderid}, function (data) {
          app.showLoading(false);
          app.success(data.msg);
          setTimeout(function () {
            that.getdata();
          }, 1000);
        });
      });
    },
    fahuo:function(e){
      var that = this;
      that.express_orderid = e.currentTarget.dataset.id;
    	that.$refs.dialogExpress.open();
    },
    dialogExpressClose:function(){
    	this.$refs.dialogExpress.close();
    },
    expresschange:function(e){
    	this.express_index = e.detail.value;
    },
    setexpressno:function(e){
    	this.express_no = e.detail.value;
    },
    refundExpress: function () {
      var that = this;
    	if(that.isloading) return;
      var orderid = that.express_orderid;
    	app.showLoading('提交中');
      var express_com = that.expressdata && that.expressdata[that.express_index]?that.expressdata[that.express_index]:'';
	  if(!that.express_no){
	  		  app.error('请输入快递单号');
	  		  return;
	  }
      var data = {
        orderid: orderid,
        express_com:express_com,
        express_no:that.express_no,
      }
	  	that.isloading = 1;
      app.post('ApiOrder/refundExpress', data, function (res) {
    	app.showLoading(false);
        if(res.status == 1){
          app.success(res.msg);
          that.$refs.dialogExpress.close();
          setTimeout(function () {
          	that.getdata();
          }, 800)
        }else{
          app.alert(res.msg);
          that.isloading = 0;
        }
      });
    },
    copy:function(e){
      var content = e.currentTarget.dataset.content;
      if(content){
        uni.setClipboardData({
          data: content,
          success:function(){
            app.success('复制成功')
          }
        });
      }
    }
  }
};
</script>
<style>
.ordertop{width:100%;height:220rpx;padding:50rpx 0 0 70rpx}
.ordertop .f1{color:#fff}
.ordertop .f1 .t1{font-size:32rpx;height:60rpx;line-height:60rpx}
.ordertop .f1 .t2{font-size:24rpx}

.address{ display:flex;width: 100%; padding: 20rpx 3%; background: #FFF;}
.address .img{width:40rpx}
.address image{width:40rpx; height:40rpx;}
.address .info{flex:1;display:flex;flex-direction:column;}
.address .info .t1{font-size:28rpx;font-weight:bold;color:#333}
.address .info .t2{font-size:24rpx;color:#999}

.product{width:96%;margin:0 2%;border-radius:8rpx;margin-top:16rpx;padding: 14rpx 3%;background: #FFF;}
.product .content{display:flex;position:relative;width: 100%; padding:16rpx 0px;border-bottom: 1px #e5e5e5 dashed;}
.product .content:last-child{ border-bottom: 0; }
.product .content image{ width: 140rpx; height: 140rpx;}
.product .content .detail{display:flex;flex-direction:column;margin-left:14rpx;flex:1}
.product .content .detail .t1{font-size:26rpx;line-height:36rpx;margin-bottom:10rpx;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:2;overflow:hidden;}
.product .content .detail .t2{height: 46rpx;line-height: 46rpx;color: #999;overflow: hidden;font-size: 26rpx;}
.product .content .detail .t3{display:flex;height:40rpx;line-height:40rpx;color: #ff4246;}
.product .content .detail .x1{ flex:1}
.product .content .detail .x2{ width:100rpx;font-size:32rpx;text-align:right;margin-right:8rpx}
.product .content .comment{position:absolute;top:64rpx;right:10rpx;border: 1px #ffc702 solid; border-radius:10rpx;background:#fff; color: #ffc702;  padding: 0 10rpx; height: 46rpx; line-height: 46rpx;}
.product .content .comment2{position:absolute;top:64rpx;right:10rpx;border: 1px #ffc7c2 solid; border-radius:10rpx;background:#fff; color: #ffc7c2;  padding: 0 10rpx; height: 46rpx; line-height: 46rpx;}

.orderinfo{width:96%;margin:0 2%;border-radius:8rpx;margin-top:16rpx;padding: 14rpx 3%;background: #FFF;}
.orderinfo .item{display:flex;width:100%;padding:20rpx 0;border-bottom:1px dashed #ededed;overflow:hidden}
.orderinfo .item:last-child{ border-bottom: 0;}
.orderinfo .item .t1{width:200rpx;flex-shrink:0}
.orderinfo .item .t2{flex:1;text-align:right}
.orderinfo .item .red{color:red}
.orderinfo .item .grey{color:grey}

.bottom{ width: 100%; padding: 16rpx 20rpx;background: #fff; position: fixed; bottom: 0px;left: 0px;display:flex;justify-content:flex-end;align-items:center;}

.btn1{margin-left:20rpx;width:160rpx;height:60rpx;line-height:60rpx;color:#fff;border-radius:3px;text-align:center}
.btn2{margin-left:20rpx;min-width:160rpx;height:60rpx;line-height:60rpx;color:#333;background:#fff;border:1px solid #cdcdcd;border-radius:3px;text-align:center}
.btn3{position:absolute;top:60rpx;right:10rpx;font-size:24rpx;width:120rpx;height:50rpx;line-height:50rpx;color:#333;background:#fff;border:1px solid #cdcdcd;border-radius:3px;text-align:center}
.btn4{border: none;}
.btitle{ width:100%;height:100rpx;background:#fff;padding:0 20rpx;border-bottom:1px solid #f5f5f5}
.btitle .comment{border: 1px #ffc702 solid;border-radius:10rpx;background:#fff; color: #ffc702;  padding: 0 10rpx; height: 46rpx; line-height: 46rpx;}
.btitle .comment2{border: 1px #ffc7c0 solid;border-radius:10rpx;background:#fff; color: #ffc7c0;  padding: 0 10rpx; height: 46rpx; line-height: 46rpx;}

.layui-imgbox{margin-right:16rpx;margin-bottom:10rpx;font-size:24rpx;position: relative;}
.layui-imgbox-img{display: block;width:200rpx;height:200rpx;padding:2px;border: #d3d3d3 1px solid;background-color: #f6f6f6;overflow:hidden}
.layui-imgbox-img>image{max-width:100%;}

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

.layui-imgbox{margin-right:16rpx;margin-bottom:10rpx;font-size:24rpx;position: relative;}
.layui-imgbox-img{display: block;width:200rpx;height:200rpx;padding:2px;border: #d3d3d3 1px solid;background-color: #f6f6f6;overflow:hidden}
.layui-imgbox-img>image{max-width:100%;}
.layui-imgbox-repeat{position: absolute;display: block;width:32rpx;height:32rpx;line-height:28rpx;right: 2px;bottom:2px;color:#999;font-size:30rpx;background:#fff}
.uploadbtn{position:relative;height:200rpx;width:200rpx}
.btn-class{height:45rpx;line-height:45rpx;color:#333;background:#fff;border:1px solid #cdcdcd;border-radius:3px;text-align:center;padding: 0 15rpx;flex-shrink: 0;margin: 0 0 0 10rpx;font-size:24rpx;}
</style>
