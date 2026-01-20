<template>
	<view>
		<view class="page">
			<view class="body">
				<view class="body_progress">
					<view v-for="(item,index) in dataList" :key="index">
						<view v-if="index==0" :class="item.status==2?'progress_active':''">
							<view class="progress_tag"></view>
						</view>
						<view v-if="index!=0" :class="item.status==2?'progress_active':''">
							<view class="progress_line"></view>
							<view class="progress_tag"></view>
						</view>
					</view>
				</view>
				<view class="body_module">
					<view class="body_list" v-for="(item,index) in dataList" :key="index" :class="item.status==2?'list_active':item.status==3?'collect_active':'' ">
						<view class="body_data">
							<view class="body_title" @tap.stop="toDetail" :data-id="item.id">{{item.title}}<img :src="pre_url+'/static/img/week/week_detail.png'" class="body_icon" alt=""/></view>
							<view class="body_text" v-if="detail.freight_type==0 ||detail.freight_type==2 ">配送日: {{item.cycle_date}}</view>
							<view class="body_text" v-else-if="detail.freight_type==1  ">取货日: {{item.cycle_date}}</view>
						</view>
						<view class="body_state" >
						{{item.status==0?'待支付':item.status==1 && detail.freight_type==2?'待配送':item.status==1 && detail.freight_type==0?'待发货':item.status==2 && detail.freight_type==2?'配送中':item.status==2 && detail.freight_type==0?'已发货': item.status==1&& detail.freight_type==1?'待取货':'已完成'}}
							
						</view>
					</view>
				</view>
			</view>
		</view>
	</view>
</template>

<script>
	var app = getApp();
	export default {
		data() {
			return {
				pre_url: app.globalData.pre_url,
				dataList:[],
				detail:[]
			}
		},
		onLoad(opt) {
			this.opt = app.getopts(opt);
			this.getdata();
		},
		onShow(){
			this.getdata();
		},
		methods: {
			toDetail:function(e){
				var id = e.currentTarget.dataset.id
				app.goto('cycleplandetail?id=' + id );
			},
			getdata: function () {
				var that = this;
				app.showLoading();
				app.get('ApiAdminOrder/getCycleList', {id: that.opt.id}, function (res) {
					that.dataList = res.data;
					that.detail = res.detail
					app.showLoading(false);
					that.isload = true;
				});
			},
		}
	}
</script>
<style>
	page{
		background: #F6F6F6;
	}
</style>
<style scoped>
	.page{
		position: relative;
		padding: 15px;
	}
	.body{
		position: relative;
		width: 690rpx;
		padding: 20rpx 15px 20rpx 0;
		background: #FFFFFF;
		display: flex;
		box-sizing: border-box;
		border-radius: 10rpx;
		margin: 0 auto;
	}
	.body_progress{
		width: 120rpx;
		height: 100%;
	}
	.progress_tag{
		width: 15px;
		height: 15px;
		border: 2px solid #D0D0D0;
		border-radius: 50%;
		margin: 2.5px auto 0 auto;
	}
	.progress_tag:first-child{
		margin: 25px auto 0 auto;
	}
	.progress_line{
		width: 1px;
		height: 55px;
		background: #d0d0d0;
		margin: 2.5px auto 0 auto;
	}
	.progress_active .progress_tag{
		border: 4rpx solid #6FD16B;
	}
	.progress_active .progress_line{
		background: #6FD16B;
	}
	
	
	
	.body_module{
		flex: 1;
	}
	.body_list{
		position: relative;
		margin-top: 10px;
		padding: 12.5px 0;
		display: flex;
		align-items: center;
		border-bottom: 1px solid #F6F6F6;
	}
	.body_list:first-child{
		margin-top: 0;
	}
	.body_list:last-child{
		border-bottom: 0;
	}
	.body_data{
		flex: 1;
	}
	.body_title{
		font-size: 15px;
		line-height: 15px;
		font-family: PingFang SC;
		color: #333;
		display: flex;
		align-items: center;
	}
	.body_icon{
		height: 14px;
		width: 14px;
		margin-left: 10rpx;
	}
	.body_text{
		font-size: 15px;
		line-height: 15px;
		font-family: PingFang SC;
		font-weight: 500;
		color: #333;
		margin-top: 9px;
	}
	.body_state{
		font-size: 15px;
		line-height: 15px;
		font-family: PingFang SC;
		color: #333;
	}
	
	.list_active .body_title{
		color: #323232;
	}
	.list_active .body_state{
		color: #6FD16B;
	}
	.collect_active .body_state{
		color: #f44336;
	}
</style>
