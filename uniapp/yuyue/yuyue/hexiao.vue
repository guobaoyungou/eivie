<template>
<view>
<block v-if="isload">
		<view class="address">
			<view class="img">
				<image :src="pre_url+'/static/img/address3.png'"></image>
			</view>
			<view class="info">
				<text class="t1">{{order.linkman}} {{order.tel}}</text>
				<text class="t2" v-if="order.fwtype == 2">地址：{{order.area}}{{order.address}}</text>
			</view>
		</view>
		<view class="product">			<view v-for="(item, idx) in order.prolist" :key="idx" class="content">					<view>							<image :src="item.pic"></image>					</view>					<view class="detail">						<text class="t1">{{item.name}}</text>						<text class="t2" v-if="item.ggname">{{item.ggname}}</text>						<view class="t3" ><text class="x1 flex1">￥{{item.sell_price}}</text><text class="x2">×{{item.num}}</text></view>					</view>			</view>		</view>
		
		<view class="orderinfo">
			<view class="item">
				<text class="t1">下单人</text>
				<text class="flex1"></text>
				<image :src="order.headimg" style="width:80rpx;height:80rpx;margin-right:8rpx"/>
				<text  style="height:80rpx;line-height:80rpx">{{order.nickname}}</text>
			</view>
			<view class="item">
				<text class="t1">{{t('会员')}}ID</text>
				<text class="t2">{{order.mid}}</text>
			</view>
		</view>
		<view class="orderinfo" v-if="order.remark">
			<view class="item">
				<text class="t1">备注</text>
				<text class="t2">{{order.remark}}</text>
			</view>
		</view>
		<view class="orderinfo">
			<view class="item">
				<text class="t1">订单编号</text>
				<text class="t2" user-select="true" selectable="true">{{order.ordernum}}</text>
			</view>
			<view class="item">
				<text class="t1">下单时间</text>
				<text class="t2">{{order.createtime}}</text>
			</view>
			<view class="item" v-if="order.status>0 && order.paytypeid!='4' && order.paytime">
				<text class="t1">支付时间</text>
				<text class="t2">{{order.paytime}}</text>
			</view>
			<view class="item" v-if="order.status>0 && order.paytime">
				<text class="t1">支付方式</text>
				<text class="t2">{{order.paytype}}</text>
			</view>
			<view class="item" v-if="order.status>1 && order.send_time">
				<text class="t1">派单时间</text>
				<text class="t2">{{order.send_time}}</text>
			</view>
			<view class="item" v-if="order.status==3 && order.collect_time">
				<text class="t1">完成时间</text>
				<text class="t2">{{order.collect_time}}</text>
			</view>
		</view>
		<view class="orderinfo">
			<view class="item">
				<text class="t1">商品金额</text>
				<text class="t2 red">¥{{order.product_price}}</text>
			</view>
			<view class="item">
			    <text class="t1">服务方式</text>
			    <text class="t2">{{order.fwtype==1?'到店服务':'上门服务'}}</text>
			</view>
			<view class="item" v-if="order.freight_time">
				<text class="t1">{{order.freight_type!=1?'配送':'提货'}}时间</text>
				<text class="t2">{{order.freight_time}}</text>
			</view>
			<view class="item" v-if="order.scoredk > 0">
				<text class="t1">{{t('积分')}}抵扣</text>
				<text class="t2 red">-¥{{order.scoredk_money}}</text>
			</view>
			<view class="item">
				<text class="t1">实付款</text>
				<text class="t2 red">¥{{order.totalprice}}</text>
			</view>

			<view class="item">
				<text class="t1">订单状态</text>
				<text class="t2" v-if="order.status==0">未付款</text>
				<text class="t2" v-if="order.status==1">已付款</text>
				<text class="t2" v-if="order.status==2">已派单</text>
				<text class="t2" v-if="order.status==3">已完成</text>
				<text class="t2" v-if="order.status==4">已关闭</text>
			</view>
			<view class="item" v-if="order.refund_status>0">
				<text class="t1">退款状态</text>
				<text class="t2 red" v-if="order.refund_status==1">审核中,¥{{order.refund_money}}</text>
				<text class="t2 red" v-if="order.refund_status==2">已退款,¥{{order.refund_money}}</text>
				<text class="t2 red" v-if="order.refund_status==3">已驳回,¥{{order.refund_money}}</text>
			</view>
			<view class="item" v-if="order.refund_status>0">
				<text class="t1">退款原因</text>
				<text class="t2 red">{{order.refund_reason}}</text>
			</view>
			<view class="item" v-if="order.refund_checkremark">
				<text class="t1">审核备注</text>
				<text class="t2 red">{{order.refund_checkremark}}</text>
			</view>
			<view class="item">
				<text class="t1">备注</text>
				<text class="t2 red">{{order.message ? order.message : '无'}}</text>
			</view>
		</view>
	<view style="height:140rpx"></view>
	<view class="btn-add" v-if="order.status==1 || order.status==2" :style="{background:t('color1')}" @tap="hexiao">立即核销</view>
</block>
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
			type:'yuyue',
			order:{},
      nodata: false,
      nomore: false,
	  pre_url:app.globalData.pre_url,
    };
  },
  
  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.type = this.opt.type;
		this.getdata();
  },
	onPullDownRefresh: function () {
		this.getdata();
	},
  methods: {
    getdata: function () {
      var that = this;
			that.loading = true;
      app.post('ApiYuyue/hexiao',{type:that.type,co:that.opt.co}, function (res) {
				that.loading = false;
				if (res.status == 1) {
					that.order = res.order
					that.loaded(); 
				}else{
						if(res.msg) {
							app.alert(res.msg, function() {
								if (res.url) app.goto(res.url);
							});
						}
					}
      });
    },
		hexiao:function(){
      var that = this;
			app.confirm('确定要核销吗?',function(){
				app.showLoading('核销中');
				app.post('ApiYuyue/hexiao',{op:'confirm',type:that.type,co:that.opt.co}, function (res) {
					app.showLoading(false);
					if (res.status == 1) {
						app.alert(res.msg,function(){
							app.goto('/yuyue/yuyue/my','reLaunch');	
						});
					}else{
							if(res.msg) {
								app.alert(res.msg, function() {
									if (res.url) app.goto(res.url);
								});
							}
						}
				})
			})
		}
  }
};
</script>
<style>
.address{ display:flex;width: 100%; padding: 20rpx 3%; background: #FFF;margin-bottom:10rpx;}
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
.orderinfo .topitem{display:flex;padding:60rpx 40rpx;align-items:center;border-bottom:2px dashed #E5E5E5;position:relative}
.orderinfo .topitem .f1{font-size:50rpx;font-weight:bold;}
.orderinfo .topitem .f1 .t1{font-size:60rpx;}
.orderinfo .topitem .f1 .t2{font-size:40rpx;}
.orderinfo .topitem .f2{margin-left:40rpx}
.orderinfo .topitem .f2 .t1{font-size:36rpx;color:#2B2B2B;font-weight:bold;height:50rpx;line-height:50rpx}
.orderinfo .topitem .f2 .t2{font-size:24rpx;color:#999999;height:50rpx;line-height:50rpx}

.btn-add{width:90%;max-width:700px;margin:0 auto;height:96rpx;line-height:96rpx;text-align:center;color:#fff;font-size:30rpx;font-weight:bold;border-radius:40rpx;position: fixed;left:0px;right:0;bottom:20rpx;}
</style>