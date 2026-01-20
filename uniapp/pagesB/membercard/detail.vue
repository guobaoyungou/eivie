<template>
	<view class="container">
		<block v-if="isload">
			<view class="itembox">
				<view class="title">会员卡详情</view>
				<view class="item">
					<view class="label">特权说明</view>
					<view class="t1">{{data.prerogative}}</view>
				</view>
				<view class="item">
					<view class="label">有效日期</view>
					<view class="t1" >{{data.yxq}}</view>
			
				</view>
				<view class="item" @tap="goto" :data-url="'tel::'+data.service_phone">
					<view class="label">电话</view>
					<view class="t1 tel">{{data.service_phone}}</view>
				</view>
				<view class="item">
					<view class="label">使用须知</view>
					<view class="t1">{{data.description}}</view>
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
			data:{},
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
			app.get('ApiMembercardCustom/getdetail', {id: that.opt.id}, function (res) {
				that.loading = false;
				uni.setNavigationBarTitle({
					title:  '会员卡'
				});
				if(res.status==1){
					that.data = res.data
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
	
	}
}
</script>
<style>
	page{ background: #fff;}
	
	
	.itembox{ background: #fff;margin:0 50rpx; padding:20rpx;  }
	.itembox .title{ font-weight: bold; font-size:30rpx;border-bottom: 1rpx solid #f3f3f3;padding: 30rpx 0;}
	.itembox .item{ display: flex;margin-top: 30rpx; width: 100%;}
	.itembox .item .label{ color: #999; width: 200rpx;}

	.itembox .item .t1{ color:#000 ; width: 80%; }
	.itembox .item .t1.tel{ color: #80B76F;}
	
</style>
