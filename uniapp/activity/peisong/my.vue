<template>
<view>
	<block v-if="isload">
	<view class="banner">
			<image :src="psuser.headimg" background-size="cover"/>
			<view class="info">
				 <text class="nickname">{{psuser.realname}}</text>
				 <text>{{psuser.tel}}</text>
			</view>
	</view>
	<view class="contentdata">
		<view class="custom_field">
			<view class='item' data-url='orderlist?st=4' @tap='goto'>
				<text class="t1">累计配送</text>
				<text class='t2'>{{psuser.totalnum}}份</text>
			</view>
			<view class='item'>
				<text class="t1">总收入</text>
				<text class='t2'>{{psuser.totalmoney}}元</text>
			</view>
			<view class='item'>
				<text class="t1">好评率</text>
				<text class='t2'>{{psuser.comment_haopercent}}%</text>
			</view>
		</view>

		<view class="listcontent">
			<view class="list">
				<view class="item" @tap="goto" data-url="withdraw">
					<view class="f1"><image :src="pre_url+'/static/img/peisong/ico-qianbao.png'"></image></view>
					<view class="f2">我的钱包</view>
					<text class="f3">余额：{{psuser.money}}</text>
					<image :src="pre_url+'/static/img/arrowright.png'" class="f4"></image>
				</view>
			</view>
			<view class="list">
				<view class="item" @tap="goto" data-url="moneylog">
					<view class="f1"><image :src="pre_url+'/static/img/peisong/ico-zhangdan.png'"></image></view>
					<view class="f2">账单明细</view>
					<text class="f3"></text>
					<image :src="pre_url+'/static/img/arrowright.png'" class="f4"></image>
				</view>
			</view>
			<!-- <view class="list">
				<view class="item" @tap="goto" data-url="moneylog">
					<view class="f1"><image :src="pre_url+'/static/img/peisong/ico-order.png'"></image></view>
					<view class="f2">配送记录</view>
					<text class="f3"></text>
					<image :src="pre_url+'/static/img/arrowright.png'" class="f4"></image>
				</view>
			</view> -->
			<view class="list">
				<view class="item">
					<view class="f1"><image :src="pre_url+'/static/img/peisong/ico-jiedan.png'"></image></view>
					<view class="f2">接单状态</view>
					<text class="f3"><switch value="1" :checked="psuser.status==1?true:false" @change="switchchange"></switch></text>
				</view>
			</view>
			<view class="list">
				<view class="item" @tap="goto" data-url="setinfo">
					<view class="f1"><image :src="pre_url+'/static/img/peisong/ico-shenfen.png'"></image></view>
					<view class="f2">提现设置</view>
					<text class="f3"></text>
					<image :src="pre_url+'/static/img/arrowright.png'" class="f4"></image>
				</view>
			</view>
			<view class="list">
				<view class="item" @tap="goto" data-url="/pages/index/login">
					<view class="f1"><image :src="pre_url+'/static/img/peisong/ico-logout.png'"></image></view>
					<view class="f2">退出登录</view>
					<text class="f3"></text>
					<image :src="pre_url+'/static/img/arrowright.png'" class="f4"></image>
				</view>
			</view>
		</view>
	</view>

	<view class="tabbar">
		<view class="tabbar-bot"></view>
		<view class="tabbar-bar" style="background-color:#ffffff">
			<view @tap="goto" data-url="dating" data-opentype="reLaunch" class="tabbar-item">
				<view class="tabbar-image-box">
					<image class="tabbar-icon" :src="pre_url+'/static/img/peisong/home.png'"></image>
				</view>
				<view class="tabbar-text">大厅</view>
			</view>
			<view @tap="goto" data-url="orderlist" data-opentype="reLaunch" class="tabbar-item">
				<view class="tabbar-image-box">
					<image class="tabbar-icon" :src="pre_url+'/static/img/peisong/order.png'"></image>
				</view>
				<view class="tabbar-text">订单</view>
			</view>
			<view @tap="goto" data-url="orderlist?st=4" data-opentype="reLaunch" class="tabbar-item">
				<view class="tabbar-image-box">
					<image class="tabbar-icon" :src="pre_url+'/static/img/peisong/orderwc.png'"></image>
				</view>
				<view class="tabbar-text">已完成</view>
			</view>
			<view @tap="goto" data-url="my" data-opentype="reLaunch" class="tabbar-item">
				<view class="tabbar-image-box">
					<image class="tabbar-icon" :src="pre_url+'/static/img/peisong/my2.png'"></image>
				</view>
				<view class="tabbar-text active">我的</view>
			</view>
		</view>
	</view>
	</block>
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
			
			set:{},
			psuser:{},
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
		getdata:function(){
			var that = this;
			that.loading = true;
			app.get('ApiPeisong/my', {}, function (res) {
				that.loading = false;
				//that.set = res.set;
				that.psuser = res.psuser;
				that.loaded();
			});
		},
    switchchange: function (e) {
      console.log(e);
      var value = e.detail.value ? 1 : 0;
      app.post('ApiPeisong/setpsst', {st: value}, function (data) {});
    },
    saoyisao: function (d) {
      var that = this;
			if(app.globalData.platform == 'h5'){
				app.alert('请使用微信扫一扫功能扫码核销');return;
			}else if(app.globalData.platform == 'mp'){
				// #ifdef H5
				var jweixin = require('jweixin-module');
				jweixin.ready(function () {   //需在用户可能点击分享按钮前就先调用
					jweixin.scanQRCode({
						needResult: 1, // 默认为0，扫描结果由微信处理，1则直接返回扫描结果，
						scanType: ["qrCode","barCode"], // 可以指定扫二维码还是一维码，默认二者都有
						success: function (res) {
							var content = res.resultStr; // 当needResult 为 1 时，扫码返回的结果
							var params = content.split('?')[1];
							app.goto('/admin/hexiao/hexiao?'+params);
							//if(content.length == 18 && (/^\d+$/.test(content))){ //是十八位数字 付款码
							//	location.href = "{:url('shoukuan')}/aid/{$aid}/auth_code/"+content
							//}else{
							//	location.href = content;
							//}
						}
					});
				});
				// #endif
			}else{
				// #ifndef H5
				uni.scanCode({
					success: function (res) {
						console.log(res);
						var content = res.result;
						var params = content.split('?')[1];
						app.goto('/admin/hexiao/hexiao?'+params);
					}
				});
				// #endif
			}
    }
  }
};
</script>
<style>
@import "./common.css";
.banner{ display:flex;width:100%;height:322rpx;padding:80rpx 32rpx 40rpx 32rpx;color:#fff;position:relative;
background: linear-gradient(-45deg, #06A051 0%, #03B269 100%);}
.banner image{ width:120rpx;height:120rpx;border-radius:50%;margin-right:20rpx}
.banner .info{display:flex;flex:auto;flex-direction:column;padding-top:10rpx}
.banner .info .nickname{font-size:32rpx;font-weight:bold;padding-bottom:12rpx}
.banner .set{ width:70rpx;height:100rpx;line-height:100rpx;font-size:40rpx;text-align:center}
.banner .set image{width:50rpx;height:50rpx;border-radius:0}

.contentdata{display:flex;flex-direction:column;width:100%;padding:0 30rpx;margin-top:-100rpx;position:relative;margin-bottom:20rpx}

.custom_field{display:flex;width:100%;align-items:center;padding:30rpx 8rpx;background:#fff;border-radius:16rpx}
.custom_field .item{flex:1;display:flex;flex-direction:column;justify-content:center;align-items:center}
.custom_field .item .t1{color:#666;font-size:26rpx;margin-top:10rpx}
.custom_field .item .t2{color:#111;font-weight:bold;font-size:36rpx;margin-top:20rpx}

.score{ display:flex;width:100%;align-items:center;padding:10rpx 20rpx;background:#fff;border-top:1px dotted #eee}
.score .f1 .t2{color:#ff3300}

.list{ width: 100%;background: #fff;margin-top:20rpx;padding:0 20rpx;font-size:30rpx;margin-bottom:20rpx;border-radius:16rpx}
.list .item{ height:100rpx;display:flex;align-items:center;border-bottom:1px solid #eee}
.list .item:last-child{border-bottom:0;margin-bottom:20rpx}
.list .f1{width:50rpx;height:50rpx;line-height:50rpx;display:flex;align-items:center}
.list .f1 image{ width:44rpx;height:44rpx;}
.list .f1 span{ width:40rpx;height:40rpx;font-size:40rpx}
.list .f2{color:#222;font-weight:bold;margin-left:10rpx}
.list .f3{ color:#06A051;font-size:26rpx;text-align:right;flex:1}
.list .f4{ width: 40rpx; height: 40rpx;}

switch{transform:scale(.7);}
</style>