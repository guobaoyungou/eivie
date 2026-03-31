<template>
<view class="container">
	<block v-if="isload">
	<!-- #ifdef H5 || MP-WEIXIN-->
	<block v-if="isVerify">
	<view class="fixbox">
		<view class="watch_num"><image class="eye" :src="pre_url+'/static/img/eye2.png'"></image>{{watch_num}}</view>
		<view class="video_main">
			<view class="videoBox" v-if="detail.live_status==1">
        <div v-if="detail.type==0" id="videoContain" :style="{height:(videoHeight)+'px'}"><div></div></div>
				<!--<video v-if="detail.type==0" id="player-video2" @play="liveplay" class="video" @loadedmetadata="videoInfo" :controls="false" object-fit="cover" :style="{height:videoHeight+'px'}"></video>-->

        <video v-if="detail.type==1" id="video" :style="{height:videoHeight+'px'}" class="video" @loadedmetadata="videoInfo" :src="detail.video_url" :initial-time="playtime" @timeupdate="timeupdate"  @ended="playEnd" object-fit="fill" :controls="false" :autoplay="true"></video>
			</view>
			<view class="video-poster" v-if="detail.live_status!=1">
				<image :src="detail.pic"></image>
			</view>
			<view class="bar"  :style="{top:(videoHeight-30)+'px'}">
				<view class="countdownc" v-if="detail.live_status==1" style="top: -58rpx;">直播时长：{{timer_show}}</view>
				<view class="countdownc" v-else-if="detail.live_status==-1">直播倒计时：{{countdown_show}}</view>
				<view class="countdownc" v-else-if="detail.live_status==2">直播已结束</view>
				<view class="countdownc" v-else>直播未开始</view>
				<dd-tab class="zbc-dd-tab"  :itemdata="['直播互动','直播介绍','边看边买','直播福利']" :itemst="['0','1','2','3']" :st="st" @changetab="changetab"></dd-tab>
			</view>
		</view>
	</view>
	<scroll-view class="content" scroll-y @scrolltolower="getmore" :style="{top:(videoHeight+55)+'px',height:'calc(100vh - '+(videoHeight*1 + 55*1)+'px)'}" :scroll-top="scrollHeight">
		<view v-if="isprotop">
			<view class="toppro" :style="'background:rgba('+t('color2rgb')+',0.2);'">
				<image class="proimg" :src="toppro.pic" @tap="goto" :data-url="'/pages/shop/product?id='+toppro.proid"></image>
				<view class="proinfo"  @click.stop="buydialogChange" :data-proid="toppro.proid">
					<view class="title">
						<text>{{toppro.name}}</text>
						<image :src="pre_url+'/static/img/close.png'" class="close" @tap.stop="closeTop"></image>
					</view>
					<view class="t1">库存：{{toppro.stock}}</view>
					<view class="t2">
						<view :style="{color:t('color1')}">￥<text class="price">{{toppro.sell_price}}</text></view>
						<view class="btn" :style="'background:rgba('+t('color1rgb')+',0.2);color:'+t('color1')">立即购买</view>
					</view>
				</view>
			</view>
		</view>
		<view v-if="st=='1'" class="eventbox">
		<dp :pagecontent="pagecontent"></dp>
		</view>
		<!-- 互动start -->
		<view  class="eventbox filter-scroll-view-box" v-if="st=='0'" scroll-y="true" :scroll-top="scrollTop">
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
				<!-- <nomore v-if="nomore"></nomore> -->
			</view>
		</view>
		<view v-if="st==0" style="height: 60px;"></view>
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
		<!-- 互动联通End -->
		<!-- 产品start -->
		<view v-if="st=='2'"  class="probox">
			<view class="prolist" v-for="(item,index) in prolist" :key="index">
				<image class="proimg" :src="item.pic" @tap="goto" :data-url="'/pages/shop/product?id='+item.proid"></image>
				<view class="proinfo" @click.stop="buydialogChange" :data-proid="item.proid" :btntype="btntype">
					<view class="title">{{item.name}}</view>
					<view class="t1">库存：{{item.stock}}</view>
					<view class="t2">
						<view :style="{color:t('color1')}">￥<text class="price">{{item.sell_price}}</text></view>
						<view class="buyopt" :style="'background:rgba('+t('color1rgb')+',0.2);color:'+t('color1')">立即购买</view>
					</view>
				</view>
			</view>
			<!-- <view class="eventbox" v-if="prolist.length==0"><nodata></nodata></view> -->
		</view>
		<!-- 产品end -->
		<!-- 直播福利start -->
		<view v-if="st=='3'"  class="eventbox">
			
			<!-- <view class="eventlist" v-for="(item,index) in eventlist" :key="index"> -->
			<view class="eventlist" v-if="eventlist.length>0" v-for="(item,index) in eventlist" :key="index">
				<view class="eventL" :style="{borderColor:item.st==0?t('color1'):'#bbb'}"><view class="dot" :style="{borderColor:item.st==0?t('color1'):'#bbb'}"></view></view>
				<view class="eventR">
					<view class="btf">
						<view class="event">
							<view class="time" :style="{color:item.st==0?t('color1'):'#999'}">第{{item.minutes}}分钟</view>
							<view :style="{color:item.st==0?'#222':'#999'}">{{item.name}}</view>
						</view>
						<view class="evenst">
							<text :class="'st'+item.st" v-if="item.st==2"  :style="{background:t('color1'),color:'#fff'}" @tap="getRoomEvent" :data-index="index">领取</text>
							<text :class="'st'+item.st" v-if="item.st==3"  :style="{background:t('color1'),color:'#fff'}" @tap="getRoomEvent" :data-index="index">重新答题</text>
							<text :class="'st'+item.st" v-if="item.st==1" >已领取</text>
							<text :class="'st'+item.st" v-if="item.st==-1" >已失效</text>
							<image v-if="item.st==0" :src="pre_url+'/static/img/libao_liwu.png'" class="eventimg"></image>
						</view>
					</view>
					<view class="btg"></view>
				</view>
			</view>
			<nodata v-if="eventlist.length==0"></nodata>
		</view>
		<!-- 直播福利end -->
	</scroll-view>
	<buydialog v-if="buydialogShow" :proid="proid" @buydialogChange="buydialogChange" :btntype="btntype" :param="buyparam"></buydialog>
	<view v-if="showmember" class="alert_bg" :style="{paddingTop:(videoHeight+60)+'px'}">
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
			</view>
			
		</view>
	</view>
	<!-- 优惠券S -->
	<view v-if="showEvent" class="alert_bg" :style="{paddingTop:(videoHeight+60)+'px'}">
		<view class="alert_content" v-if="eventtype==3">
			<view class="alert_title">
				<view class="alert_txt"></view>
				<image class="alert_close" :src="pre_url+'/static/img/close.png'" @tap="hideEvent"></image>
			</view>
			<view class="alert_main">
				<view class="coupon-alert">
					<view class="coupon-alert-row flex-y-center" :style="{borderColor:t('color1')}">
						<view class="coupon-alert-L" :style="{color:t('color1')}">
							<view>
								<view class="coupon-alert-LT">
									<view v-if="eventinfo.coupon_type==1">
										<text>￥</text>
										<text class="coupon-alert-money">{{eventinfo.coupon_money}}</text>
									</view>
									<view v-if="eventinfo.coupon_type==10"><text class="t1">{{eventinfo.copupon_discount/10}}</text><text class="t2">折</text></view>
									<view v-if="eventinfo.coupon_type==3"><text class="t1">{{eventinfo.copupon_limit_count}}</text><text class="t2">次</text></view>
									<view v-if="eventinfo.coupon_type==5">
										<text>￥</text>
										<text class="coupon-alert-money">{{eventinfo.coupon_money}}</text>
									</view>
								</view>
								<view class="coupon-alert-LB">
									<view class="font-tips">
										<text v-if="eventinfo.coupon_minprice>0">满{{eventinfo.coupon_minprice}}元可用</text>
										<text v-else>无门槛</text>
									</view>
								</view>
							</view>
						</view>
						<view class="coupon-alert-R">
							<view class="coupon-alert-name">{{eventinfo.coupon_name}}</view>
							<view><text class="coupon-alert-tag" :style="'background:rgba('+t('color1rgb')+',0.15);color:'+t('color1')">{{eventinfo.coupon_typetxt}}</text></view>
						</view>
					</view>
					<view class="bottom-alert-btn flex-x-center" :style="{background:t('color1')}" @tap="getCoupon" :data-id="eventinfo.id">立即领取</view>
				</view>
			</view>
		</view>
		<!-- 优惠券alertEnd -->
		<!-- 红包alertStart -->
		<view class="redbag-alert" v-if="eventtype==2">
			<image :src="pre_url+'/static/img/coupon-top.png'" class="redbag-topbg" mode="widthFix"></image>
			<view class="redbag-content">
				<view class="redbag-money" :style="{color:(eventinfo.redstep==2?'#efbc09':'')}">
					<view>{{eventinfo.redstep==2?'拼手气红包':'获得红包'}}</view>
					<text>￥</text>
					<text class="money">{{redbag_money}}</text>
				</view>
				<view class="redbag-btn1 flex-x-center" @tap="getRedbag" :data-id="eventinfo.id"  data-isopen="1" v-if="eventinfo.redstep==2">打 开</view>
				<view class="redbag-btn flex-x-center" @tap="getRedbag" :data-id="eventinfo.id" data-isopen="0" v-else-if="eventinfo.redstep==1">确 定</view>
				<view class="redbag-btn flex-x-center" @tap="hideEvent" v-else>关 闭</view>
			</view>
			<view class="redbg-close" @tap="hideEvent">
				<image :src="pre_url+'/static/img/close2.png'"></image>
			</view>
		</view>
		<!-- 红包alertEnd -->
		<view class="alert_content"  v-if="eventtype==0 || eventtype==1">
			<view class="alert_title">
				<view class="alert_txt"></view>
				<image class="alert_close" :src="pre_url+'/static/img/close.png'" @tap="hideEvent"></image>
			</view>
			<view class="question-alert">
				<view class="questionlist">
					<view class="question-item">
						<view class="question-title">{{questionitem.name}}</view>
						<view class="question-info" v-for="(option,key) in questionitem.options" :key="key" @tap="questionOptionChoose" :data-index="key" :style="questionoptionkey==key?('background:rgba('+t('color1rgb')+',0.15);color:'+t('color1')):''">
							<text class="question-sort">{{option.biaoshi}}、</text>
							<view class="question-choose">{{option.option}}</view>
						</view>
					</view>
				</view>
				<block v-if="!isEventDeal">
					<view class="bottom-alert-btn flex-x-center" :style="{background:t('color1')}" v-if="question_num==questionlist.length" @tap="questionDone" :data-id="eventinfo.id">提交</view>
					<view class="bottom-alert-btn flex-x-center" :style="{background:t('color1')}" v-if="question_num<questionlist.length" @tap="nextquestion">下一题</view>
				</block>
			</view>
		</view>
	<!-- 优惠券E -->
	<!-- 积分 S -->
	<view class="alert_content" v-if="eventtype==4">
		<view class="alert_title">
			<view class="alert_txt"></view>
			<image class="alert_close" :src="pre_url+'/static/img/close.png'" @tap="hideEvent"></image>
		</view>
		<view class="score_alert">
			<view class="score_main" :style="{borderColor:t('color1'),color:t('color1')}">
				<text class="scoretxt">{{eventinfo.give_score}}</text>
				<text>可获得{{t('积分')}}</text>
			</view>
			<view class="bottom-alert-btn flex-x-center" :style="{background:t('color1')}" @tap="getScore" :data-id="eventinfo.id">立即领取</view>
		</view>
	</view>
		<!-- 积分 E -->
	</view>
	<!-- alert_bg end -->
	</block>
	<view v-if="showVerify" class="alert_bg">
		<view class="alert_content">
			<view class="verify-row">请输入观看验证码</view>
			<view class="verify-row"><input v-model="verifyCode" name="verify_name" /></view>
			<view class="bottom-alert-btn flex-x-center" :style="{background:t('color1')}" @tap="submitVerify">确 定</view>
		</view>
	</view>
	<!-- 快链 -->
	<view class="option" :style="'background:rgba('+t('color1rgb')+',0.15);'" v-if="iconlist.length>0">
		<view class="opt" v-for="(item,index) in iconlist" :key="index" @tap="goto" :data-url="item.link">
			<image class="m" :src="item.icon" v-if="item.icon">
			<text class="t" v-if="item.name">{{item.name}}</text>
		</view>
	</view>
	<!-- 快链 -->
	<!-- #endif  -->
	</block>
	<loading v-if="loading"></loading>
</view>
</template>
<script>
import TCPlayer from './tcplayer.v5.1.0.min.js';
import './tcplayer.min.css';
var app = getApp();

var player;
export default {
  data() {
    return {
			opt:{},
			loading:false,
      isload: false,
			menuindex:-1,
			pre_url: app.globalData.pre_url,
			platform: app.globalData.platform,
			set:{},
			pagecontent:{},
			detail:{},
			datalist:[],
			pagenum: 1,
			nomore: false,
			nodata:false,
			roomid:0,
			prolist:[],
			ppagenum: 1,
			pnomore: false,
			pnodata:false,
			isprotop:false,
			toppro:{},
			//
			videoContext:'',
			isplay:false,
			st:0,
			isposter:false,
			posterurl:'',
			videoControls:false,//当已经存在完播时，支持拖动播放
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
			playtime:0,
			//购买
			proid:0,
			buydialogShow:false,
			btntype:2,
			buyparam:{roomid:0},
			isgl:false,
			showmember:false,
			member:{},
			interval1:null,
			eventtime:0,
			eventinfo:{},
			eventtype:-1,
			showEvent:false,
			isEventDeal:false,
			redbag_money:'',
			eventGeting:false,
			question_num:0,
			questionlist:[],
			questionitem:{},
			questionoptionkey:-1,
			showVerify:false,
			isVerify:false,
			verifyCode:'',
			currentTime:0,//录播播放时长
			currentTime2:0,//直播播放时长
			iconlist:[],
			eventlist:[],
			enodata:false,
			watch_num:0,
			countdown:0,
			countdown_show:'',
			//直播时长
			intervalB:'',
			timer:0,
			timer_show:'',
			intervalC:'',
			videoHeight:200,
			clientwidth:400,
			showmember:false,
			member:{},
			isgl:false,
			scrollHeight:0,
			isshowauth:false
    }
  },

  onLoad: function (opt) {
		var that = this;
		this.opt = app.getopts(opt);
		this.id = this.opt.id || 0;
		this.roomid = this.opt.id || 0;
		this.buyparam.roomid = this.roomid;
		this.clientwidth = uni.getWindowInfo().windowWidth;
		if(this.clientwidth>500){
			this.clientwidth = 500
		}
  },
	onPullDownRefresh: function () {
		this.checkVerify()
	},
	onShow() {
		var that = this;
		console.log('-----detail3-------');
		console.log(player)
		if(!player) return;
		player.play();
	},
	onReady() {
		this.checkVerify();
	},
	onUnload:function(){
		//回退上一个页面执行（后退）
		// if(this.detail.id){
		// 	this.playOver();
		// }
		// clearInterval(this.interval1);
	},
	onHide:function(){
		//跳转其他页面执行（前进）
		// this.playOver();
	},
  methods: {
		checkVerify:function(){
			var that = this;
			that.loading = true;
			app.get('ApiH5zb/checkVerify', {id:that.roomid}, function (res) {
				that.loading = false;
				if(res.status==-77){
					//拉黑无法观看
					app.alert(res.msg);
					setTimeout(function(){
						app.goback();
					},1000)
					return;
				}
				if(res.status==1){
					that.isVerify = true;
					that.getdata();
				}else if(res.status==2){
					that.showVerify = true;
					that.loaded();
				}else{
					app.alert(res.msg);
				}
			})
		},
		submitVerify:function(){
			var that = this;
			that.loading = true;
			app.post('ApiH5zb/checkVerify', {id:that.roomid,code:that.verifyCode}, function (res) {
				if(res.status==1){
					that.isVerify = true;
					that.showVerify = false;
					that.loading = false;
					that.getdata();
				}else if(res.status==2){
					app.error(res.msg);
					that.showVerify = true;
				}else{
					app.alert(res.msg);
				}
			})
		},
    getdata: function () {
			var that = this;
			that.loading = true;
			app.post('ApiH5zb/getPlayer', {id:that.roomid}, function (res) {
				that.loading = false;
				if(res.status==1){
					that.detail = res.detail;
					that.watch_num = res.detail.watch_num;
					that.set = res.set;
					that.isgl = res.isgl;
					that.playtime = res.playtime;
					that.pagecontent = res.pagecontent;
					that.nowtime = res.nowtime;
					that.iconlist = res.iconlist;
					if(res.isprotop){
						that.isprotop = true;
						that.toppro =  res.toppro
					}
					if(that.detail.live_status==-1){
						// 直播倒计时
						that.countdown = res.detail.countdown;
						that.formattedCountdown(0);
						that.startCountdown();
					}else if(that.detail.live_status==1){
						that.timer = res.detail.timer
						that.formattedCountdown(1)
						that.startTimer()
					}
					that.loaded();
					that.getchatlist();
					that.getprolist();
					that.geteventlist();
					var msgdata = {
						aid: app.globalData.aid,
						mid: app.globalData.mid,
						bid: that.bid,
						roomid:that.roomid,
						msgtype: 'join',
						content: '加入直播间',
						platform:app.globalData.platform,
						pre_url:app.globalData.pre_url
					}
					if(that.detail.type==0){
						console.log('live')
						if(that.detail.live_status==1 || that.detail.live_status==0){
							var vidoDom = null;
              var video = null;
							setTimeout(function(){
                const video = document.createElement("video")
                video.setAttribute("id", "player-video")
                video.setAttribute('playsinline',true)
                video.setAttribute('webkit-playsinline',true)
                video.setAttribute('autoplay',true);
                video.style.width = '100vw';
                //video.style.height = '100px';
                document.getElementById("videoContain").appendChild(video)
							},500);
							setTimeout(function(){
								player = TCPlayer('player-video', {
										sources: [{
											src: that.detail.pull_webrtc, // 播放地址
										}],
										licenseUrl: res.set.license_url, // license 地址，参考准备工作部分，在视立方控制台申请 license 后可获得 licenseUrl
										controls:true,
                    controlBar:{playToggle:false,progressControl:false,volumePanel:false,fullscreenToggle:false,currentTimeDisplay:false,durationDisplay:false,QualitySwitcherMenuButton:false,playbackRateMenuButton:false,timeDivider:false},
										autoplay:true//,
										//poster:that.detail.pic
								});
								player.width(that.clientwidth);
								player.height(that.videoHeight);
                player.play();
								player.on('playing',function(playing){
									console.log('--playing---')
									console.log(player.videoWidth(),'videoWidth-i')
									console.log(player.videoHeight(),'videoHeight-i')
									if(player.videoHeight()>0 && player.videoHeight()!=that.videoHeight){
										that.videoHeight = that.clientwidth / player.videoWidth() * player.videoHeight()
										player.width(that.clientwidth);
										player.height(that.videoHeight);
									}
									that.detail.live_status = 1;
								})
								player.on('play',function(play){
									console.log('--play---')
									console.log(that.clientwidth);
									console.log(player.videoWidth(),'videoWidth')
									console.log(player.videoHeight(),'videoHeight')
									if(player.videoHeight()>0){
										that.videoHeight = that.clientwidth / player.videoWidth() * player.videoHeight()
										player.width(that.clientwidth);
									}
									player.height(that.videoHeight);
									that.detail.live_status = 1;
								})
								player.on('error',function(error){
									console.log('--error---')
									console.log(error)
									that.detail.live_status = 2;
									that.detail.videoHeight = 200;
								})
								player.on('ended',function(ended){
									console.log('--ended---')
									that.detail.live_status = 2;
									that.detail.videoHeight = 200;
								})
								player.on('waiting',function(waiting){
									console.log('--waiting---')
									console.log(waiting)
								})
							},1000)
							that.geteventlist();
						}
					}else{
						console.log('-----videoContext------')
						if(that.playtime>0){
							that.geteventlist();
							that.videoContext = uni.createVideoContext('video');
							setTimeout(function(){
								that.videoContext.play();
							},1000)
						}
					}
					that.sendNotice(msgdata);
				}else{
					app.alert(res.msg);
				}
			})
    },
    liveplay:function(e){
      console.log('----liveplay--')
      console.log(e);
      player.play();
    },
		videoInfo:function(e){
			var that = this;
			var info = e.detail;
			that.videoHeight = that.clientwidth / info.width * info.height;
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
		geteventlist:function(e){
			var that = this;
			clearInterval(this.interval1);
			that.eventGeting = false;
			app.get('ApiH5zb/getEventList', {roomid:that.roomid,isend:0}, function (res) {
				that.eventtime = res.nowtime;
				that.eventlist = res.eventlist;
				clearInterval(that.interval1);
				if(that.eventlist.length>0){
					if(that.detail.live_status==1){
						that.interval1 = setInterval(function(){
							that.checkRoomEvent(true);
							that.eventtime = that.eventtime+3;
						},3000)
					}
				}else{
					that.enodata = true;
				}
			})
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
		checkRoomEvent:function(){
			var that = this;
			var eventlist = that.eventlist;
			console.log('eventtime:'+that.eventtime);
			for(var i in eventlist){
				if(eventlist[i].trigger_time<that.eventtime && eventlist[i].is_trigger==1){
					console.log('show-event')
					that.showEvent = true;
					that.eventinfo = eventlist[i];
					that.eventtype = that.eventinfo.type;
					//可领取
					that.eventlist[i].st = 2;
					that.eventlist[i].is_trigger = 0;
					if(that.eventtype==2){
						that.redbag_money = that.eventinfo.redbag_money
					}else if(that.eventtype==0 || that.eventtype==1){
						that.questionlist = that.eventinfo.questionlist;
						if(that.questionlist.length>0){
							that.question_num = 1;
							that.questionitem = that.questionlist[0]
						}
					}
					// eventlist.splice(i,1);
					break;
				}
			}
			// console.log(that.eventlist)
		},
		getRoomEvent:function(e){
			var that = this;
			that.showEvent = true;
			var index = e.currentTarget.dataset.index;
			that.eventinfo = that.eventlist[index];
			that.eventtype = that.eventinfo.type;
			if(that.eventtype==2){
				that.redbag_money = that.eventinfo.redbag_money
			}else if(that.eventtype==0 || that.eventtype==1){
				that.questionlist = that.eventinfo.questionlist;
				if(that.questionlist.length>0){
					that.question_num = 1;
					that.questionitem = that.questionlist[0]
				}
			}
		},
		getCoupon:function(e){
			var that = this;
			var eventid = e.currentTarget.dataset.id;
			if(that.eventGeting) return;
			that.eventGeting = true;
			app.showLoading('请稍后...');
			app.post('ApiH5zb/roomEventDeal', {roomid:that.roomid,eventid:eventid}, function (res) {
				app.showLoading(false);
				if(res.status==1){
					app.error(res.msg);
					that.showEvent = false;
					that.eventinfo = [];
					that.eventtype = -1;
					that.geteventlist();
				}else{
					app.error(res.msg)
					that.eventGeting = false;
				}
			})
		},
		getScore:function(e){
			var that = this;
			var eventid = e.currentTarget.dataset.id;
			if(that.eventGeting) return;
			that.eventGeting = true;
			app.showLoading('请稍后...');
			app.post('ApiH5zb/roomEventDeal', {roomid:that.roomid,eventid:eventid}, function (res) {
				app.showLoading(false);
				if(res.status==1){
					app.error(res.msg);
					that.showEvent = false;
					that.eventinfo = [];
					that.eventtype = -1;
					that.geteventlist();
				}else{
					app.error(res.msg)
					that.eventGeting = false;
				}
			})
		},
		getRedbag:function(e){
			var that = this;
			var eventid = e.currentTarget.dataset.id;
			var isopen = e.currentTarget.dataset.isopen;
			if(that.eventGeting) return;
			that.eventGeting = true;
			app.showLoading('请稍后...');
			app.post('ApiH5zb/roomEventDeal', {roomid:that.roomid,eventid:eventid,isopen:isopen}, function (res) {
				app.showLoading(false);
				if(res.status==1){
					that.eventGeting = false;
					if(isopen==1){
						that.eventinfo.redstep = 3;
						that.redbag_money = res.redbag_money;
						console.log(that.eventinfo)
					}else{
						app.error(res.msg);
						that.showEvent = false;
						that.eventinfo = [];
						that.eventtype = -1;
					}
					that.geteventlist();
				}else if(res.status==2){
					that.redbag_money = res.redbag_money;
					that.isEventDeal = true;
					that.geteventlist();
				}else{
					that.eventGeting = false;
					app.error(res.msg)
				}
			})
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
		  app.post('ApiH5zb/roomProlist', {keyword:that.keyword,pagenum: pagenum,roomid:that.roomid}, function (res) {
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
		changetab:function(e){
			this.st = e
			if(this.st==2){
				this.getprolist()
			}else if(this.st==3){
				this.geteventlist()
			}
			
			if(this.st==0){
				this.scrollHeight = 1000000;
			}else{
				this.scrollHeight = 0;
			}
			this.buydialogShow = false;
		},
		showpro:function(){
			this.st = 2
			this.getprolist()
		},
		closeTop:function(){
			this.isprotop = false;
			this.toppro = {}
		},
		buydialogChange: function (e) {
			if(!this.buydialogShow){
				this.proid = e.currentTarget.dataset.proid;
			}
			this.buydialogShow = !this.buydialogShow;
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
		hideMemberInfo:function(){
			this.showmember = false;
			this.member = {}
		},
		hideEvent:function(){
			this.showEvent = false;
			this.eventtype = -1;
			this.isEventDeal = false;
			this.eventinfo = {};
		},
		nextquestion:function(){
			var that = this;
			var questionlist = that.questionlist;
			if(that.question_num<questionlist.length){
				if(!that.questionlist[that.question_num-1]['selected'] || that.questionlist[that.question_num-1]['selected']=='' || that.questionlist[that.question_num-1]['selected']==undefined){
					app.error('请选择答案');return;
				}
				var question_key = that.question_num;
				that.question_num++;
				that.questionoptionkey = -1;
				that.questionitem = questionlist[question_key]
			}
		},
		questionOptionChoose:function(e){
			var that = this;
			var index = e.currentTarget.dataset.index;
			that.questionoptionkey = index;
			that.questionlist[that.question_num-1]['selected'] = that.questionitem.options[index].biaoshi;
		},
		questionDone:function(e){
			var that = this;
			var eventid = e.currentTarget.dataset.id;
			if(!that.questionlist[that.question_num-1]['selected'] || that.questionlist[that.question_num-1]['selected']=='' || that.questionlist[that.question_num-1]['selected']==undefined){
				app.error('请选择答案');return;
			}
			if(that.eventGeting) return;
			that.eventGeting = true;
			app.showLoading('请稍后...');
			app.post('ApiH5zb/roomEventDeal', {roomid:that.roomid,eventid:eventid,qustionlist:that.questionlist}, function (res) {
				app.showLoading(false);
				that.loading = false;
				if(res.status==9){
					that.geteventlist();
					app.confirm(res.msg,function(){
						var eventlist = that.eventlist;
						for(var i in eventlist){
							if(eventlist[i].id==eventid){
								that.showEvent = true;
								that.eventinfo = eventlist[i];
								that.eventtype = that.eventinfo.type;
								that.questionlist = that.eventinfo.questionlist;
								if(that.questionlist.length>0){
									that.question_num = 1;
									that.questionoptionkey = -1;
									that.questionitem = that.questionlist[0]
								}
								break;
							}
						}
					},function(){
						that.hideEvent()
					})
				}else if(res.status>0){
					that.question_num = 0;
					that.questionlist=[];
					that.questionitem= {},
					that.questionoptionkey = -1;
					if(res.redbag_money>0){
						that.isEventDeal = false;
						that.redbag_money = res.redbag_money;
						that.eventinfo = {redbag_type:2};
						that.eventtype = 2;
						that.geteventlist();
					}else{
						app.error(res.msg);
						that.isEventDeal = true;
						that.geteventlist()
						that.hideEvent()
					}
				}else{
					app.error(res.msg)
					that.eventGeting = false;
				}
			})
		},
		timeupdate:function(e){
			//跳转到指定播放位置 initial-time 时间为秒
			let that = this;
			//播放的总时长
			var duration = e.detail.duration;
			//实时播放进度 秒数
			var currentTime = e.detail.currentTime;
			if(currentTime>that.playtime){
				that.playtime = currentTime
			}
			//当前视频进度
			that.currentTime  = currentTime; //实时播放进度
		},
		playVideo:function(e){
			var that = this;
			this.videoContext.play();
			this.isplay = true;
			app.get('ApiH5zb/playVideo', {roomid:that.roomid,isend:0,roomid:that.roomid}, function (res) {
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
			})
		},
		sendMessage: function (e) {
		  var that = this;
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
		      }
		      app.sendSocketMessage({type: 'h5zb',data:msgdata});
		      that.message = "";
		      that.trimMessage = "";
		      that.faceshow = false
		    }
		  }
		},
		sendNotice:function(msgdata){
			app.sendSocketMessage({type: 'h5zb',data:msgdata});
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
		sendimg: function () {
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
			if(data.type == 'h5zb' && that.roomid == data.data.roomid) {
				if(data.data.msgtype=='topnum'){
					
				}else if(data.data.msgtype=='productst0'){
					var datalist = that.prolist;
					var proinfo = data.data.content;
					if(datalist.length>0){
						for(var i in datalist){
							if(that.toppro.id==proinfo.id){
								that.isprotop = false;
								that.toppro = {}
							}
							if(datalist[i].id==proinfo.id){
								datalist.splice(i,1);
								that.prolist = datalist;
								break;
							}
						}
					}
				}else if(data.data.msgtype=='start_live'){
					that.detail.live_status=1;
					that.videoHeight = 200;
					that.getdata();
				}else if(data.data.msgtype=='end_live'){
					that.videoHeight = 200;
					that.detail.live_status=2;
					that.getdata();
				}else if(data.data.msgtype=='join'){
					that.watch_num++;
				}else if(data.data.msgtype=='watch'){
					// that.watch_num++;
				}else if(data.data.msgtype=='event'){
					that.geteventlist()
				}else if(data.data.msgtype=='productst0'){
					var datalist = that.prolist;
					var proinfo = data.data.content;
					that.getprolist();
				}else if(data.data.msgtype=='productst1'){
					var datalist = that.prolist;
					var proinfo = data.data.content;
					var curlist = [proinfo];
					var newdata = curlist.concat(datalist);
					that.prolist = newdata;
					that.isprotop = true;
					that.toppro = proinfo;
				}else if(data.data.msgtype=='producttop'){
					that.toppro = data.data.content
					that.isprotop = true
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
		scrollToBottom: function () {
		  var that = this;
		  setTimeout(function () {
				uni.pageScrollTo({
					scrollTop: 10000,
					duration:0
				});
		  },300);
		},
		scrollToTop: function () {
		  var that = this;
		  setTimeout(function () {
				uni.pageScrollTo({
					scrollTop: 0,
					duration:0
				});
		  },300);
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
		playEnd:function(e){
			var that = this;
			if(that.detail.type==1){
				var play_time = that.currentTime;
			}else{
				var play_time = that.currentTime2;
			}
			// app.post('ApiH5zb/playVideo', {roomid:that.roomid,isend:1,play_time:play_time}, function (res) {
			// 	console.log(res);
			// })
		},
		playOver:function(){
			var that = this;
			if(that.detail.type==1){
				var play_time = that.currentTime;
			}else{
				var play_time = that.currentTime2;
			}
			app.post('ApiH5zb/playVideo', {roomid:that.roomid,isend:2,play_time:play_time}, function (res) {
				console.log('playover');
			})
		},
		hideQuestionAlert:function(){
			var that = this;
			that.showQuestionGobtn = false;
		},
		startTimer() {
			console.log('intervalB:'+this.intervalB)
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
			that.member = {}
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
		}
  }
};
</script>
<style>
@import "../common.css";
 .video{width: 100%;}
 .buyopt{padding: 6rpx 20rpx;border-radius: 40rpx;font-size: 26rpx;}
 .font-tips{font-size: 24rpx;}
 .coupon-alert-row{border: 1px solid #f6f6ff; border-radius: 16rpx;padding: 20rpx;margin: 20rpx;margin-top: 40rpx;}
 .coupon-alert-L{width: 140rpx;text-align: right;display: flex;flex-direction: column;justify-content: center;align-items: center;text-align: center;border-right: 1px solid #e3e3e3;}
 .coupon-alert-L .coupon-alert-money{font-size: 50rpx;font-weight: bold;}
 .coupon-alert-R{flex: 1;margin-left: 10rpx; display: flex;flex-direction: column; padding-left: 20rpx;}
 .coupon-alert-name{font-size: 38rpx;margin-bottom: 10rpx;}
 .coupon-alert-tag{font-size: 24rpx;border-radius: 6rpx;padding: 4rpx 10rpx;}
 .bottom-alert-btn{ margin: 50rpx 20rpx 10rpx 20rpx; height: 80rpx;line-height: 80rpx;border-radius: 50rpx;color: #fff;}
 
 .redbag-alert{width: 500rpx;display: flex;flex-flow: column;align-items: center;justify-content: center;text-align: center;}
 .redbag-topbg{width: 500rpx}
 .redbag-content{width: 444rpx;background: #fff;min-height: 320rpx;margin-top: -10rpx;border-radius: 0 0 50rpx 50rpx;}
 .redbag-btn{margin: 20rpx 30rpx; height: 80rpx;line-height: 80rpx;border-radius: 50rpx;color: #fff;background: #ff5237;}
 .redbag-btn1{margin: 20rpx 30rpx; height: 80rpx;line-height: 80rpx;border-radius: 50rpx;color: #fff;background: #fbc80f;}
 .redbag-money{color: #ff5237;margin: 40rpx;line-height: 60rpx;}
 .redbag-money .money{font-size: 50rpx;font-weight: bold;}
 .redbg-close{width: 60rpx;height: 60rpx;margin: 10rpx auto;border: 1px solid #fff;border-radius: 50%;display: flex;align-items: center;justify-content: center;padding: 10rpx;}
 .redbg-close image{width: 30rpx;height: 30rpx;}
 
 .question-alert{border-radius: 16rpx;width: 100%;padding: 10rpx;}
 .question-title{font-size: 32rpx;font-weight: bold;}
 .question-info{display: flex;align-items: center;margin: 14rpx 0;border: 1px solid #efefef;padding:20rpx;border-radius: 10rpx;}
 .question-sort{flex-shrink: 0; width: 50rpx;}
 .question-choose{flex:1;}
 
 .verify-row{margin: 20rpx 30rpx;color: #999;}
 .verify-row input{color: #222;border: 1px solid #e3e3e3; border-radius: 10rpx;height: 80rpx;line-height: 80rpx;padding: 0 20rpx;}
 

 .score_main{width: 300rpx;display: flex;flex-direction: column;text-align: center;align-items: center;margin: 0 auto;border: 1px solid #efefef;padding: 30rpx;border-radius: 20rpx;}
 .scoretxt{font-size: 60rpx;font-weight: bold;}
 
 .option{display: flex;flex-direction: column;align-items: center;
 position: fixed;right: 0;bottom: 300rpx;border-right: none;background:#cbcbcb;color: #1a4e55;
 border-radius: 10rpx 0 0 10rpx;z-index: 400;}
 .option .opt{display: flex;flex-direction: column;align-items: center;justify-content: center;border-bottom: 1px solid #eee;padding:10rpx 20rpx;}
 .option .opt:last-child{border: none;}
 .option .opt .m{width:60rpx; height: 60rpx;border-radius: 10rpx;}
 .option .opt .t{max-width: 80rpx;text-align: center;font-size: 24rpx;overflow: hidden;text-overflow: ellipsis;white-space: nowrap;}
 .probox{width: 94%;margin: 30rpx 3%;}
 .prolist{margin-bottom: 20rpx;}
 .eventbox{background: #fff;width: 94%;margin: 30rpx 3%;border-radius: 16rpx;padding: 30rpx;}
 .eventlist{display: flex;align-items: center;height: 158rpx;}
 .eventL{border-left:  2px dashed red;flex-shrink: 0; width: 40rpx;display: flex;align-items: center;height: 150rpx;}
 .eventL .dot{width:30rpx;height: 30rpx;border: 2px solid #bbb;border-radius: 50%;background: #fff;margin-left: -16rpx;}
 .eventR{display: flex;flex: 1;flex-direction: column;}
 .eventR .btf{width: 100%;display: flex;justify-content: space-between;align-items: center;background: #f6f6ff; border-radius: 16rpx;padding: 20rpx;height: 120rpx;}
 .eventR .btg{}
 .eventimg{width: 50rpx;height: 60rpx;}
 .event{display: flex;align-items: center;}
 .event .time{ /* font-weight: bold; */margin-right: 20rpx;}
 .evenst{text-align: right;flex-shrink: 0;font-size: 24rpx;}
 .evenst .st1{color: #999;}
 .evenst .st-1{color: #999;}
 .evenst .st2{border-radius: 30rpx;padding:6rpx 20rpx;}
 .evenst .st3{border-radius: 30rpx;padding:6rpx 20rpx;}
 .zb-dd-tab{margin-top: -46rpx;height: 60px;}
 .content{top:540rpx;}
 /* .videoBox .video{max-height: 426rpx;} */
 
 /*视频自适应*/
 .videoBox .video{height: auto;min-height: 400rpx;width: 100%;}
.fixbox{height: auto;position: fixed;top: 0;width: 100%;background: #fff;z-index: 777;}
.video_main{position: relative;height: auto;width: 100%;}
.videoBox{width: 100%;height: auto;position: relative;top: 0;left: 0;display: flex;justify-content: center;align-items: center;}
.videoBoxUn{width: 100%;height: auto;position: relative;top: 0;left: 0;}
.videoBox .playerVideo{max-width: 100%;max-height: 100%;}
.videoBox .poster{max-height: auto;}
.vjs-tech{width:100%}

.chat{}
.video-poster{position: fixed;top: 0;width: 100%;height: 220px;}
.video-poster image{width: 100%;height: 100%;z-index: 60000;}
.hide{opacity: 0;}

.bar{height: 90px;position: fixed;width: 100%;position: absolute;}
.bar .countdownc{height: 30px;display: flex;justify-content: center;align-items: center;background: #00000047;z-index: 500;color: #fff;}
.bar .zbc-dd-tab{height: 60px;width: 100%;}
.alert_content{}
</style>