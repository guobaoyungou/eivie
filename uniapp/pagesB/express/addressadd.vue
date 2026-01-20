<template>
<view class="container">
	<block v-if="isload">
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
					<text class="label">详细地址</text>
					<input class="input" type="text" placeholder="请输入详细地址" placeholder-style="font-size:28rpx;color:#BBBBBB" name="address" :value="address"></input>
				</view>
				<view class="item flex-y-center" v-if="type!=1">
					<view class="f2 flex-y-center flex1">
						<input id="addressxx" placeholder="粘贴地址信息，可自动识别并填写，如：张三，188********，广东省 东莞市 xx区 xx街道 xxxx" placeholder-style="font-size:24rpx;color:#BBBBBB" style="width:85%;font-size:24rpx;margin:20rpx 0;height:100rpx;padding:4rpx 10rpx" @input="setaddressxx"></input>
						<view style="width:15%;text-align:center;color:#999" @tap="shibie">识别</view>
					</view>
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
			if(addressId) {
				that.loading = true;
				app.get('ApiExpress/addressadd', {id: addressId,type: that.type}, function (res) {
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
					that.loaded();
				});
			}else{
				that.loaded();
			}
		},
		regionchange(e) {
			const value = e.detail.value
			console.log(value[0].text + ',' + value[1].text + ',' + value[2].text);
			this.regiondata = value[0].text + ',' + value[1].text + ',' + value[2].text
		},
    selectzuobiao: function () {
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
      var mailtype = that.opt.mailtype || '';
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
			app.showLoading('提交中');
      app.post('ApiExpress/addressadd', {mailtype:mailtype,type: that.type,addressid: addressId,name: name,tel: tel,area: area,address: address,latitude: latitude,longitude: longitude,company:company}, function (res) {
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
        app.post('ApiExpress/del', {addressid: addressId}, function () {
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
      app.post('ApiExpress/shibie', {addressxx: addressxx}, function (res) {
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
    }
  }
};
</script>
<style>
.container{display:flex;flex-direction:column}

.form{ width:94%;margin:20rpx 3%;border-radius:5px;padding: 0 3%;background: #FFF;}
.form-item{display:flex;align-items:center;width:100%;border-bottom: 1px #ededed solid;height:98rpx;}
.form-item:last-child{border:0}
.form-item .label{ color:#8B8B8B;font-weight:bold;height: 60rpx; line-height: 60rpx; text-align:left;width:150rpx;padding-right:20rpx}
.form-item .input{ flex:1;height: 60rpx; line-height: 60rpx;text-align:right}

.savebtn{ width: 90%; height:96rpx; line-height: 96rpx; text-align:center;border-radius:48rpx; color: #fff;font-weight:bold;margin: 0 5%; margin-top:60rpx; border: none; }


</style>