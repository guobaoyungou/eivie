<template>
<view class="container">
	<block v-if="isload">
		<view class="toptabbar_tab" v-if="showtoptabbar==1 && toptabbar_show==1">
			<view class="item" :class="toptabbar_index==0?'on':''" :style="{color:toptabbar_index==0?t('color1'):'#333'}" @tap="changetoptab" data-index="0">商品<view class="after" :style="{background:t('color1')}"></view></view>
			<view class="item" :class="toptabbar_index==1?'on':''" :style="{color:toptabbar_index==1?t('color1'):'#333'}" @tap="changetoptab" data-index="1">评价<view class="after" :style="{background:t('color1')}"></view></view>
			<view class="item" :class="toptabbar_index==2?'on':''" :style="{color:toptabbar_index==2?t('color1'):'#333'}" @tap="changetoptab" data-index="2">详情<view class="after" :style="{background:t('color1')}"></view></view>
			<view class="item" v-if="tjdatalist.length > 0" :class="toptabbar_index==3?'on':''" :style="{color:toptabbar_index==3?t('color1'):'#333'}" @tap="changetoptab" data-index="3">推荐<view class="after" :style="{background:t('color1')}"></view></view>
		</view>

		<scroll-view @scroll="scroll" :scrollIntoView="scrollToViewId" :scrollTop="scrollTop" :scroll-y="true" style="height:100%;overflow:scroll">
		
		<view id="scroll_view_tab0">
		
			<view class="swiper-container">
				<swiper class="swiper" :indicator-dots="false" :autoplay="true" :interval="5000" @change="swiperChange">
					<block v-for="(item, index) in product.pics" :key="index">
						<swiper-item class="swiper-item">
							<view class="swiper-item-view"><image class="img" :src="item" mode="widthFix"/></view>
						</swiper-item>
					</block>
				</swiper>
				<view class="imageCount">{{current+1}}/{{product.pics.length}}</view>
			</view>
			<view class="collage_title">
				<view class="f1">
					<view class="t1">
						<view class="x1">￥</view>
						<view class="x2">{{product.sell_price}}</view>
						<view class="x3">礼包数量{{product.perlimit}}</view>
					</view>
				</view>
			</view>

			<view class="header"> 
				<view class="title">
					<view class="lef">
						<text>{{product.name}}</text>
					</view>
					<view class="share" @tap="shareClick">
						<image :src="pre_url+'/static/img/share.png'"></image>
						<text>分享</text>
					</view>
				</view>
				<view class="sellpoint" @tap="buydialogShow">查看详情</view>
				<view class="sellpoint" v-if="product.sellpoint">{{product.sellpoint}}</view>
                <view class="sales_stock">
                	<view class="f2">库存：{{product.stock}}</view>
                </view>
			</view>


			
			<view style="width:100%;height:auto;padding:20rpx 0 0" v-if="shopset.detail_guangao2">
				<image :src="shopset.detail_guangao2" style="width:100%;height:auto" mode="widthFix" v-if="shopset.detail_guangao2" @tap="showgg2Dialog"/>
			</view>
			<uni-popup id="gg2Dialog" ref="gg2Dialog" type="dialog" v-if="shopset.detail_guangao2 && shopset.detail_guangao2_t">
				<image :src="shopset.detail_guangao2_t" @tap="previewImage" :data-url="shopset.detail_guangao2_t" class="img" mode="widthFix" style="width:600rpx;height:auto;border-radius:10rpx;"/>
				<view class="ggdiaplog_close" @tap="closegg2Dialog">
					<image :src="pre_url+'/static/img/close2.png'" style="width:100%;height:100%"/>
				</view>
			</uni-popup>

		
		</view>

		<view id="scroll_view_tab1">

			<view class="commentbox" v-if="shopset.comment==1 && commentcount > 0">
				<view class="title">
					<view class="f1">评价({{commentcount}})</view>
					<view class="f2" @tap="goto" :data-url="'commentlist?proid=' + product.id">好评度 <text :style="{color:t('color1')}">{{product.comment_haopercent}}%</text><image style="width:32rpx;height:32rpx;" :src="pre_url+'/static/img/arrowright.png'" /></view>
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
		

		</view>

		<view id="scroll_view_tab2">

			<view class="shop" v-if="shopset.showjd==1">
				<image :src="business.logo" class="p1"/>
				<view class="p2 flex1">
					<view class="t1">{{business.name}}</view>
					<view class="t2">{{business.desc}}</view>
				</view>
				<button class="p4" :style="'background:linear-gradient(90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'" @tap="goto" :data-url="product.bid==0?'/pages/index/index':'/pagesExt/business/index?id='+product.bid" data-opentype="reLaunch">进入店铺</button>
			</view>
			<view class="detail_title"><view class="t1"></view><view class="t2"></view><view class="t0">商品描述</view><view class="t2"></view><view class="t1"></view></view>
			<view class="detail">
				<dp :pagecontent="pagecontent"></dp>
			</view>
		
		</view>
		
		<view id="scroll_view_tab3">

		</view>

		<view style="width:100%;height:110rpx;box-sizing:content-box" class="notabbarbot"></view>

		</scroll-view>

		<view class="bottombar flex-row" :class="menuindex>-1?'tabbarbot':'notabbarbot'" v-if="product.status==1">
			<view class="cart flex-col flex-x-center flex-y-center" @tap="goto" :data-url="kfurl" v-if="kfurl!='contact::'">
				<image class="img" :src="pre_url+'/static/img/kefu.png'"/>
				<view class="t1">客服</view>
			</view>
			<button class="cart flex-col flex-x-center flex-y-center" v-else open-type="contact" show-message-card="true">
				<image class="img" :src="pre_url+'/static/img/kefu.png'"/>
				<view class="t1">客服</view>
			</button>
			<view class="favorite flex-col flex-x-center flex-y-center" @tap="addfavorite">
				<image class="img" :src="pre_url+'/static/img/shoucang.png'"/>
				<view class="t1">{{isfavorite?'已收藏':'收藏'}}</view>
			</view>
			<!-- <view class="tocart" :style="{background:t('color2')}" @tap="buydialogShow" data-btntype="1"><text>单独购买</text><text>￥{{product.market_price}}</text></view> -->
			<!-- <view class="tocart" :style="{background:t('color2')}" @tap="buydialogShow" data-btntype="1"></view> -->
			<view class="tobuy" :style="{background:t('color1')}"  @tap="tobuy" data-type="2"><text>立即购买</text></view>
		</view>


		<view :hidden="buydialogHidden">
			<view class="buydialog-mask">
				<view class="buydialog" :class="menuindex>-1?'tabbarbot':'notabbarbot'">
					<view class="close" @tap="buydialogChange">
						<image :src="pre_url+'/static/img/close.png'" class="image"></image>
					</view>
					<block v-for="(item,index) in guigelist">
						<view class="shop-options flex-bt">
							<view class="shop-info-view">
								<view class="shop-title">{{item.name}}</view>
								<view class="shop-tisp" style="width: 100%;">
									<text>{{item.ggname}}</text>
									<text class="addnum" style="margin-left: 80%;">库存：{{item.stock}}</text>
								</view>
							</view>
						</view>
					</block>
					
					<!-- <view class="op">
						<block v-if="btntype==1">
							<button class="tobuy" :style="{background:t('color2')}" @tap="tobuy" data-type="1">确定</button>
						</block>
						<block v-if="btntype==2">
							<button class="tobuy" :style="{background:t('color1')}" @tap="tobuy" data-type="2">下一步</button>
						</block>
						<block v-if="btntype==3">
							<button class="tobuy" :style="{background:t('color1')}" @tap="tobuy" data-type="3">确 定</button>
						</block>
					</view> -->
				</view>
			</view>
		</view>

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
	</block>
	<loading v-if="loading"></loading>
	<dp-tabbar :opt="opt" @getmenuindex="getmenuindex"></dp-tabbar>
	<popmsg ref="popmsg"></popmsg>
	<view style="display:none">{{test}}</view>
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
			
		indexurl:app.globalData.indexurl,
		tabnum: 1,
		buydialogHidden: true,
		num: 1,
		sysset:{},
		shopset:{},
		isfavorite: false,
		btntype: 1,
		ggselected: [],
		guigedata:[],
		guigelist:[],
		ks: '',
		gwcnum: 1,
		nodata: 0,
		product:{},
		userinfo: [],
		current: 0,
		pagecontent: "",
		business:{},
		commentcount:0,
		commentlist:[],
		nowtime: "",
		sharetypevisible: false,
		showposter: false,
		posterpic: "",
		kfurl:'',
		tjdatalist:[],
		showtoptabbar:0,
		toptabbar_show:0,
		toptabbar_index:0,
		scrollToViewId: "",
		scrollTop:0,
		scrolltab0Height:0,
		scrolltab1Height:0,
		scrolltab2Height:0,
		scrolltab3Height:0,
		test:'',
		pre_url:app.globalData.pre_url,
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
  methods: {
		getdata: function () {
			var that = this;
			var id = that.opt.id;
			that.loading = true;
			app.get('ApiTaocan/product', {id: id}, function (res) {
				that.loading = false;
				if (res.status == 0) {
					app.alert(res.msg);
					return;
				}
				var pagecontent = JSON.parse(res.product.detail);
				that.pagecontent = pagecontent;
				that.business = res.business;
				that.commentcount = res.commentcount;
				that.commentlist = res.commentlist;
				that.ggselected = res.ggselected;
				that.guigedata = res.guigedata;
				that.guigelist = res.guigelist;
				that.isfavorite = res.isfavorite;
				that.ks = res.ks;
				that.nowtime = res.nowtime;
				that.product = res.product;
				that.shopset = res.shopset;
				that.sysset = res.sysset;
				that.tjdatalist = res.tjdatalist || [];
				that.showtoptabbar = res.showtoptabbar || 0;
				uni.setNavigationBarTitle({
					title: res.product.name
				});
				that.kfurl = '/pages/kefu/index?bid='+res.product.bid;
				if(app.globalData.initdata.kfurl != ''){
					that.kfurl = app.globalData.initdata.kfurl;
				}
				if(that.business && that.business.kfurl){
					that.kfurl = that.business.kfurl;
				}
				that.loaded({title:res.product.name,pic:res.product.pic});
				
				setTimeout(function(){
					let view0 = uni.createSelectorQuery().in(that).select('#scroll_view_tab0')
					view0.fields({
						size: true,//是否返回节点尺寸（width height）
						rect: false,//是否返回节点的 scrollLeft scrollTop，节点必须是 scroll-view 或者 viewport
						scrollOffset: false,//是否返回节点的 scrollLeft scrollTop，节点必须是 scroll-view 或者 viewport
					}, (res) => {
						console.log(res)
						that.scrolltab0Height = res.height
					}).exec();
					let view1 = uni.createSelectorQuery().in(that).select('#scroll_view_tab1')
					view1.fields({
						size: true,//是否返回节点尺寸（width height）
						rect: false,//是否返回节点的 scrollLeft scrollTop，节点必须是 scroll-view 或者 viewport
						scrollOffset: false,//是否返回节点的 scrollLeft scrollTop，节点必须是 scroll-view 或者 viewport
					}, (res) => {
						console.log(res)
						that.scrolltab1Height = res.height
					}).exec();
					let view2 = uni.createSelectorQuery().in(that).select('#scroll_view_tab2')
					view2.fields({
						size: true,//是否返回节点尺寸（width height）
						rect: false,//是否返回节点的 scrollLeft scrollTop，节点必须是 scroll-view 或者 viewport
						scrollOffset: false,//是否返回节点的 scrollLeft scrollTop，节点必须是 scroll-view 或者 viewport
					}, (res) => {
						console.log(res)
						that.scrolltab2Height = res.height
					}).exec();
				},500)
			});
		},
    swiperChange: function (e) {
      var that = this;
      that.current = e.detail.current;
    },
    //加入购物车
    buydialogShow: function (e) {
      var btntype = e.currentTarget.dataset.btntype;
      this.btntype = btntype;
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
    },
    tobuy: function (e) {
      var type = e.currentTarget.dataset.type;
      var that = this;
      var ks = that.ks;
      var proid = that.product.id;
      app.goto('libao?proid=' + proid  );
    },
    //加
    gwcplus: function (e) {
      var gwcnum = this.gwcnum + 1;
      var ggselected = this.ks;
      if (gwcnum > this.guigelist[ggselected].stock) {
        app.error('库存不足');
        return;
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
      this.gwcnum = this.gwcnum - 1
    },
    //输入
    gwcinput: function (e) {
      var ggselected = this.ks;
      var gwcnum = parseInt(e.detail.value);
      if (gwcnum < 1) return 1;

      if (gwcnum > this.guigelist[ggselected].stock) {
        return this.guigelist[ggselected].stock;
      }
      this.gwcnum = gwcnum;
    },
    //收藏操作
    addfavorite: function () {
      var that = this;
      var proid = that.product.id;
			app.showLoading('收藏中');
      app.post('ApiTaocan/addfavorite', {proid: proid,type: 'taocan'}, function (data) {
				app.showLoading(false);
        if (data.status == 1) {
          that.isfavorite = !that.isfavorite
        }
        app.success(data.msg);
      });
    },
    tabClick: function (e) {
      this.tabnum = e.currentTarget.dataset.num;
    },
    shareClick: function () {
      this.sharetypevisible = true;
    },
    handleClickMask: function () {
      this.sharetypevisible = false;
    },
    showPoster: function () {
      var that = this;
      that.showposter = true;
      that.sharetypevisible = false;
      app.showLoading('努力生成中');
			app.post('ApiTaocan/getposter', {proid: that.product.id}, function (data) {
				app.showLoading(false);
        if (data.status == 0) {
          app.alert(data.msg);
        } else {
          that.posterpic = data.poster;
        }
      });
    },
    posterDialogClose: function () {
      this.showposter = false;;
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
						sharedata.href = app.globalData.pre_url +'/h5/'+app.globalData.aid+'.html#/pagesA/taocan/product?scene=id_'+that.product.id+'-pid_' + app.globalData.mid;
						sharedata.imageUrl = that.product.pic;
						var sharelist = app.globalData.initdata.sharelist;
						if(sharelist){
							for(var i=0;i<sharelist.length;i++){
								if(sharelist[i]['indexurl'] == '/pagesA/taocan/product'){
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
			//console.log(that.scrolltab0Height);
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
		showgg1Dialog:function(){
			this.$refs.gg1Dialog.open();
		},
		closegg1Dialog:function(){
			this.$refs.gg1Dialog.close();
		},
		showgg2Dialog:function(){
			this.$refs.gg2Dialog.open();
		},
		closegg2Dialog:function(){
			this.$refs.gg2Dialog.close();
		},
	}
}
</script>
<style>
page {position: relative;width: 100%;height: 100%;}
.container{height:100%}

.swiper-container{position:relative}
.swiper {width: 100%;height: 750rpx;overflow: hidden;}
.swiper-item-view{width: 100%;height: 750rpx;}
.swiper .img {width: 100%;height: 750rpx;overflow: hidden;}

.imageCount {width:100rpx;height:50rpx;background-color: rgba(0, 0, 0, 0.3);border-radius:40rpx;line-height:50rpx;color:#fff;text-align:center;font-size:26rpx;position:absolute;right:13px;bottom:20rpx;}

.header {width: 100%;padding: 0 3%;background: #fff;}
.header .title {padding: 10px 0px;line-height:44rpx;font-size:32rpx;display:flex;}
.header .title .lef{display:flex;flex-direction:column;justify-content: center;flex:1;color:#222222;font-weight:bold}
.header .title .lef .t2{ font-size:26rpx;color:#999;padding-top:10rpx;font-weight:normal}
.header .title .share{width:88rpx;height:88rpx;padding-left:20rpx;border-left:0 solid #f5f5f5;text-align:center;font-size:24rpx;color:#222;display:flex;flex-direction:column;align-items:center}
.header .title .share image{width:32rpx;height:32rpx;margin-bottom:4rpx}

.header .sellpoint{font-size:28rpx;color: #666;padding-bottom:20rpx;}

.header .price{height: 86rpx;overflow: hidden;line-height: 86rpx;border-top: 1px solid #eee;}
.header .price .t1 .x1{ color: #e94745; font-size: 34rpx;}
.header .price .t1 .x2{ color: #939393; margin-left: 10rpx; text-decoration: line-through;font-size:24rpx}
.header .price .t2{color: #aaa; font-size: 24rpx;}
.header .fuwupoint{width:100%;font-size:24rpx;color:#999;display:flex;flex-wrap:wrap;border-top:1px solid #eee;padding:10rpx 0}
.header .fuwupoint .t{ padding:4rpx 20rpx 4rpx 0}
.header .fuwupoint .t:before{content: "";	display: inline-block;	vertical-align: middle;	margin-top: -4rpx;	margin-right: 10rpx;	width: 24rpx;	height: 24rpx;	background: url('data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABgAAAAYBAMAAAASWSDLAAAABGdBTUEAALGPC/xhBQAAAAFzUkdCAK7OHOkAAAAwUExURUdwTOU5O+Q5POU5POQ4O+U4PN80P+M4O+Q4O+Q4POQ5POQ4OuQ4O+Q4O+I4PuQ5PJxkAycAAAAPdFJOUwAf+VSoeAvzws7ka7miLboUzckAAADJSURBVBjTY2BgYGCMWVR5VIABDBid/gPBFwjP/JOzQKKtfjGIzf3fEUSJ/N8AJO21Iao3fQbqqA+AcLi/CzCwfGGAAn8HBnlFMIttBoP4R4b4C2BOzk8G3q8M5w3AnPsLGZj/MKwHW8b6/QED4y8G/QQQx14ZSHwCcWYkMOtvAHOAyvqnPf8KcuMvkAGZP9eDjAQaEO/AwDb/D0gj0GiQpRnTQIYIfUR1DopDGexVIZygz8ieC4B6WyzRBOJtBkZ/pAABBZUWOKgAispF5e7ibycAAAAASUVORK5CYII=') no-repeat;	background-size: 24rpx auto; }
.header .sales_stock{display:flex;justify-content:space-between;height:60rpx;line-height:60rpx;margin-top:30rpx;font-size:24rpx;color:#777777}
.choose{ display:flex;align-items:center;width: 100%; background: #fff;  margin-top: 20rpx; height: 80rpx; line-height: 80rpx; padding: 0 3%; color: #505050; }
.choose .f2{ width: 40rpx; height: 40rpx;}


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

.bottombar{ width: 100%; position: fixed;bottom: 0px; left: 0px; background: #fff;height:110rpx;align-items:center;box-sizing:content-box}
.bottombar .favorite{width: 15%;color:#707070;font-size:26rpx}
.bottombar .favorite .fa{ font-size:40rpx;height:50rpx;line-height:50rpx}
.bottombar .favorite .img{ width:50rpx;height:50rpx}
.bottombar .favorite .t1{font-size:24rpx;color:#222222;height:30rpx;line-height:30rpx;margin-top:6rpx}
.bottombar .cart{width: 15%;font-size:26rpx;color:#707070}
.bottombar .cart .img{ width:50rpx;height:50rpx}
.bottombar .cart .t1{font-size:24rpx;color:#222222;height:30rpx;line-height:30rpx;margin-top:6rpx}
.bottombar .tocart{ width:35%; height: 90rpx;border-radius:10rpx;color: #fff; background: #fa938a; font-size: 28rpx;display:flex;flex-direction:column;align-items:center;justify-content:center;border-radius:45rpx 0 0 45rpx;padding-left:20rpx}
.bottombar .tobuy{ width:35%;margin-left: 35%; height: 90rpx;border-radius:10rpx;color: #fff; background: #df2e24; font-size:28rpx;display:flex;flex-direction:column;align-items:center;justify-content:center;border-radius:45rpx;margin-right:16rpx;padding-right:20rpx}



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

.collage_title{width:100%;height:110rpx;background: linear-gradient(90deg, #FF3143 0%, #FE6748 100%);display:flex;align-items:center;padding:0 40rpx}
.collage_title .f1{flex:1;display:flex;flex-direction:column;}
.collage_title .f1 .t1{display:flex;align-items:center;height:60rpx;line-height:60rpx}
.collage_title .f1 .t1 .x1{font-size:28rpx;color:#fff}
.collage_title .f1 .t1 .x2{font-size:48rpx;color:#fff;padding-right:20rpx}
.collage_title .f1 .t1 .x3{font-size:24rpx;font-weight:bold;height:40rpx;line-height:40rpx;padding:0 20rpx;border-radius:20rpx;background:#fff;color:#ED533A}
.collage_title .f1 .t2{color:rgba(255,255,255,0.6);font-size:20rpx;}
.collage_title .f2{color:#fff;font-size:28rpx;}

.toptabbar_tab{display:flex;width:100%;height:90rpx;background: #fff;top:var(--window-top);z-index:11;position:fixed;border-bottom:1px solid #f3f3f3}
.toptabbar_tab .item{flex:1;font-size:28rpx; text-align:center; color:#666; height: 90rpx; line-height: 90rpx;overflow: hidden;position:relative}
.toptabbar_tab .item .after{display:none;position:absolute;left:50%;margin-left:-16rpx;bottom:10rpx;height:3px;border-radius:1.5px;width:32rpx}
.toptabbar_tab .on{color: #323233;}
.toptabbar_tab .on .after{display:block}

.xihuan{height: auto;overflow: hidden;display:flex;align-items:center;width:100%;padding:20rpx 160rpx;margin-top:20rpx}
.xihuan-line{height: auto; padding: 0; overflow: hidden;flex:1;height:0;border-top:1px solid #eee}
.xihuan-text{padding:0 32rpx;text-align:center;display:flex;align-items:center;justify-content:center}
.xihuan-text .txt{color:#111;font-size:30rpx}
.xihuan-text .img{text-align:center;width:36rpx;height:36rpx;margin-right:12rpx}
.prolist{width: 100%;height:auto;padding: 8rpx 20rpx;}

.dp-collage-item{height: auto; position: relative;overflow: hidden; padding: 0px; display:flex;flex-wrap:wrap}
.dp-collage-item .item{display: inline-block;position: relative;margin-bottom: 12rpx;background: #fff;border-radius:10rpx;overflow:hidden}
.dp-collage-item .product-pic {width: 100%;height:0;overflow:hidden;background: #ffffff;padding-bottom: 100%;position: relative;}
.dp-collage-item .product-pic .image{position:absolute;top:0;left:0;width: 100%;height:auto}
.dp-collage-item .product-pic .saleimg{ position: absolute;width: 60px;height: auto; top: -3px; left:-3px;}
.dp-collage-item .product-pic .tag{padding: 0 15rpx;line-height: 35rpx;display: inline-block;font-size: 24rpx;color: #fff;background: linear-gradient(to bottom right,#ff88c0,#ec3eda);position: absolute;left: 0;top: 0;border-radius: 0 0 10rpx 0}
.dp-collage-item .product-info {padding:20rpx 20rpx;position: relative;}
.dp-collage-item .product-info .p1 {color:#323232;font-weight:bold;font-size:28rpx;line-height:36rpx;margin-bottom:10rpx;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:2;overflow:hidden;height:72rpx}
.dp-collage-item .product-info .p2{display:flex;align-items:center;overflow:hidden;padding:2px 0}
.dp-collage-item .product-info .p2-1{flex-grow:1;flex-shrink:1;height:40rpx;line-height:40rpx;overflow:hidden;white-space: nowrap}
.dp-collage-item .product-info .p2-1 .t1{font-size:36rpx;}
.dp-collage-item .product-info .p2-1 .t2 {margin-left:10rpx;font-size:24rpx;color: #aaa;text-decoration: line-through;/*letter-spacing:-1px*/}
.dp-collage-item .product-info .p2-2{font-size:20rpx;height:40rpx;line-height:40rpx;text-align:right;padding-left:20rpx;color:#999}
.dp-collage-item .product-info .p3{display:flex;align-items:center;overflow:hidden;margin-top:10rpx;justify-content:space-between}
.dp-collage-item .product-info .p3-1{height:40rpx;line-height:40rpx;border:0 #FF3143 solid;border-radius:10rpx;color:#FF3143;padding:0 24rpx;font-size:24rpx}
.dp-collage-item .product-info .p3-2{color:#999999;font-size:20rpx;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:1;}
.dp-collage-item .product-info .total{border-radius: 8rpx;border: 1rpx solid #FF3143;font-size: 24rpx;background: #ffeded;overflow: hidden;}
.dp-collage-item .product-info .total .num{color: #fff;background: #FF3143;padding: 3rpx 8rpx;}
.dp-collage-item .product-info .total .sales{color: #FF3143;padding: 3rpx 8rpx;}
.dp-collage-item .product-info .price{position: relative;margin-top: 15rpx;}
.dp-collage-item .product-info .price .text{color: #FF3143;font-weight: bold;font-size: 30rpx;}
.dp-collage-item .product-info .price .add{height: 50rpx;width: 50rpx;border-radius: 100rpx;background: #FF3143;}
.dp-collage-item .product-info .price .add image{height: 30rpx;width: 30rpx;display: block;}

.ggdiaplog_close{width:50rpx;height:50rpx;position:absolute;bottom:-100rpx;left:50%;margin-left:-25rpx;border:1px solid rgba(255,255,255,0.5);border-radius:50%;padding:8rpx}
.shop-options{width: 100%;height:auto;align-items: center;justify-content: flex-start;position: relative;margin-top: 30rpx;border-bottom:1px #f3f3f3 solid;padding-bottom: 20rpx;}
.shop-options .shop-image{width: 145rpx;height: 145rpx;border-radius: 16rpx;border: 1px red solid;}
.shop-options .shop-info-view{height: 100%;flex:1;padding: 20rpx;display: flex;flex-direction: column;align-items: flex-start;justify-content: flex-start;
}
.shop-info-view .shop-title{color: #1A1A1A;font-size: 24rpx;font-weight: bold;}
.shop-info-view .shop-tisp{color: #AAAAAA;font-size: 24rpx;margin-top: 20rpx;}

</style>