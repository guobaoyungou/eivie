<template>
<view class="container">
	<block v-if="isload">
		<view class="orderinfo">
			<view class="item">
				<text class="t1">提交人</text>
				<text class="flex1"></text>
				<image :src="detail.headimg" style="width:80rpx;height:80rpx;margin-right:8rpx"/>
				<text  style="height:80rpx;line-height:80rpx">{{detail.nickname}}</text>
			</view>
			<view class="item">
				<text class="t1">{{t('会员')}}ID</text>
				<text class="t2">{{detail.mid}}</text>
			</view>
			<view class="item">
				<text class="t1">标题</text>
				<text class="t2">{{detail.title}}</text>
			</view>
			<view v-for="(item, index) in formcontent" :key="index" class="item">
				<text class="t1">{{item.val1}}</text>
				<view class="t2" v-if="item.key=='upload'"><image :src="detail['form'+index]" style="width:50px" mode="widthFix" @tap="previewImage" :data-url="detail['form'+index]"></image></view>
        <!-- #ifdef !H5 && !MP-WEIXIN -->
        <view class="t2"v-else-if="item.key=='upload_file'" style="overflow: hidden;white-space: pre-wrap;word-wrap: break-word;color: #4786BC;">
        		{{detail['form'+index]}}
        </view>
        <!-- #endif -->
        <!-- #ifdef H5 || MP-WEIXIN -->
        <view class="t2"v-else-if="item.key=='upload_file'"  @tap="download" :data-file="detail['form'+index]" style="overflow: hidden;white-space: pre-wrap;word-wrap: break-word;color: #4786BC;">
        		点击下载查看
        </view>
        <!-- #endif -->
        <view class="t2" v-else-if="item.key=='upload_video'"><video :src="detail['form'+index]"  style="width:80%;height:300rpx;margin-top:20rpx"></video></view>
        <view class="t2"v-else-if="item.key=='upload_pics'">
          <block v-for="(item2,index2) in detail['form'+index]" :key="index2">
            <image :src="item2" style="width:50px;margin-right: 10rpx;" mode="widthFix" @tap="previewImage" :data-url="item2"></image>
          </block>
        </view>
        <text class="t2" v-else>{{detail['form'+index]}}</text>
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
			<block v-if="form.payset==1">
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
		<view style="width:100%;height:160rpx"></view>
		<view class="bottom notabbarbot">
			<view class="btn2" @click="copyForm">复制</view>
			<view v-if="detail.status==0" class="btn2" @tap="setst" :data-st="1" :data-id="detail.id">确认</view>
			<view v-if="detail.status==0" class="btn2" @tap="setst2" :data-st="2" :data-id="detail.id">驳回</view>
			<view class="btn2" @tap="del" :data-id="detail.id">删除</view>
		</view>
		<uni-popup id="dialogSetst2" ref="dialogSetst2" type="dialog">
			<uni-popup-dialog mode="input" title="驳回原因" :value="detail.reason" placeholder="请输入驳回原因" @confirm="setst2confirm"></uni-popup-dialog>
		</uni-popup>
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
		copyForm(){
			let that = this;
			uni.showModal({
				title: '提示',
				content: '确定要复制此表单内容吗？',
				success: function (res) {
					if (res.confirm) {
						let text = '';
						let status = '';
						if(that.detail.status == 0){
							if(!that.detail.payorderid || that.detail.paystatus==1){
								status = '待确认';
							}else if(that.detail.payorderid && that.detail.paystatus==0){
								status = '待支付';
							}
						}else if(that.detail.status == 1){
							status = '已确认';
						}else if(that.detail.status == 2){
							status = '已驳回';
						}
						text = `提交人：${that.detail.nickname}\n${that.t('会员')}ID：${that.detail.mid}\n标题：${that.detail.title}\n`;
						that.formcontent.forEach((item,index) => {
							if(item.key == 'upload_pics' || item.key == 'upload') return;
							text +=item.val1+'：'+that.detail['form'+index]+'\n'
						})
						text += `提交时间：${that.detail.createtime}\n审核状态：${status}\n`;
						if(that.detail.status == 2){
							text += `驳回原因：${that.detail.reason}\n`;
						}
						if(that.form.payset == 1){
							if(that.detail.yx_order_discount_rand && that.detail.discount_rand_money>0){
								text += `随机立减：-￥${that.detail.discount_rand_money}\n付款金额：￥${that.detail.money}\n付款方式：${that.detail.paytype}\n`;
							}
							let paystatusText = '';
							if(that.detail.paystatus == 1 && that.detail.isrefund == 0){
								paystatusText = '已付款'
							}
							if(that.detail.paystatus == 1 && that.detail.isrefund == 1){
								paystatusText = '已退款'
							}
							if(that.detail.paystatus == 0){
								paystatusText = '未付款'
							}
							text += `付款状态：${paystatusText}\n`;
						}
						uni.setClipboardData({
							data: text,
							success: function () {
								console.log('复制成功');
							}
						});
					} else if (res.cancel) {
						console.log('用户点击取消');
					}
				}
			});
		},
		getdata: function () {
			var that = this;
			that.loading = true;
			app.get('ApiAdminForm/formdetail', {id: that.opt.id}, function (res) {
				that.loading = false;
				that.form = res.form;
				that.formcontent = res.formcontent;
				that.detail = res.detail;
				that.loaded();
			});
		},
		setst:function(e){
			var that = this;
			var id = e.currentTarget.dataset.id;
			var st = e.currentTarget.dataset.st;
			app.confirm('确定要'+(st==2?'驳回':'确认')+'吗?',function(){
				app.post('ApiAdminForm/formsetst', {id:id,st:st}, function (res) {
					app.success(res.msg);
					setTimeout(function () {
						that.getdata();
					},1000);
				})
			});
		},
		setst2:function(e){
			this.$refs.dialogSetst2.open();
		},
		setst2confirm:function(done,value){
			this.$refs.dialogSetst2.close();
      var that = this;
      app.post('ApiAdminForm/formsetst', {id: that.opt.id,st:2,reason:value}, function (data) {
        app.success(data.msg);
        setTimeout(function () {
          that.getdata();
        }, 1000);
      });
		},
		del:function(e){
			var that = this;
			var id = e.currentTarget.dataset.id;
			app.confirm('确定要删除吗?',function(){
				app.post('ApiAdminForm/formdel', {id:id}, function (res) {
					app.success(res.msg);
					setTimeout(function () {
						app.goto('/admin/index/index');
					},1000);
				})
			});
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
  }
};
</script>
<style>

.orderinfo{ width:100%;margin-top:10rpx;padding: 14rpx 3%;background: #FFF;}
.orderinfo .item{display:flex;width:100%;padding:20rpx 0;border-bottom:1px dashed #ededed;}
.orderinfo .item:last-child{ border-bottom: 0;}
.orderinfo .item .t1{width:200rpx;}
.orderinfo .item .t2{flex:1;text-align:right}
.orderinfo .item .red{color:red}

.bottom{ width: 100%;height:92rpx;padding: 0 20rpx;background: #fff; position: fixed; bottom: 0px;left: 0px;display:flex;justify-content:flex-end;align-items:center;}

.btn1{margin-left:20rpx;width:160rpx;height:60rpx;line-height:60rpx;color:#fff;border-radius:3px;text-align:center}
.btn2{margin-left:20rpx;width:160rpx;height:60rpx;line-height:60rpx;color:#333;background:#fff;border:1px solid #cdcdcd;border-radius:3px;text-align:center}
.btn3{position:absolute;top:60rpx;right:10rpx;font-size:24rpx;width:120rpx;height:50rpx;line-height:50rpx;color:#333;background:#fff;border:1px solid #cdcdcd;border-radius:3px;text-align:center}

</style>