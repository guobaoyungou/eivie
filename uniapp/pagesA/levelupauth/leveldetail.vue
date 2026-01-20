<template>
<view class="container">
	<block v-if="isload">
		<view class="topbg" :style="'background:url(' + pre_url + '/static/img/lv-topbg.png) no-repeat;background-size:100%'">
			<view class="topinfo" :style="'background:url(' + pre_url + '/static/img/lv-top.png);background-size:100%'">
					<view class="info">
			
						<view class="flex" style="margin-top: 10rpx;">
							<view style="margin-right: 10rpx;color: #fff;"> 会员等级：</view>
							<view class="user-level">
								<image class="level-img" :src="userlevel.icon" v-if="userlevel.icon"></image>
								<view class="level-name">{{userlevel.name}}</view>
							</view>
						</view>
						<view class="flex" style="margin-top: 20rpx;">
							<view style="margin-right: 10rpx;color: #fff;">升级费用：</view>
					
								<view class="level-name" style="color: #fff;">￥{{userlevel.apply_paymoney}}</view>
						
						</view>
					</view>
			</view>
	
		</view>
		<view style="width:100%;height:20rpx;background-color:#f6f6f6"></view>

		<view class="explain">
			<view class="f1"> — 等级特权 — </view>
			<view class="f2">
				<parse :content="userlevel.explain" />
			</view>
		</view>
		
		<view class="bottom">
			<button class="zsbtn" @tap="zhaunzeng"  :style="{background:t('color1')}"  v-if="getplatform() == 'h5' || getplatform() == 'mp' || getplatform() == 'app'"> 转赠</button>
			<button v-else class="zsbtn" open-type="share" data-type='1'  :data-levelid="userlevel.id" :style="{background:t('color1')}"> 转赠</button>
			<button class="smbtn" @tap="tosale"  :style="{background:t('color1')}"  v-if="getplatform() == 'h5' || getplatform() == 'mp' || getplatform() == 'app'"> 售卖</button>
			<button class="smbtn" open-type="share" data-type='2'  :data-levelid="userlevel.id"  v-else>
				售卖
			</button>
		</view>
		<view v-if="dialogShow" class="popup_modal" @tap="zhaunzeng">
			<view class="content1">
				<view class="title">我要转赠</view>
				<view class="f1">点击下方按钮，复制链接发送给好友</view>
				<view class="btnbox" @tap="shareScheme" v-if="getplatform() == 'wx'">
						<button class="btn2" :style="{background:t('color1')}"  >复制</button>
				</view>
				<view class="btnbox" v-else>
					<button class="btn2" :style="{background:t('color1')}" @tap="copy" :data-text="pre_url+'/h5/'+aid+'.html#/pagesA/levelupauth/give?pid='+frommid+'&levelid='+levelid" >复制</button>
				</view>
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
export default {
  data() {
    return {
			opt:{},
			loading:false,
      isload: false,
			menuindex:-1,
			
			pre_url:app.globalData.pre_url,
      userinfo: [],
      userlevel: [],
      sysset: [],
			showleveldown:false,
			salelevel_money:0,
			dialogShow:false,
			frommid:0,
			aid:1,
			sharepic:''
    };
  },
  onShareAppMessage: function (e) {
  	var that = this;
	var title = '您有一份会员等级待领取！';
	var sharepic  = that.sharepic;
	var type =   e.target.dataset.type
	if(type==1){
			var sharelink = app.globalData.pre_url +'/h5/'+app.globalData.aid+'.html#/pagesA/levelupauth/give?scene=levelid_'+that.userlevel.id+'-pid_'+that.frommid;
	}else{
		var sharelink = app.globalData.pre_url +'/h5/'+app.globalData.aid+'.html#/pagesA/levelupauth/buy?scene=levelid_'+that.userlevel.id+'-pid_'+that.frommid;
	}

	console.log({title:title,tolink:sharelink,pic:sharepic})
  	var sharedata = this._sharewx({title:title,tolink:sharelink,pic:sharepic});
  	return sharedata;
  },
  onShareTimeline:function(e){
  	var that = this;
		var title = '您有一份会员等级待领取！';
		var sharepic = that.sharepic;
		var type =   e.target.dataset.type
		if(type==1){
				var sharelink = app.globalData.pre_url +'/h5/'+app.globalData.aid+'.html#/pagesA/levelupauth/give?scene=levelid_'+that.userlevel.id+'-pid_'+that.frommid;
		}else{
			var sharelink = app.globalData.pre_url +'/h5/'+app.globalData.aid+'.html#/pagesA/levelupauth/buy?scene=id_'+that.userlevel.id+'-pid_'+that.frommid;
		}
  	var sharewxdata = this._sharewx({title:title,tolink:sharelink,pic:sharepic});
  	var query = (sharewxdata.path).split('?')[1]+'&seetype=circle';
  	var link = app.globalData.pre_url +'/h5/'+app.globalData.aid+'.html#'+(sharewxdata.path).split('?')[0]+'&seetype=circle';
  	return {
  		title: sharewxdata.title,
  		imageUrl: sharewxdata.imageUrl,
  		query: query,
  		link:link
  	}
  },
  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.sharepic = this.pre_url+'/static/img/levelupauth.jpg';
		this.getdata();
  },
	onPullDownRefresh: function () {
		this.getdata();
	},
  methods: {
		getdata: function () {
			var that = this;
			that.loading = true;
			app.post('ApiLevelupAuth/levelinfo', {id:that.opt.id}, function (res) {
				that.loading = false;
				if (res.userinfo) {
					that.userlevel = res.userlevel;
					that.userinfo = res.userinfo;
					that.showleveldown = res.showleveldown
					that.salelevel_money = res.salelevel_money
					that.frommid = res.userinfo.id
					that.aid = app.globalData.aid
					that.show = true;
				}
				that.loaded();
			});
		},
		shareScheme: function (e) {
			var that = this;
			var levelid = that.userlevel.id
			app.showLoading();
			app.post('ApiLevelupAuth/getwxScheme', {levelid: levelid}, function (data) {
				app.showLoading(false);
				if (data.status == 0) {
					app.alert(data.msg);
				} else {
						that.showScheme = true;
						that.schemeurl=data.openlink
				}
			});
		},
		zhaunzeng:function(e){
			var that=this
			var levelprice =  that.userlevel.apply_paymoney
			if(that.salelevel_money<levelprice){
				app.alert('额度不足');return
			}
			this.dialogShow = !this.dialogShow
			that.levelid =  that.userlevel.id
		},
		tosale:function(e){
			var that = this;
			
			var levelprice =  that.userlevel.apply_paymoney
			if(that.salelevel_money<levelprice){
				app.alert('额度不足');return
			}
			var levelid =  that.userlevel.id
			var platform = app.getplatform()
			var frommid = that.frommid
			if(platform == 'mp' || platform == 'h5'){
				var sharelink = app.globalData.pre_url +'/h5/'+app.globalData.aid+'.html#/pagesA/levelupauth/buy?scene=pid_'+frommid+'-levelid_'+levelid;
				this._sharemp({title:"您有一份会员升级待查收，请尽快处理~",link:sharelink,pic:that.sharepic})
				app.error('点击右上角发送给好友或分享到朋友圈');
			}else if(platform == 'app'){
				uni.showActionSheet({
					itemList: ['发送给微信好友', '分享到微信朋友圈'],
					success: function (res){
						if(res.tapIndex >= 0){
							var scene = 'WXSceneSession';
							if (res.tapIndex == 1) {
								scene = 'WXSenceTimeline';
							}
							var sharedata = {};
							sharedata.provider = 'weixin';
							sharedata.type = 0;
							sharedata.scene = scene;
							sharedata.title = '您的好友向给您发送了一个会员等级';
							sharedata.summary = '您有一份会员等级待领取，请尽快处理~';
							sharedata.href = app.globalData.pre_url +'/h5/'+app.globalData.aid+'.html#/pagesA/levelupauth/buy?scene=pid_'+that.payorder.frommid+'-levelid_'+levelid;
							sharedata.imageUrl = '';
							uni.share(sharedata);
						}
					}
				});
			}else{
				app.error('该终端不支持此操作');
			}
		},
  }
};
</script>
<style>
page{background:#fff}
.topbg{width:100%;display:flex;flex-direction:column;align-items:center;padding-bottom:30rpx}
.topinfo{margin-top:70rpx;width:670rpx;height:270rpx;padding:60rpx 50rpx;display:flex;justify-content:center;position:relative}
.topinfo .headimg{width:120rpx;height:120rpx;border-radius:50%;}
.topinfo .info{display:flex;flex:auto;flex-direction:column;padding-left:20rpx;height:120rpx;}
.topinfo .info .nickname{font-size:36rpx;font-weight:bold;color:#fff;margin-bottom:10rpx}
.topinfo .info .endtime{color:#fff;font-size:24rpx;margin-top:20rpx}
.topinfo .set{position:absolute;top:30rpx;right:40rpx;width:70rpx;height:70rpx;line-height:70rpx;font-size:50rpx;text-align:center;color:#fff}

.topbg .upbtn{margin-top:10rpx;width:660rpx;height:110rpx;line-height:90rpx;text-align:center;color:#fff;font-size:32rpx;}

.user-level{color:#b48b36;background-color:#ffefd4;margin-top:4rpx;width:auto;height:36rpx;border-radius:18rpx;padding:0 20rpx;display:flex;align-items:center}
.user-level .level-img {width:32rpx;height:32rpx;margin-right:6rpx;margin-left:-14rpx;border-radius:50%}
.user-level .level-name {font-size:24rpx;}

.explain{ width:100%;margin:20rpx 0;}
.explain .f1{width:100%;text-align:center;font-size:30rpx;color:#333;font-weight:bold;height:50rpx;line-height:50rpx}
.explain .f2{padding:20rpx;background-color:#fff}

.bottom{ position: fixed;height: 100rpx;line-height: 100rpx;bottom: 0;display: flex; width: 100%;background: #fff;padding: 15rpx 30rpx;border-top: 1rpx solid #f6f6f6; }
.bottom .zsbtn{ width: 50%;text-align: center;height: 70rpx; line-height: 70rpx;margin-right: 30rpx;border-radius: 10rpx;color: #fff; }
.bottom .smbtn{ width: 50%;text-align: center; height: 70rpx;line-height: 70rpx;border-radius: 10rpx; }

.popup_modal{ position: fixed; width: 100%; height: 100%; top:0; background: rgba(0,0,0,0.5);}
.popup_modal .content1{ background: #fff; position: absolute;top:30%; left: 8%; border-radius: 10rpx; width: 80%;} 
.popup_modal .content1 .title{ height: 100rpx; text-align: center; line-height: 100rpx;  font-weight: bold; font-size: 32rpx; border-bottom: 1rpx solid #f3f3f3;}
.popup_modal .content1 .item{ display: flex;padding:0 30rpx; margin-top: 30rpx; }
.popup_modal .content1  .f1{ height: 80rpx; line-height: 80rpx;margin: 30rpx;}

.btnbox{ display: flex; margin-bottom: 50rpx;}
.btnbox .btn1{ background: #e6e6e6}
.btnbox .btn1,.btnbox .btn2{ width: 40%; color: #fff; border-radius: 30rpx;}
</style>