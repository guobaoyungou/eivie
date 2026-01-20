<template>
<view class="container">
	<block v-if="isload">
  <view class="mymoney" :style="{background:t('color1')}">
    <view class="f1">我的{{title_name}}</view>
    <view class="f2"><text style="font-size:26rpx">￥</text>{{mymoney}}</view>
    <view class="f3" @tap="goto" :data-url="data_url"><text>变动记录</text><text class="iconfont iconjiantou" style="font-size:20rpx"></text></view>
  </view>
	<form @submit="formSubmit" autocomplete="off">

		<view class="content2">
			
			<block >
			<view class="item2"><view class="f1">对方账号信息</view></view>
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
					<input class="input" type="number" name="mid" :value="mid" placeholder="请输入对方ID/手机号" placeholder-style="color:#999;font-size:36rpx" @input="memberInput"></input>
					<view class="query-button" :style="{color:t('color1')}" @click="changeQuery(mid)">查询</view>
				</view>
			</view>
			<view class="item4" style="height: 1rpx;"></view>
			</block>
			
			<view class="item2"><view class="f1">转账金额</view></view>
			<view class="item3"><view class="f2"><input class="input" type="number" name="money" value="" placeholder="请输入转账金额" placeholder-style="color:#999;font-size:36rpx" @input="moneyinput"></input></view>
			</view>
			<view class="item4">
			</view>
			<view class="desText"></view>
			<view class="desText">注：{{title_name}}转账对方收到的是余额，请仔细核对转账信息，转账后不可退回 </view>
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
			feetype:0,//手续费扣除方式 0扣除转出方 1扣除接受方
			title_name:'',
			ly_type:0,//来源 0爱心基金 1升级奖励
      data_url:'/pagesD/my/offlineLoveUpgradeLog?ly_type=0',
    };
  },

  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.mid = this.opt.mid || '';
		this.ly_type = this.opt.ly_type || 0;
		if(this.opt.tourl) this.tourl = decodeURIComponent(this.opt.tourl);
    if(this.ly_type == 1){
      this.data_url='/pagesD/my/offlineSubsidiesLog?ly_type=1';
    }else {
      this.data_url='/pagesD/my/offlineSubsidiesLog?ly_type=0';
    }
		var that = this;

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
			if(!mid) return app.error('请输入会员ID/手机号');

      var field = '';
      if(mid.length == 11){
        field = {tel:that.mid};
      }else {
        field = {mid:that.mid};
      }
			that.loading = true
			app.get('ApiMy/getMemberBase',field,function (res) {
				that.loading = false
				if(res.status == 1){
					that.member_info = res.data;
				}else{
					app.error('未查询到此会员！');
				}
			});
		},
		getdata: function () {
			var that = this;
			that.loading = true
			app.get('ApiMy/myOfflineSubsidies', {mid:that.mid,ly_type:that.ly_type}, function (res) {
				that.loading = false
				if(res.status == 0) {
					app.alert(res.msg);return;
				}
				if(res.status == 1) {
					that.mymoney = res.mymoney;
					that.moneyList = res.moneyList;
					that.title_name = res.title_name;

          uni.setNavigationBarTitle({
            title: '我的'+that.title_name
          });
				}
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
			}else if(that.money_transfer_min > 0 && money < that.money_transfer_min){
				app.error('最低转账金额'+that.money_transfer_min);
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
				app.post('ApiMy/myOfflineSubsidies', {money: money,mobile: mobile,mid:mid,paypwd:paypwd,feetype:that.feetype,ly_type:that.ly_type}, function (data) {
					app.showLoading(false);
					if (data.status == 0) {
						app.error(data.msg);
						return;
					}else {
						app.success(data.msg);
            that.getdata();
						that.subscribeMessage(function () {
								setTimeout(function () {
									//app.goto(data.tourl);
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
.mymoney{width:94%;margin:20rpx 3%;border-radius: 10rpx 56rpx 10rpx 10rpx;position:relative;display:flex;flex-direction:column;padding:70rpx 0}
.mymoney .f1{margin:0 0 0 60rpx;color:rgba(255,255,255,0.8);font-size:24rpx;}
.mymoney .f2{margin:20rpx 0 0 60rpx;color:#fff;font-size:64rpx;font-weight:bold}
.mymoney .f3{height:56rpx;padding:0 10rpx 0 20rpx;border-radius: 28rpx 0px 0px 28rpx;background:rgba(255,255,255,0.2);font-size:20rpx;font-weight:bold;color:#fff;display:flex;align-items:center;position:absolute;top:94rpx;right:0}

</style>