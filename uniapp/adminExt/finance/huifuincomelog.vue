<template>
<view class="container">
	<block v-if="isload">
		<view style="width: 100%;">
		  <view class="topsearch flex-xy-center sx" >
		    <text class="t1">日期筛选：</text>
		    <view class="f2 flex" style="line-height:30px;">
		      <picker mode="date" :value="startDate" @change="bindStartDateChange">
		        <view class="picker">{{startDate}}</view>
		      </picker>
		      <view style="padding:0 10rpx;color:#222;font-weight:bold">至</view>
		      <picker mode="date" :value="endDate" @change="bindEndDateChange">
		        <view class="picker">{{endDate}}</view>
		      </picker>
		      <view class="t_date">
		        <view v-if="startDate" class="x1" @tap="clearDate">清除</view>
		      </view>
		    </view>
		  </view>
		</view>
		<view class="content">
			<view v-for="(item, index) in datalist" :key="index" class="item">
				<view class="f1">
						<text class="t1">{{item.settle_abstract}}</text>
						<text class="t2">结算商户号：{{item.huifu_id}}</text>
						<text class="t2">流水编号：{{item.trans_id}}</text>
						<text class="t2">结算时间：{{item.trans_date}}</text>
						<!-- <text class="t3">变更后{{t('余额')}}: {{item.after}}</text> -->
				</view>
				<view class="f2">
						<text class="t1" v-if="item.trans_amt>0">+{{item.trans_amt}}</text>
				</view>
			</view>
		</view>
		<nomore v-if="nomore"></nomore>
		<nodata v-if="nodata"></nodata>
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

		canwithdraw:false,
		textset:{},
		st: 0,
		datalist: [],
		pagenum: 1,
		nodata:false,
		nomore: false,
		startDate: '-选择日期-',
		endDate: '-选择日期-',
    };
  },

  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.st = this.opt.st || 0;
		this.getdata();
  },
	onPullDownRefresh: function () {
		this.getdata(true);
	},
  onReachBottom: function () {
    if (!this.nodata && !this.nomore) {
      this.pagenum = this.pagenum + 1;
      this.getdata(true);
    }
  },
  methods: {
    getdata: function (loadmore) {
		if(!loadmore){
			this.pagenum = 1;
			this.datalist = [];
		}
		var that = this;
		var pagenum = that.pagenum;
		var st = that.st;
		var date_start = that.startDate=='-选择日期-' ? '' : that.startDate;
		var date_end = that.endDate=='-选择日期-' ? '' : that.endDate;
		that.nodata = false;
		that.nomore = false;
		that.loading = true;
		app.post('ApiAdminFinance/getHuifuIncomeLog', {pagenum: pagenum,begin_date:date_start,end_date:date_end}, function (res) {
			that.loading = false;
			var data = res.data;
			if (pagenum == 1) {
				that.textset = app.globalData.textset;
				uni.setNavigationBarTitle({
					title:  '汇付收入明细'
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
	bindStartDateChange:function(e){
	  if(this.endDate && this.endDate != '-选择日期-'){
	    if(e.target.value > this.endDate){
	      app.error('开始时间必须小于等于结束时间');return;
	    }
	    this.startDate = e.target.value
	    this.getdata();
	  }else {
	    this.startDate = e.target.value
	  }
	},
	bindEndDateChange:function(e){
	  if(this.startDate && this.startDate != '-选择日期-'){
	    if(this.startDate > e.target.value){
	      app.error('结束时间必须大于等于开始时间');return;
	    }
	    this.endDate = e.target.value;
	    this.getdata();
	  }else {
	    this.endDate = e.target.value;
	  }
	},
	clearDate(){
	  var that  = this;
	  that.startDate = '-选择日期-';
	  that.endDate = '-选择日期-';
	  uni.pageScrollTo({
	    scrollTop: 0,
	    duration: 0
	  });
	  this.getdata();
	},
    changetab: function (st) {
      this.st = st;
      uni.pageScrollTo({
        scrollTop: 0,
        duration: 0
      });
      this.getdata();
    }

  }
};
</script>
<style>
.container{ width:100%;display:flex;flex-direction:column}
.content{ width:94%;margin:0 3% 20rpx 3%;}
.content .item{width:100%;background:#fff;margin:20rpx 0;padding:20rpx 20rpx;border-radius:8px;display:flex;align-items:center}
.content .item:last-child{border:0}
.content .item .f1{display:flex;flex-direction:column}
.content .item .f1 .t1{color:#000000;font-size:30rpx;word-break:break-all;overflow:hidden;text-overflow:ellipsis;}
.content .item .f1 .t2{color:#666666}
.content .item .f1 .t3{color:#666666}
.content .item .f2{ flex:1;font-size:36rpx;text-align:right}
.content .item .f2 .t1{color:#03bc01}
.content .item .f2 .t2{color:#000000}
.content .item .f3{ flex:1;font-size:32rpx;text-align:right}
.content .item .f3 .t1{color:#03bc01}
.content .item .f3 .t2{color:#000000}
.btn1{height:60rpx;line-height:60rpx;color:#fff;border-radius:3px;text-align:center;padding: 0 15rpx;float: right;font-size: 14px;margin-left: 10rpx}
.data-empty{background:#fff}

.topsearch {height:80rpx;border:0;background-color:#fff;flex:1}
.topsearch .f1 input{height:100%;flex:1;padding:0 20rpx;font-size:28rpx;color:#333;}
.t_date .x1{height:45rpx;line-height:40rpx;padding:0 10rpx;border:1px solid #ccc;border-radius:6rpx;font-size:22rpx;color:#666;margin: 8rpx 0 0 30rpx;}
.content .sx{padding:20rpx 16rpx; margin:0}
</style>