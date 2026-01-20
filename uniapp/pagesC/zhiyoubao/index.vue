<template>
	<view class="page-view" v-if="isload">
		<view class="top-view flex-col">
			<!-- <view v-if="headlist && headlist.length>0" class="time-view flex flex-y-center">
				<view class="time-left-view flex">
					<scroll-view scroll-x style="flex:1;white-space: nowrap;">
						<block v-for="(item,index) in headlist" :key="index">
							<view style="display: inline-block;width: 20%;">
								<view :class="[item.issel? 'time-options-active':'','time-options flex-col']" :style="item.issel?'background-color:'+t('color1'):''" @tap="changeTime" :data-index="index" :data-daytime="item.daytime">
									<view class="time-title">{{item.week}}</view>
									<view class="time-num">{{item.daystr}}</view>
								</view>
							</view>
						</block>
					</scroll-view>
				</view>
				<view class="time-right-view flex flex-y-center flex-x-center">
					<image @tap="toSelectDate(playDate,1)" class="rili-icon" :src="pre_url+'/static/img/planeticket/rili.png'"></image>
				</view>
			</view> -->
     <!-- date='+date+'&dayin=0&dayin2='+that.showday+'&type=1&otherParam='+otherParam -->
      <scroll-view scroll-y style="height: 676rpx;width: 100%;position: relative;padding-bottom: 20rpx;">
        <calendar :is-show="true" :isFixed='false' :between-start="betweenStart" :between-end="betweenEnd" initMonth="24" :ys-num="ysNum" :choose-type="chooseType" :start-date="startDate" :end-date="endDate"  :tip-data="tipData" :mode="mode" :showhas="true" :dayPerforms="dayPerforms" :themeColor="t('color1')" @callback="getCalendar"></calendar>
      </scroll-view>
		</view>
		<!-- <view style="width: 100%;height: 150px;"></view> -->
    <view v-if="datalist && datalist.length>0" style="width: 95%;margin: 20rpx auto">

      <view v-for="(item,index) in datalist" :key="index" @tap="goto" :data-url="'detail?id='+item.id+'&playDate='+playDate" class="content">
        <view style="display: flex;">
          <view v-if="item.pic" class="content-pic">
            <image :src="item.pic" mode="widthFix" style="width: 100%;border-radius: 10rpx 10rpx;height: auto;min-height: 0;"></image>
          </view>
          <view style="margin-left: 10rpx;">
            <view class="content-title">{{item.title}}</view>
            <view class="content-title2">日期：{{item.performDate}}</view>
            <view class="content-title2">时间：{{item.performTime}}</view>
            <view class="title2" :style="'color:'+t('color1')">
              <text v-if="item.price>=0">￥{{item.price}}起</text>
              <text v-else>暂无价格</text>
            </view>
            <!-- <view style="width: 100%;display: flex;justify-content: flex-end;">
              <view @tap="gobuy" :data-index="index" style="border-radius: 8rpx;overflow: hidden;padding: 4rpx;width: 120rpx;" :style="'text-align:center;background-color:'+t('color1')">
                <view style="color: #fff;padding: 10rpx 0;">去购买</view>
              </view>
            </view> -->
          </view>
        </view>
      </view>
    </view>
    <nodata v-if="nodata"></nodata>
    <nomore v-if="nomore"></nomore>
    <loading v-if="loading"></loading>
		<dp-tabbar :opt="opt" @getmenuindex="getmenuindex"></dp-tabbar>
		<popmsg ref="popmsg"></popmsg>
	</view>
</template>

<script>
  import calendar from './mobile-calendar-simple/Calendar.vue'
	var app = getApp();
	export default {
    components:{
        calendar
    },
		data(){
			return{
				opt:{},
				loading:false,
				isload: false,
				nodata:false,
				nomore: false,
        menuindex:-1,
        pagenum: 1,
        pre_url:app.globalData.pre_url,
    
				timeIndex:1,
        showid:0,
        performid:0,
        areaid:0,
        playDate:'',
        headlist:'',//头部时间列表
        datalist:'',//中间数据列表
        showday :0,
        
        //日期参数
        betweenStart:'',
        betweenEnd:'',
        startDate:'',
        dayPerforms:'',
        
        ysNum:'',
        chooseType:'' ,
        endDate:'',
        tipData:'',
        mode:1,
			}
		},
    onLoad: function (opt) {
      var that = this;
    	that.opt = app.getopts(opt);
      that.showid = that.opt.showid || 0;
      that.performid = that.opt.performid || 0;
      that.areaid = that.opt.areaid || 0;
      that.playDate = that.opt.playDate || '';
    },
    onShow:function(){
      var that = this;
      that.getdata();
    },
    onPullDownRefresh: function () {
    	this.pagenum = 1;
    	this.datalist = [];
    	this.getdata();
    },
    onReachBottom: function () {
      if (!this.nodata && !this.nomore) {
        this.pagenum = this.pagenum + 1;
        this.getdata();
      }
    },
		methods:{
      getdata: function (loadmore) {
      	if(!loadmore){
      		this.pagenum = 1;
      		this.datalist = [];
      	}
        var that = this;
        var pagenum = that.pagenum;
      	that.nodata = false;
      	that.nomore = false;
      	that.loading = true;
        app.post('ApiZhiyoubao/getPerforms', {showid:that.showid,performid:that.performid,areaid:that.areaid,playDate:that.playDate,pagenum: pagenum}, function (res) {
      		that.loading = false;
          if(res.status == 1){
            that.headlist = res.headlist || '';
            that.datalist = res.datalist || '';
            if(!that.datalist || that.datalist.length<=0){
              that.nodata  = true;
            }
            that.playDate = res.playDate || '';
            that.showday  = res.showday || 0;
            
            that.betweenStart= res.betweenStart;
            that.betweenEnd  = res.betweenEnd;
            that.startDate   = res.startDate;
            that.dayPerforms = res.dayPerforms;
            that.loaded();
          }else {
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
			changeTime(e,type=0){
        var that = this;

        that.playDate  = e.currentTarget.dataset.daytime;
        that.headlist = [];
        that.datalist = [];
        if(type == 0) that.getdata();
			},
      toSelectDate(date,otherParam){
        var that = this;
        app.goto('/pagesExt/checkdate/checkDate?date='+date+'&dayin=0&dayin2='+that.showday+'&type=1&otherParam='+otherParam);
      },
      goprice:function(e){
        var that = this;
        var datalist = that.datalist;
        var outPerformCode  = e.currentTarget.dataset.outPerformCode;
        var url = 'detailsprice?outPerformCode='+outPerformCode+'&playDate='+that.playDate; 
        app.goto(url);
      },
      getCalendar:function(data){
        var that = this;
        console.log(data)
        if(data){
          var dateStr = data.startStr.dateStr;
          var e = {
            currentTarget:{
              dataset:{
                daytime:dateStr
              }
            }
          }
          that.changeTime(e,1);
        }else{
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
              that.changeTime(e,1);
            }
          })
        }
        that.getdata();
      }
		}
	}
</script>

<style>
	.page-view{width: 100%;height: 100vh;}
	.top-view{width: 100%;background: #fff;}
	.top-view .address-view{justify-content: center;}
	.top-view .address-view .address-text{font-size: 40rpx;color: #676767;flex: 1;}
	.top-view .address-view .fangxiang-icon{width: 50rpx;height:50rpx;margin: 50rpx 40rpx;}
	.top-view .address-view .fangxiang-icon image{width: 50rpx;height:50rpx;}
	.top-view .time-view{width: 100%;justify-content: space-between;padding-bottom: 5rpx;}
	.top-view .time-view .time-left-view{width: calc(100% - 104rpx);}
	.top-view .time-view .time-left-view .time-options{width: 100%;height: 130rpx;border-radius: 8rpx;justify-content: space-between;padding: 20rpx 10rpx;}
	.top-view .time-view .time-left-view .time-options-active{color: #fff !important;}
	.top-view .time-view .time-left-view .time-options-active .time-title{color: #fff !important;}
	.top-view .time-view .time-left-view .time-options-active .time-num{color: #fff !important;}
	.time-options .time-title{width: 100%;text-align: center;font-size: 26rpx;color: #838383;}
	.time-options	.time-num{color: #333;font-size: 26rpx;width: 100%;text-align: center;}
	.top-view .time-view .time-right-view{width: 100rpx;height: 130rpx;box-shadow: -4px 0px 14px -14px rgba(0,0,0,.7);}
	.top-view .time-view .time-right-view .rili-icon{width: 46rpx;height: 46rpx;}
  
  .content{background-color: #fff;border-radius: 8rpx;padding: 20rpx;margin-bottom: 20rpx;}
  .content-pic{width: 150rpx;max-height: 300rpx;border-radius: 4rpx;overflow: hidden;}
  .content-title{font-weight: bold;line-height: 50rpx;max-height: 100rpx;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:2;overflow:hidden;word-break: break-all;}
  .content-title2{color: #666;line-height: 40rpx;margin-top: 5rpx;}

  .filter-page{height: 100%;}
  .filter-scroll-view{margin-top:var(--window-top)}
  .search-filter{display: flex;flex-direction: column;text-align: left;width:100%;flex-wrap:wrap;padding:0;}
  .filter-content-title{color:#999;font-size:28rpx;height:30rpx;line-height:30rpx;padding:0 30rpx;margin-top:30rpx;margin-bottom:10rpx}
  .filter-title{color:#BBBBBB;font-size:32rpx;background:#F8F8F8;padding:60rpx 0 30rpx 20rpx;}
  .search-filter-content{display: flex;flex-wrap:wrap;padding:10rpx 20rpx;}
  .search-filter-content .filter-item{background:#F4F4F4;border-radius:28rpx;color:#2B2B2B;font-weight:bold;margin:10rpx 10rpx;min-width:140rpx;height:56rpx;line-height:56rpx;text-align:center;font-size: 24rpx;padding:0 30rpx}
  .search-filter-content .close{text-align: right;font-size:24rpx;color:#ff4544;width:100%;padding-right:20rpx}
  .search-filter button .icon{margin-top:6rpx;height:54rpx;}
  .search-filter-btn{display:flex;padding:30rpx 30rpx 50rpx 30rpx;justify-content: space-between}
  .search-filter-btn .btn{width:240rpx;height:66rpx;line-height:66rpx;background:#fff;border:1px solid #e5e5e5;border-radius:33rpx;color:#2B2B2B;font-weight:bold;font-size:24rpx;text-align:center}
  .search-filter-btn .btn2{width:240rpx;height:66rpx;line-height:66rpx;border-radius:33rpx;color:#fff;font-weight:bold;font-size:24rpx;text-align:center}
</style>