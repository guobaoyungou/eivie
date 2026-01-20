<template>
<view class="container" :style="{background:info.color1}">
	<block v-if="isload">
		<view class="banner"><image :src="info.banner" mode="widthFix"></image></view>
		<view class="box1">
			<view class="item"><view class="t1" :style="{color:info.color2}">{{info.joinnum}}</view><view class="t2">参与人数</view></view>
			<view class="item"><view class="t1" :style="{color:info.color2}">{{info.helpnum}}</view><view class="t2">累计投票</view></view>
			<view class="item"><view class="t1" :style="{color:info.color2}">{{info.readcount}}</view><view class="t2">访问次数</view></view>
		</view>
		<view class="box2">
			<view class="f1">
				<image :src="pre_url+'/static/img/clock.png'" style="width:32rpx;height:32rpx;margin-right:6rpx"/>
				<text>{{ info.starttime > nowtime ? '距活动开始还有' : '距活动结束还剩'}}</text>
			</view>
			<view class="f2" :style="{color:info.color2}">
				<text class="t1">{{djsday}}</text><text class="t2">天</text><text class="t1">{{djshour}}</text><text class="t2">小时</text><text class="t1">{{djsmin}}</text><text class="t2">分钟</text><text class="t1">{{djssec}}</text><text class="t2">秒</text>
			</view>
			<view class="topsearch flex-y-center">
				<view class="topsearch-f1 flex-y-center">
					<image class="img" :src="pre_url+'/static/img/search_ico.png'"></image>
					<input v-model="keyword" placeholder="搜索编号或选手名称" placeholder-style="font-size:24rpx;color:#778899" @confirm="searchConfirm"></input>
					<view class="search-btn-class" :style="{color:info.color2}" @click="searchConfirm">搜索</view>
				</view>
			</view>
		</view>
		<view class="box3">
			<block v-for="(item,index) in datalist" :key="index">

			<view v-if="info.listtype==0" class="item" @tap="goto" :data-url="'detail?id='+item.id">
				<view class="pic"><image :src="item.pic" class="img" mode="widthFix"/></view>
				<view class="name">{{item.name}}</view>
				<view class="no">NO: {{item.number}}</view>
				<view class="helpnum">{{item.helpnum}}票</view>
				<view class="tou" :style="{background:'linear-gradient(90deg,'+info.color2+' 0%,rgba('+info.color2rgb+',0.7) 100%)'}" @tap.stop="toupiao" :data-id="item.id" data-type="0" :data-isconfirm="isconfirm">{{info.helptext}}</view>
			</view>
			
			<view v-if="info.listtype==1" class="itemlist" @tap="goto" :data-url="'detail?id='+item.id">
				<view class="pic"><image :src="item.pic" class="img" mode="widthFix"/></view>
				<view class="right">
					<view class="name">{{item.name}}</view>
					<view class="no">NO: {{item.number}}</view>
					<view class="helpnum">{{item.helpnum}}票</view>
					<view class="tou" :style="{background:'linear-gradient(90deg,'+info.color2+' 0%,rgba('+info.color2rgb+',0.7) 100%)'}" @tap.stop="toupiao" :data-id="item.id" data-type="0" :data-isconfirm="isconfirm">{{info.helptext}}</view>
				</view>
			</view>

			</block>
		</view>
		<view style="background:#fff;width:94%;margin:0 3%;border-radius:12rpx" v-if="nodata"><nodata></nodata></view>
		<view style="width:100%;height:60rpx"></view>

		<uni-popup id="dialogPayscore" ref="dialogPayscore" type="dialog" v-if="isconfirm == 0">
			<view class="hxqrbox">
				<view class="hxqrbox1">
					<text>{{confirmmsg}}</text>
				</view>
				<view class="tou2" :style="{background:'linear-gradient(90deg,'+info.color2+' 0%,rgba('+info.color2rgb+',0.7) 100%)'}" @tap="toupiaoGoon" :data-id="nowjoinid" data-type="0" data-isconfirm="1">继续投票</view>
				<view class="close" @tap="closePayscore">
					<image :src="pre_url+'/static/img/close2.png'" style="width:100%;height:100%"/>
				</view>
			</view>
		</uni-popup>
		<uni-popup id="dialogPaynotenough" ref="dialogPaynotenough" type="dialog">
			<view class="hxqrbox">
				<view class="hxqrbox2">
					<text>{{confirmmsg}}</text>
				</view>
				<view v-if="paynotenoughtxt!='' && paynotenoughurl!=''" class="tou2" :style="{background:'linear-gradient(90deg,'+info.color2+' 0%,rgba('+info.color2rgb+',0.7) 100%)'}" @tap="goto" :data-url="paynotenoughurl">{{paynotenoughtxt}}</view>
				<view class="close" @tap="closePaynotenough">
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
			
      datalist: [],
      pagenum: 1,
      nomore: false,
      nodata: false,
			
      info: {},
			nowtime:0,
      djsday: '00',
      djshour: '00',
      djsmin: '00',
      djssec: '00',
			keyword:'',
			isconfirm:1,
			confirmmsg:'',
			ispaynotenough:0,
			paynotenoughtxt:'',
			paynotenoughurl:''
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
		return;
    if (!this.nodata && !this.nomore) {
      this.pagenum = this.pagenum + 1;
      this.getdata(true);
    }
  },
  onUnload: function (loadmore) {
    clearInterval(interval);
  },
  onShareAppMessage: function () {
		var that = this;
		var title = that.info.sharetitle ? that.info.sharetitle : that.info.name;
		var sharepic = that.info.sharepic ? that.info.sharepic : that.info.banner;
		var sharelink = that.info.sharelink ? that.info.sharelink : '';
		var sharedesc = that.info.sharedesc ? that.info.sharedesc : '';
		return this._sharewx({title:title,desc:sharedesc,link:sharelink,pic:sharepic});
  },
	onShareTimeline:function(){
		var that = this;
		var title = that.info.sharetitle ? that.info.sharetitle : that.info.name;
		var sharepic = that.info.sharepic ? that.info.sharepic : that.info.banner;
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
    getdata: function (loadmore) {
			if(!loadmore){
				this.pagenum = 1;
				this.datalist = [];
			}
      var that = this;
      var pagenum = that.pagenum;
			that.loading = true;
			that.nodata = false;
			that.nomore = false;
      app.post('ApiToupiao/index', {id:that.opt.id,pagenum:pagenum,keyword:that.keyword}, function (res) {
				that.loading = false;

				if (pagenum == 1) {
					that.datalist = res.datalist;
          if ((that.datalist).length == 0) {
            that.nodata = true;
          }
					that.info = res.info;
					that.nowtime = res.nowtime;
					uni.setNavigationBarTitle({
						title: that.info.name
					});
					var title = that.info.sharetitle ? that.info.sharetitle : that.info.name;
					var sharepic = that.info.sharepic ? that.info.sharepic : that.info.banner;
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
        }else{
          if ((res.datalist).length == 0) {
            that.nomore = true;
          } else {
            var datalist = that.datalist;
            var newdata = datalist.concat(res.datalist);
            that.datalist = newdata;
          }
        }
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
		searchConfirm:function(e){
			var that = this;
      // var keyword = e.detail.value;
      // that.keyword = keyword;
      that.getdata();
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
		closePaynotenough:function(){
			this.$refs.dialogPaynotenough.close();
		},
		toupiao:function(e){
			var that = this;
			var id = e.currentTarget.dataset.id;
			var type = e.currentTarget.dataset.type;
			// var isconfirm = e.currentTarget.dataset.isconfirm;
			//后台读取是否需要确认
			that.loading = true;
			app.get('ApiToupiao/toupiao',{id:id},function(res0){
				that.loading = false;
				if(res0.status==1){
					that.isconfirm = res0.isconfirm;
					that.confirmmsg = res0.msg;
					that.toupiaoSubmit(id,type)
				}else if(res0.status==20){
					that.confirmmsg = res0.msg
					that.paynotenoughtxt = res0.data.pay_not_enough
					that.paynotenoughurl = res0.data.pay_not_enough_url
					that.$refs.dialogPaynotenough.open();
				}else{
					app.alert(res0.msg);
				}
			})
		},
		toupiaoGoon:function(e){
			var that = this;
			that.isconfirm = 1;
			var id = e.currentTarget.dataset.id;
			var type = e.currentTarget.dataset.type;
			that.toupiaoSubmit(id,type)
		},
		toupiaoSubmit:function(id,type){
			var that = this;
			// var isconfirm = e.currentTarget.dataset.isconfirm;
			var isconfirm = that.isconfirm;
			var pay_type = that.info.pay_type || 0;
			var pay_score = that.info.pay_score || 0;
			var pay_money = that.info.pay_money || 0;
			var need_confirm = 0;
			if((pay_type==1 && pay_score>0) || (pay_type==2 && pay_money>0)){
				that.isconfirm = isconfirm
				need_confirm = 1;
				that.nowjoinid = id;
				if(isconfirm==0){
					that.$refs.dialogPayscore.open();
					return;
				}
			}
			if(type == 0 && that.info.help_check == 1){
				that.nowjoinid = id;
				that.$refs.dialogCaptcha.open();
				return;
			}
			if(type == 0 && that.info.help_check == 2){
				that.nowjoinid = id;
				that.$refs.dialogSmscode.open();
				return;
			}
			app.showLoading('投票中');
			app.post('ApiToupiao/toupiao',{id:id,captcha:that.captcha,smstel:that.smstel,smscode:that.smscode},function(res){
				app.showLoading(false);
				if(res.status==1){
					app.success(res.msg);
					var datalist = that.datalist;
					for(var i in datalist){
						if(datalist[i].id == id){
							datalist[i].helpnum++;break;
						}
					}
					that.datalist = datalist;
					that.info.helpnum++
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
						that.isconfirm = 0
					}
					app.alert(res.msg);
				}
			})
		}
  }
}
</script>
<style>

.banner{width:100%;}
.banner image{width:100%;height:auto}

.box1{width:94%;margin-left:3%;border-radius:12rpx;background:#fff;padding:60rpx 10rpx;display:flex;align-items:center;position:relative;z-index:12;margin-top:-160rpx}
.box1 .item{flex:1;display:flex;flex-direction:column;align-items:center;}
.box1 .item .t1{font-size:48rpx;font-weight:bold}
.box1 .item .t2{font-size:24rpx;color:#778899;margin-top:10rpx}
.box2{width:94%;margin-left:3%;border-radius:12rpx;background:#fff;padding:20rpx 10rpx;display:flex;flex-direction:column;align-items:center;margin-top:20rpx}
.box2 .f1{display:flex;align-items:center;color:#222222}
.box2 .f2{display:flex;align-items:flex-end;color:#222222;margin-top:20rpx;font-size:24rpx}
.box2 .f2 .t1{font-size:48rpx;font-weight:bold;padding:0 12rpx}
.box2 .f2 .t2{color:#222222;height:48rpx;line-height:48rpx}

.topsearch{width:94%;margin:40rpx 3% 10rpx 3%;}
.topsearch .topsearch-f1{height:80rpx;border-radius:10rpx;border:0;background-color:#f5f5f6;flex:1}
.topsearch .topsearch-f1 .img{width:24rpx;height:24rpx;margin-left:10px}
.topsearch .topsearch-f1 input{height:100%;flex:1;padding:0 20rpx;font-size:28rpx;color:#333;}
.topsearch .search-btn-class{font-size: 28rpx;color:#333;margin-right: 20rpx;}

.box3{width:96%;margin-left:2%;display:flex;flex-wrap:wrap;margin-top:20rpx;}
.box3 .item{width:48%;margin:8rpx 1%;background:#fff;border-radius:12rpx;overflow:hidden;position:relative;padding-bottom:24rpx}
.box3 .item .pic {width: 100%;height:0;overflow:hidden;background: #f7f7f8;padding-bottom: 100%;position: relative;margin-bottom:10rpx}
.box3 .item .pic .img{position:absolute;top:0;left:0;width: 100%;height:auto}
.box3 .item .name{padding:10rpx 10rpx;color:#222222;font-size:28rpx;font-weight:bold;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:1;overflow:hidden;}
.box3 .item .no{color:#778899;font-size:24rpx;padding:4rpx 10rpx;}
.box3 .item .helpnum{color:#778899;font-size:24rpx;padding:4rpx 10rpx;}
.box3 .item .tou{position:absolute;bottom:30rpx;right:20rpx;border-radius:24rpx;font-size:22rpx;color:#fff;height:48rpx;line-height:48rpx;padding:0 20rpx}

.box3 .itemlist{width:98%;margin:8rpx 1%;background:#fff;border-radius:12rpx;overflow:hidden;position:relative;padding:24rpx;display:flex}
.box3 .itemlist .pic {width:180rpx;height:180rpx;overflow:hidden;background: #f7f7f8;position: relative;}
.box3 .itemlist .pic .img{position:absolute;top:0;left:0;width: 100%;height:auto}
.box3 .itemlist .right{padding-left:20rpx;flex:1}
.box3 .itemlist .name{margin:10rpx 10rpx;color:#222222;font-size:28rpx;font-weight:bold;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:1;overflow:hidden;}
.box3 .itemlist .no{color:#778899;font-size:24rpx;padding:4rpx 10rpx;}
.box3 .itemlist .helpnum{color:#778899;font-size:24rpx;padding:4rpx 10rpx;}
.box3 .itemlist .tou{position:absolute;bottom:30rpx;right:20rpx;border-radius:24rpx;font-size:22rpx;color:#fff;height:48rpx;line-height:48rpx;padding:0 20rpx}

.hxqrbox{background:#fff;padding:50rpx;position:relative;border-radius:20rpx;max-width: 94%;margin: 0 auto;min-width: 500rpx;}
.hxqrbox .hxqrbox1{display:flex;align-items:center}
.hxqrbox .hxqrbox2{text-align: center;}
.hxqrbox .hxqrbox1 input{width:270rpx;}
.hxqrbox .hxqrbox1 image{width:210rpx;height:70rpx}
.hxqrbox  .tou2{border-radius:24rpx;font-size:32rpx;color:#fff;height:80rpx;line-height:80rpx;padding:0 20rpx;text-align:center;margin-top:60rpx}
.hxqrbox .close{width:50rpx;height:50rpx;position:absolute;bottom:-100rpx;left:50%;margin-left:-25rpx;border:1px solid rgba(255,255,255,0.5);border-radius:50%;padding:8rpx}
</style>