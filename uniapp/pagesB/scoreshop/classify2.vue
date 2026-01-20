<template>
<view class="container">
	<block v-if="isload">
		<view @tap.stop="goto" data-url="/activity/scoreshop/prolist" class="search-container">
			<view class="search-box">
				<image class="img" :src="pre_url+'/static/img/search_ico.png'"></image>
				<view class="search-text">搜索感兴趣的商品</view>
			</view>
		</view>
		<view class="order-tab">
			<view class="order-tab2">
				<block v-for="(item, index) in clist" :key="index">
					<view :class="'item ' + (curTopIndex == index ? 'on' : '')" @tap="switchTopTab" :data-index="index" :data-id="item.id"> 
            <view class="toppic">
             <image v-if="item.pic" :src="item.pic" mode="widthFix" style="width: 100%;"/>
            </view>
            {{item.name}}
            <view class="after" :style="{background:t('color1')}"></view>
          </view>
				</block>
			</view>
		</view>
		<view class="content-container">
			<view class="nav_left">
				<view :class="'nav_left_items ' + (curIndex == -1 ? 'active' : '')" :style="{color:curIndex == -1?t('color1'):'#333'}" @tap="switchRightTab" data-index="-1" :data-id="clist[curTopIndex].id"><view class="before" :style="{background:t('color1')}"></view>全部</view>
				<block v-for="(item, index2) in clist[curTopIndex].child" :key="index2">
					<view :class="'nav_left_items ' + (curIndex == index2 ? 'active' : '')" :style="{color:curIndex == index2?t('color1'):'#333'}" @tap="switchRightTab" :data-index="index2" :data-id="item.id"><view class="before" :style="{background:t('color1')}"></view>{{item.name}}</view>
				</block>
			</view>
			<view class="nav_right">
				<view class="nav_right-content">
					<view class="nav-pai">
						<view class="nav-paili" :style="{color:(!field||field=='sort')?t('color1'):'#323232'}" @tap="changeOrder" data-field="sort" data-order="desc">综合</view> 
						<view class="nav-paili" :style="field=='sales'?'color:'+t('color1'):''" @tap="changeOrder" data-field="sales" data-order="desc">兑换数量</view> 
						<view class="nav-paili" @tap="changeOrder" data-field="score_price" :data-order="order=='asc'?'desc':'asc'">
							<text :style="field=='score_price'?'color:'+t('color1'):''">所需{{t('积分')}}</text>
							<text class="iconfont iconshangla" :style="field=='score_price'&&order=='asc'?'color:'+t('color1'):''"></text>
							<text class="iconfont icondaoxu" :style="field=='score_price'&&order=='desc'?'color:'+t('color1'):''"></text>
						</view>  
					</view>
					<view class="classify-ul" v-if="curTopIndex>-1 && curIndex>-1 && clist[curTopIndex].child[curIndex].child.length>0">
						<view class="flex" style="width:100%;overflow-y:hidden;overflow-x:scroll;">
						 <view class="classify-li" :style="curIndex2==-1?'color:'+t('color1')+';background:rgba('+t('color1rgb')+',0.2)':''" @tap="changeCTab" :data-id="clist[curTopIndex].child[curIndex].id" data-index="-1">全部</view>
						 <block v-for="(item, idx2) in clist[curTopIndex].child[curIndex].child" :key="idx2">
						 <view class="classify-li" :style="curIndex2==idx2?'color:'+t('color1')+';background:rgba('+t('color1rgb')+',0.2)':''" @tap="changeCTab" :data-id="item.id" :data-index="idx2">{{item.name}}</view>
						 </block>
						</view>
					</view>
					<scroll-view class="classify-box" scroll-y="true" @scrolltolower="scrolltolower">
						<view class="product-itemlist">
							<view class="item" v-for="(item,index) in datalist" :key="item.id" @click="goto" :data-url="'/activity/scoreshop/product?id='+item.id">
								<view class="product-pic">
									<image class="image" :src="item.pic" mode="widthFix"/>
								</view>
								<view class="product-info">
									<view class="p1"><text>{{item.name}}</text></view>
									<view>
										<view class="p2" >
											<view class="t1" :style="{color:t('color1')}">
                        <block v-if="item.score_price>0">{{item.score_price}}{{t('积分')}}</block>
                        <block v-if="item.money_price>0">
                          <block v-if="item.score_price>0">+</block>{{item.money_price}}元
                        </block>
                      </view>
											<text class="t2" v-if="item.market_price*1 > item.sell_price*1">￥{{item.market_price}}</text>
										</view>
									</view>
									<view class="p3">
										<view class="p3-1" v-if="item.sales>0"><text style="overflow:hidden">已售{{item.sales}}件</text></view>
									</view>
                  <view v-if="item.sales<=0 && item.merchant_name" style="height: 44rpx;"></view>
									<view class="p4" v-if="item.hide_cart!=true" :style="{background:'rgba('+t('color1rgb')+',0.1)',color:t('color1')}" @click.stop="buydialogChange" :data-proid="item.id" :data-canaddcart="item.canaddcart"><text class="iconfont icon_gouwuche"></text></view>
								</view>
							</view>
						</view>
						<nomore text="没有更多商品了" v-if="nomore"></nomore>
						<nodata text="没有查找到相关商品" v-if="nodata"></nodata>
					</scroll-view>
				</view>
			</view>
		</view>
    
    <view v-if="buydialogShow">
    	<view class="buydialog-mask" @tap="buydialogChange"></view>
    	<view class="buydialog" :class="menuindex>-1?'tabbarbot':'notabbarbot'">
    		<view class="close" @tap="buydialogChange">
    			<image :src="pre_url+'/static/img/close.png'" class="image"/>
    		</view>
    		<view class="title flex">
    			<image :src="nowguige.pic || product.pic" class="img" @tap="previewImage" :data-url="nowguige.pic || product.pic"/>
    			<view class="flex1">
    				<view  class="price" :style="{color:t('color1')}">
    					{{nowguige.score_price}}{{t('积分')}}<text v-if="nowguige.money_price*1>0">+{{nowguige.money_price}}元</text>
    					<text v-if="nowguige.market_price > 0" class="t2">￥{{nowguige.market_price}}</text>
    				</view>
    				<view class="stock" v-if="!shopset || shopset.hide_stock!=1">库存：{{nowguige.stock}}</view>
    				<view class="choosename" v-if="product.guigeset==1">已选规格: {{nowguige.name}}</view>
    			</view>
    		</view>
    		<view style="max-height:50vh;overflow:scroll" v-if="product.guigeset==1">
    			<view v-for="(item, index) in guigedata" :key="index" class="guigelist flex-col">
    				<view class="name">{{item.title}}</view>
    				<view class="item flex flex-y-center">
    					<block v-for="(item2, index2) in item.items" :key="index2">
    						<view :data-itemk="item.k" :data-idx="item2.k" :class="'item2 ' + (ggselected[item.k]==item2.k ? 'on':'')" @tap="ggchange">{{item2.title}}</view>
    					</block>
    				</view>
    			</view>
    		</view>
    		<view class="buynum flex flex-y-center">
    			<view class="flex1">购买数量：</view>
    			<view class="addnum">
    				<view class="minus" @tap="gwcminus"><image class="img" :src="pre_url+'/static/img/cart-minus.png'"/></view>
    				<input class="input" type="number" :value="gwcnum" @input="gwcinput"></input>
    				<view class="plus" @tap="gwcplus"><image class="img" :src="pre_url+'/static/img/cart-plus.png'"/></view>
    			</view>
    		</view>
    		<view class="op">
    			<block v-if="nowguige.stock <= 0">
    				<button class="nostock">库存不足</button>
    			</block>
    			<block v-else>
    				<button class="addcart" :style="{backgroundColor:t('color2')}" @tap="addcart" v-if="btntype==0 && canaddcart">加入购物车</button>
    				<button class="tobuy" :style="{backgroundColor:t('color1')}" @tap="tobuy" v-if="btntype==0">立即购买</button>
    				<button class="addcart" :style="{backgroundColor:t('color2')}" @tap="addcart" v-if="btntype==1">确 定</button>
    				<button class="tobuy" :style="{backgroundColor:t('color1')}" @tap="tobuy" v-if="btntype==2">确 定</button>
    			</block>
    		</view>
    	</view>
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

      bottom: 0,
      pagenum: 1,
      order: '',
      field: '',
      clist: [],
      curTopIndex: 0,
      curIndex: -1,
      curIndex2: -1,
      datalist: [],
      nopro: 0,
      curCid: 0,
			proid:0,
			buydialogShow: false,
      prodata: [],
      userinfo: [],
      nomore: false,
			nodata: false,
      bid:'',
      
      btntype:0,
      product: [],
      cartnum: "",
      buydialogShow: false,
      guigelist:{},
      guigedata:{},
      ggselected:{},
      nowguige:{},
      gwcnum:1,
      ggselected:[],
      ks:'',
      canaddcart:false,
	  pre_url: app.globalData.pre_url,
    };
  },
  onLoad: function (opt) {
		this.opt = app.getopts(opt);
    this.bid = this.opt.bid ? this.opt.bid  : '';
		this.getdata();
  },
	onPullDownRefresh: function () {
		this.getdata();
	},
  methods: {
		getdata: function () {
			var that = this;
			var nowcid = that.opt.cid;
			if (!nowcid) nowcid = '';
			that.loading = true;
			app.get('ApiScoreshop/classify2', {cid: nowcid,bid:that.bid}, function (res) {
				that.loading = false;
        if(res.btntype === 0 || res.btntype === '0' || res.btntype>0){
          that.btntype = res.btntype
        }
				var data = res.data;
				that.clist  = data;
				that.curCid = data[0]['id'];
				if (nowcid) {
					for (var i = 0; i < data.length; i++) {
						if (data[i]['id'] == nowcid) {
							that.curTopIndex = i;
							that.curCid = nowcid;
							break;
						}
						var downcdata = data[i]['child'];
						var isget = 0;
						for (var j = 0; j < downcdata.length; j++) {
							if (downcdata[j]['id'] == nowcid) {
								that.curIndex = i + 1;
								that.curIndex2 = j;
								that.curTopIndex = i;
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
			wherefield.cid = cid;
			app.post('ApiScoreshop/getprolist',wherefield, function (res) { 
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
			this.getdatalist();
		},
    switchTopTab: function (e) {
      var that = this;
      var id = e.currentTarget.dataset.id;
      var index = parseInt(e.currentTarget.dataset.index);
      this.curTopIndex = index;
      this.curIndex = -1;
      this.curIndex2 = -1;
      this.prolist = [];
      this.nopro = 0;
      this.curCid = id;
      this.getdatalist();
    },
		buydialogChange: function (e) {
      var that = this;
      var id = this.opt.id || 0;
      var buydialogShow = that.buydialogShow;
			if(!buydialogShow){
        that.canaddcart = e.currentTarget.dataset.canaddcart;
				var id = e.currentTarget.dataset.proid;
        that.loading = true;
        app.post('ApiScoreshop/product', {id: id}, function (res) {
        	that.loading = false;
        	if (res.status == 0) {
        		app.alert(res.msg);
        		return;
        	}
        	var product = res.product;
        	that.product = product;
        
        	that.guigelist = res.guigelist;
        	that.guigedata = res.guigedata;
        	var ggselected = [];
        	for (var i = 0; i < (res.guigedata).length; i++) {
        		ggselected.push(0);
        	}
        	that.ks = ggselected.join(','); 
        	that.nowguige = that.guigelist[that.ks];
        	that.ggselected = ggselected;
        
        	that.cartnum = res.cartnum;
        	that.shopset = res.shopset;
          that.buydialogShow = true;
        });
			}else{
        that.product = [];
        that.cartnum = "";
        that.buydialogShow =  false;
        that.guigelist = {};
        that.guigedata = {};
        that.ggselected = {};
        that.nowguige = {};
        that.gwcnum = 1;
        that.ggselected = [];
        that.ks = '';
        that.buydialogShow = false;
      }
		},
    ggchange: function (e){
    	var idx = e.currentTarget.dataset.idx;
    	var itemk = e.currentTarget.dataset.itemk;
    	var ggselected = this.ggselected;
    	ggselected[itemk] = idx;
    	var ks = ggselected.join(',');
    	this.ggselected = ggselected;
    	this.ks = ks;
    	this.nowguige = this.guigelist[this.ks];
    },
    //加
    gwcplus: function (e) {
    	var gwcnum = this.gwcnum + 1;
    	var ks = this.ks;
    	if (gwcnum > this.guigelist[ks].stock) {
    		app.error('库存不足');
    		return 1;
    	}
    	this.gwcnum = this.gwcnum + 1;
    },
    //减
    gwcminus: function (e) {
    	var gwcnum = this.gwcnum - 1;
    	if (gwcnum <= 0) {
    		return;
    	}
    	this.gwcnum = this.gwcnum - 1;
    },
    //输入
    gwcinput: function (e) {
    	var ks = this.ks;
    	var gwcnum = parseInt(e.detail.value);
    	if (gwcnum < 1) gwcnum = 1;
    	if (gwcnum > this.guigelist[ks].stock) {
    		gwcnum = this.guigelist[ks].stock > 0 ? this.guigelist[ks].stock : 1;
    	}
    	this.gwcnum = gwcnum;
    },
    addcart:function(){
    	var that = this;
    	var ks = that.ks;
    	var num = that.gwcnum;
    	var proid = that.product.id;
    	var ggid = that.guigelist[ks].id;
    	var stock = that.guigelist[ks].stock;
    	
    	if (num < 1) num = 1;
    	if (stock < num) {
    		app.error('库存不足');
    		return;
    	}
    	that.loading = true;
    	app.post('ApiScoreshop/addcart', {proid:proid,ggid:ggid,num:num}, function (res) {
    		that.loading = false;
    		if (res.status == 1) {
    			app.success(res.msg);
    			that.cartnum+=num;
    			that.buydialogShow = false;
    		}else{
    			app.error(res.msg);
    		}
    	});
    },
    tobuy:function(){
      var that = this;
    	var ks = that.ks;
    	var num = that.gwcnum;
    	var proid = that.product.id;
    	var ggid = that.guigelist[ks].id;
    	var proid = that.product.id;
    	var num = that.gwcnum;
    	if (num < 1) num = 1;
    	var prodata = proid + ',' + num + ',' + (ggid == undefined ? '':ggid);
    	app.goto('/activity/scoreshop/buy?prodata='+prodata);
    },
  }
};
</script>
<style>
page {height:100%;}
.container{width: 100%;height:100%;max-width:640px;background-color: #fff;color: #939393;display: flex;flex-direction:column}
.search-container {width: 100%;height: 94rpx;padding: 16rpx 23rpx 14rpx 23rpx;background-color: #fff;position: relative;overflow: hidden;}
.search-box {display:flex;align-items:center;height:60rpx;border-radius:30rpx;border:0;background-color:#f7f7f7;flex:1}
.search-box .img{width:24rpx;height:24rpx;margin-right:10rpx;margin-left:30rpx}
.search-box .search-text {font-size:24rpx;color:#C2C2C2;width: 100%;}


.order-tab{display:flex;width:100%;overflow-x:scroll;border-bottom: 1px #f5f5f5 solid;background: #fff;padding:0 10rpx}
.order-tab2{display:flex;width:auto;min-width:100%}
.order-tab2 .item{width:auto;padding:0 20rpx;font-size:30rpx;font-weight:bold;text-align: center; color:#999999; line-height:90rpx; overflow: hidden;position:relative;flex-shrink:0;flex-grow: 1;}
.order-tab2 .on{color:#222222;}
.order-tab2 .after{display:none;position:absolute;left:50%;margin-left:-20rpx;bottom:10rpx;height:6rpx;border-radius:1.5px;width:40rpx}
.order-tab2 .on .after{display:block}

.content-container{flex:1;height:100%;display:flex;overflow: hidden;}

.nav_left{width: 25%;height:100%;background: #ffffff;overflow-y:scroll;}
.nav_left .nav_left_items{line-height:50rpx;color:#333;font-weight:bold;border-bottom:0px solid #E6E6E6;font-size:28rpx;position: relative;border-right:0 solid #E6E6E6;padding:25rpx 30rpx;}
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
.product-itemlist .product-info .p1 {color:#323232;font-weight:bold;font-size:28rpx;line-height:36rpx;margin-bottom:0;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:2;overflow:hidden;height:72rpx}
.product-itemlist .product-info .p2{margin-top:10rpx;height:36rpx;line-height:36rpx;overflow:hidden;}
.product-itemlist .product-info .p2 .t1{font-size:32rpx;}
.product-itemlist .product-info .p2 .t2 {margin-left:10rpx;font-size:24rpx;color: #aaa;text-decoration: line-through;/*letter-spacing:-1px*/}
.product-itemlist .product-info .p3{display:flex;align-items:center;overflow:hidden;margin-top:10rpx}
.product-itemlist .product-info .p3-1{font-size:20rpx;height:30rpx;line-height:30rpx;text-align:right;color:#999}
.product-itemlist .product-info .p4{width:56rpx;height:56rpx;border-radius:50%;position:absolute;display:relative;bottom:6rpx;right:4rpx;text-align:center;}
.product-itemlist .product-info .p4 .icon_gouwuche{font-size:32rpx;height:56rpx;line-height:56rpx}
::-webkit-scrollbar{width: 0;height: 0;color: transparent;}

.btn2{color:#fff;width:160rpx;height:56rpx;display:flex;align-items:center;justify-content:center;border-radius:8rpx;float: right;}


.buydialog-mask{ position: fixed; top: 0px; left: 0px; width: 100%; background: rgba(0,0,0,0.5); bottom: 0px;z-index:10}
.buydialog{ position: fixed; width: 100%; left: 0px; bottom: 0px; background: #fff;z-index:11;border-radius:20rpx 20rpx 0px 0px}
.buydialog .close{ position: absolute; top: 0; right: 0;padding:20rpx;z-index:12}
.buydialog .close .image{ width: 30rpx; height:30rpx; }
.buydialog .title{ width: 94%;position: relative; margin: 0 3%; padding:20rpx 0px; border-bottom:0; height: 190rpx;}
.buydialog .title .img{ width: 160rpx; height: 160rpx; position: absolute; top: 20rpx; border-radius: 10rpx; border: 0 #e5e5e5 solid;background-color: #fff}
.buydialog .title .price{ padding-left:180rpx;width:100%;font-size: 36rpx;height:70rpx; color: #FC4343;overflow: hidden;}
.buydialog .title .price .t1{ font-size:26rpx}
.buydialog .title .price .t2{ font-size:26rpx;text-decoration:line-through;color:#aaa;margin-left:10rpx}
.buydialog .title .choosename{ padding-left:180rpx;width: 100%;font-size: 24rpx;height: 42rpx;line-height:42rpx;color:#888888}
.buydialog .title .stock{ padding-left:180rpx;width: 100%;font-size: 24rpx;height: 42rpx;line-height:42rpx;color:#888888}

.buydialog .guigelist{ width: 94%; position: relative; margin: 0 3%; padding:0px 0px 10px 0px; border-bottom: 0; }
.buydialog .guigelist .name{ height:70rpx; line-height: 70rpx;}
.buydialog .guigelist .item{ font-size: 30rpx;color: #333;flex-wrap:wrap}
.buydialog .guigelist .item2{ height:60rpx;line-height:60rpx;margin-bottom:4px;border:0; border-radius:4rpx; padding:0 40rpx;color:#666666; margin-right: 10rpx; font-size:26rpx;background:#F4F4F4}
.buydialog .guigelist .on{color:#FC4343;background:rgba(252,67,67,0.1);font-weight:bold}
.buydialog .buynum{ width: 94%; position: relative; margin: 0 3%; padding:10px 0px 10px 0px; }
.buydialog .addnum {font-size: 30rpx;color: #666;width:auto;display:flex;align-items:center}
.buydialog .addnum .plus {width:65rpx;height:48rpx;background:#F6F8F7;display:flex;align-items:center;justify-content:center}
.buydialog .addnum .minus {width:65rpx;height:48rpx;background:#F6F8F7;display:flex;align-items:center;justify-content:center}
.buydialog .addnum .img{width:24rpx;height:24rpx}
.buydialog .addnum .input{flex:1;width:50rpx;border:0;text-align:center;color:#2B2B2B;font-size:28rpx;margin: 0 15rpx;}

.buydialog .op{width:90%;margin:20rpx 5%;border-radius:36rpx;overflow:hidden;display:flex;margin-top:100rpx;}
.buydialog .addcart{flex:1;height:72rpx; line-height: 72rpx; color: #fff; border-radius: 0px; border: none;font-size:28rpx;font-weight:bold}
.buydialog .tobuy{flex:1;height: 72rpx; line-height: 72rpx; color: #fff; border-radius: 0px; border: none;font-size:28rpx;font-weight:bold}
.buydialog .nostock{flex:1;height: 72rpx; line-height: 72rpx; background:#aaa; color: #fff; border-radius: 0px; border: none;}
.toppic{width: 80rpx;height: 80rpx;border: 80rpx;border-radius: 50%;background-color: #f1f1f1;overflow: hidden;margin: 0 auto;}
</style>