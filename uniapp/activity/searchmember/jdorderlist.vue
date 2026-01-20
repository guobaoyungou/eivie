<template>
<view class="container">
	<block v-if="isload">
		<view>
			<view class="search-container">
				<view class="search-box">
					<image class="img" src="/static/img/search_ico.png"></image>
					<input class="search-text" placeholder="搜索商家" placeholder-style="color:#aaa;font-size:24rpx" @confirm="searchConfirm"/>
				</view>
			</view>
			<block v-for="(item, index) in datalist" :key="item.id">
			<view class="order-box" @tap="goto" :data-url="'jdorderdetail?id=' + item.id">
				<view class="head">
					<view v-if="item.fwtype==1">
						<view class="f1" v-if="item.status==3"><image src="/static/peisong/ps_time.png" class="img"/>已完成</view>
						<view class="f1" v-if="item.status==1"><image src="/static/peisong/ps_time.png" class="img"/><text class="t1">等待客户上门</text> </view>
						<view class="f1" v-if="item.status==2"><image src="/static/peisong/ps_time.png" class="img"/><text class="t1" style="margin-left:10rpx">服务中</text></view>
					</view>
					<view v-else-if="item.fwtype==2">
						<view class="f1" v-if="item.status==3"><image src="/static/peisong/ps_time.png" class="img"/>已完成</view>
						<view class="f1" v-else-if="item.status==1"><image src="/static/peisong/ps_time.png" class="img"/>期望上门时间<text class="t1">{{item.orderinfo.yydate}}</text> </view>
						<view class="f1" v-else-if="item.status==2"  ><image src="/static/peisong/ps_time.png" class="img"/>已到达，服务中</view>
					</view>
					<view class="flex1"></view>
					<view class="f2"><text class="t1">{{item.ticheng}}</text>元</view>
				</view>
				<view class="content">
					<!-- <view class="f1">
						<view class="t1"><text class="x1">{{item.juli}}</text><text class="x2">{{item.juli_unit}}</text></view>
						<view class="t2"><image src="/static/peisong/ps_juli.png" class="img"/></view>
						<view class="t3"><text class="x1">{{item.juli2}}</text><text class="x2">{{item.juli2_unit}}</text></view>
					</view> -->
					<view class="f2">
						<view class="t1">{{item.binfo.name}}</view>
						<view class="t2">{{item.binfo.address}}</view>
						<view class="t3">{{item.orderinfo.address}}</view>
						<view class="t2">{{item.orderinfo.area}}</view>
					</view>
					<view class="f3" @tap.stop="daohang" :data-index="index"><image :src="pre_url+'/static/img/peisong/ps_daohang.png'" class="img"/></view>
				</view>
				<view v-if="item.fwtype==1" class="op">
					<view class="t1" v-if="item.status==1">已接单，待顾客上门</view>
					<view class="t1" v-if="item.status==2">顾客已到达</view>
					<view class="t1" v-if="item.status==3">已完成</view>
					<view class="flex1"></view>
					<!-- <view class="btn1" @tap.stop="setst" :data-id="item.id" data-st="2" v-if="item.status==1">顾客已到达</view>
					<view class="btn1" @tap.stop="setst" :data-id="item.id" data-st="3" v-if="item.status==2">我已完成</view> -->
				</view>
				<view v-else-if="item.fwtype==2" class="op">
					<view class="t1" v-if="item.status==1">已接单，等待上门</view>
					<view class="t1" v-if="item.status==2">已到达，共用时{{item.useminute}}分钟</view>
					<view class="t1" v-if="item.status==3">已完成</view>
					<view class="flex1"></view>
					<!-- <view class="btn1" @tap.stop="setst" :data-id="item.id" data-st="2" v-if="item.status==1">我已到达</view>
					<view class="btn1" @tap.stop="setst" :data-id="item.id" data-st="3" v-if="item.status==2">我已完成</view> -->
				</view>
			
			</view>
			</block>
		
			<nomore v-if="nomore"></nomore>
			<nodata v-if="nodata"></nodata>
		</view>
		<!-- <view style="width:100%;height:120rpx"></view> -->
		<!-- <view class="bottom">
			<view class="my">
				<image src="/static/img/my.png" class="img"/>
				<text>我的</text>
			</view>
			<view class="btn1" @tap="setpsst" data-st="1" v-if="psuser.status==0">暂停接单中</view>
			<view class="btn2" :style="{background:t('color1')}" @tap="setpsst" data-st="0" v-if="psuser.status==1">开启接单中</view>
		</view> -->
		
		<!-- <view class="tabbar" v-if="showtabbar">
			<view class="tabbar-bot"></view>
			<view class="tabbar-bar" style="background-color:#ffffff">
				<view @tap="goto" data-url="dating" data-opentype="reLaunch" class="tabbar-item">
					<view class="tabbar-image-box">
						<image class="tabbar-icon" :src="pre_url+'/static/img/peisong/home.png'"></image>
					</view>
					<view class="tabbar-text">大厅</view>
				</view>
				<view @tap="goto" data-url="jdorderlist" data-opentype="reLaunch" class="tabbar-item">
					<view class="tabbar-image-box">
						<image class="tabbar-icon" :src="pre_url+'/static/img/peisong/order'+(st!=3?'2':'')+'.png'"></image>
					</view>
					<view class="tabbar-text" :class="st!=3?'active':''">订单</view>
				</view>
				<view @tap="goto" data-url="jdorderlist?st=3" data-opentype="reLaunch" class="tabbar-item">
					<view class="tabbar-image-box">
						<image class="tabbar-icon" :src="pre_url+'/static/img/peisong/orderwc'+(st==3?'2':'')+'.png'"></image>
					</view>
					<view class="tabbar-text" :class="st==3?'active':''">已完成</view>
				</view>
				<view v-if="showform" @tap="goto" data-url="formlog" data-opentype="reLaunch" class="tabbar-item">
					<view class="tabbar-image-box">
						<image class="tabbar-icon" :src="pre_url+'/static/img/peisong/dangan.png'"></image>
					</view>
					<view class="tabbar-text">档案</view>
				</view>
				<view @tap="goto" data-url="my" data-opentype="reLaunch" class="tabbar-item">
					<view class="tabbar-image-box">
						<image class="tabbar-icon" :src="pre_url+'/static/img/peisong/my.png'"></image>
					</view>
					<view class="tabbar-text">我的</view>
				</view>
			</view>
		</view> -->

	</block>
	<loading v-if="loading"></loading>
	<dp-tabbar :opt="opt"></dp-tabbar>
	<popmsg ref="popmsg"></popmsg>
	<view style="display:none">{{timestamp}}</view>
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

      st: '11',
      datalist: [],
      pagenum: 1,
      nomore: false,
      nodata: false,
			keyword:'',
			interval1:null,
			timestamp:'',
			showform:0,
			showtabbar:false,
    };
  },
  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.st = this.opt.st || '11';
		if(this.opt.mid){
			this.showtabbar = false;
		}else{
			this.showtabbar = true;
		}
		this.getdata();
  },
	onUnload:function(){
		clearInterval(this.interval1);
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
      this.st = st;
      uni.pageScrollTo({
        scrollTop: 0,
        duration: 0
      });
      this.getdata();
    },
    getdata: function (loadmore) {
			if(!loadmore){
				this.pagenum = 1;
				this.datalist = [];
			}
      var that = this;
      var st = that.st;
      var pagenum = that.pagenum;
      var keyword = that.keyword;
			that.nodata = false;
			that.nomore = false;
      app.post('ApiSearchMember/orderlist', {st: st,pagenum: pagenum,keyword:keyword,mid:this.opt.mid}, function (res) {
				if(res.status==0){
					app.alert(res.msg);
					return;
				}
        var data = res.datalist;
        if (pagenum == 1) {
					that.datalist = data;
					that.nowtime = res.nowtime
					that.showform = res.showform;
          if (data.length == 0) {
            that.nodata = true;
          }
					that.loaded();
					that.updatemylocation();
					clearInterval(that.interval1);
					that.interval1 = setInterval(function(){
						that.updatemylocation(true);
						that.nowtime = that.nowtime + 10;
					},10000)
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
		updatemylocation:function(){
			var that = this;
			console.log('updatemylocation');
			app.getLocation(function(res){
				var longitude = res.longitude;
				var latitude = res.latitude;
				var datalist = that.datalist;
				for(var i in datalist){
					var thisdata = datalist[i];
					var rs = that.getdistance(thisdata.longitude2,thisdata.latitude2,longitude,latitude,1);
					thisdata.juli2 = rs.juli;
					thisdata.juli2_unit = rs.unit;
					thisdata.leftminute = parseInt((thisdata.yujitime - that.nowtime) / 60);
					datalist[i] = thisdata;
				}
				that.datalist = datalist;
				that.timestamp = parseInt((new Date().getTime())/1000);
				app.get('ApiPeisong/updatemylocation',{longitude:longitude,latitude:latitude,t:that.timestamp},function(){
				//	if(needload) that.getdata();
				});
			});
		},
		getdistance: function (lng1, lat1, lng2, lat2) {
			if(!lat1 || !lng1 || !lat2 || !lng2) return '';
			var rad1 = lat1 * Math.PI / 180.0;
			var rad2 = lat2 * Math.PI / 180.0;
			var a = rad1 - rad2;
			var b = lng1 * Math.PI / 180.0 - lng2 * Math.PI / 180.0;
			var r = 6378137;
			var juli = r * 2 * Math.asin(Math.sqrt(Math.pow(Math.sin(a / 2), 2) + Math.cos(rad1) * Math.cos(rad2) * Math.pow(Math.sin(b / 2), 2)));
			var unit = 'm';
			if(juli> 1000){
				juli = juli/1000;
				unit = 'km';
			}
			juli = juli.toFixed(1);
			return {juli:juli,unit:unit}
		},
    setst: function (e) {
      var that = this;
      var id = e.currentTarget.dataset.id;
      var st = e.currentTarget.dataset.st;
			if(st == 2){
				var tips = '确定改为已到达吗?';
			}if(st == 3){
				var tips = '确定改为已完成吗?';
			}
      app.confirm(tips, function () {
				app.showLoading('提交中');
        app.post('ApiYuyueWorker/setst', {id: id,st:st}, function (data) {
					app.showLoading(false);
          app.success(data.msg);
          setTimeout(function () {
            that.getdata();
          }, 1000);
        });
      });
    },
		daohang:function(e){
			var that = this;
			var index = e.currentTarget.dataset.index;
			var datainfo = that.datalist[index];
			uni.showActionSheet({
        itemList: ['导航到商家', '导航到用户'],
        success: function (res) {
					if(res.tapIndex >= 0){
						if (res.tapIndex == 0) {
							var longitude = datainfo.longitude
							var latitude = datainfo.latitude
							var name = datainfo.binfo.name
							var address = datainfo.binfo.address
						}else{
							var longitude = datainfo.longitude2
							var latitude = datainfo.latitude2
							var name = datainfo.orderinfo.address
							var address = datainfo.orderinfo.address
						}
						uni.openLocation({
							latitude:parseFloat(latitude),
							longitude:parseFloat(longitude),
							name:name,
							address:address,
							scale: 13,
							success: function () {
                console.log('success');
							},
							fail:function(res){
								console.log(res);
							}
						})
					}
				}
			});
		},
    searchConfirm: function (e) {
      var that = this;
      var keyword = e.detail.value;
      that.keyword = keyword
      that.getdata();
    },
  }
};
</script>
<style>
@import "../yuyue/common.css";
.container{ width:100%;display:flex;flex-direction:column}
.search-container {width: 100%;height:100rpx;padding: 20rpx 23rpx 20rpx 23rpx;background-color: #fff;position: relative;overflow: hidden;border-bottom:1px solid #f5f5f5}
.search-box {display:flex;align-items:center;height:60rpx;border-radius:30rpx;border:0;background-color:#f7f7f7;flex:1}
.search-box .img{width:24rpx;height:24rpx;margin-right:10rpx;margin-left:30rpx}
.search-box .search-text {font-size:24rpx;color:#222;width: 100%;}

.order-box{ width: 94%;margin:20rpx 3%;padding:6rpx 3%; background: #fff;border-radius:8px}
.order-box .head{ display:flex;width:100%; border-bottom: 1px #f5f5f5 solid; height:88rpx; line-height:88rpx; overflow: hidden; color: #999;}
.order-box .head .f1{display:flex;align-items:center;color:#222222}
.order-box .head .f1 .img{width:24rpx;height:24rpx;margin-right:4px}
.order-box .head .f1 .t1{color:#06A051;margin-right:10rpx}
.order-box .head .f2{color:#FF6F30}
.order-box .head .f2 .t1{font-size:36rpx;margin-right:4rpx}

.order-box .content{display:flex;justify-content:space-between;width: 100%; padding:16rpx 0px;border-bottom: 1px solid #f5f5f5;position:relative}
.order-box .content .f1{width:100rpx;display:flex;flex-direction:column;align-items:center}
.order-box .content .f1 .t1{display:flex;flex-direction:column;align-items:center}
.order-box .content .f1 .t1 .x1{color:#FF6F30;font-size:28rpx;font-weight:bold}
.order-box .content .f1 .t1 .x2{color:#999999;font-size:24rpx;margin-bottom:8rpx}
.order-box .content .f1 .t2 .img{width:12rpx;height:36rpx}

.order-box .content .f1 .t3{display:flex;flex-direction:column;align-items:center}
.order-box .content .f1 .t3 .x1{color:#FF6F30;font-size:28rpx;font-weight:bold}
.order-box .content .f1 .t3 .x2{color:#999999;font-size:24rpx}
.order-box .content .f2{flex:1;padding:0 20rpx}
.order-box .content .f2 .t1{font-size:36rpx;color:#222222;font-weight:bold;line-height:50rpx;margin-bottom:6rpx;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:1;overflow:hidden;}
.order-box .content .f2 .t2{font-size:24rpx;color:#222222;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:1;overflow:hidden;}
.order-box .content .f2 .t3{font-size:36rpx;color:#222222;font-weight:bold;line-height:50rpx;margin-top:30rpx;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:1;overflow:hidden;}
.order-box .content .f3 .img{width:72rpx;height:168rpx}

.order-box .op{display:flex;justify-content:flex-end;align-items:center;width:100%; padding:20rpx 0px; border-top: 1px #f4f4f4 solid; color: #555;}
.order-box .op .t1{color:#06A051;font-weight:bold}
.order-box .op .btn1{width:200rpx;background:linear-gradient(-90deg, #06A051 0%, #03B269 100%);;height:88rpx;line-height:88rpx;color:#fff;border-radius:10rpx;text-align:center;font-size:32rpx}

</style>