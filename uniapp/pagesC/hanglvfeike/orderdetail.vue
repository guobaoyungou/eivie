<template>
<view class="container">
	<block v-if="isload">
		<view class="page-bg-view" :style="{backgroundImage:`linear-gradient(${t('color1')} 30%,transparent)`}"></view>

    <view v-if="detail.status==0" class="head">
      <view class="head-title">等待付款</view>
      <block v-if="detail.ischange &&detail.ischange == 1">
        <view class="head-title2">改签通过未付款的订单会失效或被关闭，请尽快付款</view>
      </block>
      <block v-else>
        <view class="head-title2">未付款订单会失效或被关闭，请尽快付款</view>
      </block>
      <view class="head-title2" v-if="djs">剩余时间：<text style="color: red;">{{djs}}</text></view>
    </view>

		<!-- 航班信息 -->
		<view class="hangban-info-view flex flex-y-center">
			<view class="logo-view">
				<image v-if="detail.flightdata && detail.flightdata.airlinePic" :src="pre_url+'/static/img/planeticket/jipaiologo.png'"></image>
        <image v-else :src="pre_url+'/static/img/planeticket/jipaiologo.png'"></image>
			</view>
			<view class="flex-col">
				<view class="hangban-name">{{detail.airlineName}}{{detail.flightNo}}</view>
				<view class="hangban-time">{{detail.fromDate}} {{detail.week}}</view>
			</view>
		</view>
		<!-- 航班信息 -->
    <view class="hangban-top-view" v-if="detail.flightdata">
      <view class="address-info-view flex flex-y-center">
          <view class="address-name-view flex-col flex-xy-center">
						<view class="depart-name">{{detail.flightdata.deparAirportName}}{{detail.flightdata.departTerminal}}</view>
            <view class="depart-time">{{detail.flightdata.departTime}}</view>
          </view>
          <view class="location-icon flex-col flex-y-center">
            <view class="stop-tag" v-if="detail.flightdata.stopname">停</view>
            <image :src="pre_url+'/static/img/planeticket/info_icon.png'" mode="widthFix"></image>
            <view v-if="detail.flightdata.stopname"  style="font-size: 24rpx;text-align: center;color: #676767;">{{detail.flightdata.stopname}}</view>
						<view class="travel-time">{{detail.flightdata.flightTime}}</view>
          </view>
          <view class="address-name-view flex-col flex-xy-center">
						<view class="depart-name">{{detail.flightdata.arriveAirportName}}{{detail.flightdata.arriveTerminal}}</view>
            <view class="depart-time">{{detail.flightdata.arriveTime}}</view>
          </view>
      </view>
			<view class="divider-view"></view>
      <view class="supplement-view flex">
          <view>
            {{detail.flightdata.planeCName}}<text v-if="detail.planeTypeName"> ({{detail.flightdata.planeTypeName}})</text> 
          </view>
					<view v-if="detail.flightdata.mealsname" style="padding: 0rpx 15rpx;">|</view>
          <view v-if="detail.flightdata.mealsname">{{detail.flightdata.mealsname}}</view>
          <view v-if="(!detail.ischange && detail.status>=0 && detail.status<=3 )|| (detail.ischange && detail.status>0 && detail.status<=3 )" @tap="getguize" style="width:160rpx ;display: flex;align-items: center;">
            <text  style="padding: 0rpx 15rpx;">|</text><text :style="{color:t('color1')}">退改规则</text><image :src="pre_url+'/static/img/arrowright.png'" style="width: 28rpx;height: 28rpx;"></image>
          </view>
      </view>
    </view>
		<!-- 订票人信息 -->
    <view v-if="ordergoods" class="product">
      <view v-if="detail.tipmsg" class="tips-text">{{detail.tipmsg}}</view>
      <view v-for="(item, idx) in ordergoods" :key="idx" class="box">
				<view class="book-person-view flex flex-y-center">
					<view class="left-info-view flex-col ">
						<view class="left-topinfo flex flex-y-center">
							<view class="name-view">{{item.name}}</view>
							<view>{{item.cardNo}}</view>
						</view>
            <view v-if="item.status==4" class="status-view flex flex-y-center">
              <view style="color: #bbb;">已关闭</view>
            </view>
						<view  class="status-view flex flex-y-center" style="justify-content: space-between;">
              <view v-if="item.refund_status!=0" style="display: flex;margin-right: 20rpx;">
                <view style="color: #8D8D8D;font-weight: bold;">退款状态：</view>
                <text  v-if="item.refund_status==1" style="color: #ff8758;">审核中</text>
                <text  v-if="item.refund_status==2" style="color: green;">已审核</text>
                <text  v-if="item.refund_status==3" style="color: red;">已退款</text>
                <text  v-if="item.refund_status==4"  style="color: #bbb;">已驳回</text>
              </view>
              
              <view v-if="item.change_status!=0" style="display: flex;">
                <text style="color: #8D8D8D;font-weight: bold;">改签状态：</text>
                <text v-if="item.change_status==1" style="color: #ff8758;">待审核</text>
                <text v-if="item.change_status==2" style="color: green;">已审核</text>
                <text v-if="item.change_status==3" style="color: #999;">已完成</text>
                <text v-if="item.change_status==-1" style="color: #bbb;">已取消</text>
                <text v-if="item.change_status==-2" style="color: #bbb;">已驳回</text>
              </view>
						</view>

            <view v-if="item.totalprice>=0" class="status-view">
              <view>
                <text style="color: #8D8D8D;font-weight: bold;">费用：</text>
                <text style="color: red;">￥{{item.totalprice}}</text>
              </view>
              <view style="color: #333;margin-top: 10rpx;">
                <block v-if="!detail.ischange">
                  <view v-if="item.taxFee>0 || item.fuelFee>0 || item.serveprice>0" class="t1" style="display: flex;flex-wrap: wrap;font-size: 26rpx;margin-top: 10rpx;">
                    (<view v-if="item.price>0" style="margin-right: 10rpx;">票价<text style="color: red;">￥{{item.price}}</text></view>
                    <view v-if="item.taxFee>0" style="margin-right: 10rpx;">+ 机建费<text style="color: red;">￥{{item.taxFee}}</text></view>
                    <view v-if="item.fuelFee>0" style="margin-right: 10rpx;">+ 燃油费<text style="color: red;">￥{{item.fuelFee}}</text></view>
                    <view v-if="item.serveprice>0">+ 服务费<text style="color: red;">￥{{item.serveprice}}</text></view>)
                  </view>
                </block>
                <block v-else>
                  <view v-if="item.upgradeFee>0 || item.changeFee>0 || item.serveprice>0" class="t1" style="display: flex;flex-wrap: wrap;font-size: 26rpx;margin-top: 10rpx;">
                    (<view v-if="item.upgradeFee>0" style="margin-right: 10rpx;">+ 升舱费<text style="color: red;">￥{{item.upgradeFee}}</text></view>
                    <view v-if="item.changeFee>0" style="margin-right: 10rpx;">+ 手续费<text style="color: red;">￥{{item.changeFee}}</text></view>
                    <view v-if="item.serveprice>0" >+ 服务费<text style="color: red;">￥{{item.serveprice}}</text></view>)
                  </view>
                </block>
              </view>
            </view>
					</view>
				</view>
        <!-- <view style="display: flex;justify-content: flex-end;">
        	<view class='but-class' :style="{color:t('color1')}" v-if="false">取消退款</view>
        	<view class='but-class' :style="{color:t('color1')}">取消改签</view>
        
          <view v-if="!item.canchange" class='but-class no-but-class'>改签</view>
          <view v-if="!item.canrefund" class='but-class no-but-class'>退款</view>

          <view v-if="item.canchange" class='but-class'>改签</view>
          <view v-if="item.canrefund" class='but-class'>退款</view>
        </view> -->
      </view>
    </view>

		<view class="orderinfo">
      <view class="item">
      	<text class="t1">机票类型</text>
      	<text class="t2">
          <block v-if="!detail.ischange">
            普通机票
          </block>
          <block v-else>
            改签机票
          </block>
        </text>
      </view>
      <view class="item">
      	<text class="t1">联系人</text>
      	<text class="t2">{{detail.contacts}}</text>
      </view>
      <view class="item">
      	<text class="t1">联系电话</text>
      	<text class="t2">{{detail.mobile}}</text>
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
        <text v-if="detail.ischange && detail.status == 0 && detail.change_status==1 " class="t2 red">审核中</text>
        <text v-else class="t2 red">￥{{detail.totalprice}}  </text>
			</view>
			<view class="item">
				<text class="t1">订单状态</text>
				<text class="t2" v-if="detail.status==0">未付款</text>
				<text class="t2" v-if="detail.status==1">待出票</text>
				<text class="t2" v-if="detail.status==2">已出票</text>
				<text class="t2" v-if="detail.status==4">已关闭</text>
			</view>
      <block v-if="detail.refund_money>0">
        <view class="item" >
        	<view class="t1">退款金额</view>
        	<view class="t2 red">
            已退款,¥{{detail.refund_money}}
          </view>
        </view>
      </block>
      
      <block v-if="detail.change_status!=0">
        <view class="item" >
          <view class="t1">改签状态</view>
          <view v-if="detail.change_status==1" class="t2" style="color: #ff8758;">改签待审核</view>
          <view v-if="detail.change_status==2" class="t2" style="color: green;">改签已审核</view>
          <view v-if="detail.change_status==3" class="t2" style="color: #999;">改签已完成</view>
          <view v-if="detail.change_status==-1" class="t2" style="color: #bbb;">改签已取消</view>
          <view v-if="detail.change_status==-2" class="t2" style="color: #bbb;">改签已驳回</view>
        </view>
      </block>
			
      <view class="item" v-if="detail.refund_checkremark">
      	<text class="t1">审核备注</text>
      	<text class="t2 red">{{detail.refund_checkremark}}</text>
      </view>
		</view>
		<view style="width:100%;height:calc(160rpx + env(safe-area-inset-bottom));"></view>
  
    <view class="bottom notabbarbot" >
      <view v-if="linktel" @tap="callphone" :data-phone="linktel" class="btn2">联系客服</view>
    	<block v-if="detail.status==0">
    		<view class="btn2" @tap="toclose" :data-id="detail.id" :data-ischange="detail.ischange">
					<text v-if="detail.ischange">取消改签</text>
					<text v-else>关闭订单</text>
				</view>
    		<view class="btn1" v-if="detail.canpay" :style="{background:t('color1')}" @tap="goto" :data-url="'/pagesExt/pay/pay?id=' + detail.payorderid">去付款</view>
    	</block>
      <view v-if="detail.canchange" class="btn2" @tap="goto" :data-url="'change?orderid=' + detail.id">改签</view>
    	
      <view v-if="detail.canrefund" class="btn2" @tap="goto" :data-url="'refund?orderid=' + detail.id">退款</view>
      <view v-if="detail.refundcount >0" class="btn2" @tap.stop="goto" :data-url="'refundlist?orderid='+ detail.id">售后详情</view>
    	<block v-if="([1,2,3]).includes(detail.status) && invoice">
    		<view class="btn2" @tap="goto" :data-url="'invoice?type=shop&orderid=' + detail.id">发票</view>
    	</block>
      <view v-if="detail.ischange && detail.showold && detail.change_oldorderid>0" class="btn2" @tap.stop="goto" :data-url="'orderlist?orderid='+ detail.change_oldorderid">改签前订单</view>
      <view v-if="detail.ischange && detail.showoriginal && detail.change_original_orderid>0" class="btn2" @tap.stop="goto" :data-url="'orderlist?orderid='+ detail.change_original_orderid">最初订单</view>
    </view>
	</block>
  
  <!-- 详情弹窗 -->
  <uni-popup id="popup" ref="popup" type="bottom" >
  	<view class="popup__content" style="bottom: 0;padding-top:0;padding-bottom:0; max-height: 86vh;background-color: #fff;border-radius: 16rpx 16rpx;overflow: hidden;">
  		<!-- <view class="popup-close" @click="popupdetailClose">
  			<image :src="`${pre_url}/static/img/hotel/popupClose.png`"></image>
  		</view> -->
      <dd-tab :itemdata="['产品说明','行李规定','退改规则']" :itemst="['0','1','2']" :st="st2" :showstatus="showstatus2" :isfixed="true" @changetab="changetab2"></dd-tab>
      <view style="width:100%;height:100rpx"></view>
  		<scroll-view :scrollIntoView="intoviewid" :scrollWithAnimation="true" scroll-y style="height: auto;max-height:calc(86vh - 130rpx);;">
        <view v-if="guize" style="padding:0 20rpx;">
          <view id="scrollid0" class="popup_title">产品说明</view>
          <view style="border: 2rpx solid #f1f1f1;border-radius: 8rpx 8rpx;">
              <view :style="'padding: 20rpx;background:rgba('+t('color1rgb')+',0.4)'">票价/其他说明</view>
              <view v-if="guize.baoxiaocontent" style="border-top: 2rpx solid #f1f1f1;">
                <view style="display: flex;align-items: center;">
                  <view class="popup-item-title" >
                    报销凭证
                  </view>
                  <view class="popup-item-content">
                    {{guize.baoxiaocontent}}
                  </view>
                </view>
              </view>
              <view v-if=" guize.pricecontent" style="border-top: 2rpx solid #f1f1f1;">
                <view style="display: flex;align-items: center;">
                  <view class="popup-item-title" >
                    价格说明
                  </view>
                  <view class="popup-item-content">
                    {{guize.pricecontent}}
                  </view>
                </view>
              </view>
          </view>
          
          <view id="scrollid1" class="popup_title">行李规定</view>
          <view style="border: 2rpx solid #f1f1f1;border-radius: 8rpx 8rpx;">
              <view :style="'padding: 20rpx;background:rgba('+t('color1rgb')+',0.4)'">托运/手提行李</view>
  
              <view v-if="guize.checkedluggage" style="display: flex;align-items: center;border-top: 2rpx solid #f1f1f1;">
                <view class="popup-item-title" >
                  托运行李
                </view>
                <view class="popup-item-content">
                  {{guize.checkedluggage}}
                </view>
              </view>
  
              <view v-if="guize.cabinluggage" style="display: flex;align-items: center;border-top: 2rpx solid #f1f1f1;">
                <view class="popup-item-title" >
                  手提行李
                </view>
                <view class="popup-item-content">
                  {{guize.cabinluggage}}
                </view>
              </view>
  
              <view v-if="guize.infantluggage" style="display: flex;align-items: center;border-top: 2rpx solid #f1f1f1;">
                <view class="popup-item-title" >
                  婴儿行李
                </view>
                <view class="popup-item-content">
                  {{guize.infantluggage}}
                </view>
              </view>
              
              <view v-if="guize.luggage" style="display: flex;align-items: center;border-top: 2rpx solid #f1f1f1;">
                <view class="popup-item-title" >
                  其他说明
                </view>
                <view class="popup-item-content">
                  {{guize.luggage}}
                </view>
              </view>
          </view>
          
          <view id="scrollid2" class="popup_title">退改规则</view>
          <view style="border: 2rpx solid #f1f1f1;border-radius: 8rpx 8rpx;">
              <view :style="'padding: 20rpx;background:rgba('+t('color1rgb')+',0.4)'">退改费用/规则</view>
  
              <view v-if="guize.refundStipulate" style="display: flex;align-items: center;border-top: 2rpx solid #f1f1f1;">
                <view class="popup-item-title" >
                  退改费
                </view>
                <view class="popup-item-content">
                  <block v-if="guize.refundStipulate.rules && guize.refundStipulate.rules.length>0">
                    <view v-for="(item,index) in guize.refundStipulate.rules" :key="index">
                      {{item.txt}} ￥{{item.charge}}/人
                    </view>
                  </block>
                  <block v-else>
                    <view v-if="guize.refundStipulate.comment">{{guize.refundStipulate.comment}}</view>
                  </block>
                </view>
              </view>
              <view v-if="guize.changeStipulate" style="display: flex;align-items: center;border-top: 2rpx solid #f1f1f1;">
                <view class="popup-item-title" >
                  同舱改签票
                </view>
                <view class="popup-item-content">
                  <block v-if="guize.changeStipulate.rules && guize.changeStipulate.rules.length>0">
                    <view v-for="(item,index) in guize.changeStipulate.rules" :key="index">
                      {{item.txt}} ￥{{item.charge}}/人
                    </view>
                  </block>
                  <block v-else>
                    <view v-if="guize.changeStipulate.comment">{{guize.changeStipulate.comment}}</view>
                  </block>
                </view>
              </view>
  
              <view v-if="guize.modifyStipulate" style="display: flex;align-items: center;border-top: 2rpx solid #f1f1f1;">
                <view class="popup-item-title" >
                  签转
                </view>
                <view class="popup-item-content">
                  {{guize.modifyStipulate}}
                </view>
              </view>
              <view v-if="guize.othercontent" style="display: flex;align-items: center;border-top: 2rpx solid #f1f1f1;">
                <view class="popup-item-title" >
                  其他说明
                </view>
                <view class="popup-item-content">
                  {{guize.othercontent}}
                </view>
              </view>
          </view>
        </view>
  			<view style="width: 100%;height: auto;max-height: 10vh"></view>
  		</scroll-view>
      
  	</view>
  </uni-popup>
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
      
      linktel:'',
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
			app.get('ApiHanglvfeike/orderdetail', {id: that.opt.id}, function (res) {
				that.loading = false;
				if(res.status == 1){

					that.detail = res.detail;	
          that.linktel = res.linktel;
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
        app.post('ApiHanglvfeike/delOrder', {orderid: orderid}, function (data) {
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
			var ischange =  e.currentTarget.dataset.ischange;
			if(ischange){
				var msg = '确定要取消改签吗？';
			}else{
				var msg = '确定要关闭该订单吗？';
			}
      app.confirm(msg, function () {
				app.showLoading('提交中');
        app.post('ApiHanglvfeike/closeOrder', {orderid: orderid}, function (data) {
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
    changetab2: function (e) {
      var st2 = e;
      this.st2 = st2;
      this.intoviewid = 'scrollid'+st2;
    },
    getguize: function () {
      var that = this;
    	that.nodata = false;
    	that.nomore = false;
    	that.loading = true;
      app.post('ApiHanglvfeike/guize', {searchNo:that.detail.searchNo,flightNo:that.detail.flightNo,cabinNo:that.detail.cabinNo}, function (res) {
    		that.loading = false;
        if(res.status == 1){
          that.guize = res.guize;
          that.$refs.popup.open();
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
    popupdetailClose(){
    	this.$refs.popup.close();
    },
    callphone:function(e) {
    	var phone = e.currentTarget.dataset.phone;
    	uni.makePhoneCall({
    		phoneNumber: phone,
    		fail: function () {
    		}
    	});
    }
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
.product .tips-text{font-size: 26rpx;width: 100%;text-align: center;color: #abb7c7;padding: 5rpx 0rpx 20rpx;}
.product .box{width: 100%; border-radius:24rpx;margin-bottom: 15rpx;background: #f4f6fa;overflow: hidden;padding: 20rpx;}
.product .book-person-view{width: 100%;justify-content: space-between;}
.product .book-person-view .left-info-view{min-height:100rpx;justify-content: space-between;}
.product .book-person-view .left-topinfo{width: 100%;font-size: 28rpx;color: #000;}
.product .book-person-view .left-topinfo .name-view{min-width: 70rpx;max-width: 140rpx;width: auto;white-space: nowrap;overflow: hidden;text-overflow: ellipsis;text-align: left;margin-right: 20rpx;}
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

/* 航班信息 */
.hangban-top-view{width:94%;border-radius:24rpx;margin: 20rpx auto 0rpx;overflow: hidden;}
.hangban-top-view .address-info-view{justify-content: space-between;padding: 30rpx;background: #fff;}
.hangban-top-view .address-info-view .address-name-view{}
.hangban-top-view .address-info-view .address-name-view .depart-name{font-size: 26rpx;color: #333;}
.hangban-top-view .address-info-view .address-name-view .depart-time{font-size: 40rpx;font-weight: bold;color: #000;margin-top: 15rpx;}
.hangban-top-view .address-info-view .location-icon{position: relative;flex: 1;justify-content: center;}
.hangban-top-view .address-info-view .location-icon image{width: 160rpx;}
.hangban-top-view .address-info-view .location-icon .stop-tag{border: 1px #9c9c9c solid;font-size: 24rpx;color: #7c7c7c;border-radius: 4rpx;padding: 0rpx 4rpx;white-space: nowrap;width: 40rpx;text-align: center;margin: 0 auto;}
.hangban-top-view .address-info-view .location-icon	.travel-time{font-size: 24rpx;color: #b4c1d2;}
/* 分割线 */
.hangban-top-view .divider-view{width: 100%;height:35rpx;position: relative;
background: radial-gradient(circle at left center, transparent 16rpx, #fff 0) left center, radial-gradient(circle at right center, transparent 16rpx, #fff 0) right center;background-repeat: no-repeat;background-size: 50% 100%;}
.hangban-top-view .divider-view::after{content: " ";display: block;width: 94%;border-top: 1px #b4c1d2 dashed;left: 50%;top: 50%;transform: translate(-50%,-50%);position: absolute;}
.hangban-top-view .supplement-view{color: #999;text-align: center;justify-content: center;background: #fff;padding: 10rpx 0rpx 30rpx;font-size: 24rpx;}
/*  */
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
	.hangban-info-view{width: 94%;margin: 50rpx auto 10rpx;}
	.hangban-info-view .logo-view{width: 60rpx;height: 60rpx;margin-right: 20rpx;}
	.hangban-info-view .logo-view image{width: 100%;height: 100%;}
	.hangban-info-view .hangban-name{font-size: 40rpx;color: #000;font-weight: bold;}
	.hangban-info-view .hangban-time{font-size: 32rpx;margin-top: 3rpx;}
  
  .head{width: 94%;margin: 20rpx auto;background-color: #fff;padding: 20rpx;border-radius:20rpx ;line-height: 45rpx;}
  .head-title{font-size: 36rpx;font-weight: bold;}
  .head-title2{color: #333;margin: 10rpx 0;}
</style>