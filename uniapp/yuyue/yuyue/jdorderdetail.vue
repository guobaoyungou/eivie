<template>
<view class="container">
	<block v-if="isload">
    <map v-if="orderinfo.protype == 1" class="map" :longitude="psorder.longitude2" :latitude="psorder.latitude2" scale="14" :markers="[{
    	id:0,
    	latitude:psorder.latitude2,
    	longitude:psorder.longitude2,
    	iconPath: `${pre_url}/static/img/peisong/marker_kehu.png`,
    	width:'44',
    	height:'54'
    },{
    	id:2,
    	latitude:worker.latitude,
    	longitude:worker.longitude,
    	iconPath: `${pre_url}/static/img/peisong/marker_qishou.png`,
    	width:'44',
    	height:'54'
    }]"></map>
		<map v-else-if="psorder.status!=4" class="map" :longitude="binfo.longitude" :latitude="binfo.latitude" scale="14" :markers="[{
			id:0,
			latitude:binfo.latitude,
			longitude:binfo.longitude,
			iconPath: `${pre_url}/static/img/peisong/marker_business.png`,
			width:'44',
			height:'54'
		},{
			id:1,
			latitude:orderinfo.latitude,
			longitude:orderinfo.longitude,
			iconPath: `${pre_url}/static/img/peisong/marker_kehu.png`,
			width:'44',
			height:'54'
		},{
			id:2,
			latitude:worker.latitude,
			longitude:worker.longitude,
			iconPath: `${pre_url}/static/img/peisong/marker_qishou.png`,
			width:'44',
			height:'54'
		}]"></map>
		<map v-else class="map" :longitude="binfo.longitude" :latitude="binfo.latitude" scale="14" :markers="[{
			id:0,
			latitude:binfo.latitude,
			longitude:binfo.longitude,
			iconPath: `${pre_url}/static/img/peisong/marker_business.png`,
			width:'44',
			height:'54'
		},{
			id:0,
			latitude:orderinfo.latitude,
			longitude:orderinfo.longitude,
			iconPath: `${pre_url}/static/img/peisong/marker_kehu.png`,
			width:'44',
			height:'54'
		}]"></map>

		<view class="order-box">
			<view class="head">
				<view class="fwtype1" v-if="psorder.fwtype != 2">到店</view><view v-else class="fwtype2">上门</view>
				<view v-if="psorder.fwtype==1 || psorder.fwtype==3">
					<view class="f1" v-if="psorder.status==3"><image :src="pre_url+'/static/img/peisong/ps_time.png'" class="img"/>已完成</view>
					<view class="f1" v-if="psorder.status==1"><image :src="pre_url+'/static/img/peisong/ps_time.png'" class="img"/> {{orderinfo.yydate}}<text class="t1">预计上门时间</text> </view>
					<view class="f1" v-if="psorder.status==2"><image :src="pre_url+'/static/img/peisong/ps_time.png'" class="img"/><text class="t1" style="margin-left:10rpx">服务中</text></view>
				</view>
				<view v-else-if="psorder.fwtype==2">
						<view class="f1" v-if="psorder.status==3"><image :src="pre_url+'/static/img/peisong/ps_time.png'" class="img"/>已完成</view>
						<view class="f1" v-else-if="psorder.status==1"><image :src="pre_url+'/static/img/peisong/ps_time.png'" class="img"/>期望上门时间<text class="t1">{{orderinfo.yydate}}</text> </view>
						<view class="f1" v-else-if="psorder.status==2"  ><image :src="pre_url+'/static/img/peisong/ps_time.png'" class="img"/>已到达，服务中</view>
				</view>
				<view class="flex1"></view>
				<view class="f2">
					<view class="t1" v-if="psorder.showprice">
						<text>￥{{psorder.order_totalprice}}</text>
						<text class="t11" v-if="psorder.ticheng>0">(￥{{psorder.ticheng}})</text>
					</view>
					<block v-else><text class="t1">{{psorder.ticheng}}</text>元</block>
				</view>
			</view>
			<view class="content" style="border-bottom:0">
        <block v-if="!orderinfo.protype">
          <view class="f1" v-if="psorder.fwtype == 2">
            <view class="t1"><text class="x1">{{psorder.juli}}</text><text class="x2">{{psorder.juli_unit}}</text></view>
            <view class="t2"><image :src="pre_url+'/static/img/peisong/ps_juli.png'" class="img"/></view>
            <view class="t3"><text class="x1">{{psorder.juli2}}</text><text class="x2">{{psorder.juli2_unit}}</text></view>
          </view>
          <view class="f2">
            <view class="t1">{{binfo.name}}</view>
            <view class="t2">{{binfo.address}}</view>
            <view class="t3">{{orderinfo.address}}</view>
              <view class="t2">{{orderinfo.area}}</view>
          </view>
          <view class="f3"  @tap.stop="daohang" data-protype="0" :data-fwtype="psorder.fwtype"><image :src="pre_url+'/static/img/peisong/ps_daohang.png'" class="img"/></view>
        </block>
        <block v-else>
          <view class="f1" style="margin-top: 38rpx;">
            <view class="t3"><text class="x1">{{psorder.juli2}}</text><text class="x2">{{psorder.juli2_unit}}</text></view>
          </view>
          <view class="f2">
            <view class="t3">{{orderinfo.address}}</view>
              <view class="t2">{{orderinfo.area}}</view>
          </view>
          <view class="f3" @tap.stop="daohang" data-protype="1"><image :src="pre_url+'/static/img/peisong/ps_daohang.png'" class="img"/></view>
        </block>
			</view>
		</view>

		<view class="orderinfo">
			<view class="box-title" v-if="psorder.resetname">服务项目({{orderinfo.procount}})</view>
			<view class="box-title" v-else>商品清单({{orderinfo.procount}})</view>
			<view v-for="(item, idx) in prolist" :key="idx" class="item">
				<text class="t1 flex1">{{item.name}} {{item.ggname}}</text>
				<text class="t2 flex0">￥{{item.sell_price}} ×{{item.num}} </text>
			</view>
		</view>
		
		<view class="orderinfo" v-if="psorder.status!=0">
			<view class="box-title">服务信息</view>
			<view class="item">
				<text class="t1">用户姓名</text>
				<text class="t2">{{orderinfo.linkman}}</text>
			</view>
			<view class="item">
				<text class="t1">用户电话</text>
				<text class="t2">{{orderinfo.tel}}</text>
			</view>
			<view class="item">
				<text class="t1">预约时间</text>
				<text class="t2">{{orderinfo.yydate}}</text>
			</view>
			<view class="item">
				<text class="t1">接单时间</text>
				<text class="t2">{{dateFormat(psorder.starttime)}}</text>
			</view>
			<view class="item" v-if="psorder.daodiantime">
				<text class="t1" v-if="psorder.resetname">到达时间</text>
				<text class="t1" v-else>{{yuyue_sign?'出发时间':'到店时间'}}</text>
				<text class="t2">{{dateFormat(psorder.daodiantime)}}</text>
			</view>
			<view class="item" v-if="psorder.sign_time">
				<text class="t1">开始时间</text>
				<text class="t2">{{dateFormat(psorder.sign_time)}}</text>
			</view>
			<view class="item" v-if="psorder.endtime">
				<text class="t1">完成时间</text>
				<text class="t2">{{dateFormat(psorder.endtime)}}</text>
			</view>
		</view>

		<view class="orderinfo">
			<view class="box-title">订单信息</view>
			<view class="item">
				<text class="t1">订单编号</text>
				<text class="t2" user-select="true" selectable="true">{{orderinfo.ordernum}}</text>
			</view>
			<view class="item">
				<text class="t1">下单时间</text>
				<text class="t2">{{dateFormat(orderinfo.createtime)}}</text>
			</view>
			<block v-if="orderinfo.status>0 && orderinfo.status<4">
				<view class="item">
					<text class="t1">支付时间</text>
					<text class="t2">{{dateFormat(orderinfo.paytime)}}</text>
				</view>
				<view class="item">
					<text class="t1">支付方式</text>
					<text class="t2">{{orderinfo.paytype}}</text>
				</view>
			</block>
			<view class="item">
				<text class="t1" v-if="psorder.resetname">服务金额</text>
				<text class="t1" v-else>商品金额</text>
				<text class="t2 red">¥{{orderinfo.product_price}}</text>
			</view>
			<view class="item">
				<text class="t1">订单金额</text>
				<text class="t2 red">¥{{orderinfo.totalprice}}</text>
			</view>
			<view class="item">
				<text class="t1">实付款</text>
				<text class="t2 red">¥{{orderinfo.paymoney}}</text>
			</view>
			<view class="item" v-if="orderinfo.message">
				<text class="t1">备注</text>
				<text class="t2 red wb-ba">{{orderinfo.message ? orderinfo.message : '无'}}</text>
			</view>
			<view class="item" v-for="item in orderinfo.formdata" :key="index" v-if="(orderinfo.formdata).length > 0">
				<text class="t1">{{item[0]}}</text>
				<view class="t2" v-if="item[2]=='upload'"><image :src="item[1]" style="width:400rpx;height:auto" mode="widthFix" @tap="previewImage" :data-url="item[1]"/></view>
				<view class="t2" v-else-if="item[2]=='upload_video'"><video :src="item[1]" style="width: 100%;"/></video></view>
        <view class="t2" v-else-if="item[2]=='upload_pics'">
          <block v-for="vv in item[1]" :key='kk'>
          	<image :src="vv" style="width:200rpx;height:auto;margin-right: 10rpx;" mode="widthFix" @tap="previewImage" :data-url="vv"/>
          </block>
        </view>
				<text class="t2 wb-ba" v-else user-select="true" selectable="true">{{item[1]}}</text>
			</view>
      <view class="item">
      	<text class="t1">服务方式</text>
      	<text class="t2">
          <block v-if="orderinfo.fwtype==2">
            {{text['上门服务']}}
          </block>
          <block v-if="orderinfo.fwtype==3">
            到商家服务
          </block>
          <block v-if="orderinfo.fwtype==1">
            {{text['到店服务']}} 
          </block>
        </text>
      </view>
      <block v-if="orderinfo.fwbid && orderinfo.fwbinfo">
        <view class="item">
        	<text class="t1">商家名称</text>
        	<text class="t2">{{orderinfo.fwbinfo.name}}</text>
        </view>
        <view class="item" v-if="orderinfo.fwbinfo.address">
        	<view class="t1">商家地址</view>
        	<view v-if="!orderinfo.fwbinfo.latitude || !orderinfo.fwbinfo.longitude" class="t2">
            {{orderinfo.fwbinfo.address}}
          </view>
          <view v-else @tap="openLocation" :data-latitude="orderinfo.fwbinfo.latitude" :data-longitude="orderinfo.fwbinfo.longitude" class="t2">
            {{orderinfo.fwbinfo.address}}
          </view>
        </view>
      </block>
		</view>
		<view class="orderinfo" v-if="psorder.show_service_pics">
			<view class="box-title">服务图片</view>
			<view class="item" style="align-items: center;" v-if="psorder.service_before_pics.length > 0">
				<text class="t1">施工前</text>
				<view class="t2">
					<view class="img-box flex-row">
						<view v-for="pic in psorder.service_before_pics" class="pic-img">
							<image :src="pic" @tap="previewImage" mode="widthFix" :data-url="pic"></image>
						</view>
					</view>
				</view>
			</view>
			<view class="item" style="align-items: center;" v-if="psorder.service_pics.length > 0">
				<text class="t1">施工中</text>
				<view class="t2">
					<view class="img-box flex-row">
						<view v-for="pic in psorder.service_pics" class="pic-img">
							<image :src="pic" @tap="previewImage" mode="widthFix" :data-url="pic"></image>
						</view>
					</view>
				</view>
			</view>
			<view class="item" style="align-items: center;" v-if="psorder.service_finish_pics.length > 0">
				<text class="t1">完成施工</text>
				<view class="t2">
					<view class="img-box flex-row">
						<view v-for="pic in psorder.service_finish_pics" class="pic-img">
							<image :src="pic" @tap="previewImage" mode="widthFix" :data-url="pic"></image>
						</view>
					</view>
				</view>
			</view>
		</view>
		<view style="width:100%;height:120rpx"></view>
		<view class="bottom" v-if="psorder.status!=4">
			<view class="f1" v-if="psorder.status!=0" @tap="call" :data-tel="orderinfo.tel"><image :src="pre_url+'/static/img/peisong/tel1.png'" class="img"/>联系顾客</view>
			<view class="f2" v-if="psorder.status!=0" @tap="call" :data-tel="binfo.tel"><image :src="pre_url+'/static/img/peisong/tel2.png'" class="img"/>联系商家</view>
			
			<view class="btn1" @tap="qiangdan" :data-id="psorder.id" v-if="psorder.status==0 && psorder.isqd==1 && psorder.worker_confirm < 0">立即抢单</view>
			<view class="btn1" 	style="background: #BCBFC7;" v-if="psorder.status==0 && !psorder.isqd">{{psorder.djs}}后可抢单</view>
			
			<!-- 服务人员进行确认是否接单 -->
			<block v-if="psorder.status==0 && (psorder.worker_confirm==0 || psorder.worker_confirm == 2)">
				<view class="btn1" @tap="confirmOrder" :data-id="psorder.id" data-st="1" :style="{background:t('color1')}">接受订单</view>
				<view class="btn1" @tap="confirmOrder" :data-id="psorder.id" data-st="2" :style="{background:t('color2')}">拒绝订单</view>
			</block>
			
			<block v-if="psorder.fwtype==1 || psorder.fwtype==3">
				<view class="btn1" @tap="setst" :data-id="psorder.id" data-st="2" v-if="psorder.status==1">顾客已到店</view>
				<view class="btn1" @tap="setst" :data-id="psorder.id" data-st="3" v-if="psorder.status==2 && !psorder.endtime">我已完成</view>
				<view class="btn1" v-if="psorder.status==2 && psorder.endtime" style="background: #f1f1f1;color:#333">待确认</view>
			</block>
			<block v-if="psorder.fwtype==2">
        
        <block v-if="psorder.status==1">
          <block v-if="yuyuecar && psorder.protype && needstartpic">
            <view class="btn1" @tap.stop="goto" :data-url="'/pagesA/yuyuecar/uppic?id='+psorder.id+'&st=2'">我已到达</view>
          </block>
          <block v-else>
            <view class="btn1" @tap="setst" :data-id="psorder.id" data-st="2">我已到达</view>
          </block>
        </block>
        
        <block v-if="psorder.status==2 && !psorder.endtime">
          <block v-if="yuyuecar && psorder.protype && needendpic">
            <view class="btn1" @tap.stop="goto" :data-url="'/pagesA/yuyuecar/uppic?id='+psorder.id+'&st=3'">我已完成</view>    
          </block>
          <block v-else>
            <view class="btn1" @tap="setst" :data-id="psorder.id" data-st="3">我已完成</view>    
          </block>
        </block>

        <view class="btn1" v-if="psorder.status==2 && psorder.endtime" style="background: #f1f1f1;color:#333">待确认</view>
        <view class="btn1" v-if="psorder.status==3" style="background: #f1f1f1;color:#333">已完成</view>
			</block>
		</view>
	</block>
	<loading v-if="loading"></loading>
	<dp-tabbar :opt="opt"></dp-tabbar>
	<popmsg ref="popmsg"></popmsg>
</view>
</template>

<script>
var app = getApp();
var interval2 = null;
export default {
  data() {
    return {
			opt:{},
			loading:false,
      isload: false,
			menuindex:-1,
			pre_url:app.globalData.pre_url,
			
      orderinfo: {},
      prolist: [],
			worker:{},
      binfo: {},
      psorder: {},
			yuyue_sign:false,
			nowtime2:'',
      
      yuyuecar:false,
      cancancel:false,
      needstartpic:false,
      startpic:'',
      needendpic:false,
      endpic:'',
			tmplids:'',
			text:{}
    };
  },
  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.getdata();
  },
	onUnload:function(){
		clearInterval(this.interval2);
	},
	onPullDownRefresh: function () {
		this.getdata();
	},
  methods: {
		getdata: function () {
			var that = this;
			app.get('ApiYuyueWorker/orderdetail', {id: that.opt.id}, function (res) {
				if(res.status==0){
					app.alert(res.msg)
					setTimeout(function () {
					  app.goto('dating');
					}, 1000);
					
				}
				that.orderinfo = res.orderinfo;
				that.prolist = res.prolist;
				that.binfo = res.binfo;
				that.psorder = res.psorder;
				that.worker = res.worker;
				that.yuyue_sign = res.yuyue_sign
				that.nowtime2 = res.nowtime
				if(res.isdelayed){
					clearInterval(interval2);
					interval2 = setInterval(function () {
						that.nowtime2 = that.nowtime2 + 1;
						that.getdjs();
					}, 1000);
				}
        if(res.yuyuecar){
          that.yuyuecar = true
          if(res.cancancel){
            that.cancancel = res.cancancel;
          }
          if(res.needstartpic){
            that.needstartpic = res.needstartpic;
          }
          if(res.needendpic){
            that.needendpic = res.needendpic;
          }
        }
				if(res.tmplids){
					that.tmplids = res.tmplids;
				}
				that.text = res.text
        
				that.loaded();
			});
		},
		getdjs: function () {
				var that = this;
				var nowtime = that.nowtime2;
				var psorder = that.psorder
				var totalsec = psorder.createtime * 1 + psorder.delayedtime * 60 - nowtime * 1;
				if (totalsec <= 0) {
					that.psorder.isqd = true;
				} else {
					var houer = Math.floor(totalsec / 3600);
					var min = Math.floor((totalsec - houer * 3600) / 60);
					var sec = totalsec - houer * 3600 - min * 60;
					var djs =  (min < 10 ? '0' : '') + min + '分' + (sec < 10 ? '0' : '') + sec + '秒';
				}
				that.psorder.djs = djs;
			},
    qiangdan: function (e) {
      var that = this;
      var id = e.currentTarget.dataset.id;
      app.confirm('确定要接单吗?', function () {
				app.showLoading('提交中');
        app.post('ApiYuyueWorker/qiangdan', {id: id}, function (data) {
					app.showLoading(false);
					if(data.status==1){
						app.success(data.msg);
						that.subscribeMessage(function () {
							setTimeout(function () {
							  that.getdata();
							}, 1000);
						});
					}else{
					  app.error(data.msg);
						that.subscribeMessage(function () {
							setTimeout(function () {
								app.goto('dating');
							}, 1000);
						});
					}
        });
      });
    },
    setst: function (e) {
      var that = this;
      var id = e.currentTarget.dataset.id;
      var st = e.currentTarget.dataset.st;
			if(st == 2){
				var tips = '确定改为已到店吗?';
				if(that.psorder.resetname){
					tips = '已到达现场';
				}
			}if(st == 3){
				var tips = '确定改为已完成吗?';
				if(that.psorder.resetname){
					tips = '服务已完成';
				}
			}
      app.confirm(tips, function () {
				app.showLoading('提交中');
        app.post('ApiYuyueWorker/setst', {id: id,st:st}, function (data) {
					app.showLoading(false);
          app.success(data.msg);
          setTimeout(function () {
            that.getdata();
          }, 1000);
        });
      });
    },

		daohang:function(e){
			var that = this;
			var datainfo = that.psorder;
			var binfo = that.binfo
			var orderinfo = that.orderinfo
      var protype = e.currentTarget.dataset.protype;
			var fwtype = e.currentTarget.dataset.fwtype;

      if(protype==1 || fwtype == 2){
      	var list = ['导航到用户'];
      }else{
      	var list = ['导航到商家'];
      }
			uni.showActionSheet({
        itemList: list,
        success: function (res) {
					if(res.tapIndex >= 0){
						if (res.tapIndex == 0) {
							if(protype==1){
								// 用户
								var longitude = datainfo.longitude2
								var latitude = datainfo.latitude2
								var name = datainfo.orderinfo.address
								var address = datainfo.orderinfo.address
							}else{
								if(fwtype != 2){
									// 到店
									var longitude = datainfo.longitude
									var latitude = datainfo.latitude
									var name = datainfo.binfo.name
									var address = datainfo.binfo.address
								}else{
									// 上门
									var longitude = datainfo.longitude2
									var latitude = datainfo.latitude2
									var name = datainfo.orderinfo.address
									var address = datainfo.orderinfo.address
								}
							}
						}
						console.log(longitude);
						console.log(latitude);
						console.log(address);
						uni.openLocation({
						 latitude:parseFloat(latitude),
						 longitude:parseFloat(longitude),
						 name:name,
						 address:address,
						 scale: 13
						})
					}
				}
			});
		},
		call:function(e){
			var tel = e.currentTarget.dataset.tel;
			uni.makePhoneCall({
				phoneNumber: tel
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
    	 scale: 13
    	})
    },
		confirmOrder:function(e){
			var that = this;
			var id = e.currentTarget.dataset.id;
			var st = e.currentTarget.dataset.st;
			var tips = '确定要接受该订单吗?';
			if(st == 2){
				var tips = '确定拒绝该订单吗?';
			}
			app.confirm(tips, function () {
				app.showLoading('提交中');
			  app.post('ApiYuyueWorker/confirmOrder', {id: id,st:st}, function (data) {
					app.showLoading(false);
			    app.success(data.msg);
			    setTimeout(function () {
			      app.goto('/yuyue/yuyue/jdorderlist','redirect')
			    }, 1000);
			  });
			});
		}
  }
}
</script>
<style>

.map{width:100%;height:500rpx;overflow:hidden}
.ordertop{width:100%;height:220rpx;padding:50rpx 0 0 70rpx}
.ordertop .f1{color:#fff}
.ordertop .f1 .t1{font-size:32rpx;height:60rpx;line-height:60rpx}
.ordertop .f1 .t2{font-size:24rpx}

.order-box{ width: 94%;margin:20rpx 3%;padding:6rpx 3%; background: #fff;border-radius:8px}
.order-box .head{ display:flex;width:100%; border-bottom: 1px #f5f5f5 solid; height:88rpx; line-height:88rpx; overflow: hidden; color: #999;}
.order-box .head .f1{display:flex;align-items:center;color:#222222;font-size: 26rpx;}
.order-box .head .f1 .img{width:24rpx;height:24rpx;margin-right:4px}
.order-box .head .f1 .t1{color:#06A051;margin-right:10rpx}
.order-box .head .f2{color:#FF6F30}
.order-box .head .f2 .t1{font-size:36rpx;margin-right:4rpx}
.order-box .head .f2 .t11{font-size:30rpx;color: #999;}

.order-box .content{display:flex;justify-content:space-between;width: 100%; padding:16rpx 0px;border-bottom: 1px solid #f5f5f5;position:relative}
.order-box .content .f1{width:100rpx;display:flex;flex-direction:column;align-items:center}
.order-box .content .f1 .t1{display:flex;flex-direction:column;align-items:center}
.order-box .content .f1 .t1 .x1{color:#FF6F30;font-size:28rpx;font-weight:bold}
.order-box .content .f1 .t1 .x2{color:#999999;font-size:24rpx;margin-bottom:8rpx}
.order-box .content .f1 .t2 .img{width:12rpx;height:36rpx}

.order-box .content .f1 .t3{display:flex;flex-direction:column;align-items:center}
.order-box .content .f1 .t3 .x1{color:#FF6F30;font-size:28rpx;font-weight:bold}
.order-box .content .f1 .t3 .x2{color:#999999;font-size:24rpx}
.order-box .content .f2{}
.order-box .content .f2 .t1{font-size:36rpx;color:#222222;font-weight:bold;line-height:50rpx;margin-bottom:6rpx;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:1;overflow:hidden;}
.order-box .content .f2 .t2{font-size:24rpx;color:#222222;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:1;overflow:hidden;}
.order-box .content .f2 .t3{font-size:36rpx;color:#222222;font-weight:bold;line-height:50rpx;margin-top:30rpx;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:1;overflow:hidden;}
.order-box .content .f3 .img{width:72rpx;height:168rpx}

.orderinfo{width: 94%;margin:20rpx 3%;margin-top:10rpx;padding: 14rpx 3%;background: #FFF;border-radius:8px}
.orderinfo .box-title{color:#161616;font-size:30rpx;height:80rpx;line-height:80rpx;font-weight:bold}
.orderinfo .item{display:flex;width:100%;padding:10rpx 0;}
.orderinfo .item .t1{width:200rpx;color:#161616}
.orderinfo .item .t2{flex:1;text-align:right;color:#222222}
.orderinfo .item .red{color:red}

.bottom{ width: 100%;background: #fff; position: fixed; bottom: 0px;left: 0px;display:flex;justify-content:flex-end;align-items:center;z-index: 1;}
.bottom .f1{width:188rpx;display:flex;align-items:center;flex-direction:column;font-size:20rpx;color:#373C55;border-right:1px solid #EAEEED}
.bottom .f1 .img{width:44rpx;height:44rpx}
.bottom .f2{width:188rpx;display:flex;align-items:center;flex-direction:column;font-size:20rpx;color:#373C55}
.bottom .f2 .img{width:44rpx;height:44rpx}
.bottom .btn1{flex:1;background:linear-gradient(-90deg, #06A051 0%, #03B269 100%);height:100rpx;line-height:100rpx;color:#fff;text-align:center;font-size:32rpx}
.fwtype1{background: linear-gradient(-90deg, #06A051 0%, #03B269 100%);color: #fff;border-radius: 8rpx;
	text-align: center;margin: 24rpx 10rpx;padding: 5rpx 8rpx;line-height: 33rpx;font-size: 22rpx;}
.fwtype2{background: #FF6F30;color: #fff;border-radius: 8rpx;text-align: center;margin: 24rpx 8rpx;padding: 5rpx 10rpx;line-height: 33rpx;font-size: 22rpx;}
.img-box {flex-wrap: wrap;}
.img-box .pic-img{width: 200rpx;margin: 10rpx;}
.img-box image{width: 100%;height: 100%;}

.wb-ba{word-break: break-all;}
</style>