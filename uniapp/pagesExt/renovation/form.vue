<template>
<view class="container" v-if="isload">
	<image :src="info.banner" style="width:100%;height:auto;display:table" mode="widthFix" v-if="info.banner"/>
	<form @submit="formSubmit">
	<view class="content" :style="{background:'linear-gradient(180deg,'+info.bgcolor+' 0%,rgba('+info.bgcolorrgb.red+','+info.bgcolorrgb.green+','+info.bgcolorrgb.blue+',0) 100%)'}">
		<view class="form">
			<image :src="pre_url+'/static/img/renovation_calculator/form_price.gif'" mode="widthFix" class="form_price" alt="" />
			<view class="form_option">
				<text>客厅 ?? 元</text>
				<text>厨房 ?? 元</text>
				<text>卧室 ?? 元</text>
			</view>
			<view>
				<view class="form_item" style="padding:10rpx 10rpx">
					<uni-data-picker :localdata="items" :border="false" :placeholder="regiondata || '请选择地区'" @change="regionchange" style="width:100%"></uni-data-picker>
				</view>
				<view class="form_item">
					<input class="form_input" type="number" value="90" name="mianji"/>
					<text>㎡</text>
				</view>
			</view>
			<button class="form-btn" form-type="submit" :style="{background:'linear-gradient(180deg,'+info.bgcolor+' 0%,rgba('+info.bgcolorrgb.red+','+info.bgcolorrgb.green+','+info.bgcolorrgb.blue+',0.8) 100%)'}">立即估算报价</button>

			<view class="xieyi-item" v-if="info.xystatus==1">
				<checkbox-group @change="isagreeChange"><label class="flex-y-center"><checkbox class="checkbox" value="1" :checked="isagree"/>我已阅读并同意</label></checkbox-group>
				<text :style="{color:info.bgcolor}" @tap="showxieyiFun">《用户使用协议》</text>
			</view>
		</view>
	</view>
	<view v-if="showxieyi" class="xieyibox">
		<view class="xieyibox-content">
			<view style="overflow:scroll;height:100%;">
				<parse :content="info.xieyi" @navigate="navigate"></parse>
			</view>
			<view style="position:absolute;z-index:9999;bottom:10px;left:0;right:0;margin:0 auto;text-align:center; width: 50%;height: 60rpx; line-height: 60rpx; color: #fff; border-radius: 8rpx;" :style="{background:'linear-gradient(90deg,'+info.bgcolor+' 0%,rgba('+info.bgcolorrgb.red+','+info.bgcolorrgb.green+','+info.bgcolorrgb.blue+',0.8) 100%)'}"  @tap="hidexieyi">已阅读并同意</view>
		</view>
	</view>
	</form>
	<view class="qd_guize">
		<!-- <view class="gztitle"> — 兑换规则 — </view> -->
		<view class="guize_txt">
			<parse :content="info.description" />
		</view>
	</view>
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
			pre_url:app.globalData.pre_url,
			items:[],
			showxieyi:false,
			isagree:false,
			info:{},
      regiondata: '',
		}
	},
	onLoad: function (opt) {
		this.opt = app.getopts(opt);
		var that = this;
		app.get('ApiIndex/getCustom',{}, function (customs) {
			var url = app.globalData.pre_url+'/static/area.json';
			uni.request({
				url: url,
				data: {},
				method: 'GET',
				header: { 'content-type': 'application/json' },
				success: function(res2) {
					that.items = res2.data
				}
			});
		});

		this.getdata();
	},
	onPullDownRefresh: function () {
		this.getdata();
	},
	methods: {
		getdata:function(){
			var that = this;
			that.loading = true;
			app.get('ApiRenovationCalculator/form',{},function (res){
				that.info = res.info;
				uni.setNavigationBarTitle({
					title: that.info.name
				});
				that.loaded();
			});
		},
		regionchange(e) {
			const value = e.detail.value
			console.log(value[0].text + ',' + value[1].text + ',' + value[2].text);
			this.regiondata = value[0].text + ',' + value[1].text + ',' + value[2].text
		},
    isagreeChange: function (e) {
      var val = e.detail.value;
      if (val.length > 0) {
        this.isagree = true;
      } else {
        this.isagree = false;
      }
      console.log(this.isagree);
    },
    showxieyiFun: function () {
      this.showxieyi = true;
    },
    hidexieyi: function () {
      this.showxieyi = false;
			this.isagree = true;
			if(this.wxloginclick){
				this.weixinlogin();
			}
			if(this.iosloginclick){
				this.ioslogin();
			}
    },
		formSubmit(e) {
			var that = this;
      var formdata = e.detail.value;
			formdata.regiondata = that.regiondata;
      if (formdata.regiondata == ''){
        app.error('请选择地区');
        return;
      }
			if (formdata.mianji == ''){
        app.error('请填写面积');
        return;
      }
			if (that.info.xystatus == 1 && !that.isagree) {
				app.error('请先阅读并同意用户使用协议');
				return false;
			}
			app.goto('result?region='+formdata.regiondata+'&mianji='+formdata.mianji);
		},
		agreementClick() {
			if (this.agreementState) {
				this.agreementState = false;
			} else {
				this.agreementState = true;
			}
		}
	}
};
</script>
<style>
	page {
		background: #f0f0f0;
	}

	.content {
		width: 750rpx;
		padding-top: 30rpx;
		padding-bottom:30rpx;
	}

	.form {
		padding: 35rpx;
		width: 710rpx;
		position: relative;
		border-radius: 35rpx;
		background: #fff;
		box-sizing: border-box;
		margin: 0 auto;
	}

	.form_price {
		width: 100%;
		display: block;
	}

	.form_option {
		padding: 30rpx 15rpx;
		font-size: 26rpx;
		font-weight: bold;
		display: flex;
		justify-content: space-between;
	}

	.form_item {
		font-size: 30rpx;
		padding: 30rpx;
		background: #f6f7f9;
		border-radius: 10rpx;
		margin-top: 20rpx;
		display: flex;
		justify-content: space-between;
		align-items: center;
	}

	.form_input {
		flex: 1;
	}

	.form_item img {
		height: 40rpx;
		width: 40rpx;
	}

	.form_item:first-child {
		margin-top: 0;
	}

	.form_btn {
		width: 100%;
		display: block;
		margin-top: 30rpx;
	}

	.agreement {
		position: relative;
		margin-top: 25rpx;
		padding: 0 20rpx;
	}

	.agreement_icon {
		height: 23rpx;
		width: 23rpx;
		margin: 5rpx 10rpx 0 0;
		display: block;
	}

	.agreement_text {
		font-size: 22rpx;
		color: #999;
	}

	.agreement_tag {
		color: #26c256;
	}

	.select {
		position: fixed;
		height: 100%;
		width: 100%;
		top: 0;
		left: 0;
		display: flex;
		justify-content: flex-end;
		background: rgba(0, 0, 0, 0.7);
	}

	.select_hide {
		position: absolute;
		height: 100%;
		width: 100%;
		top: 0;
		left: 0;
	}

	.select_body {
		position: relative;
		height: 100%;
		width: 600rpx;
		display: flex;
		border-top: 1rpx solid #f0f0f0;
		box-sizing: border-box;
		background: #fff;
	}

	.select_module {
		flex: 1;
		height: 100%;
	}

	.select_item {
		font-size: 26rpx;
		color: #333;
		height: 90rpx;
		text-align: center;
		line-height: 90rpx;
		margin-top: -2rpx;
		border-top: 1rpx solid #f0f0f0;
		border-right: 1rpx solid #f0f0f0;
		border-bottom: 1rpx solid #f0f0f0;
	}

	.select_active {
		background: #f8f8f8;
		color: #26c165;
	}

.qd_guize{width:100%;margin:30rpx 0 20rpx 0;}
.qd_guize .gztitle{width:100%;text-align:center;font-size:32rpx;color:#656565;font-weight:bold;height:100rpx;line-height:100rpx}
.guize_txt{box-sizing: border-box;padding:0 30rpx;line-height:42rpx;}

.form-btn{margin-top:60rpx;width:100%;height:96rpx;line-height:96rpx;color:#fff;font-size:30rpx;border-radius:8rpx;}
	
.xieyi-item{display:flex;align-items:center;margin-top:30rpx}
.xieyi-item{font-size:24rpx;color:#B2B5BE}
.xieyi-item .checkbox{transform: scale(0.6);}

.xieyibox{width:100%;height:100%;position:fixed;top:0;left:0;z-index:99;background:rgba(0,0,0,0.7)}
.xieyibox-content{width:90%;margin:0 auto;height:80%;margin-top:20%;background:#fff;color:#333;padding:5px 10px 50px 10px;position:relative;border-radius:2px}
</style>
