<template>
<view style="width:100%">
	<view class="dp-product-itemlist">
		<view class="item" v-for="(item,index) in data" :key="item.id" @click="goto" :data-url="!item.chaptertype || item.chaptertype==1?'/activity/kecheng/product?id='+item[idfield]:'/pagesB/kecheng/lecturermldetail?kcid='+item[idfield]">
			<view class="product-pic" v-if="showpic == 1">
				<image class="image" :src="item.pic" mode="widthFix"/>
				<image class="saleimg" :src="saleimg" v-if="saleimg!=''" mode="widthFix"/>
				<view style="color:#fff;line-height:40rpx;position:absolute;right:4px;bottom:4px;background-color: rgba(0, 0, 0, 0.3);border-radius:12rpx;width:70rpx;" >
					<view style="display:flex;align-items:center;justify-content:center;font-size:22rpx">
						<view v-if="item.kctype === 1">图文</view>
						<view v-else-if="item.kctype  === 2">音频</view>
						<view v-else-if="item.kctype  === 3">视频</view>
						<view v-else>综合</view>
					</view>
				</view>
			</view>
			<view class="product-info" :style="showpic == 0 ? 'width:100%':''">
				<view class="p1" v-if="showname == 1">{{item.name}}</view>
				<view class="p2" v-if="showprice != '0'">
					<text class="t1" :style="{color:t('color1')}"><text style="font-size:24rpx" v-if="item.price>0">￥</text>{{item.price==0?'免费':item.price}}</text>	
					<text class="t2" v-if="showprice == '1' && item.market_price*1 > item.price*1">￥{{item.market_price}}</text>
				</view>
				<view v-if="!item.chaptertype || item.chaptertype==1" class="p3">
					<view class="p3-1">共{{item.count}}节课 <block v-if=" showsales == 1 &&item.join_num > 0"><text style="margin: 0 6rpx;">|</text>{{item.join_num}}人已加入学习</block></view>
				</view>
			</view>
			<view class="product-details" :style="{color:t('color1')}">查看详情</view>
		</view>
	</view>
	<buydialog v-if="buydialogShow" :proid="proid" @buydialogChange="buydialogChange" :menuindex="menuindex"></buydialog>
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
			saleimg:{default:''},
			showname:{default:1},
			namecolor:{default:'#333'},
			showprice:{default:'1'},
			showsales:{default:'1'},
			showcart:{default:'1'},
			cartimg:{default:'/static/imgsrc/cart.svg'},
			data:{},
			sysset:{},
			idfield:{default:'id'},
			showpic:{default:1},
		},
		methods: {
			buydialogChange: function (e) {
				if(!this.buydialogShow){
					this.proid = e.currentTarget.dataset.proid
				}
				this.buydialogShow = !this.buydialogShow;
			},
		}
	}
</script>
<style>
.dp-product-itemlist{height: auto; position: relative;overflow: hidden; padding: 0px; display:flex;flex-wrap:wrap}
.dp-product-itemlist .item{width:100%;display: inline-block;position: relative;margin-bottom: 12rpx;background: #fff;display:flex;padding:20rpx;border-radius:10rpx}
.dp-product-itemlist .product-pic {width: 30%;height:0;overflow:hidden;background: #ffffff;padding-bottom: 30%;position: relative;border-radius:4px;}
.dp-product-itemlist .product-pic .image{position:absolute;top:0;left:0;width: 100%;height:auto}
.dp-product-itemlist .product-pic .saleimg{ position: absolute;width: 120rpx;height: auto; top: -6rpx; left:-6rpx;}
.dp-product-itemlist .product-info {width: 70%;padding:6rpx 10rpx 5rpx 20rpx;position: relative;}
.dp-product-itemlist .product-info .p1 {color:#323232;font-weight:bold;font-size:28rpx;line-height:36rpx;margin-bottom:10rpx;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:2;overflow:hidden;height:72rpx}
.dp-product-itemlist .product-info .p2{margin-top:20rpx;height:56rpx;line-height:56rpx;overflow:hidden;}
.dp-product-itemlist .product-info .p2 .t1{font-size:36rpx;}
.dp-product-itemlist .product-info .p2 .t2 {margin-left:10rpx;font-size:24rpx;color: #aaa;text-decoration: line-through;/*letter-spacing:-1px*/}
.dp-product-itemlist .product-info .p3{display:flex;align-items:center;overflow:hidden;margin-top:10rpx}
.dp-product-itemlist .product-info .p3-1{font-size:20rpx;height:30rpx;line-height:30rpx;text-align:right;color:#999}
.dp-product-itemlist .product-info .p4{width:48rpx;height:48rpx;border-radius:50%;position:absolute;display:relative;bottom:6rpx;right:4rpx;text-align:center;}
.dp-product-itemlist .product-info .p4 .icon_gouwuche{font-size:28rpx;height:48rpx;line-height:48rpx}
.dp-product-itemlist .product-info .p4 .img{width:100%;height:100%}
.dp-product-itemlist .product-details{position: absolute;right: 30rpx;bottom: 30rpx;font-size:28rpx;}
</style>