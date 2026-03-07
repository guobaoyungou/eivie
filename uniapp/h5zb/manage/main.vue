<template>
<view class="container" :class="clientwidth>480?'border':''">
	<view class="main-mobile">
	<block v-if="isload">
		<!-- #ifdef H5 || MP-WEIXIN-->
		<view class="fixbox" :style="{height:(videoHeight+50)+'px'}">
			<view class="watch_num"><image class="eye" :src="pre_url+'/static/img/eye2.png'"></image>{{watch_num}}</view>
			<view class="video_main" :style="{height:videoHeight+'px'}">
				<view class="videoBox" :style="{height:videoHeight+'px'}">
					<div id="local_video" v-if="detail.type==0" style="width: 100%;overflow: hidden;z-index: 999;">
						<image v-if="islive==0" :src="detail.pic" mode="aspectFill"></image>
					</div>
					<block v-if="detail.type==1">
						<video class="videoBox" id="video" v-if="detail.live_status==1"  :show-center-play-btn="false" :src="detail.video_url" :poster="detail.pic" :initial-time="playtime" @timeupdate="timeupdate" object-fit="contain" @ended="playEnd" :controls="false" autoplay="true"></video>
						<image class="videoBox" v-else :src="detail.pic" mode="aspectFill"></image>
					</block>
				</view>
				<!-- <view class="countdown" v-if="detail.live_status==-1">开播倒计时：{{countdown_show}}</view> -->
				<view class="countdown1" v-if="detail.live_status==1">开播时长：{{timer_show}}</view>
				<view class="countdown1" v-if="detail.live_status==2"><text v-if="detail.show_video_time">[已结束] 共直播{{detail.show_video_time}}</text><text v-else>已结束</text></view>
				<view class="countdown1" v-if="detail.live_status==0 || detail.live_status==-1"><text v-if="detail.starttime>0">直播时间：{{detail.zbdate}}</text><text v-else>直播未开始</text></view>
			</view>
			<dd-tab class="zb-dd-tab" :itemdata="['直播互动','直播介绍','直播商品']" :itemst="['0','1','2']" :st="st" @changetab="changetab"></dd-tab>
		</view>
		<scroll-view class="content" scroll-y @scrolltolower="getmore" :scroll-top="scrollHeight" :style="{top:(videoHeight+50)+'px',height:'calc(100vh - '+(videoHeight + 50)+'px)'}">
			<view v-if="isprotop">
				<view class="toppro" :style="'background:rgba('+t('color2rgb')+',0.2);'">
					<image class="proimg" :src="toppro.pic"></image>
					<view class="proinfo">
						<view class="title">
							<text>{{toppro.name}}</text>
							<image :src="pre_url+'/static/img/close.png'" class="close" @tap="closeTop"></image>
						</view>
						<view class="t1">库存：{{toppro.stock}}</view>
						<view class="t2">
							<view :style="{color:t('color1')}">￥<text class="price">{{toppro.sell_price}}</text></view>
							<view class="btn">立即购买</view>
						</view>
					</view>
				</view>
			</view>
			<view class="eventbox" v-if="st=='1'"><dp :pagecontent="pagecontent"></dp></view>
			<!-- 互动联通Start -->
			<view  class="eventbox filter-scroll-view-box" v-if="st=='0'">
				<view class="message-list">
					<block v-for="(item, index) in datalist" :key="index">
						<view class="message-time" v-if="item.formatTime">{{item.formatTime}}</view>
						<block v-if="item.msgtype=='bonus'">
							<view class="bonus">
								<view class="bonus-txt">{{item.content}}</view>
							</view>
						</block>
						<block v-else>
							<view class="message-item">
								<!-- <image class="message-avatar" mode="aspectFill" :src="item.headimg" @tap="memberInfo" :data-mid="item.mid"></image> -->
								<view @tap="memberInfo" :data-mid="item.mid"><text v-if="item.tag" class="message-tag" :style="'background:rgba('+t('color1rgb')+',0.15);color:'+t('color1')">{{item.tag}}</text>{{item.nickname}}</view>
								<view class="message-text">
									<parse :content="item.content" />
								</view>
							</view>
						</block>
					</block>
					<nomore v-if="nomore"></nomore>
				</view>
			</view>
			<!-- 互动联通End -->
			<view v-if="st=='2'"  class="probox">
				<view class="prolist" v-for="(item,index) in prolist" :key="index">
					<image class="proimg" :src="item.pic"></image>
					<view class="proinfo">
						<view class="title">{{item.name}}</view>
						<view class="t1">
							<text>库存：{{item.stock}}</text>
							<text :class="'tag'+' status'+item.status">{{item.status==1?'已上架':'未上架'}}</text>
						</view>
						<view class="t2">
							<view :style="{color:t('color1')}">￥<text class="price">{{item.sell_price}}</text></view>
							<view class="buyopt" >
								<view class="btn" v-if="item.status==0" @tap="setst(index)">上架</view>
								<view class="btn" v-if="item.status==1" @tap="setst(index)">下架</view>
								<view class="btn" v-if="item.status==1" :style="'background:rgba('+t('color1rgb')+',0.2);color:'+t('color1')" @tap.stop="setTopPro" :data-index="index">置顶</view>
							</view>
						</view>
					</view>
				</view>
				<view class="eventbox" v-if="pnodata"><nodata ></nodata></view>
			</view>
			<block v-if="detail.type==0">
				<view class="fastopt" @tap="startPush()" v-if="detail.live_status==0" :style="'background:rgba('+t('color2rgb')+',0.4);color:'+t('color2')">开播</view>
				<view class="fastopt2" :style="'background:rgba('+t('color1rgb')+',0.3);color:'+t('color1')">
					<view class="fopt" @tap="startPush('restart')" v-if="detail.live_status==1" :style="{borderColor:t('color1')}">重连</view>
					<view class="fopt" @tap="changeCamera" v-if="detail.live_status==1 && cameraNum > 1" :style="{borderColor:t('color1')}">翻转</view>
					<view class="fopt" @tap="endPush" v-if="detail.live_status==1">结束</view>
				</view>
			</block>
			<!-- 调试用 -->
		<!-- 	<view @tap="getDevice()" style="padding: 20rpx;">getDevice:{{devicestr}}</view>
			<view  style="padding: 20rpx;">cameraNum:{{cameraNum}}</view> -->
		</scroll-view>
		
		<view v-if="showmember" class="alert_bg">
			<view class="alert_content">
				<view class="alert_title">
					<view class="alert_txt"></view>
					<image class="alert_close" :src="pre_url+'/static/img/close.png'" @tap="hideMemberInfo"></image>
				</view>
				<view class="alert_main">
					<view class="flex-row">
						<view class="flex-lable">会员昵称：</view>
						<view class="flex-value">
							<view class="flex-y-center">
								<image :src="member.headimg" class="headimg"></image>
								<text>{{member.nickname}}</text>
							</view>
						</view>
					</view>
					<view class="flex-row">
						<view class="flex-lable">会员状态：</view>
						<view class="flex-value">{{member.tag}}</view>
					</view>
					<view class="flex-row">
						<view class="flex-lable">订单数量：</view>
						<view class="flex-value">{{member.order_num}}</view>
					</view>
					<view class="flex-row">
						<view class="flex-lable">订单金额：</view>
						<view class="flex-value">￥{{member.order_money}}</view>
					</view>
					<view class="flex-row">
						<view class="flex-lable">退款金额：</view>
						<view class="flex-value">￥{{member.refund_money}}</view>
					</view>
				</view>
				<view class="alert_bottom">
					<view class="btn" v-if="member.type>0"  @tap="changeBlacklist" :data-mid="member.id" data-type="0">解除</view>
					<view class="btn" v-if="member.type==0" @tap="changeBlacklist" :data-mid="member.id" data-type="2">拉黑</view>
					<view class="btn" v-if="member.type==0" @tap="changeBlacklist" :data-mid="member.id" data-type="1">禁言</view>
					<view class="btn" v-if="member.type==0 " @tap="setRoomMids" :data-mid="member.id">设为管理员</view>
				</view>
			</view>
		</view>
		<!-- #endif  -->
		
		<view class="chat-bottom" v-if="st==0">
			<view class="nosay" v-if="detail.pinglun_banned==1">禁言中</view>
			<view class="input-box notabbarbot" id="input-box" v-else>
				<view class="input-form">
					<!-- <image v-if="detail.pinglun_noimg==0 || !detail.pinglun_noimg" @tap="sendimg" class="pic-icon" :src="pre_url+'/static/img/msg-pic.png'"></image> -->
					<image @tap="showpro" class="cart-icon" :src="pre_url+'/static/img/shortvideo_cart.png'"></image>
					<input @confirm="sendMessage" @focus="onInputFocus" @input="messageChange" class="input" :confirmHold="true" confirmType="send" cursor-spacing="20" type="text" :value="message" maxlength="-1"/>
					
					<image @tap="toggleFaceBox" class="face-icon" :src="pre_url+'/static/img/face-icon.png'"></image>
					<!-- <button class="send-button" v-if="!trimMessage" :style="{background:t('color1')}">
						发送
					</button> -->
					<button @tap="sendMessage" class="send-button-active" v-if="trimMessage" :style="{background:t('color1')}">
						发送
					</button>
				</view>
				<!-- <view>{{sss}}</view> -->
				<wxface v-if="faceshow" @selectface="selectface"></wxface>
			</view>
			<!-- <view :class="'anit ' + (msgtipsShow == 1?'show':(msgtipsShow == 2?'hide':''))" @tap="goto" :data-url="'index?bid='+msgtips.bid">{{msgtips.unickname}}：{{msgtips.content}}</view> -->
		</view>
			
	</block>
	<loading v-if="loading"></loading>
	</view>
</view>
</template>
<script>
var app = getApp();
import TXLivePusher211Min from '@/h5zb/manage/txlive/TXLivePusher-2.1.1.min.js';
// import TXLive from '@/h5zb/manage/txlive/TXLivePusher-2.1.1.min.js';
const livePusher = new TXLivePusher211Min;
const deviceManager = livePusher.getDeviceManager();
let cameraDeviceId = null;
export default {
  data() {
    return {
			opt:{},
			loading:false,
      isload: false,
			menuindex:-1,
			bid:0,
			pre_url: app.globalData.pre_url,
			platform: app.globalData.platform,
			rtcurl:'',
			isSupport:false,
			st:0,
			status:0,
			pagecontent:{},
			detail:{},
			prolist:[],
			ppagenum: 1,
			pnomore: false,
			pnodata:false,
			roomid:0,
			datalist:[],
			pagenum: 1,
			nomore: false,
			nodata:false,
			isprotop:false,
			toppro:{},
			//聊天
			msgtipsShow:0,
			msgtips:{},
			bid:0,
			token:'',
			nowtime:'',
			pagenum: 1,
			datalist: [],
			message: "",
			trimMessage: "",
			faceshow: false,
			nomore: false,
			sss:0,
			isquestionrecord:0,
			showQuestionGobtn:false,//答题按钮
			scrollTop:100000,
			currentTime:0,
			playtime:0,
			//用户操作
			isgl:false,
			showmember:false,
			member:{},
			islive:0,
			intervalB:'',
			timer:0,
			timer_show:'',
			watch_num:0,
			videoContext:'',
			isplay:false,
			isposter:false,
			posterurl:'',
			videoControls:false,//当已经存在完播时，支持拖动播放
			countdown:0,
			countdown_show:'',
			intervalC:'',
			rtcurl:'',
			clientwidth:480,
			videoHeight:230,
			scrollHeight:0,
			isshowauth:false,
			deviceType:'pc',
			videoInitW:1920,
			videoInitH:1080,
			devicestr:'',
			cameraNum:0,//摄像头数量
			cameraArr:[],
			cameraIndex:0,
    }
  },

  onLoad: function (opt) {
		var device = uni.getDeviceInfo();
		this.deviceType = device.deviceType;
		this.opt = app.getopts(opt);
		this.roomid = this.opt.id || 0;
		this.clientwidth = uni.getWindowInfo().windowWidth;
		if(this.clientwidth>500){
			this.clientwidth = 500
		}
		this.getdata();
  },
	onPullDownRefresh: function () {
		this.getdata();
	},
  methods: {
    getdata: function (loadmore) {
			var that = this;
			that.loading = true;
			app.post('ApiH5zb/getPush', {id:that.roomid}, function (res) {
				
				if(res.status==1){
					// var rtcurl = 'webrtc://200536.push.tlivecloud.com/live/diandawkt?txSecret=49a361cda813bdd093845f2c9a095da0&txTime=666403D1';
					that.detail = res.detail;
					that.pagecontent = res.pagecontent;
					that.rtcurl = res.detail.push_webrtc;
					that.roomid = res.detail.id;
					that.isgl = res.isgl;
					that.watch_num = res.detail.watch_num;
					that.playtime = res.playtime;
					that.videoInitH = res.quality.height;
					that.videoInitW = res.quality.width;
					that.videoHeight = that.clientwidth / that.videoInitW * that.videoInitH;
					if(res.detail.live_status==1 || res.detail.live_status==2){
						that.timer = res.detail.timer
						that.formattedCountdown(1)
						if(res.detail.live_status==1){
							that.startTimer()
						}
					}
					if(that.detail.live_status==-1){
						// 直播倒计时
						that.countdown = res.detail.countdown;
						that.formattedCountdown(0);
						that.startCountdown();
					}
					that.loaded();
					that.getchatlist();
					that.getprolist();
					if(that.detail.type==1 && that.detail.live_status==1){
						console.log('-----videoContext------')
						if(that.playtime>0){
							setTimeout(function(){
								that.videoContext = uni.createVideoContext('video');
							},3000)
							that.videoContext.play();
						}
					}
				}else{
					app.alert(res.msg);
				}
			})
    },
		getDevice:function(e){
			var that = this;
			// 获取设备列表，判断如果有两个摄像头，默认使用前置，deviceId通过传入 'user' 和 'environment' 来指定打开前置和后置摄像头
			deviceManager.getDevicesList().then(function(data) {
				console.log('device data');
				console.log(data);
				that.cameraNum = 0;
				that.devicestr = ﻿JSON.stringify(data);
			  data.forEach(function(device) {
			    console.log(device.type, device.deviceId, device.deviceName);
			﻿
			    if (device.type === 'video') {
						that.cameraNum ++;
						that.cameraArr.push(device)
			      // cameraDeviceId = device.deviceId;
			    }
			  });
			});
		},
		//翻转切换摄像头设备
		changeCamera:function(e){
			var that = this;
			if(that.cameraNum > 1){
				if(that.cameraIndex == 0){
					that.cameraIndex = 1;
				}else{
					that.cameraIndex = 0;
				}
				deviceManager.switchCamera(that.cameraArr[that.cameraIndex].deviceId);
			}
		},
		startPush:function(type){
			if(!type) type = 'start';
			var that = this;
			//当前时间戳
			TXLivePusher211Min.checkSupport().then(function(data) {
				// 是否支持WebRTC  
				if (data.isWebRTCSupported) {
					// 设置视频质量
					livePusher.setRenderView('local_video');
					livePusher.setVideoQuality('720p');
					// 设置音频质量
					livePusher.setAudioQuality('standard');
					// 设置视频的分辨率
					// livePusher.setProperty('setVideoResolution', { width:300, height:480 });
					console.log('videoInitW:'+that.videoInitW)
					console.log('videoInitH:'+that.videoInitH)
					if(that.deviceType=='pc'){
						livePusher.setProperty('setVideoResolution', {width:that.videoInitW, height:that.videoInitH });
					}else{
						livePusher.setProperty('setVideoResolution', {width:that.videoInitH, height:that.videoInitW });
					}
					
					// livePusher.setProperty('setVideoResolutionMode', 'Landscape');
					// 自定义设置帧率
					livePusher.setProperty('setVideoFPS', 25);
					var hasError = false
					Promise.all([
						livePusher.startCamera().catch(function (error) {
							hasError = true;
							console.log('camera error:'+error.toString())
						 app.alert('摄像头异常，请检查');return;
						}), 
						livePusher.startMicrophone().catch(function (error) {
							// hasError = true
						 app.alert('麦克风异常，请检查');return;
						})
						]).then(function() {
							that.getDevice();
							if(!hasError){
								livePusher.startPush(that.rtcurl);
								if(type!='restart'){
									that.changePushStatus(1);
									that.timer = 0;
								}
								that.islive = 1;
								that.detail.live_status=1;
								let now = new Date();
								that.formattedCountdown(1);
								that.startTimer();
								//1分钟更新一次直播时长
								setInterval(function(){
									console.log('updatezbtime='+that.timer)
									that.updatezbtime()
								},60000)
							}
						})
				} else {    
					app.alert('WebRTC Not Support');return;
				}
				// 是否支持H264编码  
				if (data.isH264EncodeSupported) {
					console.log('H264 Encode Support');  
				} else {    
					console.log('H264 Encode Not Support');
				}
			});
		},
		updatezbtime:function(){
			app.get('ApiH5zb/updateZbtime', {roomid:this.roomid}, function (res) {
				if(res.status==1){
					console.log(res.zbtime)
				}
			})
		},
		endPush:function(){
			livePusher.stopPush();
			livePusher.stopCamera();
			livePusher.stopMicrophone();
			this.islive = 0;
			this.playEnd();
		},
		showpro:function(){
			this.st = 2
			this.getprolist()
		},
		startCountdown() {
			if (this.intervalC) return; // 如果倒计时已经在进行中，则不重复启动
			this.intervalC = setInterval(() => {
				if (this.countdown > 0) {
					this.countdown--;
					this.formattedCountdown(0);
				} else {
					clearInterval(this.intervalC);
					this.intervalC = null;
					if(this.detail.type==1 && this.detail.live_status==-1){
						this.getdata();
						// this.timer = 1;
						// this.detail.live_status = 1;
						// this.formattedCountdown(1);
						// this.startTimer();
						// that.geteventlist();
					}
				}
			}, 1000);
		},
		resetCountdown() {
			clearInterval(this.intervalC);
			this.intervalC = null;
			this.countdown = 9000; // 重置倒计时初始值
		},
		formattedCountdown(type) {
			if(!type){
				var type = 0;//倒计时
			}
			if(type==1){
				const hours = String(Math.floor(this.timer / 3600)).padStart(2, '0');
				const minutes = String(Math.floor((this.timer % 3600) / 60)).padStart(2, '0');
				const seconds = String(this.timer % 60).padStart(2, '0');
				this.timer_show = `${hours}:${minutes}:${seconds}`;
			}else{
				const hours = String(Math.floor(this.countdown / 3600)).padStart(2, '0');
				const minutes = String(Math.floor((this.countdown % 3600) / 60)).padStart(2, '0');
				const seconds = String(this.countdown % 60).padStart(2, '0');
				this.countdown_show = `${hours}:${minutes}:${seconds}`;
			}
		},
		startTimer() {
			console.log(this.intervalB)
				if (this.intervalB) return; // 如果计时器已经在进行中，则不重复启动
				this.intervalB = setInterval(() => {
					this.timer++;
					this.formattedCountdown(1)
				}, 1000);
			},
			stopTimer() {
				clearInterval(this.intervalB);
				this.intervalB = null;
			},
			resetTimer() {
				clearInterval(this.intervalB);
				this.intervalB = null;
				this.timer = 0;
			},
		getmore: function () {
			console.log('get more')
			if(this.st=='2'){
				if (!this.pnodata && !this.pnomore) {
				  this.ppagenum = this.ppagenum + 1;
				  this.getprolist(true);
				}
			}else if(this.st=='0'){
				if (!this.nodata && !this.nomore) {
				  this.pagenum = this.pagenum + 1;
				  this.getchatlist(true);
				}
			}
		},
		getprolist: function (loadmore) {
			if(!loadmore){
				this.ppagenum = 1;
				this.prolist = [];
			}
		  var that = this;
		  var pagenum = that.ppagenum;
		  var st = that.st;
			that.pnodata = false;
			that.pnomore = false;
			that.loading = true;
		  app.post('ApiH5zb/roomProlist', {keyword:that.keyword,pagenum: pagenum,roomid:that.roomid,st:'all'}, function (res) {
				that.loading = false;
		    var data = res.datalist;
		    if (pagenum == 1) {
					that.prolist = data;
		      if (data.length == 0) {
		        that.pnodata = true;
		      }
					that.loaded();
		    }else{
		      if (data.length == 0) {
		        that.pnomore = true;
		      } else {
		        var datalist = that.prolist;
		        var newdata = datalist.concat(data);
		        that.prolist = newdata;
		      }
		    }
		  });
		},
		playEnd:function(){
			var that = this;
			app.post('ApiH5zb/endPush', {id:that.roomid}, function (res) {
				if(res.status==1){
					that.detail.live_status = 2;
					that.detail.show_video_time = res.show_video_time;
					// that.getdata()
				}
			})
		},
		changetab:function(st){
			this.st = st
			if(this.st==2){
				this.getprolist()
			}
			if(this.st==0){
				this.scrollHeight = 1000000;
			}else{
				this.scrollHeight = 0;
			}
		},
		
		memberInfo:function(e){
			var that = this;
			var mid = e.currentTarget.dataset.mid;
			if(!that.isgl){
				return;
			}
			app.get('ApiH5zb/memberInfo', {roomid:that.roomid,mid:mid}, function (res) {
				if(res.status==1){
					that.showmember = true;
					that.member = res.data
				}
			})
		},
		hideMemberInfo:function(){
			this.showmember = false;
			this.member = {}
		},
		changeBlacklist:function(e){
			var that = this;
			var mid = e.currentTarget.dataset.mid;
			var type = e.currentTarget.dataset.type;
			if(!that.isgl){
				return;
			}
			app.get('ApiH5zb/changeBlacklist', {roomid:that.roomid,mid:mid,type:type}, function (res) {
				if(res.status==1){
					app.success(res.msg);
					that.showmember = false;
					that.member = {}
				}else{
					app.alert(res.msg);
				}
			})
		},
		setRoomMids:function(e){
			var that = this;
			var mid = e.currentTarget.dataset.mid;
			if(!that.isgl){
				return;
			}
			app.get('ApiH5zb/setRoomMids', {roomid:that.roomid,mid:mid,type:type}, function (res) {
				if(res.status==1){
					app.success(res.msg);
					that.showmember = false;
					that.member = {}
				}else{
					app.alert(res.msg);
				}
			})
		},
		closePoster:function(){
			this.isposter = false;
		},
		changePushStatus:function(status){
			var that = this;
			app.post('ApiH5zb/changePushStauts', {id:that.roomid,status:status}, function (res) {
				if(res.status==1){
					that.detail.live_status = res.status;
					that.status = status
				}
			})
		},
		sharemp:function(){
			app.error('点击右上角发送给好友或分享到朋友圈');
		},
		setTopPro:function(e){
			var that = this;
			var index = e.currentTarget.dataset.index;
			that.toppro = that.prolist[index]
			app.post('ApiH5zb/roomProductTop', {id:that.toppro.id,roomid:that.roomid}, function (res) {
				if(res.status==1){
					var msgdata = {
						aid: app.globalData.aid,
						mid: app.globalData.mid,
						bid: that.bid,
						roomid:that.roomid,
						proid:that.toppro.id,
						msgtype: 'producttop',
						content: '',
						platform:app.globalData.platform,
						pre_url:app.globalData.pre_url
					}
					that.sendNotice(msgdata);
					that.isprotop = true
				}
			})
		},
		closeTop:function(){
			this.isprotop = false;
			this.toppro = {}
		},
		setst:function(index){
			var that = this;
			var datalist = that.prolist;
			var pro = datalist[index]
			var id = pro.id;
			var st = pro.status==1?0:1;
			// that.loading = true;
			app.post('ApiH5zb/setst', {id:id,st:st}, function (res) {
				if(res.status==1){
					var msgdata = {
					  aid: app.globalData.aid,
					  mid: app.globalData.mid,
					  bid: that.bid,
						roomid:that.roomid,
						proid:id,
					  msgtype: 'productst'+st,
					  content: '',
						platform:app.globalData.platform,
						pre_url:app.globalData.pre_url
					}
					that.sendNotice(msgdata);
					//重新排序
					datalist.splice(index,1);
					pro.status = st
					var curlist = [pro];
					
					if(st==1){
						var newdata = curlist.concat(datalist);
						
					}else{
						var newdata = datalist.concat(curlist);
					}
					that.prolist = newdata;
				}else{
					app.alert(res.msg)
				}
			})
		},
		timeupdate:function(e){
			//跳转到指定播放位置 initial-time 时间为秒
			let that = this;
			//播放的总时长
			var duration = e.detail.video_time;
			//实时播放进度 秒数
			var currentTime = e.detail.currentTime;
			if(currentTime>that.playtime){
				that.playtime = currentTime
			}
			//更新直播时长 1分钟更新一次
			
			
		},
		playVideo:function(e){
			this.videoContext.play();
			this.isplay = true;
			app.get('ApiH5zb/playVideo', {id:this.id,isend:0}, function (res) {
				console.log(res);
			})
		},
		pause:function(e){
			this.playOver()
		},
		getSharePoster:function(e){
			var that = this;
			that.loading = true;
			app.get('ApiH5zb/getVideoPoster', {id:that.roomid}, function (res) {
				if(res.status==1){
					that.posterurl = res.poster
					that.isposter = true;
					that.loading = false;
				}else{
					app.alert(res.msg)
				}
			})
		},
		closePoster:function(){
			this.isposter = false;
		},
		updateMessageTime: function () {
		  var that = this;
		  var datalist = this.datalist;
			for(var i in datalist){
		    var thistime = parseInt(datalist[i].createtime);
		    var prevtime = 0;
		    if (i > 0) {
		      prevtime = parseInt(datalist[i - 1].createtime);
		    }
		    if (thistime - prevtime > 600) {
		      datalist[i].formatTime = that.getTime(thistime);
		    } else {
		      datalist[i].formatTime = '';
		    }
		  }
		  this.datalist = datalist;
		},
		getchatlist: function () {
		  var that = this;
			that.loading = true;
			app.post('ApiH5zb/getmessagelist',{roomid:that.roomid,pagenum: that.pagenum}, function (res) {
				that.loading = false;
				var datalist = res.data;
				if (datalist.length > 0) {
					for (var i in datalist) {
						datalist[i].content = that.transformMsgHtml(datalist[i].msgtype, datalist[i].content);
					}
					that.datalist = datalist.concat(that.datalist);
					that.updateMessageTime();
					if (that.pagenum == 1) {
						that.scrollToBottom();
					} else {
		
					}
					that.pagenum = that.pagenum + 1;
				}else{
					that.nomore = true;
				}
			});
		},
		sendMessage: function (e) {
		  var that = this;
			if(that.detail.live_status!=1){
				return;
			}
		  var message = this.message;
		  if (message.length > 2000) {
		    uni.showToast({
		      title: "单条消息不能超过2000字",
		      icon: "none",
		      duration: 1000
		    });
		  } else {
		    if (message.replace(/^\s*|\s*$/g, "")) {
		      var msgdata = {
		        aid: app.globalData.aid,
		        mid: app.globalData.mid,
		        bid: that.bid,
						roomid:that.roomid,
		        msgtype: 'text',
		        content: message,
						platform:app.globalData.platform,
						pre_url:app.globalData.pre_url
		      };
					console.log(msgdata)
		      app.sendSocketMessage({type: 'h5zb',data:msgdata});
		      that.message = "";
		      that.trimMessage = "";
		      that.faceshow = false
		    }
		  }
		},
		sendMessageTopNum: function (e) {
		  var that = this;
			var msgdata = {
			  aid: app.globalData.aid,
			  mid: app.globalData.mid,
			  bid: that.bid,
				roomid:that.roomid,
			  msgtype: 'topnum',
			  content: '',
				platform:app.globalData.platform,
				pre_url:app.globalData.pre_url
			};
			app.sendSocketMessage({type: 'h5zb',data:msgdata});
		},
		sendNotice:function(msgdata){
			if(this.detail.live_status!=1){
				return;
			}
			app.sendSocketMessage({type: 'h5zb',data:msgdata});
		},
		sendimg: function () {
			if(this.detail.live_status!=1){
				return;
			}
		  var that = this;
			if(that.detail.pinglun_noimg==1){
				app.error('禁止发图');
				return;
			}
		  app.chooseImage(function (data) {
		    for (var i = 0; i < data.length; i++) {
		      var message = data[i];
		      var msgdata = {
		        aid: app.globalData.aid,
		        mid: app.globalData.mid,
		        bid: that.bid,
						roomid:that.roomid,
		        msgtype: 'image',
		        content: message,
						platform:app.globalData.platform,
						pre_url:app.globalData.pre_url
		      };
		      app.sendSocketMessage({type: 'h5zb',data: msgdata});
		    }
		  }, 3);
		},
		receiveMessage: function (data) {
			var that = this;
			console.log('wk-zz-**')
			var message = data.data
			console.log(message)
			if(this.detail.live_status!=1){
				return;
			}
			if(data.type == 'h5zb' && that.roomid == data.data.roomid) {
				if(data.data.msgtype=='topnum'){
					
				}else if(data.data.msgtype=='productst0'){
				
				}else if(data.data.msgtype=='productst1'){
					
				}else if(data.data.msgtype=='producttop'){
					
				}else if(data.data.msgtype=='watch'){
					// that.watch_num++;
				}else if(data.data.msgtype=='join'){
					that.watch_num++
				}else{
					var message = data.data
					message.content = that.transformMsgHtml(message.msgtype, message.content);
					that.datalist = that.datalist.concat([message]);
					//that.sss = Math.random();
					setTimeout(that.updateMessageTime, 100);
					this.scrollToBottom();
					return true;
					if (that.bid != message.bid) { //其他商家发来的信息
						var content = message.content;
						if (message.msgtype == 'image') {
							content = '[图片]';
						} else if (message.msgtype == 'voice') {
							content = '[语音]';
						} else if (message.msgtype == 'video') {
							content = '[小视频]';
						} else if (message.msgtype == 'music') {
							content = '[音乐]';
						} else if (message.msgtype == 'news') {
							content = '[图文]';
						} else if (message.msgtype == 'link') {
							content = '[链接]';
						} else if (message.msgtype == 'miniprogrampage') {
							content = '[小程序]';
						} else if (message.msgtype == 'location') {
							content = '[地理位置]';
						}
						message.content = content;
						that.msgtipsShow = 1;
						that.msgtips = message;
						setTimeout(function () {
							that.msgtipsShow = 2;
						}, 10000);
						that.scrollToBottom();
					}
				}
			}
			return false;
		},
		toggleFaceBox: function () {
		  this.faceshow = !this.faceshow
		},
		scrollToBottom: function () {
		  var that = this;
			this.$nextTick(function() {
				this.scrollTop = 100000+Math.random(1000);
			});
		},
		onInputFocus: function (e) {
		  this.faceshow = false
		},
		onPageScroll: function (e) {
			var that = this;
			var scrollY = e.scrollTop;     
			if (that.st=='0' && scrollY == 0 && !that.nomore) {
				this.getchatlist();
			}
		},
		messageChange: function (e) {
		  this.message = e.detail.value;
		  this.trimMessage = e.detail.value.trim();
		},
		transformMsgHtml: function (msgtype, content) {
		  if (msgtype == 'miniprogrampage') {
		    var contentdata = JSON.parse(content);
		    content = '<div style="font-size:16px;font-weight:bold;height:25px;line-height:25px">' + contentdata.Title + '</div><img src="' + contentdata.ThumbUrl + '" style="width:400rpx"/>';
		  }
		  if (msgtype == 'image') {
		    content = '<img src="' + content + '" style="width:400rpx"/>';
		  }
		  return content;
		},
		selectface: function (face) {
		  this.message = "" + this.message + face;
			this.trimMessage = this.message.trim();
		},
		getTime: function (createtime) {
		  var t = this.nowtime - createtime;
		  if (t > 0) {
		    var todaystart = new Date(this.dateFormat(this.nowtime, "Y-m-d 00:00:00")).getTime();
		    todaystart = todaystart / 1000;
		    var lastdaystart = todaystart - 86400;
		    if (t <= 180) {
		      return '刚刚';
		    }
		    if (createtime > todaystart) {
		      return this.dateFormat(createtime, "H:i");
		    }
		    if (createtime > lastdaystart) {
		      return '昨天' + this.dateFormat(createtime, "H:i");
		    }
		    return this.dateFormat(createtime, 'Y年m月d日 H:i:s');
		  }
		},
		autoEvent:function(){
			
		}
  }
};
</script>
<style>
@import "../common.css";	
.fastopt{position: absolute;z-index: 990000;bottom: 160rpx;right: 0;width: 100rpx;height: 100rpx;border: 1px solid #f6f6f6;display: flex;border-radius: 50%;align-items: center;justify-content: center;}
.fastopt2{display: flex;flex-direction: column;align-items: center;justify-content: center;position: absolute;bottom: 160rpx;right: 0;
width: 100rpx;border-radius: 12rpx 0 0 12rpx;z-index: 90000;
}
.fastopt2 .fopt{height: 100rpx;display: flex;align-items: center;justify-content: center;border-bottom: 1px solid #f6f6f6;}
.fastopt2 .fopt:last-child{border-bottom: 0;}
.toppro .t2 .btn{background: #cbcbcb;}
.videoBox{height: 230px;overflow: hidden;position: absolute;top: 0;width: 100%;}
.videoBox image{height: 100%;width: 100%;position: absolute;top: 0;}
.videoBox video{width: 100%;max-height: 100%; object-fit: contain;}
.countdown1{position: absolute;bottom:0;height: 30px;width: 100%;z-index: 70000;background: #00000047;color: #fff;padding: 10rpx 20rpx;text-align: center;}

/* v3 pc端以移动端方式展示 */
.fixbox{position: relative;height: 230px;z-index: 99;}
.main-mobile{position: relative;top: 0;left: 0;width: 100%;height: 100%;}
.container{max-width: 480px;margin: 0 auto;border: 1px solid #f6f6f6;height: 100vh;}
.border{border: 1px solid #e9e9e9;}
.content{position: absolute;left: 0;width: 100%;top: 230px;}
.watch_num{position: absolute;top: 2px; right: 2px;z-index: 9999;}
.dd-tab2-content{height: 60px;}
.video_main{height: 230px;position: relative;top: 0;width: 100%;}
.zb-dd-tab{margin-top:0;height: 50px;}
.chat-bottom{position: absolute;bottom: 0;left: 0;}
.input-box{position: absolute;bottom: 0;left: 0;}

</style>