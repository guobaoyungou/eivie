<template>
	<view class="container">
		<block v-if="isload">
			<view class="top">
				<view class="title">累计获得</view>
				<view class="item">
					<view class="score" >
						<view class="f2">
							<text class="t1">{{info.totalscore}}</text>
							<text class="t2">{{t('积分')}}</text>
						
						</view>
						<view class="f2">
							<text class="t1">{{info.totalmoney}}</text>
							<text class="t2">{{t('余额')}}</text>
						
						</view>
						<view class="f2">
							<text class="t1">{{info.totalcoupon}}</text>
							<text class="t2">{{t('优惠券')}}</text>
	
						</view>
						
					</view>
				</view>
			</view>
	
			<view class="center2">
			
				<view class="item" v-if="datalist" v-for="(item,index) in datalist">
					<view class="headimg">
						<image  :src="item.headimg" />
						<view class="f1">
							<view class="t1">{{item.nickname}}</view>
							<view class="t2">{{item.createtime}} 开卡</view>
						</view>
					</view>
					<view class="kkimg">
						<text v-if="item.parent_givemoney>0">
							{{item.parent_givemoney}}{{t('余额')}} 
						</text>
						<text v-if="item.parent_givescore>0">
						  	+ {{item.parent_givescore}} {{t('积分')}}
						</text> 
						<text v-if="item.coupon">
							+ {{item.coupon}} {{t('优惠券')}}
						</text>
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
			nomore:false,
			nodata:false,
			datalist: [],
			pagenum: 1,
			info:[]
    }
  },
  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.getdata();
  },
	onPullDownRefresh: function () {
	  this.pagenum = 1;
	  this.datalist = [];
		this.getdata();
	},
	onReachBottom: function () {
	  if (!this.nodata && !this.nomore) {
	    this.pagenum = this.pagenum + 1;
	    this.getdata();
	  }
	},
	methods: {
		getdata: function () {
			var that = this; 
			that.loading = true;
		  var pagenum = that.pagenum;
			app.post('ApiMembercardCustom/getjiangli', {id: that.opt.id,pagenum: pagenum}, function (res) {
				that.loading = false;
				uni.setNavigationBarTitle({
					title:  '我的奖励'
				});
				if(res.status==1){
					var data = res.data;
					that.info = res.info
					if (pagenum == 1) {
						that.datalist = data;
						if (data.length == 0) {
							that.nodata = true;
						}
					}else{
						if (data.length == 0) {
							that.nomore = true;
						} else {
							var datalist = that.datalist;
							var newdata = datalist.concat(data);
							that.datalist = newdata;
						}
					}
					that.loaded();
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
	.top{ padding:30rpx; background:#fff;margin-bottom: 30rpx;}

	.top .title{ font-size: 30rpx; color: #000;}
	.top .item{ display: flex;margin-top: 30rpx;}
	.top .item .score{ display: flex;margin-right: 30rpx; width: 100%;justify-content: space-around; }
	.top .item .f2 { display: flex;flex-direction: column;text-align: center; }
	.top .item .f2 .t1{ color:#D0735A ; font-weight:bold;font-size: 30rpx;margin-bottom: 20rpx;}
	.top .item .f2 .t2{ font-display: flex; font-size:24rpx; color:#999;text-align: center; }
	
	.center2 .title{ text-align: center; font-size:30rpx; font-weight:bold;margin:50rpx;}
	.center2 .item{ display: flex;position: relative;border-bottom:2rpx solid #F6F6F6;padding-bottom: 20rpx; background:#fff; padding:20rpx;justify-content: space-between; }
	.center2 .headimg { display: flex;align-items: center;}
	.center2 .headimg .f1{ color: #333;}
	.center2 .headimg  .t1{ margin-bottom: 10rpx;}
	.center2 .headimg  .t2{ font-size: 20rpx;color: #999;}
	.center2 .headimg image{ width: 100rpx;height: 100rpx;border-radius: 50%;margin-right: 20rpx;}
	.center2 .kkimg{ width: 50%; }

	
	
</style>
