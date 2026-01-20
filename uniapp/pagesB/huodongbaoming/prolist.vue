<template>
<view class="container">
	<block v-if="isload">
		<view  class="search-container">
			<view class="title-box">
				<view>活动报名列表</view>
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
										<view class="t1" :style="{color:t('color1')}">{{item.sell_price}}<text style="font-size:24rpx;padding-left:2px">元</text></view>
										<!-- <text class="t2" v-if="item.market_price*1 > item.sell_price*1">￥{{item.market_price}}</text> -->
									</view>
									<view class="p3">
										<view class="p3-1" v-if="item.sales>0"><text style="overflow:hidden">已售{{item.sales}}件</text></view>
									</view>
							
								</view>
							</view>
						</view>
						<nomore text="没有更多活动了" v-if="nomore"></nomore>
						<nodata text="暂无相关活动" v-if="nodata"></nodata>
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
			keyword: '',

		};

	},
  
  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.getdata();
  },
	onPullDownRefresh: function () {
		this.getdata();
	},
	onNavigationBarSearchInputConfirmed:function(e){
		this.searchConfirm({detail:{value:e.text}});
	},
	methods: {
		getdata:function(){
			var that = this;
			var nowcid = that.opt.cid;
			if (!nowcid) nowcid = '';
			var bid = that.opt.bid ? that.opt.bid : '';
			that.pagenum = 1;
			that.datalist = [];
			app.get('ApiHuodongBaoming/classify', {cid:nowcid,bid:bid}, function (res) {
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
			var keyword = that.keyword; 
			that.loading = true;
			that.nodata = false;
			that.nomore = false;
			app.post('ApiHuodongBaoming/getprolist', {pagenum: pagenum,field: field,order: order,cid: cid,bid:bid,cpid:cpid,keyword:keyword}, function (res) { 
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
 
		},
		searchConfirm:function(e){
					this.keyword = e.detail.value;
					this.getdatalist(false);
				},
		buydialogChange: function (e) {
			if(!this.buydialogShow){
				this.proid = e.currentTarget.dataset.proid
			}
			this.buydialogShow = !this.buydialogShow;
		},
		toDetail(e){
			var id = e.currentTarget.dataset.id;
			app.goto('/pagesB/huodongbaoming/product?id='+id);
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
.title-box{color:#000000;font-size:26rpx;justify-content:center;width: 100%;align-items:center;text-align: center;height:60rpx;line-height: 60rpx;}
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
.classify-box{padding: 0 0 20rpx 0;width: 100%;height:calc(100% - 60rpx);overflow-y: scroll; border-top:1px solid #F5F6F8;}
</style>