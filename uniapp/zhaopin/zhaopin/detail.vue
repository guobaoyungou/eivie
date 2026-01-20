<template>
<view>
	<view class="container" v-if="isload">
		<view class="thumb" v-if="detail.thumb"><image :src="detail.thumb" mode="aspectFit" @tap="previewImage" :data-url="detail.thumb"></image></view>
		<view class="box business-box" v-if="zhaopinApply.id>0">
			<view class="f1"><image class="logo" :src="zhaopinApply.logo" @tap="previewImage" :data-url="zhaopinApply.logo"></image></view>
			<view class="f2">
				<view class="name">
					{{zhaopinApply.company}}
					<image v-if="zhaopinApply.level_icon" :src="zhaopinApply.level_icon" class="icon" @tap="previewImage" :data-url="zhaopinApply.level_icon">
				</view>
				<view v-if="zhaopinApply.assurance_fee>0">保证金：<text class="assurance_fee">￥{{zhaopinApply.assurance_fee}}</text></view>
				<view>信用分：{{zhaopinApply.pscore}}</view>
			</view>
		</view>
		<view class="box title-box">
			<view class="flex-sb">
				<view class="title">{{detail.title}}</view>
				<view class="salary">{{detail.salary}}</view>
			</view>
			<view class="flex-sb hui">
				<view class="">发布于{{detail.createtime}}</view>
				<view class="">浏览{{detail.readnum}}次</view>
			</view>
			<view class="flex-sb hui address-box" @tap="openLocation" :data-latitude="detail.latitude" :data-longitude="detail.longitude">
				<view class="">
					<image class="address-icon" src="../../static/img/address3.png"></image>
					<text v-if="detail.address">{{detail.address}}</text>
					<text v-else>{{detail.area}}</text>
				</view>
				<image class="arrowleft" src="../../static/img/arrowright.png">
			</view>
		</view>
		<view class="box share_rule" v-if="share.share_open">
			{{share.share_rule}}
		</view>
		<view class="box">
			<view class="form-item">
				<view class="form-label">招聘岗位</view>
				<view class="form-value">
					{{detail.cname}}
				</view>
			</view>
			<view class="form-item">
				<view class="form-label">招聘人数</view>
				<view class="form-value">
					{{detail.num}}人
				</view>
			</view>
			<view class="form-item">
				<view class="form-label">性别要求</view>
				<view class="form-value">
					{{detail.sex}}
				</view>
			</view>
			<view class="form-item">
				<view class="form-label">年龄要求</view>
				<view class="form-value">
					{{detail.age}}
				</view>
			</view>
			<view class="form-item">
				<view class="form-label">工作经验</view>
				<view class="form-value">
					{{detail.experience}}
				</view>
			</view>
			<view class="form-item">
				<view class="form-label">福利待遇</view>
				<view class="form-value flex-s flwp">
					<view class="tagitem" v-for="(item,index) in detail.welfare" :key="index">
						{{item}}
					</view>
				</view>
			</view>
		</view>
		<view class="box">
			<view class="form-item-row">
				<view class="form-label">职位简介</view>
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
								<image :src="pic" @click="previewImage" :data-url="pic" mode="widthFix" />
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
			<view class="option">
				<view class="btn2" @tap="sendzhaopin" :data-id="detail.id">我要应聘</view>
			</view>
		</view>
		<view class="tosign" @tap="goto" :data-url="kfurl" v-if="kfurl!='contact::'">
			<view class="v">客服</view>
		</view>
		<button class="tosign" v-else open-type="contact">
			<view class="v">客服</view>
		</button>
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
			zhaopinApply:{id:0},
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
		console.log(sharewxdata)
		console.log(query)
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
			app.get('ApiZhaopin/zhaopinDetail', {id:that.opt.id}, function (res) {
				that.loading = false;
				if(res.status==1){
					var detail = res.detail
					that.detail = res.detail
					that.zhaopinApply = res.detail.zhaopinApply
					that.formfields = res.formfields
					that.formorder = res.formorder
					that.isfavorite = res.isfavorite;
					that.share = res.share
					//分享内容
					that.mid = app.globalData.mid;
					that.shareTitle = detail.title;
					that.shareDesc = detail.title;
					that.sharePic = detail.thumb;
					that.shareLink = app.globalData.pre_url +'/h5/'+app.globalData.aid+'.html#/zhaopin/zhaopin/detail?scene=id_'+that.opt.id+'-pid_' + app.globalData.mid;
					
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
		sendzhaopin:function(){
			var that = this;
			that.loading = true;
			app.post('ApiZhaopin/zhaopinSend',{
				id:that.opt.id
			}, function (res) {
				that.loading = false;
				if(res.status==1){
					app.goto('/zhaopin/zhaopin/chat?bid='+that.detail.bid+'&mid='+res.mid+'&id='+that.detail.id+'&tbtype=2')
				}else if(res.status==2){
					app.confirm(res.msg,function(){
						app.goto('/zhaopin/qiuzhi/add');
					})
				}else{
					app.alert(res.msg)
				}
			  
			});
		},
		//收藏操作
		addfavorite: function () {
			var that = this;
			var proid = that.opt.id;
			app.post('ApiZhaopin/addfavorite', {proid: proid,type: 'zhaopin'}, function (data) {
				if (data.status == 1) {
					that.isfavorite = !that.isfavorite;
				}
				app.success(data.msg);
			});
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
		showsubqrcode:function(){
			// this.$refs.qrcodeDialog.open();
			app.error('11111111111');
		},
		closesubqrcode:function(){
			app.error('22222222');
			// this.$refs.qrcodeDialog.close();
		},
		sharemp:function(){
			app.error('点击右上角发送给好友或分享到朋友圈');
			this.sharetypevisible = false
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
			app.post('ApiZhaopin/getposter', {id: that.detail.id,type:'zhaopin'}, function (data) {
				app.showLoading(false);
				if (data.status == 0) {
					app.alert(data.msg);
				} else {
					that.posterpic = data.poster;
				}
			});
		},
		openLocation:function(e){
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
		posterDialogClose: function () {
			this.showposter = false;
		}
  }
}
</script>
<style>
@import "../common.css";
.box{background:#FFFFFF;padding: 0 30rpx;margin-bottom: 20rpx;line-height: 50rpx;}
.hui{color: #9a9a9a;}
.thumb{background: #FFFFFF;margin-bottom: 20rpx;width: 100%;max-height: 400rpx;overflow: hidden;display: flex;align-items: center;justify-content: center;}
.thumb image{max-height: 100%;}
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

.address-box .address-icon{width: 36rpx;height: 36rpx;}
.address-box .arrowleft{width: 32rpx;height: 32rpx;}

.bottom{position: fixed;bottom: 0;width: 100%;background: #FFFFFF; height: 100rpx;padding: 10rpx 20rpx;left: 0;}
.bottom .share{text-align: center;min-width: 60rpx;flex-shrink: 0;font-size: 24rpx;}
.bottom .share image{width: 30rpx;height: 30rpx;vertical-align: text-bottom;}
.bottom .option{color: #FFFFFF;flex: 1;display: flex;justify-content: flex-end;align-items: center;line-height: 60rpx;text-align: center;}
.bottom .option .btn1{background: #F0AD4E;border-radius: 50rpx 0 0 50rpx; padding: 0 30rpx;width: 45%;}
.bottom .option .btn2{background: #031028;border-radius:50rpx; padding: 0 30rpx;width: 45%;}

.business-box{display: flex;justify-content: flex-start;padding: 20rpx 30rpx;align-items: center;}
.business-box .f2{padding-left: 30rpx;color: #9a9a9a;}
.business-box .name{font-weight: bold;color: #222222;font-size: 32rpx;}
.business-box .logo{width: 100rpx;height: 100rpx;border-radius: 50%;}
.business-box .icon{width: 36rpx;height: 36rpx;border-radius: 8rpx;margin-left: 10rpx;}

.tosign{width: 100rpx;height: 100rpx;background: #090d1359;color: #FFFFFF;position: fixed;bottom: 120rpx;right: 10rpx;display:flex;justify-content: center;align-items: center;
border-radius: 50%;flex-direction: column;text-align: center;font-size: 24rpx;}

.share_rule{background: #ffebdb;margin: 20rpx;border-radius: 16rpx;color: #f87510;}
.assurance_fee{color:#f87510;font-weight: bold;font-size: 28rpx;}



.popup_share .popup__modal{min-height: 130rpx;border-radius: 0;padding: 0;}

</style>