<template>
	<view class="container">
	  <block v-if="isload">
			<!--  -->
			<view class="user-info-view flex-col">
        <view class="activity-list-view flex-col" v-if="true">
          <block v-for="(item,index) in datalist">
            <view class="flex active-options">
              <view class="active-image" >
                <image :src="item.pic"></image>
              </view>
              <view class="active-info-list flex-col" >
                <view class="active-name">{{item.name}}</view>
              
                <view class="active-fun-view flex flex-y-center">
					<block>
						<view class='fun-class invite-friends' @tap.stop="shareClick" :data-id="item.id">邀请好友</view>
						<view class='fun-class view-details' style="margin-left: 25rpx;" @tap.stop="goto" :data-url="'pages/shop/product?id=' + item.id">查看详情</view>
					</block>
                </view>
              </view>
			  <view class="set-fugou" >
			    <view class="item" @tap.stop="setfugou" :data-id="item.id">
					<view class="radio" :style="auto_fugou==item.id ? 'background:'+t('color1')+';border:0' : ''">
						<image class="radio-img" :src="pre_url+'/static/img/checkd.png'"/>
					</view>
				</view>
			  </view>
            </view>
          </block>
		  <nomore v-if="nomore"></nomore>
		  <nodata v-if="nodata"></nodata>
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
		  				<button class="f1" open-type="share" :data-id="share_set_id" v-if="getplatform() != 'h5'">
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
        <!-- <view v-else class="activity-list-view flex-col">
          <view class="flex active-options" style="text-align: center;display: block;color: #959494">{{progress_jzai}}</view>
        </view> -->
			</view>
			<view style="width: 100%;height: 100rpx;"></view>
		</block>
		<loading v-if="loading" ></loading>
		<dp-tabbar :opt="opt"></dp-tabbar>
		<popmsg ref="popmsg"></popmsg>
	</view>
</template>

<script>
	var app = getApp();
	export default{
		data(){
			return{
				loading:false,
				isload: false,
				menuindex:-1,
				pre_url:app.globalData.pre_url,
				opt:{},
				datalist: [],
				pagenum: 1,
				nodata: false,
				nomore: false,
				auto_fugou:0,
				userinfo:{},
				set:{},
				sharetypevisible: false,
				share_set_id:0,
				share_set:{},
				posterpic:'',
				showposter:false,
			}
		},
		onLoad: function (opt) {
			this.opt = app.getopts(opt);
			
			this.getdata();
		},
		onReachBottom: function () {
		  if (!this.nodata && !this.nomore) {
		    this.pagenum = this.pagenum + 1;
		    this.getdata(true);
		  }
		},
		onShareAppMessage: function () {
		    var that = this;
		    this.sharetypevisible = false;
			var thisurl = '/pagesC/queue/upload_order?scene=pid_'+app.globalData.mid+'-id_'+that.share_set_id;
		    console.log(thisurl);
			var set_info = that.share_set;
			
			return that._sharewx({title:set_info.name,pic:set_info.logo,desc:'',link:thisurl});
			
			return {
				path: thisurl,
				title: '邀请好友',
				imageUrl: ''
			};
		  },
		methods:{
			getdata: function (loadmore) {
				if(!loadmore){
					this.pagenum = 1;
					this.datalist = [];
				}
				var that = this;
				var pagenum = that.pagenum;
				var keyword = that.keyword;
				that.loading = true;
				that.nodata = false;
				that.nomore = false;
				app.post('ApiActiveCoin/fugou', {pagenum: pagenum,keyword: keyword}, function (res) {
					that.loading = false;
					that.isload = true;
					var data = res.data;
					if (pagenum == 1) {
						that.datalist = data;
						if (data.length == 0) {
							that.nodata = true;
						}
						that.userinfo = res.userinfo;
						that.auto_fugou = that.userinfo.activecoin_fugou_proid;
						console.log(that.auto_fugou);
						
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
			setfugou: function (e) {
			  var that = this;
			  var proid =  e.currentTarget.dataset.id;
			  app.post('ApiActiveCoin/setfugou', {proid:proid}, function (data) {
				  app.success('设置成功！');
			    that.auto_fugou = proid;
			  });
			},
		  shareClick: function (e) {
				var that = this;
				this.share_set_id = e.currentTarget.dataset.id;
			
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
		  	app.post('ApiShop/getposter', {proid: that.share_set_id}, function (data) {
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
		  	app.post('ApiShop/getwxScheme', {proid: that.share_set_id}, function (data) {
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
		  				sharedata.title = that.product.sharetitle || that.product.name;
		  				sharedata.summary = that.product.sharedesc || that.product.sellpoint;
		  				sharedata.href = app.globalData.pre_url +'/h5/'+app.globalData.aid+'.html#//pages/shop/product?scene=id_'+that.share_set_id+'-pid_' + app.globalData.mid;
		  				sharedata.imageUrl = that.product.pic;
		  				var sharelist = app.globalData.initdata.sharelist;
		  				if(sharelist){
		  					for(var i=0;i<sharelist.length;i++){
		  						if(sharelist[i]['indexurl'] == '/pages/shop/product'){
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
		 
		  showWechatTip:function(){
		  	uni.showToast({
		  		title: '请在微信浏览器中打开',
		  		icon: 'none'
		  	});
		  },

		}
	}
</script>

<style>
	page{background: #e3e3e3;}
	/* #ifdef H5 */
	/deep/ .uni-progress-bar{border-radius: 12px;overflow: hidden;}
	/* #endif */
	.container {width: 100%;}
	.statistics-view{width: 100%;background: #fb484c;color: #fff;font-size: 46rpx;font-weight: bold;padding: 130rpx 0rpx;letter-spacing: 4rpx;}
	.title-view{margin: 30rpx 0rpx;font-size: 24rpx;height: 45rpx;}
	.title-view .title-view-left{background: #3e3a39;border-radius: 0rpx 20rpx 20rpx 0rpx;width: auto;height: 100%;}
	.title-view .title-view-left .left-options{background-color: #fb484c;border-radius: 0rpx 20rpx 20rpx 0rpx;color: #fff;height: 45rpx;line-height: 45rpx;
	padding-left: 40rpx;padding-right: 40rpx;}
	.title-view .title-view-left .right-options{color: #fff;padding: 0rpx 20rpx;}
	.title-view .title-view-left .badu-icon{width: 25rpx;height: 25rpx;margin-right: 10rpx;}
	.user-info-view{position: relative;overflow: hidden;width: 94%;margin: 0rpx auto;}
	.rewards-view .rewards-options .rewards-text{font-size: 24rpx;white-space: nowrap;color: #959494;font-weight: bold;}
	.rewards-view .rewards-options .rewards-price{background: #fb484c;font-size: 30rpx;font-weight: bold;color: #fff001;text-align: center;width: 100%;
	border-radius: 34rpx;margin-top: 10rpx;padding: 5rpx 0rpx;}
	/* 进行中&已成团 */
	.tab-options{width: 43%;text-align: center;padding: 12rpx;border-radius: 30rpx;background-color: #e3e3e3;color: #484848;font-size: 26rpx;}
	.tab-options-active{background: #fb484c;color: #fff;}
	.activity-list-view{width: 100%;margin-top: 10rpx;}
	.activity-list-view .active-options{align-items: center;padding: 20rpx 0rpx;background: #fff;border-radius: 36rpx;margin-bottom: 20rpx;}
	.activity-list-view .active-options .active-image{width: 190rpx;height: 190rpx;border-radius: 14rpx;overflow: hidden;margin-left: 20rpx;}
	.activity-list-view .active-options .active-image image{width: 100%;height: 100%;}
	.activity-list-view .active-options .active-info-list{margin-left: 45rpx;flex: 1;}
	.active-info-list .active-name{font-size: 26rpx;color: #000;font-weight: bold;white-space: nowrap;overflow: hidden;text-overflow: ellipsis;}
	.active-info-list .active-jingdu{font-size: 24rpx;color: #959494;white-space: nowrap;overflow: hidden;text-overflow: ellipsis;margin-top: 10rpx;}
	.active-info-list .reward-amount{font-size: 24rpx;color: #fb484c;white-space: nowrap;font-weight: bold;}
	.active-info-list .active-fun-view{margin-top: 15rpx;}
	.active-info-list .active-fun-view .fun-class{font-size: 24rpx;padding: 7rpx 30rpx;border-radius: 20px;color: #fff;}
	.active-info-list .active-fun-view .invite-friends{background: #fb484c;}
	.active-info-list .active-fun-view .view-details{background-color: #eaeaea;color: #1d1d1d;}
	.active-info-list .active-fun-view .view-details2{background-color: #3b3b3b;}
	.active-progress-view{width: 100%;align-items: center;justify-content: flex-start;}
	.active-progress-view .progress-view{width: 58%;}
	.active-progress-view .progress-text{font-size: 24rpx;color: #fb484c;white-space: nowrap;margin-left: 20rpx;font-weight: bold;}
	.back-index{font-size: 36rpx;background: #3b3b3b;color: #fff;margin: 40rpx auto 60rpx;width: 29%;text-align: center;border-radius: 38rpx;
	padding: 10rpx 0rpx;}
	.popup__content .wxstore {
	  width: 100%;
	  display: flex;
	  justify-content: center;
	  align-items: center;
	}
	.set-fugou{border-left: 1px solid #e3e3e3;height: 180rpx;padding: 20rpx;display: flex;align-items: center;}
	.set-fugou .item .radio{flex-shrink:0;width: 36rpx;height: 36rpx;background: #FFFFFF;border: 3rpx solid #BFBFBF;border-radius: 50%;margin-right:10rpx}
	.set-fugou .item .radio .radio-img{width:100%;height:100%}
</style>