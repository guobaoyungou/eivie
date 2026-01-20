<template>
	<view>
		<block v-if="isload && is_show">
<!-- 		<view class="search-view flex flex-bt flex-y-center">
			<view class="popup-search flex">
				<image :src="`${pre_url}/static/img/search_ico.png`"></image>
				<input placeholder="请输入车牌号" placeholder-style="font-size: 28rpx;color: #B1B4C3;" />
			</view>
		</view> -->
		<!-- <view style="height: 30rpx;background: #f6f6f6;"></view> -->
		<view class="content-view">
			<scroll-view scroll-y>
				<block v-for="(item,index) in lists">
					<view class="options-view flex flex-y-center flex-bt">
						<view class="user-info flex">
							<image :src="`${pre_url}/static/img/vehicle/chelianglist.png`"></image>
							<view class="user-info-right flex flex-col">
								<view class="name-text">{{item.car_num}}</view>
								<view class="release-time">录入时间：{{dateFormat(item.createtime)}}</view>
							</view>
						</view>
		<!-- 				<view class="delete-but" @click="delCar(item.id)">
							<image :src="`${pre_url}/static/img/del.png`"></image>
						</view> -->
						<view class="edit-but" @click="editCar(item.id)">
							<image :src="`${pre_url}/static/img/editicon.png`"></image>
						</view>
						<view class="edit-time-but" @click="editTime(item.id)">
							<image :src="`${pre_url}/static/img/vehicle/settime.png`"></image>
						</view>
					</view>
				</block>
			</scroll-view>
		</view>
		<view style="width: 100%;height: calc(230rpx + env(safe-area-inset-bottom));"></view>
		<view class="pageBottom-class" :class="menuindex>-1?'tabbarbot':'notabbarbot2'">
			<button class="pageBottom-but" @click="addCar" :style="{background:t('color1')}">添加</button>
		</view>
		</block>
		<nomore v-if="nomore"></nomore>
		<nodata v-if="nodata"></nodata>
		<dp-tabbar :opt="opt" @getmenuindex="getmenuindex"></dp-tabbar>
	</view>
</template>

<script>
	var app = getApp();
	export default{
		data(){
			return{
				isload: false,
				pre_url: app.globalData.pre_url,
				opt:{},
				lists:[],
				nodata:false,
				nomore:false,
				menuindex:-1,
				is_show:false
			}
		},
		onLoad: function (opt) {
			this.opt = app.getopts(opt);
			this.getdata();
		},
		methods:{
			getdata:function(){
				var that = this;
				that.loading = true;
				that.nodata = false;
				app.get('ApiCarManagement/lists', {}, function (res) {
					that.loading = false;
					var lists = res.data || {};
					that.is_show = res.is_show;
					if(that.is_show){
						uni.setNavigationBarTitle({
							title: '车辆列表'
						});
					}else{
						app.alert('功能未开启')
					}
					that.lists = lists;
					if (that.lists.length == 0) {
					  that.nodata = true;
					}
					that.loaded();
				});
			},
			addCar:function(){
				app.goto('registerVehicle');
			},
			editCar:function(id){
				app.goto('registerVehicle?id='+id);
			},
			editTime:function(id){
				app.goto('vehicleSettime?id='+id);
			},
		}
	}
</script>

<style>
	page{background: #fff;}
	.search-view{width: 100%;background:#fff;position: fixed;top: 0;padding: 30rpx 30rpx 20rpx 30rpx;z-index: 9;}
	.search-view .popup-search{align-items: center;justify-content: flex-start;background: #F6F8FA;border-radius: 4px;padding: 16rpx;width: 100%;}
	.search-view .popup-search image{width: 35rpx;height: 35rpx;}
	.search-view .popup-search input{margin-left: 10rpx;}
	.content-view{padding: 0rpx 30rpx;width: 100%;height: 100%;background-color: #fff;}
	.content-view .options-view{padding: 30rpx 0rpx;position: relative;border-bottom: 1px #f5f5f5 solid;}
	.content-view .options-view .user-info{align-items: center;}
	.content-view .options-view .user-info image{width: 88rpx;height: 88rpx;border-radius: 50%;}
	.content-view .options-view .user-info .user-info-right{width: auto;padding-left: 15rpx;}
	.content-view .options-view .user-info .user-info-right .name-text{color: #1D2129;font-size: 30rpx;font-weight: bold;white-space: nowrap;}
	.content-view .options-view .user-info .user-info-right .release-time{color: rgba(0, 0, 0, 0.4);font-size: 22rpx;margin-top: 15rpx;}
	.content-view .options-view .fun-but{border-radius: 35px;color: #FFFFFF;font-size: 22rpx;color: #fff;text-align: center;padding: 10rpx 0rpx;
	position: absolute;right: 0;width: 100rpx;}
	.content-view .options-view .delete-but{position: absolute;right: 0;top: 60rpx;}
	.content-view .options-view .delete-but image{width: 38rpx;height: 38rpx;}
	.content-view .options-view .edit-but{position: absolute;right: 50rpx;top: 60rpx;}
	.content-view .options-view .edit-but image{width: 38rpx;height: 38rpx;}
	.content-view .options-view .edit-time-but{position: absolute;right: 0rpx;top: 60rpx;}
	.content-view .options-view .edit-time-but image{width: 38rpx;height: 38rpx;}
	/*  */
	.pageBottom-class{background: #fff;width: 100%;position: fixed;bottom:env(safe-area-inset-bottom);left: 50%;transform: translateX(-50%);padding-top: 20rpx;}
	.pageBottom-but{width: 94%;border-radius: 6px;background: #9c9c9c;font-size:32rpx;font-weight: 500;color: #FFFFFF;text-align: center;margin: 0 auto;}
	.not-pageBottom-but{background: rgba(61, 91, 246, 0.2) !important;}
	.page-bottom-text{text-align: center;font-size: 24rpx;color: #a09fa6;margin: 50rpx auto 30rpx;}
</style>