<template>
<view>
	<block v-if="isload">
	<view class="contentdata">
    <!--  #ifdef MP-WEIXIN -->
    <view style="color: red;font-size: 24rpx">
      您当前可接收以下类型通知（根据微信规则，每种类型需单独授权，每次点授权可获得1次对应类型推送次数，可无限次数点击）
    </view>
    <view style="color: #999;font-size: 24rpx;margin-top: 10rpx">
      *若此操作方式不便利，可开启公众号模版消息推送，开通要求：已认证的微信服务号。
    </view>
    <!--  #endif -->
		<view class="list">
			<view v-if="uinfo.tmpl_orderconfirm_show==1">
				<view class="item" style="border-top: 0">
					<view class="f2">订单提交通知</view>
					<view class="f3"><switch value="1" :checked="uinfo.tmpl_orderconfirm==1?true:false" @change="switchchange" data-type="tmpl_orderconfirm"></switch></view>
				</view>
				<!--  #ifdef MP-WEIXIN -->
				<view class="" style="font-size:24rpx;margin-bottom:10rpx;display: flex" >
          <text style="padding-top: 6rpx">剩余消息通知：</text>
          <text style="color:#FC5648;font-weight:bold;font-size:30rpx">{{uinfo.tmpl_orderconfirmNum}}</text>
          <text style="padding-top: 6rpx">次，续订消息通知点击：</text>
          <text class="data_btn flex-xy-center" :style="{background:'linear-gradient(90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'}" @tap="addsubnum" :data-tmplid="wxtmplset.tmpl_orderconfirm">授权</text>
        </view>
        <view style="font-size:24rpx;margin-bottom:20rpx" >每收到一次消息推送通知，消耗1次授权次数</view>
				<!--  #endif -->
			</view>
			<view v-if="uinfo.tmpl_orderpay_show==1">
				<view class="item">
					<view class="f2">订单支付通知</view>
					<view class="f3"><switch value="1" :checked="uinfo.tmpl_orderpay==1?true:false" @change="switchchange" data-type="tmpl_orderpay"></switch></view>
				</view>
				<!--  #ifdef MP-WEIXIN -->
        <view class="" style="font-size:24rpx;margin-bottom:10rpx;display: flex" >
          <text style="padding-top: 6rpx">剩余消息通知：</text>
          <text style="color:#FC5648;font-weight:bold;font-size:30rpx">{{uinfo.tmpl_orderpayNum}}</text>
          <text style="padding-top: 6rpx">次，续订消息通知点击：</text>
          <text class="data_btn flex-xy-center" :style="{background:'linear-gradient(90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'}" @tap="addsubnum" :data-tmplid="wxtmplset.tmpl_orderpay">授权</text>
        </view>
        <view style="font-size:24rpx;margin-bottom:20rpx" >每收到一次消息推送通知，消耗1次授权次数</view>
				<!--  #endif -->
			</view>
			<view v-if="uinfo.tmpl_ordershouhuo_show==1">
				<view class="item">
					<view class="f2">订单收货通知</view>
					<view class="f3"><switch value="1" :checked="uinfo.tmpl_ordershouhuo==1?true:false" @change="switchchange" data-type="tmpl_ordershouhuo"></switch></view>
				</view>
				<!--  #ifdef MP-WEIXIN -->
        <view class="" style="font-size:24rpx;margin-bottom:10rpx;display: flex" >
          <text style="padding-top: 6rpx">剩余消息通知：</text>
          <text style="color:#FC5648;font-weight:bold;font-size:30rpx">{{uinfo.tmpl_ordershouhuoNum}}</text>
          <text style="padding-top: 6rpx">次，续订消息通知点击：</text>
          <text class="data_btn flex-xy-center" :style="{background:'linear-gradient(90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'}" @tap="addsubnum" :data-tmplid="wxtmplset.tmpl_ordershouhuo">授权</text>
        </view>
        <view style="font-size:24rpx;margin-bottom:20rpx" >每收到一次消息推送通知，消耗1次授权次数</view>
				<!--  #endif -->
			</view>
			<view v-if="uinfo.tmpl_ordertui_show==1">
				<view class="item">
					<view class="f2">退款申请通知</view>
					<view class="f3"><switch value="1" :checked="uinfo.tmpl_ordertui==1?true:false" @change="switchchange" data-type="tmpl_ordertui"></switch></view>
				</view>
				<!--  #ifdef MP-WEIXIN -->
        <view class="" style="font-size:24rpx;margin-bottom:10rpx;display: flex" >
          <text style="padding-top: 6rpx">剩余消息通知：</text>
          <text style="color:#FC5648;font-weight:bold;font-size:30rpx">{{uinfo.tmpl_ordertuiNum}}</text>
          <text style="padding-top: 6rpx">次，续订消息通知点击：</text>
          <text class="data_btn flex-xy-center" :style="{background:'linear-gradient(90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'}" @tap="addsubnum" :data-tmplid="wxtmplset.tmpl_ordertui">授权</text>
        </view>
        <view style="font-size:24rpx;margin-bottom:20rpx" >每收到一次消息推送通知，消耗1次授权次数</view>
				<!--  #endif -->
			</view>
			<view v-if="uinfo.tmpl_withdraw_show==1">
				<view class="item">
					<view class="f2">提现申请通知</view>
					<view class="f3"><switch value="1" :checked="uinfo.tmpl_withdraw==1?true:false" @change="switchchange" data-type="tmpl_withdraw"></switch></view>
				</view>
				<!--  #ifdef MP-WEIXIN -->
        <view class="" style="font-size:24rpx;margin-bottom:10rpx;display: flex" >
          <text style="padding-top: 6rpx">剩余消息通知：</text>
          <text style="color:#FC5648;font-weight:bold;font-size:30rpx">{{uinfo.tmpl_withdrawNum}}</text>
          <text style="padding-top: 6rpx">次，续订消息通知点击：</text>
          <text class="data_btn flex-xy-center" :style="{background:'linear-gradient(90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'}" @tap="addsubnum" :data-tmplid="wxtmplset.tmpl_withdraw">授权</text>
        </view>
        <view style="font-size:24rpx;margin-bottom:20rpx" >每收到一次消息推送通知，消耗1次授权次数</view>
				<!--  #endif -->
			</view>
			<view class="item" v-if="uinfo.tmpl_formsub_show==1">
				<view class="f2">表单提交通知</view>
				<view class="f3"><switch value="1" :checked="uinfo.tmpl_formsub==1?true:false" @change="switchchange" data-type="tmpl_formsub"></switch></view>
			</view>
			<view v-if="uinfo.tmpl_kehuzixun_show==1">
				<view class="item">
					<view class="f2">用户咨询通知</view>
					<view class="f3"><switch value="1" :checked="uinfo.tmpl_kehuzixun==1?true:false" @change="switchchange" data-type="tmpl_kehuzixun"></switch></view>
				</view>
				<!--  #ifdef MP-WEIXIN -->
        <view class="" style="font-size:24rpx;margin-bottom:10rpx;display: flex" >
          <text style="padding-top: 6rpx">剩余消息通知：</text>
          <text style="color:#FC5648;font-weight:bold;font-size:30rpx">{{uinfo.tmpl_kehuzixunNum}}</text>
          <text style="padding-top: 6rpx">次，续订消息通知点击：</text>
          <text class="data_btn flex-xy-center" :style="{background:'linear-gradient(90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'}" @tap="addsubnum" :data-tmplid="wxtmplset.tmpl_kehuzixun">授权</text>
        </view>
        <view style="font-size:24rpx;margin-bottom:20rpx" >每收到一次消息推送通知，消耗1次授权次数</view>
				<!--  #endif -->
			</view>
			<view v-if="uinfo.tmpl_maidanpay_show==1">
				<view class="item">
					<view class="f2">买单付款通知</view>
					<view class="f3"><switch value="1" :checked="uinfo.tmpl_maidanpay==1?true:false" @change="switchchange" data-type="tmpl_maidanpay"></switch></view>
				</view>
				<!--  #ifdef MP-WEIXIN -->
        <view class="" style="font-size:24rpx;margin-bottom:10rpx;display: flex" >
          <text style="padding-top: 6rpx">剩余消息通知：</text>
          <text style="color:#FC5648;font-weight:bold;font-size:30rpx">{{uinfo.tmpl_orderconfirmNum}}</text>
          <text style="padding-top: 6rpx">次，续订消息通知点击：</text>
          <text class="data_btn flex-xy-center" :style="{background:'linear-gradient(90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'}" @tap="addsubnum" :data-tmplid="wxtmplset.tmpl_orderconfirm">授权</text>
        </view>
        <view style="font-size:24rpx;margin-bottom:20rpx" >每收到一次消息推送通知，消耗1次授权次数</view>
				<!--  #endif -->
			</view>
			<view v-if="uinfo.tmpl_stockwarning_show==1">
				<view class="item">
					<view class="f2">库存不足通知</view>
					<view class="f3"><switch value="1" :checked="uinfo.tmpl_stockwarning==1?true:false" @change="switchchange" data-type="tmpl_stockwarning"></switch></view>
				</view>
				<!--  #ifdef MP-WEIXIN -->
        <view class="" style="font-size:24rpx;margin-bottom:10rpx;display: flex" >
          <text style="padding-top: 6rpx">剩余消息通知：</text>
          <text style="color:#FC5648;font-weight:bold;font-size:30rpx">{{uinfo.tmpl_stockwarningNum}}</text>
          <text style="padding-top: 6rpx">次，续订消息通知点击：</text>
          <text class="data_btn flex-xy-center" :style="{background:'linear-gradient(90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'}" @tap="addsubnum" :data-tmplid="wxtmplset.tmpl_stockwarning">授权</text>
        </view>
        <view style="font-size:24rpx;margin-bottom:10rpx" >每收到一次消息推送通知，消耗1次授权次数</view>
				<!--  #endif -->
			</view>
			
			<view v-if="uinfo.tmpl_hotelbooking_success_show==1">
				<view class="item">
					<view class="f2">客户预定成功通知</view>
					<view class="f3"><switch value="1" :checked="uinfo.tmpl_hotelbooking_success==1?true:false" @change="switchchange" data-type="tmpl_hotelbooking_success"></switch></view>
				</view>
				<!--  #ifdef MP-WEIXIN -->
        <view class="" style="font-size:24rpx;margin-bottom:10rpx;display: flex" >
          <text style="padding-top: 6rpx">剩余消息通知：</text>
          <text style="color:#FC5648;font-weight:bold;font-size:30rpx">{{uinfo.tmpl_hotelbookingNum}}</text>
          <text style="padding-top: 6rpx">次，续订消息通知点击：</text>
          <text class="data_btn flex-xy-center" :style="{background:'linear-gradient(90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'}" @tap="addsubnum" :data-tmplid="wxtmplset.tmpl_hotelbooking_success">授权</text>
        </view>
        <view style="font-size:24rpx;margin-bottom:10rpx" >每收到一次消息推送通知，消耗1次授权次数</view>
				<!--  #endif -->
			</view>
			
		</view>

    <!--后台没有配置模版弹框弹窗-->
    <view id="mask-rule" v-if="nopctk">
      <view class="box-rule" style="margin: 50% auto;height:480rpx;background-color: #fff;padding: 40rpx 0 10rpx 0;">
        <view class="h2" style="color: #1A1A1A;font-size: 43rpx">未响应</view>
        <view class="con" style="color: unset;height: 56%;margin-top: 38rpx" >
          <view class="text" style="padding-top: 70rpx">
            <view style="font-size: 34rpx">请前往PC管理端检查订阅消息配置</view>
            <view style="margin-top: 20rpx">平台 - 微信小程序 - 订阅消息配置</view>
          </view>
        </view>
        <view style="width: 100%;text-align: center;display: flex;justify-content: center;">
          <view class="zdl" style="" @tap="closenopctk">知道了</view>
        </view>
      </view>
    </view>

    <!--用户关闭了消息通知弹窗-->
    <view id="mask-rule" v-if="nomestk" style="text-align: center;display: flex;justify-content: center;background-color: rgba(0, 0, 0);">
      <view style="color: #FF8E3F;margin-top: 130rpx;font-size: 40rpx;">您未开启小程序订阅消息通知</view>
      <view class="nomestk_bg" :style="'background:url(' + pre_url + '/static/img/kqxxts.png) no-repeat;background-size:100% 100%;'" @tap="closenomestk"></view>
    </view>

	</view>
	</block>
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
			set:{},
			uinfo:{},
      count0: 0,
      count1: 0,
      count2: 0,
      count3: 0,
      count4: 0,
      seckillCount: 0,
      collageCount: 0,
      kanjiaCount: 0,
			tuangouCount:0,
      scoreshopCount: 0,
      maidanCount: 0,
      productCount: 0,
			cycleCount: 0,
      hexiaoCount: 0,
			formlogCount:0,
      auth_data: {},
			showshoporder:false,
			showbusinessqr:false,
			showyuyueorder:false,
			showcollageorder:false,
			showkanjiaorder:false,
			showseckillorder:false,
			showscoreshoporder:false,
			showluckycollageorder:false,
			showtuangouorder:false,
			showyuekeorder:false,
			showmaidanlog:false,
			showformlog:false,
			showworkorder:false,
			showworkadd:false,
			workorderCount:0,
			showrecharge:false,
			wxtmplset:{},
			searchmember:false,
			showCycleorder:false,
			scoreshop_product:false,
      nopctk:false,//后台没有配置模版弹框
      nomestk:false,//用户关闭了消息通知弹框
			scoreproductCount:0,
			custom:{},//定制显示控制放一起
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
			app.get('ApiAdminIndex/index', {}, function (res) {
				that.loading = false;
				that.set = res.set;
				that.wxtmplset = res.wxtmplset;
				that.uinfo = res.uinfo;
				that.count0 = res.count0;
				that.count1 = res.count1;
				that.count2 = res.count2;
				that.count3 = res.count3;
				that.count4 = res.count4;
				that.seckillCount = res.seckillCount;
				that.collageCount = res.collageCount;
				that.cycleCount = res.cycleCount;
				that.luckycollageCount = res.luckycollageCount;
				that.kanjiaCount = res.kanjiaCount;
				that.tuangouCount = res.tuangouCount;
				that.scoreshopCount = res.scoreshopCount;
				that.yuyueCount = res.yuyueCount;
				that.maidanCount = res.maidanCount;
				that.productCount = res.productCount;
				that.hexiaoCount = res.hexiaoCount;
				that.formlogCount = res.formlogCount;
				that.auth_data = res.auth_data;
				that.showbusinessqr = res.showbusinessqr;
				that.workordercount = res.workordercount;
				that.showshoporder = res.showshoporder;
				that.showcollageorder = res.showcollageorder;
				that.showCycleorder = res.showCycleorder;
				that.showkanjiaorder = res.showkanjiaorder;
				that.showseckillorder = res.showseckillorder;
				that.showtuangouorder = res.showtuangouorder;
				that.showscoreshoporder = res.showscoreshoporder;
				that.showluckycollageorder = res.showluckycollageorder;
				that.showmaidanlog = res.showmaidanlog;
				that.showformlog = res.showformlog;
				that.showworkorder = res.showworkorder;
				that.showworkadd = res.showworkadd;
				that.showyuyueorder = res.showyuyueorder;
				that.showrecharge = res.showrecharge;
				that.searchmember = res.searchmember;
				that.showyuekeorder = res.showyuekeorder;
				that.scoreshop_product = res.scoreshop_product || false;
				that.scoreproductCount = res.scoreproductCount || 0;
				that.custom = res.custom
				that.loaded();
			});
		},
    switchchange: function (e) {
      console.log(e);
      var field = e.currentTarget.dataset.type;
      var value = e.detail.value ? 1 : 0;
      app.post('ApiAdminIndex/setusertmpl', {
        field: field,
        value: value
      }, function (data) {});
    },
		switchOpen: function (e) {
      var field = e.currentTarget.dataset.type;
      var value = e.detail.value ? 0 : 1;
      app.post('ApiAdminIndex/setField', {
        field: field,
        value: value
      }, function (data) {});
    },
		addsubnum:function(e){	
      var that = this;
			that.tmplids = [e.currentTarget.dataset.tmplid];
			console.log(that.tmplids);
			that.subscribeMessage(function (e) {
        if(e.errMsg != 'requestSubscribeMessage:ok' && e.errCode == 20004){
          //用户关闭了主开关，无法进行订阅 --用户关闭了消息通知
          that.nomestk = true;
          return;
        }else if(e.errMsg != 'requestSubscribeMessage:ok' && e.errCode == 20001){
          //没有模板数据，一般是模板 ID 不存在 或者和模板类型不对应 导致的  --后台没有配置模版
          that.nopctk = true;
          return;
        }
				that.getdata();
			});
    },
    closenopctk: function () {
      var that = this;
      that.nopctk = false;
    },
    closenomestk: function () {
      var that = this;
      that.nomestk = false;
    }
  }
};
</script>
<style>
@import "../common.css";
page {
    padding-bottom: env(safe-area-inset-bottom);
}
.contentdata{display:flex;flex-direction:column;width:100%;padding:0 30rpx;position:relative;margin-bottom:20rpx}
.money{ display:flex;width:100%;align-items:center;padding:10rpx 20rpx;background:#fff;}
.money .f1{flex:auto}
.money .f1 .t2{color:#ff3300}
.money .f2{ background:#fff;color:#ff3300;border:1px solid #ff3300;height:50rpx;line-height:50rpx;padding:0 14rpx;font-size: 28rpx;}

.score{ display:flex;width:100%;align-items:center;padding:10rpx 20rpx;background:#fff;border-top:1px dotted #eee}
.score .f1 .t2{color:#ff3300}

.agent{width:100%;background:#fff;padding:0px 20rpx;margin-top:20rpx}
.agent .head{ display:flex;align-items:center;width:100%;padding:10rpx 0;border-bottom:1px solid #eee}
.agent .head .f1{flex:auto;}
.agent .head .f2{ display:flex;align-items:center;color:#999;width:200rpx;padding:10rpx 0;text-align:right;justify-content:flex-end}
.agent .head .f2 image{ width:30rpx;height:30rpx;}
.agent .head .t3{ width: 40rpx; height: 40rpx;}
.agent .content{ display:flex;width:100%;padding:10rpx 0;align-items:center;font-size:24rpx}
.agent .content .item{padding:10rpx 0;flex:1;display:flex;flex-direction:column;align-items:center;position:relative}
.agent .content .item image{ width:50rpx;height:50rpx}
.agent .content .item .t3{ padding-top:3px}
.agent .content .item .t2{background: red;color: #fff;border-radius:50%;padding: 0 10rpx;position: absolute;top: 0px;right:40rpx;}

.list{ width: 100%;background: #fff;margin-top:20rpx;padding:0 20rpx;font-size:30rpx;border-radius:16rpx}
.list .item{ height:100rpx;display:flex;align-items:center;border-bottom:0px solid #eee}
.list .item:last-child{border-bottom:0;}
.list .f1{width:50rpx;height:50rpx;line-height:50rpx;display:flex;align-items:center;}
.list .f1 image{ width:40rpx;height:40rpx;}
.list .f1 span{ width:40rpx;height:40rpx;font-size:40rpx}
.list .f2{color:#222;font-weight:bold;}
.list .f3{ color: #FC5648;text-align:right;flex:1;}
.list .f4{ width: 24rpx; height: 24rpx;}
.list .item{ border-top: 1px solid #eee}
switch{transform:scale(.7);}

#mask-rule,#mask {position: fixed;left: 0;top: 0;z-index: 999;width: 100%;height: 100%;background-color: rgba(0, 0, 0, 0.85);}
#mask-rule .box-rule {position: relative;margin: 30% auto;padding-top: 40rpx;width: 85%;height: 675rpx;border-radius: 50rpx;background-color: #f58d40;}
#mask-rule .box-rule .star {position: absolute;left: 50%;top: -100rpx;margin-left: -130rpx;width: 259rpx;height:87rpx;}
#mask-rule .box-rule .h2 {width: 100%;text-align: center;line-height: 34rpx;font-size: 34rpx;font-weight: normal;color: #fff;}
#mask-rule #close-rule {position: absolute;right: 34rpx;top: 38rpx;width: 40rpx;height: 40rpx;}
/*内容盒子*/
#mask-rule .con {overflow: auto;position: relative;margin: auto;padding-right: 15rpx;width: 580rpx;height: 63%;line-height: 48rpx;font-size: 26rpx;color: #fff;}
#mask-rule .con .text {position: absolute;top: 0;left: 0;width: inherit;height: auto;text-align: center;justify-content: center;}

.data_btn{width:120rpx;height: 47rpx;padding: 0 30rpx;font-size: 24rpx;color: #fff;font-weight: normal;border-radius: 100rpx}
.zdl{width:220rpx;height: 60rpx;padding: 12rpx 30rpx;font-size: 27rpx;background-color: #FF8200;color: #FFFFFF;border-radius: 100rpx;margin-top: 40rpx}
.nomestk_bg{width:75%;height: 65%;top: 20%;position: absolute;}
</style>