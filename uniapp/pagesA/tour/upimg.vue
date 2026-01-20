<template>
  <view>
    <block v-if="isload">
      <view style="width: 700rpx;margin: 0 auto;">
        <view class="form-item flex-col">
        	<view class="f2" style="flex-wrap:wrap">
        		<view v-for="(item, index) in pics" :key="index" class="layui-imgbox">
        			<view class="layui-imgbox-close" @tap="removeimg" :data-index="index" data-field="pics"><image style="display:block" :src="pre_url+'/static/img/ico-del.png'"></image></view>
        			<view class="layui-imgbox-img"><image :src="item" @tap="previewImage" :data-url="item" mode="widthFix"></image></view>
        		</view>
        		<view v-if="pics.length<num" @tap="uploadimg" data-field="pics" data-pernum="20" class="uploadbtn" :style="'background:url('+pre_url+'/static/img/shaitu_icon.png) no-repeat 60rpx;background-size:80rpx 80rpx;background-color:#F3F3F3;'" ></view>
        	</view>
        	<input type="text" hidden="true" name="pics" :value="pics?pics.join(','):''" maxlength="-1"></input>
        </view>
        <view v-if="tip" style="line-height: 50rpx;font-size: 26rpx;margin-top: 10rpx;color: #888;">
          <text style="color: red;">*</text>
          {{tip}}
        </view>
      </view>
      <button @tap="upimg" class="savebtn" :style="'background:linear-gradient(90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'">提交</button>
    </block>
    <dp-tabbar :opt="opt"></dp-tabbar>
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

        orderid:0,
        pics:[],
        num:0,
        tip:'',
    };
  },

  onLoad: function (opt) {
    var that = this;
    that.opt = app.getopts(opt);
    that.orderid    = that.opt.orderid?that.opt.orderid:0;
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
      app.get('ApiTour/upimg', {orderid:that.orderid}, function (res) {
      	if (res.status == 1) {
          var data = res.data;
          that.num = data.num;
          that.tip = data.tip;
          that.loaded();
      	}else{
      		app.alert(res.msg);
      		return;
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
    upimg: function () {
    	var that = this;
      app.confirm('确定上传图片吗?', function () {
      	app.showLoading('提交中，请耐心等待相册生成');
        app.post('ApiTour/upimg', {orderid:that.orderid,pics:that.pics}, function (res) {
          app.showLoading(false);
          if (res.status == 1) {
            app.success(res.msg);
            setTimeout(function(){
              app.goback();
            },800)
            return
          }else{
            app.alert(res.msg);
            return;
          }
        });
      });
    }
  }
};
</script>
<style>
page{width: 100%;}
.form-item{ line-height: 100rpx; display: flex;justify-content: space-between;margin-top:40rpx}
.form-item .f1{color:#222;width:200rpx;flex-shrink:0}
.form-item .f2{display:flex;align-items:center}
.form-item .upload_pic{ margin:50rpx 0;background: #F3F3F3;width:90rpx;height:90rpx; text-align: center  }
.form-item .upload_pic image{ width: 32rpx;height: 32rpx; }

.layui-imgbox{margin-right:16rpx;margin-bottom:10rpx;font-size:24rpx;position: relative;}
.layui-imgbox-img{display: block;width:200rpx;height:200rpx;padding:2px;border: #d3d3d3 1px solid;background-color: #f6f6f6;overflow:hidden}
.layui-imgbox-img>image{max-width:100%;}
.layui-imgbox-repeat{position: absolute;display: block;width:32rpx;height:32rpx;line-height:28rpx;right: 2px;bottom:2px;color:#999;font-size:30rpx;background:#fff}
.uploadbtn{position:relative;height:200rpx;width:200rpx;border: 2rpx dashed #ccc;}

.deal_css{width: 80rpx;height: 80rpx;border-radius: 50%;background-color: #fff;box-shadow: 2px 2px 10px #888888;line-height: 80rpx;text-align: center;}
.savebtn{ width: 90%; height:96rpx; line-height: 96rpx; text-align:center;border-radius:48rpx; color: #fff;font-weight:bold;margin: 0 5%; margin-top:60rpx; border: none; }
</style>