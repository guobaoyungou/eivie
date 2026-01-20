<template>
<view class="container" :style="{background:info.color1}">
	<block v-if="isload">
		<view class="topbannerbg" :style="detail.pic?'background:url('+detail.pic+') 100%;background-size:100%':''"></view>
		<view class="topbannerbg2" :style="{background:'linear-gradient(rgba('+info.color1rgb+',0.8) 0%,'+info.color1+' 50%)'}"></view>

		<view class="box1">
			<view class="f1">
				<image :src="detail.pic"></image>
				<view class="t1">{{detail.name}}</view>
			</view>
			<view class="f2">
				<view class="item"><view class="t1" :style="{color:info.color2}">{{detail.number}}</view><view class="t2">编号</view></view>
				<view class="item"><view class="t1" :style="{color:info.color2}">{{detail.helpnum}}</view><view class="t2">得票数</view></view>
				<view class="item"><view class="t1" :style="{color:info.color2}">{{detail.mingci}}</view><view class="t2">当前排名</view></view>
			</view>

			<view class="f3">
				<image :src="pre_url+'/static/img/clock.png'" style="width:32rpx;height:32rpx;margin-right:6rpx"/>
				<text>{{ info.starttime > nowtime ? '距活动开始还有' : '距活动结束还剩'}}</text>
			</view>
			<view class="f4" :style="{color:info.color2}">
				<text class="t1">{{djsday}}</text><text class="t2">天</text><text class="t1">{{djshour}}</text><text class="t2">小时</text><text class="t1">{{djsmin}}</text><text class="t2">分钟</text><text class="t1">{{djssec}}</text><text class="t2">秒</text>
			</view>

		</view>
		<view class="box2">
			<view style="width:100%;"  v-if="toupiao_type == 0">
				<block v-for="(item,index) in detail.pics">
					<image :src="item" style="width:100%;" mode="widthFix"/>
				</block>
			</view>
			<view style="width:100%;" v-else-if="toupiao_type == 1">
				<block v-for="(v,k) in detail.videos" :key="k">
					<video :src="v" style="width:100%;" />
				</block>
			</view>
			<view style="width:100%;text-align:left;white-space:pre-wrap;margin-top:10rpx;font-size:32rpx">{{detail.detail_txt}}</view>
			<parse :content="detail.detail" />
		</view>
		<view style="width:100%;height:160rpx"></view>
		<view class="footer">
			<view class="btn1" :style="{background:'rgba('+info.color2rgb+',0.12)',color:info.color2}" v-if="info.canapply==1" @tap="goto" :data-url="'baoming?id='+info.id">我要报名</view>
			<view class="btn2" :style="{background:info.color2}" @tap="toupiao" :data-id="detail.id" data-type="0" :data-isconfirm="isconfirm">{{info.helptext}}</view>
		</view>

		<uni-popup id="dialogPayscore" ref="dialogPayscore" type="dialog" v-if="isconfirm == 0">
			<view class="hxqrbox">
				<view class="hxqrbox1">
					<text v-if="info.pay_type==1">投票需要消耗{{info.pay_score}}{{t('积分')}}，是否继续投票?</text>
					<text v-if="info.pay_type==2">投票需要扣除{{info.pay_money}}元{{t('余额')}}，是否继续投票?</text>
				</view>
				<view class="tou2" :style="{background:'linear-gradient(90deg,'+info.color2+' 0%,rgba('+info.color2rgb+',0.7) 100%)'}" @tap="toupiao" :data-id="nowjoinid" data-type="0" data-isconfirm="1">继续投票</view>
				<view class="close" @tap="closePayscore">
					<image :src="pre_url+'/static/img/close2.png'" style="width:100%;height:100%"/>
				</view>
			</view>
		</uni-popup>
		<uni-popup id="dialogCaptcha" ref="dialogCaptcha" type="dialog" v-if="info.help_check == 1">
			<view class="hxqrbox">
				<view class="hxqrbox1">
					<input type="text" placeholder="请输入验证码" @input="setcaptcha"/>
					<image @tap="regetcaptcha" :src="pre_url+'/?s=/ApiIndex/captcha&aid='+aid+'&session_id='+session_id+'&t='+randt"/>
				</view>
				<view class="tou2" :style="{background:'linear-gradient(90deg,'+info.color2+' 0%,rgba('+info.color2rgb+',0.7) 100%)'}" @tap="toupiao" :data-id="nowjoinid" data-type="1" :data-isconfirm="isconfirm">确 定</view>
				<view class="close" @tap="closeCaptcha">
					<image :src="pre_url+'/static/img/close2.png'" style="width:100%;height:100%"/>
				</view>
			</view>
		</uni-popup>
		<uni-popup id="dialogSmscode" ref="dialogSmscode" type="dialog" v-if="info.help_check == 2">
			<view class="hxqrbox">
				<view class="hxqrbox1">
					<image :src="pre_url+'/static/img/reg-tel.png'" style="width:44rpx;height:44rpx;margin-right:30rpx"/>
					<input type="text" placeholder="请输入手机号" @input="setsmstel"/>
					<view class="code" :style="{color:t('color1')}" @tap="sendsmscode" style="font-size:30rpx;width:160rpx;text-align:center">{{smsdjs||'获取验证码'}}</view>
				</view>
				<view class="hxqrbox1" style="margin-top:40rpx">
					<image :src="pre_url+'/static/img/reg-code.png'" style="width:44rpx;height:44rpx;margin-right:30rpx"/>
					<input type="text" placeholder="请输入短信验证码" @input="setsmscode"/>
				</view>
				<view class="tou2" :style="{background:'linear-gradient(90deg,'+info.color2+' 0%,rgba('+info.color2rgb+',0.7) 100%)'}" @tap="toupiao" :data-id="nowjoinid" data-type="1" :data-isconfirm="isconfirm">确 定</view>
				<view class="close" @tap="closeSmscode">
					<image :src="pre_url+'/static/img/close2.png'" style="width:100%;height:100%"/>
				</view>
			</view>
		</uni-popup>

	</block>
	<loading v-if="loading"></loading>
	<dp-tabbar :opt="opt"></dp-tabbar>
	<popmsg ref="popmsg"></popmsg>
</view>
</template>

<script>
var app = getApp();
var interval = null;
export default {
  data() {
    return {  
			opt:{},
			loading:false,
      isload: false,
			menuindex:-1,
      info: {},
			detail:{},
			
			
			pre_url:app.globalData.pre_url,
			aid:app.globalData.aid,
			session_id:app.globalData.session_id,
			captcha:'',
			randt:'',
			nowjoinid:0,
			smscode:'',
      smsdjs: '',
			smstel:'',
      hqing: 0,
			
			nowtime:0,
      djsday: '00',
      djshour: '00',
      djsmin: '00',
      djssec: '00',
			keyword:'',
			isconfirm:1,
			toupiao_type:0
    };
  },
  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.getdata();
  },
	onPullDownRefresh: function () {
		this.getdata();
	}, 
  onUnload: function () {
    clearInterval(interval);
  },
  onShareAppMessage: function () {
		var that = this;
		var title = that.detail.name + ' - ' + (that.info.sharetitle ? that.info.sharetitle : that.info.name);
		var sharepic = that.detail.pic;
		var sharelink = that.info.sharelink ? that.info.sharelink : '';
		var sharedesc = that.info.sharedesc ? that.info.sharedesc : '';
		return this._sharewx({title:title,desc:sharedesc,link:sharelink,pic:sharepic});
  },
	onShareTimeline:function(){
		var that = this;
		var title = that.detail.name + ' - ' + (that.info.sharetitle ? that.info.sharetitle : that.info.name);
			var sharepic = that.detail.pic;
			var sharelink = that.info.sharelink ? that.info.sharelink : '';
			var sharedesc = that.info.sharedesc ? that.info.sharedesc : '';
		var sharewxdata = this._sharewx({title:title,desc:sharedesc,link:sharelink,pic:sharepic});
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
			that.loading = true;
      app.post('ApiToupiao/detail', {id:that.opt.id}, function (res) {
				that.loading = false;
        that.info = res.info;
        that.detail = res.detail;
        that.nowtime = res.nowtime;
				uni.setNavigationBarTitle({
					title: that.info.name
				});
				if(res.info && res.info.toupiao_type > 0){
					that.toupiao_type = res.info.toupiao_type;
				}
				var title = that.detail.name + ' - ' + (that.info.sharetitle ? that.info.sharetitle : that.info.name);
				var sharepic = that.detail.pic;
				var sharelink = that.info.sharelink ? that.info.sharelink : '';
				var sharedesc = that.info.sharedesc ? that.info.sharedesc : '';
				if((that.info.pay_type==1 && that.info.pay_score>0) || (that.info.pay_type==2 && that.info.pay_money>0)){
					that.isconfirm = 0;
				}
				that.loaded({title:title,desc:sharedesc,link:sharelink,pic:sharepic});

				clearInterval(interval);
				interval = setInterval(function () {
					that.nowtime = that.nowtime + 1;
					that.getdjs();
				}, 1000);
      });
    },
    getdjs: function () {
      var that = this;
      if (that.info.starttime * 1 > that.nowtime * 1) {
        var totalsec = that.info.starttime * 1 - that.nowtime * 1;
      } else {
        var totalsec = that.info.endtime * 1 - that.nowtime * 1;
      }
      if (totalsec <= 0) {
        that.djsday = '00';
        that.djshour = '00';
        that.djsmin = '00';
        that.djssec = '00';
      } else {
        var date = Math.floor(totalsec / 86400);
        var houer = Math.floor((totalsec - date * 86400) / 3600);
        var min = Math.floor((totalsec - date * 86400 - houer * 3600) / 60);
        var sec = totalsec - date * 86400 - houer * 3600 - min * 60;
        var djsday = (date < 10 ? '0' : '') + date;
        var djshour = (houer < 10 ? '0' : '') + houer;
        var djsmin = (min < 10 ? '0' : '') + min;
        var djssec = (sec < 10 ? '0' : '') + sec;
        that.djsday = djsday;
        that.djshour = djshour;
        that.djsmin = djsmin;
        that.djssec = djssec;
      }
    },
		regetcaptcha:function(){
			this.randt = this.randt+'1';
		},
		setcaptcha:function(e){
			this.captcha = e.detail.value;
		},
		setsmscode:function(e){
			this.smscode = e.detail.value;
		},
		setsmstel:function(e){
			this.smstel = e.detail.value;
		},
    sendsmscode: function () {
      var that = this;
      if (that.hqing == 1) return;
      that.hqing = 1;
      var smstel = that.smstel;
      if (smstel == '') {
        app.alert('请输入手机号码');
        that.hqing = 0;
        return false;
      }
      if (!app.isPhone(smstel)) {
        app.alert("手机号码有误，请重填");
        that.hqing = 0;
        return false;
      }
      app.post("ApiIndex/sendsms", {tel: smstel}, function (data) {
        if (data.status != 1) {
          app.alert(data.msg);return;
        }
      });
      var time = 120;
      var interval1 = setInterval(function () {
        time--;
        if (time < 0) {
          that.smsdjs = '重新获取';
          that.hqing = 0;
          clearInterval(interval1);
        } else if (time >= 0) {
          that.smsdjs = time + '秒';
        }
      }, 1000);
    },
		closeCaptcha:function(){
			this.$refs.dialogCaptcha.close();
		},
		closeSmscode:function(){
			this.$refs.dialogSmscode.close();
		},
		closePayscore:function(){
			this.$refs.dialogPayscore.close();
		},
		payscoreCheck:function(){
			var that = this;
			var pay_type = that.info.pay_type || 0;
			var pay_score = that.info.pay_score || 0;
			var pay_money = that.info.pay_money || 0;
			if(pay_type==1 && pay_score > 0){
				this.$refs.dialogPayscore.open();
				return;
			}
			if(pay_type==2 && pay_money > 0){
				this.$refs.dialogPayscore.open();
				return;
			}
		},
		toupiao:function(e){
			var that = this;
			var id = e.currentTarget.dataset.id;
			var type = e.currentTarget.dataset.type;
			var isconfirm = e.currentTarget.dataset.isconfirm;;
			var pay_type = that.info.pay_type || 0;
			var pay_score = that.info.pay_score || 0;
			var pay_money = that.info.pay_money || 0;
			var need_confirm = 0;
			if((pay_type==1 && pay_score>0) || (pay_type==2 && pay_money>0)){
				that.isconfirm = isconfirm
				need_confirm = 1
				if(isconfirm==0){
					this.nowjoinid = id;
					this.$refs.dialogPayscore.open();
					return;
				}
			}
			
			if(type == 0 && that.info.help_check == 1){
				this.nowjoinid = id;
				this.$refs.dialogCaptcha.open();
				return;
			}
			if(type == 0 && that.info.help_check == 2){
				this.nowjoinid = id;
				this.$refs.dialogSmscode.open();
				return;
			}
			app.showLoading('投票中');
			app.post('ApiToupiao/toupiao',{id:id,captcha:that.captcha,smstel:that.smstel,smscode:that.smscode},function(res){
				app.showLoading(false);
				if(res.status==1){
					app.success(res.msg);
					that.detail.helpnum++
					if(type == 1 && that.info.help_check == 1){
						that.$refs.dialogCaptcha.close();
					}
					if(type == 1 && that.info.help_check == 2){
						that.$refs.dialogSmscode.close();
					}
					//投票成功后跳转
					if(that.info.jump_url){
						setTimeout(function () {
						  app.goto(that.info.jump_url);
						}, 1000);
					}
				}else{
					if(need_confirm==1){
						that.isconfirm = 0;
					}
					app.alert(res.msg);
				}
			})
		}
  }
}
</script>
<style>
.topbannerbg{width:100%;height:750rpx;background:#fff;position:relative;}
.topbannerbg2{position:absolute;z-index:7;width:100%;height:750rpx;top:0;}

.box1{width:94%;margin-left:3%;border-radius:12rpx;background:#fff;padding:60rpx 10rpx;display:flex;flex-direction:column;align-items:center;position:relative;z-index:12;margin-top:-400rpx}
.box1 .f1{display:flex;flex-direction:column;align-items:center;margin-top:-200rpx;margin-bottom:40rpx}
.box1 .f1 image{width:320rpx;height:320rpx;border-radius:16rpx}
.box1 .f1 .t1{color:#222222;font-size:36rpx;font-weight:bold;height:100rpx;line-height:100rpx}
.box1 .f2{display:flex;align-items:center;width:100%}
.box1 .f2 .item{flex:1;display:flex;flex-direction:column;align-items:center;}
.box1 .f2 .item .t1{font-size:48rpx;font-weight:bold;}
.box1 .f2 .item .t2{font-size:24rpx;color:#778899;margin-top:10rpx}
.box1 .f3{display:flex;align-items:center;color:#222222;margin-top:60rpx}
.box1 .f4{display:flex;align-items:flex-end;color:#222222;margin-top:20rpx;font-size:24rpx}
.box1 .f4 .t1{font-size:48rpx;font-weight:bold;padding:0 12rpx}
.box1 .f4 .t2{color:#222222;height:48rpx;line-height:48rpx}

.box2{width:94%;margin-left:3%;border-radius:12rpx;background:#fff;padding:20rpx 20rpx;display:flex;flex-direction:column;align-items:center;position:relative;z-index:12;margin-top:20rpx}

.footer{width:100%;position:fixed;bottom:0;height:132rpx;border-top:1px solid #eeeeee;z-index:14;background:#fff;display:flex;align-items:center;justify-content:center;padding:0 30rpx}
.footer .btn1{width:270rpx;height:88rpx;line-height:88rpx;border-radius:8rpx;text-align:center;margin-right:20rpx;}
.footer .btn2{flex:1;height:88rpx;line-height:88rpx;border-radius:8rpx;text-align:center;color:#fff}


.hxqrbox{background:#fff;padding:50rpx;position:relative;border-radius:20rpx}
.hxqrbox .hxqrbox1{display:flex;align-items:center}
.hxqrbox .hxqrbox1 input{width:270rpx;}
.hxqrbox .hxqrbox1 image{width:210rpx;height:70rpx}
.hxqrbox  .tou2{border-radius:24rpx;font-size:32rpx;color:#fff;height:80rpx;line-height:80rpx;padding:0 20rpx;text-align:center;margin-top:60rpx}
.hxqrbox .close{width:50rpx;height:50rpx;position:absolute;bottom:-100rpx;left:50%;margin-left:-25rpx;border:1px solid rgba(255,255,255,0.5);border-radius:50%;padding:8rpx}
</style>