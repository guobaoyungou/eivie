<template>
<view class="container">
	<block v-if="isload">
		<view class="ordertop" :style="'background:url(' + pre_url + '/static/img/ordertop.png);background-size:100%'">
			<view class="f1" v-if="detail.status==0">
				<view class="t1">等待买家付款</view>
				<view class="t2" v-if="djs">剩余时间：{{djs}}</view>
			</view>
			<view class="f1" v-if="detail.status==1">
				<view class="t1">'已成功付款'</view>
				<view class="t2">请尽快确认</view>
			</view>
			<view class="f1" v-if="detail.status==2">
				<view class="t1">已确认</view>
				<view class="t2" >待入住</view>
			</view>
			<view class="f1" v-if="detail.status==3">
				<view class="t1">已到店</view>
				<view class="t2" >入住信息：{{detail.linkname}} {{detail.tel}}</view>
			</view>
			<view class="f1" v-if="detail.status==4">
				<view class="t1">已离店</view>
			</view>
			<view class="f1" v-if="detail.status==5">
				<view class="t1">已完成</view>
			</view>
			<view class="f1" v-if="detail.status==-1">
				<view class="t1">订单已取消</view>
			</view>
		</view>

		<view class="product">
			<view class="content">
				<view>
					<image :src="detail.pic"></image>
				</view>
				<view class="detail">
					<text class="t1">{{hotel.name}}</text>
					<text class="t2">{{detail.title}}</text>
					<view class="t3">
						<text class="x1 flex1" >￥{{detail.sell_price}}</text>
					</view>
				</view>
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
			
			<view class="item">
				<text class="t1">入住姓名</text>
				<text class="t2">{{detail.linkman}}</text>
			</view>
			<view class="item">
				<text class="t1">联系电话</text>
				<text class="t2">{{detail.tel}}</text>
			</view>
			<view class="item">
				<text class="t1">入住日期</text>
				<text class="t2">{{detail.in_date}}</text>
			</view>
			<view class="item">
				<text class="t1">离店日期</text>
				<text class="t2">{{detail.leave_date}}</text>
			</view>
			<view class="item" >
				<text class="t1">入住人数</text>
				<text class="t2">{{detail.totalnum}}</text>
			</view>
		</view>
		<view class="orderinfo" v-if="detail.remark">
			<view class="item">
				<text class="t1">备注</text>
				<text class="t2">{{detail.remark}}</text>
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
			<view class="item" v-if="detail.status>1 && detail.daodian_time">
				<text class="t1">到店时间</text>
				<text class="t2">{{detail.daodian_time}}</text>
			</view>
			<view class="item" v-if="detail.status==4 && detail.real_leavedate">
				<text class="t1">实际离店日期</text>
				<text class="t2">{{detail.real_leavedate}}</text>
			</view>
		</view>		
		<view class="orderinfo" v-if="detail.isbefore==1">
			<view class="item" v-if="detail.fuwu_refund_money>0">
				<text class="t1">{{text['服务费']}}退款</text>
				<text class="t2 red">-¥{{detail.fuwu_refund_money}}</text>
			</view>
			<view class="item" >
				<text class="t1">实际支付房费</text>
				<block v-if="detail.real_usemoney>0 && detail.real_roomprice>0">
					<text class="t2 red" >¥{{detail.real_roomprice}} + {{detail.real_usemoney}}{{t('余额单位')}}</text>
				</block>
				<block v-if="detail.real_usemoney>0 && detail.real_roomprice==0">
					<text class="t2 red"> {{detail.real_usemoney}}{{t('余额单位')}}</text>
				</block>
				<block v-if="detail.real_usemoney==0 && d.order.real_roomprice>0">
					<text class="t2 red">¥{{detail.real_roomprice}}</text>
				</block>
			</view>
		</view>
		
		<view class="orderinfo" v-if="detail.yajin_money>0 && detail.status>2">
	
			<view class="item flex-bt">
				<text class="t1">押金状态</text>
				<view class="ordernum-info flex-bt">
					<text class="t2" v-if="detail.yajin_refund_status==0">待申请</text>
					<text class="t2" v-if="detail.yajin_refund_status==1">审核中</text>
					<text class="t2" v-if="detail.yajin_refund_status==2">已退款</text>
					<text class="t2" v-if="detail.yajin_refund_status==3">已驳回</text>
				</view>
			</view>
			<view class="item flex-bt" v-if="detail.yajin_refund_status==3">
				<text class="t1">驳回原因</text>
				<view class="ordernum-info flex-bt">
					<text class="t2" >{{detail.yajin_refund_reason?detail.yajin_refund_reason:'无'}}</text>
		
				</view>
			</view>
		</view>
		
		<view class="orderinfo">
			<view class="item">
				<text class="t1">房费金额</text>
				<text class="t2 red" v-if="detail.use_money>0 && detail.leftmoney>0">{{detail.use_money}}{{t('余额单位')}} + ￥{{detail.leftmoney}}</text>
				<text class="t2 red" v-else-if="detail.use_money>0 && detail.leftmoney==0">{{detail.use_money}}{{t('余额单位')}}</text>
				<text class="t2 red" v-else>￥{{detail.sell_price}}</text>
			</view>
			<view class="item" v-if="detail.fuwu_money>0">
				<text class="t1">{{text['服务费']}}</text>
				<text class="t2 red">+¥{{detail.fuwu_money}}</text>
			</view>
			
			<view class="item" v-if="detail.yajin_money>0">
				<text class="t1">押金 </text>
				<text class="t2 red">+¥{{detail.yajin_money}}

				</text>
			</view>
			<view class="item" v-else>
				<text class="t1">押金 (免押)</text>
				<text class="t2 red">+¥{{detail.yajin_money}}
			
				</text>
			</view>
			
			<view class="item" v-if="detail.leveldk_money > 0">
				<text class="t1">{{t('会员')}}折扣</text>
				<text class="t2 red">-¥{{detail.leveldk_money}}</text>
			</view>
			<view class="item" v-if="detail.jianmoney > 0">
				<text class="t1">满减活动</text>
				<text class="t2 red">-¥{{detail.manjian_money}}</text>
			</view>

			<view class="item" v-if="detail.couponmoney > 0">
				<text class="t1">{{t('优惠券')}}抵扣</text>
				<text class="t2 red">-¥{{detail.coupon_money}}</text>
			</view>
			
			<view class="item" v-if="detail.scoredk_money > 0">
				<text class="t1">{{t('积分')}}抵扣</text>
				<text class="t2 red">-¥{{detail.scoredk_money}}</text>
			</view>
			<view class="item" v-if="detail.use_money > 0">
				<text class="t1">{{t('余额')}}抵扣</text>
				<text class="t2 red">-{{detail.use_money}}{{t('余额单位')}}</text>
			</view>
      <view class="item" v-if="detail.upgradescoredk_money > 0">
      	<text class="t1">{{text['升级积分']}}抵扣</text>
      	<text class="t2 red">-{{detail.upgradescoredk_money}}</text>
      </view>
			<view class="item">
				<text class="t1">支付金额</text>
				<text class="t2 red">¥{{detail.totalprice}}</text>
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

			<view class="item">
				<text class="t1">下单备注</text>
				<text class="t2 red">{{detail.message ? detail.message : '无'}}</text>
			</view>
		
			<view class="item flex-col" v-if="(detail.status==1 || detail.status==2)">
				<text class="t1">核销码</text>
				<view class="flex-x-center">
					<image :src="detail.hexiao_qr" style="width:400rpx;height:400rpx" @tap="previewImage" :data-url="detail.hexiao_qr"></image>
				</view>
			</view>
		</view>

		<view style="width:100%;height:calc(160rpx + env(safe-area-inset-bottom));"></view>

		<view class="bottom notabbarbot">
			<view v-if="detail.refund_status==1" class="btn2" @tap="refundnopass" :data-id="detail.id">退款驳回</view>
			<view v-if="detail.refund_status==1" class="btn2" @tap="refundpass" :data-id="detail.id">退款通过</view>
			<view v-if="detail.status==0" class="btn2" @tap="closeOrder" :data-id="detail.id">关闭订单</view>
		
			<view v-if="detail.status==1" class="btn2" @tap="confirmorder" :data-id="detail.id">确认订单</view>
			<view v-if="detail.status==2" class="btn2" @tap="qrdaodian" :data-id="detail.id">确认到店</view>
			<view v-if="detail.status==3" class="btn2" @tap="qrlidian" :data-id="detail.id">确认离店</view>
			<!--<view v-if="detail.status==4" class="btn2" @tap="delOrder" :data-id="detail.id">删除</view>-->
			<view v-if="detail.status==4 && !detail.yajin_refund_status && detail.yajin_money>0" class="btn2" @tap="refundYajin" :data-id="detail.id">退押金</view>
			<view class="btn2" @tap="setremark" :data-id="detail.id">设置备注</view>
		</view>
		<uni-popup id="dialogSetremark" ref="dialogSetremark" type="dialog">
			<uni-popup-dialog mode="input" title="设置备注" :value="detail.remark" placeholder="请输入备注" @confirm="setremarkconfirm"></uni-popup-dialog>
		</uni-popup>

		
		<uni-popup id="dialogLeave" ref="dialogLeave" type="dialog">
			<view class="uni-popup-dialog">
				<view class="uni-dialog-title">
					<text class="uni-dialog-title-text">确认离店</text>
				</view>
				<view class="uni-dialog-content">
          <view class="remark" style="margin-bottom: 20rpx;">
            订单支付金额：<text style="color:#000">{{totalprice}}</text>元 押金：<text style="color:#000">{{yajin_money}}元</text>
          </view>
					<view class="uni-list-cell-db">
						<label>离店日期</label>
		        <view style="display: flex;">
		          <picker mode="date" :value="date" :start="startDate" :end="endDate" @change="bindDateChange">
		            <view class="bordercss">{{date}}</view>
		          </picker>
		          <picker mode="time" :value="real_leavehour"  @change="bindHourChange">
		            <view class="bordercss" style="width: 110rpx;margin-left: 10rpx;">{{real_leavehour}}</view>
		          </picker>
		        </view>
					</view>
		      <view class="remark">离店日期格式：{{nowday}}</view>
		      <view class="remark">
		        <view>注意：</view>
		        <view>离店时间{{leaveroomhour}}整及{{leaveroomhour}}之前，为标准退房、退费；</view>
		        <view>若离店时间超过{{leaveroomhour}}时间后退房，则会<text style="color:#000">加收</text>一日及费用；</view>
		        <view>选择完离店日期后，退费自动计算，可手动修改；</view>
		        <view>离店时间{{leaveroomhour}}在[{{text['酒店']}}]-[{{text['酒店']}}设置]-[退房时间]里设置</view>
		      </view>
		      
		      <view class="uni-list-cell-db" style="margin-top: 10rpx;">
		      	<label>退房费</label>
		        <view style="display: flex;">
		          <input type="text" :value="real_refundprice" @input="setLeavefield" data-field='real_refundprice' class="bordercss"/>
		          <text style="margin-left: 10rpx;">元</text>
		        </view>
		      </view>
		      <view class="remark">房费金额：<text style="color:#000">{{leftmoney}}</text>元</view>
		      
		      <block v-if="fuwu_money && fuwu_money > 0">
		        <view  class="uni-list-cell-db" style="margin-top: 10rpx;">
		          <label>退{{text['服务费']}}</label>
		          <view style="display: flex;">
		            <input type="text" :value="real_refund_fuwumoney" @input="setLeavefield" data-field='real_refund_fuwumoney' class="bordercss"/>
		            <text style="margin-left: 10rpx;">元</text>
		          </view>
		        </view>
		        <view class="remark">{{text['服务费']}}：<text style="color:#000">{{fuwu_money}}</text>元</view>
		      </block>
		      
		      <block v-if="use_money && use_money > 0">
		        <view class="uni-list-cell-db" style="margin-top: 10rpx;">
		          <label>退{{t('余额')}}费</label>
		          <view style="display: flex;">
		            <input type="text" :value="real_refundmoney" @input="setLeavefield" data-field='real_refundmoney' class="bordercss"/>
		            <text style="margin-left: 10rpx;">元</text>
		          </view>
		        </view>
		        <view class="remark">{{t('余额')}}抵扣：<text style="color:#000">{{use_money}}</text>元</view>
		      </block>
		
		      <block v-if="canRefundUpgradescore && upgradescore && upgradescore > 0 && upgradescore_status && upgradescore_status==1">
		        <view class="uni-list-cell-db" style="margin-top: 10rpx;">
		          <label>退{{text['升级积分']}}费</label>
		          <view style="display: flex;">
		            <input type="text" :value="real_refund_upgradescore" @input="setLeavefield" data-field='real_refund_upgradescore' class="bordercss"/>
		            <text style="margin-left: 10rpx;width: 28rpx;"></text>
		          </view>
		        </view>
		        <view class="remark">{{text['升级积分']}}抵扣：<text style="color:#000">{{upgradescore}}</text></view>
		      </block>
		
				</view>
				<view class="uni-dialog-button-group">
					<view class="uni-dialog-button" @click="dialogLeaveClose">
						<text class="uni-dialog-button-text">取消</text>
					</view>
					<view class="uni-dialog-button uni-border-left" @click="confirmleave">
						<text class="uni-dialog-button-text uni-button-color">确定</text>
					</view>
				</view>
			</view>
		</uni-popup>
        
    
	</block>
	<loading v-if="loading"></loading>
	<dp-tabbar :opt="opt"></dp-tabbar>
</view>
</template>

<script>
var app = getApp();
var interval = null;

export default {
  data() {
		const currentDate = this.getDate({
			format: true
		})
    return {
        opt:{},
        loading:false,
        isload: false,
        menuindex:-1,
        pre_url:app.globalData.pre_url,
        djs: '',
        detail: "",
        hotel: "",
        lefttime: "",
        codtxt: "",
				text:[],
        
        //离店参数s
        date: "请点击选择",
        leaveroomhour:'',//退房时间
        nowday:'',//退房时间
        totalprice:0,//支付金额
        yajin_money:0,//押金金额
        leftmoney:0,//房费金额
        fuwu_money:0,//服务费
        use_money:0,//余额抵扣
        canRefundUpgradescore:0,//能否自定义退升级积分抵扣，仅平台有权限
        upgradescore_status:0,//是否使用升级积分抵扣
        upgradescore:0,//升级积分抵扣
        real_leavehour:'',//退房时间
        real_refundprice:0,//退房费
        real_refund_fuwumoney:0,//退服务费
        real_refundmoney:0,//退余额费
        real_refund_upgradescore:0,//退升级积分费
        //离店参数e
    };
  },
  computed: {
			startDate() {
					return this.getDate('start');
			},
			endDate() {
					return this.getDate('end');
			}
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
			app.get('ApiAdminHotelOrder/detail', {id: that.opt.id}, function (res) {
				that.loading = false;
				that.detail = res.detail;
				that.hotel = res.hotel;
				that.lefttime = res.lefttime;
				that.codtxt = res.codtxt;
				that.text = res.text
        that.real_leavehour = that.leaveroomhour = res.leaveroomhour;
        that.nowday = res.nowday;
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
			app.post('ApiAdminHotelOrder/setremark', { type:'hotel',orderid: that.detail.id,content:remark }, function (res) {
				app.success(res.msg);
				setTimeout(function () {
					that.getdata();
				}, 1000)
			})
    },
		qrdaodian:function(e){
			var that = this;
			var orderid = e.currentTarget.dataset.id
			app.confirm('确定用户已经到店吗?', function () {
				app.showLoading('提交中');
				app.post('ApiAdminHotelOrder/qrdaodian', { orderid: orderid }, function (data) {
					app.showLoading(false);
					app.success(data.msg);
					setTimeout(function () {
						that.getdata();
					}, 1000)
				})
			});
		},
		qrlidian:function(){
			var that=this
      that.clearleavedata();
      
			that.totalprice = that.detail['totalprice'] || 0;//支付金额
      that.yajin_money=  that.detail['totalprice'] || 0;//押金金额
      that.leftmoney =  that.detail['leftmoney'] || 0;//房费
			that.fuwu_money = that.detail['fuwu_money'] || 0;//服务费
			that.use_money = that.detail['use_money'] || 0;//余额抵扣
      that.canRefundUpgradescore = that.detail['canRefundUpgradescore'] || 0;//能否自定义退升级积分抵扣，仅平台有权限
			that.upgradescore_status = that.detail['upgradescore_status'] || 0;//是否使用升级积分抵扣
			that.upgradescore = that.detail['upgradescore'] || 0;//升级积分抵扣
			
			that.$refs.dialogLeave.open();
		},
		dialogLeaveClose:function(){
			this.$refs.dialogLeave.close();
		},
		confirmleave:function(){
			this.$refs.dialogLeave.close();
			var that = this
			app.post('ApiAdminHotelOrder/confirmleave', { 
        orderid: that.detail.id,
        real_leavedate:that.date,
        type:'leavehour',
        real_leavehour:that.real_leavehour,
        real_refundprice:that.real_refundprice,
        real_refund_fuwumoney:that.real_refund_fuwumoney,
        real_refundmoney:that.real_refundmoney,
        real_refund_upgradescore:that.real_refund_upgradescore,
      }, function (res) {
				app.success(res.msg);
				if(res.status == 1){
				  that.clearleavedata();
				  app.success(res.msg);
				  setTimeout(function () {
				  	that.getdata();
				  }, 1000)
				}else{
				  app.alret(res.msg)
				}
			})
		},
		
		ispay:function(e){
			var that = this;
			var orderid = e.currentTarget.dataset.id
			app.confirm('确定要改为已支付吗?', function () {
				app.showLoading('提交中');
				app.post('ApiAdminOrder/ispay', {type:'hotel', orderid: orderid }, function (data) {
					app.showLoading(false);
					app.success(data.msg);
					setTimeout(function () {
						that.getdata();
					}, 1000)
				})
			});
		},
		confirmorder:function(e){
			var that = this;
			var orderid = e.currentTarget.dataset.id
			app.confirm('确定确认该订单吗?', function () {
				app.showLoading('提交中');
				app.post('ApiAdminHotelOrder/confirmorder', {orderid: orderid }, function (data) {
					app.showLoading(false);
					app.success(data.msg);
					setTimeout(function () {
						that.getdata();
					}, 1000)
				})
			});
		},
		refund: function (e) {
			var that = this;
			var orderid = e.currentTarget.dataset.id
			app.confirm('确定要拒单并退款吗?', function () {
				app.showLoading('提交中');
				app.post('ApiAdminHotelOrder/judan', { type:'hotel',orderid: orderid }, function (data) {
					app.showLoading(false);
					app.success(data.msg);
					setTimeout(function () {
						that.getdata();
					}, 1000)
				})
			});
		},
		refundYajin: function (e) {
			var that = this;
			var orderid = e.currentTarget.dataset.id
			app.confirm('确定要退还押金吗?', function () {
				app.showLoading('提交中');
				app.post('ApiAdminHotelOrder/refundYajin', { type:'hotel',orderid: orderid }, function (data) {
					app.showLoading(false);
					app.success(data.msg);
					setTimeout(function () {
						that.getdata();
					}, 1000)
				})
			});
		},
		delOrder: function (e) {
			var that = this;
			var orderid = e.currentTarget.dataset.id
			app.showLoading('删除中');
			app.confirm('确定要删除该订单吗?', function () {
				app.post('ApiAdminHotelOrder/delOrder', { type:'hotel',orderid: orderid }, function (data) {
					app.showLoading(false);
					app.success(data.msg);
					setTimeout(function () {
						app.goto('takeawayorder');
					}, 1000)
				});
			})
		},
		closeOrder: function (e) {
			var that = this;
			var orderid = e.currentTarget.dataset.id
			app.confirm('确定要关闭该订单吗?', function () {
				app.showLoading('提交中');
				app.post('ApiAdminHotelOrder/closeOrder', { type:'hotel',orderid: orderid }, function (data) {
					app.showLoading(false);
					app.success(data.msg);
					setTimeout(function (){
						that.getdata();
					}, 1000)
				});
			})
		},
		refundnopass: function (e) {
			var that = this;
			var orderid = e.currentTarget.dataset.id
			app.confirm('确定要驳回退款申请吗?', function () {
				app.showLoading('提交中');
				app.post('ApiAdminOrder/refundnopass', { type:'hotel',orderid: orderid }, function (data) {
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
				app.post('ApiAdminHotelOrder/refundpass', { type:'hotel',orderid: orderid }, function (data) {
					app.showLoading(false);
					app.success(data.msg);
					setTimeout(function () {
						that.getdata();
					}, 1000)
				})
			});
		},  
		bindDateChange: function(e) {
		    var that = this;
				that.date = e.detail.value;
		    
		    var real_leavedate = e.detail.value;
		    var real_leavehour = that.real_leavehour;
		    if(real_leavedate && real_leavehour){
		      that.beforeconfirmleave(real_leavedate,real_leavehour);
		    }
		},
		bindHourChange: function(e) {
		    var that = this;
		    that.real_leavehour = e.detail.value;
		
		    var real_leavedate = that.date;
		    var real_leavehour = e.detail.value;
		    if(real_leavedate && real_leavehour){
		      that.beforeconfirmleave(real_leavedate,real_leavehour);
		    }
		},
    beforeconfirmleave:function(real_leavedate,real_leavehour){
      var that = this;
      app.showLoading();
      app.post('ApiAdminHotelOrder/beforeconfirmleave', {orderid:that.detail.id,real_leavedate:real_leavedate,real_leavehour:real_leavehour}, function (res) {
         app.showLoading(false);
        if(res.status == 1){
          that.real_refundprice = res.real_refundprice;
          that.real_refund_fuwumoney = res.real_refund_fuwumoney;
          that.real_refundmoney = res.real_refundmoney;
          that.real_refund_upgradescore = res.real_refund_upgradescore;
        }else{
          app.alert(res.msg)
          that.real_refundprice = 0;
          that.real_refund_fuwumoney = 0;
          that.real_refundmoney = 0;
          that.real_refund_upgradescore = 0;
        }
      })
    },
		getDate(type) {
				const date = new Date();
				let year = date.getFullYear();
				let month = date.getMonth() + 1;
				let day = date.getDate();

				if (type === 'start') {
						year = year - 60;
				} else if (type === 'end') {
						year = year + 2;
				}
				month = month > 9 ? month : '0' + month;
				day = day > 9 ? day : '0' + day;
				return `${year}-${month}-${day}`;
		},
    setLeavefield: function(e){
      var that = this;
      var field = e.currentTarget.dataset.field;
      that[field] = e.detail.value;
    },
    clearleavedata:function(){
      var that = this;
      that.date= "请点击选择";
      that.totalprice=0;//支付金额
      that.yajin_money=0;//押金金额
      that.leftmoney=0;//支付金额
      that.fuwu_money=0;//服务费
      that.use_money=0;//余额抵扣
      that.upgradescore_status=0;//是否使用升级积分抵扣
      that.upgradescore=0;//升级积分抵扣
      that.real_leavehour= that.leaveroomhour;//退房时间
      that.real_refundprice=0;//退房费
      that.real_refund_fuwumoney=0;//退服务费
      that.real_refundmoney=0;//退余额费
      that.real_refund_upgradescore=0;//退升级积分费
    }
  }
};
</script>
<style>
.ordertop{width:100%;height:220rpx;padding:50rpx 0 0 70rpx}
.ordertop .f1{color:#fff}
.ordertop .f1 .t1{font-size:32rpx;height:60rpx;line-height:60rpx}
.ordertop .f1 .t2{font-size:24rpx}

.address{ display:flex;width: 100%; padding: 20rpx 3%; background: #FFF;margin-bottom:20rpx;}
.address .img{width:40rpx}
.address image{width:40rpx; height:40rpx;}
.address .info{flex:1;display:flex;flex-direction:column;}
.address .info .t1{font-size:28rpx;font-weight:bold;color:#333}
.address .info .t2{font-size:24rpx;color:#999}

.product{width:100%; padding: 14rpx 3%;background: #FFF;}
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

.orderinfo{ width:100%;margin-top:10rpx;padding: 14rpx 3%;background: #FFF;}
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

.uni-popup-dialog {width: 710rpx;border-radius: 10rpx;background-color: #fff;}
.uni-dialog-title {display: flex;flex-direction: row;justify-content: center;padding-top: 30rpx;padding-bottom: 10rpx;}
.uni-dialog-title-text {font-size: 16px;font-weight: 500;}
.uni-dialog-content {align-items: center;padding: 5px 15px 15px 15px; margin-top: 20rpx;}
.uni-dialog-content-text {font-size: 14px;color: #6e6e6e;}
.uni-dialog-button-group {display: flex;flex-direction: row;border-top-color: #f5f5f5;border-top-style: solid;border-top-width: 1px;}
.uni-dialog-button {display: flex;flex: 1;flex-direction: row;justify-content: center;align-items: center;height: 45px;}
.uni-border-left {border-left-color: #f0f0f0;border-left-style: solid;border-left-width: 1px;}
.uni-dialog-button-text {font-size: 14px;}
.uni-button-color {color: #007aff;}
/*确认离店*/
.uni-list-cell-db{ display: flex;line-height: 60rpx;align-items:center;justify-content: space-between;}
.uni-list-cell-db label{ padding-right:20rpx;line-height: 60rpx;text-align: right;}
.uni-list-cell-db picker{line-height: 60rpx;}
.uni-popup-dialog .bordercss{height: 60rpx;line-height: 60rpx;width: 200rpx;border: 2rpx solid #f1f1f1;border-radius: 6rpx 6rpx;padding:0 10rpx;}
.uni-popup-dialog .remark{color: #999;margin-top: 4rpx;text-align: left;}
</style>