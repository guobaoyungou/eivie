<template>
<view class="container">
	<block v-if="isload">
		<dd-tab :itemdata="['全部','待确认','待入住','已到店','已离店','已取消']" :itemst="['all','1','2','3','4','-1']" :st="st" :isfixed="true" @changetab="changetab"></dd-tab>
		<view style="width:100%;height:100rpx"></view>

		<view class="topsearch flex-y-center">
			<view class="f1 flex-y-center">
				<image class="img" :src="pre_url+'/static/img/search_ico.png'"></image>
				<input :value="keyword" placeholder="输入关键字搜索" placeholder-style="font-size:24rpx;color:#C2C2C2" @confirm="searchConfirm"></input>
			</view>
		</view>

		<view class="order-content">
			<block v-for="(item, index) in datalist" :key="index">
				<view class="order-box">
					<view class="head"  @tap.stop="goto" :data-url="'orderdetail?id=' + item.id">
						<view class="f1" ><image :src="pre_url+'/static/img/ico-shop.png'"></image> {{item.title}}</view>
						<view class="flex1"></view>
						<text v-if="item.status==0" class="st0">待付款</text>
						
						<block v-if="item.status==1">
							<text class="st1">待确认</text>
						</block>
						<text v-if="item.status==1 && item.refund_status==1" class="st1">退款审核中</text>
						<text v-if="item.status==2" class="st2">待入住</text>
						<text v-if="item.status==4 && item.iscomment==0" class="st3">待评价</text>
						<text v-if="item.status==4 && item.iscomment==1" class="st3">已完成</text>
						<text v-if="item.status==3" class="st4">已到店</text>
						<text v-if="item.status==-1" class="st4">订单已关闭</text>
					</view>
					<view class="content" style="border-bottom:none"  @tap.stop="goto" :data-url="'orderdetail?id=' + item.id">
						<view >
							<image :src="item.pic">
						</view>	
						<view class="detail">
							<text class="t1">{{item.titel}}</text>
							<text class="t1">入住日期：{{item.in_date}}</text>	
							<text class="t1">离店日期：{{item.leave_date}}</text>	
							<view class="t3" >		
								<block v-if="item.isbefore==1">
									<text class="x1 flex1" v-if="item.real_usemoney>0 && item.real_roomprice>0">实付房费：{{item.real_usemoney}}{{t('余额单位')}} + ￥{{item.real_roomprice}}</text>
									<text class="x1 flex1" v-else-if="item.real_usemoney>0 && item.real_roomprice==0">实付房费：￥{{item.real_usemoney}}{{t('余额单位')}}</text>
									<text class="x1 flex1" v-else>实付房费：￥{{item.real_roomprice}}</text>
								</block>
								<block v-else>
									<text class="x1 flex1" v-if="item.use_money>0 && item.leftmoney>0">房费：{{item.use_money}}{{t('余额单位')}} + ￥{{item.leftmoney}}</text>
									<text class="x1 flex1" v-else-if="item.use_money>0 && item.leftmoney==0">房费：￥{{item.use_money}}{{t('余额单位')}}</text>
									<text class="x1 flex1" v-else>房费：￥{{item.sell_price}}</text>
								</block>
							</view>
						</view>
					</view>
					<view class="bottom" style="display:flex; justify-content: space-between;">
						<text>共{{item.daycount}}晚 
							<block v-if="item.use_money>0 && item.leftmoney>0">
									实付: 押金￥{{item.yajin_money}}+{{text['服务费']}}￥{{item.fuwu_money}}+房费￥{{item.leftmoney}}+{{item.use_money?item.use_money:0}}{{t('余额单位')}}
							</block>
							<block v-else-if="item.use_money>0 && item.leftmoney==0">
									实付: 押金￥{{item.yajin_money}}+{{text['服务费']}}￥{{item.fuwu_money}}+房费{{item.use_money?item.use_money:0}}{{t('余额单位')}}
							</block>
							<block v-else>
									实付:￥{{item.totalprice}}
							</block>
						</text>
									
					</view>
					<view class="bottom" v-if="item.refund_status>0" >
						<text v-if="item.refund_status==1" style="color:red"> 退款中￥{{item.refund_money}}</text>
						<text v-if="item.refund_status==2" style="color:red"> 已退款￥{{item.refund_money}}</text>
						<text v-if="item.refund_status==3" style="color:red"> 退款申请已驳回</text>
					</view>
					<view class="op">
						<view class="bottom1" v-if="item.status>=4 && item.yajin_money>0" >
							<text v-if="item.yajin_refund_status==0" style="color:red"> 押金待申请</text>
							<text v-if="item.yajin_refund_status==1" style="color:red"> 押金待审核</text>
							<text v-if="item.yajin_refund_status==2" style="color:red"> 押金已退款</text>
							<text v-if="item.yajin_refund_status==-1" style="color:red"> 退款申请已驳回</text>
						</view>
						<view v-if="item.yajin_refund_status == 1" class="btn2" @tap="goto"  :data-url="'refundyajin?orderid='+item.id">退押金</view>
						
						<view v-if="item.status==1" @tap.stop="confirmorder" :data-id='item.id' class="btn2">确认订单</view>
						<view v-if="item.status==2" @tap.stop="qrdaodian" :data-id="item.id" class="btn2">确认到店</view>
						<view v-if="item.status==3" @tap.stop="qrlidian" :data-index="index"  :data-id="item.id" class="btn2">确认离店</view>
						<view @tap.stop="goto" :data-url="'orderdetail?id=' + item.id" class="btn2">详情</view>
					</view>
					<view class="bottom flex-y-center">
						<image :src="item.member.headimg" style="width:40rpx;height:40rpx;border-radius:50%;margin-right:10rpx"/><text style="font-weight:bold;color:#333;margin-right:8rpx">{{item.member.nickname}}</text>(ID:{{item.mid}})
					</view>
				</view>
			</block>
		</view>
		
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
		<nomore v-if="nomore"></nomore>
		<nodata v-if="nodata"></nodata>
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
		const currentDate = this.getDate({
			format: true
		})
    return {
			opt:{},
			loading:false,
      isload: false,
			menuindex:-1,

      st: 'all',
      datalist: [],
      pagenum: 1,
      nomore: false,
			nodata:false,
      codtxt: "",
			keyword:"",

			orderid:0,
			text:[],
			pre_url:app.globalData.pre_url,
      
      //离店参数s
      date: "请点击选择",
      leaveroomhour:'',//退房时间
      nowday:'',//退房时间
      totalprice:0,//支付金额
      yajin_money:0,//押金金额
      leftmoney:0,//房费
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

  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.st=this.opt.st
		this.getdata();
  },
	onPullDownRefresh: function () {
		this.getdata();
	},
	onShow: function () {
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
	computed: {
			startDate() {
					return this.getDate('start');
			},
			endDate() {
					return this.getDate('end');
			}
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
      app.post('ApiAdminHotelOrder/getorder', {keyword:that.keyword,st: st,pagenum: pagenum}, function (res) {
				that.loading = false;
        var data = res.datalist;
				var yuyue_sign = res.yuyue_sign
				that.yuyue_sign = yuyue_sign
				that.text = res.text;
        that.real_leavehour = that.leaveroomhour = res.leaveroomhour;
        that.nowday = res.nowday;
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
    changetab: function (st) {
      this.st = st;
      uni.pageScrollTo({
        scrollTop: 0,
        duration: 0
      });
      this.getdata();
    },
		searchConfirm:function(e){
			this.keyword = e.detail.value;
      this.getdata(false);
		},
		qrlidian:function(e){
			var that=this
      that.clearleavedata();

			var orderid = e.currentTarget.dataset.id;
      that.orderid = orderid;
      var index = e.currentTarget.dataset.index;
      var datalist = that.datalist;
      var order = datalist[index];

      that.totalprice = order['totalprice'] || 0;//支付金额
      that.yajin_money= order['yajin_money'] || 0;//押金金额
      that.leftmoney = order['leftmoney'] || 0;//房费
      that.fuwu_money = order['fuwu_money'] || 0;//服务费
      that.use_money  = order['use_money'] || 0;//余额抵扣
      that.canRefundUpgradescore = order['canRefundUpgradescore'] || 0;//能否自定义退升级积分抵扣，仅平台有权限
      that.upgradescore_status = order['upgradescore_status'] || 0;//是否使用升级积分抵扣
      that.upgradescore = order['upgradescore'] || 0;//升级积分抵扣
			
			that.$refs.dialogLeave.open();
		},
		dialogLeaveClose:function(){
			this.$refs.dialogLeave.close();
		},
		confirmleave:function(){
			this.$refs.dialogLeave.close();
			var that = this
			app.showLoading('提交中');
			app.post('ApiAdminHotelOrder/confirmleave', { 
        orderid: that.orderid,
        real_leavedate:that.date,
        type:'leavehour',
        real_leavehour:that.real_leavehour,
        real_refundprice:that.real_refundprice,
        real_refund_fuwumoney:that.real_refund_fuwumoney,
        real_refundmoney:that.real_refundmoney,
        real_refund_upgradescore:that.real_refund_upgradescore,
      }, function (res) {
				app.showLoading(false);
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
      app.post('ApiAdminHotelOrder/beforeconfirmleave', {orderid:that.orderid,real_leavedate:real_leavedate,real_leavehour:real_leavehour}, function (res) {
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
      that.real_leavehour=that.leaveroomhour;//退房时间
      that.real_refundprice=0;//退房费
      that.real_refund_fuwumoney=0;//退服务费
      that.real_refundmoney=0;//退余额费
      that.real_refund_upgradescore=0;//退升级积分费
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
.order-box .content .detail .t2{height: 46rpx;line-height: 46rpx;color: #999;overflow: hidden;font-size: 26rpx;}
.order-box .content .detail .t3{display:flex;height:40rpx;line-height:40rpx;color: #ff4246;}
.order-box .content .detail .x1{ flex:1}
.order-box .content .detail .x2{ width:100rpx;font-size:32rpx;text-align:right;margin-right:8rpx}

.order-box .bottom{ width:100%; padding: 10rpx 0px; border-top: 1px #f4f4f4 solid; color: #555;}
.order-box .op{ display:flex;justify-content:flex-end;align-items:center;width:100%; padding: 10rpx 0px; border-top: 1px #f4f4f4 solid; color: #555;}

.btn1{margin-left:20rpx;width:160rpx;height:60rpx;line-height:60rpx;color:#fff;border-radius:3px;text-align:center}
.btn2{margin-left:20rpx;width:160rpx;height:60rpx;line-height:60rpx;color:#333;background:#fff;border:1px solid #cdcdcd;border-radius:3px;text-align:center}

/*.order-pin{ border: 1px #ffc702 solid; border-radius: 5px; color: #ffc702; float: right; padding: 0 5px; height: 23px; line-height: 23px; margin-left: 5px; font-size: 14px; position: absolute; bottom: 10px; right: 10px; background: #fff; }*/
.order-pin{ border: 1px #ffc702 solid; border-radius: 5px; color: #ffc702; float: right; padding: 0 5px; height: 23px; line-height: 23px; margin-left: 5px;}

.zan-tex{clear:both; display: block; width: 100%; color: #565656; font-size: 12px; height: 30px; line-height: 30px; text-align: center;  }
.ind-bot{ width: 100%; float: left; text-align: center; height: 50px; line-height: 50px; font-size: 13px; color: #ccc; background:#F2F2F2}

.modal{ position: fixed; width: 100%; height: 100%; bottom: 0; background: rgb(0,0,0,0.4); z-index: 100; display: flex; justify-content: center;}
.modal .addmoney{ width: 100%; background: #fff; width: 80%; position: absolute; top: 30%; border-radius: 10rpx; }
.modal .title{ height: 80rpx; ;line-height: 80rpx; text-align: center; font-weight: bold; border-bottom: 1rpx solid #f5f5f5; font-size: 32rpx; }
.modal .item{ display: flex; padding: 30rpx;}
.modal .item input{ width: 200rpx;}
.modal .item label{ width:200rpx; text-align: right; font-weight: bold;}
.modal .item .t2{ color: #008000; font-weight: bold;}
.modal .btn{ display: flex; margin: 30rpx 20rpx; }
.modal .btn .btn-cancel{  background-color: #F2F2F2; width: 150rpx; border-radius: 10rpx;}
.modal .btn .confirm{ width: 150rpx; border-radius: 10rpx; color: #fff;}
.modal .btn .btn-update{ width: 150rpx; border-radius: 10rpx; color: #fff; }

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

.order-box .bottom1{ width:100%; padding: 10rpx 0px;  color: #555;}
</style>