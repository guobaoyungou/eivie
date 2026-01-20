<template>
<view style="width: 100%;height: auto;min-height: 100%">
	<block v-if="isload">
    <view class="content" :style="'background:url('+bgpic+') no-repeat ;background-size:100% 100%;'">
      
      <view v-if="notices && notices.length>0" class="bobaobox" >
      	<swiper style="position:relative;height:54rpx;width:450rpx;" autoplay="true" :interval="5000" vertical="true">
      		<swiper-item v-for="(item, index) in notices" :key="index"  class="flex-y-center">
      			<image :src="item.headimg"style="width:40rpx;height:40rpx;border:1px solid rgba(255,255,255,0.7);border-radius:50%;margin-right:4px"></image>
      			<view style="width:400rpx;white-space:nowrap;overflow:hidden;text-overflow: ellipsis;font-size:22rpx">
      				<text style="padding-right:2px">{{item.nickname}}</text>
      				<text style="padding-right:4px">{{item.showtime}}</text>
      				<text>{{item.msg}}</text>
      			</view>
      		</swiper-item>
      	</swiper>
      </view>
      
      <view v-if="data.shortcontent" @tap="changeshortcontent" class="shortcontent">使用说明</view>

      <view style="width: 100%;clear: both;height: 230rpx;"></view>
      <view class="centercontent" :style="'background:url('+centerpic+') no-repeat ;background-size:100% 100%;'">
        <view style="color: #A1351D;text-align: center;margin-top: 34rpx;">
          <text v-if="!data.status || data.status == 0">当前待拆金额</text>
          <text v-else-if="data.status && data.status == 1">红包金额(已拆完)</text>
          <text v-else>红包金额(已失效)</text>
        </view>
        <view style="text-align: center;font-weight: bold;">
          <text style="color: #FE0000;font-size: 30rpx;">￥</text>
          <text style="color: #FE0000;font-size: 60rpx;">{{data.redpacket}}</text>
        </view>
        <view style="text-align: center;color: #A1351D;margin-top: 10rpx;">
            <text>已邀请</text><text>{{data.invitenum}}/{{data.newnum}}人</text>
        </view>
        <view v-if="!data.status || data.status == 0" class="jindu">    
          <view style="width: 140rpx;font-size: 24rpx;">邀请进度</view> 
          <view style="width: 320rpx;"><progress activeColor="#FFE72B" backgroundColor="#FAA0AD" :percent="data.invitepercent" show-info stroke-width="3" font-size='10'/></view>
        </view>
        <view v-if="!data.status || data.status == 0" style="width: 100%;position: absolute;bottom: 150rpx;left: 0;">
          <view class="optred">
            <view @tap="takeredpacket" class="optred1" :style="'background:url('+btnpic+') no-repeat ;background-size:100% 100%;'">
              <view style="line-height: 72rpx;">拆红包</view>
            </view>
            <view v-if="data.showinvite" @tap="shareClick" class="optred1" :style="'background:url('+btnpic+') no-repeat ;background-size:100% 100%;'">
              <view style="line-height: 72rpx;">邀请好友</view>
            </view>
          </view>
        </view>
        <view v-if="!data.status || data.status == 0" style="width: 100%;position: absolute;bottom: 70rpx;left: 0;">
          <view class="daojishi">
            <view style="color:#FCD09D;margin-right: 10rpx;">红包有效倒计时:</view>
            <view class="daojishi0">
              <block v-if="day && day !== '00'">
                <view class="daojishi1">{{day}}</view>
                <view class="daojishi2" style="width: 40rpx;padding-left: 4rpx;text-align: left;">天 </view>
              </block>
              <view class="daojishi1">{{hour}}</view>
              <view class="daojishi2">：</view>
              <view class="daojishi1">{{minute}}</view>
              <view class="daojishi2">：</view>
              <view class="daojishi1">{{second}}</view>
            </view>
          </view>
        </view>
      </view>
      <view class="join" >
        <view class="jointitle">助力列表</view>
        <scroll-view scroll-y="true" style="width: 660rpx;margin: 0 auto;;height: 410rpx;">
          <block v-if="childs" v-for="(item,index) in childs" :key="index">
            <view class="joincontent">
              <view style="width: 440rpx;display: flex;align-items: center;">
                <view class="joinpic" >
                  <image :src="item.headimg" style="width: 100%;height: 100%;"></image>
                </view>
                <view class="joinname">{{item.nickname}}</view>
              </view>
              <view class="jointip" >
                <text style="font-size: 24rpx;">￥</text>
                <text style="font-size: 30rpx;">{{item.money}}</text>
              </view>
            </view>
          </block>
        </scroll-view>
      </view>
      <view style="width: 100%;clear: both;height: 60rpx;"></view>
    </view>
    <view v-if="sharetypevisible" class="popup__container">
    	<view class="popup__overlay" @tap.stop="handleClickMask"></view>
    	<view class="popup__modal" style="height:320rpx;min-height:320rpx">
    		<view class="popup__content">
    			<view class="sharetypecontent">
    				<view class="f1" @tap="shareapp" v-if="getplatform() == 'app'">
    					<image class="img" :src="pre_url+'/static/img/sharefriends.png'"/>
    					<text class="t1">分享给好友</text>
    				</view>
    				<view class="f1" @tap="sharemp" v-else-if="getplatform() == 'mp' || getplatform() == 'h5'">
    					<image class="img" :src="pre_url+'/static/img/sharefriends.png'"/>
    					<text class="t1">分享给好友</text>
    				</view>
    				<button class="f1" open-type="share" v-else-if="getplatform() != 'h5'">
    					<image class="img" :src="pre_url+'/static/img/sharefriends.png'"/>
    					<text class="t1">分享给好友</text>
    				</button>
    			</view>
    		</view>
    	</view>
    </view>
	</block>
  
  <block v-if="showshortcontent">
    <view style="width:100%;height: 100%;background-color: #000;position: fixed;opacity: 0.5;z-index: 99;top:0"></view>
    <view style="width: 700rpx;margin: 0 auto;position: fixed;top:10%;left: 25rpx;z-index: 100;">
        <scroll-view scroll-y="true" style="background-color: #fff;border-radius: 20rpx;overflow: hidden;width: 100%;height: 900rpx;padding: 20rpx;">
          <parse :content="data.shortcontent" ></parse>
        </scroll-view>
        <view @tap="changeshortcontent" style="width: 60rpx;height: 60rpx;line-height: 60rpx;text-align: center;font-size: 30rpx;background-color: #fff;margin: 0 auto;border-radius: 50%;margin-top: 20rpx;">
            X
        </view>
    </view>
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

      rplid:0,
      rid:0,
			data:{},
      bgpic:'',
      centerpic:'',
      btnpic:'',
			shareTitle:'',
			sharePic:'',
			shareDesc:'',
			shareLink:'',
      sharetypevisible: false,
      showposter: false,
      djtime:0,
      day:'00',
      hour:'00',
      minute:'00',
      second:'00',
      childs:'',
      notices:'',
      showshortcontent:false
		}
  },

  onLoad: function (opt) {
		this.opt = app.getopts(opt);
    this.rplid = this.opt.rplid || 0;
    this.rid   = this.opt.rid || 0;
    this.pid   = this.opt.pid || 0;
    
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
			app.get('ApiInviteRedpacket/redpacketdetail', {rplid: that.rplid,rid: that.rid,pid: that.pid}, function (res) {
				that.loading = false;
        if(res.status == 1){
          that.data   = res.data;
          that.djtime = res.data.djtime || 0;
          if (res.data.djtime > 0) {
          	interval = setInterval(function () {
          		that.djtime = that.djtime - 1;
          		that.getdjs();
          	}, 1000);
          }
          that.bgpic = res.data.bgpic || '';
          that.centerpic = res.data.centerpic || '';
          that.btnpic = res.data.btnpic || '';
          
          if(res.notices){
            that.notices = res.notices;
          }
          if(res.childs){
            that.childs = res.childs;
          }
          
          var pid = app.globalData.mid;
          if(res.pid){
            pid = res.pid;
            that.pid = res.pid;
          }
          that.shareTitle = '送你一个红包点击领取';
          that.shareDesc  = '点击前往查看领取';
          that.sharePic   = res.data.pic?res.data.pic:app.globalData.initdata.logo;
          that.shareLink  = app.globalData.pre_url +'/h5/'+app.globalData.aid+'.html#/pagesB/inviteredpacket/redpacketdetail?scene=rplid_'+that.rplid+'-rid_' + that.rid+'-pid_' + pid;
          that.loaded({title:that.shareTitle,pic:that.sharePic,desc:that.shareDesc,link:that.shareLink});
        } else {
          if (res.msg) {
            app.alert(res.msg, function() {
              if (res.url) app.goto(res.url);
            });
          } else if (res.url) {
            app.goto(res.url);
          } else {
            app.alert('您无查看权限');
          }
        }
			});
		},
    shareClick: function (e) {
      var that = this;
    	that.sharetypevisible = true;
    },
    handleClickMask: function () {
    	this.sharetypevisible = false
    },
		sharemp:function(){
			let that = this;
			uni.setClipboardData({
				data: that.shareLink,
				success: function() {
					uni.showToast({
						title: '复制成功,快去分享吧！',
						duration: 3000,
						icon: 'none'
					});
				},
				fail: function(err) {
					uni.showToast({
						title: '复制失败',
						duration: 2000,
						icon: 'none'
					});
				}
			});
			this.sharetypevisible = false
		},
		shareapp:function(){
      // #ifdef APP-PLUS
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
						sharedata.provider= 'weixin';
						sharedata.type    = 0;
						sharedata.scene   = scene;
						sharedata.title   = '送你一张优惠券，点击领取';
						sharedata.summary = that.shareDesc;
						sharedata.href    = that.shareLink;
						sharedata.imageUrl= that.sharePic;
						
						uni.share(sharedata);
					}
		    }
		  });
      // #endif
		},
    getdjs: function () {
      var that = this;
      var djtime = that.djtime;
      if (djtime <= 0) {
        that.day   = '00';
        that.hour   = '00';
        that.minute = '00';
        that.second = '00';
        clearInterval(interval);
      } else {
        var day   = Math.floor(djtime / 86400);
        if(day>=1){
          var hour   = Math.floor((djtime - day*86400)/3600);
          var minute = Math.floor((djtime - day*86400 - hour * 3600) / 60);
          var second = djtime - day*86400 - hour * 3600 - minute * 60;
        }else{
          day = '00';
          var hour  = Math.floor(djtime / 3600);
          var minute = Math.floor((djtime - hour * 3600) / 60);
          var second = djtime - hour * 3600 - minute * 60;
        }
        that.day    = day;
        that.hour   = hour;
        that.minute = minute;
        that.second = second;
      }
    },
    takeredpacket: function () {
    	var that = this;
    	app.showLoading();
    	app.get('ApiInviteRedpacket/takeredpacket', {rplid: that.rplid,rid: that.rid}, function (res) {
    		app.showLoading(false);
        if(res.status == 1){
          app.alert(res.msg);
          setTimeout(function(){
            that.getdata();
          },800)
        } else {
          if (res.msg) {
            app.alert(res.msg, function() {
              if (res.url) app.goto(res.url);
            });
          } else if (res.url) {
            app.goto(res.url);
          } else {
            app.alert('您无查看权限');
          }
        }
    	});
    },
    changeshortcontent:function(){
      this.showshortcontent = !this.showshortcontent;
    }
  }
};
</script>
<style>
  page{width: 100%;height: 100%;}
  .content{width: 100%;height: auto;min-height: 100%;position: relative;}
  .centercontent{width:680rpx;height: 680rpx;margin: 0 auto;overflow: hidden;position: relative;}
  .daojishi{text-align: center;width: 410rpx;margin:0 auto ;display: flex;justify-content: center;align-items: center;font-size: 24rpx;}
  .daojishi0{color: #fff;display: flex;justify-content: center;}
  .daojishi1{background-color: #E5472D;text-align: center;width: 36rpx;line-height: 36rpx;border-radius: 4rpx;}
  .daojishi2{color: #E5472D;text-align: center;width: 16rpx;}
  .jindu{background-color: #ED0523;width: 450rpx;margin:0 auto ;padding:10rpx;border-radius:60rpx 60rpx;box-shadow: 10rpx 10rpx 10rpx 0rpx #845B59;color: #fff;text-align: center;margin-top: 10rpx;display: flex;align-items: center;border: 4rpx solid #FD852E;}
  .optred{display: flex;justify-content: space-evenly;width: 510rpx;margin: 0 auto;color: #D20800;text-align: center;}
  .optred1{background-color: #FFE72B;border-radius: 12rpx;width: 222rpx;height: 80rpx;font-size: 30rpx;font-weight: bold;}
  .join{width:710rpx;height:500rpx;border: 4rpx solid #FFCEA7;border-radius: 30rpx;margin: 0 auto;background-color: #fff;margin-top: 60rpx;}
  .jointitle{color: #7A4622;text-align: center;width: 180rpx;margin: 20rpx auto;font-size: 30rpx;font-weight: bold;}
  .joincontent{background-color:#FCF7EA ;padding: 10rpx;display: flex;justify-content: space-between;margin-top: 10rpx;align-items:center;border-radius: 12rpx;}
  .joinpic{width: 80rpx;height: 80rpx;border-radius: 80rpx;background-color: #f1f1f1;overflow: hidden;}
  .joinname{width:320rpx;margin-left: 20rpx;font-size: 32rpx;color:#80562F;white-space: nowrap;text-overflow: ellipsis;overflow: hidden;}
  .jointip{width: 180rpx;color: #FF593E;font-weight: bold;text-align: right;padding-right: 10rpx;}
  
  .bobaobox {
  	position: fixed;
  	top: calc(var(--window-top) + 170rpx);
  	left: 20rpx;
  	z-index: 10;
  	background: rgba(0, 0, 0, 0.6);
  	border-radius: 30rpx;
  	color: #fff;
  	padding: 0 10rpx
  }
  .bobaobox_bottom {
  	position: fixed;
  	bottom: calc(env(safe-area-inset-bottom) + 150rpx);
  	left: 0;
  	right: 0;
  	width:470rpx;
  	margin:0 auto;
  	z-index: 10;
  	background: rgba(0, 0, 0, 0.6);
  	border-radius: 30rpx;
  	color: #fff;
  	padding: 0 10rpx
  }
  @supports (bottom: env(safe-area-inset-bottom)){
  	.bobaobox_bottom {
  		position: fixed;
  		bottom: calc(env(safe-area-inset-bottom) + 150rpx);
  		left: 0;
  		right: 0;
  		width:470rpx;
  		margin:0 auto;
  		z-index: 10;
  		background: rgba(0, 0, 0, 0.6);
  		border-radius: 30rpx;
  		color: #fff;
  		padding: 0 10rpx
  	}
  }
  .shortcontent{position: absolute;top: 80px;right: 0;width: 148rpx;text-align: center;background: #fff;line-height: 50rpx;border-radius: 50rpx 0 0 50rpx;color: #A1351D;}
</style>