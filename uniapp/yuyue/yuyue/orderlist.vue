<template>
<view class="container">
	<block v-if="isload">
		<dd-tab :itemdata="['全部','待付款','派单中','待确认','已完成','已取消']" :itemst="['all','0','1','2','3','4']" :st="st" :isfixed="true" @changetab="changetab"></dd-tab>
		<view style="width:100%;height:100rpx"></view>
		<!-- #ifndef H5 || APP-PLUS -->
		<view class="topsearch flex-y-center">
			<view class="f1 flex-y-center">
				<image class="img" :src="pre_url+'/static/img/search_ico.png'"></image>
				<input :value="keyword" placeholder="输入关键字搜索" placeholder-style="font-size:24rpx;color:#C2C2C2" @confirm="searchConfirm"></input>
			</view>
		</view>
		<!--  #endif -->
		<view class="order-content">
			<block v-for="(item, index) in datalist" :key="index">
				<view class="order-box" @tap.stop="goto" :data-url="'orderdetail?id=' + item.id">
					<view class="head">
						<view class="f1" v-if="item.bid!=0 && item.binfo" @tap.stop="goto" :data-url="'/pagesExt/business/index?id=' + item.bid"><image :src="pre_url+'/static/img/ico-shop.png'"></image> {{item.binfo.name }}</view>
						<view v-else>订单号：{{item.ordernum}}</view>
						<view class="flex1"></view>
						<text v-if="item.status==0" class="st0">待付款</text>
						
						<block v-if="item.status==1 && item.refund_status==0 && item.worker_orderid">
							<text v-if="item.worker.status==0" class="st1">待接单</text>
							<text v-if="item.worker.status==1" class="st1">已接单</text>
							<text v-if="item.worker.status==2" class="st2">服务中</text>
						</block>
						<block v-else-if="item.status==1 && item.refund_status==0">
							<text class="st1">派单中</text>
						</block>	
						<text v-if="item.status==1 && item.refund_status==1" class="st1">退款审核中</text>
						<text v-if="item.status==2 && item.worker && item.worker.endtime" class="st2">待确认</text>
						<text v-else-if="item.status==2" class="st2">服务中</text>
						<text v-if="item.status==3 && item.isconmement==0" class="st3">待评价</text>
						<text v-if="item.status==3" class="st4">已完成</text>
						<text v-if="item.status==4" class="st4">订单已关闭</text>
					</view>
					<view class="content" style="border-bottom:none">
						<view v-if="item.paidan_type==3" >
							<image :src="item.propic" ></image>
						</view>
						<view v-else @tap.stop="goto" :data-url="'product?id=' + item.proid">
							<image :src="item.propic">
						</view>	
						<view class="detail">
							<text class="t1">{{item.proname}}</text>
		
							<text class="t1">预约日期：{{item.yy_time}}</text>
						
							<view class="t3" v-if="item.balance_price>0"><text class="x1 flex1">实付金额：￥{{item.totalprice}}</text><text class="x1 flex1" v-if="item.balance_price>0">尾款：￥{{item.balance_price}}</text></view>
							<view class="t3" v-else><text class="x1 flex1">实付金额：￥{{item.totalprice}}</text><text class="x1 flex1" v-if="isshowpandan">含跑腿费：￥{{item.paidan_money}}</text></view>
						</view>
					</view>
					<view class="bottom"  v-if="item.send_time>0">
						<text>派单时间：{{item.senddate}}</text>
						<text v-if="item.refund_status==1" style="color:red"> 退款中￥{{item.refund_money}}</text>
						<text v-if="item.refund_status==2" style="color:red"> 已退款￥{{item.refund_money}}</text>
						<text v-if="item.refund_status==3" style="color:red"> 退款申请已驳回</text>
					</view>
					<view class="op">
						<view @tap.stop="toorderend" :data-id="item.id" class="btn2" v-if="item.worker && item.worker.status==2 && item.worker.endtime && item.sysset && item.sysset.finish_confirm == 1 && item.sysset.confirm_type == 1 && item.isticheng == 0">服务完成</view>
						<view @tap.stop="goto" :data-url="'orderdetail?id=' + item.id" class="btn2">详情</view>
						<block v-if="item.status==0">
							<view class="btn2" @tap.stop="toclose" :data-id="item.id">关闭订单</view>
							<view class="btn1" :style="{background:t('color1')}" @tap.stop="goto" :data-url="'/pagesExt/pay/pay?id=' + item.payorderid">去付款</view>
						</block>
						<block v-if="item.status==1" >
							<view class="btn2"  v-if="item.cancel" @tap.stop="toclose" :data-id="item.id">取消订单</view>
							<view v-if="item.refund_status==0 || item.refund_status==3" class="btn2" @tap.stop="goto" :data-url="'refund?orderid=' + item.id + '&price=' + item.totalprice">申请退款</view>
						</block>
						
						<block v-if="item.status==2">
							<view v-if="item.refund_status==0 || item.refund_status==3" class="btn2" @tap.stop="goto" :data-url="'refund?orderid=' + item.id + '&price=' + item.totalprice">申请退款</view>
							<view class="btn2" @tap.stop="goto" :data-url="'logistics?express_no=' + item.worker_orderid" v-if="item.freight_type!=3">查看进度</view>
							
							<view v-if="item.balance_pay_status == 0 && item.balance_price > 0 && item.addmoney == 0"  class="btn1" :style="{background:t('color1')}" @tap.stop="goto" :data-url="'/pagesExt/pay/pay?id=' + item.balance_pay_orderid">支付尾款</view>
							
							<view v-else-if="item.balance_pay_status == 0 && item.balance_price > 0 && item.addmoney > 0 && item.addmoneyStatus == 1"  class="btn1" :style="{background:t('color1')}" @tap.stop="goto" :data-url="'/pagesExt/pay/pay?id=' + item.balance_pay_orderid">支付尾款</view>
							
							<view v-if="item.addmoneyStatus == 0 && item.addmoney > 0" class="btn1" :style="{background:t('color1')}" @tap.stop="goto" :data-url="'/pagesExt/pay/pay?id=' + item.addmoneyPayorderid">补差价</view>
						</block>
						<block v-if="hexiao_status && item.hexiao_code && (item.status==1 || item.status==2)">
							<view class="btn2" @tap.stop="showhxqr" :data-id="item.id" data-hexiao_code="item.hexiao_code" :data-hexiao_qr="item.hexiao_qr">核销码</view>
						</block>
						<block v-if="item.status==3 || item.status==4">
							<view class="btn2" @tap.stop="todel" :data-id="item.id">删除订单</view>
						</block>
					</view>
				</view>
			</block>
		</view>
		
		<nomore v-if="nomore"></nomore>
		<nodata v-if="nodata"></nodata>
		<uni-popup id="dialogHxqr" ref="dialogHxqr" type="dialog">
			<view class="hxqrbox">
				<image :src="hexiao_qr" @tap="previewImage" :data-url="hexiao_qr" class="img"/>
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
      codtxt: "",
			keyword:'',
			isshowpandan:false,
			pre_url:app.globalData.pre_url,
			hexiao_status:false,
			hexiao_qr:'',
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
			that.nodata = false;
			that.nomore = false;
			that.loading = true;
      app.post('ApiYuyue/orderlist', {st: st,pagenum: pagenum,keyword:that.keyword}, function (res) {
				that.loading = false;
        var data = res.datalist;
				var ishowpaidan = res.ishowpaidan;	
				that.ishowpaidan = ishowpaidan
				that.hexiao_status = res.hexiao_status || false;
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
    changetab: function (st) {
      this.st = st;
      uni.pageScrollTo({
        scrollTop: 0,
        duration: 0
      });
      this.getdata();
    },
    toclose: function (e) {
      var that = this;
      var orderid = e.currentTarget.dataset.id;
      app.confirm('确定要关闭该订单吗?', function () {
				app.showLoading('提交中');
        app.post('ApiYuyue/closeOrder', {orderid: orderid}, function (data) {
					app.showLoading(false);
          app.success(data.msg);
          if(data.status == 1){
            setTimeout(function () {
              that.getdata();
            }, 1000);
          }
        });
      });
    },
    todel: function (e) {
      var that = this;
      var orderid = e.currentTarget.dataset.id;
      app.confirm('确定要删除该订单吗?', function () {
				app.showLoading('删除中');
        app.post('ApiYuyue/delOrder', {orderid: orderid}, function (data) {
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
        app.post('ApiYuyue/orderCollect', {orderid: orderid}, function (data) {
          app.success(data.msg);
          setTimeout(function () {
            that.getdata();
          }, 1000);
        });
      });
    },
		toorderend: function (e) {
			var that = this;
			var orderid = e.currentTarget.dataset.id;
			app.confirm('确定服务完成了吗?', function () {
				app.post('ApiYuyue/orderEnd', {orderid: orderid}, function (data) {
					app.success(data.msg);
					setTimeout(function () {
						that.getdata();
					}, 1000);
				});
			});
		},
		searchConfirm:function(e){
			this.keyword = e.detail.value;
      this.getdata(false);
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
.order-content{display:flex;flex-direction:column}
.order-box{ width: 94%;margin:10rpx 3%;padding:6rpx 3%; background: #fff;border-radius:8px}
.order-box .head{ display:flex;width:100%; border-bottom: 1px #f4f4f4 solid; height: 70rpx; line-height: 70rpx; overflow: hidden; color: #999;}
.order-box .head .f1{display:flex;align-items:center;color:#333}
.order-box .head .f1 image{width:34rpx;height:34rpx;margin-right:4px}
.order-box .head .st0{ width: 140rpx; color: #ff8758; text-align: right; }
.order-box .head .st1{ width: 140rpx; color: #ffc702; text-align: right; }
.order-box .head .st2{ width: 140rpx; color: #ff4246; text-align: right; }
.order-box .head .st3{ width: 140rpx; color: #999; text-align: right; }
.order-box .head .st4{ width: 140rpx; color: #bbb; text-align: right; }

.order-box .content{display:flex;width: 100%; padding:16rpx 0px;border-bottom: 1px #f4f4f4 dashed;position:relative}
.order-box .content:last-child{ border-bottom: 0; }
.order-box .content image{ width: 140rpx; height: 140rpx;}
.order-box .content .detail{display:flex;flex-direction:column;margin-left:14rpx;flex:1}
.order-box .content .detail .t1{font-size:26rpx;line-height:36rpx;margin-bottom:10rpx;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:2;overflow:hidden;}
.order-box .content .detail .t2{height: 46rpx;line-height: 46rpx;color: #999;overflow: hidden;font-size: 26rpx;}
.order-box .content .detail .t3{display:flex;height:40rpx;line-height:40rpx;color: #ff4246;}
.order-box .content .detail .x1{ flex:1}
.order-box .content .detail .x2{ width:100rpx;font-size:32rpx;text-align:right;margin-right:8rpx}

.order-box .bottom{ width:100%; padding: 10rpx 0px; border-top: 1px #f4f4f4 solid; color: #555;}
.order-box .op{ display:flex;justify-content:flex-end;align-items:center;width:100%; padding: 10rpx 0px; border-top: 1px #f4f4f4 solid; color: #555;}

.btn1{margin-left:20rpx;width:160rpx;height:60rpx;line-height:60rpx;color:#fff;border-radius:3px;text-align:center; font-size: 24rpx;}
.btn2{margin-left:20rpx;width:160rpx;height:60rpx;line-height:60rpx;color:#333;background:#fff;border:1px solid #cdcdcd;border-radius:3px;text-align:center;font-size: 24rpx;}

.hxqrbox{background:#fff;padding:50rpx;position:relative;border-radius:20rpx}
.hxqrbox .img{width:400rpx;height:400rpx}
.hxqrbox .txt{color:#666;margin-top:20rpx;font-size:26rpx;text-align:center}
.hxqrbox .close{width:50rpx;height:50rpx;position:absolute;bottom:-100rpx;left:50%;margin-left:-25rpx;border:1px solid rgba(255,255,255,0.5);border-radius:50%;padding:8rpx}
.tgr{font-size: 24rpx;}

</style>