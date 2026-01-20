<template>
<view>
	<block v-if="isload">
		<view class="orderinfo">
			<view class="item">
				<text class="t1">ID</text>
				<text class="t2">{{member.id}}</text>
			</view>
			<view class="item">
				<text class="t1">头像</text>
				<view class="t2"><image :src="member.headimg" style="width:80rpx;height:80rpx"></image></view>
			</view>
			<view class="item">
				<text class="t1">昵称</text>
				<text class="t2">{{member.nickname}}</text>
			</view>
			<view class="item">
				<text class="t1">加入时间</text>
				<text class="t2">{{member.createtime}}</text>
			</view>
			<view class="item">
				<text class="t1">{{t('余额')}}</text>
				<text class="t2">{{member.money}}</text>
			</view>
			<view class="item">
				<text class="t1">{{t('积分')}}</text>
				<text class="t2">{{member.score}}</text>
			</view>
			<view class="item">
				<text class="t1">等级</text>
				<text class="t2">{{member.levelname}}</text>
			</view>
		</view>
		<view style="width:100%;height:60rpx"></view>
		<button class="btn" @tap="openDeductDialog(1)">{{t('积分')}}消费</button>
		<button class="btn" @tap="openDeductDialog(2)" v-if="deductmoney">{{t('余额')}}消费</button>
		
		<uni-popup id="deductDialog" ref="deductDialog" type="dialog">
			<uni-popup-dialog mode="input" :title="deducttypename" value="" :placeholder="'请输入' + deducttypename + '数'" @confirm="deductConfirm"></uni-popup-dialog>
		</uni-popup>

	</block>
	<popmsg ref="popmsg"></popmsg>
	<loading v-if="loading"></loading>
</view>
</template>

<script>
var app = getApp();

export default {
  data() {
    return {
      isload: false,
      member: "",
			index2:0,
			ordershow:false,
			deductmoney:0, //扣除余额
			deducttype:1,//扣除方式 1积分 2余额
			deducttypename:'',
    };
  },
  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.getdata();
  },
	onPullDownRefresh: function () {
		this.getdata();
	},
  methods: {
		getdata:function(){
			var that = this;
			that.loading = true;
			app.post('ApiAdminMember/detail', {mid: that.opt.mid}, function (res) {
				that.loading = false;
				that.member = res.member;
				that.ordershow = res.ordershow
				uni.setNavigationBarTitle({
					title: that.t('会员') + '信息'
				});
				if(res.deductmoney){
					that.deductmoney = res.deductmoney;
				}
				that.loaded();
			});
		},
    openDeductDialog:function(type) {
      this.deducttype = type;
      this.$refs.deductDialog.open();
      this.deducttypename = this.t('积分') +'消费';
      if(type == 2){
        this.deducttypename = this.t('余额') +'消费';
      }
    },
    deductConfirm: function (done, value) {
      this.$refs.deductDialog.close();
      var that = this;
      var formdata = {
        rechargemid: that.opt.mid
      }
      var url = 'ApiAdminMember/decscore'; //扣除积分
      //扣除余额
      if(this.deducttype == 2){
        url = 'ApiAdminMember/recharge';
        formdata.rechargemoney = value * -1;
        formdata.type = 'consume';
      }else if(this.deducttype == 1){
        formdata.rechargescore = value * -1;
      }
      app.post(url, formdata, function (res) {
        if (res.status == 0) {
          app.error(res.msg);
          return;
        }
        if(res.status == 1 && that.deducttype == 2){
          app.success(res.msg);
          setTimeout(function () {
              app.goto('/admin/index/index','reLaunch')
          }, 2000);
        }
        // app.success(res.msg);
      });
    },
  }
};
</script>
<style>
.orderinfo{ width:94%;margin:20rpx 3%;border-radius:16rpx;padding: 14rpx 3%;background: #FFF;}
.orderinfo .item{display:flex;width:100%;padding:20rpx 0;border-bottom:1px dashed #ededed;}
.orderinfo .item:last-child{ border-bottom: 0;}
.orderinfo .item .t1{width:200rpx;}
.orderinfo .item .t2{flex:1;text-align:right}
.orderinfo .item .red{color:red}

.btn{ height: 88rpx;line-height: 88rpx;background: #FC4343;width:90%;margin:0 auto;border-radius:8rpx;margin-top:60rpx;color: #fff;font-size: 36rpx;}

.bottom{ width: 100%; padding: 16rpx 20rpx;background: #fff; position: fixed; bottom: 0px; left: 0px;display:flex;justify-content:flex-end;align-items:center;}
.bottom .btn{ border-radius:10rpx; padding:10rpx 16rpx;margin-left: 10px;border: 1px #999 solid;}


.uni-popup-dialog {width: 300px;border-radius: 5px;background-color: #fff;}
.uni-dialog-title {display: flex;flex-direction: row;justify-content: center;padding-top: 15px;padding-bottom: 5px;}
.uni-dialog-title-text {font-size: 16px;font-weight: 500;}
.uni-dialog-content {display: flex;flex-direction: row;justify-content: center;align-items: center;padding: 5px 15px 15px 15px;width:100%}
.uni-dialog-content-text {font-size: 14px;color: #6e6e6e;}
.uni-dialog-button-group {display: flex;flex-direction: row;border-top-color: #f5f5f5;border-top-style: solid;border-top-width: 1px;}
.uni-dialog-button {display: flex;flex: 1;flex-direction: row;justify-content: center;align-items: center;height: 45px;}
.uni-border-left {border-left-color: #f0f0f0;border-left-style: solid;border-left-width: 1px;}
.uni-dialog-button-text {font-size: 14px;}
.uni-button-color {color: #007aff;}
</style>