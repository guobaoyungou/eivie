<template>
<view class="container" v-if="isload">
	<form @submit="formSubmit" @reset="formReset" report-submit="true">
		<view class="form-item">
			<text class="label">退款原因</text>
			<view class="input-item"><textarea placeholder="请输入退款原因" placeholder-style="color:#999;" name="reason"></textarea></view>
		</view>
		<view class="form-item">
			<text class="label">退款金额(元)</text>
			<view class="flex"><input name="money" type="digit" :value="totalprice" placeholder="请输入退款金额" placeholder-style="color:#999;"></input></view>
		</view>
		<button class="ref-btn" form-type="submit" :style="'background: '+t('color1')">确定</button>
		<view class="opt_text" v-if="refund_remark !==''">* {{refund_remark}}</view>
		
	</form>
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

        orderid: 0,
        totalprice: 0,
		refund_remark : ''
    };
  },

  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		var that = this;
		that.orderid = that.opt.orderid;
		that.totalprice = that.opt.price;
		this.getdata();
  },
	onPullDownRefresh: function () {
		this.getdata();
	},
  methods: {
	   
		getdata:function(){
			var that = this;
			that.loading=true;
			app.post('ApiCerberuse/getRefundMoney', {
			    orderid: that.orderid,
			}, function (data) {
				
			    that.totalprice = data.refund_money;
				that.refund_remark = data.refund_remark;
				that.loaded();
				that.loading = false;
			});
			
		},
        formSubmit: function (e) {
            var that = this;
            var orderid = that.orderid;
            var reason = e.detail.value.reason;
            var money = parseFloat(e.detail.value.money);

            if (reason == '') {
                app.alert('请填写退款原因');
                return;
            }

            if (money < 0 || money > parseFloat(that.totalprice)) {
                app.alert('退款金额有误');
                return;
            }

            app.showLoading('提交中');
            app.post('ApiCerberuse/refund', {
                orderid: orderid,
                reason: reason,
                money: money,
				refund_remark:that.refund_remark
            }, function (data) {
                app.showLoading(false);
                app.alert(data.msg);
                if(data.status == 1){
                    var pages = getCurrentPages();
                    
                    if (pages.length > 1) {
                    var prePage = pages[pages.length - 2];
                        prePage.onLoad();
                    }
                    
                    setTimeout(function () {
                        app.goback();
                    }, 1000);
                }
                
            });
        }
  }
};
</script>
<style>
.form-item{ width:100%;background: #fff; padding: 32rpx 20rpx;}
.form-item .label{ width:100%;height:60rpx;line-height:60rpx}
.form-item .input-item{ width:100%;}
.form-item textarea{ width:100%;height:200rpx;border: 1px #eee solid;padding: 20rpx;}
.form-item input{ width:100%;border: 1px #eee solid;padding: 10rpx;height:80rpx}
.form-item .mid{ height:80rpx;line-height:80rpx;padding:0 20rpx;}
.ref-btn{ width: 90%; margin: 0 5%; height: 40px; line-height: 40px; text-align: center; color: #fff; font-size: 16px; border-radius: 8px;border: none; }
.opt_text {
			font-size: 26rpx;
			font-family: Source Han Sans CN;
			font-weight: 400;
			color: #908F9E;
			text-align: center;
			margin-top: 30rpx;
		}
</style>