<template>
<view class="container">
	<block v-if="isload">
		<dd-tab :itemdata="['全部','待确认','已确认','已驳回','待支付']" :itemst="['all','0','1','2','10']" :st="st" :isfixed="true" @changetab="changetab"></dd-tab>
		<view style="width:100%;height:100rpx"></view>
		<!-- #ifndef H5 || APP-PLUS -->
		<view class="topsearch flex-y-center">
			<view class="f1 flex-y-center">
				<image class="img" :src="pre_url+'/static/img/search_ico.png'"></image>
				<input :value="keyword" placeholder="输入关键字搜索" placeholder-style="font-size:24rpx;color:#C2C2C2" @confirm="searchConfirm"></input>
			</view>
		</view>
		<!--  #endif -->
		<view class="content" id="datalist">
			<view class="item" @tap="goto" :data-url="'formdetail?id=' + item.id" v-for="(item, index) in datalist" :key="index">
				<view class="f1">
					<text class="t1">{{item.title}}</text>
					<text class="t2" v-for="(item2, index2) in item.formcontent" :key="index2" v-if="!item2.hidden && item2.val12 && item2.key!='upload' && item2.key!='upload_file' && item2.key!='upload_video' && item2.key!='upload_pics'" :class="item2.key=='separate'?'title':''">
						{{item2.val1}}：{{item['form'+index2]}}
						<view class="t2"  v-if="item.show_distance && item2.key=='map'" @tap="openLocation" :data-latitude="item.adr_lat" :data-longitude="item.adr_lon">
							<image :src="pre_url+'/static/img/b_addr.png'" style="width:26rpx;height:26rpx;margin-right:10rpx"/>
							距离您{{item.distance}}
						</view>  
					</text>
					<text class="t2" v-if="item.custom_number">{{item.custom_number_text}}：{{item.custom_number}}</text>
					<text class="t2">提交时间：{{item.createtime}}</text>
				</view>
				<view class="f2">
					<text class="t1" v-if="item.status==0 && (!item.payorderid||item.paystatus==1)" style="color:#88e">待确认</text>
					<text class="t1" v-if="item.status==0 && item.payorderid && item.paystatus==0" style="color:red">待支付</text>
					<text class="t1" v-if="item.status==1" style="color:green">已确认</text>
					<text class="t1" v-if="item.status==2" style="color:red">已驳回</text>
				</view>
			</view>
		</view>
	</block>
	<nodata v-if="nodata"></nodata>
	<nomore v-if="nomore"></nomore>
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

      st: 'all',
      datalist: [],
      pagenum: 1,
      nomore: false,
      nodata: false,
			keyword:'',
			latitude:'',
			longitude:'',
			pre_url: app.globalData.pre_url,
    };
  },
  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.st = this.opt.st || 'all';
		var that = this;
		
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
	onNavigationBarSearchInputConfirmed:function(e){
		this.searchConfirm({detail:{value:e.text}});
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
      var pagenum = that.pagenum;
      var st = that.st;
			this.nodata = false;
			this.nomore = false;
			this.loading = true;
		
      app.post('ApiMy/formlog', {keyword:that.keyword,st: st,pagenum: pagenum,latitude:that.latitude,longitude:that.longitude}, function (res) {
				that.loading = false;
        var data = res.data;
        if (pagenum == 1) {
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
				if(that.datalist[0]['show_distance']){
					app.getLocation(function (res) {
						var latitude = res.latitude;
						var longitude = res.longitude;
						that.longitude = longitude;
						that.latitude = latitude;
						that.getDataList();
					},function () {
						that.getDataList();
					});
				}
      });
    },
	getDataList: function (loadmore) {
		if(!loadmore){
			this.pagenum = 1;
			this.datalist = [];
		}
		var that = this;
		var pagenum = that.pagenum;
		var st = that.st;
		this.nodata = false;
		this.nomore = false;
		this.loading = true;
				
		app.post('ApiMy/formlog', {keyword:that.keyword,st: st,pagenum: pagenum,latitude:that.latitude,longitude:that.longitude}, function (res) {
			that.loading = false;
			var data = res.data;
			if (pagenum == 1) {
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
	searchConfirm:function(e){
		this.keyword = e.detail.value;
		this.getdata(false);
	},
	openLocation:function(e){
		var latitude = parseFloat(e.currentTarget.dataset.latitude)
		var longitude = parseFloat(e.currentTarget.dataset.longitude)
		var address = e.currentTarget.dataset.address
		uni.openLocation({
			 latitude:latitude,
			 longitude:longitude,
			 name:address,
			 scale: 13
		})		
	}
  }
}
</script>
<style>
.topsearch{width:94%;margin:10rpx 3%;}
.topsearch .f1{height:60rpx;border-radius:30rpx;border:0;background-color:#fff;flex:1}
.topsearch .f1 .img{width:24rpx;height:24rpx;margin-left:10px}
.topsearch .f1 input{height:100%;flex:1;padding:0 20rpx;font-size:28rpx;color:#333;}
	.content{ width:100%;margin:0;}
	.content .item{ width:94%;margin:20rpx 3%;;background:#fff;border-radius:16rpx;padding:30rpx 30rpx;display:flex;align-items:center;}
	.content .item:last-child{border:0}
	.content .item .f1{width:80%;display:flex;flex-direction:column}
	.content .item .f1 .t1{color:#000000;font-size:30rpx;word-break:break-all;overflow:hidden;text-overflow:ellipsis;}
	.content .item .f1 .t2{color:#666666;margin-top:10rpx}
	.content .item .f1 .t3{color:#666666}
	.content .item .f2{width:20%;font-size:32rpx;text-align:right}
	.content .item .f2 .t1{color:#03bc01}
	.content .item .f2 .t2{color:#000000}
	.content .item .f3{ flex:1;font-size:30rpx;text-align:right}
	.content .item .f3 .t1{color:#03bc01}
	.content .item .f3 .t2{color:#000000}
	.f4{width:28rpx;height:28rpx;margin-right:8rpx}
</style>