<template>
    <view class="container">
        <block v-if="isload">
            <view class="mymoney" :style="{background:t('color1')}">
                <view class="f1">我的{{type_name}}</view>
                <view class="f2"><text style="font-size:26rpx">￥</text>{{money}}</view>
            </view>
            <view class="content">
                <view v-for="(item, index) in datalist" :key="index" class="item">
                    <view class="f1">
                        <text class="t1">{{item.remark}}</text>
                        <text class="t2">{{item.createtime}}</text>
                        <text class="t3">变更后余额: {{item.after}}</text>
                    </view>
                    <view class="f2">
                        <text class="t1" v-if="item.money>0">+{{item.money}}</text>
                        <text class="t2" v-else>{{item.money}}</text>
                    </view>
                </view>
            </view>
            <nomore v-if="nomore"></nomore>
            <nodata v-if="nodata"></nodata>
        </block>
        <loading v-if="loading"></loading>
        <dp-tabbar :opt="opt"></dp-tabbar>
        <popmsg ref="popmsg"></popmsg>
    </view>
</template>

<script>
    var app = getApp();

    export default {
        data() {
            return {
                opt: {},
                loading: false,
                isload: false,
                menuindex: -1,
                
                textset: {},
                datalist: [],
                pagenum: 1,
                nodata: false,
                nomore: false,
                type_name:'',
                money:0
            };
        },
        onLoad: function(opt) {
            var that = this;
            var opt = app.getopts(opt);
            that.opt  = opt;

            that.getdata();

        },
        onPullDownRefresh: function() {
            this.getdata(true);
        },
        onReachBottom: function() {
            if (!this.nodata && !this.nomore) {
                this.pagenum = this.pagenum + 1;
                this.getdata(true);
            }
        },
        methods: {
            getdata: function(loadmore) {
                if (!loadmore) {
                    this.pagenum = 1;
                    this.datalist = [];
                }
                var that = this;
                var pagenum = that.pagenum;
                that.nodata = false;
                that.nomore = false;
                that.loading = true;
                app.post('ApiMy/othermoneylog', {
                    type:'frozen_money',
                    st: 0,
                    pagenum: pagenum
                }, function(res) {
                    that.loading = false;
                    if(res.status == 1){
                        that.money    = res.money;
                        that.type_name= res.type_name;
                        var data = res.data;
                        if (pagenum == 1) {
                            that.textset = app.globalData.textset;
                            uni.setNavigationBarTitle({
                                title: that.type_name + '明细'
                            });
                            that.datalist = data;
                            if (data.length == 0) {
                                that.nodata = true;
                            }
                            that.loaded();
                        } else {
                            if (data.length == 0) {
                                that.nomore = true;
                            } else {
                                var datalist = that.datalist;
                                var newdata = datalist.concat(data);
                                that.datalist = newdata;
                            }
                        }
                    }else{
                       app.alsert(res.msg); 
                    }
                    
                });
            }
        }
    };
</script>
<style>
    .container {
        width: 100%;
        display: flex;
        flex-direction: column
    }

    .content {
        width: 94%;
        margin: 0 3% 20rpx 3%;
    }

    .content .item {
        width: 100%;
        background: #fff;
        margin: 20rpx 0;
        padding: 20rpx 20rpx;
        border-radius: 8px;
        display: flex;
        align-items: center
    }

    .content .item:last-child {
        border: 0
    }

    .content .item .f1 {
        width: 500rpx;
        display: flex;
        flex-direction: column
    }

    .content .item .f1 .t1 {
        color: #000000;
        font-size: 30rpx;
        word-break: break-all;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .content .item .f1 .t2 {
        color: #666666
    }

    .content .item .f1 .t3 {
        color: #666666
    }

    .content .item .f2 {
        flex: 1;
        width: 200rpx;
        font-size: 36rpx;
        text-align: right
    }

    .content .item .f2 .t1 {
        color: #03bc01
    }

    .content .item .f2 .t2 {
        color: #000000
    }

    .content .item .f3 {
        flex: 1;
        width: 200rpx;
        font-size: 32rpx;
        text-align: right
    }

    .content .item .f3 .t1 {
        color: #03bc01
    }

    .content .item .f3 .t2 {
        color: #000000
    }

    .data-empty {
        background: #fff
    }
    
    .mymoney {
        width: 94%;
        margin: 20rpx 3%;
        border-radius: 10rpx 56rpx 10rpx 10rpx;
        position: relative;
        display: flex;
        flex-direction: column;
        padding: 70rpx 0
    }
    
    .mymoney .f1 {
        margin: 0 0 0 60rpx;
        color: rgba(255, 255, 255, 0.8);
        font-size: 24rpx;
    }
    
    .mymoney .f2 {
        margin: 20rpx 0 0 60rpx;
        color: #fff;
        font-size: 64rpx;
        font-weight: bold
    }
    
    .mymoney .f3 {
        height: 56rpx;
        padding: 0 10rpx 0 20rpx;
        border-radius: 28rpx 0px 0px 28rpx;
        background: rgba(255, 255, 255, 0.2);
        font-size: 20rpx;
        font-weight: bold;
        color: #fff;
        display: flex;
        align-items: center;
        position: absolute;
        top: 94rpx;
        right: 0
    }
</style>
