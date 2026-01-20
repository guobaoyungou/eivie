<template>
<view>
	<block v-if="isload">
		<view class="topsearch flex-y-center">
			<view class="f1 flex-y-center">
				<image class="img" :src="pre_url+'/static/img/search_ico.png'"></image>
				<input :value="keyword" placeholder="输入昵称/姓名/手机号搜索" placeholder-style="font-size:24rpx;color:#C2C2C2" @confirm="searchConfirm" @input="searchChange"></input>
			</view>
		</view>
		<view class="content" v-if="datalist && datalist.length>0">
			<view class="label">
				<text class="t1">{{t('会员')}}列表（共{{count}}人）</text>
			</view>
			<block v-for="(item, index) in datalist" :key="index">
				<view class="item">
					<view class="f1">
						<image :src="item.headimg"></image>
						<view class="t2">
							<view class="x1 flex-y-center">
								{{item.nickname}}(ID：{{item.id}})
								<image style="margin-left:10rpx;width:40rpx;height:40rpx" :src="pre_url+'/static/img/nan2.png'" v-if="item.sex==1"></image>
								<image style="margin-left:10rpx;width:40rpx;height:40rpx" :src="pre_url+'/static/img/nv2.png'" v-if="item.sex==2"></image>
							</view>
							<block v-if='item.add_mendian_time'>
								<text class="x2">加入时间：{{item.add_mendian_time}}</text>
							</block>
							<block v-if='item.tel'>
								<text class="x2">手机号：{{item.tel}}</text>
							</block>
						</view>
					</view>
				</view>
			</block>
		</view>
		<nomore v-if="nomore"></nomore>
		<nodata v-if="nodata"></nodata>
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
			mdid:0,
    };
  },
  onLoad: function (opt) {
		this.opt = app.getopts(opt);
    this.mdid = this.opt.mdid || 0;
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
      app.post('ApiMendian/mendianmemberlist', {keyword: keyword,pagenum: pagenum,mdid:that.mdid}, function (res) {
        that.loading = false;
				that.is_add_member = res.is_add_member ? res.is_add_member:0;
        var data = res.datalist;
        if (pagenum == 1) {
					that.datalist = data;
					that.count = res.count;
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
  }
};
</script>
<style>

.topsearch{width:94%;margin:16rpx 3%;}
.topsearch .f1{height:60rpx;border-radius:30rpx;border:0;background-color:#fff;flex:1}
.topsearch .f1 .img{width:24rpx;height:24rpx;margin-left:10px}
.topsearch .f1 input{height:100%;flex:1;padding:0 20rpx;font-size:28rpx;color:#333;}

.content{width: 94%;margin:0 3%;background: #fff;border-radius:16rpx}
.content .label{display:flex;width: 100%;padding:24rpx 16rpx;color: #333;}
.content .label .t1{flex:1}
.content .label .t2{ width:300rpx;text-align:right}
.content .label .btn{ border-radius:8rpx; padding:3rpx 12rpx;margin-left: 10px;border: 1px #999 solid; text-align:center; font-size:28rpx;color:#333;}

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
.popup__options{display: flex;align-items: center;justify-content: flex-start;padding-bottom: 15rpx;}
.popup__options .popup__options_text{width: 120rpx;text-align: right;}
.popup__but{font-size: 14px;color: #007aff;display: table;margin: 30rpx auto 30rpx;}

.uni-popup-dialog {width: 300px;border-radius: 5px;background-color: #fff;}
.uni-dialog-title {/* #ifndef APP-NVUE */display: flex;	/* #endif */flex-direction: row;justify-content: center;padding-top: 15px;padding-bottom: 5px;}
.uni-dialog-title-text {font-size: 16px;font-weight: 500;}
.uni-dialog-content {display: flex;flex-direction: column;justify-content: center;padding: 5px 15px 15px 15px;}
.uni-dialog-content-options{display: flex;align-items: center;justify-content: flex-start;padding: 10rpx 0rpx;}
.uni-dialog-content-text {font-size: 14px;color: #6e6e6e;text-align: right;width: 228rpx;white-space: nowrap;}
.uni-dialog-button-group {/* #ifndef APP-NVUE */display: flex;/* #endif */flex-direction: row;border-top-color: #f5f5f5;border-top-style: solid;border-top-width: 1px;}
.uni-dialog-button {/* #ifndef APP-NVUE */display: flex;/* #endif */flex: 1;flex-direction: row;justify-content: center;align-items: center;height: 45px;	/* #ifdef H5 */	cursor: pointer;/* #endif */}
.uni-border-left {border-left-color: #f0f0f0;border-left-style: solid;border-left-width: 1px;}
.uni-dialog-button-text {font-size: 14px;}
.uni-button-color {color: #007aff;}
.uni-dialog-input {	flex: 1;font-size: 14px;border: 1px #d1d1d1 solid;border-radius:5rpx;margin-right: 20rpx;padding-left: 10rpx;}
</style>