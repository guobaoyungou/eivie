<template>
<view class="container">
	<block v-if="isload">
		<view class="ordertop" :style="'background:url('+(shopset.order_detail_toppic?shopset.order_detail_toppic: pre_url + '/static/img/ordertop.png')+');background-size:100%'">
			<view class="f1" v-if="detail.status==0">
				<view class="t1">等待买家付款</view>
				<view class="t2" v-if="djs">剩余时间：{{djs}}</view>
			</view>
			<view class="f1" v-if="detail.status==1">
				<view class="t1">{{detail.paytypeid==4 ? '已选择'+detail.paytype : '已成功付款'}}</view>
				<view class="t2" v-if="detail.freight_type!=1">请尽快发货</view>
				<view class="t2" v-if="detail.freight_type==1">待提货</view>
			</view>
			<view class="f1" v-if="detail.status==2">
				<view class="t1">订单已发货</view>
				<view class="t2" v-if="detail.freight_type!=3">发货信息：{{detail.express_com}} {{detail.express_no}}</view>
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
			<view class="info">
				<view class="t1"><text user-select="true" selectable="true">{{detail.linkman}}</text> <text v-if="detail.tel" @tap="goto" :data-url="'tel:'+detail.tel" style="margin-left: 20rpx;" user-select="true" selectable="true">{{detail.tel}}</text></view>
				<text class="t2" v-if="detail.freight_type!=1 && detail.freight_type!=3" user-select="true" selectable="true">地址：{{detail.area}}{{detail.address}}</text>
        <text class="t2" v-if="detail.product_thali">学生姓名：{{detail.product_thali_student_name}}</text>
        <text class="t2" v-if="detail.product_thali">学校信息：{{detail.product_thali_school}}</text>
				<text class="t2" v-if="detail.freight_type==1" @tap="openLocation" :data-address="storeinfo.address" :data-latitude="storeinfo.latitude" :data-longitude="storeinfo.longitude" user-select="true" selectable="true">取货地点：{{storeinfo.name}} - {{storeinfo.address}}</text>
        <view class="t2" v-if="detail.worknum">工号：{{detail.worknum}}</view>
      </view>
		</view>
    
    <view class="orderinfo" v-if="detail.usegiveorder && detail.usegiveorder == 1">
      <view class="title">
      	赠好友
      </view>
      <view class="item">
      	<view class="t1">领取状态</view>
      	<view v-if="detail.giveordermid>0" class="t2" style="color: green;">已领取</view>
        <view v-else class="t2" style="color: red;">未领取</view>
      </view>
      <block v-if="detail.giveordermid>0 && detail.givemember">
        <view class="item">
          <text class="t1">好友信息</text>
          <text class="flex1"></text>
          <image :src="detail.givemember.headimg" style="width:80rpx;height:80rpx;margin-right:8rpx"/>
          <text style="height:80rpx;line-height:80rpx">{{detail.givemember.nickname}}(ID:{{detail.giveordermid}})</text>
        </view>
      </block>
    </view>

		<view class="product">
			<view v-for="(item, idx) in prolist" :key="idx" class="box">
				<view class="content">
					<view @tap="goto" :data-url="'/pages/shop/product?id=' + item.proid">
						<image :src="item.pic"></image>
					</view>
					<view class="detail">
						<text class="t1">{{item.name}}</text>
						<text class="t2">{{item.ggname}}</text>
						<view class="t3" v-if="item.product_type && item.product_type==2">
							<text class="x1 flex1">{{item.real_sell_price}}元/斤</text>
							<text class="x2">×{{item.real_total_weight}}斤</text>
							</view>
						<view class="t3" v-else><text class="x1 flex1">￥{{item.sell_price}}</text><text class="x2">×{{item.num}}</text></view>
						<text class="t2" v-if="shopset.show_shd_remark == 1 && item.shd_remark" style="height: auto;">备注：{{item.shd_remark}}</text>
						<block v-if="(detail.status==1 || detail.status==2) && detail.is_quanyi==1 && item.hexiao_code">
							<view class="btn3" @tap.stop="showhxqr2" :data-id="item.id" :data-num="item.hexiao_num_total" :data-hxnum="item.hexiao_num_used" :data-hexiao_code="item.hexiao_code" style="position:absolute;top:46rpx;right:0rpx;">权益核销</view>
						</block>
					</view>
				</view>
				<!-- glassinfo -->
				<view class="glassitem" v-if="item.glassrecord">
					<view class="gcontent">
						<view class="glassheader">
							{{item.glassrecord.name}}
							{{item.glassrecord.nickname?item.glassrecord.nickname:''}}
							{{item.glassrecord.check_time?item.glassrecord.check_time:''}}
							{{item.glassrecord.typetxt}}
							
							<text class="pdl10" v-if="item.glassrecord.double_ipd==0">{{item.glassrecord.ipd?'PD'+item.glassrecord.ipd:''}}</text>
							<text class="pdl10" v-else>PD R{{item.glassrecord.ipd_right}} L{{item.glassrecord.ipd_left}}</text>
						</view>
						<view class="glassrow bt">
							<view class="grow">
							R {{item.glassrecord.degress_right}}/{{item.glassrecord.ats_right?item.glassrecord.ats_right:'0.00'}}*{{item.glassrecord.ats_zright?item.glassrecord.ats_zright:'0'}} <text class="pdl10" v-if="item.glassrecord.type==3">ADD+{{item.glassrecord.add_right?item.glassrecord.add_right:0}}</text>
							</view>
							<view class="grow">
							L {{item.glassrecord.degress_left}}/{{item.glassrecord.ats_left?item.glassrecord.ats_left:'0.00'}}*{{item.glassrecord.ats_zleft?item.glassrecord.ats_zleft:'0'}} <text class="pdl10" v-if="item.glassrecord.type==3">ADD+{{item.glassrecord.add_left?item.glassrecord.add_left:0}}</text>
							</view>
						</view>
						<view class="glassrow" v-if="item.glassrecord.remark">{{item.glassrecord.remark}}</view>
					</view>
					
				</view>
				<!-- glassinfo -->
			</view>
      <view v-if="detail.crk_givenum && detail.crk_givenum>0" style="color:#f60;line-height:70rpx">+随机赠送{{detail.crk_givenum}}件</view>
		</view>
		
		<view class="orderinfo" v-if="(detail.status==3 || detail.status==2) && (detail.freight_type==3 || detail.freight_type==4)">
			<view class="item flex-col">
				<text class="t1" style="color:#111">发货信息</text>
				<text class="t2" style="text-align:left;margin-top:10rpx;padding:0 10rpx" user-select="true" selectable="true">{{detail.freight_content}}</text>
			</view>
		</view>
		
		<view class="orderinfo" v-if="detail.mid>0">
			<view class="item">
				<text class="t1">下单人</text>
				<text class="flex1"></text>
				<image :src="detail.headimg" style="width:80rpx;height:80rpx;margin-right:8rpx"/>
				<text  style="height:80rpx;line-height:80rpx">{{detail.nickname}}</text>
			</view>
			<view class="item">
				<text class="t1">{{t('会员')}}ID</text>
				<text class="t2">{{detail.mid}}</text>
			</view>
		</view>
		<view class="orderinfo" v-if="detail.remark">
			<view class="item">
				<text class="t1">备注</text>
				<text class="t2" user-select="true" selectable="true">{{detail.remark}}</text>
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
      <view class="item" v-if="detail.issource && detail.source && detail.source == 'supply_zhenxin'">
      	<text class="t1">商品来源</text>
      	<text class="t2">甄新汇选</text>
      </view>
			<view class="item">
				<text class="t1">商品金额</text>
				<text class="t2 red">¥{{detail.product_price}}</text>
			</view>
			<view class="item" v-if="detail.leveldk_money > 0">
				<text class="t1">{{t('会员')}}折扣</text>
				<text class="t2 red">-¥{{detail.leveldk_money}}</text>
			</view>
			<view class="item" v-if="detail.manjian_money > 0">
				<text class="t1">满减活动</text>
				<text class="t2 red">-¥{{detail.manjian_money}}</text>
			</view>
			<view class="item" v-if="detail.invoice_money > 0">
				<text class="t1">发票费用</text>
				<text class="t2 red">+¥{{detail.invoice_money}}</text>
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
      <view class="item" v-if="detail.silvermoneydec && detail.silvermoneydec > 0">
      	<text class="t1">{{t('银值')}}抵扣</text>
      	<text class="t2 red">-¥{{detail.silvermoneydec}}</text>
      </view>
      <view class="item" v-if="detail.goldmoneydec && detail.goldmoneydec > 0">
      	<text class="t1">{{t('金值')}}抵扣</text>
      	<text class="t2 red">-¥{{detail.goldmoneydec}}</text>
      </view>
			<view class="item" v-if="detail.discount_rand_money > 0">
				<text class="t1">随机立减</text>
				<text class="t2 red">-¥{{detail.discount_rand_money}}</text>
			</view>
      <view class="item" v-if="detail.dedamount_dkmoney && detail.dedamount_dkmoney > 0">
      	<text class="t1">抵扣金抵扣</text>
      	<text class="t2 red">-¥{{detail.dedamount_dkmoney}}</text>
      </view>
      <view class="item" v-if="detail.shopscoredk_money > 0">
      	<text class="t1">{{t('产品积分')}}抵扣</text>
      	<text class="t2 red">-¥{{detail.shopscoredk_money}}</text>
      </view>
			<view class="item">
				<text class="t1">实付款</text>
				<text class="t2 red">¥{{detail.totalprice}}</text>
			</view>
      <view class="item" v-if="detail.combine_money && detail.combine_money > 0">
        <text class="t1">{{t('余额')}}已付</text>
        <text class="t2 red">-¥{{detail.combine_money}}</text>
      </view>
      <view class="item" v-if="detail.paytypeid == 2 && detail.status<=3 && detail.combine_wxpay && detail.combine_wxpay > 0">
        <text class="t1">微信已付</text>
        <text class="t2 red">-¥{{detail.combine_wxpay}}</text>
      </view>
      <view class="item" v-if="(detail.paytypeid == 3 || (detail.paytypeid>=302 && detail.paytypeid<=330)) && detail.combine_alipay && detail.combine_alipay > 0">
        <text class="t1">支付宝已付</text>
        <text class="t2 red">-¥{{detail.combine_alipay}}</text>
      </view>

			<view class="item">
				<text class="t1">订单状态</text>
				<text class="t2" v-if="detail.status==0">未付款</text>
				<text class="t2" v-if="detail.status==1">已付款</text>
				<text class="t2" v-if="detail.status==2">已发货</text>
				<text class="t2" v-if="detail.status==3">已收货</text>
				<text class="t2" v-if="detail.status==4">已关闭</text>
			</view>
			<view class="item" v-if="detail.refund_status>0">
				<text class="t1">退款状态</text>
				<text class="t2 red" v-if="detail.refund_status==1">审核中,¥{{detail.refund_money}}</text>
				<text class="t2 red" v-if="detail.refund_status==2">已退款,¥{{detail.refund_money}}</text>
				<text class="t2 red" v-if="detail.refund_status==3">已驳回,¥{{detail.refund_money}}</text>
			</view>
			<view class="item" v-if="detail.refund_status>0">
				<text class="t1">退款原因</text>
				<text class="t2 red">{{detail.refund_reason||'暂无'}}</text>
			</view>
			<view class="item" v-if="detail.refund_checkremark">
				<text class="t1">审核备注</text>
				<text class="t2 red">{{detail.refund_checkremark}}</text>
			</view>
			<view class="item flex-col" v-if="(detail.status==1 || detail.status==2) && detail.freight_type==1 && detail.is_quanyi==0 && !detail.hidefahuo">
				<text class="t1">核销码</text>
				<view class="flex-x-center">
					<image :src="detail.hexiao_qr" style="width:400rpx;height:400rpx" @tap="previewImage" :data-url="detail.hexiao_qr"></image>
				</view>
			</view>
			<view class="item" v-if="detail.balance_price>0">
				<text class="t1">尾款</text>
				<text class="t2 red">¥{{detail.balance_price}}</text>
			</view>
			<view class="item" v-if="detail.balance_price>0">
				<text class="t1">尾款状态</text>
				<text class="t2" v-if="detail.balance_pay_status==1">已支付</text>
				<text class="t2" v-if="detail.balance_pay_status==0">未支付</text>
			</view>
		</view>
		<view class="orderinfo" v-if="detail.checkmemid">
			<view class="item">
				<text class="t1">所选会员</text>
				<text class="flex1"></text>
				<image :src="detail.checkmember.headimg" style="width:80rpx;height:80rpx;margin-right:8rpx"/>
				<text  style="height:80rpx;line-height:80rpx">{{detail.checkmember.nickname}}</text>
			</view>
		</view>
		<view class="orderinfo" v-if="detail.isdygroupbuy==1">
			<view class="item">
				<text class="t1">抖音团购券信息</text>
		    <text class="t2">{{detail.dyorderids}}</text>
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
			<view class="btn2" v-if="detail.status ==2 && userlevel.downorder_collect" @tap="orderCollect" :data-id="detail.id">确认收货</view>
		</view>
	</block>
	<loading v-if="loading"></loading>
	<dp-tabbar :opt="opt"></dp-tabbar>
	<popmsg ref="popmsg"></popmsg>
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
        expressdata:[],
        express_index:0,
        express_no:'',
        prodata: '',
        djs: '',
        detail: "",
        prolist: "",
        shopset: {},
        storeinfo: "",
        lefttime: "",
        codtxt: "",
        peisonguser:[],
        peisonguser2:[],
        index2:0,
        express_pic:'',
        express_fhname:'',
        express_fhaddress:'',
        express_shname:'',
        express_shaddress:'',
        express_remark:'',
        
				returnProlist:[], //退款商品
				refundTotalprice:0, //退款金额
				refundNum:[],
				refundReason:'', //退款原因
        myt_weight:'',
        myt_remark:'',
        mytindex:0,
        myt_shop_id:0,
		selecthxnumDialogShow:false,
		hxogid:'',
		hxnum:'',
		hxnumlist:[],
		hexiao_code:'',
		userlevel:[]
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
		saoyisao: function (d) {
		  var that = this;
			if(app.globalData.platform == 'h5'){
				app.alert('请使用微信扫一扫功能扫码');return;
			}else if(app.globalData.platform == 'mp'){
				var jweixin = require('jweixin-module');
				jweixin.ready(function () {   //需在用户可能点击分享按钮前就先调用
					jweixin.scanQRCode({
						needResult: 1, // 默认为0，扫描结果由微信处理，1则直接返回扫描结果，
						scanType: ["qrCode","barCode"], // 可以指定扫二维码还是一维码，默认二者都有
						success: function (res) {
							let serialNumber = res.resultStr; // 当needResult 为 1 时，扫码返回的结果
							let serial = serialNumber.split(",");
							serialNumber = serial[serial.length-1];
							that.express_no = serialNumber;
						},
						fail:function(err){
							app.error(err.errMsg);
						}
					});
				});
			}else{
				uni.scanCode({
					success: function (res) {
						that.express_no = res.result;
					},
					fail:function(err){
						app.error(err.errMsg);
					}
				});
			}
		},
		getdata: function () {
			var that = this;
			that.loading = true;
			app.get('ApiAgent/downshoporderdetail', {id: that.opt.id}, function (res) {
				that.loading = false;
				that.expressdata = res.expressdata;
				that.userlevel = res.userlevel;
				that.detail = res.detail;
				that.prolist = res.prolist;
				that.shopset = res.shopset;
				that.storeinfo = res.storeinfo;
				that.lefttime = res.lefttime;
				that.codtxt = res.codtxt;
				if (res.lefttime > 0) {
					interval = setInterval(function () {
						that.lefttime = that.lefttime - 1;
						that.getdjs();
					}, 1000);
				}
				that.loaded();
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
		changePrice:function(){
			this.$refs.dialogChangePrice.open();
		},
		changePriceConfirm: function (done, val) {
			this.$refs.dialogChangePrice.close();
			var that = this
			app.post('ApiAdminOrder/changePrice', { type:'shop',orderid: that.detail.id,val:val }, function (res) {
				app.success(res.msg);
				setTimeout(function () {
					that.getdata();
				}, 1000)
			})
    },

		orderCollect: function (e) {
			var that = this;
			var orderid = e.currentTarget.dataset.id;
			var index = e.currentTarget.dataset.index;
			app.confirm('确定要收货吗?', function () {
				app.showLoading('提交中');
				app.post('ApiAgent/downorderCollect', {orderid: orderid}, function (data) {
					app.showLoading(false);
					app.success(data.msg);
					setTimeout(function () {
						that.getdata();
					}, 1000);
				});
			});
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
.product .content .detail .t1{font-size:26rpx;line-height:36rpx;margin-bottom:10rpx;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:2;overflow:hidden;}
.product .content .detail .t2{height: 46rpx;line-height: 46rpx;color: #999;overflow: hidden;font-size: 26rpx;}
.product .content .detail .t3{display:flex;height:40rpx;line-height:40rpx;color: #ff4246;}
.product .content .detail .x1{ flex:1}
.product .content .detail .x2{ width:100rpx;font-size:32rpx;text-align:right;margin-right:8rpx}
.product .content .comment{position:absolute;top:64rpx;right:10rpx;border: 1px #ffc702 solid; border-radius:10rpx;background:#fff; color: #ffc702;  padding: 0 10rpx; height: 46rpx; line-height: 46rpx;}
.product .content .comment2{position:absolute;top:64rpx;right:10rpx;border: 1px #ffc7c2 solid; border-radius:10rpx;background:#fff; color: #ffc7c2;  padding: 0 10rpx; height: 46rpx; line-height: 46rpx;}

.orderinfo{width:94%;margin:0 3%;border-radius:8rpx;margin-top:16rpx;margin-top:10rpx;padding: 14rpx 3%;background: #FFF;}
.orderinfo .item{display:flex;width:100%;padding:20rpx 0;border-bottom:1px dashed #ededed;}
.orderinfo .item:last-child{ border-bottom: 0;}
.orderinfo .item .t1{width:200rpx;}
.orderinfo .item .t2{flex:1;text-align:right}
.orderinfo .item .red{color:red}

.bottom{ width: 100%;height:calc(92rpx + env(safe-area-inset-bottom));padding: 0 20rpx;background: #fff; position: fixed; bottom: 0px;left: 0px;display:flex;justify-content:flex-end;align-items:center;}

.btn1{margin-left:20rpx;width:160rpx;height:60rpx;line-height:60rpx;color:#fff;border-radius:3px;text-align:center}
.btn2{margin-left:20rpx;width:160rpx;height:60rpx;line-height:60rpx;color:#333;background:#fff;border:1px solid #cdcdcd;border-radius:3px;text-align:center}
.btn3{position:absolute;top:60rpx;right:10rpx;font-size:24rpx;width:120rpx;height:50rpx;line-height:50rpx;color:#333;background:#fff;border:1px solid #cdcdcd;border-radius:3px;text-align:center}

.btitle{ width:100%;height:100rpx;background:#fff;padding:0 20rpx;border-bottom:1px solid #f5f5f5}
.btitle .comment{border: 1px #ffc702 solid;border-radius:10rpx;background:#fff; color: #ffc702;  padding: 0 10rpx; height: 46rpx; line-height: 46rpx;}
.btitle .comment2{border: 1px #ffc7c0 solid;border-radius:10rpx;background:#fff; color: #ffc7c0;  padding: 0 10rpx; height: 46rpx; line-height: 46rpx;}

.picker{display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:1;overflow:hidden;}

.uni-popup-dialog {width: 300px;border-radius: 5px;background-color: #fff;}
.uni-dialog-title {display: flex;flex-direction: row;justify-content: center;padding-top: 15px;padding-bottom: 5px;}
.uni-dialog-title-text {font-size: 16px;font-weight: 500;}
.uni-dialog-content {display: flex;flex-direction: row;justify-content: center;align-items: center;padding: 5px 5px 15px 5px;}
.uni-dialog-content-text {font-size: 14px;color: #6e6e6e;}
.uni-dialog-button-group {display: flex;flex-direction: row;border-top-color: #f5f5f5;border-top-style: solid;border-top-width: 1px;}
.uni-dialog-button {display: flex;flex: 1;flex-direction: row;justify-content: center;align-items: center;height: 45px;}
.uni-border-left {border-left-color: #f0f0f0;border-left-style: solid;border-left-width: 1px;}
.uni-dialog-button-text {font-size: 14px;}
.uni-button-color {color: #007aff;}
.danhao-input-view{border: 1px #eee solid;display: flex;align-items: center;flex: 1;}
.danhao-input-view image{width: 60rpx;height: 60rpx;}

.layui-imgbox{margin-right:16rpx;margin-bottom:10rpx;font-size:24rpx;position: relative;}
.layui-imgbox-img{display: block;width:200rpx;height:200rpx;padding:2px;border: #d3d3d3 1px solid;background-color: #f6f6f6;overflow:hidden}
.layui-imgbox-img>image{max-width:100%;}
.layui-imgbox-repeat{position: absolute;display: block;width:32rpx;height:32rpx;line-height:28rpx;right: 2px;bottom:2px;color:#999;font-size:30rpx;background:#fff}
.uploadbtn{position:relative;height:200rpx;width:200rpx}
.glassitem{background:#f5f5f5;display: flex;align-items: center;padding: 10rpx 0;font-size: 24rpx;}
.glassitem .gcontent{flex:1;padding: 0 20rpx;}
.glassheader{line-height: 50rpx;font-size: 26rpx;font-weight: 600;}
.glassrow{line-height: 40rpx;font-size: 26rpx;}
.glassrow .glasscol{min-width: 25%;text-align: center;}
.glassitem .bt{border-top:1px solid #e3e3e3}
.pdl10{padding-left: 10rpx;}
.item .txt{color:#666;margin-top:20rpx;font-size:26rpx;text-align:center}

.pstime-item{display:flex;border-bottom: 1px solid #f5f5f5;padding:20rpx 30rpx;}
.pstime-item .radio{flex-shrink:0;width: 32rpx;height: 32rpx;background: #FFFFFF;border: 2rpx solid #BFBFBF;border-radius: 50%;margin-right:30rpx}
.pstime-item .radio .radio-img{width:100%;height:100%}
</style>