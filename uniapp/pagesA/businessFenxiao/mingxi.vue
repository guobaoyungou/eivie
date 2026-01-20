<template>
	<view>
		<block v-if="isload">
		<view class="search-view flex-xy-center">
			<view class="input-view flex-aw">
				<view class="picker-class flex-x-center">
					<image :src="pre_url+'/static/img/timeicon.png'"></image>
					<picker mode="date" :value="start_time1" @change="bindStartTime1Change">
						<view class="picker">{{start_time1}}</view>
					</picker>
					<image :src="pre_url+'/static/img/jiantou.png'"></image>
				</view>
				<view>--</view>
				<view class="picker-class flex-x-center">
					<image :src="pre_url+'/static/img/timeicon.png'"></image>
					<picker mode="date" :value="start_time2" @change="bindStartTime2Change">
						<view class="picker">{{start_time2}}</view>
					</picker>
					<image :src="pre_url+'/static/img/jiantou.png'"></image>
				</view>
			</view>
		</view>
		<view class="content-view flex-col">
			<view class="options-view flex-bt" v-for="(item,index) in datalist" :key="index">
				<view class="info-view flex-col">
					<view class="title-text">门店营业额</view>
					<view class="inventory-text flex-bt">
						<view class="price">{{item.yeji_total}}</view>
						<view class="price">补贴：{{item.butie_yeji}}</view>
						<view class="time">{{item.jiesuan_time}}</view>
					</view>
				</view>
			</view>
			<nomore v-if="nomore"></nomore>
			<nodata v-if="nodata"></nodata>
		</view>
		</block>
	</view>
</template> 

<script>
	var app = getApp();
	export default{
		data(){
			return{
				pre_url:app.globalData.pre_url,
				start_time1:'选择日期',
				start_time2:'选择日期',
				opt:{},
				loading:false,
				isload: false,
				datalist: [],
				pagenum: 1,
				nomore: false,
				nodata:false,
				bid:0,
			}
		},
		onLoad: function (opt) {
			var that = this;
			var opt  = app.getopts(opt);
			if(opt && opt.bid){
				that.bid = opt.bid;
			}
			that.opt = opt;
			that.getdata();
		},
		onReachBottom: function () {
			if (!this.nodata && !this.nomore) {
				this.pagenum = this.pagenum + 1;
				this.getdata(true);
			}
		},
		methods:{
			getdata: function (loadmore) {
				var that = this;
				if(!loadmore){
					this.pagenum = 1;
					this.datalist = [];
				}
				var pagenum = that.pagenum;
				var st = that.st;
				that.loading = true;
				that.nodata = false;
				that.nomore = false;
				app.post('ApiBusinessFenxiao/mendianyeji', {pagenum: pagenum,bid:that.bid,s_time:that.start_time1,e_time:that.start_time2}, function (res) {
					that.loading = false;
					if(res.status == 1){
						var data = res.data;
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
						app.alert(res.msg);
					}
			
				});
			},
			bindStartTime1Change:function(e){
				this.start_time1 = e.target.value
				this.pagenum = 1;
				this.datalist = [];
				uni.pageScrollTo({
					scrollTop: 0,
					duration: 0
				});
				this.getdata();
			},
			bindStartTime2Change:function(e){
				this.start_time2 = e.target.value
				this.pagenum = 1;
				this.datalist = [];
				uni.pageScrollTo({
					scrollTop: 0,
					duration: 0
				});
				this.getdata();
			},
		}
	}
</script>

<style>
	.search-view{background: #fff;width: 100%;height: 140rpx;;position: fixed;top: 0;}
	.input-view{width: 90%;background: #F5F7F9;border-radius: 16rpx;height: 88rpx;align-items: center;}
	.input-view image{width: 35rpx;height: 35rpx;margin: 0 10rpx;}
	.input-view .picker-class{width: 43%;height: 100%;align-items: center;}
	.input-view .picker-class .picker{font-size: 24rpx;color: rgba(130, 130, 167, 0.8);white-space: nowrap;width: 150rpx;text-align: center;} 
	.content-view{width: 100%;height: auto;margin-top: 160rpx;}
	.options-view{background: #fff;width: 100%;margin-bottom:15rpx;padding: 23rpx 40rpx;align-items: center;display: flex;align-items: center;justify-content: flex-start;}

	.info-view{width: 100%;padding: 0rpx 0rpx;}
	.info-view .title-text{font-size: 30rpx;font-family:500;color: #3A4463;margin-bottom: 10rpx;width: 100%;white-space:nowrap;overflow: hidden;text-overflow: ellipsis;}
	.info-view .inventory-text{padding-top: 10rpx;}
	.info-view .inventory-text .time{color: rgba(58, 68, 99, 0.5);font-size: 24rpx;}
	.info-view .inventory-text .price{font-size: 28rpx;color: #333;font-weight: bold;}
</style>