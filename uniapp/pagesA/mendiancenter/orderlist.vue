<template>
<view class="container">
	<block v-if="isload">
		<dd-tab :itemdata="['全部','待付款','待发货','待收货','已完成']" :itemst="['all','0','1','2','3']" :st="st" :isfixed="true" @changetab="changetab"></dd-tab>
		<view style="width:100%;height:100rpx"></view>
		<!-- #ifndef H5 || APP-PLUS -->
		<view class="topsearch flex-y-center">
			<view class="f1 flex-y-center">
				<image class="img" :src="pre_url+'/static/img/search_ico.png'"></image>
				<input :value="keyword" placeholder="输入关键字搜索" placeholder-style="font-size:24rpx;color:#C2C2C2" @confirm="searchConfirm"></input>
			</view>
			<view v-if="morewifiprintShow" @click="wifiprint" data-type="2" class="btn2">批量打印</view>
		</view>
		<!--  #endif -->
		<view class="order-content">
			<checkbox-group class="radio-group" name="gettj" @change="wifiprintChexbox">
			<block v-for="(item, index) in datalist" :key="index">
				<view class="order-box" @tap.stop="goto" :data-url="'orderdetail?id=' + item.id">
					<view class="head">
						<view>订单号：{{item.ordernum}}</view>
						<view class="flex1"></view>
						<text v-if="item.status==0" class="st0">待付款</text>
						<text v-if="item.status==1" class="st1">待发货</text>
						<text v-if="item.status==2" class="st2">待收货</text>
						<text v-if="item.status==3" class="st3">已完成</text>
						<text v-if="item.status==4" class="st4">已关闭</text>
						<text v-if="item.status==8" class="st8">待提货</text>
					</view>

					<block v-for="(item2, idx) in item.prolist" :key="idx">
						<view class="content" :style="idx+1==item.procount?'border-bottom:none':''">
							<view @tap.stop="goto" :data-url="'/pages/shop/product?id=' + item2.proid">
								<image :src="item2.pic"></image>
							</view>
							<view class="detail">
								<text class="t1">{{item2.name}}</text>
								<text class="t2">{{item2.ggname}}</text>
								<block>
									<view class="t3" v-if="item2.product_type && item2.product_type==2">
										<text class="x1 flex1">{{item2.real_sell_price}}元/斤</text><text class="x2">×{{item2.real_total_weight}}斤</text>
									</view>
									<view class="t3" v-else><text class="x1 flex1">￥{{item2.sell_price}}</text><text class="x2">×{{item2.num}}</text></view>
								</block>
								<view class="t2 tgr" v-if="item2.has_glassrecord">
								{{item2.glassrecord.name}} 
								{{item2.glassrecord.nickname?item2.glassrecord.nickname:''}} 
								{{item2.glassrecord.check_time?item2.glassrecord.check_time:''}}
								{{item2.glassrecord.typetxt}}
<!--								<block>
									<text class="pdl10" v-if="item2.glassrecord.double_ipd==0">{{item2.glassrecord.ipd?'PD'+item2.glassrecord.ipd:''}}</text>
									<text class="pdl10" v-else> PD R{{item2.glassrecord.ipd_right}} L{{item2.glassrecord.ipd_left}}</text>
								</block>-->
								</view>
							</view>
						</view>
					</block>
          <view class="bottom" v-if="item.isdygroupbuy && item.isdygroupbuy==1">
          	<text style="color:red">抖音团购券</text>
          </view>
					<view class="bottom">
						<text>共计{{item.procount}}件商品 实付:￥{{item.totalprice}}</text>
						<text v-if="item.refund_status==1" style="color:red"> 退款中￥{{item.refund_money}}</text>
						<text v-if="item.refund_status==2" style="color:red"> 已退款￥{{item.refund_money}}</text>
						<text v-if="item.refund_status==3" style="color:red"> 退款申请已驳回</text>
					</view>
					<view class="bottom flex-y-center" v-if="item.mid>0">
						<image :src="item.member.headimg" style="width:40rpx;height:40rpx;border-radius:50%;margin-right:10rpx"/><text style="font-weight:bold;color:#333;margin-right:8rpx">{{item.member.nickname}}</text>(ID:{{item.mid}})
					</view>
					<view class="op"  v-if="wifiprintAuth">
						<view @click.stop="">
							<checkbox :value="''+item.id" :checked="inArray(item.id,[1,2])?true:false"></checkbox>	
						</view>
						<view :data-id="item.id"  @click.stop="wifiprint" data-type="1" class="btn2">打印小票</view>
					</view>
				</view>
			</block>
			</checkbox-group>
		</view>
		<nomore v-if="nomore"></nomore>
		<nodata v-if="nodata"></nodata>
	</block>
	<loading v-if="loading"></loading>
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

      st: 'all',
      datalist: [],
      pagenum: 1,
      nomore: false,
			nodata:false,
      codtxt: "",
			keyword:"",
			mid:'',
			wifiprintdata:[],
			morewifiprintShow:false,
			wifiprintAuth:false,
			pre_url:app.globalData.pre_url
    };
  },
  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		if(this.opt && this.opt.st){
			this.st = this.opt.st;
		}
		if(this.opt && this.opt.mid){
			this.mid = this.opt.mid;
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
			var mid = that.mid
			that.nodata = false;
			that.nomore = false;
			that.loading = true;
      app.post('ApiMendianCenter/shoporder', {mid:mid,keyword:that.keyword,st: st,pagenum: pagenum}, function (res) {
				that.loading = false;
				that.wifiprintAuth = res.wifiprintAuth;
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
	wifiprintChexbox:function(e){
		var value =  e.detail.value;
		this.wifiprintdata = value;
		if(this.wifiprintdata.length > 0){
			this.morewifiprintShow = true;
		}else{
			this.morewifiprintShow = false;
		}
	},
	wifiprint(e){
		var that = this;
		var wifiprintdata = that.wifiprintdata;
		var type = e.currentTarget.dataset.type;
		if(type ==1){
			var wifiprintdata = [];
			wifiprintdata.push(e.currentTarget.dataset.id);
		}
		 that.loading = true;
		 app.post('ApiAdminOrder/wifiprint', {ids:wifiprintdata}, function (res) {
			  that.loading = false;
			 if(res.status==1){
				app.success('打印成功');
				return;
			 }else{
				 app.error(res.msg);
				 return;
			 }
			
		 })
	},
	
  }
};
</script>
<style>
.container{ width:100%}
.topsearch{width:94%;margin:10rpx 3%;}
.topsearch .f1{height:60rpx;border-radius:30rpx;border:0;background-color:#fff;flex:1}
.topsearch .f1 .img{width:24rpx;height:24rpx;margin-left:10px}
.topsearch .f1 input{height:100%;flex:1;padding:0 20rpx;font-size:28rpx;color:#333;}

.order-content{display:flex;flex-direction:column}
.order-box{ width: 94%;margin:10rpx 3%;padding:16rpx 3%; background: #fff;border-radius:8px}
.order-box .head{ display:flex;width:100%; border-bottom: 1px #f4f4f4 solid; height: 70rpx; line-height: 70rpx; overflow: hidden; color: #999;}
.order-box .head .f1{display:flex;align-items:center;color:#333}
.order-box .head .f1 image{width:34rpx;height:34rpx;margin-right:4px}
.order-box .head .st0{ width: 140rpx; color: #ff8758; text-align: right; }
.order-box .head .st1{ width: 140rpx; color: #ffc702; text-align: right; }
.order-box .head .st2{ width: 140rpx; color: #ff4246; text-align: right; }
.order-box .head .st3{ width: 140rpx; color: #999; text-align: right; }
.order-box .head .st4{ width: 140rpx; color: #bbb; text-align: right; }
.order-box .head .st8{ width: 140rpx; color: #ff55ff; text-align: right; }

.order-box .content{display:flex;width: 100%; padding:16rpx 0px;border-bottom: 1px #f4f4f4 dashed;position:relative;align-items: center;}
.order-box .content:last-child{ border-bottom: 0; }
.order-box .content image{ width: 140rpx; height: 140rpx;}
.order-box .content .detail{display:flex;flex-direction:column;margin-left:14rpx;flex:1}
.order-box .content .detail .t1{font-size:26rpx;line-height:36rpx;margin-bottom:10rpx;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:2;overflow:hidden;}
.order-box .content .detail .t2{height: 46rpx;line-height: 46rpx;color: #999;overflow: hidden;font-size: 26rpx;}
.order-box .content .detail .t3{display:flex;height:40rpx;line-height:40rpx;color: #ff4246;}
.order-box .content .detail .x1{ flex:1}
.order-box .content .detail .x2{ width:110rpx;font-size:32rpx;text-align:right;margin-right:8rpx}

.order-box .bottom{ width:100%; padding: 10rpx 0px; border-top: 1px #f4f4f4 solid; color: #555;}
.order-box .op{ display:flex;justify-content:flex-end;align-items:center;width:100%; padding: 10rpx 0px; border-top: 1px #f4f4f4 solid; color: #555;}

.btn1{margin-left:20rpx;width:160rpx;height:60rpx;line-height:60rpx;color:#fff;border-radius:3px;text-align:center}
.btn2{margin-left:20rpx;width:160rpx;height:60rpx;line-height:60rpx;color:#333;background:#fff;border:1px solid #cdcdcd;border-radius:3px;text-align:center}
.tgr{font-size: 24rpx;}
.pdl10{padding-left: 10rpx;}
</style>