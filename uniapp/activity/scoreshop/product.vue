<template>
<view>
	<block v-if="isload">
		<view class="swiper-container">
			<swiper class="swiper" :indicator-dots="false" :autoplay="true" :interval="5000" @change="swiperChange" :style="{ height: swiperHeight + 'px' }">
				<block v-for="(item, index) in product.pics" :key="index">
					<swiper-item class="swiper-item">
						<view class="swiper-item-view" :style="{ height: swiperHeight + 'px' }">
							<image class="img" :src="item" mode="widthFix" @load="loadImg" />
						</view>
					</swiper-item>
				</block>
			</swiper>
			<view class="imageCount">{{current+1}}/{{(product.pics).length}}</view>
		</view>
		<view class="header">
			<view class="price_share">
				<view class="price">
					<view class="f1" :style="{color:t('color1')}">{{product.score_price}}{{t('积分')}}<text v-if="product.money_price*1>0">+{{product.money_price}}元</text></view>
					<view class="f2">￥{{product.sell_price}} </view>
				</view>
				<view class="price2" v-if="product.show_lvprice==1">
					<view v-for="(item,index) in product.new_lvprice_data" :class="item.is_select==1?'f1':'f2'">
						{{item.name}}价:{{item.score}}{{t('积分')}}<text v-if="item.money*1>0">+{{item.money}}元</text>
					</view>
				</view>
				<view class="share" @tap="shareClick"><image class="img" :src="pre_url+'/static/img/share.png'"/><text class="txt">分享</text></view>
			</view>
			<view class="title">{{product.name}}</view>
			<view class="sales_stock">
				<view class="f1">已兑换{{product.sales}}件</view>
				<view class="f2">库存：{{product.stock}}</view>
			</view>
			<view class="commission" :style="{background:'rgba('+t('color1rgb')+',0.1)',color:t('color1')}" v-if="shopset.showcommission==1 && (product.commission > 0 || product.commission_score > 0)">
			分享好友购买预计可得{{t('佣金')}}：
			<block v-if="product.commission > 0"><text style="font-weight:bold;padding:0 2px">{{product.commission}}</text>{{product.commission_desc}}</block>
			<text v-if="product.commission > 0 && product.commission_score > 0">+</text>
			<block v-if="product.commission_score > 0"><text style="font-weight:bold;padding:0 2px">{{product.commission_score}}</text>{{product.commission_score_desc}}</block>
			</view>
		</view>
    
    <view class="cuxiaodiv" v-if="product.givescore">
    	<view class="cuxiaopoint">
    		<view class="f0">送{{t('积分')}}</view>
    		<view class="f1" style="font-size:26rpx">购买可得{{t('积分')}}{{product.givescore}}个</view>
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

    <!-- 自提商品附近门店S -->
    <view v-if="showNearbyMendian && mendianids.length>0 && latitude && longitude " class="nearby-mendian-box">
    	<view class="nearby-mendian-title">
    		<view class="t1">附近{{t('门店')}}<text v-if="mendianids.length>1">（{{mendianids.length}}家）</text></view>
    		<view class="t2" @tap="goto" :data-url="'/pagesExt/business/mendian?type=scoreshop&bid='+product.bid+'&proid='+product.id"><text>{{mendianids.length>1?'全部':'查看'}}{{t('门店')}}</text><image :src="pre_url+'/static/img/arrowright.png'"></image></view>
    	</view>
    	<view class="nearby-mendian-info">
    		<view class="b1" @tap="goto" :data-url="'/pages/shop/mendian?id='+mendian.id"><image :src="mendian.pic"></image></view>
    		<view class="b2">
    			<view class="t1" @tap="goto" :data-url="'/pages/shop/mendian?id='+mendian.id">{{mendian.name}}</view>
    			<view class="t2 flex-y-center">
    				<block v-if="mendian.distance">
    					<view :style="{background:'rgba('+t('color1rgb')+',0.1)',color:t('color1')}" class="nearby-tag">最近</view> 
    					<view class="mendian-distance">{{mendian.distance}} </view>
    				</block>
    				<view class="mendian-address">{{mendian.address?mendian.address:mendian.area}}</view>
    			</view>
    		</view>
    		<view class="b3">
    			<view @tap="callMendian" :data-tel="mendian.tel"><image :src="pre_url+'/static/img/location/tel.png'"></image></view>
    			<!-- #ifndef MP-ALIPAY-->
    			<view @tap="toMendian" :data-address="mendian.address" :data-longitude="mendian.longitude" :data-latitude="mendian.latitude"><image :src="pre_url+'/static/img/location/daohang.png'"></image></view>
    			<!-- #endif -->
    		</view>
    	</view>
    </view>
    <!-- #ifdef MP-ALIPAY -->
    	<!-- 支付宝先授权再定位 -->
    	<view class="cuxiaodiv" v-if="showNearbyMendian && (longitude =='' || latitude =='')">
    		<view class="cuxiaopoint">
    			<view class="f0">定位服务未授权，授权后查看附近门店</view>
    			<button class="shouquan" :style="{background:'linear-gradient(90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'}" @tap="toLocation">授权</button>
    		</view>
    	</view>
    <!-- #endif -->
    <!-- 自提商品附近门店E -->
    
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
				<view class="item flex1" @tap="goto" data-url="cart">
					<image class="img" :src="pre_url+'/static/img/gwc.png'"/>
					<view class="t1">购物车</view>
					<view class="cartnum" v-if="cartnum>0">{{cartnum}}</view>
				</view>
				<view class="item" @tap="addfavorite">
					<image class="img" :src="pre_url+'/static/img/shoucang.png'"/>
					<view class="t1">{{isfavorite?'已收藏':'收藏'}}</view>
				</view>
			</view>
			<view class="op">
				<view class="tocart flex-x-center flex-y-center" @tap="buydialogChange" data-btntype="1" :style="{background:t('color2')}">加入购物车</view>
				<view class="tobuy flex-x-center flex-y-center" @tap="buydialogChange" data-btntype="2" :style="{background:t('color1')}">立即兑换</view>
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



		<view v-if="buydialogShow">
			<view class="buydialog-mask" @tap="buydialogChange"></view>
			<view class="buydialog" :class="menuindex>-1?'tabbarbot':'notabbarbot'">
				<view class="close" @tap="buydialogChange">
					<image :src="pre_url+'/static/img/close.png'" class="image"/>
				</view>
				<view class="title flex">
					<image :src="nowguige.pic || product.pic" class="img" @tap="previewImage" :data-url="nowguige.pic || product.pic"/>
					<view class="flex1">
						<view  class="price" :style="{color:t('color1')}">
							{{nowguige.score_price}}{{t('积分')}}<text v-if="nowguige.money_price*1>0">+{{nowguige.money_price}}元</text>
							<text v-if="nowguige.market_price > 0" class="t2">￥{{nowguige.market_price}}</text>
						</view>
						<view class="stock" v-if="!shopset || shopset.hide_stock!=1">库存：{{nowguige.stock}}</view>
						<view class="choosename" v-if="product.guigeset==1">已选规格: {{nowguige.name}}</view>
					</view>
				</view>
				<view style="max-height:50vh;overflow:scroll" v-if="product.guigeset==1">
					<view v-for="(item, index) in guigedata" :key="index" class="guigelist flex-col">
						<view class="name">{{item.title}}</view>
						<view class="item flex flex-y-center">
							<block v-for="(item2, index2) in item.items" :key="index2">
								<view :data-itemk="item.k" :data-idx="item2.k" :class="'item2 ' + (ggselected[item.k]==item2.k ? 'on':'')" @tap="ggchange">{{item2.title}}</view>
							</block>
						</view>
					</view>
				</view>
				<view class="buynum flex flex-y-center">
					<view class="flex1">购买数量：</view>
					<view class="addnum">
						<view class="minus" @tap="gwcminus"><image class="img" :src="pre_url+'/static/img/cart-minus.png'" /></view>
						<input class="input" type="number" :value="gwcnum" @input="gwcinput"></input>
						<view class="plus" @tap="gwcplus"><image class="img" :src="pre_url+'/static/img/cart-plus.png'"/></view>
					</view>
				</view>
				<view class="op">
					<block v-if="nowguige.stock <= 0">
						<button class="nostock">库存不足</button>
					</block>
					<block v-else>
						<button class="addcart" :style="{backgroundColor:t('color2')}" @tap="addcart" v-if="btntype==0 && canaddcart">加入购物车</button>
						<button class="tobuy" :style="{backgroundColor:t('color1')}" @tap="tobuy" v-if="btntype==0">立即购买</button>
						<button class="addcart" :style="{backgroundColor:t('color2')}" @tap="addcart" v-if="btntype==1">确 定</button>
						<button class="tobuy" :style="{backgroundColor:t('color1')}" @tap="tobuy" v-if="btntype==2">确 定</button>
					</block>
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
export default {
	data() {
		return {
			opt:{},
			loading:false,
      isload: false,
			menuindex:-1,
			pre_url:app.globalData.pre_url,
			isfavorite: false,
			current: 0,
			sysset: {},
			business:{},
			product: [],
			cartnum: "",
			pagecontent: "",
			shopset: "",
			title: "",
			sharepic: "",
			sharetypevisible: false,
			showposter: false,
			posterpic: "",
			scrolltopshow: false,
			kfurl:'',
			
			buydialogShow: false,
			btntype:2,
			guigelist:{},
			guigedata:{},
			ggselected:{},
			nowguige:{},
			gwcnum:1,
			ggselected:[],
			ks:'',
      //自提商品门店显示
      showNearbyMendian:false,
      longitude: '',
      latitude: '',
      mendianids:[],
      mendian:{},
      mendian_id:0,
      //自提商品门店显示
			swiperHeight: '',
      scoreshop_everytime_buymin: false,
		}
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
      var data = {
        id: id,
        longitude:that.longitude,
        latitude:that.latitude,
        mendian_id:that.mendian_id
      }
			app.post('ApiScoreshop/product', data, function (res) {
				that.loading = false;
				if (res.status == 0) {
					app.alert(res.msg);
					return;
				}
				var product = res.product;
				var pagecontent = JSON.parse(product.detail);
				that.sysset = res.sysset;
				that.product = product;

				that.guigelist = res.guigelist;
				that.guigedata = res.guigedata;
				var ggselected = [];
				for (var i = 0; i < (res.guigedata).length; i++) {
					ggselected.push(0);
				}
				that.ks = ggselected.join(','); 
				that.nowguige = that.guigelist[that.ks];
				that.ggselected = ggselected;

				that.cartnum = res.cartnum;
				that.pagecontent = pagecontent;
				that.shopset = res.shopset;
				that.business = res.business;
				that.title = product.name;
				that.isfavorite = res.isfavorite;
				that.sharepic = product.pics[0];
				that.scoreshop_everytime_buymin = res.scoreshop_everytime_buymin;
        if(that.scoreshop_everytime_buymin && product.everytime_buymin > 0){
          that.gwcnum = product.everytime_buymin;
        }
				uni.setNavigationBarTitle({
					title: product.name
				});
				that.kfurl = '/pages/kefu/index';
				if(app.globalData.initdata.kfurl != ''){
					that.kfurl = app.globalData.initdata.kfurl;
				}
        
        
        if(that.product.can_ziti){
        	that.showNearbyMendian = true;
        	if(res.bindmendianids.length>0){
        		that.mendian = res.mendian
        		that.mendianids = res.bindmendianids
        	}
        }
        //需要定位
        //#ifndef MP-ALIPAY
        if(res.needlocation){
        	app.getLocation(function(res) {
        		that.latitude = res.latitude;
        		that.longitude = res.longitude;
        		that.getdata()
        	},function(error){
        		console.log(error)
        	})
        }
        //#endif

				that.loaded({title:res.product.name,pic:res.product.pic});
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
		buydialogChange: function (e) {
			if(!this.buydialogShow){
				this.btntype = e.currentTarget.dataset.btntype
			}
			this.buydialogShow = !this.buydialogShow;
		},
		ggchange: function (e){
			var idx = e.currentTarget.dataset.idx;
			var itemk = e.currentTarget.dataset.itemk;
			var ggselected = this.ggselected;
			ggselected[itemk] = idx;
			var ks = ggselected.join(',');
			this.ggselected = ggselected;
			this.ks = ks;
			this.nowguige = this.guigelist[this.ks];
		},
		//加
		gwcplus: function (e) {
			var gwcnum = this.gwcnum + 1;
			var ks = this.ks;
			if (gwcnum > this.guigelist[ks].stock) {
				app.error('库存不足');
				return 1;
			}
			this.gwcnum = this.gwcnum + 1;
		},
		//减
		gwcminus: function (e) {
			var gwcnum = this.gwcnum - 1;

      //不能小于起兑数量
      if(this.scoreshop_everytime_buymin && this.product.everytime_buymin > 0 && gwcnum < this.product.everytime_buymin){
        app.error('购买数量不能小于起兑数量');
        return;
      }

			if (gwcnum <= 0) {
				return;
			}
			this.gwcnum = this.gwcnum - 1;
		},
		//输入
		gwcinput: function (e) {
			var ks = this.ks;
			var gwcnum = parseInt(e.detail.value);
			if (gwcnum < 1) gwcnum = 1;

      //不能小于起兑数量
      if(this.scoreshop_everytime_buymin && this.product.everytime_buymin > 0 && gwcnum < this.product.everytime_buymin){
        gwcnum = this.product.everytime_buymin;
      }

			if (gwcnum > this.guigelist[ks].stock) {
				gwcnum = this.guigelist[ks].stock > 0 ? this.guigelist[ks].stock : 1;
			}
			this.gwcnum = gwcnum;
		},
		addcart:function(){
			var that = this;
			var ks = that.ks;
			var num = that.gwcnum;
			var proid = that.product.id;
			var ggid = that.guigelist[ks].id;
			var stock = that.guigelist[ks].stock;
			if (num < 1) num = 1;

      //不能小于起兑数量
      if(that.scoreshop_everytime_buymin && that.product.everytime_buymin > 0 && num < this.product.everytime_buymin){
        app.error('购买数量不能小于起兑数量');
        return;
      }

			if (stock < num) {
				app.error('库存不足');
				return;
			}
			that.loading = true;
			app.post('ApiScoreshop/addcart', {proid:proid,ggid:ggid,num:num}, function (res) {
				that.loading = false;
				if (res.status == 1) {
					app.success(res.msg);
					that.cartnum+=num;
					that.buydialogShow = false;
				}else{
					app.error(res.msg);
				}
			});
		},
		tobuy:function(){
			var ks = this.ks;
			var num = this.gwcnum;
			var proid = this.product.id;
			var ggid = this.guigelist[ks].id;
			var proid = this.product.id;
			var num = this.gwcnum;
			if (num < 1) num = 1;

      //不能小于起兑数量
      if(this.scoreshop_everytime_buymin && this.product.everytime_buymin > 0 && num < this.product.everytime_buymin){
        app.error('购买数量不能小于起兑数量');
        return;
      }

			var prodata = proid + ',' + num + ',' + (ggid == undefined ? '':ggid);
			app.goto('buy?prodata='+prodata);
		},
		//收藏操作
		addfavorite: function () {
			var that = this;
			var proid = that.product.id;
			app.post('ApiScoreshop/addfavorite', {proid: proid,type: 'scoreshop'}, function (data) {
				if (data.status == 1) {
					that.isfavorite = !that.isfavorite;
				}
				app.success(data.msg);
			});
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

			app.post('ApiScoreshop/getposter', {proid: that.product.id}, function (data) {
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
						sharedata.href = app.globalData.pre_url +'/h5/'+app.globalData.aid+'.html#/activity/scoreshop/product?scene=id_'+that.product.id+'-pid_' + app.globalData.mid;
						sharedata.imageUrl = that.product.pic;
						var sharelist = app.globalData.initdata.sharelist;
						if(sharelist){
							for(var i=0;i<sharelist.length;i++){
								if(sharelist[i]['indexurl'] == '/activity/scoreshop/product'){
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
    callMendian:function(e){
    	var tel = e.currentTarget.dataset.tel;
    	uni.makePhoneCall({
    		phoneNumber: tel,
    		fail: function () {
    		}
    	});
    },
    toMendian:function(e){
    	var latitude = parseFloat(e.currentTarget.dataset.latitude);
    	var longitude = parseFloat(e.currentTarget.dataset.longitude);
    	var address = e.currentTarget.dataset.address;
    	if(!latitude || !longitude){
    		return;
    	}
    	uni.openLocation({
    	 latitude:latitude,
    	 longitude:longitude,
    	 name:address,
    	 scale: 13
    	})
    },
    toLocation(){
    	//#ifdef MP-ALIPAY
    	var that = this;
    	app.getLocation(function(res) {
    		that.latitude = res.latitude;
    		that.longitude = res.longitude;
    		that.getdata()
    	},function(error){
    		console.log(error)
    	})
    	//#endif
    },
	}
}
</script>
<style>
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

.header {width: 100%;padding: 20rpx 3%;background: #fff;}
.header .price_share{width:100%;min-height:100rpx;display:flex;align-items:center;justify-content:space-between}
.header .price_share .price{display:flex;align-items:flex-end}
.header .price_share .price .f1{font-size:36rpx;color:#51B539;font-weight:bold}
.header .price_share .price .f2{font-size:26rpx;color:#C2C2C2;text-decoration:line-through;margin-left:30rpx;padding-bottom:2px}
.header .price_share .share{display:flex;flex-direction:column;align-items:center;justify-content:center}
.header .price_share .share .img{width:32rpx;height:32rpx;margin-bottom:2px}
.header .price_share .share .txt{color:#333333;font-size:20rpx}
.header .title {color:#000000;font-size:32rpx;line-height:42rpx;font-weight:bold;}
.header .sales_stock{display:flex;justify-content:space-between;height:60rpx;line-height:60rpx;margin-top:30rpx;font-size:24rpx;color:#777777}
.header .commission{display:inline-block;margin-top:20rpx;margin-bottom:10rpx;border-radius:10rpx;font-size:20rpx;height:44rpx;line-height:44rpx;padding:0 20rpx}

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

.bottombar{ width: 94%; position: fixed;bottom: 0px; left: 0px; background: #fff;display:flex;height:100rpx;padding:0 4% 0 2%;align-items:center;box-sizing:content-box}
.bottombar .f1{flex:1;display:flex;align-items:center;margin-right:30rpx}
.bottombar .f1 .item{display:flex;flex-direction:column;align-items:center;width:80rpx;position:relative}
.bottombar .f1 .item .img{ width:44rpx;height:44rpx}
.bottombar .f1 .item .t1{font-size:18rpx;color:#222222;height:30rpx;line-height:30rpx;margin-top:6rpx}
.bottombar .op{width:60%;border-radius:36rpx;overflow:hidden;display:flex;}
.bottombar .tocart{flex:1;height:72rpx; line-height: 72rpx;color: #fff; border-radius: 0px; border: none; font-size:28rpx;font-weight:bold}
.bottombar .tobuy{flex:1;height: 72rpx; line-height: 72rpx;color: #fff; border-radius: 0px; border: none;}
.bottombar .cartnum{position:absolute;right:4rpx;top:-4rpx;background:rgba(253, 74, 70,0.8);color:#fff;border-radius:50%;width:32rpx;height:32rpx;line-height:32rpx;text-align:center;font-size:22rpx;}

.buydialog-mask{ position: fixed; top: 0px; left: 0px; width: 100%; background: rgba(0,0,0,0.5); bottom: 0px;z-index:10}
.buydialog{ position: fixed; width: 100%; left: 0px; bottom: 0px; background: #fff;z-index:11;border-radius:20rpx 20rpx 0px 0px}
.buydialog .close{ position: absolute; top: 0; right: 0;padding:20rpx;z-index:12}
.buydialog .close .image{ width: 30rpx; height:30rpx; }
.buydialog .title{ width: 94%;position: relative; margin: 0 3%; padding:20rpx 0px; border-bottom:0; height: 190rpx;}
.buydialog .title .img{ width: 160rpx; height: 160rpx; position: absolute; top: 20rpx; border-radius: 10rpx; border: 0 #e5e5e5 solid;background-color: #fff}
.buydialog .title .price{ padding-left:180rpx;width:100%;font-size: 36rpx;height:70rpx; color: #FC4343;overflow: hidden;}
.buydialog .title .price .t1{ font-size:26rpx}
.buydialog .title .price .t2{ font-size:26rpx;text-decoration:line-through;color:#aaa;margin-left:10rpx}
.buydialog .title .choosename{ padding-left:180rpx;width: 100%;font-size: 24rpx;height: 42rpx;line-height:42rpx;color:#888888}
.buydialog .title .stock{ padding-left:180rpx;width: 100%;font-size: 24rpx;height: 42rpx;line-height:42rpx;color:#888888}

.buydialog .guigelist{ width: 94%; position: relative; margin: 0 3%; padding:0px 0px 10px 0px; border-bottom: 0; }
.buydialog .guigelist .name{ height:70rpx; line-height: 70rpx;}
.buydialog .guigelist .item{ font-size: 30rpx;color: #333;flex-wrap:wrap}
.buydialog .guigelist .item2{ height:60rpx;line-height:60rpx;margin-bottom:4px;border:0; border-radius:4rpx; padding:0 40rpx;color:#666666; margin-right: 10rpx; font-size:26rpx;background:#F4F4F4}
.buydialog .guigelist .on{color:#FC4343;background:rgba(252,67,67,0.1);font-weight:bold}
.buydialog .buynum{ width: 94%; position: relative; margin: 0 3%; padding:10px 0px 10px 0px; }
.buydialog .addnum {font-size: 30rpx;color: #666;width:auto;display:flex;align-items:center}
.buydialog .addnum .plus {width:65rpx;height:48rpx;background:#F6F8F7;display:flex;align-items:center;justify-content:center}
.buydialog .addnum .minus {width:65rpx;height:48rpx;background:#F6F8F7;display:flex;align-items:center;justify-content:center}
.buydialog .addnum .img{width:24rpx;height:24rpx}
.buydialog .addnum .input{flex:1;width:50rpx;border:0;text-align:center;color:#2B2B2B;font-size:28rpx;margin: 0 15rpx;}

.buydialog .op{width:90%;margin:20rpx 5%;border-radius:36rpx;overflow:hidden;display:flex;margin-top:100rpx;}
.buydialog .addcart{flex:1;height:72rpx; line-height: 72rpx; color: #fff; border-radius: 0px; border: none;font-size:28rpx;font-weight:bold}
.buydialog .tobuy{flex:1;height: 72rpx; line-height: 72rpx; color: #fff; border-radius: 0px; border: none;font-size:28rpx;font-weight:bold}
.buydialog .nostock{flex:1;height: 72rpx; line-height: 72rpx; background:#aaa; color: #fff; border-radius: 0px; border: none;}

.header .price_share .price2{display:flex;align-items:flex-start;flex-direction: column;}
.header .price_share .price2 .f1{font-size:36rpx;color:#FC4343;font-weight:bold}
.header .price_share .price2 .f2{font-size:26rpx;color:#C2C2C2;}

/*附近门店S*/
.nearby-mendian-box{margin:20rpx 0; background: #FFFFFF;width: 100%;padding: 20rpx;}
.nearby-mendian-title{display: flex;justify-content: space-between;align-items: center;}
.nearby-mendian-title .t1{font-size: 30rpx;font-weight: bold;}
.nearby-mendian-title .t2{color: #999;font-size: 26rpx;}
.nearby-mendian-title .t2 image{height: 26rpx;width: 26rpx;vertical-align: middle;}
.nearby-mendian-info{display: flex;align-items: center;width: 100%;margin-top: 20rpx;}
.mendian-info .b1{background-color: #fbfbfb;}
.nearby-mendian-info .b1 image{height: 90rpx;width:90rpx;border-radius: 6rpx;border: 1px solid #e8e8e8;}
.nearby-mendian-info .b2{flex:1;line-height: 38rpx;margin-left: 10rpx;max-width: 70%;overflow: hidden;}
.nearby-mendian-info .b2 .t1{padding-bottom: 10rpx;}
.nearby-mendian-info .b2 .t2{font-size: 24rpx;color: #999;}
.nearby-mendian-info .b3{display: flex;justify-content: flex-end;flex-shrink: 0;padding-left: 10rpx;width: 130rpx;}
.nearby-mendian-info .b3 image{width: 40rpx;height: 40rpx;margin-right: 16rpx;}
.nearby-mendian-info .nearby-tag{padding:0 10rpx;margin-right: 10rpx;display: inline-block;font-size: 22rpx;border-radius: 8rpx;flex-shrink: 0;}
.nearby-mendian-info .mendian-distance{flex-shrink: 0;margin-right: 10rpx;}
.nearby-mendian-info .mendian-address{white-space: nowrap;text-overflow: ellipsis;max-width: 80%;}
.pd10{padding-left: 10rpx;}
/*附近门店E*/

.cuxiaodiv{background:#fff;margin-top:20rpx;padding:0 3%;}
.cuxiaodiv .cuxiaopoint .shouquan{height:55rpx;line-height:55rpx;color:#FFFFFF;border-radius:32rpx;margin-left:20rpx;flex-shrink:0;padding:0 20rpx;font-size:24rpx;font-weight:bold}
.cuxiaopoint{width:100%;font-size:24rpx;color:#333;height:88rpx;line-height:88rpx;padding:12rpx 0;display:flex;align-items:center}
.cuxiaopoint .f0{color:#555;font-weight:bold;height:32rpx;font-size:24rpx;padding-right:20rpx;display:flex;justify-content:center;align-items:center}
.cuxiaopoint .f1{margin-right:20rpx;flex:1;display:flex;flex-wrap:nowrap;overflow:hidden}
</style>