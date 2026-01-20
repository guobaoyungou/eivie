<template>
	<view class="body">
		<block v-if="isload">
		<!-- 填写资料 -->
		<view class="header" :style="{background:'linear-gradient(180deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0) 100%)'}"></view>
		<view class="container">
			<form @submit="formSubmit">
				<!-- 基础信息 -->
				<view class="box">
					<view class="form-title">
						<view class="strip" :style="{background:t('color1')}"></view>
						<view class="title">基础信息</view>
					</view>
					<view class="form-item">
						<view class="form-label"><text class="required">*</text>选择行业</view>
						<view class="form-value">
							<picker :range="qualificationlist" @change="itemChange" data-field="settlement_id">
								<view class="form-select">
									<view class="select-txt">{{qualificationlist[qualificationIndex] ? qualificationlist[qualificationIndex] : "请选择"}}</view>
										<image class="down" :src="pre_url+'/static/img/arrowdown.png'"></image>
									</view>
							</picker>
						</view>
					</view>
					<view class="form-item" v-if="is_required">
						<view class="form-label"><text class="required">*</text>特殊资质图片</view>
						<view class="form-value form-upload">
							<view class="imgbox">
								<block v-if="form.qualifications && form.qualifications.length>0" v-for="(pic,pindex) in form.qualifications" :key="pindex">
									<view class="imgitem-pics">
										<image :src="pic" @tap="previewImage" :data-url="pic" mode="widthFix"></image>
									</view>
								</block>
								<view class="imgitem-pics" v-if="!form.qualifications || form.qualifications.length < 5">
									<view class="uploadbtn1" @tap="uploadimg" data-field="qualifications" data-pernum="5"
										:style="{background:'url('+pre_url+'/static/img/shaitu_icon.png) no-repeat 44rpx',backgroundSize:'60rpx 60rpx',backgroundColor:'#F3F3F3'}">
									</view>
								</view>
							</view>
						</view>
					</view>
				</view>
				<!-- END 基础信息 -->
				
				<!-- 主体资料 -->
				<view class="box">
					<view class="form-title">
						<view class="strip" :style="{background:t('color1')}"></view>
						<view class="title">主体资料</view>
					</view>
					<view class="form-item">
						<view class="form-label"><text class="required">*</text>营业执照</view>
						<view class="form-value form-upload">
							<view class="imgbox">
								<view class="layui-imgbox" v-if="form.license_copy">
									<view class="layui-imgbox-close" @tap="removeimg" data-field="license_copy">
										<image style="display:block" :src="pre_url+'/static/img/ico-del.png'"></image>
									</view>
									<view class="layui-imgbox-img">
										<image :src="form.license_copy" @tap="previewImage" :data-url="form.license_copy" mode="widthFix"></image>
									</view>
								</view>
								<view class="imgitem-pics" v-else>
									<view class="uploadbtn1" @tap="uploadimg" data-field="license_copy" :style="{background:'url('+pre_url+'/static/img/shaitu_icon.png) no-repeat 44rpx',backgroundSize:'60rpx 60rpx',backgroundColor:'#F3F3F3'}"></view>
								</view>
							</view>
						</view>
					</view>
					<view class="form-item">
						<view class="form-label"><text class="required">*</text>商户名称</view>
						<view class="form-value">
							<input type="text" name="merchant_name" v-model="form.merchant_name" placeholder="请填写商户名称" placeholder-class="placeholder">
						</view>
						<view class="form-tips">若营业执照上没有名字，则填写"个体户+姓名"，如个体户张三</view>
					</view>
					<view class="form-item">
						<view class="form-label"><text class="required">*</text>统一信用代码</view>
						<view class="form-value">
							<input type="text" name="license_number" v-model="form.license_number" placeholder="请填写统一信用代码" placeholder-class="placeholder">
						</view>
						<view class="form-tips">请核对仔细确保无误</view>
					</view>
					<view class="form-item">
						<view class="form-label"><text class="required">*</text>商户简称</view>
						<view class="form-value">
							<input type="text" name="merchant_shortname" v-model="form.merchant_shortname" placeholder="请填写商户简称" placeholder-class="placeholder">
						</view>
						<view class="form-tips">在支付完成向买家展示，需与微信经营类目相关</view>
					</view>
					<view class="form-item">
							<view class="form-label"><text class="required">*</text>客服电话</view>
							<view class="form-value">
								<input type="text" name="service_phone" v-model="form.service_phone" placeholder="请填写客服电话" placeholder-class="placeholder">
							</view>
							<view class="form-tips">将在交易记录中向商家展示，提供咨询服务</view>
					</view>
				</view>	
				<!-- END 主体资料 -->
				
				<!-- 法人身份证件 -->
				<view class="box">
					<view class="form-title">
						<view class="strip" :style="{background:t('color1')}"></view>
						<view class="title">法人身份证件</view>
					</view>
					<view class="form-item">
						<view class="form-label"><text class="required">*</text>身份证件人像面照片</view>
						<view class="form-value form-upload">
							<view class="imgbox">
								<view class="layui-imgbox" v-if="form.id_card_copy">
									<view class="layui-imgbox-close" @tap="removeimg" data-field="id_card_copy">
										<image style="display:block" :src="pre_url+'/static/img/ico-del.png'"></image>
									</view>
									<view class="layui-imgbox-img">
										<image :src="form.id_card_copy" @tap="previewImage" :data-url="form.id_card_copy" mode="widthFix"></image>
									</view>
								</view>
								<view class="imgitem-pics" v-else>
									<view class="uploadbtn1" @tap="uploadimg" data-field="id_card_copy"
										:style="{background:'url('+pre_url+'/static/img/shaitu_icon.png) no-repeat 44rpx',backgroundSize:'60rpx 60rpx',backgroundColor:'#F3F3F3'}">
									</view>
								</view>
							</view>
						</view>
					</view>
					<view class="form-item">
						<view class="form-label"><text class="required">*</text>身份证件国徽面照片</view>
						<view class="form-value form-upload">
							<view class="imgbox">
								<view class="layui-imgbox" v-if="form.id_card_national">
									<view class="layui-imgbox-close" @tap="removeimg" data-field="id_card_national">
										<image style="display:block" :src="pre_url+'/static/img/ico-del.png'"></image>
									</view>
									<view class="layui-imgbox-img">
										<image :src="form.id_card_national" @tap="previewImage" :data-url="form.id_card_national" mode="widthFix"></image>
									</view>
								</view>
								<view class="imgitem-pics" v-else>
									<view class="uploadbtn1" @tap="uploadimg" data-field="id_card_national"
										:style="{background:'url('+pre_url+'/static/img/shaitu_icon.png) no-repeat 44rpx',backgroundSize:'60rpx 60rpx',backgroundColor:'#F3F3F3'}">
									</view>
								</view>
							</view>
						</view>
					</view>
					<view class="form-item">
						<view class="form-label"><text class="required">*</text>身份证姓名</view>
						<view class="form-value">
							<input type="text" name="id_card_name" v-model="form.id_card_name" placeholder="请填写身份证姓名" placeholder-class="placeholder">
						</view>
					</view>
					<view class="form-item">
						<view class="form-label"><text class="required">*</text>身份证号码</view>
						<view class="form-value">
							<input type="text" name="id_card_number" v-model="form.id_card_number" placeholder="请填写身份证号码" placeholder-class="placeholder">
						</view>
					</view>
				
					<view class="form-item">
						<view class="form-label"><text class="required">*</text>身份证有效日期</view>
						<view class="form-value">
							<view :class="form.card_period_begin?'':'placeholder'" class="picker-range">
								<picker mode="date" name="card_period_begin" @change="itemChange" data-field="card_period_begin">
									{{form.card_period_begin?form.card_period_begin:'请选择身份证有效日期'}}
								</picker>
							</view>
						</view>
					</view>
					<view class="form-item">
						<view class="form-label"><text class="required">*</text>身份证失效日期</view>
						<view class="form-value" style="margin-bottom: 20rpx;">
							<radio-group @change="itemChange" data-field="card_period_end_cq">
								<label class="checkbox-lebel">
									<radio value="0" style="transform: scale(0.8);" :checked="!form.card_period_end_cq || form.card_period_end_cq == 0" />
									<text>非长期</text>
								</label>
								<label class="checkbox-lebel">
									<radio value="1" style="transform: scale(0.8);" :checked="form.card_period_end_cq == 1" />
									<text>长期</text>
								</label>
							</radio-group>
						</view>
						<view class="form-value" v-if="!form.card_period_end_cq || form.card_period_end_cq == 0">
							<view :class="form.card_period_end?'':'placeholder'" class="picker-range">
								<picker mode="date" name="card_period_end" @change="itemChange" data-field="card_period_end">
									{{form.card_period_end?form.card_period_end:'请选择日期'}}
								</picker>
							</view>
						</view>
					</view>
					<view class="form-item">
						<view class="form-label"><text class="required">*</text>手机号码</view>
						<view class="form-value">
							<input type="text" name="mobile_phone" v-model="form.mobile_phone" placeholder="请填写手机号码" placeholder-class="placeholder">
						</view>
						<view class="form-tips">用户接收微信支付的重要管理信息及日常操作验证码</view>
					</view>
					<view class="form-item">
						<view class="form-label"><text class="required">*</text>联系邮箱</view>
						<view class="form-value">
							<input type="text" name="contact_email" v-model="form.contact_email" placeholder="请填写联系邮箱" placeholder-class="placeholder">
						</view>
						<view class="form-tips">用户接收微信支付的开户邮件，及日常业务通知</view>
					</view>
				</view>
				<!-- END 法人身份证件 -->
				
				<!-- 经营信息 -->
				<view class="box">
					<view class="form-title">
						<view class="strip" :style="{background:t('color1')}"></view>
						<view class="title">经营信息</view>
					</view>
					<view class="form-item">
						<view class="form-label"><text class="required">*</text>门店名称</view>
						<view class="form-value">
							<input type="text" name="biz_store_name" v-model="form.biz_store_name" placeholder="请填写门店名称" placeholder-class="placeholder">
						</view>
					</view>
					<view class="form-item">
						<view class="form-label"><text class="required">*</text>门店省市</view>
						<view class="form-value">
							<uni-data-picker class="" :localdata="citylist" popup-title="请选择门店省市" @change="cityChange($event,'biz_address_code')" :placeholder="'门店省市'">
								<view class="form-select">
									<view class="select-txt">{{form.biz_address_code_name?form.biz_address_code_name:'请选择门店省市'}}</view>
									<image class="down" :src="pre_url+'/static/img/arrowdown.png'"></image>
								</view>
							</uni-data-picker>
						</view>
					</view>
					<view class="form-item">
						<view class="form-label"><text class="required">*</text>门店详细地址</view>
						<view class="form-value">
							<input type="text" name="biz_store_address" v-model="form.biz_store_address" placeholder="请填写门店详细地址" placeholder-class="placeholder">
						</view>
					</view>
					<view class="form-item">
						<view class="form-label"><text class="required">*</text>门店门口照片</view>
						<view class="form-value form-upload">
							<view class="imgbox">
								<view class="layui-imgbox" v-if="form.store_entrance_pic">
									<view class="layui-imgbox-close" @tap="removeimg" data-field="store_entrance_pic">
										<image style="display:block" :src="pre_url+'/static/img/ico-del.png'"></image>
									</view>
									<view class="layui-imgbox-img">
										<image :src="form.store_entrance_pic" @tap="previewImage" :data-url="form.store_entrance_pic" mode="widthFix"></image>
									</view>
								</view>
								<view class="imgitem-pics" v-else>
									<view class="uploadbtn1" @tap="uploadimg" data-field="store_entrance_pic"
										:style="{background:'url('+pre_url+'/static/img/shaitu_icon.png) no-repeat 44rpx',backgroundSize:'60rpx 60rpx',backgroundColor:'#F3F3F3'}">
									</view>
								</view>
							</view>
						</view>
						<view class="form-tips">门店场所：提交门店门口照片，要求招牌清晰可见</view>
						<view class="form-tips">流动经营/便民服务：提交经营/服务现场照片</view>
					</view>
					<view class="form-item">
						<view class="form-label"><text class="required">*</text>店内环境照片</view>
						<view class="form-value form-upload">
							<view class="imgbox">
								<view class="layui-imgbox" v-if="form.indoor_pic">
									<view class="layui-imgbox-close" @tap="removeimg" data-field="indoor_pic">
										<image style="display:block" :src="pre_url+'/static/img/ico-del.png'"></image>
									</view>
									<view class="layui-imgbox-img">
										<image :src="form.indoor_pic" @tap="previewImage" :data-url="form.indoor_pic" mode="widthFix"></image>
									</view>
								</view>
								<view class="imgitem-pics" v-else>
									<view class="uploadbtn1" @tap="uploadimg" data-field="indoor_pic"
										:style="{background:'url('+pre_url+'/static/img/shaitu_icon.png) no-repeat 44rpx',backgroundSize:'60rpx 60rpx',backgroundColor:'#F3F3F3'}">
									</view>
								</view>
							</view>
						</view>
						<view class="form-tips">门店场所：提交店内环境照片</view>
						<view class="form-tips">流动经营/便民服务：提交经营/服务现场照片</view>
					</view>
				</view>
				<!-- END 经营信息 -->
				
				<!-- 银行信息 -->
				<view class="box">
					<view class="form-title">
						<view class="strip" :style="{background:t('color1')}"></view>
						<view class="title">银行信息</view>
					</view>
					<view class="form-item">
						<view class="form-label"><text class="required">*</text>账户类型</view>
						<view class="form-value" style="margin-bottom: 20rpx;">
							<radio-group @change="itemChange" data-field="bank_account_type">
								<label class="checkbox-lebel" v-if="subject == 1">
									<radio value="1" style="transform: scale(0.8);" :checked="form.bank_account_type == 1" />
									<text>个人银行卡</text>
								</label>
								<label class="checkbox-lebel">
									<radio value="2" style="transform: scale(0.8);" :checked="form.bank_account_type == 2" />
									<text>对公银行账户</text>
								</label>
							</radio-group>
						</view>
					</view>
					<view class="form-item">
						<view class="form-label"><text class="required">*</text>开户名称</view>
						<view class="form-value">
							<input type="text" name="account_name" v-model="form.account_name" placeholder="请填写开户名称" placeholder-class="placeholder">
						</view>
					</view>
					<view class="form-item">
						<view class="form-label"><text class="required">*</text>开户地址</view>
						<view class="form-value">
							<uni-data-picker class="" :localdata="citylist" popup-title="请选择开户地址" @change="cityChange($event,'bank_address_code')" :placeholder="'开户地址'">
								<view class="form-select">
									<view class="select-txt">{{form.bank_address_code_name?form.bank_address_code_name:'请选择开户地址'}}</view>
									<image class="down" :src="pre_url+'/static/img/arrowdown.png'"></image>
								</view>
							</uni-data-picker>
						</view>
					</view>
					<view class="form-item">
						<view class="form-label"><text class="required">*</text>开户银行</view>
						<view class="form-value">
							<picker :range="bankList" @change="itemChange" data-field="account_bank">
								<view class="form-select">
									<view class="select-txt">{{ bankList[form['account_bank_index']] ? bankList[form['account_bank_index']] : '请选择开户银行' }}</view>
										<image class="down" :src="pre_url+'/static/img/arrowdown.png'"></image>
									</view>
							</picker>
						</view>
					</view>
					<view class="form-item" v-if="form.account_bank_index == 0">
						<view class="form-label"><text class="required">*</text>开户银全称（含支行）</view>
						<view class="form-value">
							<input type="text" name="bank_name" v-model="form.bank_name" placeholder="请填写开户银全称（含支行）" placeholder-class="placeholder">
						</view>
					</view>
					<view class="form-item">
						<view class="form-label"><text class="required">*</text>银行账号</view>
						<view class="form-value">
							<input type="text" name="account_number" v-model="form.account_number" placeholder="请填写银行账号" placeholder-class="placeholder">
						</view>
					</view>
				</view>
				<!-- END 银行信息 -->
				<view style="display: none;">{{txt}}</view>
				<view class="form-opt">
					<button class="btn" :style="{background:t('color1')}" @tap="submit">确认无误，提交审核</button>
				</view>
			</form>
		</view>
		<!-- END 填写资料 -->
		</block>
		<loading v-if="loading"></loading>
		<popmsg ref="popmsg"></popmsg>
	</view>
</template>
<script>
	var app = getApp();

	export default {
		data() {
			return {
				opt: {},
				loading: false,
				isload: false,
				pre_url: app.globalData.pre_url,
				id: 0,
				detail: {},
				txt: 1,
				arealist: [],
				area: [],
				citylist: [],
				city: [],
				canSubmit: true,
				modal_keyword: '',
				modal_type: '',
				isShowModal: false,
				form: {},
				qualification:[], //行业资质
				qualificationIndex:-1,
				qualificationlist:[],
				is_required: 0, //是否上传特殊资质
				bankList: {}, //开户银行
				subject: 0 //主体
			}
		},
		onLoad: function(opt) {
			var that = this;
			var opt = app.getopts(opt);
			that.opt = opt;
			that.subject = that.opt.subject || 0
			that.getdata();
		},
		onPullDownRefresh: function() {
			this.getdata();
		},
		methods: {
			getdata: function() {
				var that = this;
				var id = that.opt.id;
				that.loading = true;
				app.get('ApiAdminBusinessJinjian/wxapplyInfo', {}, function(res) {
					that.loading = false;
					if (res.status == 1) {
						let data = res.data;
						that.qualification = data.qualification;
						that.bankList = data.bankList;
						
						if(!app.isEmpty(data.detail)){
							if(data.resp == 1){
								return app.goto('result?type=1','redirect');
							}
							that.form = data.detail;
							if(that.form.id){
								//回显
								that.subject = parseInt(that.form.subject_type);
								let qat = that.qualification[that.subject];
								qat.forEach((item, index) => {
										if (item.name === that.form.qualification_type) {
												that.qualificationIndex = index;
										}
								});
								that.qualification = qat;
							}
						}else{
							that.qualification = that.qualification[that.subject];
						}
						that.qualificationlist = data.qualificationList[that.subject];
						that.initAreaList();
						that.loaded();
					} else {
						app.alert(res.msg);
					}
					uni.setNavigationBarTitle({
						title:'微信进件'
					});
				});
			},
			uploadimg: function(e) {
				var that = this;
				var field = e.currentTarget.dataset.field
				var pernum = parseInt(e.currentTarget.dataset.pernum);
				if (!pernum) pernum = 1;
				var pics = [];
				if (pernum > 1) {
					if (!that.form[field]) {
						that.form[field] = [];
						that.txt = Math.random()
					}
				}
				app.chooseImage(function(urls) {
					for (var i = 0; i < urls.length; i++) {
						if (pernum == 1) {
							that.form[field] = urls[i];
						} else {
							that.form[field].push(urls[i]);
						}
						//图片识别
						if(field == 'id_card_copy' || field == 'license_copy' || field == 'id_card_national'){
							that.imageRecognition(field);
						}
						that.txt = Math.random()
					}
				}, pernum);
			},
			removeimg:function(e){
				var that = this;
				var field= e.currentTarget.dataset.field;
				that.form[field] = '';
				that.txt = Math.random();
			},
			itemChange: function(e) {
				var that = this;
				var field = e.currentTarget.dataset.field
				var value = e.detail.value;
				
				//选择行业
				if(field == 'settlement_id'){
					that.qualificationIndex = value;
					let oldqualification = that.qualification[value];
					that.form[field] = oldqualification.settlement_id;
					that.form.qualification_type = oldqualification.name;
					that.is_required = oldqualification.is_required;
					that.txt = Math.random()
					return;
				}
				
				if(field == 'account_bank'){
					that.form.account_bank_index = value;
					that.form.account_bank = that.bankList[value];
					that.txt = Math.random()
					return;
				}
				
				that.form[field] = value;
				that.txt = Math.random()
			},
			initAreaList: function(e) {
				var that = this;
				uni.request({
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
						that.citylist = newlist
					},
				});

			},
			cityChange: function(e,field) {
				var that = this;
				var arr = e.detail.value;
				var city = [];
				for (let i in arr) {
					city.push(arr[i].text)
				}
				that.form[field + '_name'] = city.join('/');
				that.form[field] = arr[1].value;
				that.txt = Math.random()
			},
			submit: function(e) {
				var that = this;
				var form = that.form;
				var formType = that.formType;
				form.subject_type = that.subject;
				if(!form.qualification_type || !form.settlement_id){
					return app.error('请选择行业');
				}
				if(that.is_required && !form.qualifications){
					return app.error('请上传特殊资质图片')
				}
				if(!form.license_copy){
					return app.error('请上传营业执照')
				}
				if(!form.merchant_name){
					return app.error('请填写商户名称')
				}
				if(!form.license_number){
					return app.error('请填写统一信用代码')
				}
				if(!form.merchant_shortname){
					return app.error('请填写商户简称')
				}
				if(!form.service_phone){
					return app.error('请填写客服电话')
				}
				if(!form.id_card_copy){
					return app.error('请上传身份证件人像面照片')
				}
				if(!form.id_card_national){
					return app.error('请上传身份证件国徽面照片')
				}
				if(!form.id_card_name){
					return app.error('请填写身份证姓名')
				}
				if(!form.id_card_number){
					return app.error('请填写身份证号码')
				}
				if(!form.card_period_begin){
					return app.error('请选择身份证有效日期')
				}
				if(!form.card_period_end && !form.card_period_end_cq){
					return app.error('请选择身份证失效日期')
				}
				if(!form.mobile_phone){
					return app.error('请填写手机号码')
				}
				if(!form.contact_email){
					return app.error('请填写联系邮箱')
				}
				if(!form.biz_store_name){
					return app.error('请填写门店名称')
				}
				if(!form.biz_address_code_name || !form.biz_address_code){
					return app.error('请选择门店省市')
				}
				if(!form.biz_store_address){
					return app.error('请填写门店详细地址')
				}
				if(!form.store_entrance_pic){
					return app.error('请上传门店门口照片')
				}
				if(!form.indoor_pic){
					return app.error('请上传店内环境照片')
				}
				if(!form.bank_account_type){
					return app.error('请选择账户类型')
				}
				if(!form.account_name){
					return app.error('请填写开户名称')
				}
				if(!form.account_bank){
					return app.error('请选择开户银行')
				}
				if(form.account_bank_index && form.account_bank_index == 0){
					if(!form.bank_name){
						return app.error('请填写开户银全称（含支行）')
					}
				}
				if(!form.account_number){
					return app.error('请填写银行账号')
				}
				that.canSubmit = false;
				app.showLoading('提交中');
				app.post("ApiAdminBusinessJinjian/wxApply", form, function(data) {
					app.showLoading(false);
					if (data.status == 1) {
							app.success(data.msg)
							setTimeout(function(){
								app.goto('result?type=1','redirect');
							},1000)
					} else {
						that.canSubmit = true;
						app.error(data.msg);
					}
				});
				
			},
			//图片识别
			imageRecognition:function(field){
				var that = this;
				let img = that.form[field];
				app.post("ApiAdminBusinessJinjian/imageRecognition", {img:img,type:field}, function(res) {
					if(res.status == 1){
						let data = res.data;
						if(field == 'license_copy'){
							that.form.merchant_name = data.merchant_name;
							that.form.license_number = data.license_number;
							that.form.id_card_name = data.id_card_name;
							that.form.biz_store_name = data.merchant_name;
							that.form.biz_store_address = data.biz_store_address;
							that.form.account_name = data.merchant_name;
						}
						
						if(field == 'id_card_copy'){
							that.form.id_card_name = data.id_card_name;
							that.form.id_card_number = data.id_card_number;
							that.form.id_card_address = data.id_card_address;
						}
						
						if(field == 'id_card_national'){
							that.form.card_period_begin = data.card_period_begin;
							if(data.card_period_end_cq){
								that.form.card_period_end_cq = data.card_period_end_cq;
							}
							if(data.card_period_end){
								that.form.card_period_end = data.card_period_end;
							}
						}
						that.txt = Math.random()
					}
				});
			}
		}
	}
</script>
<style>
	page{position:relative;width:100%;height:100%}
	.flex-sb{display:flex;justify-content:space-between;align-items:center}
	.header{height:200rpx;position:absolute;top:0;width:100%;padding-top:70rpx;text-align:center;font-weight:bold;font-size:32rpx}
	.container{position:absolute;width:100%;top:100rpx;border-radius:16rpx;padding-bottom:100rpx}
	.box{background:#FFFFFF;border-radius:24rpx;padding:20rpx;width:92%;margin:0 4% 20rpx 4%}
	.content{line-height:40rpx;font-size:24rpx}
	.placeholder{font-size:24rpx;color:#BBBBBB}
	.form-item{margin-bottom:30rpx}
	.form-title{display:flex;font-size:32rpx;font-weight:bold;width:100%; border-bottom: 1px #f5f5f5 solid; height:88rpx; line-height:88rpx; overflow: hidden;}
	.form-title .strip{margin: 24rpx 10rpx;padding: 5rpx;}
	.form-label{height:70rpx;line-height:70rpx;margin-bottom:10rpx}
	.form-value{flex:1}
	.uploadbtn1{width:150rpx;height:150rpx}
	.picker-range{border:1rpx solid #F0F0F0;height:60rpx;line-height:60rpx;border-radius:6rpx;padding:0 10rpx}
	.imgbox{display:flex;align-items:center;flex-wrap:wrap}
	.imgbox .imgitem-pics{width:150rpx;height:150rpx;margin:8rpx;background:#F0F0F0;overflow: hidden;}
	.imgbox .imgitem-pics image{width:100%;height:100%}
	.form-value .form-select,.form-value input,.form-value .picker{font-size:24rpx;border-radius:8rpx;height:70rpx;line-height:70rpx;border:1rpx solid #f0f0f0;padding:0 10rpx;flex:1}
	.form-label .required{color:#ff2400;vertical-align: middle;margin-right: 5rpx;}
	.form-value .checkbox-lebel{margin-right: 35rpx;}
	.form-tips{font-size:24rpx;flex-shrink:0;color:#999;margin-top:15rpx}
	.form-opt{position:fixed;bottom:0;width:92%;left:4%;background:#F6F6F6;height:120rpx;display:flex;align-items:center;justify-content:space-between;font-size:24rpx;color:#333;z-index:100}
	.btn{text-align:center;border-radius:50rpx;color:#FFFFFF;flex:1;height:84rpx;line-height:84rpx}
	.form-select{display:flex;align-items:center;justify-content:space-between;overflow:hidden}
	.select-txt{white-space:nowrap;text-overflow:ellipsis;overflow:hidden;max-width:300rpx}
	.down{width:24rpx;height:24rpx;flex-shrink:0}
	.grey{color:#666}
	.layui-imgbox{margin-right:16rpx;margin-bottom:10rpx;font-size:24rpx;position: relative;}
	.layui-imgbox-img{display: block;width:150rpx;height:150rpx;padding:2px;border: #d3d3d3 1px solid;background-color: #f6f6f6;overflow:hidden}
	.layui-imgbox-img>image{max-width:100%;}
	.layui-imgbox-repeat{position: absolute;display: block;width:32rpx;height:32rpx;line-height:28rpx;right: 2px;bottom:2px;color:#999;font-size:30rpx;background:#fff}
</style>