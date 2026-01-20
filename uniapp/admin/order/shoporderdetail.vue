<template>
<view class="container">
	<block v-if="isload">
		<view class="ordertop" :style="'background:url('+(shopset.order_detail_toppic?shopset.order_detail_toppic: pre_url + '/static/img/ordertop.png')+');background-size:100%'">
			<view class="f1" v-if="detail.status==0">
				<view class="t1">等待买家付款</view>
				<view class="t2" v-if="djs">剩余时间：{{djs}}</view>
			</view>
			<view class="f1" v-if="detail.status==1">
				<view class="t1">{{detail.paytypeid==4 ? '已选择'+detail.paytype : '已成功付款'}}</view>
				<view class="t2" v-if="detail.freight_type!=1">请尽快发货</view>
				<view class="t2" v-if="detail.freight_type==1">待提货</view>
			</view>
			<view class="f1" v-if="detail.status==2">
				<view class="t1">订单已发货</view>
				<view class="t2" v-if="detail.freight_type!=3">发货信息：{{detail.express_com}} {{detail.express_no}}</view>
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
				<view class="t1"><text user-select="true" selectable="true">{{detail.linkman}}</text> <text v-if="detail.tel" @tap="goto" :data-url="'tel:'+detail.tel" style="margin-left: 20rpx;" user-select="true" selectable="true">{{detail.tel}}</text></view>
				<text class="t2" v-if="detail.freight_type!=1 && detail.freight_type!=3" user-select="true" selectable="true">地址：{{detail.area}}{{detail.address}}</text>
        <text class="t2" v-if="detail.product_thali">学生姓名：{{detail.product_thali_student_name}}</text>
        <text class="t2" v-if="detail.product_thali">学校信息：{{detail.product_thali_school}}</text>
				<text class="t2" v-if="detail.freight_type==1" @tap="openLocation" :data-address="storeinfo.address" :data-latitude="storeinfo.latitude" :data-longitude="storeinfo.longitude" user-select="true" selectable="true">取货地点：{{storeinfo.name}} - {{storeinfo.address}}</text>
        <view class="t2" v-if="detail.worknum">工号：{{detail.worknum}}</view>
      </view>
		</view>
    
    <view class="orderinfo" v-if="detail.usegiveorder && detail.usegiveorder == 1">
      <view class="title">
      	赠好友
      </view>
      <view class="item">
      	<view class="t1">领取状态</view>
      	<view v-if="detail.giveordermid>0" class="t2" style="color: green;">已领取</view>
        <view v-else class="t2" style="color: red;">未领取</view>
      </view>
      <block v-if="detail.giveordermid>0 && detail.givemember">
        <view class="item">
          <text class="t1">好友信息</text>
          <text class="flex1"></text>
          <image :src="detail.givemember.headimg" style="width:80rpx;height:80rpx;margin-right:8rpx"/>
          <text style="height:80rpx;line-height:80rpx">{{detail.givemember.nickname}}(ID:{{detail.giveordermid}})</text>
        </view>
      </block>
    </view>

		<view class="product">
			<view v-for="(item, idx) in prolist" :key="idx" class="box">
				<view class="content">
					<view @tap="goto" :data-url="'/pages/shop/product?id=' + item.proid">
						<image :src="item.pic"></image>
					</view>
					<view class="detail">
						<text class="t1">{{item.name}}</text>
						<text class="t2">{{item.ggname}}</text>
						<view class="t3" v-if="item.product_type && item.product_type==2">
							<text class="x1 flex1">{{item.real_sell_price}}元/斤</text>
							<text class="x2">×{{item.real_total_weight}}斤</text>
							</view>
						<view class="t3" v-else>
							<text class="x1 flex1">
								￥{{item.sell_price}}
								<text v-if="!isNull(item.freezemoney_price) && item.freezemoney_price > 0">+{{item.freezemoney_price}}{{t('冻结资金')}}</text>
							</text>
							<text class="x2">×{{item.num}}</text>
						</view>
						<text class="t2" v-if="shopset.show_shd_remark == 1 && item.shd_remark" style="height: auto;">备注：{{item.shd_remark}}</text>
						<block v-if="(detail.status==1 || detail.status==2) && detail.is_quanyi==1 && item.hexiao_code">
							<view class="btn3" @tap.stop="showhxqr2" :data-id="item.id" :data-num="item.hexiao_num_total" :data-hxnum="item.hexiao_num_used" :data-hexiao_code="item.hexiao_code" style="position:absolute;top:46rpx;right:0rpx;">权益核销</view>
						</block>
					</view>
				</view>
				<!-- glassinfo -->
				<view class="glassitem" v-if="item.glassrecord">
					<view class="gcontent">
						<view class="glassheader">
							{{item.glassrecord.name}}
							{{item.glassrecord.nickname?item.glassrecord.nickname:''}}
							{{item.glassrecord.check_time?item.glassrecord.check_time:''}}
							{{item.glassrecord.typetxt}}
							
							<text class="pdl10" v-if="item.glassrecord.double_ipd==0">{{item.glassrecord.ipd?'PD'+item.glassrecord.ipd:''}}</text>
							<text class="pdl10" v-else>PD R{{item.glassrecord.ipd_right}} L{{item.glassrecord.ipd_left}}</text>
						</view>
						<view class="glassrow bt">
							<view class="grow">
							R {{item.glassrecord.degress_right}}/{{item.glassrecord.ats_right?item.glassrecord.ats_right:'0.00'}}*{{item.glassrecord.ats_zright?item.glassrecord.ats_zright:'0'}} <text class="pdl10" v-if="item.glassrecord.type==3">ADD+{{item.glassrecord.add_right?item.glassrecord.add_right:0}}</text>
							</view>
							<view class="grow">
							L {{item.glassrecord.degress_left}}/{{item.glassrecord.ats_left?item.glassrecord.ats_left:'0.00'}}*{{item.glassrecord.ats_zleft?item.glassrecord.ats_zleft:'0'}} <text class="pdl10" v-if="item.glassrecord.type==3">ADD+{{item.glassrecord.add_left?item.glassrecord.add_left:0}}</text>
							</view>
						</view>
						<view class="glassrow" v-if="item.glassrecord.remark">{{item.glassrecord.remark}}</view>
					</view>
					
				</view>
				<!-- glassinfo -->
			</view>
      <view v-if="detail.crk_givenum && detail.crk_givenum>0" style="color:#f60;line-height:70rpx">+随机赠送{{detail.crk_givenum}}件</view>
		</view>
		
		<view class="orderinfo" v-if="(detail.status==3 || detail.status==2) && (detail.freight_type==3 || detail.freight_type==4)">
			<view class="item flex-col">
				<text class="t1" style="color:#111">发货信息</text>
				<text class="t2" style="text-align:left;margin-top:10rpx;padding:0 10rpx" user-select="true" selectable="true">{{detail.freight_content}}</text>
			</view>
		</view>
		
		<view class="orderinfo" v-if="detail.mid>0">
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
				<text class="t2" user-select="true" selectable="true">{{detail.remark}}</text>
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
      <view class="item" v-if="detail.issource && detail.source && detail.source == 'supply_zhenxin'">
      	<text class="t1">商品来源</text>
      	<text class="t2">甄新汇选</text>
      </view>
			<view class="item">
				<text class="t1">商品金额</text>
				<text class="t2 red">¥{{detail.product_price}}</text>
			</view>
			<view class="item" v-if="!isNull(detail.total_freezemoney_price) && detail.total_freezemoney_price > 0">
				<text class="t1">{{t('冻结资金')}}</text>
				<text class="t2 red">{{detail.total_freezemoney_price}}</text>
			</view>
			<view class="item" v-if="detail.leveldk_money > 0">
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
			<view class="item" v-if="detail.up_floor_fee && detail.up_floor_fee > 0">
				<text class="t1">上楼费</text>
				<text class="t2 red">+¥{{detail.up_floor_fee}}</text>
			</view>
			
			<view class="item" v-if="detail.freight_time">
				<text class="t1">{{detail.freight_type!=1?'配送':'提货'}}时间</text>
				<text class="t2">{{detail.freight_time}}</text>
			</view>
      <view class="item" v-if="detail.wxtc_status_name">
        <text class="t1">配送状态</text>
        <text class="t2">{{detail.wxtc_status_name}}</text>
      </view>
			<view class="item" v-if="detail.coupon_money > 0">
				<text class="t1">{{t('优惠券')}}抵扣</text>
				<text class="t2 red">-¥{{detail.coupon_money}}</text>
			</view>
			<view class="item" v-if="detail.water_coupon_money && detail.water_coupon_money > 0">
				<text class="t1">{{t('电子水票')}}抵扣</text>
				<text class="t2 red">-¥{{detail.water_coupon_money}}</text>
			</view>
			<view class="item" v-if="detail.scoredk_money > 0">
				<text class="t1">{{t('积分')}}抵扣</text>
				<text class="t2 red">-¥{{detail.scoredk_money}}</text>
			</view>
			<view class="item" v-if="detail.dec_money > 0">
				<text class="t1">{{t('余额')}}抵扣</text>
				<text class="t2 red">-¥{{detail.dec_money}}</text>
			</view>
      <view class="item" v-if="detail.silvermoneydec && detail.silvermoneydec > 0">
      	<text class="t1">{{t('银值')}}抵扣</text>
      	<text class="t2 red">-¥{{detail.silvermoneydec}}</text>
      </view>
      <view class="item" v-if="detail.goldmoneydec && detail.goldmoneydec > 0">
      	<text class="t1">{{t('金值')}}抵扣</text>
      	<text class="t2 red">-¥{{detail.goldmoneydec}}</text>
      </view>
			<view class="item" v-if="detail.discount_rand_money > 0">
				<text class="t1">随机立减</text>
				<text class="t2 red">-¥{{detail.discount_rand_money}}</text>
			</view>
      <view class="item" v-if="detail.dedamount_dkmoney && detail.dedamount_dkmoney > 0">
      	<text class="t1">抵扣金抵扣</text>
      	<text class="t2 red">-¥{{detail.dedamount_dkmoney}}</text>
      </view>
      <view class="item" v-if="detail.shopscoredk_money > 0">
      	<text class="t1">{{t('产品积分')}}抵扣</text>
      	<text class="t2 red">-¥{{detail.shopscoredk_money}}</text>
      </view>
			<view class="item">
				<text class="t1">实付款</text>
				<text class="t2 red">¥{{detail.totalprice}}</text>
				<text v-if="!isNull(detail.total_freezemoney_price) && detail.total_freezemoney_price > 0"> + {{detail.total_freezemoney_price}}{{t('冻结资金')}}</text>
			</view>
      <view class="item" v-if="detail.combine_money && detail.combine_money > 0">
        <text class="t1">{{t('余额')}}已付</text>
        <text class="t2 red">-¥{{detail.combine_money}}</text>
      </view>
      <view class="item" v-if="detail.paytypeid == 2 && detail.status<=3 && detail.combine_wxpay && detail.combine_wxpay > 0">
        <text class="t1">微信已付</text>
        <text class="t2 red">-¥{{detail.combine_wxpay}}</text>
      </view>
      <view class="item" v-if="(detail.paytypeid == 3 || (detail.paytypeid>=302 && detail.paytypeid<=330)) && detail.combine_alipay && detail.combine_alipay > 0">
        <text class="t1">支付宝已付</text>
        <text class="t2 red">-¥{{detail.combine_alipay}}</text>
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
				<text class="t2 red">{{detail.refund_reason||'暂无'}}</text>
			</view>
			<view class="item" v-if="detail.refund_checkremark">
				<text class="t1">审核备注</text>
				<text class="t2 red">{{detail.refund_checkremark}}</text>
			</view>
			<view class="item flex-col" v-if="(detail.status==1 || detail.status==2) && detail.freight_type==1 && detail.is_quanyi==0 && !detail.hidefahuo">
				<text class="t1">核销码</text>
				<view class="flex-x-center">
					<image :src="detail.hexiao_qr" style="width:400rpx;height:400rpx" @tap="previewImage" :data-url="detail.hexiao_qr"></image>
				</view>
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
      <view class="item" v-if="detail.poshopid && detail.poshopid>0" style="align-items: center;">
        <text class="t1">所购店铺</text>
        <text class="flex1"></text>
        <image :src="detail.poshop.pic" style="width:80rpx;height:80rpx;margin-right:8rpx"/>
        <text  style="height:80rpx;line-height:80rpx">{{detail.poshop.name}}(ID:{{detail.poshopid}})</text>
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
		<view class="orderinfo" v-if="detail.isdygroupbuy==1">
			<view class="item">
				<text class="t1">抖音团购券信息</text>
		    <text class="t2">{{detail.dyorderids}}</text>
			</view>
		</view>
		<view class="orderinfo" v-if="(detail.formdata).length > 0">
			<view class="item" v-for="item in detail.formdata" :key="index">
				<text class="t1">{{item[0]}}</text>
				<view class="t2" v-if="item[2]=='upload'"><image :src="item[1]" style="width:400rpx;height:auto" mode="widthFix" @tap="previewImage" :data-url="item[1]"/></view>
				<text class="t2" v-else user-select="true" selectable="true">{{item[1]}}</text>
			</view>
		</view>

		<view style="width:100%;height:calc(160rpx + env(safe-area-inset-bottom));"></view>

		<view class="bottom notabbarbot" v-if="isNull(admin_user.is_fuwu) || admin_user.is_fuwu==0">
			<view v-if="detail.refund_status==1" class="btn2" @tap="refundnopass" :data-id="detail.id">退款驳回</view>
			<view v-if="detail.refund_status==1" class="btn2" @tap="refundpass" :data-id="detail.id">退款通过</view>
			<view v-if="detail.status==0" class="btn2" @tap="closeOrder" :data-id="detail.id">关闭订单</view>
			<view v-if="detail.status==0 && detail.bid==0 && !detail.isdygroupbuy && detail.order_admin_payorder_switch==1" class="btn2" @tap="ispay" :data-id="detail.id">改为已支付</view>
			<block v-if="detail.status==1 && !detail.hidefahuo && shopset.send_show">
				<view v-if="detail.product_type==2" class="btn2" @tap="goto" :data-url="'weightOrderFahuo?id='+detail.id">发货</view>
				<view v-if="detail.product_type!=2 && detail.can_fahuo==1" class="btn2" @tap="fahuo" :data-id="detail.id">发货</view>
			</block>
			
			<view v-if="(detail.status==1 || detail.status==2) && detail.freight_type==1 && detail.is_quanyi==0 && shopset.hexiao_show" class="btn2" @tap="hexiao" :data-id="detail.id">核销</view>
      <block v-if="detail.wx_express_intracity && detail.freight_type == 2">
        <view class="btn2" @tap="peisongWxtc" style="width: 190rpx" :data-id="detail.id">微信同城配送</view>
      </block>

      <block v-if="detail.status==1 && detail.canpeisong && shopset.send_show">
        <view class="btn2" v-if="detail.express_wx_status" @tap="peisongWx"  :data-id="detail.id">即时配送</view>
        <view class="btn2" v-else-if="detail.myt_status"   @tap="peisongMyt" :data-id="detail.id">麦芽田配送</view>
        <view class="btn2" v-else @tap="peisong" :data-id="detail.id">配送</view>
      </block>

			<block v-if="(detail.status==2 || detail.status==3) && detail.express_com">
				<view v-if="detail.express_type =='express_wx'" class="btn2" @tap="goto" :data-url="'/pagesExt/order/logistics?express_com='+detail.express_com+'&express_no='+detail.express_no+'&type=express_wx'">订单跟踪</view>
				<view v-else class="btn2" >
						<text v-if="detail.psid == -2" @tap="goto" :data-url="'/pagesExt/order/logistics?express_com='+detail.express_com+'&express_no='+detail.express_no">订单跟踪</text>
						<text v-else @tap.stop="logistics" :data-express_type="detail.express_type" :data-express_com="detail.express_com" :data-express_no="detail.express_no" :data-express_content="detail.express_content">查物流</text>
				</view>
			</block>
			<view v-if="detail.status==1 && detail.can_refund==1" class="btn2" @tap="refundinit" :data-id="detail.id">退款</view>
			<view v-if="detail.status==0 && shopset.changeprice_status==1" class="btn2"  @tap="changePrice" :data-id="detail.id">改价</view>
			<view v-if="detail.status==2 && detail.freight_type==10" class="btn2" @tap="fahuo" :data-id="detail.id">修改物流</view>
			<view v-if="detail.del_auth==1" class="btn2" @tap="delOrder" :data-id="detail.id">删除</view>
			<view class="btn2" @tap="setremark" :data-id="detail.id">设置备注</view>
<!--      <view :data-id="detail.id" v-if="detail.shoporder_update && detail.status == 0" @tap.stop="goto" :data-url="'/adminExt/order/updateOrder?id=' + detail.id" data-type="1" class="btn2">订单修改</view>-->
<!--      <view :data-id="detail.id" v-if="detail.shoporder_shdimg_mobile" @tap.stop="showShdimg" data-type="1" class="btn2">送货单</view>-->
		</view>
		<uni-popup id="dialogSetremark" ref="dialogSetremark" type="dialog">
			<uni-popup-dialog mode="input" title="设置备注" :value="detail.remark" placeholder="请输入备注" @confirm="setremarkconfirm"></uni-popup-dialog>
		</uni-popup>
		
		<uni-popup id="dialogChangePrice" ref="dialogChangePrice" type="dialog">
			<uni-popup-dialog mode="input" title="修改价格" :value="detail.totalprice" placeholder="请输入价格" @confirm="changePriceConfirm"></uni-popup-dialog>
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
		
		<uni-popup id="dialogExpress10" ref="dialogExpress10" type="dialog">
			<view class="uni-popup-dialog">
				<view class="uni-dialog-title">
					<text class="uni-dialog-title-text">发货信息</text>
				</view>
				<view class="uni-dialog-content">
					<view>
						<view class="form-item flex" style="border-bottom:0;">
							<view class="f1" style="margin-right:20rpx">物流单照片</view>
							<view class="f2">
								<view class="layui-imgbox" v-if="express_pic">
									<view class="layui-imgbox-close" @tap="removeimg" :data-index="0" data-field="express_pic"><image style="display:block" :src="pre_url+'/static/img/ico-del.png'"></image></view>
									<view class="layui-imgbox-img"><image :src="express_pic" @tap="previewImage" :data-url="express_pic" mode="widthFix"></image></view>
								</view>
								<view class="uploadbtn" :style="'background:url('+pre_url+'/static/img/shaitu_icon.png) no-repeat 60rpx;background-size:80rpx 80rpx;background-color:#F3F3F3;'" @tap="uploadimg" data-field="express_pic" data-pernum="1" v-else></view>
							</view>
							<input type="text" hidden="true" name="express_pic" :value="express_pic" maxlength="-1"/>
						</view>
						<view class="flex-y-center flex-x-center" style="margin:20rpx 20rpx;">
							<view style="font-size:28rpx;color:#555">发货人：</view>
							<input type="text" placeholder="请输入发货人信息" @input="setexpress_fhname" style="border: 1px #eee solid;padding: 10rpx;height:70rpx;border-radius:4px;flex:1;font-size:28rpx;"/>
						</view>
						<view class="flex-y-center flex-x-center" style="margin:20rpx 20rpx;">
							<view style="font-size:28rpx;color:#555">发货地址：</view>
							<input type="text" placeholder="请输入发货地址" @input="setexpress_fhaddress" style="border: 1px #eee solid;padding: 10rpx;height:70rpx;border-radius:4px;flex:1;font-size:28rpx;"/>
						</view>
						<view class="flex-y-center flex-x-center" style="margin:20rpx 20rpx;">
							<view style="font-size:28rpx;color:#555">收货人：</view>
							<input type="text" placeholder="请输入发货人信息" @input="setexpress_shname" style="border: 1px #eee solid;padding: 10rpx;height:70rpx;border-radius:4px;flex:1;font-size:28rpx;"/>
						</view>
						<view class="flex-y-center flex-x-center" style="margin:20rpx 20rpx;">
							<view style="font-size:28rpx;color:#555">收货地址：</view>
							<input type="text" placeholder="请输入发货地址" @input="setexpress_shaddress" style="border: 1px #eee solid;padding: 10rpx;height:70rpx;border-radius:4px;flex:1;font-size:28rpx;"/>
						</view>
						<view class="flex-y-center flex-x-center" style="margin:20rpx 20rpx;">
							<view style="font-size:28rpx;color:#555">备注：</view>
							<input type="text" placeholder="请输入备注" @input="setexpress_remark" style="border: 1px #eee solid;padding: 10rpx;height:70rpx;border-radius:4px;flex:1;font-size:28rpx;"/>
						</view>
					</view>
				</view>
				<view class="uni-dialog-button-group">
					<view class="uni-dialog-button" @click="dialogExpress10Close">
						<text class="uni-dialog-button-text">取消</text>
					</view>
					<view class="uni-dialog-button uni-border-left" @click="confirmfahuo10">
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
        		<view class="uni-dialog-content">
        			<view>
								<view v-if="detail.myt_shop" class="flex-y-center flex-x-center" style="margin:20rpx 20rpx;">
									<view style="font-size:28rpx;color:#555">门店：</view>
										<picker @change="mytshopChange" :value="mytindex" :range="detail.myt_shoplist"  range-key='name' style="font-size:28rpx;border: 1px #eee solid;padding:10rpx;height:70rpx;border-radius:4px;flex:1;line-height: 52rpx;">
											<view class="picker">{{detail.myt_shoplist[mytindex]['name']}}</view>
										</picker>
								</view>
        				<view class="flex-y-center flex-x-center" style="margin:20rpx 20rpx;">
        					<view style="font-size:28rpx;color:#555">重量：</view>
        					<input type="text" placeholder="请输入重量(选填)" @input="mytWeight" style="border: 1px #eee solid;padding: 10rpx;height:70rpx;border-radius:4px;flex:1;font-size:28rpx;"/>
        				</view>
        				<view class="flex-y-center flex-x-center" style="margin:20rpx 20rpx;">
        					<view style="font-size:28rpx;color:#555">备注：</view>
        					<input type="text" placeholder="请输入备注(选填)" @input="mytRemark" style="border: 1px #eee solid;padding: 10rpx;height:70rpx;border-radius:4px;flex:1;font-size:28rpx;"/>
        				</view>
        			</view>
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
				<uni-popup id="dialogExpress12" ref="dialogExpress12" type="dialog" :mask-click="false">
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
														<input type="number" :value="item.canRefundNum" :data-max="item.canRefundNum" :data-ogid="item.id" @input="retundInput" class="retundNum" style="border: 1px #eee solid;width: 80rpx;margin-left: 10rpx;text-align: center;">
													</view>
												</view>
											</view>
										</view>
									</view>
									</scroll-view>
								</view>
								
								<view class="flex-y-center flex-x-center" style="margin:20rpx 20rpx;height: 80rpx;">
									<view style="font-size:28rpx;color:#555">退款原因：</view>
									<input type="text" placeholder="请输入退款原因" @input="refundMoneyReason" adjust-position="false" style="border: 1px #eee solid;padding: 10rpx;height:70rpx;border-radius:4px;flex:1;font-size:28rpx;"/>
								</view>
								<view class="flex-y-center flex-x-center" style="margin:20rpx 20rpx;height: 80rpx">
									<view style="font-size:28rpx;color:#555">退款金额：</view>
									<input type="text" placeholder="请输入退款金额" @input="refundMoney" adjust-position="false" :value="refundTotalprice" style="border: 1px #eee solid;padding: 10rpx;height:70rpx;border-radius:4px;flex:1;font-size:28rpx;"/>
								</view>
							</view>
						</view>
						<view class="uni-dialog-button-group">
							<view class="uni-dialog-button" @click="dialogExpress12Close">
								<text class="uni-dialog-button-text">取消</text>
							</view>
							<view class="uni-dialog-button uni-border-left" @click="gotoRefundMoney()">
								<text class="uni-dialog-button-text uni-button-color">确定</text>
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
						</view>
						<view class="popup__content">
							<view class="pstime-item" v-for="(item, index) in hxnumlist" :key="index" @tap="hxnumRadioChange" :data-index="index">
								<view class="flex1">{{item}}</view>
								<view class="radio" :style="hxnum==item ? 'background:'+t('color1')+';border:0' : ''"><image class="radio-img" :src="pre_url+'/static/img/checkd.png'"/></view>
							</view>
						</view>
					</view>
				</view>
	</block>
  <uni-popup id="dialogShdimg" ref="dialogShdimg" type="dialog">
    <view class="hxqrbox">
      <image :src="shdimg" @tap="previewImage" :data-url="shdimg" class="img"/>
      <view class="txt">点击查看图片后，长按保存</view>
      <view class="close" @tap="closeShdimg">
        <image :src="pre_url+'/static/img/close2.png'" style="width:100%;height:100%"/>
      </view>
    </view>
  </uni-popup>
  <uni-popup id="dialogWxtcExpress" ref="dialogWxtcExpress" type="dialog">
    <view class="uni-popup-dialog">
      <view class="uni-dialog-title">
        <text class="uni-dialog-title-text">微信同城配送设置</text>
      </view>
      <view class="uni-dialog-content">
        <view>
          <view class="flex-y-center flex-x-center" style="margin:20rpx 20rpx;" v-if="est_fee">
            <view style="font-size:28rpx;color:#555">配送费约：</view>
            <input type="text" disabled="" style="border: 0px #eee solid;padding: 0;height:70rpx;border-radius:4px;flex:1;font-size:28rpx;" :value="est_fee"/>
          </view>
          <view class="flex-y-center flex-x-center" style="margin:20rpx 20rpx;">
            <view style="font-size:28rpx;color:#555">重量(kg)：</view>
            <input type="text" placeholder="请输入重量(kg)" @input="wxtcWeight" style="border: 1px #eee solid;padding: 0 10rpx;height:70rpx;border-radius:4px;flex:1;font-size:28rpx;" :value="wxtc_weight"/>
          </view>
          <view class="flex-y-center flex-x-center" style="margin:20rpx 20rpx;">
            <view style="font-size:28rpx;color:#555">物品类型：</view>
            <view class="f2" style="width: 160px">
              <radio-group class="radio-group" @change="cargoTypeChange" >
                <label v-for="(item1,idx) in detail.cargo_type_arr" :key="idx" class="flex-y-center" style="margin: 5rpx 20rpx 0 0;display:inline !important;">
                  <radio style="margin-top: 5px" class="radio" :value="idx" />{{item1}}
                </label>
              </radio-group>
            </view>
          </view>
        </view>
      </view>
      <view class="uni-dialog-button-group">
        <view class="uni-dialog-button" @click="dialogWxExpressClose">
          <text class="uni-dialog-button-text">取消</text>
        </view>
        <view class="uni-dialog-button uni-border-left" @click="wxtcConfirmfahuo">
          <text class="uni-dialog-button-text uni-button-color">确定</text>
        </view>
      </view>
    </view>
  </uni-popup>
  <uni-popup id="dialogSelectExpress" ref="dialogSelectExpress" type="dialog">
    <view style="background:#fff;padding:20rpx 30rpx;border-radius:10rpx;width:600rpx" v-if="express_content">
      <view class="sendexpress" v-for="(item, index) in express_content" :key="index" @tap="goto" :data-url="'/pagesExt/order/logistics?express_com=' + item.express_com + '&express_no=' + item.express_no+ '&ordertype=' + item.ordertype+ '&orderid=' + item.orderid" style="border-bottom: 1px solid #f5f5f5;padding:20rpx 0;">
        <view class="sendexpress-item" style="display: flex;">
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
			expressdata:[],
			express_index:0,
			express_no:'',
			prodata: '',
			djs: '',
			detail: "",
			prolist: "",
			shopset: {},
			storeinfo: "",
			lefttime: "",
			codtxt: "",
			peisonguser:[],
			peisonguser2:[],
			index2:0,
			express_pic:'',
			express_fhname:'',
			express_fhaddress:'',
			express_shname:'',
			express_shaddress:'',
			express_remark:'',
			
			returnProlist:[], //退款商品
			refundTotalprice:0, //退款金额
			refundNum:[],
			refundReason:'', //退款原因
			myt_weight:'',
			myt_remark:'',
			mytindex:0,
			myt_shop_id:0,
			selecthxnumDialogShow:false,
			hxogid:'',
			hxnum:'',
			hxnumlist:[],
			hexiao_code:'',
			admin_user:{},
      shdimg:'',//送货单图片
      wxtc_weight:'1',//微信同城货品重量
      wxtc_cargo_type:'',//微信同城货品类型
      cargoTypeindex:0,
      est_fee:false,
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
		saoyisao: function (d) {
		  var that = this;
			var express_com = this.expressdata[this.express_index]
			if(app.globalData.platform == 'h5'){
				app.alert('请使用微信打开系统，才可正常使用扫码功能');return;
			}else if(app.globalData.platform == 'mp'){
				// #ifdef H5
				var jweixin = require('jweixin-module');
				jweixin.ready(function () {   //需在用户可能点击分享按钮前就先调用
					jweixin.scanQRCode({
						needResult: 1, // 默认为0，扫描结果由微信处理，1则直接返回扫描结果，
						scanType: ["qrCode","barCode"], // 可以指定扫二维码还是一维码，默认二者都有
						success: function (res) {
							// console.log(res);
							const expressNo = that.extractExpressCode(res.resultStr);
							// console.log(expressNo);
							if (expressNo) {
								that.express_no = expressNo;
							} else {
								app.error('未识别到有效快递单号');
							}
						},
						fail:function(err){
							app.error(err.errMsg);
						}
					});
				});
				// #endif
			}else{
				uni.scanCode({
					success: function (res) {
						// console.log(res);
						const expressNo = that.extractExpressCode(res.result);
						// console.log(expressNo);
						if (expressNo) {
							that.express_no = expressNo;
						} else {
							app.error('未识别到有效快递单号');
						}
					},
					fail:function(err){
						app.error(err.errMsg);
					}
				});
			}
		},
		/**
		 * 提取快递单号（重点适配顺丰）
		 * @param {string} scanResult 扫码原始结果
		 */
		extractExpressCode(scanResult) {
			// 1. 优先处理顺丰快递（一维码、二维码可能是JSON格式）
			const sfReg = /SF\d{12,20}/; // 精准匹配 SF+12位数字格式
			const sfMatch = scanResult.match(sfReg);
			if (sfMatch) {
				let sfNo = this.extractSFCode(scanResult);
				if (sfNo) return sfNo;
			}

			// 2. 其他快递单号规则（10-20位字母/数字，排除纯数字短码）
			const otherReg = /(?![0-9]{1,9}$)[A-Z0-9]{10,20}/g;
			const otherMatches = scanResult.match(otherReg);
			if (otherMatches && otherMatches.length > 0) {
				return otherMatches[0];
			}

			return null;
		},
		/**
		 * 提取顺丰快递单号（适配自定义JSON格式）
		 * 示例结果：MMM={'k1':'024W','k2':'024JQ','k3':'','k4':'T6','k5':'SF1462209314710','k6':'','k7':'23b54d7f'}
		 */
		extractSFCode(scanResult) {
		  try {
		    // 处理自定义格式为标准JSON
		    const jsonStr = scanResult.replace(/^MMM=/, '').replace(/'/g, '"');
		    const data = JSON.parse(jsonStr);
		    // 从k5字段提取顺丰单号
		    if (data.k5 && /^SF\d{12,20}$/.test(data.k5)) {
		      return data.k5;
		    }
		  } catch (e) {
		    console.error('不是顺丰二维码或解析顺丰二维码数据失败：', e);
		  }
		  return null;
		},
		getdata: function () {
			var that = this;
			that.loading = true;
			app.get('ApiAdminOrder/shoporderdetail', {id: that.opt.id}, function (res) {
				that.loading = false;
				that.expressdata = res.expressdata;
				that.detail = res.detail;
				that.prolist = res.prolist;
				that.shopset = res.shopset;
				that.storeinfo = res.storeinfo;
				that.lefttime = res.lefttime;
				that.codtxt = res.codtxt;
				that.wxtc_weight = that.detail.cargo_weight;
				that.est_fee = that.detail.est_fee;
				if (res.lefttime > 0) {
					interval = setInterval(function () {
						that.lefttime = that.lefttime - 1;
						that.getdjs();
					}, 1000);
				}
        that.admin_user = res.admin_user;
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
			app.post('ApiAdminOrder/setremark', { type:'shop',orderid: that.detail.id,content:remark }, function (res) {
				app.success(res.msg);
				setTimeout(function () {
					that.getdata();
				}, 1000)
			})
    },
		changePrice:function(){
			this.$refs.dialogChangePrice.open();
		},
		changePriceConfirm: function (done, val) {
			this.$refs.dialogChangePrice.close();
			var that = this
			app.post('ApiAdminOrder/changePrice', { type:'shop',orderid: that.detail.id,val:val }, function (res) {
				app.success(res.msg);
				setTimeout(function () {
					that.getdata();
				}, 1000)
			})
    },
		fahuo:function(){
			if(this.detail.freight_type==10){
				this.$refs.dialogExpress10.open();
			}else{
				this.$refs.dialogExpress.open();
			}
		},
		dialogExpressClose:function(){
			this.$refs.dialogExpress.close();
		},
		dialogExpress10Close:function(){
			this.$refs.dialogExpress10.close();
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
			app.post('ApiAdminOrder/sendExpress', { type:'shop',orderid: that.detail.id,express_no:that.express_no,express_com:express_com}, function (res) {
				if(res.status == 0){
					app.error(res.msg);
					return;
				}
				app.success(res.msg);
				setTimeout(function () {
					that.getdata();
				}, 1000)
			})
		},
		setexpress_pic:function(e){
			this.express_pic = e.detail.value;
		},
		setexpress_fhname:function(e){
			this.express_fhname = e.detail.value;
		},
		setexpress_fhaddress:function(e){
			this.express_fhaddress = e.detail.value;
		},
		setexpress_shname:function(e){
			this.express_shname = e.detail.value;
		},
		setexpress_shaddress:function(e){
			this.express_shaddress = e.detail.value;
		},
		setexpress_remark:function(e){
			this.express_remark = e.detail.value;
		},
		confirmfahuo10:function(){
			this.$refs.dialogExpress10.close();
			var that = this
			var express_com = this.expressdata[this.express_index]
			app.post('ApiAdminOrder/sendExpress', { type:'shop',orderid: that.detail.id,pic:that.express_pic,fhname:that.express_fhname,fhaddress:that.express_fhaddress,shname:that.express_shname,shaddress:that.express_shaddress,remark:that.express_remark}, function (res) {
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
				app.post('ApiAdminOrder/ispay', { type:'shop',orderid: orderid }, function (data) {
					app.showLoading(false);
					app.success(data.msg);
					setTimeout(function () {
						that.getdata();
					}, 1000)
				})
			});
		},
		hexiao:function(e){
			var that = this;
			var orderid = e.currentTarget.dataset.id
			app.confirm('确定要核销并改为已完成状态吗?', function () {
				app.showLoading('提交中');
				app.post('ApiAdminOrder/hexiao', { type:'shop',orderid: orderid }, function (data) {
					app.showLoading(false);
					app.success(data.msg);
					setTimeout(function () {
						that.getdata();
					}, 1000)
				})
			});
		},
		showhxqr2:function(e){
			var that = this;
			var leftnum = e.currentTarget.dataset.num - e.currentTarget.dataset.hxnum;
			this.hxogid = e.currentTarget.dataset.id;
			if(leftnum <= 0){
				app.alert('没有剩余核销数量了');return;
			}
			that.hexiao_code = e.currentTarget.dataset.hexiao_code;
			var hxnumlist = [];
			for(var i=0;i<leftnum;i++){
				hxnumlist.push((i+1)+'');
			}
			console.log(hxnumlist);
			
				that.hxnumlist = hxnumlist;
				that.selecthxnumDialogShow = true;
				that.hxnum = '';
			
		},
		hxnumRadioChange: function (e) {
			var that = this;
			var index = e.currentTarget.dataset.index;
			this.hxnum = this.hxnumlist[index];
			app.confirm('确定要核销'+this.hxnum+'次吗?', function () {
				that.selecthxnumDialogShow = false;
				app.post('ApiAdminHexiao/hexiao',{op:'confirm',type:'shopproduct',co:that.hexiao_code,hxnum:that.hxnum}, function (res) {
					app.showLoading(false);
					if(res.status == 0){
						app.alert(res.msg);return;
					}
					if(that.hexiao_type == 1){
							app.success(tip+'成功');
							that.hexiao_status = true;
					}else{
							app.alert(res.msg,function(){
								app.goto('/admin/index/index','reLaunch');	
							});
					}
					
				})
			})
		},
		hideSelecthxnumDialog:function(){
			this.selecthxnumDialogShow = false;
		},
		delOrder: function (e) {
			var that = this;
			var orderid = e.currentTarget.dataset.id
			app.showLoading('删除中');
			app.confirm('确定要删除该订单吗?', function () {
				app.post('ApiAdminOrder/delOrder', { type:'shop',orderid: orderid }, function (data) {
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
				app.post('ApiAdminOrder/closeOrder', { type:'shop',orderid: orderid }, function (data) {
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
				app.post('ApiAdminOrder/refundnopass', { type:'shop',orderid: orderid }, function (data) {
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
				app.post('ApiAdminOrder/refundpass', { type:'shop',orderid: orderid }, function (data) {
					app.showLoading(false);
					app.success(data.msg);
					setTimeout(function () {
						that.getdata();
					}, 1000)
				})
			});
		},
		retundInput:function(e){
			var that = this;
			var valnum = e.detail.value;
			var {max,ogid} = e.currentTarget.dataset;
			var prolist = that.returnProlist;
			var refundNum = that.refundNum;
			var total = 0;
			
			if(valnum > max){
				return app.error('请输入正确的数量');
			}
			
			for(var i in refundNum){
				if(refundNum[i].ogid == ogid){
					refundNum[i].num = valnum;
				}
				if(refundNum[i].num == prolist[i].num){
					total += parseFloat(prolist[i].real_totalmoney)
				}else{
					total += refundNum[i].num * parseFloat(prolist[i].real_totalmoney) / prolist[i].num 
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
		refundinit:function(e){
			var that = this;
			that.loading = true;
			app.post('ApiAdminOrder/refundinit',{orderid:that.detail.id},function(data){
				that.loading = false;
				let prolist = data.prolist;
				that.returnProlist = data.prolist;
				that.refundTotalprice = data.detail.returnTotalprice;
				that.refundNum = [];
				that.refundReason = '';
				for(var i in prolist){
					that.refundNum.push({
						'ogid':prolist[i].id,
						'num':prolist[i].canRefundNum
					})
				}
				that.$refs.dialogExpress12.open();
			})
		},
		dialogExpress12Close:function(){
			this.returnProlist = [];
			this.refundReason = '';
			this.$refs.dialogExpress12.close();
		},
		gotoRefundMoney:function(){
			var that = this;
			console.log(that.refundNum,11111);
			app.confirm('确定要退款吗?', function () {
				that.$refs.dialogExpress12.close();
				app.showLoading('提交中');
				app.post('ApiAdminOrder/refund',{
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
					}, 1000)
				})
			});
		},
		peisong:function(){
			var that = this;
			that.loading = true;
			app.post('ApiAdminOrder/getpeisonguser',{type:'shop_order',orderid:that.detail.id},function(res){
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
						app.post('ApiAdminOrder/peisong', { type:'shop_order',orderid: that.detail.id,psid:psid}, function (res) {
                            if(res.status ==1){
                                app.success(res.msg);
                                setTimeout(function () {
                                	that.getdata();
                                }, 1000)
                            }else{
                                app.error(res.msg);
                            }
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
			app.post('ApiAdminOrder/peisong', { type:'shop_order',orderid: that.detail.id,psid:psid}, function (res) {
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
				app.post('ApiAdminOrder/peisongWx', { type:'shop_order',orderid: that.detail.id}, function (res) {
					that.loading = false;
					app.success(res.msg);
					setTimeout(function () {
						that.getdata();
					}, 1000)
				})
			})
		},
		uploadimg:function(e){
			var that = this;
			var pernum = parseInt(e.currentTarget.dataset.pernum);
			if(!pernum) pernum = 1;
			var field= e.currentTarget.dataset.field
			var pics = that[field]
			if(!pics) pics = [];
			app.chooseImage(function(urls){
				for(var i=0;i<urls.length;i++){
					pics.push(urls[i]);
				}
				if(field == 'express_pic') that.express_pic = pics[0];
			},pernum);
		},
		removeimg:function(e){
			var that = this;
			var index= e.currentTarget.dataset.index
			var field= e.currentTarget.dataset.field
			if(field == 'express_pic'){
				that.express_pic = '';
			}
		},
        peisongMyt:function(e){
            var that = this;
            var detail = that.detail;
            if(detail.myt_set){
                this.$refs.dialogExpress11.open();
            }else{
                that.goMyt();
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
                    type:'shop_order',
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
    //查看送货单图片
    showShdimg:function(e){
      var that = this;
      var id = e.currentTarget.dataset.id;
      that.loading = true;
      app.post('ApiAdminOrder/shdImg', {id:id}, function (res) {
        that.loading = false;
        if(res.status==1){
          that.shdimg = res.url || '';
          that.$refs.dialogShdimg.open();
          return;
        }else{
          app.error('送货单生成失败');
          return;
        }

      })

    },
    closeShdimg:function(){
      this.$refs.dialogShdimg.close();
    },
    goWxtc:function(){
      var that = this;
      var id = that.detail.id;
      var wxtc_cargo_type = that.wxtc_cargo_type;
      var wxtc_weight = that.wxtc_weight;

      console.log(wxtc_cargo_type)
      console.log(wxtc_weight)

      if(!wxtc_weight){
        app.error('请填写物品重量');
        return;
      }
      if(!wxtc_cargo_type){
        app.error('请选择物品类型');
        return;
      }

      that.loading = true;
      app.post('ApiAdminOrder/wxtcPeisong', {orderid:id,type: 'shop_order',weight:wxtc_weight,cargo_type:wxtc_cargo_type}, function (res) {
        that.loading = false;
        if(res.status==1){
          app.success(res.msg);
          that.$refs.dialogWxtcExpress.close();
          setTimeout(function () {
            that.getdata();
          }, 1000)
          return;
        }else{
          app.error(res.msg);
          return;
        }
      })

    },
    peisongWxtc:function(e){
      var that = this;
      var detail = that.detail;
      that.$refs.dialogWxtcExpress.open();
    },
    wxtcConfirmfahuo:function(){
      var that = this
      that.goWxtc();
    },
    dialogWxExpressClose:function(){
      this.$refs.dialogWxtcExpress.close();
    },
    wxtcWeight:function(e){
      var that = this
      that.wxtc_weight = e.detail.value;
    },
    cargoTypeChange:function(e){
      var that = this
      that.wxtc_cargo_type = e.detail.value;
    },
    logistics:function(e){
      var express_com = e.currentTarget.dataset.express_com
      var express_no = e.currentTarget.dataset.express_no
      var express_content = e.currentTarget.dataset.express_content
      var express_type = e.currentTarget.dataset.express_type
      var prolist = this.prolist;
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
        this.$refs.dialogSelectExpress.open();
      }
    },
    hideSelectExpressDialog:function(){
      this.$refs.dialogSelectExpress.close();
    },
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

.product{width:94%;margin:0 3%;border-radius:8rpx;margin-top:16rpx;padding: 14rpx 3%;background: #FFF;}
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

.orderinfo{width:94%;margin:0 3%;border-radius:8rpx;margin-top:16rpx;margin-top:10rpx;padding: 14rpx 3%;background: #FFF;}
.orderinfo .item{display:flex;width:100%;padding:20rpx 0;border-bottom:1px dashed #ededed;}
.orderinfo .item:last-child{ border-bottom: 0;}
.orderinfo .item .t1{width:200rpx;}
.orderinfo .item .t2{flex:1;text-align:right}
.orderinfo .item .red{color:red}

.bottom{ width: 100%;height:calc(134rpx + env(safe-area-inset-bottom));padding: 0 20rpx;background: #fff; position: fixed; bottom: 0px;left: 0px;display:flex;justify-content:flex-end;align-items:center;flex-wrap: wrap;}

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
.uni-dialog-content {display: flex;flex-direction: row;justify-content: center;align-items: center;padding: 5px 5px 15px 5px;}
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
.glassitem{background:#f5f5f5;display: flex;align-items: center;padding: 10rpx 0;font-size: 24rpx;}
.glassitem .gcontent{flex:1;padding: 0 20rpx;}
.glassheader{line-height: 50rpx;font-size: 26rpx;font-weight: 600;}
.glassrow{line-height: 40rpx;font-size: 26rpx;}
.glassrow .glasscol{min-width: 25%;text-align: center;}
.glassitem .bt{border-top:1px solid #e3e3e3}
.pdl10{padding-left: 10rpx;}
.item .txt{color:#666;margin-top:20rpx;font-size:26rpx;text-align:center}

.pstime-item{display:flex;border-bottom: 1px solid #f5f5f5;padding:20rpx 30rpx;}
.pstime-item .radio{flex-shrink:0;width: 32rpx;height: 32rpx;background: #FFFFFF;border: 2rpx solid #BFBFBF;border-radius: 50%;margin-right:30rpx}
.pstime-item .radio .radio-img{width:100%;height:100%}
.hxqrbox{background:#fff;padding:50rpx;position:relative;border-radius:20rpx}
.hxqrbox .img{width:400rpx;height:400rpx}
.hxqrbox .txt{color:#666;margin-top:20rpx;font-size:26rpx;text-align:center}
.hxqrbox .close{width:50rpx;height:50rpx;position:absolute;bottom:-100rpx;left:50%;margin-left:-25rpx;border:1px solid rgba(255,255,255,0.5);border-radius:50%;padding:8rpx}
</style>