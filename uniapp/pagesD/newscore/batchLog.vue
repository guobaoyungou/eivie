<template>
	<view v-if="isload">
		<view class="top-view flex-col">
			<view class="points-view flex-col">
				<view class="points-title-view flex">总释放</view>
				<view class="points-num">{{info.money || 0}}</view>
			</view>
			<view class="top-data-list flex flex-y-center">
				<view class="data-options flex-col">
					<view class="title-text">总账期</view>
					<view class="num-text">{{info.batch_num || 0}}</view>
				</view>
				<view class="line-class"></view>
				<view class="data-options flex-col">
					<view class="title-text">已释放账期</view>
					<view class="num-text">{{info.batch_num_send || 0}}</view>
				</view>
				
			</view>
		</view>
		<view class="list-title-view">
			账期明细
		</view>
		<view class="content">
		<block v-for="(item,index) in datalist">
			<view class="item">
				<view class="f1">
					<text class="t1">账期金额：{{item.money}}元</text>
					<text class="t2">{{item.remark}}</text>
					<text class="t2">预计发放时间：{{item.send_time}}</text>
				</view>
				<view class="f3">
					<text class="t1" v-if="item.status==0">未发放</text>
					<text class="t2" v-if="item.status==1">已发放</text>
				</view>
			</view>
		</block>
		<nodata v-if="nodata"></nodata>
		<nomore v-if="nomore"></nomore>
		</view>
		<loading v-if="loading"></loading>
		<dp-tabbar :opt="opt"></dp-tabbar>
		<popmsg ref="popmsg"></popmsg>
	</view>
</template>

<script>
	var app = getApp();
	export default{
		data(){
			return{
				isload:true,
				pre_url: app.globalData.pre_url,
				nodata:false,
				nomore:false,
				loading:false,
				menuindex:-1,
				pre_url:app.globalData.pre_url,
				datalist: [],
				pagenum: 1,
				info:{},
				id:0,
				textset:{}
			}
		},
		onLoad: function (opt) {
				this.opt = app.getopts(opt);
				this.id = this.opt.id || 0;
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
		    app.post('ApiNewScore/batch_log', {pagenum: pagenum,id:that.id}, function (res) {
				that.loading = false;
		      var data = res.datalist;
		      if (pagenum == 1) {
				uni.setNavigationBarTitle({
				  title: that.t('释放积分') + '明细'
				});
            
				that.datalist = data;
				if (data.length == 0) {
				  that.nodata = true;
				}
				that.info = res.info;
				that.textset = res.textset;
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
		}
	}
</script>

<style>
	.top-view{width: 100%;background:#FC2D41;align-items: center;}
	.points-view{padding: 40rpx 0rpx;width: 100%;text-align: center;}
	.points-view .points-title-view{font-size: 28rpx;color: #ecdd36;align-items: center;width: 100%;justify-content: center;}
	.points-view .points-title-view .icon-class{width: 24rpx;height: 24rpx;margin-right: 5rpx;}
	.points-view .points-num{font-size: 62rpx;color: #fff;margin-top: 10rpx;}
	.top-data-list{width: 100%;padding: 35rpx 0rpx;justify-content: center;}
	.top-data-list .data-options{text-align: center;max-width: 32%;width: auto;min-width: 30%;}
	.top-data-list .line-class{height: 50rpx;border-left: 1rpx #e5d734 solid;}
	.top-data-list .data-options .title-text{font-size: 20rpx;color: rgba(255, 255, 255, .8);font-weight: bold;white-space: nowrap;}
	.top-data-list .data-options .num-text{font-size: 34rpx;color: #ecdd36;margin-top: 15rpx;}
	.list-title-view{font-size: 28rpx;color: #828282;padding: 30rpx 20rpx;}
	
	
	.content{ width:94%;margin:0 3% 20rpx 3%;display:flex;flex-direction:column}
	.content .item{width:100%;background:#fff;margin:20rpx 0;padding:20rpx 20rpx;border-radius:8px;display:flex;align-items:center}
	.content .item:last-child{border:0}
	.content .item .f1{display:flex;flex-direction:column}
	.content .item .f1 .t1{color:#000000;font-size:30rpx;word-break:break-all;overflow:hidden;text-overflow:ellipsis;}
	.content .item .f1 .t2{color:#666666}
	.content .item .f1 .t3{color:#666666}
	.content .item .f3{ flex:1;font-size:32rpx;text-align:right}
	.content .item .f3 .t1{color:#FC2D41}
	.content .item .f3 .t2{color:#03bc01}
</style>