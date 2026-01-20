<template>
<view class="container" :style="form.form_query==1 && form.form_query_bgcolor ? 'min-height:100vh;background-color:'+form.form_query_bgcolor : ''">
	<block v-if="isload">
		<view class="orderinfo" :style="form.form_query==1 && form.form_query_bgcolor ? 'background-color:'+form.form_query_bgcolor+';color:'+form.form_query_txtcolor+'!important' : ''">
			<view class="item">
				<text class="t1">标题</text>
				<text class="t2">{{detail.title}}</text>
			</view>
			<view v-for="(item, index) in formcontent" :key="index" class="item">
				<text class="t1">{{item.val1}}</text>
				<text class="t2" v-if="item.key!='upload'">{{detail['form'+index]}}</text>
				<view class="t2" v-else><image :src="detail['form'+index]" style="width:50px" mode="widthFix" @tap="previewImage" :data-url="detail['form'+index]"></image></view>
			</view>
			<view class="item">
				<text class="t1">提交时间</text>
				<text class="t2">{{detail.createtime}}</text>
			</view>
			<view class="item">
				<text class="t1">审核状态</text>
				<text class="t2" v-if="detail.status==0 && (!detail.payorderid||detail.paystatus==1)" style="color:#88e">待确认</text>
				<text class="t2" v-if="detail.status==0 && detail.payorderid && detail.paystatus==0" style="color:red">待支付</text>
				<text class="t2" v-if="detail.status==1" style="color:green">已确认</text>
				<text class="t2" v-if="detail.status==2" style="color:red">已驳回</text>
			</view>
			<view class="item" v-if="detail.status==2">
				<text class="t1">驳回原因</text>
				<text class="t2" style="color:red">{{detail.reason}}</text>
			</view>
			<block v-if="detail.payorderid">
			<view class="item">
				<text class="t1">付款金额</text>
				<text class="t2" style="font-size:32rpx;color:#e94745">￥{{detail.money}}</text>
			</view>
			<view class="item">
				<text class="t1">付款方式</text>
				<text class="t2">{{detail.paytype}}</text>
			</view>
			<view class="item">
				<text class="t1">付款状态</text>
				<text class="t2" v-if="detail.paystatus==1 && detail.isrefund==0" style="color:green">已付款</text>
				<text class="t2" v-if="detail.paystatus==1 && detail.isrefund==1" style="color:red">已退款</text>
				<text class="t2" v-if="detail.paystatus==0" style="color:red">未付款</text>
			</view>
			<view class="item" v-if="detail.paystatus>0 && detail.paytime">
				<text class="t1">付款时间</text>
				<text class="t2">{{detail.paytime}}</text>
			</view>
			</block>
		</view>

		
		<view style="width:100%;height:20rpx"></view>

		<!-- <view class="bottom notabbarbot" v-if="opt.op != 'view'">
			<view class="btn2" @tap="todel" :data-id="detail.id">删除</view>
			<view class="btn2" @tap="goto" :data-url="detail.fromurl+'&fromrecord='+detail.id" v-if="detail.fromurl">{{againname}}</view>
			<block v-if="detail.payorderid && detail.paystatus==0">
				<view class="btn1" :style="{background:t('color1')}" @tap="goto" :data-url="'/pages/pay/pay?id=' + detail.payorderid">去付款</view>
			</block>
		</view> -->
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
			
			detail:{},
			formcontent:[],
			againname:'',
			form:{},
    };
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
			app.get('ApiSearchMember/formdetail', {id: that.opt.id,op:that.opt.op}, function (res) {
				that.loading = false;
				that.form = res.form;
				that.formcontent = res.formcontent;
				that.detail = res.detail;
				that.againname = res.againname;
				that.loaded();
			});
		},
		todel:function(e){
			var id = e.currentTarget.dataset.id;
			app.confirm('确定要删除吗?',function(){
				app.showLoading('删除中');
        app.post('ApiSearchMember/formdelete', {id: id}, function (data) {
					app.showLoading(false);
          app.success(data.msg);
          setTimeout(function () {
            app.goback(true);
          }, 1000);
        });
			})
		}
  }
};
</script>
<style>
.container{padding-top:10rpx}
.orderinfo{ width:100%;padding: 14rpx 3%;background: #FFF;}
.orderinfo .item{display:flex;width:100%;padding:20rpx 0;border-bottom:1px dashed #ededed;}
.orderinfo .item:last-child{ border-bottom: 0;}
.orderinfo .item .t1{width:200rpx;}
.orderinfo .item .t2{flex:1;text-align:right}
.orderinfo .item .red{color:red}

.bottom{ width: 100%; height:92rpx;padding: 0 20rpx;background: #fff; position: fixed; bottom: 0px;left: 0px;display:flex;justify-content:flex-end;align-items:center;}

.btn1{margin-left:20rpx;min-width:160rpx;padding: 0 20rpx;height:60rpx;line-height:60rpx;color:#fff;border-radius:3px;text-align:center;}
.btn2{margin-left:20rpx;width:160rpx;height:60rpx;line-height:60rpx;color:#333;background:#fff;border:1px solid #cdcdcd;border-radius:3px;text-align:center;}
.btn3{position:absolute;top:60rpx;right:10rpx;font-size:24rpx;width:120rpx;height:50rpx;line-height:50rpx;color:#333;background:#fff;border:1px solid #cdcdcd;border-radius:3px;text-align:center}

</style>