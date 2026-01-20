<template>
<view>
	<block v-if="isload">
    <view class="surverycontent" >
      <view class="item">
        <view class="t2"><text>待开票金额</text></view>
        <view class="t2">￥{{dkp_amount ? dkp_amount : 0}}</view>
      </view>
      <view class="item" >
        <view class="t2"><text>已开票金额</text></view>
        <view class="t2">￥{{ykp_amount ? ykp_amount : 0}}</view>
      </view>
    </view>
		<view class="tabcontent">
			<dd-tab :itemdata="['待开票账单','待提交发票','开票记录']" :itemst="['0','1','2']" :st="st" @changetab="changetab"></dd-tab>
		</view>
		<view class="content">
			<view v-for="(item, index) in datalist" :key="index" class="item">
				<view class="f2" >
						<text class="t1" v-if="st==0">账单金额：￥{{item.zdmoney}}</text>
						<text class="t1" v-else>开票金额：￥{{item.zdmoney}}</text>
						<text class="t3" v-if="st==1 || st==2" style="font-size: 13px">业务单号：{{item.ordernum}}</text>
						<text class="t3" style="font-size: 13px">账单周期：{{item.zdzq}}</text>
						<text class="t3" style="font-size: 13px">包含订单数：{{item.zdnum}}</text>
						<text class="t3" v-if="st==1 && item.status == 4" style="height:auto;font-size: 13px;color: red">已驳回<text v-if="item.rejection">，原因：{{item.rejection}}</text></text>
						<text class="t3" v-if="st==2 && item.status == 3" style="height:auto;font-size: 13px;color: #527dd2">审核中</text>
				</view>
        <view class="">
          <view  v-if="st==0 || st==1" class="btn1" @tap="goto" :data-url="'/adminExt/finance/withdrawInvoiceInfo?st='+st+'&stime=' + item.stime +'&etime='+ item.etime+'&id='+ item.id" :data-id="item.id" :style="{background:t('color2')}">开票信息</view>
          <view v-if="st==0" class="btn1" @tap="goto" :data-url="'/admin/finance/bwithdrawlog?st=3&stime=' + item.stime +'&etime='+ item.etime" :data-id="item.id"  :style="{background:t('color1')}">账单明细</view>
          <view v-if="st==1" class="btn1" @tap="goto" :data-url="'/adminExt/finance/uploadwithdrawInvoice?id='+ item.id"  :style="{background:t('color1')}">上传发票</view>
          <view v-if="st==2" class="btn1" @tap="goto" :data-url="'/adminExt/finance/uploadwithdrawInvoice?id='+ item.id" :data-id="item.id"  :style="{background:t('color1')}">发票信息</view>
        </view>
			</view>
		</view>
		<nomore v-if="nomore"></nomore>
		<nodata v-if="nodata"></nodata>
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
      pre_url:app.globalData.pre_url,
      dkp_amount:0,
      ykp_amount:0,
    };
  },
  
  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.st = this.opt.st || 0;
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
      app.post('ApiAdminFinance/withdrawInvoice', {keyword:keyword,pagenum: pagenum,st:st}, function (res) {
				that.loading = false;
        var data = res.data;
        if (pagenum == 1){
					that.count = res.count;
					that.datalist = data;
					that.dkp_amount = res.dkp_amount || 0;
					that.ykp_amount = res.ykp_amount || 0;
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
  }
};
</script>
<style>
.tabcontent{width:94%;margin:16rpx 3%;overflow:hidden}
.content{ width:94%;margin:0 3%;border-radius:16rpx;background:#fff;}
.content .item{ width:100%;padding:20rpx 20rpx;border-bottom:1px solid #f6f6f6;display:flex;align-items:center}
.content .item:last-child{border:0}
.content .item .f1{display:flex;flex-direction:column;margin-right:20rpx}
.content .item .f1 .t1{width:100rpx;height:100rpx;margin-bottom:10rpx;border-radius:50%}
.content .item .f1 .t2{color:#666666;text-align:center;overflow: hidden;text-overflow: ellipsis;display: -webkit-box;-webkit-box-orient: vertical;-webkit-line-clamp: 2;word-break: break-all;width: 105rpx;font-size: 26rpx;}
.content .item .f1 .t3{color:#666666}
.content .item .f2{ flex:1;font-size:30rpx;display:flex;flex-direction:column}
.content .item .f2 .t1{color:red;font-size:32rpx;height:50rpx;line-height:50rpx}
.content .item .f2 .t2{color:#999;font-size:28rpx;height:40rpx;line-height:40rpx}
.content .item .f2 .t3{color:#aaa;font-size:28rpx;height:40rpx;line-height:40rpx}
.content .item .f3{ font-size:32rpx;text-align:right}
.content .item .f3 .t1{color:red}
.content .item .f3 .t2{color:#000000}

.surverycontent{width: 100%;padding:20rpx 24rpx 30rpx;display:flex;flex-wrap:wrap;background: #fff;padding-top:20rpx;display:flex;justify-content: space-between;}
.surverycontent .item{width:340rpx;background: linear-gradient(to right,#ddeafe,#e6effe);margin-bottom:20rpx;padding:26rpx 30rpx;display:flex;flex-direction:column;border-radius:20rpx;}
.surverycontent .item .t1{width: 100%;color: #121212;font-size:24rpx;display: flex;align-items: center;justify-content: space-between;}
.surverycontent .item .t1 image{width: 25rpx;height: 25rpx;}
.surverycontent .item .t2{width: 100%;color: #222;font-size:36rpx;font-weight:bold;overflow-wrap: break-word;display: flex;align-items: flex-end;justify-content: flex-start;padding: 15rpx 0rpx;}
.surverycontent .item .t2 .price-unit{font-size: 24rpx;color: #222;font-weight:none;padding-bottom: 6rpx;margin-left: 5rpx;}
.surverycontent .item .t3{width: 100%;color: #999;font-size:24rpx;display: flex;align-items: center;flex-wrap: wrap;}
.surverycontent .item .t3:nth-first{margin-bottom: 10rpx;}
.surverycontent .item .t3 .price-color{color: #0060FF;display: flex;align-items: center;display: flex;align-items: center;}
.surverycontent .item .t3 .price-color image{width: 20rpx;height: 24rpx;margin-left: 10rpx;}
.op{ display:flex;flex-wrap: wrap;justify-content:flex-end;align-items:center;width:100%; padding: 10rpx 0px; border-top: 1px #f4f4f4 solid; color: #555;}
.btn1{margin-left:20rpx; margin-top: 10rpx;max-width:160rpx;height:60rpx;line-height:60rpx;color:#fff;border-radius:3px;text-align:center;padding: 0 20rpx;}
.btn2{margin-left:20rpx; margin-top: 10rpx;max-width:160rpx;height:60rpx;line-height:60rpx;color:#333;background:#fff;border:1px solid #cdcdcd;border-radius:3px;text-align:center;padding: 0 20rpx;}
</style>