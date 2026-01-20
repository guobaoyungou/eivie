<template>
<view class="container">
	<block v-if="isload">
		<!--<view class="topsearch flex-y-center">
			<view class="f1 flex-y-center">
				<image class="img" src="/static/img/search_ico.png"></image>
				<input :value="keyword" placeholder="输入姓名/手机号搜索" placeholder-style="font-size:24rpx;color:#C2C2C2" @confirm="searchConfirm" @input="searchChange"></input>
			</view>
		</view>-->

	
		<view v-for="(item, index) in datalist" :key="index" class="content flex" :data-id="item.id">
				
			<view class="item" @click="goto"  >
				<view class="f1">
					<view class="name">次卡名称：{{item.couponname}}</view>
					<view class="f2">
						<view class="headimg"><image :src="item.headimg" /></view>
						<view class="text1">	
							<text class="t1">{{item.nickname}} </text>
							<text class="t2">剩余次数：<text style="font-weight: bold;">{{item.limit_count-item.used_count}}</text> </text>
						</view>	
					</view>
				</view>
				<view class="btn" @click="goto" :data-url="'couponused?id='+item.id" :style="{background:t('color1')}"> 使用记录</view>
			</view>
	
		</view>
		<nodata v-if="nodata"></nodata>
		<nomore v-if="nomore"></nomore>
	</block>
	<loading v-if="loading"></loading>
	<dp-tabbar :opt="opt" @getmenuindex="getmenuindex"></dp-tabbar>
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
      keyword: '',
      datalist: [],
      type: "",
		keyword:'',
		nodata:false,
		 curTopIndex: -1,
		 index:0,
		 curCid:0,
		 
    }
  },
  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.type = this.opt.type || '';
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
			var bid = that.opt.bid ? that.opt.bid : '';
			var order = that.order;
		    var keyword = that.keyword;
			that.loading = true;
			that.nodata = false;
			that.nomore = false;
			app.post('ApiSearchMember/couponrecord', {pagenum: pagenum,mid:that.opt.mid}, function (res) { 
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
		searchChange: function (e) {
		  this.keyword = e.detail.value;
		},
		searchConfirm: function (e) {
		  var that = this;
		  var keyword = e.detail.value;
		  that.keyword = keyword;
		  that.getdata();
		}
  }
};
</script>
<style>
.topsearch{width:94%;margin:16rpx 3%;}
.topsearch .f1{height:60rpx;border-radius:30rpx;border:0;background-color:#fff;flex:1}
.topsearch .f1 .img{width:24rpx;height:24rpx;margin-left:10px}
.topsearch .f1 input{height:100%;flex:1;padding:0 20rpx;font-size:28rpx;color:#333;}

.search-navbar-item .iconshangla{position: absolute;top:-4rpx;padding: 0 6rpx;font-size: 20rpx;color:#7D7D7D}
.search-navbar-item .icondaoxu{position: absolute;top: 8rpx;padding: 0 6rpx;font-size: 20rpx;color:#7D7D7D}
.search-navbar-item .iconshaixuan{margin-left:10rpx;font-size:22rpx;color:#7d7d7d}
.search-history {padding: 24rpx 34rpx;}
.search-history .search-history-title {color: #666;}
.search-history .delete-search-history {float: right;padding: 15rpx 20rpx;margin-top: -15rpx;}
.search-history-list {padding: 24rpx 0 0 0;}
.search-history-list .search-history-item {display: inline-block;height: 50rpx;line-height: 50rpx;padding: 0 20rpx;margin: 0 10rpx 10rpx 0;background: #ddd;border-radius: 10rpx;font-size: 26rpx;}


.order-tab{display:flex;width:100%;overflow-x:scroll;border-bottom: 1px #f5f5f5 solid;background: #fff;padding:0 10rpx}
.order-tab2{display:flex;width:auto;min-width:100%}
.order-tab2 .item{width:20%;padding:0 20rpx;font-size:28rpx;font-weight:bold;text-align: center; color:#999999; height:80rpx; line-height:80rpx; overflow: hidden;position:relative;flex-shrink:0;flex-grow: 1;}
.order-tab2 .on{color:#222222;}
.order-tab2 .after{display:none;position:absolute;left:50%;margin-left:-20rpx;bottom:10rpx;height:6rpx;border-radius:1.5px;width:40rpx}
.order-tab2 .on .after{display:block}


.content{width:94%;margin:20rpx 3%;background:#fff;border-radius:5px;padding:20rpx 40rpx; justify-content: space-between;}
.content .item { display: flex; justify-content: space-between; align-items:center; width: 100%;}
.content .item .name{ font-size: 30rpx; font-weight: bold;}
.content .f2 { display: flex; margin-top: 20rpx;}
.content .f2 image{ width: 100rpx; height: 100rpx; border-radius: 50%; margin-right: 10rpx;}
.content .f2 .t1{color:#2B2B2B;font-size:26rpx;margin-left:10rpx; margin: 10rpx 0;}
.content .f2 .t2{color:#999999;font-size:28rpx; background: #E8E8F7;color:#7A83EC; padding:3rpx 20rpx; font-size: 20rpx; border-radius: 18rpx;}
.content .f2 .text1{  display: flex; flex-direction: column;}
.content .btn{ background: #7A83EC; height: 40rpx; line-height: 40rpx; padding: 0 20rpx; color:#fff; border-radius:28rpx; font-size: 20rpx; text-align: center; margin-top: 20rpx;}

</style>