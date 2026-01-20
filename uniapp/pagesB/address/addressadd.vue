<template>
<view class="container">
	<block v-if="isload">
		<!-- #ifdef MP-WEIXIN -->
		<view class="addfromwx" v-if="type!=1" @tap="getweixinaddress"><image :src="pre_url+'/static/img/weixin.png'" class="img"/> 获取微信收货地址 <view class="flex1"></view><image :src="pre_url+'/static/img/arrowright.png'" style="width:30rpx;height:30rpx;"/></view>
		<!-- #endif -->
		<form @submit="formSubmit">
			<view class="form">
				<view class="form-item">
					<text class="label">姓 名</text>
					<input class="input" type="text" placeholder="请输入姓名" placeholder-style="font-size:28rpx;color:#BBBBBB" name="name" :value="name"></input>
				</view>
				<view class="form-item" v-if="showCompany">
					<text class="label">公 司</text>
					<input class="input" type="text" placeholder="请输入公司或单位名称" placeholder-style="font-size:28rpx;color:#BBBBBB" name="company" :value="company"></input>
				</view>
				<view class="form-item">
					<text class="label">手机号</text>
					<input class="input" type="number" placeholder="请输入手机号" placeholder-style="font-size:28rpx;color:#BBBBBB" name="tel" :value="tel"></input>
				</view>
				<view class="form-item" v-if="type==1">
					<text class="label flex0">选择位置</text>
					<text class="flex1" style="text-align:right" :style="area ? '' : 'color:#BBBBBB'" @tap="selectzuobiao" >{{area ? area : '请选择您的位置'}}</text>
					<!-- <input class="input" type="text" placeholder="请选择您的位置" placeholder-style="font-size:28rpx;color:#BBBBBB" name="area" :value="area" @tap="selectzuobiao"></input> -->
				</view>
				<view class="form-item" v-else>
					<text class="label flex1">所在地区</text>
					<uni-data-picker :localdata="items" :border="false" :placeholder="regiondata || '请选择省市区'" @change="regionchange"></uni-data-picker>
					<!-- <picker mode="region" name="regiondata" :value="regiondata" class="input" @change="bindPickerChange">
						<view class="picker" v-if="regiondata">{{regiondata}}</view>
						<view v-else>请选择地区</view>
					</picker> -->
				</view>
				<view class="form-item">
					<text class="label">{{t('详细地址')}}</text>
					<input class="input" type="text" :placeholder="'请输入'+t('详细地址')" placeholder-style="font-size:28rpx;color:#BBBBBB" name="address" :value="address"></input>
				</view>
				<view class="item flex-y-center" v-if="type!=1">
					<view class="f2 flex-y-center flex1">
						<textarea id="addressxx" placeholder="粘贴地址信息，可自动识别并填写，如：张三，188********，广东省 东莞市 xx区 xx街道 xxxx" placeholder-style="font-size:24rpx;color:#BBBBBB" style="width:85%;font-size:24rpx;margin:20rpx 0;height:100rpx;padding:4rpx 10rpx" @input="setaddressxx"></textarea>
						<view style="width:15%;text-align:center;" @tap="shibie">识别</view>
					</view>
				</view>
				<block v-if="is_show_floor && type==1 && opt.isfloor==1">
					<view class="form-item">
						<text class="label">楼层</text>
						<input class="input" type="number" placeholder="请输入楼层,例如:6" placeholder-style="font-size:28rpx;color:#BBBBBB" name="floor" :value="floor"></input>
					</view>
					<view class="form-item">
						<text class="label">房号</text>
						<input class="input" type="text" placeholder="请输入房号,例如601" placeholder-style="font-size:28rpx;color:#BBBBBB" name="room" :value="room"></input>
					</view>
					<view class="form-item">
						<view class="label flex flex-y-center" style="width: 240rpx;">是否有电梯<image class="title-icon" :src="`${pre_url}/static/img/admin/jieshiicon.png`" @click="showExplain"></image></view>
						
						<view class="switch-view flex">
							<view class='switch-options' @click='switchChange(0)' :style="{background:have_dianti == 0 ? `rgba(${t('color1rgb')},0.2)`:'',borderColor:have_dianti == 0 ? t('color1'):'',color:have_dianti == 0 ? t('color1'):''}">否</view>
							<view class='switch-options' @click='switchChange(1)' :style="{background:have_dianti == 1 ? `rgba(${t('color1rgb')},0.2)`:'',borderColor:have_dianti == 1 ? t('color1'):'',color:have_dianti == 1 ? t('color1'):''}">是</view>
						</view>
					</view>
				</block>
				
			</view>
      <view class="form" v-if="product_thali">
        <view class="form-item">
          <text class="label">学生姓名</text>
          <input class="input" type="text" placeholder="请输入学生姓名" placeholder-style="font-size:28rpx;color:#BBBBBB" name="product_thali_student_name" :value="product_thali_student_name" style="width: 650rpx;overflow: hidden;text-overflow: ellipsis;white-space:nowrap"></input>
        </view>
        <view class="form-item" v-if="schooldata && !schoolAreaText">
          <text class="label flex1" style="white-space: nowrap;">学校信息</text>
					<picker @change="PickerSchoolArea" :range="schoolArea" range-key='name'>
						<view class="flex1 schooldata-class">{{schooldata}}</view>
					</picker>
        </view>
				<view class="form-item" v-else>
					<text class="label flex1">学校区域</text>
					<picker @change="PickerSchoolArea" :range="schoolArea" range-key='name'>
						<view class="picker-select" v-if="schoolAreaText">{{schoolAreaText}}</view>
						<view class="picker-placeholder" v-else>请选择区域</view>
					</picker>
				</view>
				<view class="form-item" v-if="schoolList.length && schoolAreaText">
					<text class="label flex1">学校信息</text>
					<picker @change="PickerSchoolList" :range="schoolList" range-key='name'>
						<view class="picker-select" v-if="schoolText">{{schoolText}}</view>
						<view class="picker-placeholder" v-else>请选择学校</view>
					</picker>
				</view>
				<view class="form-item" v-if="studentGradeList.length && schoolText">
					<text class="label flex1">学生年级</text>
					<picker @change="PickerStudentGrade" :range="studentGradeList" range-key='name'>
						<view class="picker-select" v-if="studentGradeText">{{studentGradeText}}</view>
						<view class="picker-placeholder" v-else>请选择学生年级</view>
					</picker>
				</view>
				<view class="form-item" v-if="studentClassList.length && studentGradeText">
					<text class="label flex1">学生班级</text>
					<picker @change="PickerstudentClass" :range="studentClassList" range-key='name'>
						<view class="picker-select" v-if="studentClassText">{{studentClassText}}</view>
						<view class="picker-placeholder" v-else>请选择学生班级</view>
					</picker>
				</view>
      </view>
			<button class="savebtn" :style="'background:linear-gradient(90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'" form-type="submit">保 存</button>
		</form>
	</block>
	<loading v-if="loading"></loading>
	<dp-tabbar :opt="opt"></dp-tabbar>
	<popmsg ref="popmsg"></popmsg>
	<wxxieyi></wxxieyi>
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
			pre_url:app.globalData.pre_url,
			name: '',
			tel: '',
			area: '',
			address: '',
			longitude: '',
			latitude: '',
			regiondata: '',
			schooldata: '',//学校数据列表
			type: 0,
			addressxx: '',
			company: '',
			items:[],
			school:[],//选择的学校信息
			showCompany:false,
			product_thali_student_name:'',//学生姓名
			product_thali:false,
			schoolArea:[],//学校区域
			schoolAreaText:'',
			schoolList:[],//学校列表
			schoolText:"",
			studentGradeList:[],//学生年级
			studentGradeText:'',
			studentClassList:[],//学生班级
			studentClassText:'',
      fromPage:'',//来源页面
			is_show_floor:0,
			have_dianti:0,//是否有电梯
			floor:'',//楼层
			room:''//房间
    };
  },

  onLoad: function (opt) {
		this.opt = app.getopts(opt);
    this.type = this.opt.type || 0;
    this.fromPage = this.opt.fromPage || '';
		var that = this;
		
		if(this.fromPage && this.fromPage == 'depositRefund'){
			uni.setNavigationBarTitle({
				title: '编辑取件地址'
			});
		}else{
			uni.setNavigationBarTitle({
				title: '编辑收货地址'
			});
		}
		
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
		PickerSchoolArea(e){
			this.schoolAreaText = this.schoolArea[e.detail.value].name;
			this.schoolText = this.studentGradeText = this.studentClassText = this.schooldata = '';
			this.getSchool(2,this.schoolAreaText);
		},
		PickerSchoolList(e){
			this.schoolText = this.schoolList[e.detail.value].name;
			this.studentGradeText = this.studentClassText = '';
			this.getSchool(3,this.schoolList[e.detail.value].id);
		},
		PickerStudentGrade(e){
			this.studentGradeText = this.studentGradeList[e.detail.value].name;
			this.studentClassText = '';
			this.getSchool(4,this.studentGradeList[e.detail.value].id);
		},
		PickerstudentClass(e){
			this.studentClassText = this.studentClassList[e.detail.value].name;
			this.schooldata = this.schoolAreaText+','+this.schoolText+','+this.studentGradeText+','+this.studentClassText;
		},
		getSchool(Type,ID){
			let that = this;
			uni.showLoading({
				title:'加载中...'
			})
			app.post('ApiProductThali/getSchoolClassList',{type:Type,id:ID}, function (res) { 
				uni.hideLoading();
				if(Type == 1){
					that.schoolArea = res.data;
				}
				if(Type == 2){
					that.schoolList = res.data;
				}
				if(Type == 3){
					that.studentGradeList = res.data;
				}
				if(Type == 4){
					that.studentClassList = res.data;
				}
			})
		}, 
		getdata:function(){
			var that = this;
			var addressId = that.opt.id || '';
			app.get('ApiIndex/getCustom',{}, function (customs) {
				if(customs.data.includes('plug_xiongmao')) {
					that.showCompany = true;
				}
			});
			// if(addressId) {
				that.loading = true;
				app.get('ApiAddress/addressadd', {id: addressId,type: that.type,fromPage: that.fromPage}, function (res) {
					that.loading = false;
					that.name = res.data.name;
					that.tel = res.data.tel;
					that.area = res.data.area;
					that.address = res.data.address;
					that.longitude = res.data.longitude;
					that.latitude = res.data.latitude;
					that.company = res.data.company;
					if (res.data.province){
						var regiondata = res.data.province+ ',' + res.data.city+ ',' + res.data.district;
					} else {
						var regiondata = '';
					}
					that.regiondata = regiondata
          that.schooldata = res.data.product_thali_school || '';
          that.school = res.data.school;
          that.product_thali_student_name = res.data.product_thali_student_name;
          that.product_thali = res.data.product_thali;
          if(that.product_thali){
            that.getSchool(1,'');
          }
					if(res.data.is_show_floor && res.data.is_show_floor ==1){
						that.is_show_floor = res.data.is_show_floor;
					}
					if(res.data.floor){
						that.floor = res.data.floor;
					}
					if(res.data.floor){
						that.room = res.data.room;
					}
					if(res.data.have_dianti){
						that.have_dianti = res.data.have_dianti;
					}
					that.loaded();
				});
			// }else{
			// 	that.loaded();
			// }
		},
		regionchange(e) {
			const value = e.detail.value
			console.log(value[0].text + ',' + value[1].text + ',' + value[2].text);
			this.regiondata = value[0].text + ',' + value[1].text + ',' + value[2].text
		},
    selectzuobiao: function () {
			console.log('selectzuobiao')
      var that = this;
      uni.chooseLocation({
        success: function (res) {
          console.log(res);
          that.area = res.address;
          that.address = res.name;
          that.latitude = res.latitude;
          that.longitude = res.longitude;
        },
        fail: function (res) {
					console.log(res)
          if (res.errMsg == 'chooseLocation:fail auth deny') {
            //$.error('获取位置失败，请在设置中开启位置信息');
            app.confirm('获取位置失败，请在设置中开启位置信息', function () {
              uni.openSetting({});
            });
          }
        }
      });
    },
    formSubmit: function (e) {
      var that = this;
      var formdata = e.detail.value;
      var addressId = that.opt.id || '';
      var name = formdata.name;
      var tel = formdata.tel;
      var regiondata = that.regiondata;
      var product_thali_student_name = formdata.product_thali_student_name;
      var schooldata = that.schooldata;

      if (that.type == 1) {
        var area = that.area;
				if(area == '') {
					app.error('请选择位置');
					return;
				}
      } else {
        var area = regiondata;
				if(area == '') {
					app.error('请选择省市区');
					return;
				}
      }
      var address = formdata.address;
      var longitude = that.longitude;
      var latitude = that.latitude;
      var company = formdata.company;

      if (name == '' || tel == '' || address == '') {
        app.error('请填写完整信息');
        return;
      }
      // 手机号验证
      if(!app.isPhone(tel)){
        app.error('请填写正确的手机号');
        return;
      }
	  var floor = formdata.floor;
	  var room = formdata.room;
			app.showLoading('提交中');
      app.post('ApiAddress/addressadd', {type: that.type,addressid: addressId,name: name,tel: tel,area: area,address: address,latitude: latitude,longitude: longitude,company:company,product_thali_school:schooldata,product_thali_student_name:product_thali_student_name,fromPage: that.fromPage,have_dianti:that.have_dianti,floor:floor,room:room}, function (res) {
				app.showLoading(false);
        if (res.status == 0) {
          app.alert(res.msg);
          return;
        }
        app.success('保存成功');
        setTimeout(function () {
          app.goback(true);
        }, 1000);
      });
    },
    delAddress: function () {
      var that = this;
      var addressId = that.opt.id;
      app.confirm('确定要删除该收获地址吗?', function () {
				app.showLoading('删除中');
        app.post('ApiAddress/del', {addressid: addressId}, function () {
					app.showLoading(false);
          app.success('删除成功');
          setTimeout(function () {
            app.goback(true);
          }, 1000);
        });
      });
    },
    bindPickerChange: function (e) {
      var val = e.detail.value;
      this.regiondata = val;
    },
    setaddressxx: function (e) {
      this.addressxx = e.detail.value;
    },
    shibie: function () {
      var that = this;
      var addressxx = that.addressxx;
      app.post('ApiAddress/shibie', {addressxx: addressxx}, function (res) {
        var isrs = 0;
        if (res.province) {
          isrs = 1;
          that.regiondata = res.province + ',' +res.city + ',' +res.county
        }
        if (res.detail) {
          isrs = 1;
          that.address = res.detail
        }
        if (res.person) {
          isrs = 1;
          that.name = res.person
        }
        if (res.phonenum) {
          isrs = 1;
          that.tel = res.phonenum
        }
        if (isrs == 0) {
          app.error('识别失败');
        } else {
          app.success('识别完成');
        }
      });
    },
		getweixinaddress:function(){
      var that = this;
			// #ifdef MP-WEIXIN
			wx.chooseAddress({
				success (res) {
          if(that.product_thali){
            that.name = res.userName;
            that.tel = res.telNumber;
            that.area = that.regiondata = res.provinceName+','+res.cityName+','+res.countyName;
            that.address = res.detailInfo;
            return;
          }else{
            app.showLoading('提交中');
            app.post('ApiAddress/addressadd', {type: that.type,addressid: '',name: res.userName,tel: res.telNumber,area: res.provinceName+','+res.cityName+','+res.countyName,address: res.detailInfo,}, function (res) {
              app.showLoading(false);
              if (res.status == 0) {
                app.alert(res.msg);
                return;
              }
              app.success('添加成功');
              setTimeout(function () {
                app.goback(true);
              }, 1000);
            });
          }
				}
			})
			// #endif
		},
		//上楼费操作
		showExplain(e){
			uni.showModal({
				title: '',
				content: '有电梯不收费，无电梯情况下1楼不收费，2楼起算。',
				showCancel:false
			});
		},
		switchChange(index){
			this.have_dianti = index;
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
.form-item .label{ color:#333;font-weight:normal;height: 60rpx; line-height: 60rpx; text-align:left;width:160rpx;padding-right:20rpx}
.form-item .input{ flex:1;height: 60rpx; line-height: 60rpx;text-align:right}

.savebtn{ width: 90%; height:96rpx; line-height: 96rpx; text-align:center;border-radius:48rpx; color: #fff;font-weight:bold;margin: 0 5%; margin-top:60rpx; border: none; }
.picker-placeholder{font-size: 28rpx;color: #BBBBBB;}
.schooldata-class{white-space: nowrap;overflow: hidden;text-overflow: ellipsis;}
/* 楼层 */
.title-icon{width: 28rpx;height: 28rpx;margin-left: 10rpx;}
.switch-view{flex: 1; justify-content: flex-end;}
.switch-view .switch-options{width: 120rpx;text-align: center;font-size: 28rpx;color: #999;padding: 6rpx 0rpx;border-radius: 10rpx;overflow: hidden;
	margin-left: 20rpx;border: 1px solid;}
</style>