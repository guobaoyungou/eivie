<template>
<view class="container" v-if="isload==1">
	<view class="content" id="datalist">
		<block v-for="(item, index) in datalist" :key="index"> 
			<view class="item">
				<view class="f1">
						<text class="t1" style="margin-bottom:10rpx">{{item.remark}}</text>
						<text class="t1" v-if="item.cardno">卡号：{{item.cardno}}</text>
						<text class="t1" v-else>兑换码：{{item.code}}</text>
						<text class="t2">兑换时间：{{item.createtime}}</text>
				</view>
			</view>
		</block>
		<nodata v-if="nodata"></nodata>
	</view>
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

      st: 0,
      datalist: [],
      pagenum: 1,
      myscore: 0,
      nomore: false,
			nodata:false
    };
  },
  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.getdata();
  },
	onPullDownRefresh: function () {
    this.pagenum = 1;
    this.datalist = [];
		this.getdata();
	},
  onReachBottom: function () {
    if (!this.nodata && !this.nomore) {
      this.pagenum = this.pagenum + 1
      this.getdata();
    }
  },
  methods: {
    changetab: function (e) {
      var st = e.currentTarget.dataset.st;
      this.pagenum = 1;
      this.st = st;
      this.datalist = [];
      uni.pageScrollTo({
        scrollTop: 0,
        duration: 0
      });
      this.getdata();
    },
    getdata: function () {
      var that = this;
      var pagenum = that.pagenum;
      var st = that.st;
			that.loading = true;
      app.post('ApiLipin/dhlog', {st: st,pagenum: pagenum}, function (res) {
				that.loading = false;
        var data = res.data;
        if (pagenum == 1) {
					that.datalist = data;
          that.myscore = res.myscore;
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
    }
  }
};
</script>
<style>
.nav{display:flex;width: 100%;padding:20rpx;}
.nav .item{flex:1;width: 50%;line-height:70rpx;height:70rpx;text-align:center;background:#fff;border-top:1px solid #ccc;border-bottom:1px solid #ccc;}
.nav .item:first-child{border:1px solid #ccc;border-radius:10rpx 0 0 10rpx;}
.nav .item:last-child{border:1px solid #ccc;border-radius:0 10rpx 10rpx 0}
.nav .on{background: #ff6801;color: #fff;}

.content{ width:94%;margin:0 3%;}
.content .item{width:100%;margin:20rpx 0;background:#fff;border-radius:5px;padding:20rpx 20rpx;display:flex;align-items:center}
.content .item:last-child{border:0}
.content .item .f1{flex:1;display:flex;flex-direction:column}
.content .item .f1 .t1{color:#000000;font-size:30rpx;word-break:break-all;overflow:hidden;text-overflow:ellipsis;}
.content .item .f1 .t2{color:#666666;font-size:26rpx}
.content .item .f1 .t3{color:#666666}
.content .item .f2{ flex:1;font-size:36rpx;text-align:right}
.content .item .f2 .t1{color:#03bc01}
.content .item .f2 .t2{color:#000000}
.content .item .f3{ flex:1;font-size:32rpx;text-align:right}
.content .item .f3 .t1{color:#03bc01}
.content .item .f3 .t2{color:#000000}

.data-empty{background:#fff}
</style>