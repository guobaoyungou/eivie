<template>
	<view class="container">
		<block v-if="isload">
			<view class="top">
				<view class="cardbg"  v-if="data.bg_type==1">
					<image :src="data.background_pic_url" />
				</view>
				<view class="cardbg" :style="'background: '+data.color" v-else></view>
				<view class="hyk flex">
					<view class="f1"><image :src="data.logo_url" ></view>
					<view class="f2">
						<text class="t1">{{data.brand_name}}</text>
						<text class="t2">{{data.title}}</text>
					</view>
				</view>
			</view>	
			<view style="height: 100rpx;"> </view>
			<view class="center">
				<view class="title">开卡即可获得以下奖励</view>
				<view class="item">
					<view class="score" v-if="data.givescore>0">
						<view class="f1"><image :src="pre_url+'/static/img/membercard/score_icon.png'" /></view>
						<view class="f2">
							<text class="t1">{{data.givescore}}</text>
							<text class="t2">{{t('积分')}}</text>
						</view>
					</view>
					<view class="score" v-if="data.givemoney>0">
						<view class="f1"><image :src="pre_url+'/static/img/membercard/money.png'" /></view>
						<view class="f2">
							<text class="t1">{{data.givemoney}}</text>
							<text class="t2">{{t('余额')}}</text>
						</view>
					</view>
					<view class="score" v-if="data.couponcount>0">
						<view class="f1"><image :src="pre_url+'/static/img/membercard/coupon_icon2.png'" /></view>
						<view class="f2">
							<text class="t1">{{data.couponcount}}</text>
							<text class="t2">{{t('优惠券')}}</text>
						</view>
					</view>
				</view>
				<view class="cooupondesc">
						<image :src="pre_url+'/static/img/membercard/icon.png'">
						<text class="t1" v-if="data.couponlist" v-for="(item,index) in data.couponlist">{{item.name}} {{item.nums}}张</text>
				</view>
			</view>	
			
			<view class="bottom">
				<view class="title">会员卡详情</view>
				<view class="content">
					<view class="itembox">
						<view class='item'><label>特权说明</label><text class="t1">{{data.prerogative}}</text></view>
						<view class='item'>
							<label>联系电话</label>
							<text class="t2" @tap="goto" :data-url="'tel::'+data.service_phone">{{data.service_phone}}</text>
						</view> 
						<view class='item'><label>使用说明</label><text class="t1">{{data.description}}</text></view>
					</view>
				</view>
			</view>
			<view  class="buttonbox">
			<button class="button" @tap="goto" :data-url="data.ret_url" :style="{background:'linear-gradient(-90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'}">
					立即开卡
				</button>

			</view>

		</block>		
		<loading v-if="loading"></loading>
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
			data:{},
			coupon:{},
			shareTitle:'',
			sharePic:'',
			shareDesc:'',
			shareLink:'',
			mid:0,
			picker_date:''
    }
  },
  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.getdata();
  },
	methods: {
		getdata: function () {
			var that = this; 
			that.loading = true;
			app.get('ApiMembercardCustom/receive', {id: that.opt.id,pid:that.opt.pid}, function (res) {
				that.loading = false;
				
				uni.setNavigationBarTitle({
					title:  '会员卡领取'
				});
				if(res.status==1){
					that.data = res.data
					that.loaded(true);
				}else {
					if(res.payorderid){
						app.goto('/pagesExt/pay/pay?id=' + res.payorderid,'redirectTo');
					}else if(res.url){
						app.goto(res.url,'redirectTo');
					}else{
						app.error(res.msg)
					}
				}

			});
		},
		//领取微信会员卡
		addmembercard:function(e){
			var cardId = e.currentTarget.dataset.card_id
			app.post('ApiCoupon/getmembercardparam',{card_id:cardId},function(res){
				if(res.status==0){
					app.alert(res.msg);
					return;
				}
				if(res.status==2){
					wx.addCard({
						cardList: [{
							cardId: cardId,
							cardExt: ''
						}], // 需要添加的卡券列表
						success: function (res) {
							var cardList = res.cardList; // 添加的卡券列表信息
							console.log(cardList);
						}
					});
					return;
				}
				wx.navigateToMiniProgram({
					appId: 'wxeb490c6f9b154ef9', // 固定为此appid，不可改动
					extraData: res.extraData, // 包括encrypt_card_id outer_str biz三个字段，须从step3中获得的链接中获取参数
					success: function() {},
					fail: function() {},
					complete: function() {}
				})
			})
		},
	}
}
</script>
<style>
	.top{ position: relative; height:350rpx; width: 100%;}
	.cardbg{ position: absolute; width: 88%; height:350rpx;margin: 50rpx;border-radius: 10rpx;}
	.cardbg image{ width: 100%; height:100%;border-radius: 10rpx;}
	.hyk{ margin-top: 30rpx; z-index: 1000;position: absolute; left:10%; top:20%}
	.hyk .f1 image{ width: 100rpx;height: 100rpx; border-radius:50%}
	.hyk .f2 { display: flex;flex-direction: column;margin-left: 20rpx;}
	.hyk .f2 .t1{ margin-top: 10rpx;color: #fff;font-size: 30rpx;font-weight: bold;}
	.hyk .f2 .t2{ color: #F5F2F2;font-size: 24rpx;}
	
	
	.center{ background: #fff;margin:0 50rpx; padding:20rpx; border-radius:10rpx }
	.center .title{ font-weight: bold; font-size:26rpx}
	.center .item{ display: flex;margin-top: 30rpx;}
	.center .item .score{ display: flex;margin-right: 30rpx; padding-left: 20rpx; }
	.center .item .f1{ }
	.center .item .f1 image{ width: 60rpx;height: 60rpx;border-radius: 50%; margin-right: 10rpx; }
	.center .item .f2 { display: flex;flex-direction: column;text-align: center; }
	.center .item .f2 .t1{ color:#D0735A ; font-weight:bold}
	.center .item .f2 .t2{ font-display: flex; font-size:24rpx; color:#999;text-align: center; }
	.cooupondesc{ display: flex;align-items: center;padding:0 20rpx}
	.cooupondesc image{ width: 40rpx;height: 40rpx; }
	.cooupondesc .t1{ font-size: 24rpx;color: #999;margin-left: 10rpx;  line-height: 60rpx;}
	
	.bottom{margin:30rpx 50rpx; }
	.bottom .title{ font-size: 26rpx;font-weight: bold;}
	.bottom .content{ display:flex;margin-top: 20rpx;}
	.bottom .itembox { background: #fff; width: 100%;border-radius: 10rpx;  padding:10rpx 20rpx;}
	.bottom .itembox .item{ height: 80rpx;line-height: 80rpx; font-size:24rpx}
	.bottom .itembox .item label{ color: #999;}
	.bottom .itembox .item .t1{ margin-left: 20rpx;}
	.bottom .itembox .item .t2{ margin-left: 20rpx;color:#80B76F}
	
	
	.buttonbox{ position: fixed; bottom:0; width:100%; background-color: #fff;padding:20rpx}
	.button{ width: 88%;margin: auto;height: 70rpx;line-height: 70rpx;color: #fff;text-align: center;border-radius: 15rpx; }
	
	
	
</style>
