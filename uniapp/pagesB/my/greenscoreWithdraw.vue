<template>
<view class="container">
	<block v-if="isload">
	<form @submit="formSubmit">
		<view class="myscore" :style="{background:t('color1')}">
			<view class="f1">可提取金额(元)</view>
			<view class="f2">{{canwithdraw_num}}</view>
			<view class="f1">当前每个{{t('绿色积分')}}价值{{set.green_score_price}}元</view>
			<view class="f1">我的{{t('绿色积分')}}数量：{{myscore}}</view>
			<view class="f1" v-if="green_score_hb>0">已领增值红包：{{green_score_hb}}元</view>
		</view>
		<view class="content2">
			<view class="item2"><view class="f1">提取金额</view></view>
			<view class="item3"><view class="f2"><input class="input" type="number" name="integral" :disabled="isreadonly" :value="isreadonly==true?canwithdraw_num:0" placeholder="请输入提取金额" placeholder-style="color:#999;font-size:36rpx" @input="moneyinput"></input></view></view>
			<view class="item4">
				<text style="margin-right:10rpx">{{withdraw_desc}}</text>
			</view>
		</view>
		<button class="btn" :style="{background:t('color1')}" form-type="submit">提交</button>
		<view class='text-center' @tap="goto" data-url='/pages/my/usercenter'><text>返回{{t('会员')}}中心</text></view>
	</form>
	</block>
	<view class="content" id="datalist" v-if="datalist.length>0">
		<view class="item">
			<view class="f1">
				<text class="t2" style="font-size:32rpx;">涨幅</text>
			</view>
			<view class="f2">
				<text class="t2">价值</text>
			</view>
		</view>
		<block v-for="(item, index) in datalist" :key="index"> 
		<view class="item">
			<view class="f1">
				<text class="t1" v-if="item.dif_price>0" style="color: red;">+ {{item.dif_price}}</text>
				<text class="t1" v-if="item.dif_price==0">{{item.dif_price}}</text>
				<text class="t1" v-if="item.dif_price<0" style="color: green;">{{item.dif_price}}</text>
				<text class="t2">{{item.createtime}}</text>
			</view>
			<view class="f2">
				<block>
					<text class="t2">{{item.green_score_price}}</text>
				</block>
			</view>
		</view>
	</block>
	<nodata v-if="nodata"></nodata>
	</view>
	<nomore v-if="nomore"></nomore>
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
		myscore: 0,
		withdraw_desc:'',
		set:{},
		canwithdraw_num:0,
		pagenum: 1,
		nodata: false,
		nomore: false,
		datalist: [],
		green_score_hb:0,
		log_id:0,
		log_info:{},
		isreadonly:false
    };
  },

  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		var that = this;
		// app.checkLogin();
		uni.setNavigationBarTitle({
			title: '提取'
		});
		console.log(that.opt);
		that.log_id = that.opt.log_id || 0;
		this.getdata();
  },
	onPullDownRefresh: function () {
		this.getdata();
	},
	onReachBottom: function () {
	  if (!this.nodata && !this.nomore) {
			this.pagenum = this.pagenum + 1;
			this.getdata(true);
	  }
	},
  methods: {
		getdata: function (loadmore) {
			if(!loadmore){
				this.pagenum = 1;
				this.datalist = [];
			}
			var that = this;
			var pagenum = that.pagenum;
			var st = that.st;
			that.nodata = false;
			that.nomore = false;
			that.loading = true;
		  app.post('ApiGreenScore/pricelog', {st: st,pagenum: pagenum,log_id:that.log_id}, function (res) {
			that.loading = false;
			if(res.status!=1){
				app.error(res.msg);
				return;
			}
		    var data = res.data;
		    if (pagenum == 1) {
				that.datalist = data;
				if (data.length == 0) {
					that.nodata = true;
				}
				that.set = res.set;
				that.myscore = res.myscore;
				that.withdraw_desc = res.withdraw_desc;
				that.set = res.set;
				that.canwithdraw_num = res.canwithdraw_num;
				that.green_score_hb = res.green_score_hb;
				that.log_info = res.log_info;
				that.isreadonly = res.isreadonly;
				that.loaded();
		    }else{
				if (data.length == 0) {
					that.nomore = true;
				} else {
					var datalist = that.datalist;
					var newdata = datalist.concat(data);
					that.datalist = newdata;
				}
		    }
		  });
		},
    moneyinput: function (e) {
      var money = e.detail.value;
      
    },
    formSubmit: function (e) {
		var that = this;
		var money = parseFloat(e.detail.value.integral);
		if (isNaN(money) || money <= 0) {
			app.error('数量必须大于0');
			return;
		}
		if (money < 0) {
			app.error('数量必须大于0');return;
		} else if (parseFloat(money) > parseFloat(that.canwithdraw_num)) {
			console.log(money);
			console.log(that.canwithdraw_num);
			console.log(money > that.canwithdraw_num);
			app.error('可提取金额不足');return;
		}
		app.confirm('确定要提取吗？', function(){
			app.showLoading();
			app.post('ApiGreenScore/scoreWithdraw', {money: money,log_id:that.log_id}, function (data) {
				app.showLoading(false);
				if (data.status == 0) {
					app.error(data.msg);
					return;
				} else {
					app.success(data.msg);
					that.subscribeMessage(function () {
						setTimeout(function () {
							app.goto('/pages/my/usercenter');
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

.content{ width:94%;margin:0 3%;}
.content .item{width:100%;margin:20rpx 0;background:#fff;border-radius:5px;padding:20rpx 20rpx;display:flex;align-items:center}
.content .item .f1{flex:1;display:flex;flex-direction:column}
.content .item .f1 .t1{color:#000000;font-size:32rpx;word-break:break-all;overflow:hidden;text-overflow:ellipsis;}
.content .item .f1 .t2{color:#666666}
.content .item .f1 .t3{color:#666666}
.content .item .f2{ flex:1;font-size:32rpx;text-align:right}
.content .item .f2 .t1{color:#03bc01}
.content .item .f2 .t2{color:#666666}
.content .item .f3{ flex:1;font-size:32rpx;text-align:right}
.content .item .f3 .t1{color:#03bc01}
.content .item .f3 .t2{color:#000000}
</style>