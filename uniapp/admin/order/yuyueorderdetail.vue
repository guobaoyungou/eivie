<template>
<view class="container">
	<block v-if="isload">
		<view class="ordertop flex" :style="'background:url('+pre_url+'/static/img/orderbg.png);background-size:100%'">
			<view class="f1 " v-if="detail.status==0">
				<view class="t1">等待买家付款</view>
				<view class="t2" v-if="djs">剩余时间：{{djs}}</view>				
			</view>
			<view class="f1" v-if="detail.status==1 && detail.refund_status==0">
				<block v-if="!detail.worker_id">
					<view class="t2">等待派单</view>
				</block>
				<block v-else>
					<view class="t2">订单已接单</view>
				</block>
			</view>
			
			<view class="f1" v-if="detail.status==2">
				<view class="t1">订单服务中</view>
			</view>
			<view class="f1" v-if="detail.status==3">
				<view class="t1">订单已完成</view>
			</view>
			<view class="f1" v-if="detail.status==4">
				<view class="t1">订单已取消</view>
			</view>
			<view class="orderx"><image :src="pre_url+'/static/img/orderx.png'"></view>
			
		</view>
		
		<view class="orderinfo orderinfotop">
			<view class="title">订单信息</view>
			<view class="item">
				<text class="t1">订单编号</text>
				<text class="t2" user-select="true" selectable="true">{{detail.ordernum}}</text>
			</view>
			<view class="item">
				<text class="t1">下单时间</text>
				<text class="t2">{{detail.createtime}}</text>
			</view>
			<view class="item">
				<text class="t1">预约时间</text>
				<text class="t2">{{detail.yy_time}}</text>
			</view>
		</view>
		<view class="orderinfo" v-if="(detail.formdata).length > 0">
			<view class="item" v-for="(item,index) in detail.formdata" :key="index">
				<text class="t1">{{item[0]}}</text>
				<view class="t2" v-if="item[2]=='upload'"><image :src="item[1]" style="width:400rpx;height:auto" mode="widthFix" @tap="previewImage" :data-url="item[1]"/></view>
				<view class="t2" v-else-if="item[2]=='upload_video'"><video :src="item[1]" style="width: 100%;"/></video></view>
				<view class="t2" v-else-if="item[2]=='upload_pics'">
					<block v-for="vv in item[1]" :key='kk'>
						<image :src="vv" style="width:200rpx;height:auto;margin-right: 10rpx;" mode="widthFix" @tap="previewImage" :data-url="vv"/>
					</block>
				</view>
				<text class="t2" v-else user-select="true" selectable="true">{{item[1]}}</text>
			</view>
		</view>
		
		<!-- <view class="btitle flex-y-center" v-if="detail.bid>0" @tap="goto" :data-url="'/pagesExt/business/index?id=' + detail.bid">
			<image :src="detail.binfo.logo" style="width:36rpx;height:36rpx;"></image>
			<view class="flex1" decode="true" space="true" style="padding-left:16rpx">{{detail.binfo.name}}</view>
		</view> -->
		<view class="product">
			<view class="title">服务信息</view>
			<view class="content">
				<view @tap="goto" :data-url="'product?id=' + prolist.proid">
					<image :src="prolist.propic"></image>
				</view>
				<view class="detail">
					<text class="t1">{{prolist.proname}}</text>
					<text class="t2">{{prolist.ggname}}</text>
					<view class="t3"><text class="x1 flex1">￥{{prolist.product_price}}</text><text class="" style="color: #939393;">×{{prolist.num}}</text></view>
				</view>
			</view>
		</view>
		
		<view class="orderinfo" v-if="(detail.status==3 || detail.status==2) && (detail.freight_type==3 || detail.freight_type==4)">
			<view class="item flex-col">
				<text class="t1" style="color:#111">发货信息</text>
				<text class="t2" style="text-align:left;margin-top:10rpx;padding:0 10rpx" user-select="true" selectable="true">{{detail.freight_content}}</text>
			</view>
		</view>
		
		<view class="orderinfo">
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

		<view class="orderinfo">

			<view class="item">
				<text class="t1">应付金额</text>
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
	
			<view class="item">
				<text class="t1">实付款</text>
				<text class="t2 red">¥{{detail.totalprice}}</text>
			</view>
			<block v-if="detail.showfeedetail && (detail.status==1 || detail.status==2 || detail.status==3)">
				<view class="item" v-if="detail.worker_id">
					<text class="t1">服务提成</text>
					<text class="t2 red">¥{{detail.ticheng}}</text>
				</view>
				<block v-if="detail.bid>0">
					<view class="item" v-if="detail.plateform_fee>0">
						<text class="t1">平台抽成</text>
						<text class="t2 red">¥{{detail.plateform_fee}}</text>
					</view>
					<view class="item"v-if="detail.js_totalprice>0 && detail.totalprice!=detail.js_totalprice">
						<text class="t1">预估结算</text>
						<text class="t2 red">¥{{detail.js_totalprice}}</text>
					</view>
				</block>
			</block>
			<view class="item">
				<text class="t1">订单状态</text>
				<text class="t2" v-if="detail.status==0">未付款</text>
				<text class="t2" v-if="detail.status==1">已付款</text>
				<text class="t2" v-if="detail.status==2">服务中</text>
				<text class="t2" v-if="detail.status==3">已完成</text>
				<text class="t2" v-if="detail.status==4">已关闭</text>
				<text class="" v-if="detail.refundCount" style="margin-left: 8rpx;">有退款({{detail.refundCount}})</text>
			</view>
			<view class="item" v-if="detail.refundingMoneyTotal>0">
				<text class="t1">退款中</text>
				<text class="t2 red" @tap="goto" :data-url="'refundlist?orderid='+ detail.id">¥{{detail.refundingMoneyTotal}}</text>
				<text class="t3 iconfont iconjiantou" style="color:#999;font-weight:normal"></text>
			</view>
			<view class="item" v-if="detail.refundedMoneyTotal>0">
				<text class="t1">已退款</text>
				<text class="t2 red" @tap="goto" :data-url="'refundlist?orderid='+ detail.id">¥{{detail.refundedMoneyTotal}}</text>
				<text class="t3 iconfont iconjiantou" style="color:#999;font-weight:normal"></text>
			</view>
			<view class="item" v-if="detail.refund_status>0">
				<text class="t1">退款状态</text>
				<text class="t2 red" v-if="detail.refund_status==1">审核中</text>
				<text class="t2 red" v-if="detail.refund_status==2">已退款</text>
				<text class="t2 red" v-if="detail.refund_status==3">已驳回</text>
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
			<view class="item" v-if="detail.status>0 && detail.paytypeid!='4' && detail.paytime">
				<text class="t1">支付时间</text>
				<text class="t2">{{detail.paytime}}</text>
			</view>
			<view class="item" v-if="detail.paytypeid">
				<text class="t1">支付方式</text>
				<text class="t2">{{detail.paytype}}</text>
			</view>
			
			<view class="item" v-if="detail.status>1 && detail.send_time">
				<text class="t1">派单时间</text>
				<text class="t2">{{detail.send_time}}</text>
			</view>

			<view class="item" v-if="detail.status==3 && detail.collect_time">
				<text class="t1">完成时间</text>
				<text class="t2">{{detail.collect_time}}</text>
			</view>
			<view class="item" v-if="detail.addmoney>0">
				<text class="t1">补差价</text>
				<text class="t2 red">¥{{detail.addmoney}}</text>
			</view>
      
      <view class="item">
      	<text class="t1">服务方式</text>
      	<text class="t2">
          <block v-if="detail.fwtype==2">
             {{text['上门服务']}}
          </block>
          <block v-if="detail.fwtype==3">
            到商家服务
          </block>
          <block v-if="detail.fwtype==1">
             {{text['到店服务']}}
          </block>
        </text>
      </view>
      <block v-if="detail.fwbid && detail.fwbinfo">
        <view class="item">
        	<text class="t1">商家名称</text>
        	<text class="t2">{{detail.fwbinfo.name}}</text>
        </view>
        <view class="item" v-if="detail.fwbinfo.address">
        	<view class="t1">商家地址</view>
        	<view v-if="!detail.fwbinfo.latitude || !detail.fwbinfo.longitude" class="t2">
            {{detail.fwbinfo.address}}
          </view>
          <view v-else @tap="openLocation" :data-latitude="detail.fwbinfo.latitude" :data-longitude="detail.fwbinfo.longitude" class="t2">
            {{detail.fwbinfo.address}}
          </view>
        </view>
      </block>
		</view>
		
		<view class="orderinfo">
			<view class="title">顾客信息</view>
			<view class="item">
				<text class="t1">姓名</text>
				<text class="t2">{{detail.linkman}}</text>
			</view>
			<view class="item">
				<text class="t1">手机号</text>
				<text class="t2" @tap="goto" :data-url="'tel:'+detail.tel">{{detail.tel}}</text>
			</view>
			<view class="item" v-if="detail.fwtype==2">
				<text class="t1">上门地址</text>
				<text class="t2" @tap="copy" :data-text="detail.area2+detail.address">{{detail.area2}}{{detail.address}}</text>
			</view>
		</view>
		
			
		
		<view style="width:100%;height:120rpx"></view>

		<view class="bottom">
			<view v-if="detail.refund_status==1" class="btn2" @tap="refundnopass" :data-id="detail.id">退款驳回</view>
			<view v-if="detail.refund_status==1" class="btn2" @tap="refundpass" :data-id="detail.id">退款通过</view>
			<view v-if="detail.status==0" class="btn2" @tap="closeOrder" :data-id="detail.id">关闭订单</view>
			<view v-if="detail.status==0 && detail.bid==0" class="btn2" @tap="ispay" :data-id="detail.id">改为已支付</view>
			<view v-if="detail.status==1 && detail.can_paidan && !detail.worker_id && showlist" class="btn2" @tap="goto" :data-url="'/admin/yuyue/selectworker?id='+detail.id" >派单</view>
			<view v-if="detail.status==1 && detail.worker_id && showlist" class="btn2" @tap="goto" :data-url="'/admin/yuyue/selectworker?id='+detail.id+'&type=update'" >改派</view>
			<view v-else-if="detail.status==1 && !detail.worker_id && !showlist" class="btn2" @tap="peisong" :data-id="detail.id">派单</view>

			<block v-if="(detail.status==2 || detail.status==3 || detail.worker_id>0) &&   detail.status!=4">
				<view class="btn2" @tap="logistics" :data-express_com="detail.express_com" :data-express_no="detail.worker_orderid"  >查进度</view>
			</block>
			<view class="btn2" @tap="setremark" :data-id="detail.id">设置备注</view>
			<block v-if="detail.status==3 || detail.status==4">
				<view class="btn2" @tap="todel" :data-id="detail.id">删除订单</view>
			</block>
		
		</view>
		
		
		<uni-popup id="dialogSetremark" ref="dialogSetremark" type="dialog">
			<uni-popup-dialog mode="input" title="设置备注" :value="detail.remark" placeholder="请输入备注" @confirm="setremarkconfirm"></uni-popup-dialog>
		</uni-popup>
	
		<uni-popup id="dialogPeisong" ref="dialogPeisong" type="dialog">
			<view class="uni-popup-dialog">
				<view class="uni-dialog-title">
					<text class="uni-dialog-title-text">请选择配送员</text>
				</view>
				<view class="uni-dialog-content">
					<view>
						<picker @change="peisongChange" :value="index2" :range="peisonguser2" style="font-size:24rpx;border: 1px #eee solid;padding:10rpx;height:70rpx;border-radius:4px;flex:1">
							<view class="picker">{{peisonguser2[index2]}}</view>
						</picker>
					</view>
				</view>
				<view class="uni-dialog-button-group">
					<view class="uni-dialog-button" @click="dialogPeisongClose">
						<text class="uni-dialog-button-text">取消</text>
					</view>
					<view class="uni-dialog-button uni-border-left" @click="confirmPeisong">
						<text class="uni-dialog-button-text uni-button-color">确定</text>
					</view>
				</view>
			</view>
		</uni-popup>
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
      prodata: '',
      djs: '',
      iscommentdp: "",
      detail: "",
			payorder:{},
      prolist: "",
      storeinfo: "",
      lefttime: "",
      codtxt: "",
			pay_transfer_info:{},
			invoice:0,
			selectExpressShow:false,
			express_content:'',
			peisonguser:[],
			peisonguser2:[],
			index2:0,
			showlist:false,
			text:{}
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
			app.get('ApiAdminOrder/yuyueorderdetail', {id: that.opt.id}, function (res) {
				that.loading = false;
				that.iscommentdp = res.iscommentdp,
				that.detail = res.detail;
				that.prolist = res.prolist;
				that.storeinfo = res.storeinfo;
				that.lefttime = res.lefttime;
				that.codtxt = res.codtxt;
				that.pay_transfer_info =  res.pay_transfer_info;
				that.payorder = res.payorder;
				that.invoice = res.invoice;
				that.showlist = res.showlist
				that.text = res.text
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
	setremark:function(){
		this.$refs.dialogSetremark.open();
	},
	setremarkconfirm: function (done, remark) {
		this.$refs.dialogSetremark.close();
		var that = this
		app.post('ApiAdminOrder/setremark', { type:'yuyue',orderid: that.detail.id,content:remark }, function (res) {
			app.success(res.msg);
			setTimeout(function () {
				that.getdata();
			}, 1000)
		})
	},
    todel: function (e) {
      var that = this;
      var orderid = e.currentTarget.dataset.id;
      app.confirm('确定要删除该订单吗?', function () {
				app.showLoading('删除中');
        app.post('ApiYuyue/delOrder', {orderid: orderid}, function (data) {
					app.showLoading(false);
          app.success(data.msg);
          setTimeout(function () {
            app.goback(true);
          }, 1000);
        });
      });
    },
	  refundnopass: function (e) {
		var that = this;
		var orderid = e.currentTarget.dataset.id
		app.confirm('确定要驳回退款申请吗?', function () {
			app.showLoading('提交中');
			app.post('ApiAdminOrder/refundnopass', { type:'yuyue',orderid: orderid }, function (data) {
				app.showLoading(false);
				app.success(data.msg);
				setTimeout(function () {
					that.getdata();
				}, 1000)
			})
		});
	  },
	  refundpass: function (e) {
		var that = this;
		var orderid = e.currentTarget.dataset.id
		app.confirm('确定要审核通过并退款吗?', function () {
			app.showLoading('提交中');
			app.post('ApiAdminOrder/refundpass', { type:'yuyue',orderid: orderid }, function (data) {
				app.showLoading(false);
				app.success(data.msg);
				setTimeout(function () {
					that.getdata();
				}, 1000)
			})
		});
	  },
    orderCollect: function (e) {
      var that = this;
      var orderid = e.currentTarget.dataset.id;
      app.confirm('确定已完成服务吗?', function () {
				app.showLoading('确认中');
        app.post('ApiYuyue/orderCollect', {orderid: orderid}, function (data) {
					app.showLoading(false);
          app.success(data.msg);
          setTimeout(function () {
            that.getdata();
          }, 1000);
        });
      });
    },
	peisong:function(){
		var that = this;
		that.loading = true;
		app.post('ApiAdminOrder/getyuyuepsuser',{type:'yuyue_order',orderid:that.detail.id},function(res){
			that.loading = false;
      if(res.status != 1){
        app.alert(res.msg);
        return;
      }
			var peisonguser = res.peisonguser
			var paidantype = res.paidantype
			var psfee = res.psfee
			var ticheng = res.ticheng
	
			var peisonguser2 = [];
			for(var i in peisonguser){
				peisonguser2.push(peisonguser[i].title);
			}
			that.peisonguser = res.peisonguser;
			that.peisonguser2 = peisonguser2;
			if(paidantype==1){
				that.$refs.dialogPeisong.open();
			}else{
				if(that.detail.bid == 0){
					var tips='选择服务人员抢单，订单将发布到抢单大厅由服务人员单，服务人员提成￥'+ticheng+'，确定要服务人员抢单吗？';
				}else{
					var tips='选择服务人员抢单，订单将发布到抢单大厅由服务人员单，需扣除服务费￥'+psfee+'，确定要服务人员抢单吗？';
				}
				if(paidantype == 2){
					var psid = '-1';
				}else{
					var psid = '0';
				}
				app.confirm(tips,function(){
					app.showLoading('提交中...');
					app.post('ApiAdminOrder/yuyuepeisong', { type:'yuyue_order',orderid: that.detail.id,worker_id:psid}, function (res2) {
						app.showLoading(false);
            if(res2.status != 1){
              app.alert(res2.msg);
              return;
            }
						app.success(res2.msg);
						setTimeout(function () {
							that.getdata();
						}, 1000)
					})
				})
			}
		})
	},
	dialogPeisongClose:function(){
		this.$refs.dialogPeisong.close();
	},
	peisongChange:function(e){
		this.index2 = e.detail.value;
	},
	confirmPeisong:function(){
		var that = this
		var psid = this.peisonguser[this.index2].id
    app.showLoading('提交中...');
		app.post('ApiAdminOrder/yuyuepeisong', { type:'yuyue_order',orderid: that.detail.id,worker_id:psid}, function (res) {
      app.showLoading(false);
      if(res.status != 1){
        app.alert(res.msg);
        return;
      }
			app.success(res.msg);
			that.$refs.dialogPeisong.close();
			setTimeout(function () {
				that.getdata();
			}, 1000)
		})
	},
		showhxqr:function(){
			this.$refs.dialogHxqr.open();
		},
		closeHxqr:function(){
			this.$refs.dialogHxqr.close();
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
		
		logistics:function(e){
			var express_com = e.currentTarget.dataset.express_com
			var express_no = e.currentTarget.dataset.express_no
			app.goto('/activity/yuyue/logistics?express_no=' + express_no);
		},
		hideSelectExpressDialog:function(){
			this.$refs.dialogSelectExpress.close();
		},
		ispay:function(e){
			var that = this;
			var orderid = e.currentTarget.dataset.id
			app.confirm('确定要改为已支付吗?', function () {
				app.showLoading('提交中');
				app.post('ApiAdminOrder/ispay', { type:'yuyue',orderid: orderid }, function (data) {
					app.showLoading(false);
					app.success(data.msg);
					setTimeout(function () {
						that.getdata();
					}, 1000)
				})
			});
		},
		closeOrder: function (e) {
			var that = this;
			var orderid = e.currentTarget.dataset.id
			app.confirm('确定要关闭该订单吗?', function () {
				app.showLoading('提交中');
				app.post('ApiAdminOrder/closeOrder', { type:'yuyue',orderid: orderid }, function (data) {
					app.showLoading(false);
					app.success(data.msg);
					setTimeout(function (){
						that.getdata();
					}, 1000)
				});
			})
		},
  }
};
</script>
<style>
	.text-min { font-size: 24rpx; color: #999;}
.ordertop{width:100%;height:452rpx;padding:50rpx 0 0 70rpx; justify-content: space-between;}
.ordertop .f1{color:#fff}
.ordertop .f1 .t1{font-size:40rpx;height:60rpx;line-height:60rpx;}
.ordertop .f1 .t2{font-size:26rpx; margin-top: 20rpx;}

.container .orderinfotop{ position: relative; margin-top: -200rpx;}

.address{ display:flex;width: 100%; padding: 20rpx 3%; background: #FFF;}
.address .img{width:40rpx}
.address image{width:40rpx; height:40rpx;}
.address .info{flex:1;display:flex;flex-direction:column;}
.address .info .t1{font-size:28rpx;font-weight:bold;color:#333}
.address .info .t2{font-size:24rpx;color:#999}

.product{width:96%;margin:0 2%;border-radius:8rpx;margin-top:16rpx;padding: 14rpx 3%;background: #FFF;}
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

.orderinfo{width:96%;margin:0 2%;border-radius:8rpx;margin-top:16rpx;padding: 14rpx 3%;background: #FFF;}
.orderinfo .title,.product .title{ font-weight: bold; font-size: 30rpx; line-height: 60rpx; margin-bottom: 15rpx;}
.orderinfo .item{display:flex;width:100%;padding:20rpx 0;border-bottom:1px dashed #ededed;overflow:hidden}
.orderinfo .item:last-child{ border-bottom: 0;}
.orderinfo .item .t1{width:200rpx;flex-shrink:0}
.orderinfo .item .t2{flex:1;text-align:right}
.orderinfo .item .t3{ margin-top: 3rpx;}
.orderinfo .item .red{color:red}

.bottom{ width: 100%; padding: 16rpx 20rpx;background: #fff; position: fixed; bottom: 0px;left: 0px;display:flex;justify-content:flex-end;align-items:center;z-index: 1;}

.btn1{margin-left:20rpx;min-width:160rpx;padding: 0 20rpx;height:60rpx;line-height:60rpx;color:#fff;border-radius:3px;text-align:center;}
.btn2{margin-left:20rpx;width:160rpx;height:60rpx;line-height:60rpx;color:#333;background:#fff;border:1px solid #cdcdcd;border-radius:3px;text-align:center;}
.btn3{position:absolute;top:60rpx;right:10rpx;font-size:24rpx;width:120rpx;height:50rpx;line-height:50rpx;color:#333;background:#fff;border:1px solid #cdcdcd;border-radius:3px;text-align:center}

.btitle{ width:100%;height:100rpx;background:#fff;padding:0 20rpx;border-bottom:1px solid #f5f5f5}
.btitle .comment{border: 1px #ffc702 solid;border-radius:10rpx;background:#fff; color: #ffc702;  padding: 0 10rpx; height: 46rpx; line-height: 46rpx;}
.btitle .comment2{border: 1px #ffc7c0 solid;border-radius:10rpx;background:#fff; color: #ffc7c0;  padding: 0 10rpx; height: 46rpx; line-height: 46rpx;}

.hxqrbox{background:#fff;padding:50rpx;position:relative;border-radius:20rpx}
.hxqrbox .img{width:400rpx;height:400rpx}
.hxqrbox .txt{color:#666;margin-top:20rpx;font-size:26rpx;text-align:center}
.hxqrbox .close{width:50rpx;height:50rpx;position:absolute;bottom:-100rpx;left:50%;margin-left:-25rpx;border:1px solid rgba(255,255,255,0.5);border-radius:50%;padding:8rpx}

.orderx image{ width:124rpx ; height: 124rpx; margin-right: 60rpx;}


.uni-popup-dialog {width: 300px;border-radius: 5px;background-color: #fff;}
.uni-dialog-title {display: flex;flex-direction: row;justify-content: center;padding-top: 15px;padding-bottom: 5px;}
.uni-dialog-title-text {font-size: 16px;font-weight: 500;}
.uni-dialog-content {display: flex;flex-direction: row;justify-content: center;align-items: center;padding: 5px 15px 15px 15px;}
.uni-dialog-content-text {font-size: 14px;color: #6e6e6e;}
.uni-dialog-button-group {display: flex;flex-direction: row;border-top-color: #f5f5f5;border-top-style: solid;border-top-width: 1px;}
.uni-dialog-button {display: flex;flex: 1;flex-direction: row;justify-content: center;align-items: center;height: 45px;}
.uni-border-left {border-left-color: #f0f0f0;border-left-style: solid;border-left-width: 1px;}
.uni-dialog-button-text {font-size: 14px;}
.uni-button-color {color: #007aff;}
</style>