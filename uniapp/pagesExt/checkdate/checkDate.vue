<template>
	<view v-if="pageState">
		<calendar :is-show="true" :between-start="disabledStartDate" :between-end="disabledEndDate" initMonth="24" :ys-num="opt.ys" :choose-type="opt.type" :start-date="startDate" :themeColor="t('color1')" :end-date="endDate" :tip-data="t_data" :mode="t_mode" @callback="getDate" />
			<view v-if="noticeState" class="date-notice">点击日期选择时间</view>
			<view class="date-footer" >
				<view class="btn btn1" @click="clearChoose">清除</view>
				<view class="btn btn2" @click="toDetail" :style="{background:t('color1')}">确定</view>
			</view>
		</calendar>
	</view>
</template>

<script>
	import Calendar from '@/pagesExt/cycle/mobile-calendar-simple/Calendar.vue'
	var app = getApp();
	export default {
		data() {
			return {
				opt: {},
				startDate: '',
				disabledStartDate: "",
				disabledEndDate: "",
				endDate: '',
				selectedDate: '',
				t_data: [{
					date: "1661529600000",
					value: '待收货'
				}, {
					date: "1661702400000",
					value: '待派送'
				}],
				noticeState: true,
				pageState: false,
				t_mode:1,
        otherParam:'',
			}
		},
		onLoad(opt) {
			this.opt = app.getopts(opt);
      if(this.opt.date || this.opt.startdate){
        this.startDate = this.opt.date || this.opt.startdate;
      }
      if(this.opt.enddate){
        this.endDate = this.opt.enddate;
      }
			var dayin =-300*2;
			if(this.opt.dayin){
				dayin = this.opt.dayin;//显示前多少天
			}
			this.disabledStartDate = this.getAddDays(dayin);
      
      var dayin2 = 0;
      if(this.opt.dayin2){
      	dayin2 = this.opt.dayin2;//显示后多少天
      }
			this.disabledEndDate = this.getAddDays(dayin2);
			console.log('开始日期'+this.disabledStartDate);
			console.log('结束日期'+this.disabledEndDate);
			if(this.opt.t_mode){
				this.t_mode = this.opt.t_mode;
			}
      if(this.opt.otherParam){
        this.otherParam = this.opt.otherParam;
      }
			setTimeout(()=>{
				this.noticeState = false
			},5000);
			setTimeout(()=>{
				this.pageState = true
			},100);
		},
		methods: {
			getAddDays(dayIn = 0) {
				var date = new Date();
				var myDate = new Date(date.getTime() + dayIn * 24 * 60 * 60 * 1000);
				var year = myDate.getFullYear();
				var month = myDate.getMonth() + 1;
				var day = myDate.getDate();
				var CurrentDate = year + "-";
				if (month >= 10) {
					CurrentDate = CurrentDate + month + "-";
				} else {
					CurrentDate = CurrentDate + "0" + month + "-";
				}
				if (day >= 10) {
					CurrentDate = CurrentDate + day;
				} else {
					CurrentDate = CurrentDate + "0" + day;
				}
				return CurrentDate;
			},
			//获取回调的日期数据
			getDate(date) {
				this.selectedDate = date;
			},
			toDetail() {
				// var week = this.selectedDate.week;
				// var s_date = this.selectedDate.dateStr
				if (!this.selectedDate) {
					app.error('请选择开始时间');
					return;
				}
				console.log(this.selectedDate)
				uni.$emit('selectedDate', this.selectedDate,this.otherParam)
				// app.goto('/pagesExt/week/planWrite?date='+s_date+'&week='+week);
				uni.navigateBack({
					delta: 1
				})
			},
			clearChoose() {
				var selectedDate = {};
				selectedDate.startStr  = this.resetDate();
				selectedDate.endStr  = this.resetDate();
				this.selectedDate = selectedDate;
				uni.$emit('selectedDate', this.selectedDate)
				// app.goto('/pagesExt/week/planWrite?date='+s_date+'&week='+week);
				uni.navigateBack({
					delta: 1
				})
			},
			resetDate() {
				return {
				    dateStr: '',
				    week: '',
				    recent:''
				}
			}
		},
		components: {
			Calendar
		}
	}
</script>
<style>
	page {
		background: #F6F6F6;
	}
</style>
<style scoped>
	.date-footer {
		height: 60px;
		line-height: 60px;
		font-size: 18px;
		color: #fff;
		text-align: center;
		background-color: #FFFFFF;
		width: 100%;
		position: fixed;
		bottom: 0;
		left: 0;
		z-index: 11111;
		display: flex;
		justify-content: center;
		align-items: center;
		border-top: 1rpx solid #eeeeee;
	}
	.date-footer .btn{width: 45%; height: 40px;line-height: 40px;font-size: 28rpx;}
	.date-footer .btn1{border-radius: 50rpx 0 0 50rpx;background: #d6d6d6;color: #707070;}
	.date-footer .btn2{border-radius: 0 50rpx 50rpx 0;background: #1C75FF;color: #FFFFFF;}
	.date-notice{
		position: fixed;
		width: 390rpx;
		text-align: center;
		height: 40px;
		line-height: 40px;
		background: rgba(0, 0, 0, 0.6);
		border-radius: 5px;
		left: 0;
		right: 0;
		color: #fff;
		font-size: 28rpx;
		margin: 0 auto;
		bottom: 200rpx;
		pointer-events: none;
		z-index: 11111;
	}
</style>
