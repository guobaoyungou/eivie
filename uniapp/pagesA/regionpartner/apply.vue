<template>
<view class="container">
	<block v-if="isload">
		<form @submit="formSubmit">
			<view class="form">
				<view class="form-item">
					<text class="label">姓 名</text>
					<input class="input" type="text" placeholder="请输入姓名" placeholder-style="font-size:28rpx;color:#BBBBBB" name="name" :value="name"></input>
				</view>
				<view class="form-item">
					<text class="label">店铺名称</text>
					<input class="input" type="text" placeholder="请输入公司或单位名称" placeholder-style="font-size:28rpx;color:#BBBBBB" name="company" :value="company"></input>
				</view>
				<view class="form-item">
					<text class="label">手机号</text>
					<input class="input" type="number" placeholder="请输入手机号" placeholder-style="font-size:28rpx;color:#BBBBBB" name="tel" :value="tel"></input>
				</view>
				
				<view class="form-item">
					<text class="label flex1">申请区域</text>
					<uni-data-picker :localdata="items" :border="false" :placeholder="regiondata || '请选择区域'" @change="regionchange"></uni-data-picker>
				</view>
				<view class="form-item">
					<text class="label">申请费用</text>
					<input class="input" type="text" placeholder-style="font-size:28rpx;color:#BBBBBB" disabled="true" :value="set.apply_money || 0"></input>
				</view>
				<view class="form-item">
					<text class="label flex1">当前分红人数</text>
					<input class="input" type="text" placeholder-style="font-size:28rpx;color:#BBBBBB" disabled="true" :value="set.count || 0"></input>
				</view>
			</view>
			<button class="savebtn" :style="'background:linear-gradient(90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'" form-type="submit">保 存</button>
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
      name: '',
      tel: '',
      area: '',
      address: '',
      longitude: '',
      latitude: '',
      regiondata: '',
      type: 0,
      addressxx: '',
      company: '',
			items:[],
			showCompany:false,
			set:{},
    };
  },

  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.type = this.opt.type || 0;
		var that = this;
		
		app.get('ApiIndex/getCustom',{}, function (customs) {
			var url = app.globalData.pre_url+'/static/area.json';
			if(customs.data.includes('plug_zhiming')) {
				url = app.globalData.pre_url+'/static/area_gaoxin.json';
			}
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
		var addressId = that.opt.id || '';
		app.get('ApiIndex/getCustom',{}, function (customs) {
			if(customs.data.includes('plug_xiongmao')) {
				that.showCompany = true;
			}
		});
		that.loaded();
	},
	regionchange(e) {
			var that = this;
			console.log(e);
			const value = e.detail.value
			console.log(value[0].text + ',' + value[1].text + ',' + value[2].text);
			this.regiondata = value[0].text + ',' + value[1].text + ',' + value[2].text
			app.post('ApiRegionPartner/checkArea', {province:value[0].text,city:value[1].text,district:value[2].text}, function (res) {
				app.showLoading(false);
				if (res.status == 0) {
					app.alert(res.msg);
					//that.$refs.picker.clear()
					//that.regiondata = '';
					return;
				}
				that.set = res.set;
				console.log(res.set);
			});
		},
    formSubmit: function (e) {
		var that = this;
		var formdata = e.detail.value;
		var name = formdata.name;
		var tel = formdata.tel;
		var regiondata = that.regiondata;
		var area = regiondata;
		if(area == '') {
			app.error('请选择省市区');
			return;
		}
		var company = formdata.company;

		if (name == '' || tel == '' || company == '') {
			app.error('请填写完整信息');
			return;
		}
		app.showLoading('提交中');
        app.post('ApiRegionPartner/apply', {name: name,tel: tel,area: area,company:company}, function (res) {
			app.showLoading(false);
			if (res.status == 0) {
			    app.alert(res.msg);
			    return;
			}
			app.goto('/pagesExt/pay/pay?id='+res.payorderid);
		});
    },
  }
};
</script>
<style>
.container{display:flex;flex-direction:column}

.addfromwx{width:94%;margin:20rpx 3% 0 3%;border-radius:5px;padding:20rpx 3%;background: #FFF;display:flex;align-items:center;color:#666;font-size:28rpx;}
.addfromwx .img{width:40rpx;height:40rpx;margin-right:20rpx;}
.form{ width:94%;margin:20rpx 3%;border-radius:5px;padding: 0 3%;background: #FFF;}
.form-item{display:flex;align-items:center;width:100%;border-bottom: 1px #ededed solid;height:98rpx;}
.form-item:last-child{border:0}
.form-item .label{ color:#8B8B8B;font-weight:bold;height: 60rpx; line-height: 60rpx; text-align:left;width:160rpx;padding-right:20rpx}
.form-item .input{ flex:1;height: 60rpx; line-height: 60rpx;text-align:right}
.savebtn{ width: 90%; height:96rpx; line-height: 96rpx; text-align:center;border-radius:48rpx; color: #fff;font-weight:bold;margin: 0 5%; margin-top:60rpx; border: none; }
</style>