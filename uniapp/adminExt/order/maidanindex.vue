<template>
	<view class="container">
		
		<block v-if="isload">
			<view class="box">
				<view class="box-title">
					<view class="title">
						<text class="line" :style="{background:t('color1')}"></text><text>买单统计</text>
					</view>
					<view class="more" @tap="goto" data-url="maidanlog"><text>全部记录</text><image class="icon" :src="pre_url+'/static/img/arrowright.png'"></image></view>
				</view>
				<view class="box-main range" :style="{borderColor:t('color1')}">
					<view class="range-item" :style="'border:none;background:'+(range==1?'rgba('+t('color1rgb')+',0.2)':'')" @tap="changeRange(1)">今天</view>
					<view class="range-item" :style="'border-color:'+t('color1')+';background:'+(range==2?'rgba('+t('color1rgb')+',0.2)':'')" @tap="changeRange(2)">昨天</view>
					<view class="range-item" :style="'border-color:'+t('color1')+';background:'+(range==3?'rgba('+t('color1rgb')+',0.2)':'')" @tap="changeRange(3)">本月</view>
					<view class="range-item" :style="'border-color:'+t('color1')+';background:'+(range==4?'rgba('+t('color1rgb')+',0.2)':'')" @tap="changeRange(4)">上月</view>
					<view class="range-item" :style="'border-color:'+t('color1')+';background:'+(range==5?'rgba('+t('color1rgb')+',0.2)':'')" @tap="toggleTimeModal">自定义</view>
				</view>
				<view class="tab">
					<block v-for="(item,index) in paytypelist" :key="index">
						<view class="tab-item">
							<view class="tab-txt">{{item.paytype}}</view>
							<view class="tab-money">￥{{item.total_amount}}</view>
						</view>
					</block>
				</view>
			</view>
			<!-- #ifndef MP-QQ -->
			<!-- QQ主包超出，echart不打包 -->
			<view class="echart">
				<view class="box-title">
					<view class="title"><text class="line" :style="{background:t('color1')}"></text><text>收款趋势</text></view>
				</view>
				<view class="echart-content">
				<view v-if="showechart"><l-echart ref="chart" @finished="init" class="charts-box"></l-echart></view>
				</view>
			</view>
			<!-- #endif -->
			<view v-if="ischooserange" class="popup__container">
				<view class="popup__overlay" @tap.stop="toggleTimeModal"></view>
				<view class="popup__modal">
					<view class="popup__title">
						<view class="headertab">
							<view :class="rangeType==1?'item on':'item'" :style="{color:rangeType==1?t('color1'):''}"  @tap="rangeTypeChange(1)">月份选择</view>
							<view :class="rangeType==2?'item on':'item'" :style="{color:rangeType==2?t('color1'):''}"  @tap="rangeTypeChange(2)">日期选择</view>
						</view>
						<image :src="pre_url+'/static/img/close.png'" class="popup__close" style="width:30rpx;height:30rpx" @tap.stop="toggleTimeModal"/>
					</view>
					<view class="popup__content">
						<view class="month-tab"  v-if="rangeType==1">
							<view class="month-label">月份</view>
							<view>
								<picker class="date" mode="date" :value="month" fields="month"  @change="bindDateChange" :end="curdate" data-field="month">
									<view class="uni-input">{{month?month:'请选择'}}</view>
								</picker>
							</view>
						</view>
						<view class="time-tab" v-if="rangeType==2">
							<view class="month-label">日期</view>
							<view class="time-date">
								<picker class="date" mode="date" :value="start_date"  @change="bindDateChange" :end="curdate" data-field="start_date">
									<view class="uni-input">{{start_date?start_date:'开始时间'}}</view>
								</picker>
								<text class="dt">至</text>
								<picker class="date" mode="date" :value="end_date"  @change="bindDateChange" :end="curdate" data-field="end_date">
									<view class="uni-input">{{end_date?end_date:'结束时间'}}</view>
								</picker>
							</view>
						</view>
					</view>
					<view class="popup__bottom">
						<button class="popup_btn btn1" @tap="resetTimeChoose">重 置</button>
						<button class="popup_btn" @tap="confirmTimeChoose" :style="{background:t('color1'),color:'#fff'}">确 定</button>
					</view>
				</view>
			</view>
		</block>
		<loading v-if="loading"></loading>
		<dp-tabbar :opt="opt"></dp-tabbar>
		<popmsg ref="popmsg"></popmsg>
	</view>
</template>

<script>
	var app = getApp();
	// #ifndef MP-QQ
	import lEchart from '../../echarts/l-echart/l-echart.vue';
	// H5平台需要动态加载echarts
// import * as echarts from '../../echarts/static/echarts.min.js';
let echarts = null;
if (typeof window !== 'undefined') {
    echarts = window.echarts;
}
	// #endif
	export default {
		// #ifndef MP-QQ
		components: {
		    lEchart
		},
		// #endif
	  data() {
	        return {
						opt:{},
						loading:false,
						isload: false,
						menuindex:-1,
						
						datalist: [],
						pagenum: 1,
						nomore: false,
						nodata:false,
						range:1,
						paytypelist:[],
						chartdata:{},
						yData:[],
						charttype:1,
						chartname:'收款金额',
						chartcolor:'#ee6666',
						ischooserange:false,
						rangeType:1,
						month:'',
						start_date:'',
						end_date:'',
						option: {},
						showechart:true,
						curdate:'',
						pre_url:app.globalData.pre_url,
	        };
	    },
			onLoad: function (opt) {
				this.opt = app.getopts(opt);
				this.getdata();
			},
			onPullDownRefresh: function () {
				this.getdata();
			},
	    mounted() {
				// var chart = app.$refs.chart.init();
	        // this.$refs.chart.init(echarts, chart => {
	        //     chart.setOption(this.option);
	        // });
					 // chart = await this.$refs.chart.init(echarts);
	    },
	    // 2、或者使用组件的finished事件里调用
	    methods: {
	        async init() {
	            // chart 图表实例不能存在data里
							// #ifndef MP-QQ
	            const chart = await this.$refs.chart.init(echarts);
	            chart.setOption(this.option)
							// #endif
	        },
					getdata: function () {
						var that = this;
						that.loading = true;
					  app.post('ApiAdminMaidan/index', {range: that.range,rangType:that.rangeType,month:that.month,start_date:that.start_date,end_date:that.end_date}, function (res) {
							that.loading = false;
					    that.paytypelist = res.paytypelist;
							that.chartdata = res.chartdata
							that.curdate = res.curdate;
							that.echartsInit();
							that.loaded();
					  });
					},
					changeRange:function(range){
						this.range = range;
						this.getdata();
					},
					echartsInit:function(){
						var that = this;
						this.option = {
							tooltip: {
								trigger: 'axis'
							},
							legend: {
								show:true,
								selectedMode:"single"
							},
							grid: {
								left: '3%',
								right: '4%',
								bottom: '3%',
								containLabel: true
							},
							xAxis: {
								type: 'category',
								boundaryGap: false,
								data: that.chartdata.xData
							},
							yAxis: {
								type: 'value'
							},
							series: [
								{
									name: '收款金额',
									type: 'line',
									stack: '总量',
									data: that.chartdata.yData
								},
								{
									name: '收款笔数',
									type: 'line',
									stack: '总量',
									data:that.chartdata.yData1
								}
							]
						};
						this.init();
					},
					toggleTimeModal:function(){
						this.showechart = !this.showechart
						this.ischooserange = !this.ischooserange
						if(this.ischooserange){
							this.range = 5;
						}
					},
					rangeTypeChange:function(rangType){
						this.rangeType = rangType
					},
					bindDateChange:function(e){
						var field = e.currentTarget.dataset.field;
						this[field] = e.detail.value;
					},
					resetTimeChoose:function(){
						this.month = '';
						this.start_date = ''
						this.end_date = ''
						this.range = 1;
						this.ischooserange = false;
						this.getdata()
						this.showechart = true
					},
					confirmTimeChoose:function(){
						this.ischooserange = false;
						this.showechart = true
						this.getdata()
					}
	    }
	}
</script>

<style>
	/* 请根据实际需求修改父元素尺寸，组件自动识别宽高 */
	.charts-box {
	  width: 100%;
	  min-height: 640rpx;
		}
		.box{width: 92%; margin: 30rpx 4%; border-radius: 16rpx; background: #fff;padding: 30rpx;}
		.box-title{border-bottom: 1px solid #ededed;padding-bottom:20rpx;display: flex;align-items: center;justify-content: space-between;}
		.box-title .title{display: flex;align-items: center;}
		.box-title .line{width: 6rpx;height: 24rpx;border-radius: 4rpx;margin-right: 16rpx;}
		.box-title .more{display: flex;align-items: center;justify-content: flex-end;color: #999;}
		.box-title .more .icon{width: 26rpx;height: 26rpx;}
		.box-main{margin-top: 30rpx;}
		.range{display: flex;justify-content: space-between;align-items: center;border: 1px solid #ccc;border-radius: 8rpx;}
		.range .range-item{border-left: 1px solid #ccc;flex: 1;text-align: center; padding: 10rpx 0;}
		.range .range-item:first{border: none;}
		.tab{display: flex;align-items: center;margin-top: 20rpx;flex-wrap: wrap;}
		.tab-item{display: flex;flex-direction: column;align-items: center;padding: 10rpx 0;width: 33%;line-height: 60rpx;}
		.tab-item .tab-txt{color: #999;}
		.tab-item .tab-money{font-weight: bold;font-size: 30rpx;color: #222222;}
		.echart{width: 92%; margin: 30rpx 4%; border-radius: 16rpx; background: #fff;padding: 30rpx 0;}
		.echart .box-title{padding-left: 30rpx;}
		.echart .echart-content{padding: 20rpx;}
		.echart-option{display: flex;justify-content: center;}
		.echart-line{min-height: 500rpx;width: 100%;display: flex;justify-content: center;}
		.echart-option .opt{padding:10rpx 30rpx;min-width: 200rpx;display: flex;align-items: center;}
		.echart-option .opt1{color: #ee6666;}
		.echart-option .opt1 .dot{background: #ee6666;border-radius: 50%;width: 20rpx;height: 20rpx;margin-right: 12rpx;}
		.echart-option .opt2{color: #4e9d77;}
		.echart-option .opt2 .dot{background: #4e9d77;border-radius: 50%;width: 20rpx;height: 20rpx;margin-right: 12rpx;}
		.headertab{display: flex;align-items: center;}
		.headertab .item{padding-bottom: 10rpx;margin-right: 40rpx;}
		.headertab .item.on{font-weight: bold;border-bottom: 2px solid;}
		/* .popup__title{border-bottom: 1px solid #ededed;padding: 20rpx;} */
		.popup__content{padding: 20rpx 50rpx;line-height: 60rpx;}
		.popup__bottom{position: absolute;bottom: 20rpx;width: 80%;left: 10%;color: #fff;display: flex;justify-content: center;}
		.popup__bottom .popup_btn{border-radius: 70rpx;color: #fff;width: 260rpx;}
		.popup__bottom .popup_btn.btn1{border: 1px solid #c9c9c9;color: #222222;}
		.time-date{display: flex;align-items: center;}
		.date{width: 200rpx;border-bottom: 1px solid #ededed;}
		.time-date .dt{width: 80rpx;text-align: center;}
		.month-label{font-weight: bold;font-size: 30rpx;}
</style>
