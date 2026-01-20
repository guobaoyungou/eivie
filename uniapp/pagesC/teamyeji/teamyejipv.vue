<template>
<view class="container">
	<block v-if="isload">
		<view class="content">
			<view class="topsearch flex-y-center sx" >
				<text class="t1">日期筛选：</text>
				<view class="body_data" style="min-width: 200rpx;" @click="toCheckDate"> {{startDate?startDate:'点击选择日期'}}{{endDate?' 至 '+endDate:''}}
					<!-- <img class="body_detail" :src="pre_url+'/static/img/week/week_detail.png'" /> -->
				</view>
				<view class="t_date">
					<view v-if="startDate" class="x1" @tap="clearDate">清除</view>
				</view>
			</view>
			<view class="yejilabel">
				<view class="t1 flex-bt" style="padding:10rpx;" >
					<view>总群服务业绩</view>
					<view>{{userinfo.teamyeji_pv}}</view>
				</view>
				<view class="t1 flex-bt" style="padding:10rpx;">
					<view>个群服务业绩</view>
					<view>{{userinfo.my_teamyeji_pv}}</view>
				 </view>
			</view>
		</view>
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
		opt:{},
		loading:false,
		isload: false,
		menuindex:-1,
		datalist: [],
		userinfo:{},
		keyword:'',
		nodata: false,
		nomore: false,
		dialogShow: false,
		startDate: '',
		endDate: '',
		pre_url: app.globalData.pre_url
    };
  },

  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.getdata();
  },
	onPullDownRefresh: function () {
		this.getdata();
	},
  onReachBottom: function () {
    if (!this.nodata && !this.nomore) {
      this.pagenum = this.pagenum + 1
      this.getdata(true);
    }
  },
  onShow(){
  	var that  = this;
  	uni.$on('selectedDate',function(data,otherParam){
      if(otherParam && otherParam == 1){
        that.sdate =  data.startStr.dateStr;
        that.edate = data.endStr.dateStr;
      }else{
        that.startDate = data.startStr.dateStr;
        that.endDate = data.endStr.dateStr;
      }
			uni.pageScrollTo({
				scrollTop: 0,
				duration: 0
			});
			that.getdata();
		})
  },
  methods: {
		getdata: function (loadmore) {	
			var that = this;
			var st = that.st;
			that.loading = true;
			that.nodata = false;
			that.nomore = false;
			var date_start = that.startDate;
			var date_end = that.endDate;
			app.get('ApiAgent/getTeamyejiPv', {date_start:date_start,date_end:date_end}, function (res) {
				console.log(res.data);
				that.loading = false;
				that.userinfo = res.data;
				that.isload = true;	
			});
		},
	
		toCheckDate(){
			// app.goto('../../pagesExt/checkdate/checkDate?ys=2&type=1&t_mode=5');
			app.goto('../../pagesExt/checkdate/checkDate?ys=2&type=1&t_mode=5&dayin=-400');
		},
		clearDate(){
			var that  = this;
			that.startDate = '';
			that.endDate = '';
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
.content{width:94%;margin:0 3%;border-radius:16rpx;background: #fff;margin-top: 20rpx;}
.content .label{display:flex;width: 100%;padding: 16rpx;color: #333;}
.content .label .t1{flex:1}
.content .label .t2{ width:300rpx;text-align:right}

.content .item{width: 100%;padding:32rpx 20rpx;border-top: 1px #eaeaea solid;min-height: 112rpx;display:flex;align-items:center;}
.content .item image{width: 90rpx;height: 90rpx;border-radius:4px}
.content .item .f1{display:flex;flex:1;align-items:center;}
.content .item .f1 .t2{display:flex;flex-direction:column;padding-left:20rpx}
.content .item .f1 .t2 .x1{color: #333;font-size:26rpx;}
.content .item .f1 .t2 .x2{color: #999;font-size:24rpx;}
.content .item .f1 .t2 .x3{font-size:24rpx;}

.content .item .f2{display:flex;flex-direction:column;width:200rpx;border-left:1px solid #eee;text-align: right;}
.content .item .f2 .t1{ font-size: 40rpx;color: #666;height: 40rpx;line-height: 40rpx;}
.content .item .f2 .t2{ font-size: 28rpx;color: #999;height: 50rpx;line-height: 50rpx;}
.content .item .f2 .t3{ display:flex;justify-content:flex-end;margin-top:10rpx; flex-wrap: wrap;}
.content .item .f2 .t3 .x1{padding:8rpx;border:1px solid #ccc;border-radius:6rpx;font-size:22rpx;color:#666;margin-top: 10rpx;margin-left: 6rpx;}
.content .item .f2 .t4{ display:flex;margin-top:10rpx;margin-left: 10rpx;color: #666; flex-wrap: wrap;font-size:18rpx;text-align: left}

.t_date .x1{height:40rpx;line-height:40rpx;padding:0 8rpx;border:1px solid #ccc;border-radius:6rpx;font-size:22rpx;color:#666;margin-left: 10rpx;}
.content .yejilabel{/* display:flex; */width: 100%;padding: 16rpx;color: #333;justify-content: space-between;flex-wrap: wrap;line-height:60rpx;border-top:1rpx solid #f5f5f5}
/* .content .yejilabel .t1{flex-shrink: 0;width: 50%;} */
.content .sx{padding:20rpx 16rpx; margin:0}

</style>