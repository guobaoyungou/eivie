<template>
    <view>
        <view class="buy_height" style="height:calc(120rpx + env(safe-area-inset-bottom));width: 100%;"></view>
        <view class="buy_content" style="width: 100%;position: fixed;bottom:calc(110rpx + env(safe-area-inset-bottom));line-height:100rpx;height:100rpx;background-color: #fff;z-index: 99;">
            <view style="width: 700rpx;margin: 0 auto;height:100rpx;">
                <view @tap="goto" data-url="/pages/shop/cart" style="position: relative;width: 100rpx;height: 100rpx;border-radius: 50%;float: left;margin-top: -30rpx;">
                    <view class="buycart" :style="'background-color:'+color">
                        <text class="iconfont icon_gouwuche" style="color: #fff;font-size: 56rpx;"></text>
                    </view>
                    <view style="position: absolute;background-color: red;line-height: 30rpx;font-size: 24rpx;color: #fff;padding: 0 6rpx;top:0rpx;right: 0;;border-radius: 30rpx;">
                        {{cartnum?cartnum:0}}
                    </view>
                </view>
                <view style="width: 400rpx;float: left;margin-left: 20rpx;color: #D41A1E;font-weight: bold;font-size: 28rpx;">
                    <text style="font-size: 30rpx;">￥</text>
                    <text>{{cartprice?cartprice:0}}</text>
                </view>
                <view v-if="cartnum>0" @tap="gobuy" class="go_buy" :style="'background-color:'+color">
                    去结算
                </view>
                <view v-else class="go_buy" :style="'background-color:rgba('+colorrgb+',0.4)'">
                    去结算
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
                pre_url:'',
                // cartnum:0,
                // cartprice:0,
        	}
        },
		props: {
            color:{default:''},
            colorrgb:{default:''},
            cartnum:{default:'0'},
            cartprice:{default:'0'}
		},
        mounted:function(){
            var that = this;
            that.pre_url = app.globalData.pre_url;
        },
        methods: {
            gobuy:function(){
                var that = this;
                var cartnum   = that.cartnum;
                var cartprice = that.cartprice;
                if(cartnum<=0 || cartprice<=0){
                    app.alert('请选择要清洗的商品');
                    return;
                }
                app.goto('/xixie/buy?gotype=all');
            }
        }
	}
</script>

<style>
    .buycart{width: 100rpx;height: 100rpx;overflow: hidden;border-radius: 50%;text-align: center;line-height: 100rpx;}
    .go_buy{width: 150rpx;float: right;color: #ffff;line-height: 60rpx;border-radius: 60rpx;text-align: center;margin-top: 20rpx;}
</style>