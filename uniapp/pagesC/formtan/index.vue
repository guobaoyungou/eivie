<template>
<view class="container">
	<block v-if="isload">
		<form @submit="formSubmit" @reset="formReset">
		<view class="form" style="padding-bottom: 20rpx;">
			<view class="item flex-y-center ">
				<view class="label">企业名称：</view>	
				<input type="text" class="input" placeholder="请输入企业名称" placeholder-style="color:#BBBBBB;font-size:28rpx" name="name" :value="data.name"></input>
			</view>
			<view class="item flex-y-center">
				<view class="label">联系人：</view>	
				<input type="text" class="input" placeholder="请输入联系人" placeholder-style="color:#BBBBBB;font-size:28rpx" name="linkman" :value="data.linkman"></input>
			</view>
			<view class="item flex-y-center">
				<view class="label">电话：</view>	
				<input type="text" class="input" placeholder="请输入电话" placeholder-style="color:#BBBBBB;font-size:28rpx" name="tel" :value="data.tel"></input>
			</view>
	
			<view class="item" >
				<view class="flex-y-center">
					<view class="label" >区域：</view>
					<uni-data-picker :localdata="items" :border="false" :placeholder="regiondata || '请选择省市区'" @change="regionchange"></uni-data-picker>
				</view>
				<view class="flex-y-center item2" >
					<view class="label">地址：</view>
					<input type="text" class="input" placeholder="请输入地址" placeholder-style="color:#BBBBBB;font-size:28rpx" name="address" :value="data.address"></input>
				</view>
			</view>
			<view class="item" >
				<view class="flex-y-center">
					<view class="label" >是否为高耗能行业：</view>
					<radio-group @change="gaohaoChange" class="flex">
						<label class="radio flex-y-center" ><radio value="1" :checked="is_gaohao=='1'?true:false" style="transform: scale(0.7)"/>是</label>
						<label class="radio flex-y-center" ><radio value="0" :checked="is_gaohao=='0'?true:false" style="transform: scale(0.7)"/>否</label>
					</radio-group>
				</view>
				<view class="flex-y-center item2" v-if="is_gaohao==1">
					<view class="label"></view>
					<input type="text" class="input" placeholder="请填写具体行业" placeholder-style="color:#BBBBBB;font-size:28rpx" name="gaohao_industry" :value="data.gaohao_industry"></input>
				</view>
			</view>
			<view class="item flex-y-center" >
				<view class="label" >是否需要碳指标：</view>
				<radio-group @change="tantargetChange" class="flex">
					<label class="radio flex-y-center" ><radio value="1" :checked="is_tan_target=='1'?true:false" style="transform: scale(0.7)"/>是</label>
					<label class="radio flex-y-center" ><radio value="0" :checked="is_tan_target=='0'?true:false" style="transform: scale(0.7)"/>否</label>
				</radio-group>
			</view>
			
		
			<view class="item flex-y-center" >
				<view class="label" >是否为碳配额控排企业：</view>
				<radio-group @change="kongpaiChange" class="flex">
					<label class="radio flex-y-center" ><radio value="1" :checked="is_tan_quota_kongpai_company=='1'?true:false"   style="transform: scale(0.7)"/>是</label>
					<label class="radio flex-y-center" ><radio value="0" :checked="is_tan_quota_kongpai_company=='0'?true:false"  style="transform: scale(0.7)"/>否</label>
				</radio-group>
			</view>
			<view class="item" v-if="is_tan_quota_kongpai_company ==1">
				<view class="flex-y-center">
					<view class="label" >碳配额是否盈余：</view>
					<radio-group @change="yingyuChange" class="flex">
						<label class="radio flex-y-center" ><radio value="1" :checked="tan_quota_is_yingyu=='1'?true:false" style="transform: scale(0.7)"/>是</label>
						<label class="radio flex-y-center" ><radio value="0" :checked="tan_quota_is_yingyu=='0'?true:false" style="transform: scale(0.7)"/>否</label>
					</radio-group>
				</view>
				<view class="flex-y-center item2" >
					<view class="label" style="text-align: right;">盈余：</view>
					<input type="text" class="input" placeholder="请填写数量" placeholder-style="color:#BBBBBB;font-size:28rpx" name="yingyu_num" :value="data.yingyu_num"></input>吨
				</view>
			</view>
		
			<view class="item" >
				<view class="flex-y-center">
					<view class="label" >是否有第三方服务：</view>
					<radio-group @change="thirdserviceChange" class="flex">
						<label class="radio flex-y-center" ><radio value="1" :checked="is_third_service=='1'?true:false" style="transform: scale(0.7)"/>是</label>
						<label class="radio flex-y-center" ><radio value="0" :checked="is_third_service=='0'?true:false" style="transform: scale(0.7)"/>否</label>
					</radio-group>
				</view>
				<view class="flex-y-center item2" v-if="is_third_service==1">
					<view class="label"></view>
					<input type="text" class="input" placeholder="请填写服务信息" placeholder-style="color:#BBBBBB;font-size:28rpx" name="service_info" :value="data.service_info"></input>
				</view>
			</view>
			<view class="item flex-y-center" >
				<view class="label" >是否开启CCER账号：</view>
				<radio-group @change="ccerChange" class="flex">
					<label class="radio flex-y-center" ><radio value="1" :checked="is_ccer_account=='1'?true:false" style="transform: scale(0.7)"/>是</label>
					<label class="radio flex-y-center" ><radio value="0" :checked="is_ccer_account=='0'?true:false" style="transform: scale(0.7)"/>否</label>
				</radio-group>
			</view>
			<view class="item flex-y-center" >
				<view class="label" >是否有产品碳足迹认证：</view>
				<radio-group @change="renzhengChange" class="flex">
					<label class="radio flex-y-center" ><radio value="1" :checked="is_tan_footmark_renzheng=='1'?true:false" style="transform: scale(0.7)"/>是</label>
					<label class="radio flex-y-center" ><radio value="0" :checked="is_tan_footmark_renzheng=='0'?true:false" style="transform: scale(0.7)"/>否</label>
				</radio-group>
			</view>
			<view class="item flex-y-center" >
				<view class="label" >有无绿证和绿电使用：</view>
				<radio-group @change="lvdianChange" class="flex">
					<label class="radio flex-y-center" ><radio value="1" :checked="is_lvzheng_lvdian=='1'?true:false" style="transform: scale(0.7)"/>是</label>
					<label class="radio flex-y-center" ><radio value="0" :checked="is_lvzheng_lvdian=='0'?true:false" style="transform: scale(0.7)"/>否</label>
				</radio-group>
			</view>
			<view class="item flex-y-center" >
				<view class="label" style="width: 30%;">用户类型：</view>
				<radio-group @change="membertypeChange" class="flex flex-wp">
					<block v-for="(item,index) in set.member_type">
						<label class="radio flex-y-center" ><radio :value="item" :checked="item==data.member_type || index==0?true:false" style="transform: scale(0.7)"/>{{item}}</label>
					</block>
				</radio-group>
			</view>
			
			<view class="item" >
				<view class="flex-y-center">
					<view class="label" >电压等级：</view>
					<radio-group @change="voltageChange" class="flex flex-wp">
						<block v-for="(item,index) in set.voltage_level">
							<label class="radio flex-y-center" >
								<radio :value="item" :checked="item==data.voltage_level || index==0?true:false" style="transform: scale(0.7)"/>{{item}}
							</label>
						</block>
					</radio-group>
				</view>
				
			</view>
			<view class="item" >
				<view class="flex-y-center" >
					<view class="label" >年用电量：</view>
					<input type="text" class="input" placeholder="请填写年用电量" placeholder-style="color:#BBBBBB;font-size:28rpx" name="electricity_num" :value="data.electricity_num"></input>
				</view>
				<view class="item flex-y-center item2" >
					<view class="label" >是否24小时生产：</view>
					<radio-group @change="hour24Change" class="flex">
						<label class="radio flex-y-center" ><radio value="1" :checked="is_24hour=='1'?true:false" style="transform: scale(0.7)"/>是</label>
						<label class="radio flex-y-center" ><radio value="0" :checked="is_24hour=='0'?true:false" style="transform: scale(0.7)"/>否</label>
					</radio-group>
				</view>
			</view>
			
			
			<view class="item" >
				<view class="flex-y-center">
					<view class="label" >是否参与市场化购电：</view>
					<radio-group @change="marketbuyChange" class="flex">
						<label class="radio flex-y-center" ><radio value="1" :checked="is_join_market_buy=='1'?true:false" style="transform: scale(0.7)"/>是</label>
						<label class="radio flex-y-center" ><radio value="0" :checked="is_join_market_buy=='0'?true:false" style="transform: scale(0.7)"/>否</label>
					</radio-group>
				</view>
				<view class="flex-y-center item2" v-if="is_join_market_buy==1">
					<view class="label"></view>
					<input type="text" class="input" placeholder="售电公司名称" placeholder-style="color:#BBBBBB;font-size:28rpx" name="electricity_sales_company" :value="data.electricity_sales_company"></input>
				</view>
			</view>
			<view class="item " >
				<view class="label" style="width: 100%;">双碳延伸与增值业务类型（可多选）：</view>
				<view >
					<checkbox-group @change="valueaddedChange">
						<block v-for="(item,index) in set.value_added">
							<view style="padding-top: 10rpx;"><checkbox :value="item" :checked="inArray(item,data.value_added_data)?true:false"    style="transform:scale(0.7)" />{{item}}</view>
						</block>
					</checkbox-group>
				</view>	
			</view>
			<view class="item flex-y-center" v-if="data.status ==2">
				<view class="label" style="width: 30%;">驳回信息：</view>	
				<view  class="input"> {{data.reason}}</view>
			</view>
			
			<button class="set-btn" v-if="data.status ==2 || !data.id" form-type="submit"  :style="{background:'linear-gradient(90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'}">保 存</button>
			
			<button  class="set-btn"  style="background-color: #ccc" v-else-if="data.status ==0">审核中</button>
			
		</view>
		</form>
	</block>
	<loading v-if="loading"></loading>
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
		textset:{},
		haspwd: 0,
		regiondata:'',
		items:[],
		set:{},
		data:{},
		is_gaohao:1,//是否高耗能行业
		is_tan_target:1,//是否需要碳指标
		is_tan_quota_kongpai_company:1,//是否为碳配控排企业
		tan_quota_is_yingyu:1,//碳配额是否盈余
		is_third_service:1,//是否有第三方服务
		is_ccer_account:1,//是否开启CCER账号
		is_tan_footmark_renzheng:1,//是否有产品碳足迹认证
		is_lvzheng_lvdian:1,//有无绿证绿电使用
		member_type:'',//用户类型
		//电压等级
		is_24hour:1,//是否24小时生产
		is_join_market_buy:1,
		voltage_level:'',
		value_added_data:[]
    };
  },

  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.getdata();
		var that = this;
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
  },
	onPullDownRefresh: function () {
		this.getdata();
	},
  methods: {
    formSubmit: function (e) {
		var that = this;
		var formdata = e.detail.value;
		if (formdata.name == '') {
			app.alert('请输入企业名称');return;
		}
		if (formdata.linkman == '') {
			app.alert('请输入联系人');return;
		}
		if (formdata.tel == '') {
			app.alert('请输入电话');return;
		}
		if (that.regiondata == '') {
			app.alert('请选择区域');return;
		}
		if(formdata.address == '') {
			app.alert('请输入地址');return;
		}
		if(that.is_gaohao ==1) {
			if(formdata.gaohao_industry ==''){
				 app.alert('请填写具体行业');return;
			}  
		}
		if(that.tan_quota_is_yingyu ==1) {
			if(formdata.yingyu_num ==''){
				 app.alert('请填写盈余数量');return;
			}  
		}
		if(that.is_third_service ==1) {
			if(formdata.service_info ==''){
				 app.alert('请填写服务信息');return;
			}  
		}
		if(that.voltage_level !='') {
			if(formdata.electricity_num ==''){
				 app.alert('请填写年用电量');return;
			}  
		}
		if(that.is_join_market_buy ==1){
		  if(formdata.electricity_sales_company ==''){
			 app.alert('请填写售电公司名称');return;
		  } 
		}
		formdata.is_gaohao=that.is_gaohao;//是否高耗能行业
		formdata.is_tan_target=that.is_tan_target;//是否需要碳指标
		formdata.is_tan_quota_kongpai_company=that.is_tan_quota_kongpai_company;//是否为碳配控排企业
		formdata.tan_quota_is_yingyu=that.tan_quota_is_yingyu;//碳配额是否盈余
		formdata.is_third_service=that.is_third_service;//是否有第三方服务
		formdata.is_ccer_account=that.is_ccer_account;//是否开启CCER账号
		formdata.is_tan_footmark_renzheng=that.is_tan_footmark_renzheng;//是否有产品碳足迹认证
		formdata.is_lvzheng_lvdian=that.is_lvzheng_lvdian;//有无绿证绿电使用
		formdata.member_type = that.member_type;//用户类型
		formdata.is_24hour=that.is_24hour;//是否24小时生产
		formdata.is_join_market_buy=that.is_join_market_buy;
		formdata.voltage_level = that.voltage_level;
	    formdata.area = that.regiondata;
		formdata.value_added_data = that.value_added_data;
		app.showLoading('提交中');
		app.post("ApiFormTan/save", {info:formdata}, function (data) {
			app.showLoading(false);
			if (data.status == 1) {
			  app.success(data.msg);
			  setTimeout(function () {
				that.getdata();
			  }, 1000);
			} else {
			  app.error(data.msg);
			}
		});
    },
	getdata: function() {
		var that = this;
		that.loading = true;
		app.get('ApiFormTan/index', {}, function(data) {
			that.loading = false;
			that.set = data.set;
			that.data = data.data;
			if(that.data.id){
				var thisdata =that.data; 
				console.log(thisdata,'thisdata-----');
				that.regiondata = thisdata.area;
				that.is_gaohao=thisdata.is_gaohao;//是否高耗能行业
				that.is_tan_target=thisdata.is_tan_target;//是否需要碳指标
				that.is_tan_quota_kongpai_company=thisdata.is_tan_quota_kongpai_company;//是否为碳配控排企业
				that.tan_quota_is_yingyu=thisdata.tan_quota_is_yingyu;//碳配额是否盈余
				that.is_third_service=thisdata.is_third_service;//是否有第三方服务
				that.is_ccer_account=thisdata.is_ccer_account;//是否开启CCER账号
				that.is_tan_footmark_renzheng=thisdata.is_tan_footmark_renzheng;//是否有产品碳足迹认证
				that.is_lvzheng_lvdian=thisdata.is_lvzheng_lvdian;//有无绿证绿电使用
				that.member_type = thisdata.member_type;//用户类型
				that.is_24hour=thisdata.is_24hour;//是否24小时生产
				that.is_join_market_buy=thisdata.is_join_market_buy;
				that.voltage_level = thisdata.voltage_level;
				that.value_added_data  = thisdata.value_added_data;
			}else{
				that.member_type = that.set.member_type[0];
				that.voltage_level = that.set.voltage_level[0];
				that.data.yingyu_num = '';
				that.data.electricity_num = '';
			}
			
			that.loaded();
		});
	},
	regionchange(e) {
		const value = e.detail.value
		console.log(value[0].text + ',' + value[1].text + ',' + value[2].text);
		this.regiondata = value[0].text + ',' + value[1].text + ',' + value[2].text
	},
	gaohaoChange(e){
		this.is_gaohao = e.detail.value
	},
	tantargetChange(e){
		this.is_tan_target = e.detail.value
	},
	kongpaiChange(e){
		this.is_tan_quota_kongpai_company = e.detail.value
	},
	yingyuChange(e){
		this.tan_quota_is_yingyu = e.detail.value
	},
	thirdserviceChange(e){
		this.is_third_service = e.detail.value
	},
	ccerChange(e){
		this.is_ccer_account = e.detail.value
	},
	renzhengChange(e){
		this.is_tan_footmark_renzheng = e.detail.value
	},
	lvdianChange(e){
		this.is_lvzheng_lvdian = e.detail.value
	},
	membertypeChange(e){
		this.member_type = e.detail.value
	},
	voltageChange(e){
		this.voltage_level = e.detail.value
	},
	hour24Change(e){
		this.is_24hour = e.detail.value
	},
	marketbuyChange(e){
		this.is_join_market_buy = e.detail.value
	},
	valueaddedChange(e){
		this.value_added_data = e.detail.value
	}
  }
};
</script>
<style>
.container{overflow: hidden;}
.form{ width:94%;margin:20rpx 3%;border-radius:5px;padding:20rpx 20rpx;padding: 0 3%;background: #FFF;}
.item{width:100%;border-bottom: 1px #ededed solid;padding: 25rpx 0}
.item:last-child{border:0}
.item .label{color: #000;width:50%;}
.item .input{flex:1;color: #000;}
.item .radio{padding-right: 20rpx;}
.item2{padding-top: 15rpx;}
.set-btn{width: 90%;margin:30rpx 5%;height:96rpx;line-height:96rpx;border-radius:48rpx;color:#FFFFFF;font-weight:bold;}
</style>