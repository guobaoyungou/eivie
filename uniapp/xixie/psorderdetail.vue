<template>
<view class="container">
	<block v-if="isload">

		<map v-if="psorder.status!=4" class="map" :longitude="mendian.longitude" :latitude="mendian.latitude" scale="14" :markers="[{
			id:0,
			latitude:mendian.latitude,
			longitude:mendian.longitude,
			iconPath: '/static/peisong/marker_business.png',
			width:'44',
			height:'54'
		},{
			id:1,
			latitude:psorder.latitude,
			longitude:psorder.longitude,
			iconPath: '/static/peisong/marker_kehu.png',
			width:'44',
			height:'54'
		},{
			id:2,
			latitude:psuser.latitude,
			longitude:psuser.longitude,
			iconPath: '/static/peisong/marker_qishou.png',
			width:'44',
			height:'54'
		}]"></map>
		<map v-else class="map" :longitude="mendian.longitude" :latitude="mendian.latitude" scale="14" :markers="[{
			id:0,
			latitude:mendian.latitude,
			longitude:mendian.longitude,
			iconPath: '/static/peisong/marker_business.png',
			width:'44',
			height:'54'
		},{
			id:1,
			latitude:psorder.latitude,
			longitude:psorder.longitude,
			iconPath: '/static/peisong/marker_kehu.png',
			width:'44',
			height:'54'
		}]"></map>

		<view class="order-box">
			<view class="head">
				<view class="f1">预约时间{{psorder.yy_time}}</view>
			</view>
			<view class="content" style="border-bottom:0">
				<view class="f1">
					<view class="t1"><text class="x1">{{psorder.juli}}</text><text class="x2">{{psorder.juli_unit}}</text></view>
					<view class="t2"><image src="/static/peisong/ps_juli.png" class="img"/></view>
					<view class="t3"><text class="x1">{{psorder.juli2}}</text><text class="x2">{{psorder.juli2_unit}}</text></view>
				</view>
				<view class="f2">
					<view class="t1">{{mendian&&mendian.name?mendian.name:''}}</view>
					<view class="t2">{{mendian&&mendian.address?mendian.address:''}}</view>
					<view class="t3">{{psorder.address}}</view>
					<view class="t2">{{psorder.area}}</view>
				</view>
				<view class="f3" @tap.stop="daohang"><image :src="pre_url+'/static/img/peisong/ps_daohang.png'" class="img"/></view>
			</view>
		</view>

		<view class="orderinfo">
			<view class="box-title">商品清单({{psorder.procount}})</view>
			<view v-for="(item, idx) in prolist" :key="idx" class="item">
				<text class="t1 flex1">{{item.name}}</text>
				<text class="t2 flex0">￥{{item.sell_price}} ×{{item.num}} </text>
			</view>
		</view>
		
		<view class="orderinfo" v-if="psorder.status!=0">
			<view class="box-title">配送信息</view>
			<view class="item">
				<text class="t1">接单时间</text>
				<text class="t2">{{dateFormat(psorder.qd_time)}}</text>
			</view>
			<view class="item" v-if="psorder.qh_time">
				<text class="t1">取货完成时间</text>
				<text class="t2">{{dateFormat(psorder.qh_time)}}</text>
			</view>
			<view class="item" v-if="psorder.rk_time">
				<text class="t1">入库完成时间</text>
				<text class="t2">{{dateFormat(psorder.rk_time)}}</text>
			</view>
			<view class="item" v-if="psorder.qx_time">
				<text class="t1">清洗完成时间</text>
				<text class="t2">{{dateFormat(psorder.qx_time)}}</text>
			</view>
            <view class="item" v-if="psorder.end_time">
            	<text class="t1">订单完成时间</text>
            	<text class="t2">{{dateFormat(psorder.end_time)}}</text>
            </view>
		</view>

		<view class="orderinfo">
			<view class="box-title">订单信息</view>
			<view class="item">
				<text class="t1">订单编号</text>
				<text class="t2" user-select="true" selectable="true">{{psorder.ordernum}}</text>
			</view>
			<view class="item">
				<text class="t1">下单时间</text>
				<text class="t2">{{dateFormat(psorder.createtime)}}</text>
			</view>
			<view class="item">
				<text class="t1">支付时间</text>
				<text class="t2">{{dateFormat(psorder.paytime)}}</text>
			</view>
			<view class="item">
				<text class="t1">支付方式</text>
				<text class="t2">{{psorder.paytype}}</text>
			</view>
			<view class="item">
				<text class="t1">商品金额</text>
				<text class="t2 red">¥{{psorder.product_price}}</text>
			</view>
			<view class="item">
				<text class="t1">实付款</text>
				<text class="t2 red">¥{{psorder.totalprice}}</text>
			</view>
			<!-- <view class="item">
				<text class="t1">备注</text>
				<text class="t2 red">{{psorder.message ? psorder.message : '无'}}</text>
			</view> -->
		</view>
		<view style="width:100%;height:180rpx"></view>
		<view class="bottom notabbarbot" v-if="psorder.status!=5">
			<view class="f1" v-if="psorder.status!=0" @tap="call" :data-tel="psorder.tel"><image src="/static/peisong/tel1.png" class="img"/>联系顾客</view>
			<view class="f2" v-if="psorder.status!=0" @tap="call" :data-tel="mendian.tel"><image src="/static/peisong/tel2.png" class="img"/>联系门店</view>
			<view class="btn1" @tap="qiangdan"   :data-id="psorder.id" v-if="psorder.status==0">立即抢单</view>
			<view class="btn1" @tap.stop="goto"  :data-url="'psqh?id=' + psorder.id" v-if="psorder.status==1">取货完成</view>
			<view class="btn1" @tap.stop="setst" :data-id="psorder.id" data-st="3" v-if="psorder.status==2">入库完成</view>
			<view class="btn1" @tap.stop="setst" :data-id="psorder.id" data-st="4" v-if="psorder.status==3">清洗完成</view>
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

        prolist: [],
        psorder: {},
        longitude : '',
        latitude  : '',
        mendian:''
    };
  },
    onLoad: function (opt) {
        this.opt = app.getopts(opt);
    },
    onShow: function () {
        this.getdata();
    },
	onPullDownRefresh: function () {
		this.getdata();
	},
  methods: {
		getdata: function () {
            var that = this;
            app.getLocation(function(res){
                that.longitude = res.longitude;
                that.latitude  = res.latitude;
                that.getorderdetail();
            },function(res){
                that.getorderdetail();
            });
		},
        getorderdetail:function(){
            var that = this;
            that.loading = true;
            app.get('ApiXixie/psorderdetail', {id: that.opt.id,longitude:that.longitude,latitude:that.latitude}, function (res) {
            	that.loading = false;
            	if(res.status == 1) {
                    that.prolist = res.prolist;
                    that.psorder = res.psorder;
                    that.mendian = res.mendian;
                    that.loaded();
            		
            	}else if(res.status==444){
                    app.error(res.msg);
                    setTimeout(function(){
                        app.goto("pslogin",'reLaunch');
                    },500);
                }else{
                    app.alert(res.msg);return;
                }
            });
        },
        qiangdan: function (e) {
            var that = this;
            var id = e.currentTarget.dataset.id;
            app.confirm('确定要接单吗?', function () {
                app.showLoading('提交中');
                app.post('ApiXixie/qiangdan', {id: id}, function (data) {
                    app.showLoading(false);
                    if(data.status == 1){
                        app.success(data.msg);
                        setTimeout(function () {
                        that.getdata();
                        }, 1000);
                    }else if(res.status==444){
                        app.error(res.msg);
                        setTimeout(function(){
                            app.goto("pslogin",'reLaunch');
                        },500);
                    }else{
                        app.alset(data.msg);
                    }
                });
            });
        },
        setst: function (e) {
          var that = this;
          var id = e.currentTarget.dataset.id;
          var st = e.currentTarget.dataset.st;
            if(st == 2){
                var tips = '确定取货完成吗?';
            }if(st == 3){
                var tips = '确定入库完成吗?';
            }if(st == 4){
                var tips = '确定清洗完成吗?';
            }
            app.confirm(tips, function () {
                app.showLoading('提交中');
                app.post('ApiXixie/changeStatus', {id: id,st:st}, function (data) {
                    app.showLoading(false);
                    if(data.status == 1){
                        app.success(data.msg);
                        setTimeout(function () {
                            that.getdata();
                        }, 1000);
                    }else if(res.status==444){
                        app.error(res.msg);
                        setTimeout(function(){
                            app.goto("pslogin",'reLaunch');
                        },500);
                    }else{
                        app.alset(data.msg);
                    }
                });
            });
        },
		daohang:function(e){
			var that = this;
			var datainfo = that.psorder;
            var mendian  = that.mendian;
			uni.showActionSheet({
            itemList: ['导航到商家', '导航到用户'],
            success: function (res) {
					if(res.tapIndex >= 0){
						if (res.tapIndex == 0) {
							var longitude = mendian.longitude
							var latitude  = mendian.latitude
							var name      = mendian.name
							var address   = mendian.address
						}else{
							var longitude = datainfo.longitude
							var latitude = datainfo.latitude
							var name     =  datainfo.address
							var address  =  datainfo.address
						}
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
.order-box .content .f1 .t2 .img{width:12rpx;height:36rpx; margin: 10rpx 0;}

.order-box .content .f1 .t3{display:flex;flex-direction:column;align-items:center}
.order-box .content .f1 .t3 .x1{color:#FF6F30;font-size:28rpx;font-weight:bold}
.order-box .content .f1 .t2 .img{width:12rpx;height:36rpx; margin: 10rpx 0;}
.order-box .content .f2{padding:0 10rpx;}
.order-box .content .f2 .t1{font-size:30rpx;color:#222222;font-weight:bold;line-height:50rpx;margin-bottom:6rpx;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:2;overflow:hidden;}
.order-box .content .f2 .t2{font-size:28rpx;color:#222222;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:3;overflow:hidden;}
.order-box .content .f2 .t3{font-size:30rpx;color:#222222;font-weight:bold;line-height:50rpx;margin-top:30rpx;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:2;overflow:hidden;}
.order-box .content .f3 .img{width:72rpx;height:168rpx}

.orderinfo{width: 94%;margin:20rpx 3%;margin-top:10rpx;padding: 14rpx 3%;background: #FFF;border-radius:8px}
.orderinfo .box-title{color:#161616;font-size:30rpx;height:80rpx;line-height:80rpx;font-weight:bold}
.orderinfo .item{display:flex;width:100%;padding:10rpx 0;}
.orderinfo .item .t1{width:200rpx;color:#161616}
.orderinfo .item .t2{flex:1;text-align:right;color:#222222}
.orderinfo .item .red{color:red}

.bottom{ width: 100%;background: #fff; position: fixed; bottom: 0px;left: 0px;display:flex;justify-content:flex-end;align-items:center;height: 100rpx;}
.bottom .f1{width:188rpx;display:flex;align-items:center;flex-direction:column;font-size:20rpx;color:#373C55;border-right:1px solid #EAEEED}
.bottom .f1 .img{width:44rpx;height:44rpx}
.bottom .f2{width:188rpx;display:flex;align-items:center;flex-direction:column;font-size:20rpx;color:#373C55}
.bottom .f2 .img{width:44rpx;height:44rpx}
.bottom .btn1{flex:1;background:linear-gradient(-90deg, #06A051 0%, #03B269 100%);height:80rpx;line-height:80rpx;color:#fff;text-align:center;font-size:32rpx}
</style>