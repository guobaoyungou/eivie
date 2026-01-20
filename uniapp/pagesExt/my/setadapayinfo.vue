<template>
<view class="container">
	<block v-if="isload">
		<form @submit="formSubmit" @reset="formReset">
		<view class="form">
			<!-- <view class="form-item">
					<text class="label">开户行</text>
					<picker class="picker" mode="selector" name="bankname" value="0" :range="banklist" @change="bindBanknameChange">
						<view v-if="bankname">{{bankname}}</view>
						<view v-else>请选择开户行</view>
					</picker>
			</view> -->
			<view class="form-item">
					<text class="label">银行卡号</text>
					<input type="text" class="input" placeholder="请输入银行卡号" name="bankcardnum" :value="bankcardnum" placeholder-style="color:#BBBBBB;font-size:28rpx"></input>
			</view>
			<view class="form-item">
					<text class="label">手机号</text>
					<input type="text" class="input" placeholder="请输入手机号" name="tel_no" :value="tel_no" placeholder-style="color:#BBBBBB;font-size:28rpx"></input>
			</view>
			<view class="form-item">
					<text class="label">姓名</text>
					<input type="text" class="input" placeholder="请输入姓名" name="realname" :value="realname" placeholder-style="color:#BBBBBB;font-size:28rpx"></input>
			</view>
			<view class="form-item">
					<text class="label">身份证号</text>
					<input type="text" class="input" placeholder="请输入身份证号" name="idcard" :value="idcard" placeholder-style="color:#BBBBBB;font-size:28rpx"></input>
			</view>
			<!-- <view class="form-item" v-if="zhaoshang_show">
					<text class="label">招商银行码</text>
					<input type="text" class="input" placeholder="请输入招商银行码" name="protocol_no" :value="userinfo.protocol_no" placeholder-style="color:#BBBBBB;font-size:28rpx"></input>
			</view> -->
		</view>
		<button class="set-btn" form-type="submit" :style="{background:'linear-gradient(90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'}">保 存</button>
		</form>
	</block>
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
		banklist: ['工商银行', '农业银行', '中国银行', '建设银行', '交通银行', '中信银行', '光大银行', '华夏银行', '民生银行', '广发银行', '招商银行', '兴业银行', '浦发银行', '平安银行', '邮储银行','莱商银行', '北京银行','天津银行','上海银行','重庆银行','包商银行','盛京银行','大连银行','哈尔滨银行','南京银行','杭州银行', '宁波银行','浙江民泰商业银行','泉州银行','江西银行','江西银行','齐鲁银行','青岛银行','临商银行','齐商银行','汉口银行','广州银行','东莞银行','乌鲁木齐银行','浙商银行','渤海银行','北京农商银行','上海农商银行','兰州银行','青海银行','甘肃银行','贵州银行','锦州银行','日照银行','河北银行','潍坊银行','威海商业银行','珠海华润银行','贵阳银行','宁夏银行','无锡农商行','广州农商行'],
		bankname: '',
		protocol_no:'',
		tel_no:'',
		realname:'',
		idcard:'',
		bankcardnum:'',
		zhaoshang_show:0,
		textset:{},
		smscode_show:0,
		smscode:''
    };
  },

  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.isload = true
		this.getdata();
  },
	onPullDownRefresh: function () {
		this.getdata();
	},
  methods: {
		getdata: function () {
			var that = this;
			that.loading = true;
			app.get('ApiMy/getAdapay', {}, function (res) {
				that.loading = false;
				that.bankname = res.data.bank_name;
				that.bankcardnum = res.data.card_id;
				that.tel_no = res.data.tel_no;
				that.realname = res.data.realname;
				that.idcard = res.data.idcard;
				that.smscode_show = res.smscode_show
				that.loaded();
			});
			
		},
    formSubmit: function (e) {
		var that = this;
		var formdata = e.detail.value;
		var realname = formdata.realname
		var idcard = formdata.idcard
		var bankcardnum = formdata.bankcardnum
		var tel_no = formdata.tel_no
		if (bankcardnum == '') {
			app.alert('请输入银行卡号');return;
		}
		if (realname == '') {
			app.alert('请输入姓名');return;
		}
		if (tel_no == '') {
			app.alert('请输入手机号');return;
		}
		if (idcard == '') {
			app.alert('请输入身份证号');return;
		}
		app.showLoading('提交中');
		app.post("ApiMy/setAdapay", {bankcardnum:bankcardnum,tel_no:tel_no,realname:realname,idcard:idcard}, function (data) {
			app.showLoading(false);
			if (data.status == 1) { 
				app.success(data.msg);
				setTimeout(function () {
					app.goback(true);
				}, 1000);
			}else {
				app.error(data.msg);
			}
		});
    }
  }
};
</script>
<style>
.form{ width:94%;margin:20rpx 3%;border-radius:5px;padding:20rpx 20rpx;padding: 0 3%;background: #FFF;}
.form-item{display:flex;align-items:center;width:100%;border-bottom: 1px #ededed solid;height:98rpx;line-height:98rpx;}
.form-item:last-child{border:0}
.form-item .label{color: #000;width:200rpx;}
.form-item .input{flex:1;color: #000;}
.form-item .picker{height: 60rpx;line-height:60rpx;margin-left: 0;flex:1;color: #000;}
.set-btn{width: 90%;margin:60rpx 5%;height:96rpx;line-height:96rpx;border-radius:48rpx;color:#FFFFFF;font-weight:bold;}
.smscode{ position: fixed;z-index: 9;width: 100%;height: 100%;background: rgba(0,0,0,0.8);top: 0px;left: 0;}
.smscode .close {position: absolute;padding: 20rpx;	top: 0;	right: 0;}
.smscode .main {width: 80%;margin: 40% 10% 30rpx 10%;background: #fff;position: relative;border-radius: 20rpx;}
.smscode .content {width: 100%;padding: 70rpx 20rpx 30rpx 20rpx;color: #333;font-size: 30rpx;text-align: center;}
.smscode .close .img {width: 32rpx;height: 32rpx;}
.smscode .content .form-item{display:flex;align-items:center;width:100%;border-bottom: 1px #ededed solid;height:98rpx;line-height:98rpx;}
.smscode .content .form-item:last-child{border:0}
.smscode .content .form-item .label{color: #000;width:200rpx;}
.smscode .content .form-item .input{flex:1;color: #000;}
.smscode .content .form-item .picker{height: 60rpx;line-height:60rpx;margin-left: 0;flex:1;color: #000;}
.smscode .content .form-item .code{color:#06A051}
</style>