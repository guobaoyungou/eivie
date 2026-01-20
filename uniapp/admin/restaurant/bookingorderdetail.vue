<template>
<view class="container">
	<block v-if="isload">
		<view class="ordertop" :style="'background:url(' + pre_url + '/static/img/ordertop.png);background-size:100%'">
			<view class="f1" v-if="detail.status==0">
				<view class="t1">等待买家付款</view>
				<view class="t2" v-if="djs">剩余时间：{{djs}}</view>
			</view>
			<view class="f1" v-if="detail.status==1">
				<view class="t1">{{detail.paytypeid==4 ? '已选择'+detail.paytype : '已成功付款'}}</view>
			</view>
			<view class="f1" v-if="detail.status==1 && detail.check_status == 0">
				<view class="t1">待审核</view>
			</view>
			<view class="f1" v-if="detail.status==1 && detail.check_status == -1">
				<view class="t1 color-red">驳回预约</view>
			</view>
			<view class="f1" v-if="detail.status==1 && detail.check_status == 1">
				<view class="t1">审核通过</view>
			</view>
			<view class="f1" v-if="detail.status==3">
				<view class="t1">订单已完成</view>
			</view>
			<view class="f1" v-if="detail.status==4">
				<view class="t1">订单已取消</view>
			</view>
		</view>
		<view class="product" v-if="prolist.length >0">
			<view v-for="(item, idx) in prolist" :key="idx" class="content">
				<view @tap="goto" :data-url="'/restaurant/shop/product?id=' + item.proid">
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
				<text class="t1">联系人</text>
				<text class="flex1"></text>
				<text  style="height:80rpx;line-height:80rpx">{{detail.linkman}}</text>
			</view>
			<view class="item">
				<text class="t1">电话</text>
				<text class="t2">{{detail.tel}}</text>
			</view>
			<view class="item">
				<text class="t1">桌号</text>
				<text class="t2">{{detail.tableName}}</text>
			</view>
		</view>
		
		<view class="orderinfo">
			<view class="item">
				<text class="t1">下单人</text>
				<text class="flex1"></text>
				<image :src="detail.headimg" style="width:80rpx;height:80rpx;margin-right:8rpx"/>
				<text  style="height:80rpx;line-height:80rpx">{{detail.nickname}}</text>
			</view>
			<view class="item">
				<text class="t1">{{t('会员')}}ID</text>
				<text class="t2">{{detail.mid}}</text>
			</view>
		</view>
		<view class="orderinfo" v-if="detail.remark">
			<view class="item">
				<text class="t1">备注</text>
				<text class="t2">{{detail.remark}}</text>
			</view>
		</view>
		<view class="orderinfo">
			<view class="item">
				<text class="t1">订单编号</text>
				<text class="t2" user-select="true" selectable="true">{{detail.ordernum}}</text>
			</view>
			<view class="item">
				<text class="t1">下单时间</text>
				<text class="t2">{{detail.createtime}}</text>
			</view>
			<view class="item" v-if="detail.status>0 && detail.paytypeid!='4' && detail.paytime">
				<text class="t1">支付时间</text>
				<text class="t2">{{detail.paytime}}</text>
			</view>
			<view class="item" v-if="detail.status>0 && detail.paytime">
				<text class="t1">支付方式</text>
				<text class="t2">{{detail.paytype}}</text>
			</view>
			<view class="item" v-if="detail.status>1 && detail.send_time">
				<text class="t1">发货时间</text>
				<text class="t2">{{detail.send_time}}</text>
			</view>
			<view class="item" v-if="detail.status==3 && detail.collect_time">
				<text class="t1">收货时间</text>
				<text class="t2">{{detail.collect_time}}</text>
			</view>
		</view>
		<view class="orderinfo">
			<view class="item">
				<text class="t1">商品金额</text>
				<text class="t2 red">¥{{detail.product_price}}</text>
			</view>
			<view class="item" v-if="detail.disprice > 0">
				<text class="t1">{{t('会员')}}折扣</text>
				<text class="t2 red">-¥{{detail.leveldk_money}}</text>
			</view>
			<view class="item" v-if="detail.jianmoney > 0">
				<text class="t1">满减活动</text>
				<text class="t2 red">-¥{{detail.manjian_money}}</text>
			</view>
			<view class="item" v-if="detail.freight_type==1 && detail.freightprice > 0">
				<text class="t1">服务费</text>
				<text class="t2 red">+¥{{detail.freight_price}}</text>
			</view>
			<view class="item" v-if="detail.freight_time">
				<text class="t1">{{detail.freight_type!=1?'配送':'提货'}}时间</text>
				<text class="t2">{{detail.freight_time}}</text>
			</view>
			<view class="item" v-if="detail.couponmoney > 0">
				<text class="t1">{{t('优惠券')}}抵扣</text>
				<text class="t2 red">-¥{{detail.coupon_money}}</text>
			</view>
			
			<view class="item" v-if="detail.scoredk > 0">
				<text class="t1">{{t('积分')}}抵扣</text>
				<text class="t2 red">-¥{{detail.scoredk_money}}</text>
			</view>
			<view class="item">
				<text class="t1">实付款</text>
				<text class="t2 red">¥{{detail.totalprice}}</text>
			</view>

			<view class="item">
				<text class="t1">订单状态</text>
				<text class="t2" v-if="detail.status==0">未付款</text>
				<text class="t2" v-if="detail.status==1">已付款</text>
				<text class="t2" v-if="detail.status==2">已发货</text>
				<text class="t2" v-if="detail.status==3">已收货</text>
				<text class="t2" v-if="detail.status==4">已关闭</text>
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
			<view class="item" v-if="detail.refund_checkremark">
				<text class="t1">审核备注</text>
				<text class="t2 red">{{detail.refund_checkremark}}</text>
			</view>

			<view class="item">
				<text class="t1">备注</text>
				<text class="t2 red">{{detail.message ? detail.message : '无'}}</text>
			</view>
			<view class="item" v-if="detail.field1">
				<text class="t1">{{detail.field1data[0]}}</text>
				<text class="t2 red">{{detail.field1data[1]}}</text>
			</view>
			<view class="item" v-if="detail.field2">
				<text class="t1">{{detail.field2data[0]}}</text>
				<text class="t2 red">{{detail.field2data[1]}}</text>
			</view>
			<view class="item" v-if="detail.field3">
				<text class="t1">{{detail.field3data[0]}}</text>
				<text class="t2 red">{{detail.field3data[1]}}</text>
			</view>
			<view class="item" v-if="detail.field4">
				<text class="t1">{{detail.field4data[0]}}</text>
				<text class="t2 red">{{detail.field4data[1]}}</text>
			</view>
			<view class="item" v-if="detail.field5">
				<text class="t1">{{detail.field5data[0]}}</text>
				<text class="t2 red">{{detail.field5data[1]}}</text>
			</view>
			<view class="item flex-col" v-if="(detail.status==1 || detail.status==2) && detail.freight_type==1">
				<text class="t1">核销码</text>
				<view class="flex-x-center">
					<image :src="detail.hexiao_qr" style="width:400rpx;height:400rpx" @tap="previewImage" :data-url="detail.hexiao_qr"></image>
				</view>
			</view>
		</view>

		<view style="width:100%;height:120rpx"></view>

		<view class="bottom">
			<view v-if="detail.refund_status==1" class="btn2" @tap="refundnopass" :data-id="detail.id">退款驳回</view>
			<view v-if="detail.refund_status==1" class="btn2" @tap="refundpass" :data-id="detail.id">退款通过</view>
			<view v-if="detail.status==0" class="btn2" @tap="closeOrder" :data-id="detail.id">关闭订单</view>
			<view v-if="detail.status==0 && detail.bid==0" class="btn2" @tap="ispay" :data-id="detail.id">改为已支付</view>
			<view v-if="detail.status==1 && detail.canpeisong" class="btn2" @tap="peisong" :data-id="detail.id">配送</view>
			<view v-if="detail.status==4" class="btn2" @tap="delOrder" :data-id="detail.id">删除</view>
			<view class="btn2" @tap="setremark" :data-id="detail.id">设置备注</view>
			
			<view v-if="detail.status==1 && detail.check_status == 0" class="btn2 color-red" @tap="refuse" :data-id="detail.id">驳回</view>
			<view v-if="detail.status==1 && detail.check_status == 0" class="btn2" @tap="access" :data-id="detail.id">审核通过</view>
			
		</view>
		<uni-popup id="dialogSetremark" ref="dialogSetremark" type="dialog">
			<uni-popup-dialog mode="input" title="设置备注" :value="detail.remark" placeholder="请输入备注" @confirm="setremarkconfirm"></uni-popup-dialog>
		</uni-popup>

		<uni-popup id="dialogPeisong" ref="dialogPeisong" type="dialog">
			<view class="uni-popup-dialog">
				<view class="uni-dialog-title">
					<text class="uni-dialog-title-text">请选择配送员</text>
				</view>
				<view class="uni-dialog-content">
					<view>
						<picker @change="peisongChange" :value="index2" :range="peisonguser2" style="font-size:28rpx;border: 1px #eee solid;padding:10rpx;height:70rpx;border-radius:4px;flex:1">
							<view class="picker">{{peisonguser2[index2]}}</view>
						</picker>
					</view>
				</view>
				<view class="uni-dialog-button-group">
					<view class="uni-dialog-button" @click="dialogPeisongClose">
						<text class="uni-dialog-button-text">取消</text>
					</view>
					<view class="uni-dialog-button uni-border-left" @click="confirmPeisong">
						<text class="uni-dialog-button-text uni-button-color">确定</text>
					</view>
				</view>
			</view>
		</uni-popup>

	</block>
	<loading v-if="loading"></loading>
	<dp-tabbar :opt="opt"></dp-tabbar>
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
			expressdata:[],
			express_index:0,
			express_no:'',
      prodata: '',
      djs: '',
      detail: "",
      prolist: "",
      shopset: "",
      storeinfo: "",
      lefttime: "",
      codtxt: "",
			peisonguser:[],
			peisonguser2:[],
			index2:0,
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
			app.get('ApiAdminRestaurantBookingOrder/detail', {id: that.opt.id}, function (res) {
				that.loading = false;
				that.expressdata = res.expressdata;
				that.detail = res.detail;
				that.prolist = res.prolist;
				that.shopset = res.shopset;
				that.storeinfo = res.storeinfo;
				that.lefttime = res.lefttime;
				that.codtxt = res.codtxt;
				if (res.lefttime > 0) {
					interval = setInterval(function () {
						that.lefttime = that.lefttime - 1;
						that.getdjs();
					}, 1000);
				}
				that.loaded();
			});
		},
    getdjs: function () {
      var that = this;
      var totalsec = that.lefttime;

      if (totalsec <= 0) {
        that.djs = '00时00分00秒';
      } else {
        var houer = Math.floor(totalsec / 3600);
        var min = Math.floor((totalsec - houer * 3600) / 60);
        var sec = totalsec - houer * 3600 - min * 60;
        var djs = (houer < 10 ? '0' : '') + houer + '时' + (min < 10 ? '0' : '') + min + '分' + (sec < 10 ? '0' : '') + sec + '秒';
        that.djs = djs;
      }
    },
		setremark:function(){
			this.$refs.dialogSetremark.open();
		},
		setremarkconfirm: function (done, remark) {
			this.$refs.dialogSetremark.close();
			var that = this
			app.post('ApiAdminRestaurantOrderCommon/setremark', { type:'restaurant_booking',orderid: that.detail.id,content:remark }, function (res) {
				app.success(res.msg);
				setTimeout(function () {
					that.getdata();
				}, 1000)
			})
    },
		ispay:function(e){
			var that = this;
			var orderid = e.currentTarget.dataset.id
			app.confirm('确定要改为已支付吗?', function () {
				app.showLoading('提交中');
				app.post('ApiAdminRestaurantOrderCommon/ispay', { type:'restaurant_booking',orderid: orderid }, function (data) {
					app.showLoading(false);
					app.success(data.msg);
					setTimeout(function () {
						that.getdata();
					}, 1000)
				})
			});
		},
		delOrder: function (e) {
			var that = this;
			var orderid = e.currentTarget.dataset.id
			app.showLoading('删除中');
			app.confirm('确定要删除该订单吗?', function () {
				app.post('ApiAdminRestaurantOrderCommon/delOrder', { type:'restaurant_booking',orderid: orderid }, function (data) {
					app.showLoading(false);
					app.success(data.msg);
					setTimeout(function () {
						app.goto('shoporder');
					}, 1000)
				});
			})
		},
		closeOrder: function (e) {
			var that = this;
			var orderid = e.currentTarget.dataset.id
			app.confirm('确定要关闭该订单吗?', function () {
				app.showLoading('提交中');
				app.post('ApiAdminRestaurantOrderCommon/closeOrder', { type:'restaurant_booking',orderid: orderid }, function (data) {
					app.showLoading(false);
					app.success(data.msg);
					setTimeout(function (){
						that.getdata();
					}, 1000)
				});
			})
		},
		refundnopass: function (e) {
			var that = this;
			var orderid = e.currentTarget.dataset.id
			app.confirm('确定要驳回退款申请吗?', function () {
				app.showLoading('提交中');
				app.post('ApiAdminRestaurantOrderCommon/refundnopass', { type:'restaurant_booking',orderid: orderid }, function (data) {
					app.showLoading(false);
					app.success(data.msg);
					setTimeout(function () {
						that.getdata();
					}, 1000)
				})
			});
		},
		refundpass: function (e) {
			var that = this;
			var orderid = e.currentTarget.dataset.id
			app.confirm('确定要审核通过并退款吗?', function () {
				app.showLoading('提交中');
				app.post('ApiAdminRestaurantOrderCommon/refundpass', { type:'restaurant_booking',orderid: orderid }, function (data) {
					app.showLoading(false);
					app.success(data.msg);
					setTimeout(function () {
						that.getdata();
					}, 1000)
				})
			});
		},
		refuse: function (e) {
			var that = this;
			var orderid = e.currentTarget.dataset.id
			app.confirm('确定要驳回吗？如有预定费将进行退款', function () {
				app.showLoading('提交中');
				app.post('ApiAdminRestaurantBookingOrder/check', { type:'refuse',orderid: orderid }, function (data) {
					app.showLoading(false);
					app.success(data.msg);
					setTimeout(function () {
						that.getdata();
					}, 1000)
				})
			});
		},
		access: function (e) {
			var that = this;
			var orderid = e.currentTarget.dataset.id
			app.showLoading('提交中');
			app.post('ApiAdminRestaurantBookingOrder/check', { type:'access',orderid: orderid }, function (data) {
				app.showLoading(false);
				app.success(data.msg);
				setTimeout(function () {
					that.getdata();
				}, 1000)
			})
		}
  }
};
</script>
<style>
.ordertop{width:100%;height:220rpx;padding:50rpx 0 0 70rpx}
.ordertop .f1{color:#fff}
.ordertop .f1 .t1{font-size:32rpx;height:60rpx;line-height:60rpx}
.ordertop .f1 .t2{font-size:24rpx}

.address{ display:flex;width: 100%; padding: 20rpx 3%; background: #FFF;margin-bottom:20rpx;}
.address .img{width:40rpx}
.address image{width:40rpx; height:40rpx;}
.address .info{flex:1;display:flex;flex-direction:column;}
.address .info .t1{font-size:28rpx;font-weight:bold;color:#333}
.address .info .t2{font-size:24rpx;color:#999}

.product{width:100%; padding: 14rpx 3%;background: #FFF;}
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

.orderinfo{ width:100%;margin-top:10rpx;padding: 14rpx 3%;background: #FFF;}
.orderinfo .item{display:flex;width:100%;padding:20rpx 0;border-bottom:1px dashed #ededed;}
.orderinfo .item:last-child{ border-bottom: 0;}
.orderinfo .item .t1{width:200rpx;}
.orderinfo .item .t2{flex:1;text-align:right}
.orderinfo .item .red{color:red}

.bottom{ width: 100%; padding: 16rpx 20rpx;background: #fff; position: fixed; bottom: 0px;left: 0px;display:flex;justify-content:flex-end;align-items:center;}

.btn1{margin-left:20rpx;width:160rpx;height:60rpx;line-height:60rpx;color:#fff;border-radius:3px;text-align:center}
.btn2{margin-left:20rpx;width:160rpx;height:60rpx;line-height:60rpx;color:#333;background:#fff;border:1px solid #cdcdcd;border-radius:3px;text-align:center}
.btn3{position:absolute;top:60rpx;right:10rpx;font-size:24rpx;width:120rpx;height:50rpx;line-height:50rpx;color:#333;background:#fff;border:1px solid #cdcdcd;border-radius:3px;text-align:center}
.color-red {color: red;}

.btitle{ width:100%;height:100rpx;background:#fff;padding:0 20rpx;border-bottom:1px solid #f5f5f5}
.btitle .comment{border: 1px #ffc702 solid;border-radius:10rpx;background:#fff; color: #ffc702;  padding: 0 10rpx; height: 46rpx; line-height: 46rpx;}
.btitle .comment2{border: 1px #ffc7c0 solid;border-radius:10rpx;background:#fff; color: #ffc7c0;  padding: 0 10rpx; height: 46rpx; line-height: 46rpx;}

.picker{display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:1;overflow:hidden;}

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