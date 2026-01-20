<template>
	<view v-if="isload">
		<view v-if="showyejidata" class="top-view flex-col" :style="{background:t('color1')}">
			<view class="points-view flex-col">
				<view class="points-title-view flex"><image :src="pre_url+'/static/img/jifen-F.png'" class="icon-class"></image>已释放</view>
				<view class="points-num">{{cashback_total.have_release}}</view>
			</view>
			<view class="top-data-list flex flex-y-center" v-if="set.cashback_multiply_yeji_type==0">
				<view class="data-options flex-col">
					<view class="title-text">待释放</view>
					<view class="num-text">{{cashback_total.remain_release}}</view>
				</view>
				<view class="line-class"></view>
				<view class="data-options flex-col">
					<view class="title-text">今日释放</view>
					<view class="num-text">{{cashback_total.today_release}}</view>
				</view>
				<view class="line-class"></view>
				<view class="data-options flex-col">
					<view class="title-text">今日新增</view>
					<view class="num-text">{{cashback_total.today_add}}</view>
				</view>
			</view>
		</view>
		<!-- 选项卡 -->
		<dd-tab :itemdata="[t('释放积分')+'列表','已完成记录']" :itemst="['0','1']" :st="st" :showstatus="showstatus" :isfixed="false" @changetab="changetab"></dd-tab>
		<view class="content" id="datalist">
				 <block v-for="(item, index) in datalist" :key="index"> 
					<view class="list-item" >
					  <view class="item-row">
						<view class="column">
						  <text>发放数量: {{item.back_price}}</text>
						  <text>剩余数量: {{item.remain}}</text>
						</view>
						<view class="column">
						  <text>释放数量: {{item.have_send || 0}}</text>
						  <text v-if="showLastCircleYeji">上期业绩: {{item.last_circle_yeji|| 0}}</text>
						</view>
					  </view>
					  <view class="item-row highlight">
						<text v-if="item.status!=2">上期释放: {{item.last_circle_send}}</text>
						<text v-if="item.status==2">已完成</text>
					  </view>
					  <view class="item-row">
						<text class="time">{{item.createtime}}</text>
						<block >
							<button @tap="goto" :data-url="'/pagesC/releasePoints/ogdetails?og_id='+item.sog_id" class="btn-mini2">查看明细</button>
						</block>
					  </view>
					</view>
				 </block>
		 </view>
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
				isload:false,
				pre_url: app.globalData.pre_url,
				nodata:false,
				nomore:false,
				loading:false,
				menuindex:-1,
				datalist: [],
				pagenum: 1,
				cashback_total:{},
				is_withdraw:0,
				showstatus:[1,1],
				st:0,
				set:{},
				opt:{},
        showyejidata:false,//是否显示头部业绩数据
        showLastCircleYeji:false,//是否展示上期平台业绩
			}
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
				var st = that.st;
				that.nodata = false;
				that.nomore = false;
				that.loading = true;
		    app.post('ApiCashback/og_log', {st: st,pagenum: pagenum}, function (res) {
          that.loading = false;
		      var data = res.datalist;
          that.showyejidata = res.showyejidata;
          that.showLastCircleYeji = res.showLastCircleYeji;
		      if (pagenum == 1) {
            uni.setNavigationBarTitle({
              title: that.t('增值释放积分') + '记录'
            });
            
            that.datalist = data;
            if (data.length == 0) {
              that.nodata = true;
            }
            that.cashback_total = res.total_data;
            that.set = res.set;
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
			  this.st = st;
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
	
	.tabs {
	  display: flex;
	  background-color: white;
	  margin-top: 20rpx;
	}
	.tab {
	  flex: 1;
	  text-align: center;
	  padding: 20rpx 0;
	  font-size: 28rpx;
	}
	.tab.active {
	  color: #ff4d4f;
	  border-bottom: 4rpx solid #ff4d4f;
	}
	
	.content {
	  margin-top: 20rpx;
	}
	.list-item {
	  background-color: white;
	  margin: 20rpx;
	  padding: 20rpx;
	  border-radius: 10rpx;
	}
	
	.item-row {
	  display: flex;
	  justify-content: space-between;
	  margin-bottom: 10rpx;
	  font-size: 30rpx;
	}
	
	.item-row:last-child {
	  align-items: center; /* 垂直居中对齐最后一行的内容 */
	}
	
	.column {
	  display: flex;
	  flex-direction: column;
	}
	
	.column text {
	  margin-bottom: 10rpx;
	}
	
	.highlight {
	  color: #52c41a;
	}
	
	.time {
	  color: #999;
	  font-size: 24rpx;
	  flex: 1; /* 让时间占据剩余空间 */
	}
	
	.btn-mini2, .btn-mini3 {
	  width: 140rpx;
	  height: 50rpx;
	  text-align: center;
	  border: 1px solid #e6e6e6;
	  border-radius: 10rpx;
	  display: inline-flex;
	  align-items: center;
	  justify-content: center;
	  font-size: 24rpx;
	  margin-left: auto; /* 将按钮推到右侧 */
	}
	
	.btn-mini2 {
	  background-color: #ff4d4f;
	  color: white;
	}
	
	.btn-mini3 {
	  background-color: #A5A5A5;
	  color: white;
	}
	
</style>