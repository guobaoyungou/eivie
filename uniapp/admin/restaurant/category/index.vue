<template>
<view>
	<view class="container" id="datalist">
			<view>
				<view class="info-item">
					<view class="t1" v-if="couponShow">分类名称</view>
					<view class="t1" v-else>菜品分类</view>
					<block v-if="couponShow">
						<view class="t2">店内</view>
						<view class="t2">外卖</view>
						<view class="t2">预定</view>
						<view class="t2">排序</view>
					</block>
				</view>
				<block v-if="couponShow">
					<view class="info-item" v-for="(item,index) in datalist" :key="index">
						<view class="t1">{{item.name}}<text v-if="item.status == 0" style="color: #DBAA83;">(隐藏)</text></view>
							<view class="t2"><text v-if="item.is_shop == 1">开</text><text v-else>关</text></view>
							<view class="t2"><text v-if="item.is_takeaway == 1">开</text><text v-else>关</text></view>
							<view class="t2"><text v-if="item.is_booking == 1">开</text><text v-else>关</text></view>
							<view class="t2">{{item.sort}}</view>
							<image class="t3" @tap="goto" :data-url="'edit?id=' + item.id" :src="pre_url+'/static/img/arrowright.png'" />
					</view>
				</block>
				<block v-else>
					<view class="info-item flex" v-for="(item,index) in datalist" :key="index" style="justify-content: space-between;">
						<view class="t1">{{item.name}}<text v-if="item.status == 0" style="color: #DBAA83;">(隐藏)</text></view>
						<view class="addbut" :style="{background:'linear-gradient(270deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'}" @click="couponAddChange(item)">添加</view>
					</view>
				</block>
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
			couponShow:true,
			bid:'',
      pre_url:app.globalData.pre_url,
    };
  },
  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.bid = this.opt.bid ? this.opt.bid : '';
		this.getdata();
		// 添加餐饮优惠券，选择指定菜品分类
		if(opt.coupont){
			this.couponShow = false;
		}
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
      app.post('ApiAdminRestaurantCategory/index', {pagenum: pagenum,bid:that.bid}, function (res) {
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
    },
    todel: function (e) {
      var that = this;
      var id = e.currentTarget.dataset.id;
      app.confirm('确定要删除吗?', function () {
        app.post('ApiAdminRestaurantCategory/del', {id: id}, function (res) {
          if (res.status == 1) {
            app.success(res.msg);
            that.getdata();
          } else {
            app.error(res.msg);
          }
        });
      });
    },
    setst: function (e) {
      var that = this;
      var id = e.currentTarget.dataset.id;
      var st = e.currentTarget.dataset.st;
      app.confirm('确定要' + (st == 0 ? '下架' : '上架') + '吗?', function () {
        app.post('ApiAdminRestaurantCategory/setst', {st: st,id: id}, function (res) {
          if (res.status == 1) {
            app.success(res.msg);
            that.getdata();
          } else {
            app.error(res.msg);
          }
        });
      });
    },
		couponAddChange(item){
			uni.$emit('shopDataClass',{id:item.id,name:item.name,});
			uni.navigateBack({
				delta: 1
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
.info-item .t2{ color:#444444;text-align:right;flex:1;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:1;overflow:hidden;}
.info-item .t3{ width: 26rpx;height:26rpx;margin-left:20rpx}

.btn1{margin-left:20rpx;width:120rpx;height:60rpx;line-height:60rpx;color:#fff;border-radius:3px;text-align:center}
.btn2{margin-left:20rpx;width:120rpx;height:60rpx;line-height:60rpx;color:#333;background:#fff;border:1px solid #cdcdcd;border-radius:3px;text-align:center}
.addbut{width:88rpx;height:60rpx;border-radius:30rpx;text-align:center;font-size: 24rpx;line-height:60rpx;color: #fff;}
</style>