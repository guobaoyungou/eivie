<template>
<view style="width:100%">
	<view class="dp-product-item">
		<view class="item" v-for="(item,index) in data" :style="'width:49%;margin-right:'+(index%2==0?'2%':0)" :key="item.id" @click="goto" :data-url="'/zhaopin/zhaopin/detail?id='+item[idfield]">
			<view class="product-pic">
				<image class="image" :src="item.thumb" mode="widthFix"/>
				<text class="renzheng" v-if="item.apply_tag">担保企业</text>
			</view>
			<view class="product-info">
				<view class="p1">{{item.title}}</view>
				<view class="p3">
					<text class="t2">{{item.num}}人/{{item.salary}}</text>
				</view>
      </view>
		</view>
	</view>
</view>
</template>
<script>
	export default {
		data(){
			return {
				
			}
		},
		props: {
			showstyle:{default:2},
			menuindex:{default:-1},
			data:{},
			idfield:{default:'id'}
		},
		methods: {
			
		}
	}
</script>
<style>
.dp-product-item{height: auto; position: relative;overflow: hidden; padding: 0px; display:flex;flex-wrap:wrap}
.dp-product-item .item{display: inline-block;position: relative;margin-bottom: 12rpx;background: #fff;border-radius:10rpx;overflow:hidden;height: 360rpx;}
.dp-product-item .product-pic {width: 100%;;overflow:hidden;background: #ffffff;max-height: 240rpx;}
.dp-product-item .product-pic .image{width: 100%;max-height: 100%;}
.dp-product-item .product-pic .renzheng{
	position: relative;top: -40rpx;width: 64%; color:#eeda65;background:#3a3a3a;font-size: 24rpx;
	display: flex;justify-content: center;margin: 0 18%;line-height: 40rpx;border-radius: 6rpx 6rpx 0 0;
}
.dp-product-item .product-info {padding:20rpx 20rpx;}
.dp-product-item .product-info .p1 {color:#323232;font-weight:bold;font-size:28rpx;line-height:36rpx;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:2;overflow:hidden;}
.dp-product-item .product-info .p2{display:flex;align-items:center;overflow:hidden;padding:2px 0}
.dp-product-item .product-info .p2-1{flex-grow:1;flex-shrink:1;height:40rpx;line-height:40rpx;overflow:hidden;white-space: nowrap}
.dp-product-item .product-info .p2-1 .t1{font-size:36rpx;}
.dp-product-item .product-info .p2-1 .t2 {margin-left:10rpx;font-size:24rpx;color: #aaa;text-decoration: line-through;/*letter-spacing:-1px*/}
.dp-product-item .product-info .p2-1 .t3 {margin-left:10rpx;font-size:22rpx;color: #999;}
.dp-product-item .product-info .p2-2{font-size:20rpx;height:40rpx;line-height:40rpx;text-align:right;padding-left:20rpx;color:#999}
.dp-product-item .product-info .p3{color:#999999;font-size:20rpx;margin-top:10rpx}
.dp-product-item .product-info .p4{width:48rpx;height:48rpx;border-radius:50%;position:absolute;display:relative;bottom:10rpx;right:20rpx;text-align:center;}
.dp-product-item .product-info .p4 .icon_gouwuche{font-size:28rpx;height:48rpx;line-height:48rpx}
.dp-product-item .product-info .p4 .img{width:100%;height:100%}
.bg-desc {color: #fff; padding: 10rpx 20rpx;}
</style>