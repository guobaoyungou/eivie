<template>
<view>
	<view class="container" id="datalist">
			<view>
				<view class="info-item" v-for="(item,index) in datalist" :key="index">
					<view class="t1">
						<view class="info-title">{{item.name}}<text v-if="item.status == 0" style="color: #DBAA83;">(隐藏)</text></view>
						<view class="info-item-child">
<!--							<view class="">服务费：{{item.service_fee}}</view>-->
							<view class="">预定费：{{item.booking_fee}}</view>
							<view class="">最低消费：{{item.limit_fee}}</view>
							<view class="">座位数：{{item.seat}}</view>
						</view>
						</view>
					<image class="t3" @tap="goto" :data-url="'tableCategoryEdit?id=' + item.id" :src="pre_url+'/static/img/arrowright.png'" />
				</view>
			</view>
			
			<view class="bottom-view">
				<view class="button" @tap="goto" :data-url="'tableCategoryEdit'">添加分类</view>
			</view>
	
		<nomore v-if="nomore"></nomore>
		<nodata v-if="nodata"></nodata>
	</view>

</view>
</template>

<script>
var app = getApp();

export default {
  data() {
    return {
      st: 'all',
      datalist: [],
      pagenum: 1,
      nomore: false,
      count0: 0,
      count1: 0,
      countall: 0,
      sclist: "",
      nodata: false,
      pre_url:app.globalData.pre_url,
    };
  },
  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.getdata();
  },
	onShow(opt) {
		this.opt = app.getopts(opt);
		this.getdata();
	},
	onPullDownRefresh: function () {
		this.getdata();
	},
  onReachBottom: function () {
    if (!this.nodata && !this.nomore) {
      this.pagenum = this.pagenum + 1;
      this.getdata(true);
    }
  },
  methods: {
    changetab: function (st) {
      var that = this;
      that.st = st;
      that.getdata();
    },
    getdata: function (loadmore) {
     if(!loadmore){
				this.pagenum = 1;
				this.datalist = [];
			}
      var that = this;
      var pagenum = that.pagenum;
      var st = that.st;
      var keyword = that.keyword;
			that.nodata = false;
			that.nomore = false;
			that.loading = true;
      app.post('ApiAdminRestaurantTableCategory/index', {pagenum: pagenum}, function (res) {
        that.loading = false;
        var data = res.datalist;
        if (pagenum == 1){
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
      });
    }
  }
};
</script>
<style>
.container{ width:100%;	 padding-bottom: 100rpx;}

.info-item{ display:flex;align-items:center;width: 100%; background: #fff;padding:0 3%;  border-bottom: 1px #f3f3f3 solid;line-height:96rpx}
.info-item:last-child{border:none}
.info-item .t1{ width: 300rpx;height:auto;line-height:48rpx;flex:1;}
.info-item .t3{ width: 26rpx;height:26rpx;margin-left:20rpx}
.info-title {font-weight:bold;font-size: 32rpx; line-height: 80rpx;}
.info-item-child { display: flex; flex-wrap: wrap;}
.info-item-child view { width: 300rpx; color: #666; }

.bottom-view {position: fixed; bottom: 0; width: 100%; height: 100rpx; background-color: #fff; padding: 20rpx;display: flex; flex-direction: row-reverse; align-items: center; box-shadow: 0px -10rpx 20rpx 0rpx rgb(0 0 0 / 20%);justify-content: center;}

.button{margin:0 20rpx;width:280rpx;line-height:70rpx;color:#fff;border-radius:3px;text-align:center; background-color: #007AFF;}
</style>