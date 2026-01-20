<template>
<view class="container">
	<block v-if="isload">
    <view class="content" v-if="set.business_withdraw_invoice_mobile">
      <view class="topsearch flex-y-center sx item" style="margin: 0">
        <text class="t1">日期筛选：</text>
        <view class="t1 flex" style="line-height:30px;">
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
						<text class="t1">提现金额：{{item.money}}元</text>
						<text class="t2">{{dateFormat(item.createtime,'Y-m-d H:i')}}</text>
				</view>
				<view class="f3">
						<text class="t1" v-if="item.status==0">审核中</text>
						<text class="t1" v-if="item.status==1">已审核</text>
						<text class="t2" v-if="item.status==2">已驳回</text>
						<text class="t1" v-if="item.status==3">已打款</text>
						<block v-if="item.status==4">
							<view class="btn1" :style="{background:t('color1')}" @click="confirm_shoukuan(item.id)" v-if="item.wx_state=='WAIT_USER_CONFIRM' || item.wx_state=='TRANSFERING'">确认收款</view>
							<view class="t1" v-else-if="item.wx_state=='FAIL'">转账失败</view>
							<view class="t1" v-else>处理中</view>
						</block>
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
      set: []
    };
  },

  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.st = this.opt.st || 0;
		this.startDate = this.opt.stime || '-选择日期-';
		this.endDate = this.opt.etime || '-选择日期-';
		this.st = this.opt.st || '';
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
  onShow(){
    var that  = this;
    uni.$on('selectedDate',function(data){
      that.startDate = data.startStr.dateStr;
      that.endDate = data.endStr.dateStr;
      uni.pageScrollTo({
        scrollTop: 0,
        duration: 0
      });
      that.getdata();
    })
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
      app.post('ApiAdminFinance/bwithdrawlog', {st: st,pagenum: pagenum,date_start:date_start,date_end:date_end}, function (res) {
				that.loading = false;
        var data = res.data;
        that.set = res.set;
        if (pagenum == 1) {
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
    changetab: function (st) {
      this.st = st;
      uni.pageScrollTo({
        scrollTop: 0,
        duration: 0
      });
      this.getdata();
    },
	async confirm_shoukuan(id){
		var that = this;
		var a = await that.shoukuan(id,'business_withdrawlog','');
		that.getdata();
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
    bindEndDateChange:function(e) {
      if (this.startDate && this.startDate != '-选择日期-') {
        if (this.startDate > e.target.value) {
          app.error('结束时间必须大于等于开始时间');
          return;
        }
        this.endDate = e.target.value;
        this.getdata();
      } else {
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
      that.getdata();
    },
  }
};
</script>
<style>
.container{ width:100%;display:flex;flex-direction:column}
.content{ width:94%;margin:0 3% 20rpx 3%;}
.content .item{width:100%;background:#fff;margin:20rpx 0;padding:20rpx 20rpx;border-radius:8px;display:flex;align-items:center}
.content .item:last-child{border:0}
.content .item .f1{width:500rpx;display:flex;flex-direction:column}
.content .item .f1 .t1{color:#000000;font-size:30rpx;word-break:break-all;overflow:hidden;text-overflow:ellipsis;}
.content .item .f1 .t2{color:#666666}
.content .item .f1 .t3{color:#666666}
.content .item .f2{ flex:1;width:200rpx;font-size:36rpx;text-align:right}
.content .item .f2 .t1{color:#03bc01}
.content .item .f2 .t2{color:#000000}
.content .item .f3{ flex:1;width:200rpx;font-size:32rpx;text-align:right}
.content .item .f3 .t1{color:#03bc01}
.content .item .f3 .t2{color:#000000}

.data-empty{background:#fff}
.btn1{height:60rpx;line-height:60rpx;color:#fff;border-radius:3px;text-align:center;padding: 0 15rpx;float: right;font-size: 25rpx;margin-left: 10rpx}
</style>