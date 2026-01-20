<template>
<view class="container">
	<block v-if="isload">
		<view class="wrap" style=" height: 100%;" :style="{background:'linear-gradient(-90deg,'+t('color1')+' ,rgba('+t('color1rgb')+',0.8) 100%)'}">
			<view class="title" :style="'background-image:url(' + info.banner + ');background-size:100% 100%;'"></view>
			
			<view class="content">
				<view class="f1">
					<view class="font-big" v-if="!todayRecord">--</view>
					<view class="font-big" v-if="todayRecord">{{todayRecord.money}}</view>
					<view class="font-normal">今日补贴</view>
					<view class="rule" @tap="changeRule" :style="{background:'linear-gradient(-90deg,rgba('+t('color1rgb')+',0.2) ,rgba('+t('color1rgb')+',0.2) 100%)',color:t('color2')}">活动规则</view>
				</view>
				<view class="flex">
					<view class="col-3">
						<view class="font-mid">{{data.total}}</view>
						<view class="font-normal">补贴总额</view>
					</view>
					<view class="col-3">
						<view class="font-mid">{{data.todayNum}}</view>
						<view class="font-normal">今日数量</view>
					</view>
					<view class="col-3">
						<view class="font-mid">{{data.todayLeftNum}}</view>
						<view class="font-normal">今日剩余</view>
					</view>
				</view>
				<view class="flex">
					<view class="card" @tap="goto" data-url="eduLog">
						<view class="font-mid">{{member.hongbao_everyday_edu}}</view>
						<view class="font-normal">我的额度</view>
					</view>
					<view class="card" @tap="goto" data-url="log">
						<view class="font-mid">{{member.hongbao_count}}</view>
						<view class="font-normal">补贴总数</view>
					</view>
				</view>
				<view>
					<button class="form-btn" v-if="!todayRecord" @tap="getHongbao" :style="{background:'linear-gradient(90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'}">领今日额度</button>
					<button class="form-btn" v-if="todayRecord" :style="{background:'#aaa'}">明日再来</button>
				</view>
				<view @tap="goto" data-url="log">补贴记录</view>
			</view>
			
			<view class="prolist">
				<dp-product-item :data="tjdatalist" @addcart="addcart" :menuindex="menuindex"></dp-product-item>
			</view>


			<view id="mask-rule" v-if="showmaskrule">
				<view class="box-hongbao" v-if="showBoxhongbao" :style="{background:'url('+pre_url+'/static/img/hongbao_bg.png'+') no-repeat',backgroundSize:'contain'}">
					<view class="text-center h1">· 恭喜您获得额度 ·</view>
					<view class="font-big"><text style="font-size: 34rpx; margin-right: 10rpx;">¥</text>{{hongbaoMoney}}</view>
					<view></view>
					<button class="box-btn" @tap="hideBox" :style="{background:'linear-gradient(90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'}">确定</button>
				</view>
				
				<view class="box-rule" v-if="showBoxrule" :style="{background:'linear-gradient(180deg,'+t('color1')+' 40%,rgba(255,255,255,0.8) 120%)'}">
					<view class="h2">活动规则说明</view>
					<view id="close-rule" @tap="changeRule" :style="'background-image:url('+pre_url+'/static/img/dzp/close.png);background-size:100%'"></view>
					<view class="con">
						<view class="text">
							<text decode="true" space="true">{{info.guize}}</text>
						</view>
					</view>
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
var windowWidth = uni.getSystemInfoSync().windowWidth;

export default {
  data() {
    return {
		opt:{},
		loading:false,
		isload: false,
		menuindex:-1,
		pre_url:app.globalData.pre_url,
		isStart: 1,
		name: "",
		error: "",
		info:{},
		member:{},
		pagenum: 1,
		showmaskrule: false,
		showBoxrule:false,
		showBoxhongbao:false,
		latitude: "",
		longitude: "",
		windowWidth:0,
		windowHeight:0,
		tjdatalist:[],
		data:{},
		todayRecord:{},
		hongbaoMoney:0
    };
  },
	onLoad:function(opt){
		this.opt = app.getopts(opt);
	},
  onReady: function () {
		var that = this;
		var res = uni.getSystemInfoSync();
		that.windowWidth = res.windowWidth;
		that.windowHeight = res.windowHeight;
		this.getdata();
  },
	onShow: function (opt) {
		this.opt = app.getopts(opt);
		this.getdata(false);
	},
	onPullDownRefresh: function () {
		this.getdata(false);
	},
	onReachBottom: function () {
	  if (!this.nodata && !this.nomore) {
	    this.pagenum = this.pagenum + 1;
	    this.getProlist(true);
	  }
	},
  methods: {
		
		getdata: function (loadmore) {
			var that = this;
			that.loading = true;
			app.get('ApiHongbaoEveryday/index', {}, function (res) {
				that.loading = false;
				if(res.status == 0){
					app.alert(res.msg);
					return;
				}
				that.info = res.info;
				that.member = res.member;
				that.remaindaytimes = res.remaindaytimes;
				that.remaintimes = res.remaintimes;
				that.zjlist = res.zjlist;
				that.data = res.data;
				that.todayRecord = res.todayRecord;
				uni.setNavigationBarTitle({
					title: res.info.name
				});
				var title = that.info.name;
				that.loaded();
				that.getProlist(loadmore);
				
			});
		},
		getProlist:function(loadmore){
			var that = this;
			if(!loadmore){
				this.pagenum = 1;
				this.tjdatalist = [];
			}
      var pagenum = that.pagenum;
			that.loading = true;
			app.get('ApiHongbaoEveryday/prolist', {pagenum:pagenum}, function (res) {
				that.loading = false;
				var data = res.tjdatalist;
				if (pagenum == 1) {
					that.tjdatalist = data;
				  if (data.length == 0) {
				    that.nodata = true;
				  }
				}else{
				  if (data.length == 0) {
				    that.nomore = true;
				  } else {
				    var tjdatalist = that.tjdatalist;
				    var newdata = tjdatalist.concat(data);
				    that.tjdatalist = newdata;
				  }
				}
			});
		},
		
		getHongbao:function(){
			var that = this;
			that.loading = true;
			app.get('ApiHongbaoEveryday/getHongbao', {}, function (res) {
				that.loading = false;
				if(res.status == 0){
					app.alert(res.msg);
					return;
				}
				that.hongbaoMoney = res.money;
				that.changeBoxHongbao();
			});
		},
		sharecallback:function(){
			
		},
		changeRule: function () {
      this.showmaskrule = !this.showmaskrule;
			this.showBoxrule = !this.showBoxrule;
    },
		changeBoxHongbao: function () {
		  this.showmaskrule = !this.showmaskrule;
			this.showBoxhongbao = !this.showBoxhongbao;
		},
		hideBox:function(){
			  this.showmaskrule = false;
				this.showBoxhongbao = false;
				this.getdata();
		},
    init: function () {
			var windowWidth = this.windowWidth;
			var windowHeight = this.windowHeight;
			var that = this;
			var width = windowWidth/750*600;
			var height = windowWidth/750*320;
			console.log(height)

    },
  }
};
</script>
<style>
.text-center { text-align: center;}
.container {padding-bottom: 40rpx;}
.wrap {width:100%;height:100%;padding-top: 20rpx;}
.f1 {position: relative;}
.rule{width:60rpx;height:auto;padding:14rpx;font-size:28rpx;line-height:30rpx;text-align: center;border-radius:17rpx 0px 0px 17rpx; position: absolute; right: 0; top: -10rpx;}
.title {width:94%;height:316rpx;margin: auto;}
.content { text-align: center; background-color: #fff; border-radius: 20rpx; width: 94%; margin: 0 auto; margin-top: 40rpx; padding: 40rpx 0 20rpx;}
.content>view {line-height: 200%; margin: 40rpx 0;}
.col-3 {width: 33.33%;}
.card { width: 50%; padding: 4%; margin: 0 2%; border-radius: 20rpx;background: linear-gradient(90deg, #FFDDC3 0%, #FEF2DA 100%);}
.form-btn { width: 90%; border-radius: 40rpx; font-size: 32rpx; color: #fff; line-height:86rpx; margin-top: 80rpx;margin-left: auto;margin-right: auto;}
.font-normal { color: #999;}
.font-mid { font-size: 42rpx;}
.font-big { font-size: 64rpx;}
.font-big,.font-mid  { font-weight: bold; color: #FD462A;}

.box-hongbao {position: relative;margin: 30% auto;width: 100%;height: 70%;border-radius: 16rpx;}
.box-hongbao .h1 {color: #EB8A30; font-size: 32rpx; position: relative; top: 20%;}
.box-hongbao .font-big { position: relative; top: 26%; text-align: center;}
.box-btn { position:absolute;  top:55%; width: 40%; left: 30%; border-radius: 30rpx;color: #fff; font-size: 32rpx; box-shadow:0px 5px 15px 5px rgb(51 51 51 / 30%);}

.prolist{width: 100%;height:auto;padding: 8rpx 20rpx;}

/*规则弹窗*/
#mask-rule,#mask {position: fixed;left: 0;top: 0;z-index: 999;width: 100%;height: 100%;background-color: rgba(0, 0, 0, 0.6);}
#mask-rule .box-rule {position: relative;margin: 30% auto;padding-top: 40rpx;width: 90%;height: 70%;border-radius: 16rpx;}
#mask-rule .box-rule .star {position: absolute;left: 50%;top: -100rpx;margin-left: -130rpx;width: 259rpx;height:87rpx;}
#mask-rule .box-rule .h2 {width: 100%;text-align: center;line-height: 34rpx;font-size: 34rpx;font-weight: normal;color: #fff;}
#mask-rule #close-rule {position: absolute;right: 34rpx;top: 38rpx;width: 40rpx;height: 40rpx;}
/*内容盒子*/
#mask-rule .con {overflow: auto;position: relative;margin: 40rpx auto;padding: 15rpx;width:92%;height: 88%;line-height: 48rpx;font-size: 26rpx; border-radius: 16rpx;
color: #9E7E7E; background-color: #FEFBF2;}
#mask-rule .con .text {/* position: absolute;top: 0;left: 0; width: inherit;*/height: auto;}
@media  screen and (min-width: 400px) {
	.title {height:336rpx;}
}

</style>