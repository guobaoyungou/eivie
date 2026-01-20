<template>
<view>
	<block v-if="isload">
		<!-- #ifndef H5 || APP-PLUS -->
		<view class="topsearch flex-y-center">
			<view class="f1 flex-y-center">
				<image class="img" :src="pre_url+'/static/img/search_ico.png'"></image>
				<input :value="keyword" placeholder="输入用户昵称或订单号搜索" placeholder-style="font-size:24rpx;color:#C2C2C2" @confirm="searchConfirm"></input>
			</view>
		</view>
		<!--  #endif -->
		<block v-for="(dateitem, index) in datalist" :key="index" v-if="datalist && datalist.length>0">
			<view class="content">
				<view class="label">
					<text class="t1">{{dateitem.date}}</text>
				</view>
				<view class="item" v-for="(item,index1) in dateitem.datelist" :key="index1" @tap="goto" :data-url="'maidannewdetail?id=' + item.id">
					<view class="left">
						<view class="f2">
							<view class="t2">支付会员：{{item.nickname}}</view>
							<text class="t2">支付时间：{{item.paytime}}</text>
							<text class="t2">支付方式：{{item.paytype}}</text>
							<text class="t2">订单编号：{{item.ordernum}}</text>
							<text class="t2" v-if="item.paynum">支付单号：{{item.paynum}}</text>
						</view>
					</view>
					<view class="right">
						<view>
							<view class="money" :style="{color:t('color1')}">￥{{item.money}}</view>
							<view v-if="canrefund && item.refund_money>0" class="refund-money">已退:￥{{item.refund_money}}</view>
						</view>
						<image class="more" :src="pre_url+'/static/img/arrowright.png'"></image>
					</view>
				</view>
			</view>
		</block>
		<nodata v-if="nodata"></nodata>
		<nomore v-if="nomore"></nomore>
	</block>
	<loading v-if="loading"></loading>
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
			
			keyword:'',
      st: 0,
			count:0,
      datalist: [],
      pagenum: 1,
      nodata: false,
      nomore: false,
			lastdate:'',
			canrefund:false,
			pre_url:app.globalData.pre_url,
    };
  },
  
  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.getdata();
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
	onNavigationBarSearchInputConfirmed:function(e){
		this.searchConfirm({detail:{value:e.text}});
	},
  methods: {
    getdata: function (loadmore) {
			if(!loadmore){
				this.pagenum = 1;
				this.datalist = [];
			}
      var that = this;
      var pagenum = that.pagenum;
      var st = that.st;
      var keyword = that.keyword;
			that.nodata = false;
			that.nomore = false;
			that.loading = true;
      app.post('ApiAdminMaidanNew/maidanlog', {keyword:keyword,pagenum: pagenum}, function (res) {
				that.loading = false;
        var data = res.data;
				that.canrefund = res.canrefund;
        if (pagenum == 1){
					that.count = res.count;
					that.datalist = data;
          if (data.length == 0) {
            that.nodata = true;
          }
					that.lastdate = res.lastdate;
					that.loaded();
        }else{
          if (data.length == 0) {
            that.nomore = true;
          } else {
            var datalist = that.datalist;
						//如果下一页的日期和上一页一致，则追加到上一页数组中
						var pagedate = data[0].date;
						if(pagedate==that.lastdate){
							var datelist = data[0].datelist
							var lastdata = that.datalist[that.datalist.length-1];
							var newdatelist = lastdata.datelist.concat(datelist);
							that.datalist[that.datalist.length-1].datelist = newdatelist;
							data.splice(0,1);
						}
            var newdata = datalist.concat(data);
            that.datalist = newdata;
          }
        }
      });
    },
    changetab: function (e) {
      var st = e.currentTarget.dataset.st;
      this.st = st;
      uni.pageScrollTo({
        scrollTop: 0,
        duration: 0
      });
      this.getdata();
    },
    searchChange: function (e) {
      this.keyword = e.detail.value;
    },
    searchConfirm: function (e) {
      var that = this;
      var keyword = e.detail.value;
      that.keyword = keyword;
      that.getdata();
    }
  }
};
</script>
<style>
/* @import "../common.css"; */
.topsearch{width:94%;margin:16rpx 3%;}
.topsearch .f1{height:60rpx;border-radius:30rpx;border:0;background-color:#fff;flex:1}
.topsearch .f1 .img{width:24rpx;height:24rpx;margin-left:10px}
.topsearch .f1 input{height:100%;flex:1;padding:0 20rpx;font-size:28rpx;color:#333;}

.content{width: 94%;margin:0 3%;background: #fff;border-radius:16rpx;margin-bottom: 20rpx;}
.content .label{display:flex;width: 100%;padding:24rpx 16rpx;color: #333;}
.content .label .t1{flex:1;font-weight: bold;}
.content .label .t2{ width:300rpx;text-align:right}

.content .item{width:100%;padding:20rpx 20rpx;border-top: 1px #f5f5f5 solid;display:flex;align-items:center;justify-content: space-between;}
.content .left{display: flex;align-items: center;flex: 1;}

.content .item .nickname{display:flex;}
.content .item .headimg{width:80rpx;height:80rpx;border-radius:50%;}
.content .right{flex-shrink: 0;display: flex;justify-content: flex-end;align-items: center;}
.content .item .right .money{font-size: 30rpx;font-weight: bold;}
.content .item .right .refund-money{font-size: 24rpx;color: #ff9d05;}
.content .item .right .more{width: 30rpx;height: 30rpx;}
.content .item .f1 .t2{color:#666666;text-align:center;width:140rpx;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:1;overflow:hidden;}
.content .item .f2{ flex:1;width:200rpx;font-size:30rpx;display:flex;flex-direction:column}
.content .item .f2 .t1{color:#03bc01;height:40rpx;line-height:40rpx;font-size:36rpx}
.content .item .f2 .t2{color:#999;height:40rpx;line-height:40rpx;font-size:24rpx}
.content .item .f2 .t3{color:#aaa;height:40rpx;line-height:40rpx;font-size:24rpx}
</style>