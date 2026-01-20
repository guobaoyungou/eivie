<template>
<view class="container">
	<block v-if="isload">
		<view class="topbg" :style="'background:url(' + pre_url + '/static/img/lv-topbg.png) no-repeat;background-size:100%'">
			<view class="topinfo" :style="'background:url(' + pre_url + '/static/img/lv-top.png);background-size:100% 320rpx;'">
				<view class="topuserinfo">
					<image :src="userinfo.headimg" background-size="cover" class="headimg"></image>
					<view class="info">
						<view class="nickname">{{userinfo.nickname}}</view>
						<view class="flex">
							<view class="user-level" v-if="userlevel">
								<image class="level-img" v-if="userlevel.icon" :src="userlevel.icon"></image>
								<view class="level-name">{{userlevel.name}}</view>
							</view>
						</view>
						<view class="downlevel" style="margin-top: 10rpx;color: #fff;font-size: 24rpx;" v-if="showleveldown">
							<view v-if="userinfo.isauto_down==1">已降级，需购买{{userinfo.buyproname}}才可恢复等级</view>
							<view v-else>还差{{userinfo.leveldowncommission}}佣金，降为{{userinfo.leveldownname}}</view>
						</view>
						<view class="endtime" v-if="userinfo.levelendtime > 0">到期时间：{{dateFormat(userinfo.levelendtime,'Y年m月d日')}}</view>
						<view class="endtime" v-if="userlevel && userlevel.areafenhong == 1 && userinfo.province">代理区域：{{userinfo.province}}</view>
						<view class="endtime" v-if="userlevel && userlevel.areafenhong == 2 && userinfo.province && userinfo.city">代理区域：{{userinfo.province}},{{userinfo.city}}</view>
						<view class="endtime" v-if="userlevel && userlevel.areafenhong == 3 && userinfo.province && userinfo.city && userinfo.area">代理区域：{{userinfo.province}},{{userinfo.city}},{{userinfo.area}}</view>
						<view class="endtime" v-if="userlevel && userlevel.areafenhong == 10 && userinfo.largearea">代理区域：{{userinfo.largearea}}</view>
					</view>
					<view class="set" @tap="goto" data-url="set"><text class="fa fa-cog"></text></view>
				</view>
				
				<view class="progressinfo" v-if="hasnext && showprogress">
					<view class="t1">
						<view class="n1">{{nextlevel.now_team_yeji}}</view>距{{nextlevel.name}}还需{{up_yeji}}
					</view>
					<view class="t2">
						<view class="tname">{{t('团队业绩')}}</view>
						<progress class="pinfo" :percent="progress" border-radius="3" activeColor="#fff" backgroundColor="#FFD1C9" active="true"></progress>
					</view>
				</view>
			</view>
			<view class="upbtn" :style="'background:url(' + pre_url + '/static/img/lv-upbtn.png) no-repeat;background-size:100%'" @tap="goto" :data-url="opt.id ? 'levelup?id='+opt.id : 'levelup'">我要升级</view>
		</view>
		<view style="width:100%;height:20rpx;background-color:#f6f6f6"></view>
		<view class="explain">
			<view class="f1"> — 等级特权 — </view>
			<view class="f2" v-if="userlevel">
				<parse :content="userlevel.explain" />
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
      userlevel: {},
      sysset: [],
			showleveldown:false,
			progress: 0,
			hasnext:0,
			showprogress:0,
			nextlevel: [],
			up_yeji:0
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
			app.post('ApiMy/levelinfo', {id:that.opt.id}, function (res) {
				that.loading = false;
				if (res.userinfo) {
					that.userinfo = res.userinfo;
					that.userlevel = res.userlevel ?? {};
					that.showleveldown = res.showleveldown
					that.show = true;
				}
				if(res.nextlevel){
					that.nextlevel = res.nextlevel;
					that.progress = res.nextlevel.progress;
					that.up_yeji = Math.ceil(res.nextlevel.up_team_yeji-res.nextlevel.now_team_yeji);
					if(that.up_yeji < 0){
						that.up_yeji = 0
					}
				}
				that.hasnext = res.hasnext;
				that.showprogress = res.showprogress;
				that.loaded();
			});
		},
  }
};
</script>
<style>
page{background:#fff}
.topbg{width:100%;display:flex;flex-direction:column;align-items:center;padding-bottom:30rpx}
.topinfo{margin-top:70rpx;width:670rpx;height:320rpx;padding:60rpx 50rpx;display:flex;justify-content:center;position:relative;flex-direction: column;}
.topinfo .topuserinfo{display: flex;}
.topinfo .headimg{width:120rpx;height:120rpx;border-radius:50%;}
.topinfo .info{display:flex;flex:auto;flex-direction:column;padding-left:20rpx;min-height:120rpx;justify-content: center;}
.topinfo .info .nickname{font-size:36rpx;font-weight:bold;color:#fff;margin-bottom:10rpx}
.topinfo .info .endtime{color:#fff;font-size:24rpx;margin-top:10rpx}
.topinfo .set{position:absolute;top:30rpx;right:40rpx;width:70rpx;height:70rpx;line-height:70rpx;font-size:50rpx;text-align:center;color:#fff}

.topbg .upbtn{margin-top:10rpx;width:660rpx;height:110rpx;line-height:90rpx;text-align:center;color:#fff;font-size:32rpx;}

.user-level{color:#b48b36;background-color:#ffefd4;margin-top:4rpx;width:auto;height:36rpx;border-radius:18rpx;padding:0 20rpx;display:flex;align-items:center}
.user-level .level-img {width:32rpx;height:32rpx;margin-right:6rpx;margin-left:-14rpx;border-radius:50%}
.user-level .level-name {font-size:24rpx;}

.explain{ width:100%;margin:20rpx 0;}
.explain .f1{width:100%;text-align:center;font-size:30rpx;color:#333;font-weight:bold;height:50rpx;line-height:50rpx}
.explain .f2{padding:20rpx;background-color:#fff}

.container .progressinfo{width:100%;display:flex;width:100%;flex-direction: column;}
.container .progressinfo .t1{text-align:left;color:#fff;font-size: 24rpx;}
.container .progressinfo .t1 .n1{display:inline-block;font-size: 38rpx;margin-right:10rpx;}
.container .progressinfo .t2{display:flex;align-items:center;}
.container .progressinfo .tname{font-size:24rpx;color:#fff;max-width: 30%; white-space: nowrap;overflow: hidden;text-overflow: ellipsis;margin-right: 10rpx;}
.container .progressinfo .pinfo{width: 70%;height: 12rpx;}

</style>