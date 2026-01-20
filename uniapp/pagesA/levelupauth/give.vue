<template>
<view class="container">
	<block v-if="isload">
		<view class="topbg" :style="'background:url(' + pre_url + '/static/img/lv-topbg.png) no-repeat;background-size:100%'">
			<view class="topinfo" :style="'background:url(' + pre_url + '/static/img/lv-top.png);background-size:100%'">
				<view class="info">
					<view class="nickname">{{frommember.nickname}}</view>
					<view style="color: #fff;">赠送了一个会员等级给您</view>
					<view class="flex" style="margin-top: 10rpx;">
						<view style="margin-right: 10rpx;color: #fff;"> 赠送等级：</view>
						<view class="user-level">
							<image class="level-img" :src="zslevel.icon" v-if="zslevel.icon"></image>
							<view class="level-name">{{zslevel.name}}</view>
						</view>
					</view>
					<view class="t3">您的当前会员等级：{{levelname}}</view>
				</view>
				<view class="set" @tap="goto" data-url="set"><text class="fa fa-cog"></text></view>
			</view>
			<view class="upbtn" :style="'background:url(' + pre_url + '/static/img/lv-upbtn.png) no-repeat;background-size:100%'" @tap="confirmlq" >我要领取</view>
		</view>
		<view style="width:100%;height:20rpx;background-color:#f6f6f6"></view>

		<view class="explain">
			<view class="f1"> — 等级特权 — </view>
			<view class="f2">
				<parse :content="zslevel.explain" />
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
			frommember:[],
			levelname:'',
			zslevel:[],
    };
  },
  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.getdata();
  },
	onPullDownRefresh: function () {
		this.getdata();
	},
  methods: {
		getdata: function () {
			var that = this;
			that.loading = true;
			app.get('ApiLevelupAuth/givelevel', {pid:that.opt.pid,levelid:that.opt.levelid}, function (res) {
				that.loading = false;
				if(res.status==1){
						that.frommember = res.frommember;
						that.levelname = res.levelname;
						that.zslevel = res.zslevel
						that.loaded();
				}else{
					app.error(res.msg);
					return;
				}
			});
		},
		confirmlq:function(){
			var that=this;
			that.loading = true;
			app.post('ApiLevelupAuth/givelevel', {pid:that.opt.pid,levelid:that.opt.levelid}, function (res) {
				that.loading = false;
				if(res.status==1){
					app.success(res.msg)
					setTimeout(function () {
							that.gotourl(res.tourl,'reLaunch');
					}, 1000);
				}else{
					app.error(res.msg);
					return;
				}
			});
		},
		gotourl:function(tourl, opentype){
			var that = this;
			if(app.globalData.platform == 'mp' || app.globalData.platform == 'h5'){
				if (tourl.indexOf('miniProgram::') === 0) {
					//其他小程序
					tourl = tourl.slice(13);
					var tourlArr = tourl.split('|');
					console.log(tourlArr)
					that.showOpenWeapp();
					return;
				}
			}
			app.goto(tourl, opentype);
		},
  }
};
</script>
<style>
page{background:#fff}
.topbg{width:100%;display:flex;flex-direction:column;align-items:center;padding-bottom:30rpx}
.topinfo{margin-top:70rpx;width:670rpx;height:270rpx;padding:30rpx 50rpx;display:flex;justify-content:center;position:relative}
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

.t3{ color: #fff; margin-top: 10rpx;}
</style>