<template>
<view class="dp-notice" :style="{
	color:params.color,
	backgroundColor:params.bgcolor,
	margin:(params.margin_y*2.2)+'rpx '+(params.margin_x*2.2)+'rpx' + ' 0rpx',
	padding:(params.padding_y*2.2)+'rpx '+(params.padding_x*2.2)+'rpx'
}">

	<view class="left" v-if="params.showimg==1"><image class="image" :src="params.img" mode="heightFix"/></view>
	<view class="right">
		<image v-if="params.showicon==1" class="ico" :src="params.icon"/>
		<block v-if="data && data.length == 1">
			<view class="dp-notice-content" :style="{textAlign:textAlign}">
				<view class="dp-content-text" :style="{fontSize:(params.fontsize*2.2)+'rpx',animationDuration:animationDuration,animationPlayState:animationPlayState,paddingLift:paddingLift}" @click="goto" :data-url="data[0].hrefurl">
					{{data[0].title}}
				</view>
			</view>
		</block>
		<block v-else>
			<swiper style="position:relative;height:40rpx;" autoplay="true" :interval="params.scroll*1000" vertical="true" class="itemlist" circular>
				<swiper-item class="item" v-for="item in data" :key="item.id" :style="{fontSize:(params.fontsize*2.2)+'rpx'}" @click="goto" :data-url="item.hrefurl">{{item.title}}</swiper-item>
			</swiper>
		</block>
	</view>
</view>
</template>
<script>
	export default {
		props: {
			params:{},
			data:{}
		},
		data(){
			return{
				animationDuration:'0',
				animationPlayState: 'paused',
				paddingLift:'0',
				textAlign:'left',
			}
		},
		mounted(){
			// 轮播内容为一条
			if(this.data && this.data.length == 1){
				this.init()
			}
		},
		methods: {
			async init(){
				let that = this;
				let boxWidth = 0,textWidth = 0;
				boxWidth = (await that.clientWidth('.dp-notice-content')).width;
				textWidth = (await that.clientWidth('.dp-content-text')).width;
				if(textWidth <= boxWidth) return;
				that.paddingLift = '100%';
				that.textAlign = 'right';
				that.$nextTick(() => {
					that.animationAdd();
				})
			},
			async animationAdd(){
				let that = this;
				let boxWidth = 0,textWidth = 0;
				boxWidth = (await that.clientWidth('.dp-notice-content')).width;
				textWidth = (await that.clientWidth('.dp-content-text')).width;
				that.animationDuration = `${textWidth / 40}s`;
				that.animationPlayState = 'paused'
				setTimeout(() => {
					that.animationPlayState = 'running'
				}, 100)
			},
			clientWidth(select){
				return new Promise((resolve) => {
					uni.createSelectorQuery().in(this).select(select).boundingClientRect((rect) => {
						 resolve(rect)
					}).exec()
				})
			}
		},
	}
</script>
<style>
.dp-notice{height: auto;background: #fff; font-size: 28rpx; color: #666666;overflow: hidden; white-space:nowrap; position: relative;display:flex;align-items:center;padding:2px 4px}
.dp-notice .left{position:relative;padding-right:20rpx;margin-right:20rpx;height:40rpx;display:flex;align-items: center;}
.dp-notice .left:before { content: " "; position: absolute; width: 0; top: 2px; right: 0; bottom: 2px; border-right: 1px solid #e2e2e2; }
.dp-notice .image{position:relative;height:36rpx;width:auto}
.dp-notice .right{flex-grow:1;display:flex;align-items: center;overflow:hidden}
.dp-notice .right .ico{width:36rpx;height:36rpx;margin-right:10rpx}
.dp-notice .itemlist{width:100%;height:100%;line-height:40rpx;font-size:28rpx;}
.dp-notice-content{width: 100%;height: 40rpx;line-height: 40rpx;flex: 1;overflow: hidden;}
.dp-notice-content .dp-content-text{white-space: nowrap;height: 100%;display: inline-block;animation: loop-animation 10s linear infinite both;word-break: keep-all;}
@keyframes loop-animation {
	0% {
		transform: translate3d(0, 0, 0);
	}
	100% {
		transform: translate3d(-100%, 0, 0);
	}
}
</style>