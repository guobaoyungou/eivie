<template>
	<scroll-view :scroll-y="sysset.mode==3?(isshowmendianmodal?false:true):true" class="pageContainer" :style="{backgroundColor:pageinfo.bgcolor}" @scroll="pagescroll">
		<block v-if="!show_nearbyarea">
			<!-- ！！！！！其他内容请放在自定义导航后面！！！！ -->
			<!-- #ifdef MP-WEIXIN  -->
			<block v-if="platform=='wx' && (homeNavigationCustom>1)">
				<view class="navigation"
					:style="{'background':navigationBarBackgroundColor}">
					<!-- <view :style="{height:statusBarHeight+'px'}"></view> -->
					<view class='navcontent' :style="{color:navigationBarTextStyle,marginTop:navigationMenu.top+'px',width:(navigationMenu.left-5)+'px'}">
						<!-- 标题+搜索框 -->
						<view class="header-location-top" :style="{height:navigationMenu.height+'px',background:navigationBarBackgroundColor}">
							<block v-if="homeNavigationCustom==2">
								<view class="topinfo">
									<image class="topinfoicon" :src="sysset.logo" />
									<view class="topinfotxt" :style="{color:navigationBarTextStyle}">{{sysset.name}}</view>
								</view>
								<view class="topsearch" :style="{width:(screenWidth-210)+'px'}" @tap="goto"
									data-url="/pages/shop/search">
									<image :src="pre_url+'/static/img/search.png'" />
									<text style="font-size:24rpx;color:#999">搜索感兴趣的商品</text>
								</view>
							</block>
						
							<!-- 定位模式门店模式 显示定位或门店 -->
							<block v-if="(homeNavigationCustom==3 || homeNavigationCustom==5 || homeNavigationCustom==7) && show_location==1">
								<!-- 显示定位：城市|地标 Start -->
								<!-- 当前城市 -->
								<view v-if="sysset.loc_area_type==0 && curent_address" :class="homeNavigationCustom>3?'header-location-weixin-fixedwidth':'header-location-weixin'">
									<!-- <image class="header-icon" :src="pre_url+'/static/img/location/address-dark.png'"> -->
									<uni-data-picker class="header-address header-area-picker" :localdata="arealist" popup-title="地区" @change="areachange"  :placeholder="'地区'">
										<view>{{curent_address?curent_address:'请选择定位'}}</view>
									</uni-data-picker>
									<image class="header-more" :src="pre_url+'/static/img/location/down-'+navigationBarTextStyle+'.png'"></image>
								</view>
								<!-- 当前地址（商圈地址等） -->
								<view v-if="sysset.loc_area_type==1 && curent_address" :class="homeNavigationCustom>3?'header-location-weixin-fixedwidth':'header-location-weixin'">
									<!-- <image class="header-icon" :src="pre_url+'/static/img/location/address-dark.png'"> -->
									<view class="flex-y-center" @tap="showNearbyBox">
										<view class="header-address">{{curent_address?curent_address:'请选择定位'}}</view>
										<image class="header-more" :src="pre_url+'/static/img/location/down-'+navigationBarTextStyle+'.png'">
									</view>
								</view>
								<view class="header-location-title" v-if="homeNavigationCustom==5">{{sysset.name}}</view>
								<view class="header-location-search" :style="{height:navigationMenu.height+'px'}" v-if="homeNavigationCustom==7" @tap="goto"
									data-url="/pages/shop/search">
									<image :src="pre_url+'/static/img/search.png'" />
									<text style="font-size:24rpx;color:#999">搜索感兴趣的商品</text>
								</view>
							</block>
							<!-- 显示定位：城市|地标 End -->
							<!-- 显示门店 Start -->
							<block v-if="(homeNavigationCustom==4 || homeNavigationCustom==6 || homeNavigationCustom==8) && show_mendian==1">
								<view :class="homeNavigationCustom>4?'header-location-weixin-fixedwidth':'header-location-weixin'">
									<!-- <image class="header-icon" :src="pre_url+'/static/img/location/mendian.png'"> -->
									<view class="flex-y-center header-location-f1" @tap="showMendianModal" >
										<view class="header-address">{{locationCache.mendian_name?locationCache.mendian_name:'请选择门店'}}</view>
										<image class="header-more" :src="pre_url+'/static/img/location/down-'+navigationBarTextStyle+'.png'">
									</view>
								</view>
								<view class="header-location-title" v-if="homeNavigationCustom==6">{{sysset.name}}</view>
								<view class="header-location-search" :style="{height:navigationMenu.height+'px'}" v-if="homeNavigationCustom==8" @tap="goto"
									data-url="/pages/shop/search">
									<image :src="pre_url+'/static/img/search.png'" />
									<text style="font-size:24rpx;color:#999">搜索感兴趣的商品</text>
								</view>
							</block>
							<!-- 显示门店 Start -->
					
						</view>
					</view>
				</view>				
				<view style="width:100%;" :style="{height:(44+statusBarHeight)+'px','background-image': bannerNavigationBg == 1 ? 'url(' + bgBanner + ')' : ''}" :class="{ 'navigation-bg': bannerNavigationBg == 1 }"></view>
			</block>
			<!-- 公众号关注组件 -->
			<block v-if="sysset.official_account_status==1">
				<official-account></official-account>
			</block>
			<!-- #endif -->
			<!-- ！！！！！其他内容请放在自定义导航后面！！！！ -->
		
		<view class="mendianupbg" v-if="mendian_upgrade && show_mendian==1"></view>
		<view class="mendianup" v-if="mendian_upgrade && show_mendian==1">
			<view class="left flex flex-col">
				<view v-if="mendian_change" class="f1 flex flex-y-center flex-bt" @click="gotoMendianList" >
						<view class="mendian-name-text">{{locationCache.mendian_name?locationCache.mendian_name:'无'}}</view>
						<view class="mendian-switch-view flex flex-y-center">
							<view :style="{color:t('color1')}">切换</view>
							<text class="iconfont iconjiantou" :style="{color:t('color1'),fontSize:'26rpx',fontWeight:'bold'}"></text>
						</view>
				</view>
				<view v-else class="f1">{{locationCache.mendian_name?locationCache.mendian_name:'无'}}</view>
				<view class="f2" v-if="mendian_show_address" @tap="openLocation" :data-latitude="mendian.latitude" :data-longitude="mendian.longitude" :data-address="locationCache.mendian_address">
					<view class="mendian-address-view">{{locationCache.mendian_address}}</view>
			  </view>
				<view class="f2" v-if="mendian_show_address && mendian.tel" @tap.stop="callphone" :data-phone="mendian.tel">
					<view><text style="margin-right:10rpx">{{mendian.tel}}</text><image :src="pre_url+'/static/img/telwhite.png'"></image></view>
				</view>
			</view>
		</view>
		<view :style="'background:rgba('+t('color1rgb')+',0.8);height: 170rpx;'" v-if="mendian_upgrade && show_mendian==1"></view>
		
		<!-- 代理卡片 -->
			<block v-if="sysset.agent_card == 1 && sysset.agent_card_info">
				<view style="height: 10rpx;"></view>
				<view class="agent-card">
					<view class="flex-y-center row1">
						<image class="logo" :src="sysset.agent_card_info.logo" />
						<view class="text">
							<view class="flex">
								<view class="title limitText">{{sysset.agent_card_info.shopname}}</view>
								<view class="flex right" @tap="showMap" :data-name="sysset.agent_card_info.shopname" :data-address="sysset.agent_card_info.address" :data-longitude="sysset.agent_card_info.longitude" :data-latitude="sysset.agent_card_info.latitude"><image class="img" :src="pre_url+'/static/img/b_addr.png'"></image>导航到店{{agent_juli}}</view>
							</view>
							<view class="limitText grey-text">{{sysset.agent_card_info.address}}</view>
							<view class="grey-text flex-y-center">
								<image class="img" :src="pre_url+'/static/img/my.png'"></image>
								<view>{{sysset.agent_card_info.name}}</view>
								<image class="img" :src="pre_url+'/static/img/tel.png'" style="margin-left: 30rpx;"></image>
								<view @tap="goto" :data-url="'tel::'+sysset.agent_card_info.tel"
									style="position: relative;">{{sysset.agent_card_info.tel}}
									<view class="btn" @tap="goto" :data-url="'tel::'+sysset.agent_card_info.tel">拨打</view>
								</view>
							</view>
						</view>
					</view>
					<view class="flex-y-center flex-x-center agent-card-b" :style="{background:t('color2')}">
						<view @tap="goto" :data-url="'/pagesExt/agent/card'">
							<image class="img" :src="pre_url+'/static/img/shop.png'"></image>店铺信息
						</view>
						<view @tap="goto" :data-url="'/pages/commission/poster'">
							<image class="img img2" :src="pre_url+'/static/img/card.png'"></image>店铺海报
						</view>
					</view>
				</view>
			</block>
			<!-- 代理卡片 end-->

			<block v-if="sysset.mode == 1 && (isNull(sysset.loc_business_show_type) || sysset.loc_business_show_type < 2)">
				<view class="header" :style="{'background':navigationBarBackgroundColor}">
					<view class="header_title flex-y-center flex-bt">
						<view class="flex-y-center " @click="toBusiness" :data-url="'/pagesExt/business/clist2'">
							<text :style="'color:'+loc_business_title_color">{{sysset.name}}</text>
							<image :src="pre_url+'/static/img/arrowright.png'" class="header_detail" ></image>
						</view>
					</view>
					
					<view class="header_address flex" v-if="sysset.loc_business_show_address">
						<view class="flex1">{{sysset.address}}</view>
						<text style="margin-left: 20rpx;flex-shrink: 0;">距离 {{sysset.juli}} km</text>
					</view>
				</view>
				<view class="topbannerbg" :style="sysset.banner_show && business.pic?'background:url('+business.pic+') center no-repeat;background-size:cover;':''"></view>
			</block>

			<block v-if="sysset.showgzts">
				<view style="width:100%;height:88rpx"> </view>
				<view class="follow_topbar">
					<view class="headimg">
						<image :src="sysset.logo" />
					</view>
					<view class="info">
						<view class="i">欢迎进入 <text :style="{color:t('color1')}">{{sysset.name}}</text></view>
						<view class="i">关注公众号享更多专属服务</view>
					</view>
					<view class="sub" @tap="showsubqrcode" :style="{'background-color':t('color1')}">立即关注</view>
				</view>
				<uni-popup id="qrcodeDialog" ref="qrcodeDialog" type="dialog">
					<view class="qrcodebox">
						<image :src="sysset.qrcode" @tap="previewImage" :data-url="sysset.qrcode" class="img" />
						<view class="txt">长按识别二维码关注</view>
						<view class="close" @tap="closesubqrcode">
							<image :src="pre_url+'/static/img/close2.png'" style="width:100%;height:100%" />
						</view>
					</view>
				</uni-popup>
			</block>
			
			<dp :pagecontent="pagecontent" :menuindex="menuindex" :latitude="latitude" :longitude="longitude" @getdata="getdata" :navigationHieght='navigationHieght' @banner-change="changeBannerBg"></dp>

			<view :class="sysset.ddbb_position == 'bottom' ? 'bobaobox_bottom' : 'bobaobox'" v-if="oglist && oglist.length>0">
				<swiper style="position:relative;height:54rpx;width:450rpx;" autoplay="true" :interval="5000"
					vertical="true">
					<swiper-item v-for="(item, index) in oglist" :key="index" @tap="goto" :data-url="item.tourl" class="flex-y-center">
						<image :src="item.headimg"
							style="width:40rpx;height:40rpx;border:1px solid rgba(255,255,255,0.7);border-radius:50%;margin-right:4px">
						</image>
						<view style="width:400rpx;white-space:nowrap;overflow:hidden;text-overflow: ellipsis;font-size:22rpx">
							<text style="padding-right:2px">{{item.nickname}}</text>
							<text style="padding-right:4px">{{item.showtime}}</text>
							<text style="padding-right:2px" v-if="item.type=='collage' && item.buytype=='2'">发起拼团</text>
							<text v-else-if="item.type =='business'">入驻</text>
							<text v-else-if="item.type == 'maidan'"></text>
							<text v-else>购买了</text>
							<text>{{item.name}}</text>
						</view>
					</swiper-item>
				</swiper>
			</view>

      <view v-if="copyright!=''" class="copyright" @tap="goto" :data-url="copyright_link">
        <block v-if="!copyrighttype">{{copyright}}</block>
        <block v-else-if="copyrighttype==1">
          <parse :content="copyright"></parse>
        </block>
      </view>
      
      <view v-if="advertising" style="width: 100%;background-color: #fff;margin: 20rpx 0;padding: 20rpx;">
        <block v-if="advertising.type == 1">
          <view class="ad-box">
          	<view class="ad-item" @tap="goto" :data-url="advertising.pictourl"><image :src="advertising.pic" mode="widthFix"></view>
          </view>
        </block>
        <block v-else>
          <dp-wxad :params="advertising"></dp-wxad>
        </block>
      </view>
			<dp-tabbar :opt="opt" @getmenuindex="getmenuindex"></dp-tabbar>
    <block v-if="ggsx">
			<dp-guanggao :guanggaopic="guanggaopic" :guanggaourl="guanggaourl" :guanggaotype="guanggaotype" :param="guanggaoparam" ></dp-guanggao>
    </block>
		</block>
		<popmsg ref="popmsg" :navigationHieght='navigationHieght'></popmsg>
		<loading v-if="loading"></loading>
		
		<!-- 附近商圈地址 -->
		<!-- #ifdef MP-WEIXIN  -->
		<block v-if="show_location==1 && sysset.loc_area_type==1 && show_nearbyarea">
			<view :style="{height:(34+statusBarHeight)+'px'}"></view>
			<view class="header-nearby-box">
				<view class="header-nearby-body">
					<view class="header-nearby-search">
						<view class="header-nearby-close" @tap="closeNearbyBox"><image :src="pre_url+'/static/img/location/close-dark.png'"></image></view>
						<view class="header-nearby-input" >
							<input type="text" class="input" placeholder="商圈/大厦/住宅" placeholder-style="font-size:26rpx" :value="placekeyword" @input="placekeywordInput" @confirm="searchPlace"/>
							<button class="searchbtn" :style="{borderColor:t('color1'),color:'#FFF',backgroundColor:t('color1')}" @tap="searchPlace">搜索</button>
						</view>
					</view>
					<view class="suggestion-box" v-if="suggestionplacelist.length>0">
						<block v-for="(item,index) in suggestionplacelist" :key="index">
							<view class="suggestion-place" @tap="chooseSuggestionAddress" :data-index="index">
								<view class="flex-y-center">
									<image :src="pre_url+'/static/img/address3.png'"></image>
									<text class="s-title">{{item.title}}</text>
								</view>
								<view class="s-info flex-y-center">
									<text class="s-area">{{item.city}} {{item.district}} </text>
									<text class="s-address">{{item.address}}</text>
								</view>
							</view>
						</block>
					</view>
					<view class="header-nearby-content flex-bt">
						<view>已选：{{curent_address}}</view>
						<view class="flex-xy-center" @tap="refreshAddress">
							<image class="header-nearby-imgicon" :src="pre_url+'/static/img/location/location-dark.png'">
							<text class="header-nearby-tip">重新定位</text>
						</view>
					</view>
					<view class="header-nearby-content" style="margin-top: 20rpx;">
						<view class="header-nearby-title flex-y-center">
							<image class="header-nearby-imgicon" :src="pre_url+'/static/img/location/home-dark.png'"></image>
							<text>我的地址</text>
						</view>
						<block v-for="(item,index) in myaddresslist" :key="index">
							<view class="header-nearby-info" v-if="index>3?(isshowalladdress?1==1:1==2):1==1" @tap="chooseMyAddress" :data-index="index">
									<view class="">{{item.address}}</view>
									<view class="header-nearby-txt">{{item.name}} {{item.tel}}</view>
							</view>
						</block>
						<view class="header-nearby-all flex-y-center" @tap="showAllAddress">
							<block v-if="myaddresslist.length>0">
								<text>{{isshowalladdress?'收起全部地址':'展开更多地址'}} </text><image :src="pre_url+'/static/img/location/'+(isshowalladdress?'up-grey.png':'down-grey.png')"></image>
							</block>
							<text v-else>-暂无地址-</text>
						</view>
					</view>
					<!-- 附近地址 -->
					<view class="header-nearby-content" style="margin-top: 20rpx;">
						<view class="header-nearby-title flex-y-center">
							<image class="header-nearby-imgicon" :src="pre_url+'/static/img/location/address-dark.png'"></image>
							<text>附近地址</text>
						</view>
						<block v-for="(item,index) in nearbyplacelist" :key="index">
							<view class="header-nearby-info"  @tap="chooseNearbyAddress" :data-index="index">
								<view class="">{{item.title}}</view>
								<view class="header-nearby-txt">{{item.address}}</view>
							</view>
						</block>
					</view>
				</view>
				<view class="header-location-bottom flex-xy-center" :style="{color:t('color1')}" @tap="addMyAddress">
					<text class="location-add-address">+</text><text style="padding-top: 10rpx;">新增收货地址</text>
				</view>
			</view>
		</block>
		<!-- 附近商圈地址 -->
		<!-- 门店选择start -->
		<block v-if="show_mendian">
			<view class="popup__container popup_mendian" v-if="isshowmendianmodal" style="z-index: 999999;">
				<view class="popup__overlay" @tap.stop="hideMendianModal"></view>
				<view class="popup__modal">
					<view class="popup__title">
						<text class="popup__title-text">请选择门店</text>
						<image :src="pre_url+'/static/img/close.png'" class="popup__close" style="width:36rpx;height:36rpx" @tap.stop="hideMendianModal"/>
					</view>
					<view class="popup__content">
						<block v-for="(item,index) in mendianlist" :key="index">
							<view class="mendian-info" @tap="changeMendian" :data-index="index" :data-id="item.id" :style="{background:(item.id==mendian.id?'rgba('+t('color1rgb')+',0.1)':'')}">
								<view class="b1"><image :src="item.pic"></image></view>
								<view class="b2">
									<view class="t1">{{item.name}}</view>
									<view class="t2 flex-y-center">
										<view class="mendian-distance">{{item.distance}}</view>
										<block v-if="item.address || item.area">
											<view class="line" v-if="item.distance"> </view>
											<view class="mendian-address"> {{item.address?item.address:item.area}}</view>
										</block>
									</view>
								</view>
							</view>
						</block>
					</view>
				</view>
			</view>
		</block>
		<!-- 门店选择end -->
		<!-- #endif  -->
    
    <block v-if="show_indextip && !indextipstatus">
    <view  @tap="closeindextip" style="position: fixed;right: 0rpx;top: 0rpx;width: 100%;height: 100%;background-color: #000;opacity: 0.45;z-index: 998;"></view>
    <image @tap="closeindextip" :src="pre_url+'/static/img/indextip.png'" style="position: fixed;right: 20rpx;top: 0rpx;width: 360rpx;height: 424rpx;z-index: 999;"></image>
    </block>
		<wxxieyi></wxxieyi>
	</scroll-view>
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
				pre_url: app.globalData.pre_url,
				platform: app.globalData.platform,
				homeNavigationCustom: app.globalData.homeNavigationCustom,
				navigationBarBackgroundColor: app.globalData.navigationBarBackgroundColor,
				navigationBarTextStyle: app.globalData.navigationBarTextStyle,
				statusBarHeight: 20,
				navigationMenu:{},
				id: 0,
				pageinfo: [],
				pagecontent: [],
				sysset: {},
				title: "",
				oglist: [],
				guanggaopic: "",
				guanggaourl: "",
				guanggaotype: 1,
				guanggaoparam:{},
				copyright: '',
				copyright_link:'',
				latitude: '',
				longitude: '',
				area:'',

				screenWidth: 375,
				business: [],  
				xixie:false,
				xdata:'',
				display_buy:'',
				cartnum:0,
				cartprice:0,
				code:'',
				agent_juli:'',
				
				//定位模式
				mid:app.globalData.mid,
				showlevel:2,
				show_location:0,
				curent_address:'',//当前位置: 城市或者收货地址
				arealist:[],
				show_nearbyarea:false,
				ischangeaddress:false,
				nearbyplacelist:[],
				myaddresslist:[],
				isshowalladdress:false,
				placekeyword:'',
				suggestionplacelist:[],
				
				//门店模式 显示最近的一个门店
				show_mendian:0,
				mendianid:0,
				mendian:{},
				mendianlist:[],
				mendianindex:-1,
				isshowmendianmodal:false,
				needRefreshMyaddress:false,
				headericonlist:[],
				cacheExpireTime:10,//缓存过期时间10分钟
				locationCache:{},
        trid:0,
        show_indextip:false,
        indextipstatus:false,
				
				mendian_upgrade:false,
				mendian_change:true,
				mendian_show_address:false,
				navigationHieght:0,
        ggsx: false,//广告热加载
        
        loc_business_title_color:'#333333',//商户标题颜色
				user_needlocation:true, //app-用户对获取定位的选择
				bgBanner:'', //当前benner图片
				bannerPage:'',
				bannerNavigationBg:0,//导航条背景色跟随banner 开关
        
        advertising:'',//广告
        copyrighttype:0,//版权类型 0：默认类型 1：自定义类型
			}
		},
		onLoad: function(opt) {
			this.opt = app.getopts(opt);
			this.mendianid = this.opt.mdid || 0;
			var sysinfo = uni.getSystemInfoSync();
			this.statusBarHeight = sysinfo.statusBarHeight;
			this.wxNavigationBarMenu();
			this.screenWidth = sysinfo.screenWidth;
			var locationCache = app.getLocationCache();
			if(locationCache){
				if(locationCache.latitude){
					this.latitude = locationCache.latitude
					this.longitude = locationCache.longitude
				}
				if(locationCache.area){
					this.area = locationCache.area
					this.curent_address = locationCache.address
				}
				if(locationCache.mendian_id && !this.mendianid){
					this.mendianid = locationCache.mendian_id
				}
			}
			console.log(locationCache);
      if(this.opt && this.opt.trid){
        this.trid = this.opt.trid;
      }else{
        this.trid = app.globalData.trid
      }
			// this.getdata();

			// #ifdef APP-PLUS
			var that = this;
			uni.onNetworkStatusChange((res) => {
				console.log('-----onNetworkStatusChange---')
				console.log(res)
				if(res.isConnected == true){
					that.getdata();
				}
			});
			// #endif
			if(this.platform=='wx' && (this.homeNavigationCustom>1)){
				let that = this;
				that.$nextTick(() => {
					uni.createSelectorQuery().select('.navigation').boundingClientRect((rect) => {
						that.navigationHieght = rect.height
					}).exec()
				})
			}
		},
		onShow:function(opt){
			console.log('index onshow')
			this.mid = app.globalData.mid
			if(this.needRefreshMyaddress){
				this.getMyAddress()
				that.needRefreshMyaddress = false
			}
      var indextipstatus = app.globalData.indextipstatus
      if(!indextipstatus){
        this.indextipstatus = false;
      }else{
        this.indextipstatus = true;
      }
			this.getdata()
		},
		onPullDownRefresh: function(e) {
			if(!this.show_nearbyarea && !this.isshowmendianmodal){
				this.getdata();
			}
		},
		onPageScroll: function (e) {
			uni.$emit('onPageScroll',e);
		},
		methods: {
			wxNavigationBarMenu:function(){
				var homeNavigationCustom = this.homeNavigationCustom
				if(this.platform=='wx' && (homeNavigationCustom>1)){
					//胶囊菜单信息
					this.navigationMenu = wx.getMenuButtonBoundingClientRect()
				}
			},
			toBusiness(){
				var url = '/pagesExt/business/clist2';
				var backurl = encodeURIComponent('/pages/index/index');
				app.goto(url+'?isindex=1&backurl='+backurl);
			},
			getdata: function() {
				var that = this;
				var opt = this.opt
				var id = 0;
				if (opt.select_bid) {
					var select_bid = opt.select_bid;
					app.setCache('select_bid', select_bid);
				} else {
					var select_bid = app.getCache('select_bid');
				}

				if (opt && opt.id) {
					id = opt.id;
				}
				that.loading = true;
				that.checkAreaByShowlevel();
				var locationCache =  app.getLocationCache();
				var mendian_isinit = 0;
				if(locationCache){
					if(locationCache.latitude){
						this.latitude = locationCache.latitude
						this.longitude = locationCache.longitude
					}
					if(locationCache.area){
						this.area = locationCache.area
						this.curent_address = locationCache.address
					}
					if(locationCache.mendian_id && !this.mendianid){
						this.mendianid = locationCache.mendian_id
					}
					if(locationCache.mendian_isinit && !this.mendianid){
						mendian_isinit = locationCache.mendian_isinit
					}
				}
				app.get('ApiIndex/index', {
					id: id,
					latitude: that.latitude,
					longitude: that.longitude,
					area:that.area,
					select_bid: select_bid,
					pid: app.globalData.pid,
					mendian_id:that.mendianid,
					mendian_isinit:mendian_isinit,
					trid: that.trid,
					mode:that.opt.mode?that.opt.mode:''
				}, function(data) {
					that.loading = false;
					if (data.status == 2) {
						//付费查看
						app.goto('/pagesExt/pay/pay?fromPage=index&id=' + data.payorderid + '&pageid=' + that.id,
							'redirect');
						return;
					}
					if (data.status == 1) {
						var pagecontent = data.pagecontent;
						var title = data.pageinfo && data.pageinfo.title?data.pageinfo.title:'';
            that.title = title;
						
						if(data.oglist)
							that.oglist = data.oglist;

						that.guanggaopic = data.guanggaopic;
            if(data.pageinfo){
              that.ggsx = true;
            }
						that.guanggaourl = data.guanggaourl;
						that.guanggaotype = data.guanggaotype;
						that.guanggaoparam = data.guanggaoparam;
						that.pageinfo = data.pageinfo;
						that.pagecontent = data.pagecontent;
						that.copyright = data.copyright;
						that.copyright_link = data.copyright_link || '';
            that.copyrighttype = data.copyrighttype || 0;
						that.sysset = data.sysset;
						that.mendian_change = data.mendian_change;
						var mendian_disable = data.mendian_disable || 0;
						if(mendian_disable == 1){
							app.error('您当前绑定的'+that.t('门店')+'不可用，请尽快切换'+that.t('门店'));
						}
						uni.setNavigationBarTitle({
							title: title
						});
						if(that.sysset.mode==2){
							that.show_location = data.show_location;
						}
					
						if(that.sysset.mode==3){
							that.show_mendian = data.show_mendian;
							that.area = ''
							that.curent_address=''
							that.locationCache.area = ''
							that.locationCache.address = ''
							if(data.mendian){
								that.mendian = data.mendian
								that.mendianid = data.mendian.id
								that.locationCache.mendian_id = that.mendian.id
								that.locationCache.mendian_name = that.mendian.name
								that.locationCache.latitude = that.latitude
								that.locationCache.longitude = that.longitude
								if(that.latitude=='' || that.longitude==''){
									//第一次初始化的默认门店
									that.locationCache.mendian_isinit = 1;
								}else{
									that.locationCache.mendian_isinit = 0;
								}
								if(data.mendian_upgrade){
									that.locationCache.mendian_address = that.mendian.address
									that.locationCache.mendian_name = that.mendian.name
									that.locationCache.mendian_pic = that.mendian.pic
									that.locationCache.headimg = that.mendian.headimg
								}
								app.setLocationCacheData(that.locationCache,that.cacheExpireTime)
							}
						}else{
								app.setLocationCache('mendian_id',0)
								app.setLocationCache('mendian_name','')
						}
						//头部定位
						if(that.latitude && that.longitude){
							if(that.sysset.mode==2 && that.sysset.loc_area_type==0){
								//当前城市
								that.checkLocation()
								that.initCityAreaList()
							}else if(that.sysset.mode==2 && that.sysset.loc_area_type==1){
								//附近地址
								that.checkLocation()
							}
						}
            
            that.advertising = data.advertising;//广告
						
						that.loaded();
						if (that.latitude == '' && that.longitude == '' && data.needlocation && that.user_needlocation) {
							app.getLocation(function(res) {
								that.latitude = res.latitude;
								that.longitude = res.longitude;
								if(that.sysset.mode==2){
									//当前城市
									that.checkLocation()
								}else{
									that.getdata();
								}		
							},function(res){
								console.error(res);
								// #ifdef APP-PLUS
								that.user_needlocation = false;
								// #endif
							});
						}
            if(data.loc_business_title_color) that.loc_business_title_color = data.loc_business_title_color
						if (data.sysset.mode == 1 && data.business) {
							that.business = data.business;
							if (select_bid == '')
								app.setCache('select_bid', data.business.id);

							var juli = app.getDistance(that.longitude, that.latitude, that.business.longitude, that.business.latitude);		
							that.sysset.juli = 	juli?juli:0;
						}else{
							var juli = app.getDistance(that.longitude, that.latitude, data.sysset.longitude, data.sysset.latitude);
							that.sysset.juli = 	juli?juli:0;
						}
						
						if(that.sysset.agent_card){
							if(that.sysset.agent_card_info.juli != '')
								that.agent_juli = that.sysset.agent_card_info.juli + 'km';
							else {
								let juli = app.getDistance(that.longitude, that.latitude, that.sysset.agent_card_info.longitude, that.sysset.agent_card_info.latitude);
								that.agent_juli = juli?juli + 'km':'';
							}
						}

						if(data.show_indextip){
              that.show_indextip = data.show_indextip;
            }
            // #ifdef MP-WEIXIN
            if(pagecontent && pagecontent.length > 0) {
              //判断第一个是否轮播图
              if(pagecontent[0].temp === 'banner' && pagecontent[0].data.length > 0) {
                let bannerparams = pagecontent[0].params;
                if(pagecontent[0].params.navigation_bg == 1) {
                  that.bannerNavigationBg = 1;
                  that.bannerPage = pagecontent[0];
                  that.bannerNavigationBg = 1;
                  if(that.bgBanner == ''){
                    that.bgBanner = pagecontent[0].data[0].imgurl;
                  }
                  that.pageinfo.bgcolor = 'rgba(0,0,0,0)';
                  that.navigationBarBackgroundColor = 'rgba(0,0,0,0)';
                }
              }
            }
            // #endif
						that.mendian_upgrade = data.mendian_upgrade
						that.mendian_show_address = data.mendian_show_address
			 	} else {
						if (data.msg) {
							app.alert(data.msg, function() {
								if (data.url) app.goto(data.url);
							});
						} else if (data.url) {
							app.goto(data.url);
						} else {
							app.alert('您无查看权限');
						}
					}
				});
			},
			showsubqrcode: function() {
				this.$refs.qrcodeDialog.open();
			},
			closesubqrcode: function() {
				this.$refs.qrcodeDialog.close();
			},
			changePopupAddress:function(status){
					this.xdata.popup_address = status;
			},
			setMendianData:function(data){
					this.mendian_data = data;
			},
			//头部定位start 只处理微信小程序，其他端的组件实现
			checkLocation:function(){
				var that = this
				if(that.platform!='wx'){
					return;
				}
				var loc_area_type = that.sysset.loc_area_type;
				var loc_range_type = that.sysset.loc_range_type;
				var loc_range = that.sysset.loc_range;
				var locationCache = app.getLocationCache();
				//缓存为空 或 显示城市和当前地址切换 或 同城和自定义范围切换 或 显示距离发生变化
				if(!locationCache || !locationCache.address || (locationCache.loc_area_type!=loc_area_type || locationCache.loc_range_type!=loc_range_type || locationCache.loc_range!=loc_range)){
						app.getLocation(function(res) {
							that.latitude = res.latitude;
							that.longitude = res.longitude;
							//如果从当前地址切到当前城市，则重新定位用户位置
							app.post('ApiAddress/getAreaByLocation', {latitude:that.latitude,longitude:that.longitude}, function(res) {
								if(res.status==1){
									locationCache.loc_area_type = loc_area_type
									locationCache.loc_range_type = loc_range_type
									locationCache.loc_range = loc_range
									locationCache.latitude = that.latitude
									locationCache.longitude = that.longitude
									if(loc_area_type==0){
										if(that.showlevel==1){
											locationCache.address = res.province
											locationCache.area = res.province+','+res.city
										}else if(that.showlevel==2){
											locationCache.address = res.city
											locationCache.area = res.province+','+res.city
										}else{
											locationCache.address = res.city
											locationCache.area = res.province+','+res.city+','+res.district
										}
										that.area = locationCache.area
										that.curent_address = locationCache.address
										app.setLocationCacheData(locationCache,that.cacheExpireTime)
										that.getdata()
									}else if(loc_area_type==1){
										locationCache.address = res.landmark
										locationCache.area = res.province+','+res.city+','+res.district
										that.area = locationCache.area
										that.curent_address = locationCache.address
										app.setLocationCacheData(locationCache,that.cacheExpireTime)
										that.refreshNearbyPlace();
										that.getdata()
									}else{
										return;
									}
								}
							})
						})
				}
			},
			checkAreaByShowlevel:function(){
				var that  = this
				if(that.sysset.mode==2 && that.sysset.loc_range_type==0){
					var locationCache = app.getLocationCache();
					if(locationCache && locationCache.area){
						var area = '';
						var areaArr = locationCache.area.split(',');
						var showlevel = that.showlevel
						if(showlevel==1 && areaArr.length>0){
							area = areaArr[0]
						}else if(showlevel==2 && areaArr.length>1){
							area = areaArr[0] + ','+areaArr[1]
						}else if(showlevel==3 && areaArr.length>2){
							area = areaArr[0] + ','+areaArr[1] + ','+areaArr[2]
						}
						that.locationCache.area = area;
						app.setLocationCache('area',area,that.cacheExpireTime)
					}
				}
			},
			initCityAreaList:function(){
				if(this.platform!='wx'){
					return;
				}
				var that = this;
				//地区加载
				if(that.arealist.length==0){
					uni.request({
						url: app.globalData.pre_url+'/static/area.json',
						data: {},
						method: 'GET',
						header: { 'content-type': 'application/json' },
						success: function(res2) {
							if(that.showlevel<3){
								var newlist = [];
								var arealist = res2.data
								for(var i in arealist){
									var item1 = arealist[i]
									if(that.showlevel==2){
										var children = item1.children //市
										var newchildren = [];
										for(var j in children){
											var item2 = children[j]
											item2.children = []; //去掉三级-县的数据
											newchildren.push(item2)
										}
										item1.children = newchildren
									}else{
										item1.children = []; ////去掉二级-市的数据
									}
									newlist.push(item1)
								}
								that.arealist = newlist
							}else{
								that.arealist = res2.data
							}
						}
					});
				}
			},
			areachange:function(e){
				if(this.platform!='wx'){
					return;
				}
				var that = this
				const value = e.detail.value
				var area_name = [];
				var showarea = ''
				for(var i=0;i<that.showlevel;i++){
					area_name.push(value[i].text)
					showarea = value[i].text
				}
				that.area = area_name.join(',')
				that.curent_address = showarea
				//全局缓存
				var locationCache = app.getLocationCache()
				locationCache.area = that.area
				locationCache.address = showarea
				if(that.sysset.loc_area_type==0){
					//获取地址中心地标
					app.post('ApiAddress/addressToZuobiao', {
						address:area_name.join('')
					}, function(resp) {
						that.loading = false
						if(resp.status==1){
							that.latitude = resp.latitude
							that.longitude = resp.longitude
							locationCache.latitude = that.latitude
							locationCache.longitude = that.longitude
							app.setLocationCacheData(locationCache,that.cacheExpireTime)
							that.getdata();
						}else{
							app.error('地址解析错误');
						}
					})
				}
			},
			closeNearbyBox:function(){
				if(this.platform!='wx'){
					return;
				}
				this.show_nearbyarea = false
			},
			showNearbyBox:function(){
				if(this.platform!='wx'){
					return;
				}
				var that = this
				this.show_nearbyarea = true
				this.placekeyword = ''
				this.suggestionplacelist = []
				var nearbylist = app.getLocationCache('poilist');
				if(!nearbylist){
					nearbylist = [];
				}
				if(nearbylist && nearbylist.length>0){
					this.nearbyplacelist = nearbylist
				}
				//获取我的收货地址
				if(app.globalData.mid){
					that.loading = true
					that.getMyAddress()
				}
			},
			changeAddress:function(){
				if(this.platform!='wx'){
					return;
				}
				this.ischangeaddress = true
			},
			addMyAddress:function(e){
				if(this.platform!='wx'){
					return;
				}
				this.needRefreshMyaddress = true;
				app.goto("/pagesB/address/addressadd?type=1")
			},
			getMyAddress:function(){
				if(this.platform!='wx'){
					return;
				}
				var that = this;
				that.loading = true
				app.post('ApiAddress/address', {
					type:1
				}, function(resp) {
					that.loading = false
					if(resp.status==1){
						that.myaddresslist = resp.data
					}
				})
			},
			cancelChangeAddress:function(){
				if(this.platform!='wx'){
					return;
				}
				this.ischangeaddress = false
			},
			refreshAddress:function(e){
				if(this.platform!='wx'){
					return;
				}
				var that = this
				that.loading = true
				app.getLocation(function(res) {
					var latitude = res.latitude;
					var longitude = res.longitude;
					//请求当前地址[取商圈地址]
					app.post('ApiAddress/getAreaByLocation', {
						latitude: latitude,
						longitude: longitude,
						type:1
					}, function(resp) {
						that.loading = false
						if(resp.status==1){
							that.latitude = latitude
							that.longitude = longitude
							var data = resp.data
							that.curent_address = data.address_reference.landmark
							that.nearbyplacelist = data.pois
							var locationCache = app.getLocationCache();
							locationCache.area = data.address_component.province+','+data.address_component.city+','+data.address_component.district
							locationCache.address = that.curent_address
							locationCache.latitude = latitude
							locationCache.longitude = longitude
							locationCache.poilist = that.nearbyplacelist
							app.setLocationCacheData(locationCache)
							that.getdata()
							that.show_nearbyarea = false
						}
					})
				},function(res){
					console.error(res);
				});
			},
			showAllAddress:function(){
				if(this.platform!='wx'){
					return;
				}
				this.isshowalladdress = this.isshowalladdress?false:true
			},
			chooseMyAddress:function(e){
				if(this.platform!='wx'){
					return;
				}
				var that = this
				var index = e.currentTarget.dataset.index
				var info = that.myaddresslist[index]
				that.curent_address = info.address
				that.latitude = info.latitude
				that.longitude = info.longitude
				
				var locationCache = app.getLocationCache();
				locationCache.area = info.province+','+info.city+','+info.district
				locationCache.address = info.address
				locationCache.latitude = info.latitude
				locationCache.longitude = info.longitude
				app.setLocationCacheData(locationCache,that.cacheExpireTime)
				
				that.refreshNearbyPlace();
				that.getdata()
				that.show_nearbyarea = false
			},
			chooseNearbyAddress:function(e){
				if(this.platform!='wx'){
					return;
				}
				var that = this
				var index = e.currentTarget.dataset.index
				var info = that.nearbyplacelist[index]
				that.curent_address = info.title
				that.latitude = info.location.lat
				that.longitude = info.location.lng
				var locationCache = app.getLocationCache();
				locationCache.area = info.ad_info.province+','+info.ad_info.city+','+info.ad_info.district
				locationCache.address = info.title
				locationCache.latitude = info.location.lat
				locationCache.longitude = info.location.lng
				app.setLocationCacheData(locationCache,that.cacheExpireTime)
				that.refreshNearbyPlace();
				that.getdata()
				that.show_nearbyarea = false
			},
			chooseSuggestionAddress:function(e){
				if(this.platform!='wx'){
					return;
				}
				var that = this
				var index = e.currentTarget.dataset.index
				var info = that.suggestionplacelist[index]
				that.curent_address = info.title
				that.latitude = info.location.lat
				that.longitude = info.location.lng
				var locationCache = app.getLocationCache();
				locationCache.area = info.province+','+info.city+','+info.district
				locationCache.address = info.title
				locationCache.latitude = info.location.lat
				locationCache.longitude = info.location.lng
				app.setLocationCacheData(locationCache,that.cacheExpireTime)
				that.refreshNearbyPlace();
				that.getdata()
				that.show_nearbyarea = false
			},
			refreshNearbyPlace:function(latitude='',longitude=''){
				if(this.platform!='wx'){
					return;
				}
				var that = this
				if(latitude=='' && longitude==''){
					latitude = that.latitude
					longitude = that.longitude
				}
				if(latitude && longitude){
					that.loading = true;
					app.post('ApiAddress/getAreaByLocation', {
						latitude: latitude,
						longitude: longitude,
						type:1
					}, function(resp) {
						that.loading = false
						if(resp.status==1){
							var data = resp.data
							that.nearbyplacelist = data.pois
							app.setLocationCache('poilist',that.nearbyplacelist,that.cacheExpireTime);
						}
					})
				}
			},
			placekeywordInput:function(e){
				if(this.platform!='wx'){
					return;
				}
				this.placekeyword = e.detail.value
			},
			searchPlace:function(e){
				if(this.platform!='wx'){
					return;
				}
				var that = this
				if(that.placekeyword==''){
					that.suggestionplacelist = []
					return;
				}
				var locationCache = app.getLocationCache();
				
				var region = '';
				if(locationCache){
					if(locationCache.area){
						var areaArr = locationCache.area.split(',')
						if(areaArr.length==2){
							region = areaArr[1]
						}else if(areaArr.length==1){
							region = areaArr[0]
						}
					}
				}
				that.loading = true
				app.post('ApiAddress/suggestionPlace', {
					latitude: locationCache.latitude,
					longitude: locationCache.longitude,
					region:region,
					keyword:that.placekeyword
				}, function(resp) {
					that.loading = false
					if(resp.status==1){
						that.suggestionplacelist = resp.data
					}
				})
			},
			//头部定位end
			
			//门店模式start
			showMendianModal:function(){
				if(this.platform!='wx'){
					return;
				}
				var that = this;
				if(that.mendianlist.length>0){
					that.isshowmendianmodal = true
				}else{
					app.post('ApiMendian/mendianlist', {
						latitude: that.latitude,
						longitude: that.longitude,
					}, function(resp) {
						that.loading = false
						if(resp.status==1){
							that.mendianlist = resp.data
							that.isshowmendianmodal = true
						}else{
							app.error(resp.msg);
						}
					})
				}
			},
			hideMendianModal:function(){
				if(this.platform!='wx'){
					return;
				}
				this.isshowmendianmodal = false
			},
			changeMendian:function(e){
				if(this.platform!='wx'){
					return;
				}
				var that = this;
				var mendianid = e.currentTarget.dataset.id;
				var index = e.currentTarget.dataset.index;
				that.mendianid = mendianid
				that.mendian = that.mendianlist[index]
				that.locationCache.mendian_id = that.mendian.id
				that.locationCache.mendian_name = that.mendian.name
				app.setLocationCache('mendian_id',that.mendian.id,that.cacheExpireTime)
				app.setLocationCache('mendian_name',that.mendian.name,that.cacheExpireTime)
				app.setLocationCache('mendian_isinit',0)
				that.isshowmendianmodal = false
				that.getdata()
			},
			//门店模式end
			pagescroll:function(e){
				uni.$emit('onPageScroll',e.detail);
			},
      closeindextip:function(e){
        var that = this;
        that.indextipstatus = true;
        app.globalData.indextipstatus = true;
      },
			callphone:function(e) {
				var phone = e.currentTarget.dataset.phone;
				uni.makePhoneCall({
					phoneNumber: phone,
					fail: function () {
					}
				});
			},
			openLocation:function(e){
				var latitude = parseFloat(e.currentTarget.dataset.latitude);
				var longitude = parseFloat(e.currentTarget.dataset.longitude);
				var address = e.currentTarget.dataset.address;
				uni.openLocation({
				 latitude:latitude,
				 longitude:longitude,
				 name:address,
				 scale: 13
				})
			},
			gotoMendianList:function(e){
				this.mendianid = 0;
				app.goto('/pagesB/mendianup/list')
			},
			changeBannerBg(e) {
				this.bgBanner = e.currentImg
			}
			 
	}
}
</script>
<style>
	@import "./location.css";
	.pageContainer{
		width: 100%;
		/* #ifndef MP-ALIPAY */
		position: absolute;
		height: auto;
		/* #endif */
		
	}
	.topR {
		flex: 1;
		display: -webkit-box;
		-webkit-box-orient: vertical;
		-webkit-line-clamp: 1;
		overflow: hidden;
		color: #666;
	}

	.topR .btn-text {
		margin: 0 10rpx;
		color: #333;
	}

	.follow_topbar {
		height: 88rpx;
		width: 100%;
		max-width: 640px;
		background: rgba(0, 0, 0, 0.8);
		position: fixed;
		top: 0;
		z-index: 13;
	}

	.follow_topbar .headimg {
		height: 64rpx;
		width: 64rpx;
		margin: 6px;
		float: left;
	}

	.follow_topbar .headimg image {
		height: 64rpx;
		width: 64rpx;
	}

	.follow_topbar .info {
		height: 56rpx;
		padding: 16rpx 0;
	}

	.follow_topbar .info .i {
		height: 28rpx;
		line-height: 28rpx;
		color: #ccc;
		font-size: 24rpx;
	}

	.follow_topbar .info {
		height: 80rpx;
		float: left;
	}

	.follow_topbar .sub {
		height: 48rpx;
		width: auto;
		background: #FC4343;
		padding: 0 20rpx;
		margin: 20rpx 16rpx 20rpx 0;
		float: right;
		font-size: 24rpx;
		color: #fff;
		line-height: 52rpx;
		border-radius: 6rpx;
	}

	.qrcodebox {
		background: #fff;
		padding: 50rpx;
		position: relative;
		border-radius: 20rpx
	}

	.qrcodebox .img {
		width: 400rpx;
		height: 400rpx
	}

	.qrcodebox .txt {
		color: #666;
		margin-top: 20rpx;
		font-size: 26rpx;
		text-align: center
	}

	.qrcodebox .close {
		width: 50rpx;
		height: 50rpx;
		position: absolute;
		bottom: -100rpx;
		left: 50%;
		margin-left: -25rpx;
		border: 1px solid rgba(255, 255, 255, 0.6);
		border-radius: 50%;
		padding: 8rpx
	}

	.bobaobox {
		position: fixed;
		top: calc(var(--window-top) + 180rpx);
		left: 20rpx;
		z-index: 10;
		background: rgba(0, 0, 0, 0.6);
		border-radius: 30rpx;
		color: #fff;
		padding: 0 10rpx
	}
	.bobaobox_bottom {
		position: fixed;
		bottom: calc(env(safe-area-inset-bottom) + 150rpx);
		left: 0;
		right: 0;
		width:470rpx;
		margin:0 auto;
		z-index: 10;
		background: rgba(0, 0, 0, 0.6);
		border-radius: 30rpx;
		color: #fff;
		padding: 0 10rpx
	}
	@supports (bottom: env(safe-area-inset-bottom)){
		.bobaobox_bottom {
			position: fixed;
			bottom: calc(env(safe-area-inset-bottom) + 150rpx);
			left: 0;
			right: 0;
			width:470rpx;
			margin:0 auto;
			z-index: 10;
			background: rgba(0, 0, 0, 0.6);
			border-radius: 30rpx;
			color: #fff;
			padding: 0 10rpx
		}
	}

	.navigation {
		width: 100%;
		background: #fff;
		position: fixed;
		z-index: 99;
		padding-bottom:10px;
		overflow: hidden;
	}

	.navcontent {
		display: flex;
		align-items: center;
		padding-left: 10px
	}

	.navcontent .topinfo {
		display: flex;
		align-items: center;
	}

	.navcontent .topinfoicon {
		width: 17px;
		height: 17px;
		border-radius: 4px
	}

	.navcontent .topinfotxt {
		margin-left: 6px;
		font-size: 14px;
		font-weight: 600;
		max-width: 70px;
		text-overflow: ellipsis;
		white-space: nowrap;
		overflow: hidden;
	}

	.navcontent .topsearch {
		height: 32px;
		background: #f2f2f2;
		border-radius: 16px;
		color: #232323;
		display: flex;
		align-items: center;
		justify-content: center;
		font-size: 14px;
		flex: 1;
		margin-left: 12rpx;
	}

	.navcontent .topsearch image {
		width: 14px;
		height: 15px;
		margin-right: 6px
	}

	.limitText {
		flex: 1;
		display: -webkit-box;
		-webkit-box-orient: vertical;
		-webkit-line-clamp: 1;
		overflow: hidden;
		color: #666;
	}

	.agent-card {
		height: auto;
		position: relative;
		color: #333;
		background-color: #fff;
		margin: 0 20rpx 10rpx;
		font-size: 24rpx;
		border-radius: 0 10rpx 10rpx 10rpx;
		overflow: hidden;
		box-shadow: 0 0 8rpx 0px rgb(0 0 0 / 30%);
	}

	.agent-card .row1 {
		padding: 20rpx 10rpx 20rpx 20rpx;
	}

	.agent-card .logo {
		width: 120rpx;
		height: 120rpx;
		border-radius: 50%;
	}

	.agent-card .text {
		flex: 1;
		margin-left: 20rpx;
		color: #666;
		line-height: 180%;
	}
	.agent-card .right {
		align-items: center;
		padding-right: 10rpx;
	}

	.agent-card .title {
		color: #333;
		font-weight: bold;
		font-size: 32rpx;
	}

	.agent-card .btn {
		position: absolute;
		right: -100rpx;
		padding: 0 14rpx;
		top: 0;
		border: 1px solid #B6C26E;
		border-radius: 10rpx;
		color: #B6C26E;
	}

	.agent-card .img {
		margin-right: 6rpx;
		width: 30rpx;
		height: 30rpx
	}

	.agent-card .img2 {
		width: 32rpx;
		height: 32rpx
	}

	.grey-text {
		color: #999;
		font-weight: normal;
	}

	.agent-card-b view {
		line-height: 72rpx;
		font-size: 28rpx;
		color: #444;
		width: 50%;
		display: flex;
		align-items: center;
		justify-content: center;
		position: relative;
	}

	.agent-card-b view:first-child::after {
		content: '';
		width: 1px;
		height: 28rpx;
		border-right: 1px solid #444;
		position: absolute;
		right: 0;
	}
	
	/* 多商户切换 */
	.header{
		position: relative;
		padding: 30rpx;
	}
	.header_title{
		color: #333;
		font-weight: 700;
		font-size: 35rpx;
	}
	.header_detail{
		height: 25rpx;
		width: 25rpx;
		margin-left: 15rpx;
		margin-top: 5rpx;
	}
	
	.header_address{
		font-size: 24rpx;
		color: #999;
		margin-top: 20rpx;
	}
	.mendianupbg{position: absolute;height: 300rpx; width: 100%;top:0;color:#FFF}
	.mendianup{ display: flex;padding:20rpx;align-items: center;position: absolute; height:170rpx;color: #fff; width: 100%;}	
	.mendianup .left{ width: 100%;height:100%;justify-content: space-around;}
	.mendianup .left .f1{width: 100%;}
	.mendianup .left .mendian-name-text{font-size: 36rpx;line-height:50rpx;width: auto;max-width: 78%;white-space: nowrap;overflow: hidden;text-overflow: ellipsis;}
	.mendianup .left .mendian-switch-view{font-size: 24rpx;margin-left: 30rpx;background: #fafafa;border-radius: 30rpx;padding: 6rpx 18rpx;font-weight: bold;}
	.mendianup .left .f2{display: flex;align-items: center;}
	.mendianup .left .f2 .mendian-address-view{width: 100%;white-space: nowrap;overflow: hidden;text-overflow: ellipsis;}
	.mendianup .left .f2 image{width:24rpx; height:24rpx;}
	
	/deep/ .header-location-weixin .header-address .uni-data-tree-dialog{padding-bottom: 180rpx !important;}
  .navigation-bg {background-size: 100% 200%;filter: blur(10px);z-index: -1;background-position: top;background-repeat: no-repeat;}

  /* 广告位 */
  .ad-box{width: 100%;border-radius: 10rpx;}
  .ad-item{width: 100%;display: flex;justify-content: center;margin-bottom: 20rpx;border-radius: 10rpx;}
  .ad-item image{border-radius: 12rpx;width: 100%;}
  .ad-item:last-child{margin-bottom: 0;}
</style>
