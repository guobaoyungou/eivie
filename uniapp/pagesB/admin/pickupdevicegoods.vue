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
			<view class="item" >
				<image :src="item.pic" class="img"></image>
				<view class="f1">
					<view class="title">柜门编号-{{item.goods_lane}}</view>
					<view class="t1"><text>商品信息：</text>{{item.proname}}[{{item.ggname}}]</view>
					<view class="t1"><text >近期补货：</text>{{item.add_time}}</view>
					<view class="flex" style="justify-content: flex-end;">
						<button class="btn" @tap="addstock(item.goods_lane)" :style="{background:t('color2')}" v-if="item.lack_stock > 0">补货</button>	
						<button class="btn" @tap="openBox(item.goods_lane)" :style="{background:t('color1')}">开门</button>
					</view>	
				</view>
				<!-- <view class="status"> <text v-if="device_status ==1">在线</text><text v-else style="color:#fff;">离线</text>	 </view> -->
				<view class="addstock" v-if="item.lack_stock > 0 " >
					<image class="img2"  :src="pre_url+'/static/imgsrc/pickup_device_left.png'"></image>
					<view class="text" >待补货</view>
				</view>
			</view>
			</block>
			<nodata v-if="nodata"></nodata>
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
		device_id:'',
		keyword:''
    };
  },

  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		if(this.opt){
			this.device_id = this.opt.id
		}
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
		var device_id=that.device_id;
		var keyword = that.keyword;
		app.post('ApiAdminPickupDevice/getdevicegoodslist', {device_id:device_id,pagenum: pagenum,keyword:keyword}, function (res) {
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
	openBox:function(goods_lane){
		var that = this;
		var device_id = this.device_id;
	
		app.confirm('确定要开启'+goods_lane+'号柜门吗', function () {
			that.loading = true;
			app.post('ApiAdminPickupDevice/openbox', {device_id:device_id,goods_lane: goods_lane}, function (res) {
				if(res.status ==1){
					app.success(res.msg);
				}else{
					app.error(res.msg);
				}
				that.loading = false;
			});
		})
	},
	addstock:function(goods_lane){
		var that = this;
		var device_id = this.device_id;
		app.confirm('确定补货完成?', function () {
			that.loading = true;
			app.post('ApiAdminPickupDevice/addstock', {device_id:device_id,goods_lane: goods_lane}, function (res) {
				if(res.status ==1){
					app.success(res.msg);
					that.getdata();
				}else{
					app.error(res.msg);
				}
				that.loading = false;	
			});
		 })
	}
  }
};
</script>
<style>

.content{ width:94%;margin:0 3%;}
.content .item{width:100%;margin:20rpx 0;background:#fff;border-radius:5px;padding:25rpx 25rpx;display:flex;align-items:center;justify-content: space-between;position: relative;align-items: normal;}
.content .item .f1{flex: 1;padding-left: 20rpx;font-size: 28rpx;}
.content .item .f1 .title{color:#000000;font-size:30rpx;word-break:break-all;overflow:hidden;text-overflow:ellipsis;max-width:400rpx;line-height: 45rpx;font-weight: 700;}
.content .item .f1 .t1{line-height: 50rpx;}
.content .item .f1 .t1 text{color: #B0B0B0;line-height: 45rpx}
.content .item  .img{width: 200rpx;height: 200rpx;}
.content .item .f1 .btn{ width: 120rpx; height:60rpx; line-height: 60rpx;border-radius:8rpx; color: #fff; border: none;background-color: #0F5EE5;margin: 0 10rpx;}
.content .item .f1 .flex{justify-content: flex-end;}
.content .item .status{width: 80rpx;height: 45rpx;background: #D0DAE8;border-radius: 10rpx;position: absolute;right: 1rpx;top: 1rpx;text-align: center;line-height: 45rpx;color: #0F5EE5;font-size: 24rpx;margin: 0;}

.addstock .img2{width: 110rpx;height: 110rpx;position: absolute;left: 0;top: 0;}
.addstock .text{position: absolute;left: 3rpx;top:23rpx; font-size: 24rpx;transform: rotate(-45deg);color: #fff;font-weight: 700;}


.topsearch{width:94%;margin:10rpx 3%;}
.topsearch .f1{height:60rpx;border-radius:30rpx;border:0;background-color:#fff;flex:1}
.topsearch .f1 .img{width:24rpx;height:24rpx;margin-left:10px}
.topsearch .f1 input{height:100%;flex:1;padding:0 20rpx;font-size:28rpx;color:#333;}
</style>