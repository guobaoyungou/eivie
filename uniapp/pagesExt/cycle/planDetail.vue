<template>
	<view class="page" v-if="isload">
		<view class="module">
			<view class="module_title">
				<view>第{{detail.cycle_number}}期 <text class="module_num">{{detail.cycle_number}}/{{detail.order.qsnum}}</text></view>
				
				<view v-if="(order.freight_type == 0 ||order.freight_type == 2 ) && detail.status == 2" class="btn2" @tap="orderCollect"  :data-id="detail.id">确认收货</view>
				
				<text class="module_state" v-if=" detail.status == 1 && order.freight_type == 2">待配送</text>
				<text class="module_state" v-if=" detail.status == 1 && order.freight_type == 0">待发货</text>
				
				<text class="module_state" v-if="order.freight_type == 2 && detail.status == 2">配送中</text>
				<text class="module_state" v-if="order.freight_type == 1 && detail.status == 1">待取货</text>
				<text class="module_state" v-if="order.freight_type == 0 && detail.status == 2">已发货</text>
				<text class="module_state" v-if=" detail.status == 3">已完成</text>
				
			</view>
			
			
			<view v-if="order.freight_type == 2 || order.freight_type == 0">
				
				<view class="module_list">
					<view class="module_lable">收货人</view>
					<view class="module_text">
						{{order.linkman}}
					</view>
				</view>
				
				<view class="module_list">
					<view class="module_lable">
						<text v-if="order.freight_type == 1">取货</text>
						<text v-else>收货</text>  
						日期
					</view>
					<view class="module_text">
						{{detail.cycle_date}} / {{detail.week}}
					</view>
					<!-- <view  class="module_update">
						<img  :src="pre_url+'/static/img/week/week_write.png'" alt=""/>修改
					</view> -->
				</view>
				<view class="module_list">
					<view class="module_lable">收货地址</view>
					<view class="module_text">
						{{order.area}}{{order.address}}
					</view>
				</view>
				<view class="module_list" v-if="order.freight_type == 0 && detail.status > 1">
					<view class="module_lable">物流信息</view>
					<view class="module_text">
						<view class="wl_btn" @tap.stop="logistics" :data-express_com="detail.express_com" :data-express_no="detail.express_no" >
							查看物流
						</view>
					</view>
				</view>
				<view class="module_list" v-if="order.freight_type == 2 && (detail.status == 2 || detail.status == 3)">
					<view class="module_lable">配送信息</view>
					<view class="module_text">
						<view class="wl_btn" @tap.stop="logistics" :data-express_com="detail.express_com" :data-express_no="detail.express_no" >
							查看配送信息
						</view>
					</view>
				</view>
			</view>
			<view v-else-if="order.freight_type == 1">
				<view class="module_list">
					<view class="module_lable">取货人</view>
					<view class="module_text">
						{{order.linkman}}
					</view>
				</view>
				
				<view class="module_list">
					<view class="module_lable">
						<text v-if="order.freight_type == 1">取货</text>
						<text v-else>收货</text>  
						日期
					</view>
					<view class="module_text">
						{{detail.cycle_date}} / {{detail.week}}
					</view>
					<!-- <view  class="module_update">
						<img  :src="pre_url+'/static/img/week/week_write.png'" alt=""/>修改
					</view> -->
				</view>
				<view class="module_list">
					<view class="module_lable">取货地址</view>
					<view class="module_text">
						{{storeinfo.name}} - {{storeinfo.address}}
						
					</view>
				</view>
				<view class="module_list" v-if="order.freight_type == 1 && detail.status == 1">
					<view class="module_lable">核销码</view>
					<view class="module_text">
						<view class="wl_btn" @click="showhxqr" >
							查看核销码
						</view>
					</view>
				</view>
			</view>
			
		</view>
		
		<view class="opt">
			<view class="opt_btn" @click="alertClick" v-if="detail.is_advance == 1 && detail.status==1">
				顺延 <text v-if="order.freight_type == 1">取货</text> <text v-else-if="order.freight_type == 2 || order.freight_type == 0">发货</text>
			</view>
		</view>
		
		<view class="alert" v-if="alertStatus">
			<view @click="alertClick()" class="alert_none"></view>
			<view class="alert_module">
				<view class="alert_title">
					修改顺延日期
				</view>
				<view class="alert_item" v-for="(item,index) in optionList" :key="index" :class="listIndex==index?'alert_active':''" @click="itemClick(index)">
					<view class="alert_tag"></view>
					<view class="alert_data">
						<view class="alert_name">
							{{item.title}}
						</view>
						<view class="alert_text">
							{{item.text}}
						</view>
					</view>
					<img v-if="listIndex==index"  :src="pre_url+'/static/img/week/week_true.png'" class="alert_icon" alt=""/>
				</view>
				<view class="alert_opt">
					<view @click="alertClick()" class="alert_btn">
						取消
					</view>
					<view class="alert_btn" @click="advanceDays">
						确定
					</view>
				</view>
			</view>
		</view>
		<uni-popup id="dialogHxqr" ref="dialogHxqr" type="dialog">
			<view class="hxqrbox">
				<image :src="detail.hexiao_qr" @tap="previewImage" :data-url="detail.hexiao_qr" class="img"/>
				<view class="txt">请出示核销码给核销员进行核销</view>
				<view class="close" @tap="closeHxqr">
					<image :src="pre_url+'/static/img/close2.png'" style="width:100%;height:100%"/>
				</view>
			</view>
		</uni-popup>
		
	</view>
</template>

<script>
	var app = getApp();
	export default {
		data() {
			return {
				isload:false,
				detail:{},
				order:{},
				storeinfo:{},
				pre_url: app.globalData.pre_url,
				advance_days:'',
				optionList:[
					{
						title:"顺延一日",
						text:"默认收获日顺延一日",
						value:1
					},
					{
						title:"顺延二日",
						text:"默认收获日顺延二日",
						value:2
					},
					{
						title:"顺延三日",
						text:"默认收获日顺延三日",
						value:3
					}
				],
				listIndex:null,
				alertStatus:false
			}
		},
		onLoad(opt) {
			this.opt = app.getopts(opt);
			this.getdata();
		},
		methods: {
			//查看核销码
			showhxqr:function(){
				this.$refs.dialogHxqr.open();
			},
			closeHxqr:function(){
				this.$refs.dialogHxqr.close();
			},
			orderCollect:function(e){
				var that = this;
			
				app.confirm('确定已收到货吗?', function () {
								app.showLoading();
				  app.post('ApiCycle/orderCollect', {id: that.opt.id}, function (data) {
					app.showLoading(false);
				    app.success(data.msg);
				    setTimeout(function () {
				      that.getdata();
				    }, 1000);
				  });
				});
			},
			logistics:function(e){
				var express_com = e.currentTarget.dataset.express_com
				var express_no = e.currentTarget.dataset.express_no
				app.goto('/pagesExt/cycle/logistics?express_com=' + express_com + '&express_no=' + express_no);
			},
			getdata: function () {
				var that = this;
				app.showLoading();
				app.get('ApiCycle/getCycleDetail', {id: that.opt.id}, function (res) {
					that.detail = res.data;
					that.order = res.data.order;
					that.storeinfo = res.data.storeinfo;
					app.showLoading(false);
					that.isload = true;
				});
			},
			alertClick(){
				if(this.alertStatus){
					this.alertStatus = false
				}else{
					this.alertStatus = true
				}
			},
			itemClick(e){
				this.listIndex=e;
				this.advance_days = this.optionList[this.listIndex].value;
			},
			advanceDays(){
				var that = this;
				app.showLoading();
				app.post('ApiCycle/advanceDays', {id: that.opt.id,days:this.advance_days}, function (res) {
					
					app.success(res.msg);
					setTimeout(function () {
					  that.alertStatus = false
					  that.getdata();
					}, 1000);

					app.showLoading(false);
				});
			}
		}
	}
</script>
<style>
	page {
		background: #F6F6F6;
	}
</style>
<style scoped>
	.page {
		padding: 30rpx;
	}

	.module {
		background: #FFFFFF;
		padding: 0 30rpx 50rpx 30rpx;
		border-radius: 10rpx;
		margin: 0 auto;
		width: 690rpx;
		box-sizing: border-box;
	}

	.module_title {
		padding: 30rpx;
		border-bottom: 1px solid #f6f6f6;
		font-size: 30rpx;
		color: #333;
		display: flex;
		align-items: center;
		justify-content: space-between;
	}

	.module_num {
		font-size: 28rpx;
		margin-left: 30rpx;
	}

	.module_state {
		font-size: 24rpx;
		font-family: PingFang SC;
		font-weight: bold;
		color: #6FD16B;
	}

	.module_list {
		margin-top: 30rpx;
		font-size: 26rpx;
		display: flex;
		align-items: center;
	}

	.module_lable {
		width: 120rpx;
		text-align: right;
		color: #999;
	}

	.module_text {
		margin-left: 30rpx;
		color: #333;
		flex: 1;
	}
	
	.module_update{
		color: #FC4343;
	}
	.module_update img{
		height: 24rpx;
		width: 24rpx;
		margin-right: 10rpx;
	}

	.opt {
		padding: 20rpx 30rpx;
	}
	.wl_btn {
		width: 240rpx;
		height: 70rpx;
		background: #FD4A46;
		border-radius: 10rpx;
		font-size: 28rpx;
		display: flex;
		align-items: center;
		justify-content: center;
		font-family: PingFang SC;
		font-weight: bold;
		color: #FFFFFF;
		box-sizing: border-box;
		
	}


	.opt_btn {
		height: 88rpx;
		background: #FD4A46;
		border-radius: 10rpx;
		font-size: 28rpx;
		display: flex;
		align-items: center;
		justify-content: center;
		font-family: PingFang SC;
		font-weight: bold;
		color: #FFFFFF;
		box-sizing: border-box;
		margin-top: 30rpx;
	}
	
	.alert{
		position: fixed;
		height: 100%;
		width: 100%;
		top: 0;
		left: 0;
		display: flex;
		align-items: center;
		justify-content: center;
		background: rgba(0, 0, 0, 0.6);
	}
	.alert_none{
		position: absolute;
		height: 100%;
		width: 100%;
	}
	.alert_module{
		position: relative;
		width: 560rpx;
		padding: 30rpx;
		box-sizing: border-box;
		background: #FFFFFF;
		border-radius: 24rpx;
	}
	.alert_title{
		font-size: 32rpx;
		font-family: PingFang SC;
		font-weight: bold;
		color: #323232;
		text-align: center;
		padding: 0 0 20rpx 0;
	}
	.alert_close{
		position: absolute;
		right: 35rpx;
		top: 35rpx;
		height: 28rpx;
		width: 28rpx;
	}
	.alert_item{
		height: 120rpx;
		background: #F9F6F6;
		border-radius: 8rpx;
		margin-top: 20rpx;
		padding: 0 30rpx;
		display: flex;
		align-items: center;
	}
	.alert_tag{
		width: 8rpx;
		height: 8rpx;
		background: #323232;
		border-radius: 50%;
	}
	.alert_data{
		flex: 1;
		margin-left: 25rpx;
	}
	.alert_name{
		font-size: 26rpx;
		font-family: PingFang SC;
		font-weight: bold;
		color: #323232;
	}
	.alert_text{
		font-size: 22rpx;
		font-family: PingFang SC;
		font-weight: 500;
		color: #999999;
		margin-top: 15rpx;
	}
	.alert_icon{
		height: 36rpx;
		width: 36rpx;
	}
	.alert_active{
		background: #ffe8e8;
	}
	.alert_active .alert_tag{
		background: #FC4343;
	}
	.alert_active .alert_name{
		color: #FC4343;
	}
	.alert_active .alert_text{
		color: #FC4343;
		opacity: 0.7;
	}
	.alert_opt{
		margin-top: 60rpx;
		display: flex;
	}
	.alert_btn{
		flex: 1;
		height: 88rpx;
		background: #ffe8e8;
		font-weight: bold;
		color: #FC4343;
		display: flex;
		align-items: center;
		justify-content: center;
		font-size: 26rpx;
		border-radius: 8rpx;
	}
	.alert_btn:last-child{
		flex: 2;
		margin-left: 20rpx;
		height: 88rpx;
		background: #FC4343;
		font-weight: bold;
		color: #fff;
		font-size: 26rpx;
		border-radius: 8rpx;
	}
	.btn2{ border-radius: 10rpx;margin-left:20rpx;margin-top: 10rpx;width:160rpx;height:60rpx;line-height:60rpx;color:#fff;background:#FC4343;border-radius:3px;text-align:center}
	.hxqrbox{background:#fff;padding:50rpx;position:relative;border-radius:20rpx}
	.hxqrbox .img{width:400rpx;height:400rpx}
	.hxqrbox .txt{color:#666;margin-top:20rpx;font-size:26rpx;text-align:center}
	.hxqrbox .close{width:50rpx;height:50rpx;position:absolute;bottom:-100rpx;left:50%;margin-left:-25rpx;border:1px solid rgba(255,255,255,0.5);border-radius:50%;padding:8rpx}
	
</style>
