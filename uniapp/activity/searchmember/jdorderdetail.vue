<template>
<view class="container">
	<block v-if="isload">

		<map v-if="psorder.status!=4" class="map" :longitude="binfo.longitude" :latitude="binfo.latitude" scale="14" :markers="[{
			id:0,
			latitude:binfo.latitude,
			longitude:binfo.longitude,
			iconPath: '/static/peisong/marker_business.png',
			width:'44',
			height:'54'
		},{
			id:0,
			latitude:orderinfo.latitude,
			longitude:orderinfo.longitude,
			iconPath: '/static/peisong/marker_kehu.png',
			width:'44',
			height:'54'
		}]"></map>
		<map v-else class="map" :longitude="binfo.longitude" :latitude="binfo.latitude" scale="14" :markers="[{
			id:0,
			latitude:binfo.latitude,
			longitude:binfo.longitude,
			iconPath: '/static/peisong/marker_business.png',
			width:'44',
			height:'54'
		},{
			id:0,
			latitude:orderinfo.latitude,
			longitude:orderinfo.longitude,
			iconPath: '/static/peisong/marker_kehu.png',
			width:'44',
			height:'54'
		}]"></map>

		<view class="order-box">
			<view class="head">
				<view v-if="psorder.fwtype==1">
					<view class="f1" v-if="psorder.status==3"><image src="/static/peisong/ps_time.png" class="img"/>已完成</view>
					<view class="f1" v-if="psorder.status==1"><image src="/static/peisong/ps_time.png" class="img"/> {{orderinfo.yydate}}<text class="t1">预计上门时间</text> </view>
					<view class="f1" v-if="psorder.status==2"><image src="/static/peisong/ps_time.png" class="img"/><text class="t1" style="margin-left:10rpx">服务中</text></view>
				</view>
				<view v-else-if="psorder.fwtype==2">
						<view class="f1" v-if="psorder.status==3"><image src="/static/peisong/ps_time.png" class="img"/>已完成</view>
						<view class="f1" v-else-if="psorder.status==1"><image src="/static/peisong/ps_time.png" class="img"/>期望上门时间<text class="t1">{{orderinfo.yydate}}</text> </view>
						<view class="f1" v-else-if="psorder.status==2"  ><image src="/static/peisong/ps_time.png" class="img"/>已到达，服务中</view>
				</view>
				<view class="flex1"></view>
				<view class="f2"><text class="t1">{{psorder.ticheng}}</text>元</view>
			</view>
			<view class="content" style="border-bottom:0">
				<!-- <view class="f1">
					<view class="t1"><text class="x1">{{psorder.juli}}</text><text class="x2">{{psorder.juli_unit}}</text></view>
					<view class="t2"><image src="/static/peisong/ps_juli.png" class="img"/></view>
					<view class="t3"><text class="x1">{{psorder.juli2}}</text><text class="x2">{{psorder.juli2_unit}}</text></view>
				</view> -->
				<view class="f2">
					<view class="t1">{{binfo.name}}</view>
					<view class="t2">{{binfo.address}}</view>
					<view class="t3">{{orderinfo.address}}</view>
						<view class="t2">{{orderinfo.area}}</view>
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
			<view class="box-title">服务信息</view>
			<view class="item">
				<text class="t1">用户姓名</text>
				<text class="t2">{{orderinfo.linkman}}</text>
			</view>
			<view class="item">
				<text class="t1">用户电话</text>
				<text class="t2">{{orderinfo.tel}}</text>
			</view>
			<view class="item">
				<text class="t1">预约时间</text>
				<text class="t2">{{orderinfo.yydate}}</text>
			</view>
			<view class="item">
				<text class="t1">接单时间</text>
				<text class="t2">{{dateFormat(psorder.starttime)}}</text>
			</view>
			<view class="item" v-if="psorder.daodiantime">
				<text class="t1">到店时间</text>
				<text class="t2">{{dateFormat(psorder.daodiantime)}}</text>
			</view>
			<view class="item" v-if="psorder.endtime">
				<text class="t1">完成时间</text>
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
		<view style="width:100%;height:20rpx;"></view>
		<view class="bottom" v-if="psorder.status!=4" style="display:none">
			<view class="f1" v-if="psorder.status!=0" @tap="call" :data-tel="orderinfo.tel"><image src="/static/peisong/tel1.png" class="img"/>联系顾客</view>
			<view class="f2" v-if="psorder.status!=0" @tap="call" :data-tel="binfo.tel"><image src="/static/peisong/tel2.png" class="img"/>联系商家</view>
			<!-- <view class="btn1" @tap="qiangdan" :data-id="psorder.id" v-if="psorder.status==0">立即抢单</view>
			<block v-if="psorder.fwtype==1">
				<view class="btn1" @tap="setst" :data-id="psorder.id" data-st="2" v-if="psorder.status==1">顾客已到店</view>
				<view class="btn1" @tap="setst" :data-id="psorder.id" data-st="3" v-if="psorder.status==2">我已完成</view>
			</block>
			<block v-if="psorder.fwtype==2">
				<view class="btn1" @tap="setst" :data-id="psorder.id" data-st="2" v-if="psorder.status==1">我已到达</view>
				<view class="btn1" @tap="setst" :data-id="psorder.id" data-st="3" v-if="psorder.status==2">我已完成</view>				<view class="btn1" @tap="setst" :data-id="psorder.id" data-st="4" v-if="psorder.status==3">我已完成</view>
			</block> -->
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
			
      orderinfo: {},
      prolist: [],
			worker:{},
      binfo: {},
      psorder: {},
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
			app.get('ApiSearchMember/orderdetail', {id: that.opt.id}, function (res) {
				that.orderinfo = res.orderinfo;
				that.prolist = res.prolist;
				that.binfo = res.binfo;
				that.psorder = res.psorder;
				that.worker = res.worker;
				that.loaded();
			});
		},

    qiangdan: function (e) {
      var that = this;
      var id = e.currentTarget.dataset.id;
      app.confirm('确定要接单吗?', function () {
				app.showLoading('提交中');
        app.post('ApiSearchMember/qiangdan', {id: id}, function (data) {
					app.showLoading(false);
          app.success(data.msg);
          setTimeout(function () {
            that.getdata();
          }, 1000);
        });
      });
    },
    setst: function (e) {
      var that = this;
      var id = e.currentTarget.dataset.id;
      var st = e.currentTarget.dataset.st;
			if(st == 2){
				var tips = '确定改为已到店吗?';
			}if(st == 3){
				var tips = '确定改为已完成吗?';
			}
      app.confirm(tips, function () {
				app.showLoading('提交中');
        app.post('ApiYuyueWorker/setst', {id: id,st:st}, function (data) {
					app.showLoading(false);
          app.success(data.msg);
          setTimeout(function () {
            that.getdata();
          }, 1000);
        });
      });
    },

		daohang:function(e){
			var that = this;
			var datainfo = that.psorder;
			var binfo = that.binfo
			var orderinfo = that.orderinfo
			uni.showActionSheet({
        itemList: ['导航到商家', '导航到用户'],
        success: function (res) {
					if(res.tapIndex >= 0){
						if (res.tapIndex == 0) {
							var longitude = datainfo.longitude
							var latitude = datainfo.latitude
							var name = binfo.name
							var address = binfo.address
						}else{
							var longitude = datainfo.longitude2
							var latitude = datainfo.latitude2
							var name = orderinfo.address
							var address = orderinfo.address
						}
						console.log(longitude);
						console.log(latitude);
						console.log(address);
						uni.openLocation({
						 latitude:parseFloat(latitude),
						 longitude:parseFloat(longitude),
						 name:name,
						 address:address,
						 scale: 13
						})
					}
				}
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

.bottom{ width: 100%;background: #fff; position: fixed; bottom: 0px;left: 0px;display:flex;justify-content:flex-end;align-items:center;}
.bottom .f1{width:188rpx;display:flex;align-items:center;flex-direction:column;font-size:20rpx;color:#373C55;border-right:1px solid #EAEEED}
.bottom .f1 .img{width:44rpx;height:44rpx}
.bottom .f2{width:188rpx;display:flex;align-items:center;flex-direction:column;font-size:20rpx;color:#373C55}
.bottom .f2 .img{width:44rpx;height:44rpx}
.bottom .btn1{flex:1;background:linear-gradient(-90deg, #06A051 0%, #03B269 100%);height:100rpx;line-height:100rpx;color:#fff;text-align:center;font-size:32rpx}
</style>