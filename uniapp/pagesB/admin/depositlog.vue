<template>
<view>
	<block v-if="isload">
	<view class="mymoney" :style="{background:t('color1')}">
		<view class="f1">我的{{t('入驻保证金')}}</view>
		<view class="f2"><text style="font-size:26rpx">￥</text>{{deposit}}</view>
		<view class="f3" v-if="deposit_refund " >
			<text v-if="refund_status ==1">审核中</text>
			<text @tap="depositRefund()" v-if="refund_status !=1 && deposit > 0">退款申请</text>
		</view>
	</view>
		<view class="content" v-if="datalist && datalist.length>0">
			<view v-for="(item, index) in datalist" :key="index" class="item">
				<view class="f1">
						<text class="t1">{{item.remark}}</text>
						<text class="t2">{{dateFormat(item.createtime)}}</text>
						<text class="t3">变更后余额: {{item.after}}</text>
				</view>
				<view class="f2">
						<text class="t1" v-if="item.money>0">+{{item.money}}</text>
						<text class="t2" v-else>{{item.money}}</text>
				</view>
			</view>
		</view>
		<nodata v-if="nodata"></nodata>
		<nomore v-if="nomore"></nomore>
	</block>
	<loading v-if="loading"></loading>
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
			
			keyword:'',
      st: 0,
			count:0,
      datalist: [],
      pagenum: 1,
      nodata: false,
      nomore: false,
	  deposit:0,
	  deposit_refund:0,
	  refund_status:0
    };
  },
  
  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.getdata();
  },
	onPullDownRefresh: function () {
		this.getdata();
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
      var keyword = that.keyword;
			that.nodata = false;
			that.nomore = false;
			that.loading = true;
      app.post('ApiAdminFinance/depositloglist', {keyword:keyword,pagenum: pagenum}, function (res) {
				that.loading = false;
		uni.setNavigationBarTitle({
			title: that.t('入驻保证金')+'记录'
		});	
        var data = res.data;
		that.deposit = res.deposit;
		that.deposit_refund = res.business_deposit_refund;
		that.refund_status = res.refund_status;
        if (pagenum == 1){
			that.count = res.count;
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
    changetab: function (e) {
      var st = e.currentTarget.dataset.st;
      this.st = st;
      uni.pageScrollTo({
        scrollTop: 0,
        duration: 0
      });
      this.getdata();
    },
    searchChange: function (e) {
      this.keyword = e.detail.value;
    },
    searchConfirm: function (e) {
      var that = this;
      var keyword = e.detail.value;
      that.keyword = keyword;
      that.getdata();
    },
	depositRefund:function(){
		
		app.showLoading('提交中');
		app.post('ApiAdminFinance/depositrefund', {}, function (data) {
			app.showLoading(false);
			app.alert(data.msg);
			var pages = getCurrentPages();
			if (pages.length > 1) {
				var prePage = pages[pages.length - 2];
				prePage.onLoad();
			}
			setTimeout(function () {
				app.goback();
			}, 1000);
		});
	}
  }
};
</script>
<style>
.mymoney{width:94%;margin:20rpx 3%;border-radius: 10rpx 56rpx 10rpx 10rpx;position:relative;display:flex;flex-direction:column;padding:70rpx 0}
.mymoney .f1{margin:0 0 0 60rpx;color:rgba(255,255,255,0.8);font-size:24rpx;}
.mymoney .f2{margin:20rpx 0 0 60rpx;color:#fff;font-size:64rpx;font-weight:bold}
.mymoney .f3{height:56rpx;padding:0 10rpx 0 20rpx;border-radius: 28rpx 0px 0px 28rpx;background:rgba(255,255,255,0.2);font-size:20rpx;font-weight:bold;color:#fff;display:flex;align-items:center;position:absolute;top:94rpx;right:0}

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
</style>