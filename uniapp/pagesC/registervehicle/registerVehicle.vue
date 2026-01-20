<template>
	<view v-if="is_show">
		<form @submit="subform">
			<view class="form-view flex flex-col">
				<view class="form-options flex flex-bt flex-y-center">
					<view class="options-title">车牌号</view>
					<view class="options-input" @tap="openNumber">
						<view class="uni-input" v-if="carNumber">{{carNumber}}</view>
						<view class="placeholder-class" v-else>请选择车牌号</view>
					</view>
				</view>
				<view class="form-options flex flex-bt flex-y-center">
					<view class="options-title">车辆品牌</view>
					<!-- <view class=""  @tap="changeClistDialog"><text v-if="cids.length>0" style="line-height: normal;">{{cnames}}</text><text v-else style="font-size:28rpx;color: #BFBFCB;">请选择</text></view> -->
					<view class="options-input">
						<input name="car_type" :value="info.car_type" placeholder="请输入车辆品牌" placeholder-class="placeholder-class" />
					</view>
				</view>
				<view class="form-options flex flex-bt flex-y-center">
					<view class="options-title">姓名</view>
					<view class="options-input">
						<input name="truename" :value="info.truename" placeholder="请输入您的姓名" placeholder-class="placeholder-class" />
					</view>
				</view>
				<view class="form-options flex flex-bt flex-y-center">
					<view class="options-title">电话</view>
					<view class="options-input">
						<input name="tel" :value="info.tel" placeholder="请输入您的电话" placeholder-class="placeholder-class" />
					</view>
				</view>
			</view>
			<view class="form-view flex flex-col">
				<view class="form-options flex flex-col" style="margin-top: 30rpx;">
						<view class="options-title">上传行驶证</view>
						<view class="upload-pic-view flex flex-y-center">
							<view class="up-fun-view">
								<image :src="driving_license?driving_license:`${pre_url}/static/img/uploadimage.png`" @tap="uploadCardimg(2)" mode="aspectFit"></image>
								<view class="close-but" @tap="removeimg(0)" v-if="driving_license">
									<image :src="`${pre_url}/static/img/vehicle/uploadimgdelete.png`"></image>
								</view>
								<input type="text" hidden="true" name="driving_license" :value="info.driving_license"/>
							</view>
						</view>
				</view>
				<view class="form-options flex flex-col" style="margin-top: 30rpx;">
					<view class="options-title">上传身份证</view>
					<view class="upload-pic-view flex flex-y-center">
						<view class="up-fun-view">
							<image :src="idcard?idcard:`${pre_url}/static/img/ID_icon.png`" @tap="uploadCardimg(0)" mode="aspectFit"></image>
							<view class="close-but" @tap="removeimg(1)" v-if="idcard">
								<image :src="`${pre_url}/static/img/vehicle/uploadimgdelete.png`"></image>
							</view>
							<input type="text" hidden="true" name="idcard" :value="info.idcard"/>
						</view>
						<view class="up-fun-view">
							<image :src="idcard_back?idcard_back:`${pre_url}/static/img/ID_iconreverse.png`" @tap="uploadCardimg(1)" mode="aspectFit"></image>
							<view class="close-but" @tap="removeimg(2)" v-if="idcard_back">
								<image :src="`${pre_url}/static/img/vehicle/uploadimgdelete.png`"></image>
							</view>
							<input type="text" hidden="true" name="idcard_back" :value="info.idcard_back"/>
						</view>
					</view>
				</view>
			</view>
			<view class="form-view flex flex-col">
				<view class="form-options flex flex-bt flex-y-center">
					<view class="options-title">车辆保险到期时间</view>
					<picker @change="pickerBaoxian" mode="date">
						<view class="uni-input" v-if="pickerBaoxianTime">{{pickerBaoxianTime}}</view>
						<view class="placeholder-class" v-else>请选择时间</view>
					</picker>
				</view>
				<view class="form-options flex flex-bt flex-y-center">
					<view class="options-title">车辆年检时间</view>
					<picker @change="pickerNianjian" mode="date">
						<view class="uni-input" v-if="pickerNianjianTime">{{pickerNianjianTime}}</view>
						<view class="placeholder-class" v-else>请选择时间</view>
					</picker>
				</view>
				<view class="form-options flex flex-bt flex-y-center">
					<view class="options-title">车辆下次保养时间</view>
					<picker @change="pickerBaoyangNext" mode="date">
						<view class="uni-input" v-if="pickerBaoyangNextTime">{{pickerBaoyangNextTime}}</view>
						<view class="placeholder-class" v-else>请选择时间</view>
					</picker>
				</view>
				<view class="form-options flex flex-bt flex-y-center">
					<view class="placeholder-class">
						注：到期消息提醒后，下次提醒需要再次设置
					</view>
				</view>
			</view>
			<!--  -->
			<view style="width: 100%;height: calc(140rpx + env(safe-area-inset-bottom));"></view>
			<view class="pageBottom-class">
				<!-- <view class="pageBottom-but not-pageBottom-but">确定提交</view> -->
				<button class="pageBottom-but" form-type="submit" :style="{background:t('color1')}">确定提交</button>
			</view>
		</form>
		<uni-popup id="dialogCar" ref="dialogCar" type="dialog">
		  <view class="uni-popup-dialog" style="width: 100%;position: fixed;bottom: 0;left: 0;">
		    <view class="uni-dialog-content" style="padding: 40rpx 0;">
		      <carnumberinput @inputResult="inputnumber" @close="dialogCarClose" :defaultStr="info.car_num"></carnumberinput>
		    </view>
		  </view>
		</uni-popup>
		<wxxieyi></wxxieyi>
		<dp-tabbar :opt="opt"></dp-tabbar>
	</view>
</template>
<script>
	import carnumberinput from './car-number-input.vue';
	var app = getApp();
	export default{
		components: {
			carnumberinput
		},
		data(){
			return{
				pre_url: app.globalData.pre_url,
				clist:{},
				clistshow:false,
				cnames:'',
				cids:[],
				cateArr:[],
				pics:[],
				pagecontent:[],
				edit_text:'',
				edit_text_index:'',
				id:'',
				info:{},
				opt:{},
				pickerBaoyangNextTime:'',
				pickerNianjianTime:'',
				pickerBaoxianTime:'',
				carNumber:'',
				idcard:'',
				idcard_back:'',
				driving_license:'',
				is_show:false
			}
		},
		onLoad: function (opt) {
			this.opt = app.getopts(opt);
			this.id = this.opt.id || '';
			this.getdata();
		},
		methods: {
			removeimg(type){
				let that = this;
				if(type == 1){
					that.info.idcard = '';
					that.idcard = '';
				}
				if(type == 2){
					that.info.idcard_back = '';
					that.idcard_back = '';
				}
				if(type == 0){
					that.info.driving_license = '';
					that.driving_license = '';
				}
			},
			getdata:function(){
				var that = this;
				that.loading = true;
				app.get('ApiCarManagement/edit', {id: that.id}, function (res) {
					that.loading = false;
					var info = res.data || {};
					that.is_show = res.is_show;
					if(that.is_show){
						uni.setNavigationBarTitle({
							title: '车辆信息'
						});
					}else{
						app.alert('功能未开启')
					}
					that.info = info;
					if(res.data){
						that.carNumber = info.car_num;
						that.driving_license = info.driving_license;
						that.idcard = info.idcard;
						that.idcard_back = info.idcard_back;
						that.pickerBaoxianTime = info.baoxian_time;
						that.pickerNianjianTime = info.nianjian_time;
						that.pickerBaoyangNextTime = info.baoyang_time;
					}
					that.loaded();
				});
			},
			inputnumber:function(e){
				this.carNumber = e;
			  this.$refs.dialogCar.close();
			},
			dialogCarClose:function(){
			  this.$refs.dialogCar.close();
			},
			openNumber:function(){
			  this.$refs.dialogCar.open();
			},
			// 车辆下次保养时间
			pickerBaoyangNext(e){
				this.pickerBaoyangNextTime = e.detail.value;
			},
			// 车辆年检时间
			pickerNianjian(e){
				this.pickerNianjianTime = e.detail.value;
			},
			// 车辆保险到期时间
			pickerBaoxian(e){
				this.pickerBaoxianTime = e.detail.value;
			},

			subform: function (e) {
			  var that = this;
			  var formdata = e.detail.value;
				console.log(formdata)
				if(that.carNumber == ''){
					app.alert('请输入车牌号');return;
				}
				if(formdata.car_type == ''){
					app.alert('请输入车辆品牌');return;
				}
				if(formdata.truename == ''){
					app.alert('请输入姓名');return;
				}
				if(formdata.tel == ''){
					app.alert('请输入手机号');return;
				}
				formdata.baoxian_time = that.pickerBaoxianTime;
				formdata.nianjian_time = that.pickerNianjianTime;
				formdata.baoyang_time = that.pickerBaoyangNextTime;
				formdata.car_num = that.carNumber;
				app.confirm('确定要提交吗?', function () {
					app.showLoading('保存中');
					app.post('ApiCarManagement/save', {id:that.id,info:formdata}, function (res) {
						if (res.status == 0) {
							app.error(res.msg);
						} else {
							app.success(res.msg);
							
							if(res.payorderid){
								app.goto('/pagesExt/pay/pay?id=' + res.payorderid);
							}else{
								setTimeout(function () {
									app.goto('vehicleList', 'redirect');
								}, 1000);
							}
						}
					});
				});
			},
			changeClistDialog:function(){
				this.clistshow = !this.clistshow
			},
			uploadCardimg:function(type){
				var that = this;
				app.chooseImage(function(urls){
					var img = urls[0];
					if(type == 0){
						that.info.idcard = img;
						that.idcard = img;
					}
					if(type == 1){
						that.info.idcard_back = img;
						that.idcard_back = img;
					}
					if(type == 2){
						that.info.driving_license = img;
						that.driving_license = img;
					}
				},1)
			},
		},
			
	}
</script>

<style>
	.form-view{width: 100%;background: #fff;padding: 20rpx 0rpx;margin-top: 20rpx;}
	.form-options{width: 92%;margin: 0 auto;border-bottom: 1px #f8f8f8 solid;padding: 32rpx 0rpx;}
	.form-options .options-title{font-size: 28rpx;color: #242424;font-weight: 500;}
	.form-options .options-title .up-view{}
	.options-title .up-view .up-view-options{border: 1px solid rgba(93, 102, 123, 0.3);box-sizing: border-box;border-radius: 8rpx;padding: 10rpx 16rpx;color: #5D667B;
	font-size: 22rpx;}
	.options-title .up-view .up-view-options image{width: 32rpx;height: 32rpx;margin-right: 10rpx;}
	.form-options .options-input{width: 366rpx;text-align: right;}
	.form-options .textarea-view{width: 100%;line-height: 40rpx;min-height: 260rpx;height: auto;max-height: 500rpx;overflow-y: scroll;margin-top: 32rpx;}
	.textarea-view textarea{width: 100%;}
	.upload-pic-view{width: 100%;justify-content: flex-start;flex-wrap: wrap;margin-top: 32rpx;}
	.upload-pic-view .up-fun-view{width: 210rpx;height: 210rpx;border-radius:16rpx ;overflow: hidden;position: relative;margin-bottom: 20rpx;margin-right: 20rpx}
	.upload-pic-view .up-fun-view .close-but{width: 40rpx;height: 40rpx;position: absolute;top: 10rpx;right: 10rpx;border-radius: 50%;}
	.upload-pic-view .up-fun-view .close-but image{width: 100%;height: 100%;}
	.upload-pic-view .up-fun-view image{width: 100%;height: 100%;}
	.form-options .tips-text{font-size: 28rpx;color: #9D9DAB;flex: 1;padding-left: 30rpx;}
	.form-options .tips-text image{width: 32rpx;height: 32rpx;}
	.pageBottom-class{background: #fff;width: 100%;position: fixed;bottom:0;left: 50%;transform: translateX(-50%);padding-top: 20rpx;padding-bottom: calc(20rpx + env(safe-area-inset-bottom));}
	.pageBottom-but{width: 94%;border-radius: 6px;background: #9c9c9c;font-size:32rpx;font-weight: 500;color: #FFFFFF;text-align: center;margin: 0 auto;}
	.not-pageBottom-but{background: rgba(61, 91, 246, 0.2) !important;}
	/*  */
	.placeholder-class{color: #9D9DAB;font-size: 24rpx;}
	/*  */
	.uni-popup__wrapper-box{width: 100%;height: 100%;}
	.uni-popup-dialog {width: 300px;border-radius: 5px;background-color: #fff;}
	.uni-dialog-title {display: flex;flex-direction: row;justify-content: center;padding-top: 15px;padding-bottom: 5px;}
	.uni-dialog-title-text {font-size: 16px;font-weight: 500;}
	.uni-dialog-content {display: flex;flex-direction: row;justify-content: center;align-items: center;padding: 5px 15px 15px 15px;}
	.uni-dialog-content-text {font-size: 14px;color: #6e6e6e;}
	.uni-dialog-button-group {display: flex;flex-direction: row;border-top-color: #f5f5f5;border-top-style: solid;border-top-width: 1px;}
	.uni-dialog-button {display: flex;flex: 1;flex-direction: row;justify-content: center;align-items: center;height: 45px;}
	.uni-border-left {border-left-color: #f0f0f0;border-left-style: solid;border-left-width: 1px;}
	.uni-dialog-button-text {font-size: 14px;}
	.uni-button-color {color: #007aff;}
</style>