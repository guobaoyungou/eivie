<template>
<view class="container">
	<block v-if="isload">
		<view class="topfix">
			<view class="toplabel">
				<text class="t1">{{t('分销订单')}}（{{count}}）</text>
				<text class="t2">预计：+{{commissionyj}}元</text>
			</view>
			<dd-tab :itemdata="['所有订单','待付款','已付款','已完成','退款/售后']" :itemst="['0','10','20','100','5']" :st="st" :isfixed="false" @changetab="changetab"></dd-tab>
		</view>
		<view style="margin-top:190rpx"></view>
		<block v-if="datalist && datalist.length>0">
		<view class="content">
			<view v-for="(item, index) in datalist" :key="index" class="item" @tap='todetail' :data-show="item.order_info?1:0" :data-url="'/pagesExt/order/detail?id='+item.order_info.id+'&fromfenxiao=1'">
				<view class="f1 flex" style="justify-content: flex-start;">
					<image class="img" :src="item.headimg"></image>
					<view class="t1">
						<view v-if="item.nickname">
							{{item.nickname}}
						</view>
						<view >
							{{item.ordernum}}({{item.dengji}})
						</view>
					</view>
					<view v-if="item.dannum && item.dannum > 0" style="color:#a55;margin-left: 30rpx;">
						第{{item.dannum}}单
					</view>

				</view>
				
				<view class="f2">
					<view class="t1 flex">
						<view class="x1 flex">
							<image :src="item.pic" class="img"></image>
							<view style="margin-left: 20rpx;">
								<view class="x1t" style="line-height: 50rpx;">{{item.name}}</view>
								<view class="x1t"> 下单时间：{{item.createtime}}</view>
							</view>
							
						</view>
						<view>共{{item.num}}件</view>
					</view>
					<view class="t2 flex">
						<view >实付金额：￥{{item.totalprice}}</view>
						<view >预估佣金：<text style="color: #ff6600;">{{item.commission}}</text></view>
						<view >
							<text class="dior-sp6 yfk" v-if="item.status==20 || item.status==21 || item.status==30">已付款</text>
							<text class="dior-sp6 dfk" v-if="item.status==10">待付款</text>
							<text class="dior-sp6 ywc" v-if="item.status==100">已完成</text>
							<text class="dior-sp6 ygb" v-if="item.status==200 || item.status==250">已关闭</text>
							<text class="dior-sp6 ygb" v-if="item.finish_aftersale_sku_cnt > 0">退款/售后</text>
						</view>
					</view>
					<view class="t3 flex"  v-if="item.order_info">
						<view class="btn2" :style="'border:solid 2rpx '+t('color1')+';color:'+t('color1')" @tap.stop="logistics(item.order_info)" >查看物流</view>
					</view>
				</view>
			</view>
		</view>
		<uni-popup id="dialogSelectExpress" ref="dialogSelectExpress" type="dialog">
			<view style="background:#fff;padding:20rpx 30rpx;border-radius:10rpx;width:600rpx" v-if="express_content">
				<view class="sendexpress-item" v-for="(item, index) in express_content" :key="index" @tap="goto" :data-url="'/pagesExt/order/logistics?express_com=' + item.express_com + '&express_no=' + item.express_no" style="display: flex;border-bottom: 1px solid #f5f5f5;padding:20rpx 0;">
					<view class="flex1">{{item.express_com}} - {{item.express_no}}</view>
					<image :src="pre_url+'/static/img/arrowright.png'" style="width:30rpx;height:30rpx"/>
				</view>
			</view>
		</uni-popup>
		</block>
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

      st: 0,
			count:0,
      commissionyj: 0,
      pagenum: 1,
      datalist: [],
			express_content:'',
      nodata: false,
      nomore: false,
      bid:0,
	  pre_url: app.globalData.pre_url,
    };
  },

  onLoad: function (opt) {
		this.opt = app.getopts(opt);
    this.bid = this.opt.bid || 0;
		this.getdata();
  },
	onPullDownRefresh: function () {
		this.pagenum = 1;
		this.datalist = [];
		this.getdata();
	},
  onReachBottom: function () {
    if (!this.nodata && !this.nomore) {
      this.pagenum = this.pagenum + 1;
      this.getdata();
    }
  },
  methods: {
		getdata: function () {
			var that = this;
      var pagenum = that.pagenum;
      var st = that.st;
			that.loading = true;
			that.nodata = false;
      that.nomore = false;
			app.get('ApiAgent/agorder_wxchannels',{st:st,pagenum: pagenum,bid:that.bid},function(res){
				that.loading = false;
				var data = res.datalist;
        if (pagenum == 1) {
					that.commissionyj = res.commissionyj;
					that.count = res.count;
          that.datalist = data;
          if (data.length == 0) {
            that.nodata = true;
          }
					uni.setNavigationBarTitle({
						title: '小店'+that.t('分销订单')
					});
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
      this.pagenum = 1;
      this.st = st;
      this.datalist = [];
      uni.pageScrollTo({
        scrollTop: 0,
        duration: 0
      });
      this.getdata();
    },
    logistics:function(e){
	     console.log(e)
      var express_com = e.express_com
      var express_no = e.express_no
      var express_content = e.express_content
      var express_type = e.express_type
      console.log(express_content)
      if(!express_content){
        app.goto('/pagesExt/order/logistics?express_com=' + express_com + '&express_no=' + express_no+'&type='+express_type);
      }else{
        this.express_content = JSON.parse(express_content);
        console.log(express_content);
        this.$refs.dialogSelectExpress.open();
      }
    },
	todetail:function(e){
		var url = e.currentTarget.dataset.url;
		var show = e.currentTarget.dataset.show;
		if(show){
			app.goto(url);
		}
	}
  }
};
</script>
<style>
.topfix{width: 100%;position:relative;position:fixed;background: #f9f9f9;top:var(--window-top);z-index:11;}
.toplabel{width: 100%;background: #f9f9f9;padding: 20rpx 20rpx;border-bottom: 1px #e3e3e3 solid;display:flex;}
.toplabel .t1{color: #666;font-size:30rpx;flex:1}
.toplabel .t2{color: #666;font-size:30rpx;text-align:right}

.content{ width:100%;}
.content .item{width:94%;margin-left:3%;border-radius:10rpx;background: #fff;margin-bottom:16rpx;}
.content .item .f1{width:100%;padding: 10rpx 20rpx;color: #666;border-bottom: 1px #f5f5f5 solid;align-items: center;justify-content: space-between}
.content .item .f1 .img{width: 100rpx;height: 100rpx;border-radius:50%;}
.content .item .f1 .t1{line-height: 50rpx;margin-left: 30rpx;}

.content .item .f2{padding:20rpx;align-items:center}/* display:flex; */
.content .item .f2 .t1{display:flex;flex:auto; justify-content: space-between;align-items: center;border-bottom: 1px #f5f5f5 solid;padding-bottom: 20rpx;}
.content .item .f2 .t1 .img{width: 140rpx;height: 140rpx;}
.content .item .f2 .t1 .x1{align-items: center;}
.content .item .f2 .t1 .x1 .x1t{line-height: 60rpx;}
.content .item .f2 .t1 .x2{ color:#999}
.content .item .f2 .t1 .x3{display:flex;align-items:center}
/* .content .item .f2 .t1 .x3 image{width:40rpx;height:40rpx;border-radius:50%;margin-right:4px} */

.content .item .f2 .t2{padding: 20rpx 0;justify-content: space-between}
.content .item .f2 .t3{justify-content: flex-end;}

.dfk{color: #ff9900;}
.yfk{color: red;}
.ywc{color: #ff6600;}
.ygb{color: #aaaaaa;}
.btn2{margin-left:20rpx; margin-top: 10rpx;width:160rpx;height:60rpx;line-height:60rpx;color:#333;border-radius:20px;text-align:center;}

</style>