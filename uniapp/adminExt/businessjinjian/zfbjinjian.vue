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
						<view class="form-label"><text class="required">*</text>支付宝账号</view>
						<view class="form-value">
							<input type="text" name="binding_alipay_logon_id" v-model="form.binding_alipay_logon_id" placeholder="请填写支付宝账号" placeholder-class="placeholder">
						</view>
						<view class="form-tips">资金将收到此支付宝账号内</view>
					</view>
          <view class="form-item">
            <view class="form-label"><text class="required">*</text>选择行业</view>
            <view class="form-value">
              <picker mode="multiSelector" :range="pickerData" range-key="name" @change="onPickerChange" @columnchange="onColumnChange" data-field="mcc">
                <view class="picker" v-if="selectedFirstLevel.name && selectedSecondLevel.name">
                  {{ selectedFirstLevel.name}} - {{selectedSecondLevel.name }}
                </view>
                <view class="picker" v-else>请选择行业</view>
              </picker>
            </view>
          </view>
					<view class="form-item" v-if="is_required">
						<view class="form-label"><text class="required">*</text>特殊资质图片</view>
						<view class="form-value form-upload">
							<view class="imgbox">
                <view class="imgitem-pics" v-if="form.qualifications">
                  <image :src="form.qualifications" @tap="previewImage" :data-url="form.qualifications" mode="widthFix"></image>
                </view>
								<view class="imgitem-pics" v-else>
									<view class="uploadbtn1" @tap="uploadimg" data-field="qualifications"
										:style="{background:'url('+pre_url+'/static/img/shaitu_icon.png) no-repeat 44rpx',backgroundSize:'60rpx 60rpx',backgroundColor:'#F3F3F3'}">
									</view>
								</view>
							</view>
						</view>
            <view class="form-tips wrap-text" v-if="industryDescription">{{ industryDescription }}</view>
					</view>
          <view class="form-item" v-if="is_required">
            <view class="form-label"><text class="required">*</text>行业资质代码</view>
            <view class="form-value">
              <input type="text" name="settlement_id" v-model="form.settlement_id" placeholder="请填写行业资质代码" placeholder-class="placeholder">
            </view>
            <view class="form-tips">按照【特殊资质】描述代码填写</view>
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
						<view class="form-label"><text class="required">*</text>营业执照省份</view>
						<view class="form-value">
							<uni-data-picker class="" :localdata="citylist" popup-title="请选择营业执照省份" @change="cityChange($event,'biz_address_code')" :placeholder="'请选择营业执照省份'">
								<view class="form-select">
									<view class="select-txt">{{city.length>0?city.join('/'):'请选择营业执照省份'}}</view>
									<image class="down" :src="pre_url+'/static/img/arrowdown.png'"></image>
								</view>
							</uni-data-picker>
						</view>
					</view>
					<view class="form-item">
						<view class="form-label"><text class="required">*</text>统一信用代码</view>
						<view class="form-value">
							<input type="text" name="license_number" v-model="form.license_number" placeholder="请填写统一信用代码" placeholder-class="placeholder">
						</view>
						<view class="form-tips">请核对仔细确保无误</view>
					</view>
					<view class="form-item">
						<view class="form-label"><text class="required">*</text>法人姓名</view>
						<view class="form-value">
							<input type="text" name="id_card_name" v-model="form.id_card_name" placeholder="请填写法人姓名" placeholder-class="placeholder">
						</view>
					</view>
					<view class="form-item">
						<view class="form-label"><text class="required">*</text>法人手机号码</view>
						<view class="form-value">
							<input type="text" name="mobile_phone" v-model="form.mobile_phone" placeholder="请填写法人手机号码" placeholder-class="placeholder">
						</view>
					</view>
				</view>	
				<!-- END 主体资料 -->
				
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
				is_required: 0, //是否上传特殊资质
				subject: 0, //主体
        qualification: [], // 行业资质
        pickerIndex: [0, 0], // 当前选择的索引
        selectedFirstLevel: {}, // 当前选择的一级分类
        selectedSecondLevel: {},// 当前选择的二级分类
        industryDescription:'',//特殊资质上传说明
			}
		},
    computed: {
      // 将 qualification 数据转换为 picker 需要的格式
      pickerData() {
        const firstLevel = this.qualification.map((item) => ({
          name: item.name,
          value: item
        })); // 第一列数据
        const secondLevel = this.qualification[this.pickerIndex[0]]
            ? this.qualification[this.pickerIndex[0]].children.map((item) => ({
              name: item.name,
              value: item
            }))
            : []; // 第二列数据
        return [firstLevel, secondLevel];
      },
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
        app.get('ApiAdminBusinessJinjian/aliApplyInfo', {}, function(res) {
          that.loading = false;
          if (res.status == 1) {
            let data = res.data;
            that.qualification = data.qualification;
						if (data.resp == 1) {
						  return app.goto('result?type=2', 'redirect');
						}
            if (!app.isEmpty(data.detail)) {
              that.form = data.detail;
							//回显
							if(that.form.id){
								// 初始化默认选择
								that.initDefaultSelection();
								if(that.form.biz_province && that.form.biz_city && that.form.biz_district){
									that.city = [that.form.biz_province,that.form.biz_city,that.form.biz_district]
								}
							}	
						}
						that.initAreaList();
            that.loaded();
          } else {
            app.alert(res.msg);
          }
          uni.setNavigationBarTitle({
            title: '支付宝进件'
          });
        });
      },
      // 初始化默认选择
      initDefaultSelection() {
        // if (this.qualification.length > 0) {
        //   this.selectedFirstLevel = this.qualification[this.pickerIndex[0]];
        //   this.selectedSecondLevel =
        //       this.qualification[this.pickerIndex[0]].children[this.pickerIndex[1]];
        // }
        let detail = this.form;
        if (this.qualification.length > 0 && detail) {
          // 根据 detail 中的值找到对应的索引
          const firstIndex = this.qualification.findIndex(item => item.code === detail.settlement_id);
          if (firstIndex !== -1) {
            const secondIndex = this.qualification[firstIndex].children.findIndex(item => item.code === detail.qualification_type);
            this.pickerIndex = [firstIndex, secondIndex !== -1 ? secondIndex : 0];
            this.selectedFirstLevel = this.qualification[firstIndex];
            this.selectedSecondLevel = this.qualification[firstIndex].children[secondIndex !== -1 ? secondIndex : 0];
          }
        }
      },
      onPickerChange(e) {
        const [firstIndex, secondIndex] = e.detail.value;
        this.pickerIndex = [firstIndex, secondIndex];
        this.selectedFirstLevel = this.qualification[firstIndex];
        this.selectedSecondLevel = this.qualification[firstIndex].children[secondIndex];

        const selectedIndustry = this.selectedSecondLevel || this.selectedFirstLevel;
        this.form['mcc'] = selectedIndustry.code;
        this.form['qualification_type'] = selectedIndustry.name;
        this.is_required = selectedIndustry.is_required;
        this.industryDescription = selectedIndustry.description;
      },
      // picker 列变化时触发
      onColumnChange(e) {
        const { column, value } = e.detail;
        if (column === 0) {
          // 如果第一列变化，更新第二列数据
          this.pickerIndex = [value, 0];
          this.selectedFirstLevel = this.qualification[value];
          this.selectedSecondLevel = this.qualification[value].children[0];
        } else if (column === 1) {
          // 如果第二列变化，更新第二列选择
          this.pickerIndex[1] = value;
          this.selectedSecondLevel =
              this.qualification[this.pickerIndex[0]].children[value];
        }
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
						if(field == 'license_copy'){
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
				
				that.form[field] = value;
				that.txt = Math.random()
			},
			submit: function(e) {
				var that = this;
				var form = that.form;
				if(!form.binding_alipay_logon_id){
					return app.error('请上传支付宝账号')
				}
				if(!form.mcc){
					return app.error('请选择行业');
				}
        if(that.is_required){
          if(!form.qualifications) {
            return app.error('请上传特殊资质')
          }
          if(!form.settlement_id){
            return app.error('请填写行业资质代码')
          }
				}
        if(!form.license_copy){
          return app.error('请上传营业执照')
        }
				if(!form.license_number){
					return app.error('请填写统一信用代码')
				}
				if(!form.id_card_name){
					return app.error('请填写法人姓名')
				}
        if(!form.mobile_phone){
					return app.error('请填写法人手机号码')
				}
				if(!form.biz_store_name){
					return app.error('请填写门店名称')
				}
				if(!form.store_entrance_pic){
					return app.error('请上传门店门口照片')
				}
				if(!form.indoor_pic){
					return app.error('请上传店内环境照片')
				}
				that.canSubmit = false;
				app.showLoading('提交中');
				app.post("ApiAdminBusinessJinjian/aliApply", form, function(data) {
					app.showLoading(false);
					if (data.status == 1) {
							app.success(data.msg)
							setTimeout(function(){
								return app.goto('result?type=2','redirect');
							},1000)
					} else {
						that.canSubmit = true;
						app.error(data.msg);
					}
				});
				
			},
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
							that.form.subject_type = data.subject_type;
							if(data.province && data.city && data.district){
								that.form.biz_province = data.province;
								that.form.biz_city = data.city;
								that.form.biz_district = data.district;
								that.city = [data.province,data.city,data.district]
							}
						}
						that.txt = Math.random()
					}
				});
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
				that.city = city;
				that.form.biz_province = arr[0]?.text || '';
				that.form.biz_city = arr[1]?.text || '';
				that.form.biz_district = arr[2]?.text || '';
				that.txt = Math.random()
			},
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
	.wrap-text { white-space: pre-line;}
	.layui-imgbox{margin-right:16rpx;margin-bottom:10rpx;font-size:24rpx;position: relative;}
	.layui-imgbox-img{display: block;width:150rpx;height:150rpx;padding:2px;border: #d3d3d3 1px solid;background-color: #f6f6f6;overflow:hidden}
	.layui-imgbox-img>image{max-width:100%;}
	.layui-imgbox-repeat{position: absolute;display: block;width:32rpx;height:32rpx;line-height:28rpx;right: 2px;bottom:2px;color:#999;font-size:30rpx;background:#fff}
</style>