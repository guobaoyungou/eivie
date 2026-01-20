<template>
<view class="container">
	<block v-if="isload">
		<dd-tab :itemdata="['全部','审核中','已审核','已驳回','已打款']" :itemst="['all','0','1','2','3']" :st="st" @changetab="changetab"></dd-tab>
		<view class="content">
			<view  v-for="(item, index) in datalist" :key="index" class="listrow">
				<view  class="item">
					<view class="f1">
							<text class="t1">提现{{t('积分')}}：{{item.score}}<text class="money" :style="{color:t('color1')}">(￥{{item.money}})</text></text>
							<text class="t2">{{item.showtime}}</text>
					</view>
					<view class="f3">
							<text class="st0" v-if="item.status==0">审核中</text>
							<text class="st1" v-if="item.status==1">已审核</text>
							<text class="st2" v-if="item.status==2">已驳回</text>
							<text class="st3" v-if="item.status==3">已打款</text>
					</view>
				</view>
				<view class="tips" v-if="item.status==2 && item.reason">{{item.reason}}</view>
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
      st: 'all',
      datalist: [],
      pagenum: 1,
			nodata:false,
      nomore: false
    };
  },

  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.st = this.opt.st || 'all';
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
      app.post('ApiAdminFinance/bscorewithdrawlog', {st: st,pagenum: pagenum}, function (res) {
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
.container{ width:100%;display:flex;flex-direction:column}
.content{ width:94%;margin:0 3% 20rpx 3%;}
.content .listrow{width:100%;background:#fff;margin:20rpx 0;padding:20rpx 20rpx;border-radius:8px;line-height: 50rpx;}
.content .item{display:flex;align-items:center;}
.content .item:last-child{border:0}
.content .item .f1{width:500rpx;display:flex;flex-direction:column}
.content .item .f1 .t1{color:#000000;font-size:30rpx;word-break:break-all;overflow:hidden;text-overflow:ellipsis;}
.content .item .f1 .t2{color:#666666}
.content .item .f1 .t3{color: #03bc01;}
.content .item .f2{ flex:1;width:200rpx;font-size:36rpx;text-align:right}
.content .item .f2 .t1{color:#03bc01}
.content .item .f2 .t2{color:#000000}
.content .item .f3{ flex:1;width:200rpx;font-size:32rpx;text-align:right; font-size: 26rpx;}
.content .item .f3 .st0{color:#999999}
.content .item .f3 .st1{color:#03bc01}
.content .item .f3 .st2{color:#ff7835}
.content .item .f3 .st3{color:#999999}
.content .item .f3 .t2{color:#000000}
.content .tips{color:#ff7835;font-size: 24rpx;background: #fff4ef; padding: 0 20rpx;border-radius: 10rpx;}
.money{;margin-left: 20rpx;font-weight: bold;}
.data-empty{background:#fff}
</style>