<template>
<view class="dp-text" :style="{
	textAlign:params.align,
	color:params.color,
	background:params.bgpic ? 'url('+params.bgpic+')' : params.bgcolor,
	backgroundSize:'100%',
	margin:params.margin_y*2.2+'rpx '+params.margin_x*2.2+'rpx 0',
	padding:params.padding_y*2.2+'rpx '+params.padding_x*2.2+'rpx',
	fontWeight:params.fontbold?'bold':'normal'
}" @tap="goto" :data-url="params.hrefurl">
	<text :style="[{
		whiteSpace:'pre-wrap',
		letterSpacing:params.letter_spacing ? params.letter_spacing+'px' : 'normal',
		fontSize:params.fontsize*2+'rpx',
		lineHeight:params.lineheight*2+'rpx'
	}]" :selectable="selectable" :user-select="selectable">{{params.showcontent || '文本内容'}}</text>
</view>
</template>
<script>
	export default {
		props: {
			selectable:false,
			params:{},
			data:{}
		}
	}
</script>
<style>
.dp-text{height: auto; position: relative;}
</style>