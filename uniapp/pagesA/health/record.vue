<template>
<view class="container">
	<block v-if="isload">
		<view class="top">
			<view class="top-picker">
				<picker mode="selector" :range="healthlist" range-key="name" @change="pickerChange" data-field="health">
					<view class="picker">
						<view class="picker-txt">{{health_index>-1?healthlist[health_index].name:'全部量表'}}</view>
						<image class="down" :src="pre_url+'/static/img/location/down-black.png'"></image>
					</view>
				</picker>
				<picker mode="selector" :range="bidlist" range-key="name" @change="pickerChange" data-field="bid">
					<view class="picker">
						<view class="picker-txt">{{bid_index>-1?bidlist[bid_index].name:'全部门店'}}</view>
						<image class="down" :src="pre_url+'/static/img/location/down-black.png'"></image>
					</view>
				</picker>
				<!-- 日期选择 -->
				<block>
					<view v-if="startDate" class="picker pickerD" @click="toCheckDate">
						<view class="picker-date" >
								<view class="picker-row" v-if="startDate">{{startDate}}</view>
								<view class="picker-row">{{endDate?endDate:'至今'}}</view>
						</view>
						<view class="picker-clear" @tap.stop="clearDate">清除</view>
					</view>
					<view v-else class="picker pickerD" @click="toCheckDate">
							<view>不限日期</view>
							<image class="down" :src="pre_url+'/static/img/location/down-black.png'"></image>
					</view>
				</block>
			</view>
			<view class="top-search">
				<image :src="pre_url + '/static/img/search_ico.png'" class="search-icon">
				<input class="input" type="text" @confirm="searchConfirm" v-model="keyword" placeholder="输入姓名|手机号检索" placeholder-style="font-size:26rpx;color:#999">
			</view>
		</view>
		<view class="main">
			<block v-for="(item, index) in datalist" :key="index">
			<view class="item" @tap.stop="goto" :data-url="'result?id=' + item.id">
				<view class="header">
					<view class="flex-sb">
						<view class="col">姓名：{{item.name}}</view>
						<view class="col">电话：{{item.tel}}</view>
					</view>
					<view class="flex-sb">
						<view class="col">年龄：{{item.age}}岁</view>
						<view class="col">性别：{{item.sex==2?'女':'男'}}</view>
					</view>
					<view class="flex-sb">
						家庭地址：{{item.address}}
					</view>
				</view>
				<view class="info">
					<view>
						<view>评测量表：{{item.ha_name}}</view>
						<view v-if="item.score>0">评测结果：{{item.score}}分 <text class="scoretag">{{item.score_tag}}</text></view>
						<view>评测时间：{{item.createtime}}</view>
						<view>选择门店：{{item.bname}}</view>
					</view>
					<view class="btn" :style="{background:t('color1'),color:'#FFFFFF'}">查看详情</view>
				</view>
			</view>
			</block>
		</view>
		<nomore v-if="nomore"></nomore>
		<nodata v-if="nodata"></nodata>
	</block>
	<loading v-if="loading"></loading>
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
			pre_url:app.globalData.pre_url,
      isload: false,
      datalist: [],
      pagenum: 1,
      nomore: false,
      nodata: false,
			keyword:'',
			healthlist:[],
			health_index:-1,
			bidlist:[],
			bid_index:-1,
			bid:0,
			startDate:'',
			endDate:''
    };
  },
  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.getdata();
  },
	onShow(){
		var that  = this;
		uni.$on('selectedDate',function(data){
			that.startDate = data.startStr.dateStr;
			that.endDate = data.endStr.dateStr;
			uni.pageScrollTo({
			  scrollTop: 0,
			  duration: 0
			});
			that.getdata();
		})
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
			that.nodata = false;
			that.nomore = false;
			that.loading = true;
			var ha_id = that.health_index>-1?that.healthlist[that.health_index].id:0;
			var bid = that.bid_index>-1?that.bidlist[that.bid_index].id:0;
      app.post('ApiHealth/record', {pagenum: pagenum,keyword:that.keyword,ha_id:ha_id,bid:bid,startdate:that.startDate,enddate:that.endDate}, function (res) {
				that.loading = false;
        var data = res.datalist;
        if (pagenum == 1) {
					that.datalist = data;
					that.bidlist = res.bidlist;
					that.healthlist = res.healthlist
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
		pickerChange:function(e){
			var field = e.currentTarget.dataset.field
			this[field+'_index'] = e.detail.value;
			this.getdata(false)
		},
		searchConfirm:function(){
			if(this.keyword!=''){
				this.getdata(false)
			}
		},
		toCheckDate(){
			app.goto('../../pagesExt/checkdate/checkDate?ys=2&type=1&t_mode=5');
		},
		clearDate(){
			var that  = this;
			that.startDate = '';
			that.endDate = '';
			uni.pageScrollTo({
				scrollTop: 0,
				duration: 0
			});
			this.getdata(false);
		},
  }
};
</script>
<style>
.container{ width:100%;}
.flex-sb{display: flex;justify-content: space-between;align-items: center;}
.top{position: fixed;top: 0;width: 100%;background: #FFFFFF;padding:20rpx 26rpx;z-index: 9999;}
.top-picker{display: flex;align-items: center;justify-content: space-between;}
.top-picker picker{width: 30%;}
.top-picker .pickerD{width: 35%;}
.top .picker{display: flex;justify-content: space-between;align-items: center;border:1rpx solid #e0e0e0;border-radius: 6rpx;padding:0 12rpx;height: 64rpx;line-height: 70rpx;}
.top .picker .picker-txt{text-overflow: ellipsis;white-space: nowrap;overflow: hidden;height: 70rpx;}
.top .down{width: 20rpx;height: 20rpx;margin-left: 10rpx;flex-shrink: 0;}
.top-search{display: flex;align-items: center; background: #F6F6F6;margin-top: 20rpx;padding: 12rpx 20rpx;border-radius: 10rpx;}
.search-icon{width: 30rpx;height: 30rpx;margin-right: 10rpx;}
.main{margin-top: 200rpx;padding: 26rpx;font-size: 26rpx;}
.item{background: #FFFFFF;margin-bottom: 26rpx;padding:30rpx;line-height: 46rpx;color: #666666;border-radius: 12rpx;}
.item .header{border-bottom: 1rpx solid #f0f0f0;padding-bottom: 16rpx;}
.item .info{margin-top: 16rpx;display: flex;justify-content: space-between;align-items: center;}
.item .scoretag{padding-left: 20rpx;}
.item .col{width: 47%;overflow: hidden;text-overflow: ellipsis;}
.btn{border: 1rpx solid #F0F0F0; border-radius:8rpx;text-align: center;flex-shrink: 0;padding: 6rpx 10rpx;font-size: 24rpx;}
.picker-date{font-size: 20rpx;display: flex;flex-direction: column;align-items: center;max-height: 100%;}
.picker-row{height: 30rpx;display: inline-block;line-height: 30rpx;}
.picker-clear{font-size: 20rpx;flex-shrink: 0;padding: 0 8rpx;height: 40rpx;line-height: 38rpx;background: #ECECEC;border-radius: 20rpx;margin-left: 4rpx;color: #999;}
</style>