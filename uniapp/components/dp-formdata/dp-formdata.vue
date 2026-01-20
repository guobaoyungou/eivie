<template>
<view class="dp-business" :style="{
	color:params.color,
	backgroundColor:params.bgcolor,
	margin:(params.margin_y*2.2)+'rpx '+(params.margin_x*2.2)+'rpx',
	padding:(params.padding_y*2.2)+'rpx '+(params.padding_x*2.2)+'rpx',
	fontSize:(params.fontsize*2)+'rpx'
}">
	<view class="busbox" v-for="(item,index) in data" :key="item.id">
		<view class="businfo" @click="goto" :data-url="'/pagesA/formdata/detail?id='+item.id">
			<view class="f1" style="width: 140rpx;height: 140rpx;"><image class="image" lazy-load="true" lazy-load-margin="0" :src="item.logo" style="border-radius:12rpx"/></view>
			<view class="f2">
				<view class="title">{{item.title}}</view>
				<view class="address" v-if="item.address" @tap.stop="openLocation" :data-latitude="item.latitude" :data-longitude="item.longitude" :data-company="item.title" :data-address="item.address">
          <img :src="pre_url+'/static/img/b_addr.png'" style="width:26rpx;height: 26rpx;margin-right: 20rpx;float: left;margin-top: 4rpx;">{{item.address}}
        </view>
        <view class="address" v-if="item.tel" @tap.stop="phone" :data-phone="item.tel">
          <img :src="pre_url+'/static/img/b_tel.png'" style="width:26rpx;height: 26rpx;margin-right: 20rpx;float: left;margin-top: 4rpx;">{{item.tel}}
        </view>
			</view>
      <view  @tap.stop="goto" :data-url="'/pagesA/formdata/detail?id='+item.id" style="width: 140rpx;text-align: center;line-height: 142rpx;;font-size: 24rpx;">
        查看信息
      </view>
		</view>
	</view>
</view>
</template>
<script>
	var app = getApp();
	export default {
		data(){
			return {
				pre_url:getApp().globalData.pre_url,
			}
		},
		props: {
			menuindex:{default:-1},
			params:{},
			data:{}
		},
		methods: {
      phone:function(e) {
      	var phone = e.currentTarget.dataset.phone;
      	uni.makePhoneCall({
      		phoneNumber: phone,
      		fail: function () {
      		}
      	});
      },
      openLocation:function(e){
      	var latitude  = parseFloat(e.currentTarget.dataset.latitude)
      	var longitude = parseFloat(e.currentTarget.dataset.longitude)
      	var address   = e.currentTarget.dataset.address
        if(latitude && longitude){
          uni.openLocation({
             latitude:latitude,
             longitude:longitude,
             name:address,
             scale: 13
          })	
        }
      		
      },
		}
	}
</script>
<style>
.dp-business{height: auto; position: relative;}
.dp-business .busbox{background: #fff;padding:16rpx;overflow: hidden;margin-bottom:16rpx;width:100%}
.dp-business .businfo{display:flex;width:100%}
.dp-business .businfo .f1{width:200rpx;height:200rpx; margin-right:20rpx;flex-shrink:0}
.dp-business .businfo .f1 .image{ width: 100%;height:100%;border-radius:20rpx;object-fit: cover;}
.dp-business .businfo .f2{flex:1}
.dp-business .businfo .f2 .title{font-size:28rpx;font-weight:bold; color: #222;line-height:46rpx;margin-bottom:3px;}
.dp-business .businfo .f2 .score{font-size:24rpx;color:#f99716;}
.dp-business .businfo .f2 .score .image{width:140rpx; height:50rpx; vertical-align: middle;margin-bottom:3px; margin-right:3px;}
.dp-business .businfo .f2 .sales{font-size:24rpx; color:#31C88E;margin-bottom:3px;}
.dp-business .businfo .f2 .address{color:#999;font-size:24rpx;line-height:40rpx;margin-bottom:3px;overflow: hidden;}
</style>