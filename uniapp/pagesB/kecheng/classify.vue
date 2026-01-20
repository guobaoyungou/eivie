<template>
	<view class="container">
		<block v-if="isload">
			<view class="view-show">
				<view class="content" style="height:calc(100% - 94rpx);overflow:hidden;display:flex">
					<scroll-view class="nav_left" :scrollWithAnimation="animation" scroll-y="true"
						:class="menuindex>-1?'tabbarbot':'notabbarbot'">
						<block v-for="(item, index) in data" :key="index">
							<view class="nav_left_items" :class="index===currentActiveIndex?'active':''"
								:style="{color:index===currentActiveIndex?t('color1'):'#333'}" @tap="clickRootItem"
								:data-root-item-id="item.id" :data-root-item-index="index">
								<view class="before" :style="{background:t('color1')}"></view>{{item.name}}
							</view>
						</block>
					</scroll-view>
					<view class="nav_right">
						<view class="nav_right-content">
							<scroll-view class="detail-list" @scrolltolower="scrolltolower" scroll-y="true" :show-scrollbar="false">
								<view v-for="(detail, indexs) in clist" :key="indexs"
									class="classification-detail-item">
									<view class="head" :data-id="detail.id" :id="'detail-' + detail.id">
										<view class="txt">{{detail.name}}</view>
										<view class="show-all" @tap="gotoCatproductPage" :data-id="detail.id">更多<text
												class="iconfont iconjiantou"></text></view>
									</view>
									<view class="product-itemlist">
										<view class="item" v-for="(item,index) in detail.child" :key="item.id"
											@click="goto" :data-url="'/activity/kecheng/product?id='+item.id+'&bid='+item.bid">
											<view class="product-pic">
												<image class="image" :src="item.pic" mode="widthFix" />
											</view>
											<view class="product-info">
												<view class="p1"><text>{{item.name}}</text></view>
												<view class="p2" :style="{color:t('color1')}">
													<text class="t1">
														<text v-if="item.price <= 0">免费</text>
														<text v-else>
															<text style="font-size:20rpx;padding-right:1px">￥</text>{{item.price}}
														</text>
													</text>
													<text class="t2" v-if="item.market_price > 0">￥{{item.market_price}}</text>
												</view>
											</view>
										</view>
									</view>
								</view>
								<nomore text="没有更多分类了" v-if="nodata"></nomore>
							</scroll-view>
						</view>
					</view>
				</view>
			</view>
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
				opt: {},
				loading: false,
				isload: false,
				menuindex: -1,
				data: [],
				clist: [],
				currentActiveIndex: 0,
				animation: true,
				bid: '',
				cid:'',
				nodata: false,
				last_id:0,
				last_classify_id:0,
				page_size:10
			};
		},

		onLoad: function(opt) {
			this.opt = app.getopts(opt);
			this.bid = this.opt.bid ? this.opt.bid : '';
			this.getdata();
		},
		onPullDownRefresh: function() {
			this.getdata();
		},
		methods: {
			getdata: function() {
				var that = this;
				that.loading = true;
				app.get('ApiKecheng/newcategory3', {
					bid: that.bid
				}, function(res) {
					that.loading = false;
					that.data = res.data;
					that.loaded();
					that.cid = res.data[0].id;
					that.getClassifyKecheng();
				});
			},
			getClassifyKecheng: function(isAppend = false) {
			  this.loading = true;
			  this.nodata = false;
			  const that = this;
			
			  app.get("ApiKecheng/getClassifyKecheng", {
			    id: that.cid,
			    bid: that.bid,
			    last_id: that.last_id,
			    last_classify_id: that.last_classify_id
			  }, (res) => {
			    that.loading = false;
			    const data = res.data;
			
			    if (isAppend) {
			      data.forEach((newItem) => {
			        const existingItem = that.clist.find((item) => item.id === newItem.id);
			        if (existingItem) {
			          existingItem.child = existingItem.child.concat(newItem.child);
			        } else {
			          that.clist.push(newItem);
			        }
			      });
			    } else {
			      that.clist = data;
			    }
			
			    that.page_size = res.page_size;
			
			    // 获取最后一个分类及其子项
			    const lastItem = that.clist[that.clist.length - 1];
			    if (lastItem) {
			      that.last_classify_id = lastItem.id;
			      const lastChild = lastItem.child[lastItem.child.length - 1];
			      if (lastChild) {
			        that.last_id = lastChild.id;
			      }
			    }
			
			    // 检查是否还有更多数据
			    that.nodata = res.total < that.page_size;
			  })
			},
			scrolltolower:function(){
				if (!this.nodata) {
					this.getClassifyKecheng(true)
				}
			},
			clickRootItem: function(t) {
				var e = t.currentTarget.dataset;
				this.currentActiveIndex = e.rootItemIndex;
				this.cid = e.rootItemId;
				this.last_id = 0;
				this.last_classify_id = 0;
				this.getClassifyKecheng();
			},
			gotoCatproductPage: function(t) {
				var e = t.currentTarget.dataset;
				app.goto('/activity/kecheng/list?bid='+ this.bid +'&cid=' + e.id);
			}
		}
	};
</script>
<style>
	page {
		position: relative;
		width: 100%;
		height: 100%;
	}

	.container {
		height: 100%;
		overflow: hidden
	}

	.view-show {
		background-color: white;
		line-height: 1;
		width: 100%;
		height: 100%;
	}

	.search-container {
		width: 100%;
		height: 94rpx;
		padding: 16rpx 23rpx 14rpx 23rpx;
		background-color: #fff;
		position: relative;
		overflow: hidden;
		border-bottom: 1px solid #f5f5f5
	}

	.search-box {
		display: flex;
		align-items: center;
		height: 60rpx;
		border-radius: 30rpx;
		border: 0;
		background-color: #f7f7f7;
		flex: 1
	}

	.search-box .img {
		width: 24rpx;
		height: 24rpx;
		margin-right: 10rpx;
		margin-left: 30rpx
	}

	.search-box .search-text {
		font-size: 24rpx;
		color: #C2C2C2;
		width: 100%;
	}

	.nav_left {
		width: 25%;
		height: 100%;
		background: #f6f7fb;
		overflow-y: scroll;
	}

	.nav_left .nav_left_items {
		line-height: 50rpx;
		color: #333333;
		border-bottom: 0px solid #E6E6E6;
		font-size: 28rpx;
		font-weight: bold;
		position: relative;
		border-right: 0 solid #E6E6E6;
		padding: 25rpx 30rpx;
		text-align: center;
	}

	.nav_left .nav_left_items.active {
		background-color: #fff;
		color: #333333;
		font-size: 28rpx;
		font-weight: bold;
	}

	.nav_left .nav_left_items .before {
		display: none;
		position: absolute;
		top: 50%;
		margin-top: -12rpx;
		left: 10rpx;
		height: 24rpx;
		border-radius: 4rpx;
		width: 6rpx
	}

	.nav_left .nav_left_items.active .before {
		display: block
	}

	.nav_right {
		width: 75%;
		height: 100%;
		display: flex;
		flex-direction: column;
		background: #f6f6f6;
		box-sizing: border-box;
	}

	.nav_right-content {
		background: #ffffff;
		padding: 20rpx;
		height: 100%;
		position: relative
	}

	.detail-list {
		height: 100%;
		overflow: scroll
	}

	.classification-detail-item {
		width: 100%;
		overflow: visible;
		background: #fff
	}

	.classification-detail-item .head {
		height: 82rpx;
		width: 100%;
		display: flex;
		align-items: center;
		justify-content: space-between;
	}

	.classification-detail-item .head .txt {	
		color: #222;
		font-weight: 700;
		font-size: 16px;
		white-space: nowrap; 
		overflow: hidden; 
		text-overflow: ellipsis;
		width: 85%;
	}

	.classification-detail-item .head .show-all {
		font-size: 24rpx;
		color: #949494;
		display: flex;
		align-items: center
	}

	.product-itemlist {
		height: auto;
		position: relative;
		overflow: hidden;
		padding: 0px;
		display: flex;
		flex-wrap: wrap
	}

	.product-itemlist .item {
		width: 100%;
		display: inline-block;
		position: relative;
		margin-bottom: 12rpx;
		background: #fff;
		display: flex;
		padding: 14rpx 0;
		border-radius: 10rpx;
	}

	.product-itemlist .product-pic {
		width: 65%;
		height: 0;
		overflow: hidden;
		background: #ffffff;
		padding-bottom: 30%;
		position: relative;
		border-radius: 8px;
	}

	.product-itemlist .product-pic .image {
		position: absolute;
		top: 0;
		left: 0;
		width: 100%;
		height: auto;
		object-fit: cover;
	}

	.product-itemlist .product-pic .saleimg {
		position: absolute;
		width: 120rpx;
		height: auto;
		top: -6rpx;
		left: -6rpx;
	}

	.product-itemlist .product-info {
		width: 70%;
		padding: 0 10rpx 5rpx 20rpx;
		position: relative;
	}

	.product-itemlist .product-info .p1 {
		color: #323232;
		font-weight: bold;
		font-size: 25rpx;
		line-height: 36rpx;
		margin-bottom: 0;
		display: -webkit-box;
		-webkit-box-orient: vertical;
		-webkit-line-clamp: 2;
		overflow: hidden;
		height: 72rpx;
	}

	.product-itemlist .product-info .p2 {
		position: absolute;
		align-items: center;
		overflow: hidden;
		bottom: 0;
	}

	.product-itemlist .product-info .p2 .t1 {
		height: 30rpx;
		line-height: 30rpx;
		text-align: right;
		font-size: 30rpx;
	}

	.product-itemlist .product-info .p2 .t2 {
		margin-left: 10rpx;
		font-size: 24rpx;
		color: #aaa;
		text-decoration: line-through;
	}

	::-webkit-scrollbar {
		width: 0;
		height: 0;
		color: transparent;
	}
</style>