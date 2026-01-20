<template>
<view class="container">
	<block v-if="isload">
	<form @submit="formSubmit" autocomplete="off">
		
		<view class="content2">
			<block>
			<view class="item2"><view class="f1">对方ID</view></view>
			<view class="item3">
				<view class="member-info" v-if="member_info.id">
					<view class="info-view flex-y-center">
						<image class="head-img" :src="member_info.headimg" v-if='member_info.headimg'></image>
						<image class="head-img" :src="pre_url+'/static/img/wxtx.png'" v-else></image>
						<view class="member-text-view">
							<view class="member-nickname">{{member_info.nickname}}</view>
							<view class="member-id">ID：{{member_info.id}}</view>
						</view>
					</view>
					<view class="query-button" :style="{color:t('color1')}" @click="switchMember">切换</view>
				</view>
				<view class="member-info" v-else>
					<input class="input" type="number" name="mid" :value="mid" placeholder="请输入对方ID" placeholder-style="color:#999;font-size:36rpx" @input="memberInput"></input>
					<view class="query-button" :style="{color:t('color1')}" @click="changeQuery(mid)">查询</view<>
				</view>
			</view>
			<view class="item4" style="height: 1rpx;"></view>
			</block>
			
			<view class="item2"><view class="f1">转账金额</view></view>
			<view class="item3"><view class="f2"><input class="input" type="number" name="money" value="" placeholder="请输入转账金额" placeholder-style="color:#999;font-size:36rpx" @input="moneyinput"></input></view>
			</view>
      <view class="item2" v-if="paycheck"><view class="f1">支付密码</view></view>
      <view class="item3" v-if="paycheck">
		  <view class="f2">
			  <input class="input" type="password" name="paypwd" value="" placeholder="请输入支付密码" placeholder-style="color:#999;font-size:36rpx" @input="getpwd"></input>
		</view>
      </view>
			<view class="item4">
				<text style="margin-right:10rpx" :class="mid>0?'redtxt':''">您的当前{{t('余额')}}：{{mymoney}}，转账后不可退回 </text>
			</view>
		</view>

		<button class="btn" :style="{background:t('color1')}" form-type="submit">转账</button>
		<view class='text-center' @tap="goto" data-url='/admin/finance/index' style="margin-top: 40rpx; line-height: 60rpx;"><text>返回财务首页</text></view>
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
			moneyList:[],
			mymoney: 0,
			moneySelected: '',
			paypwd: '',
			paycheck:false,
			mid:'',
			mobile:'',
			tourl:'/pages/my/usercenter',
			member_info:{},
			member_info2:{},
			pre_url:app.globalData.pre_url,
			money_transfer_type:[]
    };
  },

  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.mid = this.opt.mid || '';
		if(this.opt.tourl) this.tourl = decodeURIComponent(this.opt.tourl);

		var that = this;
		// app.checkLogin();
		uni.setNavigationBarTitle({
			title: '转账'
		});
		this.getdata();
		// this.getpaycheck();
  },
	onPullDownRefresh: function () {
		this.getdata();
	},
  methods: {
		switchMember(){
			this.member_info = {};
			this.mid = '';
		},
		switchMember2(){
			this.member_info2 = {};
			this.mobile = '';
		},
		memberInput(event){
			this.mid = event.detail.value;
		},
		changeQuery(mid){
			let that = this;
			if(!mid) return app.error('请输入会员ID');
			that.loading = true
			app.get('ApiMy/getMemberBase',{mid:that.mid},function (res) {
				that.loading = false
				if(res.status == 1){
					that.member_info = res.data;
				}else{
					app.error('未查询到此会员！');
				}
			});
		},
		changeQuery2(mobile){
			let that = this;
			if(!mobile) return app.error('请输入手机号');
			that.loading = true
			app.get('ApiMy/getMemberBase',{tel:that.mobile},function (res) {
				that.loading = false
				if(res.status == 1){
					that.member_info2 = res.data;
				}else{
					app.error('未查询到此会员！');
				}
			});
		},
		getdata: function () {
			var that = this;
			that.loading = true
			app.get('ApiAdminFinance/transferMendianMoney', {mid:that.mid}, function (res) {
				that.loading = false
				if(res.status == 0) {
					app.alert(res.msg);return;
				}
				if(res.status == 1) {
					that.mymoney = res.mymoney;
					that.moneyList = res.moneyList;
				}
				if(res.paycheck==1){
					that.paycheck = true
				}
				if(res.money_transfer_type && res.money_transfer_type.length == 0) app.alert('未设置可用的转账方式，请联系客服');
				that.money_transfer_type = res.money_transfer_type;
				that.loaded();
			});
		},
		selectMoney: function (e) {
		  var money = e.currentTarget.dataset.money;
		  this.moneySelected = money;
		},
		mobileinput: function (e) {
		  var value = e.detail.value;
			this.mobile = value;
		},
		
    moneyinput: function (e) {
      var money = parseFloat(e.detail.value);
    },
    changeradio: function (e) {
      var that = this;
      var paytype = e.currentTarget.dataset.paytype;
      that.paytype = paytype;
    },
    getpwd: function (e) {
      var that = this;
      var paypwd = e.detail.value;
      that.paypwd = paypwd;
    },
    formSubmit: function (e) {
			var that = this;
			var money = parseFloat(e.detail.value.money);
			if(that.mid>0){
				var mid = that.mid;
			}else{
				var mid = typeof(mid) != 'undefined' ? parseInt(e.detail.value.mid) : e.detail.value.mid;
			}
			if(that.mobile != ''){
				var mobile = that.mobile;
			}else{
				var mobile = e.detail.value.mobile;
			}
			var paypwd = e.detail.value.paypwd;
			// if(inArray('tel',that.money_transfer_type) && inArray('tel',that.money_transfer_type))
			if (typeof(mobile) != 'undefined' && typeof(mid) != 'undefined' && mobile == '' && (mid == '' || mid == 0 || isNaN(mid))) {
				app.error("请输入手机号码或接收人ID");
				return false;
			}
			
			if (typeof(mobile) != 'undefined' && typeof(mid) == 'undefined' && !app.isPhone(mobile)) {
				app.error("手机号码有误，请重填");
				return false;
			}
			if (typeof(mobile) != 'undefined' && mobile != '' && !app.isPhone(mobile)) {
				app.error("手机号码有误，请重填");
				return false;
			}
			if (typeof(mid) != 'undefined' && typeof(mobile) == 'undefined' && (mid == '' || mid == 0 || isNaN(mid))) {
				app.error("请输入接收人ID");
				return false;
			}
			if(typeof(mid) != 'undefined' && mid == app.globalData.mid) {
				app.error("不能转账给自己");
				return false;
			}
			if (isNaN(money) || money <= 0) {
				app.error('转账金额必须大于0');
				return;
			}
			if (this.paycheck && paypwd=='') {
				app.error("请输入支付密码");
				return false;
			}
					
			if (money < 0) {
				app.error('转账金额必须大于0');return;
			} else if (money > that.mymoney) {
				app.error(this.t('余额') + '不足');return;
			}

			app.confirm('确定要转账吗？', function(){
				app.showLoading();
				app.post('ApiAdminFinance/transferMendianMoney', {money: money,mobile: mobile,mid:mid,paypwd:paypwd}, function (data) {
					app.showLoading(false);
					if (data.status == 0) {
						app.error(data.msg);
						if(data.set_paypwd==1){
								let timer = setTimeout(function () {
								clearTimeout(timer)
								uni.navigateTo({
									url:'/pagesExt/my/paypwd'
								})
								}, 2000);
						} 
						return;
					}else {
						app.success(data.msg);
						that.subscribeMessage(function () {
								setTimeout(function () {
									app.goto(data.url);
								}, 1000);
							});
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

.content2 .item4{display:flex;width:94%;margin:0 3%;border-top:1px solid #F0F0F0;height:100rpx;line-height:100rpx;color:#8C8C8C;font-size:28rpx}
.content2 .redtxt{color: #FC4343;}
.text-center {text-align: center;}

.btn{ height:100rpx;line-height: 100rpx;width:90%;margin:0 auto;border-radius:50rpx;margin-top:30rpx;color: #fff;font-size: 30rpx;font-weight:bold}

.giveset{width:100%;padding:20rpx 20rpx 20rpx 20rpx;display:flex;flex-wrap:wrap;justify-content:center}
.giveset .item{margin:10rpx;padding:15rpx 0;width:25%;height:100rpx;background:#FDF6F6;border-radius:10rpx;display:flex;flex-direction:row;align-items:center;justify-content:center}
.giveset .item .t1{color:#545454;font-size:32rpx;}
.giveset .item .t2{color:#8C8C8C;font-size:20rpx;margin-top:6rpx}
.giveset .item.active .t1{color:#fff;font-size:32rpx}
.giveset .item.active .t2{color:#fff;font-size:20rpx}
</style>