<template>
<view style="width:100%">
	<view class="dp-product-normal-item">
		<view class="item" v-for="(item,index) in data" :style="'background:'+probgcolor+';'+(showstyle==2 ? 'width:49%;margin-right:'+(index%2==0?'2%':0) : (showstyle==3 ? 'width:32%;margin-right:'+(index%3!=2?'2%':0) :'width:100%'))" :key="item.id" @click="goto" :data-url="'/pagesA/tour/product?id='+item.proid" >
			<view class="product-pic" >
				<image class="image" :src="item.pic" mode="widthFix"/>
				<image class="saleimg" :src="saleimg" v-if="saleimg!=''" mode="widthFix"/>
			</view>
			<view class="product-info">
				<view class="p1" v-if="showname == 1">{{item.name}}</view>
				<view class="p2">
					<view class="p2-1" v-if="showprice == 1">
						<text class="t1" :style="{color:t('color1')}">
								<text style="font-size:24rpx">￥</text>{{item.sell_price}}
						</text>
					</view>
				</view>
				<view class="p3" v-if="showsales=='1' && item.sales>0">已售{{item.sales}}件</view>
        <view v-if="params.style=='2' && params.nowbuy == 1" @tap.stop="goto" :data-url="'/pagesA/tour/product?id=' + item.proid" class="nowbuy" :style="{background:'rgba('+t('color1rgb')+',0.1)',color:t('color1')}" >
            立即购买
        </view>
				<view class="p4" :style="params.style=='2' && params.nowbuy == 1?'bottom:24rpx;background:rgba('+t('color1rgb')+',0.1);color:'+t('color1'):'background:rgba('+t('color1rgb')+',0.1);color:'+t('color1')" v-if="showcart==1 && !item.price_type"  @tap.stop="goto" :data-url="'/pagesA/tour/product?id=' + item.proid"><text class="iconfont icon_gouwuche"></text></view>
				<view class="p4" :style="params.style=='2' && params.nowbuy == 1?'bottom:24rpx;background:rgba('+t('color1rgb')+',0.1);color:'+t('color1'):'background:rgba('+t('color1rgb')+',0.1);color:'+t('color1')" v-if="showcart==2 && !item.price_type"  @tap.stop="goto" :data-url="'/pagesA/tour/product?id=' + item.proid"><image :src="cartimg" class="img"/></text></view>
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
        showLinkStatus:false,
        btntype:1,
			}
		},
		props: {
			showstyle:{default:2},
			menuindex:{default:-1},
			saleimg:{default:''},
			showname:{default:1},
			namecolor:{default:'#333'},
			showprice:{default:'1'},
			showsales:{default:'1'},
			showcart:{default:'1'},
			cartimg:{default:'/static/imgsrc/cart.svg'},
			data:{},
			idfield:{default:'id'},
			probgcolor:{default:'#fff'},
			showcommission: {
				default: '0'
			},
			showbname: {
				default: '0'
			},
			showbdistance: {
				default: '0'
			},
      params:{
				type:Object,
				default() {
					return {};
				}
			},
		},
		methods: {
			buydialogChange: function (e) {
				if(!this.buydialogShow){
					this.proid = e.currentTarget.dataset.proid;
                    this.btntype = e.currentTarget.dataset.btntype
				}
				this.buydialogShow = !this.buydialogShow;
				console.log(this.buydialogShow);
			},
			addcart:function(){
				this.$emit('addcart');
			},
		}
	}
</script>
<style>
.dp-product-normal-item{height: auto; position: relative;overflow: hidden; padding: 0px; display:flex;flex-wrap:wrap}
.dp-product-normal-item .item{display: inline-block;position: relative;margin-bottom: 12rpx;background: #fff;border-radius:10rpx;overflow:hidden;}
.dp-product-normal-item .product-pic {width: 100%;height:0;overflow:hidden;background: #ffffff;padding-bottom: 100%;position: relative;}
.dp-product-normal-item .product-pic .image{position:absolute;top:0;left:0;width: 100%;height:auto}
.dp-product-normal-item .product-pic .saleimg{ position: absolute;width: 60px;height: auto; top: -3px; left:-3px;}
.dp-product-normal-item .product-info {padding:20rpx 20rpx;position: relative;}
.dp-product-normal-item .product-info .p1 {color:#323232;font-weight:bold;font-size:28rpx;line-height:36rpx;margin-bottom:10rpx;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:2;overflow:hidden;height:72rpx}
.dp-product-normal-item .product-info .p2{display:flex;align-items:center;overflow:hidden;padding:2px 0}
.dp-product-normal-item .product-info .p2-1{flex-grow:1;flex-shrink:1;height:40rpx;line-height:40rpx;overflow:hidden;white-space: nowrap}
.dp-product-normal-item .product-info .p2-1 .t1{font-size:36rpx;}
.dp-product-normal-item .product-info .p2-1 .t2 {margin-left:10rpx;font-size:24rpx;color: #aaa;text-decoration: line-through;/*letter-spacing:-1px*/}
.dp-product-normal-item .product-info .p2-1 .t3 {margin-left:10rpx;font-size:22rpx;color: #999;}
.dp-product-normal-item .product-info .p2-2{font-size:20rpx;height:40rpx;line-height:40rpx;text-align:right;padding-left:20rpx;color:#999}
.dp-product-normal-item .product-info .p3{color:#999999;font-size:20rpx;margin-top:10rpx}
.dp-product-normal-item .product-info .p4{width:52rpx;height:52rpx;border-radius:50%;position:absolute;display:relative;bottom:16rpx;right:20rpx;text-align:center;}
.dp-product-normal-item .product-info .p4 .icon_gouwuche{font-size:30rpx;height:52rpx;line-height:52rpx}
.dp-product-normal-item .product-info .p4 .img{width:100%;height:100%}
.bg-desc {color: #fff; padding: 10rpx 20rpx;}

.dp-product-normal-item .product-info .binfo {
		padding-bottom:6rpx;
		display: flex;
		align-items: center;
		min-width: 0;
	}

	.dp-product-normal-item .product-info .binfo .t1 {
		width: 30rpx;
		height: 30rpx;
		border-radius: 50%;
		margin-right: 10rpx;
		flex-shrink: 0;
	}

	.dp-product-normal-item .product-info .binfo .t2 {
		color: #666;
		font-size: 24rpx;
		font-weight: normal;
		overflow: hidden;
		text-overflow: ellipsis;
		white-space: nowrap;
	}
	.dp-product-normal-item .product-info .binfo .b2{flex-shrink: 0;}
	.dp-product-normal-item .product-info .binfo .b1{max-width: 75%;}
	.dp-product-normal-item .couponitem {
		width: 100%;
		/* padding: 0 20rpx 20rpx 20rpx; */
		font-size: 24rpx;
		color: #333;
		display: flex;
		align-items: center;
	}

	.dp-product-normal-item .couponitem .f1 {
		flex: 1;
		display: flex;
		flex-wrap: nowrap;
		overflow: hidden
	}

	.dp-product-normal-item .couponitem .f1 .t {
		margin-right: 10rpx;
		border-radius: 3px;
		font-size: 22rpx;
		height: 40rpx;
		line-height: 40rpx;
		padding-right: 10rpx;
		flex-shrink: 0;
		overflow: hidden
	}


.lianxi{color: #fff;border-radius: 50rpx 50rpx;line-height: 50rpx;text-align: center;font-size: 22rpx;padding: 0 14rpx;display: inline-block;float: right;}
.field_buy{line-height: 50rpx;border-bottom: 0;overflow: hidden;text-overflow: ellipsis;white-space:nowrap;word-break: keep-all;}
.nowbuy{width:160rpx;line-height:60rpx;text-align: center;border-radius: 4rpx;margin-top: 10rpx;}
</style>