<template>
	<view class="page-view" v-if="isload">
		<view class="top-view flex-col">
			<view v-if="headlist && headlist.length>0" class="time-view flex flex-y-center">
				<view class="time-left-view flex">
					<scroll-view scroll-x style="flex:1;white-space: nowrap;">
            <view style="display: flex;justify-content: space-between;">
              <view style="display: flex;padding-right: 5rpx;width: 99%;">
                <block v-if="headlist && headlist.length>0 ">
                  <view v-for="(item,index) in headlist" :key="index" style="display: inline-block;width: 30%;">
                    <view :class="[item.issel? 'time-options-active':'','time-options flex-col']" @tap="toSelectDate(goDate,backDate)">
                      <view class="time-title">{{item.week}}</view>
                      <view class="time-num">{{item.daystr}}</view>
                      <view class="time-title">
                        去
                      </view>
                    </view>
                  </view>
                </block>
              </view>
              <view style="width: 10rpx;height:130rpx;background-color: #f1f1f1;"></view>
              <view style="display: flex;padding-left: 5rpx;width: 99%;">
                <block  v-if="headlist2 && headlist2.length>0 ">
                  <view v-for="(item,index) in headlist2" :key="index" style="display: inline-block;width: 30%;">
                    <view :class="[item.issel? 'time-options-active':'','time-options flex-col']" @tap="toSelectDate(goDate,backDate)">
                      <view class="time-title">{{item.week}}</view>
                      <view class="time-num">{{item.daystr}}</view>
                      <view class="time-title">
                        返
                      </view>
                    </view>
                  </view>
                </block>
              </view>
            </view>
					</scroll-view>
				</view>
				<view class="time-right-view flex flex-y-center flex-x-center">
					<image @tap="toSelectDate(goDate,backDate)" class="rili-icon" :src="pre_url+'/static/img/planeticket/rili.png'"></image>
				</view>
			</view>
		</view>
		<!-- <view style="width: 100%;height: 150px;"></view> -->
		<view class="jipiao-list-view flex-col">
      
      <block v-if="datalist && datalist.length>0">
        <!-- 直达机票 -->
        <block v-for="(item,index) in datalist" :key="index">
          <view @tap="goprice" :data-index="index" class="jipiao-options flex-col">
            <view class="info-view flex">
              <view v-if="item.airlinePic" class="info-touxiang">
                <image :src="item.airlinePic"></image>
              </view>
              <view class="info-details-view flex flex-y-center">
                <view class="location-view flex-col">
                  <view class="location-time">{{item.departTime}}</view>
                  <view class="location-name">{{item.deparAirportName}}{{item.departTerminal}}</view>
                </view>
                <view class="location-icon">
                  <!-- <view class="stop-tag">临沂停</view> -->
                  <image :src="pre_url+'/static/img/planeticket/jiantou.png'" mode="widthFix"></image>
                </view>
                <view class="location-view flex-col">
                  <view class="location-time">{{item.arriveTime}}</view>
                  <view class="location-name">{{item.arriveAirportName}}{{item.arriveTerminal}}</view>
                </view>
              </view>
              <view class="price-view flex-col flex-y-center">
                <view class="price-num flex flex-y-center">
                  <view style="font-size: 24rpx;">￥</view>
                  <view style="font-size: 38rpx;">{{item.minFdPrice}}</view>
                </view>
                <view class="price-name">
                  {{item.levelname}}
                </view>
              </view>
            </view>
            <view class="jipiao-introduce">
              {{item.airlineName}}{{item.no}}| {{item.planeCName}} ({{item.planeTypeName}}) | {{item.flightTime}}
            </view>
          </view>
        </block>
      </block>
      
      <nodata v-if="nodata" text="暂无航班信息"></nodata>
		</view>
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
				timeIndex:1,
        
        fromCityname:'',//出发地名称
        fromCity:'',//出发地三字码
        toCityname:'',//目的地名称
        toCity:'',//目的地三字码
        goDate:'',
        backDate:'',
        
        headlist:'',
        headlist2:'',
        datalist:'',
        searchNo:'',
        showday :0
			}
		},
    onLoad: function (opt) {
      var that = this;
    	that.opt = app.getopts(opt);
      
      that.fromCityname= that.opt.fromCityname || '';
      that.fromCity = that.opt.fromCity || '';
      that.toCityname = that.opt.toCityname || '';
      that.toCity = that.opt.toCity || '';
      that.goDate = that.opt.goDate || '';
      that.backDate = that.opt.backDate || '';
      
      uni.setNavigationBarTitle({
      	title: that.fromCityname + ' —— ' +that.toCityname
      });
    	
    },
    onShow:function(){
      var that = this;
      
      uni.$on('selectedDate',function(data,otherParam){
        if(otherParam == 2){
          var e = {
            currentTarget:{
              dataset:{
                daytime:dateStr,
                daytime2:dateStr2
              }
            }
          }
          that.changeTime(e);
        }
      })
      that.getdata();
    },
		methods:{
      getdata: function () {
        var that = this;
      	that.nodata = false;
      	that.nomore = false;
      	app.showLoading('加载中');
        app.post('ApiHanglvfeike/searchticket2', {fromCity:that.fromCity,toCity:that.toCity,goDate:that.goDate,backDate:that.backDate}, function (res) {
      		app.showLoading(false);
          if(res.status == 1){
            that.headlist  = res.headlist || '';
            that.headlist2 = res.headlist2 || '';
            that.datalist = res.datalist || '';
            if(!that.datalist || that.datalist.length<=0){
              that.nodata  = true;
            }
            that.searchNo = res.searchNo || '';
            that.showday  = res.showday || 0;
            
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
        console.log(e)
        var that = this;
        // var headlist = that.headlist;
        // var len = headlist.length;
        // for(var i= 0;i<len;i++){
        // headlist[i]['issel'] = false;
        // }
        // var index = e.currentTarget.dataset.index;
        // headlist[index]['issel'] = true;
        // that.headlist = headlist;
        
        that.goDate   = e.currentTarget.dataset.daytime;
        that.backDate = e.currentTarget.dataset.daytime2;
        that.headlist = [];
        that.headlist2= [];
        that.datalist = [];
        that.getdata();
			},
      toSelectDate(startdate,enddate){
        var that = this;
        app.goto('/pagesExt/checkdate/checkDate?startdate='+startdate+'&enddate='+enddate+'&dayin=0&dayin2='+that.showday+'&type=1&otherParam=2&t_mode=3');
      },
      goprice:function(e){
        var that = this;
        var datalist = that.datalist;
        var index  = e.currentTarget.dataset.index;
        var url = 'detailsprice?flightNo='+datalist[index].no+'&cabinNo='+datalist[index].cabinNo+'&deparAirportName='+datalist[index].deparAirportName+'&departTerminal='+datalist[index].departTerminal+'&arriveAirportName='+datalist[index].arriveAirportName+'&arriveTerminal='+datalist[index].arriveTerminal;
            url += '&airlineName='+datalist[index].airlineName+'&no='+datalist[index].no+'&planeCName='+datalist[index].planeCName+'&planeTypeName='+datalist[index].planeTypeName+'&flightTime='+datalist[index].flightTime+'&departTime='+datalist[index].departTime+'&arriveTime='+datalist[index].arriveTime+'&taxFee='+datalist[index].taxFee+'&fuelFee='+datalist[index].fuelFee;
            url += '&searchNo='+that.searchNo+'&fromCityname='+that.fromCityname+'&toCityname='+that.toCityname+'&fromDate='+that.fromDate; 
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
	.top-view .time-view .time-left-view{width: calc(100% - 104rpx);}
	.top-view .time-view .time-left-view .time-options{width: 100%;height: 130rpx;border-radius: 10rpx;justify-content: space-between;padding: 5rpx;}
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
</style>