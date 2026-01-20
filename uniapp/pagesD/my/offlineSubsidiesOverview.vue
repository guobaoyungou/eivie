<template>
<view>
	<block v-if="isload">
		<view class="banner" :style="{background:'linear-gradient(180deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0) 100%)'}">
		</view>
		<view class="contentdata">
			<view class="data">
        <view class="data_title flex-y-center"><image class="data_icon" :src="pre_url+'/static/imgsrc/commission_m1.png'"/>我的线下补助总览</view>
				<view class="data_module flex flex-wp">
					<view @tap="goto" data-url="/pagesD/my/offlineSubsidiesLog?ly_type=2" class="data_module_view">
						<view class="data_lable">{{set.pass_text}}</view>
						<view class="data_value">{{is_end==1?datalist.pass:'计算中'}}</view>
					</view>
					<view @tap="goto" data-url="/pagesD/my/offlineSubsidiesLog?ly_type=3" class="data_module_view">
						<view class="data_lable">{{set.recommend_text}}</view>
						<view class="data_value">{{is_end==1?datalist.recommend:'计算中'}}</view>
					</view>
          <view @tap="goto" data-url="/pagesD/my/offlineSubsidiesLog?ly_type=4" class="data_module_view">
            <view class="data_lable">{{set.lecturer_text}}</view>
            <view class="data_value">{{is_end==1?datalist.lecturer:'计算中'}}</view>
          </view>
          <view @tap="goto" data-url="/pagesD/my/offlineSubsidiesLog?ly_type=0" class="data_module_view">
            <view class="data_lable">{{set.lovefund_text}}</view>
            <view class="data_value">{{is_end==1?datalist.lovefund:'计算中'}}</view>
          </view>
          <view @tap="goto" data-url="/pagesD/my/offlineSubsidiesLog?ly_type=1" class="data_module_view">
            <view class="data_lable">{{set.upgrade_text}}</view>
            <view class="data_value">{{is_end==1?datalist.upgrade:'计算中'}}</view>
          </view>
          <view @tap="goto" data-url="/pagesD/my/offlineSubsidiesLog?ly_type=5" class="data_module_view">
            <view class="data_lable">{{set.yejireward_text}}</view>
            <view class="data_value">{{is_end==1?datalist.yejireward:'计算中'}}</view>
          </view>
          <view @tap="goto" data-url="/pagesD/my/offlineSubsidiesLog?ly_type=6" class="data_module_view">
            <view class="data_lable">{{set.renshureward_text}}</view>
            <view class="data_value">{{is_end==1?datalist.renshureward:'计算中'}}</view>
          </view>
          <view @tap="goto" data-url="/pagesD/my/offlineSubsidiesLog?ly_type=7" class="data_module_view">
            <view class="data_lable">{{set.offline_text}}直推奖</view>
            <view class="data_value">{{is_end==1?datalist.offline_zhitui:'计算中'}}</view>
          </view>
          <view @tap="goto" data-url="/pagesD/my/offlineSubsidiesLog?ly_type=8" class="data_module_view">
            <view class="data_lable">{{set.offline_text}}区域代理奖</view>
            <view class="data_value">{{is_end==1?datalist.offline_area:'计算中'}}</view>
          </view>

				</view>
			</view>
		</view>
		<view style="width:100%;height:20rpx;text-align: center;margin-top: 30px;color: #5d5c5c">点击数值查看详情</view>

	</block>
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
			pre_url:app.globalData.pre_url,
      is_end:0,
      hiddenmodalput: true,
      userinfo: [],
      set:[],
      datalist:[],
    };
  },

  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		var that = this;
		this.getdata();
  },
	onPullDownRefresh: function () {
		this.getdata();
	},
  methods: {
		getdata: function () {
			var that = this;
			that.loading = true;
			app.get('ApiMy/myOfflineSubsidiesOverview', {}, function (res) {
				that.loading = false;
				uni.setNavigationBarColor({
					frontColor: '#ffffff', 
					backgroundColor: that.t('color1') 
				});
				that.set = res.set;
				that.datalist = res.data;
        that.is_end = 1;
				that.loaded();
			});
		},
  }
};
</script>
<style>
.banner{position: absolute;width: 100%;height: 900rpx;}
.contentdata{display:flex;flex-direction:column;width:100%;padding:0 30rpx;position:relative;margin-bottom:20rpx}
.data{background:#fff;padding:30rpx;margin-top:30rpx;border-radius:16rpx}
.data_title{font-size: 28rpx;color: #333;font-weight: bold;}
.data_detail image{height: 24rpx;width: 24rpx;margin-left: 10rpx;}
.data_icon{height: 35rpx;width: 35rpx;margin-right: 15rpx;}
.data_btn image{height: 24rpx;width: 24rpx;margin-left: 6rpx;}
.data_module{margin-top: 60rpx;width: 100%;}
.data_module .data_module_view{min-width: 50%;margin-bottom: 20rpx;}
.data_lable{color: #999;}
.data_value{font-size: 44rpx;font-weight: bold;color: #333;margin-top: 10rpx;}

</style>