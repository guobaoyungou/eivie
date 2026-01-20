<template>
<view class="container">
	<block v-if="isload">
		<view v-if="showmyxianjin" class="mydedamount" :style="{background:t('color1')}">
			<view class="f1">
				我的{{t('现金')}}
			</view>
			<view class="item flex-bt flex-y-center">
				<view class="value">{{myxianjin}}</view>
			</view> 
		</view>
    <dd-tab :itemdata="[t('现金')+'明细','充值记录','提现记录']" :itemst="['0','1','2']" :st="st" :showstatus="showstatus" :ismoney="1" :isfixed="false" @changetab="changetab"></dd-tab>
		<view class="content" id="datalist">
      <block v-if="st==0">
        <block v-for="(item, index) in datalist" :key="index"> 
          <view class="item">
            <view class="f2" style="display: flex;justify-content: space-between;">
              <view class="t1" style="color: #000;">
                变动金额：
                <text v-if="item.money>0" style="color: green;">+{{item.money}}</text>
                <text v-else style="color: red;">{{item.money}}</text>
              </view>
            </view>
            <view class="f1">
              <view class="t2">{{item.remark}}</view>
            </view>
            <view class="f1">
              <view class="t2">{{item.createtime}}</view>
            </view>
          </view>
        </block>
      </block>
      <block v-if="st==1">
        <view v-for="(item, index) in datalist" :key="index" class="item" style="display: flex;align-items: center ;">
          <view class="f1">
              <text class="t1">充值金额：{{item.money}}元</text>
              <text class="t2">{{item.createtime}}</text>
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
      <nodata v-if="nodata"></nodata>
		</view>
		<nomore v-if="nomore"></nomore>
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
			pre_url:app.globalData.pre_url,
      st: 0,
      datalist: [],
      pagenum: 1,
      myxianjin: 0,
      nodata: false,
      nomore: false,
			set:{},
      showstatus:[1,1,1],
      showmyxianjin:false,
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
			that.nodata = false;
			that.nomore = false;
			that.loading = true;
      app.post('ApiMy/xianjinlog', {st: that.st,pagenum: pagenum}, function (res) {
          that.loading = false;
          var data = res.data;
          that.showmyxianjin = res.showmyxianjin;
          that.myxianjin = res.myxianjin
          if (pagenum == 1) {
            that.textset = app.globalData.textset;
            uni.setNavigationBarTitle({
              title: that.t('现金') + '明细'
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
    	var a = await that.shoukuan(id,'member_xianjin_withdrawlog','');
    	console.log(a);
    	that.getdata();
    }
  }
};
</script>
<style>
.mydedamount{width:94%;margin:20rpx 3%;border-radius: 10rpx 56rpx 10rpx 10rpx;position:relative;display:flex;flex-direction:column;padding:70rpx 0}
.mydedamount .f1{margin:0 0 0 60rpx;color:rgba(255,255,255,0.8);font-size:24rpx;}
.mydedamount .f2{margin:20rpx 0 0 60rpx;color:#fff;font-size:64rpx;font-weight:bold; position: relative;}

.mydedamount .item {min-width: 50%;color: #FFFFFF;padding: 0 10%;margin-top: 30rpx;}
.mydedamount .item .label{font-size: 26rpx}
.mydedamount .item .value{font-size: 44rpx;margin-top: 10rpx;font-weight: bold;}

.mydedamount  .btn{height: 50rpx;text-align: center;border: 1px solid #e6e6e6;border-radius: 10rpx;color: #fff;font-size: 24rpx;font-weight: bold;display: inline-flex;align-items: center;justify-content: center;margin-left: 10rpx;padding: 0 10rpx;}

.content{ width:94%;margin:0 3%;}
.content .item{width:100%;margin:20rpx 0;background:#fff;border-radius:5px;padding:20rpx 20rpx;line-height: 40rpx;}
.content .item .f1{flex:1;display:flex;flex-direction:column;margin: 20rpx auto;}
.content .item .f1 .t1{color:#000000;font-size:30rpx;word-break:break-all;overflow:hidden;text-overflow:ellipsis;}
.content .item .f1 .t2{color:#666666}
.content .item .f1 .t3{color:#666666}
.content .item .f2{ flex:1;font-size:32rpx;text-align:right}
.content .item .f2 .t1{color:#03bc01}
.content .item .f2 .t2{color:#000000}
.content .item .f3{ flex:1;font-size:32rpx;text-align:right}
.content .item .f3 .t1{color:#03bc01}
.content .item .f3 .t2{color:#000000}

.data-empty{background:#fff}
.btn-mini {right: 32rpx;top: 28rpx;width: 100rpx;height: 50rpx;text-align: center;border: 1px solid #e6e6e6;border-radius: 10rpx;color: #fff;font-size: 24rpx;font-weight: bold;display: inline-flex;align-items: center;justify-content: center;position: absolute;}
.btn-xs{min-width: 100rpx;height: 50rpx;line-height: 50rpx;text-align: center;border: 1px solid #e6e6e6;border-radius: 10rpx;color: #fff;font-size: 24rpx;font-weight: bold;margin-bottom:10rpx;padding: 0 20rpx;}

.btn2{margin-left:20rpx; margin-top: 10rpx;max-width:380rpx;height:60rpx;line-height:60rpx;color:#333;background:#fff;border:1px solid #cdcdcd;border-radius:3px;text-align:center;padding: 0 20rpx;}

.btn1{height:60rpx;line-height:60rpx;color:#fff;border-radius:3px;text-align:center;padding: 0 15rpx;float: right;font-size: 14px;margin-left: 10rpx}
.data-empty{background:#fff}
</style>