<template>
<view class="container">
	<block v-if="isload">
		<form @submit="formSubmit" @reset="formReset">
		<view class="form">
			<view class="form-item">
				<text class="label">姓名</text>
				<input type="text" class="input" placeholder="请输入姓名" placeholder-class="placeholder" name="realname" v-model="form.realname" :disabled="inputDisabled"></input>
			</view>
			<view class="form-item">
				<text class="label">身份证号</text>
				<input type="text" class="input" placeholder="请输入身份证号" placeholder-class="placeholder" name="usercard" v-model="form.usercard" :disabled="inputDisabled"></input>
			</view>
			<view class="form-item" style="height: 200rpx;">
				<text class="label">身份证有效期</text>
				<view class="form-value flex-sb">
					<view class="flex1">
						<view :class="form.usercard_begin_date?'':'placeholder'" class="picker-range">
							<picker mode="date" name="usercard_begin_date" @change="itemChange"
								data-field="usercard_begin_date" v-model="form.usercard_begin_date" >
								{{form.usercard_begin_date?form.usercard_begin_date:'请选择开始日期'}}
							</picker>
						</view>
						<view style="margin-top: 6rpx;"
							:class="form.usercard_end_date?'':'placeholder'" class="picker-range">
							<picker mode="date" name="usercard_end_date" @change="itemChange"
								data-field="usercard_end_date" v-model="form.usercard_end_date" >
								{{form.usercard_end_date?form.usercard_end_date:'请选择结束日期'}}
							</picker>
						</view>
					</view>
					<checkbox-group name="usercard_date_type"	v-model="form.usercard_date_type" @change="itemChange"
						data-field="usercard_date_type">
						<label class="form-tips">
							<checkbox value="1" style="transform: scale(0.7);" :checked="form.usercard_date_type ?true:false" ></checkbox>长期
						</label>
					</checkbox-group>
				</view>
			</view>
			<view class="form-item">
					<text class="label">开户银行</text>
					<picker class="picker" mode="selector" name="bankname" value="0" :range="banklist" @change="bindBanknameChange" >
						<view v-if="bankname">{{bankname}}</view>
						<view v-else>请选择开户行</view>
					</picker>
			</view>
			<view class="form-item">
				<view class="label">银行所属地区</view>
				<view class="form-value">
					<uni-data-picker class="" :localdata="citylist" popup-title="地区" @change="cityChange" :placeholder="'地区'" v-model="city" >
						<view class="form-select flex">
							<view class="select-txt">{{city.length>0?city.join('/'):'请选择地区'}}</view>
							<image class="down" :src="pre_url+'/static/img/arrowdown.png'"></image>
						</view>
					</uni-data-picker>
				</view>
			</view>
			<view class="form-item">
					<text class="label">所属分支行</text>
					<input type="text" class="input" placeholder="请输入分支行" name="bankaddress" v-model="form.bankaddress" placeholder-class="placeholder" ></input>
			</view>
			<!-- <view class="form-item">
					<text class="label">持卡人姓名</text>
					<input type="text" class="input" placeholder="请输入持卡人姓名" name="bankcarduser" :value="form.bankcarduser" placeholder-class="placeholder"></input>
			</view> -->
			<view class="form-item">
					<text class="label">银行卡号</text>
					<input type="text" class="input" placeholder="请输入银行卡号" name="bankcardnum" v-model="form.bankcardnum" placeholder-class="placeholder" ></input>
			</view>
			<view class="form-item">
					<text class="label">手机号</text>
					<input type="text" class="input" placeholder="请输入手机号" name="tel" v-model="form.tel" placeholder-class="placeholder" ></input>
			</view>
		</view>
		<button v-if="!userinfo.huifu_id" class="set-btn" form-type="submit" :style="{background:'linear-gradient(90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'}">提 交</button>
		<button v-else class="set-btn" form-type="submit" :style="{background:'linear-gradient(90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'}">修 改</button>
		</form>
		
		<view style="display: none;">
			{{txt}}
		</view>
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
		
      banklist: ['工商银行', '农业银行', '中国银行', '建设银行', '招商银行', '邮储银行', '交通银行', '浦发银行', '民生银行', '兴业银行', '平安银行', '中信银行', '华夏银行', '广发银行', '光大银行', '北京银行', '宁波银行'],
      bankname: '',
			userinfo:{},
			textset:{},
			form: {},
			txt: 1,
			citylist:[],
			city:[],
			areaval:[],
			inputDisabled : false,
			pre_url: app.globalData.pre_url,
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
			app.get('ApiMy/setHuifuField', {}, function (data) {
				that.loading = false;
				that.userinfo = data.userinfo;
				that.form = data.userinfo;
				that.bankname = data.userinfo.bankname;
				if(that.userinfo.bank_province && that.userinfo.bank_city){
					that.city.push(that.userinfo.bank_province);
					that.city.push(that.userinfo.bank_city);
				}
				if(that.userinfo.bank_province_code && that.userinfo.bank_city_code){
					that.areaval.push(that.userinfo.bank_province_code);
					that.areaval.push(that.userinfo.bank_city_code);
				}
				if(that.userinfo.huifu_id) that.inputDisabled = true;
				// console.log(that.city)
				that.initAreaList()
				that.loaded();
			});
		},
    formSubmit: function (e) {
      // var formdata = e.detail.value;
			
			var that = this;
			var formdata = that.form;
			// console.log(formdata); return;
			formdata.bankname = this.bankname
			// var realname = formdata.realname;
			// var usercard = formdata.usercard;
			// var bankname = this.bankname
			// var bankcarduser = formdata.realname
			// var bankcardnum = formdata.bankcardnum
			// var bankaddress = formdata.bankaddress
			// var usercard_begin_date = formdata.usercard_begin_date
			// var usercard_end_date = formdata.usercard_end_date
			// var usercard_date_type = formdata.usercard_date_type
			var bankcity = ''
      
      if (formdata.realname == '') {
        app.alert('请输入姓名');return;
      }
			if (formdata.usercard == '' || !formdata.usercard) {
			  app.alert('请输入身份证号');return;
			}
			if (formdata.usercard_begin_date == '' || formdata.usercard_begin_date == 0) {
			  app.alert('请选择身份证开始日期');return;
			}
			console.log(formdata.usercard_date_type)
			if (formdata.usercard_date_type.length == 0 && (formdata.usercard_end_date == '' || formdata.usercard_end_date == 0)) {
			  app.alert('请选择身份证结束日期');return;
			}
			if (formdata.bankname == '') {
        app.alert('请选择开户行');return;
      }
      if (formdata.bankcardnum == '') {
        app.alert('请输入银行卡号');return;
      }
			formdata.areaname = this.city;
			formdata.areaval = this.areaval;
			if(formdata.areaval.length < 1){
        app.alert('请选择银行所属地区');return;
			}
      if (formdata.tel == '') {
        app.alert('请输入手机号');return;
      }
			app.showLoading('提交中');
      app.post("ApiMy/setHuifuField", {formdata}, function (data) {
				app.showLoading(false);
        if (data.status == 1) {
          app.success(data.msg);
          setTimeout(function () {
            app.goback(true);
          }, 1000);
        } else {
          app.error(data.msg);
        }
      });
    },
    bindBanknameChange: function (e) {
      this.bankname = this.banklist[e.detail.value];
			this.form.bankname = this.banklist[e.detail.value];
    },
		
		itemChange: function(e) {
			var that = this;
			var field = e.currentTarget.dataset.field
			console.log(e);
			var value =  e.detail.value;
			if(field=='usercard_date_type'){
				value = value[0] || 0;
			}
			console.log(value);
			that.form[field] = value;
			console.log(that.form)
			that.txt = Math.random()
		},
			cityChange: function(e) {
				var that = this
				var arr = e.detail.value
				var city = [];
				var areaval = [];
				for (let i in arr) {
					city.push(arr[i].text)
					areaval.push(arr[i].value)
				}
				that.city = city;
				that.areaval = areaval;
			},
			initAreaList: function(e) {
				var that = this;
				uni.request({//address3Wechat.js
					url: app.globalData.pre_url + '/static/area_wechat.json',
					data: {},
					method: 'GET',
					header: {
						'content-type': 'application/json'
					},
					success: function(res2) {
						var newlist = [];
						var areaData = res2.data
						for(let i in areaData){
							let item1 = areaData[i]
							let children = item1.sub_list //市
							let newchildren = [];
							for(let j in children){
								let item2 = children[j]
								item2.children = []; //去掉三级-县的数据
								newchildren.push(item2)
							}
							item1.children = newchildren
							newlist.push(item1)
						}
						console.log(newlist);
						that.citylist = newlist
						
					}
				});
				
				uni.request({
					url: app.globalData.pre_url + '/static/area_wechat.json',
					data: {},
					method: 'GET',
					header: {
						'content-type': 'application/json'
					},
					success: function(res2) {
						that.arealist = res2.data
					}
				});
			},
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

	.placeholder {
		color:#BBBBBB;font-size:28rpx
	}

	.flex-sb {
		display: flex;
		justify-content: space-between;
		align-items: center;
	}
	.form-title {
		font-size: 32rpx;
		padding-bottom: 20rpx;
		border-bottom: 1rpx solid #f0f0f0;
		margin-bottom: 30rpx;
	}
	.form-value {
		flex: 1;
		/* padding: 0 10rpx; */
	}
	.form-value .picker {
		font-size: 24rpx;
		border-radius: 8rpx;
		height: 70rpx;
		line-height: 70rpx;
		border: 1rpx solid #f0f0f0;
		padding: 0 10rpx;
		flex: 1;
	}
	.picker-range{border: 1rpx solid #F0F0F0;height: 60rpx;line-height: 60rpx;border-radius: 6rpx;padding: 0 10rpx;}

	.form-tips {
		font-size: 24rpx;
		flex-shrink: 0;
		color: #999;
	}

	.form-tips-block {
		font-size: 24rpx;
		flex-shrink: 0;
		color: #999;
		background: #f8f8f8;
		padding: 20rpx;
		margin-top: -30rpx;
		margin-bottom: 20rpx;
	}
	.down {
		width: 24rpx;
		height: 24rpx;
		margin-left: 10rpx;
	}
	
	.form-value .form-select
{
		align-items: center;
		justify-content: flex-start;
	}
</style>