<template>
  <view class="container">
    <block v-if="isload">
      <view style="padding: 20rpx;color:#fff" :style="'background-color:'+t('color1')">
        <view style="font-size: 30rpx;">我的信息</view>
        <view style="margin: 20rpx 0;display: flex;align-items: center;">
          <view style="width: 80rpx;height: 80rpx;overflow: hidden;border-radius: 6rpx;background-color: #f1f1f1;">
            <image :src="userinfo.headimg" style="width: 100%;height: 100%;"></image>
          </view>
          <view style="margin-left: 10rpx;">
            <view> {{userinfo.nickname}}</view>
            <view class="head-item2"> 
              <view class="head-item2-level" :style="'background-color:'+t('color2')">{{userinfo.levelname}}</view>
              <view class="head-item2-id">ID：{{userinfo.id}}</view>
            </view>
          </view>
        </view>
        <view class="teamcount">
          <view style="width: 50%;">
            <view style="font-size: 30rpx;">{{userinfo.childnum}}</view>
            <view>直属用户（人）</view>
          </view>
          <view style="width: 50%;">
            <view style="font-size: 30rpx;">{{userinfo.childnum2}}</view>
            <view>总用户（人）</view>
          </view>
        </view>
      </view>
      
      <view style="padding: 20rpx 0;background-color: #fff;">
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
      
      <view class="today">
        <view class="today-content">
          <view style="display: flex;align-items: center;">
            <view style="width: 20rpx;height: 20rpx;border-radius: 50% 50%;" :style="'border:4rpx solid '+t('color1')"></view>
            <view style="margin-left: 10rpx;font-weight: bold;font-size: 30rpx;">今日收益</view>
            <view style="margin-left: 10rpx;" :style="'color:'+t('color1')"> ￥{{userinfo.todayCommission}}</view>
          </view>
          <view @tap="goto" data-url="/adminExt/commission/myIncome" style="display: flex;align-items: center;color:#A4A4A4;font-size: 26rpx;">
            <text>查看最近收益</text>
            <image :src="pre_url+'/static/img/arrowright.png'" style="width: 30rpx;height: 30rpx;margin-left: 10rpx;"></image>
          </view>
        </view>
      </view>
      
      <view style="padding: 20rpx 0rpx;background-color: #fff;margin-top: 20rpx;">
        <view style="width: 750rpx;margin: 20rpx auto;padding: 0 20rpx;">
          <view style="display: flex;align-items: center;">
            <view style="width: 4rpx;height: 28rpx;" :style="'background-color:'+t('color1')"></view>
            <view style="margin-left: 10rpx;font-weight: bold;font-size: 30rpx;">我的粉丝</view>
          </view>
          <view style="margin: 20rpx 0;display: flex;justify-content: space-between;align-items: center;">
            <view class="myteam-search">
              <input  @confirm="searchConfirm" @input="inputTeamname" placeholder="请输入名称" placeholder-style="color:#999;height: 40rpx;line-height: 40rpx;font-size:26rpx" style="height: 40rpx;line-height: 40rpx;width: 180rpx;"/>
              <image :src="pre_url+'/static/img/search_ico.png'" style="width: 30rpx;height: 30rpx;"></image>
            </view>
            <view class="myteam-search">
              <input  @confirm="searchConfirm" @input="inputTeamid" placeholder="请输入下级ID" placeholder-style="color:#999;height: 40rpx;line-height: 40rpx;font-size:26rpx" style="height: 40rpx;line-height: 40rpx;width: 180rpx;"/>
              <image :src="pre_url+'/static/img/search_ico.png'" style="width: 30rpx;height: 30rpx;"></image>
            </view>
            <view @tap="chooseLevel" style="width: 26%;text-align:center;line-height: 60rpx;color: #fff;border-radius: 6rpx;" :style="'background-color:'+t('color1')">
              等级筛选 <text class="iconfont iconshaixuan" style="font-size: 26rpx;margin-left: 5rpx;"></text>
            </view>
          </view>
          <view style="color:#A4A4A4;">等级筛选：{{checkLevelname || '全部'}}</view>
        </view>
      </view>
      
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
    <view v-if="levelDialogShow" class="popup__container">
      <view class="popup__overlay" @tap.stop="hideTimeDialog"></view>
      <view class="popup__modal">
        <view class="popup__title">
          <text class="popup__title-text">请选择级别</text>
          <image :src="pre_url+'/static/img/close.png'" class="popup__close" style="width:36rpx;height:36rpx;" @tap.stop="hideTimeDialog"/>
        </view>
        <view class="popup__content">
          <scroll-view style="max-height: 100%;height: auto;" scroll-y>
            <view class="pstime-item" @tap="levelRadioChange" data-id="0" data-name="全部">
              <view class="flex1">全部</view>
              <view class="radio" :style="checkLevelid == 0?'background-color:red ;border-color:red':''"><image class="radio-img" :src="pre_url+'/static/img/checkd.png'"/></view>
            </view>
            <view class="pstime-item" v-for="(item, index) in allLevel" :key="index" @tap="levelRadioChange" :data-id="item.id" :data-name="item.name">
              <view class="flex1">{{item.name}}</view>
              <view class="radio" :style="checkLevelid == item.id?'background-color:red;border-color:red ;':''"><image class="radio-img" :src="pre_url+'/static/img/checkd.png'"/></view>
            </view>
          </scroll-view>
        </view>
      </view>
    </view>
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
      app.get('ApiAgent/teamManage',{},function(res){
        that.loading = false;
        if(res.status == 1){
            that.userinfo = res.userinfo;
            that.allLevel = res.allLevel;
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
    inputTeamname:function(e){
      this.teamname = e.detail.value
    },
    inputTeamid:function(e){
      this.teamid = e.detail.value
    },
    searchConfirm: function (e) {
      this.getdata();
    },
  }
};
</script>
<style>
  .head-item2{display: flex;font-size: 24rpx;color: #000;line-height: 44rpx;text-align: center;margin-top: 6rpx;}
  .head-item2-level{border-radius:44rpx 44rpx;padding: 0 20rpx;color: #fff;}
  .head-item2-id{background-color: #fff;border-radius:44rpx 44rpx ;margin-left: 10rpx;padding: 0 20rpx;}
  .today{padding: 20rpx 0rpx;background-color: #fff;margin-top:20rpx;font-weight: bold;}
  .today-content{width: 750rpx;margin: 0rpx auto;padding: 0 20rpx;display: flex;justify-content: space-between;}
  .myteam-search{background-color: #f1f1f1;display: flex;padding: 10rpx;justify-content: space-between;align-items: center;border-radius: 6rpx;width: 33%;}
  .teamcount{margin: 20rpx;display: flex;align-items: center;line-height: 60rpx;text-align: center;}
  
  .pstime-item{display:flex;border-bottom: 1px solid #f5f5f5;padding:20rpx 30rpx;}
  .pstime-item .radio{flex-shrink:0;width: 32rpx;height: 32rpx;background: #FFFFFF;border: 2rpx solid #BFBFBF;border-radius: 50%;margin-right:30rpx}
  .pstime-item .radio .radio-img{width:100%;height:100%}
</style>