<template>
<view class="container">
	<block v-if="isload">
		<map v-if="worker_order.status!=0 && worker_order.status!=4" class="map" :longitude="binfo.longitude" :latitude="binfo.latitude" scale="14" :markers="[{
			id:0,
			latitude:binfo.latitude,
			longitude:binfo.longitude,
			iconPath: `${pre_url}/static/img/peisong/marker_business.png`,
			width:'44',
			height:'54'
		},{
			id:0,
			latitude:orderinfo.latitude,
			longitude:orderinfo.longitude,
			iconPath: `${pre_url}/static/img/peisong/marker_kehu.png`,
			width:'44',
			height:'54'
		},{
			id:0,
			latitude:worker.latitude,
			longitude:worker.longitude,
			iconPath: `${pre_url}/static/img/peisong/marker_qishou.png`,
			width:'44',
			height:'54'
		}]"></map>
		<map v-else class="map" :longitude="binfo.longitude" :latitude="binfo.latitude" scale="14" :markers="[{
			id:0,
			latitude:binfo.latitude,
			longitude:binfo.longitude,
			iconPath: `${pre_url}/static/img/peisong/marker_business.png`,
			width:'44',
			height:'54'
		},{
			id:0,
			latitude:orderinfo.latitude,
			longitude:orderinfo.longitude,
			iconPath: `${pre_url}/static/img/peisong/marker_kehu.png`,
			width:'44',
			height:'54'
		}]"></map>

		<view class="order-box">
			<view class="head">
				<view class="f1" v-if="worker_order.status==3"><image :src="pre_url+'/static/img/peisong/ps_time.png'" class="img"/>已完成</view>
				<view class="f1" v-if="worker_order.status==1"><image :src="pre_url+'/static/img/peisong/ps_time.png'" class="img"/>已接单</view>
				<view class="f1" v-if="worker_order.status==2 && worker_order.endtime"><image :src="pre_url+'/static/img/peisong/ps_time.png'" class="img"/>待确认</view>
				<view class="f1" v-if="worker_order.status==2 && !worker_order.endtime"><image :src="pre_url+'/static/img/peisong/ps_time.png'" class="img"/>服务中</view>
				<view class="flex1"></view>
			</view>
			<view class="content" style="border-bottom:0">
				<view class="f1" v-if="worker_order.fwtype == 2">
					<view class="t1"><text class="x1">{{worker_order.juli}}</text><text class="x2">{{worker_order.juli_unit}}</text></view>
					<view class="t2"><image :src="pre_url+'/static/img/peisong/ps_juli.png'" class="img"/></view>
					<view class="t3"><text class="x1">{{worker_order.juli2}}</text><text class="x2">{{worker_order.juli2_unit}}</text></view>
				</view>
				<view class="f2">
					<view class="t1">{{binfo.name}}</view>
					<view class="t2">{{binfo.address}}</view>
					<view class="t3">{{orderinfo.address}}</view>
				</view>
				<view class="f3" @tap.stop="daohang"><image :src="pre_url+'/static/img/peisong/ps_daohang.png'" class="img"/></view>
			</view>
		</view>

		<view class="orderinfo">
			<view class="box-title">{{resetname ? '购买服务项目' : '购买清单'}}</view>
			<view v-for="(item, idx) in prolist" :key="idx" class="item">
				<text class="t1 flex1">{{item.name}} {{item.ggname}}</text>
				<text class="t2 flex0">￥{{item.sell_price}} ×{{item.num}} </text>
			</view>
		</view>
		
		<view class="orderinfo" v-if="worker_order.status!=0">
			<view class="box-title">服务信息</view>
			<view class="item" v-if="worker.realname">
				<text class="t1">服务人员</text>
				<text class="t2"><text style="font-weight:bold">{{worker.realname}}</text>({{worker.tel}})</text>
			</view>
			<view class="item">
				<text class="t1">接单时间</text>
				<text class="t2">{{dateFormat(worker_order.starttime)}}</text>
			</view>
			<view class="item" v-if="worker_order.daodiantime">
				<text class="t1" v-if="resetname">到达时间</text>
				<text class="t1" v-else>{{yuyue_sign?'出发时间':'到店时间'}}</text>
				<text class="t2">{{dateFormat(worker_order.daodiantime)}}</text>
			</view>
			<view class="item" v-if="worker_order.sign_time">
				<text class="t1">开始时间</text>
				<text class="t2">{{dateFormat(worker_order.sign_time)}}</text>
			</view>
			<view class="item" v-if="worker_order.endtime">
				<text class="t1">完成时间</text>
				<text class="t2">{{dateFormat(worker_order.endtime)}}</text>
			</view>
		</view>

		<view class="orderinfo">
			<view class="box-title">订单信息</view>
			<view class="item">
				<text class="t1">订单编号</text>
				<text class="t2" user-select="true" selectable="true">{{orderinfo.ordernum}}</text>
			</view>
			<view class="item">
				<text class="t1">下单时间</text>
				<text class="t2">{{dateFormat(orderinfo.createtime)}}</text>
			</view>
			<view class="item">
				<text class="t1">支付时间</text>
				<text class="t2">{{dateFormat(orderinfo.paytime)}}</text>
			</view>
			<view class="item">
				<text class="t1">支付方式</text>
				<text class="t2">{{orderinfo.paytype}}</text>
			</view>
			<view class="item">
				<text class="t1">{{resetname ? '服务金额' : '商品金额'}}</text>
				<text class="t2 red">¥{{orderinfo.product_price}}</text>
			</view>
			<view class="item">
				<text class="t1">订单金额</text>
				<text class="t2 red">¥{{orderinfo.totalprice}}</text>
			</view>
			<view class="item">
				<text class="t1">实付款</text>
				<text class="t2 red">¥{{orderinfo.paymoney}}</text>
			</view>
			<view class="item">
				<text class="t1">备注</text>
				<text class="t2 red">{{orderinfo.message ? orderinfo.message : '无'}}</text>
			</view>
		</view>
		<view style="width:100%;height:120rpx"></view>
		<view class="bottom">
			<view class="f1" v-if="worker_order.status!=0 && worker.tel" @tap="call" :data-tel="worker.tel"><image :src="pre_url+'/static/img/peisong/tel1.png'" class="img"/>联系服务人员</view>
			<view class="f2" v-if="worker_order.status!=0" @tap="call" :data-tel="binfo.tel"><image :src="pre_url+'/static/img/peisong/tel2.png'" class="img"/>联系商家</view>
			<view class="btn1" @tap="goto" :data-url="'commentps?id='+worker_order.id" v-if="mid==orderinfo.mid && worker_order.worker_id>0 && worker_order.status==3">评价服务人员</view>
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
			nodata:false,
      express_com: '',
      express_no: '',
      datalist: [],

      orderinfo: {},
      prolist: [],
      binfo: {},
      worker: {},
      worker_order: {},
			mid:'',
			resetname:false,
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
			that.express_no = that.opt.express_no;
			that.loading = true;
			app.get('ApiYuyue/logistics', { express_no: that.express_no}, function (res) {
				that.loading = false;
				that.orderinfo = res.orderinfo;
				that.prolist = res.prolist;
				that.binfo = res.binfo;
				that.worker = res.worker;
				that.worker_order = res.worker_order;
				that.set = res.set;
				that.mid = res.mid;
				that.resetname = res.resetname;
				that.loaded();
			});
		},
		call:function(e){
			var tel = e.currentTarget.dataset.tel;
			uni.makePhoneCall({
				phoneNumber: tel
			});
		}
  }
}
</script>
<style>
.expressinfo .head { width:100%;background: #fff; margin:20rpx 0;padding: 20rpx 20rpx;display:flex;align-items:center}
.expressinfo .head .f1{ width:120rpx;height:120rpx;margin-right:20rpx}
.expressinfo .head .f1 image{width:100%;height:100%}
.expressinfo .head .f2{display:flex;flex-direction:column;flex:auto;font-size:30rpx;color:#999999}
.expressinfo .head .f2 .t1{margin-bottom:8rpx}
.expressinfo .content{ width: 100%;  background: #fff;display:flex;flex-direction:column;color: #979797;padding:20rpx 40rpx}
.expressinfo .content .on{color: #23aa5e;}
.expressinfo .content .item{display:flex;width: 96%;  margin: 0 2%;border-left: 1px #dadada solid;padding:10rpx 0}
.expressinfo .content .item .f1{ width:40rpx;flex-shrink:0;position:relative}
.expressinfo .content image{width: 30rpx; height: 30rpx; position: absolute; left: -16rpx; top: 22rpx;}
/*.content .on image{ top:-1rpx}*/
.expressinfo .content .item .f1 image{ width: 30rpx; height: 30rpx;}

.expressinfo .content .item .f2{display:flex;flex-direction:column;flex:auto;}
.expressinfo .content .item .f2 .t1{font-size: 30rpx;}
.expressinfo .content .item .f2 .t1{font-size: 26rpx;}


.map{width:100%;height:500rpx;overflow:hidden}
.ordertop{width:100%;height:220rpx;padding:50rpx 0 0 70rpx}
.ordertop .f1{color:#fff}
.ordertop .f1 .t1{font-size:32rpx;height:60rpx;line-height:60rpx}
.ordertop .f1 .t2{font-size:24rpx}

.order-box{ width: 94%;margin:20rpx 3%;padding:6rpx 3%; background: #fff;border-radius:8px}
.order-box .head{ display:flex;width:100%; border-bottom: 1px #f5f5f5 solid; height:88rpx; line-height:88rpx; overflow: hidden; color: #999;}
.order-box .head .f1{display:flex;align-items:center;color:#222222}
.order-box .head .f1 .img{width:24rpx;height:24rpx;margin-right:4px}
.order-box .head .f1 .t1{color:#06A051;margin-right:10rpx}
.order-box .head .f2{color:#FF6F30}
.order-box .head .f2 .t1{font-size:36rpx;margin-right:4rpx}

.order-box .content{display:flex;justify-content:space-between;width: 100%; padding:16rpx 0px;border-bottom: 1px solid #f5f5f5;position:relative}
.order-box .content .f1{width:100rpx;display:flex;flex-direction:column;align-items:center}
.order-box .content .f1 .t1{display:flex;flex-direction:column;align-items:center}
.order-box .content .f1 .t1 .x1{color:#FF6F30;font-size:28rpx;font-weight:bold}
.order-box .content .f1 .t1 .x2{color:#999999;font-size:24rpx;margin-bottom:8rpx}
.order-box .content .f1 .t2 .img{width:12rpx;height:36rpx}

.order-box .content .f1 .t3{display:flex;flex-direction:column;align-items:center}
.order-box .content .f1 .t3 .x1{color:#FF6F30;font-size:28rpx;font-weight:bold}
.order-box .content .f1 .t3 .x2{color:#999999;font-size:24rpx}
.order-box .content .f2{}
.order-box .content .f2 .t1{font-size:36rpx;color:#222222;font-weight:bold;line-height:50rpx;margin-bottom:6rpx;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:1;overflow:hidden;}
.order-box .content .f2 .t2{font-size:24rpx;color:#222222;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:1;overflow:hidden;}
.order-box .content .f2 .t3{font-size:36rpx;color:#222222;font-weight:bold;line-height:50rpx;margin-top:30rpx;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:1;overflow:hidden;}
.order-box .content .f3 .img{width:72rpx;height:168rpx}

.orderinfo{width: 94%;margin:20rpx 3%;margin-top:10rpx;padding: 14rpx 3%;background: #FFF;border-radius:8px}
.orderinfo .box-title{color:#161616;font-size:30rpx;height:80rpx;line-height:80rpx;font-weight:bold}
.orderinfo .item{display:flex;width:100%;padding:10rpx 0;}
.orderinfo .item .t1{width:200rpx;color:#161616}
.orderinfo .item .t2{flex:1;text-align:right;color:#222222}
.orderinfo .item .red{color:red}

.bottom{ width: 100%;background: #fff; position: fixed; bottom: 0px;left: 0px;display:flex;align-items:center;height:100rpx;}
.bottom .f1{width:188rpx;display:flex;align-items:center;flex-direction:column;font-size:20rpx;color:#373C55;border-right:1px solid #EAEEED}
.bottom .f1 .img{width:44rpx;height:44rpx}
.bottom .f2{width:188rpx;display:flex;align-items:center;flex-direction:column;font-size:20rpx;color:#373C55}
.bottom .f2 .img{width:44rpx;height:44rpx}
.bottom .btn1{flex:1;background:linear-gradient(-90deg, #06A051 0%, #03B269 100%);height:100rpx;line-height:100rpx;color:#fff;text-align:center;font-size:32rpx}
</style>