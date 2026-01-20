<template>
<view class="container">
	<block v-if="isload">
		<view class="ordertop" :style="'background:url(' + pre_url + '/static/img/ordertop.png);background-size:100%'">
            <view class="f1" v-if="detail.status==-1">
            	<view class="t1">已关闭</view>
            </view>
			<view class="f1" v-if="detail.status==0">
				<view class="t1">等待买家付款</view>
			</view>
			<view class="f1" v-if="detail.status==1">
				<view class="t1">待取货</view>
			</view>
			<view class="f1" v-if="detail.status==2">
				<view class="t1">入库中</view>
			</view>
			<view class="f1" v-if="detail.status==3">
				<view class="t1">清洗中</view>
			</view>
			<view class="f1" v-if="detail.status==4">
				<view class="t1">送货中</view>
			</view>
            <view class="f1" v-if="detail.status==5">
            	<view class="t1">已完成</view>
            </view>
		</view>
		<view class="address">
			<view class="img">
				<image src="/static/img/address3.png"></image>
			</view>
			<view class="info">
				<text class="t1" user-select="true" selectable="true">{{detail.linkman}} {{detail.tel}}</text>
				<text class="t2"  user-select="true" selectable="true">地址：{{detail.area}}{{detail.address}}</text>
			</view>
		</view>
		<view class="address">
			<view class="img">
				<image src="/static/img/address3.png"></image>
			</view>
			<view class="info">
				<text class="t1" user-select="true" selectable="true">门店：{{detail.md_name}} {{detail.md_tel}}</text>
				<text class="t2"  user-select="true" selectable="true">{{detail.md_address}}</text>
			</view>
		</view>
		<view class="product">
			<view v-for="(item, idx) in detail.prolist" :key="idx" class="content">
				<view @tap="goto">
					<image :src="item.pic"></image>
				</view>
				<view class="detail">
					<text class="t1">{{item.name}}</text>
					<view class="t2 flex flex-y-center flex-bt">
					</view>
					<view class="t3"><text class="x1 flex1">￥{{item.sell_price}}</text><text class="x2">×{{item.num}}</text></view>
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
			<view class="item" v-if="detail.status>0 && detail.paytime">
				<text class="t1">支付时间</text>
				<text class="t2">{{detail.paytime}}</text>
			</view>
			<view class="item" v-if="detail.status==2 && detail.qh_time">
				<text class="t1">取货时间</text>
				<text class="t2">{{detail.qh_time}}</text>
			</view>
            <view class="item" v-if="detail.status==3 && detail.rk_time">
            	<text class="t1">取货时间</text>
            	<text class="t2">{{detail.rk_time}}</text>
            </view>
            <view class="item" v-if="detail.status==4 && detail.qx_time">
            	<text class="t1">取货时间</text>
            	<text class="t2">{{detail.qx_time}}</text>
            </view>
			<view class="item" v-if="detail.status==5 && detail.end_time">
				<text class="t1">完成时间</text>
				<text class="t2">{{detail.end_time}}</text>
			</view>
		</view>
		<view class="orderinfo">
			<view class="item">
				<text class="t1">商品金额</text>
				<text class="t2 red">¥{{detail.product_price}}</text>
			</view>
			<view class="item" v-if="detail.peisong_fee > 0">
				<text class="t1">配送费</text>
				<text class="t2 red">+¥{{detail.peisong_fee}}</text>
			</view>
			<view class="item" v-if="detail.coupon_money > 0">
				<text class="t1">{{t('优惠券')}}抵扣</text>
				<text class="t2 red">-¥{{detail.coupon_money}}</text>
			</view>
			<view class="item">
				<text class="t1">实付款</text>
				<text class="t2 red">¥{{detail.totalprice}}</text>
			</view>

			<view class="item">
				<text class="t1">订单状态</text>
                <text class="t2" v-if="detail.status==-1">已关闭</text>
				<text class="t2" v-if="detail.status==0">未付款</text>
				<text class="t2" v-if="detail.status==1">待取货</text>
				<text class="t2" v-if="detail.status==2">入库中</text>
				<text class="t2" v-if="detail.status==3">清洗中</text>
				<text class="t2" v-if="detail.status==4">送货中</text>
                <text class="t2" v-if="detail.status==5">已完成</text>
			</view>
			<view class="item" v-if="detail.refund_status>0">
				<text class="t1">退款状态</text>
				<text class="t2 red" v-if="detail.refund_status==1">审核中,¥{{detail.refund_money}}</text>
				<text class="t2 red" v-if="detail.refund_status==2">已退款,¥{{detail.refund_money}}</text>
				<text class="t2 red" v-if="detail.refund_status==3">已驳回,¥{{detail.refund_money}}</text>
			</view>
            <view class="item">
            	<text class="t1">下单类型</text>
                <text class="t2" v-if="detail.buy_type==1">上门取件</text>
            	<text class="t2" v-if="detail.buy_type==2">送货到店</text>
            </view>
            <view class="item" v-if="detail.yy_time">
            	<text class="t1">上门取件时间</text>
            	<text class="t2">{{detail.yy_time}}</text>
            </view>
		</view>

		<view style="width:100%;height:160rpx"></view>
		<view class="bottom notabbarbot" v-if="detail.status==1 || detail.status==2 || detail.status==3">

			<block v-if="detail.status==1">
                <view class="btn1" @tap="changeXixieStatus" :data-id="detail.id" data-status="2" data-name="取货完成">取货完成</view>
			</block>

			<block v-if="detail.status==2">
				<view class="btn1" @tap="changeXixieStatus" :data-id="detail.id" data-status="3" data-name="入库完成">入库完成</view>
			</block>
            <block v-if="detail.status==3">
            	<view class="btn1" @tap="changeXixieStatus" :data-id="detail.id" data-status="4" data-name="清洗完成">清洗完成</view>
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

export default {
  data() {
    return {
        opt:{},
        loading:false,
        isload: false,
        menuindex:-1,

        pre_url:app.globalData.pre_url,
        textset:{},
        detail:{},
        team:{},
        storeinfo:{},
        shopset:{},
        invoice:0
    }
  },

  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.getdata();
  },
	onPullDownRefresh: function () {
		this.getdata();
	},
  methods: {
		getdata: function (option) {
			var that = this;
			that.loading = true;
			app.get('ApiXixie/mendian_orderdetail', {id: that.opt.id}, function (res) {
				that.loading = false;
                if(res.status == 1){
                    that.detail = res.detail;
                    that.team = res.team;
                    that.storeinfo = res.storeinfo;
                    that.shopset = res.shopset;
                    that.textset = app.globalData.textset;
                    that.invoice = res.invoice;
                    that.loaded();
                }else{
                    app.alert(res.msg);
                }
				
			});
		},
        changeXixieStatus: function (e) {
            var that = this;
            var orderid = e.currentTarget.dataset.id;
            var status  = e.currentTarget.dataset.status;
            var name    = e.currentTarget.dataset.name;
            app.confirm('确定'+name+'吗?', function () {
                app.showLoading('提交中');
                app.post('ApiXixie/changeMendianStatus', {id: orderid,status:status}, function (data) {
                    app.showLoading(false);
                    if(data.status == 1){
                        app.success(data.msg);
                        setTimeout(function () {
                            that.getdata();
                        }, 1000);
                    }else{
                        app.alsert(data.msg)
                    }
                });
            });
        },
  }
};
</script>
<style>
.ordertop{width:100%;height:220rpx;padding:50rpx 0 0 70rpx}
.ordertop .f1{color:#fff}
.ordertop .f1 .t1{font-size:32rpx;height:60rpx;line-height:60rpx}
.ordertop .f1 .t2{font-size:24rpx}

.address{ display:flex;width: 100%; padding: 20rpx 3%; background: #FFF;}
.address .img{width:40rpx}
.address image{width:40rpx; height:40rpx;}
.address .info{flex:1;display:flex;flex-direction:column;}
.address .info .t1{font-size:28rpx;font-weight:bold;color:#333}
.address .info .t2{font-size:24rpx;color:#999}

.product{width:94%;margin:0 3%;border-radius:8rpx;margin-top:16rpx;padding: 14rpx 3%;background: #FFF;}
.product .content{display:flex;position:relative;width: 100%; padding:16rpx 0px;border-bottom: 1px #e5e5e5 dashed;position:relative}
.product .content:last-child{ border-bottom: 0; }
.product .content image{ width: 140rpx; height: 140rpx;}
.product .content .detail{display:flex;flex-direction:column;margin-left:14rpx;flex:1}
.product .content .detail .t1{font-size:26rpx;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:2;overflow:hidden;}
.product .content .detail .t2{color: #999;font-size: 26rpx;margin-top: 10rpx;}
.product .content .detail .t3{display:flex;color: #ff4246;margin-top: 10rpx;}
.product .content .detail .t4{margin-top: 10rpx;}
.product .content .detail .x1{ flex:1}
.product .content .detail .x2{ width:100rpx;font-size:32rpx;text-align:right;margin-right:8rpx}
.product .content .comment{position:absolute;top:64rpx;right:10rpx;border: 1px #ffc702 solid; border-radius:10rpx;background:#fff; color: #ffc702;  padding: 0 10rpx; height: 46rpx; line-height: 46rpx;}

.orderinfo{width:94%;margin:0 3%;border-radius:8rpx;margin-top:16rpx;padding: 14rpx 3%;background: #FFF;}
.orderinfo .item{display:flex;width:100%;padding:20rpx 0;border-bottom:1px dashed #ededed;}
.orderinfo .item:last-child{ border-bottom: 0;}
.orderinfo .item .t1{width:200rpx;}
.orderinfo .item .t2{flex:1;text-align:right}
.orderinfo .item .red{color:red}

.bottom{ width: 100%;height:92rpx;padding: 0 20rpx;background: #fff; position: fixed; bottom: 0px; left: 0px;display:flex;justify-content:flex-end;align-items:center;}
.btn1{margin-left:20rpx;width:160rpx;height:60rpx;line-height:60rpx;color:#fff;background:#FB4343;border-radius:3px;text-align:center}
.btn2{margin-left:20rpx;width:160rpx;height:60rpx;line-height:60rpx;color:#333;background:#fff;border:1px solid #cdcdcd;border-radius:3px;text-align:center}
.btn3{font-size:24rpx;width:120rpx;height:50rpx;line-height:50rpx;color:#333;background:#fff;border:1px solid #cdcdcd;border-radius:3px;text-align:center}

.hxqrbox{background:#fff;padding:50rpx;position:relative;border-radius:20rpx}
.hxqrbox .img{width:400rpx;height:400rpx}
.hxqrbox .txt{color:#666;margin-top:20rpx;font-size:26rpx;text-align:center}
.hxqrbox .close{width:50rpx;height:50rpx;position:absolute;bottom:-100rpx;left:50%;margin-left:-25rpx;border:1px solid rgba(255,255,255,0.5);border-radius:50%;padding:8rpx}
</style>