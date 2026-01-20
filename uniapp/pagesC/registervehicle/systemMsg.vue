<template>
	<view class="page-class">
		<block v-if="isload">
		<block v-for="(item,index) in datalist">
			<view class="msg-options flex flex-y-center" @click="gotoDetail(item.id)">
				<image :src="`${pre_url}/static/img/vehicle/xitongtongzhi.png`" class="image-class"></image>
				<view class="info-class flex flex-col">
					<view class="title-view-class flex flex-y-center flex-bt">
						<view class="title-text">{{item.title}}</view>
						<view class="time-text">{{dateFormat(item.createtime)}}</view>
					</view>
					<view class="info-text">{{item.content}}</view>
				</view>
			</view>
		</block>
		<view class="page-bottom-text">没有更多了</view>
		</block>
		<dp-tabbar :opt="opt"></dp-tabbar>
	</view>
</template>

<script>
	var app = getApp();
	export default{
		data(){
			return{
				loading:false,
				isload: false,
				pre_url: app.globalData.pre_url,
				pagenum: 1,
				nomore: false,
				nodata:false,
				datalist:[],
				opt: {},
			}
		},
		onReachBottom: function () {
		  if (!this.nodata && !this.nomore) {
		    this.pagenum = this.pagenum + 1;
		    this.getdata(true);
		  }
		},
		onLoad: function (opt) {
			this.opt = app.getopts(opt);
			this.getdata();
		},
		methods:{
			getdata:function(loadmore){
				if(!loadmore){
					this.pagenum = 1;
					this.datalist = [];
				}
				var that = this;
				var pagenum = that.pagenum;
				that.nodata = false;
				that.nomore = false;
				that.loading = true;
				app.get('ApiMemberNotice/list', {pagenum:pagenum}, function (res) {
					that.loading = false;
					var data = res.data || [];
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
			gotoDetail:function(id){
				app.goto("msgDetails?id="+id);
			}
		}
	}
</script>

<style>
	page{background: #fff;}
	.page-class{padding:0rpx 30rpx;position: relative;}
	.msg-options{width:100%;padding: 40rpx 0rpx;border-bottom: 1px #f4f4f4 solid;}
	.msg-options .image-class{width: 90rpx;height: 90rpx;border-radius: 50%;}
	.msg-options .info-class{flex: 1;height: 90rpx;padding-left: 20rpx;justify-content: space-between;}
	.title-view-class .title-text{font-size: 26rpx;font-weight: bold;color: #000;}
	.title-view-class .time-text{font-size: 20rpx;color: #a09fa6;}
	.info-class .info-text{font-size: 24rpx;color: #a09fa6;white-space: nowrap;overflow: hidden;text-overflow: ellipsis;width: 530rpx;}
	.page-bottom-text{text-align: center;font-size: 24rpx;color: #a09fa6;margin: 50rpx auto 30rpx;}
</style>