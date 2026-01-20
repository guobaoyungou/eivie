<template>
<view class="container">
	<block v-if="isload">
		<view class="content-container">
			<view class="nav_left">
			
				<block v-for="(item, index) in clist" :key="index">
					<view :class="'nav_left_items ' + (curIndex == index ? 'active' : '')" :style="{color:curIndex == index?t('color1'):'#333'}" @tap="switchRightTab" :data-index="index" :data-id="item.id"><view class="before" :style="{background:t('color1')}"></view>{{item.name}}</view>
				</block>
			</view>
			<view class="nav_right">
				<view class="nav_right-content">

					<scroll-view class="classify-box" scroll-y="true" @scrolltolower="scrolltolower">
						<view class="product-itemlist">
							<view class="item" v-for="(item,idx2) in datalist" :key="item.id"  v-if="iscate==1" >
								<view class="product-info">
										<view class="img" @tap="changeCTab" :data-id="item.id" :data-index="idx2" ><image :src="pre_url+'/static/img/workorder/'+(curIndex2==idx2?'down':'up')+'.png?v1'" mode="widthFix"/></view>
									<view class="p1" @click="goto" :data-url="'record?cid='+item.id" >
										<text>{{item.name}}</text>
										<text class="count" v-if="item.count>0">{{item.count}}</text>
									</view>
						
								</view>
								<view class="list" v-if="curIndex2==idx2" v-for="(subitem,subindex) in item.list" @tap="goto" :data-url="'record?cateid='+subitem.id">
									<label> {{subitem.name}}</label><text class="iconfont iconjiantou" style="color:#999;font-weight:normal"></text>
									
								</view>
							</view>
							<view v-else class="item" >
								<view class="product-info" >
									<view class="list" style="width: 100%;position: relative;" v-for="(subitem,subindex) in datalist" @tap="goto" :data-url="'record?cateid='+subitem.id" >
										<label> {{subitem.name}}</label><text class="iconfont iconjiantou" style="color:#999;font-weight:normal"></text>
										<text class="count" style="position: absolute;top:10rpx; right: 30rpx" v-if="subitem.count>0">{{subitem.count}}</text>
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
			curIndex:0,
			curIndex2: 0,
			datalist: [],
			curCid: 0,
			proid:0,
			buydialogShow: false,
			bid:'',  
			isshow:false,
			pre_url:app.globalData.pre_url,
			iscate:0
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
			app.get('ApiAdminWorkorder/getclassify', {cid:nowcid,bid:that.bid}, function (res) {
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
			var cid = that.curCid?that.curCid:that.clist[that.curIndex].id;
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
			console.log(wherefield);
			app.post('ApiAdminWorkorder/getsubcate',wherefield, function (res) { 
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
				that.iscate = res.iscate
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
			console.log(index)
			this.curIndex2 = index;
			this.curCid = id;
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
 
		}
,
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
	}

};
</script>
<style>
page {height:100%;}
.container{width: 100%;height:100%;max-width:640px;background-color: #fff;color: #939393;display: flex;flex-direction:column}
.content-container{flex:1;height:100%;display:flex;overflow: hidden;}

.nav_left{width: 25%;height:100%;background: #ffffff;overflow-y:scroll;}
.nav_left .nav_left_items{line-height:50rpx;color:#333;font-weight:bold;border-bottom:0px solid #E6E6E6;font-size:28rpx;position: relative;border-right:0 solid #E6E6E6;padding:25rpx 20rpx;}
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
.product-itemlist .item{width:100%;display: inline-block;position: relative;margin-bottom: 12rpx;background: #fff;padding:14rpx 0;border-radius:10rpx;border-bottom:1px solid #F8F8F8}
.product-itemlist .product-pic {width: 30%;height:0;overflow:hidden;background: #ffffff;padding-bottom: 30%;position: relative;border-radius:4px;}
.product-itemlist .product-pic .image{position:absolute;top:0;left:0;width: 100%;height:auto}
.product-itemlist .product-pic .saleimg{ position: absolute;width: 120rpx;height: auto; top: -6rpx; left:-6rpx;}
.product-itemlist .product-info {width: 100%;padding:0 10rpx 5rpx 20rpx;position: relative; display: flex; }

.product-itemlist .product-info .p1 {color:#323232;font-weight:bold;font-size:28rpx;line-height:36rpx;margin-bottom:0;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:2;overflow:hidden;height:72rpx; line-height: 72rpx; margin-bottom:10rpx;}
.product-itemlist .product-info .img{ display: flex; align-items: center;}
.product-itemlist .product-info .img image{ width: 38rpx; height: 38rpx;}
.product-itemlist .product-info .count{ display: inline-block; background-color: #FC5648;color: #fff; border-radius:50%; width:36rpx;height: 36rpx; line-height:36rpx; text-align:center;margin-left: 20rpx; }

.product-itemlist .list{ height: 60rpx; padding-left: 30rpx;display: flex;justify-content: space-between;align-items: center;}

</style>