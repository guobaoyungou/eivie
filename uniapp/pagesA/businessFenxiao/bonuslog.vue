<template>
<view class="container">
	<block v-if="isload">
		<view class="search-view flex-xy-center">
			<view class="input-view flex-aw">
				<view class="picker-class flex-x-center">
					<image :src="pre_url+'/static/img/timeicon.png'"></image>
					<picker mode="date" :value="start_time1" @change="bindStartTime1Change">
						<view class="picker">{{start_time1}}</view>
					</picker>
					<image :src="pre_url+'/static/img/jiantou.png'"></image>
				</view>
				<view>--</view>
				<view class="picker-class flex-x-center">
					<image :src="pre_url+'/static/img/timeicon.png'"></image>
					<picker mode="date" :value="start_time2" @change="bindStartTime2Change">
						<view class="picker">{{start_time2}}</view>
					</picker>
					<image :src="pre_url+'/static/img/jiantou.png'"></image>
				</view>
			</view>
		</view>
		<block v-if="datalist && datalist.length>0">
		<view class="content content-view flex-col">
			<block>
			<view class="item">
				<view class="f2">
					<view class="t1">
						<view class="item2">
							<text class="x1">收入合计：{{total}}</text>
						</view>
					</view>
				</view>
			</view>
			<view v-for="(item, index) in datalist" :key="index" class="item">
				<view class="f1"><text>{{item.name}}</text></view>
				<view class="f2">
					<view class="t1">
						<view class="item2">
							<text class="x1">{{dateFormat(item.createtime)}}</text>
						</view>
					</view>
					<view class="t2">
						<text class="x1">+{{item.bonus}}</text>
					</view>
				</view>
			</view>
			</block>
		</view>
		</block>
		<view style="width:100%;height:20rpx"></view>
	</block>
	<nodata v-if="nodata"></nodata>
	<nomore v-if="nomore"></nomore>
	<loading v-if="loading"></loading>
	<dp-tabbar :opt="opt"></dp-tabbar>
	<popmsg ref="popmsg"></popmsg>
</view>
</template>

<script>
var app = getApp();

export default {
  data() {
    return {
		pre_url:app.globalData.pre_url,
		start_time1:'选择日期',
		start_time2:'选择日期',
		opt:{},
		loading:false,
		isload: false,
		menuindex:-1,
		st: '1',
		count:0,
		commissionyj: 0,
		pagenum: 1,
		datalist: [],
		nodata: false,
		nomore: false,
		bid:0,
		total:0
    };
  },

  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.bid = this.opt.bid || 0;
		this.getdata();
  },
	onPullDownRefresh: function () {
		this.pagenum = 1;
		this.datalist = [];
		this.getdata();
	},
  onReachBottom: function () {
    if (!this.nodata && !this.nomore ) {
      this.pagenum = this.pagenum + 1;
      this.getdata();
    }
  },
  methods: {
		getdata: function () {
			var that = this;
			var pagenum = that.pagenum;
			var st = that.st;
			that.loading = true;
			that.nodata = false;
			that.nomore = false;
			app.get('ApiBusinessFenxiao/bonuslog',{pagenum: pagenum,bid:that.bid,s_time:that.start_time1,e_time:that.start_time2},function(res){
				that.loading = false;
				var data = res.datalist;
				if (pagenum == 1) {
					that.datalist = data;
					if (data.length == 0) {
						that.nodata = true;
					}
					that.total = res.total;
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
    changetab: function (st) {
      this.pagenum = 1;
      this.st = st;
      this.datalist = [];
      uni.pageScrollTo({
        scrollTop: 0,
        duration: 0
      });
      this.getdata();
    },
	bindStartTime1Change:function(e){
		this.start_time1 = e.target.value
		this.pagenum = 1;
		this.datalist = [];
		uni.pageScrollTo({
			scrollTop: 0,
			duration: 0
		});
		this.getdata();
	},
	bindStartTime2Change:function(e){
		this.start_time2 = e.target.value
		this.pagenum = 1;
		this.datalist = [];
		uni.pageScrollTo({
			scrollTop: 0,
			duration: 0
		});
		this.getdata();
	},
  }
};
</script>
<style>
	.search-view{background: #fff;width: 100%;height: 140rpx;;position: fixed;top: 0;}
	.input-view{width: 90%;background: #F5F7F9;border-radius: 16rpx;height: 88rpx;align-items: center;}
	.input-view image{width: 35rpx;height: 35rpx;margin: 0 10rpx;}
	.input-view .picker-class{width: 43%;height: 100%;align-items: center;}
	.input-view .picker-class .picker{font-size: 24rpx;color: rgba(130, 130, 167, 0.8);white-space: nowrap;width: 150rpx;text-align: center;} 
	.content-view{width: 100%;height: auto;margin-top: 160rpx;}
	.options-view{background: #fff;width: 100%;margin-bottom:15rpx;padding: 23rpx 40rpx;align-items: center;display: flex;align-items: center;justify-content: flex-start;}
	
.topfix{width: 100%;position:relative;position:fixed;background: #f9f9f9;top:var(--window-top);z-index:11;}
.toplabel{width: 100%;background: #f9f9f9;padding: 20rpx 20rpx;border-bottom: 1px #e3e3e3 solid;display:flex;}
.toplabel .t1{color: #666;font-size:30rpx;flex:1}
.toplabel .t2{color: #666;font-size:30rpx;text-align:right}

.content{ width:100%;}
.content .item{width:94%;margin-left:3%;border-radius:10rpx;background: #fff;margin-bottom:16rpx;}
.content .item .f1{width:100%;padding: 16rpx 20rpx;color: #666;border-bottom: 1px #f5f5f5 solid;}
.content .item .f2{display:flex;padding:20rpx;align-items:center}
.content .item .f2 .t1{display:flex;flex-direction:column;flex:auto}
.content .item .f2 .t1 .item2{display:flex;flex-direction:column;flex:auto;margin:10rpx 0;padding:10rpx 0;border-bottom:1px dotted #f5f5f5}
.content .item .f2 .t1 .x2{color:#999;font-size:24rpx;height:40rpx;line-height:40rpx}
.content .item .f2 .t1 .x3{display:flex;align-items:center}
.content .item .f2 .t1 .x3 image{width:40rpx;height:40rpx;border-radius:50%;margin-right:4px}
.content .item .f2 .t2{ width:360rpx;text-align:right;display:flex;flex-direction:column;}
.content .item .f2 .t2 .x1{color: #000;height:44rpx;line-height: 44rpx;overflow: hidden;font-size:36rpx;}
.content .item .f2 .t2 .x2{height:44rpx;line-height: 44rpx;overflow: hidden;}

.dfk{color: #ff9900;}
.yfk{color: red;}
.ywc{color: #ff6600;}
.ygb{color: #aaaaaa;}

</style>