<template>
<view class="container">
	<block v-if="isload">
		<view class="topsearch flex-y-center">
			<view class="f1 flex-y-center">
				<image class="img" :src="pre_url+'/static/img/search_ico.png'"></image>
				<input :value="keyword" placeholder="输入关键字搜索" placeholder-style="font-size:24rpx;color:#C2C2C2" @confirm="searchConfirm"></input>
			
			</view>
		</view>
		<view class="content" id="datalist">
			<block v-for="(item, index) in datalist" :key="index"> 
			<view class="item" @tap="goto" :data-url="'pickupdevicegoods?id='+item.id">
				<image :src="pre_url+'/static/imgsrc/pickup_device.png'" class="img" mode="widthFix"></image>
				<view class="f1">
					<view class="title">{{item.name}}</view>
					<view class="t1"><text>柜子编号：</text>{{item.device_no}}</view>
					<view class="t1"><text >格子总数：</text>{{item.goods_lane}}</view>
					<view class="t1"><text >柜子地址：</text>{{item.address}}</view>
					<view class="flex" style="justify-content: flex-end;">
						<button class="btn" :style="{background:t('color1')}" v-if="item.lack_stock > 0">补货</button>	
					</view>	
				</view>
				<view class="status"> <text v-if="device_status ==1">在线</text><text v-else style="color:#fff;">离线</text>	 </view>
				
				<view class="addstock" v-if="item.lack_stock > 0 " >
					<image class="img2"  :src="pre_url+'/static/imgsrc/pickup_device_left.png'"></image>
					<view class="text">缺货: {{item.lack_stock}}</view>
				</view>
			</view>
			</block>
			<nodata v-if="nodata"></nodata>
		</view>
		<view class="bottom-view">
			<view class="bottom flex ">
				<view class="button" @click="goto" :data-url="'pickupdeviceaddstock'" :style="{background:t('color1')}">补货统计</view>
				<view class="button" @tap="goto" :data-url="'pickupdeviceaddstocklog'" :style="{background:t('color2')}">补货清单</view>
				
			</view>
		
		</view>
		<nomore v-if="nomore"></nomore>
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
		st: 0,
		datalist: [],
		pagenum: 1,
		mynum: 0,
		nodata: false,
		nomore: false,
		keyword:''
    };
  },

  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.getdata();
  },
	onPullDownRefresh: function () {
		this.getdata();
	},
  onReachBottom: function () {
    if (!this.nodata && !this.nomore) {
      this.pagenum = this.pagenum + 1;
      this.getdata(true);
    }
  },
  methods: {
    getdata: function (loadmore) {
		if(!loadmore){
			this.pagenum = 1;
			this.datalist = [];
		}
		console.log(this.pagenum)
		var that = this;
		var pagenum = that.pagenum;
		var st = that.st;
		that.nodata = false;
		that.nomore = false;
		that.loading = true;
		var keyword=this.keyword;
		app.post('ApiAdminPickupDevice/getdevicelist', {keyword: keyword,pagenum: pagenum}, function (res) {
				that.loading = false;
			var data = res.data;
			if (pagenum == 1) {
				that.datalist = data;
				if (data.length == 0) {
					that.nodata = true;
				}
				that.loaded();
			}else{
			  if (data.length == 0) {
				that.nomore = true;
			  } else {
				var datalist = that.datalist;
				var newdata = datalist.concat(data);
				that.datalist = newdata;
			  }
			}
      });
    },
    searchConfirm:function(e){
    			this.keyword = e.detail.value;
    		this.getdata(false);
    	},
  }
};
</script>
<style>

.content{ width:94%;margin:0 3%;padding-bottom: 70rpx;}
.content .item{width:100%;margin:20rpx 0;background:#fff;border-radius:5px;padding:40rpx 20rpx 20rpx 20rpx;display:flex;justify-content: space-between;position: relative;align-items: start;}
.content .item .f1{flex: 1;padding-left: 20rpx;font-size: 28rpx;}
.content .item .f1 .title{color:#000000;font-size:30rpx;word-break:break-all;overflow:hidden;text-overflow:ellipsis;max-width:400rpx;line-height: 45rpx;}
.content .item .f1 .t1{line-height: 50rpx;}
.content .item .f1 .t1 text{color: #B0B0B0;line-height: 45rpx}
.content .item  .img{width: 200rpx;height: 200rpx;}
.content .item .f1 .btn{ width: 120rpx; height:60rpx; line-height: 60rpx;border-radius:8rpx; color: #fff; border: none;background-color: #0F5EE5;margin: 0;}
.content .item .f1 .flex{justify-content: flex-end;}
.content .item .status{width: 80rpx;height: 45rpx;background: #D0DAE8;border-radius: 5rpx;position: absolute;right: -1rpx;top: -1rpx;text-align: center;line-height: 45rpx;color: #0F5EE5;font-size: 24rpx;margin: 0;}

.addstock .img2{width: 130rpx;height: 130rpx;position: absolute;left: 0;top: 0;}
.addstock .text{width: 130rpx; height:130rpx ; position: absolute;left: 0rpx;top:0rpx; font-size: 24rpx;transform: rotate(-45deg);color: #fff;font-weight: 700;text-align: center;line-height: 70rpx;}

.topsearch{width:94%;margin:10rpx 3%;}
.topsearch .f1{height:60rpx;border-radius:30rpx;border:0;background-color:#fff;flex:1}
.topsearch .f1 .img{width:24rpx;height:24rpx;margin-left:10px}
.topsearch .f1 input{height:100%;flex:1;padding:0 20rpx;font-size:28rpx;color:#333;}
.bottom-view {position: fixed; bottom: 0; width: 100%; background-color: #fff; flex-direction: row-reverse; align-items: center; box-shadow: 0px -10rpx 20rpx 0rpx rgb(0 0 0 / 20%);padding: 20rpx 0 45rpx 0;}
.bottom{justify-content: space-around;}
.bottom-view  .button{margin:0 20rpx;width:220rpx;line-height:70rpx;color:#fff;border-radius:3px;text-align:center; background-color: #007AFF;}

</style>