<template>
<view class="container">
	<block v-if="isload">
		<view class="ordertop" :style="'background:url('+(pre_url + '/static/img/ordertop.png')+');background-size:100%'">
			<view class="f1" v-if="detail.status==0">
				<view class="t1">结算中</view>
			</view>
			<view class="f1" v-if="detail.status==1">
				<view class="t1">已完成</view>
			</view>
			<view class="f1" v-if="detail.status==2">
				<view class="t1">挂单中</view>
			</view>
			<view class="f1" v-if="detail.status==10">
				<view class="t1">已退款</view>
			</view>
		</view>
		<view class="product">
			<view v-for="(item, idx) in prolist" :key="idx" class="box">
				<view class="content">
					<view @tap="goto" :data-url="'/pages/shop/product?id=' + item.proid">
						<image :src="item.propic"></image>
					</view>
					<view class="detail">
						<text class="t1">{{item.proname}}</text>
						<view class="t2 flex flex-y-center flex-bt">
							<text>{{item.ggname}}</text>
							<view class="btn3" v-if="detail.status==3 && item.iscomment==0 && shopset.comment==1" @tap.stop="goto" :data-url="'comment?ogid=' + item.id">去评价</view>
							<view class="btn3" v-if="detail.status==3 && item.iscomment==1" @tap.stop="goto" :data-url="'comment?ogid=' + item.id">查看评价</view>
						</view>
						<view class="t3" ><text class="x1 flex1">￥{{item.sell_price}}</text><text class="x2">×{{item.num}}</text></view>
						<!-- <view class="t4 flex flex-x-bottom">
							<view class="btn3" v-if="detail.status==3 && item.iscomment==0 && shopset.comment==1" @tap.stop="goto" :data-url="'comment?ogid=' + item.id">去评价</view>
							<view class="btn3" v-if="detail.status==3 && item.iscomment==1" @tap.stop="goto" :data-url="'comment?ogid=' + item.id">查看评价</view>
						</view> -->
						<block v-if="(detail.status==1 || detail.status==2) && detail.freight_type==1 && item.hexiao_code">
							<view class="btn2" @tap.stop="showhxqr2" :data-id="item.id" :data-num="item.num" :data-hxnum="item.hexiao_num" :data-hexiao_code="item.hexiao_code" style="position:absolute;top:20rpx;right:0rpx;">核销码</view>
						</block>
					</view>
				</view>
			</view>
		</view>

		<view class="orderinfo">
			<view class="item">
				<text class="t1">订单编号</text>
				<text class="t2" user-select="true" selectable="true">{{detail.ordernum}}</text>
			</view>
			<view class="item">
				<text class="t1">下单时间</text>
				<text class="t2">{{detail.createtime}}</text>
			</view>
			<view class="item" v-if="detail.status>0 && detail.paytypeid!='4' && detail.paytime">
				<text class="t1">支付时间</text>
				<text class="t2">{{detail.paytime}}</text>
			</view>
			<view class="item" v-if="detail.paytype">
				<text class="t1">支付方式</text>
				<text class="t2">{{detail.paytype}}</text>
			</view>
			<view class="item" v-if="detail.status ==2">
				<text class="t1">挂单时间</text>
				<text class="t2">{{detail.hangup_time}}</text>
			</view>
		</view>
		<view class="orderinfo">
			<view class="item">
				<text class="t1">商品金额</text>
				<text class="t2 red">¥{{detail.pre_totalprice}}</text>
			</view>
			<view class="item" v-if="detail.leveldk_money > 0">
				<text class="t1">{{t('会员')}}折扣</text>
				<text class="t2 red">-¥{{detail.leveldk_money}}</text>
			</view>
			<view class="item" v-if="detail.coupon_money > 0">
				<text class="t1">{{t('优惠券')}}抵扣</text>
				<text class="t2 red">-¥{{detail.coupon_money}}</text>
			</view>
			
			<view class="item" v-if="detail.scoredk_money > 0">
				<text class="t1">{{t('积分')}}抵扣</text>
				<text class="t2 red">-¥{{detail.scoredk_money}}</text>
			</view>
            <view class="item" v-if="detail.dec_money > 0">
            	<text class="t1">{{t('余额')}}抵扣</text>
            	<text class="t2 red">-¥{{detail.dec_money}}</text>
            </view>
			<view class="item" v-if="detail.moling_money > 0">
				<text class="t1">抹零</text>
				<text class="t2 red">-¥{{detail.moling_money}}</text>
			</view>
			<view class="item">
				<text class="t1">实付款</text>
				<text class="t2 red"><text v-if="showprice_dollar && detail.usd_totalprice>0">${{detail.usd_totalprice}}</text>  ¥{{detail.totalprice}}</text>
			</view>
			<view class="item" >
				<text class="t1">收银员</text>
				<text class="t2 ">{{detail.admin_user}}</text>
			</view>
			<view class="item">
				<text class="t1">订单状态</text>
				<text class="t2" v-if="detail.status==0">结算中</text>
				<text class="t2" v-if="detail.status==1">已完成</text>
				<text class="t2" v-if="detail.status==2">挂单中</text>
				<text class="t2" v-if="detail.status==10">已退款</text>
			</view>

			<view class="item" v-if="detail.refund_money>0">
				<text class="t1">已退款</text>
				<text class="t2 red">¥{{detail.refund_money}}</text>
			</view>
			<view class="item" v-if="detail.refund_money>0">
				<text class="t1">退款时间</text>
				<text class="t2 red">{{dateFormat(detail.refund_time)}}</text>
			</view>
			<view class="item" v-if="detail.refund_status>0">
				<text class="t1">退款状态</text>
				<text class="t2 red" v-if="detail.refund_status==1">已退款</text>
			</view>
		</view>

		<view style="width:100%;height:calc(160rpx + env(safe-area-inset-bottom));"></view>

		<view class="bottom notabbarbot" v-if="fromfenxiao==0">
			<block v-if="detail.payaftertourl && detail.payafterbtntext">
				<view style="position:relative">
					<block v-if="detail.payafter_username">
						<view class="btn2">{{detail.payafterbtntext}}</view>
						<!-- #ifdef H5 -->
						<wx-open-launch-weapp :username="detail.payafter_username" :path="detail.payafter_path" style="position:absolute;top:0;left:0;right:0;bottom:0;z-index:8">
							<script type="text/wxtag-template">
								<div style="width:100%;height:40px;"></div>
							</script>
						</wx-open-launch-weapp>
						<!-- #endif -->
					</block>
					<block v-else>
						<view class="btn2" @tap="goto" :data-url="detail.payaftertourl">{{detail.payafterbtntext}}</view>
					</block>
				</view>
			</block>
		
		</view>
		<uni-popup id="dialogHxqr" ref="dialogHxqr" type="dialog">
			<view class="hxqrbox">
				<image :src="hexiao_qr" @tap="previewImage" :data-url="hexiao_qr" class="img"/>
				<view class="txt">请出示核销码给核销员进行核销</view>
				<view v-if="detail.hexiao_code_member">
					<input type="number" placeholder="请输入核销密码" @input="set_hexiao_code_member" style="border: 1px #eee solid;padding: 10rpx;margin:20rpx 10rpx;height:70rpx;border-radius:4px;flex:1;font-size:28rpx;"/>
					<button @tap="hexiao" class="btn" :style="{background:t('color1')}">确定</button>
				</view>
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
	<wxxieyi></wxxieyi>
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
      shopset: "",
      storeinfo: "",
      lefttime: "",
      codtxt: "",
			pay_transfer_info:{},
			invoice:0,
			selectExpressShow:false,
			express_content:'',
			fromfenxiao:0,
			hexiao_code_member:'',
			showprice_dollar:false,
			hexiao_qr:'',
			selecthxnumDialogShow:false,
			hxogid:'',
			hxnum:'',
			hxnumlist:[],
			storelist:[],
			storeshowall:false,
    };
  },

  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		if (this.opt && this.opt.fromfenxiao && this.opt.fromfenxiao == '1'){
		  this.fromfenxiao = 1;
    }
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
			app.get('ApiOrder/getCashierOrderDetail', {id: that.opt.id}, function (res) {
				that.loading = false;
				that.iscommentdp = res.iscommentdp,
				that.detail = res.detail;
				that.prolist = res.prolist;
				that.shopset = res.shopset;
				that.storeinfo = res.storeinfo;
				that.lefttime = res.lefttime;
				that.codtxt = res.codtxt;
				that.pay_transfer_info =  res.pay_transfer_info;
				that.payorder = res.payorder;
				that.invoice = res.invoice;
				that.storelist = res.storelist || [];
				that.showprice_dollar = res.showprice_dollar
				if (res.lefttime > 0) {
					interval = setInterval(function () {
						that.lefttime = that.lefttime - 1;
						that.getdjs();
					}, 1000);
				}
				that.loaded();

				if (that.detail.mdid == -1 && that.storelist) {
					app.getLocation(function(res) {
						var latitude = res.latitude;
						var longitude = res.longitude;
						that.latitude = latitude;
						that.longitude = longitude;
						var storelist = that.storelist;
						for (var x in storelist) {
							if (latitude && longitude && storelist[x].latitude && storelist[x].longitude) {
								var juli = that.getDistance(latitude, longitude,storelist[x].latitude, storelist[x].longitude);
								storelist[x].juli = juli;
							}
						}
						storelist.sort(function(a, b) {
							return a["juli"] - b["juli"];
						});
						for (var x in storelist) {
							if (storelist[x].juli) {
								storelist[x].juli = '距离'+storelist[x].juli + '千米';
							}
						}
						that.storelist = storelist;
					});
				}
			});
		},
		set_hexiao_code_member:function(e){
			this.hexiao_code_member = e.detail.value;
		},
		hexiao: function () {
			let that = this;
			
			that.loading = true;
			app.post('ApiOrder/hexiao', {orderid: that.opt.id,hexiao_code_member:that.hexiao_code_member}, function (res) {
				that.loading = false;
				if(res.status != 1){
					app.error(res.msg);return;
				}
				app.success(res.msg);
				that.closeHxqr();
				setTimeout(function () {
				  that.getdata();
				}, 1000);
			});
		},
    getdjs: function () {
      var that = this;
      var totalsec = that.lefttime;

      if (totalsec <= 0) {
        that.djs = '00时00分00秒';
      } else {
        var houer = Math.floor(totalsec / 3600);
        var min = Math.floor((totalsec - houer * 3600) / 60);
        var sec = totalsec - houer * 3600 - min * 60;
        var djs = (houer < 10 ? '0' : '') + houer + '时' + (min < 10 ? '0' : '') + min + '分' + (sec < 10 ? '0' : '') + sec + '秒';
        that.djs = djs;
      }
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
        app.post('ApiOrder/closeOrder', {orderid: orderid}, function (data) {
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
      app.confirm('确定要收货吗?', function () {
				app.showLoading('收货中');
				if(app.globalData.platform == 'wx' && that.detail.wxpaylog && that.detail.wxpaylog.is_upload_shipping_info == 1){
					app.post('ApiOrder/orderCollectBefore', {orderid: orderid}, function (data) {
						app.showLoading(false);
						if(data.status != 1){app.error(data.msg);return;}
						else{
							if (wx.openBusinessView) {
							  wx.openBusinessView({
							    businessType: 'weappOrderConfirm',
							    extraData: {
							      merchant_id: that.detail.wxpaylog.mch_id,
							      merchant_trade_no: that.detail.wxpaylog.ordernum,
							      transaction_id: that.detail.wxpaylog.transaction_id
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
		showhxqr:function(e){
			this.hexiao_qr = e.currentTarget.dataset.hexiao_qr
			this.$refs.dialogHxqr.open();
		},
		closeHxqr:function(){
			this.$refs.dialogHxqr.close();
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
		openLocation:function(e){
			var latitude = parseFloat(e.currentTarget.dataset.latitude);
			var longitude = parseFloat(e.currentTarget.dataset.longitude);
			var address = e.currentTarget.dataset.address;
			uni.openLocation({
			 latitude:latitude,
			 longitude:longitude,
			 name:address,
			 address:address,
			 scale: 13
			})
		},
		openMendian: function(e) {
			var storeinfo = e.currentTarget.dataset.storeinfo;
			app.goto('/pages/shop/mendian?id=' + storeinfo.id);
		},
		logistics:function(e){
			var express_com = e.currentTarget.dataset.express_com
			var express_no = e.currentTarget.dataset.express_no
			var express_content = e.currentTarget.dataset.express_content
			var express_type = e.currentTarget.dataset.express_type
			var prolist = this.prolist;
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
				this.$refs.dialogSelectExpress.open();
			}
		},
		hideSelectExpressDialog:function(){
			this.$refs.dialogSelectExpress.close();
		},
		doStoreShowAll:function(){
			this.storeshowall = true;
		},
  }
};
</script>
<style>
	.text-min { font-size: 24rpx; color: #999;}
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
.product .box{width: 100%; padding:16rpx 0px;border-bottom: 1px #e5e5e5 dashed;}
.product .content{display:flex;position:relative;}
.product .box:last-child{ border-bottom: 0; }
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
.orderinfo .item{display:flex;width:100%;padding:20rpx 0;border-bottom:1px dashed #ededed;overflow:hidden}
.orderinfo .item:last-child{ border-bottom: 0;}
.orderinfo .item .t1{width:200rpx;flex-shrink:0}
.orderinfo .item .t2{flex:1;text-align:right}
.orderinfo .item .t3{ margin-top: 3rpx;}
.orderinfo .item .red{color:red}

.bottom{ width: 100%;height:calc(92rpx + env(safe-area-inset-bottom));background: #fff; position: fixed; bottom: 0px;left: 0px;display:flex;justify-content:flex-end;align-items:center;padding: 0 20rpx;}

.btn { border-radius: 10rpx;color: #fff;}
.btn1{height:60rpx;line-height:60rpx;color:#fff;border-radius:3px;text-align:center;flex-shrink: 0;margin: 0 0 0 15rpx;padding: 0 15rpx;}
.btn2{height:60rpx;line-height:60rpx;color:#333;background:#fff;border:1px solid #cdcdcd;border-radius:3px;text-align:center;padding: 0 15rpx;flex-shrink: 0;margin: 0 0 0 15rpx;}
.btn3{font-size:24rpx;height:50rpx;line-height:50rpx;color:#333;background:#fff;border:1px solid #cdcdcd;border-radius:3px;text-align:center;padding: 0 15rpx;flex-shrink: 0;margin: 0 0 0 15rpx;}

.btitle{ width:100%;height:100rpx;background:#fff;padding:0 20rpx;border-bottom:1px solid #f5f5f5}
.btitle .comment{border: 1px #ffc702 solid;border-radius:10rpx;background:#fff; color: #ffc702;  padding: 0 10rpx; height: 46rpx; line-height: 46rpx;}
.btitle .comment2{border: 1px #ffc7c0 solid;border-radius:10rpx;background:#fff; color: #ffc7c0;  padding: 0 10rpx; height: 46rpx; line-height: 46rpx;}

.hxqrbox{background:#fff;padding:50rpx;position:relative;border-radius:20rpx}
.hxqrbox .img{width:400rpx;height:400rpx}
.hxqrbox .txt{color:#666;margin-top:20rpx;font-size:26rpx;text-align:center}
.hxqrbox .close{width:50rpx;height:50rpx;position:absolute;bottom:-100rpx;left:50%;margin-left:-25rpx;border:1px solid rgba(255,255,255,0.5);border-radius:50%;padding:8rpx}

.glassitem{background:#f5f5f5;display: flex;align-items: center;padding: 10rpx 0;font-size: 24rpx;}
.glassitem .gcontent{flex:1;padding: 0 20rpx;}
.glassheader{line-height: 50rpx;font-size: 26rpx;font-weight: 600;}
.glassrow{line-height: 40rpx;font-size: 26rpx;}
.glassrow .glasscol{min-width: 25%;text-align: center;}
.glassitem .bt{border-top:1px solid #e3e3e3}

.pstime-item{display:flex;border-bottom: 1px solid #f5f5f5;padding:20rpx 30rpx;}
.pstime-item .radio{flex-shrink:0;width: 32rpx;height: 32rpx;background: #FFFFFF;border: 2rpx solid #BFBFBF;border-radius: 50%;margin-right:30rpx}
.pstime-item .radio .radio-img{width:100%;height:100%}
.pdl10{padding-left: 10rpx;}

.radio-item {display: flex;width: 100%;color: #000;align-items: center;background: #fff;padding:20rpx 20rpx;border-bottom:1px dotted #f1f1f1}
.radio-item:last-child {border: 0}
.radio-item .f1 {color: #333;font-size:30rpx;flex: 1}
.storeviewmore{width:100%;text-align:center;color:#889;height:40rpx;line-height:40rpx;margin-top:10rpx}
.refundtips{background: #fff9ed; color: #ff5c5c;}
.refundtips textarea{font-size: 24rpx;line-height: 40rpx;width: 100%;height: auto; word-wrap : break-word;}
</style>