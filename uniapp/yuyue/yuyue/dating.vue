<template>
<view class="container">
	<block v-if="isload">
		<view>
			<view class="search-container">
				<view class="search-box">
					<image class="img" :src="pre_url+'/static/img/search_ico.png'"></image>
					<input class="search-text" placeholder="搜索商家" placeholder-style="color:#aaa;font-size:24rpx" @confirm="searchConfirm"/>
				</view>
			</view>
			<block v-for="(item, index) in datalist" :key="item.id">
			<view class="order-box" @tap="goto" :data-url="'jdorderdetail?id=' + item.id">
				<view class="head">	
				<view class="fwtype1" v-if="item.fwtype != 2">到店</view><view v-else class="fwtype2">上门</view>
					<view class="f1" v-if="item.leftminute>0"><image :src="pre_url+'/static/img/peisong/ps_time.png'" class="img"/><text class="t1">{{item.leftminute}}分钟内</text> 送达</view>
					<view class="f1" v-else><image :src="pre_url+'/static/img/peisong/ps_time.png'" class="img"/>预约时间：{{item.orderinfo.yydate}}<text class="t1" style="margin-left:10rpx"></text></view>
					<view class="flex1"></view>
					<view class="f2"><text class="t1">{{item.ticheng}}</text>元</view>
				</view>
				<view class="content">
          <block v-if="!item.orderinfo.protype">
            <view class="f1" v-if="item.fwtype == 2">
            	<view class="t1"><text class="x1">{{item.juli}}</text><text class="x2">{{item.juli_unit}}</text></view>
            	<view class="t2"><image :src="pre_url+'/static/img/peisong/ps_juli.png'" class="img"/></view>
            	<view class="t3"><text class="x1">{{item.juli2}}</text><text class="x2">{{item.juli2_unit}}</text></view>
            </view>
            <view class="f2">
            	<view class="t1">{{item.binfo.name}}</view>
            	<view class="t2">{{item.binfo.address}}</view>
            	<view class="t3">{{item.orderinfo.address}}</view>
            	<view class="t2">{{item.orderinfo.area}}</view>
            </view>
            <view class="f3" @tap.stop="daohang" :data-index="index" data-protype="0" :data-fwtype="item.fwtype"><image :src="pre_url+'/static/img/peisong/ps_daohang.png'" class="img"/></view>
          </block>
          <block v-else>
            <view class="f1" style="margin-top: 38rpx;">
            	<view class="t3"><text class="x1">{{item.juli2}}</text><text class="x2">{{item.juli2_unit}}</text></view>
            </view>
            <view class="f2">
            	<view class="t3">{{item.orderinfo.address}}</view>
            	<view class="t2">{{item.orderinfo.area}}</view>
            </view>
            <view class="f3" @tap.stop="daohang" :data-index="index" data-protype="1"><image :src="pre_url+'/static/img/peisong/ps_daohang.png'" class="img"/></view>
          </block>
				</view>
				<view class="op">
					<view class="flex1"></view>
					<view  v-if="!item.isqd" class="btn1" 	style="background: #BCBFC7;" >{{item.djs}}后可抢单</view>
					<view class="btn1" v-else @tap.stop="qiangdan" :data-id="item.id">抢单</view>
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
		
		<view class="tabbar">
			<view class="tabbar-bot"></view>
			<view class="tabbar-bar" style="background-color:#ffffff">
				<view @tap="goto" data-url="dating" data-opentype="reLaunch" class="tabbar-item">
					<view class="tabbar-image-box">
						<image class="tabbar-icon" :src="pre_url+'/static/img/peisong/home2.png'"></image>
					</view>
					<view class="tabbar-text active">大厅</view>
				</view>
				<view @tap="goto" data-url="jdorderlist" data-opentype="reLaunch" class="tabbar-item">
					<view class="tabbar-image-box">
						<image class="tabbar-icon" :src="pre_url+'/static/img/peisong/order.png'"></image>
					</view>
					<view class="tabbar-text">订单</view>
				</view>
				<view @tap="goto" data-url="jdorderlist?st=3" data-opentype="reLaunch" class="tabbar-item">
					<view class="tabbar-image-box">
						<image class="tabbar-icon" :src="pre_url+'/static/img/peisong/orderwc.png'"></image>
					</view>
					<view class="tabbar-text">已完成</view>
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
		</view>

	</block>
	<loading v-if="loading"></loading>
	<dp-tabbar :opt="opt"></dp-tabbar>
	<popmsg ref="popmsg"></popmsg>
	<view style="display:none">{{timestamp}}</view>
	<wxxieyi></wxxieyi>
</view>
</template>

<script>
var app = getApp();
var interval2 = null;
export default {
  data() {
    return {
			opt:{},
			loading:false,
      isload: false,
			menuindex:-1,
			pre_url:app.globalData.pre_url,

      st: 'all',
      datalist: [],
      pagenum: 1,
      nomore: false,
      nodata: false,
			interval1:null,
			timestamp:'',
			nowtime:'',
			showform:0,
			nowtime2:'',
			tmplids:''
    };
  },
  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.getdata();
  },
	onUnload:function(){
		clearInterval(this.interval1);
		clearInterval(this.interval2);
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
      app.post('ApiYuyueWorker/dating', {st: st,pagenum: pagenum,keyword:keyword}, function (res) {
				if(res.status==0){
					app.alert(res.msg);
					return;
				}
        var data = res.datalist;
        if (pagenum == 1) {
					that.datalist = data;
					that.nowtime = res.nowtime
					that.nowtime2 = res.nowtime
					that.showform = res.showform;
          if (data.length == 0) {
            that.nodata = true;
          }
					if(res.tmplids){
						that.tmplids = res.tmplids;
					}
					that.loaded();
					that.updatemylocation(false);
					clearInterval(that.interval1);
					that.interval1 = setInterval(function(){
						that.updatemylocation(true);
						that.nowtime = that.nowtime + 10;
					},10000)
					
					if(res.isdelayed){
						clearInterval(interval2);
						interval2 = setInterval(function () {
							that.nowtime2 = that.nowtime2 + 1;
							that.getdjs();
						}, 1000);
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
		
		getdjs: function () {
		  var that = this;
		  var nowtime = that.nowtime2;
		  for (var i in that.datalist) {
		    var thisteam = that.datalist[i];
		    var totalsec = thisteam.createtime * 1 + thisteam.delayedtime * 60 - nowtime * 1;
		    if (totalsec <= 0) {
		      that.datalist[i].isqd = true;
		    } else {
		      var houer = Math.floor(totalsec / 3600);
		      var min = Math.floor((totalsec - houer * 3600) / 60);
		      var sec = totalsec - houer * 3600 - min * 60;
		      var djs =  (min < 10 ? '0' : '') + min + '分' + (sec < 10 ? '0' : '') + sec + '秒';
		    }
				that.datalist[i].djs = djs;
		  }
	
		},
		updatemylocation:function(needload){
			var that = this;
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
				app.get('ApiYuyueWorker/updatemylocation',{longitude:longitude,latitude:latitude,t:that.timestamp},function(){
					//if(needload) that.getdata();
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
    qiangdan: function (e) {
      var that = this;
      var id = e.currentTarget.dataset.id;
      var st = e.currentTarget.dataset.st;
      app.confirm('确定要接单吗?', function () {
				app.showLoading('提交中');
        app.post('ApiYuyueWorker/qiangdan', {id: id}, function (data) {
					app.showLoading(false);
          app.success(data.msg);
					that.subscribeMessage(function () {
						setTimeout(function () {
						  that.getdata();
						}, 1000);
					});
        });
      });
    },
		daohang:function(e){
			var that = this;
			var index = e.currentTarget.dataset.index;
			var datainfo = that.datalist[index];
      var protype = e.currentTarget.dataset.protype;
			var fwtype = e.currentTarget.dataset.fwtype;
      if(protype==1 || fwtype == 2){
				var list = ['导航到用户'];
			}else{
				var list = ['导航到商家'];
			}
			uni.showActionSheet({
        itemList: list,
        success: function (res) {
					if(res.tapIndex >= 0){
						if (res.tapIndex == 0) {
							if(protype==1){
								// 用户
								var longitude = datainfo.longitude2
								var latitude = datainfo.latitude2
								var name = datainfo.orderinfo.address
								var address = datainfo.orderinfo.address
							}else{
								if(fwtype != 2){
									// 到店
									var longitude = datainfo.longitude
									var latitude = datainfo.latitude
									var name = datainfo.binfo.name
									var address = datainfo.binfo.address
								}else{
									// 上门
									var longitude = datainfo.longitude2
									var latitude = datainfo.latitude2
									var name = datainfo.orderinfo.address
									var address = datainfo.orderinfo.address
								}
							}
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
    receiveMessage: function (data) {
			var that = this;
			if(data.type == 'peisong' || data.type == 'peisong_jiedan') {
				that.getdata();
			}
			return false;
		},

  }
};
</script>
<style>
@import "./common.css";
.container{ width:100%;display:flex;flex-direction:column}
.search-container {width: 100%;height:100rpx;padding: 20rpx 23rpx 20rpx 23rpx;background-color: #fff;position: relative;overflow: hidden;border-bottom:1px solid #f5f5f5}
.search-box {display:flex;align-items:center;height:60rpx;border-radius:30rpx;border:0;background-color:#f7f7f7;flex:1}
.search-box .img{width:24rpx;height:24rpx;margin-right:10rpx;margin-left:30rpx}
.search-box .search-text {font-size:24rpx;color:#222;width: 100%;}

.order-box{ width: 94%;margin:20rpx 3%;padding:6rpx 3%; background: #fff;border-radius:8px}
.order-box .head{ display:flex;width:100%; border-bottom: 1px #f5f5f5 solid; height:88rpx; line-height:88rpx; overflow: hidden; color: #999;}
.order-box .head .f1{display:flex;align-items:center;color:#222222;font-size: 26rpx;}
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
.order-box .op .btn1{width:100%;background:linear-gradient(-90deg, #06A051 0%, #03B269 100%);;height:88rpx;line-height:88rpx;color:#fff;border-radius:10rpx;text-align:center;font-size:32rpx}
.fwtype1{background: linear-gradient(-90deg, #06A051 0%, #03B269 100%);color: #fff;border-radius: 8rpx;
	text-align: center;margin: 24rpx 10rpx;padding: 5rpx 8rpx;line-height: 33rpx;font-size: 22rpx;}
.fwtype2{background: #FF6F30;color: #fff;border-radius: 8rpx;text-align: center;margin: 24rpx 8rpx;padding: 5rpx 10rpx;line-height: 33rpx;font-size: 22rpx;}
</style>