<template>
<view class="container">
	<block v-if="isload">
		<view class="search-container">
			<view class="topsearch flex-y-center">
				<view class="f1 flex-y-center">
					<image class="img" :src="pre_url+'/static/img/search_ico.png'"></image>
					<input :value="keyword" placeholder="搜索你感兴趣的商家" placeholder-style="font-size:24rpx;color:#C2C2C2" confirm-type="search" @confirm="search"></input>
				</view>
			</view>
			<view class="search-navbar">
				<view @tap.stop="sortClick" class="search-navbar-item" :style="field=='juli'?'color:'+t('color1'):''" data-field="juli" data-order="asc">距离最近</view>
				<view @tap.stop="sortClick" class="search-navbar-item" :style="field=='comment_score'?'color:'+t('color1'):''" data-field="comment_score" data-order="desc">评分排序</view>
				<view @tap.stop="sortClick" class="search-navbar-item" data-field="sales" :data-order="order=='asc'?'desc':'asc'">
					<text :style="field=='sales'?'color:'+t('color1'):''">{{showviewnum ? '浏览排序': '销量排序'}}</text>
					<text class="iconfont iconshangla" :style="field=='sales'&&order=='asc'?'color:'+t('color1'):''"></text>
					<text class="iconfont icondaoxu" :style="field=='sales'&&order=='desc'?'color:'+t('color1'):''"></text>
				</view>
				<view class="search-navbar-item flex-x-center flex-y-center" @click.stop="showDrawer('showRight')">筛选 <text :class="'iconfont iconshaixuan ' + (showfilter?'active':'')"></text></view>
			</view>
		</view>
		<uni-drawer ref="showRight" mode="right" @change="change($event,'showRight')" :width="280">
			<view class="filter-scroll-view">
				<scroll-view class="filter-scroll-view-box" scroll-y="true">
					<view class="search-filter">
						<view class="filter-title">筛选</view>
						<view class="filter-content-title">商家分类</view>
						<scroll-view scroll-y style="max-height: 66vh;height:auto;">
							<view class="search-filter-content">
								<view class="filter-item" :style="catchecid=='all'?'color:'+t('color1')+';background:rgba('+t('color1rgb')+',0.1)':''" @tap.stop="cateClick" data-cid="all">全部</view>
								<block v-for="(item, index) in clist" :key="index">
									<view class="filter-item" :style="catchecid==item.id?'color:'+t('color1')+';background:rgba('+t('color1rgb')+',0.1)':''" @tap.stop="cateClick" :data-cid="item.id">{{item.name}}</view>
								</block>
							</view>
						</scroll-view>
						<view class="search-filter-btn">
							<view class="btn btn-class" @tap="filterReset">重置</view>
							<view class="btn2 btn-class" :style="{background:t('color1')}" @tap="filterConfirm">确定</view>
						</view>
					</view>
				</scroll-view>
			</view>
		</uni-drawer>
		
		<view class="ind_business">
			<view class="ind_buslist" id="datalist" v-if="show_style ==0">
				<block v-for="(item, index) in datalist" :key="index">
				<view @tap="goto" :data-url="item.tourl ? item.tourl : '/pagesExt/business/index?id=' + item.id">
					<view class="ind_busbox flex1 flex-row">
						<view class="ind_buspic flex0"><image :src="item.logo"></image></view>
						<view class="flex1" style="width: 70%;">
              <view style="display: flex;justify-content: space-between;align-items: center;">
                <view class="bus_title">{{item.name}}</view>
                <view class="bus_sales" v-if="show_maidanscoredk && item.maidanscoredk_text!=''" style="margin: 0 10rpx;color:#FF9C00">{{item.maidanscoredk_text}}</view>
                <view class="bus_sales" v-if="showviewnum">浏览：{{item.viewnum}}</view>
                <view class="bus_sales" v-else><text v-if="show_sales==1">销量：{{item.sales}}</text></view>
              </view>
							<view class="bus_score">
                <view style="display:flex;align-items:center;">
                  <image class="img" v-for="(item2,index2) in [0,1,2,3,4]" :src="pre_url+'/static/img/star' + (item.comment_score>item2?'2native':'') + '.png'"/>
                  <view class="txt">{{item.comment_score}}分</view>
                </view>
                <view class="bus_sales" v-if="item.turnover_show == 1" :style="{color:t('color1')}">营业额：{{item.turnover}}</view>
							</view>
							<view class="reward_member" v-if="item.reward_member==1" :style="{color:t('color1')}">打赏：{{item.reward_member_bili}}</view>
							<view class="bus_address" v-if="item.address" @tap.stop="openLocation" :data-latitude="item.latitude" :data-longitude="item.longitude" :data-company="item.name" :data-address="item.address"><image :src="pre_url+'/static/img/b_addr.png'" style="width:26rpx;height:26rpx;margin-right:10rpx"/><text class="x1">{{item.address}}</text><text class="x2">{{item.juli}}</text></view>
							<view class="bus_address" v-if="item.tel" @tap.stop="phone" :data-phone="item.tel"><image :src="pre_url+'/static/img/b_tel.png'" style="width:26rpx;height:26rpx;margin-right:10rpx"/><text class="x1">联系电话：{{item.tel}}</text></view>
							<view class="bus_address" v-if="item.activity_time && item.activity_time_status==1" :style="{color:t('color1')}" >
								<image :src="pre_url+'/static/img/kaishi.png'" style="width:26rpx;height:26rpx;margin-right:10rpx"/>
								活动时间：<text class="x1">{{item.activity_time}}</text>
							</view>
							<view class="ratio-list flex">
								<view class="ratio-label flex-y-center" v-if="item.rate_back && item.rate_back > 0" :style="{color:t('color1'),borderColor:t('color1')}">
									<view class="label" :style="{backgroundColor:t('color1')}">返</view>
									<view class="t1">{{item.rate_back}}%</view>
								</view>
								<view class="ratio-label flex-y-center" v-if="item.scoredkmaxpercent && item.scoredkmaxpercent > 0"  :style="{color:t('color1'),borderColor:t('color1')}">
									<view class="label" :style="{backgroundColor:t('color1')}">积</view>
									<view class="t1">{{item.scoredkmaxpercent}}%</view>
								</view>
								<view class="ratio-label flex-y-center" v-if="!isNull(item.activecoin_bili) && item.activecoin_bili > 0"  :style="{color:t('color1'),borderColor:t('color1'), width: '220rpx'}">
									<view class="label" :style="{backgroundColor:t('color1'), whiteSpace: 'nowrap', width: '120rpx'}">{{t('激活币')}}</view>
									<view class="t1">{{item.activecoin_bili}}%</view>
								</view>
								<view class="ratio-label flex-y-center" v-if="!isNull(item.newscore_ratio) && item.newscore_ratio > 0"  :style="{color:t('color1'),borderColor:t('color1'), width: '220rpx'}">
									<view class="label" :style="{backgroundColor:t('color1'), whiteSpace: 'nowrap', width: '120rpx'}">{{t('新积分')}}</view>
									<view class="t1">{{item.newscore_ratio}}%</view>
								</view>
								<view class="ratio-label flex-y-center" v-if="!isNull(item.newscore_pack_ratio) && item.newscore_pack_ratio > 0"  :style="{color:t('color1'),borderColor:t('color1'), width: '220rpx'}">
									<view class="label" :style="{backgroundColor:t('color1'), width: '120rpx'}">{{t('加速包')}}</view>
									<view class="t1">{{item.newscore_pack_ratio}}%</view>
								</view>
								<view class="ratio-label flex-y-center" v-if="!isNull(item.subsidy_ratio) && item.subsidy_ratio > 0"  :style="{color:t('color1'),borderColor:t('color1'), width: '220rpx'}">
									<view class="label" :style="{backgroundColor:t('color1'), whiteSpace: 'nowrap', width: '120rpx'}">{{t('让利')}}</view>
									<view class="t1">{{item.subsidy_ratio}}%</view>
								</view>
							</view>
						
							<view class="flex queue-free" v-if=" item.queue_free_rate_back >0">
								<view class="queue-free-ratio flex" :style="{color:t('color1'),borderColor:t('color1')}">
									<view class="icon-div" :style="{backgroundColor:t('color1')}">
										<image class="icon" :src="pre_url+'/static/img/qianbao.png'"></image>
									</view>
									最高排队补贴 {{item.queue_free_rate_back}}%
								</view>
							</view>
							<view class="flex queue-free" v-if="item.dedamount_maxdkpercent && item.dedamount_maxdkpercent >0" style="justify-content: flex-start;">
								<view class="queue-free-ratio flex" :style="{color:t('color1'),borderColor:t('color1')}">
									<view class="icon-div" :style="{backgroundColor:t('color1')}">
										<image class="icon" :src="pre_url+'/static/img/qianbao.png'"></image>
									</view>
									抵扣 {{item.dedamount_maxdkpercent}}%
								</view>
							</view>
              <view v-if="type && type=='businessexpert'" style="width: 100%;display: flex;justify-content: flex-end;">
                <view @tap.stop="chooseit" :data-id="item.id" :data-name="item.name" class="chooseit" :style="'background-color:'+t('color1')">选择</view>
              </view>
							<block v-if="showtype == 0">
							<view class="currentScroll" style="width: 510rpx; overflow-x: scroll;">
							<view class="prolist" v-if="(item.prolist).length > 0 || (item.restaurantProlist && (item.restaurantProlist).length > 0)">
								<view v-for="(item2, index2) in item.prolist" class="product" @tap.stop="goto" :data-url="'/pages/shop/product?id=' + item2.id">
									<image class="f1" :src="item2.pic"></image>
									<view class="f2">￥{{item2.sell_price}}</view>
								</view>
								<block v-if="item.restaurantProlist && (item.restaurantProlist).length > 0">
									<view v-for="(item3, index3) in item.restaurantProlist" :key="index3" class="product" @tap.stop="goto" :data-url="'/restaurant/takeaway/product?id=' + item3.id">
									 	<image class="f1" :src="item3.pic"></image>
									 	<view class="f2">￥{{item3.sell_price}}</view>
									 </view>
								</block>
							</view>
							</view>
							</block>
							<block v-if="showtype == 1">
							<view class="prolist2" v-if="(item.prolist).length > 0 || (item.restaurantProlist && (item.restaurantProlist).length > 0)">
								<view v-for="(item2, index2) in item.prolist" class="product" @tap.stop="goto" :data-url="'/pages/shop/product?id=' + item2.id">
									<image class="f1" :src="item2.pic"></image>
									<view class="f2">￥{{item2.sell_price}}</view>
									<view class="f3" v-if="item2.market_price">￥{{item2.market_price}}</view>
									<view class="f4">已售{{item2.sales}}件</view>
									<view class="f5" :style="{background:'rgba('+t('color1rgb')+',0.1)',color:t('color1')}" @click.stop="buydialogChange" :data-proid="item2.id"><text class="iconfont icon_gouwuche"></text></view>
								</view>
							</view>
							</block>
						</view>
					</view>
				</view>
				<!-- 选择设置首消店铺 -->
				<view class="op" v-if="(setfirstbuy || setlastbuy) && type == 'buylog'">
					<view class="ops">
						<view class="btn" :data-bid="item.id" data-type='firstbuy' @tap.stop="setFirstBuyBusiness" v-if="setfirstbuy && item.id != firstbuy_bid">设为默认店铺</view>
						<view class="btn" :data-bid="item.id" data-type='lastbuy' @tap.stop="setFirstBuyBusiness" v-if="setlastbuy && item.id != lastbuy_bid">设为常用店铺</view>
					</view>
				</view>
				<!-- 选择设置首消店铺 end -->
				</block>
				<nomore v-if="nomore"></nomore>
				<nodata v-if="nodata"></nodata>
			</view>
			
			<view class="busbox2"  v-for="(item, index) in datalist" :key="item.id" @tap="goto"  :data-url="'/pagesA/business/businessindex?id='+item.id" v-if="show_style ==1">
				<view class="new_blist" >
					<view class="f1"><image class="image" :src="item.logo" mode="aspectFill"  /></view>
					<view class="f2">
						<view class="t1">{{item.name}}</view>
						<view class="t2"><image class="image" :src="pre_url+'/static/img/telphone.png'" mode="widthFix"/>{{item.tel}}</view>
						<view class="t2" ><image class="image" :src="pre_url+'/static/img/position.png'" mode="widthFix"/>
							<text class="text">{{item.address}}</text>
						</view>
						<view class="t2" v-if="item.reward_member==1" :style="{color:t('color1')}">打赏：{{item.reward_member_bili}}</view>
					</view>
					<view class="f3">
						<image class="image" :src="pre_url+'/static/img/calltel.png'" @tap.stop="phone" :data-phone="item.tel" mode="widthFix" />
						<view  style="color: #EC4149;font-size: 26rpx;margin-top: 10rpx;">{{item.juli}}</view>
					</view>
				</view>
			</view>
			
		</view>
		<buydialog v-if="buydialogShow" :proid="proid" @addcart="addcart" @buydialogChange="buydialogChange" :menuindex="menuindex"></buydialog>
	</block>
	<loading v-if="loading"></loading>
	<dp-tabbar :opt="opt"></dp-tabbar>
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
      field: 'juli',
			order:'asc',
      oldcid: "",
      catchecid: "all",
      longitude: '',
      latitude: '',
			clist:[],
      datalist: [],
      pagenum: 1,
      keyword: '',
      cid: '',
      nomore: false,
      nodata: false,
      types: "",
      showfilter: "",
			showtype:0,
			buydialogShow:false,
			proid:0,
			showviewnum:false,
			show_style:0,
      ids:'',
      show_maidanscoredk:false,
      type:'', //buylog 购买过的商家列表
      setfirstbuy:false, //选择设置首消店铺
      firstbuy_bid:0,//当前首消店铺
      setlastbuy:false, //选择设置末消店铺
      lastbuy_bid:0,//当前末消店铺
	  show_sales:1,//是否显示销量
    };
  },

  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.oldcid = this.opt.cid;
		this.catchecid = this.opt.cid || 'all';
		this.cid = this.opt.cid;
    if(this.opt.keyword) {
      this.keyword = this.opt.keyword;
    }
    this.ids = this.opt.ids || '';
    if(this.opt.type){
      this.type = this.opt.type;
      if(this.type == 'buylog'){
        uni.setNavigationBarTitle({
          title: '购买商家列表'
        });
      }
    }
    this.getdata();
  },
	onShow() {
		if(app.globalData.platform=='wx' && app.globalData.hide_home_button==1){
		  uni.hideHomeButton();
		}
	},
	onPullDownRefresh: function () {
		this.getdata();
	},
  onReachBottom: function () {
    if (!this.nodata && !this.nomore) {
      this.pagenum = this.pagenum + 1;
      this.getDataList(true);
    }
  },
  methods: {
		getdata: function () {
			var that = this;
			that.loading = true;
			app.get('ApiBusiness/blist', {type:that.type}, function (res) {
				that.loading = false;
				that.clist = res.clist;
				that.showtype = res.showtype || 0;
				that.showviewnum = res.showviewnum || false;
				that.show_style = res.show_style;
        if(res.show_maidanscoredk){
          that.show_maidanscoredk = res.show_maidanscoredk;
        }
				
				//选择设置首消店铺
				if(res.setfirstbuy){
					that.setfirstbuy = res.setfirstbuy;
					that.firstbuy_bid = res.firstbuy_bid;
				}
				//选择设置末消店铺
				if(res.setlastbuy){
					that.setlastbuy = res.setlastbuy;
					that.lastbuy_bid = res.lastbuy_bid;
				}
				that.show_sales = res.show_sales;
				that.loaded();
			});
			app.getLocation(function (res) {
				var latitude = res.latitude;
				var longitude = res.longitude;
				that.longitude = longitude;
				that.latitude = latitude;
				that.getDataList();
			},
			function () {
				that.getDataList();
			});
		},
    getDataList: function (loadmore) {
			if(!loadmore){
				this.pagenum = 1;
				this.datalist = [];
			}
      var that = this;
      var pagenum = that.pagenum;
      var latitude = that.latitude;
      var longitude = that.longitude;
      var keyword = that.keyword;
      var business_city_id = that.opt.business_city_id || false;
			that.loading = true;
			that.nodata = false;
			that.nomore = false;
      app.post('ApiBusiness/blist', {pagenum: pagenum,cid: that.cid,field: that.field,order: that.order,longitude: longitude,latitude: latitude,keyword: keyword,ids:that.ids,type:that.type,business_city_id:business_city_id}, function (res) {
        that.loading = false;
				uni.stopPullDownRefresh();
        var data = res.data;
        if(res.status == 0){
          app.error(res.msg);
          return;
        }
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
			console.log(e)
			this.$refs[e].open()
		},
		// 关闭窗口
		closeDrawer(e) {
			this.$refs[e].close()
		},
		// 抽屉状态发生变化触发
		change(e, type) {
			console.log((type === 'showLeft' ? '左窗口' : '右窗口') + (e ? '打开' : '关闭'));
			this[type] = e
		},
    cateClick: function (e) {
      var that = this;
      var cid = e.currentTarget.dataset.cid;
      that.catchecid = cid
    },
		filterConfirm(){
			this.cid = this.catchecid;
			this.gid = this.catchegid;
			this.getDataList();
			this.$refs['showRight'].close()
		},
		filterReset(){
			this.catchecid = this.oldcid;
			this.catchegid = '';
		},
    filterClick: function () {
      this.showfilter = !this.showfilter
    },
    changetab: function (e) {
      var that = this;
      var cid = e.currentTarget.dataset.cid;
      that.cid = cid
      that.pagenum = 1;
      that.datalist = [];
      that.getDataList();
    },
    search: function (e) {
      var that = this;
      var keyword = e.detail.value;
      that.keyword = keyword;
			that.pagenum = 1;
      that.datalist = [];
      that.getDataList();
    },
    sortClick: function (e) {
      var that = this;
      var t = e.currentTarget.dataset;
      that.field = t.field;
      that.order = t.order;
      that.getDataList();
    },
    filterClick: function (e) {
      var that = this;
      var types = e.currentTarget.dataset.types;
      that.types = types;
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
		phone:function(e) {
			var phone = e.currentTarget.dataset.phone;
			uni.makePhoneCall({
				phoneNumber: phone,
				fail: function () {
				}
			});
		},
		buydialogChange: function (e) {
			if(!this.buydialogShow){
				this.proid = e.currentTarget.dataset.proid
			}
			this.buydialogShow = !this.buydialogShow;
			console.log(this.buydialogShow);
		},
		//选择设置首消店铺
		setFirstBuyBusiness:function(e){
			var that = this;
			if(that.setfirstbuy == false) return;
			let bid = e.currentTarget.dataset.bid;
			let type = e.currentTarget.dataset.type;
			that.loading = true;
			app.post('ApiBusiness/setFirstBuyBusiness', {bid:bid,type:type}, function (res) {
			  that.loading = false;
				if(res.status == 1){
					app.success('设置成功');
					that.getdata();
				}else{
					app.error(res.msg);
				}
			});
		},
    chooseit: function (e) {
      var that = this;
      var fromPage = that.opt.fromPage;
      var id   = e.currentTarget.dataset.id;
      var name = e.currentTarget.dataset.name;
      setTimeout(function() {
          let pages = getCurrentPages();
          if (pages.length >= 2) {
              //let curPage = pages[pages.length - 1]; // 当前页面
              let prePage = pages[pages.length - 2]; // 上一页面
              prePage.$vm.expertbid  = id;
              prePage.$vm.expertbname= name;
              uni.navigateBack();
          }
      }, 600);
    },
  }
};
</script>
<style>
.search-container {position: fixed;width: 100%;background: #fff;z-index:9;top:var(--window-top)}
.topsearch{width:100%;padding:16rpx 20rpx;}
.topsearch .f1{height:60rpx;border-radius:30rpx;border:0;background-color:#f7f7f7;flex:1}
.topsearch .f1 .img{width:24rpx;height:24rpx;margin-left:10px}
.topsearch .f1 input{height:100%;flex:1;padding:0 20rpx;font-size:28rpx;color:#333;}
.topsearch .search-btn{display:flex;align-items:center;color:#5a5a5a;font-size:30rpx;width:60rpx;text-align:center;margin-left:20rpx}
.search-navbar {display: flex;text-align: center;align-items:center;padding:5rpx 0}
.search-navbar-item {flex: 1;height: 70rpx;line-height: 70rpx;position: relative;font-size:28rpx;font-weight:bold;color:#323232}
.search-navbar-item .iconshangla{position: absolute;top:-4rpx;padding: 0 6rpx;font-size: 20rpx;color:#7D7D7D}
.search-navbar-item .icondaoxu{position: absolute;top: 8rpx;padding: 0 6rpx;font-size: 20rpx;color:#7D7D7D}
.search-navbar-item .iconshaixuan{margin-left:10rpx;font-size:22rpx;color:#7d7d7d}

.filter-scroll-view{margin-top:var(--window-top)}
.search-filter{display: flex;flex-direction: column;text-align: left;width:100%;flex-wrap:wrap;padding:0;}
.filter-content-title{color:#999;font-size:28rpx;height:30rpx;line-height:30rpx;padding:0 30rpx;margin-top:30rpx;margin-bottom:10rpx}
.filter-title{color:#BBBBBB;font-size:32rpx;background:#F8F8F8;padding:40rpx 20rpx;}
.search-filter-content{display: flex;flex-wrap:wrap;padding:10rpx 20rpx;}
.search-filter-content .filter-item{background:#F4F4F4;border-radius:28rpx;color:#2B2B2B;font-weight:bold;margin:10rpx 10rpx;min-width:140rpx;height:56rpx;line-height:56rpx;text-align:center;font-size: 24rpx;padding:0 30rpx}
.search-filter-content .close{text-align: right;font-size:24rpx;color:#ff4544;width:100%;padding-right:20rpx}
.search-filter button .icon{margin-top:6rpx;height:54rpx;}
.search-filter-btn{display:flex;padding:50rpx 20rpx;justify-content: space-between;}
.search-filter-btn .btn-class{width: 48%;height:66rpx;line-height:66rpx;border-radius:33rpx;font-weight:bold;font-size:24rpx;text-align:center;}
.search-filter-btn .btn{background:#fff;border:1px solid #e5e5e5;color:#2B2B2B;}
.search-filter-btn .btn2{color:#fff;}

.ind_business {width: 100%;margin-top: 190rpx;font-size:26rpx;padding:0 24rpx}
.ind_business .ind_busbox{ width:100%;background: #fff;padding:20rpx;overflow: hidden; margin-bottom:20rpx;border-radius:8rpx;position:relative}
.ind_business .ind_buspic{ width:120rpx;height:120rpx; margin-right: 28rpx; }
.ind_business .ind_buspic image{ width: 100%;height:100%;border-radius: 8rpx;object-fit: cover;}
.ind_business .bus_title{ font-size: 30rpx; color: #222;font-weight:bold;line-height:46rpx;white-space: nowrap;max-width: 380rpx;overflow: hidden;text-overflow: ellipsis;}
.ind_business .bus_score{font-size: 24rpx;color:#FC5648;display:flex;align-items:center;justify-content: space-between;}
.ind_business .bus_score .img{width:24rpx;height:24rpx;margin-right:10rpx}
.ind_business .bus_score .txt{margin-left:20rpx}
.ind_business .indsale_box{ display: flex}
.ind_business .bus_sales{ font-size: 24rpx; color:#999;max-width: 210rpx;text-align: right;}
.ind_business .reward_member{ font-size: 24rpx; color:#999;position:absolute;top:148rpx;right:28rpx}
.ind_business .bus_address{color:#999;font-size: 22rpx;height:36rpx;line-height: 36rpx;margin-top:6rpx;display:flex;align-items:center;-webkit-box-orient:vertical;-webkit-line-clamp:1;overflow:hidden;}
.ind_business .bus_address .x1{text-overflow: ellipsis;white-space: nowrap;width: 60%;overflow: hidden;flex: 1;}
.ind_business .bus_address .x2{padding-left:20rpx}
.ind_business .prolist{white-space: nowrap;margin-top:16rpx; margin-bottom: 10rpx;}
.ind_business .prolist .product{width:108rpx;height:160rpx;overflow:hidden;display:inline-flex;flex-direction:column;align-items:center;margin-right:24rpx}
.ind_business .prolist .product .f1{width:108rpx;height:108rpx;border-radius:8rpx;background:#f6f6f6}
.ind_business .prolist .product .f2{font-size:22rpx;color:#FC5648;font-weight:bold;margin-top:4rpx}
.ind_business .prolist2{margin-top:16rpx; margin-bottom: 10rpx;}
.ind_business .prolist2 .product{width:118rpx;overflow:hidden;display:inline-flex;flex-direction:column;margin-right:10rpx;position:relative;min-height:200rpx;padding-bottom:20rpx}
.ind_business .prolist2 .product .f1{width:118rpx;height:118rpx;border-radius:8rpx;background:#f6f6f6}
.ind_business .prolist2 .product .f2{font-size:26rpx;color:#FC5648;font-weight:bold;margin-top:4rpx;}
.ind_business .prolist2 .product .f3{font-size:22rpx;font-weight:normal;color: #aaa;text-decoration: line-through;}
.ind_business .prolist2 .product .f4{font-size:20rpx;font-weight:normal;color: #888;}

.ind_business .prolist2 .product .f5{width:48rpx;height:48rpx;border-radius:50%;position:absolute;display:relative;top:140rpx;right:0rpx;text-align:center;}
.ind_business .prolist2 .product .f5 .icon_gouwuche{font-size:28rpx;height:48rpx;line-height:48rpx}
.ind_business .prolist2 .product .f5 .img{width:100%;height:100%}

.currentScroll::-webkit-scrollbar {display: none;width: 0 !important;height: 0 !important;-webkit-appearance: none;background: transparent;color: transparent;}
.currentScroll {-ms-overflow-style: none;}
.currentScroll {overflow: -moz-scrollbars-none;}

.ind_business .busbox2{background: #fff;padding: 8px;overflow: hidden;width: 100%;border-bottom: 2rpx solid #f5f5f5;}
.ind_business .busbox2:last-child{border: none;}
.ind_business .new_blist{display:flex;width:100%;align-items: center;}
.ind_business .new_blist .f1{width:130rpx;height:130rpx; margin-right: 30rpx;flex-shrink:0}
.ind_business .new_blist .f1 .image{ width: 100%;height:100%;border-radius:50%;object-fit: cover;}
.ind_business .new_blist .f2{flex:1}
.ind_business .new_blist .f2 .t1{font-size:28rpx; color: #222;font-weight:bold;line-height:60rpx}
.ind_business .new_blist .f2 .t2{font-size:28rpx; color: #222;line-height:40rpx;align-items: center;margin: 10rpx 0;display: flex}
.ind_business .new_blist .f2 .t2 .image{width: 32rpx;  height:32rpx ; line-height: 60rpx;margin-right: 20rpx;}
.ind_business .new_blist .f2 .t2 .text{width: 350rpx;overflow: hidden;text-overflow: ellipsis; white-space: nowrap}
.ind_business .new_blist .f3{display: flex;align-items: center;flex-direction: column; justify-content: center}
.ind_business .new_blist .f3 .image{width: 80rpx;height: 80rpx}
.ind_business .op{background: #fff;padding: 10rpx 0px;margin: -25rpx 0 20rpx 0;border-radius: 0 0 8rpx 8rpx;}
.ind_business .op .ops{display: flex;flex-wrap: wrap;justify-content: flex-end;align-items: center;width: 100%;border-top: 1px #f4f4f4 solid;}
.ind_business .op .btn{margin: 10rpx 20rpx;max-width: 200rpx;height: 60rpx;line-height: 60rpx;color: #333;background: #fff;border: 1px solid #cdcdcd;border-radius: 3px;text-align: center;padding: 0 20rpx;}
/*返利 和 积分显示*/
.ratio-list{padding-top: 10rpx;}
.ratio-label{height: 40rpx;border-radius: 10rpx;width:160rpx;border: 2rpx solid;margin-right:20rpx;}
.ratio-label .label{width: 55rpx ;height: 40rpx;line-height: 40rpx;border-radius: 10rpx 20rpx 5rpx 10rpx;color: #fff;text-align: center;}
.ratio-label .t1{text-align: center;width: 65%;font-size: 28rpx;}
/* 最高排队免单比例 */
.queue-free{justify-content: flex-end;margin-top: 10rpx;}
.queue-free-ratio{line-height: 45rpx;text-align: center;border-radius: 10rpx;border:4rpx solid #FC5D2B;color: #FC5D2B;font-size: 28rpx;padding-right: 10rpx;}
.queue-free-ratio .icon-div{height: 45rpx;width: 45rpx;display: flex;align-items: center;justify-content: center;margin-right: 10rpx}
.queue-free-ratio .icon-div .icon{width: 40rpx;height: 40rpx}

.chooseit{ background: #7A83EC; height: 60rpx; line-height: 60rpx; padding: 0 10rpx; color:#fff; border-radius:28rpx; width: 130rpx; text-align: center; margin-top: 20rpx;}
</style>