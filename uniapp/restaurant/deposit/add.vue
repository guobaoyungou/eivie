<template>
<view class="container">
	<block v-if="isload">
		<form @submit="formSubmit">
		<view class="form">
			<view class="form-item">
				<text class="label">物品名称</text>
				<input type="text" class="input" placeholder="请输入物品名称" name="name" placeholder-style="color:#BBBBBB;font-size:28rpx"/>
			</view>
			<view class="form-item">
				<text class="label">寄存数量</text>
				<input type="text" class="input" placeholder="请输入寄存数量" name="num" placeholder-style="color:#BBBBBB;font-size:28rpx"/>
			</view>
			<view class="form-item">
				<text class="label">寄存人</text>
				<input type="text" class="input" placeholder="请输入姓名" name="linkman" placeholder-style="color:#BBBBBB;font-size:28rpx"/>
			</view>
			<view class="form-item">
				<text class="label">手机号</text>
				<input type="text" class="input" placeholder="请输入手机号" name="tel" placeholder-style="color:#BBBBBB;font-size:28rpx"/>
			</view>
			<view class="form-item" v-if="opt.isusetable ==1">
				<text class="label">桌台</text>
				<view class="f2" @tap="changetablelistDialog">
					<text v-if="tablename !=''">{{tablename}}</text>
					<text v-else style="color:#888">请选择</text>
					<image :src="pre_url+'/static/img/arrowright.png'" style="width:30rpx;height:30rpx"/>
				</view>
			</view>
		</view>
		<view class="form">
			<view class="form-item">
				<text class="label">备注</text>
				<input type="text" class="input" placeholder="如您有其他需求请填写" name="message" placeholder-style="color:#BBBBBB;font-size:28rpx"/>
			</view>
		</view>
		<view class="form">
			<view class="flex-col">
				<text class="label" style="height:98rpx;line-height:98rpx;font-size:30rpx">寄存拍照</text>
				<view class="flex" style="flex-wrap:wrap;padding-bottom:20rpx;">
					<view v-for="(item, index) in pic" :key="index" class="layui-imgbox">
						<view class="layui-imgbox-close" @tap="removeimg" :data-index="index" data-field="pic"><image :src="pre_url+'/static/img/ico-del.png'"></image></view>
						<view class="layui-imgbox-img"><image :src="item" @tap="previewImage" :data-url="item" mode="widthFix"></image></view>
					</view>
					<view class="uploadbtn" :style="'background:url('+pre_url+'/static/img/shaitu_icon.png) no-repeat 60rpx;background-size:80rpx 80rpx;background-color:#F3F3F3;'" @tap="uploadimg" data-field="pic" v-if="pic.length==0"></view>
				</view>
				<input type="text" hidden="true" name="pic" :value="pic.join(',')" maxlength="-1"></input>
			</view>
		</view>
		<button class="btn" form-type="submit" :style="'background:linear-gradient(90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'">提交寄存</button>
		</form>
		
		<view class="popup__container" v-if="tablelistshow">
			<view class="popup__overlay" @tap.stop="changetablelistDialog"></view>
			<view class="popup__modal">
				<view class="popup__title">
					<text class="popup__title-text">请选择桌台</text>
					<image :src="pre_url+'/static/img/close.png'" class="popup__close" style="width:36rpx;height:36rpx" @tap.stop="changetablelistDialog"/>
				</view>
				<view class="popup__content">
					<block v-for="(item, index) in tablelist" :key="item.id">
						<view class="clist-item" @tap="tableChange" :data-id="item.id" :data-name="item.name">
							<view class="flex1">{{item.name}}</view>
							<view class="radio" :style="item.id == tableid ? 'background:'+t('color1')+';border:0' : ''"><image class="radio-img" :src="pre_url+'/static/img/checkd.png'"/></view>
						</view>
					</block>
				</view>
			</view>
		</view>
	</block>
	<loading v-if="loading"></loading>
	<dp-tabbar :opt="opt" @getmenuindex="getmenuindex"></dp-tabbar>
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

			pic:[],
			tablelist:[],
			tableid:0,
			tablename:'吧台',
			tablelistshow:false,
			
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
			this.gettablelist();
			that.loaded();
		},
		formSubmit:function(e){
			var that = this;
			var info = e.detail.value;
			var tel = this.tel
			var renshu = this.renshu;
			var cid = this.cid;
			if(!info.name){
				app.alert('请输入寄存物品名称');return;
			}
			if(!info.num){
				app.alert('请输入寄存数量');return;
			}
			if(!info.linkman){
				app.alert('请输入您的姓名');return;
			}
			if(!info.tel){
				app.alert('请输入您的手机号');return;
			}
			if(!info.pic){
				app.alert('请上传寄存物品拍照');return;
			}
			if(that.opt.isusetable ==1){
				info.tableid = that.tableid;
			}
			info.bid = that.opt.bid;
			app.showLoading('提交中');
			app.post('ApiRestaurantDeposit/add',info,function(res){
				app.showLoading(false);
				if(res.status==0){
					app.alert(res.msg);
				}else{
					app.alert(res.msg,function(){
						app.goto('orderdetail?bid='+that.opt.bid,'reLaunch');
					});
				}
			});
		},
		gettablelist(){
			var that = this;
			var bid = that.opt.bid ? that.opt.bid : 0;
			that.loading = true;
			app.get('ApiRestaurantDeposit/gettablelist',{bid:bid}, function (res) {
				that.loading = false;
				that.tablelist = res.data;
			})
		},
		changetablelistDialog(){
			this.tablelistshow = !this.tablelistshow;
		}, 
		tableChange:function(e){
			var that = this;
			var tableid = e.currentTarget.dataset.id;
			that.tableid = tableid;
			var tablename = e.currentTarget.dataset.name;
			that.tablename = tablename;
			console.log(that.tableid);
			that.changetablelistDialog();
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
			}
		},
  }
};
</script>
<style>
page {position: relative;width: 100%;height: 100%;}
.container{height:100%;overflow:hidden;position: relative;}

.form{ width:94%;margin:0 3%;border-radius:5px;padding:20rpx 20rpx;padding: 0 3%;background: #FFF;margin-top:20rpx}
.form-item{display:flex;align-items:center;width:100%;border-bottom: 1px #ededed solid;height:98rpx;line-height:98rpx;font-size:30rpx}
.form-item:last-child{border:0}
.form-item .label{color: #000;width:200rpx;}
.form-item .input{flex:1;color: #000;text-align:right}
.form-item .f2{flex:1;color: #000;text-align:right}
.form-item .picker{height: 60rpx;line-height:60rpx;margin-left: 0;flex:1;color: #000;}

.btn{width:94%;margin:0 3%;margin-top:40rpx;height:90rpx;line-height:90rpx;text-align:center;background: linear-gradient(90deg, #FF7D15 0%, #FC5729 100%);color:#fff;font-size:32rpx;font-weight:bold;border-radius:10rpx}

.layui-imgbox{margin-right:16rpx;margin-bottom:10rpx;font-size:24rpx;position: relative;}
.layui-imgbox-img{display: block;width:200rpx;height:200rpx;padding:2px;border: #d3d3d3 1px solid;background-color: #f6f6f6;overflow:hidden}
.layui-imgbox-img>image{max-width:100%;}
.layui-imgbox-repeat{position: absolute;display: block;width:32rpx;height:32rpx;line-height:28rpx;right: 2px;bottom:2px;color:#999;font-size:30rpx;background:#fff}
.uploadbtn{position:relative;height:200rpx;width:200rpx}
/* 选择桌台 */
.clist-item{display:flex;border-bottom: 1px solid #f5f5f5;padding:20rpx 30rpx;}
.radio{flex-shrink:0;width: 32rpx;height: 32rpx;background: #FFFFFF;border: 2rpx solid #BFBFBF;border-radius: 50%;margin-right:30rpx}
.radio .radio-img{width:100%;height:100%;display:block}
</style>