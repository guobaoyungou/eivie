<template>
<view>
	<block v-if="isload">
		<view class="topsearch flex-y-center">
			<view class="f1 flex-y-center">
				<image class="img" :src="pre_url+'/static/img/search_ico.png'"></image>
				<input :value="keyword" placeholder="输入商品名称搜索" placeholder-style="font-size:24rpx;color:#C2C2C2" @confirm="searchConfirm" @input="searchChange"></input>
			</view>
		</view>
		<view class="content" v-if="datalist && datalist.length>0">
			<block v-for="(item, index) in datalist" :key="index">
				<view class="item">
					<view class="f1">
						<image :src="item.pic"></image>
						<view class="t2" style="width: 540rpx">
							<view class="x1 flex-y-center">
								可用天数：{{item.day?item.day:0}}天
							</view>
              <text class="x2">滤芯名称：{{item.name}}</text>
              <view class="x1 flex-y-center" style="margin-top: 10rpx" v-if="item.txing">
                <button class="btn-mini2" @tap.stop="goto" :data-url="'/pages/shop/product?id=' + item.proid">立即更换</button>
                <button class="btn-mini2"style="margin-left: 60rpx" @tap="callMendian" :data-tel="item.tel">联系客服</button>
              </view>
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
      app.post('ApiMy/productlvxinreplace', {keyword: keyword,pagenum: pagenum}, function (res) {
        that.loading = false;
        if(res.status == 0){
          app.error(res.msg);
        }else{
          var data = res.datalist;
          if (pagenum == 1) {
            if (data.length == 0) {
              that.nodata = true;
            }
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
    callMendian:function(e){
      var tel = e.currentTarget.dataset.tel;
      uni.makePhoneCall({
        phoneNumber: tel,
        fail: function () {
        }
      });
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
.content .item image{width:130rpx;height:130rpx;}
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


.btn-mini2 {
  background-color: #ff4d4f;
  color: white;
  width: 140rpx;
  height: 50rpx;
  text-align: center;
  border: 1px solid #e6e6e6;
  border-radius: 20rpx;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 24rpx;
}
</style>