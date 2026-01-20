<template>
<view class="container" v-if="isload">
  <view class="score-card" :style="{background:t('color1')}">
    <view class="score-title">{{t('互助积分')}}</view>
    <view class="score-value">{{member_help_score}}</view>
    
    <view class="score-info-row">
      <view class="score-info-item" @tap="goto" data-url="/pagesC/networkhelp/helpscorelog">
        <view class="info-label">累计{{t('互助积分')}}：</view>
        <view class="info-value">{{total_bonus.help_score_total}}</view>
      </view>
      <view class="score-info-item" @tap="goto" data-url="/activity/commission/commissionlog">
        <view class="info-label">{{t('佣金')}}：</view>
        <view class="info-value">{{total_bonus.commission_total}}</view>
      </view>
    </view>
    
    <view class="score-info-row">
      <view class="score-info-item" @tap="goto" data-url="/pagesExt/money/moneylog">
        <view class="info-label">{{t('余额')}}：</view>
        <view class="info-value">{{total_bonus.money_total}}</view>
      </view>
      <view class="score-info-item" @tap="goto" data-url="/pagesExt/my/scorelog">
        <view class="info-label">{{t('积分')}}：</view>
        <view class="info-value">{{total_bonus.score_total}}</view>
      </view>
    </view>
  </view>
  
  <view class="divider"></view>
  <view class="content" :style="{background:t('color1')}">
	  <view class="level-tabs">
		  <block v-for="(active,index) in active_list">
			<view class="tab-item"  :class="active_id == active.id ? 'active' : ''" @click='changeTab(active.id)'>{{active.name}}</view>
		  </block>
	  </view>
	  
	  <view class="user-info">
		<view class="user-count">活动人脉：{{down_members}}</view>
		<view class="user-type" @tap="goto" :data-url="'/pagesC/networkhelp/myteam?active_id='+active_id">点击查看</view>
	  </view>
	  
	  <view class="level-list">
		<!-- 创客 -->
		<view class="level-item" v-for="(item,index2) in level_list">
		  <view class="level-left">
			<view class="level-line" :style="{background:t('color1')}"></view>
			<view class="level-content">
			  <view class="level-header">
				<view class="level-name">{{item.name}}</view>
				<view :style="{background:t('color1')}" class="level-tag creator" v-if="item.is_up==1">已激活</view>
				<view class="level-tag no-creator" v-if="item.is_up==0">未激活</view>
			  </view>
			  <view class="level-condition">达标条件：{{item.up_con}}</view>
			</view>
		  </view>
		  <view class="level-right" @tap="goto" data-url="/activity/commission/poster">
			<view class="level-action"> >> 邀请好友</view>
		  </view>
		</view>
	  </view>
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
      opt: {},
      loading: false,
      isload: false,
	  member_help_score:0,
      total_bonus: {},
      active_list: [],
	  active_id:0,
	  level_list:[],
	  down_members:0
    }
  },
  onLoad: function(opt) {
    this.opt = app.getopts(opt);
    this.getdata();
  },
  onPullDownRefresh: function() {
    this.getdata();
  },
  methods: {
    getdata: function() {
      var that = this;
      that.loading = true;
      app.post('ApiNetworkHelp/index', {}, function (res) {
			that.loading = false;
			if(!res.status){
				app.error(res.msg);
				return;
			}
			var data = res.data;
			that.member_help_score = data.member_help_score;
			that.total_bonus = data.total_bonus;
			that.active_list = data.active_list;
			that.active_id = data.active_id;
			that.getlevels();
			uni.setNavigationBarTitle({
				title: '数据统计'
			});
			that.loaded();
      });
    },
	getlevels: function() {
	  var that = this;
	  var active_id = that.active_id;
	  that.loading = true;
	  app.post('ApiNetworkHelp/active_detail', {active_id:active_id}, function (res) {
			that.loading = false;
			if(!res.status){
				app.error(res.msg);
				return;
			}
			var data = res.data;
			that.level_list = data.level_list;
			that.down_members = data.down_members;
			that.loaded();
	  });
	},
	changeTab:function(active_id){
		this.active_id = active_id;
		this.getlevels();
	}
  }
};
</script>

<style>
page {
  background-color: #f6f6f6;
}

.container {
  padding-bottom: 100rpx;
}

/* 积分卡片样式 */
.score-card {
  background-color: #ff4444;
  color: #fff;
  border-radius: 20rpx;
  padding: 30rpx;
  margin: 20rpx;
}

.score-title {
  text-align: center;
  font-size: 32rpx;
  margin-bottom: 10rpx;
}

.score-value {
  text-align: center;
  font-size: 60rpx;
  font-weight: bold;
  margin-bottom: 20rpx;
}

.score-info-row {
  display: flex;
  justify-content: space-between;
  margin-top: 10rpx;
}

.score-info-item {
  display: flex;
  width: 48%;
  background-color: #fff;
  border-radius: 10rpx;
  padding: 20rpx 20rpx;
}
.content{    
	width: 100;
    padding: 20rpx;
    height: auto;
    border-radius: 20rpx;
    background: #ff4444;
    margin: 15rpx;
    height: 100%;
	padding-bottom: 100rpx;
}
.info-label {
  font-size: 24rpx;
  color: black;
}

.info-value {
  font-size: 24rpx;
  flex: 1;
  text-align: right;
  color: red;
}

/* 分隔线 */
.divider {
  height: 20rpx;
  background-color: #f6f6f6;
  margin: 20rpx 0;
}

/* 标签页样式 */
.level-tabs {
  display: flex;
  padding: 20rpx;
 
  color: #fff;
}

.tab-item {
  flex: 1;
  text-align: center;
  font-size: 28rpx;
  padding: 10rpx 0;
  position: relative;
}

.tab-item.active {
  font-weight: bold;
}

.tab-item.active::after {
  content: "";
  position: absolute;
  bottom: 0;
  left: 50%;
  transform: translateX(-50%);
  width: 40rpx;
  height: 4rpx;
  background-color: #fff;
  border-radius: 2rpx;
}

/* 用户信息 */
.user-info {
  display: flex;
  justify-content: flex-start;
  padding: 20rpx;
  background: #fff;
  margin-top: 20rpx;
  border-radius: 20rpx;
}

.user-count {
  font-size: 28rpx;
  color: #333;
}

.user-type {
  font-size: 28rpx;
  color: #ff3300;
  margin-left: 20rpx;
  border-bottom: 1px solid #ff3300;
}

/* 等级列表 */
.level-list {
  /* background-color: #fff; */
  /* padding: 0 20rpx; */
}

.level-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 30rpx 15rpx;
  border-bottom: 1rpx solid #eee;
  margin-top: 20rpx;
  border-radius: 20rpx;
  background: #fff;
}

.level-left {
  display: flex;
  flex-direction: row;
  align-items: center;
  position: relative;
}

.level-line {
  width: 6rpx;
  height: 80rpx;
  margin-right: 15rpx;
  border-radius: 3rpx;
}

.level-tag {
  display: inline-block;
  padding: 4rpx 12rpx;
  border-radius: 10rpx;
  font-size: 18rpx;
  color: #fff;
  margin-left: 10rpx;
  width: fit-content;
}

.creator {
  background-color: #ff9900;
}
.no-creator{
	border: 1px solid red;
	color: red;
}

.one-star {
  background-color: #ff6600;
}

.two-star {
  background-color: #ff3300;
}

.three-star {
  background-color: #ff0000;
}

.four-star {
  background-color: #cc0000;
}

.five-star {
  background-color: #990000;
}

.six-star {
  background-color: #660000;
}
.level-name {
  font-size: 30rpx;
  font-weight: bold;
  color: #333;
}

.level-content {
  display: flex;
  flex-direction: column;
  flex: 1;
}

.level-header {
  display: flex;
  flex-direction: row;
  align-items: center;
}

.level-condition {
  font-size: 26rpx;
  color: #666;
  margin-top: 10rpx;
}

.level-right {
  display: flex;
  align-items: center;
}

.level-action {
  font-size: 26rpx;
  color: #999;
}
</style>