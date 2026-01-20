<template>
<view class="container">
	<block v-if="isload">
		<view class="topsearch flex-y-center">
			<view class="f1 flex-y-center">
				<image class="img" :src="pre_url+'/static/img/search_ico.png'"></image>
				<input :value="keyword" placeholder="输入关键字搜索" placeholder-style="font-size:24rpx;color:#C2C2C2" @confirm="searchConfirm"></input>
			</view>
			<uni-datetime-picker v-model="datetimerange" type="datetimerange" @change="timeChange" rangeSeparator="~" />
			
		</view>
		<view class="total">合计补货：<text style="color: red;margin-right: 10rpx;">{{total_stock}}</text>件</view>
		<view class="content" id="datalist">
			<block v-for="(item, index) in datalist" :key="index"> 
			<view class="item" >
				<view class="f1">
					<view class="t1" >柜子名称：<text style="color: #1e88e5;">{{item.device_name}}</text></view>
					<view class="t1">柜子编号：<text  style="color: #333;">	{{item.device_no}}</text></view>
					<view class="t1">补货时间：<text  style="color: #333;">	{{item.createtime}}</text></view>
					<view class="t1">小计补货：<text style="color: red;margin-right: 10rpx;">{{item.total_stock}}</text> 件</view>
					<view class="t1"><text style="color: #1e88e5;">补货商品明细：</text></view>
					<block v-for="(item2, index) in item.goodsdata">
						<view class="t2 flex flex-bt">
							<view >{{item2.proname}}[{{item2.ggname}}]</view>
							<view style="color: #333;">x{{item2.add_stock}}</view>
						</view>	
					</block>
				</view>
			</view>
			</block>
			<nodata v-if="nodata"></nodata>
		</view>
		<nomore v-if="nomore"></nomore>
	</block>
	<loading v-if="loading"></loading>
	<dp-tabbar :opt="opt"></dp-tabbar>
	<popmsg ref="popmsg"></popmsg>
</view>
</template>

<script>
var app = getApp();
import uniDatetimePicker from './uni-datetime-picker/uni-datetime-picker.vue'

export default {
	components: {
		uniDatetimePicker
	},
  data() {
    return {
		opt:{},
		loading:false,
		isload: false,
		menuindex:-1,
		pre_url:app.globalData.pre_url,
		st: 0,
		datalist: [],
		pagenum: 1,
		mynum: 0,
		nodata: false,
		nomore: false,
		datetimerange: [],
		keyword:'',
		total_stock:0
    };
  },

  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		if(this.opt){
			this.device_id = this.opt.id
		}
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
		var ctime =that.datetimerange
		var keyword=that.keyword;
		app.post('ApiAdminPickupDevice/getaddstocklog', {pagenum: pagenum,keyword: keyword,ctime: ctime}, function (res) {
				that.loading = false;
				that.total_stock = res.total_stock;
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
	timeChange:function(){
		this.getdata(false);
	},
  }
};
</script>
<style>
.topsearch{width:94%;margin:10rpx 3%;}
.topsearch .f1{height:60rpx;border-radius:30rpx;border:0;background-color:#fff;width: 45%;}
.topsearch .f1 .img{width:24rpx;height:24rpx;margin-left:10px}
.topsearch .f1 input{height:100%;flex:1;padding:0 20rpx;font-size:28rpx;color:#333;}
.topsearch .timesearch{width: 40%;}
.total{width:94%;padding:20rpx 40rpx;background:#fff;margin:25rpx 3%;}
.content{ width:94%;margin:0 3%;}
.content .item{width:100%;margin:25rpx 0;background:#fff;border-radius:5px;padding:20rpx 20rpx;display:flex;align-items:center;justify-content: space-between;position: relative;align-items: normal;}
.content .item .f1{flex: 1;padding-left: 20rpx;font-size: 28rpx;line-height: 40rpx;}
.content .item .f1 .t1{line-height: 50rpx; color: #9e9e9e;padding: 5rpx 0;}

.content .item .f1 .t2{line-height: 40rpx; color: #9e9e9e;padding: 8rpx 0 8rpx 20rpx;}
.uni-date-x{border-radius: 44rpx !important;height: 60rpx !important}
.uni-date-x--border {border: none !important;}
</style>