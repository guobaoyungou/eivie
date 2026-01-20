<template>
	<view class="body">
		<block v-if="isload">
			<view class="header"
				:style="{background:'linear-gradient(180deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0) 100%)'}">
				随行付入驻记录
			</view>
			<view class="container">
					<block>
						<view class="box">
							<view class="form-title">主体类型</view>
							<view class="form-item">
								<radio-group class="flex-sb" style="width: 100%;padding: 0 50rpx;" name="subject_type"
									@change="itemChange" data-field="subject_type">
									<label v-if="form.subject_type=='SUBJECT_TYPE_INDIVIDUAL'">
										<radio value="SUBJECT_TYPE_INDIVIDUAL" style="transform: scale(0.8);" :checked="form.subject_type=='SUBJECT_TYPE_INDIVIDUAL'?true:false" :disabled="true"></radio>
										个体户
									</label>
									<label v-if="form.subject_type=='SUBJECT_TYPE_ENTERPRISE'">
										<radio value="SUBJECT_TYPE_ENTERPRISE" style="transform: scale(0.8);" :checked="form.subject_type=='SUBJECT_TYPE_ENTERPRISE'?true:false" :disabled="true"></radio>企业
									</label>
									<label v-if="form.subject_type=='SUBJECT_TYPE_MICRO'">
										<radio value="SUBJECT_TYPE_MICRO" style="transform: scale(0.8);" :checked="form.subject_type=='SUBJECT_TYPE_MICRO'?true:false" :disabled="true"></radio>个人
									</label>
								</radio-group>
							</view>
						</view>
						<view class="box" v-if="form.subject_type!='SUBJECT_TYPE_MICRO'">
							<view class="form-title">营业执照信息</view>
							<view class="form-item">
								<view class="form-label">营业执照照片</view>
								<view class="form-value form-upload">
									<view class="imgitem" v-if="form.business_license_copy">
										<image :src="form.business_license_copy" @tap="previewImage"
											:data-url="form.business_license_copy" mode="widthFix"></image>
									</view>
								</view>
							</view>
							<view class="form-item">
								<view class="form-label">统一社会信用代码</view>
								<view class="form-value">
								{{form.business_license_number}}
								</view>
							</view>
							<view class="form-item">
								<view class="form-label">企业名称</view>
								<view class="form-value">
									{{form.business_merchant_name}}
								</view>
							</view>
							<view class="form-item">
								<view class="form-label">经营者/法人姓名</view>
								<view class="form-value">
									{{form.business_legal_person}}
								</view>
							</view>
						</view>
						<view class="box">
							<view class="form-title">
								经营者/法人身份证
							</view>
							<view class="form-item">
								<view class="form-label">身份证人像面</view>
								<view class="form-value form-upload">
									<view class="imgitem" v-if="form.identity_id_card_copy">
										<image :src="form.identity_id_card_copy" @tap="previewImage"
											:data-url="form.identity_id_card_copy" mode="widthFix"></image>
									</view>
								</view>
							</view>
							<view class="form-item">
								<view class="form-label">身份证国徽面</view>
								<view class="form-value form-upload">
									<view class="imgitem" v-if="form.identity_id_card_national">
										<image :src="form.identity_id_card_national" @tap="previewImage"
											:data-url="form.identity_id_card_national" mode="widthFix"></image>
									</view>
								</view>
							</view>
							<view class="form-item">
								<view class="form-label">身份证姓名</view>
								<view class="form-value">
									{{form.identity_id_card_name}}
								</view>
							</view>
							<view class="form-item">
								<view class="form-label">身份证号码</view>
								<view class="form-value">
									{{form.identity_id_card_number}}
								</view>
							</view>
							<view class="form-item">
								<view class="form-label">身份证有效期</view>
								<view class="form-value">
									<text v-if="form.identity_id_card_valid_time_cq.length>0"></text>
									<text v-else>{{form.identity_id_card_valid_time1}} ~ {{form.identity_id_card_valid_time2}}</text>
								</view>
							</view>
							<view class="form-item">
								<view class="form-label">联系人手机号</view>
								<view class="form-value">
									{{form.contact_mobile}}
								</view>
							</view>
							<view class="form-item">
								<view class="form-label">联系人邮箱</view>
								<view class="form-value">
									{{form.contact_email}}
								</view>
							</view>
						</view>
						<view class="box">
							<view class="form-title">经营资料</view>
							<view class="form-item">
								<view class="form-label">商户简称</view>
								<view class="form-value">
									{{form.merchant_shortname}}
								</view>
							</view>
							<view class="form-item">
								<view class="form-label">客服电话</view>
								<view class="form-value">
									{{form.service_phone}}
								</view>
							</view>
							<view class="form-item">
								<view class="form-label">经营类目</view>
								<view class="form-value">
									{{category_name}}
								</view>
							</view>
							<view class="form-item">
								<view class="form-label">经营地区</view>
								<view class="form-value">
									{{area.join('/')}}
								</view>
							</view>
							<view class="form-item">
								<view class="form-label">经营详细地址</view>
								<view class="form-value">
									{{form.store_street}}
								</view>
							</view>
							<view class="form-item">
								<view class="form-label">门头照</view>
								<view class="form-value form-upload">
									<view class="imgitem" v-if="form.store_entrance_pic">
										<image :src="form.store_entrance_pic" @tap="previewImage"
											:data-url="form.store_entrance_pic" mode="widthFix"></image>
									</view>
								</view>
							</view>
							<view class="form-item">
								<view class="form-label">内景照</view>
								<view class="form-value form-upload">
									<view class="imgitem" v-if="form.indoor_pic">
										<image :src="form.indoor_pic" @tap="previewImage" :data-url="form.indoor_pic"
											mode="widthFix"></image>
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
									</view>
							</view>
								</view>
						</view>

						<view class="box">
							<view class="form-title">结算银行账户</view>
							<view class="form-item">
								<view class="form-label">账户类型</view>
								<view class="form-value">
									<text v-if="form.jiesuan_bank_account_type=='BANK_ACCOUNT_TYPE_CORPORATE'">对公银行账户</text>
									<text v-if="form.jiesuan_bank_account_type=='BANK_ACCOUNT_TYPE_PERSONAL'">个人银行账户</text>
								</view>
							</view>
							<view class="form-item" v-if="form.jiesuan_bank_account_type=='BANK_ACCOUNT_TYPE_CORPORATE'">
								<view class="form-label">开户许可证</view>
								<view class="form-value form-upload">
									<view class="imgitem" v-if="form.account_license_pic">
										<image :src="form.account_license_pic" @tap="previewImage"
											:data-url="form.account_license_pic" mode="widthFix"></image>
									</view>
								</view>
							</view>
							<view class="form-item" v-if="form.jiesuan_bank_account_type=='BANK_ACCOUNT_TYPE_PERSONAL'">
								<view class="form-label">银行卡卡号面</view>
								<view class="form-value form-upload">
									<view class="imgitem" v-if="form.bank_card_pic">
										<image :src="form.bank_card_pic" @tap="previewImage"
											:data-url="form.bank_card_pic" mode="widthFix"></image>
									</view>
								</view>
							</view>
							<view class="form-item">
								<view class="form-label">开户名称/持卡人姓名</view>
								<view class="form-value">
									{{form.jiesuan_account_name}}
								</view>
							</view>
							<view class="form-item">
								<view class="form-label">开户银行</view>
								<view class="form-value">
								{{form.jiesuan_account_bank}}
								</view>
							</view>

							<view class="form-item">
								<view class="form-label">开户银行地区</view>
								<view class="form-value">
									{{city.join('/')}}
								</view>
							</view>
							<view class="form-item">
								<view class="form-label">所属分支行</view>
								<view class="form-value"  @tap="showModal('branch_bank')">
									{{form.jiesuan_bank_name}}
								</view>
							</view>

							<view class="form-item">
								<view class="form-label">银行账号</view>
								<view class="form-value">
									{{form.jiesuan_account_number}}
								</view>
							</view>
							<view class="form-item">
								<view class="form-label">结算费率</view>
								<view class="form-value">
									<text>{{set.feepercent}}</text>
								</view>
							</view>
							<view class="form-item">
								<view class="form-label">结算时间</view>
								<view class="form-value">
									<text>{{set.duration}}</text>
								</view>
							</view>
						</view>
						
						<!-- 审核结果 -->
						<view class="box box-tips">
							<view class="form-title">入驻审核</view>
							<view class="form-item">
								<view class="form-label">状态</view>
								<view class="form-value">
									<view class="st_tag" v-if="form.taskStatusTxt">{{form.taskStatusTxt}}</view>
									<view class="st_tips" v-if="form.suggestion">{{form.suggestion}}</view>
								</view>
							</view>
							<view class="form-item">
								<view class="form-label">修改状态</view>
								<view class="form-value">
									<view class="st_tag" v-if="form.taskStatus_edit_txt">{{form.taskStatus_edit_txt}}</view>
									<view class="st_tips" v-if="form.suggestion_edit">{{form.suggestion_edit}}</view>
								</view>
							</view>
							<view class="form-item">
								<view class="form-label">复核状态</view>
								<view class="form-value">
									<view class="st_tag" v-if="form.suggestion2Txt">{{form.suggestion2Txt}}</view>
									<view class="st_tips" v-if="form.suggestion2">{{form.suggestion2}}</view>
								</view>
							</view>
						</view>
					</block>
					<view class="form-opt">
						<button class="btn btn0" v-if="form.taskStatus=='0' || form.taskStatus=='1'">{{form.taskStatus==0?'审核中':'已提交'}}</button>
						<button class="btn" v-if="form.taskStatus=='-1' || form.taskStatus=='2'" :style="{background:t('color1')}" @tap="goto" :data-url="'apply?id='+form.id">修改</button>
					</view>
			</view>
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
				app.get('ApiSxpay/myapply', {}, function(res) {
					that.loading = false;
					if(res.status==2){
						app.alert(res.msg,function(){
							app.goto('apply');
						})
					}else if (res.status == 1) {
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
						that.loaded();
					} else {
						app.alert(res.msg);
					}
				})
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
	  color: #999;
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
	.btn.btn0{background: #bbb;color: #FFFFFF;}
	.st_tips{  background: #f6f6f6;
    padding: 12rpx;
    font-size: 24rpx;
    border-radius: 10rpx;
    color: #515151;
    line-height: 36rpx;
	}
	
</style>
