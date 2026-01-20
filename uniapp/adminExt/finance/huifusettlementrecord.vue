<template>
<view class="container">
	<block v-if="isload">
		<dd-tab :itemdata="['成功','处理中','失败']" :itemst="['S','I','F']" :st="st" :showstatus="showstatus" :ismoney="1" :isfixed="true" @changetab="changetab"></dd-tab>
		<view style="width: 100%;margin-top: 95rpx;">
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
			<view v-for="(item, index) in datalist" :key="index" class="item ">
				<view class="flex-bt f1">
					<view class="view1">结算单号：{{item.trans_id}}</view>
					<view class="view2">
						<text v-if="item.trans_stat =='S'">结算成功</text>
						<text v-else-if="item.trans_stat =='I'">处理中</text>
						<text v-else-if="item.trans_stat =='F'">结算失败</text>
					</view>
				</view>
				<view class="border"></view>
				<view class="flex-bt f4 " style="padding: 20rpx 50rpx;">
					<view >结算金额(元)</view>
					<view style="font-weight: 700;">{{item.trans_amt}}</view>
				</view>
				<view class="flex-bt f4 " style="padding: 20rpx 50rpx;">
					<view >结算手续费(元)</view>
					<view style="font-weight: 700;">{{item.fee_amt}}</view>
				</view>
				<view class="flex-bt f4 " style="padding: 20rpx 50rpx;">
					<view >结算时间</view>
					<view style="font-weight: 700;">{{item.trans_date}}</view>
				</view>
				<block>
					<view class="f2 flex-bt" style="flex-flow: wrap;">
						<view  class="bankinfo">
							<view class="t1">结算账户</view>
							<view class="t2">银行卡</view>
						</view>
						<view class="bankinfo">
							<view class="t1">银行户名</view>
							<view class="t2">{{item.card_name}}</view>
						</view>
						<view class="bankinfo">
							<view class="t1">卡号</view>
							<view class="t2">{{item.card_no}}</view>
						</view>
						<view class="bankinfo">
							<view  class="t1">开户行名称</view>
							<view class="t2">{{item.bank_code_name}}</view>
						</view>
					</view>
					<view class="f2 flex-bt" style="flex-flow: wrap;margin-top: 20rpx;" v-if="item.trans_stat =='F'">
						<view  class="bankinfo" style="width: 100%;">
							失败原因：{{item.settle_desc}}
						</view>
					</view>
				</block>
				
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
		st: 'S',
		showstatus:['S','I','F'],
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
		this.st = this.opt.st || 'S';
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
		app.post('ApiAdminFinance/getHuifuSettementLog', {pagenum: pagenum,begin_date:date_start,end_date:date_end,st:st}, function (res) {
			that.loading = false;
			var data = res.data;
			if(res.status ==0){
				app.error(res.msg);
				return;
			}
			if (pagenum == 1) {
				that.textset = app.globalData.textset;
				uni.setNavigationBarTitle({
					title:  '汇付结算记录'
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
.content .item{width:100%;background:#fff;margin:20rpx 0;border-radius:8px;padding-bottom: 30rpx;}
.content .item .f1{
	padding: 30rpx 20rpx;
	font-size: 28rpx;
}
.border{
	border-bottom: 2rpx solid #eeeeee;
}
.content .item .f2{
	width: 95%;
	margin: 0 2.5%;
	padding: 10rpx;
	background-color:#F7F7F7 ;
}
.bankinfo{
	width: 50%;
	text-align: left;
	padding: 20rpx;
}
.bankinfo .t1{color: #757575;}
.bankinfo .t2{font-weight: bold;}
.content .item .f3{
	padding: 20rpx 50rpx;
}
.btn1{height:60rpx;line-height:60rpx;color:#fff;border-radius:3px;text-align:center;padding: 0 15rpx;float: right;font-size: 14px;margin-left: 10rpx}
.data-empty{background:#fff}

.topsearch {height:80rpx;border:0;background-color:#fff;flex:1}
.topsearch .f1 input{height:100%;flex:1;padding:0 20rpx;font-size:28rpx;color:#333;}
.t_date .x1{height:45rpx;line-height:40rpx;padding:0 10rpx;border:1px solid #ccc;border-radius:6rpx;font-size:22rpx;color:#666;margin: 8rpx 0 0 30rpx;}
.content .sx{padding:20rpx 16rpx; margin:0}
</style>