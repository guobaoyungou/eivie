<template>
<view class="container">
	<block v-if="isload">
		<view class="message-list">
			<block v-for="(item, index) in datalist" :key="index">
				<view class="message-time" v-if="item.formatTime">{{item.formatTime}}</view>
				<view class="message-item" v-if="item.isreply==0">
					<image class="message-avatar" mode="aspectFill" :src="item.headimg"></image>
					<view class="message-text-left">
						<view class="arrow-box arrow-left">
							<image class="arrow-icon" :src="pre_url+'/static/img/arrow-white.png'"></image>
						</view>
						<view class="message-text">
							<parse :content="item.content" />
						</view>
					</view>
				</view>
				<view class="message-item" style="justify-content:flex-end" v-else>
					<view class="message-text-right">
						<view class="arrow-box arrow-right">
							<image class="arrow-icon" :src="pre_url+'/static/img/arrow-green.png'"></image>
						</view>
						<view class="message-text">
							<parse :content="item.content" />
						</view>
					</view>
					<image class="message-avatar" mode="aspectFill" :src="item.uheadimg"></image>
				</view>
			</block>
		</view>
		<view class="input-box notabbarbot" id="input-box">
			<view class="input-form">
				<image @tap="sendimg" class="pic-icon" :src="pre_url+'/static/img/msg-pic.png'"></image>
				<input @confirm="sendMessage" @focus="onInputFocus" @input="messageChange" class="input" :confirmHold="true" confirmType="send" cursorSpacing="20" type="text" :value="message" maxlength="-1"/>
				<image @tap="toggleFaceBox" class="face-icon" :src="pre_url+'/static/img/face-icon.png'"></image>
				<button class="send-button" v-if="!trimMessage">
					发送
				</button>
				<button @tap="sendMessage" class="send-button-active" v-if="trimMessage">
					发送
				</button>
			</view>
			<wxface v-if="faceshow" @selectface="selectface"></wxface>
		</view>
	</block>
	<loading v-if="loading"></loading>
	<popmsg ref="popmsg"></popmsg>
</view>
</template>
<script>
var app = getApp();
var interval0;

export default {
  data() {
    return {
			opt:{},
      isload: false,
      loading: false,
			
			token:'',
			uid:0,
			nowtime:'',
      pagenum: 1,
      datalist: [],
      message: "",
      trimMessage: "",
      faceshow: false,
      nomore: false,
			keyword:'',
      pre_url:app.globalData.pre_url
    };
  },
  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.mid = this.opt.mid;
		this.getdata();
  },
  onUnload: function () {
    clearInterval(interval0);
  },
	onNavigationBarSearchInputConfirmed:function(e){
		this.searchConfirm({detail:{value:e.text}});
	},
  methods: {
		getdata:function(){
			var that = this;
			that.loading = true;
			app.get('ApiAdminKefu/message',{mid:that.mid},function (res) {
				that.loading = false;
				that.token = res.token;
				that.uid = res.uid;
				that.nowtime = res.nowtime;
				uni.setNavigationBarTitle({
					title: res.tomember.nickname
				});
				that.getdatalist();
				that.loaded();

				interval0 = setInterval(function () {
					that.nowtime++;
				}, 1000);
			});
		},
    updateMessageTime: function () {
      var that = this;
      var datalist = this.datalist;
			console.log(datalist)
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
			console.log(datalist)
      this.datalist = datalist;
    },
    getdatalist: function () {
      var that = this;
      if (that.loading) return;
			that.loading = true;
			app.post('ApiAdminKefu/getmessagelist',{mid:that.mid,pagenum: that.pagenum,keyword:that.keyword}, function (res) {
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
            mid: that.mid,
						umid:app.globalData.mid,
            uid: that.uid,
            msgtype: 'text',
            content: message,
						platform:app.globalData.platform,
						pre_url:app.globalData.pre_url
          };
          app.sendSocketMessage({
            type: 'tokehu',
            token: that.token,
            data: msgdata
          });
          that.message = "";
          that.trimMessage = "";
          that.faceshow = false
        }
      }
    },
    sendimg: function () {
      var that = this;
      app.chooseImage(function (data) {
        for (var i = 0; i < data.length; i++) {
          var message = data[i];
          var msgdata = {
            aid: app.globalData.aid,
            mid: that.mid,
						umid:app.globalData.mid,
            uid: that.uid,
            msgtype: 'image',
            content: message,
						platform:app.globalData.platform,
						pre_url:app.globalData.pre_url
          };
          app.sendSocketMessage({
            type: 'tokehu',
            token: that.token,
            data: msgdata
          });
        }
      }, 3);
    },
    receiveMessage: function (data) {
			var that = this;
			if((data.type == 'tokefu' && that.mid == data.data.mid) || (data.type=='tokehu' && that.mid == data.data.mid)){
				var message = data.data
				message.content = that.transformMsgHtml(message.msgtype, message.content);
				that.datalist = that.datalist.concat([message]);
				setTimeout(that.updateMessageTime, 100);
				this.scrollToBottom();
				app.post('ApiKefu/isread',{mid:that.mid});
				return true;
			}else{
				return false;
			}
    },
    toggleFaceBox: function () {
      this.faceshow = !this.faceshow
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
    onInputFocus: function (e) {
      this.faceshow = false
    },
		onPageScroll: function (e) {
			var that = this;
			var scrollY = e.scrollTop;     
			if (scrollY == 0 && !that.nomore) {
				this.getdatalist();
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
		searchConfirm:function(e){
			this.keyword = e.detail.value;
			this.pagenum = 1;
			this.datalist = [];
      this.getdatalist();
		}
  }
};
</script>
<style>
.container{display: block;min-height: 100%;	box-sizing: border-box;	background: #f4f4f4;color: #222;}
.message-list {	padding-left: 25rpx;	padding-right: 25rpx;	padding-bottom: 20rpx;padding-bottom:100rpx;}
.message-item {display:flex;padding:20rpx 0}
.message-time {	width:100%;padding-top: 20rpx;	padding-bottom: 10rpx;	text-align: center;display: inline-block;	color: #999;	font-size: 24rpx;}
.message-avatar {	width: 90rpx;	height: 90rpx;	border-radius: 50%;}
.message-text {	max-width: 525rpx;	min-height: 64rpx;	line-height: 50rpx;	font-size: 30rpx;	padding: 20rpx 30rpx;word-break:break-all}
.message-text-left {	position: relative;	background-color: #fff;	margin-left: 20rpx;	border-radius: 12rpx;	border: 1rpx solid #dddddd;}
.message-text-right {	position: relative;	background-color: #9AE966;	margin-right: 20rpx;	border-radius: 12rpx;	border: 1rpx solid #6DBF58;}
.arrow-box {	position: absolute;	width: 16rpx;	height: 24rpx;	top: 35rpx;}
.arrow-left {left: -14rpx;}
.arrow-right {right: -14rpx;}
.arrow-icon {	display: block;	width: 100%;	height: 100%;}

.input-box {	position: fixed;	z-index: 100;	bottom: 0;	width: 96%;	min-height: 100rpx;	padding: 15rpx 2%;	background-color: #fff;box-sizing:content-box}
.input-form {	width: 100%;	height: 100%;	display: flex;	flex-direction: row;	align-items: center;}
.input {	flex: 1;	height: 66rpx;	border: 1rpx solid #ddd;	padding: 5rpx 10rpx;	background-color: #fff;	font-size: 30rpx;	border-radius: 12rpx;}
.pic-icon {	width: 54rpx;	height: 54rpx;	margin-right: 18rpx;}
.face-icon {	width: 60rpx;	height: 60rpx;	margin-left: 18rpx;}
.faces-box {	width: 100%;	height: 500rpx;}
.single-face {	width: 48rpx;	height: 48rpx;	margin: 10rpx;}
.send-button {	width: 100rpx;	height: 62rpx;	margin-left: 18rpx;	border-radius: 8rpx;	text-align: center;	line-height: 62rpx;	font-size: 28rpx;	background-color: #dcdcdc;	border: 1rpx solid #ccc;	color: #999;}
.send-button-active {	width: 100rpx;	height: 62rpx;	margin-left: 20rpx;	border-radius: 8rpx;	text-align: center;	line-height: 62rpx;	font-size: 28rpx;	background-color: #2396FC;	border: 1rpx solid #1F88E5;	color: #fff;}
.send-icon {	width: 56rpx;	height: 56rpx;}

.anit{width: 100%;height: 70rpx;background:#555;position: absolute;color:#fff;font-size: 30rpx;line-height: 70rpx;top: -70rpx;text-align: left;padding:0 20rpx;overflow:hidden}
.show{top: 0rpx;animation: show 0.2s;animation-timing-function:ease;}
@keyframes show{from {top:-70rpx;}to {top:0rpx;}}
.hide{top: -70rpx;animation: hide 0.2s;animation-timing-function:ease;}
@keyframes hide{from {top:0rpx;}to {top:-70rpx;}}
</style>