<template>
<view class="container">
	<block v-if="isload">
		<view class="topbannerbg" :style="business.pic?'background:url('+business.pic+') 100%':''"></view>
		<view class="topbannerbg2"></view>
		<view class="topbanner">
			<view class="left"><image class="img" :src="business.logo"/></view>
			<view class="right">
				<view class="f1">{{business.name}}</view>
				<view class="f2">{{business.desc}}</view>
				<!-- <view class="f3"><view class="flex1"></view><view class="t2">收藏<image class="img" src="/static/img/like1.png"/></view></view> -->
			</view>
		</view>
		<view class="notice">
			<view class="content">
				<image :src="pre_url+'/static/img/queue-notice.png'" class="f1"/>
				<view class="f2">{{notice}}</view>
			</view>
		</view>
		<view class="form">
			<view class="form-item">
				<text class="label">您的姓名</text>
				<input type="text" class="input" placeholder="请输入姓名" name="linkman" :value="linkman" placeholder-style="color:#BBBBBB;font-size:28rpx" @input="inputLinkman"/>
			</view>
			<view class="form-item">
				<text class="label">手机号</text>
				<input type="text" class="input" placeholder="请输入手机号" name="tel" :value="tel" placeholder-style="color:#BBBBBB;font-size:28rpx" @input="inputTel"/>
			</view>
			<view class="form-item">
				<text class="label">用餐人数</text>
				<view class="f2" @tap="showRenshuSelect" :style="renshu==''?'color:#BBBBBB;font-size:28rpx':''">{{renshu!='' ? renshu+'人' : '请选择人数'}}<text class="iconfont iconjiantou" style="color:#999;font-weight:normal;font-size:28rpx"></text></view>
			</view>
			<view class="form-item">
				<text class="label">排队队列</text>
				<view class="f2" @tap="showCategorySelect" :style="cname==''?'color:#BBBBBB;font-size:28rpx':''">{{cname!='' ? cname : '请选择队列'}}<text class="iconfont iconjiantou" style="color:#999;font-weight:normal;font-size:28rpx"></text></view>
			</view>
		</view>
		<view class="btn" @tap="confirmQuhao">确定取号</view>

		<view v-if="renshuvisible" class="popup__container">
			<view class="popup__overlay" @tap.stop="handleClickMask"></view>
			<view class="popup__modal">
				<view class="popup__title">
					<text class="popup__title-text">请选择用餐人数</text>
					<image :src="pre_url+'/static/img/close.png'" class="popup__close" style="width:36rpx;height:36rpx" @tap.stop="handleClickMask"/>
				</view>
				<view class="popup__content">
					<view class="cuxiao-desc">
						<view class="cuxiao-item" @tap="changeRenshu" :data-id="item" v-for="item in [1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20]" :key="index">
							<view class="type-name"><text style="color:#333">{{item}}人</text></view>
							<view class="radio" :style="renshuCache==item ? 'background:'+t('color1')+';border:0' : ''"><image class="radio-img" :src="pre_url+'/static/img/checkd.png'"/></view>
						</view>
					</view>
					
					<view style="width:100%; height:120rpx;"></view>
					<view style="width:100%;position:absolute;bottom:0;padding:20rpx 5%;background:#fff">
						<view style="width:100%;height:80rpx;line-height:80rpx;border-radius:40rpx;text-align:center;color:#fff;" :style="{background:t('color1')}" @tap="chooseRenshu">确 定</view>
					</view>
				</view>
			</view>
		</view>
		<view v-if="categoryvisible" class="popup__container">
			<view class="popup__overlay" @tap.stop="handleClickMask"></view>
			<view class="popup__modal">
				<view class="popup__title">
					<text class="popup__title-text">请选择队列</text>
					<image :src="pre_url+'/static/img/close.png'" class="popup__close" style="width:36rpx;height:36rpx" @tap.stop="handleClickMask"/>
				</view>
				<view class="popup__content">
					<view class="cuxiao-desc">
						<view class="cuxiao-item" @tap="changeCategory" :data-id="item.id" v-for="item in clist" :key="index">
							<view class="type-name"><text style="color:#333">{{item.name}}</text></view>
							<view class="radio" :style="cidCache==item.id ? 'background:'+t('color1')+';border:0' : ''"><image class="radio-img" :src="pre_url+'/static/img/checkd.png'"/></view>
						</view>
					</view>
					
					<view style="width:100%; height:120rpx;"></view>
					<view style="width:100%;position:absolute;bottom:0;padding:20rpx 5%;background:#fff">
						<view style="width:100%;height:80rpx;line-height:80rpx;border-radius:40rpx;text-align:center;color:#fff;" :style="{background:t('color1')}" @tap="chooseCategory">确 定</view>
					</view>
				</view>
			</view>
		</view>

	</block>
	<loading v-if="loading"></loading>
	<dp-tabbar :opt="opt" @getmenuindex="getmenuindex"></dp-tabbar>
</view>
</template>

<script>
var app = getApp();
var intervel;
var socketMsgQueue = [];
export default {
  data() {
    return {
			opt:{},
			loading:false,
      isload: false,
			menuindex:-1,
			pre_url:app.globalData.pre_url,
			
			business:{},
      clist: [],
			notice:'',

			linkman:'',
			tel:'',
			renshuvisible:false,
			renshuCache:'',
			renshu:'',
			categoryvisible:false,
			cid:0,
			cidCache:0,
			cname:'',
			socketOpen:false,
			token:'',
			is_to_record:''
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
    clearInterval(intervel);
    if(this.socketOpen) uni.closeSocket();
  },
  methods: {
		getdata: function () {
			var that = this;
			that.loading = true;
			app.get('ApiRestaurantQueue/quhao', {bid:that.opt.bid}, function (res) {
				that.loading = false;
				if(res.status==0){
					app.alert(res.msg,function(){
						app.goback(true);
					});
					return;
				}
				that.business = res.business
				that.linkman = res.linkman
				that.tel = res.tel
				that.clist = res.clist;
				that.notice = res.notice;
				that.token = res.token;
				that.is_to_record = res.is_to_record;
				that.loaded();

				if(!that.socketOpen){
					var pre_url = app.globalData.pre_url;
					var wssurl = pre_url.replace('https://', "wss://") + '/wss';
					uni.closeSocket();
					uni.connectSocket({
						url: wssurl
					});
					uni.onSocketOpen(function (res) {
						console.log(res)
						that.socketOpen = true;
						for (var i = 0; i < socketMsgQueue.length; i++) {
							that.sendSocketMessage(socketMsgQueue[i]);
						}
						socketMsgQueue = [];
					});
				}
			});
		},
		sendSocketMessage:function(msg) {
			if (this.socketOpen) {
				console.log(msg)
				uni.sendSocketMessage({
					data: JSON.stringify(msg)
				});
			} else {
				console.log('111')
				socketMsgQueue.push(msg);
			}
		},
		confirmQuhao:function(){
			var that = this;
			var linkman = this.linkman
			var tel = this.tel
			var renshu = this.renshu;
			var cid = this.cid;
			if(that.is_to_record ==1){
				if(!linkman){
					app.alert('请输入您的姓名');return;
				}
				if(!tel){
					app.alert('请输入您的手机号');return;
				}
			}
			
			if(!renshu){
				app.alert('请选择用餐人数');return;
			}
			if(!cid){
				app.alert('请选择队列');return;
			}
			app.showLoading('提交中');
			app.post('ApiRestaurantQueue/quhao',{bid:that.opt.bid,linkman:linkman,tel:tel,renshu:renshu,cid:cid},function(res){
				app.showLoading(false);
				if(res.status==0){
					app.alert(res.msg);
				}else{
					that.sendSocketMessage({type: 'restaurant_queue_add',token: that.token,data:{ aid:app.globalData.aid,bid:that.opt.bid }});
					app.alert(res.msg,function(){
						app.goto('index?bid='+that.opt.bid,'reLaunch');
					});
				}
			})
		},
    inputLinkman: function (e) {
      this.linkman = e.detail.value;
    },
    inputTel: function (e) {
      this.tel = e.detail.value;
    },
		showRenshuSelect:function(e){
			this.renshuvisible = true;
		},
		changeRenshu: function (e) {
      var that = this;
      that.renshuCache = e.currentTarget.dataset.id;
		},
    chooseRenshu: function () {
      var that = this;
			that.renshu = that.renshuCache
      this.renshuvisible = false;
		},
		showCategorySelect:function(e){
			this.categoryvisible = true;
		},
		changeCategory: function (e) {
      var that = this;
      that.cidCache = e.currentTarget.dataset.id;
		},
    chooseCategory: function () {
      var that = this;
			that.cid = that.cidCache;
			var clist = that.clist;
			for(var i in clist){
				if(clist[i].id == that.cid){
					that.cname = clist[i].name;
				}
			}
      this.categoryvisible = false;
		},
    handleClickMask: function () {
      this.categoryvisible = false;
      this.renshuvisible = false;
    },
  }
};
</script>
<style>
page {position: relative;width: 100%;height: 100%;}
.container{height:100%;overflow:hidden;position: relative;}

.topbannerbg{width:100%;height:280rpx;background:#fff;}
.topbannerbg2{position:absolute;z-index:7;width:100%;height:280rpx;background:rgba(0,0,0,0.7);top:0}
.topbanner{position:absolute;z-index:8;width:100%;display:flex;padding:60rpx;top:0;align-items:center}
.topbanner .left{width:100rpx;height:100rpx;flex-shrink:0;margin-right:20rpx;display:none}
.topbanner .left .img{width:100%;height:100%;border-radius:50%}
.topbanner .right{display:flex;flex-direction:column;padding:20rpx 0}
.topbanner .right .f1{font-size:32rpx;font-weight:bold;color:#fff}
.topbanner .right .f2{font-size:22rpx;color:#fff;opacity:0.7;margin-top:20rpx;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:1;overflow:hidden;line-height:30rpx;}
.topbanner .right .f3{width:100%;display:flex;padding-right:20rpx;margin-top:10rpx}
.topbanner .right .f3 .t2{display:flex;align-items:center;font-size:24rpx;color:rgba(255,255,255,0.9)}
.topbanner .right .f3 .img{width:32rpx;height:32rpx;margin-left:10rpx}

.notice{display:flex;width:94%;margin:0 3%;height:120rpx;background: #fff;position:absolute;z-index:9;padding:0 50rpx;border-radius:10rpx;margin-top:-70rpx;}
.notice .content{display:flex;width:100%;align-items:center}
.notice .content .f1{width:40rpx;height:40rpx;margin-right:20rpx}
.notice .content .f2{flex:1;color:#FC5729;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:1;overflow:hidden;}

.form{ width:94%;margin:0 3%;border-radius:5px;padding:20rpx 20rpx;padding: 0 3%;background: #FFF;margin-top:70rpx}
.form-item{display:flex;align-items:center;width:100%;border-bottom: 1px #ededed solid;height:98rpx;line-height:98rpx;font-size:30rpx}
.form-item:last-child{border:0}
.form-item .label{color: #000;width:200rpx;}
.form-item .input{flex:1;color: #000;text-align:right}
.form-item .f2{flex:1;color: #000;text-align:right}
.form-item .picker{height: 60rpx;line-height:60rpx;margin-left: 0;flex:1;color: #000;}

.btn{width:94%;margin:0 3%;margin-top:40rpx;height:90rpx;line-height:90rpx;text-align:center;background: linear-gradient(90deg, #FF7D15 0%, #FC5729 100%);color:#fff;font-size:32rpx;font-weight:bold;border-radius:10rpx}

.cuxiao-desc{width:100%}
.cuxiao-item{display: flex;padding:0 40rpx 20rpx 40rpx;}
.cuxiao-item .type-name{font-size:28rpx; color: #49aa34;margin-bottom: 10rpx;flex:1}
.cuxiao-item .radio{flex-shrink:0;width: 32rpx;height: 32rpx;background: #FFFFFF;border: 2rpx solid #BFBFBF;border-radius: 50%;margin-right:30rpx}
.cuxiao-item .radio .radio-img{width:100%;height:100%}
</style>