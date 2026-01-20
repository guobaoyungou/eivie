<template>
	<view class="container">
		<block v-if="isload">
			<view class="view-show">
				<block v-if="sysset.mode == 1 && sysset.is_loc_business == 1">
					<view class="header" :style="{'background':navigationBarBackgroundColor}">
						<view class="header_title flex-y-center flex-bt">
							<view class="flex-y-center shop_title" @tap="toBusiness">
								{{business.name}}
								<image :src="pre_url+'/static/img/arrowright.png'" class="header_detail"></image>
							</view>
							<image @tap="goSearch" class="header_serach" :src="pre_url+'/static/img/search_ico.png'"></image>
						</view>
						<view class="header_address">
							距离你 {{juli}}
						</view>
					</view>
					<view class="topbannerbg"
						:style="business.pic?'background:url('+business.pic+') 100%;background-size:100% auto;':''">
					</view>
				</block>
				<block v-else>
					<view class="topbannerbg"
						:style="business.pic?'background:url('+business.pic+') center no-repeat;background-size:cover;':''">
						<view class="freight_type flex-bt" @tap="showfreighttype" v-if="sysset.shop_takeaway_switch ==1">
							<view class="takeaway f1 on"  :style="'background-color:'+t('color1')">外卖</view>
							<view class="shop f1" >自提</view>
						</view>
					</view>
					<view class="topbannerbg2"></view>
					<view class="topbanner">
						<view class="left">
							<image class="img" :src="business.logo" />
						</view>
						<view class="right">
							<view class="f1">{{business.name}}</view>
							<view class="f2">{{business.desc}}</view>
							<!-- <view class="f3"><view class="flex1"></view><view class="t2">收藏<image class="img" src="/static/img/like1.png"/></view></view> -->
						</view>
					</view>
				</block>
				<!--自定义页面-->
				<block v-if="sysset && sysset.designer_content" >
					<view>
					<dp :pagecontent="sysset.designer_content"></dp>
						</view>
				</block>
				<!--自定义页面end-->
				
				
				<view class="container body" :class="menuindex>-1?'body_status':''" :style="sysset.designer_content && sysset.designer_content.length?'margin-top:0rpx':''">
					<view class="navtab" v-if="(sysset.takeaway_show && sysset.takeaway_show==1) || sysset.business_info_show || sysset.comment_show">
						<view class="item" :class="st==0?'on':''" @tap="changetab" data-st="0" v-if="sysset.takeaway_show && sysset.takeaway_show==1">
                            <block>{{sysset.takeaway_name?sysset.takeaway_name:'点外卖'}}</block>
                            <view class="after" :style="{background:t('color1')}"></view>
						</view>
						<view class="item" :class="st==1?'on':''" @tap="changetab" data-st="1" v-if="sysset.business_info_show">
                            <block>{{sysset.business_info_name?sysset.business_info_name:'商家信息'}}</block>
                            <view class="after" :style="{background:t('color1')}"></view>
						</view>
						<view class="item" :class="st==2?'on':''" @tap="changetab" data-st="2" v-if="sysset.comment_show">
                            <block>{{sysset.comment_name?sysset.comment_name:'评价'}}</block>
                            <view class="after" :style="{background:t('color1')}"></view>
						</view>
					</view>
                    
					<view v-if="st==0" :style="{height:'calc(100% - '+(menuindex>-1?320:!sysset.takeaway_show && !sysset.business_info_show && !sysset.comment_show?102:210)+'rpx)'}">
                        <scroll-view scroll-y="true" style="height: 100%;">
                            <view class="banner" v-if="st == 0 && banner">
                                <view class="wc"> 
                                    <view class="head_img_view">
                                        <swiper class="head_img_swiper" :autoplay="true" :indicator-dots="false" :current="0" style="overflow:hidden" interval="4000">
                                            <block v-for="item in banner" :key="item.id">
                                                <swiper-item  :data-id="item.id" :open-type="item.link_url=='contact::'?'contact':(item.link_url=='share::'?'share':'')" @click="goto" :data-url="item.link_url" >
                                                    <image :src="item.pic" class="head_img" mode="scaleToFill"/>
                                                </swiper-item>
                                            </block>
                                        </swiper>
                                    </view>
                                </view>
                            </view>
                            <view class="content flex" :style="{height:'calc(100% + '+(banner?100:0)+'rpx)'}">
                                <scroll-view v-if="datalist && datalist.length > 0" class="nav_left"
                                    :scrollWithAnimation="animation" scroll-y="true" :class="menuindex>-1?'tabbarbot':''">
                                    <block v-for="(item, index) in datalist" :key="index">
                                        <view class="nav_left_items" :class="index===currentActiveIndex?'active':''"
                                            @tap="clickRootItem" :data-root-item-id="item.id" :data-root-item-index="index">
                                            <view class="before" :style="{background:t('color1')}"></view>
                                            <image :src="item.pic" v-if="item.pic" mode="widthFix"></image>
                                            <view>{{item.name}}</view>
                                            <view class="cartnum" :style="{background:t('color1')}" v-if="numCat[item.id]>0">
                                                {{numCat[item.id]}}
                                            </view>
											<view v-if="numCat[item.id]<= 0 && item.tag" :style="{background:'rgba('+t('color2rgb')+')',color:t('color1')}" class="carttag">{{item.tag}}</view>
                                        </view>
                                    </block>
                                </scroll-view>
                                <view class="nav_right">
                                <view class="nav_right-content">
                                    <scroll-view @scroll="scroll" class="detail-list" :scrollIntoView="scrollToViewId"
                                        :scrollWithAnimation="animation" scroll-y="true"
                                        :class="menuindex>-1?'tabbarbot':''">
                                        <view v-for="(detail, index) in datalist" :key="index"
                                            class="classification-detail-item">
										
											
                                            <view class="head" :data-id="detail.id" :id="'detail-' + detail.id" :style="index==0 && ((ad_pics && ad_pics.length>0) || (ad_pics2 &&  ad_pics2.length>0))?'height:auto;display:block':''">
												
												<!-- 宣传图 -->
												<block v-if="index ==0 && ad_pics_show">
													<swiper v-if="ad_pics_show && ad_pics && ad_pics.length > 0" class="adswiper" style="margin-top: 20rpx;" :autoplay='autoplay1' :current='current1' :interval='interval1' circular skip-hidden-item-layout @change='swiper1Change'>
														<block v-for="(item, index) in ad_pics" :key="index">
															<swiper-item class="swiper-item">
																<image :src="item" ></image>
															</swiper-item>
														</block>
													</swiper>
													
													<swiper  v-if="ad_pics_show && ad_pics2 && ad_pics2.length > 0" class="adswiper2" :autoplay='autoplay2' :current='current2' :interval='interval2' circular skip-hidden-item-layout @change='swiper2Change' style="margin-top: 20rpx;">
														<block v-for="(item, index) in ad_pics2" :key="index">
															<swiper-item class="swiper-item">
																<image :src="item" ></image>
															</swiper-item>
														</block>
													</swiper>
												</block>
												
                                                <view class="txt">{{detail.name}}</view>
                                                <!-- <view class="show-all" @tap="gotoCatproductPage">查看全部<text class="iconfont iconjiantou"></text></view> -->
                                            </view>
                                            <view class="product-itemlist">
                                                <view class="item" v-for="(item,index) in detail.prolist" :key="item.id"
                                                    :class="(item.stock <= 0 || item.stock_daily <= item.sales_daily) ? 'soldout' : ''"
                                                    @tap="toSelect" :data-id="item.id">
                                                    <view class="product-pic" :data-id="item.id" @tap="toDetail">
                                                        <image class="image" :src="item.pic" mode="widthFix" />
                                                        <view class="overlay">
                                                            <view class="text">售罄</view>
                                                        </view>
                                                    </view>
                                                    <view class="product-info">
                                                        <view class="p1"><text>{{item.name}}</text></view>
                                                        <view class="p2">
                                                            <view class="t1" :style="{color:t('color1')}">
                                                              <text style="font-size:20rpx;padding-right:1px">￥</text>{{item.sell_price}}
                                                              <text v-if="item.price_show && item.price_show_text" style="margin: 0 15rpx;font-size: 24rpx;font-weight: 400;">{{item.price_show_text}}</text>
                                                            </view>
                                                            <text class="t2"
                                                                v-if="item.market_price*1 > item.sell_price*1">￥{{item.market_price}}</text>	
                                                        </view>
                                                        <!-- 商品处显示会员价 -->
                                                        <view v-if="item.price_show && item.price_show == 1" style="line-height: 46rpx;">
                                                          <text style="font-size:26rpx">￥{{item.sell_putongprice}}</text>
                                                        </view>
                                                        <view v-if="item.priceshows && item.priceshows.length>0">
                                                          <view v-for="(item2,index2) in item.priceshows" style="line-height: 46rpx;">
                                                            <text style="font-size:26rpx">￥{{item2.sell_price}}</text>
                                                            <text style="margin-left: 15rpx;font-size: 22rpx;font-weight: 400;">{{item2.price_show_text}}</text>
                                                          </view>
                                                        </view>
                                                        <view class="one_buy" v-if="item.one_buy_status && item.one_buy_status==1">单点不送</view> 
                                                        <view class="p3">
                                                            <view class="p3-1" v-if="item.sales>0"><text
                                                                    style="overflow:hidden">已售{{item.sales}}件</text></view>
                                                            <view class="p3-1" v-if="item.limit_start>0"><text
                                                                    style="overflow:hidden">{{item.limit_start}}件起售</text>
                                                            </view>
                                                        </view>
                                                        <!-- <view class="p4" :style="{background:'rgba('+t('color1rgb')+',0.1)',color:t('color1')}" @click.stop="buydialogChange" :data-proid="item.id"><text class="iconfont icon_gouwuche"></text></view> -->
                                                        <view class="addnum"
                                                            v-if="item.stock > 0 && item.stock_daily > item.sales_daily">
                                                            <view v-if="numtotal[item.id]>0" class="minus"
                                                                @tap.stop="addcart" data-num="-1" :data-proid="item.id"
                                                                :data-stock="item.stock" :data-havejl="item.have_jialiao">-</view>
                                                            <text v-if="numtotal[item.id]>0"
                                                                class="i">{{numtotal[item.id]}}</text>
                                                            <view v-if="item.ggcount>1 || item.have_jialiao ==1" class="plus"
                                                                @tap.stop="buydialogChange" :data-proid="item.id"
                                                                :data-stock="item.stock" :data-ggstock="item.gglist[0].stock" :data-stockdaily="item.gglist[0].stock_daily">+</view>
                                                            <view v-else class="plus" @tap.stop="addcart" data-num="1"
                                                                :data-proid="item.id" :data-ggid="item.gglist[0].id"
                                                                :data-stock="item.stock" :data-ggstock="item.gglist[0].stock" :data-stockdaily="item.gglist[0].stock_daily">+</view>
                                                        </view>
                                                    </view>
                                                </view>
                                            </view>
                                        </view>
                                    </scroll-view>
                                    <nodata v-if="nodata"></nodata>
                                </view>
                            </view>
                            </view>
                        </scroll-view>
                    </view>
					<view v-if="st==1" class="content1">
						<view class="item flex-col">
							<text class="t1">联系电话</text>
							<view class="t2"><text v-if="business.tel">{{business.tel}}</text><text
									v-else>暂无</text></view>
						</view>
						<view class="item flex-col">
							<text class="t1">商家地址</text>
							<view class="t2"><text v-if="business.address">{{business.address}}</text><text
									v-else>暂无</text></view>
						</view>
						<view class="item flex-col">
							<text class="t1">商家描述</text>
							<view class="t2"><text v-if="business.desc">{{business.desc}}</text><text
									v-else>暂无</text></view>
						</view>
						<view class="item flex-col">
							<text class="t1">营业时间</text>
							<text class="t2">{{sysset.start_hours}} 至 {{sysset.end_hours}}</text>
						</view>
						<view class="item flex-col" v-if="business.zhengming && business.zhengming.length > 0">
							<text class="t1">证照公示</text>
							<view id="content_picpreview" class="flex t2" style="flex-wrap:wrap;padding-top:20rpx">
								<view v-for="(item, index) in business.zhengming" :key="index" class="layui-imgbox">
									<view class="layui-imgbox-img">
										<image :src="item" @tap="previewImage" :data-url="item" mode="widthFix"></image>
									</view>
								</view>
							</view>
						</view>
					</view>
					<view v-if="st==2" class="content2">
						<view class="comment">
							<block v-if="commentlist.length>0">
								<view v-for="(item, index) in commentlist" :key="index" class="item">
									<view class="f1">
										<image class="t1" :src="item.headimg" />
										<view class="t2">{{item.nickname}}</view>
										<view class="flex1"></view>
										<view class="t3">
											<image class="img" v-for="(item2,index2) in [0,1,2,3,4]" :key="index2"
												:src="pre_url+'/static/img/star' + (item.score>item2?'2native':'') + '.png'" />
										</view>
									</view>
									<view style="color:#777;font-size:22rpx;">{{item.createtime}}</view>
									<view class="f2">
										<text class="t1">{{item.content}}</text>
										<view class="t2">
											<block v-if="item.content_pic!=''">
												<block v-for="(itemp, index) in item.content_pic" :key="index">
													<view @tap="previewImage" :data-url="itemp"
														:data-urls="item.content_pic">
														<image :src="itemp" mode="widthFix" />
													</view>
												</block>
											</block>
										</view>
									</view>
									<view class="f3" v-if="item.reply_content">
										<view class="arrow"></view>
										<view class="t1">商家回复：{{item.reply_content}}</view>
									</view>
								</view>
							</block>
							<block v-else>
								<nodata v-show="comment_nodata"></nodata>
							</block>
						</view>
					</view>
				</view>
			</view>
			<buydialog-restaurant v-if="buydialogShow" :proid="proid" @buydialogChange="buydialogChange" @addcart="afteraddcart"
				:menuindex="menuindex" btntype="1" :needaddcart="false" controller="ApiRestaurantTakeaway"></buydialog-restaurant>
			<view class="footer flex" :class="menuindex>-1?'tabbarbot':''">
				<view class="cart_ico"
					:style="{background:'linear-gradient(0deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'}"
					@tap.stop="handleClickMask">
					<image class="img" :src="pre_url+'/static/img/cart.png'" />
					<view class="cartnum" :style="{background:t('color1')}" v-if="cartList.total>0">
						{{cartList.total}}
					</view>
				</view>
				<view class="text1">合计</view>
				<view class="text2 flex1" :style="{color:t('color1')}"><text
						style="font-size:20rpx">￥</text>{{totalprice}}</view>
				<view v-if="cartList.leftprice > 0" class="op" :style="{background:'#888',width:'220rpx'}">
					差{{cartList.leftprice}}元起送</view>
				<view v-else-if="cartList.is_peisong==0" class="op" :style="{background:'#888',width:'220rpx'}">
					单点不配送</view>
				<view v-else class="op"
					:style="{background:'linear-gradient(270deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'}"
					@tap="gopay">去结算</view>
			</view>
			<view v-if="cartListShow" class="popup__container" style="margin-bottom:100rpx"
				:class="menuindex>-1?'tabbarbot':''">
				<view class="popup__overlay" @tap.stop="handleClickMask" style="margin-bottom:100rpx"
					:class="menuindex>-1?'tabbarbot':''"></view>
				<view class="popup__modal" style="min-height:400rpx;padding:0">
					<view class="popup__title" style="border-bottom:1px solid #EFEFEF">
						<view class="popup__left flex-y-center">打包费 <text>￥{{totalpricePack}}</text></view>
						<text class="popup__title-text"
							style="color:#323232;font-weight:bold;font-size:32rpx">购物车</text>
						<view class="popup__close flex-y-center" @tap.stop="clearShopCartFn"
							style="color:#999999;font-size:24rpx">
							<image :src="pre_url+'/static/img/del.png'" style="width:24rpx;height:24rpx;margin-right:6rpx" />清空
						</view>
					</view>
					<view class="popup__content" style="padding:0">
						<scroll-view scroll-y class="prolist">
							<block v-for="(cart, index) in cartList.list" :key="index">
								<view class="proitem">
									<image :src="cart.guige.pic?cart.guige.pic:cart.product.pic" class="pic flex0">
									</image>
									<view class="con">
										<view class="f1">{{cart.product.name}}</view>
										<view class="f2" v-if="cart.guige.name!='默认规格'">
											{{cart.guige.name}}{{cart.jltitle}}
										</view>
										<view class="f3" style="color:#ff5555;margin-top:10rpx;font-size:28rpx">
											￥{{parseFloat(parseFloat(cart.guige.sell_price) + parseFloat(cart.jlprice)).toFixed(2)}}
										</view>
									</view>
									<view class="addnum">
										<view class="minus">
											<image class="img" :src="pre_url+'/static/img/cart-minus.png'" @tap="addcart"
												data-num="-1" :data-proid="cart.proid" :data-ggid="cart.ggid"
												:data-stock="cart.guige.stock" :data-jltitle="cart.jltitle"
												:data-jlprice="cart.jlprice" :data-jldata="cart.jldata" />
										</view>
										<text class="i">{{cart.num}}</text>
										<view class="plus">
											<image class="img" :src="pre_url+'/static/img/cart-plus.png'" @tap="addcart"
												data-num="1" :data-proid="cart.proid" :data-ggid="cart.ggid"
												:data-stock="cart.guige.stock" :data-jltitle="cart.jltitle"
												:data-jlprice="cart.jlprice" :data-jldata="cart.jldata"/>
										</view>
									</view>
								</view>
							</block>
							<block v-if="!cartList.list.length">
								<text class="nopro">暂时没有商品喔~</text>
							</block>
						</scroll-view>
					</view>
				</view>
			</view>
			<!-- 就餐方式 弹窗 -->
			<view v-if="freighttypeshow" class="popup__container">
				<view class="popup__overlay" @tap.stop="showfreighttype"></view>
				<view class="popup__modal" style="height:320rpx;min-height:320rpx">
					<view class="popup__title">
						<text class="popup__title-text">请选择用餐方式</text>
						<image :src="pre_url+'/static/img/close.png'" class="popup__close" style="width:36rpx;height:36rpx" @tap.stop="showfreighttype"/>
					</view>
					<view class="popup__content">
						<view class="eattype-selct flex-bt">
							<view class="f1" :data-type="'shop'" :style="{borderColor:freighttype=='shop'?t('color1'):'#ccc'} " @tap="selectFreighttype">店内就餐</view>
							<view class="f1" :data-type="'pickup'" :style="{borderColor:freighttype=='pickup'?t('color1'):'#ccc'} " @tap="selectFreighttype">打包自提</view>
							<view class="f1" :data-type="'takeaway'" :style="{borderColor:freighttype=='takeaway'?t('color1'):'#ccc'} " @tap="selectFreighttype">外卖配送</view>
						</view>
					</view>
				</view>
			</view>
			<!-- 就餐方式弹窗 -->
		</block>
		<loading v-if="loading"></loading>
		<dp-tabbar :opt="opt" @getmenuindex="getmenuindex"></dp-tabbar>
		<wxxieyi></wxxieyi>
	</view>
</template>
<script>
	var app = getApp();
	export default {
		data() {
			return {
				opt: {},
				loading: false,
				isload: false,
				menuindex: -1,
				nomore: false,
				nodata: false,
				st: 0,
				cartListShow: false,
				buydialogShow: false,
				harr: [],
				business: {},
				datalist: [],
				cartList: {},
				numtotal: [],
				numCat: {},
				proid: '',
				totalprice: 0.00,
				totalpricePack: 0.0,
				minprice: 0.00,
				currentActiveIndex: 0,
				animation: true,
				scrollToViewId: "",
				commentlist: [],
				comment_nodata: false,
				comment_nomore: false,
				sysset: {},
				latitude: '',
				longitude: '',
				juli: '',
				bid :0,
				scrollState: true,
                banner:'',
				pre_url: app.globalData.pre_url,
				ad_pics_show:false,
				ad_pics:[],
				current1:0,
				autoplay1:true,
				interval1:1000,
				
				ad_pics2:[],
				current2:0,
				autoplay2:true,
				interval2:1000,
				
				freighttype:'',//就餐方式
				freighttypeshow:false,//就餐方式 弹窗
			};
		},
		onLoad: function(opt) {
			this.opt = app.getopts(opt);
			// this.getdata();
			if (this.opt.select_bid || this.opt.bid) {
				this.bid = this.opt.select_bid?this.opt.select_bid:this.opt.bid;;
				app.setCache('select_bid', this.bid);
			} else {
				this.bid = app.getCache('select_bid');
			}
			var cachelongitude = app.getCache('user_current_longitude');
			var cachelatitude = app.getCache('user_current_latitude');
			if(cachelongitude && cachelatitude){
				this.latitude = cachelatitude
				this.longitude = cachelongitude
			}else{
				var that = this;
				app.getLocation(function(res) {
					that.latitude = res.latitude;
					that.longitude = res.longitude;
				});
			}
			if(this.opt.freighttype){
				this.freighttype = this.opt.freighttype;
			}
		},
		onPullDownRefresh: function() {
			this.getdata();
		},
		onShow: function() {
			if(app.globalData.platform=='wx' && app.globalData.hide_home_button==1){
			  uni.hideHomeButton();
			}
			this.getdata();
		},
		onReachBottom: function() {
			if (this.st == 2) {
				if (!this.comment_nodata && !this.comment_nomore) {
					this.pagenum = this.pagenum + 1;
					this.getCommentList(true);
				}
			}
		},
		methods: {
			swiper1Change(e){
				var current = e.detail.current;
				if(current ==2){
					this.interval1 = 500;
				}else if(current ==3){
					this.interval1 = 4000;
				}else{
					this.interval1 =1000;
				}
			},
			swiper2Change(e){
				var current = e.detail.current;
				if(current ==2){
					this.autoplay2 =false;
					let timer = ''
					timer =setInterval(() =>{
						current--;
						this.current2 = current;
						if(current==0){
							clearInterval(timer);
							setTimeout(()=>{
								this.autoplay2 = true;
								
							},2000)
						}
						console.log(current,'current--');
					},2000)
				}
			},
			toBusiness(e){
				var url = '/pagesExt/business/clist2';
				var backurl = encodeURIComponent('/restaurant/takeaway/index');
				app.goto(url+'?isindex=0&backurl='+backurl);
			},
			goSearch: function() {
				var that = this;
				if (that.bid && that.opt.tableId) {
					app.goto('search?bid=' + that.bid);
					return;
				}
				if (that.bid) {
					app.goto('search?bid=' + that.bid);
					return;
				}
				app.goto('search');
			},
			toSelect(e) {
				if (this.sysset.open_restaurant_detail_status == 0) {
					if (!this.buydialogShow) {
						this.proid = e.currentTarget.dataset.id;
					}
					this.buydialogShow = !this.buydialogShow;
				}
			},
			toDetail(e) {
				var id = e.currentTarget.dataset.id;
				if (this.sysset.open_restaurant_detail_status == 1) {
					app.goto('/restaurant/takeaway/product?id=' + id);
				}
			},
			getdata: function() {
				var that = this;
				that.loading = true;
				app.get('ApiRestaurantTakeaway/index', {
					bid: that.bid
				}, function(res) {
					that.loading = false;
					if (res.status == 0) {
						app.alert(res.msg, function() {
							app.goback();
						});
					}
					that.business = res.business
					that.datalist = res.data;
					that.cartList = res.cartList;
					that.numtotal = res.numtotal;
					that.numCat = res.numCat;
					that.sysset = res.sysset;	
					
					if(that.sysset && ( (that.sysset.ad_pics &&  that.sysset.ad_pics.length > 0) || (that.sysset.ad_pics2 && that.sysset.ad_pics2.length > 0))  ){
						that.ad_pics = that.sysset.ad_pics_list;
						that.ad_pics2 = that.sysset.ad_pics_list2;
						setTimeout(function(){
							that.ad_pics_show = true;
						},1000)
					}
                    if(res.banner){
                        that.banner = res.banner
                    }
					uni.setNavigationBarTitle({
						title: that.business.name?that.business.name:that.sysset.diancan_text
					});

					that.totalprice = parseFloat(res.cartList.totalprice);
					that.totalpricePack = parseFloat(res.cartList.totalpricePack);
					that.minprice = parseFloat(res.sysset.min_price);
					//计算每个高度
					var harr = [];
					var clientwidth = uni.getSystemInfoSync().windowWidth;
					var datalist = res.data;
					if (datalist && datalist.length > 0) {
						console.log(datalist.length)
						for (var i = 0; i < datalist.length; i++) {
							var child = datalist[i].prolist;
							console.log(child)
							harr.push(Math.ceil(child.length) * 200 / 750 * clientwidth);
						}
					} else {
						that.nodata = true;
					}
					that.harr = harr;
					that.loaded();
					if (that.sysset.is_loc_business == 1 && that.sysset.mode && that.sysset.mode == 1) {
						
						if (that.business) {
							var juli = that.getDistance(that.longitude, that.latitude, that.business.longitude,
								that.business.latitude);
						} else {
							var juli = that.getDistance(that.longitude, that.latitude, that.sysset.longitude,
								that.sysset.latitude);
						}
						that.juli = juli ? juli + ' km' : '0 km';
					}
				});
			},
			changetab: function(e) {
				this.st = e.currentTarget.dataset.st;
				this.pagenum = 1;
				this.commentlist = [];
				uni.pageScrollTo({
					scrollTop: 0,
					duration: 0
				});
				this.getCommentList();
			},
			getCommentList: function(loadmore) {
				if (!loadmore) {
					this.pagenum = 1;
					this.commentlist = [];
				}
				var that = this;
				var pagenum = that.pagenum;
				var st = that.st;
				that.loading = true;
				that.comment_nodata = false;
				that.comment_nomore = false;
				app.post('ApiRestaurantTakeaway/getdatalist', {
					id: that.bid,
					st: st,
					pagenum: pagenum
				}, function(res) {
					that.loading = false;
					uni.stopPullDownRefresh();
					var data = res.data;
					if (pagenum == 1) {
						that.commentlist = data;
						if (data.length == 0) {
							that.comment_nodata = true;
						}
					} else {
						if (data.length == 0) {
							that.comment_nomore = true;
						} else {
							var commentlist = that.commentlist;
							var newdata = commentlist.concat(data);
							that.commentlist = newdata;
						}
					}
				});
			},
			clickRootItem: function(t) {
				var e = t.currentTarget.dataset;
				this.scrollToViewId = 'detail-' + e.rootItemId;
				this.currentActiveIndex = e.rootItemIndex;
				clearTimeout(this.setTime);
				this.scrollState = false;
				this.setTime = setTimeout(() => {
					this.scrollState = true;
				}, 1000);
			},
			addcart: function(e) {
				var that = this;
				var ks = that.ks;
				var num = e.currentTarget.dataset.num;
				var proid = e.currentTarget.dataset.proid;
				var ggid = e.currentTarget.dataset.ggid;
				var jlprice = e.currentTarget.dataset.jlprice;
				var jltitle = e.currentTarget.dataset.jltitle;
				var jldata =  e.currentTarget.dataset.jldata;
				var ggstock =  e.currentTarget.dataset.ggstock;
				var stockdaily =  e.currentTarget.dataset.stockdaily;
				var  nownum = that.numtotal[proid];
				nownum = parseInt(nownum)+1
				if(nownum > ggstock){
					app.error('库存不足');
					return;
				}
				if(nownum > stockdaily){
					app.error('今日库存不足');
					return;
				}
				if(jldata){
					jldata = JSON.parse(jldata);
				}
				
				var havejl = e.currentTarget.dataset.havejl;
				if(havejl==1 &&  num < 0){
					that.cartListShow = true;
					return;
				}
				that.loading = true;
				app.post('ApiRestaurantTakeaway/addcart', {
					proid: proid,
					ggid: ggid,
					num: num,
					bid: that.bid,
					jlprice: jlprice,
					jltitle: jltitle,
					jldata:jldata
				}, function(res) {
					that.loading = false;
					if (res.status == 1) {
						that.getdata();
					} else {
						app.error(res.msg);
					}
				});
			},
			//加入购物车弹窗后
			afteraddcart: function(e) {
				e.hasoption = false;
				this.addcart({
					currentTarget: {
						dataset: e
					}
				});
			},
			clearShopCartFn: function() {
				var that = this;
				app.post("ApiRestaurantTakeaway/cartclear", {
					bid: that.bid
				}, function(res) {
					that.getdata();
				});
			},
			gopay: function() {
				var cartList = this.cartList.list;
				if (cartList.length == 0) {
					app.alert('请先添加商品到购物车');
					return;
				}
				var prodata = [];
				for (var i = 0; i < cartList.length; i++) {
					prodata.push(cartList[i].proid + ',' + cartList[i].ggid + ',' + cartList[i].num+ ',' + cartList[i].id);
				}
				app.goto('buy?frompage=fastbuy&prodata=' + prodata.join('-')+'&btype=0&freighttype='+this.freighttype);
			},
			gotoCatproductPage: function(t) {
				var e = t.currentTarget.dataset;
				app.goto('prolist?cid=' + e.id);
			},
			scroll: function(e) {
				if (this.scrollState) {
					var scrollTop = e.detail.scrollTop;
					var harr = this.harr;
					var countH = 0;
					for (var i = 0; i < harr.length; i++) {
						if (scrollTop >= countH && scrollTop < countH + harr[i]) {
							this.currentActiveIndex = i;
							break;
						}
						countH += harr[i];
					}
				}
			},
			buydialogChange: function(e) {
				if (!this.buydialogShow) {
					this.proid = e.currentTarget.dataset.proid
				}
				this.buydialogShow = !this.buydialogShow;
			},
			handleClickMask: function() {
				this.cartListShow = !this.cartListShow;
			},
			showfreighttype(){
				this.freighttypeshow = !this.freighttypeshow;
			},
			selectFreighttype(e){
				var freighttype = e.currentTarget.dataset.type;
				this.freighttype = freighttype;
				if(this.freighttype =='shop'){
					app.goto('/restaurant/shop/index?tableId=0&bid='+this.bid+'&mdid='+this.business.mdid);
				}
				this.freighttypeshow = false;
			}
		}
	};
</script>
<style>
	page {
		position: relative;
		width: 100%;
		height: 100%;
		background: #fff;
	}

	.container {
		height: 100vh;
		position: relative;
	}

	.topbannerbg {
		width: 100%;
		height: 264rpx;
		background: #fff;
		position: relative;
	}

	.topbannerbg2 {
		position: absolute;
		width: 100%;
		height: 264rpx;
		background: rgba(0, 0, 0, 0.3);
		top: 0
	}

	.topbanner {
		position: absolute;
		width: 100%;
		display: flex;
		padding: 40rpx 20rpx;
		top: 0
	}

	.topbanner .left {
		width: 160rpx;
		height: 160rpx;
		flex-shrink: 0;
		margin-right: 20rpx
	}

	.topbanner .left .img {
		width: 100%;
		height: 100%;
		border-radius: 50%
	}

	.topbanner .right {
		display: flex;
		flex-direction: column;
		padding: 20rpx 0
	}

	.topbanner .right .f1 {
		font-size: 36rpx;
		font-weight: bold;
		color: #fff
	}

	.topbanner .right .f2 {
		font-size: 22rpx;
		color: #fff;
		opacity: 0.7;
		margin-top: 20rpx;
		display: -webkit-box;
		-webkit-box-orient: vertical;
		-webkit-line-clamp: 1;
		overflow: hidden;
		line-height: 30rpx;
	}

	.topbanner .right .f3 {
		width: 100%;
		display: flex;
		padding-right: 20rpx;
		margin-top: 10rpx
	}

	.topbanner .right .f3 .t2 {
		display: flex;
		align-items: center;
		font-size: 24rpx;
		color: rgba(255, 255, 255, 0.9)
	}

	.topbanner .right .f3 .img {
		width: 32rpx;
		height: 32rpx;
		margin-left: 10rpx
	}

	.navtab {
		display: flex;
		width: 100%;
		height: 110rpx;
		background: #fff;
		position: relative;
		padding: 0 50rpx;
		border-radius: 24rpx 24rpx 0 0;
	}

	.navtab .item {
		flex: 1;
		font-size: 28rpx;
		text-align: center;
		color: #222222;
		height: 110rpx;
		line-height: 110rpx;
		overflow: hidden;
		position: relative
	}

	.navtab .item .after {
		display: none;
		position: absolute;
		left: 50%;
		margin-left: -20rpx;
		bottom: 20rpx;
		height: 4px;
		border-radius: 2px;
		width: 40rpx
	}

	.navtab .on {
		font-size: 30rpx;
		font-weight: bold
	}

	.navtab .on .after {
		display: block
	}

	.body {
		margin-top: -30rpx;
		z-index: 5;
	}

	.body_status {
		padding: 0 0 env(safe-area-inset-bottom) 0;
	}

	.content1 .item {
		display: flex;
		flex-direction: column;
		width: 100%;
		padding: 0 40rpx;
		margin-top: 40rpx
	}

	.content1 .item:last-child {
		border-bottom: 0;
	}

	.content1 .item .t1 {
		width: 200rpx;
		color: #2B2B2B;
		font-weight: bold;
		font-size: 30rpx;
		height: 60rpx;
		line-height: 60rpx
	}

	.content1 .item .t2 {
		color: #2B2B2B;
		font-size: 24rpx;
		line-height: 30rpx
	}

	.content2 .comment {
		padding: 0 10rpx
	}

	.content2 .comment .item {
		background-color: #fff;
		padding: 10rpx 20rpx;
		display: flex;
		flex-direction: column;
	}

	.content2 .comment .item .f1 {
		display: flex;
		width: 100%;
		align-items: center;
		padding: 10rpx 0;
	}

	.content2 .comment .item .f1 .t1 {
		width: 70rpx;
		height: 70rpx;
		border-radius: 50%;
	}

	.content2 .comment .item .f1 .t2 {
		padding-left: 10rpx;
		color: #333;
		font-weight: bold;
		font-size: 30rpx;
	}

	.content2 .comment .item .f1 .t3 {
		text-align: right;
	}

	.content2 .comment .item .f1 .t3 .img {
		width: 24rpx;
		height: 24rpx;
		margin-left: 10rpx
	}

	.content2 .comment .item .score {
		font-size: 24rpx;
		color: #f99716;
	}

	.content2 .comment .item .score image {
		width: 140rpx;
		height: 50rpx;
		vertical-align: middle;
		margin-bottom: 6rpx;
		margin-right: 6rpx;
	}

	.content2 .comment .item .f2 {
		display: flex;
		flex-direction: column;
		width: 100%;
		padding: 10rpx 0;
	}

	.content2 .comment .item .f2 .t1 {
		color: #333;
		font-size: 28rpx;
	}

	.content2 .comment .item .f2 .t2 {
		display: flex;
		width: 100%
	}

	.content2 .comment .item .f2 .t2 image {
		width: 100rpx;
		height: 100rpx;
		margin: 10rpx;
	}

	.content2 .comment .item .f2 .t3 {
		color: #aaa;
		font-size: 24rpx;
	}

	.content2 .comment .item .f2 .t3 {
		color: #aaa;
		font-size: 24rpx;
	}

	.content2 .comment .item .f3 {
		width: 100%;
		padding: 10rpx 0;
		position: relative
	}

	.content2 .comment .item .f3 .arrow {
		width: 16rpx;
		height: 16rpx;
		background: #eee;
		transform: rotate(45deg);
		position: absolute;
		top: 0rpx;
		left: 36rpx
	}

	.content2 .comment .item .f3 .t1 {
		width: 100%;
		border-radius: 10rpx;
		padding: 10rpx;
		font-size: 22rpx;
		color: #888;
		background: #eee
	}


	.view-show {
		width: 100%;
		height: 100%;
	}

	.search-container {
		width: 100%;
		height: 94rpx;
		padding: 16rpx 23rpx 14rpx 23rpx;
		background-color: #fff;
		position: relative;
		overflow: hidden;
		border-bottom: 1px solid #f5f5f5
	}

	.search-box {
		display: flex;
		align-items: center;
		height: 60rpx;
		border-radius: 30rpx;
		border: 0;
		background-color: #f7f7f7;
		flex: 1
	}

	.search-box .img {
		width: 24rpx;
		height: 24rpx;
		margin-right: 10rpx;
		margin-left: 30rpx
	}

	.search-box .search-text {
		font-size: 24rpx;
		color: #C2C2C2;
		width: 100%;
	}

	.nav_left {
		width: 25%;
		height: 100%;
		background: #F6F6F6;
		overflow-y: scroll;
	}

	.nav_left .nav_left_items {
		line-height: 50rpx;
		color: #999999;
		border-bottom: 0px solid #E6E6E6;
		font-size: 24rpx;
		position: relative;
		border-right: 0 solid #E6E6E6;
		padding: 25rpx 30rpx;
		text-align: center;
	}

	.nav_left .nav_left_items.active {
		background: #fff;
		color: #222222;
		font-size: 28rpx;
		font-weight: bold
	}
	
	.nav_left .nav_left_items image {
		height: 40rpx;
		width: 40rpx;
		display: block;
		margin: 0 auto;
	}

	.nav_left .nav_left_items .before {
		display: none;
		position: absolute;
		top: 50%;
		margin-top: -22rpx;
		left: 0rpx;
		height: 44rpx;
		border-radius: 4rpx;
		width: 6rpx
	}

	.nav_left .nav_left_items.active .before {
		display: block
	}

	.nav_left .nav_left_items .cartnum {
		position: absolute;
		top: 8rpx;
		right: 8rpx;
		width: 36rpx;
		height: 36rpx;
		border: 1px solid #fff;
		border-radius: 50%;
		display: flex;
		align-items: center;
		justify-content: center;
		overflow: hidden;
		font-size: 18rpx;
		color: #fff
	}
	
	.nav_left .nav_left_items .carttag {
		position: absolute;
		top: 10rpx;
		right: -8rpx;
		line-height: 30rpx;
		padding: 0 15rpx;
		border-radius: 50rpx;
		background: #ffebea;
		color: #c95e3e;
		font-weight: normal;
		font-size: 17rpx;
	}

	.nav_right {
		width: 75%;
		height: 100%;
		display: flex;
		flex-direction: column;
		background: #fff;
		box-sizing: border-box;
	}

	.nav_right-content {
		background: #ffffff;
		padding: 20rpx 10rpx 0 20rpx;
		height: 100%;
		position: relative
	}

	.detail-list {
		height: 100%;
		overflow: scroll
	}

	.classification-detail-item {
		width: 100%;
		overflow: visible;
		background: #fff
	}

	.classification-detail-item .head {
		height: 82rpx;
		width: 100%;
		display: flex;
		align-items: center;
		justify-content: space-between;
	}

	.classification-detail-item .head .txt {
		color: #222222;
		font-weight: bold;
		font-size: 28rpx;
	}

	.classification-detail-item .head .show-all {
		font-size: 22rpx;
		color: #949494;
		display: flex;
		align-items: center
	}

	.product-itemlist {
		height: auto;
		position: relative;
		overflow: hidden;
		padding: 0px;
		display: flex;
		flex-wrap: wrap
	}

	.product-itemlist .item {
		width: 100%;
		display: inline-block;
		position: relative;
		margin-bottom: 12rpx;
		background: #fff;
		display: flex;
		padding: 14rpx 0;
		border-radius: 10rpx;
		border-bottom: 1px solid #F8F8F8
	}

	.product-itemlist .product-pic {
		width: 30%;
		height: 0;
		overflow: hidden;
		background: #ffffff;
		padding-bottom: 30%;
		position: relative;
		border-radius: 4px;
	}

	.product-itemlist .product-pic .image {
		position: absolute;
		top: 0;
		left: 0;
		width: 100%;
		height: auto
	}

	.product-itemlist .product-pic .saleimg {
		position: absolute;
		width: 120rpx;
		height: auto;
		top: -6rpx;
		left: -6rpx;
	}

	.product-itemlist .product-info {
		width: 70%;
		padding: 0 10rpx 5rpx 20rpx;
		position: relative;
	}

	.product-itemlist .product-info .p1 {
		color: #323232;
		font-weight: bold;
		font-size: 28rpx;
		line-height: 30rpx;
		margin-bottom: 0;
		display: -webkit-box;
		-webkit-box-orient: vertical;
		-webkit-line-clamp: 2;
		overflow: hidden;
		height: 60rpx
	}

	.product-itemlist .product-info .p2 {
		margin-top: 10rpx;
		height: 36rpx;
		line-height: 36rpx;
		overflow: hidden;
	}

	.product-itemlist .product-info .p2 .t1 {
		font-size: 32rpx;
	}

	.product-itemlist .product-info .p2 .t2 {
		margin-left: 10rpx;
		font-size: 24rpx;
		color: #aaa;
		text-decoration: line-through;
		/*letter-spacing:-1px*/
	}

	.product-itemlist .product-info .p3 {
		display: flex;
		align-items: center;
		overflow: hidden;
		margin-top: 10rpx
	}

	.product-itemlist .product-info .p3-1 {
		font-size: 20rpx;
		height: 30rpx;
		line-height: 30rpx;
		text-align: right;
		color: #999;
		margin-left: 6rpx;
	}

	.product-itemlist .product-info .p3-1:nth-child(1) {
		margin: 0;
	}

	.product-itemlist .product-info .p4 {
		width: 48rpx;
		height: 48rpx;
		border-radius: 50%;
		position: absolute;
		display: relative;
		bottom: 6rpx;
		right: 4rpx;
		text-align: center;
	}

	.product-itemlist .product-info .p4 .icon_gouwuche {
		font-size: 28rpx;
		height: 48rpx;
		line-height: 48rpx
	}

	.product-itemlist .addnum {
		position: absolute;
		right: 10rpx;
		bottom: 20rpx;
		font-size: 30rpx;
		color: #666;
		width: auto;
		display: flex;
		align-items: center
	}

	.product-itemlist .addnum .plus {
		width: 40rpx;
		height: 40rpx;
		background: #FD4A46;
		color: #FFFFFF;
		border-radius: 50%;
		display: flex;
		align-items: center;
		justify-content: center;
		font-size: 28rpx
	}

	.product-itemlist .addnum .minus {
		width: 40rpx;
		height: 40rpx;
		background: #FFFFFF;
		color: #FD4A46;
		border: 1px solid #FD4A46;
		border-radius: 50%;
		display: flex;
		align-items: center;
		justify-content: center;
		font-size: 28rpx
	}

	.product-itemlist .addnum .img {
		width: 24rpx;
		height: 24rpx
	}

	.product-itemlist .addnum .i {
		padding: 0 20rpx;
		color: #999999;
		font-size: 28rpx
	}

	.overlay {
		background-color: rgba(0, 0, 0, .5);
		position: absolute;
		width: 60%;
		height: 60%;
		border-radius: 50%;
		display: none;
		top: 20%;
		left: 20%;
	}

	.overlay .text {
		color: #fff;
		text-align: center;
		transform: translateY(100%);
	}

	.product-itemlist .soldout .product-pic .overlay {
		display: block;
	}

	.prolist {
		max-height: 620rpx;
		min-height: 320rpx;
		overflow: hidden;
		padding: 0rpx 20rpx;
		font-size: 28rpx;
		border-bottom: 1px solid #e6e6e6;
	}

	.prolist .nopro {
		text-align: center;
		font-size: 26rpx;
		display: block;
		margin: 80rpx auto;
	}

	.prolist .proitem {
		position: relative;
		padding: 10rpx 0;
		display: flex;
		border-bottom: 1px solid #eee
	}

	.prolist .proitem .pic {
		width: 120rpx;
		height: 120rpx;
		margin-right: 20rpx;
	}

	.prolist .proitem .con {
		padding-right: 180rpx;
		padding-top: 10rpx
	}

	.prolist .proitem .con .f1 {
		color: #323232;
		font-size: 26rpx;
		line-height: 32rpx;
		margin-bottom: 10rpx;
		margin-top: -6rpx;
		display: -webkit-box;
		-webkit-box-orient: vertical;
		-webkit-line-clamp: 2;
		overflow: hidden;
	}

	.prolist .proitem .con .f2 {
		font-size: 24rpx;
		line-height: 28rpx;
		color: #999;
		display: -webkit-box;
		-webkit-box-orient: vertical;
		-webkit-line-clamp: 1;
		overflow: hidden;
	}

	.prolist .proitem .addnum {
		position: absolute;
		right: 20rpx;
		bottom: 50rpx;
		font-size: 30rpx;
		color: #666;
		width: auto;
		display: flex;
		align-items: center
	}

	.prolist .proitem .addnum .plus {
		width: 48rpx;
		height: 36rpx;
		background: #F6F8F7;
		display: flex;
		align-items: center;
		justify-content: center
	}

	.prolist .proitem .addnum .minus {
		width: 48rpx;
		height: 36rpx;
		background: #F6F8F7;
		display: flex;
		align-items: center;
		justify-content: center
	}

	.prolist .proitem .addnum .img {
		width: 24rpx;
		height: 24rpx
	}

	.prolist .proitem .addnum .i {
		padding: 0 20rpx;
		color: #2B2B2B;
		font-weight: bold;
		font-size: 24rpx
	}

	.prolist .tips {
		font-size: 22rpx;
		color: #666;
		text-align: center;
		line-height: 56rpx;
		background: #f5f5f5;
	}

	.footer {
		width: 100%;
		background: #fff;
		margin-top: 5px;
		position: fixed;
		left: 0px;
		bottom: 0px;
		z-index: 8;
		display: flex;
		align-items: center;
		padding: 0 20rpx;
		border-top: 1px solid #EFEFEF
	}

	.footer .cart_ico {
		width: 64rpx;
		height: 64rpx;
		border-radius: 10rpx;
		display: flex;
		align-items: center;
		justify-content: center;
		position: relative
	}

	.footer .cart_ico .img {
		width: 36rpx;
		height: 36rpx;
	}

	.footer .cart_ico .cartnum {
		position: absolute;
		top: -17rpx;
		right: -17rpx;
		width: 34rpx;
		height: 34rpx;
		border: 1px solid #fff;
		border-radius: 50%;
		display: flex;
		align-items: center;
		justify-content: center;
		overflow: hidden;
		font-size: 20rpx;
		font-weight: bold;
		color: #fff
	}

	.footer .text1 {
		height: 100rpx;
		line-height: 100rpx;
		color: #555555;
		font-weight: bold;
		font-size: 30rpx;
		margin-left: 40rpx;
		margin-right: 10rpx
	}

	.footer .text2 {
		font-size: 32rpx;
		font-weight: bold
	}

	.footer .op {
		width: 200rpx;
		height: 72rpx;
		line-height: 72rpx;
		border-radius: 36rpx;
		font-weight: bold;
		color: #fff;
		font-size: 28rpx;
		text-align: center
	}

	.layui-imgbox {
		margin-right: 16rpx;
		margin-bottom: 10rpx;
		font-size: 24rpx;
		position: relative;
	}
	.layui-imgbox-img {
		display: block;
		width: 200rpx;
		height: 200rpx;
		padding: 2px;
		border: #d3d3d3 1px solid;
		background-color: #f6f6f6;
		overflow: hidden
	}

	.layui-imgbox-img>image {
		max-width: 100%;
	}

	.popup__left {
		position: absolute;
		top: 36rpx;
		left: 34rpx;
		color: #999999;
		font-size: 24rpx
	}

	.popup__left text {
		color: #ff5555;
	}

	.header {
		position: relative;
		padding: 30rpx;
	}

	.header_title {
		font-size: 28rpx;
		color: #333;
	}

	.header_detail {
		height: 30rpx;
		width: 30rpx;
		margin-left: 10rpx;
	}

	.header_serach {
		height: 35rpx;
		width: 35rpx;
	}

	.header_address {
		font-size: 24rpx;
		color: #999;
		margin-top: 20rpx;
	}

	.shop_title {
		font-weight: 700;
		font-size: 35rpx;
	}
    
    .banner{position:relative;background:#fff; height: 220rpx; padding: 20rpx 0; width: 100%}
    .banner .wc{width: 700rpx;margin: 0 auto;}
    .banner .head_img_view{ padding: 0; height: 220rpx; margin-bottom: 10rpx; overflow: hidden;} 
    .banner .head_img_swiper{overflow: hidden;  height: 220rpx;}
    .banner .head_img{width: 100%; border-radius: 10rpx;height: 200rpx; }	
	.one_buy{border: 1px solid #bdbdbd;border-radius: 8rpx;width: 100rpx;font-size: 20rpx;color: #757575;text-align: center;margin-top: 2rpx;}

	.adswiper .swiper-item{border-radius: 20rpx;overflow: hidden;}
	.adswiper2 .swiper-item{border-radius: 20rpx;overflow: hidden;}
	.adswiper2{margin-bottom: 20rpx;}
/* 就餐方式弹窗 */
.eattype-selct{width: 94%;margin: 20rpx 3%;}
.eattype-selct .f1{
	border: 1px solid #ccc;
	width: 220rpx;
	height: 80rpx;
	line-height: 80rpx;
	text-align: center;
	border-radius: 15rpx;
	font-weight: 700;
}
.freight_type{background-color: #C2C2C2;border-radius: 30rpx;width: 160rpx;height: 55rpx;position: absolute;right: 10rpx;top: 120rpx;font-size: 24rpx;color: #424242;z-index: 2;text-align: center;}
.freight_type .f1{line-height: 55rpx;text-align: center;width: 50%;}
.freight_type .on{color: #fff;border-radius: 30rpx;width: 85rpx;}
</style>
