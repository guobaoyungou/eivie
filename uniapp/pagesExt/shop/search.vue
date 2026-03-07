<template>
<view class="container">
	<block v-if="isload">
		<view class="search-container" :style="history_show?'height:100%;':''">
			<view class="topsearch flex-y-center">
				<view class="f1 flex-y-center">
					<image class="img" :src="pre_url+'/static/img/search_ico.png'"></image>
					<input :value="keyword" placeholder="搜索感兴趣的商品" placeholder-style="font-size:24rpx;color:#C2C2C2" @confirm="searchConfirm" @input="searchChange" @focus="searchFocus"></input>
					<view class="camera" v-if="set.image_search == 1" @tap="goto" :data-url="'/pagesExt/shop/imgsearch?bid='+bid" :style="'background-image:url('+pre_url+'/static/img/camera.png)'"></view>
				</view>
				<view class="search-btn" @tap="searchbtn">
					<image :src="pre_url+'/static/img/show-cascades.png'" style="height:36rpx;width:36rpx" v-if="!history_show && productlisttype=='itemlist'"/>
					<image :src="pre_url+'/static/img/show-list.png'" style="height:36rpx;width:36rpx" v-if="!history_show && productlisttype=='item2'"/>
					<text v-if="history_show">搜索</text>
				</view>
			</view>
			<view class="search-history" v-show="history_show">
				<view>
					<text class="search-history-title">最近搜索</text>
					<view class="delete-search-history" @tap="deleteSearchHistory">
						<image :src="pre_url+'/static/img/del.png'" style="width:36rpx;height:36rpx"/>
					</view>
				</view>
				<view class="search-history-list">
					<view v-for="(item, index) in history_list" :key="index" class="search-history-item" :data-value="item" @tap="historyClick">{{item}}
					</view>
					<view v-if="!history_list || history_list.length==0" class="flex-y-center"><image :src="pre_url+'/static/img/tanhao.png'" style="width:36rpx;height:36rpx;margin-right:10rpx"/>暂无记录		</view>
				</view>
				<view v-if="group_multi_select == 1" style="margin-top: 35rpx;">
					<view>
						<text class="search-history-title">商品分组</text>
					</view>
					<view class="search-history-list">
						<block v-for="(item, index) in glist" :key="index">
							<view class="search-history-item tjgroup" :style="catchegid==item.id?'color:'+t('color1')+';background:rgba('+t('color1rgb')+',0.1)':''" @tap.stop="groupClickSearch" :data-gid="item.id">{{item.name}}</view>
						</block>
					</view>
				</view>
				<view class="retract-box">
					<div class="horizontal-line"></div>
					<view class="retract" @click="history_show = false">收起<text class="iconfont iconshangla" style="color:#888;margin-left: 10rpx;"></text></view>
					<div class="horizontal-line"></div>
				</view>
			</view>
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
			<uni-drawer ref="showRight" mode="right" @change="change($event,'showRight')" :width="280">
				<scroll-view scroll-y="true" class="filter-scroll-view filter-page">
					<view class="filter-scroll-view-box">
						<view class="search-filter" style="padding-bottom: 150rpx;">
							<view class="filter-title">筛选</view>
							<view class="filter-content-title">商品分组</view>
							<!-- 商品分组多选模式 -->
							<view class="search-filter-content" v-if="group_multi_select == 1">
								<view class="filter-item" :style="catchegid.length == 0 ? 'color:'+t('color1')+';background:rgba('+t('color1rgb')+',0.1)':''" @tap.stop="groupClick" data-gid="">全部</view>
								<block v-for="(item, index) in glist" :key="index">
									<view class="filter-item" :style="catchegid.indexOf(item.id) != -1 ? 'color:'+t('color1')+';background:rgba('+t('color1rgb')+',0.1)':''" @tap.stop="groupClick" :data-gid="item.id">{{item.name}}</view>
								</block>
							</view>
							<!-- 商品分组单选 -->
							<view class="search-filter-content" v-else>
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
								<view class="btn btn-class" @tap="filterReset">重置</view>
								<view class="btn2 btn-class" :style="{background:t('color1')}" @tap="filterConfirm">确定</view>
							</view>
						</view>
					</view>
				</scroll-view>
			</uni-drawer>
			
		</view>
		<view class="product-container">
			<block v-if="datalist && datalist.length>0">
				<dp-product-item v-if="productlisttype=='item2'" :data="datalist" :menuindex="menuindex" :showsales="hide_sales" :showstock="hide_stock"></dp-product-item>
				<dp-product-itemlist v-if="productlisttype=='itemlist'" :data="datalist" :menuindex="menuindex" :showsales="hide_sales" :showstock="hide_stock"></dp-product-itemlist>
			</block>
			<nomore text="没有更多商品了" v-if="nomore"></nomore>
			<nodata text="没有查找到相关商品" v-if="nodata"></nodata>
			<loading v-if="loading"></loading>
		</view>
	</block>
	<loading v-if="loading"></loading>
	<dp-tabbar :opt="opt" @getmenuindex="getmenuindex"></dp-tabbar>
	<popmsg ref="popmsg"></popmsg>
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
			set:{},
			mendianid:0,
			latitude:'',
			longitude:'',
			hide_sales:0,
			hide_stock:0,
			group_multi_select:0,//商品分组多选
			list_sort_show:['sales'], //排序字段显示 默认显示销量
			area:''
    };
  },
  onLoad: function (opt) {
		this.opt = app.getopts(opt);
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
		//读全局缓存的地区信息
		var locationCache =  app.getLocationCache();
		if(locationCache){
			if(locationCache.latitude){
				this.latitude = locationCache.latitude
				this.longitude = locationCache.longitude
			}
			if(locationCache.area){
				this.area = locationCache.area
			}
			if(locationCache.mendian_id){
				this.mendianid = locationCache.mendian_id
			}
		}
    var productlisttype = app.getCache('productlisttype');
    if (productlisttype) this.productlisttype = productlisttype;
		this.history_list = app.getCache('search_history_list');
		this.getdata();
  },
	onPullDownRefresh: function () {
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
			that.pagenum = 1;
			that.datalist = [];
			var cid = that.opt.cid;
			var gid = that.opt.gid;
			var bid = that.opt.bid ? that.opt.bid : '';
			var cid2 = that.cid2;
			that.loading = true;
			app.get('ApiShop/prolist', {cid: cid,gid: gid,bid:bid,cid2:cid2,mendian_id:that.mendianid,latitude:that.latitude,longitude:that.longitude,area:that.area}, function (res) {
				that.loading = false;
			  that.clist = res.clist;
			  that.clist2 = res.clist2;
			  that.glist = res.glist;
				that.set = res.set;
				that.loaded();
				that.getprolist();
				if(res.set && res.set.group_multi_select){
					//商品分组多选
					if(res.set.group_multi_select == 1){
						that.catchegid = [];
						that.group_multi_select = res.set.group_multi_select;
					}
				}
				if(res.list_sort_show && res.list_sort_show.length > 0){
					that.list_sort_show = res.list_sort_show;
				}
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
      that.history_show = false;
			that.loading = true;
			that.nodata = false;
      that.nomore = false;
			var bid = that.opt.bid ? that.opt.bid : '';
      app.post('ApiShop/getprolist',{pagenum: pagenum,keyword: keyword,field: field,order: order,gid: gid,cid: cid,cid2:cid2,cpid:cpid,bid:bid,mendian_id:that.mendianid,latitude:that.latitude,longitude:that.longitude,area:that.area}, function (res) {
				that.loading = false;
        var data = res.data;
				if(res.shopset && res.shopset.hide_sales){
					that.hide_sales = res.shopset.hide_sales;
				}
				if(res.shopset && res.shopset.hide_stock){
					that.hide_stock = res.shopset.hide_stock;
				}
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
		searchFocus: function (e) {
      this.history_show = true;
    },
    searchbtn: function () {
      var that = this;
      if (that.history_show) {
        var keyword = that.keyword;
        that.searchproduct();
      } else {
        if (that.productlisttype == 'itemlist') {
          that.productlisttype = 'item2';
          app.setCache('productlisttype', 'item2');
        } else {
          that.productlisttype = 'itemlist';
          app.setCache('productlisttype', 'itemlist');
        }
      }
    },
    searchConfirm: function (e) {
      var that = this;
      var keyword = e.detail.value;
      that.keyword = keyword
      that.searchproduct();
    },
    searchproduct: function () {
      var that = this;
      that.pagenum = 1;
      that.datalist = [];
      that.addHistory();
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
      //允许多选
      if (that.group_multi_select == 1) {
      	//全部
      	if(gid == ''){
      		that.catchegid = [];
      		return;
      	}
      	
      	//检查gid是否已存在于catchegid中
      	var index = that.catchegid.indexOf(gid);
      	
        //如果gid已存在，则移除
        if (index > -1) {
          that.catchegid.splice(index, 1);
        } else {
          //如果gid不存在，则添加
          that.catchegid.push(gid);
        }
      } else {
        //如果不是多选，则直接设置catchegid为gid
        that.catchegid = gid;
      }
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
    addHistory: function () {
      var that = this;
      var keyword = that.keyword;
      if (app.isNull(keyword)) return;
      var historylist = app.getCache('search_history_list');
      if (app.isNull(historylist)) historylist = [];
      historylist.unshift(keyword);
      var newhistorylist = [];
      for (var i in historylist) {
        if (historylist[i] != keyword || i == 0) {
          newhistorylist.push(historylist[i]);
        }
      }
      if (newhistorylist.length > 5) newhistorylist.splice(5, 1);
      app.setCache('search_history_list', newhistorylist);
      that.history_list = newhistorylist
    },
    historyClick: function (e){
      var that = this;
      var keyword = e.currentTarget.dataset.value;
      if (keyword.length == 0) return;
      that.keyword = keyword;
      that.searchproduct();
    },
    deleteSearchHistory: function () {
      var that = this;
      that.history_list = null;
      app.removeCache("search_history_list");
    },
		groupClickSearch:function(e){
			var that = this;
			that.gid = e.currentTarget.dataset.gid;
			this.searchproduct();
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
.topsearch .f1 .camera {height:72rpx;width:72rpx;color: #666;border: 0px;padding: 0px;margin: 0px;background-position: center;background-repeat: no-repeat; background-size:40rpx;}
.topsearch .search-btn{display:flex;align-items:center;color:#5a5a5a;font-size:30rpx;width:60rpx;text-align:center;margin-left:20rpx}
.search-navbar {display: flex;text-align: center;align-items:center;padding:5rpx 0}
.search-navbar-item {flex: 1;height: 70rpx;line-height: 70rpx;position: relative;font-size:28rpx;font-weight:bold;color:#323232}

.search-navbar-item .iconshangla{position: absolute;top:-4rpx;padding: 0 6rpx;font-size: 20rpx;color:#7D7D7D}
.search-navbar-item .icondaoxu{position: absolute;top: 8rpx;padding: 0 6rpx;font-size: 20rpx;color:#7D7D7D}
.search-navbar-item .iconshaixuan{margin-left:10rpx;font-size:22rpx;color:#7d7d7d}
.search-history {padding: 24rpx 34rpx;}
.search-history .search-history-title {color: #666;}
.search-history .delete-search-history {float: right;padding: 15rpx 20rpx;margin-top: -15rpx;}
.search-history-list {padding: 24rpx 0 0 0;}
.search-history-list .search-history-item {display: inline-block;height: 50rpx;line-height: 50rpx;padding: 0 20rpx;margin: 0 10rpx 10rpx 0;background: #ddd;border-radius: 10rpx;font-size: 26rpx;}

.filter-page{height: 100%;width: 99%;}
.filter-scroll-view{margin-top:var(--window-top)}
.search-filter{display: flex;flex-direction: column;text-align: left;width:100%;flex-wrap:wrap;padding:0;}
.filter-content-title{color:#999;font-size:28rpx;height:30rpx;line-height:30rpx;padding:0 30rpx;margin-top:30rpx;margin-bottom:10rpx}
.filter-title{color:#BBBBBB;font-size:32rpx;background:#F8F8F8;padding:40rpx 20rpx;}
.search-filter-content{display: flex;flex-wrap:wrap;padding:10rpx 20rpx;}
.search-filter-content .filter-item{background:#F4F4F4;border-radius:28rpx;color:#2B2B2B;font-weight:bold;margin:10rpx 10rpx;min-width:140rpx;height:56rpx;line-height:56rpx;text-align:center;font-size: 24rpx;padding:0 30rpx}
.search-filter-content .close{text-align: right;font-size:24rpx;color:#ff4544;width:100%;padding-right:20rpx}
.search-filter button .icon{margin-top:6rpx;height:54rpx;}
.search-filter-btn{display:flex;padding:50rpx 20rpx;justify-content: space-between;}
.search-filter-btn .btn-class{width: 48%;height:66rpx;line-height:66rpx;border-radius:33rpx;font-weight:bold;font-size:24rpx;text-align:center;}
.search-filter-btn .btn{background:#fff;border:1px solid #e5e5e5;color:#2B2B2B;}
.search-filter-btn .btn2{color:#fff;}

.product-container {width: 100%;margin-top: 190rpx;font-size:26rpx;padding:0 24rpx}
.search-history-list .tjgroup{background: #F4F4F4;}
.retract-box{margin-top: 50rpx;display: flex;align-items: center;justify-items: center;}
.retract{width: 150rpx;background: #F4F4F4;padding: 10rpx 20rpx;border-radius: 20px;text-align: center; margin: 0 auto;}
.horizontal-line{width: 35%; height: 1px; background-color: #e6e4e4;}
</style>