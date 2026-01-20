<template>
<view class="container">
	<block v-if="isload">
		<view class="mymoney" :style="{background:t('color1')}">
			<view class="f1">我的{{t('现金')}}</view>
			<view class="f2"><text style="font-size:26rpx">￥</text>{{userinfo.xianjin}}</view>
			<view class="f3" @tap="goto" data-url="xianjinlog?st=1"><text>充值记录</text><text class="iconfont iconjiantou" style="font-size:20rpx"></text></view>
		</view>
		<view class="content2">
			<view class="item2" v-if="caninput==1 || giveset.length>0"><view class="f1">充值金额({{getunit('现金单位')}})</view></view>
			<block v-if="caninput==1">
				<view class="item3"><view class="f1">￥</view><view class="f2"><input type="digit" name="money" :value="money" placeholder="请输入充值金额" placeholder-style="color:#999;font-size:40rpx" @input="moneyinput" style="font-size:60rpx"/></view></view>
			</block>
			<view class="giveset" v-if="giveset.length>0">
				<view v-for="(item, index) in giveset" :key="index" v-if="item.money > 0" class="item" :class="moneyduan==item.money?'active':''" :style="moneyduan==item.money?'background:'+t('color1'):''" @tap="selectgiveset" :data-money="item.money">
					<text class="t1">{{caninput==1?'满':'充'}}{{item.money}}{{getunit('现金单位')}}</text>
					<text class="t2" v-if="item.give && item.give_score">赠{{item.give}}{{getunit('现金单位')}}+{{item.give_score}}{{t('积分')}}</text>
					<text class="t2" v-else-if="item.give && !item.give_score">赠送{{item.give}}{{getunit('现金单位')}}</text>
					<text class="t2" v-else-if="!item.give && item.give_score">赠送{{item.give_score}}{{t('积分')}}</text>
				</view>
			</view>
      <view class="item2" v-if="recharge_minimum && recharge_minimum>0">
        <view class="f1">最低充值{{recharge_minimum}}元</view>
      </view>
			<view style="padding:20rpx 30rpx;line-height:42rpx;" v-if="shuoming">
				<parse :content="shuoming" @navigate="navigate"></parse>
			</view>
		</view>
		<view class="op">
			<view class="btn" @tap="topay" :style="{background:t('color1')}">去支付</view>
		</view>

    <view class="op" v-if="transfer">
    	<view class="btn" @tap="goto" data-url="rechargeToMember" :style="{background:t('color2')}">转账</view>
    </view>
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
		pre_url: app.globalData.pre_url,
			opt:{},
			loading:false,
      isload: false,
			menuindex:-1,
			
			textset:{},
			canrecharge:0,
      userinfo: {},
			giveset:[],
			shuoming:'',
      money: '',
      moneyduan: 0,
      give_coupon_list: "",
      give_coupon_show: false,
      give_coupon_close_url: "",
			caninput:1,
			transfer:false,
      recharge_minimum:0,//充值门槛，最低充值额度
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
			app.loading = true;
			app.get('ApiMoney/xianjinrecharge', {}, function (res) {
				app.loading = false;
        if(res.status == 1){
          if (res.canrecharge == 0) {
          	app.goto('xianjinlog?st=0', 'redirect');
          	return;
          }
          that.isload = true;
          that.textset = app.globalData.textset;
          uni.setNavigationBarTitle({
          	title: that.t('现金') + '充值'
          });
          that.canrecharge = res.canrecharge;
          that.giveset = res.giveset;
          that.caninput = res.caninput;
          that.shuoming = res.shuoming;
          that.userinfo = res.userinfo;
          that.transfer = res.transfer;
          if(that.canrecharge == 1 && that.caninput == 0 && (!that.giveset || that.giveset.length == 0)){
          	//既不能输入，后台又没有设置
          	app.alert('后台未设置充值金额也未开启输入金额，请联系客服');return;
          }
          if(res.recharge_minimum){
            that.recharge_minimum = res.recharge_minimum;//充值门槛，最低充值额度
          }
          that.loaded();
        }else{
          app.alert(res.msg);
          return;
        }
			});
		},
    moneyinput: function (e) {
      var money = e.detail.value;
      var giveset = this.giveset;

      if (parseFloat(money) < 0) {
        app.error('必须大于0');
      } else {
        var moneyduan = 0;
        if (giveset.length > 0) {
          for (var i in giveset) {
            if (money * 1 >= giveset[i]['money'] * 1 && giveset[i]['money'] * 1 > moneyduan) {
              moneyduan = giveset[i]['money'] * 1;
            }
          }
        }
        this.money = money;
        this.moneyduan = moneyduan;
      }
    },
    selectgiveset: function (e) {
      var money = e.currentTarget.dataset.money;
      this.money = money;
      this.moneyduan = money;
    },
    topay: function (e) {
      var that = this;
      var money = that.money;
			var paytype = e.currentTarget.dataset.paytype;
			that.loading = true;
			
      app.post('ApiMoney/xianjinrecharge',{money: money},function (res) {
				that.loading = false;
        if (res.status == 0) {
          app.error(res.msg);
          return;
        }
				app.goto('/pagesExt/pay/pay?id='+res.payorderid);
      });
    },
    getunit:function(text=''){
      var rtext = this.t(text);
      if(rtext =='佣金单位' || rtext =='现金单位'){
        rtext = '元';
      }
      return rtext;
    },
  }
};
</script>
<style>
.container{display:flex;flex-direction:column}
.mymoney{width:94%;margin:20rpx 3%;border-radius: 10rpx 56rpx 10rpx 10rpx;position:relative;display:flex;flex-direction:column;padding:70rpx 0}
.mymoney .f1{margin:0 0 0 60rpx;color:rgba(255,255,255,0.8);font-size:24rpx;}
.mymoney .f2{margin:20rpx 0 0 60rpx;color:#fff;font-size:64rpx;font-weight:bold}
.mymoney .f3{height:56rpx;padding:0 10rpx 0 20rpx;border-radius: 28rpx 0px 0px 28rpx;background:rgba(255,255,255,0.2);font-size:20rpx;font-weight:bold;color:#fff;display:flex;align-items:center;position:absolute;top:94rpx;right:0}
.content2{width:94%;margin:10rpx 3%;border-radius:10rpx;display:flex;flex-direction:column;background:#fff}
.content2 .item1{display:flex;width:100%;border-bottom:1px solid #F0F0F0;padding:0 30rpx}
.content2 .item1 .f1{flex:1;font-size:32rpx;color:#333333;font-weight:bold;height:120rpx;line-height:120rpx}
.content2 .item1 .f2{color:#FC4343;font-size:44rpx;font-weight:bold;height:120rpx;line-height:120rpx}

.content2 .item2{display:flex;width:100%;padding:0 30rpx}
.content2 .item2 .f1{height:120rpx;line-height:120rpx;color:#999999;font-size:28rpx}

.content2 .item3{display:flex;width:100%;padding:0 30rpx;border-bottom:1px solid #F0F0F0;}
.content2 .item3 .f1{height:120rpx;line-height:120rpx;font-size:60rpx;color:#333333;font-weight:bold;margin-right:20rpx}
.content2 .item3 .f2{display:flex;align-items:center;font-size:60rpx;color:#333333;font-weight:bold}
.content2 .item3 .f2 input{height:120rpx;line-height:120rpx;}

.op{width:96%;margin:20rpx 2%;display:flex;align-items:center;margin-top:40rpx}
.op .btn{flex:1;height:100rpx;line-height:100rpx;background:#07C160;width:90%;margin:0 10rpx;border-radius:10rpx;color: #fff;font-size:28rpx;font-weight:bold;display:flex;align-items:center;justify-content:center}
.op .btn .img{width:48rpx;height:48rpx;margin-right:20rpx}

.giveset{width:100%;padding:20rpx 0rpx;display:flex;flex-wrap:wrap;justify-content:center}
.giveset .item{margin:10rpx;padding:15rpx 0;width:210rpx;height:120rpx;background:#FDF6F6;border-radius:10rpx;display:flex;flex-direction:column;align-items:center;justify-content:center}
.giveset .item .t1{color:#545454;font-size:32rpx;}
.giveset .item .t2{color:#8C8C8C;font-size:20rpx;margin-top:6rpx}
.giveset .item.active .t1{color:#fff;font-size:32rpx}
.giveset .item.active .t2{color:#fff;font-size:20rpx}
</style>