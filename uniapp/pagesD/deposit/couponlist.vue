<template>
<view class="container">
	<block v-if="isload">
		<view class="coupon-list">
			<view v-for="(item, index) in datalist" :key="item.id" class="coupon" @tap.stop="goto" :data-url="'coupondetail?id=' + item.id">
				<view class="pt_left">
					<view class="pt_left-content" >
						<image :src="item.pic" mode="widthFix"></image>
					</view>
				</view>
				<view class="pt_right">
					<view class="f1">
						<view class="t1">{{item.name}}</view>
						<!-- <view class="t3" v-if="item.type!=20" :style="item.bid>0?'margin-top:0':'margin-top:10rpx'">有效期至 {{item.yxqdate}}</view> -->
						<!-- <view class="t4" v-if="item.bid>0">适用商家：{{item.bname}}</view> -->
						<view class="t4" > x {{item.limit_count}}</view>
						<view class="t4 price" v-if="item.price>0"><text class="price_tag">￥</text>{{item.price}}</view>
					</view>		
					<button class="btn" :style="{background:'linear-gradient(270deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'}"  @tap.stop="getcoupon" :data-id="item.id" :data-price="item.price" :data-score="item.score" :data-key="index">{{item.price > 0 ? '购买' : (item.score>0?'兑换':'领取')}}</button>
				</view>
			</view>
		</view>
		<nodata v-if="nodata" :text="'暂无可领' + t('电子水票')"></nodata>
		<nomore v-if="nomore"></nomore>

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
			menuindex:-1,
			pre_url:app.globalData.pre_url,
			
			nomore:false,
			nodata:false,
      datalist: [],
      pagenum: 1,
	  tourl :'',
    };
  },

  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		if(this.opt && this.opt.tourl){
			this.tourl = decodeURIComponent(this.opt.tourl);
		}
		console.log(this.tourl);
		this.getdata();
  },
	onPullDownRefresh: function () {
    this.pagenum = 1;
    this.datalist = [];
		this.getdata();
	},
  onReachBottom: function () {
    if (!this.nodata && !this.nomore) {
      this.pagenum = this.pagenum + 1;
      this.getdata();
    }
  },
  methods: {
    getdata: function () {
      var that = this;
      var pagenum = that.pagenum;
      var st = that.st;
      var bid = that.opt && (that.opt.bid || that.opt.bid === '0') ? that.opt.bid : '';
			that.loading = true;
			that.nomore = false;
			that.nodata = false;
      app.post('ApiCoupon/getDepositCouponList', {st: st,pagenum: pagenum,bid: bid}, function (res) {
				that.loading = false;
        var data = res.data;
        if (pagenum == 1) {
			uni.setNavigationBarTitle({
				title: that.t('水票') 
			});
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
				that.loaded();
      });
    },
		getcoupon:function(e){
			var that = this;
			var datalist = that.datalist;
			var key = e.currentTarget.dataset.key;
			var couponinfo = datalist[key];
			that.getcouponconfirm(e);
		},
		getcouponconfirm: function (e) {
			var that = this;
			var datalist = that.datalist;
			var id = e.currentTarget.dataset.id;
			var score = parseInt(e.currentTarget.dataset.score);
			var price = e.currentTarget.dataset.price;
			var key = e.currentTarget.dataset.key;

			if (price > 0) {
				app.post('ApiCoupon/buycoupon', {id: id}, function (res) {
					if(res.status == 0) {
							app.error(res.msg);
					} else {
						app.goto('/pagesExt/pay/pay?id=' + res.payorderid + '&tourl='+encodeURIComponent(that.tourl));
					}
				})
				return;
			}
			var key = e.currentTarget.dataset.key;
			if (score > 0) {
				app.confirm('确定要消耗' + score + '' + that.t('积分') + '兑换吗?', function () {
					app.showLoading('兑换中');
					app.post('ApiCoupon/getcoupon', {id: id}, function (data) {
						app.showLoading(false);
						if (data.status == 0) {
							app.error(data.msg);
						} else {
							app.success(data.msg);
							setTimeout(function(){
								app.goto('mycoupon');
							},1000)
						}
					});
				});
			} else {
				app.showLoading('领取中');
				app.post('ApiCoupon/getcoupon', {id: id}, function (data) {
					app.showLoading(false);
					if (data.status == 0) {
						app.error(data.msg);
					} else {
						app.success(data.msg);
						setTimeout(function(){
							app.goto('mycoupon');
						},1000)
					}
				});
			}
		}
  }
};
</script>
<style>
.coupon-list{width:100%;padding:20rpx}
.coupon{width:100%;display:flex;margin-bottom:20rpx;border-radius:10rpx;overflow:hidden}
.coupon .pt_left{background: #fff;min-height:200rpx;color: #FFF;width:25%;display:flex;flex-direction:column;align-items:center;justify-content:center}
.coupon .pt_left-content{width:100%;height:100%;display:flex;flex-direction:column;align-items:center;justify-content:center;    padding: 20rpx 0 20rpx 20rpx;}

.coupon .pt_left-content image{width: 100%;border-radius: 10rpx;}

.coupon .pt_right{background: #fff;width:75%;display:flex;min-height:200rpx;text-align: left;padding:20rpx 20rpx;position:relative}
.coupon .pt_right .f1{flex-grow: 1;flex-shrink: 1;}
.coupon .pt_right .f1 .t1{font-size:28rpx;color:#2B2B2B;font-weight:bold;height:60rpx;line-height:60rpx;overflow:hidden}
.coupon .pt_right .f1 .t2{height:36rpx;line-height:36rpx;font-size:20rpx;font-weight:bold;padding:0 16rpx;border-radius:4rpx}
.coupon .pt_right .f1 .t3{font-size:20rpx;color:#999999;height:46rpx;line-height:46rpx;}
.coupon .pt_right .f1 .t4{font-size:20rpx;color:#999999;height:46rpx;line-height:46rpx;max-width: 76%;text-overflow: ellipsis;overflow: hidden;white-space: nowrap;}
.coupon .pt_right .f1 .price{color: #ff4246;font-size: 28rpx;}
.price_tag{font-style: 24rpx;}
.coupon .pt_right .btn{position:absolute;right:16rpx;top:49%;margin-top:-28rpx;border-radius:28rpx;width:150rpx;height:56rpx;line-height:56rpx;color:#fff}
.coupon .pt_right .sygq{position:absolute;right:30rpx;top:50%;margin-top:-50rpx;width:100rpx;height:100rpx;}

</style>