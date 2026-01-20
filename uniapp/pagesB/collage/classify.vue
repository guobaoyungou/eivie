<template>
<view class="container">
	<block v-if="isload">
		<view @tap.stop="goto" :data-url="'prolist?bid='+bid" class="search-container">
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
					<!-- <view class="nav-pai">
						<view class="nav-paili" :style="(!field||field=='sort')?'color:'+t('color1'):''" @tap="changeOrder" data-field="sort" data-order="desc">综合</view> 
						<view class="nav-paili" :style="field=='sales'?'color:'+t('color1'):''" @tap="changeOrder" data-field="sales" data-order="desc">销量</view> 
						<view class="nav-paili" @tap="changeOrder" data-field="sell_price" :data-order="order=='asc'?'desc':'asc'">
							<text :style="field=='sell_price'?'color:'+t('color1'):''">价格</text>
							<text class="iconfont iconshangla" :style="field=='sell_price'&&order=='asc'?'color:'+t('color1'):''"></text>
							<text class="iconfont icondaoxu" :style="field=='sell_price'&&order=='desc'?'color:'+t('color1'):''"></text>
						</view>  
					</view> -->
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
							<view class="item" v-for="(item,index) in datalist" :key="item.id" @click="goto" :data-url="'/activity/collage/product?id='+item.id">
								<view class="flex">
									<view class="product-pic">
										<image class="image" :src="item.pic" mode="widthFix"/>
									</view>
									<view class="product-info">
										<view class="p1">{{item.name}}</view>
										<view class="p2 flex">
												<view>
													<text class="t1" :style="{color:t('color1')}"><text style="font-size:20rpx;padding-right:1px">￥</text>{{item.sell_price}}</text>
													<text class="t2" v-if="item.market_price*1 > item.sell_price*1">￥{{item.market_price}}</text>
												</view>
										</view>
                    <view class="p3">
                    	<view class="t1">已拼成<text style="font-size:32rpx;color:#f40;padding:0 2rpx;">{{item.sales}}</text>件</view>
                    	<view class="t2" :style="{borderColor:t('color1'),color:t('color1')}">
                    		<view class="x1" v-if="!item.collage_type">{{item.teamnum}}人团</view>
                    		<view class="x2" :style="{backgroundColor:t('color1')}">去拼团</view>
                    	</view>
                    </view>
									</view>
								</view>
							</view>
						</view>
						<nomore text="没有更多商品了" v-if="nomore"></nomore>
						<nodata text="暂无相关商品" v-if="nodata"></nodata>
						<view style="width:100%;height:40rpx"></view>
					</scroll-view>
				</view>
			</view>
		</view>
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
			bid:'',
			pre_url:app.globalData.pre_url,
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
		getdata:function(){
			var that = this;
			var nowcid = that.opt.cid;
			if (!nowcid) nowcid = '';
			that.pagenum = 1;
			that.datalist = [];
			that.loading = true;
			app.get('ApiCollage/classify', {cid:nowcid,bid:that.bid}, function (res) {
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
			app.post('ApiCollage/prolist',wherefield, function (res) { 
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
.nav_left .nav_left_items{line-height:50rpx;color:#999999;border-bottom:0px solid #E6E6E6;font-size:24rpx;position: relative;border-right:0 solid #E6E6E6;padding:25rpx 30rpx;}
.nav_left .nav_left_items.active{background: #fff;color:#222222;font-size:28rpx;font-weight:bold}
.nav_left .nav_left_items .before{display:none;position:absolute;top:50%;margin-top:-12rpx;left:10rpx;height:24rpx;border-radius:4rpx;width:8rpx}
.nav_left .nav_left_items.active .before{display:block}

.nav_right{width: 75%;height:100%;display:flex;flex-direction:column;background: #f6f6f6;box-sizing: border-box;padding:20rpx 20rpx 0 20rpx}
.nav_right-content{height:100%}
.nav-pai{ width: 100%;display:flex;border-radius:10rpx; margin-bottom: 10rpx; align-items:center;justify-content:center; background: #fff;}
.nav-paili{flex:1; text-align:center;color:#323232; font-size:28rpx;font-weight:bold;position: relative;height:80rpx;line-height:80rpx;}
.nav-paili .iconshangla{position: absolute;top:-4rpx;padding: 0 6rpx;font-size: 20rpx;color:#7D7D7D}
.nav-paili .icondaoxu{position: absolute;top: 8rpx;padding: 0 6rpx;font-size: 20rpx;color:#7D7D7D}

.classify-ul{width:100%;height:100rpx;padding:0 10rpx;}
.classify-li{flex-shrink:0;display:flex;background:#F5F6F8;border-radius:22rpx;color:#6C737F;font-size:20rpx;text-align: center;height:44rpx; line-height:44rpx;padding:0 28rpx;margin:12rpx 10rpx 12rpx 0}

.classify-box{padding: 0 0 20rpx 0;width: 100%;height:calc(100% - 20rpx);overflow-y: scroll; border-top:1px solid #F5F6F8;}
.classify-box .nav_right_items{ width:100%;border-bottom:1px #f4f4f4 solid;  padding:16rpx 0;  box-sizing:border-box;  position:relative; }

.product-itemlist{height: auto; position: relative;overflow: hidden; padding: 0px; display:flex;flex-wrap:wrap}
.product-itemlist .item{width:100%;display: inline-block;position: relative;margin-bottom: 12rpx;background: #fff;padding:20rpx 14rpx;border-radius:10rpx;border-bottom:1px solid #F8F8F8}
.product-itemlist .product-pic {width: 160rpx;height:160rpx;overflow:hidden;background: #ffffff;padding-bottom: 30%;position: relative;border-radius:4px;}
.product-itemlist .product-pic .image{position:absolute;top:0;left:0;width: 100%;height:auto}
.product-itemlist .product-pic .saleimg{ position: absolute;width: 120rpx;height: auto; top: -6rpx; left:-6rpx;}
.product-itemlist .product-info {width: 70%;padding:0 10rpx 5rpx 20rpx;position: relative;}
.product-itemlist .product-info .name_1{ height: 46rpx;line-height: 46rpx;}
.product-itemlist .product-info .p1 {color:#323232;font-weight:bold;font-size:24rpx;line-height:35rpx;margin-bottom:0;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:2;overflow:hidden;}
.product-itemlist .product-info .p1 .team_text{ margin-right:10rpx;border-radius: 4rpx; color: #FF3143; background:#FFDED9 ; font-size: 20rpx; padding: 8rpx 6rpx;}
.product-itemlist .product-info .p2{ justify-content:space-between;height:36rpx;line-height:36rpx;overflow:hidden; margin-top: 20rpx;}
.product-itemlist .product-info .p2 .t1{font-size:32rpx; font-weight: bold;}
.product-itemlist .product-info .p2 .t2 {margin-left:10rpx;font-size:24rpx;color: #aaa;text-decoration: line-through;/*letter-spacing:-1px*/}
.product-itemlist .product-info .p3-1{font-size:20rpx;height:36rpx;line-height:36rpx;text-align:right;color:#999}
.product-itemlist .product-info .p4{width:48rpx;height:48rpx;border-radius:50%;position:absolute;display:relative;bottom:6rpx;right:4rpx;text-align:center;}
.product-itemlist .product-info .p4 .icon_gouwuche{font-size:28rpx;height:48rpx;line-height:48rpx}
.item .desc{ display: flex; margin-top: 15rpx;background:linear-gradient(to right, #FF3143, #FF8F99); justify-content: space-between;  padding:0 10rpx; color: #fff;border-radius: 6rpx; line-height: 60rpx; font-size: 20rpx;}
::-webkit-scrollbar{width: 0;height: 0;color: transparent;}

.product-itemlist .product-info .p3{font-size: 24rpx;line-height:50rpx;overflow:hidden;}
.product-itemlist .product-info .p3 .t1{color:#aaa;font-size:24rpx;flex:1}
.product-itemlist .product-info .p3 .t2{height: 50rpx;line-height: 50rpx;overflow: hidden;border: 1px #FF3143 solid;border-radius:10rpx;width: 280rpx;display: flex;justify-content: space-between;text-align: center;}
.product-itemlist .product-info .p3 .t2 .x1{width: 50%;}
.product-itemlist .product-info .p3 .t2 .x2{background: #FF3143;color:#fff;width: 50%;}
</style>