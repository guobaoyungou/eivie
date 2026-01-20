<template>
<view class="container">
	<block v-if="isload">
		<view class="ordertop" :style="'background:url(' + pre_url + '/static/img/ordertop_refund.jpg);background-size:100%'">
			<view class="f1" v-if="detail.refund_status==0">
				<view class="t1">已取消</view>
			</view>
			<view class="f1" v-if="detail.refund_status==1">
				<view class="t1">审核中</view>
			</view>
			<view class="f1" v-if="detail.refund_status==2">
				<view class="t1">已审核</view>
			</view>
			<view class="f1" v-if="detail.refund_status==3">
				<view class="t1">已通过</view>
			</view>
      <block v-if="detail.refund_status==4">
        <view class="t1">已驳回</view>
      </block>
		</view>
    
    <view v-if="detail.refundgoods && detail.refundgoods.length>0" class="product">
    	<view v-for="(item, idx) in detail.refundgoods" :key="idx" class="content">
        <view class="detail">
          <text class="t1">{{item.fname}}</text>
          <text class="t2">{{item.realName}} {{item.certNo}}</text>
        </view>
    	</view>
    </view>

		<view class="orderinfo">
			<view class="item">
				<text class="t1">售后单编号</text>
				<text class="t2" user-select="true" selectable="true">{{detail.refund_ordernum}}</text>
			</view>
			<view class="item">
				<text class="t1">订单编号</text>
				<text class="t2" user-select="true" selectable="true" @tap="goto" :data-url="'detail?id='+detail.orderid">{{detail.ordernum}}</text>
			</view>
			<view class="item">
				<text class="t1">申请时间</text>
				<text class="t2">{{detail.createtime}}</text>
			</view>
		</view>
		<view class="orderinfo">

			<view class="item">
				<text class="t1">退款状态</text>
				<text class="t2 grey" v-if="detail.refund_status==0">已取消</text>
				<text class="t2 red" v-if="detail.refund_status==1">审核中</text>
				<text class="t2 red" v-if="detail.refund_status==2">已审核</text>
				<text class="t2 red" v-if="detail.refund_status==3">已通过￥{{detail.refund_money}}</text>
        <text class="t2 red" v-if="detail.refund_status==4">已驳回</text>
			</view>
			<view class="item">
				<text class="t1">原因</text>
				<text class="t2">{{detail.refund_reason}}</text>
			</view>
			<view class="item" v-if="detail.refund_checkremark">
				<text class="t1">审核备注</text>
				<text class="t2">{{detail.refund_checkremark}}</text>
			</view>
			<view class="item" v-if="detail.refund_pics">
				<text class="t1">图片</text>
				<view id="content_picpreview" class="flex" style="flex-wrap:wrap;padding-top:20rpx">
					<view v-for="(item, index) in detail.refund_pics" :key="index" class="layui-imgbox">
						<view class="layui-imgbox-img"><image :src="item" @tap="previewImage" :data-url="item" mode="widthFix"></image></view>
					</view>
				</view>
			</view>
		</view>
    <view style="width:100%;height:calc(160rpx + env(safe-area-inset-bottom));"></view>
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
      detail: "",
    };
  },

  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.getdata();
  },
	onPullDownRefresh: function () {
		this.getdata();
	},
  methods: {

		getdata: function () {
			var that = this;
			that.loading = true;
			app.get('ApiZhiyoubao/refundDetail', {id: that.opt.id}, function (res) {
				that.loading = false;
				that.detail = res.detail;
				that.loaded();
			});
		},
    toclose: function (e) {
      var that = this;
      var id = e.currentTarget.dataset.id;
      app.confirm('确定要关闭该退款单吗?', function () {
				app.showLoading('提交中');
        app.post('ApiZhiyoubao/refundOrderClose', {id: id}, function (data) {
					app.showLoading(false);
          app.success(data.msg);
          setTimeout(function () {
            that.getdata();
          }, 1000);
        });
      });
    },
    copy:function(e){
      var content = e.currentTarget.dataset.content;
      if(content){
        uni.setClipboardData({
          data: content,
          success:function(){
            app.success('复制成功')
          }
        });
      }
    }
  }
};
</script>
<style>
.ordertop{width:100%;height:220rpx;padding:50rpx 0 0 70rpx}
.ordertop .f1{color:#fff}
.ordertop .f1 .t1{font-size:32rpx;height:60rpx;line-height:60rpx}
.ordertop .f1 .t2{font-size:24rpx}

.address{ display:flex;width: 100%; padding: 20rpx 3%; background: #FFF;}
.address .img{width:40rpx}
.address image{width:40rpx; height:40rpx;}
.address .info{flex:1;display:flex;flex-direction:column;}
.address .info .t1{font-size:28rpx;font-weight:bold;color:#333}
.address .info .t2{font-size:24rpx;color:#999}

.product{width:96%;margin:0 2%;border-radius:8rpx;margin-top:16rpx;padding: 14rpx 3%;background: #FFF;}
.product .content{position:relative;width: 100%; padding:16rpx 0px;border-bottom: 1px #e5e5e5 dashed;margin-bottom: 10rpx;}
.product .content:last-child{ border-bottom: 0; }
.product .content image{ width: 140rpx; height: 140rpx;}
.product .content .detail{display:flex;flex-direction:column;margin-left:14rpx;flex:1}
.product .content .detail .t1{font-size:26rpx;line-height:36rpx;margin-bottom:10rpx;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:2;overflow:hidden;}
.product .content .detail .t2{height: 46rpx;line-height: 46rpx;color: #999;overflow: hidden;font-size: 26rpx;}
.product .content .detail .t3{display:flex;height:40rpx;line-height:40rpx;color: #ff4246;}
.product .content .detail .x1{ flex:1}
.product .content .detail .x2{ width:100rpx;font-size:32rpx;text-align:right;margin-right:8rpx}
.product .content .comment{position:absolute;top:64rpx;right:10rpx;border: 1px #ffc702 solid; border-radius:10rpx;background:#fff; color: #ffc702;  padding: 0 10rpx; height: 46rpx; line-height: 46rpx;}
.product .content .comment2{position:absolute;top:64rpx;right:10rpx;border: 1px #ffc7c2 solid; border-radius:10rpx;background:#fff; color: #ffc7c2;  padding: 0 10rpx; height: 46rpx; line-height: 46rpx;}

.orderinfo{width:96%;margin:0 2%;border-radius:8rpx;margin-top:16rpx;padding: 14rpx 3%;background: #FFF;}
.orderinfo .item{display:flex;width:100%;padding:20rpx 0;border-bottom:1px dashed #ededed;overflow:hidden}
.orderinfo .item:last-child{ border-bottom: 0;}
.orderinfo .item .t1{width:200rpx;flex-shrink:0}
.orderinfo .item .t2{flex:1;text-align:right}
.orderinfo .item .red{color:red}
.orderinfo .item .grey{color:grey}

.bottom{ width: 100%; padding: 16rpx 20rpx;background: #fff; position: fixed; bottom: 0px;left: 0px;display:flex;justify-content:flex-end;align-items:center;}

.btn1{margin-left:20rpx;width:160rpx;height:60rpx;line-height:60rpx;color:#fff;border-radius:3px;text-align:center}
.btn2{margin-left:20rpx;min-width:160rpx;height:60rpx;line-height:60rpx;color:#333;background:#fff;border:1px solid #cdcdcd;border-radius:3px;text-align:center}
.btn3{position:absolute;top:60rpx;right:10rpx;font-size:24rpx;width:120rpx;height:50rpx;line-height:50rpx;color:#333;background:#fff;border:1px solid #cdcdcd;border-radius:3px;text-align:center}
.btn4{border: none;}
.btitle{ width:100%;height:100rpx;background:#fff;padding:0 20rpx;border-bottom:1px solid #f5f5f5}
.btitle .comment{border: 1px #ffc702 solid;border-radius:10rpx;background:#fff; color: #ffc702;  padding: 0 10rpx; height: 46rpx; line-height: 46rpx;}
.btitle .comment2{border: 1px #ffc7c0 solid;border-radius:10rpx;background:#fff; color: #ffc7c0;  padding: 0 10rpx; height: 46rpx; line-height: 46rpx;}

.layui-imgbox{margin-right:16rpx;margin-bottom:10rpx;font-size:24rpx;position: relative;}
.layui-imgbox-img{display: block;width:200rpx;height:200rpx;padding:2px;border: #d3d3d3 1px solid;background-color: #f6f6f6;overflow:hidden}
.layui-imgbox-img>image{max-width:100%;}

.uni-popup-dialog {width: 300px;border-radius: 5px;background-color: #fff;}
.uni-dialog-title {display: flex;flex-direction: row;justify-content: center;padding-top: 15px;padding-bottom: 5px;}
.uni-dialog-title-text {font-size: 16px;font-weight: 500;}
.uni-dialog-content {display: flex;flex-direction: row;justify-content: center;align-items: center;padding: 5px 15px 15px 15px;}
.uni-dialog-content-text {font-size: 14px;color: #6e6e6e;}
.uni-dialog-button-group {display: flex;flex-direction: row;border-top-color: #f5f5f5;border-top-style: solid;border-top-width: 1px;}
.uni-dialog-button {display: flex;flex: 1;flex-direction: row;justify-content: center;align-items: center;height: 45px;}
.uni-border-left {border-left-color: #f0f0f0;border-left-style: solid;border-left-width: 1px;}
.uni-dialog-button-text {font-size: 14px;}
.uni-button-color {color: #007aff;}
.danhao-input-view{border: 1px #eee solid;display: flex;align-items: center;flex: 1;}
.danhao-input-view image{width: 60rpx;height: 60rpx;}

.layui-imgbox{margin-right:16rpx;margin-bottom:10rpx;font-size:24rpx;position: relative;}
.layui-imgbox-img{display: block;width:200rpx;height:200rpx;padding:2px;border: #d3d3d3 1px solid;background-color: #f6f6f6;overflow:hidden}
.layui-imgbox-img>image{max-width:100%;}
.layui-imgbox-repeat{position: absolute;display: block;width:32rpx;height:32rpx;line-height:28rpx;right: 2px;bottom:2px;color:#999;font-size:30rpx;background:#fff}
.uploadbtn{position:relative;height:200rpx;width:200rpx}
.btn-class{height:45rpx;line-height:45rpx;color:#333;background:#fff;border:1px solid #cdcdcd;border-radius:3px;text-align:center;padding: 0 15rpx;flex-shrink: 0;margin: 0 0 0 10rpx;font-size:24rpx;}
</style>
