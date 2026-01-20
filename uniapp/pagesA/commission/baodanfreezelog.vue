<template>
<view class="container">
	<block v-if="isload">
		<view class="content">
			<block >
			<view v-for="(item, index) in datalist" :key="index" class="item">
				<view class="f1">
						<text class="t1">{{item.remark}}</text>
						<text class="t2">{{item.createtime}}</text>
						<text class="t3">变更后余额: {{item.after}}元</text>
				</view>
				<view class="f2">
						<text class="t1" v-if="item.commission>0">+{{item.commission}}</text>
						<text class="t2" v-else>{{item.commission}}</text>
				</view>
			</view>
			</block>
			
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
export default {
  data() {
    return {
			opt:{},
			loading:false,
      isload: false,
			menuindex:-1,
			
      nodata: false,
      nomore: false,
      st: 0,
	  type:0,
      datalist: [],
			textset:{},
      pagenum: 1,
    };
  },

  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.st = this.opt.st || 0;
		this.type = this.opt.type || 0;
		var that = this;
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
      var st = that.st;
	  var type = that.type;
      var pagenum = that.pagenum;
			that.loading = true;
			that.nodata = false;
      that.nomore = false;
      app.post('ApiAgent/baodanCommissionLog', {pagenum: pagenum}, function (res) {
				that.loading = false;
        var data = res.data;
        if (pagenum == 1) {
					that.textset = app.globalData.textset;
					uni.setNavigationBarTitle({
						title: '报单佣金冻结记录'
					});
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

.content{ width:94%;margin:20rpx 3%;}
.content .item{width:100%;background:#fff;margin:20rpx 0;padding:40rpx 30rpx;border-radius:8px;display:flex;align-items:center}
.content .item:last-child{border:0}
.content .item .f1{width:500rpx;display:flex;flex-direction:column}
.content .item .f1 .t1{color:#000000;font-size:30rpx;word-break:break-all;overflow:hidden;text-overflow:ellipsis;}
.content .item .f1 .t2{color:#666666;font-size:24rpx;margin-top:10rpx}
.content .item .f1 .t3{color:#666666;font-size:24rpx;margin-top:10rpx}
.content .item .f2{ flex:1;width:200rpx;font-size:36rpx;text-align:right}
.content .item .f2 .t1{color:#03bc01}
.content .item .f2 .t2{color:#000000}
.content .item .f3{ flex:1;width:200rpx;font-size:32rpx;text-align:right}
.content .item .f3 .t1{color:#03bc01}
.content .item .f3 .t2{color:#000000}

.data-empty{background:#fff}
</style>