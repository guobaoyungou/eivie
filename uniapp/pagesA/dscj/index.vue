<template>
<view class="container">
	<block v-if="isload">
		<view class="swiper-container">
			<swiper class="swiper" :indicator-dots="false" :autoplay="true" :interval="5000" @change="swiperChange">
				<block v-for="(item, index) in sliders" :key="index">
					<swiper-item class="swiper-item">
						<view class="swiper-item-view"><image class="img" :src="item" mode="widthFix"/></view>
					</swiper-item>
				</block>
			</swiper>
			<view class="counter"><view class="imageCount">{{current+1}}/{{sliders.length}}</view></view>
		</view>
		<view class="main-container">
			<view class="mainbox">
				<view class="title">
					{{info.name}}
				</view>
				<view class="prize">
					<view v-for="(item,index) in jxarr" :key="index">{{item.jxmc}} <text class="number">×{{item.jxsl}}份</text></view>
				</view>
				<view class="time">
					<view v-if="info.is_end==-1">开始时间：<text class="date">{{info.starttime}}</text></view>
					<view v-if="info.is_end==1 || info.is_end==2">开奖时间：<text class="date">{{info.opentime}}</text></view>
					<view v-if="info.is_end==0">结束时间：<text class="date">{{info.endtime}}</text></view>
					<view v-if="info.need_fee && info.fee>0">报名费用：<text class="price">{{info.fee}} 元</text></view>
				</view>
				<view class="rule">
						<view>{{info.opentime}} 自动开奖</view>
						<view v-if="info.opennum>0">或参与人数满 {{info.opennum}}人 自动开奖</view>
				</view>
				
				<view class="result" v-if="info.is_end==1"><text class="restag">已开奖</text><text class="resbtn" @tap="goto" :data-url="'prize?hid='+info.id">查看中奖名单</text></view>
			</view>
			<view class="mainbox mgt10">
				<view class="joinwrap" :class="'st'+info.is_end">
					<view class="join">
						<text v-if="info.is_end==-1">未开始</text>
						<text v-if="info.is_end==2">已结束</text>
						<text v-if="info.is_end==1">已开奖</text>
						<block v-if="info.is_end==0">
							<text v-if="info.is_join==1">等待开奖</text>
							<text v-if="info.is_join==0 && showqrcode && !showdoneqrcode" @tap="showqrmodal">参与抽奖</text>
							<text v-if="info.is_join==0 && (!showqrcode || (showqrcode && showdoneqrcode))" @tap="joinin">参与抽奖</text>
						</block>
					</view>
				</view>
				<block v-if="joinnum>0">
					<view class="joinnum">已有{{joinnum}}人参加此活动</view>
					<view class="joinlist">
						<block v-for="(item,index) in datalist" :key="index">
						<view class="img"><image :src="item.headimg"></image></view>
						</block>
					</view>
					<view class="joinmore" v-if="nomore==false" @tap="getmore">---查看更多---</view>
					<view class="joinmore" v-if="nomore==true && pagenum>1">---无更多数据---</view>
				</block>
			</view>
			<view class="mainbox mgt10">
				<view class="detail">
					<view class="title">活动详情</view>
					<view class="content">
						<rich-text :nodes="info.content"></rich-text>
					</view>
				</view>
			</view>
		</view>
		
		<view v-if="showqrpopup" class="popup__container">
			<view class="popup__overlay" @tap.stop="hideqrmodal"></view>
			<view class="popup__modal" style="">
				<view class="popup__content">
					<view><image :data-url="info.qrcode" :src="info.qrcode" @tap="previewImage" ></image></view>
					<view class="txt" v-if="info.qrcode_tip">{{info.qrcode_tip}}</view>
				</view>
			</view>
		</view>
		<view class="myrecord" @tap="goto" :data-url="'myrecord?bid='+bid">我的<br>抽奖</view>
	</block>
	<loading v-if="loading"></loading>
	<dp-tabbar :opt="opt"></dp-tabbar>
	<popmsg ref="popmsg"></popmsg>
</view>
</template>

<script>
var dot_inter, bool;
var interval;
var app = getApp();
var windowWidth = uni.getSystemInfoSync().windowWidth;

export default {
  data() {
    return {
			opt:{},
			loading:false,
      isload: false,
			menuindex:-1,
			
			pre_url:app.globalData.pre_url,
      isStart: 1,
      name: "",
      jxmc: "",
      detect: 1,
      error: "",
			member:{},
      remaindaytimes: 0,
			remaintimes:0,

      register: 1,
      award_name: 0,
      jxshow: false,
      showmaskrule: false,
      latitude: "",
      longitude: "",
      r: 0,
      lastX: "",
      lastY: "",
      minX: "",
      minY: "",
      maxX: "",
      maxY: "",
      canvasWidth: "",
      canvasHeight: "",
      isScroll: false,
      award: 0,
      jx: "",
			windowWidth:0,
			windowHeight:0,
			//定时抽奖
			bid:0,
			info:{},
			jxarr:[],
			sliders:[],
			zjlist:[],
			current:0,
			//参加人员列表
			datalist: [],
			pagenum: 1,
			nomore: false,
			nodata: false,
			joinnum:0,
			showqrcode:false,
			showqrpopup:false,
			showdoneqrcode:false,
    };
  },
	onLoad:function(opt){
		this.opt = app.getopts(opt);
		this.bid = this.opt.bid || 0;
		this.getdata();
		this.getJoinList();
	},
  onReady: function () {
		var that = this;
		var res = uni.getSystemInfoSync();
		that.windowWidth = res.windowWidth;
		that.windowHeight = res.windowHeight;
  },
	onPullDownRefresh: function () {
	},
  onShareAppMessage: function () {
		var that = this;
		var title = that.info.name;
		if (that.info.sharetitle) title = that.info.sharetitle;
		var sharepic = that.info.sharepic ? that.info.sharepic : '';
		var sharelink = that.info.sharelink ? that.info.sharelink : '';
		var sharedesc = that.info.sharedesc ? that.info.sharedesc : '';
		return this._sharewx({title:title,desc:sharedesc,link:sharelink,pic:sharepic,callback:function(){that.sharecallback();}});
  },
	onShareTimeline:function(){
		var that = this;
		var title = that.info.name;
		if (that.info.sharetitle) title = that.info.sharetitle;
		var sharepic = that.info.sharepic ? that.info.sharepic : '';
		var sharelink = that.info.sharelink ? that.info.sharelink : '';
		var sharedesc = that.info.sharedesc ? that.info.sharedesc : '';
		var sharewxdata = this._sharewx({title:title,desc:sharedesc,link:sharelink,pic:sharepic,callback:function(){that.sharecallback();}});
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
			app.get('ApiDscj/dscjDetail', {id: id,bid:that.opt.bid}, function (res) {
				that.loading = false;
				if(res.status == 0){
					app.alert(res.msg);
					return;
				}
				that.info = res.info;
				if(that.info.qrcode){
					that.showqrcode = true
				}
				that.jxarr = res.jxarr;
				that.sliders = res.info.pics
				// that.member = res.member;
				that.zjlist = res.zjlist;
				uni.setNavigationBarTitle({
					title: res.info.name
				});
				
				var title = that.info.name;
				if (that.info.sharetitle) title = that.info.sharetitle;
				var sharepic = that.info.sharepic ? that.info.sharepic : '';
				var sharelink = that.info.sharelink ? that.info.sharelink : '';
				var sharedesc = that.info.sharedesc ? that.info.sharedesc : '';
				that.loaded({title:title,desc:sharedesc,link:sharelink,pic:sharepic,callback:function(){that.sharecallback();}});
			});
		},
		swiperChange: function (e) {
			var that = this;
			that.current = e.detail.current
		},
		showqrmodal:function(){
			this.showqrpopup = true;
			this.showdoneqrcode = true;
		},
		hideqrmodal:function(){
			this.showqrpopup = false;
		},
		getmore:function(){
			if(this.nomore==false){
				this.pagenum++;
			}
			this.getJoinList(true)
		},
		getJoinList: function (loadmore) {
			if(!loadmore){
				this.pagenum = 1;
				this.datalist = [];
			}
		  var that = this;
		  var pagenum = that.pagenum;
			that.nodata = false;
			that.nomore = false;
		  that.loading = true;
		  app.post('ApiDscj/dscjJoinList', {id: that.opt.id,pagenum: pagenum,bid:that.bid}, function (res) {
				that.loading = false;
		    that.loaddingmore = false;
		    var data = res.data;
				that.joinnum = res.count
		    if (pagenum == 1) {
					that.datalist = res.data;
		      if (data.length < 21) {
		         that.nomore = true;
		      }
					// that.loaded();
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
		joinin:function(e){
			var that = this;
			app.post("ApiDscj/dscjJoin", {id: that.opt.id,bid:that.bid}, function (res) {
				if (res.status == 1) {
					if(res.payorderid>0){
						var tourl = encodeURIComponent('/pagesA/dscj/index?id='+that.opt.id);
						app.goto('/pagesExt/pay/pay?id=' + res.payorderid + '&tourl='+tourl);
					}else{
						app.success(res.msg);
						that.tmplids = res.tmplids
						setTimeout(function () {
							that.getdata();
							that.getJoinList();
						}, 1000);
					}
					
				} else {
					app.error(res.msg);
				}
			});
		},
		sharecallback:function(){
			var that = this;
			app.post("ApiDscj/share", {hid: that.info.id}, function (res) {
				if (res.status == 1) {
					setTimeout(function () {
						that.getdata();
					}, 1000);
				} else if (res.status == 0) {//dialog(res.msg);
				}
			});
		}
  }
};
</script>
<style>
.container{}
.mgt10{margin-top: 30rpx;}
.mainbox{background: #FFFFFF;padding: 20rpx;}
.swiper-container{position:relative}
.swiper {width: 100%;height: 423rpx;overflow: hidden;}
.swiper-item-view{width: 100%;height: 423rpx;}
.swiper .img {width: 100%;height: 423rpx;overflow: hidden;}
.counter{position:absolute;right:13px;bottom:20rpx;display: flex;justify-content: center;width: 100%;}
.imageCount {width:100rpx;height:50rpx;background-color: rgba(0, 0, 0, 0.3);border-radius:40rpx;line-height:50rpx;color:#fff;text-align:center;font-size:26rpx;}

.main-container{}
.main-container .title{font-weight: bold;font-size: 32rpx;line-height: 70rpx;}
.main-container .prize{line-height: 46rpx;color: #8d8d8d;}
.main-container .number{color: #f4733b;padding-left:20rpx ;}
.main-container .rule{padding: 20rpx;line-height: 50rpx;background: #fef3e8;border-radius: 8rpx;color:#5e5e5e;}
.main-container .time{color: #222222;padding: 20rpx 0;line-height: 50rpx;}
.main-container .time .price{color: #f4733b;font-size: 32rpx;font-weight: bold;}
.main-container .time .date{font-size: 30rpx;font-weight: bold;padding: 0 10rpx;}
.main-container .joinwrap{width: 220rpx; height: 220rpx;line-height: 210rpx;border-radius: 50%;background: #f4733b;display: flex;justify-content: center;align-items: center;margin: 50rpx auto;}
.main-container .joinwrap.st-1{background: #6083ef;}
.main-container .joinwrap.st-1 .join{background: #6a8cf7;}
.main-container .joinwrap.st2{background: #b3b3b3;}
.main-container .joinwrap.st2 .join{background: #b3b3b3;}
.main-container .join{width: 200rpx; height: 200rpx;line-height: 200rpx;text-align: center;background: #f4733b;color: #FFFFFF;font-size: 38rpx;border-radius: 50%;border: #FFFFFF 3rpx solid;font-weight: 600;}

.main-container .joinnum{text-align: center;color: #8d8d8d;}
.main-container .result{display: flex;justify-content: space-between;align-items: center;border-top: 1rpx solid #edecec;margin: 20rpx 0;padding-top: 30rpx;}
.main-container .result .restag{font-size: 30rpx;font-weight: bold;}
.main-container .result .resbtn{background: #f4733b;color: #FFFFFF;padding: 10rpx 30rpx;border-radius: 50rpx;}
.joinlist{display: flex;justify-content: center;align-items: center;flex-wrap: wrap;padding: 30rpx;}
.joinlist image{width: 80rpx;height: 80rpx;border-radius: 50%;}
.joinlist .img{margin:3rpx 6rpx;}
.joinmore{color: #acacac;text-align: center;}
.detail .content{line-height: 46rpx;}

.myrecord{width: 100rpx;text-align: center;padding: 0 10rpx; height: 100rpx;background: #2f5be4;opacity: 0.7;border-radius: 50%;position: fixed;bottom: 350rpx;right: 20rpx;color: #FFF6F3;font-weight: bold;font-size: 24rpx;display: flex;justify-content: center;align-items: center;}

.popup__container{width: 80%; margin: 10% auto;}
.popup__modal{position: fixed;top:400rpx;width: 660rpx;height: 720rpx;margin: 0 auto;border-radius: 20rpx;left: 46rpx;}
.popup__content{text-align: center;padding: 20rpx 0 ;}
.popup__content image{height: 600rpx;width: 600rpx;}
</style>