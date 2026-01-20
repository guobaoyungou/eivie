<template>
<view class="container">
	<block v-if="isload">
		<view class="topfix">
			<view class="tongji flex-y-center "  :style="{background:color1}">
				<view>
					<view class="price">{{total_back_price}}</view>
					<view>待补{{money_text}}</view>
				</view>
				<view>
					<view class="price">{{total_money}}</view>
					<view>已补{{money_text}}</view>
				</view>
			</view>
			<dd-tab :itemdata="['补贴记录','待补记录']" :itemst="['0','1']" :st="st" :isfixed="false" @changetab="changetab"></dd-tab>
		</view>
		 <view style="margin-top:300rpx"></view>
		<view class="content">
			
			<block v-if="st==0">
			<view v-for="(item, index) in datalist" :key="index" class="item">
				<view class="flex-bt flex-y-center">
					<view class="f1">
							<text class="t1"><text v-if="item.type==1 && item.cashback_name !=''">{{item.cashback_name}}</text><text v-else>补贴{{money_text}}</text>：{{item.money}}</text>
							<text class="t2">{{item.createtime}}</text>
							<text class="t2" v-if="item.status==2">过期时间：{{item.expiretime || ''}}</text>
							<text class="t2" v-if="item.status==1">领取时间：{{item.collecttime || ''}}</text>
					</view>
					<view class="f3">
							<text class="t1" style="color: red;" v-if="item.status==0">待领取</text>
							<text class="t1" v-if="item.status==1">已领取</text>
							<text class="t2" v-if="item.status==2">已过期</text>
					</view>
				</view>
				<view class="flex-x-bottom" v-if="send_condition == false">
					<view class="btn1" v-if="item.status==0" :style="{background:t('color1')}" @click="receiveinit(item.id)" >领取</view>
				</view>
			</view>
			</block>
			<block v-if="st==1">
			<view v-for="(item, index) in datalist" :key="index" class="item flex-xy-center">
				<view class="f1">
						<text class="t1">{{item.remark}}</text>
						<text class="t2">{{item.createtime}}</text>
						<text class="t3">变更后{{money_text}}: {{item.after}}</text>
				</view>
				<view class="f2">
						<text class="t1" v-if="item.money>0">+{{item.money}}</text>
						<text class="t2" v-else>{{item.money}}</text>
				</view>
			</view>
			</block>
		</view>
		<!-- #ifdef APP-PLUS -->
			<ad-rewarded-video ref="adRewardedVideo"  :adpid="adpid" :preload="false" :loadnext="false" :disabled="true" v-slot:default="{loading, error}"  @close="onadclose"	@error="onaderror">
			</ad-rewarded-video>
		<!-- #endif -->
		
		<view v-if="showvideo" class="video-container" style="height:100vh">
			<view class="close" v-if="!showVideoClose && this_play_seconds > 0" >{{this_play_seconds}}秒</view>
			<view class="close" v-if="showVideoClose" @tap="videoClose" >关闭</view>
			<video  class="video" id="video" style="height:100vh" :autoplay="true" :controls="false" :src="video_url" @timeupdate="timeupdate" @ended="videoend" ></video>
		</view>
		<view class="signing" :style="signing ? 'background-color:rgba(17,17,17,0.3)' : 'background-color:' + t('color1')" v-if="send_condition" @tap="handleSignin">{{signing ? '已签到' : '签到'}}</view>
	</block>
	<nodata v-if="nodata"></nodata>
	<nomore v-if="nomore"></nomore>
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
		isload: false,
		menuindex:-1,

		nodata: false,
		nomore: false,
		st: 0,
		type:0,
		datalist: [],
		textset:{},
		pagenum: 1,
		set:{},
		total_back_price:0,
		total_money:0,
		adpid:'',
		rewardedvideoad:'',
		video_url:'',
		play_seconds:0,
		showvideo:false,
		id:'',
		pageHeight:'',
		showVideoClose:false,
		toreceive:0,//h5 正在领取，video的播放进度250ms进行回调，会多次调用接口
		send_condition:false,//签到领取
		signing: false, // 签到
		money_text:'金额',//金额文字自定义
		this_play_seconds:0,
    };
  },

  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.st = this.opt.st || 0;
		this.type = this.opt.type || 0;
		var that = this;
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
  onReady() {
  	var sysinfo = uni.getSystemInfoSync();
  	this.pageHeight = sysinfo.windowHeight;
  },
  methods: {
    getdata: function (loadmore) {
		if(!loadmore){
			this.pagenum = 1;
			this.datalist = [];
		}
		var that = this;
		var st = that.st;
		var type = that.type;
		var pagenum = that.pagenum;
		that.loading = true;
		that.nodata = false;
		that.nomore = false;
		app.post('ApiAgent/cashbackadduprecord', {st: st,pagenum: pagenum,type:type}, function (res) {
			that.loading = false;
			var data = res.data;
			that.total_back_price = res.rdata.total_back_price;
			that.total_money = res.rdata.total_money;
			that.adpid = res.rdata.adpid;
			that.rewardedvideoad = res.rdata.rewardedvideoad;
			that.video_url = res.rdata.video_url;
			that.play_seconds = res.rdata.play_seconds;
			if(res.rdata && res.rdata.send_condition){
				that.send_condition = res.rdata.send_condition;
			}
			if(res.rdata && res.rdata.signing){
				that.signing = true;
			}
			if(res.rdata && res.rdata.money_text){
				that.money_text = res.rdata.money_text;
			}
			that.this_play_seconds =res.rdata.play_seconds;
			if (pagenum == 1) {
				that.textset = app.globalData.textset;
				that.set = res.set;
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
    changetab: function (st) {
      this.st = st;
      uni.pageScrollTo({
        scrollTop: 0,
        duration: 0
      });
      this.getdata();
    },
	async confirm_shoukuan(id){
		var that = this;
		var a = await that.shoukuan(id,'member_commission_withdrawlog','');
		console.log(a);
		that.getdata();
	},
	receiveinit(id){
		var that  = this;
		this.id =id;
		var is_play_local_video = true;//是否播放自己上传的视频
		//#ifdef MP-WEIXIN
		if (app.globalData.platform == 'wx' && that.rewardedvideoad && wx.createRewardedVideoAd) {
			is_play_local_video = false;
			app.showLoading();
			
			var rewardedVideoAd = wx.createRewardedVideoAd({ adUnitId: that.rewardedvideoad});
			rewardedVideoAd.load().then(() => {app.showLoading(false);rewardedVideoAd.show();}).catch(err => { app.alert('加载失败');});
			rewardedVideoAd.onError((err) => {
				app.showLoading(false);
				app.alert(err.errMsg);
				console.log('onError event emit', err)
				rewardedVideoAd.offLoad()
				rewardedVideoAd.offClose();
			});
			rewardedVideoAd.onClose(res => {
				app.globalData.rewardedVideoAd[couponinfo.rewardedvideoad] = null;
				if (res && res.isEnded) {
					//app.alert('播放结束 发放奖励');
					that.receivemoney();
				} else {
					console.log('播放中途退出，不下发奖励');
				}
				rewardedVideoAd.offLoad()
				rewardedVideoAd.offClose();
			});
			
		}
		//#endif
		//#ifdef APP-PLUS
		if(app.globalData.platform == 'app' && that.adpid){
			is_play_local_video = false;
			setTimeout(() => {
				this.$refs.adRewardedVideo.show();
			},500)
		}
		//#endif
		
		if (is_play_local_video){ 
			that.toreceive = 0;
			that.showvideo = true;
			that.getdjs();
		}
		
	},
	//adset广告
	onadload(e) {},
	onadclose(e) {
		const detail = e.detail
		// 用户点击了【关闭广告】按钮
		if (detail && detail.isEnded) {
			// 正常播放结束
			this.receivemoney();
		} else {
			// 播放中途退出
			console.log('播放中途退出，不下发奖励');
		}
	},
	onaderror(e) {
		var error = JSON.stringify(e.detail)
		this.errMsg = error.errMsg;
	  // 广告加载失败
		uni.showModal({
			title: '错误',
			content: this.errMsg,
			showCancel:false,
			confirmText:'已知晓'
		});
	  
	},
	//video播放进度变化时触发
	timeupdate(e){
		var that = this;
		
		if(that.play_seconds >0){
			//实时播放进度 秒数
			var currentTime = parseFloat(e.detail.currentTime).toFixed(0);
			
			//播放描述 大于 设置的描述 就领取
			if(currentTime == that.play_seconds && that.toreceive == 0){
				console.log('发放');	
				that.toreceive = 1;
				that.showVideoClose = true;
				 that.receivemoney();
			}
		}else{
			//如果不设置，播放就领取
			// that.receivemoney();
		}
	},
	videoend(e){
		this.showvideo = false;
		this.showVideoClose = false;
		this.toreceive = 0;
		this.getdata();
	},
	getdjs(){
		var that = this;
		that.this_play_seconds = that.play_seconds;
		var djsinterval =null;
		 djsinterval = setInterval(function () {
			that.this_play_seconds = that.this_play_seconds - 1;
			console.log(that.this_play_seconds,'that.this_play_seconds');
			if(that.this_play_seconds ==0){
				clearInterval(djsinterval)
			}
		}, 1000);
		
	},
	videoClose(){
		this.showvideo = false;
		this.showVideoClose = false;
		this.toreceive = 0;
		this.getdata();
	},
	receivemoney(id){
		var that = this;
		app.post('ApiAgent/receivebackprice', {id:that.id}, function (res) {
			if(res.status ==0){
				app.error(res.msg);
				return;
			}
			app.success('领取成功');
			that.id = '';
			that.toreceive = 1;
			setTimeout(function(){
				//that.getdata();
			})
			if(!that.showvideo)that.showVideoClose = false;//视频显示按钮
		});	
	},
	handleSignin:function(){
		var that = this;
		if (that.signing) return;
		app.post('ApiCashback/signin', {}, function(res) {
			if (res.status === 1) {
				that.signing = true;
				app.success(data.msg);
				return;
			} 
			that.signing = false;
			app.error(res.msg);
		})
	}
  }
};
</script>
<style>

.content{ width:94%;margin:20rpx 3%;}
.content .item{width:100%;background:#fff;margin:20rpx 0;padding:30rpx 30rpx;border-radius:8px;}
.content .item:last-child{border:0}
.content .item .f1{width:500rpx;display:flex;flex-direction:column}
.content .item .f1 .t1{color:#000000;font-size:30rpx;word-break:break-all;overflow:hidden;text-overflow:ellipsis;}
.content .item .f1 .t2{color:#666666;font-size:24rpx;margin-top:10rpx}
.content .item .f1 .t3{color:#666666;font-size:24rpx;margin-top:10rpx;display: flex;}
.content .item .f1 .t3 image{width:40rpx;height:40rpx;border-radius:50%;margin-right:4px;align-content: center;}
.content .item .f2{ flex:1;width:200rpx;font-size:36rpx;text-align:right}
.content .item .f2 .t1{color:#03bc01}
.content .item .f2 .t2{color:#000000}
.content .item .f3{ flex:1;width:200rpx;font-size:32rpx;text-align:right}
.content .item .f3 .t1{color:#03bc01}
.content .item .f3 .t2{color:#000000}

.data-empty{background:#fff}
.btn1{width: 120rpx;height:50rpx;line-height:50rpx;color:#fff;border-radius:3px;text-align:center;padding: 0 15rpx;font-size: 25rpx;margin-left: 10rpx}
.content .item .f1 .x3{display:flex;align-items:center;margin-top:10rpx}
.content .item .f1 .x3 image{width:40rpx;height:40rpx;border-radius:50%;margin-right:4px}

.topfix{width: 100%;position:relative;position:fixed;background: #f9f9f9;top:var(--window-top);z-index:11;}
.tongji{width: 94%;margin: 30rpx 3% 0 3%;padding: 30rpx;border-radius: 20rpx; background-color: #F2350D;height: 160rpx;color: #fff;position: relative;justify-content: space-evenly;text-align: center;}
.tongji .price{font-size: 40rpx;font-weight: 700;}

.video-container{
	position: fixed;
	width: 100vw;
	top: 0;
	left: 0;
	z-index: 20;
	overflow: hidden;
}
.video{
	width: 100vw;
	top: 0;
	left: 0;
}
 .close{width:120rpx;height:45rpx;position:absolute;z-index: 30;top: 45rpx;right: 35rpx;text-align: center;border: 1px solid #ffff;color: #ffff;line-height: 45rpx;border-radius:20rpx;background-color: #aaa;}	
 .signing{ width: 150rpx; height: 70rpx; position: fixed; right: 0%; bottom: 20%; display: flex; align-items: center; border-top-left-radius: 30rpx; border-bottom-left-radius: 30rpx; justify-content: center;color: #fff;}
</style>