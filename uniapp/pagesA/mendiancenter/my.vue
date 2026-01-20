<template>
<view>
	<block v-if="isload">
	<view class="banner" :style="'background:rgba('+t('color1rgb')+',1);color:#FFF'">
			<image :src="mendian.headimg" background-size="cover"/>
			<view class="info">
				 <view class="nickname">{{mendian.name}} 
						<view class="changemendian" v-if="changemendian" @tap="goto" data-url="/pagesC/mendian/changemendian">
							<text style="margin-left: 20rpx;font-size: 26rpx;"> 切换</text>
							<text class="iconfont iconjiantou" style="font-size: 40rpx;"></text>
						</view>
				 </view>
				 <text class="t1">ID:{{mendian.id}}</text>
				 <text class="t1">绑定社区：{{mendian.xqname}}</text>
				 <text class="t1" v-if="sets.showLevel_status==1">{{t('门店')}}等级：{{mendian.levelname}}</text>
			</view>
			
			<view class="sets"  >
					<image :src="pre_url+'/static/img/set.png'" @tap="goto" data-url="set"/>
					<view   class="setup-view" @tap="saoyisao"  >
						<image :src="`${pre_url}/static/img/admin/saoyisao.png`"></image>
					</view>
			</view>
	</view>
	<view class="contentdata">
		<view class="title"><text class="t1">{{t('门店')}}概况</text><view class="t2"  @tap="getqrcode">推广二维码<text class="iconfont iconjiantou" style="color:#0F89F0;font-weight:normal"></text></view> </view>
		<view class="custom_field">
			<view class='item' data-url='jdorderlist?st=3' @tap='goto' v-if="sets.frontincome_status">
				<text class="t1">{{mendian.totalmoney}}</text>
				<text class='t2'>总收入(元)</text>
			</view>
			<view class='item'>
				<text class="t1">{{mendian.totalnum}}</text>
				<text class='t2'>总订单</text>
			</view>
			<view class='item'>
				<text class="t1">{{mendian.membernum}}</text>
				<text class='t2'>会员数</text>
			</view>
		</view>
	</view>
	<view class="contentdata" style="margin-top: 0;">
		<view class="title"><text class="t1">今日详情</text></view>
		<view class="custom_field">
			<view class='item' data-url='jdorderlist?st=3' @tap='goto'>
				<text class="t1">{{mendian.daytotalnum}}</text>
				<text class='t2'>订单总数(笔)</text>
			</view>
			<view class='item'>
				<text class="t1">{{mendian.paymembernum}}</text>
				<text class='t2'>付款人数</text>
			</view>
			<view class='item'>
				<text class="t1">{{mendian.daypaymoney}}</text>
				<text class='t2'>销售额</text>
			</view>
			<view class='item'>
				<text class="t1">{{mendian.daymembercount}}</text>
				<text class='t2'>新增会员数</text>
			</view>
			<view class='item'>
				<text class="t1">{{mendian.count4}}</text>
				<text class='t2'>售后订单(笔)</text>
			</view>
			<view class='item' v-if="sets.frontincome_status">
				<text class="t1">{{mendian.ygmoney}}</text>
				<text class='t2'>预估收入</text>
			</view>
		</view>
	</view>
			<view class="mall-orders flex-col width" >
				<view class="order-title flex-bt">
					<view class="title-text flex-y-center"><image class="left-img" :src="`${pre_url}/static/img/admin/titletips.png`"></image>{{t('门店')}}订单</view>
					<view class="all-text flex-y-center" @tap="goto" data-url="orderlist">全部订单<image class="right-img" :src="`${pre_url}/static/img/admin/jiantou.png`"></image></view>
				</view>
				<view class="order-list flex-bt">
					<view class="option-order flex-col" @tap="goto" data-url="orderlist?st=0">
						<text class="num-text" :style="{color:t('color1')}">{{order.count0}}</text>
						<text class="title-text">待付款</text>
					</view>
					<view class="option-order flex-col" @tap="goto" data-url="orderlist?st=1">
						<text class="num-text" :style="{color:t('color1')}">{{order.count1}}</text>
						<text class="title-text">待发货</text>
					</view>
					<view class="option-order flex-col" @tap="goto" data-url="orderlist?st=2">
						<text class="num-text" :style="{color:t('color1')}">{{order.count2}}</text>
						<text class="title-text">待收货</text>
					</view>
					<view class="option-order flex-col" @tap="goto" data-url="shopRefundOrder">
						<text class="num-text" :style="{color:t('color1')}">{{order.count4}}</text>
						<text class="title-text">退款/售后</text>
					</view>
					<view class="option-order flex-col" @tap="goto" data-url="orderlist?st=3">
						<text class="num-text" :style="{color:t('color1')}">{{order.count3}}</text>
						<text class="title-text">已完成</text>
					</view>
				</view>
			</view>
			
		<view class="listcontent">
			<view class="list" v-if="sets.frontincome_status">
				<view class="item" @tap="goto" data-url="withdraw">
					<view class="f1"><image :src="pre_url+'/static/img/peisong/ico-qianbao.png'"></image></view>
					<view class="f2">我的钱包</view>
					<text class="f3">可用佣金：{{mendian.money}}</text>
					<image :src="pre_url+'/static/img/arrowright.png'" class="f4"></image>
				</view>
			</view>
			<view class="list" v-if="sets.frontincome_status">
				<view class="item" @tap="goto" data-url="moneylog">
					<view class="f1"><image :src="pre_url+'/static/img/peisong/ico-zhangdan.png'"></image></view>
					<view class="f2">结算记录</view>
					<text class="f3"></text>
					<image :src="pre_url+'/static/img/arrowright.png'" class="f4"></image>
				</view>
			</view>
			<view class="list">
				<view class="item" @tap="goto" data-url="/pagesB/mendianup/fahuolog">
					<view class="f1"><image :src="pre_url+'/static/img/peisong/ico-zhangdan.png'"></image></view>
					<view class="f2">拣货单</view>
					<text class="f3"></text>
					<image :src="pre_url+'/static/img/arrowright.png'" class="f4"></image>
				</view>
			</view>
			<view class="list">
				<view class="item" @tap="goto" data-url="/pagesB/mendianup/hexiaolog">
					<view class="f1"><image :src="pre_url+'/static/img/peisong/ico-zhangdan.png'"></image></view>
					<view class="f2">核销记录</view>
					<text class="f3"></text>
					<image :src="pre_url+'/static/img/arrowright.png'" class="f4"></image>
				</view>
			</view>
			<view class="list" v-if="sets.addhxuser_status==1">
				<view class="item" @tap="goto" data-url="hexiaouser">
					<view class="f1"><image :src="pre_url+'/static/img/peisong/ico-zhangdan.png'"></image></view>
					<view class="f2">核销员管理</view>
					<text class="f3"></text>
					<image :src="pre_url+'/static/img/arrowright.png'" class="f4"></image>
				</view>
			</view>
		</view>
		
		<view style="height: 100rpx;"></view>

		<view class="modal" v-if="showqrcode" @tap="showqrcodes" >
			<view class="modalcontent">
				<view class="title">推广二维码</view>
				<view class="code"><image @tap="previewImage" :data-url="url"  :src="url" /></view>
				<view class="bottom" @tap.stop="saveimage(url)">保存图片</view>
			</view>
		</view>

	</block>
	<wxxieyi></wxxieyi>
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
			mendian:{},
			checked:'',
			sets:false,
			url:'',
			showqrcode:false,
			order:[],
			changemendian:false
    };
  },
  onLoad: function (opt) {
		var that= this
		this.opt = app.getopts(opt);
		uni.setNavigationBarTitle({
			title: that.t('门店') + '中心'
		});
		this.getdata();
  },
	onPullDownRefresh: function () {
		this.getdata();
	},
  methods: {
		getdata:function(){
			var that = this;
			app.get('ApiMendianCenter/my', {}, function (res) {
				that.mendian = res.mendian;
				that.sets = res.sets;
				that.order = res.order
				that.changemendian = res.changemendian || false;
				that.loaded();
			});
		},
    switchchange: function (e) {
      console.log(e);
      var value = e.detail.value ? 1 : 0;
      app.post('ApiMendianCenter/setpsst', {st: value}, function (data) {});
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
							app.goto('/pagesA/mendiancenter/hexiao?'+params);
						}
					});
				});
				// #endif
			}else{
				uni.scanCode({
					success: function (res) {
						console.log(res);
						if(res.path){
								app.goto('/'+res.path);
						}else{
							var content = res.path;		
							var content = res.result;
							var params = content.split('?')[1];
							var url = '/pagesA/mendiancenter/hexiao?'+params;
							var param = app.getparams(url);
							app.goto(url);
						}
					}
				});
			}
    },
		saveimage:function(pic){
			var that = this;
			if(app.globalData.platform == 'mp' || app.globalData.platform == 'h5'){
				app.error('请长按图片保存');return;
			}
			app.showLoading('图片保存中');
			uni.downloadFile({
				url: pic,
				success (res) {
					if (res.statusCode === 200) {
						uni.saveImageToPhotosAlbum({
							filePath: res.tempFilePath,
							success:function () {
								app.success('保存成功');
							},
							fail:function(){
								app.showLoading(false);
								app.error('保存失败');
							}
						})
					}
				},
				fail:function(){
					app.showLoading(false);
					app.error('下载失败');
				}
			});
		},
		getqrcode:function(){
			var that = this;
			app.get('ApiMendianCenter/getqrcode', {}, function (res) {
				that.showqrcode =true;
				if(res.status==1){
								that.url = res.url;
				}else{
					app.error(res.msg)
					that.showqrcode =false;
				}
	
				that.loaded();
			});
		},
		
		showqrcodes:function(e){
				var that = this;
				that.showqrcode = !this.showqrcode;
		}
  }
};
</script>
<style>
.banner{ display:flex;width:100%;height:322rpx;padding:40rpx 32rpx 40rpx 32rpx;color:#fff;position:relative;
background: linear-gradient(-45deg, #FF7E5E 0%, #FF7E5E 100%);}
.banner image{ width:120rpx;height:120rpx;border-radius:50%;margin-right:20rpx}
.banner .info{display:flex;flex:auto;flex-direction:column;padding-top:10rpx}
.banner .info .nickname{font-size:32rpx;font-weight:bold;padding-bottom:12rpx;display: flex;}
.banner .info .changemendian{display: flex;align-items: center;white-space: nowrap;}
.banner .info .t1{ font-size: 24rpx;line-height: 34rpx;}
.banner .sets{ width:70rpx;height:80rpx;line-height:80rpx;font-size:40rpx;text-align:center}
.banner .sets image{width:50rpx;height:50rpx;border-radius:0;margin-left: 20rpx;}
.banner .sets .setup-view image{ width:60rpx;height:60rpx;margin-left: 10rpx;}

.contentdata{display:flex;flex-direction:column;width:95%;padding:30rpx 30rpx;position:relative;background:#fff;border-radius:16rpx; margin:-100rpx 20rpx 30rpx;}
.contentdata .title { text-align: center; font-size:28rpx; position: relative; }
.contentdata .title .t1{ font-weight:bold}
.contentdata .title .t2{ position: absolute;  width: 50%; text-align: right;color: #0F89F0; right: 20rpx; font-size:24rpx; top:0;}

.custom_field{display:flex;width:100%;align-items:center;flex-wrap: wrap; }
.custom_field .item{display:flex;flex-direction:column;justify-content:center;align-items:center; width:33.33%;margin-top: 20rpx;}
.custom_field .item .t1{color:#666;font-size:30rpx;margin-top:10rpx;font-weight:bold;}
.custom_field .item .t2{color:#111;font-size:24rpx;margin-top:20rpx}

.score{ display:flex;width:100%;align-items:center;padding:10rpx 20rpx;background:#fff;border-top:1px dotted #eee}
.score .f1 .t2{color:#ff3300}

.list{ width: 100%;background: #fff;margin-top:20rpx;padding:0 20rpx;font-size:30rpx;margin-bottom:20rpx;border-radius:16rpx}
.list .item{ height:100rpx;display:flex;align-items:center;border-bottom:1px solid #eee}
.list .item:last-child{border-bottom:0;margin-bottom:20rpx}
.list .f1{width:50rpx;height:50rpx;line-height:50rpx;display:flex;align-items:center}
.list .f1 image{ width:44rpx;height:44rpx;}
.list .f1 span{ width:40rpx;height:40rpx;font-size:40rpx}
.list .f2{color:#222;font-weight:bold;margin-left:10rpx}
.list .f3{ color:#FF7E5E;font-size:26rpx;text-align:right;flex:1}
.list .f4{ width: 40rpx; height: 40rpx;}

switch{transform:scale(.7);}
.listcontent{ padding:0 20rpx; }

.mall-orders{border-radius:12rpx;overflow: hidden;background: #fff; margin:0 20rpx}
.order-title{padding: 32rpx 40rpx;align-items: center; border-bottom:1rpx solid #f6f6f6}
.order-title .title-text{font-size: 26rpx;font-weight: 500;color: #222;}
.order-title .all-text{font-size: 24rpx;color: #5f6064;}
.order-title .title-text .left-img{width: 6rpx;height: 24rpx;margin-right: 12rpx;}
.order-title .all-text .right-img{width: 10rpx;height: 20rpx;margin-left: 20rpx;}
.order-list{justify-content: space-around;padding:40rpx 40rpx;}
.order-list .option-order{align-items: center;}
.order-list .option-order .num-text{font-size: 28rpx;font-weight: bold;padding-bottom:10rpx;}
.order-list .option-order .title-text{font-size: 24rpx;color: #5f6064;}
.order-list .option-order  .iconfont{font-size:60rpx}

.modal{ position: fixed; width: 100%;height: 100%; background:rgba(0,0,0,0.4); top:0}
.modalcontent{ width: 80%;position: absolute;top: 30%;background: #fff;border-radius: 20rpx; left:10%}
.modalcontent .title{ text-align: center;height: 80rpx;line-height: 80rpx;font-size: 30rpx;}
.modalcontent .code{ display: flex;align-items: center;}
.modalcontent .code image{ width: 300rpx;height: 300rpx;margin: 0 auto;}
.modalcontent .bottom{ height: 100rpx; line-height:100rpx;color: #0F89F0; text-align:center;border-top: 1rpx solid #fefefe;}
</style>