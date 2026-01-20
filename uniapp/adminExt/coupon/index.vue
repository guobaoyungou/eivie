<template>
<view class="container">
	<dd-tab :itemdata="['进行中('+jxz_num+')','未开始('+wks_num+')','已结束('+yjs_num+')']" :itemst="['2','1','3']" :st="st" :isfixed="true" @changetab="changetab"></dd-tab>
  <view style="width:100%;height:100rpx"></view>
	<block v-if="isload">
	<!-- #ifndef H5 || APP-PLUS -->
	<view class="topsearch flex-y-center">
		<view class="f1 flex-y-center">
			<image class="img" :src="pre_url+'/static/img/search_ico.png'"></image>
			<input :value="keyword" placeholder="输入关键字搜索" placeholder-style="font-size:24rpx;color:#C2C2C2" @confirm="searchConfirm"></input>
		</view>
	</view>
	<!--  #endif -->
<!--	<view class="order-content" id="datalist">-->
  <view class="coupon-list">
    <view v-for="(item, index) in datalist" :key="index" class="coupon">
      <view class="order-box">
        <view class="content" style="border-bottom:none">
          <view class="pt_left">
            <view class="pt_left-content">
              <view class="f1" :style="{color:t('color1')}" v-if="item.type==1"><text class="t0">￥</text><text class="t1">{{item.money}}</text></view>
              <view class="f1" :style="{color:t('color1')}" v-if="item.type==10"><text class="t1">{{(item.discount/10)}}</text><text class="t0">折</text></view>
              <view class="f1" :style="{color:t('color1')}" v-if="item.type==3"><text class="t1">{{item.limit_count}}</text><text class="t2">次</text></view>
              <view class="f1" :style="{color:t('color1')}" v-if="item.type==5"><text class="t0">￥</text><text class="t1">{{item.money}}</text></view>
              <view class="f1" :style="{color:t('color1')}" v-if="item.type==6"><text class="t0">￥</text><text class="t1">{{item.money}}</text></view>
              <block v-if="item.type!=1 && item.type!=10 && item.type!=3 && item.type!=5 &&  item.type!=6">
                <view class="f1" :style="{color:t('color1')}">{{item.type_txt}}</view>
              </block>
              <view class="f2" :style="{color:t('color1')}" v-if="item.type==1 || item.type==4 || item.type==5 || item.type==10 ||  item.type==6">
                <text v-if="item.minprice>0">满{{item.minprice}}元可用</text>
                <text v-else>无门槛</text>
              </view>
            </view>
          </view>
          <view class="pt_right">
            <view class="f1">
              <view class="t1">{{item.name}}</view>
              <text class="t2" :style="{background:'rgba('+t('color1rgb')+',0.1)',color:t('color1')}">{{item.type_txt}}</text>
              <text class="t2" v-if="!item.from_mid && (item.isgive == 1 || item.isgive == 2)" :style="{background:'rgba('+t('color2rgb')+',0.1)',color:t('color2')}">可赠送</text>
              <view class="t3" :style="item.bid>0?'margin-top:0':'margin-top:10rpx'">有效期至 {{item.endtime}}</view>
            </view>
          </view>
        </view>
        <view class="op">
					<text style="color:red;margin-left: 20rpx;" class="flex1" v-if="item.status == '已结束'">{{item.status}}</text>
					<text style="color:green;margin-left: 20rpx;" class="flex1" v-else>{{item.status}}</text>
					<view @tap="goto" :data-url="'/admin/member/index?coupon=1' + '&name=' + item.name + '&id=' + item.id" class="btn2" v-if="item.bid==0">推送</view>
          <view @tap="goto" :data-url="'edit?id='+item.id+'&type=1'" class="btn2">编辑</view>
          <view class="btn2" @tap="todel" :data-id="item.id">删除</view>
        </view>
      </view>
    </view>
  </view>
	<view class="bottom-but-view notabbarbot">
		<button class="savebtn" :style="'background:linear-gradient(90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',1) 100%)'" @tap="goto" data-url="/adminExt/coupon/edit?type=2">添加{{t('优惠券')}}</button>
	</view>
	<nomore v-if="nomore"></nomore>
  <loading v-if="loading"></loading>
	<nodata v-if="nodata"></nodata>
	<view style="width:100%;height:calc(160rpx + env(safe-area-inset-bottom));"></view>
  </block>
</view>
</template>

<script>
var app = getApp();

export default {
  data() {
    return {
      st: '2',
      datalist: [],
      pagenum: 1,
      loading:false,
      isload: false,
      nomore: false,
      yjs_num: 0,
      jxz_num: 0,
      wks_num: 0,
      sclist: "",
			keyword: '',
      nodata: false,
      pre_url:app.globalData.pre_url,
    };
  },
	onShow() {
		this.getdata();
	},
  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.getdata();
		uni.setNavigationBarTitle({
			title: this.t('优惠券')+'列表'
		});
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
    changetab: function (st) {
      var that = this;
      that.st = st;
      that.getdata();
    },
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
      app.post('ApiAdminCoupon/index', {name:that.keyword,pagenum: pagenum,st: that.st}, function (res) {
        that.loading = false;
        var data = res.data;
        if (pagenum == 1){
					that.wks_num = data.wks_num;
					that.yjs_num = data.yjs_num;
					that.jxz_num = data.jxz_num;
					that.datalist = data.list;
          if (data.length == 0) {
            that.nodata = true;
          }
					that.loaded();
        }else{
          if (data.length == 0) {
            that.nomore = true;
          } else {
            var datalist = that.datalist;
            var newdata = datalist.concat(data.list);
            that.datalist = newdata;
          }
        }
      });
    },
    todel: function (e) {
      var that = this;
      var id = e.currentTarget.dataset.id;
			let confirmText = '确定要删除该'+that.t('优惠券')+'吗?';
      app.confirm(confirmText, function () {
        app.post('ApiAdminCoupon/del', {ids: id}, function (res) {
          if (res.status == 1) {
            app.success(res.msg);
            that.getdata();
          } else {
            app.error(res.msg);
          }
        });
      });
    },
    setst: function (e) {
      var that = this;
      var id = e.currentTarget.dataset.id;
      var st = e.currentTarget.dataset.st;
      app.confirm('确定要' + (st == 0 ? '下架' : '上架') + '吗?', function () {
        app.post('ApiAdminRestaurantProduct/setst', {st: st,id: id}, function (res) {
          if (res.status == 1) {
            app.success(res.msg);
            that.getdata();
          } else {
            app.error(res.msg);
          }
        });
      });
    },
		searchConfirm:function(e){
			this.keyword = e.detail.value;
      this.getdata(false);
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
.order-box{ width: 100%;background: #fff;}
.order-box .content{display:flex;width: 100%;border-bottom: 1px #e5e5e5 dashed;position:relative}
.order-box .content:last-child{ border-bottom: 0; }
.order-box .content image{ width: 140rpx; height: 140rpx;}
.order-box .content .detail{display:flex;flex-direction:column;margin-left:14rpx;flex:1}
.order-box .content .detail .t1{font-size:26rpx;min-height:50rpx;line-height:36rpx;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:2;overflow:hidden;}
.order-box .content .detail .t2{height:36rpx;line-height:36rpx;color: #999;overflow: hidden;font-size: 24rpx;}
.order-box .content .detail .t3{display:flex;height: 36rpx;line-height: 36rpx;color: #ff4246;}
.order-box .content .detail .x1{ font-size:30rpx;margin-right:5px}
.order-box .content .detail .x2{ font-size:24rpx;text-decoration:line-through;color:#999}
.coupon-list{width:100%;padding:20rpx}
.coupon{width:100%;display:flex;margin-bottom:20rpx;border-radius:10rpx;overflow:hidden;align-items:center;position:relative;background: #fff;}
.coupon .order-box .content .pt_left{background: #fff;min-height:200rpx;color: #FFF;width:30%;display:flex;flex-direction:column;align-items:center;justify-content:center}
.coupon .order-box .content .pt_left-content{width:100%;height:100%;margin:30rpx 0;border-right:1px solid #EEEEEE;display:flex;flex-direction:column;align-items:center;justify-content:center}
.coupon .order-box .content .pt_left .f1{font-size:40rpx;font-weight:bold;text-align:center;}
.coupon .order-box .content .pt_left .t0{padding-right:0;}
.coupon .order-box .content .pt_left .t1{font-size:60rpx;}
.coupon .order-box .content .pt_left .t2{padding-left:10rpx;}
.coupon .order-box .content .pt_left .f2{font-size:20rpx;color:#4E535B;text-align:center;}
.coupon .order-box .content .pt_right{background: #fff;width:70%;display:flex;min-height:200rpx;text-align: left;padding:20rpx 20rpx;position:relative}
.coupon .order-box .content .pt_right .f1{flex-grow: 1;flex-shrink: 1;}
.coupon .order-box .content .pt_right .f1 .t1{font-size:28rpx;color:#2B2B2B;font-weight:bold;height:60rpx;line-height:60rpx;overflow:hidden}
.coupon .order-box .content .pt_right .f1 .t2{height:36rpx;line-height:36rpx;font-size:20rpx;font-weight:bold;padding:0 16rpx;border-radius:4rpx; margin-right: 16rpx;}
.coupon .order-box .content .pt_right .f1 .t2:last-child {margin-right: 0;}
.coupon .order-box .content .pt_right .f1 .t3{font-size:20rpx;color:#999999;height:46rpx;line-height:46rpx;}
.coupon .order-box .content .pt_right .f1 .t4{font-size:20rpx;color:#999999;height:46rpx;line-height:46rpx;max-width: 76%;text-overflow: ellipsis;overflow: hidden;white-space: nowrap;}
.coupon .order-box .content .pt_right .btn{position:absolute;right:16rpx;top:49%;margin-top:-28rpx;border-radius:28rpx;width:140rpx;height:56rpx;line-height:56rpx;color:#fff}
.coupon .order-box .content .pt_right .sygq{position:absolute;right:30rpx;top:50%;margin-top:-50rpx;width:100rpx;height:100rpx;}
.coupon .pt_left.bg3{background:#ffffff;color:#b9b9b9!important}
.coupon .pt_right.bg3 .t1{color:#b9b9b9!important}
.coupon .pt_right.bg3 .t3{color:#b9b9b9!important}
.coupon .pt_right.bg3 .t4{color:#999999!important}
.coupon .radiobox{position:absolute;left:0;padding:20rpx}
.coupon .radio{flex-shrink:0;width: 32rpx;height: 32rpx;background: #FFFFFF;border: 2rpx solid #BFBFBF;border-radius: 50%;}
.coupon .radio .radio-img{width:100%;height:100%}
.order-box .bottom{ width:100%; padding:10rpx 0px; border-top: 1px #e5e5e5 solid; color: #555;}
.order-box .op{ display:flex;align-items:center;width:100%; padding:20rpx 0px; border-top: 1px #e5e5e5 solid; color: #555;justify-content: flex-end;}
.btn2{margin-right:20rpx;width:120rpx;height:60rpx;line-height:60rpx;color:#333;background:#fff;border:1px solid #cdcdcd;border-radius:3px;text-align:center}
.bottom-but-view{width: 100%;position: fixed;bottom: 0rpx;left: 0rpx;/* #ifdef H5*/margin-bottom: 52rpx;/* #endif */}
.savebtn{ width: 90%; height:96rpx; line-height: 96rpx; text-align:center;border-radius:48rpx; color: #fff;font-weight:bold;margin: 0 5%; border: none; }
</style>