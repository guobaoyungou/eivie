<template>
<view class="container">
  <view class="myjichamoney" :style="{background:t('color1')}">
  	<view class="f1">
  		我的{{t('级差奖励')}}
  	</view>
  	<view class="f2">{{myjichamoney}}<view class="btn-mini"  v-if="canwithdraw" @tap="goto" data-url="jichamoneywithdraw">提现</view></view>
  </view>
	<block v-if="isload">
		<dd-tab :itemdata="['明细记录','提现记录']" :itemst="['0','1']" :st="st" :showstatus="showstatus" :ismoney="1" :isfixed="false" @changetab="changetab"></dd-tab>
		<view class="content">
			<block v-if="st==0">
			<view v-for="(item, index) in datalist" :key="index" class="item">
				<view class="f1">
						<text class="t1">{{item.remark}}</text>
						<text class="t2">{{item.createtime}}</text>
						<text class="t3">变更后: {{item.after}}</text>
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
						<text class="t1">提现金额：{{item.money}}元</text>
						<text class="t2">{{item.createtime}}</text>
						<text class="t2" v-if="item.status==2">驳回原因：{{item.reason  || '无'}}</text>
				</view>
				<view class="f3">
						<text class="t1" v-if="item.status==0">审核中</text>
						<text class="t1" v-if="item.status==1">已审核</text>
						<block v-if="item.status==4">
							<view class="btn1" :style="{background:t('color1')}" @click="confirm_shoukuan(item.id)" v-if="item.wx_state=='WAIT_USER_CONFIRM' || item.wx_state=='TRANSFERING'">确认收款</view>
							<view class="t1" v-else-if="item.wx_state=='FAIL'">转账失败</view>
							<view class="t1" v-else>处理中</view>
						</block>
						<text class="t2" v-if="item.status==2">已驳回</text>
						<text class="t1" v-if="item.status==3">已打款</text>
						
				</view>
			</view>
			</block>
		</view>
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
    return {
			opt:{},
			loading:false,
      isload: false,
			menuindex:-1,
			
      myjichamoney:0,
			canwithdraw:false,
			textset:{},
      st: 0,
      datalist: [],
      pagenum: 1,
			nodata:false,
      nomore: false,
      showstatus:[1,1],
      appinfo:{},//提现数据对应的平台信息
      detail:{},//提现详情内容
    };
  },

  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.st = this.opt.st || 0;
		this.getdata();
  },
	onPullDownRefresh: function () {
		this.getdata(true);
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
      var pagenum = that.pagenum;
      var st = that.st;
			that.nodata = false;
			that.nomore = false;
			that.loading = true;
      app.post('ApiMy/jichamoneylog', {st: st,pagenum: pagenum}, function (res) {
				that.loading = false;
        var data = res.data;
        if (pagenum == 1) {
					that.textset = app.globalData.textset;
					uni.setNavigationBarTitle({
						title: that.t('级差奖励') + '明细'
					});
          that.myjichamoney= res.myjichamoney;
					that.canwithdraw = res.canwithdraw;
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
        if(res.showstatus.length > 0){
          that.showstatus = res.showstatus;
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
      var a = await that.shoukuan(id,'member_withdrawlog','');
      console.log(a);
      that.getdata();
    }
  }
};
</script>
<style>
.myjichamoney{width:94%;margin:20rpx 3%;border-radius: 10rpx 56rpx 10rpx 10rpx;position:relative;display:flex;flex-direction:column;padding:70rpx 0}
.myjichamoney .f1{margin:0 0 0 60rpx;color:rgba(255,255,255,0.8);font-size:24rpx;}
.myjichamoney .f2{margin:20rpx 0 0 60rpx;color:#fff;font-size:64rpx;font-weight:bold; position: relative;}
.myjichamoney .item {min-width: 50%;color: #FFFFFF;padding: 0 10%;margin-top: 30rpx;}
.myjichamoney .item .label{font-size: 26rpx}
.myjichamoney .item .value{font-size: 44rpx;margin-top: 10rpx;font-weight: bold;}
.myjichamoney  .btn{height: 50rpx;text-align: center;border: 1px solid #e6e6e6;border-radius: 10rpx;color: #fff;font-size: 24rpx;font-weight: bold;display: inline-flex;align-items: center;justify-content: center;margin-left: 10rpx;padding: 0 10rpx;}
.btn-mini {right: 32rpx;top: 28rpx;width: 100rpx;height: 50rpx;text-align: center;border: 1px solid #e6e6e6;border-radius: 10rpx;color: #fff;font-size: 24rpx;font-weight: bold;display: inline-flex;align-items: center;justify-content: center;position: absolute;}
  
.container{ width:100%;display:flex;flex-direction:column}
.content{ width:94%;margin:0 3% 20rpx 3%;}
.content .item{width:100%;background:#fff;margin:20rpx 0;padding:20rpx 20rpx;border-radius:8px;display:flex;align-items:center}
.content .item:last-child{border:0}
.content .item .f1{display:flex;flex-direction:column}
.content .item .f1 .t1{color:#000000;font-size:30rpx;word-break:break-all;overflow:hidden;text-overflow:ellipsis;}
.content .item .f1 .t2{color:#666666}
.content .item .f1 .t3{color:#666666}
.content .item .f2{ flex:1;font-size:36rpx;text-align:right}
.content .item .f2 .t1{color:#03bc01}
.content .item .f2 .t2{color:#000000}
.content .item .f3{ flex:1;font-size:32rpx;text-align:right}
.content .item .f3 .t1{color:#03bc01}
.content .item .f3 .t2{color:#000000}
.btn1{height:60rpx;line-height:60rpx;color:#fff;border-radius:3px;text-align:center;padding: 0 15rpx;float: right;font-size: 14px;margin-left: 10rpx}
.data-empty{background:#fff}
</style>