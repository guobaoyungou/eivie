<template>
<view class="container">
	<block v-if="isload">
		<view class="topsearch flex-y-center" :style="{background:t('color1')}">
			<view class="f1 flex-y-center">
				<image class="img" :src="pre_url+'/static/img/search_ico.png'"></image>
				<input :value="keyword" placeholder="输入关键字搜索" placeholder-style="font-size:24rpx;color:#C2C2C2" @confirm="searchConfirm"></input>
			</view>
		</view>
		<view class="listbox">
			<view v-for="(item, index) in datalist" :key="index" class="tr listitem">
				<!-- <view class="member">
					<view><image :src="item.headimg"></view>
					<view>{{item.nickname}}</view>
				</view> -->
				<view class="info flex" @tap="goto" :data-url="'/pagesA/dscj/index?id='+item.hid+'&bid='+item.bid">
					<view>
					<view class="title">活动名称：{{item.title}}</view>
					<view class="td td2">参加时间：{{item.createtime}}</view>
					<view class="td td2" v-if="item.totalprice>0">报名支付：￥{{item.totalprice}}</view>
					<view class="td td2" v-if="item.is_done">中奖奖品：
						<text v-if="item.jxid>0" :style="{color:t('color1')}">{{item.jxmc}}</text>
						<text v-if="item.jxid==0">未中奖</text>
					</view>
					<view v-else>待开奖</view>
					</view>
					<view class="opt">
							<!-- <text v-if="item.status==0" @tap="duijiang" :data-k="index" :data-id="item.id" style="background-color:#fb5a43;padding:4rpx 8rpx">兑奖</text> -->
							<text  @tap="goto" :data-url="'/pagesA/dscj/index?id='+item.hid+'&bid='+item.bid" >查看</text>
					</view>
				</view>
			</view>
		</view>
		<nomore v-if="nomore"></nomore>
		<nodata v-if="nodata"></nodata>
		
		<view class="myrecord" @tap="goto" :data-url="'/pages/index/index'">首页</view>
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

      st: 'all',
      datalist: [],
      pagenum: 1,
      nomore: false,
      nodata: false,
			keyword:'',
			bid:0,
			pre_url:app.globalData.pre_url,
    };
  },
  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.bid = this.opt.bid || 0;
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
	onNavigationBarSearchInputConfirmed:function(e){
		this.searchConfirm({detail:{value:e.text}});
	},
  methods: {
    changetab: function (st) {
			this.st = st;
      uni.pageScrollTo({
        scrollTop: 0,
        duration: 0
      });
      this.getdata();
    },
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
      app.post('ApiDscj/myrecord', {bid:that.bid,pagenum: pagenum,keyword:that.keyword}, function (res) {
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
    
		searchConfirm:function(e){
			this.keyword = e.detail.value;
      this.getdata(false);
		}
  }
};
</script>
<style>
.container{ width:100%;}
.topsearch{width:100%;margin: 0;height: 100rpx;line-height: 100rpx;padding: 0 20rpx;}
.topsearch .f1{height:60rpx;border-radius:30rpx;border:0;background-color:#fff;flex:1}
.topsearch .f1 .img{width:24rpx;height:24rpx;margin-left:10px}
.topsearch .f1 input{height:100%;flex:1;padding:0 20rpx;font-size:28rpx;color:#333;}

.flex{justify-content: space-between;align-items: center;}
.listbox{width:100%;font-size:30rpx;margin:20rpx 0;padding: 0 20rpx;}
.listbox .listitem{background-color:#FFFFFF; color:#222222;border-radius: 16rpx;margin-top: 20rpx;padding: 30rpx 10rpx;}
.listitem .member{flex-shrink: 0;width: 150rpx;text-align: center;}
.listitem .member image{height: 70rpx;width: 70rpx;border-radius: 50%;}
.listitem .info{flex: 1;line-height: 50rpx;color: #989898;padding: 0 20rpx;}
.listitem .info .title{color: #222222;font-weight: bold;font-size: 30rpx;}
.listitem .opt{display: flex;justify-content: flex-end;align-items: center;flex-shrink: 0;}
.listitem .opt text{padding: 0 20rpx;border: 1rpx solid #f58d19;border-radius: 20rpx;color: #f58d19;}
.myrecord{width: 100rpx;text-align: center;padding: 0 10rpx; height: 100rpx;background: #2f5be4;opacity: 0.7;border-radius: 50%;position: fixed;bottom: 350rpx;right: 20rpx;color: #FFF6F3;font-weight: bold;font-size: 24rpx;display: flex;justify-content: center;align-items: center;}


</style>