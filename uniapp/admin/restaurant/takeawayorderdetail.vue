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
				<view class="t2">请尽快接单</view>
			</view>
			<view class="f1" v-if="detail.status==12">
				<view class="t1">{{detail.paytypeid==4 ? '已选择'+detail.paytype : '已成功付款'}}</view>
				<view class="t2" v-if="detail.freight_type!=1">请尽快发货</view>
				<view class="t2" v-if="detail.freight_type==1">待提货</view>
			</view>
			<view class="f1" v-if="detail.status==2">
				<view class="t1">订单已发货</view>
				<view class="t2" v-if="detail.freight_type!=3 && detail.freight_type!=2">发货信息：{{detail.express}} {{detail.express_no}}</view>
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
				<image :src="pre_url+'/static/img/address3.png'"></image>
			</view>
			<view class="info">
				<text class="t1">{{detail.linkman}} {{detail.tel}}</text>
				<text class="t2" v-if="detail.freight_type!=1 && detail.freight_type!=3">地址：{{detail.area}}{{detail.address}}</text>
				<text class="t2" v-if="detail.freight_type==1" @tap="openLocation" :data-address="storeinfo.address" :data-latitude="storeinfo.latitude" :data-longitude="storeinfo.longitude">取货地点：{{storeinfo.name}} - {{storeinfo.address}}</text>
			</view>
		</view>
		<view class="product">
			<view v-for="(item, idx) in prolist" :key="idx" class="content">
				<view>
					<image :src="item.pic"></image>
				</view>
				<view class="detail">
					<text class="t1">{{item.name}}</text>
					<text class="t2">{{item.ggname}}{{item.jltitle?item.jltitle:''}}</text>
					<view class="t3">
						
						<text class="x1 flex1" v-if="item.jlprice">￥{{  parseFloat(parseFloat(item.sell_price)+parseFloat(item.jlprice)).toFixed(2) }}</text>
						<text class="x1 flex1" v-else>￥{{item.sell_price}}</text>
						<block v-if="goods_hexiao_status && (detail.status==1 || detail.status==2 || detail.status==12) && detail.freight_type==1 && item.hexiao_code && item.num>0">
						    <view>
										<view v-if="item.status == 3" style="color: #999;">已核销</view>
						        <view v-else style="color: #f60;">未核销</view>
						    </view>
						</block>
						<text class="x2">×{{item.num}}</text>
					</view>
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
			<view class="item" v-if="detail.leveldk_money > 0">
				<text class="t1">{{t('会员')}}折扣</text>
				<text class="t2 red">-¥{{detail.leveldk_money}}</text>
			</view>
			<view class="item" v-if="detail.jianmoney > 0">
				<text class="t1">满减活动</text>
				<text class="t2 red">-¥{{detail.manjian_money}}</text>
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
			<view class="item" v-if="detail.couponmoney > 0">
				<text class="t1">{{t('优惠券')}}抵扣</text>
				<text class="t2 red">-¥{{detail.coupon_money}}</text>
			</view>
			
			<view class="item" v-if="detail.scoredk > 0">
				<text class="t1">{{t('积分')}}抵扣</text>
				<text class="t2 red">-¥{{detail.scoredk_money}}</text>
			</view>
			<view class="item">
				<text class="t1">{{detail.status ==0?'应付':'实付'}}款</text>
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
			<view class="item flex-col" v-if="(detail.status==1 || detail.status==12 || detail.status==2) && detail.freight_type==1">
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
			<view v-if="detail.status==1" class="btn2" @tap="refund" :data-id="detail.id">拒单退款</view>
			<view v-if="detail.status==1" class="btn2" @tap="jiedan" :data-id="detail.id">接单</view>
			<view v-if="detail.status==12" class="btn2" @tap="fahuo" :data-id="detail.id">发货</view>
			<block v-if="detail.status==12 && detail.canpeisong">
				<view class="btn2" v-if="detail.express_wx_status" @tap="peisongWx" :data-id="detail.id">即时配送</view>
        <view class="btn2" v-else-if="detail.myt_status"   @tap="peisongMyt" :data-id="detail.id">麦芽田配送</view>
				<view class="btn2" v-else @tap="peisong" :data-id="detail.id">配送</view>
			</block>
			<view v-if="detail.status==2 || detail.status==3" class="btn2" @tap="goto" :data-url="'/pagesExt/order/logistics?express_com='+detail.express_com+'&express_no='+detail.express_no+'&type='+detail.express_type">查物流</view>
			<view v-if="detail.status==4" class="btn2" @tap="delOrder" :data-id="detail.id">删除</view>
			<view class="btn2" @tap="setremark" :data-id="detail.id">设置备注</view>
			<view class="btn2" @tap="print" :data-id="detail.id">打印小票</view>
			<view class="btn2" v-if="shopset.is_refund &&( detail.status==1 || detail.status==2 || detail.status==3 || detail.status==12)" @tap="refundinit" :data-id="detail.id">退款</view>
		</view>
		<uni-popup id="dialogSetremark" ref="dialogSetremark" type="dialog">
			<uni-popup-dialog mode="input" title="设置备注" :value="detail.remark" placeholder="请输入备注" @confirm="setremarkconfirm"></uni-popup-dialog>
		</uni-popup>

		<uni-popup id="dialogExpress" ref="dialogExpress" type="dialog">
			<view class="uni-popup-dialog">
				<view class="uni-dialog-title">
					<text class="uni-dialog-title-text">发货</text>
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
							<input type="text" placeholder="请输入快递单号" @input="setexpressno" style="border: 1px #eee solid;padding: 10rpx;height:70rpx;border-radius:4px;flex:1;font-size:28rpx;"/>
						</view>
					</view>
				</view>
				<view class="uni-dialog-button-group">
					<view class="uni-dialog-button" @click="dialogExpressClose">
						<text class="uni-dialog-button-text">取消</text>
					</view>
					<view class="uni-dialog-button uni-border-left" @click="confirmfahuo">
						<text class="uni-dialog-button-text uni-button-color">确定</text>
					</view>
				</view>
			</view>
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
        
    <uni-popup id="dialogExpress11" ref="dialogExpress11" type="dialog">
      <view class="uni-popup-dialog">
        <view class="uni-dialog-title">
          <text class="uni-dialog-title-text">配送设置</text>
        </view>
        <view class="uni-dialog-content" style="display: block;">
          <block v-if="mytdata">
            <block v-if="!mytdata.msg">
                <scroll-view scroll-Y="true" style="width: 100%;max-height: 500rpx;">
                  <block  v-for="item in mytdata.detail" :key="index">
                    <view class="flex-y-center flex-x-center" style="margin:20rpx 20rpx;">
                      <view style="font-size:28rpx;color:#555;width: 130rpx;">{{item.name}}：</view>
                      <view style="padding: 10rpx;flex:1;font-size:28rpx;line-height: 40rpx;">
                        <block v-if="!item.error_message">
                          <view>配送费：{{item.amount}}元</view> 
                          <view>距离：{{item.distance}}米</view>
                        </block>
                        <block v-else>
                          <view>计价失败原因：{{item.error_message}}</view>
                        </block>
                      </view>
                    </view>
                  </block>
                </scroll-view>
                <view v-if="detail.myt_shop" class="flex-y-center flex-x-center" style="margin:20rpx 20rpx;">
                  <view style="font-size:28rpx;color:#555;width: 130rpx;">门店：</view>
                    <picker @change="mytshopChange" :value="mytindex" :range="detail.myt_shoplist"  range-key='name' style="font-size:28rpx;border: 1px #eee solid;padding:0 10rpx;height:70rpx;border-radius:4px;flex:1;line-height: 52rpx;">
                      <view class="picker">{{detail.myt_shoplist[mytindex]['name']}}</view>
                    </picker>
                </view>
                <view class="flex-y-center flex-x-center" style="margin:20rpx 20rpx;">
                  <view style="font-size:28rpx;color:#555;width: 130rpx;">重量：</view>
                  <input type="text" placeholder="请输入重量(选填)" @input="mytWeight" :value="myt_weight" style="border: 1px #eee solid;padding:0 10rpx;height:70rpx;border-radius:4px;flex:1;font-size:28rpx;"/>
                  kg
                </view>
                <view class="flex-y-center flex-x-center" style="margin:20rpx 20rpx;">
                  <view style="font-size:28rpx;color:#555;width: 130rpx;">备注：</view>
                  <input type="text" placeholder="请输入备注(选填)" @input="mytRemark" style="border: 1px #eee solid;padding:0 10rpx;height:70rpx;border-radius:4px;flex:1;font-size:28rpx;"/>
                </view>
            </block>
            <block  v-else>
              <view class="flex-y-center flex-x-center" style="margin:20rpx 20rpx;">
                <view style="font-size:28rpx;color:#555">错误信息：</view>
                <view style="padding: 10rpx;flex:1;font-size:28rpx;line-height: 40rpx;">{{mytdata.msg}}</view>
              </view>
            </block>
          </block>
          <block v-else>
            <view class="flex-y-center flex-x-center" style="margin:20rpx 20rpx;">
              <view style="font-size:28rpx;color:#555">错误：</view>
              <view style="padding: 10rpx;height:70rpx;flex:1;font-size:28rpx;">无数据返回</view>
            </view>
          </block>
        </view>
        <view class="uni-dialog-button-group">
          <view class="uni-dialog-button" @click="dialogExpress11Close">
            <text class="uni-dialog-button-text">取消</text>
          </view>
          <view class="uni-dialog-button uni-border-left" @click="confirmfahuo11">
            <text class="uni-dialog-button-text uni-button-color">确定</text>
          </view>
        </view>
      </view>
    </uni-popup>
	<uni-popup id="dialogRefund" ref="dialogRefund" type="dialog" :mask-click="false">
		<view class="uni-popup-dialog">
			<view class="uni-dialog-title">
				<text class="uni-dialog-title-text">退款</text>
			</view>
			<view class="uni-dialog-content">
				<view>
					<view class="product" style="width: 100%;margin:0;padding:0">
						<scroll-view class="popup-content" scroll-y="true" style="max-height: 600rpx;overflow: hidden;">
						<view v-for="(item, idx) in returnProlist" :key="idx" class="box">
							<view class="content">
								<view @tap="goto" :data-url="'/pages/shop/product?id=' + item.proid">
									<image :src="item.pic" style="width: 110rpx;height: 110rpx"></image>
								</view>
								<view class="detail">
									<text class="t1">{{item.name}}</text>
									<text class="t2">{{item.ggname}}</text>
									<view class="t3">
										<text class="x1 flex1">￥{{item.sell_price}}×{{item.num}}</text>
										<view style="color: #888;font-size: 24rpx;display: flex;">
											<text>退货数量</text>
											<input type="number" :value="item.can_num" :data-max="item.can_num" :data-ogid="item.id" @input="retundInput" class="retundNum" style="border: 1px #eee solid;width: 80rpx;margin-left: 10rpx;text-align: center;">
										</view>
									</view>
								</view>
							</view>
						</view>
						</scroll-view>
					</view>
					
					<view class="flex-y-center flex-x-center" style="margin:20rpx 20rpx;height: 80rpx;">
						<view style="font-size:28rpx;color:#555">退款原因：</view>
						<input type="text" placeholder="请输入退款原因" @input="refundMoneyReason" adjust-position="false" style="border: 1px #eee solid;height:70rpx;border-radius:4px;flex:1;font-size:28rpx;padding:0 10rpx"/>
					</view>
					<view class="flex-y-center flex-x-center" style="margin:20rpx 20rpx;height: 80rpx">
						<view style="font-size:28rpx;color:#555">退款金额：</view>
						<input type="text" placeholder="请输入退款金额" @input="refundMoney" adjust-position="false" :value="refundTotalprice" style="border: 1px #eee solid;height:70rpx;border-radius:4px;flex:1;font-size:28rpx;padding:0 10rpx"/>
					</view>
				</view>
			</view>
			<view class="uni-dialog-button-group">
				<view class="uni-dialog-button" @click="dialogRefundClose">
					<text class="uni-dialog-button-text">取消</text>
				</view>
				<view class="uni-dialog-button uni-border-left" @click="gotoRefundMoney()">
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
        goods_hexiao_status:false,
        
        myt_weight:'',
        myt_remark:'',
        mytindex:0,
        myt_shop_id:0,
        mytdata:'',
		returnProlist:[], //退款商品
		refundNum:[],//退款数据
		refundTotalprice:0,//退款总金额
		refundReason:''//退款理由
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
			app.get('ApiAdminRestaurantTakeawayOrder/detail', {id: that.opt.id}, function (res) {
				that.loading = false;
                if(res.goods_hexiao_status){
                    that.goods_hexiao_status = true
                }
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
			app.post('ApiAdminOrder/setremark', { type:'restaurant_takeaway',orderid: that.detail.id,content:remark }, function (res) {
				app.success(res.msg);
				setTimeout(function () {
					that.getdata();
				}, 1000)
			})
    },
		fahuo:function(){
			this.$refs.dialogExpress.open();
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
		confirmfahuo:function(){
			this.$refs.dialogExpress.close();
			var that = this
			var express_com = this.expressdata[this.express_index]
			app.post('ApiAdminOrder/sendExpress', { type:'restaurant_takeaway',orderid: that.detail.id,express_no:that.express_no,express_com:express_com}, function (res) {
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
				app.post('ApiAdminOrder/ispay', { type:'restaurant_takeaway',orderid: orderid }, function (data) {
					app.showLoading(false);
					app.success(data.msg);
					setTimeout(function () {
						that.getdata();
					}, 1000)
				})
			});
		},
		jiedan:function(e){
			var that = this;
			var orderid = e.currentTarget.dataset.id
			app.confirm('确定要接单吗?', function () {
				app.showLoading('提交中');
				app.post('ApiAdminOrder/jiedan', { type:'restaurant_takeaway',orderid: orderid }, function (data) {
					app.showLoading(false);
					app.success(data.msg);
					setTimeout(function () {
						that.getdata();
					}, 1000)
				})
			});
		},
		refund: function (e) {
			var that = this;
			var orderid = e.currentTarget.dataset.id
			app.confirm('确定要拒单并退款吗?', function () {
				app.showLoading('提交中');
				app.post('ApiAdminOrder/judan', { type:'restaurant_takeaway',orderid: orderid }, function (data) {
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
				app.post('ApiAdminOrder/delOrder', { type:'restaurant_takeaway',orderid: orderid }, function (data) {
					app.showLoading(false);
					app.success(data.msg);
					setTimeout(function () {
						app.goto('takeawayorder');
					}, 1000)
				});
			})
		},
		closeOrder: function (e) {
			var that = this;
			var orderid = e.currentTarget.dataset.id
			app.confirm('确定要关闭该订单吗?', function () {
				app.showLoading('提交中');
				app.post('ApiAdminOrder/closeOrder', { type:'restaurant_takeaway',orderid: orderid }, function (data) {
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
				app.post('ApiAdminOrder/refundnopass', { type:'restaurant_takeaway',orderid: orderid }, function (data) {
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
				app.post('ApiAdminOrder/refundpass', { type:'restaurant_takeaway',orderid: orderid }, function (data) {
					app.showLoading(false);
					app.success(data.msg);
					setTimeout(function () {
						that.getdata();
					}, 1000)
				})
			});
		},
		print: function (e) {
			var that = this;
			var orderid = e.currentTarget.dataset.id
			app.showLoading('打印中');
			app.post('ApiAdminOrder/print', { type:'restaurant_takeaway',orderid: orderid }, function (data) {
				app.showLoading(false);
				app.success(data.msg);
			})
		},
		peisong:function(){
			var that = this;
			that.loading = true;
			app.post('ApiAdminOrder/getpeisonguser',{type:'restaurant_takeaway_order',orderid:that.detail.id},function(res){
				that.loading = false;
				var peisonguser = res.peisonguser
				var paidantype = res.paidantype
				var psfee = res.psfee
				var ticheng = res.ticheng

				var peisonguser2 = [];
				for(var i in peisonguser){
					peisonguser2.push(peisonguser[i].title);
				}
				that.peisonguser = res.peisonguser;
				that.peisonguser2 = peisonguser2;
				if(paidantype==1){
					that.$refs.dialogPeisong.open();
				}else{
					if(that.detail.bid == 0){
						var tips='选择配送员配送，订单将发布到抢单大厅由配送员抢单，配送员提成￥'+ticheng+'，确定要配送员配送吗？';
					}else{
						var tips='选择配送员配送，订单将发布到抢单大厅由配送员抢单，需扣除配送费￥'+psfee+'，确定要配送员配送吗？';
					}
					if(paidantype == 2){
						var psid = '-1';
					}else{
						var psid = '0';
					}
					app.confirm(tips,function(){
						app.post('ApiAdminOrder/peisong', { type:'restaurant_takeaway_order',orderid: that.detail.id,psid:psid}, function (res) {
							app.success(res.msg);
							setTimeout(function () {
								that.getdata();
							}, 1000)
						})
					})
				}
			})
		},
		dialogPeisongClose:function(){
			this.$refs.dialogPeisong.close();
		},
		peisongChange:function(e){
			this.index2 = e.detail.value;
		},
		confirmPeisong:function(){
			var that = this
			var psid = this.peisonguser[this.index2].id
			app.post('ApiAdminOrder/peisong', { type:'restaurant_takeaway_order',orderid: that.detail.id,psid:psid}, function (res) {
				app.success(res.msg);
				that.$refs.dialogPeisong.close();
				setTimeout(function () {
					that.getdata();
				}, 1000)
			})
		},
		peisongWx:function(){
			var that = this;
			var psfee = that.detail.freight_price;
			if(that.detail.bid == 0){
				var tips='选择即时配送，订单将派单到第三方配送平台，并扣除相应费用，确定要派单吗？';
			}else{
				var tips='选择即时配送，订单将派单到第三方配送平台，需扣除配送费￥'+psfee+'，确定要派单吗？';
			}
			app.confirm(tips,function(){
				that.loading = true;
				app.post('ApiAdminOrder/peisongWx', { type:'restaurant_takeaway_order',orderid: that.detail.id}, function (res) {
					that.loading = false;
					app.success(res.msg);
					setTimeout(function () {
						that.getdata();
					}, 1000)
				})
			})
		},
    peisongMyt:function(e){
        var that = this;
        var detail = that.detail;
        if(detail.myt_set){
          var data = {
              type:'restaurant_takeaway_order',
              orderid: detail.id,
          }
          app.post('ApiAdminOrder/mytprice', data, function (res) {
              that.loading = false;
              if(res.status == 1){
                  that.mytdata = res.data;
                  if(res.data.weight){
                    that.myt_weight = res.data.weight
                  }
                  that.$refs.dialogExpress11.open();
              }else{
                  app.alert(res.msg)
              }
          })
        }
    },
    goMyt:function(){
        var that = this;
        var detail = that.detail;
        var tips='选择麦芽田配送，订单将派单到第三方配送平台，并扣除相应费用，确定要派单吗？';
        app.confirm(tips,function(){
            that.$refs.dialogExpress11.close();
            that.loading = true;
            var data = {
                type:'restaurant_takeaway_order',
                orderid: detail.id,
                myt_weight:that.myt_weight,
                myt_remark:that.myt_remark,
                myt_shop_id:that.myt_shop_id
            }
            app.post('ApiAdminOrder/peisong', data, function (res) {
                that.loading = false;
                if(res.status == 1){
                    app.success(res.msg);
                    setTimeout(function () {
                        that.getdata();
                    }, 1000)
                }else{
                    app.alert(res.msg)
                }
                
            })
        })
    },
    confirmfahuo11:function(){
      var that = this
      that.goMyt();
    },
    dialogExpress11Close:function(){
      this.$refs.dialogExpress11.close();
    },
    mytWeight:function(e){
      this.myt_weight = e.detail.value;
    },
    mytRemark:function(e){
      this.myt_remark = e.detail.value;
    },
    mytshopChange:function(e){
        var that = this;
        var detail   = that.detail;
        var mytindex = e.detail.value;
        that.mytindex = mytindex;
        //that.myt_name  = detail.myt_shoplist[mytindex]['name'];
        that.myt_shop_id    = detail.myt_shoplist[mytindex]['id'];
    },
	refundinit:function(e){
		var that = this;
		that.loading = true;
		app.post('ApiAdminRestaurantTakeawayOrder/refundProlist',{orderid:that.detail.id},function(data){
			
			let prolist = data.prolist;
			that.returnProlist = data.prolist;
			that.refundTotalprice = data.detail.totalprice;
			
			for(var i in prolist){
				that.refundNum.push({
					'ogid':prolist[i].id,
					'num':prolist[i].can_num
				})
			}
			that.loading = false;
			that.$refs.dialogRefund.open();
			
		})
	},
	retundInput:function(e){
		var that = this;
		var valnum = e.detail.value;
		var {max,ogid} = e.currentTarget.dataset;
		var prolist = that.returnProlist;
		var refundNum = that.refundNum;
		var total = 0;
		valnum = !valnum?0:valnum;
		if(valnum > max){
			return app.error('请输入正确的数量');
		}
		for(var i in refundNum){
			if(refundNum[i].ogid == ogid){
				refundNum[i].num = valnum;
			}
			console.log(prolist[i]);
			var pro = prolist[i];
			if(refundNum[i].num == pro.num){
				total += parseFloat(prolist[i].real_totalprice)
			}else{
				total += refundNum[i].num * parseFloat(prolist[i].real_totalprice) / prolist[i].num 
			}
		}
		total = parseFloat(total);
		total = total.toFixed(2);
		that.refundTotalprice = total; 
	},
	refundMoneyReason:function(e){
		this.refundReason = e.detail.value;
	},
	refundMoney:function(e){
		this.refundTotalprice = e.detail.value;
	},
	dialogRefundClose:function(){
		this.returnProlist = [];
		this.refundReason = '';
		this.$refs.dialogRefund.close();
	},
	gotoRefundMoney:function(){
		var that = this;
		console.log(that.refundNum,11111);
		app.confirm('确定要退款吗?', function () {
			that.$refs.dialogRefund.close();
			app.showLoading('提交中');
			app.post('ApiAdminRestaurantTakeawayOrder/refund',{
				orderid:that.detail.id,
				refundNum:that.refundNum,
				reason:that.refundReason,
				money:that.refundTotalprice
			},function(res){
				if(res.status == 0){
					app.error(res.msg);
					return;
				}
				app.showLoading(false);
				app.success(res.msg);
				setTimeout(function () {
					that.getdata();
					that.refundinit();
				}, 1000)
			})
		});
	},
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