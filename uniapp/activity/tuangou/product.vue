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
		<view style="position:fixed;top:15vh;left:20rpx;z-index:9;background:rgba(0,0,0,0.6);border-radius:20rpx;color:#fff;padding:0 10rpx" v-if="bboglist.length>0">
			<swiper style="position:relative;height:54rpx;width:350rpx;" :autoplay="true" :interval="5000" :vertical="true">
				<swiper-item v-for="(item, index) in bboglist" :key="index" @tap="goto" :data-url="'product?id=' + item.proid" class="flex-y-center">
					<image :src="item.headimg" style="width:40rpx;height:40rpx;border:1px solid rgba(255,255,255,0.7);border-radius:50%;margin-right:4px"/>
					<div style="width:300rpx;white-space:nowrap;overflow:hidden;text-overflow: ellipsis;font-size:22rpx">{{item.nickname}} {{item.showtime}}购买了该商品</div>
				</swiper-item>
			</swiper>
		</view>
		<view class="swiper-container" v-if="isplay==0">
			<swiper class="swiper" :indicator-dots="false" :autoplay="true" :interval="5000" @change="swiperChange" :style="{ height: swiperHeight + 'px' }">
				<block v-for="(item, index) in product.pics" :key="index">
					<swiper-item class="swiper-item">
						<view class="swiper-item-view" :id="'content-wrap' + index" :style="{ height: swiperHeight + 'px' }">
							<image class="img" :src="item" mode="widthFix" @load="loadImg"  />
						</view>
					</swiper-item>
				</block>
			</swiper>
			<view class="imageCount">{{current+1}}/{{(product.pics).length}}</view>
			<view v-if="product.video" class="provideo" @tap="payvideo"><image :src="pre_url+'/static/img/video.png'"/><view class="txt">{{product.video_duration}}</view></view>
		</view>
		<view class="videobox" v-if="isplay==1">
			<video autoplay="true" class="video" id="video" :src="product.video"></video>
			<view class="parsevideo" @tap="parsevideo">退出播放</view>
		</view>

		<view :class="[tuangou_status == 2 ? 'seckill-title-end':'','seckill_title']">
			<image :src="pre_url+'/static/img/tghd.png'" class="f0"/>
			<view class="f1">
				<view class="t1">
					<block v-if="showprice_dollar && product.usd_sellprice">
						<text style="font-size:24rpx">$</text>{{product.usd_sellprice}}
						<text style="margin-left: 6rpx;font-size: 36rpx;"><text style="font-size:24rpx">￥</text>{{product.min_price}}</text>
					</block>
					<block v-else>
							<text style="font-size:24rpx">￥</text>{{product.min_price}}
					</block>
					<text class="x2">￥{{product.market_price}}</text>
				</view>
				<view class="t2" v-if="tuangou_status==0">团购未开始</view>
				<view class="t2" v-if="tuangou_status==1">火爆团购中</view>
				<view class="t2" v-if="tuangou_status==2">团购已结束</view>
			</view>
			<view class="f3" v-if="tuangou_status != 2">
				<view class="t1">距团购{{tuangou_status==0?'开始':'结束'}}还剩</view>
				<view class="t2" id="djstime"><text class="djsspan">{{djshour}}</text> : <text class="djsspan">{{djsmin}}</text> : <text class="djsspan">{{djssec}}</text></view>
			</view>
		</view>

		<view class="header"> 
			<view class="price_share">
				<view class="price">
					<view class="f1" :style="{color:t('color1')}">
						<block v-if="showprice_dollar && product.usd_sellprice">
							<text style="font-size:24rpx">$</text>{{product.usd_sellprice}}
							<text style="margin-left: 6rpx;font-size: 36rpx;"><text style="font-size:28rpx">￥</text>{{product.sell_price}}</text>
						</block>
						<block v-else>
								<text style="font-size:36rpx">￥</text>{{product.sell_price}}
						</block>
						
						</view>
					<view class="f2" v-if="product.market_price*1 > product.sell_price*1">￥{{product.market_price}}</view>
				</view>
				<view class="share" @tap="shareClick"><image class="img" :src="pre_url+'/static/img/share.png'"/><text class="txt">分享</text></view>
			</view>
			<view class="title">{{product.name}}</view>
			<view class="sellpoint" v-if="product.sellpoint"  @tap="copy1" >{{product.sellpoint}}</view>
			<view class="sales_stock">
				<view class="f1">已参团：{{product.buynum}}人次</view>
				<view class="f2">库存：{{product.stock}}</view>
			</view>
		</view>
		
		<view class="tg_pricedata">
			<view class="tg_pricetitle">团购价格</view>
			<view class="tg_price flex" v-for="(item, index) in product.pricedata" :key="index">
				<view class="t1">满{{item.num}}人次</view>	
				<block v-if="showprice_dollar && item.usd_money">
					<view class="t2 flex1" style="color:#e94745;margin-left:10rpx">
					<text style="font-size:24rpx">$</text>{{item.usd_money}} {{item.money}}元</view>
				</block>
				<block v-else>
					<view class="t2 flex1" >{{item.money}}元</view>
				</block>
				
				<view class="t3"><text style="color:#f50" v-if="product.buynum>=item.num">已达成</text><text style="color:#333" v-else>还差{{item.num-product.buynum}}人次</text></view>
			</view>
			<view style="padding:5px 0;font-size:22rpx;color:red">注：商品金额并非实际成交金额，活动结束后根据参团人数计算达成的最低团购价格，多付的金额将退还</view>
		</view>

		<!-- <view class="choose" @tap="buydialogChange" data-btntype="2">
			<view class="f1 flex1">请选择商品规格及数量</view>
			<image class="f2" :src="pre_url+'/static/img/arrowright.png'"/>
		</view> -->
		
		<view class="cuxiaodiv" v-if="product.givescore > 0">
			<view class="cuxiaopoint">
				<view class="f0">送{{t('积分')}}</view>
				<view class="f1" style="font-size:26rpx">购买可得{{t('积分')}}{{product.givescore}}个</view>
			</view>
		</view>

		<view class="cuxiaodiv" v-if="cuxiaolist.length>0 || couponlist.length>0">
			<view class="cuxiaopoint" v-if="cuxiaolist.length>0">
				<view class="f0">促销</view>
				<view class="f1">
					<view v-for="(item, index) in cuxiaolist" :key="index" class="t" :style="{background:'rgba('+t('color1rgb')+',0.1)',color:t('color1')}"><text class="t0">{{item.tip}}</text><text class="t1">{{item.name}}</text></view>
				</view>
				<view class="f2" @tap="showcuxiaodetail">
					<image class="img" :src="pre_url+'/static/img/arrowright.png'"/>
				</view>
			</view>
			<view class="cuxiaopoint" v-if="couponlist.length>0">
				<view class="f0">优惠</view>
				<view class="f1">
					<view v-for="(item, index) in couponlist" :key="index" class="t" :style="{background:'rgba('+t('color1rgb')+',0.1)',color:t('color1')}"><text class="t0" style="padding:0 6px">券</text><text class="t1">{{item.name}}</text></view>
				</view>
				<view class="f2" @tap="showcuxiaodetail">
					<image class="img" :src="pre_url+'/static/img/arrowright.png'"/>
				</view>
			</view>
		</view>
		<view v-if="showcuxiaodialog" class="popup__container">
			<view class="popup__overlay" @tap.stop="hidecuxiaodetail"></view>
			<view class="popup__modal coupon-modal">
					<view class="popup__title">
						<text class="popup__title-text">优惠促销</text>
						<image :src="pre_url+'/static/img/close.png'" class="popup__close" style="width:36rpx;height:36rpx" @tap.stop="hidecuxiaodetail"/>
					</view>
					<view class="popup__content">
						<view v-for="(item, index) in cuxiaolist" :key="index" class="service-item">
							<view class="suffix">
								<view class="type-name"><text style="border-radius:4px;border:1px solid #f05423;color: #ff550f;font-size:20rpx;padding:2px 5px">{{item.tip}}</text> <text style="color:#333;margin-left:20rpx">{{item.name}}</text></view>
							</view>
						</view>
						<couponlist :couponlist="couponlist" @getcoupon="getcoupon"></couponlist>
					</view>
			</view>
		</view>
		<view class="commentbox" v-if="tuangouset.comment==1 && commentcount > 0">
			<view class="title">
				<view class="f1">评价({{commentcount}})</view>
				<view class="f2" @tap="goto" :data-url="'commentlist?proid=' + product.id">好评度 <text :style="{color:t('color1')}">{{product.comment_haopercent}}%</text><image style="width:32rpx;height:32rpx;" :src="pre_url+'/static/img/arrowright.png'"/></view>
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
		
		<view class="shop" v-if="tuangouset.showjd==1">
			<image :src="business.logo" class="p1"/>
			<view class="p2 flex1">
				<view class="t1">{{business.name}}</view>
				<view class="t2">{{business.desc}}</view>
			</view>
			<button class="p4" :style="{background:'linear-gradient(90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'}" @tap="goto" :data-url="product.bid==0?'/pages/index/index':'/pagesExt/business/index?id='+product.bid" data-opentype="reLaunch">进入店铺</button>
		</view>
		<view class="detail_title"><view class="t1"></view><view class="t2"></view><view class="t0">商品描述</view><view class="t2"></view><view class="t1"></view></view>
		<view class="detail">
			<dp :pagecontent="pagecontent"></dp>
		</view>

		<view style="width:100%;height:140rpx;"></view>
		<view class="bottombar flex-row" :class="menuindex>-1?'tabbarbot':'notabbarbot'" v-if="product.status==1">
			<view class="f1">
				<view class="item" @tap="goto" :data-url="kfurl" v-if="kfurl!='contact::'">
					<image class="img" :src="pre_url+'/static/img/kefu.png'"/>
					<view class="t1">客服</view>
				</view>
				<button class="item" v-else open-type="contact" show-message-card="true">
					<image class="img" :src="pre_url+'/static/img/kefu.png'"/>
					<view class="t1">客服</view>
				</button>
				<view class="item flex1" @tap="shareClick">
					<image class="img" :src="pre_url+'/static/img/share2.png'"/>
					<view class="t1">分享</view> 
				</view>
				<view class="item" @tap="addfavorite">
					<image class="img" :src="pre_url+'/static/img/shoucang.png'"/>
					<view class="t1">{{isfavorite?'已收藏':'收藏'}}</view>
				</view>
			</view>
			<view class="op">
				<view class="tobuy flex-x-center flex-y-center" :style="{background:t('color1')}" @tap="buydialogChange" data-controller="ApiTuangou" data-btntype="2" v-if="tuangou_status==1">立即团购</view>
				<view class="tobuy flex-x-center flex-y-center" style="background:#ccc" v-else>{{tuangou_status == 0 ? '未开始':'已结束'}}</view>
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

		<buydialog-tuangou v-if="buydialogShow" :proid="product.id" :btntype="btntype" @buydialogChange="buydialogChange" :menuindex="menuindex" controller="ApiTuangou"></buydialog-tuangou>
		<scrolltop :isshow="scrolltopshow"></scrolltop>
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
			initdata:{},
			pre_url:app.globalData.pre_url,
			
			tuangou_status:1,
			nowtime:0,
			djshour:'00',
			djsmin:'00',
			djssec:'00',
			buydialogShow: false,
			btntype:1,
			isfavorite: false,
			current: 0,
			isplay: 0,
			showcuxiaodialog: false,
			business: "",
			product: [],

			cartnum: "",
			commentlist: "",
			commentcount: "",

			cuxiaolist: "",

			couponlist: "",

			pagecontent: "",

			guigelist: "",

			guigedata: "",

			sysset:{},

			tuangouset: "",

			title: "",

			bboglist: "",

			sharepic: "",

			sharetypevisible: false,
			showposter: false,
			posterpic: "",
			scrolltopshow: false,
			kfurl:'',
			showprice_dollar:false,
			swiperHeight: '',
		};

	},
  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.getdata();
  },
	onPullDownRefresh: function () {
		this.getdata();
	},
	onShareAppMessage:function(){
		return this._sharewx({title:this.product.name,pic:this.product.pic});
	},
	onShareTimeline:function(){
		var sharewxdata = this._sharewx({title:this.product.name,pic:this.product.pic});
		var query = (sharewxdata.path).split('?')[1]+'&seetype=circle';
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
			app.post('ApiTuangou/product', {id: id}, function (res) {
				that.loading = false;
				if (res.status == 0) {
					app.alert(res.msg);
					return;
				}
				var product = res.product;
				var sysset = res.sysset;
				var pagecontent = JSON.parse(product.detail);
				that.business = res.business;
				that.product = product;
				that.cartnum = res.cartnum;
				that.commentlist = res.commentlist;
				that.commentcount = res.commentcount;
				that.cuxiaolist = res.cuxiaolist;
				that.couponlist = res.couponlist;
				that.pagecontent = pagecontent;
				that.sysset = sysset;
				that.tuangouset = res.tuangouset;
				that.title = product.name;
				that.isfavorite = res.isfavorite;
				that.oglist = res.oglist;
				that.sharepic = product.pics[0];
				that.nowtime = res.nowtime;
				that.showprice_dollar = res.showprice_dollar
				that.getdjs();
				clearInterval(interval);
				interval = setInterval(function(){
					that.nowtime = that.nowtime+1;
					that.getdjs();
				},1000)
				uni.setNavigationBarTitle({
					title: product.name
				});
				that.kfurl = '/pages/kefu/index?bid='+res.product.bid;
				if(app.globalData.initdata.kfurl != ''){
					that.kfurl = app.globalData.initdata.kfurl;
				}
				if(that.business && that.business.kfurl){
					that.kfurl = that.business.kfurl;
				}
				that.loaded({title:res.product.name,pic:res.product.pic});
			});
		},
		getdjs:function(){
			var that = this
			var nowtime = that.nowtime*1;
			var starttime = that.product.starttime*1;
			var endtime = that.product.endtime*1;
			if(endtime < nowtime){ //已结束
				that.tuangou_status = 2
				that.djshour = '00';
				that.djsmin = '00';
				that.djssec = '00';
			}else{
				if(starttime > nowtime){ //未开始
					that.tuangou_status = 0
					var totalsec = starttime - nowtime;
				}else{ //进行中
					that.tuangou_status = 1
					var totalsec = endtime - nowtime;
				}
				var houer = Math.floor(totalsec/3600);
				var min = Math.floor((totalsec - houer *3600)/60);
				var sec = totalsec - houer*3600 - min*60
				var djs = (houer<10?'0':'')+houer+'时'+(min<10?'0':'')+min+'分'+(sec<10?'0':'')+sec+'秒';
				var djshour = (houer<10?'0':'')+houer
				var djsmin = (min<10?'0':'')+min
				var djssec = (sec<10?'0':'')+sec
				that.djshour = djshour;
				that.djsmin = djsmin;
				that.djssec = djssec;
			}
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
				this.btntype = e.currentTarget.dataset.btntype
			}
			this.buydialogShow = !this.buydialogShow;
		},

		//收藏操作

		addfavorite: function () {

			var that = this;

			var proid = that.product.id;

			app.post('ApiTuangou/addfavorite', {proid: proid,type: 'tuangou'}, function (data) {

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
			app.post('ApiTuangou/getposter', {proid: that.product.id}, function (data) {
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
		sharemp:function(){
			app.error('点击右上角发送给好友或分享到朋友圈');
			this.sharetypevisible = false
		},
		shareapp:function(){
			var that = this;
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
						sharedata.href = app.globalData.pre_url +'/h5/'+app.globalData.aid+'.html#/activity/tuangou/product?scene=id_'+that.product.id+'-pid_' + app.globalData.mid;
						sharedata.imageUrl = that.product.pic;
						var sharelist = app.globalData.initdata.sharelist;
						if(sharelist){
							for(var i=0;i<sharelist.length;i++){
								if(sharelist[i]['indexurl'] == '/activity/tuangou/product'){
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
		copy1: function(e) {
			var that=this
			var text = that.product.name+'\n\n'+that.product.sellpoint;
			uni.setClipboardData({
				data: text,
				success: function () {
					getApp().error('复制成功')
				}
			});
		},
	}

};
</script>
<style>
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

.swiper-container{position:relative}
.swiper {width: 100%;height: 750rpx;overflow: hidden;}
.swiper-item-view{width: 100%;height: 750rpx;}
.swiper .img {width: 100%;height: 750rpx;overflow: hidden;}

.imageCount {width:100rpx;height:50rpx;background-color: rgba(0, 0, 0, 0.3);border-radius:40rpx;line-height:50rpx;color:#fff;text-align:center;font-size:26rpx;position:absolute;right:13px;bottom:20rpx;}

.provideo{background:rgba(255,255,255,0.7);width:160rpx;height:54rpx;padding:0 20rpx 0 4rpx;border-radius:27rpx;position:absolute;bottom:30rpx;left:50%;margin-left:-80rpx;display:flex;align-items:center;justify-content:space-between}
.provideo image{width:50rpx;height:50rpx;}
.provideo .txt{flex:1;text-align:center;padding-left:10rpx;font-size:24rpx;color:#333}

.videobox{width:100%;height:750rpx;text-align:center;background:#000}
.videobox .video{width:100%;height:650rpx;}
.videobox .parsevideo{margin:0 auto;margin-top:20rpx;height:40rpx;line-height:40rpx;color:#333;background:#ccc;width:140rpx;border-radius:25rpx;font-size:24rpx}

.seckill_title{ width:100%;height:110rpx;background: linear-gradient(90deg, #FF3143 0%, #FD6647 100%);display:flex;align-items:center;}
.seckill-title-end{background: #ccc;}
.seckill_title .f0{width:88rpx;height:88rpx;margin-left:20rpx}
.seckill_title .f1{flex:1;padding:10rpx 20rpx;display:flex;flex-direction:column;}
.seckill_title .f1 .t1{font-size:40rpx;color:#fff;line-height:50rpx}
.seckill_title .f1 .t1 .x2{padding-left:8rpx;font-size:26rpx;color:#fff;text-decoration:line-through}
.seckill_title .f1 .t2{color:#fff;font-size:22rpx}
.seckill_title .f3{width:250rpx;height:110rpx;background:#FFDBDF;color:#333;display:flex;flex-direction:column;align-items:center;justify-content:center}
.seckill_title .f3 .t2{color:#FF3143}
.seckill_title .djsspan{font-size:22rpx;border-radius:8rpx;background:#FF3143;color:#fff;text-align:center;padding:4rpx 8rpx;margin:0 4rpx}


.header {width: 100%;padding: 20rpx 3%;background: #fff;}
.header .price_share{width:100%;height:100rpx;display:flex;align-items:center;justify-content:space-between}
.header .price_share .price{display:flex;align-items:flex-end}
.header .price_share .price .f1{font-size:50rpx;color:#51B539;font-weight:bold}
.header .price_share .price .f2{font-size:26rpx;color:#C2C2C2;text-decoration:line-through;margin-left:30rpx;padding-bottom:5px}
.header .price_share .share{display:flex;flex-direction:column;align-items:center;justify-content:center}
.header .price_share .share .img{width:32rpx;height:32rpx;margin-bottom:2px}
.header .price_share .share .txt{color:#333333;font-size:20rpx}
.header .title {color:#000000;font-size:32rpx;line-height:42rpx;font-weight:bold;}
.header .sales_stock{display:flex;justify-content:space-between;height:60rpx;line-height:60rpx;margin-top:30rpx;font-size:24rpx;color:#777777}
.header .sellpoint{font-size:28rpx;color: #666;padding-top:20rpx;}
.header .commission{display:inline-block;margin-top:20rpx;margin-bottom:10rpx;border-radius:10rpx;font-size:20rpx;height:44rpx;line-height:44rpx;padding:0 20rpx}

.choose{ display:flex;align-items:center;width: 100%; background: #fff;  margin-top: 20rpx; height: 88rpx; line-height: 88rpx;padding: 0 3%; color: #333; }
.choose .f2{ width: 32rpx; height: 32rpx;}

.cuxiaodiv{background:#fff;margin-top:20rpx;padding: 0 3%;}
.cuxiaopoint{width:100%;font-size:24rpx;color:#333;height:88rpx;line-height:88rpx;padding:12rpx 0;display:flex;align-items:center}
.cuxiaopoint .f0{color:#777777;height:32rpx;font-size:24rpx;padding-right:20rpx;display:flex;justify-content:center;align-items:center}
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

.detail{min-height:200rpx;}

.detail_title{width:100%;display:flex;align-items:center;justify-content:center;margin-top:60rpx;margin-bottom:30rpx}
.detail_title .t0{font-size:28rpx;font-weight:bold;color:#222222;margin:0 20rpx}
.detail_title .t1{width:12rpx;height:12rpx;background:rgba(253, 74, 70, 0.2);transform:rotate(45deg);margin:0 4rpx;margin-top:6rpx}
.detail_title .t2{width:18rpx;height:18rpx;background:rgba(253, 74, 70, 0.4);transform:rotate(45deg);margin:0 4rpx}

.commentbox{width:100%;background:#fff;padding:0 3%;margin-top:20rpx}
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

.bottombar{ width: 94%; position: fixed;bottom: 0px; left: 0px; background: #fff;display:flex;height:100rpx;padding:0 4% 0 2%;align-items:center;box-sizing:content-box}
.bottombar .f1{flex:1;display:flex;align-items:center;margin-right:30rpx}
.bottombar .f1 .item{display:flex;flex-direction:column;align-items:center;width:80rpx;position:relative}
.bottombar .f1 .item .img{ width:44rpx;height:44rpx}
.bottombar .f1 .item .t1{font-size:18rpx;color:#222222;height:30rpx;line-height:30rpx;margin-top:6rpx}
.bottombar .op{width:60%;border-radius:36rpx;overflow:hidden;display:flex;}
.bottombar .tobuy{flex:1;height: 72rpx; line-height: 72rpx;color: #fff; border-radius: 0px; border: none;}

.tg_pricedata{width: 100%; background: #fff;  margin-top:20rpx; padding: 0 3%;padding-top:0;position: relative; min-height:100rpx;}
.tg_pricetitle{height:70rpx;line-height:70rpx;font-size:30rpx;color:#555;font-weight: bold;border-bottom:1px solid #ddd}
.tg_price{height:80rpx;line-height:80rpx;border-bottom:1px dotted #ddd}
.tg_price .t1{flex-shrink: 0;}
</style>