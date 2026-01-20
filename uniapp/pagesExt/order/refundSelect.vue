<template>
<view class="container">
	<block v-if="isload">
		<form @submit="formSubmit" @reset="formReset" report-submit="true">
			<view class="form-content">
				<view class="form-item">
					<text class="label">{{markname}}商品</text>
				</view>
				<view class="product">
					<view v-for="(item, index) in prolist" :key="index" class="content">
						<view @tap="goto" :data-url="'/pages/shop/product?id=' + item.proid">
							<image :src="item.pic"></image>
						</view>
						<view class="detail">
							<text class="t1">{{item.name}}</text>
							<text class="t2">{{item.ggname}}</text>
							<view class="t3"><text class="x1 flex1">￥{{item.sell_price}}
							<text v-if="!isNull(item.service_fee) && item.service_fee > 0">+{{item.service_fee}}{{t('服务费')}}</text>
							<text v-if="!isNull(item.freezemoney_price) && item.freezemoney_price > 0">+{{item.freezemoney_price}}{{t('冻结资金')}}</text>
							</text>
							<text class="x2">×{{item.num}}</text>
							</view>
						</view>
					</view>
				</view>
			</view>
			<view class="card-view">
				<view class="card-wrap">
					<view class="card-title">选择类型</view>
					<view v-if="opt.type != 'exchange'" class="info-item mt" @tap="goto" :data-url="'refund?orderid=' + detail.id + '&type=refund&ogid='+ogid">
						<view class="t1 flex1 flex-col">
							<view>我要退款(无需退货)</view>
							<view class="desc">没收到货，或与卖家协商一致不用退货只退款</view>
						</view>
						<image class="t3" :src="pre_url+'/static/img/arrowright.png'"/>
					</view>
					<view v-if="opt.type != 'exchange'" class="info-item" @tap="goto" :data-url="'refund?orderid=' + detail.id + '&type=return&ogid='+ogid">
						<view class="t1 flex1 flex-col">
							<view>我要退货退款</view>
							<view class="desc">已收到货，需要退还收到的货物</view>
						</view>
						<image class="t3" :src="pre_url+'/static/img/arrowright.png'"/>
					</view>
          <view v-if="detail.shop_order_exchange_product && opt.type == 'exchange'" class="info-item" @tap="goto" :data-url="'refund?orderid=' + detail.id + '&type=exchange&ogid='+ogid">
            <view class="t1 flex1 flex-col">
              <view>我要换货</view>
              <view class="desc">对收到的商品不满意，可与商家协商换货</view>
            </view>
            <image class="t3" :src="pre_url+'/static/img/arrowright.png'"/>
          </view>
				</view>
			</view>
			<view class="card-view">
				<view class="card-wrap">
					<view class="info-item mt" @tap="goto" :data-url="'refundlist?orderid='+ detail.id">
						<view class="t1 flex1">
							<view>查看本订单{{markname}}记录</view>
						</view>
						<image class="t3" :src="pre_url+'/static/img/arrowright.png'"/>
					</view>
				</view>
			</view>
		</form>
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

			pre_url: app.globalData.pre_url,
      orderid: '',
	  ogid:0,
      totalprice: 0,
			order:{},
			detail: {},
			refundNum:[],
			prolist: [],
			content_pic:[],
			cindex:-1,
			cateArr:['未收到货','已收到货'],
      markname:'退款',
    };
  },

  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.orderid = this.opt.orderid;
		this.ogid = typeof this.opt.ogid == "undefined"?0:this.opt.ogid;
		this.pre_url = app.globalData.pre_url;
    if(this.opt.type == 'exchange'){
      uni.setNavigationBarTitle({
        title: '申请换货'
      });
      this.markname = '换货';
    }
		this.getdata();
  },
	onPullDownRefresh: function () {
		this.getdata();
	},
  methods: {
		getdata: function () {
			var that = this;
			that.loading = true;
			app.post('ApiOrder/refundinit', {id: that.orderid, ogid:that.ogid}, function (res) {
				that.loading = false;
				if(res.status == 0) {
					app.alert(res.msg,function(){
						app.goback();return;
					})
				}
				that.tmplids = res.tmplids;
				that.detail = res.detail;
				var temp = [];
				that.prolist = res.prolist;
				that.order = res.order;
				for(var i in that.prolist) {
					temp.push({ogid:that.prolist[i].id,num:0})
				}
				console.log(temp)
				that.refundNum = temp;
				that.loaded();
			});
		},
		
  }
};
</script>
<style>
	.card-view {background-color: #FFFFFF;width:96%;margin:0 2%;border-radius:8rpx;margin-top:16rpx;padding: 14rpx 3%;}
	.card-wrap { }
	.card-wrap:first-child{ }
	.card-wrap .card-title {font-size: 30rpx; color: #333; font-weight: bold;}
		
	.info-item{ display:flex;align-items:center;width: 100%; background: #fff;padding:0 3%;  border-bottom: 1px #f3f3f3 solid; min-height: 96rpx;}
	.info-item:last-child{border:none}
	.info-item .t1{ width: 200rpx;color: #333;font-weight:bold;/* height:96rpx;line-height:96rpx */}
	.info-item .t1.flex-col {line-height: 40rpx;height: 40rpx; justify-content: center; margin: 50rpx 0; }
	.info-item .desc {color: #888; font-size: 24rpx; font-weight: normal;}
	.info-item .t2{ color:#444444;text-align:right;flex:1;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:1;overflow:hidden;}
	.info-item .t3{ width: 26rpx;height:26rpx;margin-left:20rpx}
	
.product{width:96%;margin:0 2%;border-radius:8rpx;margin-top:16rpx;padding: 14rpx 3%;background: #FFF;}
.product .content{display:flex;position:relative;width: 100%; padding:16rpx 0px;border-bottom: 1px #e5e5e5 dashed;}
.product .content:last-child{ border-bottom: 0; }
.product .content image{ width: 140rpx; height: 140rpx;}
.product .content .detail{display:flex;flex-direction:column;margin-left:14rpx;flex:1}
.product .content .detail .t1{font-size:26rpx;line-height:36rpx;margin-bottom:10rpx;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:2;overflow:hidden;}
.product .content .detail .t2{height: 46rpx;line-height: 46rpx;color: #999;overflow: hidden;font-size: 26rpx;}
.product .content .detail .t3{display:flex;height:40rpx;line-height:40rpx;color: #ff4246; position: relative;}
.product .content .detail .x1{ flex:1}
.product .content .detail .x2{ width:100rpx;font-size:32rpx;text-align:right;margin-right:8rpx}
.product .content .comment{position:absolute;top:64rpx;right:10rpx;border: 1px #ffc702 solid; border-radius:10rpx;background:#fff; color: #ffc702;  padding: 0 10rpx; height: 46rpx; line-height: 46rpx;}
.product .content .comment2{position:absolute;top:64rpx;right:10rpx;border: 1px #ffc7c2 solid; border-radius:10rpx;background:#fff; color: #ffc7c2;  padding: 0 10rpx; height: 46rpx; line-height: 46rpx;}

.form-content{width:96%;margin:16rpx 2%;border-radius:10rpx;display:flex;flex-direction:column;background:#fff;overflow:hidden}
.form-item{ width:100%;padding: 32rpx 20rpx;}
.form-item .label{ width:100%;height:60rpx;line-height:60rpx}
.form-item .input-item{ width:100%;}
.form-item textarea{ width:100%;height:200rpx;border: 1px #eee solid;padding: 20rpx;}
.form-item input{ width:100%;border: 1px #f5f5f5 solid;padding: 10rpx;height:80rpx}
.form-item .mid{ height:80rpx;line-height:80rpx;padding:0 20rpx;}
.btn{ height:100rpx;line-height: 100rpx;width:90%;margin:0 auto;border-radius:50rpx;margin-top:50rpx;color: #fff;font-size: 30rpx;font-weight:bold}
</style>