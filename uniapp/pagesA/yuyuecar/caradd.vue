<template>
<view class="container">
	<block v-if="isload">
		<form @submit="formSubmit">
			<view class="form">
        <view class="form-item" style="overflow: hidden;">
        	<text class="label">车牌</text>
        	<view style="flex: 1;"></view>
          <view style="width: 200rpx;text-align: right;overflow: hidden;line-height: 60rpx;" @tap="openNumber">
            <text>{{number}}</text>
            <image :src="pre_url+'/static/img/arrowright.png'" style="width:30rpx;height:30rpx;float: right;margin-top: 14rpx;"/>
          </view>
        </view>
        <view class="form-item">
        	<text class="label">车型</text>
          <view style="flex: 1;"></view>
          <view style="width: 200rpx;text-align: right;overflow: hidden;line-height: 60rpx;">
            <picker name="type" :value="type" :range="typedata" class="input" @change="pickerCar">
            	{{typename}}
            	<image :src="pre_url+'/static/img/arrowright.png'" style="width:30rpx;height:30rpx;float: right;margin-top: 14rpx;"/>
            </picker>
          </view>
        </view>
        <view class="form-item">
        	<text class="label">颜色</text>
        	<input class="input" type="text" placeholder="请输入颜色" placeholder-style="font-size:28rpx;color:#BBBBBB" name="color" :value="color"></input>
        </view>
				<view class="form-item">
					<text class="label">姓名</text>
					<input class="input" type="text" placeholder="请输入姓名" placeholder-style="font-size:28rpx;color:#BBBBBB" name="name" :value="name"></input>
				</view>
				<view class="form-item">
					<text class="label">手机号</text>
					<input class="input" type="number" placeholder="请输入手机号" placeholder-style="font-size:28rpx;color:#BBBBBB" name="tel" :value="tel"></input>
				</view>
			</view>
			<button class="savebtn" :style="'background:linear-gradient(90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'" form-type="submit">保 存</button>
		</form>
    
    <uni-popup id="dialogCar" ref="dialogCar" type="dialog">
      <view class="uni-popup-dialog" style="width: 100%;position: fixed;bottom: 0;left: 0;">
        <view class="uni-dialog-content" style="padding: 40rpx 0;">
          <carnumberinput @inputResult="inputnumber" @close="dialogCarClose" :defaultStr="number"></carnumberinput>
        </view>
      </view>
    </uni-popup>
	</block>
	<loading v-if="loading"></loading>
	<dp-tabbar :opt="opt"></dp-tabbar>
	<popmsg ref="popmsg"></popmsg>
</view>
</template>

<script>
import carnumberinput from './car-number-input.vue';
var app = getApp();
export default {
  components: {
  	carnumberinput
  },
  data() {
    return {
      opt:{},
      loading:false,
      isload: false,
      menuindex:-1,
      number:'',
      typedata:{},
      type:'',
      typename:'',
      color:'',
      name: '',
      tel: '',
			items:[],
      is_open:false,
      pre_url: app.globalData.pre_url,
    };
  },

  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		var that = this;
    this.getdata();
  },
	onPullDownRefresh: function () {
		this.getdata();
	},
  methods: {
		getdata:function(){
			var that = this;
			var carid = that.opt.id || '';
      that.loading = true;
      app.get('ApiYuyuecar/caradd', {id: carid}, function (res) {
        that.loading = false;
        that.typedata = res.typedata;
  
        if(res.data){
          that.number   = res.data.number;
          that.type     = res.data.type;
          that.typename = res.data.typename;
          that.color    = res.data.color;
          that.name = res.data.name;
          that.tel  = res.data.tel;
        }

        that.loaded();
      });
		},
    formSubmit: function (e) {
      console.log(e)
      var that = this;
      var formdata = e.detail.value;
      var carid = that.opt.id || '';
      var name  = formdata.name;
      var tel   = formdata.tel;
      var color = formdata.color;
      if (!that.number || that.type==='' || color==='' || name == '' || tel == '') {
        app.error('请填写完整信息');
        return;
      }
			app.showLoading('提交中');
      app.post('ApiYuyuecar/caradd', {carid: carid,number: that.number,type: that.type,color:color,name: name,tel: tel}, function (res) {
				app.showLoading(false);
        if (res.status == 1) {
          app.success('保存成功');
          setTimeout(function () {
            app.goback(true);
          }, 1000);
        }else{
          app.alert(res.msg);
          return;
        }
        
      });
    },
    delAddress: function () {
      var that = this;
      var carid = that.opt.id;
      app.confirm('确定要删除该收获地址吗?', function () {
				app.showLoading('删除中');
        app.post('ApiYuyuecar/del', {carid: carid}, function () {
					app.showLoading(false);
          app.success('删除成功');
          setTimeout(function () {
            app.goback(true);
          }, 1000);
        });
      });
    },
    pickerCar: function (e) {
      var that  = this;
      var typedata = that.typedata;
      var val   = e.detail.value;
      that.type = val;
      that.typename = typedata[val];
    },
    inputnumber:function(e){
      this.number = e;
      this.$refs.dialogCar.close();
    },
    dialogCarClose:function(){
      this.$refs.dialogCar.close();
    },
    openNumber:function(){
      this.$refs.dialogCar.open();
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
.form-item .label{ color:#8B8B8B;font-weight:bold;height: 60rpx; line-height: 60rpx; text-align:left;width:160rpx;padding-right:20rpx}
.form-item .input{ flex:1;height: 60rpx; line-height: 60rpx;text-align:right}

.savebtn{ width: 90%; height:96rpx; line-height: 96rpx; text-align:center;border-radius:48rpx; color: #fff;font-weight:bold;margin: 0 5%; margin-top:60rpx; border: none; }

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