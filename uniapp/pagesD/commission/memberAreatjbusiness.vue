<template>
  <view class="container">
    <block v-if="isload">
      <view class="topfix">
        <view class="head-total" style="background-color: #fd2936;color: #feef2f;">
          <view class="data-search flex-xy-center">
            <view class="header-back-but" @tap="goback">
              <image :src="`${pre_url}/static/img/hotel/fanhui.png`"></image>
            </view>
            <view class="data-btn" :class="{selected: datetype === 'today'}" @tap="changeDateType('today')">今日</view>
            <view class="data-btn" :class="{selected: datetype === 'week'}" @tap="changeDateType('week')">近7天</view>
            <view class="data-btn" :class="{selected: datetype === 'month'}" @tap="changeDateType('month')">本月</view>
            <view class="data-btn" :class="{selected: datetype === 'year'}" @tap="changeDateType('year')">本年</view>
          </view>
          <view v-if="provincename" class="area-box">
            <text>{{provincename}}</text>
            <text v-if="cityname">-{{cityname}}</text>
          </view>
          <view class="tongji flex-y-center">
            <view class="stat-item">
              <view>商家数量</view>
              <view class="price">{{count.business_count}}</view>
            </view>
            <view class="divider"></view>
            <view class="stat-item mb0">
              <view>区域业绩</view>
              <view class="price">{{count.commission_count}}</view>
            </view>
            <view class="divider"></view>
            <view class="stat-item">
              <view>订单数量</view>
              <view class="price">{{count.order_count}}</view>
            </view>
          </view>
          <view class="pv-box">
            <view class="pv">{{count.rangli_count}}</view>
            <view class="pv-title" :style="{background:'url('+pre_url+'/static/img/countbj.png) no-repeat',backgroundSize: '70%',backgroundPosition: 'center',  filter: 'brightness(1000%) saturate(0%)'}">业绩统计(PV)</view>
          </view>
          <view style="height: 40rpx"></view>
        </view>
        <view v-if="neworders && neworders.length>0" class="neworders" >     
        		<image :src="pre_url+'/static/imgsrc/notice2.png'" style="width:36rpx;height:36rpx;margin-right:10rpx"/>
            <swiper :autoplay="true" :interval="2000" :vertical="true" :circular="true" class="neworders-swiper" >
              <swiper-item v-for="item in neworders" style="white-space: nowrap;overflow: hidden;text-overflow: ellipsis;">
                {{item.name}}店 订单{{item.totalprice}}元
              </swiper-item>
            </swiper>
        </view>
        <view style="background: #fff;width: 720rpx;margin: 0 auto;display: flex;align-items: center;line-height: 80rpx;">
          <view>地区业绩概览</view>
          <view style="flex:1;display: flex;margin-left: 20rpx;align-items: center;">
            <view v-if="provinceArray && provinceArray.length>0">
              <picker  mode="selector" :range="provinceArray" @change="provincechange">
                <view class="picker">
                  <text>{{provincename?provincename:'请选择省份'}}</text>
                  <image class="arrowdown" :src="pre_url+'/static/img/location/down-black.png'"></image>
                </view>
              </picker>
            </view>
            
            <view v-if="cityArray && cityArray.length>0">
              <picker mode="selector"  :range="cityArray" @change="citychange">
                <view class="picker">
                   <text>{{cityname?cityname:'城市'}}</text>
                  <image class="arrowdown" :src="pre_url+'/static/img/location/down-black.png'"></image>
                </view>
              </picker>
            </view>
            
            <view v-if="showdistrict && districtArray && districtArray.length>0">
              <picker mode="selector" :range="districtArray" @change="districtchange">
                <view class="picker">
                   <text>{{districtname?districtname:'县区'}}</text>
                  <image class="arrowdown" :src="pre_url+'/static/img/location/down-black.png'"></image>
                </view>
              </picker>
            </view>
          </view>
        </view>
      </view>

			<view class="ind_business">
        <view v-for="(item, index) in datalist" :key="index" 
        @tap="goto" :data-url=" '/pagesD/commission/areatjbusiness?opttype=member_business_area&provincename=' + provincename + '&cityname=' + cityname + '&districtname=' + item.name">
          <view class="ind_busbox">
            <view style="height: 100rpx;line-height: 100rpx;background-color: #000;border-radius: 20rpx;color: #fff;text-align: center;width: 200rpx;overflow: hidden;white-space:nowrap;text-overflow: ellipsis;">
                {{item.name}}
            </view>
            <view class="flex" style="flex: 1;margin-left: 30rpx;">
              <view class="activecoin flex rl-total">
                <text class="rl-title">累计让利</text>
                <text class="rl-number">{{item.newscore_total}}</text>
              </view>
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
      st: '1',
      count:{},
      areaname:'',
      pagenum: 1,
      datalist: [],
      nodata: false,
      nomore: false,
      pre_url:app.globalData.pre_url,
      yqpath:'',
      datetype: 'today',
      
      
      neworders:[],
      items:[],
      citydata:[],
      provinceArray:[],
      cityArray:[],
      showdistrict:false,
      districtArray:[],
      
      provincename:'',
      cityname:'',
      districtname:'',

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
  methods: {
    getdata: function () {
      var that = this;
      var st = that.st;
			var pagenum = that.pagenum;
			var datetype = that.datetype;
      that.loading = true;
      that.nodata = false;
      that.nomore = false;
      app.get('ApiAgent/areatjbusiness',{
        st:st,pagenum: pagenum,date:datetype,
        opttype:'member_business_area',provincename:that.provincename,cityname:that.cityname,districtname:that.districtname,
      },function(res){
        that.loading = false;
        uni.setNavigationBarTitle({
        	title: that.t('会员')+'区域商家中心'
        });
        if(res.status == 1){
          that.count = res.count;
          that.neworders = res.neworders || [];

          if(!that.provincename){
            that.items = res.items || [];
            that.citydata = res.citydata || [];
            that.provinceArray = res.provinceArray || [];
            that.cityArray = res.cityArray || [];
            that.showdistrict = res.showdistrict || false;
            that.districtArray = res.districtArray || [];
            
            that.provincename = res.provincename || '';
            that.cityname = res.cityname || '';
            that.districtname = res.districtname || '';
          }

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
    changeDateType: function(type) {
      this.datetype = type;
      this.pagenum = 1;
      this.getdata();
    },
    provincechange:function(e){
    	var key = e.detail.value;
    	this.provincename = this.provinceArray[key];
      
    	var children  = this.items[key]['children'];
    	this.citydata = children;

    	var cityArray = [];
      var cityname  = '';
      if(children && children.length>0){
        for(var i=0;i<children.length;i++){
        	cityArray.push(children[i]['text']);
        }
        cityname = children[0]['text'] || '';
      }
    	
    	this.cityArray = cityArray;
    	this.cityname = cityname;

      this.districtArray = [];
    	this.districtname = '';
    	this.getdata();
    },
    citychange:function(e){
      var that = this;
    	var key = e.detail.value;
    	that.cityname = that.cityArray[key];
      if(that.showdistrict){
        var districtArray = [];
        var districtname  = '';
        var children = that.citydata[key]['children'];
        if(children && children.length>0){
          for(var i=0;i<children.length;i++){
          	districtArray.push(children[i]['text']);
          }
          districtname = children[0]['text'] || '';
        }
        that.districtArray = districtArray;
        that.districtname  = districtname;
      }
    	that.getdata();
    },
    districtchange:function(e){
    	var key = e.detail.value;
    	this.districtname = this.districtArray[key];
    	this.getdata();
    },
  }
};
</script>
<style>
.topfix{width: 100%;background: #f9f9f9;top:var(--window-top);z-index:11;}

.head-total .data-search{padding: 30rpx 0;position: relative;}
.head-total .data-search .selected{background-color: #ffff00 !important;}
.head-total .data-search .data-btn{padding: 5rpx 10rpx;color: #000;background-color: #fe7f25;margin-right: 20rpx;border-radius: 10rpx;}
.head-total .pv-box{text-align: center;margin-top: 20rpx}
.head-total .pv-box .pv-title{font-size: 30rpx;height: 80rpx;line-height: 80rpx}
.head-total .pv-box .pv{font-size: 60rpx;font-weight: bold;}
.head-total .area-box{background: #000;text-align: center;padding: 10rpx 0;height: 60rpx;height: 60rpx;}
.head-total .tongji{height: 160rpx;color: #fff;justify-content: space-evenly;text-align: center;}
.head-total .tongji .price{font-size: 40rpx;font-weight: 700;color: #fff;margin-top: 10rpx;}
.head-total .tongji .divider { width: 2rpx; height: 80rpx; background-color: rgba(255, 255, 255, 0.5); align-self: center; }
.ind_business {width: 100%;margin-top: 20rpx;font-size:26rpx;padding:0 24rpx}
.ind_business .ind_busbox{ width:100%;background: #fff;overflow: hidden; margin-bottom:20rpx;border-radius:8rpx;position:relative;display: flex;align-items: center;}
.ind_business .ind_buspic{ width:180rpx;height:180rpx;}
.ind_business .ind_buspic image{ width: 100%;height:100%;border-radius: 8rpx;object-fit: cover;}
.ind_business .bus_title{ font-size: 30rpx; color: #222;font-weight:bold;line-height:46rpx;white-space: nowrap;max-width: 380rpx;overflow: hidden;text-overflow: ellipsis;}
.ind_business .bus_score{font-size: 24rpx;color:#FC5648;display:flex;align-items:center;justify-content: space-between;}
.ind_business .bus_score .img{width:24rpx;height:24rpx;margin-right:10rpx}
.ind_business .bus_score .txt{margin-left:20rpx}
.ind_business .bus_address{color:#999;font-size: 22rpx;height:36rpx;line-height: 36rpx;margin-top:6rpx;display:flex;align-items:center;-webkit-box-orient:vertical;-webkit-line-clamp:1;overflow:hidden;}
.ind_business .bus_address .x1{text-overflow: ellipsis;white-space: nowrap;width: 60%;overflow: hidden;flex: 1;}
.ind_business .bus_address .x2{padding-left:20rpx}
.ind_business .activecoin{background: #ffdbdd;color: #fd2936;border-radius: 10rpx;line-height: 40rpx;}
.ind_business .activecoin .rl-title{background: #fd2936;color:#fff;border-radius: 10rpx;width: 150rpx;text-align: center;}
.ind_business .activecoin .rl-number{margin-left: 20rpx;font-weight: bold;}
.ind_business .rl-ratio{width: 40%;margin-right: 3%;}
.ind_business .rl-total{flex: 1;}
.header-back-but{position: absolute;left: 40rpx}
.header-back-but image{width: 40rpx;height: 45rpx;}

.arrowdown{width: 30rpx;height: 30rpx;margin-right: 20rpx;margin-left: 10rpx;}
.picker{display: flex;align-items: center;}
.neworders{background: #fff;width: 720rpx;margin: 0 auto;display: flex;align-items: center;padding-top: 20rpx;}
.neworders-swiper{position:relative;width:670rpx;height:40rpx;line-height:40rpx;font-size:28rpx;}
</style>