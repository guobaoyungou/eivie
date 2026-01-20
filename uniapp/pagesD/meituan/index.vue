<template>
	<view class="container">
		<block v-if="isload">
			<view class="search-container">
				<view class="topsearch flex-y-center">
					<view class="f1 flex-y-center">
						<image class="img" :src="pre_url+'/static/img/search_ico.png'"></image>
						<input :value="keyword" placeholder="请输入关键字搜索" placeholder-style="font-size:24rpx;color:#C2C2C2" @confirm="searchConfirm" @input="searchChange"></input>
					</view>
				</view>
				<view class="banner">
					<swiper v-if="set.pics" class="swiper"  autoplay="true" :interval="5000">
						<block v-for="(item, index) in set.pics" :key="index">
							<swiper-item class="swiper-item">
								<image :src="item.pic" mode="widthFix" @tap="goto" :data-url="item.url"></image>
							</swiper-item>
						</block>
					</swiper>
				</view>
			</view>
			<!-- 瀑布流内容 -->
			<view class="waterfalls-box" :style="{ height: height + 'px' }">
				<view v-for="(item, index) of datalist" 
				class="waterfalls-list" 
				:key="item.id" 
				:id="'waterfalls-list-id-' + item.id"
				:ref="'waterfalls-list-id-' + item.id" 
				:style="{
					'--offset': offset + 'px',
					'--cols': cols,
					top: allPositionArr[index] ? allPositionArr[index].top : 0,
					left: allPositionArr[index] ? allPositionArr[index].left : 0,
				}" 
				@click="toDetail(index)">
					<image class="waterfalls-list-image" mode="widthFix" :style="imageStyle" :src="item.spot_pic || ' '" @load="imageLoadHandle(index)" @error="imageLoadHandle(index)" />
					<view class="article-waterfall-info">
						<view class="p1">{{item.spot_name}}</view>
						<view class="p3" v-if="item.spot_tag">{{item.spot_tag}}</view>
						<view class="p2">
							<view class="p2-1">
								<view class="flex-y-center">
										<view class="t1" :style="{color:t('color1')}">
											<text style="font-size:24rpx">￥</text>{{item.sell_price}}<text style="font-size: 24rpx;">起</text>
										</view>
									</view>
							</view>
						</view>
					</view>
				</view>
			</view>
			<nomore text="没有更多商品了" v-if="nomore"></nomore>
			<nodata text="没有查找到相关商品" v-if="nodata"></nodata>
		</block>
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
				nomore: false,
				nodata: false,
				keyword: '',
				pagenum: 1,
				datalist: [],
				ty: 0, //类型
				pre_url: app.globalData.pre_url,
				// 瀑布流相关
				topArr: [], // left, right 多个时依次表示第几列的数据
				allPositionArr: [], // 保存所有的位置信息
				allHeightArr: [], // 保存所有的 height 信息
				height: 0, // 外层包裹高度
				oldNum: 0,
				num: 0,
				offset: 8, // 间距，单位为 px
				cols: 2, // 列数
				imageStyle: {}, // 图片样式
				set:{}
			};
		},
		onLoad: function (opt) {
			this.opt = app.getopts(opt);
			this.ty = this.opt.ty || 0;
			this.getdata();
		},
		onReachBottom: function () {
		  if (!this.nodata && !this.nomore) {
		    this.pagenum = this.pagenum + 1;
		    this.getprolist();
		  }
		},
		methods: {
			getdata:function(){
				var that = this;
				let arr = [];
				for (let i = 0; i < this.cols; i++) {
					arr.push(0);
				}
				that.topArr = arr;
				that.num = 0;
				that.oldNum = 0;
				that.height = 0;
				that.pagenum = 1;
				that.datalist = [];
				that.getprolist();
				that.loaded();
				let titleMap = {1: '门票',2: '酒店',3: '套餐'}
				let title = titleMap[that.ty] || '门票';
				uni.setNavigationBarTitle({
					title: title
				});
			},
			getprolist: function () {
				var that = this;
				that.loading = true;
				that.nodata = false;
				that.nomore = false;
				app.post('ApiMeituanProduct/index',{
					pagenum: that.pagenum,
					keyword: that.keyword,
					ty:that.ty
				}, function (res) {
					that.loading = false;
					var data = res.data;
					if (that.pagenum == 1) {
						that.datalist = data;
						that.set  = res.set;
						that.nodata = data.length == 0;
					} else {
						if (data.length == 0) {
							that.nomore = true;
						} else {
							that.datalist = that.datalist.concat(data);
						}
					}
				});
			},
			toDetail:function(key){
				var that = this;
				var item = that.datalist[key];
				var id = item.id;
				return app.goto('product?id='+id);
			},
			searchChange: function (e) {
			  this.keyword = e.detail.value;
			},
			searchConfirm: function (e) {
			  this.keyword = e.detail.value;
			  this.pagenum = 1;
			  this.datalist = [];
			  this.getprolist();
			},
			// 图片加载处理
			imageLoadHandle(index) {
				const id = "waterfalls-list-id-" + this.datalist[index].id;
				const query = uni.createSelectorQuery().in(this);
				setTimeout(() => {
					query.select("#" + id).fields({
						size: true
					}, (data) => {
						this.num++;
						this.$set(this.allHeightArr, index, data.height);
						if (this.num === this.datalist.length) {
							for (let i = this.oldNum; i < this.num; i++) {
								const getTopArrMsg = () => {
									let arrtmp = [...this.topArr].sort((a, b) => a - b);
									return {
										shorterIndex: this.topArr.indexOf(arrtmp[0]),
										shorterValue: arrtmp[0],
										longerIndex: this.topArr.indexOf(arrtmp[this.cols - 1]),
										longerValue: arrtmp[this.cols - 1],
									};
								};
								const {
									shorterIndex,
									shorterValue
								} = getTopArrMsg();
								const position = {
									top: shorterValue + "px",
									left: (data.width + this.offset) * shorterIndex + "px",
								};
								this.$set(this.allPositionArr, i, position);
								this.topArr[shorterIndex] =
									shorterValue + this.allHeightArr[i] + this.offset;
								this.height = getTopArrMsg().longerValue - this.offset;
							}
							this.oldNum = this.num;
							
							if (this.datalist.length === 0) {
								this.height = 100; //最小高度
							}
						}
					}).exec();
				}, 100);
			}
		}
	};
</script>

<style scoped>
	.container{padding:20rpx;box-sizing:border-box}
	.search-container{width:100%;background:#fff;z-index:9;top:0}
	.topsearch{width:100%;padding:16rpx 20rpx}
	.topsearch .f1{height:60rpx;border-radius:30rpx;border:0;background-color:#f7f7f7;flex:1}
	.topsearch .f1 .img{width:24rpx;height:24rpx;margin-left:10px}
	.topsearch .f1 input{height:100%;flex:1;padding:0 20rpx;font-size:28rpx;color:#333}
	.banner .swiper-item image{width: 100%;}
	.waterfalls-box{position:relative;width:100%;overflow:hidden;margin-top:20rpx}
	.waterfalls-box .waterfalls-list{width:calc((100% - var(--offset) * (var(--cols) - 1)) / var(--cols));position:absolute;background-color:#fff;border-radius:8rpx;left:calc(-50% - var(--offset));box-shadow:0 2rpx 10rpx rgba(0,0,0,0.1)}
	.waterfalls-box .waterfalls-list .waterfalls-list-image{width:100%;will-change:transform;border-radius:8rpx 8rpx 0 0;display:block}
	.article-waterfall-info{padding:10rpx 20rpx 20rpx 20rpx;display:flex;flex-direction:column}
	.article-waterfall-info .p1{color:#222222;font-weight:bold;font-size:28rpx;line-height:46rpx;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:2;overflow:hidden}
	.article-waterfall-info .p2{flex-grow:0;flex-shrink:0;display:flex;align-items:center;padding-top:10rpx;font-size:24rpx;color:#a88;overflow:hidden;display:flex;align-items:center;overflow:hidden;padding:2px 0;margin-top: 10rpx;}
	.article-waterfall-info .p2-1{flex-grow:1;flex-shrink:1;height:40rpx;line-height:40rpx;overflow:hidden;white-space:nowrap}
	.article-waterfall-info .p2-1 .t1{font-size:36rpx}
	.article-waterfall-info .p2-1 .t2{margin-left:10rpx;font-size:24rpx;color:#aaa;text-decoration:line-through}
	.article-waterfall-info .p2-1 .t3{margin-left:10rpx;font-size:22rpx;color:#999}
	.article-waterfall-info .p2-2{font-size:20rpx;height:40rpx;line-height:40rpx;text-align:right;padding-left:20rpx;color:#999}
	.article-waterfall-info .p3{color:#8c8c8c;font-size: 22rpx;font-weight: 400;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:2;overflow:hidden;margin-top: 10rpx;}
</style>