<template>
<view class="container">
	<block v-if="isload">
		<view class="search-container">
			<view class="topsearch flex-y-center">
				<view class="f1 flex-y-center">
					<image class="img" :src="pre_url+'/static/img/search_ico.png'"></image>
					<input :value="keyword" placeholder="搜索活动名称" placeholder-style="font-size:24rpx;color:#C2C2C2" confirm-type="search" @confirm="search"></input>
				</view>
			</view>

		</view>
		
		<view class="ind_business">
			<view class="ind_buslist" id="datalist" >
				<block v-for="(item, index) in datalist" :key="index">
				<view @tap="goto" :data-url="'product?id=' + item.id">
					<view class="ind_busbox flex1 flex-row">
						<view class="ind_buspic flex0"><image :src="item.pic" mode="aspectFill"></image></view>
						<view class="right  flex1">
							<view class="bus_title">{{item.name}}</view>
				              <view style="color: #887e7e;">活动开始时间:{{item.huodong_start_time}}</view>
                      <view style="color: #887e7e;">活动结束时间:{{item.huodong_end_time}}</view>
                      <!-- 报名开始时间 -->
                      <view style="color: #887e7e;">报名开始时间:{{ item.start_time }}</view>
                       

				              <view style="color: #887e7e;" v-if="item.countdown != '' && !item.isend && item.isstart">报名截止:{{ item.countdown }}</view>
                      <view style="color: #887e7e;" >已报名:{{ item.apply_num }}</view>
				         
							<view class="p2">
								<block v-if="item.score_price>0 && item.sell_price<=0">
									<view class="t1" :style="{color:t('color1')}">{{item.score_price}}{{t('积分')}}/人</view>
								</block>
								<block v-if="item.score_price<=0 && item.sell_price>0">
									<view class="t1" :style="{color:t('color1')}">￥{{item.sell_price}}</view>
								</block>	
								<block v-if="item.score_price>0 && item.sell_price>0">
									<view class="t1" :style="{color:t('color1')}">{{item.score_price}}{{t('积分')}}<text v-if="item.sell_price>0">+{{item.sell_price}}元</text></view>
								</block>	
								<view class="btn"  :style="{background:t('color1')}" v-if="!item.isend && item.isapply > 0 && item.isstart">已报名</view>
                <view class="btn"  style="background:#aaa;" v-else-if="item.isend">报名已结束</view>
                <view class="btn"  :style="{background:t('color1')}" v-else-if="!item.isend && item.isapply == 0 && item.isstart">报名进行中</view>
                  <view class="btn" style="background:#aaa;" v-else-if="!item.isstart">报名未开始</view>

							</view>
						</view>
					</view>
				</view>
				</block>
				<nomore v-if="nomore"></nomore>
				<nodata v-if="nodata"></nodata>
			</view>

		</view>
	
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
      datalist: [],
      pagenum: 1,
      keyword: '',
      cid: '',
      nomore: false,
      nodata: false,
    };
  },

  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.oldcid = this.opt.cid;
		this.catchecid = this.opt.cid;
		this.cid = this.opt.cid;
    if(this.opt.keyword) {
      this.keyword = this.opt.keyword;
    }
    this.ids = this.opt.ids || '';
		this.getDataList();
  },
	onPullDownRefresh: function () {
		this.getDataList();
	},
  onReachBottom: function () {
    if (!this.nodata && !this.nomore) {
      this.pagenum = this.pagenum + 1;
      this.getDataList(true);
    }
  },
  methods: {
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
			that.loading = true;
			that.nodata = false;
			that.nomore = false;
      app.post('ApiHuodongBaoming/getprolist', {pagenum: pagenum,cid: that.cid,order: that.order,keyword: keyword,ids:that.ids}, function (res) {
        that.loading = false;
				uni.stopPullDownRefresh();
        var data = res.data;
			
        if (pagenum == 1) {
          that.datalist = data;
          if (data.length == 0) {
            that.nodata = true;
          }
					that.loaded(true)
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
	
		phone:function(e) {
			var phone = e.currentTarget.dataset.phone;
			uni.makePhoneCall({
				phoneNumber: phone,
				fail: function () {
				}
			});
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
.filter-title{color:#BBBBBB;font-size:32rpx;background:#F8F8F8;padding:60rpx 0 30rpx 20rpx;}
.search-filter-content{display: flex;flex-wrap:wrap;padding:10rpx 20rpx;}
.search-filter-content .filter-item{background:#F4F4F4;border-radius:28rpx;color:#2B2B2B;font-weight:bold;margin:10rpx 10rpx;min-width:140rpx;height:56rpx;line-height:56rpx;text-align:center;font-size: 24rpx;padding:0 30rpx}
.search-filter-content .close{text-align: right;font-size:24rpx;color:#ff4544;width:100%;padding-right:20rpx}
.search-filter button .icon{margin-top:6rpx;height:54rpx;}
.search-filter-btn{display:flex;padding:30rpx 30rpx;justify-content: space-between}
.search-filter-btn .btn{width:240rpx;height:66rpx;line-height:66rpx;background:#fff;border:1px solid #e5e5e5;border-radius:33rpx;color:#2B2B2B;font-weight:bold;font-size:24rpx;text-align:center}
.search-filter-btn .btn2{width:240rpx;height:66rpx;line-height:66rpx;border-radius:33rpx;color:#fff;font-weight:bold;font-size:24rpx;text-align:center}

.ind_business {width: 100%;margin-top: 110rpx;font-size:26rpx;padding:0 24rpx}
.ind_business .ind_busbox{ width:100%;background: #fff;padding:20rpx;overflow: hidden; margin-bottom:20rpx;border-radius:8rpx;position:relative;
}
.ind_business .ind_buspic{ width:200rpx;height:160rpx; margin-right: 28rpx; }
.ind_business .ind_buspic image{ width: 100%;height:100%;border-radius: 8rpx;object-fit: cover;}
.ind_business .bus_title{ font-size: 30rpx; color: #222;font-weight:bold;line-height:46rpx}
.ind_business .bus_score{font-size: 24rpx;color:#FC5648;display:flex;align-items:center}
.ind_business .bus_score .img{width:24rpx;height:24rpx;margin-right:10rpx}
.ind_business .bus_score .txt{margin-left:20rpx}
.ind_business .indsale_box{ display: flex}
.ind_business .bus_sales{ font-size: 24rpx; color:#999;position:absolute;top:20rpx;right:28rpx}
.ind_business .reward_member{ font-size: 24rpx; color:#999;position:absolute;top:100rpx;right:28rpx}
.ind_business .bus_address{color:#999;font-size: 22rpx;height:36rpx;line-height: 36rpx;margin-top:6rpx;display:flex;align-items:center;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:1;overflow:hidden;}
.ind_business .bus_address .x2{padding-left:20rpx}
.ind_business .prolist{white-space: nowrap;margin-top:16rpx; margin-bottom: 10rpx;}
.ind_business .prolist .product{width:108rpx;height:160rpx;overflow:hidden;display:inline-flex;flex-direction:column;align-items:center;margin-right:24rpx}
.ind_business .prolist .product .f1{width:108rpx;height:108rpx;border-radius:8rpx;background:#f6f6f6}
.ind_business .prolist .product .f2{font-size:22rpx;color:#FC5648;font-weight:bold;margin-top:4rpx}

.currentScroll::-webkit-scrollbar {display: none;width: 0 !important;height: 0 !important;-webkit-appearance: none;background: transparent;color: transparent;}
.currentScroll {-ms-overflow-style: none;}
.currentScroll {overflow: -moz-scrollbars-none;}

.ind_busbox .right{ display:flex;  flex-direction: column; justify-content: space-between; }
.ind_buslist .p2{display:flex;align-items:center;overflow:hidden;padding:2px 0; justify-content: space-between;}
.ind_buslist .p2 .t1{ font-size: 36rpx;}
.ind_busbox .right .btn{ color: #fff; padding:10rpx 20rpx; border-radius: 10rpx; }
</style>