<template>
	<view class="page-view" v-if="isload">
		<view class="top-view flex-col">
			<view v-if="headlist && headlist.length>0" class="time-view flex flex-y-center">
				<view class="time-left-view flex">
					<scroll-view scroll-x style="flex:1;white-space: nowrap;">
						<block v-for="(item,index) in headlist" :key="index">
							<view style="display: inline-block;width: 20%;">
								<view :class="[item.issel? 'time-options-active':'','time-options flex-col']" @tap="changeT" :data-index="index" :data-daytime="item.daytime">
									<view class="time-title">{{item.week}}</view>
									<view class="time-num">{{item.daystr}}</view>
								</view>
							</view>
						</block>
					</scroll-view>
				</view>
				<view class="time-right-view flex flex-y-center flex-x-center">
					<image @tap="toSelectDate(changeTime,1)" class="rili-icon" :src="pre_url+'/static/img/planeticket/rili.png'"></image>
				</view>
			</view>
		</view>
		<!-- <view style="width: 100%;height: 150px;"></view> -->
		<view class="jipiao-list-view flex-col">
      <block v-if="codelist">
          <view style="width: 100%;padding: 20rpx;background-color: #fff;border-top:2rpx solid #f1f1f1;display: flex;justify-content: space-between;">
              <view style="width: 120rpx;">改签类型:</view>
              <view>
                <picker @change="codeChange" :value="codeindex" :range="codelist" range-key='msg' style="width: 100%;">
                  <view style="width: 100%;display: flex;justify-content: space-between;align-items: center;">
                    <view style="max-width: 450rpx;">{{code_msg}}</view>
                    <image :src="pre_url+'/static/img/arrowright.png'" style="width: 30rpx;height: 30rpx;"></image>
                  </view>
                </picker>
              </view>
          </view>
      </block>
      <block v-if="datalist">
        <!-- 直达机票 -->
        <block v-for="(item,index) in datalist" :key="index" >
          <view v-if="index == codeindex" class="datacontent" style="margin-bottom: 20rpx;">
            <block v-for="(item2,index2) in item.flights" :key="index2">
              <view @tap="goprice" :data-index="index" :data-index2="index2" class="jipiao-options flex-col">
                  <view class="info-view flex">
                    <view v-if="item2.airlinePic" class="info-touxiang">
                      <image :src="item2.airlinePic"></image>
                    </view>
                    <view class="info-details-view flex flex-y-center">
                      <view class="location-view flex-col">
                        <view class="location-time">{{item2.startTime}}</view>
                        <view class="location-name">{{item2.departAirport}}{{item2.departTerminal}}</view>
                      </view>
                      <view class="location-icon">
                        <view v-if="item2.nonstop" class="stop-tag">停</view>
                        <image :src="pre_url+'/static/img/planeticket/jiantou.png'" mode="widthFix"></image>
                        <view v-if="item2.stopname" style="font-size: 24rpx;text-align: center;color: #676767;">{{item2.stopname}}</view>
                      </view>
                      <view class="location-view flex-col">
                        <view class="location-time">{{item2.endTime}}</view>
                        <view class="location-name">{{item2.arriveAirport}}{{item2.arriveTerminal}}</view>
                      </view>
                    </view>
                    <view class="price-view flex-col flex-y-center">
                      <view @tap="gobuy" :data-index="index" style="border-radius: 8rpx;overflow: hidden;padding: 4rpx;width: 90rpx;" :style="'text-align:center;background-color:'+t('color1')">
                        <block v-if="item2.showstock">
                          <view style="color: #fff;padding: 6rpx 0;">选择</view>
                          <view :style="'font-size: 24rpx;;color:'+t('color1')+';background-color:#fff'">剩{{item2.stock}}张</view>
                        </block>
                        <block v-else>
                          <view style="color: #fff;padding: 20rpx 0;">选择</view>
                        </block>
                      </view>
                    </view>
                  </view>
                  <view :data-index2="-1" class="jipiao-introduce" style="text-align: left;">
                    {{item2.airlineName}}{{item2.flightNo}}| {{item2.planeCName}} <text v-if="item2.flightTime"> | {{item2.flightTime}}</text>
                  </view>
                  <view style="margin-top: 10rpx;color: #999;">
                   <view v-if="item2.showFee">
                     <block v-if="item2.gqFee>0">手续费<text :style="'margin:0 10rpx 0 2rpx;color:'+t('color1')">￥{{item2.gqFee}}/人</text> </block>
                     <block v-if="item2.upgradeFee>0">升舱费<text :style="'margin:0 10rpx 0 2rpx;color:'+t('color1')">￥{{item2.upgradeFee}}/人</text> </block>
                     <block v-if="item2.allFee>0">总收费<text :style="'margin:0 10rpx 0 2rpx;color:'+t('color1')">￥{{item2.allFee}}/人</text></block>
                   </view>
                   <view v-if="item2.showchildFee">
                      <block v-if="item2.childGqFee>0">儿童手续费<text :style="'margin:0 10rpx 0 2rpx;color:'+t('color1')">￥{{item2.childGqFee}}/人</text> </block>
                      <block v-if="item2.childUpgradeFee>0">儿童升舱费<text :style="'margin:0 10rpx 0 2rpx;color:'+t('color1')">￥{{item2.childUpgradeFee}}/人</text> </block>
                      <block v-if="item2.childAllFee>0">儿童总费用	<text :style="'margin:0 10rpx 0 2rpx;color:'+t('color1')">￥{{item2.childAllFee}}/人</text></block>
                   </view>
                  </view>
              </view>
              
            </block>
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
        menuindex: -1,
				timeIndex:1,
				
				headlist:'',
        datalist:'',
        orderid : 0,
        ogids :'',
        changeTime:'',
				showday:0,
        
        codelist:'',
        codeindex:0,
        codelist:[],//店铺列表
        code:'',//服务类型
        code_msg:'',//服务类型名称

			}
		},
    onLoad: function (opt) {
      var that = this;
    	that.opt = app.getopts(opt);
      
      that.orderid= that.opt.orderid || '';
      that.ogids = that.opt.ogids || '';
    	
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
          that.changeT(e);
        }
      })
      that.getdata();
    },
		methods:{
      getdata: function () {
        var that = this;
      	that.nodata = false;
      	that.nomore = false;
        app.removeCache('changesdata');
      	app.showLoading('加载中');
        app.post('ApiHanglvfeike/applychange', {orderid:that.orderid,ogids:that.ogids,changeTime:that.changeTime}, function (res) {
      		app.showLoading(false);
          if(res.status == 1){
            that.headlist = res.headlist || '';
            that.datalist = res.datalist || '';
            that.codelist = res.codelist || '';
            that.code_msg = res.code_msg || '';
            that.changeTime  = res.changeTime || '';
						that.showday  = res.showday;
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
			changeT(e){
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
        
        that.changeTime  = e.currentTarget.dataset.daytime;
        that.headlist = [];
        that.datalist = [];
        that.getdata();
			},
      toSelectDate(date,otherParam){
        var that = this;
        app.goto('/pagesExt/checkdate/checkDate?date='+date+'&dayin=0&dayin2='+that.showday+'&type=1&otherParam='+otherParam);
      },
      goprice:function(e){
        var that = this;
        var datalist = that.datalist;
        var index  = e.currentTarget.dataset.index;
				var index2 = e.currentTarget.dataset.index2;
				var code   = datalist[index]['code'];
        var changesdata = datalist[index]['flights'][index2];
        app.setCache('changesdata', changesdata);
        var url = 'changebuy?orderid='+that.orderid+'&ogids='+that.ogids+'&changeTime='+that.changeTime+'&code='+code; 
        app.goto(url);
      },
      codeChange:function(e){
          var that = this;
          var codeindex = e.detail.value;
          that.codeindex = codeindex;
          that.code_msg = that.codelist[codeindex]['msg'];
          that.code     = that.codelist[codeindex]['code'];
      },
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
	.top-view .time-view .time-left-view .time-options{width: 100%;height: 110rpx;border-radius: 10rpx;justify-content: space-between;padding: 5rpx;}
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
	.info-details-view .location-icon .stop-tag{border: 1px #9c9c9c solid;position: absolute;top:0rpx;left: 50%;transform: translateX(-50%);
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
  
  .datacontent .item{line-height: 44rpx;width: 92%;margin: 10rpx auto;font-weight: bold;border-top:2rpx solid #ddd ;padding: 20rpx 0 10rpx 0}
</style>