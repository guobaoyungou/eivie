<template>
<view class="container">
	<block v-if="isload">
		<view class="ordertop" :style="'background:url(' + pre_url + '/static/img/ordertop.png);background-size:100%'">
			<view class="f1" v-if="detail.status==0">
				<view class="t1">等待买家付款</view>
				<view class="t2" v-if="detail.pay_end_date">剩余时间：{{detail.pay_end_date}}</view>
			</view>
			<view class="f1" v-if="detail.status==1">
				<view class="t2">待出票</view>
			</view>
			<view class="f1" v-if="detail.status==2">
				<view class="t2">已出票</view>
			</view>
			<view class="f1" v-if="detail.status==3">
				<view class="t1">已完成</view>
			</view>
			<view class="f1" v-if="detail.status==4">
				<view class="t1">已取消</view>
			</view>
			<view class="f1" v-if="detail.status==-1">
				<view class="t1">订单已关闭</view>
			</view>
		</view>


		<view class="product">
			<view  class="content">
				<view class="hotelpic">
					<image :src="product.pic"></image>
				</view>
				<view class="detail">
					<text class="t1">{{product.name}}</text>
					<text class="t2" v-if="product.type == 2">日期：{{detail.visit_date}} - {{detail.visit_end_date}}</text>
					<text class="t2" >￥{{detail.product_price}}</text>
				</view>
			</view>
		</view>
		<view class="orderinfo1">
			<view class="title">游客信息</view>
				<block v-for="item in detail.travellerdata" :key="index">
					<view class="item" v-if="item.traveller_name">
						<view class="f1">
							<label class="t1">姓名</label>
							<text class="t2 flex-bt">{{item.traveller_name}} </text>
						</view>
					</view>
					<view class="item" v-if="item.traveller_mobile">
						<view class="f1">
							<label class="t1">联系方式</label>
							<text class="t2 flex-bt">{{item.traveller_mobile}} </text>
						</view>
					</view>
					<view class="item" v-if="item.traveller_email">
						<view class="f1">
							<label class="t1">Email</label>
							<text class="t2 flex-bt">{{item.traveller_email}} </text>
						</view>
					</view>
					<view class="item" v-if="item.traveller_credentials">
						<view class="f1">
							<label class="t1">证件号码</label>
							<text class="t2 flex-bt">{{item.traveller_credentials}} </text>
						</view>
					</view>
				</block>
		</view>
		
		<view class="orderinfo" v-if="detail.serial_code">	
			<view class="title">入园凭证</view>
			<view class="item flex-bt">
				<text class="t1">凭证码</text>
				<text class="t2">{{detail.serial_code}}</text>
			</view>
			<view class="item-serial-code" v-if="detail.qr_code">
				<image :src="detail.qr_code" mode="mode" style="width: 100%;height: 100%;"></image>
			</view>
		</view>
		
		
		<view class="orderinfo">
			<view class="title">订单信息</view>
			<view class="item flex-bt">
				<text class="t1">订单编号</text>
				<view class="ordernum-info flex-bt">
					<text class="t2" user-select="true" selectable="true">{{detail.ordernum}}</text>
					<view class="btn-class" style="margin-left: 20rpx;" @click="copy" :data-text='detail.ordernum'>复制</view>
				</view>
			</view>
			<view class="item">
				<text class="t1">下单时间</text>
				<text class="t2">{{detail.createtime}}</text>
			</view>
			<view class="item" v-if="detail.status>0 && detail.paytypeid!='4' && detail.paytime">
				<text class="t1">支付时间</text>
				<text class="t2">{{detail.paytime}}</text>
			</view>
			<view class="item" v-if="product.type != 2 && detail.visit_date">
				<text class="t1">游玩时间</text>
				<text class="t2">{{detail.visit_date}}</text>
			</view>
			<view class="item" v-if="detail.status>0 && detail.paytime">
				<text class="t1">支付方式</text>
				<text class="t2">{{detail.paytype}}</text>
			</view>
			<view class="item" v-if="detail.product_price>0 && detail.paytime">
				<text class="t1">商品金额</text>
				<text class="t2 red">¥{{detail.product_price}}</text>
			</view>
			<view class="item" v-if="detail.dedamountdk_money>0 && detail.paytime">
				<text class="t1">抵扣金支付</text>
				<text class="t2 red">-¥{{detail.dedamountdk_money}}</text>
			</view>
			<view class="item" v-if="detail.totalprice>0 && detail.paytime">
				<text class="t1">实付</text>
				<text class="t2 red">¥{{detail.totalprice}}</text>
			</view>

			<view class="item">
				<text class="t1">订单状态</text>
				<text class="t2" v-if="detail.status==0">未付款</text>
				<text class="t2" v-if="detail.status==1">待出票</text>
				<text class="t2" v-if="detail.status==2">已出票</text>
				<text class="t2" v-if="detail.status==3">已完成</text>
				<text class="t2" v-if="detail.status==4">已取消</text>
				<text class="t2" v-if="detail.status==-1">已关闭</text>
			</view>
			<view class="item" v-if="detail.refund_status>0">
				<text class="t1">退款状态</text>
				<text class="t2 red" v-if="detail.refund_status==1">审核中,¥{{detail.refund_money}}</text>
				<text class="t2 red" v-if="detail.refund_status==2">已退款,¥{{detail.refund_money}}</text>
				<text class="t2 red" v-if="detail.refund_status==3">已驳回,¥{{detail.refund_money}}</text>
			</view>
			<view class="item" v-if="detail.refund_status>0">
				<text class="t1">退款原因</text>
				<text class="t2 red">{{detail.refund_reason}}</text>
			</view>
		</view>
		<view style="width:100%;height:calc(160rpx + env(safe-area-inset-bottom));"></view>

		<view class="bottom notabbarbot">
			<block v-if="detail.status==0">
				<view class="btn2" @tap="toclose" :data-id="detail.id">关闭订单</view>
				<view class="btn1" :style="{background:t('color1')}" @tap="goto" :data-url="'/pagesExt/pay/pay?id=' + detail.payorderid">去付款</view>
			</block>
			<block v-if="detail.status==2 || detail.status == 1">
				<view class="btn2" v-if="detail.refund_status==0 || detail.refund_status==3"  @tap="goto" :data-url="'refund?id=' + detail.id + '&price=' + detail.totalprice">申请退款</view>
			</block>
		</view>

	</block>
	<loading v-if="loading"></loading>
	<dp-tabbar :opt="opt"></dp-tabbar>
	<popmsg ref="popmsg"></popmsg>
	<wxxieyi></wxxieyi>
</view>
</template>

<script>
var app = getApp();
var interval = null;

export default {
  data() {
    return {
			opt:{},
			loading:false,
      isload: false,
			menuindex:-1,
			pre_url:app.globalData.pre_url,
      detail: "",	
			product:[]	
    };
  },

  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.getdata();
  },
	onPullDownRefresh: function () {
		this.getdata();
	},
  onUnload: function () {
    clearInterval(interval);
  },
  methods: {
		getdata: function () {
			var that = this;
			that.loading = true;
			app.get('ApiMeituanProduct/orderdetail', {id: that.opt.id}, function (res) {
				uni.stopPullDownRefresh();
				that.loading = false;
        if(res.status == 1){
          that.detail = res.detail;
					if(res.detail && res.detail.product){
						that.product = res.detail.product;
					}
          that.isload = 1;
          that.loaded();
        } else {
          if (res.msg) {
            app.alert(res.msg, function() {
              if (res.url) app.goto(res.url);
            });
          } else if (res.url) {
            app.goto(res.url);
          } else {
            app.alert('您无查看权限');
          }
        }
			});
		},
    toclose: function (e) {
      var that = this;
      var orderid = e.currentTarget.dataset.id;
      app.confirm('确定要关闭该订单吗?', function () {
				app.showLoading('提交中');
        app.post('ApiMeituanProduct/closeOrder', {orderid: orderid}, function (data) {
					app.showLoading(false);
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
.ordertop{width:100%;height:220rpx;padding:50rpx 0 0 70rpx}
.ordertop .f1{color:#fff}
.ordertop .f1 .t1{font-size:32rpx;height:60rpx;line-height:60rpx}
.ordertop .f1 .t2{font-size:24rpx}

.product{width:94%;margin:0 3%;border-radius:8rpx;margin-top:16rpx;padding: 14rpx 3%;background: #FFF;}
.product .content{display:flex;position:relative;width: 100%; padding:16rpx 0px;border-bottom: 1px #e5e5e5 dashed;}
.product .content:last-child{ border-bottom: 0; }
.product .content .hotelpic image{ width: 120rpx; height: 100rpx;}
.product .content .item1{ display: flex; align-items: center;}
.product .content .item1 image{ width: 40rpx; height:40rpx}

.product .content .detail{display:flex;flex-direction:column;margin-left:14rpx;flex:1}
.product .content .detail .t1{font-size:28rpx;line-height:36rpx;margin-bottom:10rpx;color: #000;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:2;overflow:hidden;}
.product .content .detail .t2{height: 46rpx;line-height: 46rpx;color: #999;overflow: hidden;font-size: 26rpx;}
.product .content .detail .t3{display:flex;height:40rpx;line-height:40rpx;color: #ff4246;}
.product .content .detail .x1{ flex:1}
.product .content .detail .x2{ width:100rpx;font-size:32rpx;text-align:right;margin-right:8rpx}
.product .content .comment{position:absolute;top:64rpx;right:10rpx;border: 1px #ffc702 solid; border-radius:10rpx;background:#fff; color: #ffc702;  padding: 0 10rpx; height: 46rpx; line-height: 46rpx;}
.product .content .comment2{position:absolute;top:64rpx;right:10rpx;border: 1px #ffc7c2 solid; border-radius:10rpx;background:#fff; color: #ffc7c2;  padding: 0 10rpx; height: 46rpx; line-height: 46rpx;}


.orderinfo1{width:94%;margin:0 3%;border-radius:8rpx;margin-top:16rpx;padding: 14rpx 3%;background: #FFF;}
.orderinfo1 .item { padding:20rpx 0}
.orderinfo1 .item .f1{ display: flex; font-size: 24rpx; }
.orderinfo1 .item .f1 .t1{ color: #999; width: 150rpx;}
.orderinfo1 .item .f1 .price{ color: red; }
.orderinfo1 .cost-details{color: #06D470;font-size: 24rpx;font-weight: bold;}
.orderinfo1 .cost-details image{width:24rpx;height: 24rpx;transform: rotate(90deg);margin: 0rpx 20rpx 0rpx 10rpx;}


.orderinfo{width:94%;margin:0 3%;border-radius:8rpx;margin-top:16rpx;padding: 14rpx 3%;background: #FFF;}
.orderinfo .title{ font-size: 28rpx; font-weight: bold; margin-bottom: 20rpx;}
.orderinfo .item{display:flex;width:100%;padding:20rpx 0;border-bottom:1px dashed #ededed;}
.orderinfo .item:last-child{ border-bottom: 0;}
.orderinfo .item .t1{width:200rpx; font-size: 24rpx;}
.orderinfo .item .t2{flex:1;text-align:right; font-size: 24rpx;}
.orderinfo .item .red{color:red}
.order-info-title{align-items: center;}
.btn-class{height:45rpx;line-height:45rpx;color:#333;background:#fff;border:1px solid #cdcdcd;border-radius:3px;text-align:center;padding: 0 15rpx;flex-shrink: 0;margin: 0 0 0 10rpx;font-size:24rpx;}
.ordernum-info{align-items: center;}

.bottom{ width: 100%;height:calc(92rpx + env(safe-area-inset-bottom));padding: 0 20rpx;background: #fff; position: fixed; bottom: 0px;left: 0px;display:flex;justify-content:flex-end;align-items:center;}

.btn1{margin-left:20rpx;width:160rpx;height:60rpx;line-height:60rpx;color:#fff;border-radius:3px;text-align:center}
.btn2{margin-left:20rpx;width:160rpx;height:60rpx;line-height:60rpx;color:#333;background:#fff;border:1px solid #cdcdcd;border-radius:3px;text-align:center}
.btn3{position:absolute;top:60rpx;right:10rpx;font-size:24rpx;width:120rpx;height:50rpx;line-height:50rpx;color:#333;background:#fff;border:1px solid #cdcdcd;border-radius:3px;text-align:center}

.btitle{ width:100%;height:100rpx;background:#fff;padding:0 20rpx;border-bottom:1px solid #f5f5f5}
.btitle .comment{border: 1px #ffc702 solid;border-radius:10rpx;background:#fff; color: #ffc702;  padding: 0 10rpx; height: 46rpx; line-height: 46rpx;}
.btitle .comment2{border: 1px #ffc7c0 solid;border-radius:10rpx;background:#fff; color: #ffc7c0;  padding: 0 10rpx; height: 46rpx; line-height: 46rpx;}

.item-serial-code{width: 300rpx;height: 300rpx;margin: 0 auto;}
</style>