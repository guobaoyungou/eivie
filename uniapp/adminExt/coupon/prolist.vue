<template>
<view class="container">
	<!-- 搜索 -->
	<block v-if="!pageSwitch">
		<view class="search-container-search" :style="history_show?'height:100%;':''">
			<view class="topsearch flex-y-center">
				<view class="f1 flex-y-center">
					<image class="img" :src="pre_url+'/static/img/search_ico.png'"></image>
					<input :value="keyword" placeholder="搜索感兴趣的商品" placeholder-style="font-size:24rpx;color:#C2C2C2" @confirm="searchConfirm" @input="searchChange"></input>
				</view>
				<view class="search-btn" @tap="searchbtn">
					<text>搜索</text>
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
		</view>
		<view class="product-container">
			<block v-if="datalist && datalist.length>0">
					<view class="product-itemlist">
						<view class="item" v-for="(item,index) in datalist" :key="item.id" @click="toDetail" :data-type="item.type?item.type:0" :data-id="item.id" >
							<view class="product-pic">
								<image class="image" :src="item.pic" mode="widthFix"/>
							</view>
							<view class="product-info">
								<view class="p1"><text>{{item.name}}</text></view>
								<view class="p2">
									<view class="t1" :style="{color:t('color1')}">{{item.sell_price}}<text style="font-size:24rpx;padding-left:2px">元/{{item.danwei}}</text></view>
									<text class="t2" v-if="item.market_price*1 > item.sell_price*1">￥{{item.market_price}}</text>
								</view>
								<view class="p3">
									<view class="p3-1" v-if="item.sales>0"><text style="overflow:hidden">已售{{item.sales}}件</text></view>
								</view>
								<view class="addbut" :style="{background:'linear-gradient(270deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'}" @click="couponAddChange(item)">添加</view>
							</view>
						</view>
					</view>
			</block>
			<nomore text="没有更多商品了" v-if="nomore"></nomore>
			<nodata text="没有查找到相关商品" v-if="nodata"></nodata>
		</view>
	</block>
	<!-- 列表 -->
	<block v-if="pageSwitch">
		<view @tap.stop="goSearch"class="search-container">
			<view class="search-box">
				<image class="img" :src="pre_url+'/static/img/search_ico.png'"></image>
				<view class="search-text">搜索感兴趣的商品</view>
			</view>
		</view>
		<view class="content-container">
			<view class="nav_left">
				<view :class="'nav_left_items ' + (curIndex == -1 ? 'active' : '')" @tap="switchRightTab" data-index="-1" data-id="0"><view class="before" :style="{background:t('color1')}"></view>全部</view>
				<block v-for="(item, index) in clist" :key="index">
					<view :class="'nav_left_items ' + (curIndex == index ? 'active' : '')" @tap="switchRightTab" :data-index="index" :data-id="item.id"><view class="before" :style="{background:t('color1')}"></view>{{item.name}}</view>
				</block>
			</view>
			<view class="nav_right">
				<view class="nav_right-content">
					<view class="classify-ul" v-if="curIndex>-1 && clist[curIndex].child.length>0">
						<view class="flex" style="width:100%;overflow-y:hidden;overflow-x:scroll;">
						 <view class="classify-li" :style="curIndex2==-1?'color:'+t('color1')+';background:rgba('+t('color1rgb')+',0.2)':''" @tap="changeCTab" :data-id="clist[curIndex].id" data-index="-1">全部</view>
						 <block v-for="(item, idx2) in clist[curIndex].child" :key="idx2">
						 <view class="classify-li" :style="curIndex2==idx2?'color:'+t('color1')+';background:rgba('+t('color1rgb')+',0.2)':''" @tap="changeCTab" :data-id="item.id" :data-index="idx2">{{item.name}}</view>
						 </block>
						</view>
					</view>
					<scroll-view class="classify-box" scroll-y="true" @scrolltolower="scrolltolower">
						<view class="product-itemlist">
							<view class="item" v-for="(item,index) in datalist" :key="item.id" @click="toDetail" :data-type="item.type?item.type:0" :data-id="item.id" >
								<view class="product-pic">
									<image class="image" :src="item.pic" mode="widthFix"/>
								</view>
								<view class="product-info">
									<view class="p1"><text>{{item.name}}</text></view>
									<view class="p2">
										<view class="t1" :style="{color:t('color1')}">{{item.sell_price}}<text style="font-size:24rpx;padding-left:2px">元/{{item.danwei}}</text></view>
										<text class="t2" v-if="item.market_price*1 > item.sell_price*1">￥{{item.market_price}}</text>
									</view>
									<view class="p3">
										<view class="p3-1" v-if="item.sales>0"><text style="overflow:hidden">已售{{item.sales}}件</text></view>
									</view>
									<view class="addbut" :style="{background:'linear-gradient(270deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'}" @click="couponAddChange(item)">添加</view>
								</view>
							</view>
						</view>
						<nomore text="没有更多商品了" v-if="nomore"></nomore>
						<nodata text="暂无相关商品" v-if="nodata"></nodata>
						<view style="width:100%;height:100rpx"></view>
					</scroll-view>
				</view>
			</view>
		</view>
		<buydialog v-if="buydialogShow" :proid="proid" @buydialogChange="buydialogChange" :menuindex="menuindex"></buydialog>
	</block>
	<loading v-if="loading" loadstyle="left:62.5%"></loading>
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
			pagenum: 1,
			nomore: false,
			nodata: false,
			order: '',
			field: '',
			clist: [],
			curIndex: -1,
			curIndex2: -1,
			datalist: [],
			nodata: false,
			curCid: 0,
			proid:0,
			buydialogShow: false,
			pageSwitch:true,
			// 
			history_show: true,
			keyword: '',
			history_list: [],
			productlisttype: 'item2',
			pre_url:app.globalData.pre_url,
		};
	},
  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.getdata();
  },
	onPullDownRefresh: function () {
		this.getdata();
	},
	methods: {
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
		  app.post('ApiYuyue/getprolist',{pagenum: pagenum,keyword: keyword,field: field,order: order,gid: gid,cid: cid,cpid:cpid,is_coupon:1}, function (res) {
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
		deleteSearchHistory: function () {
		  var that = this;
		  that.history_list = null;
		  app.removeCache("search_history_list");
		},
		historyClick: function (e){
		  var that = this;
		  var keyword = e.currentTarget.dataset.value;
		  if (keyword.length == 0) return;
		  that.keyword = keyword;
		  that.searchproduct();
		},
		// 
		// 搜索商品
		goSearch(){
			this.pageSwitch = !this.pageSwitch;
		},
		couponAddChange(item){
			uni.$emit('shopDataEmitS',{id:item.id,name:item.name,pic:item.pic,give_num:1});
			uni.navigateBack({
				delta: 1
			});
		},
		getdata:function(){
			var that = this;
			var nowcid = that.opt.cid;
			if (!nowcid) nowcid = '';
			var bid = that.opt.bid ? that.opt.bid : '';
			that.pagenum = 1;
			that.datalist = [];
			app.get('ApiYuyue/classify', {cid:nowcid,bid:bid}, function (res) {
			  var clist = res.data;
			  that.clist = clist;
			  if (nowcid) {
			    for (var i = 0; i < clist.length; i++) {
			      if (clist[i]['id'] == nowcid) {
			        that.curIndex = i;
			        that.curCid = nowcid;
			      }
			      var downcdata = clist[i]['child'];
			      var isget = 0;
			      for (var j = 0; j < downcdata; j++) {
			        if (downcdata[j]['id'] == nowcid) {
			          that.curIndex = i;
			          that.curIndex2 = j;
			          that.curCid = nowcid;
			          isget = 1;
			          break;
			        }
			      }
			      if (isget) break;
			    }
			  }
				that.loaded();
				that.getdatalist();
			});
		},
		getdatalist: function (loadmore) {
			if(!loadmore){
				this.pagenum = 1;
				this.datalist = [];
			}
			var that = this;
			var pagenum = that.pagenum;
			var cid = that.curCid;
			var bid = that.opt.bid ? that.opt.bid : '';
			var cpid = that.opt.cpid ? that.opt.cpid : '';
			var order = that.order;
    
			var field = that.field; 
			that.loading = true;
			that.nodata = false;
			that.nomore = false;
			app.post('ApiYuyue/getprolist', {pagenum: pagenum,field: field,order: order,cid: cid,bid:bid,cpid:cpid,is_coupon:1}, function (res) { 
				that.loading = false;

				uni.stopPullDownRefresh();

				var data = res.data;
				if (data.length == 0) {
					if(pagenum == 1){
						that.nodata = true;
					}else{
						that.nomore = true;
					}
				}
				var datalist = that.datalist;
				var newdata = datalist.concat(data);
				that.datalist = newdata;
			});
 
		},
   
		scrolltolower: function () {
     
			if (!this.nomore) {
   
				this.pagenum = this.pagenum + 1;    
				this.getdatalist(true);
 
			}
 
		},
    
		//改变子分类
    
		changeCTab: function (e) {
    
			var that = this;

			var id = e.currentTarget.dataset.id;
			var index = parseInt(e.currentTarget.dataset.index);

			this.curIndex2 = index;
			this.nodata = false;

			this.curCid = id;

			this.pagenum = 1;

			this.datalist = [];

			this.nomore = false;

			this.getdatalist();

		},
    
		//改变排序规则

		changeOrder: function (e) {
    
			var t = e.currentTarget.dataset;
  
			this.field = t.field; 
			this.order = t.order;
 
			this.pagenum = 1;
			this.datalist = []; 
			this.nomore = false;
			
			this.getdatalist();
  
		},
   
		//事件处理函数
 
		switchRightTab: function (e) {
  
			var that = this;
    
			var id = e.currentTarget.dataset.id;
   
			var index = parseInt(e.currentTarget.dataset.index);
  
			this.curIndex = index;
			this.curIndex2 = -1;
			this.nodata = false;
			this.curCid = id
;
			this.pagenum = 1; 
			this.datalist = [];
			this.nomore = false;
  
			this.getdatalist();
 
		}
,
		buydialogChange: function (e) {
			if(!this.buydialogShow){
				this.proid = e.currentTarget.dataset.proid
			}
			this.buydialogShow = !this.buydialogShow;
		},
		toDetail(e){
			return;
			var id = e.currentTarget.dataset.id;
			var type = e.currentTarget.dataset.type;
			if(type == 0){
				app.goto('/activity/yuyue/product?id='+id);
			}else{
				var prodata = id;
				app.goto('/activity/yuyue/buy?prodata=' + prodata);
			}
		}
	}

};
</script>
<style>
page {height:100%;}
.container{width: 100%;height:100%;max-width:640px;background-color: #fff;color: #939393;display: flex;flex-direction:column}
.search-container {width: 100%;height: 94rpx;padding: 16rpx 23rpx 14rpx 23rpx;background-color: #fff;position: relative;overflow: hidden;border-bottom:1px solid #f5f5f5}
.search-box {display:flex;align-items:center;height:60rpx;border-radius:30rpx;border:0;background-color:#f7f7f7;flex:1}
.search-box .img{width:24rpx;height:24rpx;margin-right:10rpx;margin-left:30rpx}
.search-box .search-text {font-size:24rpx;color:#C2C2C2;width: 100%;}

.content-container{flex:1;height:100%;display:flex;overflow: hidden;}

.nav_left{width: 25%;height:100%;background: #ffffff;overflow-y:scroll;}
.nav_left .nav_left_items{line-height:50rpx;color:#999999;border-bottom:0px solid #E6E6E6;font-size:28rpx;position: relative;border-right:0 solid #E6E6E6;padding:25rpx 30rpx;}
.nav_left .nav_left_items.active{background: #fff;color:#222222;font-size:28rpx;font-weight:bold}
.nav_left .nav_left_items .before{display:none;position:absolute;top:50%;margin-top:-12rpx;left:10rpx;height:24rpx;border-radius:4rpx;width:8rpx}
.nav_left .nav_left_items.active .before{display:block}

.nav_right{width: 75%;height:100%;display:flex;flex-direction:column;background: #f6f6f6;box-sizing: border-box;padding:20rpx 20rpx 0 20rpx}
.nav_right-content{background: #ffffff;padding:0 20rpx;height:100%}
.nav-pai{ width: 100%;display:flex;align-items:center;justify-content:center;}
.nav-paili{flex:1; text-align:center;color:#323232; font-size:28rpx;font-weight:bold;position: relative;height:80rpx;line-height:80rpx;}
.nav-paili .iconshangla{position: absolute;top:-4rpx;padding: 0 6rpx;font-size: 20rpx;color:#7D7D7D}
.nav-paili .icondaoxu{position: absolute;top: 8rpx;padding: 0 6rpx;font-size: 20rpx;color:#7D7D7D}

.classify-ul{width:100%;height:100rpx;padding:0 10rpx;}
.classify-li{flex-shrink:0;display:flex;background:#F5F6F8;border-radius:22rpx;color:#6C737F;font-size:20rpx;text-align: center;height:44rpx; line-height:44rpx;padding:0 28rpx;margin:12rpx 10rpx 12rpx 0}

.classify-box{padding: 0 0 20rpx 0;width: 100%;height:calc(100% - 60rpx);overflow-y: scroll; border-top:1px solid #F5F6F8;}
.classify-box .nav_right_items{ width:100%;border-bottom:1px #f4f4f4 solid;  padding:16rpx 0;  box-sizing:border-box;  position:relative; }

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
.addbut{width:88rpx;height:60rpx;border-radius:30rpx;position:absolute;display:relative;bottom:6rpx;right:4rpx;text-align:center;font-size: 24rpx;
line-height:60rpx;color: #fff;}
/*  */
.search-container-search {position: fixed;width: 100%;background: #fff;z-index:9;top:var(--window-top)}
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

.product-container {width: 100%;margin-top: 120rpx;font-size:26rpx;padding:0 24rpx}
</style>