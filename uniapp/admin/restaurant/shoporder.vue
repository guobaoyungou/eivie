<template>
<view class="container">
	<block v-if="isload">
		<dd-tab :itemdata="['全部','待付款','已支付','已完成','退款']" :itemst="['all','0','1','3','10']" :st="st" :isfixed="true" @changetab="changetab"></dd-tab>
		<view style="width:100%;height:100rpx"></view>
		<!-- #ifndef H5 || APP-PLUS -->
		<view class="topsearch flex-y-center">
			<view class="f1 flex-y-center">
				<image class="img" :src="pre_url+'/static/img/search_ico.png'"></image>
				<input :value="keyword" placeholder="输入关键字搜索" placeholder-style="font-size:24rpx;color:#C2C2C2" @confirm="searchConfirm"></input>
			</view>
			<view class="shaixuan" @click="showTimesearch" :style="{borderColor:t('color1')}">按日期查询</view>
		</view>
		<!--  #endif -->
		<view class="order-content">
			<block v-for="(item, index) in datalist" :key="index">
				<view class="order-box" @tap="goto" :data-url="'shoporderdetail?id=' + item.id">
					<view class="top flex flex-bt">
						<view style="font-size: 26rpx;">时间：{{dateFormat(item.createtime)}}</view>
						<view style="color: red;font-size: 30rpx;" v-if="item.printdaynum > 0">当日流水号：#{{item.printdaynum}}</view>
					</view>
					<view class="head">
						<view>订单号：{{item.ordernum}}</view>
						<view class="flex1"></view>
						<text v-if="item.status==0" class="st0">待付款</text>
						<text v-if="item.status==1 && item.paytypeid != 4" class="st1">已付款</text>
						<text v-if="item.status==1 && item.paytypeid == 4" class="st1">线下付款</text>
						<text v-if="item.status==3" class="st3">已完成</text>
						<text v-if="item.status==4" class="st4">已关闭</text>
					</view>

					<block v-for="(item2, idx) in item.prolist" :key="idx">
						<view class="content" :style="idx+1==item.procount?'border-bottom:none':''">
							<view @tap.stop="goto" :data-url="'/restaurant/shop/product?id=' + item2.proid">
								<image :src="item2.pic"></image>
							</view>
							<view class="detail">
								<text class="t1">{{item2.name}}</text>
								<view v-if="(item2.ggtext && item2.ggtext.length)" class="flex-col">
									<block v-for="(item3,index) in item2.ggtext">
										<text class="t2">{{item3}}</text>
									</block>
								</view>
								<text class="t2" v-if="item2.ggname">{{item2.ggname}}{{item2.jltitle?item2.jltitle:''}}</text>
								<view class="t3">
									<!-- <text class="x1 flex1">￥{{item2.sell_price}}</text> -->
									<text class="x1 flex1" v-if="item2.jlprice">￥{{  parseFloat(parseFloat(item2.sell_price)+parseFloat(item2.jlprice)).toFixed(2) }}</text>
									<text class="x1 flex1" v-else>￥{{item2.sell_price}}</text>
									<text class="x2">×{{item2.num}}</text>
								</view>
								<text class="t2" v-if="item2.remark">备注：{{item2.remark}}</text>
							</view>
						</view>
					</block>
					<view class="bottom">
						<text>共计{{item.procount}}个菜品 实付:￥{{item.totalprice}}</text>
						<text v-if="item.refund_status==1" style="color:red"> 退款中￥{{item.refund_money}}</text>
						<text v-if="item.refund_status==2" style="color:red"> 已退款￥{{item.refund_money}}</text>
						<text v-if="item.refund_status==3" style="color:red"> 退款申请已驳回</text>
					</view>
					<view class="bottom flex-y-center" v-if="item.mid">
						<image :src="item.member.headimg" style="width:40rpx;height:40rpx;border-radius:50%;margin-right:10rpx"/><text style="font-weight:bold;color:#333;margin-right:8rpx">{{item.member.nickname}}</text>(ID:{{item.mid}})
					</view>
				</view>
			</block>
		</view>
		<nomore v-if="nomore"></nomore>
		<nodata v-if="nodata"></nodata>
		
		<view class="posterDialog" v-if="timesearchshow">
			<view class="main">
				<view class="close" @tap="timesearchClose"><image class="img" :src="pre_url+'/static/img/close.png'"/></view>
				<view class="content">
					
					<view class="f2" style="line-height:30px;display:flex;align-items:center;font-size: 26rpx;justify-content: center;" >
					  <picker mode="date" :value="start_time1" @change="bindStartTime1Change">
					    <view class="picker">{{start_time1 || '-选择日期-'}}</view>
					  </picker>
					  <picker mode="time" :value="start_time2" @change="bindStartTime2Change">
					    <view class="picker" style="padding-left:10rpx">{{start_time2 ||'-选择时间-'}}</view>
					  </picker>
					  <view style="padding:0 10rpx;color:#222;font-weight:bold">到</view>
					  <picker mode="date" :value="end_time1" @change="bindEndTime1Change">
					    <view class="picker">{{end_time1 ||'-选择日期-'}}</view>
					  </picker>
					  <picker mode="time" :value="end_time2" @change="bindEndTime2Change">
					    <view class="picker" style="padding-left:10rpx">{{end_time2 || '-选择时间-'}}</view>
					  </picker>
					</view>
					<view class="flex">
						<button class="op" @click="timeclear" :style="{background:t('color2')}">清空</button>
						<button class="op" @click="dateSearch" :style="{background:t('color1')}">确定</button>
					</view>	
				</view>
			</view>
		</view>
	</block>
	<loading v-if="loading"></loading>
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

      st: 'all',
      datalist: [],
      pagenum: 1,
      nomore: false,
			nodata:false,
      codtxt: "",
			keyword:"",
			pre_url:app.globalData.pre_url,
			start_time1:'',
			start_time2:'',
			end_time1:'',
			end_time2:'',
			timesearchshow:false
    };
  },

  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		if(this.opt && this.opt.st){
			this.st = this.opt.st;
		}
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
	onNavigationBarSearchInputConfirmed:function(e){
		this.searchConfirm({detail:{value:e.text}});
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
			var start_time = that.start_time1;
			if(that.start_time2 !=''){
				start_time+=' '+that.start_time2;
			}
			
			var end_time = that.end_time1;
			if(that.end_time2 !=''){
				end_time+=' '+ that.end_time2;
			}
      app.post('ApiAdminRestaurantShopOrder/index', {keyword:that.keyword,st: st,pagenum: pagenum,start_time:start_time,end_time:end_time}, function (res) {
				that.loading = false;
        var data = res.datalist;
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
		timesearchClose(){
			this.timesearchshow = false;
		},
		bindStartTime1Change:function(e){
			this.start_time1 = e.target.value
		},
		bindStartTime2Change:function(e){
			this.start_time2 = e.target.value
		},
		bindEndTime1Change:function(e){
			this.end_time1 = e.target.value
		},
		bindEndTime2Change:function(e){
			this.end_time2 = e.target.value
		},
		dateSearch(){
			this.timesearchshow = false;
			this.getdata();
		},
		showTimesearch(){
			this.timesearchshow = !this.timesearchshow ;
		},
		timeclear(){
			this.start_time1 = '';
			this.start_time2='';
			this.end_time1 = '';
			this.end_time2 = ''
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
.shaixuan{border: 6rpx solid #000;font-size: 24rpx;padding: 9rpx;font-size: 24rpx;}
.order-content{display:flex;flex-direction:column}
.order-box{ width: 94%;margin:10rpx 3%;padding:16rpx 3%; background: #fff;border-radius:8px}
.order-box .top{height: 70rpx;line-height: 70rpx;}
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
.posterDialog .main{margin: 60rpx 2% 30rpx 2%;width: 90%;}
.op{width:90%;margin:20rpx 5%;border-radius:36rpx;overflow:hidden;margin-top:60rpx;text-align: center;color: #fff;}
</style>