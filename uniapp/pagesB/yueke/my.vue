<template>
<view>
	<block v-if="isload">
	<view class="banner">
			<image :src="worker.headimg" background-size="cover"/>
			<view class="info">
				 <text class="nickname">{{worker.realname}}</text>
				 <text>{{worker.tel}}</text>
			</view>
	</view>
	<view class="contentdata">
		<view class="custom_field">
			<view class='item' data-url='jdorderlist?st=3' @tap='goto'>
				<text class="t1">累计服务</text>
				<text class='t2'>{{worker.totalnum}}次</text>
			</view>
			<view class='item'>
				<text class="t1">总收入</text>
				<text class='t2'>{{worker.totalmoney}}元</text>
			</view>
			<view class='item'>
				<text class="t1">好评率</text>
				<text class='t2'>{{worker.comment_haopercent}}%</text>
			</view>
		</view>

		<view class="listcontent">
			<view class="list">
				<view class="item" @tap="goto" data-url="withdraw">
					<view class="f1"><image :src="pre_url+'/static/img/peisong/ico-qianbao.png'"></image></view>
					<view class="f2">我的钱包</view>
					<text class="f3">余额：{{worker.money}}</text>
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
	
			<view class="list">
				<view class="item" @tap="goto" data-url="setinfo">
					<view class="f1"><image :src="pre_url+'/static/img/peisong/ico-shenfen.png'"></image></view>
					<view class="f2">提现设置</view>
					<text class="f3"></text>
					<image :src="pre_url+'/static/img/arrowright.png'" class="f4"></image>
				</view>
			</view>
			<view class="list">
				<view class="item" @tap="goto" data-url="setpwd">
					<view class="f1"><image :src="pre_url+'/static/img/peisong/ico-shenfen.png'"></image></view>
					<view class="f2">修改密码</view>
					<text class="f3"></text>
					<image :src="pre_url+'/static/img/arrowright.png'" class="f4"></image>
				</view>
			</view>
			<view class="list">
				<view class="item" @tap="goto" data-url="/pagesExt/yueke/workerlogin">
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
			<view @tap="goto" data-url="/pagesExt/yueke/workerorderlist" data-opentype="reLaunch" class="tabbar-item">
				<view class="tabbar-image-box">
					<image class="tabbar-icon" :src="pre_url+'/static/img/peisong/order.png'"></image>
				</view>
				<view class="tabbar-text">订单</view>
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
			worker:{},
			checked:'',
			showform:0
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
			app.get('ApiYueke/my', {}, function (res) {
				that.worker = res.worker;
				that.showform = res.showform;
				that.loaded();
			});
		}
  }
};
</script>
<style>
@import "/common.css";
.banner{ display:flex;width:100%;height:322rpx;padding:80rpx 32rpx 40rpx 32rpx;color:#fff;position:relative;
background: linear-gradient(-45deg, #06A051 0%, #03B269 100%);}
.banner image{ width:120rpx;height:120rpx;border-radius:50%;margin-right:20rpx}
.banner .info{display:flex;flex:auto;flex-direction:column;padding-top:10rpx}
.banner .info .nickname{font-size:32rpx;font-weight:bold;padding-bottom:12rpx}
.banner .sets{ width:70rpx;height:100rpx;line-height:100rpx;font-size:40rpx;text-align:center}
.banner .sets image{width:50rpx;height:50rpx;border-radius:0}

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

.tabbar{height: auto; position: relative;}
.tabbar-icon {width: 50rpx;height: 50rpx;}
.tabbar-bar {display: flex;flex-direction: row;width: 100%;height:100rpx;position: fixed;bottom: 0;padding:10rpx 0 0 0;background: #fff;font-size: 24rpx;color: #999;border-top: 1px solid #e5e5e5;z-index: 8;box-sizing:content-box}
.tabbar-item {flex: 1;text-align: center;overflow: hidden;}
.tabbar-image-box {height: 54rpx;margin-bottom: 4rpx;}
.tabbar-text {line-height: 30rpx;font-size: 24rpx;color:#222222}
.tabbar-text.active{color:#06A051}
.tabbar-bot{height:110rpx;width:100%;box-sizing:content-box}
@supports(bottom: env(safe-area-inset-bottom)){
	.tabbar-bot{padding-bottom:env(safe-area-inset-bottom);}
	.tabbar-bar{padding-bottom:env(safe-area-inset-bottom);}
}
</style>