<template>
<view>
	<block v-if="isload">
		<view class="wrap" :style="{background:t('color1')}">
			<view class="top flex" >
				<view class="f1"><text class="t1">{{tkdata.ishg==1?'合格':'不合格'}}</text>
				<text class="t2">{{tkdata.ishg==1?'恭喜您，已通过考试':'请认真学习后再来试一下吧~'}} </text></view>
				<view class="score"><text class="t1">{{tkdata.score}}</text><text class="t2">分</text></view>
			</view>
			<view class="content">
				<view class="c_1 flex">
					<view class="f2"><text class="t1">{{tkdata.rightnum}}</text><text class="t2">答对题目</text></view>
					<view class="f2"><text class="t1">{{tkdata.errornum}}</text><text class="t2">答错题目</text></view>
					
				</view>
				<view class="c_2">
					<view class="list1"><text class="t3">考试时间</text><text class="t4">{{tkdata.time}}</text></view>
					<view class="list1"> <text class="t3">交卷时间</text><text class="t4">{{tkdata.endtime}}</text></view>
					<view class="list1"><text class="t3">答题用时</text><text class="t4">{{tkdata.longtime}}</text></view>
				</view>
        <block v-if="tkdata.ishg==1">
          <view  class="aginbtn" :style="{background:t('color1')}" @tap="goto" :data-url="'recordlog?kcid='+tkdata.kcid+'&mlid='+tkdata.mlid">答题记录</view>
          <view  class="bottom flex" >
            <view class="btn2" v-if="tkdata.mlid>0" :style="'width:100%'" @tap="goto" :data-url="'mldetail?kcid='+tkdata.kcid+'&id='+tkdata.mlid" :data-opentype="redirect"> 继续学习</view>
            <view class="btn3" v-if="tkdata.errornum>0" :style="'margin-left:0;width:100%'" @tap="goto" :data-url="'error?rid='+tkdata.id">错题回顾</view>
          </view>
        </block>
        
        <block v-else>
          <view   class="aginbtn" :style="{background:t('color1')}" @tap="goto" :data-url="'tiku?id='+tkdata.kcid+'&mlid='+tkdata.mlid">再答一次</view>
          <view  class="bottom flex" >
            <view class="btn2" :style="'width:100%'" @tap="goto" :data-url="'product?id='+tkdata.kcid"> 继续学习</view>
            <view class="btn3" v-if="tkdata.errornum>0" :style="'width:100%'" @tap="goto" :data-url="'error?rid='+tkdata.id">错题回顾</view>
          </view>
        </block>
			</view>
			<view style="height: 130rpx;"></view>
		</view>
	</block>
	<loading v-if="loading"></loading>
	<dp-tabbar :opt="opt" @getmenuindex="getmenuindex"></dp-tabbar>
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
			pre_url:app.globalData.pre_url,
			tkdata:[]
		};
	},
	  onLoad: function (opt) {
			this.opt = app.getopts(opt);
			this.getdata();
	  },
	onUnload: function () {
		clearInterval(interval);
	},
	methods: {
		getdata:function(){
			var that = this;
			var rid = this.opt.rid || 0;
			that.loading = true;
			app.post('ApiKecheng/complete', {rid:rid}, function (res){
				that.loading = false;
				if (res.status == 0) {
					//app.alert(res.msg);
					return;
				}
				that.tkdata = res.data
				that.loaded();		
			})
		},

	}

};
</script>
<style>
.wrap{ background: #fff; padding: 30rpx; height: auto;}
.top{ margin-top: 60rpx; padding: 50rpx; justify-content: space-between;}
.top .f1 .t1{ display: block; color: #fff; font-size: 44rpx;}
.top .f1 .t2{ font-size: 26rpx;  color:rgba(255,255,255,0.8); margin-top: 30rpx;display: block;}
.content{ background: #fff; padding: 80rpx; border-radius:20rpx ;}
.content .c_1{ justify-content: space-between; padding: 0 80rpx;}
.content .c_1 .f2 text{ display:block }
.content .c_1 .f2 .t1{  font-size: 64rpx; font-weight: bold; color: #222; text-align: center;}
.content .c_1 .f2 .t2{ color: #93949E; font-size: 24rpx;  margin-top: 20rpx;}
.content .list1{ line-height: 60rpx;}
.content .list1 .t3{ font-size: 26rpx; color: #222;}
.content .list1 .t4{ display: inline-block; margin-left: 40rpx;color: #93949E;}
.content .c_2{ margin-top: 50rpx;} 
.aginbtn{ margin-top: 80rpx; text-align: center;color: #fff; border-radius:40rpx ; height: 88rpx; line-height: 88rpx; font-size: 32rpx;}
.top .score{ width: 160rpx; height: 160rpx;}
.bottom .btn2{ background-color: rgba(255,0,0,0.3); width: 240rpx; color:#FF5347 ; margin-top: 50rpx; height: 88rpx; text-align: center; 
font-weight: bold; font-size: 32rpx;
line-height: 88rpx;border-radius: 40rpx;}
.bottom .btn3{ background-color:#EEEEEE; width: 240rpx; color:#222 ; margin-top: 50rpx; height: 88rpx; text-align: center; 
font-weight: bold; font-size: 32rpx;
line-height: 88rpx;border-radius: 40rpx; margin-left: 30rpx;}
.score .t1{ color:#fff; font-size: 64rpx;font-weight: bold; text-align: center; display: inline-block; margin-left: 30rpx; margin-top: 20rpx;}
.score .t2{ color:rgba(255,255,255,0.8);}
</style>