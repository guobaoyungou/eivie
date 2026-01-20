<template>
	<view class="container">
		<block v-if="isload">
		<view class="tab-box shopping">
			<view class="page-tab">
				<view class="page-tab2">
					<view :class="'item ' + (curTopIndex == -1 ? 'on' : '')" @tap="switchTopTab" :data-index="-1" :data-id="0">全部</view>
					<block v-for="(item, index) in clist" :key="index">
						<view :class="'item ' + (curTopIndex == index ? 'on' : '')" @tap="switchTopTab" :data-index="index" :data-id="item.id">{{item.name}}</view>
					</block>
				</view>
			</view>
			<view class="shop-box">
				<view class="shop-item" v-for="item in contentList" :key="index">
					<image class="shop-img" :src="item.pic ? item.pic : logo" mode="aspectFill"> </image>
					<view class="f2 flex-col flex1">
						<view class="shop-name multi-ellipsis-2">
							{{item.name}}
						</view>
						<view class="desc">{{textset['座位数']}}：{{item.seat}}</view>
						<view class="desc"><text v-if="set.show_booking_fee">预定费：{{item.booking_fee}}</text>最低消费：{{item.limit_fee}}</view>
					</view>
					<view class="f3 button" @click="goto" :data-url="'add?bid='+opt.bid+'&tableId=' + item.id">
						选择
					</view>
				</view>
			</view>
		</view>
		
		<nomore v-if="nomore"></nomore>
		<nodata v-if="nodata"></nodata>
		</block>
		<loading v-if="loading"></loading>
		<dp-tabbar :opt="opt"></dp-tabbar>
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
				nomore: false,
				nodata:false,
				
				pagenum:1,
				contentList:[],
				clist:[],
				curCid:0,
				curTopIndex: -1,
				curIndex: -1,
				logo:'',
				set: {},
        		textset:[]
			}
		},
		onLoad: function (opt) {
			this.opt = app.getopts(opt);
			this.opt.bid = this.opt.bid ? this.opt.bid : 0;
			this.logo = app.globalData.initdata.logo;
			this.getdata();
		},
		onPullDownRefresh: function () {
			this.getdata();
		},
		onReachBottom: function () {
			if (!this.nodata && !this.nomore) {
				this.pagenum = this.pagenum + 1;
				this.getTabContentList();
			}
		},
		methods: {
			getdata: function () {
				var that = this;
				that.loading = true;
				var nowcid = that.opt.cid;
				if (!nowcid) nowcid = 0;
				app.get('ApiRestaurantBooking/tableCategory', {bid:that.opt.bid}, function (res) {
					that.loading = false;
					var data = res.data;
					that.set = res.set;
					that.clist = data;
					that.textset = res.textset;
					var title = that.textset['餐桌']+'列表';
					uni.setNavigationBarTitle({
						title: title
					});
					// that.curCid = data[0]['id'];
					if (nowcid) {
						for (var i = 0; i < data.length; i++) {
							if (data[i]['id'] == nowcid) {
								that.curTopIndex = i;
								that.curCid = nowcid;
								break;
							}
						}
					}
					that.getTabContentList();
					that.loaded();
				});
			},
			
			switchTopTab: function (e) {
			  var that = this;
			  var id = e.currentTarget.dataset.id;
			  var index = parseInt(e.currentTarget.dataset.index);
			  this.curTopIndex = index;
			  this.curIndex = -1;
			  this.contentList = [];
			  this.curCid = id;
				this.pagenum = 1;
			  this.getTabContentList();
			},
			getTabContentList:function(){
				var that = this;
				var pagenum = that.pagenum;
				var tempdata = uni.getStorageSync('restaurant_booking');
				that.nodata = false;
				that.nomore = false;
				that.loading = true;
				console.log(tempdata)
				app.post('ApiRestaurantBooking/tableList', {cid: that.curCid,bid:that.opt.bid,pagenum: pagenum, time:tempdata.chooseTimeStr}, function (res) {
					that.loading = false;
					var data = res.data;
					if (pagenum == 1) {
						that.contentList = data;
						if (data.length == 0) {
							that.nodata = true;
						}
						that.loaded();
					}else{
						if (data.length == 0) {
							that.nomore = true;
						} else {
							var contentList = that.contentList;
							var newdata = contentList.concat(data);
							that.contentList = newdata;
						}
					}
				});
			},
		}
	}
</script>

<style>
.page-tab{display:flex;width:100%;overflow-x:scroll;border-bottom: 1px #f5f5f5 solid;padding:0 10rpx; background-color: #FFFFFF;}
.page-tab2{display:flex;width:auto;min-width:100%}
.page-tab2 .item{width:auto;padding:0 20rpx;font-size:28rpx;text-align: center; color:#333; height:90rpx; line-height:90rpx; overflow: hidden;position:relative;flex-shrink:0;flex-grow: 1;}
.page-tab2 .on{color:#FE5B07; font-size: 30rpx;}

.shop-box {padding: 0 30rpx 30rpx;}
.shop-box .shop-item {width: 100%;margin-top: 20rpx; display: flex;background: #fff;border-radius: 8rpx; padding: 20rpx; position: relative;}
.shop-box .shop-item .shop-img {width: 200rpx;height: 200rpx;border-radius: 8rpx; background-color: #eee;}
.shop-box .shop-item .f2 {margin-top: 12rpx;justify-content: space-between; padding: 10rpx;}
.shop-box .shop-item .shop-name {font-size: 32rpx;color: #333;}
.shop-box .shop-item .desc {color: #999;}
.shop-box .shop-item .desc text{margin-right: 16rpx;}
.shop-box .shop-item .f3 {width: 120rpx; align-items: center;position: absolute;top: 20rpx; right: 20rpx;}
.button{width: 100rpx;height:70rpx;line-height:70rpx;font-size:28rpx;color:#FFFFFF;  background: linear-gradient(90deg, #FF7D15 0%, #FC5729 100%);
border-radius: 10rpx; text-align: center;}

</style>
