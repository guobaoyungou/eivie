<template>
	<view>
		<view class="top-view" :style="{backgroundColor:tColor('color1')}">
			<view class="title-view flex-y-center flex-bt">
				<view class="title-left-view flex-y-center">
					<view class="title-left-icon flex-xy-center">
						<image :src="typeData.imgurl"></image>
				</view>{{typeData.text}}</view>
				<view class="title-right-view flex-y-center">
					<uni-data-picker class="dp-header-picker" :localdata="arealist" popup-title="地区" @change="areachange" :placeholder="'地区'">
						<view>{{locationCache.address?locationCache.address:'请选择定位'}}</view>
					</uni-data-picker>
					<image :src="pre_url+'/static/img/hdsanjiao.png'"></image></view>
			</view>
		</view>
		<view class="content-view">
			<view class="price-view flex-col">
				<view class="options-view flex-bt">
					<view class="options-title">缴费单位</view>
					<view class="input-view" v-if="!is_open" style="width: 66%;">
						<picker @change="bindPickerChange" :value="pickerIndex" :range="array" range-key='name' style="width: 115%;">
							<view class="uni-input">{{array[pickerIndex].name}}</view>
						</picker>
					</view>
					<view class="input-view" v-else>
						<view class="not-supported ">当前城市暂不支持</view>
					</view>
					<view class="icon-view flex-y-center" style="width: 10rpx;"><image :src="pre_url+'/static/img/goback.jpg'" v-if="!is_open"></image></view>
				</view>
				<view class="options-view flex-bt">
					<view class="options-title">姓名</view>
					<view class="input-view">
						<input class="uni-input" type="text" :adjust-position='false' placeholder="请输入姓名" :disabled='is_open' v-model="recharge_name" />
					</view>
					<view class="icon-view" style="font-size: 24rpx;color: #6489fc;"></view>
				</view>
				<view class="options-view flex-bt">
					<view class="options-title">户号</view>
					<view class="input-view">
						<input class="uni-input" type="number" :adjust-position='false' placeholder="请输入户号" :disabled='is_open' v-model="account_number" />
					</view>
					<!-- 获取户号 -->
					<view class="icon-view" :style="{color:tColor('color1'),fontSzie:'24rpx'}" @click="obtainaCcount">获取户号</view>
				</view>
				<view class="options-view flex-bt" v-if="pageInfo.is_other_amount">
					<view class="options-title">缴费金额</view>
					<view class="input-view" style="width: 61%;">
						<input class="uni-input" type="number" :adjust-position='false' :disabled='is_open' v-model="payment_amount" @input="paymentInput" :placeholder="jiaofeiplace" />
					</view>
					<!-- 获取户号 -->
					<view class="icon-view" style="width: 50rpx;"></view>
				</view>
				<!-- <view class="prompt-text" v-show="pageInfo.min_amount && pageInfo.min_amount > 0">最低充值{{pageInfo.min_amount}}元</view> -->
				<!-- 固定金额 -->
				<view class="fixed-money-view">
					<block v-for="(item,index) in pageInfo.fixed_amount">
						<view class="options-view-s flex-col" @click="optionChange(item,index)" :style="{borderColor: index == optionIndex ? tColor('color1'):''}">
							<view class="options-title" :style="{color:index == optionIndex ? tColor('color1'):''}">{{item.money}}<text style="font-size: 34rpx;margin-left: 5rpx;">元</text></view>
							<view class="preferential-price" v-if="(Number(item.money -discountNum(item.money))).toFixed(2) > 0" :style="{color:index == optionIndex ? tColor('color1'):''}">优惠价 {{discountNum(item.money)}}</view>
						</view>
					</block>
				</view>
				<!-- <view class="notice-view flex-y-center">
					<checkbox class="checkbox-class" @click="checkbox_click" :checked="checked_boolean"></checkbox>
					已经阅读 <view style="color: #6489fc;padding: 0rpx 10rpx;" @click="goto" data-url='/pages/index/main?id=438' v-html="pageInfo.pay_agreement"></view> 和 <view style="color: #6489fc;padding: 0rpx 10rpx;" @click="goto" data-url='/pages/index/main?id=438' v-html="pageInfo.pay_des"></view>
				</view> -->
				<button :style="{backgroundColor:tColor('color1')}" class="btn-class" hover-class='btn-hover-class' :disabled='is_open' @click="nextChange()">下一步</button>
<!-- 				<view style="width: 100%;padding-top:20rpx;text-align: center;">
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
			<!-- #ifndef MP-WEIXIN-->
			<!-- <view :style="{width:'100%',height: menuindex > -1 ? '220rpx':'160rpx'}"></view> -->
			<!-- #endif -->
		</view>
		<!-- <view :class="[menuindex > -1 ? 'tabbarshow':'tabbarnot','payment-records']"><text :style="{color:tColor('color1')}" style="font-size: 28rpx;margin: 0 auto;" @click="goto" :data-url="'record_recharge?item='+`${encodeURIComponent(JSON.stringify(typeData))}`">缴费记录</text></view> -->
		<uni-popup  ref="popup" type="bottom" :safe-area='false'>
			<view class="uni-popup-class" :style="{height:  menuindex > -1 ? 'calc(550rpx +  env(safe-area-inset-bottom))':'500rpx'}">
				<view class="close" @tap="popupChangeClose"><image :src="pre_url+'/static/img/close.png'" class="image"/></view>
				<view class="popup-title-view">{{pageInfo.type_name}}充值</view>
				<view class="popup-price-view flex-col">
					<view class="price-num flex-y-center">
						<text style="font-weight: bold;font-size: 40rpx;">￥</text>
						{{discountNum(payment_amount)}} 
						<view class="price-num-tips" style="margin-top: 5rpx;font-weight: normal;margin-left: 15rpx;" v-if="(Number(payment_amount -discountNum(payment_amount))).toFixed(2) > 0">
							优惠：-{{ (Number(payment_amount -discountNum(payment_amount))).toFixed(2)}}
						</view>
					</view>
					<view class="price-num-tips">{{pageInfo.type_name}}充值{{payment_amount}}元</view>
				</view>
				<button :style="{backgroundColor:tColor('color1')}" class="btn-class" hover-class='btn-hover-class' :disabled='is_open' @click="topay()">确认付款</button>
			</view>
		</uni-popup>
		<uni-popup  ref="popupobtaina" type="bottom" :safe-area='false'>
			<view class="uni-popup-class" :style="{height:  menuindex > -1 ? 'calc(530rpx +  env(safe-area-inset-bottom))':'480rpx'}">
				<view class="close" @tap="popupChangeClose"><image :src="pre_url+'/static/img/close.png'" class="image"/></view>
				<view class="popup-title-view">快速获取户号</view>
				<view class="quick-access">
					<view class="quick-access-title">1.查看缴费通知单</view>
					<view class="quick-access-text">在纸质缴费/催费单上查户号</view>
				</view>
				<view class="quick-access">
					<view class="quick-access-title">2.查询通知短信</view>
					<view class="quick-access-text">搜索由缴费单位发送的催费短信，短信中包含户号</view>
				</view>
				<view class="quick-access">
					<view class="quick-access-title">3.第三方平台查询账单</view>
					<view class="quick-access-text">通过微信、支付宝、京东、抖音等平台查询账单金额</view>
				</view>
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
				 array: [],
				 pickerIndex:0,
				 checked_boolean:false,
				 typeData:{
					 imgurl:'',
					 text:'',
					 type:''
				 },
				 arealist:[],
				 area:'',
				 showlevel:2,
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
				 is_open:false,
				 loading:false,
				 checked_boolean:false,
				 account_number:'',
				 payment_amount:'',
				 paymoney_kid:'',
				 pageInfo:{},
				 optionIndex:null,
				 discount_num:'',
				 recharge_name:'',
				 menuindex:-1,
				 jiaofeiplace:'',
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
			that.initCityAreaList();
		},
		computed:{
			discountNum(money){
				return function(money){
					return Number(money*this.discount_num*0.01).toFixed(2);
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
			obtainaCcount(){
				this.$refs.popupobtaina.open()
			},
			paymentInput(event){
				this.optionIndex = null;
				this.paymoney_kid = '';
			},
			optionChange(item,index){
				let that = this;
				that.paymoney_kid = item.kid;
				that.payment_amount = item.money;
				that.optionIndex = index;
				// if(!that.account_number) return  app.error('请输入户号');
				// if(!that.payment_amount) return  app.error('请输入缴费金额');
				// if (!that.checked_boolean) return app.error('请先阅读并同意协议');
				// that.open();
			},
			open() {
				this.$refs.popup.open()
			},
			popupChangeClose() {
				this.$refs.popup.close();
				this.$refs.popupobtaina.close()
			},
			nextChange(){
				let that = this;
				if(!that.account_number) return  app.error('请输入户号');
				if(!that.recharge_name) return  app.error('请输入姓名');
				if(!that.payment_amount) return  app.error('请输入缴费金额');
				// if (!that.checked_boolean) return app.error('请先阅读并同意协议');
				that.open();
			},
			topay() {
				var that = this;
				that.popupChangeClose();
				app.showLoading('提交中');
				app.post('ApiLivepay/createOrder', {
					city_name: that.locationCache.address,
					type: that.typeData.type,
					company_id: that.array[that.pickerIndex].id,
					pay_money: that.payment_amount,
					pay_money_kid: that.paymoney_kid,
					recharge_number:that.account_number,
					recharge_name:that.recharge_name
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
			getitem(address){
				let that = this;
				that.loading = true;
				app.post('ApiLivepay/getitem',{city_name:address,type:this.typeData.type},function(res){
					that.loading = false;
					if(res.status){
						that.array = res.data.company
						that.discount_num = res.data.company[0].min_discount;
						that.pageInfo = res.data.info;
						if(that.pageInfo.min_amount && that.pageInfo.min_amount > 0) {
							that.jiaofeiplace = '自定义金额'+ '，最低充值'+ that.pageInfo.min_amount +'元';
						}else{
							that.jiaofeiplace = '自定义金额';
						}
						that.is_open = false;
						if(res.data.last_recharge_number)	that.account_number = res.data.last_recharge_number;
						if(res.data.last_recharge_name) that.recharge_name = res.data.last_recharge_name;
						if(!that.typeData.imgurl) that.typeData.imgurl = res.data.info.icon;
						if(!that.typeData.text) that.typeData.text = res.data.info.type_name;
					}else{
						that.is_open = true;
						app.error(res.msg);
					}
				})
			},
			areachange(e){
				var that = this
				const value = e.detail.value
				var area_name = [];
				var showarea = ''
				for(var i=0;i<that.showlevel;i++){
					area_name.push(value[i].text)
					showarea = value[i].text
				}
				that.area = area_name.join(',')
				that.curent_address = showarea
				//全局缓存
				that.locationCache.area = area_name.join(',')
				that.locationCache.address = showarea
					//获取地址中心地标
					app.post('ApiAddress/addressToZuobiao', {
						address:area_name.join('')
					}, function(resp) {
						that.loading = false
						if(resp.status==1){
							that.latitude = resp.latitude
							that.longitude = resp.longitude
							that.locationCache.latitude = resp.latitude;
							that.locationCache.longitude = resp.longitude;
							app.setLocationCacheData(that.locationCache)
							// that.getdata();
						}else{
							app.error('地址解析错误');
						}
					})
				that.getitem(that.locationCache.address)
			},
			initCityAreaList:function(){
				var that = this;
				//地区加载
				if(that.arealist.length==0){
					uni.request({
						url: app.globalData.pre_url+'/static/area.json',
						data: {},
						method: 'GET',
						header: { 'content-type': 'application/json' },
						success: function(res2) {
							if(that.showlevel<3){
								var newlist = [];
								var arealist = res2.data
								for(var i in arealist){
									var item1 = arealist[i]
									if(that.showlevel==2){
										var children = item1.children //市
										var newchildren = [];
										for(var j in children){
											var item2 = children[j]
											item2.children = []; //去掉三级-县的数据
											newchildren.push(item2)
										}
										item1.children = newchildren
									}else{
										item1.children = []; ////去掉二级-市的数据
									}
									newlist.push(item1)
								}
								that.arealist = newlist
							}else{
								that.arealist = res2.data
							}
						}
					});
				}
			},
			 bindPickerChange: function(e) {
			  this.pickerIndex = e.detail.value
				this.discount_num = this.array[this.pickerIndex].min_discount;
			},
			checkbox_click(event){
				this.checked_boolean = !this.checked_boolean;
			}
		}
	}
</script>

<style>
	/deep/ .dp-header-picker .uni-data-tree-dialog{color: #333333;}
	.top-view{width: 100%;height: 200rpx;padding: 25rpx 30rpx;}
	.top-view .title-view{font-size: 28rpx;color: #fff;padding-top: 10rpx;}
	.top-view .title-view .title-left-view{font-size: 28rpx;}
	.top-view .title-view .title-left-view .title-left-icon{width: 60rpx;height: 60rpx;background: #fff;border-radius: 50%;margin-right: 20rpx;}
	.top-view .title-view .title-left-view .title-left-icon image{width: 40rpx;height: 40rpx;}
	.top-view .title-view .title-right-view{font-size: 24rpx;align-items: center;}
	.top-view .title-view .title-right-view image{width: 20rpx;height: 20rpx;margin-left: 10rpx;}
	.content-view{width: 94%;height:auto;position: relative;top:-80rpx;left: 50%;transform: translateX(-50%);}
	.price-view{width: 100%;height:auto;border-radius: 16rpx;padding:20rpx 30rpx;background:#fff;}
	.price-view .options-view{align-items: center;border-bottom: 1px #f0f0f0 solid;width: 100%;padding: 20rpx 0rpx;}
	.price-view .options-view .icon-view{width:115rpx;justify-content: flex-end;text-align: right;}
	.price-view .options-view .icon-view image{width: 10rpx;height: 15rpx;transform: rotate(180deg);}
	.price-view .options-view .options-title{font-size: 28rpx;color: #000;width: 23%;}
	.price-view .options-view .input-view{width: 50%;}
	.price-view .options-view .input-view .not-supported{font-size: 28rpx;color: #b8b8b8;}
	.price-view .options-view .input-view .uni-input{font-size: 28rpx;}
	.fixed-money-view{width: 100%;display: flex;align-items: center;justify-content: flex-start;flex-wrap: wrap;margin: 14rpx 0rpx;}
	.fixed-money-view .options-view-s{width: 31%;height: 125rpx;border: 3rpx #e0e0e0 solid;border-radius:12rpx;margin: 1%;align-items: center;justify-content: center;padding: 20rpx 0rpx;}
	.fixed-money-view .options-view-s .options-title{font-size:40rpx;color: #333;font-weight: bold;display: flex;align-items: center;}
	.fixed-money-view .options-view-s .preferential-price{font-size: 24rpx;color: #828282;margin-top: 5rpx;color: #ca4d4d;}
	.fixed-money-view .options-view-s .options-tisp{font-size: 24rpx;color: #9c9c9c;}
	.prompt-text{width: 100%;text-align: left;font-size: 24rpx;color: #b8b8b8;padding: 8rpx 0rpx;}
	.notice-view{padding: 20rpx 0rpx;margin-top: 50rpx;width: 100%;text-align: left;font-size: 24rpx;color: #b8b8b8;}
	.notice-view .checkbox-class{transform: scale(0.6);}
	.btn-class{width: 100%;padding: 8rpx 0rpx;color: #fff;font-size:30rpx;border-radius: 12rpx;}
	.btn-hover-class{background-color: #a8bcf7;}
	.recharge-text-view{width: 100%;border-radius: 16rpx;background:#fff;padding: 30rpx;margin-top: 20rpx;}
	.recharge-text-view .recharge-title{width: 100%;text-align: left;font-size: 28rpx;color: #333;font-weight: bold;padding-bottom: 20rpx;}
	.payment-records{width: 100%;position: fixed;text-align: center;letter-spacing: 2rpx;height: 150rpx;line-height: 80rpx;background-color: #f6f6f6;}
	.tabbarshow{bottom: calc(35px + env(safe-area-inset-bottom));}
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
	.quick-access{margin-top: 20rpx;}
	.quick-access .quick-access-title{font-size: 28rpx;color: #333;}
	.quick-access .quick-access-text{font-size: 24rpx;color: #999;margin-top: 10rpx;}
</style>