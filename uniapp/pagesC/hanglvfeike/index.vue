<template>
	<view class="page-view" v-if="isload">
		<view class="banner-view">
      <view class="swiper-container">
      	<swiper v-if="banners" class="swiper" :indicator-dots="false" :autoplay="true" :interval="5000" @change="swiperChange"   :current="current" :style="{ height: swiperHeight + 'px' }">
      		<block v-for="(item, index) in banners" :key="index">
      			<swiper-item class="swiper-item">
      				<view @tap="golinkurl" :data-linkurl="item.linkurl" class="swiper-item-view" :id="'content-wrap' + index" :style="{ height: swiperHeight + 'px' }">
      					<image class="img" :src="item.pic" mode="widthFix"  @load="loadImg"  />
      				</view>
      			</swiper-item>
      		</block>
      	</swiper>
      </view>
		</view>
		<view class="content-view flex-col">
			<view class="tab-view flex flex-y-center">
				<view :class="[tabIndex == 0 ? 'tab-options-active':'','tab-options']" @tap='tabChange(0)'>机票</view>
			</view>
			<view class="fun-view flex-col">
				<view class="fun-tab flex felx-y-center">
					<view @tap='funTabChange(0)' :class="[airtype == 0 ? 'funtab-left-active':'not-left-active','fun-tab-options']">单程</view>
					<!-- <view @tap='funTabChange(1)' :class="[airtype == 1 ? 'funtab-right-active':'not-right-active','fun-tab-options']">往返</view> -->
				</view>
				<view class="info-view flex-col">
					<view class="address-view flex">
						<view class="address-text" style="text-align: left;" @tap="goto" :data-url="'choosecitys?type=from'">
              <text v-if="fromCityname" style="color: #000;">{{fromCityname}}</text>
              <text v-else>出发地</text>
            </view>
						<view class="address-switch flex" @tap="switchChange">
							<image :class="[switchType ? 'bg-img-active':'','bg-img']" :src="pre_url+'/static/img/planeticket/qiehuan.png'"></image>
							<image class="logo-img" :src="pre_url+'/static/img/planeticket/feijizhong.png'"></image>
						</view>
						<view class="address-text" style="text-align: right;" @tap="goto" :data-url="'choosecitys?type=to'">
              <text v-if="toCityname" style="color: #000;">{{toCityname}}</text>
              <text v-else>目的地</text>
            </view>
					</view>
					<view class="time-view flex-col">
						<view class="title-text">出发时间</view>
						<view class="time-num-text" style="display: flex;justify-content: space-between;">
              <view @tap="toSelectDate(1,daytime)">{{day}} {{week}}</view>
              <view v-if="airtype == 1" @tap="toSelectDate(2,daytime,daytime2)">{{day2}} {{week2}}</view>
            </view>
					</view>
					<view @tap="godetails"  class="search-but">
						搜索机票
					</view>
				</view>
			</view>
      <view style="position: relative;height: 120rpx;">
        <view v-if="linktel" @tap="callphone" :data-phone="linktel" style="font-size: 32rpx;text-align: center;color: #af1e24;">联系客服</view>
        <view style="width: 70rpx;position: absolute;top:50rpx;right: 20rpx;">
          <view v-if="kfurl!='contact::'" @tap="goto" :data-url="kfurl" style="width: 70rpx;">
          	<image class="img" :src="pre_url+'/static/img/kefu.png'" style="width: 70rpx;height: 70rpx;"/>
          </view>
          <button v-else open-type="contact" show-message-card="true" style="width: 70rpx;">
          	<image class="img" :src="pre_url+'/static/img/kefu.png'" style="width: 70rpx;height: 70rpx;"/>
          </button>
        </view>
      </view>
		</view>
    <loading v-if="loading"></loading>
		<dp-tabbar :opt="opt" @getmenuindex="getmenuindex"></dp-tabbar>
		<popmsg ref="popmsg"></popmsg>
	</view>
</template>

<script>
	var app = getApp();
	export default {
		data(){
			return{
        opt:{},
        menuindex:-1,
				isload: false,
				pre_url:app.globalData.pre_url,
				tabIndex:0,
				switchType:false,
        
        airtype:0,//机票类型
        current:0,
        swiperHeight: '',
        banners:'',
        bannerpics:'',
        showday:0,
        
        fromCityname:'',//出发地名称
        fromCity:'',//出发地三字码
        
        toCityname:'',//目的地名称
        toCity:'',//目的地三字码
        
        daytime:'',
        day:'',
        week:'',
        daytime2:'',
        day2:'',
        week2:'',
        
        linktel:'',
        kfurl:'',
			}
		},
    onLoad: function (opt) {
      var that = this;
    	that.opt = app.getopts(opt);
      //获取缓存
      that.fromCityname= app.getCache('search_fromCityname') || '';
      that.fromCity    = app.getCache('search_fromCity') || '';
      that.toCityname  = app.getCache('search_toCityname') || '';
      that.toCity      = app.getCache('search_toCity') || '';
    	that.getdata();
    },
    onShow(){
      var that = this;
      var pages = getCurrentPages(); //获取加载的页面
      var currentPage = pages[pages.length - 1]; //获取当前页面的对象
      
      if(currentPage && (currentPage.$vm.fromCityname || currentPage.$vm.toCityname)){
        if(currentPage.$vm.fromCityname){
            that.fromCityname= currentPage.$vm.fromCityname;
            that.fromCity    = currentPage.$vm.fromCity;
            app.setCache('search_fromCityname', that.fromCityname);
            app.setCache('search_fromCity', that.fromCity);
        }
        if(currentPage.$vm.toCityname){
            that.toCityname  = currentPage.$vm.toCityname;
            that.toCity      = currentPage.$vm.toCity;
            app.setCache('search_toCityname', that.toCityname);
            app.setCache('search_toCity', that.toCity);
        }
      }
      uni.$on('selectedDate',function(data,otherParam){
        if(otherParam == 1 || otherParam == 2){
          var dateStr = data.startStr.dateStr;
          that.daytime= dateStr;
          var dateArr = dateStr.split('-');
          that.day    = dateArr[1]+'月'+dateArr[2]+'日';
          that.week   = data.startStr.week;
          if(otherParam == 2){
            var dateStr2 = data.endStr.dateStr;
            that.daytime2= dateStr2;
            var dateArr2 = dateStr2.split('-');
            that.day2    = dateArr2[1]+'月'+dateArr2[2]+'日';
            that.week2   = data.endStr.week;
          }
        }
      })
    },
		methods:{
      getdata: function () {
        var that = this;
      	that.nodata = false;
      	that.nomore = false;
      	that.loading = true;
        app.post('ApiHanglvfeike/index', {}, function (res) {
      		that.loading = false;
          if(res.status == 1){
            that.banners = res.banners || '';
            that.bannerpics = res.bannerpics || '';
            
            that.daytime = res.daytime || '';
            that.day = res.day || '';
            that.week = res.week || '';
            that.daytime2 = res.daytime2 || '';
            that.day2 = res.day2 || '';
            that.week2= res.week2 || '';
            
            that.showday = res.showday;
            that.linktel = res.linktel;
            
            that.kfurl = res.kfurl;
            if(app.globalData.initdata.kfurl != ''){
            	that.kfurl = app.globalData.initdata.kfurl;
            }
            that.loaded();
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
			tabChange(index){
				this.tabIndex = index;
			},
			funTabChange(index){
				this.airtype = index;
			},
			switchChange(){
				let that = this;
				if(that.switchType) return;
				that.switchType = true;
        var fromCityname= that.fromCityname;//出发地名称
        var fromCity    = that.fromCity;//出发地三字码
        var toCityname  = that.toCityname;//出发地名称
        var toCity      = that.toCity;//出发地三字码
        
        that.fromCityname= toCityname;
        that.fromCity    = toCity;
        that.toCityname  = fromCityname;
        that.toCity      = fromCity;
				setTimeout(() =>{
					that.switchType = false;
				},600)
			},
      loadImg() {
      	this.getCurrentSwiperHeight('.img');
      },
      swiperChange: function (e) {
      	var that = this;
      	that.current = e.detail.current;
      	// 禁止错误滑动事件
      	if(!e.detail.source) return that.current = 0;
      	//动态设置swiper的高度，使用nextTick延时设置
      	this.$nextTick(() => {
      	  this.getCurrentSwiperHeight('.img');
      	});
      },
      // 动态获取内容高度
      getCurrentSwiperHeight(element) {
      		// #ifndef MP-ALIPAY
      		let query = uni.createSelectorQuery().in(this);
      		query.selectAll(element).boundingClientRect();
      		var imgList = this.bannerpics;
          query.exec((res) => {
            // 切换到其他页面swiper的change事件仍会触发，这时获取的高度会是0，会导致回到使用swiper组件的页面不显示了
            if (imgList.length && res[0][this.current].height) {
              this.swiperHeight = res[0][this.current].height;
            }
          });	
      		// #endif
      		// #ifdef MP-ALIPAY
      		var imgList = this.bannerpics;
      		my.createSelectorQuery().select(element).boundingClientRect().exec((ret) => {
      			if (imgList.length && ret[this.current].height) {
      			  this.swiperHeight = ret[this.current].height;
      			}
      			});
      		// #endif
      },
      golinkurl:function(e){
        var that = this;
        var linkurl = e.currentTarget.dataset.linkurl;
        if(linkurl){
          app.goto(linkurl);
        }
      },
      toSelectDate(otherParam,startdate,enddate){
        var that = this;
        var url = '/pagesExt/checkdate/checkDate?startdate='+startdate+'&dayin=0&dayin2='+that.showday+'&type=1&otherParam='+otherParam;
        if(otherParam == 2){
          url += '&t_mode=3&enddate='+enddate;
        }else{
          url += '&t_mode=1';
        }
        app.goto(url);
      },
      godetails:function(){
        var that  = this;
        if(!that.fromCity){
          app.alert('请选择出发地');
          return;
        }
        if(!that.toCity){
          app.alert('请选择目的地');
          return;
        }
        var url = 'fromCityname='+that.fromCityname+'&fromCity='+that.fromCity+'&toCityname='+that.toCityname+'&toCity='+that.toCity;
        if(that.airtype == 1){
          url += '&goDate='+that.daytime+'&backDate='+that.daytime2;
          var gourl = 'detailstwo?'+url;
        }else{
          url += '&fromDate='+that.daytime;
          var gourl = 'details?'+url;
        }
        app.goto(gourl)
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
		
	}
</script>

<style>
	.page-view{background: #fbf6f6;width: 100%;height: 100vh;}
	.banner-view{width: 100%;}
	.banner-view image{width: 100%;}
	.content-view{width: 100%;position: relative;}
	.tab-view{width: 100%;height: 90rpx;position: absolute;left: 0;top: -88rpx;border-radius: 40rpx 40rpx 0rpx 0rpx;justify-content: space-around;
	background-image: linear-gradient(rgba(255, 255, 255,.4),rgba(255, 255, 255,.8));border-top: 2px #efefef solid;}
	.tab-view .tab-options{font-size: 34rpx;font-weight: bold;color: #888888;height: 90rpx;line-height: 88rpx;padding: 0rpx 5rpx;}
	.tab-view .tab-options-active{color: #af1e24;position: relative;}
	.tab-view .tab-options-active::after{content: " ";width: 100%;height: 6rpx;background: #af1e24;display: block;position: absolute;left: 0;bottom: 4rpx;border-radius: 8rpx;}
	.fun-view{width: 94%;margin: 20rpx auto;border-radius: 25rpx;background: #fff;padding: 8rpx;}
	.fun-view .fun-tab{justify-content: center;background-image: linear-gradient(#f2f2f2 50%,#fff 50%);border-radius: 25rpx 25rpx 0rpx 0rpx;overflow: hidden;}
	.fun-view .fun-tab .fun-tab-options{color: #888888;font-size: 26rpx;font-weight: bold;width: 100%;text-align: center;padding: 25rpx 0rpx;background: #f2f2f2;}
	.fun-view .fun-tab .funtab-left-active{border-top-right-radius: 35rpx;background: #fff;color: #333;}
	.fun-view .fun-tab .funtab-right-active{border-top-left-radius: 35rpx;background: #fff;color: #333;}
	.fun-view .fun-tab .not-right-active{border-bottom-left-radius: 35rpx;}
	.fun-view .fun-tab .not-left-active{border-bottom-right-radius: 35rpx;}
	.fun-view .info-view{border-radius: 0rpx 0rpx 25rpx 25rpx;background-image: linear-gradient(to right, #fffcfc,#fff3f3);padding: 15rpx 20rpx;}
	.fun-view .info-view .address-view{width: 100%;align-items: center;justify-content: space-between;border-bottom: 3px #f2f2f2 solid;padding-top: 15rpx;padding-bottom: 35rpx;}
	.address-view .address-switch{width: 80rpx;height: 80rpx;position: relative;}
	.address-view .address-switch .bg-img{width: 100%;height: 100%;z-index: 1;}
	.address-view .address-switch .bg-img-active{animation: rotate 0.6s ease-in-out;}
	.address-view .address-switch .logo-img{width: 64rpx;height: 64rpx;position: absolute;top: 50%;left: 50%;transform: translate(-50%,-50%);z-index: 2;}
	.address-view .address-text{font-size:36rpx;font-weight: bold;color: #888888;white-space: nowrap;flex: 1;overflow: hidden;text-overflow: ellipsis;}
	@keyframes rotate {
	  from {
	    transform: rotate(0deg);
	  }
	  to {
	    transform: rotate(360deg);
	  }
	}
	.time-view{width: 100%;padding: 20rpx 0rpx;}
	.title-text{font-size: 26rpx;color: #9a9a9a;font-weight: bold;}
	.time-num-text{font-size: 40rpx;color: #3e3e3e;padding-top: 15rpx;font-weight: bold;}
	.search-but{background: #af1e24;font-size: 32rpx;color: #f3f3f3;text-align: center;width: 100%;padding: 20rpx 0rpx;border-radius: 40rpx;margin-top: 30rpx;
	margin-bottom: 20rpx;}
  
  .swiper-container{position:relative;overflow: hidden;}
  .swiper {width: 100%;height: 500rpx;overflow: hidden;}
  .swiper-item-view{width: 100%;height: 500rpx;}
  .swiper .img {width: 100%;height: 500rpx;overflow: hidden;}
</style>