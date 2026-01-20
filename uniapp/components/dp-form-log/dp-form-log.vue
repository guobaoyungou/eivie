<template>
	<view class="dp-form-log" :style="{backgroundColor:params.bgcolor,
	margin:(params.margin_y*2.2)+'rpx '+(params.margin_x*2.2)+'rpx',
	borderRadius:params.borderradius+'px'}" @tap="goto" :data-url="params.hrefurl+'?id='+data.id">
		<div class="flex head">
			<div class="left">{{params.title}}</div>
			<div class="right">全部{{data.count}}条</div><image :src="pre_url+'/static/img/arrowright.png'"  style="width:32rpx;height:32rpx"/>
		</div>
		<div class="text" v-if="data.log">
			<div class="title"><text v-if="data.log.realname">{{data.log.realname}}</text><text v-else>{{data.log.nickname}}</text>填写了{{data.name}}</div>
			<div>{{data.log.time}}</div>
		</div>
	</view>
</template>

<script>
	var app =getApp();
	export default {
		name:"dp-form-log",
		props: {
			params:{},
			data:{},
			pre_url:app.globalData.pre_url,
		},
		methods:{
		}
	}
</script>

<style>
.dp-form-log {padding:20rpx 20rpx;height: auto; }
.dp-form-log .head {align-items: center;}
.dp-form-log .head .left{ font-size: 32rpx; width: 50%; color: #333}
.dp-form-log .head .right { text-align: right; width: 50%; color: #666 }
.dp-form-log .text { margin-top: 10rpx; padding-left: 30rpx; color: #666;}
.dp-form-log .title text {margin-right: 12rpx;}
</style>