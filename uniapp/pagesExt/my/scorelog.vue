<template>
<view class="container">
	<block v-if="isload">
		<view class="myscore" :style="{background:t('color1')}">
			<view class="f1">
				我的{{t('积分')}}
				<view class="btn" @tap="goto" data-url="/pagesB/my/memberscorewithdraw" v-if="set.member_score_withdraw">提现</view>
			</view>
			<view class="f2">{{myscore}}<view class="btn-mini" v-if="scoreTransfer" @tap="goto" data-url="/pagesExt/my/scoreTransfer">转赠</view></view>
			<view class="item flex-bt flex-y-center">
				<view v-if="scoreWithdraw">
					<view class="label">允提{{t('积分')}} <view class="btn" @tap="goto" data-url="/pagesExt/my/scoreWithdraw">提现</view></view>
					<view class="value">{{myscore2}}</view>
				</view>
				<view v-if="score_freeze > 0">
					<view class="label flex-y-center"  @tap="goto" data-url="/pagesB/my/scorefreezelog">冻结{{t('积分')}}
						<image class="right-image" :src="pre_url+'/static/imgsrc/commission_dw.png'" />
					</view>
					<view class="value" style="margin-top: 20rpx;">{{score_freeze}}</view>
				</view>
			</view> 
		</view>
		<view class="content" id="datalist">
			<block v-for="(item, index) in datalist" :key="index"> 
			<view class="item">
				<view class="f1">
						<text class="t1">{{item.remark}}</text>
						<text class="t2">{{item.createtime}}</text>
				</view>
				<view class="f2">
					<block v-if="item.score>0">
						<text class="t1">+{{item.score}}</text>
					</block>
					<block v-else>
						<text class="t2">{{item.score}}</text>
					</block>
				</view>
			</view>
		</block>
		<nodata v-if="nodata"></nodata>
		</view>
		<nomore v-if="nomore"></nomore>
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
      st: 0,
      datalist: [],
      pagenum: 1,
      myscore: 0,
      myscore2: 0,
      nodata: false,
      nomore: false,
			scoreTransfer:false,
			scoreWithdraw:false,
			businessTransfer:false,//商家互转
			score_freeze:0,
			set:{}
    };
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
      app.post('ApiMy/scorelog', {st: st,pagenum: pagenum}, function (res) {
				that.loading = false;
        var data = res.data;
        if (pagenum == 1) {
					uni.setNavigationBarTitle({
						title: that.t('积分') + '明细'
					});
					that.myscore = res.myscore;
					that.myscore2 = res.score_withdraw;
					that.scoreTransfer = res.scoreTransfer;
					that.scoreWithdraw = res.scoreWithdraw;
					that.businessTransfer = res.businessTransfer;
					that.score_freeze = res.scoreFreeze;
					that.datalist = data;
					that.set = res.set;
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
    changetab: function (e) {
      var st = e.currentTarget.dataset.st;
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
.myscore{width:94%;margin:20rpx 3%;border-radius: 10rpx 56rpx 10rpx 10rpx;position:relative;display:flex;flex-direction:column;padding:70rpx 0}
.myscore .f1{margin:0 0 0 60rpx;color:rgba(255,255,255,0.8);font-size:24rpx;}
.myscore .f2{margin:20rpx 0 0 60rpx;color:#fff;font-size:64rpx;font-weight:bold; position: relative;}

.myscore .item {min-width: 50%;color: #FFFFFF;padding: 0 10%;margin-top: 30rpx;}
.myscore .item .label{font-size: 26rpx}
.myscore .item .value{font-size: 44rpx;margin-top: 10rpx;font-weight: bold;}

.myscore  .btn{height: 50rpx;text-align: center;border: 1px solid #e6e6e6;border-radius: 10rpx;color: #fff;font-size: 24rpx;font-weight: bold;display: inline-flex;align-items: center;justify-content: center;margin-left: 10rpx;padding: 0 10rpx;}

.content{ width:94%;margin:0 3%;}
.content .item{width:100%;margin:20rpx 0;background:#fff;border-radius:5px;padding:20rpx 20rpx;display:flex;align-items:center}
.content .item .f1{flex:1;display:flex;flex-direction:column}
.content .item .f1 .t1{color:#000000;font-size:30rpx;word-break:break-all;overflow:hidden;text-overflow:ellipsis;}
.content .item .f1 .t2{color:#666666}
.content .item .f1 .t3{color:#666666}
.content .item .f2{ flex:1;font-size:36rpx;text-align:right}
.content .item .f2 .t1{color:#03bc01}
.content .item .f2 .t2{color:#000000}
.content .item .f3{ flex:1;font-size:32rpx;text-align:right}
.content .item .f3 .t1{color:#03bc01}
.content .item .f3 .t2{color:#000000}

.data-empty{background:#fff}
.btn-mini {right: 32rpx;top: 28rpx;width: 100rpx;height: 50rpx;text-align: center;border: 1px solid #e6e6e6;border-radius: 10rpx;color: #fff;font-size: 24rpx;font-weight: bold;display: inline-flex;align-items: center;justify-content: center;position: absolute;}
.btn-xs{min-width: 100rpx;height: 50rpx;line-height: 50rpx;text-align: center;border: 1px solid #e6e6e6;border-radius: 10rpx;color: #fff;font-size: 24rpx;font-weight: bold;margin-bottom:10rpx;padding: 0 20rpx;}
.scoreL{flex: 1;padding-left: 60rpx;color: #FFFFFF;}
.score_txt{color:rgba(255,255,255,0.8);font-size:24rpx;padding-bottom: 14rpx;}
.score{color:#fff;font-size:64rpx;font-weight:bold;}
.scoreR{padding-right: 30rpx;align-self: flex-end;flex-wrap: wrap;}
.right-image{height: 32rpx;width: 32rpx;margin-left: 5rpx;}
</style>