<template>
<view class="container">
	<block v-if="isload">
		<form @submit="formSubmit" @reset="formReset" report-submit="true">
			<view class="orderinfo">
				<view class="item">
					<text class="t1">订单编号</text>
					<text class="t2" user-select="true" selectable="true">{{detail.ordernum}}</text>
				</view>
				
				<view class="item">
					<text class="t1">可开票金额</text>
					<text class="t2 red">¥{{detail.totalprice}}</text>
				</view>
				<view class="item">
					<text class="t1">下单时间</text>
					<text class="t2">{{detail.createtime}}</text>
				</view>
			</view>
			<view class="orderinfo">
				<view class="item">
					<text class="t1">发票类型</text>
					<view class="t2">
						<block v-if="inputDisabled">
							<text v-if="invoice && invoice.type == 1">普通发票</text>
							<text v-if="invoice && invoice.type == 2">增值税专用发票</text>
						</block>
						<block v-else>
							<radio-group class="radio-group" @change="changeOrderType" name="invoice_type">
							<label class="radio" v-if="inArray(1,invoice_type)">
								<radio value="1" :checked="invoice_type_select == 1 ? true : false"></radio>普通发票
							</label>
							<label class="radio" v-if="inArray(2,invoice_type)">
								<radio value="2" :checked="invoice_type_select == 2 ? true : false"></radio>增值税专用发票
							</label>
							</radio-group>
						</block>
					 </view>
				</view>
				<view class="item">
					<text class="t1">抬头类型</text>
					<view class="t2">
						<block v-if="inputDisabled">
							<text v-if="invoice && invoice.name_type == 1">个人</text>
							<text v-if="invoice && invoice.name_type == 2">公司</text>
						</block>
						<block v-else>
							<radio-group class="radio-group" @change="changeNameType" name="name_type">
							<label class="radio">
								<radio value="1" :checked="name_type_select == 1 ? true : false" :disabled="name_type_personal_disabled ? true : false"></radio>个人
							</label>
							<label class="radio">
								<radio value="2" :checked="name_type_select == 2 ? true : false"></radio>公司
							</label>
							</radio-group>
						</block>
					</view>
				</view>
				<view class="item">
					<text class="t1">抬头名称</text>
					<input class="t2" type="text" placeholder="抬头名称" placeholder-style="font-size:28rpx;color:#BBBBBB" name="invoice_name" :disabled="inputDisabled" :value="invoice ? invoice.invoice_name : ''" ></input>
				</view>
				<view class="item" v-if="name_type_select == 2">
					<text class="t1">公司税号</text>
					<input class="t2" type="text" placeholder="公司税号" placeholder-style="font-size:28rpx;color:#BBBBBB" name="tax_no" :disabled="inputDisabled" :value="invoice ? invoice.tax_no : ''"></input>
				</view>
				<view class="item" v-if="invoice_type_select == 2">
					<text class="t1">注册地址</text>
					<input class="t2" type="text" placeholder="注册地址" placeholder-style="font-size:28rpx;color:#BBBBBB" name="address" :disabled="inputDisabled" :value="invoice ? invoice.address : ''"></input>
				</view>
				<view class="item" v-if="invoice_type_select == 2">
					<text class="t1">注册电话</text>
					<input class="t2" type="text" placeholder="注册电话" placeholder-style="font-size:28rpx;color:#BBBBBB" name="tel" :disabled="inputDisabled" :value="invoice ? invoice.tel : ''"></input>
				</view>
				<view class="item" v-if="invoice_type_select == 2">
					<text class="t1">开户银行</text>
					<input class="t2" type="text" placeholder="开户银行" placeholder-style="font-size:28rpx;color:#BBBBBB" name="bank_name" :disabled="inputDisabled" :value="invoice ? invoice.bank_name : ''"></input>
				</view>
				<view class="item" v-if="invoice_type_select == 2">
					<text class="t1">银行账号</text>
					<input class="t2" type="text" placeholder="银行账号" placeholder-style="font-size:28rpx;color:#BBBBBB" name="bank_account" :disabled="inputDisabled" :value="invoice ? invoice.bank_account : ''"></input>
				</view>
				<view class="item">
					<text class="t1">手机号</text>
					<input class="t2" type="text" placeholder="接收电子发票手机号" placeholder-style="font-size:28rpx;color:#BBBBBB" name="mobile" :disabled="inputDisabled" :value="invoice ? invoice.mobile : ''"></input>
				</view>
				<view class="item">
					<text class="t1">邮箱</text>
					<input class="t2" type="text" placeholder="接收电子发票邮箱" placeholder-style="font-size:28rpx;color:#BBBBBB" name="email" :disabled="inputDisabled" :value="invoice ? invoice.email : ''"></input>
				</view>
				<!-- <view class="item" v-if="pay_transfer_info.pay_transfer_desc">
					<text class="text-min">{{pay_transfer_info.pay_transfer_desc}}</text>
				</view> -->
				<view class="item">
					<text class="t1">开票状态</text>
					<text class="t2" v-if="invoice">{{invoice.status_label}}</text>
					<text class="t2" v-else>未申请</text>
				</view>
				<view class="item" v-if="invoice && invoice.check_remark">
					<text class="t1">审核备注</text>
					<text class="t2">{{invoice.check_remark}}</text>
				</view>
			</view>
			<button class="btn" v-if=" !invoice" form-type="submit" :style="{background:t('color1')}">确定</button>
			<button class="btn" v-if=" invoice && invoice.status != 1" form-type="submit" :style="{background:t('color1')}">修改</button>
			<view class="btn-a" @tap="back">返回上一步</view>
			<view style="padding-top:30rpx"></view>
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
				
				pics:[],
				detail:{},
				invoice:{},
				invoice_type:[],
				invoice_type_select:1,
				name_type_select:1,
				name_type_personal_disabled:false,
				inputDisabled:false
			}
		},
		onLoad: function (opt) {
			this.opt = app.getopts(opt);
			this.pre_url = app.globalData.pre_url;
			this.getdata();
		},
		onPullDownRefresh: function () {
			this.getdata();
		},
		methods: {
			getdata: function () {
				var that = this;
				var type = that.opt.type;
				that.loading = true;
				app.get('ApiOrder/invoice', {id: that.opt.orderid,type:type}, function (res) {
					that.loading = false;
					if (res.status == 0) {
						app.error(res.msg);
						return;
					}
					that.detail = res.detail;
					that.invoice = res.invoice;
					if(res.invoice) {
						that.invoice_type_select = res.invoice.type;
						that.name_type_select = res.invoice.name_type;
						if(that.invoice_type_select == 2) {
							that.name_type_personal_disabled = true;
						} else {
							that.name_type_personal_disabled = false;
						}
						if(res.invoice.status == 1) {
							that.inputDisabled = true;
						}
 					}else{
						if(app.inArray(1,res.invoice_type)){
							that.invoice_type_select = 1;
						}else if(app.inArray(2,res.invoice_type)){
							that.invoice_type_select = 2;
						}
					}
					that.invoice_type = res.invoice_type;
					
					that.loaded();
					//
				});
			},
    formSubmit: function (e) {
      var that = this;
      var id = that.opt.orderid;
			var type = that.opt.type;
			var formdata = e.detail.value;
			if(formdata.invoice_name == '') {
				app.error('请填写抬头名称');
				return;
			}
			if((formdata.name_type == 2 || formdata.invoice_type == 2) && formdata.tax_no == '') {
				///^[A-Z0-9]{15}$|^[A-Z0-9]{17}$|^[A-Z0-9]{18}$|^[A-Z0-9]{20}$/
				app.error('请填写公司税号');
				return;
			}
			if(formdata.invoice_type == 2) {
				if(formdata.address == '') {
					app.error('请填写注册地址');
					return;
				}
				if(formdata.tel == '') {
					app.error('请填写注册电话');
					return;
				}
				if(formdata.bank_name == '') {
					app.error('请填写开户银行');
					return;
				}
				if(formdata.bank_account == '') {
					app.error('请填写银行账号');
					return;
				}
			}
			if (formdata.mobile != '') {
				if(!app.isPhone(formdata.mobile)){
					app.error("手机号码有误，请重填");
					return;
				}
			}
			if (formdata.email != '') {
				if(!/^([A-Za-z0-9_\-\.])+\@([A-Za-z0-9_\-\.])+\.([A-Za-z]{2,4})$/.test(formdata.email)){
					app.error("邮箱有误，请重填");
					return;
				}
			}
			if(formdata.mobile == '' && formdata.email == '') {
				app.error("手机号和邮箱请填写其中一个");
				return;
			}

			app.showLoading('提交中');
      app.post('ApiOrder/invoice', {id: id,formdata:formdata,type:type}, function (res) {
				app.showLoading(false);
        app.alert(res.msg);
        if (res.status == 1) {
         setTimeout(function () {
					 if(type == 'shop')
						app.goto('/pagesExt/order/detail?id='+that.detail.id);
					 else
						app.goto('/activity/'+type+'/orderdetail?id='+that.detail.id);
         }, 1000);
        }
      });
    },
		changeOrderType: function(e) {
			var that = this;
			var value = e.detail.value;
			if(value == 2) {
				that.name_type_select = 2;
				that.name_type_personal_disabled = true;
			} else {
				that.name_type_personal_disabled = false;
			}
			that.invoice_type_select = value;
		},
		changeNameType: function(e) {
			var that = this;
			var value = e.detail.value;
			that.name_type_select = value;
		},
		back:function(e) {
			uni.navigateBack({
				
			})
		}
		}
	}
</script>

<style>
.radio radio{transform: scale(0.8);}
.radio:nth-child(2) { margin-left: 30rpx;}
.btn-a { text-align: center; padding: 30rpx; color: rgb(253, 74, 70);}
.text-min { font-size: 24rpx; color: #999;}
.orderinfo{width:96%;margin:0 2%;border-radius:8rpx;margin-top:16rpx;padding: 14rpx 3%;background: #FFF;}
.orderinfo .item{display:flex;width:100%;padding:20rpx 0;border-bottom:1px dashed #ededed;overflow:hidden}
.orderinfo .item:last-child{ border-bottom: 0;}
.orderinfo .item .t1{width:200rpx;flex-shrink:0}
.orderinfo .item .t2{flex:1;text-align:right; font-size: 28rpx;}
.orderinfo .item .red{color:red}
.orderinfo .item .grey{color:grey}

.form-item4{width:100%;background: #fff; padding: 20rpx 20rpx;margin-top:1px}
.form-item4 .label{ width:150rpx;}

.form-content{width:94%;margin:16rpx 3%;border-radius:10rpx;display:flex;flex-direction:column;background:#fff;overflow:hidden}
.form-item{ width:100%;padding: 32rpx 20rpx;}
.form-item .label{ width:100%;height:60rpx;line-height:60rpx}
.form-item .input-item{ width:100%;}
.form-item textarea{ width:100%;height:200rpx;border: 1px #eee solid;padding: 20rpx;}
.form-item input{ width:100%;border: 1px #f5f5f5 solid;padding: 10rpx;height:80rpx}
.form-item .mid{ height:80rpx;line-height:80rpx;padding:0 20rpx;}
.btn{ height:100rpx;line-height: 100rpx;width:90%;margin:0 auto;border-radius:50rpx;margin-top:50rpx;color: #fff;font-size: 30rpx;font-weight:bold}
</style>
