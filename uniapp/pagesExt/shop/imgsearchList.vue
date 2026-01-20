<template>
<view class="container">
	<block v-if="isload">
		<view class="search-container" :style="history_show?'height:100%;':''">
			
			<uni-drawer ref="showRight" mode="right" @change="change($event,'showRight')" :width="280">
				<view class="filter-scroll-view">
					<scroll-view class="filter-scroll-view-box" scroll-y="true">
						<view class="search-filter">
							<view class="filter-title">筛选</view>
							<view class="filter-content-title">商品分组</view>
							<view class="search-filter-content">
								<view class="filter-item" :style="catchegid==''?'color:'+t('color1')+';background:rgba('+t('color1rgb')+',0.1)':''" @tap.stop="groupClick" data-gid="">全部</view>
								<block v-for="(item, index) in glist" :key="index">
									<view class="filter-item" :style="catchegid==item.id?'color:'+t('color1')+';background:rgba('+t('color1rgb')+',0.1)':''" @tap.stop="groupClick" :data-gid="item.id">{{item.name}}</view>
								</block>
							</view>
							<block v-if="!bid || bid <=0">
							<view class="filter-content-title">商品分类</view>
							<view class="search-filter-content">
								<view class="filter-item" :style="catchecid==oldcid?'color:'+t('color1')+';background:rgba('+t('color1rgb')+',0.1)':''" @tap.stop="cateClick" :data-cid="oldcid">全部</view>
								<block v-for="(item, index) in clist" :key="index">
									<view class="filter-item" :style="catchecid==item.id?'color:'+t('color1')+';background:rgba('+t('color1rgb')+',0.1)':''" @tap.stop="cateClick" :data-cid="item.id">{{item.name}}</view>
								</block>
							</view>
							</block>
							<block v-else>
							<view class="filter-content-title">商品分类</view>
							<view class="search-filter-content">
								<view class="filter-item" :style="catchecid2==oldcid2?'color:'+t('color1')+';background:rgba('+t('color1rgb')+',0.1)':''" @tap.stop="cate2Click" :data-cid2="oldcid2">全部</view>
								<block v-for="(item, index) in clist2" :key="index">
									<view class="filter-item" :style="catchecid2==item.id?'color:'+t('color1')+';background:rgba('+t('color1rgb')+',0.1)':''" @tap.stop="cate2Click" :data-cid2="item.id">{{item.name}}</view>
								</block>
							</view>
							</block>

							<view class="search-filter-btn">
								<view class="btn" @tap="filterReset">重置</view>
								<view class="btn2" :style="{background:t('color1')}" @tap="filterConfirm">确定</view>
							</view>
						</view>
					</scroll-view>
				</view>
			</uni-drawer>

		</view>
		<view class="product-container">
			<view class="search-navbar-img" v-if="imgurl"><image :src="imgurl" mode="aspectFill" @tap="goback()"></image></view>
			<view class="search-navbar" v-show="!history_show">
				<view @tap.stop="sortClick" class="search-navbar-item" :style="(!field||field=='sort')?'color:'+t('color1'):''" data-field="sort" data-order="desc">综合</view>
				<view @tap.stop="sortClick" class="search-navbar-item" :style="field=='sales'?'color:'+t('color1'):''" data-field="sales" data-order="desc" v-if="list_sort_show.length>0 && list_sort_show.indexOf('sales') != -1">销量</view>
				<view @tap.stop="sortClick" class="search-navbar-item" :style="field=='stock'?'color:'+t('color1'):''" data-field="stock" data-order="desc" v-if="list_sort_show.length>0 && list_sort_show.indexOf('stock') != -1">现货</view><!-- 库存 更名 现货 -->
				<view @tap.stop="sortClick" class="search-navbar-item" data-field="sell_price" :data-order="order=='asc'?'desc':'asc'">
					<text :style="field=='sell_price'?'color:'+t('color1'):''">价格</text>
					<text class="iconfont iconshangla" :style="field=='sell_price'&&order=='asc'?'color:'+t('color1'):''"></text>
					<text class="iconfont icondaoxu" :style="field=='sell_price'&&order=='desc'?'color:'+t('color1'):''"></text>
				</view>
				<view class="search-navbar-item flex-x-center flex-y-center" @click.stop="showDrawer('showRight')">筛选 <text :class="'iconfont iconshaixuan ' + (showfilter?'active':'')"></text></view>
			</view>
			<block v-if="datalist && datalist.length>0">
				<dp-product-item v-if="productlisttype=='item2'" :data="datalist" :showsales="hide_sales" :showstock="hide_stock" :menuindex="menuindex"></dp-product-item>
				<dp-product-itemlist v-if="productlisttype=='itemlist'" :data="datalist" :showsales="hide_sales" :showstock="hide_stock" :menuindex="menuindex"></dp-product-itemlist>
			</block>
			<nomore text="没有更多商品了" v-if="nomore"></nomore>
			<nodata text="没有查找到相关商品" v-if="nodata"></nodata>
			<loading v-if="loading"></loading>
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
			opt:{},
			loading:false,
      isload: true,
			menuindex:-1,
			pre_url:app.globalData.pre_url,

			nomore:false,
			nodata:false,
      keyword: '',
      pagenum: 1,
      datalist: [],
      history_list: [],
      history_show: true,
      order: '',
			field:'',
      oldcid: "",
      catchecid: "",
      catchegid: "",
      cid: "",
      gid: '',
			cid2:'',
      oldcid2: "",
      catchecid2: "",
      clist: [],
      clist2: [],
      glist: [],
      productlisttype: 'item2',
      showfilter: "",
			cpid:0,
			bid:0,
			
			info:{},
			imgurl:'',
			newimgpath:'',
			imageUrls:'',
			list_sort_show:['sales'],//排序字段显示 默认显示销量
			hide_sales:0,
			hide_stock:0,
    };
  },
  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		console.log(this.opt.imgurl);
		console.log(decodeURIComponent(this.opt.imgurl));
		this.imgurl = this.opt.imgurl ? (this.opt.imgurl) : '';
		this.oldcid = this.opt.cid || '';
		this.catchecid = this.opt.cid;
		this.cid = this.opt.cid;
		this.cid2 = this.opt.cid2 || '';
		this.oldcid2 = this.opt.cid2 || '';
		this.catchecid2 = this.opt.cid2;
		this.gid = this.opt.gid;
		this.cpid = this.opt.cpid || 0;
		this.bid = this.opt.bid ? this.opt.bid : 0;
		if(this.opt.keyword) {
			this.keyword = this.opt.keyword;
		}
		//console.log(this.bid);
		if(this.cpid > 0){
			uni.setNavigationBarTitle({
				title: '可用商品列表'
			});
		}
  //   var productlisttype = app.getCache('productlisttype');
  //   if (productlisttype) this.productlisttype = productlisttype;
		// this.history_list = app.getCache('search_history_list');
		this.getdata();
  },
	onPullDownRefresh: function () {
		this.getdata();
	},
  onReachBottom: function () {
    // if (!this.nodata && !this.nomore) {
    //   this.pagenum = this.pagenum + 1;
    //   this.getprolist();
    // }
  },
  methods: {
		getdata:function(){
			var that = this;
			that.pagenum = 1;
			that.datalist = [];
			var cid = that.opt.cid;
			var gid = that.opt.gid;
			var bid = that.opt.bid ? that.opt.bid : '';
			var cid2 = that.cid2;
			that.loading = true;
			app.get('ApiShop/prolist', {cid: cid,gid: gid,bid:bid,cid2:cid2}, function (res) {
				that.loading = false;
			  that.clist = res.clist;
			  that.clist2 = res.clist2;
			  that.glist = res.glist;
				if(res.list_sort_show && res.list_sort_show.length > 0){
					that.list_sort_show = res.list_sort_show;
				}
				
				if(res.set && res.set.hide_sales){
					that.hide_sales = res.set.hide_sales;
				}
				if(res.set && res.set.hide_stock){
					that.hide_stock = res.set.hide_stock;
				}
				that.loaded();
				that.getprolist();
			});
		},
    getprolist: function () {
      var that = this;
      var pagenum = that.pagenum;
      var keyword = that.keyword;
      var order = that.order;
      var field = that.field;
      var gid = that.gid;
      var cid = that.cid;
			var cid2 = that.cid2;
      var cpid = that.cpid;
			var imgurl = that.imgurl;
      that.history_show = false;
			that.loading = true;
			that.nodata = false;
      that.nomore = false;
			var bid = that.opt.bid ? that.opt.bid : 0;			
      app.post('ApiShop/getprolistWithImg',{pagenum: pagenum,keyword: keyword,field: field,order: order,gid: gid,cid: cid,cid2:cid2,cpid:cpid,bid:bid,imgurl:imgurl,bid:bid}, function (res) {
				that.loading = false;
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
    searchChange: function (e) {
      this.keyword = e.detail.value;
      if (e.detail.value == '') {
        this.history_show = true;
        this.datalist = [];
      }
    },
    searchproduct: function () {
      var that = this;
      that.pagenum = 1;
      that.datalist = [];
      that.getprolist();
    },
    sortClick: function (e) {
      var that = this;
      var t = e.currentTarget.dataset;
      that.field = t.field;
      that.order = t.order;
      that.searchproduct();
    },
    groupClick: function (e) {
      var that = this;
      var gid = e.currentTarget.dataset.gid;
			if(gid === true) gid = '';
      that.catchegid = gid
    },
    cateClick: function (e) {
      var that = this;
      var cid = e.currentTarget.dataset.cid;
			if(cid === true) cid = '';
      that.catchecid = cid
    },
    cate2Click: function (e) {
      var that = this;
      var cid2 = e.currentTarget.dataset.cid2;
			if(cid2 === true) cid2 = '';
      that.catchecid2 = cid2
    },
		filterConfirm(){
			this.cid = this.catchecid;
			this.cid2 = this.catchecid2;
			this.gid = this.catchegid;
			this.searchproduct();
			this.$refs['showRight'].close()
		},
		filterReset(){
			this.catchecid = this.oldcid;
			this.catchecid2 = this.oldcid2;
			this.catchegid = '';
		},
    filterClick: function () {
      this.showfilter = !this.showfilter
    },
  }
};
</script>
<style>
.search-container {position: fixed;width: 100%;background: #fff;z-index:9;top:var(--window-top)}
.search-navbar-img { height: 70rpx; text-align: center;background-color: #fff; border-radius: 20rpx; border-bottom-left-radius: 0; border-bottom-right-radius: 0;}
.search-navbar-img image { border: 3px solid #fff; border-radius: 20rpx; margin-top: -30rpx;width: 100rpx;height:100rpx;}
.search-navbar {display: flex;text-align: center;align-items:center;padding:5rpx 0;
    background-color: #fff;
    margin-bottom: 20rpx;
    border-radius: 20rpx; border-top-left-radius: 0; border-top-right-radius: 0;}
.search-navbar-item {flex: 1;height: 70rpx;line-height: 70rpx;position: relative;font-size:28rpx;font-weight:bold;color:#323232}

.search-navbar-item .iconshangla{position: absolute;top:-4rpx;padding: 0 6rpx;font-size: 20rpx;color:#7D7D7D}
.search-navbar-item .icondaoxu{position: absolute;top: 8rpx;padding: 0 6rpx;font-size: 20rpx;color:#7D7D7D}
.search-navbar-item .iconshaixuan{margin-left:10rpx;font-size:22rpx;color:#7d7d7d}
.filter-scroll-view{margin-top:var(--window-top);height: 100%;}
.filter-scroll-view .filter-scroll-view-box{height:100%}
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

.product-container {width: 100%;margin-top: 100rpx;font-size:26rpx;padding:0 24rpx}
.topimg {width:96%;height:316rpx;margin: auto;margin-top: 10rpx;}
.centerimg { width: 480rpx; margin: 80rpx auto 0; height: 340rpx; border: 2px dashed #ccc; border-radius: 20rpx;background-position: center;background-repeat: no-repeat; background-size:180rpx; text-align: center;}
.centerimg image{width: 200rpx;height: 200rpx; margin-top: 40rpx;}
.centerimg .title { font-size: 32rpx;}
</style>