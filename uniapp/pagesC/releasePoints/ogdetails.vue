<template>
	<view v-if="isload">
		<view v-if="showyejidata" class="top-view flex-col" :style="{background:t('color1')}">
			<view v-if="showHaveRelease" class="points-view flex-col">
				<view class="points-title-view flex"><image :src="pre_url+'/static/img/jifen-F.png'" class="icon-class"></image>已释放</view>
				<view class="points-num">{{cashback_total.have_release}}</view>
			</view>
			<view v-if="showRemainRelease || showLastCircleYeji || showNextCircleYeji" class="top-data-list flex flex-y-center">
				<view v-if="showRemainRelease" class="data-options flex-col">
					<view class="title-text">待释放</view>
					<view class="num-text">{{cashback_total.remain_release}}</view>
				</view>
				<view v-if="showRemainRelease && showLastCircleYeji" class="line-class"></view>
				<view v-if="showLastCircleYeji" class="data-options flex-col">
					<view class="title-text">上期平台业绩</view>
					<view class="num-text">{{cashback_total.last_circle_yeji}}</view>
				</view>
				<view v-if="showLastCircleYeji && showNextCircleYeji" class="line-class"></view>
				<view v-if="showNextCircleYeji" class="data-options flex-col">
					<view class="title-text">下期平台业绩</view>
					<view class="num-text">{{cashback_total.next_circle_yeji}}</view>
				</view>
			</view>
		</view>
		<view class="list-title-view">
			释放日记
		</view>
		<block v-for="(item,index) in datalist">
			<view class="options-view flex-bt">
				<view class="left-view flex-col">
					<view class='left-title'>{{item.remark}}</view>
					<view class='time-text'>{{item.create_time}}</view>
				</view>
				<view class="price-view" v-if="item.is_yuji==1" style="color: green;">{{item.back_price}}</view>
				<view class="price-view" v-else>{{item.back_price}}</view>
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
				cashback_total:{},
				og_id:0,
        showyejidata:false,//是否显示头部业绩数据
        showHaveRelease:false,//是否显示已释放数据
        showRemainRelease:false,//是否显示待释放绩数据
        showLastCircleYeji:false,//是否显示上期平台业绩数据
        showNextCircleYeji:false,//是否显示下期平台业绩数据
			}
		},
		onLoad: function (opt) {
				this.opt = app.getopts(opt);
				this.og_id = this.opt.og_id || 0;
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
		    app.post('ApiCashback/og_detail_log', {st: st,pagenum: pagenum,og_id:that.og_id}, function (res) {
				that.loading = false;
		      var data = res.datalist;
          that.showyejidata = res.showyejidata;//是否显示头部业绩数据
          that.showHaveRelease = res.showHaveRelease;//是否显示已释放数据
          that.showRemainRelease = res.showRemainRelease;//是否显示待释放绩数据
          that.showLastCircleYeji = res.showLastCircleYeji;//是否显示上期平台业绩数据
          that.showNextCircleYeji = res.showNextCircleYeji;//是否显示下期平台业绩数据
		      if (pagenum == 1) {
            uni.setNavigationBarTitle({
              title: that.t('释放积分') + '明细'
            });
            
            that.datalist = data;
            if (data.length == 0) {
              that.nodata = true;
            }
            that.cashback_total = res.total_data;
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
			changetab: function (is_withdraw) {
			  this.is_withdraw = is_withdraw;
			  uni.pageScrollTo({
			    scrollTop: 0,
			    duration: 0
			  });
			  this.getdata();
			},
		}
	}
</script>

<style>
	.top-view{width: 100%;background: #fb443e;align-items: center;}
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
	.price-view{font-size: 30rpx;color: #fb443e;}
</style>