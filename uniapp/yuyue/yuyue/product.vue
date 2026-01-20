<template>
<view>
	<block v-if="isload">
		<block v-if="sysset.showgzts">
			<view style="width:100%;height:88rpx"> </view>
			<view class="follow_topbar">
				<view class="headimg"><image :src="sysset.logo"/></view>
				<view class="info">
					<view class="i">欢迎进入 <text :style="{color:t('color1')}">{{sysset.name}}</text></view>
					<view class="i">关注公众号享更多专属服务</view>
				</view>
				<view class="sub" @tap="showsubqrcode" :style="{'background-color':t('color1')}">立即关注</view>
			</view>
			<uni-popup id="qrcodeDialog" ref="qrcodeDialog" type="dialog">
				<view class="qrcodebox">
					<image :src="sysset.qrcode" @tap="previewImage" :data-url="sysset.qrcode" class="img"/>
					<view class="txt">长按识别二维码关注</view>
					<view class="close" @tap="closesubqrcode">
						<image :src="pre_url+'/static/img/close2.png'" style="width:100%;height:100%"/>
					</view>
				</view>
			</uni-popup>
		</block>
		
		<dp-guanggao :guanggaopic="guanggaopic" :guanggaourl="guanggaourl"></dp-guanggao>

		<view style="position:fixed;top:15vh;left:20rpx;z-index:9;background:rgba(0,0,0,0.6);border-radius:20rpx;color:#fff;padding:0 10rpx" v-if="bboglist.length>0">
			<swiper style="position:relative;height:54rpx;width:350rpx;" :autoplay="true" :interval="5000" :vertical="true">
				<swiper-item v-for="(item, index) in bboglist" :key="index" @tap="goto" :data-url="'/yuyue/yuyue/product?id=' + item.proid" class="flex-y-center">
					<image :src="item.headimg" style="width:40rpx;height:40rpx;border:1px solid rgba(255,255,255,0.7);border-radius:50%;margin-right:4px"/>
					<view style="width:300rpx;white-space:nowrap;overflow:hidden;text-overflow: ellipsis;font-size:22rpx">{{item.nickname}} {{item.showtime}}购买了该商品</view>
				</swiper-item>
			</swiper>
		</view>
		<!-- <view class="topbox"><image :src="pre_url+'/static/img/goback.png'" class="goback" /></view> -->
		<view class="swiper-container" v-if="isplay==0" >
			<swiper class="swiper" :indicator-dots="false" :autoplay="true" :interval="500000" @change="swiperChange" :style="{ height: swiperHeight + 'px' }">
				<block v-for="(item, index) in product.pics" :key="index">
					<swiper-item class="swiper-item">
						<view class="swiper-item-view" :style="{ height: swiperHeight + 'px' }">
							<image class="img" :src="item" mode="widthFix" @load="loadImg" />
						</view>
					</swiper-item>
				</block>
			</swiper>
			<view class="imageCount" v-if="(product.pics).length > 1">{{current+1}}/{{(product.pics).length}}</view>
			<view v-if="product.video" class="provideo" @tap="payvideo"><image :src="pre_url+'/static/img/video.png'"/><view class="txt">{{product.video_duration}}</view></view>
		</view>
	
		<view class="header"> 
			<view class="price_share">
				<view class="title">{{product.name}}</view>
				<view class="share" @tap="shareClick"><image class="img" :src="pre_url+'/static/img/share.png'"/><text class="txt">分享</text></view>
			</view>
			<view class="pricebox flex">
				<view class="price">
					<view class="f1" :style="{color:t('color1')}" v-if="set.show_free && product.min_price <= 0 && product.max_price <= 0">免费</view>
					<view class="f1" :style="{color:t('color1')}" v-else>
						{{product.min_price}}<text v-if="product.max_price!=product.min_price">-{{product.max_price}}</text><text style="font-size:24rpx;font-weight:normal;padding-left:6rpx">元/{{product.danwei}}</text>
					</view>
					<view class="f2" v-if="product.market_price*1 > product.sell_price*1">￥{{product.market_price}}<text v-if="product.max_price!=product.min_price">起</text></view>
				</view>
				<view class="sales_stock">
					<view class="f1" >已售：{{product.sales}} </view>
				</view>
				
			</view>
			
			<view class="sellpoint" v-if="product.sellpoint">{{product.sellpoint}}</view>
		
			<view style="margin:20rpx 0;color:red;font-size:22rpx" v-if="product.balance_price > 0">定金金额：{{product.advance_price}}元，尾款金额：{{product.balance_price}}元</view>
			
			<view class="cuxiaodiv" v-if="fuwulist.length>0">
				<view class="fuwupoint" v-if="fuwulist.length>0">
					<view class="f1" @tap="showfuwudetail">
						<view class="t" v-for="(item, index) in fuwulist" :key="index">{{item.name}}</view>
					</view>
					<view class="f2" @tap="showfuwudetail">
						<image :src="pre_url+'/static/img/arrow-point.png'" mode="widthFix"/>
					</view>
				</view>
			</view>
		</view>
		
		<view class="cuxiaopoint" v-if="couponlist.length>0">
			<view class="f0">优惠</view>
			<view class="f1" @tap="showcuxiaodetail">
				<view v-for="(item, index) in couponlist" :key="index" class="t" :style="{background:'rgba('+t('color1rgb')+',0.1)',color:t('color1')}"><text class="t0" style="padding:0 6px">券</text><text class="t1">{{item.name}}</text></view>
			</view>
			<view class="f2" @tap="showcuxiaodetail">
				<image :src="pre_url+'/static/img/arrow-point.png'" mode="widthFix"/>
			</view>
		</view>
		
		<view class="choosebox">
      <view v-if="showselectpeople && product.fwpeople==1" @tap="gotopeople"  class="choosedate" style="border-bottom: 2rpx solid #eee;">
      	<view class="f0">服务人员</view>
      	<view class="f1 flex1">{{worker?worker.realname:'请选择人员'}}</view>
      	<image class="f2" :src="pre_url+'/static/img/arrowright.png'"/>
      </view>
			<view class="choose" @tap="buydialogChange" data-btntype="2">
				<view class="f0">选择服务</view>
				<view class="flex1 flex-y-center">
					<text class="xuanzefuwu-text">{{ggname}}</text> 
					<text v-if="num">× {{num}}</text>
				</view>
				<image class="f2" :src="pre_url+'/static/img/arrowright.png'"/>
			</view>
			
			<block v-if="product.rqtype == 5">
				<view class="choosedate"  @tap="chooseTime5">
				  <view class="f0">服务时间</view>
				  <view class="f1 flex1">
				    {{selectedDate === '' ? '请选择预约时间' : selectedDate}} 
				    {{selectedTime === '' ? '' : selectedTime}}
				  </view>
				  <image class="f2" :src="pre_url+'/static/img/arrowright.png'"/>
				</view>
				
			</block>
			<block v-else>
				<block v-if="!isfuwu">
					<block v-if="!selmoretime">
						<view class="choosedate" @tap="chooseTime">
							<view class="f0">服务时间</view>
							<view class="f1 flex1">{{yydate}}</view>
							<image class="f2" :src="pre_url+'/static/img/arrowright.png'"/>
						</view>
					</block>
					<block v-else>
						<view class="choosedate" @tap="chooseTime2">
							<view class="f0">服务时间</view>
							<view class="f1 flex1">{{yydate}}</view>
							<image class="f2" :src="pre_url+'/static/img/arrowright.png'"/>
						</view>
						<view class="choosedate">
							<view class="f0">已选数量</view>
							<view class="f1 flex1">{{product.timejg}}分钟 X {{sortsnum}}</view>
						</view>
					</block>
				</block>
			</block>
		</view>
		
		<view class="commentbox" v-if="commentcount > 0">
			<view class="title">
				<view class="f1">评价({{commentcount}})</view>
				<view class="f2" @tap="goto" :data-url="'commentlist?proid=' + product.id">好评率 <text :style="{color:t('color1')}">{{product.comment_haopercent}}%</text><image style="width:32rpx;height:32rpx;" :src="pre_url+'/static/img/arrowright.png'"/></view>
			</view>
			<view class="comment">
				<view class="item" v-if="commentlist.length>0">
					<view class="f1">
						<image class="t1" :src="commentlist[0].headimg"/>
						<view class="t2">{{commentlist[0].nickname}}</view>
						<view class="flex1"></view>
						<view class="t3"><image class="img" v-for="(item2,index2) in [0,1,2,3,4]" :key="index2"  :src="pre_url+'/static/img/star' + (commentlist[0].score>item2?'2native':'') + '.png'"/></view>
					</view>
					<view class="f2">
						<text class="t1">{{commentlist[0].content}}</text>
						<view class="t2">
							<block v-if="commentlist[0].content_pic!=''">
								<block v-for="(itemp, index) in commentlist[0].content_pic" :key="index">
									<view @tap="previewImage" :data-url="itemp" :data-urls="commentlist[0].content_pic">
										<image :src="itemp" mode="widthFix"/>
									</view>
								</block>
							</block>
						</view>
					</view>
					<view class="f3" @tap="goto" :data-url="'commentlist?proid=' + product.id">查看全部评价</view>
				</view>
				<view v-else class="nocomment">暂无评价~</view>
			</view>
		</view>
		<view class="shop" v-if="shopset.showjd==1">
			<image :src="business.logo" class="p1"/>
			<view class="p2 flex1">
				<view class="t1">{{business.name}}</view>
				<view class="t2">{{business.desc}}</view>
			</view>
			<button class="p4" :style="{background:'linear-gradient(90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'}" @tap="goto" :data-url="product.bid==0?'/pages/index/index':'/pagesExt/business/index?id='+product.bid" data-opentype="reLaunch">进入店铺</button>
		</view>
		<view class="detail_title"><view class="t1"></view><view class="t2"></view><view class="t0">服务详情</view><view class="t2"></view><view class="t1"></view></view>
		<view class="detail">
			<dp :pagecontent="pagecontent"></dp>
		</view>
		
		<!-- #ifdef MP-TOUTIAO -->
		<view class="dp-cover" v-if="video_status">
			<button open-type="share" data-channel="video" class="dp-cover-cover" :style="{
				zIndex:10,
				top:'60vh',
				left:'80vw',
				width:'110rpx',
				height:'110rpx'
			}">
				<image :src="pre_url+'/static/img/uploadvideo2.png'" :style="{width:'110rpx',height:'110rpx'}"/>
			</button>
		</view>
		<!-- #endif -->

		<view style="width:100%;height:140rpx;"></view>
		<view class="bottombar flex-row" :class="menuindex>-1?'tabbarbot':''" v-if="product.status==1">
			<view class="f1">
				<view class="item" @tap="goto" :data-url="'prolist?bid=' + product.bid" >
					<image class="img" :src="pre_url+'/static/img/shou.png'"/>
					<view class="t1">首页</view>
				</view>
				<view class="item" @tap="goto" :data-url="kfurl" v-if="kfurl!='contact::'">
					<image class="img" :src="pre_url+'/static/img/kefu.png'"/>
					<view class="t1">客服</view>
				</view>
				<button class="item" v-else open-type="contact" show-message-card="true">
					<image class="img" :src="pre_url+'/static/img/kefu.png'"/>
					<view class="t1">客服</view>
				</button>
				<view class="item" @tap="addfavorite">
					<image class="img" :src="pre_url+'/static/img/shoucang.png'"/>
					<view class="t1">{{isfavorite?'已收藏':'收藏'}}</view>
				</view>
			</view>
			<view class="op">
				<view class="tobuy flex-x-center flex-y-center" :style="{background:t('color1')}" v-if="isfuwu" @tap="buydialogChange" data-btntype="2">立即预约</view>
				<view v-else class="tobuy flex-x-center flex-y-center" @tap="tobuy" :style="{background:t('color1')}">立即预约</view>
			</view>
		</view>
		<yybuydialog v-if="buydialogShow" :proid="product.id" :btntype="btntype"  @currgg="currgg" @buydialogChange="buydialogChange" :menuindex="menuindex" @addcart="addcart" :isfuwu="isfuwu"  @tobuy="tobuy"></yybuydialog>
		<scrolltop :isshow="scrolltopshow"></scrolltop>

		
		<view v-if="sharetypevisible" class="popup__container">
			<view class="popup__overlay" @tap.stop="handleClickMask"></view>
			<view class="popup__modal" style="height:320rpx;min-height:320rpx">
				<!-- <view class="popup__title">
					<text class="popup__title-text">请选择分享方式</text>
					<image :src="pre_url+'/static/img/close.png'" class="popup__close" style="width:36rpx;height:36rpx" @tap.stop="hidePstimeDialog"/>
				</view> -->
				<view class="popup__content">
					<view class="sharetypecontent">
						<view class="f1" @tap="shareapp" v-if="getplatform() == 'app'">
							<image class="img" :src="pre_url+'/static/img/sharefriends.png'"/>
							<text class="t1">分享给好友</text>
						</view>
						<view class="f1" @tap="sharemp" v-else-if="getplatform() == 'mp'">
							<image class="img" :src="pre_url+'/static/img/sharefriends.png'"/>
							<text class="t1">分享给好友</text>
						</view>
					<!-- 	<view class="f1" @tap="sharemp" v-else-if="getplatform() == 'h5'">
							<image class="img" :src="pre_url+'/static/img/sharefriends.png'"/>
							<text class="t1">分享给好友</text>
						</view> -->
						<button class="f1" open-type="share" v-else-if="getplatform() != 'h5'">
							<image class="img" :src="pre_url+'/static/img/sharefriends.png'"/>
							<text class="t1">分享给好友</text>
						</button>
						<view class="f2" @tap="showPoster">
							<image class="img" :src="pre_url+'/static/img/sharepic.png'"/>
							<text class="t1">生成分享图片</text>
						</view>
					</view>
				</view>
			</view>
		</view>
		<view class="posterDialog" v-if="showposter">
			<view class="main">
				<view class="close" @tap="posterDialogClose"><image class="img" :src="pre_url+'/static/img/close.png'"/></view>
				<view class="content">
					<image class="img" :src="posterpic" mode="widthFix" @tap="previewImage" :data-url="posterpic"></image>
				</view>
			</view>
		</view>
		
		<view v-if="timeDialogShow" class="popup__container">
			<view class="popup__overlay" @tap.stop="hidetimeDialog"></view>
			<view class="popup__modal">
				<view class="popup__title">
					<text class="popup__title-text">请选择时间</text>
					<image :src="pre_url+'/static/img/close.png'" class="popup__close" style="width:36rpx;height:36rpx"
						@tap.stop="hidetimeDialog" />
				</view>
				<view class="order-tab">
					<view class="order-tab2">
						<block v-for="(item, index) in datelist" :key="index">
							<view  :class="'item ' + (curTopIndex == index ? 'on' : '')" @tap="switchTopTab" :data-index="index" :data-id="item.id" >
								<view class="datetext">{{item.weeks}}</view>
								<view class="datetext2">{{item.date}}</view>
								<view class="after"  :style="{background:t('color1')}"></view>
							</view>			
						</block>
					</view>
				</view>
				<view class="flex daydate">
					<block v-for="(item,index2) in timelist" :key="index2">
						<view :class="'date ' + ((timeindex==index2 && item.status==1) ? 'on' : '') + (item.status==0 ?'hui' : '') "  @tap="switchDateTab" :data-index2="index2" :data-status="item.status" :data-time="item.timeint"> {{item.time}}</view>						
					</block>
				</view>
				<view class="op">
					<button class="tobuy on" :style="{backgroundColor:t('color1')}" @tap="selectDate" >确 定</button>
				</view>
			</view>
		</view>
		
		
		<view v-if="timeDialogShow2" class="popup__container">
			<view class="popup__overlay" @tap.stop="hidetimeDialog"></view>
			<view class="popup__modal"  style="height: 1000rpx;">
				<view class="popup__title2">
					<image :src="pre_url+'/static/img/biao.png'" class="popup_close2" style="width:36rpx;height:36rpx"
						@tap.stop="hidetimeDialog" />
					<text class="popup__title-text">选择时间</text>

				</view>
				<view class="order-tab">
					<view class="order-tab3">
						<block v-for="(item, index) in datelist" :key="index">
							<view  :class="'item ' + (curTopIndex == index ? 'on' : '')" @tap.stop="switchTopTab" :data-index="index" :data-id="item.id" >
								<view class="datetext">{{item.weeks}}</view>
								<view class="datetext2">{{item.date}}</view>
								<view class="after"  :style="{background:t('color1')}"></view>
							</view>			
						</block>
					</view>
				</view>
				<view class="daydate2">
					<view class="datebox">
						<block v-for="(item,index2) in timelist" :key="index2">
							<view :class="'date ' + ((timeindex==index2 && item.status==1) ? 'on' : '') + (item.status==0 ?'hui' : '') "  @tap.stop="switchDateTab" :data-index2="index2" :data-status="item.status" :data-time="item.timeint"> 
								<text class="t1">{{item.time}}</text>
								<text class="t2" v-if="product.fwpeople==0" >剩余 {{item.stock}}</text>							
							</view>						
						</block>
					</view>
				</view>
				<view class="op">
					<button class="tobuy on" :style="{backgroundColor:t('color1')}" @tap="selectDate" >确 定</button>
				</view>
			</view>
		</view>
		
    <view v-if="timeDialogShow3" class="popup__container">
    	<view class="popup__overlay" @tap.stop="hidetimeDialog2"></view>
    	<view class="popup__modal">
    		<view class="popup__title">
    			<text class="popup__title-text">请选择时间</text>
    			<image :src="pre_url+'/static/img/close.png'" class="popup__close" style="width:36rpx;height:36rpx"
    				@tap.stop="hidetimeDialog2" />
    		</view>
    		<view class="order-tab">
    			<view class="order-tab2">
    				<block v-for="(item, index) in datetimes" :key="index">
    					<view  :class="'item ' + (curTopIndex == index ? 'on' : '')" @tap="switchTopTab2" :data-index="index" :data-id="item.id" >
    						<view class="datetext">{{item.weeks}}</view>
    						<view class="datetext2">{{item.date}}</view>
    						<view class="after"  :style="{background:t('color1')}"></view>
    					</view>			
    				</block>
    			</view>
    		</view>
        <block v-for="(item2,index2) in datetimes" :key="index3">
          <view v-if="curTopIndex == index2" class="flex daydate" style="justify-content: space-around;font-size: 26rpx;">
            <block v-for="(item3,index3) in item2.times" :key="index3">
              <view :class="'date ' + ((item3.issel && item3.status==1) ? 'on' : '') + (item3.status==0 ?'hui' : '') "  @tap="switchDateTab2" :data-sort="item3.sort" :data-index="index2" :data-index2="index3" :data-status="item3.status" :data-year="item2.year" :data-date="item2.date" :data-time="item3.time" :data-time2="item3.time2" :data-timeint="item3.timeint" style="min-width: 19%;width: auto;"> {{item3.timerange}}</view>						
            </block>
          </view>
        </block>
        <view class="selsortsnum">
          <view>已选数量(最少选择{{product.datetype1_modelselnum}}个连续时间段)</view>
          <view>{{product.timejg}}分钟 X {{sortsnum}}</view>
        </view>
    		<view class="op">
    			<button class="tobuy on" :style="{backgroundColor:t('color1')}" @tap="hidetimeDialog2">确定</button>
    		</view>
    	</view>
    </view>

		<view v-if="showfuwudialog" class="popup__container">
			<view class="popup__overlay" @tap.stop="hidefuwudetail"></view>
			<view class="popup__modal">
					<view class="popup__title">
						<text class="popup__title-text">服务</text>
						<image :src="pre_url+'/static/img/close.png'" class="popup__close" style="width:36rpx;height:36rpx" @tap.stop="hidefuwudetail"/>
					</view>
					<view class="popup__content">
						<view v-for="(item, index) in fuwulist" :key="index" class="service-item">
							<view class="fuwudialog-content">
								<view class="f1">{{item.name}}</view>
								<text class="f2">{{item.desc}}</text>
							</view>
						</view>
					</view>
			</view>
		</view>
		
		<view v-if="timeDialogShow5" class="popup__container">
			<view class="popup__overlay" @tap.stop="hidetimeDialog5"></view>
			<view class="popup__modal">
				<view class="popup__title">
					<text class="popup__title-text">请选择预约时间</text>
					<image :src="pre_url+'/static/img/close.png'" class="popup__close" style="width:36rpx;height:36rpx"
						@tap.stop="hidetimeDialog5" />
				</view>
				<view class="order-tab1-date">
					<view class="order-tab2-date">
						<view class="tab2-date-name">选择日期<view class="date-star">*</view></view>
						<picker
							mode="date" 
							:value="selectedDate || startDate" 
							:start="startDate" 
							:end="endDate" 
							@change="handleDateConfirm"
							@cancel="closeAllPickers"
						>
							<view class="tab2-sel" @tap="showDatePickerInfo">{{ selectedDate || '请选择' }} <image class="f2" :src="pre_url+'/static/img/arrowright.png'"/></view>
						</picker>
						
					</view>    
					<view class="order-tab2-date">
						<view class="tab2-date-name">选择时间<view class="date-star">*</view></view>
						<picker
							mode="time" 
							:value="selectedTime || startDayTime" 
							:start="startDayTime" 
							:end="endDayTime" 
							@change="handleTimeConfirm"
							@cancel="closeAllPickers"
						>
							<view class="tab2-sel" @tap="showTimePickerInfo">{{ selectedTime || '请选择' }} <image class="f2" :src="pre_url+'/static/img/arrowright.png'"/></view>
						</picker>
						
					</view>  
				</view>
				
				<view class="op" style="margin-top: 100rpx;">
					<button class="tobuy on" :style="{backgroundColor:t('color1')}" @tap="confirmSelection">确定</button>
				</view>
			</view>
		</view>
	</block>
	<loading v-if="loading"></loading>
	<dp-tabbar :opt="opt" @getmenuindex="getmenuindex"></dp-tabbar>
	<popmsg ref="popmsg"></popmsg>
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
			textset:{},
			pre_url:app.globalData.pre_url,
			isload:false,
			buydialogShow: false,
			btntype:1,
			isfavorite: false,
			current: 0,
			isplay: 0,
			showcuxiaodialog: false,
			showfuwudialog:false,
			business: "",
			product: [],
			cartnum: "",
			commentlist: "",
			commentcount: "",
			cuxiaolist: "",
			couponlist: "",
			fuwulist: [],
			pagecontent: "",
			shopset: {},
			sysset:{},
			title: "",
			bboglist: "",
			sharepic: "",
			sharetypevisible: false,
			showposter: false,
			posterpic: "",
			scrolltopshow: false,
			kfurl:'',
			ggname:'请选择服务',
      ggid:0,
			timeDialogShow: false,
			datelist:[],
			daydate:[],
			curTopIndex: 0,
			index:0,
			day: -1,
			days:'请选择服务时间',
			dates:'',
			num:0,
			timeindex:-1,
			startTime:0,
			selectDates:'',
			timelist:[],
			
			guanggaopic: "",
			guanggaourl: "",
			video_status:0,
			video_title:'',
			video_tag:[],
			isfuwu:false,
			timeDialogShow2:false,
      
      showselectpeople:false,
      worker:'',
      workerid:0,
      
      selmoretime:false,//是否需要多选
      timeDialogShow3:false,
      datetimes:[],
      yydate :'请选择服务时间',
      sorts  :[],
      sortsnum:0,
      yydates:[],
      set:[],
			
			showDatePicker: false,
			showTimePicker: false,
			selectedDate: '',
			selectedTime: '',
			startDate: '',
			endDate: ''    ,
			startDayTime: '',
			endDayTime: '',
			timeDialogShow5:false,
			swiperHeight: '',
		};
	},
  onLoad: function (opt) {
		this.opt = app.getopts(opt);
    this.workerid = this.opt.workerid || 0;
		this.getdata();
  },
  onShow:function(){
    var that = this;
    var pages = getCurrentPages(); //获取加载的页面
    var currentPage = pages[pages.length - 1]; //获取当前页面的对象
    if(currentPage && currentPage.$vm.workerid && currentPage.$vm.realname){
        console.log(currentPage.$vm.workerid)
        that.worker    = {'id': currentPage.$vm.workerid,'realname':currentPage.$vm.realname,'tel':currentPage.$vm.tel};
        that.workerid =  currentPage.$vm.workerid;
    }
  },
	onPullDownRefresh: function () {
		this.getdata();
	},
	onShareAppMessage:function(shareOption){
		//#ifdef MP-TOUTIAO
		console.log(shareOption);
			return {
				title: this.video_title,
				channel: "video",
				extra: {
					hashtag_list: this.video_tag,
				},
				success: () => {
					console.log("分享成功");
				},
				 fail: (res) => {
				    console.log(res);
				    // 可根据 res.errCode 处理失败case
				  },
			};
		//#endif
		return this._sharewx({title:this.product.name,pic:this.product.pic});
	},
	onShareTimeline:function(){
		var sharewxdata = this._sharewx({title:this.product.name,pic:this.product.pic});
		var query = (sharewxdata.path).split('?')[1]+'&seetype=circle';
		console.log(sharewxdata)
		console.log(query)
		return {
			title: sharewxdata.title,
			imageUrl: sharewxdata.imageUrl,
			query: query
		}
	},
	onUnload: function () {
		clearInterval(interval);
	},

	methods: {
		loadImg() {
			this.getCurrentSwiperHeight('.img');
		},
		// 动态获取内容高度
		getCurrentSwiperHeight(element) {
				// #ifndef MP-ALIPAY
				let query = uni.createSelectorQuery().in(this);
				query.selectAll(element).boundingClientRect();
				var imgList = this.product.pics;
		    query.exec((res) => {
		      // 切换到其他页面swiper的change事件仍会触发，这时获取的高度会是0，会导致回到使用swiper组件的页面不显示了
		      if (imgList.length && res[0][this.current].height) {
		        this.swiperHeight = res[0][this.current].height;
		      }
		    });	
				// #endif
				// #ifdef MP-ALIPAY
				var imgList = this.product.pics;
				my.createSelectorQuery().select(element).boundingClientRect().exec((ret) => {
					if (imgList.length && ret[this.current].height) {
					  this.swiperHeight = ret[this.current].height;
					}
					});
				// #endif
		},
		getdata:function(){
			var that = this;
			var id = this.opt.id || 0;
			that.loading = true;
			app.get('ApiYuyue/product', {id: id,workerid:that.workerid}, function (res) {
				
				if (res.status == 0) {
					app.alert(res.msg);
					return;
				}
				that.textset = app.globalData.textset;
				var product = res.product;
				var pagecontent = JSON.parse(product.detail);
				that.business = res.business;
				that.product = product;
				that.cartnum = res.cartnum;
				that.commentlist = res.commentlist;
				that.commentcount = res.commentcount;
				that.pagecontent = pagecontent;
				that.shopset = res.shopset;
				that.sysset = res.sysset;
				that.title = product.name;
				that.isfavorite = res.isfavorite;
				that.fuwulist = res.fuwulist2;
				that.sharepic = product.pics[0];
				that.isfuwu = res.isfuwu
				
				that.startDate = product.yybegintime
				that.endDate = product.yyendtime
				that.startDayTime = product.yybegintimedetail
				that.endDayTime = product.yyendtimedetail
				
				//that.couponlist = res.couponlist
				uni.setNavigationBarTitle({
					title: product.name
				});
				if(res.set){
					that.set = res.set;
					if(res.set.ad_status && res.set.ad_pic)
					{	
						that.guanggaopic = res.set.ad_pic;
						that.guanggaourl = res.set.ad_link;
					}
					that.video_status = res.set.video_status;
					that.video_title = res.set.video_title;
					that.video_tag = res.set.video_tag;
				}
				that.datelist = res.datelist;
				that.daydate = res.daydate;
				that.kfurl = '/pages/kefu/index?bid='+product.bid;
				if(app.globalData.initdata.kfurl != ''){
					that.kfurl = app.globalData.initdata.kfurl;
				}
				if(that.business && that.business.kfurl){
					that.kfurl = that.business.kfurl;
				}
        
        if(res.showselectpeople){
          that.showselectpeople = true;
        }
        if(res.worker){
          that.worker    = res.worker;
          that.workerid  = res.worker['id'];
        }
        if(res.ggarr){
          that.ggname = res.ggarr.ggname;
          that.ggid   = res.ggarr.ggid
          that.proid  = res.ggarr.proid
          that.num    = res.ggarr.num
        }
        if(res.selmoretime){
          that.selmoretime = true;
          if(res.datetimes){
            that.datetimes = res.datetimes;
          }
        }
				that.loading = false;
				that.loaded({title:product.name,pic:product.pic});
        if(!product.is_open || product.is_open != 1){
          var tipmsg = product.noopentip?product.noopentip:'停业中';
          app.alert(tipmsg,function(){
            app.goback();
          });return;
        }
			});
		},
		swiperChange: function (e) {
			var that = this;
			that.current = e.detail.current
			// 禁止错误滑动事件
			if(!e.detail.source) return that.current = 0;
			//动态设置swiper的高度，使用nextTick延时设置
			this.$nextTick(() => {
			  this.getCurrentSwiperHeight('.img');
			});
		},
		payvideo: function () {
			this.isplay = 1;
			uni.createVideoContext('video').play();
		},
		parsevideo: function () {
			this.isplay = 0;
			uni.createVideoContext('video').stop();
		},
		buydialogChange: function (e) {
			if(!this.buydialogShow){
				this.btntype = e.currentTarget.dataset.btntype;
			}
			this.buydialogShow = !this.buydialogShow;
		},
		currgg: function (e) {
			console.log(e);
			var that = this
			this.ggname = e.ggname;
			that.ggid = e.ggid
			that.proid = e.proid
			that.num = e.num
		},
		switchTopTab: function (e) {
		  var that = this;
		  var id = e.currentTarget.dataset.id;
		  var index = parseInt(e.currentTarget.dataset.index);
		  this.curTopIndex = index;
		  that.days = that.datelist[index].year+that.datelist[index].date
		  that.nowdate = that.datelist[index].nowdate
		   // if(!that.dates ){ that.dates = that.daydate[0] }
		  this.curIndex = -1;
		  this.curIndex2 = -1;
		  //检测服务时间是否可预约
		  that.loading = true;
		  app.get('ApiYuyue/isgetTime', { date: that.days,proid:this.opt.id, key:that.datelist[index].key, timeMode:that.datelist[index].timeMode}, function (res) {
			  that.loading = false;
			  that.timelist = res.data;
		  })
			
		},
		switchDateTab: function (e) {
		  var that = this;
		  var index2 = parseInt(e.currentTarget.dataset.index2);
		  var timeint = e.currentTarget.dataset.time
		  var status = e.currentTarget.dataset.status
		  if(status==0){
				app.error('此时间不可选择');return;	
		  }
      that.timeint   = timeint
      that.timeindex = index2
      that.starttime1= that.timelist[index2].time
      if(!that.days || that.days=='请选择服务时间'){ that.days = that.datelist[0].year + that.datelist[0].date }
      that.selectDates = that.starttime1;
      that.yydate = that.days+' '+that.selectDates;
		},
		selectDate:function(e){
			var that=this
			if(that.timeindex >= 0 && that.timelist[that.timeindex].status==0){
					that.starttime1='';
			}
			if(!that.starttime1){
				app.error('请选择服务时间');return;
			}
			if(that.product.showdatetype==1){
					that.timeDialogShow2 = false;
			}else{
					that.timeDialogShow = false;
			}
		},
		//收藏操作
		addfavorite: function () {
			var that = this;
			var proid = that.product.id;
			app.post('ApiYuyue/addfavorite', {proid: proid,type: 'yuyue'}, function (data) {
				if (data.status == 1) {
					that.isfavorite = !that.isfavorite;
				}
				app.success(data.msg);
			});
		},
		shareClick: function () {
			this.sharetypevisible = true;
		},
		handleClickMask: function () {
			this.sharetypevisible = false
		},
		showPoster: function () {
			var that = this;
			that.showposter = true;
			that.sharetypevisible = false;
			app.showLoading('生成海报中');
			app.post('ApiYuyue/getposter', {proid: that.product.id}, function (data) {
				app.showLoading(false);
				if (data.status == 0) {
					app.alert(data.msg);
				} else {
					that.posterpic = data.poster;
				}
			});
		},
		
		posterDialogClose: function () {
			this.showposter = false;
		},
		showfuwudetail: function () {
			this.showfuwudialog = true;
		},
		hidefuwudetail: function () {
			this.showfuwudialog = false
		},
		showcuxiaodetail: function () {
			this.showcuxiaodialog = true;
		},
		hidecuxiaodetail: function () {
			this.showcuxiaodialog = false
		},
		getcoupon:function(){
			this.showcuxiaodialog = false;
			this.getdata();
		},
		onPageScroll: function (e) {
			var that = this;
			var scrollY = e.scrollTop;     
			if (scrollY > 200) {
				that.scrolltopshow = true;
			}
			if(scrollY < 150) {
				that.scrolltopshow = false
			}
		},	
		
		//选择时间 
		chooseTime: function(e) {
			var that = this;
			console.log(that.product.showdatetype);
			if(that.product.showdatetype==1){
					that.timeDialogShow2 = true;
			}else{
					that.timeDialogShow = true;
			}
			that.timeIndex = -1;
			var curTopIndex = that.datelist[0];
			that.nowdate = that.datelist[that.curTopIndex].year+that.datelist[that.curTopIndex].date;
			that.loading = true;
			app.get('ApiYuyue/isgetTime', { date: that.nowdate,proid:this.opt.id,key:that.datelist[that.curTopIndex].key,timeMode:that.datelist[that.curTopIndex].timeMode,workerid:that.workerid}, function (res) {
			  that.loading = false;
			  that.timelist = res.data;
			})
			
		},
    hidetimeDialog: function() {
    	var that=this
    	if(that.product.showdatetype==1){
    			that.timeDialogShow2 = false;
    	}else{
    			that.timeDialogShow = false;
    	}
    },
		showfuwudetail: function () {
			this.showfuwudialog = true;
		},
		hidefuwudetail: function () {
			this.showfuwudialog = false
		},
		sharemp:function(){
			// #ifdef H5
			app.error('点击右上角发送给好友或分享到朋友圈');
			this.sharetypevisible = false
			// #endif
		},
		shareapp:function(){
			// #ifdef APP-PLUS
			var that = this;
			that.sharetypevisible = false;
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
						sharedata.title = that.product.name;
						//sharedata.summary = app.globalData.initdata.desc;
						sharedata.href = app.globalData.pre_url +'/h5/'+app.globalData.aid+'.html#/yuyue/yuyue/product?scene=id_'+that.product.id+'-pid_' + app.globalData.mid;
						sharedata.imageUrl = that.product.pic;
						var sharelist = app.globalData.initdata.sharelist;
						if(sharelist){
							for(var i=0;i<sharelist.length;i++){
								if(sharelist[i]['indexurl'] == '/yuyue/yuyue/product'){
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
			// #endif
		},
		showsubqrcode:function(){
			this.$refs.qrcodeDialog.open();
		},
		closesubqrcode:function(){
			this.$refs.qrcodeDialog.close();
		},
		tobuy: function (e) {
			var that = this;
			var ks = that.ks;
			var proid = that.product.id;
			var ggid = that.ggid;
			var num = that.num;
			var yydate = that.yydate;
      console.log(yydate)
			var prodata = proid + ',' + ggid + ',' + num;
			if(!ggid || ggid==undefined){
					this.buydialogShow = !this.buydialogShow;
					return;
			}
      if(that.showselectpeople && that.product.fwpeople==1&& !that.workerid){
        app.error('请选择服务人员');
        setTimeout(function() {
            that.gotopeople();
        }, 600);
        return;
      }
			// console.log(yydate)
			if(that.product.rqtype != 5){
				if(!yydate || yydate=='请选择服务时间' ){
				  //app.error('请选择服务时间');
				  if(!that.selmoretime){
				    that.chooseTime();
				  }else{
				    that.chooseTime2();
				  }
				  return;
				}
			}else{
				if(!that.selectedDate){
					// app.error('请选择服务日期');
					this.timeDialogShow5 = true
					return;
				}
				if(!that.selectedTime){
					// app.error('请选择服务时间');
					this.timeDialogShow5 = true
					return;
				}
				yydate  = that.selectedDate + ' ' + that.selectedTime;
				
			}
			
      if(that.selmoretime){
        if(that.sortsnum<that.product.datetype1_modelselnum){
          app.error('服务时间最少选择'+that.product.datetype1_modelselnum+'个连续时间段');
          return;
        }
      }
			//var str2 = yydate.replace('年', '/');
			//var str2 = str2.replace('月', '/');
			app.setCache('yydate',yydate);
			//var timestamp = Date.parse(str2);
      var yydates = that.yydates;
      if(yydates && yydates.length>0){
        yydates = JSON.stringify(yydates)
      }else{
        yydates = '';
      }
			app.goto('/yuyue/yuyue/buy?prodata=' + prodata+'&workerid=' + that.workerid+'&yydate=' + yydate+'&yydates=' + yydates);
		},
    gotopeople:function(e){
    		var that=this
    		var yydate = that.yydate;
    		// if(!yydate || yydate=='请选择服务时间 ' ){
    		// 	//app.error('请选择服务时间');
    		//  	that.chooseTime();
    		// 	return;
    		// }
    		app.goto('selectpeople?prodata='+that.product.id+'&gotype=2');
    },
    chooseTime2: function(e) {
    	var that = this;
    	that.timeDialogShow3 = true;
    	that.timeIndex  = -1;
    	var curTopIndex = that.datetimes[0];
    	that.nowdate    = that.datetimes[that.curTopIndex].year+that.datetimes[that.curTopIndex].date;
    },
    switchTopTab2: function (e) {
      var that = this;
      var id = e.currentTarget.dataset.id;
      var index = parseInt(e.currentTarget.dataset.index);
      that.curTopIndex = index;
      that.days        = that.datetimes[index].year+that.datetimes[index].date
      that.nowdate     = that.datetimes[index].nowdate
      that.curIndex    = -1;
      that.curIndex2   = -1;
    },
    switchDateTab2: function (e) {
      var that = this;
      var index2 = parseInt(e.currentTarget.dataset.index2);
      var sort   = parseInt(e.currentTarget.dataset.sort);
      var year    = e.currentTarget.dataset.year
      var date    = e.currentTarget.dataset.date
      var time    = e.currentTarget.dataset.time
      var time2   = e.currentTarget.dataset.time2
      var timeint = e.currentTarget.dataset.timeint

      var status = e.currentTarget.dataset.status
      if(status==0){
    		app.error('此时间不可选择');return;	
      }
    
      var sorts       = that.sorts;
      var yydates     = that.yydates;
      var curTopIndex = that.curTopIndex;
      var datetimes   = that.datetimes
      
      var pos = sorts.indexOf(sort);
      //存在则删除
      if(pos>=0){
        sorts.splice(pos, 1);
        that.datetimes[curTopIndex].times[index2].issel = false;
        yydates.splice(pos, 1);
      }else{
        var len = sorts.length;
        if(len>=1){
          sorts.sort();
          //查询选择的是否是相邻的数据;
          var min = sorts[0] - sort;
          var max = sort - sorts[len-1];
          if(min>1 || max>1){
            app.error('只能选择相邻的连续的时间段');return;  
          }
          that.datetimes[curTopIndex].times[index2].issel = true;
        }else{
          that.datetimes[curTopIndex].times[index2].issel = true;
        }
        sorts.push(sort);

        var yydate = {'sort':sort,'year':year,'date':date ,'time':time ,'time2':time2 ,'timeint':timeint};
        yydates.push(yydate);
      }
      that.sorts     = sorts;
      that.sortsnum  = sorts.length;
      that.yydates   = yydates;
      var ylen  = yydates.length;
      if(ylen>=1){
        var ssort = yydates[0].sort;
        var syear = yydates[0].year;
        var sdate = yydates[0].date;
        var stime = yydates[0].time;
        var stime2 = yydates[0].time2;
        if(ylen>1){
          var esort  = yydates[ylen-1].sort;
          var eyear  = yydates[ylen-1].year;
          var edate  = yydates[ylen-1].date;
          var etime  = yydates[ylen-1].time;
          var etime2 = yydates[ylen-1].time2;
          if(syear == eyear){
            if(sdate == edate){
              if(ssort<=esort){
                that.yydate = syear+sdate+' '+stime+'-'+etime2;
              }else{
                that.yydate = eyear+edate+' '+etime+'-'+stime2;
              }
            }else{
              if(ssort<=esort){
                that.yydate = syear+sdate+' '+stime+'-'+edate+' '+etime2;
              }else{
                that.yydate = eyear+edate+' '+etime+'-'+sdate+' '+stime2;
              }
            }
          }else{
            if(ssort<=esort){
              that.yydate = syear+sdate+' '+stime+'-'+eyear+edate+' '+etime2;
            }else{
              that.yydate = eyear+edate+' '+etime+'-'+syear+sdate+' '+stime2;
            }
          }
        }else{
          that.yydate = syear+sdate+' '+stime;
        }
      }else{
        that.yydate = '请选择服务时间'
      }
    },
    hidetimeDialog2: function() {
    	this.timeDialogShow3 = false;
    },
		chooseTime5(){
			this.timeDialogShow5 = true
		},
	 // 显示/隐藏弹窗
		hidetimeDialog5() {
			this.timeDialogShow5 = false
		},
		
		// 日期确认
		handleDateConfirm(e) {
			this.selectedDate = e.detail.value
			this.selectedDate = this.selectedDate.replace(/(\d{4})-(\d{2})-(\d{2})/, '$1年$2月$3');
			this.closeAllPickers()
		},
		
		// 时间确认
		handleTimeConfirm(e) {
			console.log('closeAllPickers called') // 调试
			this.selectedTime = e.detail.value
			this.closeAllPickers()
		},
		
		// 关闭所有选择器
		closeAllPickers() {
			console.log('closeAllPickers called') // 调试
			this.showDatePicker = false
			this.showTimePicker = false
		},
		showDatePickerInfo() {
			this.showDatePicker = true
			this.showTimePicker = false
		},
		showTimePickerInfo() {
			console.log('closeAllPickers called') // 调试
			this.showDatePicker = false
			this.showTimePicker = true
		},
		
		// 确定按钮
		confirmSelection() {
			if (!this.selectedDate || !this.selectedTime) {
				uni.showToast({
					title: '请选择完整的预约时间',
					icon: 'none'
				})
				return
			}
			// 这里处理确认逻辑
			this.hidetimeDialog5()
			// 可以在这里触发父组件的回调或提交数据
		}
		

	}
};
</script>
<style>
	.dp-cover{height: auto; position: relative;}
	.dp-cover-cover{position:fixed;z-index:99999;cursor:pointer;display:flex;align-items:center;justify-content:center;overflow:hidden;background-color: inherit;}
	
.follow_topbar {height:88rpx; width:100%;max-width:640px; background:rgba(0,0,0,0.8); position:fixed; top:0; z-index:13;}
.follow_topbar .headimg {height:64rpx; width:64rpx; margin:6px; float:left;}
.follow_topbar .headimg image {height:64rpx; width:64rpx;}
.follow_topbar .info {height:56rpx; padding:16rpx 0;}
.follow_topbar .info .i {height:28rpx; line-height:28rpx; color:#ccc; font-size:24rpx;}
.follow_topbar .info {height:80rpx; float:left;}
.follow_topbar .sub {height:48rpx; width:auto; background:#FC4343; padding:0 20rpx; margin:20rpx 16rpx 20rpx 0; float:right; font-size:24rpx; color:#fff; line-height:52rpx; border-radius:6rpx;}
.qrcodebox{background:#fff;padding:50rpx;position:relative;border-radius:20rpx}
.qrcodebox .img{width:400rpx;height:400rpx}
.qrcodebox .txt{color:#666;margin-top:20rpx;font-size:26rpx;text-align:center}
.qrcodebox .close{width:50rpx;height:50rpx;position:absolute;bottom:-100rpx;left:50%;margin-left:-25rpx;border:1px solid rgba(255,255,255,0.5);border-radius:50%;padding:8rpx}

.goback{ position: absolute; top:0 ;width:64rpx ; height: 64rpx;z-index: 10000; margin: 30rpx;}
.goback img{ width:64rpx ; height: 64rpx;}

.swiper-container{position:relative}
.swiper {width: 100%;height: 750rpx;overflow: hidden;}
.swiper-item-view{width: 100%;height: 750rpx;}
.swiper .img {width: 100%;height: 750rpx;overflow: hidden;}

.imageCount {width:100rpx;height:50rpx;background-color: rgba(0, 0, 0, 0.3);border-radius:40rpx;line-height:50rpx;color:#fff;text-align:center;font-size:26rpx;position:absolute;right:13px;bottom:80rpx;}

.provideo{background:rgba(255,255,255,0.7);width:160rpx;height:54rpx;padding:0 20rpx 0 4rpx;border-radius:27rpx;position:absolute;bottom:30rpx;left:50%;margin-left:-80rpx;display:flex;align-items:center;justify-content:space-between}
.provideo image{width:50rpx;height:50rpx;}
.provideo .txt{flex:1;text-align:center;padding-left:10rpx;font-size:24rpx;color:#333}

.videobox{width:100%;height:750rpx;text-align:center;background:#000}
.videobox .video{width:100%;height:650rpx;}
.videobox .parsevideo{margin:0 auto;margin-top:20rpx;height:40rpx;line-height:40rpx;color:#333;background:#ccc;width:140rpx;border-radius:25rpx;font-size:24rpx;}

.header {padding: 20rpx 3%;background: #fff; width: 94%; border-radius:10rpx; margin: auto; margin-bottom: 20rpx; margin-top: -60rpx; position: relative;}
.header .pricebox{ width: 100%;border:1px solid #fff; justify-content: space-between;}
.header .pricebox .price{display:flex;align-items:flex-end}
.header .pricebox .price .f1{font-size:36rpx;color:#51B539;font-weight:bold}
.header .pricebox .price .f2{font-size:26rpx;color:#C2C2C2;text-decoration:line-through;margin-left:30rpx;padding-bottom:5px}
.header .price_share{width:100%;height:100rpx;display:flex;align-items:center;justify-content:space-between}
.header .price_share .share{display:flex;flex-direction:column;align-items:center;justify-content:center}
.header .price_share .share .img{width:32rpx;height:32rpx;margin-bottom:2px}
.header .price_share .share .txt{color:#333333;font-size:20rpx}
.header .title {color:#000000;font-size:32rpx;line-height:42rpx;font-weight:bold;}
.header .sellpoint{font-size:28rpx;color: #666;padding-top:20rpx;}
.header .sales_stock{height:60rpx;line-height:60rpx;font-size:24rpx;color:#777777; }
.header .commission{display:inline-block;margin-top:20rpx;margin-bottom:10rpx;border-radius:10rpx;font-size:20rpx;height:44rpx;line-height:44rpx;padding:0 20rpx}

.choosebox{margin: auto;width: 94%; border-radius:10rpx; background: #fff;  }

.choose{ width: 100%;display:flex;align-items:center;justify-content: center; margin: auto; height: 88rpx;line-height: 88rpx;padding: 0 3%; color: #333; border-bottom:1px solid #eee }
.choose .f0{color:#555;font-weight:bold;height:32rpx;font-size:24rpx;padding-right:30rpx;display:flex;justify-content:center;align-items:center;min-width:150rpx ;}
.choose .f2{ width: 32rpx; height: 32rpx;}
.choose .xuanzefuwu-text{display: inline-block;white-space: nowrap;overflow: hidden;text-overflow: ellipsis;max-width: 385rpx;}

.choosedate{ display:flex;align-items:center;justify-content: center;   margin:auto; height: 88rpx;padding: 0 3%; color: #333; }
.choosedate .f0{color:#555;font-weight:bold;height:32rpx;font-size:24rpx;padding-right:30rpx;display:flex;justify-content:center;align-items:center;min-width:150rpx ;}
.choosedate .f2{ width: 32rpx; height: 32rpx;}

.cuxiaodiv{background:#fff;margin-top:20rpx;}

.fuwupoint{width:100%;font-size:24rpx;color:#333;height:88rpx;line-height:88rpx;padding:12rpx 0;display:flex;align-items:center}
.fuwupoint .f0{color:#555;font-weight:bold;height:32rpx;font-size:24rpx;padding-right:30rpx;display:flex;justify-content:center;align-items:center}
.fuwupoint .f1{margin-right:20rpx;flex:1;display:flex;flex-wrap:nowrap;overflow:hidden}
.fuwupoint .f1 .t{ padding:4rpx 20rpx 4rpx 0;color:#777;flex-shrink:0}
.fuwupoint .f1 .t:before{content: "";display: inline-block;vertical-align: middle;	margin-top: -4rpx;margin-right: 10rpx;	width: 24rpx;	height: 24rpx;	background: url('data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABgAAAAYBAMAAAASWSDLAAAABGdBTUEAALGPC/xhBQAAAAFzUkdCAK7OHOkAAAAwUExURUdwTOU5O+Q5POU5POQ4O+U4PN80P+M4O+Q4O+Q4POQ5POQ4OuQ4O+Q4O+I4PuQ5PJxkAycAAAAPdFJOUwAf+VSoeAvzws7ka7miLboUzckAAADJSURBVBjTY2BgYGCMWVR5VIABDBid/gPBFwjP/JOzQKKtfjGIzf3fEUSJ/N8AJO21Iao3fQbqqA+AcLi/CzCwfGGAAn8HBnlFMIttBoP4R4b4C2BOzk8G3q8M5w3AnPsLGZj/MKwHW8b6/QED4y8G/QQQx14ZSHwCcWYkMOtvAHOAyvqnPf8KcuMvkAGZP9eDjAQaEO/AwDb/D0gj0GiQpRnTQIYIfUR1DopDGexVIZygz8ieC4B6WyzRBOJtBkZ/pAABBZUWOKgAispF5e7ibycAAAAASUVORK5CYII=') no-repeat;background-size: 24rpx auto;}
.fuwupoint .f2{flex-shrink:0;display:flex;align-items:center;width:32rpx;height: 32rpx;}
.fuwupoint .f2 .img{width:32rpx;height:32rpx;}
.fuwudialog-content{font-size:24rpx}
.fuwudialog-content .f1{color:#333;height:80rpx;line-height:80rpx;font-weight:bold}
.fuwudialog-content .f1:before{content: "";display: inline-block;vertical-align: middle;	margin-top: -4rpx;margin-right: 10rpx;	width: 24rpx;	height: 24rpx;	background: url('data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABgAAAAYBAMAAAASWSDLAAAABGdBTUEAALGPC/xhBQAAAAFzUkdCAK7OHOkAAAAwUExURUdwTOU5O+Q5POU5POQ4O+U4PN80P+M4O+Q4O+Q4POQ5POQ4OuQ4O+Q4O+I4PuQ5PJxkAycAAAAPdFJOUwAf+VSoeAvzws7ka7miLboUzckAAADJSURBVBjTY2BgYGCMWVR5VIABDBid/gPBFwjP/JOzQKKtfjGIzf3fEUSJ/N8AJO21Iao3fQbqqA+AcLi/CzCwfGGAAn8HBnlFMIttBoP4R4b4C2BOzk8G3q8M5w3AnPsLGZj/MKwHW8b6/QED4y8G/QQQx14ZSHwCcWYkMOtvAHOAyvqnPf8KcuMvkAGZP9eDjAQaEO/AwDb/D0gj0GiQpRnTQIYIfUR1DopDGexVIZygz8ieC4B6WyzRBOJtBkZ/pAABBZUWOKgAispF5e7ibycAAAAASUVORK5CYII=') no-repeat;background-size: 24rpx auto;}
.fuwudialog-content .f2{color:#777}

.cuxiaopoint{width:100%;font-size:24rpx;color:#333;height:88rpx;line-height:88rpx;padding:12rpx 0;display:flex;align-items:center}
.cuxiaopoint .f0{color:#555;font-weight:bold;height:32rpx;font-size:24rpx;padding-right:20rpx;display:flex;justify-content:center;align-items:center}
.cuxiaopoint .f1{margin-right:20rpx;flex:1;display:flex;flex-wrap:nowrap;overflow:hidden}
.cuxiaopoint .f1 .t{margin-left:10rpx;border-radius:3px;font-size:24rpx;height:40rpx;line-height:40rpx;padding-right:10rpx;flex-shrink:0;overflow:hidden}
.cuxiaopoint .f1 .t0{display:inline-block;padding:0 5px;}
.cuxiaopoint .f1 .t1{padding:0 4px}
.cuxiaopoint .f2{flex-shrink:0;display:flex;align-items:center;width:32rpx;height: 32rpx;}
.cuxiaopoint .f2 .img{width:32rpx;height:32rpx;}
.cuxiaodiv .cuxiaopoint{border-bottom:1px solid #E6E6E6;}
.cuxiaodiv .cuxiaopoint:last-child{border-bottom:0}

.popup__container{position: fixed;bottom: 0;left: 0;right: 0;width:100%;height:auto;z-index:10;background:#fff}
.popup__overlay{position: fixed;bottom: 0;left: 0;right: 0;width:100%;height: 100%;z-index: 11;opacity:0.3;background:#000}
.popup__modal{width: 100%;position: absolute;bottom: 0;color: #3d4145;overflow-x: hidden;overflow-y: hidden;opacity:1;padding-bottom:20rpx;background: #fff;border-radius:20rpx 20rpx 0 0;z-index:12;min-height:600rpx;max-height:1600rpx;}
.popup__title{text-align: center;padding:30rpx;position: relative;position:relative}
.popup__title-text{font-size:32rpx}
.popup__close{position:absolute;top:34rpx;right:34rpx}
.popup__content{width:100%;max-height:880rpx;overflow-y:scroll;padding:20rpx 0;}
.service-item{display: flex;padding:0 40rpx 20rpx 40rpx;}
.service-item .prefix{padding-top: 2px;}
.service-item .suffix{padding-left: 10rpx;}
.service-item .suffix .type-name{font-size:28rpx; color: #49aa34;margin-bottom: 10rpx;}


.shop{display:flex;align-items:center;width: 100%; background: #fff;  margin-top: 20rpx; padding: 20rpx 3%;position: relative; min-height: 100rpx;}
.shop .p1{width:90rpx;height:90rpx;border-radius:6rpx;flex-shrink:0}
.shop .p2{padding-left:10rpx}
.shop .p2 .t1{width: 100%;height:40rpx;line-height:40rpx;overflow: hidden;color: #111;font-weight:bold;font-size:30rpx;}
.shop .p2 .t2{width: 100%;height:30rpx;line-height:30rpx;overflow: hidden;color: #999;font-size:24rpx;margin-top:8rpx}
.shop .p4{height:64rpx;line-height:64rpx;color:#FFFFFF;border-radius:32rpx;margin-left:20rpx;flex-shrink:0;padding:0 30rpx;font-size:24rpx;font-weight:bold}

.detail{min-height:200rpx; width: 94%; margin: auto; border-radius: 10rpx;}

.detail_title{width:100%;display:flex;align-items:center;justify-content:center;margin-top:40rpx;margin-bottom:30rpx}
.detail_title .t0{font-size:28rpx;font-weight:bold;color:#222222;margin:0 20rpx}
.detail_title .t1{width:12rpx;height:12rpx;background:rgba(253, 74, 70, 0.2);transform:rotate(45deg);margin:0 4rpx;margin-top:6rpx}
.detail_title .t2{width:18rpx;height:18rpx;background:rgba(253, 74, 70, 0.4);transform:rotate(45deg);margin:0 4rpx}

.commentbox{width:90%;background:#fff;padding:0 3%;border-radius:10rpx;margin: auto;margin-top:20rpx; }
.commentbox .title{height:90rpx;line-height:90rpx;border-bottom:1px solid #DDDDDD;display:flex}
.commentbox .title .f1{flex:1;color:#111111;font-weight:bold;font-size:30rpx}
.commentbox .title .f2{color:#333;font-weight:bold;font-size:28rpx;display:flex;align-items:center}
.commentbox .nocomment{height:100rpx;line-height:100rpx}

.comment{display:flex;flex-direction:column;min-height:200rpx;}
.comment .item{background-color:#fff;padding:10rpx 20rpx;display:flex;flex-direction:column;}
.comment .item .f1{display:flex;width:100%;align-items:center;padding:10rpx 0;}
.comment .item .f1 .t1{width:70rpx;height:70rpx;border-radius:50%;}
.comment .item .f1 .t2{padding-left:10rpx;color:#333;font-weight:bold;font-size:30rpx;}
.comment .item .f1 .t3{text-align:right;}
.comment .item .f1 .t3 .img{width:24rpx;height:24rpx;margin-left:10rpx}
.comment .item .score{ font-size: 24rpx;color:#f99716;}
.comment .item .score image{ width: 140rpx; height: 50rpx; vertical-align: middle;  margin-bottom:6rpx; margin-right: 6rpx;}
.comment .item .f2{display:flex;flex-direction:column;width:100%;padding:10rpx 0;}
.comment .item .f2 .t1{color:#333;font-size:28rpx;}
.comment .item .f2 .t2{display:flex;width:100%}
.comment .item .f2 .t2 image{width:100rpx;height:100rpx;margin:10rpx;}
.comment .item .f2 .t3{color:#aaa;font-size:24rpx;}
.comment .item .f3{margin:20rpx auto;padding:0 30rpx;height:60rpx;line-height:60rpx;border:1px solid #E6E6E6;border-radius:30rpx;color:#111111;font-weight:bold;font-size:26rpx}

.bottombar{ width: 100%; position: fixed;bottom: 0px; left: 0px; background: #fff;display:flex;height:100rpx;padding:0 30rpx 0 10rpx;align-items:center;}
.bottombar .f1{flex:1;display:flex;align-items:center;margin-right:30rpx}
.bottombar .f1 .item{display:flex;flex-direction:column;align-items:center;width:50%;position:relative}
.bottombar .f1 .item .img{ width:44rpx;height:44rpx}
.bottombar .f1 .item .t1{font-size:18rpx;color:#222222;height:30rpx;line-height:30rpx;margin-top:6rpx}
.bottombar .op{width:60%;border-radius:36rpx;overflow:hidden;display:flex;}
.bottombar .tocart{flex:1;height:72rpx; line-height: 72rpx;color: #fff; border-radius: 0px; border: none;font-size:28rpx;font-weight:bold}
.bottombar .tobuy{flex:1;height: 72rpx; line-height: 72rpx;color: #fff; border-radius: 0px; border: none;font-size:28rpx;font-weight:bold; background: #FD4A46; border-top-right-radius:20rpx;border-bottom-left-radius:20rpx;}
.bottombar .cartnum{position:absolute;right:4rpx;top:-4rpx;color:#fff;border-radius:50%;width:32rpx;height:32rpx;line-height:32rpx;text-align:center;font-size:22rpx;}

/*时间范围*/
.datetab{ display: flex; border:1px solid red; width: 200rpx; text-align: center;}

.popup__title2{ height:80rpx; display: flex;align-items: center; }
.popup__title2 .popup_close2{ margin:0 10rpx; }
.order-tab2{display:flex;width:auto;min-width:100%;overflow-x: scroll;}
.order-tab2 .item{width:auto;font-size:28rpx;font-weight:bold;text-align: center; color:#999999;overflow: hidden;flex-shrink:0;flex-grow: 1; display: flex; flex-direction: column; justify-content: center; align-items: center; width: 20%;}
.order-tab2 .item .datetext{ line-height: 60rpx; height:60rpx;}
.order-tab2 .item .datetext2{ line-height: 60rpx; height:60rpx;font-size: 22rpx;}
.order-tab2 .on{color:#222222;}
.order-tab2 .after{display:none;margin-left:-10rpx;bottom:5rpx;height:6rpx;border-radius:1.5px;width:70rpx}
.order-tab2 .on .after{display:block}
.daydate{ padding:20rpx; flex-wrap: wrap; overflow-y: scroll; height:400rpx;}
.daydate .date{width: 20%;text-align: center;line-height: 60rpx;height: 60rpx; margin-top: 30rpx;}
.daydate .on{ background:red; color:#fff;}
.daydate .hui{ border:1px solid #f0f0f0; background:#f0f0f0;border-radius: 5rpx;}
.tobuy{flex:1;height: 72rpx; line-height: 72rpx; color: #fff; border-radius: 0px; border: none;font-size:28rpx;font-weight:bold;width:90%;margin:20rpx 5%;border-radius:36rpx;}

.order-tab .order-tab3{	display:flex;width:auto;min-width:100%;overflow-x: scroll;}
.order-tab3 .item{width:auto;font-size:28rpx;font-weight:bold;text-align: center; color:#999999;overflow: hidden;flex-shrink:0;flex-grow: 1; display: flex; flex-direction: column; justify-content: center; align-items: center; width: 120rpx; height: 120rpx; border:1rpx solid #E9E9E9; margin-left: 20rpx; border-radius: 10rpx;}
.order-tab3 .item .datetext{ color: #4F9BF2;}
.order-tab3 .on{color:#222222; background:#EBF1DE; border:1rpx solid #49C54E}
.order-tab3 .on .after{display:block}

.daydate2{ padding:20rpx; overflow-y: scroll; height:660rpx; }
.daydate2 .datebox{ width:100%; display: flex; flex-wrap: wrap;}
.daydate2 .date{ width:30%;text-align: center; margin-top: 30rpx; margin-left: 10rpx; border-radius: 10rpx;border:1px solid #E9E9E9; display: flex; flex-direction: column; padding:20rpx 0;max-height: 120rpx; height: auto; } 
.daydate2 .date .t2{ color: #3D91F1;}
.daydate2 .on{ background:#EBF1DE;border: 0.5px solid #49C54E; }
.daydate2 .hui{ border:1px solid #E9E9E9; border-radius: 5rpx; color:#999}
.daydate2 .date.hui .t2{ color: #999;}
.tobuy{flex:1;height: 72rpx; line-height: 72rpx; color: #fff; border-radius: 0px; border: none;font-size:28rpx;font-weight:bold;width:90%;margin:20rpx 5%;border-radius:36rpx;}



.cuxiaopoint{width:100%;font-size:24rpx;color:#333;height:88rpx;line-height:88rpx;padding:12rpx 0;display:flex;align-items:center}
.cuxiaopoint .f0{color:#555;font-weight:bold;height:32rpx;font-size:24rpx;padding-right:20rpx;display:flex;justify-content:center;align-items:center}
.cuxiaopoint .f1{margin-right:20rpx;flex:1;display:flex;flex-wrap:nowrap;overflow:hidden}
.cuxiaopoint .f1 .t{margin-left:10rpx;border-radius:3px;font-size:24rpx;height:40rpx;line-height:40rpx;padding-right:10rpx;flex-shrink:0;overflow:hidden}
.cuxiaopoint .f1 .t0{display:inline-block;padding:0 5px;}
.cuxiaopoint .f1 .t1{padding:0 4px}
.cuxiaopoint .f2{flex-shrink:0;display:flex;align-items:center;width:32rpx;height: 32rpx;}
.cuxiaopoint .f2 .img{width:32rpx;height:32rpx;}
.cuxiaodiv .cuxiaopoint{border-bottom:1px solid #E6E6E6;}
.cuxiaodiv .cuxiaopoint:last-child{border-bottom:0}
.selsortsnum{display: flex;justify-content: space-between;padding: 20rpx;background:#f0f0f0;border-radius: 4rpx;width: 98%;margin: 0 auto;}

/* 外层容器（日期+时间整体布局） */
.order-tab1-date {
  width: 100%;
  padding: 30rpx; /* 左右留出间距 */
}
.order-tab2-date .f2 {
	width:32rpx;
	height:32rpx;
}
/* 每一行（日期/时间） */
.order-tab2-date {
  display: flex;
  justify-content: space-between; /* 左右两端对齐 */
  align-items: center;
  padding: 25rpx 0; /* 上下间距 */
  border-bottom: 1rpx solid #f5f5f5; /* 底部浅色分割线 */
}
/* 左侧文字（"选择日期"、"选择时间"） */
.tab2-date-name {
  display: flex;
  align-items: center;
  font-size: 30rpx;
  color: #333;
}
/* 红色星号（必填提示） */
.date-star {
  color: #ff0000; /* 红色 */
  margin-left: 5rpx;
  font-size: 30rpx;
}
/* 右侧箭头文字（"请选择>"） */
.tab2-sel {
  font-size: 28rpx;
  color: #333;
  display: flex;
  align-items: center;
}

</style>