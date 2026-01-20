<template>
<view class="container">
	<block v-if="isload">
		<view class="ordertop" :style="'background:url('+(shopset.order_detail_toppic?shopset.order_detail_toppic: pre_url + '/static/img/ordertop.png')+');background-size:100%'">
			<view class="f1" v-if="detail.status==0">
				<view class="t1">等待买家付款</view>
				<view class="t2" v-if="djs">剩余时间：{{djs}}</view>
				<view class="t2" v-if="detail.paytypeid == 5">
					 <text v-if="detail.transfer_check == 1">{{t('转账汇款')}}后请上传付款凭证</text> 
					 <text v-if="detail.transfer_check == 0">{{t('转账汇款')}}待审核</text>
					 <text v-if="detail.transfer_check == -1">{{t('转账汇款')}}已驳回</text>
				</view>
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
			<view class="f1" v-if="detail.status==8">
				<view class="t1">订单已到达代收点</view>
			</view>
		</view>
    
		<view class="address flex-y-center" v-if="detail.is_pingce != 1">
			<view class="img" v-if="mendian_no_select == 0">
				<image :src="pre_url+'/static/img/address3.png'"></image>
			</view>
			<view class="info" v-if="detail.mdid == -1">
				<view class="t1"><text user-select="true" selectable="true">{{detail.linkman}} {{detail.tel}} {{ email }} </text></view>
				<view class="t1" style="margin-top:20rpx">取货地点：</view>
				<view>
					<block v-for="(item, idx) in storelist" :key="idx">
						<view class="radio-item" v-if="idx<5 || storeshowall==true" @tap="openLocation" :data-latitude="item.latitude" :data-longitude="item.longitude" :data-company="item.name" :data-address="item.address">
							<view class="f1">
								<view>{{item.name}}</view>
								<view v-if="item.address" style="text-align:left;font-size:24rpx;color:#aaaaae;display: -webkit-box;-webkit-box-orient: vertical;-webkit-line-clamp:1;overflow: hidden;">{{item.address}}</view>
							</view>
							<text style="color:#f50;">{{item.juli}}</text>
						</view>
					</block>
					<view v-if="storeshowall==false && storelist.length > 5" class="storeviewmore" @tap="doStoreShowAll">- 查看更多 - </view>
				</view>
        <view class="t2" v-if="detail.worknum">工号：{{detail.worknum}}</view>
			</view>
			<view class="info" v-else-if="mendian_no_select==1">
				<text class="t1" user-select="true" selectable="true">{{detail.linkman}} {{detail.tel}} {{ email }}</text>
				<view class="t1" style="margin-top:20rpx">取货地点：</view>
				<view>
					<block v-for="(item, idx) in mendianArr" :key="idx">
						<view class="radio-item" v-if="idx<5 || storeshowall==true" @tap="openLocation" :data-latitude="item.latitude" :data-longitude="item.longitude" :data-company="item.name" :data-address="item.address">
							<view class="img">
								<image :src="pre_url+'/static/img/address3.png'"></image>
							</view>
							<view class="f1">
								<view>{{item.name}}</view>
								<view v-if="item.address" style="text-align:left;font-size:24rpx;color:#aaaaae;display: -webkit-box;-webkit-box-orient: vertical;-webkit-line-clamp:1;overflow: hidden;">{{item.address}}</view>
							</view>
							<text style="color:#f50;">{{item.juli}}</text>
						</view>
					</block>
					<view v-if="storeshowall==false && mendianArr.length > 5" class="storeviewmore" @tap="doStoreShowAll">- 查看更多 - </view>
				</view>
			</view>			
			<view class="info" v-else>
				<text class="t1" user-select="true" selectable="true">{{detail.linkman}} {{detail.tel}} {{ email }}</text>
				<text class="t2" v-if="detail.freight_type!=1 && detail.freight_type!=3" user-select="true" selectable="true">地址：{{detail.area}}{{detail.address}}</text>
        <text class="t2" v-if="detail.product_thali">学生姓名：{{detail.product_thali_student_name}}</text>
        <text class="t2" v-if="detail.product_thali">学校信息：{{detail.product_thali_school}}</text>
				<block v-if="detail.freight_type==1">
					<text class="t2" v-if="!isNull(storeinfo)" @tap="openMendian" :data-storeinfo="storeinfo" user-select="true" selectable="true">取货地点：{{storeinfo.name}} - {{storeinfo.address}}</text>
					<text class="t2" v-else>取货地点数据不存在，请联系客服</text>
				</block>
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
        <view v-else class="t2" style="color: red;display: flex;justify-content: flex-end;align-items: center;">
          <view>未领取</view>
          <block v-if="detail.status == 1">
          <!-- #ifdef APP -->
            <button @tap="giveordershareapp" class="btn2" >分享好友</button>
          <!-- #endif -->
          <!-- #ifdef H5 -->
            <button @tap="giveordersharemp" class="btn2" >分享好友</button>
          <!-- #endif -->
          <!-- #ifndef H5 -->
            <button open-type="share" class="btn2" >分享好友</button>
          <!-- #endif -->
          </block>
        </view>
      </view>
      <block v-if="detail.giveordermid>0 && detail.givemember">
        <view class="item">
          <text class="t1">好友信息</text>
          <text class="flex1"></text>
          <image :src="detail.givemember.headimg" style="width:80rpx;height:80rpx;margin-right:8rpx"/>
          <text  style="height:80rpx;line-height:80rpx">{{detail.givemember.nickname}}</text>
        </view>
      </block>
    </view>
		
		<view class="orderinfo" v-if = "detail.is_pingce == 1">
			<view class="title">
				测评信息
			</view>
			<view class="item">
				<text class="t1">姓名</text>
				<text class="t2">{{detail.linkman}}</text>
			</view>
			<view class="item">
				<text class="t1">性别</text>
				<text class="t2">{{detail.pingce.gender}}</text>
			</view>
			<view class="item">
				<text class="t1">生日</text>
				<text class="t2">{{detail.pingce.age}}</text>
			</view>
			<view class="item">
				<text class="t1">手机</text>
				<text class="t2">{{detail.tel}}</text>
			</view>
			<view class="item">
				<text class="t1">邮箱</text>
				<text class="t2">{{detail.pingce.email}}</text>
			</view>
			<view class="item">
				<text class="t1">院校</text>
				<text class="t2">{{detail.pingce.school}}</text>
			</view>
			<view class="item" v-if="detail.pingce.faculties">
				<text class="t1">院系</text>
				<text class="t2">{{detail.pingce.faculties ? detail.pingce.faculties : ''}}</text>
			</view>
			<view class="item">
				<text class="t1">专业</text>
				<text class="t2">{{detail.pingce.major}}</text>
			</view>
			<view class="item">
				<text class="t1">学历</text>
				<text class="t2">{{detail.pingce.education}}</text>
			</view>
			<view class="item">
				<text class="t1">入学年份</text>
				<text class="t2">{{detail.pingce.enrol}}年</text>
			</view>
			<view class="item" v-if="detail.pingce.class_name">
				<text class="t1">班级</text>
				<text class="t2">{{detail.pingce.class_name ? detail.pingce.class_name : ''}}</text>
			</view>
		</view>
		
		<view class="btitle flex-y-center" v-if="detail.bid>0" @tap="goto" :data-url="'/pagesExt/business/index?id=' + detail.bid">
			<image :src="detail.binfo.logo" style="width:36rpx;height:36rpx;"></image>
			<view class="flex1" decode="true" space="true" style="padding-left:16rpx">{{detail.binfo.name}}</view>
		</view>
		<view class="product">
			<view v-for="(item, idx) in prolist" :key="idx" class="box">
				<view class="content">
					<view @tap="goto" :data-url="'/pages/shop/product?id=' + item.proid">
						<image :src="item.pic"></image>
					</view>
					<view class="detail">
						<text class="t1">{{item.name}}</text>
						<view class="t2 flex flex-y-center flex-bt">
							<text>{{item.gg_group_title ? item.gg_group_title:''}} {{item.ggname}}</text>
							<view class="btn3" v-if="detail.status==3 && item.iscomment==0 && shopset.comment==1" @tap.stop="goto" :data-url="'comment?ogid=' + item.id">去评价</view>
							<view class="btn3" v-if="detail.status==3 && item.iscomment==1" @tap.stop="goto" :data-url="'comment?ogid=' + item.id">查看评价</view>
							
							<!-- 单品申请退货 -->
							<block v-if="(detail.status==2 || detail.status==1) && detail.paytypeid!='4' && shopset.canrefund==1 && detail.order_can_refund==1 && item.canrefund==1 && item.refund_num < item.num">
								<view class="btn3" @tap="goto" :data-url="'refundSelect?orderid=' + detail.id+'&ogid='+item.id">退款</view>
							</block>
						</view>
            <view class="t2" v-if="item.protype && item.protype == 3">
            	<text>手工费：{{item.hand_fee}}</text>
            </view>
						<view class="t3" v-if="item.product_type && item.product_type==2">
							<text class="x1 flex1">{{item.real_sell_price}}元/斤</text>
							<text class="x2">×{{item.real_total_weight}}斤</text>
						</view>
						<view class="t3" v-else>
							<text class="x1 flex1">￥{{item.sell_price}}
							<text v-if="!isNull(item.service_fee) && item.service_fee > 0">+{{item.service_fee}}{{t('服务费')}}</text>
							<text v-if="!isNull(item.freezemoney_price) && item.freezemoney_price > 0">+{{item.freezemoney_price}}{{t('冻结资金')}}</text>
							</text>
							<text class="x2">×{{item.num}}</text>
						</view>
						<!-- <view class="t4 flex flex-x-bottom">
							<view class="btn3" v-if="detail.status==3 && item.iscomment==0 && shopset.comment==1" @tap.stop="goto" :data-url="'comment?ogid=' + item.id">去评价</view>
							<view class="btn3" v-if="detail.status==3 && item.iscomment==1" @tap.stop="goto" :data-url="'comment?ogid=' + item.id">查看评价</view>
						</view> -->
						<block v-if="(detail.status==1 || detail.status==2 || detail.status==8) && (detail.freight_type==1 || detail.freight_type==5) && item.is_quanyi!=1 && item.hexiao_code">
							<view class="btn2" @tap.stop="showhxqr" :data-id="item.id" :data-num="item.num" :data-hxnum="item.hexiao_num" :data-hexiao_code="item.hexiao_code" style="position:absolute;top:20rpx;right:0rpx;">核销码</view>
						</block>
						<block v-if="(detail.status==1 || detail.status==2 || detail.status==8) && item.is_quanyi==1 && item.hexiao_code">
							<view class="btn3" @tap.stop="showhxqr2" :data-id="item.id" :data-num="item.hexiao_num_total" :data-hxnum="item.hexiao_num_used" :data-hexiao_code="item.hexiao_code" style="position:absolute;top:46rpx;right:100rpx;">核销码</view>
						</block>
						<block v-if="mendian_no_select==1 && item.is_hx">
							<view class="t3"><text class="x2">已核销</text></view>
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
							R {{item.glassrecord.degress_right}}/{{item.glassrecord.ats_right?item.glassrecord.ats_right:'0.00'}}*{{item.glassrecord.ats_zright?item.glassrecord.ats_zright:'0'}}  <text class="pdl10" v-if="item.glassrecord.type==3">ADD+{{item.glassrecord.add_right?item.glassrecord.add_right:0}}</text>
							</view>
							<view class="grow">
							L {{item.glassrecord.degress_left}}/{{item.glassrecord.ats_left?item.glassrecord.ats_left:'0.00'}}*{{item.glassrecord.ats_zleft?item.glassrecord.ats_zleft:'0'}}  <text class="pdl10" v-if="item.glassrecord.type==3">ADD+{{item.glassrecord.add_left?item.glassrecord.add_left:0}}</text>
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
				<view class="flex-bt order-info-title">
					<text class="t1" style="color:#111">发货信息</text>
					<view class="btn-class" @click="copy" :data-text='detail.freight_content'>复制</view>
				</view>
				<view v-for="(item, index) in contentWithLinks" :key="index" class="t2" style="text-align:left;margin-top:10rpx;padding:0 10rpx;display: flex;justify-content: space-between;align-items: center;line-height: 60rpx;" user-select="true" selectable="true">
					<view v-if="item.url"  @click="handleLinkClick(item.url)" style="word-wrap: break-word;max-width: 540rpx;">{{item.text}}</view>
					<view v-else style="word-wrap: break-word;max-width: 540rpx;">{{item.text}}</view>
          <view v-if="detail.exchangepage" class="btn-class" @tap="goto" :data-url="'/pagesD/shop/codeExchangepage?isgoback=1&orderid='+detail.id">去使用</view>
				</view>
			</view>
		</view>
		
		<view class="orderinfo">
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
			<view class="item" v-if="detail.paytypeid">
				<text class="t1">支付方式</text>
				<text class="t2">{{detail.paytype}}</text>
			</view>
			<view class="item" v-if="detail.yuding_type =='1'">
				<text class="t1">订单类型</text>
				<text class="t2">预定订单</text>
			</view>
			<view class="item" v-if="detail.exchange_card_take_date">
				<text class="t1">兑换提货日期</text>
				<text class="t2">{{detail.exchange_card_take_date}}</text>
			</view>
			<block v-if="detail.paytypeid == '5' && detail.transfer_check==1">
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
				<view class="item" v-if="pay_transfer_info.pay_transfer_qrcode">
					<text class="t1">图片</text>
					<view id="content_picpreview" class="flex" style="flex-wrap:wrap;padding-top:20rpx">
						<view v-for="(item, index) in pay_transfer_info.pay_transfer_qrcode_arr" :key="index" class="layui-imgbox">
							<view class="layui-imgbox-img"><image :src="item" @tap="previewImage" :data-url="item" mode="widthFix"></image></view>
						</view>
					</view>
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
      <view class="item" v-if="detail.status==3 && detail.handtime">
      	<text class="t1">回寄时间</text>
      	<text class="t2 red">{{detail.handtime}}</text>
      </view>
		</view>
		<view class="orderinfo">
			<view class="item">
				<text class="t1">商品金额</text>
				<text class="t2 red">¥{{detail.product_price}} <text v-if="!isNull(detail.service_fee) && detail.service_fee > 0">+{{detail.service_fee}}{{t('服务费')}}</text></text>
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
				<text class="t2 red">¥{{detail.invoice_money}}</text>
			</view>
			<view class="item">
				<text class="t1">配送方式</text>
				<text class="t2">{{detail.freight_text}}</text>
			</view>
			<view class="item" v-if="detail.freight_time">
				<text class="t1">{{detail.freight_type!=1?'配送':'提货'}}时间</text>
				<text class="t2">{{detail.freight_time}}</text>
			</view>
			<view class="item" v-if="detail.freight_price > 0">
				<text class="t1" v-if="detail.freight_type==0 || detail.freight_type==2">配送费</text>
				<text class="t1" v-else="detail.freight_type==1">服务费</text>
				<text class="t2 red">¥{{detail.freight_price}}</text>
			</view>
			<view class="item" v-if="detail.up_floor_fee && detail.up_floor_fee > 0">
				<text class="t1">上楼费</text>
				<text class="t2 red">¥{{detail.up_floor_fee}}</text>
			</view>
			<view class="item" v-if="detail.weight_price>0">
				<text class="t1">{{t('包装费')}}</text>
				<text class="t2 red">¥{{detail.weight_price}}</text>
			</view>
			<view class="item" v-if="detail.coupon_money > 0">
				<text class="t1">{{t('优惠券')}}抵扣</text>
				<text class="t2 red">-¥{{detail.coupon_money}}</text>
			</view>
			<view class="item" v-if="detail.water_coupon_money && detail.water_coupon_money > 0">
				<text class="t1">{{t('电子水票')}}抵扣</text>
				<text class="t2 red">-¥{{detail.water_coupon_money}}</text>
			</view>
			<view class="item" v-if="detail.discount_rand_money > 0">
				<text class="t1">随机立减</text>
				<text class="t2 red">-¥{{detail.discount_rand_money}}</text>
			</view>
			<view class="item" v-if="detail.discount_money_admin > 0">
				<text class="t1">商家优惠</text>
				<text class="t2 red">-¥{{detail.discount_money_admin}}</text>
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
				<text class="t2 red"><text v-if="showprice_dollar && detail.usd_totalprice>0">${{detail.usd_totalprice}}</text>  ¥{{detail.totalprice}} 
				<text v-if="!isNull(detail.service_fee_money) && detail.service_fee_money > 0"> + {{detail.service_fee_money}}{{t('服务费')}}</text>
				<text v-if="!isNull(detail.total_freezemoney_price) && detail.total_freezemoney_price > 0"> + {{detail.total_freezemoney_price}}{{t('冻结资金')}}</text>
				</text>
			</view>
      <view class="item" v-if="detail.combine_money && detail.combine_money > 0">
      	<text class="t1">{{t('余额')}}已付</text>
      	<text class="t2 red">-¥{{detail.combine_money}}</text>
      </view>
      <view class="item" v-if="detail.paytypeid == 2 && detail.combine_wxpay && detail.combine_wxpay > 0">
      	<text class="t1">微信已付</text>
      	<text class="t2 red">-¥{{detail.combine_wxpay}}</text>
      </view>
      <view class="item" v-if="(detail.paytypeid == 3 || (detail.paytypeid>=302 && detail.paytypeid<=330)) && detail.combine_alipay && detail.combine_alipay > 0">
      	<text class="t1">支付宝已付</text>
      	<text class="t2 red">-¥{{detail.combine_alipay}}</text>
      </view>
			<view class="item" v-if="detail.is_yuanbao_pay==1">
				<text class="t1">{{t('元宝')}}</text>
				<text class="t2 red">{{detail.total_yuanbao}}</text>
			</view>
			<view class="item">
				<text class="t1">订单状态</text>
				<text class="t2" v-if="detail.status==0">未付款</text>
				<text class="t2" v-if="detail.status==1">{{detail.paytypeid==4?'待发货':'已支付'}}</text>
				<text class="t2" v-if="detail.status==2 && detail.express_isbufen==0">已发货</text>
				<text class="t2" v-if="detail.status==2 && detail.express_isbufen==1">部分发货</text>
				<text class="t2" v-if="detail.status==3">已收货</text>
				<text class="t2" v-if="detail.status==4">已关闭</text>
				<text class="t2" v-if="detail.status==8">待提货</text>
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
		<view class="orderinfo" v-if="(detail.formdata).length > 0">
			<view class="item" v-for="item in detail.formdata">
				<text class="t1">{{item[0]}}</text>
				<view class="t2" v-if="item[2]=='upload'"><image :src="item[1]" style="width:400rpx;height:auto" mode="widthFix" @tap="previewImage" :data-url="item[1]"/></view>
				<view class="t2" v-else-if="item[2]=='upload_pics'" v-for="picurl in item[1]">
          <image :src="picurl" style="width:400rpx;height:auto" mode="widthFix" @tap="previewImage" :data-url="picurl"/>
        </view>
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
    <view class="orderinfo" v-if="detail.isdygroupbuy==1">
    	<view class="item">
    		<text class="t1">抖音团购券信息</text>
        <text class="t2">{{detail.dyorderids}}</text>
    	</view>
    </view>
		<view class="orderinfo refundtips" v-if="detail.order_refund_tips">
			<textarea :value="detail.order_refund_tips" disabled="true" auto-height="true"></textarea>
		</view>
		<view v-if="show_product_xieyi && product_xieyi" class="orderinfo">
			<view class="item">
				<text class="t1">商品协议</text>
				<view class="t2">
					<text v-for="(xieyi_item, xieyi_index) in product_xieyi" :key="xieyi_index" @tap="showproductxieyi(xieyi_index)" :style="'color:'+t('color1')">《{{xieyi_item.name}}》</text>
				</view>
			</view>
		</view>
		<view style="width:100%;height:calc(160rpx + env(safe-area-inset-bottom));"></view>

		<view class="bottom notabbarbot" v-if="fromfenxiao==0">
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
					<view class="btn2" @tap="goto" :data-url="'/pagesB/workorder/index?type=1&id='+detail.id" :data-id="detail.id">发起工单</view>
			</block>
      <block v-if="detail.status!=4 && detail.transfer_order_parent_check">
        <view @tap.stop="transferOrder" :data-orderid="detail.id" class="btn2" style="max-width: 220rpx">转给上级审核</view>
      </block>
			<block v-if="detail.status==0">
				<view class="btn2" @tap="toclose" :data-id="detail.id">关闭订单</view>
				<view class="btn1" v-if="detail.paytypeid != 5" :style="{background:t('color1')}" @tap="goto" :data-url="'/pagesExt/pay/pay?id=' + detail.payorderid">去付款</view>
				<block v-if="detail.paytypeid==5">
						<view class="btn1" v-if="detail.transfer_check == 1" :style="{background:t('color1')}" @tap="goto" :data-url="'/pages/pay/transfer?id=' + detail.payorderid">上传付款凭证</view>
						<view class="btn1" v-if="detail.transfer_check == 0" :style="{background:t('color1')}">转账待审核</view>
						<view class="btn1" v-if="detail.transfer_check == -1" :style="{background:t('color1')}">转账已驳回</view>
				</block>
			</block>
      <view class="btn1" v-if="detail.cancodpay" :style="{background:t('color1')}" @tap="gocodpay" :data-id="detail.id">在线支付</view>
			<block v-if="detail.status==1">
				<block v-if="detail.paytypeid!='4'">
					<view class="btn2" @tap="goto" :data-url="'refundSelect?orderid=' + detail.id" v-if="shopset.canrefund==1 && detail.order_can_refund==1 && detail.refundnum < detail.procount">退款</view>
				</block>
				<block v-else>
					<!-- <view class="btn2">{{codtxt}}</view> -->
				</block>
			</block>
			<block v-if="(detail.status==2 || detail.status==3) && detail.freight_type!=3 && detail.freight_type!=4">
					<view class="btn2" v-if="detail.express_type =='express_wx'" @tap="logistics" :data-express_type="detail.express_type" :data-express_com="detail.express_com" :data-express_no="detail.express_no" :data-express_content="detail.express_content">订单跟踪</view>
					<view class="btn2" v-else @tap="logistics" :data-express_type="detail.express_type" :data-express_com="detail.express_com" :data-express_no="detail.express_no" :data-express_content="detail.express_content">查看物流</view>
			</block>
			<!-- <view class="btn2" v-if="detail.status==1 && detail.freight_type==1 && detail.freight_type1_shipping_status==1" @tap="logistics" :data-express_type="detail.express_type" data-express_com="自提" :data-express_no="detail.ordernum" :data-express_content="detail.express_content">查看物流</view> -->
			<block v-if="([1,2,3]).includes(detail.status) && invoice">
				<view class="btn2" @tap="goto" :data-url="'invoice?type=shop&orderid=' + detail.id">发票</view>
			</block>
			<block v-if="detail.is_pingce == 1 && (detail.status==1 || detail.status==2 || detail.status==3)">
				<view class="btn1" @tap="viewReport" :data-id="detail.id" v-if="detail.pingce_status == 2" :style="{background:t('color1')}">查看报告</view>
				<view class="btn1" @tap="toevaluate" :data-id="detail.id" v-else :style="{background:t('color1')}">继续测评</view>
			</block>
			<block v-if="detail.status==2">
				<block v-if="detail.paytypeid!='4'">
					<view class="btn2" @tap="goto" :data-url="'refundSelect?orderid=' + detail.id" v-if="shopset.canrefund==1 && detail.order_can_refund==1 && detail.refundnum < detail.procount">退款</view>
				</block>
				<view class="btn1" :style="{background:t('color1')}" @tap="orderCollect" :data-id="detail.id" v-if="detail.can_collect && detail.paytypeid!='4' && (detail.balance_pay_status==1 || detail.balance_price==0)">
          确认收货
        </view>
        <view class="btn1" style="background:#bbb" v-if="!detail.can_collect && detail.paytypeid!='4' && (detail.balance_pay_status==1 || detail.balance_price==0)">
          运输中
        </view>
				<!-- <view class="btn2" v-if="detail.paytypeid=='4'">{{codtxt}}</view> -->
				<view v-if="detail.balance_pay_status == 0 && detail.balance_price > 0" class="btn1" :style="{background:t('color1')}" @tap.stop="goto" :data-url="'/pagesExt/pay/pay?id=' + detail.balance_pay_orderid">支付尾款</view>
			</block>
			<block v-if="(detail.status==1 || detail.status==2 || detail.status==8) && (detail.freight_type==1 || detail.freight_type==5) && detail.hexiao_qr">
				<view class="btn2" @tap="showhxqr" :data-hexiao_qr="detail.hexiao_qr">核销码</view>
			</block>
			<view v-if="detail.refundCount" class="btn2" @tap.stop="goto" :data-url="'refundlist?orderid='+ detail.id">售后详情</view>
      <block v-if="detail.ishand==1">
        <block v-if="detail.status==3">
            <view class="btn2" @tap.stop="goto" :data-url="'/pagesA/handwork/hand?orderid=' + detail.id" v-if="detail.canhand &&  detail.hand_num < detail.procount">回寄</view>
        </block>
        <view v-if="detail.handCount" class="btn2" @tap.stop="goto" :data-url="'/pagesA/handwork/handlist?orderid='+ detail.id">查看回寄</view>
      </block>
			<block v-if="detail.status==3 || detail.status==4">
				<view class="btn2" @tap="todel" :data-id="detail.id">删除订单</view>
			</block>
      <block v-if="detail.shop_order_exchange_product && (detail.status==3 || detail.status==2)">
        <view class="btn2" @tap="goto" :data-url="'refundSelect?orderid=' + detail.id + '&type=exchange'" style="background-color: #1A1A1A;color: #fff">换货</view>
      </block>
			<block v-if="detail.bid>0 && detail.status==3">
				<view v-if="iscommentdp==0" class="btn1" :style="{background:t('color1')}" @tap="goto" :data-url="'/pagesExt/order/commentdp?orderid=' + detail.id">评价店铺</view>
				<view v-if="iscommentdp==1" class="btn2" @tap="goto" :data-url="'/pagesExt/order/commentdp?orderid=' + detail.id">查看评价</view>
			</block>
		</view>
		<uni-popup id="dialogHxqr" ref="dialogHxqr" type="dialog">
			<view class="hxqrbox">
				<image :src="hexiao_qr" @tap="previewImage" :data-url="hexiao_qr" class="img"/>
				<view class="txt">请出示核销码给核销员进行核销</view>
				<view v-if="detail.hexiao_code_member">
					<input type="number" placeholder="请输入核销密码" @input="set_hexiao_code_member" style="border: 1px #eee solid;padding: 10rpx;margin:20rpx 10rpx;height:70rpx;border-radius:4px;flex:1;font-size:28rpx;"/>
					<button @tap="hexiao" class="btn" :style="{background:t('color1')}">确定</button>
				</view>
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
				</view>
				<view class="popup__content">
					<view class="pstime-item" v-for="(item, index) in hxnumlist" :key="index" @tap="hxnumRadioChange" :data-index="index">
						<view class="flex1">{{item}}</view>
						<view class="radio" :style="hxnum==item ? 'background:'+t('color1')+';border:0' : ''"><image class="radio-img" :src="pre_url+'/static/img/checkd.png'"/></view>
					</view>
				</view>
			</view>
		</view>
		<view v-if="showproxieyi" class="xieyibox">
			<view class="xieyibox-content">
				<view style="overflow:scroll;height:100%;">
					<parse :content="proxieyi_content" @navigate="navigate"></parse>
				</view>
				<view style="position:absolute;z-index:9999;bottom:10px;left:0;right:0;margin:0 auto;text-align:center; width: 50%;height: 60rpx; line-height: 60rpx; color: #fff; border-radius: 8rpx;" :style="{background:'linear-gradient(90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'}"  @tap="hideproxieyi">确定</view>
			</view>
		</view>
		
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
			fromfenxiao:0,
			hexiao_code_member:'',
			showprice_dollar:false,
			hexiao_qr:'',
			selecthxnumDialogShow:false,
			hxogid:'',
			hxnum:'',
			hxnumlist:[],
			storelist:[],
			storeshowall:false,
			mendian_no_select:0,
			mendianArr:[],
			detail_content:'',
			email:'',
			show_product_xieyi:0,//是否展示产品协议
			product_xieyi:[],//产品协议列表
			showproxieyi:0,//是否展示产品协议弹窗
			proxieyi_content:'',//产品协议内容
			reportShow:false,//测评报告
			reportArr:{},//报告列表
    };
  },

  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		if (this.opt && this.opt.fromfenxiao && this.opt.fromfenxiao == '1'){
		  this.fromfenxiao = 1;
    }
		this.getdata();
  },
	onPullDownRefresh: function () {
		this.getdata();
	},
  onUnload: function () {
    clearInterval(interval);
  },
  onShareAppMessage:function(){
    var that = this;
    if(that.detail.usegiveorder && that.detail.usegiveorder == 1){
      return {
      	title: that.detail.giveordertitle,
      	path: '/pagesC/shop/takegiveorder?payorderid=' + that.detail.payorderid,
      	imageUrl: that.detail.giveorderpic,
      	desc: that.detail.giveordertitle,
      };
    }
  	return this._sharewx();
  },
  onShareTimeline:function(){
    var that = this;
    if(that.detail.usegiveorder && that.detail.usegiveorder == 1){
      return {
      	title: that.detail.giveordertitle,
      	path: '/pagesC/shop/takegiveorder?payorderid=' + that.detail.payorderid,
      	imageUrl: that.detail.giveorderpic,
      	desc: that.detail.giveordertitle,
      };
    }
  	var sharewxdata = this._sharewx();
  	var query = (sharewxdata.path).split('?')[1]+'&seetype=circle';
  	return {
  		title: sharewxdata.title,
  		imageUrl: sharewxdata.imageUrl,
  		query: query
  	}
  },
  methods: {
		getdata: function () {
			var that = this;
			that.loading = true;
			app.get('ApiOrder/detail', {id: that.opt.id,channel:that.opt.channel}, function (res) {
				that.loading = false;
					if(res.status == 1){
					that.iscommentdp = res.iscommentdp,
					that.detail = res.detail;	
					// 判断当前值存不存在					
					if(res.detail.pingce){
						  that.detail.pingce = JSON.parse(res.detail.pingce)
					}		

					that.prolist = res.prolist;
					that.shopset = res.shopset;
					that.storeinfo = res.storeinfo;
					that.lefttime = res.lefttime;
					that.codtxt = res.codtxt;
					that.pay_transfer_info =  res.pay_transfer_info;
					that.payorder = res.payorder;
					that.invoice = res.invoice;
					that.storelist = res.storelist || [];
					that.showprice_dollar = res.showprice_dollar
					if (res.lefttime > 0) {
						interval = setInterval(function () {
							that.lefttime = that.lefttime - 1;
							that.getdjs();
						}, 1000);
					}
					that.mendian_no_select = res.mendian_no_select;
					that.mendianArr = res.mendianArr;
					that.show_product_xieyi = res.show_product_xieyi;
					that.product_xieyi = res.product_xieyi;

					that.loaded();
          
          //订单赠好友
          if(that.detail.usegiveorder && that.detail.usegiveorder == 1){
            that._sharemp({
            	title:that.detail.giveordertitle,
            	desc:that.detail.giveordertitle,
            	link:app.globalData.pre_url + '/h5/' + app.globalData.aid + '.html#/pagesC/shop/takegiveorder?payorderid=' + that.detail.payorderid,
            	pic:that.detail.giveorderpic,
            });
          }
					that.detail_content = res.detail.freight_content;
					if (that.detail.mdid == -1 && that.storelist) {
						app.getLocation(function(res) {
							var latitude = res.latitude;
							var longitude = res.longitude;
							that.latitude = latitude;
							that.longitude = longitude;
							var storelist = that.storelist;
							for (var x in storelist) {
								if (latitude && longitude && storelist[x].latitude && storelist[x].longitude) {
									var juli = that.getDistance(latitude, longitude,storelist[x].latitude, storelist[x].longitude);
									storelist[x].juli = juli;
								}
							}
							storelist.sort(function(a, b) {
								return a["juli"] - b["juli"];
							});
							for (var x in storelist) {
								if (storelist[x].juli) {
									storelist[x].juli = '距离'+storelist[x].juli + '千米';
								}
							}
							that.storelist = storelist;
						});
					}
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
		set_hexiao_code_member:function(e){
			this.hexiao_code_member = e.detail.value;
		},
		hexiao: function () {
			let that = this;
			
			that.loading = true;
			app.post('ApiOrder/hexiao', {orderid: that.opt.id,hexiao_code_member:that.hexiao_code_member}, function (res) {
				that.loading = false;
				if(res.status != 1){
					app.error(res.msg);return;
				}
				app.success(res.msg);
				that.closeHxqr();
				setTimeout(function () {
				  that.getdata();
				}, 1000);
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
	//查看评测状态和跳转链接
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

    orderCollect: function (e) {
      var that = this;
      var orderid = e.currentTarget.dataset.id;
      app.confirm('确定要收货吗?', function () {
				app.showLoading('收货中');
				if(app.globalData.platform == 'wx' && that.detail.wxpaylog && that.detail.wxpaylog.is_upload_shipping_info == 1){
					// #ifdef MP-WEIXIN
					app.post('ApiOrder/orderCollectBefore', {orderid: orderid}, function (data) {
						app.showLoading(false);
						if(data.status != 1){app.error(data.msg);return;}
						else{
							if (wx.openBusinessView) {
							  wx.openBusinessView({
							    businessType: 'weappOrderConfirm',
							    extraData: {
							      merchant_id: that.detail.wxpaylog.mch_id,
							      merchant_trade_no: that.detail.wxpaylog.ordernum,
							      transaction_id: that.detail.wxpaylog.transaction_id
							    },
							    success(res) {
							      //dosomething
										console.log('openBusinessView success')
										console.log(res)
										app.post('ApiOrder/orderCollect', {orderid: orderid}, function (data2) {
											app.showLoading(false);
											app.success(data2.msg);
											setTimeout(function () {
												that.getdata();
											}, 1000);
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
						app.success(data.msg);
						setTimeout(function () {
							that.getdata();
						}, 1000);
					});
				}
        
      });
    },
		showhxqr:function(e){
			this.hexiao_qr = e.currentTarget.dataset.hexiao_qr
			this.$refs.dialogHxqr.open();
		},
		closeHxqr:function(){
			this.$refs.dialogHxqr.close();
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
		openLocation:function(e){
			var latitude = parseFloat(e.currentTarget.dataset.latitude);
			var longitude = parseFloat(e.currentTarget.dataset.longitude);
			var address = e.currentTarget.dataset.address;
			uni.openLocation({
			 latitude:latitude,
			 longitude:longitude,
			 name:address,
			 address:address,
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
		doStoreShowAll:function(){
			this.storeshowall = true;
		},
		handleLinkClick(url) {
			//是否包含http或https
			const regex = /^(?:http(s)?\:)?\/\//;
			if (!regex.test(url)) {
			  url = `http://${url}`;
			}
			app.goto(url);
		},
		showproductxieyi:function(xieyi_index){
			var that = this;
			var product_xieyi = that.product_xieyi;
			that.proxieyi_content = product_xieyi[xieyi_index] ? product_xieyi[xieyi_index].content : '';
			that.showproxieyi = 1;
		},
		hideproxieyi:function(){
			this.showproxieyi = 0;
		},
    giveordersharemp:function(){
      if(app.globalData.platform == 'mp'){
        var msg = '复制链接成功，或点击右上角发送给好友';
      }else{
        var msg = '复制成功,快去分享吧';
      }
    	let that = this;
    	let shareLink = app.globalData.pre_url +'/h5/'+app.globalData.aid+'.html#/pagesC/shop/takegiveorder?scene=pid_' + app.globalData.mid+'&payorderid='+that.detail.payorderid+'&title=领礼物：'+that.detail.giveordertitle;
    	uni.setClipboardData({
    		data: shareLink,
    		success: function() {
    			uni.showToast({
    				title: msg,
    				duration: 3000,
    				icon: 'none'
    			});
    		},
    		fail: function(err) {
    			uni.showToast({
    				title: '复制失败',
    				duration: 2000,
    				icon: 'none'
    			});
    		}
    	});
    },
    giveordershareapp:function(){
      var that = this;
    	uni.showActionSheet({
        itemList: ['发送给微信好友', '分享到微信朋友圈'],
        success: function (res){
    			if(res.tapIndex >= 0){
    				var scene = 'WXSceneSession';
    				if (res.tapIndex == 1) {
    					scene = 'WXSenceTimeline';
    				}
    				var sharedata = {};
    				sharedata.provider = 'weixin';
    				sharedata.type = 0;
    				sharedata.scene = scene;
    				sharedata.title   = that.detail.giveordertitle;
    				sharedata.summary = '';
    				sharedata.href    = app.globalData.pre_url +'/h5/'+app.globalData.aid+'.html#/pagesC/shop/takegiveorder?scene=pid_' + app.globalData.mid+'&payorderid='+that.detail.payorderid+'&title=领礼物：'+that.detail.giveordertitle;
    				sharedata.imageUrl= that.detail.giveorderpic;
    				var sharelist = app.globalData.initdata.sharelist;
    				if(sharelist){
    					for(var i=0;i<sharelist.length;i++){
    						if(sharelist[i]['indexurl'] == app.globalData.initdata.indexurl){
    							sharedata.title = sharelist[i].title;
    							sharedata.summary = sharelist[i].desc;
    							sharedata.imageUrl = sharelist[i].pic;
    							if(sharelist[i].url){
    								var sharelink = sharelist[i].url;
    								if(sharelink.indexOf('/') === 0){
    									sharelink = app.globalData.pre_url +'/h5/'+app.globalData.aid+'.html#'+ sharelink;
    								}
    								if(app.globalData.mid>0){
    									 sharelink += (sharelink.indexOf('?') === -1 ? '?' : '&') + 'pid='+app.globalData.mid;
    								}
    								sharedata.href = sharelink;
    							}
    						}
    					}
    				}
    				uni.share(sharedata);
    			}
        }
      });
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
    }
  },
	computed:{
		contentWithLinks() {
			// 使用正则表达式匹配链接
			const urlRegex = /https?:\/\/[^\s]+|www\.[^\s]+/gi;
			let match;
			let lastIndex = 0;
			const result = [];
			if(app.isNull(this.detail_content)){
				return this.detail_content;
			}
			if(this.detail_content){
				// 遍历所有匹配的链接
				while ((match = urlRegex.exec(this.detail_content)) !== null) {
					// 添加文本部分
					if (match.index > lastIndex) {
						result.push({ text: this.detail_content.substring(lastIndex, match.index) });
					}
					// 添加链接部分
					result.push({ text: match[0], url: match[0] });
					lastIndex = match.index + match[0].length;
				}
				
				// 添加剩余的文本部分
				if (lastIndex < this.detail_content.length) {
					result.push({ text: this.detail_content.substring(lastIndex) });
				}
			}

			return result;
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
.product .box{width: 100%; padding:16rpx 0px;border-bottom: 1px #e5e5e5 dashed;}
.product .content{display:flex;position:relative;}
.product .box:last-child{ border-bottom: 0; }
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
.orderinfo .title {font-weight: bold; line-height: 60rpx;}
.orderinfo .item{display:flex;width:100%;padding:20rpx 0;border-bottom:1px dashed #ededed;overflow:hidden}
.orderinfo .item:last-child{ border-bottom: 0;}
.orderinfo .item .t1{width:120rpx;flex-shrink:0; text-align:justify;text-align-last:justify;}

.orderinfo .item .t2{flex:1;text-align:right}
.orderinfo .item .t3{ margin-top: 3rpx;}
.orderinfo .item .red{color:red}
.order-info-title{align-items: center;}
.btn-class{height:45rpx;line-height:45rpx;color:#333;background:#fff;border:1px solid #cdcdcd;border-radius:3px;text-align:center;padding: 0 15rpx;flex-shrink: 0;margin: 0 0 0 10rpx;font-size:24rpx;}
.ordernum-info{align-items: center;}
.bottom{ width: 100%; height:calc(92rpx + env(safe-area-inset-bottom));background: #fff; position: fixed; bottom: 0px;left: 0px;display:flex;justify-content:flex-end;align-items:center;padding: 0 15rpx;}

.btn { border-radius: 10rpx;color: #fff;}
.btn1{height:60rpx;line-height:60rpx;color:#fff;border-radius:3px;text-align:center;flex-shrink: 0;margin: 0 0 0 15rpx;padding: 0 15rpx;}
.btn2{height:60rpx;line-height:60rpx;color:#333;background:#fff;border:1px solid #cdcdcd;border-radius:3px;text-align:center;padding: 0 15rpx;flex-shrink: 0;margin: 0 0 0 15rpx;}
.btn3{font-size:24rpx;height:50rpx;line-height:50rpx;color:#333;background:#fff;border:1px solid #cdcdcd;border-radius:3px;text-align:center;padding: 0 15rpx;flex-shrink: 0;margin: 0 0 0 15rpx;}

.btitle{ width:100%;height:100rpx;background:#fff;padding:0 20rpx;border-bottom:1px solid #f5f5f5}
.btitle .comment{border: 1px #ffc702 solid;border-radius:10rpx;background:#fff; color: #ffc702;  padding: 0 10rpx; height: 46rpx; line-height: 46rpx;}
.btitle .comment2{border: 1px #ffc7c0 solid;border-radius:10rpx;background:#fff; color: #ffc7c0;  padding: 0 10rpx; height: 46rpx; line-height: 46rpx;}

.hxqrbox{background:#fff;padding:50rpx;position:relative;border-radius:20rpx}
.hxqrbox .img{width:400rpx;height:400rpx}
.hxqrbox .txt{color:#666;margin-top:20rpx;font-size:26rpx;text-align:center}
.hxqrbox .close{width:50rpx;height:50rpx;position:absolute;bottom:-100rpx;left:50%;margin-left:-25rpx;border:1px solid rgba(255,255,255,0.5);border-radius:50%;padding:8rpx}

.glassitem{background:#f5f5f5;display: flex;align-items: center;padding: 10rpx 0;font-size: 24rpx;}
.glassitem .gcontent{flex:1;padding: 0 20rpx;}
.glassheader{line-height: 50rpx;font-size: 26rpx;font-weight: 600;}
.glassrow{line-height: 40rpx;font-size: 26rpx;}
.glassrow .glasscol{min-width: 25%;text-align: center;}
.glassitem .bt{border-top:1px solid #e3e3e3}

.pstime-item{display:flex;border-bottom: 1px solid #f5f5f5;padding:20rpx 30rpx;}
.pstime-item .radio{flex-shrink:0;width: 32rpx;height: 32rpx;background: #FFFFFF;border: 2rpx solid #BFBFBF;border-radius: 50%;margin-right:30rpx}
.pstime-item .radio .radio-img{width:100%;height:100%}
.pdl10{padding-left: 10rpx;}

.radio-item {display: flex;width: 100%;color: #000;align-items: center;background: #fff;padding:20rpx 20rpx;border-bottom:1px dotted #f1f1f1}
.radio-item:last-child {border: 0}
.radio-item .f1 {color: #333;font-size:30rpx;flex: 1}
.storeviewmore{width:100%;text-align:center;color:#889;height:40rpx;line-height:40rpx;margin-top:10rpx}
.refundtips{background: #fff9ed; color: #ff5c5c;}
.refundtips textarea{font-size: 24rpx;line-height: 40rpx;width: 100%;height: auto; word-wrap : break-word;}

.layui-imgbox{margin-right:16rpx;margin-bottom:10rpx;font-size:24rpx;position: relative;}
.layui-imgbox-img{display: block;width:200rpx;height:200rpx;padding:2px;border: #d3d3d3 1px solid;background-color: #f6f6f6;overflow:hidden}
.layui-imgbox-img>image{max-width:100%;}
.layui-imgbox-repeat{position: absolute;display: block;width:32rpx;height:32rpx;line-height:28rpx;right: 2px;bottom:2px;color:#999;font-size:30rpx;background:#fff}
.xieyibox{width:100%;height:100%;position:fixed;top:0;left:0;z-index:99;background:rgba(0,0,0,0.7)}
.xieyibox-content{width:90%;margin:0 auto;/*  #ifdef  MP-TOUTIAO */height:60%;/*  #endif  *//*  #ifndef  MP-TOUTIAO */height:80%;/*  #endif  */margin-top:20%;background:#fff;color:#333;padding:5px 10px 50px 10px;position:relative;border-radius:2px;}

.clist-item{display:flex;border-bottom: 1px solid #f5f5f5;padding:20rpx 30rpx;}
</style>