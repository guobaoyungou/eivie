<template>
	<view>
		<view class="content-view">
			<view class="top-view" :style="{backgroundColor:tColor('color1')}"></view>
			<view class="view-position">
				<view class="content-top-view">
					<view class="title-view">{{companyText || '手机充值'}}</view>
					<view class="input-view flex-y-center flex-bt">
						<input class="input-class" maxlength='13' :adjust-position='false' type="tel" :value="phone_number" placeholder="请输入手机号" @input='inputChange'
						placeholder-style="font-weight:none;letter-spacing: 3rpx;color:rgba(255,255,255,.6);font-size: 45rpx;" />
					</view>
					<view class="tisp-text flex-y-center">请留意号码是否正确，<text style="color: orangered;opacity: 1;">以免充错</text></view>
				</view>
				<view class="price-view" v-if="pageInfo.fixed_amount && pageInfo.fixed_amount.length > 0">
					<view class="fixd-view">
						<block v-for="(item,index) in pageInfo.fixed_amount">
							<view class="options-view flex-col" @click="optionChange(item)">
								<view class="options-title flex-y-center" :style="{color:item.money_stock == 0 ? '#d0d0d0':''}">{{item.money}}<text style="font-size: 34rpx;margin-left: 5rpx;">元</text></view>
								<view class="preferential-price" style="color:#d0d0d0" v-if='item.money_stock == 0'>补货中</view>
								<view class="preferential-price" v-if="(Number(item.money -discountNum(item.money))).toFixed(2) > 0 && (item.money_stock != 0)">优惠价 {{discountNum(item.money)}}</view>
							</view>
						</block>					
					</view>
<!-- 					<view style="width: 100%;padding-top:20rpx;text-align: center;">
						<text :style="{color:tColor('color1')}" style="font-size: 24rpx;" @click="goto" :data-url="'record_recharge?item='+`${encodeURIComponent(JSON.stringify(typeData))}`">缴费记录</text>
					</view> -->
				</view>
				<!-- 充值说明 -->
				<view class="recharge-text-view" v-if="pageInfo.pay_des">
					<view class="recharge-title">充值说明</view>
					<view style="width: 100%;">
						<parse :content="pageInfo.pay_des"></parse>
					</view>
				</view>
				<!-- 充值协议 -->
				<view class="recharge-text-view" v-if="pageInfo.pay_agreement">
					<view class="recharge-title">充值协议</view>
					<view style="width: 100%;">
						<parse :content="pageInfo.pay_agreement"></parse>
					</view>
				</view>
			</view>
			<!-- <view :style="{width:'100%',height: menuindex > -1 ? '220rpx':'160rpx'}"></view> -->
		</view>
		<!-- <view :class="[menuindex > -1 ? 'tabbarshow':'tabbarnot','payment-records']"><text :style="{color:tColor('color1')}" style="font-size: 28rpx;margin: 0 auto;" @click="goto" :data-url="'record_recharge?item='+`${encodeURIComponent(JSON.stringify(typeData))}`">缴费记录</text></view> -->
		<uni-popup  ref="popup" type="bottom" :safe-area='false'>
			<view class="uni-popup-class" :style="{height:  menuindex > -1 ? 'calc(550rpx +  env(safe-area-inset-bottom))':'500rpx'}">
				<view class="close" @tap="popupChangeClose"><image :src="pre_url+'/static/img/close.png'" class="image"/></view>
				<view class="popup-title-view">手机充值</view>
				<view class="popup-price-view flex-col">
					<view class="price-num flex-y-center"><text style="font-weight: bold;font-size: 40rpx;">￥</text>{{discountNum(paymoneyRes.money)}} <view class="price-num-tips" style="margin-top: 5rpx;font-weight: normal;margin-left: 15rpx;">优惠：-{{ (Number(paymoneyRes.money -discountNum(paymoneyRes.money))).toFixed(2)}}</view></view>
					<view class="price-num-tips">话费充值{{paymoneyRes.money}}元</view>
				</view>
				<button :style="{backgroundColor:tColor('color1')}" class="btn-class" hover-class='btn-hover-class' @click="topay()">确认付款</button>
			</view>
		</uni-popup>
		<loading v-if="loading"></loading>
		<dp-tabbar :opt="opt" @getmenuindex="getmenuindex"></dp-tabbar>
	</view>
</template>

<script>
	var app =getApp();
	export default{
		data(){
			return{
				opt:{},
				phone_number:'',
				phone:'',
				typeData:{
					imgurl:'',
					text:'',
					type:''
				},
				pageInfo:{},
				loading:false,
				locationCache:{
					latitude:'',
					longitude:'',
					area:'',
					address:'',
					poilist:[],
					loc_area_type:-1,
					loc_range_type:-1,
					loc_range:'',
					mendian_id:0,
					mendian_name:'',
					showlevel:2
				},
				paymoneyRes:{},
				companyText:"",
				menuindex:-1,
				pre_url:app.globalData.pre_url,
			}
		},
		onLoad(options) {
			this.opt = app.getopts(options);
			let that = this;
			if(options.type){
				this.typeData.type = options.type;
				this.getaddress();
			}else{
				this.typeData = JSON.parse(decodeURIComponent(decodeURIComponent(options.item)));
				that.locationCache.address = decodeURIComponent(options.address)
				that.getitem(that.locationCache.address)
			}
		},
		computed:{
			discountNum(money){
				return function(money){
					return Number(money*this.pageInfo.discount_ratio*0.01).toFixed(2);
				}
			}
		},
		methods:{
			tColor(text){
				let that = this;
				if(text=='color1'){
					if(app.globalData.initdata.color1 == undefined){
						let timer = setInterval(() => {
							that.tColor('color1')
						},1000)
						clearInterval(timer)
					}else{
						return app.globalData.initdata.color1;
					}
				}else if(text=='color2'){
					return app.globalData.initdata.color2;
				}else if(text=='color1rgb'){
					if(app.globalData.initdata.color1rgb == undefined){
						let timer = setInterval(() => {
							that.tColor('color1rgb')
						},1000)
						clearInterval(timer)
					}else{
						var color1rgb = app.globalData.initdata.color1rgb;
						return color1rgb['red']+','+color1rgb['green']+','+color1rgb['blue'];
					}
				}else if(text=='color2rgb'){
					var color2rgb = app.globalData.initdata.color2rgb;
					return color2rgb['red']+','+color2rgb['green']+','+color2rgb['blue'];
				}else{
					return app.globalData.initdata.textset[text] || text;
				}
			},
			// 无入口进入
			async getaddress(){
				this.locationCache  = await app.getLocationCache();
				this.getitem(this.locationCache.address);
			},
			open() {
				this.$refs.popup.open()
			},
			popupChangeClose() {
				this.$refs.popup.close()
			},
			topay() {
				var that = this;
				that.popupChangeClose();
				app.showLoading('提交中');
				app.post('ApiLivepay/createOrder', {
					city_name: that.locationCache.address,
					type: that.typeData.type,
					company_id: that.companyText,
					pay_money: that.paymoneyRes.money,
					pay_money_kid: that.paymoneyRes.kid,
					recharge_number:that.phone
				}, function(res) {
					app.showLoading(false);
					if (res.status == 0) {
						app.error(res.msg);
						return;
					}
					if(res.payorderid)
					app.goto('/pagesExt/pay/pay?id=' + res.payorderid,'redirectTo');
				});
			},
			optionChange(item){
				if(item.money_stock == 0) return;
				if(!this.phone || !app.isPhone(this.phone)) return app.error('请输入正确手机号');
				this.paymoneyRes = item,
				this.open();
			},
			getitem(address){
				let that = this;
				that.loading = true;
				app.post('ApiLivepay/getitem',{city_name:address,type:this.typeData.type},function(res){
					that.loading = false;
					if(res.status){
						that.pageInfo = res.data.info;
						if(!that.typeData.imgurl) that.typeData.imgurl = res.data.info.icon;
						if(!that.typeData.text) that.typeData.text = res.data.info.type_name;
						if(res.data.last_recharge_number){
							let phonenum = res.data.last_recharge_number.split('');
							phonenum.splice(4,0,' ');
							that.phone_number = phonenum.join('');
							phonenum.splice(9,0,' ');
							that.phone_number = phonenum.join('');
							that.phone = that.phone_number.replace(/\s*/g,'');
							app.showLoading();
							app.post('ApiLivepay/checkPhoneType',{recharge_number:that.phone},function(res){
								app.showLoading(false);
								if(res.status){
									that.companyText = res.phone_name
								}
							})
						}
					}
				})
			},
			inputChange(event){
				let that = this;
				let phonenum = event.detail.value.split('');
				if(event.detail.value.length == 3){phonenum.splice(4,0,' ');this.phone_number = phonenum.join('')}
				if(event.detail.value.length == 8){phonenum.splice(9,0,' ');this.phone_number = phonenum.join('')}
				this.phone = event.detail.value.replace(/\s*/g,'');
				if(this.phone.length == 11){
					app.showLoading();
					app.post('ApiLivepay/checkPhoneType',{recharge_number:this.phone},function(res){
						app.showLoading(false);
						if(res.status){
							that.companyText = res.phone_name
						}
					})
				}
			}
		}
	}
</script>

<style>
	.dp-header-picker .uni-data-tree-dialog{color: #333333;}
	.top-view{width: 100%;height: 34%;position: absolute;top:0;left:0;}
	.content-top-view{width: 94%;padding: 37rpx 0rpx 17rpx;margin: 80rpx auto 0;}
	.content-top-view .title-view{font-size: 28rpx;color: #fff;height: 45rpx;}
	.content-top-view .input-view{width: 100%;padding: 30rpx 0rpx;border-bottom: 1px rgba(255,255,255,.4) solid;}
	.content-top-view .input-view .icon-input{width: 50rpx;height: 50rpx;}
	.content-top-view .input-view .icon-input image{width: 100%;height: 100%;}
	.input-class{font-size: 55rpx;font-weight: normal;color: #fff;height: 55rpx;line-height: 55rpx;}
	.tisp-text{font-size: 24rpx;color: #fff;padding: 20rpx 0rpx;}
	/* .content-view{width: 94%;height:auto;position: absolute;top:0;left: 50%;transform: translateX(-50%);} */
	.content-view{width: 100%;height:95vh;overflow: scroll;position: relative;}
	.view-position{width: 94%;position: relative;top:-80rpx;left: 50%;transform: translateX(-50%);}
	.price-view{width: 100%;height:auto;border-radius: 16rpx;background:#fff;padding: 30rpx;display: flex;flex-direction: column;}
	.price-view .fixd-view{display: flex;align-items: center;justify-content: flex-start;flex-wrap: wrap;}
	.price-view .options-view{width: 31%;height: 125rpx;border: 3rpx #e0e0e0 solid;border-radius:12rpx;margin: 1%;align-items: center;justify-content: center;padding: 20rpx 0rpx;}
	.price-view .options-view .options-title{font-size:42rpx;color: #333;font-weight: bold;}
	.price-view .options-view .options-tisp{font-size:40rpx;color: #333;font-weight: bold;display: flex;align-items: center;}
	.price-view .options-view .preferential-price{font-size: 24rpx;color: #ca4d4d;margin-top: 5rpx;}
	.recharge-text-view{width: 100%;border-radius: 16rpx;background:#fff;padding: 30rpx;margin-top: 20rpx;}
	.recharge-text-view .recharge-title{width: 100%;text-align: left;font-size: 28rpx;color: #333;font-weight: bold;margin-bottom: 20rpx;}
	.payment-records{width: 100%;position: fixed;text-align: center;letter-spacing: 2rpx;height: 150rpx;line-height: 80rpx;background-color: #f6f6f6;}
	.tabbarshow{bottom: calc(30px + env(safe-area-inset-bottom));}
	.tabbarnot{bottom: 0rpx;}
	.uni-popup-class{background: #fff;border-radius:20rpx 20rpx 0px 0px;width: 100%;position: relative;padding: 0rpx 30rpx;}
	.uni-popup-class .close{position: absolute;right: 30rpx;top: 30rpx;}
	.uni-popup-class .image{ width: 30rpx; height:30rpx; }
	.uni-popup-class .popup-title-view{width: 100%;text-align: center;padding: 25rpx 0rpx;font-size: 30rpx;font-weight: bold;}
	.uni-popup-class .popup-price-view{align-items: center;justify-content: center;padding: 35rpx 0rpx 40rpx;}
	.uni-popup-class .popup-price-view .price-num{font-size: 65rpx;color: #333;font-weight: bold;}
	.uni-popup-class .popup-price-view .price-num-tips{font-size: 24rpx;color: #828282;}
	.uni-popup-class .btn-class{width: 100%;padding: 10rpx 0rpx;color: #fff;font-size:30rpx;border-radius: 12rpx;margin-top: 30rpx;}
	.uni-popup-class .btn-hover-class{background-color: #a8bcf7;}
	.uni-popup__wrapper-box{padding-bottom: 0rpx !important;}
</style>