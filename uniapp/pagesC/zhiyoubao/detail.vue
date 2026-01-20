<template>
<view class="container">
	<block v-if="isload">
		<scroll-view @scroll="scroll" :scrollIntoView="scrollToViewId" :scrollTop="scrollTop" :scroll-y="true" style="height:100%;overflow:scroll">
      <view id="scroll_view_tab0">
        <block v-if="product.pics">
          <view class="swiper-container" v-if="isplay==0">
            <swiper class="swiper" :indicator-dots="false" :autoplay="true" :interval="5000" @change="swiperChange"   :current="current" :style="{ height: swiperHeight + 'px' }">
              <block v-for="(item, index) in product.pics" :key="index">
                <swiper-item class="swiper-item">
                  <view class="swiper-item-view" :id="'content-wrap' + index" :style="{ height: swiperHeight + 'px' }">
                    <image class="img" :src="item" mode="widthFix" @tap="previewImage" :data-urls="product.pics" :data-url="item" @load="loadImg"  />
                  </view>
                </swiper-item>
              </block>
            </swiper>
            <!-- <view class="imageCount">{{current+1}}/{{(product.pics).length}}</view> -->
            <block v-if="product.video">
              <block v-if="product.video_type==1" >
                <view v-if="product.video_feedtype==1"  class="wxfeedvideo">
                  <view class="videop"><image class="playicon" :src="pre_url+'/static/img/video.png'"/><view class="txt">播放视频</view></view>
                  <!-- #ifdef MP-WEIXIN  -->
                  <channel-video class="feedvideo" :feed-token="product.video_feedtoken" :feed-id="product.video_feedid" :finder-user-name="product.video_finderuser" ></channel-video>
                  <!-- #endif -->
                </view>
                <view v-if="product.video_feedtype==0" @tap="goto" :data-url="product.video" class="provideo">
                  <image :src="pre_url+'/static/img/video.png'"/><view class="txt">查看视频</view>
                </view>
              </block>
              <view v-if="product.video_type==0" class="provideo" @tap="payvideo">
                <image :src="pre_url+'/static/img/video.png'"/><view class="txt">{{product.video_duration}}</view>
              </view>
            </block>
          </view>
        </block>
        <view class="videobox" v-if="isplay==1">
          <video autoplay="true" class="video" id="video" :src="product.video"></video>
          <view class="parsevideo" @tap="parsevideo">退出播放</view>
        </view>			

        <view class="header" style="display: flex;">
          <view v-if="product.pic" class="content-pic">
            <image :src="product.pic" mode="widthFix" style="width: 100%;border-radius: 10rpx 10rpx;"></image>
          </view>
          <view style="margin-left: 10rpx;">
            <view class="title">{{product.title}}</view>
            <view class="title2">日期：{{product.performDate}}</view>
            <view class="title2">时间：{{product.performTime}}</view>
            <view class="title2" :style="'color:'+t('color1')">
              <text v-if="product.price>=0">￥{{product.price}}起</text>
              <text v-else>暂无价格</text>
            </view>
          </view>
        </view>

        <view v-if="showotherinfo" style="padding: 0rpx 3%;background-color: #fff;">
          <view style="border-top:2rpx solid #f1f1f1 ;padding: 20rpx 0;">
            <view style="color: #191919;font-weight: bold;">{{product.theaterPlaceName}}</view>
            <view v-if="product.beforeCheckTime && product.beforeCheckTime>0"style="color: #191919;">提前检票时间：{{product.beforeCheckTime}}分钟</view>
            <view v-if="product.beforeTime && product.beforeTime>0"style="color: #191919;">提前演出入场时间：{{product.beforeTime}}分钟</view>
            <view v-if="product.timeLong && product.timeLong>0"style="color: #191919;">演出时长：{{product.timeLong}}分钟</view>
            <view v-if="product.address" style="color: #999;font-size: 26rpx;">地址：{{product.address}}</view>
          </view>
        </view>

      </view>

      <view id="scroll_view_tab2" style="background-color:#fff ;margin-top: 20rpx;padding: 20rpx; ">
        <view style="font-size: 36rpx;font-weight: bold;">详情描述</view>
        <view class="detail" style="margin-top: 20rpx;">
          <parse :content="product.content" @navigate="navigate"></parse>
        </view>
      </view>

      <view style="width:100%;height:140rpx;"></view>
		</scroll-view>
    
    <view class="bottombar flex-row" :class="menuindex>-1?'tabbarbot':'notabbarbot'">
      <view v-if="showkf" class="f1 flex" >
          <view class="item" @tap="goto" :data-url="kfurl" v-if="kfurl!='contact::'">
            <image class="img"
              :src="item.iconPath ? item.iconPath:pre_url+'/static/img/kefu.png'" />
            <view class="t1">{{item.text ? item.text:'客服'}}</view>
          </view>
          <button class="item" v-else open-type="contact" show-message-card="true">
            <image class="img"
              :src="item.iconPath ? item.iconPath:pre_url+'/static/img/kefu.png'" />
            <view class="t1">{{item.text ? item.text:'客服'}}</view>
          </button>
      </view>
      <view class="op">
      	<view v-if="product.price>=0" class="tobuy flex-x-center flex-y-center" :style="{background:t('color1')}" @tap="goseat">
      			立即购票
      	</view>
        <view v-else class="tobuy flex-x-center flex-y-center" style="background:#ddd" >
        		立即购票
        </view>
      </view>
    </view>
    
    <view v-if="openselarea" class="popup__container">
    	<view class="popup__overlay" @tap.stop="handleClickMask"></view>
    	<view class="popup__modal">
    		<view class="popup__title">
    			<text class="popup__title-text">选择区域</text>
    			<image :src="`${pre_url}/static/img/hotel/popupClose2.png`" class="popup__close" style="width:56rpx;height:56rpx;top:20rpx;right:20rpx" @tap.stop="handleClickMask"/>
    		</view>
    		<view class="popup__content">
          <view class="calendar-view" style="padding:0 10rpx;">
            <scroll-view style="width: 100%;height:360rpx;" :scroll-y="true" :scroll-x="true">
              <block v-for="(item,index) in areadatas">
                <view class="areacss" @tap="selarea" :data-id="item.id" :style="areaid == item.id?'border:2rpx solid '+t('color1')+';':''">
                  <view>{{item.areaName}}</view>
                  <view v-if="item.price>=0" style="color: red;">￥{{item.price}}</view>
                </view>
              </block>
            </scroll-view>
          </view>
    			<view class="choose-but-class" :style="'background: linear-gradient(90deg,rgba('+t('color1rgb')+',1) 0%,rgba('+t('color1rgb')+',1) 100%)'" @tap="popupClose">
    				确认选择
    			</view>
    		</view>
    	</view>
    </view>

		<view v-if="sharetypevisible" class="popup__container">
			<view class="popup__overlay" @tap.stop="handleClickMask"></view>
			<view class="popup__modal" style="height:320rpx;min-height:320rpx">
				<view class="popup__content">
					<view class="sharetypecontent">
						<!-- #ifdef APP -->
						<view class="f1" @tap="shareapp">
							<image class="img" :src="pre_url+'/static/img/sharefriends.png'"/>
							<text class="t1">分享给好友</text>
						</view>
						<!-- #endif -->
						<!-- #ifdef H5 -->
						<view class="f1" @tap="sharemp" v-if="getplatform() == 'mp'">
							<image class="img" :src="pre_url+'/static/img/sharefriends.png'"/>
							<text class="t1">分享给好友</text>
						</view>
						<!-- #endif -->
						<!-- #ifndef H5 || APP -->
						<button class="f1" open-type="share" v-if="getplatform() != 'h5'">
							<image class="img" :src="pre_url+'/static/img/sharefriends.png'"/>
							<text class="t1">分享给好友</text>
						</button>
						<!-- #endif -->
					</view>
				</view>
			</view>
		</view>
	</block>

	<loading v-if="loading"></loading>
	<dp-tabbar :opt="opt" @getmenuindex="getmenuindex"></dp-tabbar>
	<popmsg ref="popmsg"></popmsg>
	<wxxieyi></wxxieyi>
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
			pre_url: app.globalData.pre_url,
			isload:false,
			current: 0,
			isplay: 0,
      
      id:0,
      playDate:'',
			product: [],

			title: "",
			sharepic: "",
			sharetypevisible: false,
			scrolltopshow: false,

			toptabbar_show:0,
			toptabbar_index:0,
      scrollToViewId: "",
			scrollTop:0,
			scrolltab0Height:0,
			scrolltab1Height:0,
			scrolltab2Height:0,
			scrolltab3Height:0,

			swiperHeight: '',
			scrollLeft:0,
      
      showkf:false,
      kfurl:'',
      business:'',
      
      areadatas:[],
      openselarea:false,
      areaid:0,
      
      showotherinfo:false,

		};
	},
  onLoad: function (opt) {
		this.opt = app.getopts(opt);
    this.id = this.opt.id || 0;
    this.playDate = this.opt.playDate || '';
		this.getdata();
	},
	onShow:function(e){
	},
	onPullDownRefresh: function () {
		this.getdata();
	},
	onShareAppMessage:function(){
		return this._sharewx({title:this.product.title,pic:this.product.pic});
	},
	onShareTimeline:function(){
		var sharewxdata = this._sharewx({title:this.product.title,pic:this.product.pic});
		var query = (sharewxdata.path).split('?')[1]+'&seetype=circle';
		return {
			title: sharewxdata.title,
			imageUrl: sharewxdata.imageUrl,
			query: query
		}
	},
	methods: {
		getdata:function(){
			var that = this;
			that.loading = true;
			app.get('ApiZhiyoubao/getPerformDetail', {id: that.id}, function (res) {
				that.loading = false;
				if (res.status == 1) {
          var product = res.product;
          that.title = product.title;
          uni.setNavigationBarTitle({
            title: product.title
          });
          that.product = product;
          
          that.areadatas = res.areadatas;
          
          that.sharepic = product.pic;
          that.showkf = res.showkf || false;
          that.kfurl = '/pages/kefu/index?bid='+product.bid;
          if(app.globalData.initdata.kfurl != ''){
            that.kfurl = app.globalData.initdata.kfurl;
          }
          
          that.business = res.business || '';
          if(that.business && that.business.kfurl){
            that.kfurl = that.business.kfurl;
          }
          
          if(res.showotherinfo) that.showotherinfo = res.showotherinfo
          that.loaded({title:product.title,pic:product.pic});
				}else{
          app.alert(res.msg,function(){
          	app.goback();
          });
          return;
        }
				
			});
		},
	  loadImg() {
			this.getCurrentSwiperHeight('.img');
		},
		swiperChange: function (e) {
			var that = this;
			that.current = e.detail.current;
			// 禁止错误滑动事件
			if(!e.detail.source) return that.current = 0;
			//动态设置swiper的高度，使用nextTick延时设置
			this.$nextTick(() => {
			  this.getCurrentSwiperHeight('.img');
			});
		},
		// 动态获取内容高度
	  getCurrentSwiperHeight(element) {
				// #ifndef MP-ALIPAY
				let query = uni.createSelectorQuery().in(this);
				query.selectAll(element).boundingClientRect();
				var imgList = this.product.pics;
        if(imgList){
          query.exec((res) => {
            // 切换到其他页面swiper的change事件仍会触发，这时获取的高度会是0，会导致回到使用swiper组件的页面不显示了
            if (imgList.length && res[0][this.current].height) {
              this.swiperHeight = res[0][this.current].height;
            }
          });	
        }
	      
				// #endif
				// #ifdef MP-ALIPAY
				var imgList = this.product.pics;
        if(imgList){
          my.createSelectorQuery().select(element).boundingClientRect().exec((ret) => {
          	if (imgList.length && ret[this.current].height) {
          	  this.swiperHeight = ret[this.current].height;
          	}
          });
        }
				
				// #endif
		},
		payvideo: function () {
			this.isplay = 1;
			uni.createVideoContext('video').play();
		},
		parsevideo: function () {
			this.isplay = 0;
			uni.createVideoContext('video').stop();
		},
		shareClick: function () {
			this.sharetypevisible = true;
		},
		handleClickMask: function () {
			this.sharetypevisible = false
		},

		onPageScroll: function (e) {
			uni.$emit('onPageScroll',e);
		},
		changetoptab:function(e){
			var index = e.currentTarget.dataset.index;
			this.scrollToViewId = 'scroll_view_tab'+index;
			this.toptabbar_index = index;
			if(index == 0) this.scrollTop = 0;
			console.log(index);
		},
		scroll:function(e){
			var scrollTop = e.detail.scrollTop;
			//console.log(e)
			var that = this;
			if (scrollTop > 200) {
				that.scrolltopshow = true;
			}
			if(scrollTop < 150) {
				that.scrolltopshow = false
			}
			if (scrollTop > 100) {
				that.toptabbar_show = true;
			}
			if(scrollTop < 50) {
				that.toptabbar_show = false
			}
			var height0 = that.scrolltab0Height;
			var height1 = that.scrolltab0Height + that.scrolltab1Height;
			var height2 = that.scrolltab0Height + that.scrolltab1Height + that.scrolltab2Height;
			//var height3 = that.scrolltab0Height + that.scrolltab1Height + that.scrolltab2Height + that.scrolltab3Height;
			if(scrollTop >=0 && scrollTop < height0){
				//this.scrollToViewId = 'scroll_view_tab0';
				this.toptabbar_index = 0;
			}else if(scrollTop >= height0 && scrollTop < height1){
				//this.scrollToViewId = 'scroll_view_tab1';
				this.toptabbar_index = 1;
			}else if(scrollTop >= height1 && scrollTop < height2){
				//this.scrollToViewId = 'scroll_view_tab2';
				this.toptabbar_index = 2;
			}else if(scrollTop >= height2){
				//this.scrollToViewId = 'scroll_view_tab3';
				this.toptabbar_index = 3;
			}
		},
		sharemp:function(){
			app.error('点击右上角发送给好友或分享到朋友圈');
			this.sharetypevisible = false
		},
		shareapp:function(){
			// #ifdef APP
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
						sharedata.title = that.product.title;
						//sharedata.summary = that.product.sharedesc || that.product.sellpoint;
						sharedata.href = app.globalData.pre_url +'/h5/'+app.globalData.aid+'.html#/pagesC/zhiyoubao/detail?scene=id_'+that.product.id+'-pid_' + app.globalData.mid;
						sharedata.imageUrl = that.product.pic;
						var sharelist = app.globalData.initdata.sharelist;
						if(sharelist){
							for(var i=0;i<sharelist.length;i++){
								if(sharelist[i]['indexurl'] == '/pagesC/zhiyoubao/detail'){
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
		goseat:function(e){
      var that = this;
      if(that.areadatas && that.areadatas.length>0){
        that.openselarea = true;
      }else{
        app.goto('seat?type=0&id='+that.product.id)
      }
    },
    handleClickMask:function(){
    	this.openselarea = false;
    },
    popupClose:function(){
      this.openselarea = false;
      app.goto('seat?type=0&id='+this.product.id+'&areaid='+this.areaid);
    },
    selarea:function(e){
       var that = this;
       that.areaid = e.currentTarget.dataset.id;
    }
	},
	
};
</script>
<style scoped>
page {position: relative;width: 100%;}
.container{height:100%}

.swiper-container{position:relative;overflow: hidden;}
.swiper {width: 100%;height: 750rpx;overflow: hidden;}
.swiper-item-view{width: 100%;height: 750rpx;}
.swiper .img {width: 100%;height: 750rpx;overflow: hidden;}

.imageCount {width:100rpx;height:50rpx;background-color: rgba(0, 0, 0, 0.3);border-radius:40rpx;line-height:50rpx;color:#fff;text-align:center;font-size:26rpx;position:absolute;right:13px;bottom:20rpx;}

.provideo{background:rgba(255,255,255,0.7);width:190rpx;height:54rpx;padding:0 20rpx 0 4rpx;border-radius:27rpx;position:absolute;bottom:30rpx;left:50%;margin-left:-80rpx;display:flex;align-items:center;justify-content:space-between}
.provideo image,.playicon{width:50rpx;height:50rpx;}
.provideo .txt{flex:1;text-align:center;padding-left:10rpx;font-size:24rpx;color:#333}
.wxfeedvideo{background:rgba(255,255,255,0.7);width:190rpx;height:54rpx;padding:0 20rpx 0 4rpx;border-radius:27rpx;position:absolute;bottom:30rpx;left:50%;margin-left:-80rpx;}
.wxfeedvideo .videop{display:flex;align-items:center;justify-content:space-between}
.wxfeedvideo .feedvideo{position: relative;height: 54rpx;width: 100%;top: -54rpx;z-index: 9999;width: 200rpx;opacity: 0;}
.videobox{width:100%;height:750rpx;text-align:center;background:#000}
.videobox .video{width:100%;height:650rpx;}
.videobox .parsevideo{margin:0 auto;margin-top:20rpx;height:40rpx;line-height:40rpx;color:#333;background:#ccc;width:140rpx;border-radius:25rpx;font-size:24rpx}

.header {width: 100%;padding: 20rpx 3%;background: #fff;}
.header .price_share{width:100%;min-height:100rpx;display:flex;align-items:center;justify-content:space-between}
.header .price_share .price{display:flex;align-items:flex-end}
.header .price_share .price .f1{font-size:50rpx;color:#51B539;font-weight:bold}
.header .price_share .price .f2{font-size:26rpx;color:#C2C2C2;text-decoration:line-through;margin-left:30rpx;padding-bottom:5px}
.header .price_share .share{display:flex;flex-direction:column;align-items:center;justify-content:center;min-width: 60rpx;}
.header .price_share .share .img{width:32rpx;height:32rpx;margin-bottom:2px}
.header .price_share .share .txt{color:#333333;font-size:20rpx}
.header .title {color:#000000;font-size:32rpx;line-height:42rpx;font-weight:bold;}
.header .price_share .title { display:flex;align-items:flex-end;}

.choose{ display:flex;align-items:center;width: 100%; background: #fff;  margin-top: 20rpx; height: 88rpx; line-height: 88rpx;padding: 0 3%; color: #333; }
.choose .f0{color:#555;font-weight:bold;height:32rpx;font-size:24rpx;padding-right:30rpx;display:flex;justify-content:center;align-items:center}
.choose .f2{ width: 32rpx; height: 32rpx;}

.popup__container{position: fixed;bottom: 0;left: 0;right: 0;width:100%;height:auto;z-index:10;background:#fff}
.popup__overlay{position: fixed;bottom: 0;left: 0;right: 0;width:100%;height: 100%;z-index: 11;opacity:0.3;background:#000}
.popup__modal{width: 100%;position: absolute;bottom: 0;color: #3d4145;overflow-x: hidden;overflow-y: hidden;opacity:1;padding-bottom:20rpx;background: #fff;border-radius:20rpx 20rpx 0 0;z-index:12;min-height:600rpx;max-height:1000rpx;}
.popup__title{text-align: center;padding:30rpx;position: relative;position:relative}
.popup__title-text{font-size:32rpx}
.popup__close{position:absolute;top:34rpx;right:34rpx}
.popup__content{width:100%;max-height:880rpx;overflow-y:scroll;padding:20rpx 0;}

.detail{min-height:200rpx;}

.detail_title{width:100%;display:flex;align-items:center;justify-content:center;margin-top:60rpx;margin-bottom:30rpx}
.detail_title .t0{font-size:28rpx;font-weight:bold;color:#222222;margin:0 20rpx}
.detail_title .t1{width:12rpx;height:12rpx;background:rgba(253, 74, 70, 0.2);transform:rotate(45deg);margin:0 4rpx;margin-top:6rpx}
.detail_title .t2{width:18rpx;height:18rpx;background:rgba(253, 74, 70, 0.4);transform:rotate(45deg);margin:0 4rpx}

.prolist{width: 100%;height:auto;padding: 8rpx 20rpx;}

.scrolltop{position:fixed;bottom:160rpx;right:20rpx;width:60rpx;height:60rpx;background:rgba(0,0,0,0.4);color:#fff;border-radius:50%;padding:12rpx 10rpx 8rpx 10rpx;z-index:9;}
.scrolltop .image{width:100%;height:100%;}

.bottombar{ width: 94%; position: fixed;bottom: 0px; left: 0px; background: #fff;display:flex;height:100rpx;padding:0 4% 0 2%;align-items:center;box-sizing:content-box}
.bottombar .f1{display:flex;align-items:center;margin-right:15rpx;}
.bottombar .f1 .item{display:flex;flex-direction:column;align-items:center;width:82rpx;position:relative;}
.bottombar .f1 .item .img{ width:44rpx;height:44rpx}
.bottombar .f1 .item .t1{font-size:18rpx;color:#222222;height:30rpx;line-height:30rpx;margin-top:6rpx}
.bottombar .op{border-radius:36rpx;overflow:hidden;display:flex;flex: 1;}
.bottombar .tocart{flex:1;height:72rpx; line-height: 72rpx;color: #fff; border-radius: 0px; border: none;font-size:28rpx;font-weight:bold}
.bottombar .tobuy{flex:1;height: 72rpx; line-height: 72rpx;color: #fff; border-radius: 0px; border: none;font-size:28rpx;font-weight:bold}
.bottombar .cartnum{position:absolute;right:4rpx;top:-4rpx;color:#fff;border-radius:50%;width:32rpx;height:32rpx;line-height:32rpx;text-align:center;font-size:22rpx;}

.bottombar .op2{width:60%;overflow:hidden;display:flex;}
.bottombar .tocart2{ flex:1;height: 80rpx;border-radius:10rpx;color: #fff; background: #fa938a; font-size: 28rpx;display:flex;flex-direction:column;align-items:center;justify-content:center;margin-right:10rpx;}
.bottombar .tobuy2{ flex:1; height: 80rpx;border-radius:10rpx;color: #fff; background: #df2e24; font-size:28rpx;display:flex;flex-direction:column;align-items:center;justify-content:center}


.title2{color: #666;line-height: 40rpx;margin-top: 10rpx;}
.content{background-color: #fff;border-radius: 8rpx;padding: 20rpx;margin-bottom: 20rpx;}
.content-pic{width: 200rpx;max-height: 400rpx;border-radius: 4rpx;overflow: hidden;}
.content-title{font-weight: bold;line-height: 50rpx;max-height: 100rpx;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:2;overflow:hidden;word-break: break-all;}
.content-title2{color: #666;line-height: 40rpx;margin-top: 5rpx;}

.choose-but-class{width: 94%;background: linear-gradient(90deg, #06D470 0%, #06D4B9 100%);color: #FFFFFF;font-size: 32rpx;font-weight: bold;padding: 24rpx;
	border-radius: 60rpx;position: fixed;bottom:10rpx;left: 50%;transform: translateX(-50%);margin-bottom: env(safe-area-inset-bottom);text-align: center;}
.calendar-view{width: 100%;position: relative;max-height: 60vh;padding-top: 30rpx;height: auto;overflow: hidden;padding-bottom: env(safe-area-inset-bottom);}
.areacss{min-width: 200rpx;text-align: center;line-height: 50rpx;border: 2rpx solid #f1f1f1;border-radius: 8rpx 8rpx ;overflow: hidden;white-space:nowrap;text-overflow: ellipsis;background-color: #fff;display:inline-block;margin-right: 10rpx;}
</style>