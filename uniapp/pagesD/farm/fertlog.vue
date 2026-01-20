<template>
<view class="container">
	<block v-if="isload">
		<dd-tab :itemdata="tabdata" :itemst="tabitems" :st="st" :showstatus="showstatus" :isfixed="true" @changetab="changetab"></dd-tab>
		<view class="myscore" :style="{background:t('color1')}">
			<!-- <view class="f1">
				我的{{fert_name}}
			</view>
			<view class="f2">
				0
				<view class="btn-mini" @tap="goto" data-url="/pagesD/farm/landlist">兑换</view>
			</view> -->
			<view class="item flex-bt flex-y-center">
				<view>
					<view class="label flex-y-center">我的{{fert_name}}</view>
					<view class="value" style="margin-top: 20rpx;">{{remain}}</view>
					<view class="btn" v-if="show_buy" @tap="goto" data-url="/pagesD/farm/buy">兑换</view>
				</view>
			</view>
			<view class="item flex-bt flex-y-center" v-if="show_ljnum==1">
				<view>
					<view class="label flex-y-center">累计获得</view>
					<view class="value" style="margin-top: 20rpx;">{{ljnum}}</view>
				</view>
			</view> 
		</view>
		<view class="content">
			<view v-for="(item, index) in datalist" :key="index" class="item">
				<view class="f1">
					<text class="t1">{{item.remark}}</text>
					<text class="t2">{{item.createtime}}</text>
					<text class="t3">变更后{{t('余额')}}: {{item.after}}</text>
				</view>
				<view class="f2">
					<text class="t1" v-if="item.num>0">+{{item.num}}</text>
					<text class="t2" v-else>{{item.num}}</text>
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
		pre_url:app.globalData.pre_url,
		loading:false,
		isload: false,
		menuindex:-1,
		tabdata:[],
		tabitems:[],
		showstatus:[],	
		canwithdraw:false,
		textset:{},
		st: 'seed',
		datalist: [],
		pagenum: 1,
		nodata:false,
		nomore: false,
		fert_name:'',
		remain:0,
		ljnum:0,
		show_ljnum:0,
		show_buy:0,
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
		app.post('ApiFarm/fert_log', {st: st,pagenum: pagenum}, function (res) {
			that.loading = false;
			var data = res.data;
			if (pagenum == 1) {
				that.textset = app.globalData.textset;
				uni.setNavigationBarTitle({
					title: res.fert_name + '明细'
				});
				that.datalist = data;
				if (data.length == 0) {
					that.nodata = true;
				}
				that.tabdata = res.tabdata;
				that.tabitems = res.tabitems;
				that.fert_name = res.fert_name;
				that.remain = res.remain;
				that.ljnum = res.ljnum;
				that.show_ljnum = res.show_ljnum;
				that.show_buy = res.show_buy;
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
			console.log(res.showstatus,'showstatus');
			if(res.showstatus.length > 0){
				that.showstatus = res.showstatus;
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

.myscore{width:94%;margin:20rpx 3%;border-radius: 10rpx 56rpx 10rpx 10rpx;position:relative;display:flex;flex-direction:initial;padding:70rpx 0}
.myscore .f1{margin:0 0 0 60rpx;color:rgba(255,255,255,0.8);font-size:24rpx;}
.myscore .f2{margin:20rpx 0 0 60rpx;color:#fff;font-size:64rpx;font-weight:bold; position: relative;}

.myscore .item {min-width: 50%;color: #FFFFFF;padding: 0 10%;margin-top: 30rpx;}
.myscore .item .label{font-size: 26rpx}
.myscore .item .value{font-size: 44rpx;margin-top: 10rpx;font-weight: bold;}

.myscore  .btn{height: 50rpx;text-align: center;border: 1px solid #e6e6e6;border-radius: 10rpx;color: #fff;font-size: 24rpx;font-weight: bold;display: inline-flex;align-items: center;justify-content: center;margin-left: 10rpx;padding: 0 10rpx;}
.right-image{height: 32rpx;width: 32rpx;margin-left: 5rpx;}

.content{ width:94%;margin:0 3% 20rpx 3%;}
.content .item{width:100%;background:#fff;margin:20rpx 0;padding:20rpx 20rpx;border-radius:8px;display:flex;align-items:center}
.content .item:last-child{border:0}
.content .item .f1{display:flex;flex-direction:column}
.content .item .f1 .t1{color:#000000;font-size:30rpx;word-break:break-all;overflow:hidden;text-overflow:ellipsis;}
.content .item .f1 .t2{color:#666666}
.content .item .f1 .t3{color:#666666}
.content .item .f2{ flex:1;font-size:36rpx;text-align:right}
.content .item .f2 .t1{color:#03bc01}
.content .item .f2 .t2{color:#000000}
.content .item .f3{ flex:1;font-size:32rpx;text-align:right}
.content .item .f3 .t1{color:#03bc01}
.content .item .f3 .t2{color:#000000}
.btn1{height:60rpx;line-height:60rpx;color:#fff;border-radius:3px;text-align:center;padding: 0 15rpx;float: right;font-size: 14px;margin-left: 10rpx}
.data-empty{background:#fff}


.btn-mini {right: 32rpx;top: 28rpx;width: 100rpx;height: 50rpx;text-align: center;border: 1px solid #e6e6e6;border-radius: 10rpx;color: #fff;font-size: 24rpx;font-weight: bold;display: inline-flex;align-items: center;justify-content: center;position: absolute;}
.btn-xs{min-width: 100rpx;height: 50rpx;line-height: 50rpx;text-align: center;border: 1px solid #e6e6e6;border-radius: 10rpx;color: #fff;font-size: 24rpx;font-weight: bold;margin-bottom:10rpx;padding: 0 20rpx;}
.scoreL{flex: 1;padding-left: 60rpx;color: #FFFFFF;}
.score_txt{color:rgba(255,255,255,0.8);font-size:24rpx;padding-bottom: 14rpx;}
.score{color:#fff;font-size:64rpx;font-weight:bold;}
.scoreR{padding-right: 30rpx;align-self: flex-end;flex-wrap: wrap;}
.right-image{height: 32rpx;width: 32rpx;margin-left: 5rpx;}
</style>