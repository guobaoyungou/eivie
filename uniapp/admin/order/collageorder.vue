<template>
<view class="container">
	<block v-if="isload">
		<dd-tab :itemdata="['全部','待付款','待发货','待收货','已完成','退款']" :itemst="['all','0','1','2','3','10']" :st="st" :isfixed="true" @changetab="changetab"></dd-tab>
		<view style="width:100%;height:100rpx"></view>
		<!-- #ifndef H5 || APP-PLUS -->
		<view class="topsearch flex-y-center">
			<view class="f1 flex-y-center">
				<image class="img" :src="pre_url+'/static/img/search_ico.png'"></image>
				<input :value="keyword" placeholder="输入关键字搜索" placeholder-style="font-size:24rpx;color:#C2C2C2" @confirm="searchConfirm"></input>
			</view>
		</view>
		<!--  #endif -->
		<view class="order-content">
			<block v-for="(item, index) in datalist" :key="index">
				<view class="order-box" @tap="goto" :data-url="'collageorderdetail?id=' + item.id">
					<view class="head">
						<view>订单号：{{item.ordernum}}</view>
						<view class="flex1"></view>
						<text v-if="item.status==0" class="st0">待付款</text>
						<block v-if="item.status==1">
							<block v-if="item.buytype!=1">
								<text v-if="item.team.status==1" class="st1">拼团中</text>
								<text v-if="item.team.status==2 && item.freight_type!=1" class="st1">拼团成功,待发货</text>
								<text v-if="item.team.status==2 && item.freight_type==1" class="st1">拼团成功,待提货</text>
								<text v-if="item.team.status==3" class="st4">拼团失败,已退款</text>
							</block>
							<block v-else>
								<text v-if="item.freight_type!=1" class="st1">待发货</text>
								<text v-if="item.freight_type==1" class="st1">待提货</text>
							</block>
						</block>
						<text v-if="item.status==2" class="st2">待收货</text>
						<text v-if="item.status==3" class="st3">已完成</text>
						<text v-if="item.status==4" class="st4">已关闭</text>
					</view>
					<view class="content" style="border-bottom:none">
						<view @tap="goto" :data-url="'product?id=' + item.proid">
							<image :src="item.propic"></image>
						</view>
						<view class="detail">
							<text class="t1">{{item.proname}}</text>
							<text class="t2">{{item.ggname}}</text>
							<view class="t3"><text class="x1 flex1">￥{{item.sell_price}}</text><text class="x2">×{{item.num}}</text></view>
						</view>
					</view>
					<view class="bottom">
						<text>共计{{item.num}}件商品 实付:￥{{item.totalprice}}</text>
						<text v-if="item.refund_status==1" style="color:red"> 退款中￥{{item.refund_money}}</text>
						<text v-if="item.refund_status==2" style="color:red"> 已退款￥{{item.refund_money}}</text>
						<text v-if="item.refund_status==3" style="color:red"> 退款申请已驳回</text>
					</view>
					<view class="bottom flex-y-center">
						<image :src="item.member.headimg" style="width:40rpx;height:40rpx;border-radius:50%;margin-right:10rpx"/><text style="font-weight:bold;color:#333;margin-right:8rpx">{{item.member.nickname}}</text>(ID:{{item.mid}})
					</view>
					<view class="op"  v-if="sysset.show_operate">
						<view :data-id="item.id" @tap.stop="goto" :data-url="'/adminExt/collage/team?teamid=' + item.teamid" class="btn2">查看团</view>
						<view v-if="item.refund_status==1" class="btn2" @tap.stop="refundnopass" :data-id="item.id">退款驳回</view>
						<view v-if="item.refund_status==1" class="btn2" @tap.stop="refundpass" :data-id="item.id">退款通过</view>
						<view v-if="item.team && item.team.status==2" class="btn2" :data-freighttype="item.freight_type" @tap.stop="fahuo" :data-id="item.id">发货</view>
					</view>
				</view>
			</block>
		</view>
		<nomore v-if="nomore"></nomore>
		<nodata v-if="nodata"></nodata>
		
		<uni-popup id="dialogExpress" ref="dialogExpress" type="dialog">
			<view class="uni-popup-dialog">
				<view class="uni-dialog-title">
					<text class="uni-dialog-title-text">发货</text>
				</view>
				<view class="uni-dialog-content">
					<view>
						<view class="flex-y-center flex-x-center" style="margin:20rpx 20rpx">
							<text style="font-size:28rpx;color:#000">快递公司：</text>
							<picker @change="expresschange" :value="express_index" :range="expressdata" style="font-size:28rpx;border: 1px #eee solid;padding:10rpx;height:70rpx;border-radius:4px;flex:1">
								<view class="picker">{{expressdata[express_index]}}</view>
							</picker>
						</view> 
						<view class="flex-y-center flex-x-center" style="margin:20rpx 20rpx;">
							<view style="font-size:28rpx;color:#555">快递单号：</view>
							<input type="text" placeholder="请输入快递单号" @input="setexpressno" style="border: 1px #eee solid;padding: 10rpx;height:70rpx;border-radius:4px;flex:1;font-size:28rpx;"/>
						</view>
					</view>
				</view>
				<view class="uni-dialog-button-group">
					<view class="uni-dialog-button" @click="dialogExpressClose">
						<text class="uni-dialog-button-text">取消</text>
					</view>
					<view class="uni-dialog-button uni-border-left" @click="confirmfahuo">
						<text class="uni-dialog-button-text uni-button-color">确定</text>
					</view>
				</view>
			</view>
		</uni-popup>
		<uni-popup id="dialogExpress10" ref="dialogExpress10" type="dialog">
			<view class="uni-popup-dialog">
				<view class="uni-dialog-title">
					<text class="uni-dialog-title-text">发货信息</text>
				</view>
				<view class="uni-dialog-content">
					<view>
						<view class="form-item flex" style="border-bottom:0;">
							<view class="f1" style="margin-right:20rpx">物流单照片</view>
							<view class="f2">
								<view class="layui-imgbox" v-if="express_pic">
									<view class="layui-imgbox-close" @tap="removeimg" :data-index="0" data-field="express_pic"><image style="display:block" :src="pre_url+'/static/img/ico-del.png'"></image></view>
									<view class="layui-imgbox-img"><image :src="express_pic" @tap="previewImage" :data-url="express_pic" mode="widthFix"></image></view>
								</view>
								<view class="uploadbtn" :style="'background:url('+pre_url+'/static/img/shaitu_icon.png) no-repeat 60rpx;background-size:80rpx 80rpx;background-color:#F3F3F3;'" @tap="uploadimg" data-field="express_pic" data-pernum="1" v-else></view>
							</view>
							<input type="text" hidden="true" name="express_pic" :value="express_pic" maxlength="-1"/>
						</view>
						<view class="flex-y-center flex-x-center" style="margin:20rpx 20rpx;">
							<view style="font-size:28rpx;color:#555">发货人：</view>
							<input type="text" placeholder="请输入发货人信息" @input="setexpress_fhname" style="border: 1px #eee solid;padding: 10rpx;height:70rpx;border-radius:4px;flex:1;font-size:28rpx;"/>
						</view>
						<view class="flex-y-center flex-x-center" style="margin:20rpx 20rpx;">
							<view style="font-size:28rpx;color:#555">发货地址：</view>
							<input type="text" placeholder="请输入发货地址" @input="setexpress_fhaddress" style="border: 1px #eee solid;padding: 10rpx;height:70rpx;border-radius:4px;flex:1;font-size:28rpx;"/>
						</view>
						<view class="flex-y-center flex-x-center" style="margin:20rpx 20rpx;">
							<view style="font-size:28rpx;color:#555">收货人：</view>
							<input type="text" placeholder="请输入发货人信息" @input="setexpress_shname" style="border: 1px #eee solid;padding: 10rpx;height:70rpx;border-radius:4px;flex:1;font-size:28rpx;"/>
						</view>
						<view class="flex-y-center flex-x-center" style="margin:20rpx 20rpx;">
							<view style="font-size:28rpx;color:#555">收货地址：</view>
							<input type="text" placeholder="请输入发货地址" @input="setexpress_shaddress" style="border: 1px #eee solid;padding: 10rpx;height:70rpx;border-radius:4px;flex:1;font-size:28rpx;"/>
						</view>
						<view class="flex-y-center flex-x-center" style="margin:20rpx 20rpx;">
							<view style="font-size:28rpx;color:#555">备注：</view>
							<input type="text" placeholder="请输入备注" @input="setexpress_remark" style="border: 1px #eee solid;padding: 10rpx;height:70rpx;border-radius:4px;flex:1;font-size:28rpx;"/>
						</view>
					</view>
				</view>
				<view class="uni-dialog-button-group">
					<view class="uni-dialog-button" @click="dialogExpress10Close">
						<text class="uni-dialog-button-text">取消</text>
					</view>
					<view class="uni-dialog-button uni-border-left" @click="confirmfahuo10">
						<text class="uni-dialog-button-text uni-button-color">确定</text>
					</view>
				</view>
			</view>
		</uni-popup>
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
		pre_url:app.globalData.pre_url,
		sysset:{},
		expressdata:[],//快递数据
		express_index:0,
		express_no:'',
		id:''//
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
      app.post('ApiAdminOrder/collageorder', {keyword:that.keyword,st: st,pagenum: pagenum}, function (res) {
				that.loading = false;
        var data = res.datalist;
				that.expressdata = res.expressdata;
				that.sysset = res.sysset
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

	refundnopass: function (e) {
		var that = this;
		var orderid = e.currentTarget.dataset.id
		app.confirm('确定要驳回退款申请吗?', function () {
			app.showLoading('提交中');
			app.post('ApiAdminOrder/refundnopass', { type:'collage',orderid: orderid }, function (data) {
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
			app.post('ApiAdminOrder/refundpass', { type:'collage',orderid: orderid }, function (data) {
				app.showLoading(false);
				app.success(data.msg);
				setTimeout(function () {
					that.getdata();
				}, 1000)
			})
		});
	},
	expresschange:function(e){
		this.express_index = e.detail.value;
	},
	fahuo:function(e){
		var freight_type =  e.currentTarget.dataset.freighttype;
		var id =  e.currentTarget.dataset.id;
		this.id = id;
		if(freight_type==10){
			this.$refs.dialogExpress10.open();
		}else{
			this.$refs.dialogExpress.open();
		}
	},
	dialogExpressClose:function(){
		this.$refs.dialogExpress.close();
	},
	confirmfahuo:function(){
		this.$refs.dialogExpress.close();
		var that = this
		var express_com = this.expressdata[this.express_index]
		that.loading = true;
		app.post('ApiAdminOrder/sendExpress', { type:'collage',orderid: that.id,express_no:that.express_no,express_com:express_com}, function (res) {
			app.success(res.msg);
			that.loading = false;
			setTimeout(function () {
				that.getdata();
			}, 1000)
		})
	},
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
.order-box{ width: 94%;margin:10rpx 3%;padding:16rpx 3%; background: #fff;border-radius:8px}
.order-box .head{ display:flex;width:100%; border-bottom: 1px #f4f4f4 solid; height: 70rpx; line-height: 70rpx; overflow: hidden; color: #999;}
.order-box .head .f1{display:flex;align-items:center;color:#333}
.order-box .head .f1 image{width:34rpx;height:34rpx;margin-right:4px}
.order-box .head .st0{ width: 140rpx; color: #ff8758; text-align: right; }
.order-box .head .st1{ color: #ffc702; text-align: right; }
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
/* 发货弹窗 */

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