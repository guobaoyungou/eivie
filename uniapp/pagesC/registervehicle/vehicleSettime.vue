<template>
	<view>
		<block v-if="isload">
		<form @submit="subform">
			<view class="form-view flex flex-col">
				<view class="form-options flex flex-bt flex-y-center">
					<view class="options-title">车辆保险到期时间</view>
					<picker @change="pickerBaoxian" mode="date">
						<view class="uni-input" v-if="pickerBaoxianTime">{{pickerBaoxianTime}}</view>
						<view class="placeholder-class" v-else>请选择时间</view>
					</picker>
				</view>
				<view class="form-options flex flex-bt flex-y-center">
					<view class="options-title">车辆年检时间</view>
					<picker @change="pickerNianjian" mode="date">
						<view class="uni-input" v-if="pickerNianjianTime">{{pickerNianjianTime}}</view>
						<view class="placeholder-class" v-else>请选择时间</view>
					</picker>
				</view>
				<view class="form-options flex flex-bt flex-y-center">
					<view class="options-title">车辆下次保养时间</view>
					<picker @change="pickerBaoyangNext" mode="date">
						<view class="uni-input" v-if="pickerBaoyangNextTime">{{pickerBaoyangNextTime}}</view>
						<view class="placeholder-class" v-else>请选择时间</view>
					</picker>
				</view>
			</view>
			<!--  -->
			<view style="width: 100%;height: calc(140rpx + env(safe-area-inset-bottom));"></view>
			<view class="pageBottom-class">
				<!-- <view class="pageBottom-but not-pageBottom-but">确定提交</view> -->
				<button class="pageBottom-but" form-type="submit" :style="{background:t('color1')}">确定提交</button>
			</view>
		</form>
		</block>
		<wxxieyi></wxxieyi>
	</view>
</template>
<script>
	import carnumberinput from './car-number-input.vue';
	var app = getApp();
	export default{
		data(){
			return{
				isload: false,
				pre_url: app.globalData.pre_url,
				id:'',
				info:{},
				opt:{},
				pickerBaoyangNextTime:'',
				pickerNianjianTime:'',
				pickerBaoxianTime:'',
			}
		},
		onLoad: function (opt) {
			this.opt = app.getopts(opt);
			this.id = this.opt.id || '';
			this.getdata();
		},
		methods: {
			getdata:function(){
				var that = this;
				that.loading = true;
				app.get('ApiCarManagement/edit', {id: that.id}, function (res) {
					that.loading = false;
					var info = res.data || {};
					that.info = info;
					if(res.data){
						that.pickerBaoxianTime = info.baoxian_time;
						that.pickerNianjianTime = info.nianjian_time;
						that.pickerBaoyangNextTime = info.baoyang_time;
					}
					that.loaded();
				});
			},

			// 车辆下次保养时间
			pickerBaoyangNext(e){
				this.pickerBaoyangNextTime = e.detail.value;
			},
			// 车辆年检时间
			pickerNianjian(e){
				this.pickerNianjianTime = e.detail.value;
			},
			// 车辆保险到期时间
			pickerBaoxian(e){
				this.pickerBaoxianTime = e.detail.value;
			},

			subform: function (e) {
			  var that = this;
			  var formdata = e.detail.value;
				console.log(formdata)
				formdata.baoxian_time = that.pickerBaoxianTime;
				formdata.nianjian_time = that.pickerNianjianTime;
				formdata.baoyang_time = that.pickerBaoyangNextTime;
				formdata.car_num = that.carNumber;
				
				app.showLoading('保存中');
			  app.post('ApiCarManagement/changetime', {id:that.id,info:formdata}, function (res) {
			    if (res.status == 0) {
			      app.error(res.msg);
			    } else {
			      app.success(res.msg);
						setTimeout(function () {
							app.goto('vehicleList', 'redirect');
						}, 1000);
			    }
			  });
			},

		},
			
	}
</script>

<style>
	.form-view{width: 100%;background: #fff;padding: 20rpx 0rpx;margin-top: 20rpx;}
	.form-options{width: 92%;margin: 0 auto;border-bottom: 1px #f8f8f8 solid;padding: 32rpx 0rpx;}
	.form-options .options-title{font-size: 28rpx;color: #242424;font-weight: 500;}
	.form-options .options-input{width: 366rpx;text-align: right;}
	.pageBottom-class{background: #fff;width: 100%;position: fixed;bottom:0;left: 50%;transform: translateX(-50%);padding-top: 20rpx;padding-bottom: calc(20rpx + env(safe-area-inset-bottom));}
	.pageBottom-but{width: 94%;border-radius: 6px;background: #9c9c9c;font-size:32rpx;font-weight: 500;color: #FFFFFF;text-align: center;margin: 0 auto;}
	.not-pageBottom-but{background: rgba(61, 91, 246, 0.2) !important;}
	/*  */
	.placeholder-class{color: #9D9DAB;font-size: 24rpx;}
</style>