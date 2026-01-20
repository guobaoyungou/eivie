<template>
<view class="container">
	<block v-if="isload">
		<view class="ind_business">
			<view class="ind_buslist" id="datalist">
				<block v-for="(item, index) in datalist" :key="index" >
				<view @tap="selectBusiness" :data-id="item.id">
					<view class="ind_busbox ">
						<view class="flex1 flex-row flex-y-center">
							<view class="ind_buspic flex0"><image :src="item.logo"></image></view>
							<view style="flex: 1;">
								<view class="bus_title">{{item.name}}</view>
								<view class="bus_address">地址：<text class="x1">{{item.province}}{{item.city}}{{item.district}}{{item.address}}</text></view>
							</view>
							<view  class="status flex-y-center">
								<view v-if="item.is_open==1">营业中</view>	
								<view v-else>休息中</view>	
								<image :src="pre_url+'/static/img/arrowright.png'" class="image"/>
							</view>	
						</view>	
						
						<view class="ind_bottom" >
							<view class="v1"><text  class="t1">申请时间：</text>{{dateFormat(item.createtime)}}</view>
						</view>	
					</view>
					
				</view>
				</block>
				<nomore v-if="nomore"></nomore>
				<nodata v-if="nodata"></nodata>
			</view>
		</view>
	</block>
	<loading v-if="loading"></loading>
	<dp-tabbar :opt="opt"></dp-tabbar>
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
		pre_url:app.globalData.pre_url,
		datalist: [],
		pagenum: 1,
		nomore: false,
		nodata: false,
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
		that.loading = true;
		that.nodata = false;
		that.nomore = false;
		var status = 1;//只查询审核通过的
		app.post('ApiAdminBusiness/getUserBusinessList', {pagenum: pagenum,status:status}, function (res) {
			that.loading = false;
			uni.stopPullDownRefresh();
			var data = res.datalist;
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
			console.log(that.datalist)
		});
    },
	//选择登录进入
	selectBusiness(e){
		var that = this;
		var bid = e.currentTarget.dataset.id;
		that.loading = true;
		app.post('ApiAdminBusiness/selectBusiness', {bid: bid}, function (res) {
			that.loading = false;
			if(res.status ==0){
				app.error(res.msg)
				return;
			}
			app.goto(res.tourl);
		});
	}
  }
};
</script>
<style>

.ind_business {width: 100%;margin-top: 20rpx;font-size:26rpx;padding:0 24rpx}
.ind_business .ind_busbox{ background: #fff;padding:20rpx;overflow: hidden; margin-bottom:20rpx;border-radius:8rpx;position:relative}
.ind_business .ind_buspic{ width:100rpx;height:100rpx; margin-right: 28rpx; }
.ind_business .ind_buspic image{ width: 100%;height:100%;border-radius: 8rpx;object-fit: cover;}
.bus_address{font-size: 24rpx;color: #7D7D7D;}
.ind_busbox .status{width: 130rpx;margin-left: 10rpx;color: #999;}
.ind_busbox .status .image{width:30rpx;height:30rpx;margin-left: 10rpx;}
.ind_business .bus_title{ font-size: 28rpx; color: #222;font-weight:bold;line-height: 40rpx;width:100%;white-space: nowrap;overflow: hidden;text-overflow: ellipsis;}
.ind_business .bus_score{font-size: 24rpx;color:#FC5648;display:flex;align-items:center}
.ind_business .bus_score .img{width:24rpx;height:24rpx;margin-left:10rpx}
.ind_business .bus_score .txt{margin-left:20rpx}
.ind_business .indsale_box{ display: flex}
.ind_business .bus_sales{ font-size: 24rpx; color:#999;margin-left: 20rpx;}
.ind_bottom{margin-top: 20rpx;font-size: 24rpx;border-top: 2rpx solid #f5f5f5;padding-top: 20rpx;}
.ind_bottom .v1{color: #212121;line-height: 45rpx;}
.ind_bottom .v1 .t1{color: #7D7D7D;}
.btn{ height:100rpx;line-height: 100rpx;width:90%;margin:0 auto;border-radius:50rpx;margin-top:30rpx;color: #fff;font-size: 30rpx;font-weight:bold}

</style>