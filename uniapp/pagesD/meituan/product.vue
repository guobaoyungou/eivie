<template>
	<view>
		<block v-if="isload">
				<view class='dp-banner'>
					<swiper class="dp-banner-swiper" :autoplay="true" :indicator-dots="false" :current="0" :circular="true" :interval="3000">
						<block v-for="(item,index) in detail.pic" :key="index"> 
							<swiper-item>
								<view @click="viewPicture(detail.pic)">
									<image :src="item" class="dp-banner-swiper-img" mode="widthFix"/>
								</view>
							</swiper-item>
						</block>
					</swiper>
				</view>
				
				<view class="position-view">
					<view class="content-view" @tap="selectDate">
						<view class="title-view">{{detail.spot_name}}</view>
						<view class="hotel-nature" style="padding: 0;margin-top: 5rpx;" v-if="detail.spot_tag">
							<view class="hotspot-nature-text">{{detail.spot_tag}}</view>
						</view>
						<view class="hotel-nature">
							<view class="hotspot-nature-text" v-if="detail.spot_address">
								<view class="hotspot-value">{{detail.spot_address}}</view>
							</view>
						</view>
					</view>
					<block v-for="(item,index) in list" :key="index">
						<!-- 酒店选择入住时间 -->
						<view class="hotel-select-time" v-if="!isEmpty(list[2])">
							<view class="time-view flex flex-y-center flex-bt" @tap="selectDate">
								<view class="time-options flex flex-y-center flex-bt">
									<view class="month-tetx">{{startDate}}</view>
									<view class="day-tetx">{{startWeek}}入住</view>
								</view>
								<view class="content-text">
									<view class="content-decorate left-c-d"></view>
									共{{dayCount}}晚
									<view class="content-decorate right-c-d"></view>
								</view>
								<view class="time-options flex flex-y-center flex-bt">
									<view class="month-tetx">{{endDate}}</view>
									<view class="day-tetx">{{endWeek}}离店</view>
								</view>
							</view>
						</view>
						<!-- END 酒店选择入住时间 -->
						<view class="hotels-list">
							<view class="hotels-list-title">
								<view class="title">{{typeText(index)}}</view> 
							</view>
							<block v-for="(val,key) in item" :key="key">
								<view class="hotels-options" @tap.stop="openDetail" :data-id='val.id' :data-index='index' :data-key='key'>
									<view class="hotel-img">
										<image :src="val.pic" mode="widthFix"></image>
									</view>
									<view class="hotel-info">
										<view class="hotel-title">{{val.name}}</view>
										<view class="hotel-but-view">
											<view class="make-info">
												<view class="hotel-price" :style="{color:t('color1')}">
													<view>￥</view>
													<view class="hotel-price-num">{{val.sell_price}}</view>
												</view>
											</view>
											<view class="hotel-make" :style="'background:rgba('+t('color1rgb')+',0.8);color:#FFF'">预约</view>
										</view>
									</view>
								</view>
							</block>
						</view>
					</block>
				</view>
				
				<!-- 详情弹窗 -->
				<uni-popup id="popup" ref="popup" type="bottom" >
					<view class="popup__content" style="bottom: 0;padding-top:0;padding-bottom:0; max-height: 86vh; ">
						<view class="popup-close" @click="popupdetailClose">
							<image :src="`${pre_url}/static/img/hotel/popupClose.png`"></image>
						</view>
						<scroll-view scroll-y style="height: auto;max-height: 90vh;">
							<view class="popup-banner-view" style="height: 450rpx;">
								<swiper class="dp-banner-swiper" :autoplay="true" :indicator-dots="false" :current="0" :circular="true" :interval="3000" @change='swiperChange'>
									<block v-for="(item,index) in product.pics" :key="index"> 
										<swiper-item>
											<view @click="viewPicture(product.pics,item)">
												<image :src="item" class="dp-banner-swiper-img" mode="widthFix"/>
											</view>
										</swiper-item>
									</block>
								</swiper>
								<!-- <view class="popup-numstatistics flex flex-xy-center" v-if='product.pics.length'>
									{{bannerindex}} / {{bannerList.length}}
								</view> -->
							</view>
							<view class="hotel-details-view flex flex-col">
								<view class="hotel-title">{{product.name}}</view>
							</view>
							<!-- 重要通知	 -->
							<view class="hotel-equity-view flex flex-col" v-if="product.importent_point">
								<view class="equity-title-view flex">
									<view class="equity-title">重要提示</view>
								</view>
								<view class="equity-options flex flex-col">{{ product.importent_point }}</view>
							</view>
							<!-- END 重要通知	 -->
							
							<!-- 预定说明 -->
							<view class="hotel-equity-view flex flex-col" v-if="product.booking_info">
								<view class="equity-title-view flex">
									<view class="equity-title">预定说明</view>
								</view>
								<view class="equity-options flex flex-col">
										<parse :content="product.booking_info"></parse>
								</view>
							</view>
							<!-- END 预定说明 -->
							
							<!-- 使用说明 -->
							<view class="hotel-equity-view flex flex-col" v-if="(product.sell_start_date || product.sell_end_date) || (product.goods_extra_info && product.goods_extra_info.visit_way ) || product.visit_address">
								<view class="equity-title-view flex">
									<view class="equity-title">使用说明</view>
								</view>
								<view class="equity-options flex flex-col">
									<view class="sub-desc-view flex" v-if="product.book_date==2 && (product.sell_start_date || product.sell_end_date)">
										<view class="sub-title">有效期</view>
										<view class="sub-value">{{product.sell_start_date}}~{{product.sell_end_date}}</view>
									</view>
									<view class="sub-desc-view flex" v-if="product.goods_extra_info && product.goods_extra_info.visit_way">
										<view class="sub-title">入园方式</view>
										<view class="sub-value">{{product.goods_extra_info.visit_way}}</view>
									</view>
									<view class="sub-desc-view flex" v-if="product.visit_address">
										<view class="sub-title">入园地址</view>
										<view class="sub-value">{{product.visit_address}}</view>
									</view>
								</view>
							</view>
							<!-- END 使用说明 -->
							
							<!-- 费用说明 -->
							<view class="hotel-equity-view flex flex-col" v-if="product.cost_include || product.cost_no_include">
								<view class="equity-title-view flex">
									<view class="equity-title">费用说明</view>
								</view>
								<view class="equity-options flex flex-col">
									<view class="sub-desc-view flex" v-if="product.cost_include">
										<view class="sub-title">费用包含</view>
										<view class="sub-value">{{product.cost_include}}</view>
									</view>
									<view class="sub-desc-view flex" v-if="product.cost_no_include">
										<view class="sub-title">费用不包含</view>
										<view class="sub-value">{{product.cost_no_include}}</view>
									</view>
								</view>
							</view>
							<!-- END 费用说明 -->
							
							<!-- 其他说明 -->
							<view class="hotel-equity-view flex flex-col" v-if="product.goods_extra_info && (product.goods_extra_info.buy_limit_info || product.goods_extra_info.goods_other_desc)">
								<view class="equity-title-view flex">
									<view class="equity-title">其他说明</view>
								</view>
								<view class="equity-options flex flex-col">
									<view class="sub-desc-view flex" v-if="product.goods_extra_info.buy_limit_info">
										<view class="sub-title">限购说明</view>
										<view class="sub-value">{{product.goods_extra_info.buy_limit_info}}</view>
									</view>
									<view class="sub-desc-view flex" v-if="product.goods_extra_info && product.goods_extra_info.goods_other_desc">
										<view class="sub-title">其他</view>
										<view class="sub-value">{{product.goods_extra_info.goods_other_desc}}</view>
									</view>
								</view>
							</view>
							<!-- END 其他说明 -->
							
							<!-- 入园须知 -->
							<view class="hotel-equity-view flex flex-col" v-if="product.notice">
								<view class="equity-title-view flex">
									<view class="equity-title">入园须知</view>
								</view>
								<view class="equity-options flex flex-col">
										<parse :content="product.notice"></parse>
								</view>
							</view>
							<!-- END 入园须知 -->
							
							
							<view style="width:100%;height:140rpx;"></view>
							<view class="bottombar flex-row" :class="menuindex>-1?'tabbarbot':'notabbarbot'">
								<view class="f1 flex">
									<view class="price" :style="{color:t('color1')}"><text style="font-size:18px;">￥</text>{{product.sell_price}}</view>
									<view class="market_price">{{product.market_price}}</view>
								</view>
								<view class="op">
									<view class="tobuy flex-x-center flex-y-center" @tap="tobuy" :style="{background:t('color1')}">购买</view>
								</view>
							</view>
						</scroll-view>
					</view>
				</uni-popup>
				<!-- END 详情弹窗 -->
				
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
								<calendar :is-show="true" :isFixed='false' showstock='0' :start-date="starttime" :end-date="endtime" mode="2" :themeColor="t('color1')" @callback="getDate"  maxdays='10' initMonth="1">
								</calendar>
							</view>
							<view class="choose-but-class" :style="'background: linear-gradient(90deg,rgba('+tColor('color1rgb')+',1) 0%,rgba('+tColor('color1rgb')+',1) 100%)'" @tap="handleClickMask">
								确认{{dayCount}}晚
							</view>
						</view>
					</view>
				</view>
				<nomore v-if="nomore"></nomore>
				<nodata v-if="nodata"></nodata>
		</block>
		<loading v-if="loading"></loading>
		<dp-tabbar :opt="opt"></dp-tabbar>
		<popmsg ref="popmsg"></popmsg>
	</view>
</template>

<script>
	import calendar from './mobile-calendar-simple/Calendar.vue'
	var app = getApp();
	export default{
		components:{
		    calendar
		},
		data(){
      return {
        loading:false,
        isload: false,
        id: 0,
        pre_url: app.globalData.pre_url,
        nodata: false,
        nomore: false,
        text: [],
        detail: [],
        roomstyle: 1,
        btnstyle: 1,
        moneyunit: '元',
				goods_extra:[],
				calendarvisible:false,
				dateprice:0,
        date_price: [],
        selectedDateIndex: -1,
				visit_date:'',//游玩日期
				set:[],
				list:[],
				product:[],//选中产品信息
				dayCount:1,
				startWeek:'',
				endWeek:'',
				startDate:'',
				endDate:'',
				starttime:'',
				endtime:''
      }
		},
		onLoad(opt) {
			this.opt = app.getopts(opt);
			this.id = this.opt.id?this.opt.id:0
			var sysinfo = uni.getSystemInfoSync();
			this.statusBarHeight = sysinfo.statusBarHeight;
			this.getdata();
		},
		methods:{
			getdata:function(){
				var that = this;
				app.post('ApiMeituanProduct/getProductDetail', {id:this.id}, function (res) {
						that.loading = false;
						if(res.status == 0){
							return app.alert(res.msg);
						}
						that.detail = res.detail;
						that.set = res.set;
						that.list = res.list;

            // 设置默认日期（当天入住，次日离店）
            var startDateObj = new Date();
            var endDateObj = new Date(startDateObj);
            endDateObj.setDate(startDateObj.getDate() + 1);
            //yyyy-mm-dd
            const formatDate = (date) => {
              const year = date.getFullYear();
              const month = (date.getMonth() + 1).toString().padStart(2, '0');
              const day = date.getDate().toString().padStart(2, '0');
              return `${year}-${month}-${day}`;
            };
            var starttime = formatDate(startDateObj);
            var endtime = formatDate(endDateObj);
            //计算天数
            var daycount = Math.ceil((endDateObj - startDateObj) / (1000 * 60 * 60 * 24));
            var weekdays = ['周日', '周一', '周二', '周三', '周四', '周五', '周六'];
            var startWeek = weekdays[startDateObj.getDay()];
            var endWeek = weekdays[endDateObj.getDay()];
            var startDate = `${(startDateObj.getMonth() + 1).toString().padStart(2, '0')}月${startDateObj.getDate().toString().padStart(2, '0')}日`;
            var endDate = `${(endDateObj.getMonth() + 1).toString().padStart(2, '0')}月${endDateObj.getDate().toString().padStart(2, '0')}日`;

            that.detail = res.detail;
            that.set = res.set;
            that.list = res.list;

            that.starttime = starttime;
            that.endtime = endtime;
            that.startDate = startDate;
            that.endDate = endDate;
            that.startWeek = startWeek;
            that.endWeek = endWeek;
            that.dayCount = daycount;
            that.loaded();
				})
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
			tobuy: function (e) {
				let visit_date = this.starttime;
				if(this.product.type == 2){
					visit_date = this.starttime+ '/' +this.endtime;
				}
				return app.goto('buy?id='+this.product.id+'&visit_date='+visit_date+'&daycount='+this.dayCount,'redirect');
			},
      selectDateItem:function(date, index){
        this.selectedDateIndex = index;
        this.dateprice = date.sell_price;
				this.visit_date = date.date; 
			},
			typeText:function(t){
				let titleMap = {1: '门票',2: '酒店',3: '套餐'}
				return titleMap[t] || '未知';
			},
			openDetail:function(e){
        let id = e.currentTarget.dataset.id;
        let index = e.currentTarget.dataset.index;
        let key = e.currentTarget.dataset.key
        let product = this.list[index][key];
        this.product = product;
				this.$refs.popup.open();
			},
			popupdetailClose(){
				this.$refs.popup.close();
			},
			selectDate:function(){
				this.calendarvisible = true;
			},
      handleClickMask:function(){
        this.calendarvisible = false;
      },
			getDate(date){
				var that = this
				if(date.dayCount){
						that.dayCount = date.dayCount;
				}
				if(date.startStr){
						var starttime =  date.startStr.dateStr
						var startDate = starttime.substr(5).replace('-', '月');
						that.starttime = starttime
						that.startDate = startDate
						that.startWeek = date.startStr.week
				}
				if(date.endStr){
					var endtime =  date.endStr.dateStr
					var endDate = endtime.substr(5).replace('-', '月');
					that.endtime = endtime
					that.endDate = endDate
					that.endWeek = date.endStr.week
				}		
			},
		},
	}
</script>

<style>
.dp-banner{width: 100%;height: 250px;}
.dp-banner-swiper{width:100%;height:100%;}
.dp-banner-swiper-img{width:100%;height:auto}

.position-view{width: 100%;height: auto;position: relative;top:-125rpx;}
.content-view{border-radius: 20rpx;background: #fff;padding: 20rpx 40rpx;width: 96%;height: auto;margin:0 auto;}
.content-view .title-view{color: #1E1A33;font-size: 40rpx;padding: 20rpx 0;font-weight: bold;}

.hotel-nature{}
.hotel-nature .hotspot-nature-text{display: flex; align-items: flex-start; color: #4A4950;padding:15rpx 0;font-size: 25rpx;}
.hotel-nature .hotspot-nature-text .hotspot-value{flex: 1;}

/*  */
.hotels-list{width: 96%;margin: 0px auto;background: #fff;border-radius: 8px;padding: 15px 10px;margin-top: 10px;}
.hotels-list .hotels-list-title{display: flex;align-items: center;justify-content: space-between;}
.hotels-list .hotels-list-title .title{color: #1E1A33;font-size: 28rpx;font-weight: bold;padding:0 20rpx;}
.hotels-list .hotels-options{width: 100%;padding: 20rpx;display: flex;align-items: center;justify-content: space-between;background: #FFFFFF;border-bottom: 1px solid #c9c9c9;}
.hotels-list .hotels-options:last-child{border-bottom: 0;}
.hotels-list .hotels-options .hotel-img{width: 100px;overflow: hidden;}
.hotels-list .hotels-options .hotel-img image{width: 100%;height: 100%;}
.hotels-list .hotels-options .hotel-info{flex: 1;padding-left: 20rpx;}
.hotels-list .hotels-options .hotel-info .hotel-title{width: 100%;color: #343536;font-size: 30rpx;}
.hotels-list .hotels-options .hotel-info .hotel-but-view{width: 100%;display: flex;align-items: center;justify-content: space-between;margin-top: 25rpx;}
.hotels-list .hotels-options .hotel-info .hotel-but-view .make-info{display: flex;flex-direction: column;justify-content: flex-start;}
.hotels-options .hotel-info .hotel-but-view .make-info .hotel-price{display: flex;align-items: center;justify-content: flex-start;font-size: 24rpx;}
.hotel-info .hotel-but-view .make-info .hotel-price .hotel-price-num{font-size: 40rpx;font-weight: bold;padding: 0rpx 3rpx;}
.hotels-list .hotels-options .hotel-info .hotel-but-view .hotel-make{background: linear-gradient(90deg, #06D470 0%, #06D4B9 100%);width: 72px;height: 32px;line-height: 32px;
text-align: center;border-radius: 36px;color: #FFFFFF;font-size: 28rpx;font-weight: bold;}


.equity-options .sub-desc-view{margin-bottom: 20rpx;}
.equity-options .sub-desc-view .sub-title{width: 185rpx;}
.equity-options .sub-desc-view .sub-value{flex: 1;}

.bottombar{ width: 94%; position: fixed;bottom: 0; left: 0; background: #fff;display:flex;height:100rpx;padding:0 4% 0 2%;align-items:center;box-sizing:content-box}
.bottombar .f1{flex:1;display:flex;align-items:flex-end;margin-right:30rpx}
.bottombar .op{width:50%;border-radius:36rpx;overflow:hidden;display:flex;}
.bottombar .tobuy{flex:1;height: 72rpx; line-height: 72rpx;color: #fff; border-radius: 0px; border: none;}
.bottombar .price{font-size: 50rpx;font-weight: bold;}
.bottombar .market_price{font-size:26rpx;color:#C2C2C2;text-decoration:line-through;margin: 0 0 5rpx 20rpx;}

.popup__content{ background: #fff;overflow:hidden; height: auto; }
.popup__content{width: 100%;height:auto;position: relative;}
.popup__content .popup-close{position: fixed;right: 20rpx;top: 20rpx;width: 56rpx;height: 56rpx;z-index: 11;}
.popup__content .popup-close image{width: 100%;height: 100%;}
.popup__content .popup-banner-view{width: 100%;height: 500rpx;position: relative;}
.popup__content .hotel-details-view{width: 100%;padding: 30rpx 40rpx;background: #fff;}
.popup__content .hotel-details-view	.hotel-title{color: #1E1A33;font-size: 35rpx;}
.hotel-details-view	.introduce-view .options-intro image{width: 32rpx;height: 32rpx;}
.popup__content .hotel-equity-view{width: 100%;padding:30rpx 40rpx 40rpx;background: #fff;margin-top: 20rpx;}
.hotel-equity-view .equity-title-view{align-items: center;justify-content: flex-start;}
.hotel-equity-view .equity-title-view .equity-title{color: #1E1A33;font-size: 32rpx;font-weight: bold;}
.hotel-equity-view .equity-options{margin-top: 40rpx;}

.hotel-select-time{width: 96%;margin: 0 auto;background: #fff;padding: 0 20rpx;margin-top: 20rpx;}
.hotel-select-time .time-view{width: 100%;padding: 15px 0px;}
.hotel-select-time .time-view .time-options .month-tetx{color: #1e1a33; font-size: 16px; font-weight: 500;}
.hotel-select-time .time-view .time-options .day-tetx{color: rgba(30, 26, 51, .4); font-size: 13px; margin-left: 10px;}
.hotel-select-time .time-view .content-text{box-sizing: border-box; border: 1px solid #000; text-align: center; border-radius: 20px; padding: 0 5px; color: #000; font-size: 11px; position: relative;}		
.hotel-select-time .time-view .content-text .content-decorate{width: 13rpx;height: 2rpx;background: red;position: absolute;top: 50%;background: #000;}
.hotel-select-time .time-view .content-text .left-c-d{left: -13rpx;}
.hotel-select-time .time-view .content-text .right-c-d{right: -13rpx;}

	.popup__content .reserve-time-view{width: 88%;height:130rpx;margin:30rpx auto 0;border-bottom: 1px  #f0f0f0 solid;display: flex;align-items: center;
	justify-content: space-between;}
	.popup__content .reserve-time-view .time-view{display: flex;flex-direction: column;align-items: flex-start;}
	.popup__content .reserve-time-view .time-view .time-title{color: #7B8085;line-height: 24rpx;}
	.popup__content .reserve-time-view .time-view .date-time{color: #111111;font-size: 32rpx;font-weight: bold;padding-right: 20rpx;}
	.popup__content .reserve-time-view .statistics-view{display: flex;flex-direction: column;align-items: center;justify-content: center;}
	.popup__content .reserve-time-view .statistics-view .statistics-date{width: 88rpx;height: 32rpx;border-radius: 20px;font-size: 20rpx;}
	.choose-but-class{width: 94%;background: linear-gradient(90deg, #06D470 0%, #06D4B9 100%);color: #FFFFFF;font-size: 32rpx;font-weight: bold;padding: 24rpx;
	border-radius: 60rpx;position: fixed;bottom:10rpx;left: 50%;transform: translateX(-50%);margin-bottom: env(safe-area-inset-bottom);text-align: center;}
	.calendar-view{width: 100%;position: relative;max-height: 60vh;padding-top: 30rpx;height: auto;overflow: hidden;padding-bottom: env(safe-area-inset-bottom);}
	
</style>