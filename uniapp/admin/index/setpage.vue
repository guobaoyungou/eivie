<template>
<view>
	<block v-if="isload">
	<view class="contentdata">
		<view class="list">
			<view class="item">
					<view class="head-view">
						<view class="avat-view"  @tap="goto" :data-url="uinfo.bid==0 ? '/pages/index/index' : '/pagesExt/business/index?id='+uinfo.bid">
							<view class="avat-img-view"><image :src="set.logo"></image></view>
							<view class="user-info">
								 <text class="un-text">{{uinfo.un}}</text>
							</view>
						</view>
						<view class="flex flex-y-center">
							<view v-if="showrecharge" class="recharge" @tap="goto" data-url="recharge">充值</view>
							<image :src="pre_url+'/static/img/arrowright.png'" class="f4"></image>
						</view>
					</view>
			</view>
		</view>
		<view class="list" v-if="false">
			<view v-if="uinfo.tmpl_orderconfirm_show==1">
				<view class="item">
					<view class="f2">订单提交通知</view>
					<view class="f3"><switch value="1" :checked="uinfo.tmpl_orderconfirm==1?true:false" @change="switchchange" data-type="tmpl_orderconfirm"></switch></view>
				</view>
				<!--  #ifdef MP-WEIXIN -->
				<view style="color:#999;font-size:24rpx;margin-bottom:10rpx" @tap="addsubnum" :data-tmplid="wxtmplset.tmpl_orderconfirm">剩余可接收次数：<text style="color:#FC5648;font-weight:bold;font-size:30rpx">{{uinfo.tmpl_orderconfirmNum}}</text>，每点击此处一次可增加一次机会</view>
				<!--  #endif -->
			</view>
			<view v-if="uinfo.tmpl_orderpay_show==1">
				<view class="item">
					<view class="f2">订单支付通知</view>
					<view class="f3"><switch value="1" :checked="uinfo.tmpl_orderpay==1?true:false" @change="switchchange" data-type="tmpl_orderpay"></switch></view>
				</view>
				<!--  #ifdef MP-WEIXIN -->
				<view style="color:#999;font-size:24rpx;margin-bottom:10rpx" @tap="addsubnum" :data-tmplid="wxtmplset.tmpl_orderconfirm">剩余可接收次数：<text style="color:#FC5648;font-weight:bold;font-size:30rpx">{{uinfo.tmpl_orderconfirmNum}}</text>，每点击此处一次可增加一次机会</view>
				<!--  #endif -->
			</view>
			<view v-if="uinfo.tmpl_ordershouhuo_show==1">
				<view class="item">
					<view class="f2">订单收货通知</view>
					<view class="f3"><switch value="1" :checked="uinfo.tmpl_ordershouhuo==1?true:false" @change="switchchange" data-type="tmpl_ordershouhuo"></switch></view>
				</view>
				<!--  #ifdef MP-WEIXIN -->
				<view style="color:#999;font-size:24rpx;margin-bottom:10rpx" @tap="addsubnum" :data-tmplid="wxtmplset.tmpl_ordershouhuo">剩余可接收次数：<text style="color:#FC5648;font-weight:bold;font-size:30rpx">{{uinfo.tmpl_ordershouhuoNum}}</text>，每点击此处一次可增加一次机会</view>
				<!--  #endif -->
			</view>
			<view v-if="uinfo.tmpl_ordertui_show==1">
				<view class="item">
					<view class="f2">退款申请通知</view>
					<view class="f3"><switch value="1" :checked="uinfo.tmpl_ordertui==1?true:false" @change="switchchange" data-type="tmpl_ordertui"></switch></view>
				</view>
				<!--  #ifdef MP-WEIXIN -->
				<view style="color:#999;font-size:24rpx;margin-bottom:10rpx" @tap="addsubnum" :data-tmplid="wxtmplset.tmpl_ordertui">剩余可接收次数：<text style="color:#FC5648;font-weight:bold;font-size:30rpx">{{uinfo.tmpl_ordertuiNum}}</text>，每点击此处一次可增加一次机会</view>
				<!--  #endif -->
			</view>
			<view v-if="uinfo.tmpl_withdraw_show==1">
				<view class="item">
					<view class="f2">提现申请通知</view>
					<view class="f3"><switch value="1" :checked="uinfo.tmpl_withdraw==1?true:false" @change="switchchange" data-type="tmpl_withdraw"></switch></view>
				</view>
				<!--  #ifdef MP-WEIXIN -->
				<view style="color:#999;font-size:24rpx;margin-bottom:10rpx" @tap="addsubnum" :data-tmplid="wxtmplset.tmpl_withdraw">剩余可接收次数：<text style="color:#FC5648;font-weight:bold;font-size:30rpx">{{uinfo.tmpl_withdrawNum}}</text>，每点击此处一次可增加一次机会</view>
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
				<view style="color:#999;font-size:24rpx;margin-bottom:30rpx" @tap="addsubnum" :data-tmplid="wxtmplset.tmpl_kehuzixun">剩余可接收次数：<text style="color:#FC5648;font-weight:bold;font-size:30rpx">{{uinfo.tmpl_kehuzixunNum}}</text>，每点击此处一次可增加一次机会</view>
				<!--  #endif -->
			</view>
		</view>
		<view class="list">
			<view class="item" v-if="uinfo.bid>0">
				<view class="f2">店铺休息</view>
				<view class="f3"><switch value="1" :checked="set.is_open==0?true:false" @change="switchOpen" data-type="is_open"></switch></view>
			</view>
			<view v-if="uinfo.bid>0" style="color:#999;font-size:24rpx;margin-bottom:30rpx" >休息时不接单</view>
			<view class="item" @tap="goto" data-url="setinfo" v-if="uinfo.bid>0">
				<view class="f2">店铺设置</view>
				<text class="f3"></text>
				<image :src="pre_url+'/static/img/arrowright.png'" class="f4"></image>
			</view>
			<view class="item" @tap="goto" data-url="/adminExt/business/setnewscore" v-if="custom.setnewscore==1">
				<view class="f2">{{t('新积分')}}活动设置</view>
				<text class="f3"></text>
				<image :src="pre_url+'/static/img/arrowright.png'" class="f4"></image>
			</view>
			<view class="item" @tap="goto" data-url="/adminExt/set/setchannels" v-if="custom.business_channels">
				<view class="f2">视频号</view>
				<text class="f3"></text>
				<image :src="pre_url+'/static/img/arrowright.png'" class="f4"></image>
			</view>
			<view v-if="custom.business_channels" style="color:#999;font-size:24rpx;margin-bottom:30rpx">仅微信小程序支持</view>
			<view v-if="custom.show_maidancopy" class="item" @tap="goto" :data-url="'/adminExt/set/maidan?bid='+uinfo.bid">
				<view class="f2">买单设置</view>
				<text class="f3"></text>
				<image :src="pre_url+'/static/img/arrowright.png'" class="f4"></image>
			</view>
			<view class="item" @tap="goto" data-url="setpwd">
				<view class="f2">修改密码</view>
				<text class="f3"></text>
				<image :src="pre_url+'/static/img/arrowright.png'" class="f4"></image>
			</view>
			<view v-if="custom.editpwd" class="item" @tap="goto" data-url="/adminExt/set/editPwd">
				<view class="f2">重置密码</view>
				<text class="f3"></text>
				<image :src="pre_url+'/static/img/arrowright.png'" class="f4"></image>
			</view>
			<view class="item" @tap="goto" data-url="/adminExt/business/selectbusiness" v-if="custom.multi_business">
				<view class="f2">切换店铺</view>
				<text class="f3"></text>
				<image :src="pre_url+'/static/img/arrowright.png'" class="f4"></image>
			</view>
			<view class="item" @tap="goto" data-url="../index/login">
				<view class="f2">切换账号</view>
				<text class="f3"></text>
				<image :src="pre_url+'/static/img/arrowright.png'" class="f4"></image>
			</view>
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
			that.subscribeMessage(function () {
				that.getdata();
			});
    }
  }
};
</script>
<style>
@import "../common.css";
.head-view{display:flex;align-items: center;justify-content: space-between;width: 100%;}
.head-view .avat-view{display:flex;align-items: center;justify-content: flex-start;}
.head-view .avat-view .avat-img-view {width: 80rpx;height:80rpx;border-radius: 50%;overflow: hidden;}
.head-view .avat-view .avat-img-view image{width: 100%;height: 100%;}
.head-view .avat-view .user-info{margin-left: 20rpx;}
.head-view .avat-view .user-info .un-text{font-size: 28rpx;color: rgba(34, 34, 34, 0.7);}
.head-view .recharge{width: 100rpx;color: #FB6534; text-align: center; font-size: 24rpx;}
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
.list .f2{color:#222}
.list .f3{ color: #FC5648;text-align:right;flex:1;}
.list .f4{ width: 24rpx; height: 24rpx;}

switch{transform:scale(.7);}
</style>