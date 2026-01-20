<template>
<view>
	<block v-if="isload">
		<view style="color:red;padding:10rpx 30rpx;margin-top:20rpx" v-if="info.id && info.status==-1">审核不通过：{{info.reason}}，请修改后再提交</view>
		<view style="color:red;padding:10rpx 30rpx;margin-top:20rpx" v-if="info.id && info.status==0">您已提交申请，请等待审核</view>
		<form @submit="subform">
			<!-- <view class="apply_box">
				<view class="apply_item">
					<view>项目名称</view>
					<view class="flex-y-center"><input type="text" name="name" :value="info.name" disabled="true"></input></view>
				</view>
			</view> -->

			<view class="apply_box">
				<view class="apply_item" style="border-bottom:0"><view>邀请二维码<text style="color:red"> *</text></view></view>
				<view class="flex" style="flex-wrap:wrap;padding-bottom:20rpx;">
					<view v-for="(item, index) in pic" :key="index" class="layui-imgbox">
						<view class="layui-imgbox-close" @tap="removeimg" :data-index="index" data-field="pic"><image :src="pre_url+'/static/img/ico-del.png'"></image></view>
						<view class="layui-imgbox-img"><image :src="item" @tap="previewImage" :data-url="item" mode="widthFix"></image></view>
					</view>
					<view class="uploadbtn" :style="'background:url('+pre_url+'/static/img/shaitu_icon.png) no-repeat 60rpx;background-size:80rpx 80rpx;background-color:#F3F3F3;'" @tap="uploadimg" data-field="pic" v-if="pic.length==0"></view>
				</view>
				<input type="text" hidden="true" name="pic" :value="pic.join(',')" maxlength="-1"></input>
				<view class="apply_item">
					<view>联系手机号</view>
					<view class="flex-y-center"><input type="text" name="tel" :value="info.tel" placeholder="请填写手机号码"></input></view>
				</view>
			</view>

			<view style="padding:30rpx 0"><button v-if="!info.id || info.status==-1" form-type="submit" class="set-btn" :style="'background:linear-gradient(90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'">提交申请</button>
</view>
		<view v-if="info.status==1" style="text-align: center;padding: 0 30rpx;">你的项目邀请二维码已审核固定<br>如要修改请联系群管理</view>
		</form>


	</block>
	<loading v-if="loading"></loading>
	<dp-tabbar :opt="opt"></dp-tabbar>
	<popmsg ref="popmsg"></popmsg>
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
      datalist: [],
      pagenum: 1,
      cateArr: [],
      cindex: 0,
			pic:[],
			pics:[],
			logo:[],
      info: {},
			bset:{},
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
			let id = this.opt.id;
			let pid = this.opt.pid;
			that.loading = true;
			app.get('ApiProject/record', {id:id,pid:pid}, function (res) {
				that.loading = false;
				uni.setNavigationBarTitle({
					title: res.title
				});

				var pics = res.info ? res.info.pics : '';
				if (pics) {
					pics = pics.split(',');
				} else {
					pics = [];
				}
				that.info = res.info
				that.logo = res.info.logo ? [res.info.logo] : [];
				that.pic = res.info.pic ? [res.info.pic] : [];
				that.pics = pics;
				that.loaded();
			});
		},
    subform: function (e) {
      var that = this;
      var info = e.detail.value;
			let pid = this.opt.pid;

      if (info.pic == '') {
        app.error('请上传邀请二维码');
        return false;
      }
			//if(!/(^0?1[3|4|5|6|7|8|9][0-9][0-9][0-9][0-9][0-9][0-9][0-9][0-9][0-9]$)/.test(tel)){
      //	dialog('手机号格式错误');return false;
      //}
      if (that.info && that.info.id) {
        info.id = that.info.id;
      }
			info.pid= that.opt.pid;
			app.showLoading('提交中');
      app.post("ApiProject/record", {info: info,pid:pid}, function (res) {
				app.showLoading(false);
        app.error(res.msg);
				if(res.status == 1){
					setTimeout(function () {
						app.goto('myrecord');
					}, 1000);
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
				if(field == 'pics') that.pics = pics;
				if(field == 'zhengming') that.zhengming = pics;
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
			}else if(field == 'pics'){
				var pics = that.pics
				pics.splice(index,1);
				that.pics = pics;
			}else if(field == 'zhengming'){
				var pics = that.zhengming
				pics.splice(index,1);
				that.zhengming = pics;
			}
		},
  }
}
</script>
<style>
radio{transform: scale(0.6);}
checkbox{transform: scale(0.6);}
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
