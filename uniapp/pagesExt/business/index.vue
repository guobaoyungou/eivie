<template>
<scroll-view :scroll-y="isshowmendianmodal?false:true" class="pageContainer">
		<view v-if="isload" :style="{backgroundColor:(isdiy?pageinfo.bgcolor:'')}">
			<!-- #ifndef MP-WEIXIN  -->
			<!-- 显示门店 Start -->
			<block v-if="show_mendian==1 && isdiy==1">
				<view class="header-mendian-box">
					<view class="header-mendian">
						<view class="flex-y-center" @tap="showMendianModal">
							<view class="header-address">{{mendian.name?mendian.name:'全部门店'}}</view>
							<image class="header-more" :src="pre_url+'/static/img/location/right-dark.png'">
						</view>
					</view>
					<view class="flex-bt header-mendian-address" v-if="mendian.address">
						<view class="f1 flex-y-center">{{mendian.address}}</view>
						<view class="f2" v-if="mendian.distance">距离您{{mendian.distance}}km</view>
					</view>
				</view>
			</block>
			<!-- 显示门店 Start -->
			<!-- #endif -->
			<!-- #ifdef MP-WEIXIN  -->
			<block v-if="platform=='wx' && (homeNavigationCustom==4 || homeNavigationCustom==6 || homeNavigationCustom==8)">
				<view class="navigation" :style="{'background':navigationBarBackgroundColor}">
					<!-- <view :style="{height:statusBarHeight+'px'}"></view> -->
					<view class='navcontent' :style="{color:navigationBarTextStyle,marginTop:navigationMenu.top+'px',width:(navigationMenu.left-5)+'px'}">
						<view class="header-location-top" :style="{height:navigationMenu.height+'px'}">
							<!-- 显示门店 Start -->
							<block v-if="(homeNavigationCustom==4 || homeNavigationCustom==6 || homeNavigationCustom==8) && show_mendian==1">
								<view :class="homeNavigationCustom>4?'header-location-weixin-fixedwidth':'header-location-weixin'">
									<!-- <image class="header-icon" :src="pre_url+'/static/img/location/mendian.png'"> -->
									<view class="flex-y-center header-location-f1" @tap="showMendianModal">
										<view class="header-address">{{mendian.id?mendian.name:'全部门店'}}</view>
										<image class="header-more" :src="pre_url+'/static/img/location/down-'+navigationBarTextStyle+'.png'">
									</view>
								</view>
								<view class="header-location-title" v-if="homeNavigationCustom==6">{{sysset.sysname}}</view>
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
				<view style="width:100%;" :style="{height:(44+statusBarHeight)+'px'}"></view>
			</block>
			<!-- #endif -->
			<block v-if="isdiy">
				<view :style="'display:flex;min-height: 100vh;flex-direction: column;background-color:' + pageinfo.bgcolor">
					<view class="container">
						<dp :pagecontent="pagecontent" :menuindex="menuindex" :latitude="latitude" :longitude="longitude" @getdata="getdata"></dp>
					</view>
				</view>
				<dp-guanggao :guanggaopic="guanggaopic" :guanggaourl="guanggaourl" :guanggaotype="guanggaotype" :param="guanggaoparam"></dp-guanggao>
			</block>
			<block v-else>
				<view class="container nodiydata" v-if="isload">
					<swiper v-if="pics.length>0" class="swiper" :indicator-dots="pics[1]?true:false" :autoplay="true" :interval="5000" indicator-color="#dcdcdc" indicator-active-color="#fff">
						<block v-for="(item, index) in pics" :key="index">
							<swiper-item class="swiper-item">
								<image :src="item" mode="widthFix" class="image"/>
							</swiper-item>
						</block>
					</swiper>
          <view v-if="showShare" @tap="goto" :data-url="'/pagesA/business/poster?bid='+bid" style="position: absolute;top:30rpx;right: 30rpx;z-index: 99;">
            <image :src="pre_url+'/static/img/share.png'" style="width: 40rpx;height: 40rpx;"/>
          </view>
					<view class="topcontent">
						<view class="logo">
							<image class="img" :src="business.logo"/>
						</view>
						<view class="ratio-list flex">
							<view class="ratio-label flex-y-center" v-if="!isNull(business.newscore_ratio) && business.newscore_ratio > 0"  :style="{color:t('color1'),borderColor:t('color1'), width: '220rpx'}">
								<view class="label" :style="{backgroundColor:t('color1'), width: '120rpx'}">{{t('新积分')}}</view>
								<view class="t1">{{business.newscore_ratio}}%</view>
							</view>
						</view>
						<view class="title">{{business.name}}</view>
						<view class="desc">
							<view class="f1">
								<image class="img" v-for="(item2,index2) in [0,1,2,3,4]" :key="index2"  :src="pre_url+'/static/img/star' + (business.comment_score>item2?'2native':'') + '.png'"/>
								<text class="txt">{{business.comment_score}}</text>
							</view>
							<view class="f2">销量 {{business.sales}}</view>
							<view class="f2" v-if="business.turnover_show==1">营业额 {{business.turnover}}</view>
						</view>
						<view v-if="bset && bset.show_link" class="tel" :style="{background:'linear-gradient(90deg,'+t('color1')+' 0%, rgba('+t('color1rgb')+',0.8) 100%)'}">
                <view @tap="phone" :data-phone="business.tel" class="tel_online"><image class="img" :src="pre_url+'/static/img/telwhite.png'"/>
                    {{bset && bset.show_linktext?bset.show_linktext:'联系商家'}}
                </view>
            </view>
						<view v-if="business.address" class="address" @tap="openLocation" :data-latitude="business.latitude" :data-longitude="business.longitude" :data-company="business.name" :data-address="business.address">
							<image class="f1" :src="pre_url+'/static/img/shop_addr.png'"/>
							<view class="f2">{{business.address}}</view>  
							<image class="f3" :src="pre_url+'/static/img/arrowright.png'"/>
						</view>
					</view>
					<!-- #ifndef MP-WEIXIN  -->
					<!-- 门店S -->
					<view v-if="show_mendian" class="mendian-box" @tap="showMendianModal">
						<view class="flex-y-center f1">
							<image class="icon" :src="pre_url+'/static/img/location/mendian.png'">
							<view class="name">
								<view v-if="mendian.id" class="flex-bt">
									<view>{{mendian.name}}</view>
									<view v-if="mendian.distance">距离您{{mendian.distance}}km</view>
								</view>
								<view v-else>
								共<text class="num">{{mendianlist.length}}</text>家门店
								</view>
							</view>
						</view>
						<view class="f2" >
							<image class="exchange" v-if="mendian.id" :src="pre_url+'/static/img/location/change.png'"></image>
							<image class="more" v-else :src="pre_url+'/static/img/arrowright.png'"></image>
						</view>
					</view>
					<!-- 门店E -->
					<!-- #endif -->
					<view class="contentbox">
						<view class="shop_tab">
							<view v-if="showfw" :class="'cptab_text ' + (st==-1?'cptab_current':'')" @tap="changetab" data-st="-1">本店服务<view class="after" :style="{background:t('color1')}"></view></view>
							<view v-if="bset && bset.show_product" :class="'cptab_text ' + (st==0?'cptab_current':'')" @tap="changetab" data-st="0">{{bset && bset.show_producttext?bset.show_producttext:'本店商品'}}<view class="after" :style="{background:t('color1')}"></view></view>
							<view v-if="bset && bset.show_comment" :class="'cptab_text ' + (st==1?'cptab_current':'')" @tap="changetab" data-st="1">{{bset && bset.show_commenttext?bset.show_commenttext:'店铺评价'}}({{countcomment}})<view class="after" :style="{background:t('color1')}"></view></view>
							<view v-if="bset && bset.show_detail" :class="'cptab_text ' + (st==2?'cptab_current':'')" @tap="changetab" data-st="2">{{bset && bset.show_detailtext?bset.show_detailtext:'商家详情'}}<view class="after" :style="{background:t('color1')}"></view></view>
							<view v-if="bset && bset.show_mianndan" :class="'cptab_text ' + (st==3?'cptab_current':'')" @tap="changetab" data-st="3">免单项目<view class="after" :style="{background:t('color1')}"></view></view>												
						</view>

						<view class="cp_detail" v-if="st==-1" style="padding-top:20rpx">
							<view class="classify-ul" v-if="yuyue_clist.length>0">
								<view class="flex" style="width:100%;overflow-y:hidden;overflow-x:scroll;">
								 <view class="classify-li" :style="yuyue_cid==0?'color:'+t('color1')+';background:rgba('+t('color1rgb')+',0.2)':''" @tap="changeyuyueCTab" :data-id="0">全部</view>
								 <block v-for="(item, idx2) in yuyue_clist" :key="idx2">
								 <view class="classify-li" :style="yuyue_cid==item.id?'color:'+t('color1')+';background:rgba('+t('color1rgb')+',0.2)':''" @tap="changeyuyueCTab" :data-id="item.id">{{item.name}}</view>
								 </block>
								</view>
							</view>

							<dp-yuyue-itemlist :data="datalist" :menuindex="menuindex"></dp-yuyue-itemlist>
							
							<nomore v-if="nomore"></nomore>
							<nodata v-if="nodata"></nodata>
						</view>

						<view class="cp_detail" v-if="st==0" style="padding-top:20rpx">
							<dp-product-itemlist :data="datalist" :menuindex="menuindex"></dp-product-itemlist>
							
							<nomore v-if="nomore"></nomore>
							<nodata v-if="nodata"></nodata>
						</view>

						<view class="cp_detail" v-if="st==1">
							<view class="comment">
								<block v-if="datalist.length>0">
									<view v-for="(item, index) in datalist" :key="index" class="item">
										<view class="f1">
											<image class="t1" :src="item.headimg"/>
											<view class="t2">{{item.nickname}}</view>
											<view class="flex1"></view>
											<view class="t3"><image class="img" v-for="(item2,index2) in [0,1,2,3,4]" :key="index2"  :src="pre_url+'/static/img/star' + (item.score>item2?'2native':'') + '.png'"/></view>
										</view>
										<view style="color:#777;font-size:22rpx;">{{item.createtime}}</view>
										<view class="f2">
											<text class="t1">{{item.content}}</text>
											<view class="t2">
												<block v-if="item.content_pic!=''">
													<block v-for="(itemp, index) in item.content_pic" :key="index">
														<view @tap="previewImage" :data-url="itemp" :data-urls="item.content_pic">
															<image :src="itemp" mode="widthFix"/>
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
									<nodata v-show="nodata"></nodata>
								</block>
							</view>
						</view>
						<view class="cp_detail" v-if="st==2" style="padding:20rpx">
							<parse :content="business.content"></parse>
						</view>
						<view class="cp_detail" v-if="st==3" style="padding-top:20rpx">
							<view class="free_product" v-for="(item, index) in datalist">
								<view class="itemlist">
								  <view class="pic">
										<image :src="item.pic" />
									</view>
									<view class="right">
										<view class="title">{{item.name}}</view>
										<view class="price">	
												<text class="t1">￥</text>
												<text class="t2">{{item.sell_price}}</text>
										</view>
									</view>
									<view class="hexiao"  >
										<button @tap="miandantobuy" :data-proid="item.id" :style="{background:'linear-gradient(-90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'}">核销码</button>
									</view>
								</view>
							</view>
						</view>
					</view>
					<view v-if="couponcount>0" class="covermy" @tap="goto" :data-url="'/pagesExt/coupon/couponlist?bid=' + business.id">
						<text style="padding:0 4rpx;height:36rpx;line-height:36rpx">商家</text>
						<text style="padding:0 4rpx;height:36rpx;line-height:36rpx">{{t('优惠券')}}</text>
					</view>
				</view>
			</block>	
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
      
      <!-- 短视频s -->
      <view v-if="business.show_shortvideo && business.show_shortvideo == 1 && shortvideos && shortvideos.length>0" class="shortvideo_content">
      	<block v-if="shortvideo_type == 1">
      		<view v-for="(item, index) in shortvideos" :key="index" class="item2" @tap="goto"  :data-url="'/activity/shortvideo/detail?id=' + item.id">
      			<view class="f1"><image class="image" :src="item.coverimg" mode="widthFix"/></view>
      			<view class="f2">
      				<view class="t1">{{item.name}}</view>
      				<view class="t2">{{item.description}}</view>
      				<view class="t3">播放量 {{item.view_num}} <text style="padding:0 20rpx">·</text> 点赞数 {{item.zan_num}}</view>
      				<view class="t4"><image class="touxiang" :src="item.binfo.logo"/>{{item.binfo.name}}</view>
      			</view>
      		</view>
      	</block>
      	<block v-else>
      		<view v-for="(item, index) in shortvideos" :key="index" class="item" :style="index%2==0?'margin-right:2%':''">
      			<image class="ff" mode="widthFix" :src="item.coverimg" @tap="goto"  :data-url="'/activity/shortvideo/detail?id=' + item.id"></image>
      			<view class="f2">
      				<view class="t1"><image class="touxiang" :src="item.binfo.logo"/></view>
      				<view class="t2"><image class="tubiao" :src="pre_url+'/static/img/shortvideo_playnum.png'"/>{{item.view_num}}</view>
      				<view class="t3"><image class="tubiao" :src="pre_url+'/static/img/shortvideo_likenum.png'"/>{{item.zan_num}}</view>
      			</view>
      		</view>
      	</block>
      </view>
      <!-- 短视频e -->
      
			<dp-tabbar :opt="opt" @getmenuindex="getmenuindex"></dp-tabbar>
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
								<view class="mendian-info" @tap="changeMendian" :data-index="index" :data-id="item.id" :style="{background:(item.id==mendianid?'rgba('+t('color1rgb')+',0.1)':'')}">
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
							<view class="mendian-all" @tap="allMendian" :style="{background:'rgba('+t('color1rgb')+',0.9)',color:'#FFF'}">全部门店</view>
						</view>
					</view>
				</view>
			</block>
			<!-- 门店选择end -->
		</view>
		<!-- 首消店铺 -->
		<nodata v-if="isfirstbuy" :text='msg'></nodata>
		<!-- 首消店铺 end -->
		
		<uni-popup id="dialogHxqr" ref="dialogHxqr" type="dialog">
			<view class="hxqrbox">
				<image :src="miandanorder.hexiao_qr" @tap="previewImage" :data-url="miandanorder.hexiao_qr" class="img"/>
				<view class="txt">请出示核销码给核销员进行核销</view>
				<view class="close" @tap="closeHxqr">
					<image :src="pre_url+'/static/img/close2.png'" style="width:100%;height:100%"/>
				</view>
			</view>
		</uni-popup>
	<popmsg ref="popmsg"></popmsg>
	<loading v-if="loading" ></loading>
	<wxxieyi></wxxieyi>
</scroll-view> 
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
			platform: app.globalData.platform,
			homeNavigationCustom: app.globalData.homeNavigationCustom,
			navigationBarBackgroundColor: app.globalData.navigationBarBackgroundColor,
			navigationBarTextStyle: app.globalData.navigationBarTextStyle,
			businessindexNavigationCustom: app.globalData.businessindexNavigationCustom,
			navigationMenu:{},
			statusBarHeight: 20,
			screenWidth: 375,
			isdiy: 0,
      
      bid:0,
			st: 0,
			business:[],
			countcomment:0,
			couponcount:0,
			pics:[],
			pagenum: 1,
			datalist: [],
			topbackhide: false,
			nomore: false,
			nodata:false,

			title: "",
			sysset: "",
			guanggaopic: "",
			guanggaourl: "",
			guanggaotype: "1",
			guanggaoparam:{},
			pageinfo: "",
			pagecontent: "",
			showfw:false,
			yuyue_clist:[],
			yuyue_cid:0,
			video_status:0,
			video_title:'',
			video_tag:[],
      bset:'',
			show_mendian:0,
			mendianid:0,
			mendiancount:0,
			mendianlist:[],
			mendianindex:-1,
			isshowmendianmodal:false,
			mendian_cache_prefix:'',
			mendian:{},
			latitude:'',
			longitude:'',
			miandanorder:'',
			cacheExpireTime:10,//缓存过期时间
      
      shortvideos:'',
      shortvideo_type:0,
      showShare:false,
      regbid:0,
      type:'', //类型 firstbuy:首消界面
      isfirstbuy:0, //是否查询到首消界面
      msg:'', //输出信息   
		}
	},
	onLoad: function (opt) {
		this.opt = app.getopts(opt);
    this.st = this.opt.st || 0;

    var bid = this.opt.id || 0;
		this.opt.bid = bid;
    this.bid     = bid;
    this.regbid  = this.opt.regbid || 0;
    this.type  = this.opt.type || '';

		var sysinfo = uni.getSystemInfoSync();
		this.wxNavigationBarMenu();
		this.statusBarHeight = sysinfo.statusBarHeight;
		this.screenWidth = sysinfo.screenWidth;
		//当前商家用户所选门店缓存
		this.mendian_cache_prefix = 'business_mendian_'+app.globalData.aid+'_'+this.opt.bid
		var cache_mendianid = app.getCache(this.mendian_cache_prefix+'_id');
		if(cache_mendianid){
			this.mendianid = cache_mendianid;
		}
		console.log('businessindexNavigationCustom:'+app.globalData.businessindexNavigationCustom)
		this.getdata();
  },
	onShow:function() {
		if(app.globalData.platform=='wx' && app.globalData.hide_home_button==1){
			uni.hideHomeButton();
		}
	},
	onPullDownRefresh: function () {
		this.getdata();
	},
	onReachBottom: function () {
		if (this.isdiy == 0) {
			if (!this.nodata && !this.nomore) {
				this.pagenum = this.pagenum + 1;
				this.getDataList(true);
			}
		}
	},
	onPageScroll: function (e) {
		uni.$emit('onPageScroll',e);
	},
	onShareAppMessage:function(){
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
		
		return this._sharewx({title:this.business.name});
	},
	onShareTimeline:function(){
		var sharewxdata = this._sharewx({title:this.business.name,pic:this.business.logo});
		var query = (sharewxdata.path).split('?')[1]+'&seetype=circle';
		return {
			title: sharewxdata.title,
			imageUrl: sharewxdata.imageUrl,
			query: query
		}
	},
	onPageScroll: function (e) {
		if (this.isdiy == 0) {
			var that = this;
			var scrollY = e.scrollTop;
			if (scrollY > 200 && !that.topbackhide) {
				that.topbackhide = true;
			}
			if (scrollY < 150 && that.topbackhide) {
				that.topbackhide = false;
			}
		}
	},
	methods: {
		wxNavigationBarMenu:function(){
			var homeNavigationCustom = this.homeNavigationCustom
			if(this.platform=='wx' && ( homeNavigationCustom==4 || homeNavigationCustom==6 || homeNavigationCustom==8)){
				//胶囊菜单信息
				this.navigationMenu = wx.getMenuButtonBoundingClientRect()
			}else if(this.platform=='wx' && this.businessindexNavigationCustom!=0){
				
			}
		},
		getdata: function () {
			var that = this;
			var id = that.opt.id || 0;
			this.mendian_cache_prefix = 'business_mendian_'+app.globalData.aid+'_'+this.opt.bid
			var cache_mendianid = app.getCache(this.mendian_cache_prefix+'_id');
			if(cache_mendianid){
				this.mendianid = cache_mendianid;
			}
			that.loading = true;
			app.get('ApiBusiness/index', {id: id,mendian_id:that.mendianid,latitude:that.latitude,longitude:that.longitude,regbid:that.regbid,type:that.type}, function (res) {
				that.loading = false;
				
				//首消界面
				if(res.status == 0 && res.type && (res.type == 'firstbuy' || res.type == 'lastbuy')){
					that.isfirstbuy = 1;
					that.msg = res.msg;
					return;
				}
				
				//首消界面
				if(that.opt && that.opt.type && (res.type == 'firstbuy' || res.type == 'lastbuy')){
					that.opt.typebid = res.business.id;
				}
				
				that.isdiy = res.isdiy;
				that.business = res.business;
				that.countcomment = res.countcomment;
				that.couponcount = res.couponcount;
				that.pics = res.pics;
				var bset = res.bset;
				that.bset = bset;
				
				if(app.globalData.platform=='wx' && app.globalData.hide_home_button==1){
					uni.hideHomeButton();
				}
				
				if(bset){
						if(bset.show_product){
								that.st = 0;
						}else if(bset.show_comment){
								that.st = 1;
						}else if(bset.show_detail){
								that.st = 2;
						}
						if(bset.show_mianndan){
							that.st = 3;
						}
				}

				if(that.business && that.business.mid && res.business_indexbindfenxiao){
					app.globalData.pid = that.business.mid;
					uni.setStorageSync('pid', that.business.mid);
				}

				that.guanggaopic = res.guanggaopic;
				that.guanggaourl = res.guanggaourl;
				that.guanggaotype = res.guanggaotype;
				that.guanggaoparam = res.guanggaoparam;
				that.pageinfo = res.pageinfo;
				that.pagecontent = res.pagecontent;
				that.sysset = res.sysset;
				that.showfw = res.showfw || false;
				if(that.showfw){
					that.st = -1;
					that.yuyue_clist = res.yuyue_clist;
				}
				if(res.yuyueset){
					that.video_status = res.yuyueset.video_status;
					that.video_title = res.yuyueset.video_title;
					that.video_tag = res.yuyueset.video_tag;
				}
				
				//显示门店
				that.show_mendian = res.show_mendian
				if(res.show_mendian){
					that.mendiancount = res.mendianlist.length
					if(res.mendian){
						that.mendian = res.mendian
						if(app.getCache(that.mendian_cache_prefix+'_id')!=that.mendianid){
							app.setCache(that.mendian_cache_prefix+'_id',that.mendianid,that.cacheExpireTime)
						}
					}
					that.mendianlist = res.mendianlist
				}
				//显示门店

				if(res.shortvideos){
          that.shortvideos     = res.shortvideos;
          that.shortvideo_type = res.shortvideo_type;
        }
				
        if(res.showShare){
          that.showShare = res.showShare;
        }

				that.loaded({title:that.business.name,pic:that.business.logo});
				if(res.show_mendian || res.show_location_jl == 1){
					if(!that.latitude || !that.longitude){
						app.getLocation(function(resL){
							that.latitude = resL.latitude
							that.longitude = resL.longitude
							that.getdata()
						})
					}
				}
				if (res.isdiy == 0) {
					that.isload = 1;
					uni.setNavigationBarTitle({
						title: that.business.name
					});
					that.getDataList();
				} else {
					if (res.status == 2) {
						//付费查看
						app.goto('/pagesExt/pay/pay?fromPage=index&id=' + res.payorderid + '&pageid=' + that.res.id, 'redirect');
						return;
					}
					if (res.status == 1) {
						var pagecontent = res.pagecontent;
						that.isdiy = 1;

						that.title = res.pageinfo.title;
						that.sysset = res.sysset;
						that.guanggaopic = res.guanggaopic;

						that.guanggaourl = res.guanggaourl;
						that.pageinfo = res.pageinfo;
						that.pagecontent = pagecontent;
						uni.setNavigationBarTitle({
							title: res.pageinfo.title
						});
					} else {
						app.alert(res.msg);
					}
				}
			});
		},
		changetab: function (e) {
			var st = e.currentTarget.dataset.st;
			this.pagenum = 1;
			this.st = st;
			this.datalist = [];
			uni.pageScrollTo({
				scrollTop: 0,
				duration: 0
			});
			this.getDataList();
		},
		getDataList: function (loadmore) {
			if(!loadmore){
				this.pagenum = 1;
				this.datalist = [];
			}
			var that = this;
			var pagenum = that.pagenum;
			var st = that.st;
			that.loading = true;
			that.nodata = false;
			that.nomore = false;
			app.post('ApiBusiness/getdatalist', {id: that.business.id,st: st,pagenum: pagenum,yuyue_cid:that.yuyue_cid,mendian_id:that.mendianid}, function (res) {
				that.loading = false;
				uni.stopPullDownRefresh();
        var data = res.data;
        if (pagenum == 1) {
          that.datalist = data;
          if (data.length == 0) {
            that.nodata = true;
          }
        }else{
          if (data.length == 0) {
            that.nomore = true;
          } else {
            var datalist = that.datalist;
            var newdata = datalist.concat(data);
            that.datalist = newdata;
          }
        }
      });
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
		phone:function(e) {
			var phone = e.currentTarget.dataset.phone;
			uni.makePhoneCall({
				phoneNumber: phone,
				fail: function () {
				}
			});
		},
		//改变子分类
		changeyuyueCTab: function (e) {
			var that = this;
			var id = e.currentTarget.dataset.id;
			this.nodata = false;
			this.yuyue_cid = id;
			this.pagenum = 1;
			this.datalist = [];
			this.nomore = false;
			this.getDataList();
		},
		//门店模式start
		showMendianModal:function(){
			var that = this;
			if(that.mendianlist.length>0){
				that.isshowmendianmodal = true
			}else{
				app.post('ApiMendian/mendianlist', {
					latitude: that.latitude,
					longitude: that.longitude,
					bid:that.opt.bid
				}, function(resp) {
					that.loading = false
					if(resp.status==1){
						that.mendianlist = resp.data
						that.mendiancount = that.mendianlist.length
						that.isshowmendianmodal = true
					}else{
						app.error(resp.msg);
					}
				})
			}
		},
		hideMendianModal:function(){
			this.isshowmendianmodal = false
		},
		changeMendian:function(e){
			var that = this;
			var index = e.currentTarget.dataset.index;
			var mendian = that.mendianlist[index]
			let object = {
				address: mendian.address,
				latitude: mendian.latitude,
				longitude: mendian.longitude,
				mendian_id: mendian.id,
				mendian_name: mendian.name
			}
			that.mendianid = mendian.id
			that.mendian = mendian
			app.setCache('locationCache',object,that.cacheExpireTime);
			app.setCache(that.mendian_cache_prefix+'_id',mendian.id,that.cacheExpireTime)
			
			that.isshowmendianmodal = false
			if(that.isdiy==1){
				that.getdata()
			}else{
				that.getDataList(false)
			}
		},
		//免单下单
		miandantobuy: function(e) {
			var that = this;
			var bid = that.business.id;
			var proid = e.currentTarget.dataset.proid;
			app.showLoading('提交中');
			app.post('ApiBusinessMiandan/createOrder', {
				proid: proid,
				bid: bid
			}, function(res) {
				app.showLoading(false);
				if(res.status==1 && res.order){
						that.miandanorder = res.order
						that.$refs.dialogHxqr.open();
				}else if(res.status==2 && res.url){
						//跳转指定页面
						//app.error(res.msg);						
						app.confirm(res.msg, function () {
							app.goto(res.url);
						});
						return;
				}else if (res.status == 0) {
					//that.showsuccess(res.data.msg);
					app.error(res.msg);
					return;
				}
			});
		},
		showhxqr:function(){
			this.$refs.dialogHxqr.open();
		},
		closeHxqr:function(){
			this.$refs.dialogHxqr.close();
		},
		allMendian:function(e){
			this.mendianid=0
			this.isshowmendianmodal = false
			app.removeCache(this.mendian_cache_prefix+'_id')
			this.mendian = {};
			if(that.isdiy==1){
				that.getdata()
			}else{
				that.getDataList(false)
			}
		},
		//门店模式end
	}
}
</script>
<style>
	@import url("../../pages/index/location.css");
	.pageContainer{
		/* position: absolute; */
		width: 100%;
		height: 100%;
	}
.container{position:relative}
.nodiydata{display:flex;flex-direction:column}
.nodiydata .swiper {width: 100%;height: 400rpx;position:relative;z-index:1}
.nodiydata .swiper .image {width: 100%;height: 400rpx;overflow: hidden;}

.nodiydata .topcontent{width:94%;margin-left:3%;padding: 24rpx; border-bottom:1px solid #eee;margin-bottom:20rpx; background: #fff;margin-top:-120rpx;display:flex;flex-direction:column;align-items:center;border-radius:16rpx;position:relative;z-index:2;}
.nodiydata .topcontent .logo{width:160rpx;height:160rpx;margin-top:-104rpx;border:2px solid rgba(255,255,255,0.5);border-radius:50%;}
.nodiydata .topcontent .logo .img{width:100%;height:100%;border-radius:50%;}

.nodiydata .topcontent .title {color:#222222;font-size:36rpx;font-weight:bold;margin-top:12rpx}
.nodiydata .topcontent .desc {display:flex;align-items:center}
.nodiydata .topcontent .desc .f1{ margin:20rpx 0; font-size: 24rpx;color:#FC5648;display:flex;align-items:center}
.nodiydata .topcontent .desc .f1 .img{ width:24rpx;height:24rpx;margin-right:10rpx;}
.nodiydata .topcontent .desc .f2{ margin:10rpx 0;padding-left:60rpx;font-size: 24rpx;color:#999;}
.nodiydata .topcontent .tel{font-size:28rpx;color:#fff; padding:16rpx 40rpx; border-radius: 60rpx; font-weight: normal }
.nodiydata .topcontent .tel .img{ width: 28rpx;height: 28rpx; vertical-align: middle;margin-right: 10rpx}
.nodiydata .topcontent .address{width:100%;display:flex;align-items:center;padding-top:20rpx}
.nodiydata .topcontent .address .f1{width:28rpx;height:28rpx;margin-right:8rpx}
.nodiydata .topcontent .address .f2{flex:1;color:#999999;font-size:26rpx}
.nodiydata .topcontent .address .f3{display: inline-block; width:26rpx; height: 26rpx}

.nodiydata .contentbox{width:94%;margin-left:3%;background: #fff;border-radius:16rpx;margin-bottom:32rpx;overflow:hidden}

.nodiydata .shop_tab{display:flex;width: 100%;height:90rpx;border-bottom:1px solid #eee;}
.nodiydata .shop_tab .cptab_text{flex:1;text-align:center;color:#646566;height:90rpx;line-height:90rpx;position:relative;flex-basis: content}
.nodiydata .shop_tab .cptab_current{color: #323233;}
.nodiydata .shop_tab .after{display:none;position:absolute;left:50%;margin-left:-16rpx;bottom:10rpx;height:3px;border-radius:1.5px;width:32rpx}
.nodiydata .shop_tab .cptab_current .after{display:block;}


.nodiydata .cp_detail{min-height:500rpx}
.nodiydata .comment .item{background-color:#fff;padding:10rpx 20rpx;display:flex;flex-direction:column;}
.nodiydata .comment .item .f1{display:flex;width:100%;align-items:center;padding:10rpx 0;}
.nodiydata .comment .item .f1 .t1{width:70rpx;height:70rpx;border-radius:50%;}
.nodiydata .comment .item .f1 .t2{padding-left:10rpx;color:#333;font-weight:bold;font-size:30rpx;}
.nodiydata .comment .item .f1 .t3{text-align:right;}
.nodiydata .comment .item .f1 .t3 .img{width:24rpx;height:24rpx;margin-left:10rpx}
.nodiydata .comment .item .score{ font-size: 24rpx;color:#f99716;}
.nodiydata .comment .item .score image{ width: 140rpx; height: 50rpx; vertical-align: middle;  margin-bottom:6rpx; margin-right: 6rpx;}
.nodiydata .comment .item .f2{display:flex;flex-direction:column;width:100%;padding:10rpx 0;}
.nodiydata .comment .item .f2 .t1{color:#333;font-size:28rpx;}
.nodiydata .comment .item .f2 .t2{display:flex;width:100%}
.nodiydata .comment .item .f2 .t2 image{width:100rpx;height:100rpx;margin:10rpx;}
.nodiydata .comment .item .f2 .t3{color:#aaa;font-size:24rpx;}
.nodiydata .comment .item .f2 .t3{color:#aaa;font-size:24rpx;}
.nodiydata .comment .item .f3{width:100%;padding:10rpx 0;position:relative}
.nodiydata .comment .item .f3 .arrow{width: 16rpx;height: 16rpx;background:#eee;transform: rotate(45deg);position:absolute;top:0rpx;left:36rpx}
.nodiydata .comment .item .f3 .t1{width:100%;border-radius:10rpx;padding:10rpx;font-size:22rpx;color:#888;background:#eee}

.contentbox .free_product{ position: relative;}
.contentbox .free_product .pic{ margin-right: 20rpx;}
.contentbox .free_product .right .title{ color: #222222; font-weight: bold;}
.contentbox .free_product .right .price{ color: #EF3835; font-weight: bold; margin-top: 50rpx;}
.contentbox .free_product .hexiao{ position: absolute; right:20rpx; bottom:15% }
.contentbox .free_product .hexiao button{ color: #fff; border-radius: 50rpx;padding:0 40rpx; height: 60rpx; line-height: 60rpx;}
.contentbox .free_product .itemlist{ border-bottom: 1rpx solid #EDEDED;display: flex; padding:20rpx; }
.contentbox .pic image{ width: 130rpx; height: 130rpx; border-radius: 10rpx;}

.nodiydata .nomore-footer-tips{background:#fff!important}

.nodiydata .covermy{position:fixed;z-index:99999;cursor:pointer;display:flex;flex-direction:column;align-items:center;justify-content:center;overflow:hidden;z-index:9999;top:81vh;left:82vw;color:#fff;background-color:rgba(92,107,129,0.6);width:110rpx;height:110rpx;font-size:26rpx;border-radius:50%;}


.classify-ul{width:100%;height:70rpx;padding:0 10rpx;}
.classify-li{flex-shrink:0;display:flex;background:#F5F6F8;border-radius:22rpx;color:#6C737F;font-size:20rpx;text-align: center;height:44rpx; line-height:44rpx;padding:0 28rpx;margin:12rpx 10rpx 12rpx 0}

	.dp-cover{height: auto; position: relative;}
	.dp-cover-cover{position:fixed;z-index:99999;cursor:pointer;display:flex;align-items:center;justify-content:center;overflow:hidden;background-color: inherit;}
/* 核销 */
.hxqrbox{background:#fff;padding:50rpx;position:relative;border-radius:20rpx}
.hxqrbox .img{width:400rpx;height:400rpx}
.hxqrbox .txt{color:#666;margin-top:20rpx;font-size:26rpx;text-align:center}
.hxqrbox .close{width:50rpx;height:50rpx;position:absolute;bottom:-100rpx;left:50%;margin-left:-25rpx;border:1px solid rgba(255,255,255,0.5);border-radius:50%;padding:8rpx}
	
	/* 门店 */
	.mendian-box{background: #FFFFFF;width: 94%; margin:0 3% 20rpx 3%;padding: 20rpx;display: flex;justify-content: space-between;border-radius: 16rpx;align-items: center;color: #555;}
	.mendian-box .f1{flex: 1;}
	.mendian-box .f2{flex-shrink: 0;width: 30rpx;margin-top: 4rpx;}
	.mendian-box .tips{font-size: 26rpx;color: #b0b0b0;}
	.mendian-box .icon{width: 30rpx;height: 30rpx;}
	.mendian-box .name{padding-left: 12rpx;flex: 1;}
	.mendian-box .num{font-size: 32rpx;font-weight: bold;padding: 0 6rpx;}
	.mendian-box .exchange{width: 32rpx; height: 32rpx;}
	.mendian-box .more{width: 26rpx; height: 26rpx;margin-top: 4rpx;}
	.mendian-all{width: 90%;text-align: center;padding: 26rpx 20rpx;margin: 0 5%;border-radius: 60rpx;}
	/* 门店 */
	
	.navigation {
		width: 100%;
		background: #fff;
		position: fixed;
		z-index: 99;
		padding-bottom:10px
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
	
  .shortvideo_content{width:96%;margin-left:2%;position:relative;margin-top:10rpx; display:flex;flex-wrap:wrap}
  .shortvideo_content .item{width:49%;height:500rpx;background:#fff;overflow:hidden;border-radius:8rpx;margin-bottom:20rpx;position:relative;background:#666}
  .shortvideo_content .item .ff{width:100%;height:100%;display:block;}
  .shortvideo_content .item .f2{position: absolute;bottom:20rpx;left:20rpx;display:flex;align-items:center;color:#fff;font-size:22rpx}
  .shortvideo_content .item .f2 .t1{display:flex;align-items:center;text-shadow: 0px 6px 12px rgba(0, 0, 0, 0.12);}
  .shortvideo_content .item .f2 .t2{display:flex;align-items:center;margin-left:30rpx;text-shadow: 0px 6px 12px rgba(0, 0, 0, 0.12);}
  .shortvideo_content .item .f2 .t3{display:flex;align-items:center;margin-left:30rpx;text-shadow: 0px 6px 12px rgba(0, 0, 0, 0.12);}
  .shortvideo_content .item .f2 .tubiao{display:block;height:28rpx;width:28rpx;margin-right:10rpx}
  .shortvideo_content .item .f2 .touxiang{display:block;width:40rpx;height:40rpx;border-radius:50%;}
  
  .shortvideo_content .item2{width:100%;background:#fff;display:flex;padding:20rpx 0;border-bottom:1px solid #f5f5f5}
  .shortvideo_content .item2 .f1 {width:30%;height:0;overflow:hidden;background: #ffffff;padding-bottom:40%;position: relative;border-radius:4rpx;background:#999}
  .shortvideo_content .item2 .f1 .image{position:absolute;top:0;left:0;width: 100%;height:auto}
  .shortvideo_content .item2 .f2 {width: 70%;padding:6rpx 10rpx 5rpx 20rpx;position: relative;}
  .shortvideo_content .item2 .f2 .t1 {color:#222222;font-weight:bold;font-size:30rpx;line-height:40rpx;margin-bottom:10rpx;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:2;overflow:hidden;}
  .shortvideo_content .item2 .f2 .t2 {color:#666;font-size:26rpx;line-height:36rpx;margin-bottom:10rpx;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:2;overflow:hidden;}
  .shortvideo_content .item2 .f2 .t3{color:#222;font-size:22rpx;color:#9C9C9C;margin-top:20rpx;}
  .shortvideo_content .item2 .f2 .t4{display:flex;align-items:center;color:#515254;font-size:24rpx;position:absolute;bottom:10rpx;}
  .shortvideo_content .item2 .f2 .t4 .touxiang{display:block;width:30rpx;height:30rpx;border-radius:50%;margin-right:10rpx;}
  
  /*返利 和 积分显示*/
  .ratio-list{padding-top: 10rpx;    position: absolute;top: 10rpx;right: 0rpx;}
  .ratio-label{height: 40rpx;border-radius: 10rpx;width:160rpx;border: 2rpx solid;margin-right:20rpx;}
  .ratio-label .label{width: 55rpx ;height: 40rpx;line-height: 40rpx;border-radius: 10rpx 20rpx 5rpx 10rpx;color: #fff;text-align: center;}
  .ratio-label .t1{text-align: center;width: 65%;font-size: 28rpx;}
</style>