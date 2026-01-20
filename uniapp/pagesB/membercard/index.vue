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
				<view class="cardcode" v-if="data.code_type=='text'">{{info.card_code}}</view>
				<view class="cardcode" v-else-if="data.code_type=='tel'">{{info.mobile}}</view>
			</view>	
			<view style="height: 100rpx;"> </view>
			<view class="center">
				<view class="item">
					<view class="score" v-for="(item,index) in data.custom_field">
						<view class="f2" v-if="item=='积分'">
							<text class="t2">{{t('积分')}}</text>
							<text class="t1">{{info.score}}</text>
						</view>
						<view class="f2" v-if="item=='余额'">
							<text class="t2">{{t('余额')}}</text>
							<text class="t1">{{info.money}}</text>
						</view>
						<view class="f2" v-if="item=='优惠券'">
							<text class="t2">{{t('优惠券')}}</text>
							<text class="t1">{{info.coupon}}</text>
						</view>
						<view class="f2" v-if="item=='等级'">
							<text class="t2">等级</text>
							<text class="t1">{{info.levelname}}</text>
						</view>
						<view class="f2" v-if="item=='自定义1'" @tap="goto" :data-url="data.custom_field_customize1_link">
							<text class="t2">{{data.custom_field_customize1_name}}</text>
							<text class="t1">{{data.custom_field_customize1_value}}</text>
						</view>
						<view class="f2" v-if="item=='自定义2'" @tap="goto" :data-url="data.custom_field_customize12_link">
							<text class="t2">{{data.custom_field_customize2_name}}</text>
							<text class="t1">{{data.custom_field_customize1_value}}</text>
						</view>
					</view>
				</view>
			</view>	
			
			<view v-if="data.center_title" class="center2" @tap="goto" :data-url="data.center_url">
				<view class="f1"><text class="name">{{data.center_title}}</text></view>
				<view class="f2"><text class="sub"> {{data.center_sub_title}}</text></view>
			</view>
			
			<view class="fieldlist">
				<view class="item" v-if="data.custom_url_name" @tap="goto" :data-url="data.custom_url">
						<label>{{data.custom_url_name}}</label>
						<view class="t2">
							{{data.custom_url_sub_title}}
							<text class="iconfont iconjiantou" style="color:#999;font-weight:normal"></text>
						</view>
				</view>
				<view class="item" v-if="data.custom_cell1_name" @tap="goto" :data-url="data.custom_cell1_url">
						<label>{{data.custom_cell1_name}}</label>
						<view class="t2">
							{{data.custom_cell1_tips}}
							<text class="iconfont iconjiantou" style="color:#999;font-weight:normal"></text>
						</view>
				</view>
				<view class="item" v-if="data.promotion_url_name" @tap="goto" :data-url="data.promotion_url">
						<label>{{data.promotion_url_name}}</label>
						<view class="t2">
							{{data.promotion_url_sub_title}}
							<text class="iconfont iconjiantou" style="color:#999;font-weight:normal"></text>
						</view>
				</view>
				<view class="item" v-if="data.promotion_url_name2" @tap="goto" :data-url="data.promotion_url2">
						<label>{{data.promotion_url_name2}}</label>
						<view class="t2">
							{{data.promotion_url_sub_title2}}
							<text class="iconfont iconjiantou" style="color:#999;font-weight:normal"></text>
						</view>
				</view>
				<view class="item"  @tap="goto" :data-url="'detail?id='+data.id">
						<label>会员卡详情</label>
						<view class="t2">
							<text class="iconfont iconjiantou" style="color:#999;font-weight:normal"></text>
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
			textset:{},
			data:{},
			coupon:{},
			shareTitle:'',
			sharePic:'',
			shareDesc:'',
			shareLink:'',
			mid:0,
			info:[]
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
			app.get('ApiMembercardCustom/gethyk', {id: that.opt.id}, function (res) {
				that.loading = false;
				uni.setNavigationBarTitle({
					title:  '会员卡'
				});
				if(res.status==1){
					that.data = res.data
					that.info = res.info
					that.loaded(true);
				}else{
					if(res.msg){
							app.alert(res.msg);
					}
					setTimeout(function () {
						app.goto(res.url,'redirectTo');
					}, 1000);
				}

			});
		},
		formSubmit: function (e) {
			var that = this;
			var subdata = e.detail.value;
			console.log(subdata);
			var fieldlist = that.data.field_list;
			for (var i = 0; i < fieldlist.length; i++) {
				//console.log(subdata['form' + i]);
				if (fieldlist[i].required == 1 && (subdata[fieldlist[i].key] === '')) {
					console.log(subdata[fieldlist[i].key]);
					app.alert(fieldlist[i].name + ' 必填');
					return;
				}
			}

			app.post("ApiMembercardCustom/getcard" , {hykid: that.data.id, subdata: subdata}, function (res) {
				if (res.status == 1) {
					app.success(res.msg);
					if(res.payorderid)
						app.goto('/pagesExt/pay/pay?id=' + res.payorderid,'redirectTo');
				} else {
					app.alert(res.msg);
				}
			});
		},
		date_Change:function(e){
			this.picker_date = e.detail.value;
		},
	}
}
</script>
<style>
	page{ background: #fff;}
	
	.top{ position: relative; height:350rpx; width: 100%;}
	.cardbg{ position: absolute; width: 88%; height:350rpx;margin: 50rpx;border-radius: 10rpx;}
	.cardbg image{ width: 100%; height:100%;border-radius: 10rpx;}
	.hyk{ margin-top: 30rpx; z-index: 1000;position: absolute; left:10%; top:20%}
	.hyk .f1 image{ width: 100rpx;height: 100rpx; border-radius:50%}
	.hyk .f2 { display: flex;flex-direction: column;margin-left: 20rpx;}
	.hyk .f2 .t1{ margin-top: 10rpx;color: #fff;font-size: 30rpx;font-weight: bold;}
	.hyk .f2 .t2{ color: #F5F2F2;font-size: 24rpx;}
	.cardcode{ position: absolute; bottom:-20rpx; color:#fff; left:10%}
	
	
	.center{ background: #fff;margin:0 50rpx; padding:20rpx; border-radius:10rpx }
	.center .title{ font-weight: bold; font-size:26rpx}
	.center .item{ display: flex;margin-top: 30rpx; width: 100%;}
	.center .item .score{ display: flex;margin-right: 30rpx;     width: 33%; justify-content: center;}
	.center .item .f2 { display: flex;flex-direction: column;text-align: center; }
	.center .item .f2 .t1{ color:#64B35A ; font-weight:bold;margin-top: 20rpx;}
	.center .item .f2 .t2{ font-display: flex; font-size:24rpx; color:#999;text-align: center; }

	.center2{ display: flex;justify-content: center;flex-direction: column; margin-top:50rpx }
	.center2 .f1{ margin:0 auto }
	.center2 .name{ border: 1rpx solid #64B35A; color: #64B35A; padding:10rpx 30rpx; border-radius:10rpx}
	.center2 .sub{ color:#999;}
	.center2 .f2{ margin:20rpx auto }
	

	.fieldlist { padding:30rpx; background:#fff; margin:0 50rpx; border-radius:10rpx }
	.fieldlist .item{ display: flex;height: 70rpx;line-height: 70rpx; border-bottom:1rpx dashed #F5F2F2;align-items: center;justify-content: space-between;}
	.fieldlist .item .t2{ color: #999;}
	
</style>
