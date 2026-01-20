<template>
<view class="container">
	<block v-if="isload">
		<dd-tab :itemdata="['发起众筹列表','申请入股列表']" :itemst="['0','1']" :st="st" :showstatus="showstatus" :ismoney="1" :isfixed="true" @changetab="changetab"></dd-tab>
		<view class="content">
			<view v-for="(item, index) in datalist" :key="index" class="item">
				<view>
            <view >
              <view >所属商户：{{item.bname}}</view>
            </view>
						<view >
              <view >门店类型：{{item.shname}}</view>
            </view>
            <view style="display: flex;flex-wrap: wrap;align-items: center;">
              <view>凭证：</view>
              <view v-for="(item2, index2) in item.pics" :key="index2" style="margin: 10rpx 0;">
                <view style="width: 180rpx;height: 180rpx;margin-right: 10rpx;overflow: hidden;"><image :src="item2" @tap="previewImage" :data-url="item2" mode="widthFix" style="width: 100%;"></image></view>
              </view>
            </view>
            <view >
              状态：
              <text v-if="item.status == 0" style="color: #f30;">待审核</text>
              <text v-if="item.status == 1" style="color: green;">已通过</text>
              <text v-if="item.status == -1" style="color: #999;">已驳回</text>
            </view>
            <view v-if="item.status == -1" >拒绝原因：{{item.checkreason}}</view>
            <view style="display: flex;justify-content: space-between;align-items: center;">
              <view>时间：{{item.createtime}}</view>
							<view v-if="item.status == 1 && item.endstatus == 0" @tap="goto" :data-url="'/pagesD/businessShareholder/index?type=1&id='+item.id+'&pid='+item.mid" :style="'border: 2rpx solid'+t('color1')+';border-radius: 8rpx 8rpx ;padding: 0 18rpx;font-size:26rpx;color:'+t('color1')">
							  继续入股
							</view>
              <view v-if="item.status == 1 && item.endstatus == 0" @tap="goto" :data-url="'/pagesD/businessShareholder/index?opttype=share&type=1&id='+item.id+'&pid='+item.mid" :style="'border: 2rpx solid'+t('color1')+';border-radius: 8rpx 8rpx ;padding: 0 18rpx;font-size:26rpx;color:'+t('color1')">
                邀请入股
              </view>
            </view>
				</view>
			</view>
		</view>
		<nomore v-if="nomore"></nomore>
		<nodata v-if="nodata"></nodata>
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
			
			canwithdraw:false,
			textset:{},
      st: 0,
      datalist: [],
      pagenum: 1,
			nodata:false,
      nomore: false,
      showstatus:[1,1],
    };
  },

  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.st = this.opt.st || 0;
		this.getdata();
  },
	onPullDownRefresh: function () {
		this.getdata(true);
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
      app.post('ApiBusinessShareholder/applylog', {st: st,pagenum: pagenum}, function (res) {
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
    changetab: function (st) {
      this.st = st;
      uni.pageScrollTo({
        scrollTop: 0,
        duration: 0
      });
      this.getdata();
    },
  }
};
</script>
<style>
.container{ width:100%;margin-top:90rpx;display:flex;flex-direction:column}
.content{ width:94%;margin:0 3% 20rpx 3%;}
.content .item{width:100%;background:#fff;margin:20rpx 0;padding:20rpx 20rpx;border-radius:8px;line-height: 60rpx;}
.content .item:last-child{border:0}
</style>