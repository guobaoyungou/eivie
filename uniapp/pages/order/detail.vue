<template>
<view class="container">
	<block v-if="isload">
		<view class="ordertop" :style="'background:url(' + pre_url + '/static/img/ordertop.png);background-size:100%'">
			<view class="f1" v-if="detail.status==0">
				<view class="t1">等待买家付款</view>
				<view class="t2" v-if="djs">剩余时间：{{djs}}</view>
				<view class="t2" v-if="detail.paytypeid == 5">转账汇款后请上传付款凭证</view>
			</view>
			<view class="f1" v-if="detail.status==1">
				<view class="t1">{{detail.paytypeid==4 ? '已选择'+detail.paytype : '已成功付款'}}</view>
				<view class="t2" v-if="detail.freight_type!=1">我们会尽快为您发货</view>
				<view class="t2" v-if="detail.freight_type==1">请尽快前往自提地点取货</view>
			</view>
			<view class="f1" v-if="detail.status==2">
				<view class="t1">订单已发货</view>
				<text class="t2" v-if="detail.freight_type!=3" user-select="true" selectable="true">发货信息：{{detail.express_com}} {{detail.express_no}}</text>
			</view>
			<view class="f1" v-if="detail.status==3">
				<view class="t1">订单已完成</view>
			</view>
			<view class="f1" v-if="detail.status==4">
				<view class="t1">订单已取消</view>
			</view>
		</view>
		<view class="address">
			<view class="img">
				<image src="/static/img/address3.png"></image>
			</view>
			<view class="info">
				<text class="t1" user-select="true" selectable="true">{{detail.linkman}} {{detail.tel}}</text>
				<text class="t2" v-if="detail.freight_type!=1 && detail.freight_type!=3" user-select="true" selectable="true">地址：{{detail.area}}{{detail.address}}</text>
				<text class="t2" v-if="detail.freight_type==1" @tap="openMendian" :data-storeinfo="storeinfo" user-select="true" selectable="true">取货地点：{{storeinfo.name}} - {{storeinfo.address}} </text>
			</view>
		</view>
		<view class="btitle flex-y-center" v-if="detail.bid>0" @tap="goto" :data-url="'/pages/business/index?id=' + detail.bid">
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
					<view class="t2 flex flex-y-center flex-bt">
						<text>{{item.ggname}}</text>
						<view class="btn3" v-if="detail.status==3 && item.iscomment==0 && shopset.comment==1" @tap.stop="goto" :data-url="'comment?ogid=' + item.id">去评价</view>
						<view class="btn3" v-if="detail.status==3 && item.iscomment==1" @tap.stop="goto" :data-url="'comment?ogid=' + item.id">查看评价</view>
					</view>
					<view class="t3"><text class="x1 flex1">￥{{item.sell_price}}</text><text class="x2">×{{item.num}}</text></view>
					<!-- <view class="t4 flex flex-x-bottom">
						<view class="btn3" v-if="detail.status==3 && item.iscomment==0 && shopset.comment==1" @tap.stop="goto" :data-url="'comment?ogid=' + item.id">去评价</view>
						<view class="btn3" v-if="detail.status==3 && item.iscomment==1" @tap.stop="goto" :data-url="'comment?ogid=' + item.id">查看评价</view>
					</view> -->
				</view>
			</view>
		</view>
		
		<view class="orderinfo" v-if="(detail.status==3 || detail.status==2) && (detail.freight_type==3 || detail.freight_type==4)">
			<view class="item flex-col">
				<text class="t1" style="color:#111">发货信息</text>
				<text class="t2" style="text-align:left;margin-top:10rpx;padding:0 10rpx" user-select="true" selectable="true">{{detail.freight_content}}</text>
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
			<view class="item" v-if="detail.paytypeid">
				<text class="t1">支付方式</text>
				<text class="t2">{{detail.paytype}}</text>
			</view>
			<block v-if="detail.paytypeid == '5'">
				<view class="item" v-if="pay_transfer_info.pay_transfer_account_name">
					<text class="t1">户名</text>
					<text class="t2">{{pay_transfer_info.pay_transfer_account_name}}</text>
				</view>
				<view class="item" v-if="pay_transfer_info.pay_transfer_account">
					<text class="t1">账户</text>
					<text class="t2">{{pay_transfer_info.pay_transfer_account}}</text>
				</view>
				<view class="item" v-if="pay_transfer_info.pay_transfer_bank">
					<text class="t1">开户行</text>
					<text class="t2">{{pay_transfer_info.pay_transfer_bank}}</text>
				</view>
				<view class="item" v-if="pay_transfer_info.pay_transfer_desc">
					<text class="text-min">{{pay_transfer_info.pay_transfer_desc}}</text>
				</view>
				<view class="item">
					<text class="t1">付款凭证审核</text>
					<text class="t2">{{payorder.check_status_label}}</text>
				</view>
				<view class="item" v-if="payorder.check_remark">
					<text class="t1">审核备注</text>
					<text class="t2">{{payorder.check_remark}}</text>
				</view>
			</block>
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
			<view class="item" v-if="detail.manjian_money > 0">
				<text class="t1">满减活动</text>
				<text class="t2 red">-¥{{detail.manjian_money}}</text>
			</view>
			<view class="item" v-if="detail.invoice_money > 0">
				<text class="t1">发票费用</text>
				<text class="t2 red">+¥{{detail.invoice_money}}</text>
			</view>
			<view class="item">
				<text class="t1">配送方式</text>
				<text class="t2">{{detail.freight_text}}</text>
			</view>
			<view class="item" v-if="detail.freight_type==1 && detail.freightprice > 0">
				<text class="t1">服务费</text>
				<text class="t2 red">+¥{{detail.freight_price}}</text>
			</view>
			<view class="item" v-if="detail.freight_time">
				<text class="t1">{{detail.freight_type!=1?'配送':'提货'}}时间</text>
				<text class="t2">{{detail.freight_time}}</text>
			</view>
			<view class="item" v-if="detail.coupon_money > 0">
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
            <view class="item" v-if="detail.is_yuanbao_pay==1">
            	<text class="t1">{{t('元宝')}}</text>
            	<text class="t2 red">{{detail.total_yuanbao}}</text>
            </view>
			<view class="item">
				<text class="t1">订单状态</text>
				<text class="t2" v-if="detail.status==0">未付款</text>
				<text class="t2" v-if="detail.status==1">{{detail.paytypeid==4?'待发货':'已支付'}}</text>
				<text class="t2" v-if="detail.status==2">已发货</text>
				<text class="t2" v-if="detail.status==3">已收货</text>
				<text class="t2" v-if="detail.status==4">已关闭</text>
			</view>
			<view class="item" v-if="detail.refundingMoneyTotal>0">
				<text class="t1">退款中</text>
				<text class="t2 red" @tap="goto" :data-url="'refundlist?orderid='+ detail.id">¥{{detail.refundingMoneyTotal}}</text>
				<text class="t3 iconfont iconjiantou" style="color:#999;font-weight:normal"></text>
			</view>
			<view class="item" v-if="detail.refundedMoneyTotal>0">
				<text class="t1">已退款</text>
				<text class="t2 red" @tap="goto" :data-url="'refundlist?orderid='+ detail.id">¥{{detail.refundedMoneyTotal}}</text>
				<text class="t3 iconfont iconjiantou" style="color:#999;font-weight:normal"></text>
			</view>
			<view class="item" v-if="detail.refund_status>0">
				<text class="t1">退款状态</text>
				<text class="t2 red" v-if="detail.refund_status==1">审核中</text>
				<text class="t2 red" v-if="detail.refund_status==2">已退款</text>
				<text class="t2 red" v-if="detail.refund_status==3">已驳回</text>
			</view>
			
			<view class="item" v-if="detail.balance_price>0">
				<text class="t1">尾款</text>
				<text class="t2 red">¥{{detail.balance_price}}</text>
			</view>
			<view class="item" v-if="detail.balance_price>0">
				<text class="t1">尾款状态</text>
				<text class="t2" v-if="detail.balance_pay_status==1">已支付</text>
				<text class="t2" v-if="detail.balance_pay_status==0">未支付</text>
			</view>
		</view>
		<view class="orderinfo" v-if="detail.checkmemid">
			<view class="item">
				<text class="t1">所选会员</text>
				<text class="flex1"></text>
				<image :src="detail.checkmember.headimg" style="width:80rpx;height:80rpx;margin-right:8rpx"/>
				<text  style="height:80rpx;line-height:80rpx">{{detail.checkmember.nickname}}</text>
			</view>
		</view>
		<view class="orderinfo" v-if="(detail.formdata).length > 0">
			<view class="item" v-for="item in detail.formdata" :key="index">
				<text class="t1">{{item[0]}}</text>
				<view class="t2" v-if="item[2]=='upload'"><image :src="item[1]" style="width:400rpx;height:auto" mode="widthFix" @tap="previewImage" :data-url="item[1]"/></view>
				<text class="t2" v-else user-select="true" selectable="true">{{item[1]}}</text>
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

		<view style="width:100%;height:160rpx"></view>

		<view class="bottom notabbarbot">
			<block v-if="detail.payaftertourl && detail.payafterbtntext">
				<view style="position:relative">
					<block v-if="detail.payafter_username">
						<view class="btn2">{{detail.payafterbtntext}}</view>
						<!-- #ifdef H5 -->
						<wx-open-launch-weapp :username="detail.payafter_username" :path="detail.payafter_path" style="position:absolute;top:0;left:0;right:0;bottom:0;z-index:8">
							<script type="text/wxtag-template">
								<div style="width:100%;height:40px;"></div>
							</script>
						</wx-open-launch-weapp>
						<!-- #endif -->
					</block>
					<block v-else>
						<view class="btn2" @tap="goto" :data-url="detail.payaftertourl">{{detail.payafterbtntext}}</view>
					</block>
				</view>
			</block>
			<block v-if="detail.isworkorder==1">
					<view class="btn2" @tap="goto" :data-url="'/activity/workorder/index?type=1&id='+detail.id" :data-id="detail.id">发起工单</view>
			</block>
			<block v-if="detail.status==0">
				<view class="btn2" @tap="toclose" :data-id="detail.id">关闭订单</view>
				<view class="btn1" v-if="detail.paytypeid != 5" :style="{background:t('color1')}" @tap="goto" :data-url="'/pages/pay/pay?id=' + detail.payorderid">去付款</view>
				<view class="btn1" v-if="detail.paytypeid == 5" :style="{background:t('color1')}" @tap="goto" :data-url="'/pages/pay/transfer?id=' + detail.payorderid">上传付款凭证</view>
			</block>
			<block v-if="detail.status==1">
				<block v-if="detail.paytypeid!='4'">
					<view class="btn2" @tap="goto" :data-url="'refundSelect?orderid=' + detail.id" v-if="shopset.canrefund==1 && detail.refundnum < detail.procount">退款</view>
				</block>
				<block v-else>
					<!-- <view class="btn2">{{codtxt}}</view> -->
				</block>
			</block>
			<block v-if="(detail.status==2 || detail.status==3) && detail.freight_type!=3 && detail.freight_type!=4">
					<view class="btn2" v-if="detail.express_type =='express_wx'" @tap="logistics" :data-express_type="detail.express_type" :data-express_com="detail.express_com" :data-express_no="detail.express_no" :data-express_content="detail.express_content">订单跟踪</view>
					<view class="btn2" v-else @tap="logistics" :data-express_type="detail.express_type" :data-express_com="detail.express_com" :data-express_no="detail.express_no" :data-express_content="detail.express_content">查看物流</view>
			</block>
			<block v-if="([1,2,3]).includes(detail.status) && invoice">
				<view class="btn2" @tap="goto" :data-url="'invoice?type=shop&orderid=' + detail.id">发票</view>
			</block>
			<block v-if="detail.status==2">
				<block v-if="detail.paytypeid!='4'">
					<view class="btn2" @tap="goto" :data-url="'refundSelect?orderid=' + detail.id" v-if="shopset.canrefund==1 && detail.refundnum < detail.procount">退款</view>
				</block>
				<view class="btn1" :style="{background:t('color1')}" @tap="orderCollect" :data-id="detail.id" v-if="detail.paytypeid!='4' && (detail.balance_pay_status==1 || detail.balance_price==0)">确认收货</view>
				<!-- <view class="btn2" v-if="detail.paytypeid=='4'">{{codtxt}}</view> -->
				<view v-if="detail.balance_pay_status == 0 && detail.balance_price > 0" class="btn1" :style="{background:t('color1')}" @tap.stop="goto" :data-url="'/pages/pay/pay?id=' + detail.balance_pay_orderid">支付尾款</view>
			</block>
			<block v-if="(detail.status==1 || detail.status==2) && detail.freight_type==1">
				<view class="btn2" @tap="showhxqr">核销码</view>
			</block>
			<view v-if="detail.refundCount" class="btn2" @tap.stop="goto" :data-url="'refundlist?orderid='+ detail.id">查看退款</view>
			<block v-if="detail.status==3 || detail.status==4">
				<view class="btn2" @tap="todel" :data-id="detail.id">删除订单</view>
			</block>
			<block v-if="detail.bid>0 && detail.status==3">
				<view v-if="iscommentdp==0" class="btn1" :style="{background:t('color1')}" @tap="goto" :data-url="'/pages/order/commentdp?orderid=' + detail.id">评价店铺</view>
				<view v-if="iscommentdp==1" class="btn2" @tap="goto" :data-url="'/pages/order/commentdp?orderid=' + detail.id">查看评价</view>
			</block>
		</view>
		<uni-popup id="dialogHxqr" ref="dialogHxqr" type="dialog">
			<view class="hxqrbox">
				<image :src="detail.hexiao_qr" @tap="previewImage" :data-url="detail.hexiao_qr" class="img"/>
				<view class="txt">请出示核销码给核销员进行核销</view>
				<view class="close" @tap="closeHxqr">
					<image src="/static/img/close2.png" style="width:100%;height:100%"/>
				</view>
			</view>
		</uni-popup>

		
		<uni-popup id="dialogSelectExpress" ref="dialogSelectExpress" type="dialog">
			<view style="background:#fff;padding:20rpx 30rpx;border-radius:10rpx;width:600rpx">
				<view class="sendexpress-item" v-for="(item, index) in express_content" :key="index" @tap="goto" :data-url="'/pages/order/logistics?express_com=' + item.express_com + '&express_no=' + item.express_no" style="display: flex;border-bottom: 1px solid #f5f5f5;padding:20rpx 0;">
					<view class="flex1">{{item.express_com}} - {{item.express_no}}</view>
					<image src="/static/img/arrowright.png" style="width:30rpx;height:30rpx"/>
				</view>
			</view>
		</uni-popup>

	</block>
	<loading v-if="loading"></loading>
	<dp-tabbar :opt="opt"></dp-tabbar>
	<popmsg ref="popmsg"></popmsg>
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
      prodata: '',
      djs: '',
      iscommentdp: "",
      detail: "",
			payorder:{},
      prolist: "",
      shopset: "",
      storeinfo: "",
      lefttime: "",
      codtxt: "",
			pay_transfer_info:{},
			invoice:0,
			selectExpressShow:false,
			express_content:'',
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
			app.get('ApiOrder/detail', {id: that.opt.id}, function (res) {
				that.loading = false;
				that.iscommentdp = res.iscommentdp,
				that.detail = res.detail;
				that.prolist = res.prolist;
				that.shopset = res.shopset;
				that.storeinfo = res.storeinfo;
				that.lefttime = res.lefttime;
				that.codtxt = res.codtxt;
				that.pay_transfer_info =  res.pay_transfer_info;
				that.payorder = res.payorder;
				that.invoice = res.invoice;
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
    todel: function (e) {
      var that = this;
      var orderid = e.currentTarget.dataset.id;
      app.confirm('确定要删除该订单吗?', function () {
				app.showLoading('删除中');
        app.post('ApiOrder/delOrder', {orderid: orderid}, function (data) {
					app.showLoading(false);
          app.success(data.msg);
          setTimeout(function () {
            app.goback(true);
          }, 1000);
        });
      });
    },
    toclose: function (e) {
      var that = this;
      var orderid = e.currentTarget.dataset.id;
      app.confirm('确定要关闭该订单吗?', function () {
				app.showLoading('提交中');
        app.post('ApiOrder/closeOrder', {orderid: orderid}, function (data) {
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
		showhxqr:function(){
			this.$refs.dialogHxqr.open();
		},
		closeHxqr:function(){
			this.$refs.dialogHxqr.close();
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
		openMendian: function(e) {
			var storeinfo = e.currentTarget.dataset.storeinfo;
			app.goto('/pages/shop/mendian?id=' + storeinfo.id);
		},
		logistics:function(e){
			var express_com = e.currentTarget.dataset.express_com
			var express_no = e.currentTarget.dataset.express_no
			var express_content = e.currentTarget.dataset.express_content
			var express_type = e.currentTarget.dataset.express_type
			console.log(express_content)
			if(!express_content){
				app.goto('/pages/order/logistics?express_com=' + express_com + '&express_no=' + express_no+'&type='+express_type);
			}else{
				this.express_content = JSON.parse(express_content);
				console.log(express_content);
				this.$refs.dialogSelectExpress.open();
			}
		},
		hideSelectExpressDialog:function(){
			this.$refs.dialogSelectExpress.close();
		}
  }
};
</script>
<style>
	.text-min { font-size: 24rpx; color: #999;}
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

.product .content .detail .t1{font-size:26rpx;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:2;overflow:hidden;}
.product .content .detail .t2{color: #999;font-size: 26rpx;margin-top: 10rpx;}
.product .content .detail .t3{display:flex;color: #ff4246;margin-top: 10rpx;}
.product .content .detail .t4{margin-top: 10rpx;}

.product .content .detail .x1{ flex:1}
.product .content .detail .x2{ width:100rpx;font-size:32rpx;text-align:right;margin-right:8rpx}
.product .content .comment{position:absolute;top:64rpx;right:10rpx;border: 1px #ffc702 solid; border-radius:10rpx;background:#fff; color: #ffc702;  padding: 0 10rpx; height: 46rpx; line-height: 46rpx;}
.product .content .comment2{position:absolute;top:64rpx;right:10rpx;border: 1px #ffc7c2 solid; border-radius:10rpx;background:#fff; color: #ffc7c2;  padding: 0 10rpx; height: 46rpx; line-height: 46rpx;}

.orderinfo{width:96%;margin:0 2%;border-radius:8rpx;margin-top:16rpx;padding: 14rpx 3%;background: #FFF;}
.orderinfo .item{display:flex;width:100%;padding:20rpx 0;border-bottom:1px dashed #ededed;overflow:hidden}
.orderinfo .item:last-child{ border-bottom: 0;}
.orderinfo .item .t1{width:200rpx;flex-shrink:0}
.orderinfo .item .t2{flex:1;text-align:right}
.orderinfo .item .t3{ margin-top: 3rpx;}
.orderinfo .item .red{color:red}

.bottom{ width: 100%; height:92rpx;padding: 0 20rpx;background: #fff; position: fixed; bottom: 0px;left: 0px;display:flex;justify-content:flex-end;align-items:center;}

.btn1{margin-left:20rpx;min-width:160rpx;padding: 0 20rpx;height:60rpx;line-height:60rpx;color:#fff;border-radius:3px;text-align:center;}
.btn2{margin-left:20rpx;width:160rpx;height:60rpx;line-height:60rpx;color:#333;background:#fff;border:1px solid #cdcdcd;border-radius:3px;text-align:center;}
.btn3{font-size:24rpx;width:120rpx;height:50rpx;line-height:50rpx;color:#333;background:#fff;border:1px solid #cdcdcd;border-radius:3px;text-align:center}

.btitle{ width:100%;height:100rpx;background:#fff;padding:0 20rpx;border-bottom:1px solid #f5f5f5}
.btitle .comment{border: 1px #ffc702 solid;border-radius:10rpx;background:#fff; color: #ffc702;  padding: 0 10rpx; height: 46rpx; line-height: 46rpx;}
.btitle .comment2{border: 1px #ffc7c0 solid;border-radius:10rpx;background:#fff; color: #ffc7c0;  padding: 0 10rpx; height: 46rpx; line-height: 46rpx;}

.hxqrbox{background:#fff;padding:50rpx;position:relative;border-radius:20rpx}
.hxqrbox .img{width:400rpx;height:400rpx}
.hxqrbox .txt{color:#666;margin-top:20rpx;font-size:26rpx;text-align:center}
.hxqrbox .close{width:50rpx;height:50rpx;position:absolute;bottom:-100rpx;left:50%;margin-left:-25rpx;border:1px solid rgba(255,255,255,0.5);border-radius:50%;padding:8rpx}
</style>