<template>
	<view v-if="isload">
		<view class="top-view flex-col">
			<view class="points-view flex-col">
				<view class="points-title-view flex"><image :src="pre_url+'/static/img/jifen-F.png'" class="icon-class"></image>{{t('新积分')}}</view>
				<view class="points-num">{{info.score || 0}}</view>
			</view>
			<view class="top-data-list flex flex-y-center">
				<view class="data-options flex-col">
					<view class="title-text">{{textset['已释放']}}</view>
					<view class="num-text">{{info.release_score || 0}}</view>
				</view>
				<view class="line-class"></view>
				<view class="data-options flex-col">
					<view class="title-text">{{textset['累计补贴']}}</view>
					<view class="num-text">{{info.send_all || 0}}</view>
				</view>
				<view class="line-class"></view>
				<view class="data-options flex-col">
					<view class="title-text">{{textset['剩余数量']}}</view>
					<view class="num-text">{{info.remain || 0}}</view>
				</view>
			</view>
		</view>
		<view class="list-title-view">
			补贴日记
		</view>
		<block v-for="(item,index) in datalist">
			<view class="options-view flex-bt" @tap="goto" :data-url="item.batchlog_url">
				<view class="left-view flex-col">
					<view class='left-title'>{{item.remark}}</view>
					<view class='time-text'>{{item.createtime}}</view>
				</view>
				<view class="price-view flex-col">
					<view class="money">
						{{item.money}}
						<text v-if="item.batchlog_url" class="iconfont iconjiantou" style="font-size:28rpx"></text>
					</view>
					<view class="pack_money" v-if="!isNull(item.pack_money) && item.pack_money>0">{{t('加速包')}}提速：{{item.pack_money}}</view>
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
		    app.post('ApiNewScore/release_log', {st: st,pagenum: pagenum,id:that.id}, function (res) {
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
	.options-view{background: #fff;border-bottom: 1px #f6f6f6 solid;padding: 30rpx 20rpx;align-items: center;}
	.options-view .left-view{}
	.options-view .left-view .left-title{font-size: 26rpx;color: #333;font-weight: bold;white-space: nowrap;}
	.options-view .left-view .time-text{font-size: 24rpx;color: #828282;margin-top: 20rpx;}
	.price-view .money{font-size: 30rpx;color: #fb443e;text-align: end;}
	.price-view .pack_money{font-size: 26rpx;color: #2A80D9;}
</style>