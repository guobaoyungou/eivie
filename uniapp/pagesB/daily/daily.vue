<template>
<view>
	<block v-if="isload">
		<view class="daily-detail">
			<view class="box-name flexc">
				<view class="title">	[{{sysset.name}}]</view>
				<view class="ml10 hou">{{detail.name}}</view>
			</view>
			<view class="box-wq" @tap.stop="goto" :data-url="'/pagesB/daily/wqlist'">
					<view class="see">查看往期</view>
			</view>
			<view class="box-content mlt30">
				<view class="logo"><image class="imgs" :src="sysset.logo" /></view>
				<view class="content-audio flexjsa" v-if="detail.voice_url">
					<view class="left">
							<image class="bf" :src="pre_url+'/static/img/daily/bf.png'" />
							<text>{{detail.voice_duration}}</text>
					</view>
					<view class="right flex" v-if="playshow" @tap="play">
						<text>点击听语音</text>
						<audio :src="detail.voice_url"></audio>
						<image :src="pre_url+'/static/img/daily/sz.png'" />
					</view>
					<view class="right flex" v-if="!playshow" @tap="pause">
						<text>点击关语音</text>
						<audio :src="detail.voice_url"></audio>
						<image :src="pre_url+'/static/img/daily/sz.png'"  />
					</view>
				</view>
				<view class="content">
					<view v-html="detail.content"></view>
				</view>
				<view class="time mlt30">
					<text>{{sysset.name}}</text>
					<text class="ml10">{{detail.fabutime}}</text>
				</view>
			</view>
			
			<view class="box-content2" v-if="detail.tourldata">
				<view class="item" v-for="(item, index) in detail.tourldata" :key="index"  @tap.stop="goto" :data-url="item.tourl">
					<view class="image">
						<image :src="item.tourlpic" />
					</view>
					<view class="more" @tap.stop="goto" :data-url="item.tourl">查看</view>
				</view>
				<!-- <view class="item">
					<view class="image">
						<image src="../../static/img/cs.png" />
					</view>
					<view class="more">查看</view>
				</view> -->
			</view>
			<block v-if="detail.is_ganwu" >
			<view class="box-ganwu mlt40" > 已分享过感悟 </view>
			</block>
			<block  v-else>	
				<block v-if="detail.can_daka">
					<view class="box-ganwu mlt40" @tap="share"> 分享自己的感悟并打卡	</view>
					<view class="box-daka mlt30" v-if="detail.is_daka == 0" @tap="daka" >不了, 直接打卡	</view>
				</block>
			</block>
			
			<view class="box-commont mlt40">
				<view class="top">全部感悟</view>
				<block v-for="(item, index) in datalist" :key="index">
				<view class="commont flexcm">
					<view class="flex">
						<view class="commont-left">
							<image class="headimg" :src="item.headimg" />
						</view>
						<view class="commont-right flexcm">
							<view class="commont-top">{{item.nickname}}    {{item.createtime}}</view>
							<view>{{item.ganwu}}</view>
							<view class="commont-bottom">
								<view class="cuIon" @tap="showPoster" :data-orderid="item.id" :data-mid="item.mid" :data-index="index" >
										<image :src="pre_url+'/static/img/daily/share.png'" />
										<text class="ml10">{{item.share_num}}</text>
								</view>
								<view class="cuIon  ml40 cuIcon-like">
										<image v-if="item.is_star == 0" @tap="savestar" data-status="1" :data-orderid="item.id" :data-index="index"  :src="pre_url+'/static/img/daily/fav.png'" />
										<image v-if="item.is_star == 1" @tap="savestar" data-status="0" :data-orderid="item.id" :data-index="index" :src="pre_url+'/static/img/daily/fav2.png'" />
										<text class="ml10">{{item.star_num}}</text>
								</view>
							</view>
						</view>
					</view>
					<view class="border"></view>
				</view>
				</block>
				<nomore v-if="nomore"></nomore>
				<nodata v-if="nodata"></nodata>
			</view>
			
			<view class="cu-modal" v-if="showmodal">
				<form @submit="saveganwu" @reset="formReset" report-submit="true">
				<view class="cu-dialog">
						<view class="cu-bar bg-white justify-end">
							<view class="content">感悟</view>
							<view class="close" @tap="close"><image :src="pre_url+'/static/img/close.png'"></view>
						</view>
						<view class="padding-xl">
							<textarea placeholder="请输入感悟"  id="ganwuinput" name='ganwuval' style="background: #F8F8F8; "></textarea>
						</view>
						<view class="cu-bar bg-white justify-center">
							<view class="aciton">
								<button class="cu-btn1" @tap="close" >取消</button>
								<button class="cu-btn2" form-type="submit">确定</button>
							</view>
						</view>
				</view>
				</form>
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
						<button class="f1" open-type="share" v-if="getplatform() != 'h5'">
							<image class="img" :src="pre_url+'/static/img/sharefriends.png'"/>
							<text class="t1">分享给好友</text>
						</button>
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
		
		<view class="covermy-view flex-col" :class="menuindex>-1?'tabbarbot':'notabbarbot'">
			<view class="covermy" @tap="goto" data-url="pages/index/index"><image :src="pre_url+'/static/img/lt_gohome.png'"></image></view>
			
		</view>
		
		
		</view>
		<view style="width:100%;height:20rpx"></view>
	</block>
	<loading v-if="loading"></loading>
	<dp-tabbar :opt="opt"></dp-tabbar>
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
			isload: true,
			menuindex:-1,
			playshow:true, //播放
			stipshow:false, //暂停
			ganwuval:'', //暂停
			pre_url:app.globalData.pre_url,
			detail: [],
			sysset: [],
			datalist: [],
			pagenum: 1,
			nomore: false,
			nodata: false,
			sharepic: "",
			sharetypevisible: false,
			showposter: false,
			posterpic: "",
			showmodal:false
    };
  },

  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		var that = this;

		this.getdata();
  },

  methods: {
		getdata: function () {
			var that = this;
			var id = this.opt.id || 0;
			that.id = id;
			that.loading = true;
			app.get('ApiDailyjinju/getdetail', {id: id}, function (res) {
				console.log(res);
				if(res.status == 1){
					var detail = res.data.info;
					that.detail = detail;
					that.sysset = res.data.sysset;
					that.getdatalist();
				}else{
				  app.alert(res.msg)
				}
			});

		},
		getdatalist: function (loadmore) {
				if(!loadmore){
					this.pagenum = 1;
					this.datalist = [];
				}
		  var that = this;
		  var pagenum = that.pagenum;
		  var proid = that.detail.id;
				that.nodata = false;
				that.nomore = false;
				that.loading = true;
		  app.post('ApiDailyjinju/ganwulist', {pagenum: pagenum,proid:proid}, function (res) {
					that.loading = false;
		    var data = res.datalist;
					var ishowpaidan = res.ishowpaidan;	
					that.ishowpaidan = ishowpaidan
		    if (pagenum == 1) {
						that.datalist = data;
		      if (data.length == 0) {
		        that.nodata = true;
		      }
						that.loaded();
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
		savestar: function (e) {
		  var that = this;
		  var proid = that.detail.id;
		  var orderid = e.currentTarget.dataset.orderid;
		  var status = e.currentTarget.dataset.status;
		  var index = e.currentTarget.dataset.index;
			app.showLoading('提交中');
		  app.post('ApiDailyjinju/savestar', {proid:proid,orderid:orderid,status:status }, function (data) {
					app.showLoading(false);
				if (data.status == 0) {
				  app.error(data.msg);
				  return;
				} else {
					app.success(data.msg);
					that.datalist[index]['star_num'] = data.star_num;
					that.datalist[index]['is_star'] = status;
					//that.getdatalist();
				}
		  });
		},
		daka: function () {
		  var that = this;
		  var proid = that.detail.id;
			app.showLoading('提交中');
		  app.post('ApiDailyjinju/daka', {proid:proid }, function (data) {
					app.showLoading(false);
				if (data.status == 0) {
				  app.error(data.msg);
				  return;
				} else {
					app.success(data.msg);
					that.getdata();
				}
		  });
		},
		saveganwu: function (e) {
		  var that = this;
		  var proid = that.detail.id;
		  var ganwuval = e.detail.value.ganwuval;
			if(ganwuval == ''){
				app.error('请输入感悟');
				return;
			}
		  
			app.showLoading('提交中');
		    app.post('ApiDailyjinju/saveganwu', {proid:proid,ganwu:ganwuval}, function (data) {
					app.showLoading(false);
				if (data.status == 0) {
				  app.error(data.msg);
				  return;
				} else {
					app.success(data.msg);
					that.showmodal=false
					that.getdata();					
					that.showPosterMy();					
				}
		  });
		},
		play() {
		  const audioElem = this.$el.querySelector('audio')
		  audioElem.play()
		  this.playshow = false
		},
		pause() {
		  const audioElem = this.$el.querySelector('audio')
		  audioElem.pause()
		  this.playshow = true
		},
		share:function(){
				var that = this;
				that.showmodal=true
		},
		close:function(){
			var that = this;
			that.showmodal=false
		},
		shareClick: function () {
			this.sharetypevisible = true;
		},
		handleClickMask: function () {
			this.sharetypevisible = false
		},
		showPosterMy: function () {
			var that = this;
			that.sharetypevisible = false;
			app.post('ApiDailyjinju/getposter', {proid: that.detail.id}, function (data) {
				app.showLoading(false);
				if (data.status == 0) {
					app.alert(data.msg);
				} else {
					that.posterpic = data.poster;
					that.showposter = true;
				}
			});
		},
		showPoster: function (e) {
			var that = this;			
			that.sharetypevisible = false;
			var orderid = e.currentTarget.dataset.orderid;
			var mid = e.currentTarget.dataset.mid;
			var index = e.currentTarget.dataset.index;
			app.showLoading('生成海报中');
			app.post('ApiDailyjinju/getposter', {proid: that.detail.id,orderid:orderid,mid:mid}, function (data) {
				app.showLoading(false);
				if (data.status == 0) {
					app.alert(data.msg);
				} else {
					that.posterpic = data.poster;
					that.showposter = true;
					that.datalist[index]['share_num'] = that.datalist[index]['share_num'] + 1;
				}
			});
		},
		posterDialogClose: function () {
			this.showposter = false;
		},
	},
};
</script>
<style>
.flexc{ display: flex; justify-content: center; align-items: center;}
.ml10{ margin-left: 10rpx;}
.mlt30 {  margin-top: 30rpx;}
.mlt40{ margin-top: 40rpx;}
.ml40{ margin-left: 40rpx;}
.flexcm{ display: flex; flex-direction: column;}
.daily-detail{color: rgb(255, 255, 255);  background: rgb(60, 61, 79); padding: 30rpx; min-height: 100vh; }
.daily-detail .box-name .title{ font-weight: 700; font-size: 42rpx; letter-spacing: 4rpx; }
.daily-detail .box-name .hou{ font-weight: 700; font-size: 42rpx; letter-spacing: 4rpx; }
.daily-detail .box-wq{text-align: right; margin-top: 26px;}
.daily-detail .box-wq .see{ display: inline-block; height: 52rpx;  line-height: 48rpx; border-radius: 30rpx; padding: 0px 30rpx;border: 1px solid #fff; font-size: 24rpx; letter-spacing: 2rpx;}

.daily-detail .box-content{position: relative; padding: 124rpx 40rpx 40rpx 40rpx;  min-height: 530rpx;    border-radius: 10rpx;  background-color: #fff; color: #000; margin-top: 30rpx;}
.daily-detail .box-content .logo { position: absolute; left: calc(50% - 35px); top: -36px;}
.daily-detail .box-content .logo .imgs{  width: 144rpx; height: 144rpx; border-radius: 50%; }
.daily-detail .box-content .content-audio{    height: 88rpx; line-height: 88rpx;  padding: 0 20rpx;border-radius: 40rpx;background: rgb(225, 202, 152); color: rgb(255, 255, 255); display: flex; justify-content: space-between; }
.daily-detail .box-content .content-audio .left{ width: 60%; align-items: center; display: flex;}
.daily-detail .box-content .content-audio .left .bf{width: 30rpx; height: 30rpx;}
.daily-detail .box-content .content-audio .right{ display: flex; align-items: center;}
.daily-detail .box-content .content-audio .right image{width: 40rpx; height: 40rpx;}


.daily-detail .box-commont .top{ padding:26rpx;border-bottom: 1px solid rgb(43, 43, 53);}
.daily-detail .box-content .content{ font-size: 38rpx;font-weight: 700;  line-height: 2; margin-top: 40rpx;}
.daily-detail .box-content .time {  text-align: right; font-size: 32rpx; font-weight: 700;}

.box-content2{position: relative; padding: 40rpx 40rpx 10rpx 40rpx;  border-radius: 10rpx;  background-color: #fff; color: #000; margin-top: 30rpx; height: auto;}
.box-content2 .item{ display: flex; justify-content: space-between; align-items: center; margin-bottom: 30rpx;}
.box-content2 .item .image {width: 270rpx; height: 164rpx; }
.box-content2 .item .image image{width:100%; height: 100%; }
.box-content2 .item .more{ display: ;}

.daily-detail .box-ganwu{height: 92rpx;line-height: 94rpx; text-align: center; border-radius: 10rpx;background: rgb(225, 202, 152);color: rgb(255, 255, 255);}
.daily-detail .box-daka {   height: 92rpx; line-height: 92rpx; text-align: center; border-radius: 10px; border: 1px solid #5d5c5c;}

.daily-detail .box-commont .commont { padding: 26rpx;}
.daily-detail .box-commont .commont .commont-left .headimg{   width: 82rpx;  height: 82rpx; border-radius: 50%;}
.daily-detail .box-commont .commont .commont-right{ padding:16rpx 26rpx; width: 100%;}
.daily-detail .box-commont .commont .commont-right .commont-top{width: 94%;  max-width: 94%;display: inline-block; align-items: center; color: #a6a8b5;}
.daily-detail .box-commont .commont .commont-right .commont-center{ max-width: 90%; margin-top: 14rpx;}
.daily-detail .box-commont .commont .commont-right .commont-bottom{ margin-top: 30rpx; font-size: 24rpx; display: flex; align-items: center;}
.commont-bottom .cuIon{ display: flex; align-items: center; color: #E1CA98;}
.commont-bottom .cuIon image{ width:40rpx; height: 40rpx;}
.commont-bottom .cuIcon-like image{ width: 32rpx; height: 32rpx;}

.box-commont .border{border-bottom: 1px solid rgb(43, 43, 53); height: 2rpx; width:100rpx; }

.cu-modal{ position: fixed; top:0; right: 0; width: 100%; height: 100%; background: rgb(60, 61, 79,0.4);transform: scale(1); overflow-x: hidden;text-align: center;overflow-y: auto;pointer-events: auto;}
.cu-dialog{  position: relative;  width: 85%; border-radius: 10rpx; overflow: hidden; background: #f8f8f8;    vertical-align: middle;    display: inline-block;
  margin-left: auto; margin-right: auto; top:30% }
.cu-dialog .cu-bar{  position: relative; }
.cu-dialog .content{  color: #666; min-height: 104rpx; line-height: 104rpx; background: #fff;}
.cu-dialog .cu-bar .close{ width: 30rpx; height: 30rpx; position: absolute;  right: 20rpx;top:30rpx; }
.cu-dialog .cu-bar .close image{ width: 100%; height: 100%;}
.padding-xl{  width: 100%;padding:0 52rpx;  background: #F8F8F8}
.padding-xl textarea{ width: 100%; color: #000;text-align: left; padding:20rpx} 
.cu-bar{ background: #fff; min-height: 104rpx; display: flex; align-items: center;justify-content: center; }
.cu-bar .aciton { display: flex; align-items: center;}
.cu-bar .aciton .cu-btn1{ height: 40px; line-height: 40px; text-align: center; width: 120rpx;border: 0.5px solid rgb(225, 202, 152); color: rgb(225, 202, 152); margin-right: 30rpx; border-radius: 10rpx;}
.cu-bar .aciton .cu-btn2{ height: 40px; line-height: 40px; text-align: center; width: 120rpx;background:rgb(225, 202, 152); color: #fff; border-radius: 10rpx;}

.covermy-view{position:fixed;z-index:99999;bottom:0;right:20rpx;width:126rpx;height: 250rpx;box-sizing:content-box;justify-content: space-between;margin-bottom: 140rpx;}
.covermy{width:126rpx;height:126rpx;box-sizing:content-box;}
.covermy image{width:100%;height:100%;}
</style>