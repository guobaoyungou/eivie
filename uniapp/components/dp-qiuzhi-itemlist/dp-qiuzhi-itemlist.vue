<template>
<view style="width:100%">
	<view class="dp-qiuzhi-itemlist">
		<view class="item" v-for="(item,index) in data" :key="item.id" @click="goto" :data-url="'/zhaopin/qiuzhi/detail?id='+item[idfield]">
				<view class="item1 flex">	
						<view class="product-pic">
							<text class="status" :class="'st'+item.has_job">{{item.has_job==1?'在职':'离职'}}</text>
							<image class="image" :style="'filter: blur('+item.mohu+'px);-webkit-filter: blur('+item.mohu+'px);-moz-filter: blur('+item.mohu+'px)'"  :src="item.thumb" mode="aspectFit"/>
							<text class="qianyue" v-if="item.qianyue_id>0">签约保障中</text>
							<block v-else>
							<text class="qianyue" v-if="item.apply_id>0">认证保障中</text>
							<text class="norenzheng" v-else @tap.stop="gorenzheng">未认证</text>
							</block>
							</block>
						</view>
						<view class="product-info">
							<view class="p1" v-if="showname == 1">
							{{item.name}}
							<image v-if="item.sex==1" src="../../static/img/nan.png"></image>
							<image v-if="item.sex==2" src="../../static/img/nv.png"></image>
							</view>
							<view class="p2"><text>期望薪资：</text><text class="number">{{item.salary}}</text></view>
							<view class="p2">
								<text>期望岗位：</text>
						    <text>{{item.cnames}}</text>
							</view>
							<view class="p2">
								<text>期望城市：</text>
							  <text>{{item.area}}</text>
							</view>
						</view>
				</view>
				<view class="item2 flex" v-if="item.tags && item.tags.length>0">
					<view class="tagitem" v-for="(wf,wk) in item.tags" :key="wk">{{wf}}</view>
				</view>
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
			showtitle:{default:1},
			showname:{default:1},
			namecolor:{default:'#333'},
			showaddress:{default:'1'},
			data:{},
			idfield:{default:'id'}
		},
		methods: {
			buydialogChange: function (e) {
				if(!this.buydialogShow){
					this.proid = e.currentTarget.dataset.proid
				}
				this.buydialogShow = !this.buydialogShow;
			}
		}
	}
</script>
<style>
.dp-qiuzhi-itemlist{height: auto; position: relative;overflow: hidden; padding: 0px; }
.dp-qiuzhi-itemlist .item{width:100%;display: inline-block;margin-bottom: 20rpx;background: #fff;padding: 20rpx;padding-top: 0;}
.dp-qiuzhi-itemlist .item1{align-items: center;padding: 20rpx 0;}
.dp-qiuzhi-itemlist .product-pic {width: 180rpx;height:180rpx;overflow:hidden;background: #ffffff;position: relative;flex-shrink: 0;}
.dp-qiuzhi-itemlist .product-pic .image{max-width: 100%;max-height: 100%;border-radius:5px;vertical-align: middle;top: 0;position: absolute;left: 0;}
.dp-qiuzhi-itemlist .product-pic .image.mohu{filter: blur(10px);-webkit-filter: blur(10px);-moz-filter: blur(10px);}
.dp-qiuzhi-itemlist .product-pic .status{color: #FFFFFF;background:#b1b2b2;font-size: 24rpx;padding: 0 8rpx;position: relative;top: 0;z-index: 5;border-radius: 0 0 6rpx 0;}
.dp-qiuzhi-itemlist .product-pic .qianyue{color:#eeda65;background:#3a3a3a;font-size: 22rpx;padding: 0 8rpx 8rpx 8rpx;position: relative;top: -72rpx;left:13%;z-index: 5;border-radius: 6rpx 6rpx 0 0;}
.dp-qiuzhi-itemlist .product-pic .norenzheng{color:#fff;background:#fb9c48;font-size: 22rpx;padding: 5rpx 8rpx;position: absolute;z-index: 5;border-radius: 6rpx 6rpx 0 0;bottom: 0;left: 0;right: 0;margin: 0 auto;width: 90rpx;text-align: center;}
/* .dp-qiuzhi-itemlist .product-pic .norenzheng{color: #fff;background: #fb9c48;font-size: 22rpx;position: relative;top: -68rpx;left:13%;z-index: 5;border-radius: 6rpx 6rpx 0 0;width: 120rpx;display: inline-block;text-align: center;line-height: 36rpx;} */
.dp-qiuzhi-itemlist .product-pic .renzheng{color:#f7f7f7;background:#43a59c;font-size: 22rpx;padding: 0 8rpx;position: relative;top: -70rpx;left:13%;z-index: 5;border-radius: 6rpx 6rpx 0 0;}
.dp-qiuzhi-itemlist .product-pic .st1{background:#FF3A69;}
.dp-qiuzhi-itemlist .product-pic .st2{background:#3889f6;}
.dp-qiuzhi-itemlist .product-info {padding-left:20rpx;color: #999;font-size: 24rpx;}
.dp-qiuzhi-itemlist .product-info .p1 {color:#323232;font-weight:bold;font-size:28rpx;line-height:36rpx;margin-bottom:10rpx;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:2;overflow:hidden;}
.dp-qiuzhi-itemlist .product-info .p1 image{width: 30rpx;height:30rpx;vertical-align: text-bottom;}
.dp-qiuzhi-itemlist .product-info .number {color:#FF3A69;}
.dp-qiuzhi-itemlist .product-info .p2 {line-height: 40rpx;}

.dp-qiuzhi-itemlist .item2{ border-top: 1rpx solid #f6f6f6;; padding-top: 14rpx; justify-content: flex-start; line-height: 36rpx; color: #6c6c6c; font-size: 24rpx;flex-wrap: wrap;}
.dp-qiuzhi-itemlist .item2 .tagitem{background: #f4f7fe;text-align: center;padding: 2rpx 8rpx;margin-right: 8rpx;white-space: normal;}
.dp-qiuzhi-itemlist .head  image{ width:42rpx ; height: 42rpx;  border-radius: 50%; vertical-align: middle; margin-right: 10rpx;}
.dp-qiuzhi-itemlist .text2{ color:#FF3A69; width: 128rpx;height: 48rpx;border-radius: 24rpx 0px 0px 24rpx; text-align: center;background: linear-gradient(-90deg, rgba(255, 235, 240, 0.4) 0%, #FDE6EC 100%);}
</style>