<template>
<view class="container">
	<block v-if="isload">
		<view class="topsearch flex-y-center">
			<view class="f1 flex-y-center">
				<image class="img" :src="pre_url+'/static/img/search_ico.png'"></image>
				<input :value="keyword" placeholder="搜索感兴趣的活动" placeholder-style="font-size:24rpx;color:#C2C2C2" @confirm="searchConfirm"></input>
			</view>
		</view>
		<dd-tab :itemdata="['全部','未开始','进行中','已结束']" :itemst="['all','0','1','2']" :st="st" :isfixed="false" @changetab="changetab"></dd-tab>
		
		<view class="article_list">
			<view class="article-itemlist" v-for="(item,index) in datalist" :key="item.id" @click="goto" :data-url="item.listpage_tourl">
				<view class="article-pic">
					<image class="image" :src="item.listpage_pic" mode="widthFix"/>
				</view>
				<view class="article-info">
					<view class="p1">{{item.listpage_title}}</view>
					<block v-if="item.listpage_description">
					<view class="p3" v-if="item.listpage_description">{{item.listpage_description}}</view>
					</block>
					<view class="p2">
						<text style="overflow:hidden" class="flex1">{{item.starttime}}</text>
						<text style="overflow:hidden;color:#f50">{{item.count}}人已报名</text>
					</view>
				</view>
			</view>
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

			nodata:false,
			nomore:false,
			keyword:'',
      pagenum: 1,
      datalist: [],
      bid: 0,
      st: 'all',
	  pre_url:app.globalData.pre_url,
    };
  },
  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.bid = this.opt.bid || 0;
		if(this.opt && this.opt.st){
			this.st = this.opt.st;
		}
		if(this.opt.keyword) {
			this.keyword = this.opt.keyword;
		}
    this.getdata();
  },
	onPullDownRefresh: function () {
		this.getdata();
	},
  onReachBottom: function () {
    if (!this.nomore && !this.nodata) {
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
      var keyword = that.keyword;
      var st = that.st;
			that.loading = true;
			that.nodata = false;
			that.nomore = false;
      app.post('ApiForm/formlist', {bid:that.bid,st: st,pagenum: pagenum,keyword:keyword}, function (res) {
				that.loading = false;
        var data = res.datalist;
        if (pagenum == 1){
					//uni.setNavigationBarTitle({
					//	title: res.title
					//});
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
    searchConfirm: function (e) {
      var that = this;
      var keyword = e.detail.value;
      that.keyword = keyword
      that.getdata();
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
page{background:#f6f6f7}
.topsearch{width:100%;padding:20rpx 20rpx;background:#fff}
.topsearch .f1{height:70rpx;border-radius:35rpx;border:0;background-color:#f5f5f5;flex:1;overflow:hidden}
.topsearch .f1 image{width:30rpx;height:30rpx;margin-left:10px}
.topsearch .f1 input{height:100%;flex:1;padding:0 20rpx;font-size:28rpx;color:#333;background-color:#f5f5f5;}

.article_list{padding:10rpx 16rpx;background:#f6f6f7;margin-top:6rpx;}

.article_list .article-itemlist {width:100%;display: inline-block;position: relative;margin-bottom:12rpx;padding:12rpx;background: #fff;display:flex;border-radius:8rpx;}
.article_list .article-itemlist .article-pic {width: 35%;height:0;overflow:hidden;background: #ffffff;padding-bottom: 25%;position: relative;}
.article_list .article-itemlist .article-pic .image{position:absolute;top:0;left:0;width: 100%;height:auto}
.article_list .article-itemlist .article-info {width: 65%;height:auto;overflow:hidden;padding:0 20rpx;display:flex;flex-direction:column;justify-content:space-between}
.article_list .article-itemlist .article-info .p1{color:#222222;font-weight:bold;font-size:30rpx;line-height:46rpx;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:2;overflow:hidden;height:92rpx}
.article_list .article-itemlist .article-info .p2{display:flex;flex-grow:0;flex-shrink:0;font-size:24rpx;color:#a88;overflow:hidden;padding-bottom:6rpx}

.p3{color:#8c8c8c;font-size:26rpx;line-height:46rpx;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:2;overflow:hidden;}

.areasearch{display: flex;align-items: center;}
.area-picker{display: flex;max-width: 250rpx;flex-shrink: 0;align-items: center;color:#999;padding-left: 20rpx;font-size: 28rpx;justify-content: space-between;}
.area-picker .txt{max-width: 180rpx;overflow: hidden;text-overflow: ellipsis;flex: 1;white-space: nowrap;}
.area-picker image{width: 24rpx;height: 24rpx;}
.area-input{display: flex;align-items: center;justify-content: space-between;flex: 1;padding-right:30rpx; border-left: 1px solid #e1e1e1;margin-left: 20rpx;}
</style>