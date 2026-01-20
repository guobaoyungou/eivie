


<template>
<view>
	<block v-if="isload">
	<view class="banner" :style="{background:'url('+pre_url+'/static/img/topbg.png)',backgroundSize:'100%'}">
			<image :src="set.logo" background-size="cover" @tap="goto" :data-url="uinfo.bid==0 ? '/pages/index/index' : '/pagesExt/business/index?id='+uinfo.bid"/>
			<view class="info">
				 <text class="nickname">{{set.name}}</text>
				 <text>{{uinfo.un}}</text>
				 <view class="recharge" v-if="showrecharge" @tap="goto" data-url="recharge">充值</view>
			</view>
			<view class="set" @tap="saoyisao">
				<image src="/static/img/ico-scan.png"></image>
			</view>
	</view>
	
	<view class="contentdata">
		<view class="flex" v-if="inArray('member_code_buy',auth_data.hexiao_auth_data)">
			<button class="btn" @tap="goto" data-url="../member/code">会员消费</button>
			<button class="btn" @tap="saoyisao">商品核销</button>
		</view>
		
		<block v-if="auth_data.order">
		<view class="order"  v-if="showshoporder">
			<view class="head">
				<text class="f1">商城订单</text>
				<view class="f2" @tap="goto" data-url="../order/shoporder"><text>查看全部订单</text><image src="/static/img/arrowright.png"></image></view>
			</view>
			<view class="content">
				 <view class="item" @tap="goto" data-url="../order/shoporder?st=0">
						<image :src="pre_url+'/static/img/admin/order1.png'"></image>
						<text class="t3">待付款({{count0}})</text>
				 </view>
				 <view class="item" @tap="goto" data-url="../order/shoporder?st=1">
						<image :src="pre_url+'/static/img/admin/order2.png'"></image>
						<text class="t3">待发货({{count1}})</text>
				 </view>
				 <view class="item" @tap="goto" data-url="../order/shoporder?st=2">
						<image :src="pre_url+'/static/img/admin/order3.png'"></image>
						<text class="t3">待收货({{count2}})</text>
				 </view>
				 <view class="item" @tap="goto" data-url="../order/shopRefundOrder">
						<image :src="pre_url+'/static/img/admin/order4.png'"></image>
						<text class="t3">退款/售后({{count4}})</text>
				 </view>
			</view>
		</view>
		<view class="list">
			<view class="item" @tap="goto" data-url="../order/collageorder" v-if="showcollageorder">
				<view class="f2">拼团订单</view>
				<text class="f3">{{collageCount}}</text>
				<image src="/static/img/arrowright.png" class="f4"></image>
			</view>
			<view class="item" @tap="goto" data-url="../order/kanjiaorder" v-if="showkanjiaorder">
				<view class="f2">砍价订单</view>
				<text class="f3">{{kanjiaCount}}</text>
				<image src="/static/img/arrowright.png" class="f4"></image>
			</view>
			<view class="item" @tap="goto" data-url="../order/seckillorder" v-if="showseckillorder">
				<view class="f2">秒杀订单</view>
				<text class="f3">{{seckillCount}}</text>
				<image src="/static/img/arrowright.png" class="f4"></image>
			</view>
			<view class="item" @tap="goto" data-url="../order/tuangouorder" v-if="showtuangouorder">
				<view class="f2">团购订单</view>
				<text class="f3">{{tuangouCount}}</text>
				<image src="/static/img/arrowright.png" class="f4"></image>
			</view>
			<view class="item" @tap="goto" data-url="../order/scoreshoporder" v-if="showscoreshoporder">
				<view class="f2">{{t('积分')}}商城订单</view>
				<text class="f3">{{scoreshopCount}}</text>
				<image src="/static/img/arrowright.png" class="f4"></image>
			</view>
			<view class="item" @tap="goto" data-url="../order/luckycollageorder" v-if="showluckycollageorder">
				<view class="f2">幸运拼团订单</view>
				<text class="f3">{{luckycollageCount}}</text>
				<image src="/static/img/arrowright.png" class="f4"></image>
			</view>
			<view class="item" @tap="goto" data-url="../order/yuyueorder" v-if="showyuyueorder">
				<view class="f2">预约订单</view>
				<text class="f3">{{yuyueorderCount}}</text>
				<image src="/static/img/arrowright.png" class="f4"></image>
			</view>
			<view class="item" @tap="goto" data-url="../order/yuekeorder" v-if="showyuekeorder">
				<view class="f2">约课记录</view>
				<text class="f3"></text>
				<image src="/static/img/arrowright.png" class="f4"></image>
			</view>
			<view class="item" @tap="goto" data-url="../order/cycleorder" v-if="showCycleorder">
				<view class="f2">周期购订单</view>
				<text class="f3">{{cycleCount}}</text>
				<image src="/static/img/arrowright.png" class="f4"></image>
			</view>
			<view class="item" @tap="goto" data-url="../order/maidanlog" v-if="showmaidanlog">
				<view class="f2">买单记录</view>
				<text class="f3">{{maidanCount}}</text>
				<image src="/static/img/arrowright.png" class="f4"></image>
			</view>
			<view class="item" @tap="goto" data-url="../form/formlog" v-if="showformlog">
				<view class="f2">表单提交记录</view>
				<text class="f3">{{formlogCount}}</text>
				<image src="/static/img/arrowright.png" class="f4"></image>
			</view>
			<view class="item" @tap="goto" data-url="/pagesA/searchmember/searchmember" v-if="searchmember">
				<view class="f2">一键查看</view>
				<text class="f3"></text>
				<image src="/static/img/arrowright.png" class="f4"></image>
			</view>
			
		</view>
		</block>
		
			<view class="list"  v-if="showworkorder">
				<view class="item" @tap="goto" data-url="../workorder/category">
					<view class="f2">工单记录</view>
					<text class="f3">{{workorderCount1}}</text>
					<image src="/static/img/arrowright.png" class="f4"></image>
				</view>
			</view>
			<!-- 量表记录 -->
			<view class="list"  v-if="custom.showHealth">
				<view class="item" @tap="goto" data-url="/admin/health/record">
					<view class="f2">量表填写记录</view>
					<text class="f3"></text>
					<image src="/static/img/arrowright.png" class="f4"></image>
				</view>
			</view>

		<block v-if="auth_data.product">
		<view class="list">
			<view class="item" @tap="goto" data-url="../product/index">
				<view class="f2">商品列表</view>
				<text class="f3">{{productCount}}</text>
				<image src="/static/img/arrowright.png" class="f4"></image>
			</view>
			<view class="item" @tap="goto" data-url="../product/edit">
				<view class="f2">添加商品</view>
				<text class="f3"></text>
				<image src="/static/img/arrowright.png" class="f4"></image>
			</view>
		</view>
		<view class="list" v-if="scoreshop_product">
			<view class="item" @tap="goto" data-url="../scoreproduct/index">
				<view class="f2">兑换商品列表</view>
				<text class="f3">{{scoreproductCount}}</text>
				<image src="/static/img/arrowright.png" class="f4"></image>
			</view>
			<view class="item" @tap="goto" data-url="../scoreproduct/edit">
				<view class="f2">添加兑换商品</view>
				<text class="f3"></text>
				<image src="/static/img/arrowright.png" class="f4"></image>
			</view>
		</view>

		</block>
		
		<block v-if="auth_data.restaurant_product || auth_data.restaurant_table || auth_data.restaurant_tableWaiter">
			<view class="list">
				<view v-if="auth_data.restaurant_product" class="item" @tap="goto" data-url="../restaurant/product/index">
					<view class="f2">菜品列表</view>
					<text class="f3"></text>
					<image src="/static/img/arrowright.png" class="f4"></image>
				</view>
				<view v-if="auth_data.restaurant_product" class="item" @tap="goto" data-url="../restaurant/product/edit">
					<view class="f2">添加菜品</view>
					<text class="f3"></text>
					<image src="/static/img/arrowright.png" class="f4"></image>
				</view>
				<view v-if="auth_data.restaurant_product" class="item" @tap="goto" data-url="../restaurant/category/index">
					<view class="f2">菜品分类</view>
					<text class="f3"></text>
					<image src="/static/img/arrowright.png" class="f4"></image>
				</view>
				<view v-if="auth_data.restaurant_product" class="item" @tap="goto" data-url="../restaurant/category/edit">
					<view class="f2">添加分类</view>
					<text class="f3"></text>
					<image src="/static/img/arrowright.png" class="f4"></image>
				</view>
				<view v-if="auth_data.restaurant_table" class="item" @tap="goto" data-url="../restaurant/tableCategory">
					<view class="f2">餐桌分类</view>
					<text class="f3"></text>
					<image src="/static/img/arrowright.png" class="f4"></image>
				</view>
				<view v-if="auth_data.restaurant_table" class="item" @tap="goto" data-url="../restaurant/table">
					<view class="f2">餐桌编辑</view>
					<text class="f3"></text>
					<image src="/static/img/arrowright.png" class="f4"></image>
				</view>
				<view v-if="auth_data.restaurant_tableWaiter" class="item" @tap="goto" data-url="../restaurant/tableWaiter">
					<view class="f2">餐桌管理</view>
					<text class="f3"></text>
					<image src="/static/img/arrowright.png" class="f4"></image>
				</view>
			</view>
		</block>
		<block v-if="auth_data.restaurant_takeaway || auth_data.restaurant_shop || auth_data.restaurant_booking || auth_data.restaurant_deposit || auth_data.restaurant_queue">
			<view class="list">
				<view v-if="auth_data.restaurant_takeaway" class="item" @tap="goto" data-url="../restaurant/takeawayorder">
					<view class="f2">外卖订单</view>
					<text class="f3"></text>
					<image src="/static/img/arrowright.png" class="f4"></image>
				</view>
				<view v-if="auth_data.restaurant_shop" class="item" @tap="goto" data-url="../restaurant/shoporder">
					<view class="f2">点餐订单</view>
					<text class="f3"></text>
					<image src="/static/img/arrowright.png" class="f4"></image>
				</view>
				<view v-if="auth_data.restaurant_booking" class="item" @tap="goto" data-url="../restaurant/bookingorder">
					<view class="f2">预定订单</view>
					<text class="f3"></text>
					<image src="/static/img/arrowright.png" class="f4"></image>
				</view>
				<view v-if="auth_data.restaurant_booking" class="item" @tap="goto" data-url="../restaurant/booking">
					<view class="f2">添加预定</view>
					<text class="f3"></text>
					<image src="/static/img/arrowright.png" class="f4"></image>
				</view>
				<view v-if="auth_data.restaurant_deposit" class="item" @tap="goto" data-url="../restaurant/depositorder">
					<view class="f2">寄存订单</view>
					<text class="f3"></text>
					<image src="/static/img/arrowright.png" class="f4"></image>
				</view>
				<view v-if="auth_data.restaurant_queue" class="item" @tap="goto" data-url="../restaurant/queue">
					<view class="f2">排队叫号</view>
					<text class="f3"></text>
					<image src="/static/img/arrowright.png" class="f4"></image>
				</view>
				<view v-if="auth_data.restaurant_queue" class="item" @tap="goto" data-url="../restaurant/queueCategory">
					<view class="f2">排队管理</view>
					<text class="f3"></text>
					<image src="/static/img/arrowright.png" class="f4"></image>
				</view>
			</view>
		</block>

		<view class="list">
			<view class="item" @tap="goto" data-url="../hexiao/record">
				<view class="f2">我的核销</view>
				<text class="f3">{{hexiaoCount}}</text>
				<image src="/static/img/arrowright.png" class="f4"></image>
			</view>
			<view class="item" v-if="inArray('member_code_buy',auth_data.hexiao_auth_data)" @tap="goto" data-url="../member/code">
				<view class="f2">会员消费</view>
				<text class="f3"></text>
				<image src="/static/img/arrowright.png" class="f4"></image>
			</view>
		</view>

		<view class="list">
			<view v-if="uinfo.tmpl_orderconfirm_show==1">
				<view class="item">
					<view class="f2">订单提交通知</view>
					<view class="f3"><switch value="1" :checked="uinfo.tmpl_orderconfirm==1?true:false" @change="switchchange" data-type="tmpl_orderconfirm"></switch></view>
				</view>
				<!--  #ifdef MP-WEIXIN -->
				<view style="color:#999;font-size:24rpx;margin-bottom:10rpx" @tap="addsubnum" :data-tmplid="wxtmplset.tmpl_orderconfirm">剩余可接收次数：<text style="color:#FC5648;font-weight:bold;font-size:30rpx">{{uinfo.tmpl_orderconfirmNum}}</text>，每点击此处一次可增加一次机会</view>
				<!--  #endif -->
			</view>
			<view v-if="uinfo.tmpl_orderpay_show==1">
				<view class="item">
					<view class="f2">订单支付通知</view>
					<view class="f3"><switch value="1" :checked="uinfo.tmpl_orderpay==1?true:false" @change="switchchange" data-type="tmpl_orderpay"></switch></view>
				</view>
				<!--  #ifdef MP-WEIXIN -->
				<view style="color:#999;font-size:24rpx;margin-bottom:10rpx" @tap="addsubnum" :data-tmplid="wxtmplset.tmpl_orderconfirm">剩余可接收次数：<text style="color:#FC5648;font-weight:bold;font-size:30rpx">{{uinfo.tmpl_orderconfirmNum}}</text>，每点击此处一次可增加一次机会</view>
				<!--  #endif -->
			</view>
			<view v-if="uinfo.tmpl_ordershouhuo_show==1">
				<view class="item">
					<view class="f2">订单收货通知</view>
					<view class="f3"><switch value="1" :checked="uinfo.tmpl_ordershouhuo==1?true:false" @change="switchchange" data-type="tmpl_ordershouhuo"></switch></view>
				</view>
				<!--  #ifdef MP-WEIXIN -->
				<view style="color:#999;font-size:24rpx;margin-bottom:10rpx" @tap="addsubnum" :data-tmplid="wxtmplset.tmpl_ordershouhuo">剩余可接收次数：<text style="color:#FC5648;font-weight:bold;font-size:30rpx">{{uinfo.tmpl_ordershouhuoNum}}</text>，每点击此处一次可增加一次机会</view>
				<!--  #endif -->
			</view>
			<view v-if="uinfo.tmpl_ordertui_show==1">
				<view class="item">
					<view class="f2">退款申请通知</view>
					<view class="f3"><switch value="1" :checked="uinfo.tmpl_ordertui==1?true:false" @change="switchchange" data-type="tmpl_ordertui"></switch></view>
				</view>
				<!--  #ifdef MP-WEIXIN -->
				<view style="color:#999;font-size:24rpx;margin-bottom:10rpx" @tap="addsubnum" :data-tmplid="wxtmplset.tmpl_ordertui">剩余可接收次数：<text style="color:#FC5648;font-weight:bold;font-size:30rpx">{{uinfo.tmpl_ordertuiNum}}</text>，每点击此处一次可增加一次机会</view>
				<!--  #endif -->
			</view>
			<view v-if="uinfo.tmpl_withdraw_show==1">
				<view class="item">
					<view class="f2">提现申请通知</view>
					<view class="f3"><switch value="1" :checked="uinfo.tmpl_withdraw==1?true:false" @change="switchchange" data-type="tmpl_withdraw"></switch></view>
				</view>
				<!--  #ifdef MP-WEIXIN -->
				<view style="color:#999;font-size:24rpx;margin-bottom:10rpx" @tap="addsubnum" :data-tmplid="wxtmplset.tmpl_withdraw">剩余可接收次数：<text style="color:#FC5648;font-weight:bold;font-size:30rpx">{{uinfo.tmpl_withdrawNum}}</text>，每点击此处一次可增加一次机会</view>
				<!--  #endif -->
			</view>
			<view class="item" v-if="uinfo.tmpl_formsub_show==1">
				<view class="f2">表单提交通知</view>
				<view class="f3"><switch value="1" :checked="uinfo.tmpl_formsub==1?true:false" @change="switchchange" data-type="tmpl_formsub"></switch></view>
			</view>
			<view v-if="uinfo.tmpl_kehuzixun_show==1">
				<view class="item">
					<view class="f2">用户咨询通知</view>
					<view class="f3"><switch value="1" :checked="uinfo.tmpl_kehuzixun==1?true:false" @change="switchchange" data-type="tmpl_kehuzixun"></switch></view>
				</view>
				<!--  #ifdef MP-WEIXIN -->
				<view style="color:#999;font-size:24rpx;margin-bottom:30rpx" @tap="addsubnum" :data-tmplid="wxtmplset.tmpl_kehuzixun">剩余可接收次数：<text style="color:#FC5648;font-weight:bold;font-size:30rpx">{{uinfo.tmpl_kehuzixunNum}}</text>，每点击此处一次可增加一次机会</view>
				<!--  #endif -->
			</view>
		</view>
		<view class="list">
			<view v-if="showbusinessqr" class="item" @tap="goto" data-url="businessqr">
				<view class="f2">推广码</view>
				<text class="f3"></text>
				<image src="/static/img/arrowright.png" class="f4"></image>
			</view>
			<view v-if="showbusinessqr" class="item" @tap="goto" data-url="businessqr">
				<view class="f2">推广码</view>
				<text class="f3"></text>
				<image src="/static/img/arrowright.png" class="f4"></image>
			</view>
			<view class="item" @tap="goto" :data-url="'/pagesA/workorder/category?bid='+uinfo.bid" v-if="showworkadd" >
				<view class="f2">工单提交</view>
				<text class="f3"></text>
				<image src="/static/img/arrowright.png" class="f4"></image>
			</view>
			<view class="item" v-if="uinfo.bid>0">
				<view class="f2">店铺休息</view>
				<view class="f3"><switch value="1" :checked="set.is_open==0?true:false" @change="switchOpen" data-type="is_open"></switch></view>
			</view>
			<view v-if="uinfo.bid>0" style="color:#999;font-size:24rpx;margin-bottom:30rpx" >休息时不接单</view>
			<view class="item" @tap="goto" data-url="setinfo" v-if="uinfo.bid>0">
				<view class="f2">店铺设置</view>
				<text class="f3"></text>
				<image src="/static/img/arrowright.png" class="f4"></image>
			</view>
			<view class="item" @tap="goto" data-url="setpwd">
				<view class="f2">修改密码</view>
				<text class="f3"></text>
				<image src="/static/img/arrowright.png" class="f4"></image>
			</view>
			<view class="item" @tap="goto" data-url="../index/login">
				<view class="f2">切换账号</view>
				<text class="f3"></text>
				<image src="/static/img/arrowright.png" class="f4"></image>
			</view>
		</view>
	</view>

	<view class="tabbar">
		<view class="tabbar-bot"></view>
		<view class="tabbar-bar" style="background-color:#ffffff">
			<view @tap="goto" data-url="../member/index" data-opentype="reLaunch" class="tabbar-item" v-if="auth_data.member">
				<view class="tabbar-image-box">
					<image class="tabbar-icon" :src="pre_url+'/static/img/admin/member.png'"></image>
				</view>
				<view class="tabbar-text">{{t('会员')}}</view>
			</view>
			<view @tap="goto" data-url="../kefu/index" data-opentype="reLaunch" class="tabbar-item" v-if="auth_data.zixun">
				<view class="tabbar-image-box">
					<image class="tabbar-icon" :src="pre_url+'/static/img/admin/zixun.png'"></image>
				</view>
				<view class="tabbar-text">咨询</view>
			</view>
			<view @tap="goto" data-url="../finance/index" data-opentype="reLaunch" class="tabbar-item" v-if="auth_data.finance">
				<view class="tabbar-image-box">
					<image class="tabbar-icon" :src="pre_url+'/static/img/admin/finance.png'"></image>
				</view>
				<view class="tabbar-text">财务</view>
			</view>
			<view @tap="goto" data-url="../index/index" data-opentype="reLaunch" class="tabbar-item">
				<view class="tabbar-image-box">
					<image class="tabbar-icon" :src="pre_url+'/static/img/admin/my2.png'"></image>
				</view>
				<view class="tabbar-text active">我的</view>
			</view>
		</view>
	</view>
	</block>
	<popmsg ref="popmsg"></popmsg>
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
			
			set:{},
			uinfo:{},
      count0: 0,
      count1: 0,
      count2: 0,
      count3: 0,
      count4: 0,
      seckillCount: 0,
      collageCount: 0,
      kanjiaCount: 0,
			tuangouCount:0,
      scoreshopCount: 0,
      maidanCount: 0,
      productCount: 0,
			yuyueorderCount: 0,
			cycleCount: 0,
      hexiaoCount: 0,
			formlogCount:0,
      auth_data: {},
			luckycollageCount:0,
			showshoporder:false,
			showbusinessqr:false,
			showyuyueorder:false,
			showcollageorder:false,
			showkanjiaorder:false,
			showseckillorder:false,
			showscoreshoporder:false,
			showluckycollageorder:false,
			showtuangouorder:false,
			showyuekeorder:false,
			showmaidanlog:false,
			showformlog:false,
			showworkorder:false,
			showworkadd:false,
			workorderCount1:0,
			showrecharge:false,
			wxtmplset:{},
			searchmember:false,
			showCycleorder:false,
			scoreshop_product:false,
			scoreproductCount:0,
			custom:{},//定制显示控制放一起
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
		getdata:function(){
			var that = this;
			that.loading = true;
			app.get('ApiAdminIndex/index', {}, function (res) {
				that.loading = false;
				that.set = res.set;
				that.wxtmplset = res.wxtmplset;
				that.uinfo = res.uinfo;
				that.count0 = res.count0;
				that.count1 = res.count1;
				that.count2 = res.count2;
				that.count3 = res.count3;
				that.count4 = res.count4;
				that.seckillCount = res.seckillCount;
				that.collageCount = res.collageCount;
				that.cycleCount = res.cycleCount;
				that.luckycollageCount = res.luckycollageCount;
				that.kanjiaCount = res.kanjiaCount;
				that.tuangouCount = res.tuangouCount;
				that.scoreshopCount = res.scoreshopCount;
				that.yuyueCount = res.yuyueCount;
				that.maidanCount = res.maidanCount;
				that.productCount = res.productCount;
				that.hexiaoCount = res.hexiaoCount;
				that.formlogCount = res.formlogCount;
				that.auth_data = res.auth_data;
				that.showbusinessqr = res.showbusinessqr;
				that.yuyueorderCount = res.yuyueorderCount;
				that.workorderCount1 = res.workorderCount;
				that.showshoporder = res.showshoporder;
				that.showcollageorder = res.showcollageorder;
				that.showCycleorder = res.showCycleorder;
				that.showkanjiaorder = res.showkanjiaorder;
				that.showseckillorder = res.showseckillorder;
				that.showtuangouorder = res.showtuangouorder;
				that.showscoreshoporder = res.showscoreshoporder;
				that.showluckycollageorder = res.showluckycollageorder;
				that.showmaidanlog = res.showmaidanlog;
				that.showformlog = res.showformlog;
				that.showworkorder = res.showworkorder;
				that.showworkadd = res.showworkadd;
				that.showyuyueorder = res.showyuyueorder;
				that.showrecharge = res.showrecharge;
				that.searchmember = res.searchmember;
				that.showyuekeorder = res.showyuekeorder;
				that.scoreshop_product = res.scoreshop_product || false;
				that.scoreproductCount = res.scoreproductCount || 0;
				that.custom = res.custom
				that.loaded();
			});
		},
    switchchange: function (e) {
      console.log(e);
      var field = e.currentTarget.dataset.type;
      var value = e.detail.value ? 1 : 0;
      app.post('ApiAdminIndex/setusertmpl', {
        field: field,
        value: value
      }, function (data) {});
    },
		switchOpen: function (e) {
      var field = e.currentTarget.dataset.type;
      var value = e.detail.value ? 0 : 1;
      app.post('ApiAdminIndex/setField', {
        field: field,
        value: value
      }, function (data) {});
    },
    saoyisao: function (d) {
      var that = this;
			if(app.globalData.platform == 'h5'){
				app.alert('请使用微信扫一扫功能扫码核销');return;
			}else if(app.globalData.platform == 'mp'){
				var jweixin = require('jweixin-module');
				jweixin.ready(function () {   //需在用户可能点击分享按钮前就先调用
					jweixin.scanQRCode({
						needResult: 1, // 默认为0，扫描结果由微信处理，1则直接返回扫描结果，
						scanType: ["qrCode","barCode"], // 可以指定扫二维码还是一维码，默认二者都有
						success: function (res) {
							var content = res.resultStr; // 当needResult 为 1 时，扫码返回的结果
							var params = content.split('?')[1];
							app.goto('/admin/hexiao/hexiao?'+params);
							//if(content.length == 18 && (/^\d+$/.test(content))){ //是十八位数字 付款码
							//	location.href = "{:url('shoukuan')}/aid/{$aid}/auth_code/"+content
							//}else{
							//	location.href = content;
							//}
						}
					});
				});
			}else{
				uni.scanCode({
					success: function (res) {
						console.log(res);
						var content = res.result;
						var params = content.split('?')[1];
						app.goto('/admin/hexiao/hexiao?'+params);
					}
				});
			}
    },
		addsubnum:function(e){	
      var that = this;
			that.tmplids = [e.currentTarget.dataset.tmplid];
			console.log(that.tmplids);
			that.subscribeMessage(function () {
				that.getdata();
			});
    }
  }
};
</script>
<style>
@import "../common.css";
.banner{ display:flex;width:100%;height:352rpx;padding:40rpx 32rpx;color:#fff;position:relative}
.banner image{ width:120rpx;height:120rpx;border-radius:50%;margin-right:20rpx}
.banner .info{display:flex;flex:auto;flex-direction:column;padding-top:10rpx}
.banner .info .nickname{font-size:32rpx;font-weight:bold;padding-bottom:12rpx}
.banner .set{ width:70rpx;height:100rpx;line-height:100rpx;font-size:40rpx;text-align:center}
.banner .set image{width:50rpx;height:50rpx;border-radius:0}

.contentdata{display:flex;flex-direction:column;width:100%;padding:0 30rpx;margin-top:-160rpx;position:relative;margin-bottom:20rpx}

.order{width:100%;background:#fff;padding:0 20rpx;margin-top:20rpx;border-radius:16rpx}
.order .head{ display:flex;align-items:center;width:100%;padding:10rpx 0;border-bottom:0px solid #eee;}
.order .head .f1{flex:auto;color:#333}
.order .head .f2{ display:flex;align-items:center;color:#FC5648;width:200rpx;padding:10rpx 0;text-align:right;justify-content:flex-end}
.order .head .f2 image{ width:30rpx;height:30rpx;}
.order .head .t3{ width: 40rpx; height: 40rpx;}
.order .content{ display:flex;width:100%;padding:10rpx 0;align-items:center;font-size:24rpx}
.order .content .item{padding:10rpx 0;flex:1;display:flex;flex-direction:column;align-items:center;position:relative}
.order .content .item image{ width:50rpx;height:50rpx}
.order .content .item .t3{ padding-top:3px;color:#333}
.order .content .item .t2{background: red;color: #fff;border-radius:50%;padding: 0 10rpx;position: absolute;top: 0px;right:40rpx;width:34rpx;height:34rpx;text-align:center;}

.list{ width: 100%;background: #fff;margin-top:20rpx;padding:0 20rpx;font-size:30rpx;border-radius:16rpx}
.list .item{ height:100rpx;display:flex;align-items:center;border-bottom:0px solid #eee}
.list .item:last-child{border-bottom:0;}
.list .f1{width:50rpx;height:50rpx;line-height:50rpx;display:flex;align-items:center;}
.list .f1 image{ width:40rpx;height:40rpx;}
.list .f1 span{ width:40rpx;height:40rpx;font-size:40rpx}
.list .f2{color:#222}
.list .f3{ color: #FC5648;text-align:right;flex:1;}
.list .f4{ width: 24rpx; height: 24rpx;}

.recharge{ background: #fff; width: 100rpx;color: #FB6534; text-align: center; font-size: 20rpx; padding: 5rpx; border-radius: 10rpx; margin-top: 10rpx;}

.btn {
	line-height: 90rpx;
	text-align: center;
	background: #FFFFFF;
	font-size: 30rpx;
	color: #FF4015;
	padding: 0 60rpx;
	border-radius: 10rpx;
}

switch{transform:scale(.7);}
</style>