<template>
<view>
	<block v-if="isload">
		<view class="banner" :style="{background:'linear-gradient(180deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0) 100%)'}">
		</view>
		
		<view class="contentdata">
			<view class="form-box">
				<view class="form-item flex">
					<view class="f2" style="line-height:30px">
						 <picker mode="date" :value="start_time1" @change="bindStartTime1Change">
							 <view class="picker">{{start_time1}}</view>
						 </picker>
						 <view style="padding:0 10rpx;color:#333;">到</view>
						 <picker mode="date" :value="end_time1" @change="bindEndTime1Change">
							 <view class="picker">{{end_time1}}</view>
						 </picker>
					</view>
					<view class="data_btn2" @tap="search" :style="'background:'+t('color1')+';border:0'">查询</view>
				</view>
			</view>
			
			<view class="data">
				<view class="data_module flex">
					<view class="flex1">
						<view class="data_lable">查询业绩(元)</view>
						<view class="data_value">{{yeji}}</view>
					</view>
				</view>
			</view>
			
		</view>
		<view style="width:100%;height:20rpx"></view>
		
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
			
     
     start_time1:'-开始日期-',
     end_time1:'-结束日期-',
		 yeji:0
    };
  },

  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		var that = this;
		this.getdata();
  },
	onPullDownRefresh: function () {
		this.getdata();
	},
  methods: {
		bindStartTime1Change:function(e){
			this.start_time1 = e.target.value
		},
		bindEndTime1Change:function(e){
			this.end_time1 = e.target.value
		},
		getdata: function () {
			var that = this;
				uni.setNavigationBarTitle({
					title: '团队业绩查询'
				});
			that.loaded();
		},
    search: function () {
      this.hiddenmodalput = true;
			var that = this;
			if(that.start_time1 == '-开始日期-'){
				app.error('请选择日期');return;
			}
			if(that.end_time1 == '-结束日期-'){
				app.error('请选择日期');return;
			}
			that.loading = true;
			app.get('ApiAgent/teamyeji', {start_time:that.start_time1,end_time:that.end_time1}, function (res) {
				that.loading = false;
				if(res.msg && !res.status){
					app.error(res.msg);return;
				}
				that.yeji = res.data.yeji;
			});
    },
  }
};
</script>
<style>
.contentdata{display:flex;flex-direction:column;width:100%;padding:40rpx 30rpx 0;position:relative;margin-bottom:20rpx;background:#fff;}

.data{padding:30rpx;margin-top:30rpx;border-radius:16rpx}
.data_title{font-size: 28rpx;color: #333;font-weight: bold;}
.data_icon{height: 35rpx;width: 35rpx;margin-right: 15rpx;}
.data_text{font-size: 26;color: #999;margin-top: 60rpx;}
.data_price{font-size: 64rpx;color: #333;font-weight: bold;margin-top: 10rpx;}
.data_btn{height: 56rpx;padding: 0 30rpx;font-size: 24rpx;color: #fff;font-weight: normal;border-radius: 100rpx;}
.data_btn image{height: 24rpx;width: 24rpx;margin-left: 6rpx;}
.data_module{margin-top: 60rpx;}
.data_lable{font-size: 26;color: #999;}
.data_value{font-size: 44rpx;font-weight: bold;color: #333;margin-top: 10rpx;}

.form-box{ padding:2rpx 24rpx 0 24rpx; background: #fff;margin: 24rpx;border-radius: 10rpx}
.form-item{ line-height: 100rpx; display: flex;justify-content: space-between;border-bottom:1px solid #eee }
.form-item .f1{color:#222;width:200rpx;flex-shrink:0}
.form-item .f2{display:flex;align-items:center}
.form-box .form-item:last-child{ border:none}
.form-box .flex-col{padding-bottom:20rpx;display: flex;}
.picker { background-color: #f7f7f7; padding: 0 30rpx; border-radius: 10rpx;}
.data_btn2 { line-height: 56rpx;padding: 0 30rpx;font-size: 24rpx;color: #fff;font-weight: normal;border-radius: 100rpx;}
</style>