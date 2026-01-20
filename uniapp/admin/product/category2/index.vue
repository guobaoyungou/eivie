<template>
<view>
	<view class="container" id="datalist">
			<view>
				<view class="info-item">
					<view class="t1">分类管理</view>
					<view class="t2"  @tap="goto" :data-url="'edit'">添加分类</view>
					<image class="t3" @tap="goto" :data-url="'edit'" :src="pre_url+'/static/img/arrowright.png'" />
				</view>
				<view class="info-item">
					<view class="t1">分类名称</view>
					<view class="t2">图标</view>
					<view class="t2">状态</view>
					<view class="t2">排序</view>
					<view class="t3"></view>
				</view>
				
					<view class="info-item" v-for="(item,index) in datalist" :key="index" >
						<view class="t1"><text v-if="item.deep == 0">{{item.name}} </text><text class="d1" v-if="item.deep == 1">{{item.name}} </text><text class="d2" v-if="item.deep == 2">{{item.name}} </text></view>
						<view class="t2"><image :src="item.pic"></image></view>
						<view class="t2"><text v-if="item.status == 1">显示</text><text v-else>隐藏</text></view>
						<view class="t2">{{item.sort}}</view>
						<image class="t3" @tap="goto" :data-url="'edit?id=' + item.id" :src="pre_url+'/static/img/arrowright.png'" />
					</view>
				
			</view>
	
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
      app.post('ApiAdminProductCategory2/index', {pagenum: pagenum}, function (res) {
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
.container{ width:100%;}

.info-item{ display:flex;align-items:center;width: 100%; background: #fff;padding:0 3%;  border-bottom: 1px #f3f3f3 solid;height:96rpx;line-height:96rpx}
.info-item:last-child{border:none}
.info-item .t1{ width: 300rpx;font-weight:bold;height:auto;line-height:48rpx;}
.info-item .t2{ color:#444444;text-align:right;flex:1;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:1;overflow:hidden;line-height:48rpx;}
.info-item .t3{ width: 26rpx;height:26rpx;margin-left:20rpx}

.btn1{margin-left:20rpx;width:120rpx;height:60rpx;line-height:60rpx;color:#fff;border-radius:3px;text-align:center}
.btn2{margin-left:20rpx;width:120rpx;height:60rpx;line-height:60rpx;color:#333;background:#fff;border:1px solid #cdcdcd;border-radius:3px;text-align:center}

.info-item .t2 image{ width: 100rpx; height: 100rpx;}
.info-item .t1 .d1{margin-left:5rpx}
.info-item .t1 .d2{margin-left:20rpx}
</style>