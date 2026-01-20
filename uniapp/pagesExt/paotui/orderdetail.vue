<template>
<view class="container">
	<block v-if="isload">
		<view class="ordertop" :style="'background:url('+( pre_url + '/static/img/ordertop.png')+');background-size:100%;overflow:hidden'">
            <view style="width: 660rpx;margin:0 auto;margin-top: 70rpx;">
                <view class="f1" v-if="detail.status==0">
                	<view class="t1">待付款</view>
                </view>
                <view class="f1" v-if="detail.status==1">
                	<view class="t1">待接单</view>
                </view>
                <view class="f1" v-if="detail.status==2">
                	<view class="t1">已接单</view>
                </view>
                <view class="f1" v-if="detail.status==3">
                	<view class="t1">已到店</view>
                </view>
                <view class="f1" v-if="detail.status==4">
                	<view class="t1">配送中</view>
                </view>
                <view class="f1" v-if="detail.status==5">
                	<view class="t1">已完成</view>
                </view>
                <view class="f1" v-if="detail.status==-1">
                	<view class="t1">已取消</view>
                </view>
                <view class="f1" v-if="detail.status==-2">
                	<view class="t1">退款失败</view>
                </view>
            </view>
		</view>
        <view style="width: 100%;background-color: #fff;padding: 20rpx 0;">
            <view v-if="detail.btntype == 1" style="width: 700rpx;margin: 0 auto;background-color: #fff;">
                <view style="overflow: hidden;border-bottom: 2rpx #f4f4f4 solid;line-height: 80rpx;">
                    <view style="float:left">
                        帮我送
                    </view>
                </view>
                <view style="overflow: hidden;padding: 20rpx;" @tap="goto" :data-url="'detail?id=' + detail.id">
                    <view class="black" ></view>
                    <view style="float: left;margin-left: 20rpx;float: left;width: 580rpx;">
                        <view class="first_title">
                            {{detail.take_area}} 
                        </view>
                        <view style="font-size: 30rpx;color: #666666;line-height: 50rpx;">
                            {{detail.take_address}}
                        </view>
                        <view style="font-size: 24rpx;color: #666666;line-height: 50rpx;">
                            {{detail.take_name}} {{detail.take_tel}}
                        </view>
                    </view>
                </view>
                <view class="red_view"  >
                    <view class="red"></view>
                    <view style="float: left;margin-left: 20rpx;float: left;width: 580rpx;">
                        <view class="first_title">
                            {{detail.send_area}}
                        </view>
                        <view style="font-size: 30rpx;color: #666666;line-height: 50rpx;">
                            {{detail.send_address}}
                        </view>
                        <view style="font-size: 24rpx;color: #666666;line-height: 50rpx;">
                            {{detail.send_name}} {{detail.send_tel}}
                        </view>
                    </view>
                </view>
            </view>
            <view v-if="detail.btntype == 2" style="width: 700rpx;margin: 0 auto;background-color: #fff;">
                <view style="overflow: hidden;border-bottom: 2rpx #f4f4f4 solid;line-height: 80rpx;">
                    <view style="float:left">
                        帮我取
                    </view>
                </view>
                <view class="red_view" style="margin-top: 0;" @tap="goto" :data-url="'detail?id=' + detail.id">
                    <view class="red"></view>
                    <view style="float: left;margin-left: 20rpx;float: left;width: 580rpx;">
                        <view class="first_title">
                            {{detail.take_area}}
                        </view>
                        <view style="font-size: 30rpx;color: #666666;line-height: 50rpx;">
                            {{detail.take_address}}
                        </view>
                        <view style="font-size: 24rpx;color: #666666;line-height: 50rpx;">
                            {{detail.take_name}} {{detail.take_tel}}
                        </view>
                    </view>
                </view>
                <view style="overflow: hidden;padding: 20rpx;margin-top: 20rpx;" >
                    <view class="black" ></view>
                    <view style="float: left;margin-left: 20rpx;float: left;width: 580rpx;">
                        <view class="first_title">
                            {{detail.send_area}} 
                        </view>
                        <view style="font-size: 30rpx;color: #666666;line-height: 50rpx;">
                            {{detail.send_address}}
                        </view>
                        <view style="font-size: 24rpx;color: #666666;line-height: 50rpx;">
                            {{detail.send_name}} {{detail.send_tel}}
                        </view>
                    </view>
                </view>
            </view>
        </view>
        
		<view class="orderinfo">
			<view class="item">
				<text class="t1">订单编号</text>
				<text class="t2" user-select="true" selectable="true">{{detail.ordernum}}</text>
			</view>
			<view class="item">
				<text class="t1">下单时间</text>
				<text class="t2">{{detail.createtime}}</text>
			</view>
			<view class="item" v-if="detail.status>0 && detail.paytypeid!='4' && detail.paytime">
				<text class="t1">支付时间</text>
				<text class="t2">{{detail.paytime}}</text>
			</view>
			<view class="item" v-if="detail.paytypeid">
				<text class="t1">支付方式</text>
				<text class="t2">{{detail.paytype}}</text>
			</view>

			<view class="item" v-if="detail.status>=2 && detail.starttime">
				<text class="t1">接单时间</text>
				<text class="t2">{{detail.starttime}}</text>
			</view>
            <view class="item" v-if="detail.status>=3 && detail.daodiantime">
            	<text class="t1">到店时间</text>
            	<text class="t2">{{detail.daodiantime}}</text>
            </view>
            <view class="item" v-if="detail.status>=4 && detail.quhuotime">
            	<text class="t1">取货时间</text>
            	<text class="t2">{{detail.quhuotime}}</text>
            </view>
			<view class="item" v-if="detail.status==5 && detail.endtime">
				<text class="t1">完成时间</text>
				<text class="t2">{{detail.endtime}}</text>
			</view>
		</view>
		<view class="orderinfo">
			<view class="item">
				<text class="t1">物品</text>
				<text class="t2">{{detail.name}}</text>
			</view>
            <view class="item" v-if="detail.pic">
            	<text class="t1">物品图片</text>
            	<view class="t2" ><image :src="detail.pic" style="width:400rpx;height:auto" mode="widthFix" @tap="previewImage" :data-url="detail.pic"/></view>
            </view>
			<view class="item" >
				<text class="t1">重量</text>
				<text class="t2">{{detail.weight}}公斤</text>
			</view>
			<view class="item">
				<text class="t1">取件时间</text>
				<text class="t2">{{detail.take_time}}</text>
			</view>
            <view class="item">
				<text class="t1">备注</text>
				<text class="t2">{{detail.remark}}</text>
			</view>
			<view class="item" v-if="detail.distance_fee > 0">
				<text class="t1">距离费用</text>
				<text class="t2" style="color: red;">+¥{{detail.distance_fee}}</text>
			</view>
            <view class="item" v-if="detail.weight_fee > 0">
            	<text class="t1">重量费用</text>
            	<text class="t2" style="color: red;">+¥{{detail.weight_fee}}</text>
            </view>
            <view class="item" v-if="detail.tip_fee > 0">
            	<text class="t1">小费</text>
            	<text class="t2" style="color: red;">+¥{{detail.tip_fee}}</text>
            </view>
			<view class="item" v-if="detail.time_fee > 0">
				<text class="t1">特殊时间段附加</text>
				<text class="t2" style="color: red;">+¥{{detail.time_fee}}</text>
			</view>
			
			<view class="item" v-if="detail.dt_fee > 0">
				<text class="t1">动态溢价</text>
				<text class="t2" style="color: red;">+¥{{detail.dt_fee}}</text>
			</view>
			<view class="item">
				<text class="t1">实付款</text>
				<text class="t2" style="color: red;"> ¥{{detail.totalprice}}</text>
			</view>
            <view class="item" v-if="detail.cancel_fee>0">
            	<text class="t1">违约金</text>
            	<text class="t2" style="color: red;">¥{{detail.cancel_fee}}</text>
            </view>
			<view class="item" v-if="detail.refund_status == 2 && detail.refund_money>0">
				<text class="t1">已退款</text>
				<text class="t2" style="color: red;">¥{{detail.refund_money}}</text>
			</view>
            <view class="item" v-if="detail.status != -1 && detail.refund_status == 1 && detail.refund_money>0">
            	<text class="t1">退款状态</text>
            	<text class="t2" style="color: red;">等待退款</text>
            </view>
            <view class="item" v-if="detail.status != -1 && detail.refund_status == -1">
            	<text class="t1">退款状态</text>
            	<text class="t2" style="color: red;">退款驳回</text>
            </view>
            <view class="item" v-if="detail.status != -1 && detail.refund_status == -2 && detail.refund_money>0">
            	<text class="t1">退款状态</text>
            	<text class="t2" style="color: red;">退款失败</text>
            </view>
		</view>

		<view style="width:100%;height:calc(160rpx + env(safe-area-inset-bottom));"></view>
		<view v-if="detail.status != -1" class="bottom notabbarbot">
            <block v-if="detail.status>=0 && detail.status<=cancel_status">
                <view class="btn2" @tap.stop="tocancel" :data-id="detail.id" data-type="1">取消订单</view>
            </block>
            <block v-if="detail.status == -2">
                <view class="btn2" @tap.stop="tocancel" :data-id="detail.id" data-type="2">退款</view>
            </block>
            <block v-if="detail.status == 5 && end_refund_status">
                <block v-if="detail.refund_status ==1 ">
                    <view v-if="detail.refund_money <=0" class="btn2" @tap.stop="tocancel" :data-id="detail.id" data-type="20">申请退款</view>
                </block>
                <block v-else>
                    <view v-if="detail.refund_status !=2" class="btn2" @tap.stop="tocancel" :data-id="detail.id" data-type="20">申请退款</view>
                </block>
            </block>
			<block v-if="detail.status==0">
			    <view class="btn1" v-if="detail.paytypeid!=5" :style="{background:t('color1')}"
			        @tap.stop="goto" :data-url="'/pagesExt/pay/pay?id=' + detail.payorderid">去付款</view>
			</block>
			<block v-if="detail.status>=2 && detail.status<=4">
			    <view class="btn2" @tap.stop="logistics" :data-id="detail.id" :data-express_no="detail.express_no" :data-express_type="detail.express_type">订单跟踪</view>
			</block>
            <!-- <block v-if="detail.status==4">
                <view v-if="detail.paytypeid!='4' && (detail.balance_pay_status==1 || detail.balance_price==0)" class="btn1" :style="{background:t('color1')}" @tap.stop="orderCollect"
                    :data-id="detail.id">
                    确认收货
                </view>
            </block> -->
            <block v-if="detail.status==-1 || detail.status==5">
                <view class="btn2" @tap.stop="todel" :data-id="detail.id">删除订单</view>
            </block>
		</view>
	</block>
	<loading v-if="loading"></loading>
	<dp-tabbar :opt="opt"></dp-tabbar>
	<popmsg ref="popmsg"></popmsg>
</view>
</template>

<script>
var app = getApp();
var interval = null;

export default {
    data() {
        return {
            opt:{},
            loading:false,
            isload: false,
            menuindex:-1,
            pre_url:app.globalData.pre_url,
            detail: "",
            cancel_status:0,
            end_refund_status:false
        };
    },

    onLoad: function (opt) {
        this.opt = app.getopts(opt);
    },
    onShow:function(){
		this.getdata();
    },
	onPullDownRefresh: function () {
		this.getdata();
	},
    onUnload: function () {
        //clearInterval(interval);
    },
  methods: {
		getdata: function () {
			var that = this;
			that.loading = true;
			app.get('ApiPaotui/orderdetail', {id: that.opt.id}, function (res) {
				that.loading = false;
                if(res.cancel_status){
                    that.cancel_status = res.cancel_status;
                }
                if(res.end_refund_status){
                    that.end_refund_status = res.end_refund_status;
                }
                if(res.status == 1){
                    that.detail = res.detail;
                    that.loaded();
                }else{
                    app.alert(res.msg)
                }
			});
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
    }
};
</script>
<style>
.text-min { font-size: 24rpx; color: #999;}
.ordertop{width:100%;height:220rpx;}

.ordertop .f1{color:#fff}
.ordertop .f1 .t1{font-size:32rpx;height:60rpx;line-height:60rpx}
.ordertop .f1 .t2{font-size:24rpx}

.orderinfo{width:700rpx;margin:0 auto;border-radius:8rpx;margin-top:16rpx;padding: 20rpx;background: #FFF;}
.orderinfo .item{display:flex;width:100%;padding:20rpx 0;border-bottom:1px dashed #ededed;overflow:hidden}
.orderinfo .item:last-child{ border-bottom: 0;}
.orderinfo .item .t1{width:200rpx;flex-shrink:0}
.orderinfo .item .t2{flex:1;text-align:right}
.orderinfo .item .t3{ margin-top: 3rpx;}
.orderinfo .item .red{color:red}
.first_title{
    width: 100%;
    overflow: hidden;
    font-size: 30rpx;
    line-height: 40rpx;
}
.black{width: 16rpx;height: 16rpx;background-color: #000;border-radius: 50%;float: left;margin-top: 14rpx;}
.red{width: 16rpx;height: 16rpx;background-color:#FF3A51;border-radius: 50%;float: left;margin-top: 14rpx;}
.red_view{overflow: hidden;background-color:#F6F6F6;border-radius: 12rpx;padding: 20rpx;}
    
.bottom{ width: 100%; height:calc(92rpx + env(safe-area-inset-bottom));background: #fff; position: fixed; bottom: 0px;left: 0px;display:flex;justify-content:flex-end;align-items:center;padding: 0 20rpx;}

.btn { border-radius: 10rpx;color: #fff;}
.btn1{height:60rpx;line-height:60rpx;color:#fff;border-radius:3px;text-align:center;flex-shrink: 0;margin: 0 0 0 15rpx;padding: 0 15rpx;}
.btn2{height:60rpx;line-height:60rpx;color:#333;background:#fff;border:1px solid #cdcdcd;border-radius:3px;text-align:center;padding: 0 15rpx;flex-shrink: 0;margin: 0 0 0 15rpx;}
.btn3{font-size:24rpx;height:50rpx;line-height:50rpx;color:#333;background:#fff;border:1px solid #cdcdcd;border-radius:3px;text-align:center;padding: 0 15rpx;flex-shrink: 0;margin: 0 0 0 15rpx;}

</style>