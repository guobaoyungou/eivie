<template>
  <view class="container">
    <block v-if="isload">
      <!-- 列表 -->
     <view class="content" id="datalist">
		 <block v-for="(item, index) in datalist" :key="index"> 
			<view class="list-item" >
        <view class="item-row highlight">
          <view>贡献值: {{item.gxz}}</view>
        </view>
			  <view class="item-row">
          <view class="column">
            <text>所属季度: {{item.jd}}</text>
            <text>所属时间: {{item.time}}</text>
          </view>
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
		type:1
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
		var type = that.opt.type || 1;
		that.nodata = false;
		that.nomore = false;
		that.loading = true;
      app.post('ApiMy/jqfenhonggxz', {type: type,pagenum: pagenum}, function (res) {
		  that.loading = false;
        var data = res.data;
        var title = res.title;
        uni.setNavigationBarTitle({
          title: title
        });
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
  margin: 3rpx 0;
  font-size: 26rpx;
}

.highlight {
  font-weight: bold;
  margin-bottom: 10rpx;
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