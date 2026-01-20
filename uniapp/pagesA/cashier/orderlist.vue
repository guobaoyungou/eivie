<template>
<view class="container">
	<block v-if="isload">
		<dd-tab :itemdata="['全部','已完成','已退款']" :itemst="['all','1','10']" :st="st" :isfixed="true" @changetab="changetab"></dd-tab>
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
				<view class="order-box" @tap="goto" :data-url="'detail?id=' + item.id">
					<view class="head">
						<view class="f1" v-if="item.bid!=0 && item.binfo" @tap.stop="goto" :data-url="'/pagesExt/business/index?id=' + item.bid"><image :src="pre_url+'/static/img/ico-shop.png'"></image> {{item.binfo.name}}</view>
						<view class="f1" v-else><image :src="item.binfo.logo" class="logo-row"></image> {{item.binfo.name}}</view>
						<view class="flex1">
								<text style="color:orangered; margin-left: 10rpx;" v-if="item.yuding_type && item.yuding_type =='1'">[预定订单]</text>
						</view>
						<!-- <text v-if="item.status==0" class="st0">结算中</text> -->
						<text v-if="item.status==1" class="st3">已完成</text>
						<!-- <text v-if="item.status==2" class="st2">挂单中</text> -->
						<text v-if="item.status==10" class="st4">已退款</text>
					</view>

					<block v-for="(item2, idx) in item.prolist" :key="idx">
						<view class="content" :style="idx+1==item.procount?'border-bottom:none':''">
							<view @tap.stop="goto" :data-url="'/pages/shop/product?id=' + item2.proid">
								<image :src="item2.propic"></image>
							</view>
							<view class="detail">
								<text class="t1">{{item2.proname}}</text>
								<text class="t2">{{item2.ggname}}</text>
								<block>
									<view class="t3" v-if="item2.product_type && item2.product_type==2">
										<text class="x1 flex1">{{item2.real_sell_price}}元/斤</text>
										<text class="x2">×{{item2.real_total_weight}}斤</text>
									</view>
									<view class="t3" v-else>
										<text class="x1 flex1"><text v-if="showprice_dollar && item2.usd_sellprice">${{item2.usd_sellprice}} </text>￥{{item2.sell_price}}</text>
										<text class="x2">×{{item2.num}}</text>
									</view>
								</block>
								<block v-if="(item.status==1 || item.status==2) && item.freight_type==1 && item2.hexiao_code">
									<view class="btn2" @tap.stop="showhxqr2" :data-id="item2.id" :data-num="item2.num" :data-hxnum="item2.hexiao_num" :data-hexiao_code="item2.hexiao_code" style="position:absolute;top:40rpx;right:0rpx;">核销码</view>
								</block>
							</view>
						</view>
					</block>
					<view class="bottom">
						<view>共计{{item.procount}}件商品 实付:￥{{item.totalprice}}  <text v-if="item.balance_price > 0 && item.balance_pay_status == 0"  style="display: block; float: right;">尾款：￥{{item.balance_price}}</text></view>
						<!-- <text v-if="item.refund_status==1" style="color:red;padding-left:6rpx">退款中￥{{item.refund_money}}</text> -->
						<text v-if="item.refund_status==1" style="color:red;padding-left:6rpx">已退款￥{{item.refund_money}}</text>
						<!-- <text v-if="item.refund_status==3" style="color:red;padding-left:6rpx">退款申请已驳回</text> -->
						
					</view>

					<view class="op">
						<block v-if="([1,2,3]).includes(item.status) && item.invoice">
							<view class="btn2" @tap.stop="goto" :data-url="'invoice?type=shop&orderid=' + item.id">发票</view>
						</block>
						<view @tap.stop="goto" :data-url="'/pagesA/cashier/orderdetail?id=' + item.id" class="btn2">详情</view>

						<block v-if="(item.status==1 || item.status==2) && item.freight_type==1 && item.hexiao_qr">
							<view class="btn2" @tap.stop="showhxqr" :data-hexiao_qr="item.hexiao_qr">核销码</view>
						</block>
						<view v-if="item.refundCount" class="btn2" @tap.stop="goto" :data-url="'refundlist?orderid='+ item.id">查看退款</view>
						<block v-if="item.status==3 || item.status==4">
							<view class="btn2" @tap.stop="todel" :data-id="item.id">删除订单</view>
						</block>
						<block v-if="item.bid>0 && item.status==3">
							<view v-if="item.iscommentdp==0" class="btn1" :style="{background:t('color1')}" @tap.stop="goto" :data-url="'/pagesExt/order/commentdp?orderid=' + item.id">评价店铺</view>
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

		<uni-popup id="dialogSelectExpress" ref="dialogSelectExpress" type="dialog">
			<view style="background:#fff;padding:20rpx 30rpx;border-radius:10rpx;width:600rpx" v-if="express_content">
				<view class="sendexpress" v-for="(item, index) in express_content" :key="index" style="border-bottom: 1px solid #f5f5f5;padding:20rpx 0;">
					<view class="sendexpress-item" @tap="goto" :data-url="'/pagesExt/order/logistics?express_com=' + item.express_com + '&express_no=' + item.express_no" style="display: flex;">
						<view class="flex1" style="color:#121212">{{item.express_com}} - {{item.express_no}}</view>
						<image :src="pre_url+'/static/img/arrowright.png'" style="width:30rpx;height:30rpx"/>
					</view>
					<view v-if="item.express_oglist" style="margin-top:20rpx">
						<view class="oginfo-item" v-for="(item2, index2) in item.express_oglist" :key="index2" style="display: flex;align-items:center;margin-bottom:10rpx">
							<image :src="item2.pic" style="width:50rpx;height:50rpx;margin-right:10rpx;flex-shrink:0"/>
							<view class="flex1" style="color:#555">{{item2.name}}({{item2.ggname}})</view>
						</view>
					</view>
				</view>
			</view>
		</uni-popup>
		<view v-if="selecthxnumDialogShow" class="popup__container">
			<view class="popup__overlay" @tap.stop="hideSelecthxnumDialog"></view>
			<view class="popup__modal">
				<view class="popup__title">
					<text class="popup__title-text">请选择核销数量</text>
					<image :src="pre_url+'/static/img/close.png'" class="popup__close" style="width:36rpx;height:36rpx" @tap.stop="hideSelecthxnumDialog"/>
				</view>
				<view class="popup__content">
					<view class="pstime-item" v-for="(item, index) in hxnumlist" :key="index" @tap="hxnumRadioChange" :data-index="index">
						<view class="flex1">{{item}}</view>
						<view class="radio" :style="hxnum==item ? 'background:'+t('color1')+';border:0' : ''"><image class="radio-img" :src="pre_url+'/static/img/checkd.png'"/></view>
					</view>
				</view>
			</view>
		</view>

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
			nodata:false,
      codtxt: "",
			canrefund:1,
			express_content:'',
			selectExpressShow:false,
			hexiao_qr:'',
			keyword:'',
			showprice_dollar:false,
			hexiao_qr:'',
			selecthxnumDialogShow:false,
			hxogid:'',
			hxnum:'',
			hxnumlist:[],
			pre_url: app.globalData.pre_url,
    };
  },

  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		if(this.opt && this.opt.st){
			this.st = this.opt.st;
		}
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
      app.post('ApiOrder/getCashierOrder', {st: st,pagenum: pagenum,keyword:that.keyword}, function (res) {
				that.loading = false;
        var data = res.datalist;
        if (pagenum == 1) {
					that.codtxt = res.codtxt;
					that.canrefund = res.canrefund;
					that.showprice_dollar = res.showprice_dollar
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
			if(st == 5){
				app.goto('refundlist');return;
			}
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
        app.post('ApiOrder/closeOrder', {orderid: orderid}, function (data) {
					app.showLoading(false);
          app.success(data.msg);
          setTimeout(function () {
            that.getdata();
          }, 1000);
        });
      });
    },
    todel: function (e) {
      var that = this;
      var orderid = e.currentTarget.dataset.id;
      app.confirm('确定要删除该订单吗?', function () {
				app.showLoading('删除中');
        app.post('ApiOrder/delOrder', {orderid: orderid}, function (data) {
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
			var index = e.currentTarget.dataset.index;
			var orderinfo = that.datalist[index];
      app.confirm('确定要收货吗?', function () {
				app.showLoading('提交中');
				if(app.globalData.platform == 'wx' && orderinfo.wxpaylog && orderinfo.wxpaylog.is_upload_shipping_info == 1){
					app.post('ApiOrder/orderCollectBefore', {orderid: orderid}, function (data) {
						app.showLoading(false);
						if(data.status != 1){app.error(data.msg);return;}
						else{
							if (wx.openBusinessView) {
							  wx.openBusinessView({
							    businessType: 'weappOrderConfirm',
							    extraData: {
							      merchant_id: orderinfo.wxpaylog.mch_id,
							      merchant_trade_no: orderinfo.wxpaylog.ordernum,
							      transaction_id: orderinfo.wxpaylog.transaction_id
							    },
							    success(res) {
							      //dosomething
										console.log('openBusinessView success')
										console.log(res)
										app.post('ApiOrder/orderCollect', {orderid: orderid}, function (data2) {
											app.showLoading(false);
											app.success(data2.msg);
											setTimeout(function () {
												that.getdata();
											}, 1000);
										});
							    },
							    fail(err) {
							      //dosomething
										console.log('openBusinessView fail')
										console.log(err)
							    },
							    complete() {
							      //dosomething
							    }
							  });
							} else {
							  //引导用户升级微信版本
								app.error('请升级微信版本');
								console.log('openBusinessView error')
							}
						}
					});
				}else{
					app.post('ApiOrder/orderCollect', {orderid: orderid}, function (data) {
						app.showLoading(false);
						app.success(data.msg);
						setTimeout(function () {
							that.getdata();
						}, 1000);
					});
				}
      });
    },
		logistics:function(e){
			var index = e.currentTarget.dataset.index;
			var orderinfo = this.datalist[index];
			var express_com = orderinfo.express_com
			var express_no = orderinfo.express_no
			var express_content = orderinfo.express_content
			var express_type = orderinfo.express_type
			var prolist = orderinfo.prolist
			console.log(express_content)
			if(!express_content){
				app.goto('/pagesExt/order/logistics?express_com=' + express_com + '&express_no=' + express_no+'&type='+express_type);
			}else{
				express_content = JSON.parse(express_content);
				for(var i in express_content){
					if(express_content[i].express_ogids){
						var express_ogids = (express_content[i].express_ogids).split(',');
						console.log(express_ogids);
						var express_oglist = [];
						for(var j in prolist){
							if(app.inArray(prolist[j].id+'',express_ogids)){
								express_oglist.push(prolist[j]);
							}
						}
						express_content[i].express_oglist = express_oglist;
					}
				}
				this.express_content = express_content;
				console.log(express_content);
				this.$refs.dialogSelectExpress.open();
			}
		},
		hideSelectExpressDialog:function(){
			this.$refs.dialogSelectExpress.close();
		},
		showhxqr:function(e){
			this.hexiao_qr = e.currentTarget.dataset.hexiao_qr
			this.$refs.dialogHxqr.open();
		},
		closeHxqr:function(){
			this.$refs.dialogHxqr.close();
		},
		searchConfirm:function(e){
			this.keyword = e.detail.value;
      this.getdata(false);
		},
		showhxqr2:function(e){
      var that = this;
			var leftnum = e.currentTarget.dataset.num - e.currentTarget.dataset.hxnum;
			this.hxogid = e.currentTarget.dataset.id;
			if(leftnum <= 0){
				app.alert('没有剩余核销数量了');return;
			}
			var hxnumlist = [];
			for(var i=0;i<leftnum;i++){
				hxnumlist.push((i+1)+'');
			}
      if (hxnumlist.length > 6) {
				that.hxnumlist = hxnumlist;
        that.selecthxnumDialogShow = true;
        that.hxnum = '';
      } else {
        uni.showActionSheet({
          itemList: hxnumlist,
          success: function (res) {
						if(res.tapIndex >= 0){
							that.hxnum = hxnumlist[res.tapIndex];
							that.gethxqr();
						}
          }
        });
      }
		},
		gethxqr(){
      var that = this;
			var hxnum = this.hxnum;
			var hxogid = this.hxogid;
			if(!hxogid){
				app.alert('请选择要核销的商品');return;
			}
			if(!hxnum){
				app.alert('请选择核销数量');return;
			}
			app.showLoading();
			app.post('ApiOrder/getproducthxqr', {hxogid: hxogid,hxnum:hxnum}, function (data) {
				app.showLoading(false);
				if(data.status == 0){
					app.alert(data.msg);
				}else{
					that.hexiao_qr = data.hexiao_qr
					that.$refs.dialogHxqr.open();
				}
			});
		},
    hxnumRadioChange: function (e) {
      var that = this;
      var index = e.currentTarget.dataset.index;
			this.hxnum = this.hxnumlist[index];
			setTimeout(function(){
				that.selecthxnumDialogShow = false;
				that.gethxqr();
			},200)
    },
		hideSelecthxnumDialog:function(){
			this.selecthxnumDialogShow = false;
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
.order-box .head image{width:34rpx;height:34rpx;margin-right:4px}
.order-box .head .st0{ width: 140rpx; color: #ff8758; text-align: right; }
.order-box .head .st1{ width: 140rpx; color: #ffc702; text-align: right; }
.order-box .head .st2{ width: 140rpx; color: #ff4246; text-align: right; }
.order-box .head .st3{ width: 140rpx; color: #999; text-align: right; }
.order-box .head .st4{ width: 140rpx; color: #bbb; text-align: right; }

.order-box .content{display:flex;width: 100%; padding:16rpx 0px;border-bottom: 1px #f4f4f4 dashed;position:relative;align-items: center;}
.order-box .content:last-child{ border-bottom: 0; }
.order-box .content image{ width: 140rpx; height: 140rpx;}
.order-box .content .detail{display:flex;flex-direction:column;margin-left:14rpx;flex:1}
.order-box .content .detail .t1{font-size:26rpx;line-height:36rpx;margin-bottom:10rpx;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:2;overflow:hidden;}
.order-box .content .detail .t2{height: 44rpx;line-height: 44rpx;color: #999;overflow: hidden;font-size: 24rpx;}
.order-box .content .detail .t3{display:flex;height:40rpx;line-height:40rpx;color: #ff4246;}
.order-box .content .detail .x1{ flex:1}
.order-box .content .detail .x2{ width:110rpx;font-size:32rpx;text-align:right;margin-right:8rpx}

.order-box .bottom{ width:100%; padding: 10rpx 0px; border-top: 1px #f4f4f4 solid; color: #555;}
.order-box .op{ display:flex;flex-wrap: wrap;justify-content:flex-end;align-items:center;width:100%; padding: 10rpx 0px; border-top: 1px #f4f4f4 solid; color: #555;}

.btn1{margin-left:20rpx; margin-top: 10rpx;max-width:160rpx;height:60rpx;line-height:60rpx;color:#fff;border-radius:3px;text-align:center;padding: 0 20rpx;}
.btn2{margin-left:20rpx; margin-top: 10rpx;max-width:160rpx;height:60rpx;line-height:60rpx;color:#333;background:#fff;border:1px solid #cdcdcd;border-radius:3px;text-align:center;padding: 0 20rpx;}

.hxqrbox{background:#fff;padding:50rpx;position:relative;border-radius:20rpx}
.hxqrbox .img{width:400rpx;height:400rpx}
.hxqrbox .txt{color:#666;margin-top:20rpx;font-size:26rpx;text-align:center}
.hxqrbox .close{width:50rpx;height:50rpx;position:absolute;bottom:-100rpx;left:50%;margin-left:-25rpx;border:1px solid rgba(255,255,255,0.5);border-radius:50%;padding:8rpx}
.tgr{font-size: 24rpx;}

.pstime-item{display:flex;border-bottom: 1px solid #f5f5f5;padding:20rpx 30rpx;}
.pstime-item .radio{flex-shrink:0;width: 32rpx;height: 32rpx;background: #FFFFFF;border: 2rpx solid #BFBFBF;border-radius: 50%;margin-right:30rpx}
.pstime-item .radio .radio-img{width:100%;height:100%}
.pdl10{padding-left: 10rpx;}

</style>