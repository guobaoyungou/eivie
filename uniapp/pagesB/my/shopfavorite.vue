<template>
<view class="container">
	<block v-if="isload">
		<view class="topsearch flex-y-center">
			<view class="f1 flex-y-center">
				<image class="img" :src="pre_url+'/static/img/search_ico.png'"></image>
				<input :value="keyword" placeholder="搜索感兴趣的商品" placeholder-style="font-size:24rpx;color:#C2C2C2" @confirm="searchConfirm" ></input>
				
			</view>
			
		</view>
		
		<view class="content-container">
			<view class="nav_left">
				<view :class="'nav_left_items ' + (curIndex == -1 ? 'active' : '')" :style="{color:curIndex == -1?t('color1'):'#333'}" @tap="switchRightTab" data-index="-1" data-id="0"><view class="before" :style="{background:t('color1')}"></view>全部</view>
				<block v-for="(item, index) in clist" :key="index">
					<view :class="'nav_left_items ' + (curIndex == index ? 'active' : '')" :style="{color:curIndex == index?t('color1'):'#333'}" @tap="switchRightTab" :data-index="index" :data-id="item.id"><view class="before" :style="{background:t('color1')}"></view>{{item.name}}</view>
				</block>
			</view>
			<view class="nav_right">
				<view class="nav_right-content">
					<view class="nav-pai">
						<view class="nav-paili" :style="(!field||field=='sort')?'color:'+t('color1'):''" @tap="changeOrder" data-field="sort" data-order="desc">综合</view> 
						<view class="nav-paili" :style="field=='sales'?'color:'+t('color1'):''" @tap="changeOrder" data-field="sales" data-order="desc">销量</view> 
						<view class="nav-paili" @tap="changeOrder" data-field="sell_price" :data-order="order=='asc'?'desc':'asc'">
							<text :style="field=='sell_price'?'color:'+t('color1'):''">价格</text>
							<text class="iconfont iconshangla" :style="field=='sell_price'&&order=='asc'?'color:'+t('color1'):''"></text>
							<text class="iconfont icondaoxu" :style="field=='sell_price'&&order=='desc'?'color:'+t('color1'):''"></text>
						</view>  
					</view>
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
							<view class="item" v-for="(item,index) in datalist" :key="item.id" :class="item.stock <= 0 ? 'soldout' : ''" @click="goto" :data-url="'/pages/shop/product?id='+item.id">
								<view class="product-pic">
									<image class="image" :src="item.pic" mode="widthFix"/>
									<view class="overlay"><view class="text">售罄</view></view>
								</view>
								<view class="product-info">
									<view class="p1"><text>{{item.name}}</text></view>	
									<view :style="{color:item.cost_color?item.cost_color:'#999',fontSize:'32rpx'}" v-if="item.show_cost && item.price_type != 1"><text style="font-size: 24rpx;padding-right:1px">{{item.cost_tag}}</text>{{item.cost_price}}</view>
									<view class="p2" >
										
										
										<view class="t1" style="font-size:24rpx;padding-right:1px" :style="{color:item.sellprice_color?item.sellprice_color:t('color1')}">
											{{item.sellprice_name}}<text style="font-size: 32rpx;">{{item.sell_price}}</text>
										</view>
										
										<view class="t1" :style="{color:item.price_color?item.price_color:t('color1')}" v-if="item.price > 0">
											<text style="font-size:24rpx;padding-right:1px">{{t('优惠价')}}</text><text style="font-size: 32rpx;">{{item.price}}</text>
										</view>
										
									</view>

									<view class="p3">
										<view class="p3-1" v-if="item.sales>0"><text style="overflow:hidden">已售{{item.sales}}件</text></view>
									</view>
									<view class="p3">
										<view class="p3-1" v-if="shopset && shopset.classify_show_stock == 1"><text style="overflow:hidden">库存{{item.stock}}</text></view>
									</view>
									
									<view class="p4" v-if="!item.price_type && item.hide_cart!=true" :style="{background:'rgba('+t('color1rgb')+',0.1)',color:t('color1')}" @click.stop="buydialogChange" :data-proid="item.id"><text class="iconfont icon_gouwuche"></text>
									</view>
									
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
		<block v-if="productType == 4">
			<block v-if="ggNum == 2">
				<buydialog-pifa v-if="buydialogShow" :proid="proid" @buydialogChange="buydialogChange" @addcart="afteraddcart"  :menuindex="menuindex" />
			</block>
			<block v-else>
				<buydialog-pifa2 v-if="buydialogShow" :proid="proid"  @buydialogChange="buydialogChange" @addcart="afteraddcart" :menuindex="menuindex" />
			</block>
		</block>
		<block v-else>
			<buydialog v-if="buydialogShow" :proid="proid" @buydialogChange="buydialogChange" :menuindex="menuindex"></buydialog>
		</block>
        <view class="posterDialog linkDialog" v-if="showLinkStatus">
        	<view class="main">
        		<view class="close" @tap="showLinkChange"><image class="img" :src="pre_url+'/static/img/close.png'"/></view>
        		<view class="content">
        			<view class="title">{{lx_name}}</view>
        			<view class="row" v-if="lx_bid > 0">
        				<view class="f1" style="width: 150rpx;">店铺名称</view>
        				<view class="f2" style="width: 100%;max-width: 470rpx;display: flex;" @tap="goto" :data-url="'/pagesExt/business/index?id='+lx_bid">
        				  <view style="width: 100%;white-space: nowrap;text-overflow: ellipsis;overflow: hidden;">{{lx_bname}}</view>
        				  <view style="flex: 1;"></view>
        				  <image :src="pre_url+'/static/img/arrowright.png'" class="image"/>
        				</view>
        			</view>
        			<view class="row" v-if="lx_tel">
        				<view class="f1" style="width: 150rpx;">联系电话</view>
        				<view class="f2" style="width: 100%;max-width: 470rpx;" @tap="goto" :data-url="'tel::'+lx_tel" :style="{color:t('color1')}">{{lx_tel}}<image :src="pre_url+'/static/img/copy.png'" class="copyicon" @tap.stop="copy" :data-text="lx_tel"></image></view>
        			</view>
        		</view>
        	</view>
        </view>
	</block>
	<loading v-if="loading" loadstyle="left:62.5%"></loading>
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
			pagenum: 1,
			nomore: false,
			nodata: false,
			order: '',
			field: '',
			clist: [],
			curIndex: -1,
			curIndex2: -1,
			datalist: [],
			curCid: 0,
			proid:0,
			buydialogShow: false,
			bid:'',
            
			showLinkStatus:false,
      lx_bname:'',
			lx_name:'',
			lx_bid:'',
			lx_tel:'',
			mendianid:0,
			latitude:'',
			longitude:'',
			area:'',
			productType:'',
			ggNum:'',
			shopset:{},
			keyword:'',
			pre_url: app.globalData.pre_url,
		};
	},
	onShow:function() {
    if(app.globalData.platform=='wx' && app.globalData.hide_home_button==1){
      uni.hideHomeButton();
    }
	},
  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.bid = this.opt.bid ? this.opt.bid  : '';
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
		this.getdata();
  },
	onPullDownRefresh: function () {
		this.getdata();
	},
	methods: {
		getdata:function(){
			var that = this;
			var nowcid = that.opt.cid;
			if (!nowcid) nowcid = '';
			that.pagenum = 1;
			that.datalist = [];
			that.loading = true;
			app.get('ApiShop/classify', {cid:nowcid,bid:that.bid}, function (res) {
				that.loading = false;
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
			      for (var j = 0; j < downcdata.length; j++) {
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
			  uni.setNavigationBarTitle({
			  	title: that.t('商城商品收藏')
			  });
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
			var order = that.order;
    
			var field = that.field; 
			that.loading = true;
			that.nodata = false;
			that.nomore = false;
			var wherefield = {};
			wherefield.pagenum = pagenum;
			wherefield.field = field;
			wherefield.order = order;
			wherefield.bid = bid;
			wherefield.keyword = that.keyword;

			if(bid > 0){
				wherefield.cid2 = cid;
			}else{
				wherefield.cid = cid;
			}
			
			app.post('ApiMy/getShopFavorite',wherefield, function (res) { 
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
				that.shopset = res.shopset;
			});
 
		},
		searchConfirm: function (e) {
		  var that = this;
		  var keyword = e.detail.value;
		  that.keyword = keyword
		  that.getdatalist();
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
				this.proid = e.currentTarget.dataset.proid;
				this.datalist.forEach(item => {
					if(item.id == this.proid){
						this.productType = item.product_type;
						this.ggNum = item.gg_num;
					}
				})
			}
			this.buydialogShow = !this.buydialogShow;
		},
		// buydialogChange: function (e) {
		// 	if(!this.buydialogShow){
		// 		this.proid = e.currentTarget.dataset.proid
		// 	}
		// 	this.buydialogShow = !this.buydialogShow;
		// },
        showLinkChange: function (e) {
            var that = this;
        	that.showLinkStatus = !that.showLinkStatus;
            that.lx_name = e.currentTarget.dataset.lx_name;
            that.lx_bid = e.currentTarget.dataset.lx_bid;
            that.lx_bname = e.currentTarget.dataset.lx_bname;
            that.lx_tel = e.currentTarget.dataset.lx_tel;
        },
	}

};
</script>
<style>
page {height:100vh;}
.topsearch{width:100%;padding:16rpx 20rpx;}
.topsearch .f1{height:60rpx;border-radius:30rpx;border:0;background-color:#f7f7f7;flex:1}
.topsearch .f1 .img{width:24rpx;height:24rpx;margin-left:10px}
.topsearch .f1 input{height:100%;flex:1;padding:0 20rpx;font-size:28rpx;color:#333;}
.topsearch .f1 .camera {height:72rpx;width:72rpx;color: #666;border: 0px;padding: 0px;margin: 0px;background-position: center;background-repeat: no-repeat; background-size:40rpx;}
.topsearch .search-btn{display:flex;align-items:center;color:#5a5a5a;font-size:30rpx;width:60rpx;text-align:center;margin-left:20rpx}
.container{width: 100%;height:100%;max-width:640px;background-color: #fff;color: #939393;display: flex;flex-direction:column}
.search-container {width: 100%;height: 94rpx;padding: 16rpx 23rpx 14rpx 23rpx;background-color: #fff;position: relative;overflow: hidden;border-bottom:1px solid #f5f5f5}
.search-box {display:flex;align-items:center;height:60rpx;border-radius:30rpx;border:0;background-color:#f7f7f7;flex:1}
.search-box .img{width:24rpx;height:24rpx;margin-right:10rpx;margin-left:30rpx}
.search-box .search-text {font-size:24rpx;color:#C2C2C2;width: 100%;}

.content-container{flex:1;height:100%;display:flex;overflow: hidden;}

.nav_left{width: 25%;height:100%;background: #ffffff;overflow-y:scroll;}
.nav_left .nav_left_items{line-height:50rpx;color:#333;font-weight:bold;border-bottom:0px solid #E6E6E6;font-size:28rpx;position: relative;border-right:0 solid #E6E6E6;padding:25rpx 30rpx;}
.nav_left .nav_left_items.active{background: #fff;color:#333;font-size:28rpx;font-weight:bold}
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
.product-itemlist .product-info .p1 {color:#323232;font-weight:bold;font-size:28rpx;line-height:36rpx;margin-bottom:0;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:2;overflow:hidden;height:72rpx;margin-bottom:10rpx;}
.product-itemlist .product-info .p2{line-height:36rpx;overflow:hidden;}
/* .product-itemlist .product-info .p2 .t1{font-size:32rpx;} */
.product-itemlist .product-info .p2 .t2 {margin-left:10rpx;font-size:24rpx;color: #aaa;text-decoration: line-through;/*letter-spacing:-1px*/}
.product-itemlist .product-info .p3{display:flex;align-items:center;overflow:hidden;margin-top:10rpx}
.product-itemlist .product-info .p3-1{font-size:24rpx;height:30rpx;line-height:30rpx;text-align:right;color:#999}
.product-itemlist .product-info .p4{width:56rpx;height:56rpx;border-radius:50%;position:absolute;display:relative;bottom:6rpx;right:4rpx;text-align:center;}
.product-itemlist .product-info .p4 .icon_gouwuche{font-size:32rpx;height:56rpx;line-height:56rpx}
.overlay {background-color: rgba(0,0,0,.5); position: absolute; width:60%; height: 60%; border-radius: 50%; display: none; top: 20%; left: 20%;}
.overlay .text{ color: #fff; text-align: center; transform: translateY(100%);}
.product-itemlist .soldout .product-pic .overlay{ display: block;}
::-webkit-scrollbar{width: 0;height: 0;color: transparent;}

.lianxi{color: #fff;border-radius: 50rpx 50rpx;line-height: 50rpx;text-align: center;font-size: 22rpx;padding: 0 14rpx;display: inline-block;float: right;}

.member{position: relative;border-radius: 8rpx;border: 1rpx solid #fff;overflow: hidden;margin-top: 10rpx;box-sizing: content-box;}
.member_lable{height: 100%;font-size: 22rpx;color: #fff;background: #fff;padding: 0 10rpx;}
.member_value{padding: 0 10rpx;font-size: 20rpx;color: #fff;}
</style>