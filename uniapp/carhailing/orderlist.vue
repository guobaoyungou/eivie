<template>
<view class="container">
	<block v-if="isload">
		<dd-tab :itemdata="['全部','待付款','已付款','已完成','已取消']" :itemst="['all','0','1','3','4']" :st="st" :isfixed="true" @changetab="changetab"></dd-tab>
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
						<view class="f1" v-if="item.bid!=0 && item.binfo" @tap.stop="goto" :data-url="'/pagesExt/business/index?id=' + item.bid"><image :src="pre_url+'/static/img/ico-shop.png'"></image> {{item.binfo.name}}</view>
						<view v-else>订单号：{{item.ordernum}}</view>
						<view class="flex1"></view>
						<text v-if="item.status==0" class="st0">待付款</text>
						<text v-if="item.status==1 && item.refund_status==0" class="st1">已付款</text>
						<text v-if="item.status==1 && item.refund_status==1" class="st1">退款审核中</text>
						<text v-if="item.status==3" class="st4">已完成</text>
						<text v-if="item.status==4" class="st4">已关闭</text>
					</view>
					<view class="content" style="border-bottom:none">
						<view @tap.stop="goto" :data-url="'product?id=' + item.proid">
							<image :src="item.propic">
						</view>	
						<view class="detail">
							<text class="t1">{{item.proname}}</text>
							<text class="t2">
								<text v-if="item.cid == '2'">
									乘车日期：{{item.yy_date}}
								</text>
								<text v-if="item.cid == '1'">
									租车价格：￥{{item.product_price}} /天
								</text>
								<text v-if="item.cid == '3'">
									包车价格：￥{{item.product_price}} /天
								</text>
							</text>
							<text class="t2" v-if="item.cid ==1 || item.cid ==3">
								<text v-if="item.cid ==1">租车天数</text><text v-if="item.cid ==3">包车数量</text> ：x {{item.num}}</text>
							<view class="t3"><text class="x1 flex1">实付金额：￥{{item.totalprice}}</text></view>
						</view>
					</view>
					<view class="bottom"  v-if="item.refund_status!=0">
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
						<block v-if="item.status==1" >
							<view class="btn2"  v-if="item.totalprice==0" @tap.stop="toclose" :data-id="item.id">取消订单</view>
							<view v-if="item.totalprice>0 && (item.refund_status==0 || item.refund_status==3)" class="btn2" :data-cid="item.cid" :data-id="item.id" :data-price = "item.totalprice" @tap.stop="torefund" >申请退款</view>
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
		<view v-if="showdesc" class="xieyibox">
			<view class="xieyibox-content">
				<view style="overflow:scroll;height:100%;">
					<parse :content="sysset.refund_desc" @navigate="navigate"></parse>
				</view>
				<view style="position:absolute;z-index:9999;bottom:10px;left:7%;margin:0 auto;text-align:center; width: 30%;height: 60rpx; line-height: 60rpx; color: #fff; border-radius: 8rpx;background: #bdbdbd;"   @tap="canceldescClick">取消</view>
				<view style="position:absolute;z-index:9999;bottom:10px;right:7%;margin:0 auto;text-align:center; width: 50%;height: 60rpx; line-height: 60rpx; color: #fff; border-radius: 8rpx;" :style="{background:'linear-gradient(90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'}"  @tap="hidedescClick">已阅读并同意</view>
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
      nodata: false,
      codtxt: "",
			keyword:'',
			isshowpandan:false,
			showdesc:false,//弹窗显示
			isagree:0,
			sysset:[],
			url:'',
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
      var pagenum = that.pagenum;
      var st = that.st;
			that.nodata = false;
			that.nomore = false;
			that.loading = true;
      app.post('ApiCarHailing/orderlist', {st: st,pagenum: pagenum,keyword:that.keyword}, function (res) {
				that.loading = false;
				var data = res.datalist;
				that.sysset = res.sysset;
				var ishowpaidan = res.ishowpaidan;	
				that.ishowpaidan = ishowpaidan
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
	showdescClick: function () {	
	  this.showdesc = true;
	},
	canceldescClick:function(){
		this.showdesc = false;
	},
	hidedescClick:function(){
		this.showdesc = false;
		this.isagree = 1;
		var url = this.url;
		app.goto(url);
	},
    changetab: function (st) {
      this.st = st;
      uni.pageScrollTo({
        scrollTop: 0,
        duration: 0
      });
      this.getdata();
    },
	torefund:function(e){
		var orderid = e.currentTarget.dataset.id;
		var price = e.currentTarget.dataset.price;
		var url='refund?orderid='+orderid+'&price='+price;
		this.url = url;
		this.isagree = false;
		if(this.sysset.refund_desc_status ==1){
			if(!this.isagree){
				this.showdesc = true;
				return;
			}
		}
		
		app.goto(url);
	},
    toclose: function (e) {
      var that = this;
      var orderid = e.currentTarget.dataset.id;
      app.confirm('确定要关闭该订单吗?', function () {
				app.showLoading('提交中');
        app.post('ApiCarHailing/closeOrder', {orderid: orderid}, function (data) {
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
        app.post('ApiCarHailing/delOrder', {orderid: orderid}, function (data) {
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
        app.post('ApiCarHailing/orderCollect', {orderid: orderid}, function (data) {
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
		}
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
.order-box .content .detail .t2{height: 36rpx;line-height: 36rpx;color: #666;overflow: hidden;font-size: 24rpx;}
.order-box .content .detail .t3{display:flex;height:40rpx;line-height:40rpx;color: #ff4246;}
.order-box .content .detail .x1{ flex:1}
.order-box .content .detail .x2{ width:100rpx;font-size:32rpx;text-align:right;margin-right:8rpx}

.order-box .bottom{ width:100%; padding: 10rpx 0px; border-top: 1px #f4f4f4 solid; color: #555;}
.order-box .op{ display:flex;justify-content:flex-end;align-items:center;width:100%; padding: 10rpx 0px; border-top: 1px #f4f4f4 solid; color: #555;}

.btn1{margin-left:20rpx;width:160rpx;height:60rpx;line-height:60rpx;color:#fff;border-radius:3px;text-align:center; font-size: 24rpx;}
.btn2{margin-left:20rpx;width:160rpx;height:60rpx;line-height:60rpx;color:#333;background:#fff;border:1px solid #cdcdcd;border-radius:3px;text-align:center;font-size: 24rpx;}
.xieyibox{width:100%;height:100%;position:fixed;top:0;left:0;z-index:99;background:rgba(0,0,0,0.7)}
.xieyibox-content{width:90%;margin:0 auto;height:80%;margin-top:20%;background:#fff;color:#333;padding:5px 10px 50px 10px;position:relative;border-radius:2px}

</style>