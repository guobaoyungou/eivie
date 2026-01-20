<template>
<view class="container">
	<block v-if="isload">
		<view class="search-container" :style="history_show?'height:100%;':''">
			<view class="topsearch flex-y-center">
				<view class="f1 flex-y-center">
					<image class="img" :src="pre_url+'/static/img/search_ico.png'"></image>
					<input :value="keyword" placeholder="搜索感兴趣的商品" placeholder-style="font-size:24rpx;color:#C2C2C2" @confirm="searchConfirm" @input="searchChange"></input>
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
			</view>
			<view class="search-navbar" v-show="!history_show">
				<view @tap.stop="sortClick" class="search-navbar-item" :style="(!field||field=='sort')?'color:'+t('color1'):''" data-field="sort" data-order="desc">综合</view>
				<view @tap.stop="sortClick" class="search-navbar-item" :style="field=='sales'?'color:'+t('color1'):''" data-field="sales" data-order="desc">销量</view>
				<view @tap.stop="sortClick" class="search-navbar-item" data-field="sell_price" :data-order="order=='asc'?'desc':'asc'">
					<text :style="field=='sell_price'?'color:'+t('color1'):''">价格</text>
					<text class="iconfont iconshangla" :style="field=='sell_price'&&order=='asc'?'color:'+t('color1'):''"></text>
					<text class="iconfont icondaoxu" :style="field=='sell_price'&&order=='desc'?'color:'+t('color1'):''"></text>
				</view>
				<view class="search-navbar-item flex-x-center flex-y-center" @click.stop="showDrawer('showRight')">筛选 <text :class="'iconfont iconshaixuan ' + (showfilter?'active':'')"></text></view>
			</view>
			<uni-drawer ref="showRight" mode="right" @change="change($event,'showRight')" :width="280">
				<view class="filter-scroll-view" style="max-height:100%;padding-bottom:110rpx;overflow:hidden auto">
					<view class="filter-scroll-view-box">
						<view class="search-filter">
							<view class="filter-title">筛选</view>
							
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
							<block v-for="(item, index) in paramlist">
							<view class="filter-content-title">{{item.name}}</view>
							<view class="search-filter-content">
								<view class="filter-item" :style="catcheparams[item.name]==''?'color:'+t('color1')+';background:rgba('+t('color1rgb')+',0.1)':''" @tap.stop="paramClick" :data-paramkey="item.name" data-paramval="">全部</view>
								<block v-for="(item2, index) in item.params" :key="index">
									<view class="filter-item" :style="catcheparams[item.name]==item2?'color:'+t('color1')+';background:rgba('+t('color1rgb')+',0.1)':''" @tap.stop="paramClick" :data-paramkey="item.name" :data-paramval="item2">{{item2}}</view>
								</block>
							</view>
							</block>

							<view class="search-filter-btn">
								<view class="btn" @tap="filterReset">重置</view>
								<view class="btn2" :style="{background:t('color1')}" @tap="filterConfirm">确定</view>
							</view>
						</view>
					</view>
				</view>
			</uni-drawer>

			
		</view>
		<view class="product-container">
			<block v-if="datalist && datalist.length>0">
				<dp-cycle-item v-if="productlisttype=='item2'" :data="datalist" :menuindex="menuindex"></dp-cycle-item>
				<dp-cycle-itemlist v-if="productlisttype=='itemlist'" :data="datalist" :menuindex="menuindex"></dp-cycle-itemlist>
			</block>
			<nomore text="没有更多商品了" v-if="nomore"></nomore>
			<nodata text="没有查找到相关商品" v-if="nodata"></nodata>
			<loading v-if="loading"></loading>
		</view>
	</block>
	<view style="display:none">{{test}}</view>
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

			nomore:false,
			nodata:false,
      keyword: '',
      pagenum: 1,
      datalist: [],
      history_list: [],
      history_show: false,
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
			paramlist:[],
			catcheparams:{},
			proparams:{},
      productlisttype: 'item2',
      showfilter: "",
			cpid:0,
			bid:0,
			latitude: '',
			longitude: '',
			test:'',
			pre_url:app.globalData.pre_url,
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
		//console.log(this.bid);
		if(this.cpid > 0){
			uni.setNavigationBarTitle({
				title: '可用商品列表'
			});
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
			app.get('ApiCycle/prolist', {cid: cid,gid: gid,bid:bid,cid2:cid2}, function (res) {
				that.loading = false;
			  that.clist = res.clist;
			  that.clist2 = res.clist2;
			  that.glist = res.glist;
				that.loaded();
				if (that.latitude == '' && that.longitude == '' && res.needlocation) {
					app.getLocation(function(res) {
						that.latitude = res.latitude;
						that.longitude = res.longitude;
						that.getprolist();
					},function(){
						that.getprolist();
					});
				}else{
					that.getprolist();
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
      app.post('ApiCycle/getprolist',{pagenum: pagenum,keyword: keyword,field: field,order: order,gid: gid,cid: cid,cid2:cid2,cpid:cpid,bid:bid,latitude: that.latitude,longitude: that.longitude,proparams:that.proparams}, function (res) {
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
		
		paramClick:function(e){
			console.log(e)
			var paramkey = e.currentTarget.dataset.paramkey;
			var paramval = e.currentTarget.dataset.paramval;
			this.catcheparams[paramkey] = paramval;
			this.test = Math.random();
			console.log(this.catcheparams);
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
      that.catchegid = gid
    },
    cateClick: function (e) {
      var that = this;
      var cid = e.currentTarget.dataset.cid;
			if(cid === true) cid = '';
      that.catchecid = cid;
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
			this.proparams = this.catcheparams;
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
.search-history {padding: 24rpx 34rpx;}
.search-history .search-history-title {color: #666;}
.search-history .delete-search-history {float: right;padding: 15rpx 20rpx;margin-top: -15rpx;}
.search-history-list {padding: 24rpx 0 0 0;}
.search-history-list .search-history-item {display: inline-block;height: 50rpx;line-height: 50rpx;padding: 0 20rpx;margin: 0 10rpx 10rpx 0;background: #ddd;border-radius: 10rpx;font-size: 26rpx;}

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

.product-container {width: 100%;margin-top: 190rpx;font-size:26rpx;padding:0 24rpx}



</style>