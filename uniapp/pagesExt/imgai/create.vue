<template>
	<view :style="'background:'+bgcolor">
		<block v-if="isload">
		<form @submit="formSubmit" @reset="formReset">
		<view class="page">
			<view class="title">请输入关键词开始你的创作</view>
			<view class="textarea">
				<textarea class="textarea_module" name="ai_text" v-model="ai_text" placeholder="多个关键字以逗号拼接,最多输入100字"></textarea>
				<view class="textarea_text">0/100</view>
			</view>
			<view class="try flex-y-center">
				<view class="try_title">试一试：</view>
				<scroll-view :scroll-x="true" class="try_module">
					<block v-for="(item, idx) in keywords" :key="idx">
						<view class="try_item" :class="{'try_active': keyword_class == item.id}" :data-id="item.id" :data-keywords="item.keyword" @click="writeText">{{item.name}}</view>
					</block>
				</scroll-view>
			</view>
			<view class="title">渲染引擎</view>
			<scroll-view :scroll-x="true" class="render">
				<view class="render_item render_active">百度文心</view>
			</scroll-view>
			<view class="title">选择风格</view>
			<scroll-view :scroll-x="true" class="style">
				<block v-for="(item, idx) in cats" :key="idx">
					<view class="style_item" :data-style="item.name" :data-id="item.id" @tap="choseStyle(item)">
						<image :src="item.pic" mode=""></image>
						<view class="style_title" :class="{'style_active': style_class == item.id}">{{item.name}}</view>
					</view>
				</block>
			</scroll-view>
			<view class="title">图片比例</view>
			<view class="size flex">
				<view class="size_module" @click="choseBili(1)">
					<view class="size_item flex-xy-center" :class="{'style_active': img_bili == 1}">
						<view class="size_tag1"></view>
					</view>
					<view class="size_title">1:1</view>
				</view>
				<view class="size_module" @click="choseBili(2)">
					<view class="size_item flex-xy-center" :class="{'style_active': img_bili == 2}">
						<view class="size_tag2"></view>
					</view>
					<view class="size_title">2:3</view>
				</view>
				<view class="size_module" @click="choseBili(3)">
					<view class="size_item flex-xy-center" :class="{'style_active': img_bili == 3}">
						<view class="size_tag3"></view>
					</view>
					<view class="size_title">3:2</view>
				</view>
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
			<view class="freight" v-if="end_time" :style="'background:'+bgcolor">
				<view class="freighttips">试用有效期截止：{{end_time}}</view>
			</view>
			<button class="btn" form-type="submit">
				开始绘画<text class="btn_tip" v-if="total_pay>0 && pay_way!='free'">(消费{{total_pay}}{{pay_type}})</text>
			</button>
		</view>
		</form>
		</block>
		<loading v-if="loading"></loading>
	</view>
</template>

<script>
	var app = getApp();

	export default {
		data() {
			return {
				loading:false,
				isload: false,
				cats:[],
				keywords: [],
				total_pay:0,
				pay_type:'',
				ai_text: '',
				ai_style: '',
				img_bili:1,
				keyword_class:'',
				style_class:'',
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
				app.get('ApiImgai/getSet', {}, function (data) {
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
				//获取推荐关键词
				app.get('ApiImgai/getKeywords', {}, function (data) {
					that.keywords = data.data;
				});
				//获取风格
				app.get('ApiImgai/getStyle', {}, function (data) {
					that.cats = data.data;
				});
				that.loading = false;
			},
			writeText : function(e){
				var that = this;
				console.log(e.target.dataset.keywords);
				that.ai_text = e.target.dataset.keywords
				that.keyword_class = e.target.dataset.id
			},
			choseStyle : function(e){
				var that = this;
				that.ai_style = e.name
				that.style_class = e.id
			},
			choseBili : function(type){
				var that = this;
				that.img_bili = type
			},
			formSubmit: function (e) {
			  var that = this;
			  console.log(that.ai_text)
			  console.log(that.ai_style)
			  console.log(that.img_bili)
			  if (that.ai_text.length >100) {
			    app.alert('关键词最多支持100字');
			    return;
			  }
			  if(!that.ai_text){
				  app.alert('请输入关键词');
				  return;
			  }
			  if(!that.ai_style){
				  app.alert('请选择风格');
				  return;
			  }
			  if(!that.img_bili){
				  app.alert('请选择图片比例');
				  return;
			  }
			  
				app.showLoading('提交中');
			  app.post("ApiImgai/createOrder", {ai_text: that.ai_text,ai_style:that.ai_style,img_bili:that.img_bili,pay_way:that.pay_way}, function (data) {
				app.showLoading(false);
				console.log(data)
			    if (data.status == 1) {
			      app.success(data.msg);
			      if(data.data.payorderid){
			      	app.goto('/pagesExt/pay/pay?id=' + data.data.payorderid);
			      }	else{
					  //不需要支付
					  app.goto('/pagesExt/imgai/detail?id=' + data.data.oid);
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
	page {
		background: #fff;
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

	.textarea_text {
		position: absolute;
		width: 100%;
		bottom: 30rpx;
		text-align: right;
		font-size: 26rpx;
		color: #999;
		padding: 0 30rpx;
		box-sizing: border-box;
	}

	.try {
		padding: 30rpx 0;
		overflow: hidden;
	}

	.try_title {
		font-size: 26rpx;
		color: #333;
		width: 120rpx;
		flex-shrink: 0;
	}

	.try_module {
		white-space: nowrap;
		width: calc(100% - 120rpx);
	}

	.try_item {
		padding: 10rpx 15rpx;
		font-size: 24rpx;
		color: #333;
		border: 1px solid #f0f0f0;
		border-radius: 4rpx;
		margin: 0 15rpx 0 0;
		display: inline-block;
	}

	.try_item:last-child {
		margin: 0 0 0 0;
	}
	.try_active{
		color: #FF9900;
		border: 1px solid #FF9900;
	}
	
	
	.render {
		white-space: nowrap;
		padding: 30rpx 0;
	}
	
	.render_item {
		padding: 10rpx 15rpx;
		font-size: 24rpx;
		color: #333;
		border: 1px solid #f0f0f0;
		border-radius: 4rpx;
		margin: 0 15rpx 0 0;
		display: inline-block;
	}
	
	.render_item:last-child {
		margin: 0 0 0 0;
	}
	.render_active{
		color: #FF9900;
		border: 1px solid #FF9900;
	}
	
	.style {
		white-space: nowrap;
		padding: 30rpx 0;
	}
	.style_item {
		position: relative;
		width: 170rpx;
		height: 170rpx;
		margin: 0 15rpx 0 0;
		display: inline-block;
	}
	.style_item image{
		width: 100%;
		height: 100%;
	}
	.style_title{
		position: absolute;
		bottom: 0;
		width: 100%;
		box-sizing: border-box;
		height: 35rpx;
		line-height: 35rpx;
		text-align: center;
		padding: 0 10rpx;
		font-size: 22rpx;
		color: #fff;
		background: rgba(0, 0, 0, 0.5);
	}
	.style_item:last-child {
		margin: 0 0 0 0;
	}
	.style_active{
		background: #FF9900;
	}
	
	.size{
		position: relative;
		padding: 30rpx 0;
	}
	.size_module{
		margin: 0 30rpx 0 0;
	}
	.size_item{
		width: 150rpx;
		height: 150rpx;
		border: 2px solid #ddd;
		border-radius: 8rpx;
	}
	.size_tag1{
		width: 120rpx;
		height: 120rpx;
		background: #ddd;
		border-radius: 8rpx;
	}
	.size_tag2{
		width: 84rpx;
		height: 120rpx;
		background: #ddd;
		border-radius: 8rpx;
	}
	.size_tag3{
		height: 84rpx;
		width: 120rpx;
		background: #ddd;
		border-radius: 8rpx;
	}
	.size_title{
		font-size: 26rpx;
		color: #333;
		margin: 20rpx 0 0 0;
		text-align: center;
	}
	.size_active{
		border-color: #FF9900;
	}
	.size_active view{
		background: #FF9900;
	}
	
	.btn{
		display: block;
		height: 90rpx;
		border-radius: 100rpx;
		background: #FF9900;
		color: #fff;
		text-align: center;
		line-height: 90rpx;
		font-size: 28rpx;
	}
	.btn_tip{
		font-size: 22rpx;
	}
	.freight {width: 100%;padding: 20rpx 0;background: #fff;display: flex;flex-direction: column;}
	.freight .f1 {color: #333;margin-bottom: 10rpx}
	.freight .f2 {color: #111111;text-align: right;flex: 1}
	.freight .f3 {width: 24rpx;height: 28rpx;}
	.freighttips {color: red;font-size: 24rpx;}
	.freight-ul {width: 100%;}
	.freight-li {background: #F5F6F8;border-radius: 24rpx;color: #6C737F;font-size: 24rpx;line-height: 48rpx;padding: 0 28rpx;margin: 12rpx 10rpx 12rpx 0;display: inline-block;white-space: break-spaces;max-width: 610rpx;vertical-align: middle;}
</style>