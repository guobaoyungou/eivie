<template>
<view class="container">

	<!-- #ifndef H5 || APP-PLUS -->
	<view class="topsearch flex-y-center">
		<view class="f1 flex-y-center">
			<image class="img" :src="pre_url+'/static/img/search_ico.png'"></image>
			<input :value="keyword" placeholder="输入关键字搜索" placeholder-style="font-size:24rpx;color:#C2C2C2" @confirm="searchConfirm"></input>
		</view>
	</view>
	<!--  #endif -->
	<view class="order-content" id="datalist" style="padding-bottom: 80rpx">
	<block v-for="(item, index) in datalist" :key="index">
		<view class="order-box">
			<view class="content" style="border-bottom:none">
				<view class="detail">
					<text class="t1">{{item.location}}</text>
					<view class="t2">设备编号：{{item.deviceId}}</view>
					<view class="t2">出水口数量：{{item.outlet_num}}</view>
					<view class="t2">添加时间：{{item.createtime}}</view>
				</view>
			</view>
      <view class="op">

        <block >
          <view class="btn2" style="border: 0"></view>
          <view class="btn2" style="border: 0"></view>
          <view class="btn2" style="border: 0"></view>
          <view class="btn2" style="border: 0"></view>
          <view class="btn2" @tap="todel" :data-id="item.id">删除</view>
          <view @tap="goto" :data-url="'outletList?id='+item.id" class="btn2" style="width: 260rpx">出水口管理</view>
        </block>
      </view>
		</view>
	</block>
	</view>
  <view class="bottom-but-view notabbarbot">
    <button class="savebtn" :style="'background:linear-gradient(90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',1) 100%)'" @tap="syn">更新同步设备</button>
  </view>
  <loading v-if="loading"></loading>
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
      loading: false,
      isload: false,
      count0: 0,
      count1: 0,
      countall: 0,
      sclist: "",
			keyword: '',
      nodata: false,
			bottomButShow:true,
			bid:0,
      pre_url:app.globalData.pre_url
    };
  },
  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.bid = this.opt.bid ? this.opt.bid : '';
		this.getdata();
		if(opt.coupon){
			this.bottomButShow = false;
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
	onNavigationBarSearchInputConfirmed:function(e){
		this.searchConfirm({detail:{value:e.text}});
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
			that.nodata = false;
			that.nomore = false;
			that.loading = true;
      app.post('ApiAdminWaterHappyti/getDeviceList', {keyword:that.keyword,pagenum: pagenum,bid:that.bid}, function (res) {
        that.loading = false;
        var data = res.datalist;
        if (pagenum == 1){
					that.countall = res.countall;
					that.count0 = res.count0;
					that.count1 = res.count1;
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
    syn: function (loadmore) {
      var that = this;
      app.confirm('确定更新同步设备吗?', function () {
        that.loading = true;
        app.post('ApiAdminWaterHappyti/synequipment', {}, function (res) {
          that.loading = false;
          var data = res.datalist;
          if (res.status == 1) {
            app.success(res.msg);
            that.getdata();
          } else {
            app.error(res.msg);
          }
        });
      });

    },
    todel: function (e) {
      var that = this;
      var id = e.currentTarget.dataset.id;
      app.confirm('确定要删除该设备吗?', function () {
        that.loading = true;
        app.post('ApiAdminWaterHappyti/delDevice', {id: id}, function (res) {
          that.loading = false;
          if (res.status == 1) {
            app.success(res.msg);
            that.getdata();
          } else {
            app.error(res.msg);
          }
        });
      });
    },
		searchConfirm:function(e){
			this.keyword = e.detail.value;
      this.getdata(false);
		},
		couponAddChange(item){
			uni.$emit('shopDataEmit',{id:item.id,name:item.name,pic:item.pic,give_num:1});
			uni.navigateBack({
				delta: 1
			});
		}
  }
};
</script>
<style>
.container{ width:100%;}
.topsearch{width:94%;margin:10rpx 3%;}
.topsearch .f1{height:60rpx;border-radius:30rpx;border:0;background-color:#fff;flex:1}
.topsearch .f1 .img{width:24rpx;height:24rpx;margin-left:10px}
.topsearch .f1 input{height:100%;flex:1;padding:0 20rpx;font-size:28rpx;color:#333;}
.order-content{display:flex;flex-direction:column}
.order-box{ width: 94%;margin:10rpx 3%;padding:6rpx 3%; background: #fff;border-radius:8px}

.order-box .content{display:flex;width: 100%; padding:16rpx 0px;border-bottom: 1px #e5e5e5 dashed;position:relative}
.order-box .content:last-child{ border-bottom: 0; }
.order-box .content image{ width: 140rpx; height: 140rpx;}
.order-box .content .detail{display:flex;flex-direction:column;margin-left:14rpx;flex:1}
.order-box .content .detail .t1{font-size:26rpx;min-height:50rpx;line-height:36rpx;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:2;overflow:hidden;}
.order-box .content .detail .t2{height:36rpx;line-height:36rpx;color: #999;overflow: hidden;font-size: 24rpx;}
.order-box .content .detail .t3{display:flex;height: 36rpx;line-height: 36rpx;color: #ff4246;}
.order-box .content .detail .x1{ font-size:30rpx;margin-right:5px}
.order-box .content .detail .x2{ font-size:24rpx;text-decoration:line-through;color:#999}

.order-box .bottom{ width:100%; padding:10rpx 0px; border-top: 1px #e5e5e5 solid; color: #555;}
.order-box .op{ display:flex;align-items:center;width:100%; padding:10rpx 0px; border-top: 1px #e5e5e5 solid; color: #555;}
.btn1{margin-left:20rpx;width:120rpx;height:60rpx;line-height:60rpx;color:#fff;border-radius:3px;text-align:center}
.btn2{margin-left:20rpx;width:120rpx;height:60rpx;line-height:60rpx;color:#333;background:#fff;border:1px solid #cdcdcd;border-radius:3px;text-align:center}
.bottom-but-view{width: 100%;position: fixed;bottom: 0rpx;left: 0rpx;/* #ifdef H5*/margin-bottom: 52rpx;/* #endif */}
.savebtn{ width: 90%; height:96rpx; line-height: 96rpx; text-align:center;border-radius:48rpx; color: #fff;font-weight:bold;margin: 0 5%; border: none; }
</style>