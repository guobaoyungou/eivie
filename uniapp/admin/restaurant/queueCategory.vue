<template>
<view>
	<view class="container" id="datalist">
			<view>
				<view class="info-item">
					<view class="t1">队列名称</view>
					<view class="t2">前缀</view>
					<view class="t2">座位数</view>
					<view class="t2">排序</view>
				</view>
				<view class="info-item" v-for="(item,index) in datalist" :key="index">
					<view class="t1">{{item.name}}<text v-if="item.status == 0" style="color: #DBAA83;">(隐藏)</text></view>
					<view class="t2">{{item.code}}</view>
					<view class="t2">{{item.seat_min}}-{{item.seat_max}}</view>
					<view class="t2">{{item.sort}}</view>
					<image class="t3" @tap="goto" :data-url="'queueCategoryEdit?id=' + item.id" :src="pre_url+'/static/img/arrowright.png'" />
				</view>
			</view>
			<view style="margin-top: 40rpx;">
				<view style="text-align: center;">排队开关：<text v-if="set.status == 1" style="color: #008000;">开启</text><text v-else style="color: #CA2428;">关闭</text></view>
				<button class="btn1" @tap="setst" v-if="set.status == 1" data-st="0" :style="'background:linear-gradient(90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'" >关闭</button>
				<button class="btn1" @tap="setst" v-else data-st="1" :style="'background:linear-gradient(90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'" >开启</button>
			
			</view>
			<button class="savebtn" @tap="goto" data-url="queueCategoryEdit" :style="'background:linear-gradient(90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'" >添加</button>
	
	</view>

	<nomore v-if="nomore"></nomore>
	<nodata v-if="nodata"></nodata>
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
      set: {},
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
	onShow: function () {
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
      app.post('ApiAdminRestaurantQueue/categoryList', {}, function (res) {
        that.loading = false;
        var data = res.datalist;
				that.datalist = data;
				that.set = res.set;
				console.log(that.set.status)
				if (data.length == 0) {
					that.nodata = true;
				}
				that.loaded();
      });
    },
    setst: function (e) {
      var that = this;
      var st = e.currentTarget.dataset.st;
      app.confirm('确定要' + (st == 0 ? '关闭' : '开启') + '吗?', function () {
        app.post('ApiAdminRestaurantQueue/setst', {st: st}, function (res) {
          if (res.status == 1) {
            app.success(res.msg);
            that.getdata();
          } else {
            app.error(res.msg);
          }
        });
      });
    }
  }
};
</script>
<style>
.container{ width:100%; padding-bottom: 100rpx;}

.info-item{ display:flex;align-items:center;width: 100%; background: #fff;padding:0 3%;  border-bottom: 1px #f3f3f3 solid;height:96rpx;line-height:96rpx}
.info-item:last-child{border:none}
.info-item .t1{ width: 300rpx;font-weight:bold;height:auto;line-height:48rpx;}
.info-item .t2{ color:#444444;text-align:right;flex:1;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:1;overflow:hidden;}
.info-item .t3{ width: 26rpx;height:26rpx;margin-left:20rpx}
.savebtn{ width: 90%; height:80rpx; line-height: 80rpx; text-align:center;border-radius:8rpx; color: #fff;font-weight:bold;margin: 0 5%; margin-top:60rpx; border: none;
 position: fixed; bottom: 10rpx;}

.btn1{margin-left:20rpx;width:120rpx;height:60rpx;line-height:60rpx;color:#fff;border-radius:3px;text-align:center; margin: 15rpx auto;}
.btn2{margin-left:20rpx;width:120rpx;height:60rpx;line-height:60rpx;color:#333;background:#fff;border:1px solid #cdcdcd;border-radius:3px;text-align:center}
</style>