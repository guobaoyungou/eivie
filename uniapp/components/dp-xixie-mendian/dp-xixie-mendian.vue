<template>
    <view style="width: 100%;background-color: #fff;">
        <view style="width: 700rpx;margin: 0 auto;padding-bottom: 20rpx;">
            <view @tap="selAddress" style="overflow: hidden;font-size: 30rpx;font-weight: bold;height: 80rpx;line-height: 80rpx;">
                <view style="width: 40rpx;float: left;margin-right: 10rpx;">
                    <view style="width: 40rpx;margin: 0 auto;overflow: hidden;">
                        <image src="/static/img/address3.png" style="width: 40rpx;height: 40rpx;float: left;margin-top: 20rpx;"></image>
                    </view>
                </view>
                <view style="width: 600rpx;white-space: nowrap;;float: left;overflow: hidden;">
                    <text>送至:</text>
                    <text>{{mendian_data.m_address?mendian_data.m_address:'无收货地址，请选择'}}</text>
                </view>
                <image src="/static/img/arrowright.png" style="width: 30rpx;height: 30rpx;float: right;margin-top: 24rpx;"></image>
            </view>
            <view style="overflow: hidden;font-size: 30rpx;font-weight: bold;height: 80rpx;line-height: 80rpx;">
                <view @tap="selMendian" style="width: 40rpx;float: left;margin-right: 10rpx;">
                    <view style="width: 30rpx;margin: 0 auto;">
                        <image src="/static/img/address3.png" style="width: 30rpx;height: 30rpx;float: left;margin-top: 24rpx;"></image>
                    </view>
                </view>
                <view @tap="selMendian" style="width: 600rpx;white-space: nowrap;;float: left;overflow: hidden;">
                    <text>{{mendian_data.name?mendian_data.name:'附近暂无门店'}}</text>
                </view>
                <image @tap="callMobile" :data-mobile="mendian_data.tel" src="/static/img/mobile.png" style="width: 30rpx;height: 30rpx;float: right;margin-top:24rpx;"></image>
            </view>
            <view style="overflow: hidden;font-size: 28rpx;">
                <text>{{mendian_data.address?mendian_data.address:''}}</text>
            </view>
        </view>
    </view>
</template>
<script>
    var app = getApp();
	export default {
		data(){
			return {
			}
		},
		props: {
            mendian_data:{default:''},
		},
		methods: {
            selAddress:function(){
                var that = this;
                var mendian_data = that.mendian_data;
                if(!mendian_data || !mendian_data.m_address){
                    this.$emit('changePopupAddress',true);
                }else{
                    var frompage = encodeURIComponent('/pages/index/index');
                    app.goto('/pages/address/address?fromPage='+frompage+'&type=1')
                }
            },
            selMendian:function(){
                var that = this;
                var mendian_data = that.mendian_data;
                if(!mendian_data || !mendian_data.m_address){
                    this.$emit('changePopupAddress',true);
                }
            },
            callMobile:function(e){
                var that = this;
                var mobile     = e.currentTarget.dataset.mobile;
                if(mobile){
                    uni.makePhoneCall({
                        phoneNumber: mobile
                    })
                }else{
                    app.alert('暂无可拨打电话');
                }
            }
		}
	}
</script>
<style>

</style>