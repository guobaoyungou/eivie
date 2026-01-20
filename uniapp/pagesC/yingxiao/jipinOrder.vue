<template>
  <view class="container">
    <block v-if="isload">
      <!-- 选项卡 -->
      <dd-tab :itemdata="['全部','未完成','已完成']" :itemst="['all','0','1']" :st="st" :isfixed="true" @changetab="changetab"></dd-tab>
      <view style="width:100%;height:100rpx"></view>
      <!-- 列表 -->
      <!-- #ifndef H5 || APP-PLUS -->
      <view class="topsearch flex-y-center">
        <view class="f1 flex-y-center">
          <image class="img" :src="pre_url+'/static/img/search_ico.png'"></image>
          <input :value="keyword" placeholder="输入活动名称/订单号搜索" placeholder-style="font-size:24rpx;color:#C2C2C2" @confirm="searchConfirm"></input>
        </view>
      </view>
      <!--  #endif -->
     <view class="content" id="datalist">
		 <block v-for="(item, index) in datalist" :key="index"> 
			<view class="list-item" >
        <view class="item-row highlight">
          <view>序号: {{item.num}}</view>
          <view ><button class="btn-mini2"@tap.stop="goto" :data-url="'jipinCharts?id=' + item.id" :data-id="item.id">关系图</button></view>
        </view>
			  <view class="item-row">
          <view class="column">
            <text>所属活动: {{item.collage_jipin_name}}</text>
            <text>订单号: {{item.ordernum}}</text>
            <text>订单商品: {{item.goods_name}}</text>
            <text>时间: {{item.createtime}}</text>
            <text v-if="item.status ==0" style="color: #2D66FC">状态: {{item.status_name}}</text>
            <text v-if="item.status ==1" style="color: #888">状态: {{item.status_name}}</text>
            <text v-if="item.status ==1" style="color: #888">出局时间：{{item.outtime}}</text>
          </view>
			  </view>
			  <view class="item-row">
				  <text class="time" v-if="item.diff">{{item.diff}}后可提现</text>
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
		st: 'all',
		datalist: [],
		pagenum: 1,
    mydeposit: 0,
		nodata: false,
		nomore: false,
    keyword:'',
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
      app.post('ApiCollageJipin/collagejipinorder', {st: st,pagenum: pagenum,keyword:that.keyword}, function (res) {
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
    changetab: function (st) {
      this.st = st;
      uni.pageScrollTo({
        scrollTop: 0,
        duration: 0
      });
      this.getdata();
    },
    searchConfirm:function(e){
      this.keyword = e.detail.value;
      this.getdata(false);
    }
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

.topsearch{width:94%;margin:10rpx 3%;}
.topsearch .f1{height:60rpx;border-radius:30rpx;border:0;background-color:#fff;flex:1}
.topsearch .f1 .img{width:24rpx;height:24rpx;margin-left:10px}
.topsearch .f1 input{height:100%;flex:1;padding:0 20rpx;font-size:28rpx;color:#333;}

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
  font-size: 34rpx;
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
  font-size: 26rpx;
}

.highlight {
  font-weight: bold;
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