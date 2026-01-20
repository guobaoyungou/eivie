<template>
<view class="container">
	<block v-if="isload">
		<view class="ordertop flex" :style="'background:url('+pre_url+'/static/img/orderbg.png);background-size:100%'">
			<view class="f1 " v-if="detail.status==0">
				<view class="t1">等待买家付款</view>
				<view class="t2" v-if="djs">剩余时间：{{djs}}</view>				
			</view>
			
			<view class="f1" v-if="detail.status==2">
				<view class="t1">订单服务中</view>
			</view>
			<view class="f1" v-if="detail.status==3">
				<view class="t1">订单已完成</view>
			</view>
			<view class="f1" v-if="detail.status==4">
				<view class="t1">订单已取消</view>
			</view>
			<view class="orderx"><image :src="pre_url+'/static/img/orderx.png'"></view>
		</view>
		
		<view class="orderinfo orderinfotop">
			<view class="title">订单信息</view>
			<view class="item">
				<text class="t1">订单编号</text>
				<text class="t2" user-select="true" selectable="true">{{detail.ordernum}}</text>
			</view>
			<view class="item">
				<text class="t1">下单时间</text>
				<text class="t2">{{detail.createtime}}</text>
			</view>
		</view>
		<view class="orderinfo" v-if="(detail.formdata).length > 0">
			<view class="item" v-for="item in detail.formdata" :key="index">
				<text class="t1">{{item[0]}}</text>
				<view class="t2" v-if="item[2]=='upload'"><image :src="item[1]" style="width:400rpx;height:auto" mode="widthFix" @tap="previewImage" :data-url="item[1]"/></view>
				<text class="t2" v-else user-select="true" selectable="true">{{item[1]}}</text>
			</view>
		</view>
		
		<view class="product">
			<view class="title">信息</view>
			<view class="content">
				<view @tap="goto" :data-url="'product?id=' + prolist.proid">
					<image :src="prolist.propic"></image>
				</view>
				<view class="detail">
					<text class="t1">{{prolist.proname}}</text>
					<view class="t2 flex flex-y-center flex-bt">
						<text v-if='prolist.ggname'>{{prolist.ggname}}</text>
					</view>
					<view class="t3"><text class="x1 flex1">￥{{prolist.product_price}}</text><text class="x2">×{{prolist.num}}</text></view>
				</view>
			</view>
		</view>

		<view class="orderinfo">

			<view class="item">
				<text class="t1">应付金额</text>
				<text class="t2 red">¥{{detail.product_price}}</text>
			</view>
			<view class="item" v-if="detail.leveldk_money > 0">
				<text class="t1">{{t('会员')}}折扣</text>
				<text class="t2 red">-¥{{detail.leveldk_money}}</text>
			</view>
			<view class="item" v-if="detail.coupon_money > 0">
				<text class="t1">{{t('优惠券')}}抵扣</text>
				<text class="t2 red">-¥{{detail.coupon_money}}</text>
			</view>	
			<view class="item">
				<text class="t1">所需积分</text>
				<text class="t2 red">{{detail.totalscore}}{{t('积分')}}</text>
			</view>
			<view class="item">
				<text class="t1">实付款</text>
				<text class="t2 red">¥{{detail.totalprice}} + {{detail.totalscore}}{{t('积分')}}</text>
			</view>
			<view class="item">
				<text class="t1">订单状态</text>
				<text class="t2" v-if="detail.status==0">未付款</text>
				<text class="t2" v-if="detail.status==1">待使用</text>
				<text class="t2" v-if="detail.status==3">已完成</text>
				<text class="t2" v-if="detail.status==4">已关闭</text>
			</view>
			<view class="item" v-if="detail.status>0 && detail.paytypeid!='4' && detail.paytime">
				<text class="t1">支付时间</text>
				<text class="t2">{{detail.paytime}}</text>
			</view>
			<view class="item" v-if="detail.paytypeid">
				<text class="t1">支付方式</text>
				<text class="t2">{{detail.paytype}}</text>
			</view>
			
			<view class="item" v-if="detail.status==3 && detail.collect_time">
				<text class="t1">完成时间</text>
				<text class="t2">{{detail.collect_time}}</text>
			</view>
		</view>
		
		<view class="orderinfo">
			<view class="title">
				信息
			</view>
			<view class="item">
				<text class="t1">姓名</text>
				<text class="t2">{{detail.linkman}}</text>
			</view>
			<view class="item">
				<text class="t1">手机号</text>
				<text class="t2">{{detail.tel}}</text>
			</view>
  
		</view>


		<view style="width:100%;height:120rpx"></view>

		<view class="bottom">
			<block v-if="detail.status==0">
				<view class="btn2" @tap="toclose" :data-id="detail.id">关闭订单</view>
				
			</block>

			<block v-if="detail.status==3 || detail.status==4">
				<view class="btn2" @tap="todel" :data-id="detail.id">删除订单</view>
			</block>
			<block v-if="detail.status==1 && detail.protype==0">
				<view class="btn2" @tap="showhxqr">核销码</view>
			</block>
			<block v-if="detail.status==1 && detail.protype==1">
				<view class="btn2" @tap="orderCollect" :data-id="detail.id">确认完成</view>
			</block>
		</view>
		<uni-popup id="dialogHxqr" ref="dialogHxqr" type="dialog">
			<view class="hxqrbox">
				<image :src="detail.hexiao_qr" @tap="previewImage" :data-url="detail.hexiao_qr" class="img"/>
				<view class="txt">请出示核销码给核销员进行核销</view>
				<view class="close" @tap="closeHxqr">
					<image :src="pre_url+'/static/img/close2.png'" style="width:100%;height:100%"/>
				</view>
			</view>
		</uni-popup>
	</block>
	<loading v-if="loading"></loading>
	<dp-tabbar :opt="opt"></dp-tabbar>
	<popmsg ref="popmsg"></popmsg>
</view>
</template>

<script>
var app = getApp();
var interval = null;

export default {
  data() {
    return {
			opt:{},
			loading:false,
      isload: false,
			menuindex:-1,
			
			pre_url:app.globalData.pre_url,
      prodata: '',
      djs: '',
      iscommentdp: "",
      detail: "",
			payorder:{},
      prolist: "",
      storeinfo: "",
      lefttime: "",
      codtxt: "",
			pay_transfer_info:{},
			invoice:0,
			selectExpressShow:false,
			express_content:'',
    };
  },

  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.getdata();
  },
	onPullDownRefresh: function () {
		this.getdata();
	},
  onUnload: function () {
    clearInterval(interval);
  },
  methods: {
		getdata: function () {
			var that = this;
			that.loading = true;
			app.get('ApiHuodongBaoming/orderdetail', {id: that.opt.id}, function (res) {
				that.loading = false;
        if(res.status == 1){
          that.iscommentdp = res.iscommentdp,
          that.detail = res.detail;
          that.prolist = res.prolist;
          that.storeinfo = res.storeinfo;
          that.lefttime = res.lefttime;
          that.codtxt = res.codtxt;
          that.pay_transfer_info =  res.pay_transfer_info;
          that.payorder = res.payorder;
          that.invoice = res.invoice;
          that.loaded();
        } else {
          if (res.msg) {
            app.alert(res.msg, function() {
              if (res.url) app.goto(res.url);
            });
          } else if (res.url) {
            app.goto(res.url);
          } else {
            app.alert('您无查看权限');
          }
        }
			});
		},
    todel: function (e) {
      var that = this;
      var orderid = e.currentTarget.dataset.id;
      app.confirm('确定要删除该订单吗?', function () {
				app.showLoading('删除中');
        app.post('ApiHuodongBaoming/delOrder', {orderid: orderid}, function (data) {
					app.showLoading(false);
          app.success(data.msg);
          setTimeout(function () {
            app.goback(true);
          }, 1000);
        });
      });
    },
    toclose: function (e) {
      var that = this;
      var orderid = e.currentTarget.dataset.id;
      app.confirm('确定要关闭该订单吗?', function () {
				app.showLoading('提交中');
        app.post('ApiHuodongBaoming/closeOrder', {orderid: orderid}, function (data) {
					app.showLoading(false);
          app.success(data.msg);
          setTimeout(function () {
            that.getdata();
          }, 1000);
        });
      });
    },
    orderCollect: function (e) {
      var that = this;
      var orderid = e.currentTarget.dataset.id;
      app.confirm('确定已完成吗?', function () {
				app.showLoading('确认中');
        app.post('ApiHuodongBaoming/orderCollect', {orderid: orderid}, function (data) {
					app.showLoading(false);
          app.success(data.msg);
          setTimeout(function () {
            that.getdata();
          }, 1000);
        });
      });
    },
		showhxqr:function(){
			this.$refs.dialogHxqr.open();
		},
		closeHxqr:function(){
			this.$refs.dialogHxqr.close();
		}
  }
};
</script>
<style>
.text-min { font-size: 24rpx; color: #999;}
.ordertop{width:100%;height:452rpx;padding:50rpx 0 0 70rpx; justify-content: space-between;}
.ordertop .f1{color:#fff}
.ordertop .f1 .t1{font-size:40rpx;height:60rpx;line-height:60rpx;}
.ordertop .f1 .t2{font-size:26rpx; margin-top: 20rpx;}
.container .orderinfotop{ position: relative; margin-top: -200rpx;}
.product{width:96%;margin:0 2%;border-radius:8rpx;margin-top:16rpx;padding: 14rpx 3%;background: #FFF;}
.product .content{display:flex;position:relative;width: 100%; padding:16rpx 0px;border-bottom: 1px #e5e5e5 dashed;}
.product .content:last-child{ border-bottom: 0; }
.product .content image{ width: 140rpx; height: 140rpx;}
.product .content .detail{display:flex;flex-direction:column;margin-left:14rpx;flex:1}
.product .content .detail .t1{font-size:26rpx;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:2;overflow:hidden;}
.product .content .detail .t2{color: #999;font-size: 26rpx;margin-top: 10rpx;}
.product .content .detail .t3{display:flex;color: #ff4246;margin-top: 10rpx;}
.product .content .detail .t4{margin-top: 10rpx;}
.product .content .detail .x1{ flex:1}
.product .content .detail .x2{ width:100rpx;font-size:32rpx;text-align:right;margin-right:8rpx}
.product .content .comment{position:absolute;top:64rpx;right:10rpx;border: 1px #ffc702 solid; border-radius:10rpx;background:#fff; color: #ffc702;  padding: 0 10rpx; height: 46rpx; line-height: 46rpx;}
.product .content .comment2{position:absolute;top:64rpx;right:10rpx;border: 1px #ffc7c2 solid; border-radius:10rpx;background:#fff; color: #ffc7c2;  padding: 0 10rpx; height: 46rpx; line-height: 46rpx;}
.orderinfo{width:96%;margin:0 2%;border-radius:8rpx;margin-top:16rpx;padding: 14rpx 3%;background: #FFF;}
.orderinfo .title,.product .title{ font-weight: bold; font-size: 30rpx; line-height: 60rpx; margin-bottom: 15rpx;}
.orderinfo .item{display:flex;width:100%;padding:20rpx 0;border-bottom:1px dashed #ededed;overflow:hidden}
.orderinfo .item:last-child{ border-bottom: 0;}
.orderinfo .item .t1{width:200rpx;flex-shrink:0}
.orderinfo .item .t2{flex:1;text-align:right}
.orderinfo .item .t3{ margin-top: 3rpx;}
.orderinfo .item .red{color:red}
.bottom{ width: 100%; padding: 16rpx 20rpx;background: #fff; position: fixed; bottom: 0px;left: 0px;display:flex;justify-content:flex-end;align-items:center;}
.btn1{margin-left:20rpx;min-width:160rpx;padding: 0 20rpx;height:60rpx;line-height:60rpx;color:#fff;border-radius:3px;text-align:center;}
.btn2{margin-left:20rpx;width:160rpx;height:60rpx;line-height:60rpx;color:#333;background:#fff;border:1px solid #cdcdcd;border-radius:3px;text-align:center;}
.btn3{font-size:24rpx;width:120rpx;height:50rpx;line-height:50rpx;color:#333;background:#fff;border:1px solid #cdcdcd;border-radius:3px;text-align:center}
.hxqrbox{background:#fff;padding:50rpx;position:relative;border-radius:20rpx}
.hxqrbox .img{width:400rpx;height:400rpx}
.hxqrbox .txt{color:#666;margin-top:20rpx;font-size:26rpx;text-align:center}
.hxqrbox .close{width:50rpx;height:50rpx;position:absolute;bottom:-100rpx;left:50%;margin-left:-25rpx;border:1px solid rgba(255,255,255,0.5);border-radius:50%;padding:8rpx}
.orderx image{ width:124rpx ; height: 124rpx; margin-right: 60rpx;}
</style>