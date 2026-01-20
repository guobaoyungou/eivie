<template>
<view class="container">
	<block v-if="isload">
		<!-- #ifndef H5 || APP-PLUS -->
		<view class="topsearch flex-y-center">
			<view class="f1 flex-y-center">
				<image class="img" :src="pre_url+'/static/img/search_ico.png'"></image>
				<input :value="keyword" placeholder="输入课程标题搜索" placeholder-style="font-size:24rpx;color:#C2C2C2" @confirm="searchConfirm" @input="searchChange"></input>
			</view>
		</view>
		<!--  #endif -->
		<view class="order-content">
			<block v-for="(item, index) in datalist" :key="index">
				<view class="order-box" @tap="goto" :data-url="'/pagesB/kecheng/lecturermldetail?kcid='+item.kcid">
					<view class="content" style="border-bottom:none">
						<view>
							<image :src="item.pic"></image>
						</view>
						<view class="detail">
              <text class="t1">订单号：{{item.ordernum}}</text>
							<text class="t1" style="height:72rpx;">{{item.title}}</text>
              <text class="t1" style="color: red;">{{item.price==0 || item.price==null?'免费':'支付金额：￥' + item.price}}</text>
              <text class="t1" style="color: red;">分佣：{{item.lecturer_sendmoney}}</text>
						</view>
					</view>
          <view class="t2" style="height: 40rpx;line-height: 40rpx;color: #999;overflow: hidden;font-size: 24rpx;">{{item.createtime}}</view>
				</view>
			</block>
		</view>
		
		<nomore v-if="nomore"></nomore>
		<nodata v-if="nodata"></nodata>
	</block>
	<loading v-if="loading"></loading>
	<dp-tabbar :opt="opt"></dp-tabbar>
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
      datalist: [],
      pagenum: 1,
      nomore: false,
			nodata:false,
      codtxt: "",
			keyword:'',
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
			var keyword = that.keyword;
      var pagenum = that.pagenum;
      var st = that.st;
			that.nodata = false;
			that.nomore = false;
			that.loading = true;
      app.post('ApiKecheng/orderlist', {keyword: keyword,st: st,pagenum: pagenum,seetype:'lecturer'}, function (res) {
			that.loading = false;
        var data = res.datalist;
        if (pagenum == 1) {
			that.datalist = data;
          if (data.length == 0) {
            that.nodata = true;
          }
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
	}
  }
};
</script>
<style>
.container{ width:100%;}
.topsearch{width:94%;margin:16rpx 3%;}
.topsearch .f1{height:60rpx;border-radius:30rpx;border:0;background-color:#fff;flex:1}
.topsearch .f1 .img{width:24rpx;height:24rpx;margin-left:10px}
.topsearch .f1 input{height:100%;flex:1;padding:0 20rpx;font-size:28rpx;color:#333;}

.order-content{display:flex;flex-direction:column}
.order-box{ width: 94%;margin:10rpx 3%;padding:6rpx 3%; background: #fff;border-radius:8px}
.order-box .head{ display:flex;width:100%; border-bottom: 1px #f4f4f4 solid; height: 70rpx; line-height: 70rpx; overflow: hidden; color: #999;}
.order-box .head .f1{display:flex;align-items:center;color:#333}
.order-box .head .f1 image{width:34rpx;height:34rpx;margin-right:4px}

.order-box .content{display:flex;width: 100%; padding:16rpx 0px;border-bottom: 1px #f4f4f4 dashed;position:relative}
.order-box .content:last-child{ border-bottom: 0; }
.order-box .content image{ width:200rpx; height: 200rpx; border-radius: 10rpx;}
.order-box .content .detail{display:flex;flex-direction:column;margin-left:14rpx;flex:1}
.order-box .content .detail .t1{font-size:26rpx;line-height:36rpx;margin-bottom:10rpx;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:2;overflow:hidden;}
.order-box .content .detail .t2{height: 46rpx;line-height: 46rpx;color: #999;overflow: hidden;font-size: 22rpx;}
.order-box .content .detail .t3{display:flex;height:40rpx;line-height:40rpx;color: #ff4246; margin-top: 30rpx;}
.order-box .content .detail .t4{display:flex;justify-content: space-between; }
.order-box .content .detail .btn2{ margin-top: 40rpx;}

.order-box .content .detail .x1{ flex:1}
.order-box .content .detail .x2{ width:100rpx;font-size:32rpx;text-align:right;margin-right:8rpx}

.order-box .bottom{ width:100%; padding: 10rpx 0px; border-top: 1px #f4f4f4 solid; color: #555;}
.order-box .op{ display:flex;justify-content:flex-end;align-items:center;width:100%; padding: 10rpx 0px; border-top: 1px #f4f4f4 solid; color: #555;}

.btn1{margin-left:20rpx;width:160rpx;height:60rpx;line-height:60rpx;color:#fff;border-radius:3px;text-align:center}
.btn2{margin-left:20rpx;width:160rpx;height:60rpx;line-height:60rpx;color:#333;background:#fff;border:1px solid #cdcdcd;border-radius:3px;text-align:center}
.order-pin{ border: 1px #ffc702 solid; border-radius: 5px; color: #ffc702; float: right; padding: 0 5px; height: 23px; line-height: 23px; margin-left: 5px;}

.zan-tex{clear:both; display: block; width: 100%; color: #565656; font-size: 12px; height: 30px; line-height: 30px; text-align: center;  }
.ind-bot{ width: 100%; float: left; text-align: center; height: 50px; line-height: 50px; font-size: 13px; color: #ccc; background:#F2F2F2}
</style>