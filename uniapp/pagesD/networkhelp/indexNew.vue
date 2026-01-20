<template>
<view class="container" v-if="isload">
	<view class="custom_field" v-if="show_wallet==1">
		<view class='item'>
			<text class='t2'>{{t('佣金')}}</text>
			<text class="t1">{{userinfo.commission}}</text>
		</view>
		<view class='item'>
			<text class='t2'>{{t('余额')}}</text>
			<text class="t1">{{userinfo.money}}</text>
		</view>
		<view class='item'>
			<text class='t2'>{{t('积分')}}</text>
			<text class="t1">{{userinfo.score}}</text>
		</view>
	</view>
	<!-- <view class="content" :style="{background:t('color1')}" style="padding-bottom: unset;">
		<view class="level-tabs">
		  <block v-for="(active,index) in active_list">
			<view class="tab-item"  :class="active_id == active.id ? 'active' : ''" @click='changeTab(active.id)'>{{active.name}}</view>
		  </block>
		</view>
	</view> -->
	  <view class="score-card" :style="{background:t('color1')}">
		  
		<block v-for="(item,index) in score_data">
			<view class="score-info-row">
				<block v-for="(item2,index2) in item">
				  <view class="score-info-item" @tap="goto" :data-url="item2.url">
					<view class="info-label">{{item2.name}}：</view>
					<view class="info-value">{{item2.value}}</view>
				  </view>
				</block>
			</view>
		</block>
	  </view>
   
  
  <view class="divider">
    <view>——</view>
    <view>——</view>
    <view>——</view>
  </view>
  <view class="content" :style="{background:t('color1')}">
	  <view class="level-tabs">
	    <block v-for="(active,index) in active_list">
	  	<view class="tab-item"  :class="active_id == active.id ? 'active' : ''" @click='changeTab(active.id)'>{{active.name}}</view>
	    </block>
	  </view>
	  <view class="user-info">
		<view class="user-count">活动人脉：{{down_members}}</view>
		<!-- <view class="user-type" @tap="goto" :data-url="'/pagesC/networkhelp/myteam?active_id='+active_id">点击查看</view> -->
	  </view>
	  
	  <view class="level-list">
		<!-- 创客 -->
		<view class="level-item" v-for="(item,index2) in level_list">
		  <view class="level-left">
			<view class="level-line" :style="{background:t('color1')}"></view>
			<view class="level-content">
			  <view class="level-header">
				<view class="level-name">{{item.name}}</view>
			  </view>
			</view>
		  </view>
		  <view class="level-right" @tap="goto" :data-url="'/pagesD/networkhelp/netteam?active_id='+active_id+'&net_path_level='+item.level">
			<view class="level-action"> >> 查看人脉</view>
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
	  userinfo:{},
	  level_list:[],
	  down_members:0,
	  score_data:[],
	  active_id:0,
	  active_list:[],
	  show_wallet:0
    }
  },
  onLoad: function(opt) {
    this.opt = app.getopts(opt);
	this.active_id = this.opt.active_id || 0;
    this.getdata();
  },
  onPullDownRefresh: function() {
    this.getdata();
  },
  methods: {
    getdata: function() {
      var that = this;
      that.loading = true;
      app.post('ApiNetworkHelp/index_new', {active_id:that.active_id}, function (res) {
			that.loading = false;
			if(!res.status){
				app.error(res.msg);
				return;
			}
			that.userinfo = res.userinfo;
			that.down_members = res.down_members;
			that.score_data = res.score_data;
			that.level_list = res.level_list;
			that.active_list = res.active_list;
			that.active_id = res.active_id;
			that.show_wallet = res.show_wallet || 0;
			uni.setNavigationBarTitle({
				title: '数据统计'
			});
			that.loaded();
      });
    },
	changeTab:function(active_id){
		this.active_id = active_id;
		this.getdata();
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
  width: 100%;
  background-color: #fff;
  border-radius: 10rpx;
  padding: 20rpx 20rpx;
  margin: 0 10rpx;
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
  display: flex;
  flex-direction: column;
  align-items: center;
  background-color: #f6f6f6;
  /* margin: 20rpx 0; */
  /* padding: 20rpx 0; */
  padding-bottom: 20rpx;
}

.divider view {
  /* margin: 5rpx 0; */
  height: 10rpx;
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

.custom_field{display:flex;width:100%;align-items:center;padding:16rpx 8rpx;background:#fff;}
.custom_field .item{flex:1;display:flex;flex-direction:column;justify-content:center;align-items:center;font-weight: bold;margin-top: 10rpx;}
</style>