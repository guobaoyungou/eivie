<template>
<view class="container">
	<block v-if="isload">
		<dd-tab :itemdata="['全部','待付款','待核销','已核销']" :itemst="['all','0','1','3']" :st="st" :isfixed="true" @changetab="changetab"></dd-tab>
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
					<text class="flex1">订单号：{{item.ordernum}}</text>
					<text v-if="item.status==0" class="st0">待付款</text>
					<text v-if="item.status==1" class="st1">待核销</text>
					<text v-if="item.status==3" class="st3">已核销</text>
					<text v-if="item.status==4" class="st4">已关闭</text>
				</view>
        <view class="head" style="color: #000;" @tap.stop="goto" :data-url="'list?gbid=' + item.gbid+'&bid='+item.bid">
        	<text class="flex1" >礼包：{{item.proname}}</text>
        </view>
        <block v-for="(item2, idx) in item.prolist" :key="idx">
          <view class="content" :style="idx+1==item.procount?'border-bottom:none':''">
            <view style="width: 100%;display: flex;">
              <view @tap.stop="goto" :data-url="'detail?id=' + item2.proid+'&gbid=' + item.gbid+'&bid='+item2.bid">
                <image :src="item2.pic"></image>
              </view>
              <view class="detail">
                <text class="t1">{{item2.name}}</text>
                <view class="t3"><text class="x1 flex1">￥{{item2.sell_price}}</text><text class="x2">×{{item2.num}}</text></view>
              </view>
            </view>
            
            <block v-if="(item.status==1 || item.status==2) && item2.hexiao_code">
              <view style="width: 100%;position: relative;">
                <view class="btn2" @tap.stop="showhxqr2" :data-id="item2.id" :data-num="item2.num" :data-hxnum="item2.hexiao_num" :data-hexiao_code="item2.hexiao_code" style="position: absolute;bottom:0;right: 0;">核销码</view>
              </view>
            </block>
            <block v-if="item.status==3 && !item2.hexiao_code">
              <view style="width: 100%;position: relative;">
                <view class="btn2" style="position: absolute;bottom:0;right: 0;border: 0;">已核销</view>
              </view>
            </block>
          </view>
        </block>
				<view class="bottom">
					<text>共计{{item.totalnum}}件 实付:￥{{item.totalprice}}</text>
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
					<!-- <block v-if="item.status==2">
						<view class="btn1" :style="{background:t('color1')}" @tap.stop="orderCollect" :data-id="item.id">确认核销</view>
					</block> -->
          <block v-if="(item.status==1 || item.status==2) && item.hexiao_qr">
          	<view class="btn2" @tap.stop="showhxqr" :data-hexiao_qr="item.hexiao_qr">核销码</view>
          </block>
					<block v-if="item.status==4">
						<view class="btn2" @tap.stop="todel" :data-id="item.id">删除订单</view>
					</block>
				</view>
			</view>
			</block>
		</view>
		<nomore v-if="nomore"></nomore>
		<nodata v-if="nodata"></nodata>
	</block>
  <uni-popup id="dialogHxqr" ref="dialogHxqr" type="dialog">
  	<view class="hxqrbox">
  		<image :src="hexiao_qr" @tap="previewImage" :data-url="hexiao_qr" class="img"/>
  		<view class="txt">请出示核销码给核销员进行核销</view>
  		<view class="close" @tap="closeHxqr">
  			<image :src="pre_url+'/static/img/close2.png'" style="width:100%;height:100%"/>
  		</view>
  	</view>
  </uni-popup>
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
    changetab: function (st) {
      this.st = st;
      uni.pageScrollTo({
        scrollTop: 0,
        duration: 0
      });
      this.getdata();
    },
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
      app.post('ApiGiftBag/orderlist', {st: st,pagenum: pagenum,keyword:that.keyword}, function (res) {
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
    toclose: function (e) {
      var that = this;
      var orderid = e.currentTarget.dataset.id;
      app.confirm('确定要关闭该订单吗?', function () {
				app.showLoading('提交中');
        app.post('ApiGiftBag/closeOrder', {orderid: orderid}, function (data) {
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
        app.post('ApiGiftBag/delOrder', {orderid: orderid}, function (data) {
					app.showLoading(false);
          app.success(data.msg);
          setTimeout(function () {
            that.getdata();
          }, 1000);
        });
      });
    },
    orderCollect: function (e) {
      return;
      var that = this;
      var orderid = e.currentTarget.dataset.id;
      app.confirm('确定要收货吗?', function () {
				app.showLoading('提交中');
        app.post('ApiGiftBag/orderCollect', {orderid: orderid}, function (data) {
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
    showhxqr2:function(e){
      var that = this;
      that.hxogid = e.currentTarget.dataset.id;
      
      var hxnum = e.currentTarget.dataset.hxnum;
    	var num  = e.currentTarget.dataset.num;
      var leftnum = num - hxnum;
    	if(leftnum <= 0){
    		app.alert('没有剩余核销数量了');return;
    	}
      
      that.hxnum  = 1;
    	that.gethxqr();
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
.order-content{display:flex;flex-direction:column}
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

.order-box .bottom{ width:100%; padding: 10rpx 0px; border-top: 1px #f4f4f4 solid; color: #555;}
.order-box .op{ display:flex;flex-wrap: wrap;align-items:center;width:100%; padding: 10rpx 0px; border-top: 1px #f4f4f4 solid; color: #555;}

.btn1{margin-left:20rpx;margin-top: 10rpx;width:160rpx;height:60rpx;line-height:60rpx;color:#fff;border-radius:3px;text-align:center}
.btn2{margin-left:20rpx;margin-top: 10rpx;width:160rpx;height:60rpx;line-height:60rpx;color:#333;background:#fff;border:1px solid #cdcdcd;border-radius:3px;text-align:center}


.hxqrbox{background:#fff;padding:50rpx;position:relative;border-radius:20rpx}
.hxqrbox .img{width:400rpx;height:400rpx}
.hxqrbox .txt{color:#666;margin-top:20rpx;font-size:26rpx;text-align:center}
.hxqrbox .close{width:50rpx;height:50rpx;position:absolute;bottom:-100rpx;left:50%;margin-left:-25rpx;border:1px solid rgba(255,255,255,0.5);border-radius:50%;padding:8rpx}
.tgr{font-size: 24rpx;}
</style>