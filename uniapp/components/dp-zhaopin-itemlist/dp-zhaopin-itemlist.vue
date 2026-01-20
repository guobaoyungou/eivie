<template>
<view style="width:100%">
	<view class="dp-product-zhaopin">
		<view class="item" v-for="(item,index) in data" :key="item.id" @click="goto" :data-url="'/zhaopin/zhaopin/detail?id='+item[idfield]">
				<view class="item1 flex">	
						<view class="product-pic">
							<image class="image" :src="item.thumb" mode="aspectFit"/>
							<text class="renzheng" v-if="item.apply_tag">担保企业</text>
						</view>
						<view class="product-info flex1">
							<view class="p1">{{item.title}}</view>
							<view class="p2" >{{item.salary}}</view>
							<view class="p3" >
								<view class="flex">
									<view class="tagitem" v-if="item.cname">{{item.cname}}</view>
									<view class="tagitem" v-if="item.num">{{item.num}}人</view>
									<view class="tagitem" v-if="item.experience">{{item.experience}}</view>
									<view class="tagitem" v-if="item.education">{{item.education}}</view>
								</view>
							</view>
							<view class="p4 flex-s" v-if="showaddress && item.area">
									<image src="../../static/img/address3.png"></image>
									<text>{{item.area}}</text>
							</view>
						</view>
				</view>
				<view class="item2 flex" v-if="item.welfare && item.welfare.length>0">
					<view class="tagitem" v-for="(wf,wk) in item.welfare" :key="wk">{{wf}}</view>
				</view>
		</view>
	</view>
</view>
</template>
<script>
	export default {
		data(){
			return {
				buydialogShow:false,
				proid:0,
			}
		},
		props: {
			menuindex:{default:-1},
			namecolor:{default:'#333'},
			showaddress:{default:'1'},
			data:{},
			idfield:{default:'id'}
		},
		methods: {
		}
	}
</script>
<style>
.dp-product-zhaopin{height: auto; position: relative;overflow: hidden; padding: 0px; }
.dp-product-zhaopin .item{width:100%;display: inline-block;margin-bottom: 20rpx;background: #fff;padding: 20rpx;}
.dp-product-zhaopin .item1{}
.dp-product-zhaopin .product-pic {width: 260rpx;height:200rpx;overflow:hidden;padding-bottom: 30%;border-radius: 14rpx;}
.dp-product-zhaopin .product-pic .image{max-width: 100%;max-height: 200rpx;border-radius:5px;vertical-align: middle;}
.dp-product-zhaopin .product-pic .renzheng{
	position: relative;top: -40rpx;width: 64%; color:#eeda65;background:#3a3a3a;font-size: 24rpx;
	display: flex;justify-content: center;margin: 0 18%;line-height: 40rpx;border-radius: 6rpx 6rpx 0 0;
}
.dp-product-zhaopin .product-info {padding:6rpx 10rpx 5rpx 20rpx;}
.dp-product-zhaopin .product-info .p1 {color:#323232;font-weight:bold;font-size:28rpx;line-height:36rpx;margin-bottom:10rpx;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:2;overflow:hidden;}
.dp-product-zhaopin .product-info .p2 {color:#FF3A69;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:2;overflow:hidden;}
.dp-product-zhaopin .product-info .p3 {font-size: 20rpx;padding: 10rpx 0;}
.dp-product-zhaopin .product-info .p3 .tagitem{background: #cce3fc4d;color: #457dc6;padding: 2rpx 8rpx;margin-right: 8rpx;}
.dp-product-zhaopin .product-info .p4{line-height: 42rpx;color: #999999;}
.dp-product-zhaopin .product-info .p4 image{width: 30rpx;height: 30rpx;vertical-align: text-bottom;}
.dp-product-zhaopin .item2{ border-top: 1rpx solid #f6f6f6;; padding-top: 20rpx; justify-content: flex-start; line-height: 42rpx; color: #6c6c6c; font-size: 24rpx;flex-wrap: wrap;}
.dp-product-zhaopin .item2 .tagitem{background: #f4f7fe;text-align: center;padding: 2rpx 8rpx;margin-right: 8rpx;white-space: normal;}
.dp-product-zhaopin .head  image{ width:42rpx ; height: 42rpx;  border-radius: 50%; vertical-align: middle; margin-right: 10rpx;}
.dp-product-zhaopin .text2{ color:#FF3A69; width: 128rpx;height: 48rpx;border-radius: 24rpx 0px 0px 24rpx; text-align: center;background: linear-gradient(-90deg, rgba(255, 235, 240, 0.4) 0%, #FDE6EC 100%);}
</style>