<template>
	<view :style="'background:'+bgcolor">
		<image class="banner"
			:src="pre_url+'/static/images/video_bg.png'"
			mode="widthFix"></image>
		<form @submit="formSubmit" @reset="formReset">
		<view class="page">
			<view class="title">今日播报</view>
			<view class="textarea">
				<textarea class="textarea_module" v-model="video_url" placeholder="请粘贴分享链接"></textarea>
			</view>
			<view class="freight" v-if="!end_time" :style="'background:'+bgcolor">
				<view class="f1">支付方式</view>
				<view class="freight-ul">
					<view style="width:100%;overflow-y:hidden;overflow-x:scroll;white-space: nowrap;">
						<block v-for="(item, idx2) in pay_ways" :key="idx2">
							<view class="freight-li"
								:style="pay_way==item.type?'color:'+t('color1')+';background:rgba('+t('color1rgb')+',0.2)':''"
								@tap="changePayway" :data-type="item.type" :data-totalpay="item.num" :data-index="idx2">{{item.name}}
							</view>
						</block>
					</view>
				</view>
				<block v-for="(item, idx2) in pay_ways" :key="idx2">
					<view class="freighttips" v-if="pay_way==item.type">{{item.desc}}</view>
				</block>
			</view>
			<view class="freight" v-if="end_time">
				<view class="freighttips">使用有效期截止：{{end_time}}</view>
			</view>
			<button class="btn" form-type="submit">
				去水印解析<text class="btn_tip" v-if="total_pay>0 && pay_way!='free'">(消费{{total_pay}}{{pay_type}})</text>
			</button>
			<view class="sustain">
				<view class="sustain_title">支持平台</view>
				<view class="sustain_module">
					<view v-for="(item,index) in cats">
						<image :src="item.pic" mode="widthFix" class="sustain_image"></image>
						<view class="sustain_text">{{item.name}}</view>
					</view>
					
				</view>
			</view>
		</view>
		</form>
	</view>
</template>

<script>
	var app = getApp();

	export default {
		data() {
			return {
				video_url:'',
				total_pay:0,
				pay_type:'',
				cats:[],
				pre_url:app.globalData.pre_url,
				pay_ways:[],
				pay_way:'free',
				end_time:'',
				bgcolor:'#fff',
			};
		},

		onLoad: function() {
			this.getdata();
		},
		methods: {
			getdata: function () {
				var that = this;
				that.loading = true;
				that.loaded();
				//获取支付设置
				app.get('ApiVideoSpider/getSet', {}, function (data) {
					if(data.data.pay_money>0){
						that.total_pay = data.data.pay_money;
						that.pay_type='余额';
					}else if(data.data.pay_score>0){
						that.total_pay = data.data.pay_score;
						that.pay_type='积分';
					}
					that.pay_ways = data.data.pay_ways
					that.end_time = data.data.end_time
					that.bgcolor = data.data.bgcolor
				});
				
				//获取支持的平台
				app.get('ApiVideoSpider/getCategory', {}, function (data) {
					that.cats = data.data
				});
				
				that.loading = false;
			},
			formSubmit: function (e) {
				var that = this;

				if(!that.video_url){
				  app.alert('请输入分享链接');
				  return;
				}
			  
				app.showLoading('提交中');
				app.post("ApiVideoSpider/createOrder", {url: that.video_url,pay_way:that.pay_way}, function (data) {
					app.showLoading(false);
					console.log(data)
					if (data.status == 1) {
					    app.success(data.msg);
					    if(data.data.payorderid){
							app.goto('/pagesExt/pay/pay?id=' + data.data.payorderid);
					    }else{
							//不需要支付
							app.goto('/pagesExt/videospider/detail?id=' + data.data.oid);
						}	
					} else {
					    app.error(data.msg);
					}
				});
			},
			changePayway:function(e){
				var that = this
				that.pay_way = e.currentTarget.dataset.type;
				that.total_pay = e.currentTarget.dataset.totalpay;
				console.log(that.pay_way);
			}
		}
	};
</script>
<style>
	

	.banner {
		position: relative;
		width: 100%;
		display: block;
	}

	.page {
		padding: 30rpx;
	}

	.title {
		font-size: 32rpx;
		font-weight: bold;
	}

	.textarea {
		position: relative;
	}

	.textarea_module {
		position: relative;
		padding: 30rpx;
		margin: 20rpx 0 0 0;
		display: block;
		border-radius: 20rpx;
		background: #f0f0f0;
		height: 260rpx;
		font-size: 28rpx;
		width: 100%;
		box-sizing: border-box;
	}

	.btn {
		display: block;
		height: 90rpx;
		border-radius: 100rpx;
		background: #FF9900;
		color: #fff;
		text-align: center;
		line-height: 90rpx;
		font-size: 28rpx;
		margin-top: 30rpx;
	}
	.btn_tip{
		font-size: 22rpx;
	}
	.sustain {
		position: relative;
		margin-top: 50rpx;
	}

	.sustain_title {
		font-size: 28rpx;
		color: #333;
		font-weight: bold;
	}

	.sustain_module {
		position: relative;
		display: grid;
		margin: 15rpx 0 0 0;
		grid-template-columns: repeat(5, 1fr);
		grid-column-gap: 10rpx;
		grid-row-gap: 10rpx;
	}

	.sustain_image {
		width: 90rpx;
		height: 90rpx;
		display: block;
		margin: 0 auto;
	}

	.sustain_text {
		font-size: 24rpx;
		color: #333;
		text-align: center;
		margin: 10rpx 0 0 0;
	}
	.freight {width: 100%;padding: 20rpx 0;background: #fff;display: flex;flex-direction: column;}
	.freight .f1 {color: #333;margin-bottom: 10rpx}
	.freight .f2 {color: #111111;text-align: right;flex: 1}
	.freight .f3 {width: 24rpx;height: 28rpx;}
	.freighttips {color: red;font-size: 24rpx;}
	.freight-ul {width: 100%;}
	.freight-li {background: #F5F6F8;border-radius: 24rpx;color: #6C737F;font-size: 24rpx;line-height: 48rpx;padding: 0 28rpx;margin: 12rpx 10rpx 12rpx 0;display: inline-block;white-space: break-spaces;max-width: 610rpx;vertical-align: middle;}
</style>