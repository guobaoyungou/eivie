<template>
<view class="container">
	<block v-if="isload">
		<view class="top">
			<view class="f1" @tap="goto" data-url="/pagesExt/my/scorelog">积分：{{score}}</view>
			<view class="f2" @tap="goto" data-url="/pagesExt/coupon/mycoupon">优惠券：{{couponcount}}</view>
		</view>	
		<view class="order-content">
			<block v-for="(item, index) in datalist" :key="index">
			<view class="order-box" @tap.stop="goto" :data-url="'orderdetail?id=' + item.id">
				<view class="head">
					<text class="flex1">订单号：{{item.ordernum}}</text>
				</view>
        <view class="head" style="color: #000;" @tap.stop="goto" :data-url="'list?gbid=' + item.gbid+'&bid='+item.bid">
        	<text class="flex1" >礼包：{{item.proname}}</text>
        </view>
				<view class="content" >
					<view style="width: 100%;display: flex;">
						<view @tap.stop="goto" :data-url="'orderdetail?id=' + item.proid+'&gbid=' + item.gbid+'&bid='+item.bid">
							<image :src="item.propic"></image>
						</view>
						<view class="detail">
							<view class="f1">
								<text class="t1">{{item.name}}</text>
								<view class="t3"><text class="x1 flex1">￥{{item.sell_price}}</text></view>
							</view>
							<view class="f2" >
								<text class="t1">赠送积分：{{item.givescore}}</text>
								<text class="t2">优惠券：{{item.couponcount}}张</text>
							</view>
						</view>
					</view>
				</view>

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
			menuindex:-1,

      st: 'all',
      datalist: [],
      pagenum: 1,
      nomore: false,
      nodata: false,
			keyword:'',
      hxnum:0,//要核销的数量
      hxogid:0,
      hexiao_qr:'',
			score:0,
			couponcount:0
    };
  },
  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.getdata();
  },
	onPullDownRefresh: function () {
		this.getdatalist();
	},
  onReachBottom: function () {
    if (!this.nodata && !this.nomore) {
      this.pagenum = this.pagenum + 1;
      this.getdatalist(true);
    }
  },
	onNavigationBarSearchInputConfirmed:function(e){
		this.searchConfirm({detail:{value:e.text}});
	},
  methods: {
    changetab: function (st) {
      this.st = st;
      uni.pageScrollTo({
        scrollTop: 0,
        duration: 0
      });
      this.getdatalist();
    },
		getdata:function(e){
			var that=this;
			app.get('ApiGiftPack/orderlist', {}, function (res) {
				that.loading = false;
				if(res.score){
						that.score = res.score
				}
				that.couponcount = res.couponcount
				that.loaded();
				that.getdatalist()
			})
		},
    getdatalist: function (loadmore) {
			if(!loadmore){
				this.pagenum = 1;
				this.datalist = [];
			}
      var that = this;
      var pagenum = that.pagenum;
      var st = that.st;
			that.nodata = false;
			that.nomore = false;
			that.loading = true;
      app.post('ApiGiftPack/orderlist', {st: st,pagenum: pagenum,keyword:that.keyword}, function (res) {
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
    gethxqr(){
      var that   = this;
    	var hxnum  = that.hxnum;
    	var hxogid = that.hxogid;
    	if(!hxogid){
    		app.alert('请选择要核销的商品');return;
    	}
    	if(!hxnum){
    		app.alert('请选择核销数量');return;
    	}
    	app.showLoading();
    	app.post('ApiGiftBag/getproducthxqr', {hxogid: hxogid,hxnum:hxnum}, function (data) {
    		app.showLoading(false);
    		if(data.status == 0){
    			app.alert(data.msg);
    		}else{
    			that.hexiao_qr = data.hexiao_qr
    			that.$refs.dialogHxqr.open();
    		}
    	});
    },
    showhxqr:function(e){
    	this.hexiao_qr = e.currentTarget.dataset.hexiao_qr
    	this.$refs.dialogHxqr.open();
    },
    closeHxqr:function(){
    	this.$refs.dialogHxqr.close();
    },
  }
};
</script>
<style>
.container{ width:100%;}
.topsearch{width:94%;margin:10rpx 3%;}
.topsearch .f1{height:60rpx;border-radius:30rpx;border:0;background-color:#fff;flex:1}
.topsearch .f1 .img{width:24rpx;height:24rpx;margin-left:10px}
.topsearch .f1 input{height:100%;flex:1;padding:0 20rpx;font-size:28rpx;color:#333;}
.order-content{display:flex;flex-direction:column;margin-top: 100rpx;}
.order-box{ width: 94%;margin:10rpx 3%;padding:6rpx 3%; background: #fff;border-radius:8px}
.order-box .head{ display:flex;width:100%; border-bottom: 1px #f4f4f4 solid; height: 70rpx; line-height: 70rpx; overflow: hidden; color: #999;}
.order-box .head .f1{display:flex;align-items:center;color:#333}
.order-box .head .f1 image{width:34rpx;height:34rpx;margin-right:4px}
.order-box .head .st0{ width: 140rpx; color: #ff8758; text-align: right; }
.order-box .head .st1{ width: 204rpx; color: #ffc702; text-align: right; }
.order-box .head .st2{ width: 204rpx; color: #ff4246; text-align: right; }
.order-box .head .st3{ width: 140rpx; color: #999; text-align: right; }
.order-box .head .st4{ width: 140rpx; color: #bbb; text-align: right; }

.order-box .content{display:block;width: 100%; padding:16rpx 0px;border-bottom: 1px #f4f4f4 dashed;position:relative}
.order-box .content:last-child{ border-bottom: 0; }
.order-box .content image{ width: 140rpx; height: 140rpx;}
.order-box .content .detail{display:flex;flex-direction:column;margin-left:14rpx;flex:1}
.order-box .content .detail .t1{font-size:26rpx;line-height:36rpx;margin-bottom:10rpx;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:2;overflow:hidden;}
.order-box .content .detail .t2{height: 46rpx;line-height: 46rpx;color: #999;overflow: hidden;font-size: 26rpx;}
.order-box .content .detail .t3{display:flex;height:40rpx;line-height:40rpx;color: #ff4246;}
.order-box .content .detail .x1{ flex:1}
.order-box .content .detail .x2{ width:100rpx;font-size:32rpx;text-align:right;margin-right:8rpx}
.detail .f2{ margin-top: 10rpx;}


.order-box .bottom{ width:100%; padding: 10rpx 0px; border-top: 1px #f4f4f4 solid; color: #555;}
.order-box .op{ display:flex;flex-wrap: wrap;align-items:center;width:100%; padding: 10rpx 0px; border-top: 1px #f4f4f4 solid; color: #555;}

.btn1{margin-left:20rpx;margin-top: 10rpx;width:160rpx;height:60rpx;line-height:60rpx;color:#fff;border-radius:3px;text-align:center}
.btn2{margin-left:20rpx;margin-top: 10rpx;width:160rpx;height:60rpx;line-height:60rpx;color:#333;background:#fff;border:1px solid #cdcdcd;border-radius:3px;text-align:center}


.hxqrbox{background:#fff;padding:50rpx;position:relative;border-radius:20rpx}
.hxqrbox .img{width:400rpx;height:400rpx}
.hxqrbox .txt{color:#666;margin-top:20rpx;font-size:26rpx;text-align:center}
.hxqrbox .close{width:50rpx;height:50rpx;position:absolute;bottom:-100rpx;left:50%;margin-left:-25rpx;border:1px solid rgba(255,255,255,0.5);border-radius:50%;padding:8rpx}
.tgr{font-size: 24rpx;}

.top{ display: flex; padding:30rpx;background: #fff;position: fixed; z-index: 1000;width: 100%; top:0 }
.top .f1{ text-align: left; width: 50%;}
.top .f2{ text-align: left; width: 50%;}
</style>