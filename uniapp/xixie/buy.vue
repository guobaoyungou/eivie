<template>
	<view class="container">
		<block v-if="isload">
			<form @submit="topay">

				<view class="address-add" >
                    <view class="flex-y-center" @tap="goto" :data-url="'/pages/address/'+(address.id ? 'address' : 'addressadd')+'?fromPage=buy&type=1'">
                        <view class="f1">
                        	<image class="img" src="/static/img/address.png" />
                        </view>
                        <view class="f2 flex1" v-if="address.id">
                        	<view style="font-weight:bold;color:#111111;font-size:30rpx">{{address.name}} {{address.tel}} <text v-if="address.company">{{address.company}}</text></view>
                        	<view style="font-size:24rpx">{{address.area}} {{address.address}}</view>
                        </view>
                        <view v-else class="f2 flex1">请选择地址</view>
                        <image src="/static/img/arrowright.png" class="f3"></image>
                    </view>
					<view class="flex-y-center" style="margin-top: 10rpx;">
					    <view class="f1">
					    	<image class="img" src="/static/img/address.png" />
					    </view>
					    <view class="f2 flex1" v-if="mendian">
					    	<view style="font-weight:bold;color:#111111;font-size:30rpx">{{mendian.name}}</view>
					    	<view style="font-size:24rpx">{{mendian.address}}</view>
					    </view>
					    <view v-else class="f2 flex1">附近暂无门店</view>
                        <image @tap="callMobile" :data-mobile="mendian.tel" src="/static/img/mobile.png" class="f3"></image>
					</view>
				</view>
                <view style="width: 94%;margin: 20rpx 3%;background: #fff;line-height: 100rpx;text-align: center;overflow: hidden;">
                    <view class="buytype" @tap="changeBuytype" :style="buy_type == 1?'color:#fff;background-color:'+t('color1'):''" data-type="1">
                        上门取件
                    </view>
                    <view class="buytype" @tap="changeBuytype" :style="buy_type == 2?'color:#fff;background-color:'+t('color1'):''" data-type="2">
                        送货到店
                    </view>
                </view>
				<view v-for="(buydata, index) in allbuydata" :key="index" class="buydata" >
					<view class="btitle">
						<image class="img" src="/static/img/ico-shop.png" />{{buydata.business.name}}
					</view>
					<view class="bcontent">
						<view class="product">
							<view v-for="(item, index2) in buydata.prodata" :key="index2" class="item flex">
								<view class="img">
									<image v-if="item.product.pic" :src="item.product.pic"></image>
									<image v-else :src="item.product.pic"></image>
								</view>
								<view class="info flex1">
									<view class="f1">{{item.product.name}}</view>
									<view class="f3">
										<text style="font-weight:bold;">￥{{item.product.sell_price}}</text>
										<text style="padding-left:20rpx"> × {{item.num}}</text>
									</view>
								</view>
							</view>
						</view>
                        <view class="price" v-if="buy_type ==1">
                        	<view class="f1">上门取件时间</view>
                        	<view class="f2" @tap="chooseTime" >{{yydate?yydate:'请选择上门取件时间'}}</view>
                        </view>
						<view class="price">
							<text class="f1">商品金额</text>
							<text class="f2">¥{{buydata.product_price}}</text>
						</view>
                        <view class="price" v-if="buy_type ==1 && peisong_fee>0">
                        	<text class="f1">配送费</text>
                        	<text class="f2">+¥{{peisong_fee}}</text>
                        </view>
						<view class="price">
							<view class="f1">{{t('优惠券')}}</view>
							<view v-if="buydata.couponCount > 0" class="f2" @tap="showCouponList" :data-bid="buydata.bid">
								<block v-if="(buydata.coupons).length>0">
									<text class="couponname" :style="{background:t('color1')}" v-for="(item,index) in buydata.coupons">{{item.couponname}}</text>
								</block>
								<block v-else>
									<text class="couponname" :style="{background:t('color1')}">{{buydata.couponCount+'张可用'}}</text>
								</block>
								<text class="iconfont iconjiantou" style="color:#999;font-weight:normal"></text>
							</view>
							<text class="f2" v-else style="color:#999">无可用{{t('优惠券')}}</text>
						</view>
					</view>
				</view>

				<view style="width: 100%; height:110rpx;"></view>
				<view class="footer flex notabbarbot">
					<view class="text1 flex1">总计：
						<text style="font-weight:bold;font-size:36rpx">￥{{alltotalprice}}</text>
					</view>
					<button class="op" form-type="submit" :style="{background:'linear-gradient(-90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'}" :disabled="submitDisabled">
						提交订单</button>
				</view>
			</form>
			<view v-if="couponvisible" class="popup__container">
				<view class="popup__overlay" @tap.stop="handleClickMask"></view>
				<view class="popup__modal">
					<view class="popup__title">
						<text class="popup__title-text">请选择{{t('优惠券')}}</text>
						<image src="/static/img/close.png" class="popup__close" style="width:36rpx;height:36rpx"
							@tap.stop="handleClickMask" />
					</view>
					<view class="popup__content">
						<couponlist :couponlist="allbuydata[bid].couponList" :choosecoupon="true"
							:selectedrids="allbuydata[bid].couponrids" :bid="bid" @chooseCoupon="chooseCoupon">
						</couponlist>
					</view>
				</view>
			</view>
            <view v-if="timeDialogShow" class="popup__container">
            	<view class="popup__overlay" @tap.stop="hidetimeDialog"></view>
            	<view class="popup__modal">
            		<view class="popup__title">
            			<text class="popup__title-text">请选择时间</text>
            			<image src="/static/img/close.png" class="popup__close" style="width:36rpx;height:36rpx"
            				@tap.stop="hidetimeDialog" />
            		</view>
            		<view class="order-tab">
            			<view class="order-tab2">
            				<block v-for="(item, index) in datelist" :key="index">
            					<view  :class="'item ' + (curTopIndex == index ? 'on' : '')" @tap="switchTopTab" :data-index="index" :data-id="item.id" >
            						<view class="datetext">{{item.weeks}}</view>
            						<view class="datetext2">{{item.date}}</view>
            						<view class="after"  :style="{background:t('color1')}"></view>
            					</view>			
            				</block>
            			</view>
            		</view>
            		<view class="flex daydate">
            			<block v-for="(item,index2) in timelist" :key="index2">
            				<view :class="'date ' + ((timeindex==index2 && item.status==1) ? 'on' : '') + (item.status==0 ?'hui' : '') "  @tap="switchDateTab" :data-index2="index2" :data-status="item.status" :data-time="item.timeint"> {{item.time}}</view>						
            			</block>
            		</view>
            		<view class="op">
            			<button class="tobuy on" :style="{backgroundColor:t('color1')}" @tap="selectDate" >确 定</button>
            		</view>
            	</view>
            </view>
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
				opt: {},
				loading: false,
				isload: false,
				menuindex: -1,

				pre_url:app.globalData.pre_url,

				address: [],
				mendian: '',
                peisong_fee:0,
				totalprice: '0.00',
				couponvisible: false,

				bid: 0,
				nowbid: 0,
				needaddress: 1,
				linkman: '',
				tel: '',
				userinfo: {},

				latitude: "",
				longitude: "",
				allbuydata: {},
				allbuydatawww: {},
				alltotalprice: "",
				items: [],

				submitDisabled:false,
                type:'',
                buy_type:1,
                
                datelist:'',
                yydate:'',
                timeDialogShow: false,
                timelist:'',
                timeindex:-1,
                nowdate:'',
                curTopIndex: 0,
                day: -1,
                days:'请选择服务时间',
                dates:'',
                gotype:''

			};
		},

		onLoad: function(opt) {
            var that = this;
			that.opt = app.getopts(opt);
            if(opt.type){
                that.type = opt.type;
            }
            if(opt.gotype){
                that.gotype = opt.gotype;
            }
			that.getdata();
		},
		onPullDownRefresh: function() {
			this.getdata();
		},
		methods: {
			getdata: function() {
				var that = this;
				that.loading = true;
				app.get('ApiXixie/buy', {
                    gotype:that.gotype,
					prodata: that.opt.prodata
				}, function(res) {
					that.loading = false;
					if (res.status == 0) {
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
							app.alert('您没有权限购买该商品');
						}
						return;
					}

					that.address = res.address;
                    that.mendian = res.mendian;
                    that.peisong_fee = res.peisong_fee;
                    
					that.linkman = res.linkman;
                    
					that.tel = res.tel;
					that.userinfo = res.userinfo;

					that.allbuydata = res.allbuydata;
					that.allbuydatawww = JSON.parse(JSON.stringify(res.allbuydata));

					that.needLocation = res.needLocation;
                    that.datelist = res.datelist;
                    
					that.calculatePrice();
					that.loaded();

					if (res.needLocation == 1) {
						app.getLocation(function(res) {
							var latitude = res.latitude;
							var longitude = res.longitude;
							that.latitude = latitude;
							that.longitude = longitude;
							var allbuydata = that.allbuydata;
							that.allbuydata = allbuydata;
						});
					}
				});
			},
			inputLinkman: function(e) {
				this.linkman = e.detail.value;
			},
			inputTel: function(e) {
				this.tel = e.detail.value;
			},
			inputfield: function(e) {
				var bid = e.currentTarget.dataset.bid;
				var field = e.currentTarget.dataset.field;
				allbuydata2[bid][field] = e.detail.value;
				this.allbuydata2 = allbuydata2;
			},
			//选择收货地址
			chooseAddress: function() {
				app.goto('/pages/address/address?fromPage=buy&type=1');
			},
			//计算价格
			calculatePrice: function() {
				var that = this;
				var address = that.address;
				var allbuydata = that.allbuydata;
				var alltotalprice = 0;

				var needaddress = 0;

				for (var k in allbuydata) {
					var product_price = parseFloat(allbuydata[k].product_price);

					var coupon_money = parseFloat(allbuydata[k].coupon_money); //-优惠券抵扣 


					var totalprice = product_price  -  coupon_money;
					if (totalprice < 0) totalprice = 0; //优惠券不抵扣运费

					allbuydata[k].totalprice = totalprice.toFixed(2);
					alltotalprice += totalprice;
				}
				that.needaddress = needaddress;
                
                if(that.buy_type == 1){
                    alltotalprice = alltotalprice+that.peisong_fee;
                }

				if (alltotalprice < 0) alltotalprice = 0;

				if (alltotalprice < 0) alltotalprice = 0;
				alltotalprice = alltotalprice.toFixed(2);
				that.alltotalprice = alltotalprice;
				that.allbuydata = allbuydata;
			},
			chooseCoupon: function(e) {
				var allbuydata = this.allbuydata;
				var bid = e.bid;
				var couponrid = e.rid;
				var couponkey = e.key;
				var oldcoupons = allbuydata[bid].coupons;
				var oldcouponrids = allbuydata[bid].couponrids;
				var couponList = allbuydata[bid].couponList;
				if (app.inArray(couponrid,oldcouponrids)) {
					var coupons = [];
					var couponrids = [];
					for(var i in oldcoupons){
						if(oldcoupons[i].id != couponrid){
							coupons.push(oldcoupons[i]);
							couponrids.push(oldcoupons[i].id);
						}
					}
				} else {
					coupons = oldcoupons;
					couponrids = oldcouponrids;
					console.log(allbuydata[bid].coupon_peruselimit + '---' + oldcouponrids.length);
					if(allbuydata[bid].coupon_peruselimit > oldcouponrids.length){
						console.log('xxxx');
						coupons.push(couponList[couponkey]);
						couponrids.push(couponList[couponkey].id);
					}else{
						if(allbuydata[bid].coupon_peruselimit > 1){
							app.error('最多只能选用'+allbuydata[bid].coupon_peruselimit+'张');
							return;
						}else{
							coupons = [couponList[couponkey]];
							couponrids = [couponrid];
						}
					}
				}
				allbuydata[bid].coupons = coupons;
				allbuydata[bid].couponrids = couponrids;
				var coupon_money = 0;
				var coupontype = 1;
				for(var i in coupons){
					if(coupons[i]['type'] == 4){
						coupontype = 4;
					}else if(coupons[i]['type'] == 10){
						coupon_money += coupons[i]['thistotalprice'] * (100-coupons[i]['discount']) * 0.01;
					}else{
						coupon_money += coupons[i]['money']
					}
				}
				allbuydata[bid].coupontype = coupontype;
				allbuydata[bid].coupon_money = coupon_money;
				this.allbuydata = allbuydata;
				this.couponvisible = false;
				this.calculatePrice();
			},
			//提交并支付
			topay: function(e) {
				var that = this;
				var needaddress = that.needaddress;
                if ( !this.address || !this.address.id) {
                	app.error('请选择地址');
                	return;
                }
				var addressid = this.address.id;
				var linkman = this.linkman;
				var tel = this.tel;
				var frompage = that.opt.frompage ? that.opt.frompage : '';
				var allbuydata = that.allbuydata;
				
				if ( addressid == undefined || !addressid) {
					app.error('请选择地址');
					return;
				}
				var buydata = [];
				for (var i in allbuydata) {

					var couponrid = (allbuydata[i].couponrids).join(',');
					var buydatatemp = {
						bid: 0,
						prodata: allbuydata[i].prodatastr,
						couponrid: couponrid,
					};
					buydata.push(buydatatemp);
				}
				app.showLoading('提交中');
				app.post('ApiXixie/createOrder', {
					frompage: frompage,
					buydata: buydata,
					addressid: addressid,
					linkman: linkman,
					tel: tel,
                    yydate:that.yydate,
                    buy_type:that.buy_type,
                    gotype:that.gotype
				}, function(res) {
					app.showLoading(false);
					if (res.status == 0) {
						app.error(res.msg);
						return;
					}
					if(res.payorderid)
					app.goto('/pages/pay/pay?id=' + res.payorderid);
				});
			},
			showCouponList: function(e) {
				this.couponvisible = true;
				this.bid = e.currentTarget.dataset.bid;
			},
			handleClickMask: function() {
				this.couponvisible = false;
			},
            callMobile:function(e){
                var that = this;
                var mobile     = e.currentTarget.dataset.mobile;
                if(mobile){
                    uni.makePhoneCall({
                        phoneNumber: mobile
                    })
                }else{
                    app.alert('暂无可拨打电话');
                }
            },
            changeBuytype:function(e){
                var that   = this;
                var type = e.currentTarget.dataset.type;
                that.buy_type = type;
                this.calculatePrice();
            },
            //选择时间
            chooseTime: function(e) {
            	var that = this;
                if(!that.mendian){
                    app.alert('附近暂无门店');
                    return;
                }
                var mdid = that.mendian.id;
            	that.timeDialogShow = true;
            	that.timeIndex = -1;
            	var curTopIndex = that.datelist[0];
            	that.nowdate = that.datelist[that.curTopIndex].year+that.datelist[that.curTopIndex].date;
            	that.loading = true;
            	app.get('ApiXixie/isgetTime', {date:that.nowdate,mdid:mdid}, function (res) {
            	  that.loading = false;
            	  that.timelist = res.data;
            	})
            },
            hidetimeDialog:function(){
                this.timeDialogShow = false;
            },
            switchTopTab: function (e) {
              var that = this;
              if(!that.mendian){
                  app.alert('附近暂无门店');
                  return;
              }
              var mdid = that.mendian.id;

              var index = parseInt(e.currentTarget.dataset.index);
              this.curTopIndex = index;
              that.days = that.datelist[index].year+that.datelist[index].date
              that.nowdate = that.datelist[index].nowdate
               // if(!that.dates ){ that.dates = that.daydate[0] }
              this.curIndex = -1;
              this.curIndex2 = -1;
              //检测预约时间是否可预约
              that.loading = true;
            
              app.get('ApiXixie/isgetTime', { date: that.days,mdid:mdid}, function (res) {
            	  that.loading = false;
            	  that.timelist = res.data;
              })
            	
            },
            switchDateTab: function (e) {
              var that = this;
              var index2 = parseInt(e.currentTarget.dataset.index2);
              var timeint = e.currentTarget.dataset.time
              var status  = e.currentTarget.dataset.status
              if(status==0){
            		app.error('此时间不可选择');return;	
              }
              that.timeint = timeint
              that.timeindex = index2
            	//console.log(that.timelist);
              that.starttime1 = that.timelist[index2].time
              if(!that.days || that.days=='请选择服务时间'){ that.days = that.datelist[0].year + that.datelist[0].date }
              that.selectDates = that.starttime1;
            },
            selectDate:function(e){
            	var that=this
            	if(that.timeindex >= 0 && that.timelist[that.timeindex].status==0){
            			that.starttime1='';
            	}
            	if(!that.starttime1){
            		app.error('请选择预约时间');return;
            	}
            	 var yydate = that.days+' '+that.selectDates
            	 that.yydate = yydate
            	 this.timeDialogShow = false;
            }
            
		}
	}
</script>

<style>
.redBg{color:#fff;padding:4rpx 16rpx;font-weight:normal;border-radius:8rpx;font-size:24rpx; width: auto; display: inline-block; margin-top: 4rpx;}
.address-add {width: 94%;margin: 20rpx 3%;background: #fff;border-radius: 20rpx;padding: 20rpx 3%;min-height: 140rpx;}
.address-add .f1 {margin-right: 20rpx}
.address-add .f1 .img {width: 66rpx;height: 66rpx;}
.address-add .f2 {color: #666;}
.address-add .f3 {width: 26rpx;height: 26rpx;}
.linkitem {width: 100%;padding: 1px 0;background: #fff;display: flex;align-items: center}.cf3 {width: 200rpx;height: 26rpx;display: block;
    text-align: right;}
.linkitem .f1 {width: 160rpx;color: #111111}
.linkitem .input {height: 50rpx;padding-left: 10rpx;color: #222222;font-weight: bold;font-size: 28rpx;flex: 1}
.buydata {width: 94%;margin: 0 3%;background: #fff;margin-bottom: 20rpx;border-radius: 20rpx;}
.btitle {width: 100%;padding: 20rpx 20rpx;display: flex;align-items: center;color: #111111;font-weight: bold;font-size: 30rpx}
.btitle .img {width: 34rpx;height: 34rpx;margin-right: 10rpx}
.bcontent {width: 100%;padding: 0 20rpx}
.product {width: 100%;border-bottom: 1px solid #f4f4f4}
.product .item {width: 100%;padding: 20rpx 0;background: #fff;border-bottom: 1px #ededed dashed;}
.product .item:last-child {border: none}
.product .info {padding-left: 20rpx;}
.product .info .f1 {color: #222222;font-weight: bold;font-size: 26rpx;line-height: 36rpx;margin-bottom: 10rpx;display: -webkit-box;-webkit-box-orient: vertical;-webkit-line-clamp: 2;overflow: hidden;}
.product .info .f2 {color: #999999;font-size: 24rpx}
.product .info .f3 {color: #FF4C4C;font-size: 28rpx;display: flex;align-items: center;margin-top: 10rpx}
.product image {width: 140rpx;height: 140rpx}
.freight {width: 100%;padding: 20rpx 0;background: #fff;display: flex;flex-direction: column;}
.freight .f1 {color: #333;margin-bottom: 10rpx}
.freight .f2 {color: #111111;text-align: right;flex: 1}
.freight .f3 {width: 24rpx;height: 28rpx;}
.freighttips {color: red;font-size: 24rpx;}
.freight-ul {width: 100%;display: flex;}
.freight-li {flex-shrink: 0;display: flex;background: #F5F6F8;border-radius: 24rpx;color: #6C737F;font-size: 24rpx;text-align: center;height: 48rpx;line-height: 48rpx;padding: 0 28rpx;margin: 12rpx 10rpx 12rpx 0}


.price {width: 100%;padding: 20rpx 0;background: #fff;display: flex;align-items: center}
.price .f1 {color: #333}
.price .f2 {color: #111;font-weight: bold;text-align: right;flex: 1}
.price .f3 {width: 24rpx;height: 24rpx;}
.price .couponname{color:#fff;padding:4rpx 16rpx;font-weight:normal;border-radius:8rpx;font-size:24rpx;display:inline-block;margin:2rpx 0 2rpx 10rpx}

.remark {width: 100%;padding: 16rpx 0;background: #fff;display: flex;align-items: center}
.remark .f1 {color: #333;width: 200rpx}
.remark input {border: 0px solid #eee;height: 70rpx;padding-left: 10rpx;text-align: right}
.footer {width: 96%;background: #fff;margin-top: 5px;position: fixed;left: 0px;bottom: 0px;padding: 0 2%;display: flex;align-items: center;z-index: 8;box-sizing:content-box}
.footer .text1 {height: 110rpx;line-height: 110rpx;color: #2a2a2a;font-size: 30rpx;}
.footer .text1 text {color: #e94745;font-size: 32rpx;}
.footer .op {width: 200rpx;height: 80rpx;line-height: 80rpx;color: #fff;text-align: center;font-size: 30rpx;border-radius: 44rpx}
.footer .op[disabled] { background: #aaa !important; color: #666;}

.pstime-item {display: flex;border-bottom: 1px solid #f5f5f5;padding: 20rpx 30rpx;}
.pstime-item .radio {flex-shrink: 0;width: 32rpx;height: 32rpx;background: #FFFFFF;border: 2rpx solid #BFBFBF;border-radius: 50%;margin-right: 30rpx}
.pstime-item .radio .radio-img {width: 100%;height: 100%}
.cuxiao-desc {width: 100%}


.member_search{width:100%;padding:0 40rpx;display:flex;align-items:center}
.searchMemberButton{height:60rpx;background-color: #007AFF;border-radius: 10rpx;width: 160rpx;line-height: 60rpx;color: #fff;text-align: center;font-size: 28rpx;display: block;}
.memberlist{width:100%;padding:0 40rpx;height: auto;margin:20rpx auto;}
.memberitem{display:flex;align-items:center;border-bottom:1px solid #f5f5f5;padding:20rpx 0}
.memberitem image{display: block;height:100rpx;width:100rpx;margin-right:20rpx;}
.memberitem .t1{color:#333;font-weight:bold}
.memberitem .radio {flex-shrink: 0;width: 32rpx;height: 32rpx;background: #FFFFFF;border: 2rpx solid #BFBFBF;border-radius: 50%;margin-right: 30rpx}
.memberitem .radio .radio-img {width: 100%;height: 100%}


.placeholder{  font-size: 26rpx;line-height: 80rpx;}
.selected-item span{ font-size: 26rpx !important;}
.orderinfo{width:94%;margin:0 3%;border-radius:8rpx;margin-top:16rpx;padding: 14rpx 3%;background: #FFF;}
.orderinfo .item{display:flex;width:100%;padding:20rpx 0;border-bottom:1px dashed #ededed;overflow:hidden}
.orderinfo .item:last-child{ border-bottom: 0;}
.orderinfo .item .t1{width:200rpx;flex-shrink:0}
.orderinfo .item .t2{flex:1;text-align:right}
.orderinfo .item .red{color:red}

.storeviewmore{width:100%;text-align:center;color:#889;height:40rpx;line-height:40rpx;margin-top:10rpx}

.btn{ height:80rpx;line-height: 80rpx;width:90%;margin:0 auto;border-radius:40rpx;margin-top:40rpx;color: #fff;font-size: 28rpx;font-weight:bold}
.invoiceBox .radio radio{transform: scale(0.8);}
.invoiceBox .radio:nth-child(2) { margin-left: 30rpx;}

.buytype{width: 50%;float: left;font-weight: bold;}


/*时间范围*/
.datetab{ display: flex; border:1px solid red; width: 200rpx; text-align: center;}
.order-tab{ }
.order-tab2{display:flex;width:auto;min-width:100%}
.order-tab2 .item{width:auto;font-size:28rpx;font-weight:bold;text-align: center; color:#999999;overflow: hidden;flex-shrink:0;flex-grow: 1; display: flex; flex-direction: column; justify-content: center; align-items: center;}
.order-tab2 .item .datetext{ line-height: 60rpx; height:60rpx;}
.order-tab2 .item .datetext2{ line-height: 60rpx; height:60rpx;font-size: 22rpx;}
.order-tab2 .on{color:#222222;}
.order-tab2 .after{display:none;margin-left:-10rpx;bottom:5rpx;height:6rpx;border-radius:1.5px;width:70rpx}
.order-tab2 .on .after{display:block}
.daydate{ padding:20rpx; flex-wrap: wrap; overflow-y: scroll; height:400rpx; }
.daydate .date{ width:20%;text-align: center;line-height: 60rpx;height: 60rpx; margin-top: 30rpx;}
.daydate .on{ background:red; color:#fff;}
.daydate .hui{ border:1px solid #f0f0f0; background:#f0f0f0;border-radius: 5rpx;}
.tobuy{flex:1;height: 72rpx; line-height: 72rpx; color: #fff; border-radius: 0px; border: none;font-size:28rpx;font-weight:bold;width:90%;margin:20rpx 5%;border-radius:36rpx;}

</style>
