<template>
<view class="dp-cube" :style="{
	background:params.bgcolor,height:(params.maxheight*187.5)+'rpx',margin:(params.margin_y/340*750)+'rpx '+(params.margin_x/340*750)+'rpx 0',
	padding:(params.padding_y/340*750)+'rpx '+(Number(params.padding_x)/340*750)+'rpx'
}">
	<block v-for="(row,idx) in params.layout">
		<block v-for="(col,idx2) in row">
			<view @click="goto" :data-url="col.hrefurl" v-if="!col.isempty" :style="[{
				position:'absolute',
				display:'flex',
				alignItems:'center',
				top:(idx*(750-params.margin_y/340*750*2-params.padding_y/340*750*2)/4+params.padding_y/340*750*1)+'rpx',
				left:(idx2*(750-(Number(params.margin_x)+Number(tabcub.margin_x ? tabcub.margin_x:0))/340*750*2-(Number(params.padding_x)+Number(tabcub.padding_x ? tabcub.padding_x:0))/340*750*2)/4+Number(params.padding_x)/340*750*1)+'rpx',
				width:(col.cols*(750-(Number(params.margin_x)+Number(tabcub.margin_x ? tabcub.margin_x:0))/340*750*2-(Number(params.padding_x)+Number(tabcub.padding_x ? tabcub.padding_x:0))/340*750*2)/4)+3+'rpx',
				height:(col.rows*(750-params.margin_y/340*750*2-params.padding_y/340*750*2)/4)+'rpx',
				lineHeight:(col.rows*(750-params.margin_y/340*750*2-params.padding_y/340*750*2)/4)+'rpx'
			}]">
				<image :src="col.imgurl" show-menu-by-longpress='true' style="width:100%;max-height:100%" mode="widthFix"></image>
			</view>
		</block>
	</block>
</view>
</template>
<script>
	export default {
		props: {
			params:{},
			tabcub:{
				margin_x:0,
				padding_x:0
			},
			data:{}
		}
	}
</script>
<style>
.dp-cube{height: auto; position: relative;display:block;position:relative;height:750rpx}
</style>