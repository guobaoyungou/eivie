<template>
<view class="container">
	<block v-if="isload">
		<view class="search-container" :style="history_show?'height:100%;':''">
			<view class="topsearch flex-y-center">
				<view class="f1 flex-y-center">
					<image :src="pre_url+'/static/img/search_ico.png'"></image>
					<input :value="keyword" placeholder="商品搜索..." @confirm="searchConfirm" @input="searchChange"/>
				</view>
			</view>
			<view class="search-history" v-show="history_show">
				<view>
					<text class="search-history-title">最近搜索</text>
					<view class="delete-search-history" @tap="deleteSearchHistory">
						<text class="fa fa-trash-o" style="font-size:36rpx"></text>
					</view>
				</view>
				<view class="search-history-list">
					<view v-for="(item, index) in history_list" :key="index" class="search-history-item" :data-value="item" @tap="historyClick">{{item}}
					</view>
					<view v-if="!history_list || history_list.length==0"><text class="fa fa-exclamation-circle"></text> 暂无记录		</view>
				</view>
			</view>
			<view class="search-navbar" v-show="!history_show">
				<view @tap.stop="sortClick" :class="'search-navbar-item ' + (!field?'active':'')" data-field="" data-order="">综合</view>
				<view @tap.stop="sortClick" :class="'search-navbar-item ' + (field=='sales'?'active':'')" data-field="sales" data-order="desc">兑换数</view>
				<view @tap.stop="sortClick" class="search-navbar-item" data-field="score_price" :data-order="order=='asc'?'desc':'asc'">
					<text :class="field=='score_price'?'active':''">所需{{t('积分')}}</text>
					<text :class="'fa fa-caret-up ' + (field=='score_price'&&order=='asc'?'active':'')"></text>
					<text :class="'fa fa-caret-down ' + (field=='score_price'&&order=='desc'?'active':'')"></text>
				</view>
				<view class="search-navbar-item flex-x-center flex-y-center" @tap.stop="filterClick">筛选<text class="iconfont iconshaixuan" style="font-size: 28rpx;margin-left: 5rpx;"></text></view>
			</view>
			<view class="search-filter" v-if="showfilter">
				<view class="search-filter-content" :if="glist && glist.length >0">
					<block v-for="(item, index) in glist" :key="index">
						<view @tap.stop="groupClick" :data-gid="item.id" style="display: flex;align-items: center;justify-content: center;padding: 15rpx 30rpx;">
							<icon type="success_no_circle" size="18" v-if="gid == item.id"></icon>{{item.name}}
						</view>
					</block>
				</view>
				<view class="search-filter-content" :if="clist && clist.length >0" style="border-top:1px solid #f5f5f5">
					<block v-for="(item, index) in clist" :key="index">
						<view @tap.stop="cateClick" :data-cid="item.id" style="display: flex;align-items: center;justify-content: center;padding: 15rpx 30rpx;">
							<icon type="success_no_circle" size="18" v-if="cid == item.id"></icon>{{item.name}}
						</view>
					</block>
				</view>
				<view class="search-filter-content" style="border-top:1px solid #eee;text-align:right"><div class="close"><text @tap.stop="filterClick">关闭</text></div></view>
			</view>
		</view>
		<view class="product-container">
			<block v-if="datalist && datalist.length>0">
				<block v-for="(item, index) in datalist" :key="index">
				<view @tap="goto" :data-url="'product?id=' + item.id" class="product-item">
					<view class="itemcontent">
						<view class="product-pic">
							<image :src="item.pic"></image>
						</view> 
						<view class="product-info">
							<view class="p1">{{item.name}}</view>
							<view class="p2"><block v-if="item.sell_price>0">价值{{item.sell_price}}元</block></view>
							<view class="p3">
								<view class="t1 flex">
									<view class="x1" :style="{color:t('color1')}">
										<text style="font-size:13px">
												<block v-if="item.score_price>0">{{item.score_price}}{{t('积分')}}</block>
												 <block v-if="item.money_price>0"><block v-if="item.score_price>0">+</block>{{item.money_price}}元</block>
											 </text>
									</view>
								</view>
							</view>
						</view>
					</view>
					<view class="itembottom">
						<view class="f1">已兑换<text :style="{color:t('color1')}"> {{item.sales}} </text>件</view>
						<button class="f2" :style="{background:t('color1')}">立即兑换</button>
					</view>
				</view>
				</block>
			</block>
			<nomore text="没有更多商品了" v-if="nomore"></nomore>
			<nodata text="没有查找到相关商品" v-if="nodata"></nodata>
			<loading v-if="loading"></loading>
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
			opt:{},
			loading:false,
      isload: false,
			menuindex:-1,
			
			textset:{},
			nomore:false,
			nodata:false,
      keyword: '',
      pagenum: 1,
      datalist: [],
      history_list: [],
      history_show: false,
      order: '',
			field:'',
      cid: "",
      clist: [],
      glist: [],
      productlisttype: 'item2',
      showfilter: "",
			cpid:0,
			bid:0,
      pre_url:app.globalData.pre_url,
    };
  },
  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.cid = this.opt.cid;
		this.keyword = this.opt.keyword;
		this.bid = this.opt.bid || 0;
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
			that.loading = true;
			app.get('ApiScoreshop/prolist', {bid:that.bid,cid: cid,gid: gid}, function (res) {
				that.loading = false;
			  that.clist = res.clist;
			  that.textset = app.globalData.textset;
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
      var cpid = that.cpid;
      that.history_show = false;
			that.loading = true;
			that.nodata = false;
      that.nomore = false;
      app.post('ApiScoreshop/getprolist',{bid:that.bid,pagenum: pagenum,keyword: keyword,field: field,order: order,gid: gid,cid: cid,cpid:cpid}, function (res) {
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
      if (gid == this.gid) {
        that.gid = '';
      } else {
        that.gid = gid
      }
      that.searchproduct();
    },
    cateClick: function (e) {
      var that = this;
      var cid = e.currentTarget.dataset.cid;
      if (cid == this.cid) {
        that.cid = '';
      } else {
        that.cid = cid;
      }
      that.searchproduct();
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
.topsearch{width:100%;padding:10rpx 20rpx;}
.topsearch .f1{height:70rpx;border-radius:35rpx;border:0;background-color:#f2f2f2;flex:1}
.topsearch .f1 image{width:30rpx;height:30rpx;margin-left:10px}
.topsearch .f1 input{height:100%;flex:1;padding:0 20rpx;font-size:28rpx;color:#333;}
.search-btn{color:#5a5a5a;font-size:30rpx;width:60rpx;text-align:center;margin-left:20rpx}
.search-navbar {display: flex;text-align: center;align-items:center;padding:4rpx 0}
.search-navbar-item {flex: 1;height: 60rpx;line-height: 60rpx;position: relative;}
.search-navbar .active{color:#ff4544!important;}
.search-navbar-item .fa-caret-up {position: absolute;top: 12rpx;padding: 0 6rpx;font-size: 24rpx;color:#ddd;}
.search-navbar-item .fa-caret-down {position: absolute;top: 24rpx;padding: 0 6rpx;font-size: 24rpx;color:#ddd;}
.search-history {padding: 24rpx 34rpx;}
.search-history .search-history-title {color: #666;}
.search-history .delete-search-history {float: right;padding: 15rpx 20rpx;margin-top: -15rpx;}
.search-history-list {padding: 24rpx 0 0 0;}
.search-history-list .search-history-item {display: inline-block;height: 50rpx;line-height: 50rpx;padding: 0 20rpx;margin: 0 10rpx 10rpx 0;background: #ddd;border-radius: 10rpx;font-size: 26rpx;}

.search-filter {display: flex;flex-direction: column;text-align: left;width:100%;flex-wrap:wrap;padding:6rpx;border-top:1px solid #ddd;border-bottom:1px solid #f5f5f0;}
.search-filter-content{display: flex;flex-wrap:wrap;padding:10rpx 0;}
.search-filter-content button{margin:4rpx 10rpx;font-size: 24rpx;display:flex;}
.search-filter-content .close{text-align: right;font-size:24rpx;color:#ff4544;width:100%;padding-right:20rpx}
.search-filter button .icon{margin-top:6rpx;height:54rpx;}
.search-navbar .active{color:#ff4544!important;}

.product-container {width: 100%;margin-top: 176rpx;font-size:26rpx;padding:0 14rpx}

.product-item{display:flex;flex-direction:column;background: #fff; padding:0 20rpx;margin:0;margin-bottom:20rpx;border-radius:20rpx}
.product-item .itemcontent{display:flex;height:220rpx;border-bottom:1px solid #E6E6E6;padding:20rpx 0}
.product-item .product-pic {width: 180rpx;height: 180rpx; background: #ffffff;overflow:hidden}
.product-item .product-pic image{width: 100%;height:180rpx;}
.product-item .product-info {padding:4rpx 10rpx;flex:1}
.product-item .product-info .p1 {color:#323232;font-weight:bold;font-size:28rpx;line-height:36rpx;margin-bottom:10rpx;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:2;overflow:hidden;height:72rpx}
.product-item .product-info .p2{height:50rpx;display:flex;align-items:center;color:#666;font-size:24rpx}
.product-item .product-info .p3{font-size:32rpx;height:40rpx;line-height:40rpx;display:flex;align-items:center}
.product-item .product-info .p3 .t1{flex:auto}
.product-item .product-info .p3 .t1 .x1{font-size:28rpx;font-weight:bold}
.product-item .product-info .p3 .t1 .x2{margin-left:10rpx;font-size:26rpx;color: #888;}
.product-item .product-info .p3 .t2{padding:0 16rpx;font-size:22rpx;height:44rpx;line-height:44rpx;overflow: hidden;color:#fff;background:#4fee4f;border:0;border-radius:20rpx;}
.product-item .product-info .p3 button:after{border:0}

.product-item .itembottom{width:100%;padding:0 20rpx;display:flex;height:100rpx;align-items:center}
.product-item .itembottom .f1{flex:1;color:#666;font-size:24rpx}
.product-item .itembottom .f2{color:#fff;width:160rpx;height:56rpx;display:flex;align-items:center;justify-content:center;border-radius:8rpx}

</style>