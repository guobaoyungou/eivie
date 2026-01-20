<template>
<view class="container">
	<block v-if="isload">
		<dd-tab :itemdata="['排队中','已完成']" :itemst="['0','1']" :st="st" :isfixed="true" @changetab="changetab"></dd-tab>
		<view style="width:100%;height:100rpx"></view>
		<view class="order-content">
			<block v-for="(item, index) in datalist" :key="index">
				<view class="order-box">	
					<view class="head">
						<view class="f1">订单号：{{item.ordernum}}</view>
						<view class="flex1"></view>
						<text  class="st0">{{item.statusLabel}}</text>
					</view>
					<view class="content" style="border-bottom:none">
						<view class="detail">
							<text class="t1">商户名称：{{item.bname}}</text>
						</view>
						<view class="detail" v-if="item.title">
							排队名称：<text class="t1" :style="'color:'+t('color1')">{{item.title}}</text>
						</view>
						<view class="detail">
							排队金额：<text class="t1" :style="'color:'+t('color1')">{{item.money}}</text>
						</view>
						<view class="detail">
							已返金额：<text class="t1" :style="'color:'+t('color1')">{{item.money_give}}</text> <text v-if="item.status==0 && set.quit_wxhb == 1 && item.money_quit_hb > 0" @tap.stop="quitWithHb" :data-money="item.money_quit_hb" :data-id="item.id" :data-index="index" :style="'color:'+t('color1')+';margin-left: 30rpx;'">退出排队抽红包</text>
						</view>
						<view class="detail" v-if="item.queue_no">
							当前排名：<text class="t1" :style="'color:'+t('color1')">{{item.queue_noLabel}}</text>
						</view>
						<view class="detail">
							<text class="t1">排队时间：{{item.createtimeFormat}}</text>
						</view>
					</view>
				</view>
			</block>
		</view>
		<nomore v-if="nomore"></nomore>
		<nodata v-if="nodata"></nodata>
	</block>
	<!-- 红包区域 -->
	<view class="page-view-hongbao" v-if="prizeVisible" @touchmove.stop.prevent="() => {}" @mousewheel.prevent>
		<!-- 三个红包 -->
		<view class="hongbao-view">
			<view class="wrap">
				<view class="envelope" @tap="receive">
					<image :src="`${pre_url}/static/img/envelope.png`" mode="aspectFill" class="cover" />
					<image :src="`${pre_url}/static/img/btn.png`" mode="aspectFill" class="btn" />
				</view>
			</view>
			<view class="wrap">
				<view class="envelope" @tap="receive">
					<image :src="`${pre_url}/static/img/envelope.png`" mode="aspectFill" class="cover" />
					<image :src="`${pre_url}/static/img/btn.png`" mode="aspectFill" class="btn" />
				</view>
			</view>
			<view class="wrap">
				<view class="envelope" @tap="receive">
					<image :src="`${pre_url}/static/img/envelope.png`" mode="aspectFill" class="cover" />
					<image :src="`${pre_url}/static/img/btn.png`" mode="aspectFill" class="btn" />
				</view>
			</view>
			<!-- 关闭弹窗按钮  -->
			<view class="hongbao-view-close" @click="hbclose">
				<image :src="pre_url+'/static/img/close2.png'"></image>
			</view>
		</view>
		<uni-popup ref="popup" @change="">
			<view class="cl-popup">
				<view class="main">
					<image :src="`${pre_url}/static/img/popup-top.png`" mode="aspectFill" class="top" />
					<image :src="`${pre_url}/static/img/popup-icon.png`" mode="aspectFill" class="icon" />
					<image :src="`${pre_url}/static/img/popup-bottom.png`" mode="aspectFill" class="bottom" />
					<view class="content">
						<view class="price">
							<text class="num">{{hbmoney}}</text>
							<text class="unit">元</text>
						</view>
						<!-- 标题 -->
						<view class="title"> {{hbtext}} </view>
						<!-- 领取按钮 -->
						<view class="cl-button">
							<text @tap="hbsuccess">确定</text>
						</view>
					</view>
				</view>
			</view>
		</uni-popup>
	</view>
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

      st: '0',
      datalist: [],
			set:{},
			hbmoney:0,
			hbtext:'奖励将发送到微信零钱或余额中',
			tempid:0,
			tempindex:0,
      pagenum: 1,
      nomore: false,
      nodata: false,
			keyword:'',
			prizeVisible: true,//是否展示红包
			pre_url:app.globalData.pre_url,
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
	onNavigationBarSearchInputConfirmed:function(e){
		this.searchConfirm({detail:{value:e.text}});
	},
  methods: {
		// 红包关闭
		hbclose(){
			this.prizeVisible = false;
		},
		hbshow(){
			this.prizeVisible = true;
		},
		hbsuccess(){
			this.prizeVisible = false;
			let datalist = this.datalist;
			let tempindex = this.tempindex;
			datalist.splice(tempindex, 1);
			this.tempindex = datalist;
		},
		// 领取红包
		receive() {
			let that = this;
			that.$refs.popup.open('center');
			// that.loading = true;
			// app.post('ApiQueueFree/quitHb', {id: that.tempid}, function (res) {
			// 	that.loading = false;
			//   if(res.status == 0){
			// 		app.alert(res.msg);
			// 		that.hbclose()
			// 		return;
			// 	}
			// 	that.$refs.popup.open('center');
			// });
		},
		quitWithHb(e) {
			let that = this;
			let tempid = e.currentTarget.dataset.id;
			let money = e.currentTarget.dataset.money;
			that.tempid = tempid;
			that.tempindex = e.currentTarget.dataset.index;
			that.hbmoney = money;
			that.hbshow();
		},
    changetab: function (st) {
			this.st = st;
      uni.pageScrollTo({
        scrollTop: 0,
        duration: 0
      });
      this.getdata();
    },
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
      app.post('ApiQueueFree/index', {status: st,pagenum: pagenum,keyword:that.keyword}, function (res) {
				that.loading = false;
        var data = res.datalist;
        if (pagenum == 1) {
					that.datalist = data;
					that.set = res.set;
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
    }
  }
};
</script>
<style>
.container{ width:100%;}
.order-content{display:flex;flex-direction:column}
.order-box{ width: 94%;margin:10rpx 3%;padding:6rpx 3%; background: #fff;border-radius:8px}
.order-box .head{ display:flex;width:100%; border-bottom: 1px #f4f4f4 solid; height: 70rpx; line-height: 70rpx; overflow: hidden; color: #999;}
.order-box .head .f1{display:flex;align-items:center;color:#333}
.order-box .head .f1 image{width:34rpx;height:34rpx;margin-right:4px}
.order-box .head .st0{ width: 140rpx; color: #ff8758; text-align: right; }
.order-box .content {line-height: 180%;}
.page-view-hongbao {width: 100%;height: 100%;position: fixed;top:0;left: 0;z-index: 999;background-color: rgba(0, 0, 0, 0.4);overscroll-behavior-y: contain !important;}
.page-view-hongbao .hongbao-view{width: 100%;margin: 266rpx auto;	display: flex;align-items: center;justify-content: space-around;position: relative;}
.hongbao-view .wrap {display: flex;justify-content: center;position: relative;width: 25%;height: 400rpx;overflow: hidden;}
.hongbao-view .wrap .cover{width: 100%;height:230rpx;}
.hongbao-view .wrap .envelope {position: relative;top: 70rpx;animation: envelope-animation 1.8s ;width: 100%;}
@keyframes envelope-animation {
	0% {
		top: 120rpx;
		transform: scaleY(1);
	}
	20% {
		top: 20rpx;
		transform: scaleY(1);
	}
	70% {
		top: 20rpx;
		transform: scaleY(1);
	}
	80% {
		top: 20rpx;
		transform: scaleY(1);
	}
	90% {
		top: 70rpx;
		transform: scaleY(0.9);
	}
	100% {
		top: 70rpx;
		transform: scaleY(1);
	}
}
.hongbao-view .hongbao-view-close{position: absolute;bottom:-188rpx;border: 2px #fff solid;width: 60rpx;height: 60rpx;border-radius: 50%;display: flex;align-items: center;justify-content: center;}
.hongbao-view .hongbao-view-close image{width: 80%;height: 80%;}
.hongbao-view .wrap .btn {position: absolute;top: 30rpx;left: calc(50% - 40rpx);width: 80rpx;height: 80rpx;animation: btn-animation 0.3s 4;animation-direction: alternate;}
@keyframes btn-animation {from {transform: scale(1);}to {	transform: scale(0.6);}}
.cl-popup {}
.cl-popup .main {position: relative;width: 580rpx;height: 770rpx;}
.cl-popup .top {position: absolute;top: 0;width: 100%;height: 560rpx;}
.cl-popup .icon {position: absolute;top: 324rpx;left: calc(50% - 87rpx);width: 174rpx;height: 178rpx;z-index: 2;}
.cl-popup .bottom {position: absolute;bottom: 0;width: 100%;height: 434rpx;}
.cl-popup .content {display: flex;flex-direction: column;align-items: center;position: absolute;top: 0;left: 0;width: 100%;height: 100%;z-index: 5;}
.cl-popup .price {margin-top: 70rpx;margin-bottom: 300rpx;}
.cl-popup .num {font-size: 122rpx;font-weight: bold;color: #fc5c43;}
.cl-popup .unit {position: relative;left: 10rpx;bottom: 10rpx;font-size: 50rpx;font-weight: 500;color: #fc5c43;}
.cl-popup .title {margin-bottom: 40rpx;font-size: 28rpx;font-weight: 400;color: #ffe0be;}
.cl-popup .cl-button {width: 316rpx;height: 78rpx;background: linear-gradient(180deg, #fff7da 0%, #f3a160 100%);box-shadow: 0 3rpx 6rpx #d12200;border-radius: 50rpx;text-align: center;line-height: 78rpx;}
.cl-popup .cl-button text {font-size: 32rpx;font-weight: bold;color: #f74d2e;}
</style>