<template>
<view class="container">
	<block v-if="isload">
		<view class="address">
			<view class="img">
				<image :src="pre_url+'/static/img/address3.png'"></image>
			</view>
			<view class="info">
				<view class="t1" user-select="true" selectable="true">{{detail.linkman}} <text v-if="detail.tel" @tap="goto" :data-url="'tel:'+detail.tel" style="margin-left: 20rpx;">{{detail.tel}}</text></view>
				<text class="t2" v-if="detail.freight_type!=1 && detail.freight_type!=3" user-select="true" selectable="true">地址：{{detail.area}}{{detail.address}}</text>
				<text class="t2" v-if="detail.freight_type==1" @tap="openLocation" :data-address="storeinfo.address" :data-latitude="storeinfo.latitude" :data-longitude="storeinfo.longitude" user-select="true" selectable="true">取货地点：{{storeinfo.name}} - {{storeinfo.address}}</text>
      </view>
		</view>
		<view class="orderinfo">
			<view class="item">
				<text class="t1">订单金额</text>
				<text class="t2 red">¥{{detail.product_price}}</text>
			</view>
			<view class="item" v-if="detail.leveldk_money > 0">
				<text class="t1">{{t('会员')}}折扣</text>
				<text class="t2 red">-¥{{detail.leveldk_money}}</text>
			</view>
			<view class="item" v-if="detail.manjian_money > 0">
				<text class="t1">满减活动</text>
				<text class="t2 red">-¥{{detail.manjian_money}}</text>
			</view>
			<view class="item" v-if="detail.coupon_money > 0">
				<text class="t1">{{t('优惠券')}}抵扣</text>
				<text class="t2 red">-¥{{detail.coupon_money}}</text>
			</view>
			
			<view class="item" v-if="detail.scoredk_money > 0">
				<text class="t1">{{t('积分')}}抵扣</text>
				<text class="t2 red">-¥{{detail.scoredk_money}}</text>
			</view>
			<view class="item" v-if="detail.dec_money > 0">
				<text class="t1">{{t('余额')}}抵扣</text>
				<text class="t2 red">-¥{{detail.dec_money}}</text>
			</view>
			<view class="item">
				<text class="t1">实付金额</text>
				<text class="t2 red">¥{{detail.totalprice}}</text>
			</view>
			<view class="item">
				<text class="t1">下单时间</text>
				<text class="t2">{{detail.createtime}}</text>
			</view>
			<view class="item">
				<text class="t1">订单状态</text>
				<text class="t2" v-if="detail.status==0">未付款</text>
				<text class="t2" v-if="detail.status==1">待发货</text>
				<text class="t2" v-if="detail.status==2">已发货</text>
				<text class="t2" v-if="detail.status==3">已收货</text>
				<text class="t2" v-if="detail.status==4">已关闭</text>
			</view>
			<view class="item" v-if="detail.refund_status>0">
				<text class="t1">退款状态</text>
				<text class="t2 red" v-if="detail.refund_status==1">审核中,¥{{detail.refund_money}}</text>
				<text class="t2 red" v-if="detail.refund_status==2">已退款,¥{{detail.refund_money}}</text>
				<text class="t2 red" v-if="detail.refund_status==3">已驳回,¥{{detail.refund_money}}</text>
			</view>
			<view class="item" v-if="detail.refund_status>0">
				<text class="t1">退款原因</text>
				<text class="t2 red">{{detail.refund_reason||'暂无'}}</text>
			</view>
			<view class="item" v-if="detail.refund_checkremark">
				<text class="t1">审核备注</text>
				<text class="t2 red">{{detail.refund_checkremark}}</text>
			</view>
		</view>
		<view class="product">
			<view class="colitem row-header">
				<view class="col col-1">商品</view>
				<view class="col col-2">单价(元/斤)</view>
				<view class="col col-3">应拣(斤)</view>
				<view class="col col-4">实拣(斤)</view>
				<view class="col col-5">总价</view>
			</view>
			<view v-for="(item, idx) in prolist" :key="idx" class="colitem">
				<view class="col col-1">
					<view>{{item.name}}</view>
					<view>{{item.ggname}}</view>
				</view>
				<view class="col col-2"><input type="text" :value="item.real_sell_price" @input="inputChange"  :data-index="idx" data-field="real_sell_price" /></view>
				<view class="col col-3">{{item.total_weight}}</view>
				<view class="col col-4"><input type="text" :value="item.real_total_weight" @input="inputChange" :data-index="idx" data-field="real_total_weight"/></view>
				<view class="col col-5">{{item.real_totalprice}}</view>
			</view>
			<view class="heji"><text>合计：</text><text :style="{color:t('color1')}">￥{{totalprice}}</text></view>
		</view>
		<view class="tips">
			<view>* 发货重量为实际结算重量;</view>
			<view>* 实拣重量小于购买重量，订单差额会原路退还用户;</view>
			<view>* 实拣重量大于购买重量，用户无法追加订单金额，请谨慎操作！</view>
		</view>
		<view style="width:100%;height:160rpx"></view>
		
		<view class="bottom">
			<view class="btn2" :style="{background:t('color1'),color:'#FFF'}" @tap="fahuo">确定发货</view>
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
        prolist: "",
				totalprice:0
    };
  },
  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.getdata();
  },
	onPullDownRefresh: function () {
		this.getdata();
	},
  methods: {
		getdata: function () {
			var that = this;
			that.loading = true;
			app.get('ApiAdminOrder/weightOrderFahuo', {id: that.opt.id}, function (res) {
				that.loading = false;
				if(res.status==1){
					that.detail = res.detail;
					that.prolist = res.prolist;
					that.totalprice = res.detail.totalprice
					that.loaded();
				}else{
					app.alert(res.msg);
					setTimeout(function(){
						app.goback(true);
					},1000)
				}
			});
		},
		calprice:function(){
			var that = this;
			var prolist = that.prolist
			var totalprice = 0;
			for(var i in prolist){
				var gprice = prolist[i].real_sell_price;
				var gweight = prolist[i].real_total_weight;
				totalprice = totalprice + gprice * gweight
			}
			that.totalprice = totalprice.toFixed(2)
		},
		inputChange:function(e){
			var that = this;
			var prolist = that.prolist
			var field = e.currentTarget.dataset.field
			var index = e.currentTarget.dataset.index
			var val = e.detail.value;
			prolist[index][field] = val
			that.prolist = prolist
			that.calprice()
		},
		fahuo:function(e){
			var that = this;
			app.post('ApiAdminOrder/weightOrderFahuo', {id: that.opt.id,prolist:that.prolist}, function (res) {
				if(res.status==1){
					app.success(res.msg);
					setTimeout(function(){
						app.goto('/admin/order/shoporder');
					},1000)
				}else{
					app.alert(res.msg);
				}
			});
		}
  }
};
</script>
<style>
.address{ display:flex;width: 100%; padding: 20rpx 3%; background: #FFF;}
.address .img{width:40rpx}
.address image{width:40rpx; height:40rpx;}
.address .info{flex:1;display:flex;flex-direction:column;}
.address .info .t1{font-size:28rpx;font-weight:bold;color:#333}
.address .info .t2{font-size:24rpx;color:#999}

.product{background: #ffffff;padding: 20rpx;margin-top: 10rpx;}
.colitem{display: flex;justify-content: space-around;max-width: 100%;overflow-y: scroll;flex-wrap: nowrap;padding: 20rpx 0;font-size: 24rpx;align-items: center;}
.colitem view{text-align: center;}
.col-1 {width: 150rpx;}
.col input{border-bottom: 1rpx solid #999;height: 60rpx;width: 140rpx;font-size: 24rpx;}
.row-header{border-bottom: 1rpx solid #f1f1f1;padding-bottom: 14rpx;font-size: 28rpx;}
.heji{display: flex;justify-content: space-between;align-items: center;font-weight: bold;font-size: 30rpx;padding: 20rpx 20rpx 0 50rpx;border-top: 1rpx solid #f1f1f1;}
.orderinfo{width:100%;border-radius:8rpx;margin-top:16rpx;margin-top:10rpx;padding: 14rpx 3%;background: #FFF;}
.orderinfo .item{display:flex;width:100%;padding:10rpx 0;}
.orderinfo .item:last-child{ border-bottom: 0;}
.orderinfo .item .t1{width:200rpx;color: #666;}
.orderinfo .item .t2{flex:1;text-align:right}
.orderinfo .item .red{color:red}

.bottom{ width: 100%;padding: 14rpx 0;background: #fff; position: fixed; bottom: 0px;left: 0px;display:flex;justify-content:center;align-items:center;}
.btn2{border-radius:3px;text-align:center;width: 92%;margin: 0 4%;padding: 16rpx}
.tips{background: #fff; width: 100%;font-size: 24rpx;line-height: 40rpx;color: #fca92d;padding: 20rpx;margin-top: 10rpx;}
</style>