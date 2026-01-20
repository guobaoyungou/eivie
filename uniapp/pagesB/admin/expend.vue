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
		
		<view class="content">
			<view class="item" v-for="(item,index1) in datalist" :key="index1" @tap="goto" :data-url="'expendEdit?id=' + item.id">
				<view class="left">
					<view class="f2">
						<text class="t2">分类：{{item.cname}}</text>
						<text class="t2">时间：{{item.createtime}}</text>
						<text class="t2">备注：{{item.remark}}</text>
						<view class="t2">管理员：{{item.adminname}}</view>
					</view>
				</view>
				<view class="right">
					<view>
						<view class="money" :style="{color:t('color1')}">￥{{item.money}}</view>
					</view>
					<image class="more" :src="pre_url+'/static/img/arrowright.png'" ></image>
				</view>
			</view>
		</view>
		<nodata v-if="nodata"></nodata>
		
		<nomore v-if="nomore"></nomore>
	</block>
	<loading v-if="loading"></loading>
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
	onShow() {
		this.getdata();
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
		app.post('ApiAdminExpend/index', {pagenum: pagenum,keyword: keyword,ctime: ctime}, function (res) {
			that.loading = false;
			if (pagenum == 1) {
				that.datalist = res.datalist;
				if (res.datalist.length == 0) {
					that.nodata = true;
				}
				that.loaded();
			}else{
			  if (res.datalist.length == 0) {
					that.nomore = true;
			  } else {
					var datalist = that.datalist;
					var newdata = datalist.concat(res.datalist);
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

.uni-date-x{border-radius: 44rpx !important;height: 60rpx !important}
.uni-date-x--border {border: none !important;}

.content{width: 94%;margin:0 3%;background: #fff;border-radius:16rpx;margin-bottom: 20rpx;}
.content .label{display:flex;width: 100%;padding:24rpx 16rpx;color: #333;}
.content .label .t1{flex:1;font-weight: bold;}
.content .label .t2{ width:300rpx;text-align:right}

.content .item{width:100%;padding:20rpx 20rpx;border-top: 1px #f5f5f5 solid;display:flex;align-items:center;justify-content: space-between;}
.content .left{display: flex;align-items: center;flex: 1;}

.content .item .nickname{display:flex;}
.content .item .headimg{width:80rpx;height:80rpx;border-radius:50%;}
.content .right{flex-shrink: 0;display: flex;justify-content: flex-end;align-items: center;}
.content .item .right .money{font-size: 30rpx;font-weight: bold;}
.content .item .right .refund-money{font-size: 24rpx;color: #ff9d05;}
.content .item .right .more{width: 30rpx;height: 30rpx;}
.content .item .f1 .t2{color:#666666;text-align:center;width:140rpx;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:1;overflow:hidden;}
.content .item .f2{ flex:1;width:200rpx;font-size:30rpx;display:flex;flex-direction:column}
.content .item .f2 .t1{color:#03bc01;height:40rpx;line-height:40rpx;font-size:36rpx}
.content .item .f2 .t2{color:#999;height:40rpx;line-height:40rpx;font-size:24rpx}
.content .item .f2 .t3{color:#aaa;height:40rpx;line-height:40rpx;font-size:24rpx}
</style>