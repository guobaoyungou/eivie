<template>
	<view class="container">
		<!-- 选择钓点 -->
		<view class="basan">
			<view class="seat_params">
				<view class="seat" >
					<image :src="pre_url+'/static/img/fishpond/seat_kexuan.png'" class="seat-img">空闲</image>
				</view>
				<view class="seat">
					<image :src="pre_url+'/static/img/fishpond/seat_yishou.png'" class="seat-img">使用中</image>
				</view>
				<view class="seat">
					<image :src="pre_url+'/static/img/fishpond/seat_yixuan.png'" class="seat-img">已选择</image>
				</view>
				<view class="seat">
					<image :src="pre_url+'/static/img/fishpond/seat_suozuo.png'" class="seat-img">锁座</image>
				</view>
			</view>
			<view class="tips-text flex-x-center">
				<text>请在下方选择位置</text>
			</view>
			<view class="basan-seat flex-wp">
				<view class="seat-box" v-for="(item, index) in basan" :key="index" :data-id="item.id" :data-index="index" @tap="selectBasan">
					<image :src="pre_url+'/static/img/fishpond/seat_yishou.png'" mode="" v-if="item.status == 2"></image>
					<image :src="pre_url+'/static/img/fishpond/seat_yixuan.png'" mode="" v-else-if="item.status == 1"></image>
					<image :src="pre_url+'/static/img/fishpond/seat_suozuo.png'" mode="" v-else-if="item.status == 3"></image>
					<image :src="pre_url+'/static/img/fishpond/seat_kexuan.png'" mode="" v-else></image>
					<text class="basan-name">{{item.name}}</text>
				</view>
			</view>
			<view class="op">
				<block >
					<button class="tobuy" :style="{background:t('color2')}" @tap="subSave" data-type="1">确定</button>
				</block>
			</view>
		</view>
		<!-- 选择钓点 END -->
	</view>
</template>

<script>
	var app = getApp();
	export default {
		data() {
			return {
				pre_url: app.globalData.pre_url,
				basan:[],
				selectedbasan:[],
				basan_sum:0,
				selectNum:0, //选中数量
				orderid:''
			}
		},
		onLoad: function (opt) {
			this.opt = app.getopts(opt);
			this.getdata();
		},
		methods: {
			getdata: function (option) {
				var that = this;
				that.loading = true;
				app.get('ApiFishPond/saveBasan', {orderid: that.opt.orderid}, function (res) {
					that.loading = false;
					if(res.status == 1){
						that.basan = res.data;
						that.basan_sum = res.order.basan_sum;
						that.orderid = res.order.orderid;
						that.selectedbasan = res.order.basanid;
						that.selectNum = res.order.basanid.length;
						that.loaded();
					}else{
						if (res.msg) {
						  app.alert(res.msg, function() {
						    if (res.url) app.goto(res.url);
						  });
						} else if (res.url) {
						  app.goto(res.url);
						} else {
						  app.alert('您无查看权限');
						}
					}
				});
			},
			selectBasan:function(e){
				var that = this;
				let index = e.currentTarget.dataset.index;
				let id = e.currentTarget.dataset.id;
				let basan = that.basan[index];
				
				var selectNum = 0;
				//已锁定
				if(basan.status == 3 || basan.status == 2){
					app.error('此钓位不可选');
					return;
				}
			
				if(basan.status == 1){
					basan.status = 0; // 取消选中
					// 从selectedbasan数组中移除对应的id
					let indexToRemove = that.selectedbasan.indexOf(id);
					if (indexToRemove !== -1) {
						if(that.selectNum == 0){
							that.selectNum = 0;
						}else{
							that.selectNum--;
						}
						that.selectedbasan.splice(indexToRemove, 1);
					}
				} else {
					if (that.selectNum >= that.basan_sum) {
						return app.error('只能选择'+that.basan_sum+'个位置');
					}
					that.selectNum++;
					basan.status = 1; // 标记为选中
					that.selectedbasan.push(id); // 将id添加到selectedbasan数组
				}
				console.log(that.selectedbasan);
			},
			subSave: function (e) {
			  var that = this;
				var selectedbasan = this.selectedbasan;
				if(app.isNull(selectedbasan)){
					return app.error('请选择新的位置');
				}
				var basanIds = selectedbasan.join(',');
				if(that.selectNum != that.basan_sum){
					return app.error('请选择位置');
				}
				app.showLoading('正在锁定位置');
				app.post('ApiFishPond/saveBasan', {
				  orderid: that.opt.orderid,
				  basanids: basanIds,
				}, function (data) {
					app.showLoading(false);
				  if (data.status == 0) {
						app.error(data.msg);
				    return;
				  }
					that.subscribeMessage(function () {
						setTimeout(function () {
							app.success(data.msg);
						}, 100);
					});
				});
			},
		}
	}
</script>

<style>
.basan{background:#fff;margin-top:20rpx}
	.seat_params{display:flex;align-items:center;justify-content:center;padding:20rpx 0}
	.seat_params > .seat{display:flex;align-items:center;justify-content:center;font-weight:400;font-size:27rpx;color:#222222}
	.seat_params > .seat:not(:last-child){margin-right:47rpx}
	.tips-text{background:#e4e2e2;margin:20rpx 0;padding:10rpx 0;color:#a5a0a0}
	.seat_params > .seat > .seat-img{width:36rpx;height:36rpx;display:block;margin-right:16rpx}
	.basan-seat{margin-top:12rpx;height:auto;overflow:hidden;padding:20rpx}
	.basan-seat .seat-box{width:16.66%;padding:20rpx 5rpx;box-sizing:border-box;display: flex;flex-direction: column;align-items: center;}
	.basan-seat .seat-box image{width:80rpx;height:80rpx}
	.basan-seat .seat-box .basan-name{font-size: 24rpx;color: #696868;margin-top: 10rpx;}
	.op{width:90%;margin:20rpx 5%;border-radius:36rpx;overflow:hidden;display:flex;margin-top:100rpx;}
	.addcart{flex:1;height:72rpx; line-height: 72rpx;color: #fff; border-radius: 0px; border: none; font-size:28rpx;font-weight:bold}
	.tobuy{flex:1;height: 72rpx; line-height: 72rpx;color: #fff; border-radius: 0px; border: none;}
	.nostock{flex:1;height: 72rpx; line-height: 72rpx; background:#aaa; color: #fff; border-radius: 0px; border: none;}
</style>
