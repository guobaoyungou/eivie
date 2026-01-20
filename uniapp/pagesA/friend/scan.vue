<template>
<view class="container">
	<block v-if="isload">
		<view class="box top">
			<view class="left"><image :src="friend.headimg"></image></view>
			<view class="right">
				<view class="nickname">{{friend.nickname}}</view>
				<view class="desc">ID：{{friend.fmid}}</view>
				<view class="desc">等级：{{friend.levelname}}</view>
				<view class="desc" v-if="friend.area">地区：{{friend.area}}</view>
				<view class="desc" v-if="friend.id>0">添加时间：{{friend.createtime}}</view>
			</view>
		</view>
		<view class="box">
			<view class="row flex-sb">
				<view class="flex-s f1">
					<view class="label">备注：</view>
					<view class="value">
						<input v-model="remark" type="text" v-if="editRemark || friend.id==0">
						<text v-else>{{friend.remark}}</text>
					</view>
				</view>
				<view class="btn-r" v-if="friend.id>0 && !editRemark" @tap="showRemarkInput">改备注</view>
				<view class="btn btn2" v-if="friend.id>0 && editRemark" @tap="changeRemark" :style="'background:'+t('color1')+'; color:#fff'" >确定</view>
			</view>
		</view>
		<view class="box">
			<view class="row">
				<view class="label">来 源：</view>
				<view class="value">{{friend.from}}</view>
			</view>
		</view>
		<view class="box option">
			<view class="btn btn1" v-if="friend.id>0" @tap="delFriend">删除好友</view>
			<view class="btn" v-if="friend.id==0 && btnauth.addFriend" :style="'color:'+t('color1')+'; border-bottom:1px solid '+t('color1')" @tap="addFriend"  :data-id="friend.fmid">添加好友</view>
		</view>
	</block>
	<loading v-if="loading"></loading>
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
			pre_url:app.globalData.pre_url,
      isload: false,
			friend:{},
			fmid:0,
			editRemark:false,
			remark:'',
			btnauth:{}
    };
  },
  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.fmid = this.opt.fmid || 0;
		this.getdata();
  },
	onPullDownRefresh: function () {
		this.editRemark = false;
		this.getdata();
	},
  methods: {
		getdata:function(){
			var that = this;0
			that.loading = true
			app.get('ApiFriend/scan', {fmid:that.fmid}, function (res) {
				that.loading = false;
				if(res.status==1){
					that.friend = res.friend
					that.remark = res.friend.remark
					that.btnauth = res.btnauth
					that.loaded()
				}else{
					app.alert(res.msg)
				}
			})
		},
		addFriend:function(e){
			var that = this;
			var id = e.currentTarget.dataset.id;
			app.showLoading('添加中');
			app.post('ApiFriend/addFriend', {id:id,from:'扫一扫',remark:that.remark}, function (res) {
				app.showLoading(false);
				if(res.status==1){
					app.success(res.msg);
					setTimeout(function () {
					  app.goto('index')
					}, 1000);
				}else{
					app.error(res.msg);
				}
			})
		},
		showRemarkInput:function(){
			this.editRemark = true;
		},
		changeRemark:function(){
			var that = this;
			if(that.friend.id==0){
				return;
			}
			app.showLoading('提交中');
			app.post('ApiFriend/editFriendRemark', {id:that.friend.id,remark:that.remark}, function (res) {
				app.showLoading(false);
				if(res.status==1){
					that.editRemark = false;
					that.friend.remark = that.remark;
					app.success(res.msg);
					// setTimeout(function () {
					//   that.getdata()
					// }, 1000);
				}else{
					app.error(res.msg);
				}
			})
		},
		delFriend:function(e){
			var that = this
			app.showLoading('删除中')
			app.post('ApiFriend/delFriend', {fmid:that.friend.fmid}, function (res) {
				app.showLoading(false);
				if(res.status==1){
					app.success(res.msg);
					setTimeout(function () {
					  app.goto('index','reLaunch')
					}, 1000);
				}else{
					app.error(res.msg);
				}
			})
		}
  }
};
</script>
<style>
.container{ width:100%;}
.flex-sb{display: flex;justify-content: space-between;align-items: center;}
.flex-s{display: flex;align-items: center;}
.f1{flex: 1;}
.box{background: #fff;padding: 20rpx 30rpx;margin-bottom: 14rpx;}
.top{display: flex;padding: 50rpx 30rpx;align-items: center;}
.top .left image{width: 150rpx;height: 150rpx;border-radius: 16rpx;}
.top .right{margin-left: 20rpx;}
.top .desc{color: #999;font-size: 24rpx;}
.top .nickname{font-weight: bold;font-size: 30rpx;}
.row{display: flex;align-items: center;}
.row .label{flex-shrink: 0; width: 140rpx;}
.row .value{margin-left: 10rpx;color: #666;flex: 1;}
.row .value input{border: 1rpx solid #f0f0f0;font-size: 24rpx;padding: 0 10rpx;width: 100%;height: 56rpx;line-height: 56rpx;border-radius: 6rpx;}
.row .btn-r{width: 120rpx;flex-shrink: 0;text-align: center; font-size: 24rpx;border: 1rpx solid #e5e5e5; border-radius: 6rpx;padding:0 10rpx;height: 56rpx;line-height: 56rpx;margin-left: 8rpx;color: #646464;}
.option{display: flex;justify-content: center;}
.option .btn{text-align: center;margin: 0 10rpx;}
.option .btn1{color: #ff3c00;border-bottom: 1px solid #ff3c00;}
.option .btn0{color: #666;}
.btn2{padding: 6rpx 20rpx;border-radius: 6rpx;margin-left: 6rpx;}
</style>