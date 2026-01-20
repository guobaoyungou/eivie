<template>
	<view class="page-view" v-if="isload">
    <view style="background-color: #fff;padding: 20rpx;">
      <view style="display: flex;justify-content: space-between;align-items: center;">

          <view v-if="flightsdata.airlinePic" style="width: 50rpx;height: 50rpx;">
            <image :src="flightsdata.airlinePic" style="width: 100%;height: 100%;"></image>
          </view>
          <view style="width: calc(100% - 70rpx);">
            <view style="font-size: 26rpx;color: #999;">
              {{fromDate}} {{week}}
              {{flightsdata.deparAirportName}}{{flightsdata.departTerminal}} - {{flightsdata.arriveAirportName}}{{flightsdata.arriveTerminal}}
            </view>
            <view  style="display: flex;align-items: center;margin: 25rpx 0;">
              <view style="font-size:36rpx;font-weight:bold;">{{flightsdata.departTime}}</view>
              <image :src="pre_url+'/static/img/planeticket/jiantou.png'" mode="widthFix" style="margin: 0 20rpx;width: 100rpx;"></image>
              <view style="font-size:36rpx;font-weight:bold;">{{flightsdata.arriveTime}}</view>
            </view>
            <view v-if="flightsdata.stopname" style="font-size: 24rpx;color: #999;">
             (经停{{flightsdata.stopname}})
            </view>
            <view style="font-size: 24rpx;color: #999;">
              {{flightsdata.airlineName}}{{flightsdata.no}}| {{flightsdata.planeCName}} ({{flightsdata.planeTypeName}}) | {{flightsdata.mealsname}} | {{flightsdata.flightTime}}
            </view>
          </view>
      </view>

    </view>
		<view class="jipiao-list-view flex-col">
      <view style="border-top:2rpx solid #f1f1f1">
        <dd-tab :itemdata="itemdata" :itemst="itemst" :st="st" :showstatus="showstatus" :isfixed="false" @changetab="changetab"></dd-tab>
      </view>
      <block v-if="st == 0">
        <view v-if="datalist && datalist.length>0" style="padding: 0 20rpx;margin-top: 20rpx;">
          <block v-for="(item,index) in datalist" :key="index">
            <view style="background-color: #fff;border-radius: 12rpx;margin-bottom: 20rpx;">
              <view style="padding: 10rpx 20rpx;">
                <view style="display: flex;justify-content: space-between;align-items: center;" >
                  <view :style="'color:'+t('color1')">
                    <text>￥</text>
                    <text style="font-size: 40rpx;">{{item.minSalePrice}}</text>
                  </view>
                  <view @tap="gobuy" :data-index="index" style="border-radius: 8rpx;overflow: hidden;padding: 4rpx;width: 90rpx;" :style="'text-align:center;background-color:'+t('color1')">
                    <block v-if="item.showstock">
                      <view style="color: #fff;padding: 6rpx 0;">订</view>
                      <view :style="'font-size: 24rpx;;color:'+t('color1')+';background-color:#fff'">剩{{item.stock}}张</view>
                    </block>
                    <block v-else>
                      <view style="color: #fff;padding: 20rpx 0;">订</view>
                    </block>
                  </view>
                </view>
                <view style="color: #999;line-height: 50rpx;">{{item.levelname}}</view>
                <view @tap="getguize" :data-index="index" :data-cabinno="item.cabinNo" style="color: #999;line-height: 50rpx;display: flex;align-items: center;border-radius:0 0 8rpx 8rpx;">
                <text v-if="item.discount>0">{{item.discount}}折</text>退改详情 <image :src="pre_url+'/static/img/arrowright.png'" style="width: 28rpx;height: 28rpx;"></image></view>
              </view>
            </view>
          </block>
        </view>
        <block v-else>
          <nodata text="暂无仓位信息"></nodata>
        </block>
      </block>
      <block v-if="st == 1">
        <view v-if="datalist2 && datalist2.length>0" style="padding: 0 20rpx;margin-top: 20rpx;">
          <block v-for="(item,index) in datalist2" :key="index">
            <view style="background-color: #fff;border-radius: 12rpx;margin-bottom: 20rpx;">
              <view style="padding: 10rpx 20rpx;">
                <view style="display: flex;justify-content: space-between;align-items: center;" >
                  <view :style="'color:'+t('color1')">
                    <text>￥</text>
                    <text style="font-size: 40rpx;">{{item.minSalePrice}}</text>
                  </view>
                  <view @tap="gobuy" :data-index="index" style="border-radius: 8rpx;overflow: hidden;padding: 4rpx;width: 90rpx;" :style="'text-align:center;background-color:'+t('color1')">
                    <block v-if="item.showstock">
                      <view style="color: #fff;padding: 6rpx 0;">订</view>
                      <view :style="'font-size: 24rpx;;color:'+t('color1')+';background-color:#fff'">剩{{item.stock}}张</view>
                    </block>
                    <block v-else>
                      <view style="color: #fff;padding: 20rpx 0;">订</view>
                    </block>
                  </view>
                </view>
                <view style="color: #999;line-height: 50rpx;">{{item.levelname}}</view>
                <view @tap="getguize" :data-index="index" :data-cabinno="item.cabinNo" style="color: #999;line-height: 50rpx;display: flex;align-items: center;border-radius:0 0 8rpx 8rpx;">{{item.discount}}折退改详情 <image :src="pre_url+'/static/img/arrowright.png'" style="width: 28rpx;height: 28rpx;"></image></view>
              </view>
            </view>
          </block>
        </view>
        <block v-else>
          <nodata text="暂无航班信息"></nodata>
        </block>
      </block>
		</view>
  
    <!-- 详情弹窗 -->
    <uni-popup id="popup" ref="popup" type="bottom" >
    	<view class="popup__content" style="bottom: 0;padding-top:0;padding-bottom:0; max-height: 86vh;background-color: #fff;border-radius: 16rpx 16rpx;overflow: hidden;">
    		<!-- <view class="popup-close" @click="popupdetailClose">
    			<image :src="`${pre_url}/static/img/hotel/popupClose.png`"></image>
    		</view> -->
        <dd-tab :itemdata="['产品说明','行李规定','退改规则']" :itemst="['0','1','2']" :st="st2" :showstatus="showstatus2" :isfixed="true" @changetab="changetab2"></dd-tab>
        <view style="width:100%;height:100rpx"></view>
    		<scroll-view :scrollIntoView="intoviewid" :scrollWithAnimation="true" scroll-y style="height: auto;max-height:calc(86vh - 130rpx);;">
          <view v-if="guize" style="padding:0 20rpx;">
            <view id="scrollid0" class="popup_title">产品说明</view>
            <view style="border: 2rpx solid #f1f1f1;border-radius: 8rpx 8rpx;">
                <view :style="'padding: 20rpx;background:rgba('+t('color1rgb')+',0.4)'">票价/其他说明</view>
                <view v-if="guize.baoxiaocontent" style="border-top: 2rpx solid #f1f1f1;">
                  <view style="display: flex;align-items: center;">
                    <view class="popup-item-title" >
                      报销凭证
                    </view>
                    <view class="popup-item-content">
                      {{guize.baoxiaocontent}}
                    </view>
                  </view>
                </view>
                <view v-if=" guize.pricecontent" style="border-top: 2rpx solid #f1f1f1;">
                  <view style="display: flex;align-items: center;">
                    <view class="popup-item-title" >
                      价格说明
                    </view>
                    <view class="popup-item-content">
                      {{guize.pricecontent}}
                    </view>
                  </view>
                </view>
            </view>
            
            <view id="scrollid1" class="popup_title">行李规定</view>
            <view style="border: 2rpx solid #f1f1f1;border-radius: 8rpx 8rpx;">
                <view :style="'padding: 20rpx;background:rgba('+t('color1rgb')+',0.4)'">托运/手提行李</view>
    
                <view v-if="guize.checkedluggage" style="display: flex;align-items: center;border-top: 2rpx solid #f1f1f1;">
                  <view class="popup-item-title" >
                    托运行李
                  </view>
                  <view class="popup-item-content">
                    {{guize.checkedluggage}}
                  </view>
                </view>
    
                <view v-if="guize.cabinluggage" style="display: flex;align-items: center;border-top: 2rpx solid #f1f1f1;">
                  <view class="popup-item-title" >
                    手提行李
                  </view>
                  <view class="popup-item-content">
                    {{guize.cabinluggage}}
                  </view>
                </view>
    
                <view v-if="guize.infantluggage" style="display: flex;align-items: center;border-top: 2rpx solid #f1f1f1;">
                  <view class="popup-item-title" >
                    婴儿行李
                  </view>
                  <view class="popup-item-content">
                    {{guize.infantluggage}}
                  </view>
                </view>
                <view v-if="guize.luggage" style="display: flex;align-items: center;border-top: 2rpx solid #f1f1f1;">
                  <view class="popup-item-title" >
                    其他说明
                  </view>
                  <view class="popup-item-content">
                    {{guize.luggage}}
                  </view>
                </view>
            </view>
            
            <view id="scrollid2" class="popup_title">退改规则</view>
            <view style="border: 2rpx solid #f1f1f1;border-radius: 8rpx 8rpx;">
                <view :style="'padding: 20rpx;background:rgba('+t('color1rgb')+',0.4)'">退改费用/规则</view>
    
                <view v-if="guize.refundStipulate" style="display: flex;align-items: center;border-top: 2rpx solid #f1f1f1;">
                  <view class="popup-item-title" >
                    退改费
                  </view>
                  <view class="popup-item-content">
                    <block v-if="guize.refundStipulate.rules && guize.refundStipulate.rules.length>0">
                      <view v-for="(item,index) in guize.refundStipulate.rules" :key="index">
                        {{item.txt}} ￥{{item.charge}}/人
                      </view>
                    </block>
                    <block v-else>
                      <view v-if="guize.refundStipulate.comment">{{guize.refundStipulate.comment}}</view>
                    </block>
                  </view>
                </view>
                <view v-if="guize.changeStipulate" style="display: flex;align-items: center;border-top: 2rpx solid #f1f1f1;">
                  <view class="popup-item-title" >
                    同舱改签票
                  </view>
                  <view class="popup-item-content">
                    <block v-if="guize.changeStipulate.rules && guize.changeStipulate.rules.length>0">
                      <view v-for="(item,index) in guize.changeStipulate.rules" :key="index">
                        {{item.txt}} ￥{{item.charge}}/人
                      </view>
                    </block>
                    <block v-else>
                      <view v-if="guize.changeStipulate.comment">{{guize.changeStipulate.comment}}</view>
                    </block>
                  </view>
                </view>
    
                <view v-if="guize.modifyStipulate" style="display: flex;align-items: center;border-top: 2rpx solid #f1f1f1;">
                  <view class="popup-item-title" >
                    签转
                  </view>
                  <view class="popup-item-content">
                    {{guize.modifyStipulate}}
                  </view>
                </view>
                <view v-if="guize.othercontent" style="display: flex;align-items: center;border-top: 2rpx solid #f1f1f1;">
                  <view class="popup-item-title" >
                    其他说明
                  </view>
                  <view class="popup-item-content">
                    {{guize.othercontent}}
                  </view>
                </view>
            </view>
          </view>
    			<view style="width: 100%;height: 10rpx"></view>
    		</scroll-view>
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
		data(){
			return{
				isload:false,
        nodata:false,
				pre_url:app.globalData.pre_url,
        menuindex: -1,
				timeIndex:1,
        
        flightsdata:'',
        searchNo:'',//查询号
        flightNo:'',//航班号


        fromCityname:'',//出发地名称
        toCityname:'',//目的地名称
        fromDate:'',
        week:'',
        
        headdata:'',
        datalist:'',
        
        itemdata:[],
        itemst:[],
        st:0,
        showstatus:[1,1],
        minprice:-1,
        minprice2:-1,
        
        st2:0,
        showstatus2:[1,1,1],
        intoviewid:'',
        guize:''
			}
		},
    onLoad: function (opt) {
      var that = this;
    	that.opt = app.getopts(opt);
      
      var flightsdata = app.getCache('flightsdata') || '';
      if(!flightsdata){
        app.error('数据已失效');
        setTimeout(function(){
          app.goback();
        },900)
        return;
      }
      that.flightsdata = flightsdata;
      
      that.searchNo= that.opt.searchNo || '';
      that.flightNo= flightsdata.no || '';

      that.fromCityname= that.opt.fromCityname || '';
      that.toCityname  = that.opt.toCityname || '';
      that.fromDate    = that.opt.fromDate || '';

      uni.setNavigationBarTitle({
      	title: that.fromCityname + ' —— ' +that.toCityname
      });
    	
    },
    onShow:function(){
      var that = this;
      uni.$on('selectedDate',function(data,otherParam){
        if(otherParam){
          var dateStr = data.startStr.dateStr;
          var e = {
            currentTarget:{
              dataset:{
                daytime:dateStr
              }
            }
          }
          that.changeTime(e)
        }
      })
      that.getdata();
    },
		methods:{
      getdata: function () {
        var that = this;
      	that.nodata = false;
      	that.nomore = false;
      	that.loading = true;
        app.removeCache('flightspricedata');
        app.showLoading('加载中');
        app.post('ApiHanglvfeike/searchprice', {fromDate:that.fromDate,searchNo:that.searchNo,flightNo:that.flightNo}, function (res) {
          app.showLoading(false);
      		that.loading = false;
          if(res.status == 1){
            that.datalist  = res.datalist || '';
            that.datalist2 = res.datalist2 || '';
            that.minprice  = res.minprice;
            that.minprice2 =res.minprice2;
            that.week = res.week || '';
            that.itemdata = res.itemdata;
            that.itemst   = res.itemst;
            that.loaded();
          }else if(res.status == 2){
            app.error(res.msg);
            setTimeout(function(){
              app.goback();
            },900)
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
			changeTime(e){
        var that = this;
        that.fromDate  = e.currentTarget.dataset.daytime;
        that.headlist = [];
        that.datalist = [];
        that.getdata();
			},
      toSelectDate(date,otherParam){
        var that = this;
        app.goto('/pagesExt/checkdate/checkDate?date='+date+'&dayin=0&dayin2='+that.showday+'&type=1&otherParam='+otherParam);
      },
      getguize: function (e) {
        var that = this;
      	that.nodata = false;
      	that.nomore = false;
      	that.loading = true;
        var cabinNo = e.currentTarget.dataset.cabinno;
         console.log(cabinNo)
        app.post('ApiHanglvfeike/guize', {searchNo:that.searchNo,flightNo:that.flightNo,cabinNo:cabinNo}, function (res) {
      		that.loading = false;
          if(res.status == 1){
            that.guize = res.guize;
            that.$refs.popup.open();
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
      popupdetailClose(){
      	this.$refs.popup.close();
      },
      changetab: function (e) {
        var st = e;
        this.st = st;
      },
      changetab2: function (e) {
        var st2 = e;
        this.st2 = st2;
        this.intoviewid = 'scrollid'+st2;
      },
      gobuy:function(e){
        var that = this;
        if(that.st == 1){
          var datalist = that.datalist2;
        }else{
          var datalist = that.datalist;
        }
        var index  = e.currentTarget.dataset.index;
        var flightspricedata = datalist[index];
        flightspricedata['changeText'] = flightspricedata['refundText'] = flightspricedata['xleText'] = '';
        app.setCache('flightspricedata', flightspricedata);
        var url = 'buy?searchNo='+that.searchNo+'&fromCityname='+that.fromCityname+'&toCityname='+that.toCityname+'&fromDate='+that.fromDate+'&week='+that.week;  
        app.goto(url);
      }
		}
	}
</script>

<style>
	.page-view{background: #fbf6f6;width: 100%;height: 100vh;}
	.top-view{width: 100%;background: #fff;}
	.top-view .address-view{justify-content: center;}
	.top-view .address-view .address-text{font-size: 40rpx;color: #676767;flex: 1;}
	.top-view .address-view .fangxiang-icon{width: 50rpx;height:50rpx;margin: 50rpx 40rpx;}
	.top-view .address-view .fangxiang-icon image{width: 50rpx;height:50rpx;}
	.top-view .time-view{width: 100%;justify-content: space-between;padding-bottom: 5rpx;}
	.top-view .time-view .time-left-view{width: calc(100% - 130rpx);}
	.top-view .time-view .time-left-view .time-options{width: 120rpx;height: 130rpx;border-radius: 10rpx;justify-content: space-between;padding: 5rpx;}
	.top-view .time-view .time-left-view .time-options-active{background-color: #af1e24;color: #fff !important;}
	.top-view .time-view .time-left-view .time-options-active .time-title{color: #fff !important;}
	.top-view .time-view .time-left-view .time-options-active .time-num{color: #fff !important;}
	.time-options .time-title{width: 100%;text-align: center;font-size: 26rpx;color: #838383;}
	.time-options	.time-num{color: #333;font-size: 26rpx;width: 100%;text-align: center;}
	.top-view .time-view .time-right-view{width: 100rpx;height: 130rpx;box-shadow: -4px 0px 14px -14px rgba(0,0,0,.7);}
	.top-view .time-view .time-right-view .rili-icon{width: 46rpx;height: 46rpx;}
	.jipiao-list-view{width: 100%;}
	.jipiao-list-view .jipiao-options{background: #fff;width: 92%;margin: 10rpx auto;border-radius: 16rpx;padding: 25rpx;}
	.jipiao-list-view .jipiao-options .info-view{width: 100%;justify-content: space-between;align-items: center;}
	.jipiao-list-view .jipiao-options .info-view .info-touxiang{width: 50rpx;height: 50rpx;}
	.jipiao-list-view .jipiao-options .info-view .info-touxiang image{width: 100%;height: 100%;}
	.jipiao-list-view .jipiao-options .info-view .info-details-view{}
	.info-details-view .location-icon{width: 120rpx;position: relative;margin: 0rpx 25rpx 15rpx;}
	.info-details-view .location-icon image{width: 120rpx;}
	.info-details-view .location-icon .stop-tag{border: 1px #9c9c9c solid;position: absolute;bottom: -25rpx;left: 50%;transform: translateX(-50%);
	font-size: 24rpx;color: #7c7c7c;border-radius: 4rpx;padding: 0rpx 4rpx;white-space: nowrap;}
	.info-details-view .location-view{align-items: center;}
	.info-details-view .location-view .location-time{font-size: 44rpx;color: #333;font-weight: bold;}
	.info-details-view .location-view .location-name{font-size: 26rpx;color: #676767;margin-top: 5rpx;}
	.jipiao-list-view .jipiao-options .info-view .price-view{}
	.price-view .price-name{font-size: 26rpx;color: #353535;margin-top: 5rpx;}
	.price-view .price-num{color: #ff771b;font-weight: bold;}
	.jipiao-list-view .jipiao-options .jipiao-introduce{width: 100%;text-align: center;font-size: 26rpx;color: #676767;padding-top: 15rpx;}
	/* 共享售卖 */
	.shared-selling-view{border-radius: 16rpx;padding: 25rpx;width: 100%;margin-top: 15rpx;background: #f9f9f9;}
	.shared-selling-view .shared-title{width: 100%;padding-bottom: 15rpx;}
	.shared-selling-view .shared-title .feiji-icon{width: 28rpx;height: 28rpx;margin-right: 5rpx;}
	.shared-selling-view .shared-title .feiji-icon image{width: 100%;height: 100%;font-size: 28rpx;color: #676767;}
	.shared-selling-view .shared-title .title-text-view{white-space: nowrap;overflow: hidden;text-overflow: ellipsis;}
	.shared-selling-view .sell-options{width: 100%;border-bottom: 1px #eeefef solid;align-items: center;justify-content: space-between;padding: 20rpx 15rpx;}
	.shared-selling-view .sell-options .sell-left-view{justify-content: flex-start;}
	.sell-left-view .sell-touxiang{width: 28rpx;height: 28rpx;margin-right: 10rpx;}
	.sell-left-view .sell-touxiang image{width: 100%;height: 100%;}
	.sell-left-view .sell-name{font-size: 26rpx;color: #676767;}
	.shared-selling-view .sell-options .sell-price{font-size: 30rpx;color: #ff771b;}
	.shared-selling-view .sell-options .jiantou-icon{width: 28rpx;height: 28rpx;margin-left: 5rpx;}
	.shared-selling-view .more-view{width: 100%;align-items: center;justify-content: center;padding: 15rpx 0rpx 0rpx;font-size: 28rpx;color: #d6d6d6;}
	.shared-selling-view .more-view image{width: 40rpx;height: 40rpx;margin-left: 10rpx;}
  
  .popup__content{width: 100%;height:auto;position: relative;}
  .popup__content .popup-close{position: fixed;right: 20rpx;top: 20rpx;width: 56rpx;height: 56rpx;z-index: 11;}
  .popup__content .popup-close image{width: 100%;height: 100%;}
  .popup_title{font-size: 36rpx;font-weight: bold;line-height: 80rpx;}
  .popup-item-title{width: 180rpx;text-align: center;padding: 20rpx 10rpx;border-right: 2rpx solid #f1f1f1;}
  .popup-item-content{width: 160rpx;padding: 20rpx  10rpx;width: 100%;color:#999}


</style>