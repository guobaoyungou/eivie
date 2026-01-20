<template>
<view>
	<block v-if="isload">
		<view class="container">
			<view class="datalist">
				<block v-for="(item, index) in datalist" :key="index">
				<view @tap="toDetail" :data-id="item.id" class="collage-product">
					<view class="product-pic">
						<image :src="item.pic" mode="widthFix"></image>
					</view> 
					<view class="product-info">
						<view class="p1">{{item.name}}</view>
						<view class="p3">
							<view class="t1">活动人数<text style="font-size:32rpx;color:#f40;padding:0 2rpx;">{{item.total_num}}</text>人</view>
						</view>
						<view class="p2 flex flex-y-center">
							<view class='fun-class invite-friends' @tap.stop="goto" :data-url="'/pagesC/liandong/team?id='+item.id">查看活动</view>
							<view class='fun-class invite-friends' @tap.stop="shareClick" :data-id="item.id">邀请好友</view>
						</view>
					</view>
				</view>
				</block>
			</view>
			<nomore v-if="nomore"></nomore>
			<nodata v-if="nodata"></nodata>
		</view>
	</block>
	<loading v-if="loading"></loading>
	<dp-tabbar :opt="opt"></dp-tabbar>
	<popmsg ref="popmsg"></popmsg>
	
	<view v-if="sharetypevisible" class="popup__container">
		<view class="popup__overlay" @tap.stop="handleClickMask"></view>
		<view class="popup__modal" style="height:320rpx;min-height:320rpx">
			<!-- <view class="popup__title">
				<text class="popup__title-text">请选择分享方式</text>
				<image :src="pre_url+'/static/img/close.png'" class="popup__close" style="width:36rpx;height:36rpx" @tap.stop="hidePstimeDialog"/>
			</view> -->
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
					<!-- #ifndef H5 -->
					<button class="f1" open-type="share" :data-id="share_active_id" v-if="getplatform() != 'h5'">
						<image class="img" :src="pre_url+'/static/img/sharefriends.png'"/>
						<text class="t1">分享给好友</text>
					</button>
					<!-- #endif -->
					<view class="f2" @tap="showPoster">
						<image class="img" :src="pre_url+'/static/img/sharepic.png'"/>
						<text class="t1">生成分享图片</text>
					</view>
					<!-- #ifdef MP-WEIXIN -->
					<view class="f1" @tap="shareScheme" v-if="xcx_scheme">
						<image class="img" :src="pre_url+'/static/img/weixin.png'"/>
						<text class="t1">小程序链接</text>
					</view>
					<!-- #endif -->
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
		
		bid:'',
		pics: [],
		pagenum: 1,
		st: '',
		datalist: [],
		nomore: false,
		nodata:false,
		sharetypevisible: false,
		showposter:false,
		posterpic:'',
		share_active_id:0,
		active:{}
		
    };
  },
  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.bid = this.opt.bid || '';
		this.getdata();
  },
	onPullDownRefresh: function () {
		this.getdata();
	},
  onReachBottom: function () {
    if (!this.nodata && !this.nomore) {
      this.pagenum = this.pagenum + 1;
      this.getdata(true);
    }
  },
  methods: {
		getdata: function (loadmore) {
			var that = this;
			if(!loadmore){
				this.pagenum = 1;
				this.datalist = [];
			}
			var pagenum = that.pagenum;
			var st = that.st;
			that.loading = true;
			that.nodata = false;
			that.nomore = false;
			app.post('ApiLiandong/index', {st: st,pagenum: pagenum}, function (res) {
				that.loading = false;
				var data = res.datalist;
				if (pagenum == 1) {
					that.pics = res.pics;
					that.clist = res.clist;
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
				that.loaded();
			});
		},
		toDetail:function(e){
			var id = e.currentTarget.dataset.id;
			app.goto('active?id='+id);
		},
		shareClick: function (e) {
			var that = this;
			this.share_active_id = e.currentTarget.dataset.id;
			app.post('ApiLiandong/detail', {id:that.share_active_id}, function (data) {
				that.active = data.info;
			});
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
			app.post('ApiLiandong/poster', {active_id: that.share_active_id}, function (data) {
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
		shareScheme: function () {
			var that = this;
			app.showLoading();
			app.post('ApiLiandong/getwxScheme', {active_id: that.share_active_id}, function (data) {
				app.showLoading(false);
				if (data.status == 0) {
					app.alert(data.msg);
				} else {
					that.showScheme = true;
					that.schemeurl=data.openlink
				}
			});
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
						sharedata.title = that.active.sharetitle || that.active.name;
						sharedata.summary = that.active.sharedesc || that.active.desc;
						sharedata.href = app.globalData.pre_url +'/h5/'+app.globalData.aid+'.html#/pagesC/liandong/team?scene=id_'+that.share_active_id+'-pid_' + app.globalData.mid;
						sharedata.imageUrl = that.active.pic;
						var sharelist = app.globalData.initdata.sharelist;
						if(sharelist){
							for(var i=0;i<sharelist.length;i++){
								if(sharelist[i]['indexurl'] == '/pagesC/liandong/team'){
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
	}
}
</script>
<style>
.container{background:#f4f4f4}
.swiper {width:94%;margin:0 3%;height: 350rpx;margin-top: 20rpx;border-radius:20rpx;overflow:hidden}
.swiper image {width: 100%;height: 350rpx;overflow: hidden;}

.category{width:94%;margin:0 3%;padding-top: 10px;padding-bottom: 10px;flex-direction:row;white-space: nowrap; display:flex;}
.category .item{width: 150rpx;display: inline-block; text-align: center;}
.category .item image{width: 80rpx;height: 80rpx;margin: 0 auto;border-radius: 50%;}
.category .item .t1{display: block;color: #666;}

.datalist{width:94%;margin:0 3%;}
.collage-product {display:flex;height:220rpx; background: #fff; padding:20rpx 20rpx;margin-top: 20rpx;border-radius:20rpx}
.collage-product .product-pic {width: 180rpx;height: 180rpx; background: #ffffff;overflow:hidden}
.collage-product .product-pic image{width: 100%;height:180rpx;}
.collage-product .product-info {padding: 5rpx 10rpx;flex:1}
.collage-product .product-info .p1 {color:#323232;font-weight:bold;font-size:28rpx;line-height:36rpx;margin-bottom:10rpx;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:2;overflow:hidden;height:72rpx}
.collage-product .product-info .p2{font-size: 32rpx;line-height: 40rpx}
.collage-product .product-info .p2 .t1{color: #f40;}
.collage-product .product-info .p2 .t2 {margin-left: 10rpx;font-size: 26rpx;color: #888;text-decoration: line-through;}
.collage-product .product-info .p3{font-size: 24rpx;height:50rpx;line-height:50rpx;overflow:hidden;display:flex;}
.collage-product .product-info .p3 .t1{color:#aaa;font-size:24rpx;flex:1}
.collage-product .product-info .p3 .t2{height: 50rpx;line-height: 50rpx;overflow: hidden;border: 1px #FF3143 solid;border-radius:10rpx;}
.collage-product .product-info .p3 .t2 .x1{padding: 10rpx 24rpx;}
.collage-product .product-info .p3 .t2 .x2{padding: 14rpx 24rpx;background: #FF3143;color:#fff;}

.covermy{position:absolute;z-index:99999;cursor:pointer;display:flex;flex-direction:column;align-items:center;justify-content:center;overflow:hidden;z-index:9999;top:260rpx;right:0;color:#fff;background-color:rgba(17,17,17,0.3);width:140rpx;height:60rpx;font-size:26rpx;border-radius:30rpx 0px 0px 30rpx;}

.collage-product .product-info .fun-class{font-size: 24rpx;padding: 7rpx 30rpx;border-radius: 20px;color: #fff;}
.collage-product .product-info .invite-friends{width: 200rpx;text-align: center;background: #fb484c;margin-left: 15rpx;}
</style>