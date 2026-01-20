<template>
<view class="container">
	<block v-if="isload">
		<dd-tab :itemdata="['全部','待付款','待发货','待收货','已完成','退款/售后']" :itemst="['all','0','1','2','3','5']" :st="st" :isfixed="true" @changetab="changetab"></dd-tab>
		<view style="width:100%;height:100rpx"></view>
		<!-- #ifndef H5 || APP-PLUS -->
		<view class="topsearch flex-y-center">
			<view class="f1 flex-y-center">
				<image class="img" :src="pre_url+'/static/img/search_ico.png'"></image>
				<input :value="keyword" placeholder="输入关键字搜索" placeholder-style="font-size:24rpx;color:#C2C2C2" @confirm="searchConfirm"></input>
			</view>
		</view>
		<!--  #endif -->
		<view class="order-content">
			<block v-for="(item, index) in datalist" :key="index">
				<view class="order-box" @tap="goto" :data-url="'detail?id=' + item.id">
					<view class="head">
            <!-- 眼镜 显示订单号-->
            <block v-if="glass_order_custom == 1">
              <view class="f1">订单号：{{item.ordernum}} <text @click.stop="copy" :data-text='item.ordernum' style="margin-left: 20rpx;color: #599dfd;">复制</text></view>
            </block>
            <block v-else>
              <view class="f1" v-if="item.bid!=0" @tap.stop="goto" :data-url="'/pagesExt/business/index?id=' + item.bid"><image :src="pre_url+'/static/img/ico-shop.png'"></image> {{item.binfo.name}}</view>
              <view class="f1" v-else><image :src="item.binfo.logo" class="logo-row"></image> {{item.binfo.name}}</view>
						</block>
            <view class="flex1">
								<text style="color:orangered; margin-left: 10rpx;" v-if="item.yuding_type && item.yuding_type =='1'">[预定订单]</text>
						</view>
            <text style="color:orangered; margin-left: 10rpx;" v-if="item.wxtc && item.wxtc_status_name">{{item.wxtc_status_name}}</text>
						<text v-if="item.status==0" class="st0">待付款</text>
						<text v-if="item.status==1" class="st1">待发货</text>
						<text v-if="item.status==2" class="st2">待收货</text>
						<text v-if="item.status==3" class="st3">已完成</text>
						<text v-if="item.status==4" class="st4">已关闭</text>
						<text v-if="item.status==8" class="st8">待提货</text>
					</view>

					<block v-for="(item2, idx) in item.prolist" :key="idx">
						<view class="content" :style="idx+1==item.procount?'border-bottom:none':''">
							<view @tap.stop="goto" :data-url="'/pages/shop/product?id=' + item2.proid">
								<image :src="item2.pic" mode='aspectFill'></image>
							</view>
							<view class="detail">
								<text class="t1">{{item2.name}}</text>
								<text class="t2">{{item2.gg_group_title ? item2.gg_group_title:''}} {{item2.ggname}}</text>
                <text class="t2" v-if="item2.protype && item2.protype == 3">手工费：{{item2.hand_fee}}</text>
								<block>
									<view class="t3" v-if="item2.product_type && item2.product_type==2">
										<text class="x1 flex1">{{item2.real_sell_price}}元/斤</text>
										<text class="x2">×{{item2.real_total_weight}}斤</text>
									</view>
									<view class="t3" v-else>
										<text class="x1 flex1"><text v-if="showprice_dollar && item2.usd_sellprice">${{item2.usd_sellprice}} </text>￥{{item2.sell_price}}
										<text v-if="!isNull(item2.service_fee) && item2.service_fee > 0">+{{item2.service_fee}}{{t('服务费')}}</text>
										<text v-if="!isNull(item2.freezemoney_price) && item2.freezemoney_price > 0">+{{item2.freezemoney_price}}{{t('冻结资金')}}</text>
										</text>
										<text class="x2">×{{item2.num}}</text>
									</view>
								</block>
								<block v-if="mendian_no_select==1 && item2.is_hx">
									<view class="t3"><text class="x2">已核销</text></view>
								</block>
								<view class="t2 tgr" v-if="item2.has_glassrecord">
									{{item2.glassrecord.name}} 
									{{item2.glassrecord.nickname?item2.glassrecord.nickname:''}} 
									{{item2.glassrecord.check_time?item2.glassrecord.check_time:''}}
									{{item2.glassrecord.typetxt}}
									<!-- <block>
										<text class="pdl10" v-if="item2.glassrecord.double_ipd==0">{{item2.glassrecord.ipd?'PD'+item2.glassrecord.ipd:''}}</text>
										<text class="pdl10" v-else> PD R{{item2.glassrecord.ipd_right}} L{{item2.glassrecord.ipd_left}}</text>
									</block> -->
								</view>

								<block v-if="(item.status==1 || item.status==2 || item.status==8) && (item.freight_type==1 || item.freight_type==5) && item2.is_quanyi!=1 && item2.hexiao_code">
									<view class="btn2" @tap.stop="showhxqr2" :data-id="item2.id" :data-num="item2.num" :data-hxnum="item2.hexiao_num" :data-hexiao_code="item2.hexiao_code" style="position:absolute;top:40rpx;right:0rpx;">核销码</view>
								</block>
								<block v-if="(item.status==1 || item.status==2 || item.status==8) && item2.is_quanyi==1 && item2.hexiao_code">
									<view class="btn2" @tap.stop="showhxqr2" :data-id="item2.id" :data-num="item2.hexiao_num_total" :data-hxnum="item2.hexiao_num_used" :data-hexiao_code="item2.hexiao_code" :data-hexiao_tip="item2.hexiao_tip" style="position:absolute;top:40rpx;right:0rpx;">核销码</view>
								</block>
							</view>
						</view>
					</block>
          <view v-if="item.crk_givenum && item.crk_givenum>0" style="color:#f60;line-height:70rpx">+随机赠送{{item.crk_givenum}}件</view>
          <view class="bottom" v-if="item.isdygroupbuy && item.isdygroupbuy==1">
          	<text style="color:red">抖音团购券</text>
          </view>
					<view class="bottom">
						<view>共计{{item.procount}}件商品 实付:￥{{item.totalprice}}  
						<text v-if="!isNull(item.total_freezemoney_price) && item.total_freezemoney_price > 0">+{{item.total_freezemoney_price}}{{t('冻结资金')}}</text>
						<text v-if="item.balance_price > 0 && item.balance_pay_status == 0"  style="display: block; float: right;">尾款：￥{{item.balance_price}}</text>
						</view>
						<text v-if="item.refund_status==1" style="color:red;padding-left:6rpx">退款中￥{{item.refund_money}}</text>
						<text v-if="item.refund_status==2" style="color:red;padding-left:6rpx">已退款￥{{item.refund_money}}</text>
						<text v-if="item.refund_status==3" style="color:red;padding-left:6rpx">退款申请已驳回</text>
					</view>
					
          <view class="bottom" v-if="glass_order_custom == 1">
            <view class="binfo-name">
              <text v-if="item.bid!=0">{{item.binfo.name}}</text>
              <text v-else>{{item.binfo.name}}</text>
              <text v-if="item.express_com">{{item.express_com}}</text>
              <text v-if="item.express_no">{{item.express_no}} </text>
              <text v-if="item.express_no"  @click.stop="copy" :data-text='item.express_no' style="color:#599dfd">复制</text>
            </view>
            <view class="member-content">
              <view>收货人：{{item.linkman}} {{item.tel}}</view>
              <view>{{item.area}}{{item.address}}</view>
              <view v-if="item.message">备注：{{item.message}}</view>
            </view>
          </view>
					<view class="bottom" v-if="item.tips!=''">
						<text style="color:red">{{item.tips}}</text>
					</view>
					<!-- 预约提货时间 -->
					<view class="bottom" v-if="!isNull(item.exchange_card_take_date)  && item.exchange_card_take_date">
						<text>预约提货时间：{{item.exchange_card_take_date}}</text>
					</view>
					<!-- 预约提货时间 -->
					<view class="op">
						<block v-if="item.is_pingce == 1 && (item.status==1 || item.status==2 || item.status==3)">
							<view class="btn1" @tap.stop="viewReport" :data-id="item.id" v-if="item.pingce_status == 2" :style="{background:t('color1')}">查看报告</view>
							<view class="btn1" @tap.stop="toevaluate" :data-id="item.id" v-else :style="{background:t('color1')}">继续测评</view>
						</block>
						<block v-if="([1,2,3]).includes(item.status) && item.invoice">
							<view class="btn2" @tap.stop="goto" :data-url="'invoice?type=shop&orderid=' + item.id">发票</view>
						</block>
						<view v-if="item.is_fenqi && item.is_fenqi == 1" @tap.stop="fenqiDetails(item)" class="btn2">分期详情</view>
						<view @tap.stop="goto" :data-url="'detail?id=' + item.id" class="btn2">详情</view>
            <block v-if="item.status!=4 && item.transfer_order_parent_check">
              <view @tap.stop="transferOrder" :data-orderid="item.id" class="btn2" style="max-width: 220rpx">转给上级审核</view>
            </block>
						<block v-if="item.status==0">
							<view class="btn2" @tap.stop="toclose" :data-id="item.id">关闭订单</view>
							<view class="btn1" v-if="item.paytypeid!=5 && item.is_fenqi != 1" :style="{background:t('color1')}" @tap.stop="goto" :data-url="'/pagesExt/pay/pay?id=' + item.payorderid">去付款</view>
							<block v-if="item.paytypeid==5">
									<view class="btn1" v-if="item.transfer_check == 1" :style="{background:t('color1')}" @tap.stop="goto" :data-url="'/pages/pay/transfer?id=' + item.payorderid">付款凭证</view>
									<view class="btn1" v-else :style="{background:t('color1')}">
											<text v-if="item.transfer_check == 0">转账待审核</text>
											<text v-if="item.transfer_check == -1">转账已驳回</text>
									</view>
							</block>
						</block>
            <view class="btn1" v-if="item.cancodpay" :style="{background:t('color1')}" @tap="gocodpay" :data-id="item.id">在线支付</view>
						<block v-if="item.status==1">
							<block v-if="item.paytypeid!='4' && item.is_fenqi != 1">
								<view class="btn2" @tap.stop="goto" :data-url="'refundSelect?orderid=' + item.id + '&price=' + item.totalprice" v-if="item.order_can_refund==1 && item.procanrefund==1 && item.refundnum < item.procount">退款</view>
								
							</block>
							<block v-else>
								<!-- <view class="btn2">{{codtxt}}</view> -->
							</block>
							<view class="btn2" v-if="item.freight_type==1 && item.freight_type1_shipping_status==1" @tap.stop="logistics" :data-index="index">查看物流</view>
						</block>
						<block v-if="item.status==2 || item.status==8">
							<block v-if="item.paytypeid!='4'">
								<view class="btn2" @tap.stop="goto" :data-url="'refundSelect?orderid=' + item.id + '&price=' + item.totalprice" v-if="item.order_can_refund==1 && item.procanrefund==1 && item.refundnum < item.procount">退款</view>
							</block>
							<block v-else>
								<!-- <view class="btn2">{{codtxt}}</view> -->
							</block>
							<block v-if="item.freight_type!=3 && item.freight_type!=4">
									<view class="btn2" v-if="item.express_type =='express_wx'" @tap.stop="logistics" :data-index="index">订单跟踪</view>
									<view class="btn2" v-else @tap.stop="logistics" :data-index="index">查看物流</view>
							</block>
							
							<view v-if="item.balance_pay_status == 0 && item.balance_price > 0" class="btn1" :style="{background:t('color1')}" @tap.stop="goto" :data-url="'/pagesExt/pay/pay?id=' + item.balance_pay_orderid">支付尾款</view>
							<view class="btn-wrapper" v-if="item.can_collect && item.paytypeid!='4' && (item.balance_pay_status==1 || item.balance_price==0)">
								<view class="btn1" :style="{background:t('color1')}" @tap.stop="orderCollect" :data-id="item.id" :data-index="index">
									确认收货
								</view>
								<view class="tips-container" :style="{background: 'rgba(' + (collect_reward_set.bgcolor || t('color1rgb')) + ', 0.7)',color: collect_reward_set.fontcolor || '#fff'}" v-if="collect_reward_set && collect_reward_set.prompt && item.is_collect_reward">
									<text class="tips-text">{{collect_reward_set.prompt}}</text>
									<view class="close-btn" @tap.stop="closeTips(index)">×</view>
									<view class="arrow-up" :style="{borderBottom:'12rpx solid rgba('+(collect_reward_set.bgcolor || t('color1rgb'))+', 0.7)'}"></view>
								</view>
							</view>
              <view v-if="!item.can_collect &&item.paytypeid!='4' && (item.balance_pay_status==1 || item.balance_price==0)" class="btn1" style="background:#bbb">
                运输中
              </view>
						</block>
						<block v-if="(item.status==1 || item.status==2 || item.status==8) && (item.freight_type==1 || item.freight_type==5) && item.hexiao_qr">
							<view class="btn2" @tap.stop="showhxqr" :data-hexiao_qr="item.hexiao_qr" :data-hexiao_num_remain="item.hexiao_num_remain">核销码</view>
						</block>
						<view v-if="item.refundCount && item.is_fenqi != 1" class="btn2" @tap.stop="goto" :data-url="'refundlist?orderid='+ item.id">售后详情</view>
            <block v-if="item.ishand==1">
              <block v-if="item.status==3">
                  <view class="btn2" @tap.stop="goto" :data-url="'/pagesA/handwork/hand?orderid=' + item.id" v-if="item.canhand &&  item.hand_num < item.procount">回寄</view>
              </block>
              <view v-if="item.handCount" class="btn2" @tap.stop="goto" :data-url="'/pagesA/handwork/handlist?orderid='+ item.id">查看回寄</view>
            </block>
						<block v-if="item.status==3 || item.status==4">
							<view class="btn2" @tap.stop="todel" :data-id="item.id">删除订单</view>
						</block>
						<block v-if="item.status==3">
							<view class="btn2" @tap.stop="anotherOrder(item)" :data-id="item.id">再来一单</view>
						</block>
            <block v-if="item.shop_order_exchange_product && (item.status==3 || item.status==2)">
              <view class="btn2" @tap.stop="goto" :data-url="'refundSelect?orderid=' + item.id + '&type=exchange'" :data-id="item.id" style="background-color: #1A1A1A;color: #fff">换货</view>
            </block>
						<view v-if="item.isNeedCard==1" class="btn2" @tap.stop="goto" :data-url="'/pagesB/order/uploadcard?orderid='+item.id">补充资料</view>
						<block v-if="item.bid>0 && item.status==3">
							<view v-if="item.iscommentdp==0" class="btn1" :style="{background:t('color1')}" @tap.stop="goto" :data-url="'/pagesExt/order/commentdp?orderid=' + item.id">评价店铺</view>
						</block>
					</view>
				</view>
			</block>
		</view>
		<nomore v-if="nomore"></nomore>
		<nodata v-if="nodata"></nodata>

		
		<uni-popup id="dialogHxqr" ref="dialogHxqr" type="dialog">
			<view class="hxqrbox">
				<image :src="hexiao_qr" @tap="previewImage" :data-url="hexiao_qr" class="img"/>
				<view class="txt">请出示核销码给核销员进行核销</view>
				<view class="txt" v-if="hexiao_num_remain>0">剩余核销次数：{{hexiao_num_remain}}</view>
				<view class="close" @tap="closeHxqr">
					<image :src="pre_url+'/static/img/close2.png'" style="width:100%;height:100%"/>
				</view>
			</view>
		</uni-popup>

		<uni-popup id="dialogSelectExpress" ref="dialogSelectExpress" type="dialog">
			<view style="background:#fff;padding:20rpx 30rpx;border-radius:10rpx;width:600rpx" v-if="express_content">
				<view class="sendexpress" v-for="(item, index) in express_content" :key="index" style="border-bottom: 1px solid #f5f5f5;padding:20rpx 0;">
					<view class="sendexpress-item" @tap="goto" :data-url="'/pagesExt/order/logistics?express_com=' + item.express_com + '&express_no=' + item.express_no+ '&ordertype=' + item.ordertype+ '&orderid=' + item.orderid" style="display: flex;">
						<view class="flex1" style="color:#121212">{{item.express_com}} - {{item.express_no}}</view>
						<image :src="pre_url+'/static/img/arrowright.png'" style="width:30rpx;height:30rpx"/>
					</view>
					<view v-if="item.express_oglist" style="margin-top:20rpx">
						<view class="oginfo-item" v-for="(item2, index2) in item.express_oglist" :key="index2" style="display: flex;align-items:center;margin-bottom:10rpx">
							<image :src="item2.pic" style="width:50rpx;height:50rpx;margin-right:10rpx;flex-shrink:0"/>
							<view class="flex1" style="color:#555">{{item2.name}}({{item2.ggname}})</view>
						</view>
					</view>
				</view>
			</view>
		</uni-popup>
		<view v-if="selecthxnumDialogShow" class="popup__container">
			<view class="popup__overlay" @tap.stop="hideSelecthxnumDialog"></view>
			<view class="popup__modal">
				<view class="popup__title">
					<text class="popup__title-text">请选择核销数量</text>
					<image :src="pre_url+'/static/img/close.png'" class="popup__close" style="width:36rpx;height:36rpx" @tap.stop="hideSelecthxnumDialog"/>
					<view class="txt" style="color: red;" v-if="hexiao_tip">{{hexiao_tip}}</view>
				</view>
				<view class="popup__content">
					<view class="pstime-item" v-for="(item, index) in hxnumlist" :key="index" @tap="hxnumRadioChange" :data-index="index">
						<view class="flex1">{{item}}</view>
						<view class="radio" :style="hxnum==item ? 'background:'+t('color1')+';border:0' : ''"><image class="radio-img" :src="pre_url+'/static/img/checkd.png'"/></view>
					</view>
				</view>
			</view>
		</view>
		<!-- 分期付款详情 -->
		<uni-popup id="fenqidetails" ref="fenqidetails" type="dialog">
			<view class="fenqi-popup-view">
				<view class="popup__title popup__title-text" style="padding: 15rpx;">分期详情</view>
				<view class="fenqi-details-list">
					<scroll-view scroll-y style="width: 100%;height: 700rpx;">
						<block v-for="(item,index) in fenqiDetailsLsit">
							<view class="options-fenqilist flex-col">
								<view class="fenqilist-view flex-bt">
									<view style="font-size: 28rpx;color: #333;font-weight: bold;">{{item.fenqi_num}}期</view>
									<view v-if='item.status == 0' style="color: #bbb;font-size: 28rpx;font-weight: bold;">未支付</view>
									<view v-if='item.status == 1' style="color: #ff8758;font-size: 28rpx;font-weight: bold;">已付款</view>
									<view v-if='item.status == 2' style="color: #bbb;font-size: 28rpx;font-weight: bold;">已过期</view>
								</view>
								<view class="fenqilist-view flex-bt" style="padding: 8rpx 0rpx;">
									<view class="lable-text">支付金额</view>
									<view class="content-text">￥{{item.fenqi_money}}</view>
								</view>
								<view class="fenqilist-view flex-bt">
									<view class="lable-text">赠送数量</view>
									<view class="content-text">{{item.fenqi_give_num}}张</view>
								</view>
								<view class="fenqilist-view flex-bt" v-if='item.end_paytime'>
									<view class="lable-text">到期时间</view>
									<view class="content-text">{{item.end_paytime}}</view>
								</view>
							</view>
						</block>
					</scroll-view>
				</view>
				<view class="fenqi-but-view" v-if="fenqibut_status">
					<view class="btn2" @click="fenquGopay(1)">支付当期</view>
					<view class="btn2" @click="fenquGopay(2)">全部支付</view>
				</view>
			</view>
		</uni-popup>
		<!-- 测评报告 -->
		<view class="popup__container" v-if="reportShow">
			<view class="popup__overlay" @tap.stop="changeReportDialog"></view>
			<view class="popup__modal">
				<view class="popup__title">
					<text class="popup__title-text">查看报告</text>
					<image :src="pre_url+'/static/img/close.png'" class="popup__close" style="width:36rpx;height:36rpx" @tap.stop="changeReportDialog"/>
				</view>
				<view class="popup__content">
					<view class="clist-item" @tap.stop="goto" :data-url="reportArr.bolePsyReport" v-if="reportArr.bolePsyReport">
						<view class="flex1">32种人才心理特质报告</view>
						<view class="radio"><image :src="pre_url+'/static/img/arrowright.png'" style="width:30rpx;height:30rpx"/></view>
					</view>
					<view class="clist-item" @tap.stop="goto" :data-url="reportArr.bolePostfitReport" v-if="reportArr.bolePostfitReport">
						<view class="flex1">42种职场岗位适配报</view>
						<view class=""><image :src="pre_url+'/static/img/arrowright.png'" style="width:30rpx;height:30rpx"/></view>
					</view>
				</view>
			</view>
		</view>
	</block>
	<uni-popup ref="popupReward" type='center'>
		<view class="popup-reward-content">
			<view class="popup-reward-price flex">
				<view class="price-num-text popup-color-text">
					<text>恭喜你获得</text>
					<text>{{collect_reward}}</text>
				</view>
				<view class="popup-reward-confirm" @click="popupRewardClose">确认</view>
			</view>
			<image :src="`${pre_url}/static/img/ordercollect/rewardbg.png`" mode="widthFix" />
		</view>
		<!-- 关闭弹窗按钮  -->
		<view class="popupMiandan-close" @click="popupRewardClose">
			<image :src="pre_url+'/static/img/close2.png'"></image>
		</view>
	</uni-popup>
	<loading v-if="loading"></loading>
	<dp-tabbar :opt="opt"></dp-tabbar>
	<popmsg ref="popmsg"></popmsg>
</view>
</template>

<script>
// import { object } from 'prop-types';
var app = getApp();

export default {
  data() {
    return {
			opt:{},
			loading:false,
      isload: false,
			menuindex:-1,
			pre_url: app.globalData.pre_url,

      st: 'all',
      datalist: [],
      pagenum: 1,
      nomore: false,
			nodata:false,
      codtxt: "",
			canrefund:1,
			express_content:'',
			selectExpressShow:false,
			hexiao_qr:'',
			keyword:'',
			showprice_dollar:false,
			hexiao_qr:'',
			selecthxnumDialogShow:false,
			hxogid:'',
			hxnum:'',
			hxnumlist:[],
			fenqiDetailsLsit:[],
			fenqiorderid:'',
			fenqibut_status:false,
			mendian_no_select:0,
			hexiao_num_remain:0,
			hexiao_tip:'',
			glass_order_custom:0,//眼镜订单定制界面
			reportShow:false,//测评报告
			reportArr:{},//报告列表
			collect_reward_set:[],
			collect_reward:'',
    };
  },

  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		if(this.opt && this.opt.st){
			this.st = this.opt.st;
		}
		this.getdata();
  },
	onPullDownRefresh: function () {
		this.getdata();
	},
  onReachBottom: function () {
    if (!this.nodata && !this.nomore) {
      this.pagenum = this.pagenum + 1;
      this.getdata(true);
    }
  },
	onNavigationBarSearchInputConfirmed:function(e){
		this.searchConfirm({detail:{value:e.text}});
	},
  methods: {
    getdata: function (loadmore) {
			if(!loadmore){
				this.pagenum = 1;
				this.datalist = [];
			}
      var that = this;
      var pagenum = that.pagenum;
      var st = that.st;
			that.nodata = false;
			that.nomore = false;
			that.loading = true;
      app.post('ApiOrder/orderlist', {st: st,pagenum: pagenum,keyword:that.keyword}, function (res) {
				that.loading = false;
        var data = res.datalist;
        if (pagenum == 1) {
					that.codtxt = res.codtxt;
					that.canrefund = res.canrefund;
					that.showprice_dollar = res.showprice_dollar
					that.datalist = data;
					that.mendian_no_select = res.mendian_no_select;
					that.collect_reward_set = res.collect_reward_set;
          if (data.length == 0) {
            that.nodata = true;
          }
					that.loaded();
        }else{
          if (data.length == 0) {
            that.nomore = true;
          } else {
            var datalist = that.datalist;
            var newdata = datalist.concat(data);
            that.datalist = newdata;
          }
        }
        if(res && res.glass_order_custom){
          that.glass_order_custom = res.glass_order_custom;
        }
      });
    },
    changetab: function (st) {
			if(st == 5){
				app.goto('refundlist');return;
			}
      this.st = st;
      uni.pageScrollTo({
        scrollTop: 0,
        duration: 0
      });
      this.getdata();
    },
		
			fenquGopay(type){
				let that = this;
				let selectfenqinum = type;
				that.loading = true;
				app.post('ApiOrder/saveFenqidata',{orderid:that.fenqiorderid,select_fenqi_type:selectfenqinum}, function(res){
					that.loading = false;
					if(res.status == 1){
						app.success(res.msg);
						app.goto('/pagesExt/pay/pay?id=' + res.payorderid,'redirectTo');
					}else{
						app.error(res.msg)
					}
				})
			},
			fenqiDetails(item){
				let that = this;
				that.fenqiorderid = item.id;
				that.loading = true;
				app.post('ApiOrder/getFenqidata',{orderid:item.id}, function(res){
					that.loading = false;
					if(res.status == 1){
						that.fenqiDetailsLsit = Object.values(res.data);
						that.fenqiDetailsLsit.forEach(item => {
							// item.check = false;
							if(item.status == 0){
								that.fenqibut_status = true;
							}
						})
						that.$nextTick( () => {
							that.$refs.fenqidetails.open();
						})
					}else{
						app.error(res.msg)
					}
				})
			},//查看评测状态和跳转链接
			toevaluate: function (e) {
				app.showLoading();
				var that = this;
				var orderid = e.currentTarget.dataset.id;
				app.post('ApiOrder/pingceOrder', {id: orderid}, function (data) {
		      app.showLoading(false);
					if(data.status == 1)
						app.goto(data.url);
					else
						app.error(data.msg);
				});
			},
			//查看评测报告
			viewReport: function (e) {
				this.reportShow = !this.reportShow;
				app.showLoading();
				var that = this;
				var orderid = e.currentTarget.dataset.id;
				app.post('ApiOrder/pingceOrder', {id: orderid}, function (data) {
		      app.showLoading(false);
					if(data.status == 2)
						that.reportArr = data.report_arr;
					else
						app.error(data.msg);
				});
			},
			
			changeReportDialog:function(){
				this.reportShow = !this.reportShow
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
    todel: function (e) {
      var that = this;
      var orderid = e.currentTarget.dataset.id;
      app.confirm('确定要删除该订单吗?', function () {
				app.showLoading('删除中');
        app.post('ApiOrder/delOrder', {orderid: orderid}, function (data) {
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
			var index = e.currentTarget.dataset.index;
			var orderinfo = that.datalist[index];
      app.confirm('确定要收货吗?', function () {
				app.showLoading('提交中');
				if(app.globalData.platform == 'wx' && orderinfo.wxpaylog && orderinfo.wxpaylog.is_upload_shipping_info == 1){
					// #ifdef MP-WEIXIN
					app.post('ApiOrder/orderCollectBefore', {orderid: orderid}, function (data) {
						app.showLoading(false);
						if(data.status != 1){app.error(data.msg);return;}
						else{
							if (wx.openBusinessView) {
							  wx.openBusinessView({
							    businessType: 'weappOrderConfirm',
							    extraData: {
							      merchant_id: orderinfo.wxpaylog.mch_id,
							      merchant_trade_no: orderinfo.wxpaylog.ordernum,
							      transaction_id: orderinfo.wxpaylog.transaction_id
							    },
							    success(res) {
							      //dosomething
										console.log('openBusinessView success')
										console.log(res)
										app.post('ApiOrder/orderCollect', {orderid: orderid}, function (data2) {
											app.showLoading(false);
											if(data2.status == 1 && data2.collect_reward){
												that.collect_reward = data2.collect_reward;
												that.$refs.popupReward.open();
											}else{
												app.success(data2.msg);
												setTimeout(function () {
													that.getdata();
												}, 1000);
											}
										});
							    },
							    fail(err) {
							      //dosomething
										console.log('openBusinessView fail')
										console.log(err)
							    },
							    complete() {
							      //dosomething
							    }
							  });
							} else {
							  //引导用户升级微信版本
								app.error('请升级微信版本');
								console.log('openBusinessView error')
							}
						}
					});
					// #endif
				}else{
					app.post('ApiOrder/orderCollect', {orderid: orderid}, function (data) {
						app.showLoading(false);
						if(data.status == 1 && data.collect_reward){
							that.collect_reward = data.collect_reward;
							that.$refs.popupReward.open();
						}else{
							app.success(data.msg);
							setTimeout(function () {
								that.getdata();
							}, 1000);
						}
					});
				}
      });
    },
    //转单上级审核
    transferOrder:function(e){
      var orderid = e.currentTarget.dataset.orderid;
      app.showLoading();
      app.post('ApiTransferOrderParentCheck/transferOrder', {id: orderid}, function (data) {
        app.showLoading(false);
        if(data.status == 0){
          app.error(data.msg);
        }else{
          app.alert(data.msg);
          setTimeout(function () {
            this.getdata();
          }, 1000);
        }
      });
    },
		logistics:function(e){
			var index = e.currentTarget.dataset.index;
			var orderinfo = this.datalist[index];
			var express_com = orderinfo.express_com
			var express_no = orderinfo.express_no
			var express_content = orderinfo.express_content
			var express_type = orderinfo.express_type
			var prolist = orderinfo.prolist
			console.log(express_content)
			if(!express_content){
				app.goto('/pagesExt/order/logistics?express_com=' + express_com + '&express_no=' + express_no+'&type='+express_type);
			}else{
				express_content = JSON.parse(express_content);
				for(var i in express_content){
					if(express_content[i].express_ogids){
						var express_ogids = (express_content[i].express_ogids).split(',');
						console.log(express_ogids);
						var express_oglist = [];
						for(var j in prolist){
							if(app.inArray(prolist[j].id+'',express_ogids)){
								express_oglist.push(prolist[j]);
							}
						}
						express_content[i].express_oglist = express_oglist;
					}
				}
				this.express_content = express_content;
				console.log(express_content);
				this.$refs.dialogSelectExpress.open();
			}
		},
		hideSelectExpressDialog:function(){
			this.$refs.dialogSelectExpress.close();
		},
		showhxqr:function(e){
			this.hexiao_qr = e.currentTarget.dataset.hexiao_qr
			this.hexiao_num_remain = e.currentTarget.dataset.hexiao_num_remain
			this.$refs.dialogHxqr.open();
		},
		closeHxqr:function(){
			this.$refs.dialogHxqr.close();
		},
		searchConfirm:function(e){
			this.keyword = e.detail.value;
      this.getdata(false);
		},
		showhxqr2:function(e){
      var that = this;
			var leftnum = e.currentTarget.dataset.num - e.currentTarget.dataset.hxnum;
			this.hxogid = e.currentTarget.dataset.id;
			if(leftnum <= 0){
				app.alert('没有剩余核销数量了');return;
			}
			var hxnumlist = [];
			for(var i=0;i<leftnum;i++){
				hxnumlist.push((i+1)+'');
			}
			var hexiao_tip = e.currentTarget.dataset.hexiao_tip;
			that.hexiao_tip = hexiao_tip;
      if (hxnumlist.length > 6) {
				that.hxnumlist = hxnumlist;
        that.selecthxnumDialogShow = true;
        that.hxnum = '';
      } else {
        uni.showActionSheet({
          itemList: hxnumlist,
          success: function (res) {
						if(res.tapIndex >= 0){
							that.hxnum = hxnumlist[res.tapIndex];
							that.gethxqr();
						}
          }
        });
      }
		},
		gethxqr(){
      var that = this;
			var hxnum = this.hxnum;
			var hxogid = this.hxogid;
			if(!hxogid){
				app.alert('请选择要核销的商品');return;
			}
			if(!hxnum){
				app.alert('请选择核销数量');return;
			}
			app.showLoading();
			app.post('ApiOrder/getproducthxqr', {hxogid: hxogid,hxnum:hxnum}, function (data) {
				app.showLoading(false);
				if(data.status == 0){
					app.alert(data.msg);
				}else{
					that.hexiao_qr = data.hexiao_qr
					that.$refs.dialogHxqr.open();
				}
			});
		},
    hxnumRadioChange: function (e) {
      var that = this;
      var index = e.currentTarget.dataset.index;
			this.hxnum = this.hxnumlist[index];
			setTimeout(function(){
				that.selecthxnumDialogShow = false;
				that.gethxqr();
			},200)
    },
		hideSelecthxnumDialog:function(){
			this.selecthxnumDialogShow = false;
		},
		anotherOrder(e){
			var prodata = [];
			for (var i = 0; i < e.prolist.length; i++) {
			  prodata.push(e.prolist[i].proid + ',' + e.prolist[i].ggid + ',' + e.prolist[i].num);
			}
			app.goto('/pages/shop/buy?prodata=' + prodata.join('-'));
		},
    gocodpay:function(e){
      var that = this;
      var id = e.currentTarget.dataset.id;
      app.post('ApiOrder/checkcodpay', {orderid: id}, function (res) {
      	app.showLoading(false);
      	if(res.status == 1){
      		app.goto('/pagesExt/pay/pay?id=' + res.payorderid);
      	}else{
          app.alert(res.msg);
      	}
      });
    },
		closeTips:function(index){
			this.$set(this.datalist[index], 'is_collect_reward', false);
		},
		popupRewardClose:function(){
			var that = this;
			that.$refs.popupReward.close();
			setTimeout(function () {
					that.getdata();
			}, 1000);
		}
  }
};
</script>
<style>
.container{ width:100%;}
.topsearch{width:94%;margin:10rpx 3%;}
.topsearch .f1{height:60rpx;border-radius:30rpx;border:0;background-color:#fff;flex:1}
.topsearch .f1 .img{width:24rpx;height:24rpx;margin-left:10px}
.topsearch .f1 input{height:100%;flex:1;padding:0 20rpx;font-size:28rpx;color:#333;}
.order-content{display:flex;flex-direction:column}
.order-box{ width: 94%;margin:10rpx 3%;padding:6rpx 3%; background: #fff;border-radius:8px}
.order-box .head{ display:flex;width:100%; border-bottom: 1px #f4f4f4 solid; height: 70rpx; line-height: 70rpx; overflow: hidden; color: #999;}
.order-box .head .f1{display:flex;align-items:center;color:#333}
.order-box .head image{width:34rpx;height:34rpx;margin-right:4px}
.order-box .head .st0{ width: 140rpx; color: #ff8758; text-align: right; }
.order-box .head .st1{ width: 140rpx; color: #ffc702; text-align: right; }
.order-box .head .st2{ width: 140rpx; color: #ff4246; text-align: right; }
.order-box .head .st3{ width: 140rpx; color: #999; text-align: right; }
.order-box .head .st4{ width: 140rpx; color: #bbb; text-align: right; }
.order-box .head .st8{ width: 140rpx; color: #ff55ff; text-align: right; }

.order-box .content{display:flex;width: 100%; padding:16rpx 0px;border-bottom: 1px #f4f4f4 dashed;position:relative;align-items: center;}
.order-box .content:last-child{ border-bottom: 0; }
.order-box .content image{ width: 140rpx; height: 140rpx;}
.order-box .content .detail{display:flex;flex-direction:column;margin-left:14rpx;flex:1}
.order-box .content .detail .t1{font-size:26rpx;line-height:36rpx;margin-bottom:10rpx;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:2;overflow:hidden;}
.order-box .content .detail .t2{height: 44rpx;line-height: 44rpx;color: #999;overflow: hidden;font-size: 24rpx;}
.order-box .content .detail .t3{display:flex;height:40rpx;line-height:40rpx;color: #ff4246;}
.order-box .content .detail .x1{ flex:1}
.order-box .content .detail .x2{ width:110rpx;font-size:32rpx;text-align:right;margin-right:8rpx}

.order-box .bottom{ width:100%; padding: 10rpx 0px; border-top: 1px #f4f4f4 solid; color: #555;}
.order-box .op{ display:flex;flex-wrap: wrap;justify-content:flex-end;align-items:center;width:100%; padding: 10rpx 0px; border-top: 1px #f4f4f4 solid; color: #555;}

.btn1{margin-left:20rpx; margin-top: 10rpx;max-width:160rpx;height:60rpx;line-height:60rpx;color:#fff;border-radius:3px;text-align:center;padding: 0 20rpx;}
.btn2{margin-left:20rpx; margin-top: 10rpx;max-width:160rpx;height:60rpx;line-height:60rpx;color:#333;background:#fff;border:1px solid #cdcdcd;border-radius:3px;text-align:center;padding: 0 20rpx;}

.hxqrbox{background:#fff;padding:50rpx;position:relative;border-radius:20rpx}
.hxqrbox .img{width:400rpx;height:400rpx}
.hxqrbox .txt{color:#666;margin-top:20rpx;font-size:26rpx;text-align:center}
.hxqrbox .close{width:50rpx;height:50rpx;position:absolute;bottom:-100rpx;left:50%;margin-left:-25rpx;border:1px solid rgba(255,255,255,0.5);border-radius:50%;padding:8rpx}
.tgr{font-size: 24rpx;}

.pstime-item{display:flex;border-bottom: 1px solid #f5f5f5;padding:20rpx 30rpx;}
.pstime-item .radio{flex-shrink:0;width: 32rpx;height: 32rpx;background: #FFFFFF;border: 2rpx solid #BFBFBF;border-radius: 50%;margin-right:30rpx}
.pstime-item .radio .radio-img{width:100%;height:100%}
.pdl10{padding-left: 10rpx;}

.fenqi-popup-view{width: 640rpx;height:880rpx;background: #fff;padding:20rpx 30rpx;border-radius:10rpx;}
.fenqi-details-list{width: 100%;height: 700rpx;}
.fenqi-details-list .options-fenqilist{width: 100%;border: 1px #f4f4f4 solid;padding: 15rpx 25rpx;margin: 10rpx 0rpx 10rpx;border-radius:10rpx;}
.fenqi-details-list .options-fenqilist .fenqilist-view{}
.options-fenqilist .fenqilist-view .lable-text{font-size: 24rpx;color: #555;}
.options-fenqilist .fenqilist-view .content-text{font-size: 24rpx;color:#333;font-weight: bold;}
.fenqi-but-view{width: 100%;display: flex;flex-direction: row;justify-content: space-around;align-items: center;}
.fenqi-but-view .tisp-text{font-size: 22rpx;color: #666;width: 100%;text-align: center;}
.binfo-name{font-weight: bold;}
.binfo-name text{margin-right: 10rpx;}
.member-content{padding-top:10rpx;font-size: 27rpx;color:#999;}
.member-content view{margin-bottom: 10rpx;}
.clist-item{display:flex;border-bottom: 1px solid #f5f5f5;padding:20rpx 30rpx;}
/* 确认收获奖励tips */
.btn-wrapper{position:relative;display:inline-block}
.tips-container{position:absolute;top:100%;right:0;margin-top:20rpx;display:inline-block;background-color:#FF9900;color:white;padding:20rpx 40rpx 20rpx 20rpx;border-radius:10rpx;box-shadow:0 2px 6px rgba(0,0,0,0.15);max-width:500rpx;min-width:120rpx;width:max-content;width:-moz-max-content;width:-webkit-max-content;box-sizing:border-box;z-index:1}
.tips-text{display:block;white-space:normal;word-wrap:break-word;word-break:break-word;line-height:1.5;font-size:26rpx;overflow-wrap:break-word;padding-right:20rpx}
.close-btn{position:absolute;top:50%;right:10rpx;transform:translateY(-50%);font-weight:bold;cursor:pointer;font-size:32rpx;line-height:1;width:30rpx;height:30rpx;display:flex;align-items:center;justify-content:center}
.arrow-up{position:absolute;top:-12rpx;right:40rpx;width:0;height:0;border-left:12rpx solid transparent;border-right:12rpx solid transparent;border-bottom:12rpx solid #FF9900}
/* 确认收货奖励弹窗 */
.popup-reward-content{position: relative;display: flex;justify-content: center;}
.popupMiandan-close{border: 2px #fff solid;width: 60rpx;height: 60rpx;border-radius: 50%;display: flex;align-items: center;justify-content: center;position: absolute;top: 0;right: 0;}
.popupMiandan-close image{width: 80%;height: 80%;}
.popup-reward-content .popup-reward-price{width: 135px;position: absolute;top:30%;align-items: center;justify-content: center;z-index: 2;flex-direction: column;}
.popup-reward-content .popup-reward-price .price-num-text{width: 135px;height: 80px;font-size: 32rpx;text-align: center;color: #850b01;padding:20rpx 0;}
.popup-reward-content .popup-reward-price .price-num-text text{display: block;width: 100%;padding: 5rpx;}
.popup-reward-confirm{color: #FFFFFF;padding: 10rpx 60rpx;border: 1px solid #fff;border-radius: 10rpx;margin-top: 30rpx;background: rgba(255,255,255, 0.2);}
</style>