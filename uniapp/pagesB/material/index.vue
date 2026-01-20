<template>
	<view class="container">
		<block v-if="isload">
			<dd-tab :itemdata="['图片','视频']" :itemst="['0','1']" :st="st" :isfixed="true" @changetab="changetab"></dd-tab>
			<view style="width:100%;height:100rpx"></view>
			<view class="content-container">
				<view class="nav_left">
					<block v-for="(item, index) in clist" :key="index">
						<view :class="'nav_left_items ' + (curIndex == index ? 'active' : '')" @tap="switchRightTab" :data-index="index" :data-id="item.id">
							<view class="before" :style="{background:t('color1')}"></view>{{item.name}}
						</view>
					</block>
				</view>
				<view class="nav_right">
					<view class="nav_right-content">
						<scroll-view class="classify-box" scroll-y="true" @scrolltolower="scrolltolower">
							<view class="product-itemlist">
								<view class="item" v-for="(item,index) in datalist" :key="item.id" @click="goto" :data-url="'detail?id='+item.id">
									<image class="image" :src="item.url" mode="widthFix" v-if="item.ctype == 0" />
									<image class="image" :src="item.cover" mode="widthFix" v-else-if="item.ctype == 1"/>
								</view>
							</view>
							<nomore text="没有更多商品了" v-if="nomore"></nomore>
							<nodata text="暂无相关商品" v-if="nodata"></nodata>
							<view style="width:100%;height:100rpx"></view>
						</scroll-view>
					</view>
				</view>
			</view>
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
				opt: {},
				st: '0',
				loading: false,
				isload: false,
				pagenum: 1,
				nomore: false,
				nodata: false,
				clist: [],
				curIndex: 0,
				curIndex2: -1,
				datalist: [],
				curCid: 0,
				bid:'',
			};
		},

		onLoad: function(opt) {
			this.opt = app.getopts(opt);
			this.bid = this.opt.bid ? this.opt.bid : '';
			if(this.opt && this.opt.st){
				this.st = this.opt.st;
			}
			this.getdata();
		},
		onPullDownRefresh: function() {
			this.getdata();
		},
		methods: {
			getdata: function() {
				var that = this;
				var bid = that.opt.bid ? that.opt.bid : '';
				that.datalist = [];
				that.loading = true;
				app.get('ApiMaterial/classify', {
					bid:bid,
					st:that.st
				}, function(res) {
					that.loading = false;
					var clist = res.data;
					if(clist[that.curIndex] && clist[that.curIndex].id){
						that.curCid = clist[that.curIndex].id;
					}
					that.clist = clist;
					that.loaded();
					that.getdatalist();
				});
			},
			
			getdatalist: function(loadmore) {
				if (!loadmore) {
					this.pagenum = 1;
					this.datalist = [];
				}
				var that = this;
				var pagenum = that.pagenum;
				var bid = that.opt.bid ? that.opt.bid : '';
				
				that.loading = true;
				that.nodata = false;
				that.nomore = false;
				
				app.post('ApiMaterial/getList', {bid:bid,cid:that.curCid,pagenum:that.pagenum,st:that.st}, function(res) {
					that.loading = false;
					uni.stopPullDownRefresh();
					var data = res.data;
					if (data.length == 0) {
						if (pagenum == 1) {
							that.nodata = true;
						} else {
							that.nomore = true;
						}
					}
					var datalist = that.datalist;
					var newdata = datalist.concat(data);
					that.datalist = newdata;
				});

			},
			changetab: function(st) {
				this.st = st;
				uni.pageScrollTo({
					scrollTop: 0,
					duration: 0
				});
				this.getdata();
			},
			scrolltolower: function() {
				if (!this.nomore) {
					this.pagenum = this.pagenum + 1;
					this.getdatalist(true);
				}
			},

			//事件处理函数
			switchRightTab: function(e) {
				var that = this;
				var id = e.currentTarget.dataset.id;
				var index = parseInt(e.currentTarget.dataset.index);

				this.curIndex = index;
				this.nodata = false;
				this.curCid = id;
				this.pagenum = 1;
				this.datalist = [];
				this.nomore = false;
				this.getdatalist();
			}
		}

	};
</script>
<style>
	page{height:100%}
	.container{width:100%;height:100%;max-width:640px;background-color:#fff;color:#939393;display:flex;flex-direction:column}
	.search-container{width:100%;height:94rpx;padding:16rpx 23rpx 14rpx 23rpx;background-color:#fff;position:relative;overflow:hidden;border-bottom:1px solid #f5f5f5}
	.search-box{display:flex;align-items:center;height:60rpx;border-radius:30rpx;border:0;background-color:#f7f7f7;flex:1}
	.search-box .img{width:24rpx;height:24rpx;margin-right:10rpx;margin-left:30rpx}
	.search-box .search-text{font-size:24rpx;color:#C2C2C2;width:100%}
	.content-container{flex:1;height:100%;display:flex;overflow:hidden}
	.nav_left{width:23%;height:100%;background:#ffffff;overflow-y:scroll}
	.nav_left .nav_left_items{line-height:50rpx;color:#999999;border-bottom:0px solid #E6E6E6;font-size:24rpx;position:relative;border-right:0 solid #E6E6E6;padding:25rpx 30rpx}
	.nav_left .nav_left_items.active{background:#fff;color:#222222;font-size:28rpx;font-weight:bold}
	.nav_left .nav_left_items .before{display:none;position:absolute;top:50%;margin-top:-12rpx;left:10rpx;height:24rpx;border-radius:4rpx;width:8rpx}
	.nav_left .nav_left_items.active .before{display:block}
	.nav_right{width:77%;height:100%;display:flex;flex-direction:column;background:#f6f6f6;box-sizing:border-box;padding:15rpx 15rpx 0 15rpx}
	.nav_right-content{height:100%}
	.nav-pai{width:100%;display:flex;border-radius:10rpx;margin-bottom:10rpx;align-items:center;justify-content:center;background:#fff}
	.nav-paili{flex:1;text-align:center;color:#323232;font-size:28rpx;font-weight:bold;position:relative;height:80rpx;line-height:80rpx}
	.nav-paili .iconshangla{position:absolute;top:-4rpx;padding:0 6rpx;font-size:20rpx;color:#7D7D7D}
	.nav-paili .icondaoxu{position:absolute;top:8rpx;padding:0 6rpx;font-size:20rpx;color:#7D7D7D}
	.classify-ul{width:100%;height:100rpx;padding:0 10rpx}
	.classify-li{flex-shrink:0;display:flex;background:#F5F6F8;border-radius:22rpx;color:#6C737F;font-size:20rpx;text-align:center;height:44rpx;line-height:44rpx;padding:0 28rpx;margin:12rpx 10rpx 12rpx 0}
	.classify-box{padding:0 0 20rpx 0;width:100%;height:calc(100% - 60rpx);overflow-y:scroll;border-top:1px solid #F5F6F8}
	.classify-box .nav_right_items{width:100%;border-bottom:1px #f4f4f4 solid;padding:16rpx 0;box-sizing:border-box;position:relative}
	.product-itemlist{padding:0px;display:flex;flex-wrap:wrap}
	.product-itemlist .item{width:48%;display:inline-block;margin:4rpx;background:#fff;border-radius:10rpx;border-bottom:1px solid #F8F8F8}
	.product-itemlist .image{width:100%}
	::-webkit-scrollbar{width:0;height:0;color:transparent}
</style>