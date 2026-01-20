<template>
<view class="container">
	<block v-if="isload">
		<view @tap.stop="goto" :data-url="'/carhailing/prolist?bid='+bid" class="search-container">
			<view class="search-box">
				<image class="img" :src="pre_url+'/static/img/search_ico.png'""></image>
				<view class="search-text">搜索感兴趣的商品</view>
			</view>
		</view>
		<view class="order-tab">
			<view class="order-tab2">
				<block v-for="(item, index) in clist" :key="index">
					<view :class="'item ' + (curTopIndex == index ? 'on' : '')" @tap="switchTopTab" :data-index="index" :data-id="item.id">{{item.name}}<view class="after" :style="{background:t('color1')}"></view></view>
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
					<!-- <view class="nav-pai">
						<view class="nav-paili" :style="{color:(!field||field=='sort')?t('color1'):'#323232'}" @tap="changeOrder" data-field="sort" data-order="desc">综合</view> 
						<view class="nav-paili" :style="field=='sales'?'color:'+t('color1'):''" @tap="changeOrder" data-field="sales" data-order="desc">销量</view> 
						<view class="nav-paili" @tap="changeOrder" data-field="sell_price" :data-order="order=='asc'?'desc':'asc'">
							<text :style="field=='sell_price'?'color:'+t('color1'):''">价格</text>
							<text class="iconfont iconshangla" :style="field=='sell_price'&&order=='asc'?'color:'+t('color1'):''"></text>
							<text class="iconfont icondaoxu" :style="field=='sell_price'&&order=='desc'?'color:'+t('color1'):''"></text>
						</view>  
					</view> -->
					
					<scroll-view class="classify-box" scroll-y="true" @scrolltolower="scrolltolower">
						<view class="product-itemlist module">
							<view class="item" v-for="(item,index) in datalist" :key="item.id">
								<view class="module_data" @tap="goto" :data-url="'product?id='+item.id+''">
									<image :src="item.pic" class="module_img" alt=""/>
									<view class="module_content">
										<view class="module_title">{{item.name}}</view>
										<!-- <view class="module_item"  v-if="item.cid ==2"> <view class="module_time">{{item.starttime}}~{{item.endtime}}</view></view> -->
										<view class="module_item" v-if="item.cid != 2 ">
											<text v-if="item.cid==1">租车</text><text v-else>包车</text>费用：<text class="txt-yel">￥{{item.sell_price}} /天</text></view>
										<view class="module_item" v-else>拼车费用：<text class="txt-yel">￥{{item.sell_price}} /人</text></view>
										<!-- <view class="module_item">所在城市：{{area}}</view> -->
									</view>
									<!-- <view v-if="item.cid ==2">
										<view class="module_btn module_end" v-if="item.isend">已结束</view>
										<view class="module_btn" v-else-if="item.leftnum > 0">预约</view>
										<view class="module_btn module_end" v-else>满员</view>
									</view> -->
									<!-- <view v-else-if="item.cid ==1">
										<view class="module_btn " >租车</view>
									</view>
									<view v-else>
										<view class="module_btn " >包车</view>
									</view> -->
								</view>
								<!-- <view class="module_num" v-if="item.cid ==2">
									<view class="module_lable">
										<view>当前</view>
										<view>预约</view>
									</view>
									<view class="module_view">
										<block v-for="(item2,index2) in item.yyorderlist">
											<image :src="item2.headimg"/>
										</block>
									</view>
									<view class="module_tag" v-if="item.leftnum > 0">剩余{{item.leftnum}}个名额</view>
									<view class="module_tag module_end" v-else>满员</view>
								</view> -->
							</view>
						</view>
						<nomore text="没有更多商品了" v-if="nomore"></nomore>
						<nodata text="没有查找到相关商品" v-if="nodata"></nodata>
					</scroll-view>
				</view>
			</view>
		</view>
	</block>
	<buydialog v-if="buydialogShow" :proid="proid" @buydialogChange="buydialogChange" :menuindex="menuindex"></buydialog>
    <view class="posterDialog linkDialog" v-if="showLinkStatus">
    	<view class="main">
    		<view class="close" @tap="showLinkChange"><image class="img" :src="pre_url+'/static/img/close.png'"/></view>
    		<view class="content">
    			<view class="title">{{lx_name}}</view>
    			<view class="row" v-if="lx_bid > 0">
    				<view class="f1">店铺名称</view>
    				<view class="f2" @tap="goto" :data-url="'/pagesExt/business/index?id='+lx_bid">{{lx_bname}}<image :src="pre_url+'/static/img/arrowright.png'" class="image"/></view>
    			</view>
    			<view class="row" v-if="lx_tel">
    				<view class="f1">联系电话</view>
    				<view class="f2" @tap="goto" :data-url="'tel::'+lx_tel" :style="{color:t('color1')}">{{lx_tel}}<image :src="pre_url+'/static/img/copy.png'" class="copyicon" @tap.stop="copy" :data-text="lx_tel"></image></view>
    			</view>
    		</view>
    	</view>
    </view>
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
			buydialogShow: false,
      prodata: [],
      userinfo: [],
      nomore: false,
			nodata: false,
			bid:'',
            
            showLinkStatus:false,
            lx_name:'',
            lx_bid:'',
            lx_tel:'',
			area:'',
			pre_url:app.globalData.pre_url,
    };
  },
  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.bid = this.opt.bid ? this.opt.bid  : '';
		this.getdata();
		this.area = app.getCache('user_current_area_show');
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
			app.get('ApiCarHailing/classify2', {cid: nowcid,bid:that.bid}, function (res) {
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
			if(bid > 0){
				wherefield.cid2 = cid;
			}else{
				wherefield.cid = cid;
			}
			app.post('ApiCarHailing/getprolist',wherefield, function (res) { 
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
.search-container {width: 100%;height: 94rpx;padding: 16rpx 23rpx 14rpx 23rpx;background-color: #fff;position: relative;overflow: hidden;}
.search-box {display:flex;align-items:center;height:60rpx;border-radius:30rpx;border:0;background-color:#f7f7f7;flex:1}
.search-box .img{width:24rpx;height:24rpx;margin-right:10rpx;margin-left:30rpx}
.search-box .search-text {font-size:24rpx;color:#C2C2C2;width: 100%;}


.order-tab{display:flex;width:100%;overflow-x:scroll;border-bottom: 1px #f5f5f5 solid;background: #fff;padding:0 10rpx}
.order-tab2{display:flex;width:auto;min-width:100%}
.order-tab2 .item{width:auto;padding:0 20rpx;font-size:30rpx;font-weight:bold;text-align: center; color:#999999; height:90rpx; line-height:90rpx; overflow: hidden;position:relative;flex-shrink:0;flex-grow: 1;}
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
.product-itemlist .product-info .p1 {color:#323232;font-weight:bold;font-size:28rpx;line-height:36rpx;margin-bottom:0;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:2;overflow:hidden;}
.product-itemlist .product-info .p2{margin-top:10rpx;height:36rpx;line-height:36rpx;overflow:hidden;}
.product-itemlist .product-info .p2 .t1{font-size:32rpx;}
.product-itemlist .product-info .p2 .t2 {margin-left:10rpx;font-size:24rpx;color: #aaa;text-decoration: line-through;/*letter-spacing:-1px*/}
.product-itemlist .product-info .p3{display:flex;align-items:center;overflow:hidden;margin-top:10rpx}
.product-itemlist .product-info .p3-1{font-size:20rpx;height:30rpx;line-height:30rpx;text-align:right;color:#999}
.product-itemlist .product-info .p4{width:56rpx;height:56rpx;border-radius:50%;position:absolute;display:relative;bottom:6rpx;right:4rpx;text-align:center;}
.product-itemlist .product-info .p4 .icon_gouwuche{font-size:32rpx;height:56rpx;line-height:56rpx}
::-webkit-scrollbar{width: 0;height: 0;color: transparent;}

.lianxi{color: #fff;border-radius: 50rpx 50rpx;line-height: 50rpx;text-align: center;font-size: 22rpx;padding: 0 14rpx;display: inline-block;float: right;}

.member{position: relative;border-radius: 8rpx;border: 1rpx solid #fd4a46;overflow: hidden;margin-top: 10rpx;box-sizing: content-box;}
.member_lable{height: 100%;font-size: 22rpx;color: #fff;background: #fd4a46;padding: 0 10rpx;}
.member_value{padding: 0 10rpx;font-size: 20rpx;color: #fd4a46;}
.module{
		position: relative;
		padding: 30rpx 10rpx;
		box-sizing: border-box;
		border-radius: 20rpx;
		margin: 0 auto 30rpx auto;
		background: #fff;
	}
	.module_data{
		display: flex;
	}
	.module_img{
		height: 130rpx;
		width: 130rpx;
		margin-right: 30rpx;
	}
	.module_content{
		flex: 1;
	}
	.txt-yel{color:#FF9900}
	.module_btn{
		height: 65rpx;
		padding: 0 40rpx;
		color: #fff;
		font-size: 28rpx;
		display: flex;
		align-items: center;
		justify-content: center;
		border-radius: 100rpx;
		background: #0993fe;
	}
	.module_title{
		font-size: 28rpx;
		color: #333;
		line-height:36rpx;
		height:72rpx;
		display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:2;overflow:hidden;
	}
	.module_item{
		margin-top: 10rpx;
		color: #999;
		display: flex;
		align-items: center;
		font-size: 24rpx;
	}
	.module_time{
		padding: 0 10rpx;
		height: 35rpx;
		line-height: 33rpx;
		font-size: 22rpx;
		color: #d55c5f;
		border: 1rpx solid #d55c5f;
	}
	.module_num{
		display: flex;
		align-items: center;
		margin-top: 20rpx;
	}
	.module_lable{
		font-size: 24rpx;
		color: #666;
		line-height: 24rpx;
		border-right: 1px solid #e0e0e0;
		padding: 0 15rpx 0 0;
		margin-right: 15rpx;
	}
	.module_view{
		display: flex;
		flex: 1;
		align-items: center;
	}
	.module_view image{
		height: 60rpx;
		width: 60rpx;
		border-radius: 100rpx;
		margin-right: 10rpx;
	}
	.module_tag{
		height: 50rpx;
		background: #fefae8;
		color: #b37e4b;
		font-size: 24rpx;
		padding: 0 10rpx;
		line-height: 50rpx;
	}
	.module_end{
		color: #999;
		background: #f0f0f0;
	}
	
</style>