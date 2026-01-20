<template>
<view>
	<block v-if="isload">
		<form @submit="subform">
			<view class="form-box">
				<view class="form-item">
					<view class="f1">商家商品分类<text style="color:red"> *</text></view>
					<view class="f2" @tap="changeClist2Dialog"><text v-if="cid>0">{{cname}}</text><text v-else style="color:#888">顶级分类</text><image :src="pre_url+'/static/img/arrowright.png'" style="width:30rpx;height:30rpx"/></view>
				</view>
				<view class="form-item">
					<view class="f1">分类名称<text style="color:red"> *</text></view>
					<view class="f2"><input type="text" name="name" :value="info.name" placeholder="请填写名称" placeholder-style="color:#888"></input></view>
				</view>	
				<view class="form-item flex-col" style="border-bottom:0">
					<view class="f1">图片</view>
					<view class="f2">
						<view v-for="(item, index) in pic" :key="index" class="layui-imgbox">
							<view class="layui-imgbox-close" @tap="removeimg" :data-index="index" data-field="pic"><image style="display:block" :src="pre_url+'/static/img/ico-del.png'"></image></view>
							<view class="layui-imgbox-img"><image :src="item" @tap="previewImage" :data-url="item" mode="widthFix"></image></view>
						</view>
						<view class="uploadbtn" :style="'background:url('+pre_url+'/static/img/shaitu_icon.png) no-repeat 60rpx;background-size:80rpx 80rpx;background-color:#F3F3F3;'" @tap="uploadimg" data-field="pic" data-pernum="1" v-if="pic.length==0"></view>
					</view>
					<input type="text" hidden="true" name="pic" :value="pic.join(',')" maxlength="-1"/>
				</view>

				<view class="form-item">
					<view class="f1">排序</view>
					<view class="f2"><input type="text" name="sort" :value="info.sort" placeholder="用于排序,越大越靠前" placeholder-style="color:#888"></input></view>
				</view>
				<view class="form-item">
					<view>状态<text style="color:red"> *</text></view>
					<view>
						<radio-group class="radio-group" name="status" @change="">
							<label><radio value="1" :checked="info.status==1?true:false"></radio> 显示</label> 
							<label><radio value="0" :checked="!info || info.status==0?true:false"></radio> 隐藏</label>
						</radio-group>
					</view>
				</view>
			</view>


			<button class="savebtn" :style="'background:linear-gradient(90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'" form-type="submit">提交</button>
			<button class="button text-btn" @tap="todel" v-if="info.id">删除</button>
			<view style="height:50rpx"></view>
		</form>
		
		<view class="popup__container" v-if="clist2show">
			<view class="popup__overlay" @tap.stop="changeClist2Dialog"></view>
			<view class="popup__modal">
				<view class="popup__title">
					<text class="popup__title-text">请选择分类</text>
					<image :src="pre_url+'/static/img/close.png'" class="popup__close" style="width:36rpx;height:36rpx" @tap.stop="changeClist2Dialog"/>
				</view>
				<view class="popup__content">
					<block v-for="(item, index) in clist2">
						<view class="clist-item" @tap="cids2Change" :data-id="item.id">
							<view class="flex1">{{item.name}}</view>
							<view class="radio" :style="item.id==cid ? 'background:'+t('color1')+';border:0' : ''"><image class="radio-img" :src="pre_url+'/static/img/checkd.png'"/></view>
						</view>
						<block v-for="(item2, index2) in item.child" :key="item2.id">
							<view class="clist-item" style="padding-left:80rpx" @tap="cids2Change" :data-id="item2.id">
								<view class="flex1" v-if="item.child.length-1==index2">└ {{item2.name}}</view>
								<view class="flex1" v-else>├ {{item2.name}}</view>
								<view class="radio" :style="item2.id==cid ? 'background:'+t('color1')+';border:0' : ''"><image class="radio-img" :src="pre_url+'/static/img/checkd.png'"/></view>
							</view>
						</block>
					</block>
				</view>
			</view>
		</view>

	</block>
	<loading v-if="loading"></loading>
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
			  clist2show:false,
			  clist2:[],
			  cateArr2:[],
			  cid:'',
			  cname:'',
			  subStatus : false,
		}
  },

  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.getdata();
  },
  methods: {
		getdata:function(){
			var that = this;
			var id = that.opt.id ? that.opt.id : '';
			that.loading = true;
			app.get('ApiAdminProductCategory2/edit',{id:id}, function (res) {
				that.loading = false;
				that.info = res.info;
				that.pic = res.pic;
				that.clist2 = res.clist2;
				that.cateArr2 = res.cateArr2;
				that.cname = res.cname;
				that.cid = res.info.pid;

				that.loaded();
			});
		},
    subform: function (e) {
      var that = this;
      var formdata = e.detail.value;
	  if(formdata.name.length == 0){
	  	app.alert('请填写名称');
	  	return;
	  }
	  if(that.subStatus){
	  	app.alert('请勿重复提交');
	  	return;
	  }
	  formdata.pid = that.cid;
	  that.subStatus = true
      var id = that.opt.id ? that.opt.id : '';
      app.post('ApiAdminProductCategory2/save', {id:id,info:formdata}, function (res) {
		  that.subStatus = false
        if (res.status == 0) {
          app.error(res.msg);
        } else {
          app.success(res.msg);
          setTimeout(function () {
            app.goto('index', 'redirect');
          }, 1000);
        }
      });
    },
	changeClist2Dialog:function(){
		this.clist2show = !this.clist2show
	},
	cids2Change:function(e){
		var clist = this.clist2;
		var cids = this.cids2;
		var cateArr = this.cateArr2;
		var cid = e.currentTarget.dataset.id;		
		var cname = cateArr[cid];
		this.cid = cid;
		this.cname = cname;
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
		}
	},
		todel: function (e) {
		  var that = this;
		  var id = that.opt.id ? that.opt.id : '';
		  app.confirm('确定要删除吗?', function () {
		    app.post('ApiAdminProductCategory2/del', {id: id}, function (res) {
		      if (res.status == 1) {
		        app.success(res.msg);
		        app.goback(true)
		      } else {
		        app.error(res.msg);
		      }
		    });
		  });
		},
		bindStatusChange:function(e){
			this.info.status = e.detail.value;
		},
  }
};
</script>
<style>
radio{transform: scale(0.6);}
checkbox{transform: scale(0.6);}
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


.clist-item{display:flex;border-bottom: 1px solid #f5f5f5;padding:20rpx 30rpx;}
.radio{flex-shrink:0;width: 32rpx;height: 32rpx;background: #FFFFFF;border: 2rpx solid #BFBFBF;border-radius: 50%;margin-right:30rpx}
.radio .radio-img{width:100%;height:100%;display:block}

.uploadbtn{position:relative;height:200rpx;width:200rpx}
</style>