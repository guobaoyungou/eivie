<template>
	<view class="content" v-if="isload">
		<form @submit="formSubmit">
			<view class="box">
				<view class="form-title">
					请选择发布类型
				</view>
				<view class="form-item">
					<radio-group name="type" @change="changeType">
						<!-- <view class="choose-item" v-if="showvip">
							<view class="choose-radio flex-sb">
								<label>
										<radio value="1" style="transform: scale(0.8);" />是否开通VIP
								</label>
								<view class="choose-price">￥{{zhaopinset.vip_fee}}</view>
							</view>
							<view class="choose-tips">开通VIP后，可成为平台合作商户，可在线发布产品</view>
						</view> -->
						<view class="choose-item">
							<view class="choose-radio flex-sb">
								<label>
										<radio value="2" style="transform: scale(0.8);" />是否置顶
								</label>
								<view class="hui"><text class="choose-price">￥{{zhaopinset.top_per_fee}}</text></view>
							</view>
							<view class="choose-tips">招聘置顶后，将在平台优先展示</view>
						</view>
						<view class="choose-item">
							<view class="choose-radio">
								<label>
										<radio value="3" style="transform: scale(0.8);" />是否担保招聘
								</label>
							</view>
							<view class="choose-tips">托管平台，享受签约技师优选推荐</view>
						</view>
						<view class="choose-item">
							<view class="choose-radio">
								<label>
										<radio value="4" style="transform: scale(0.8);" />免费发布
								</label>
							</view>
							<view class="choose-tips">无需任何费用</view>
						</view>
					</radio-group>
				</view>
			</view>
			<view class="form-option">
				<button class="btn" :style="{background:'linear-gradient(-90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)',color:'#ffffff'}" @tap="next">确定</button>
			</view>
		</form>
		<!-- <view v-if="copyright!=''" class="copyright">{{copyright}}</view> -->
		<loading v-if="loading"></loading>
		<popmsg ref="popmsg"></popmsg>
	</view>
</template>
<script>
	var app = getApp();
	export default {
		data() {
			return {
				opt: {},
				loading: false,
				isload: false,
				pre_url: app.globalData.pre_url,
				zhaopinset:{},
				showvip:false,
				type:0,
				needApprove:false
			}
		},
		onLoad: function(opt) {
			this.opt = app.getopts(opt);
			this.getdata();
		},
		onPullDownRefresh: function(e) {
			
		},
		methods: {
			getdata: function() {
				var that = this;
				var opt = this.opt
				that.loading = true
				that.loaded()
				app.get('ApiZhaopin/zhaopinStep2', {}, function (res) {
						that.loading = false;
						that.zhaopinset = res.zhaopinset;
						that.showvip = res.showvip;
						that.needApprove = res.needApprove
						that.loaded();
				});
			},
			changeType:function(e){
				var that = this
				var type = e.detail.value
				that.type = type
			},
			next: function (e) {
				var that = this;
				that.loading = true
				var type = that.type
			  if(type==1){
					//开通vip先缴费
					that.loading = false;
					app.post('ApiZhaopin/vipApply', {id:that.opt.id}, function (res) {
						that.loading = false;
						if(res.status==1 && res.payorderid){
							if(that.needApprove){
								var tourl = encodeURIComponent('/zhaopin/zhaopin/apply?vipoid='+res.orderid);
							}else{
								var tourl = encodeURIComponent('/zhaopin/zhaopin/my?type=2&st=1');
							}
							app.goto('/pages/pay/pay?id=' + res.payorderid + '&tourl='+tourl);
						}else{
							app.alert(res.msg)
							return;
						}
					});
				}else if(type==2){
					//置顶
					app.goto('top?id='+that.opt.id)
				}else if(type==3){
					//担保
					app.goto('top?isas=1&id='+that.opt.id)
				}else{
					if(that.needApprove){
						app.goto('/zhaopin/zhaopin/apply?zid='+that.opt.id)
					}else{
						app.success('发布成功')
						setTimeout(function(){
							app.goto('/zhaopin/zhaopin/my?type=2')
						},1000)
						
					}
					// app.goto('top?as=1&id='+that.opt.id)
				}
			},
	}
}
</script>
<style>
	@import "../common.css";
	page{background: #FFFFFF;}
	.content{color:#222222;padding: 30rpx;}
	.hui{color: #aaaaaa;}
	.form-title{font-size: 36rpx;font-weight: 500;line-height: 70rpx;}
	.choose-item{padding: 20rpx 0;}
	.choose-tips{padding-left: 50rpx;color: #aaaaaa;font-size: 24rpx;}
	.choose-price{color: #F05525;font-size: 34rpx;}
	/* 行排列 */
	.form-item-row{border-bottom: 1rpx solid #f6f6f6; padding: 20rpx 0;}
	.form-item-row .form-label,.form-item-row .form-value{width: 100%;}
	.form-item-row .form-value textarea{width: 100%;height: 200rpx;}
	
	.form-option{display: flex;justify-content: center;height: 80rpx;line-height: 80rpx;margin-top: 30rpx;}
	.form-option .btn{text-align: center;width: 95%;border-radius: 90rpx;}
	.form-option .btn1{border: 1rpx solid #CCCCCC;margin-left: 0;}
	</style>
