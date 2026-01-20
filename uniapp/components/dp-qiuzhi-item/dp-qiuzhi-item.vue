<template>
<view style="width:100%">
	<view class="dp-product-item">
		<view class="item" v-for="(item,index) in data" :style="'width:49%;margin-right:'+(index%2==0?'2%':0)" :key="item.id" @click="goto" :data-url="'/zhaopin/qiuzhi/detail?id='+item[idfield]">
			<view class="product-pic">
				<text class="tag" :class="item.has_job==1?'st1':'st2'">{{item.has_job==1?'在职':'离职'}}</text>
				<image class="image" :style="'filter: blur('+item.mohu+'px);-webkit-filter: blur('+item.mohu+'px);-moz-filter: blur('+item.mohu+'px)'" :src="item.thumb" mode="aspectFill"/>
				<text class="qianyue" v-if="item.qianyue_id>0">签约保障中</text>
				<text class="qianyue" v-else>认证保障中</text>
			</view>
			<view class="product-info">
				<view class="p1">{{item.title}}</view>
				<view class="p3">{{item.cnames}}</view>
				<view class="p3">
					<text class="t2">{{item.name}}/{{item.sex==1?'男':'女'}}/{{item.salary}}</text>
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
				buydialogShow:false,
				proid:0,
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
.dp-product-item .item{display: inline-block;position: relative;margin-bottom: 12rpx;background: #fff;overflow:hidden}
.dp-product-item .product-pic {width: 100%;height:340rpx;overflow:hidden;background: #ffffff;padding-bottom: 100%;position: relative;background: #ededed;}
.dp-product-item .product-pic .image{position:absolute;top:-32rpx;left:0;width: 100%;height: 380rpx;}
.dp-product-item .product-pic .tag{ position: absolute;height: auto; top: -2rpx; left:0px;color: #FFFFFF;background:#b1b2b2;padding: 0 10rpx;border-radius: 0 0 8rpx 0;font-size: 24rpx;height: 30rpx;z-index: 99;}
.dp-product-item .product-pic .tag.st1{background:#FF3A69;}
.dp-product-item .product-pic .tag.st2{background:#3889f6;}
.dp-product-item .product-pic .qianyue{color:#eeda65;background:#3a3a3a;font-size: 22rpx;padding: 4rpx 20rpx 6rpx 20rpx;position: absolute;top: 310rpx;left:28%;z-index: 50;border-radius: 6rpx 6rpx 0 0;}
.dp-product-item .product-pic .renzheng{color:#eeda65;background:#3a3a3a;font-size: 22rpx;padding: 4rpx 20rpx;position: absolute;top: 319rpx;left:28%;z-index: 50;border-radius: 6rpx 6rpx 0 0;}
.dp-product-item .product-info {padding:20rpx 20rpx;position: relative;}
.dp-product-item .product-info .p1 {color:#323232;font-weight:bold;font-size:28rpx;line-height:36rpx;margin-bottom:10rpx;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:2;overflow:hidden;}
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