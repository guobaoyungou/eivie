<template>
<view class="container">
	<block v-if="isload">
		<view class="view-show">
			<view class="topbannerbg" :style="business.pic?'background:url('+business.pic+') 100%':''"></view>
			<view class="topbannerbg2"></view>
			<view class="topbanner">
				<view class="left"><image class="img" :src="business.logo"/></view>
				<view class="right">
					<view class="f1">{{business.name}}</view>
					<view class="f2">{{business.desc}}</view>
					<view class="f2" style="opacity:0.9" v-if="business.address" @tap="openLocation" :data-latitude="business.latitude" :data-longitude="business.longitude" :data-company="business.name" :data-address="business.address"><text class="iconfont icondingwei"></text>{{business.address}}</view>
					<!-- <view class="f3"><view class="flex1"></view><view class="t2">收藏<image class="img" src="/static/img/like1.png"/></view></view> -->
				</view>
			</view>
			<view class="navtab">
				<block v-if="paylist && paylist.length > 0">
				<block v-for="(item, index) in menuList" :key="index">
					<view :class="'item ' + (st == item.st ? 'on' : '')" @tap="changetab" :data-st="item.st">{{item.alias ? item.alias : item.name}}<view class="after" :style="{background:t('color1')}"></view></view>
				</block>
					<!-- <view class="item" :class="st==0?'on':''" @tap="changetab" data-st="0">商品<view class="after" :style="{background:t('color1')}"></view></view>
					<view class="item" :class="st==1?'on':''" @tap="changetab" data-st="1">商家信息<view class="after" :style="{background:t('color1')}"></view></view>
					<view class="item" :class="st==3?'on':''" @tap="changetab" data-st="3" v-if="paylist && paylist.length > 0">会员支付<view class="after" :style="{background:t('color1')}"></view></view> -->
				</block>
				<block v-else>
					<view class="item" :class="st==0?'on':''" @tap="changetab" data-st="0">商品<view class="after" :style="{background:t('color1')}"></view></view>
					<view class="item" :class="st==1?'on':''" @tap="changetab" data-st="1">商家信息<view class="after" :style="{background:t('color1')}"></view></view>
					<view class="item" :class="st==2?'on':''" @tap="changetab" data-st="2" v-if="!paylist || paylist.length == 0">评价<view class="after" :style="{background:t('color1')}"></view></view>
				</block>
			</view>
			<view v-if="st==0" class="content" style="overflow:hidden;display:flex;margin-top:86rpx" :style="{height:'calc(100% - '+(menuindex>-1?550:450)+'rpx)'}">
				<scroll-view class="nav_left" :scrollWithAnimation="animation" scroll-y="true">
					<block v-for="(item, index) in datalist" :key="index" >
					<view class="nav_left_items" :class="index===currentActiveIndex?'active':''" :style="{color:index===currentActiveIndex?t('color1'):'#333'}" @tap="clickRootItem" :data-root-item-id="item.id" :data-root-item-index="index"><view class="before" :style="{background:t('color1')}"></view>{{item.name}}</view>
					</block>
				</scroll-view>
				<view class="nav_right">
					<view class="nav_right-content">
						<scroll-view @scroll="scroll" class="detail-list" :scrollIntoView="scrollToViewId" :scrollWithAnimation="animation" scroll-y="true">
							<view v-for="(detail, index) in datalist" :key="index" class="classification-detail-item">
								<view class="head" :data-id="detail.id" :id="'detail-' + detail.id">
									<view class="txt">{{detail.name}}</view>
									<!-- <view class="show-all" @tap="gotoCatproductPage">查看全部<text class="iconfont iconjiantou"></text></view> -->
								</view>
								<view class="product-itemlist">
									<view class="item" v-for="(item,indexs) in detail.prolist" :key="indexs">
										<view class="product-pic" @click="goto" :data-url="'product?id='+item.id">
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
											</view>
											<!-- <view class="p4" :style="{background:'rgba('+t('color1rgb')+',0.1)',color:t('color1')}" @click.stop="buydialogChange" :data-proid="item.id"><text class="iconfont icon_gouwuche"></text></view> -->
											<view class="addnum" v-if="!item.price_type">
												<view class="countbut-view" style="padding-right: 10rpx;" v-if="numtotal[item.id]>0" @tap.stop="addcart" data-num="-1" :data-proid="item.id" :data-stock="item.stock">
													<view class="minus">-</view>
												</view>
												<view v-if="numtotal[item.id]>0" class="input-view" :style="{width: Number(numtotal[item.id]) >= 100 ? '75rpx':'60rpx'}">
													<input class="input-class" type="number" v-model="numtotal[item.id]" @blur='inpurCart($event,item.id,item.gglist[0].id)' :style="{width: Number(numtotal[item.id]) >= 100 ? '75rpx': (Number(numtotal[item.id]) >= 10 ? '45rpx':'30rpx')}" />
												</view>
												<view class="countbut-view" style="padding-left: 10rpx;" v-if="item.ggcount>1" @tap.stop="buydialogChange" :data-proid="item.id" :data-stock="item.stock">
													<view class="plus">+</view>
												</view>
												<view class="countbut-view" style="padding-left: 10rpx;" v-else @tap.stop="addcart" data-num="1" :data-proid="item.id" :data-ggid="item.gglist[0].id" :data-stock="item.stock">
													<view class="plus">+</view>
												</view>
											</view>
										</view>
									</view>
								</view>
							</view>
						</scroll-view>
					</view>
				</view>
			</view>
			<view v-if="st==1" class="content1" style="margin-top:86rpx;padding-top:20rpx">
				<view class="item flex-col">
					<text class="t1">联系电话</text>
					<text class="t2">{{business.tel}}</text>
				</view>
				<view class="item flex-col">
					<text class="t1">商家地址</text>
					<text class="t2">{{business.address}}</text>
				</view>
				<view class="item flex-col">
					<text class="t1">商家简介</text>
					<view class="t2"><parse :content="business.content"></parse></view>
				</view>
				<view class="item flex-col" v-if="bid!=0">
					<text class="t1">营业时间</text>
					<text class="t2">{{business.start_hours}} 至 {{business.end_hours}}</text>
				</view>
        <view style="width: 100%;height: 140rpx;clear: both;"></view>
			</view>
			<view v-if="st==2" class="content2" style="margin-top:86rpx;padding-top:20rpx">
				<view class="comment" style="height:calc(100vh - 460rpx);overflow:scroll">
					<block v-if="commentlist.length>0">
						
						<view class="title">
							<view class="f1">评价({{business.comment_num}})</view>
							<view class="f2" @tap="goto">好评率 <text :style="{color:t('color1')}">{{business.comment_haopercent}}%</text></view>
						</view>
						<view v-for="(item, index) in commentlist" :key="index" class="item">
							<view class="f1">
								<image class="t1" :src="item.headimg"/>
								<view class="t2">{{item.nickname}}</view>
								<view class="flex1"></view>
								<view class="t3"><image class="img" v-for="(item2,index2) in [0,1,2,3,4]" :key="index2"  :src="pre_url+'/static/img/star' + (item.score>item2?'2native':'') + '.png'"/></view>
							</view>
							<view style="color:#777;font-size:22rpx;">{{item.createtime}}</view>
							<view class="f2">
								<text class="t1">{{item.content}}</text>
								<view class="t2">
									<block v-if="item.content_pic!=''">
										<block v-for="(itemp, index) in item.content_pic" :key="index">
											<view @tap="previewImage" :data-url="itemp" :data-urls="item.content_pic">
												<image :src="itemp" mode="widthFix"/>
											</view>
										</block>
									</block>
								</view>
							</view>
							<view class="f3" v-if="item.reply_content">
								<view class="arrow"></view>
								<view class="t1">商家回复：{{item.reply_content}}</view>
							</view>
						</view>
						<view style="width:100%;height:120rpx"></view>
					</block>
					<block v-else>
						<nodata v-show="comment_nodata"></nodata>
					</block>
				</view>
			</view>
			<view v-if="st==3" class="content2" style="margin-top:86rpx;padding-top:20rpx">
				<view class="paylist" style="height:calc(100vh - 460rpx);overflow:scroll">
					<view class="item" v-for="(item,index) in paylist" :key="index" @click="createpayorder" :data-id="item.id">
						<view class="f1">
							<image class="image" :src="item.pic" mode="widthFix"/>
						</view>
						<view class="f2">
							<view class="t1"><text>{{item.name}}</text></view>
							<view class="t2" :style="{color:t('color1')}">￥{{item.market_price}}<text style="padding:0 4rpx">/</text><text style="font-size:24rpx">会员价￥</text>{{item.sell_price}}</view>
						</view>
						<image :src="pre_url+'/static/img/arrowright.png'" class="arrowright"/>
					</view>
				</view>
			</view>
		</view>
		<buydialog v-if="buydialogShow" :proid="proid" @buydialogChange="buydialogChange" @addcart="afteraddcart" :menuindex="menuindex" btntype="1" :needaddcart="false"></buydialog>
		<view style="height:auto;position:relative">
			<view style="width:100%;height:100rpx"></view>
			<view class="footer flex" :class="menuindex>-1?'tabbarbot':'notabbarbot'">
				<view class="cart_ico" :style="{background:'linear-gradient(0deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'}" @tap.stop="handleClickMask"><image class="img" :src="pre_url+'/static/img/cart.png'" /><view class="cartnum" :style="{background:t('color1')}" v-if="cartList.total>0">{{cartList.total}}</view></view>
				<view class="text1">合计</view>
				<view class="text2" :style="{color:t('color1')}"><text style="font-size:20rpx">￥</text>{{cartList.totalprice}}</view>
				<view class="flex1"></view>
				<view class="op" :style="{background:'linear-gradient(270deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'}" @tap="gopay">去结算</view>
			</view>
		</view>
	<uni-popup ref="popup" type="bottom" :animation='false'>
		<view class="popup-content-fastbuy" :class="menuindex>-1?'tabbarbot':'notabbarbot'">
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
								<view class="minus" @tap="addcart" data-num="-1" :data-proid="cart.proid" :data-ggid="cart.ggid" :data-stock="cart.guige.stock"><image class="img" :src="pre_url+'/static/img/cart-minus.png'"/></view>
								<view class="input-view" :style="{width: Number(cart.num) >= 100 ? '75rpx':'60rpx'}">
									<input class="input-class" type="number" v-model="cart.num" @blur='inpurCart($event,cart.proid,cart.ggid)' />
								</view>
								<view class="plus" @tap="addcart" data-num="1" :data-proid="cart.proid" :data-ggid="cart.ggid" :data-stock="cart.guige.stock"><image class="img" :src="pre_url+'/static/img/cart-plus.png'"/></view>
							</view>
						</view>
					</block>
					<block v-if="!cartList.list.length">
						<text class="nopro">暂时没有商品喔~</text>
					</block>
				</scroll-view>
			</view>
		</view>
	</uni-popup>
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
			
			st:0,
			buydialogShow:false,
			harr:[],
			business:{},
      datalist: [],
			menuList:[],
			cartList:{},
			numtotal:[],
			proid:'',
			totalprice:'0.00',
      currentActiveIndex: 0,
      animation: true,
      scrollToViewId: "",
			commentlist:[],
			comment_nodata:false,
			comment_nomore:false,
			paylist:[],
			bid:0,
			scrollState:true,
			custom:{},
			mendianid:0,
			pre_url: app.globalData.pre_url,
    };
  },

  onLoad: function (opt) {
		this.opt = app.getopts(opt);
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
	onReachBottom: function () {
		if (this.st == 2) {
			if (!this.comment_nodata && !this.comment_nomore) {
				this.pagenum = this.pagenum + 1;
				this.getCommentList(true);
			}
		}
	},
  methods: {
		inpurCart(e,proId,ggId){
			var that = this;
			var num = e.detail.value || 0;
			that.loading = true;
			app.post('ApiShop/addcart', {proid: proId,ggid: ggId,input_num: num}, function (res) {
				that.loading = false;
				if (res.status == 1) {
					that.getdata();
				} else {
					that.getdata();
					app.error(res.msg);
				}
			});
		},
		getdata: function () {
			var that = this;
			that.loading = true;
			app.get('ApiShop/fastbuy2', {bid:that.opt.bid,mendian_id:that.mendianid}, function (res) {
				that.cartList = {};
				that.numtotal = [];
				that.loading = false;
				that.business = res.business
				that.datalist = res.data;
				that.cartList = res.cartList;
				that.menuList = res.menuList;
				that.numtotal = res.numtotal;
				that.paylist = res.paylist;
				that.bid = res.bid;
				that.custom = res.custom;
				uni.setNavigationBarTitle({
					title: that.business.name
				});
				
				if(that.menuList.length > 0) {
					that.st = that.menuList[0].st;
				}

				//计算每个高度
				var harr = [];
				var clientwidth = uni.getWindowInfo().windowWidth;
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
					// 若有初始cid参数，首次刷新后清空参数
					that.opt.cid = '';
				}
				that.loaded();
			});
		},
		changetab:function(e){
			this.st = e.currentTarget.dataset.st;
			this.pagenum = 1;
			this.commentlist = [];
			uni.pageScrollTo({
				scrollTop: 0,
				duration: 0
			});
			this.getCommentList();
		},
		getCommentList: function (loadmore) {
			if(!loadmore){
				this.pagenum = 1;
				this.commentlist = [];
			}
			var that = this;
			var pagenum = that.pagenum;
			var st = that.st;
			that.loading = true;
			that.comment_nodata = false;
			that.comment_nomore = false;
			if(that.bid == 0){
				app.post('ApiShop/commentlist', {pagenum: pagenum,proid:0}, function (res) {
					that.loading = false;
					uni.stopPullDownRefresh();
					var data = res.data;
					if (pagenum == 1) {
						that.commentlist = data;
						if (data.length == 0) {
							that.comment_nodata = true;
						}
					}else{
						if (data.length == 0) {
							that.comment_nomore = true;
						} else {
							var commentlist = that.commentlist;
							var newdata = commentlist.concat(data);
							that.commentlist = newdata;
						}
					}
				});
			}else{
				app.post('ApiBusiness/getdatalist', {id: that.business.id,st: st,pagenum: pagenum}, function (res) {
					that.loading = false;
					uni.stopPullDownRefresh();
					var data = res.data;
					if (pagenum == 1) {
						that.commentlist = data;
						if (data.length == 0) {
							that.comment_nodata = true;
						}
					}else{
						if (data.length == 0) {
							that.comment_nomore = true;
						} else {
							var commentlist = that.commentlist;
							var newdata = commentlist.concat(data);
							that.commentlist = newdata;
						}
					}
				});
			}
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
      this.addcart({currentTarget:{dataset:e}});
    },
    clearShopCartFn: function () {
			var that = this;
			uni.showModal({
				title: '提示',
				content: '确定清空此购物车？',
				success: function (ress) {
					if (ress.confirm) {
						app.post("ApiShop/cartclear", {bid:that.opt.bid}, function (res) {
						  that.getdata();
						});
					} else if (ress.cancel) {
						console.log('用户点击取消');
					}
				}
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
      app.goto('prolist?cid=' + e.id);
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
				this.proid = e.currentTarget.dataset.proid
			}
			this.buydialogShow = !this.buydialogShow;
		},
		handleClickMask:function(){
			this.$refs.popup.open();
		},
		openLocation:function(e){
			//console.log(e)
			var latitude = parseFloat(e.currentTarget.dataset.latitude)
			var longitude = parseFloat(e.currentTarget.dataset.longitude)
			var address = e.currentTarget.dataset.address
			uni.openLocation({
			 latitude:latitude,
			 longitude:longitude,
			 name:address,
			 scale: 13
		 })		
		},
		createpayorder:function(e){
			var id = e.currentTarget.dataset.id
			app.showLoading('提交中');
			app.post('ApiPlugBusinessqr/createpayorder',{id:id},function(res){
				if(res.status == 0) {
						app.error(res.msg);
				} else {
					app.goto('/pagesExt/pay/pay?id=' + res.payorderid);
				}
			})
		}
  }
};
</script>
<style>
page {position: relative;width: 100%;height: 100%;}
.container{height:100%;position: relative;}

.topbannerbg{width:100%;height:264rpx;background:#fff;}
.topbannerbg2{position:absolute;z-index:7;width:100%;height:264rpx;background:rgba(0,0,0,0.7);top:0}
.topbanner{position:absolute;z-index:8;width:100%;display:flex;padding:40rpx 20rpx;top:0}
.topbanner .left{width:160rpx;height:160rpx;flex-shrink:0;margin-right:20rpx}
.topbanner .left .img{width:100%;height:100%;border-radius:50%}
.topbanner .right{display:flex;flex-direction:column;padding:20rpx 0}
.topbanner .right .f1{font-size:36rpx;font-weight:bold;color:#fff}
.topbanner .right .f2{font-size:22rpx;color:#fff;opacity:0.7;margin-top:20rpx;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:1;overflow:hidden;line-height:30rpx;}
.topbanner .right .f3{width:100%;display:flex;padding-right:20rpx;margin-top:10rpx}
.topbanner .right .f3 .t2{display:flex;align-items:center;font-size:24rpx;color:rgba(255,255,255,0.9)}
.topbanner .right .f3 .img{width:32rpx;height:32rpx;margin-left:10rpx}

.navtab{display:flex;width:100%;height:110rpx;background: #fff;position:absolute;z-index:9;padding:0 50rpx;border-radius:24rpx 24rpx 0 0;margin-top:-24rpx;}
.navtab .item{flex:1;font-size:32rpx; text-align:center; color:#222222; height: 110rpx; line-height: 110rpx;overflow: hidden;position:relative}
.navtab .item .after{display:none;position:absolute;left:50%;margin-left:-20rpx;bottom:20rpx;height:4px;border-radius:2px;width:40rpx}
.navtab .on{font-size:36rpx;font-weight:bold}
.navtab .on .after{display:block}

.content1 .item{display:flex;flex-direction:column;width:100%;padding:0 40rpx;margin-top:40rpx}
.content1 .item:last-child{ border-bottom: 0;}
.content1 .item .t1{width:200rpx;color:#2B2B2B;font-weight:bold;font-size:30rpx;height:60rpx;line-height:60rpx}
.content1 .item .t2{color:#2B2B2B;font-size:24rpx;line-height:30rpx}

.content2 .comment{padding:0 10rpx;overflow:scroll}

.content2 .comment .title{height:90rpx;line-height:90rpx;border-bottom:1px solid #DDDDDD;display:flex;margin:0 3%}
.content2 .comment .title .f1{flex:1;color:#111111;font-weight:bold;font-size:30rpx}
.content2 .comment .title .f2{color:#333;font-weight:bold;font-size:28rpx;display:flex;align-items:center}

.content2 .comment .item{background-color:#fff;padding:10rpx 20rpx;display:flex;flex-direction:column;}
.content2 .comment .item .f1{display:flex;width:100%;align-items:center;padding:10rpx 0;}
.content2 .comment .item .f1 .t1{width:70rpx;height:70rpx;border-radius:50%;}
.content2 .comment .item .f1 .t2{padding-left:10rpx;color:#333;font-weight:bold;font-size:30rpx;}
.content2 .comment .item .f1 .t3{text-align:right;}
.content2 .comment .item .f1 .t3 .img{width:24rpx;height:24rpx;margin-left:10rpx}
.content2 .comment .item .score{ font-size: 24rpx;color:#f99716;}
.content2 .comment .item .score image{ width: 140rpx; height: 50rpx; vertical-align: middle;  margin-bottom:6rpx; margin-right: 6rpx;}
.content2 .comment .item .f2{display:flex;flex-direction:column;width:100%;padding:10rpx 0;}
.content2 .comment .item .f2 .t1{color:#333;font-size:28rpx;}
.content2 .comment .item .f2 .t2{display:flex;width:100%}
.content2 .comment .item .f2 .t2 image{width:100rpx;height:100rpx;margin:10rpx;}
.content2 .comment .item .f2 .t3{color:#aaa;font-size:24rpx;}
.content2 .comment .item .f2 .t3{color:#aaa;font-size:24rpx;}
.content2 .comment .item .f3{width:100%;padding:10rpx 0;position:relative}
.content2 .comment .item .f3 .arrow{width: 16rpx;height: 16rpx;background:#eee;transform: rotate(45deg);position:absolute;top:0rpx;left:36rpx}
.content2 .comment .item .f3 .t1{width:100%;border-radius:10rpx;padding:10rpx;font-size:22rpx;color:#888;background:#eee}


.view-show{background-color: white;line-height: 1;width: 100%;height: 100%;}
.search-container {width: 100%;height: 94rpx;padding: 16rpx 23rpx 14rpx 23rpx;background-color: #fff;position: relative;overflow: hidden;border-bottom:1px solid #f5f5f5}
.search-box {display:flex;align-items:center;height:60rpx;border-radius:30rpx;border:0;background-color:#f7f7f7;flex:1}
.search-box .img{width:24rpx;height:24rpx;margin-right:10rpx;margin-left:30rpx}
.search-box .search-text {font-size:24rpx;color:#C2C2C2;width: 100%;}

.nav_left{width: 25%;height:100%;background:#F6F6F6;overflow-y:scroll;}
.nav_left .nav_left_items{line-height:50rpx;color:#333333;font-weight:bold;border-bottom:0px solid #E6E6E6;font-size:28rpx;position: relative;border-right:0 solid #E6E6E6;padding:25rpx 30rpx;}
.nav_left .nav_left_items.active{background: #fff;color:#333333;font-size:28rpx;font-weight:bold}
.nav_left .nav_left_items .before{display:none;position:absolute;top:50%;margin-top:-22rpx;left:0rpx;height:44rpx;border-radius:4rpx;width:6rpx}
.nav_left .nav_left_items.active .before{display:block}

.nav_right{width: 75%;height:100%;display:flex;flex-direction:column;background: #fff;box-sizing: border-box;padding:0 0 0 0}
.nav_right-content{background: #ffffff;padding:0 20rpx;height:100%;position:relative}
.detail-list {height:100%;overflow:scroll}
.classification-detail-item {width: 100%;overflow: visible;background:#fff}
.classification-detail-item .head {height: 82rpx;width: 100%;display: flex;align-items:center;justify-content:space-between;}
.classification-detail-item .head .txt {color:#222222;font-weight:bold;font-size:28rpx;}
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
.product-itemlist .product-info .p2 .t1-m {font-size: 26rpx;font-weight: bold;padding-left: 6rpx;}
.product-itemlist .product-info .p2 .t2 {margin-left:10rpx;font-size:24rpx;color: #aaa;text-decoration: line-through;/*letter-spacing:-1px*/}
.product-itemlist .product-info .p3{display:flex;align-items:center;overflow:hidden;margin-top:10rpx}
.product-itemlist .product-info .p3-1{font-size:20rpx;height:30rpx;line-height:30rpx;text-align:right;color:#999}
.product-itemlist .product-info .p4{width:48rpx;height:48rpx;border-radius:50%;position:absolute;display:relative;bottom:6rpx;right:4rpx;text-align:center;}
.product-itemlist .product-info .p4 .icon_gouwuche{font-size:28rpx;height:48rpx;line-height:48rpx}
.product-itemlist .product-info .p5{font-size:24rpx;font-weight: bold;margin: 6rpx 0;}
.product-itemlist .product-info .p6{font-size:24rpx;display: flex;flex-wrap: wrap;margin-top: 6rpx;}
.product-itemlist .product-info .p6-m{text-align: center;padding:6rpx 10rpx;border-radius: 6rpx;margin: 6rpx;}
.product-itemlist .addnum {position: absolute;right:10rpx;bottom:20rpx;font-size: 32rpx;color: #666;width: auto;display:flex;align-items:center}
.product-itemlist .addnum .countbut-view{padding: 5rpx;}
.product-itemlist .addnum .plus {width:44rpx;height:44rpx;background:#FD4A46;color:#FFFFFF;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:32rpx;}
.product-itemlist .addnum .minus {width:44rpx;height:44rpx;background:#FFFFFF;color:#FD4A46;border:1px solid #FD4A46;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:32rpx}
.product-itemlist .addnum .img{width:24rpx;height:24rpx}
.product-itemlist .addnum .input-view {margin: 0 2rpx;}
.product-itemlist .addnum .input-class{color:#2B2B2B;font-size:32rpx;text-align: center;width: 100%;height: 32rpx;margin: 0 auto;border-radius: 5rpx;}

.prolist {max-height: 620rpx;min-height: 320rpx;overflow: hidden;padding:0rpx 20rpx;font-size: 28rpx;border-bottom: 1px solid #e6e6e6;}
.prolist .nopro {text-align: center;font-size: 26rpx;display: block;margin: 80rpx auto;}
.prolist .proitem{position: relative;padding:10rpx 0;display:flex;border-bottom:1px solid #eee}
.prolist .proitem .pic{width: 120rpx;height: 120rpx;margin-right: 20rpx;}
.prolist .proitem .con{padding-right:180rpx;padding-top:10rpx}
.prolist .proitem .con .f1{color:#323232;font-size:26rpx;line-height:32rpx;margin-bottom: 10rpx;margin-top: -6rpx;display: -webkit-box;-webkit-box-orient: vertical;-webkit-line-clamp: 2;overflow: hidden;}
.prolist .proitem .con .f2{font-size: 24rpx;line-height:28rpx;color: #999;display: -webkit-box;-webkit-box-orient: vertical;-webkit-line-clamp: 1;overflow: hidden;}
.prolist .proitem .addnum {position: absolute;right: 20rpx;bottom:50rpx;font-size: 30rpx;color: #666;width: auto;display:flex;align-items:center}
.prolist .proitem .addnum .plus,.minus {width:52rpx;height:40rpx;background:#F6F8F7;display:flex;align-items:center;justify-content:center;}
.prolist .proitem .addnum .img{width:24rpx;height:24rpx}
.prolist .tips {font-size: 22rpx;color: #666;text-align: center;line-height: 56rpx;background: #f5f5f5;}
.prolist .proitem .addnum .input-view {margin: 0 10rpx;font-weight:bold;}
.prolist .proitem .addnum .input-class{color:#2B2B2B;font-size:24rpx;text-align: center;width: 100%;}

.paylist{padding:20rpx 30rpx;background:#f5f5f5}
.paylist .item{width:100%;display: inline-block;position: relative;margin-bottom: 20rpx;background: #fff;display:flex;padding:0;border-radius:10rpx;border-bottom:1px solid #F8F8F8;overflow:hidden}
.paylist .item .f1{width: 30%;height:0;overflow:hidden;background: #ffffff;padding-bottom: 30%;position: relative;border-radius:4px;}
.paylist .item .f1 .image{position:absolute;top:0;left:0;width: 100%;height:auto}
.paylist .item .f2{width: 70%;padding:30rpx 20rpx 30rpx 40rpx;position: relative;}
.paylist .item .f2 .t1 {color:#323232;font-weight:bold;font-size:40rpx;line-height:50rpx;margin-bottom:0;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:2;overflow:hidden;height:100rpx}
.paylist .item .f2 .t2{font-size:36rpx;line-height:46rpx;}
.paylist .item .arrowright{position:absolute;top:90rpx;right:20rpx;width:40rpx;height:40rpx}

.footer {width: 100%;background: #fff;margin-top: 5px;position: fixed;left: 0px;bottom: 0px;z-index:8;display:flex;align-items:center;padding:0 20rpx;border-top:1px solid #EFEFEF}
.footer .cart_ico{width:64rpx;height:64rpx;border-radius: 10rpx;display:flex;align-items:center;justify-content:center;position:relative}
.footer .cart_ico .img{width:36rpx;height:36rpx;}
.footer .cart_ico .cartnum{position:absolute;top:-17rpx;right:-17rpx;width:34rpx;height:34rpx;border:1px solid #fff;border-radius:50%;display:flex;align-items:center;justify-content:center;overflow:hidden;font-size:20rpx;font-weight:bold;color:#fff}
.footer .text1 {height: 100rpx;line-height: 100rpx;color:#555555;font-weight:bold;font-size: 30rpx;margin-left:40rpx;margin-right:10rpx}
.footer .text2 {font-size: 32rpx;font-weight:bold}
.footer .op{width: 200rpx;height: 72rpx;line-height:72rpx;border-radius: 36rpx;font-weight:bold;color:#fff;font-size:28rpx;text-align:center}
::-webkit-scrollbar{width: 0;height: 0;color: transparent;}
.popup-content-fastbuy{background: #fff;border-radius:20rpx 20rpx 0 0;}
.uni-popup__wrapper-box{background: #fff !important;border-radius:20rpx 20rpx 0 0;}
</style>