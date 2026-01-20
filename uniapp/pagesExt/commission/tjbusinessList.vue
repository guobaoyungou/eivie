<template>
<view class="container">
	<block v-if="isload">
		<view class="topfix">
			<view class="toplabel">
				<text class="t1">商家个数（{{count}}）</text>
			</view>
		</view>
		<view class="topsearch flex-y-center sx" v-if="show_search==1" >
			<view class="f1 flex-y-center">
				<text class="t1">营业额日期筛选：</text>
				<view class="range-item" :style="'border-color:'+t('color1')" @tap="toggleTimeModal">{{choosetime || '选择日期'}}</view>
			</view>
		</view>
		<view class="topsearch flex-y-center sx" v-if="show_search==1" >
			<view class="search-area">
				<view class="search-header">
			        <view class="search-title">店铺区域筛选</view>
			        <view class="reset-btn" @tap="searchArea">查询</view>
				</view>
			    <view class="region-select">
			        <view class="region-item">
			          <picker @change="provinceChange" :value="provinceIndex" :range="provinceList">
			            <view class="picker-content">
						{{provinceList[provinceIndex] || '安徽省'}}
						<text class="iconfont icondaoxu"></text>
						</view>
			          </picker>
			        </view>
			        <view class="region-item">
			          <picker @change="cityChange" :value="cityIndex" :range="cityList">
			            <view class="picker-content">{{cityList[cityIndex] || '市'}}<text class="iconfont icondaoxu"></text></view>
			          </picker>
			        </view>
			        <view class="region-item">
			          <picker @change="districtChange" :value="districtIndex" :range="districtList">
			            <view class="picker-content">{{districtList[districtIndex] || '区/县'}}<text class="iconfont icondaoxu"></text></view>
			          </picker>
			        </view>
				</view>
			</view>
		</view>
		
		<view class="ind_business">
			<view class="ind_buslist" id="datalist">
				<block v-for="(item, index) in datalist" :key="index">
				<view @tap="goto" :data-url="'/pagesExt/business/index?id=' + item.id">
					<view class="ind_busbox flex1 flex-row">
						<view class="ind_buspic flex0"><image :src="item.logo"></image></view>
						<view class="flex1">
							<view class="bus_title">{{item.name}}</view>
							<view style="padding-top: 10px;">商户ID:{{item.id}}</view>
							<view class="bus_sales">销量：{{item.sales}}</view>
							<view class="bus_order" v-if="show_order_price">营业额：{{item.order_price_total}}</view>
						</view>
					</view>
				</view>
				</block>
				<nomore v-if="nomore"></nomore>
				<nodata v-if="nodata"></nodata>
				<view v-if="ischooserange" class="popup__container">
					<view class="popup__overlay" @tap.stop="toggleTimeModal"></view>
					<view class="popup__modal">
						<view class="popup__title">
							<view class="headertab">
								<view :class="rangeType==1?'item on':'item'" :style="{color:rangeType==1?t('color1'):''}"  @tap="rangeTypeChange(1)">年份选择</view>
								<view :class="rangeType==2?'item on':'item'" :style="{color:rangeType==2?t('color1'):''}"  @tap="rangeTypeChange(2)">月份选择</view>
								<view :class="rangeType==3?'item on':'item'" :style="{color:rangeType==3?t('color1'):''}"  @tap="rangeTypeChange(3)">日期选择</view>
							</view>
							<image src="/static/img/close.png" class="popup__close" style="width:30rpx;height:30rpx" @tap.stop="toggleTimeModal"/>
						</view>
						<view class="popup__content">
							<view class="year-tab"  v-if="rangeType==1">
								<view class="month-label">年份</view>
								<view>
									<picker class="date" mode="date" :value="year" fields="year"  @change="bindDateChange" :end="curdate" data-field="year">
										<view class="uni-input">{{year?year:'请选择'}}</view>
									</picker>
								</view>
							</view>
							<view class="month-tab"  v-if="rangeType==2">
								<view class="month-label">月份</view>
								<view>
									<picker class="date" mode="date" :value="month" fields="month"  @change="bindDateChange" :end="curdate" data-field="month">
										<view class="uni-input">{{month?month:'请选择'}}</view>
									</picker>
								</view>
							</view>
							<view class="time-tab" v-if="rangeType==3">
								<view class="month-label">日期</view>
								<view class="time-date">
									<picker class="date" mode="date" :value="start_date"  @change="bindDateChange" :end="curdate" data-field="start_date">
										<view class="uni-input">{{start_date?start_date:'开始时间'}}</view>
									</picker>
									<text class="dt">至</text>
									<picker class="date" mode="date" :value="end_date"  @change="bindDateChange" :end="curdate" data-field="end_date">
										<view class="uni-input">{{end_date?end_date:'结束时间'}}</view>
									</picker>
								</view>
							</view>
						</view>
						<view class="popup__bottom">
							<button class="popup_btn btn1" @tap="resetTimeChoose">重 置</button>
							<button class="popup_btn" @tap="confirmTimeChoose" :style="{background:t('color1'),color:'#fff'}">确 定</button>
						</view>
					</view>
				</view>
			</view>
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
			pre_url:app.globalData.pre_url,
      field: 'juli',
			order:'asc',
			clist:[],
      datalist: [],
      pagenum: 1,
      nomore: false,
      nodata: false,
      types: "",
      showfilter: "",
			showtype:0,
			buydialogShow:false,
			count:0,
			show_order_price:0,
			show_search:0,//是否显示搜索条件
			ischooserange:false,//营业额日期筛选数据
			rangeType:0,//营业额日期筛选数据
			year:'',//营业额日期筛选数据
			month:'',//营业额日期筛选数据
			start_date:'',//营业额日期筛选数据
			end_date:'',//营业额日期筛选数据
			curdate:'',//营业额日期筛选数据
			choosetime:'',//营业额日期筛选数据
			
			 // 店铺区域筛选数据
			  provinceList: [],
			  provinceIndex:0,
			  province: '',
			  cityList: ['市'],
			  cityIndex:0,
			  city: '',
			  districtList: ['区/县'],
			  districtIndex:0,
			  area: '',
    };
  },

  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.getDataList();
  },
	onPullDownRefresh: function () {
		this.getDataList();
	},
  onReachBottom: function () {
    if (!this.nodata && !this.nomore) {
      this.pagenum = this.pagenum + 1;
      this.getDataList(true);
    }
  },
  methods: {
    getDataList: function (loadmore) {
			if(!loadmore){
				this.pagenum = 1;
				this.datalist = [];
			}
      var that = this;
      var pagenum = that.pagenum;
      var keyword = that.keyword;
			that.loading = true;
			that.nodata = false;
			that.nomore = false;
			var rangeType = that.rangeType;
			var year = that.year;
			var month = that.month;
			var start_date = that.start_date;
			var end_date = that.end_date;
      app.post('ApiAgent/tjblist', {
		  pagenum: pagenum,field: that.field,order: that.order,
		  rangeType:rangeType,
		  year:year,
		  month:month,
		  start_date:start_date,
		  end_date:end_date,
		  province:that.province,
		  city:that.city,
		  district:that.district,
		  }, function (res) {
        that.loading = false;
				uni.stopPullDownRefresh();
        var data = res.data;
				var count =  res.count
				that.count = count
				that.loaded();
        if (pagenum == 1) {
          that.datalist = data;
		  that.show_order_price = res.show_order_price || 0;
		  that.show_search = res.show_search || 0;
          if (data.length == 0) {
            that.nodata = true;
          }
		  if(that.show_search==1){
			  that.getAreadata();
		  }
        }else{
          if (data.length == 0) {
            that.nomore = true;
          } else {
            var datalist = that.datalist;
            var newdata = datalist.concat(data);
            that.datalist = newdata;
          }
        }

      });
    },
	toggleTimeModal:function(){
		this.showechart = !this.showechart
		this.ischooserange = !this.ischooserange
		if(this.ischooserange){
			this.range = 5;
		}
		if(!this.rangeType){
			this.rangeType = 1;
		}
	},
	rangeTypeChange:function(rangType){
		this.rangeType = rangType
	},
	bindDateChange:function(e){
		var field = e.currentTarget.dataset.field;
		this[field] = e.detail.value;
		this.choosetime = e.detail.value;
	},
	resetTimeChoose:function(){
		this.year = '';
		this.month = '';
		this.start_date = ''
		this.end_date = ''
		this.range = 1;
		this.ischooserange = false;
		this.rangeType = 0;
		this.getDataList()
	},
	confirmTimeChoose:function(){
		this.ischooserange = false;
		this.getDataList()
	},
	
	getAreadata: function() {
	      var that = this;
	      that.loading = true;
	      app.get('ApiAgent/getAreaAddress', {province:that.province,city:that.city,area:that.area}, function (res) {
	        that.loading = false;
	        if(res.status == 1) {
	          that.isload = true;
	          // 如果有数据，可以在这里设置
			  that.provinceList = res.province_arr || [];
			  console.log(that.provinceList);
			  that.cityList = res.city_arr || [];
			  that.districtList = res.area_arr || [];
	        } else {
	          app.error(res.msg);
	          return;
	        }
	      });
	    },
	    
	    // 省份选择变化
	    provinceChange: function(e) {
	      this.provinceIndex = e.detail.value;
		  this.province = this.provinceList[this.provinceIndex];
		  this.getAreadata();
	    },
	    
	    // 城市选择变化
	    cityChange: function(e) {
	      this.cityIndex = e.detail.value;
		  this.city = this.cityList[this.cityIndex];
		  this.getAreadata();
	      // 根据选择的城市更新区县列表
	    },
	    
	    // 区县选择变化
	    districtChange: function(e) {
	      this.districtIndex = e.detail.value;
		  this.district = this.districtList[this.districtIndex];
	    },
		 // 查询区域代理信息
		searchArea: function() {
			this.getDataList();
		},
	
  }
};
</script>
<style>
.toplabel{width: 100%;background: #fff;padding: 30rpx;display:flex;}


.ind_business {width: 100%;font-size:26rpx;padding:0 24rpx; margin-top: 20rpx;}
.ind_business .ind_busbox{ width:100%;background: #fff;padding:20rpx;overflow: hidden; margin-bottom:20rpx;border-radius:8rpx;position:relative}
.ind_business .ind_buspic{ width:100rpx;height:100rpx; margin-right: 28rpx; }
.ind_business .ind_buspic image{ width: 100%;height:100%;border-radius: 8rpx;object-fit: cover;}
.ind_business .bus_title{ font-size: 30rpx; color: #222;font-weight:bold;line-height:46rpx}
.ind_business .bus_score{font-size: 24rpx;color:#FC5648;display:flex;align-items:center}
.ind_business .bus_score .img{width:24rpx;height:24rpx;margin-right:10rpx}
.ind_business .bus_score .txt{margin-left:20rpx}
.ind_business .indsale_box{ display: flex}
.ind_business .bus_sales{ font-size: 24rpx; color:#999;position:absolute;top:20rpx;right:28rpx}
.ind_business .bus_order{ font-size: 24rpx; color:#999;position:absolute;bottom:20rpx;right:28rpx}

.topsearch{width:94%;margin:16rpx 3%;}
.topsearch .f1{height:60rpx;border:0;background-color:#fff;flex:1;padding: 10rpx;}

.headertab{display: flex;align-items: center;}
.headertab .item{padding-bottom: 10rpx;margin-right: 40rpx;}
.headertab .item.on{font-weight: bold;border-bottom: 2px solid;}
/* .popup__title{border-bottom: 1px solid #ededed;padding: 20rpx;} */
.popup__content{padding: 20rpx 50rpx;line-height: 60rpx;}
.popup__bottom{position: absolute;bottom: 20rpx;width: 80%;left: 10%;color: #fff;display: flex;justify-content: center;}
.popup__bottom .popup_btn{border-radius: 70rpx;color: #fff;width: 260rpx;}
.popup__bottom .popup_btn.btn1{border: 1px solid #c9c9c9;color: #222222;}
.time-date{display: flex;align-items: center;}
.date{width: 200rpx;border-bottom: 1px solid #ededed;}
.time-date .dt{width: 80rpx;text-align: center;}
.month-label{font-weight: bold;font-size: 30rpx;}

/* 搜索区域样式 */
.search-area{background-color:#fff;padding:30rpx;margin-bottom:20rpx;flex: 1;}
.search-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:30rpx;}
.search-title{color:#333;}
.reset-btn{color:#00c389;padding:10rpx 20rpx;border:1px solid #00c389;border-radius:30rpx;}
.region-select{display:flex;justify-content:space-between;margin-bottom:40rpx;}
.region-item{flex:1;margin:0 10rpx;}
.picker-content{height:80rpx;line-height:80rpx;border:1px solid #eee;border-radius:8rpx;text-align:center;position:relative;font-size:22rpx;color:#333;}
.arrow{font-size:24rpx;position:absolute;right:10rpx;color:#999;}
.search-btn{height:88rpx;line-height:88rpx;background:#00c389;width:90%;margin:0 auto;border-radius:8rpx;color:#fff;font-size:32rpx;}

</style>