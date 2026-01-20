<template>
<view class="container">
	<block v-if="isload">
		<dd-tab :itemdata="['全部','待入住','已到店','已离店','退款']" :itemst="['all','2','3','4','10']" :st="st" :isfixed="true" @changetab="changetab"></dd-tab>
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
				<view class="order-box" @tap="goto" :data-url="'orderdetail?id=' + item.id">
					<view class="head">
						<view class="f1"><image :src="pre_url+'/static/img/ico-shop.png'"></image> {{item.hotel.name}}</view>
						<view class="flex1"></view>
						<text v-if="item.status==0" class="st0">待付款</text>
						<text v-if="item.status==1" class="st1">待确认</text>
						<text v-if="item.status==2" class="st2">待入住</text>
						<text v-if="item.status==3" class="st3">已到店</text>
						<text v-if="item.status==4" class="st4">已离店</text>
						<text v-if="item.status==-1" class="st4">已关闭</text>
					</view>

					<view class="content" >
						<view >
							<image :src="item.pic"></image>
						</view>
						<view class="detail">
							<text class="t1">{{item.totalnum}}{{text['间']}}, {{item.title}}</text>
							<text class="t2" style="color:#666">{{item.in_date}} - {{item.leave_date}}</text>
							<view class="t3">
								<block v-if="item.isbefore==1">
									<text class="x1 flex1" v-if="item.real_usemoney>0 && item.real_roomprice>0">实付房费：{{item.real_usemoney}}{{moneyunit}} + ￥{{item.real_roomprice}}</text>
									<text class="x1 flex1" v-else-if="item.real_usemoney>0 && item.real_roomprice==0">实付房费：￥{{item.real_usemoney}}{{moneyunit}}</text>
									<text  class="x1 flex1" v-else>实付房费：￥{{item.real_roomprice}}</text>
 								</block>
								<block v-else>
									<text  class="x1 flex1" v-if="item.use_money>0 && item.leftmoney>0">房费：{{item.use_money}}{{moneyunit}} + ￥{{item.leftmoney}}</text>
									<text class="x1 flex1" v-else-if="item.use_money>0 && item.leftmoney==0">房费：￥{{item.use_money}}{{moneyunit}}</text>
									<text class="x1 flex1" v-else>房费：￥{{item.sell_price}}</text>
 
								</block>
							</view>
							<view class="t3" v-if="item.coupon_money > 0">
								<block>
									<text  class="x1 flex1 ">优惠券:￥{{ item.coupon_money }}</text>

								</block>
							</view>
						</view>
					</view>
					
					<view class="bottom" style="display:flex; justify-content: space-between;">
						<text>共{{item.daycount}}晚 
							<block v-if="item.use_money>0 && item.leftmoney>0">
									实付: 押金￥{{item.yajin_money}}+{{text['服务费']}}￥{{item.fuwu_money}}+房费￥{{item.leftmoney}}+{{item.use_money?item.use_money:0}}{{moneyunit}}
							</block>
							<block v-else-if="item.use_money>0 && item.leftmoney==0">
									实付: 押金￥{{item.yajin_money}}+{{text['服务费']}}￥{{item.fuwu_money}}+房费{{item.use_money?item.use_money:0}}{{moneyunit}}
							</block>
							<block v-else>
									实付:￥{{item.totalprice}}
							</block>
						</text>
				
					</view>
					<view class="bottom" v-if="item.refund_status>0">
						<text v-if="item.refund_status==1" style="color:red"> 退款中￥{{item.refund_money}}</text>
						<text v-if="item.refund_status==2" style="color:red"> 已退款￥{{item.refund_money}}</text>
						<text v-if="item.refund_status==3" style="color:red"> 退款申请已驳回</text>
					</view>
					<view class="op">
						<view @tap.stop="goto" :data-url="'orderdetail?id=' + item.id" class="btn2">详情</view>
						<block v-if="item.status==0">
							<view class="btn2" @tap.stop="toclose" :data-id="item.id">关闭订单</view>
							<view class="btn1" :style="{background:t('color1')}" @tap.stop="goto" :data-url="'/pagesExt/pay/pay?id=' + item.payorderid">去付款</view>
						</block>
						<block v-if="item.status==1 && item.isrefund==1">
								<view v-if="item.refund_status==0 || item.refund_status==3" class="btn2"  @tap.stop="goto" :data-url="'refund?id='+item.id">申请退款</view>
						</block>
						<block v-if="item.status==2 && item.isrefund==1">
								<view v-if="item.refund_status==0 || item.refund_status==3" class="btn2"  @tap.stop="goto" :data-url="'refund?id='+item.id">申请退款</view>
								<!--<view  class="btn2"  @tap="goto" :data-url="'befor_out?id='+item.id">提前离店</view>-->
						</block>
						<block v-if="item.status==4">
								<block v-if="item.yajin_refund_status==2">
									<text class="btn2 color1" :style="'background:#1BA035;color:#FFF'">押金已退</text> 
								</block>
								<block v-else>
									<text class="btn2 color1" :style="'background:rgba('+t('color1rgb')+',0.8);color:#FFF'"  @tap.stop="refundyajin" v-if="item.yajin_money>0" :data-index="index" >退押金</text> 	
								</block>
								<view v-if="item.hotel.comment == 1"  class="btn2 color1"  :style="'background:#FCC421;color:#FFF'" @tap.stop="goto" :data-url="'comment?oid='+item.id">评价</view>
						</block>
						<block v-if="item.status==-1">
							<view class="btn2" @tap.stop="todel" :data-id="item.id">删除订单</view>
						</block>
					</view>
				</view>
			</block>
			
			
			<view class="model_yajin"  v-if="showyajin">
				<view class="yajinbox">
					<view class="yajincontent">
						<view class="title">押金详情</view>
						<!--<view class="item"><label>应退押金：</label>
							<view class="f2"><text style="color: red; font-weight: bold;">{{yajin}}</text>元</view>
						</view>-->
						<view class="desc" v-if="yajininfo.refund_status==0 && set.yajin_desc">
							{{set.yajin_desc}}
						</view>
						
						<block v-if="yajininfo.refund_status>0">
							<view class="item">
								<label>申请时间：</label>	
								<view class="f2"><text>{{dateFormat(yajininfo.apply_time)}}</text></view>
							</view>
							<view class="item"><label>退款状态：</label>
								<text class="t2 red" v-if="yajininfo.refund_status==1">审核中</text>
								<text class="t2 red" v-if="yajininfo.refund_status==2">已退款</text>
								<text class="t2 red" v-if="yajininfo.refund_status==-1">已驳回</text>
							</view>
							<view class="item"  v-if="yajininfo.refund_status==2"><label>退款时间：</label><text>{{yajininfo.refund_time}}</text></view>
						</block>
						<block v-if="yajininfo.refund_status==-1">
							<view class="item"><label>申请时间：</label><text>{{dateFormat(yajininfo.apply_time)}}</text></view>
							<view class="item"><label>退款状态：</label>
								<text class="t2 red">已驳回</text>
							</view>
							<view class="item"  v-if="yajininfo.refund_status==2"><label>退款时间：</label><text >{{yajininfo.refund_time}}</text></view>
						</block>
						<view class="item" v-if="yajininfo.refund_status==-1"><label>驳回原因：</label><text >{{yajininfo.refund_reason}}</text></view>
						
						
						<view class="yajinbtn" v-if="yajininfo.refund_status<1">
							<view class="btn1" @tap="closeYajin"  :style="'background:#FCC421;color:#FFF; border:none'">稍后申请</view>
							<view class="btn2" @tap="yajinsubmit"  :style="'background:rgba('+t('color1rgb')+',0.8);color:#FFF;border:none'" >立即申请</view>
						</view>
						
						
						
						
					</view>
					<view class="close" @tap="closeYajin">
						<image :src="pre_url+'/static/img/close2.png'" style="width:100%;height:100%"/>
					</view>
				</view>
			</view>
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
      nodata: false,
      nomore: false,
      codtxt: "",
			keyword:'',
			text:[],
			hotelid:0,
			yajin:0,
			orderid:0,
			yajininfo:[],
			set:[],
			showyajin:false,
			tmplids:[],
			moneyunit:'元',
			pre_url:app.globalData.pre_url,
    };
  },

  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		if(this.opt && this.opt.st){
			this.st = this.opt.st;
		}
		if(this.opt && this.opt.hotelid){
			this.hotelid = this.opt.hotelid;
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
      app.post('ApiHotel/orderlist', {st: st,pagenum: pagenum,keyword:that.keyword,hotelid:that.hotelid}, function (res) {
				that.loading = false;
        var data = res.datalist;
        if (pagenum == 1) {
					that.datalist = data;
					that.set = res.set
					that.moneyunit = res.moneyunit
					that.text = res.text
          if (data.length == 0) {
            that.nodata = true;
          }
					uni.setNavigationBarTitle({
						title: '订单列表'
					});
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
        app.post('ApiHotel/closeOrder', {orderid: orderid}, function (data) {
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
        app.post('ApiHotel/delOrder', {orderid: orderid}, function (data) {
					app.showLoading(false);
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
		refundyajin:function(e){
			var that = this;
			var index = e.currentTarget.dataset.index;
			that.yajin = that.datalist[index].yajin_money
			var orderid =  that.datalist[index].id
			that.orderid =  orderid
			app.get('ApiHotel/refundYajin', {orderid: orderid}, function (data) {
				if(data.status==1){
						that.yajininfo = data.detail
						that.tmplids = data.tmplids;
						console.log(that.tmplids);
				}
			});
			//this.$refs.dialogYajin.open();
			this.showyajin = true;
		},
		closeYajin:function(){
			//this.$refs.dialogYajin.close();
			this.showyajin = false;
		},
		yajinsubmit: function (e) {
		  var that = this;
		  var orderid = that.orderid;
		  app.confirm('确定要申请退押金吗?', function () {
				app.showLoading('提交中');
				app.post('ApiHotel/refundYajin', {orderid: orderid}, function (data) {
					app.showLoading(false);
					app.success(data.msg);
					that.subscribeMessage(function () {
					  setTimeout(function () {
					   that.showyajin=false;
					   that.getdata();
					  }, 1000);
					});
				});
		  });
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
.order-box .content .detail .t1{font-size:28rpx;line-height:36rpx;margin-bottom:10rpx;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:2;overflow:hidden;}
.order-box .content .detail .t2{height: 46rpx;line-height: 46rpx;color: #999;overflow: hidden;font-size: 26rpx;}
.order-box .content .detail .t3{display:flex;height:40rpx;line-height:40rpx;color: #ff4246;}
.order-box .content .detail .x1{ flex:1}
.order-box .content .detail .x2{ width:100rpx;font-size:32rpx;text-align:right;margin-right:8rpx}

.order-box .bottom{ width:100%; padding: 10rpx 0px; border-top: 1px #f4f4f4 solid; color: #555;}
.order-box .op{ display:flex;justify-content:flex-end;align-items:center;width:100%; padding: 10rpx 0px; border-top: 1px #f4f4f4 solid; color: #555;}

.btn1{margin-left:20rpx;width:160rpx;height:60rpx;line-height:60rpx;color:#fff;border-radius:3px;text-align:center}
.btn2{margin-left:20rpx;width:160rpx;height:60rpx;line-height:60rpx;color:#333;background:#fff;border:1px solid #cdcdcd;border-radius:3px;text-align:center}
.btn2.color1{ background-color: #ff8758; color: #fff; border:none}


.yajinbox{background:#fff;padding:30rpx 50rpx ;position:relative;border-radius:20rpx;width: 80%; margin: 0 auto; top: 30%; }
.yajinbox .close{width:50rpx;height:50rpx;position:absolute;bottom:-100rpx;left:50%;margin-left:-25rpx;border:1px solid rgba(255,255,255,0.5);border-radius:50%;padding:8rpx}
.yajincontent{ margin: 0 20rpx; width: 100%;}
.yajincontent .title{ font-weight: bold;  margin-bottom: 30rpx; font-size: 32rpx; text-align: center; }
.yajincontent .desc{ width: 100%;}
.yajincontent .item{ display: flex; justify-content: space-between;align-items: center; padding:15rpx 0 }
.yajincontent .yajinprice{ margin-bottom: 50rpx;}
.yajinbtn{ display: flex; margin-top: 30rpx;justify-content: flex-end;}
.yajinbtn .btn1{ background-color:#f3f3f3 ;  color: #999;}
.yajinbox .title{ font-weight: bold;}
.yajincontent .f2{ width: 75%; display: flex; justify-content: flex-end;}
.yajincontent .item .red{color:red}
.yajincontent .item label{ width: 160rpx;}
.yajincontent .item .t2{ width: 75%;    text-align: right;}
.model_yajin{ background-color: rgba(0,0,0,0.5); position: fixed; width: 100%; height:100%}

</style>