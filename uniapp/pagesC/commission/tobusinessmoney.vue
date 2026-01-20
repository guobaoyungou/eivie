<template>
<view class="container">
	<block v-if="isload">
	<form @submit="formSubmit" autocomplete="off">
		<view class="content2">
			<view class="item2"><view class="f1">商户ID</view></view>
			<view class="item3">
				<view class="member-info" v-if="business.id">
					<view class="info-view flex-y-center">
						<image class="head-img" :src="business.logo" v-if='business.logo'></image>
						<image class="head-img" :src="pre_url+'/static/img/wxtx.png'" v-else></image>
						<view class="member-text-view">
							<view class="member-nickname">{{business.name}}</view>
							<view class="member-id">ID：{{business.id}}</view>
						</view>
					</view>
					<view class="query-button" :style="{color:t('color1')}" @click="switchBusiness">切换</view>
				</view>
				<view class="member-info" v-else>
					<input class="input" type="number" name="bid" :value="bid" placeholder="请输入商户ID" placeholder-style="color:#999;font-size:36rpx" @input="businessInput"></input>
					<view class="query-button" :style="{color:t('color1')}" @click="changeQuery(bid)">查询</view<>
				</view>
			</view>
			<view class="item4" style="height: 1rpx;"></view>
			
			<view class="item2"><view class="f1">转账金额</view></view>
			<view class="item3">
				<view class="f2"><input class="input" type="number" name="money" value="" placeholder="请输入转账金额" placeholder-style="color:#999;font-size:36rpx" @input="moneyinput"></input></view>
			</view>
			<view class="item4">
			</view>
			<view class="desText">您的当前{{t('佣金')}}：{{mycommission}}，转账后不可退回 </view>
			<view class="desText" v-if="transfer_rate > 0">转账费率：{{transfer_rate}}%</view>
		</view>
		<button class="btn" :style="{background:t('color1')}" form-type="submit">转账</button>
		<view class='text-center' @tap="goto" data-url='/pages/my/usercenter' style="margin-top: 40rpx; line-height: 60rpx;"><text>返回{{t('会员')}}中心</text></view>
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
			userinfo: {},
			mycommission: 0,
			tourl:'/pages/my/usercenter',
			business:{},
			pre_url:app.globalData.pre_url,
			transfer_rate:0
    };
  },

  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		var that = this;
		uni.setNavigationBarTitle({
			title: '转账'
		});
		this.getdata();
  },
	onPullDownRefresh: function () {
		this.getdata();
	},
  methods: {
		switchBusiness(){
			this.business = {};
			this.bid = '';
		},
		businessInput(event){
			this.bid = event.detail.value;
		},
		changeQuery(bid){
			let that = this;
			if(!bid) return app.error('请输入商户ID');
			that.loading = true
			app.get('ApiBusiness/getBusiness',{id:that.bid},function (res) {
				that.loading = false
				if(res.status == 1){
					that.business = res.data;
				}else{
					app.error('未查询到此商户！');
				}
			});
		},
		getdata: function () {
			var that = this;
			that.loading = true
			app.get('ApiAgent/commissionToBusiness', {}, function (res) {
				that.loading = false
				if(res.status == 0) {
					app.alert(res.msg);return;
				}
				if(res.status == 1) {
					that.mycommission = res.mycommission;
					that.transfer_rate = res.transfer_rate;
				}
				that.loaded();
			});
		},
    moneyinput: function (e) {
      var money = parseFloat(e.detail.value);
    },
    formSubmit: function (e) {
			var that = this;
			var money = parseFloat(e.detail.value.money);
			if(that.bid>0){
				var bid = that.bid;
			}else{
				var bid = typeof(bid) != 'undefined' ? parseInt(e.detail.value.bid) : e.detail.value.bid;
			}
			if (typeof(bid) != 'undefined' && (bid == '' || bid == 0 || isNaN(bid))) {
				app.error("请输入商户ID");
				return false;
			}
			if (isNaN(money) || money <= 0) {
				app.error('转账金额必须大于0');
				return;
			}
			if (money < 0) {
				app.error('转账金额必须大于0');return;
			} else if (money > that.mycommission) {
				app.error(this.t('佣金') + '不足');return;
			}

			app.confirm('确定要转账吗？', function(){
				app.showLoading();
				app.post('ApiAgent/commissionToBusiness', {money: money,bid:bid}, function (data) {
					app.showLoading(false);
					if (data.status == 0) {
						app.error(data.msg);
						return;
					}else {
						app.success(data.msg);
						that.subscribeMessage(function () {
								setTimeout(function () {
									app.goto(that.tourl);
								}, 1000);
							});
					}
				}, '提交中');
			})
    },
  }
};
</script>
<style>
.container{display:flex;flex-direction:column}
.content2{width:94%;margin:10rpx 3%;padding-bottom:10rpx;border-radius:10rpx;display:flex;flex-direction:column;background:#fff}
.content2 .item1{display:flex;width:100%;border-bottom:1px solid #F0F0F0;padding:0 30rpx}
.content2 .item1 .f1{flex:1;font-size:32rpx;color:#333333;font-weight:bold;height:120rpx;line-height:120rpx}
.content2 .item1 .f2{color:#FC4343;font-size:44rpx;font-weight:bold;height:120rpx;line-height:120rpx}

.content2 .item2{display:flex;width:100%;padding:0 30rpx;padding-top:10rpx}
.content2 .item2 .f1{height:80rpx;line-height:80rpx;color:#999999;font-size:28rpx}

.content2 .item3{display:flex;width:100%;padding:0 30rpx;padding-bottom:20rpx}
.content2 .item3 .f1{height:100rpx;line-height:100rpx;font-size:60rpx;color:#333333;font-weight:bold;margin-right:20rpx}
.content2 .item3 .f2{display:flex;align-items:center;font-size:36rpx;color:#333333;font-weight:bold;flex: 1;}
.content2 .item3 .f2 .input{font-size:36rpx;height:100rpx;line-height:100rpx;width: 100%;}
.content2 .item3 .member-info{display:flex;align-items:center;flex: 1;}
.content2 .item3 .member-info .input{font-size:36rpx;height:100rpx;line-height:100rpx;width: 100%;color:#333333;font-weight:bold;}
.content2 .item3 .member-info .query-button{white-space: nowrap;font-size: 28rpx;border-radius: 8rpx;padding: 5rpx 8rpx;}
.content2 .item3 .member-info .info-view{flex: 1;}
.content2 .item3 .member-info .info-view .head-img{width: 90rpx;height: 90rpx;border-radius: 8rpx;overflow: hidden;}
.content2 .item3 .member-info .info-view .member-text-view{height: 90rpx;padding-left: 20rpx;display: flex;flex-direction: column;align-items: flex-start;justify-content: flex-start;}
.content2 .item3 .member-info .info-view .member-text-view .member-nickname{font-size: 28rpx;color: #333;font-weight: bold;}
.content2 .item3 .member-info .info-view .member-text-view .member-id{font-size: 24rpx;color: #999999;margin-top: 10rpx;}
.content2 .item3 .member-info-oneline .info-view .member-text-view {flex-direction: row; align-items: center;}

.content2 .item4{display:flex;width:94%;margin:0 3%;border-top:1px solid #F0F0F0;}
.content2 .desText{width:94%;margin:12rpx 3%;color:#8C8C8C;font-size:28rpx}
.text-center {text-align: center;}

.btn{ height:100rpx;line-height: 100rpx;width:90%;margin:0 auto;border-radius:50rpx;margin-top:30rpx;color: #fff;font-size: 30rpx;font-weight:bold}

.giveset{width:100%;padding:20rpx 20rpx 20rpx 20rpx;display:flex;flex-wrap:wrap;justify-content:center}
.giveset .item{margin:10rpx;padding:15rpx 0;width:25%;height:100rpx;background:#FDF6F6;border-radius:10rpx;display:flex;flex-direction:row;align-items:center;justify-content:center}
.giveset .item .t1{color:#545454;font-size:32rpx;}
.giveset .item .t2{color:#8C8C8C;font-size:20rpx;margin-top:6rpx}
.giveset .item.active .t1{color:#fff;font-size:32rpx}
.giveset .item.active .t2{color:#fff;font-size:20rpx}


.withdrawtype{width:94%;margin:20rpx 3%;border-radius:10rpx;display:flex;flex-direction:column;margin-top:20rpx;background:#fff}
.withdrawtype .f1{height:100rpx;line-height:100rpx;padding:0 30rpx;color:#333333;font-weight:bold}


.withdrawtype .f2{padding:0 30rpx}
.withdrawtype .f2 .item{border-bottom:1px solid #f5f5f5;height:100rpx;display:flex;align-items:center}
.withdrawtype .f2 .item:last-child{border-bottom:0}
.withdrawtype .f2 .item .t1{flex:1;display:flex;align-items:center;color:#333}
.withdrawtype .f2 .item .t1 .img{width:44rpx;height:44rpx;margin-right:40rpx}

.withdrawtype .f2 .item .radio{flex-shrink:0;width: 36rpx;height: 36rpx;background: #FFFFFF;border: 3rpx solid #BFBFBF;border-radius: 50%;margin-right:10rpx}
.withdrawtype .f2 .item .radio .radio-img{width:100%;height:100%}
</style>