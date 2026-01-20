<template>
	<view>
		<block v-if="isload">
			<form @submit="topay">
				<view style="height: 80rpx;"></view>
				<view class="titlebg-postions">
					<view class="titlebg-view" :style="'background:rgba('+t('color1rgb')+')'"></view>
				</view>
				<view class="position-view">
					<view class="order-time-details flex flex-col">
						<view class="name-info flex flex-y-center flex-bt">
							<view class="flex flex-col">
								<view class="name-text">{{product.name}}</view>
							</view>
						</view>
						<view class="calendar-view" v-if="product.date_price && product.date_price.length > 0 && product.type != 2">
							<view class="calendar-view-title">选择日期</view>
							<view class="calendar-date-list ">
								<view class="date-list">
									<view v-if="product.date_price" v-for="(item, index) in product.date_price" :key="index" class="date-item" @tap="selectDateItem(item, index)" :style="{borderColor: index === selectedDateIndex ? t('color1') : '', backgroundColor: index === selectedDateIndex ? 'rgba(' + t('color1rgb') + ', 0.2)': ''}" >
										<view>{{item.week}} {{item.date}}</view>
										<view class="date-price" :style="{color:t('color1')}">￥<text>{{ item.sell_price }}</text></view>
									</view>
								</view>
							</view>
						</view>
						<view class="calendar-view flex-bt" style="padding: 20rpx 0;" v-if="product.type == 2">
							<view class="calendar-view-title">选择日期:</view>
							<view>{{visit_date}} （{{daycount}}晚）</view>
						</view>
						<view class="buynum flex flex-y-center">
							<view class="flex1">购买数量：</view>
							<view class="addnum">
								<view class="minus"><image class="img" :src="pre_url+'/static/img/cart-minus.png'" @tap="gwcminus"/></view>
								<input class="input" type="number" :value="num" @input="gwcinput"></input>
								<view class="plus"><image class="img" :src="pre_url+'/static/img/cart-plus.png'" @tap="gwcplus"/></view>
							</view>
						</view>
						<view class="gou-tips flex flex-y-center" v-if="product.maximum">
							<image :src="`${pre_url}/static/img/hotel/error.png`" ></image>
							最多购买{{product.maximum}}张
						</view>
					</view>
					
					<!-- 游客信息 -->
					<view class="order-options-view flex flex-col">
						<view class="options-title flex flex-bt flex-y-center">
							<view>游客信息</view>
						</view>
						<view v-for="(item, index) in travellerdata" :key="index" class="traveller-data">
							<view class="booking-options booking-options-title" v-if="product.is_single != 1">游客{{index + 1}}</view>
							<view class="booking-options flex flex-y-center flex-bt form-item" v-if="product.need_contact_name">
								<view class="book-title">姓名</view>
								<view class="room-form">
									<input :name="'traveller_name'+index" v-model="travellerdata[index].traveller_name" placeholder="请填写姓名" style="text-align: right;"/>
								</view>
							</view>
							<view class="booking-options flex flex-y-center flex-bt form-item" v-if="product.need_contact_phone">
								<view class="book-title">联系方式</view>
								<view class="room-form">
									<input :name="'traveller_mobile'+index" v-model="travellerdata[index].traveller_mobile"  placeholder="请填写联系方式" style="text-align: right;"/>
								</view>
							</view>
							<view class="booking-options flex flex-y-center flex-bt form-item" v-if="product.need_contact_email">
								<view class="book-title">Email</view>
								<view class="room-form">
									<input :name="'traveller_email'+index" v-model="travellerdata[index].traveller_email"  placeholder="请填写邮箱" style="text-align: right;"/>
								</view>
							</view>
							<view class="booking-options flex flex-y-center flex-bt form-item" v-if="product.need_contact_id_card">
								<view class="book-title">证件号码</view>
								<view class="room-form">
									<input :name="'traveller_credentials'+index" v-model="travellerdata[index].traveller_credentials"  placeholder="请填写证件号码" style="text-align: right;"/>
								</view>
							</view>
						</view>
					</view>
					
					<!--抵扣金抵扣-->
					<view class="price order-options-view flex flex-col" v-if="userinfo.dedamount>0 " >
					  <checkbox-group @change="moneydk" class="flex" style="width:100%">
					    <view class="f1">
					      <view>
					          使用抵扣金抵扣（抵扣金：<text style="color:#e94745">{{userinfo.dedamount}}</text>）,最多可抵扣（<text style="color:#e94745">{{maxusemoney}}</text>）
					      </view>
					      <view style="font-size:24rpx;color:#999" >
					        1、选择此项提交订单时将直接扣除抵扣金
					      </view>
					    </view>
					    <view class="f2" style="font-weight:normal">
					      <checkbox value="1" :checked="usededamount?true:false" style="margin-left:6px;transform:scale(.8)"></checkbox>
					    </view>
					  </checkbox-group>
					</view>
					<!--抵扣金抵扣 end-->

					<view style="height: 300rpx;"></view>
				</view>
			
				<!-- 底部按钮 -->
				<view class="but-view flex flex-col">
					<view class="yuding-view flex flex-y-center flex-bt">
						<view class="price-view flex flex-col">
							<view class="text">共计:</view>
							<block v-if="usededamount">
								<view class="price-text" :style="{color:t('color1')}" >￥{{totalprice}}+
									<text style="font-size: 24rpx;">{{usededamount?usemoney:0}}抵扣金</text>
								</view>
							</block>
							<block v-else>
								<view class="price-text" :style="{color:t('color1')}">￥{{totalprice}}</view>
							</block>
						</view>
						<view class="flex flex-row flex-y-center">
							<button class='but-class1' :style="'background:rgba('+t('color1rgb')+',0.8);color:#FFF'" form-type="submit">购买</button>
						</view>
					</view>
				</view>
			
				</form>
		</block>
	</view>
</template>

<script>
	var app = getApp();
	export default {
		data(){
			return{
				isload:false,
				platform: app.globalData.platform,
				pre_url: app.globalData.pre_url,
				set:[],
				text:[],
				num:1,
				totalprice:0,
				userinfo:[],
				usededamount:false, //使用抵扣金
				usemoney:0, //抵扣额度
				dedamount_dkpercent:0,//抵扣比例
				maxusemoney:0,
				couponList:[],
			  couponvisible: false,
				couponrid: 0,
				couponnametext:'',
				coupon_money: 0,
				id:0,
				product:[],
				travellerdata: [
					{
						traveller_name: '',
						traveller_mobile: '',
						traveller_email: '',
						traveller_credentials: '',
						traveller_credentials_type:'ID_CARD' //证件类型
					}
				],
				selectedDateIndex:-1,
				visit_date:'',//游玩日期
				daycount:0
			}
		},
		onLoad(opt) {
			this.opt = app.getopts(opt);
			this.id = this.opt.id
			
			//酒店
			this.visit_date = this.opt.visit_date || ''; 
			this.daycount = this.opt.daycount || 0;
			
			this.getdata()
		},
		methods:{
			getdata:function(e){
				var that = this
				app.post('ApiMeituanProduct/buy', {id:that.id,date:that.opt.date,visit_date:that.visit_date}, function (res) {
						if(res.status==1){
							that.product = res.product
							if(res.product && res.product.minimum > 0){
								that.num = res.product.minimum //最小起订量
							}
							if(res.dedamount_dkpercent){
								that.dedamount_dkpercent = res.dedamount_dkpercent;
							}
							
							that.userinfo = res.userinfo
							that.loaded();
							that.addTraveller();
							that.calculatePrice();
						}else if (res.status == 0) {
							if (res.msg) {
								app.alert(res.msg, function() {
									if (res.url) {
										app.goto(res.url);
									} else {
										app.goback();
									}
								});
							} else if (res.url) {
								app.goto(res.url);
							} else {
								app.alert('您没有权限');
							}
							return;
						}
				 })
			},
			//加
			gwcplus: function (e) {
			 const maxQuantity = this.product.maximum || Infinity; 
			 this.num = Math.min(this.num + 1, maxQuantity);
        this.addTraveller();
				this.calculatePrice();
			},
			//减
			gwcminus: function (e) {
			  this.num = Math.max(1, this.num - 1);
				this.addTraveller();
				this.calculatePrice();
			},
			//输入
			gwcinput: function (e) {
			  const maxQuantity = this.product.maximum || Infinity;
				let inputValue = parseInt(e.detail.value);
				// 确保输入值在 1 到最大起订量之间
				this.num = isNaN(inputValue) ? 1 : Math.min(Math.max(1, inputValue), maxQuantity);
				this.addUserinfo();
        this.addTraveller();
				this.calculatePrice();
			},
			moneydk: function(e) {
				this.usededamount = !this.usededamount;
				this.calculatePrice();
			},
      //添加游客资料
      addTraveller:function(e){
        var num = this.num;
        var product = this.product;
        var travellerdata = this.travellerdata;
        // 根据 product.is_single 的值计算需要的游客信息数量
        let requiredTravellerCount = 0;
        if (product.is_single === 0) {
          requiredTravellerCount = num; // 一张票一个人的信息
        } else if (product.is_single === 1) {
          requiredTravellerCount = 1; // 不管多少张票都只要一个人的信息
        } else if (product.is_single >= 2) {
          requiredTravellerCount = num * product.is_single; // 一张票对应多个个人的信息
        }

        if (requiredTravellerCount > travellerdata.length) {
          // 购买数量增加，追加游客信息表单
          for (let i = travellerdata.length; i < requiredTravellerCount; i++) {
            travellerdata.push({
              traveller_name: product.need_contact_name ? '' : null,
              traveller_mobile: product.need_contact_phone ? '' : null,
              traveller_email: product.need_contact_email ? '' : null,
              traveller_credentials: product.need_contact_id_card ? '' : null,
              traveller_credentials_type: 'ID_CARD'
            });
          }
        } else if (requiredTravellerCount < travellerdata.length) {
          // 购买数量减少，移除多余的游客信息表单
          travellerdata.splice(requiredTravellerCount);
        }

        this.travellerdata = travellerdata;
      },
			chooseCoupon: function (e) { //选择优惠券
				var couponrid = e.rid;
			  var couponkey = e.key;
			  if (couponrid == this.couponrid) {
			    this.couponkey = 0;
			    this.couponrid = 0;
			    this.coupontype = 1;
			    this.coupon_money = 0;
			    this.couponvisible = false;
			  } else {
			    var couponList = this.couponList;
			    var coupon_money = couponList[couponkey]['money'];
			    var coupontype = couponList[couponkey]['type'];
			    if (coupontype == 4) {
			      coupon_money = this.freightprice;
			    }
					this.couponnametext = couponList[couponkey].couponname;
			 
			    this.couponkey = couponkey;
			    this.couponrid = couponrid;
			    this.coupontype = coupontype;
			    this.coupon_money = coupon_money;
			    this.couponvisible = false;
			  }
			  this.calculatePrice();
			},
			//计算价格
			calculatePrice: function() {
				// 解构获取必要的数据
				var userinfo = this.userinfo
				var product = this.product
				var num = this.num
				var usededamount = this.usededamount
				var usescore = this.usescore
				var coupon_money = this.coupon_money
				var dedamount_dkpercent = this.dedamount_dkpercent;
				let baseTotalPrice = product.sell_price * num;
				
				// 抵扣金抵扣
				let dedamountDeduction = 0;
				var addAmountTotal = (product.add_amount || 0) * num;
				var maxusemoney = parseFloat(addAmountTotal * dedamount_dkpercent / 100).toFixed(2);
				if (usededamount && userinfo.dedamount > 0) {
					//抵扣金抵扣金额不能超过基础总价
					var deductionAmount = Math.min(userinfo.dedamount, maxusemoney);
					dedamountDeduction = Math.min(baseTotalPrice,deductionAmount);
				}
				// 抵扣余额后的价格
				let remainingPrice = baseTotalPrice - dedamountDeduction;

				// 优惠券抵扣
				let couponDeduction = 0;
				if (coupon_money > 0) {
					// 优惠券抵扣金额不能超过抵扣余额后的价格
					couponDeduction = Math.min(coupon_money, remainingPrice);
				}

				const totalprice = Math.max(remainingPrice - couponDeduction, 0);

				// 更新数据
				this.maxusemoney = maxusemoney;
				this.usemoney = dedamountDeduction.toFixed(2);
				this.coupon_money = couponDeduction.toFixed(2);
				this.totalprice = totalprice.toFixed(2);
			},
			topay: function(e) {
				var that = this;
				var product = this.product;
				var travellerdata = this.travellerdata;
				var isSingle = product.is_single === 1; //1 仅需1个游客信息
				var num = this.num;
				if(that.visit_date == '' && product.book_date == 1){
					return app.error('请选择日期');
				}
				if (num <= 0) {
					app.error('购买数量必须大于 0');
					return;
				}
				if (product.maximum && num > product.maximum) {
					app.error(`最多可购买 ${product.maximum} 张`);
					return;
				}
				for (let i = 0; i < travellerdata.length; i++) {
					const traveller = travellerdata[i];
					if (product.need_contact_name &&!traveller.traveller_name.trim()) {
						app.error(isSingle ? '请填写姓名' : `请填写第 ${i + 1} 位游客的姓名`);
						return;
					}
					if (product.need_contact_phone) {
						const phoneValue = traveller.traveller_mobile.trim();
						if (!phoneValue) {
							app.error(isSingle ? '请填写联系方式' : `请填写第 ${i + 1} 位游客的联系方式`);
							return;
						}
						// 验证手机号格式
						const phoneRegex = /^1[3-9]\d{9}$/;
						if (!phoneRegex.test(phoneValue)) {
							app.error(isSingle ? '请填写有效的联系方式' : `请填写第 ${i + 1} 位游客的有效联系方式`);
							return;
						}
					}
					if (product.need_contact_email) {
						const emailValue = traveller.traveller_email.trim();
						if (!emailValue) {
							app.error(isSingle ? '请填写邮箱' : `请填写第 ${i + 1} 位游客的邮箱`);
							return;
						}
						// 验证邮箱格式
						const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
						if (!emailRegex.test(emailValue)) {
							app.error(isSingle ? '请填写有效的邮箱' : `请填写第 ${i + 1} 位游客的有效邮箱`);
							return;
						}
					}
					if (product.need_contact_id_card &&!traveller.traveller_credentials.trim()) {
						app.error(isSingle ? '请填写证件号码' : `请填写第 ${i + 1} 位游客的证件号码`);
						return;
					}
				}
				app.showLoading('提交中');
				app.post('ApiMeituanProduct/createOrder', {id:that.product.id,num:that.num,travellerdata:travellerdata,visit_date:that.visit_date,usededamount:that.usededamount}, function(res) {
					app.showLoading(false);
					if (res.status == 0) {
						return app.error(res.msg);
					}
					return app.goto('/pagesExt/pay/pay?id=' + res.payorderid,'redirectTo');
				});
			},
			selectDateItem:function(date, index){
				this.selectedDateIndex = index;
				this.visit_date = date.bef_date;
				this.product.sell_price = date.sell_price;
				this.calculatePrice();
			}
		}
	}


</script>

<style>
	/*  */
	.popup-but-view .price-view .text{color: #222222;font-size: 24rpx;font-weight: bold;}
	.popup-but-view .price-view .price-text{color:#06D470;font-size: 36rpx;font-weight: bold;margin-top: 15rpx;}
	.popup-but-view .cost-details{color: #06D470;font-size: 24rpx;font-weight: bold;}
	.popup-but-view .cost-details image{width:24rpx;height: 24rpx;margin: 0rpx 20rpx 0rpx 10rpx;}

	.hotelpopup__content .popup-close image{width: 100%;height: 100%;}

	.hotel-equity-view .equity-options .options-title-view image{width: 28rpx;height: 28rpx;margin-right: 20rpx;}
	/*  */
	.hotel-equity-view .promotion-options image{width: 20rpx;height: 20rpx;}
	/*  */
	.hotel-equity-view  .cost-details{width: 100%;padding-bottom: 30rpx;border-bottom: 1px #efefef solid;margin-top: 40rpx;}
	.hotel-equity-view  .cost-details .price-view{padding-bottom: 10rpx;}
	.hotel-equity-view  .cost-details .price-view .price-text{color: rgba(30, 26, 51, 0.8);font-size: 24rpx;}
	/*  */
	.but-view{width: 100%;background: #fff;position: fixed;bottom: 0rpx;padding: 20rpx;z-index: 2;box-shadow: 0rpx 0rpx 10rpx 5rpx #ebebeb;}
	.but-view  .select-view image{width: 20rpx;height: 20rpx;}

	.but-view  .select-view-active image{width: 20rpx;height: 20rpx;}
	.but-view .read-agreement{width: 100%;border-bottom: 1px #e6e6e6 solid;justify-content: flex-start;padding-bottom: 30rpx;padding-top:10rpx;color: #59595D;font-size: 24rpx;}
	.but-view .yuding-view{padding-bottom: env(safe-area-inset-bottom);padding-left: 20rpx;padding-right: 20rpx;padding-top: 30rpx;	}
	.but-view .yuding-view .price-view{}
	.but-view .yuding-view .price-view .text{color: #222222;font-size: 24rpx;font-weight: bold;}
	.but-view .yuding-view .price-view .price-text{color:#06D470;font-size: 36rpx;font-weight: bold;margin-top: 15rpx;}
	.but-view .yuding-view .cost-details{color: #06D470;font-size: 24rpx;font-weight: bold;}
	.but-view .yuding-view .cost-details image{width:24rpx;height: 24rpx;margin: 0rpx 20rpx 0rpx 10rpx;}
	.but-view  .yuding-view .but-class1{background: linear-gradient(90deg, #06D470 0%, #06D4B9 100%);font-size: 32rpx;color: #fff;font-weight: bold;
	width: 216rpx;border-radius: 60rpx;text-align: center;letter-spacing: 3rpx;}

	.position-view{width: 100%;position: absolute;padding-bottom: 100rpx;}
	.position-view .order-time-details{width: 96%;margin: 0 auto;background: #fff;border-radius: 8px;padding:20rpx 30rpx;}
	.order-time-details .name-info{width: 100%;padding: 30rpx 0rpx;}
	.order-time-details .name-info .name-text {color: #1E1A33;font-size: 30rpx;font-weight: bold;}
	.order-time-details .name-info .name-tisp {display:flex;align-items:center;margin-top:20rpx;}
	.order-time-details .hotel-details-view image{width: 22rpx;height: 22rpx;margin-left: 10rpx;}
	.order-time-details .time-warning image{width: 32rpx;height: 32rpx;margin-right: 20rpx;margin-left: 20rpx;}
	.order-time-details .gou-tips{color: #a9a7a7;font-size: 24rpx;}
	.order-time-details .gou-tips image{width: 32rpx;height: 32rpx;}
	/*  */
	.position-view .order-options-view{width: 96%;margin: 20rpx auto 0rpx;background: #fff;border-radius: 8px;padding:20rpx 30rpx;}
	.order-options-view .options-title{color: #1E1A33;font-size: 32rpx;font-weight: bold;padding-bottom: 20rpx;}
	.order-options-view .preferential-view .pre-text image{width: 24rpx;height: 24rpx;margin-left: 20rpx;}
	/*  */
	.titlebg-postions{position: absolute;left: 0;top:0;width: 100%;height: 50vh;overflow: hidden;}
	.titlebg-postions .titlebg-view{width: 1500rpx;height: 1500rpx;background: #08DA70;border-radius:50%;position: absolute;left: 50%;transform: translate(-50%,-50%);}
	/*  */
	.header-back-but image{width: 40rpx;height: 45rpx;}

	.hotel-details-view	.introduce-view .options-intro image{width: 32rpx;height: 32rpx;}
	.hotel-details-view	.introduce-view .options-intro .options-title{color: #1E1A33;font-size: 24rpx;margin-left: 15rpx;}

	/*  */
	.hotel-equity-view .equity-options .options-title-view image{width: 28rpx;height: 28rpx;margin-right: 20rpx;}
	/*  */
	.position-view .order-options-view{width: 96%;margin: 20rpx auto 0rpx;background: #fff;border-radius: 8px;padding:20rpx 30rpx;}
	.order-options-view .options-title{color: #1E1A33;font-size: 32rpx;font-weight: bold;padding-bottom: 20rpx;}
	/* +++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++ */
	.order-options-view .options-title .right-view image{width: 24rpx;height: 24rpx;margin-left: 10rpx;}
	.order-options-view .booking-options-title{border-bottom: 1rpx solid #dedede;}
	.order-options-view .booking-options{width: 100%;padding: 20rpx 0rpx;}
	.order-options-view .booking-options .book-title{color: #888889;font-size: 28rpx;text-align: left;width: 140rpx;}
	.order-options-view .booking-options .room-form{width: 510rpx;}
	.order-options-view .booking-options .room-form input{width: 100%; font-size: 24rpx;}
	/* ++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++ */

	/*余额抵扣*/
	.price .f1 {color: #333}
	.price .f2 {color: #111;font-weight: bold;text-align: right;flex: 1}

	.position-view .buynum{position: relative; padding:10px 0px 10px 0px; }
	.position-view .addnum {font-size: 30rpx;color: #666;width:auto;display:flex;align-items:center}
	.position-view .addnum .plus {width:48rpx;height:36rpx;background:#F6F8F7;display:flex;align-items:center;justify-content:center}
	.position-view .addnum .minus {width:48rpx;height:36rpx;background:#F6F8F7;display:flex;align-items:center;justify-content:center}
	.position-view .addnum .img{width:24rpx;height:24rpx}
	.position-view .addnum .input{flex:1;width:70rpx;border:0;text-align:center;color:#2B2B2B;font-size:24rpx}
	
	.traveller-data{margin-bottom: 20rpx;}
	.calendar-view .calendar-date-list .date-list{display:flex;flex-direction:row;overflow-x:auto;white-space:nowrap;padding:20rpx 0;}
	.calendar-view .calendar-date-list .date-list .date-item{border:1rpx solid #ccc;border-radius:10rpx;padding:20rpx;margin-right:15rpx;min-width:180rpx;font-size:26rpx;flex-shrink:0;width:180rpx}
	.calendar-view .calendar-date-list .date-list .date-item .date-price{font-weight: bold;margin-top: 10rpx;}
</style>