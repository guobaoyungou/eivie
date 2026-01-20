<template>
  <view style="width: 100%;height: 100%;padding-bottom: 60rpx;">
    <block v-if="isload && detail">
      <view :style="'width:100%;background-color:'+detail.color">
        <view class="content_view">
          <view style="width: 540rpx;">
            <view style="font-size: 36rpx;font-weight: bold;line-height: 60rpx;">{{detail.title}} </view>
            <view class="content" style="display: block;">
              <view style="width:100%;white-space: pre-wrap;word-break: break-all;">{{detail.word}}</view>
              <view style="width:100%;white-space: pre-wrap;word-break: break-all;">{{detail.word2}}</view>
            </view>
          </view>
          <view class="right_view">
          	<image :src="detail.logo" style="width: 100%;height: 100%;"></image>
          </view>
        </view>
      </view>
      <view style="width: 680rpx;margin: 0 auto;">
        <block v-if="detail.content && detail.content.length>0">
          <view v-for="(item,index) in detail.content" :key="index" style="margin: 20rpx 0;">
            <view style="line-height: 60rpx;color:#161616;font-size: 36rpx;font-weight: bold;margin: 10rpx 0;">
              {{item.name}}
            </view>
            <view class="bottom_content">
              <view v-if="item.type == 'address'" @tap.stop="openLocation" :data-latitude="detail.latitude" :data-longitude="detail.longitude" :data-company="detail.title" :data-address="item.content">
                <img :src="pre_url+'/static/img/b_addr.png'" style="width:30rpx;height: 30rpx;margin-right: 20rpx;float: left;margin-top: 14rpx;">
                {{item.content}}
              </view>
              <view v-else-if="item.type == 'tel'" @tap.stop="phone" :data-phone="item.content">
                <img :src="pre_url+'/static/img/b_tel.png'" style="width:30rpx;height: 30rpx;margin-right: 20rpx;float: left;margin-top: 14rpx;">
                {{item.content}}
              </view>
              <view v-else>
                <view v-if="item.key=='upload'">
                  <image :src="item.content" style="width:200rpx" mode="widthFix" @tap="previewImage" :data-url="item.content"></image>
                </view>
                <!-- #ifdef !H5 && !MP-WEIXIN -->
                <view v-else-if="item.key=='upload_file'" style="overflow: hidden;white-space: pre-wrap;word-wrap: break-word;color: #4786BC;">
                		{{item.content}}
                </view>
                <!-- #endif -->
                <!-- #ifdef H5 || MP-WEIXIN -->
                <view v-else-if="item.key=='upload_file'"  @tap="download" :data-file="item.content" style="overflow: hidden;white-space: pre-wrap;word-wrap: break-word;color: #4786BC;">
                		点击下载查看
                </view>
                <view v-else-if="item.key=='upload_video'" >
                		<video :src="item.content"  style="width:80%;height:300rpx;margin-top:20rpx"></video>
                </view>
                <!-- #endif -->
                <view v-else-if="item.key=='upload_pics'">
                  <block v-for="(item2,index2) in item.content" :key="index2">
                    <image :src="item2" style="width:200rpx;margin-right: 10rpx;" mode="widthFix" @tap="previewImage" :data-url="item2"></image>
                  </block>
                </view>
                <view v-else>
                  {{item.content}}
                </view>
              </view>
            </view>
          </view>
        </block>
      </view>
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
  
      id: 0,
      longitude: '',
      latitude: '',
      detail: '',
    };
  },

  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.id = this.opt.id;
		this.getdata();
  },
  methods: {
		getdata: function () {
      var that = this;
			that.loading = true;
      app.post('ApiForm/formdata_detail', {id: that.id}, function (res) {
        that.loading = false;
        that.loaded();
        if(res.status == 1){
          that.detail = res.data;
        }else{
          app.alert(res.msg)
        }
      });
    },
		openLocation:function(e){
			var latitude  = parseFloat(e.currentTarget.dataset.latitude)
			var longitude = parseFloat(e.currentTarget.dataset.longitude)
			var address   = e.currentTarget.dataset.address
			if(latitude && longitude){
			  uni.openLocation({
			     latitude:latitude,
			     longitude:longitude,
			     name:address,
			     scale: 13
			  })	
			}	
		},
		phone:function(e) {
			var phone = e.currentTarget.dataset.phone;
			uni.makePhoneCall({
				phoneNumber: phone,
				fail: function () {
				}
			});
		},
    download:function(e){
        var that = this;
        var file = e.currentTarget.dataset.file;
        // #ifdef H5
            window.location.href= file;
        // #endif
        
        // #ifdef MP-WEIXIN
        uni.downloadFile({
        	url: file, 
        	success: (res) => {
                var filePath = res.tempFilePath;
        		if (res.statusCode === 200) {
        			uni.openDocument({
                      filePath: filePath,
                      showMenu: true,
                      success: function (res) {
                        console.log('打开文档成功');
                      }
                    });
        		}
        	}
        });
        // #endif
    },
  }
};
</script>
<style>
  page{width: 100%;height: auto;background-color: #fff !important;}
  .content_view{width: 680rpx;margin: 0 auto;padding: 50rpx 0;justify-content: space-between;color: #fff;display: flex;}
  .content{font-size: 24rpx;display: flex;display: flex;line-height: 50rpx;align-items: center;}
  .dot{width: 4rpx;height: 4rpx;border-radius: 50%;background-color: #fff;margin: 0 20rpx;}
  .right_view{width: 120rpx;height: 120rpx;background:#f1f1f1;border-radius: 12rpx;text-align: center;line-height: 120rpx;color: #666;overflow: hidden;}
  .bottom_content{line-height: 60rpx;color:#363636;font-size: 28rpx;margin: 10rpx 0;align-items: center;word-break: break-all;}
</style>