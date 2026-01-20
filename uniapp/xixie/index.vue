<template>
<view class="container">
	<block v-if="isload">
		<view class="topsearch flex-y-center">
			<view class="f1 flex-y-center">
				<image class="img" src="/static/img/search_ico.png"></image>
				<input :value="keyword" placeholder="搜索感兴趣的商品" placeholder-style="font-size:24rpx;color:#C2C2C2" @confirm="searchConfirm" @input="searchChange" ></input>
				<!-- <view class="camera" v-if="set.image_search == 1" @tap="goto" data-url="/pagesExt/shop/imgsearch" :style="'background-image:url('+pre_url+'/static/img/camera.png)'"></view> -->
			</view>
			<view class="search-btn" @tap="searchproduct">
				<text >搜索</text>
			</view>
		</view>
		<view class="order-tab">
			<view class="order-tab2">
				<block v-for="(item, index) in clist" :key="index">
					<view :class="'item ' + (curTopIndex == index ? 'on' : '')" @tap="switchTopTab" :data-index="index" :data-id="item.id">
                        <view class="top_menu_pic" :style="curTopIndex == index? 'border-color:'+t('color1'): ''">
                            <view style="width: 80rpx;height: 80rpx;margin: 0 auto;border-radius: 50%;overflow: hidden;">
                                <image :src="item.pic" style="width: 80rpx;height: 80rpx;border-radius: 50%;"></image>
                            </view>
                        </view>
                        <view>{{item.name}}</view>
                        <view class="after" :style="{background:t('color1')}"></view>
                    </view>
				</block>
			</view>
		</view>
		<view class="content-container">
			<view class="nav_left">
				<view :class="'nav_left_items ' + (curIndex == -1 ? 'active' : '')" @tap="switchRightTab" data-index="-1" :data-id="clist[curTopIndex].id"><view class="before" :style="{background:t('color1')}"></view>全部</view>
				<block v-for="(item, index2) in clist[curTopIndex].child" :key="index2">
					<view :class="'nav_left_items ' + (curIndex == index2 ? 'active' : '')" @tap="switchRightTab" :data-index="index2" :data-id="item.id"><view class="before" :style="{background:t('color1')}"></view>{{item.name}}</view>
				</block>
			</view>
			<view class="nav_right">
				<view class="nav_right-content">
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
							<view class="item" v-for="(item,index) in datalist" :key="item.id">
								<view class="product-pic">
									<image class="image" :src="item.pic" mode="widthFix"/>
								</view>
								<view class="product-info">
									<view class="p1"><text>{{item.name}}</text></view>
                                    <view class="p2" v-if="item.vip_price && item.vip_price>0" style="overflow: hidden;height: 40rpx;">
                                        <view style="background-color: #CC932E;color: #fff;display: inline-block;padding: 0 10rpx;border-radius: 6rpx 0 0 6rpx;font-size: 22rpx;float: left;line-height: 40rpx;">会员价</view>
                                        <view class="t1"  :style="{backgroundColor:t('color1'),color:'#fff',display:'inline-block',borderRadius:'0 6rpx 6rpx 0',padding:'0 20rpx',lineHeight: '40rpx',float:'left',fontSize:'24rpx' }">
                                            <text style="font-size:20rpx;padding-right:1px">￥</text>
                                            {{item.vip_price}}
                                        </view>
                                    </view>
									<view class="p2" style="overflow:unset">
										<text class="t1" :style="{color:t('color1')}"><text style="font-size:20rpx;padding-right:1px">￥</text>{{item.sell_price}}</text>
                                        <view style="float: right;">
                                            <view v-if="!item.gwcnum || item.gwcnum<=0" @tap="gwcplus" :data-proid="item.id" style="float: right;">
                                                <text class="iconfont icon_gouwuche" :style="{color:t('color1')}"></text>
                                            </view>
                                            <view  v-if="item.gwcnum && item.gwcnum>0" class="addnum" style="margin-top: 8rpx;">
                                                <view class="minus" @tap="gwcminus" :data-proid="item.id"><image class="img" src="/static/img/cart-minus.png"/></view>
                                                <input class="input" type="number" :value="item.gwcnum?item.gwcnum:1" :data-proid="item.id" @input="gwcinput"></input>
                                                <view class="plus" @tap="gwcplus" :data-proid="item.id"><image class="img" src="/static/img/cart-plus.png"/></view>
                                            </view>
                                        </view>
									</view>
								</view>
							</view>
						</view>
						<nomore text="没有更多商品了" v-if="nomore"></nomore>
						<nodata text="没有查找到相关商品" v-if="nodata"></nodata>
					</scroll-view>
				</view>
			</view>
		</view>
	</block>
    <block v-if="display_buy">
        <dp-xixie-buycart :cartnum="cartnum" :cartprice="cartprice" :color="t('color1')" :colorrgb="t('color1rgb')"></dp-xixie-buycart>
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

            nomore: false,
            nodata: false,
            keyword:'',
            
            display_buy:'',
            cartnum:0,
            cartprice:0,
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
		getdata: function () {
			var that = this;
			var nowcid = that.opt.cid;
			if (!nowcid) nowcid = '';
			that.loading = true;
			app.get('ApiXixie/prolist', {cid: nowcid}, function (res) {
				that.loading = false;
				var data = res.data;
				that.clist = data;
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
                that.getXixieData();
				that.loaded();
				that.getdatalist();
			});
            
		},
        getXixieData:function(){
            var that = this;
            app.post('ApiXixie/get_xixie_data',{id:0}, function (res) {
                if(res.status == 1){
                    var xdata       = res.xdata;
                    that.display_buy= xdata.display_buy?xdata.display_buy:false;
                    that.cartnum    = xdata.cartnum?xdata.cartnum:0;
                    that.cartprice  = xdata.cartprice?xdata.cartprice:0;
                }else{
                    app.alert(res.msg)
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
			var order = that.order;
			var field = that.field; 
            var keyword = that.keyword;
			that.loading = true;
			that.nodata = false;
			that.nomore = false;
			var wherefield = {};
			wherefield.pagenum = pagenum;
			wherefield.field = field;
			wherefield.order = order;
			wherefield.cid = cid;
            wherefield.keyword = keyword;

			app.post('ApiXixie/getprolist',wherefield, function (res) { 
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
        //加
        gwcplus: function (e) {
            var that = this;
            var proid    =  e.currentTarget.dataset.proid;
            var datalist = that.datalist;
            var len      = datalist.length;
            var num = 0;
            var add_i= -1;
            if(len>0){
                for(var i=0;i<len;i++){
                    if(datalist[i]['id'] == proid){
                        add_i = i;
                        //datalist[i]['gwcnum']+= 1;
                        num = datalist[i]['gwcnum']+1;
                        if(datalist[i]['buymax']>0 && datalist[i]['gwcnum']>datalist[i]['buymax']){
                            //datalist[i]['gwcnum'] = datalist[i]['buymax'];
                            num = datalist[i]['buymax'];
                            app.alert('每人限购'+datalist[i]['buymax']);
                            break;
                            return;
                        }
                        //num = datalist[i]['gwcnum'];
                    }
                }
            }
            that.addcart(proid,num,add_i,datalist);
        },
        //减
        gwcminus: function (e) {
            var that = this;
            var proid =  e.currentTarget.dataset.proid;
            var datalist = that.datalist;
            var len  = datalist.length;
            var num = 0;
            var add_i= -1;
            if(len>0){
                for(var i=0;i<len;i++){
                    if(datalist[i]['id'] == proid){
                        add_i = i;
                        //datalist[i]['gwcnum'] -= 1;
                        num = datalist[i]['gwcnum']-1;
                        if(datalist[i]['gwcnum']<=0){
                            //datalist[i]['gwcnum'] = 0;
                            num = 0;
                            //that.addshow = false;
                        }
                        //num = datalist[i]['gwcnum'];
                    }
                }
            }
            that.addcart(proid,num,add_i,datalist);
        },
        //输入
        gwcinput: function (e) {
        	var ks = this.ks;
        	var input_num = parseInt(e.detail.value);
        	if (input_num !== input_num){
                input_num = 1;
            } 
            var that = this;
            var proid    =  e.currentTarget.dataset.proid;
            var datalist = that.datalist;
            var len      = datalist.length;
            var num = 0;
             var add_i= -1;
            if(len>0){
                for(var i=0;i<len;i++){
                    if(datalist[i]['id'] == proid){
                        add_i = i;
                        //datalist[i]['gwcnum'] = input_num;
                        num = input_num;
                        if(datalist[i]['buymax']>0 && datalist[i]['gwcnum']>datalist[i]['buymax']){
                            // datalist[i]['gwcnum'] = datalist[i]['buymax'];
                            // num = datalist[i]['gwcnum'];
                            num = datalist[i]['buymax'];
                            app.alert('每人限购'+datalist[i]['buymax']);
                            break;
                            return;
                        }
                        if(datalist[i]['gwcnum']<=0){
                            //datalist[i]['gwcnum'] = 0;
                            num = 0;
                            //that.addshow = false;
                        }
                        //num = datalist[i]['gwcnum'];
                    }
                }
            }
            
            that.addcart(proid,num,add_i,datalist);
        },
        //加入购物车操作
        addcart: function (proid,num,i,datalist) {
        	var that = this;
            app.post('ApiXixie/addcart', {proid: proid,num: num}, function (res) {
                if (res.status == 1) {
                    if(num<=0){
                        that.addshow = false;
                    }
                    if(i>=0){
                        datalist[i]['gwcnum'] = num;
                    }
                    that.datalist = datalist;
                    that.getXixieData();
                } else {
                    app.error(res.msg);
                }
            });
        	//this.$emit('addcart',{proid: proid,num: num});
        	this.$emit('buydialogChange');
        },
        searchConfirm: function (e) {
          var that = this;
          var keyword = e.detail.value;
          that.keyword = keyword;
          that.searchproduct();
        },
        searchChange: function (e) {
          this.keyword = e.detail.value;
        },
        searchproduct: function () {
            var that = this;
            that.pagenum = 1;
            that.datalist = [];
            that.getdatalist();
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
.order-tab2 .item{width:auto;padding:0 20rpx;font-size:28rpx;font-weight:bold;text-align: center; color:#999999; line-height:50rpx; overflow: hidden;position:relative;flex-shrink:0;flex-grow: 1;}
.order-tab2 .on{color:#222222;}
.order-tab2 .after{display:none;bottom:10rpx;height:6rpx;border-radius:1.5px;width:40rpx;margin: 0 auto;}
.order-tab2 .on .after{display:block}
.order-tab2 .on .after{display:block}
.content-container{flex:1;height:100%;display:flex;overflow: hidden;}

.nav_left{width: 25%;height:100%;background: #ffffff;overflow-y:scroll;}
.nav_left .nav_left_items{line-height:50rpx;color:#999999;border-bottom:0px solid #E6E6E6;font-size:28rpx;position: relative;border-right:0 solid #E6E6E6;padding:25rpx 30rpx;}
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

.classify-box{padding: 0 0 20rpx 0;width: 100%;height:calc(100% - 0rpx);overflow-y: scroll; border-top:1px solid #F5F6F8;}
.classify-box .nav_right_items{ width:100%;border-bottom:1px #f4f4f4 solid;  padding:16rpx 0;  box-sizing:border-box;  position:relative; }

.product-itemlist{height: auto; position: relative;overflow: hidden; padding: 0px; display:flex;flex-wrap:wrap}
.product-itemlist .item{width:100%;display: inline-block;position: relative;margin-bottom: 12rpx;background: #fff;display:flex;padding:14rpx 0;border-radius:10rpx;border-bottom:1px solid #F8F8F8}
.product-itemlist .product-pic {width: 30%;height:0;overflow:hidden;background: #ffffff;padding-bottom: 30%;position: relative;border-radius:4px;}
.product-itemlist .product-pic .image{position:absolute;top:0;left:0;width: 100%;height:auto}
.product-itemlist .product-pic .saleimg{ position: absolute;width: 120rpx;height: auto; top: -6rpx; left:-6rpx;}
.product-itemlist .product-info {width: 70%;padding:0 10rpx 5rpx 20rpx;position: relative;}
.product-itemlist .product-info .p1 {color:#323232;font-weight:bold;font-size:28rpx;line-height:30rpx;margin-bottom:0;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:2;overflow:hidden;height:60rpx}
.product-itemlist .product-info .p2{margin-top:10rpx;height:60rpx;line-height:60rpx;overflow:hidden;}
.product-itemlist .product-info .p2 .t1{font-size:28rpx;}
.product-itemlist .product-info .p2 .t2 {margin-left:10rpx;font-size:24rpx;color: #aaa;text-decoration: line-through;/*letter-spacing:-1px*/}
.product-itemlist .product-info .p3{display:flex;align-items:center;overflow:hidden;margin-top:10rpx}
.product-itemlist .product-info .p3-1{font-size:20rpx;height:30rpx;line-height:30rpx;text-align:right;color:#999}
.product-itemlist .product-info .p4{width:48rpx;height:48rpx;border-radius:50%;position:absolute;display:relative;bottom:6rpx;right:4rpx;text-align:center}
.product-itemlist .product-info .p4 .icon_gouwuche{font-size:28rpx;height:48rpx;line-height:48rpx}
::-webkit-scrollbar{width: 0;height: 0;color: transparent;}

.buynum{ width: 94%; position: relative; margin: 0 3%; padding:10px 0px 10px 0px; }
.addnum {font-size: 30rpx;color: #666;width:auto;display:flex;align-items:center;float: right;}
.addnum .plus {width:48rpx;height:36rpx;background:#F6F8F7;display:flex;align-items:center;justify-content:center}
.addnum .minus {width:48rpx;height:36rpx;background:#F6F8F7;display:flex;align-items:center;justify-content:center}
.addnum .img{width:24rpx;height:24rpx}
.addnum .input{flex:1;width:70rpx;border:0;text-align:center;color:#2B2B2B;font-size:24rpx}

.topsearch{width:100%;padding:16rpx 20rpx;}
.topsearch .f1{height:60rpx;border-radius:30rpx;border:0;background-color:#f7f7f7;flex:1}
.topsearch .f1 .img{width:24rpx;height:24rpx;margin-left:10px}
.topsearch .f1 input{height:100%;flex:1;padding:0 20rpx;font-size:28rpx;color:#333;}
.topsearch .f1 .camera {height:72rpx;width:72rpx;color: #666;border: 0px;padding: 0px;margin: 0px;background-position: center;background-repeat: no-repeat; background-size:40rpx;}
.topsearch .search-btn{display:flex;align-items:center;color:#5a5a5a;font-size:30rpx;width:60rpx;text-align:center;margin-left:20rpx}
.search-navbar {display: flex;text-align: center;align-items:center;padding:5rpx 0}
.search-navbar-item {flex: 1;height: 70rpx;line-height: 70rpx;position: relative;font-size:28rpx;font-weight:bold;color:#323232}

.top_menu_pic{width: 100rpx;height: 100rpx;padding: 10rpx;margin: 0 auto;border: 2rpx solid #fff;border-radius: 50%;}

</style>