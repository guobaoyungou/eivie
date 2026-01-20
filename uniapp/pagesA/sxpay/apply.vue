<template>
	<view class="body">
		<block v-if="isload">
			<view class="header"
				:style="{background:'linear-gradient(180deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0) 100%)'}">
				随行付入驻申请
			</view>
			<view class="container">
				<form @submit="formSubmit">
					<block v-if="step==1">
						<view class="box">
							<view class="form-title">主体类型</view>
							<view class="form-item">
								<radio-group class="flex-sb" style="width: 100%;padding: 0 50rpx;" name="subject_type"
									@change="itemChange" data-field="subject_type">
									<label>
										<radio value="SUBJECT_TYPE_INDIVIDUAL" style="transform: scale(0.8);" :checked="form.subject_type=='SUBJECT_TYPE_INDIVIDUAL'?true:false"></radio>
										个体户
									</label>
									<label>
										<radio value="SUBJECT_TYPE_ENTERPRISE" style="transform: scale(0.8);" :checked="form.subject_type=='SUBJECT_TYPE_ENTERPRISE'?true:false"></radio>企业
									</label>
									<label>
										<radio value="SUBJECT_TYPE_MICRO" style="transform: scale(0.8);" :checked="form.subject_type=='SUBJECT_TYPE_MICRO'?true:false"></radio>个人
									</label>
								</radio-group>
							</view>
						</view>
						<view class="box" v-if="form.subject_type!='SUBJECT_TYPE_MICRO'">
							<view class="form-title">营业执照信息</view>
							<view class="form-item">
								<view class="form-label"><text class="required">*</text>营业执照照片</view>
								<view class="form-value form-upload">
									<view class="imgitem" v-if="form.business_license_copy">
										<image :src="form.business_license_copy" @tap="previewImage"
											:data-url="form.business_license_copy" mode="widthFix"></image>
									</view>

									<view class="uploadrow">
										<view class="uploadbtn" @tap="uploadimg" data-field="business_license_copy"
											:style="'border:1px solid rgba('+t('color1rgb')+',0.3);color:'+t('color1')">
											点击上传</view>
										<view class="uploadtip">图片需小于2MB</view>
									</view>
								</view>
							</view>
							<view class="form-item">
								<view class="form-label"><text class="required">*</text>统一社会信用代码</view>
								<view class="form-value">
									<input type="text" name="business_license_number"
										v-model="form.business_license_number" placeholder="请填写统一社会信用代码"
										placeholder-class="placeholder">
								</view>
							</view>
							<view class="form-item">
								<view class="form-label"><text class="required">*</text>企业名称</view>
								<view class="form-value">
									<input type="text" name="business_merchant_name"
										v-model="form.business_merchant_name" placeholder="请填写企业名称"
										placeholder-class="placeholder">
								</view>
							</view>
							<view class="form-item">
								<view class="form-label"><text class="required">*</text>经营者/法人姓名</view>
								<view class="form-value">
									<input type="text" name="business_legal_person" v-model="form.business_legal_person"
										placeholder="请填写经营者/法人姓名" placeholder-class="placeholder">
								</view>
							</view>
						</view>
						<view class="box">
							<view class="form-title">
								经营者/法人身份证
							</view>
							<view class="form-item">
								<view class="form-label"><text class="required">*</text>身份证人像面</view>
								<view class="form-value form-upload">
									<view class="imgitem" v-if="form.identity_id_card_copy">
										<image :src="form.identity_id_card_copy" @tap="previewImage"
											:data-url="form.identity_id_card_copy" mode="widthFix"></image>
									</view>
									<view class="uploadrow">
										<view class="uploadbtn" @tap="uploadimg" data-field="identity_id_card_copy"
											:style="'border:1px solid rgba('+t('color1rgb')+',0.3);color:'+t('color1')">
											点击上传</view>
										<view class="uploadtip">图片需小于2MB</view>
									</view>
								</view>
							</view>
							<view class="form-item">
								<view class="form-label"><text class="required">*</text>身份证国徽面</view>
								<view class="form-value form-upload">
									<view class="imgitem" v-if="form.identity_id_card_national">
										<image :src="form.identity_id_card_national" @tap="previewImage"
											:data-url="form.identity_id_card_national" mode="widthFix"></image>
									</view>
									<view class="uploadrow">
										<view class="uploadbtn" @tap="uploadimg" data-field="identity_id_card_national"
											:style="'border:1px solid rgba('+t('color1rgb')+',0.3);color:'+t('color1')">
											点击上传</view>
										<view class="uploadtip">图片需小于2MB</view>
									</view>
								</view>
							</view>
							<view class="form-item">
								<view class="form-label"><text class="required">*</text>身份证姓名</view>
								<view class="form-value">
									<input type="text" name="identity_id_card_name" v-model="form.identity_id_card_name"
										placeholder="请填写身份证姓名" placeholder-class="placeholder">
								</view>
							</view>
							<view class="form-item">
								<view class="form-label"><text class="required">*</text>身份证号码</view>
								<view class="form-value">
									<input type="text" name="identity_id_card_number"
										v-model="form.identity_id_card_number" placeholder="请填写身份证号码"
										placeholder-class="placeholder">
								</view>
							</view>
							<view class="form-item">
								<view class="form-label"><text class="required">*</text>身份证有效期</view>
								<view class="form-value flex-sb">
									<view class="flex1">
										<view :class="form.identity_id_card_valid_time1?'':'placeholder'" class="picker-range">
											<picker mode="date" name="identity_id_card_valid_time1" @change="itemChange"
												data-field="identity_id_card_valid_time1">
												{{form.identity_id_card_valid_time1?form.identity_id_card_valid_time1:'请选择日期'}}
											</picker>
										</view>
										<view style="margin-top: 6rpx;"
											:class="form.identity_id_card_valid_time2?'':'placeholder'" class="picker-range">
											<picker mode="date" name="identity_id_card_valid_time2" @change="itemChange"
												data-field="identity_id_card_valid_time2">
												{{form.identity_id_card_valid_time2?form.identity_id_card_valid_time2:'请选择日期'}}
											</picker>
										</view>
									</view>
									<checkbox-group name="identity_id_card_valid_time_cq"
										v-model="form.identity_id_card_valid_time_cq" @change="itemChange"
										data-field="identity_id_card_valid_time_cq">
										<label class="form-tips">
											<checkbox value="1" style="transform: scale(0.7);" :checked="form.identity_id_card_valid_time_cq.length>0?true:false"></checkbox>长期
										</label>
									</checkbox-group>
								</view>
							</view>
							<view class="form-item">
								<view class="form-label"><text class="required">*</text>联系人手机号</view>
								<view class="form-value">
									<input type="text" name="contact_mobile" v-model="form.contact_mobile"
										placeholder="请填写联系人手机号" placeholder-class="placeholder">
								</view>
							</view>
							<view class="form-item">
								<view class="form-label"><text class="required">*</text>联系人邮箱</view>
								<view class="form-value">
									<input type="text" name="contact_email" v-model="form.contact_email"
										placeholder="请填写联系人邮箱" placeholder-class="placeholder">
								</view>
							</view>
						</view>
					</block>
					<block v-if="step==2">
						<view class="box">
							<view class="form-title">经营资料</view>
							<view class="form-item">
								<view class="form-label"><text class="required">*</text>商户简称</view>
								<view class="form-value">
									<input type="text" name="merchant_shortname" v-model="form.merchant_shortname"
										placeholder="请填写商户简称" placeholder-class="placeholer">
								</view>
							</view>
							<view class="form-item">
								<view class="form-label"><text class="required">*</text>客服电话</view>
								<view class="form-value">
									<input type="text" name="service_phone" v-model="form.service_phone"
										placeholder="请填写客服电话" placeholder-class="placeholer">
								</view>
							</view>
							<view class="form-item">
								<view class="form-label"><text class="required">*</text>经营类目</view>
								<view class="form-value" @tap="showModal('category')">
									<view class="form-select">
										<view class="select-txt">{{category_name?category_name:'请选择经营类目'}}</view>
										<image class="down" :src="pre_url+'/static/img/arrowdown.png'"></image>
									</view>
								</view>
							</view>
							<view class="form-item">
								<view class="form-label"><text class="required">*</text>经营地区</view>
								<view class="form-value">
									<uni-data-picker class="" :localdata="arealist" data-field="area" popup-title="地区" @change="areaChange" :placeholder="'地区'">
										<view class="form-select">
											<view class="select-txt">{{area.length>0?area.join('/'):'请选择经营地区'}}</view>
											<image class="down" :src="pre_url+'/static/img/arrowdown.png'"></image>
										</view>
									</uni-data-picker>
								</view>
							</view>
							<view class="form-item">
								<view class="form-label"><text class="required">*</text>经营详细地址</view>
								<view class="form-value">
									<input type="text" name="store_street" v-model="form.store_street"
										placeholder="请填写经营详细地址" placeholder-class="store_street">
								</view>
							</view>
							<view class="form-item">
								<view class="form-label"><text class="required">*</text>门头照</view>
								<view class="form-value form-upload">
									<view class="imgitem" v-if="form.store_entrance_pic">
										<image :src="form.store_entrance_pic" @tap="previewImage"
											:data-url="form.store_entrance_pic" mode="widthFix"></image>
									</view>
									<view class="uploadrow">
										<view class="uploadbtn" @tap="uploadimg" data-field="store_entrance_pic"
											:style="'border:1px solid rgba('+t('color1rgb')+',0.3);color:'+t('color1')">
											点击上传</view>
										<view class="uploadtip">图片需小于2MB</view>
									</view>
								</view>
							</view>
							<view class="form-item">
								<view class="form-label"><text class="required">*</text>内景照</view>
								<view class="form-value form-upload">
									<view class="imgitem" v-if="form.indoor_pic">
										<image :src="form.indoor_pic" @tap="previewImage" :data-url="form.indoor_pic"
											mode="widthFix"></image>
									</view>
									<view class="uploadrow">
										<view class="uploadbtn" @tap="uploadimg" data-field="indoor_pic"
											:style="'border:1px solid rgba('+t('color1rgb')+',0.3);color:'+t('color1')">
											点击上传</view>
										<view class="uploadtip">图片需小于2MB</view>
									</view>
								</view>
							</view>
							<view class="form-item">
								<view class="form-label">其他资料照片</view>
								<view class="form-value form-upload">
									<view class="imgbox">
										<block v-if="store_other_pics.length>0"
											v-for="(pic,pindex) in store_other_pics">
											<view class="imgitem-pics">
												<image :src="pic" @tap="previewImage" :data-url="pic" mode="widthFix">
												</image>
											</view>
										</block>
										<view class="imgitem-pics">
											<view class="uploadbtn1" @tap="uploadimg" data-field="store_other_pics"
												data-pernum="5"
												:style="{background:'url('+pre_url+'/static/img/shaitu_icon.png) no-repeat 44rpx',backgroundSize:'60rpx 60rpx',backgroundColor:'#F3F3F3'}">
											</view>
										</view>
									</view>
							</view>
								</view>
						</view>

						<view class="box">
							<view class="form-title">结算银行账户</view>
							<view class="form-tips-block">
								<view>*选择“经营者个人银行卡”时，开户名称必须与“经营者证件姓名”一致。</view>
								<view v-if="form.subject_type!='SUBJECT_TYPE_MICRO'">*选择“对公银行账户”时，开户名称必须与营业执照上的“商户名称”一致。
								</view>
							</view>
							<view class="form-item">
								<view class="form-label"><text class="required">*</text>账户类型</view>
								<view class="form-value">
									<radio-group class="radio-row" name="jiesuan_bank_account_type" @change="itemChange"
										data-field="jiesuan_bank_account_type">
										<label v-if="form.subject_type!='SUBJECT_TYPE_MICRO'">
											<radio value="BANK_ACCOUNT_TYPE_CORPORATE" style="transform: scale(0.8);" :checked="form.jiesuan_bank_account_type=='BANK_ACCOUNT_TYPE_CORPORATE'?true:false"></radio>对公银行账户
										</label>
										<label>
											<radio value="BANK_ACCOUNT_TYPE_PERSONAL" style="transform: scale(0.8);" :checked="form.jiesuan_bank_account_type=='BANK_ACCOUNT_TYPE_PERSONAL'?true:false">
											</radio>个人银行账户
										</label>
									</radio-group>
								</view>
							</view>
							<view class="form-item" v-if="form.jiesuan_bank_account_type=='BANK_ACCOUNT_TYPE_CORPORATE'">
								<view class="form-label"><text class="required">*</text>开户许可证</view>
								<view class="form-value form-upload">
									<view class="imgitem" v-if="form.account_license_pic">
										<image :src="form.account_license_pic" @tap="previewImage"
											:data-url="form.account_license_pic" mode="widthFix"></image>
									</view>
									<view class="uploadrow">
										<view class="uploadbtn" @tap="uploadimg" data-field="account_license_pic"
											:style="'border:1px solid rgba('+t('color1rgb')+',0.3);color:'+t('color1')">
											点击上传</view>
										<view class="uploadtip">图片需小于2MB</view>
									</view>
								</view>
							</view>
							<view class="form-item" v-if="form.jiesuan_bank_account_type=='BANK_ACCOUNT_TYPE_PERSONAL'">
								<view class="form-label"><text class="required">*</text>银行卡卡号面</view>
								<view class="form-value form-upload">
									<view class="imgitem" v-if="form.bank_card_pic">
										<image :src="form.bank_card_pic" @tap="previewImage"
											:data-url="form.bank_card_pic" mode="widthFix"></image>
									</view>
									<view class="uploadrow">
										<view class="uploadbtn" @tap="uploadimg" data-field="bank_card_pic"
											:style="'border:1px solid rgba('+t('color1rgb')+',0.3);color:'+t('color1')">
											点击上传</view>
										<view class="uploadtip">图片需小于2MB</view>
									</view>
								</view>
							</view>
							<view class="form-item">
								<view class="form-label"><text class="required">*</text>开户名称/持卡人姓名</view>
								<view class="form-value">
									<input type="text" name="jiesuan_account_name" v-model="form.jiesuan_account_name"
										placeholder="请填写开户名称/持卡人姓名" placeholder-class="jiesuan_account_name">
								</view>
							</view>
							<view class="form-item">
								<view class="form-label"><text class="required">*</text>开户银行</view>
								<view class="form-value">
									<picker :range="banklist" :value="bank_index>0?bank_index:''" @change="bankChange">
										<view class="form-select">
											<view class="select-txt">{{bank_name?bank_name:'请选择开户银行'}}</view>
											<image class="down" :src="pre_url+'/static/img/arrowdown.png'"></image>
										</view>
									</picker>
								</view>
							</view>

							<view class="form-item">
								<view class="form-label"><text class="required">*</text>开户银行地区</view>
								<view class="form-value">
									<uni-data-picker class="" :localdata="citylist" popup-title="地区" @change="cityChange" data-field="city" :placeholder="'地区'">
										<view class="form-select">
											<view class="select-txt">{{city.length>0?city.join('/'):'请选择开户银行地区'}}</view>
											<image class="down" :src="pre_url+'/static/img/arrowdown.png'"></image>
										</view>
									</uni-data-picker>
								</view>
							</view>
							<view class="form-item">
								<view class="form-label"><text class="required">*</text>所属分支行</view>
								<view class="form-value"  @tap="showModal('branch_bank')">
									<view class="form-select">
										<view class="select-txt">{{branch_bank_name?branch_bank_name:'请选择所属分支行'}}</view>
										<image class="down" :src="pre_url+'/static/img/arrowdown.png'"></image>
									</view>
									<!-- <picker :range="branchBanklist" range-key="bank_name">
										<view class="form-select">
											<view class="select-txt">{{branch_bank_name?branch_bank_name:'请选择所属分支行'}}</view>
											<image class="down" :src="pre_url+'/static/img/arrowdown.png'"></image>
										</view>
									</picker> -->
								</view>
							</view>

							<view class="form-item">
								<view class="form-label"><text class="required">*</text>银行账号</view>
								<view class="form-value">
									<input type="text" name="jiesuan_account_number"
										v-model="form.jiesuan_account_number" placeholder="请填写银行账号"
										placeholder-class="placeholer">
								</view>
							</view>
							<view class="form-item">
								<view class="form-label">结算费率</view>
								<view class="form-value">
									<text class="form-tips">{{set.feepercent}}</text>
								</view>
							</view>
							<view class="form-item">
								<view class="form-label">结算时间</view>
								<view class="form-value">
									<text class="form-tips">{{set.duration}}</text>
								</view>
							</view>
						</view>
					</block>
					<view style="display: none;">
						{{txt}}
					</view>
					<view class="form-opt">
						<button class="btn" v-if="step==1" :style="{background:t('color1')}" @tap="submit">{{step==1?'下一步':'提交审核'}}</button>
						<block v-else>
							<button class="btn btn1" @tap="prestep">上一步</button>
							<button class="btn btn2" :style="{background:t('color1')}" @tap="submit">提交审核</button>
						</block>
					</view>
				</form>
			</view>
			<!-- 经营类目选择start -->
			<view class="popup__container modal-s" v-if="isShowModal" style="z-index: 999;">
				<view class="popup__overlay" @tap.stop="hideModal"></view>
				<view class="popup__modal">
					<view class="popup__title">
						<view class="popup__title-text">请选择<text v-if="modal_type=='category'">经营类目</text><text v-if="modal_type=='branch_name'">分支行</text></view>
						<image :src="pre_url+'/static/img/close.png'" class="popup__close" style="width:36rpx;height:36rpx"
							@tap.stop="hideModal" />
					</view>
					<view class="popup__content select_content">
						<view class="modal-search">
							<input type="text" placeholder="输入关键字检索" placeholder-class="placeholder"
								@confirm="modalSearch" v-model="modal_keyword" />
							<image :src="pre_url+'/static/img/search.png'" @tap="modalSearch"></image>
						</view>
						<!-- 经营类目 -->
						<view class="modal-body">
							<block v-if="modal_type=='category'"  v-for="(item,index) in mccCdArr" :key="index">
								<view class="select-item" :data-index="index" :data-name="item" @tap="chooseCategory"
									:style="category_name==item?('background:rgba('+t('color1rgb')+',0.16);color:'+t('color1')):''">
									{{item}}
								</view>
							</block>
							<!-- 分行 -->
							<block v-if="modal_type=='branch_bank'" v-for="(item,index) in modalBranchList" :key="index">
								<view class="select-item" :data-bankno="item.bank_no" :data-bankname="item.bank_name" @tap="chooseBranchBank"
									:style="branch_bank_no==item.bank_no?('background:rgba('+t('color1rgb')+',0.16);color:'+t('color1')):''">
									{{item.bank_name}}
								</view>
							</block>
						</view>
					</view>
				</view>
			</view>
			<!-- 经营类目选择end -->
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
				step:1,
				id: 0,
				set:{},
				detail: {},
				form: {},
				subject_type: '',
				txt: 1,
				business_license_copy: '', //营业执照
				identity_id_card_copy: '',
				identity_id_card_national: '',
				canSubmit: true,
				mccCdArr: [], //经营类目
				isShowModal: false,
				modal_keyword: '',
				modal_type:'',
				modal_item_name:'',
				modalBranchList:[],//筛选分行
				category_name: '',
				arealist: [],
				area: [],
				citylist:[],
				city:[],
				banklist: [],
				bank_name:'',
				bank_index:-1,
				branchBanklist:[],//地区全量分行信息
				branch_bank_no:'',
				branch_bank_name:'',
				store_other_pics:[]
			}
		},
		onLoad: function(opt) {
			var that = this;
			var opt = app.getopts(opt);
			that.opt = opt;
			that.id = that.opt.id || 0
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
				app.get('ApiSxpay/apply', {}, function(res) {
					that.loading = false;
					if (res.status == 1) {
						that.form = res.data
						that.mccCdArr = res.mccCdArr
						that.banklist = res.banklist
						that.set = res.set
						if(res.detail){
							that.form = res.detail
							if(res.detail.mccCd){
								for(let mckey in that.mccCdArr){
									if(mckey==res.detail.mccCd){
										that.category_name = that.mccCdArr[mckey]
										break;
									}
								}
							}
							//经营地址
							if(that.form.store_province){
								that.area.push(that.form.store_province)
							}
							if(that.form.store_city){
								that.area.push(that.form.store_city)
							}
							if(that.form.store_area){
								that.area.push(that.form.store_area)
							}
							//开户城市
							if(that.form.jiesuan_bank_province){
								that.city.push(that.form.jiesuan_bank_province)
							}
							if(that.form.jiesuan_bank_city){
								that.city.push(that.form.jiesuan_bank_city)
							}
							//开户银行
							if(that.form.jiesuan_account_bank){
								console.log(that.form.jiesuan_account_bank)
								for(let i in that.banklist){
									if(that.form.jiesuan_account_bank==that.banklist[i]){
										that.bank_index = i;
										that.bank_name = that.banklist[i]
										break;
									}
								}
							}
							//分行
							if(that.form.jiesuan_bank_name){
								var bankArr = that.form.jiesuan_bank_name.split('-')
								if(bankArr.length>1){
									that.branch_bank_no = bankArr[0]
									that.branch_bank_name = bankArr[1]
								}
							}
							//其他资料
							if(that.form.store_other_pics){
								that.store_other_pics = that.form.store_other_pics.split(',')
							}
						}
						that.initAreaList()
						that.loaded();
					} else {
						app.alert(res.msg);
					}
				});
			},
			initAreaList: function(e) {
				var that = this;
				uni.request({
					url: app.globalData.pre_url + '/static/area.json',
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
							let children = item1.children //市
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
					}
				});
				
				uni.request({
					url: app.globalData.pre_url + '/static/area.json',
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
							that.txt = Math.random()
						} else {
							if(field=='store_other_pics'){
								that.store_other_pics.push(urls[i]);
							}else{
								that.form[field].push(urls[i]);
							}
							that.txt = Math.random()
						}
					}
				}, pernum);
			},
			itemChange: function(e) {
				var that = this;
				var field = e.currentTarget.dataset.field
				that.form[field] = e.detail.value;
				that.txt = Math.random()
			},
			showModal: function(type) {
				this.modal_keyword = ''
				if(type=='category'){
					this.modalDatalist = this.mccCdArr
					this.modal_item_name = this.category_name
				}else if(type=='branch_bank'){
					if(this.bank_name=='' || this.city.length==0){
						app.error('请先选择开户银行和开户银行地区');return;
					}
					this.modalBranchList = this.branchBanklist
					this.modal_item_name = this.branch_bank_name
				}
				this.modal_type = type
				this.isShowModal = true
			},
			hideModal: function() {
				this.isShowModal = false
			},
			modalSearch:function(){
				if(this.modal_type=='category'){
					this.categorySearch();
				}else if(this.modal_type=='branch_bank'){
					this.branchFilter();
				}
			},
			categorySearch: function(e) {
				var that = this
				if (that.modal_keyword) {
					app.loading = true
					app.post("ApiSxpay/mccCategorySearch", {
						keyword: that.modal_keyword
					}, function(data) {
						app.loading = false;
						that.mccCdArr = data.data
					});
				}
			},
			areaChange: function(e) {
				var that = this
				var arr = e.detail.value
				var area = [];
				for (let i in arr) {
					area.push(arr[i].text)
				}
				that.area = area
			},
			cityChange: function(e) {
				var that = this
				var arr = e.detail.value
				var city = [];
				for (let i in arr) {
					city.push(arr[i].text)
				}
				that.city = city;
				that.getBranchBankList();
			},
			bankChange:function(e){
				var that = this
				var bank_index = e.detail.value
				that.bank_name = that.banklist[bank_index]
				that.getBranchBankList()
			},
			getBranchBankList:function(){
				var that = this
				if(that.bank_name && that.city && that.city.length>0){
					that.loading = true;
					app.post("ApiSxpay/getBranchBankList", {
						bank: that.bank_name,
						city: that.city
					}, function(res) {
						that.loading = false
						that.branchBanklist = res.data
					});
				}else{
					that.branchBanklist = [];
				}
			},
			chooseCategory: function(e) { 
				var that = this;
				var index = e.currentTarget.dataset.index;
				var name = e.currentTarget.dataset.name;
				that.form.mccCd = index
				that.category_name = name
				that.txt = Math.random()
				that.isShowModal = false
			},
			chooseBranchBank: function(e) {
				var that = this;
				var bank_no = e.currentTarget.dataset.bankno;
				var bank_name = e.currentTarget.dataset.bankname;
				that.branch_bank_name = bank_name
				that.branch_bank_no = bank_no
				that.txt = Math.random()
				that.isShowModal = false
			},
			prestep:function(){
				this.step--;
			},
			submit: function(e) {
				var that = this;
				var formdata = that.form;
				if (that.step == 1) {
					//第一步校验
					if(!formdata.subject_type){
						app.error('请选择主体类型');
						return;
					}
					if (formdata.subject_type != 'SUBJECT_TYPE_MICRO') {
						if (!formdata.business_license_copy) {
							app.error('请上传营业执照照片');
							return;
						}
						if (!formdata.business_license_number) {
							app.error('请填写统一社会信用代码');
							return;
						}
						if (!formdata.business_merchant_name) {
							app.error('请填写企业名称');
							return;
						}
						if (!formdata.business_legal_person) {
							app.error('请填写经营者/法人姓名');
							return;
						}
					}
					if (!formdata.identity_id_card_copy) {
						app.error('请上传身份证人像面');
						return;
					}
					if (!formdata.identity_id_card_national) {
						app.error('请上传身份证国徽面');
						return;
					}

					if (!formdata.identity_id_card_name) {
						app.error('请填写身份证姓名');
						return;
					}
					if (!formdata.identity_id_card_number) {
						app.error('请填写身份证号码');
						return;
					}
					if ((!formdata.identity_id_card_valid_time_cq || formdata.identity_id_card_valid_time_cq.length ==
							0) && (!formdata.identity_id_card_valid_time1 || !formdata.identity_id_card_valid_time2)) {
						app.error('请填写身份证有效期');
						return;
					}
					if (!formdata.contact_mobile) {
						app.error('请填写联系人手机号');
						return;
					}
					if (!app.isPhone(formdata.contact_mobile)) {
						app.error("手机号填写有误");
						return false;
					}
					that.step = 2;
				} else if (that.step == 2) {
					if (!formdata.merchant_shortname) {
						app.error('请填写商户简称');
						return;
					}
					if (!formdata.service_phone) {
						app.error('请填写客服电话');
						return;
					}
					if (!formdata.mccCd) {
						app.error('请选择经营类目');
						return;
					}
					if (that.area.length==0) {
						app.error('请选择经营地区');
						return;
					}
				  formdata['store_province'] = that.area[0];
					formdata['store_city'] = that.area[1];
					formdata['store_area'] = that.area[2];
					if (!formdata.store_street) {
						app.error('请填写经营详细地址');
						return;
					}
					if (!formdata.store_entrance_pic) {
						app.error('请上传门头照');
						return;
					}
					if (!formdata.indoor_pic) {
						app.error('请上传内景照');
						return;
					}
					
					if (!formdata.jiesuan_bank_account_type) {
						app.error('请选择账户类型');
						return;
					}
					if(formdata.jiesuan_bank_account_type=='BANK_ACCOUNT_TYPE_PERSONAL'){
						if (!formdata.bank_card_pic) {
							app.error('请上传银行卡卡号面');
							return;
						}
					}
					if(formdata.jiesuan_bank_account_type=='BANK_ACCOUNT_TYPE_CORPORATE'){
						if (!formdata.account_license_pic) {
							app.error('请上传开户许可证');
							return;
						}
					}
					
					
					if (!formdata.jiesuan_account_name) {
						app.error('请填写开户名称/持卡人姓名');
						return;
					}
					if (that.bank_name=='') {
						app.error('请选择开户银行');
						return;
					}
					formdata['jiesuan_account_bank'] = that.bank_name
					if (that.city.length==0) {
						app.error('请选择开户银行地区');
						return;
					}
					formdata['jiesuan_bank_province'] = that.city[0]
					formdata['jiesuan_bank_city'] = that.city[1]
					if (that.branch_bank_name=='') {
						app.error('请选择开户银行所属分支行');
						return;
					}
					formdata['jiesuan_bank_name'] = that.branch_bank_no+'-'+that.branch_bank_name
					if (!formdata.jiesuan_account_number) {
						app.error('请填写银行账号');
						return;
					}
					if(that.store_other_pics.length>0){
						formdata['store_other_pics'] = that.store_other_pics.join(',')
					}
					that.canSubmit = false;
					app.showLoading('提交中');
					app.post("ApiSxpay/apply", formdata, function(data) {
						app.showLoading(false);
						// that.canSubmit = true;
						if (data.status == 1) {
								app.success(data.msg)
								setTimeout(function(){
									app.goto('myapply');
								},1000)
						} else {
							that.canSubmit = true;
							app.error(data.msg);
						}
					});
					
				}



			}
		}
	}
</script>
<style>
	page {
		position: relative;
		width: 100%;
		height: 100%;
	}

	.flex-sb {
		display: flex;
		justify-content: space-between;
		align-items: center;
	}

	.header {
		height: 260rpx;
		position: absolute;
		top: 0;
		width: 100%;
		padding-top: 70rpx;
		text-align: center;
		font-weight: bold;
		font-size: 32rpx;
	}

	.container {
		position: absolute;
		width: 100%;
		top: 160rpx;
		border-radius: 16rpx;
		padding-bottom: 100rpx;
	}

	.box {
		background: #FFFFFF;
		border-radius: 24rpx;
		padding: 20rpx;
		width: 92%;
		margin: 0 4% 20rpx 4%;
	}

	.content {
		line-height: 40rpx;
		font-size: 24rpx;
	}

	.placeholder {
		font-size: 24rpx;
		color: #BBBBBB;
	}

	.form-item {
		display: flex;
		align-items: center;
		margin-bottom: 20rpx;
	}

	.form-title {
		font-size: 32rpx;
		padding-bottom: 20rpx;
		border-bottom: 1rpx solid #f0f0f0;
		margin-bottom: 30rpx;
	}

	.form-label {
		flex-shrink: 0;
		width: 120px;
		text-align: right;
		margin-right: 20rpx;
	}

	.form-value {
		flex: 1;
		padding: 0 10rpx;
	}

	.form-upload {}

	.uploadrow {
		display: flex;
		align-items: center;
	}

	.uploadbtn {
		width: 150rpx;
		height: 60rpx;
		line-height: 60rpx;
		text-align: center;
		border: 1rpx solid #F0F0F0;
		border-radius: 6rpx;
		color: #666;
	}

	.uploadbtn1 {
		width: 150rpx;
		height: 150rpx;
	}

	.uploadtip {
		font-size: 20rpx;
		color: #999;
		padding-left: 10rpx;
	}

	.form-upload .imgitem {
		width: 240rpx;
		height: 150rpx;
		margin-right: 10rpx;
		background: #f5f5f5;
		display: flex;
		align-items: center;
		justify-content: center;
		margin-bottom: 10rpx;
	}

	.form-upload .imgitem image {
		max-width: 100%;
		max-height: 100%;
	}
	
	.picker-range{border: 1rpx solid #F0F0F0;height: 60rpx;line-height: 60rpx;border-radius: 6rpx;padding: 0 10rpx;}

	/* 多图上传 */
	.imgbox {
		display: flex;
		align-items: center;
		flex-wrap: wrap;
	}

	.imgbox .imgitem-pics {
		width: 150rpx;
		height: 150rpx;
		margin: 8rpx;
		background: #F0F0F0;
	}

	.imgbox .imgitem-pics image {
		max-width: 100%;
		max-height: 100%;
	}

	.form-radio {
		border: none;
	}

	.form-radio label {
		margin-right: 20rpx;
	}

	.form-value .form-select,
	.form-value input,
	.form-value .picker {
		font-size: 24rpx;
		border-radius: 8rpx;
		height: 70rpx;
		line-height: 70rpx;
		border: 1rpx solid #f0f0f0;
		padding: 0 10rpx;
		flex: 1;
	}

	.radio-row {
		display: flex;
		flex-direction: column;
		width: 100%;
	}

	.form-label .required {
		color: #ff2400;
	}

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

	.form-opt {
		position: fixed;
		bottom: 0;
		width: 92%;
		left: 4%;
		background: #F6F6F6;
		height: 120rpx;
		display: flex;
		align-items: center;
		justify-content: space-between;
		font-size: 24rpx;
		color: #333;
		z-index: 100;
	}

	.btn {
		text-align: center;
		border-radius: 50rpx;
		color: #FFFFFF;
		flex: 1;
		height: 84rpx;
		line-height: 84rpx;
	}
	.btn.btn1{border-radius: 50rpx 0 0 50rpx;background: #888;color: #fff;}
	.btn.btn2{border-radius: 0 50rpx 50rpx 0;}

	.form-select {
		display: flex;
		align-items: center;
		justify-content: space-between;
		overflow: hidden;
	}

	.select-txt {
		white-space: nowrap;
		text-overflow: ellipsis;
		overflow: hidden;
		max-width: 300rpx;
	}

	.down {
		width: 24rpx;
		height: 24rpx;
		flex-shrink: 0;
	}

	.modal-s .popup__overlay {
		opacity: 0.6;
	}

	.modal-s .popup__modal {
		width: 100%;
		max-height: 1100rpx;
		height: 1100rpx;
	}

	.modal-s .popup__content {
		max-height: 1100rpx;
		padding: 0 20rpx;
		height: 1100rpx;
	}

	.modal-s .select-item {
		background: #F6F6F6;
		border-radius: 6rpx;
		padding: 20rpx;
		margin-bottom: 14rpx;
	}

	.modal-s .modal-search {
		border: 1rpx solid #E5E5E5;
		border-radius: 10rpx;
		height: 70rpx;
		line-height: 70rpx;
		display: flex;
		align-items: center;
		padding: 0 20rpx;
		justify-content: space-between;
	}

	.modal-search image {
		width: 44rpx;
		height: 44rpx;
	}

	.modal-s .modal-body {
		margin-top: 14rpx;
	}

	.modal-body .on {}
</style>
