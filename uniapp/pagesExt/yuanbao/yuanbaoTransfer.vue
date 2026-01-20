<template>
<view class="container">
	<block v-if="isload">
	<form @submit="formSubmit">
		
		<view class="content2">
			<!-- <view class="item2"><view class="f1">接收人手机号</view></view>
			<view class="item3"><view class="f2"><input class="input" type="number" name="mobile" value="" placeholder="请输入接收人手机号" placeholder-style="color:#999;font-size:36rpx" @input="mobileinput"></input></view></view>
			<view class="item4" style="height: 1rpx;">
			</view> -->
			<view class="item2"><view class="f1">接收人ID</view></view>
			<view class="item3"><view class="f2"><input class="input" type="number" name="mid" value="" placeholder="请输入接收人ID" placeholder-style="color:#999;font-size:36rpx"></input></view></view>
			<view class="item4" style="height: 1rpx;">
			</view>
			<view class="item2"><view class="f1">转赠数量</view></view>
			<view class="item3"><view class="f2"><input class="input" type="number" name="integral" value="" placeholder="请输入转赠数量" placeholder-style="color:#999;font-size:36rpx" @input="moneyinput"></input></view></view>
			<view class="item4" style="height:170rpx;line-height: 50rpx;padding: 10rpx 0;">
				<view style="margin-right:10rpx">
                    您的当前{{t('元宝')}}：{{myyuanbao}}
                    <view v-if="yuanbao_money_ratio">
                        还需要支付{{yuanbao_money_ratio}}%比例的现金
                    </view>
                    <view>
                    转赠后不可退回 
                    </view>
                </view>
			</view>
		</view>
		<button class="btn" :style="{background:t('color1')}" form-type="submit">转账</button>
		<view class='text-center' @tap="goto" data-url='/pages/my/usercenter'><text>返回{{t('会员')}}中心</text></view>
	</form>
	</block>
	<loading v-if="loading"></loading>
	<dp-tabbar :opt="opt"></dp-tabbar>
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
			
            userinfo: [],
            myyuanbao: 0,
            yuanbao_money_ratio:0
    };
  },

    onLoad: function (opt) {
        this.opt = app.getopts(opt);
        var that = this;
        this.getdata();
    },
	onPullDownRefresh: function () {
		this.getdata();
	},
  methods: {
		getdata: function () {
			var that = this;
			app.get('ApiMy/yuanbaoTransfer', {}, function (res) {

				if(res.status == 1) {
                    uni.setNavigationBarTitle({
                    	title: res.title
                    });
					that.myyuanbao           = res.myyuanbao;
                    that.yuanbao_money_ratio = res.yuanbao_money_ratio;
                    that.loaded();
				}else{
                    app.alert(res.msg);return;
                }
				
			});
		},
		
		mobileinput: function (e) {
		  var value = parseFloat(e.detail.value);
		},
		
    moneyinput: function (e) {
      var money = parseFloat(e.detail.value);
      
    },
    changeradio: function (e) {
      var that = this;
      var paytype = e.currentTarget.dataset.paytype;
      that.paytype = paytype;
    },
    formSubmit: function (e) {
      var that = this;
      var money = parseFloat(e.detail.value.integral);
			var mid = parseInt(e.detail.value.mid);
			var mobile = e.detail.value.mobile;
			if (typeof(mobile) != 'undefined' && !app.isPhone(mobile)) {
			  app.error("手机号码有误，请重填");
			  return false;
			}
			if (typeof(mid) != 'undefined' && (mid == '' || isNaN(mid))) {
				app.error("请输入接收人ID");
				return false;
			}
			if(typeof(mid) != 'undefined' && mid == app.globalData.mid) {
				app.error("不能转赠给自己");
				return false;
			}
            if (isNaN(money) || money <= 0) {
                app.error('数量必须大于0');
                return;
            }

            if (money < 0) {
                app.error('数量必须大于0');return;
            } else if (money > that.myyuanbao) {
                app.error(this.t('元宝') + '不足');return;
            }

            app.confirm('确定要转账吗？', function(){
                app.showLoading();
                app.post('ApiMy/yuanbaoTransfer', {integral: money,mobile: mobile,mid:mid}, function (res) {
                    app.showLoading(false);
                    if (res.status == 0) {
                        app.error(res.msg);
                        return;
                    } else if (res.status == 1) {
                        app.success(res.msg);
                        that.subscribeMessage(function () {
                            setTimeout(function () {
                                app.goto('/pages/my/usercenter');
                            }, 1000);
                        });
                    }else if(res.status == 2){
                        app.goto('/pagesExt/pay/pay?id=' + res.payorderid);
                    }
                }, '提交中');
            })
    }
  }
};
</script>
<style>
.container{display:flex;flex-direction:column}
.content2{width:94%;margin:10rpx 3%;border-radius:10rpx;display:flex;flex-direction:column;background:#fff}
.content2 .item1{display:flex;width:100%;border-bottom:1px solid #F0F0F0;padding:0 30rpx}
.content2 .item1 .f1{flex:1;font-size:32rpx;color:#333333;font-weight:bold;height:120rpx;line-height:120rpx}
.content2 .item1 .f2{color:#FC4343;font-size:44rpx;font-weight:bold;height:120rpx;line-height:120rpx}

.content2 .item2{display:flex;width:100%;padding:0 30rpx;padding-top:10rpx}
.content2 .item2 .f1{height:80rpx;line-height:80rpx;color:#999999;font-size:28rpx}

.content2 .item3{display:flex;width:100%;padding:0 30rpx;padding-bottom:20rpx}
.content2 .item3 .f1{height:100rpx;line-height:100rpx;font-size:60rpx;color:#333333;font-weight:bold;margin-right:20rpx}
.content2 .item3 .f2{display:flex;align-items:center;font-size:36rpx;color:#333333;font-weight:bold}
.content2 .item3 .f2 .input{font-size:36rpx;height:100rpx;line-height:100rpx;}
.content2 .item4{display:flex;width:94%;margin:0 3%;border-top:1px solid #F0F0F0;height:100rpx;line-height:100rpx;color:#8C8C8C;font-size:28rpx}

.text-center {text-align: center; margin-top: 20rpx;}

.btn{ height:100rpx;line-height: 100rpx;width:90%;margin:0 auto;border-radius:50rpx;margin-top:30rpx;color: #fff;font-size: 30rpx;font-weight:bold}
</style>