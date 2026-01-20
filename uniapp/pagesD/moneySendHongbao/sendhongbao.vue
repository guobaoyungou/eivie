<template>
<view class="page-content">
	<block v-if="isload">
		<view class="top-switch-view flex flex-bt flex-y-center">
			<view class="hb-title">{{resEnvelopeType==2 ? '人均红包':'拼手气红包'}}</view>
			<view class="switch-change-view" @click="changeSwitch">{{`切换为${resEnvelopeType==1 ? '人均':'拼手气'}红包`}}</view>
		</view>
		<view class="options-view flex-bt flex-y-center">
			<view class="options-title flex-y-center">
				<view>{{resEnvelopeType==2 ? '人均金额':'总金额'}}</view>
				<image :src="pre_url+'/static/img/pin.png'" class="pin-icon" v-if="!resEnvelopeType" />
			</view>
			<view class="options-input flex-y-center">
				<input type="digit" class="input-class" v-model="money" placeholder-class="placeholder-class" placeholder="0.00" @input="computeMoney"/>
				<view class="unit">元</view>
			</view>
		</view>
		<view class="options-view flex-bt flex-y-center">
			<view class="options-title flex-y-center">红包个数</view>
			<view class="options-input flex-y-center">
				<input type="number" class="input-class" v-model="num" placeholder-class="placeholder-class" placeholder="0" @input="computeMoney"/>
				<view class="unit">个</view>
			</view>
		</view>
		<view class="options-view flex-bt flex-y-center">
			<view class="options-title flex-y-center">领取规则</view>
			<view class="options-input flex-y-center">
				<checkbox-group class="uni-flex uni-row checkbox-group" @change="ruleChange" style="flex-wrap: wrap">
					<checkbox value="-1" style="transform: scale(0.8)" class="checkbox" :checked="true" >全部</checkbox>
					<checkbox value="0" style="transform: scale(0.8)" class="checkbox ">老用户</checkbox>
					<checkbox value="1" style="transform: scale(0.8)" class="checkbox ">新用户</checkbox>
				</checkbox-group>
			</view>
		</view>
		<view class="options-view flex-bt flex-y-center">
			<view class="options-title flex-y-center">过期日期</view>
			<view class="options-input flex-y-center">
				<view class="flex-y-center">
<!-- 					<picker mode="time" :value="time"  @change="bindTimeChange">
						<view class="uni-input">{{time}}</view>
					</picker> -->
					<picker mode="date" :value="date" :start="startDate" :end="endDate" @change="bindDateChange">
						<view class="date-class" v-if="date">{{date}}</view>
						<view class="placeholder-class" v-else>选择过期日期</view>
					</picker>
					<picker mode="time" :value="time"  @change="bindTimeChange" style="margin-left: 20rpx;">
						<view class="uni-input" v-if="time">{{time}}</view>
						<view class="placeholder-class" v-else>选择时间</view>
					</picker>
				</view>
				<view class="cover-icon flex-xy-center">
					<image :src="pre_url+'/static/img/lt_right_jiantou.png'"  />
				</view>
			</view>
		</view>
		<view class="options-view flex-bt flex-y-center">
			<view class="options-title flex-y-center">红包封面</view>
			<view class="options-input flex-y-center" @click="goto" data-url="hongbaocover">
				<view class="cover-img-view">
					<image :src="pic" mode="scaleToFill" />
				</view>
				<view class="cover-icon flex-xy-center">
					<image :src="pre_url+'/static/img/lt_right_jiantou.png'"  />
				</view>
			</view>
		</view>
		<view class="page-but-view flex-col">
			<view class="price-view flex flex-y-center">
				<view style="font-size: 50rpx;font-weight: normal;">￥</view>
				<view>{{totalmoney >0?totalmoney:'0.00' }}</view>
			</view>
			<view class="but-class" :style="{backgroundColor:t('color1')}" @tap="sendHongbao">
				发红包
			</view>
			<!-- <view class="tisp-text">{{sysset.expire_hour}}小时内未被领取，红包金额将退回</view> -->
		</view>
		<view class="hb-log" @tap="goto" :data-url="'hongbaolog?type=1'">红包记录</view>
	</block>
	<loading v-if="loading"></loading>
</view>
</template>

<script>
	var app = getApp();
	export default {
		data(){
			return{
				 isload: false,
				 loading:false,
				resEnvelopeType:1,
				pre_url: app.globalData.pre_url,
				date:'',
				time:'',
				sysset:[],
				totalmoney:'0.00',//总金额
				money:'',//输入金额
				num:'',//红包数量
				pic:'',
				receive_type:['-1']
			}
		},
		onLoad: function (opt) {
			this.opt = app.getopts(opt);
			this.getdata();
		},
		onShow() {
			console.log('onshow');
			var that = this;
			uni.$once('pic',function(data){
				that.pic = data.picurl
			})
		},
		methods:{
			getdata: function () {
				var that = this;
				that.loading = true;
				app.get('ApiMoneySendHongbao/index', {}, function (res) {
					uni.setNavigationBarTitle({
						title:'发红包'
					});
						that.loading = false;
					if(res.status ==0){
						app.error(res.msg);
						setTimeout(function(){
							app.goback();
						},1000)
						return;
					}
				
					that.sysset = res.sysset;
					that.pic = that.sysset.default_pic;
					that.loaded();
				});
			},
			// 切换红包类型
			changeSwitch(){
				this.resEnvelopeType = this.resEnvelopeType==2?1:2;
				this.totalmoney = 0;
				this.money = 0;
				this.num = 0;
			},
			bindDateChange: function(e) {
			    this.date = e.detail.value
			},
			bindTimeChange: function(e) {
			    this.time = e.detail.value
			},
			ruleChange(e){
				console.log(e);
				this.receive_type = e.detail.value;
			},
			computeMoney(){
				if(this.resEnvelopeType ==1){
					var totalmoney = parseFloat(this.money).toFixed(2);
					this.totalmoney = Number.isNaN(totalmoney)?0:totalmoney;
				}else{
					var totalmoney = this.num * this.money;
					if(totalmoney < 0){
						totalmoney = 0;
					}
					this.totalmoney = parseFloat(totalmoney).toFixed(2);
				}
			},
			sendHongbao(){
				var that = this;
				if(!that.money){
					app.error('请输入金额');
					return;
				}
				if(!that.num){
					app.error('请输入红包个数');
					return;
				}
				if(that.date =='' || that.time ==''){
					app.error('请选择过期时间');
					return;
				}
				var expire_time = that.date +' ' +that.time;
				that.loading = true;
				app.post('ApiMoneySendHongbao/sendHongbao', {money:that.money,num:that.num,expire_time:expire_time,pic:that.pic,type:that.resEnvelopeType,receive_type:that.receive_type}, function (res) {
					that.loading = false;
					if(res.status ==0){
						app.error(res.msg);
						return;
					}
					app.goto('hongbaoshare?id='+res.hbid);
				});
			}
		}
	}
</script>

<style>
	.page-content{width: 100%;padding: 30rpx;}
	.page-content .top-switch-view{width: 100%;font-size: 26rpx;padding: 10rpx 30rpx;}
	.page-content .top-switch-view .hb-title{color: #999999;}
	.page-content .top-switch-view .switch-change-view{color: #1e67c1;}
	.options-view{width: 100%;border-radius: 20rpx;background: #fff;margin-top: 20rpx;padding: 22rpx;}
	.options-view .options-title{font-size: 30rpx;color:#333;font-weight: 400;}
	.options-view .options-title .pin-icon{width: 27rpx;height: 27rpx;margin-left: 10rpx;}
	.options-view .options-input{justify-content: flex-end;}
	.options-view .options-input .input-class{text-align: right;font-size: 30rpx;}
	.placeholder-class{font-size: 28rpx;color: #999999;}
	.options-view .options-input .unit{font-size: 28rpx;color:#333;padding-left: 10rpx;}
	/*  */
	.options-view .options-input .cover-img-view{width: 70rpx;height: 100rpx;border-radius: 4rpx;overflow: hidden;}
	.options-view .options-input .cover-img-view image{width: 100%;height: 100%;}
	.options-view .options-input .cover-icon{width: 24rpx;height: 26rpx;margin-left: 10rpx;}
	.options-view .options-input .cover-icon image{width: 100%;height: 100%;}
	.options-view .options-input .date-class{font-size: 30rpx;color: #000;}
	/*  */
	.page-but-view{width: 100%;margin-top: 120rpx;}
	.page-but-view .price-view{justify-content: center;font-size: 76rpx;color: #000;font-weight: bold;}
	.page-but-view .but-class{font-size: 34rpx;color: #f1f1f1;margin:40rpx auto 20rpx;text-align: center;width: 420rpx;padding: 26rpx 0rpx;border-radius: 50rpx;background: #999999;letter-spacing: 4rpx;}
	.tisp-text{width: 100%;text-align: center;font-size: 28rpx;color: #999999;}
	.hb-log{font-size: 28rpx;color: #1e67c1;position: absolute;left: 50%;transform: translateX(-50%);bottom: calc(30rpx + env(safe-area-inset-bottom));}
</style>