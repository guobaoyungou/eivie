<template>
<view class="container">
	<block v-if="isload">
		<form @submit="formSubmit" @reset="formReset" v-if="!set || set.status == 0">
		<view class="form">
			<view class="form-item">
				<text class="label">姓名</text>
				<input type="text" class="input" placeholder="请输入姓名" placeholder-style="color:#BBBBBB;font-size:28rpx" name="realname" :value="info.realname"></input>
			</view>
			<view class="form-item">
				<text class="label">身份证号</text>
				<input type="text" class="input" placeholder="请输入身份证号" placeholder-style="color:#BBBBBB;font-size:28rpx" name="usercard" :value="info.usercard"></input>
			</view>
		</view>
		<button class="set-btn" form-type="submit" :style="{background:'linear-gradient(90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'}">保 存</button>
		</form>
		<form @submit="formVerifySubmit" @reset="formReset" v-if="set && set.status == 1">
			
			<block v-if="info.realname_status == 1">
				<view class="h2 font-big">您已通过实名认证</view>
				<view class="form">
					<view class="form-item">
						<text class="label">姓名</text>
						<text class="input">{{info.realname}}</text>
					</view>
					<view class="form-item">
						<text class="label">身份证号</text>
						<text class="input">{{info.usercard}}</text>
					</view>
				</view>
			</block>
			
			<view class="" v-else>
				<view class="h2 font-big">请上传身份证的正反面</view>
				<view class="flex row">
					<view class="row-l">
						<view class="font-big">头像面</view>
						<view class="font-desc">上传您的身份证头像面</view>
					</view>
					<view class="row-r">
						<view v-for="(item, index) in idcard" :key="index" class="layui-imgbox">
							<view class="layui-imgbox-close" @tap="removeimg" :data-index="index" data-field="idcard"><image style="display:block" :src="pre_url+'/static/img/ico-del.png'"></image></view>
							<view class="layui-imgbox-img"><image :src="item" @tap="previewImage" :data-url="item" class="img" mode="aspectFit"></image></view>
						</view>
						<image :src="pre_url + '/static/img/idcard.png'" class="img" mode="aspectFit" @tap="uploadimg" data-field="idcard" v-if="idcard.length==0"></image>
						<input type="text" hidden="true" name="idcard" :value="idcard.join(',')" maxlength="-1"/>
					</view>
				</view>
				<view class="flex row">
					<view class="row-l">
						<view class="font-big">国徽面</view>
						<view class="font-desc">上传您的身份证国徽面</view>
					</view>
					<view class="row-r">
						<view v-for="(item, index) in idcard_back" :key="index" class="layui-imgbox">
							<view class="layui-imgbox-close" @tap="removeimg" :data-index="index" data-field="idcard_back"><image style="display:block" :src="pre_url+'/static/img/ico-del.png'"></image></view>
							<view class="layui-imgbox-img"><image :src="item" @tap="previewImage" :data-url="item" mode="aspectFill"></image></view>
						</view>
						<image :src="pre_url + '/static/img/idcard_back.png'" class="img" mode="aspectFit" @tap="uploadimg" data-field="idcard_back" v-if="idcard_back.length==0"></image>
						<input type="text" hidden="true" name="idcard_back" :value="idcard_back.join(',')" maxlength="-1"/>
					</view>
				</view>
				<button class="set-btn" form-type="submit" :style="{background:'linear-gradient(90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'}">保 存</button>
			</view>
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
		
			textset:{},
			set:{},
			info:{},
			idcard:[],
			idcard_back:[]
    };
  },

  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.getdata();
  },
	onPullDownRefresh: function () {
		this.getdata();
	},
  methods: {
		getdata:function(){
			var that = this;
			app.get('ApiMy/getRealnameSet',{},function (data){
				if(data){
					that.set=data.set;
					that.info=data.userinfo;
					
					that.loaded();
				}
			});
		},
    formSubmit: function (e) {
      var formdata = e.detail.value;
			var realname = formdata.realname
      if (realname == '') {
        app.alert('请输入姓名');return;
      }
	  var usercard = formdata.usercard
      var mid = '';
      if(this.opt && this.opt.mid){
        mid = this.opt.mid;
      }
      app.showLoading('提交中');
      app.post("ApiMy/setfield", {mid:mid,realname:realname,usercard:usercard}, function (data) {
        app.showLoading(false);
        if (data.status == 1) {
          app.success(data.msg);
          setTimeout(function () {
            app.goback(true);
          }, 1000);
        } else {
          app.error(data.msg);
        }
      });
    },
		formVerifySubmit: function (e) {
      var formdata = e.detail.value;
			var realname = formdata.realname
      // if (realname == '') {
      //   app.alert('请输入姓名');return;
      // }
			
			if(formdata.idcard == ''){
				app.alert('请上传身份证头像面');return;
			}
			if(formdata.idcard_back == ''){
				app.alert('请上传身份证国徽面');return;
			}
			app.showLoading('提交中');
      app.post("ApiMy/saveRealname", {info:formdata}, function (data) {
				app.showLoading(false);
        if (data.status == 1) {
          app.success(data.msg);
          setTimeout(function () {
            app.goback(true);
          }, 1000);
        } else {
          app.error(data.msg);
        }
      });
    },
		
		uploadimg:function(e){
			var that = this;
			var pernum = parseInt(e.currentTarget.dataset.pernum);
			if(!pernum) pernum = 1;
			var field= e.currentTarget.dataset.field
			var pics = that[field]
			if(!pics) pics = [];
			app.chooseImage(function(urls){
				for(var i=0;i<urls.length;i++){
					pics.push(urls[i]);
				}
				if(field == 'idcard') that.idcard = pics;
				if(field == 'idcard_back') that.idcard_back = pics;
			},pernum);
		},
		removeimg:function(e){
			var that = this;
			var index= e.currentTarget.dataset.index
			var field= e.currentTarget.dataset.field
			if(field == 'idcard'){
				var idcard = that.idcard
				idcard.splice(index,1);
				that.idcard = idcard;
			}else if(field == 'idcard_back'){
				var idcard_back = that.idcard_back
				idcard_back.splice(index,1);
				that.idcard_back = idcard_back;
			}
		},
  }
};
</script>
<style>
.container{overflow: hidden; width:96%;margin:20rpx 2%;border-radius:5px;padding:20rpx 20rpx;padding: 0 3%;}
.form{background: #FFF;padding: 0 10rpx;}
.form-item{display:flex;align-items:center;width:100%;border-bottom: 1px #ededed solid;height:98rpx;line-height:98rpx;}
.form-item:last-child{border:0}
.form-item .label{color: #000;width:200rpx;}
.form-item .input{flex:1;color: #000;}
.set-btn{width: 90%;margin:60rpx 5%;height:96rpx;line-height:96rpx;border-radius:48rpx;color:#FFFFFF;font-weight:bold;}

.h2{margin: 20rpx 0;}
.font-big {font-size:36rpx;color:#333;}
.font-desc {color:#999;}
.row { background-color: #fff;padding: 30rpx;height: 300rpx;border-radius: 10rpx;margin-top: 40rpx;}
.row-l {width: 45%;padding-top: 20rpx;}
.row-r {width: 55%;}
.img {width: 100%;height: 100%;}

.layui-imgbox{font-size:24rpx;position: relative;width: 100%;height: 100%;}
.layui-imgbox-img{width: 100%;height: 100%;display: block;padding:2px;border: #d3d3d3 1px solid;background-color: #f6f6f6;overflow:hidden}
.layui-imgbox-img>image{width: 100%;height: 100%;}
.layui-imgbox-repeat{position: absolute;display: block;width:32rpx;height:32rpx;line-height:28rpx;right: 2px;bottom:2px;color:#999;font-size:30rpx;background:#fff}
</style>