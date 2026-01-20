<template>
<view class="container">
	<block v-if="isload">
		<view class="ordertop" :style="'background:url(' + pre_url + '/static/img/ordertop.png);background-size:100%'">
			<view class="f1" v-if="detail.status==0">
				<view class="t1">等待买家付款</view>
				<view class="t2" v-if="djs">剩余时间：{{djs}}</view>
			</view>
			<view class="f1" v-if="detail.status==1">
				<view class="t1">{{detail.paytypeid==4 ? '已选择'+detail.paytype : '已成功付款'}}</view>
				<view class="t2" v-if="detail.freight_type!=1">我们会尽快为您发货</view>
				<view class="t2" v-if="detail.freight_type==1">请尽快前往自提地点取货</view>
			</view>
			<view class="f1" v-if="detail.status==2">
				<view class="t1"> <text v-if="detail.type && (detail.type==1 || detail.type==2)">等待发放</text><text v-else> 订单已发货</text></view>
				<block v-if="detail.freight_type!=3">
					<text class="t2" user-select="true" selectable="true" v-if="detail.express_com && detail.express_no">发货信息：{{detail.express_com}} {{detail.express_no}}</text>
				</block>
			</view>
			<view class="f1" v-if="detail.status==3">
				<view class="t1">订单已完成</view>
			</view>
			<view class="f1" v-if="detail.status==4">
				<view class="t1">订单已取消</view>
			</view>
		</view>
		<view class="address">
			<view class="img">
				<image :src="pre_url+'/static/img/address3.png'"></image>
			</view>
			<view class="info" v-if="detail.mdid == -1">
				<view class="t1"><text user-select="true" selectable="true">{{detail.linkman}} {{detail.tel}}</text></view>
				<view class="t1" style="margin-top:20rpx">取货地点：</view>
				<view>
					<block v-for="(item, idx) in storelist" :key="idx">
						<view class="radio-item" v-if="idx<5 || storeshowall==true" @tap="openLocation" :data-latitude="item.latitude" :data-longitude="item.longitude" :data-company="item.name" :data-address="item.address">
							<view class="f1">
								<view>{{item.name}}</view>
								<view v-if="item.address" class="flex-y-center" style="text-align:left;font-size:24rpx;color:#aaaaae;display: -webkit-box;-webkit-box-orient: vertical;-webkit-line-clamp:1;overflow: hidden;">{{item.address}}</view>
							</view>
							<text style="color:#f50;">{{item.juli}}</text>
						</view>
					</block>
					<view v-if="storeshowall==false && storelist.length > 5" class="storeviewmore" @tap="doStoreShowAll">- 查看更多 - </view>
				</view>
			</view>
			<view class="info" v-else>
				<text class="t1" user-select="true" selectable="true">{{detail.linkman}} {{detail.tel}}</text>
				<text class="t2" v-if="detail.freight_type!=1 && detail.freight_type!=3" user-select="true" selectable="true">地址：{{detail.area}}{{detail.address}}</text>
				<block v-if="detail.freight_type==1">
					<text class="t2" v-if="!isNull(storeinfo)" @tap="openMendian" :data-storeinfo="storeinfo" :data-latitude="storeinfo.latitude" :data-longitude="storeinfo.longitude" user-select="true" selectable="true">取货地点：{{storeinfo.name}} - {{storeinfo.address}}</text>
					<text class="t2" v-else>取货地点数据不存在，请联系客服</text>
				</block>
			</view>
		</view>
		<view class="btitle flex-y-center" v-if="detail.bid>0 && detail.binfo">
			<image :src="detail.binfo.logo" style="width:36rpx;height:36rpx;" @tap="goto" :data-url="'/pagesExt/business/index?id=' + detail.bid"></image>
			<text class="flex1" decode="true" space="true" @tap="goto" :data-url="'/pagesExt/business/index?id=' + detail.bid" style="padding-left:16rpx">{{detail.binfo.name}}</text>
		</view>
		<view class="product">
			<view v-for="(item, idx) in prolist" :key="idx" class="content">
				<view @tap="goto" :data-url="'product?id=' + item.proid">
					<image :src="item.pic"></image>
				</view>
				<view class="detail">
					<text class="t1">{{item.name}}</text>
					<text class="t2" v-if="item.ggname" style="color:#666">规格：{{item.ggname}}</text>
					<text class="t2" v-else>市场价￥{{item.sell_price}}</text>
					<view class="t3"><text class="x1 flex1"><text v-if="item.money_price>0">￥{{item.money_price}}+</text>{{item.score_price}}{{t('金豆')}}</text><text class="x2">×{{item.num}}</text></view>
				</view>
			</view>
		</view>
		
		<view class="orderinfo" v-if="(detail.status==3 || detail.status==2) && (detail.freight_type==3 || detail.freight_type==4)">
			<view class="item flex-col">
				<view class="flex-bt order-info-title">
					<text class="t1" style="color:#111">发货信息</text>
					<view class="btn-class" @click="copy" :data-text='detail.freight_content'>复制</view>
				</view>
				<text class="t2" style="text-align:left;margin-top:10rpx;padding:0 10rpx" user-select="true" selectable="true">{{detail.freight_content}}</text>
			</view>
		</view>
		
		<view class="orderinfo">
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
			<view class="item" v-if="detail.status>1 && detail.send_time">
				<text class="t1">发货时间</text>
				<text class="t2">{{detail.send_time}}</text>
			</view>
			<view class="item" v-if="detail.status==3 && detail.collect_time">
				<text class="t1">收货时间</text>
				<text class="t2">{{detail.collect_time}}</text>
			</view>
		</view>
		<view class="orderinfo">
			<view class="item">
				<text class="t1">商品金额</text>
				<text class="t2 red" v-if="detail.totalmoney">¥{{detail.totalmoney}} + {{detail.totalscore}}{{t('金豆')}}</text>
				<text class="t2 red" v-else>{{detail.totalscore}}{{t('金豆')}}</text>
			</view>
			<view class="item">
				<text class="t1">配送方式</text>
				<text class="t2">{{detail.freight_text}}</text>
			</view>
			<view class="item" v-if="detail.freight_type==1 && detail.freightprice > 0">
				<text class="t1">服务费</text>
				<text class="t2 red">+¥{{detail.freight_price}}</text>
			</view>
			<view class="item" v-if="detail.freight_time">
				<text class="t1">{{detail.freight_type!=1?'配送':'提货'}}时间</text>
				<text class="t2">{{detail.freight_time}}</text>
			</view>
			<view class="item">
				<text class="t1">实付款</text>
				<text class="t2 red">¥{{detail.totalprice}} + {{detail.totalscore}}{{t('金豆')}}</text>
			</view>

			<view class="item">
				<text class="t1">订单状态</text>
				<text class="t2" v-if="detail.status==0">未付款</text>
				<text class="t2" v-if="detail.status==1">待发货</text>
				<view class="t2" v-if="detail.status==2"><text v-if="detail.type && (detail.type==1||detail.type==2)">等待发放</text><text v-else> 已发货</text></view>
				<text class="t2" v-if="detail.status==3">已收货</text>
				<text class="t2" v-if="detail.status==4">已关闭</text>
			</view>
			<view class="item" v-if="detail.refund_status>0">
				<text class="t1">退款状态</text>
				<text class="t2 red" v-if="detail.refund_status==1">审核中,¥{{detail.refund_money}} <text v-if="detail.refund_gold_bean > 0">+{{detail.refund_gold_bean}}{{t('金豆')}}</text></text>
				<text class="t2 red" v-if="detail.refund_status==2">已退款,¥{{detail.refund_money}} <text v-if="detail.refund_gold_bean > 0">+{{detail.refund_gold_bean}}{{t('金豆')}}</text></text>
				<text class="t2 red" v-if="detail.refund_status==3">已驳回,¥{{detail.refund_money}} <text v-if="detail.refund_gold_bean > 0">+{{detail.refund_gold_bean}}{{t('金豆')}}</text></text>
			</view>
			<view class="item" v-if="detail.refund_status>0">
				<text class="t1">退款原因</text>
				<text class="t2 red">{{detail.refund_reason}}</text>
			</view>
			<view class="item" v-if="detail.refund_checkremark">
				<text class="t1">审核备注</text>
				<text class="t2 red">{{detail.refund_checkremark}}</text>
			</view>
			
			<view class="item" v-if="detail.isfuwu && detail.fuwuendtime > 0">
				<text class="t1">到期时间</text>
				<text class="t2 red">{{_.dateFormat(detail.fuwuendtime,'Y-m-d H:i')}}</text>
			</view>
      <view class="item" v-if="detail.othermid && detail.otherinfo">
      	<text class="t1">兑换来源</text>
      	<text class="t2">{{detail.otherinfo}}</text>
      </view>
		</view>
		
		<view class="orderinfo" v-if="(detail.formdata).length > 0">
			<view class="item" v-for="item in detail.formdata" :key="index" style="display: block">
				<text class="t1">{{item[0]}}</text>
				<view class="t2" v-if="item[2]=='upload'"><image :src="item[1]" style="width:400rpx;height:auto" mode="widthFix" @tap="previewImage" :data-url="item[1]"/></view>
        <view class="t2" v-else-if="item[2]=='upload_pics'" v-for="picurl in item[1]">
          <image :src="picurl" style="width:400rpx;height:auto" mode="widthFix" @tap="previewImage" :data-url="picurl"/>
        </view>
				<text class="t2" v-else user-select="true" selectable="true">{{item[1]}}</text>
			</view>
		</view>

		<view style="width:100%;height:calc(160rpx + env(safe-area-inset-bottom));"></view>

		<view class="bottom notabbarbot">
			<block v-if="detail.status==0">
				<view class="btn2" @tap="toclose" :data-id="detail.id">关闭订单</view>
				<view class="btn1" :style="{background:t('color1')}" @tap="goto" :data-url="'/pagesExt/pay/pay?id=' + detail.payorderid">去付款</view>
			</block>
			<block v-if="detail.status==1 && show_refund == 1">
				<block v-if="detail.paytypeid!='4'">
					<view v-if="detail.refund_status==0 || detail.refund_status==3" class="btn2" @tap="goto" :data-url="'refund?orderid=' + detail.id + '&price=' + detail.totalprice + '&score=' + detail.totalscore">申请退款</view>
				</block>
				<block v-else>
					<!-- <view class="btn ref">{{codtxt}}</view> -->
				</block>
			</block>
			
			<block v-if="detail.status==2 || detail.status==3">
				<view class="btn2"  @tap="logistics" :data-express_type="detail.express_type" :data-express_com="detail.express_com" :data-express_no="detail.express_no" :data-express_content="detail.express_content" v-if="detail.freight_type!=3 && detail.freight_type!=4">查看物流</view>
			</block>

			<block v-if="detail.status==2">
				<block v-if="detail.paytypeid!='4' && show_refund == 1">
					<view v-if="detail.refund_status==0 || detail.refund_status==3" class="btn2" @tap="goto" :data-url="'refund?orderid=' + detail.id + '&price=' + detail.totalprice + '&score=' + detail.totalscore">申请退款</view>
				</block>
				<view class="btn1" :style="{background:t('color1')}" @tap="orderCollect" :data-id="detail.id" v-if="detail.paytypeid!='4'  && !detail.type">确认收货</view>
				<block v-else>
					<!-- <view class="btn2">{{codtxt}}</view> -->
				</block>
			</block>
			<block v-if="(detail.status==1 || detail.status==2) && detail.freight_type==1">
				<view class="btn2" @tap="showhxqr">核销码</view>
			</block>
			<block v-if="detail.status==3 || detail.status==4">
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
    
    <uni-popup id="dialogSelectExpress" ref="dialogSelectExpress" type="dialog">
    	<view style="background:#fff;padding:20rpx 30rpx;border-radius:10rpx;width:600rpx" v-if="express_content">
    		<view class="sendexpress" v-for="(item, index) in express_content" :key="index" style="border-bottom: 1px solid #f5f5f5;padding:20rpx 0;">
    			<view class="sendexpress-item" @tap="goto" :data-url="'/pagesExt/order/logistics?express_com=' + item.express_com + '&express_no=' + item.express_no+ '&ordertype=' + item.ordertype+ '&orderid=' + item.orderid" style="display: flex;">
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
      prolist: "",
      shopset: "",
      storeinfo: {},
      lefttime: "",
      codtxt: "",
			storelist:[],
			storeshowall:false,
			show_refund:1, //显示退款入口
      
      express_content:'',
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
			app.get('ApiGoldBeanShop/orderdetail', {id: that.opt.id}, function (res) {
				uni.stopPullDownRefresh();
				that.loading = false;
        if(res.status == 1){
          that.detail = res.detail;
          that.prolist = res.prolist;
          that.shopset = res.shopset;
          if(res.storeinfo)that.storeinfo = res.storeinfo;
          that.lefttime = res.lefttime;
          that.codtxt = res.codtxt;
          that.storelist = res.storelist || [];
          that.isload = 1;
          if(res.scoreshopset && res.scoreshopset.show_refund && app.isNull(res.scoreshopset.show_refund)){
            that.show_refund = res.scoreshopset.show_refund
          }
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
        app.post('ApiGoldBeanShop/delOrder', {orderid: orderid}, function (data) {
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
        app.post('ApiGoldBeanShop/closeOrder', {orderid: orderid}, function (data) {
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
        app.post('ApiScoreshop/orderCollect', {orderid: orderid}, function (data) {
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
		doStoreShowAll:function(){
			this.storeshowall = true;
		},
    logistics:function(e){
    	var express_com = e.currentTarget.dataset.express_com
    	var express_no = e.currentTarget.dataset.express_no
    	var express_content = e.currentTarget.dataset.express_content
    	var express_type = e.currentTarget.dataset.express_type || ''
    	var prolist = this.prolist;
    	if(!express_content){
    		app.goto('/pagesExt/order/logistics?express_com=' + express_com + '&express_no=' + express_no+'&type='+express_type);
    	}else{
    		express_content = JSON.parse(express_content);
    		for(var i in express_content){
    			if(express_content[i].express_ogids){
    				var express_ogids = (express_content[i].express_ogids).split(',');
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
  }
};
</script>
<style>
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

.product{width:94%;margin:0 3%;border-radius:8rpx;margin-top:16rpx;padding: 14rpx 3%;background: #FFF;}
.product .content{display:flex;position:relative;width: 100%; padding:16rpx 0px;border-bottom: 1px #e5e5e5 dashed;}
.product .content:last-child{ border-bottom: 0; }
.product .content image{ width: 140rpx; height: 140rpx;}
.product .content .detail{display:flex;flex-direction:column;margin-left:14rpx;flex:1}
.product .content .detail .t1{font-size:28rpx;line-height:36rpx;margin-bottom:10rpx;color: #000;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:2;overflow:hidden;}
.product .content .detail .t2{height: 46rpx;line-height: 46rpx;color: #999;overflow: hidden;font-size: 26rpx;}
.product .content .detail .t3{display:flex;height:40rpx;line-height:40rpx;color: #ff4246;}
.product .content .detail .x1{ flex:1}
.product .content .detail .x2{ width:100rpx;font-size:32rpx;text-align:right;margin-right:8rpx}
.product .content .comment{position:absolute;top:64rpx;right:10rpx;border: 1px #ffc702 solid; border-radius:10rpx;background:#fff; color: #ffc702;  padding: 0 10rpx; height: 46rpx; line-height: 46rpx;}
.product .content .comment2{position:absolute;top:64rpx;right:10rpx;border: 1px #ffc7c2 solid; border-radius:10rpx;background:#fff; color: #ffc7c2;  padding: 0 10rpx; height: 46rpx; line-height: 46rpx;}

.orderinfo{width:94%;margin:0 3%;border-radius:8rpx;margin-top:16rpx;padding: 14rpx 3%;background: #FFF;}
.orderinfo .item{display:flex;width:100%;padding:20rpx 0;border-bottom:1px dashed #ededed;}
.orderinfo .item:last-child{ border-bottom: 0;}
.orderinfo .item .t1{width:200rpx;}
.orderinfo .item .t2{flex:1;text-align:right}
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
</style>