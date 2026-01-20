<template>
<view>
	<block v-if="isload">
		<form @submit="subform">
			<view class="form-box">
				<view class="form-item">
					<view class="f1">会员昵称<text style="color:red"> *</text></view>
					<view class="f2"><input type="text" name="nickname" :value="info.nickname" placeholder="请填写会员昵称" placeholder-style="color:#888"></input></view>
				</view>
				<view class="form-item">
					<view class="f1">手机号<text style="color:red"> *</text></view>
					<view class="f2"><input type="Number" name="tel" :value="info.tel" placeholder="请填写会员手机号" placeholder-style="color:#888"></input></view>
				</view>
				<view class="form-item">
					<view class="f1">会员等级</view>
					<view class="f2">
						<picker @change="bindPickerChange" :value="payTypeIndex" :range="payTypeArr" class="picker-class">
							<view class="uni-input">{{payTypeArr[payTypeIndex]}}</view>
						</picker>
						<image :src="pre_url+'/static/img/arrowright.png'" style="width:30rpx;height:30rpx"/>
					</view>
				</view>
				<view class="form-item">
					<view class="f1">推荐人ID</view>
					<view class="f2">
						<input type="text" name="pid" :value="info.pid" placeholder="请填写推荐人ID" placeholder-style="color:#888"></input>
					</view>
				</view>
				<view class="form-item">
					<view class="f1">密码</view>
					<view class="f2"><input type="text" name="pwd" :value="info.pwd" placeholder="请填写密码" placeholder-style="color:#888"></input></view>
				</view>
				<view class="form-item">
					<view class="f1">确认密码</view>
					<view class="f2"><input type="text" name="repwd" :value="info.repwd" placeholder="请确认密码" placeholder-style="color:#888"></input></view>
				</view>
			</view>
			<view class="form-box">
				<view class="form-item flex-col" style="border-bottom:0">
					<view class="f1">头像</view>
					<view class="f2">
						<view v-for="(item, index) in pic" :key="index" class="layui-imgbox">
							<view class="layui-imgbox-close" @tap="removeimg" :data-index="index" data-field="pic"><image style="display:block" :src="pre_url+'/static/img/ico-del.png'"></image></view>
							<view class="layui-imgbox-img"><image :src="item" @tap="previewImage" :data-url="item" mode="widthFix"></image></view>
						</view>
						<view class="uploadbtn" :style="'background:url('+pre_url+'/static/img/shaitu_icon.png) no-repeat 60rpx;background-size:80rpx 80rpx;background-color:#F3F3F3;'" @tap="uploadimg" data-field="pic" data-pernum="1" v-if="pic.length==0"></view>
					</view>
					<input type="text" hidden="true" name="pic" :value="pic.join(',')" maxlength="-1"/>
				</view>
			</view>
			<button class="savebtn" :style="'background:linear-gradient(90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'" form-type="submit">提交</button>
			<view style="height:50rpx"></view>
		</form>
	</block>
	<loading v-if="loading"></loading>
	<popmsg ref="popmsg"></popmsg>
	<wxxieyi></wxxieyi>
</view>
</template>

<script>
var app = getApp();

export default {
  data() {
    return {
			isload:false,
			loading:false,
			pre_url:app.globalData.pre_url,
      info:{},
			pic:[],
			pics:[],
			cids:[],
			cids2:[],
			payTypeArr: [],
			payTypeIndex:0,
			addType:0,
			dejiArr:[],
			isdefault:''
    };
  },

  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.addType = opt.type;
		this.getdata();
  },
  methods: {
		bindPickerChange: function(e) {
		  this.payTypeIndex = e.detail.value;
			this.paytype = this.payTypeArr[this.payTypeIndex];
			this.isdefault = this.dejiArr[this.payTypeIndex].id;
		},
		getdata:function(){
			var that = this;
			that.loading = true;
			app.post('ApiAdminMember/memberlevel',{},function (res) {
				let nameArr = res.data.map(item => item.name);
				that.dejiArr= res.data;
				that.payTypeArr =nameArr
				that.isdefault = res.data[0].id;
				that.loading = false;
				that.loaded();
			});
		},

    subform: function (e) {
      var that = this;
      var formdata = e.detail.value;
			if(!formdata.nickname) return app.error('请输入会员昵称');
			if(!formdata.tel) return app.error('请输入会员手机号');
      app.post('ApiAdminMember/memberadd', {
				nickname:formdata.nickname,
				headimg:formdata.pic,
				tel:formdata.tel,
				levelid:that.isdefault,
				reg_pid:formdata.pid,
				pwd:formdata.pwd,
				repwd:formdata.repwd,
			}, function (res) {
        if (res.status == 0) {
          app.error(res.msg);
        } else {
          app.success(res.msg);
          setTimeout(function () {
            if(that.addType == 1){
							app.goto('/admin/member/index')
						}else{
							app.goto('dkorder?mid=' + res.member.id)
						}
          }, 500);
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
				if(field == 'pic') that.pic = pics;
				if(field == 'pics') that.pics = pics;
			},pernum);
		},
		removeimg:function(e){
			var that = this;
			var index= e.currentTarget.dataset.index
			var field= e.currentTarget.dataset.field
			if(field == 'pic'){
				var pics = that.pic
				pics.splice(index,1);
				that.pic = pics;
			}else if(field == 'pics'){
				var pics = that.pics
				pics.splice(index,1);
				that.pics = pics;
			}
		}
  }
};
</script>
<style>
.picker-class{width: 300rpx;}
.picker-class .uni-input{text-align:right}
.form-box{ padding:2rpx 24rpx 0 24rpx; background: #fff;margin: 24rpx;border-radius: 10rpx}
.form-item{ line-height: 100rpx; display: flex;justify-content: space-between;border-bottom:1px solid #eee }
.form-item .f1{color:#222;width:200rpx;flex-shrink:0}
.form-item .f2{display:flex;align-items:center}
.form-box .form-item:last-child{ border:none}
.form-box .flex-col{padding-bottom:20rpx}
.form-item input{ width: 100%; border: none;color:#111;font-size:28rpx; text-align: right}
.form-item textarea{ width:100%;min-height:200rpx;padding:20rpx 0;border: none;}
.form-item .upload_pic{ margin:50rpx 0;background: #F3F3F3;width:90rpx;height:90rpx; text-align: center  }
.form-item .upload_pic image{ width: 32rpx;height: 32rpx; }
.savebtn{ width: 90%; height:96rpx; line-height: 96rpx; text-align:center;border-radius:48rpx; color: #fff;font-weight:bold;margin: 0 5%; margin-top:60rpx; border: none; }
.layui-imgbox{margin-right:16rpx;margin-bottom:10rpx;font-size:24rpx;position: relative;}
.layui-imgbox-img{display: block;width:200rpx;height:200rpx;padding:2px;border: #d3d3d3 1px solid;background-color: #f6f6f6;overflow:hidden}
.layui-imgbox-img>image{max-width:100%;}
.layui-imgbox-repeat{position: absolute;display: block;width:32rpx;height:32rpx;line-height:28rpx;right: 2px;bottom:2px;color:#999;font-size:30rpx;background:#fff}
.uploadbtn{position:relative;height:200rpx;width:200rpx}
</style>