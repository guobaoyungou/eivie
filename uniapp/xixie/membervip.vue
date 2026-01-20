<template>
    <view v-if="isload">
        <view style="width: 100%;background-color: #292728;height: 400rpx;overflow: hidden;">
            <view style="width: 700rpx;margin: 0 auto;margin-top: 40rpx;">
                <view style="overflow: hidden;">
                    <view
                        style="width: 80rpx;height: 80rpx;float: left;overflow: hidden;border-radius: 50%;border: 2rpx solid #fff;margin-right: 20rpx;">
                        <image :src="userinfo.headimg" style="width: 100%;height: 100%;"></image>
                    </view>
                    <view style="float: left;color:#ffff;font-size: 24rpx;line-height: 40rpx;">
                        <view style="white-space: nowrap;overflow: hidden;">{{userinfo.nickname}}</view>
                        <view>
                            <block v-if="userinfo.is_vip == 1">
                                已开通
                            </block>
                            <block v-else>
                                未开通
                            </block>
                            
                        </view>
                    </view>
                </view>
                <view
                    style="background-color: #F1DDC8;width: 100%;border-radius: 20rpx 20rpx;height: 200rpx;margin-top: 40rpx;overflow: hidden;padding: 40rpx;">
                    <view style="margin-top: 20rpx;">
                        <view style="float: left;font-size: 40rpx;font-weight: bold;">
                            开通会员
                        </view>
                        <view
                            style="float: right;line-height: 40rpx;padding: 20rpx;background-color: #262324;color: #fff;border-radius: 40rpx;">
                            ￥{{set.fee}} 开通
                        </view>
                    </view>
                </view>
            </view>
        </view>
        <view style="width: 700rpx;margin: 0 auto;margin-top: 40rpx;">
            <view style="line-height: 60rpx;font-size: 34rpx;font-weight: bold;overflow: hidden;">
                <view
                    style="height: 34rpx;float: left;margin-top: 16rpx;border-right:4rpx solid #D41A1E ;margin-right: 20rpx;">
                </view>
                <text>使用说明</text>
            </view>
            <view style="padding: 20rpx;">
                <parse :content="set.content"></parse>
            </view>
        </view>
        <view style="width: 700rpx;margin: 0 auto;margin-top: 60rpx;">
            <view @tap='recharge' class="btn" :style="{background:t('color1')}">
                <!-- <text>充值</text> -->
                <text>{{set.fee}}</text>
                <text>元开通会员</text>
            </view>
        </view>
    </view>
</template>

<script>
    var app = getApp();
    export default {
        data() {
            return {
                opt: {},
                isload: false,
                set:'',
                userinfo:''
            }
        },
        onLoad: function(opt) {
            this.opt = app.getopts(opt);
            this.getdata();
        },
        onPullDownRefresh: function(e) {
            this.getdata();
        },
        methods: {
            getdata: function() {
                var that = this;
                app.post('ApiXixie/xixie_vip', {id:0}, function(res) {
                    if(res.status == 1){
                        that.isload = true;
                        that.set = res.set;
                        that.userinfo= res.userinfo;
                    }else{
                        app.alert(res.msg);
                    }
                });
            },
            recharge:function(){
                app.post('ApiXixie/recharge', {id:0}, function(res) {
                    if(res.status == 1){
                        app.goto('/pages/pay/pay?id='+res.payorderid);
                    }else{
                        app.alert(res.msg);
                    }
                });
            }
        }
    }
</script>

<style>
    .btn {
        width: 100%;
        line-height: 110rpx;
        text-align: center;
        color: #fff;
        font-size: 40rpx;
        border-radius: 12rpx;
        font-weight: bold;
    }
</style>
