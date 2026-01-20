<template>
  <view>
    <block v-if="isload">
      <view class="head" :style="'background: '+color1"></view>
      <view style="width: 700rpx;margin: 0 auto;background-color: #fff;;padding: 20rpx;margin-top: -60rpx;border-radius: 12rpx;">
        <view class="content_css" >
          <view class="left">
            绑定旅行社：
          </view>
          <view class="right" style="overflow: hidden;">
            <view style="width: 380rpx;float: left;overflow: hidden;text-overflow: ellipsis;white-space: nowrap;">
              {{travel_name}}
            </view>
            <view v-if="apply_status<0" @tap="saoyisao" class="scan">
              <image :src="pre_url+'/static/img/ico-scan.png'" style="width: 50rpx;height: 50rpx;"></image>
            </view>
          </view>
        </view>
        <view class="content_css" >
          <view class="left">
            真实姓名：
          </view>
          <view class="right">
            <input v-if="apply_status<0" @input="inputVal" data-name="name" name='name' placeholder="请输入真实姓名" placeholder-style="line-height:80rpx;height:80rpx" style="line-height:80rpx;height:80rpx" :value="name"/>
            <text v-else>{{name}}</text>
          </view>
        </view>
        <view class="content_css">
          <view class="left">
            手机号码：
          </view>
          <view class="right">
            <input v-if="apply_status<0" @input="inputVal" data-name="tel" name='tel' placeholder="请输入手机号码" placeholder-style="line-height:80rpx;height:80rpx" style="line-height:80rpx;height:80rpx" :value="tel"/>
            <text v-else>{{tel}}</text>
          </view>
        </view>
        <view class="content_css" style="border: 0;">
          <view class="left">
            证件号码：
          </view>
          <view class="right">
            <block v-if="apply_status<0">
              <input  @input="inputVal" data-name="tour_id_number" name='tour_id_number' placeholder="请输入导游证件号码" placeholder-style="line-height:80rpx;height:80rpx" style="line-height:80rpx;height:80rpx" :value="tour_id_number"/>
            </block>
            <block v-else>
              <view v-if="tour_id_number">{{tour_id_number}}</view>
              <view v-else @tap="addCode" style="display: inline-block;border-radius: 6rpx;padding: 0 20rpx;border: 2rpx solid #ccc;height: 60rpx;line-height: 60rpx;">
                点击填写
              </view>
            </block>
          </view>
        </view>
        <!-- <view class="content_css"  style="border: 0;height: auto;" v-if="apply_status == -2 || apply_status == -1">
          <view class="left">
            导游证件：
          </view>
          <view class="right" style="height: auto;padding:20rpx 0;">
            <view v-if="pic" class="layui-imgbox" style="width: 200rpx;">
            	<view class="layui-imgbox-close" @tap="removeimg" data-field="pic"><image style="display:block" :src="pre_url+'/static/img/ico-del.png'"></image></view>
            	<view class="layui-imgbox-img"><image :src="pic" @tap="previewImage" :data-url="pic" mode="widthFix"></image></view>
            </view>
            <view v-if="!pic" @tap="uploadimg" data-field="pic" data-pernum="1" class="uploadbtn" :style="'background:url('+pre_url+'/static/img/shaitu_icon.png) no-repeat 60rpx;background-size:80rpx 80rpx;background-color:#F3F3F3;'" ></view>
            <input type="text" hidden="true" name="pic" :value="pic?pic:''" maxlength="-1"></input>
          </view>
        </view> -->
        <!-- <view class="content_css"  style="border: 0;height: auto;" v-if="apply_status != -2 && apply_status != -1">
          <view class="left">
            导游证件：
          </view>
          <view class="right" style="height: auto;padding:20rpx 0;">
            <view class="layui-imgbox" style="width: 200rpx;">
            	<view class="layui-imgbox-img"><image :src="pic" @tap="previewImage" :data-url="pic" mode="widthFix"></image></view>
            </view>
          </view>
        </view> -->
        <view v-if="apply_status == 1 && code" class="content_css content_css2">
          <view class="left">
            优惠码：
          </view>
          <view class="right">
            <text >{{code}}</text>
          </view>
        </view>
        <view v-if="apply_status == 1 " class="content_css content_css2">
          <view class="left">
            推广码：
          </view>
          <view @tap="goto" data-url="tuiguang" class="right">
            <view style="display: inline-block;border-radius: 6rpx;padding: 0 20rpx;border: 2rpx solid #ccc;height: 60rpx;line-height: 60rpx;">点击查看</view>
          </view>
        </view>
        <view v-if="apply_status == -1 && reason" class="content_css content_css2">
          <view class="left">
            驳回原因：
          </view>
          <view class="right">
            <text style="color: red;">{{reason}}</text>
          </view>
        </view>
        <view v-if="apply_status != -2 " class="content_css content_css2">
          <view class="left">
            审核状态：
          </view>
          <view class="right">
            <text v-if="apply_status == 0">
                审核中
            </text>
            <text v-if="apply_status == 1" >
                已通过
            </text>
            <text v-if="apply_status == -1" style="color: red;">
                已驳回
            </text>
          </view>
        </view>
        
      </view>
      <view v-if="apply_status<0" class="btn" @tap="postdata" :style="'background: '+color1">提交</view>
      
      <uni-popup id="dialogExpress1" ref="dialogExpress1" type="dialog">
      	<view class="uni-popup-dialog">
      		<view class="uni-dialog-title">
      			<text class="uni-dialog-title-text">导游证件号码</text>
      		</view>
      		<view class="uni-dialog-content">
      			<view>
      				<view class="flex-y-center flex-x-center" style="margin:20rpx 20rpx;">
      					<view style="font-size:28rpx;color:#555">证件号码：</view>
      					<input type="text" placeholder="请输入导游证件号码" @input="inputIdnumber" style="border: 1px #eee solid;padding: 10rpx;height:70rpx;border-radius:4px;flex:1;font-size:28rpx;"/>
      				</view>
      			</view>
      		</view>
      		<view class="uni-dialog-button-group">
      			<view class="uni-dialog-button" @click="dialogExpress1Close">
      				<text class="uni-dialog-button-text">取消</text>
      			</view>
      			<view class="uni-dialog-button uni-border-left" @click="confirmfahuo1">
      				<text class="uni-dialog-button-text uni-button-color">确定</text>
      			</view>
      		</view>
      	</view>
      </uni-popup>
    </block>
    <dp-tabbar :opt="opt"></dp-tabbar>
    <wxxieyi></wxxieyi>
  </view>
</template>

<script>
var app = getApp();
export default {
  data() {
    return {
        opt:{},
        levelid:0,
        loading:false,
        isload: false,
        menuindex:-1,
        tlid:0,
        pre_url:app.globalData.pre_url,

        travel_id:0,
        travel_name:'',
        name:'',
        tel:'',
        code:'',
        apply_status:-2,
        reason:'',
        color1:'',
        pic:'',
        tour_id_number:''
    };
  },

  onLoad: function (opt) {
    var that = this;
		var opt = app.getopts(opt);
    if(opt.travelid){
      that.travel_id = opt.travelid
    }
    that.opt = opt;
    that.color1 = app.t('color1');
    that.getdata();
  },
	onPullDownRefresh: function () {

	},
  onPullDownRefresh: function () {

  },
  methods: {
		getdata: function () {
			var that = this;
			that.loading = true;
      app.get('ApiTour/apply', {id:0,travel_id:that.travel_id}, function (res) {
      	if (res.status == 1) {
          var data = res.data;
          
          if(!that.color1 && res._initdata){
            that.color1 = res._initdata.color1;
          }
          if(data.travel_id){
            that.travel_id = data.travel_id;
          }
          if(data.travel_name){
            that.travel_name = data.travel_name;
          }

          if(data.name){
            that.name = data.name;
          }
          if(data.tel){
            that.tel = data.tel;
          }
          if(data.code){
            that.code = data.code;
          }
          if(data.reason){
            that.reason = data.reason
          }
          if(data.tour_id_number){
            that.tour_id_number=data.tour_id_number
          }
          if(data.pic){
            that.pic = data.pic
          }

          that.apply_status = data.apply_status;

          that.loaded();
      		//app.success(res.msg);
      	}else{
      		app.alert(res.msg);
      		return;
      	}
      });
		},
    saoyisao: function (d) {
      var that = this;
    	if(app.globalData.platform == 'h5'){
    		app.alert('请使用微信扫一扫功能');return;
    	}else if(app.globalData.platform == 'mp'){
    		var jweixin = require('jweixin-module');
    		jweixin.ready(function () {   //需在用户可能点击分享按钮前就先调用
    			jweixin.scanQRCode({
    				needResult: 1, // 默认为0，扫描结果由微信处理，1则直接返回扫描结果，
    				scanType: ["qrCode","barCode"], // 可以指定扫二维码还是一维码，默认二者都有
    				success: function (res) {

    					var content = res.resultStr; // 当needResult 为 1 时，扫码返回的结果
              if(!content){
                app.alert('请扫描正确的二维码');
                return;
              }

              var pos = content.indexOf('=');
              if(pos == -1){
                app.alert('请扫描正确的二维码');
                return;
              }
              var ewmcode = content.split('=')[2];
              if(!ewmcode){
                app.alert('请扫描正确的二维码');
                return;
              };
              app.post('ApiTour/applyewm', {ewmcode:ewmcode}, function (res) {
              	if (res.status == 1) {
                  that.travel_id  = res.id;
                  that.travel_name= res.name;
              		//app.success(res.msg);
              	}else{
              		app.alert(res.msg);
              		return;
              	}
              });
    				}
    			});
    		});
    	}else{
    		uni.scanCode({
    			success: function (res) {

    				var content = res.result;
            if(!content){
              app.alert('请扫描正确的二维码');
              return;
            }
            var pos = content.indexOf('=');
            if(pos == -1){
              app.alert('请扫描正确的二维码');
              return;
            }
    				var ewmcode = content.split('=')[2];
            if(!ewmcode){
              app.alert('请扫描正确的二维码');
              return;
            };
            app.post('ApiTour/applyewm', {ewmcode:ewmcode}, function (res) {
            	if (res.status == 1) {
                that.travel_id  = res.id;
                that.travel_name= res.name;
            		//app.success(res.msg);
            	}else{
            		app.alert(res.msg);
            		return;
            	}
            });
            
    			}
    		});
    	}
    },
		postdata: function () {
			var that = this;
			var travel_id  = that.travel_id;
      if(!travel_id){
        app.alert('请选择旅行社');
      }
      var name  = that.name;
      if(!name){
        app.alert('请填写真实姓名');
      }
      var tel  = that.tel;
      if(!tel){
        app.alert('请填写手机号');
      }
      
      app.confirm('确定提交吗？',function(){
        app.showLoading('提交中');
        app.post('ApiTour/apply', {travel_id:travel_id,name:name,tel:tel,pic:that.pic,tour_id_number:that.tour_id_number}, function (res) {
        	app.showLoading(false);
        	if (res.status == 1) {
        		app.success(res.msg);
            setTimeout(function () {
              app.goback();
            }, 1000);
        	}else{
        		app.alert(res.msg);
        		return;
        	}
        });
      })
		},
    inputVal:function(e){
        var that = this;
        var val  = e.detail.value;
        var name = e.currentTarget.dataset.name;
        that[name] = val;
    },
    uploadimg:function(e){
    	var that = this;
    	var pernum = parseInt(e.currentTarget.dataset.pernum);
    	if(!pernum) pernum = 1;
    	var field= e.currentTarget.dataset.field
    	var pics = that[field]
    	if(!pics) pics = [];
      var pic = '';
    	app.chooseImage(function(urls){
    		for(var i=0;i<urls.length;i++){
          if(field == 'pics'){
            pics.push(urls[i]);
          }else{
            pic = urls[i];
          }  
    		}
    		if(field == 'pic')  that.pic = pic;
    		if(field == 'pics') that.pics = pics;
    	},pernum);
    },
    removeimg:function(e){
    	var that = this;
    	var index= e.currentTarget.dataset.index
    	var field= e.currentTarget.dataset.field
    	if(field == 'pic'){
    		that.pic = '';
    	}else if(field == 'pics'){
    		var pics = that.pics
    		pics.splice(index,1);
    		that.pics = pics;
    	}
    },
    addCode:function(){
      this.$refs.dialogExpress1.open();
    },
    dialogExpress1Close:function(){
    	this.$refs.dialogExpress1.close();
    },
    inputIdnumber:function(e){
      var id_number = e.detail.value;
      if(id_number){
        this.id_number = e.detail.value;
      }else{
        this.id_number = "";
      }
    },
    confirmfahuo1:function(){
    	var that = this
      var id_number = that.id_number;
      if(!id_number){
        app.alert('请输入导游证件号码');
        return
      }
      app.confirm('确定提交吗？',function(){
    	  app.showLoading('提交中');
    	  app.post('ApiTour/addIdnumber', {id_number:id_number}, function (res) {
    	  	app.showLoading(false);
    	  	if (res.status == 1) {
    	  		that.tour_id_number = id_number;
            app.success(res.msg);
            setTimeout(function () {
              that.$refs.dialogExpress1.close();
            }, 500);
    	  	}else{
            that.tour_id_number = '';
    	  		app.alert(res.msg);
    	  		return;
    	  	}
    	  });
      })
    }
  }
};
</script>
<style>
page{background:#f1f1f1;width: 100%;height: 100%;}
.head{width: 100%;height: 200rpx;}
.content_css{overflow: hidden;line-height: 80rpx;border-bottom: 2rpx solid #E7E7E7;}
.left{width: 180rpx;float:left}
.right{width: 480rpx;float:right;line-height:80rpx;height:80rpx}
.scan{background-color:#fff;height: 54rpx;width: 54rpx;float: right;padding: 2rpx;overflow: hidden;margin-top: 10rpx;margin-right: 20rpx;}
.btn{width: 400rpx;color: #fff;line-height: 100rpx;height: 100rpx;text-align: center;margin:0 auto;margin-top: 60rpx;border-radius: 100rpx;}
.content_css2{border: 0;border-top: 2rpx solid #E7E7E7;}

.layui-imgbox{margin-right:16rpx;margin-bottom:10rpx;font-size:24rpx;position: relative;}
.layui-imgbox-img{display: block;width:200rpx;height:200rpx;padding:2px;border: #d3d3d3 1px solid;background-color: #f6f6f6;overflow:hidden}
.layui-imgbox-img>image{max-width:100%;}
.layui-imgbox-repeat{position: absolute;display: block;width:32rpx;height:32rpx;line-height:28rpx;right: 2px;bottom:2px;color:#999;font-size:30rpx;background:#fff}
.uploadbtn{position:relative;height:200rpx;width:200rpx;border: 2rpx dashed #ccc;}

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