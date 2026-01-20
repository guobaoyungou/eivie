<template>
<view>
	<block v-if="isload">
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
				<view class="title">{{product.name}}</view>
				<view class="share" @tap="shareClick"><image class="img" :src="pre_url+'/static/img/share.png'"/><text class="txt">分享</text></view>
			</view>
			<view class="pricebox flex">
				<view class="sellpoint">课程价格：￥{{product.sell_price}}</view>
			</view>
			<view class="sellpoint" v-if="product.sellpoint">{{product.sellpoint}}</view>
			<view class="sellpoint" v-if="product.starttime && product.endtime">课程时间：{{product.yy_date}} {{product.starttime}} ~ {{product.endtime}}</view>
		</view>
		<view class="module">
			<view class="module_data" @tap="goto" :data-url="'workerinfo?id='+workerinfo.id" v-if="workerinfo">
				<image :src="workerinfo.headimg" class="module_img" alt=""/>
				<view class="module_content">
					<view class="module_title">{{workerinfo.realname}} {{workerinfo.dengji||''}}</view>
					<view class="module_item">{{workerinfo.desc}}</view>
				</view>
			</view>
			<view class="module_num">
				<view class="module_lable">
					<view>当前</view>
					<view>预约</view>
				</view>
				<view class="module_view">
					<block v-for="(item2,index2) in yyorderlist">
						<image :src="item2.headimg"/>
					</block>
				</view>
				<!-- 一对多模式 （默认） -->
				<view v-if="!product.yuyue_model || product.yuyue_model == 1">
					<view class="module_tag" v-if="product.leftnum > 0">剩余{{product.leftnum}}个名额</view>
					<view class="module_tag module_end" v-else>满员</view>
				</view>
			</view>
		</view>
		
		<view class="detail_title"><view class="t1"></view><view class="t2"></view><view class="t0">课程介绍</view><view class="t2"></view><view class="t1"></view></view>
		<view class="detail">
			<dp :pagecontent="pagecontent"></dp>
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
				<view class="item" @tap="addfavorite">
					<image class="img" :src="pre_url+'/static/img/shoucang.png'"/>
					<view class="t1">{{isfavorite?'已收藏':'收藏'}}</view>
				</view>
			</view>
			<view class="op">
					<view class="tobuy flex-x-center flex-y-center" @tap="buydialogShow" :style="{background:t('color1')}" v-if="product.yuyue_model && product.yuyue_model == 2">立即预约</view>
					<view class="tobuy flex-x-center flex-y-center" :style="{background:'#aaa'}" v-else-if="product.isend">已结束</view>
					<view class="tobuy flex-x-center flex-y-center" @tap="tobuy" :style="{background:t('color1')}" v-else>立即预约</view>
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
		<!-- 一对一陪教模式 规格弹窗 -->
		<view :hidden="buydialogHidden">
			<view class="buydialog-mask">
				<view class="buydialog" :class="menuindex>-1?'tabbarbot':'notabbarbot'">
					<view class="close" @tap="buydialogChange">
						<image :src="pre_url+'/static/img/close.png'" class="image"></image>
					</view>
					<view class="title">
						<image :src="guigelist[ks].pic?guigelist[ks].pic:product.pic" class="img" @tap="previewImage" :data-url="guigelist[ks].pic?guigelist[ks].pic:product.pic"></image>
						<view class="price"><text class="t1">￥</text>{{guigelist[ks].sell_price}}</view>
						<view class="choosename">已选规格: {{guigelist[ks].name}}</view>
					</view>
		
					<view v-for="(item, index) in guigedata" :key="index" class="guigelist flex-col">
						<view class="name">{{item.title}}</view>
						<view class="item flex flex-y-center">
							<block v-for="(item2, index2) in item.items" :key="index2">
								<view :data-itemk="item.k" :data-idx="item2.k" :class="'item2 ' + (ggselected[item.k]==item2.k ? 'on':'')" @tap="ggchange">{{item2.title}}</view>
							</block>
						</view>
					</view>
					<view class="buynum flex flex-y-center">
						<view class="flex1">购买数量：</view>
						<view class="addnum">
							<view class="minus"><image class="img" :src="pre_url+'/static/img/cart-minus.png'" @tap="gwcminus"/></view>
							<input class="input" type="number" :value="gwcnum" @input="gwcinput" @blur='gwcinputblur'></input>
							<view class="plus"><image class="img" :src="pre_url+'/static/img/cart-plus.png'" @tap="gwcplus"/></view>
						</view>
					</view>
					<view class="op">
						<button class="tobuy" :style="{background:t('color2')}" @tap="tobuy">确定</button>
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
			pre_url:app.globalData.pre_url,
			isload:false,
			isfavorite: false,
			current: 0,
			isplay: 0,
			business: "",
			product: [],
			pagecontent: "",
			title: "",
			sharepic: "",
			sharetypevisible: false,
			showposter: false,
			posterpic: "",
			scrolltopshow: false,
			kfurl:'',
			workerinfo:{},
			yyorderlist:[],
			timeDialogShow:false,
			dateIndex:0,
			buydialogHidden: true,
			ggselected:[],
			guigedata:[],
			guigelist:[],
			nowguige:[],
			ks: '',
			gwcnum: 1,
		};
	},
  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		if(this.opt && this.opt.dateIndex) this.dateIndex = this.opt.dateIndex;
		this.getdata();
  },
	onPullDownRefresh: function () {
		this.getdata();
	},
	onShareAppMessage:function(shareOption){
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
			app.get('ApiYueke/product', {id:id,dateIndex:that.dateIndex}, function (res) {
				that.loading = false;
				if (res.status == 0) {
					app.alert(res.msg);
					return;
				}
				var product = res.product;
				var pagecontent = JSON.parse(product.detail);
				that.business = res.business;
				that.product = product;
				that.pagecontent = pagecontent;
				that.title = product.name;
				that.isfavorite = res.isfavorite;
				that.workerinfo = res.workerinfo;
				that.yyorderlist = res.yyorderlist;
				that.sharepic = product.pics[0];
				if(res.ggselected && res.guigedata && res.guigelist && res.ks){
					that.ggselected = res.ggselected;
					that.guigedata = res.guigedata;
					that.guigelist = res.guigelist;
					that.ks = res.ks;
					that.nowguige = that.guigelist[that.ks];
					if(that.nowguige.limit_start > 0){
						that.gwcnum = that.nowguige.limit_start;
					}
				}
				
				uni.setNavigationBarTitle({
					title: product.name
				});
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
		//收藏操作
		addfavorite: function () {
			var that = this;
			var proid = that.product.id;
			app.post('ApiYueke/addfavorite', {proid: proid,type: 'yueke'}, function (data) {
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
			app.post('ApiYueke/getposter', {proid: that.product.id}, function (data) {
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
						sharedata.href = app.globalData.pre_url +'/h5/'+app.globalData.aid+'.html#/pagesExt/yueke/product?scene=id_'+that.product.id+'-pid_' + app.globalData.mid;
						sharedata.imageUrl = that.product.pic;
						var sharelist = app.globalData.initdata.sharelist;
						if(sharelist){
							for(var i=0;i<sharelist.length;i++){
								if(sharelist[i]['indexurl'] == '/pagesExt/yueke/product'){
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
			/* 预约模式 一对一陪教模式 */
			if(that.product.yuyue_model && that.product.yuyue_model == 2){
				var ks = that.ks;
				var proid = that.product.id;
				var ggid = that.guigelist[ks].id;
				var stock = that.guigelist[ks].stock;
				var num = that.gwcnum;
				if (num < 1) num = 1;
				var prodata = ggid + ',' + num;
				app.goto('buy?proid=' + this.product.id +'&dateIndex='+this.dateIndex+'&prodata='+ prodata);
			}else{
				app.goto('buy?proid=' + this.product.id +'&dateIndex='+this.dateIndex);
			}
		},
		buydialogShow: function (e) {
		  this.buydialogHidden = !this.buydialogHidden;
		},
		buydialogChange: function (e) {
		  this.buydialogHidden = !this.buydialogHidden;
		},
		//选择规格
		ggchange: function (e) {
		  var idx = e.currentTarget.dataset.idx;
		  var itemk = e.currentTarget.dataset.itemk;
		  var ggselected = this.ggselected;
		  ggselected[itemk] = idx;
		  var ks = ggselected.join(',');
		  this.ggselected = ggselected;
		  this.ks = ks;
			this.nowguige = this.guigelist[this.ks];
			if(this.nowguige.limit_start == 0) {
				this.gwcnum = 1
			}else{
				this.gwcnum = this.nowguige.limit_start;
			}
		},
		//加
		gwcplus: function (e) {
		  var gwcnum = this.gwcnum + 1;
		  var ggselected = this.ks;
			if (this.product.perlimitdan > 0 && gwcnum > this.product.perlimitdan) {
				app.error('每单限购'+this.product.perlimitdan+'件');
				return 1;
			}
		  this.gwcnum = this.gwcnum + 1
		},
		//减
		gwcminus: function (e) {
		  var gwcnum = this.gwcnum - 1;
		  var ggselected = this.ks;
		  if (gwcnum <= 0) {
		    return;
		  }
			
			if(this.nowguige.limit_start > 0) {
				if (gwcnum <= this.nowguige.limit_start - 1) {
					if(this.nowguige.limit_start > 1){
						app.error('该规格' + this.nowguige.limit_start + '件起售');
					}
					return;
				}
			}
			
		  this.gwcnum = this.gwcnum - 1
		},
		//输入
		gwcinput: function (e) {
		  var ggselected = this.ks;
		  var gwcnum = parseInt(e.detail.value);
		  if (gwcnum < 1) return 1;
		
		  this.gwcnum = gwcnum;
		},
		gwcinputblur(e){
			var ks = this.ks;
			var gwcnum = parseInt(e.detail.value);
			if (gwcnum < 1) return 1;
			if(this.nowguige.limit_start > 0) {
				if (gwcnum <= this.nowguige.limit_start - 1) {
					if(this.nowguige.limit_start > 1){
						app.error('该规格' + this.nowguige.limit_start + '件起售');
					}
					gwcnum = this.nowguige.limit_start;
				}
			}
			this.gwcnum = gwcnum;
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
.order-tab2{display:flex;width:auto;min-width:100%}
.order-tab2 .item{width:auto;font-size:28rpx;font-weight:bold;text-align: center; color:#999999;overflow: hidden;flex-shrink:0;flex-grow: 1; display: flex; flex-direction: column; justify-content: center; align-items: center;}
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


.module{position: relative;width: 700rpx;padding: 30rpx;box-sizing: border-box;border-radius: 20rpx;margin: 0 auto 30rpx auto;background: #fff;}
.module_data{display: flex;}
.module_img{height: 130rpx;width: 130rpx;margin-right: 30rpx;}
.module_content{flex: 1;}
.module_btn{height: 65rpx;padding: 0 40rpx;color: #fff;font-size: 28rpx;display: flex;align-items: center;justify-content: center;border-radius: 100rpx;background: #0993fe;}
.module_title{font-size: 28rpx;color: #333;}
.module_item{margin-top: 10rpx;color: #999;display: flex;align-items: center;font-size: 24rpx;}
.module_time{padding: 0 10rpx;height: 35rpx;line-height: 33rpx;font-size: 22rpx;margin-left: 20rpx;color: #d55c5f;border: 1rpx solid #d55c5f;}
.module_num{display: flex;align-items: center;margin-top: 20rpx;}
.module_lable{font-size: 24rpx;color: #666;line-height: 24rpx;border-right: 1px solid #e0e0e0;padding: 0 15rpx 0 0;margin-right: 15rpx;}
.module_view{display: flex;flex: 1;align-items: center;}
.module_view image{height: 60rpx;width: 60rpx;border-radius: 100rpx;margin-right: 10rpx;}
.module_tag{height: 50rpx;background: #fefae8;color: #b37e4b;font-size: 24rpx;padding: 0 10rpx;line-height: 50rpx;}
.module_end{color: #999;background: #f0f0f0;}

.buydialog-mask{ position: fixed; top: 0px; left: 0px; width: 100%; background: rgba(0,0,0,0.5); bottom: 0px;z-index:10}
.buydialog{ position: fixed; width: 100%; left: 0px; bottom: 0px; background: #fff;z-index:11;border-radius:20rpx 20rpx 0px 0px}
.buydialog .close{ position: absolute; top: 0; right: 0;padding:20rpx;z-index:12}
.buydialog .close .image{ width: 30rpx; height:30rpx; }
.buydialog .title{ width: 94%;position: relative; margin: 0 3%; padding:20rpx 0px; border-bottom:0; height: 190rpx;}
.buydialog .title .img{ width: 160rpx; height: 160rpx; position: absolute; top: 20rpx; border-radius: 10rpx; border: 0 #e5e5e5 solid;background-color: #fff}
.buydialog .title .price{ padding-left:180rpx;width:100%;font-size: 36rpx;height:70rpx; color: #FC4343;overflow: hidden;}
.buydialog .title .price .t1{ font-size:26rpx}
.buydialog .title .price .t2{ font-size:26rpx;text-decoration:line-through;color:#aaa}
.buydialog .title .choosename{ padding-left:180rpx;width: 100%;font-size: 24rpx;height: 42rpx;line-height:42rpx;color:#888888}
.buydialog .title .stock{ padding-left:180rpx;width: 100%;font-size: 24rpx;height: 42rpx;line-height:42rpx;color:#888888}

.buydialog .guigelist{ width: 94%; position: relative; margin: 0 3%; padding:0px 0px 10px 0px; border-bottom: 0; }
.buydialog .guigelist .name{ height:70rpx; line-height: 70rpx;}
.buydialog .guigelist .item{ font-size: 30rpx;color: #333;flex-wrap:wrap}
.buydialog .guigelist .item2{ height:60rpx;line-height:60rpx;margin-bottom:4px;border:0; border-radius:4rpx; padding:0 40rpx;color:#666666; margin-right: 10rpx; font-size:26rpx;background:#F4F4F4}
.buydialog .guigelist .on{color:#FC4343;background:rgba(252,67,67,0.1);font-weight:bold}
.buydialog .buynum{ width: 94%; position: relative; margin: 0 3%; padding:10px 0px 10px 0px; }
.buydialog .addnum {font-size: 30rpx;color: #666;width:auto;display:flex;align-items:center}
.buydialog .addnum .plus {width:48rpx;height:36rpx;background:#F6F8F7;display:flex;align-items:center;justify-content:center}
.buydialog .addnum .minus {width:48rpx;height:36rpx;background:#F6F8F7;display:flex;align-items:center;justify-content:center}
.buydialog .addnum .img{width:24rpx;height:24rpx}
.buydialog .addnum .input{flex:1;width:70rpx;border:0;text-align:center;color:#2B2B2B;font-size:24rpx}
.buydialog .op{width:90%;margin:20rpx 5%;border-radius:36rpx;overflow:hidden;display:flex;margin-top:100rpx;}
.buydialog .addcart{flex:1;height:72rpx; line-height: 72rpx;color: #fff; border-radius: 0px; border: none; font-size:28rpx;font-weight:bold}
.buydialog .tobuy{flex:1;height: 72rpx; line-height: 72rpx;color: #fff; border-radius: 0px; border: none;}
.buydialog .nostock{flex:1;height: 72rpx; line-height: 72rpx; background:#aaa; color: #fff; border-radius: 0px; border: none;}
</style>