<template>
<view>
	<block v-if="isload">
	<view class="container">
		<view class="containerbox">
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
				<view class="imageCount">{{current+1}}/{{product.pics.length}}</view>
			</view>
			
			<view class="collage_title">
				<view class="f1">
					<view class="t1">
						<view class="x1">￥</view>
						<view class="x2">{{product.sell_price}}</view>
						<view class="t2">￥{{product.market_price}}</view>
						<view class="x3">{{product.teamnum}}人拼</view>
					</view>
		
				</view>
				<view class="f2">已拼{{product.sales}}件</view>
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
			<view class="title2 flex" v-if="product.show_teamnum == 1">
				<view class="p1"><image :src="pre_url+'/static/img/pintuan_1.png'"></image><view class="t1">参与拼团</view><view class="t1"><text class="t1_1">{{product.teamnum}}</text>人成团</view></view>
				<view class="p1"><image :src="pre_url+'/static/img/pintuan_2.png'"></image><view class="t1"><text class="t1_1">{{product.gua_num}}</text>人拼中发货</view><view class="t1"><text class="t1_1">{{product.teamnum-product.gua_num}}</text>人未中退款</view></view>
				<view class="p1"><image :src="pre_url+'/static/img/pintuan_3.png'"></image><view class="t1">未中补贴</view><view class="t1"><text class="t1_1">{{product.fy_money_val}}</text>元参与奖</view></view>
			</view>
			
			
		</view>
		<view class="teamlist" v-if="teamCount > 0">
			<view class="label"><view class="after" :style="{background:t('color1')}"></view>{{teamCount}}人在拼单，可直接参与</view>
			<scroll-view :scroll-y="true" class="content">
				<view v-for="(item, index) in teamList" :key="index" class="item">
					<view class="f1">
						<image :src="item.headimg"></image>		
						<image :src="pre_url+'/static/img/wh.png'" class="img1"></image>		
					</view>
					<view class="f2">
						<view class="t1">还差{{item.teamnum - item.num}}人拼成</view>
						<view class="t2">剩余{{item.djs}}</view>
					</view>
					<button class="f3" @tap="buydialogShow" data-btntype="3" :data-teamid="item.id" style="white-space: nowrap;">去参团</button>
				</view>
			</scroll-view>
		</view>

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
		

		<view style="width:100%;height:70px;"></view>

		<view class="bottombar flex-row" :class="menuindex>-1?'tabbarbot':'notabbarbot'" v-if="product.status==1">
			<view class="f1">
				<view class="item" @tap="goto" :data-url="'prolist?bid='+product.bid">
					<image class="img" :src="pre_url+'/static/img/shou.png'"/>
					<view class="t1">首页</view>
				</view>
				<view class="item  " @tap="goto" :data-url="kfurl" v-if="kfurl!='contact::'">
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
				<block v-if="product.isopen_buy == 1">
				<view class="tocart" :style="{background:t('color2')}" @tap="buydialogShow" data-btntype="1"><text>￥{{product.market_price}}</text><text>单独购买</text></view>
				<view class="tobuy" :style="{background:t('color1')}" @tap="buydialogShow" data-btntype="2"><text>￥{{product.sell_price}}</text><text>发起拼团</text></view></block>
				<block v-else>
					<view class="tobuy2" :style="{background:t('color1')}" @tap="buydialogShow" data-btntype="2"><text>￥{{product.sell_price}}</text><text>发起拼团</text></view>
				</block>
			</view>
		</view>


		<view :hidden="buydialogHidden">
			<view class="buydialog-mask">
				<view class="buydialog" :class="menuindex>-1?'tabbarbot':'notabbarbot'">
					<view class="close" @tap="buydialogChange">
						<image :src="pre_url+'/static/img/close.png'" class="image"></image>
					</view>
					<view class="title">
						<image :src="guigelist[ks].pic?guigelist[ks].pic:product.pic" class="img" @tap="previewImage" :data-url="guigelist[ks].pic?guigelist[ks].pic:product.pic"></image>
						<!-- <text class="name">{{product.name}}</text> -->
						<view class="price" v-if="btntype==1"><text class="t1">￥</text>{{guigelist[ks].market_price}}</view>
						<view class="price" v-else><text class="t1">￥</text>{{guigelist[ks].sell_price}} <text v-if="guigelist[ks].market_price > guigelist[ks].sell_price" class="t2">￥{{guigelist[ks].market_price}}</text></view>
						<view class="choosename">已选规格: {{guigelist[ks].name}}</view>
						<view class="stock">剩余{{guigelist[ks].stock}}件</view>
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
							<input class="input" type="number" :value="gwcnum" @input="gwcinput"></input>
							<view class="plus"><image class="img" :src="pre_url+'/static/img/cart-plus.png'" @tap="gwcplus"/></view>
						</view>
					</view>
					<view class="op">
						<block v-if="btntype==1">
							<button class="tobuy" :style="{background:t('color2')}" @tap="tobuy" data-type="1">确定</button>
						</block>
						<block v-if="btntype==2">
							<button class="tobuy" :style="{background:t('color1')}" @tap="tobuy" data-type="2">下一步</button>
						</block>
						<block v-if="btntype==3">
							<button class="tobuy" :style="{background:t('color1')}" @tap="tobuy" data-type="3">确 定</button>
						</block>
					</view>
				</view>
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
			pre_url:app.globalData.pre_url,
			indexurl:app.globalData.indexurl,
			tabnum: 1,
      buydialogHidden: true,
      num: 1,
			teamCount:0,
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
      teamList: [],
      teamid: "",
      sharetypevisible: false,
      showposter: false,
      posterpic: "",
			kfurl:'',
			test:'',
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
		getdata: function () {
			var that = this;
			var id = that.opt.id;
			that.loading = true;
			app.get('ApiLuckyCollage/product', {id: id}, function (res) {
				that.loading = false;
				if (res.status == 0) {
					app.alert(res.msg, function(){
						if(res.url){
							app.goto(res.url);
						}
					});
					
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
				that.teamCount = res.teamCount;
				that.teamList = res.teamList;
				setInterval(function () {
					that.nowtime = that.nowtime + 1;
					that.getdjs();
				}, 1000);
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
			});
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
    getdjs: function () {
      var that = this;
      var nowtime = that.nowtime;
      for (var i in that.teamList) {
        var thisteam = that.teamList[i];
        var totalsec = thisteam.createtime * 1 + thisteam.teamhour * 3600 - nowtime * 1;
        if (totalsec <= 0) {
          that.teamList[i].djs = '00时00分00秒';
        } else {
          var houer = Math.floor(totalsec / 3600);
          var min = Math.floor((totalsec - houer * 3600) / 60);
          var sec = totalsec - houer * 3600 - min * 60;
          var djs = (houer < 10 ? '0' : '') + houer + '时' + (min < 10 ? '0' : '') + min + '分' + (sec < 10 ? '0' : '') + sec + '秒';
        }
				that.teamList[i].djs = djs;
				that.test = Math.random();
      }
    },
    //加入购物车
    buydialogShow: function (e) {
      var btntype = e.currentTarget.dataset.btntype;
      if (btntype == 3) {
        this.teamid = e.currentTarget.dataset.teamid
      }
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
      var ggid = that.guigelist[ks].id;
      var num = that.gwcnum;
			var shareid = that.opt.pid;
      app.goto('buy?proid=' + proid + '&ggid=' + ggid + '&num=' + num + '&shareid='+shareid+'&buytype=' + type + (type == 3 ? '&teamid=' + that.teamid : ''));
    },
    //加
    gwcplus: function (e) {
      var gwcnum = this.gwcnum + 1;
      var ggselected = this.ks;
      if (gwcnum > this.guigelist[ggselected].stock) {
        app.error('库存不足');
        return;
      }
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
      app.post('ApiLuckyCollage/addfavorite', {proid: proid,type: 'luckycollage'}, function (data) {
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
			app.post('ApiLuckyCollage/getposter', {proid: that.product.id}, function (data) {
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
						sharedata.href = app.globalData.pre_url +'/h5/'+app.globalData.aid+'.html#/activity/luckycollage/product?scene=id_'+that.product.id+'-pid_' + app.globalData.mid;
						sharedata.imageUrl = that.product.pic;
						var sharelist = app.globalData.initdata.sharelist;
						if(sharelist){
							for(var i=0;i<sharelist.length;i++){
								if(sharelist[i]['indexurl'] == '/activity/luckycollage/product'){
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
		}
	}
}
</script>
<style>
.swiper-container{position:relative}
.swiper {width: 100%;height: 750rpx;overflow: hidden;}
.swiper-item-view{width: 100%;height: 750rpx;}
.swiper .img {width: 100%;height: 750rpx;overflow: hidden;}

.imageCount {width:100rpx;height:50rpx;background-color: rgba(0, 0, 0, 0.3);border-radius:40rpx;line-height:50rpx;color:#fff;text-align:center;font-size:26rpx;position:absolute;right:13px;bottom:20rpx;}
.containerbox{ position: relative;}

.header {width: 95%;padding: 0 3%;background: #fff; margin:0 20rpx;border-radius: 0px 0px 16rpx 16rpx;}
.header .title {padding: 10px 0px;line-height:44rpx;font-size:32rpx;display:flex;}
.header .title .lef{display:flex;flex-direction:column;justify-content: center;flex:1;color:#222222;font-weight:bold}
.header .title .lef .t2{ font-size:26rpx;color:#999;padding-top:10rpx;font-weight:normal}
.header .title .share{width:88rpx;height:88rpx;padding-left:20rpx;border-left:0 solid #f5f5f5;text-align:center;font-size:24rpx;color:#222;display:flex;flex-direction:column;align-items:center}
.header .title .share image{width:32rpx;height:32rpx;margin-bottom:4rpx}

.header .price{height: 86rpx;overflow: hidden;line-height: 86rpx;border-top: 1px solid #eee;}
.header .price .t1 .x1{ color: #e94745; font-size: 34rpx;}
.header .price .t1 .x2{ color: #939393; margin-left: 10rpx; text-decoration: line-through;font-size:24rpx}
.header .price .t2{color: #aaa; font-size: 24rpx;}
.header .fuwupoint{width:100%;font-size:24rpx;color:#999;display:flex;flex-wrap:wrap;border-top:1px solid #eee;padding:10rpx 0}
.header .fuwupoint .t{ padding:4rpx 20rpx 4rpx 0}
.header .fuwupoint .t:before{content: "";	display: inline-block;	vertical-align: middle;	margin-top: -4rpx;	margin-right: 10rpx;	width: 24rpx;	height: 24rpx;	background: url('data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABgAAAAYBAMAAAASWSDLAAAABGdBTUEAALGPC/xhBQAAAAFzUkdCAK7OHOkAAAAwUExURUdwTOU5O+Q5POU5POQ4O+U4PN80P+M4O+Q4O+Q4POQ5POQ4OuQ4O+Q4O+I4PuQ5PJxkAycAAAAPdFJOUwAf+VSoeAvzws7ka7miLboUzckAAADJSURBVBjTY2BgYGCMWVR5VIABDBid/gPBFwjP/JOzQKKtfjGIzf3fEUSJ/N8AJO21Iao3fQbqqA+AcLi/CzCwfGGAAn8HBnlFMIttBoP4R4b4C2BOzk8G3q8M5w3AnPsLGZj/MKwHW8b6/QED4y8G/QQQx14ZSHwCcWYkMOtvAHOAyvqnPf8KcuMvkAGZP9eDjAQaEO/AwDb/D0gj0GiQpRnTQIYIfUR1DopDGexVIZygz8ieC4B6WyzRBOJtBkZ/pAABBZUWOKgAispF5e7ibycAAAAASUVORK5CYII=') no-repeat;	background-size: 24rpx auto; }
.choose{ display:flex;align-items:center;width: 100%; background: #fff;  margin-top: 20rpx; height: 80rpx; line-height: 80rpx; padding: 0 3%; color: #505050; }
.choose .f2{ width: 40rpx; height: 40rpx;}

.teamlist{ width:95%;margin:0 20rpx;border-radius: 16rpx;   background:#fff;padding:10rpx 20rpx;font-size:26rpx;margin-top:20rpx;display:flex;flex-direction:column}
.teamlist .label{ padding-left: 30rpx; height: 100rpx; position: relative; width:100%;color:#222222;font-weight:bold; line-height: 80rpx;}
.teamlist .label .after{display:block;position:absolute;border-right:10rpx solid red;top:20rpx;left:10rpx;height:36rpx; border-radius:1.5px;}
.teamlist .content{ width:100%;max-height:300rpx;overflow:scroll}
.teamlist .item{width:100%;display:flex;align-items:center;padding:12rpx 3px;border-bottom:0px solid #f5f5f5}
.teamlist .item .f1{overflow:hidden;display:flex;align-items:center}
.teamlist .item .f1 image{width:80rpx;height:80rpx; border-radius: 50%;}
.teamlist .item .f1 .img1{ position: absolute;left:10%; background: #D9D9D9; }
.teamlist .item .f1 .t1{padding-left:6rpx;font-size:30rpx;color:#333}
.teamlist .item .f2{ margin:0 8rpx; width: 160px; margin-left: 50px;}
.teamlist .item .f2 .t1{font-size:24rpx;color:#333}
.teamlist .item .f2 .t2{font-size:22rpx;color:#999}
.teamlist .item .f3{ background: linear-gradient(90deg, #FF3143 0%, #FE6748 100%);color:#fff;border-radius:30rpx;padding:0 30rpx;height:60rpx;border:0;text-align:right;font-size:26rpx;display:flex;align-items:center}
.teamlist .item .f3:after{border:0}


.shop{width:95%;margin:0 20rpx;display:flex;align-items:center; border-radius: 16rpx; background: #fff;  margin-top: 20rpx; padding: 20rpx 3%;position: relative; min-height: 136rpx;}
.shop .p1{width:90rpx;height:90rpx;border-radius:50%;flex-shrink:0}
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

.bottombar{ width: 96%; position: fixed;bottom: 0px; left: 0px; background: #fff;display:flex;height:120rpx;padding:0 2% 0 2%;align-items:center;box-sizing:content-box}
.bottombar .f1{flex:1;display:flex;align-items:center;margin-right:10rpx}
.bottombar .f1 .item{display:flex;flex-direction:column;align-items:center;width:90rpx;position:relative}
.bottombar .f1 .item .img{ width:44rpx;height:44rpx}
.bottombar .f1 .item .t1{font-size:18rpx;color:#222222;height:30rpx;line-height:30rpx;margin-top:6rpx}

.bottombar .op{width:60%;border-radius:36rpx;overflow:hidden;display:flex;}
.bottombar .tocart{ width:50%; height: 100rpx;color: #fff; border-radius: 88rpx 0px 0px 88rpx;background: #fa938a; font-size: 28rpx;display:flex;flex-direction:column;align-items:center;justify-content:center}
.bottombar .tobuy{ width:50%; height: 100rpx;color: #fff; border-radius: 0px 88rpx 88rpx 0px;background: #df2e24; font-size:28rpx;display:flex;flex-direction:column;align-items:center;justify-content:center}
.bottombar .tobuy2{ width:100%; height: 100rpx;color: #fff; border-radius: 88rpx 88rpx 88rpx 88rpx;background: #df2e24; font-size:28rpx;display:flex;flex-direction:column;align-items:center;justify-content:center}


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
.buydialog .addnum .plus {width:76rpx;height:66rpx;background:#F6F8F7;display:flex;align-items:center;justify-content:center}
.buydialog .addnum .minus {width:76rpx;height:66rpx;background:#F6F8F7;display:flex;align-items:center;justify-content:center}
.buydialog .addnum .img{width:30rpx;height:30rpx}
.buydialog .addnum .input{flex:1;width:70rpx;border:0;text-align:center;color:#2B2B2B;font-size:28rpx}
.buydialog .op{width:90%;margin:20rpx 5%;border-radius:36rpx;overflow:hidden;display:flex;margin-top:100rpx;}
.buydialog .addcart{flex:1;height:72rpx; line-height: 72rpx;color: #fff; border-radius: 0px; border: none; font-size:28rpx;font-weight:bold}
.buydialog .tobuy{flex:1;height: 72rpx; line-height: 72rpx;color: #fff; border-radius: 0px; border: none;}
.buydialog .nostock{flex:1;height: 72rpx; line-height: 72rpx; background:#aaa; color: #fff; border-radius: 0px; border: none;}

.collage_title{width:95%;height:110rpx; position: absolute; bottom: 0; background-image: url('https://v2d.diandashop.com/static/img/xypt_bg.png'); background-size: 100%; display:flex;align-items:center;padding:0 40rpx; margin:0 20rpx;}
.collage_title .f1{flex:1;display:flex;flex-direction:column;}
.collage_title .f1 .t1{display:flex;align-items:center;height:60rpx;line-height:60rpx; margin-top: 20rpx;}
.collage_title .f1 .t1 .x1{font-size:28rpx;color:#fff}
.collage_title .f1 .t1 .x2{font-size:48rpx;color:#fff;padding-right:20rpx}
.collage_title .f1 .t1 .x3{margin-left: 60rpx;font-size:24rpx;font-weight:bold;height:40rpx;line-height:40rpx;padding:0 20rpx;border-radius:20rpx;background:#fff;color:#ED533A}
.collage_title .f1 .t2{color:rgba(255,255,255,0.6);font-size:20rpx;text-decoration: line-through;margin-top: 10rpx;}
.collage_title .f2{color:#fff;font-size:28rpx;}

.title2{ align-items: center; padding-bottom: 20rpx;}
.title2 .p1{ font-size: 20rpx;border-bottom-right-radius:40rpx; margin: auto; width: 30%;padding:30rpx 0; text-align: center; border: 1rpx solid #FF3143; border: 1px solid rgba(255, 49, 67, 0.2); margin-right: 10rpx;}
.title2 .t1{ line-height: 40rpx;}
.title2 .t1 .t1_1{ color:#FF3143}
.title2 image{ width: 80rpx; height: 80rpx;}
</style>