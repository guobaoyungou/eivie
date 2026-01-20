<template>
	<view>
		<block v-if="isload">
			
			<!-- #ifndef H5 || APP-PLUS -->
				<view class="navigation">
					<view class='navcontent' :style="{marginTop:navigationMenu.top+'px',width:(navigationMenu.right)+'px'}">
						<view class="header-location-top" :style="{height:navigationMenu.height+'px'}">
							<view class="header-back-but" @tap="goback">
								<image :src="`${pre_url}/static/img/hotel/fanhui.png`"></image>
							</view>
							<!-- <view class="header-page-title" style="color:#fff;">{{text['酒店']}}详情</view> -->
						</view>
					</view>
				</view>
			<!--  #endif -->
				
				<!--  -->
				<view class='dp-banner'>
					<swiper class="dp-banner-swiper" :autoplay="true" :indicator-dots="false" :current="0" :circular="true" :interval="3000">
						<block v-for="(item,index) in photolist" :key="index"> 
							<swiper-item>
								<view @click="viewPicture(photolist,item)">
									<image :src="item" class="dp-banner-swiper-img" mode="widthFix"/>
								</view>
							</swiper-item>
						</block>
					</swiper>
				</view>
				<!--  -->
				<view class="position-view">
					<view class="banner-tab-view">
						<scroll-view scroll-x style="width: auto;white-space: nowrap;margin-top: 7.5rpx;">
							<block v-for="(item,index) in photos">
								<view :class="'tab-options-banner '+(index==pindex?'tab-options-banner-active':'')" @tap="chanagePhoto" :data-index='index'>{{item.name}}</view>
							</block>
						</scroll-view>
						<!--<view class='tab-options-banner'>公共区域</view>
						<view class='tab-options-banner tab-options-banner-active'>封面</view>
						<view class='tab-options-banner '>大楼外部</view>-->
					</view>
					<view class="content-view" :style="{background: 'linear-gradient(180deg, '+ tColor('color1') +' 0%, #FFFFFF 45px, #FFFFFF 63%, #FFFFFF 100%)'}">
						<view class="title-view">{{hotel.name}}</view>
						<view class="hotel-nature">
							<view class="star-view" v-if="hotel.hotellevel>0">
								<image class="start"  :src="pre_url+'/static/img/star2native.png'" v-for="(item,index) in hotel.hotellevel+2"></image>
							</view>
							<view class="hotspot-nature-text" v-if="nature != null">{{ nature }}</view>
						</view>
						<view class="hotspot-view" v-if="hotel.tag.length">
							<view class="hotspot-view-left">
								<view class='hotspot-options' v-for="(item,index) in hotel.tag">
									{{item}}
								</view>
							</view>
							<!--<view class="hotspot-more">
								设施/详情
								<image :src="`${pre_url}/static/img/hotel/gengduo.png`"></image>
							</view>-->
						</view>
						<view class="address-view">
							<view style="flex: 1;">
								<view class="address-text">{{hotel.address}}</view>
								<view class="address-traffic"><image :src="`${pre_url}/static/img/hotel/address.png`"></image>距您{{hotel.juli}} </view>
							</view>
							<view class="fangshi-view">
								<view class="fagnshi-options" @tap="openLocation" :data-latitude="hotel.latitude" :data-longitude="hotel.longitude" :data-company="hotel.name" :data-address="hotel.address" >
									<image :src="`${pre_url}/static/img/hotel/dingwei.png`"></image>
									<view>导航</view>
								</view>
								<!--<view class="fagnshi-options">
									<image :src="`${pre_url}/static/img/hotel/dache.png`"></image>
									<view>打车</view>
								</view>-->
							</view>
						</view>
						<!--  -->
						<view class="time-view flex flex-y-center flex-bt" @tap="selectDate">
							<view class="time-options flex flex-y-center flex-bt">
								<view class="month-tetx">{{startDate}}</view>
								<view class="day-tetx">{{startWeek}}入住</view>
							</view>
							<view class="content-text">
								<view class="content-decorate left-c-d"></view>
								共{{dayCount}}晚
								<view class="content-decorate right-c-d"></view>
							</view>
							<view class="time-options flex flex-y-center flex-bt">
								<view class="month-tetx">{{endDate}}</view>
								<view class="day-tetx">{{endWeek}}离店</view>
							</view>
						</view>
					</view>
					<!--  -->
					<view class="screen-view">
						<view class="screen-view-left">
							<block v-for="(item,index) in grouplist">
								<view :class="'screen-options '" :style="inArray(item.id,gids) ?'background:rgba('+t('color1rgb')+',0.05);color:'+tColor('color1'):''"  :data-id="item.id"  @tap="groupChange">{{item.name}}</view>
							</block>
						</view>
						<!--<view class="right-screen">
							筛选<image :src="`${pre_url}/static/img/hotel/screenicon.png`"></image>
						</view>-->
					</view>
					<!--  -->
					<view class="hotels-list">
						<block v-for="(item,index) in roomlist" >
							<view class="hotels-options" @tap.stop="hotelDetail" :data-id='item.id'>
								<view class="hotel-img">
									<image :src="item.pic" v-if="roomstyle ==1"></image>
									<image class="fangxing" :src="item.pic" v-else></image>

								</view>
								<view class="hotel-info">
									<view class="hotel-title">{{item.name}}</view>
									<view class="hotel-characteristic" v-if="roomstyle ==1">
										<block v-for="(items,indexs) in item.tag">
											<view class="characteristic-options" :style="'background:rgba('+t('color1rgb')+',0.05);color:'+tColor('color1')">{{items}}</view>
										</block>
									</view> 
									<view class="hotel-characteristic " v-else>
										<view class="under_title" v-if="item.bedxing!='不显示'">
											 
											<view class="options-title">{{item.bedxing}}</view>
										</view>
										<view class="under_title">
										 
											<view class="options-title">{{item.square}}m²</view>
										</view>
									 
						
										<view class="under_title" v-if="item.ischuanghu!='不显示'">
									 
											<view class="options-title">{{item.ischuanghu}}</view>
										</view>
										<view class="under_title" v-if="item.breakfast!='不显示'">
										 
											<view class="options-title">{{item.breakfast}}早餐</view>
										</view>
									</view>
									
									
									<view class="hotel-but-view">
										<view class="make-info">
											<view class="hotel-price" :style="{color:t('color1')}" v-if="item.daymoney">
												<view class="hotel-price-num">{{item.daymoney}}{{moneyunit}}</view>
												<view>/晚起</view>
											</view>
											<view class="hotel-price" :style="{color:t('color1')}" v-else>
												<view>￥</view>
												<view class="hotel-price-num">{{item.sell_price}}</view>
												<view>起</view>
											</view>
											<view class="hotel-text" v-if="roomstyle == 1">{{item.sales}}人已预定 | 剩{{item.stock}}{{text['间']}}可订</view>
											<view class="hotel-text" v-else>{{item.sales}}人已预定</view>
										</view>
										<block  v-if="btnstyle == 1">
											<view class="hotel-make" v-if="item.stock>0 && !item.isbooking"  :style="'background:rgba('+t('color1rgb')+',0.8);color:#FFF'">预约</view>
											<view class="hotel-make" v-else-if="item.isbooking" style="background:#999999; color:#fff">不可订</view>
											<view class="hotel-make" v-else style="background:#999999; color:#fff">已满</view>
										</block>
									</view>
									<view v-if="btnstyle == 2">
										<view v-if="item.stock>0 && !item.isbooking" class="hotel-make-new" :style="{borderColor:t('color1')}">									 
											<view class="make-new-view" :style="'background:'+t('color1')+''">订</view>
											<view class="make-new-view2" :style="{color:t('color1'),borderColor:t('color1')}">剩{{item.stock}}{{text['间']}}</view>									 
										</view>
										<view  v-else-if="item.isbooking"  class="hotel-make-new" style="background:#999999;border: 1px #999999 solid; color:#fff">									 
											<view class="make-new-view" style="background:#999999; color:#fff">不可订</view>
											<view class="make-new-view2" style="background:#999999;border-bottom: 1px #999999 solid; color:#fff">剩{{item.stock}}{{text['间']}}</view>									 
										</view>
										<view  v-else class="hotel-make-new" style="background:#999999;border: 1px #999999 solid; color:#fff">							 
											<view class="make-new-view" style="background:#999999; color:#fff">已满</view>
											<view class="make-new-view2" style="background:#999999;border-bottom: 1px #999999 solid; color:#fff">剩{{item.stock}}{{text['间']}}</view>									 
										</view>
								    </view>
								</view>
							</view>
						</block>
					</view>
					<nomore text="没有更多了" v-if="nomore"></nomore>
					<nodata text="没有查找到相关房型" v-if="nodata"></nodata>
					<!--  -->
					<view class="hotel-more"  :style="{color:t('color1')}"  v-if="totalpagenum>pagenum" @tap="showmore">
						查看剩余房型
						<image :src="`${pre_url}/static/img/hotel/mroe-hotel.png`"></image>
					</view>
					<!-- 酒店设施 -->
					<view class="work-order-view flex flex-col">
						<view class="work-order-title">
							<view class="work-order-title-left">配套设施</view>
							<!--<view class="work-order-title-right">
								更多详情<image :src="`${pre_url}/static/img/hotel/rightjiantou.png`"></image>
							</view>-->
						</view>
						<view class="facilities-view flex ">
							<block v-for="(item,index) in hotel.ptsheshi">
								<view class="options-facilities flex flex-col">
									<image :src="item.icon"></image>
									<view class="facilities-text">{{item.text}}</view>
								</view>
							</block>
						</view>
					</view>
					<!-- 订房必读 -->
					<view class="work-order-view flex flex-col">
						<view class="work-order-title">
							<view class="work-order-title-left">订房须知</view>
							<!--<view class="work-order-title-right">
								全部政策<image :src="`${pre_url}/static/img/hotel/rightjiantou.png`"></image>
							</view>-->
						</view>
						<view class="required-reading-view">
										<parse :content="hotel.content" @navigate="navigate"></parse>
						</view>
					</view>
					<!-- 评价 -->
					<view class="work-order-view flex flex-col" v-if="hotel.comment == 1">
						<view class="work-order-title">
							<view class="work-order-title-left">住客评价</view>
							<view class="work-order-title-right" @tap="goto" :data-url="'/hotel/order/commentlist?hotelid='+hotel.id"><image :src="`${pre_url}/static/img/hotel/rightjiantou.png`"></image></view>
						</view>
						<view class="score-view" v-if="commentlist.length>0">
							<view class="score-content flex flex-y-center flex-bt">
								<view class="total-score-view flex flex-col">
									<view class="total-score-num flex flex-y-center" :style="{color:t('color1')}">
										<view class='total-num'>{{hotel.comment_score}}</view>
										<view class="tatal-describe">{{haoping}}</view>
									</view>
								</view>
							</view>
							
							<view class="evaluate-list-view flex flex-col" >
								<block v-for="(item,index) in commentlist" >
									<view class="text-options flex flex-col">
										<view class="user-info-view flex flex-y-center flex-bt">
											<view class="info-view flex flex-y-center">
												<image class="avater-view" :src="item.headimg"></image>
												<view class="name-view flex flex-col">
													<view class="name-text">{{item.nickname}}</view>
													<view class="time-view">{{item.createtime}}</view>
												</view>
											</view>
											<view class="scoring-view flex flex-xy-center" :style="'background:rgba('+t('color1rgb')+',0.08);color:'+t('color1')">
												<view>{{item.score}}</view>
												<view style="font-size: 20rpx;margin-left:5rpx;">分</view>
											</view>
										</view>
			
										<view class="evaluate-imgList flex flex-y-center">
											<block v-for="(pic,index) in item.content_pic">
												<view class="img-options">
													<image :src="pic"></image>
												</view>
											</block>
										</view>
										<view class="evaluate-value">
											{{item.content}}
										</view>
									</view>
								</block>
							</view>
						</view>
						<view v-else class="">
								<nodata text="没有查找到记录"></nodata>
						</view>

					</view>
				</view>
				
				
				<!-- 详情弹窗 -->
				<uni-popup id="popup" ref="popup" type="bottom" >
					<view class="popup__content" style="bottom: 0;padding-top:0;padding-bottom:0; max-height: 86vh; ">
						<view class="popup-close" @click="popupdetailClose">
							<image :src="`${pre_url}/static/img/hotel/popupClose.png`"></image>
						</view>
						<scroll-view scroll-y style="height: auto;max-height: 90vh;">
							<view class="popup-banner-view" style="height: 450rpx;">
								<swiper class="dp-banner-swiper" :autoplay="true" :indicator-dots="false" :current="0" :circular="true" :interval="3000" @change='swiperChange'>
									<block v-for="(item,index) in room.pics" :key="index"> 
										<swiper-item>
											<view @click="viewPicture(room.pics,item)">
												<image :src="item" class="dp-banner-swiper-img" mode="widthFix"/>
											</view>
										</swiper-item>
									</block>
								</swiper>
								<view class="popup-numstatistics flex flex-xy-center" v-if='bannerList.length'>
									{{bannerindex}} / {{bannerList.length}}
								</view>
							</view>
							<view class="hotel-details-view flex flex-col">
								<view class="hotel-title">{{room.name}}</view>
								<view class="introduce-view flex ">
									<view class="options-intro flex flex-y-center" v-if="room.bedxing!='不显示'">
										<image :src="pre_url+'/static/img/hotel/dachuang.png'"></image>
										<view class="options-title">{{room.bedxing}}</view>
									</view>
									<view class="options-intro flex flex-y-center">
										<image :src="pre_url+'/static/img/hotel/pingfang.png'"></image>
										<view class="options-title">{{room.square}}m²</view>
									</view>
									<view class="options-intro flex flex-y-center">
										<image :src="pre_url+'/static/img/hotel/chuangkuan.png'"></image>
										<view class="options-title">{{room.bedwidth}}米</view>
									</view>
					
									<view class="options-intro flex flex-y-center" v-if="room.ischuanghu!='不显示'">
										<image :src="pre_url+'/static/img/hotel/youchuang.png'"></image>
										<view class="options-title">{{room.ischuanghu}}</view>
									</view>
									<view class="options-intro flex flex-y-center" v-if="room.breakfast!='不显示'">
										<image :src="pre_url+'/static/img/hotel/zaocan.png'"></image>
										<view class="options-title">{{room.breakfast}}早餐</view>
									</view>
								</view>
								<view class="other-view flex flex-y-center">
									<view class="other-title">特色</view>
									
									<view class="other-text" style="white-space: pre-line;">{{room.tese}}</view>
								</view>
								<view class="other-view flex flex-y-center">
									<view class="other-title">房型详情</view>
									<dp :pagecontent="pagecontent"></dp>
								</view>
									
							</view>
							<!-- 酒店权益 -->
							<view class="hotel-equity-view flex flex-col" v-if="qystatus == 1">
								<view class="equity-title-view flex">
									<view class="equity-title">{{ qyname }}</view>
									<!--<view class="equity-title-tisp">填写订单时兑换</view>-->
								</view>
							
								<view class="equity-options flex flex-col">
										<parse :content="hotel.hotelquanyi" @navigate="navigate"></parse>
								</view>
								
							</view>
							<!-- 政策服务 -->
							<view class="hotel-equity-view flex flex-col"  v-if="fwstatus == 1">
								<view class="equity-title-view flex">
									<view class="equity-title">{{ fwname }}</view>
								</view>
								<view class="equity-options flex flex-col">
										<parse :content="hotel.hotelfuwu" @navigate="navigate"></parse>
								</view>
							</view>
						
							<!-- 费用明细 -->
							<view class="hotel-equity-view flex flex-col">
								<view class="equity-title-view flex">
									<view class="equity-title">费用明细</view>
								</view>
								<view class="cost-details flex flex-col">
									<view class="price-view flex flex-bt flex-y-center">
										<view class="price-text">押金（可退）</view>
										<view class="price-num">￥{{yajin}}</view>
									</view>
									<view class="price-view flex flex-bt flex-y-center">
										<view class="price-text">{{text['服务费']}}</view>
										<view class="price-num">￥{{service_money}}/天</view>
									</view>
									<view class="price-view flex flex-bt flex-y-center" v-if="room.isdaymoney==1">
										<view class="price-text">房费</view>
										<view class="price-num">{{room.daymoney}}{{moneyunit}}/晚</view>
									</view>
									<view class="price-view flex flex-bt flex-y-center" v-else>
										<view class="price-text">房费</view>
										<view class="price-num">￥{{room.price}}/晚</view>
									</view>
					
									<view class="price-view flex flex-y-center" style="justify-content: flex-end;margin-top: 30rpx;margin-bottom: 0rpx;align-items: center;padding-bottom: 0rpx;">
										<view class="price-text" style="font-size: 28rpx;margin-right: 15rpx;">每日金额</view>
										<view class="price-num flex flex-y-center"  v-if="room.isdaymoney==1">
											<view style="font-size: 24rpx;font-weight: none;margin-top: 5rpx;">￥</view>
											<view style="font-size: 44rpx;">{{totalprice}}+{{room.daymoney}}{{moneyunit}}</view>		
										</view>
										<view class="price-num flex flex-y-center"  v-else>
											<view style="font-size: 24rpx;font-weight: none;margin-top: 5rpx;">￥</view>
											<view style="font-size: 44rpx;">{{totalprice}}</view>		
										</view>
									
									</view>
									
									<view v-if="room.isdaymoney==1" class="tips">未有旅居{{t('余额')}},支付{{room.price}}/晚或去获取旅居{{t('余额')}}</view>
								</view>
							</view>
							
							<view style="height:260rpx"></view>
							
						</scroll-view>
						<!-- 预定 -->
						<view class="popup-but-view flex flex-col" style="bottom: 0;">
							<view class="price-statistics flex flex-y-center" :style="{color:t('color1')}">
								<view class="title-text">每日金额：</view>
								<view class="price-text flex" v-if="room.isdaymoney==1">
									<view style="font-size: 22rpx;margin-top: 8rpx;">￥</view>
									<view style="font-weight: bold;font-size: 36rpx;">{{totalprice}}+{{room.daymoney}}{{moneyunit}}</view>
								</view>
								<view class="price-text flex" v-else>
									<view style="font-size: 22rpx;margin-top: 8rpx;">￥</view>
									<view style="font-weight: bold;font-size: 36rpx;">{{totalprice}}</view>
								</view>
								<!--<view class="title-text">共减：</view>
								<view class="price-text flex">
									<view style="font-size: 22rpx;margin-top: 8rpx;">￥</view>
									<view style="font-weight: bold;font-size: 36rpx;">123.00</view>
								</view>-->
							</view>
							<view class="detail_but-class" v-if="minstock>0 && !room.isbooking" @tap="tobuy" :style="'background: linear-gradient(90deg,rgba('+tColor('color1rgb')+',1) 0%,rgba('+tColor('color1rgb')+',1) 100%)'">预定</view>
							<view class="but-class" v-else-if="room.isbooking && minstock>0" :style="'background: #999999;color:#fff'">不可订</view>
							<view class="but-class" v-else :style="'background: #999999;color:#fff'">已订满</view>
						</view>
					</view>
				</uni-popup>
				
				<!-- 选择日期弹窗 -->
				
				<view v-if="calendarvisible" class="popup__container">
					<view class="popup__overlay" @tap.stop="handleClickMask"></view>
					<view class="popup__modal">
						<view class="popup__title">
							<text class="popup__title-text">选择日期</text>
							<image :src="`${pre_url}/static/img/hotel/popupClose2.png`" class="popup__close" style="width:56rpx;height:56rpx;top:20rpx;right:20rpx" @tap.stop="handleClickMask"/>
						</view>
						<view class="popup__content">
							<view class="reserve-time-view" >
								<view class="time-view">
									<view class='time-title'>入住</view>
									<view class="flex flex-y-center" style="margin-top: 15rpx;align-items: flex-end;">
										<view class="date-time">{{startDate}}</view>
										<view class='time-title'>{{startWeek}}</view>
								</view>
								</view>
								<view class='statistics-view'>
									<view class="statistics-date">
										<view class="content-decorate left-c-d"></view>
										共{{dayCount}}晚
										<view class="content-decorate right-c-d"></view>
									</view>
									<view class="color-line"></view>
								</view>
								<view class="time-view">
									<view class='time-title'>离店</view>
										<view class="flex flex-y-center" style="margin-top: 15rpx;align-items: flex-end;">
											<view class="date-time">{{endDate}}</view>
											<view class='time-title'>{{endWeek}}</view>
									</view>
								</view>
							</view>
							<view class="calendar-view">
								<calendar :is-show="true" :isFixed='false' showstock='1' :text="text"  :dayroomprice="dayroomprice" :start-date="starttime" :end-date="endtime" mode="2"  @callback="getDate"  :maxdays='maxdays'  :themeColor="t('color1')" :between-end="maxenddate" :initMonth="initMonth">

								</calendar>
							</view>
							<view class="choose-but-class" :style="'background: linear-gradient(90deg,rgba('+tColor('color1rgb')+',1) 0%,rgba('+tColor('color1rgb')+',1) 100%)'" @tap="popupClose">
								确认{{dayCount}}晚 可订{{minday}}{{text['间']}}
							</view>
						</view>
					</view>
				</view>
		</block>
	</view>
</template>

<script>
	import calendar from '../mobile-calendar-simple/Calendar.vue'
	var app = getApp();
	export default{
		components:{
		    calendar
		},
		data(){
			return{
				isload: false,
				startDate:'',
				endDate:'',
				hotelid:0,
				hotel:[],
				navigationMenu:{},
				statusBarHeight: 20,
				platform: app.globalData.platform,
				pre_url: app.globalData.pre_url,
				text:[],
				roomlist:[],
				bannerindex:1,
				bannerList:[],
				dayCount:1,
				startWeek:'',
				endWeek:'',
				starttime:'',
				pagecontent: "",
				endtime:'',
				roomList:[],
				room:[],
				yajin:0,
				service_money:0,
				totalprice:0,
				photos:[],
				pindex:0,
				photolist:[],
				grouplist:[],
				gindex:-1,
				gids:[],
				nomore:false,	
				nodata: false,
				totalpagenum:0,
				commentlist:[],
				haoping:'',
				maxenddate:'',
				dayroomprice:[],
				minday:0,
				minstock:0,
				calendarvisible:false,
				maxdays:0,
				pagenum:0,
				qystatus:0,
				fwstatus:0,
				qyname:'',
				fwname:'',
				roomstyle:1,
				btnstyle:1,
				nature:'',
				moneyunit:'元',
				photosArr:[],
        initMonth:6,//日期插件显示多少个月
			}
		},
		onLoad(opt) {
			this.opt = app.getopts(opt);
			this.hotelid = this.opt.id?this.opt.id:0
			var sysinfo = uni.getSystemInfoSync();	
			this.statusBarHeight = sysinfo.statusBarHeight;
			this.wxNavigationBarMenu();
			
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
					app.setCache('user_current_latitude',res.latitude)
					app.setCache('user_current_longitude',res.longitude)
				});
			}
			
			this.getdata();
			// 酒店详情弹窗！！！！！！！！！！！-----------------------------------------------------------------------------------------------
			// this.$refs.popup.open();
		},
		methods:{
			getdata:function(e){
				var that=this;
        //读取缓存时间(缓存30分钟)
        var starttime= app.getCache('startTime');
        var endtime  = app.getCache('endTime');
        var daycount = app.getCache('dayCount');

				app.post('ApiHotel/getsysset', {hotelid:that.hotelid,startday:starttime,endday:endtime,daycount:daycount}, function (res) { 
						if(res.status==1){
							that.set=res.set
							that.roomstyle = res.set.roomstyle;
							that.btnstyle = res.set.btnstyle;
							that.catelist = res.catelist

              app.setCache('startTime',res.startday,30);
              that.starttime = starttime = res.startday;
              var starttime2 = starttime.replace(/-/g, '/');

              app.setCache('endTime',res.endday,30);
              that.endtime = endtime = res.endday;
              var endtime2 = endtime.replace(/-/g, '/');
              
              app.setCache('dayCount',res.dayCount,30);
              that.dayCount = res.dayCount;

              var startDate= starttime.substr(5).replace('-', '月');
              var endDate  = endtime.substr(5).replace('-', '月');
              app.setCache('startDate',startDate,30);
              app.setCache('endDate',endDate,30);
              that.startDate = startDate
              that.endDate = endDate

              that.startWeek = res.startweek
              that.endWeek = res.endweek

							that.text = res.text
							that.moneyunit = res.moneyunit
              that.initMonth = res.initMonth || 6;//日期插件显示的月份
							uni.setNavigationBarTitle({
								title:res.text['酒店']+'详情'
							});
							
							that.getdetail()
						}
				 })
			},
			getdetail:function(loadmore){
				var that=this
				if(!loadmore){
					this.pagenum = 1;
					this.datalist = [];
				}
				var pagenum = that.pagenum;
				that.loading = true;
				that.nodata = false;
				that.nomore = false;
				var latitude = that.latitude;
				var longitude = that.longitude;
				app.post('ApiHotel/gethotelDetail', {hotelid:this.hotelid,pagenum: pagenum,gids:that.gids,starttime:that.starttime,endtime:that.endtime,daycount:that.dayCount,longitude: longitude,latitude: latitude,}, function (res) {
						that.loading = false;
						uni.stopPullDownRefresh();
						if(res.status==1){
							var data = res.datalist;
							if (pagenum == 1) {
								that.qystatus = res.detail.qystatus
								that.fwstatus = res.detail.fwstatus
								that.qyname = res.detail.qyname
								that.fwname = res.detail.fwname
								that.nature = res.detail.nature
																
								that.roomlist = res.datalist;
							  if (data.length == 0) {
							    that.nodata = true;
							  }
								that.totalpagenum = res.totalpagenum
								that.hotel=res.detail
								console.log(that.hotel)
								that.photos = res.photos
								that.photos.forEach(item => that.photosArr.push(...item.pics));
								if(res.photos.length>0){
									that.photolist = res.photos[0].pics
								}
								that.grouplist = res.grouplist	
								that.commentlist = res.commentlist
								that.haoping = res.haoping
								that.dayroomprice = res.roomdayprice
								that.maxenddate = res.maxenddate
								that.maxdays = res.maxdays
                
                that.loaded();
							}else{
							  if (data.length == 0) {
							    that.nomore = true;
							  } else {
							    var roomlist = that.roomlist;
							    var newdata = roomlist.concat(data);
							    that.roomlist = newdata;
							  }
							}
						}else{
              app.alert(res.msg)
            }
				})
			},
			handleClickMask:function(){
				this.calendarvisible = false;
			},
			showmore:function(e){
				if (!this.nodata && !this.nomore) {
					this.pagenum = this.pagenum + 1;
					this.getdetail(true);
				}
			},
			selectDate:function(){
				// 选择日期弹窗-------------------------------------------------------------------------------------------
				//this.$refs.popupTime.open();
				this.calendarvisible = true;
			},
			getDate(date){
				var that=this
				if(date.dayCount){
						app.setCache('dayCount',date.dayCount,30)
						that.dayCount = date.dayCount;
				}
				if(date.startStr){
						var starttime =  date.startStr.dateStr
						app.setCache('startTime',starttime,30);
						var startDate = starttime.substr(5).replace('-', '月');
						app.setCache('startDate',startDate,30);
						
						that.starttime = starttime
						that.startDate = startDate
						that.startWeek = date.startStr.week
				}
				if(date.endStr){
					var endtime =  date.endStr.dateStr
					app.setCache('endTime',endtime,30);
					var endDate = endtime.substr(5).replace('-', '月');
					app.setCache('endDate',endDate,30);
					that.endtime = endtime
					that.endDate = endDate
					that.endWeek = date.endStr.week
				}		
				var minday = this.dayroomprice[starttime]['stock'];
				var timestamp = new Date(starttime).getTime();
				for(var i=0;i<date.dayCount;i++){
					var daystr = this.dateFormat((timestamp/1000)+86400*i,'Y-m-d')
					if(minday>this.dayroomprice[daystr]['stock']){
						minday = this.dayroomprice[daystr]['stock'];
					}
				}
				that.minday = minday;
				that.getdata();
			 },
			popupClose(){
				//this.$refs.popupTime.close();
				this.calendarvisible = false;
			},
			hotelDetail:function(e){
				var id = e.currentTarget.dataset.id;
				var that = this
				app.post('ApiHotel/getroomDetail', {id:id,startDate:that.starttime,endDate:that.endtime,dayCount:that.dayCount}, function (res) {
						console.log(res);
						if(res.status==1){
							that.room=res.room
						 
							var pagecontent = JSON.parse(res.room.detail);
							that.pagecontent = pagecontent;
							 
							 
							that.minstock = res.minstock
							that.calculatePrice();
						}
				})
				// 房型详情-------------------------------------------------------------------------------------------
				this.$refs.popup.open();
			},
			groupChange:function(e){
				var grouplist = this.grouplist;
				var gid = e.currentTarget.dataset.id;
				var gids = this.gids;
				var newgids = [];
				var ischecked = false;
				for(var i in gids){
					if(gids[i] != gid){
						newgids.push(gids[i]);
					}else{
						ischecked = true;
					}
				}
				if(ischecked==false){
					newgids.push(gid);
				}
				this.gids = newgids;
				this.getdetail()
			},
			openLocation:function(e){
				//console.log(e)
				var latitude = parseFloat(e.currentTarget.dataset.latitude)
				var longitude = parseFloat(e.currentTarget.dataset.longitude)
				var address = e.currentTarget.dataset.address
				uni.openLocation({
				 latitude:latitude,
				 longitude:longitude,
				 name:address,
				 scale: 13
			 })		
			},
			chanagePhoto:function(e){
				var that=this
				var pindex = e.currentTarget.dataset.index

				that.pindex = pindex;
				that.photolist = that.photos[pindex].pics
			},
			//计算价格
			calculatePrice: function() {
				var that = this;
				var dayCount = that.dayCount;
				var room = that.room
				console.log(room);
				var totalprice = 0;
				var service_money = 0;
				if(room.isservice_money==1){
					service_money = room.service_money;
				}
				that.service_money = service_money;	
				var yajin=0;
				if(room.isyajin==1 || room.isyajin==2){
						yajin = room.yajin_money
				}else if(room.isyajin==-1){
						yajin=0;
				}else{
					if(that.hotel.isyajin==1 || that.hotel.isyajin==2){
							yajin = that.hotel.yajin_money
					}else{
						yajin = 0
					}
				}				
				that.yajin =parseFloat(yajin) ;
				//是否使用余额定价
				if(room.isdaymoney==1){
						totalprice = parseFloat(service_money);
				}else{
						totalprice = parseFloat(service_money) + parseFloat(room.price);
				}
				that.totalprice =parseFloat(totalprice).toFixed(2);;
			},
			tobuy: function (e) {
				var that = this;
				var roomid = that.room.id;
				var daycount = that.dayCount;
				var starttime = app.getCache('startTime');
				var endtime = app.getCache('endTime');
				if(!starttime || !endtime){
						return app.error("请选择入离时间");
				}
				//var timestamp = Date.parse(str2);
				app.goto('buy?roomid=' + roomid+'&daycount='+daycount);
			},
			popupdetailClose(){
				this.$refs.popup.close();
			},
			swiperChange(event){
				this.bannerindex = event.detail.current;
			},
			tColor(text){
				let that = this;
				if(text=='color1'){
					if(app.globalData.initdata.color1 == undefined){
						let timer = setInterval(() => {
							that.tColor('color1')
						},1000)
						clearInterval(timer)
					}else{
						return app.globalData.initdata.color1;
					}
				}else if(text=='color2'){
					return app.globalData.initdata.color2;
				}else if(text=='color1rgb'){
					if(app.globalData.initdata.color1rgb == undefined){
						let timer = setInterval(() => {
							that.tColor('color1rgb')
						},1000)
						clearInterval(timer)
					}else{
						var color1rgb = app.globalData.initdata.color1rgb;
						return color1rgb['red']+','+color1rgb['green']+','+color1rgb['blue'];
					}
				}else if(text=='color2rgb'){
					var color2rgb = app.globalData.initdata.color2rgb;
					return color2rgb['red']+','+color2rgb['green']+','+color2rgb['blue'];
				}else{
					return app.globalData.initdata.textset[text] || text;
				}
			},
			wxNavigationBarMenu:function(){
				if(this.platform=='wx'){
					//胶囊菜单信息
					this.navigationMenu = wx.getMenuButtonBoundingClientRect()
				}
			},
			viewPicture(urls,url){
				let arr = urls;
				uni.previewImage({
					current: url,
					urls:this.photosArr
				});
			},
			goback: function() {
			  var that = this;
				getApp().goback();
			},
		},

	}
</script>

<style>
	/*  */
	.choose-but-class{width: 94%;background: linear-gradient(90deg, #06D470 0%, #06D4B9 100%);color: #FFFFFF;font-size: 32rpx;font-weight: bold;padding: 24rpx;
	border-radius: 60rpx;position: fixed;bottom:10rpx;left: 50%;transform: translateX(-50%);margin-bottom: env(safe-area-inset-bottom);text-align: center;}
	.calendar-view{width: 100%;position: relative;max-height: 60vh;padding-top: 30rpx;height: auto;overflow: hidden;padding-bottom: env(safe-area-inset-bottom);}
	/*  */
	.popup__content{ background: #fff;overflow:hidden; height: auto; }
	.popup__content .reserve-time-view{width: 88%;height:130rpx;margin:30rpx auto 0;border-bottom: 1px  #f0f0f0 solid;display: flex;align-items: center;
	justify-content: space-between;}
	.popup__content .reserve-time-view .time-view{display: flex;flex-direction: column;align-items: flex-start;}
	.popup__content .reserve-time-view .time-view .time-title{color: #7B8085;line-height: 24rpx;}
	.popup__content .reserve-time-view .time-view .date-time{color: #111111;font-size: 32rpx;font-weight: bold;padding-right: 20rpx;}
	.popup__content .reserve-time-view .statistics-view{display: flex;flex-direction: column;align-items: center;justify-content: center;}
	.popup__content .reserve-time-view .statistics-view .statistics-date{width: 88rpx;height: 32rpx;border-radius: 20px;font-size: 20rpx;
	color: #000;border: 1rpx solid #000;box-sizing: border-box;display: flex;align-items: center;justify-content: center;position: relative;}
	.statistics-view .statistics-date .content-decorate{width: 13rpx;height: 2rpx;background: red;position: absolute;top: 50%;background: #000;}
	.statistics-view .statistics-date .left-c-d{left: -13rpx;}
	.statistics-view .statistics-date .right-c-d{right: -13rpx;}
	.popup__content .reserve-time-view .statistics-view .color-line{border-top: 1rpx solid #f0f0f0;width: 130rpx;margin-top: 25rpx;}
	.uni-popup__wrapper-box{background: #f7f8fa;border-radius: 40rpx 40rpx 0rpx 0rpx;overflow: hidden;}
	/*  */
	.popup__content .popup-but-view{width: 100%;position: sticky;bottom: 0rpx;padding: 20rpx 40rpx;background: #fff;box-shadow: 0rpx 0rpx 10rpx 5rpx #ebebeb;}
	.popup__content .popup-but-view .but-class{width: 100%;padding: 22rpx;text-align: center;color: #FFFFFF;font-size: 32rpx;font-weight: bold;border-radius: 60rpx;background: linear-gradient(90deg, #06D470 0%, #06D4B9 100%); }
	.popup__content .popup-but-view .price-statistics{padding-bottom: 15rpx;}
	.popup__content .popup-but-view .price-statistics .title-text{font-size: 24rpx;}
	.popup__content .popup-but-view .price-statistics .price-text{padding: 0rpx 10rpx;align-items: center;}
	/*  */
	.popup__content{width: 100%;height:auto;position: relative;}
	/*  */
	
	
	.popup__content .popup-close{position: fixed;right: 20rpx;top: 20rpx;width: 56rpx;height: 56rpx;z-index: 11;}
	
	
	.popup__content .popup-close image{width: 100%;height: 100%;}
	/*  */
	.popup__content .popup-banner-view{width: 100%;height: 500rpx;position: relative;}
	.popup__content .popup-banner-view .popup-numstatistics{position: absolute;right: 20rpx;bottom: 20rpx;background: rgba(0, 0, 0, 0.3);
	border-radius: 28px;width: 64px;height: 28px;text-align: center;line-height: 28px;color: #fff;font-size: 20rpx;}
	.popup__content .hotel-details-view{width: 100%;padding: 30rpx 40rpx;background: #fff;}
	.popup__content .hotel-details-view	.hotel-title{color: #1E1A33;font-size: 35rpx;}
	.popup__content .hotel-details-view	.introduce-view{width: 100%;align-items: center;flex-wrap: wrap;justify-content: flex-start;padding: 20rpx 10rpx;}
	.popup__content .hotel-details-view	.introduce-view .options-intro{padding: 15rpx 0rpx;margin-right: 20rpx;width: auto;}
	.hotel-details-view	.introduce-view .options-intro image{width: 32rpx;height: 32rpx;}
	.hotel-details-view	.introduce-view .options-intro .options-title{color: #1E1A33;font-size: 24rpx;margin-left: 15rpx;}
	.hotel-details-view .other-view{width: 100%;justify-content: flex-start;padding: 12rpx 0rpx;}
	.hotel-details-view .other-view .other-title{color: #A5A3AD;font-size: 24rpx;margin-right: 40rpx;}
	.hotel-details-view .other-view .other-text{color: #1E1A33;font-size: 24rpx;}
	/*  */
	.popup__content .hotel-equity-view{width: 100%;padding:30rpx 40rpx 40rpx;background: #fff;margin-top: 20rpx;}
	.hotel-equity-view .equity-title-view{align-items: center;justify-content: flex-start;}
	.hotel-equity-view .equity-title-view .equity-title{color: #1E1A33;font-size: 32rpx;font-weight: bold;}
	.hotel-equity-view .equity-title-view .equity-title-tisp{color: #A5A3AD;font-size: 24rpx;margin-left: 28rpx;}
	.hotel-equity-view .equity-options{margin-top: 40rpx;}
	.hotel-equity-view .equity-options .options-title-view{align-items: center;justify-content: flex-start;}
	.hotel-equity-view .equity-options .options-title-view image{width: 28rpx;height: 28rpx;margin-right: 20rpx;}
	.hotel-equity-view .equity-options .options-title-view  .title-text{color: #1E1A33;font-size: 28rpx;font-weight: bold;}
	.hotel-equity-view .equity-options .options-text{color: rgba(30, 26, 51, 0.8);font-size: 24rpx;padding: 15rpx 0rpx;line-height: 40rpx;margin-left: 50rpx;margin-right: 50rpx;}
	/*  */
	.hotel-equity-view .promotion-options{width: 100%;justify-content: space-between;padding: 12rpx 0rpx;}
	.hotel-equity-view .promotion-options image{width: 20rpx;height: 20rpx;}
	.hotel-equity-view .promotion-options .left-view{justify-content: flex-start;}
	.hotel-equity-view .promotion-options .left-view .logo-view{width: 80px;height: 20px;text-align: center;line-height: 18px;border-radius: 8rpx;border:1px solid;font-size: 20rpx;}
	.hotel-equity-view .promotion-options .left-view .logo-view-text{color: rgba(30, 26, 51, 0.8);font-size: 20rpx;padding-left: 30rpx;}
	/*  */
	.hotel-equity-view  .cost-details{background: #F4F5F9;width: 100%;border-radius:6px;padding: 40rpx;}
	.hotel-equity-view  .cost-details .price-view{padding-bottom: 30rpx;}
	.hotel-equity-view  .cost-details .price-view .price-text{color: rgba(30, 26, 51, 0.8);font-size: 24rpx;}
	.hotel-equity-view  .cost-details .price-view .price-num{color: #1E1A33;font-size: 28rpx;font-weight: bold;}
	/*  */
	.position-view{width: 100%;height: auto;position: relative;top:-125rpx;}
	.position-view .banner-tab-view{height: 60rpx;border-radius: 20px;background: rgba(0, 0, 0, 0.3);padding: 0rpx 8rpx;max-width:96%;width:auto;margin-left:2%;display: inline-block;}
	.position-view .banner-tab-view .tab-options-banner{font-size: 20rpx;color: #FFFFFF;height: 46rpx;line-height: 46rpx;text-align: center;border-radius: 30rpx;padding: 0rpx 23rpx;margin-right: 2rpx;display: inline-block;}
	.position-view .banner-tab-view	.tab-options-banner-active{background: linear-gradient(90deg, #FFFFFF 0%, rgba(255, 255, 255, 0.8) 100%);color: #000000;}
	.content-view{border-radius: 40rpx 40rpx 0rpx 0rpx;background: #fff;padding: 0rpx 40rpx;width: 100%;height: auto;margin-top: 30rpx;}
	.content-view .title-view{color: #1E1A33;font-size: 40rpx;padding: 40rpx 0 0rpx 0 ;font-weight: bold;}
	.content-view .hotspot-view{width: 100%;display: flex;align-items: center;justify-content: space-between;}
	.content-view .hotspot-view .hotspot-view-left{display: flex;align-items: center;justify-content: flex-start;width: 100%; flex-wrap: wrap;}
	.content-view .hotspot-view .hotspot-view-left .hotspot-options{display: inline-block;background: #F6F7F8;padding: 5px 10px;border-radius: 6px;color: #4A4950; margin-top: 20rpx;
	font-size: 20rpx;margin-right: 13rpx;}
	.content-view .hotspot-view .hotspot-view-left .hotspot-options-active{background: #FFF4D5;color: #EF8E32;}
	.content-view .hotspot-view .hotspot-more{color: #4A4950;font-size: 18rpx;display: flex;align-items: center;}
	.content-view .hotspot-view .hotspot-more image{width: 7px;height: 7px;margin-left: 10rpx;}
	.content-view .address-view{display: flex;align-items: center;justify-content: space-between;margin: 30rpx 0rpx;}
	.content-view .address-view .address-text{color: #727177;font-size: 31rpx;width: 95%;font-weight: bold;}
	.content-view .address-traffic{color: rgba(30, 26, 51, 0.4);font-size: 26rpx;margin-top: 20rpx;display: flex;align-items: center;}
	.content-view .address-traffic image{width: 24rpx;height: 24rpx;margin-right: 10rpx;}
	.content-view .address-view .fangshi-view{display: flex;align-items: center;justify-content: space-between;}
	.content-view .address-view .fangshi-view .fagnshi-options{display: flex;flex-direction: column;align-items: center;justify-content: center;color: #06D470;font-size: 18rpx;}
	.content-view .address-view .fangshi-view .fagnshi-options image{width: 65rpx;height: 65rpx;margin-bottom: 10rpx;}
	.content-view .time-view{width: 100%;padding: 30rpx 0rpx;}
	.content-view .time-view .time-options{}
	.content-view .time-view .time-options .month-tetx{color: #1E1A33;font-size: 32rpx;font-weight: 500;}
	.content-view .time-view .time-options .day-tetx{color: rgba(30, 26, 51, 0.4);font-size: 26rpx;margin-left: 20rpx;}
	.content-view .time-view .content-text{box-sizing: border-box;border: 1px solid #000;text-align: center;border-radius: 20px; padding:0 10rpx;
	color: #000;font-size: 22rpx;position: relative}
	.content-view .time-view .content-text .content-decorate{width: 13rpx;height: 2rpx;background: red;position: absolute;top: 50%;background: #000;}
	.content-view .time-view .content-text .left-c-d{left: -13rpx;}
	.content-view .time-view .content-text .right-c-d{right: -13rpx;}
	/*  */
	.screen-view{width: 100%;display: flex;align-items: center;justify-content: space-between;padding:20rpx; overflow-x: auto;}
	.screen-view .screen-view-left{flex:1;display: flex;align-items: center;justify-content: flex-start;margin-right: 30rpx;}
	.screen-view .screen-view-left .screen-options{display: flex;align-items: center;justify-content: space-between;background: #F4F4F4;border-radius: 6px;color: #212121;  font-size: 24rpx;padding: 12rpx 18rpx;margin-right: 20rpx;background: #fff; white-space: nowrap;}
	.screen-view .screen-view-left .screen-options image{width: 16rpx;height: 16rpx;margin-left: 16rpx;}
	.screen-view .right-screen{display: flex;align-items: center;color: #212121;font-size: 24rpx;background: #fff;padding: 12rpx 18rpx;border-radius: 6px;}
	.screen-view .right-screen image{width: 24rpx;height: 24rpx;margin-left: 10rpx;}
	/*  */
	.hotels-list{width: 96%;margin: 0rpx auto 0rpx;display: flex;align-items: center;justify-content: space-between;flex-direction:column;}
	.hotels-list .hotels-options{width: 100%;padding: 20rpx;display: flex;align-items: center;justify-content: space-between;border-radius: 8px;background: #FFFFFF;
	margin-bottom: 20rpx;position: relative;}
	.hotels-list .hotels-options .hotel-img{width: 98px;height: 130px;border-radius: 15rpx;overflow: hidden;
	display: flex;align-items: center;justify-content: center;
	}
	.hotels-list .hotels-options .hotel-img .fangxing{width: 98px;height: 98px;border-radius: 15rpx;overflow: hidden; 
 
	display: flex;align-items: center;justify-content: center;
    }
	

	.hotels-list .hotels-options .hotel-img image{width: 100%;height: 100%;}
	.hotels-list .hotels-options .hotel-info{flex: 1;padding-left: 20rpx;}
	.hotels-list .hotels-options .hotel-info .hotel-title{width: 100%;color: #343536;font-size: 30rpx;}
	.hotels-list .hotels-options .hotel-info .hotel-address{width: 100%;color: #7B8085;font-size: 24rpx;margin-top: 7rpx;}
	.hotels-list .hotels-options .hotel-info .hotel-characteristic{width: 80%;display: flex;align-items: center;justify-content: flex-start;margin-top: 7rpx;flex-wrap: wrap; }
	.hotels-list .hotels-options .hotel-info .hotel-characteristic .characteristic-options{font-size: 20rpx;padding: 7rpx 13rpx;flex-wrap: wrap;margin-right: 20rpx; margin-top: 10rpx;}
	.hotels-list .hotels-options .hotel-info .hotel-but-view{width: 100%;display: flex;align-items: center;justify-content: space-between;margin-top: 25rpx;}
	.hotels-list .hotels-options .hotel-info .hotel-but-view .make-info{display: flex;flex-direction: column;justify-content: flex-start;}
	.hotels-options .hotel-info .hotel-but-view .make-info .hotel-price{display: flex;align-items: center;justify-content: flex-start;font-size: 24rpx;}
	.hotel-info .hotel-but-view .make-info .hotel-price .hotel-price-num{font-size: 40rpx;font-weight: bold;padding: 0rpx 3rpx;}
	.hotels-options .hotel-info .hotel-but-view .make-info .hotel-text{color: #7B8085;font-size: 24rpx;margin-top: 15rpx;}
	.hotels-list .hotels-options .hotel-info .hotel-but-view .hotel-make{background: linear-gradient(90deg, #06D470 0%, #06D4B9 100%);width: 72px;height: 32px;line-height: 32px;
	text-align: center;border-radius: 36px;color: #FFFFFF;font-size: 28rpx;font-weight: bold;}
	.hotel-make-new{width: 90rpx;height: 95rpx;display: flex;align-items: center;justify-content: space-between;flex-direction: column;position: absolute;
	right: 20rpx;top: 110rpx;border-radius: 10rpx;overflow: hidden;}
	.hotel-make-new .make-new-view{width: 100%;padding:7rpx 0rpx;text-align: center;background: red;color: #fff;font-size: 30rpx;font-weight: bold;}
	.hotel-make-new .make-new-view2{width: 100%;text-align: center;font-size: 20rpx;border-bottom: 1px solid;color: red;padding-bottom: 5rpx;}
	/*  */
	.hotel-more{width: 96%;margin: 0rpx auto;background: #FFFFFF;border-radius: 8px;color: #06D470;text-align: center;padding: 30rpx 0rpx;font-size: 24rpx;letter-spacing: 3rpx;
	display: flex;align-items: center;justify-content: center;}
	.hotel-more image{width: 20rpx;height: 20rpx;margin-left: 10rpx;}
	/*  */
	.work-order-view{width: 96%;margin: 0rpx auto;background: #FFFFFF;border-radius: 8px;padding: 30rpx 20rpx;margin-top: 20rpx;}
	.work-order-view .work-order-title{display: flex;align-items: center;justify-content: space-between;}
	.work-order-view .work-order-title .work-order-title-left{color: #1E1A33;font-size: 28rpx;font-weight: bold;}
	.work-order-view .work-order-title .work-order-title-right{color: rgba(30, 26, 51, 0.4);font-size: 24rpx;display: flex;align-items: center;justify-content: flex-end;}
	.work-order-view .work-order-title .work-order-title-right image{width: 20rpx;height: 20rpx;margin-left: 10rpx;}
	.work-order-view .facilities-view{width: 100%;align-items: center;flex-wrap: wrap;margin-top: 20rpx;}
	.work-order-view .facilities-view .options-facilities{align-items: center;justify-content: center;width: 25%;padding: 20rpx;}
	.work-order-view .facilities-view .options-facilities image{width: 64rpx;height: 64rpx;}
	.work-order-view .facilities-view .options-facilities .facilities-text{color: #4A4950;font-size: 20rpx;margin-top: 15rpx;}
	/*  */
	.required-reading-view{width: 100%;display: flex;flex-direction: column;padding-top: 20rpx;}
	.required-reading-view .read-address-view{display: flex;align-items: center;justify-content: flex-start;padding: 8rpx 0rpx;}
	.required-reading-view .read-address-view	.address-title-text{color: #888889;font-size: 28rpx;}
	.required-reading-view .read-address-view	.address-text-view{color: #222229;font-size: 28rpx;}
	.required-reading-view .read-tisp-view{display: flex;flex-direction: column;padding: 20rpx 0rpx;}
	.required-reading-view .read-tisp-view .read-tisp-title{color: #1E1A33;font-size: 26rpx;font-weight: bold;margin-bottom:20rpx;}
	.required-reading-view .read-tisp-view .read-tisp-title image{width: 28rpx;height: 28rpx;margin-right: 15rpx;}
	.required-reading-view .read-tisp-view .read-tisp-options{color: #4A4950;font-size: 24rpx;margin-bottom: 10rpx;padding-left: 40rpx;}
	/*  */
	.score-view{width: 100%;display: flex;flex-direction: column;padding-top: 20rpx;}
	.score-view .score-content{background: #FAFAFC;padding: 15rpx;border-radius: 8px;margin-top: 20rpx;}
	.score-view .score-content .total-score-view{align-items: flex-start;}
	.score-view .score-content .total-score-view .total-score-num{align-items: flex-end;}
	.score-view .score-content .total-score-view .total-score-num .total-num{font-size: 36px;font-weight: bold;}
	.score-view .score-content .total-score-view .total-score-num  .tatal-describe{font-size: 28rpx;margin-left: 10rpx;margin-bottom: 10rpx;}
	.score-view .score-content .total-score-view  .score-evaluate{color: #4A4950;font-size: 24rpx;margin-top: 15rpx;}
	.score-view .score-content .score-statistics{}
	.score-view .score-content .score-statistics .statistics-options{padding: 0rpx 12rpx;}
	.score-view .score-content .score-statistics .statistics-options .icon-view{width: 60rpx;height: 60rpx;border-radius: 50%;box-sizing: border-box;border: 2px solid #06D470;
	color: #06D470;font-size: 24rpx;text-align: center;line-height: 55rpx;}
	.score-view .score-content .score-statistics .statistics-options .evaluate-icon{color: #4A4950;font-size: 24rpx;margin-top: 20rpx;}
	.score-view	.evaluate-tab{margin-top: 20rpx;width: 100%;}
	.score-view	.evaluate-tab .evaluate-options{display: inline-block;background: #F4F4F4;padding: 10px;text-align: center;color: #4A4950;font-size: 24rpx;margin-right: 15rpx;
	border-radius: 18rpx;}
	.score-view	.evaluate-list-view{margin-top: 30rpx;padding-bottom: 50rpx;}
	.score-view	.evaluate-list-view	.text-options{padding: 20rpx 0rpx 10rpx 20rpx;}
	.evaluate-list-view	.text-options .user-info-view{width: 100%;}
	.text-options .user-info-view .info-view .avater-view{width: 80rpx;height: 80rpx;border-radius: 50%;}
	.user-info-view .info-view .name-view{margin-left: 15rpx;}
	.user-info-view .info-view .name-view .name-text{color: #000000;font-size: 30rpx;font-weight: bold;}
	.user-info-view .info-view .name-view .time-view{color: rgba(0, 0, 0, 0.4);font-size: 28rpx;margin-top: 10rpx;}
	.text-options .user-info-view .scoring-view{border-radius: 4px;width: 96rpx;height: 80rpx;border-radius: 8rpx;text-align: center;line-height: 80rpx;font-size: 32rpx;
	padding: 0rpx 10rpx;}
	.text-options	.evaluate-imgList{width: 100%;padding: 20rpx 0rpx 0rpx;flex-wrap: wrap;justify-content: flex-start;}
	.text-options	.evaluate-imgList .img-options{width: 145rpx;height: 145rpx;border-radius: 20rpx;overflow: hidden;margin-right: 15rpx;margin-bottom: 15rpx;}
	.text-options	.evaluate-imgList .img-options image{width: 100%;height: 100%;}
	.text-options	 .evaluate-value{color: #4A4950;font-size: 26rpx;line-height: 40rpx;padding: 20rpx 0rpx;}
	
	/*  */
	.dp-banner{width: 100%;height: 250px;}
	.dp-banner-swiper{width:100%;height:100%;}
	.dp-banner-swiper-img{width:100%;height:auto}
	.banner-poster{width: 82%;margin: 30rpx auto 0rpx;display: flex;flex-direction:column;align-items: flex-end;}
	.banner-poster .poster-title{color: #FFFFFF;font-size: 56rpx;font-weight: 900;padding: 30rpx 0rpx;}
	.banner-poster .poster-text{color: #FFFFFF;font-size: 26rpx;opacity: 0.6;padding: 10rpx 0rpx;}
	.banner-poster .poster-but{width: 108px;height: 36px;color: #FFFFFF;text-align: center;line-height: 36px;font-size: 28rpx;font-weight: bold;margin: 40rpx 0rpx;border-radius: 36px;}
	/*  */
	.navigation {width: 100%;padding-bottom:10px;overflow: hidden;position: fixed;top: 0;z-index: 2;}
	.navcontent {display: flex;align-items: center;padding-left: 10px;}
	.header-location-top{position: relative;display: flex;justify-content: center;align-items: center;flex:1;}
	.header-back-but{position: absolute;left:0;display: flex;align-items: center;width: 40rpx;height: 45rpx;overflow: hidden;}
	.header-back-but image{width: 40rpx;height: 45rpx;} 
	.header-page-title{font-size: 36rpx;}
	
	/*查看详情里的按钮*/
	.detail_but-class{width: 100%;padding: 22rpx;text-align: center;color: #FFFFFF;font-size: 32rpx;font-weight: bold;border-radius: 60rpx;background: linear-gradient(90deg, #06D470 0%, #06D4B9 100%);   }
	
	.tips{ margin-top: 20rpx; color: #999;}
	.hotel-nature{display: flex;align-items: center;justify-content: flex-start;margin-top: 10rpx;}
	.hotel-nature .star-view{display: flex;align-items: center;justify-content: flex-start;}
	.hotel-nature .star-view image{width: 13px; height: 13px;margin-right: 10rpx;}
	.hotel-nature .hotspot-nature-text{font-size: 24rpx;color: #4A4950;margin-left: 10rpx;}
    .hotel-characteristic .under_title{color: #7B8085;font-size: 24rpx;margin-top: 7rpx; margin-left: 7rpx;}
</style>