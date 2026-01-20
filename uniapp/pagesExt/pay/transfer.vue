<template>
<view class="container">
	<block v-if="isload">
		<form @submit="formSubmit" @reset="formReset" report-submit="true">
			<!-- <view class="form-content">
				<view class="form-item">
					<text class="label">{{t('转账汇款')}}</text>
				</view>
			</view> -->
			<view class="orderinfo">
				<view class="item">
					<text class="t1">订单编号</text>
					<text class="t2" user-select="true" selectable="true">{{detail.ordernum}}</text>
				</view>
				
				<view class="item">
					<text class="t1">订单金额</text>
					<text class="t2 red">¥{{detail.money}}</text>
				</view>
        <view class="item" v-if="orderDetail.money_transfer">
          <text class="t1">转账金额</text>
          <text class="t2 red">¥{{orderDetail.money_transfer}}</text>
        </view>
				<view class="item">
					<text class="t1">下单时间</text>
					<text class="t2">{{orderDetail.createtime}}</text>
				</view>
				<view class="item" v-if="detail.paytypeid">
					<text class="t1">支付方式</text>
					<text class="t2">{{detail.paytype}}</text>
				</view>
				<view class="item" v-if="pay_transfer_info.pay_transfer_account_name">
					<text class="t1">户名</text>
					<text class="t2">{{pay_transfer_info.pay_transfer_account_name}}</text>
				</view>
				<view class="item" v-if="pay_transfer_info.pay_transfer_account">
					<text class="t1">账号</text>
					<text class="t2">{{pay_transfer_info.pay_transfer_account}}</text>
				</view>
				<view class="item" v-if="pay_transfer_info.pay_transfer_bank">
					<text class="t1">开户行</text>
					<text class="t2">{{pay_transfer_info.pay_transfer_bank}}</text>
				</view>
				<view class="item" v-if="pay_transfer_info.pay_transfer_qrcode">
					<text class="t1">图片</text>
					<view id="content_picpreview" class="flex" style="flex-wrap:wrap;padding-top:20rpx">
						<view v-for="(item, index) in pay_transfer_info.pay_transfer_qrcode_arr" :key="index" class="layui-imgbox">
							<view class="layui-imgbox-img"><image :src="item" @tap="previewImage" :data-url="item" mode="widthFix"></image></view>
						</view>
					</view>
				</view>
				<view class="item" v-if="pay_transfer_info.pay_transfer_desc">
					<text class="text-min">{{pay_transfer_info.pay_transfer_desc}}</text>
				</view>
				<view class="item">
					<text class="t1">审核状态</text>
					<text class="t2">{{detail.check_status_label}}</text>
				</view>
				<view class="item" v-if="detail.check_remark">
					<text class="t1">审核备注</text>
					<text class="t2">{{detail.check_remark}}</text>
				</view>
			</view>
			<view class="form-content">
				<view class="form-item flex-col">
					<view class="label">上传付款凭证(最多三张)</view>
					<view id="content_picpreview" class="flex" style="flex-wrap:wrap;padding-top:20rpx">
						<view v-for="(item, index) in pics" :key="index" class="layui-imgbox">
							<view class="layui-imgbox-close" v-if="detail.check_status != 1" @tap="removeimg" :data-index="index" data-field="pics"><image :src="pre_url+'/static/img/ico-del.png'"></image></view>
							<view class="layui-imgbox-img"><image :src="item" @tap="previewImage" :data-url="item" mode="widthFix"></image></view>
						</view>
						<view class="uploadbtn" :style="'background:url('+pre_url+'/static/img/shaitu_icon.png) no-repeat 60rpx;background-size:80rpx 80rpx;background-color:#F3F3F3;'" @tap="uploadimg" data-field="pics" v-if="pics.length<3 && detail.check_status != 1"></view>
					</view>
				</view>
			</view>
			<button class="btn" v-if="detail.check_status != 1" form-type="submit" :style="{background:t('color1')}">确定</button>
      <view class="btn-a" @tap="goback" v-if="detail.money_recharge_transfer">返回</view>
			<view class="btn-a" @tap="goto" v-else :data-url="'pages/order/detail?id='+detail.orderid">查看订单详情</view>
			<view v-if="detail.transfer_order_parent_check" class="btn-a" @tap="goto" :data-url="'pages/my/usercenter'" style="padding: 0 30rpx">暂不上传凭证</view>
			<view style="padding-top:30rpx"></view>
		</form>
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
				pre_url:app.globalData.pre_url,
				
				pics:[],
				detail:{},
				orderDetail:{},
				pay_transfer_info:{}
			}
		},
		onLoad: function (opt) {
			this.opt = app.getopts(opt);
			this.getdata();
		},
		onPullDownRefresh: function () {
			this.getdata();
		},
		methods: {
			getdata: function () {
				var that = this;
				that.loading = true;
				app.get('ApiPay/transfer', {id: that.opt.id}, function (res) {
					uni.setNavigationBarTitle({
						title: that.t('转账汇款')
					});
					that.loading = false;
					if (res.status == 0) {
						app.error(res.msg);
						return;
					}
					that.detail = res.detail;
					that.pay_transfer_info = res.pay_transfer_info;
					that.orderDetail = res.orderDetail;
					that.pics = res.detail.paypics;
					
					that.loaded();
					//
				});
			},
    formSubmit: function (e) {
      var that = this;
      var id = that.opt.id;
			var pics = that.pics;
			if(pics.length <= 0) {
				app.error('请上传付款凭证');
				return;
			}

			app.showLoading('提交中');
      app.post('ApiPay/transfer', {id: id,pics:pics}, function (res) {
				app.showLoading(false);
        app.alert(res.msg);
        if (res.status == 1) {
         setTimeout(function () {
           app.goto(res.gotourl,'reLaunch');
         }, 1000);
        }
      });
    },
		uploadimg:function(e){
			var that = this;
			var field= e.currentTarget.dataset.field
			var pics = that[field]
			if(!pics) pics = [];
			app.chooseImage(function(urls){
				for(var i=0;i<urls.length;i++){
					pics.push(urls[i]);
				}
			},1)
		},
		removeimg:function(e){
			var that = this;
			var index= e.currentTarget.dataset.index
			var field= e.currentTarget.dataset.field
			var pics = that[field]
			pics.splice(index,1)
		},
		}
	}
</script>

<style>
.btn-a { text-align: center; padding: 30rpx; color: rgb(253, 74, 70);}
.text-min { font-size: 24rpx; color: #999;}
.orderinfo{width:96%;margin:0 2%;border-radius:8rpx;margin-top:16rpx;padding: 14rpx 3%;background: #FFF;}
.orderinfo .item{display:flex;width:100%;padding:20rpx 0;border-bottom:1px dashed #ededed;overflow:hidden}
.orderinfo .item:last-child{ border-bottom: 0;}
.orderinfo .item .t1{width:200rpx;flex-shrink:0}
.orderinfo .item .t2{flex:1;text-align:right}
.orderinfo .item .red{color:red}
.orderinfo .item .grey{color:grey}

.form-item4{width:100%;background: #fff; padding: 20rpx 20rpx;margin-top:1px}
.form-item4 .label{ width:150rpx;}
.layui-imgbox{margin-right:16rpx;margin-bottom:10rpx;font-size:24rpx;position: relative;}
.layui-imgbox-img{display: block;width:200rpx;height:200rpx;padding:2px;border: #d3d3d3 1px solid;background-color: #f6f6f6;overflow:hidden}
.layui-imgbox-img>image{max-width:100%;}
.layui-imgbox-repeat{position: absolute;display: block;width:32rpx;height:32rpx;line-height:28rpx;right: 2px;bottom:2px;color:#999;font-size:30rpx;background:#fff}
.uploadbtn{position:relative;height:200rpx;width:200rpx}
	

.form-content{width:94%;margin:16rpx 3%;border-radius:10rpx;display:flex;flex-direction:column;background:#fff;overflow:hidden}
.form-item{ width:100%;padding: 32rpx 20rpx;}
.form-item .label{ width:100%;height:60rpx;line-height:60rpx}
.form-item .input-item{ width:100%;}
.form-item textarea{ width:100%;height:200rpx;border: 1px #eee solid;padding: 20rpx;}
.form-item input{ width:100%;border: 1px #f5f5f5 solid;padding: 10rpx;height:80rpx}
.form-item .mid{ height:80rpx;line-height:80rpx;padding:0 20rpx;}
.btn{ height:100rpx;line-height: 100rpx;width:90%;margin:0 auto;border-radius:50rpx;margin-top:50rpx;color: #fff;font-size: 30rpx;font-weight:bold}
</style>
