<template>
<view class="container">
	<dd-tab :itemdata="['全部('+countall+')','已上架('+count1+')','未上架('+count0+')']" :itemst="['all','1','0']" :st="st" :isfixed="true" @changetab="changetab"></dd-tab>
	<view style="width:100%;height:100rpx"></view>
	<!-- #ifndef H5 || APP-PLUS -->
	<view class="topsearch flex-y-center">
		<view class="f1 flex-y-center">
			<image class="img" :src="pre_url+'/static/img/search_ico.png'"></image>
			<input :value="keyword" placeholder="输入关键字搜索" placeholder-style="font-size:24rpx;color:#C2C2C2" @confirm="searchConfirm"></input>
		</view>
	</view>
	<!--  #endif -->
	<view class="order-content" id="datalist">
	<block v-for="(item, index) in datalist" :key="index">
		<view class="order-box">
			<view class="content" style="border-bottom:none">
				<view>
					<image :src="item.pic"></image>
				</view>
				<view class="detail">
					<text class="t1">{{item.name}}</text>
					<view class="t2">剩余：{{item.stock}}<text style="color:#a88;padding-left:20rpx">已售：{{item.sales}}</text></view>
					<view class="t3"><text class="x1">￥{{item.sell_price}}</text><text class="x2">￥{{item.market_price}}</text></view>
				</view>
			</view>
			<view class="op">
				<text style="color:red" class="flex1" v-if="!item.status || item.status==0">未上架</text>
				<text style="color:green" class="flex1" v-else>已上架</text>
				<text style="color:orange" class="flex1" v-if="!item.ischecked">待审核</text>
				<block v-if="bottomButShow">
					<view class="btn1" :style="{background:t('color1')}" @tap="setst" data-st="1" :data-id="item.id" v-if="!item.status || item.status==0">上架</view>
					<view class="btn1" :style="{background:t('color2')}" @tap="setst" data-st="0" :data-id="item.id" v-else>下架</view>
					<view @tap="goto" :data-url="'edit?id='+item.id" class="btn2">编辑</view>
					<view class="btn2" @tap="todel" :data-id="item.id">删除</view>
				</block>
				<block v-else>
					<view class="btn1" :style="{background:t('color1')}" @click="couponAddChange(item)">添加</view>
				</block>
			</view>
		</view>
	</block>
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
      app.post('ApiAdminRestaurantProduct/index', {keyword:that.keyword,pagenum: pagenum,st: that.st,bid:that.bid}, function (res) {
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
    todel: function (e) {
      var that = this;
      var id = e.currentTarget.dataset.id;
      app.confirm('确定要删除该菜品吗?', function () {
        app.post('ApiAdminRestaurantProduct/del', {id: id}, function (res) {
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
        app.post('ApiAdminRestaurantProduct/setst', {st: st,id: id}, function (res) {
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
</style>