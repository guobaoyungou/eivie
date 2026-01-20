<template>
<view class="container">
	<block v-if="isload">
		<view class="search-container">
			<view class="topsearch flex-y-center">
				<view class="f1 flex-y-center">
					<image class="img" :src="pre_url+'/static/img/search_ico.png'"></image>
					<input :value="keyword" placeholder="搜索相关信息" placeholder-style="font-size:24rpx;color:#C2C2C2" confirm-type="search" @confirm="search"></input>
				</view>
			</view>
		</view>
		<view class="ind_business">
			<view class="ind_buslist" id="datalist">
				<block v-for="(item, index) in datalist" :key="index">
				<view @tap="goto" :data-url="item.tourl ? item.tourl : '/pagesA/formdata/detail?id=' + item.id">
					<view class="ind_busbox flex1 flex-row">
						<view class="ind_buspic flex0"><image :src="item.logo"></image></view>
						<view class="flex1" style="flex: 1;">
							<view class="bus_title">{{item.title}}</view>
							<view class="bus_address" v-if="item.address" @tap.stop="openLocation" :data-latitude="item.latitude" :data-longitude="item.longitude" :data-company="item.title" :data-address="item.address">
                <image :src="pre_url+'/static/img/b_addr.png'" style="width:26rpx;height:26rpx;margin-right:10rpx;float: left;margin-top: 4rpx;"/>
                <text class="x1">{{item.address}}</text><text class="x2">{{item.juli}}</text>
              </view>
							<view class="bus_address" v-if="item.tel" @tap.stop="phone" :data-phone="item.tel">
                <image :src="pre_url+'/static/img/b_tel.png'" style="width:26rpx;height:26rpx;margin-right:10rpx;float: left;margin-top: 4rpx;"/>
                <text class="x1">联系电话：{{item.tel}}</text>
              </view>
						</view>
            <view 
              @tap.stop="goto" :data-url="item.tourl ? item.tourl : '/pagesA/formdata/detail?id=' + item.id" 
              style="width: 140rpx;text-align: center;line-height: 142rpx;font-size: 24rpx;">
              查看信息
            </view>
					</view>
				</view>
				</block>
				<nomore v-if="nomore"></nomore>
				<nodata v-if="nodata"></nodata>
			</view>
		</view>
	</block>
	<loading v-if="loading"></loading>
	<dp-tabbar :opt="opt"></dp-tabbar>
	<popmsg ref="popmsg"></popmsg>
	<wxxieyi></wxxieyi>
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
  
      id: 0,
      longitude: '',
      latitude: '',
			clist:[],
      datalist: [],
      pagenum: 1,
      keyword: '',
      nomore: false,
      nodata: false,
    };
  },

  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.id = this.opt.id;
		this.getdata();
  },
	onPullDownRefresh: function () {
		this.getdata();
	},
  onReachBottom: function () {
    if (!this.nodata && !this.nomore) {
      this.pagenum = this.pagenum + 1;
      this.getDataList(true);
    }
  },
  methods: {
		getdata: function () {
			var that = this;
      app.getLocation(function (res) {
      	var latitude = res.latitude;
      	var longitude = res.longitude;
      	that.longitude = longitude;
      	that.latitude = latitude;
      	that.getDataList(false);
      },function () {
				that.getDataList(false);
			});
		},
    getDataList: function (loadmore) {
			if(!loadmore){
				this.pagenum = 1;
				this.datalist = [];
			}
      var that = this;
      var pagenum   = that.pagenum;
      var latitude  = that.latitude;
      var longitude = that.longitude;
      var keyword   = that.keyword;
			that.loading = true;
			that.nodata  = false;
			that.nomore  = false;
      app.post('ApiForm/formdata', {pagenum: pagenum,id: that.id,longitude: longitude,latitude: latitude,keyword: keyword}, function (res) {
        that.loading = false;
        that.loaded();
				uni.stopPullDownRefresh();

        var data = res.data;
        if (pagenum == 1) {
          uni.setNavigationBarTitle({
            title: res.title
          });
          that.datalist = data;
          if (data.length == 0) {
            that.nodata = true;
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
    search: function (e) {
      var that = this;
      var keyword = e.detail.value;
      that.keyword = keyword;
			that.pagenum = 1;
      that.datalist = [];
      that.getDataList();
    },
		openLocation:function(e){
			var latitude = parseFloat(e.currentTarget.dataset.latitude)
			var longitude = parseFloat(e.currentTarget.dataset.longitude)
			var address = e.currentTarget.dataset.address
			if(latitude && longitude){
			  uni.openLocation({
			     latitude:latitude,
			     longitude:longitude,
			     name:address,
			     scale: 13
			  })	
			}	
		},
		phone:function(e) {
			var phone = e.currentTarget.dataset.phone;
			uni.makePhoneCall({
				phoneNumber: phone,
				fail: function () {
				}
			});
		},
  }
};
</script>
<style>
.search-container {position: fixed;width: 100%;background: #fff;z-index:9;top:var(--window-top)}
.topsearch{width:100%;padding:16rpx 20rpx;}
.topsearch .f1{height:60rpx;border-radius:30rpx;border:0;background-color:#f7f7f7;flex:1}
.topsearch .f1 .img{width:24rpx;height:24rpx;margin-left:10px}
.topsearch .f1 input{height:100%;flex:1;padding:0 20rpx;font-size:28rpx;color:#333;}
.topsearch .search-btn{display:flex;align-items:center;color:#5a5a5a;font-size:30rpx;width:60rpx;text-align:center;margin-left:20rpx}
.search-navbar {display: flex;text-align: center;align-items:center;padding:5rpx 0}
.search-navbar-item {flex: 1;height: 70rpx;line-height: 70rpx;position: relative;font-size:28rpx;font-weight:bold;color:#323232}
.search-navbar-item .iconshangla{position: absolute;top:-4rpx;padding: 0 6rpx;font-size: 20rpx;color:#7D7D7D}
.search-navbar-item .icondaoxu{position: absolute;top: 8rpx;padding: 0 6rpx;font-size: 20rpx;color:#7D7D7D}
.search-navbar-item .iconshaixuan{margin-left:10rpx;font-size:22rpx;color:#7d7d7d}

.ind_business {width: 100%;margin-top: 100rpx;font-size:26rpx;padding:0 24rpx}
.ind_business .ind_busbox{ width:100%;background: #fff;padding:20rpx;overflow: hidden; margin-bottom:20rpx;border-radius:8rpx;position:relative}
.ind_business .ind_buspic{ width:120rpx;height:120rpx; margin-right: 28rpx; }
.ind_business .ind_buspic image{ width: 100%;height:100%;border-radius: 8rpx;object-fit: cover;}
.ind_business .bus_title{ font-size: 30rpx; color: #222;font-weight:bold;line-height:46rpx}
.ind_business .bus_score{font-size: 24rpx;color:#FC5648;display:flex;align-items:center}
.ind_business .bus_score .img{width:24rpx;height:24rpx;margin-right:10rpx}
.ind_business .bus_score .txt{margin-left:20rpx}
.ind_business .indsale_box{ display: flex}
.ind_business .bus_sales{ font-size: 24rpx; color:#999;position:absolute;top:20rpx;right:28rpx}

.ind_business .bus_address{color:#999;font-size: 22rpx;line-height: 36rpx;margin-top:6rpx;overflow:hidden;}
.ind_business .bus_address .x2{padding-left:20rpx}
</style>