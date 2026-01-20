<template>
<view class="container">
	<block v-if="isload">
		<view class="article_list flex-wp">
			<view class="article-itemlist flex-col" v-for="(item,index) in datalist" :key="item.id" @click="goto" :data-url="'/pagesA/hikvision/detail?id='+item.id">
				<view class="article-pic">
					<image class="image" :src="item.icon" mode="widthFix"/>
				</view>
				<view class="article-info">
					<view class="p1" :style="set.title_size&&set.title_size>0?'font-size:'+set.title_size*2+'rpx':''">{{item.name}}</view>
                    <block>
                    	<view class="p3">
                            {{item.deviceserial}}
                        </view>
                    </block>

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
		datalist: [],
		pagenum: 1,
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
		that.loading = true;
		that.nodata = false;
		that.nomore = false;
		var pagenum = that.pagenum;
		app.post('ApiHikvision/getlists', {pagenum: that.pagenum}, function (res) {
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


.article_list{padding:10rpx 16rpx;background:#f6f6f7;margin-top:6rpx;}

.article_list .article-itemlist {width:48%;display: inline-block;position: relative;margin:0rpx 5rpx 20rpx;padding:12rpx;background: #fff;display:flex;border-radius:8rpx;}
.article_list .article-itemlist .article-pic {width: 100%;height:200rpx;overflow:hidden;background: #ffffff;padding-bottom: 25%;position: relative;}
.article_list .article-itemlist .article-pic .image{position:absolute;top:0;left:0;width: 100%;height:auto}
.article_list .article-itemlist .article-info {width: 100%;height:auto;overflow:hidden;padding:0 20rpx;display:flex;flex-direction:column;justify-content:space-between;margin-top: 20rpx;}
.article_list .article-itemlist .article-info .p1{color:#222222;font-weight:bold;font-size:28rpx;line-height:46rpx;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:2;overflow:hidden;height:92rpx}
.article_list .article-itemlist .article-info .p2{display:flex;flex-grow:0;flex-shrink:0;font-size:24rpx;color:#a88;overflow:hidden;padding-bottom:6rpx}

.p3{color:#8c8c8c;font-size:28rpx;line-height:46rpx;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:2;overflow:hidden;}
</style>