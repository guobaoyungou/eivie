<template>
<view>
<block v-if="isload">
	<view class="orderinfo">
		<view class="item">
			<text class="t1">{{t('会员')}}信息</text>
			<view class="t2 flex-y-center flex-x-bottom">
				<image :src="info.headimg" style="width:80rpx;height:80rpx;margin-right:8rpx"></image> 
				<view style="max-width: 380rpx;text-align: left;">{{info.nickname}}</view>
			</view>
		</view>
	</view>
	<view class="orderinfo">
		<view class="item">
			<text class="t1">提现金额</text>
			<text class="t2">￥{{info.txmoney}}</text>
		</view>
		<view class="item">
			<text class="t1">打款金额</text>
			<text class="t2">￥{{info.money}}</text>
		</view>
		<view class="item">
			<text class="t1">提现方式</text>
			<text class="t2">{{info.paytype}}</text>
		</view>
		<view class="item" v-if="info.paytype=='支付宝'">
			<text class="t1">支付宝账号</text>
			<text class="t2">{{info.aliaccount}}</text>
		</view>
		<view class="item" v-if="info.paytype=='支付宝'">
			<text class="t1">姓名</text>
			<text class="t2">{{info.aliaccountname}}</text>
		</view>
		<view class="item" v-if="info.paytype=='银行卡'">
			<text class="t1">开户行</text>
			<text class="t2">{{info.bankname}}</text>
		</view>
		<view class="item" v-if="info.paytype=='银行卡'">
			<text class="t1">持卡人</text>
			<text class="t2">{{info.bankcarduser}}</text>
		</view>
		<view class="item" v-if="info.paytype=='银行卡'">
			<text class="t1">卡号</text>
			<text class="t2">{{info.bankcardnum}}</text>
		</view>
    <block v-if="info.paytype=='收款码'">
      <view v-if="info.wxpaycode" class="item" >
        <view class="t1">微信收款码</view>
        <view class="t2">
          <image :src="info.wxpaycode" @tap="previewImage" :data-url="info.wxpaycode" mode="widthFix" style="width: 200rpx;"></image>
        </view>
      </view>
      <view v-if="info.alipaycode" class="item" >
        <view class="t1">支付宝收款码</view>
        <view class="t2">
          <image :src="info.alipaycode" @tap="previewImage" :data-url="info.alipaycode" mode="widthFix" style="width: 200rpx;"></image>
        </view>
      </view>
    </block>

		<view class="item">
			<text class="t1">状态</text>
			<text class="t2" v-if="info.status==0">审核中</text>
			<text class="t2" v-if="info.status==1">已审核</text>
			<text class="t2" v-if="info.status==2">已驳回</text>
			<text class="t2" v-if="info.status==3">已打款</text>
		</view>
	</view>
  <view style="width:100%;height:120rpx"></view>

  <view class="bottom">
		<view v-if="info.status==0" class="btn" @tap="shenhepass" :data-id="info.id">审核通过</view>
		<view v-if="info.status==0" class="btn" @tap="shenhenopass" :data-id="info.id">审核驳回</view>
		<view v-if="info.status==1" class="btn" @tap="setydk" :data-id="info.id">改为已打款</view>
		<view v-if="info.status==1 && (info.paytype=='微信钱包' || info.paytype=='银行卡')" class="btn" @tap="wxdakuan" :data-id="info.id">微信打款</view>
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

      info: {},
    };
  },
  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.getdata();
  },
  methods: {
		getdata: function () {
			var that = this;
			that.loading = true;
			app.get('ApiAdminFinance/withdrawdetail', {id: that.opt.id}, function (res) {
				that.loading = false;
				that.info = res.info;
				uni.setNavigationBarTitle({
					title: that.t('余额') + '提现详情'
				});
				that.loaded();
			});
		},
    shenhenopass: function (e) {
      var that = this;
      var id = e.currentTarget.dataset.id;
      app.confirm('确定要驳回提现申请吗?', function () {
				app.showLoading('提交中');
        app.post('ApiAdminFinance/widthdrawnopass', {id: id}, function (data) {
					app.showLoading(false);
          app.success(data.msg);
          setTimeout(function () {
            that.getdata();
          }, 1000);
        });
      });
    },
    shenhepass: function (e) {
      var that = this;
      var id = e.currentTarget.dataset.id;
      app.confirm('确定要审核通过吗?', function () {
				app.showLoading('提交中');
        app.post('ApiAdminFinance/widthdrawpass', {id: id}, function (data) {
					app.showLoading(false);
          app.success(data.msg);
          setTimeout(function () {
            that.getdata();
          }, 1000);
        });
      });
    },
    setydk: function (e) {
      var that = this;
      var id = e.currentTarget.dataset.id;
      app.confirm('确定已通过其他方式打款吗?此操作仅修改状态，不进行打款', function () {
				app.showLoading('提交中');
        app.post('ApiAdminFinance/widthdsetydk', {id: id}, function (data) {
					app.showLoading(false);
          app.success(data.msg);
          setTimeout(function () {
            that.getdata();
          }, 1000);
        });
      });
    },
    wxdakuan: function (e) {
      var that = this;
      var id = e.currentTarget.dataset.id;
      app.confirm('确定要微信打款吗?', function () {
				app.showLoading('提交中');
        app.post('ApiAdminFinance/widthdwxdakuan', {id: id}, function (data) {
					app.showLoading(false);
          app.success(data.msg);
          setTimeout(function () {
            that.getdata();
          }, 1000);
        });
      });
    }
  }
};
</script>
<style>

.orderinfo{ width:100%;margin-top:10rpx;padding: 14rpx 3%;background: #FFF;}
.orderinfo .item{display:flex;width:100%;padding:20rpx 0;border-bottom:1px dashed #ededed;}
.orderinfo .item:last-child{ border-bottom: 0;}
.orderinfo .item .t1{width:200rpx;}
.orderinfo .item .t2{flex:1;text-align:right}
.orderinfo .item .red{color:red}

.bottom{ width: 100%; padding: 16rpx 20rpx;background: #fff; position: fixed; bottom: 0px; left: 0px;display:flex;justify-content:flex-end;align-items:center;}
.bottom .btn{ border-radius:10rpx; padding:10rpx 16rpx;margin-left: 10px; border: 1px #999 solid;color: #555;}
.bottom .pay{ border: 1px #ff8758 solid; color: #ff8758;}
.bottom .del{ border: 1px red solid;color: red;}
.bottom .coll{ border: 1px #ff4246 solid;color: #ff4246;}
.bottom .wul{ border: 1px #06aa53 solid; color: #06aa53; }
.bottom .ref{ border: 1px #999 solid;color: #999;}
.bottom .det{ border: 1px #555 solid;color: #555;}

</style>