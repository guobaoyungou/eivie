<template>
    <view style="width: 100%;height: 100%;">
        <block v-if="isload">
            <dd-tab :itemdata="['全部','待接单','处理中','已完成','退款']" :itemst="['all','1','24','5','-2']" :st="st" :isfixed="true" @changetab="changetabs"></dd-tab>
            <view style="width:100%;height:100rpx"></view>
            <view style="overflow: hidden;margin-bottom: 40rpx;">
                <view  v-for="(item, index) in datalist" :key="index" style="width: 700rpx;margin: 0 auto;border-radius: 12rpx;background-color: #fff;padding-bottom: 20rpx;margin-bottom: 20rpx;">
                    <view style="width: 660rpx;margin: 0 auto;">
                        <view style="overflow: hidden;border-bottom: 2rpx #f4f4f4 solid;line-height: 80rpx;">
                            <view style="float:left">
                                <text v-if="item.btntype == 1">帮我送</text>
                                <text v-if="item.btntype == 2">帮我取</text>
                            </view>
                            <view style="float: right;">
                                <text v-if="item.status==0" class="st0">待付款</text>
                                <text v-if="item.status==1" class="st1">待接单</text>
                                <text v-if="item.status==2" class="st2">已接单</text>
                                <text v-if="item.status==3" class="st2">已到店</text>
                                <text v-if="item.status==4" class="st2">配送中</text>
                                <text v-if="item.status==5" class="st3">已完成</text>
                                <text v-if="item.status==-1" class="st3">已取消</text>
                                <text v-if="item.status==-2" class="st3" style="color: red;">退款失败</text>
                            </view>
                        </view>
                        <view style="overflow: hidden;padding: 20rpx;" @tap="goto" :data-url="'detail?id=' + item.id">
                            <view class="black" ></view>
                            <view style="float: left;margin-left: 20rpx;float: left;width: 580rpx;">
                                <view class="first_title">
                                    {{item.take_area}} 
                                </view>
                                <view style="font-size: 30rpx;color: #666666;line-height: 50rpx;">
                                    {{item.take_address}}
                                </view>
                                <view style="font-size: 24rpx;color: #666666;line-height: 50rpx;">
                                    {{item.take_name}} {{item.take_tel}}
                                </view>
                            </view>
                        </view>
                        <view class="red_view"  >
                            <view class="red"></view>
                            <view style="float: left;margin-left: 20rpx;float: left;width: 580rpx;">
                                <view class="first_title">
                                    {{item.send_area}}
                                </view>
                                <view style="font-size: 30rpx;color: #666666;line-height: 50rpx;">
                                    {{item.send_address}}
                                </view>
                                <view style="font-size: 24rpx;color: #666666;line-height: 50rpx;">
                                    {{item.send_name}} {{item.send_tel}}
                                </view>
                            </view>
                        </view>
                        <view style="overflow: hidden;border-top: 2rpx #f4f4f4 solid;border-bottom: 2rpx #f4f4f4 solid;margin: 20rpx 0; line-height: 80rpx;">
                            <view style="float:left">
                                <view>实付：<text style="color: red;">￥{{item.totalprice}}</text></view>
                                <view v-if="item.cancel_fee >0" style="margin-left: 20rpx;">
                                    <view >违约金：<text style="color: red;">￥{{item.cancel_fee}}</text></view>
                                </view>
                                <view v-if="item.refund_status == 2 && item.refund_money >0" style="margin-left: 20rpx;">
                                    <view>已退款：<text style="color: red;">￥{{item.refund_money}}</text></view>
                                </view>
                                <view v-if="item.status != -1 && item.refund_status == 1 && item.refund_money >0" style="margin-left: 20rpx;">
                                    <text style="color: red;">等待退款</text>
                                </view>
                                <view v-if="item.status != -1 && item.refund_status == -1 && item.refund_money >0" style="margin-left: 20rpx;">
                                    <view style="color: red;">退款驳回</view>
                                </view>
                                <view v-if="item.status != -1 && item.refund_status == -2 && item.refund_money>0" style="margin-left: 20rpx;">
                                    <view style="color: red;">退款驳回</view>
                                </view>
                            </view>
                        </view>
                        <view style="overflow: hidden;display: flex;">
                            <view @tap.stop="goto" :data-url="'orderdetail?id=' + item.id" class="btn2">详情</view>
                            <block v-if="item.status>=0 && item.status<=cancel_status">
                                <view class="btn2" @tap.stop="tocancel" :data-id="item.id" data-type="1">取消订单</view>
                            </block>
                            <block v-if="item.status == -2">
                                <view class="btn2" @tap.stop="tocancel" :data-id="item.id" data-type="2">退款</view>
                            </block>
                            <block v-if="item.status == 5 && end_refund_status">
                                <block v-if="item.refund_status ==1 ">
                                    <view v-if="item.refund_money <=0" class="btn2" @tap.stop="tocancel" :data-id="item.id" data-type="20">申请退款</view>
                                </block>
                                <block v-else>
                                    <view v-if="item.refund_status !=2" class="btn2" @tap.stop="tocancel" :data-id="item.id" data-type="20">申请退款</view>
                                </block>
                            </block>
                            <block v-if="item.status==0">
                                <view class="btn1" v-if="item.paytypeid!=5" :style="{background:t('color1')}"
                                    @tap.stop="goto" :data-url="'/pagesExt/pay/pay?id=' + item.payorderid">去付款</view>
                            </block>
                            <block v-if="item.status>=2 && item.status<=5">
                                <view class="btn2" @tap.stop="logistics" :data-id="item.id" :data-express_no="item.express_no" :data-express_type="item.express_type">订单跟踪</view>
                            </block>
                            <!-- <block v-if="item.status==4">
                                <view v-if="item.paytypeid!='4' && (item.balance_pay_status==1 || item.balance_price==0)" class="btn1" :style="{background:t('color1')}" @tap.stop="orderCollect"
                                    :data-id="item.id">
                                    确认收货
                                </view>
                            </block> -->
                            <block v-if="item.status==-1 || item.status==5">
                                <view class="btn2" @tap.stop="todel" :data-id="item.id">删除订单</view>
                            </block>
                        </view>
                    </view>
                </view>
            </view>
            <nomore v-if="nomore"></nomore>
            <nodata v-if="nodata"></nodata>
        </block>
        <view @tap="goto" data-url="/pages/index/index" data-opentype="reLaunch" class="back_index" :style="'background:'+t('color1')" >
            首页
        </view>
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
                nodata: false,
                menuindex: -1,
                pre_url: app.globalData.pre_url,
                
                st: 'all',
                datalist: [],
                pagenum: 1,
                cancel_status:0,
                end_refund_status:false,
            };
        },
        onLoad: function(opt) {
            this.opt = app.getopts(opt);
            
        },
        onShow:function(opt){
            var that = this;
            this.getdata();
        },
        ReachBottom: function () {
            if (!this.nodata && !this.nomore) {
                this.pagenum = this.pagenum + 1;
                this.getdata(true);
            }
        },
        methods: {
            getdata: function (loadmore) {
                if(!loadmore){
                    this.pagenum = 1;
                    this.datalist = [];
                }
                var that = this;
                var pagenum = that.pagenum;
                var st = that.st;
                that.nodata = false;
                that.nomore = false;
                that.loading = true;
                app.post('ApiPaotui/orderlist', {st: st,pagenum: pagenum}, function (res) {
                    that.loading = false;
                    if(res.cancel_status){
                        that.cancel_status = res.cancel_status;
                    }
                    if(res.end_refund_status){
                        that.end_refund_status = res.end_refund_status;
                    }
                    var data = res.datalist;
                    if (pagenum == 1) {
                        that.datalist = data;
                        if (data.length == 0) {
                            that.nodata = true;
                        }
                        that.loaded();
                    }else{
                        if (data.length == 0) {
                            that.nomore = true;
                        } else {
                            var datalist = that.datalist;
                            var newdata = datalist.concat(data);
                            that.datalist = newdata;
                        }
                    }
                });
            },
            changetabs: function (st) {
                this.st = st;
                uni.pageScrollTo({
                    scrollTop: 0,
                    duration: 0
                });
                this.getdata();
            },
            tocancel: function (e) {
                var that = this;
                var orderid = e.currentTarget.dataset.id;
                var type    = e.currentTarget.dataset.type;
                if(type == 1){
                    var msg = '确定要取消该订单吗?';
                }else if(type == 2){
                    var msg = '确定要退款吗?';
                }else if(type == 20){
                    var msg = '确定要申请退款吗?';
                }
                app.confirm(msg, function () {
                    app.showLoading('提交中');
                    app.post('ApiPaotui/cancelOrder', {type:type,orderid: orderid}, function (data) {
                        app.showLoading(false);
                        if(data.status == 1){
                            app.success(data.msg);
                            setTimeout(function () {
                                that.getdata();
                            }, 1000);
                        }else{
                            app.error(data.msg);
                        }
                    });
                });
            },
            todel: function (e) {
                var that = this;
                var orderid = e.currentTarget.dataset.id;
                app.confirm('确定要删除该订单吗?', function () {
                    app.showLoading('删除中');
                    app.post('ApiPaotui/delOrder', {orderid: orderid}, function (data) {
                        app.showLoading(false);
                        app.success(data.msg);
                        setTimeout(function () {
                            that.getdata();
                        }, 1000);
                    });
                });
            },
            logistics:function(e){
                var that = this;
                var express_no  = e.currentTarget.dataset.express_no;
                var express_type  = e.currentTarget.dataset.express_type;
                app.goto('/pagesExt/order/logistics?express_com=同城配送&express_no='+express_no+'&express_type='+express_type);
            },
        },
    }
</script>

<style>
    page{
        width: 100%;
        height: 100%;
    }
    .btn{
        width: 250rpx;display: inline-block;padding: 20rpx;line-height: 70rpx;
    }
    .btn1{
        border-radius: 70rpx;
    }
    .first_title{
        width: 100%;
        overflow: hidden;
        font-size: 30rpx;
        line-height: 40rpx;
    }
    .black{width: 16rpx;height: 16rpx;background-color: #000;border-radius: 50%;float: left;margin-top: 14rpx;}
    .red{width: 16rpx;height: 16rpx;background-color:#FF3A51;border-radius: 50%;float: left;margin-top: 14rpx;}
    .red_view{overflow: hidden;background-color:#F6F6F6;border-radius: 12rpx;padding: 20rpx;}

    .item {
        height: 50px;
        line-height: 50px;
        align-items: center;
        justify-content: center;
        text-align: center;
    }
    .st0{ width: 140rpx; color: #ff8758; text-align: right; }
    .st1{ width: 140rpx; color: #ffc702; text-align: right; }
    .st2{ width: 140rpx; color: #ff4246; text-align: right; }
    .st3{ width: 140rpx; color: #999; text-align: right; }
    .st4{ width: 140rpx; color: #bbb; text-align: right; }
    
    .btn1{margin-left:20rpx; margin-top: 10rpx;max-width:160rpx;height:60rpx;line-height:60rpx;color:#fff;border-radius:3px;text-align:center;padding: 0 20rpx;}
    .btn2{margin-left:20rpx; margin-top: 10rpx;max-width:160rpx;height:60rpx;line-height:60rpx;color:#333;background:#fff;border:1px solid #cdcdcd;border-radius:3px;text-align:center;padding: 0 20rpx;}
    .back_index{position: fixed;bottom: 160rpx;right: 0rpx;width: 100rpx;line-height: 100rpx;border-radius: 50%;text-align: center;color:#fff}
</style>
