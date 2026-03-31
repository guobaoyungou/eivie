<template>
<view class="container">
	<block v-if="isload">
		<view class="qd_head" v-if="style == 1">
			<block v-if="signset.bgpic"><image :src="signset.bgpic" class="qdbg"></image></block>
			<block v-else><image :src="pre_url + '/static/img/sign-bg.png'" class="qdbg"></image></block>
			
			<view class="myscore"><view class="f1">{{userinfo.score}}</view><view class="f2">{{t('积分')}}</view></view>
			<view class="signlog" @tap="goto" data-url="signrecord">签到记录</view>
			<block v-if="signset.ispay==1">			
				<view class="pmtext" @tap.stop="goto" data-url="/pagesB/sign/pmlist">奖励排名</view>
				<view class="canyutext"  @tap.stop="" >参与人数：{{cynum}}</view>
				
				<view class="bonus_text" v-if="signset.isshowbonus==1">
					<view class="title"><text  style="color: #fff;">￥</text><text class="t2">{{bonusprice}}</text></view>
					<view class="title1">奖金池金额</view>
				</view>
			</block>
			
			
			<view class="signbtn" v-if="!hassign">
			 
				<button class="btn"    :style="{background:t('color1')}" @tap="signinNew">立即签到</button>		
				<picker mode="date" style="padding-top: 5px;" :end="endDate"  @change="editorBindPickerChange">
					<button class="btn"   :style="{background:t('color1')}"  mode="date" v-if="is_forget == 1">补签</button>
				 </picker>

			</view>
			<view class="signbtn" :style="!is_forget?'top: 740rpx':'top: 700rpx'"  v-else >
				
				<button class="btn2">今日已签到</button>
				<picker mode="date" style="padding-top: 5px;" :end="endDate"  @change="editorBindPickerChange">
					<button class="btn"   :style="{background:t('color1')}"  mode="date" v-if="is_forget == 1">补签</button>
				 </picker>
				<view class="signtip">已连续签到{{userinfo.signtimeslx}}天</view>
			</view>
		</view>
		<view class="qd_head qd_head2" v-if="style == 2" :style="'background-image:url(' + signset.bgpic + ');'">
			<!-- <view class="myscore"><view class="f1">{{userinfo.score}}</view><view class="f2">{{t('积分')}}</view></view> -->
			<view class="signlog" @tap="goto" data-url="signrecord">签到记录</view>
			
			<view class="signbtn" v-if="!hassign">
				<!-- <button class="btn" v-if="signset.ispay==1"  :style="{background:t('color1')}" @tap="signpay">立即签到</button>
				<button class="btn" v-else :style="{background:t('color1')}" @tap="signin">立即签到</button> -->
				<button class="btn" :style="{background:t('color1')}" @tap="signinNew">立即签到</button>
				<picker mode="date" style="padding-top: 5px;" :end="endDate"  @change="editorBindPickerChange">
					<button class="btn"   :style="{background:t('color1')}"  mode="date" v-if="is_forget == 1">补签</button>
				 </picker>

				<view class="signtip">当前共{{userinfo.score}}{{t('积分')}}</view>
			</view>
			<view class="signbtn" v-else>
				<button class="btn2">今日已签到</button>
				<picker mode="date" style="padding-top: 5px;" :end="endDate"  @change="editorBindPickerChange">
					<button class="btn"   :style="{background:t('color1')}"  mode="date" v-if="is_forget == 1">补签</button>
				 </picker>
				<view class="signtip">已连续签到{{userinfo.signtimeslx}}天，共{{userinfo.score}}{{t('积分')}}</view>
			</view>
			<view class="calendar" :style="!is_forget?'padding-top: 300rpx':'padding-top: 360rpx'">
					<uni-calendar 
					:insert="true"
					:lunar="false" 
					:start-date="start_date"
					:end-date="end_date"
					:selected='selectedDate'
					:showMonth="false"
					:backColor= "t('color1')"
					:fontColor= "'#fff'"
					 />
			</view>
			<!-- [{date: '2021-11-02', info: '已签'}] -->
		</view>
	
	 <view v-if="display && list.length > 0" class="qd_guize" >
			<view class="gztitle"> — 签到排名 — </view>
			<view class="paiming">
				<view v-for="(item, index) in list" :key="index" class="item flex">
					<view class="f1">
							<text class="t1">{{item.nickname}}</text>
					</view>
					<view class="f2">
							<text class="t2">连续签到</text>
							<text class="t1">{{item.signtimeslx}}</text>
							<text class="t2">天</text>
					</view>
				</view>
			</view>
			<view class="btn-a" @tap="getPaiming" v-if="!nomore && list.length >=10" :style="{color:t('color1')}">查看更多</view>
			<nomore v-if="nomore"></nomore>
	 </view>
	 
		<view class="qd_guize">
			<view class="gztitle"> — 签到规则 — </view>
			<view class="guize_txt">
				<parse :content="signset.guize" />
			</view>
		</view>
		
		<view class="modal" v-if="showpay">
			

			<view class="signbox">
				<view class="title">提示信息</view>
				 
				  
				<view class="f1">需支付金额：<text class="t1">￥</text><text class="t2">{{signset.payprice}}</text> </view>
				<view class="btn">
					<button class="btn-cancel" @tap="cancel">取消</button>
					<button class="confirm"  :style="'background:linear-gradient(90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'"  @tap.stop="topay">立即支付</button>
				</view>
			</view>
		</view>

		<!-- 扩展字段采集弹窗 -->
		<view class="modal" v-if="showExtForm">
			<view class="signbox ext-form-box">
				<view class="title">信息采集</view>
				<scroll-view scroll-y style="max-height:60vh;padding:20rpx 30rpx;">
					<!-- 员工号 -->
					<view class="ext-field-item" v-if="extConfig.show_employee_no==1">
						<view class="ext-field-label">员工号<text v-if="extConfig.require_employee_no==1" class="required">*</text></view>
						<input type="text" v-model="extFormData.employee_no" placeholder="请输入员工号" class="ext-field-input" />
					</view>
					<!-- 上传照片 -->
					<view class="ext-field-item" v-if="extConfig.show_photo==1">
						<view class="ext-field-label">上传照片<text v-if="extConfig.require_photo==1" class="required">*</text></view>
						<view class="ext-photo-area">
							<image v-if="extFormData.sign_photo_url" :src="extFormData.sign_photo_url" mode="aspectFill" class="ext-photo-preview" @tap="chooseExtPhoto"></image>
							<view v-else class="ext-photo-btn" @tap="chooseExtPhoto">
								<text class="ext-photo-plus">+</text>
								<text class="ext-photo-txt">选择图片</text>
							</view>
						</view>
					</view>
					<!-- 自定义字段 -->
					<block v-if="extConfig.show_custom_fields==1">
						<view class="ext-field-item" v-for="(cf,ci) in extConfig.custom_fields" :key="cf.id">
							<view class="ext-field-label">{{cf.field_name}}<text v-if="cf.is_required==1" class="required">*</text></view>
							<!-- 文本 -->
							<input v-if="cf.field_type=='text'" type="text" v-model="extFormData.custom_fields['field_'+cf.id]" :placeholder="'请输入'+cf.field_name" class="ext-field-input" />
							<!-- 单选 -->
							<picker v-if="cf.field_type=='select'" :range="cf.field_options" @change="onPickerChange($event,cf.id)">
								<view class="ext-field-input ext-picker">{{extFormData.custom_fields['field_'+cf.id] || '请选择'}}</view>
							</picker>
							<!-- 多选 -->
							<view v-if="cf.field_type=='checkbox'" class="ext-checkbox-group">
								<label v-for="(opt,oi) in cf.field_options" :key="oi" class="ext-checkbox-item">
									<checkbox :value="opt" :checked="isChecked(cf.id,opt)" @tap="toggleCheck(cf.id,opt)" />
									<text>{{opt}}</text>
								</label>
							</view>
							<!-- 图片 -->
							<view v-if="cf.field_type=='image'" class="ext-photo-area">
								<image v-if="extFormData.custom_fields['field_'+cf.id]" :src="extFormData.custom_fields['field_'+cf.id]" mode="aspectFill" class="ext-photo-preview" @tap="chooseCustomImage(cf.id)"></image>
								<view v-else class="ext-photo-btn" @tap="chooseCustomImage(cf.id)">
									<text class="ext-photo-plus">+</text>
									<text class="ext-photo-txt">选择图片</text>
								</view>
							</view>
						</view>
					</block>
				</scroll-view>
				<view class="btn" style="padding:20rpx 30rpx;">
					<button class="btn-cancel" @tap="cancelExtForm">取消</button>
					<button class="confirm" :style="'background:linear-gradient(90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'" @tap="submitExtForm">确认提交</button>
				</view>
			</view>
		</view>
		
		<!-- #ifdef APP-PLUS -->
			<ad-rewarded-video ref="adRewardedVideo" :adpid="signset.adset_adpid" :preload="false" :loadnext="false" :disabled="true" 
			v-slot:default="{loading, error}" @load="onadload" @close="onadclose"	@error="onaderror">
			</ad-rewarded-video>
		<!-- #endif -->
	</block>
	<loading v-if="loading"></loading>
	<dp-tabbar :opt="opt"></dp-tabbar>
	<popmsg ref="popmsg"></popmsg>
</view>
</template>

<script>
let videoAd = null;
var app = getApp();
import uniCalendar from './uni-calendar/uni-calendar.vue';
export default {
	components: {
		uniCalendar
	},
  data() {
    return {
			opt:{},
			loading:false,
            isload: false,
			menuindex:-1,
			pre_url:app.globalData.pre_url,
			hassign:false,
			signset:{},
			userinfo:{},
			list: [],
			display:false,
			style:1,
			start_date:'',
			end_date:'',
			selectedDate:[],//{date: '2022-08-02', info: '已签'}
			nomore:false,
			nodata:false,
			pagenum:1,
			isdoing:false,
			showpay:false,
			pmlist:[],
			cynum:0,
			bonusprice:0,
			imgurl:'',
			video_url:'',
			signtime:'',
			forget:0,
			signImg:false,
			signvio:false,
			is_forget:false,
			endDate:'',
			adsetError:'',
			// 扩展字段
			showExtForm:false,
			extConfig:{
				show_employee_no:0,
				require_employee_no:0,
				show_photo:0,
				require_photo:0,
				show_custom_fields:0,
				custom_fields:[]
			},
			extFormData:{
				employee_no:'',
				sign_photo_url:'',
				custom_fields:{}
			}
    };
  },
	onReady() {
		// #ifdef APP-PLUS
		if(this.signset.adset_st == 1){
			this.$refs.adRewardedVideo.load();
		}
		// #endif
	},
  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.getdata();
  },
	onPullDownRefresh: function () {
		this.getdata();
	},
    methods: {
			showAd() {
				this.$refs.adRewardedVideo.show();
			},
			onadload(e) {
				console.log('广告数据加载成功');
			},
			onadclose(e) {
				const detail = e.detail
				// 用户点击了【关闭广告】按钮
				if (detail && detail.isEnded) {
					// 正常播放结束
					console.log("onClose " + detail.isEnded);
					//签到是否需要拍照
					var signImg = this.signImg;
					//签到是否需要拍视频
					var signvio = this.signvio;
					//签到是否需要支付
					var signpay = this.signset.ispay;
					// 当不需要拍照 视频 支付那么直接签到 signin
					if(!signImg &&!signvio &&!signpay){
						this.signin();
					}else if(!signImg && !signvio && signpay){
						this.showpay = true;
					}else if(signImg && !signvio){
						this.uploadImg();
					}else if(!signImg && signvio){
						this.uploadvid();
					}
				} else {
					// 播放中途退出
					console.log("onClose " + detail.isEnded);
					uni.showToast({
						icon:'error',
						title:'未观看完整广告签到失败！'
					})
				}
				// 播放完毕 手动加载下一条广告
				this.$refs.adRewardedVideo.load();
			},
			onaderror(e) {
				// 广告加载失败
				this.adsetError = JSON.stringify(e.detail);
				// 广告加载失败
				uni.showModal({
					title: '错误',
					content: this.adsetError,
					showCancel:false,
					confirmText:'已知晓'
				});
			},
     	editorBindPickerChange:function(e){
				var that = this;
				var val = e.detail.value;
				  that.signtime = val;
				  that.forget =1;
				  that.signin();
			},
		getdata: function () {
			var that = this;
			that.loading = true;
			app.get('ApiSign/index', {}, function (res) {
				that.loading = false;
				that.hassign = res.hassign;
				that.signset = res.signset;
				that.userinfo = res.userinfo;
				that.list = res.list;
				that.display = res.signset.display;
				that.style = res.signset.style;
				that.end_date=res.today;
				that.selectedDate=res.selectedDate;
				that.signImg = res.signImg;
				that.signvio = res.signvio;
				that.is_forget = res.is_forget;
				that.endDate = res.endDate;
				// 扩展字段配置
				that.extConfig.show_employee_no = res.show_employee_no || 0;
				that.extConfig.require_employee_no = res.require_employee_no || 0;
				that.extConfig.show_photo = res.show_photo || 0;
				that.extConfig.require_photo = res.require_photo || 0;
				that.extConfig.show_custom_fields = res.show_custom_fields || 0;
				that.extConfig.custom_fields = res.custom_fields || [];
				//   #ifdef H5  
				that.signvio = false;
				//   #endif  

				if(res.signset.ispay==1){
					that.pmlist = res.pmlist
					that.bonusprice = res.bonusprice
					that.cynum = res.cynum
				}
				that.loaded();
				if (app.globalData.platform == 'wx' && res.signset.rewardedvideoad && !videoAd && wx.createRewardedVideoAd) {
					videoAd = wx.createRewardedVideoAd({
						adUnitId: res.signset.rewardedvideoad
					});
					videoAd.onLoad(() => {})
					videoAd.onError((err) => {})
					videoAd.onClose(res2 => {
						that.isdoing = false;
						if (res2 && res2.isEnded) {
							that.confirmsign();
						} else {
							
						}
					});
				}
			});
		},
		signinNew:function(){
			// #ifdef APP-PLUS
			// 1.判断是否开启 签到观看广告
			if(this.signset.adset_st == 1) return this.showAd(); //打开广告
			// #endif

			// 检查是否需要弹出扩展字段采集表单
			var hasExtFields = this.extConfig.show_employee_no == 1 || this.extConfig.show_photo == 1 || (this.extConfig.show_custom_fields == 1 && this.extConfig.custom_fields.length > 0);
			if(hasExtFields){
				// 重置表单数据
				this.extFormData = {
					employee_no:'',
					sign_photo_url:'',
					custom_fields:{}
				};
				this.showExtForm = true;
				return;
			}

			 //签到是否需要拍照
			var signImg = this.signImg;
			//签到是否需要拍视频
			var signvio = this.signvio;
			//签到是否需要支付
			var signpay = this.signset.ispay;
			// 当不需要拍照 视频 支付那么直接签到 signin
			if(!signImg &&!signvio &&!signpay){
				this.signin();
			}else if(!signImg && !signvio && signpay){
				this.showpay = true;
			}else if(signImg && !signvio){
				this.uploadImg();
			}else if(!signImg && signvio){
				this.uploadvid();
			}
			 
		},
		uploadImg:function(){
			var that = this;
			

			uni.showActionSheet({
				itemList: ['拍照'],
				success: function (res) {
					if (!res.cancel) {
					  if(res.tapIndex == 0){
							that.photograph();
						}
					}
				},
				fail: function (res) {
					console.log(res.errMsg);
				}
			});
		},
		uploadvid:function(){
			var that = this;
			uni.showActionSheet({
				itemList: ['视频'],
				success: function (res) {
					if (!res.cancel) {
					 if(res.tapIndex == 0){
							that.videograph();
						}
					}
				},
				fail: function (res) {
					console.log(res.errMsg);
				}
			});
		},
    signin: function () {
			var that = this;
			if(that.isdoing) return;
			that.isdoing = true;
			if (app.globalData.platform == 'wx' && that.signset.rewardedvideoad && videoAd) {
				videoAd.show().catch(() => {
					videoAd.load()
					.then(() => videoAd.show())
					.catch(err => {
						that.confirmsign();
					});
				});
			}else{
				that.confirmsign();
			}
    },
	photograph:function(){
      var that = this;
      uni.chooseImage({
        count: 1,
        sourceType: ['camera'], //拍照
        success: function (res) {
          var urls = res.tempFilePaths;
		 
          if(urls){
          	var tempFilePath = urls[0];	 	 		
			app.showLoading('上传中');
				uni.uploadFile({
					url: app.globalData.baseurl + 'ApiImageupload/uploadImg/aid/' + app.globalData.aid + '/platform/' + app.globalData.platform +'/session_id/' +app.globalData.session_id,
					filePath: tempFilePath,
					name: 'file',
					success: function(res) {						
						if(typeof res.data == 'string'){
							var data = JSON.parse(res.data);
						}else{
							var data = res.data;
						}						
						var imgurl = data.url;						
						that.imgurl = imgurl;
						app.showLoading(false);
						if (data.status == 1) {
							var signpay = that.signset.ispay;
							if(signpay && that.forget != 1){
								that.showpay = true;
							}else{
							  that.signin();
							}
						} else {
							app.alert(data.msg);
						}
					},
					fail: function(res) {
						app.showLoading(false);
						app.alert(res.errMsg);
					}
				});		
          }
        },fail:function(error){
					console.log(error);
        }
      });
    },
	videograph:function(){
      var that = this;
      uni.chooseVideo({
        count: 1,
        sourceType: ['camera'], //拍照
        success: function (res) {
          var urls = res.tempFilePath;		 
		  if(urls){
          	var tempFilePath = urls;	 	 		
			app.showLoading('上传中');
				uni.uploadFile({
					url: app.globalData.baseurl + 'ApiImageupload/uploadImg/aid/' + app.globalData.aid + '/platform/' + app.globalData.platform +'/session_id/' +app.globalData.session_id,
					filePath: tempFilePath,
					name: 'file',
					success: function(res) {						
						if(typeof res.data == 'string'){
							var data = JSON.parse(res.data);
						}else{
							var data = res.data;
						}
						that.video_url = data.url;
						app.showLoading(false);
						if (data.status == 1) {
							var signpay = that.signset.ispay;
							if(signpay && that.forget != 1){
							that.showpay = true;
						}else{
							that.signin();
						}
						} else {
							app.alert(data.msg);
						}
					},
					fail: function(res) {
						app.showLoading(false);
						app.alert(res.errMsg);
					}
				});
          }
        },fail:function(error){
					console.log(error);
        }
      });
    },
		confirmsign(){
			var that = this;
			var imgurl = that.imgurl;
			var video_url = that.video_url;
			var time = that.signtime;
			var forget = that.forget;
			 
			var url = 'ApiSign/signin';
			if(forget == 1){
				url = 'ApiSign/signForget';

			}
      app.post(url, {
				'sign_img':imgurl,
				'sign_video': video_url,
				"time":time,
				'forget':forget,
				'employee_no': that.extFormData.employee_no || '',
				'sign_photo_url': that.extFormData.sign_photo_url || '',
				'custom_fields_data': JSON.stringify(that.extFormData.custom_fields || {})
			}, function (data) {
        if (data.status == 1) {
			if(that.signset.is_check == 1){
	    	 app.success(data.msg);
			}else{
				app.success('+' + data.scoreadd + that.t('积分'));

			}
          that.getdata();
        } else {
          app.alert(data.msg);
        }
		 that.isdoing = false;
      });
		},
		// 扩展表单相关方法
		cancelExtForm:function(){
			this.showExtForm = false;
		},
		submitExtForm:function(){
			var that = this;
			// 前端校验必填项
			if(this.extConfig.show_employee_no == 1 && this.extConfig.require_employee_no == 1){
				if(!this.extFormData.employee_no || this.extFormData.employee_no.trim() === ''){
					app.alert('请填写员工号');
					return;
				}
			}
			if(this.extConfig.show_photo == 1 && this.extConfig.require_photo == 1){
				if(!this.extFormData.sign_photo_url){
					app.alert('请上传照片');
					return;
				}
			}
			if(this.extConfig.show_custom_fields == 1 && this.extConfig.custom_fields){
				for(var i=0; i<this.extConfig.custom_fields.length; i++){
					var cf = this.extConfig.custom_fields[i];
					if(cf.is_required == 1){
						var fkey = 'field_'+cf.id;
						var val = this.extFormData.custom_fields[fkey];
						if(!val || (typeof val === 'string' && val.trim() === '') || (Array.isArray(val) && val.length === 0)){
							app.alert('请填写'+cf.field_name);
							return;
						}
					}
				}
			}
			this.showExtForm = false;
			// 继续原有签到流程
			var signImg = this.signImg;
			var signvio = this.signvio;
			var signpay = this.signset.ispay;
			if(!signImg && !signvio && !signpay){
				this.signin();
			}else if(!signImg && !signvio && signpay){
				this.showpay = true;
			}else if(signImg && !signvio){
				this.uploadImg();
			}else if(!signImg && signvio){
				this.uploadvid();
			}
		},
		chooseExtPhoto:function(){
			var that = this;
			uni.chooseImage({
				count:1,
				sourceType:['album','camera'],
				success:function(res){
					var tempFilePath = res.tempFilePaths[0];
					app.showLoading('上传中');
					uni.uploadFile({
						url: app.globalData.baseurl + 'ApiImageupload/uploadImg/aid/' + app.globalData.aid + '/platform/' + app.globalData.platform +'/session_id/' +app.globalData.session_id,
						filePath: tempFilePath,
						name: 'file',
						success:function(res){
							var data = typeof res.data == 'string' ? JSON.parse(res.data) : res.data;
							app.showLoading(false);
							if(data.status == 1){
								that.extFormData.sign_photo_url = data.url;
								that.$forceUpdate();
							}else{
								app.alert(data.msg);
							}
						},
						fail:function(){
							app.showLoading(false);
							app.alert('上传失败');
						}
					});
				}
			});
		},
		chooseCustomImage:function(fieldId){
			var that = this;
			uni.chooseImage({
				count:1,
				sourceType:['album','camera'],
				success:function(res){
					var tempFilePath = res.tempFilePaths[0];
					app.showLoading('上传中');
					uni.uploadFile({
						url: app.globalData.baseurl + 'ApiImageupload/uploadImg/aid/' + app.globalData.aid + '/platform/' + app.globalData.platform +'/session_id/' +app.globalData.session_id,
						filePath: tempFilePath,
						name: 'file',
						success:function(res){
							var data = typeof res.data == 'string' ? JSON.parse(res.data) : res.data;
							app.showLoading(false);
							if(data.status == 1){
								that.$set(that.extFormData.custom_fields, 'field_'+fieldId, data.url);
							}else{
								app.alert(data.msg);
							}
						},
						fail:function(){
							app.showLoading(false);
							app.alert('上传失败');
						}
					});
				}
			});
		},
		onPickerChange:function(e, fieldId){
			var idx = e.detail.value;
			var cf = this.extConfig.custom_fields.find(function(c){ return c.id == fieldId; });
			if(cf && cf.field_options && cf.field_options[idx]){
				this.$set(this.extFormData.custom_fields, 'field_'+fieldId, cf.field_options[idx]);
			}
		},
		isChecked:function(fieldId, opt){
			var val = this.extFormData.custom_fields['field_'+fieldId];
			if(!val || !Array.isArray(val)) return false;
			return val.indexOf(opt) >= 0;
		},
		toggleCheck:function(fieldId, opt){
			var fkey = 'field_'+fieldId;
			var val = this.extFormData.custom_fields[fkey];
			if(!val || !Array.isArray(val)){
				val = [];
			}
			var idx = val.indexOf(opt);
			if(idx >= 0){
				val.splice(idx, 1);
			}else{
				val.push(opt);
			}
			this.$set(this.extFormData.custom_fields, fkey, val);
		},
		getPaiming: function () {
			var that = this;
			if (!this.nodata && !this.nomore) {
			  this.pagenum = this.pagenum + 1;
				this.loading = true;
			  app.post('ApiSign/getPaiming', {pagenum:this.pagenum}, function (res) {
					that.loading = false;
					var datalist = res.datalist;
					if (that.pagenum == 1) {
						that.list = datalist;
						if (datalist.length == 0) {
							that.nodata = true;
						}
					}else{
						if (datalist.length == 0) {
							that.nomore = true;
						} else {
							var list = that.list;
							var newdata = list.concat(datalist);
							that.list = newdata;
						}
					}
				});
			}
      
    },
		signpay:function(e){
				var that = this;
				that.showpay=true
		},
		cancel:function(e){
			var that=this
			that.showpay = false
		},
		topay:function(e){
			var that=this
			app.showLoading('提交中');
			var imgurl = that.imgurl;
			var video_url = that.video_url;
			
			var time = that.signtime;
			var forget = that.forget;
			app.post('ApiSign/createorder', {'sign_img':imgurl,'sign_video': video_url,"time":time,'forget':forget}, function (res) {
			if (res.status == 0) {
					app.error(res.msg);
					return;
				}
				app.showLoading(false);
				if(res.payorderid)
				app.goto('/pagesExt/pay/pay?id=' + res.payorderid,'redirectTo');
				that.isdoing = false;
			});
		}
  }
};
</script>
<style>
page{background:#f4f4f4}
.qd_head{width: 100%;/* height:940rpx; */position: relative;}
.qdbg{width: 100%;height:940rpx;}
.myscore{position:absolute;top:60rpx;width:100%;display:flex;color:#fff;flex-direction:column;align-items:center;z-index:2}
.myscore .f1{font-size:56rpx;font-weight:bold}
.myscore .f2{font-size:32rpx}
.signlog{position:absolute;top:50rpx;right:20rpx;color:#fff;font-size:28rpx;z-index:2}

.pmtext{position:absolute;top:100rpx;right:20rpx;color:#fff;font-size:28rpx;z-index:2}
.canyutext{position:absolute;top:150rpx;right:20rpx;color:#fff;font-size:28rpx;z-index:2}
.bonus_text{position:absolute;top:500rpx;width:100%;display:flex;color:#fff;flex-direction:column;align-items:center;z-index:2;font-size: 40rpx;}
.bonus_text .title1{ font-size: 30rpx;}
.bonus_text .t2{font-size:56rpx;font-weight:bold}

.qd_head .signbtn{position:absolute;top:740rpx;width:100%;display:flex;flex-direction:column;align-items:center;z-index:2}
.qd_head .signbtn .btn{width:440rpx;height:80rpx;border-radius:40rpx;font-size:32rpx;font-weight:bold;color:#fff}
.qd_head .signbtn .btn2{width:440rpx;height:80rpx;background:#FCB0B0;border-radius:40rpx;font-size:32rpx;font-weight:bold;color:#fff}
.qd_head .signbtn .signtip{color:#999999;margin-top:16rpx}
.btn-a { text-align: center;margin-top: 18rpx;}

.qd_head2 { padding-bottom: 20rpx;background-position: center;background-repeat: no-repeat;background-size:cover;}
.qd_head2 .calendar { width: 96%;margin: 0 2%;}
.qd_head2 .signbtn {/* position:initial;*/top: 136rpx;}
.qd_head2 .signbtn .signtip {color: #fff;}


.qd_guize{width:100%;margin:0;padding-bottom:20rpx}
.qd_guize .gztitle{width:100%;text-align:center;font-size:32rpx;color:#656565;font-weight:bold;height:100rpx;line-height:100rpx}
.guize_txt{box-sizing: border-box;padding:0 30rpx;line-height:42rpx;}
.paiming{ width:94%;margin:0 3%;background:#fff;border-radius:10px;padding:20rpx 20rpx;}
.paiming .item{ line-height: 80rpx;border-bottom: 1px dashed #eee;}
.paiming .item:last-child{border:0}
.paiming .item .f1{flex:1;display:flex;flex-direction:column}
.paiming .item .f1 .t1{color:#000000;font-size:30rpx;word-break:break-all;overflow:hidden;text-overflow:ellipsis;}
.paiming .item .f1 .t2{color:#666666}
.paiming .item .f1 .t3{color:#666666}
.paiming .item .f2{ flex:1;text-align:right;font-size:30rpx;}
.paiming .item .f2 .t1{color:#03bc01}
.paiming .item .f2 .t2{color:#000000}


.modal{ position: fixed; width: 100%; height: 100%; top:0; background: rgba(0, 0, 0, 0.5); z-index: 200;}
.modal .signbox{ position: absolute; background-color: #fff; top:20%; ;left:10% ;width:80%}
.modal .title{ height: 80rpx; ;line-height: 80rpx; text-align: center; font-weight: bold; border-bottom: 1rpx solid #f5f5f5; font-size: 32rpx; }
.modal .f1{ height: 100rpx; line-height:100rpx; margin-left: 40rpx; font-size: 28rpx; margin-bottom: 40rpx;}
.modal .f1 .t1{ color:#F21A2E }
.modal .f1 .t2{ color: #F21A2E; font-size: 36rpx;}
.modal .btn{ display: flex; margin: 0rpx 20rpx 30rpx;}
.modal .btn .btn-cancel{  background-color: #F2F2F2; width: 40%; border-radius: 10rpx;height:60rpx; font-size: 26rpx;}
.modal .btn .confirm{ width: 40%; border-radius: 10rpx; color: #fff;height: 60rpx; font-size: 26rpx;}

.itembox{ display: flex; justify-content: center; height: 100rpx; line-height: 100rpx; }
.itembox .item .t3{ color: #F21A2E;  }
.itembox .item .t2{ margin-right: 20rpx; font-size: 36rpx; color: #F21A2E;font-weight: bold; }
 
.form-uploadbtn{position:relative;height:180rpx;width:180rpx;margin-right: 16rpx;margin-bottom:10rpx;}

/* 扩展表单样式 */
.ext-form-box{border-radius:16rpx;overflow:hidden;}
.ext-field-item{margin-bottom:24rpx;}
.ext-field-label{font-size:28rpx;color:#333;margin-bottom:10rpx;font-weight:bold;}
.ext-field-label .required{color:#F21A2E;margin-left:4rpx;}
.ext-field-input{height:70rpx;line-height:70rpx;border:1rpx solid #ddd;border-radius:8rpx;padding:0 20rpx;font-size:28rpx;background:#fff;box-sizing:border-box;width:100%;}
.ext-picker{color:#999;}
.ext-photo-area{display:flex;}
.ext-photo-btn{width:160rpx;height:160rpx;border:2rpx dashed #ccc;border-radius:8rpx;display:flex;flex-direction:column;align-items:center;justify-content:center;}
.ext-photo-plus{font-size:56rpx;color:#ccc;line-height:60rpx;}
.ext-photo-txt{font-size:22rpx;color:#999;}
.ext-photo-preview{width:160rpx;height:160rpx;border-radius:8rpx;}
.ext-checkbox-group{display:flex;flex-wrap:wrap;}
.ext-checkbox-item{display:flex;align-items:center;margin-right:20rpx;margin-bottom:10rpx;font-size:28rpx;}
</style>