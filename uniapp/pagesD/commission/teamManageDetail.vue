<template>
  <view class="container" style="width: 720rpx;margin: 0 auto;">
    <block v-if="isload">
      <view style="border-radius: 8rpx;overflow: hidden;">
        
        <view style="padding: 20rpx;color:#fff;" :style="'background-color:'+t('color1')">
          <view style="margin: 20rpx 0;display: flex;align-items: center;">
            <view style="width: 130rpx;height: 130rpx;overflow: hidden;border-radius: 6rpx;background-color: #f1f1f1;">
              <image :src="userinfo.headimg" style="width: 100%;height: 100%;"></image>
            </view>
            <view style="margin-left: 10rpx;">
              <view> {{userinfo.nickname}}</view>
              <view class="head-item2"> 
                <view class="head-item2-level" :style="'background-color:'+t('color2')">{{userinfo.levelname}}</view>
              </view>
              <view style="margin-top: 6rpx;">ID：{{userinfo.id}}</view>
            </view>
          </view>
          <view class="head-otherinfo" >
            <view class="head-otherinfo2"></view>
            <view class="head-otherinfo3">加入时间：{{userinfo.createtime}}</view>
          </view>
          <view v-if="userinfo.tel" @tap="callphone" :data-phone="userinfo.tel" class="head-otherinfo" style="width: 300rpx;margin-top: 20rpx;">
            <view class="head-otherinfo2"></view>
            <view class="head-otherinfo3">电话：{{userinfo.tel}}</view>
          </view>
        </view>
        <view style="padding: 20rpx;background-color: #fff;">
          <view class="teamcount">
            <view style="width: 32%;">
              <view style="font-size: 30rpx;font-weight: bold;">￥{{userinfo.commission1}}</view>
            </view>
            <view style="color:#fff ;">|</view>
            <view style="width: 32%;">
              <view style="font-size: 30rpx;font-weight: bold;">￥{{userinfo.commission0}}</view>
            </view>
            <view style="color:#fff ;">|</view>
            <view style="width: 32%;">
              <view style="font-size: 30rpx;font-weight: bold;">￥{{userinfo.commission2}}</view>
            </view>
          </view>
          <view class="teamcount" style="color: #A4A4A4;border-bottom: 2rpx solid #f1f1f1;padding-bottom: 20rpx;">
            <view style="width: 32%;">
              <view>已结算提成</view>
            </view>
            <view>|</view>
            <view style="width: 32%;">
              <view>未结算提成</view>
            </view>
            <view>|</view>
            <view style="width: 32%;">
              <view>总提成</view>
            </view>
          </view>
        </view>
        <view style="padding:0 30rpx;background-color: #fff;">
          <view style="line-height: 70rpx;display: flex;justify-content: space-between;">
            <view>完成订单数</view>
            <view>{{userinfo.orderEndnum}}</view>
          </view>
          <view style="line-height: 70rpx;display: flex;justify-content: space-between;">
            <view>订单总金额</view>
            <view>￥{{userinfo.orderTotalprice}}</view>
          </view>
        </view>
      </view>

      <view style="padding: 20rpx 0;background-color: #fff;margin-top: 20rpx;border-radius: 6rpx;">
        <view style="border-bottom: 2rpx solid #f1f1f1;padding-bottom: 20rpx;">
          <view class="teamcount">
            <view style="width: 50%;">
              <view style="font-size: 30rpx;font-weight: bold;">{{userinfo.childnum}}</view>
            </view>
            <view style="width: 50%;">
              <view style="font-size: 30rpx;font-weight: bold;">{{userinfo.childnum2}}</view>
            </view>
          </view>
          <view class="teamcount" style="color: #A4A4A4;">
            <view style="width: 50%;">
              <view>直属会员人数</view>
            </view>
            <view>|</view>
            <view style="width: 50%;">
              <view>总团队人数</view>
            </view>
          </view>
        </view>
        <view>
          <view class="teamcount">
            <view style="width: 50%;">
              <view style="font-size: 30rpx;font-weight: bold;">￥{{userinfo.childOrderpirce}}</view>
            </view>
            <view style="width: 50%;">
              <view style="font-size: 30rpx;font-weight: bold;">￥{{userinfo.childOrderpirce2}}</view>
            </view>
          </view>
          <view class="teamcount" style="color: #A4A4A4;">
            <view style="width: 50%;">
              <view>直属团队订单额</view>
            </view>
            <view>|</view>
            <view style="width: 50%;">
              <view>总团队订单额</view>
            </view>
          </view>
        </view>
      </view>

      <view style="font-weight: bold;font-size: 30rpx;line-height: 80rpx;">他的团队</view>
      <view style="width: 730rpx;margin: 0rpx auto;border-radius: 6rpx;">
        <view v-for="(item, index) in datalist" :key="index" style="background-color: #fff;padding: 20rpx 0;margin-top: 20rpx;"> 
          <view @tap="goto" :data-url="'teamManageDetail?mid='+item.id" style="margin: 20rpx 0;display: flex;padding:0 20rpx;">
            <view>
              <view style="width: 130rpx;height: 130rpx;overflow: hidden;border-radius: 6rpx;background-color: #f1f1f1;">
                <image :src="item.headimg" style="width: 100%;height: 100%;"></image>
              </view>
              <view style="line-height: 40rpx;font-size: 26rpx;text-align: center;" :style="'color:'+t('color2')">(ID:{{item.id}})</view>
            </view>
            
            <view style="margin-left: 16rpx;">
              <view style=""> {{item.nickname}}</view>
              <view class="head-item2"> 
                <view class="head-item2-level" :style="'background-color:'+t('color2')">{{item.levelname}}</view>
              </view>
              <view style="font-size: 26rpx;margin-top: 6rpx;color: #797979;">加入时间： {{item.createtime}}</view>
              <view v-if="item.tel" @tap.stop="callphone" :data-phone="item.tel" style="font-size: 26rpx;margin-top: 6rpx;"><text style="color: #797979;">电话：</text><text style="color: #1E9FFF;">{{item.tel}}</text></view>
            </view>
          </view>
        </view>
        <nodata v-if="nodata"></nodata>
        <nomore v-if="nomore"></nomore>
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
      nodata: false,
      nomore: false,
      pre_url:app.globalData.pre_url,
      
      mid:0,
      userinfo:{},
      datalist: [],
      pagenum: 1,
      
      teamname:'',
      teamid:0,
      checkLevelid: 0,
      checkLevelname: '',
      levelDialogShow: false,
      allLevel:{},
    };
  },

  onLoad: function (opt) {
    this.opt = app.getopts(opt);
    this.mid = this.opt.mid || 0;
    this.getdata();
  },
  onPullDownRefresh: function () {
    this.pagenum = 1;
    this.datalist = [];
    this.getdata();
  },
  onReachBottom: function () {
    if (!this.nodata && !this.nomore) {
      this.pagenum = this.pagenum + 1;
      this.getdatalist(true);
    }
  },
  methods: {
    getdata: function (loadmore) {
      var that = this;
      var st = that.st;
			var pagenum = that.pagenum;
      that.loading = true;
      that.nodata = false;
      that.nomore = false;
      app.get('ApiAgent/teamManage',{mid:that.mid},function(res){
        that.loading = false;
        if(res.status == 1){
            that.userinfo = res.userinfo;
            that.getdatalist();
        }else{
          app.alert(res.msg)
        }
      });
    },
    getdatalist: function (loadmore) {
      if(!loadmore){
      	this.pagenum = 1;
      	this.datalist = [];
      }
      var that = this;
      var st = that.st;
    	var pagenum = that.pagenum;
      that.loading = true;
      that.nodata = false;
      that.nomore = false;
      app.post('ApiAgent/teamManage',{
        st:st,
        mid:that.mid,
        pagenum: pagenum,
        teamname:that.teamname,
        teamid:that.teamid,
        checkLevelid:that.checkLevelid
      },function(res){
        that.loading = false;
        if(res.status == 1){
          var data = res.datalist;
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
        }else{
          app.alert(res.msg)
        }
      });
    },
    chooseLevel: function (e) {
      this.levelDialogShow = true;
    },
    hideTimeDialog: function () {
      this.levelDialogShow = false;
    },
    levelRadioChange: function (e) {
      var that = this;
      that.checkLevelname = e.currentTarget.dataset.name;
      that.checkLevelid = e.currentTarget.dataset.id;
      that.levelDialogShow = false;
      that.getdatalist();
    },
    callphone:function(e) {
      var phone = e.currentTarget.dataset.phone;
      uni.makePhoneCall({
        phoneNumber: phone,
        fail: function () {
        }
      });
    },
  }
};
</script>
<style>
  .head-item2{display: flex;font-size: 24rpx;color: #000;line-height: 44rpx;text-align: center;margin-top: 6rpx;}
  .head-item2-level{border-radius:44rpx 44rpx;padding: 0 20rpx;color: #fff;}
  .head-item2-id{background-color: #fff;border-radius:44rpx 44rpx ;margin-left: 10rpx;padding: 0 20rpx;}
  .head-otherinfo{width: 470rpx;position: relative;height: 40rpx;line-height: 40rpx;overflow: hidden;text-overflow: ellipsis;border-radius: 40rpx 40rpx;}
  .head-otherinfo2{width: 100%;height: 40rpx;position: absolute;top:0;left: 0;background-color: #fff;opacity: 0.3;z-index: 9;}
  .head-otherinfo3{width: 100%;height: 40rpx;position: absolute;top:0;left: 0;z-index: 10;padding-left:20rpx;}
  .today{padding: 20rpx 0rpx;background-color: #fff;margin-top:20rpx;font-weight: bold;}
  .today-content{width: 750rpx;margin: 0rpx auto;padding: 0 20rpx;display: flex;justify-content: space-between;}
  .myteam-search{background-color: #f1f1f1;display: flex;padding: 10rpx;justify-content: space-between;align-items: center;border-radius: 6rpx;width: 33%;}
  .teamcount{margin: 20rpx;display: flex;align-items: center;line-height: 60rpx;text-align: center;}
  
  .pstime-item{display:flex;border-bottom: 1px solid #f5f5f5;padding:20rpx 30rpx;}
  .pstime-item .radio{flex-shrink:0;width: 32rpx;height: 32rpx;background: #FFFFFF;border: 2rpx solid #BFBFBF;border-radius: 50%;margin-right:30rpx}
  .pstime-item .radio .radio-img{width:100%;height:100%}
</style>