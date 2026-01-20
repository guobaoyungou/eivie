<template>
<view class="container">
	<block v-if="isload">
		<view class="ordertop" :style="'background:url(' + pre_url + '/static/img/ordertop.png);background-size:100%'">
			<view class="f1" v-if="detail.status==0">
				<view class="t1">等待买家付款</view>
				<view class="t2" v-if="djs">剩余时间：{{djs}}</view>
			</view>
			<view class="f1" v-if="detail.status==1 && detail.paytypeid != 4">
				<view class="t1">已成功付款</view>
				<view><text style="font-weight: bold;margin-left: 10rpx;font-size: 38rpx;" v-if="detail.pickup_number">取餐号:{{detail.pickup_number}} </text></view>
			</view>
			<view class="f1" v-if="detail.status==1 && detail.paytypeid == 4">
				<view class="t1">请线下支付</view>
			</view>
			<view class="f1" v-if="detail.status==12">
				<view class="t1">商家已接单</view>
				<view class="t2" v-if="detail.freight_type!=1">请等待配送</view>
				<view class="t2" v-if="detail.freight_type==1">请尽快前往自提地点取货</view>
			</view>
			<view class="f1" v-if="detail.status==2">
				<view class="t1">订单配送中</view>
				<view class="t2" v-if="detail.freight_type!=3">发货信息：{{detail.express}} {{detail.express_no}}</view>
			</view>
			<view class="f1" v-if="detail.status==3">
				<view class="t1">订单已完成</view>
			</view>
			<view class="f1" v-if="detail.status==4">
				<view class="t1">订单已取消</view>
			</view>
			<view class="f1" v-if="detail.show_printdaynum">
				<view v-if="detail.printdaynum" style="font-weight: bold;margin-left: 10rpx;font-size: 38rpx;">
					日流水号:#{{detail.printdaynum}}
				</view>	
			</view>
		</view>
		<view class="address" v-if="detail.is_bar_table_order==0">
			<view class="info">
				<text class="t1" v-if="detail.linkman">{{detail.linkman}} {{detail.tel}}</text>
				<text class="t2" >{{detail.tabletext}}：{{detail.tableName}}</text>			</view>
		</view>
		<view class="btitle flex-y-center" v-if="detail.binfo">
			<image :src="detail.binfo.logo" style="width:36rpx;height:36rpx;" @tap="goto" :data-url="'index?bid=' + detail.bid"></image>
			<text class="flex1" decode="true" space="true" @tap="goto" :data-url="'index?bid=' + detail.bid" style="padding-left:16rpx">{{detail.binfo.name}}</text>
		</view>
		<view class="product">
			<view v-for="(item, idx) in prolist" :key="idx" class="content" v-if="detail.eattype ==0">
				<view class="flex">
					<view @tap="goto" :data-url="'product?id=' + item.proid">
						<image :src="item.pic"></image>
					</view>
					<view class="detail">
						<text class="t1">{{item.name}}</text>
						<view class="t2 flex flex-y-center " style="justify-content: space-between;">
							<view v-if="(item.ggtext && item.ggtext.length)" class="flex-col">
								<block v-for="(item2,index) in item.ggtext">
									<text class="t2">{{item2}}</text>
								</block>
							</view>
							<text v-if="item.ggname">{{item.ggname}} </text><text v-if="item.jltitle">{{item.jltitle}}</text>	
							<view class="btn3" v-if="detail.status==3 && item.iscomment==0 && shopset.comment==1 " @tap.stop="goto" :data-url="'comment?ogid=' + item.id">去评价</view>
							<view class="btn3" v-if="detail.status==3 && item.iscomment==1" @tap.stop="goto" :data-url="'comment?ogid=' + item.id">查看评价</view>
						</view>
						<view class="t3">
							<text class="x1 flex1" v-if="item.jlprice">￥{{parseFloat(parseFloat(item.sell_price)+parseFloat(item.jlprice)).toFixed(2)}}<text v-if="item.product_type && item.product_type==1" style="font-size: 20rpx;">斤</text></text>
							<text class="x1 flex1" v-else>￥{{item.sell_price}}<text v-if="item.product_type && item.product_type==1" style="font-size: 20rpx;">斤</text></text>
							<text class="x2">×{{item.num}}<text v-if="item.product_type && item.product_type==1" style="font-size: 20rpx;">斤</text></text>
						</view>
						
						<!-- <view class="t4 flex flex-x-bottom">
							<view class="btn3" v-if="detail.status==3 && item.iscomment==0 && shopset.comment==1" @tap.stop="goto" :data-url="'comment?ogid=' + item.id">去评价</view>
							<view class="btn3" v-if="detail.status==3 && item.iscomment==1" @tap.stop="goto" :data-url="'comment?ogid=' + item.id">查看评价</view>
						</view> -->
					</view>
				</view>
				<view v-if="item.remark" style="color:#999999;">备注：{{item.remark}}</view>
			</view>
			<!-- 带下单次数的列表 -->
			<view class="timeslist" v-for="(times, index) in prolist" :key="idx" v-if="detail.eattype ==1">
				<view class="tip flex flex-y-center ">
					<view class="t1">第 {{times.times}} 次下单</view>
					<view class="t1">下单成功，坐等开吃  <text style="color: #999;margin-left: 20rpx;">{{times.createtime}}</text></view>
				</view>
				<view  class="content " v-for="(item, idx) in times.prolist" :key="idx" style="border: none;">
					<view class="flex">
						<view @tap="goto" :data-url="'product?id=' + item.proid">
							<image :src="item.pic"></image>
						</view>
						<view class="detail">
							<text class="t1">{{item.name}}</text>
							<view class="t2 flex flex-y-center flex-bt">
								<view v-if="(item.ggtext && item.ggtext.length)" class="flex-col">
									<block v-for="(item2,index) in item.ggtext">
										<text class="t2">{{item2}}</text>
									</block>
								</view>
								<text v-if="item.ggname">{{item.ggname}} </text><text v-if="item.jltitle">{{item.jltitle}}</text>	
								<view class="btn3" v-if="detail.status==3 && item.iscomment==0 && shopset.comment==1 && item.is_show_comment==1" @tap.stop="goto" :data-url="'comment?ogid=' + item.id">去评价</view>
								<view class="btn3" v-if="detail.status==3 && item.iscomment==1" @tap.stop="goto" :data-url="'comment?ogid=' + item.id">查看评价</view>
							</view>
							<view class="t3">
								<text class="x1 flex1" v-if="item.jlprice">￥{{parseFloat(parseFloat(item.sell_price)+parseFloat(item.jlprice)).toFixed(2)}}<text v-if="item.product_type && item.product_type==1" style="font-size: 20rpx;">斤</text></text>
								<text class="x1 flex1" v-else>￥{{item.sell_price}}<text v-if="item.product_type && item.product_type==1" style="font-size: 20rpx;">斤</text></text>
								<text class="x2">×{{item.num}}<text v-if="item.product_type && item.product_type==1" style="font-size: 20rpx;">斤</text></text>
							</view>
						</view>
					</view>
					<view v-if="item.remark" style="color: #999999">备注：{{item.remark}}</view>
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
			<view class="item" v-if="detail.pack_fee > 0">
				<text class="t1">打包费</text>
				<text class="t2 red">+¥{{detail.pack_fee}}</text>
			</view>
			<view class="item" v-if="detail.leveldk_money > 0">
				<text class="t1">{{t('会员')}}折扣</text>
				<text class="t2 red">-¥{{detail.leveldk_money}}</text>
			</view>
			<view class="item" v-if="detail.manjian_money > 0">
				<text class="t1">满减活动</text>
				<text class="t2 red">-¥{{detail.manjian_money}}</text>
			</view>
			<view class="item" v-if="detail.tea_fee > 0">
				<text class="t1">{{shopset.tea_fee_text}}</text>
				<text class="t2 red">+¥{{detail.tea_fee}}</text>
			</view>
			<view class="item" v-if="detail.couponmoney > 0">
				<text class="t1">{{t('优惠券')}}抵扣</text>
				<text class="t2 red">-¥{{detail.coupon_money}}</text>
			</view>
			<view class="item" v-if="detail.scoredk > 0">
				<text class="t1">{{t('积分')}}抵扣</text>
				<text class="t2 red">-¥{{detail.scoredk_money}}</text>
			</view>
			<view class="item" v-if="detail.discount_money > 0">
				<text class="t1">优惠</text>
				<text class="t2 red">-¥{{detail.discount_money}}</text>
			</view>

			<view class="item" v-if="detail.scoredk > 0">
				<text class="t1">{{t('积分')}}抵扣</text>
				<text class="t2 red">-¥{{detail.scoredk_money}}</text>
			</view>
			<view class="item" v-if="detail.timing_money > 0">
				<text class="t1">{{shopset.timing_fee_text}}</text>
				<text class="t2 red">+¥{{detail.timing_money}}</text>
			</view>
			<view class="item" v-if="detail.service_money > 0">
				<text class="t1">服务费</text>
				<text class="t2 red">¥{{detail.service_money}}</text>
			</view>
			<view class="item">
				<text class="t1">实付款</text>
				<text class="t2 red">¥{{detail.totalprice}}</text>
			</view>

			<view class="item">
				<text class="t1">订单状态</text>
				<text class="t2" v-if="detail.status==0">未付款</text>
				<text class="t2" v-if="detail.status==1 && detail.paytypeid != 4">已付款</text>
				<text class="t2" v-if="detail.status==1 && detail.paytypeid == 4">线下支付</text>
				<text class="t2" v-if="detail.status==12">商家已接单</text>
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
				<text class="t1">退款时间</text>
				<text class="t2 red">{{detail.refund_time}}</text>
			</view>
			<view class="item" v-if="detail.refund_status>0">
				<text class="t1">退款原因</text>
				<text class="t2 red">{{detail.refund_reason}}</text>
			</view>
			<view class="item" v-if="detail.refund_checkremark">
				<text class="t1">审核备注</text>
				<text class="t2 red">{{detail.refund_checkremark}}</text>
			</view>
			
			<view class="item" v-if="detail.isfuwu && detail.fuwuendtime > 0">
				<text class="t1">到期时间</text>
				<text class="t2 red">{{_.dateFormat(detail.fuwuendtime,'Y-m-d H:i')}}</text>
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
			<!-- <view class="item flex-col" v-if="(detail.status==1 || detail.status==2) && detail.freight_type==1">
				<text class="t1">核销码</text>
				<view class="flex-x-center">
					<image :src="detail.hexiao_qr" style="width:500rpx;height:500rpx" @tap="previewImage" :data-url="detail.hexiao_qr"></image>
				</view>
				<text class="flex-x-center">请出示核销码给核销员进行核销</text>
			</view> -->
		</view>

		<view style="width:100%;height:calc(160rpx + env(safe-area-inset-bottom));"></view>

		<view class="bottom notabbarbot">
			<block v-if="detail.status==0">
				<!-- 开启餐后付款用户不可关闭订单（可能厨房已经做好或者已经吃了） -->
				<view class="btn2" v-if="shopset.pay_after != 1 " @tap="toclose" :data-id="detail.id">关闭订单</view>
				<view class="btn1" v-if="detail.eattype ==1" :style="{background:t('color1')}" @tap.stop="toBuy" >去结算</view>
				<view class="btn1" v-else :style="{background:t('color1')}" @tap="toPay" :data-id="detail.payorderid" :data-istopay="detail.is_topay" >去付款</view>
			</block>
			<!-- <block v-if="detail.status==1">
				<view class="btn2" @tap="quxiao" :data-id="detail.id">取消订单</view>
			</block> -->
			<block v-if="detail.status==12">
				<view v-if="detail.refund_status==0 || detail.refund_status==3" class="btn2" @tap="toRefund" :data-url="'refund?orderid=' + detail.id + '&price=' + detail.totalprice">申请退款</view>
			</block>
			<!-- <block v-if="detail.status==2 || detail.status==3">
				<view class="btn2" @tap="goto" :data-url="'logistics?express_com=' + detail.express_com + '&express_no=' + detail.express_no" v-if="detail.freight_type!=3 && detail.freight_type!=4">配送详情</view>
			</block> -->
			<block v-if="detail.status==2 || detail.status==1">
				<view v-if="detail.refund_status==0 || detail.refund_status==3" class="btn2" @tap="toRefund" :data-url="'refund?orderid=' + detail.id + '&price=' + detail.totalprice">申请退款</view>
			</block>
			<block v-if="(detail.status==1 || detail.status==2) && detail.freight_type==1">
				<view class="btn2" @tap="showhxqr">核销码</view>
			</block>
			<!-- <block v-if="detail.status==3 || detail.status==4">
				<view class="btn2" @tap="todel" :data-id="detail.id">删除订单</view>
			</block> -->
			<block v-if="detail.status==3 ">
				<view v-if="iscommentdp==0" class="btn1" :style="{background:t('color1')}" @tap="goto" :data-url="'commentdp?orderid=' + detail.id">评价店铺</view>
				<view v-if="iscommentdp==1" class="btn2" @tap="goto" :data-url="'commentdp?orderid=' + detail.id">查看评价</view>
			</block>
		</view>
		<uni-popup id="dialogHxqr" ref="dialogHxqr" type="dialog">
			<view class="hxqrbox">
				<image :src="detail.hexiao_qr" @tap="previewImage" :data-url="detail.hexiao_qr" class="img"/>
				<view class="txt">请出示核销码给核销员进行核销</view>
				<view class="close" @tap="closeHxqr">
					<image :src="pre_url+'/static/img/close2.png'" style="width:100%;height:100%"/>
				</view>
			</view>
		</uni-popup>
		<uni-popup id="dialogRefundTip" ref="dialogRefundTip" type="dialog">
			<view class="hxqrbox">
				<view class="txt" >您可以联系店内服务人员</view>
				<view class="txt" >或者点击下方按钮联系门店电话客服</view>
				<view class="callphone" :style="{background:t('color1')}" @click="call" :data-tel="shopset.tel">联系客服</view>
				
				<view class="close" @tap="closeRefundTip">
					<image :src="pre_url+'/static/img/close2.png'" style="width:100%;height:100%"/>
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
      prodata: '',
      djs: '',
      iscommentdp: "0",
      detail: "",
      prolist: "",
      shopset: "",
      storeinfo: "",
      lefttime: "",
      codtxt: ""
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
			app.get('ApiRestaurantShop/orderdetail', {id: that.opt.id}, function (res) {
				that.loading = false;
				that.iscommentdp = res.iscommentdp,
				that.detail = res.detail;
				that.prolist = res.prolist;
				that.shopset = res.shopset;
				that.storeinfo = res.storeinfo;
				that.lefttime = res.lefttime;
				that.codtxt = res.codtxt;
				// if (res.lefttime > 0) {
				// 	interval = setInterval(function () {
				// 		that.lefttime = that.lefttime - 1;
				// 		that.getdjs();
				// 	}, 1000);
				// }
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
        app.post('ApiRestaurantShop/delOrder', {orderid: orderid}, function (data) {
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
        app.post('ApiRestaurantShop/closeOrder', {orderid: orderid}, function (data) {
					app.showLoading(false);
          app.success(data.msg);
          setTimeout(function () {
            that.getdata();
          }, 1000);
        });
      });
    },
		quxiao:function(e){
			var that = this;
      var orderid = e.currentTarget.dataset.id;
      app.confirm('确定要取消订单吗?', function () {
				app.showLoading('提交中');
        app.post('ApiRestaurantShop/quxiao', {orderid: orderid}, function (data) {
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
        app.post('ApiRestaurantShop/orderCollect', {orderid: orderid}, function (data) {
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
		toPay:function(e){
			var payorderid = e.currentTarget.dataset.id;
			var istopay = e.currentTarget.dataset.istopay;
			if(istopay ==1){
				var url ='/pagesExt/pay/pay?id='+payorderid
				app.goto(url);
			}else{
				app.alert('请呼叫服务员结账');
				return;
			}
		},
		call:function(e){
			var tel = e.currentTarget.dataset.tel;
			uni.makePhoneCall({
				phoneNumber: tel
			});
		},
		toRefund(e){
			var url = e.currentTarget.dataset.url;
			var shopset = this.shopset;

			if(shopset && shopset.is_apply_refund ==0 ){
				this.$refs.dialogRefundTip.open();
			}else{
				app.goto(url);
			}
		},
		closeRefundTip:function(){
			this.$refs.dialogRefundTip.close();
		},
		toBuy(){
			var detail = this.detail
			var orderid = detail.id;
			var tableid = detail.tableid;
			var bid = detail.bid;
			var renshu = detail.renshu;
			app.goto('buy?orderid='+orderid+'&tableId='+tableid+'&bid='+bid+'&renshu='+renshu);
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
.product .content{position:relative;width: 100%; padding:16rpx 0px;border-bottom: 1px #e5e5e5 dashed;}
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

.orderinfo{ width:100%;margin-top:10rpx;padding: 14rpx 3%;background: #FFF;}
.orderinfo .item{display:flex;width:100%;padding:20rpx 0;border-bottom:1px dashed #ededed;}
.orderinfo .item:last-child{ border-bottom: 0;}
.orderinfo .item .t1{width:200rpx;}
.orderinfo .item .t2{flex:1;text-align:right}
.orderinfo .item .red{color:red}

.bottom{ width: 100%;height:calc(92rpx + env(safe-area-inset-bottom));padding: 0 20rpx;background: #fff; position: fixed; bottom: 0px;left: 0px;display:flex;justify-content:flex-end;align-items:center;}

.btn1{margin-left:20rpx;width:160rpx;height:60rpx;line-height:60rpx;color:#fff;border-radius:3px;text-align:center}
.btn2{margin-left:20rpx;width:160rpx;height:60rpx;line-height:60rpx;color:#333;background:#fff;border:1px solid #cdcdcd;border-radius:3px;text-align:center}
.btn3{font-size:24rpx;width:120rpx;height:50rpx;line-height:50rpx;color:#333;background:#fff;border:1px solid #cdcdcd;border-radius:3px;text-align:center}

.btitle{ width:100%;height:100rpx;background:#fff;padding:0 20rpx;border-bottom:1px solid #f5f5f5}
.btitle .comment{border: 1px #ffc702 solid;border-radius:10rpx;background:#fff; color: #ffc702;  padding: 0 10rpx; height: 46rpx; line-height: 46rpx;}
.btitle .comment2{border: 1px #ffc7c0 solid;border-radius:10rpx;background:#fff; color: #ffc7c0;  padding: 0 10rpx; height: 46rpx; line-height: 46rpx;}

.hxqrbox{background:#fff;padding:50rpx;position:relative;border-radius:20rpx}
.hxqrbox .img{width:400rpx;height:400rpx}
.hxqrbox .txt{color:#666;margin-top:20rpx;font-size:26rpx;text-align:center}
.hxqrbox .close{width:50rpx;height:50rpx;position:absolute;bottom:-100rpx;left:50%;margin-left:-25rpx;border:1px solid rgba(255,255,255,0.5);border-radius:50%;padding:8rpx}
.callphone{width:160rpx;height:60rpx;line-height:60rpx;color:#333;background:#fff;text-align:center;border-radius: 50rpx;color: #FFF;margin: 0 auto;margin-top: 20rpx; }
.refundtipbox{background:#fff;padding:50rpx;position:relative;border-radius:20rpx;}
.refundtipbox .txt{color:#666;font-size:26rpx;text-align:center;margin-top: 0;margin-bottom: 20rpx;letter-spacing:4rpx}
.timeslist{border-bottom: 1px #e5e5e5 dashed;padding: 20rpx 0;}
.timeslist:last-child{ border-bottom: 0; }
.tip{font-size: 28rpx;color: #666;justify-content: space-between;padding: 10rpx 0;}

</style>