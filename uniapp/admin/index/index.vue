<template>
	<view class="view-width">
		<block v-if="isload">
			<!-- background:'url('+pre_url+'/static/img/admin/headbgimg.png)' -->
		<view class="head-class" :style="{background:'url('+set.bgimg+')',backgroundSize:'cover',backgroundRepeat:'no-repeat'}">
			<!-- #ifndef H5 -->
			<view :style="{height:(44+statusBarHeight)+'px'}"></view>
			<!-- #endif -->
			<view class="head-view flex-bt flex-y-center">
				<view class="avat-view">
					<view class="user-info flex-row" @tap="goto" :data-url="uinfo.bid==0 ? '/pages/index/index' : '/pagesExt/business/index?id='+uinfo.bid" >
						 <text class="nickname">{{set.name}}</text><text class="nickname" v-if="uinfo.bid > 0" style="font-weight: normal;">(ID:{{uinfo.bid}})</text>
					</view>
          <image class="imgback" :src="`${pre_url}/static/img/location/right-black.png`" ></image>
				</view>
				<view class="option-img-view">
					<view  style="margin-right: 28rpx;" class="setup-view" @tap="saoyisao"  v-if="auth_data.hexiao_auth_data">
						<image :src="`${pre_url}/static/img/admin/saoyisao.png`"></image>
					</view>
					<view class="setup-view" @tap="goto" data-url="setpage">
						<image class="setup-img":src="`${pre_url}/static/img/admin/setup.png`"></image>
					</view>
				</view>
			</view>
			<!-- 汇付数据统计 -->
			<block  v-if="inArray('business_data_tongji',auth_data.wxauth_data) &&   uinfo.bid > 0">
				<view class="timetab flex-x-center flex-bt">
					<view class="tab" @tap="timeTabChange('today')" :class="time_tab_index =='today'?'tab-on':'' ">今日</view>
					<view class="tab" @tap="timeTabChange('yesterday')" :class="time_tab_index =='yesterday'?'tab-on':'' ">昨日</view>
					<view class="tab" @tap="timeTabChange('month')" :class="time_tab_index =='month'?'tab-on':'' ">本月</view>
					<view class="tab" @tap="timeTabChange('total')" :class="time_tab_index =='total'?'tab-on':'' ">累计</view>
				</view>
				<view class="today-data flex-bt flex-y-center" >
					
					<view class="option-view flex-col flex-x-center">
							<view class="title-text flex flex-y-center flex-x-center" @click="showExplain" data-text="商城订单和买单订单的订单总和">订单量<image class="title-icon" :src="`${pre_url}/static/img/admin/jieshiicon.png`"></image></view>
						<view class="flex-y-center flex-x-center">
							<text class="unit-money">￥</text><text class="num-text">{{yingyedata.total_order_count || 0}}</text>
						</view>	
					</view>
					<view class="option-view flex-col flex-x-center">
							<view class="title-text flex flex-y-center flex-x-center" @click="showExplain" data-text="商家商品订单+买单订单总金额">营业额<image class="title-icon" :src="`${pre_url}/static/img/admin/jieshiicon.png`"></image></view>
						<view class="flex-y-center flex-x-center">
							<text class="unit-money">￥</text><text class="num-text">{{yingyedata.yingye_money || 0}}</text>
						</view>	
					</view>
					<view class="option-view flex-col flex-x-center">
							<view class="title-text flex flex-y-center flex-x-center" @click="showExplain" data-text="营业额的基础上，除去平台抽成后所获得的金额">商家收入<image class="title-icon" :src="`${pre_url}/static/img/admin/jieshiicon.png`"></image></view>
						<view class="flex-y-center flex-x-center">
							<text class="unit-money">￥</text><text class="num-text">{{yingyedata.business_income || 0.00}}</text>
						</view>	
					</view>
				</view>
			</block>
			<!-- 汇付数据统计 -->
			
			<view class="today-data flex-bt flex-y-center"  v-if="show_top_tongji">
				<view class="option-view flex-col flex-x-center">
					<view class="title-text flex flex-y-center flex-x-center" @click="explanation">今日收款<image class="title-icon" :src="`${pre_url}/static/img/admin/jieshiicon.png`"></image></view>
					<view class="flex-y-center flex-x-center">
						<text class="unit-money">￥</text><text class="num-text">{{today_money}}</text>
					</view>	
				</view>
				<view class="option-view flex-col flex-x-center">
					<text class="title-text">今日订单</text>
					<view class="flex-y-center flex-x-center">
						<text class="num-text">{{today_order_count}}</text>
					</view>	
				</view>
				<view class="option-view flex-col flex-x-center" v-if="inArray('show_hx_num',show_auth)">
					<text class="title-text">核销次数</text>
					<view class="flex-y-center flex-x-center">
						<text class="num-text">{{uinfo.hexiao_num}}</text>
					</view>	
				</view>
				<!-- <view class="option-view flex-col flex-x-center">
					<text class="title-text">今日访客数</text>
					<view class="flex-y-center flex-x-center">
						<text class="num-text">5555</text>
					</view>	
				</view> -->
			</view>
			
	
			<block v-if="auth_data.order">
			<view class="mall-orders flex-col width" v-if="showshoporder">
				<view class="order-title flex-bt">
					<view class="title-text flex-y-center"><image class="left-img" :src="`${pre_url}/static/img/admin/titletips.png`"></image>商城订单</view>
					<view class="all-text flex-y-center" @tap="goto" data-url="../order/shoporder">全部订单<image class="right-img" :src="`${pre_url}/static/img/admin/jiantou.png`"></image></view>
				</view>
				<view class="order-list flex-bt">
					<view class="option-order flex-col" @tap="goto" data-url="../order/shoporder?st=0">
						<text class="num-text">{{count0}}</text>
						<text class="title-text">待付款</text>
					</view>
					<view class="option-order flex-col" @tap="goto" data-url="../order/shoporder?st=1">
						<text class="num-text">{{count1}}</text>
						<text class="title-text">待发货</text>
					</view>
					<view class="option-order flex-col" @tap="goto" data-url="../order/shoporder?st=2">
						<text class="num-text">{{count2}}</text>
						<text class="title-text">待收货</text>
					</view>
					<view class="option-order flex-col" @tap="goto" data-url="../order/shopRefundOrder">
						<text class="num-text">{{count4}}</text>
						<text class="title-text">退款/售后</text>
					</view>
					<view class="option-order flex-col" @tap="goto" data-url="../order/shoporder?st=3">
						<text class="num-text">{{count3}}</text>
						<text class="title-text">已完成</text>
					</view>
				</view>
			</view>
		</block>
		<view class="meun-view flex-aw">
			<view class="meun-options flex-col flex-x-center" @tap="goto" data-url="../hexiao/record"  v-if="auth_data.hexiao_auth_data">
				<image :src="`${pre_url}/static/img/admin/menu1.png`" class="menu-img"></image>
				<text class="menu-text">核销记录</text>
			</view>
			<block v-if="auth_data.product">			
				<view class="meun-options flex-col flex-x-center" @tap="goto" data-url="../product/index">
					<image :src="`${pre_url}/static/img/admin/menu2.png`" class="menu-img"></image>
					<text class="menu-text">商品管理</text>
				</view>				
			</block>
			<view class="meun-options flex-col flex-x-center" @tap="goto" data-url="../index/setnotice" v-if="uinfo.shownotice">
				<image :src="`${pre_url}/static/img/admin/menu3.png`" class="menu-img"></image>
				<text class="menu-text">消息通知</text>
			</view>
			<view class="meun-options flex-col flex-x-center" @tap="goto" data-url="login">
				<image :src="`${pre_url}/static/img/admin/menu4.png`" class="menu-img"></image>
				<text class="menu-text">切换账号</text>
			</view>
			<view class="meun-options flex-col flex-x-center" @tap="goto" data-url="setpwd">
				<image :src="`${pre_url}/static/img/admin/menu5.png`" class="menu-img"></image>
				<text class="menu-text">修改密码</text>
			</view>
		</view>		
		</view>

		<block v-if="auth_data.restaurant_product || auth_data.restaurant_table || auth_data.restaurant_tableWaiter">
		<view class="menu-manage flex-col">
			<view class="menu-title">菜品管理</view>
			<view class="menu-list width">
					<view class="meun-list-options flex-col flex-x-center flex-y-center" @tap="goto" data-url="../restaurant/product/edit" v-if="auth_data.restaurant_product">
						<image :src="`${pre_url}/static/img/admin/dishm1.png`" class="menu-img"></image>
						<text class="menu-text">添加菜品</text>
					</view>
					<view class="meun-list-options flex-col flex-x-center flex-y-center" @tap="goto" data-url="../restaurant/product/index" v-if="auth_data.restaurant_product" >
						<image :src="`${pre_url}/static/img/admin/dishm2.png`" class="menu-img"></image>
						<text class="menu-text">菜品列表</text>
					</view>
					<view class="meun-list-options flex-col flex-x-center flex-y-center" @tap="goto" data-url="../restaurant/category/index" v-if="auth_data.restaurant_product">
						<image :src="`${pre_url}/static/img/admin/dishm6.png`" class="menu-img"></image>
						<text class="menu-text">菜品分类</text>
					</view>
					<view class="meun-list-options flex-col flex-x-center flex-y-center" @tap="goto" data-url="../restaurant/category/edit" v-if="auth_data.restaurant_product">
						<image :src="`${pre_url}/static/img/admin/dishm4.png`"class="menu-img"></image>
						<text class="menu-text">添加分类</text>
					</view>
					<view class="meun-list-options flex-col flex-x-center flex-y-center" @tap="goto" data-url="../restaurant/table" v-if="auth_data.restaurant_table">
						<image :src="`${pre_url}/static/img/admin/dishm5.png`" class="menu-img"></image>
						<text class="menu-text">餐桌编辑</text>
					</view>
					<view class="meun-list-options flex-col flex-x-center flex-y-center" @tap="goto" data-url="../restaurant/tableCategory" v-if="auth_data.restaurant_table">
						<image :src="`${pre_url}/static/img/admin/dishm6.png`" class="menu-img"></image>
						<text class="menu-text">餐桌分类</text>
					</view>
					<view class="meun-list-options flex-col flex-x-center flex-y-center" @tap="goto" data-url="../restaurant/tableWaiter" v-if="auth_data.restaurant_tableWaiter">
						<image :src="`${pre_url}/static/img/admin/dishm3.png`" class="menu-img"></image>
						<text class="menu-text">点餐清台</text>
					</view>
			</view>
		</view>
		</block>

		<block v-if="custom.mendian_upgrade && uinfo.bid == 0">
			<view class="menu-manage flex-col">
				<view class="menu-title">{{t('门店')}}管理</view>
				<view class="menu-list width">
					<block>
						<view class="meun-list-options flex-col flex-x-center flex-y-center"  @tap="goto" data-url="/adminExt/mendian/list">
							<image :src="`${pre_url}/static/img/admin/dismendian.png`" class="menu-img"></image>
							<text class="menu-text">{{t('门店')}}列表</text>
						</view>
						<view class="meun-list-options flex-col flex-x-center flex-y-center"  @tap="goto" data-url="/adminExt/mendian/withdrawlog" >
							<image :src="`${pre_url}/static/img/admin/dishm8.png`" class="menu-img"></image>
							<text class="menu-text">{{t('门店')}}佣金提现</text>
						</view>
					</block>
				</view>
			</view>
		</block>
		
		<block v-if="auth_data.restaurant_takeaway || auth_data.restaurant_shop || auth_data.restaurant_booking || auth_data.restaurant_deposit || auth_data.restaurant_queue">
			<view class="menu-manage flex-col">
				<view class="menu-title">外卖管理</view>
				<view class="menu-list width">
					<block>
						<view class="meun-list-options flex-col flex-x-center flex-y-center"  @tap="goto" data-url="../restaurant/takeawayorder" v-if="auth_data.restaurant_takeaway">
							<image :src="`${pre_url}/static/img/admin/dishm7.png`" class="menu-img"></image>
							<text class="menu-text">外卖订单</text>
						</view>
						<view class="meun-list-options flex-col flex-x-center flex-y-center"  @tap="goto" data-url="../restaurant/shoporder" v-if="auth_data.restaurant_shop">
							<image :src="`${pre_url}/static/img/admin/dishm8.png`" class="menu-img"></image>
							<text class="menu-text">点餐订单</text>
						</view>
						<view class="meun-list-options flex-col flex-x-center flex-y-center"  @tap="goto" data-url="../restaurant/bookingorder" v-if="auth_data.restaurant_booking">
							<image :src="`${pre_url}/static/img/admin/dishm9.png`" class="menu-img"></image>
							<text class="menu-text">预定订单</text>
						</view>
						<view class="meun-list-options flex-col flex-x-center flex-y-center"  @tap="goto" data-url="../restaurant/booking" v-if="auth_data.restaurant_booking">
							<image :src="`${pre_url}/static/img/admin/wm1.png`" class="menu-img"></image>
							<text class="menu-text">添加预定</text>
						</view>
						<view class="meun-list-options flex-col flex-x-center flex-y-center"  @tap="goto" data-url="../restaurant/queue" v-if="auth_data.restaurant_queue">
							<image :src="`${pre_url}/static/img/admin/wm3.png`" class="menu-img"></image>
							<text class="menu-text">排队叫号</text>
						</view>
						<view class="meun-list-options flex-col flex-x-center flex-y-center"  @tap="goto" data-url="../restaurant/queueCategory"  v-if="auth_data.restaurant_queue">
							<image :src="`${pre_url}/static/img/admin/wm4.png`" class="menu-img"></image>
							<text class="menu-text">排队管理</text>
						</view>
						<view class="meun-list-options flex-col flex-x-center flex-y-center"  @tap="goto" data-url="../restaurant/depositorder" v-if="auth_data.restaurant_deposit">
							<image :src="`${pre_url}/static/img/admin/wm2.png`" class="menu-img"></image>
							<text class="menu-text">寄存订单</text>
						</view>
					</block>
				</view>
			</view>
		</block>
    <block v-if="showmdmoney == 1 && (auth_data.mendian_mdmoneylog || auth_data.mendian_mdwithdraw || auth_data.mendian_mdwithdrawlog)">
    	<view class="menu-manage flex-col">
    		<view class="menu-title">门店余额</view>
    		<view class="menu-list width">
    			<block>
    				<view class="meun-list-options flex-col flex-x-center flex-y-center"  @tap="goto" data-url="../finance/mdmoneylog" v-if="auth_data.mendian_mdmoneylog">
    					<image :src="`${pre_url}/static/img/admin/financenbg7.png`" class="menu-img"></image>
    					<text class="menu-text">余额明细</text>
    				</view>
    				<view class="meun-list-options flex-col flex-x-center flex-y-center"  @tap="goto" data-url="../finance/mdwithdraw" v-if="auth_data.mendian_mdwithdraw">
    					<image :src="`${pre_url}/static/img/admin/financenbg8.png`" class="menu-img"></image>
    					<text class="menu-text">余额提现</text>
    				</view>
    				<view class="meun-list-options flex-col flex-x-center flex-y-center"  @tap="goto" data-url="../finance/mdwithdrawlog" v-if="auth_data.mendian_mdwithdrawlog">
    					<image :src="`${pre_url}/static/img/admin/financenbg8.png`" class="menu-img"></image>
    					<text class="menu-text">提现记录</text>
    				</view>
    			</block>
    		</view>
    	</view>
    </block>

		<view class="menu-manage" v-if="shopDataShow">
			<view class="divider-div"></view>
			<view class="tab-div flex-y-center">
				<view class="tab-options flex-col" @click="tabChange(1)" v-if="auth_data.order && (showcollageorder || showkanjiaorder || showseckillorder || showscoreshoporder || showluckycollageorder || showyuyueorder || showCycleorder)">
					<view :class="{'tab-options-active':tabIndex == 1}">商城订单</view>
					<view class="color-bar" v-if="tabIndex == 1"></view>
				</view>
				<view class="tab-options flex-col" @click="tabChange(2)" v-if="auth_data.restaurant_takeaway || auth_data.restaurant_shop || auth_data.restaurant_booking || auth_data.restaurant_queue || auth_data.restaurant_deposit">
					<view :class="{'tab-options-active':tabIndex == 2}">餐饮订单</view>
					<view class="color-bar" v-if="tabIndex == 2"></view>
				</view>
				<view class="tab-options flex-col" @click="tabChange(0)" v-if="auth_data.restaurant_product || auth_data.restaurant_tableWaiter">
					<view :class="{'tab-options-active':tabIndex == 0}">餐饮数据</view>
					<view class="color-bar" v-if="tabIndex == 0"></view>
				</view>
					
				<view class="tab-options flex-col" @click="tabChange(4)" v-if="auth_data.hotel_order">
					<view :class="{'tab-options-active':tabIndex == 4}">{{hotel.text['酒店']}}订单</view>
					<view class="color-bar" v-if="tabIndex == 4"></view>
				</view>
				
			</view>
			<!-- 餐饮数据 -->
			<block v-if="auth_data.restaurant_product || auth_data.restaurant_tableWaiter">
				<view class="data-div flex-col" v-if="tabIndex == 0">
					<view class="data-div-list">
						<view class="data-div-options" @tap="goto" data-url="../restaurant/product/index" v-if="auth_data.restaurant_product">
							<view class="title-text">菜品</view>
							<view class="num-text">{{restaurant_product_count}}</view>
						</view>
						<view class="border-bar-div" v-if="auth_data.restaurant_product"></view>
						<view class="data-div-options" @tap="goto" data-url="../restaurant/tableWaiter" v-if="auth_data.restaurant_tableWaiter">
							<view class="title-text">餐桌</view>
							<view class="num-text">{{restaurant_table_count}}</view>
						</view>
					</view>
				</view>
			</block>		

			<!-- 商城订单 -->
			<block v-if="auth_data.order">
				<view class="data-div flex-col" v-if="tabIndex == 1">
					<view class="data-div-list">
						<view class="data-div-options" @tap="goto" data-url="../order/collageorder" v-if="showcollageorder">
							<view class="title-text">拼团订单</view>
							<view class="num-text">{{collageCount}}</view>
						</view>
						<view class="border-bar-div" v-if="showcollageorder"></view>
						<view class="data-div-options" @tap="goto" data-url="../order/kanjiaorder" v-if="showkanjiaorder">
							<view class="title-text">砍价订单</view>
							<view class="num-text">{{kanjiaCount}}</view>
						</view>
						<view class="border-bar-div" v-if="showkanjiaorder"></view>
						<view class="data-div-options" @tap="goto" data-url="../order/seckillorder" v-if="showseckillorder">
							<view class="title-text">秒杀订单</view>
							<view class="num-text">{{seckillCount}}</view>
						</view>
						<view class="border-bar-div" v-if="showseckillorder"></view>
						<view class="data-div-options" @tap="goto" data-url="../order/tuangouorder" v-if="showtuangouorder">
							<view class="title-text">团购订单</view>
							<view class="num-text">{{tuangouCount}}</view>
						</view>
						<view class="border-bar-div" v-if="showtuangouorder"></view>
						<view class="data-div-options" @tap="goto" data-url="../order/scoreshoporder" v-if="showscoreshoporder">
							<view class="title-text">{{t('积分')}}商城订单</view>
							<view class="num-text">{{scoreshopCount}}</view>
						</view>
						<view class="border-bar-div" v-if="showscoreshoporder"></view>
						<view class="data-div-options" @tap="goto" data-url="../order/luckycollageorder" v-if="showluckycollageorder">
							<view class="title-text">{{t('幸运拼团')}}订单</view>
							<view class="num-text">{{luckycollageCount}}</view>
						</view>
						<view class="border-bar-div" v-if="showluckycollageorder"></view>
						<view class="data-div-options" @tap="goto" data-url="../order/yuyueorder" v-if="showyuyueorder">
							<view class="title-text">预约订单</view>
							<view class="num-text">{{yuyueorderCount}}</view>
						</view>
						<view class="border-bar-div" v-if="showyuyueorder"></view>
						<view class="data-div-options" @tap="goto" data-url="../order/cycleorder" v-if="showCycleorder">
							<view class="title-text">周期购订单</view>
							<view class="num-text">{{cycleCount}}</view>
						</view>
					</view>
				</view>
			</block>
			<!-- 外卖订单 -->
			<block v-if="auth_data.restaurant_takeaway || auth_data.restaurant_shop || auth_data.restaurant_booking || auth_data.restaurant_queue || auth_data.restaurant_deposit">
				<view class="data-div flex-col" v-if="tabIndex == 2">
					<view class="data-div-list">
						<view class="data-div-options" @tap="goto" data-url="../restaurant/takeawayorder" v-if="auth_data.restaurant_takeaway">
							<view class="title-text">外卖订单</view>
							<view class="num-text">{{restaurant_takeaway_count}}</view>
						</view>
						<view class="border-bar-div" v-if="auth_data.restaurant_takeaway"></view>
						<view class="data-div-options" @tap="goto" data-url="../restaurant/shoporder" v-if="auth_data.restaurant_shop">
							<view class="title-text">点餐订单</view>
							<view class="num-text">{{restaurant_shop_count}}</view>
						</view>
						<view class="border-bar-div" v-if="auth_data.restaurant_shop"></view>
						<view class="data-div-options" @tap="goto" data-url="../restaurant/bookingorder" v-if="auth_data.restaurant_booking">
							<view class="title-text">预定订单</view>
							<view class="num-text">{{restaurant_booking_count}}</view>
						</view>
						<view class="border-bar-div" v-if="auth_data.restaurant_booking"></view>
						<view class="data-div-options" @tap="goto" data-url="../restaurant/queue" v-if="auth_data.restaurant_queue">
							<view class="title-text">排队叫号</view>
							<view class="num-text">{{restaurant_queue}}</view>
						</view>
						<view class="border-bar-div" v-if="auth_data.restaurant_queue"></view>
						<view class="data-div-options" @tap="goto" data-url="../restaurant/depositorder" v-if="auth_data.restaurant_deposit">
							<view class="title-text">寄存订单</view>
							<view class="num-text">{{restaurant_deposit}}</view>
						</view>
					</view>
				</view>
			</block>
			
			
			<!-- 酒店订单 -->
			<block v-if="auth_data.hotel_order">
				<view class="data-div flex-col" v-if="tabIndex == 4">
					<view class="data-div-list hotelorder">
						<view class="data-div-options" @tap="goto" data-url="/adminExt/hotel/orderlist?st=all" v-if="auth_data.hotel_order" >
							<view class="title-text">全部</view>
							<view class="num-text">{{hotel.hotelCount}}</view>
						</view>
						<view class="data-div-options" @tap="goto" data-url="/adminExt/hotel/orderlist?st=1" v-if="auth_data.hotel_order">
							<view class="title-text">待确认</view>
							<view class="num-text">{{hotel.hotelOrderCount1}}</view>
						</view>
						<view class="data-div-options" @tap="goto" data-url="/adminExt/hotel/orderlist?st=2" v-if="auth_data.hotel_order">
							<view class="title-text">待入住</view>
							<view class="num-text">{{hotel.hotelOrderCount2}}</view>
						</view>
						<view class="data-div-options" @tap="goto" data-url="/adminExt/hotel/orderlist?st=3" v-if="auth_data.hotel_order">
							<view class="title-text">已到店</view>
							<view class="num-text">{{hotel.hotelOrderCount3}}</view>
						</view>
						<view class="data-div-options" @tap="goto" data-url="/adminExt/hotel/orderlist?st=4" v-if="auth_data.hotel_order">
							<view class="title-text">已离店</view>
							<view class="num-text">{{hotel.hotelOrderCount4}}</view>
						</view>
					</view>
				</view>
			</block>
		</view>
		
		
		<view class="menu-manage flex-col" v-if="false">
			<view class="menu-title">商家数据</view>
			<view class="mer-list">
				<view class="merchant-view cysj-text" :style="{background:'url('+pre_url+'/static/img/admin/merbg1.png)',backgroundSize:'cover',backgroundRepeat:'no-repeat'}">
					<view class="mer-title">餐饮数据</view>
					<block>
						<view class="mer-options" @tap="goto" data-url="../restaurant/product/index" v-if="auth_data.restaurant_product">菜品列表:<text>{{restaurant_product_count}}</text></view>	
						<view class="mer-options" @tap="goto" data-url="../restaurant/tableWaiter" v-if="auth_data.restaurant_tableWaiter">餐桌管理:<text>{{restaurant_table_count}}</text></view>
					</block>
				</view>
				<view class="merchant-view" :style="{background:'url('+pre_url+'/static/img/admin/merbg2.png)',backgroundSize:'cover',backgroundRepeat:'no-repeat'}">
					<view class="mer-title">商城订单</view>
					<scroll-view class="scroll-Y scdd-text" scroll-y="true">
						<block v-if="auth_data.order">
							<view class="mer-options" @tap="goto" data-url="../order/collageorder" v-if="showcollageorder">拼团订单:<text>{{collageCount}}</text></view>
							<view class="mer-options" @tap="goto" data-url="../order/kanjiaorder" v-if="showkanjiaorder">砍价订单:<text>{{kanjiaCount}}</text></view>
							<view class="mer-options" @tap="goto" data-url="../order/seckillorder" v-if="showseckillorder">秒杀订单:<text>{{seckillCount}}</text></view>
							<view class="mer-options" @tap="goto" data-url="../order/tuangouorder" v-if="showtuangouorder">团购订单:<text>{{tuangouCount}}</text></view>
							<view class="mer-options" @tap="goto" data-url="../order/scoreshoporder" v-if="showscoreshoporder">{{t('积分')}}商城订单:<text>{{scoreshopCount}}</text></view>
							<view class="mer-options" @tap="goto" data-url="../order/luckycollageorder" v-if="showluckycollageorder">{{t('幸运拼团')}}订单:<text>{{luckycollageCount}}</text></view>
							<view class="mer-options" @tap="goto" data-url="../order/yuyueorder" v-if="showyuyueorder">预约订单:<text>{{yuyueorderCount}}</text></view>
							<view class="mer-options" @tap="goto" data-url="../order/cycleorder" v-if="showCycleorder">周期购订单:<text>{{cycleCount}}</text></view>
							<!-- <view class="mer-options" @tap="goto" data-url="../order/maidanlog" v-if="showmaidanlog">买单记录:<text>{{maidanCount}}</text></view> -->
							<!-- <view class="mer-options" @tap="goto" data-url="../form/formlog" v-if="showformlog">表单提交记录:<text>{{formlogCount}}</text></view> -->
						</block>
							<!-- <view class="mer-options" @tap="goto" data-url="../workorder/record" v-if="showworkorder">工单记录:<text>{{workorderCount}}</text></view> -->
						<block v-if="scoreshop_product">
							<!-- <view class="mer-options" @tap="goto" data-url="../scoreproduct/index" v-if="showworkorder">兑换商品列表:<text>{{scoreproductCount}}</text></view> -->
						</block>
					</scroll-view>
				</view>
				<view class="merchant-view" :style="{background:'url('+pre_url+'/static/img/admin/merbg3.png)',backgroundSize:'cover',backgroundRepeat:'no-repeat'}">
					<view class="mer-title">外卖订单</view>
					<scroll-view class="scroll-Y wmdd-text" scroll-y="true">
						<view class="mer-options" @tap="goto" data-url="../restaurant/takeawayorder" v-if="auth_data.restaurant_takeaway">外卖订单:<text>{{restaurant_takeaway_count}}</text></view>
						<view class="mer-options" @tap="goto" data-url="../restaurant/shoporder" v-if="auth_data.restaurant_shop">点餐订单:<text>{{restaurant_shop_count}}</text></view>
						<view class="mer-options" @tap="goto" data-url="../restaurant/bookingorder" v-if="auth_data.restaurant_booking">预定订单:<text>{{restaurant_booking_count}}</text></view>
						<view class="mer-options" @tap="goto" data-url="../restaurant/queue" v-if="auth_data.restaurant_queue">排队叫号:<text>{{restaurant_queue}}</text></view>
						<view class="mer-options" @tap="goto" data-url="../restaurant/depositorder" v-if="auth_data.restaurant_deposit">寄存订单:<text>{{restaurant_deposit}}</text></view>
					</scroll-view>
				</view>
				
			</view>
		</view>
		
		<!-- 新增页面放在adminExt分包 -->
		<view class="menu-manage flex-col">
			<!-- <view class="menu-title">其他</view> -->
			<view class="menu-list width">
				<view class="meun-list-options flex-col flex-x-center flex-y-center"  @tap="goto" data-url="/adminExt/coupon/index" v-if="inArray('Coupon',show_auth)">
					<image :src="`${pre_url}/static/img/admin/wm5.png`" class="menu-img"></image>
					<text class="menu-text">{{t('优惠券')}}</text>
				</view>
				<view class="meun-list-options flex-col flex-x-center flex-y-center"  @tap="goto" data-url="/adminExt/coupon/restaurantList" v-if="inArray('RestaurantCoupon',show_auth)">
					<image :src="`${pre_url}/static/img/admin/wm5.png`" class="menu-img"></image>
					<text class="menu-text">餐饮优惠券</text>
				</view>
				<block v-if="auth_data.order">
					<view class="meun-list-options flex-col flex-x-center flex-y-center"  @tap="goto" data-url="../order/yuekeorder" v-if="showyuekeorder">
						<image :src="`${pre_url}/static/img/admin/wm5.png`" class="menu-img"></image>
						<text class="menu-text">约课记录</text>
					</view>
					<view class="meun-list-options flex-col flex-x-center flex-y-center" @tap="goto" data-url="../form/formlog" v-if="showformlog">
						<image :src="`${pre_url}/static/img/admin/wm8.png`" class="menu-img"></image>
						<text class="menu-text">表单提交记录</text>
					</view>
					<view class="meun-list-options flex-col flex-x-center flex-y-center" @tap="goto" data-url="/adminExt/order/maidanindex" v-if="showmaidanlog">
						<image :src="`${pre_url}/static/img/admin/wm9.png`" class="menu-img"></image>
						<text class="menu-text">买单统计</text>
					</view>
					<view class="meun-list-options flex-col flex-x-center flex-y-center" @tap="goto" data-url="/adminExt/order/maidannewindex" v-if="inArray('MaidanNew',show_auth)">
						<image :src="`${pre_url}/static/img/admin/wm9.png`" class="menu-img"></image>
						<text class="menu-text">新买单统计</text>
					</view>
	
				</block>

        <view class="meun-list-options flex-col flex-x-center flex-y-center"  @tap="goto" :data-url="'/adminExt/order/maidanOrderAdd?bid='+uinfo.bid" v-if="inArray('maidan_orderadd_mobile_paytransfer',show_auth)">
          <image :src="`${pre_url}/static/img/admin/wm4.png`" class="menu-img"></image>
          <text class="menu-text">买单订单录入</text>
        </view>
				
				<view class="meun-list-options flex-col flex-x-center flex-y-center" @tap="goto" data-url="/adminExt/huodongbaoming/order"  v-if="inArray('HuodongBaomingOrder',show_auth)">
					<image :src="`${pre_url}/static/img/admin/wm9.png`" class="menu-img"></image>
					<text class="menu-text">活动报名订单</text>
				</view>
				
					<view class="meun-list-options flex-col flex-x-center flex-y-center" @tap="goto" data-url="/admin/health/record" v-if="custom.showHealth">
						<image :src="`${pre_url}/static/img/admin/wm5.png`" class="menu-img"></image>
						<text class="menu-text">量表填写记录</text>
					</view>
					<view class="meun-list-options flex-col flex-x-center flex-y-center" @tap="goto" data-url="../workorder/category" v-if="showworkorder">
						<image :src="`${pre_url}/static/img/admin/wm5.png`" class="menu-img"></image>
						<text class="menu-text">工单</text>
					</view>
					<view class="meun-list-options flex-col flex-x-center flex-y-center" @tap="goto" data-url="../scoreproduct/index"  v-if="scoreshop_product">
						<image :src="`${pre_url}/static/img/admin/wm5.png`" class="menu-img"></image>
						<text class="menu-text">兑换商品列表</text>
					</view>
					<view class="meun-list-options flex-col flex-x-center flex-y-center"  @tap="goto" data-url="../member/code" v-if="inArray('member_code_buy',auth_data.hexiao_auth_data)">
						<image :src="`${pre_url}/static/img/admin/wm6.png`" class="menu-img"></image>
						<text class="menu-text">会员消费</text>
					</view>
					<view class="meun-list-options flex-col flex-x-center flex-y-center"  @tap="goto" data-url="businessqr" v-if="showbusinessqr">
						<image :src="`${pre_url}/static/img/admin/wm5.png`" class="menu-img"></image>
						<text class="menu-text">推广码</text>
					</view>
					<view class="meun-list-options flex-col flex-x-center flex-y-center"  @tap="goto" :data-url="'/pagesA/workorder/category?bid='+uinfo.bid" v-if="showworkadd">
						<image :src="`${pre_url}/static/img/admin/wm5.png`" class="menu-img"></image>
						<text class="menu-text">工单提交</text>
					</view>
					<view class="meun-list-options flex-col flex-x-center flex-y-center"  @tap="goto" data-url="../product/edit" v-if="auth_data.product && add_product">
						<image :src="`${pre_url}/static/img/admin/wm7.png`" class="menu-img"></image>
						<text class="menu-text">添加商品</text>
					</view>
					<view class="meun-list-options flex-col flex-x-center flex-y-center"  @tap="goto" data-url="../scoreproduct/edit" v-if="scoreshop_product">
						<image :src="`${pre_url}/static/img/admin/wm5.png`" class="menu-img"></image>
						<text class="menu-text">添加兑换商品</text>
					</view>
					<block v-if="auth_data.order">
						<view class="meun-list-options flex-col flex-x-center flex-y-center"  @tap="goto" data-url="/activity/searchmember/searchmember" v-if="searchmember">
							<image :src="`${pre_url}/static/img/admin/wm5.png`" class="menu-img"></image>
							<text class="menu-text">一键查看</text>
						</view>
					</block>
					<view class="meun-list-options flex-col flex-x-center flex-y-center"  @tap="goto" data-url="../yingxiao/queueFree" v-if="inArray('queue_free',show_auth)">
						<image :src="`${pre_url}/static/img/admin/wm5.png`" class="menu-img"></image>
						<text class="menu-text">排队记录</text>
					</view>
					<view class="meun-list-options flex-col flex-x-center flex-y-center"  @tap="goto" data-url="/adminExt/queuefree/queueFreeSet" v-if="inArray('wxadminQueueFreeSet',show_auth)">
						<image :src="`${pre_url}/static/img/admin/wm5.png`" class="menu-img"></image>
						<text class="menu-text">排队设置</text>
					</view>
					<view class="meun-list-options flex-col flex-x-center flex-y-center"  @tap="goto" data-url="/adminExt/queuefree/businessfanlilog" v-if="inArray('QueueFreeBusinessFanli',show_auth)">
						<image :src="`${pre_url}/static/img/admin/wm5.png`" class="menu-img"></image>
						<text class="menu-text">商户排队返利</text>
					</view>
					
					<block v-if="show_categroy_business">
					<view class="meun-list-options flex-col flex-x-center flex-y-center"  @tap="goto" data-url="../product/category2/index" v-if="auth_data.product">
						<image :src="`${pre_url}/static/img/admin/wm2.png`" class="menu-img"></image>
						<text class="menu-text">商品分类</text>
					</view><view class="meun-list-options flex-col flex-x-center flex-y-center"  @tap="goto" data-url="../product/category2/edit?id=" v-if="auth_data.product">
						<image :src="`${pre_url}/static/img/admin/wm1.png`" class="menu-img"></image>
						<text class="menu-text">添加商品分类</text>
					</view>
					</block>
					<view class="meun-list-options flex-col flex-x-center flex-y-center"  @tap="goto" data-url="../order/dkorder" v-if="inArray('ShopOrderlr',show_auth)">
						<image :src="`${pre_url}/static/img/admin/wm5.png`" class="menu-img"></image>
						<text class="menu-text">代客下单</text>
					</view>
					<view class="meun-list-options flex-col flex-x-center flex-y-center"  @tap="goto" data-url="/adminExt/shop/shopstock"  v-if="inArray('ShopStock',show_auth)">
						<image :src="`${pre_url}/static/img/admin/wm5.png`" class="menu-img"></image>
						<text class="menu-text">库存录入</text>
					</view>
					<view class="meun-list-options flex-col flex-x-center flex-y-center"  @tap="goto" data-url="../business/index" v-if="inArray('show_business',show_auth) && uinfo.bid==0">
						<image :src="`${pre_url}/static/img/admin/wm5.png`" class="menu-img"></image>
						<text class="menu-text">商家列表</text>
					</view>
					<view class="meun-list-options flex-col flex-x-center flex-y-center"  @tap="goto" data-url="/pagesB/admin/pickupdevice" v-if="auth_data.device_addstock">
						<image :src="`${pre_url}/static/img/admin/dishm6.png`" class="menu-img"></image>
						<text class="menu-text">商品柜设备</text>
					</view>
					<view class="meun-list-options flex-col flex-x-center flex-y-center"  @tap="goto" data-url="/pagesC/invoicebaoxiao/adminrecordlist" v-if="inArray('invoicebaoxiao',show_auth)">
						<image :src="`${pre_url}/static/img/admin/dishm8.png`" class="menu-img"></image>
						<text class="menu-text">发票报销记录</text>
					</view>
          <view class="meun-list-options flex-col flex-x-center flex-y-center"  @tap="goto" data-url="/pagesC/qrcodevar/index" v-if="auth_data.qrcode_variable_maidan">
          	<image :src="`${pre_url}/static/img/admin/menu1.png`" class="menu-img"></image>
          	<text class="menu-text">绑定收款码</text>
          </view>          
          <view class="meun-list-options flex-col flex-x-center flex-y-center"  @tap="goto" data-url="/adminExt/set/qrcodeShop" v-if="inArray('qrcode_shop',auth_data.wxauth_data)">
          	<image :src="`${pre_url}/static/img/admin/menu1.png`" class="menu-img"></image>
          	<text class="menu-text">店铺二维码</text>
          </view>
		  
		  <view class="meun-list-options flex-col flex-x-center flex-y-center"  @tap="goto" data-url="/adminExt/set/payqrcode" v-if="inArray('show_payqrcode',show_auth)">
		  	<image :src="`${pre_url}/static/img/admin/menu1.png`" class="menu-img"></image>
		  	<text class="menu-text">店铺收款码</text>
		  </view>
          <view class="meun-list-options flex-col flex-x-center flex-y-center"  @tap="goto" data-url="/adminExt/product/lvxinEdit" v-if="inArray('product_lvxin_replace_remind',show_auth) && uinfo.bid==0">
            <image :src="`${pre_url}/static/img/admin/wm9.png`" class="menu-img"></image>
            <text class="menu-text">售后录入</text>
          </view>
					<view class="meun-list-options flex-col flex-x-center flex-y-center"  @tap="goto" data-url="/adminExt/businessjinjian/index" v-if="inArray('business_jinjian',auth_data.wxauth_data)">
						<image :src="`${pre_url}/static/img/admin/wm7.png`" class="menu-img"></image>
						<text class="menu-text">商户进件</text>
					</view>
					<view class="meun-list-options flex-col flex-x-center flex-y-center"  @tap="goto" data-url="/adminExt/adminuser/list" v-if="inArray('business_add_admin',auth_data.wxauth_data)">
						<image :src="`${pre_url}/static/img/admin/menu4.png`" class="menu-img"></image>
						<text class="menu-text">管理员管理</text>
					</view>
					<view class="meun-list-options flex-col flex-x-center flex-y-center"  @tap="goto" data-url="/adminExt/businesspaycode/index" v-if="inArray('business_paycode',auth_data.wxauth_data)">
						<image :src="`${pre_url}/static/img/admin/menu1.png`" class="menu-img"></image>
						<text class="menu-text">商家收款码</text>
					</view>
          <view class="meun-list-options flex-col flex-x-center flex-y-center"  @tap="goto" data-url="/adminExt/sound/index" v-if="auth_data.sound_mobile">
          	<image :src="`${pre_url}/static/img/admin/menu1.png`" class="menu-img"></image>
          	<text class="menu-text">绑定云音响</text>
          </view>  
					<view class="meun-list-options flex-col flex-x-center flex-y-center"  @tap="qlszJinjian" v-if="inArray('qilin_jinjian',auth_data.wxauth_data)">
						<image :src="`${pre_url}/static/img/admin/wm7.png`" class="menu-img"></image>
						<text class="menu-text">麒麟进件</text>
					</view>
					<view class="meun-list-options flex-col flex-x-center flex-y-center"  @tap="goto" data-url="/adminExt/article/list"  v-if="inArray('article',show_auth)">
						<image :src="`${pre_url}/static/img/admin/article.png`" class="menu-img"></image>
						<text class="menu-text">文章管理</text>
					</view>
					<view class="meun-list-options flex-col flex-x-center flex-y-center"  @tap="goto" data-url="/adminExt/mendian/qrcode"  v-if="inArray('mendian_qrcode',auth_data.wxauth_data)">
						<image :src="`${pre_url}/static/img/admin/menu1.png`" class="menu-img"></image>
						<text class="menu-text">门店二维码</text>
					</view>
					<view class="meun-list-options flex-col flex-x-center flex-y-center"  @tap="goto" data-url="/adminExt/business/managebusiness" v-if="uinfo.manage_multi_business_status">
						<image :src="`${pre_url}/static/img/admin/wm5.png`" class="menu-img"></image>
						<text class="menu-text">商户管理</text>
					</view>
          <view class="meun-list-options flex-col flex-x-center flex-y-center"  @tap="goto" data-url="/adminExt/water/index" v-if="inArray('water_happy_ti',show_auth)">
            <image :src="`${pre_url}/static/img/admin/wm4.png`" class="menu-img"></image>
            <text class="menu-text">扫码打水管理</text>
          </view>
					<view class="meun-list-options flex-col flex-x-center flex-y-center"  @tap="goto" data-url="/adminExt/order/goldbeanshoporder" v-if="inArray('GoldBeanShop',show_auth)">
					  <image :src="`${pre_url}/static/img/admin/wm5.png`" class="menu-img"></image>
					  <text class="menu-text">{{t('金豆')}}商城订单</text>
					</view>
					<!-- 新增页面放在adminExt分包 -->
			</view>
		</view>
		
		<view class="tabbar">
			<view class="tabbar-bot"></view>
			<view class="tabbar-bar" style="background-color:#ffffff">
				<view @tap="goto" data-url="../member/index" data-opentype="reLaunch" class="tabbar-item" v-if="auth_data.member">
					<view class="tabbar-image-box">
						<image class="tabbar-icon" :src="pre_url+'/static/img/admin/member.png?v=1'"></image>
					</view>
					<view class="tabbar-text">{{t('会员')}}</view>
				</view>
				<view @tap="goto" data-url="../kefu/index" data-opentype="reLaunch" class="tabbar-item" v-if="auth_data.zixun">
					<view class="tabbar-image-box">
						<image class="tabbar-icon" :src="pre_url+'/static/img/admin/zixun.png?v=1'"></image>
					</view>
					<view class="tabbar-text">咨询</view>
				</view>
				<view @tap="goto" data-url="../finance/index" data-opentype="reLaunch" class="tabbar-item" v-if="auth_data.finance">
					<view class="tabbar-image-box">
						<image class="tabbar-icon" :src="pre_url+'/static/img/admin/finance.png?v=1'"></image>
					</view>
					<view class="tabbar-text">财务</view>
				</view>
				<view @tap="goto" data-url="../index/index" data-opentype="reLaunch" class="tabbar-item">
					<view class="tabbar-image-box">
						<image class="tabbar-icon" :src="pre_url+'/static/img/admin/my2.png?v=1'"></image>
					</view>
					<view class="tabbar-text active">我的</view>
				</view>
			</view>
		</view>
		
		</block>
		<popmsg ref="popmsg"></popmsg>
		<loading v-if="loading"></loading>
		<wxxieyi></wxxieyi>
	</view>
</template>

<script>
	const app = getApp();
	export default {
		data(){
			return{
				opt:{},
				loading:false,
				statusBarHeight: 20,
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
				luckycollageCount:0,
				auth_data: {},
				showshoporder:false,
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
				workorderCount:0,
				showrecharge:false,
				showrestaurantproduct:false,
				showrestauranttable:false,
				wxtmplset:{},
				searchmember:false,
				showCycleorder:false,
				scoreshop_product:false,
				scoreproductCount:0,
				custom:{},//定制显示控制放一起
				isload:false,
				today_order_count:0,//进入订单数
				today_money:0,
				restaurant_shop_count:0,
				restaurant_takeaway_count:0,
				restaurant_booking_count:0,
				restaurant_queue:0,
				restaurant_deposit:0,
				restaurant_product_count:0,
				restaurant_table_count:0,
				tabIndex:1,
				shopDataShow:true,
				other_show:true,
				showworkadd:false,
				showbusinessqr:false,
				show_categroy_business:false,
				show_auth:[],
				add_product:1,
				hotel:0,
        showmdmoney:0,
				showarticle:0,
				time_tab_index:'today',//时间筛选
				yingyedata:{},
				show_top_tongji:false
			}
		},
		onLoad(opt){
			this.opt = app.getopts(opt);
			this.getdata();
			let sysinfo = uni.getWindowInfo();
			this.statusBarHeight = sysinfo.statusBarHeight;
		},
		onPullDownRefresh: function () {
			this.getdata();
		},
		methods:{
			// 今日收款解释说明
			explanation(){
				uni.showModal({
					title: '解释说明',
					content: `数据不含${app.t('余额')}支付`,
					showCancel:false
				});
			},
			determineTabIndex(){
				if((this.auth_data.order && (this.showcollageorder || this.showkanjiaorder || this.showseckillorder || this.showscoreshoporder || this.showluckycollageorder || this.showyuyueorder || this.showCycleorder))){
					this.tabIndex = 1;
				}else{
					if(this.auth_data.restaurant_takeaway || this.auth_data.restaurant_shop || this.auth_data.restaurant_booking || this.auth_data.restaurant_queue || this.auth_data.restaurant_deposit){
						this.tabIndex = 2;
					}else{
						if(this.auth_data.restaurant_product || this.auth_data.restaurant_tableWaiter){
							this.tabIndex = 0;
						}else if(this.auth_data.hotel_order){
							this.tabIndex = 4;
						}else{
							this.shopDataShow = false;
						}
					}
				}
				// // 其他
				// if((this.auth_data.order && (this.showyuekeorder || this.showformlog || this.showmaidanlog || this.searchmember)) || this.custom.showHealth || this.showworkorder || this.scoreshop_product || inArray('member_code_buy',this.auth_data.hexiao_auth_data) || this.showbusinessqr || this.showworkadd || this.auth_data.product){
				// 	this.other_show = true;
				// }else{
				// 	this.other_show = false;
				// }
			},
			// 
			tabChange(type){
				this.tabIndex = type
			},
			// 页面信息
			getdata:function(){
				var that = this;
				that.loading = true;
				app.get('ApiAdminIndex/index', {}, function (res) {
					that.loading = false;
					that.set = res.set;
					that.show_auth = res.show_auth
					res.show_auth.filter(item => {
						if(item == 'ScoreshopProduct') that.scoreshop_product = true;
						if(item == 'ScoreshopOrder') that.showscoreshoporder = true;
						if(item == 'KanjiaOrder') that.showkanjiaorder = true;
						if(item == 'CollageOrder') that.showcollageorder = true;
						if(item == 'SeckillOrder') that.showseckillorder = true;
						if(item == 'TuangouOrder') that.showtuangouorder = true;
						if(item == 'LuckyCollageOrder') that.showluckycollageorder = true;
						if(item == 'YuyueOrder') that.showyuyueorder = true;
						if(item == 'CycleOrder') that.showCycleorder = true;
					})
					// that.wxtmplset = res.wxtmplset;
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
					if(that.auth_data.finance){
						that.show_top_tongji = true;
					}
					if(that.uinfo.bid  > 0 && app.inArray('business_data_tongji',that.auth_data.wxauth_data)){
						that.show_top_tongji = false;
					}
					
					that.yuyueorderCount = res.yuyueorderCount;
					that.workordercount = res.workordercount;
					that.showshoporder = res.showshoporder;
					that.hotelCount = res.hotelCount
					// that.showcollageorder = res.showcollageorder;
					// that.showCycleorder = res.showCycleorder;
					// that.showkanjiaorder = res.showkanjiaorder;
					// that.showseckillorder = res.showseckillorder;
					// that.showtuangouorder = res.showtuangouorder;
					// that.showscoreshoporder = res.showscoreshoporder;
					// that.showluckycollageorder = res.showluckycollageorder;
					that.showmaidanlog = res.showmaidanlog;
					that.showformlog = res.showformlog;
					that.showworkorder = res.showworkorder;
					// that.showyuyueorder = res.showyuyueorder;
					that.showrecharge = res.showrecharge;
					that.showrestaurantproduct = res.showrestaurantproduct;
					that.showrestauranttable = res.showrestauranttable;
					that.searchmember = res.searchmember;
					that.showyuekeorder = res.showyuekeorder;
					// that.scoreshop_product = res.scoreshop_product || false;
					that.scoreproductCount = res.scoreproductCount || 0;
					that.custom = res.custom;
					that.today_order_count = res.today_order_count;
					that.today_money = res.today_money;
					that.restaurant_takeaway_count = res.restaurant_takeaway_count;
					that.restaurant_shop_count = res.restaurant_shop_count;
					that.restaurant_booking_count = res.restaurant_booking_count;
					that.restaurant_queue = res.restaurant_queue;
					that.restaurant_deposit = res.restaurant_deposit;
					that.restaurant_product_count = res.restaurant_product_count;
					that.restaurant_table_count = res.restaurant_table_count;
					that.showworkadd = res.showworkadd;
					that.showbusinessqr = res.showbusinessqr;
					that.show_categroy_business = res.show_categroy_business;
					that.add_product = res.add_product;
					that.hotel = res.hotel;
          that.showmdmoney = res.showmdmoney || 0;
					that.showarticle = res.showarticle || 0;
					that.loaded();
					that.determineTabIndex()
					that.getYingyeData();
				});
			},
			saoyisao: function (d) {
			  var that = this;
				if(app.globalData.platform == 'h5'){
					app.alert('请使用微信扫一扫功能扫码核销');return;
				}else if(app.globalData.platform == 'mp'){
					// #ifdef H5
					var jweixin = require('jweixin-module');
					jweixin.ready(function () {   //需在用户可能点击分享按钮前就先调用
						jweixin.scanQRCode({
							needResult: 1, // 默认为0，扫描结果由微信处理，1则直接返回扫描结果，
							scanType: ["qrCode","barCode"], // 可以指定扫二维码还是一维码，默认二者都有
							success: function (res) {
								var content = res.resultStr; // 当needResult 为 1 时，扫码返回的结果
								var params = content.split('?')[1];
								var url = '/admin/hexiao/hexiao?'+params;
								//扫出餐码
								var outurl = new Buffer(content, 'base64').toString('utf8')
								var outparam = app.getparams('?'+outurl);
								if(outparam['type'] =='outfood'){
									url = '/restaurant/admin/outfood?'+outurl;
								}
								app.goto(url);
								//if(content.length == 18 && (/^\d+$/.test(content))){ //是十八位数字 付款码
								//	location.href = "{:url('shoukuan')}/aid/{$aid}/auth_code/"+content
								//}else{
								//	location.href = content;
								//}
							},
							fail:function(err){
								if(err.errMsg == 'scanQRCode:the permission value is offline verifying' || err.errMsg == 'scanQRCode:permission denied' || err.errMsg == 'permission denied'){
									app.error('请先绑定公众号');
								}else{
									app.error(err.errMsg);
								}
							}
						});
					});
					// #endif					
				}else{
					// #ifndef H5
					uni.scanCode({
						success: function (res) {
							console.log(res);
							if(res.path){
									app.goto('/'+res.path);
							}else{
								var content = res.result;
								var params = content.split('?')[1];
								var url = '/admin/hexiao/hexiao?'+params;
								//扫出餐码
								var outurl = new Buffer(content, 'base64').toString('utf8')
								var outparam = app.getparams('?'+outurl);
								if(outparam['type'] =='outfood'){
									url = '/restaurant/admin/outfood?'+outurl;
								}
								app.goto(url);
							}
						},
						fail:function(err){
							app.error(err.errMsg);
						}
					});
					// #endif
				}
			},
			qlszJinjian:function(){
				var that = this;
				that.loading = true;
				app.post('ApiAdminIndex/qlszJinjian', {}, function (res) {
					if(res.status == 0){
						app.alert(res.msg);return;
					}
					app.goto(res.url); return;
				})
			},
			showExplain(e){
				var text = e.currentTarget.dataset.text;
				uni.showModal({
					title: '解释说明',
					content: text,
					showCancel:false
				});
			},
			timeTabChange(type){
				this.time_tab_index = type;
				this.getYingyeData();
			},
			getYingyeData(){
			
				var that = this;
				var time_tab = that.time_tab_index;
				that.loading = true;
				app.get('ApiAdminIndex/getYingyeData', {time_tab:time_tab}, function (res) {
					that.loading = false;
					that.yingyedata = res.data;
				});
			}
		}
	}
</script>

<style>
	@import "../common.css";
	page{background:#fff}
	.width{width: 95%;margin: 0 auto;}
	.view-width{width: 100%;height: auto;padding-bottom:60rpx}
	.head-class{}
	.head-view{
		/* #ifndef H5*/
		padding: 10rpx 40rpx 15rpx 40rpx;
		/* #endif */
		/* #ifdef H5 */
		padding: 30rpx 40rpx;
		/* #endif */
	}
	.head-view .avat-view{display:flex;align-items: center;justify-content: flex-start;}
	.head-view .avat-view .avat-img-view {width: 80rpx;height:80rpx;border-radius: 50%;overflow: hidden;border:2rpx #fff solid;}
	.head-view .avat-view .avat-img-view image{width: 100%;height: 100%;}
	.head-view .avat-view .user-info{display: flex;align-items: flex-start;flex-direction: column;}
	.head-view .avat-view .user-info .nickname{font-size: 32rpx;font-weight: bold;}
	.head-view .avat-view .user-info .un-text{font-size: 24rpx;color: rgba(34, 34, 34, 0.7);}
  .head-view .avat-view .imgback{width: 40rpx;height: 40rpx;margin-top: 4rpx;}
	.head-view .option-img-view{width: 160rpx;display: flex;align-items: center;justify-content: flex-end;}
	.head-view .recharge{background: #fff; width: 100rpx;color: #FB6534; text-align: center; font-size: 24rpx; padding: 5rpx; border-radius: 10rpx;margin-left: 20rpx;}
	.head-view .setup-view{position:relative;width: 64rpx;height:64rpx;}
	.head-view .setup-view image{width: 64rpx;height: 64rpx;}
	.head-view .setup-view .setup-img{
		/* #ifdef H5 */
		/* width: 48rpx;height: 48rpx;position:absolute;top:50%;left:50%;transform:translate(-50%,-50%); */
		/* #endif */
		}
	.today-data{padding: 20rpx 65rpx 40rpx;justify-content:space-around }
	.option-view{width: 50%;}
	.option-view text{text-align: center;}
	.option-view .title-text{font-size: 24rpx;color: #5f6064;padding-bottom: 15rpx;}
	.option-view .title-text .title-icon{width: 30rpx;height: 30rpx;margin-left: 10rpx;}
	.option-view .num-text{font-size: 28rpx;font-weight: 700}
	.option-view .unit-money{font-size: 24rpx;font-weight: 700;}
	.mall-orders{border-radius:12rpx;overflow: hidden;}
	.order-title{padding: 32rpx 40rpx;align-items: center;background: linear-gradient(to right, #c4dfff , #d7e8ff);}
	.order-title .title-text{font-size: 26rpx;font-weight: 500;color: #222;}
	.order-title .all-text{font-size: 24rpx;color: #5f6064;}
	.order-title .title-text .left-img{width: 6rpx;height: 24rpx;margin-right: 12rpx;}
	.order-title .all-text .right-img{width: 10rpx;height: 20rpx;margin-left: 20rpx;}
	.order-list{justify-content: space-around;padding:40rpx 0rpx;background: #D5E8FF;}
	.order-list .option-order{align-items: center;}
	.order-list .option-order .num-text{font-size: 28rpx;font-weight: bold;padding-bottom:10rpx;}
	.order-list .option-order .title-text{font-size: 24rpx;color: #5f6064;}
	.meun-view{padding:40rpx;}
	.meun-view .meun-options .menu-img{width: 88rpx;height:88rpx;}
	.meun-view .meun-options .menu-text{font-size: 24rpx;color: #242424;margin-top:12rpx;}
	.menu-manage{margin-bottom:50rpx}
	.menu-manage .menu-title{font-size: 30rpx;color: #242424;font-weight:bold;padding: 10rpx 40rpx;}
	.menu-manage .menu-list{display: flex;align-items: center;flex-wrap: wrap;justify-items: flex-start;}
	.menu-manage .menu-list .meun-list-options{width: 16%;margin:4% 2%;}
	.menu-manage .menu-list .meun-list-options .menu-img{width:60rpx;height:60rpx;}
	.menu-manage .menu-list .meun-list-options .menu-text{font-size: 24rpx;color: #242424;margin-top: 20rpx;white-space: nowrap;}
	.menu-manage .divider-div{width: 100%;height:20rpx;background: #F2F3F4;}
	.menu-manage .tab-div{padding-top: 20rpx;}
	.menu-manage .tab-div .tab-options {height:100rpx;font-size:26rpx;color: #666666;justify-content:flex-start;align-items:center;padding:20rpx 40rpx 0rpx}
	.menu-manage .tab-div .tab-options-active{color:#3d7af7}
	.menu-manage .tab-div .tab-options .color-bar{width:48rpx;height:3px;background: #3d7af7;margin-top: 20rpx;}
	.menu-manage .data-div{padding: 0rpx 30rpx;align-items:center;justify-content:space-between;}
	.data-div-list{display: flex;flex-direction: row;width: 100%;align-items: center;justify-content: flex-start;flex-wrap:wrap;}
	.data-div-list .data-div-options{display: flex;flex-direction: column;align-items: center;justify-content: space-around;width: 22%;padding: 30rpx 0rpx;}
	.data-div-list .data-div-options .title-text{font-size: 24rpx;color: #5f6064;}
	.data-div-list .data-div-options .num-text{font-size: 28rpx;font-weight: bold;color: #222222;margin-top: 20rpx;}
	.data-div-list .border-bar-div{height: 40rpx;width: 3rpx;background:#e5e5e5;margin: 0rpx 12rpx;}
	.data-div-list .border-bar-div:nth-child(8n){height: 40rpx;width: 0rpx;background:red;margin: 0rpx 0rpx;}

	.data-div-list.hotelorder .data-div-options{ width: 20%;}
		
	
	.mer-list{padding: 20rpx 40rpx;display: flex;align-items: center;justify-content: space-between;}
	.mer-list .merchant-view {display: flex;flex-direction: column;align-items: flex-start;
	width: 31%;height: 380rpx;border-radius:16rpx;padding: 0rpx 18rpx;background-repeat: no-repeat; background-size: cover;}
	.mer-list .merchant-view .mer-title{font-size: 24rpx;color: #242424;padding: 26rpx 0rpx;font-weight: 500;}
	.mer-list .merchant-view .mer-options{font-size: 20rpx;color: #7B7B7B;padding-bottom:20rpx;white-space: nowrap;	/* #ifdef H5*/	transform: scale(0.8);	/* #endif*/}
	.mer-list .merchant-view .mer-options text{padding: 0rpx 10rpx;font-size: 20rpx;text-align: left;}
	.cysj-text .mer-options text{color: #3F71E5;font-weight: 500;}
	.scdd-text .mer-options text{color: #FF9000;font-weight: 500;}
	.wmdd-text .mer-options text{color: #02B56A;font-weight: 500;}
	.scroll-Y{height: 280rpx;}
	
	/* 汇付统计数据 */
	.timetab{
		background-color: #c4dfff52;
		padding:10rpx 10rpx;
		border-radius: 10rpx;
		width: 60%;
		margin: 0 20%;
	}
	.timetab .tab{
		border-radius: 10rpx;
		height: 50rpx;
		line-height: 50rpx;
		width: 20%;
		background-color: #c4dfff;
		text-align: center;
	}
	.timetab .tab-on{
		color: #3d7af7;
		font-weight: bold;
	}
</style>
