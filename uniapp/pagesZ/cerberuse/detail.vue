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
		
		<view class="swiper-container" v-if="isplay==0" >
			<swiper class="swiper" :indicator-dots="false" :autoplay="true" :interval="500000" @change="swiperChange">
				<block v-for="(item, index) in product.pics" :key="index">
					<swiper-item class="swiper-item">
						<view class="swiper-item-view"><image class="img" :src="item" mode="widthFix"/></view>
					</swiper-item>
				</block>
			</swiper>
			<view class="imageCount" v-if="(product.pics).length > 1">{{current+1}}/{{(product.pics).length}}</view>
			<view v-if="product.video" class="provideo" @tap="payvideo"><image :src="pre_url+'/static/img/video.png'"/><view class="txt">{{product.video_duration}}</view></view>
		</view>
	
		<view class="header"> 
			<view class="price_share">
				<view class="title">{{product.title}}</view>
				<view class="share" @tap="shareClick"><image class="img" :src="pre_url+'/static/img/share.png'"/><text class="txt">分享</text></view>
			</view>
			<view class="pricebox flex">
				<view class="price">
					<view class="f1" :style="{color:t('color1')}">
						{{product.price}}<text style="font-size:24rpx;font-weight:normal;padding-left:6rpx">元/小时</text>
					</view>
				</view>
				<!-- <view class="sales_stock">
					<view class="f1" >已售：{{product.sales}} </view>
				</view> -->
			</view>
			
			<view class="sellpoint" v-if="product.sellpoint">{{product.sellpoint}}</view>

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
		
	<!-- 	<view class="choosebox">
			<view v-if="!isfuwu" class="choosedate" @tap="chooseTime"   >
				<view class="f0">服务时间</view>
				<view class="f1 flex1">{{days}} {{selectDates}}</view>
				<image class="f2" src="/static/img/arrowright.png"/>
			</view>
		</view> -->
		
		
		<view class="shop" v-if="shopset.showjd==1">
			<image :src="business.logo" class="p1"/>
			<view class="p2 flex1">
				<view class="t1">{{business.name}}</view>
				<view class="t2">{{business.desc}}</view>
			</view>
			<button class="p4" :style="{background:'linear-gradient(90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'}" @tap="goto" :data-url="product.bid==0?'/pages/index/index':'/pagesExt/business/index?id='+product.bid" data-opentype="reLaunch">进入店铺</button>
		</view>
		<view class="detail_title"><view class="t1"></view><view class="t2"></view><view class="t0">详情</view><view class="t2"></view><view class="t1"></view></view>
		<view class="detail">
			<view v-html="product.content"></view>
		</view>
		
		

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
				<!-- <view class="item" @tap="addfavorite">
					<image class="img" :src="pre_url+'/static/img/shoucang.png'"/>
					<view class="t1">{{isfavorite?'已收藏':'收藏'}}</view>
				</view> -->
			</view>
			<view class="op">
				<view  class="tobuy flex-x-center flex-y-center" @tap="tobuy" :style="{background:t('color1')}"> <text v-if="opt.type ==1">立即预约</text> <text v-else>立即购买</text> </view>
			</view>
		</view>
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
						<!-- <view class="f1" @tap="sharemp" v-else-if="getplatform() == 'h5'">
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
			isfuwu:false,
		};
	},
  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.getdata();
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
		getdata:function(){
			var that = this;
			var id = this.opt.id || 0;
			that.loading = true;
			app.get('ApiCerberuse/detail', {id: id}, function (res) {
				that.loading = false;
				if (res.status == 0) {
					app.alert(res.msg);
					return;
				}
				that.textset = app.globalData.textset;
				var product = res.data;
				var pagecontent = product.content;
				that.product = product;	
				that.pagecontent = pagecontent;
				that.title = product.title;
				uni.setNavigationBarTitle({
					title: product.title
				});	
				that.datelist = res.datelist;
				that.daydate = res.daydate;
				that.kfurl = '/pages/kefu/index?bid='+product.bid;
				if(app.globalData.initdata.kfurl != ''){
					that.kfurl = app.globalData.initdata.kfurl;
				}
				if(that.business && that.business.kfurl){
					that.kfurl = that.business.kfurl;
				}
				that.loaded({title:product.name,pic:product.pic});
			});
		},
		swiperChange: function (e) {
			var that = this;
			that.current = e.detail.current
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
		  //检测预约时间是否可预约
		  that.loading = true;
		  app.get('ApiCerberuse/isgettime', { date: that.days,device_id:this.opt.id}, function (res) {
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
		  that.timeint = timeint
		  that.timeindex = index2
			//console.log(that.timelist);
		  that.starttime1 = that.timelist[index2].time
		  if(!that.days || that.days=='请选择服务时间'){ that.days = that.datelist[0].year + that.datelist[0].date }
		  that.selectDates = that.starttime1;
		},
		selectDate:function(e){
			var that=this
			if(that.timeindex >= 0 && that.timelist[that.timeindex].status==0){
					that.starttime1='';
			}
			if(!that.starttime1){
				app.error('请选择预约时间');return;
			}
			 this.timeDialogShow = false;
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
			that.timeDialogShow = true;
			that.timeIndex = -1;
			var curTopIndex = that.datelist[0];
			that.nowdate = that.datelist[that.curTopIndex].year+that.datelist[that.curTopIndex].date;
			that.loading = true;
			app.get('ApiCerberuse/isgettime', { date: that.nowdate,proid:this.opt.id}, function (res) {
			  that.loading = false;
			  that.timelist = res.data;
			})
			
		},	
		hidetimeDialog: function() {
			this.timeDialogShow = false;
		},
		
		showfuwudetail: function () {
			this.showfuwudialog = true;
		},
		hidefuwudetail: function () {
			this.showfuwudialog = false
		},
		sharemp:function(){
			app.error('点击右上角发送给好友或分享到朋友圈');
			this.sharetypevisible = false
		},
		shareapp:function(){
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
						sharedata.href = app.globalData.pre_url +'/h5/'+app.globalData.aid+'.html#/activity/yuyue/product?scene=id_'+that.product.id+'-pid_' + app.globalData.mid;
						sharedata.imageUrl = that.product.pic;
						var sharelist = app.globalData.initdata.sharelist;
						if(sharelist){
							for(var i=0;i<sharelist.length;i++){
								if(sharelist[i]['indexurl'] == '/activity/yuyue/product'){
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
			app.goto('buy?proid=' + this.product.id+'&type='+that.opt.type);
		},
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

.choose{ display:flex;align-items:center;justify-content: center; margin: auto; height: 88rpx; line-height: 88rpx;padding: 0 3%; color: #333; border-bottom:1px solid #eee }
.choose .f0{color:#555;font-weight:bold;height:32rpx;font-size:24rpx;padding-right:30rpx;display:flex;justify-content:center;align-items:center}
.choose .f2{ width: 32rpx; height: 32rpx;}


.choosedate{ display:flex;align-items:center;ustify-content: center;   margin:auto; height: 88rpx; line-height: 88rpx;padding: 0 3%; color: #333; }
.choosedate .f0{color:#555;font-weight:bold;height:32rpx;font-size:24rpx;padding-right:30rpx;display:flex;justify-content:center;align-items:center}
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
.popup__modal{width: 100%;position: absolute;bottom: 0;color: #3d4145;overflow-x: hidden;overflow-y: hidden;opacity:1;padding-bottom:20rpx;background: #fff;border-radius:20rpx 20rpx 0 0;z-index:12;min-height:600rpx;max-height:1000rpx;}
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
.order-tab{ }
.order-tab2{display:flex;width:auto;min-width:100%;overflow-x: scroll;}
.order-tab2 .item{width:auto;font-size:28rpx;font-weight:bold;text-align: center; color:#999999;overflow: hidden;flex-shrink:0;flex-grow: 1; display: flex; flex-direction: column; justify-content: center; align-items: center; width: 20%;}
.order-tab2 .item .datetext{ line-height: 60rpx; height:60rpx;}
.order-tab2 .item .datetext2{ line-height: 60rpx; height:60rpx;font-size: 22rpx;}
.order-tab2 .on{color:#222222;}
.order-tab2 .after{display:none;margin-left:-10rpx;bottom:5rpx;height:6rpx;border-radius:1.5px;width:70rpx}
.order-tab2 .on .after{display:block}
.daydate{ padding:20rpx; flex-wrap: wrap; overflow-y: scroll; height:400rpx; }
.daydate .date{ width:20%;text-align: center;line-height: 60rpx;height: 60rpx; margin-top: 30rpx;}
.daydate .on{ background:red; color:#fff;}
.daydate .hui{ border:1px solid #f0f0f0; background:#f0f0f0;border-radius: 5rpx;}
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

</style>