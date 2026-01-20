<template>
<view>
	<block v-if="isload">
		<view class="banner" :style="{background:'linear-gradient(180deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0) 100%)'}">
			<image :src="userinfo.headimg" background-size="cover"/>
			<view class="info">
				 <text class="nickname">{{userinfo.nickname}}</text>
				 <text>推荐人：{{userinfo.pid > 0 ? userinfo.pnickname : '无'}}</text>
			</view>
		</view>
		<view class="contentdata">
			<view class="order">
				<view class="head">
					<text class="f1">我的{{t('佣金')}}</text>
					<view class="f2" @tap="goto" data-url="withdraw" v-if="comwithdraw==1"><text>立即提现</text><image src="/static/img/arrowright.png"></image></view>
					<view class="f2" @tap="tomoney" v-else-if="commission2money=='1'"><text>转到{{t('余额')}}账户</text><image src="/static/img/arrowright.png"></image></view>
				</view>
				<view class="content">
					 <view class="item" @tap="goto" data-url="../order/shoporder?st=0">
							<text class="t1">￥{{userinfo.commission}}</text>
							<text class="t3">{{comwithdraw==1?'可提现':'剩余'}}{{t('佣金')}}</text>
					 </view>
					 <view class="item" @tap="goto" data-url="../order/shoporder?st=0">
							<text class="t1">￥{{count3}}</text>
							<text class="t3">已提现{{t('佣金')}}</text>
					 </view>
					 <view class="item" @tap="goto" data-url="../order/shoporder?st=0">
							<text class="t1">￥{{userinfo.commission_yj}}</text>
							<text class="t3">在路上</text>
					 </view>
				</view>
			</view>
			<view class="order" v-if="hasfenhong">
				<view class="head">
					<text class="f1">{{t('股东分红')}}</text>
					<view class="f2" @tap="goto" data-url="fenhong"><text>查看详情</text><image src="/static/img/arrowright.png"></image></view>
				</view>
				<view class="content">
					 <view class="item">
							<text class="t1">￥{{userinfo.fenhong}}</text>
							<text class="t3">累计获得</text>
					 </view>
					 <view class="item">
							<text class="t1">￥{{userinfo.fenhong_yj}}</text>
							<text class="t3">在路上</text>
					 </view>
					 <view class="item"></view>
				</view>
			</view>
			<view class="order" v-if="hasteamfenhong">
				<view class="head">
					<text class="f1">{{t('团队分红')}}</text>
					<view class="f2" @tap="goto" data-url="teamfenhong"><text>查看详情</text><image src="/static/img/arrowright.png"></image></view>
				</view>
				<view class="content">
					 <view class="item">
							<text class="t1">￥{{userinfo.teamfenhong}}</text>
							<text class="t3">累计获得</text>
					 </view>
					 <view class="item">
							<text class="t1">￥{{userinfo.teamfenhong_yj}}</text>
							<text class="t3">在路上</text>
					 </view>
					 <view class="item"></view>
				</view>
			</view>
			<view class="order" v-if="hasareafenhong">
				<view class="head">
					<text class="f1">{{t('区域代理分红')}}</text>
					<view class="f2" @tap="goto" data-url="areafenhong"><text>查看详情</text><image src="/static/img/arrowright.png"></image></view>
				</view>
				<view class="content">
					 <view class="item">
							<text class="t1">￥{{userinfo.areafenhong}}</text>
							<text class="t3">累计获得</text>
					 </view>
					 <view class="item">
							<text class="t1">￥{{userinfo.areafenhong_yj}}</text>
							<text class="t3">在路上</text>
					 </view>
					 <view class="item"></view>
				</view>
			</view>
			<view class="order" v-if="hasteamfenhong && (teamnum_show==1 || teamyeji_show==1)">
				<view class="head">
					<text class="f1">{{t('我的团队')}}</text>
					<view class="f2" @tap="goto" data-url="myteam"><text>查看详情</text><image src="/static/img/arrowright.png"></image></view>
				</view>
				<view class="content">
					 <view class="item" v-if="teamnum_show==1">
							<text class="t1">{{userinfo.teamnum}}</text>
							<text class="t3">团队总人数</text>
					 </view>
					 <view class="item">
					 		<block v-if="teamyeji_show==1">
							<text class="t1">￥{{userinfo.teamyeji}}</text>
							<text class="t3">团队总业绩</text>
							</block>
					 </view>
					 <view class="item">
							<block v-if="gongxianfenhong_show==1">
							<text class="t1">￥{{userinfo.gongxianfenhong}}</text>
							<text class="t3">预计{{userinfo.gongxianfenhong_txt || '股东贡献量分红'}}</text>
							</block>
					 </view>
				</view>
			</view>

			<view class="list">
				<view class="item" @tap="tomoney" v-if="comwithdraw==1 && commission2money=='1'">
					<view class="f2">{{t('佣金')}}转{{t('余额')}}</view>
					<text class="f3"></text>
					<image src="/static/img/arrowright.png" class="f4"></image>
				</view>
				<view class="item" @tap="goto" data-url="myteam">
					<view class="f2">{{t('我的团队')}}</view>
					<text class="f3"></text>
					<image src="/static/img/arrowright.png" class="f4"></image>
				</view>
				<view class="item" @tap="goto" data-url="downorder">
					<view class="f2">{{t('分销订单')}}</view>
					<text class="f3"></text>
					<image src="/static/img/arrowright.png" class="f4"></image>
				</view>
				<view class="item" @tap="goto" data-url="commissionlog">
					<view class="f2">{{t('佣金')}}明细</view>
					<text class="f3"></text>
					<image src="/static/img/arrowright.png" class="f4"></image>
				</view>
				<view class="item" @tap="goto" data-url="commissionrecord">
					<view class="f2">{{t('佣金')}}记录</view>
					<text class="f3"></text>
					<image src="/static/img/arrowright.png" class="f4"></image>
				</view>
				<view class="item" @tap="goto" data-url="poster">
					<view class="f2">分享海报</view>
					<text class="f3"></text>
					<image src="/static/img/arrowright.png" class="f4"></image>
				</view>
				<view class="item" @tap="goto" data-url="fhorder" v-if="showfenhong">
					<view class="f2">分红订单</view>
					<text class="f3"></text>
					<image src="/static/img/arrowright.png" class="f4"></image>
				</view>
				<view class="item" @tap="goto" data-url="fhlog" v-if="showfenhong">
					<view class="f2">分红记录</view>
					<text class="f3"></text>
					<image src="/static/img/arrowright.png" class="f4"></image>
				</view>
				<view class="item" @tap="goto" data-url="orderMendian" v-if="showMendianOrder">
					<view class="f2">服务订单</view>
					<text class="f3"></text>
					<image src="/static/img/arrowright.png" class="f4"></image>
				</view>
				<view class="item" @tap="goto" data-url="commissionlogMendian" v-if="showMendianOrder">
					<view class="f2">服务佣金</view>
					<text class="f3"></text>
					<image src="/static/img/arrowright.png" class="f4"></image>
				</view>
				<view class="item" @tap="goto" data-url="orderYeji" v-if="showYeji">
					<view class="f2">业绩统计</view>
					<text class="f3"></text>
					<image src="/static/img/arrowright.png" class="f4"></image>
				</view>
				<view class="item" @tap="goto" data-url="/pagesExt/agent/cardEdit" v-if="set && set.agent_card == 1">
					<view class="f2">代理卡片</view>
					<text class="f3"></text>
					<image src="/static/img/arrowright.png" class="f4"></image>
				</view>
			</view>
		</view>
		<view style="width:100%;height:20rpx"></view>
		
		<uni-popup id="dialogInput" ref="dialogInput" type="dialog">
			<uni-popup-dialog mode="input" :title="t('佣金') + '转' + t('余额')" value="" placeholder="请输入转入金额" @confirm="tomonenyconfirm"></uni-popup-dialog>
		</uni-popup>
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
			
      hiddenmodalput: true,
      userinfo: [],
      count: 0,
      count1: 0,
      count2: 0,
      count3: 0,
      count4: 0,
      comwithdraw: 0,
      canwithdraw: true,
      money: 0,
      count0: "",
      countdqr: "",
      commission2money: "",
			showfenhong:false,
			showMendianOrder:false,
			hasfenhong:false,
			hasareafenhong:false,
			hasteamfenhong:false,
			showYeji:false,
			fxjiesuantime:0,
			teamyeji_show:0,
			teamnum_show:0,
			gongxianfenhong_show:0,
			set:{}
    };
  },

  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		var that = this;
		this.getdata();
  },
	onPullDownRefresh: function () {
		this.getdata();
	},
  methods: {
		getdata: function () {
			var that = this;
			that.loading = true;
			app.get('ApiAgent/commissionSurvey', {}, function (res) {
				that.loading = false;
				uni.setNavigationBarTitle({
					title: '我的' + that.t('佣金')
				});
				that.userinfo = res.userinfo;
				that.set = res.set;
				that.count = res.count;
				that.count1 = res.count1;
				that.count2 = res.count2;
				that.count3 = res.count3;
				that.count0 = res.count0;
				that.countdqr = res.countdqr;
				that.comwithdraw = res.comwithdraw;
				that.commission2money = res.commission2money;
				that.showfenhong = res.showfenhong;
				that.showMendianOrder = res.showMendianOrder;
				that.hasfenhong = res.hasfenhong;
				that.hasareafenhong = res.hasareafenhong;
				that.hasteamfenhong = res.hasteamfenhong;
				that.showYeji = res.hasYeji;
				that.fxjiesuantime = res.fxjiesuantime;
				that.teamyeji_show = res.teamyeji_show;
				that.teamnum_show = res.teamnum_show;
				that.gongxianfenhong_show = res.gongxianfenhong_show;
				that.loaded();
			});
		},
    cancel: function () {
      this.hiddenmodalput = true;
    },
    tomoney: function () {
      this.$refs.dialogInput.open()
    },
    tomonenyconfirm: function (done, val) {
			console.log(val)
      var that = this;
      var money = val;
      if (money == '' || parseFloat(money) <= 0) {
        app.alert('请输入转入金额');
        return;
      }
      if (parseFloat(money) > this.userinfo.commission) {
        app.alert('可转入' + that.t('佣金') + '不足');
        return;
      }
			done();
			app.showLoading('提交中');
      app.post('ApiAgent/commission2money', {money: money}, function (data) {
				app.showLoading(false);
        if (data.status == 0) {
          app.error(data.msg);
        } else {
          that.hiddenmodalput = true;
          app.success(data.msg);
          setTimeout(function () {
            that.getdata();
          }, 1000);
        }
      });
    }
  }
};
</script>
<style>
.banner{ display:flex;width:100%;height:560rpx;padding:40rpx 32rpx;color:#fff;position:relative}
.banner image{ width:120rpx;height:120rpx;border-radius:50%;margin-right:20rpx}
.banner .info{display:flex;flex:auto;flex-direction:column;padding-top:10rpx}
.banner .info .nickname{font-size:32rpx;font-weight:bold;padding-bottom:12rpx}
.banner .set{ width:70rpx;height:100rpx;line-height:100rpx;font-size:40rpx;text-align:center}
.banner .set image{width:50rpx;height:50rpx;border-radius:0}

.contentdata{display:flex;flex-direction:column;width:100%;padding:0 30rpx;margin-top:-380rpx;position:relative;margin-bottom:20rpx}

.order{width:100%;background:#fff;padding:0 20rpx;margin-top:20rpx;border-radius:16rpx}
.order .head{ display:flex;align-items:center;width:100%;padding:10rpx 0;border-bottom:0px solid #eee}
.order .head .f1{flex:auto;color:#333}
.order .head .f2{ display:flex;align-items:center;color:#FE2B2E;width:200rpx;padding:10rpx 0;text-align:right;justify-content:flex-end}
.order .head .f2 image{ width:30rpx;height:30rpx;}
.order .head .t3{ width: 40rpx; height: 40rpx;}
.order .content{ display:flex;width:100%;padding:10rpx 0;align-items:center;font-size:24rpx}
.order .content .item{padding:10rpx 0;flex:1;display:flex;flex-direction:column;align-items:center;position:relative}
.order .content .item image{ width:50rpx;height:50rpx}
.order .content .item .t1{color:#FE2B2E;font-size:36rpx;font-weight:bold;}
.order .content .item .t3{ padding-top:3px;color:#666}
.order .content .item .t2{background: red;color: #fff;border-radius:50%;padding: 0 10rpx;position: absolute;top: 0px;right:40rpx;width:34rpx;height:34rpx;text-align:center;}

.list{ width: 100%;background: #fff;margin-top:20rpx;padding:0 20rpx;font-size:30rpx;border-radius:16rpx}
.list .item{ height:100rpx;display:flex;align-items:center;border-bottom:0px solid #eee}
.list .item:last-child{border-bottom:0;}
.list .f1{width:50rpx;height:50rpx;line-height:50rpx;display:flex;align-items:center;}
.list .f1 image{ width:40rpx;height:40rpx;}
.list .f1 span{ width:40rpx;height:40rpx;font-size:40rpx}
.list .f2{color:#222}
.list .f3{ color: #FC5648;text-align:right;flex:1;}
.list .f4{ width: 24rpx; height: 24rpx;}
</style>