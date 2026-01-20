<template>
<view>
	<block  v-if="isload">
		<view class="tab-view flex flex-bt">
			<view @click='tabChange(0)' :class="[tabIndex == 0 ? 'options-tab-active':'','options-tab']">我收到的</view>
			<view @click='tabChange(1)' :class="[tabIndex == 1 ? 'options-tab-active':'','options-tab']">我发出的</view>
		</view>
		<view class="statistics-view flex-col">
			<view class="tj-title flex-y-center">{{tabIndex == 0 ? '收到':'发出'}}<text style="color: #f74a32;padding: 0rpx 5rpx;">{{count}}</text>个红包，共</view>
			<view class="tj-price flex-y-bottom">{{totalmoney}}<text style="font-size: 28rpx;color: #666666;padding: 0rpx 10rpx;margin-bottom: 15rpx;">元</text></view>
		</view>
		<!-- 记录 -->
		<view class="flex-col">
			
			<block v-for="(item,index) in datalist" :key="index" v-if="tabIndex ==0">
				<view class="options-log flex-y-center flex-bt">
					<view class="left-view flex flex-y-center">
						<!-- 两个类型-对应两种图标 -->
						<image class="left-icon" style="border-radius: 50%;" :src="item.headimg" /><!-- <image :src="pre_url+'/static/img/redenvelope/red_pu.png'" /> -->
						<view class="info-view flex-col">
							<view class="info-title">{{item.type_text}}</view>
							<view class="info-content">来自<text style="color: #333;margin-right: 10rpx;">{{item.nickname}}</text> {{item.showtime}}</view>
						</view>
					</view>
					<view class="right-view flex-col">
						<view class="price-view">{{item.money}} 元</view>
						<view class="price-status">已领取</view>
					</view>
				</view>
			</block>
			<block v-for="(item,index) in datalist" :key="index" v-if="tabIndex==1">
				<view class="options-log flex-y-center flex-bt" @tap="goto" :data-url="'hongbaoshare?id='+item.id">
					<view class="left-view flex flex-y-center">
						<!-- 两个类型-对应两种图标 -->
						<image class="left-icon" :src="pre_url+'/static/img/redenvelope/red_pin.png'" /><!-- <image :src="pre_url+'/static/img/redenvelope/red_pu.png'" /> -->
						<view class="info-view flex-col">
							<view class="info-title">{{item.type_text}}</view>
							<view class="info-content">包 <text style="color: #333;margin-right: 10rpx;">{{item.num}}个红包</text> {{item.showtime}}</view>
							<view class="info-content">{{item.expire_time}} <text style="color: #333;margin-right: 10rpx;">过期</text></view>
						</view>
					</view>
					<view class="right-view flex-col">
						<view class="price-view">{{item.money}} 元</view>
						<view class="price-status">
							<text v-if="item.status ==0">进行中</text>
							<text v-if="item.status ==1">已完成</text>
							<text v-if="item.status ==2">已撤回</text>
						</view>
						<view class="price-status" style="color: #f74a32; !important" @tap.stop="cancelHongbao" :data-id="item.id" v-if="item.status ==0">撤回</view>
					</view>
				</view>
			</block>
		</view>
	</block>
	<nodata v-if="nodata"></nodata>
	<nomore v-if="nomore"></nomore>
	<loading v-if="loading"></loading>
</view>
</template>

<script>
	var app = getApp();
	export default {
		data(){
			return{
				 isload: false,
				loading:false,
				nodata:false,
				nomore:false,
				pre_url: app.globalData.pre_url,
				tabIndex:0,
				datalist:[],
				pagenum: 1,
				totalmoney:0,
				count:0
			}
		},
		onLoad: function (opt) {
			this.opt = app.getopts(opt);
			if(this.opt.type){
				this.tabIndex = this.opt.type;
			}
		  this.getdata();
		},
		onPullDownRefresh: function () {
			this.getdata();
		},
		onReachBottom: function () {
		  if (!this.nomore && !this.nodata) {
		    this.pagenum = this.pagenum + 1;
		    this.getdata(true);
		  }
		},
		methods:{
			getdata: function (loadmore) {
				if(!loadmore){
					this.pagenum = 1;
					this.datalist = [];
				}
			  var that = this;
			  var pagenum = that.pagenum;
				that.loading = true;
				that.nodata = false;
				that.nomore = false;
				app.post('ApiMoneySendHongbao/hongbaoList', {type:that.tabIndex,pagenum:pagenum}, function (res) {
					that.loading = false;
					var data = res.data;
					that.totalmoney = res.totalmoney;
					that.count = res.count;
					if (pagenum == 1) {
						uni.setNavigationBarTitle({
							title: '发红包'
						});
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
			tabChange(index){
				this.tabIndex = index;
				this.totalmoney = 0;
				this.count = 0;
				this.getdata();
			},
			cancelHongbao(e){
				var that = this;
				var id =  e.currentTarget.dataset.id;
				app.confirm('确定要撤回该红包吗?', function () {
					that.loading = true;
					app.post('ApiMoneySendHongbao/cancelHongbao', {id:id}, function (res) {
						if(res.status ==0){
							app.error(res.msg);
							return;
						}
						app.success(res.msg);
						setTimeout(function(){
							that.getdata();
						})
					});
				});
			}
		}
	}
</script>

<style>
	page{background: #fdf8f3;}
	.tab-view{width: 100%;background: #fff;border-bottom: 1rpx #e3e3e3 solid;}
	.options-tab{font-size: 30rpx;width: 50%;text-align: center;color: #666666;padding: 20rpx 0rpx;}
	.options-tab-active{color: #f74a32;position: relative;}
	.options-tab-active::after{content: " ";display: block;width: 100rpx;height: 2px;background: #f74a32;position: absolute;left: 50%;bottom: 0;transform: translateX(-50%);
	border-radius: 6rpx;}
	/*  */
	.statistics-view{justify-content: center;align-items: center;padding: 50rpx 0rpx;}
	.statistics-view .tj-title{font-size: 24rpx;color: #999999;padding: 10rpx 0rpx;}
	.statistics-view .tj-price{font-size: 80rpx;color: #f74a32;font-weight: bold;letter-spacing: 4rpx;}
	.options-log{width: 100%;background: #fff;padding: 20rpx;margin-bottom: 1px;}
	.left-view{}
	.left-view .left-icon{width: 70rpx;height: 70rpx;}
	.left-view .info-view{margin-left: 20rpx;}
	.left-view .info-view .info-title{font-size: 28rpx;color: #000000;}
	.left-view .info-view .info-content{font-size: 26rpx;color: #909090;margin-top: 10rpx;}
	.right-view{align-items: flex-end;}
	.right-view .price-view{font-size: 28rpx;color: #000000;}
	.right-view .price-status{font-size: 24rpx;color: #909090;margin-top: 10rpx;}
</style>