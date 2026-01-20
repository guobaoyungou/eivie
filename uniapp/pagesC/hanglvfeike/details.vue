<template>
	<view class="page-view" v-if="isload">
		<view class="top-view flex-col">
			<view v-if="headlist && headlist.length>0" class="time-view flex flex-y-center">
				<view class="time-left-view flex">
					<scroll-view scroll-x style="flex:1;white-space: nowrap;">
						<block v-for="(item,index) in headlist" :key="index">
							<view style="display: inline-block;width: 20%;">
								<view :class="[item.issel? 'time-options-active':'','time-options flex-col']" @tap="changeTime" :data-index="index" :data-daytime="item.daytime">
									<view class="time-title">{{item.week}}</view>
									<view class="time-num">{{item.daystr}}</view>
									<view class="time-title">
                    <text v-if="item.price>=0">￥{{item.price}}</text>
                    <text v-else>查看</text>
                  </view>
								</view>
							</view>
						</block>
					</scroll-view>
				</view>
				<view class="time-right-view flex flex-y-center flex-x-center">
					<image @tap="toSelectDate(fromDate,1)" class="rili-icon" :src="pre_url+'/static/img/planeticket/rili.png'"></image>
				</view>
			</view>
		</view>
		<!-- <view style="width: 100%;height: 150px;"></view> -->
		<view class="jipiao-list-view flex-col">
      
      <block v-if="datalist && datalist.length>0">
        <!-- 直达机票 -->
        <block v-for="(item,index) in datalist" :key="index">
          <view  v-if="item.showstatus" class="jipiao-options flex-col" style="position: relative;">
            <view v-if="item.minSalePrice<=minprice" class="minpirce-wc" >
              <view class="minpirce-title" >今日低价</view>
            </view>
            <view style="position: relative;">
              <view @tap="goprice" :data-index="index" :data-index2="-1" class="info-view flex">
                <view v-if="item.airlinePic" class="info-touxiang">
                  <image :src="item.airlinePic"></image>
                </view>
                <view class="info-details-view flex flex-y-center">
                  <view class="location-view flex-col">
                    <view class="location-time">{{item.departTime}}</view>
                    <view class="location-name">{{item.deparAirportName}}{{item.departTerminal}}</view>
                  </view>
                  <view class="location-icon">
                    <view v-if="item.stopname" class="stop-tag">停</view>
                    <image :src="pre_url+'/static/img/planeticket/jiantou.png'" mode="widthFix"></image>
                    <view v-if="item.stopname" style="font-size: 24rpx;text-align: center;color: #676767;">{{item.stopname}}</view>
                  </view>
                  <view class="location-view flex-col">
                    <view class="location-time">{{item.arriveTime}}</view>
                    <view class="location-name">{{item.arriveAirportName}}{{item.arriveTerminal}}</view>
                  </view>
                </view>
                <view class="price-view flex-col flex-y-center">
                  <view class="price-num flex flex-y-center">
                    <view style="font-size: 24rpx;">￥</view>
                    <view style="font-size: 38rpx;">{{item.minSalePrice}}</view>
                  </view>
                  <view class="price-name">
                    {{item.levelname}}
                  </view>
                </view>
              </view>
              <view @tap="goprice" :data-index="index" :data-index2="-1" class="jipiao-introduce">
                {{item.airlineName}}{{item.no}}| {{item.planeCName}} ({{item.planeTypeName}}) | {{item.flightTime}}
              </view>
              <!-- 共享售卖 -->
              <block v-if="item.sharedata && item.sharedata.length>0">
                <view  class="shared-selling-view flex-col">
                  <view class="shared-title flex flex-y-center">
                    <image class="feiji-icon" :src="pre_url+'/static/img/planeticket/feiji.png'"></image>
                    <view class="title-text-view">此航班还有{{item.sharename}}等<text :style="'color:'+t('color1')">共享</text>售卖</view>
                  </view>
                  <block  v-for="(item2,index2) in item.sharedata" :key="index2" >
                    <view @tap.stop="goprice" :data-index="index" :data-index2="index2" class="sell-options flex" :style="index2>=2 && !item.showmore?'display:none':'display:flex' ">
                      <view class="sell-left-view flex flex-y-center">
                        <view v-if="item2.airlinePic" class="sell-touxiang">
                          <image :src="item2.airlinePic"></image>
                        </view>
                        <view class="sell-name">
                          {{item.airlineName}} {{item.no}}
                        </view>
                      </view>
                      <view class="flex flex-y-center">
                        <view class="sell-price">￥{{item.minSalePrice}}</view>
                        <image class="jiantou-icon" :src="pre_url+'/static/img/planeticket/arrowright2.png'"></image>
                      </view>
                    </view>
                  </block>
                  <view  v-if="item.sharenum>2 && !item.showmore" @tap.stop="goshowmore"  :data-index="index" class="more-view flex">
                    <view>展开更多共享</view>
                    <image :src="pre_url+'/static/img/location/down-grey.png'"></image>
                  </view>
                  <view v-if="item.sharenum>2 && item.showmore" @tap.stop="goshowmore" :data-index="index" class="more-view flex">
                    <view>收起更多共享</view>
                    <image :src="pre_url+'/static/img/location/up-grey.png'"></image>
                  </view>
                </view>
              </block>
            </view>
          </view>
        </block>
      </block>
      
      <nodata v-if="nodata" text="暂无航班信息"></nodata>
		</view>
    
    <view style="width: 100%;height: 90rpx;"></view>
    <view style="position: fixed;bottom: 0;left: 0;width: 100%;display: flex;justify-content: space-around;background-color: #fff;padding: 20rpx ;">
    	<view @tap.stop="sortClick" data-field="price" :data-order="order=='asc'?'desc':'asc'" style="display: flex;align-items: center;">
    		<view :style="field=='price'?'color:'+t('color1'):''">价格</view>
        <view>
          <view class="iconfont iconshangla" :style="field=='price'&&order=='asc'?'color:'+t('color1'):''"></view>
          <view class="iconfont icondaoxu" :style="field=='price'&&order=='desc'?'color:'+t('color1'):''"></view>
        </view>
    	</view>
    	<view @tap.stop="sortClick" data-field="time" :data-order="order=='asc'?'desc':'asc'" style="display: flex;align-items: center;">
    		<view :style="field=='time'?'color:'+t('color1'):''">出发时间</view>
        <view>
          <view class="iconfont iconshangla" :style="field=='time'&&order=='asc'?'color:'+t('color1'):''"></view>
          <view class="iconfont icondaoxu" :style="field=='time'&&order=='desc'?'color:'+t('color1'):''"></view>
        </view>
    	</view>
    	<view @click.stop="showDrawer('showRight')">筛选 <text :class="'iconfont iconshaixuan ' + (showfilter?'active':'')"></text></view>
    </view>
    
    <uni-drawer ref="showRight" mode="right" @change="change($event,'showRight')" :width="280">
    	<scroll-view scroll-y="true" class="filter-scroll-view filter-page">
    		<view class="filter-scroll-view-box">
    			<view class="search-filter" style="padding-bottom: 150rpx;">
    				<view class="filter-title">筛选</view>

            <block v-if="departTimes && departTimes.length>0">
              <view class="filter-content-title">起飞时间</view>
              <!-- 起飞时间单选 -->
              <view class="search-filter-content">
                <view class="filter-item" :style="seldepartTimes2===''?'color:'+t('color1')+';background:rgba('+t('color1rgb')+',0.1)':''" @tap.stop="departTimesClick" data-item="all">全部</view>
                <block v-for="(item, index) in departTimes" :key="index">
                  <view class="filter-item" :style="seldepartTimes2==item?'color:'+t('color1')+';background:rgba('+t('color1rgb')+',0.1)':''" @tap.stop="departTimesClick" :data-item="item">{{item}}</view>
                </block>
              </view>
            </block>
            
    				<block v-if="planeTypes && planeTypes.length>0">
              <view class="filter-content-title">机型</view>
              <view class="search-filter-content">
                <view class="filter-item" :style="selplaneTypes2===''?'color:'+t('color1')+';background:rgba('+t('color1rgb')+',0.1)':''" @tap.stop="planeTypesClick" data-item="all">全部</view>
                <block v-for="(item, index) in planeTypes" :key="index">
                  <view class="filter-item" :style="selplaneTypes2==item.type?'color:'+t('color1')+';background:rgba('+t('color1rgb')+',0.1)':''" @tap.stop="planeTypesClick" :data-item="item.type">{{item.name}}</view>
                </block>
              </view>
    				</block>
            
            <block v-if="airlines && airlines.length>0">
              <view class="filter-content-title">航空公司</view>
              <view class="search-filter-content">
                <view class="filter-item" :style="selairlines2===''?'color:'+t('color1')+';background:rgba('+t('color1rgb')+',0.1)':''" @tap.stop="airlinesClick" data-item="all">全部</view>
                <block v-for="(item, index) in airlines" :key="index">
                  <view class="filter-item" :style="selairlines2==item.code?'color:'+t('color1')+';background:rgba('+t('color1rgb')+',0.1)':''" @tap.stop="airlinesClick" :data-item="item.code">{{item.name}}</view>
                </block>
              </view>
            </block>
    
    				<view class="search-filter-btn">
    					<view class="btn" @tap="filterReset">重置</view>
    					<view class="btn2" :style="{background:t('color1')}" @tap="filterConfirm">确定</view>
    				</view>
    			</view>
    		</view>
    	</scroll-view>
    </uni-drawer>
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
        
        fromCityname:'',//出发地名称
        fromCity:'',//出发地三字码
        toCityname:'',//目的地名称
        toCity:'',//目的地三字码
        fromDate:'',
        
        headlist:'',//头部时间列表
        datalist:'',//中间数据列表
        searchNo:'',
        minprice:'',
        showday :0,
        
        departTimes:'',//起飞时间段筛选
        seldepartTimes:'',//选择的时间段筛选
        seldepartTimes2:'',//待确认选择的时间段筛选
        
        planeTypes:'',//机型筛选
        selplaneTypes:'',//选择的机型
        selplaneTypes2:'',//待确认选择的机型
        
        airlines:'',//航空公司筛选
        selairlines:'',//选择的航空公司
        selairlines2:'',//待确认选择的航空公司
        
        field:'',
        order:'',
        showfilter: "",//筛选
			}
		},
    onLoad: function (opt) {
      var that = this;
    	that.opt = app.getopts(opt);
      
      that.fromCityname= that.opt.fromCityname || '';
      that.fromCity = that.opt.fromCity || '';
      that.toCityname = that.opt.toCityname || '';
      that.toCity = that.opt.toCity || '';
      that.fromDate = that.opt.fromDate || '';
      
      uni.setNavigationBarTitle({
      	title: that.fromCityname + ' —— ' +that.toCityname
      });
    },
    onShow:function(){
      var that = this;
      uni.$on('selectedDate',function(data,otherParam){
        console.log(data)
        if(otherParam){
          var dateStr = data.startStr.dateStr;
          var e = {
            currentTarget:{
              dataset:{
                daytime:dateStr
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
        app.removeCache('flightsdata');
      	app.showLoading('加载中');
        app.post('ApiHanglvfeike/searchticket', {fromCity:that.fromCity,toCity:that.toCity,fromDate:that.fromDate}, function (res) {
      		app.showLoading(false);
          if(res.status == 1){
            that.headlist = res.headlist || '';
            that.datalist = res.datalist || '';
            if(!that.datalist || that.datalist.length<=0){
              that.nodata  = true;
            }
            that.searchNo = res.searchNo || '';
            that.minprice = res.minprice || '';
            that.showday  = res.showday || 0;
            
            that.departTimes = res.departTimes || '';//起飞时间段筛选
            that.planeTypes = res.planeTypes || '';//机型筛选
            that.airlines = res.airlines || '';//航空公司筛选

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
        
        that.fromDate  = e.currentTarget.dataset.daytime;
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
        if(index2>=0){
          var flightsdata = datalist[index]['sharedata'][index2];
        }else{
          var flightsdata = datalist[index];
          flightsdata['sharedata'] = '';
        }
        app.setCache('flightsdata', flightsdata);
        var url = 'detailsprice?searchNo='+that.searchNo+'&fromCityname='+that.fromCityname+'&toCityname='+that.toCityname+'&fromDate='+that.fromDate; 
        app.goto(url);
      },
      goshowmore:function(e){
        var that = this;
        var datalist = that.datalist;
        var index  = e.currentTarget.dataset.index;
        that.datalist[index]['showmore'] = !that.datalist[index]['showmore'];
      },
      sortClick: function (e) {
        var that = this;
        var t = e.currentTarget.dataset;
        var field = that.field = t.field;
        var order = that.order = t.order;
        
        var datalist = that.datalist;
        
        if(field == 'price'){
          if(order == 'asc'){
            datalist.sort((a, b) => a.minSalePrice - b.minSalePrice);
          }else{
             datalist.sort((a, b) => b.minSalePrice - a.minSalePrice);
          }
        }else if(field == 'time'){
          if(order == 'asc'){
            datalist.sort((a, b) => a.departTimestr - b.departTimestr);
          }else{
             datalist.sort((a, b) => b.departTimestr - a.departTimestr);
          }
        }
        that.datalist = datalist;
      },
      // 打开窗口
      showDrawer(e) {
      	console.log(e)
      	this.$refs[e].open()
      },
      // 关闭窗口
      closeDrawer(e) {
      	this.$refs[e].close()
      },
      // 抽屉状态发生变化触发
      change(e, type) {
      	console.log((type === 'showLeft' ? '左窗口' : '右窗口') + (e ? '打开' : '关闭'));
      	this[type] = e
      },

      departTimesClick: function (e) {
        var that = this;
        var seldepartTimes2 = e.currentTarget.dataset.item;
        that.seldepartTimes2 = !seldepartTimes2 || seldepartTimes2=='all'?'':seldepartTimes2;
        console.log(that.seldepartTimes2)
      },
      planeTypesClick: function (e) {
        var that = this;
        var selplaneTypes2 = e.currentTarget.dataset.item;
        that.selplaneTypes2 = !selplaneTypes2 || selplaneTypes2=='all'?'':selplaneTypes2;
        console.log(that.selplaneTypes2)
      },
      airlinesClick: function (e) {
        var that = this;
        var selairlines2 = e.currentTarget.dataset.item;
        that.selairlines2 = !selairlines2 || selairlines2=='all'?'':selairlines2;
      },

      filterConfirm(){
        var that = this;
      	that.seldepartTimes = that.seldepartTimes2;
      	that.selplaneTypes = that.selplaneTypes2;
      	that.selairlines = that.selairlines2;
        that.dealsel();
      	that.$refs['showRight'].close();
      },
      filterReset(){
      	var that = this;
      	that.seldepartTimes2 = '';
      	that.selplaneTypes2 = '';
      	that.selairlines2 = '';
      },
      filterClick: function () {
        this.showfilter = !this.showfilter
      },
      dealsel:function(){
        var that = this;
        //处理筛选
        var datalist = that.datalist;
        var len = datalist.length;
        if(len>0){
          var selnum = 0;
          for(var i = 0;i<len;i++){
            datalist[i]['showstatus'] = true;
            //筛选起飞时间
            if(that.seldepartTimes !== ''){
              var seldepartTimeArr = that.seldepartTimes.split('-');
              if(datalist[i]['departTime']<seldepartTimeArr[0] || datalist[i]['departTime']>seldepartTimeArr[1]){
                datalist[i]['showstatus'] = false
              }
            }
            
            //筛选机型筛选
            if(that.selplaneTypes !== ''){
              if(datalist[i]['planeType'] != that.selplaneTypes){
                datalist[i]['showstatus'] = false
              }
            }
            
            //筛选机型筛选
            if(that.selairlines !== ''){
              if(datalist[i]['airline'] != that.selairlines){
                datalist[i]['showstatus'] = false
              }
            }
            if(datalist[i]['showstatus']) selnum ++;
          }
          if(selnum == 0){
            that.nodata = true;
          }else{
            that.nodata = false;
          }
        }
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
	.info-details-view .location-icon{width: 120rpx;position: relative;margin: 0rpx 5rpx 15rpx;}
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
  
  .iconshangla{padding: 0 6rpx;font-size: 20rpx;color:#7D7D7D}
  .icondaoxu{padding: 0 6rpx;font-size: 20rpx;color:#7D7D7D}
  .iconshaixuan{margin-left:10rpx;font-size:22rpx;color:#7d7d7d}
  
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
  
  .minpirce-wc{position:absolute; top:0;left: 0;z-index: 0;width: 100%;height:100rpx;background-image: linear-gradient(to bottom , #ECF9F2 30%, #ffffff 70%);border-radius: 16rpx;}
  .minpirce-title{color: #5FD195;border-radius:16rpx 0 20rpx 0 ;border-boo-right-radius:;background-color: #DDF3E7;font-size: 24rpx;width:130rpx ;text-align: center;line-height: 40rpx;font-weight: bold;}
</style>