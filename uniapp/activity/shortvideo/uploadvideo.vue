<template>
<view>
	<block v-if="isload">
		<form @submit="formsubmit">
		<view class="st_box">
			<view class="st_form">
				<view v-if="cateArr">
					<picker @change="cateChange" :value="cindex" :range="cateArr" style="height:80rpx;line-height:80rpx;border-bottom:1px solid #EEEEEE;font-size:18px">
						<view class="picker">{{cindex==-1? '请选择类型' : cateArr[cindex]}}</view>
					</picker>
				</view>
				<!-- <view v-if="cateArr">
					<picker @change="cateChange" :value="cindex" :range="cateArr" style="height:80rpx;line-height:80rpx;border-bottom:1px solid #EEEEEE;font-size:18px">
						<view class="picker">{{cindex==-1? '请选择曝光标签' : cateArr[cindex]}}</view>
					</picker>
				</view> -->
				<view><input placeholder="输入标题" name="title" maxlength="-1"/></view>
				<view><textarea placeholder="输入简介" name="content" maxlength="-1" style="height:100rpx;"></textarea></view>
				<view class="uploadbtn_ziti1">上传封面</view>
				<view class="flex" style="flex-wrap:wrap;padding-top:20rpx;">
					<view v-for="(item, index) in pics" :key="index" class="layui-imgbox">
						<view class="layui-imgbox-close" @tap="removeimg" :data-index="index" data-field="pics"><image :src="pre_url+'/static/img/ico-del.png'"></image></view>
						<view class="layui-imgbox-img"><image :src="item" @tap="previewImage" :data-url="item" mode="widthFix"></image></view>
						<!-- <view class="layui-imgbox-repeat" @tap="xuanzhuan" :data-index="index" data-field="pics"><text class="fa fa-repeat"></text></view> -->
					</view>
					<view class="uploadbtn" :style="'background:url('+pre_url+'/static/img/shaitu_icon.png) no-repeat 60rpx;background-size:80rpx 80rpx;background-color:#F3F3F3;'" @tap="uploadimg" data-field="pics" v-if="pics.length<1">
						
					</view>
					
				</view>
				
				<input type="text" hidden="true" name="pics" :value="pics.join(',')" maxlength="-1"></input>
			
			<view class="uploadbtn_ziti2">上传短视频</view>
				<view class="flex-y-center" style="width:100%;padding:20rpx 0;margin-top:20rpx;">
					<image :src="pre_url+'/static/img/uploadvideo.png'" style="width:200rpx;height:200rpx;background:#eee;" @tap="uploadvideo"></image><text v-if="video" style="padding-left:20rpx;color:#333">已上传短视频</text></view>
				<input type="text" hidden="true" name="video" :value="video" maxlength="-1"></input>
			</view>
		</view>
		<view class="st_button flex-y-center">
			<button form-type="submit" :style="{background:t('color1')}">发表</button>
		</view>
		<view style="width:100%;margin-top:40rpx;text-align:center;color:#999;display:flex;align-items:center;justify-content:center" @tap="goto" data-url="myupload">我的发表记录<image :src="pre_url+'/static/img/arrowright.png'" style="width:30rpx;height:30rpx"/></view>
		<view style="width:100%;height:60rpx"></view>
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
      datalist: [],
      content_pic: [],
      pagenum: 1,
      cateArr: [],
      cindex: -1,
			pics:[],
      video: '',
			sysset:{},
			bid:0,
    };
  },

  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.bid = this.opt.bid || 0;
		this.getdata();		
  },
	onPullDownRefresh: function () {
		this.getdata();
	},
  methods: {
		getdata: function () {
			var that = this;
			that.loading = true;
			app.get('ApiShortvideo/uploadvideo', {bid:that.bid}, function (res) {
				that.loading = false;
				if (res.status == 0) {
					app.alert(res.msg);
					return;
				}
				that.clist = res.clist;
				that.sysset = res.sysset;
				var clist = res.clist;
				if (clist.length > 0) {
					var cateArr = [];
					for (var i in clist) {
						if (that.opt && that.opt.cid == clist[i].id) {
							that.cindex = i;
						}
						cateArr.push(clist[i].name);
					}
				} else {
					cateArr = false;
				}
				that.cateArr = cateArr
				that.loaded();
			});
		},
    cateChange: function (e) {
      this.cindex = e.detail.value;
    },
    formsubmit: function (e) {
      var that = this;
      console.log(e);
      var clist = that.clist;
      if (clist.length > 0) {
        if (that.cindex == -1) {
          app.error('请选择分类');
          return false;
        }
        var cid = clist[that.cindex].id;
      } else {
        var cid = 0;
      }
      var formdata = e.detail.value;
      var title = formdata.title;
			if (title == '') {
        app.error('请输入标题');
        return false;
      }
      var content = formdata.content;
      var pics = formdata.pics;
      var video = formdata.video;
      if (pics == '') {
        app.error('请上传封面');
        return false;
      }
      if (video == '') {
        app.error('请上传短视频');
        return false;
      }
      app.post('ApiShortvideo/uploadvideo', {bid:that.bid,title:title,cid: cid,pics: pics,content: content,video: video}, function (res) {
        app.showLoading(false);
        if (res.status == 1) {
          app.success(res.msg);
		  that.pics.push(res.ffmpeg_img);
          setTimeout(function () {
            app.goback(true);
          }, 1000);
        } else {
          app.error(res.msg);
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
    uploadvideo: function () {
      var that = this;
      //var maxDuration = that.sysset.upload_maxduration;
			//if(!maxDuration) maxDuration = 999999;
      var maxsize = that.sysset.upload_maxsize;
			if(!maxsize) maxsize = 999999999999999;
      uni.chooseVideo({
        sourceType: ['album', 'camera'],
        //maxDuration: maxDuration,
        success: function (res) {
          var tempFilePath = res.tempFilePath;
					var size = res.size;
					if(size > maxsize * 1024){
						app.alert('短视频文件过大');return;
					}
					//console.log(size);return;
          app.showLoading('上传中');
          uni.uploadFile({
            url: app.globalData.baseurl + 'ApiImageupload/uploadImg/aid/' + app.globalData.aid + '/platform/' + app.globalData.platform + '/session_id/' + app.globalData.session_id + '/type/1',
            filePath: tempFilePath,
            name: 'file',
            success: function (res) {
                console.log(res)
              app.showLoading(false);
              var data = JSON.parse(res.data);

              if (data.status == 1) {
                that.video = data.url;

                if(data.ffmpeg_img){
                    var pics = []
                    pics.push(data.ffmpeg_img);
                    that.pics = pics;
                }

              } else {
                app.alert(data.msg);
              }
            },
            fail: function (res) {
              app.showLoading(false);
              app.alert(res.errMsg);
            }
          });
        },
        fail: function (res) {
          console.log(res); //alert(res.errMsg);
        }
      });
    },
		removeimg:function(e){
			var that = this;
			var index= e.currentTarget.dataset.index
			var field= e.currentTarget.dataset.field
			var pics = that[field]
			pics.splice(index,1)
		},
  }
};
</script>
<style>
page{background:#f7f7f7}
.st_box{ padding:0rpx 0 }
.st_button{ display: flex; justify-content: space-between;padding:24rpx 24rpx 10rpx 24rpx;}
.st_button button{background: #1658c6;border-radius:6rpx;border: none;padding:0 20rpx;color: #fff;font-size:36rpx;text-align: center;width:100%;display: flex;height:100rpx;justify-content: center;align-items: center;}

.st_form{ padding: 24rpx;background: #ffffff;margin: 10px;border-radius: 15px;}
.st_form input{ width: 100%;height: 120rpx; border: none;border-bottom:1px solid #EEEEEE;}
.st_form input::-webkit-input-placeholder { /* WebKit browsers */ color:    #BBBBBB; font-size: 24rpx}
.st_form textarea{ width:100%;min-height:200rpx;padding:20rpx 0;border: none;border-bottom:1px solid #EEEEEE;}

.layui-imgbox{margin-right:16rpx;margin-bottom:10rpx;font-size:24rpx;position: relative;}
.layui-imgbox-img{display: block;width:200rpx;height:200rpx;padding:2px;border: #d3d3d3 1px solid;background-color: #f6f6f6;overflow:hidden}
.layui-imgbox-img>image{max-width:100%;}
.layui-imgbox-repeat{position: absolute;display: block;width:32rpx;height:32rpx;line-height:28rpx;right: 2px;bottom:2px;color:#999;font-size:30rpx;background:#fff}
.uploadbtn{position:relative;height:200rpx;width:200rpx}
.uploadbtn_ziti1{height:30rpx; line-height: 30rpx;font-size:30rpx; margin-top: 20rpx;}
.uploadbtn_ziti2{height:30rpx; line-height: 30rpx;font-size:30rpx; padding-top: 20rpx; margin-top: 20rpx;border-top:1px solid #EEEEEE;}
</style>