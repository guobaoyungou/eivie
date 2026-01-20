<template>
<view class="container">
	<block v-if="isload">
		<view class="page-bg-view" :style="{backgroundImage:`linear-gradient(${t('color1')} 30%,transparent)`}"></view>

    <view v-if="detail.status==0" class="head">
      <view class="head-title">等待付款</view>
      <view class="head-title2">未付款订单会失效或被关闭，请尽快付款</view>
      <view class="head-title2" v-if="djs">剩余时间：<text style="color: red;">{{djs}}</text></view>
    </view>
    <view class="head">
      <view  style="display: flex;margin-bottom: 20rpx;">
        <view v-if="detail.pic" class="content-pic">
          <image :src="detail.pic" mode="widthFix" style="width: 100%;border-radius: 10rpx 10rpx;"></image>
        </view>
        <view style="margin-left: 10rpx;">
          <view class="title">{{detail.title}}</view>
          <view class="title2">日期：{{detail.performDate}}</view>
          <view class="title2">时间：{{detail.performTime}}</view>
        </view>
      </view>
      
      <view style="padding: 0rpx 3%;background-color: #fff;">
        <view style="border-top:2rpx solid #f1f1f1 ;padding: 20rpx 0;">
          <view style="color: #191919;font-weight: bold;">{{detail.theaterPlaceName}}</view>
          <view v-if="detail.beforeCheckTime && detail.beforeCheckTime>0"style="color: #191919;">提前检票时间：{{detail.beforeCheckTime}}分钟</view>
          <view v-if="detail.beforeTime && detail.beforeTime>0"style="color: #191919;">提前演出入场时间：{{detail.beforeTime}}分钟</view>
          <view v-if="detail.timeLong && detail.timeLong>0"style="color: #191919;">演出时长：{{detail.timeLong}}分钟</view>
          <view v-if="detail.address" style="color: #999;font-size: 26rpx;">地址：{{detail.address}}</view>
        </view>
      </view>
    </view>

		<!-- 订票人信息 -->
    <view v-if="ordergoods" class="product">
      <view v-if="detail.tipmsg" class="tips-text">{{detail.tipmsg}}</view>
      <block v-for="(item, index) in ordergoods" :key="index">
        <view style="color: #191919;font-weight: bold;line-height: 50rpx;">{{item.areaName}}</view>
        <view class="box">
          <view class="book-person-view">
            <block v-for="(item2, index2) in item.certs" :key="index2">
              <view class="left-info-view flex-col ">
                <view class="left-topinfo flex flex-y-center">
                  <view class="name-view">{{item2.fname}}</view>
                </view>
                <view v-if="item2.realName || item2.certNo" style="margin: 10rpx 0;">{{item2.realName}} {{item2.certNo}}</view>
                <view v-if="detail.checkCodeType == 2 && item2.status>=1 && item3.status<=4" class="status-view flex flex-y-center">
                  <view v-if="item2.status==1" style="color: #ff8758;">待发码</view>
                  <view  v-if="item2.status==2" style="color: green;">待核销</view>
                  <view  v-if="item2.status==3" style="color: red;">已核销</view>
                  <view  v-if="item2.status==4"  style="color: #bbb;">已驳回</view>
                </view>

                <block v-if="item2.status==2 && item2.hexiao_qr">
                  <view class="btn2" @tap.stop="showhxqr" :data-hexiao_qr="item2.hexiao_qr" style="position:absolute;top:40rpx;right:10rpx;">核销码</view>
                </block>
                
                <view class="status-view">
                  <view>
                    <text style="color: #8D8D8D;font-weight: bold;">费用：</text>
                    <text style="color: red;">￥{{item2.totalprice}}</text>
                  </view>
                </view>
                
                <view v-if="item2.refund_status!=0" class="status-view flex flex-y-center" style="justify-content: space-between;">
                  <view v-if="item2.refund_status!=0" style="display: flex;margin-right: 20rpx;">
                    <view style="color: #8D8D8D;font-weight: bold;">退款状态：</view>
                    <text  v-if="item2.refund_status==1" style="color: #ff8758;">审核中</text>
                    <text  v-if="item2.refund_status==2" style="color: green;">已审核</text>
                    <text  v-if="item2.refund_status==3" style="color: red;">已退款</text>
                    <text  v-if="item2.refund_status==4"  style="color: #bbb;">已驳回</text>
                  </view>
                </view>
              </view>
            </block>
            
            <view v-if="item.refund_status!=0" class="status-view flex flex-y-center" style="justify-content: space-between;">
              <view v-if="item.refund_status!=0" style="display: flex;margin-right: 20rpx;">
                <view style="color: #8D8D8D;font-weight: bold;">退款状态：</view>
                <text  v-if="item.refund_status==1" style="color: #ff8758;">审核中</text>
                <text  v-if="item.refund_status==2" style="color: green;">已审核</text>
                <text  v-if="item.refund_status==3" style="color: red;">已退款</text>
                <text  v-if="item.refund_status==4"  style="color: #bbb;">已驳回</text>
              </view>
            </view>
          </view>
        </view>
      </block>
    </view>

		<view class="orderinfo">
      <view class="item">
      	<text class="t1">联系人</text>
      	<text class="t2">{{detail.linkName}}</text>
      </view>
      <view class="item">
      	<text class="t1">联系电话</text>
      	<text class="t2">{{detail.tel}}</text>
      </view>
			<view class="item flex-bt">
				<text class="t1">订单编号</text>
				<view class="ordernum-info flex-bt">
					<text class="t2" user-select="true" selectable="true">{{detail.ordernum}}</text>
					<view class="btn-class" :style="{backgroundColor:t('color1')}" @click="copy" :data-text='detail.ordernum'>复制</view>
				</view>
			</view>
			<view class="item">
				<text class="t1">下单时间</text>
				<text class="t2">{{detail.createtime}}</text>
			</view>
			<view class="item" v-if="detail.status>0 && detail.paytypeid!='4' && detail.paytime">
				<text class="t1">支付时间</text>
				<text class="t2">{{detail.paytime}}</text>
			</view>
			<view class="item" v-if="detail.paytypeid">
				<text class="t1">支付方式</text>
				<text class="t2">{{detail.paytype}}</text>
			</view>

			<view class="item">
				<text class="t1">实付款</text>
        <text class="t2 red">￥{{detail.totalprice}}  </text>
			</view>
			<view class="item">
				<text class="t1">订单状态</text>
				<text class="t2" v-if="detail.status==0">未付款</text>
				<text class="t2" v-if="detail.status==1">待发码</text>
				<text class="t2" v-if="detail.status==2">待核销</text>
        <text class="t2" v-if="detail.status==3">已核销</text>
				<text class="t2" v-if="detail.status==4">已关闭</text>
			</view>
      <block v-if="detail.refund_money>0">
        <view class="item" >
        	<view class="t1">退款金额</view>
        	<view class="t2 red">
            已退款,¥{{detail.refund_money}}
          </view>
        </view>
        <view v-if="detail.refund_reason" class="item" >
        	<view class="t1">退款原因</view>
        	<view class="t2 red">
            {{detail.refund_reason}}
          </view>
        </view>
      </block>

      <view class="item" v-if="detail.refund_checkremark">
      	<text class="t1">审核备注</text>
      	<text class="t2 red">{{detail.refund_checkremark}}</text>
      </view>
		</view>
		<view style="width:100%;height:calc(160rpx + env(safe-area-inset-bottom));"></view>
  
    <view class="bottom notabbarbot" >
      <block v-if="detail.status==2 && detail.hexiao_qr">
      	<view class="btn2" @tap.stop="showhxqr" :data-hexiao_qr="detail.hexiao_qr">核销码</view>
      </block>
    	<block v-if="detail.status==0">
    		<view class="btn2" @tap="toclose" :data-id="detail.id">关闭订单</view>
    		<view class="btn1" v-if="detail.canpay" :style="{background:t('color1')}" @tap="goto" :data-url="'/pagesExt/pay/pay?id=' + detail.payorderid">去付款</view>
    	</block>

      <view v-if="detail.canrefund" class="btn2" @tap="goto" :data-url="'refund?orderid=' + detail.id">退款</view>
      <view v-if="detail.refundcount >0" class="btn2" @tap.stop="goto" :data-url="'refundlist?orderid='+ detail.id">售后详情</view>
    	<block v-if="([1,2,3]).includes(detail.status) && invoice">
    		<view class="btn2" @tap="goto" :data-url="'invoice?type=shop&orderid=' + detail.id">发票</view>
    	</block>
    </view>
    
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
      
      detail: "",
      ordergoods:'',
      djs: '',
      lefttime: "",
			pay_transfer_info:{},
			invoice:0,
      
      st2:0,
      showstatus2:[1,1,1],
      intoviewid:'',
      guize:'',
      
      hexiao_qr:'',
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
			app.get('ApiZhiyoubao/orderdetail', {id: that.opt.id}, function (res) {
				that.loading = false;
				if(res.status == 1){

					that.detail = res.detail;	
          that.ordergoods  = res.ordergoods;
					that.lefttime = res.lefttime;
					that.invoice = res.invoice;
					if (res.lefttime > 0) {
						interval = setInterval(function () {
							that.lefttime = that.lefttime - 1;
							that.getdjs();
						}, 1000);
					}

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
        app.post('ApiZhiyoubao/delOrder', {orderid: orderid}, function (data) {
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
			var msg = '确定要取消换座吗？';
      app.confirm(msg, function () {
				app.showLoading('提交中');
        app.post('ApiZhiyoubao/closeOrder', {orderid: orderid}, function (data) {
					app.showLoading(false);
          app.success(data.msg);
          setTimeout(function () {
            that.getdata();
          }, 1000);
        });
      });
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
	.text-min { font-size: 24rpx; color: #999;}
.ordertop{width:100%;height:220rpx;padding:50rpx 0 0 70rpx}
.ordertop .f1{color:#fff}
.ordertop .f1 .t1{font-size:32rpx;height:60rpx;line-height:60rpx}
.ordertop .f1 .t2{font-size:24rpx}

.product{width:94%;border-radius:24rpx;margin: 20rpx auto 0rpx;background: #FFF;padding: 20rpx;}
.product .tips-text{font-size: 24rpx;width: 100%;text-align: center;color: #abb7c7;padding: 5rpx 0rpx 20rpx;}
.product .box{width: 100%; border-radius:24rpx;margin-bottom: 15rpx;background: #f4f6fa;overflow: hidden;padding: 20rpx;}
.product .book-person-view{width: 100%;justify-content: space-between;}
.product .book-person-view .left-info-view{min-height:100rpx;justify-content: space-between;position: relative;padding: 10rpx 0;}
.product .book-person-view .left-topinfo{width: 100%;font-size: 28rpx;color: #000;}
.product .book-person-view .left-topinfo .name-view{min-width: 70rpx;width: auto;margin-right: 20rpx;}
.product .book-person-view .left-info-view .status-view{width: 100%;margin-top: 10rpx;}
.product .book-person-view .left-but-view{width: 130rpx;min-height:120rpx;justify-content: space-between;}
.but-class{font-size: 24rpx;border: 1px #e6e7e8 solid;border-radius: 8rpx;width: 130rpx;text-align: center;height: 54rpx;
line-height: 54rpx;background-color: #fff;color: #000;font-weight: bold;}
.no-but-class{background: #fefefe;color: #cfcfcf;}

/*  */
.product .content{display:flex;position:relative;}
.product .box:last-child{ border-bottom: 0; }
.product .content image{ width: 140rpx; height: 140rpx;}
.product .content .detail{display:flex;flex-direction:column;margin-left:14rpx;flex:1}

.product .content .detail .t1{font-size:26rpx;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:2;overflow:hidden;}
.product .content .detail .t2{color: #999;font-size: 26rpx;margin-top: 10rpx;}
.product .content .detail .t3{display:flex;color: #ff4246;margin-top: 10rpx;}
.product .content .detail .t4{margin-top: 10rpx;}
.product .content .detail .red{color: red;}

.product .content .detail .x1{ flex:1}
.product .content .detail .x2{ width:100rpx;font-size:32rpx;text-align:right;margin-right:8rpx}
.product .content .comment{position:absolute;top:64rpx;right:10rpx;border: 1px #ffc702 solid; border-radius:10rpx;background:#fff; color: #ffc702;  padding: 0 10rpx; height: 46rpx; line-height: 46rpx;}
.product .content .comment2{position:absolute;top:64rpx;right:10rpx;border: 1px #ffc7c2 solid; border-radius:10rpx;background:#fff; color: #ffc7c2;  padding: 0 10rpx; height: 46rpx; line-height: 46rpx;}

.orderinfo{width:94%;border-radius:24rpx;background: #FFF;padding: 20rpx;margin: 20rpx auto 0rpx;}
.orderinfo .item{display:flex;width:100%;padding:20rpx 0;border-bottom:1px dashed #f5f5f5;overflow:hidden;}
.orderinfo .item:last-child{ border-bottom: 0;}
.orderinfo .item .t1{width:120rpx;flex-shrink:0; text-align:justify;text-align-last:justify;font-size: 28rpx;color: #999;}

.orderinfo .item .t2{flex:1;text-align:right;color: #000;font-size: 28rpx;}
.orderinfo .item .t3{ margin-top: 3rpx;}
.orderinfo .item .red{color:red}

.order-info-title{align-items: center;}
.btn-class{height:45rpx;line-height:45rpx;color:#333;border-radius:6px;text-align:center;padding: 0 15rpx;font-size:24rpx;color: #fff;margin-left: 20rpx;}
.ordernum-info{align-items: center;}
.bottom{ width: 100%; height:calc(92rpx + env(safe-area-inset-bottom));background: #fff; position: fixed; bottom: 0px;left: 0px;display:flex;justify-content:flex-end;align-items:center;padding: 0 15rpx;}

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

.pdl10{padding-left: 10rpx;}

.radio-item {display: flex;width: 100%;color: #000;align-items: center;background: #fff;padding:20rpx 20rpx;border-bottom:1px dotted #f1f1f1}
.radio-item:last-child {border: 0}
.radio-item .f1 {color: #333;font-size:30rpx;flex: 1}
.storeviewmore{width:100%;text-align:center;color:#889;height:40rpx;line-height:40rpx;margin-top:10rpx}
.refundtips{background: #fff9ed; color: #ff5c5c;}
.refundtips textarea{font-size: 24rpx;line-height: 40rpx;width: 100%;height: auto; word-wrap : break-word;}

.layui-imgbox{margin-right:16rpx;margin-bottom:10rpx;font-size:24rpx;position: relative;}
.layui-imgbox-img{display: block;width:200rpx;height:200rpx;padding:2px;border: #d3d3d3 1px solid;background-color: #f6f6f6;overflow:hidden}
.layui-imgbox-img>image{max-width:100%;}
.layui-imgbox-repeat{position: absolute;display: block;width:32rpx;height:32rpx;line-height:28rpx;right: 2px;bottom:2px;color:#999;font-size:30rpx;background:#fff}

.popup__content{width: 100%;height:auto;position: relative;}
.popup__content .popup-close{position: fixed;right: 20rpx;top: 20rpx;width: 56rpx;height: 56rpx;z-index: 11;}
.popup__content .popup-close image{width: 100%;height: 100%;}
.popup_title{font-size: 36rpx;font-weight: bold;line-height: 80rpx;}
.popup-item-title{width: 180rpx;text-align: center;padding: 20rpx 10rpx;border-right: 2rpx solid #f1f1f1;}
.popup-item-content{width: 160rpx;padding: 20rpx  10rpx;width: 100%;color:#999}

.page-bg-view{width: 100%;height: 40vh;position: absolute;top: 0;left: 0;z-index: -1;}

.head{width: 94%;margin: 20rpx auto;background-color: #fff;padding: 20rpx;border-radius:20rpx ;line-height: 45rpx;}
.head-title{font-size: 36rpx;font-weight: bold;}
.head-title2{color: #333;margin: 10rpx 0;}

.content{background-color: #fff;border-radius: 8rpx;padding: 20rpx;margin-bottom: 20rpx;}
.content-pic{width: 200rpx;max-height: 400rpx;border-radius: 4rpx;overflow: hidden;}
.content-title{font-weight: bold;line-height: 50rpx;max-height: 100rpx;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:2;overflow:hidden;word-break: break-all;}
.content-title2{color: #666;line-height: 40rpx;margin-top: 5rpx;}
</style>