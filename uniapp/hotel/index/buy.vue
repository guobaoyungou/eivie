<template>
	<view>
		<block v-if="isload">
			<form @submit="createorder">
				<view class="navigation" :style="'background:rgba('+tColor('color1rgb')+')'">
					<view class='navcontent' :style="{marginTop:navigationMenu.top+'px',width:(navigationMenu.right)+'px'}">
						<view class="header-location-top" :style="{height:navigationMenu.height+'px'}">
							<view class="header-back-but"  @tap="goback">
								<image :src="`${pre_url}/static/img/hotel/fanhui.png`"></image>
							</view>
							<view class="header-page-title" style="color:#fff;">订单详情</view>
						</view>
					</view>
				</view>
				<!-- #ifndef H5 || APP-PLUS -->
				<!--  -->
				<view style="height: 140rpx;"></view>
				<!--  #endif -->
				<view style="height: 80rpx;"></view>
				<view class="titlebg-postions">
					<view class="titlebg-view" :style="'background:rgba('+t('color1rgb')+')'"></view>
				</view>
				<view class="position-view">
					<view class="order-time-details flex flex-col">
						<view class="time-view flex flex-y-center flex-bt">
							<view class="time-options flex flex-y-center flex-bt">
								<view class="month-tetx">{{startDate}}</view>
								<view class="day-tetx">{{startweek}}入住</view>
							</view>
							<view class="content-text">
								<view class="content-decorate left-c-d"></view>
								共{{dayCount}}晚
								<view class="content-decorate right-c-d"></view>
							</view>
							<view class="time-options flex flex-y-center flex-bt">
								<view class="month-tetx">{{endDate}}</view>
								<view class="day-tetx">{{endweek}}离店</view>
							</view>
						</view>
						<view class="name-info flex flex-y-center flex-bt">
							<view class="flex flex-col">
								<view class="name-text">{{room.name}}</view>
								<view class="name-tisp">{{room.tag}}</view>
							</view>
							<view @click="showDetail" class="hotel-details-view flex flex-y-center" :style="{color:t('color1')}">
								房型详情<image :src="pre_url+'/static/img/arrowright.png'"></image>
							</view>
						</view>
						<view class="time-warning flex flex-y-center" :style="'background: rgba('+t('color1rgb')+',0.2);color:'+t('color1')+''" v-if="hotel.isrefund"> 
							<image :src="`${pre_url}/static/img/hotel/error.png`" ></image>
							限时取消 {{startDate}} {{hotel.refund_hour}}点前免费取消
						</view>
					</view>
					
					<!-- 订房信息 -->
					<view class="order-options-view flex flex-col">
						<view class="options-title flex flex-bt flex-y-center">
							<view>订房信息</view>
							<view class="right-view flex flex-y-center" @click="bookingChange" :style="{color:t('color1')}">订房说明<image :src="`${pre_url}/static/img/hotel/shuom.png`"></image></view>
						</view>
						<view class="booking-options flex flex-y-center flex-bt">
							<view class="book-title">
								{{text['间']}}数
							</view>
							<view class="room-number-view">
								<scroll-view scroll-x style="width: 100%;white-space: nowrap;">
									<block v-for="(item,index) in room.limitnums">
										<view class="room-options"  :style="item == changebookIndex ?'background: rgba('+t('color1rgb')+',0.2);color:'+t('color1')+'':''" @click="changebook(item)">{{item}}{{text['间']}}</view>
									</block>
								</scroll-view>
							</view>
						</view>
						<block v-for="(item,index) in num">
							<view class="booking-options flex flex-y-center flex-bt form-item" style="padding: 0;">
								<view class="book-title">
									住客姓名
								</view>
								<view class="room-form">
										<input :name="'name'+index" placeholder="仅填写1人姓名(姓名不可重复)" style="text-align: right;"/>
								</view>
							</view>
						</block>
						<view class="booking-options flex flex-y-center flex-bt form-item" >
							<view class="book-title">
								联系方式
							</view>
							<view class="room-form">
								<input name="tel" v-model="tel"  placeholder="请填写联系方式" style="text-align: right;"/>
							</view>
						</view>
						
						<view class="form-item booking-options flex flex-y-center flex-bt" v-for="(item,idx) in hotel.formdata" :key="item.id">
							<view class="book-title">{{item.val1}}<text v-if="item.val3==1" style="color:red"> *</text></view>
							<block v-if="item.key=='input'">
								<input type="text" :name="'form'+'_'+idx" class="input" :placeholder="item.val2" placeholder-style="font-size:28rpx"/>
							</block>
							<block v-if="item.key=='textarea'">
								<textarea :name="'form'+'_'+idx" class='textarea' :placeholder="item.val2" placeholder-style="font-size:28rpx"/>
							</block>
							<block v-if="item.key=='radio'">
								<radio-group class="radio-group" :name="'form'+'_'+idx">
									<label v-for="(item1,idx1) in item.val2" :key="item1.id" class="flex-y-center">
										<radio class="radio" :value="item1"/>{{item1}}
									</label>
								</radio-group>
							</block>
							<block v-if="item.key=='checkbox'">
								<checkbox-group :name="'form'+'_'+idx" class="checkbox-group">
									<label v-for="(item1,idx1) in item.val2" :key="item1.id" class="flex-y-center">
										<checkbox class="checkbox" :value="item1"/>{{item1}}
									</label>
								</checkbox-group>
							</block>
						 

							<block v-if="item.key=='selector'">
								<picker class="picker" mode="selector" :name="'form'+'_'+idx" value="" :range="item.val2" @change="editorBindPickerChange" :data-bid="hotel.bid" :data-idx="idx">
							 
								 <view v-if="(hotel.editorFormdata[idx]) || (hotel.editorFormdata[idx]===0)"> {{item.val2[hotel.editorFormdata[idx]]}}</view>

								<view v-else >请选择</view>
								</picker>
								<text class="iconfont iconjiantou" style="color:#999;font-weight:normal"></text>
							</block>

							<block  v-if="item.key=='time'">
								<picker class="picker" mode="time" :name="'form'+'_'+idx" value="" :start="item.val2[0]" :end="item.val2[1]" :range="item.val2" @change="editorBindPickerChange" :data-bid="hotel.bid" :data-idx="idx">
									<view v-if="hotel.editorFormdata[idx]">{{hotel.editorFormdata[idx]}}</view>
									<view v-else>请选择</view>
								</picker>
								<text class="iconfont iconjiantou" style="color:#999;font-weight:normal"></text>
							</block>
							<block v-if="item.key=='date'">
								<picker class="picker" mode="date" :name="'form'+'_'+idx" value="" :start="item.val2[0]" :end="item.val2[1]" :range="item.val2" @change="editorBindPickerChange" :data-bid="hotel.bid" :data-idx="idx">
									<view v-if="hotel.editorFormdata[idx]">{{hotel.editorFormdata[idx]}}</view>
									<view v-else>请选择</view>
								</picker>
								<text class="iconfont iconjiantou" style="color:#999;font-weight:normal"></text>
							</block>
							<block v-if="item.key=='upload'">
								<input type="text" style="display:none" :name="'form'+'_'+idx" :value="hotel.editorFormdata[idx]"/>
								<view class="flex" style="flex-wrap:wrap;padding-top:20rpx">
									<view class="form-imgbox" v-if="hotel.editorFormdata[idx]">
										<view class="layui-imgbox-close" style="z-index: 2;" @tap="removeimg" :data-bid="hotel.bid" :data-idx="idx"><image style="display:block" :src="pre_url+'/static/img/ico-del.png'"></image></view>
										<view class="form-imgbox-img"><image class="image" :src="hotel.editorFormdata[idx]" @click="previewImage" :data-url="hotel.editorFormdata[idx]" mode="aspectFit"/></view>
									</view>
									<view v-else class="form-uploadbtn" :style="{background:'url('+pre_url+'/static/img/shaitu_icon.png) no-repeat 50rpx',backgroundSize:'80rpx 80rpx',backgroundColor:'#F3F3F3'}" @click="editorChooseImage" :data-bid="hotel.bid" :data-idx="idx"></view>
								</view>
							</block>
						</view>
					</view>
		
					<!-- 本单可享 -->
					<view class="order-options-view flex flex-col">
						<view class="options-title">本单可享</view>
						<view class="preferential-view flex flex-y-center flex-bt">
							<view class="pre-title">{{t('优惠券')}}</view>
							<view class="pre-text flex flex-y-center">
								<view v-if="couponList.length>0 && (leftmoney>0 || (leftmoney<=0 && couponrid>0)) && !moneydec" class="f2" @tap="showCouponList">
						<text style="color:#fff;padding:4rpx 16rpx;font-weight:normal;border-radius:8rpx;font-size:24rpx" :style="{background:t('color1')}">
						{{couponrid!=0?couponnametext:couponList.length+'张可用'}}
					 
					</text><text class="iconfont iconjiantou" style="color:#999;font-weight:normal">
						
					  </text></view>
								<text class="f2" v-else style="color:#999">无可用{{t('优惠券')}}</text>
							</view>
						</view>	
					</view>
					<!--余额抵扣-->
					<view class="price order-options-view flex flex-col" v-if="hotel.money_dikou==1 && userinfo.money>0 " >
					  <checkbox-group @change="moneydk"  :data-rate="hotel.dikou_bl" class="flex" style="width:100%">
					    <view class="f1">
					      <view>
					          使用{{t('余额')}}抵扣（余额：<text style="color:#e94745">{{userinfo.money}}</text>{{moneyunit}}）
					      </view>
					      <view style="font-size:24rpx;color:#999" >
					        1、选择此项提交订单时将直接扣除{{t('余额')}}
					      </view>
					       <!--<view style="font-size:24rpx;color:#999" >
					        2、最多可抵扣房费的{{showmoneyrate}}%
									2、最多可抵扣房费{{dkmoney}}元
					      </view> -->
					    </view>
					    <view class="f2" style="font-weight:normal">
					      <checkbox value="1" :checked="moneydec?true:false" style="margin-left:6px;transform:scale(.8)"></checkbox>
					    </view>
					  </checkbox-group>
					</view>
					<!--余额抵扣-->
					
					<!--积分抵扣开始-->
					<view class="price  order-options-view flex flex-col" 	v-if="hotel.score_dikou == 1 && userinfo.score>0 && hotel.scoredkmaxpercent>0">
						<checkbox-group @change="scoredk"  class="flex" style="width:100%">
							<view class="f1">
								<view>{{userinfo.score*1}} {{t('积分')}}可抵扣 <text
										style="color:#e94745">{{userinfo.scoredk_money*1}}</text> 元</view>
								<view style="font-size:22rpx;color:#999" v-if="hotel.scoredkmaxpercent>0">
									最多可抵扣房费金额的{{hotel.scoredkmaxpercent}}%</view>
							</view>
							<view class="f2" style="font-weight:normal">
								<checkbox value="1" :disabled="isdisabled" :checked="usescore?true:false"  style="margin-left:6px;transform:scale(.8)"></checkbox>
							</view>
						</checkbox-group>
					</view>
					<!--积分抵扣结束-->
          
          <!-- 升级积分抵扣s -->
          <view class="price  order-options-view flex flex-col" v-if="userinfo.upgradescore>0 && userinfo.upgradescoredk_money>0 && userinfo.upgradescoredkmaxpercent>0">
          	<checkbox-group @change="upgradescoredk" class="flex" style="width:100%">
          		<view class="f1">
          			<view>{{userinfo.upgradescore*1}} {{text['升级积分']}}可抵扣 <text
          					style="color:#e94745">{{userinfo.upgradescoredk_money*1}}</text>元</view>
          			<view style="font-size:22rpx;color:#999" v-if="userinfo.upgradescoremaxtype==0">
                  1、最多可抵扣{{text['服务费']}}金额的{{userinfo.upgradescoredkmaxpercent<=100?userinfo.upgradescoredkmaxpercent:'100'}}%
                </view>
          			<view style="font-size:22rpx;color:#999" v-else-if="userinfo.upgradescoremaxtype==1">
          				1、最多可抵扣{{userinfo.upgradescoredkmaxmoney}}元
                </view>
                <view style="font-size:24rpx;color:#999" >
                  2、提交订单时将直接扣除{{text['升级积分']}}
                </view>
          		</view>
          		<view class="f2">
          			<checkbox value="1" style="margin-left:6px;transform:scale(.8)"></checkbox>
          		</view>
          	</checkbox-group>
          </view>
          <!-- 升级积分抵扣e -->
			
					<!-- 特殊要求 -->
					<view class="order-options-view flex flex-col">
						<view class="options-title">特殊要求</view>
						<view class="requirement-view">
							<textarea name="message" placeholder="填写您的入驻环境,服务等要求"></textarea>
						</view>
					</view>
					
					<view style="height: 300rpx;"></view>
				</view>
			
				<!-- 底部按钮 -->
				<view class="but-view flex flex-col"  >
					<view class="read-agreement flex flex-y-center" v-if="hotel.xystatus==1">
						<view :class="'select-view '+((isagree && isagree1)?'select-view-active':'')"   :style="(isagree && isagree1) ?'border-color:'+t('color1')+';background-color:'+t('color1')+'':''"   @tap="changeChecked" >
							<image :src="pre_url+'/static/img/checkd.png'"></image>
						</view>
			
						<view class="flex flex-y-center">
							我已阅读并同意<view :style="ysfont_size > 0?'font-size:'+ysfont_size+'rpx;color:red':'color:red'"  @tap="showxieyiFun2"  >《{{hotel.ysname}}》</view>以及<view :style="ydfont_size > 0?'font-size:'+ydfont_size+'rpx;color:red':'color:red'"   @tap="showxieyiFun">《{{hotel.xyname}}》</view>
						</view>
					</view>
					<view class="yuding-view flex flex-y-center flex-bt">
						<view class="price-view flex flex-col">
							<view class="text">共计:</view>
							<block v-if="moneydec">
								<view class="price-text" :style="{color:t('color1')}" >￥{{totalprice}}+
									<text style="font-size: 24rpx;">{{moneydec?usemoney:0}}{{moneyunit}}</text>
								</view>
							</block>
							<block v-else>
								<view class="price-text" :style="{color:t('color1')}">￥{{totalprice}}</view>
							</block>
						</view>
						<view class="flex flex-row flex-y-center">
							<view class="cost-details flex flex-y-center"  @click="mignxiChange"  :style="{color:t('color1')}">
								费用明细
								<image :src="pre_url+'/static/img/arrowdown.png'"></image>
							</view>
							<button class='but-class1' :style="'background:rgba('+t('color1rgb')+',0.8);color:#FFF'" form-type="submit">预定</button>
						</view>
					</view>
				</view>
			
				
				<view v-if="showxieyi" class="xieyibox">
					<view class="xieyibox-content">
						<view style="overflow:scroll;height:100%;">
							<parse :content="hotel.xycontent" @navigate="navigate"></parse>
						</view>
						<view class="xieyibut-view flex-y-center">
							<view class="but-class" style="background:#A9A9A9"  @tap="closeXieyi">不同意并退出</view>
							<view class="but-class" :style="{background:'linear-gradient(90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'}" v-if="hotel.isqianzi==1"   @tap="goto" data-url="signature">去签字</view>
							<view class="but-class" :style="{background:'linear-gradient(90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'}" v-else  @tap="hidexieyi">阅读并同意</view>
						</view>
					</view>
				</view>
				<view v-if="showxieyi2" class="xieyibox">
					<view class="xieyibox-content">
						<view style="overflow:scroll;height:100%;">
							<parse :content="hotel.yscontent" @navigate="navigate"></parse>
						</view>
						<view class="xieyibut-view flex-y-center">
							<view class="but-class" style="background:#A9A9A9"  @tap="closeXieyi">不同意并退出</view>
							<view class="but-class" :style="{background:'linear-gradient(90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'}"  @tap="hidexieyi2">阅读并同意</view>
						</view>
					</view>
				</view>
				<!-- 优惠券begin -->
				<view v-if="couponvisible" class="popup__container">
					<view class="popup__overlay" @tap.stop="handleClickMask"></view>
					<view class="popup__modal coupon-modal">
						<view class="popup__title">
							<text class="popup__title-text">请选择{{t('优惠券')}}</text>
							<image :src="pre_url+'/static/img/close.png'" class="popup__close" style="width:36rpx;height:36rpx" @tap.stop="handleClickMask"/>
						</view>
						<view class="popup__content">
							<couponlist :couponlist="couponList" :choosecoupon="true" :selectedrid="couponrid" :bid="hotel.bid" @chooseCoupon="chooseCoupon"></couponlist>
						</view>
					</view>
				</view>
				<!-- 优惠券end -->
				
				<!-- 费用明细弹窗 -->
				<uni-popup id="popup" ref="popup" type="bottom">
					<view class="hotelpopup__content">
						<view class="popup-close" @click="popupClose">
							<image :src="`${pre_url}/static/img/hotel/popupClose2.png`"></image>
						</view>
						<scroll-view scroll-y style="height: auto;max-height: 50vh;">
							<!-- 费用明细 -->
							<view class="hotel-equity-view flex flex-col">
								<view class="equity-title-view flex" style="padding-bottom: 40rpx;">
									<view class="equity-title">在线支付</view>
								</view>
								<view class="cost-details flex flex-col">
									<view class="price-view flex flex-bt flex-y-center" style="margin-bottom: 30rpx;">
										<view class="price-text-title">房费</view>
										<!--<view class="price-num-title">￥{{roomprice}}</view>-->
									</view>
									
									<!--<view class="price-view flex flex-bt flex-y-center" v-for="(item,index) in roomprices">
										<view class="price-text">{{item.datetime}} </view>
										<view class="price-num">￥{{item.sell_price}}</view>
									</view>-->
									<view class="price-view flex flex-bt flex-y-center">
										<view class="price-text">{{t('余额')}}抵扣</view>
										<view class="price-num">{{moneydec?usemoney:0}}{{moneyunit}}</view>
									</view>
									<view class="price-view flex flex-bt flex-y-center">
										<view class="price-text">现金支付</view>
										<view class="price-num">￥{{leftmoney}}</view>
									</view>
								</view>
								<view class="cost-details flex flex-col">
									<view class="price-view flex flex-bt flex-y-center" style="margin-bottom: 30rpx;">
										<view class="price-text-title">其他</view>
										<view class="price-num-title"></view>
									</view>
									<view class="price-view flex flex-bt flex-y-center">
										<view class="price-text">押金(可退)</view>
										<view class="price-num">￥{{yajin}}</view>
									</view>
									<view class="price-view flex flex-bt flex-y-center" v-if="service_money>0">
										<view class="price-text">{{text['服务费']}}</view>
										<view class="price-num">￥{{service_money}}</view>
									</view>
								</view>
								<view class="cost-details flex flex-col" v-if="coupon_money>0 || usescore>0 || useupgradescore>0">
									<view class="price-view flex flex-bt flex-y-center" style="margin-bottom: 30rpx;">
										<view class="price-text-title">优惠</view>
										<view class="price-num-title"></view>
									</view>
									<view class="price-view flex flex-bt flex-y-center" v-if="coupon_money>0">
										<view class="price-text">优惠券抵扣</view>
										<view class="price-num">-￥{{coupon_money}}</view>
									</view>
									<view class="price-view flex flex-bt flex-y-center" v-if="usescore>0">
										<view class="price-text">{{t('积分')}}抵扣</view>
										<view class="price-num">-{{scoredk_money}}</view>
									</view>
                  <view class="price-view flex flex-bt flex-y-center" v-if="useupgradescore>0">
                  	<view class="price-text">{{text['升级积分']}}抵扣</view>
                  	<view class="price-num">-{{upgradescoredk_money}}</view>
                  </view>
								</view>
							</view>
						</scroll-view>
						<view class="popup-but-view flex flex-y-center flex-bt">
							<view class="price-view flex flex-col">
								<view class="text">在线付:</view>
								<block v-if="moneydec">
									<view class="price-text" :style="{color:t('color1')}">￥{{totalprice}}
										<text  style="font-size: 24rpx;">+{{moneydec?usemoney:0}}{{moneyunit}}</text>
									</view>
								</block>
								<block v-else>
									<view class="price-text" :style="{color:t('color1')}">￥{{totalprice}}</view>
								</block>
							</view>
							<view class="flex flex-row flex-y-center">
								<view class="cost-details flex flex-y-center" @click="popupClose" :style="{color:t('color1')}">
									费用明细
									<image :src="pre_url+'/static/img/arrowdown.png'"></image>
								</view>
								<button class='but-class' :style="{background:'linear-gradient(90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'}"  form-type="submit">极速支付</button>
							</view>
						</view>
					</view>
				</uni-popup>
		
			<!-- 订房说明 -->
				<uni-popup id="popupBook" ref="popupBook" type="bottom">
					<view class="hotelpopup__content" style="background: #fff;">
						<view class="popup-close" @click="popupClose">
							<image :src="`${pre_url}/static/img/hotel/popupClose2.png`"></image>
						</view>
						<view class="popup__title">
							<view class="popup__title-text">订房说明</view>
						</view>
						<view class="booking_notice">{{room.booking_notice}}</view>
					</view>
				</uni-popup>
				
				<!-- 详情弹窗 -->
				<uni-popup id="popupDetail" ref="popupDetail" type="bottom">
					<view class="hotelpopup__content">
						<view class="popup-close" @click="popupdetailClose">
							<image :src="`${pre_url}/static/img/hotel/popupClose.png`"></image>
						</view>
						<scroll-view scroll-y style="height: auto;max-height: 70vh;">
							<view class="popup-banner-view" style="height: 450rpx;">
								<swiper class="dp-banner-swiper" :autoplay="true" :indicator-dots="false" :current="0" :circular="true" :interval="3000" @change='swiperChange'>
									<block v-for="(item,index) in room.pics" :key="index"> 
										<swiper-item>
											<view @click="viewPicture(item)">
												<image :src="item" class="dp-banner-swiper-img" mode="widthFix"/>
											</view>
										</swiper-item>
									</block>
								</swiper>
								<view class="popup-numstatistics flex flex-xy-center" v-if='room.pics.length'>
									{{bannerindex}} / {{room.pics.length}}
								</view>
							</view>
							<view class="hotel-details-view flex flex-col">
								<view class="hotel-title">{{room.name}}</view>
								<view class="introduce-view flex ">
									<view class="options-intro flex flex-y-center"  v-if="room.bedxing!='0'">
										<image :src="pre_url+'/static/img/hotel/dachuang.png'"></image>
										<view class="options-title">{{room.bedxing}}</view>
									</view>
									<view class="options-intro flex flex-y-center">
										<image :src="pre_url+'/static/img/hotel/pingfang.png'"></image>
										<view class="options-title">{{room.square}}m²</view>
									</view>
									<view class="options-intro flex flex-y-center">
										<image :src="pre_url+'/static/img/hotel/dachuang.png'"></image>
										<view class="options-title">{{room.bedwidth}}米</view>
									</view>
					
									 <view class="options-intro flex flex-y-center" v-if="room.ischuanghu!=0">
										<image :src="pre_url+'/static/img/hotel/youchuang.png'"></image>
										<view class="options-title">{{room.ischuanghu}}</view>
									</view>
 
									 <view class="options-intro flex flex-y-center" v-if="room.breakfast!=0">
										<image :src="pre_url+'/static/img/hotel/zaocan.png'"></image>
										<view class="options-title">{{room.breakfast}}早餐</view>
									</view>
								</view>
								<view class="other-view flex flex-y-center">
									<view class="other-title">特色</view>
									<view class="other-text" style="white-space: pre-line;">{{room.tese}}</view>
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
							<view class="hotel-equity-view flex flex-col"   v-if="fwstatus == 1">
								<view class="equity-title-view flex">
									<view class="equity-title">{{ fwname }}</view>
								</view>
								<view class="equity-options flex flex-col">
										<parse :content="hotel.hotelfuwu" @navigate="navigate"></parse>
								</view>
							</view>
						</scroll-view>
					</view>
				</uni-popup>
				</form>
		</block>
	</view>
</template>

<script>
	var app = getApp();
	export default {
		data(){
			return{
				isload:false,
				navigationMenu:{},
				statusBarHeight: 20,
				platform: app.globalData.platform,
				pre_url: app.globalData.pre_url,
				startDate:'',
				endDate:'',
				dayCount:1,
				startweek:'',
				endweek:'',
				room:[],
				bannerindex:1,
				hotel:[],
				isagree:0,
				isagree1:0,
				showxieyi:false,
				showxieyi2:false,
				changebookIndex:1,
				set:[],
				text:[],
				num:1,
			 
				yhmoney:0,
				totalprice:0,
				couponmoney:0,
				service_money:0,
				yajin:0,
				roomprice:0,
				totalroomprice:0,
				roomprices:[],
				userinfo:[],
				moneydec:false,
				moneyrate:0,
				dec_money:0,
				signatureurl:'',
				dikoutext:[],
				yjcount:0,
				showmoneyrate:0,
				dkmoney:0,
				usemoney:0,
				leftmoney:0,
				editorFormdata:[],
				name:'',
				tel:'',
				scoredkmaxmoney:0,
				isdisabled:false,
				usescore:0,
				scoredk_money:0,
				couponList:[],
			  couponvisible: false,
				couponrid: 0,
				couponnametext:'',
				coupon_money: 0,
				tmplids: [],
				qystatus:0,
				fwstatus:0,
				qyname:'',
				fwname:'',
				ysfont_size:0,
				ydfont_size:0,
				moneyunit:'元',
        startTime:'',
        endTime:'',
        
        //升级积分抵扣
        useupgradescore: 0,
        upgradescoredk_money: 0,
			}
		},
		onLoad(opt) {
			this.opt = app.getopts(opt);
			var sysinfo = uni.getWindowInfo();
			this.statusBarHeight = sysinfo.statusBarHeight;			
			this.wxNavigationBarMenu();
      
      this.startTime = app.getCache('startTime') || '';
      this.endTime   = app.getCache('endTime') || '';
      
			var startDate = app.getCache('startDate');
			console.log(startDate);
			var endDate = app.getCache('endDate');
			var day = new Date(startDate).getDay();
			var day2 = new Date(endDate).getDay();
		  var weekdays = ['周日', '周一', '周二', '周三', '周四', '周五', '周六'];
			this.startweek = weekdays[day];
			this.endweek = weekdays[day2];
			this.startDate = startDate
			this.endDate = endDate
			
			this.dayCount = this.opt.daycount
			this.roomid = this.opt.roomid
			this.getdata()
			//this.$refs.popup.open();
		},
		methods:{
			getdata:function(e){
				var that=this
				app.post('ApiHotel/buy', {id:that.roomid,dayCount:that.dayCount,startdate:that.startTime,enddate:that.endTime}, function (res) {
						if(res.status==1){
							that.set=res.set
							that.text = res.text
							that.room=res.room	
							that.hotel = res.hotel
							that.ysfont_size = res.hotel.ysfontsize
							that.ydfont_size = res.hotel.xyfontsize
							that.qystatus = res.hotel.qystatus
							that.fwstatus = res.hotel.fwstatus
							that.qyname = res.hotel.qyname
							that.fwname = res.hotel.fwname
							that.roomprices = res.roomprices
							that.totalroomprice  = res.totalroomprice
							that.userinfo = res.userinfo
							that.dikoutext = res.dikoutext
							that.yjcount = res.yjcount;
							that.moneyunit = res.moneyunit
							if(res.hotel.money_dikou==1){
								that.moneydec = true;
							}
							that.tmplids = res.tmplids
							var couponList = res.couponList;
							that.couponList = couponList;
							that.loaded();
							that.calculatePrice();
						}else if (res.status == 0) {
							if (res.msg) {
								app.alert(res.msg, function() {
									if (res.url) {
										app.goto(res.url);
									} else {
										app.goback();
									}
								});
							} else if (res.url) {
								app.goto(res.url);
							} else {
								app.alert('您没有权限预定该房型');
							}
							return;
						}
				 })
			},
			moneydk: function(e) {
				var that = this;
				var moneydec = that.moneydec
				if(moneydec){
					moneydec = false
				}else{
					moneydec = true
				}
				that.moneydec = moneydec
				that.calculatePrice();
			},
			showCouponList: function () {
			  this.couponvisible = true;
			},
			handleClickMask: function () {
			  this.couponvisible = false;
			},
			
			chooseCoupon: function (e) { //选择优惠券
				var couponrid = e.rid;
			  var couponkey = e.key;
			  if (couponrid == this.couponrid) {
			    this.couponkey = 0;
			    this.couponrid = 0;
			    this.coupontype = 1;
			    this.coupon_money = 0;
			    this.couponvisible = false;
			  } else {
			    var couponList = this.couponList;
			    var coupon_money = couponList[couponkey]['money'];
			    var coupontype = couponList[couponkey]['type'];
			    if (coupontype == 4) {
			      coupon_money = this.freightprice;
			    }
				this.couponnametext = couponList[couponkey].couponname;
			 
			    this.couponkey = couponkey;
			    this.couponrid = couponrid;
			    this.coupontype = coupontype;
			    this.coupon_money = coupon_money;
			    this.couponvisible = false;
			  }
			  this.calculatePrice();
			},
			//积分抵扣
			scoredk: function(e) {
				var that=this;
				if(this.leftmoney<=0 && !this.usescore){
          app.error('房费已为0，不可使用'+that.t('积分')+'抵扣');return;
				}
				var usescore = e.detail.value[0];
				if (!usescore) usescore = 0;
				this.usescore = usescore;
				this.calculatePrice();
			},
			//计算价格
			calculatePrice: function() {
				var that = this;
				var userinfo = that.userinfo;
				var money = userinfo && userinfo.money?parseFloat(userinfo.money):0;
				var hotel = that.hotel;
				var dikoutext = that.dikoutext
				var rate  = '';
				var num = that.num;
				var daycount = that.dayCount;
				var roomprices = that.roomprices;
				var coupon_money = parseFloat(that.coupon_money); //-优惠券抵扣 
				
				var usednum = 0;
				var dkmoney = 0;
				var usemoney = 0;
				for(var i=0;i<num;i++){
					if(dikoutext.length==0) continue;
					var thisdikou = dikoutext[0];
					for(var k=0;k<dikoutext.length;k++){
						if(k <= i) thisdikou = dikoutext[k];
					}
					for(var j=0;j<daycount;j++){
						var thisdkmoney = thisdikou.dikou_bl * that.roomprices[j].sell_price * 0.01;
						var olddkmoney = dkmoney;
						dkmoney += thisdkmoney;
						var oldusemoney = usemoney;
						if(hotel.money_dikou_type == 0){ //余额抵扣的是金额
							usemoney += thisdkmoney;
							if(usemoney > money){
								usemoney = money;
								dkmoney = money;
								break;
							}
						}else{ //余额抵扣的是天数 1余额代表1天
							usemoney += thisdikou.dikou_bl * that.roomprices[j].daymoney * 0.01;
							if(usemoney > money){
								usemoney = oldusemoney;
								dkmoney = olddkmoney;
								break;
							}
						}
					}
				}
				if(hotel.money_dikou_type == 1){
					this.usemoney = Math.round(usemoney);
				}else{
					this.usemoney = usemoney.toFixed(2);
				}
				var leftmoney = 0;
				if(that.moneydec==1){
						leftmoney = (that.totalroomprice*num-dkmoney).toFixed(2);
				}else{
						leftmoney = (that.totalroomprice*num).toFixed(2);
				}
				
	
				//是否可积分抵扣
				if(leftmoney<=0){
					that.usescore=0;
					that.isdisabled=true;
					that.coupon_money=0
					coupon_money = 0;
					that.couponkey = 0;
					that.couponrid = 0;
				}else{
					that.isdisabled=false;
				}
				if (that.usescore) {
					var scoredkmaxpercent = parseFloat(that.userinfo.scoredkmaxpercent); //最大抵扣比例
					var scoredk_money = parseFloat(that.userinfo.scoredk_money); //-积分抵扣
					if (scoredk_money > 0 && scoredkmaxpercent > 0 && scoredkmaxpercent <= 100 &&
						scoredk_money > leftmoney * scoredkmaxpercent * 0.01) {
						scoredk_money = (leftmoney * scoredkmaxpercent * 0.01).toFixed(2);;
					}
					that.scoredk_money = scoredk_money
					leftmoney =  (leftmoney-scoredk_money).toFixed(2);
					if (leftmoney < 0) leftmoney = 0;
				} else {
					var scoredk_money = 0;
				}
				that.leftmoney = leftmoney-coupon_money;

				/*
				for(var i=0;i<dikoutext.length;i++){
					var dikou = dikoutext[i];
					if(num>=dikou.dikou_num){
						rate = dikou.dikou_bl;
						var thisdkmoney = dikou.dikou_bl * that.totalroomprice * 0.01 * (dikou.dikou_num - usednum);
						dkmoney += thisdkmoney;
						usednum = dikou.dikou_num;
						if(hotel.money_dikou_type == 0){ //余额抵扣的是金额
							usemoney += thisdkmoney;
						}else{ //余额抵扣的是天数 1余额代表1天
							usemoney += Math.ceil(dikou.dikou_bl * 0.01);
						}
						if(usemoney > money){
							usemoney = money;break;
						}
					}
				}
				*/
				that.dkmoney = dkmoney.toFixed(2);
				that.showmoneyrate = rate
				that.moneyrate = rate
				
				var dkmoney = 0;
				var dec_money = 0;
				if(that.moneydec==1){
					dkmoney = that.dkmoney;
					dec_money = dkmoney;

					if(userinfo && money>0){
						if(dec_money>=money){
							if(hotel.money_dikou_type == 0){
								dec_money = money; 
							}else{
								
							}
						}
					}
				}
				that.dec_money = dec_money;

				var dayCount = that.dayCount;
				var room = that.room
				var service_money = 0;
				var num = that.num; //间/人数
				var hotel = that.hotel
				if(room.isservice_money==1){
					service_money = room.service_money*num*dayCount;
				}
				if(service_money>0) service_money = service_money.toFixed(2);
        //升级积分抵扣
        if (that.useupgradescore && service_money>0 && that.userinfo.upgradescore>0 && that.userinfo.upgradescoredk_money>0 && that.userinfo.upgradescoredkmaxpercent>0) {
          var upgradescoredk_money     = parseFloat(that.userinfo.upgradescoredk_money); //会员升级积分换算最大可抵扣数值
        
          var upgradescoremaxtype = parseInt(that.userinfo.upgradescoremaxtype);//兑换类型
          if (upgradescoremaxtype == 0) {
            var upgradescoredkmaxpercent = parseFloat(that.userinfo.upgradescoredkmaxpercent); //最大抵扣比例
            var nowupgradescoredk_money  = service_money * upgradescoredkmaxpercent * 0.01;//现在可最大抵扣数值
          } else{
            var nowupgradescoredk_money = parseFloat(that.userinfo.upgradescoredkmaxmoney); //现在可最大抵扣数值
          }
        
          if(nowupgradescoredk_money>0){
            nowupgradescoredk_money = nowupgradescoredk_money.toFixed(2);
            if(nowupgradescoredk_money<=upgradescoredk_money){
              service_money -= nowupgradescoredk_money;
              that.upgradescoredk_money = nowupgradescoredk_money;
            }else{
              service_money -= upgradescoredk_money;
              that.upgradescoredk_money = upgradescoredk_money;
            }
          }
          if(service_money<=0){
            service_money = 0;
          }else{
            service_money = service_money * 100;
            service_money = Math.round(service_money)/100;
          } 
        }
        if(service_money<0) service_money;
        that.service_money = service_money;
        
				var yjcount = that.yjcount;
				var yajin=0;
				if(yjcount==0){
					if(room.isyajin==1){
							yajin = room.yajin_money*num
					}else if(room.isyajin==2){
							yajin = room.yajin_money
					}else if(room.isyajin==-1){
							yajin=0;
					}else{
						if(that.hotel.isyajin==1){
								yajin = that.hotel.yajin_money*num
						}else if(that.hotel.isyajin==2){
								yajin = that.hotel.yajin_money
						}else{
							yajin = 0
						}
					}		
				}
				var roomprice =  parseFloat(that.totalroomprice)*num - dec_money - scoredk_money-coupon_money;
			  that.roomprice = roomprice.toFixed(2);
				
				that.yajin =parseFloat(yajin).toFixed(2);
				that.totalprice = (parseFloat(service_money) + parseFloat(yajin) + roomprice).toFixed(2);
			},
			createorder:function(e){
					var that=this
	
					var isagree = that.isagree
					if(!isagree && that.hotel.isqianzi==1){
						return app.error("请先阅读并签订"+that.hotel.xyname);
					}	else if(!isagree &&  that.hotel.xystatus==1){
						return app.error("请先阅读并同意"+that.hotel.xyname);
					}
					var isagree1 = that.isagree1
					if(!isagree1 && that.hotel.xystatus==1){
						return app.error("请先阅读并同意"+that.hotel.ysname);
					}
					var signatureurl = that.signatureurl;
					if(!signatureurl && that.hotel.isqianzi==1){
							return app.error("请先签订"+that.hotel.xyname);
					}
					
					
					var num = that.num
					var formdata = e.detail.value
					var formname = {};
					for (var i = 0; i < num;i++){
						var thisfield = 'name' + i;
						if ( formdata[thisfield] === undefined || formdata[thisfield].length==0){
								app.error('请填写姓名');return;
						}
						formname[i] = formdata[thisfield];
					}
					formdata.names = formname
					if(!formdata.tel){
						return app.error("请填写联系电话");
					}
	
					if(formdata.tel.trim()!= '' && !app.isPhone(formdata.tel)){
						return app.error("请填写正确的手机号");
					}
					
					var hotel = that.hotel;
					console.log(hotel);
					var formdata_fields = hotel.formdata;
					 
					console.log(formdata);
			 
					for (var j = 0; j < formdata_fields.length;j++){
						var thisfield = 'form'+ '_' + j;
						 console.log(thisfield);
						if (formdata_fields[j].val3 == 1 && (formdata[thisfield] === '' || formdata[thisfield] === undefined || formdata[thisfield].length==0)){
								app.alert(formdata_fields[j].val1+' 必填');return;
						}
						if (formdata_fields[j].key == 'selector') {
								formdata[thisfield] = formdata_fields[j].val2[formdata[thisfield]]
						}						 
					}
				 
					 

					var couponkey = this.couponkey;
					var couponrid = this.couponrid;
          
					app.showLoading('提交中');
					app.post('ApiHotel/createOrder',{formdata:formdata,roomid:that.roomid,dayCount:that.dayCount,num:that.num,startDate:that.startDate,endDate:that.endDate,signatureurl:signatureurl,moneydec:that.moneydec,usescore:that.usescore,couponrid: couponrid,startTime:that.startTime,endTime:that.endTime,useupgradescore:that.useupgradescore}, function (res) {
						app.showLoading(false);
						if(res.status==1){
							  that.subscribeMessage(function () {
									app.goto('/pagesExt/pay/pay?id=' + res.payorderid,'redirectTo');
								})
						}else{
							if (res.url) {
								app.goto(res.url);
							} 
							app.error(res.msg);
							return;
						}
					});
					
			},
			inputname: function(e) {
				var that=this
				var name = e.detail.value;
				console.log(name)
			},
			changebook(item){
					var that=this					
					this.changebookIndex = item;
					that.num = item
					this.coupon_money = 0;
					var startdate = app.getCache('startTime');

			 
					this.couponrid = 0
					app.post('ApiHotel/getYajin',{num:item,roomid:that.roomid,startdate:startdate}, function (res) {
						if(res.status==1){
							that.yjcount = res.yjcount
							that.couponList = res.couponlist
							that.calculatePrice();
						}else{
							app.error(res.msg);
							return;
						}
					});
					
			
			},
			/***自定义表单 */
					//选择时间
	    	chooseTime: function(e) {
				var that = this;
				var prodata = that.prodata.split(',');
				that.proid = prodata[0]
				that.timeDialogShow = true;
				that.timeIndex = -1;
				var curTopIndex = that.datelist[0];
				that.nowdate = that.datelist[that.curTopIndex].year+that.datelist[that.curTopIndex].date;
				that.loading = true;
				app.get('ApiYuyue/isgetTime', { date:that.nowdate,proid:that.proid}, function (res) {
				  that.loading = false;
				  that.timelist = res.data;
				})
			},	
 			editorChooseImage: function (e) {
				var that = this;
				var bid = e.currentTarget.dataset.bid;
				var idx = e.currentTarget.dataset.idx;
				var editorFormdata = that.hotel.editorFormdata;
				if(!editorFormdata) editorFormdata = [];
				app.chooseImage(function(data){
					editorFormdata[idx] = data[0];
					// console.log(editorFormdata)
					that.editorFormdata = editorFormdata
					that.hotel.editorFormdata = editorFormdata
					that.test = Math.random();
				})
			},
			removeimg:function(e){
				var that = this;
				var bid = e.currentTarget.dataset.bid;
				var idx = e.currentTarget.dataset.idx;
				var pics = that.editorFormdata
				pics.splice(idx,1);
				that.editorFormdata = pics;
				that.allbuydata[bid].editorFormdata = that.editorFormdata;
			},
			editorBindPickerChange:function(e){
				var that = this;
				var bid = e.currentTarget.dataset.bid;
				var idx = e.currentTarget.dataset.idx;
				var val = e.detail.value;
				
				var editorFormdata = that.hotel.editorFormdata;
				if(!editorFormdata) editorFormdata = [];
				editorFormdata[idx] = val;
			 
				that.hotel.editorFormdata = editorFormdata;
			 
				let aaa=that.hotel.formdata
				that.hotel.formdata = [];
				that.hotel.formdata = aaa;
			 
				that.test = Math.random();
			},
			removeimg:function(e){
				var that = this;
				var bid = e.currentTarget.dataset.bid;
				var idx = e.currentTarget.dataset.idx;
				var pics = that.editorFormdata
				pics.splice(idx,1);
				that.editorFormdata = pics;
				that.allbuydata[bid].editorFormdata = that.editorFormdata;
			},
		 
		 	/***自定义表单 */
			showxieyiFun: function () {
				var that=this
				if(!this.isagree1){
					app.error('请先阅读并同意'+that.hotel.ysname);return;
				}
			  this.showxieyi = true;
			},
			hidexieyi: function () {
				var that=this
			  this.showxieyi = false;
				that.isagree = true;
			},
			hidexieyi2: function () {
			  this.showxieyi2 = false;
				this.isagree1 = true;
			},
			showxieyiFun2: function () {
			  this.showxieyi2 = true;
			},
			// 不同意协议
			closeXieyi(){
				this.showxieyi = false;
				this.showxieyi2 = false;
				this.isagree = false;
			},
			changeChecked:function(e){
				var that=this
				if(!this.isagree){
						that.isagree1 = 1;
						that.isagree = 1;
				}else{
						that.isagree = 0;
						that.isagree1 = 0;
				}
				console.log(	that.isagree );
			},
			showDetail:function(e){

					// 房型详情-------------------------------------------------------------------------------------------
					this.$refs.popupDetail.open();
			},
			bookingChange(){
				this.$refs.popupBook.open();
			},
			mignxiChange(){
				this.$refs.popup.open();
			},
			popupClose(){
				this.$refs.popup.close();
			},	
			popupdetailClose(){
				this.$refs.popupDetail.close();
			},
			swiperChange(event){
				this.bannerindex = event.detail.current+1;
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
					console.log(app.globalData.initdata.color1rgb,'/*-/*-/-*')
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
      //升级积分抵扣
      upgradescoredk: function(e) {
      	var useupgradescore  = e.detail.value[0];
      	if (!useupgradescore) useupgradescore = 0;
      	this.useupgradescore = useupgradescore;
      	this.calculatePrice();
      },
		},
    goback: function() {
      var that = this;
    	getApp().goback();
    },
	}


</script>

<style>
	/*  */
	.uni-popup__wrapper-box{background: #f7f8fa;border-radius: 40rpx 40rpx 0rpx 0rpx;overflow: hidden;}
	.hotelpopup__content .popup-but-view{padding: 30rpx 20rpx;background: #fff;}
	.popup-but-view .price-view{} 
	.popup-but-view .price-view .text{color: #222222;font-size: 24rpx;font-weight: bold;}
	.popup-but-view .price-view .price-text{color:#06D470;font-size: 36rpx;font-weight: bold;margin-top: 15rpx;}
	.popup-but-view .cost-details{color: #06D470;font-size: 24rpx;font-weight: bold;}
	.popup-but-view .cost-details image{width:24rpx;height: 24rpx;margin: 0rpx 20rpx 0rpx 10rpx;}
	.popup-but-view .but-class{background: linear-gradient(90deg, #06D470 0%, #06D4B9 100%);font-size: 32rpx;color: #fff;font-weight: bold;padding:0 30rpx;	width: 216rpx;border-radius: 60rpx;text-align: center;letter-spacing: 3rpx;}
	
	.hotelpopup__content{width: 100%;height:auto;position: relative;}
	.hotelpopup__content .popup-close{position: fixed;right: 20rpx;top: 20rpx;width: 56rpx;height: 56rpx;z-index: 11;}
	.hotelpopup__content .popup-close image{width: 100%;height: 100%;}
	.hotelpopup__content .hotel-equity-view{width: 100%;padding:30rpx 40rpx 40rpx;background: #fff;margin-top: 20rpx;}
	.hotel-equity-view .equity-title-view{align-items: center;justify-content: center;border-bottom: 1px #efefef solid;}
	.hotel-equity-view .equity-title-view .equity-title{color: #1E1A33;font-size: 30rpx;font-weight: bold;}
	
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
	.hotel-equity-view  .cost-details{width: 100%;padding-bottom: 30rpx;border-bottom: 1px #efefef solid;margin-top: 40rpx;}
	.hotel-equity-view  .cost-details .price-view{padding-bottom: 10rpx;}
	.hotel-equity-view  .cost-details .price-view .price-text{color: rgba(30, 26, 51, 0.8);font-size: 24rpx;}
	.hotel-equity-view  .cost-details .price-view .price-num{color: #1E1A33;font-size: 24rpx;}
	.hotel-equity-view  .cost-details .price-view .price-text-title{color: rgba(30, 26, 51, 0.8);font-size: 30rpx;font-weight: bold;}
	.hotel-equity-view  .cost-details .price-view .price-num-title{color: #1E1A33;font-size: 30rpx;font-weight: bold;}
	/*  */
	.but-view{width: 100%;background: #fff;position: fixed;bottom: 0rpx;padding: 20rpx;z-index: 2;box-shadow: 0rpx 0rpx 10rpx 5rpx #ebebeb;}
	.but-view .select-view{width: 32rpx;height: 32rpx;border-radius: 50%;border: 1px solid #D9D9DA;margin-right: 20rpx;}
	.but-view  .select-view image{width: 20rpx;height: 20rpx;}
	.read-agreement .t1{ font-weight: bold; font-size: 34rpx;}
	
	.but-view  .select-view-active{width: 32rpx;height: 32rpx;border-radius: 50%;border: 1px solid #06D470;margin-right: 20rpx;background: #06D470;
	display: flex;align-items: center;justify-content: center;}
	.but-view  .select-view-active image{width: 20rpx;height: 20rpx;}
	.but-view .read-agreement{width: 100%;border-bottom: 1px #e6e6e6 solid;justify-content: flex-start;padding-bottom: 30rpx;padding-top:10rpx;color: #59595D;font-size: 24rpx;}
	.but-view .yuding-view{padding-bottom: env(safe-area-inset-bottom);padding-left: 20rpx;padding-right: 20rpx;padding-top: 30rpx;	}
	.but-view .yuding-view .price-view{}
	.but-view .yuding-view .price-view .text{color: #222222;font-size: 24rpx;font-weight: bold;}
	.but-view .yuding-view .price-view .price-text{color:#06D470;font-size: 36rpx;font-weight: bold;margin-top: 15rpx;}
	.but-view .yuding-view .cost-details{color: #06D470;font-size: 24rpx;font-weight: bold;}
	.but-view .yuding-view .cost-details image{width:24rpx;height: 24rpx;margin: 0rpx 20rpx 0rpx 10rpx;}
	.but-view .yuding-view .but-class{background: linear-gradient(90deg, #06D470 0%, #06D4B9 100%);font-size: 32rpx;color: #fff;font-weight: bold;padding: 30rpx;
	width: 216rpx;border-radius: 60rpx;text-align: center;letter-spacing: 3rpx;}
	.but-view  .yuding-view .but-class1{background: linear-gradient(90deg, #06D470 0%, #06D4B9 100%);font-size: 32rpx;color: #fff;font-weight: bold;
	width: 216rpx;border-radius: 60rpx;text-align: center;letter-spacing: 3rpx;}
	
	/*  */
	.position-view{width: 100%;position: absolute;padding-bottom: 100rpx;}
	.position-view .order-time-details{width: 96%;margin: 0 auto;background: #fff;border-radius: 8px;padding:20rpx 30rpx;}
	.order-time-details .time-view{width: 100%;}
	.order-time-details .time-view .time-options{}
	.order-time-details .time-view .time-options .month-tetx{color: #1E1A33;font-size: 28rpx;font-weight: bold;}
	.order-time-details .time-view .time-options .day-tetx{color: rgba(30, 26, 51, 0.4);font-size: 22rpx;margin-left: 20rpx;}
	.order-time-details .time-view .content-text{width: 46px;height: 40rpx;line-height: 39rpx;text-align: center;border-radius: 20px;color: #000;font-size: 20rpx;border:1px #000 solid;position: relative;}
	.order-time-details .time-view .content-text .content-decorate{width: 13rpx;height: 2rpx;background: red;position: absolute;top: 50%;}
	.order-time-details .time-view .content-text .left-c-d{left: -13rpx;background: #000;}
	.order-time-details .time-view .content-text .right-c-d{right: -13rpx;background: #000;}
	.order-time-details .name-info{width: 100%;padding: 30rpx 0rpx;}
	.order-time-details .name-info .name-text {color: #1E1A33;font-size: 30rpx;font-weight: bold;}
	.order-time-details .name-info .name-tisp{color: #A5A3AD;font-size: 24rpx;margin-top: 15rpx;}
	.order-time-details .hotel-details-view{color: #06D470;font-size: 24rpx;}
	.order-time-details .hotel-details-view image{width: 22rpx;height: 22rpx;margin-left: 10rpx;}
	.order-time-details .time-warning{background: rgba(27, 204, 152, 0.06);width: 100%;padding: 20rpx 0rpx;border-radius: 8px;color: #06D470;font-size: 24rpx;justify-content: flex-start;}
	.order-time-details .time-warning image{width: 32rpx;height: 32rpx;margin-right: 20rpx;margin-left: 20rpx;}
	/*  */
	.navigation {width: 100%;padding-bottom:10px;overflow: hidden;position: fixed;top: 0;z-index: 2;background: #08DA70;}
	.navcontent {display: flex;align-items: center;padding-left: 10px;}
	.header-location-top{position: relative;display: flex;justify-content: center;align-items: center;flex:1;}
	.header-back-but{position: absolute;left:0;display: flex;align-items: center;width: 40rpx;height: 45rpx;overflow: hidden;}
	.header-back-but image{width: 40rpx;height: 45rpx;} 
	.header-page-title{font-size: 36rpx;}


	.hotelpopup__content .popup-banner-view{width: 100%;height: 500rpx;position: relative;}
	.hotelpopup__content .popup-banner-view .popup-numstatistics{position: absolute;right: 20rpx;bottom: 20rpx;background: rgba(0, 0, 0, 0.3);
	border-radius: 28px;width: 64px;height: 28px;text-align: center;line-height: 28px;color: #fff;font-size: 20rpx;}
	.hotelpopup__content .hotel-details-view{width: 100%;padding: 30rpx 40rpx;background: #fff;}
	.hotelpopup__content .hotel-details-view	.hotel-title{color: #1E1A33;font-size: 40rpx;}
	.hotelpopup__content .hotel-details-view	.introduce-view{width: 100%;align-items: center;flex-wrap: wrap;justify-content: flex-start;padding: 20rpx 10rpx;}
	.hotelpopup__content .hotel-details-view	.introduce-view .options-intro{padding: 15rpx 0rpx;margin-right: 20rpx;width:auto;}
	.hotel-details-view	.introduce-view .options-intro image{width: 32rpx;height: 32rpx;}
	.hotel-details-view	.introduce-view .options-intro .options-title{color: #1E1A33;font-size: 24rpx;margin-left: 15rpx;}
	.hotel-details-view .other-view{width: 100%;justify-content: flex-start;padding: 12rpx 0rpx;}
	.hotel-details-view .other-view .other-title{color: #A5A3AD;font-size: 24rpx;margin-right: 40rpx;}
	.hotel-details-view .other-view .other-text{color: #1E1A33;font-size: 24rpx;}	
	
	 /*  */
	.dp-banner{width: 100%;height: 250px;}
	.dp-banner-swiper{width:100%;height:100%;}
	.dp-banner-swiper-img{width:100%;height:auto}
	.banner-poster{width: 82%;margin: 30rpx auto 0rpx;display: flex;flex-direction:column;align-items: flex-end;}
	.banner-poster .poster-title{color: #FFFFFF;font-size: 56rpx;font-weight: 900;padding: 30rpx 0rpx;}
	.banner-poster .poster-text{color: #FFFFFF;font-size: 26rpx;opacity: 0.6;padding: 10rpx 0rpx;}
	.banner-poster .poster-but{width: 108px;height: 36px;color: #FFFFFF;text-align: center;line-height: 36px;font-size: 28rpx;font-weight: bold;margin: 40rpx 0rpx;border-radius: 36px;}
	/*  */
	
	/*  */
	.hotelpopup__content .hotel-equity-view{width: 100%;padding:30rpx 40rpx 40rpx;background: #fff;}
	.hotel-equity-view .equity-title-view{align-items: center;justify-content: flex-start; padding: 20rpx 0;}
	.hotel-equity-view .equity-title-view .equity-title{color: #1E1A33;font-size: 32rpx;font-weight: bold;}
	.hotel-equity-view .equity-title-view .equity-title-tisp{color: #A5A3AD;font-size: 24rpx;margin-left: 28rpx;}
	.hotel-equity-view .equity-options{margin-top: 40rpx;}
	.hotel-equity-view .equity-options .options-title-view{align-items: center;justify-content: flex-start;}
	.hotel-equity-view .equity-options .options-title-view image{width: 28rpx;height: 28rpx;margin-right: 20rpx;}
	.hotel-equity-view .equity-options .options-title-view  .title-text{color: #1E1A33;font-size: 28rpx;font-weight: bold;}
	.hotel-equity-view .equity-options .options-text{color: rgba(30, 26, 51, 0.8);font-size: 24rpx;padding: 15rpx 0rpx;line-height: 40rpx;margin-left: 50rpx;margin-right: 50rpx;}
	/*  */
	
	.xieyibox{width:100%;height:100%;position:fixed;top:0;left:0;z-index:99;background:rgba(0,0,0,0.7)}
	.xieyibox-content{width:90%;margin:0 auto;height:80%;margin-top:20%;background:#fff;color:#333;padding:5px 10px 50px 10px;position:relative;border-radius:2px}
	.xieyibox-content .xieyibut-view{height: 60rpx;position:absolute;z-index:9999;bottom:10px;left:0;right:0;margin:0 auto;justify-content: space-around;}
	.xieyibox-content .xieyibut-view .but-class{text-align:center; width: auto;height: 60rpx; line-height: 60rpx; color: #fff; border-radius: 8rpx;padding:0rpx 25rpx;}
	
	.position-view .order-options-view{width: 96%;margin: 20rpx auto 0rpx;background: #fff;border-radius: 8px;padding:20rpx 30rpx;}
		.order-options-view .options-title{color: #1E1A33;font-size: 32rpx;font-weight: bold;padding-bottom: 20rpx;}
		/* +++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++ */
		.order-options-view .options-title .right-view{color: #1BCC98;font-size: 24rpx;}
		.order-options-view .options-title .right-view image{width: 24rpx;height: 24rpx;margin-left: 10rpx;}
		.order-options-view .booking-options{width: 100%;padding: 20rpx 0rpx;}
		.order-options-view .booking-options .book-title{color: #888889;font-size: 28rpx;text-align: left;width: 140rpx;}
		.order-options-view .booking-options .room-number-view{width: 510rpx;}
		.order-options-view .booking-options .room-number-view .room-options{background: #F8F9FD;border-radius: 4px;padding: 18rpx 21rpx;display: inline-block;
		margin-right: 20rpx;color: #222222;font-size: 28rpx;}
		.order-options-view .booking-options .room-number-view .room-options-active{border: 1px solid rgba(27, 204, 152, 0.5);background: #E8FFF7;box-sizing: border-box;color: #06D470;}
		.order-options-view .booking-options .room-form{width: 510rpx;padding: 18rpx 0rpx;}
		.order-options-view .booking-options .room-form input{width: 100%; font-size: 24rpx;}
		/* ++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++ */
		.order-options-view .preferential-view{padding: 20rpx 0rpx;}
		.order-options-view .preferential-view .pre-title{color: #888889;font-size: 28rpx;}
		.order-options-view .preferential-view .pre-text{color: #222229;font-size: 28rpx;}
		.order-options-view .preferential-view .pre-text image{width: 24rpx;height: 24rpx;margin-left: 20rpx;}
		/*  */
		.requirement-view{background: #F4F5F9;width: 100%;border-radius: 8px;height: 300rpx;margin-top: 20rpx;padding: 30rpx;}
		/*  */
		.titlebg-postions{position: absolute;left: 0;top:0;width: 100%;height: 50vh;overflow: hidden;}
		.titlebg-postions .titlebg-view{width: 1500rpx;height: 1500rpx;background: #08DA70;border-radius:50%;position: absolute;left: 50%;transform: translate(-50%,-50%);}
		/*  */
		.navigation {width: 100%;padding-bottom:10px;overflow: hidden;position: fixed;top: 0;z-index: 2;background: #08DA70;}
		.navcontent {display: flex;align-items: center;padding-left: 10px;}
		.header-location-top{position: relative;display: flex;justify-content: center;align-items: center;flex:1;}
		.header-back-but{position: absolute;left:0;display: flex;align-items: center;width: 40rpx;height: 45rpx;overflow: hidden;}
		.header-back-but image{width: 40rpx;height: 45rpx;} 
		.header-page-title{font-size: 36rpx;}
		
		/*余额抵扣*/
		.price .f1 {color: #333}
		.price .f2 {color: #111;font-weight: bold;text-align: right;flex: 1}
		.price .f3 {width: 24rpx;height: 24rpx;}
		.price .couponname{color:#fff;padding:4rpx 16rpx;font-weight:normal;border-radius:8rpx;font-size:24rpx;display:inline-block;margin:2rpx 0 2rpx 10rpx}
		/*订房说明*/
		.booking_notice{ padding: 30rpx;}


		.form-item {width: 100%;padding: 16rpx 0;background: #fff;display: flex;align-items: center;justify-content:space-between}
		.form-item .label {color: #333;width: 200rpx;flex-shrink:0}
		.form-item .radio{transform:scale(.7);}
		.form-item .checkbox{transform:scale(.7);}
		.form-item .input {border:0px solid #eee;height: 70rpx;padding-left: 10rpx;text-align: right;flex:1}
		.form-item .textarea{height:140rpx;line-height:40rpx;overflow: hidden;flex:1;border:1px solid #eee;border-radius:2px;padding:8rpx;margin-left: 36rpx;}
		.form-item .radio-group{display:flex;flex-wrap:wrap;justify-content:flex-end}
		.form-item .radio{height: 70rpx;line-height: 70rpx;display:flex;align-items:center}
		.form-item .radio2{display:flex;align-items:center;}
		.form-item .radio .myradio{margin-right:10rpx;display:inline-block;border:1px solid #aaa;background:#fff;height:32rpx;width:32rpx;border-radius:50%}
		.form-item .checkbox-group{display:flex;flex-wrap:wrap;justify-content:flex-end}
		.form-item .checkbox{height: 70rpx;line-height: 70rpx;display:flex;align-items:center}
		.form-item .checkbox2{display:flex;align-items:center;height: 40rpx;line-height: 40rpx;}
		.form-item .checkbox .mycheckbox{margin-right:10rpx;display:inline-block;border:1px solid #aaa;background:#fff;height:32rpx;width:32rpx;border-radius:2px}
		.form-item .picker{height: 70rpx;line-height:70rpx;flex:1;text-align:right}
		.form-imgbox{margin-right:16rpx;margin-bottom:10rpx;font-size:24rpx;position: relative;}
		.form-imgbox-close{position: absolute;display: block;width:32rpx;height:32rpx;right:-16rpx;top:-16rpx;color:#999;font-size:32rpx;background:#fff}
		.form-imgbox-close .image{width:100%;height:100%}
		.form-imgbox-img{display: block;width:180rpx;height:180rpx;padding:2px;border: #d3d3d3 1px solid;background-color: #f6f6f6;overflow:hidden}
		.form-imgbox-img>.image{width:100%;height:100%}
		.form-imgbox-repeat{position: absolute;display: block;width:32rpx;height:32rpx;line-height:28rpx;right: 2px;bottom:2px;color:#999;font-size:30rpx;background:#fff}
		.form-uploadbtn{position:relative;height:180rpx;width:180rpx;margin-right: 16rpx;margin-bottom:10rpx;}
	</style>
</style>