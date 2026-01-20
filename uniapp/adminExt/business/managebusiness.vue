<template>
<view class="container">
	<block v-if="isload">
		<view class="ind_business">
			<view class="ind_buslist" id="datalist">
				<block v-for="(item, index) in datalist" :key="index">
				<view @tap="goto" :data-url="'index?bid=' + item.id">
					<view class="ind_busbox ">
						<view class="flex1 flex-row flex-y-center">
							<view class="ind_buspic flex0"><image :src="item.logo"></image></view>
							<view class="" style="flex:1">
								<view class="bus_title">{{item.name}}</view>
								<view class="bus_address">地址：<text class="x1">{{item.province}}{{item.city}}{{item.district}}{{item.address}}</text></view>
							</view>
							<view class="status flex-y-center" @tap="toEditBusiness" :data-id="item.id">
								<text v-if="item.status ==0" style="color: orange;">待审核</text>	
								<text v-else-if="item.status ==1" style="color: green;">营业中</text>	
								<text v-else-if="item.status ==2" style="color: red;">已驳回</text>	
								<text v-else >已过期</text>	
									<image :src="pre_url+'/static/img/arrowright.png'" class="image"/>
							</view>	
						</view>	
						
						<!-- 审核状态 -->
						<view class="ind_bottom" >
							<view class="v1"><text  class="t1">申请时间：</text>{{dateFormat(item.createtime)}}</view>
							<block v-if="item.status ==2">
								<view class="v1  flex-bt">
									<view class="t1">拒绝原因：	<text style="#212121">{{item.reason}}</text></view>
									<view  class="delete" @tap="deleteBusiness" :data-id="item.id">删除</view>
								</view>
								
							</block>	
							<block v-else-if="item.status == 1">
								<view class="v1">营业状态：<text class="t1" v-if="item.is_open==1">营业中</text><text v-else>休息中</text></view>
							</block>	
							
						</view>	
					</view>
					
				</view>
				</block>
		
				<nomore v-if="nomore"></nomore>
				<nodata v-if="nodata"></nodata>
				<view style="height: 170rpx;"></view>
				<button class="savebtn" :style="'background:linear-gradient(90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'"  @tap="toAddBusiness">创建商户</button>
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
		
		app.post('ApiAdminBusiness/getUserBusinessList', {pagenum: pagenum}, function (res) {
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
	deleteBusiness(e){
		var that = this;
		var bid = e.currentTarget.dataset.id;
		app.confirm('确定删除商户吗?', function () {
			that.loading = true;
			app.post('ApiAdminBusiness/deleteBusiness', {bid:bid}, function (res) {
				that.loading = false;
				if(res.status ==0){
					app.error(res.msg);
					return;
				}
				app.success('删除成功')
				that.getdata();
			});
		})	
	},
	toAddBusiness(){
		app.goto('editbusiness');
	},
	toEditBusiness(e){
		var bid = e.currentTarget.dataset.id;
		app.goto('editbusiness?bid='+bid);
	}
	
  }
};
</script>
<style>

.ind_business {width: 100%;margin-top: 20rpx;font-size:26rpx;padding:0 24rpx}
.ind_business .ind_busbox{ background: #fff;padding:20rpx;overflow: hidden; margin-bottom:20rpx;border-radius:8rpx;position:relative}
.ind_business .ind_buspic{ width:100rpx;height:100rpx; margin-right: 28rpx; }
.ind_business .ind_buspic image{ width: 100%;height:100%;border-radius: 8rpx;object-fit: cover;}
.ind_business .bus_title{ font-size: 28rpx; color: #222;font-weight:bold;line-height: 40rpx;width:100%;white-space: nowrap;overflow: hidden;text-overflow: ellipsis;}
.bus_address{font-size: 24rpx;color: #7D7D7D;}
.ind_busbox .status{width: 125rpx;margin-left: 10rpx;color: #999;}
.ind_busbox .status .image{width:30rpx;height:30rpx;margin-left: 10rpx;}

.ind_business .bus_score{font-size: 24rpx;color:#FC5648;display:flex;align-items:center}
.ind_business .bus_score .img{width:24rpx;height:24rpx;margin-left:10rpx}
.ind_business .bus_score .txt{margin-left:20rpx}
.ind_business .indsale_box{ display: flex}
.ind_business .bus_sales{ font-size: 24rpx; color:#999;margin-left: 20rpx;}
.ind_bottom{margin-top: 20rpx;font-size: 24rpx;border-top: 2rpx solid #eeeeee;padding-top: 20rpx;}
.ind_bottom .v1{color: #212121;line-height: 45rpx;}
.ind_bottom .v1 .t1{color: #7D7D7D;}
.ind_bottom .delete{color: #424242;font-size: 28rpx;margin-right: 20rpx;}
.savebtn{ width: 90%; height:96rpx; line-height: 96rpx; text-align:center;border-radius:48rpx; color: #fff;font-weight:bold;margin-top:60rpx; border: none; position: fixed;bottom: 60rpx;left: 5%;}
</style>