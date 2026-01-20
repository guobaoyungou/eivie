<template>
<view style="width:100%">
	<view class="dp-product-item">
		<view class="item" v-for="(item,index) in data" :style="showstyle==2 ? 'width:49%;margin-right:'+(index%2==0?'2%':0) : (showstyle==3 ? 'width:32%;margin-right:'+(index%3!=2?'2%':0) :'width:100%')" :key="item.id" @click="goto" :data-url="'/pagesExt/cycle/product?id='+item[idfield]">
			<view class="product-pic">
				<image class="image" :src="item.pic" mode="widthFix"/>
				<image class="saleimg" :src="saleimg" v-if="saleimg!=''" mode="widthFix"/>
			</view>
			<view class="product-info">
				<view class="p1" v-if="showname == 1">{{item.name}}</view>
				<view class="p2">
					<view class="p2-1" v-if="showprice != '0' && ( item.price_type != 1 || item.sell_price > 0)">
						<text class="t1" :style="{color:t('color1')}"><text style="font-size:24rpx">￥</text>{{item.sell_price}}</text>
						<text class="t2" v-if="showprice == '1' && item.market_price*1 > item.sell_price*1">￥{{item.market_price}}</text>
						<text class="t3" v-if="item.juli" style="color:#888;">{{item.juli}}</text> 
					</view>
                    <view class="p2-1" v-if="item.xunjia_text && item.price_type == 1 && item.sell_price <= 0" style="height: 50rpx;line-height: 44rpx;">
                    	<text v-if="showstyle!=1" class="t1" :style="{color:t('color1'),fontSize:'30rpx'}">询价</text>
                        <text v-if="showstyle==1" class="t1" :style="{color:t('color1')}">询价</text>
                        <block v-if="item.xunjia_text && item.price_type == 1">
                        	<view class="lianxi" :style="{background:t('color1')}" @tap.stop="showLinkChange" :data-lx_name="item.lx_name" :data-lx_bid="item.lx_bid" :data-lx_bname="item.lx_bname" :data-lx_tel="item.lx_tel" data-btntype="2">{{item.xunjia_text?item.xunjia_text:'联系TA'}}</view>
                        </block>
                    </view>
				</view>
                <view class="p1" v-if="item.merchant_name" style="color: #666;font-size: 24rpx;white-space: nowrap;text-overflow: ellipsis;margin-top: 6rpx;height: 30rpx;line-height: 30rpx;font-weight: normal;"><text>{{item.merchant_name}}</text></view>
                <view class="p1" v-if="item.main_business" style="color: #666;font-size: 24rpx;margin-top: 4rpx;font-weight: normal;"><text>{{item.main_business}}</text></view>
				
				<view class="p3" >
				
					<view class="p3-1" :style="{background:'rgba('+t('color1rgb')+',0.12)',color:t('color1')}">{{item.ps_cycle_title}}</view>
					<view class="p3-2" v-if="showsales=='1' && item.sales>0"><text style="overflow:hidden">已售{{item.sales}}件</text></view>
			
				</view>
                <view v-if="showsales !='1' ||  item.sales<=0" style="height: 44rpx;"></view>
                <!-- <view class="p4" :style="{background:'rgba('+t('color1rgb')+',0.1)',color:t('color1')}" v-if="showcart==1 && !item.price_type" @click.stop="buydialogChange" :data-proid="item[idfield]"><text class="iconfont icon_gouwuche"></text></view> -->
                <!-- <view class="p4" :style="{background:'rgba('+t('color1rgb')+',0.1)',color:t('color1')}" v-if="showcart==2 && !item.price_type" @click.stop="buydialogChange" :data-proid="item[idfield]"><image :src="cartimg" class="img"/></text></view> -->
            </view>
			<view class="bg-desc" v-if="item.hongbaoEdu > 0" :style="{background:'linear-gradient(90deg,'+t('color2')+' 0%,rgba('+t('color2rgb')+',0.8) 100%)'}">可获额度 +{{item.hongbaoEdu}}</view>
		</view>
	</view>
	<buydialog v-if="buydialogShow" :proid="proid" @addcart="addcart" @buydialogChange="buydialogChange" :menuindex="menuindex"></buydialog>
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
                
                showLinkStatus:false,
                lx_name:'',
                lx_bid:'',
                lx_tel:'',
				pre_url:app.globalData.pre_url,
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
			idfield:{default:'id'}
		},
		methods: {
			buydialogChange: function (e) {
				if(!this.buydialogShow){
					this.proid = e.currentTarget.dataset.proid
				}
				this.buydialogShow = !this.buydialogShow;
				console.log(this.buydialogShow);
			},
			addcart:function(){
				this.$emit('addcart');
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
.dp-product-item{height: auto; position: relative;overflow: hidden; padding: 0px; display:flex;flex-wrap:wrap}
.dp-product-item .item{display: inline-block;position: relative;margin-bottom: 12rpx;background: #fff;border-radius:10rpx;overflow:hidden}
.dp-product-item .product-pic {width: 100%;height:0;overflow:hidden;background: #ffffff;padding-bottom: 100%;position: relative;}
.dp-product-item .product-pic .image{position:absolute;top:0;left:0;width: 100%;height:auto}
.dp-product-item .product-pic .saleimg{ position: absolute;width: 60px;height: auto; top: -3px; left:-3px;}
.dp-product-item .product-info {padding:20rpx 20rpx;position: relative;}
.dp-product-item .product-info .p1 {color:#323232;font-weight:bold;font-size:28rpx;line-height:36rpx;margin-bottom:10rpx;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:2;overflow:hidden;height:72rpx}
.dp-product-item .product-info .p2{display:flex;align-items:center;overflow:hidden;padding:2px 0}
.dp-product-item .product-info .p2-1{flex-grow:1;flex-shrink:1;height:40rpx;line-height:40rpx;overflow:hidden;white-space: nowrap}
.dp-product-item .product-info .p2-1 .t1{font-size:36rpx;}
.dp-product-item .product-info .p2-1 .t2 {margin-left:10rpx;font-size:24rpx;color: #aaa;text-decoration: line-through;/*letter-spacing:-1px*/}
.dp-product-item .product-info .p2-1 .t3 {margin-left:10rpx;font-size:22rpx;color: #999;}
.dp-product-item .product-info .p2-2{font-size:20rpx;height:40rpx;line-height:40rpx;text-align:right;padding-left:20rpx;color:#999}
.dp-product-item .product-info .p3{color:#999999;font-size:20rpx;margin-top:10rpx;justify-content: space-between;display:flex;}
.dp-product-item .product-info .p3 .p3-1{height:40rpx;line-height:40rpx;border:0 #FF3143 solid;border-radius:10rpx;color:#FF3143;padding:0 24rpx;font-size:24rpx}
.dp-product-item .product-info .p3 .p3-2{color:#999999;font-size:20rpx;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:1;}
.dp-product-item .product-info .p4{width:48rpx;height:48rpx;border-radius:50%;position:absolute;display:relative;bottom:10rpx;right:20rpx;text-align:center;}
.dp-product-item .product-info .p4 .icon_gouwuche{font-size:28rpx;height:48rpx;line-height:48rpx}
.dp-product-item .product-info .p4 .img{width:100%;height:100%}
.bg-desc {color: #fff; padding: 10rpx 20rpx;}

.lianxi{color: #fff;border-radius: 50rpx 50rpx;line-height: 50rpx;text-align: center;font-size: 22rpx;padding: 0 14rpx;display: inline-block;float: right;}
</style>