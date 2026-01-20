<template>
<view class="container">
	<block v-if="isload">
		<dd-tab :itemdata="[t('余额')+'明细','充值记录','提现记录']" :itemst="['0','1','2']" :st="st" :showstatus="showstatus" :ismoney="1" :isfixed="true" @changetab="changetab"></dd-tab>
		<view class="content">
			<block v-if="st==0">
			<view v-for="(item, index) in datalist" :key="index" class="item">
				<view class="f1">
						<text class="t1">{{item.remark}}</text>
						<text class="t2">{{item.createtime}}</text>
						<text class="t3">变更后{{t('余额')}}: {{item.after}}</text>
				</view>
				<view class="f2">
						<text class="t1" v-if="item.money>0">+{{item.money}}</text>
						<text class="t2" v-else>{{item.money}}</text>
				</view>
			</view>
			</block>
			<block v-if="st==1">
			<view v-for="(item, index) in datalist" :key="index" class="item">
				<view class="f1">
						<text class="t1">充值金额：{{item.money}}元</text>
						<text class="t2">{{item.createtime}}</text>
						<text class="t2" v-if="item.mendian_name">所属门店：{{item.mendian_name}}</text>
				</view>
        <view class="f3" v-if="item.money_recharge_transfer && item.paytypeid == 5">
          <view v-if="item.transfer_check == 1" >
            <text class="t2" style="line-height: 60rpx;font-size:13px" v-if="item.payorder.check_status == 1 && item.status==0">充值失败</text>
            <text class="t2" style="line-height: 60rpx;font-size:13px" v-if="item.payorder.check_status == 2 && item.status==0">凭证被驳回</text>
            <text class="t2" style="line-height: 60rpx;font-size:13px" v-if="!item.payorder.check_status && !item.payorder.paypics && item.status==0">凭证待上传</text>
            <text class="t2" style="line-height: 60rpx;font-size:13px" v-if="!item.payorder.check_status && item.payorder.paypics && item.status==0">凭证审核中</text>
            <text class="t1" style="line-height: 60rpx;font-size:13px" v-if="(item.payorder.check_status == 1 || item.payorder.check_status == 2) && (item.status==1)">充值成功</text>
            <text class="btn1" :style="{background:t('color1')}" @tap.stop="goto" :data-url="'/pages/pay/transfer?id=' + item.payorderid">付款凭证</text>
          </view>
          <view v-else style="font-size:13px">
            <text v-if="item.transfer_check == 0">转账待审核</text>
            <text v-if="item.transfer_check == -1">转账已驳回</text>
          </view>
        </view>
				<view class="f3" v-else style="font-size:13px">
						<text class="t2" v-if="item.status==0">充值失败</text>
						<text class="t1" v-if="item.status==1">充值成功</text>
				</view>
			</view>
			</block>
			<block v-if="st==2">
			<view v-for="(item, index) in datalist" :key="index" class="item">
				<view class="f1">
						<text class="t1">提现金额：{{item.money}}元</text>
						<text class="t2">{{item.createtime}}</text>
						<text class="t2" v-if="item.status==2">驳回原因：{{item.reason  || '无'}}</text>
				</view>
				<view class="f3">
						<text class="t1" v-if="item.status==0">审核中</text>
						<text class="t1" v-if="item.status==1">已审核</text>
						<block v-if="item.status==4">
							<view class="btn1" :style="{background:t('color1')}" @click="confirm_shoukuan(item.id)" v-if="item.wx_state=='WAIT_USER_CONFIRM' || item.wx_state=='TRANSFERING'">确认收款</view>
							<view class="t1" v-else-if="item.wx_state=='FAIL'">转账失败</view>
							<view class="t1" v-else>处理中</view>
						</block>
						<text class="t2" v-if="item.status==2">已驳回</text>
						<text class="t1" v-if="item.status==3">已打款</text>
						
				</view>
			</view>
			</block>
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
	  showstatus:[1,1,1],
	  appinfo:{},//提现数据对应的平台信息
	  detail:{},//提现详情内容
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
			that.nodata = false;
			that.nomore = false;
			that.loading = true;
      app.post('ApiMy/moneylog', {st: st,pagenum: pagenum}, function (res) {
				that.loading = false;
        var data = res.data;
        if (pagenum == 1) {
					that.textset = app.globalData.textset;
					uni.setNavigationBarTitle({
						title: that.t('余额') + '明细'
					});
					that.canwithdraw = res.canwithdraw;
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
		console.log(res.data.showstatus,'showstatus');
		if(res.showstatus.length > 0){
			that.showstatus = res.showstatus;
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
		var a = await that.shoukuan(id,'member_withdrawlog','');
		console.log(a);
		that.getdata();
	}

  }
};
</script>
<style>
.container{ width:100%;margin-top:90rpx;display:flex;flex-direction:column}
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
</style>