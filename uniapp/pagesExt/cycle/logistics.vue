<template>
<view class="container">
	<block v-if="isload">
	<block v-if="express_com=='同城配送'">
		<map v-if="psorder.status!=0 && psorder.status!=4" class="map" :longitude="binfo.longitude" :latitude="binfo.latitude" scale="14" :markers="[{
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
			latitude:psuser.latitude,
			longitude:psuser.longitude,
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
				<block v-if="type == 'express_wx'">
					<view class="f1" v-if="psorder.order_status==101"><image :src="pre_url+'/static/img/peisong/ps_time.png'" class="img"/>分配骑手</view>
					<view class="f1" v-else-if="psorder.order_status==102"><image :src="pre_url+'/static/img/peisong/ps_time.png'" class="img"/>骑手赶往店家</view>
					<view class="f1" v-else-if="psorder.order_status==201"><image :src="pre_url+'/static/img/peisong/ps_time.png'" class="img"/>骑手到店</view>
					<view class="f1" v-else-if="psorder.order_status==202"><image :src="pre_url+'/static/img/peisong/ps_time.png'" class="img"/>取货成功</view>
					<view class="f1" v-else-if="psorder.order_status==301"><image :src="pre_url+'/static/img/peisong/ps_time.png'" class="img"/>配送中</view>
					<view class="f1" v-else-if="psorder.order_status==302"><image :src="pre_url+'/static/img/peisong/ps_time.png'" class="img"/>已送达</view>
					<view class="f1" v-else><image :src="pre_url+'/static/img/peisong/ps_time.png'" class="img"/>配送中</view>
				</block>
				<block v-else>
					<view class="f1" v-if="psorder.status==4"><image :src="pre_url+'/static/img/peisong/ps_time.png'" class="img"/>已送达</view>
					<view class="f1" v-else-if="psorder.leftminute>0"><image :src="pre_url+'/static/img/peisong/ps_time.png'" class="img"/><text class="t1">{{psorder.leftminute}}分钟内</text> 送达</view>
					<view class="f1" v-else-if="psorder.yujitime>0"><image :src="pre_url+'/static/img/peisong/ps_time.png'" class="img"/>已超时<text class="t1" style="margin-left:10rpx">{{-psorder.leftminute}}分钟</text></view>
					<view class="f1" v-else><image :src="pre_url+'/static/img/peisong/ps_time.png'" class="img"/>配送中</view>
				</block>
			
				<view class="flex1"></view>
				<view class="f2"><text class="t1">{{orderinfo.freight_price}}</text>元</view>
			</view>
			<view class="content" style="border-bottom:0">
				<block v-if="type == 'express_wx'">
					<view class="f1" v-if="psuser.latitude">
						<view class="t1"><text class="x1">{{psorder.juli}}</text><text class="x2">{{psorder.juli_unit}}</text></view>
						<view class="t2"><image :src="pre_url+'/static/img/peisong/ps_juli.png'" class="img"/></view>
						<view class="t3"><text class="x1">{{psorder.juli2}}</text><text class="x2">{{psorder.juli2_unit}}</text></view>
					</view>
				</block>
				<block v-else>
					<view class="f1">
						<view class="t1"><text class="x1">{{psorder.juli}}</text><text class="x2">{{psorder.juli_unit}}</text></view>
						<view class="t2"><image :src="pre_url+'/static/img/peisong/ps_juli.png'" class="img"/></view>
						<view class="t3"><text class="x1">{{psorder.juli2}}</text><text class="x2">{{psorder.juli2_unit}}</text></view>
					</view>
				</block>
				
				<view class="f2">
					<view class="t1">{{binfo.name}}</view>
					<view class="t2">{{binfo.address}}</view>
					<view class="t3">{{orderinfo.address}}</view>
				</view>
				<view class="f3" @tap.stop="daohang"><image :src="pre_url+'/static/img/peisong/ps_daohang.png'" class="img"/></view>
			</view>
		</view>

		<view class="orderinfo">
			<view class="box-title">商品清单({{orderinfo.procount}})</view>
			<view v-for="(item, idx) in prolist" :key="idx" class="item">
				<text class="t1 flex1">{{item.name}} {{item.ggname}}</text>
				<text class="t2 flex0">￥{{item.sell_price}} ×{{item.num}} </text>
			</view>
		</view>
		
		<view class="orderinfo" v-if="psorder.status!=0">
			<view class="box-title">配送信息</view>
			<view class="item" v-if="psuser.realname">
				<text class="t1">配送员</text>
				<text class="t2"><text style="font-weight:bold">{{psuser.realname}}</text>({{psuser.tel}})</text>
			</view>
			<view class="item" v-if="psorder.starttime">
				<text class="t1">接单时间</text>
				<text class="t2">{{dateFormat(psorder.starttime)}}</text>
			</view>
			<view class="item" v-if="psorder.daodiantime">
				<text class="t1">到店时间</text>
				<text class="t2">{{dateFormat(psorder.daodiantime)}}</text>
			</view>
			<view class="item" v-if="psorder.quhuotime">
				<text class="t1">取货时间</text>
				<text class="t2">{{dateFormat(psorder.quhuotime)}}</text>
			</view>
			<view class="item" v-if="psorder.endtime">
				<text class="t1">送达时间</text>
				<text class="t2">{{dateFormat(psorder.endtime)}}</text>
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
				<text class="t1">商品金额</text>
				<text class="t2 red">¥{{orderinfo.product_price}}</text>
			</view>
			<view class="item">
				<text class="t1">实付款</text>
				<text class="t2 red">¥{{orderinfo.totalprice}}</text>
			</view>
			<view class="item">
				<text class="t1">备注</text>
				<text class="t2 red">{{orderinfo.message ? orderinfo.message : '无'}}</text>
			</view>
		</view>
		<view style="width:100%;height:120rpx"></view>
		<view class="bottom">
			<view class="f1" v-if="psorder.status!=0 && psuser.tel" @tap="call" :data-tel="psuser.tel"><image :src="pre_url+'/static/img/peisong/tel1.png'" class="img"/>联系配送员</view>
			<view class="f2" v-if="psorder.status!=0" @tap="call" :data-tel="binfo.tel"><image :src="pre_url+'/static/img/peisong/tel2.png'" class="img"/>联系商家</view>
			<view class="btn1" v-if="psorder.status ==4" @tap="goto" :data-url="'commentps?id='+psorder.id" >评价配送员</view>
		</view>
	</block>
	<block v-else-if="express_com=='货运托运'">
		<view class="orderinfo">
			<block v-if="datalist.pic">
			<view class="item">
				<text class="t1">物流单照片</text>
			</view>
			<view class="item">
				<image class="t2" :src="datalist.pic" @tap="previewImage" :data-url="datalist.pic" mode="widthFix"/>
			</view>
			</block>
			<view class="item" v-if="datalist.fhname">
				<text class="t1">发货人信息</text>
				<text class="t2">{{datalist.fhname}}</text>
			</view>
			<view class="item" v-if="datalist.fhaddress">
				<text class="t1">发货地址</text>
				<text class="t2">{{datalist.fhaddress}}</text>
			</view>
			<view class="item" v-if="datalist.shname">
				<text class="t1">收货人信息</text>
				<text class="t2">{{datalist.shname}}</text>
			</view>
			<view class="item" v-if="datalist.shaddress">
				<text class="t1">收货地址</text>
				<text class="t2">{{datalist.shaddress}}</text>
			</view>
			<view class="item" v-if="datalist.remark">
				<text class="t1">备注</text>
				<text class="t2">{{datalist.remark}}</text>
			</view>
		</view>
	</block>
	<block v-else>
		<view class="expressinfo">
			<view class="head">
				<view class="f1"><image :src="pre_url + '/static/img/feiji.png'"></image></view>
				<view class="f2">
					<view class="t1">快递公司：<text style="color:#333" user-select="true" selectable="true">{{express_com}}</text></view>
					<view class="t2">快递单号：<text style="color:#333" user-select="true" selectable="true">{{express_no}}</text></view>
				</view>
			</view>
			<view class="content">
				<view v-for="(item, index) in datalist" :key="index" :class="'item ' + (index==0?'on':'')">
					<view class="f1"><image :src="'/static/img/dot' + (index==0?'2':'1') + '.png'"></image></view>
					<view class="f2">
						<text class="t2">{{item.time}}</text>
						<text class="t1">{{item.context}}</text>
					</view>
				</view>
				<nodata v-if="nodata" text="暂未查找到物流信息"></nodata>
			</view>
		</view>
	</block>
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
			type:'',
      datalist: [],

      orderinfo: {},
      prolist: [],
      binfo: {},
      psuser: {},
      psorder: {}
    };
  },

  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.getdata();
  },
	onPullDownRefresh: function () {
		this.getdata();
	},
  onPullDownRefresh: function () {
    this.getdata();
  },
  methods: {
		getdata: function () {
			var that = this;
			that.express_com = that.opt.express_com;
			that.express_no = that.opt.express_no;
			that.type = that.opt.type;
			that.loading = true;
			app.get('ApiCycle/logistics', {express_com: that.express_com,express_no: that.express_no,type:that.type}, function (res) {
				that.loading = false;
				if(that.express_com == '同城配送'){
						that.orderinfo = res.orderinfo;
						that.prolist = res.prolist;
						that.binfo = res.binfo;
						that.psorder = res.psorder;
						that.psuser = res.psuser;
						setTimeout(function(){
							that.getdata();
						},10000)
				}else{
					var datalist = res.datalist;
					if (datalist.length < 1) {
						that.nodata = true;
					}
					that.datalist = datalist;
				}
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