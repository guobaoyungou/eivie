<template>
<view class="container">
	<block v-if="isload">
		<view class="recorddetail">
			<view class="item">
				<text class="t1">标题：</text>
				<text class="t2">{{t('积分')}}报销记录</text>
			</view>
			<view class="item-pics">
				<view class="t1">已上传发票截图</view>
				<view class="flex" style="flex-wrap:wrap;padding-bottom:20rpx;">
					<view v-for="(item, index) in invoicepic" :key="index" class="layui-imgbox">
						<view class="layui-imgbox-img">
							<image :src="item" @tap="previewImage" :data-url="item" mode="widthFix"></image>
						</view>
					</view>
				</view>
			</view>
			<view class="item-pics">
				<view class="t1">已上传现场消费照片</view>
				<view class="flex" style="flex-wrap:wrap;padding-bottom:20rpx;">
					<view v-for="(item, index) in consumepic" :key="index" class="layui-imgbox">
						<view class="layui-imgbox-img">
							<image :src="item" @tap="previewImage" :data-url="item" mode="widthFix"></image>
						</view>
					</view>
				</view>
			</view>
			<view class="item-pics">
				<view class="t1">OCR发票信息</view>
				<view style="padding-bottom:20rpx;">
					<view v-for="(item, index) in detail.invoice_data" :key="index" class="layui-text">
						发票编号：{{item.invoice_number}}
					</view>
				</view>
			</view>
			<view class="item">
				<view class="t1">发票真伪</view>
				<text class="t2 red" v-if="detail.authenticity == 0">发票存疑</text>
				<text class="t2 green" v-if="detail.authenticity == 1">发票为真</text>
				<text class="t2" style="color:#a0a0a0;" v-if="detail.authenticity == -1">待辩真伪</text>
			</view>
			<view class="item">
				<text class="t1">提交人：</text>
				<text class="t2">
					<view style="width: 100rpx; height: 100rpx;">
						<image v-if="detail.userinfo.headimg" :src="detail.userinfo.headimg" style="width: 100%;height: 100%;" mode=""></image>
					</view>
					{{detail.userinfo.nickname}}
				</text>
			</view>
			<view class="item">
				<text class="t1">{{t('会员')}}ID：</text>
				<text class="t2">{{detail.mid}}</text>
			</view>
			<view class="item">
				<text class="t1">提交时间：</text>
				<text class="t2">{{detail.createtime}}</text>
			</view>
			<view class="item" v-if="detail.status > 0">
				<text class="t1">审核时间：</text>
				<text class="t2">{{detail.operatetime}}</text>
			</view>
			<view class="item">
				<text class="t1">审核状态：</text>
				<text class="t2 red" v-if="detail.status == 0">待审核</text>
				<text class="t2 green" v-if="detail.status == 1">已通过</text>
				<text class="t2 yellow" v-if="detail.status == 2">已驳回</text>
				<text class="t2" v-if="detail.status == 3">已关闭</text>
			</view>
			<view class="item">
				<text class="t1">打款状态：</text>
				<text class="t2" v-if="detail.payment_status == 0">未打款</text>
				<text class="t2 red" v-if="detail.payment_status == 1">待打款</text>
				<text class="t2 green" v-if="detail.payment_status == 2">已打款</text>
				<text class="t2" v-if="detail.payment_status == 3">已关闭</text>
			</view>
			<view class="item" v-if="detail.deduct_score">
				<text class="t1">扣除{{t('积分')}}：</text>
				<text class="t2">{{detail.deduct_score}}</text>
			</view>
			<view class="item" v-if="detail.money">
				<text class="t1">打款金额：</text>
				<text class="t2">{{detail.money}}</text>
			</view>
			<view class="item" v-if="detail.status==2">
				<text class="t1">驳回原因：</text>
				<text class="t2" style="color:red">{{detail.reason}}</text>
			</view>
		</view>
		<view style="width:100%;height:160rpx"></view>
		
		<view class="bottom">
			<block v-if="detail.status == 0 && detail.payment_status == 0">
				<view class="btn2" @tap="pass(1)">通过</view>
				<view class="btn2" @tap="nopassPopup">驳回</view>
			</block>
			<block v-if="detail.status == 1 && detail.payment_status == 1">
				<view class="btn2" @tap="nopassPopup">驳回</view>
				<view class="btn2" @tap="dakuan">已打款</view>
			</block>
			<block v-if="detail.payment_status < 2">
				<view class="btn2" @tap="goto" :data-url="'adminedit?id=' + detail.id">编辑</view>
				<view class="btn2" @tap="pass(3)">关闭</view>
			</block>
		</view>
		<uni-popup id="dialogNopass" ref="dialogNopass" type="dialog">
			<uni-popup-dialog mode="input" title="确定要驳回申请吗？" placeholder="请输入驳回原因" @confirm="nopass"></uni-popup-dialog>
		</uni-popup>
		
	</block>
	<loading v-if="loading"></loading>
	<dp-tabbar :opt="opt"></dp-tabbar>
	<popmsg ref="popmsg"></popmsg>
	<wxxieyi></wxxieyi>
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
			menuindex:-1,
			detail:{},
			invoicepic:[],
			consumepic:[],
			deduct_score:'',
			money:'',
			paytype:'',
			payinfo:''
    };
  },
  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.getdata();
  },
	onPullDownRefresh: function () {
		this.getdata();
	},
  onShow:function(){
    this.getdata();
  },
  methods: {
		getdata: function () {
			var that = this;
			that.loading = true;
			app.get('ApiAdminInvoiceBaoxiao/detail', {id: that.opt.id}, function (res) {
				that.loading = false;
				that.detail = res.data;
				that.invoicepic = that.detail.invoice_pics;
				that.consumepic = that.detail.consume_pics;
				if(res.status == 0){
					app.error(res.msg);
					setTimeout(function () {
						app.goto('recordlist');
					}, 3000);
					return;
				}
				that.loaded();
			});
		},
		pass:function(st){
			var that = this;
			var msg = "确定要审核通过吗?";
			if(st == 3){
				msg = "确定要关闭吗"
			}
			app.confirm(msg, function () {
				app.showLoading('提交中');
				app.post('ApiAdminInvoiceBaoxiao/setst', { id:that.detail.id,st:st}, function (data) {
					app.showLoading(false);
					app.success(data.msg);
					setTimeout(function () {
						that.getdata();
					}, 1000)
				})
			});
			return;
		},
		nopassPopup:function(){
			this.$refs.dialogNopass.open();
		},
		nopass: function (done, remark) {
			this.$refs.dialogNopass.close();
			var that = this
			app.post('ApiAdminInvoiceBaoxiao/setst', {id:that.detail.id,st:2,reason:remark}, function (res) {
				app.success(res.msg);
				setTimeout(function () {
					that.getdata();
				}, 1000)
			})
		},
		dakuan:function(){
			var that = this
			app.post('ApiAdminInvoiceBaoxiao/payment', {
				id:that.detail.id,
			}, function (res) {
				if(res.status == 0){
					app.error(res.msg);
					return;
				}
				app.success(res.msg);
				setTimeout(function () {
					that.getdata();
				}, 1000)
			})
		}
  }
};
</script>
<style>
.container{padding-top:10rpx}
.recorddetail{ width:100%;padding: 14rpx 3%;background: #FFF;}
.recorddetail .item{display:flex;width:100%;padding:20rpx 0;border-bottom:1px dashed #ededed;font-size: 26rpx;}
.recorddetail .item:last-child{ border-bottom: 0;}
.recorddetail .item .t1{width:200rpx;}
.recorddetail .item .t2{flex:1;text-align:right}

.bottom{ width: 100%; height:92rpx;padding: 0 20rpx;background: #fff; position: fixed; bottom: 0px;left: 0px;display:flex;justify-content:flex-end;align-items:center;}

.btn1{margin-left:20rpx;min-width:160rpx;padding: 0 20rpx;height:60rpx;line-height:60rpx;color:#fff;border-radius:3px;text-align:center;}
.btn2{margin-left:20rpx;width:160rpx;height:60rpx;line-height:60rpx;color:#333;background:#fff;border:1px solid #cdcdcd;border-radius:3px;text-align:center;}
.btn3{position:absolute;top:60rpx;right:10rpx;font-size:24rpx;width:120rpx;height:50rpx;line-height:50rpx;color:#333;background:#fff;border:1px solid #cdcdcd;border-radius:3px;text-align:center}

.red{color: red;}
.yellow{color:#ffc107;}
.green{color: green;}

.item-pics{width: 100%;border-bottom: 1px dashed #ededed;}
.item-pics .t1{padding: 20rpx 0;}
.layui-imgbox {margin-right: 30rpx;margin-bottom: 10rpx;font-size: 24rpx;position: relative;}
.layui-imgbox-img {display: block;width: 200rpx;height: 200rpx;padding: 2px;	border: #d3d3d3 1px solid;background-color: #f6f6f6;overflow: hidden}
.layui-imgbox-img>image {max-width: 100%;}
.layui-text{line-height: 42rpx;font-size: 24rpx;}

.uni-popup-dialog {width: 300px;border-radius: 5px;background-color: #fff;}
.uni-dialog-title {display: flex;flex-direction: row;justify-content: center;padding-top: 15px;padding-bottom: 5px;}
.uni-dialog-title-text {font-size: 16px;font-weight: 500;}
.uni-dialog-content {display: flex;flex-direction: row;justify-content: center;align-items: center;padding: 5px 5px 15px 5px;}
.uni-dialog-content-text {font-size: 14px;color: #6e6e6e;}
.uni-dialog-button-group {display: flex;flex-direction: row;border-top-color: #f5f5f5;border-top-style: solid;border-top-width: 1px;}
.uni-dialog-button {display: flex;flex: 1;flex-direction: row;justify-content: center;align-items: center;height: 45px;}
.uni-border-left {border-left-color: #f0f0f0;border-left-style: solid;border-left-width: 1px;}
.uni-dialog-button-text {font-size: 14px;}
.uni-button-color {color: #007aff;}
.dialog-input-view{border: 1px #eee solid;display: flex;align-items: center;flex: 1;}
.dialog-input-view image{width: 60rpx;height: 60rpx;}
.dialog-input-view .dialog-input{border:none;outline:none;padding: 10rpx;height:70rpx;border-radius:4px;flex:1;font-size:28rpx;}
.fontcr{font-size:14px;color:#555;}
</style>