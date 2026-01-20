<template>
<view class="container">
	<block v-if="isload">
		<form @submit="formSubmit">
			<view class="form">
				<view class="form-item">
						<text class="label">开户行</text>
						<picker class="picker" mode="selector" name="bankname" value="0" :range="banklist" @change="bindBanknameChange">
							<view v-if="bankname">{{bankname}}</view>
							<view v-else>请选择开户行</view>
						</picker>
				</view>
				<view class="form-item">
						<text class="label">所属分支行</text>
						<input type="text" class="input" placeholder="请输入分支行" name="bankaddress" :value="data.bankaddress" placeholder-style="color:#BBBBBB;font-size:28rpx"></input>
				</view>
				<view class="form-item">
						<text class="label">持卡人姓名</text>
						<input type="text" class="input" placeholder="请输入持卡人姓名" name="bankcarduser" :value="data.bankcarduser" placeholder-style="color:#BBBBBB;font-size:28rpx"></input>
				</view>
				<view class="form-item">
						<text class="label">银行卡号</text>
						<input type="text" class="input" placeholder="请输入银行卡号" name="bankcardnum" :value="data.bankcardnum" placeholder-style="color:#BBBBBB;font-size:28rpx"></input>
				</view>
			</view>
			<button class="savebtn" :style="'background:linear-gradient(90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'" form-type="submit">保 存</button>
		</form>
	</block>
	<loading v-if="loading"></loading>
	<dp-tabbar :opt="opt"></dp-tabbar>
	<popmsg ref="popmsg"></popmsg>
	<wxxieyi></wxxieyi>
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
		  banklist: ['工商银行', '农业银行', '中国银行', '建设银行', '招商银行', '邮储银行', '交通银行', '浦发银行', '民生银行', '兴业银行', '平安银行', '中信银行', '华夏银行', '广发银行', '光大银行', '北京银行', '宁波银行'],
			bankinfo:[],
			data:[],
			bankname: '',
    };
  },

  onLoad: function (opt) {
		this.opt = app.getopts(opt);
    this.type = this.opt.type || 0;
		var that = this;
    this.getdata();
  },
	onPullDownRefresh: function () {
		this.getdata();
	},
  methods: {
		getdata:function(){
				var that = this;
				var bankId = that.opt.id || '';
				that.loading = true;
				app.get('ApiBank/bankadd', {id: bankId}, function (data) {
					that.loading = false;
					that.data = data.data;
					that.bankname = data.data.bankname
					that.loaded();
				});
		},
    formSubmit: function (e) {
      var that = this;
      var formdata = e.detail.value;
      var bankId = that.opt.id || '';
      var bankaddress = formdata.bankaddress;
      var bankcarduser = formdata.bankcarduser;
      var bankcardnum = formdata.bankcardnum;
			app.showLoading('提交中');
      app.post('ApiBank/bankadd', {bankcardnum: bankcardnum,bankaddress: bankaddress,bankcardnum: bankcardnum,bankname: that.bankname,bankcarduser:bankcarduser,bankId:bankId}, function (res) {
				app.showLoading(false);
        if (res.status == 0) {
          app.alert(res.msg);
          return;
        }
        app.success('保存成功');
        setTimeout(function () {
          app.goback(true);
        }, 1000);
      });
    },
    delAddress: function () {
      var that = this;
      var bankId = that.opt.id;
      app.confirm('确定要删除银行卡吗?', function () {
				app.showLoading('删除中');
        app.post('ApiBank/del', {bankid: bankId}, function () {
					app.showLoading(false);
          app.success('删除成功');
          setTimeout(function () {
            app.goback(true);
          }, 1000);
        });
      });
    },
	  bindBanknameChange: function (e) {
		  this.bankname = this.banklist[e.detail.value];
	  },
  }
};
</script>
<style>
.container{display:flex;flex-direction:column}

.addfromwx{width:94%;margin:20rpx 3% 0 3%;border-radius:5px;padding:20rpx 3%;background: #FFF;display:flex;align-items:center;color:#666;font-size:28rpx;}
.addfromwx .img{width:40rpx;height:40rpx;margin-right:20rpx;}
.form{ width:94%;margin:20rpx 3%;border-radius:5px;padding: 0 3%;background: #FFF;}
.form-item{display:flex;align-items:center;width:100%;border-bottom: 1px #ededed solid;height:98rpx;justify-content: space-between; }
.form-item:last-child{border:0}
.form-item .label{ color:#8B8B8B;font-weight:bold;height: 60rpx; line-height: 60rpx; text-align:left;width:160rpx;padding-right:20rpx}
.form-item .input{ flex:1;height: 60rpx; line-height: 60rpx;text-align:right}

.savebtn{ width: 90%; height:96rpx; line-height: 96rpx; text-align:center;border-radius:48rpx; color: #fff;font-weight:bold;margin: 0 5%; margin-top:60rpx; border: none; }


</style>