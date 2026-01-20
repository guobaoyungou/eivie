<template>
<view class="container">
	<block v-if="isload">
		<view class="search-container" :style="history_show?'height:100%;':''">
			<view class="topsearch flex-y-center">
				<view class="f1 flex-y-center">
					<image class="img" :src="pre_url+'/static/img/search_ico.png'"></image>
					<input :value="keyword" placeholder="搜索感兴趣的商品" placeholder-style="font-size:24rpx;color:#C2C2C2" @confirm="searchConfirm" @input="searchChange" @focus="searchFocus"></input>
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
				<scroll-view scroll-y="true" class="filter-scroll-view filter-page">
					<view class="filter-scroll-view-box">
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
					</view>
				</scroll-view>
			</uni-drawer>

			
		</view>
		<view class="product-container">
			<block v-if="datalist && datalist.length>0">
			<view style="width:100%">
				<view class="dp-product-normal-item">
					<!-- @click="goto" :data-url="'/pages/shop/product?id='+item[idfield]" -->
					<view class="item" v-for="(item,index) in datalist" :style="'background:'+probgcolor+';'+(showstyle==2 ? 'width:49%;margin-right:'+(index%2==0?'2%':0) : (showstyle==3 ? 'width:32%;margin-right:'+(index%3!=2?'2%':0) :'width:100%'))" :key="item.id">
						<view class="product-pic" >
							<image class="image" :src="item.pic" mode="widthFix"/>
							<image class="saleimg" :src="saleimg" v-if="saleimg!=''" mode="widthFix"/>
						</view>
						<view class="product-info">
							<view class="p1" v-if="item.name">{{item.name}}</view>
							<!-- 是否显示商家 距离 佣金 S-->
							<view class="binfo flex-bt" v-if="(showbname=='1' || showbdistance=='1') && item.binfo">
									<view class="flex-y-center b1">
										<block v-if="showbname=='1'">
											<image :src="item.binfo.logo" class="t1">
											<text class="t2">{{item.binfo.name}}</text>
										</block>
									</view>
									<view class="b2 t2" v-if="showbdistance=='1' && item.binfo.distance">{{item.binfo.distance}}</view>
							</view>
							<view class="couponitem" v-if="showcommission == 1 && item.commission_price>0">
								<view class="f1">
									<view class="t" :style="{background:'rgba('+t('color2rgb')+',0.1)',color:t('color2')}">
										<text>{{t('佣金')}}{{item.commission_price}}{{item.commission_desc}}</text>
									</view>
								</view>
							</view>
							<!-- 是否显示商家 距离 佣金 E-->
			                
			        <view v-if="showstyle==2">
			            <view class="field_buy" v-if="params.brand == 1 && item.brand">
			                <span style="width: 80rpx">品牌：</span>
			                <span>{{item.brand}}</span>
			            </view>
			            <view  class="field_buy" v-if="params.barcode == 1 && item.barcode">
			                <span style="width: 80rpx">货号：</span>
			                <span>{{item.barcode}}</span>
			            </view>
			            <view  class="field_buy" v-if="params.guige == 1 && item.ggname">
			                <span style="width: 80rpx"> 规格：</span>
			                <span>{{item.ggname}}</span>
			            </view>
			            <view  class="field_buy" v-if="params.unit == 1 && item.unit">
			                <span style="width: 80rpx"> 单位：</span>
			                <span>{{item.unit}}</span>
			            </view>
			            <view  class="field_buy" v-if="params.ggstock == 1">
			                <span style="width: 80rpx"> 库存：</span>
			                <span>{{item.ggstock}}</span>
			            </view>
			            <view  class="field_buy" v-if="params.valid_time == 1 && item.valid_time">
			                <span style="width: 80rpx"> 有效期：</span>
			                <span>{{item.valid_time}}</span>
			            </view>
			            <view  class="field_buy" v-if="params.remark == 1 && item.remark">
			                <span style="width: 80rpx"> 备注：</span>
			                <span>{{item.remark}}</span>
			            </view>
			        </view>
			        <view v-if="(showstyle=='2' || showstyle=='3') && item.price_type != 1 && item.show_cost == '1'" :style="{color:item.cost_color?item.cost_color:'#999',fontSize:'36rpx'}"><text style="font-size: 24rpx;">{{item.cost_tag}}</text>{{item.cost_price}}</view>      
							<view class="p2">
								<view class="p2-1" :class="params.style=='1'?'flex-bt flex-y-center':''" v-if="showprice != '0' && ( item.price_type != 1 || item.sell_price > 0)">
									<view v-if="showstyle=='1' && item.price_type != 1 && item.show_cost=='1'" :style="{color:item.cost_color?item.cost_color:'#999',fontSize:'36rpx'}"><text style="font-size: 24rpx;">{{item.cost_tag}}</text>{{item.cost_price}}</view>
									<view class="flex-y-center" v-if="(!item.show_sellprice || (item.show_sellprice && item.show_sellprice==true)) || item.usd_sellprice">
										<view class="t1" :style="{color:item.price_color?item.price_color:t('color1')}">
											<block v-if="item.usd_sellprice">
												<text style="font-size:24rpx">$</text>{{item.usd_sellprice}}
												<text style="font-size: 28rpx;"><text style="font-size:24rpx">￥</text>{{item.sell_price}}</text><text style="font-size:24rpx" v-if="item.product_unit">/{{item.product_unit}}</text>
											</block>
											<block v-else>
												<text style="font-size:24rpx">{{item.price_tag?item.price_tag:'￥'}}</text>{{item.sell_price}}<text style="font-size:24rpx" v-if="item.product_unit">/{{item.product_unit}}</text>
											</block>
										</view>
										<text class="t2" v-if="(!item.show_sellprice || (item.show_sellprice && item.show_sellprice==true)) && item.market_price*1 > item.sell_price*1 && showprice == '1'">￥{{item.market_price}}</text>
										<text class="t3" v-if="item.juli" style="color:#888;">{{item.juli}}</text> 
									</view>
								</view>
								<view class="p2-1" v-if="item.xunjia_text && item.price_type == 1 && item.sell_price <= 0" style="height: 50rpx;line-height: 44rpx;">
									<text v-if="showstyle!=1" class="t1" :style="{color:t('color1'),fontSize:'30rpx'}">询价</text>
										<text v-if="showstyle==1" class="t1" :style="{color:t('color1')}">询价</text>
										<block v-if="item.xunjia_text && item.price_type == 1">
											<view class="lianxi" :style="{background:t('color1')}" @tap.stop="showLinkChange" :data-lx_name="item.lx_name" :data-lx_bid="item.lx_bid" :data-lx_bname="item.lx_bname" :data-lx_tel="item.lx_tel" data-btntype="2">{{item.xunjia_text?item.xunjia_text:'联系TA'}}</view>
										</block>
								</view>
							</view>
							<view class="p1" v-if="item.merchant_name" style="color: #666;font-size: 24rpx;white-space: nowrap;text-overflow: ellipsis;margin-top: 6rpx;height: 30rpx;line-height: 30rpx;font-weight: normal;"><text>{{item.merchant_name}}</text></view>
							<view class="p1" v-if="item.main_business" style="color: #666;font-size: 24rpx;margin-top: 4rpx;font-weight: normal;"><text>{{item.main_business}}</text></view>
							<text class="p3" v-if="item.product_type == 3">手工费: ￥{{item.hand_fee?item.hand_fee:0}}</text>
							<view class="p3" v-if="showsales=='1' && item.sales>0">已售{{item.sales}}件</view>
							<view v-if="(showsales !='1' ||  item.sales<=0) && item.main_business" style="height: 44rpx;"></view>
			        <view v-if="params.style=='2' && params.nowbuy == 1" @click.stop="buydialogChange" data-btntype="2" :data-proid="item[idfield]" class="nowbuy" :style="{background:'rgba('+t('color1rgb')+',0.1)',color:t('color1')}" >
			            立即购买
			        </view>
							<view class="p4" :style="params.style=='2' && params.nowbuy == 1?'bottom:24rpx;background:rgba('+t('color1rgb')+',0.1);color:'+t('color1'):'background:rgba('+t('color1rgb')+',0.1);color:'+t('color1')" v-if="showcart==1 && !item.price_type && item.hide_cart!=true && !setCoupon" @click.stop="buydialogChange" data-btntype="1" :data-proid="item.id"><text class="iconfont icon_gouwuche"></text></view>
							<view class="addbut" :style="{background:'linear-gradient(270deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'}" v-else @click="couponAddChange(item)">添加</view>
							<!-- <view class="p4" :style="params.style=='2' && params.nowbuy == 1?'bottom:24rpx;background:rgba('+t('color1rgb')+',0.1);color:'+t('color1'):'background:rgba('+t('color1rgb')+',0.1);color:'+t('color1')" v-if="showcart==2 && !item.price_type && item.hide_cart!=true" @click.stop="buydialogChange" data-btntype="1" :data-proid="item[idfield]"><image :src="cartimg" class="img"/></text></view> -->
			      </view>
						<view class="bg-desc" v-if="item.hongbaoEdu > 0" :style="{background:'linear-gradient(90deg,'+t('color2')+' 0%,rgba('+t('color2rgb')+',0.8) 100%)'}">可获额度 +{{item.hongbaoEdu}}</view>
					</view>
				</view>
				<buydialog v-if="buydialogShow" :proid="proid" @buydialogChange="buydialogChange" @addcart="afteraddcart" :menuindex="menuindex" btntype="1" :needaddcart="false"></buydialog>
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
			</view>
			
			
				<!-- <dp-product-item v-if="productlisttype=='item2'" :data="datalist" :menuindex="menuindex"></dp-product-item> -->
				<!-- <dp-product-itemlist v-if="productlisttype=='itemlist'" :data="datalist" :menuindex="menuindex"></dp-product-itemlist> -->
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
			area:'',
			
			showstyle:2,
			showcommission: 0,
			showprice:1,
			params:{},
			showLinkStatus:false,
			showprice:1,
			showcost:0,
			showsales:1,
			showcart:1,
			buydialogShow:false,
			proid:0,
			mid:'',
			setCoupon:false,
			showname:1,
      frompage:''
    };
  },
  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		if(opt.coupon == 'false') this.setCoupon = true;
		this.oldcid = this.opt.cid || '';
		this.catchecid = this.opt.cid;
		this.cid = this.opt.cid;
		this.cid2 = this.opt.cid2 || '';
		this.oldcid2 = this.opt.cid2 || '';
		this.catchecid2 = this.opt.cid2;
		this.gid = this.opt.gid;
		this.cpid = this.opt.cpid || 0;
		this.bid = this.opt.bid ? this.opt.bid : 0;
		this.mid = this.opt.mid ? this.opt.mid  : '';
		this.frompage = this.opt.frompage ? this.opt.frompage  : '';
		this.addressData = this.opt.addressData ? this.opt.addressData  : '';

		if(this.opt.keyword) {
			this.keyword = this.opt.keyword;
		}
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
		afteraddcart: function (e) {
			this.addcart({currentTarget:{dataset:e}});
		},
		buydialogChange: function (e) {
			if(!this.buydialogShow){
				this.proid = e.currentTarget.dataset.proid
			}
			this.buydialogShow = !this.buydialogShow;
		},
		addcart:function(e){
			var that = this;
			var sell_price = '';
			var num = e.currentTarget.dataset.num;
			var proid = e.currentTarget.dataset.proid;
			var ggid = e.currentTarget.dataset.ggid;

			that.loading = true;
			app.post('ApiAdminOrderlr/addcart', {proid: proid,ggid: ggid,num: num,sell_price:sell_price,mid:that.mid}, function (res) {
				that.loading = false;
				if (res.status == 1) {
					that.getdata();
					app.success(res.msg)
					setTimeout(() => {
						app.goto('dkfastbuy?mid='+that.mid+'&frompage='+that.frompage+'&addressData='+that.addressData,'reLaunch')
					},500)
				} else {
					app.error(res.msg);
				}
			});
		},
		getdata:function(){
			var that = this;
			that.pagenum = 1;
			that.datalist = [];
			var cid = that.opt.cid;
			var gid = that.opt.gid;
			var bid = that.opt.bid ? that.opt.bid : '';
			var cid2 = that.cid2;
			that.loading = true;
			let isCoupon  = '';
			if(that.setCoupon){
				isCoupon = 1
			}
			app.get('ApiShop/prolist', {cid: cid,gid: gid,bid:bid,cid2:cid2,mendian_id:that.mendianid,latitude:that.latitude,longitude:that.longitude,area:that.area,is_coupon:isCoupon}, function (res) {
				that.loading = false;
			  that.clist = res.clist;
			  that.clist2 = res.clist2;
			  that.glist = res.glist;
				that.set = res.set;
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
      that.history_show = false;
			that.loading = true;
			that.nodata = false;
      that.nomore = false;
			var bid = that.opt.bid ? that.opt.bid : '';
			let isCoupon  = '';
			if(that.setCoupon){
				isCoupon = 1
			}
      app.post('ApiShop/getprolist',{pagenum: pagenum,keyword: keyword,field: field,order: order,gid: gid,cid: cid,cid2:cid2,cpid:cpid,bid:bid,mendian_id:that.mendianid,latitude:that.latitude,longitude:that.longitude,area:that.area,order_add_mobile:1,is_coupon:isCoupon}, function (res) {
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
			this.$refs[e].open()
		},
		// 关闭窗口
		closeDrawer(e) {
			this.$refs[e].close()
		},
		// 抽屉状态发生变化触发
		change(e, type) {
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
		couponAddChange(item){
			uni.$emit('shopDataEmit',{id:item.id,name:item.name,pic:item.pic,give_num:1});
			uni.navigateBack({
				delta: 2
			});
		}
  }
};
</script>
<style>
.addbut{width:88rpx;height:60rpx;border-radius:30rpx;text-align:center;font-size: 24rpx;line-height:60rpx;color: #fff;position:absolute;bottom:6rpx;right:4rpx;}
.search-container {position: fixed;width: 100%;background: #fff;z-index:9;top:var(--window-top)}
.topsearch{width:100%;padding:16rpx 20rpx;}
.topsearch .f1{height:60rpx;border-radius:30rpx;border:0;background-color:#f7f7f7;flex:1}
.topsearch .f1 .img{width:24rpx;height:24rpx;margin-left:10px}
.topsearch .f1 input{height:100%;flex:1;padding:0 20rpx;font-size:28rpx;color:#333;}
.topsearch .f1 .camera {height:72rpx;width:72rpx;color: #666;border: 0px;padding: 0px;margin: 0px;background-position: center;background-repeat: no-repeat; background-size:40rpx;}

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

.filter-page{height: 100%;}
.filter-scroll-view{margin-top:var(--window-top)}
.search-filter{display: flex;flex-direction: column;text-align: left;width:100%;flex-wrap:wrap;padding:0;}
.filter-content-title{color:#999;font-size:28rpx;height:30rpx;line-height:30rpx;padding:0 30rpx;margin-top:30rpx;margin-bottom:10rpx}
.filter-title{color:#BBBBBB;font-size:32rpx;background:#F8F8F8;padding:60rpx 0 30rpx 20rpx;}
.search-filter-content{display: flex;flex-wrap:wrap;padding:10rpx 20rpx;}
.search-filter-content .filter-item{background:#F4F4F4;border-radius:28rpx;color:#2B2B2B;font-weight:bold;margin:10rpx 10rpx;min-width:140rpx;height:56rpx;line-height:56rpx;text-align:center;font-size: 24rpx;padding:0 30rpx}
.search-filter-content .close{text-align: right;font-size:24rpx;color:#ff4544;width:100%;padding-right:20rpx}
.search-filter button .icon{margin-top:6rpx;height:54rpx;}
.search-filter-btn{display:flex;padding:30rpx 30rpx 50rpx 30rpx;justify-content: space-between}
.search-filter-btn .btn{width:240rpx;height:66rpx;line-height:66rpx;background:#fff;border:1px solid #e5e5e5;border-radius:33rpx;color:#2B2B2B;font-weight:bold;font-size:24rpx;text-align:center}
.search-filter-btn .btn2{width:240rpx;height:66rpx;line-height:66rpx;border-radius:33rpx;color:#fff;font-weight:bold;font-size:24rpx;text-align:center}

.product-container {width: 100%;margin-top: 190rpx;font-size:26rpx;padding:0 24rpx}
.dp-product-normal-item{height: auto; position: relative;overflow: hidden; padding: 0px; display:flex;flex-wrap:wrap}
.dp-product-normal-item .item{display: inline-block;position: relative;margin-bottom: 12rpx;background: #fff;border-radius:10rpx;overflow:hidden;}
.dp-product-normal-item .product-pic {width: 100%;height:0;overflow:hidden;background: #ffffff;padding-bottom: 100%;position: relative;}
.dp-product-normal-item .product-pic .image{position:absolute;top:0;left:0;width: 100%;height:auto}
.dp-product-normal-item .product-pic .saleimg{ position: absolute;width: 60px;height: auto; top: -3px; left:-3px;}
.dp-product-normal-item .product-info {padding:20rpx 20rpx;position: relative;}
.dp-product-normal-item .product-info .p1 {color:#323232;font-weight:bold;font-size:28rpx;line-height:36rpx;margin-bottom:10rpx;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:2;overflow:hidden;height:72rpx}
.dp-product-normal-item .product-info .p2{display:flex;align-items:center;overflow:hidden;padding:2px 0}
.dp-product-normal-item .product-info .p2-1{flex-grow:1;flex-shrink:1;height:40rpx;line-height:40rpx;overflow:hidden;white-space: nowrap}
.dp-product-normal-item .product-info .p2-1 .t1{font-size:36rpx;}
.dp-product-normal-item .product-info .p2-1 .t2 {margin-left:10rpx;font-size:24rpx;color: #aaa;text-decoration: line-through;/*letter-spacing:-1px*/}
.dp-product-normal-item .product-info .p2-1 .t3 {margin-left:10rpx;font-size:22rpx;color: #999;}
.dp-product-normal-item .product-info .p2-2{font-size:20rpx;height:40rpx;line-height:40rpx;text-align:right;padding-left:20rpx;color:#999}
.dp-product-normal-item .product-info .p3{color:#999999;font-size:20rpx;margin-top:10rpx}
.dp-product-normal-item .product-info .p4{width:52rpx;height:52rpx;border-radius:50%;position:absolute;display:relative;bottom:16rpx;right:20rpx;text-align:center;}
.dp-product-normal-item .product-info .p4 .icon_gouwuche{font-size:30rpx;height:52rpx;line-height:52rpx}
.dp-product-normal-item .product-info .p4 .img{width:100%;height:100%}
.bg-desc {color: #fff; padding: 10rpx 20rpx;}

.dp-product-normal-item .product-info .binfo {padding-bottom:6rpx;display: flex;align-items: center;min-width: 0;}
.dp-product-normal-item .product-info .binfo .t1 {width: 30rpx;	height: 30rpx;border-radius: 50%;margin-right: 10rpx;flex-shrink: 0;}
.dp-product-normal-item .product-info .binfo .t2 {color: #666;font-size: 24rpx;font-weight: normal;	overflow: hidden;text-overflow: ellipsis;	white-space: nowrap;}
.dp-product-normal-item .product-info .binfo .b2{flex-shrink: 0;}
.dp-product-normal-item .product-info .binfo .b1{max-width: 75%;}
.dp-product-normal-item .couponitem {width: 100%;	/* padding: 0 20rpx 20rpx 20rpx; */font-size: 24rpx;color: #333;display: flex;align-items: center;}
.dp-product-normal-item .couponitem .f1 {flex: 1;	display: flex;flex-wrap: nowrap;overflow: hidden}
.dp-product-normal-item .couponitem .f1 .t {margin-right: 10rpx;border-radius: 3px;font-size: 22rpx;height: 40rpx;line-height: 40rpx;padding-right: 10rpx;flex-shrink: 0;overflow: hidden}
.lianxi{color: #fff;border-radius: 50rpx 50rpx;line-height: 50rpx;text-align: center;font-size: 22rpx;padding: 0 14rpx;display: inline-block;float: right;}
.field_buy{line-height: 40rpx;border-bottom: 0;padding: 4rpx 0;word-break: break-all;}
.nowbuy{width:160rpx;line-height:60rpx;text-align: center;border-radius: 4rpx;margin-top: 10rpx;}
</style>