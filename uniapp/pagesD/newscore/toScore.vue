<template>
<view class="container">
	<block v-if="isload">
	<form @submit="formSubmit">
		<view class="myscore" :style="{background:t('color1')}">
			<view class="f1">我的{{t('新积分')}}</view>
			<view class="f2">{{my_new_score}}</view>
			<view class="f1">我的{{t('积分')}}数量：{{my_score}}</view>
		</view>
		<view class="content2">
			<view class="item2"><view class="f1">转入数量</view></view>
			<view class="item3"><view class="f2"><input class="input" type="number" name="integral" value="" placeholder="请输入转入数量" placeholder-style="color:#999;font-size:36rpx" @input="moneyinput"></input></view></view>
			<view class="item4" v-if="!isNull(set.to_score_desc)">
				<text style="margin-right:10rpx">{{set.to_score_desc}}</text>
			</view>
		</view>
		<button class="btn" :style="{background:t('color1')}" form-type="submit">确定</button>
		<view class='text-center' @tap="goto" data-url='/pages/my/usercenter'><text>返回{{t('会员')}}中心</text></view>
	</form>
	</block>
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
		my_score: 0,
		my_new_score:0,
		set:{},
		id:0
    };
  },

  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		var that = this;
		that.id = that.opt.id || 0;
		// app.checkLogin();
		uni.setNavigationBarTitle({
			title: '提取'
		});
		this.getdata();
  },
	onPullDownRefresh: function () {
		this.getdata();
	},
	onReachBottom: function () {
		this.getdata();
	},
  methods: {
		getdata: function () {
			var that = this;
			var st = that.st;
			that.loading = true;
		  app.get('ApiNewScore/to_score', {id:that.id}, function (res) {
			that.loading = false;
			if(res.status){
				uni.setNavigationBarTitle({
					title: '转'+that.t('积分')
				});
				that.set = res.set;
				that.my_score = res.my_score;
				that.my_new_score = res.my_new_score;
				that.set = res.set;
				that.loaded();
			}else{
				app.error(res.msg);
				return;
			}
			
		    
		  });
		},
    moneyinput: function (e) {
      var money = e.detail.value;
      
    },
    formSubmit: function (e) {
		var that = this;
		var new_score = parseFloat(e.detail.value.integral);
		if (isNaN(new_score) || new_score <= 0) {
			app.error('数量必须大于0');
			return;
		}
		if (new_score < 0) {
			app.error('数量必须大于0');return;
		}
		app.confirm('确定要转入吗？', function(){
			app.showLoading();
			app.post('ApiNewScore/to_score', {new_score: new_score,id:that.id}, function (data) {
				app.showLoading(false);
				if (data.status == 1) {
					app.success(data.msg);
					that.subscribeMessage(function () {
						setTimeout(function () {
							app.goto('/pages/my/usercenter');
						}, 1000);
					});
				} else {
					app.error(data.msg);
					return;
				}
			}, '提交中');
		})
    }
  }
};
</script>
<style>
	.myscore{width:94%;margin:20rpx 3%;border-radius: 10rpx 56rpx 10rpx 10rpx;position:relative;display:flex;flex-direction:column;padding:70rpx 0}
	.myscore .f1{margin:0 0 0 60rpx;color:rgba(255,255,255,0.8);font-size:24rpx;}
	.myscore .f2{margin:20rpx 0 0 60rpx;color:#fff;font-size:64rpx;font-weight:bold; position: relative;}
	
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