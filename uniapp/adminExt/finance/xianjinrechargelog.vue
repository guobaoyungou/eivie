<template>
<view>
	<block v-if="isload">
		<view class="topsearch flex-y-center">
			<view class="f1 flex-y-center">
				<image class="img" :src="pre_url+'/static/img/search_ico.png'"></image>
				<input :value="keyword" :placeholder="'输入'+t('会员')+'昵称搜索'" placeholder-style="font-size:24rpx;color:#C2C2C2" @confirm="searchConfirm" @input="searchChange"></input>
			</view>
		</view>
		<view class="content" v-if="datalist && datalist.length>0">
			<view class="label">
				<text class="t1">充值记录（共{{count}}条）</text>
				<text class="t1" v-if="today_rechargemoney > 0">今日收款：￥{{today_rechargemoney}}</text>
			</view>
			<view v-for="(item, index) in datalist" :key="index" class="item">
				<view class="f1">
					<image class="t1" :src="item.headimg"></image>
					<text class="t2">{{item.nickname}}</text>
				</view>
				<view class="f2" style="width: 400rpx">
					<text class="t1">充值金额：{{item.money}}元</text>
					<text class="t2">{{dateFormat(item.createtime)}}</text>
					<text class="t3">支付方式：{{item.paytype}}</text>
					<text class="t3">单号：{{item.ordernum}}</text>
					<text class="t3" :style="item.status==1 ? 'color:#03bc01' : 'color:red'">状态：{{item.status_name}}</text>
				</view>
        <view class="f3" v-if="item.money_recharge_transfer && item.paytypeid == 5 && item.payorder_check_status >=0 && item.paytype !='随行付支付'">
          <view v-if="item.transfer_check == 1" >
            <text class="btn1" :style="{background:t('color1')}" @tap.stop="payCheck" :data-orderid="item.id">付款凭证</text>
          </view>
          <view v-if="item.transfer_check == 0" >
            <text class="btn1" :style="{background:t('color1')}" @tap.stop="transferCheck" :data-orderid="item.id">转账审核</text>
          </view>
          <view v-if="item.transfer_check == -1" >
            <text >转账已驳回</text>
          </view>
        </view>
			</view>
		</view>
    <!--转账审核弹框-->
    <uni-popup id="transferCheck" ref="transferCheck" type="dialog">
      <view class="uni-popup-dialog">
        <view class="uni-dialog-title">
          <text class="uni-dialog-title-text">转账审核</text>
        </view>
        <view class="uni-dialog-button-group">
          <view class="uni-dialog-button" data-st="1" @click="dotransferCheck">
            <text class="uni-dialog-button-text uni-button-color">同意可转账</text>
          </view>
          <view class="uni-dialog-button uni-border-left" data-orderid="" data-st="-1" @click="dotransferCheck">
            <text class="uni-dialog-button-text ">驳回可转账</text>
          </view>
        </view>
      </view>
    </uni-popup>
    <!--付款凭证审核弹框-->
    <uni-popup id="payCheck" ref="payCheck" type="dialog">
      <view class="uni-popup-dialog">
        <view class="uni-dialog-title" style="margin-bottom: 10rpx">
          <text class="uni-dialog-title-text">付款凭证</text>
        </view>
        <view class="uni-dialog-content flex" v-if="rechargeorder.paypics" style="padding: 0">
          <image v-for="(item1, index1) in rechargeorder.paypics" :key="index1" :src="item1" @tap="previewImage" :data-url="item1" class="img" style="width: 200rpx;height: 200rpx"/>
        </view>
        <view class="flex-y-center flex-x-lift" style="margin:20rpx 20rpx;height: 80rpx;">
          <view style="font-size:28rpx;color:#555">审核状态：{{rechargeorder.check_status_label?rechargeorder.check_status_label:''}}</view>
        </view>
        <view class="flex-y-center flex-x-center" style="margin:20rpx 20rpx;height: 80rpx;">
          <view style="font-size:28rpx;color:#555">审核备注：</view>
          <input type="text" :value="rechargeorder.check_remark?rechargeorder.check_remark:''" @input="check_remark_input" style="border: 1px #eee solid;height:70rpx;border-radius:4px;flex:1;font-size:28rpx;padding:0 10rpx"/>
        </view>
        <view class="uni-dialog-button-group">
          <view class="uni-dialog-button" :data-orderid="rechargeorder.orderid" data-st="1" @click="dopayCheck">
            <text class="uni-dialog-button-text uni-button-color">确认已支付</text>
          </view>
          <view class="uni-dialog-button uni-border-left" :data-orderid="rechargeorder.orderid" data-st="2" @click="dopayCheck">
            <text class="uni-dialog-button-text ">驳回</text>
          </view>
        </view>
      </view>
    </uni-popup>
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
      pre_url:app.globalData.pre_url,
      orderid: 0,
      rechargeorder: [],
      check_remark: '',
	  today_rechargemoney:0
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
      app.post('ApiAdminFinance/xianjinrechargelog', {keyword:keyword,pagenum: pagenum}, function (res) {
				that.loading = false;
        uni.setNavigationBarTitle({
          title: that.t('现金') +'充值记录'
        });
        var data = res.data;
        that.today_rechargemoney = res.today_rechargemoney;
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
    transferCheck:function(e){
      var that = this;
      that.$refs.transferCheck.open();
      that.orderid = e.currentTarget.dataset.orderid;
    },
    payCheck:function(e){
      var that = this;
      var orderid = e.currentTarget.dataset.orderid;
      that.check_remark = '';
      that.loading = true;
      app.post('ApiAdminFinance/getxianjinrechargeorderdetail', {orderid:orderid}, function (res) {
        that.loading = false;
        that.rechargeorder = res.payorder;
        that.$refs.payCheck.open();
      });
    },
    check_remark_input:function(e){
      this.check_remark = e.detail.value;
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
    dotransferCheck: function (e) {
      var that = this;
      var st = e.currentTarget.dataset.st;
      var orderid = this.orderid;
      that.loading = true;
      app.post('ApiAdminFinance/xianjinTransferCheck', {orderid:orderid,st: st}, function (res) {
        that.loading = false;
        if (res.status == 0){
          app.error(res.msg);return;
        }else{
          app.success(res.msg);
          that.$refs.transferCheck.close();
          that.getdata();
          that.loaded();
        }
      });
    },
    dopayCheck: function (e) {
      var that = this;
      var st = e.currentTarget.dataset.st;
      var orderid = e.currentTarget.dataset.orderid;
      that.loading = true;
      app.post('ApiAdminFinance/xianjinPayCheck', {orderid:orderid,st: st,remark:that.check_remark}, function (res) {
        that.loading = false;
        if (res.status == 0){
          app.error(res.msg);return;
        }else{
          app.success(res.msg);
          that.$refs.payCheck.close();
          that.rechargeorder = [];
          that.getdata();
          that.loaded();
        }
      });
    },
  }
};
</script>
<style>
/* @import "../common.css"; */
.topsearch{width:94%;margin:16rpx 3%;}
.topsearch .f1{height:60rpx;border-radius:30rpx;border:0;background-color:#fff;flex:1}
.topsearch .f1 .img{width:24rpx;height:24rpx;margin-left:10px}
.topsearch .f1 input{height:100%;flex:1;padding:0 20rpx;font-size:28rpx;color:#333;}

.content{width: 94%;margin:0 3%;background: #fff;border-radius:16rpx}
.content .label{display:flex;width: 100%;padding:24rpx 16rpx;color: #333;}
.content .label .t1{flex:1}
.content .label .t2{ width:300rpx;text-align:right}

.content .item{ width:100%;padding:20rpx 20rpx;border-top: 1px #f5f5f5 solid;display:flex;align-items:center}
.content .item .f1{display:flex;flex-direction:column;margin-right:20rpx}
.content .item .f1 .t1{width:100rpx;height:100rpx;margin-bottom:10rpx;border-radius:50%;margin-left:20rpx}
.content .item .f1 .t2{color:#666666;text-align:center;width:140rpx;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:1;overflow:hidden;}
.content .item .f2{ flex:1;width:200rpx;font-size:30rpx;display:flex;flex-direction:column}
.content .item .f2 .t1{color:#03bc01}
.content .item .f2 .t2{color:#999;font-size:24rpx}
.content .item .f2 .t3{color:#aaa;font-size:24rpx}
.content .item .f3{ flex:1;width:200rpx;font-size:32rpx;text-align:right}
.content .item .f3 .t1{color:#03bc01}
.content .item .f3 .t2{color:#000000}
.btn1{height:60rpx;line-height:60rpx;color:#fff;border-radius:3px;text-align:center;padding: 0 15rpx;float: right;font-size: 14px;margin-left: 10rpx}
.uni-popup-dialog {width: 300px;border-radius: 5px;background-color: #fff;}
.uni-dialog-title {display: flex;flex-direction: row;justify-content: center;padding-top: 15px;padding-bottom: 5px;}
.uni-dialog-title-text {font-size: 16px;font-weight: 500;}
.uni-dialog-content {display: flex;flex-direction: row;justify-content: center;align-items: center;padding: 5px 15px 15px 15px;}
.uni-dialog-content-text {font-size: 14px;color: #6e6e6e;}
.uni-dialog-button-group {display: flex;flex-direction: row;border-top-color: #f5f5f5;border-top-style: solid;border-top-width: 1px;}
.uni-dialog-button {display: flex;flex: 1;flex-direction: row;justify-content: center;align-items: center;height: 45px;}
.uni-border-left {border-left-color: #f0f0f0;border-left-style: solid;border-left-width: 1px;}
.uni-dialog-button-text {font-size: 14px;}
.uni-button-color {color: #007aff;}
</style>