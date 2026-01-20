<template>
<view class="container">
	<block v-if="isload">
		<dd-tab :itemdata="[t('佣金')+'明细','提现记录']" :itemst="['0','1']" :st="st" :isfixed="false" @changetab="changetab"></dd-tab>
		<view class="content">
			<block v-if="st==0">
			<view v-for="(item, index) in datalist" :key="index" class="item">
				<view class="f1">
						<text class="t1">{{item.remark}}</text>
						<text class="t2">{{item.createtime}}</text>
						<text class="t3">变更后余额: {{item.after}}</text>
						<text class="t3" v-if="set.commission_service_fee_show && set.commission_service_fee_show == 1">平台服务费：{{item.service_fee}}</text>
						<block v-if="item.order">
							<text class="t3">订单金额: {{item.order.totalprice}}</text>
							<text class="t3" v-if="item.order.member">下单人: <image :src="item.order.member.headimg"></image>{{item.order.member.nickname}}</text>
							<view class="t3" v-if="item.order.business"><image :src="item.order.business.logo"></image>{{item.order.business.name}}</view>
						</block>
						<view class="x3"  v-if="show_from_member && item.frommid>0">来源{{t('会员')}}：<image :src="item.from_headimg"></image>{{item.from_nickname}}</view>
				</view>
				<view class="f2">
						<text class="t1" v-if="item.money>0">+{{item.money}}</text>
						<text class="t2" v-else>{{item.money}}</text>
				</view>
			</view>
			</block>
			<block v-if="st==1">
			<view v-for="(item, index) in datalist" :key="index" class="item">
				<view class="f1">
						<text class="t1">提现金额：{{item.money}}</text>
						<text class="t2">{{item.createtime}}</text>
						<text class="t2" v-if="item.status==2">驳回原因：{{item.reason || '无'}}</text>
				</view>
				<view class="f3">
						<text class="t1" v-if="item.status==0">审核中</text>
						<text class="t1" v-if="item.status==1">已审核</text>
						<text class="t2" v-if="item.status==2">已驳回</text>
						<text class="t1" v-if="item.status==3">已打款</text>
						<block v-if="item.status==4">
							<view class="btn1" :style="{background:t('color1')}" @click="confirm_shoukuan(item.id)" v-if="item.wx_state=='WAIT_USER_CONFIRM' || item.wx_state=='TRANSFERING'">确认收款</view>
							<view class="t1" v-else-if="item.wx_state=='FAIL'">转账失败</view>
							<view class="t1" v-else>处理中</view>
						</block>
				</view>
			</view>
			</block>
		</view>
	</block>
	<nodata v-if="nodata"></nodata>
	<nomore v-if="nomore"></nomore>
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
			
      nodata: false,
      nomore: false,
      st: 0,
			type:0,
      datalist: [],
			textset:{},
      pagenum: 1,
			set:{},
			show_from_member:0,//是否显示来源会员信息
    };
  },

  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.st = this.opt.st || 0;
		this.type = this.opt.type || 0;
		var that = this;
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
  methods: {
    getdata: function (loadmore) {
			if(!loadmore){
				this.pagenum = 1;
				this.datalist = [];
			}
      var that = this;
      var st = that.st;
			var type = that.type;
      var pagenum = that.pagenum;
			that.loading = true;
			that.nodata = false;
      that.nomore = false;
      app.post('ApiAgent/commissionlog', {st: st,pagenum: pagenum,type:type}, function (res) {
				that.loading = false;
        var data = res.data;
        if (pagenum == 1) {
					that.textset = app.globalData.textset;
					that.set = res.set;
					uni.setNavigationBarTitle({
						title: that.t('佣金') + '明细'
					});
          that.datalist = data;
          if (data.length == 0) {
            that.nodata = true;
          }
		  that.show_from_member = res.show_from_member || 0;
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
	async confirm_shoukuan(id){
		var that = this;
		var a = await that.shoukuan(id,'member_commission_withdrawlog','');
		console.log(a);
		that.getdata();
	}
  }
};
</script>
<style>

.content{ width:94%;margin:20rpx 3%;}
.content .item{width:100%;background:#fff;margin:20rpx 0;padding:40rpx 30rpx;border-radius:8px;display:flex;align-items:center}
.content .item:last-child{border:0}
.content .item .f1{width:500rpx;display:flex;flex-direction:column}
.content .item .f1 .t1{color:#000000;font-size:30rpx;word-break:break-all;overflow:hidden;text-overflow:ellipsis;}
.content .item .f1 .t2{color:#666666;font-size:24rpx;margin-top:10rpx}
.content .item .f1 .t3{color:#666666;font-size:24rpx;margin-top:10rpx;display: flex;}
.content .item .f1 .t3 image{width:40rpx;height:40rpx;border-radius:50%;margin-right:4px;align-content: center;}
.content .item .f2{ flex:1;width:200rpx;font-size:36rpx;text-align:right}
.content .item .f2 .t1{color:#03bc01}
.content .item .f2 .t2{color:#000000}
.content .item .f3{ flex:1;width:200rpx;font-size:32rpx;text-align:right}
.content .item .f3 .t1{color:#03bc01}
.content .item .f3 .t2{color:#000000}

.data-empty{background:#fff}
.btn1{height:60rpx;line-height:60rpx;color:#fff;border-radius:3px;text-align:center;padding: 0 15rpx;float: right;font-size: 25rpx;margin-left: 10rpx}
.content .item .f1 .x3{display:flex;align-items:center;margin-top:10rpx}
.content .item .f1 .x3 image{width:40rpx;height:40rpx;border-radius:50%;margin-right:4px}
</style>