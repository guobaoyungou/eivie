<template>
<view class="container">
	<block v-if="isload">
			<view class="banner" :style="{background:'linear-gradient(180deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0) 100%)'}">
			</view>
			<view class="user">
				<image :src="userinfo.headimg" background-size="cover"/>
				<view class="info">
					 <text class="nickname">{{userinfo.nickname}}</text>
					 <view class="f2"><text>点位：{{other_data.my_point_count}}个</text> <text>已完成点位：{{other_data.finish_count}}个</text></view>
				</view>
			</view>
			<view class="contentdata">
				<view class="data">
					<view class="data_module flex">
						<view class="flex1" @tap="goto" data-url="/pagesB/paimingfenhong/withdraw">
							<view class="data_value">{{userinfo.paiming_fenhong_money}}</view>
							<view class="data_lable">分红转出</view>
						</view>
						<view class="flex1">
							<view class="data_value">{{other_data.my_totalprice}}</view>
							<view class="data_lable">消费总额</view>
						</view>
						<view class="border"></view>
						<view class="flex1">
							<view class="data_value">{{other_data.yesterday_totalprice}}</view>
							<view class="data_lable">昨日营业额</view>
						</view>
						<view class="flex1">
							<view class="data_value">{{other_data.yesterday_money}}</view>
							<view class="data_lable">昨日分红</view>
						</view>
					</view>
				</view>
				<view class="data1">
					<view class="title1">我的今日排名:
						<text v-if="other_data.my_point_order">{{other_data.my_point_order}}位</text>
						<text class="t2" v-else>暂无</text>
					</view>
					<view class="title2">当前排位总计{{other_data.record_count}}人</view>
				</view>			
				
				<view class="data">
					<view class="data_module ">
						<view class="search">
								<picker class="picker" mode="date" :value="date" :start="startDate" :end="endDate" @change="bindDateChange">
									<view class="uni-input">{{date}}</view>
								</picker>
							<button @tap="dataSearch" >查询</button>
						</view>
						<view class="title">
							<view class="border"></view>
							<view>分红记录</view>
						</view>
						<view class="item" v-for="(item, index) in datalist" :key="index">
							<view class="f1">
								<text class="t1">{{item.remark}}</text>
								<text class="t1">{{item.createtime}}</text>
							</view>
							<view class="f2">
								<text class="t1" v-if="item.money > 0">{{item.money}}</text>	
								<text class="t1" v-if="item.money < 0">-{{item.money}}</text>	
							</view>
						</view>
					
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
		const currentDate = this.getDate({
				format: true
		})
    return {
			opt:{},
			loading:false,
			isload: false,
			menuindex:-1,
			userinfo: {},
			money: 0,
			sysset: false,
			other_data: {},
			datalist: [],
			date: currentDate
    };
  },

  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		var that = this;
		this.getdata();
		this.getdatalist();
  },
	onPullDownRefresh: function () {
		this.getdata();
		this.getdatalist();
	},
  onReachBottom: function () {
	if (!this.nodata && !this.nomore) {
	  this.pagenum = this.pagenum + 1;
	  this.getdatalist(true);
	}
  },
 computed: {
			startDate() {
					return this.getDate('start');
			},
			endDate() {
					return this.getDate('end');
			}
	},
  methods: {
		getdata: function () {
			var that = this;
			that.loading = false;
			that.loaded();
			console.log(that.userinfo)
			//  app.get('ApiPaimingFenhong/index', {}, function (res) {
			// 		that.loading = false;
			// 		uni.setNavigationBarTitle({
			// 			title: that.t('排名分红') + '转出'
			// 		});
			// 		var sysset = res.sysset;
			// 		that.sysset = sysset;
			// 		that.userinfo = res.userinfo;
			// 		that.loaded();
			// });
			  app.get('ApiPaimingFenhong/index', {}, function (res) {
				  that.loading = false;
				  uni.setNavigationBarTitle({
				  	title: that.t('排名分红')
				  });
				  that.userinfo = res.userinfo;
				  that.other_data = res.other_data;
				  that.loaded();
		  });
		},
		getdatalist: function (loadmore) {
					if(!loadmore){
						this.pagenum = 1;
						this.datalist = [];
					}
		      var that = this;
		      var date = that.date;
		      var pagenum = that.pagenum;
			  that.loading = true;
			  that.nodata = false;
		      that.nomore = false;
		      app.post('ApiPaimingFenhong/moneyLog', {ctime: date,pagenum: pagenum}, function (res) {
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
			 dataSearch: function () {
				 this.getdatalist();
			 },
		 bindDateChange: function(e) {
					this.date = e.detail.value
			},
			getDate(type) {
					const date = new Date();
					let year = date.getFullYear();
					let month = date.getMonth() + 1;
					let day = date.getDate();

					if (type === 'start') {
							year = year - 60;
					} else if (type === 'end') {
							year = year + 2;
					}
					month = month > 9 ? month : '0' + month;
					day = day > 9 ? day : '0' + day;
					return `${year}-${month}-${day}`;
			}

  }
};
</script>
<style>
.container{display:flex;flex-direction:column;padding-bottom: 40rpx;}
.banner{position: absolute;width: 100%;height: 900rpx;}
.user{ display:flex;width:100%;padding:40rpx 45rpx 0 45rpx;color:#fff;position:relative}
.user image{ width:100rpx;height:100rpx;border-radius:50%;margin-right:20rpx}
.user .info{ display: flex; flex-direction: column; width: 80%;}
.user .info .nickname{font-size:32rpx;font-weight:bold; margin-top: 10rpx;}
.info .f2{ display: flex; justify-content: space-between; width: 100%; margin-top: 10rpx;}

.contentdata{display:flex;flex-direction:column;width:100%;padding:0 30rpx;position:relative;margin:40rpx 0  20rpx 0}
.data{background:#fff;padding:30rpx;margin-top:30rpx;border-radius:16rpx}
.data_module{width: 100%;}
.data_module .data_module_view{min-width: 50%;margin-bottom: 20rpx;}
.data_lable{font-size: 26;color: #999; text-align: center;}
.data_value{font-size: 34rpx;font-weight: bold;color: #333;margin-top: 10rpx; text-align: center;}
.data_module .border{ border:1rpx solid #f5f5f5; height: 80rpx;}

.data_module .search{ display: flex; align-items: center; justify-content: space-between;}
.data_module .picker{ border: 1rpx solid #f5f5f5; width: 80%; height: 60rpx; border-radius: 10rpx; line-height: 60rpx; padding-left: 20rpx;}


.data1{ margin-top:30rpx; padding:20rpx}
.data1 .title1{ font-size: 30rpx; color: #fff; font-weight: bold;}
.data1 .title1 text{word-wrap:break-word}
.data1 .title2{ font-size: 30rpx; color: #fff; font-weight: bold; margin-top: 20rpx;}
.data_module .title { color: #333; font-weight: bold; display: flex; align-items: center; font-size: 28rpx; margin: 20rpx 0; }
.data_module .title .border{ background: #FD4A46; width: 10rpx; height:20rpx;  margin-right: 20rpx; }

.data_module .item{width:100%;margin:20rpx 0;background:#fff;border-radius:5px;padding:0rpx 20rpx;display:flex;align-items:center}
.data_module .item .f1{display:flex;flex-direction:column}
.data_module .item .f1 .t1{color:#000000;font-size:26rpx;word-break:break-all;overflow:hidden;text-overflow:ellipsis;}
.data_module .item .f2{ flex:1;text-align:right; font-size: 32rpx;}
.data_module .item .f2 .t1{color:#03bc01; }
</style>