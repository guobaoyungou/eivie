<template>
<view>
<view class="container" v-if="isload">
	<view class="warning" v-if="detail.mohu>0">该人员设置了隐私保护，详细信息，请联系招聘顾问！</view>
	<view class="profile flex-s">
		<view class="thumb">
			<!-- <image :src="detail.thumb" mode="aspectFit" @tap="previewImage" :data-url="detail.thumb"></image> -->
			<!-- <image :src="detail.thumb" mode="aspectFit" @tap="previewImage" :data-url="detail.thumb"></image> -->
			<image v-if="detail.mohu>0" :src="detail.thumb" mode="aspectFit" :style="'filter: blur('+detail.mohu+'px);-webkit-filter: blur('+detail.mohu+'px);-moz-filter: blur('+detail.mohu+'px)'"></image>
			<image v-else :src="detail.thumb" mode="aspectFit" @tap="previewImage" :data-url="detail.thumb"></image>
		</view>
		<view class="info">
				<view class="name">{{detail.name}} <image v-if="detail.level_icon" :src="detail.level_icon" class="icon"></view>
				<view class="">
					<text v-if="detail.age">{{detail.age}}岁</text>
					<text v-if="detail.sex==1">/男</text>
					<text v-if="detail.sex==2">/女</text>
				</view>
				<view class="">
					状态：{{detail.has_job=='1'?'在职':'离职'}}
				</view>
				<view v-if="detail.pscore" class="">
					信用分：{{detail.pscore}}
				</view>
		</view>
	</view>
	<view class="box title-box">
		<view class="title" v-if="detail.title">{{detail.title}}</view>
		<view class="flex-sb">
			<view class="salary">{{detail.salary}}</view>
			<view class="" v-if="detail.area">{{detail.area}}</view>
		</view>
		<view class="flex-sb hui">
			<view class="">发布于{{detail.createtime}}</view>
			<view class="">浏览{{detail.readnum}}次</view>
		</view>
	</view>
	<view class="box share_rule" v-if="share.share_open">
		{{share.share_rule}}
	</view>
	<view class="box">
		<view class="form-item">
			<view class="form-label">期望岗位</view>
			<view class="form-value">
				{{detail.cnames}}
			</view>
		</view>
		<view class="form-item">
			<view class="form-label">期望城市</view>
			<view class="form-value">
				{{detail.area}}
			</view>
		</view>
		<view class="form-item">
			<view class="form-label">工作经验</view>
			<view class="form-value">
				{{detail.experience}}
			</view>
		</view>
		<view class="form-item-row">
			<view class="form-label">个人优势</view>
			<view class="form-value flex-s flwp">
				<view class="tagitem" v-for="(item,index) in detail.tags" :key="index">
					{{item}}
				</view>
			</view>
		</view>
	</view>
	<view class="box">
		<view class="form-item-row">
			<view class="form-label">个人简介</view>
			<view class="form-value textarea">
				{{detail.desc}}
			</view>
		</view>
	</view>
	<view class="box custome-field" v-if="formorder && formfields">
		<block  v-for="(item,idx) in formfields"  :key="idx">
		<view :class="(item.key=='textarea' || item.key=='upload')?'form-item-row':'form-item'">
			<view class="form-label">{{item.val1}}</view>
			<view :class="'form-value '+item.key">
				<block v-if="item.key=='upload'">
					<view class="form-imgbox" v-for="(pic, pindex) in formorder['form'+idx]">
						<view class="form-imgbox-img">
							<image v-if="detail.mohu>0" :src="pic" mode="widthFix" :style="'filter: blur('+detail.mohu+'px);-webkit-filter: blur('+detail.mohu+'px);-moz-filter: blur('+detail.mohu+'px)'" />
							<image v-else :src="pic" @click="previewImage" :data-url="pic" mode="widthFix" />
						</view>
					</view>
				</block>
				<block v-else>
				{{formorder['form'+idx]}}
				</block>
			</view>
		</view>
		</block>
	</view>
	<view style="height: 100rpx;"></view>
	<view class="bottom flex-c">
		<view class="share"  @tap="shareClick">
			<view><image src="../../static/img/lt_share.png"></image></view>
			<view>分享</view>
		</view>
		<view class="share" @tap="addfavorite">
			<view>
				<image v-if="isfavorite" src="../../static/img/lt_like2.png"></image>
				<image v-if="!isfavorite" src="../../static/img/lt_like.png"></image>
			</view>
			<view>{{isfavorite?'已收藏':'收藏'}}</view>
		</view>
		<!-- v-if="detail.is_mine==0" -->
		<view class="option" >
			<view class="btn1" v-if="detail.secret_type>0" :data-url="detail.tel">{{detail.tel}}</view>
			<view class="btn1" v-else  :data-url="detail.tel">{{detail.tel}}</view>
			<view class="btn2" @tap="sendOffer">向Ta发送招聘</view>
		</view>
	</view>
	<!-- 检索条件 Start -->
	<view v-if="isshowfilter" class="popup__container popup_filter">
		<view class="popup__overlay" @tap.stop="hideFilter"></view>
		<view class="popup__modal">
			<view class="popup__title">
				<text class="popup__title-text">请选择招聘记录</text>
				<!-- <image src="/static/img/close.png" class="popup__close" @tap.stop="hideFilter" /> -->
			</view>
			<view class="popup__content">
				<view>
					<block v-for="(item,index) in datalist" :key="index">
						<view class="filter-selector" :class="itemindex==index?'on':''" @tap="chooseSelector" :data-index="index">
						<radio value="1" :checked="itemindex==index" style="transform: scale(0.7);" />{{item.title}}
						</view>
					</block>
				</view>
			</view>
			
			<view class="popup__bottom flex-sb">
				<view class="btn btn-reset" @tap="hideFilter">取消</view>
				<view class="btn btn1" @tap="filterConfirm">确定发送</view>
			</view>
		</view>
	</view>
	<view class="tosign" @tap="goto" :data-url="kfurl" v-if="kfurl!='contact::'">
		<view class="v">客服</view>
	</view>
	<button class="tosign" v-else open-type="contact">
		<view class="v">客服</view>
	</button>
	<!-- 检索条件 End -->
</view>
<!-- share modal start -->
	<view v-if="sharetypevisible" class="popup__container popup_share">
		<view class="popup__overlay" @tap.stop="handleClickMask"></view>
		<view class="popup__modal" style="height:220rpx;min-height:220rpx">
			<!-- <view class="popup__title">
				<text class="popup__title-text">请选择分享方式</text>
				<image src="/static/img/close.png" class="popup__close" style="width:36rpx;height:36rpx" @tap.stop="hidePstimeDialog"/>
			</view> -->
			<view class="popup__content">
				<view class="sharetypecontent">
					<view class="f1" @tap="shareapp" v-if="getplatform() == 'app'">
						<image class="img" src="/static/img/weixin.png"/>
						<text class="t1">分享给好友</text>
					</view>
					<view class="f1" @tap="sharemp" v-else-if="getplatform() == 'mp'">
						<image class="img" src="/static/img/weixin.png"/>
						<text class="t1">分享给好友</text>
					</view>
					<view class="f1" @tap="sharemp" v-else-if="getplatform() == 'h5'">
						<image class="img" src="/static/img/weixin.png"/>
						<text class="t1">分享给好友</text>
					</view>
					<button class="f1" open-type="share" v-else>
						<image class="img" src="/static/img/weixin.png"/>
						<text class="t1">分享给好友</text>
					</button>
					<view class="f2" @tap="showPoster">
						<image class="img" src="/static/img/sharepic.png"/>
						<text class="t1">生成分享图片</text>
					</view>
				</view>
			</view>
		</view>
	</view>
	
	<view class="posterDialog" v-if="showposter">
		<view class="main">
			<view class="close" @tap="posterDialogClose"><image class="img" src="/static/img/close.png"/></view>
			<view class="content">
				<image class="img" :src="posterpic" mode="widthFix" @tap="previewImage" :data-url="posterpic"></image>
			</view>
		</view>
	</view>
	<!-- share modal end -->
	<loading v-if="loading"></loading>
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
			formfields:[],
			detail:{},
			formorder:{},
			datalist:[],
			isshowfilter:false,
			itemindex:-1,
			bid:0,
			shareTitle:'',
			sharePic:'',
			shareDesc:'',
			shareLink:'',
			mid:0,
			isfavorite:false,
			share:[],
			sharetypevisible: false,
			showposter: false,
			posterpic: "",
			kfurl:''
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
		return this._sharewx({title:this.shareTitle,pic:this.sharePic,desc:this.shareDesc,link:this.shareLink});
	},
	onShareTimeline:function(){
		var sharewxdata = this._sharewx({title:this.shareTitle,pic:this.sharePic,desc:this.shareDesc,link:this.shareLink});
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
			app.get('ApiZhaopin/qiuzhiDetail', {id:that.opt.id}, function (res) {
				that.loading = false;
				if(res.status==1){
					var detail = res.detail
					that.detail = res.detail
					that.formfields = res.formfields
					that.formorder = res.formorder
					that.isfavorite = res.isfavorite
					that.share = res.share
					//分享内容
					that.mid = app.globalData.mid;
					that.shareTitle = detail.title;
					that.shareDesc = detail.title;
					that.sharePic = detail.thumb;
					that.shareLink = app.globalData.pre_url +'/h5/'+app.globalData.aid+'.html#/zhaopin/qiuzhi/detail?scene=id_'+that.opt.id+'-pid_' + app.globalData.mid;
					
					that.kfurl = app.globalData.initdata.kfurl
					var platform = app.globalData.platform
					if(platform=='app'){
						that.kfurl = '/pages/kefu/index'
					}
					that.loaded({title:that.shareTitle,pic:that.sharePic,desc:that.shareDesc,link:that.shareLink});
				}else{
					app.alert(res.msg)
				}
			});
		},
		//收藏操作
		addfavorite: function () {
			var that = this;
			var proid = that.opt.id;
			app.post('ApiZhaopin/addfavorite', {proid: proid,type: 'qiuzhi'}, function (data) {
				if (data.status == 1) {
					that.isfavorite = !that.isfavorite;
				}
				app.success(data.msg);
			});
		},
		sendOffer:function(){
			var that = this;
			that.loading = true;
			app.post('ApiZhaopin/qiuzhiSend',{
				id:that.opt.id
			}, function (res) {
				that.loading = false;
				if(res.status==1){
					that.datalist = res.zhaopinlist;
					that.isshowfilter = true;
					that.bid = res.bid
					// app.goto('/zhaopin/zhaopin/chat?bid='+that.detail.bid+'&mid='+res.mid+'&id='+that.detail.id+'&tbtype=2')
				}else if(res.status==2){
					app.confirm(res.msg,function(){
						app.goto('/zhaopin/zhaopin/add');
					})
				}else if(res.status==3){
					app.confirm(res.msg,function(){
						app.goto('/admin/index/recharge');
					})
				}else{
					app.alert(res.msg)
				}
			});
			
		},
		hideFilter:function(e){
			this.isshowfilter = false
		},
		chooseSelector:function(e){
			var that = this
			var index = e.currentTarget.dataset.index;
			that.itemindex = index
		},
		filterConfirm:function(){
			var that = this
			if(that.itemindex>-1){
				var zid = this.datalist[that.itemindex].id
				that.isshowfilter = false;
				app.post('ApiZhaopin/qiuzhiSendConfirm',{zid:zid,qid:that.detail.id},function(res){
					app.goto('/zhaopin/qiuzhi/chat?id='+that.detail.id+'&tbtype=2&zid='+zid)
				})
			}else{
				app.alert('请选择招聘信息');
				return;
			}
		},
		sharewx:function(){
			app.error('点击右上角发送给好友或分享到朋友圈');
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
						sharedata.title = that.product.sharetitle || that.product.name;
						sharedata.summary = that.product.sharedesc || that.product.sellpoint;
						sharedata.href = app.globalData.pre_url +'/h5/'+app.globalData.aid+'.html#/pages/shop/product?scene=id_'+that.product.id+'-pid_' + app.globalData.mid;
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
		},
		posterDialogClose: function () {
			this.showposter = false;
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
			app.post('ApiZhaopin/getposter', {id: that.detail.id,type:'qiuzhi'}, function (data) {
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
		}
		
  }
}
</script>
<style>
@import "../common.css";
.box{background:#FFFFFF;padding: 0 30rpx;margin-bottom: 20rpx;line-height: 50rpx;}
.warning{background: #000000;opacity: 0.7;color: #ff0000;position: fixed;top: 0;width: 100%;line-height: 50rpx;text-align: center;font-weight: bold;z-index: 10;}
.hui{color: #9a9a9a;}
.profile{background: #FFFFFF;padding:50rpx 30rpx;margin-bottom: 20rpx;font-size: 24rpx;}
.profile .info{padding-left: 20rpx;color: #999;line-height: 38rpx;}
.profile .name{font-size: 32rpx;font-weight: bold;color: #222222;line-height: 50rpx;}
.profile .icon{width: 36rpx;height: 36rpx;border-radius: 8rpx;margin-left: 10rpx;}
.thumb{width: 120rpx;height: 120rpx;overflow: hidden;display: flex;align-items: center;justify-content: center;border-radius: 50%;}
.thumb image{max-height: 100%;border-radius: 50%;}
.form-item{display: flex;justify-content: flex-start;align-items: center;border-bottom: 1rpx solid #f6f6f6; padding: 20rpx 0;}
.form-label{flex-shrink: 0;width: 180rpx;flex-wrap: wrap;font-weight: bold;/* text-align: justify;text-align-last: justify; */padding-right: 30rpx;}
.form-value{flex: 1;}
.form-value.upload{text-align: center;}
.form-tips{color: #CCCCCC;font-size: 28rpx;padding: 20rpx 0;}
/* 行排列 */
.form-item-row{border-bottom: 1rpx solid #f6f6f6; padding: 20rpx 0;}
.form-item-row .form-label,.form-item-row .form-value{width: 100%;}
.form-value.textarea{padding:10rpx 4rpx;border-radius: 8rpx;color: #999;}
.tagitem{background: #f4f7fe;text-align: center;padding: 0 6rpx;margin-right: 8rpx;white-space: normal;font-size: 24rpx;color: #999;margin-bottom: 8rpx;}
.title-box{padding: 30rpx;}
.title-box .title{font-size: 32rpx;font-weight: bold;}
.title-box .salary{color: #FF3A69;}
/* 图片 */
.form-imgbox{width: 100%;}
.form-imgbox-img>image{max-width:100%;border-radius: 8rpx;}
.form-imgbox-repeat{position: absolute;display: block;width:32rpx;height:32rpx;line-height:28rpx;right: 2px;bottom:2px;color:#999;font-size:30rpx;background:#fff}
.form-uploadbtn{position:relative;height:116rpx;width:116rpx}

.box .form-item:last-child{border: none;}

.bottom{position: fixed;bottom: 0;width: 100%;background: #FFFFFF; height: 100rpx;padding: 10rpx 20rpx;left: 0;}
.bottom .share{text-align: center;min-width: 60rpx;flex-shrink: 0;font-size: 24rpx;}
.bottom .share image{width: 30rpx;height: 30rpx;vertical-align: text-bottom;}
.bottom .option{color: #FFFFFF;flex: 1;display: flex;justify-content: flex-end;align-items: center;line-height: 60rpx;text-align: center;}
.bottom .option .btn1{background: #F0AD4E;border-radius: 50rpx 0 0 50rpx; padding: 0 30rpx;width: 45%;}
.bottom .option .btn2{background: #031028;border-radius:0 50rpx 50rpx 0; padding: 0 30rpx;width: 45%;}

/* modal */
.popup__content{padding:0 20rpx;height: 440rpx;}
.popup__overlay{opacity: 0.5;}
.popup__modal{border-radius: 0;max-height: 640rpx;min-height: 640rpx;}
.popup__title{background: #f6f6f6;padding: 20rpx;}
.popup__title .popup__close{width: 24rpx;height: 24rpx;}

.popup__content .choose-box{display: flex;justify-content: space-between;flex-wrap: wrap;align-items: center;}
.popup__content .choose-box .choose-item.on{color: #FE924A;background:#fe924a30;}
.choose-box .choose-item{flex-shrink: 0;background: #F6F6F6;text-align: center;padding:10rpx;flex-wrap: nowrap;white-space: nowrap;overflow: hidden;text-overflow: ellipsis;}
.filter-cid .choose-box .choose-item{width: 48%;flex-shrink: 0;background: #F6F6F6;text-align: center;padding:16rpx;margin-bottom: 16rpx;}
.filter-selector{padding: 20rpx;border-bottom: 1rpx solid #f6f6f6;}
.filter-selector.on{color: #FE924A;font-weight: bold;}
.popup_filter .popup__bottom{padding: 10rpx 20rpx;position: absolute;bottom: 5rpx;width: 100%;}
.popup_filter .popup__bottom .btn{width: 48%;text-align: center;padding: 16rpx 20rpx;}
.popup_filter .popup__bottom .btn1{background: #031028;color: #FFFFFF;}

.popup_filter .popup__bottom .btn-reset{background: #aaaaaa;color: #FFFFFF;}

.tosign{width: 100rpx;height: 100rpx;background: #090d1359;color: #FFFFFF;position: fixed;bottom: 120rpx;right: 10rpx;display:flex;justify-content: center;align-items: center;
border-radius: 50%;flex-direction: column;text-align: center;font-size: 24rpx;}
.share_rule{background: #ffebdb;margin: 20rpx;border-radius: 16rpx;color: #f87510;}


.popup_share .popup__modal{min-height: 130rpx;border-radius: 0;padding: 0;}

</style>