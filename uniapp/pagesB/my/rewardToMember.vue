<template>
<view class="container">
	<block v-if="isload">
	<form @submit="formSubmit" autocomplete="off">
		
		<view class="content2">
			<!-- <view class="item2"><view class="f1">对方手机号</view></view>
			<view class="item3"><view class="f2"><input class="input" type="number" name="mobile" value="" placeholder="请输入手机号" placeholder-style="color:#999;font-size:36rpx" @input="mobileinput"></input></view></view>
			<view class="item4" style="height: 1rpx;">
			</view> -->
			<view class="item2"><view class="f1">对方ID(手机号)</view></view>
			<view class="item3">
				<view class="member-info" v-if="member_info.id">
					<view class="info-view flex-y-center">
						<image class="head-img" src="member_info.headimg" v-if='head-img'></image>
						<image class="head-img" :src="pre_url+'/static/img/wxtx.png'" v-else></image>
						<view class="member-text-view">
							<view class="member-nickname">{{member_info.nickname}}</view>
							<view class="member-id">id:{{member_info.id}}</view>
						</view>
					</view>
					<view class="query-button" :style="{color:t('color1')}" @click="switchMember">切换</view>
				</view>
				<view class="member-info" v-else>
					<input class="input" type="number" name="mid" :value="mid" placeholder="请输入对方ID/手机号" placeholder-style="color:#999;font-size:36rpx" @input="memberInput"></input>
					<view class="query-button" :style="{color:t('color1')}" @click="changeQuery(mid)">查询</view<>
				</view>
<!-- 				<view class="f2" v-if="mid>0">
					{{mid}}
				</view> -->
			</view>
			<view class="item4" style="height: 1rpx;">
			</view>
			<view class="item2"><view class="f1">打赏金额</view></view>
			<view class="item3"><view class="f2"><input class="input" type="digit" name="money" value="" placeholder="请输入打赏金额" placeholder-style="color:#999;font-size:36rpx" @input="moneyinput"></input></view>
				<!-- <view class="giveset">
					<view v-for="(item, index) in moneyList" :key="index" class="item" :class="moneySelected==item?'active':''" :style="moneySelected==item?'background:'+t('color1'):''" @tap="selectMoney" :data-money="item">
						<text class="t1">{{item}}元</text>
					</view>
				</view> -->
			</view>
			<!-- <view class="item4">
				<text style="margin-right:10rpx" :class="mid>0?'redtxt':''">您的当前{{t('余额')}}：{{mymoney}}，转账后不可退回 </text>
			</view> -->
		</view>
		

		<button class="btn" :style="{background:t('color1')}" form-type="submit">确定</button>
		<view class='text-center' @tap="goto" data-url='/pages/my/usercenter' style="margin-top: 40rpx; line-height: 60rpx;"><text>返回{{t('会员')}}中心</text></view>
	</form>
	<view class="explain">
		<view class="f1"> — 打赏说明 — </view>
		<view class="f2">
			<parse :content="desc" />
		</view>
	</view>
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
		moneyList:[],
        mymoney: 0,
		moneySelected: '',
        paypwd: '',
		paycheck:false,
		mid:0,
		tourl:'/pages/my/usercenter',
		member_info:{},
		pre_url:app.globalData.pre_url,
		desc:''
    };
  },

  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.mid = this.opt.mid || 0;
		if(this.opt.tourl) this.tourl = decodeURIComponent(this.opt.tourl);

		var that = this;
		// app.checkLogin();
		uni.setNavigationBarTitle({
			title: '打赏'
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
		memberInput(event){
			this.mid = event.detail.value;
		},
		changeQuery(mid){
			let that = this;
			if(!mid) return app.error('请输入会员ID');
			that.loading = true
			app.get('ApiBusinessReward/getMemberBase',{mid:that.mid},function (res) {
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
			app.get('ApiBusinessReward/rechargeToMember', {mid:that.mid}, function (res) {
				that.loading = false
				if(res.status == 0) {
					app.alert(res.msg);
					if(res.url){
						setTimeout(function () {
							 app.goto(res.url);
						}, 1000);	
					}
					return;
				}
				that.desc = res.desc;
				that.loaded();
			});
		},
		
    moneyinput: function (e) {
      var money = parseFloat(e.detail.value);
      
    },
    formSubmit: function (e) {
        var that = this;

      // var money = parseFloat(that.moneySelected);
        var money = parseFloat(e.detail.value.money);
				if(that.mid>0){
					var mid = that.mid;
				}else{
					var mid = parseInt(e.detail.value.mid);
				}
		
		var mobile = e.detail.value.mobile;
        var paypwd = e.detail.value.paypwd;
		if (typeof(mobile) != 'undefined' && !/^1[3456789]\d{9}$/.test(mobile)) {
		  app.error("手机号码有误，请重填");
		  return false;
		}
		if (typeof(mid) != 'undefined' && (mid == '' || isNaN(mid))) {
			app.error("请输入接收人ID");
			return false;
		}
		if(typeof(mid) != 'undefined' && mid == app.globalData.mid) {
			app.error("不能打赏给自己");
			return false;
		}
		if (isNaN(money) || money <= 0) {
			app.error('打赏金额必须大于0');
			return;
		}
		
				
		if (money < 0) {
			app.error('转账金额必须大于0');return;
		} 


		app.confirm('确定要打赏吗？', function(){
			app.showLoading();
			app.post('ApiBusinessReward/rechargeToMember', {money: money,mid:mid}, function (data) {
				app.showLoading(false);
			  if (data.status == 0) {
				app.error(data.msg);
				return;
			  }else {
				if(data.data.payorderid){
					app.goto('/pagesExt/pay/pay?id=' + data.data.payorderid);
				}
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

.explain{ width:100%;margin:20rpx 0;}
.explain .f1{width:100%;text-align:center;font-size:30rpx;color:#333;font-weight:bold;height:50rpx;line-height:50rpx}
.explain .f2{padding:20rpx;background-color:#fff}
</style>