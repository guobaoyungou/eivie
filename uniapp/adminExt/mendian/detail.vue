<template>
<view class="container">
	<block v-if="isload">

		<view class="orderinfo" >
			<view class="item">
				<text class="t1">申请人</text>
				<text class="flex1"></text>
				<image :src="detail.headimg" style="width:80rpx;height:80rpx;margin-right:8rpx"/>
				<text  style="height:80rpx;line-height:80rpx">{{detail.nickname?detail.nickname:''}}</text>
			</view>
	
			<view class="item">
				<text class="t1">姓名</text>
				<text class="t2">{{detail.name}}</text>
			</view>
			<view class="item">
				<text class="t1">电话</text>
				<text class="t2">{{detail.tel?detail.tel:''}}</text>
			</view>
			<view class="item">
				<text class="t1">社区名称</text>
				<text class="t2">{{detail.xqname}}</text>
			</view>
			<view class="item">
				<text class="t1">详细地址</text>
				<text class="t2">{{detail.province}}{{detail.city}}{{detail.district}}{{detail.street}}</text>
			</view>
			<view class="item">
				<text class="t1">申请时间</text>
				<text class="t2">{{detail.createtime}}</text>
			</view>
		</view>
		
		<view class="orderinfo">
			<view class="item">
				<text class="t1">状态</text>
				<text class="t2 st0" v-if="detail.check_status==0">待审核</text>
				<text class="t2 st1" v-if="detail.check_status==1">已通过</text>
				<text class="t2 st2" v-if="detail.check_status==2">已驳回</text>
			</view>
			<view class="item" v-if="detail.check_status==2">
				<text class="t1">驳回原因</text>
				<text class="t2" v-if="detail.reason">{{detail.reason}}</text>
			</view>
		</view>

		<view style="width:100%;height:160rpx"></view>

		<view class="bottom notabbarbot" v-if="detail.check_status==0">
			<view v-if="detail.check_status==0" class="btn2" @tap="checkstatus" :data-id="detail.id" data-st="2">驳回</view>
			<view v-if="detail.check_status==0" class="btn2" @tap="checkstatus" :data-id="detail.id" data-st="1">通过</view>
		</view>
		
		<uni-popup id="dialogSetremark" ref="dialogSetremark" type="dialog">
			<uni-popup-dialog mode="input" title="驳回原因" :value="detail.remark" placeholder="请输入驳回原因" @confirm="setremarkconfirm"></uni-popup-dialog>
		</uni-popup>
	</block>
	<loading v-if="loading"></loading>
	<dp-tabbar :opt="opt"></dp-tabbar>
	<popmsg ref="popmsg"></popmsg>
</view>
</template>

<script>
var app = getApp();
var interval = null;

export default {
  data() {
    return {
        opt:{},
        loading:false,
        isload: false,
        menuindex:-1,
        pre_url:app.globalData.pre_url,
				st:0,
				detail:[]
    };
  },
  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.getdata();
  },
  methods: {
		getdata: function () {
			var that = this;
			that.loading = true;
			app.get('ApiAdminMendian/detail', {id: that.opt.id}, function (res) {
				that.loading = false;
				that.detail = res.detail;
				that.loaded();
			});
		},
   
		setremarkconfirm: function (done, remark) {
			this.$refs.dialogSetremark.close();
			var that = this
			app.post('ApiAdminMendian/setcheckst', {id: that.detail.id,reason:remark,st:that.st }, function (res) {
				app.success(res.msg);
				setTimeout(function () {
					that.getdata();
				}, 1000)
			})
    },

		checkstatus: function (e) {
			var that = this;
			var id = e.currentTarget.dataset.id
			var st = e.currentTarget.dataset.st
			that.st = st
			if(st==2){
					this.$refs.dialogSetremark.open();return;
			}
			
			app.confirm('确定要审核通过吗?', function () {
				app.showLoading('提交中');
				app.post('ApiAdminMendian/setcheckst', {id: id,st:st }, function (data) {
					app.showLoading(false);
					app.success(data.msg);
					setTimeout(function (){
						that.getdata();
					}, 1000)
				});
			})
		},

  }
};
</script>
<style>
.ordertop{width:100%;height:220rpx;padding:50rpx 0 0 70rpx}
.ordertop .f1{color:#fff}
.ordertop .f1 .t1{font-size:32rpx;height:60rpx;line-height:60rpx}
.ordertop .f1 .t2{font-size:24rpx}


.orderinfo{width:94%;margin:0 3%;border-radius:8rpx;margin-top:16rpx;margin-top:10rpx;padding: 14rpx 3%;background: #FFF;}
.orderinfo .item{display:flex;width:100%;padding:20rpx 0;border-bottom:1px dashed #ededed;}
.orderinfo .item:last-child{ border-bottom: 0;}
.orderinfo .item .t1{width:200rpx;}
.orderinfo .item .t2{flex:1;text-align:right}
.orderinfo .item .red{color:red}
.orderinfo .item .st1{ color: #219241; }
.orderinfo .item .st0{ color: #F7C952; }
.orderinfo .item .st2{ color: #FD5C58; }

.bottom{ width: 100%;height:92rpx;padding: 0 20rpx;background: #fff; position: fixed; bottom: 0px;left: 0px;display:flex;justify-content:flex-end;align-items:center;}

.btn1{margin-left:20rpx;width:160rpx;height:60rpx;line-height:60rpx;color:#fff;border-radius:3px;text-align:center}
.btn2{margin-left:20rpx;width:160rpx;height:60rpx;line-height:60rpx;color:#333;background:#fff;border:1px solid #cdcdcd;border-radius:3px;text-align:center}
.btn3{position:absolute;top:60rpx;right:10rpx;font-size:24rpx;width:120rpx;height:50rpx;line-height:50rpx;color:#333;background:#fff;border:1px solid #cdcdcd;border-radius:3px;text-align:center}


</style>