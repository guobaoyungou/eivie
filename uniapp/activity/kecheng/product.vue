<template>
<view>
	<block v-if="isload">
		<view class="swiper-container"  >
			<swiper class="swiper" :indicator-dots="false" :autoplay="true" :interval="500000" @change="swiperChange" :current="current" :style="{ height: swiperHeight + 'px' }">
				<block v-for="(item, index) in product.pics" :key="index">
					<swiper-item class="swiper-item">
						<view class="swiper-item-view"><image class="img" :src="item" mode="widthFix"  @load="loadImg"/></view>
					</swiper-item>
				</block>
			</swiper>
			<view class="imageCount">{{current+1}}/{{(product.pics).length}}</view>
		</view>
		<view class="header"> 
			<view class="price_share">
				<view class="title">{{product.name}}</view>
        <view class="share" @tap="shareClick"><image class="img" :src="pre_url+'/static/img/share.png'"/><text class="txt">分享</text></view>
			</view>
			<view class="pricebox flex">
				<view class="price">
					<view class="f1" v-if="product.price>0" :style="{color:t('color1')}">
						￥<text style="font-size:36rpx">{{product.price}}</text>
					</view>
					<view class="f1" v-else :style="{color:t('color1')}">
						<text style="font-size:36rpx">免费</text>
					</view>
					<view class="f2"  v-if="product.market_price>0">￥{{product.market_price}}</view>
					
				</view>
				<view v-if="!product.chaptertype || product.chaptertype==1" class="sales_stock">
					<view class="f1">{{product.count}}节课<block v-if="sysset && sysset.show_join_num == 1"><text style="margin: 0 6rpx;">|</text>已有{{product.join_num}}人学习</block> </view>
				</view>	
			</view>
			<view class="commission" :style="{background:'rgba('+t('color1rgb')+',0.1)',color:t('color1')}" v-if="kechengset.showcommission==1 && product.commission > 0">分享好友购买预计可得{{t('佣金')}}：<text style="font-weight:bold;padding:0 2px">{{product.commission}}</text>{{product.commission_desc}}</view>
			<view class="upsavemoney" :style="{background:'linear-gradient(90deg, rgb(255, 180, 153) 0%, #ffcaa8 100%)',color:'#653a2b'}" v-if="kechengset.show_lvupsavemoney==1 &&product.upgrade_text && product.price > 0">
				<!-- <view class="flex1">升级到 {{product.nextlevelname}} 预计可节省<text style="font-weight:bold;padding:0 2px;color:#ca4312">{{product.upsavemoney}}</text>元</view> -->
				<view class="flex1">{{product.upgrade_text}} </view>
				<!-- <view style="margin-left:20rpx;font-weight:bold;display:flex;align-items:center;color:#ca4312" @tap="goto" data-url="/pagesExt/my/levelup">立即升级<image :src="pre_url+'/static/img/arrowright2.png'" style="width:30rpx;height:30rpx"/></view> -->
				<view style="margin-left:20rpx;font-weight:bold;display:flex;align-items:center;color:#ca4312" @tap="goto" data-url="/pagesExt/my/levelup"><image :src="pre_url+'/static/img/arrowright2.png'" style="width:30rpx;height:30rpx"/></view>
			</view> 
		</view>		
		
		<view v-if="!product.chaptertype || product.chaptertype==1" class="detail">
			<view class="detail_title">
				<view class="order-tab2">
					<view :class="'item ' + (curTopIndex == 1 ? 'on' : '')" @tap="switchTopTab" :data-index="1" >课程介绍<view class="after" :style="{background:t('color1')}"></view></view>
					<view :class="'item ' + (curTopIndex == 2 ? 'on' : '')" @tap="switchTopTab" :data-index="2" >课程目录<view class="after" :style="{background:t('color1')}"></view></view>
				</view>
			</view>
			<block v-if="curTopIndex==1"><dp :pagecontent="pagecontent"></dp></block>
			<block v-if="curTopIndex==2">
				<view class="mulubox-container" v-for="(item, index) in datalist" :key="index">
					<view class="mulubox flex" @tap="todetail" :data-mianfei='item.ismianfei' :data-url="'mldetail?id='+item.id+'&kcid='+item.kcid" :data-index='index'>
						<view class="left_box" v-if="item.children && item.children.length > 0" >
							<image :src="pre_url+'/static/img/arrowdown.png'" style="width:24rpx;height:24rpx;" :style="item.expanded && item.children && item.children.length > 0 ? '' :'transform: rotate(-90deg);'" />
						</view>
						<view class="left_box" v-else>
							<image v-if="item.kctype==1" :src="pre_url+'/static/img/tw_icon.png'" /> 
							<image v-if="item.kctype==2" :src="pre_url+'/static/img/mp3_icon.png'" />
							<image v-if="item.kctype==3" :src="pre_url+'/static/img/video_icon.png'" /> 
						</view>
						<view class="right_box flex">
							<view class="title_box">
								<view class="t1"> {{item.name}}</view>
								<view> <text  v-if="item.kctype==1"  class="t2">图文课程 </text>
									<text v-if="item.kctype==2"  class="t2">音频课程 </text>
									<text v-if="item.kctype==3"  class="t2">视频课程 </text>
									<text  v-if="item.kctype!=1" class="t2"> 时长: {{item.duration?item.duration:'未知'}}</text>
								</view>
							</view>
							<view class="skbtn" v-if="item.ismianfei && product.price>0">试看</view>
							<view class="skbtn" v-if="product.price==0">免费</view>
						</view>	
					</view>
					<!-- 子章节 -->
					<view v-if="item.expanded && item.children && item.children.length > 0" :class="['children-container', item.expanded && item.children && item.children.length > 0 ? 'expanded' : '']">
						<view class="child-item" v-for="(child, childIndex) in item.children" :key="child.id" @tap="todetail" :data-mianfei='child.ismianfei' :data-url="'mldetail?id='+child.id+'&kcid='+child.kcid">
							<view class="mulubox flex">
								<view class="left_box">
									<view style="width: 40rpx; height: 1px;"></view>
									<image v-if="child.kctype==1" :src="pre_url+'/static/img/tw_icon.png'" style="margin-left: 40rpx;" /> 
									<image v-if="child.kctype==2" :src="pre_url+'/static/img/mp3_icon.png'" style="margin-left: 40rpx;" />
									<image v-if="child.kctype==3" :src="pre_url+'/static/img/video_icon.png'" style="margin-left: 40rpx;" /> 
								</view>
								<view class="right_box flex">
									<view class="title_box">
										<view class="t1"> {{child.name}}</view>
										<view> 
											<text v-if="child.kctype==1" class="t2">图文课程 </text>
											<text v-if="child.kctype==2" class="t2">音频课程 </text>
											<text v-if="child.kctype==3" class="t2">视频课程 </text>
											<text v-if="child.kctype!=1" class="t2"> 时长: {{child.duration?child.duration:'未知'}}</text>
										</view>
									</view>
									<view class="skbtn" v-if="child.ismianfei && product.price>0">试看</view>
									<view class="skbtn" v-if="product.price==0">免费</view>
								</view>
							</view>
						</view>
					</view>
				</view>
				<view class="more" v-if="kechengset.details_rec==1 && !nomore" @tap="showmore"> 点击查看更多 </view>
				<nomore text="没有更多课程了" v-if="nomore"></nomore>
				<nodata text="没有查找到相关课程" v-if="nodata"></nodata>
			</block>
		</view>
    <view v-else-if="product.freecontent" class="detail">
      <view style="padding: 20rpx;font-size: 28rpx;">
       <parse :content="product.freecontent"></parse>
      </view>
    </view>
		
		<view>
		<view class="xihuan" v-if="tjdatalist.length > 0">
				<view class="xihuan-line"></view>
				<view class="xihuan-text">
					<image :src="pre_url+'/static/img/xihuan.png'" class="img"/>
					<text class="txt">为您推荐</text>
				</view>
				<view class="xihuan-line"></view>
			</view>
			<view class="prolist">
				<dp-kecheng-item :data="tjdatalist" @addcart="addcart" :menuindex="menuindex"></dp-kecheng-item>
			</view>
		</view>
		
		<view style="width:100%;height:140rpx;"></view>
		<view class="bottombar flex-row" :class="menuindex>-1?'tabbarbot':'notabbarbot'" v-if="product.status==1">
			<view class="f1" style="width:100%;flex: 1;">
				<view class="item" @tap="goto" :data-url="'/pages/index/index'">
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
			<view class="op" style="width: 100%;flex: 1;">
        <block v-if="product.price==0 || product.ispay==1">
          <view  class="tobuy">
            <block v-if="!product.chaptertype || product.chaptertype==1" >
              <view class="tobuy flex-x-center flex-y-center" @tap="goto" :data-url="'mldetail?kcid='+product.id" :style="{background:t('color1')}" >立即学习</view>
            </block>
            <block v-else>
              <view class="tobuy flex-x-center flex-y-center" @tap="goto" :data-url="'/pagesB/kecheng/lecturermldetail?kcid='+product.id" :style="{background:t('color1')}" >立即查看</view>
            </block>
          </view>
        </block>
        <block v-else>
          <view v-if="!product.chaptertype || product.chaptertype==1" class="tobuy flex-x-center flex-y-center" @tap="tobuy" :style="{background:t('color1')}" >立即购买</view>
          <view v-else class="tobuy flex-x-center flex-y-center" @tap="goto" :data-url="'/pagesB/kecheng/lecturermldetail?kcid='+product.id" :style="{background:t('color1')}" >立即查看</view>
        </block>
			</view>
		</view>
		<scrolltop :isshow="scrolltopshow"></scrolltop>
		<view v-if="sharetypevisible" class="popup__container">
			<view class="popup__overlay" @tap.stop="handleClickMask"></view>
			<view class="popup__modal" style="height:320rpx;min-height:320rpx">

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
			isload:false,
			isfavorite: false,
			current: 0,
			product: [],
			pagecontent: "",
			title: "",
			sharepic: "",
			sharetypevisible: false,
			showposter: false,
			posterpic: "",
			scrolltopshow: false,
			kfurl:'',
			timeDialogShow: false,
			curTopIndex: 1,
			datalist: [],
			tjdatalist:[],
			kechengset: {},
			business:{},
			sysset:{},
			pre_url:app.globalData.pre_url,
			nodata: false,
			swiperHeight: '',
			nomore:false,
      osname:'',
		};
	},
  onLoad: function (opt) {
    var that = this;
    uni.getSystemInfo({
    	success: function(res) {
    		that.osname = res.osName;
    	}
    })
		that.opt = app.getopts(opt);
		that.getdata();
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
	onReachBottom: function () {
		if (!this.nodata && !this.nomore) {
			this.pagenum = this.pagenum + 1;
			this.getdatalist(true);
		}
	},

	methods: {
		getdata:function(){
			var that = this;
			var id = this.opt.id || 0;
			that.loading = true;
			app.get('ApiKecheng/detail', {id: id}, function (res) {
				that.loading = false;
				if (res.status == 0) {
					app.alert(res.msg);
					return;
				}
				that.textset = app.globalData.textset;
				var product = res.product;
				var pagecontent = JSON.parse(product.detail);
				that.product = product;
				that.pagecontent = pagecontent;
				that.title = product.name;
				that.isfavorite = res.isfavorite;
				that.sharepic = product.pics[0];
				that.tjdatalist = res.tjdatalist;
				that.kechengset = res.kechengset;
				that.business = res.business;
				that.sysset = res.sysset;
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
		showmore:function(e){
			if (!this.nodata && !this.nomore) {
				this.pagenum = this.pagenum + 1;
				this.getdatalist(true);
			}
		},
		swiperChange: function (e) {
			var that = this;
			that.current = e.detail.current
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
		  this.curTopIndex = e.currentTarget.dataset.index;
		  this.getdatalist();
		},
		getdatalist: function (loadmore) {
			if(!loadmore){
				this.pagenum = 1;
				this.datalist = [];
			}
			var that = this;
			var pagenum = that.pagenum;
			var id = that.opt.id ? that.opt.id : '';
			var order = that.order;
			var field = that.field; 
			that.loading = true;
			that.nodata = false;
			that.nomore = false;
			app.post('ApiKecheng/getmululist', {pagenum: pagenum,field: field,order: order,id:id}, function (res) { 
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
		todetail:function(e){
			var that = this;
		    var url = e.currentTarget.dataset.url;
			var ismf = e.currentTarget.dataset.mianfei;
			var index = e.currentTarget.dataset.index;
			var item = that.datalist[index] || '';
			if(item && item.children && item.children.length > 0){
				that.datalist[index].expanded = !item.expanded;
				return;
			}
			if(ismf==1 || that.product.ispay==1 || that.product.price==0){
				app.goto(url);
			}else{
				app.error('请先购买课程');
			}
		},
		//收藏操作
		addfavorite: function () {
			var that = this;
			var proid = that.product.id;
			app.post('ApiKecheng/addfavorite', {proid: proid,type: 'kecheng'}, function (data) {
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
			app.post('ApiKecheng/getposter', {proid: that.product.id}, function (data) {
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
		sharemp:function(){
			app.error('点击右上角发送给好友或分享到朋友圈');
			this.sharetypevisible = false
		},
		shareapp:function(){
			// #ifdef APP || APP-PLUS
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
						sharedata.href = app.globalData.pre_url +'/h5/'+app.globalData.aid+'.html#/activity/kecheng/product?scene=id_'+that.product.id+'-pid_' + app.globalData.mid;
						sharedata.imageUrl = that.product.pic;
						var sharelist = app.globalData.initdata.sharelist;
						if(sharelist){
							for(var i=0;i<sharelist.length;i++){
								if(sharelist[i]['indexurl'] == '/activity/kecheng/product'){
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
			app.showLoading();
			var that=this;
      var kechengset = that.kechengset;
      if(that.osname == 'ios' && !kechengset.ios_canbuy){
        app.alert(kechengset.ios_tip);
        return;
      }

			//购买
			app.post('ApiKecheng/createOrder', {
				kcid:that.product.id,
			}, function(res) {
				app.showLoading(false);
				if (res.status == 0) {
					app.error(res.msg);
					return;
				}
				app.goto('/pagesExt/pay/pay?id=' + res.payorderid);
			});
		},	 
		loadImg() {
			console.log(222);
			this.getCurrentSwiperHeight('.img');
		},
		swiperChange: function (e) {
			var that = this;
			that.current = e.detail.current
			
			//动态设置swiper的高度，使用nextTick延时设置
			this.$nextTick(() => {
			  this.getCurrentSwiperHeight('.img');
			});
		},
		// 动态获取内容高度
	  getCurrentSwiperHeight(element) {
	      let query = uni.createSelectorQuery().in(this);
	      query.selectAll(element).boundingClientRect();
				var imgList = this.product.pics;
				console.log(imgList)
	      query.exec((res) => {
	        // 切换到其他页面swiper的change事件仍会触发，这时获取的高度会是0，会导致回到使用swiper组件的页面不显示了
	        if (imgList.length && res[0][this.current].height) {
	          this.swiperHeight = res[0][this.current].height;
	        }
	      });	
		},
		// 展开/收起子章节
		toggleExpand: function(item) {
			this.$set(item, 'expanded', !item.expanded);
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

.goback{ position: absolute; top:0 ;width:64rpx ; height: 64rpx;z-index: 10000; margin: 30rpx;}
.goback img{ width:64rpx ; height: 64rpx;}

.swiper-container{position:relative}
.swiper {width: 100%;height: 420rpx;overflow: hidden;}
.swiper-item-view{width: 100%;height: 750rpx;}
.swiper .img {width: 100%;height: 750rpx;overflow: hidden;}

.imageCount {width:100rpx;height:50rpx;background-color: rgba(0, 0, 0, 0.3);border-radius:40rpx;line-height:50rpx;color:#fff;text-align:center;font-size:26rpx;position:absolute;right:13px;bottom:20rpx;}

.provideo{background:rgba(255,255,255,0.7);width:160rpx;height:54rpx;padding:0 20rpx 0 4rpx;border-radius:27rpx;position:absolute;bottom:30rpx;left:50%;margin-left:-80rpx;display:flex;align-items:center;justify-content:space-between}
.provideo image{width:50rpx;height:50rpx;}
.provideo .txt{flex:1;text-align:center;padding-left:10rpx;font-size:24rpx;color:#333}

.videobox{width:100%;height:750rpx;text-align:center;background:#000}
.videobox .video{width:100%;height:650rpx;}
.videobox .parsevideo{margin:0 auto;margin-top:20rpx;height:40rpx;line-height:40rpx;color:#333;background:#ccc;width:140rpx;border-radius:25rpx;font-size:24rpx;}

.header {padding: 20rpx 3%;background: #fff; width: 100%; border-radius:10rpx; margin: auto; margin-bottom: 20rpx; position: relative;}
.header .price_share{width:100%;height:100rpx;display:flex;align-items:center;justify-content:space-between}
.header .price_share .price{display:flex;align-items:flex-end}
.header .price_share .price .f1{font-size:50rpx;color:#51B539;font-weight:bold}
.header .price_share .price .f2{font-size:26rpx;color:#C2C2C2;text-decoration:line-through;margin-left:30rpx;padding-bottom:5px}
.header .price_share .share{display:flex;flex-direction:column;align-items:center;justify-content:center;flex-shrink: 0;}
.header .price_share .share .img{width:32rpx;height:32rpx;margin-bottom:2px}
.header .price_share .share .txt{color:#333333;font-size:20rpx}
.header .title {color:#000000;font-size:32rpx;line-height:42rpx;font-weight:bold;}
.header .sellpoint{font-size:28rpx;color: #666;padding-top:20rpx;}
.header .sales_stock{height:60rpx;line-height:60rpx;font-size:24rpx;color:#BBB; }
.header .commission{display:inline-block;margin-top:20rpx;margin-bottom:10rpx;border-radius:10rpx;font-size:20rpx;height:44rpx;line-height:44rpx;padding:0 20rpx}
.header .upsavemoney{display:flex;align-items:center;margin-top:20rpx;margin-bottom:10rpx;border-radius:10rpx;font-size:20rpx;height:70rpx;padding:0 20rpx}

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
.shop .p2 .t2{width: 100%;height:30rpx;line-height:30rpx;overflow: hidden;color: #999;font-size:24rpx;margin-top:8rpx;}
.shop .p4{height:64rpx;line-height:64rpx;color:#FFFFFF;border-radius:32rpx;margin-left:20rpx;flex-shrink:0;padding:0 30rpx;font-size:24rpx;font-weight:bold}

.detail{min-height:200rpx; width: 100%; margin: auto; border-radius: 10rpx; background: #fff;}

.detail_title{width:100%;display:flex;align-items:center;justify-content:center;margin-top:40rpx}
.detail_title .t0{font-size:28rpx;font-weight:bold;color:#222222;margin:0 20rpx}
.detail_title .t1{width:12rpx;height:12rpx;background:rgba(253, 74, 70, 0.2);transform:rotate(45deg);margin:0 4rpx;margin-top:6rpx}
.detail_title .t2{width:18rpx;height:18rpx;background:rgba(253, 74, 70, 0.4);transform:rotate(45deg);margin:0 4rpx}

.bottombar{ width: 94%; position: fixed;bottom: 0px; left: 0px; background: #fff;display:flex;height:100rpx;padding:0 4% 0 2%;align-items:center;box-sizing:content-box}
.bottombar .f1{flex:1;display:flex;align-items:center;justify-content: space-between;}
.bottombar .f1 .item{display:flex;flex-direction:column;align-items:center;width:33.3%;position:relative}
.bottombar .f1 .item .img{ width:44rpx;height:44rpx}
.bottombar .f1 .item .t1{font-size:18rpx;color:#222222;height:30rpx;line-height:30rpx;margin-top:6rpx}
.bottombar .op{width:60%;border-radius:36rpx;overflow:hidden;display:flex;}
.bottombar .tocart{flex:1;height:72rpx; line-height: 72rpx;color: #fff; border-radius: 0px; border: none;font-size:28rpx;font-weight:bold}
.bottombar .tobuy{flex:1;height: 72rpx; line-height: 72rpx;color: #fff; border-radius: 0px; border: none;font-size:28rpx;font-weight:bold}
.bottombar .cartnum{position:absolute;right:4rpx;top:-4rpx;color:#fff;border-radius:50%;width:32rpx;height:32rpx;line-height:32rpx;text-align:center;font-size:22rpx;}

.pricebox{ width: 100%;border:1px solid #fff; justify-content: space-between;}
.pricebox .price{display:flex;align-items:flex-end}
.pricebox .price .f2{font-size:26rpx;color:#C2C2C2;text-decoration:line-through;margin-left:30rpx;padding-bottom:4rpx}

.order-tab2{display:flex;width:auto;min-width:100%; padding-top: 10rpx; border-bottom: 1px solid #F7F7F7;}
.order-tab2 .item{width:20%;padding:0 20rpx;font-size:28rpx;font-weight:bold;text-align: center; color:#999999; height:80rpx; line-height:80rpx; overflow: hidden;position:relative;flex-shrink:0;flex-grow: 1;}
.order-tab2 .on{color:#222222;}
.order-tab2 .after{display:none;position:absolute;left:47%;margin-left:-20rpx;bottom:0rpx;height:6rpx;border-radius:1.5px;width:60rpx}
.order-tab2 .on .after{display:block}

.mulubox{ padding-top: 35rpx; padding-left: 30rpx;}
.left_box{ display: flex;}
.left_box image{ width: 44rpx; height:44rpx; margin-right: 40rpx; margin-top: 26rpx; }
.right_box{ border-bottom: 1px solid #F6F6F6; padding-bottom: 30rpx; width: 100%; justify-content: space-between;}
.title_box .t1{ color: #1E252F; font-size: 28rpx; font-weight: bold;}
.title_box .t2{ color: #B8B8B8;font-size: 24rpx;line-height: 60rpx; margin-right: 15rpx;}
.skbtn{  background-color: #FFEEEC; text-align: center; margin-right: 10px; height: 44rpx; width: 95rpx; color: #FC6D65; font-size: 24rpx; line-height: 40rpx; border-radius: 22rpx; margin-top: 20rpx;}
.xihuan{height: auto;overflow: hidden;display:flex;align-items:center;width:100%;padding:12rpx 160rpx}
.xihuan-line{height: auto; padding: 0; overflow: hidden;flex:1;height:0;border-top:1px solid #eee}
.xihuan-text{padding:0 32rpx;text-align:center;display:flex;align-items:center;justify-content:center}
.xihuan-text .txt{color:#111;font-size:30rpx}
.xihuan-text .img{text-align:center;width:36rpx;height:36rpx;margin-right:12rpx}
.prolist{width: 100%;height:auto;padding: 8rpx 20rpx;}

.more{text-align: center; height:80rpx;line-height: 80rpx;color: #999;}

/* 子章节样式 */
.child-item .mulubox{padding-left: 0;}
.expand-icon{padding-left: 20rpx;flex-shrink: 0;}
.children-container {display: block;overflow: hidden;transition: max-height 0.3s ease-out;}
</style>