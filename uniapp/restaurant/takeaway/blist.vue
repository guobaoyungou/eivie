<template>
<view class="container">
	<block v-if="isload">
		<view class="search-container">
			<view class="topsearch flex-y-center">
				<view class="f1 flex-y-center">
					<image class="img" :src="pre_url+'/static/img/search_ico.png'"></image>
					<input :value="keyword" placeholder="搜索你感兴趣的商家" placeholder-style="font-size:24rpx;color:#C2C2C2" confirm-type="search" @confirm="search"></input>
				</view>
			</view>
			<view class="search-navbar">
				<view @tap.stop="sortClick" class="search-navbar-item" :style="field=='juli'?'color:'+t('color1'):''" data-field="juli" data-order="asc">距离最近</view>
				<view @tap.stop="sortClick" class="search-navbar-item" :style="field=='comment_score'?'color:'+t('color1'):''" data-field="comment_score" data-order="desc">评分排序</view>
				<view @tap.stop="sortClick" class="search-navbar-item" data-field="sales" :data-order="order=='asc'?'desc':'asc'">
					<text :style="field=='sales'?'color:'+t('color1'):''">销量排序</text>
					<text class="iconfont iconshangla" :style="field=='sales'&&order=='asc'?'color:'+t('color1'):''"></text>
					<text class="iconfont icondaoxu" :style="field=='sales'&&order=='desc'?'color:'+t('color1'):''"></text>
				</view>
				<view class="search-navbar-item flex-x-center flex-y-center" @click.stop="showDrawer('showRight')">筛选 <text :class="'iconfont iconshaixuan ' + (showfilter?'active':'')"></text></view>
			</view>
		</view>
		<uni-drawer ref="showRight" mode="right" @change="change($event,'showRight')" :width="280">
			<view class="filter-scroll-view">
				<scroll-view class="filter-scroll-view-box" scroll-y="true">
					<view class="search-filter">
						<view class="filter-title">筛选</view>
						<view class="filter-content-title">商家分类</view>
						<view class="search-filter-content">
							<view class="filter-item" :style="catchecid==oldcid?'color:'+t('color1')+';background:rgba('+t('color1rgb')+',0.1)':''" @tap.stop="cateClick" :data-cid="oldcid">全部</view>
							<block v-for="(item, index) in clist" :key="index">
								<view class="filter-item" :style="catchecid==item.id?'color:'+t('color1')+';background:rgba('+t('color1rgb')+',0.1)':''" @tap.stop="cateClick" :data-cid="item.id">{{item.name}}</view>
							</block>
						</view>
						<view class="search-filter-btn">
							<view class="btn" @tap="filterReset">重置</view>
							<view class="btn2" :style="{background:t('color1')}" @tap="filterConfirm">确定</view>
						</view>
					</view>
				</scroll-view>
			</view>
		</uni-drawer>
		
		<view class="ind_business">
			<view class="ind_buslist" id="datalist">
				<block v-for="(item, index) in datalist" :key="index">
				<view @tap="goto" :data-url="'index?bid=' + item.id">
					<view class="ind_busbox flex1 flex-row">
						<view class="ind_buspic flex0"><image :src="item.logo"></image></view>
						<view>
							<view class="bus_title">{{item.name}}</view>
							<view class="bus_score">
								<image class="img" v-for="(item2,index2) in [0,1,2,3,4]" :key="index2"  :src="pre_url+'/static/img/star' + (item.comment_score>item2?'2native':'') + '.png'"/>
								<view class="txt">{{item.comment_score}}分</view>
								<view class="bus_sales">销量：{{item.sales}}</view>
							</view>
							<view class="bus_address"><text class="x1">{{item.address}}</text><text class="x2">{{item.juli}}</text></view>
							<view class="prolist" v-if="(item.prolist).length > 0">
								<view v-for="(item2, index2) in item.prolist" :key="index2" class="product" @tap.stop="goto" :data-url="'product?id=' + item2.id">
									<image class="f1" :src="item2.pic"></image>
									<view class="f2">￥{{item2.sell_price}}</view>
								</view>
							</view>
						</view>
					</view>
				</view>
				</block>
				<nomore v-if="nomore"></nomore>
				<nodata v-if="nodata"></nodata>
			</view>
		</view>
	</block>
	<loading v-if="loading"></loading>
	<dp-tabbar :opt="opt"></dp-tabbar>
	<wxxieyi></wxxieyi>
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
      field: 'juli',
			order:'asc',
      oldcid: "",
      catchecid: "",
      longitude: '',
      latitude: '',
			clist:[],
      datalist: [],
      pagenum: 1,
      keyword: '',
      cid: '',
      nomore: false,
      nodata: false,
      types: "",
      showfilter: "",
    };
  },

  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.oldcid = this.opt.cid;
		this.catchecid = this.opt.cid;
		this.cid = this.opt.cid;
		this.getdata();
  },
	onPullDownRefresh: function () {
		this.getdata();
	},
  onReachBottom: function () {
    if (!this.nodata && !this.nomore) {
      this.pagenum = this.pagenum + 1;
      this.getDataList(true);
    }
  },
  methods: {
		getdata: function () {
			var that = this;
			that.loading = true;
			app.get('ApiRestaurantTakeaway/blist', {}, function (res) {
				that.loading = false;
				that.clist = res.clist;
				that.loaded();
			});
			app.getLocation(function (res) {
				var latitude = res.latitude;
				var longitude = res.longitude;
				that.longitude = longitude;
				that.latitude = latitude;
				that.getDataList();
			},
			function () {
				that.getDataList();
			});
		},
    getDataList: function (loadmore) {
			if(!loadmore){
				this.pagenum = 1;
				this.datalist = [];
			}
      var that = this;
      var pagenum = that.pagenum;
      var latitude = that.latitude;
      var longitude = that.longitude;
      var keyword = that.keyword;
			that.loading = true;
			that.nodata = false;
			that.nomore = false;
      app.post('ApiRestaurantTakeaway/blist', {pagenum: pagenum,cid: that.cid,field: that.field,order: that.order,longitude: longitude,latitude: latitude,keyword: keyword}, function (res) {
        that.loading = false;
				uni.stopPullDownRefresh();
        var data = res.data;
        if (pagenum == 1) {
          that.datalist = data;
          if (data.length == 0) {
            that.nodata = true;
          }
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
		// 打开窗口
		showDrawer(e) {
			console.log(e)
			this.$refs[e].open()
		},
		// 关闭窗口
		closeDrawer(e) {
			this.$refs[e].close()
		},
		// 抽屉状态发生变化触发
		change(e, type) {
			console.log((type === 'showLeft' ? '左窗口' : '右窗口') + (e ? '打开' : '关闭'));
			this[type] = e
		},
    cateClick: function (e) {
      var that = this;
      var cid = e.currentTarget.dataset.cid;
      that.catchecid = cid
    },
		filterConfirm(){
			this.cid = this.catchecid;
			this.gid = this.catchegid;
			this.getDataList();
			this.$refs['showRight'].close()
		},
		filterReset(){
			this.catchecid = this.oldcid;
			this.catchegid = '';
		},
    filterClick: function () {
      this.showfilter = !this.showfilter
    },
    changetab: function (e) {
      var that = this;
      var cid = e.currentTarget.dataset.cid;
      that.cid = cid
      that.pagenum = 1;
      that.datalist = [];
      that.getDataList();
    },
    search: function (e) {
      var that = this;
      var keyword = e.detail.value;
      that.keyword = keyword;
			that.pagenum = 1;
      that.datalist = [];
      that.getDataList();
    },
    sortClick: function (e) {
      var that = this;
      var t = e.currentTarget.dataset;
      that.field = t.field;
      that.order = t.order;
      that.getDataList();
    },
    filterClick: function (e) {
      var that = this;
      var types = e.currentTarget.dataset.types;
      that.types = types;
    }
  }
};
</script>
<style>
.search-container {position: fixed;width: 100%;background: #fff;z-index:9;top:var(--window-top)}
.topsearch{width:100%;padding:16rpx 20rpx;}
.topsearch .f1{height:60rpx;border-radius:30rpx;border:0;background-color:#f7f7f7;flex:1}
.topsearch .f1 .img{width:24rpx;height:24rpx;margin-left:10px}
.topsearch .f1 input{height:100%;flex:1;padding:0 20rpx;font-size:28rpx;color:#333;}
.topsearch .search-btn{display:flex;align-items:center;color:#5a5a5a;font-size:30rpx;width:60rpx;text-align:center;margin-left:20rpx}
.search-navbar {display: flex;text-align: center;align-items:center;padding:5rpx 0}
.search-navbar-item {flex: 1;height: 70rpx;line-height: 70rpx;position: relative;font-size:28rpx;font-weight:bold;color:#323232}
.search-navbar-item .iconshangla{position: absolute;top:-4rpx;padding: 0 6rpx;font-size: 20rpx;color:#7D7D7D}
.search-navbar-item .icondaoxu{position: absolute;top: 8rpx;padding: 0 6rpx;font-size: 20rpx;color:#7D7D7D}
.search-navbar-item .iconshaixuan{margin-left:10rpx;font-size:22rpx;color:#7d7d7d}

.filter-scroll-view{margin-top:var(--window-top)}
.search-filter{display: flex;flex-direction: column;text-align: left;width:100%;flex-wrap:wrap;padding:0;}
.filter-content-title{color:#999;font-size:28rpx;height:30rpx;line-height:30rpx;padding:0 30rpx;margin-top:30rpx;margin-bottom:10rpx}
.filter-title{color:#BBBBBB;font-size:32rpx;background:#F8F8F8;padding:60rpx 0 30rpx 20rpx;}
.search-filter-content{display: flex;flex-wrap:wrap;padding:10rpx 20rpx;}
.search-filter-content .filter-item{background:#F4F4F4;border-radius:28rpx;color:#2B2B2B;font-weight:bold;margin:10rpx 10rpx;min-width:140rpx;height:56rpx;line-height:56rpx;text-align:center;font-size: 24rpx;padding:0 30rpx}
.search-filter-content .close{text-align: right;font-size:24rpx;color:#ff4544;width:100%;padding-right:20rpx}
.search-filter button .icon{margin-top:6rpx;height:54rpx;}
.search-filter-btn{display:flex;padding:30rpx 30rpx;justify-content: space-between}
.search-filter-btn .btn{width:240rpx;height:66rpx;line-height:66rpx;background:#fff;border:1px solid #e5e5e5;border-radius:33rpx;color:#2B2B2B;font-weight:bold;font-size:24rpx;text-align:center}
.search-filter-btn .btn2{width:240rpx;height:66rpx;line-height:66rpx;border-radius:33rpx;color:#fff;font-weight:bold;font-size:24rpx;text-align:center}

.ind_business {width: 100%;margin-top: 190rpx;font-size:26rpx;padding:0 24rpx}
.ind_business .ind_busbox{ background: #fff;padding:20rpx;overflow: hidden; margin-bottom:20rpx;border-radius:8rpx;position:relative}
.ind_business .ind_buspic{ width:120rpx;height:120rpx; margin-right: 28rpx; }
.ind_business .ind_buspic image{ width: 100%;height:100%;border-radius: 8rpx;object-fit: cover;}
.ind_business .bus_title{ font-size: 28rpx; color: #222;font-weight:bold;line-height: 40rpx;width:500rpx;white-space: nowrap;overflow: hidden;text-overflow: ellipsis;}
.ind_business .bus_score{font-size: 24rpx;color:#FC5648;display:flex;align-items:center}
.ind_business .bus_score .img{width:24rpx;height:24rpx;margin-left:10rpx}
.ind_business .bus_score .txt{margin-left:20rpx}
.ind_business .indsale_box{ display: flex}
.ind_business .bus_sales{ font-size: 24rpx; color:#999;margin-left: 20rpx;}

.ind_business .bus_address{color:#999;font-size: 24rpx;height:30rpx;line-height: 30rpx;overflow:hidden;margin-top:6rpx}
.ind_business .bus_address .x2{padding-left:20rpx}
.ind_business .prolist{width:100%;margin-top:16rpx;overflow:hidden;display:flex;}
.ind_business .prolist .product{width:108rpx;overflow:hidden;display:flex;flex-direction:column;align-items:center;margin-right:24rpx}
.ind_business .prolist .product .f1{width:108rpx;height:108rpx;border-radius:8rpx;background:#f6f6f6}
.ind_business .prolist .product .f2{font-size:22rpx;color:#FC5648;font-weight:bold}
</style>