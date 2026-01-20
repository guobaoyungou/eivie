<template>
<view class="container">
	<block v-if="isload">

		<form @submit="formSubmit">
			<view class="form">
        <view class="form-item">
        	<text class="label flex1">证件类型</text>
        	<picker @change="bindPickerChange" :value="type" :range="typedata">
        		<view class="picker" v-if="typename">{{typename}}</view>
        		<view v-else style="color:#BBBBBB">请选择证件类型</view>
        	</picker>
          <image :src="pre_url+'/static/img/arrowright.png'" style="width:28rpx ;height: 28rpx;"></image>
        </view>
        <block v-if="type == 0">
          <view class="form-item">
            <text class="label">姓 名</text>
            <input class="input" type="text" placeholder="请输入姓名" placeholder-style="font-size:28rpx;color:#BBBBBB" name="name" :value="info.name"></input>
          </view>
          <view class="form-item">
            <text class="label">证件号码</text>
            <input class="input" type="text" placeholder="请输入证件号码" placeholder-style="font-size:28rpx;color:#BBBBBB" name="usercard" :value="info.usercard"></input>
          </view>
          <view class="form-item">
            <text class="label">联系电话</text>
            <input class="input" type="number" placeholder="请输入联系电话" placeholder-style="font-size:28rpx;color:#BBBBBB" name="tel" :value="info.tel"></input>
          </view>
          <view class="form-item">
          		<text class="label flex1">出生日期</text>
          		<picker mode="date" value="" start="1900-01-01" :end="dateFormat('','Y-m-d')" @change="bindDateChange" style="text-algin:right">
          			<view class="picker" v-if="birthday">{{birthday}}</view>
          			<view v-else style="color:#BBBBBB">请选择出生日期</view>
          		</picker>
          </view>
        </block>
        
        <block v-if="type == 1">
          <view class="form-item">
            <text class="label">姓(英文)</text>
            <input class="input" type="text" placeholder="Surname,例如:Zhang" placeholder-style="font-size:28rpx;color:#BBBBBB" name="surname" :value="info.surname"></input>
          </view>
          <view class="form-item">
            <text class="label">名(英文)</text>
            <input class="input" type="text" placeholder="Given name,例如San" placeholder-style="font-size:28rpx;color:#BBBBBB" name="surname2" :value="info.surname2"></input>
          </view>
          <view class="form-item">
            <text class="label">证件号码</text>
            <input class="input" type="text" placeholder="请输入证件号码" placeholder-style="font-size:28rpx;color:#BBBBBB" name="usercard" :value="info.usercard"></input>
          </view>
          <view class="form-item">
            <text class="label">联系电话</text>
            <input class="input" type="number" placeholder="请输入联系电话" placeholder-style="font-size:28rpx;color:#BBBBBB" name="tel" :value="info.tel"></input>
          </view>
          <view class="form-item">
          		<text class="label flex1">出生日期</text>
          		<picker mode="date" value="" start="1900-01-01" :end="dateFormat('','Y-m-d')" @change="bindDateChange" style="text-algin:right">
          			<view class="picker" v-if="birthday">{{birthday}}</view>
          			<view v-else style="color:#BBBBBB">请选择出生日期</view>
          		</picker>
          </view>
          <view class="form-item" style="justify-content: space-between;">
          		<text class="label">性别</text>
          		<radio-group  @change="sexChange" class="radio-group" name="sex">
                <label class="radio" style="margin-right: 20rpx;">
                  <radio :checked="sex==1?true:false" value="1"></radio>男
                </label>
                <label class="radio">
                  <radio :checked="sex==2?true:false" value="2"></radio>女
                </label>
          		</radio-group>
          </view>
          <view class="form-item">
          	<text class="label flex1">国籍</text>
            <picker @change="nationalChange" :value="nationalindex" :range="nationaldata" range-key='name' style="text-algin:right">
                <view class="picker" v-if="nationalityname">{{nationalityname}}</view>
                <view v-else style="color:#BBBBBB">请选择国籍</view>
            </picker>
          </view>
        </block>
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
      id:0,
      info:{},
      
      typedata:['身份证','护照'],
      type:0,
      typename:'身份证',
      
      birthday: '',
      sex:1,
      
      nationalindex:0,
      nationaldata:[],//国籍列表
      nationality:'',//国籍
      nationalityname:'',//国籍名称
    };
  },

  onLoad: function (opt) {
		this.opt = app.getopts(opt);
    this.id = this.opt.id || 0;
    this.getdata();
  },
	onPullDownRefresh: function () {
		this.getdata();
	},
  methods: {
		getdata:function(){
			var that = this;
      that.loading = true;
      app.get('ApiHanglvfeike/userdetail', {id: that.id}, function (res) {
        that.loading = false;
        that.info = res.info;
        if(res.info){
          that.type = res.info.type;
          if(that.type == 1){
            that.typename = '护照';
          }else{
            that.typename = '身份证';
          }

          that.birthday = res.info.birthday;
          that.sex = res.info.sex;
          
          that.nationality = res.info.nationality;//国籍
          that.nationalityname = res.info.nationalityname;//国籍名称
        }
        that.nationaldata = res.nationaldata?JSON.parse(res.nationaldata):[];
        that.loaded();
      });
		},
    formSubmit: function (e) {
      var that = this;
      var formdata = e.detail.value;
      var id = that.id || 0;
      if (formdata.tel == '' || formdata.usercard == '') {
        app.error('请填写完整信息');
        return;
      }

      if(that.type == 1) {
        var nationality = that.nationality;
				if(nationality == '') {
					app.error('请选择国籍');
					return;
				}
      }
      
      formdata['birthday'] = that.birthday;
      formdata['sex']      =  that.sex;
      formdata['nationality'] = that.nationality;//国籍
      formdata['nationalityname'] = that.nationalityname;//国籍名称
      
      var data = {
        id:id,
        type:that.type,
        formdata:formdata
      }

			app.showLoading('提交中');
      app.post('ApiHanglvfeike/userdetail', data, function (res) {
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
    bindPickerChange: function (e) {
      var that = this;
      var type = e.detail.value;
      that.typename = that.typedata[type];
      that.type = type;
    },
		bindDateChange: function (e) {
		  this.birthday = e.detail.value;
		},
    sexChange:function(e){
    	var sex = e.detail.value;
    	this.sex = sex;
    },
    nationalChange:function(e){
      var that = this;
      var nationalindex = e.detail.value;
      that.nationalindex= nationalindex;
      that.nationalityname = that.nationaldata[nationalindex]['name'];
      that.nationality  = that.nationaldata[nationalindex]['code'];
    },
  }
};
</script>
<style>
radio{transform:scale(.7);}
.container{display:flex;flex-direction:column}

.addfromwx{width:94%;margin:20rpx 3% 0 3%;border-radius:5px;padding:20rpx 3%;background: #FFF;display:flex;align-items:center;color:#666;font-size:28rpx;}
.addfromwx .img{width:40rpx;height:40rpx;margin-right:20rpx;}
.form{ width:94%;margin:20rpx 3%;border-radius:5px;padding: 0 3%;background: #FFF;}
.form-item{display:flex;align-items:center;width:100%;border-bottom: 1px #ededed solid;height:98rpx;}
.form-item:last-child{border:0}
.form-item .label{ color:#8B8B8B;font-weight:bold;height: 60rpx; line-height: 60rpx; text-align:left;width:160rpx;padding-right:20rpx}
.form-item .input{ flex:1;height: 60rpx; line-height: 60rpx;text-align:right}

.savebtn{ width: 90%; height:96rpx; line-height: 96rpx; text-align:center;border-radius:48rpx; color: #fff;font-weight:bold;margin: 0 5%; margin-top:60rpx; border: none; }
.picker-placeholder{font-size: 28rpx;color: #BBBBBB;}
.schooldata-class{white-space: nowrap;overflow: hidden;text-overflow: ellipsis;}

</style>