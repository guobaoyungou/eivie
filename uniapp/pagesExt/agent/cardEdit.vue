<template>
<view class="container">
	<block v-if="isload">
		<form @submit="formSubmit" @reset="formReset">
		<view class="form">
			<view class="form-item">
				<text class="label">店铺名称</text>
				<input type="text" class="input" placeholder="输入店铺名称" placeholder-style="font-size:28rpx;color:#BBBBBB" name="shopname" :value="info.shopname"></input>
			</view>
			<view class="form-item">
				<text class="label">坐标</text>
				<input type="text" class="input" disabled placeholder="请选择坐标" placeholder-style="font-size:28rpx;color:#BBBBBB" name="zuobiao" :value="latitude ? latitude+','+longitude:''" @tap="locationSelect"></input>
			</view>
				<input type="text" hidden="true" name="latitude" :value="latitude"></input>
				<input type="text" hidden="true" name="longitude" :value="longitude"></input>
			<view class="form-item">
				<text class="label">地址</text>
				<input type="text" class="input" placeholder="输入地址" placeholder-style="font-size:28rpx;color:#BBBBBB" name="address" :value="address" @input="inputAddress"></input>
			</view>
			<view class="form-item">
				<text class="label">联系人</text>
				<input type="text" class="input" placeholder="输入联系人姓名" placeholder-style="font-size:28rpx;color:#BBBBBB" name="name" :value="info.name"></input>
			</view>
			<view class="form-item">
				<text class="label">联系电话</text>
				<input type="text" class="input" placeholder="输入联系电话" placeholder-style="font-size:28rpx;color:#BBBBBB" name="tel" :value="info.tel"></input>
			</view>
		</view>
		<view class="apply_box">
			<view class="apply_item" style="border-bottom:0"><text>店铺LOGO</text></view>
			<view class="flex" style="flex-wrap:wrap;padding-bottom:20rpx;">
				<view v-for="(item, index) in pic" :key="index" class="layui-imgbox">
					<view class="layui-imgbox-close" @tap="removeimg" :data-index="index" data-field="pic"><image :src="pre_url+'/static/img/ico-del.png'"></image></view>
					<view class="layui-imgbox-img"><image :src="item" @tap="previewImage" :data-url="item" mode="widthFix"></image></view>
				</view>
				<view class="uploadbtn" :style="'background:url('+pre_url+'/static/img/shaitu_icon.png) no-repeat 60rpx;background-size:80rpx 80rpx;background-color:#F3F3F3;'" @tap="uploadimg" data-field="pic" v-if="pic.length==0"></view>
			</view>
			<input type="text" hidden="true" name="logo" :value="pic.join(',')" maxlength="-1"></input>
		</view>
		<view class="form-box">
			<view class="form-item flex-col">
				<text>店铺介绍</text>
				<view class="detailop"><view class="btn" @tap="detailAddtxt">+文本</view><view class="btn" @tap="detailAddpic">+图片</view></view>
				<view>
					<block v-for="(setData, index) in pagecontent" :key="index">
						<view class="detaildp">
						<view class="op"><view class="flex1"></view><view class="btn" @tap="detailMoveup" :data-index="index">上移</view><view class="btn" @tap="detailMovedown" :data-index="index">下移</view><view class="btn" @tap="detailMovedel" :data-index="index">删除</view></view>
						<view class="detailbox" v-if="setData">
							<block v-if="setData.temp=='text'">
								<dp-text :params="setData.params" :data="setData.data"></dp-text>
							</block>
							<block v-if="setData.temp=='picture'">
								<dp-picture :params="setData.params" :data="setData.data"></dp-picture>
							</block>
							<block v-if="setData.temp=='richtext'">
								<dp-richtext :params="setData.params" :data="setData.data" :content="setData.content"></dp-richtext>
							</block>
						</view>
						</view>
					</block>
				</view>
			</view>
		</view>
		<input type="text" hidden="true" name="id" :value="info.id" ></input>
		<button class="set-btn" form-type="submit" :style="{background:'linear-gradient(90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'}">确 认</button>
		</form>

		<uni-popup id="dialogDetailtxt" ref="dialogDetailtxt" type="dialog">
			<view class="uni-popup-dialog">
				<view class="uni-dialog-title">
					<text class="uni-dialog-title-text">请输入文本内容</text>
				</view>
				<view class="uni-dialog-content">
					<textarea value="" placeholder="请输入文本内容" @input="catcheDetailtxt"></textarea>
				</view>
				<view class="uni-dialog-button-group">
					<view class="uni-dialog-button" @click="dialogDetailtxtClose">
						<text class="uni-dialog-button-text">取消</text>
					</view>
					<view class="uni-dialog-button uni-border-left" @click="dialogDetailtxtConfirm">
						<text class="uni-dialog-button-text uni-button-color">确定</text>
					</view>
				</view>
				<view class="uni-popup-dialog__close" @click="dialogDetailtxtClose">
					<span class="uni-popup-dialog__close-icon "></span>
				</view>
			</view>
		</uni-popup>
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
			pagecontent:[],
      info: {},
			pic:[],
      latitude: '',
      longitude: '',
      address:''
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
		getdata: function () {
			var that = this;
			that.loading = true;
			app.get('ApiMy/setAgentCard', {}, function (res) {
				that.loading = false;
				that.info = res.info
				that.address = res.info.address;
				that.latitude = res.info.latitude;
				that.longitude = res.info.longitude;
				that.pagecontent = res.pagecontent ? res.pagecontent : [];
				var pics = res.info ? res.info.logo : '';
				if (pics) {
					pics = pics.split(',');
				} else {
					pics = [];
				}
				that.pic = pics;
				that.loaded();
			});
		},
    formSubmit: function (e) {
			var that = this;
      var formdata = e.detail.value;

			if (formdata.shopname == '') {
			  app.error('请填写店铺名称');
			  return false;
			}
			if (formdata.zuobiao == '') {
			  app.error('请选择店铺坐标');
			  return false;
			}
			if (formdata.address == '') {
			  app.error('请填写店铺地址');
			  return false;
			}
      if (formdata.name == '') {
        app.error('请填写联系人姓名');
        return false;
      }
      if (formdata.tel == '') {
        app.error('请填写联系电话');
        return false;
      }
			if (formdata.pic == '') {
			  app.error('请上传店铺LOGO');
			  return false;
			}
			formdata.address = that.address;
			formdata.latitude = that.latitude;
			formdata.longitude = that.longitude;
			app.showLoading('提交中');
      app.post("ApiMy/setAgentCard", {formdata: formdata,pagecontent:that.pagecontent}, function (data) {
				app.showLoading(false);
        if (data.status == 1) {
          app.success(data.msg);
          // setTimeout(function () {
          //   app.goback();
          // }, 1000);
        } else {
          app.error(data.msg);
        }
      });
    },
		uploadimg:function(e){
			var that = this;
			var field= e.currentTarget.dataset.field
			var pics = that[field]
			if(!pics) pics = [];
			app.chooseImage(function(urls){
				for(var i=0;i<urls.length;i++){
					pics.push(urls[i]);
				}
				if(field == 'pic') that.pic = pics;
			},1)
		},
		removeimg:function(e){
			var that = this;
			var index= e.currentTarget.dataset.index
			var field= e.currentTarget.dataset.field
			if(field == 'pic'){
				var pics = that.pic
				pics.splice(index,1);
				that.pic = pics;
			}e
		},
		inputAddress: function(e) {
			this.address = e.detail.value;
		},
		locationSelect: function () {
		  var that = this;
		  uni.chooseLocation({
		    success: function (res) {
		      that.info.address = res.name;
					that.info.latitude = res.latitude;
		      that.info.longitude = res.longitude;
		      that.address = res.name;
		      that.latitude = res.latitude;
		      that.longitude = res.longitude;
		    }
		  });
		},
		detailAddtxt:function(){
			this.$refs.dialogDetailtxt.open();
		},
		dialogDetailtxtClose:function(){
			this.$refs.dialogDetailtxt.close();
		},
		catcheDetailtxt:function(e){
			console.log(e)
			this.catche_detailtxt = e.detail.value;
		},
		dialogDetailtxtConfirm:function(e){
			var detailtxt = this.catche_detailtxt;
			console.log(detailtxt)
			var Mid = 'M' + new Date().getTime() + parseInt(Math.random() * 1000000);
			var pagecontent = this.pagecontent;
			pagecontent.push({"id":Mid,"temp":"text","params":{"content":detailtxt,"showcontent":detailtxt,"bgcolor":"#ffffff","fontsize":"14","lineheight":"20","letter_spacing":"0","bgpic":"","align":"left","color":"#000","margin_x":"0","margin_y":"0","padding_x":"5","padding_y":"5","quanxian":{"all":true},"platform":{"all":true}},"data":"","other":"","content":""});
			this.pagecontent = pagecontent;
			this.$refs.dialogDetailtxt.close();
		},
		detailAddpic:function(){
			var that = this;
			app.chooseImage(function(urls){
				var Mid = 'M' + new Date().getTime() + parseInt(Math.random() * 1000000);
				var pics = [];
				for(var i in urls){
					var picid = 'p' + new Date().getTime() + parseInt(Math.random() * 1000000);
					pics.push({"id":picid,"imgurl":urls[i],"hrefurl":"","option":"0"})
				}
				var pagecontent = that.pagecontent;
				pagecontent.push({"id":Mid,"temp":"picture","params":{"bgcolor":"#FFFFFF","margin_x":"0","margin_y":"0","padding_x":"0","padding_y":"0","quanxian":{"all":true},"platform":{"all":true}},"data":pics,"other":"","content":""});
				that.pagecontent = pagecontent;
			},9);
		},
		detailMoveup:function(e){
			var index = e.currentTarget.dataset.index;
			var pagecontent = this.pagecontent;
			if(index > 0)
				pagecontent[index] = pagecontent.splice(index-1, 1, pagecontent[index])[0];
		},
		detailMovedown:function(e){
			var index = e.currentTarget.dataset.index;
			var pagecontent = this.pagecontent;
			if(index < pagecontent.length-1)
				pagecontent[index] = pagecontent.splice(index+1, 1, pagecontent[index])[0];
		},
		detailMovedel:function(e){
			var index = e.currentTarget.dataset.index;
			var pagecontent = this.pagecontent;
			pagecontent.splice(index,1);
		},
  }
};
</script>
<style>
.form{ width:94%;margin:20rpx 3%;border-radius:5px;padding:20rpx 20rpx;padding: 14rpx 3%;background: #FFF;}
.form-item{display:flex;align-items:baseline;width:100%;border-bottom: 1px #ededed solid;line-height:100rpx;}
.form-item:last-child{border:0}
.form-item .label{color:#8B8B8B;font-weight:bold;width:200rpx;}
.form-item .input{flex:1;color: #000;}
.set-btn{width: 90%;margin:60rpx 5%;height:96rpx;line-height:96rpx;border-radius:48rpx;color:#FFFFFF;font-weight:bold;}

.form-box{ padding:2rpx 24rpx 0 24rpx; background: #fff;margin: 24rpx;border-radius: 10rpx}
.form-box .form-item{ line-height: 100rpx; display: flex;justify-content: space-between;border-bottom:1px solid #eee;}
.form-item .f1{color:#222;width:200rpx;flex-shrink:0}
.form-item .f2{display:flex;align-items:center}
.form-box .form-item:last-child{ border:none}
.form-box .flex-col{padding-bottom:20rpx}
.form-item input{ width: 100%; border: none;color:#111;font-size:28rpx; text-align: right}
.form-item textarea{ width:100%;min-height:200rpx;padding:20rpx 0;border: none;}

.detailop{display:flex;line-height:60rpx}
.detailop .btn{border:1px solid #ccc;margin-right:10rpx;padding:0 16rpx;color:#222;border-radius:10rpx}
.detaildp{position:relative;line-height:50rpx}
.detaildp .op{width:100%;display:flex;justify-content:flex-end;font-size:24rpx;height:60rpx;line-height:60rpx;margin-top:10rpx}
.detaildp .op .btn{background:rgba(0,0,0,0.4);margin-right:10rpx;padding:0 10rpx;color:#fff}
.detaildp .detailbox{border:2px dashed #00a0e9}

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

.apply_box{ padding:2rpx 24rpx 0 24rpx; background: #fff;margin: 24rpx;border-radius: 10rpx}
.apply_title { background: #fff}
.apply_title .qr_goback{ width:18rpx;height:32rpx; margin-left:24rpx;     margin-top: 34rpx;}
.apply_title .qr_title{ font-size: 36rpx; color: #242424;   font-weight:bold;margin: 0 auto; line-height: 100rpx;}

.apply_item{ line-height: 100rpx; display: flex;justify-content: space-between;border-bottom:1px solid #eee }
.apply_box .apply_item:last-child{ border:none}
.apply_item input{ width: 100%; border: none;color:#111;font-size:28rpx; text-align: right}
.apply_item input::placeholder{ color:#999999}
.apply_item textarea{ width:100%;min-height:200rpx;padding:20rpx 0;border: none;}
.apply_item .upload_pic{ margin:50rpx 0;background: #F3F3F3;width:90rpx;height:90rpx; text-align: center  }
.apply_item .upload_pic image{ width: 32rpx;height: 32rpx; }
.set-btn{width: 90%;margin:0 5%;height:96rpx;line-height:96rpx;border-radius:48rpx;color:#FFFFFF;font-weight:bold;}

.layui-imgbox{margin-right:16rpx;margin-bottom:10rpx;font-size:24rpx;position: relative;}
.layui-imgbox-img{display: block;width:200rpx;height:200rpx;padding:2px;border: #d3d3d3 1px solid;background-color: #f6f6f6;overflow:hidden}
.layui-imgbox-img>image{max-width:100%;}
.layui-imgbox-repeat{position: absolute;display: block;width:32rpx;height:32rpx;line-height:28rpx;right: 2px;bottom:2px;color:#999;font-size:30rpx;background:#fff}
.uploadbtn{position:relative;height:200rpx;width:200rpx}
</style>
