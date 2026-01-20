<template>
<view>
	<block v-if="isload">
    <view class="headcontent" :style="bgpic?'background:url('+bgpic+') no-repeat,background-size:100% 100%':'background: linear-gradient(180deg, rgba('+t('color1rgb')+',0.65) 0%, rgba('+t('color1rgb')+',0) 100%);'">
      <view style="width: 710rpx;margin: 0 auto;padding: 20rpx 0;">
        <view style="display: flex;align-items: center;justify-content: space-between;">
          <view @tap.stop="goto" :data-url="canedit?'/pagesB/kecheng/lecturerapply?opttype=1':''" style="display: flex;align-items: center;">
            <view class="headimg">
              <image :src="lecturer.headimg" style="width: 100%;height: 100%;"></image>
            </view>
            <view>
              {{lecturer.nickname}}
            </view>
          </view>
          <view v-if="canedit" @tap.stop="goto" data-url="/pagesB/kecheng/lecturersend" class="send" :style="'background-color:'+t('color1')">
            发布
          </view>
        </view>
        <view style="margin-top: 20rpx;line-height: 40rpx;white-space: pre-wrap;word-wrap: break-word;">{{lecturer.shortdesc}}</view>
      </view>
    </view>
    <view style="background-color: #fff;">
      <view style="width: 400rpx;">
        <dd-tab :itemdata="itemdata" :itemst="itemst" :st="st" :isfixed="false" @changetab="changetab"></dd-tab>
      </view>
    </view>
    <view class="content">
      <view v-for="(item, index) in datalist" :key="index" class="item" :style="index+1>=datalen?'border:0':''" @tap="goto" :data-url="'/pagesB/kecheng/lecturermldetail?kcid='+item.id">
        <view class="item1">
          <view class="title">
            {{item.name}}
          </view>
          <view style="width: 200rpx;height: 140rpx;background-color: #f1f1f1;border-radius: 8rpx;overflow: hidden;">
            <image :src="item.pic" mode="widthFix" style="width: 100%;"></image>
          </view>
        </view>
        <view style="font-size: 24rpx;color: #999;display: flex;align-items: center;">
          <view>{{item.showtime}}</view>
          <view v-if="canedit" @tap.stop="goto" :data-url="'/pagesB/kecheng/lecturersend?id='+item.id" class="btn">编辑</view>
        </view>
      </view>
    </view>
    <nomore v-if="nomore"></nomore>
    <nodata v-if="nodata"></nodata>
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
      datalen:0,
      pagenum: 1,
      nodata:false,
      nomore: false,
      id:0,
      lecturer:'',
      st: 0,
      datalen:0,
      bgpic:'',
      canedit:false,
      itemdata:['文章','视频'],
      itemst:['0','1']
    };
  },
  onLoad: function (opt) {
    this.opt = app.getopts(opt);
    this.id = this.opt.id || 0;
  },
  onShow:function(){
    this.getdata();
  },
  onPullDownRefresh: function () {
  	this.getdata(true);
  },
  onReachBottom: function () {
    if (!this.nodata && !this.nomore) {
      this.pagenum = this.pagenum + 1;
      this.getdata(true);
    }
  },
  methods: {
		getdata: function (loadmore= false) {
      var that = this;
			if(!loadmore){
				that.pagenum = 1;
				that.datalist = [];
			}
		  var pagenum = that.pagenum;
			that.loading = true;
			app.post('ApiKecheng/lecturercenter', {id:that.id,st: that.st,pagenum: pagenum}, function (res) {
				that.loading = false;
        if(res.status == 1){
          if(res.itemdata){
            that.itemdata = res.itemdata
          }
          if(res.itemst){
            that.itemst = res.itemst
          }
          if(res.bgpic){
            that.bgpic = res.bgpic
          }
          if(res.canedit){
            that.canedit = res.canedit
          }
          that.lecturer = res.lecturer;
          var data = res.data;
          if (pagenum == 1) {
          	that.datalist = data;
            if (data.length == 0) {
              that.nodata = true;
            }
          	that.loaded();
          }else{
            if (data.length == 0) {
              that.nomore = true;
            } else {
              var datalist = that.datalist;
              var newdata = datalist.concat(data);
              that.datalist = newdata;
            }
          }
          that.datalen = that.datalist.length;
        } else {
          if (res.msg) {
            app.alert(res.msg, function() {
              if (res.url) app.goto(res.url);
            });
          } else if (res.url) {
            app.goto(res.url);
          } else {
            app.alert('您无查看权限');
          }
        }
			});
		},
    changetab: function (st) {
      var that = this;
      that.st = st;
      uni.pageScrollTo({
        scrollTop: 0,
        duration: 0
      });
      that.nodata= false;
      that.nomore= false;
      that.getdata();
    },
  }
}
</script>
<style>
  .headcontent{width: 100%;padding: 20rpx 0;}
  .content{width: 710rpx;margin: 0 auto;padding: 0 20rpx;background-color: #fff;margin-top:20rpx;border-radius: 8rpx;}
  .content .item{padding: 20rpx;border-bottom: 2rpx solid #f1f1f1;}
  .item1{display: flex;justify-content: space-between;padding: 20rpx 0;}
  .title{width: 440rpx;font-size:30rpx;word-break: break-all;text-overflow: ellipsis;overflow: hidden;display: -webkit-box;-webkit-box-orient: vertical;-webkit-line-clamp: 3;height: 140rpx;line-height: 45rpx;}
  .btn{height:60rpx;line-height:60rpx;color:#333;background:#fff;border-radius:3px;text-align:center;padding: 0 15rpx;flex-shrink: 0;margin: 0 0 0 15rpx;}
  .headimg{width: 100rpx;height: 100rpx;background-color: #f1f1f1;border-radius: 50% 50%;margin-right: 10rpx;overflow: hidden;}
  .send{width: 120rpx;text-align: center;color: #fff;line-height: 60rpx;border-radius: 60rpx;}
</style>