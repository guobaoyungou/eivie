<template>
<view class="container">
	<view class="comment">
		<view v-for="(item, index) in datalist" :key="index" class="item">
			<view class="f1">
				<image class="t1" :src="item.headimg"/>
				<view class="t2">{{item.nickname}}</view>
				<view class="flex1"></view>
				<view class="t3"><image class="img" v-for="(item2,index2) in [0,1,2,3,4]" :key="index2"  :src="pre_url+'/static/img/star' + (item.score>item2?'2native':'') + '.png'"/></view>
			</view>
			<view style="color:#777;font-size:22rpx;">{{item.createtime}}</view>
			<view class="f2">
				<text class="t1">{{item.content}}</text>
				<view class="t2">
					<block v-if="item.content_pic!=''">
						<block v-for="(itemp, index) in item.content_pic" :key="index">
							<view @tap="previewImage" :data-url="itemp" :data-urls="item.content_pic">
								<image :src="itemp" mode="widthFix"/>
							</view>
						</block>
					</block>
				</view>
				<text class="t3" v-if="item.ggname">规格：{{item.ggname}}</text>
			</view>
			<view class="f3" v-if="item.reply_content">
				<view class="arrow"></view>
				<view class="t1">商家回复：{{item.reply_content}}</view>
			</view>
		</view>
  </view>
	<view class="addicon" @tap="goto" :data-url="'/pagesA/shop/productComment?proid='+proid" :style="'background:rgba('+t('color1rgb')+',0.5);color:#FFF'" v-if="addcommentbtn"><image :src="pre_url+'/static/img/add.png'"></image></view>
	<nomore v-if="nomore"></nomore>
	<nodata v-if="nodata" text="暂无评价~"></nodata>
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
      datalist: [],
      pagenum: 1,
      nomore: false,
			addcommentbtn:false,
			proid:0,
			nodata: false,
    };
  },

  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.proid = this.opt.proid || 0;
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
			that.loading = true;
			that.nodata = false;
			that.nomore = false;
      app.post('ApiShop/commentlist', {proid: that.opt.proid,pagenum: pagenum}, function (res) {
				that.loading = false;
				var data = res.data;
				if (pagenum == 1) {
          that.datalist = data;
          if (data.length == 0) {
            that.nodata = true;
          }
					that.addcommentbtn = res.addcommentbtn
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
    }
  }
};
</script>
<style>
	page{background: #fff;}
.container{background:#fff}
.comment{display:flex;flex-direction:column;padding:10rpx 0;}
.comment .item{background-color:#fff;padding:10rpx;width:94%;margin:0 3%;display:flex;flex-direction:column;border-bottom: 1rpx solid #efefef;}
.comment .item .f1{display:flex;width:100%;align-items:center;padding:10rpx 0;}
.comment .item .f1 .t1{width:70rpx;height:70rpx;border-radius:50%;}
.comment .item .f1 .t2{padding-left:10rpx;color:#333;font-weight:bold;font-size:30rpx;}
.comment .item .f1 .t3{text-align:right;}
.comment .item .f1 .t3 .img{width:24rpx;height:24rpx;margin-left:10rpx}
.comment .item .score{ font-size: 24rpx;color:#f99716;}
.comment .item .score image{ width: 140rpx; height: 50rpx; vertical-align: middle;  margin-bottom:6rpx; margin-right: 6rpx;}
.comment .item .f2{display:flex;flex-direction:column;width:100%;padding:10rpx 0;}
.comment .item .f2 .t1{color:#333;font-size:28rpx;}
.comment .item .f2 .t2{display:flex;width:100%}
.comment .item .f2 .t2 image{width:100rpx;height:100rpx;margin:10rpx;}
.comment .item .f2 .t3{color:#aaa;font-size:24rpx;}
.comment .item .f2 .t3{color:#aaa;font-size:24rpx;}
.comment .item .f3{width:100%;padding:10rpx 0;position:relative}
.comment .item .f3 .arrow{width: 16rpx;height: 16rpx;background:#eee;transform: rotate(45deg);position:absolute;top:0rpx;left:36rpx}
.comment .item .f3 .t1{width:100%;border-radius:10rpx;padding:10rpx;font-size:22rpx;color:#888;background:#eee}
.addicon{position: fixed;bottom: 50rpx;right: 10rpx;width: 80rpx;height: 80rpx;border-radius: 50%;border: 1rpx solid #e2e2e2;display: flex;justify-content: center;align-items: center;}
.addicon image{height: 50rpx;width: 50rpx;}
</style>