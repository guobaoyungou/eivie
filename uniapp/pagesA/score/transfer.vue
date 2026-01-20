<template>
<view class="container">
	<block v-if="isload">
	<form @submit="formSubmit">
		
		<view class="content2">
			<view class="item2"><view class="f1">转账类型</view></view>
			<view class="item3">
				<radio-group name="type" @change="typeChange">
					<label><radio value="1" :checked="type==1?true:false" style="transform: scale(0.8);"></radio>个人到商户</label>
					<label style="margin-left: 50rpx;"><radio value="0" style="transform: scale(0.8);" :checked="type==0?true:false"></radio>商户到个人</label>
				</radio-group>
			</view>
			
			<view class="item4" style="height: 1rpx;"></view>
			<view class="item2"><view class="f1">转出账号</view></view>
			<view class="item3">
				<view class="f2 f2-1">
					<text v-if="type==0">{{business.name}}(bid={{business.id}})</text>
					<text v-if="type==1">{{member.name}}(mid={{member.id}})</text>
				</view>
			</view>
			
			<view class="item4" style="height: 1rpx;"></view>
			<view class="item2"><view class="f1">转入账号</view></view>
			<view class="item3">
				<view class="f2 f2-1">
					<text v-if="type==1">{{business.name}}(bid={{business.id}})</text>
					<text v-if="type==0">{{member.name}}(mid={{member.id}})</text>
				</view>
			</view>
			
			<view class="item4" style="height: 1rpx;"></view>
			<view class="item2"><view class="f1">转赠数量</view></view>
			<view class="item3"><view class="f2"><input class="input" type="number" name="integral" value="" placeholder="请输入转赠数量" placeholder-class="placeholder" @input="moneyinput"></input></view></view>
			<view class="item2" v-if="paycheck"><view class="f1">支付密码</view></view>
			<view class="item3" v-if="paycheck">
				<view class="f2">
					  <input class="input" type="password" name="paypwd" value="" placeholder="请输入支付密码" placeholder-class="placeholder" @input="getpwd"></input>
				</view>
			</view>
			
			<view class="item4" :style="{color:t('color1')}">
				<text style="margin-right:10rpx">当前{{type==1?'会员':'商户'}}{{t('积分')}}：{{myscore}}</text>
			</view>
			
		</view>
		<button class="btn" :style="{background:t('color1')}" form-type="submit">确 定</button>
	
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
			paycheck:false,
			paypwd: '',
			mid:'',
			set:{},
			bname:'转账商户',
			mname:'接收会员',
			businesslist:[],
			business_index:-1,
			type:1,
			member:{},
			myscore:0,
			business:{}
    };
  },

  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		console.log(this.opt);
		this.mid = this.opt.mid ? this.opt.mid : '';
		var that = this;
		this.getdata();
  },
	onPullDownRefresh: function () {
		this.getdata();
	},
  methods: {
		getdata: function () {
			var that = this;
			that.loading = true
			app.get('ApiAdminScore/businessMemberTransfer', {}, function (res) {
				that.loading = false
				if(res.status == 1) {
					// that.businesslist = res.businesslist;
					that.member = res.member
					that.myscore = res.member.score
					that.business = res.business
					if(res.paycheck==1){
						that.paycheck = true
					}
					that.loaded();
				}else{
					app.alert(res.msg);return;
				}
			});
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
		typeChange:function(e){
			this.type = e.detail.value
			if(this.type==0){
				this.myscore = this.business.score
			}else{
				this.myscore = this.member.score
			}
		},
		businessChange:function(e){
			this.business_index = e.detail.value
			console.log('s='+this.type)
			if(this.type==0){
				this.myscore = this.businesslist[this.business_index].score
				console.log(this.myscore)
			}
		},
    formSubmit: function (e) {
      var that = this;
      var money = parseFloat(e.detail.value.integral);
			var paypwd = e.detail.value.paypwd;
			
			
			if (this.paycheck && paypwd=='') {
				app.error("请输入支付密码");
				return false;
			}
      if (isNaN(money) || money <= 0) {
        app.error('数量必须大于0');
        return;
      }
			
			if (money < 0) {
        app.error('数量必须大于0');return;
      } else if (money > that.myscore) {
        app.error(this.t('积分') + '不足');return;
      }

			app.showLoading('提交中');
			app.post('ApiAdminScore/businessMemberTransfer', {score: money,type: that.type,paypwd:paypwd}, function (data) {
				app.showLoading(false);
			  if (data.status == 0) {
			    app.error(data.msg);
			    return;
			  } else {
			    app.success(data.msg);
			    that.subscribeMessage(function () {
			      setTimeout(function () {
			        app.goback(true);
			      }, 1000);
			    });
			  }
			});
    }
  }
};
</script>
<style>
.container{display:flex;flex-direction:column}
.content2{width:94%;margin:10rpx 3%;border-radius:10rpx;display:flex;flex-direction:column;background:#fff;margin-top: 30rpx;}
.content2 .item1{display:flex;width:100%;border-bottom:1px solid #F0F0F0;padding:0 30rpx}
.content2 .item1 .f1{flex:1;font-size:32rpx;color:#333333;font-weight:bold;height:120rpx;line-height:120rpx}
.content2 .item1 .f2{color:#FC4343;font-size:44rpx;font-weight:bold;height:120rpx;line-height:120rpx}

.content2 .item2{display:flex;width:100%;padding:0 30rpx;padding-top:10rpx}
.content2 .item2 .f1{height:80rpx;line-height:80rpx;color:#999999;font-size:28rpx}

.content2 .item3{display:flex;width:100%;padding:10rpx 30rpx;padding-bottom:20rpx}
.content2 .item3 label{font-size: 30rpx; font-weight: bold;color: #434343;}
.content2 .item3 .f1{height:100rpx;line-height:100rpx;font-size:60rpx;color:#333333;font-weight:bold;margin-right:20rpx}
.content2 .item3 .f2{display:flex;align-items:center;font-size:36rpx;color:#333333;font-weight:bold;flex: 1;}
.content2 .item3 .f2-1{font-size:36rpx;color:#787878;font-weight:bold;}
.content2 .item3 .f2 .input{font-size:36rpx;width: 100%;}
.content2 .item4{display:flex;width:94%;margin:0 3%;border-top:1px solid #F0F0F0;height:100rpx;line-height:100rpx;color:#8C8C8C;font-size:28rpx}

.text-center {text-align: center; line-height: 80rpx;}

.btn{ height:100rpx;line-height: 100rpx;width:90%;margin:0 auto;border-radius:50rpx;margin:40rpx auto;color: #fff;font-size: 30rpx;font-weight:bold;}

.placeholder{color:#999;font-size:34rpx}
.picker{flex:1;}
.picker .pf1{flex: 1;}
.picker .row{width: 100%;display: flex;justify-content: space-between;align-items: center;}
.picker image{width: 30rpx;height: 30rpx;}
</style>