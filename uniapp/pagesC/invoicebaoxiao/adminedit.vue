<template>
<view>
	<block v-if="isload">
		<form @submit="subform">
			<view class="form-box">
				<view class="form-item">
					<view>发票真伪<text style="color:red"> *</text></view>
					<view>
						<radio-group class="radio-group" name="authenticity" @change="authenticityChange">
							<label><radio value="1" :checked="info.authenticity==1?true:false"></radio> 发票为真</label>
							<label><radio value="-1" :checked="info.authenticity==-1?true:false"></radio> 发票存疑</label>
							<label><radio value="0" :checked="info.authenticity==0?true:false"></radio> 待辩真伪</label>
						</radio-group>
					</view>
				</view>
				<view class="form-item" style="height:80rpx;line-height:80rpx">
					<view class="f1">扣除积分<text style="color:red"> *</text></view>
					<view class="f2">
						<input type="digit" name="deduct_score" :value="info.deduct_score" placeholder="请填写扣除积分" placeholder-style="color:#888"></input>
					</view>
				</view>
				<view class="form-item" style="height:80rpx;line-height:80rpx">
					<view class="f1">报销金额<text style="color:red"> *</text></view>
					<view class="f2">
						<input type="digit" name="money" :value="info.money" placeholder="请填写报销金额" placeholder-style="color:#888"></input>
					</view>
				</view>
				<view class="form-item">
					<view>支付方式<text style="color:red"> *</text></view>
					<view>
						<radio-group class="radio-group" name="paytype" @change="paytypeChange">
							<label><radio value="支付宝" :checked="info.paytype=='支付宝'?true:false"></radio> 支付宝</label> 
							<label><radio value="银行卡" :checked="info.paytype=='银行卡'?true:false"></radio> 银行卡</label>
							<label><radio value="收款码" :checked="info.paytype=='收款码'?true:false"></radio> 收款码</label>
						</radio-group>
					</view>
				</view>
			</view>
			<!-- 支付宝 -->
			<view class="form-box" v-if="info.paytype=='支付宝'">
				<view class="form-item" style="height:80rpx;line-height:80rpx">
					<view class="f1">真实姓名<text style="color:red"> *</text></view>
					<view class="f2">
						<input type="text" name="aliaccountname" :value="info.aliaccountname" placeholder="请填写真实姓名" placeholder-style="color:#888"></input>
					</view>
				</view>
				<view class="form-item" style="height:80rpx;line-height:80rpx">
					<view class="f1">支付宝账号<text style="color:red"> *</text></view>
					<view class="f2">
						<input type="text" name="aliaccount" :value="info.aliaccount" placeholder="请填写支付宝账号" placeholder-style="color:#888"></input>
					</view>
				</view>
			</view>
			<!-- 银行卡 -->
			<view class="form-box" v-if="info.paytype=='银行卡'">
				<view class="form-item" style="height:80rpx;line-height:80rpx">
					<view class="f1">开户行<text style="color:red"> *</text></view>
					<view class="f2">
						<picker class="picker" mode="selector" name="bankname" value="0" :range="banklist" @change="bindBanknameChange">
							<view v-if="bankname">{{bankname}}</view>
							<view v-else>请选择开户行</view>
						</picker>
					</view>
				</view>
				<view class="form-item" style="height:80rpx;line-height:80rpx">
					<view class="f1">所属分支行<text style="color:red"> *</text></view>
					<view class="f2">
						<input type="text" name="bankaddress" :value="info.bankaddress" placeholder="请填写所属分支行" placeholder-style="color:#888"></input>
					</view>
				</view>
				<view class="form-item" style="height:80rpx;line-height:80rpx">
					<view class="f1">持卡人姓名<text style="color:red"> *</text></view>
					<view class="f2">
						<input type="text" name="bankcarduser" :value="info.bankcarduser" placeholder="请填写持卡人姓名" placeholder-style="color:#888"></input>
					</view>
				</view>
				<view class="form-item" style="height:80rpx;line-height:80rpx">
					<view class="f1">银行卡号<text style="color:red"> *</text></view>
					<view class="f2">
						<input type="text" name="bankcardnum" :value="info.bankcardnum" placeholder="请填写银行卡号" placeholder-style="color:#888"></input>
					</view>
				</view>
			</view>
			<!-- 收款码 -->
			<view class="form-box" v-if="info.paytype=='收款码'">
				<view class="form-item flex-col" style="border-bottom:0">
					<view class="f1">微信收款码</view>
					<view class="f2">
						<view v-for="(item, index) in wxpaycode" :key="index" class="layui-imgbox">
							<view class="layui-imgbox-close" @tap="removeimg" :data-index="index" data-field="wxpaycode">
								<image style="display:block" :src="pre_url+'/static/img/ico-del.png'"></image>
							</view>
							<view class="layui-imgbox-img">
								<image :src="item" @tap="previewImage" :data-url="item" mode="widthFix"></image>
							</view>
						</view>
						<view class="uploadbtn" :style="'background:url('+pre_url+'/static/img/shaitu_icon.png) no-repeat 60rpx;background-size:80rpx 80rpx;background-color:#F3F3F3;'" @tap="uploadimg" data-field="wxpaycode" data-pernum="1" v-if="wxpaycode.length==0"></view>
					</view>
					<input type="text" hidden="true" name="wxpaycode" :value="wxpaycode.join(',')" maxlength="-1"/>
				</view>
				<view class="form-item flex-col" style="border-bottom:0">
					<view class="f1">支付宝收款码</view>
					<view class="f2">
						<view v-for="(item, index) in alipaycode" :key="index" class="layui-imgbox">
							<view class="layui-imgbox-close" @tap="removeimg" :data-index="index" data-field="alipaycode">
								<image style="display:block" :src="pre_url+'/static/img/ico-del.png'"></image>
							</view>
							<view class="layui-imgbox-img">
								<image :src="item" @tap="previewImage" :data-url="item" mode="widthFix"></image>
							</view>
						</view>
						<view class="uploadbtn" :style="'background:url('+pre_url+'/static/img/shaitu_icon.png) no-repeat 60rpx;background-size:80rpx 80rpx;background-color:#F3F3F3;'" @tap="uploadimg" data-field="alipaycode" data-pernum="1" v-if="alipaycode.length==0"></view>
					</view>
					<input type="text" hidden="true" name="alipaycode" :value="alipaycode.join(',')" maxlength="-1"/>
				</view>
			</view>
					
			<button class="savebtn" :style="'background:linear-gradient(90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'" form-type="submit">提交</button>
			<view style="height:50rpx"></view>
		</form>

	
	</block>
	<loading v-if="loading"></loading>
	<popmsg ref="popmsg"></popmsg>
	<wxxieyi></wxxieyi>
</view>
</template>

<script>
var app = getApp();

export default {
  data() {
    return {
			isload:false,
			loading:false,
			pre_url:app.globalData.pre_url,
      info:{},
			banklist: ['工商银行', '农业银行', '中国银行', '建设银行', '招商银行', '邮储银行', '交通银行', '浦发银行', '民生银行', '兴业银行', '平安银行', '中信银行', '华夏银行', '广发银行', '光大银行', '北京银行', '宁波银行'],
			bankname: '',
			wxpaycode:[],
			alipaycode:[]
    };
  },

  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		if(opt.cids) this.cidsArr = opt.cids;
		this.getdata();
  },
  methods: {
		getdata:function(){
			var that = this;
			var id = that.opt.id ? that.opt.id : '';
			that.loading = true;
			app.get('ApiAdminInvoiceBaoxiao/edit',{id:id}, function (res) {
				that.loading = false;
				that.info = res.data;
				that.wxpaycode = that.info.wxpaycode ? [that.info.wxpaycode] : [];
				that.alipaycode = that.info.alipaycode ? [that.info.alipaycode] : [];
				if(that.info && that.info.bankname){
					that.bankname = that.info.bankname
				}
				that.loaded();
			});
		},
		subform: app.Debounce(function (e) {
      var that = this;
      var formdata = e.detail.value;
      if(that.bankname){
        formdata.bankname = that.bankname
      }
      // 验证输入值是否为整数
      if (!this.isInteger(formdata.deduct_score)) {
        uni.showToast({
          title: '扣除积分必须为整数',
          icon: 'none'
        });
        return;
      }
      app.post('ApiAdminInvoiceBaoxiao/save', {id:that.info.id,info:formdata}, function (res) {
        if (res.status == 0) {
          app.error(res.msg);
        } else {
          app.success(res.msg);
          setTimeout(function () {
            let url = `adminrecorddetail?id=${that.info.id}`;
            app.goto(url, 'redirect');
          }, 1000);
        }
      });
    },1000),
		uploadimg:function(e){
			var that = this;
			var field= e.currentTarget.dataset.field
			var pics = that[field]
			if(!pics) pics = [];
			app.chooseImage(function(urls){
				for(var i=0;i<urls.length;i++){
					pics.push(urls[i]);
				}
			},1);
		},
		removeimg:function(e){
			var that = this;
			var index= e.currentTarget.dataset.index
			var field= e.currentTarget.dataset.field
			var pics = that[field]
			pics.splice(index,1)
		},
		
		bindBanknameChange: function (e) {
		  this.bankname = this.banklist[e.detail.value];
		},
		authenticityChange:function(e){
			this.info.authenticity = e.detail.value;
		},
		paytypeChange:function(e){
			this.info.paytype = e.detail.value;
		},
		isInteger(value) {
			return /^(0|[1-9]\d*)$/.test(value); // 允许 0 和 正整数
		}
  }
};
</script>
<style>
radio{transform: scale(0.6);}
.form-box{ padding:2rpx 24rpx 0 24rpx; background: #fff;margin: 24rpx;border-radius: 10rpx}
.form-item{ line-height: 100rpx; display: flex;justify-content: space-between;border-bottom:1px solid #eee }
.form-item .f1{color:#222;width:200rpx;flex-shrink:0}
.form-item .f2{display:flex;align-items:center}
.form-box .form-item:last-child{ border:none}
.form-box .flex-col{padding-bottom:20rpx}
.form-item input{ width: 100%; border: none;color:#111;font-size:28rpx; text-align: right}
.form-item textarea{ width:100%;min-height:200rpx;padding:20rpx 0;border: none;}
.form-item .upload_pic{ margin:50rpx 0;background: #F3F3F3;width:90rpx;height:90rpx; text-align: center  }
.form-item .upload_pic image{ width: 32rpx;height: 32rpx; }
.savebtn{ width: 90%; height:96rpx; line-height: 96rpx; text-align:center;border-radius:48rpx; color: #fff;font-weight:bold;margin: 0 5%; margin-top:60rpx; border: none; }

.layui-imgbox{margin-right:16rpx;margin-bottom:10rpx;font-size:24rpx;position: relative;}
.layui-imgbox-img{display: block;width:200rpx;height:200rpx;padding:2px;border: #d3d3d3 1px solid;background-color: #f6f6f6;overflow:hidden}
.layui-imgbox-img>image{max-width:100%;}
.layui-imgbox-repeat{position: absolute;display: block;width:32rpx;height:32rpx;line-height:28rpx;right: 2px;bottom:2px;color:#999;font-size:30rpx;background:#fff}
.uploadbtn{position:relative;height:200rpx;width:200rpx}

</style>