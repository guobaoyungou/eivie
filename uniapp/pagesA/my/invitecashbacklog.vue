<template>
<view class="container">
	<block v-if="isload">
		<view class="content">
        <block v-for="(item, index) in datalist" :key="index"> 
          <view class="item">
            <view class="f1" style="width: 100%;">
                <view class="t1">{{item.proname}}</view>
                <block v-if="item.status>=0">
                  <view v-if="item.allmoney || item.allscore || item.allcommission" style="line-height: 50rpx;">
                      
                    <block v-if="item.status == 1">
                      <text class="t1" v-if="item.return_type && item.return_type == 1 && item.return_day >=2">
                        总返现：
                      </text>
                      <text class="t1" v-else>
                        返现：
                      </text>
                    </block>
                    <text class="t1" v-if="item.status == 0">预估返现：</text>

                    <block v-if="item.allmoney>0">
                      <text class="t1">{{item.allmoney}}{{t('余额')}}</text>
                    </block>
                    <block v-if="item.allscore>0">
                      <text class="t1" style="margin-left: 10rpx;">{{item.allscore}}{{t('积分')}}</text>
                    </block>
                    <block v-if="item.allcommission>0">
                      <text class="t1" style="margin-left: 10rpx;">{{item.allcommission}}{{t('佣金')}}</text>
                    </block>
                  </view>
                  <view v-else style="line-height: 50rpx;">
                      <text class="t1" v-if="item.status == 1">返现：</text>
                      <text class="t1" v-if="item.status == 0">预估返现：</text>
                      <block >
                        <text class="t1">0{{t('余额')}}</text>
                      </block>
                      <block >
                        <text class="t1" style="margin-left: 10rpx;">0{{t('积分')}}</text>
                      </block>
                      <block>
                        <text class="t1" style="margin-left: 10rpx;">0{{t('佣金')}}</text>
                      </block>
                  </view>
                  
                  <block v-if="item.status==1 && item.return_type && item.return_type == 1 && item.return_day >=2">
                    <view v-if="item.allmoney || item.allscore || item.allcommission" style="line-height: 50rpx;">
                      <text class="t1" style="color: #03bc01;">已返现：</text>
                      <block v-if="item.allmoney>0">
                        <text class="t1" style="color: #03bc01;">{{item.sendmoney}}{{t('余额')}}</text>
                      </block>
                      <block v-if="item.allscore>0">
                        <text class="t1" style="margin-left: 10rpx;color: #03bc01;">{{item.sendscore}}{{t('积分')}}</text>
                      </block>
                      <block v-if="item.allcommission>0">
                        <text class="t1" style="margin-left: 10rpx;color: #03bc01;">{{item.sendcommission}}{{t('佣金')}}</text>
                      </block>
                    </view>
                    
                    <view v-if="item.return_remark" style="line-height: 40rpx;">
                      <text class="t1" style="color: red;">{{item.return_remark}}</text>
                    </view>
                  </block>
                  <view v-if="item.otherremark" style="line-height: 40rpx;">
                    <text class="t1" >{{item.otherremark}}</text>
                  </view>
                </block>
                <text class="t2" v-if="item.create_time">{{item.create_time}}</text>
                <text class="t2" v-if="item.tipinfor" style="color: #ff8758;">{{item.tipinfor}}</text>
            </view>
            <view class="f2" style="width: 120rpx;flex: unset">
              <block v-if="item.status == 1">
                <block v-if="item.return_type && item.return_type == 1 && item.return_day >=2">
                  <text class="t1" v-if="item.return_status == 1 " style="color:#ff8758">发放中</text>
                  <text class="t1" v-else-if="item.return_status == -2 " style="color:#999">已停止</text>
                  <text class="t1" v-else-if="item.return_status == -1 " style="color:#bbb">已取消</text>
                </block>
                <text class="t1" v-else>已发放</text>
              </block>

              <block v-if="item.status == 0">
                <text class="t1" v-if="item.yx_invite_cashback_commission_day && item.back_commission_day==0" style="color: red;">未发放</text>
                <text class="t1" v-if="!item.yx_invite_cashback_commission_day" style="color: red;">未发放</text>
              </block>
              <text class="t1" v-if="item.status == -1" style="color: #999;">已失效</text>
              <view v-if="item.status==2">
                <view class="btn1" v-if="item.receive_st==0"  :style="{background:t('color1')}" @click="receiveinit(item.id)" >领取</view>
                <view class="btn1" v-if="item.receive_st==1"  style="background-color: #999;">今日已领</view>
              </view>
			  
            </view>
          </view>
      </block>
		</view>
    <nodata v-if="nodata"></nodata>
		<nomore v-if="nomore"></nomore>
		
		<!-- #ifdef APP-PLUS -->
			<ad-rewarded-video ref="adRewardedVideo" :adpid="adpid" :preload="false" :loadnext="false" :disabled="true" v-slot:default="{loading, error}"  @close="onadclose"	@error="onaderror">
						<view class="ad-error" v-if="error">{{error}}</view>
			</ad-rewarded-video>
		<!-- #endif -->
		
		<view v-if="showvideo" class="video-container" :style="{height:pageHeight+'px'}">
			<view class="close" v-if="showVideoClose" @tap="videoClose" >关闭</view>
			<video  class="video" id="video" :style="{height:pageHeight+'px'}" :autoplay="true" :controls="false" :src="video_url" @timeupdate="timeupdate" ></video>
		</view>
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
      isload: false,
			menuindex:-1,
			
			pre_url:app.globalData.pre_url,
      st: 0,
      datalist: [],
      datalist2: [],
      pagenum: 1,
      nodata: false,
      nomore: false,
	  
	  adpid:'',
	  rewardedvideoad:'',
	  video_url:'',
	  play_seconds:0,
	  showvideo:false,
	  id:'',
	  pageHeight:'',
	  showVideoClose:false,
	  toreceive:1//h5 正在领取，video的播放进度250ms进行回调，会多次调用接口
    };
  },

  onLoad: function (opt) {
		this.opt = app.getopts(opt);
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
  	var sysinfo = uni.getWindowInfo();
  	this.pageHeight = sysinfo.windowHeight;
  },
  methods: {
    getdata: function (loadmore) {
			if(!loadmore){
				this.pagenum = 1;
				this.datalist = [];
			}
      var that = this;
      var pagenum = that.pagenum;
      var st = that.st;
			that.nodata = false;
			that.nomore = false;
			that.loading = true;
      app.post('ApiMy/invitecashbacklog', {st: st,pagenum: pagenum}, function (res) {
				that.loading = false;
        var data = res.data;
		//视频各种参数
		if(res.rdata.adpid){
			that.adpid =res.rdata.adpid;
		}
		if(res.rdata.rewardedvideoad){
			that.rewardedvideoad =res.rdata.rewardedvideoad;
		}
		if(res.rdata.video_url){
			that.video_url = res.rdata.video_url;
		}
		if( res.rdata.play_seconds){
			that.play_seconds = res.rdata.play_seconds;
		}		
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
    changetab: function (e) {
      var st = e.currentTarget.dataset.st;
      this.st = st;
      uni.pageScrollTo({
        scrollTop: 0,
        duration: 0
      });
      this.getdata();
    },
	receiveinit(id){
		var that  = this;
		this.id =id;
		//#ifdef MP-WEIXIN
		if (app.globalData.platform == 'wx' && that.rewardedvideoad && wx.createRewardedVideoAd) {
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
				rewardedVideoAd = null;
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
		if(app.globalData.platform == 'app'){
			setTimeout(() => {
				this.$refs.adRewardedVideo.show();
			},500)
		}
		//#endif
		if (app.globalData.platform == 'h5' || app.globalData.platform == 'mp'){ 
			that.showvideo = true;
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
		this.errMsg = e.detail.errMsg;
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
			console.log(parseFloat(currentTime).toFixed(0),'currentTimecurrentTime');
			console.log(that.play_seconds,'that.play_seconds');
			//播放描述 大于 设置的描述 就领取
			
			if(currentTime == that.play_seconds  && that.toreceive == 1){
				console.log('发放');	
				that.toreceive = 0;
				that.showVideoClose = true;
				 that.receivemoney();
			}
		}else{
			//如果不设置，播放就领取
			// that.receivemoney();
		}
	},
	videoClose(){
		this.showvideo = false;
		this.showVideoClose = false;
		this.toreceive = 1;//领取完以后不能更改状态，关闭视频后才能更改为1，下次才可领取，如果领取完就更改，视频继续播放 还是会再次领取
		
	},
	receivemoney(id){
		var that = this;
		app.post('ApiMy/receiveinvitecash', {id:that.id}, function (res) {
			if(res.status ==0){
				app.error(res.msg);
				return;
			}
			app.success(res.msg);
			uni.showToast({
			  title: res.msg,
			  icon: 'none',
			  duration: 3000,
			  style: `
			    z-index: 9999;
			  `
			});
	
			that.id = '';
			setTimeout(function(){
				that.getdata();
			})
			if(!that.showvideo)that.showVideoClose = false;//视频显示按钮
		});	
	}
  }
};
</script>
<style>
.myscore{width:94%;margin:20rpx 3%;border-radius: 10rpx 56rpx 10rpx 10rpx;position:relative;display:flex;flex-direction:column;padding:70rpx 0}
.myscore .f1{margin:0 0 0 60rpx;color:rgba(255,255,255,0.8);font-size:24rpx;}
.myscore .f2{margin:20rpx 0 0 60rpx;color:#fff;font-size:64rpx;font-weight:bold; position: relative;}

.content{ width:94%;margin:0 3%;}
.content .item{width:100%;margin:20rpx 0;background:#fff;border-radius:5px;padding:20rpx 20rpx;display:flex;align-items:center}
.content .item .f1{flex:1;display:flex;flex-direction:column}
.content .item .f1 .t1{color:#000000;font-size:30rpx;word-break:break-all;overflow:hidden;text-overflow:ellipsis;}
.content .item .f1 .t2{color:#666666}
.content .item .f1 .t3{color:#666666}
.content .item .f2{ flex:1;font-size:36rpx;text-align:right}
.content .item .f2 .t1{color:#03bc01}
.content .item .f2 .t2{color:#000000}
.content .item .f3{ flex:1;font-size:32rpx;text-align:right}
.content .item .f3 .t1{color:#03bc01}
.content .item .f3 .t2{color:#000000}

.data-empty{background:#fff}
.btn-mini {right: 32rpx;top: 28rpx;width: 100rpx;height: 50rpx;text-align: center;border: 1px solid #e6e6e6;border-radius: 10rpx;color: #fff;font-size: 24rpx;font-weight: bold;display: inline-flex;align-items: center;justify-content: center;position: absolute;}
.btn-xs{min-width: 100rpx;height: 50rpx;line-height: 50rpx;text-align: center;border: 1px solid #e6e6e6;border-radius: 10rpx;color: #fff;font-size: 24rpx;font-weight: bold;margin-bottom:10rpx;padding: 0 20rpx;}
.scoreL{flex: 1;padding-left: 60rpx;color: #FFFFFF;}
.score_txt{color:rgba(255,255,255,0.8);font-size:24rpx;padding-bottom: 14rpx;}
.score{color:#fff;font-size:64rpx;font-weight:bold;}
.scoreR{padding-right: 30rpx;align-self: flex-end;flex-wrap: wrap;}
.btn1{width: 130rpx;height:50rpx;line-height:50rpx;color:#fff;border-radius:3px;text-align:center;padding: 0 15rpx;font-size: 25rpx;margin-left: 10rpx}
.video-container{
	position: absolute;
	width: 750rpx;
	top: 0;
	left: 0;
	z-index: 20;
}
.video{
	position: absolute;
	width: 750rpx;
	top: 0;
	left: 0;
}
 .close{width:120rpx;height:45rpx;position:absolute;z-index: 30;top: 45rpx;right: 35rpx;text-align: center;border: 1px solid #ffff;color: #ffff;line-height: 45rpx;border-radius:20rpx;}	
</style>