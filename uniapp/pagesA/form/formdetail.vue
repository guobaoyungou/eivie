<template>
<view class="container" :style="form.form_query==1 && form.form_query_bgcolor ? 'min-height:100vh;background-color:'+form.form_query_bgcolor : ''">
	<block v-if="isload">
		<view class="orderinfo" :style="form.form_query==1 && form.form_query_bgcolor ? 'background-color:'+form.form_query_bgcolor+';color:'+form.form_query_txtcolor+'!important' : ''">
			<view class="item" v-if="form.show_name==1">
				<text class="t1">标题</text>
				<text class="t2">{{detail.title}}</text>
			</view>
			<block v-for="(item, index) in formcontent" :key="index" >
			<view class="item" v-if="!item.hidden && item.val12">
				<text class="t1" :class="item.key=='separate'?'title':''">{{item.val1}}</text>
				<text class="t2" v-if="item.key!='upload' && item.key!='upload_file' && item.key!='upload_video' && item.key!='upload_pics'">
				{{detail['form'+index]}}
				</text>
				
				<view class="t2" v-if="item.key=='upload'"><image :src="detail['form'+index]" style="width:50px" mode="widthFix" @tap="previewImage" :data-url="detail['form'+index]"></image></view>
				<!-- #ifdef !H5 && !MP-WEIXIN -->
				<view class="t2" v-if="item.key=='upload_file'" style="overflow: hidden;white-space: pre-wrap;word-wrap: break-word;color: #4786BC;">
						{{detail['form'+index]}}
				</view>
				<!-- #endif -->
				<!-- #ifdef H5 || MP-WEIXIN -->
				<view class="t2" v-if="item.key=='upload_file'"  @tap="download" :data-file="detail['form'+index]" style="overflow: hidden;white-space: pre-wrap;word-wrap: break-word;color: #4786BC;">
						点击下载查看
				</view>
				<!-- #endif -->
        <view class="t2" v-if="item.key=='upload_video'"><video :src="detail['form'+index]"  style="width:80%;height:300rpx;margin-top:20rpx"></video></view>
			</view>
			<view class="item" v-if="item.key=='map' && detail.show_distance" @tap="openLocation" :data-latitude="detail.adr_lat" :data-longitude="detail.adr_lon" >
				<text class="t1">
				</text>
				<view class="t2">
					距离您{{detail.distance}}
					<image :src="pre_url+'/static/img/b_addr.png'" style="width:26rpx;height:26rpx;margin-right:10rpx"/>
					点击导航
				</view>  
			</view>
      <view class="t2" v-if="item.key=='upload_pics'" >
        <block v-for="(item2,index2) in detail['form'+index]" :key="index2">
          <image :src="item2" mode="widthFix" @tap="previewImage" :data-url="item2" style="width:50px;margin-right: 10rpx;"></image>
        </block>
      </view>
			</block>
			
			<view class="feebox" v-if="detail.is_other_fee==1">
				<text class="title">费用明细</text>
				<view class="feelist">
					<view class="feeitem" v-for="(item,index) in detail.fee_items" :key="index">
						<view>{{item.name}}</view>
						<view class="price">￥{{item.money}}</view>
					</view>
				</view>
			</view>
			
			<view class="item" v-if="form.show_time==1">
				<text class="t1">提交时间</text>
				<text class="t2">{{detail.createtime}}</text>
			</view>
			<view class="item" v-if="form.show_audit==1">
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
        <view class="item" v-if="detail.yx_order_discount_rand && detail.discount_rand_money>0">
          <text class="t1">随机立减</text>
          <text class="t2" style="font-size:32rpx;color:#e94745">-￥{{detail.discount_rand_money}}</text>
        </view>
				<view class="item">
					<text class="t1">付款金额</text>
					<text class="t2" style="font-size:32rpx;color:#e94745">￥{{detail.money}}</text>
				</view>
				<view class="item">
					<text class="t1">付款方式</text>
					<text class="t2">{{detail.paytype || ''}}</text>
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
			
			<block v-if="form.hxqrcode_status == 1 && (form.payset == 0 || detail.paystatus == 1)">
				<view class="item">
					<text class="t1">核销状态</text>
					<text class="t2" v-if="detail.hexiao_status==0" style="color:#88e">待核销</text>
					<text class="t2" v-if="detail.hexiao_status==1" style="color:green">已核销</text>
				</view>
				<view class="item flex-col flex-y-center" v-if="detail.hexiao_status==0">
					<view>核销码</view>
					<image :src="hexiaoqr" style="width:250rpx;height:250rpx" @tap="previewImage" :data-url="hexiaoqr"></image>
				</view>
			</block>
			
		</view>

		
		<view style="width:100%;height:calc(160rpx + env(safe-area-inset-bottom));"></view>

		<view class="bottom notabbarbot" v-if="opt.op != 'view'">
      <!-- #ifdef H5 || MP-WEIXIN -->
      <view class="btn2" v-if="detail.is_ht && detail.contract_file" @tap="download" :data-file="detail.contract_file">查看合同</view>
      <!-- #endif -->
			<view class="btn2" @tap="todel" :data-id="detail.id">删除</view>
			<view class="btn2" @tap="goto" :data-url="detail.fromurl+'&fromrecord='+detail.id+'&type=edit'" v-if="detail.fromurl && detail.edit_status">{{detail.edit_name?detail.edit_name:'编辑'}}</view>
			<view class="btn2" @tap="goto" :data-url="detail.fromurl+'&fromrecord='+detail.id" v-if="detail.fromurl && detail.againsubmit">{{againname}}</view>
			<block v-if="detail.payorderid && detail.paystatus==0">
				<view class="btn1" :style="{background:t('color1')}" @tap="goto" :data-url="'/pagesExt/pay/pay?id=' + detail.payorderid">去付款</view>
			</block>
		</view>
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
			menuindex:-1,
			
			detail:{},
			formcontent:[],
			againname:'',
			form:{},
			latitude:'',
			longitude:'',
			hexiaoqr:'',//核销码
			pre_url: app.globalData.pre_url,
			
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
			app.get('ApiMy/formdetail', {id: that.opt.id,op:that.opt.op,latitude:that.latitude,longitude:that.longitude}, function (res) {
				that.loading = false;
				that.form = res.form;
				that.formcontent = res.formcontent;
				that.detail = res.detail;
				that.againname = res.againname;
				if(res.detail.hexiao_qr) that.hexiaoqr = res.detail.hexiao_qr;
				
				if(res.status == 0){
					app.error(res.msg);
					setTimeout(function () {
						app.goto(res.redirect_url);
					}, 3000);
					return;
				}
				
				if(res.detail.show_distance && !res.detail.distance){
					app.getLocation(function(resL){
						that.latitude = resL.latitude
						that.longitude = resL.longitude
						that.getdata()
					})
				}
				that.loaded();
			});
		},
		todel:function(e){
			var id = e.currentTarget.dataset.id;
			app.confirm('确定要删除吗?',function(){
				app.showLoading('删除中');
        app.post('ApiMy/formdelete', {id: id}, function (data) {
					app.showLoading(false);
          app.success(data.msg);
          setTimeout(function () {
            app.goback(true);
          }, 1000);
        });
			})
		},
		download:function(e){
			var that = this;
			var file = e.currentTarget.dataset.file;
			// #ifdef H5
					window.location.href= file;
			// #endif
			
			// #ifdef MP-WEIXIN
			uni.downloadFile({
				url: file, 
				success: (res) => {
					var filePath = res.tempFilePath;
					if (res.statusCode === 200) {
						uni.openDocument({
							filePath: filePath,
							showMenu: true,
							success: function (res) {
								console.log('打开文档成功');
							}
						});
					}
				}
			});
			// #endif
		},
		openLocation:function(e){
			//console.log(e)
			var latitude = parseFloat(e.currentTarget.dataset.latitude)
			var longitude = parseFloat(e.currentTarget.dataset.longitude)
			var address = e.currentTarget.dataset.address
			uni.openLocation({
			 latitude:latitude,
			 longitude:longitude,
			 name:address,
			 scale: 13
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
.orderinfo .item .t1.title{font-size: 36rpx;font-weight: 600;line-height: 80rpx;width:100%}
.orderinfo .item .t2{flex:1;text-align:right}
.orderinfo .item .red{color:red}

.bottom{ width: 100%; height:92rpx;padding: 0 20rpx;background: #fff; position: fixed; bottom: 0px;left: 0px;display:flex;justify-content:flex-end;align-items:center;}

.btn1{margin-left:20rpx;min-width:160rpx;padding: 0 20rpx;height:60rpx;line-height:60rpx;color:#fff;border-radius:3px;text-align:center;}
.btn2{margin-left:20rpx;width:160rpx;height:60rpx;line-height:60rpx;color:#333;background:#fff;border:1px solid #cdcdcd;border-radius:3px;text-align:center;}
.btn3{position:absolute;top:60rpx;right:10rpx;font-size:24rpx;width:120rpx;height:50rpx;line-height:50rpx;color:#333;background:#fff;border:1px solid #cdcdcd;border-radius:3px;text-align:center}

.feebox{border-bottom:1px dashed #ededed;padding: 20rpx 0;}
.feelist{line-height: 50rpx;font-size: 24rpx;}
.feeitem{display: flex;align-items: center;justify-content: space-between;}
.feeitem .price{color: #e9393b;}
.feebox .title{ 
	font-size: 28rpx;
	color: #9c9c9c;
	border-bottom: 2rpx solid #d2d1d1;
	padding-bottom: 4rpx;
	display: inline-block;
	margin-bottom: 8rpx;
}
 .f1{width:28rpx;height:28rpx;margin-right:8rpx}
</style>