<template>
<view class="container">
	<block v-if="isload">
		<view>

			<block v-for="(item, index) in datalist" :key="item.id">
			<view class="order-box" @tap="goto" :data-url="'psorderdetail?id=' + item.id">
				<view class="head">
					<view class="f1"><image src="/static/peisong/ps_time.png" class="img"/>
				        <text class="t1">预约时间 {{item.yy_time}}</text>
				    </view>
					<view class="flex1"></view>
				</view>
				<view class="content">
					<view class="f1">
						<view class="t1"><text class="x1">{{item.juli}}</text><text class="x2">{{item.juli_unit}}</text></view>
						<view class="t2"><image src="/static/peisong/ps_juli.png" class="img"/></view>
						<view class="t3"><text class="x1">{{item.juli2}}</text><text class="x2">{{item.juli2_unit}}</text></view>
					</view>
					<view class="f2">
						<view class="t1">{{mendian&&mendian.name?mendian.name:''}}</view>
						<view class="t2">{{mendian&&mendian.address?mendian.address:''}}</view>
						<view class="t3">{{item.address}}</view>
						<view class="t2">{{item.area}}</view>
					</view>
					<view class="f3" @tap.stop="daohang" :data-index="index"><image :src="pre_url+'/static/img/peisong/ps_daohang.png'" class="img"/></view>
				</view>
				<view class="op">
					<view class="t1" v-if="item.status==1">已接单，正在赶往顾客家</view>
					<view class="t1" v-if="item.status==2">已取货，入库中</view>
					<view class="t1" v-if="item.status==3">已入库，清洗中</view>
					<view class="t1" v-if="item.status==4">已清洗，送货中</view>
					<view class="flex1"></view>
					<view class="btn1" @tap.stop="goto"  :data-url="'psqh?id=' + item.id" v-if="item.status==1">取货完成</view>
					<view class="btn1" @tap.stop="setst" :data-id="item.id" data-st="3" v-if="item.status==2">入库完成</view>
					<view class="btn1" @tap.stop="setst" :data-id="item.id" data-st="4" v-if="item.status==3">清洗完成</view>
				</view>
			</view>
			</block>
		
			<nomore v-if="nomore"></nomore>
			<nodata v-if="nodata"></nodata>
		</view>
		<view class="tabbar">
			<view class="tabbar-bot"></view>
			<view class="tabbar-bar" style="background-color:#ffffff">
				<view @tap="goto" data-url="psdating" data-opentype="reLaunch" class="tabbar-item">
					<view class="tabbar-image-box">
						<image class="tabbar-icon" :src="pre_url+'/static/img/peisong/home.png'"></image>
					</view>
					<view class="tabbar-text">大厅</view>
				</view>
				<view @tap="goto" data-url="psorderlist" data-opentype="reLaunch" class="tabbar-item">
					<view class="tabbar-image-box">
						<image class="tabbar-icon" :src="pre_url+'/static/img/peisong/order'+(st!=5?'2':'')+'.png'"></image>
					</view>
					<view class="tabbar-text" :class="st!=5?'active':''">订单</view>
				</view>
				<view @tap="goto" data-url="psorderlist?st=5" data-opentype="reLaunch" class="tabbar-item">
					<view class="tabbar-image-box">
						<image class="tabbar-icon" :src="pre_url+'/static/img/peisong/orderwc'+(st==5?'2':'')+'.png'"></image>
					</view>
					<view class="tabbar-text" :class="st==5?'active':''">已完成</view>
				</view>
			</view>
		</view>
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
        
        longitude : '',
        latitude  : '',
        mendian:''
    };
  },
    onLoad: function (opt) {
        this.opt = app.getopts(opt);
        this.st = this.opt.st || '11';
    },
    onShow:function(){
        this.getdata();
    },
	onUnload:function(){
		clearInterval(this.interval1);
	},
	onPullDownRefresh: function () {
		this.getorderlist();
	},
    onReachBottom: function () {
        if (!this.nodata && !this.nomore) {
          this.pagenum = this.pagenum + 1;
          this.getorderlist(true);
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
        getdata: function () {
            var that = this;
            app.getLocation(function(res){
                that.longitude = res.longitude;
                that.latitude  = res.latitude;
                that.getorderlist();
            },function(res){
                that.getorderlist();
            });
        },
        getorderlist: function (loadmore) {
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
            that.loading = true;
            app.post('ApiXixie/psorderlist', {st: st,pagenum: pagenum,keyword:keyword,longitude:that.longitude,latitude:that.latitude}, function (res) {
                that.loading = false;
                if(res.status==1){
                    var data = res.datalist;
                    if (pagenum == 1) {
                        that.datalist = data;
                        that.mendian  = res.mendian;
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
                }else if(res.status==444){
                    app.error(res.msg);
                    setTimeout(function(){
                        app.goto("pslogin",'reLaunch');
                    },500);
                }else{
                    app.alert(res.msg);
                    return;
                }
                
            });
        },
        setst: function (e) {
          var that = this;
          var id = e.currentTarget.dataset.id;
          var st = e.currentTarget.dataset.st;
            if(st == 2){
                var tips = '确定取货完成吗?';
            }if(st == 3){
                var tips = '确定入库完成吗?';
            }if(st == 4){
                var tips = '确定清洗完成吗?';
            }
            app.confirm(tips, function () {
                app.showLoading('提交中');
                app.post('ApiXixie/changeStatus', {id: id,st:st}, function (data) {
                    app.showLoading(false);
                    if(data.status == 1){
                        app.success(data.msg);
                        setTimeout(function () {
                            that.getdata();
                        }, 1000);
                    }else if(res.status==444){
                        app.error(res.msg);
                        setTimeout(function(){
                            app.goto("pslogin",'reLaunch');
                        },500);
                    }else{
                        app.alset(data.msg);
                    }
                });
            });
        },
        daohang:function(e){
            var that = this;
            var index = e.currentTarget.dataset.index;
            var datainfo = that.datalist[index];
            var mendian  = that.mendian;
            uni.showActionSheet({
            itemList: ['导航到商家', '导航到用户'],
            success: function (res) {
                    if(res.tapIndex >= 0){
                        if (res.tapIndex == 0) {
                            var longitude = mendian.longitude
                            var latitude  = mendian.latitude
                            var name      = mendian.name
                            var address   = mendian.address
                        }else{
                            var longitude = datainfo.longitude
                            var latitude  = datainfo.latitude
                            var name      = datainfo.address
                            var address   = datainfo.address
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
        }
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
.order-box .content .f1 .t2 .img{width:12rpx;height:36rpx; margin: 10rpx 0;}

.order-box .content .f1 .t3{display:flex;flex-direction:column;align-items:center}
.order-box .content .f1 .t3 .x1{color:#FF6F30;font-size:28rpx;font-weight:bold}
.order-box .content .f1 .t3 .x2{color:#999999;font-size:24rpx}
.order-box .content .f2{flex:1;padding:0 20rpx}
.order-box .content .f2 .t1{font-size:30rpx;color:#222222;font-weight:bold;line-height:50rpx;margin-bottom:6rpx;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:2;overflow:hidden;}
.order-box .content .f2 .t2{font-size:28rpx;color:#222222;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:3;overflow:hidden;}
.order-box .content .f2 .t3{font-size:30rpx;color:#222222;font-weight:bold;line-height:50rpx;margin-top:30rpx;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:2;overflow:hidden;}
.order-box .content .f3 .img{width:72rpx;height:168rpx}

.order-box .op{display:flex;justify-content:flex-end;align-items:center;width:100%; padding:20rpx 0px; border-top: 1px #f4f4f4 solid; color: #555;}
.order-box .op .t1{color:#06A051;font-weight:bold}
.order-box .op .btn1{width:200rpx;background:linear-gradient(-90deg, #06A051 0%, #03B269 100%);;height:80rpx;line-height:80rpx;color:#fff;border-radius:10rpx;text-align:center;font-size:32rpx}

</style>