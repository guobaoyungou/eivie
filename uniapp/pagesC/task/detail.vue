<template>
	<view>
		<block v-if="isload">
      <view class="form-box">
        <view class="form-item flex-col" :style="substatus==-1?'border:0':''">
        	<view class="f1">详情</view>
        	<view class="f2">
            <parse :content="task.content" @navigate="navigate"></parse>
          </view>
        </view>
      	<view v-if="substatus>=0" class="form-item flex-col" style="border: 0;">
      		<view class="f1">上传凭证</view>
      		<view class="f2" style="flex-wrap:wrap">
      			<view v-for="(item, index) in pics" :key="index" class="layui-imgbox">
      				<view class="layui-imgbox-close" v-if="substatus" @tap="removeimg" :data-index="index" data-field="pics"><image style="display:block" :src="pre_url+'/static/img/ico-del.png'"></image></view>
      				<view class="layui-imgbox-img"><image :src="item" @tap="previewImage" :data-url="item" mode="widthFix"></image></view>
      			</view>
      			<view class="uploadbtn" :style="'background:url('+pre_url+'/static/img/shaitu_icon.png) no-repeat 60rpx;background-size:80rpx 80rpx;background-color:#F3F3F3;'" @tap="uploadimg" data-field="pics" data-pernum="9" v-if="substatus && pics.length<picnum"></view>
      		</view>
      		<input type="text" hidden="true" name="pics" :value="pics.join(',')" maxlength="-1"></input>
      	</view>
        <block v-if="log">
          <view class="form-item" style="border: 0;">
            <view class="f1">审核状态</view>
            <view class="f2">
              <view v-if="log.status == -1" style="color: red;">
                <view>已驳回</view>
              </view>
              <view v-if="log.status == 0">审核中</view>
              <view v-if="log.status == 1" style="color: green;">已通过</view>
            </view>
          </view>
          <view v-if="log.status == -1 && log.checkreason" style="color: red;">
            驳回原因：{{log.checkreason}}
          </view>
        </block>
        <button v-if="substatus==1" @tap="submit" class="savebtn" :style="'background-color:'+t('color1')">提交</button>
        <view style="height:20rpx"></view>
      </view>
      <view style="height:40rpx"></view>
		</block>
		<popmsg ref="popmsg"></popmsg>
		<loading v-if="loading"></loading>
	</view>
</template>

<script>
	const app = getApp();
	export default {
		data(){
			return{
				opt:{},
				loading:false,
				isload:false,
				pre_url:app.globalData.pre_url,
        
        id:0,
        task:'',
        detail:'',
        log:'',
        pics:[],
        picnum:6,
        
        substatus:-1,
        type:0,
        detailid:0,
			}
		},
		onLoad(opt){
			this.opt = app.getopts(opt);
      this.id = this.opt.id || 0;
      this.type = this.opt.type || 0;
      this.detailid = this.opt.detailid || 0;
			this.getdata();
		},
		methods:{
			// 页面信息
			getdata: function () {
			  var that = this;
				app.get('ApiTask/detail', {id:that.id,type:that.type,detailid:that.detailid}, function (res) {
					that.loading = false;
          if(res.status == 1){
            that.picnum = res.picnum;
            uni.setNavigationBarTitle({
            	title: res.detail.name
            });
            that.task   = res.task;
            that.detail = res.detail;
            that.log    = res.log;
            
            that.substatus = res.substatus;
            that.pics   = res.log && res.log.pics || [];
            that.loaded();
          }else{
            app.alert(res.msg)
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
      },
      submit: function () {
        var that = this;
        app.confirm('确定提交？',function(){
          app.post('ApiTask/detail', {id:that.id,type:that.type,detailid:that.detailid,pics:that.pics}, function (res) {
            that.loading = false;
            if(res.status == 1){
              app.success(res.msg)
              that.substatus = false;
              if(that.log){
                that.log['status'] = 0;
              }else{
                that.log = {status:0};
              }
            }else{
              app.alert(res.msg)
            }
          });
        })
      },
    }
	}
</script>

<style>
  .form-box{ padding:2rpx 24rpx 0 24rpx; background: #fff;width:710rpx;margin: 0rpx auto;border-radius: 10rpx}
  .form-item{ line-height: 100rpx; display: flex;justify-content: space-between;border-bottom:1px solid #eee }
  .form-item .f1{color:#222;width:200rpx;flex-shrink:0;font-weight: bold;font-size: 30rpx;}
  .form-item .f2{display:flex;align-items:center}
  .form-box .form-item:last-child{ border:none}
  .form-box .flex-col{padding-bottom:20rpx}
  .form-item input{ width: 100%; border: none;color:#111;font-size:28rpx; text-align: right}
  .form-item textarea{ width:100%;min-height:200rpx;padding:20rpx 0;border: none;}
  .form-item .upload_pic{ margin:50rpx 0;background: #F3F3F3;width:90rpx;height:90rpx; text-align: center  }
  .form-item .upload_pic image{ width: 32rpx;height: 32rpx; }
  
  .layui-imgbox{margin-right:16rpx;margin-bottom:10rpx;font-size:24rpx;position: relative;}
  .layui-imgbox-img{display: block;width:200rpx;height:200rpx;padding:2px;border: #d3d3d3 1px solid;background-color: #f6f6f6;overflow:hidden}
  .layui-imgbox-img>image{max-width:100%;}
  .layui-imgbox-repeat{position: absolute;display: block;width:32rpx;height:32rpx;line-height:28rpx;right: 2px;bottom:2px;color:#999;font-size:30rpx;background:#fff}
  .uploadbtn{position:relative;height:200rpx;width:200rpx}
  
  .savebtn{ width: 90%; height:96rpx; line-height: 96rpx; text-align:center;border-radius:48rpx; color: #fff;font-weight:bold;margin: 0 5%; margin-top:60rpx; border: none; }
</style>
