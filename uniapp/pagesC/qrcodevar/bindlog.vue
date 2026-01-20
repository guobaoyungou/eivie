<template>
<view class="container">
	<block v-if="isload">
		<view class="content" id="datalist">
			<block v-for="(item, index) in datalist" :key="index"> 
        <view class="item">
          <view class="f1">
              <view style="width: 200rpx;height: 200rpx;"><image :src="item.qrcode" @tap="previewImage" :data-url="item.qrcode" style="width: 100%;height: 100%;"></image></view>
              <view class="t2" style="width:470rpx;">
                <view v-if="item.code">随机码：{{item.code}}</view>
                <view>绑定时间：{{item.bindtime}}</view>
               
                <block v-if="canbindsound">
                  <view @tap="openSound" :data-id="item.id" :data-soundid="item.soundid" class="" style="display: flex;justify-content: space-between;align-items: center;margin: 20rpx auto;">
                    <view>绑定云音响：{{item.soundname}}</view>
                    <view style="display:flex;align-items: center;">
                      <text>去绑定</text>
                      <image :src="pre_url+'/static/img/arrowright.png'" style="width: 30rpx;height: 30rpx;"></image>
                    </view>
                  </view>
                </block>
                <view @tap="unbind" :data-id="item.id" class="unbind">
                  解绑
                </view>
              </view>
          </view>
        </view>
      </block>
		<nodata v-if="nodata"></nodata>
		</view>
		<nomore v-if="nomore"></nomore>
	</block>
  <uni-popup id="dialogSound" ref="dialogSound" type="dialog">
  	<view class="uni-popup-dialog">
  		<view class="uni-dialog-title">
  			<text class="uni-dialog-title-text">请选择云音响</text>
  		</view>
  		<view class="uni-dialog-content">
  			<view class="flex-y-center flex-x-center" style="margin:20rpx 20rpx;">
          <view style="font-size:28rpx;color:#555">云音响：</view>
  				<picker @change="soundChange" :value="soundindex" :range="sounds" range-key="name" mode='selector' style="font-size:28rpx;border: 1px #eee solid;padding:10rpx;height:70rpx;border-radius:4px;flex:1;min-width:300rpx ">
  					<view class="picker">{{soundindex>=0?sounds[soundindex]['name']: '请选择'}}</view>
  				</picker>
  			</view>
  		</view>
  		<view class="uni-dialog-button-group">
  			<view class="uni-dialog-button" @click="dialogSoundClose">
  				<text class="uni-dialog-button-text">取消</text>
  			</view>
  			<view class="uni-dialog-button uni-border-left" @click="confirmSound">
  				<text class="uni-dialog-button-text uni-button-color">确定</text>
  			</view>
  		</view>
  	</view>
  </uni-popup>
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
      st: 0,
      datalist: [],
      pagenum: 1,
      nodata: false,
      nomore: false,
      
      
      id:0,
      soundindex:-1,
      canbindsound:false,
      sounds:'',
    };
  },

  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.getdata();
  },
	onPullDownRefresh: function () {
		this.getdata();
	},
  onReachBottom: function () {
    if (!this.nodata && !this.nomore) {
      this.pagenum = this.pagenum + 1;
      this.getdata(true);
    }
  },
  methods: {
    getdata: function (loadmore) {
			if(!loadmore){
				this.pagenum = 1;
				this.datalist = [];
			}
      var that = this;
      var pagenum = that.pagenum;
      var st = that.st;
			that.nodata = false;
			that.nomore = false;
			that.loading = true;
      app.post('ApiAdminIndex/bindqrcodevarlist', {st: st,pagenum: pagenum}, function (res) {
				that.loading = false;
        var data = res.data;
        if (pagenum == 1) {
          if(res.canbindsound){
            that.canbindsound = res.canbindsound;
          }
          if(res.sounds){
            that.sounds = res.sounds;
          }
          console.log(that.sounds)
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
      });
    },
    changetab: function (e) {
      var st = e.currentTarget.dataset.st;
      this.st = st;
      uni.pageScrollTo({
        scrollTop: 0,
        duration: 0
      });
      this.getdata();
    },
    openSound:function(e){
      var that = this;
    	that.id = e.currentTarget.dataset.id;
      that.$refs.dialogSound.open();
    },
    soundChange:function(e){
      console.log(e)
      this.soundindex = e.detail.value;
    },
    dialogSoundClose:function(){
    	this.$refs.dialogSound.close();
    },
    confirmSound:function(){
    	var that = this;
      var soundid = that.soundindex>=0?that.sounds[that.soundindex]['id']:0;
      app.confirm('确定绑定此云音响',function(){
          app.post('ApiAdminIndex/qrcodevarbindsound', {id:that.id,soundid:soundid}, function (res) {
            if(res.status == 1){
              app.success(res.msg);
              that.$refs.dialogSound.close();
              setTimeout(function () {
              	that.getdata();
              }, 1000)
            }else{
              app.alert(res.msg)
            }
          })
      })
    	
    },
    unbind:function(e){
    	var that = this;
      var id = e.currentTarget.dataset.id
      app.confirm('确定解绑此收款码？',function(){
          app.post('ApiAdminIndex/unbindqrcodevar', {id:id}, function (res) {
            if(res.status == 1){
              app.success(res.msg);
              that.$refs.dialogSound.close();
              setTimeout(function () {
              	that.getdata();
              }, 1000)
            }else{
              app.alert(res.msg)
            }
          })
      })
    },
  }
};
</script>
<style>

.content{ width:94%;margin:0 3%;}
.content .item{width:100%;margin:20rpx 0;background:#fff;border-radius:5px;padding:20rpx 20rpx;display:flex;align-items:center}
.content .item .f1{flex:1;display:flex;align-items: center;}
.content .item .f1 .t1{color:#000000;font-size:30rpx;word-break:break-all;overflow:hidden;text-overflow:ellipsis;}
.content .item .f1 .t2{color:#666666}
.content .item .f1 .t3{color:#666666}
.content .item .f2{ flex:1;font-size:36rpx;text-align:right}
.content .item .f2 .t1{color:#03bc01}
.content .item .f2 .t2{color:#000000}
.content .item .f3{ flex:1;font-size:32rpx;text-align:right}
.content .item .f3 .t1{color:#03bc01}
.content .item .f3 .t2{color:#000000}

.data-empty{background:#fff}
.btn-mini {right: 32rpx;top: 28rpx;width: 100rpx;height: 50rpx;text-align: center;border: 1px solid #e6e6e6;border-radius: 10rpx;color: #fff;font-size: 24rpx;font-weight: bold;display: inline-flex;align-items: center;justify-content: center;position: absolute;}

.uni-popup-dialog {width: 300px;border-radius: 5px;background-color: #fff;}
.uni-dialog-title {display: flex;flex-direction: row;justify-content: center;padding-top: 15px;padding-bottom: 5px;}
.uni-dialog-title-text {font-size: 16px;font-weight: 500;}
.uni-dialog-content {display: flex;flex-direction: row;justify-content: center;align-items: center;padding: 5px 5px 15px 5px;}
.uni-dialog-content-text {font-size: 14px;color: #6e6e6e;}
.uni-dialog-button-group {display: flex;flex-direction: row;border-top-color: #f5f5f5;border-top-style: solid;border-top-width: 1px;}
.uni-dialog-button {display: flex;flex: 1;flex-direction: row;justify-content: center;align-items: center;height: 45px;}
.uni-border-left {border-left-color: #f0f0f0;border-left-style: solid;border-left-width: 1px;}
.uni-dialog-button-text {font-size: 14px;}
.uni-button-color {color: #007aff;}

.unbind{width: 100rpx;text-align: center;line-height: 60rpx;border: 2rpx solid #ddd;border-radius: 2rpx;margin-top: 10rpx;}
</style>