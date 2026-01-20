<template>
<view class="container">
	<block v-if="isload">
		<view @tap.stop="goto" :data-url="'dksearch?mid='+mid" class="search-container">
			<view class="search-box">
				<image class="img" :src="pre_url+'/static/img/search_ico.png'"></image>
				<view class="search-text">搜索感兴趣的商品</view>
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
							<!-- @click="goto" :data-url="'/pages/shop/product?id='+item.id" -->
							<view class="item" v-for="(item,index) in datalist" :key="item.id" :class="item.stock <= 0 ? 'soldout' : ''" >
								<view class="product-pic">
									<image class="image" :src="item.pic" mode="widthFix"/>
									<view class="overlay"><view class="text">售罄</view></view>
								</view>
								<view class="product-info">
									<view class="p1"><text>{{item.name}}</text></view>
									<!-- 价格展示默认 -->
									<view v-if="item.price_show_type =='0' || !item.price_show_type ">
										<view :style="{color:item.cost_color?item.cost_color:'#999',fontSize:'32rpx'}" v-if="item.show_cost && item.price_type != 1"><text style="font-size: 20rpx;padding-right:1px">{{item.cost_tag}}</text>{{item.cost_price}}</view>
										<view class="p2" v-if="(item.price_type != 1 || item.sell_price > 0) && (item.showprice_dollar || item.show_sellprice)">
											<block v-if="item.showprice_dollar">
												<view class="t1" :style="{color:t('color1')}">
													<text style="font-size:20rpx;padding-right:1px">$</text>{{item.usd_sellprice}}
													<text style="font-size: 28rpx;margin-left: 6rpx;"><text style="font-size:20rpx;padding-right:1px">￥</text>{{item.sell_price}}</text>	
												</view>
											</block>
											<block v-else>
												<view class="t1" :style="{color:item.price_color?item.price_color:t('color1')}">
													<text style="font-size:20rpx;padding-right:1px">{{item.price_tag?item.price_tag:'￥'}}</text>{{item.sell_price}}<block v-if="item.product_unit">/{{item.product_unit}}</block>
												</view>
											</block>
											<text class="t2" v-if="item.market_price*1 > item.sell_price*1">￥{{item.market_price}}</text>
										</view>
									</view>
									<!-- 价格展示普通价和会员等级价 -->
									<view v-if="item.price_show_type =='1' || item.price_show_type =='2'">
										<view v-if="item.is_vip == '0'">
											<view class="p2" v-if="item.price_type != 1 || item.sell_price > 0">
												<text class="t1" :style="{color:t('color1')}"><text style="padding-right:1px;font-size: 20rpx;">￥</text>
													<text style="font-size:32rpx;">{{item.sell_price}}</text>
												</text>
												<text class="t2" v-if="item.market_price*1 > item.sell_price*1">￥{{item.market_price}}</text>
											</view>
											
											<view class="flex" v-if="item.price_show_type =='2'&& item.lvprice ==1 ">
												<view class="member flex" :style="'border-color:' + t('color1')">
													<view :style="{background:t('color1')}" class="member_lable flex-y-center">{{item.level_name_show}}</view>
													<view :style="'color:' + t('color1')" class="member_value flex-y-center">
														￥<text>{{item.sell_price_origin}}</text>
													</view>
												</view>
											</view>
										</view>
										<view v-if="item.is_vip == '1'">
											<view class="flex" v-if=" item.lvprice ==1 ">
												<view class="member flex" :style="'border-color:' + t('color1')">
													<view :style="{background:t('color1')}" class="member_lable flex-y-center">{{item.level_name_show}}</view>
													<view :style="'color:' + t('color1')" class="member_value flex-y-center" >
														￥<text style="font-size: 32rpx;">{{item.sell_price}}</text>
													</view>
												</view>
											</view>
											<view class="p2" v-if="item.price_type != 1 || item.sell_price > 0" >
												<text class="t1" :style="{color:t('color1')}"><text style="font-size:20rpx;padding-right:1px">￥</text>
												<text :style="item.lvprice =='1'?'font-size:26rpx;':'font-size:32rpx;'">{{item.sell_price_origin}}</text>
												</text>
												<text class="t2" v-if="item.market_price*1 > item.sell_price*1">￥{{item.market_price}}</text>
											</view>
										</view>
										
									</view>
									
									<view class="p2" v-if="item.xunjia_text && item.price_type == 1 && item.sell_price <= 0" style="height: 50rpx;line-height: 44rpx;">
										<text class="t1" :style="{color:t('color1'),fontSize:'30rpx'}">询价</text>
										<block v-if="item.xunjia_type==1">
											<view class="lianxi" v-if="item.xunjia_btn_url" :style="{background:item.xunjia_btn_bgcolor?item.xunjia_btn_bgcolor:t('color1'),color:item.xunjia_btn_color?item.xunjia_btn_color:'#FFF'}" @tap.stop="goto" :data-url="item.xunjia_btn_url">{{item.xunjia_text?item.xunjia_text:'联系TA'}}</view>
											<view class="lianxi" v-else :style="{background:item.xunjia_btn_bgcolor?item.xunjia_btn_bgcolor:t('color1'),color:item.xunjia_btn_color?item.xunjia_btn_color:'#FFF'}" @tap.stop="showLinkChange" :data-lx_name="item.lx_name" :data-lx_bid="item.lx_bid" :data-lx_bname="item.lx_bname" :data-lx_tel="item.lx_tel" data-btntype="2">{{item.xunjia_text?item.xunjia_text:'联系TA'}}</view>
										</block>
										<view v-else class="lianxi" :style="{background:t('color1')}" @tap.stop="showLinkChange" :data-lx_name="item.lx_name" :data-lx_bid="item.lx_bid" :data-lx_bname="item.lx_bname" :data-lx_tel="item.lx_tel" data-btntype="2">{{item.xunjia_text?item.xunjia_text:'联系TA'}}</view>
									</view>
									<view class="p1" v-if="item.merchant_name" style="color: #666;font-size: 24rpx;white-space: nowrap;text-overflow: ellipsis;margin-top: 6rpx;height: 30rpx;line-height: 30rpx;font-weight: normal"><text>{{item.merchant_name}}</text></view>
									<view class="p1" v-if="item.main_business" style="color: #666;font-size: 24rpx;margin-top: 4rpx;font-weight: normal;"><text>{{item.main_business}}</text></view>
									<view class="p3">
										<view class="p3-1" v-if="item.sales>0"><text style="overflow:hidden">已售{{item.sales}}件</text></view>
									</view>
                  <view v-if="item.sales<=0 && item.merchant_name" style="height: 44rpx;"></view>
									<view class="p4" v-if="!item.price_type && item.hide_cart!=true" :style="{background:'rgba('+t('color1rgb')+',0.1)',color:t('color1')}" @click.stop="buydialogChange" :data-proid="item.id"><text class="iconfont icon_gouwuche"></text></view>
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
		<buydialog v-if="buydialogShow" :proid="proid" @buydialogChange="buydialogChange" @addcart="afteraddcart" :menuindex="menuindex" btntype="1" :needaddcart="false"></buydialog>
		<view style="height:auto;position:relative">
			<view style="width:100%;height:100rpx"></view>
			<view class="footer flex" :class="menuindex>-1?'tabbarbot':'notabbarbot'">
				<view class="cart_ico" :style="{background:'linear-gradient(0deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'}" @tap.stop="handleClickMask"><image class="img" :src="pre_url+'/static/img/cart.png'" /><view class="cartnum" :style="{background:t('color1')}" v-if="cartList.list.length>0">{{cartData.total}}</view></view>
				<view class="text1">合计</view>
				<view class="text2 flex1" :style="{color:t('color1')}"><text style="font-size:20rpx">￥</text>{{cartData.totalprice}}</view>
				<view class="op" :style="{background:'linear-gradient(270deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'}" @tap="gopay">去录入</view>
			</view>
		</view>
		<view v-if="cartListShow" class="popup__container" style="margin-bottom:100rpx" :class="menuindex>-1?'tabbarbot':'notabbarbot'">
			<view class="popup__overlay" @tap.stop="handleClickMask" style="margin-bottom:100rpx" :class="menuindex>-1?'tabbarbot':'notabbarbot'"></view>
			<view class="popup__modal" style="min-height:400rpx;padding:0">
				<view class="popup__title" style="border-bottom:1px solid #EFEFEF">
					<text class="popup__title-text" style="color:#323232;font-weight:bold;font-size:32rpx">购物车</text>
					<view class="popup__close flex-y-center" @tap.stop="clearShopCartFn" style="color:#999999;font-size:24rpx"><image :src="pre_url+'/static/img/del.png'" style="width:24rpx;height:24rpx;margin-right:6rpx"/>清空</view>
				</view>
				<view class="popup__content" style="padding:0">
					<scroll-view scroll-y class="prolist">
						<block v-for="(cart, index) in cartList.list" :key="index">
							<view class="proitem">
								<image :src="cart.guige.pic?cart.guige.pic:cart.product.pic" class="pic flex0"></image>
								<view class="con">
									<view class="f1">{{cart.product.name}}</view>
									<view class="f2" v-if="cart.guige.name!='默认规格'">{{cart.guige.name}}</view>
									<view class="f3" style="color:#ff5555;margin-top:10rpx;font-size:28rpx">￥{{cart.guige.sell_price}}</view>
								</view>
								<view class="addnum">
									<view class="minus"><image class="img" :src="pre_url+'/static/img/cart-minus.png'" @tap="addcart" data-num="-1" :data-proid="cart.guige.proid" :data-ggid="cart.guige.id" :data-stock="cart.guige.stock" /></view>
									<text class="i">{{cart.num}}</text>
									<view class="plus"><image class="img" :src="pre_url+'/static/img/cart-plus.png'" @tap="addcart" data-num="1" :data-proid="cart.guige.proid" :data-ggid="cart.guige.id" :data-stock="cart.guige.stock" /></view>
								</view>
							</view>
						</block>
						<block v-if="!cartList.list.length">
							<text class="nopro">暂时没有商品喔~</text>
						</block>
					</scroll-view>
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
			lx_name:'',
			lx_bid:'',
			lx_tel:'',
			mendianid:0,
			latitude:'',
			longitude:'',
			area:'',
			cartList:{
				list:[]
			},
			cartListShow:false,
			cartData:'',
			mid:'',
			addressData:'',
			pre_url: app.globalData.pre_url,
		};
	},
	onShow:function() {
    if(app.globalData.platform=='wx' && app.globalData.hide_home_button==1){
      uni.hideHomeButton();
    }
		this.getdata();
	},
  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.bid = this.opt.bid ? this.opt.bid  : '';
		this.mid = this.opt.mid ? this.opt.mid  : '';
		this.addressData = this.opt.addressData ? this.opt.addressData:'';
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
				that.loaded();
				that.getdatalist();
				that.getdatacart();
			});
		},
		clearShopCartFn: function () {
		  var that = this;
			uni.showModal({
				title: '提示',
				content: '确认删除选购的商品吗？',
				success: function (res) {
					if (res.confirm) {
						app.post("ApiAdminOrderlr/cartdelete", {mid:that.mid,cartid:''}, function (res) {
						  that.getdata();
						});
					} else if (res.cancel) {
						
					}
				}
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
			if(bid > 0){
				wherefield.cid2 = cid;
			}else{
				wherefield.cid = cid;
			}
			//如果设置过地域限制【定位模式下】
			wherefield.area = that.area;
			wherefield.latitude = that.latitude;
			wherefield.longitude = that.longitude;
			wherefield.mendian_id = that.mendianid;
			wherefield.order_add_mobile = '1';
			app.post('ApiShop/getprolist',wherefield, function (res) { 
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
			this.curCid = id;
			this.pagenum = 1; 
			this.datalist = [];
			this.nomore = false;
			this.getdatalist();
		},
		buydialogChange: function (e) {
			if(!this.buydialogShow){
				this.proid = e.currentTarget.dataset.proid
			}
			this.buydialogShow = !this.buydialogShow;
		},
    showLinkChange: function (e) {
        var that = this;
    	that.showLinkStatus = !that.showLinkStatus;
        that.lx_name = e.currentTarget.dataset.lx_name;
        that.lx_bid = e.currentTarget.dataset.lx_bid;
        that.lx_bname = e.currentTarget.dataset.lx_bname;
        that.lx_tel = e.currentTarget.dataset.lx_tel;
    },
		afteraddcart: function (e) {
			this.addcart({currentTarget:{dataset:e}});
		},
		handleClickMask:function(){
			this.cartListShow = !this.cartListShow;
		},
		addcart:function(e){
			var that = this;
			console.log(e);
			var sell_price = e.currentTarget.dataset.ggprice;
			var num = e.currentTarget.dataset.num;
			var proid = e.currentTarget.dataset.proid;
			var ggid = e.currentTarget.dataset.ggid;
			that.loading = true;
			app.post('ApiAdminOrderlr/addcart', {proid: proid,ggid: ggid,num: num,sell_price:sell_price,mid:that.mid}, function (res) {
				that.loading = false;
				if (res.status == 1) {
					 that.getdatacart();
				} else {
					app.error(res.msg);
				}
			});
		},
		getdatacart(){
			let that = this;
			that.loading = true;
			app.post('ApiAdminOrderlr/cart', {mid:that.mid}, function (res) {
				that.loading = false;
				that.cartList.list = res.cartlist;
				that.cartData = res.cart;
			});
		},
		gopay: function () {
		  var cartList = this.cartList.list;
		  if (cartList.length == 0) {
		    app.alert('请先添加商品到购物车');
		    return;
		  }
			app.goto('shopstock')
		},
	}

};
</script>
<style>
page {height:100vh;}
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
.product-itemlist .product-info .p2{height:36rpx;line-height:36rpx;overflow:hidden;}
.product-itemlist .product-info .p2 .t1{font-size:32rpx;}
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

.member{position: relative;border-radius: 8rpx;border: 1rpx solid #fd4a46;overflow: hidden;margin-top: 10rpx;box-sizing: content-box;}
.member_lable{height: 100%;font-size: 22rpx;color: #fff;background: #fd4a46;padding: 0 10rpx;}
.member_value{padding: 0 10rpx;font-size: 20rpx;color: #fd4a46;}

.prolist {max-height: 620rpx;min-height: 320rpx;overflow: hidden;padding:0rpx 20rpx;font-size: 28rpx;border-bottom: 1px solid #e6e6e6;}
.prolist .nopro {text-align: center;font-size: 26rpx;display: block;margin: 80rpx auto;}
.prolist .proitem{position: relative;padding:10rpx 0;display:flex;border-bottom:1px solid #eee}
.prolist .proitem .pic{width: 120rpx;height: 120rpx;margin-right: 20rpx;}
.prolist .proitem .con{padding-right:180rpx;padding-top:10rpx}
.prolist .proitem .con .f1{color:#323232;font-size:26rpx;line-height:32rpx;margin-bottom: 10rpx;margin-top: -6rpx;display: -webkit-box;-webkit-box-orient: vertical;-webkit-line-clamp: 2;overflow: hidden;}
.prolist .proitem .con .f2{font-size: 24rpx;line-height:28rpx;color: #999;display: -webkit-box;-webkit-box-orient: vertical;-webkit-line-clamp: 1;overflow: hidden;}
.prolist .proitem .addnum {position: absolute;right: 20rpx;bottom:50rpx;font-size: 30rpx;color: #666;width: auto;display:flex;align-items:center}
.prolist .proitem .addnum .plus {width:48rpx;height:36rpx;background:#F6F8F7;display:flex;align-items:center;justify-content:center}
.prolist .proitem .addnum .minus {width:48rpx;height:36rpx;background:#F6F8F7;display:flex;align-items:center;justify-content:center}
.prolist .proitem .addnum .img{width:24rpx;height:24rpx}
.prolist .proitem .addnum .i {padding: 0 20rpx;color:#2B2B2B;font-weight:bold;font-size:24rpx}
.prolist .tips {font-size: 22rpx;color: #666;text-align: center;line-height: 56rpx;background: #f5f5f5;}

.footer {width: 100%;background: #fff;margin-top: 5px;position: fixed;left: 0px;bottom: 0px;z-index:8;display:flex;align-items:center;padding:0 20rpx;border-top:1px solid #EFEFEF}
.footer .cart_ico{width:64rpx;height:64rpx;border-radius: 10rpx;display:flex;align-items:center;justify-content:center;position:relative}
.footer .cart_ico .img{width:36rpx;height:36rpx;}
.footer .cart_ico .cartnum{position:absolute;top:-17rpx;right:-17rpx;width:34rpx;height:34rpx;border:1px solid #fff;border-radius:50%;display:flex;align-items:center;justify-content:center;overflow:hidden;font-size:20rpx;font-weight:bold;color:#fff}
.footer .text1 {height: 100rpx;line-height: 100rpx;color:#555555;font-weight:bold;font-size: 30rpx;margin-left:40rpx;margin-right:10rpx}
.footer .text2 {font-size: 32rpx;font-weight:bold}
.footer .op{width: 200rpx;height: 72rpx;line-height:72rpx;border-radius: 36rpx;font-weight:bold;color:#fff;font-size:28rpx;text-align:center}
::-webkit-scrollbar{width: 0;height: 0;color: transparent;}
</style>