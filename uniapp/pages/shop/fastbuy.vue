<template>
<view class="container">
	<block v-if="isload">
		<view class="view-show">
			<view @tap.stop="goto" :data-url="'/pages/shop/search?bid='+bid" class="search-container">
				<view class="search-box">
					<image class="img" :src="pre_url+'/static/img/search_ico.png'"></image>
					<view class="search-text">搜索感兴趣的商品</view>
				</view>
			</view>
			<view class="content" style="overflow:hidden;display:flex" :style="{height:'calc(100% - '+(menuindex>-1?294:194)+'rpx)'}">
				<scroll-view class="nav_left" :scrollWithAnimation="animation" scroll-y="true" :class="menuindex>-1?'tabbarbot':'notabbarbot'">
					<block v-for="(item, index) in datalist" :key="index" >
					<view class="nav_left_items" :class="index===currentActiveIndex?'active':''" :style="{color:index===currentActiveIndex?t('color1'):'#333'}" @tap="clickRootItem" :data-root-item-id="item.id" :data-root-item-index="index"><view class="before" :style="{background:t('color1')}"></view>{{item.name}}</view>
					</block>
				</scroll-view>
				<view class="nav_right">
					<view class="nav_right-content">
						<scroll-view @scroll="scroll" class="detail-list" :scrollIntoView="scrollToViewId" :scrollWithAnimation="animation" scroll-y="true" :show-scrollbar="false">
							<view v-for="(detail, indexs) in datalist" :key="indexs" class="classification-detail-item">
								<view class="head" :data-id="detail.id" :id="'detail-' + detail.id">
									<view class="txt">{{detail.name}}</view>
									<view class="show-all" @tap="gotoCatproductPage" :data-id="detail.id">查看全部<text class="iconfont iconjiantou"></text></view>
								</view>
								<view class="product-itemlist">
									<view class="item" v-for="(item,index) in detail.prolist" :key="item.id" @click="goto" :data-url="'/pages/shop/product?id='+item.id">
										<view class="product-pic">
											<image class="image" :src="item.pic" mode="widthFix"/>
										</view>
										<view class="product-info">
											<view class="p1"><text>{{item.name}}</text></view>
											<view class="p5" :style="{color:t('color2')}" v-if="custom.product_show_sellpoint && item.sellpoint"><text>{{item.sellpoint}}</text></view>
											<view :style="{color:t('color1')}" v-if="item.showgivescore">
											  <text style="font-size: 24rpx;">赠送{{item.showgivescore}}{{t('积分')}}</text>
											</view>
                      <view class="p2" v-if="item.price_type != 1 || item.sell_price > 0">
												<view class="t1" :style="{color:t('color1')}">
													<text style="font-size:20rpx;padding-right:1px">￥</text>{{item.sell_price}}
                          <text v-if="item.price_show && item.price_show_text" style="margin: 0 15rpx;font-size: 24rpx;font-weight: 400;">{{item.price_show_text}}</text>
													<text style="font-size:24rpx" v-if="item.product_unit">/{{item.product_unit}}</text>
												</view>
												<!-- 服务费 -->
												<text v-if="!isNull(item.service_fee) && item.service_fee_switch && item.service_fee > 0" class="t1-m" :style="{color:t('color1')}">+{{item.service_fee}}{{t('服务费')}}</text>
												<!-- 称重商品单价 -->
												<text v-if="custom.product_weight && item.product_type==2 && item.unit_price" class="t1-m" :style="{color:t('color1')}">
													(约{{item.unit_price}}元/斤)
												</text>
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
											<view class="p6" v-if="custom.product_show_fwlist && item.fwlist && item.fwlist.length>0">
												<view class="p6-m" :style="'background:rgba('+t('color2rgb')+',0.15);color:'+t('color2')+';'" v-for="(fw,fwidx) in item.fwlist" :key="fwidx">
													{{fw}}
												</view>
											</view>
											<view class="p3">
												<view class="p3-1" v-if="item.sales>0"><text style="overflow:hidden">已售{{item.sales}}件</text></view>
												<view class="p3-2" v-if="item.limit_start>0"><text style="overflow:hidden;">{{item.limit_start}}件起售</text></view>
											</view>
											<view class="p4" v-if="!item.price_type" :style="{background:'rgba('+t('color1rgb')+',0.1)',color:t('color1')}" @click.stop="buydialogChange" :data-proid="item.id" :data-indexs='indexs'><text class="iconfont icon_gouwuche"></text></view>
										</view>
									</view>
								</view>
							</view>
						</scroll-view>
					</view>
				</view>
			</view>
		</view>
		<block v-if="productType == 4">
			<block v-if="ggNum == 2">
				<buydialog-pifa v-if="buydialogShow" :proid="proid" btntype="1" @buydialogChange="buydialogChange" @addcart="afteraddcart"  :menuindex="menuindex" :needaddcart="false" />
			</block>
			<block v-else>
				<buydialog-pifa2 v-if="buydialogShow" :proid="proid" btntype="1" @buydialogChange="buydialogChange" @addcart="afteraddcart2" :menuindex="menuindex" :needaddcart="false" />
			</block>
		</block>
		<block v-else>
			<buydialog v-if="buydialogShow" :proid="proid" @buydialogChange="buydialogChange" @addcart="afteraddcart" :menuindex="menuindex" btntype="1" :needaddcart="false"></buydialog>
		</block>
		<view style="height:auto;position:relative">
			<view style="width:100%;height:100rpx"></view>
			<view class="footer flex" :class="menuindex>-1?'tabbarbot':'notabbarbot'">
				<view class="cart_ico" :style="{background:'linear-gradient(0deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'}" @tap.stop="handleClickMask"><image class="img" :src="pre_url+'/static/img/cart.png'" /><view class="cartnum" :style="{background:t('color1')}" v-if="cartList.total>0">{{cartList.total}}</view></view>
				<view class="text1">合计</view>
				<view class="text2 flex1" :style="{color:t('color1')}"><text style="font-size:20rpx">￥</text>{{cartList.totalprice}}</view>
				<view class="op" :style="{background:'linear-gradient(270deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'}" @tap="gopay">去结算</view>
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
									<view class="minus"><image class="img" :src="pre_url+'/static/img/cart-minus.png'" @tap="addcart" data-num="-1" :data-proid="cart.proid" :data-ggid="cart.ggid" :data-stock="cart.guige.stock"/></view>
									<text class="i">{{cart.num}}</text>
									<view class="plus"><image class="img" :src="pre_url+'/static/img/cart-plus.png'" @tap="addcart" data-num="1" :data-proid="cart.proid" :data-ggid="cart.ggid" :data-stock="cart.guige.stock"/></view>
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
      isload: false,
			menuindex:-1,
			
			cartListShow:false,
			buydialogShow:false,
			harr:[],
      datalist: [],
			cartList:{},
			proid:'',
			totalprice:'0.00',
      currentActiveIndex: 0,
      animation: true,
      scrollToViewId: "",
			bid:'',
			scrollState:true,
			productType:'',
			ggNum:'',
			custom:{},
			mendianid:0,
			pre_url: app.globalData.pre_url,
    };
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
		getdata: function () {
			var that = this;
			var bid = that.opt.bid ? that.opt.bid : 0;
			that.loading = true;
			app.get('ApiShop/fastbuy', {bid:bid,mendian_id:that.mendianid}, function (res) {
				that.loading = false;
				that.datalist = res.data;
				that.cartList = res.cartList;
				that.custom = res.custom

				//计算每个高度
				var harr = [];
				var clientwidth = uni.getSystemInfoSync().windowWidth;
				var datalist = res.data;
				for (var i = 0; i < datalist.length; i++) {
					var child = datalist[i].prolist;
					harr.push(Math.ceil(child.length) * 200 / 750 * clientwidth);
				}
				that.harr = harr;

				if(that.opt.cid){
					that.scrollToViewId = 'detail-' + that.opt.cid;
					for (var i = 0; i < datalist.length; i++) {
						if(datalist[i].id == that.opt.cid) that.currentActiveIndex = i;
					}
				}
				that.loaded();
			});
		},
    clickRootItem: function (t) {
			this.scrollState=false;
      var e = t.currentTarget.dataset;
      this.scrollToViewId = 'detail-' + e.rootItemId;
      this.currentActiveIndex = e.rootItemIndex;
			setTimeout(()=>{
				this.scrollState=true;
			},500)
    },
		addcart:function(e){
			var that = this;
			var ks = that.ks;
			var num = e.currentTarget.dataset.num;
			var proid = e.currentTarget.dataset.proid;
			var ggid = e.currentTarget.dataset.ggid;
			that.loading = true;
			app.post('ApiShop/addcart', {proid: proid,ggid: ggid,num: num}, function (res) {
				that.loading = false;
				if (res.status == 1) {
					that.getdata();
				} else {
					app.error(res.msg);
				}
			});
		},
    //加入购物车弹窗后
    afteraddcart: function (e) {
			e.hasoption = false;
			console.log('111');
      this.addcart({currentTarget:{dataset:e}});
    },
		// 批发商品
		afteraddcart2:function(){
			let that = this;
			setTimeout(() => {
				that.getdata();
			},300)
		},
    clearShopCartFn: function () {
      var that = this;
      app.post("ApiShop/cartclear", {bid:that.opt.bid}, function (res) {
        that.getdata();
      });
    },
    gopay: function () {
      var cartList = this.cartList.list;
      if (cartList.length == 0) {
        app.alert('请先添加商品到购物车');
        return;
      }
      var prodata = [];
      for (var i = 0; i < cartList.length; i++) {
        prodata.push(cartList[i].proid + ',' + cartList[i].ggid + ',' + cartList[i].num);
      }
      app.goto('/pagesB/shop/buy?frompage=fastbuy&prodata=' + prodata.join('-'));
    },
    gotoCatproductPage: function (t) {
      var e = t.currentTarget.dataset;
			if(this.bid){
				app.goto('/pages/shop/prolist?bid='+this.bid+'&cid2=' + e.id);
			}else{
				app.goto('/pages/shop/prolist?cid=' + e.id);
			}
    },
    scroll: function (e) {
		if(this.scrollState){
			var scrollTop = e.detail.scrollTop;
			var harr = this.harr;
			var countH = 0;
			for (var i = 0; i < harr.length; i++) {
			  if (scrollTop >= countH && scrollTop < countH + harr[i]) {
			    this.currentActiveIndex = i;
			    break;
			  }
			  countH += harr[i];
			}
		}
    },
		buydialogChange: function (e) {
			if(!this.buydialogShow){
				this.proid = e.currentTarget.dataset.proid;
				let index = e.currentTarget.dataset.indexs;
				this.datalist[index].prolist.forEach(item => {
					if(item.id == this.proid){
						this.productType = item.product_type;
						this.ggNum = item.gg_num;
					}
				})
			}
			this.buydialogShow = !this.buydialogShow;
		},
		handleClickMask:function(){
			this.cartListShow = !this.cartListShow;
		}
  }
};
</script>
<style>
page {position: relative;width: 100%;height: 100%;}
.container{height:100%;overflow:hidden}
.view-show{background-color: white;line-height: 1;width: 100%;height: 100%;}
.search-container {width: 100%;height: 94rpx;padding: 16rpx 23rpx 14rpx 23rpx;background-color: #fff;position: relative;overflow: hidden;border-bottom:1px solid #f5f5f5}
.search-box {display:flex;align-items:center;height:60rpx;border-radius:30rpx;border:0;background-color:#f7f7f7;flex:1}
.search-box .img{width:24rpx;height:24rpx;margin-right:10rpx;margin-left:30rpx}
.search-box .search-text {font-size:24rpx;color:#C2C2C2;width: 100%;}

.nav_left{width: 25%;height:100%;background: #ffffff;overflow-y:scroll;}
.nav_left .nav_left_items{line-height:50rpx;color:#333333;font-weight:bold;border-bottom:0px solid #E6E6E6;font-size:28rpx;position: relative;border-right:0 solid #E6E6E6;padding:25rpx 30rpx;}
.nav_left .nav_left_items.active{background: #fff;color:#333333;font-size:30rpx;font-weight:bold}
.nav_left .nav_left_items .before{display:none;position:absolute;top:50%;margin-top:-12rpx;left:10rpx;height:24rpx;border-radius:4rpx;width:8rpx}
.nav_left .nav_left_items.active .before{display:block}

.nav_right{width: 75%;height:100%;display:flex;flex-direction:column;background: #f6f6f6;box-sizing: border-box;padding:20rpx 20rpx 20rpx 20rpx}
.nav_right-content{background: #ffffff;padding:20rpx;height:100%;position:relative}
.detail-list {height:100%;overflow:scroll}
.classification-detail-item {width: 100%;overflow: visible;background:#fff}
.classification-detail-item .head {height: 82rpx;width: 100%;display: flex;align-items:center;justify-content:space-between;}
.classification-detail-item .head .txt {color:#222222;font-weight:bold;font-size:30rpx;}
.classification-detail-item .head .show-all {font-size: 26rpx;color:#949494;display:flex;align-items:center}

.product-itemlist{height: auto; position: relative;overflow: hidden; padding: 0px; display:flex;flex-wrap:wrap}
.product-itemlist .item{width:100%;display: inline-block;position: relative;margin-bottom: 12rpx;background: #fff;display:flex;padding:14rpx 0;border-radius:10rpx;border-bottom:1px solid #F8F8F8}
.product-itemlist .product-pic {width: 30%;height:0;overflow:hidden;background: #ffffff;padding-bottom: 30%;position: relative;border-radius:4px;}
.product-itemlist .product-pic .image{position:absolute;top:0;left:0;width: 100%;height:auto}
.product-itemlist .product-pic .saleimg{ position: absolute;width: 120rpx;height: auto; top: -6rpx; left:-6rpx;}
.product-itemlist .product-info {width: 70%;padding:0 10rpx 5rpx 20rpx;position: relative;}
.product-itemlist .product-info .p1 {color:#323232;font-weight:bold;font-size:28rpx;line-height:36rpx;margin-bottom:0;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:2;overflow:hidden;height:72rpx}
.product-itemlist .product-info .p2{margin-top:10rpx;height:36rpx;line-height:36rpx;overflow:hidden;}
.product-itemlist .product-info .p2 .t1{font-size:32rpx;font-weight:bold;}
.product-itemlist .product-info .p2 .t2 {margin-left:10rpx;font-size:24rpx;color: #aaa;text-decoration: line-through;/*letter-spacing:-1px*/}
.product-itemlist .product-info .p2 .t1-m {font-size: 26rpx;font-weight: bold;padding-left: 6rpx;}
.product-itemlist .product-info .p3{display:flex;align-items:center;overflow:hidden;margin-top:10rpx}
.product-itemlist .product-info .p3-1{font-size:20rpx;height:30rpx;line-height:30rpx;text-align:right;color:#999;margin-right:10rpx}
.product-itemlist .product-info .p3-2{font-size:20rpx;height:30rpx;line-height:30rpx;text-align:right;color:#777}
.product-itemlist .product-info .p4{width:56rpx;height:56rpx;border-radius:50%;position:absolute;display:relative;bottom:6rpx;right:4rpx;text-align:center;}
.product-itemlist .product-info .p4 .icon_gouwuche{font-size:32rpx;height:56rpx;line-height:56rpx}
.product-itemlist .product-info .p5{font-size:24rpx;font-weight: bold;margin: 6rpx 0;}
.product-itemlist .product-info .p6{font-size:24rpx;display: flex;flex-wrap: wrap;margin-top: 6rpx;}
.product-itemlist .product-info .p6-m{text-align: center;padding:6rpx 10rpx;border-radius: 6rpx;margin: 6rpx;}

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