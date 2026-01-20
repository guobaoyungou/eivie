<template>
<view>
	<block v-if="isload">
		<view class="topsearch flex-y-center">
			<view class="f1 flex-y-center">
				<image class="img" :src="pre_url+'/static/img/search_ico.png'"></image>
				<input :value="keyword" placeholder="输入门店名称/手机号搜索" placeholder-style="font-size:24rpx;color:#C2C2C2" @confirm="searchConfirm" @input="searchChange"></input>
			</view>
		</view>
		<view class="content" v-if="datalist && datalist.length>0">
			<view class="label">
				<text class="t1">商家列表</text>
			</view>
			<block v-for="(item, index) in datalist" :key="index">
				<view class="item">
					<view class="f1" @tap="goto" :data-url="'detail?mid=' + item.id">
						<image :src="item.logo"></image>
						<view class="t2">

							<view class="x1 flex-y-center">
								{{item.name}}
								<image style="margin-left:10rpx;width:40rpx;height:40rpx" :src="pre_url+'/static/img/nan2.png'" v-if="item.sex==1"></image>
								<image style="margin-left:10rpx;width:40rpx;height:40rpx" :src="pre_url+'/static/img/nv2.png'" v-if="item.sex==2"></image>
							</view>
							<block>
								<text class="x2">总额度：{{item.sales_quota}}</text>
								<text class="x2">已销售额度：{{item.total_sales_quota}}</text>
                <text class="x2">剩余销售额度：{{item.syquota}}</text>
							</block>
					
						</view>
					</view>
					<view class="f2">
						<view class="btn" @tap="setQuota" :data-sales_quota="item.sales_quota" :data-bid="item.id">设置额度</view>
					</view>
				</view>
			</block>
		</view>
		<nomore v-if="nomore"></nomore>
		<nodata v-if="nodata"></nodata>
		
		<uni-popup id="rechargeDialog" ref="rechargeDialog" type="dialog">
			<uni-popup-dialog mode="input" title="设置额度" :value="sales_quota" placeholder="请输入额度" @confirm="setConfirm"></uni-popup-dialog>
		</uni-popup>
		
	</block>
	<popmsg ref="popmsg"></popmsg>
	<loading v-if="loading"></loading>
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
			pre_url:app.globalData.pre_url,

      datalist: [],
      pagenum: 1,
      nomore: false,
			nodata:false,
      count: 0,
      keyword: '',
      auth_data: {},
			dkopen:false,
			sales_quota:0,
			bid:0
    };
  },
  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.getdata();
		if(opt.type) this.dkopen = true;
  },
	onPullDownRefresh: function () {
		this.getdata();
	},
  onReachBottom: function () {
    if (!this.nodata && !this.nomore) {
      this.pagenum = this.pagenum + 1;
      this.getdata(true);
    }
  },
  methods: {

    getdata: function (loadmore) {
			if(!loadmore){
				this.pagenum = 1;
				this.datalist = [];
			}
      var that = this;
			var pagenum = that.pagenum;
      var keyword = that.keyword;
			that.nodata = false;
			that.nomore = false;
			that.loading = true;
      app.post('ApiAdminBusiness/index', {keyword: keyword,pagenum: pagenum}, function (res) {
        that.loading = false;
        var data = res.datalist;
        if (pagenum == 1) {
					that.datalist = data;
					that.count = res.count;
					that.auth_data = res.auth_data;
          if (data.length == 0) {
            that.nodata = true;
          }
					uni.setNavigationBarTitle({
						title: '商家列表'
					});
					that.loaded();
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
    searchChange: function (e) {
      this.keyword = e.detail.value;
    },
    searchConfirm: function (e) {
      var that = this;
      var keyword = e.detail.value;
      that.keyword = keyword;
      that.getdata();
    },
    setQuota: function (e) {
			var that=this
			that.sales_quota =  e.currentTarget.dataset.sales_quota;
			that.bid = e.currentTarget.dataset.bid;
			console.log(that.bid)
      this.$refs.rechargeDialog.open();
    },
		setConfirm: function (done,value) {
			this.$refs.rechargeDialog.close();
		  var that = this;
			 app.post('ApiAdminBusiness/setquota', {bid:that.bid,salequota:value}, function (data) {
				 app.success(data.msg);
				 setTimeout(function () {
					 that.getdata();
				 }, 1000);
			 });
		},
  }
};
</script>
<style>
@import "../common.css";
.topsearch{width:94%;margin:16rpx 3%;}
.topsearch .f1{height:60rpx;border-radius:30rpx;border:0;background-color:#fff;flex:1}
.topsearch .f1 .img{width:24rpx;height:24rpx;margin-left:10px}
.topsearch .f1 input{height:100%;flex:1;padding:0 20rpx;font-size:28rpx;color:#333;}

.content{width: 94%;margin:0 3%;background: #fff;border-radius:16rpx}
.content .label{display:flex;width: 100%;padding:24rpx 16rpx;color: #333;}
.content .label .t1{flex:1}
.content .label .t2{ width:300rpx;text-align:right}

.content .item{width: 100%;padding: 32rpx;border-top: 1px #e5e5e5 solid;min-height: 112rpx;display:flex;align-items:center;}
.content .item image{width:90rpx;height:90rpx;}
.content .item .f1{display:flex;flex:1}
.content .item .f1 .t2{display:flex;flex-direction:column;padding-left:20rpx}
.content .item .f1 .t2 .x1{color: #222;font-size:30rpx;}
.content .item .f1 .t2 .x2{color: #999;font-size:24rpx}

.content .item .f2{display:flex;flex-direction:column;width:auto;text-align:right;border-left:1px solid #e5e5e5}
.content .item .f2 .t1{ font-size: 40rpx;color: #666;height: 40rpx;line-height: 40rpx;}
.content .item .f2 .t2{ font-size: 28rpx;color: #999;height: 50rpx;line-height: 50rpx;}
.content .item .btn{ border-radius:8rpx; padding:3rpx 12rpx;margin-left: 10px;border: 1px #999 solid; text-align:center; font-size:28rpx;color:#333;}
.content .item .btn:nth-child(n+2) {margin-top: 10rpx;}

</style>