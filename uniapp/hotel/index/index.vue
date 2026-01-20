<template>
	<view>
		<block v-if="isload">
			<view class='dp-banner'>
				<swiper class="dp-banner-swiper" :autoplay="true" :indicator-dots="false" :current="0" :circular="true" :interval="3000">
					<block v-for="(item,index) in set.pics"> 
						<swiper-item>
							<view @click="goto">
								<image :src="item" class="dp-banner-swiper-img" mode="widthFix"/>
							</view>
						</swiper-item>
					</block>
				</swiper> 
			</view>
			<!--  -->
			<view class="position-view">
				<view class="banner-poster">
					<!--<view class="poster-title">林地探索 心灵营地</view>
					<view class="poster-text">呼吸清新空气，品味天然甘露，享受烟火人间</view>
					<view class="poster-text">去有风的地方，在山林里自由奔跑</view>
					<view class="poster-but" :style="{backgroundColor:tColor('color1')}">立即预定</view>-->
				</view>
				<view class="reserve-view">
					<view class="tab-view">
						<scroll-view scroll-x class="scroll-class">
							<block v-for="(item,index) in catelist">
								<view :class="[index == 0 ? 'options-tab-active':'' , index == catelist.length-1 ? 'options-tab-active2':'', 'options-tab']" @click="tabChange(index)">
									{{item.name}}
									<view class="active-class-show" v-if="tabindex == index && tabindex == 0" :style="{color:t('color1')}">
										<view class="text-view">{{item.name}}</view>
										<view class="color-view" :style="{backgroundColor:tColor('color1')}"></view>
									</view>
									<view class="active-class-show-last" v-if="tabindex == catelist.length-1 && tabindex == index " :style="{color:t('color1')}">
										<view class="text-view">{{item.name}}</view>
										<view class="color-view" :style="{backgroundColor:tColor('color1')}"></view>
									</view>
									<view class="active-class-show-s" v-if="(tabindex != catelist.length-1) && (tabindex != 0) && tabindex == index " :style="{color:t('color1')}">
										<view class="text-view">{{item.name}}</view>
										<view class="color-view" :style="{backgroundColor:tColor('color1')}"></view>
									</view>
								</view>
							</block>
						</scroll-view>
					</view>
					<view style="height: 120rpx;background: #ebeef5;border-radius: 20rpx 20rpx 0rpx 0rpx;overflow: hidden;"></view>
					<view class="reserve-time-view" @tap="selectDate">
						<view class="time-view">
							<view class='time-title'>入住时间</view>
							<view class="flex flex-y-center" style="margin-top: 15rpx;align-items: flex-end;">
								<view class="date-time">{{startDate}}</view>
								<view class='time-title'>{{startWeek}}</view>
						</view>
						</view>
						<view class='statistics-view'>
							<view class="statistics-date">
								<view class="content-decorate left-c-d"></view>
								{{dayCount}}晚
								<view class="content-decorate right-c-d"></view>
							</view>
							<view class="color-line"></view>
						</view>
						<view class="time-view">
							<view class='time-title'>离店时间</view>
								<view class="flex flex-y-center" style="margin-top: 15rpx;align-items: flex-end;">
									<view class="date-time">{{endDate}}</view>
									<view class='time-title'>{{endWeek}}</view>
							</view>
						</view>
					</view>
					<!--  -->
					<view class="search-view">
						<input placeholder="输入位置/关键字" placeholder-style="color: rgba(123, 128, 133, 0.6);font-size: 28rpx;"  class="input-class" :value="keyword" @input="searchChange"/>
						<image :src="pre_url+'/static/img/arrowright.png'"></image>
					</view>
					<view class="search-but"  @tap="search" :style="{backgroundColor:tColor('color1')}">
						{{text['酒店']}}查询
					</view>
				</view>
				<!-- menu -->
				<view class="menu-view">
					<scroll-view scroll-x class="scroll-view-class" v-if="catelist.length > 3">
						<block v-for="(item,index) in catelist">
							<view style="display: inline-block;width: 21.5%;margin-right: 30rpx;">
								<view class="menu-options-scroll" @tap="goto" :data-url="'hotellist?cateid='+item.id">
									<image :src="item.pic"></image>
									<view class="menu-text-view">{{item.name}}</view>
								</view>
							</view>
						</block>
					</scroll-view>
					<block v-for="(item,index) in catelist" v-else>
						<view class="menu-options" @tap="goto" :data-url="'hotellist?cateid='+item.id">
							<image :src="item.pic"></image>
							<view class="menu-text-view">{{item.name}}</view>
						</view>
					</block>
				</view>
				<!--  -->
				<view class="hotels-list">
					<view class="hottitle">热门{{text['酒店']}}</view>
					<block v-for="(item,index) in datalist">
						<view class="hotels-options" @tap="goto" :data-url="'hoteldetails?id='+item.id">
							<view class="hotel-img">
								<image :src="item.pic" mode="aspectFill"></image>
							</view>
							<view class="hotel-info">
								<view class="hotel-title">{{item.name}}</view>
								<view class="hotel-address">{{item.address}}</view>
								<view class="hotel-characteristic">
									<block v-for="(items,indexs) in item.tag">
										<view class="characteristic-options" :style="'background:rgba('+t('color1rgb')+',0.05);color:'+tColor('color1')" v-if="indexs < 6">{{items}} </view>
									</block>
								</view>
								<view class="hotel-but-view">
									<view class="make-info">
										<view class="hotel-price" :style="{color:t('color1')}" v-if="item.min_daymoney">
											<view class="hotel-price-num">{{item.min_daymoney}}{{moneyunit}}</view>
											<view>/晚起</view>
										</view>
										<view class="hotel-price" :style="{color:t('color1')}" v-else>
											<view>￥</view>
											<view class="hotel-price-num">{{item.min_price}}</view>
											<view>起</view>
										</view>
										<view class="hotel-text">{{item.sales}}人已预定 </view>
									</view>
									
									<view class="hotel-make"  :style="'background:rgba('+t('color1rgb')+',0.8);color:#FFF'">预约</view>
								</view>
							</view>
						</view>
					</block>
				</view>
				<view style="height: 160rpx;"></view>
			</view>
			
			
	
			<!-- 选择日期弹窗 -->
			<view v-if="calendarvisible" class="popup__container">
				<view class="popup__overlay" @tap.stop="handleClickMask"></view>
				<view class="popup__modal">
					<view class="popup__title">
						<text class="popup__title-text">选择日期</text>
						<image :src="`${pre_url}/static/img/hotel/popupClose2.png`" class="popup__close" style="width:56rpx;height:56rpx;top:20rpx;right:20rpx" @tap.stop="handleClickMask"/>
					</view>
					<view class="popup__content">
						<view class="reserve-time-view" >
							<view class="time-view">
								<view class='time-title'>入住</view>
								<view class="flex flex-y-center" style="margin-top: 15rpx;align-items: flex-end;">
									<view class="date-time">{{startDate}}</view>
									<view class='time-title'>{{startWeek}}</view>
							</view>
							</view>
							<view class='statistics-view'>
								<view class="statistics-date">
									<view class="content-decorate left-c-d"></view>
									共{{dayCount}}晚
									<view class="content-decorate right-c-d"></view>
								</view>
								<view class="color-line"></view>
							</view>
							<view class="time-view">
								<view class='time-title'>离店</view>
									<view class="flex flex-y-center" style="margin-top: 15rpx;align-items: flex-end;">
										<view class="date-time">{{endDate}}</view>
										<view class='time-title'>{{endWeek}}</view>
								</view>
							</view>
						</view>
						<view class="calendar-view">
							<calendar :is-show="true" :isFixed='false' showstock='0' :start-date="starttime" :end-date="endtime" mode="2" @callback="getDate"  :themeColor="t('color1')"  :maxdays='maxdays' :initMonth="initMonth">
				
							</calendar>
						</view>
						<view class="choose-but-class" :style="'background: linear-gradient(90deg,rgba('+tColor('color1rgb')+',1) 0%,rgba('+tColor('color1rgb')+',1) 100%)'" @tap="popupClose">
							确认{{dayCount}}晚 
						</view>
					</view>
				</view>
			</view>
	
		</block>

		<loading v-if="loading"></loading>
		<dp-tabbar :opt="opt"  @getmenuindex="getmenuindex"></dp-tabbar>
		<popmsg ref="popmsg"></popmsg>
	</view>
</template>

<script>
	import calendar from '../mobile-calendar-simple/Calendar.vue'
	var app = getApp();
	export default {
		components:{
		    calendar
		},
		data(){
			return {
				opt:{},
				isload: false,
				bannerindex:0,
				set:[],
				cateList:[],
				tabindex:0,
				pre_url: app.globalData.pre_url,
				datalist:[],
				startDate:'',
				endDate:'',
				startWeek:'',
				endWeek:'',
				dayCount:1,
				starttime:'',
				endtime:'',
				keyword:'',
				cateid:'',
				text:[],
				calendarvisible:false,
				catelist:[],
				loading:false,
				maxdays:10,
				moneyunit:'元',
        initMonth:6,//日期插件显示多少个月
			}
		},
		onLoad: function (opt) {
			this.opt = app.getopts(opt);
			console.log('--------------------');
			console.log(this.opt);
			this.getdata();
		},
		methods:{
			getdata:function(e){
				var that=this
				app.post('ApiHotel/getsysset', {}, function (res) { 
						if(res.status==1){
							that.set=res.set
							that.catelist = res.catelist
							if(res.catelist.length>0){
									that.cateid = res.catelist[0].id
							}
							var starttime = app.getCache('startTime');
							var starttimestamp = new Date(starttime).getTime();
							var timestampnow = Date.now();

							var endtime = app.getCache('endTime');
							var endtimestamp = new Date(endtime).getTime();
				
							if(!starttime || timestampnow>starttimestamp){
									app.setCache('startTime',res.startday,30);
									starttime = app.getCache('startTime');
							}
							if(!endtime  || timestampnow>endtimestamp){
									app.setCache('endTime',res.endday,30);
									endtime = app.getCache('endTime');
							}
							var daycount = 1;
							starttimestamp = new Date(starttime).getTime();
							endtimestamp = new Date(endtime).getTime();
							daycount = (endtimestamp/1000-starttimestamp/1000)/86400;
							console.log(daycount);
							app.setCache('dayCount',daycount,30);
							
					
							var weekdays = ['周日', '周一', '周二', '周三', '周四', '周五', '周六'];
							var day = new Date(starttime).getDay();
							var day2 = new Date(endtime).getDay();
							var startWeek = weekdays[day];
							var endWeek = weekdays[day2];
							var startDate = starttime.substr(5).replace('-', '月');
							var endDate = endtime.substr(5).replace('-', '月');
							app.setCache('startDate',startDate,30);
							app.setCache('endDate',endDate,30);
							that.starttime = starttime;
							that.endtime = endtime;
							that.startDate = startDate
							that.endDate = endDate
							that.startWeek = startWeek
							that.endWeek = endWeek
							that.maxdays = res.maxdays
							that.text = res.text
							that.dayCount = daycount
							that.moneyunit = res.moneyunit
              that.initMonth = res.initMonth || 6;//日期插件显示的月份
							
							uni.setNavigationBarTitle({
								title:res.text['酒店']+'首页'
							});
							that.loaded();
							that.getdatalist()
						}
				 })
			},
			getdatalist: function (loadmore) {
				if(!loadmore){
					this.pagenum = 1;
					this.datalist = [];
				}
				var that = this;
				var pagenum = that.pagenum
				var cid = that.curCid;
				var bid = that.opt.bid ? that.opt.bid : '';
				var cpid = that.opt.cpid ? that.opt.cpid : '';
				var order = that.order;
				var field = that.field; 
				that.loading = true;
				that.nodata = false;
				that.nomore = false;
				app.post('ApiHotel/gethotels', {pagenum: pagenum,field: field,order: order,cid: cid}, function (res) { 
					that.loading = false;
					uni.stopPullDownRefresh();
					var data = res.data;
					if (data.length == 0) {
						if(pagenum == 1){
							that.nodata = true;
						}else{
							that.nomore = true;
						}
					}
					var datalist = that.datalist;
					var newdata = datalist.concat(data);
					that.datalist = newdata;
				});
			},
			handleClickMask:function(){
				this.calendarvisible = false;
			},
			tabChange(index){
				var that=this
				this.tabindex = index;
				that.cateid = that.catelist[index].id
				
			},
			selectDate:function(){
				// 选择日期弹窗-------------------------------------------------------------------------------------------
				//this.$refs.popupTime.open();
				this.calendarvisible = true;
			},
			getDate(date){
				console.log(date);
				var that=this
				if(date.dayCount){
						app.setCache('dayCount',date.dayCount,30)
						that.dayCount = date.dayCount;
				}
				if(date.startStr){
						var starttime =  date.startStr.dateStr
						app.setCache('startTime',starttime,30);
						var startDate = starttime.substr(5).replace('-', '月');
						app.setCache('startDate',startDate,30);
								
						that.starttime = starttime
						that.startDate = startDate
						that.startWeek = date.startStr.week
				}
				if(date.endStr){
					var endtime =  date.endStr.dateStr
					app.setCache('endTime',endtime,30);
					var endDate = endtime.substr(5).replace('-', '月');
					app.setCache('endDate',endDate,30);
						
					that.endtime = endtime
					that.endDate = endDate
					that.endWeek = date.endStr.week
				}			
			 },
			popupClose(){
				//this.$refs.popupTime.close();
				this.calendarvisible = false;
			},
			search: function () {
				var that=this;
				var cateid = that.cateid
				var keyword  = that.keyword
				app.goto('hotellist?keyword=' + keyword+'&cateid='+cateid);
			},
			searchChange: function (e) {
	
			  this.keyword = e.detail.value;
			},
			tColor(text){
				let that = this;
				if(text=='color1'){
					if(app.globalData.initdata.color1 == undefined){
						let timer = setInterval(() => {
							that.tColor('color1')
						},1000)
						clearInterval(timer)
					}else{
						return app.globalData.initdata.color1;
					}
				}else if(text=='color2'){
					return app.globalData.initdata.color2;
				}else if(text=='color1rgb'){
					if(app.globalData.initdata.color1rgb == undefined){
						let timer = setInterval(() => {
							that.tColor('color1rgb')
						},1000)
						clearInterval(timer)
					}else{
						var color1rgb = app.globalData.initdata.color1rgb;
						return color1rgb['red']+','+color1rgb['green']+','+color1rgb['blue'];
					}
				}else if(text=='color2rgb'){
					var color2rgb = app.globalData.initdata.color2rgb;
					return color2rgb['red']+','+color2rgb['green']+','+color2rgb['blue'];
				}else{
					return app.globalData.initdata.textset[text] || text;
				}
			},
		}
	}
</script>

<style>
	.popup__modal{bottom:90px}
	.dp-tabbar{ bottom: 0; position: absolute;}
.dp-banner{width: 100%;height: 450px;}
.dp-banner-swiper{width:100%;height:100%;}
.dp-banner-swiper-img{width:100%;height:auto}
.position-view{width: 100%;position: absolute;top:30rpx;}
.banner-poster{width: 82%;margin: 30rpx auto 0rpx;display: flex;flex-direction:column;align-items: flex-end; height:260rpx}
.banner-poster .poster-title{color: #FFFFFF;font-size: 56rpx;font-weight: 900;padding: 30rpx 0rpx;}
.banner-poster .poster-text{color: #FFFFFF;font-size: 26rpx;opacity: 0.6;padding: 10rpx 0rpx;}
.banner-poster .poster-but{width: 108px;height: 36px;color: #FFFFFF;text-align: center;line-height: 36px;font-size: 28rpx;font-weight: bold;margin: 40rpx 0rpx;border-radius: 36px;}

/*  */
.reserve-view{width: 96%;background-color: #fff;margin: 120rpx auto 0rpx;border-radius: 20rpx;position: relative;padding-bottom: 1rpx;}
.reserve-view .tab-view{color: #111111;font-size: 32rpx;height: 140rpx;width:100%;font-weight: bold;border-radius: 20rpx;
box-sizing: content-box;position: absolute;left: 0;top: -20rpx;overflow: hidden;}
.reserve-view .tab-view .scroll-class{white-space: nowrap;width: 100%;height: 140rpx;position: relative;}
.reserve-view .tab-view .options-tab{width: 33%;height: 120rpx;text-align: center;line-height: 120rpx;display: inline-block;position: relative;background: #ebeef5;margin-top: 20rpx;}
.reserve-view .tab-view .options-tab-active{border-radius: 20rpx 0rpx 0rpx 0rpx;}
.reserve-view .tab-view .options-tab-active2{border-radius: 0rpx 20rpx 0rpx 0rpx;}
.reserve-view .tab-view .active-class-show{width: 100%;position: absolute;top:-20rpx;left: 0rpx;height: 140rpx;line-height: 155rpx;
background: #fff;border-radius: 30rpx 30rpx 0rpx 0rpx;z-index: 9;}
.reserve-view .tab-view .active-class-show .text-view{position: relative;width: 100%;}
.reserve-view .tab-view .color-view{width: 60rpx;height: 10rpx;opacity: .4;border-radius: 10rpx;position: absolute;bottom: 20rpx;left:50%;margin-left:-30rpx;}
.reserve-view .tab-view .active-class-show::after{content: ' ';display: block; width: 0;height: 0;border-top: 0rpx solid transparent; border-right: 60rpx solid transparent;
        border-left: 0rpx solid transparent;border-bottom: 130rpx solid #fff;position: absolute;right: -54rpx;bottom: 0;}
.reserve-view .tab-view .active-class-show-last{width: 100%;position: absolute;top:-20rpx;right: 0rpx;height: 140rpx;background: #fff;z-index: 9;line-height: 155rpx;
border-radius: 30rpx 30rpx 0rpx 0rpx;}
.reserve-view .tab-view .active-class-show-last::before{content: ' ';display: block; width: 0;height: 0;border-top: 0rpx solid transparent; 
        border-right: 0rpx solid transparent;border-left: 60rpx solid transparent;border-bottom: 130rpx solid #fff;position: absolute;left: -54rpx;bottom: 0;}
.reserve-view .tab-view .active-class-show-s{width: 100%;position: absolute;top:-20rpx;right: 0rpx;height: 140rpx;background: #fff;line-height: 155rpx;
z-index: 9;border-radius: 30rpx 30rpx 0rpx 0rpx;}
.reserve-view .tab-view .active-class-show-s::after{content: ' ';display: block; width: 0;height: 0;border-top: 0rpx solid transparent; border-right: 60rpx solid transparent;
        border-left: 0rpx solid transparent;border-bottom: 130rpx solid #fff;position: absolute;right: -54rpx;bottom: 0;}
.reserve-view .tab-view .active-class-show-s::before{content: ' ';display: block; width: 0;height: 0;border-top: 0rpx solid transparent; 
        border-right: 0rpx solid transparent;border-left: 60rpx solid transparent;border-bottom: 130rpx solid #fff;position: absolute;left: -54rpx;bottom: 0;}
.reserve-view .reserve-time-view{width: 88%;height:130rpx;margin:30rpx auto 0;border-bottom: 1px  #f0f0f0 solid;display: flex;align-items: center;
justify-content: space-between;}
.reserve-view .reserve-time-view .time-view{display: flex;flex-direction: column;align-items: flex-start;}
.reserve-view .reserve-time-view .time-view .time-title{color: #7B8085;line-height: 24rpx;}
.reserve-view .reserve-time-view .time-view .date-time{color: #111111;font-size: 32rpx;font-weight: bold;padding-right: 20rpx;}
.reserve-view .reserve-time-view .statistics-view{display: flex;flex-direction: column;align-items: center;justify-content: center;}
.reserve-view .reserve-time-view .statistics-view .statistics-date{height: 32rpx;border-radius: 20px;font-size: 26rpx; padding:5rpx 20rpx;
color: #000;border: 1rpx solid #000;box-sizing: border-box;display: flex;align-items: center;justify-content: center;position: relative}
.reserve-view .reserve-time-view .statistics-view .color-line{border-top: 1rpx solid #f0f0f0;width: 130rpx;margin-top: 25rpx;}
/*  */
.reserve-view .search-view{width: 88%;height:130rpx;margin:0 auto;border-bottom: 1px  #f0f0f0 solid;display: flex;align-items: center;justify-content: space-between;}
.reserve-view .search-view image{width: 28rpx;height: 28rpx;margin-right: 20rpx;}
.reserve-view .search-view .input-class{flex: 1;height: 80rpx;line-height: 80rpx;font-size: 28rpx;}
.reserve-view .search-but{width: 88%;height:96rpx;margin:60rpx auto;border-radius: 36px;display: flex;align-items: center;justify-content: center;color: #FFFFFF;font-size:30rpx; z-index: 10; position: relative;
letter-spacing: 3rpx;}
/*  */
.menu-view{width: 96%;margin: 40rpx auto 0rpx;display: flex;align-items: center;justify-content: space-around;}
.menu-view .scroll-view-class{width: 100%;white-space: nowrap;}
.menu-view .menu-options{display: flex;flex-direction: column;align-items: center;justify-content: center;width: 21.5%;border-radius: 8px;padding: 20rpx;background: #FFFFFF;}
.menu-view .menu-options image{width: 96rpx;height: 96rpx;}
.menu-view .menu-options-scroll{display: flex;flex-direction: column;align-items: center;justify-content: center;width: 100%;border-radius: 8px;padding: 20rpx;background: #FFFFFF; margin-right: 30rpx;}
.menu-view .menu-options-scroll image{width: 96rpx;height: 96rpx;}
.menu-view .menu-options .menu-text-view{color: #343536;font-size: 24rpx;margin-top: 20rpx;}
/*  */
.hotels-list{width: 96%;margin: 40rpx auto 0rpx;display: flex;flex-direction:column;}
.hotels-list .hottitle{ font-size: 32rpx; font-weight: bold; padding:20rpx }

.hotels-list .hotels-options{width: 100%;padding: 20rpx;display: flex;align-items: center;justify-content: space-between;border-radius: 8px;background: #FFFFFF;margin-bottom: 20rpx;}
.hotels-list .hotels-options .hotel-img{width: 98px;height: 130px;border-radius: 15rpx;overflow: hidden;}
.hotels-list .hotels-options .hotel-img image{width: 100%;height: 100%;}
.hotels-list .hotels-options .hotel-info{flex: 1;padding-left: 20rpx;}
.hotels-list .hotels-options .hotel-info .hotel-title{width: 100%;color: #343536;font-size: 30rpx;}
.hotels-list .hotels-options .hotel-info .hotel-address{width: 100%;color: #7B8085;font-size: 24rpx;margin-top: 7rpx;}
.hotels-list .hotels-options .hotel-info .hotel-characteristic{width: 100%;display: flex; flex-wrap: wrap; align-items: center;justify-content: flex-start;margin-top: 7rpx;}
.hotels-list .hotels-options .hotel-info .hotel-characteristic .characteristic-options{font-size: 20rpx;padding: 7rpx 13rpx;flex-wrap: wrap;margin-right: 20rpx; margin-top: 6rpx;}
.hotels-list .hotels-options .hotel-info .hotel-but-view{width: 100%;display: flex;align-items: center;justify-content: space-between;margin-top: 25rpx;}
.hotels-list .hotels-options .hotel-info .hotel-but-view .make-info{display: flex;flex-direction: column;justify-content: flex-start;}
.hotels-options .hotel-info .hotel-but-view .make-info .hotel-price{display: flex;align-items: center;justify-content: flex-start;font-size: 24rpx;}
.hotel-info .hotel-but-view .make-info .hotel-price .hotel-price-num{font-size: 40rpx;font-weight: bold;padding: 0rpx 3rpx;}
.hotels-options .hotel-info .hotel-but-view .make-info .hotel-text{color: #7B8085;font-size: 24rpx;margin-top: 15rpx;}
.hotels-list .hotels-options .hotel-info .hotel-but-view .hotel-make{background: linear-gradient(90deg, #06D470 0%, #06D4B9 100%);width: 72px;height: 32px;line-height: 32px;
text-align: center;border-radius: 36px;color: #FFFFFF;font-size: 28rpx;font-weight: bold;}


/*时间弹窗*/
	.calendar-view{width: 100%;position: relative;max-height: 60vh;padding-top: 30rpx;height: auto;overflow: hidden;padding-bottom: env(safe-area-inset-bottom);}
	/*  */
	.popup__content{ background: #fff;overflow:hidden}
	.popup__content .reserve-time-view{width: 88%;height:130rpx;margin:30rpx auto 0;border-bottom: 1px  #f0f0f0 solid;display: flex;align-items: center;
	justify-content: space-between;}
	.popup__content .reserve-time-view .time-view{display: flex;flex-direction: column;align-items: flex-start;}
	.popup__content .reserve-time-view .time-view .time-title{color: #7B8085;line-height: 24rpx;}
	.popup__content .reserve-time-view .time-view .date-time{color: #111111;font-size: 32rpx;font-weight: bold;padding-right: 20rpx;}
	.popup__content .reserve-time-view .statistics-view{display: flex;flex-direction: column;align-items: center;justify-content: center;}
	.popup__content .reserve-time-view .statistics-view .statistics-date{width: 88rpx;height: 32rpx;border-radius: 20px;font-size: 20rpx;
	color: #000;border: 1rpx solid #000;box-sizing: border-box;display: flex;align-items: center;justify-content: center;position: relative}
	.statistics-view .statistics-date .content-decorate{width: 13rpx;height: 2rpx;background: red;position: absolute;top: 50%;}
	.statistics-view .statistics-date .left-c-d{left: -13rpx;background: #000;}
	.statistics-view .statistics-date .right-c-d{right: -13rpx;background: #000}
	.popup__content .reserve-time-view .statistics-view .color-line{border-top: 1rpx solid #f0f0f0;width: 130rpx;margin-top: 25rpx;}
	.uni-popup__wrapper-box{background: #f7f8fa;border-radius: 40rpx 40rpx 0rpx 0rpx;overflow: hidden;}
	
	.popup__content .popup-close{position: fixed;right: 20rpx;top: 20rpx;width: 56rpx;height: 56rpx;z-index: 11;}
	.popup__content .popup-close image{width: 100%;height: 100%;}
	.choose-but-class{width: 94%;background: linear-gradient(90deg, #06D470 0%, #06D4B9 100%);color: #FFFFFF;font-size: 32rpx;font-weight: bold;padding: 24rpx;	border-radius: 60rpx;position: fixed;bottom: 10rpx;left: 50%;transform: translateX(-50%);margin-bottom: env(safe-area-inset-bottom);text-align: center;}
	/*  */
</style>