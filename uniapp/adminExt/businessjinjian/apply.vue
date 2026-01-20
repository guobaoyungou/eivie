<template>
	<view class="body">
		<block v-if="isload">
			<block v-if="apply <= 0">
				<view class="header"
					:style="{background:'linear-gradient(180deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0) 100%)'}">
					商户进件
				</view>
				<view class="container">
					<form @submit="formSubmit">
						<!-- 超级管理员信息 -->
						<block v-if="step == 1">
							<view class="box">
								<view class="form-title">超级管理员信息</view>
								<view class="form-item" v-if="formType == 0 || formType == 1">
									<view class="form-item-center">
										<view class="form-label"><text class="required">*</text>超级管理员类型</view>
										<view class="form-value">
											<radio-group class="flex-sb" style="width: 100%;" name="contact_type" @change="itemChange" data-field="contact_type">
												<label><radio value="LEGAL" style="transform: scale(0.8);" :checked="form.contact_type=='LEGAL'?true:false"></radio>经营者/法人</label>
												<label><radio value="SUPER" style="transform: scale(0.8);" :checked="form.contact_type=='SUPER'?true:false"></radio>经办人</label>
											</radio-group>
										</view>
									</view>
								</view>
								<view class="form-item">
									<view class="form-item-center">
										<view class="form-label"><text class="required">*</text>超级管理员姓名</view>
										<view class="form-value">
											<input type="text" name="contact_name" v-model="form.contact_name" placeholder="请填写超级管理员姓名" placeholder-class="placeholder">
										</view>
									</view>
								</view>
								<view v-if="form.contact_type == 'SUPER'">
									<view class="form-item">
										<view class="form-item-center">
											<view class="form-label"><text class="required">*</text>身份证件号码</view>
											<view class="form-value">
												<input type="text" name="contact_id_number" v-model="form.contact_id_number" placeholder="请填写身份证件号码" placeholder-class="placeholder">
											</view>
										</view>
									</view>
									<view class="form-item">
										<view class="form-item-center">
											<view class="form-label"><text class="required">*</text>身份证件正面照片</view>
											<view class="form-value form-upload">
												<view class="imgitem" v-if="form.contact_id_doc_copy">
													<image :src="form.contact_id_doc_copy" @tap="previewImage" :data-url="form.contact_id_doc_copy" mode="widthFix"></image>
												</view>
												<view class="uploadrow">
													<view class="uploadbtn" @tap="uploadimg" data-field="contact_id_doc_copy" :style="'border:1px solid rgba('+t('color1rgb')+',0.3);color:'+t('color1')">点击上传</view>
													<view class="uploadtip">图片需小于2MB</view>
												</view>
											</view>
										</view>
									</view>
									<view class="form-item">
										<view class="form-item-center">
											<view class="form-label"><text class="required">*</text>身份证件反面照片</view>
											<view class="form-value form-upload">
												<view class="imgitem" v-if="form.contact_id_doc_copy_back">
													<image :src="form.identity_id_card_national" @tap="previewImage" :data-url="form.contact_id_doc_copy_back" mode="widthFix"></image>
												</view>
												<view class="uploadrow">
													<view class="uploadbtn" @tap="uploadimg" data-field="contact_id_doc_copy_back" :style="'border:1px solid rgba('+t('color1rgb')+',0.3);color:'+t('color1')">点击上传</view>
													<view class="uploadtip">图片需小于2MB</view>
												</view>
											</view>
										</view>
									</view>
									<view class="form-item">
										<view class="form-item-center">
											<view class="form-label"><text class="required">*</text>身份证件有效期时间</view>
											<view class="form-value flex-sb">
												<view class="flex1">
													<view :class="form.contact_period_begin?'':'placeholder'" class="picker-range">
														<picker mode="date" name="contact_period_begin" @change="itemChange" data-field="contact_period_begin"> {{form.contact_period_begin ? form.contact_period_begin : '请选择日期'}}</picker>
													</view>
													<view style="margin-top: 6rpx;" :class="form.contact_period_end?'':'placeholder'" class="picker-range">
														<picker mode="date" name="contact_period_end" @change="itemChange" data-field="contact_period_end">{{form.contact_period_end ? form.contact_period_end : '请选择日期'}}</picker>
													</view>
												</view>
												<checkbox-group  @change="itemChange" data-field="contact_period_end_cq">
													<label class="form-tips">
														<checkbox value="1" style="transform: scale(0.7);" :checked="form.contact_period_end_cq == 1 ? true : false"></checkbox>长期
													</label>
												</checkbox-group>
											</view>
										</view>
									</view>
									<view class="form-item-center">
										<view class="form-label"><text class="required">*</text>业务办理授权函</view>
										<view class="form-value form-upload">
											<view class="imgitem" v-if="form.business_authorization_letter">
												<image :src="form.business_authorization_letter" @tap="previewImage" :data-url="form.business_authorization_letter" mode="widthFix"></image>
											</view>
											<view class="uploadrow">
												<view class="uploadbtn" @tap="uploadimg" data-field="business_authorization_letter" :style="'border:1px solid rgba('+t('color1rgb')+',0.3);color:'+t('color1')">点击上传</view>
												<view class="uploadtip">图片需小于2MB</view>
											</view>
										</view>
									</view>
								</view>
								<view class="form-item">
									<view class="form-item-center">
										<view class="form-label"><text class="required">*</text>联系手机</view>
										<view class="form-value">
											<input type="text" name="mobile_phone" v-model="form.mobile_phone" placeholder="请填写联系手机" placeholder-class="placeholer">
										</view>
									</view>
								</view>
								<view class="form-item">
									<view class="form-item-center">
										<view class="form-label"><text class="required">*</text>联系人邮箱</view>
										<view class="form-value">
											<input type="text" name="contact_email" v-model="form.contact_email" placeholder="请填写联系人邮箱" placeholder-class="placeholder">
										</view>
									</view>
								</view>
							</view>
						</block>
						<!-- END 超级管理员信息 -->
						
						<!-- 主体资料 -->
						<block v-if="step == 2">
							<view class="box">
								<view class="form-title">主体资料</view>
								<view class="form-item">
									<view class="form-item-center">
										<view class="form-label"><text class="required">*</text>主体类型</view>
										<view class="form-value">
											<picker :range="subjectType" :value="form.subject_type > 0 ? form.subject_type : ''" @change="subjectChange" data-field="subject_type">
												<view class="form-select">
													<view class="select-txt">{{ subjectType[subjectIndex] ? subjectType[subjectIndex] : '请选择' }}</view>
													<image class="down" :src="pre_url+'/static/img/arrowdown.png'"></image>
												</view>
											</picker>
										</view>
									</view>
								</view>
								<!-- 营业执照 主体为个体户/企业，必填-->
								<view v-if="subjectIndex == 0 || subjectIndex == 5 || subjectIndex == 6">
									<view class="form-item">
										<view class="form-item-center">
											<view class="form-label"><text class="required">*</text>营业执照照片</view>
											<view class="form-value form-upload">
												<view class="imgitem" v-if="form.license_copy">
													<image :src="form.license_copy" @tap="previewImage" :data-url="form.license_copy" mode="widthFix"></image>
												</view>
												<view class="uploadrow">
													<view class="uploadbtn" @tap="uploadimg" data-field="license_copy" :style="'border:1px solid rgba('+t('color1rgb')+',0.3);color:'+t('color1')"> 点击上传</view>
													<view class="uploadtip">图片需小于2MB</view>
												</view>
											</view>
										</view>
									</view>
									<view class="form-item">
										<view class="form-item-center">
											<view class="form-label"><text class="required">*</text>注册号/统一社会信用代码</view>
											<view class="form-value">
												<input type="text" name="license_number" v-model="form.license_number" placeholder="请填写注册号/统一社会信用代码" placeholder-class="placeholder">
											</view>
										</view>
									</view>
									<view class="form-item">
										<view class="form-item-center">
											<view class="form-label"><text class="required">*</text>商户名称</view>
											<view class="form-value">
												<input type="text" name="merchant_name" v-model="form.merchant_name" placeholder="请填写商户名称" placeholder-class="placeholder">
											</view>
										</view>
									</view>
									<view class="form-item">
										<view class="form-item-center">
											<view class="form-label"><text class="required">*</text>个体户经营者/法人姓名</view>
											<view class="form-value">
												<input type="text" name="legal_person" v-model="form.legal_person" placeholder="请填写个体户经营者/法人姓名" placeholder-class="placeholder">
											</view>
										</view>
									</view>
								</view>
								<!-- END 营业执照 -->
								<view class="form-item" v-if="formType == 0 || formType  == 2">
									<view class="form-item-center">
										<view class="form-label"><text class="required">*</text>签约支付宝账户</view>
										<view class="form-value">
											<input type="text" name="binding_alipay_logon_id" v-model="form.binding_alipay_logon_id" placeholder="请填写签约支付宝账户" placeholder-class="placeholder">
										</view>
									</view>
									<view class="form-tips-block">商户主体与该支付宝账号主体相同</view>
								</view>
								
								<!-- 登记证书 主体为政府机关/事业单位/其他组织时，必填-->
								<view v-if="subjectIndex >= 1 && subjectIndex <= 4 ">
									<view class="form-item">
										<view class="form-item-center">
											<view class="form-label"><text class="required">*</text>登记证书照片</view>
											<view class="form-value form-upload">
												<view class="imgitem" v-if="form.cert_copy">
													<image :src="form.cert_copy" @tap="previewImage" :data-url="form.cert_copy" mode="widthFix"></image>
												</view>
												<view class="uploadrow">
													<view class="uploadbtn" @tap="uploadimg" data-field="cert_copy" :style="'border:1px solid rgba('+t('color1rgb')+',0.3);color:'+t('color1')"> 点击上传</view>
													<view class="uploadtip">图片需小于2MB</view>
												</view>
											</view>
										</view>
									</view>
									<view class="form-item">
										<view class="form-item-center">
											<view class="form-label"><text class="required">*</text>登记证书类型</view>
											<view class="form-value">
												<picker :range="certType[subjectIndex]" :value="certTypeIndex > 0 ? certTypeIndex : ''"  @change="itemChange" data-field="cert_type"> 
													<view class="form-select">
														<view class="select-txt">{{ form.cert_type ? form.cert_type : '请选择' }}</view>
														<image class="down" :src="pre_url+'/static/img/arrowdown.png'"></image>
													</view>
												</picker>
											</view>
										</view>
									</view>
									<view class="form-item">
										<view class="form-item-center">
											<view class="form-label"><text class="required">*</text>证书号</view>
											<view class="form-value">
												<input type="text" name="cert_number" v-model="form.cert_number" placeholder="请填写证书号" placeholder-class="placeholder">
											</view>
										</view>
									</view>
									<view class="form-item">
										<view class="form-item-center">
											<view class="form-label"><text class="required">*</text>商户名称</view>
											<view class="form-value">
												<input type="text" name="merchant_name" v-model="form.merchant_name" placeholder="请填写商户名称" placeholder-class="placeholder">
											</view>
										</view>
									</view>
									<view class="form-item">
										<view class="form-item-center">
											<view class="form-label"><text class="required">*</text>注册地址</view>
											<view class="form-value">
												<input type="text" name="company_address" v-model="form.company_address" placeholder="请填写注册地址" placeholder-class="placeholder">
											</view>
										</view>
									</view>
									<view class="form-item">
										<view class="form-item-center">
											<view class="form-label"><text class="required">*</text>法定代表人</view>
											<view class="form-value">
												<input type="text" name="legal_person" v-model="form.legal_person" placeholder="请填写法定代表人" placeholder-class="placeholder">
											</view>
										</view>
									</view>
									<view class="form-item">
										<view class="form-item-center">
											<view class="form-label"><text class="required">*</text>身份证有效期</view>
											<view class="form-value flex-sb">
												<view class="flex1">
													<view :class="form.period_begin?'':'placeholder'" class="picker-range">
														<picker mode="date" name="period_begin" @change="itemChange" data-field="period_begin"> {{form.period_begin ? form.period_begin : '请选择日期'}}</picker>
													</view>
													<view style="margin-top: 6rpx;" :class="form.period_end?'':'placeholder'" class="picker-range">
														<picker mode="date" name="period_end" @change="itemChange" data-field="period_end">{{form.period_end ? form.period_end : '请选择日期'}}</picker>
													</view>
												</view>
												<checkbox-group  @change="itemChange" data-field="period_end_cq">
													<label class="form-tips">
														<checkbox value="1" style="transform: scale(0.7);" :checked="form.period_end_cq == 1 ? true : false"></checkbox>长期
													</label>
												</checkbox-group>
											</view>
										</view>
									</view>
								</view>
								<!-- END 登记证书 -->
								
								<!-- 经营者/法人身份证件 -->
								<view class="form-item">
									<view class="form-item-center">
										<view class="form-label"><text class="required">*</text>证件持有人类型</view>
										<view class="form-value">
											<radio-group class="flex-sb" style="width: 100%;" name="id_holder_type" @change="itemChange" data-field="id_holder_type">
												<label>
													<radio value="LEGAL" style="transform: scale(0.8);" :checked="form.id_holder_type=='LEGAL'?true:false"></radio>经营者/法人
												</label>
												<label>
													<radio value="SUPER" style="transform: scale(0.8);" :checked="form.id_holder_type=='SUPER'?true:false"></radio>经办人
												</label>
											</radio-group>
										</view>
									</view>
								</view>
								<view class="form-item" v-if="form.id_holder_type == 'SUPER'">
									<view class="form-item-center">
										<view class="form-label"><text class="required">*</text>法定代表人说明函</view>
										<view class="form-value form-upload">
											<view class="imgitem" v-if="form.authorize_letter_copy">
												<image :src="form.authorize_letter_copy" @tap="previewImage" :data-url="form.authorize_letter_copy" mode="widthFix"></image>
											</view>
											<view class="uploadrow">
												<view class="uploadbtn" @tap="uploadimg" data-field="authorize_letter_copy" :style="'border:1px solid rgba('+t('color1rgb')+',0.3);color:'+t('color1')"> 点击上传</view>
												<view class="uploadtip">图片需小于2MB</view>
											</view>
										</view>
									</view>
								</view>
								<view v-if="form.id_holder_type == 'LEGAL'">
									<view class="form-item">
										<view class="form-item-center">
											<view class="form-label"><text class="required">*</text>证件类型</view>
											<view class="form-value ">
												<picker :range="contactIdDocType" @change="itemChange" data-field="id_doc_type">
													<view class="form-select">
														<view class="select-txt">{{form['id_doc_type'] ? form['id_doc_type'] : '请选择' }}</view>
														<image class="down" :src="pre_url+'/static/img/arrowdown.png'"></image>
													</view>
												</picker>
											</view>
										</view>
									</view>
									<!-- 身份证信息 当证件持有人类型为经营者/法人且证件类型为“身份证”时填写 -->
									<view v-if="form.id_doc_type == '中国大陆居民-身份证'">
										<view class="form-item">
											<view class="form-item-center">
												<view class="form-label"><text class="required">*</text>身份证人像面照片</view>
												<view class="form-value form-upload">
													<view class="imgitem" v-if="form.id_card_copy">
														<image :src="form.id_card_copy" @tap="previewImage" :data-url="form.id_card_copy" mode="widthFix"></image>
													</view>
													<view class="uploadrow">
														<view class="uploadbtn" @tap="uploadimg" data-field="id_card_copy" :style="'border:1px solid rgba('+t('color1rgb')+',0.3);color:'+t('color1')">点击上传</view>
														<view class="uploadtip">图片需小于2MB</view>
													</view>
												</view>
											</view>
										</view>
										<view class="form-item">
											<view class="form-item-center">
												<view class="form-label"><text class="required">*</text>身份证国徽面照片</view>
												<view class="form-value form-upload">
													<view class="imgitem" v-if="form.id_card_national">
														<image :src="form.id_card_national" @tap="previewImage" :data-url="form.id_card_national" mode="widthFix"></image>
													</view>
													<view class="uploadrow">
														<view class="uploadbtn" @tap="uploadimg" data-field="id_card_national" :style="'border:1px solid rgba('+t('color1rgb')+',0.3);color:'+t('color1')"> 点击上传</view>
														<view class="uploadtip">图片需小于2MB</view>
													</view>
												</view>
											</view>
										</view>
										<view class="form-item">
											<view class="form-item-center">
												<view class="form-label"><text class="required">*</text>身份证姓名</view>
												<view class="form-value">
													<input type="text" name="id_card_name" v-model="form.id_card_name" placeholder="请填写身份证姓名" placeholder-class="placeholder">
												</view>
											</view>
										</view>
										<view class="form-item">
											<view class="form-item-center">
												<view class="form-label"><text class="required">*</text>身份证号码</view>
												<view class="form-value">
													<input type="text" name="id_card_number" v-model="form.id_card_number" placeholder="请填写身份证号码" placeholder-class="placeholder">
												</view>
											</view>
										</view>
										<view class="form-item" v-if="subjectIndex == 0">
											<!-- 主体类型为企业时，需要填写-->
											<view class="form-item-center">
												<view class="form-label"><text class="required">*</text>身份证居住地址</view> 
												<view class="form-value">
													<input type="text" name="id_card_address" v-model="form.id_card_address" placeholder="请填写身份证居住地址" placeholder-class="placeholder">
												</view>
											</view>
										</view>
										<view class="form-item">
											<view class="form-item-center">
												<view class="form-label"><text class="required">*</text>身份证有效期</view>
												<view class="form-value flex-sb">
													<view class="flex1">
														<view :class="form.card_period_begin?'':'placeholder'" class="picker-range">
															<picker mode="date" name="card_period_begin" @change="itemChange" data-field="card_period_begin">
																{{form.card_period_begin?form.card_period_begin:'请选择日期'}}
															</picker>
														</view>
														<view style="margin-top: 6rpx;" :class="form.card_period_end?'':'placeholder'" class="picker-range">
															<picker mode="date" name="card_period_end" @change="itemChange" data-field="card_period_end">
																{{form.card_period_end?form.card_period_end:'请选择日期'}}
															</picker>
														</view>
													</view>
													<checkbox-group  @change="itemChange" data-field="card_period_end_cq">
														<label class="form-tips">
															<checkbox value="1" style="transform: scale(0.7);" :checked="form.card_period_end_cq == 1 ? true : false"></checkbox>长期
														</label>
													</checkbox-group>
												</view>
											</view>
										</view>
									</view>
									<!-- END 身份证信息 -->
									
									<!-- 其他类型证件信息 -->
									<view v-if="form.id_doc_type != '中国大陆居民-身份证'">
										<!-- 当证件持有人类型为经营者/法人且证件类型不为“身份证”时填写。其他情况，无需上传 -->
										<view class="form-item">
											<view class="form-item-center">
												<view class="form-label"><text class="required">*</text>证件正面照片</view>
												<view class="form-value form-upload">
													<view class="imgitem" v-if="form.id_doc_copy">
														<image :src="form.id_doc_copy" @tap="previewImage" :data-url="form.id_doc_copy" mode="widthFix"></image>
													</view>
													<view class="uploadrow">
														<view class="uploadbtn" @tap="uploadimg" data-field="id_doc_copy" :style="'border:1px solid rgba('+t('color1rgb')+',0.3);color:'+t('color1')">点击上传</view>
														<view class="uploadtip">图片需小于2MB</view>
													</view>
												</view>
											</view>
										</view>
										<view class="form-item">
											<view class="form-item-center">
												<view class="form-label"><text class="required">*</text>证件反面照片</view>
												<view class="form-value form-upload">
													<view class="imgitem" v-if="form.id_doc_copy_back">
														<image :src="form.id_doc_copy_back" @tap="previewImage" :data-url="form.id_doc_copy_back" mode="widthFix"></image>
													</view>
													<view class="uploadrow">
														<view class="uploadbtn" @tap="uploadimg" data-field="id_doc_copy_back" :style="'border:1px solid rgba('+t('color1rgb')+',0.3);color:'+t('color1')"> 点击上传</view>
														<view class="uploadtip">图片需小于2MB</view>
													</view>
												</view>
											</view>
										</view>
										<view class="form-item">
											<view class="form-item-center">
												<view class="form-label"><text class="required">*</text>证件姓名</view>
												<view class="form-value">
													<input type="text" name="id_doc_name" v-model="form.id_doc_name" placeholder="请填写证件姓名" placeholder-class="placeholder">
												</view>
											</view>
										</view>
										<view class="form-item">
											<view class="form-item-center">
												<view class="form-label"><text class="required">*</text>证件号码</view>
												<view class="form-value">
													<input type="text" name="id_doc_number" v-model="form.id_doc_number" placeholder="请填写证件号码" placeholder-class="placeholder">
												</view>
											</view>
										</view>
										<view class="form-item" v-if="subjectIndex == 0">
											<!-- 主体类型为企业时，需要填写-->
											<view class="form-item-center">
												<view class="form-label"><text class="required">*</text>证件居住地址</view> 
												<view class="form-value">
													<input type="text" name="id_doc_address" v-model="form.id_doc_address" placeholder="请填写证件居住地址" placeholder-class="placeholder">
												</view>
											</view>
										</view>
										<view class="form-item">
											<view class="form-item-center">
												<view class="form-label"><text class="required">*</text>证件有效期</view>
												<view class="form-value flex-sb">
													<view class="flex1">
														<view :class="form.doc_period_begin?'':'placeholder'" class="picker-range">
															<picker mode="date" name="doc_period_begin" @change="itemChange" data-field="doc_period_begin">
																{{form.doc_period_begin?form.doc_period_begin:'请选择日期'}}
															</picker>
														</view>
														<view style="margin-top: 6rpx;"
															:class="form.doc_period_end?'':'placeholder'" class="picker-range">
															<picker mode="date" name="doc_period_end" @change="itemChange" data-field="doc_period_end">
																{{form.doc_period_end?form.doc_period_end:'请选择日期'}}
															</picker>
														</view>
													</view>
													<checkbox-group  @change="itemChange" data-field="doc_period_end_cq">
														<label class="form-tips">
															<checkbox value="1" style="transform: scale(0.7);" :checked="form.doc_period_end_cq == 1 ? true : false"></checkbox>长期
														</label>
													</checkbox-group>
												</view>
											</view>
										</view>
									</view>
									<!-- END 其他类型证件信息 -->
								</view>
							</view>
							<!-- END 经营者/法人身份证件 -->
						</block>
						<!-- END 主体资料 -->
						
						<!-- 经营资料 -->
						<block v-if="step == 3">
							<view class="box">
								<view class="form-title">经营资料</view>
								<view class="form-item">
									<view class="form-item-center">
										<view class="form-label"><text class="required">*</text>商户简称</view>
										<view class="form-value">
											<input type="text" name="merchant_shortname" v-model="form.merchant_shortname" placeholder="请填写商户简称" placeholder-class="placeholder">
										</view>
									</view>
								</view>
								<view class="form-item">
									<view class="form-item-center">
										<view class="form-label"><text class="required">*</text>客服电话</view>
										<view class="form-value">
											<input type="text" name="service_phone" v-model="form.service_phone" placeholder="请填写客服电话" placeholder-class="placeholder">
										</view>
									</view>
								</view>
								<view class="form-item">
									<view class="form-item-center">
										<view class="form-label"><text class="required">*</text>经营场景类型</view>
										<view class="form-value">
											<checkbox-group class="checkbox-group"  @change="itemChange" data-field="sales_scenes_type">
												<label v-for="(sales_val,sales_index) in salesScenesType" :key="sales_index" class="flex-y-center">
													<checkbox style="transform: scale(0.7);" :value="sales_val" :checked="form.sales_scenes_type && inArray(sales_val,form.sales_scenes_type) ? true : false"/>{{sales_val}}
												</label>
											</checkbox-group>
										</view>
									</view>
								</view>
								<view class="form-item" v-if="formType == 0 || formType == 2">
									<view class="form-item-center">
										<view class="form-label"><text class="required">*</text>商户使用服务</view>
										<view class="form-value">
											<checkbox-group class="checkbox-group"  @change="itemChange" data-field="service">
												<label v-for="(service_val,service_index) in service" :key="service_index" class="flex-y-center">
													<checkbox style="transform: scale(0.7);" :value="service_val" :checked="form.service && form.service.includes(service_val) ? true : false" />{{service_val}}
												</label>
											</checkbox-group>
										</view>
									</view>
								</view>
								<view class="form-item">
									<view class="form-item-center">
										<view class="form-label"><text class="required">*</text>商户站点信息</view>
										<view class="form-value">
											<picker :range="siteType" :value="form.site_type ? subjectIndex : ''" @change="itemChange" data-field="site_type">
												<view class="form-select">
													<view class="select-txt">{{ form.site_type ? form.site_type : '请选择' }}</view>
													<image class="down" :src="pre_url+'/static/img/arrowdown.png'"></image>
												</view>
											</picker>
										</view>
									</view>
								</view>
								<!-- 线下场所场景 -->
								<view v-if="form.sales_scenes_type && form.sales_scenes_type.includes('线下场所')">
									<!-- 当"经营场景类型"选择"SALES_SCENES_STORE"，该场景资料必填 -->
									<view class="form-item">
										<view class="form-item-center">
											<view class="form-label"><text class="required">*</text>线下场所名称</view>
											<view class="form-value">
												<input type="text" name="biz_store_name" v-model="form.biz_store_name" placeholder="请填写线下场所名称" placeholder-class="placeholder">
											</view>
										</view>
									</view>
									<view class="form-item">
										<view class="form-item-center">
											<view class="form-label"><text class="required">*</text>线下场所省市编码</view>
											<view class="form-value">
												<input type="text" name="biz_address_code" v-model="form.biz_address_code" placeholder="请填写线下场所省市编码" placeholder-class="placeholder">
											</view>
										</view>
									</view>
									<view class="form-item">
										<view class="form-item-center">
											<view class="form-label"><text class="required">*</text>线下场所地址</view>
											<view class="form-value">
												<input type="text" name="biz_store_address" v-model="form.biz_store_address" placeholder="请填写线下场所地址" placeholder-class="placeholder">
											</view>
										</view>
									</view>
									<view class="form-item">
										<view class="form-label">线下场所门头照片</view>
										<view class="form-value form-upload">
											<view class="imgbox">
												<block v-if="form.store_entrance_pic && form.store_entrance_pic.length>0" v-for="(pic,pindex) in form.store_entrance_pic" :key="pindex">
													<view class="imgitem-pics">
														<image :src="pic" @tap="previewImage" :data-url="pic" mode="widthFix"></image>
													</view>
												</block>
												<view class="imgitem-pics">
													<view class="uploadbtn1" @tap="uploadimg" data-field="store_entrance_pic" data-pernum="3"
														:style="{background:'url('+pre_url+'/static/img/shaitu_icon.png) no-repeat 44rpx',backgroundSize:'60rpx 60rpx',backgroundColor:'#F3F3F3'}">
													</view>
												</view>
											</view>
										</view>
									</view>
									<view class="form-item">
										<view class="form-label">线下场所内部照片</view>
										<view class="form-value form-upload">
											<view class="imgbox">
												<block v-if="form.indoor_pic && form.indoor_pic.length>0" v-for="(pic,pindex) in form.indoor_pic" :key="pindex">
													<view class="imgitem-pics">
														<image :src="pic" @tap="previewImage" :data-url="pic" mode="widthFix"></image>
													</view>
												</block>
												<view class="imgitem-pics">
													<view class="uploadbtn1" @tap="uploadimg" data-field="indoor_pic" data-pernum="3"
														:style="{background:'url('+pre_url+'/static/img/shaitu_icon.png) no-repeat 44rpx',backgroundSize:'60rpx 60rpx',backgroundColor:'#F3F3F3'}">
													</view>
												</view>
											</view>
										</view>
									</view>
									
									<view class="form-item">
										<view class="form-item-center">
											<view class="form-label">线下场所对应的商家AppID</view>
											<view class="form-value">
												<input type="text" name="biz_sub_appid" v-model="form.biz_sub_appid" placeholder="请填写线下场所对应的商家AppID" placeholder-class="placeholder">
											</view>
										</view>
									</view>
								</view>
								<!-- END 线下场所场景 -->
								
								<!-- 公众号场景 -->
								<view v-if="form.sales_scenes_type && form.sales_scenes_type.includes('公众号')">
									<!-- 当"经营场景类型"选择"SALES_SCENES_MP"，该场景资料必填 -->
									<view class="form-item">
										<view class="form-item-center">
											<view class="form-label">服务商公众号AppID</view>
											<view class="form-value">
												<input type="text" name="mp_appid" v-model="form.mp_appid" placeholder="请填写服务商公众号AppID" placeholder-class="placeholder">
											</view>
										</view>
										<view class="form-tips-block">服务商公众号AppID与商家公众号AppID，二选一必填</view>
									</view>
									<view class="form-item">
										<view class="form-item-center">
											<view class="form-label">商家公众号AppID</view>
											<view class="form-value">
												<input type="text" name="mp_sub_appid" v-model="form.mp_sub_appid" placeholder="请填写商家公众号AppID" placeholder-class="placeholder">
											</view>
										</view>
										<view class="form-tips-block">服务商公众号AppID与商家公众号AppID，二选一必填</view>
									</view>
									<view class="form-item">
										<view class="form-label"><text class="required">*</text>公众号页面截图</view>
										<view class="form-value form-upload">
											<view class="imgbox">
												<block v-if="form.mp_pics && form.mp_pics.length>0" v-for="(pic,pindex) in form.mp_pics" :key="pindex">
													<view class="imgitem-pics">
														<image :src="pic" @tap="previewImage" :data-url="pic" mode="widthFix"></image>
													</view>
												</block>
												<view class="imgitem-pics">
													<view class="uploadbtn1" @tap="uploadimg" data-field="mp_pics" data-pernum="3"
														:style="{background:'url('+pre_url+'/static/img/shaitu_icon.png) no-repeat 44rpx',backgroundSize:'60rpx 60rpx',backgroundColor:'#F3F3F3'}">
													</view>
												</view>
											</view>
										</view>
									</view>
								</view>
								<!-- END 公众号场景 -->
								
								<!-- 小程序场景 -->
								<view v-if="showWx || (form.sales_scenes_type && form.sales_scenes_type.includes('小程序'))">
									<!-- 当"经营场景类型"选择"SALES_SCENES_MINI_PROGRAM"，该场景资料必填 -->
									<view class="form-item">
										<view class="form-item-center">
											<view class="form-label">服务商小程序AppID</view>
											<view class="form-value">
												<input type="text" name="mini_program_appid" v-model="form.mini_program_appid" placeholder="请填写服务商小程序AppID" placeholder-class="placeholder">
											</view>
										</view>
										<view class="form-tips-block">服务商小程序AppID与商家小程序AppID，二选一必填</view>
									</view>
									<view class="form-item">
										<view class="form-item-center">
											<view class="form-label">商家小程序AppID</view>
											<view class="form-value">
												<input type="text" name="mini_program_sub_appid" v-model="form.mini_program_sub_appid" placeholder="请填写商家公众号AppID" placeholder-class="placeholder">
											</view>
										</view>
										<view class="form-tips-block">
											<view>请填写已认证的小程序AppID</view>
											<view>服务商小程序AppID与商家小程序AppID，二选一必填</view>
										</view>
									</view>
									<view class="form-item">
										<view class="form-item-center">
											<view class="form-label"><text class="required">*</text>小程序名称</view>
											<view class="form-value">
												<input type="text" name="site_name" v-model="form.site_name" placeholder="请填写小程序名称" placeholder-class="placeholder">
											</view>
										</view>
									</view>
								</view>
								<!-- END 小程序场景 -->
								
								<!-- 互联网网站场景 -->
								<view v-if="showH5 || (form.sales_scenes_type && form.sales_scenes_type.includes('互联网网站'))">
									<!-- 当"经营场景类型"选择"SALES_SCENES_WEB"，该场景资料必填-->
									<view class="form-item">
										<view class="form-item-center">
											<view class="form-label"><text class="required">*</text>互联网网站域名</view>
											<view class="form-value">
												<input type="text" name="domain" v-model="form.domain" placeholder="请填写互联网网站域名" placeholder-class="placeholder">
											</view>
										</view>
									</view>
								</view>
								<!-- END 互联网网站场景 -->
								
								<!-- App场景 -->
								<view v-if="form.sales_scenes_type && form.sales_scenes_type.includes('App')">
									<!-- 当"经营场景类型"选择"SALES_SCENES_WEB"，该场景资料必填-->
									<view class="form-item">
										<view class="form-item-center">
											<view class="form-label">服务商应用AppID</view>
											<view class="form-value">
												<input type="text" name="app_appid" v-model="form.app_appid" placeholder="请填写服务商应用AppID" placeholder-class="placeholder">
											</view>
										</view>
									</view>
									<view class="form-item">
										<view class="form-item-center">
											<view class="form-label">商家应用AppID</view>
											<view class="form-value">
												<input type="text" name="app_sub_appid" v-model="form.app_sub_appid" placeholder="请填写商家应用AppID" placeholder-class="placeholder">
											</view>
										</view>
									</view>
								</view>
								<!-- END App场景 -->
								
								<!-- 企业微信场景 -->
								<view v-if="form.sales_scenes_type && form.sales_scenes_type.includes('企业微信')">
									<!-- 当"经营场景类型"选择"SALES_SCENES_WEWORK"，该场景资料必填-->
									<view class="form-item">
										<view class="form-item-center">
											<view class="form-label"><text class="required">*</text>服务商企业微信CorpID</view>
											<view class="form-value">
												<input type="text" name="corp_id" v-model="form.corp_id" placeholder="请填写服务商企业微信CorpID" placeholder-class="placeholder">
											</view>
										</view>
									</view>
									<view class="form-item">
										<view class="form-label"><text class="required">*</text>企业微信页面截图</view>
										<view class="form-value form-upload">
											<view class="imgbox">
												<block v-if="form.wework_pics && form.wework_pics.length>0" v-for="(pic,pindex) in form.wework_pics" :key="pindex">
													<view class="imgitem-pics">
														<image :src="pic" @tap="previewImage" :data-url="pic" mode="widthFix"></image>
													</view>
												</block>
												<view class="imgitem-pics">
													<view class="uploadbtn1" @tap="uploadimg" data-field="wework_pics" data-pernum="3"
														:style="{background:'url('+pre_url+'/static/img/shaitu_icon.png) no-repeat 44rpx',backgroundSize:'60rpx 60rpx',backgroundColor:'#F3F3F3'}">
													</view>
												</view>
											</view>
										</view>
									</view>
								</view>
								<!-- END 企业微信场景 -->
							</view>
						</block>
						<!-- END 经营资料 -->
						
						<block v-if="step == 4">
							<!-- 结算规则 -->	
							<view class="box">
								<view class="form-title">结算规则</view>
								<view class="form-item flex-sb" v-if="formType < 2">
									<view class="form-label"><text class="required">*</text>所属行业</view>
									<view class="form-value" @tap="showModal('wx')">
										<view class="form-select">
											<view class="select-txt">{{form.qualification_type?form.qualification_type:'请选择'}}</view>
											<image class="down" :src="pre_url+'/static/img/arrowdown.png'"></image>
										</view>
									</view>
								</view>
								<view class="form-item flex-sb" v-if="formType == 0 || formType == 2">
									<view class="form-label"><text class="required">*</text>商户类别</view>
									<view class="form-value" @tap="showModal('ali')">
										<view class="form-select">
											<view class="select-txt">{{form.mcc_name?form.mcc_name:'请选择'}}</view>
											<image class="down" :src="pre_url+'/static/img/arrowdown.png'"></image>
										</view>
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
											<view class="imgitem-pics">
												<view class="uploadbtn1" @tap="uploadimg" data-field="qualifications" data-pernum="3"
													:style="{background:'url('+pre_url+'/static/img/shaitu_icon.png) no-repeat 44rpx',backgroundSize:'60rpx 60rpx',backgroundColor:'#F3F3F3'}">
												</view>
											</view>
										</view>
									</view>
								</view>
							</view>
							<!-- END 结算规则 -->
							
							<!-- 结算银行账户 -->
							<view class="box">
								<view class="form-title">结算银行账户</view>
								<view class="form-item">
									<view class="form-item-center">
										<view class="form-label"><text class="required">*</text>账户类型</view>
										<view class="form-value">
											<picker :range="bankAccountType" @change="itemChange" data-field="bank_account_type"> 
												<view class="form-select">
													<view class="select-txt">{{ bankAccountType[form['bank_account_type']] ? bankAccountType[form['bank_account_type']] : '请选择' }}</view>
													<image class="down" :src="pre_url+'/static/img/arrowdown.png'"></image>
												</view>
											</picker>
										</view>
									</view>
								</view>
								<view class="form-item">
									<view class="form-item-center">
										<view class="form-label"><text class="required">*</text>开户名称</view>
										<view class="form-value">
											<input type="text" name="account_name" v-model="form.account_name" placeholder="请填写开户名称" placeholder-class="placeholder">
										</view>
									</view>
								</view>
								<view class="form-item">
									<view class="form-item-center">
										<view class="form-label"><text class="required">*</text>开户银行</view>
										<view class="form-value">
											<picker :range="bankList" @change="itemChange" data-field="account_bank">
												<view class="form-select">
													<view class="select-txt">{{ bankList[form['account_bank']] ? bankList[form['account_bank']] : '请选择' }}</view>
														<image class="down" :src="pre_url+'/static/img/arrowdown.png'"></image>
													</view>
											</picker>
										</view>
									</view>
								</view>
								<block v-if="formType == 0 || formType == 2">
									<view v-if="subjectIndex != 5 || subjectIndex != 6">
									<view class="form-item">
										<view class="form-item-center">
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
									</view>
									<view class="form-item">
										<view class="form-item-center">
											<view class="form-label"><text class="required">*</text>卡类型</view>
											<view class="form-value">
												<picker :range="accountTypeList" @change="itemChange" data-field="account_type"> 
													<view class="form-select">
														<view class="select-txt">{{ accountTypeList[form['account_type']] ? accountTypeList[form['account_type']] : '请选择' }}</view>
															<image class="down" :src="pre_url+'/static/img/arrowdown.png'"></image>
														</view>
												</picker>
											</view>
										</view>
									</view>
									<view class="form-item">
										<view class="form-item-center">
											<view class="form-label"><text class="required">*</text>卡户名</view>
											<view class="form-value">
												<input type="text" name="account_holder_name" v-model="form.account_holder_name" placeholder="请填写银行账号" placeholder-class="placeholder">
											</view>
										</view>
									</view>
								</view>
								</block>
								<view class="form-item" v-if="formType == 0 || formType == 2">
									<view class="form-item-center">
										<view class="form-label"><text class="required">*</text>开户银行省份编码</view>
										<view class="form-value">
											<input type="text" name="bank_province_code" v-model="form.bank_province_code" placeholder="请填写开户银行省份编码" placeholder-class="placeholder">
										</view>
									</view>
								</view>
								<view class="form-item" v-if="formType == 0 || formType == 2">
									<view class="form-item-center">
										<view class="form-label"><text class="required">*</text>开户银行城市编码</view>
										<view class="form-value">
											<input type="text" name="bank_city_code" v-model="form.bank_city_code" placeholder="请填写开户银行城市编码" placeholder-class="placeholder">
										</view>
									</view>
								</view>
								<view class="form-item" v-if="formType == 0 || formType == 2">
									<view class="form-item-center">
										<view class="form-label"><text class="required">*</text>开户银行区县编码</view>
										<view class="form-value">
											<input type="text" name="bank_district_code" v-model="form.bank_district_code" placeholder="请填写开户银行区县编码" placeholder-class="placeholder">
										</view>
									</view>
								</view>
								<view class="form-item" v-if="form.account_bank == 0">
									<view class="form-item-center">
										<view class="form-label"><text class="required">*</text>开户银行联行号</view>
										<view class="form-value">
											<input type="text" name="bank_branch_id" v-model="form.bank_branch_id" placeholder="请填写开户银行联行号" placeholder-class="placeholder">
										</view>
									</view>
								</view>
								<view class="form-item">
									<view class="form-item-center">
										<view class="form-label"><text class="required">*</text>开户银行全称（含支行）</view>
										<view class="form-value">
											<input type="text" name="bank_name" v-model="form.bank_name" placeholder="请填写开户银行全称" placeholder-class="placeholder">
										</view>
									</view>
								</view>
								<view class="form-item" v-if="formType == 0 || formType == 2">
									<view class="form-item-center">
										<view class="form-label"><text class="required">*</text>开户行简称缩写</view>
										<view class="form-value">
											<input type="text" name="account_inst_id" v-model="form.account_inst_id" placeholder="请填写开户行简称缩写" placeholder-class="placeholder">
										</view>
									</view>
								</view>
								<view class="form-item">
									<view class="form-item-center">
										<view class="form-label"><text class="required">*</text>银行账号</view>
										<view class="form-value">
											<input type="text" name="account_number" v-model="form.account_number" placeholder="请填写银行账号" placeholder-class="placeholder">
										</view>
									</view>
								</view>
								<!-- 个人结算到支付宝账户 -->
								<view  v-if="subjectIndex == 5 || subjectIndex == 6">
									<view class="form-item">
										<view class="form-item-center">
											<view class="form-label"><text class="required">*</text>支付宝账号登录号</view>
											<view class="form-value">
												<input type="text" name="default_settle_target" v-model="form.default_settle_target" placeholder="请填写支付宝账号登录号" placeholder-class="placeholder">
											</view>
										</view>
									</view>
									<view class="form-item">
										<view class="form-item-center">
											<view class="form-label"><text class="required">*</text>结算支付宝账号</view>
											<view class="form-value">
												<input type="text" name="alipay_logon_id" v-model="form.alipay_logon_id" placeholder="请填写结算支付宝账号" placeholder-class="placeholder">
											</view>
										</view>
									</view>
								</view>
							</view>
							<!-- END 结算银行账户 -->
						</block>
						
						<view style="display: none;">{{txt}}</view>
						<view class="form-opt">
							<button class="btn" v-if="step==1" :style="{background:t('color1')}" @tap="submit">{{step==1?'下一步':'提交审核'}}</button>
							<block v-else-if="step==2 || step==3">
								<button class="btn btn1" @tap="prestep">上一步</button>
								<button class="btn btn2" :style="{background:t('color1')}" @tap="submit">下一步</button>
							</block>
							<block v-else>
								<button class="btn btn1" @tap="prestep">上一步</button>
								<button class="btn btn2" :style="{background:t('color1')}" @tap="submit">提交审核</button>
							</block>
						</view>
					</form>
				</view>
				<!-- 所属行业选择start -->
				<view class="popup__container modal-s" v-if="isShowModal" style="z-index: 999;">
					<view class="popup__overlay" @tap.stop="hideModal"></view>
					<view class="popup__modal">
						<view class="popup__title">
							<view class="popup__title-text">请选择所属行业</text></view>
							<image :src="pre_url+'/static/img/close.png'" class="popup__close" style="width:36rpx;height:36rpx" @tap.stop="hideModal" />
						</view>
						<view class="popup__content select_content">
							<view class="modal-search">
								<input type="text" placeholder="输入关键字检索" placeholder-class="placeholder"
									@confirm="modalSearch" v-model="modal_keyword" />
								<image :src="pre_url+'/static/img/search.png'" @tap="modalSearch"></image>
							</view>
							<view class="modal-body">
								<block v-if="modal_type == 'wx'" v-for="(item,index) in qualification" :key="index" >
									<view class="select-title">{{index}}</view>
									<block v-for="(v,k) in item" :key="k">
										<view v-for="(vv,kk) in v.qualification" :key="kk" class="select-item" :data-id="v.settlement_id" :data-name="vv.name" :data-required="vv.is_required" @tap="chooseQualification"
											:style="form.qualification_type == vv.name ?('background:rgba('+t('color1rgb')+',0.16);color:'+t('color1')):''">
											{{vv.name}}
										</view>
									</block>
								</block>
								<block v-if="modal_type=='ali'" v-for="(item,index) in aliqualification" :key="index">
									<view class="select-item" :data-id="item.code" :data-name="item.name" :data-required="item.is_required" @tap="chooseQualification" data-type="ali"
										:style="form.mcc == item.code?('background:rgba('+t('color1rgb')+',0.16);color:'+t('color1')):''">
										{{item.name}}
									</view>
								</block>
							</view>
						</view>
					</view>
				</view>
				<!-- 所属行业选择end -->
				
			</block>
		</block>
		<block v-else-if="apply == 1">
			
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
				subject_type: '',
				txt: 1,
				arealist: [],
				area: [],
				citylist:[],
				city:[],
				canSubmit: true,
				modal_keyword: '',
				modal_type:'',
				isShowModal: false,
				step:1,
				apply:-1,
				form: {},
				contactIdDocType:{},
				subjectType:['企业','事业单位','民办非企业组织','社会团体','党政及国家机关','个人商户','个体工商户'], //支付宝格式
				subjectIndex:-1,
				certType:{},
				certTypeIndex:-1,
				salesScenesType:{},
				qualification:{},
				aliqualification:{},
				is_required:0, //是否上传特殊资质
				bankAccountType:['对公银行账户','经营者个人银行卡'],
				bankList:{},
				service:{},
				accountTypeList:['借记卡','信用卡'],
				siteType:{},
				showH5:0,
				showWx:0,
				formType:0, //0:全部 1:微信 2:支付宝
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
				app.get('ApiAdminBusinessJinjian/applyInfo', {id:id}, function(res) {
					that.loading = false;
					if (res.status == 1) {
						that.contactIdDocType = Object.values(res.contactIdDocType);
						that.salesScenesType = Object.values(res.salesScenesType);
						that.qualification = res.qualification;
						that.aliqualification = res.aliQualification;
						that.bankList = res.bankList;
						that.service = res.service;
						that.siteType = res.siteType;
						that.formType = res.formType;
						if(res.cartType){
							let certtype = {};
							certtype[1] = [res.cartType[0]]; //事业单位
							certtype[4] = [res.cartType[1]]; //政府机关
							certtype[2] = res.cartType.slice(1); //民办非企业组织
							certtype[3] = res.cartType.slice(1); //社会组织
							that.certType = certtype;
						}
						if(!app.isEmpty(res.detail)){
							that.form = res.detail;
							that.subjectIndex = res.detail.subject_type;
							if(that.form.account_inst_city && that.form.account_inst_province){
								that.city.push(that.form.account_inst_city)
								that.city.push(that.form.account_inst_province)
							}
						}
						that.initAreaList();
						that.loaded();
					} else {
						app.alert(res.msg);
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
						} else {
							that.form[field].push(urls[i]);
						}
						that.txt = Math.random()
					}
				}, pernum);
			},
			itemChange: function(e,list) {
				var that = this;
				var field = e.currentTarget.dataset.field
				var value = e.detail.value;
				if(field == 'cert_type'){ //登记证书类型
					value = that.certType[that.subjectIndex][value]
				}
				if(field == 'period_end_cq' || field == 'card_period_end_cq' || field == 'contact_period_end_cq' || field == 'doc_period_end_cq'){
					if(value.length == 1){
						value = 1;
					}else{
						value = 0;
					}
				}
				if(field == 'id_doc_type'){
					value = that.contactIdDocType[value];
				}
				if(field == 'service'){
					if(value == 'jsapi支付'){
						that.showH5 = 1;
					}
				}
				if(field == 'site_type'){
					if(value == '支付宝小程序'){
						that.showWx = 1;
					}
					that.form[field] = that.siteType[value];
					that.txt = Math.random()
					return;
				}
				that.form[field] = value;
				that.txt = Math.random()
			},
			prestep:function(){
				this.step--;
			},
			subjectChange:function(e){
				let field = e.currentTarget.dataset.field
				this.form[field] = e.detail.value;
				this.subjectIndex = e.detail.value;
			},
			showModal: function(type) {
				this.modal_keyword = ''
				this.modal_type = type;
				this.isShowModal = true
			},
			hideModal: function() {
				this.isShowModal = false
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
			cityChange: function(e) {
				var that = this
				var arr = e.detail.value
				var city = [];
				for (let i in arr) {
					city.push(arr[i].text)
				}
				that.city = city;
			},
			chooseQualification:function(e){
				var that = this;
				var id = e.currentTarget.dataset.id;
				var name = e.currentTarget.dataset.name;
				var is_required = e.currentTarget.dataset.required;
				var type = e.currentTarget.dataset.type;
				if(type == 'ali'){
					that.form.mcc = id;
					that.form.mcc_name = name;
				}else{
					that.form.settlement_id = id
					that.form.qualification_type = name
					that.is_required = is_required
				}
				that.txt = Math.random()
				that.isShowModal = false
			},
			modalSearch:function(){
				var that = this
				app.loading = true
				app.post("ApiAdminBusinessJinjian/CategorySearch", {
					keyword: that.modal_keyword,
					type:that.modal_type,
				}, function(data) {
					app.loading = false;
					if(that.modal_type == 'ali'){
						that.aliqualification = data.data;
					}else{
						that.qualification = data.data;
					}
				});
			},
			submit: function(e) {
				var that = this;
				var form = that.form;
				var formType = that.formType;
				if(that.step == 1){
					if(formType < 2){
						if(!form.contact_type){
							return app.error('请选择超级管理员类型');
						}
						if(form.contact_type == 'SUPER'){
							if(!form.contact_name){
								return app.error('请填写超级管理员姓名');
							}
							if(!form.contact_id_number){
								return app.error('请填写身份证件号码');
							}
							if(!form.contact_id_doc_copy){
								return app.error('请上传身份证件正面照片');
							}
							if(!form.contact_id_doc_copy_back){
								return app.error('请上传身份证件反面照片');
							}
							if(!form.contact_period_begin && (!form.contact_period_end && !form.contact_period_end_cq)){
								return app.error('请选择身份证有效期');
							}
							if(!form.business_authorization_letter){
								return app.error('请上传业务办理授权函');
							}
						}
					}
					if(!form.mobile_phone){
						return app.error('请填写联系手机');
					}
					if(!form.contact_email){
						return app.error('请填写联系人邮箱');
					}
					
					that.step = 2;
				}else if (that.step == 2) {
					if(form.subject_type === undefined || form.subject_type === null) {
						return app.error('请选择主体类型');
					}
					
					if(form.subject_type == 0 || form.subject_type == 5 || form.subject_type == 6){
						if(!form.license_copy){
							return app.error('请上传营业执照照片');
						}
						if(!form.license_number){
							return app.error('请填写注册号/统一社会信用代码');
						}
						if(!form.merchant_name){
							return app.error('请填写商户名称');
						}
						if(!form.legal_person){
							return app.error('请填写个体户经营者/法人姓名');
						}
					}
					if(formType == 0 || formType == 2){
						//支付宝字段
						if(!form.binding_alipay_logon_id){
							return app.error('请填写签约支付宝账户');
						}
					}
					
					if(form.subject_type >= 1 && form.subject_type <= 4){
						if(!form.cert_copy){
							return app.error('请上传登记证书照片');
						}
						if(!form.cert_type){
							return app.error('请选择登记证书类型');
						}
						if(!form.cert_number){
							return app.error('请填写证书号');
						}
						if(!form.merchant_name){
							return app.error('请填写商户名称');
						}
						if(!form.company_address){
							return app.error('请填写注册地址');
						}
						if(!form.legal_person){
							return app.error('请填写法定代表人');
						}
						if(!form.period_begin && (!form.period_end && !form.period_end_cq)){
							return app.error('请选择身份证有效期');
						}
					}
					
					if(!form.id_holder_type){
						return app.error('请选择证件持有人类型');
					}
					if(form.id_holder_type == 'SUPER'){
						if(!form.authorize_letter_copy){
							return app.error('请上传法定代表人说明函');
						}
					}
					if(form.id_holder_type == 'LEGAL'){
						if(!form.id_doc_type){
							return app.error('请选择证件类型');
						}
						if(form.id_doc_type == '中国大陆居民-身份证'){
							if(!form.id_card_copy){
								return app.error('请上传身份证人像面照片');
							}
							if(!form.id_card_national){
								return app.error('请上传身份证国徽面照片');
							}
							if(!form.id_card_name){
								return app.error('请填写身份证姓名');
							}
							if(!form.id_card_number){
								return app.error('请填写身份证号码');
							}
							if(form.subject_type == 0){
								if(!form.id_card_address){
									return app.error('请填写身份证居住地址');
								}
							}
							if(!form.card_period_begin && (!form.card_period_end && !form.card_period_end_cq)){
								return app.error('请选择身份证有效期');
							}
						}
						if(form.id_doc_type != '中国大陆居民-身份证'){
							if(!form.id_doc_copy){
								return app.error('请上传证件正面照片');
							}
							if(!form.id_doc_copy_back){
								return app.error('请上传证件反面照片');
							}
							if(!form.id_doc_name){
								return app.error('请填写证件姓名');
							}
							if(!form.id_doc_number){
								return app.error('请填写证件号码');
							}
							if(form.subject_type == 0){
								if(!form.id_doc_address){
									return app.error('请填写证件居住地址');
								}
							}
							if(!form.doc_period_begin ||  !form.doc_period_end){
								return app.error('请选择证件有效期');
							}
						}
					}
					
					that.step = 3;
				}else if(that.step == 3){
					if(!form.merchant_shortname){
						return app.error('请填写商户简称');
					}
					if(!form.service_phone){
						return app.error('请填写客服电话');
					}
					if(!form.sales_scenes_type){
						return app.error('请选择经营场景类型');
					}
					if(formType == 0 || formType == 2){
						//支付宝字段
						if(!form.service){
							return app.error('请选择商户使用服务');
						}
					}
					if(form.sales_scenes_type && form.sales_scenes_type.includes('线下场所')){
						if(!form.biz_store_name){
							return app.error('请填写线下场所名称');
						}
						if(!form.biz_address_code){
							return app.error('请填写线下场所省市编码');
						}
						if(!form.biz_store_address){
							return app.error('请填写线下场所地址');
						}
					}
					if(form.sales_scenes_type && form.sales_scenes_type.includes('公众号')){
						if(!form.mp_appid && !form.mp_sub_appid){
							return app.error('服务商公众号AppID与商家公众号AppID，二选一必填');
						}
						if(!form.mp_pics){
							return app.error('请上传公众号页面截图');
						}
					}
					if(form.sales_scenes_type && form.sales_scenes_type.includes('小程序')){
						if(!form.mini_program_appid && !form.mini_program_sub_appid){
							return app.error('服务商小程序AppID与商家小程序AppID，二选一必填');
						}
						if(!form.site_name){
							return app.error('请填写小程序名称');
						}
					}
					if(that.showH5 || (form.sales_scenes_type && form.sales_scenes_type.includes('互联网网站'))){
						if(!form.domain){
							return app.error('请填写互联网网站域名');
						}
					}
					if(form.sales_scenes_type && form.sales_scenes_type.includes('企业微信')){
						if(!form.corp_id){
							return app.error('请填写服务商企业微信CorpID');
						}
						if(!form.wework_pics){
							return app.error('请上传企业微信页面截图');
						}
					}
					
					that.step = 4;
				}else if(that.step == 4){
					if(formType < 2){
						//微信字段
						if(!form.qualification_type){
							return app.error('请选择所属行业');
						}
					}
					if(formType == 0 || formType == 2){
						if(!form.mcc_name){
							return app.error('请选择商户类别');
						}
					}
					if(!form.qualifications && that.is_required){
						return app.error('请上传特殊资质图片');
					}
					if(form.bank_account_type === undefined || form.bank_account_type === null){
						return app.error('请选择账户类型');
					}
					if(!form.account_name){
						return app.error('请填写开户名称');
					}
					if(form.account_bank === undefined || form.account_bank === null){
						return app.error('请选择开户银行');
					}
					if(formType == 0 || formType == 2){
						//支付宝字段
						if(that.city.length > 0){
							form['account_inst_province'] = that.city[0]; //开户行所在地-省
							form['account_inst_city'] = that.city[1]; //开户行所在地-市
						}else{
							return app.error('请选择开户银行地区');
						}
						if(form.subject_type == 5 && form.subject_type == 6){
							if(!form.alipay_logon_id){
								return app.error('请填写结算支付宝账号');
							}
						}
						if(form.account_type === undefined || form.account_type === null){
							return app.error('请选择卡类型');
						}
						if(!form.account_holder_name){
							return app.error('请填写卡户名');
						}
						if(!form.bank_province_code){
							return app.error('请填写开户银行省份编码');
						}
					}
					if(!form.bank_city_code){
						return app.error('请填写开户银行城市编码');
					}
					if(formType == 0 || formType == 2){
						//支付宝字段
						if(!form.bank_district_code){
							return app.error('请填写开户银行区县编码');
						}
					}
					if(form.account_bank == 0){
						if(!form.bank_branch_id){
							return app.error('请填写开户银行联行号');
						}
						if(!form.bank_name){
							return app.error('请填写开户银行全称（含支行）');
						}
					}
					if(formType == 0 || formType == 2){
						//支付宝字段
						if(!form.account_inst_id){
							return app.error('请填写开户行简称缩写');
						}
					}
					if(!form.account_number){
						return app.error('请填写银行账号');
					}
					
					that.canSubmit = false;
					form['id'] = that.id;
					app.showLoading('提交中');
					app.post("ApiAdminBusinessJinjian/apply", form, function(data) {
						app.showLoading(false);
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
	page{position:relative;width:100%;height:100%}
	.flex-sb{display:flex;justify-content:space-between;align-items:center}
	.header{height:260rpx;position:absolute;top:0;width:100%;padding-top:70rpx;text-align:center;font-weight:bold;font-size:32rpx}
	.container{position:absolute;width:100%;top:160rpx;border-radius:16rpx;padding-bottom:100rpx}
	.box{background:#FFFFFF;border-radius:24rpx;padding:20rpx;width:92%;margin:0 4% 20rpx 4%}
	.content{line-height:40rpx;font-size:24rpx}
	.placeholder{font-size:24rpx;color:#BBBBBB}
	.form-item{margin-bottom:30rpx}
	.form-title{font-size:32rpx;padding-bottom:20rpx;border-bottom:1rpx solid #f0f0f0;margin-bottom:30rpx}
	.form-label{flex-shrink:0;width:120px;text-align:right;margin-right:20rpx;word-break:break-all}
	.form-value{flex:1;padding:0 10rpx}
	.form-upload{}
	.form-item-center{display:flex;align-items:center;margin-bottom: 10rpx;}
	.uploadrow{display:flex;align-items:center}
	.uploadbtn{width:150rpx;height:60rpx;line-height:60rpx;text-align:center;border:1rpx solid #F0F0F0;border-radius:6rpx;color:#666}
	.uploadbtn1{width:150rpx;height:150rpx}
	.uploadtip{font-size:20rpx;color:#999;padding-left:10rpx}
	.form-upload .imgitem{width:240rpx;height:240rpx;margin-right:10rpx;background:#f5f5f5;display:flex;align-items:center;justify-content:center;margin-bottom:10rpx}
	.form-upload .imgitem image{max-width:100%;max-height:100%}
	.picker-range{border:1rpx solid #F0F0F0;height:60rpx;line-height:60rpx;border-radius:6rpx;padding:0 10rpx}
	.imgbox{display:flex;align-items:center;flex-wrap:wrap}
	.imgbox .imgitem-pics{width:150rpx;height:150rpx;margin:8rpx;background:#F0F0F0}
	.imgbox .imgitem-pics image{max-width:100%;max-height:100%}
	.form-radio{border:none}
	.form-radio label{margin-right:20rpx}
	.form-value .form-select,.form-value input,.form-value .picker{font-size:24rpx;border-radius:8rpx;height:70rpx;line-height:70rpx;border:1rpx solid #f0f0f0;padding:0 10rpx;flex:1}
	.radio-row{display:flex;flex-direction:column;width:100%}
	.form-label .required{color:#ff2400}
	.form-tips{font-size:24rpx;flex-shrink:0;color:#999}
	.form-tips-block{font-size:24rpx;flex-shrink:0;color:#999;background:#f8f8f8;padding:10rpx;margin-bottom:20rpx}
	.form-opt{position:fixed;bottom:0;width:92%;left:4%;background:#F6F6F6;height:120rpx;display:flex;align-items:center;justify-content:space-between;font-size:24rpx;color:#333;z-index:100}
	.btn{text-align:center;border-radius:50rpx;color:#FFFFFF;flex:1;height:84rpx;line-height:84rpx}
	.btn.btn1{border-radius:50rpx 0 0 50rpx;background:#888;color:#fff}
	.btn.btn2{border-radius:0 50rpx 50rpx 0}
	.form-select{display:flex;align-items:center;justify-content:space-between;overflow:hidden}
	.select-txt{white-space:nowrap;text-overflow:ellipsis;overflow:hidden;max-width:300rpx}
	.down{width:24rpx;height:24rpx;flex-shrink:0}
	.modal-s .popup__overlay{opacity:0.6}
	.modal-s .popup__modal{width:100%;max-height:1100rpx;height:1100rpx}
	.modal-s .popup__content{max-height:1100rpx;padding:0 20rpx;height:1100rpx}
	.modal-s .select-title{font-weight: bold; font-size: 30rpx; padding: 20rpx 0 20rpx 20rpx;position: relative;line-height: 1;}
	.modal-s .select-title::before { content: ''; position: absolute; left: 0; top: 50%; transform: translateY(-50%);  width: 3px;  height: 1em; background-color: #9c9c9c; }
	.modal-s .select-item{background:#F6F6F6;border-radius:6rpx;padding:20rpx;margin-bottom:14rpx}
	.modal-s .modal-search{border:1rpx solid #E5E5E5;border-radius:10rpx;height:70rpx;line-height:70rpx;display:flex;align-items:center;padding:0 20rpx;justify-content:space-between}
	.modal-search image{width:44rpx;height:44rpx}
	.modal-s .modal-body{margin-top:14rpx}
	
</style>
