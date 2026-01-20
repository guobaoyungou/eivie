<template>
<view class="container">
	<block v-if="isload">
		<view class="order-content">
			<block v-for="(item, index) in datalist" :key="index">
				<view class="order-box" @tap.stop="goto" :data-url="'complete?rid=' + item.id">
					<view class="content flex" >
						<view class="detail">
							<text class="t1">{{item.title}}</text>
							<text class="t2">{{item.date}}</text>
						</view>
						<view class="score">
							<text class="t3">{{item.score}}</text>
							<text class="t4">分</text>							
						</view>
					</view>		
				
				</view>
			</block>
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
      datalist: [],
      pagenum: 1,
      nomore: false,
      codtxt: "",
			nodata:false,
      mlid:0,
    };
  },

  onLoad: function (opt) {
		this.opt = app.getopts(opt);
    this.mlid = this.opt.mlid || 0;
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
		var kcid = this.opt.kcid || 0;
		that.nodata = false;
		that.nomore = false;
		that.loading = true;
      app.post('ApiKecheng/recordlog', {kcid: kcid,pagenum: pagenum,mlid:that.mlid}, function (res) {
		that.loading = false;
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
      });
    },
  }
};
</script>
<style>

.order-content{display:flex;flex-direction:column}
.order-box .content{display:flex;padding:16rpx 0px; background: #fff; margin:30rpx; border-radius:8rpx; padding: 30rpx; justify-content: space-between;}
.order-box .content .detail{display:flex;flex-direction:column;margin-left:14rpx;width: 70%;}
.order-box .content .detail .t1{font-size:28rpx;line-height:36rpx;margin-bottom:10rpx;color:#333;font-weight: bold;}
.order-box .content .detail .t2{height: 46rpx;line-height: 46rpx;color: #93949E;overflow: hidden;font-size: 26rpx;}
.order-box .content .score .t3{height:40rpx;line-height:40rpx; font-size: 32rpx;color:#FF5347; font-weight: bold; margin-top: 30rpx; }
.order-box .content .score .t4{ font-size: 24rpx;color:#FF5347; }
.order-box .content .score{ margin-top: 30rpx;}

</style>