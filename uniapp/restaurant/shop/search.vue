<template>
<view class="container">
	<block v-if="isload">
		<view class="search-container" :style="history_show?'height:100%;':''">
			<view class="topsearch flex-y-center">
				<view class="f1 flex-y-center">
					<image class="img" :src="pre_url+'/static/img/search_ico.png'"></image>
					<input :value="keyword" placeholder="搜索菜品" placeholder-style="font-size:24rpx;color:#C2C2C2" @confirm="searchConfirm" @input="searchChange" @focus="searchFocus"></input>
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
			</view>

			
		</view>
		<view class="product-container">
			<!-- <block v-if="datalist && datalist.length>0">
				<dp-product-item v-if="productlisttype=='item2'" :data="datalist" :menuindex="menuindex"></dp-product-item>
				<dp-product-itemlist v-if="productlisttype=='itemlist'" :data="datalist" :menuindex="menuindex"></dp-product-itemlist>
			</block> -->
			<view class="product-itemlist" v-if="datalist && datalist.length>0">
				<view class="item" v-for="(item,index) in datalist" :key="item.id" :class="(item.stock <= 0 || item.stock_daily <= item.sales_daily) ? 'soldout' : ''">
					<view class="product-pic" @click="goto" :data-url="'product?id='+item.id">
						<image class="image" :src="item.pic" mode="widthFix"/>
						<view class="overlay"><view class="text">售罄</view></view>
					</view>
					<view class="product-info">
						<view class="p1"><text>{{item.name}}</text></view>
						<view class="p2">
							<view class="t1" :style="{color:t('color1')}">
                <text style="font-size:20rpx;padding-right:1px">￥</text>{{item.sell_price}}
                <text v-if="item.price_show && item.price_show_text" style="margin: 0 15rpx;font-size: 24rpx;font-weight: 400;">{{item.price_show_text}}</text>
              </view>
							<text class="t2" v-if="item.market_price*1 > item.sell_price*1">￥{{item.market_price}}</text>
						</view>
            <!-- 商品处显示会员价 -->
            <view v-if="item.price_show && item.price_show == 1" style="line-height: 46rpx;">
              <text style="font-size:26rpx">￥{{item.sell_putongprice}}</text>
            </view>
            <view v-if="item.priceshows && item.priceshows.length>0">
              <view v-for="(item2,index2) in item.priceshows" style="line-height: 46rpx;">
                <text style="font-size:26rpx">￥{{item2.sell_price}}</text>
                <text style="margin-left: 15rpx;font-size: 22rpx;font-weight: 400;">{{item2.price_show_text}}</text>
              </view>
            </view>
						<view class="p3">
							<view class="p3-1" v-if="item.sales>0"><text style="overflow:hidden">已售{{item.sales}}件</text></view>
							<view class="p3-1" v-if="item.limit_start>0"><text style="overflow:hidden">{{item.limit_start}}件起售</text></view>
						</view>
						<!-- <view class="p4" :style="{background:'rgba('+t('color1rgb')+',0.1)',color:t('color1')}" @click.stop="buydialogChange" :data-proid="item.id"><text class="iconfont icon_gouwuche"></text></view> -->
						<view class="addnum" v-if="item.stock > 0 && item.stock_daily > item.sales_daily">
							<view v-if="item.ggcount>1" class="plus" @tap.stop="buydialogChange" :data-proid="item.id" :data-stock="item.stock">+</view>
							<view v-else class="plus" @tap.stop="addcart" data-num="1" :data-proid="item.id" :data-ggid="item.gglist[0].id" :data-stock="item.stock">+</view>
						</view>
					</view>
				</view>
			</view>
			<buydialog v-if="buydialogShow" :proid="proid" @buydialogChange="buydialogChange" @addcart="afteraddcart" :menuindex="menuindex" btntype="1" :needaddcart="false" controller="ApiRestaurantShop"></buydialog>
			
			<nomore text="没有更多菜品了" v-if="nomore"></nomore>
			<nodata text="没有查找到相关菜品" v-if="nodata"></nodata>
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

			nomore:false,
			nodata:false,
			buydialogShow:false,
      keyword: '',
      pagenum: 1,
      datalist: [],
      history_list: [],
      history_show: true,
      order: '',
			field:'',
      oldcid: "",
			proid:'',
      cid: "",
      gid: '',
      clist: [],
      clist2: [],
      glist: [],
      productlisttype: 'item2',
			cpid:0,
			bid:0,
      pre_url:app.globalData.pre_url,
    };
  },
  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.oldcid = this.opt.cid || '';
		this.cid = this.opt.cid;
		this.gid = this.opt.gid;
		this.cpid = this.opt.cpid || 0;
		this.bid = this.opt.bid ? this.opt.bid : 0;
		//console.log(this.bid);
		if(this.cpid > 0){
			uni.setNavigationBarTitle({
				title: '可用菜品列表'
			});
		}
    var productlisttype = app.getCache('productlisttype');
    if (productlisttype) this.productlisttype = productlisttype;
		this.history_list = app.getCache('search_history_list_rest_shop');
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
			that.loading = true;
			that.getprolist();
			that.loaded();
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
			var bid = that.opt.bid ? that.opt.bid : '';
      app.post('ApiRestaurantShop/getprolist',{pagenum: pagenum,keyword: keyword,field: field,order: order,gid: gid,cid: cid,cpid:cpid,bid:bid}, function (res) {
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
    addHistory: function () {
      var that = this;
      var keyword = that.keyword;
      if (app.isNull(keyword)) return;
      var historylist = app.getCache('search_history_list_rest_shop');
      if (app.isNull(historylist)) historylist = [];
      historylist.unshift(keyword);
      var newhistorylist = [];
      for (var i in historylist) {
        if (historylist[i] != keyword || i == 0) {
          newhistorylist.push(historylist[i]);
        }
      }
      if (newhistorylist.length > 5) newhistorylist.splice(5, 1);
      app.setCache('search_history_list_rest_shop', newhistorylist);
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
      app.removeCache("search_history_list_rest_shop");
    },
		
		addcart:function(e){
			var that = this;
			var ks = that.ks;
			var num = e.currentTarget.dataset.num;
			var proid = e.currentTarget.dataset.proid;
			var ggid = e.currentTarget.dataset.ggid;
			that.loading = true;
			app.post('ApiRestaurantShop/addcart', {proid: proid,ggid: ggid,num: num,bid:that.opt.bid}, function (res) {
				that.loading = false;
				if (res.status == 1) {
					app.success(res.msg ? res.msg : '添加成功')
				} else {
					app.error(res.msg);
				}
			});
		},
		buydialogChange: function (e) {
			if(!this.buydialogShow){
				this.proid = e.currentTarget.dataset.proid
			}
			this.buydialogShow = !this.buydialogShow;
		},
    //加入购物车弹窗后
    afteraddcart: function (e) {
			e.hasoption = false;
      this.addcart({currentTarget:{dataset:e}});
    },
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

.product-itemlist{height: auto; position: relative;overflow: hidden; padding: 0px; display:flex;flex-wrap:wrap}
.product-itemlist .item{width:100%;display: inline-block;position: relative;margin-bottom: 12rpx;background: #fff;display:flex;padding:14rpx 0;border-radius:10rpx;border-bottom:1px solid #F8F8F8}
.product-itemlist .product-pic {width: 30%;height:0;overflow:hidden;background: #ffffff;padding-bottom: 30%;position: relative;border-radius:4px;}
.product-itemlist .product-pic .image{position:absolute;top:0;left:0;width: 100%;height:auto}
.product-itemlist .product-pic .saleimg{ position: absolute;width: 120rpx;height: auto; top: -6rpx; left:-6rpx;}
.product-itemlist .product-info {width: 70%;padding:0 10rpx 5rpx 20rpx;position: relative;}
.product-itemlist .product-info .p1 {color:#323232;font-weight:bold;font-size:28rpx;line-height:30rpx;margin-bottom:0;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:2;overflow:hidden;height:60rpx}
.product-itemlist .product-info .p2{margin-top:10rpx;height:36rpx;line-height:36rpx;overflow:hidden;}
.product-itemlist .product-info .p2 .t1{font-size:32rpx;}
.product-itemlist .product-info .p2 .t2 {margin-left:10rpx;font-size:24rpx;color: #aaa;text-decoration: line-through;/*letter-spacing:-1px*/}
.product-itemlist .product-info .p3{display:flex;align-items:center;overflow:hidden;margin-top:10rpx}
.product-itemlist .product-info .p3-1{font-size:20rpx;height:30rpx;line-height:30rpx;text-align:right;color:#999}
.product-itemlist .product-info .p4{width:48rpx;height:48rpx;border-radius:50%;position:absolute;display:relative;bottom:6rpx;right:4rpx;text-align:center;}
.product-itemlist .product-info .p4 .icon_gouwuche{font-size:28rpx;height:48rpx;line-height:48rpx}
.product-itemlist .addnum {position: absolute;right:10rpx;bottom:20rpx;font-size: 30rpx;color: #666;width: auto;display:flex;align-items:center}
.product-itemlist .addnum .plus {width:40rpx;height:40rpx;background:#FD4A46;color:#FFFFFF;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:28rpx}
.product-itemlist .addnum .minus {width:40rpx;height:40rpx;background:#FFFFFF;color:#FD4A46;border:1px solid #FD4A46;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:28rpx}
.product-itemlist .addnum .img{width:24rpx;height:24rpx}
.product-itemlist .addnum .i {padding: 0 20rpx;color:#999999;font-size:28rpx}
.overlay {background-color: rgba(0,0,0,.5); position: absolute; width:60%; height: 60%; border-radius: 50%; display: none; top: 20%; left: 20%;}
.overlay .text{ color: #fff; text-align: center; transform: translateY(100%);}
.product-itemlist .soldout .product-pic .overlay{ display: block;}
</style>