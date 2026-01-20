<template>
<view style="width:100%">
	<view class="dp-product-itemlist">
		<view class="item" v-for="(item,index) in data" :key="item.id" @click="goto" :data-url="'/pagesExt/cycle/product?id='+item[idfield]">
			<view class="product-pic">
				<image class="image" :src="item.pic" mode="widthFix"/>
				<image class="saleimg" :src="saleimg" v-if="saleimg!=''" mode="widthFix"/>
			</view>
			<view class="product-info">
				<view class="p1" v-if="showname == 1">{{item.name}}</view>
				<view class="p2" v-if="showprice != '0' && ( item.price_type != 1 || item.sell_price > 0)">
					<text class="t1" :style="{color:t('color1')}"><text style="font-size:24rpx;padding-right:1px">￥</text>{{item.sell_price}}</text>
					<text class="t2" v-if="showprice == '1' && item.market_price*1 > item.sell_price*1">￥{{item.market_price}}</text>
					<text class="t3" v-if="item.juli">{{item.juli}}</text>
				</view>
                <view class="p2" v-if="item.xunjia_text && item.price_type == 1 && item.sell_price <= 0" style="height: 50rpx;line-height: 44rpx;">
                	<text class="t1" :style="{color:t('color1'),fontSize:'30rpx'}">询价</text>
                    <block v-if="item.xunjia_text && item.price_type == 1">
                    	<view class="lianxi" :style="{background:t('color1')}" @tap.stop="showLinkChange" :data-lx_name="item.lx_name" :data-lx_bid="item.lx_bid" :data-lx_bname="item.lx_bname" :data-lx_tel="item.lx_tel" data-btntype="2">{{item.xunjia_text?item.xunjia_text:'联系TA'}}</view>
                    </block>
                </view>
                <view class="p1" v-if="item.merchant_name" style="color: #666;font-size: 24rpx;white-space: nowrap;text-overflow: ellipsis;margin-top: 6rpx;height: 30rpx;line-height: 30rpx;font-weight: normal"><text>{{item.merchant_name}}</text></view>
                <view class="p1" v-if="item.main_business" style="color: #666;font-size: 24rpx;margin-top: 4rpx;font-weight: normal;"><text>{{item.main_business}}</text></view>
				<view class="p3">
					
					<view class="p3-1" :style="{background:'rgba('+t('color1rgb')+',0.12)',color:t('color1')}">{{item.ps_cycle_title}}</view>
					<view class="p3-2" v-if="showsales=='1' && item.sales>0"><text style="overflow:hidden">已售{{item.sales}}件</text></view>
								
				</view>
                <view v-if="showsales !='1' ||  item.sales<=0" style="height: 44rpx;"></view>
            </view>
		</view>
	</view>
	<buydialog v-if="buydialogShow" :proid="proid" @buydialogChange="buydialogChange" :menuindex="menuindex"></buydialog>
    <view class="posterDialog linkDialog" v-if="showLinkStatus">
    	<view class="main">
    		<view class="close" @tap="showLinkChange"><image class="img" :src="pre_url+'/static/img/close.png'"/></view>
    		<view class="content">
    			<view class="title">{{lx_name}}</view>
    			<view class="row" v-if="lx_bid > 0">
    				<view class="f1">店铺名称</view>
    				<view class="f2" @tap="goto" :data-url="'/pagesExt/business/index?id='+lx_bid">{{lx_bname}}<image :src="pre_url+'/static/img/arrowright.png'" class="image"/></view>
    			</view>
    			<view class="row" v-if="lx_tel">
    				<view class="f1">联系电话</view>
    				<view class="f2" @tap="goto" :data-url="'tel::'+lx_tel" :style="{color:t('color1')}">{{lx_tel}}<image :src="pre_url+'/static/img/copy.png'" class="copyicon" @tap.stop="copy" :data-text="lx_tel"></image></view>
    			</view>
    		</view>
    	</view>
    </view>
</view>
</template>
<script>
	var app = getApp();
	export default {
		data(){
			return {
				buydialogShow:false,
				proid:0,
				pre_url:app.globalData.pre_url,
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
			idfield:{default:'id'}
		},
		methods: {
			buydialogChange: function (e) {
				if(!this.buydialogShow){
					this.proid = e.currentTarget.dataset.proid
				}
				this.buydialogShow = !this.buydialogShow;
			},
            showLinkChange: function (e) {
                var that = this;
            	that.showLinkStatus = !that.showLinkStatus;
                that.lx_name = e.currentTarget.dataset.lx_name;
                that.lx_bid = e.currentTarget.dataset.lx_bid;
                that.lx_bname = e.currentTarget.dataset.lx_bname;
                that.lx_tel = e.currentTarget.dataset.lx_tel;
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
.dp-product-itemlist .product-info .p2 .t3 {margin-left:10rpx;font-size:24rpx;color: #888;}
.dp-product-itemlist .product-info .p3{display:flex;align-items:center;overflow:hidden;margin-top:10rpx;justify-content: space-between;display:flex;}
.dp-product-itemlist .product-info .p3 .p3-1{height:40rpx;line-height:40rpx;border:0 #FF3143 solid;border-radius:10rpx;color:#FF3143;padding:0 24rpx;font-size:24rpx}
.dp-product-itemlist .product-info .p3 .p3-2{color:#999999;font-size:20rpx;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:1;}

.dp-product-itemlist .product-info .p4{width:48rpx;height:48rpx;border-radius:50%;position:absolute;display:relative;bottom:6rpx;right:4rpx;text-align:center;}
.dp-product-itemlist .product-info .p4 .icon_gouwuche{font-size:28rpx;height:48rpx;line-height:48rpx}
.dp-product-itemlist .product-info .p4 .img{width:100%;height:100%}

.lianxi{color: #fff;border-radius: 50rpx 50rpx;line-height: 50rpx;text-align: center;font-size: 22rpx;padding: 0 14rpx;display: inline-block;float: right;}
</style>