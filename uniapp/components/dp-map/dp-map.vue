<template>
<view class="dp-map" :style="{
	backgroundColor:params.bgcolor,
	margin:params.margin_y*2.2+'rpx '+params.margin_x*2.2+'rpx 0',
	padding:params.padding_y*2.2+'rpx '+params.padding_x*2.2+'rpx'
}">
	<!-- #ifdef MP-WEIXIN -->
	<map :style="{width:'100%',height:params.height*2.2+'rpx'}" :longitude="params.longitude" :latitude="params.latitude" :markers="[{
		label:{content:params.address,fontSize:'14px',borderRadius:'4px',color:'#000',anchorX:'-10px',anchorY:'-62px',padding:'3px',textAlign:'center'},
		anchor:{x:0.17,y:1},
		id:0,
		latitude:params.latitude,
		longitude: params.longitude,
		iconPath: pre_url+'/static/img/marker.png',
		width:'73',
		height:'33'
	}]" @click="openLocation" :data-longitude="params.longitude" :data-latitude="params.latitude" :data-address="params.address"></map>
	<!-- #endif -->
	<!-- #ifndef MP-WEIXIN -->
	<map :style="{width:'100%',height:params.height*2.2+'rpx'}" :longitude="params.longitude" :latitude="params.latitude" :markers="[{
		label:{content:params.address,fontSize:'14px',borderRadius:'4px',color:'#000',x:'-10px',y:'-62px',padding:'3px',textAlign:'center'},
		anchor:{x:0.17,y:1},
		id:0,
		latitude:params.latitude,
		longitude: params.longitude,
		iconPath: pre_url+'/static/img/marker.png',
		width:'73',
		height:'33'
	}]" @click="openLocation" :data-longitude="params.longitude" :data-latitude="params.latitude" :data-address="params.address"></map>
	<!-- #endif -->
</view>
</template>
<script>
	export default {
		data(){
			return {
				pre_url:getApp().globalData.pre_url
			}
		},
		props: {
			params:{},
			data:{}
		},
		methods:{
			openLocation:function(e){
				var latitude = parseFloat(e.currentTarget.dataset.latitude)
				var longitude = parseFloat(e.currentTarget.dataset.longitude)
				var address = e.currentTarget.dataset.address
				uni.openLocation({
				 latitude:latitude,
				 longitude:longitude,
				 name:address,
				 scale: 13
			 })
			}
		}
	}
</script>
<style>
.dp-map{height: auto; position: relative;}
</style>