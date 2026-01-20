<template>
<view class="container">
	<block v-if="isload">
		<view class="ordertop" :style="'background:url(' + pre_url + '/static/img/ordertop.png);background-size:100%'">
			<view class="f1" v-if="detail.status==0">
				<view class="t1">等待买家付款</view>
				<view class="t2" v-if="djs">剩余时间：{{djs}}</view>
			</view>
			<view class="f1" v-if="detail.status==1">
				<view class="t2">已付款，等待商家确认</view>
			</view>
			<view class="f1" v-if="detail.status==2">
				<view class="t2">商家已确认，等待入住</view>
			</view>
			<view class="f1" v-if="detail.status==3">
				<view class="t1">已到店</view>
			</view>
			<view class="f1" v-if="detail.status==4">
				<view class="t1">已离店</view>
			</view>
			<view class="f1" v-if="detail.status==5">
				<view class="t1">订单已完成</view>
			</view>
			<view class="f1" v-if="detail.status==-1">
				<view class="t1">订单已关闭</view>
			</view>
		</view>


		<view class="product">
			<view  class="content">
				<view class="hotelpic">
					<image :src="hotel.pic"></image>
				</view>
				<view class="detail">
					<text class="t1">{{hotel.name}}</text>
					<text class="t2" >{{hotel.address}}</text>
				</view>
			</view>
			<view class="content" style=" width: 80%;margin: 0 auto;display: flex; justify-content: space-between;">
				<view class="item1"  @tap="openLocation" :data-latitude="hotel.latitude" :data-longitude="hotel.longitude" :data-company="hotel.name" :data-address="hotel.address">
					<image :src="pre_url+'/static/img/hotel/add2.png'"/><text>地图导航</text>
				</view>
				<view class="item1" @tap.stop="phone" :data-phone="hotel.tel">
					<image :src="pre_url+'/static/img/hotel/tel.png'"/><text>联系{{text['酒店']}}</text>
				</view>
			</view>
		</view>
		<view class="orderinfo1">
			<view class="item flex-bt">
				<view class="f1">
					<label class="t1">在线支付</label>
					<text class="price flex-bt">￥{{detail.totalprice}} </text>
				</view>
				<view class="cost-details flex flex-y-center"  @click="mignxiChange"  :style="{color:t('color1')}">
					费用明细
					<text class="iconfont iconjiantou" style="color:#999;font-weight:normal"></text>
				</view>
			</view>
			<!--<view class="item flex-bt">
				<view class="f1">
					<label class="t1">发票报销</label>
					<text class="t2">如需发票，请向{{text['酒店']}}前台索取</text>
				</view>
			</view>-->
		</view>
		
		<view class="orderinfo1">
			<view class="time-view flex flex-y-center flex-bt">
				<view class="time-options flex flex-y-center flex-bt">
					<view class="month-tetx">{{detail.in_date}}</view>
					
				</view>
				<view class="content-text">
					<view class="content-decorate left-c-d"></view>
					{{detail.daycount}}晚
					<view class="content-decorate right-c-d"></view>
				</view>
				<view class="time-options flex flex-y-center flex-bt">
					<view class="month-tetx">{{detail.leave_date}}</view>
				</view>
			</view>
				<view class="name-info flex flex-y-center flex-bt">
					<view class="flex flex-col">
						<view class="name-text">{{room.name}}</view>
						<view class="name-tisp">{{room.tag}}</view>
					</view>
					<view @click="showDetail" class="hotel-details-view flex flex-y-center" :style="{color:t('color1')}">
						查看房型	<image :src="pre_url+'/static/img/arrowdown.png'"></image>
					</view>
				</view>
				<view class="item">
					<view class="f1">
						<label class="t1">入住姓名</label>
						<text class="t2 flex-bt">{{detail.linkman}} </text>
					</view>
				</view>
				<view class="item">
					<view class="f1">
						<label class="t1">联系手机</label>
						<text class="t2 flex-bt">{{detail.tel}} </text>
					</view>
				</view>
				<view class="item" v-if="detail.message">
					<view class="f1">
						<label class="t1">预定备注</label>
						<text class="t2 flex-bt">{{detail.message}} </text>
					</view>
				</view>
	
		</view>
		
		<view class="orderinfo" v-if="detail.isbefore==1 && (detail.real_usemoney>0 ||  detail.real_roomprice>0)">
			<view class="title">实际支付</view>
			<view class="item flex-bt">
				<text class="t1">房费</text>
				<text class="t2 red" v-if="detail.real_usemoney>0 && detail.real_roomprice>0">{{detail.real_usemoney}}{{moneyunit}} + ￥{{detail.real_roomprice}}</text>
				<text class="t2 red" v-else-if="detail.real_usemoney>0 && detail.real_roomprice==0">{{detail.real_usemoney}}{{moneyunit}}</text>
				<text class="t2 red" v-else>￥{{detail.real_roomprice}}</text>
			</view>
			<view class="item flex-bt" v-if="detail.real_fuwu_money>0">
				<text class="t1">{{text['服务费']}}</text>
				<view class="ordernum-info flex-bt">
					<text class="t2 red" >￥{{detail.real_fuwu_money}}</text>
		
				</view>
			</view>
		</view>
		
		
		<view class="orderinfo" v-if="detail.yajin_money>0 && detail.status>2">
			<view class="title">押金信息</view>
			<view class="item flex-bt">
				<text class="t1">押金状态</text>
				<view class="ordernum-info flex-bt">
					<text class="t2" v-if="detail.yajin_refund_status==0">待申请</text>
					<text class="t2" v-if="detail.yajin_refund_status==1">审核中</text>
					<text class="t2" v-if="detail.yajin_refund_status==2">已退款</text>
					<text class="t2" v-if="detail.yajin_refund_status==-1">已驳回</text>
				</view>
			</view>
			<view class="item flex-bt" v-if="detail.yajin_refund_status==-1">
				<text class="t1">驳回原因</text>
				<view class="ordernum-info flex-bt">
					<text class="t2" >{{detail.yajin_refund_reason?detail.yajin_refund_reason:'无'}}</text>

				</view>
			</view>
		</view>
		
		<view class="orderinfo">
			<view class="title">订单信息</view>
			<view class="item flex-bt">
				<text class="t1">订单编号</text>
				<view class="ordernum-info flex-bt">
					<text class="t2" user-select="true" selectable="true">{{detail.ordernum}}</text>
					<view class="btn-class" style="margin-left: 20rpx;" @click="copy" :data-text='detail.ordernum'>复制</view>
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
			<view class="item" v-if="detail.status>0 && detail.paytime">
				<text class="t1">支付方式</text>
				<text class="t2">{{detail.paytype}}</text>
			</view>
			<view class="item" v-if="detail.daodian_time">
				<text class="t1">到店时间</text>
				<text class="t2">{{detail.daodian_time}}</text>
			</view>
			
			<view class="item" v-if="detail.real_leavedate">
				<text class="t1">离店日期</text>
				<text class="t2">{{detail.real_leavedate}}</text>
			</view>
			

			<view class="item">
				<text class="t1">订单状态</text>
				<text class="t2" v-if="detail.status==0">未付款</text>
				<text class="t2" v-if="detail.status==1">待确认</text>
				<text class="t2" v-if="detail.status==2">待入住</text>
				<text class="t2" v-if="detail.status==3">已到店</text>
				<text class="t2" v-if="detail.status==4">已离店</text>
				<text class="t2" v-if="detail.status==5">已完成</text>
				<text class="t2" v-if="detail.status==-1">已关闭</text>
			</view>
			<view class="item" v-if="detail.refund_status>0">
				<text class="t1">退款状态</text>
				<text class="t2 red" v-if="detail.refund_status==1">审核中,¥{{detail.refund_money}}</text>
				<text class="t2 red" v-if="detail.refund_status==2">已退款,¥{{detail.refund_money}}</text>
				<text class="t2 red" v-if="detail.refund_status==3">已驳回,¥{{detail.refund_money}}</text>
			</view>
			<view class="item" v-if="detail.refund_status>0">
				<text class="t1">退款原因</text>
				<text class="t2 red">{{detail.refund_reason}}</text>
			</view>
			<view class="item" v-if="detail.refund_checkremark">
				<text class="t1">审核备注</text>
				<text class="t2 red">{{detail.refund_checkremark}}</text>
			</view>
			
		</view>
		<view class="orderinfo" v-if="(detail.formdata).length > 0">
			<view class="item" v-for="item in detail.formdata" :key="index">
				<text class="t1">{{item[0]}}</text>
				<view class="t2" v-if="item[2]=='upload'"><image :src="item[1]" style="width:400rpx;height:auto" mode="widthFix" @tap="previewImage" :data-url="item[1]"/></view>
				<text class="t2" v-else user-select="true" selectable="true">{{item[1]}}</text>
			</view>
		</view>


		<view style="width:100%;height:calc(160rpx + env(safe-area-inset-bottom));"></view>

		<view class="bottom notabbarbot">
			<block v-if="detail.status==0">
				<view class="btn2" @tap="toclose" :data-id="detail.id">关闭订单</view>
				<view class="btn1" :style="{background:t('color1')}" @tap="goto" :data-url="'/pagesExt/pay/pay?id=' + detail.payorderid">去付款</view>
			</block>
			<block v-if="detail.status==1 && detail.isrefund==1">
					<view v-if="detail.refund_status==0 || detail.refund_status==3" class="btn2" @tap="goto" :data-url="'refund?id=' + detail.id + '&price=' + detail.totalprice">申请退款</view>
			</block>
			
			<block v-if="detail.status==2 && detail.isrefund==1">
				<block v-if="detail.paytypeid!='4'">
					<view v-if="detail.refund_status==0 || detail.refund_status==3" class="btn2" @tap="goto" :data-url="'refund?id=' + detail.id + '&price=' + detail.totalprice">申请退款</view>
				</block>
	
			</block>
			<block v-if="detail.status==2">
				<view class="btn2" @tap="showhxqr">核销码</view>
			</block>
			<block v-if="detail.status==-1">
				<view class="btn2" @tap="todel" :data-id="detail.id">删除订单</view>
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



				<!-- 弹窗 -->
				<uni-popup id="popup" ref="popup" type="bottom">
					<view class="hotelpopup__content">
						<view class="popup-close" @click="popupClose">
							<image :src="`${pre_url}/static/img/hotel/popupClose2.png`"></image>
						</view>
						<scroll-view scroll-y style="height: auto;max-height: 50vh;">
							<!-- 费用明细 -->
							<view class="hotel-equity-view flex flex-col">
								<view class="equity-title-view flex" style="padding-bottom: 40rpx;">
									<view class="equity-title">费用明细</view>
								</view>
								<view class="cost-details flex flex-col">
									<view class="price-view flex flex-bt flex-y-center" style="margin-bottom: 30rpx;">
										<view class="price-text-title">房费</view>
										<!--<view class="price-num-title">￥{{roomprice}}</view>-->
									</view>
									<!--<view class="price-view flex flex-bt flex-y-center" v-for="(item,index) in roomprices">
										<view class="price-text">{{item.datetime}} </view>
										<view class="price-num">￥{{item.sell_price}}</view>
									</view>-->
									<view class="price-view flex flex-bt flex-y-center">
										<view class="price-text">优惠券抵扣</view>
										<view class="price-num">￥{{detail.coupon_money}}</view>
									</view>
									<view class="price-view flex flex-bt flex-y-center">
										<view class="price-text">{{t('余额')}}抵扣</view>
										<view class="price-num">{{detail.use_money?detail.use_money:0}}{{moneyunit}}</view>
									</view>
									<view class="price-view flex flex-bt flex-y-center">
										<view class="price-text">现金支付</view>
										<view class="price-num">￥{{detail.leftmoney}}</view>
									</view>
								</view>
								<view class="cost-details flex flex-col">
									<view class="price-view flex flex-bt flex-y-center" style="margin-bottom: 30rpx;">
										<view class="price-text-title">其他</view>
										<view class="price-num-title"></view>
									</view>
									<view class="price-view flex flex-bt flex-y-center" v-if="detail.yajin_money>0">
										<view class="price-text">押金(可退)</view>
										<view class="price-num">￥{{detail.yajin_money}}</view>
									</view>
									<view class="price-view flex flex-bt flex-y-center" v-if="detail.fuwu_money>0">
										<view class="price-text">{{text['服务费']}}</view>
										<view class="price-num">￥{{detail.fuwu_money}}</view>
									</view>
								</view>
								<view class="cost-details flex flex-col" v-if="detail.couponmoney>0 || detail.scoredk_money>0 || (detail.upgradescoredk_money && detail.upgradescoredk_money>0)">
									<view class="price-view flex flex-bt flex-y-center" style="margin-bottom: 30rpx;">
										<view class="price-text-title">优惠</view>
										<view class="price-num-title"></view>
									</view>
									<view class="price-view flex flex-bt flex-y-center" v-if="detail.couponmoney>0" >
										<view class="price-text">优惠券抵扣</view>
										<view class="price-num">-￥{{detail.couponmoney}}</view>
									</view>
									<view class="price-view flex flex-bt flex-y-center" v-if="detail.scoredk_money>0" >
										<view class="price-text">{{t('积分')}}抵扣</view>
										<view class="price-num">-￥{{detail.scoredk_money}}</view>
									</view>
									<view class="price-view flex flex-bt flex-y-center" v-if="detail.upgradescoredk_money>0" >
										<view class="price-text">{{text['升级积分']}}抵扣</view>
										<view class="price-num">-￥{{detail.upgradescoredk_money}}</view>
									</view>
								</view>
							</view>
						</scroll-view>
					</view>
				</uni-popup>
				
				<!-- 详情弹窗 -->
				<uni-popup id="popupDetail" ref="popupDetail" type="bottom">
					<view class="hotelpopup__content">
						<view class="popup-close" @click="popupdetailClose">
							<image :src="`${pre_url}/static/img/hotel/popupClose.png`"></image>
						</view>
						<scroll-view scroll-y style="height: auto;max-height: 70vh;">
							<view class="popup-banner-view" style="height: 450rpx;">
								<swiper class="dp-banner-swiper" :autoplay="true" :indicator-dots="false" :current="0" :circular="true" :interval="3000" @change='swiperChange'>
									<block v-for="(item,index) in room.pics" :key="index"> 
										<swiper-item>
											<view @click="viewPicture(item)">
												<image :src="item" class="dp-banner-swiper-img" mode="widthFix"/>
											</view>
										</swiper-item>
									</block>
								</swiper>
								<view class="popup-numstatistics flex flex-xy-center" v-if='room.pics.length'>
									{{bannerindex}} / {{room.pics.length}}
								</view>
							</view>
							<view class="hotel-details-view flex flex-col">
								<view class="hotel-title">{{room.name}}</view>
								<view class="introduce-view flex ">
									<view class="options-intro flex flex-y-center"  v-if="room.bedxing!='不显示'">
										<image :src="pre_url+'/static/img/hotel/dachuang.png'"></image>
										<view class="options-title">{{room.bedxing}}</view>
									</view>
									<view class="options-intro flex flex-y-center">
										<image :src="pre_url+'/static/img/hotel/pingfang.png'"></image>
										<view class="options-title">{{room.square}}m²</view>
									</view>
									<view class="options-intro flex flex-y-center">
										<image :src="pre_url+'/static/img/hotel/dachuang.png'"></image>
										<view class="options-title">{{room.bedwidth}}米</view>
									</view>
					
									<view class="options-intro flex flex-y-center" v-if="room.ischuanghu!='0'">
										<image :src="pre_url+'/static/img/hotel/youchuang.png'"></image>
										<view class="options-title">{{room.ischuanghu}}</view>
									</view>
								
									<view class="options-intro flex flex-y-center" v-if="room.breakfast!='不显示'">
										<image :src="pre_url+'/static/img/hotel/zaocan.png'"></image>
										<view class="options-title">{{room.breakfast}}早餐</view>
									</view>
								</view>
								<view class="other-view flex flex-y-center">
									<view class="other-title">特色</view>
									<view class="other-text" style="white-space: pre-line;">{{room.tese}}</view>
								</view>
							</view>
							<!-- 酒店权益 -->
							<view class="hotel-equity-view flex flex-col" v-if="qystatus == 1">
								<view class="equity-title-view flex">
									<view class="equity-title">{{ qyname }}</view>
								  <!--<view class="equity-title-tisp">填写订单时兑换</view>-->
								</view>			
								<view class="equity-options flex flex-col">
										<parse :content="hotel.hotelquanyi" @navigate="navigate"></parse>
								</view>
							</view>
							<!-- 政策服务 -->
							<view class="hotel-equity-view flex flex-col"   v-if="fwstatus == 1">
								<view class="equity-title-view flex">
									<view class="equity-title">{{ fwname }}</view>
								</view>
								<view class="equity-options flex flex-col">
										<parse :content="hotel.hotelfuwu" @navigate="navigate"></parse>
								</view>
							</view>
						</scroll-view>
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
      prodata: '',
      djs: '',
      detail: "",
      storeinfo: "",
      lefttime: "",
      codtxt: "",
			hotel:[],
			room:[],
			roomprices:[],
			roomprice:0,
			text:[],
			bannerindex:1,
			qystatus:0,
				fwstatus:0,
				qyname:'',
				fwname:'',
				moneyunit:'元'
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
			app.get('ApiHotel/orderdetail', {id: that.opt.id}, function (res) {
				uni.stopPullDownRefresh();
				that.loading = false;
        if(res.status == 1){
          that.detail = res.detail;
          that.hotel = res.storeinfo;
          that.lefttime = res.lefttime;
          that.codtxt = res.codtxt;
					that.room = res.room
          that.isload = 1;
					that.roomprice =res.totalroomprice
					that.roomprices = res.roomprices
					that.text = res.text
					that.qystatus = res.storeinfo.qystatus
					that.fwstatus = res.storeinfo.fwstatus
					that.qyname = res.storeinfo.qyname
					that.moneyunit = res.moneyunit
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
		mignxiChange(){
			this.$refs.popup.open();
		},
		popupClose(){
			this.$refs.popup.close();
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
        app.post('ApiHotel/delOrder', {orderid: orderid}, function (data) {
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
        app.post('ApiHotel/closeOrder', {orderid: orderid}, function (data) {
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
		},
		openMendian: function(e) {
			var storeinfo = e.currentTarget.dataset.storeinfo;
			app.goto('/pages/shop/mendian?id=' + storeinfo.id);
		},
		openLocation:function(e){
			var latitude = parseFloat(e.currentTarget.dataset.latitude);
			var longitude = parseFloat(e.currentTarget.dataset.longitude);
			var address = e.currentTarget.dataset.address;
			uni.openLocation({
			 latitude:latitude,
			 longitude:longitude,
			 name:address,
			 scale: 13
			})
		},
		swiperChange(event){
				this.bannerindex = event.detail.current+1;
			},
		phone:function(e) {
			var phone = e.currentTarget.dataset.phone;
			uni.makePhoneCall({
				phoneNumber: phone,
				fail: function () {
				}
			});
		},
		doStoreShowAll:function(){
			this.storeshowall = true;
		},
		showDetail:function(e){
				// 房型详情-------------------------------------------------------------------------------------------
				this.$refs.popupDetail.open();
		},
		popupdetailClose(){
			this.$refs.popupDetail.close();
		},
  }
};
</script>
<style>
.ordertop{width:100%;height:220rpx;padding:50rpx 0 0 70rpx}
.ordertop .f1{color:#fff}
.ordertop .f1 .t1{font-size:32rpx;height:60rpx;line-height:60rpx}
.ordertop .f1 .t2{font-size:24rpx}

.product{width:94%;margin:0 3%;border-radius:8rpx;margin-top:16rpx;padding: 14rpx 3%;background: #FFF;}
.product .content{display:flex;position:relative;width: 100%; padding:16rpx 0px;border-bottom: 1px #e5e5e5 dashed;}
.product .content:last-child{ border-bottom: 0; }
.product .content .hotelpic image{ width: 120rpx; height: 100rpx;}
.product .content .item1{ display: flex; align-items: center;}
.product .content .item1 image{ width: 40rpx; height:40rpx}

.product .content .detail{display:flex;flex-direction:column;margin-left:14rpx;flex:1}
.product .content .detail .t1{font-size:28rpx;line-height:36rpx;margin-bottom:10rpx;color: #000;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:2;overflow:hidden;}
.product .content .detail .t2{height: 46rpx;line-height: 46rpx;color: #999;overflow: hidden;font-size: 26rpx;}
.product .content .detail .t3{display:flex;height:40rpx;line-height:40rpx;color: #ff4246;}
.product .content .detail .x1{ flex:1}
.product .content .detail .x2{ width:100rpx;font-size:32rpx;text-align:right;margin-right:8rpx}
.product .content .comment{position:absolute;top:64rpx;right:10rpx;border: 1px #ffc702 solid; border-radius:10rpx;background:#fff; color: #ffc702;  padding: 0 10rpx; height: 46rpx; line-height: 46rpx;}
.product .content .comment2{position:absolute;top:64rpx;right:10rpx;border: 1px #ffc7c2 solid; border-radius:10rpx;background:#fff; color: #ffc7c2;  padding: 0 10rpx; height: 46rpx; line-height: 46rpx;}


.orderinfo1{width:94%;margin:0 3%;border-radius:8rpx;margin-top:16rpx;padding: 14rpx 3%;background: #FFF;}
.orderinfo1 .item { padding:20rpx 0}
.orderinfo1 .item .f1{ display: flex; font-size: 24rpx; }
.orderinfo1 .item .f1 .t1{ color: #999; width: 150rpx;}
.orderinfo1 .item .f1 .price{ color: red; }
.orderinfo1 .cost-details{color: #06D470;font-size: 24rpx;font-weight: bold;}
.orderinfo1 .cost-details image{width:24rpx;height: 24rpx;transform: rotate(90deg);margin: 0rpx 20rpx 0rpx 10rpx;}
/*入住时间样式*/
.orderinfo1 .time-view{width: 100%;padding-top: 10rpx;}
.orderinfo1 .time-view .time-options{}
.orderinfo1 .time-view .time-options .month-tetx{color: #1E1A33;font-size: 28rpx;font-weight: bold;}
.orderinfo1 .time-view .content-text{width: 46px;height: 40rpx;line-height: 39rpx;text-align: center;border-radius: 20px;color: #000;font-size: 20rpx;position: relative;border: 1px #000 solid;}
.orderinfo1 .time-view .content-text .content-decorate{width: 13rpx;height: 2rpx;background: red;position: absolute;top: 50%;}
.orderinfo1 .time-view .content-text .left-c-d{left: -13rpx;background: #000;}
.orderinfo1 .time-view .content-text .right-c-d{right: -13rpx;background: #000;}
.orderinfo1 .name-info{width: 100%;padding: 30rpx 0rpx;}
.orderinfo1 .name-info .name-text {color: #1E1A33;font-size: 30rpx;font-weight: bold;}
.orderinfo1 .name-info .name-tisp{color: #A5A3AD;font-size: 24rpx;margin-top: 15rpx;}
.orderinfo1 .hotel-details-view image{width: 22rpx;height: 22rpx;margin-left: 10rpx;}


.orderinfo{width:94%;margin:0 3%;border-radius:8rpx;margin-top:16rpx;padding: 14rpx 3%;background: #FFF;}
.orderinfo .title{ font-size: 28rpx; font-weight: bold; margin-bottom: 20rpx;}
.orderinfo .item{display:flex;width:100%;padding:20rpx 0;border-bottom:1px dashed #ededed;}
.orderinfo .item:last-child{ border-bottom: 0;}
.orderinfo .item .t1{width:200rpx; font-size: 24rpx;}
.orderinfo .item .t2{flex:1;text-align:right; font-size: 24rpx;}
.orderinfo .item .red{color:red}
.order-info-title{align-items: center;}
.btn-class{height:45rpx;line-height:45rpx;color:#333;background:#fff;border:1px solid #cdcdcd;border-radius:3px;text-align:center;padding: 0 15rpx;flex-shrink: 0;margin: 0 0 0 10rpx;font-size:24rpx;}
.ordernum-info{align-items: center;}

.bottom{ width: 100%;height:calc(92rpx + env(safe-area-inset-bottom));padding: 0 20rpx;background: #fff; position: fixed; bottom: 0px;left: 0px;display:flex;justify-content:flex-end;align-items:center;}

.btn1{margin-left:20rpx;width:160rpx;height:60rpx;line-height:60rpx;color:#fff;border-radius:3px;text-align:center}
.btn2{margin-left:20rpx;width:160rpx;height:60rpx;line-height:60rpx;color:#333;background:#fff;border:1px solid #cdcdcd;border-radius:3px;text-align:center}
.btn3{position:absolute;top:60rpx;right:10rpx;font-size:24rpx;width:120rpx;height:50rpx;line-height:50rpx;color:#333;background:#fff;border:1px solid #cdcdcd;border-radius:3px;text-align:center}

.btitle{ width:100%;height:100rpx;background:#fff;padding:0 20rpx;border-bottom:1px solid #f5f5f5}
.btitle .comment{border: 1px #ffc702 solid;border-radius:10rpx;background:#fff; color: #ffc702;  padding: 0 10rpx; height: 46rpx; line-height: 46rpx;}
.btitle .comment2{border: 1px #ffc7c0 solid;border-radius:10rpx;background:#fff; color: #ffc7c0;  padding: 0 10rpx; height: 46rpx; line-height: 46rpx;}

.hxqrbox{background:#fff;padding:50rpx;position:relative;border-radius:20rpx}
.hxqrbox .img{width:400rpx;height:400rpx}
.hxqrbox .txt{color:#666;margin-top:20rpx;font-size:26rpx;text-align:center}
.hxqrbox .close{width:50rpx;height:50rpx;position:absolute;bottom:-100rpx;left:50%;margin-left:-25rpx;border:1px solid rgba(255,255,255,0.5);border-radius:50%;padding:8rpx}

.radio-item {display: flex;width: 100%;color: #000;align-items: center;background: #fff;padding:20rpx 20rpx;border-bottom:1px dotted #f1f1f1}
.radio-item:last-child {border: 0}
.radio-item .f1 {color: #333;font-size:30rpx;flex: 1}
.storeviewmore{width:100%;text-align:center;color:#889;height:40rpx;line-height:40rpx;margin-top:10rpx}


	
	/* 费用明细 */
.hotelpopup__content{width: 100%;height:auto;position: relative;}
.hotelpopup__content .popup-close{position: fixed;right: 20rpx;top: 20rpx;width: 56rpx;height: 56rpx;z-index: 11;}
.hotelpopup__content .popup-close image{width: 100%;height: 100%;}
.hotelpopup__content .hotel-equity-view{width: 100%;padding:30rpx 40rpx 40rpx;background: #fff;margin-top: 20rpx;}
.hotelpopup__content .hotel-equity-view{width: 100%;padding:30rpx 40rpx 40rpx;background: #fff;}
.hotel-equity-view .equity-title-view{align-items: center;justify-content: flex-start; padding: 20rpx 0;}
.hotel-equity-view .equity-title-view .equity-title{color: #1E1A33;font-size: 32rpx;font-weight: bold;}
.hotel-equity-view .equity-title-view .equity-title-tisp{color: #A5A3AD;font-size: 24rpx;margin-left: 28rpx;}
.hotel-equity-view .equity-options{margin-top: 40rpx;}
.hotel-equity-view .equity-options .options-title-view{align-items: center;justify-content: flex-start;}
.hotel-equity-view .equity-options .options-title-view image{width: 28rpx;height: 28rpx;margin-right: 20rpx;}
.hotel-equity-view .equity-options .options-title-view  .title-text{color: #1E1A33;font-size: 28rpx;font-weight: bold;}
.hotel-equity-view .equity-options .options-text{color: rgba(30, 26, 51, 0.8);font-size: 24rpx;padding: 15rpx 0rpx;line-height: 40rpx;margin-left: 50rpx;margin-right: 50rpx;}

.hotel-equity-view  .cost-details{width: 100%;padding-bottom: 30rpx;border-bottom: 1px #efefef solid;margin-top: 40rpx;}
.hotel-equity-view  .cost-details .price-view{padding-bottom: 10rpx;}
.hotel-equity-view  .cost-details .price-view .price-text{color: rgba(30, 26, 51, 0.8);font-size: 24rpx;}
.hotel-equity-view  .cost-details .price-view .price-num{color: #1E1A33;font-size: 24rpx;}
.hotel-equity-view  .cost-details .price-view .price-text-title{color: rgba(30, 26, 51, 0.8);font-size: 30rpx;font-weight: bold;}
.hotel-equity-view  .cost-details .price-view .price-num-title{color: #1E1A33;font-size: 30rpx;font-weight: bold;}
	/*  */
	/* 房型详情 */
	.dp-banner{width: 100%;height: 250px;}
	.dp-banner-swiper{width:100%;height:100%;}
	.dp-banner-swiper-img{width:100%;height:auto}
	.hotelpopup__content .popup-banner-view{width: 100%;height: 500rpx;position: relative;}
	.hotelpopup__content .popup-banner-view .popup-numstatistics{position: absolute;right: 20rpx;bottom: 20rpx;background: rgba(0, 0, 0, 0.3);
	border-radius: 28px;width: 64px;height: 28px;text-align: center;line-height: 28px;color: #fff;font-size: 20rpx;}
	.hotelpopup__content .hotel-details-view{width: 100%;padding: 30rpx 40rpx;background: #fff;}
	.hotelpopup__content .hotel-details-view	.hotel-title{color: #1E1A33;font-size: 40rpx;}
	.hotelpopup__content .hotel-details-view	.introduce-view{width: 100%;align-items: center;flex-wrap: wrap;justify-content: flex-start;padding: 20rpx 10rpx;}
	.hotelpopup__content .hotel-details-view	.introduce-view .options-intro{padding: 15rpx 0rpx;margin-right: 20rpx;width: auto;}
	.hotel-details-view	.introduce-view .options-intro image{width: 32rpx;height: 32rpx;}
	.hotel-details-view	.introduce-view .options-intro .options-title{color: #1E1A33;font-size: 24rpx;margin-left: 15rpx;}
	.hotel-details-view .other-view{width: 100%;justify-content: flex-start;padding: 12rpx 0rpx;}
	.hotel-details-view .other-view .other-title{color: #A5A3AD;font-size: 24rpx;margin-right: 40rpx;}
	.hotel-details-view .other-view .other-text{color: #1E1A33;font-size: 24rpx;}	
	
</style>