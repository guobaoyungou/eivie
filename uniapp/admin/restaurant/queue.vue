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
			</view>
		</view>
		
		<view class="myqueue" v-if="1">
			<view class="title"><text class="t1">当前叫号</text><!-- <text class="t2" @tap="cancel">取消排队</text> --></view>
			<view class="head">
				<view class="f1" v-if="lastQueue">{{lastQueue.queue_no}}</view>
				<view class="f2" v-else>暂无</view>
				<view class="flex" v-if="lastQueue"><button class="btn-mini" @tap="guohao" :data-id="lastQueue.id">过号</button> <button class="btn-mini" @tap="repeat" :data-id="lastQueue.id" :data-queue_no="lastQueue.queue_no">重复</button></view>
			</view>
			<view class="content">
				<view class="item" v-for="item in clist" :key="index">
					<view class="f1">
						<view class="t1">{{item.name}}</view>
						<view class="t2">{{item.seat_min}}-{{item.seat_max}}人</view>
					</view>
					<view class="f2">等待<text style="color:#FC5729;padding:0 6rpx;font-size:28rpx">{{item.waitnum}}</text>桌</view>
					<view class="f3"><button class="btn-mini" @tap="jiaohao" :data-cid="item.id" v-if="item.waitnum">下一桌</button><button class="btn-mini" disabled="true" v-else>下一桌</button></view>
				</view>
			</view>
		</view>
		
		<view class="btn" v-if="is_show_quhao" @tap="goto" :data-url="'/restaurant/admin/quhao?bid='+bid">取号排队</view>
		<!-- <view class="log" v-if="!myqueue" @tap="goto" :data-url="'record?bid='+bid">我的排队记录</view> -->
	</block>
	<loading v-if="loading"></loading>
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
			
			business:{},
      clist: [],
			myqueue:'',
			myjustqueue:'',
			lastQueue:{},
			bid:'',
			socketOpen:false,
			token:'',
			is_show_quhao:''
    };
  },

  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.bid = this.opt.bid || 0;
		
		this.getdata();
  },
	onShow:function() {
		
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
			app.get('ApiAdminRestaurantQueue/index', {}, function (res) {
				if(res.status == 0) {
					app.alert(res.msg,function(){
						app.goback();
					});
				}
				that.business = res.business
				that.clist = res.clist;
				that.lastQueue = res.lastQueue;
				that.myqueue = res.myqueue;
				that.myjustqueue = res.myjustqueue;
				that.token = res.token
				that.is_show_quhao = res.is_show_quhao;
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
					that.sendSocketMessage({type: 'restaurant_queue',token: that.token,data:{ aid:app.globalData.aid,bid:that.bid }});
					intervel = setInterval(function () {
						that.sendSocketMessage({type: 'connect',token: that.token});
					}, 25000);
					
					uni.onSocketMessage(function (res) {
						try {
							var data = JSON.parse(res.data);
							that.receiveMessage(data);
						} catch (e) {}
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
    receiveMessage: function (data) {
			console.log(data);
			if(data.type == 'restaurant_queue_add' || data.type == 'restaurant_queue_cancel' || data.type == 'restaurant_queue_callno'){
				this.getdata();
			}
    },
		jiaohao:function(e){
			var that = this;
			var cid = e.currentTarget.dataset.cid;
			var queue_no = e.currentTarget.dataset.queue_no;
			app.showLoading();
			app.get('ApiAdminRestaurantQueue/jiaohao', {cid:cid}, function (res) {
				app.showLoading(false);
				if(res.status == 0) {
					app.alert(res.msg,function(){
						that.getdata();
					});
				}
				if(res.status == 1) {
					app.success(res.msg,function(){
						//that.getdata();
					});
					that.sendSocketMessage({ type:'restaurant_queue_callno',token:that.token,data:{ call_id:res.queue.id,aid:app.globalData.aid,bid:that.bid } })
				}
			})
		},
		repeat:function(e){
			var that = this;
			var id = e.currentTarget.dataset.id;
			var queue_no = e.currentTarget.dataset.queue_no;
			app.showLoading();
			app.get('ApiAdminRestaurantQueue/jiaohao', {id:id}, function (res) {
				app.showLoading(false);
				if(res.status == 0) {
					app.alert(res.msg,function(){
						that.getdata();
					});
				}
				if(res.status == 1) {
					app.success(res.msg,function(){
						that.getdata();
					});
					that.sendSocketMessage({ type:'restaurant_queue_callno',token:that.token,data:{ call_id:res.queue.id,aid:app.globalData.aid,bid:that.bid } })
				};
			})
		},
		guohao:function(e){
			var that = this;
			var id = e.currentTarget.dataset.id;
			app.confirm('确定要过号吗?',function(){
				app.showLoading();
				app.get('ApiAdminRestaurantQueue/guohao', {id:id}, function (res) {
					app.showLoading(false);
					app.alert(res.msg,function(){
						//that.getdata();
					});
					that.sendSocketMessage({type: 'restaurant_queue_cancel',token: that.token,data:{ aid:app.globalData.aid,bid:that.bid,queue_id:id }});
				})
			})
		}
  }
};
</script>
<style>
.container{height:100%;overflow:hidden;position: relative;}

.topbannerbg{width:100%;height:280rpx;background:#fff;}
.topbannerbg2{position:absolute;z-index:7;width:100%;height:280rpx;background:rgba(0,0,0,0.7);top:0}
.topbanner{position:absolute;z-index:8;width:100%;display:flex;padding:60rpx;top:0;align-items:center}
.topbanner .left{width:100rpx;height:100rpx;flex-shrink:0;margin-right:20rpx;}
.topbanner .left .img{width:100%;height:100%;border-radius:50%}
.topbanner .right{display:flex;flex-direction:column;padding:20rpx 0}
.topbanner .right .f1{font-size:32rpx;font-weight:bold;color:#fff}
.topbanner .right .f2{font-size:22rpx;color:#fff;opacity:0.7;margin-top:20rpx;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:1;overflow:hidden;line-height:30rpx;}
.topbanner .right .f3{width:100%;display:flex;padding-right:20rpx;margin-top:10rpx}
.topbanner .right .f3 .t2{display:flex;align-items:center;font-size:24rpx;color:rgba(255,255,255,0.9)}
.topbanner .right .f3 .img{width:32rpx;height:32rpx;margin-left:10rpx}

.notice{display:flex;width:94%;margin:0 3%;height:120rpx;background: #fff;position:absolute;z-index:11;padding:0 50rpx;border-radius:10rpx;margin-top:-70rpx;}
.notice .content{display:flex;width:100%;align-items:center}
.notice .content .f1{width:40rpx;height:40rpx;margin-right:20rpx}
.notice .content .f2{flex:1;color:#FC5729;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:1;overflow:hidden;}

 .content{display:flex;flex-direction:column;margin:20rpx 0}
 .content .item{background:#FFF6F3;border-radius:10rpx;display:flex;align-items:center;width:100%;height:130rpx;margin-bottom:16rpx;padding:0 20rpx}
 .content .item .f1{display:flex;flex-direction:column;width:50%;padding-left:20rpx}
 .content .item .f1 .t1{color:#222222; font-size: 32rpx;}
 .content .item .f1 .t2{color:#999999;}
 .content .item .f2{display:flex;width:30%;align-items:center;}
 .content .item .f3{display:flex;width:20%;align-items:center;font-size:24rpx;justify-content:flex-end}

.myqueue{display:flex;flex-direction:column;width:94%;margin:0 3%;margin-top:20rpx;background: #fff;padding:20rpx;border-radius:10rpx;}
.myqueue .title{display:flex;align-items:center;justify-content:space-between}
.myqueue .title .t1{color:#161616;font-weight:bold}
.myqueue .title .t2{color:#FC5729;font-size:24rpx}
.myqueue .head{display:flex;flex-direction:column;align-items:center;width:100%;margin:40rpx 0; position: relative;}
.myqueue .head .f1{font-size:64rpx;font-weight:bold;color:#564B4B}
.myqueue .head .f2{font-size:24rpx;color:#A29E9E;margin-top:10rpx}
.myqueue .bottom{display:flex;align-items:center;width:100%;color:#564B4B;justify-content:space-between}

.btn{width:94%;margin:0 3%;margin-top:40rpx;height:90rpx;line-height:90rpx;text-align:center;background: linear-gradient(90deg, #FF7D15 0%, #FC5729 100%);color:#fff;font-size:32rpx;font-weight:bold;border-radius:10rpx}
.log{width:100%;text-align:center;margin-top:30rpx;margin-bottom:40rpx;color:#888}
.btn-mini {width: 200rpx;height: 60rpx;line-height: 60rpx;background: #E34242;border-radius: 8rpx; color: #fff;}
/* .head .btn-mini { position: absolute; right: 20rpx;width: 120rpx; bottom: 40rpx;} */
.head .btn-mini {margin: 10rpx 30rpx;}
</style>