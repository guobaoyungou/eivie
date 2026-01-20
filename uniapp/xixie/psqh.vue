<template>
<view>
    <view class="form-box">
        <view class="form-item flex-col">
            <view class="f1">物品图片</view>
            <view class="f2" style="flex-wrap:wrap">
                <view v-for="(item, index) in pics" :key="index" class="layui-imgbox">
                    <view class="layui-imgbox-close" @tap="removeimg" :data-index="index" data-field="pics"><image style="display:block" src="/static/img/ico-del.png"></image></view>
                    <view class="layui-imgbox-img"><image :src="item" @tap="previewImage" :data-url="item" mode="widthFix"></image></view>
                </view>
                <view class="uploadbtn" :style="'background:url('+pre_url+'/static/img/shaitu_icon.png) no-repeat 60rpx;background-size:80rpx 80rpx;background-color:#F3F3F3;'" @tap="uploadimg" data-field="pics" data-pernum="6" v-if="pics.length<=5"></view>
            </view>
            <input type="text" hidden="true" name="pics" :value="pics.join(',')" maxlength="-1"></input>
        </view>
    </view>
    <button class="savebtn" style="background:linear-gradient(-90deg, #06A051 0%, #03B269 100%)" @tap="postData">确定取货完成</button>
    <view style="height:50rpx"></view>
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
            id:0,
            pics:[]

        };
  },

  onLoad: function (opt) {
		this.opt = app.getopts(opt);
        if(this.opt.id){
            this.id = this.opt.id;
        }
  },
  methods: {
        postData: function () {
            var that = this;
            var id   = that.id ? that.id : '';
            var pics = that.pics? that.pics : '';
            app.confirm('确定取货完成吗', function () {
                app.post('ApiXixie/changeStatus', {id:id,st:2,pics:pics}, function (res) {
                    if (res.status == 1) {
                        app.success(res.msg);
                        setTimeout(function () {
                            app.goback();
                        }, 1000);
                        
                    }else if(res.status==444){
                        app.error(res.msg);
                        setTimeout(function(){
                            app.goto("pslogin",'reLaunch');
                        },500);
                    }else{
                        app.alset(data.msg);
                    }
                });
            });
        },
		uploadimg:function(e){
			var that = this;
			var pernum = parseInt(e.currentTarget.dataset.pernum);
			if(!pernum) pernum = 1;
            var pics = that.pics;
			if(!pics) pics = [];
			app.chooseImage(function(urls){
				for(var i=0;i<urls.length;i++){
					pics.push(urls[i]);
				}
				 that.pics = pics;
			},pernum);
		},
		removeimg:function(e){
			var that = this;
			var index= e.currentTarget.dataset.index
            var pics = that.pics
            pics.splice(index,1);
            that.pics = pics;
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
.savebtn{ width: 90%; height:96rpx; line-height: 96rpx; text-align:center;border-radius:12rpx; color: #fff;font-weight:bold;margin: 0 5%; margin-top:60rpx; border: none; }

.layui-imgbox{margin-right:16rpx;margin-bottom:10rpx;font-size:24rpx;position: relative;}
.layui-imgbox-close{position: absolute;display: block;width:32rpx;height:32rpx;right:-16rpx;top:-16rpx;z-index:5;color:#999;font-size:32rpx;background:#fff;border-radius:50%}
.layui-imgbox-close image{width:100%;height:100%}
.layui-imgbox-img{display: block;width:200rpx;height:200rpx;padding:2px;border: #d3d3d3 1px solid;background-color: #f6f6f6;overflow:hidden}
.layui-imgbox-img>image{max-width:100%;}
.layui-imgbox-repeat{position: absolute;display: block;width:32rpx;height:32rpx;line-height:28rpx;right: 2px;bottom:2px;color:#999;font-size:30rpx;background:#fff}
.uploadbtn{position:relative;height:200rpx;width:200rpx}
</style>