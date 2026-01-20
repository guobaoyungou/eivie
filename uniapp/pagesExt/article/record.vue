<template>
<view class="container">
	<view class="topsearch flex-y-center">
		<view class="f1 flex-y-center">
			<image class="img" :src="pre_url+'/static/img/search_ico.png'"></image>
			<input :value="keyword" placeholder="搜索本页" placeholder-style="font-size:24rpx;color:#C2C2C2" @confirm="searchConfirm"></input>
		</view>
	</view>
	<block v-if="isload">
		<view class="article_list">
			<!--横排-->
			<view  class="article-itemlist" v-for="(item,index) in datalist" :key="item.id" @click="goto" :data-url="'/pagesExt/article/fujian?id='+item.id">
				<view class="article-pic">
					<image class="image" :src="item.pic" mode="widthFix"/>
				</view>
				<view class="article-info">
					<view class="p1">{{item.name}}</view>
					<block>
						<view class="p3"  >
					        {{item.subname}}
					    </view>
					</block>
					<view class="p2">
						<text style="overflow:hidden" class="flex1">{{item.createtime}}</text>
						<!-- <text style="overflow:hidden">阅读 {{item.readcount}}</text> -->
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
      datalist: [],
      pagenum: 1,
			clist:[],
			cnamelist:[],
			cidlist:[],
      datalist: [],
      cid: 0,
			bid: 0,
			listtype:0,
            set:'',
            look_type:false,
			pre_url:app.globalData.pre_url,
    };
  },
  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.cid = this.opt.cid || 0;	
		this.bid = this.opt.bid || 0;
        this.look_type = this.opt.look_type || false;
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
	searchConfirm: function (e) {
		var that = this;
		var keyword = e.detail.value;
		that.keyword = keyword
		that.getdata();
	},
	getdata: function (loadmore) {
		if(!loadmore){
			this.pagenum = 1;
			this.datalist = [];
		}
	  var that = this;
	  var pagenum = that.pagenum;
	  var keyword = that.keyword;
	  var cid = that.cid;
		console.log(cid)
		that.loading = true;
		that.nodata = false;
		that.nomore = false;
	  app.post('ApiArticle/getResourceList', {pagenum:pagenum,keyword:keyword}, function (res) {
				that.loading = false;
		var data = res.data;
		if (pagenum == 1) {
		uni.setNavigationBarTitle({
			title: res.title
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
	searchConfirm: function (e) {
	  var that = this;
	  var keyword = e.detail.value;
	  that.keyword = keyword
	  that.getdata();
	},
	changetab: function (cid) {
	  this.cid = cid;
	  uni.pageScrollTo({
		scrollTop: 0,
		duration: 0
	  });
			if(this.listtype==2){
				this.$refs.waterfall.refresh();
			}
	  this.getdata();
	},
  }
};
</script>
<style>
page{background:#f6f6f7}
page{background:#f6f6f7}
.topsearch{width:100%;padding:20rpx 20rpx;background:#fff}
.topsearch .f1{height:70rpx;border-radius:35rpx;border:0;background-color:#f5f5f5;flex:1;overflow:hidden}
.topsearch .f1 image{width:30rpx;height:30rpx;margin-left:10px}
.topsearch .f1 input{height:100%;flex:1;padding:0 20rpx;font-size:28rpx;color:#333;background-color:#f5f5f5;}

.article_list{padding:10rpx 16rpx;background:#f6f6f7;margin-top:6rpx;}
.article_list .article-item1 {width:100%;display: inline-block;position: relative;margin-bottom:16rpx;background: #fff;border-radius:12rpx;overflow:hidden}
.article_list .article-item1 .article-pic {width:100%;height:auto;overflow:hidden;background: #ffffff;}
.article_list .article-item1 .article-pic .image{width: 100%;height:auto}
.article_list .article-item1 .article-info {padding:10rpx 20rpx 20rpx 20rpx;}
.article_list .article-item1 .article-info .p1{color:#222222;font-weight:bold;font-size:28rpx;line-height:46rpx;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:2;overflow:hidden;}
.article_list .article-item1 .article-info .t1{word-break: break-all;text-overflow: ellipsis;overflow: hidden;display: block;font-size: 32rpx;}
.article_list .article-item1 .article-info .t2{word-break: break-all;text-overflow: ellipsis;padding-top:4rpx;overflow:hidden;}
.article_list .article-item1 .article-info .p2{flex-grow:0;flex-shrink:0;display:flex;padding:10rpx 0;font-size:24rpx;color:#a88;overflow:hidden}

.article_list .article-item2 {width: 49%;display: inline-block;position: relative;margin-bottom: 12rpx;background: #fff;border-radius:8rpx;}

.article_list .article-itemlist {width:100%;display: inline-block;position: relative;margin-bottom:12rpx;padding:12rpx;background: #fff;display:flex;border-radius:8rpx;}
.article_list .article-itemlist .article-pic {width: 35%;height:0;overflow:hidden;background: #ffffff;padding-bottom: 25%;position: relative;}
.article_list .article-itemlist .article-pic .image{position:absolute;top:0;left:0;width: 100%;height:auto}
.article_list .article-itemlist .article-info {width: 65%;height:auto;overflow:hidden;padding:0 20rpx;display:flex;flex-direction:column;justify-content:space-between}
.article_list .article-itemlist .article-info .p1{color:#222222;font-weight:bold;font-size:28rpx;line-height:46rpx;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:2;overflow:hidden;}
.article_list .article-itemlist .article-info .p2{display:flex;flex-grow:0;flex-shrink:0;font-size:24rpx;color:#a88;overflow:hidden;padding-bottom:6rpx}

.article_list .article-item3 {width: 32%;display: inline-block;position: relative;margin-bottom: 12rpx;background: #fff;border-radius:8rpx;}

.p3{color:#8c8c8c;font-size:28rpx;line-height:46rpx;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:2;overflow:hidden;}
</style>