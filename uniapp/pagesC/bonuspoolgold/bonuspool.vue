<template>
<view style="width: 100%;height: auto;min-height: 100%">
	<block >
    <view class="content" :style="'background-image:url('+bgpic+');background-repeat: no-repeat;background-size:100% 100%;'">
      
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
      
      <view v-if="set.desc" @tap="changeshortcontent" class="shortcontent">{{t('奖金池')}}规则</view>

      <view style="width: 100%;clear: both;height: 230rpx;"></view>
      <view class="centercontent" :style="'background-image:url('+centerpic+');background-repeat: no-repeat;background-size:100% 100%;'">
        <view style="color: #A1351D;text-align: center;margin-top: 34rpx;">
          <text>我的{{t('金币')}}</text>
        </view>
        <view style="text-align: center;font-weight: bold;">
          <text style="color: #FE0000;font-size: 60rpx;">{{member.gold}}</text>
        </view>
        <view style="text-align: center;color: #A1351D;margin-top: 10rpx;" v-if="set.show_bonus_pool==1">
            <text>总{{t('奖金池')}}：{{set.bonus_pool_total}}</text>
        </view>
		<view style="text-align: center;color: #A1351D;margin-top: 10rpx;" v-if="set.show_gold_price==1">
		    <text>{{t('金币')}}价格：{{set.gold_price}}</text>
		</view>
        <view style="width: 100%;position: absolute;bottom: 150rpx;left: 0;">
          <view class="optred">
            <view @tap="goto" data-url="/pagesC/bonuspoolgold/withdraw" class="optred1" :style="'background-image:url('+btnpic+');background-size:100% 100%;background-repeat: no-repeat;'">
              <view style="line-height: 72rpx;">{{set.withdraw_btn_text}}</view>
            </view>
            <view @tap="goto" :data-url="set.buy_url" class="optred1" :style="'background-image:url('+btnpic+') ;background-repeat: no-repeat;background-size:100% 100%;'">
              <view style="line-height: 72rpx;">{{set.buy_btn_text}}</view>
            </view>
          </view>
        </view>
     
      </view>
      <view class="join" >
        <view class="jointitle">{{t('金币')}}明细</view>
        <scroll-view scroll-y="true" style="width: 660rpx;margin: 0 auto;;height: 410rpx;">
          <block v-if="logs" v-for="(item,index) in logs" :key="index">
            <view class="joincontent">
              <view style="width: 80%;display: flex;align-items: center;">
                <view class="joinname" style="width: 100%;">{{item.createtime}} {{item.remark}}</view>
              </view>
              <view class="jointip" style="width: 20%;" >
                <text style="font-size: 30rpx;">{{item.value}}</text>
              </view>
            </view>
          </block>
        </scroll-view>
      </view>
      <view style="width: 100%;clear: both;height: 60rpx;"></view>
    </view>
	</block>
  
  <block v-if="showshortcontent">
    <view style="width:100%;height: 100%;background-color: #000;position: fixed;opacity: 0.5;z-index: 99;top:0"></view>
    <view style="width: 700rpx;margin: 0 auto;position: fixed;top:10%;left: 25rpx;z-index: 100;">
        <scroll-view scroll-y="true" style="background-color: #fff;border-radius: 20rpx;overflow: hidden;width: 100%;height: 900rpx;padding: 20rpx;">
          <parse :content="set.desc" ></parse>
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
		data:{},
		bgpic:'',
		centerpic:'',
		btnpic:'',
		childs:'',
		notices:'',
		showshortcontent:false,
		set:{},
		member:{},
		logs:[]
	}
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
			app.get('ApiBonusPoolGold/bonuspool', {}, function (res) {
				that.loading = false;
				if(res.status == 1){
					var set   = res.set;
					that.set = set;
					that.bgpic = set.bgpic || '';
					that.centerpic = set.centerpic || '';
					that.btnpic = set.btnpic || '';
					that.member = res.member;
					that.logs = res.logs
					if(res.notices){
						that.notices = res.notices;
					}
					uni.setNavigationBarTitle({
						title: that.t('奖金池')
					});
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
  .optred1{border-radius: 12rpx;width: 222rpx;height: 80rpx;font-size: 30rpx;font-weight: bold;}
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