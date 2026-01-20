<template>
  <view class="container">
    <block v-if="isload">
		<view class="top-data-list flex flex-y-center" :style="{background:t('color1')}" v-if="show_top==1">
			<view class="data-options flex-col">
				<view class="title-text">最大份额</view>
				<view class="num-text">{{set.max_green_score}}</view>
			</view>
			<view class="line-class"></view>
			<view class="data-options flex-col">
				<view class="title-text">已发放</view>
				<view class="num-text">{{set.green_score_total}}</view>
			</view>
			<view class="line-class"></view>
			<view class="data-options flex-col">
				<view class="title-text">剩余</view>
				<view class="num-text">{{set.plate_remain_green_score}}</view>
			</view>
		</view>
      <!-- 顶部卡片 -->
     <view class="myscore" :style="{background:t('color1')}" >
     	<view class="f1">我的{{t('绿色积分')}}</view>
     	<view class="f2">{{myscore}}<view class="btn-mini" @tap="goto" v-if="set.open_withdraw==1" data-url="/pagesB/my/greenscoreWithdraw">全部提取</view></view>
     	<view class="f1">当前每个{{t('绿色积分')}}价值{{set.green_score_price}}元</view>
     	<view class="f1" v-if="green_score_hb>0">已领增值红包{{green_score_hb}}元</view>
     </view>
      <!-- 选项卡 -->
	  <dd-tab :itemdata="[t('绿色积分')+'列表','已完成记录']" :itemst="['0','1']" :st="is_withdraw" :showstatus="showstatus" :ismoney="1" :isfixed="false" @changetab="changetab"></dd-tab>

      <!-- 列表 -->
     <view class="content" id="datalist">
		 <block v-for="(item, index) in datalist" :key="index"> 
			<view class="list-item" >
			  <view class="item-row"  @tap="goto" :data-url="'/pagesB/my/greenscorelog?jilu_id='+item.id">
				<view class="column">
				  <text>发放数量: {{item.value}}</text>
				  <text>剩下数量: {{item.remain}}</text>
				</view>
				<view class="column" v-if="greenscore_max==1">
				  <text>封顶额度: {{item.maximum_num || 0}}</text>
				  <text>已扣额度: {{item.dec_maximum|| 0}}</text>
				</view>
			  </view>
			  <view class="item-row highlight">
				<text>可提取金额: {{item.green_score_value}}</text>
			  </view>
			  <view class="item-row">
				<text class="time">{{item.remark}} {{item.createtime}}</text>
				<block v-if="withdraw_type==1 && set.open_withdraw==1">
					<button class="btn-mini2" v-if="item.is_withdraw==0 && item.remain>0" @tap="goto" :data-url="'/pagesB/my/greenscoreWithdraw?log_id='+item.id">提取</button>
					<button class="btn-mini3" v-else >已提取</button>
				</block>
				<block v-if="false">
					<button class="btn-mini2">查看明细</button>
				</block>
			  </view>
			</view>
		 </block>
      </view>
      <nodata v-if="nodata"></nodata>
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
		st: 1,
		datalist: [],
		pagenum: 1,
		myscore: 0,
		nodata: false,
		nomore: false,
		score_price:1,
		set:[],
		withdraw_url:'',
		is_withdraw:0,
		showstatus:[1,1],
		green_score_hb:0,
		withdraw_type:0,
		greenscore_max:0,
		show_top:0
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
      app.post('ApiGreenScore/scorejilu', {st: st,pagenum: pagenum,is_withdraw:that.is_withdraw}, function (res) {
		that.loading = false;
        var data = res.data;
        if (pagenum == 1) {
			uni.setNavigationBarTitle({
				title: that.t('绿色积分') + '明细'
			});
			that.myscore = res.myscore;
			that.datalist = data;
			if (data.length == 0) {
				that.nodata = true;
			}
			that.set = res.set;
			that.withdraw_url = res.withdraw_url;
			that.green_score_hb = res.green_score_hb;
			that.withdraw_type = res.withdraw_type;
			that.greenscore_max = res.greenscore_max;
			that.show_top = res.show_top;
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
};
</script>

<style>
.container {
  background-color: #f5f5f5;
  min-height: 100vh;
}
.myscore{width:94%;margin:20rpx 3%;border-radius: 10rpx 56rpx 10rpx 10rpx;position:relative;display:flex;flex-direction:column;padding:70rpx 0}
.myscore .f1{margin:0 0 0 60rpx;color:rgba(255,255,255,0.8);font-size:24rpx;}
.myscore .f2{margin:20rpx 0 0 60rpx;color:#fff;font-size:64rpx;font-weight:bold; position: relative;}
.btn-mini {right: 32rpx;top: 28rpx;width: 130rpx;height: 50rpx;text-align: center;border: 1px solid #e6e6e6;border-radius: 10rpx;color: #fff;font-size: 24rpx;font-weight: bold;display: inline-flex;align-items: center;justify-content: center;position: absolute;}

.title {
  font-size: 28rpx;
}
.score {
  font-size: 60rpx;
  font-weight: bold;
  margin: 10rpx 0;
}
.subtitle {
  font-size: 24rpx;
  opacity: 0.8;
}
.btn-withdraw {
  position: absolute;
  top: 20rpx;
  right: 20rpx;
  background-color: white;
  color: #ff4d4f;
  border: none;
  border-radius: 30rpx;
  padding: 10rpx 20rpx;
  font-size: 24rpx;
}

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

.top-data-list{width: 100%;padding: 35rpx 0rpx;justify-content: center;}
.top-data-list .data-options{text-align: center;max-width: 32%;width: auto;min-width: 30%;}
.top-data-list .line-class{height: 50rpx;border-left: 1rpx #e5d734 solid;}
.top-data-list .data-options .title-text{font-size: 20rpx;color: #fff;font-weight: bold;white-space: nowrap;}
.top-data-list .data-options .num-text{font-size: 34rpx;color: #ecdd36;margin-top: 15rpx;}
</style>