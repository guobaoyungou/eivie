<template>
<view class="container">
	<block v-if="isload">
    <view class="form">
      <view v-if="templatepics && templatepics.length>0" class="form-item">
        <view class="flex" style="flex-wrap:wrap;">
          <view v-for="(item2, index2) in templatepics" :key="index2" class="dp-form-imgbox" >
            <view class="dp-form-imgbox-img" style="margin-bottom: 10rpx;"><image class="image" :src="item2" @click="previewImage" :data-url="item2" mode="aspectFit"/></view>
          </view>
        </view>
      </view>
    </view>
		<form @submit="formSubmit">
			<view class="form">
        <view v-if="fieldData && fieldData.length>0" class="form-item">
        	<view class="label" style="font-weight: bold;">补充合同信息</view>
          <view style="display: none;">{{test}}</view>
        </view>
        <view v-for="(item,idx) in fieldData" >
          
          <view class="form-item">
          	<view class="label">{{item.val1}}</view>
            <view>
              <block v-if="item.key=='input'">
                  <!-- #ifdef MP-WEIXIN || MP-ALIPAY -->
                  <block v-if="item.val4==2 && item.val6==1">
                    <input :type="(item.val4==1 || item.val4==2) ? 'digit' : 'text'" disabled="true" class="input" :placeholder="item.val2" placeholder-style="font-size:28rpx;color:#BBBBBB" :value="fieldFormdata[idx]['ComponentValue']"/>
                    
                    <button class="authtel" :style="{backgroundColor:t('color1'),color:'#fff'}"  open-type="getPhoneNumber" type="primary" @getphonenumber="getPhoneNumber" :data-idx="idx">一键填写</button>
                  </block>
                  <block v-else>
                    <input :type="(item.val4==1 || item.val4==2) ? 'digit' : 'text'" 	class="input" :placeholder="item.val2" 	placeholder-style="font-size:28rpx;color:#BBBBBB" :value="fieldFormdata[idx]['ComponentValue']" @input="inputField" @blur='inputBlur' :data-idx="idx"/>
                  </block>
                  <!-- #endif -->
                  
                  <!-- #ifdef H5 || APP || MP-TOUTIAO || MP-BAIDU || MP-QQ -->
                  <block>
                    <input :type="(item.val4==1 || item.val4==2) ? 'digit' : 'text'"  class="input" :placeholder="item.val2" placeholder-style="font-size:28rpx;color:#BBBBBB" :value="fieldFormdata[idx]['ComponentValue']" @input="inputField" @blur='inputBlur' :data-idx="idx"/>
                  </block>
                  <!-- #endif -->

              </block>
              <block v-if="item.key=='textarea'">
                <textarea  class='textarea'  :placeholder="item.val2" placeholder-style="font-size:28rpx;color:#BBBBBB;"  :value="fieldFormdata[idx]['ComponentValue']" @input="inputField" @blur='inputBlur' :data-idx="idx"/>
              </block>
              <block v-if="item.key=='radio'">
                <radio-group :class="item.val10=='1'?'rowalone':'flex'" style="flex-wrap:wrap"  @change="setfield2" :data-idx="idx">
                  <label v-for="(item1,idx1) in item.val2" :key="item1.id" class="flex-y-center" :class="[item.val11=='1'?'checkborder':'',item.val10=='1'?'':'rowmore']" :style="{padding:'0 10rpx',marginTop:'10rpx',borderRadius: '10rpx'}">
                      <radio  class="radio" :value="item1" :checked="fieldFormdata[idx]['ComponentValue'] && fieldFormdata[idx]['ComponentValue']==item1 ? true : false" />{{item1}}
                  </label>
                </radio-group>
              </block>
              <block v-if="item.key=='checkbox'">
                <checkbox-group :class="item.val4=='1'?'rowalone':'flex'" style="flex-wrap:wrap" @change="setfield2" :data-idx="idx">
                  <label v-for="(item1,idx1) in item.val2" :key="item1.id" class="flex-y-center" :class="[item.val9=='1'?'checkborder':'',item.val4=='1'?'':'rowmore']" :style="{padding:'0 10rpx',marginTop:'10rpx',borderRadius: '10rpx'}">
                    <checkbox class="checkbox" :value="item1" :checked="fieldFormdata[idx]['ComponentValue'] && inArray(item1,fieldFormdata[idx]['ComponentValue']) ? true : false"/>{{item1}}
                  </label>
                </checkbox-group>
              </block>
              <block v-if="item.key=='selector'">
                <picker mode="selector" :value="fieldFormdata[idx]['pickindex']" :range="item.val2" @change="pickerField" :data-idx="idx"  style="width: 100%;">
                  <view  v-if="fieldFormdata[idx]['ComponentValue']" class="pickerview">
                    <view>{{fieldFormdata[idx]['ComponentValue']}}</view>
                    <image style="width: 40rpx;height: 40rpx;" :src="pre_url+'/static/img/arrowdown.png'"></image>
                  </view>
                  <view v-else  class="pickerview">
                    <view>请选择</view>
                    <image style="width: 40rpx;height: 40rpx;" :src="pre_url+'/static/img/arrowdown.png'"></image>
                  </view>
                </picker>
              </block>
              <block v-if="item.key=='time'">
                <picker mode="time" :value="fieldFormdata[idx]['pickindex']" :start="item.val2[0]" :end="item.val2[1]" :range="item.val2" @change="pickerField" :data-idx="idx" style="width: 100%;">
                  <view v-if="fieldFormdata[idx]['ComponentValue']" class="pickerview">
                    <view>{{fieldFormdata[idx]['ComponentValue']}}</view>
                    <image style="width: 40rpx;height: 40rpx;" :src="pre_url+'/static/img/arrowdown.png'"></image>
                  </view>
                  <view v-else class="pickerview">
                    <view>请选择</view>
                    <image style="width: 40rpx;height: 40rpx;" :src="pre_url+'/static/img/arrowdown.png'"></image>
                  </view>
                </picker>
              </block>
              <block v-if="item.key=='date'">
                <picker class="picker" mode="date" :value="fieldFormdata[idx]['pickindex']" :start="item.val2[0]" :end="item.val2[1]" :range="item.val2" @change="pickerField" :data-idx="idx">
                  <view v-if="fieldFormdata[idx]['ComponentValue']" class="pickerview">
                    <view>{{fieldFormdata[idx]['ComponentValue']}}</view>
                    <image style="width: 40rpx;height: 40rpx;" :src="pre_url+'/static/img/arrowdown.png'"></image>
                  </view>
                  <view v-else class="pickerview">
                    <view>请选择</view>
                    <image style="width: 40rpx;height: 40rpx;" :src="pre_url+'/static/img/arrowdown.png'"></image>
                  </view>
                </picker>
              </block>
              <block v-if="item.key=='year'">
                <picker class="picker" :value="fieldFormdata[idx]['pickindex']"  @change="pickerField" :data-idx="idx" :range="yearList" >
                  <view v-if="fieldFormdata[idx]['ComponentValue']" class="pickerview">
                    <view>{{fieldFormdata[idx]['ComponentValue']}}</view>
                    <image style="width: 40rpx;height: 40rpx;" :src="pre_url+'/static/img/arrowdown.png'"></image>
                  </view>
                  <view v-else class="pickerview">
                    <view>请选择</view>
                    <image style="width: 40rpx;height: 40rpx;" :src="pre_url+'/static/img/arrowdown.png'"></image>
                  </view>
                </picker>
              </block>
                      
              <block v-if="item.key=='region'">
                <uni-data-picker style="flex: 1;width: 100%;" :localdata="items" popup-title="请选择省市区" :placeholder="fieldFormdata[idx]['ComponentValue'] || '请选择省市区'" @change="onchange" :returndata="idx"></uni-data-picker>
                <input type="text" style="display:none" :value="fieldFormdata[idx]['ComponentValue']"/>
              </block>
              <block v-if="item.key=='upload'">
                <input type="text" style="display:none" :value="fieldFormdata[idx]['ComponentValue']"/>
                <view class="flex" style="flex-wrap:wrap;padding-top:20rpx">
                  <view class="dp-form-imgbox" v-if="fieldFormdata[idx]['ComponentValue']">
                    <view class="dp-form-imgbox-close" @tap="removeimg" :data-idx="idx" ><image :src="pre_url+'/static/img/ico-del.png'" class="image"></image></view>
                    <view class="dp-form-imgbox-img"><image class="image" :src="fieldFormdata[idx]['ComponentValue']" @click="previewImage" :data-url="fieldFormdata[idx]['ComponentValue']" mode="aspectFit" :data-idx="idx"/></view>
                  </view>
                  <view v-else class="dp-form-uploadbtn" :style="{background:'url('+pre_url+'/static/img/shaitu_icon.png) no-repeat 60rpx',backgroundSize:'80rpx 80rpx',backgroundColor:'#F3F3F3'}" @click="chooseImage" :data-idx="idx"  data-type="pic"></view>
                </view>
              </block>
              <!-- #ifdef H5 || MP-WEIXIN -->
              <block v-if="item.key=='upload_file'">
                <input type="text" style="display:none" :value="fieldFormdata[idx]['ComponentValue']"/>
                <view class="flex-y-center" style="flex-wrap:wrap;padding-top:20rpx">
                  <view class="dp-form-imgbox" v-if="fieldFormdata[idx]['ComponentValue']">
                    <view class="dp-form-imgbox-close" @tap="removeimg" :data-idx="idx" >
                      <image :src="pre_url+'/static/img/ico-del.png'" class="image"></image>
                    </view>
                    <view  style="overflow: hidden;white-space: pre-wrap;word-wrap: break-word;color: #4786BC;width: 530rpx;" @tap="download" :data-file="fieldFormdata[idx]['ComponentValue']" >
                      文件已上传成功
                    </view>
                  </view>
                  <view v-else class="dp-form-uploadbtn" :style="{background:'url('+pre_url+'/static/img/shaitu_icon.png) no-repeat 60rpx',backgroundSize:'80rpx 80rpx',backgroundColor:'#F3F3F3'}" @click="chooseFile" :data-idx="idx"  style="margin-right:20rpx;"></view>
                  <view v-if="item.val2" style="color:#999">{{item.val2}}</view>
                </view>
              </block>
              <!-- #endif -->
              <block v-if="item.key=='upload_video'">
                <input type="text" style="display:none" :value="fieldFormdata[idx]['ComponentValue']"/>
                <view class="flex-y-center" style="flex-wrap:wrap;padding-top:20rpx">
                  <view class="dp-form-imgbox" v-if="fieldFormdata[idx]['ComponentValue']">
                    <view class="dp-form-imgbox-close" @tap="removeimg" :data-idx="idx" >
                        <image :src="pre_url+'/static/img/ico-del.png'" class="image"></image>
                    </view>
                    <view  style="overflow: hidden;white-space: pre-wrap;word-wrap: break-word;color: #4786BC;width: 430rpx;">
                        <video  :src="fieldFormdata[idx]['ComponentValue']" style="width: 100%;"/></video>
                    </view>
                  </view>
                  <view v-else class="dp-form-uploadbtn" :style="{background:'url('+pre_url+'/static/img/shaitu_icon.png) no-repeat 60rpx',backgroundSize:'80rpx 80rpx',backgroundColor:'#F3F3F3'}" @click="upVideo" :data-idx="idx"  style="margin-right:20rpx;"></view>
                  <view v-if="item.val2" style="color:#999">{{item.val2}}</view>
                </view>
              </block>
              <block v-if="item.key=='map'">
                <input type="text" style="display:none" :value="fieldFormdata[idx]['ComponentValue']"/>
                <view class="flex-y-center" style="flex-wrap:wrap;padding: 0 20rpx;height: auto;line-height: 70rpx;background-color: #f1f1f1;">
                    <text class="flex1"  :style="area ? '' : 'color:#BBBBBB'" @click="selectzuobiao" :data-idx="idx"  >{{fieldFormdata[idx]['ComponentValue'] ? fieldFormdata[idx]['ComponentValue'] : '请点击选择您的位置'}}</text>
                </view>
              </block>
              <block v-if="item.key=='upload_pics'">
                <input type="text" style="display:none" :value="fieldFormdata[idx]['ComponentValue']?fieldFormdata[idx]['ComponentValue'].join(','):''" maxlength="-1"/>
                <view class="flex" style="flex-wrap:wrap;padding-top:20rpx">
                  <view v-for="(item2, index2) in fieldFormdata[idx]['ComponentValue']" :key="index2" class="dp-form-imgbox" >
                    <view class="dp-form-imgbox-close" @tap="removeimg" :data-index="index2" data-type="pics" :data-idx="idx" ><image :src="pre_url+'/static/img/ico-del.png'" class="image"></image></view>
                    <view class="dp-form-imgbox-img" style="margin-bottom: 10rpx;"><image class="image" :src="item2" @click="previewImage" :data-url="item2" mode="aspectFit" :data-idx="idx"/></view>
                  </view>
                  <view class="dp-form-uploadbtn" :style="{background:'url('+pre_url+'/static/img/shaitu_icon.png) no-repeat 60rpx',backgroundSize:'80rpx 80rpx',backgroundColor:'#F3F3F3',marginBottom: '10rpx'}" @click="chooseImage" :data-idx="idx"  data-type="pics"></view>
                </view>
              </block>
            </view>
          </view>
        </view>

        <view v-for="(item,index) in approverData">
          <view class="form-item">
          	<view class="label" style="font-weight: bold;">填写签署人信息</view>
          </view>
          <view v-if="item.OrganizationNameShow" class="form-item">
          	<view class="label">组织机构名称<text style="font-size: 24rpx;">(该名称与企业营业执照中注册的名称一致)</text></view>
          	<view>
              <input class="input" type="text" :name="'OrganizationName'+index" @input="inputName" data-field="OrganizationName" :data-index="index" placeholder-style="font-size:28rpx;color:#BBBBBB" ></input>
            </view>
          </view>
          <view v-if="item.UserIdShow" class="form-item">
          	<view class="label">UserId</view>
          	<view>
              <input class="input" type="text" :name="'UserId'+index" @input="inputName" data-field="UserId" :data-index="index" placeholder-style="font-size:28rpx;color:#BBBBBB" ></input>
            </view>
          </view>
          
          <view v-if="item.ApproverNameShow" class="form-item">
          	<view class="label">姓名</view>
          	<view>
              <input class="input" type="text" :name="'ApproverName'+index" @input="inputName" data-field="ApproverName" :data-index="index" placeholder-style="font-size:28rpx;color:#BBBBBB" ></input>
            </view>
          </view>
          
          <view v-if="item.ApproverMobileShow" class="form-item">
          	<view class="label">手机号</view>
            <view>
              <input class="input" type="text" :name="'ApproverMobile'+index"  @input="inputName" data-field="ApproverMobile" :data-index="index" placeholder-style="font-size:28rpx;color:#BBBBBB" ></input>
            </view>
          </view>
          <view v-if="item.ApproverIdCardNumberShow" class="form-item">
          	<view class="label">证件类型</view>
            <picker mode="selector" :value="approverFormdata[index]['ApproverIdCardTypeIndex']" :range="ApproverIdCardType" @change="pickerApproverIdCardType" :data-index="index" style="width: 100%;">
              <view class="pickerview">
                <view>{{ApproverIdCardType[approverFormdata[index]['ApproverIdCardTypeIndex']]}}</view>
                <image style="width: 40rpx;height: 40rpx;" :src="pre_url+'/static/img/arrowdown.png'"></image>
              </view>
            </picker>
          </view>
          <view v-if="item.ApproverIdCardNumberShow" class="form-item">
          	<view class="label">证件号码</view>
            <view>
              <input class="input" type="text" :name="'ApproverIdCardNumber'+index" @input="inputName" data-field="ApproverIdCardNumber" :data-index="index" placeholder-style="font-size:28rpx;color:#BBBBBB" ></input>
            </view>
          </view>
        </view>
			</view>
      <button class="savebtn" :style="'background:linear-gradient(90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'" form-type="submit">发起签署</button>
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
      platform:'',
			pre_url:app.globalData.pre_url,

      type:'',
      createflowid:0,//签署管理id
      agree_id:0,//待签署同意的记录表id
      newlv_id:0,//新等级
      fieldfill:0,//控件自动填充 0：关闭 1：开启
      
      templatepics:[],//模板图片
      fieldData:[],//控件所有数据
      fieldFormdata:[],//控件要提交数据
      authphone:'',
      yearList:[],
      test:'test',
      items: [],
      
      approverData:[],
      approverFormdata:[],
      
      ApproverIdCardType:['居民身份证','港澳居民来往内地通行证','港澳台居民居住证'],
    };
  },

  onLoad: function (opt) {
		this.opt = app.getopts(opt);
    this.type = this.opt.type || '';
    this.getdata();
  },
	onPullDownRefresh: function () {
		this.getdata();
	},
  mounted:function(){
  	var that = this;
  	that.platform = app.getplatform();
  	app.get('ApiIndex/getCustom',{}, function (customs) {
  		var url = app.globalData.pre_url+'/static/area.json';
  		if(customs.data.includes('plug_zhiming')) {
  			url = app.globalData.pre_url+'/static/area_gaoxin.json';
  		}
  		uni.request({
  			url: app.globalData.pre_url+'/static/area.json',
  			data: {},
  			method: 'GET',
  			header: { 'content-type': 'application/json' },
  			success: function(res2) {
  				that.items = res2.data
  			}
  		});
  	});
  },
  methods: {
		getdata:function(){
			var that = this;
      that.loading = true;
      if(that.type == 'member_uplevel'){
        app.get('ApiMy/tencentqianUplevel',{type:that.type},function (res){
          that.loading = false;
        	if(res.status == 1){
            uni.setNavigationBarTitle({
                title: res.FlowName|| '发起签署'
            });
            
            that.templatepics = res.templatepics || [];
            that.fieldData = res.fieldData;
            that.fieldFormdata = res.fieldFormdata;
            
            let len = that.fieldData.length;
            if(len>0){
              let year = [];
              for(let i=0;i<len;i++){
              	if(that.fieldData[i].key=='year'){
              		for(let j=that.fieldData[i].val2[0];j<=that.fieldData[i].val2[1];j++){
              			year.push(j);
              		}
              	}
              }
              that.yearList = year.reverse();
            }
            
            that.approverData = res.approverData;
            that.approverFormdata = res.approverFormdata;
            that.createflowid = res.createflowid;
            that.agree_id = res.agree_id;
            that.newlv_id = res.newlv_id;
            that.fieldfill= res.fieldfill || 0;
            that.loaded();
        	}else{
            app.alert(res.msg)
          }
        });
      }
		},
    inputName:function(e){
      var that = this;
      var value = e.detail.value;
      var field = e.currentTarget.dataset.field;
      var index = e.currentTarget.dataset.index;
      that.approverFormdata[index][field] = value
    },
    pickerApproverIdCardType: function(e) {
      var that = this;
      console.log(e)
      var value = e.detail.value;
      var index = e.currentTarget.dataset.index;
      that.approverFormdata[index]['ApproverIdCardTypeIndex'] = value;
    },
    formSubmit: function (e) {
      var that = this;
      var fieldFormdata = that.fieldFormdata;
      var len = fieldFormdata.length;
      if(len>0){
        for (var i = 0; i < len;i++){
          if (fieldFormdata[i].key == 'input' && fieldFormdata[i].val4 && fieldFormdata[i]['ComponentValue']!==''){
          	if(fieldFormdata[i].val4 == '2'){ //手机号
          		if (!app.isPhone(fieldFormdata[i]['ComponentValue'])) {
          			app.alert(fieldFormdata[i].val1+' 格式错误');return;
          		}
          	}
          	if(fieldFormdata[i].val4 == '3'){ //身份证号
          		if (!/(^\d{15}$)|(^\d{18}$)|(^\d{17}(\d|X|x)$)/.test(fieldFormdata[i]['ComponentValue'])) {
          			app.alert(fieldFormdata[i].val1+' 格式错误');return;
          		}
          	}
          	if(fieldFormdata[i].val4 == '4'){ //邮箱
          		if (!/^(.+)@(.+)$/.test(fieldFormdata[i]['ComponentValue'])) {
          			app.alert(fieldFormdata[i].val1+' 格式错误');return;
          		}
          	}
          }
        }
      }
      app.confirm('确定发起签署？',function(){
        that.loading = true;
        var data = {
          type: that.type,
          fieldFormdata:fieldFormdata,
          approverFormdata:that.approverFormdata,
          createflowid:that.createflowid,
          agree_id:that.agree_id,
          newlv_id:that.newlv_id
        }
        app.post('ApiMy/tencentqianUplevel', data, function (res) {
          that.loading = false;
          if(res.status == 1){
            app.success(res.msg);
            setTimeout(function(){
              app.goto('/pagesD/tencent_qian/qianlog','redirect')
            },900)
          }else{
            app.alert(res.msg);
          }
        });
      })
    },
    inputField:function(e){
      var that = this;
    	var idx = e.currentTarget.dataset.idx;
    	var value = e.detail.value;
    	that.fieldFormdata[idx]['ComponentValue'] = value;
      that.test = Math.random();
    },
    inputBlur:function(e){
      var that = this;
      console.log(e)
      var idx = e.currentTarget.dataset.idx;
      var value = e.detail.value;
      that.fieldFormdata[idx]['ComponentValue'] = value;
      that.test = Math.random();
      that.autofill(idx,that.fieldFormdata[idx]['ComponentValue']);
    },
    setfield2:function(e){
      var that = this;
      console.log(e)
      var idx = e.currentTarget.dataset.idx;
      var value = e.detail.value;
      that.fieldFormdata[idx]['ComponentValue'] = value;
      that.test = Math.random();
      that.autofill(idx,that.fieldFormdata[idx]['ComponentValue']);
    },
    autofill:function(idx,ComponentValue,pickindex=0){
      var that = this;
      //是否开启自动填充
      if(that.fieldfill == 1){
        var key = that.fieldFormdata[idx]['key'];
        var val0= that.fieldFormdata[idx]['val0'];
        var val22= that.fieldFormdata[idx]['val22'];
        if(val22 == '') return;
        var len = that.fieldFormdata.length;
        for(var i =0;i < len;i++){
          if(that.fieldFormdata[i]['val0'] != val0 && that.fieldFormdata[i]['key'] == key && that.fieldFormdata[i]['val22'] == val22){
            var isempty = false;//是否为空
            if(that.fieldFormdata[i]['key'] == 'checkbox' || that.fieldFormdata[i]['key'] == 'upload_pics'){
              if(that.fieldFormdata[i]['ComponentValue'].length == 0) isempty = true;
            }else{
              if(!that.fieldFormdata[i]['ComponentValue']) isempty = true;
            }
            console.log(isempty)
            if(isempty){
              that.fieldFormdata[i]['pickindex'] = pickindex;
              that.fieldFormdata[i]['ComponentValue'] = ComponentValue;
            }
          }
        }
      }
    },
    getPhoneNumber: function (e) {
    	var that = this
    	var idx = e.currentTarget.dataset.idx;
    	if(that.authphone){
    		that.fieldFormdata[idx]['ComponentValue'] = that.authphone;
        that.test = Math.random();
    		return true;
    	}

    	// #ifdef MP-WEIXIN
    	if(e.detail.errMsg == "getPhoneNumber:fail user deny"){
    		app.error('请同意授权获取手机号');return;
    	}
    	if(!e.detail.iv || !e.detail.encryptedData){
    		app.error('请同意授权获取手机号');return;
    	}
    	wx.login({success (res1){
    		var code = res1.code;
    		//用户允许授权
    		app.post('ApiIndex/authphone',{ iv: e.detail.iv,encryptedData:e.detail.encryptedData,code:code,pid:app.globalData.pid},function(res2){
    			if (res2.status == 1) {
    				that.authphone = res2.tel;
    				that.fieldFormdata[idx]['ComponentValue'] = that.authphone;
    				that.test = Math.random();
            that.autofill(idx,that.fieldFormdata[idx]['ComponentValue']);
    			} else {
    				app.error(res2.msg);
    			}
    			return;
    		})
    	}});
    	// #endif
    	
    	// #ifdef MP-ALIPAY
    	if(e.detail.errMsg == "getPhoneNumber:fail Error: 用户取消授权"){
    		app.error('请同意授权获取手机号');return;
    	}
    	else if(e.detail.errMsg && e.detail.errorMessage){
    		app.error(e.detail.errMsg);return;
    	}
    	if(e.detail.encryptedData && e.detail.encryptedData.subMsg){
    		app.error(e.detail.encryptedData.subMsg);return;
    	}
    	if(!e.detail.sign || !e.detail.encryptedData){
    		app.error('请同意授权获取手机号');return;
    	}
    	//https://opendocs.alipay.com/mini/api/getphonenumber
    	app.post('ApiIndex/aliAuthphone',{ encryptedData:e.detail.encryptedData,pid:app.globalData.pid},function(res2){
    		if (res2.status == 1) {
    			that.authphone = res2.tel;
    			that.fieldFormdata[idx]['ComponentValue'] = that.authphone;
    			that.test = Math.random();
          that.autofill(idx,that.fieldFormdata[idx]['ComponentValue']);
    		} else {
    			app.error(res2.msg);
    		}
    		return;
    	})
    	// #endif
    },
    pickerField: function(e) {
      var that = this;
      console.log(e)
      var idx = e.currentTarget.dataset.idx;
      var pickindex  = e.detail.value
      that.fieldFormdata[idx]['pickindex'] = pickindex;
      var item = that.fieldData[idx];
      if(item.key == 'selector'){
        that.fieldFormdata[idx]['ComponentValue'] = item.val2[pickindex]
      }else if(item.key == 'year'){
        that.fieldFormdata[idx]['ComponentValue'] = that.yearList[pickindex]
      }else{
        that.fieldFormdata[idx]['ComponentValue'] = pickindex
      }
      console.log(that.fieldFormdata[idx]['ComponentValue'])
      that.autofill(idx,that.fieldFormdata[idx]['ComponentValue'],that.fieldFormdata[idx]['pickindex']);
    },
    onchange(e) {
      var that = this;
      var value = e.detail.value;
      var idx = e.detail.returndata;
      var regiondata = value[0].text + ',' + value[1].text + ',' + value[2].text;
      that.fieldFormdata[idx]['ComponentValue'] = regiondata
      console.log(that.fieldFormdata[idx]['ComponentValue'])
      that.autofill(idx,that.fieldFormdata[idx]['ComponentValue']);
    },
    chooseImage: function (e) {
    	var that = this;
    	var idx = e.currentTarget.dataset.idx;
      var type = e.currentTarget.dataset.type;
    	// 选择图片多选， 最多可以选择的图片张数，默认9
    	let count = type == 'pics' ? 9 : 1; 
    	app.chooseImage(function(data){
        if(type == 'pics'){
          var pics = that.fieldFormdata[idx]['ComponentValue'];
          if(!pics){
            pics = [];
          }
          for(var i=0;i<data.length;i++){
          	pics.push(data[i]);
          }
          that.fieldFormdata[idx]['ComponentValue'] = pics;
          that.test = Math.random();
          that.autofill(idx,that.fieldFormdata[idx]['ComponentValue']);
        }else{
          that.fieldFormdata[idx]['ComponentValue'] = data[0];
          that.test = Math.random();
          console.log(that.fieldFormdata[idx]['ComponentValue'])
          that.autofill(idx,that.fieldFormdata[idx]['ComponentValue']);
        }
    	},count)
    },
    removeimg:function(e){
    	var that = this;
    	var idx = e.currentTarget.dataset.idx;

      var type  = e.currentTarget.dataset.type;
      var index = e.currentTarget.dataset.index;
      if(type == 'pics'){
        var pics = that.fieldFormdata[idx]['ComponentValue']
        pics.splice(index,1);
        that.fieldFormdata[idx]['ComponentValue'] = pics;
        that.test = Math.random();
      }else{
        that.fieldFormdata[idx]['ComponentValue'] = '';
      }
      console.log(that.fieldFormdata[idx]['ComponentValue'])
    },
    selectzuobiao: function (e) {
      var that = this;
      var idx = e.currentTarget.dataset.idx;
      uni.chooseLocation({
        success: function (res) {
          console.log(res);
          that.area = res.address;
          // that.address = res.name;
          that.adr_lat = res.latitude;
          that.adr_lon = res.longitude;
          that.fieldFormdata[idx]['ComponentValue'] = res.address;
          console.log(that.fieldFormdata[idx]['ComponentValue'])
          that.autofill(idx,that.fieldFormdata[idx]['ComponentValue']);
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
  }
};
</script>
<style>
page{background-color: #fff;}
.container{display:flex;flex-direction:column}
.form{ width: 720rpx;margin:0 auto;background: #FFF;}
.form-item{dwidth:100%;margin-bottom: 20rpx;}
.form-item:last-child{border:0}
.form-item .label{ color:#333;font-weight:normal;line-height: 70rpx; text-align:left;}
.form-item .input{ height: 70rpx; line-height: 70rpx;padding:0 20rpx ;background-color: #f1f1f1;}
.form-item textarea{ padding:20rpx ;background-color: #f1f1f1;width: 100%;}
.form-item .radio{transform:scale(.7);}
.form-item .checkbox{transform:scale(.7);}
.savebtn{ width: 90%; height:96rpx; line-height: 96rpx; text-align:center;border-radius:48rpx; color: #fff;font-weight:bold;margin: 60rpx 5%; border: none; }

.authtel{border-radius: 10rpx; height: 68rpx; line-height: 68rpx;margin-left: 20rpx;padding: 0 20rpx;}
.input-arrow {width: 7px;height: 7px;border-left: 1px solid #999;border-bottom: 1px solid #999;}
.checkborder{border: 1px solid #dcdfe6;border-radius: 5px;margin-top: 15rpx;min-width: 300rpx;padding: 0 10rpx;}
.rowalone{width: 100%;}
.rowmore{margin-right: 20rpx;}

.layui-imgbox{margin-right:16rpx;margin-bottom:10rpx;font-size:24rpx;position: relative;}
.layui-imgbox-img{display: block;width:200rpx;height:200rpx;padding:2px;border: #d3d3d3 1px solid;background-color: #f6f6f6;overflow:hidden}
.layui-imgbox-img>image{max-width:100%;}
.layui-imgbox-repeat{position: absolute;display: block;width:32rpx;height:32rpx;line-height:28rpx;right: 2px;bottom:2px;color:#999;font-size:30rpx;background:#fff}
.uploadbtn{position:relative;height:200rpx;width:200rpx}
.pickerview{display: flex;justify-content:space-between;background-color:#f1f1f1;height: 70rpx;line-height: 70rpx;padding: 0 20rpx;align-items: center;}

.dp-form-imgbox{margin-right:16rpx;font-size:24rpx;position: relative;}
.dp-form-imgbox-close{position: absolute;display: block;width:32rpx;height:32rpx;right:-16rpx;top:-16rpx;color:#999;font-size:32rpx;background:#fff;z-index:9;border-radius:50%}
.dp-form-imgbox-close .image{width:100%;height:100%}
.dp-form-imgbox-img{display: block;width:200rpx;height:200rpx;padding:2px;border: #d3d3d3 1px solid;background-color: #f6f6f6;overflow:hidden;}
.dp-form-imgbox-img>.image{width:100%;height:100%}
.dp-form-imgbox-repeat{position: absolute;display: block;width:32rpx;height:32rpx;line-height:28rpx;right: 2px;bottom:2px;color:#999;font-size:30rpx;background:#fff}
.dp-form-uploadbtn{position:relative;height:200rpx;width:200rpx}
</style>