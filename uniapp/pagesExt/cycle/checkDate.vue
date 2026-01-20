<template>
	<view v-if="pageState">
		<calendar :is-show="true" :between-start="disabledStartDate" :ys-num="opt.ys" :choose-type="opt.type" :start-date="startDate" :end-date="endDate" :tip-data="t_data" mode="1" @callback="getDate" />
			<view v-if="noticeState" class="date-notice">点击日期修改开始时间</view>
			<view class="date-footer" @click="toDetail">确定</view>
		</calendar>
	</view>
</template>

<script>
	import Calendar from './mobile-calendar-simple/Calendar.vue'
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
				pageState: false
			}
		},
		onLoad(opt) {
			this.opt = app.getopts(opt);
			this.startDate = this.opt.date;
			this.disabledStartDate = this.getAddDays(this.opt.ys);
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
				uni.$emit('selectedDate', this.selectedDate)
				// app.goto('/pagesExt/week/planWrite?date='+s_date+'&week='+week);
				uni.navigateBack({
					delta: 1
				})
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
		height: 50px;
		line-height: 50px;
		font-size: 18px;
		color: #fff;
		text-align: center;
		background-color: #f44336;
		width: 100%;
		position: fixed;
		bottom: 0;
		left: 0;
		z-index: 11111;
	}
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
