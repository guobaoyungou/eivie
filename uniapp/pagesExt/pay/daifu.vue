<template>
	<view class="container">
		<block v-if="isload">
			<view class="topbg" :style="{background:t('color1')}">
				<view class="headimg">
					<image :src="order_member.headimg">
				</view>
				<view><text class="nickname">{{order_member.nickname}} </text> 发起了代付请求~</view>
			</view>
			<view class="box box-price">
				<view class="top">
					<view class="f1">需付款</view>
					<view class="f2" v-if="payorder.score==0"><text class="t1">￥</text><text
							class="t2">{{payorder.money}}</text></view>
					<view class="f2" v-else-if="payorder.money>0 && payorder.score>0"><text class="t1">￥</text><text
							class="t2">{{payorder.money}}</text><text style="font-size:28rpx"> +
							{{payorder.score}}{{t('积分')}}</text></view>
					<view class="f2" v-else><text class="t3">{{payorder.score}}{{t('积分')}}</text></view>
				</view>
				
				<!-- 支付方式 -->
				<view class="paytype">
					<block v-if="payorder.money==0 && payorder.score>0">
						<view class="f2">
							<view class="item" v-if="moneypay==1" @tap.stop="changeradio" data-typeid="1">
								<view class="t1 flex">
									<image class="img" :src="pre_url+'/static/img/pay-money.png'" />
									<view class="flex-col"><text>{{t('积分')}}支付</text><view
											style="font-size:22rpx;font-weight:normal">剩余{{t('积分')}}<text
												style="color:#FC5729">{{userinfo.score}}</text></view></view>
								</view>
								<view class="radio" :style="typeid=='1' ? 'background:'+t('color1')+';border:0' : ''">
									<image class="radio-img" :src="pre_url+'/static/img/checkd.png'" />
								</view>
							</view>
						</view>
					</block>
					<block v-else>
						<view class="f2">
							<view class="item"
								v-if="wxpay==1 && (wxpay_type==0 || wxpay_type==1 || wxpay_type==2 || wxpay_type==3)"
								@tap.stop="changeradio" data-typeid="2">
								<view class="t1">
									<image class="img" :src="pre_url+'/static/img/withdraw-weixin.png'" />微信支付
								</view>
								<view class="radio" :style="typeid=='2' ? 'background:'+t('color1')+';border:0' : ''">
									<image class="radio-img" :src="pre_url+'/static/img/checkd.png'" />
								</view>
							</view>
							<view class="item" v-if="wxpay==1 && wxpay_type==22" @tap.stop="changeradio" data-typeid="22">
								<view class="t1">
									<image class="img" :src="pre_url+'/static/img/withdraw-weixin.png'" />微信支付
								</view>
								<view class="radio" :style="typeid=='22' ? 'background:'+t('color1')+';border:0' : ''">
									<image class="radio-img" :src="pre_url+'/static/img/checkd.png'" />
								</view>
							</view>
					
							<view class="item" v-if="alipay==2" @tap.stop="changeradio" data-typeid="23">
								<view class="t1">
									<image class="img" :src="pre_url+'/static/img/withdraw-alipay.png'" />支付宝支付
								</view>
								<view class="radio" :style="typeid=='23' ? 'background:'+t('color1')+';border:0' : ''">
									<image class="radio-img" :src="pre_url+'/static/img/checkd.png'" />
								</view>
							</view>
					
							<!-- <view class="item" v-if="alipay==2" @tap.stop="changeradio" data-typeid="24">
							<view class="t1"><image class="img" :src="pre_url+'/static/img/pay-money.png'"/>银联支付</view>
							<view class="radio" :style="typeid=='24' ? 'background:'+t('color1')+';border:0' : ''"><image class="radio-img" :src="pre_url+'/static/img/checkd.png'"/></view>
						</view> -->
					
							<view class="item" v-if="alipay==1" @tap.stop="changeradio" data-typeid="3">
								<view class="t1">
									<image class="img" :src="pre_url+'/static/img/withdraw-alipay.png'" />支付宝支付
								</view>
								<view class="radio" :style="typeid=='3' ? 'background:'+t('color1')+';border:0' : ''">
									<image class="radio-img" :src="pre_url+'/static/img/checkd.png'" />
								</view>
							</view>
					
							<block v-if="more_alipay==1">
								<view class="item" v-if="alipay2==1" @tap.stop="changeradio" data-typeid="31">
									<view class="t1">
										<image class="img" :src="pre_url+'/static/img/withdraw-alipay.png'" />支付宝支付2
									</view>
									<view class="radio" :style="typeid=='31' ? 'background:'+t('color1')+';border:0' : ''">
										<image class="radio-img" :src="pre_url+'/static/img/checkd.png'" />
									</view>
								</view>
								<view class="item" v-if="alipay3==1" @tap.stop="changeradio" data-typeid="32">
									<view class="t1">
										<image class="img" :src="pre_url+'/static/img/withdraw-alipay.png'" />支付宝支付3
									</view>
									<view class="radio" :style="typeid=='32' ? 'background:'+t('color1')+';border:0' : ''">
										<image class="radio-img" :src="pre_url+'/static/img/checkd.png'" />
									</view>
								</view>
							</block>
					
							<view class="item" v-if="baidupay==1" @tap.stop="changeradio" data-typeid="11">
								<view class="t1">
									<image class="img" :src="pre_url+'/static/img/pay-money.png'" />在线支付
								</view>
								<view class="radio" :style="typeid=='11' ? 'background:'+t('color1')+';border:0' : ''">
									<image class="radio-img" :src="pre_url+'/static/img/checkd.png'" />
								</view>
							</view>
							<view class="item" v-if="toutiaopay==1" @tap.stop="changeradio" data-typeid="12">
								<view class="t1">
									<image class="img" :src="pre_url+'/static/img/pay-money.png'" />在线支付
								</view>
								<view class="radio" :style="typeid=='12' ? 'background:'+t('color1')+';border:0' : ''">
									<image class="radio-img" :src="pre_url+'/static/img/checkd.png'" />
								</view>
							</view>
							<view class="item" v-if="moneypay==1" @tap.stop="changeradio" data-typeid="1">
								<view class="t1 flex">
									<image class="img" :src="pre_url+'/static/img/pay-money.png'" />
									<view class="flex-col"><text>{{t('余额')}}支付</text><view
											style="font-size:22rpx;font-weight:normal">可用余额<text
												style="color:#FC5729">￥{{userinfo.money}}</text></view></view>
								</view>
								<view class="radio" :style="typeid=='1' ? 'background:'+t('color1')+';border:0' : ''">
									<image class="radio-img" :src="pre_url+'/static/img/checkd.png'" />
								</view>
							</view>
						</view>
					</block>
				</view>
				<!-- 支付方式 -->
				<!-- 立即支付 -->
				<view>
					<button class="btn" @tap="topay" :style="{background:t('color1')}" v-if="typeid != '0'">帮好友支付</button>
					<uni-popup id="dialogInput" ref="dialogInput" type="dialog">
						<uni-popup-dialog mode="password" title="支付密码" value="" placeholder="请输入支付密码" @confirm="getpwd">
						</uni-popup-dialog>
					</uni-popup>
				</view>
				<!-- 立即支付 -->
			</view>
			<view class="box goods" v-if="order_goods.length>0">
				<view class="bitem" v-for="(bitem,bindex) in order_goods" :key="bindex">
					<view class="box-title">{{bitem.bname}}</view>
					<view class="goods-item" v-for="(item,index) in bitem.goodslist" :key="index">
						<view class="left">
							<image class="proimg" :src="item.pic"></image>
						</view>
						<view class="right">
								<view>
									<view class="title">{{item.name}}</view>
									<view v-if="item.ggname" class="hui">{{item.ggname}}</view>
									<view class="hui">×{{item.num}}</view>
								</view>
								<view>
									<view class="title">￥{{item.real_totalprice?item.real_totalprice:item.totalprice}}</view>
									<view class="hui line" v-if="item.real_totalprice && item.real_totalprice!=item.totalprice">￥{{item.totalprice}}</view>
								</view>
						</view>
					</view>
				</view>
			</view>
			<view class="daifu_desc" v-if="daifu_desc">
				<view class="title">说明</view>
				<textarea :value="daifu_desc"></textarea>
			</view>
			<view style="height: 40rpx;"></view>
			<block v-if="give_coupon_show">
				<view class="give-coupon flex-x-center flex-y-center">
					<view class='coupon-block'>
						<image :src="pre_url+'/static/img/coupon-top.png'" style="width:630rpx;height:330rpx;"></image>
						<view @tap="give_coupon_close" :data-url="give_coupon_close_url"
							class="coupon-del flex-x-center flex-y-center">
							<image :src="pre_url+'/static/img/coupon-del.png'"></image>
						</view>
						<view class="flex-x-center">
							<view class="coupon-info">
								<view class="flex-x-center coupon-get">获得{{give_coupon_num}}张{{t('优惠券')}}</view>
								<view style="background:#f5f5f5;padding:10rpx 0">
									<block v-for="(item,index) in give_coupon_list" :key="item.id">
										<block v-if="index < 3">
											<view class="coupon-coupon">
												<view :class="item.type==1?'pt_img1':'pt_img2'"></view>
												<view class="pt_left" :class="item.type==1?'':'bg2'">
													<view class="f1" v-if="item.type==1"><text class="t0">￥</text><text
															class="t1">{{item.money}}</text></view>
													<view class="f1" v-if="item.type==2">礼品券</view>
													<view class="f1" v-if="item.type==3"><text
															class="t1">{{item.limit_count}}</text><text
															class="t2">次</text></view>
													<view class="f1" v-if="item.type==4">抵运费</view>
													<view class="f2" v-if="item.type==1 || item.type==4">
														<text v-if="item.minprice>0">满{{item.minprice}}元可用</text>
														<text v-else>无门槛</text>
													</view>
												</view>
												<view class="pt_right">
													<view class="f1">
														<view class="t1">{{item.name}}</view>
														<view class="t2" v-if="item.type==1">代金券</view>
														<view class="t2" v-if="item.type==2">礼品券</view>
														<view class="t2" v-if="item.type==3">计次券</view>
														<view class="t2" v-if="item.type==4">运费抵扣券</view>
														<!-- <view class="t4" v-if="item.bid>0">适用商家：{{item.bname}}</view> -->
														<!-- <view class="t3">有效期至 {{item.yxqdate}}</view> -->
													</view>
												</view>
												<view class="coupon_num" v-if="item.givenum > 1">×{{item.givenum}}
												</view>
											</view>
										</block>
									</block>
								</view>
								<view @tap="goto" data-url="/pagesExt/coupon/mycoupon" class="flex-x-center coupon-btn">
									前往查看</view>
							</view>
						</view>
					</view>
				</view>
			</block>
			<uni-popup id="dialogOpenWeapp" ref="dialogOpenWeapp" type="dialog" :maskClick="false">
				<view style="background:#fff;padding:50rpx;position:relative;border-radius:20rpx">
					<view
						style="height:80px;line-height:80px;width:200px;margin:0 auto;font-size: 18px;text-align:center;font-weight:bold;text-align:center;color:#333">
						恭喜您支付成功</view>
					<!-- #ifdef H5 -->
					<wx-open-launch-weapp :username="payorder.payafter_username" :path="payorder.payafter_path">
						<script type="text/wxtag-template">
							<div style="background:#FD4A46;height:50px;line-height: 50px;width:200px;margin:0 auto;border-radius:5px;margin-top:15px;color: #fff;font-size: 15px;font-weight:bold;text-align:center">{{payorder.payafterbtntext}}</div>
					</script>
					</wx-open-launch-weapp>
					<!-- #endif -->
					<view
						style="height:50px;line-height: 50px;width:200px;margin:0 auto;border-radius:5px;color:#66f;font-size: 14px;text-align:center"
						@tap="goto" :data-url="detailurl">查看订单详情</view>
				</view>
			</uni-popup>


			<uni-popup id="dialogPayconfirm" ref="dialogPayconfirm" type="dialog" :maskClick="false">
				<uni-popup-dialog type="info" title="支付确认" content="是否已完成支付" @confirm="PayconfirmFun">
				</uni-popup-dialog>
			</uni-popup>
		</block>
		<view @tap="closeInvite" v-if="invite_status && invite_free"
			style="width:100%;height: 100%;background-color: #000;position: fixed;opacity: 0.5;z-index: 99;top:0">
		</view>
		<view v-if="invite_status && invite_free"
			style="width: 700rpx;margin: 0 auto;position: fixed;top:10%;left: 25rpx;z-index: 100;">
			<view @tap="gotoInvite"
				style="background-color: #fff;border-radius: 20rpx;overflow: hidden;width: 100%;min-height: 700rpx;">
				<image :src="invite_free.pic" mode="widthFix" style="width: 100%;height: auto;"></image>
			</view>
			<view @tap="closeInvite" v-if="invite_status && invite_free"
				style="width: 80rpx;height: 80rpx;line-height: 80rpx;text-align: center;font-size: 30rpx;background-color: #fff;margin: 0 auto;border-radius: 50%;margin-top: 20rpx;">
				X
			</view>
		</view>
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
				pre_url: app.globalData.pre_url,

				detailurl: '',
				tourl: '',
				typeid: '0',
				wxpay: 0,
				wxpay_type: 0,
				alipay: 0,
				baidupay: 0,
				toutiaopay: 0,
				moneypay: 0,
				cancod: 0,
				pay_month: 0,
				pay_transfer: 0,
				codtxt: '',
				pay_month_txt: '',
				give_coupon_list: [],
				give_coupon_num: 0,
				userinfo: [],
				paypwd: '',
				hiddenmodalput: true,
				payorder: {},
				tmplids: [],
				give_coupon_show: false,
				give_coupon_close_url: "",
				more_alipay: 0,
				alipay2: 0,
				alipay3: 0,
				open_pay: false, //打开支付选项
				pay_type: '', //支付类型（新增）

				order_member: [],
				order_goods: [],
				invite_free: '',
				invite_status: false,
				free_tmplids: '',
				sharepic: app.globalData.initdata.logo,
				daifu_desc:''
			};
		},
		onShareAppMessage: function() {
			sharedata.summary = '您有一份好友代付待查收，请尽快处理！';
			return this._sharewx({
				title: '您的好友向您发出了代付请求',
				pic: this.sharepic
			});
		},
		onShareTimeline: function() {
			var sharewxdata = this._sharewx({
				title: '您有一份好友代付待查收，请尽快处理！',
				pic: this.sharepic
			});
			var query = (sharewxdata.path).split('?')[1]+'&seetype=circle';
			console.log(sharewxdata)
			console.log(query)
			return {
				title: sharewxdata.title,
				imageUrl: sharewxdata.imageUrl,
				query: query
			}
		},
		onShow:function() {
			if(app.globalData.platform=='wx' && app.globalData.hide_home_button==1){
			  uni.hideHomeButton();
			}
		},
		onLoad: function(opt) {
			this.opt = app.getopts(opt);
			if (this.opt.tourl) this.tourl = decodeURIComponent(this.opt.tourl);
			this.getdata();
		},
		onPullDownRefresh: function() {
			this.getdata();
		},
		methods: {
			getdata: function() {
				var that = this;
				that.loading = true;
				var thisurl = '';
				if (app.globalData.platform == 'mp' || app.globalData.platform == 'h5') {
					thisurl = location.href;
				}
				app.post('ApiPay/daifu', {
					orderid: that.opt.id,
					thisurl: thisurl,
					tourl: that.tourl,
					scene: app.globalData.scene
				}, function(res) {

					that.loading = false;
					if (res.status == 0) {
						app.error(res.msg);
						return;
					}

					that.wxpay = res.wxpay;
					that.wxpay_type = res.wxpay_type;
					that.alipay = res.alipay;
					that.baidupay = res.baidupay;
					that.toutiaopay = res.toutiaopay;
					that.cancod = res.cancod;
					that.codtxt = res.codtxt;
					that.pay_money = res.pay_money;
					that.pay_money_txt = res.pay_money_txt;
					that.moneypay = res.moneypay;
					that.pay_transfer = res.pay_transfer;
					that.pay_transfer_info = res.pay_transfer_info;
					that.pay_month = res.pay_month;
					that.pay_month_txt = res.pay_month_txt;
					that.payorder = res.payorder;
					that.userinfo = res.userinfo;
					that.order_member = res.order_member;
					that.order_goods = res.order_goods;
					that.tmplids = res.tmplids;
					that.give_coupon_list = res.give_coupon_list;
					that.daifu_desc = res.daifu_desc
					if (that.give_coupon_list) {
						for (var i in that.give_coupon_list) {
							that.give_coupon_num += that.give_coupon_list[i]['givenum'];
						}
					}
					that.detailurl = res.detailurl;
					that.tourl = res.tourl;

					that.more_alipay = res.more_alipay;
					that.alipay2 = res.alipay2;
					that.alipay3 = res.alipay3;
					if (that.wxpay) {
						if (that.wxpay_type == 22) {
							that.typeid = 22;
						} else {
							that.typeid = 2;
						}
					} else if (that.moneypay) {
						that.typeid = 1;
					} else if (that.alipay) {
						that.typeid = 3;
						if (that.alipay == 2) {
							that.typeid = 23;
						}
					} else if (that.more_alipay) {
						if (that.alipay2) {
							that.typeid = 31;
						}
						if (that.alipay3) {
							that.typeid = 32;
						}
					} else if (that.baidupay) {
						that.typeid = 11;
					} else if (that.toutiaopay) {
						that.typeid = 12;
					}
					if (that.payorder.money == 0 && that.payorder.score > 0) {
						that.typeid = 1;
					}
					if (res.invite_free) {
						that.invite_free = res.invite_free;
					}
					if (res.free_tmplids) {
						that.free_tmplids = res.free_tmplids;
					}
					uni.setNavigationBarTitle({
						title: res.daifu_txt
					});
					that.loaded();
				});
			},
			getpwd: function(done, val) {
				this.paypwd = val;
				this.topay({
					currentTarget: {
						dataset: {
							typeid: 1
						}
					}
				});
			},
			changeradio: function(e) {
				var that = this;
				var typeid = e.currentTarget.dataset.typeid;
				that.typeid = typeid;
				console.log(typeid)
			},
			topay: function(e) {
				var that = this;
				var typeid = that.typeid;
				var orderid = this.payorder.id;
				if (typeid == 1) { //余额支付
					if (that.userinfo.haspwd && that.paypwd == '') {
						that.$refs.dialogInput.open();
						return;
					}
					app.confirm('确定用' + that.t('余额') + '支付吗?', function() {
						app.showLoading('提交中');
						app.post('ApiPay/daifu', {
							op: 'submit',
							orderid: orderid,
							typeid: typeid,
							paypwd: that.paypwd,
							pay_type: that.pay_type
						}, function(res) {
							app.showLoading(false);
							if (res.status == 0) {
								app.error(res.msg);
								return;
							}
							if (res.status == 2) {
								app.success(res.msg);
								that.subscribeMessage(function() {
									if (that.invite_free) {
										that.invite_status = true;
									} else {
										setTimeout(function() {
											if (that.give_coupon_list && that
												.give_coupon_list.length > 0) {
												that.give_coupon_show = true;
												that.give_coupon_close_url = that
												.tourl;
											} else {
												that.gotourl(that.tourl, 'reLaunch');
											}
										}, 1000);
									}
								});
								return;
							}
						});
					});
				} else if (typeid == 2) { //微信支付
					console.log(app)
					app.showLoading('提交中');
					app.post('ApiPay/daifu', {
						op: 'submit',
						orderid: orderid,
						typeid: typeid
					}, function(res) {
						app.showLoading(false);
						if (res.status == 0) {
							app.error(res.msg);
							return;
						}
						if (res.status == 2) {
							//无需付款
							app.success(res.msg);
							that.subscribeMessage(function() {
								if (that.invite_free) {
									that.invite_status = true;
								} else {
									setTimeout(function() {
										if (that.give_coupon_list && that.give_coupon_list
											.length > 0) {
											that.give_coupon_show = true;
											that.give_coupon_close_url = that.tourl;
										} else {
											that.gotourl(that.tourl, 'reLaunch');
										}
									}, 1000);
								}
							});
							return;
						}
						var opt = res.data;
						if (app.globalData.platform == 'wx') {
							if (that.payorder.type == 'shop' || that.wxpay_type == 2) {
								if (opt.orderInfo) {
									console.log('requestOrderPayment1');
									wx.requestOrderPayment({
										'timeStamp': opt.timeStamp,
										'nonceStr': opt.nonceStr,
										'package': opt.package,
										'signType': opt.signType ? opt.signType : 'MD5',
										'paySign': opt.paySign,
										'orderInfo': opt.orderInfo,
										'success': function(res2) {
											app.success('付款完成');
											that.subscribeMessage(function() {
												if (that.invite_free) {
													that.invite_status = true;
												} else {
													setTimeout(function() {
														if (that
															.give_coupon_list &&
															that.give_coupon_list
															.length > 0) {
															that.give_coupon_show =
																true;
															that.give_coupon_close_url =
																that.tourl;
														} else {
															that.gotourl(that
																.tourl,
																'reLaunch');
														}
													}, 1000);
												}
											});
										},
										'fail': function(res2) {
											//app.alert(JSON.stringify(res2))
										}
									});
								} else if(opt.sxpay && opt.path){
                  //随行付
                  uni.openEmbeddedMiniProgram({
                    appId: opt.appId,
                    path: opt.path,
                    extraData: {},
                    success(res) {
                      console.log('随行付半屏小程序打开');
                    }
                  })
                } else {
									console.log('requestOrderPayment2');
									wx.requestOrderPayment({
										'timeStamp': opt.timeStamp,
										'nonceStr': opt.nonceStr,
										'package': opt.package,
										'signType': opt.signType ? opt.signType : 'MD5',
										'paySign': opt.paySign,
										'success': function(res2) {
											app.success('付款完成');
											that.subscribeMessage(function() {
												if (that.invite_free) {
													that.invite_status = true;
												} else {
													setTimeout(function() {
														if (that
															.give_coupon_list &&
															that.give_coupon_list
															.length > 0) {
															that.give_coupon_show =
																true;
															that.give_coupon_close_url =
																that.tourl;
														} else {
															that.gotourl(that
																.tourl,
																'reLaunch');
														}
													}, 1000);
												}
											});
										},
										'fail': function(res2) {
											//app.alert(JSON.stringify(res2))
										}
									});
								}
							} else if(opt.sxpay && opt.path){
								//随行付
								uni.openEmbeddedMiniProgram({
									appId: opt.appId,
									path: opt.path,
									extraData: {},
									success(res) {
										console.log('随行付半屏小程序打开');
									}
								})
							} else {
								uni.requestPayment({
									'provider': 'wxpay',
									'timeStamp': opt.timeStamp,
									'nonceStr': opt.nonceStr,
									'package': opt.package,
									'signType': opt.signType ? opt.signType : 'MD5',
									'paySign': opt.paySign,
									'success': function(res2) {
										app.success('付款完成');
										that.subscribeMessage(function() {
											if (that.invite_free) {
												that.invite_status = true;
											} else {
												setTimeout(function() {
													if (that.give_coupon_list &&
														that.give_coupon_list
														.length > 0) {
														that.give_coupon_show =
															true;
														that.give_coupon_close_url =
															that.tourl;
													} else {
														that.gotourl(that.tourl,
															'reLaunch');
													}
												}, 1000);
											}
										});
									},
									'fail': function(res2) {
										//app.alert(JSON.stringify(res2))
									}
								});
							}
						} else if (app.globalData.platform == 'mp') {
							// #ifdef H5
							function jsApiCall() {
								WeixinJSBridge.invoke('getBrandWCPayRequest', opt, function(res) {
									if (res.err_msg == "get_brand_wcpay_request:ok") {
										app.success('付款完成');
										that.subscribeMessage(function() {
											if (that.invite_free) {
												that.invite_status = true;
											} else {
												setTimeout(function() {
													if (that.give_coupon_list && that
														.give_coupon_list.length > 0) {
														that.give_coupon_show = true;
														that.give_coupon_close_url =
															that.tourl;
													} else {
														that.gotourl(that.tourl,
															'reLaunch');
													}
												}, 1000);
											}
										});
									} else {

									}
								});
							}
							if (typeof WeixinJSBridge == "undefined") {
								if (document.addEventListener) {
									document.addEventListener('WeixinJSBridgeReady', jsApiCall, false);
								} else if (document.attachEvent) {
									document.attachEvent('WeixinJSBridgeReady', jsApiCall);
									document.attachEvent('onWeixinJSBridgeReady', jsApiCall);
								}
							} else {
								jsApiCall();
							}
							// #endif
							/*
							var jweixin = require('jweixin-module');
							jweixin.chooseWXPay({
								timestamp: opt.timeStamp, // 支付签名时间戳，注意微信jssdk中的所有使用timestamp字段均为小写。但最新版的支付后台生成签名使用的timeStamp字段名需大写其中的S字符
								nonceStr: opt.nonceStr, // 支付签名随机串，不长于 32 位
								package: opt.package, // 统一支付接口返回的prepay_id参数值，提交格式如：prepay_id=\*\*\*）
								signType: opt.signType, // 签名方式，默认为'SHA1'，使用新版支付需传入'MD5'
								paySign: opt.paySign, // 支付签名
								success: function (res2) {
									// 支付成功后的回调函数
									app.success('付款完成');
									that.subscribeMessage(function () {
										setTimeout(function () {
											if (that.give_coupon_list && that.give_coupon_list.length > 0) {
												that.give_coupon_show = true;
												that.give_coupon_close_url = that.tourl;
											} else {
												that.gotourl(that.tourl,'reLaunch');
											}
										}, 1000);
									});
								}
							});
							*/
						} else if (app.globalData.platform == 'h5') {
							location.href = opt.wx_url + '&redirect_url=' + encodeURIComponent(location.href
								.split('#')[0] + '#' + that.tourl);
						} else if (app.globalData.platform == 'app') {
							console.log(opt)
							uni.requestPayment({
								'provider': 'wxpay',
								'orderInfo': opt,
								'success': function(res2) {
									app.success('付款完成');
									that.subscribeMessage(function() {
										if (that.invite_free) {
											that.invite_status = true;
										} else {
											setTimeout(function() {
												if (that.give_coupon_list && that
													.give_coupon_list.length > 0) {
													that.give_coupon_show = true;
													that.give_coupon_close_url =
														that.tourl;
												} else {
													that.gotourl(that.tourl,
														'reLaunch');
												}
											}, 1000);
										}
									});
								},
								'fail': function(res2) {
									console.log(res2)
									//app.alert(JSON.stringify(res2))
								}
							});
						} else if (app.globalData.platform == 'qq') {
							qq.requestWxPayment({
								url: opt.wx_url,
								referer: opt.referer,
								success(res) {
									that.subscribeMessage(function() {
										if (that.invite_free) {
											that.invite_status = true;
										} else {
											setTimeout(function() {
												if (that.give_coupon_list && that
													.give_coupon_list.length > 0) {
													that.give_coupon_show = true;
													that.give_coupon_close_url = that
														.tourl;
												} else {
													that.gotourl(that.tourl,
														'reLaunch');
												}
											}, 1000);
										}
									});
								},
								fail(res) {}
							})
						}
					})
				} else if (typeid == 3 || typeid == 31 || typeid == 32) { //支付宝支付
					app.showLoading('提交中');
					app.post('ApiPay/daifu', {
						op: 'submit',
						orderid: orderid,
						typeid: typeid
					}, function(res) {
						console.log(res)
						app.showLoading(false);
						if (res.status == 0) {
							app.error(res.msg);
							return;
						}
						if (res.status == 2) {
							//无需付款
							app.success(res.msg);
							that.subscribeMessage(function() {
								if (that.invite_free) {
									that.invite_status = true;
								} else {
									setTimeout(function() {
										if (that.give_coupon_list && that.give_coupon_list
											.length > 0) {
											that.give_coupon_show = true;
											that.give_coupon_close_url = that.tourl;
										} else {
											that.gotourl(that.tourl, 'reLaunch');
										}
									}, 1000);
								}
							});
							return;
						}
						var opt = res.data;
						if (app.globalData.platform == 'alipay') {
							uni.requestPayment({
								'provider': 'alipay',
								'orderInfo': opt.trade_no,
								'success': function(res2) {
									console.log(res2)
									if (res2.resultCode == '6001') {
										return;
									}
									app.success('付款完成');
									that.subscribeMessage(function() {
										if (that.invite_free) {
											that.invite_status = true;
										} else {
											setTimeout(function() {
												if (that.give_coupon_list && that
													.give_coupon_list.length > 0) {
													that.give_coupon_show = true;
													that.give_coupon_close_url =
														that.tourl;
												} else {
													that.gotourl(that.tourl,
														'reLaunch');
												}
											}, 1000);
										}
									});
								},
								'fail': function(res2) {
									//app.alert(JSON.stringify(res2))
								}
							});
						} else if (app.globalData.platform == 'mp' || app.globalData.platform == 'h5') {
							document.body.innerHTML = res.data;
							document.forms['alipaysubmit'].submit();
						} else if (app.globalData.platform == 'app') {
							console.log('------------alipay----------')
							console.log(opt)
							console.log('------------alipay end----------')
							uni.requestPayment({
								'provider': 'alipay',
								'orderInfo': opt,
								'success': function(res2) {
									console.log('------------success----------')
									console.log(res2)
									app.success('付款完成');
									that.subscribeMessage(function() {
										if (that.invite_free) {
											that.invite_status = true;
										} else {
											setTimeout(function() {
												if (that.give_coupon_list && that
													.give_coupon_list.length > 0) {
													that.give_coupon_show = true;
													that.give_coupon_close_url =
														that.tourl;
												} else {
													that.gotourl(that.tourl,
														'reLaunch');
												}
											}, 1000);
										}
									});
								},
								'fail': function(res2) {
									console.log(res2)
									//app.alert(JSON.stringify(res2))
								}
							});
						}
					})
				} else if (typeid == '11') {
					app.showLoading('提交中');
					app.post('ApiPay/daifu', {
						op: 'submit',
						orderid: orderid,
						typeid: typeid
					}, function(res) {
						app.showLoading(false);
						swan.requestPolymerPayment({
							'orderInfo': res.orderInfo,
							'success': function(res2) {
								app.success('付款完成');
								that.subscribeMessage(function() {
									if (that.invite_free) {
										that.invite_status = true;
									} else {
										setTimeout(function() {
											if (that.give_coupon_list && that
												.give_coupon_list.length > 0) {
												that.give_coupon_show = true;
												that.give_coupon_close_url = that
													.tourl;
											} else {
												that.gotourl(that.tourl,
													'reLaunch');
											}
										}, 1000);
									}
								});
							},
							'fail': function(res2) {
								if (res2.errCode != 2) {
									app.alert(JSON.stringify(res2))
								}
							}
						});
					});
				} else if (typeid == '12') {
					app.showLoading('提交中');
					app.post('ApiPay/daifu', {
						op: 'submit',
						orderid: orderid,
						typeid: typeid
					}, function(res) {
						app.showLoading(false);
						console.log(res.orderInfo);
						tt.pay({
							'service': 5,
							'orderInfo': res.orderInfo,
							'success': function(res2) {
								if (res2.code === 0) {
									app.success('付款完成');
									that.subscribeMessage(function() {
										if (that.invite_free) {
											that.invite_status = true;
										} else {
											setTimeout(function() {
												if (that.give_coupon_list && that
													.give_coupon_list.length > 0) {
													that.give_coupon_show = true;
													that.give_coupon_close_url =
														that.tourl;
												} else {
													that.gotourl(that.tourl,
														'reLaunch');
												}
											}, 1000);
										}
									});
								}
							},
							'fail': function(res2) {
								app.alert(JSON.stringify(res2))
							}
						});
					});
				} else if (typeid == '22') {
					if (app.globalData.platform == 'wx') {
						wx.login({
							success: function(res) {
								if (res.code) {
									app.showLoading('提交中');
									app.post('ApiPay/getYunMpauthParams', {
										jscode: res.code
									}, function(res) {
										app.showLoading(false);
										app.post('https://showmoney.cn/scanpay/fixed/mpauth', res
											.params,
											function(res2) {
												console.log(res2.sessionKey);
												app.post('ApiPay/getYunUnifiedParams', {
													orderid: orderid,
													sessionKey: res2.sessionKey
												}, function(res3) {
													app.post(
														'https://showmoney.cn/scanpay/unified',
														res3.params,
														function(res4) {
															if (res4.respcd ==
																'09') {
																wx.requestPayment({
																	timeStamp: res4
																		.timeStamp,
																	nonceStr: res4
																		.nonceStr,
																	package: res4
																		.package,
																	signType: res4
																		.mpSignType,
																	paySign: res4
																		.mpSign,
																	success: function success(
																		result
																		) {
																		app.success(
																			'付款完成'
																			);
																		that.subscribeMessage(
																			function() {
																				if (that
																					.invite_free
																					) {
																					that.invite_status =
																						true;
																				} else {
																					setTimeout
																						(function() {
																								if (that
																									.give_coupon_list &&
																									that
																									.give_coupon_list
																									.length >
																									0
																									) {
																									that.give_coupon_show =
																										true;
																									that.give_coupon_close_url =
																										that
																										.tourl;
																								} else {
																									that.gotourl(
																										that
																										.tourl,
																										'reLaunch'
																										);
																								}
																							},
																							1000
																							);
																				}
																			}
																			);
																	},
																	fail: function(
																		res5
																		) {
																		//app.alert(JSON.stringify(res5))
																	}
																});
															} else {
																app.alert(res4
																	.errorDetail
																	);
															}
														})
												})
											})
									})
								} else {
									console.log('登录失败！' + res.errMsg)
								}
							}
						});
					} else {
						var url = app.globalData.baseurl + 'ApiPay/daifu' + '&aid=' + app.globalData.aid +
							'&platform=' + app.globalData.platform + '&session_id=' + app.globalData.session_id;
						url += '&op=submit&orderid=' + orderid + '&typeid=22';
						location.href = url;
					}
				} else if (typeid == '23') {
					//var url = app.globalData.baseurl + 'ApiPay/daifu'+'&aid=' + app.globalData.aid + '&platform=' + app.globalData.platform + '&session_id=' + app.globalData.session_id;
					//url += '&op=submit&orderid='+orderid+'&typeid=23';
					//location.href = url;
					setTimeout(function() {
						that.$refs.dialogPayconfirm.open();
					}, 1000);

					app.goto('/pages/index/webView2?orderid=' + orderid + '&typeid=23' + '&aid=' + app.globalData.aid +
						'&platform=' + app.globalData.platform + '&session_id=' + app.globalData.session_id);
					return;
					app.showLoading('提交中');
					app.post('ApiPay/daifu', {
						op: 'submit',
						orderid: orderid,
						typeid: 23
					}, function(res) {
						app.showLoading(false);
						console.log(res)
						app.goto('url::' + res.url);
					});
				} else if (typeid == '24') {
					//var url = app.globalData.baseurl + 'ApiPay/daifu'+'&aid=' + app.globalData.aid + '&platform=' + app.globalData.platform + '&session_id=' + app.globalData.session_id;
					//url += '&op=submit&orderid='+orderid+'&typeid=23';
					//location.href = url;

					app.goto('/pages/index/webView2?orderid=' + orderid + '&typeid=24');
					return;
					app.showLoading('提交中');
					app.post('ApiPay/daifu', {
						op: 'submit',
						orderid: orderid,
						typeid: 24
					}, function(res) {
						app.showLoading(false);
						console.log(res)
						app.goto('url::' + res.url);
					});
				}
			},
			topay2: function() {
				var that = this;
				var orderid = this.payorder.id;
				app.confirm('确定要' + that.codtxt + '吗?', function() {
					app.showLoading('提交中');
					app.post('ApiPay/daifu', {
						op: 'submit',
						orderid: orderid,
						typeid: 4
					}, function(res) {
						app.showLoading(false);
						if (res.status == 0) {
							app.error(res.msg);
							return;
						}
						if (res.status == 2) {
							//无需付款
							app.success(res.msg);
							that.subscribeMessage(function() {
								setTimeout(function() {
									that.gotourl(that.tourl, 'reLaunch');
								}, 1000);
							});
							return;
						}
					});
				})
			},
			topayMonth: function() {
				var that = this;
				var orderid = this.payorder.id;
				app.confirm('确定要' + that.pay_month_txt + '支付吗?', function() {
					app.showLoading('提交中');
					app.post('ApiPay/daifu', {
						op: 'submit',
						orderid: orderid,
						typeid: 41
					}, function(res) {
						app.showLoading(false);
						if (res.status == 0) {
							app.error(res.msg);
							return;
						}
						if (res.status == 2) {
							//无需付款
							app.success(res.msg);
							that.subscribeMessage(function() {
								setTimeout(function() {
									that.gotourl(that.tourl, 'reLaunch');
								}, 1000);
							});
							return;
						}
					});
				})
			},
			topayTransfer: function(e) {
				var that = this;
				var orderid = this.payorder.id;
				app.confirm('确定要' + e.currentTarget.dataset.text + '吗?', function() {
					app.showLoading('提交中');
					app.post('ApiPay/daifu', {
						op: 'submit',
						orderid: orderid,
						typeid: 5
					}, function(res) {
						app.showLoading(false);
						if (res.status == 0) {
							app.error(res.msg);
							return;
						}
						if (res.status == 2) {
							//无需付款
							app.success(res.msg);
							that.subscribeMessage(function() {

							});
							setTimeout(function() {
								that.gotourl('transfer?id=' + orderid, 'reLaunch');
							}, 1000);
							return;
						}
					});
				})
			},
			give_coupon_close: function(e) {
				var that = this;
				var tourl = e.currentTarget.dataset.url;
				this.give_coupon_show = false;
				that.gotourl(tourl, 'reLaunch');
			},
			gotourl: function(tourl, opentype) {
				var that = this;
				if (app.globalData.platform == 'mp' || app.globalData.platform == 'h5') {
					if (tourl.indexOf('miniProgram::') === 0) {
						//其他小程序
						tourl = tourl.slice(13);
						var tourlArr = tourl.split('|');
						that.showOpenWeapp();
						return;
					}
				}
				app.goto(tourl, opentype);
			},
			showOpenWeapp: function() {
				this.$refs.dialogOpenWeapp.open();
			},
			closeOpenWeapp: function() {
				this.$refs.dialogOpenWeapp.close();
			},
			PayconfirmFun: function() {
				this.gotourl(this.tourl, 'reLaunch');
			},
			close_pay: function() {
				var that = this;
				that.open_pay = false;
			},
			closeInvite: function() {
				var that = this;
				that.invite_status = false;
				setTimeout(function() {
					if (that.give_coupon_list && that.give_coupon_list.length > 0) {
						that.give_coupon_show = true;
						that.give_coupon_close_url = that.tourl;
					} else {
						that.gotourl(that.tourl, 'reLaunch');
					}
				}, 1000);
			},
			gotoInvite: function() {
				var that = this;
				var free_tmplids = that.free_tmplids;
				if (free_tmplids && free_tmplids.length > 0) {
					uni.requestSubscribeMessage({
						tmplIds: free_tmplids,
						success: function(res) {
							console.log(res)
						},
						fail: function(res) {
							console.log(res)
						}
					})
				}
				app.goto('/pagesExt/invite_free/index', 'reLaunch')
			}
		}
	}
</script>
<style>
	.topbg {
		height: 360rpx;
		padding-top: 90rpx;
		text-align: center;
		color: #fff;
		display: flex;
		flex-direction: column;
	}
	
	.box-price{position: relative;top:-70rpx;padding-bottom: 60rpx;}

	.topbg .headimg image {
		height: 120rpx;
		width: 120rpx;
		border-radius: 50%;
	}

	.topbg .nickname {
		font-size: 36rpx;
		font-weight: bold;
		padding-right: 6rpx;
	}

	.box {
		width: 94%;
		margin: 0 3%;
		background: #FFFFFF;
		margin-top: 20rpx;
		border-radius: 20rpx;
	}

	.box-title {
		height: 70rpx;
		line-height: 70rpx;
		color: #333333;
		font-weight: bold;
		font-size: 30rpx;
		/* border-bottom: 1px solid #F0F3F6; */
	}

	.goods {
		padding: 20rpx;
		margin-top: -40rpx;
		border-radius: 10rpx;
	}

	.bitem{padding-bottom: 30rpx;}
	.goods-item {
		display: flex;
		justify-content: center;
		justify-content: space-between;
		padding: 8rpx 0;
	}


	.goods-item .proimg {
		width: 120rpx;
		height: 120rpx;
		border-radius: 10rpx;
	}

	.goods-item .right {
		align-self: flex-start;
		flex: 1;
		padding-left: 30rpx;
		display: flex;
		align-items: flex-start;
		justify-content: space-between;
		line-height: 40rpx;
	}

	.goods-item .right .f1 {
		display: flex;
		justify-content: space-between;
		align-items: flex-start;
	}
	.goods-item .right .title{
		font-size: 28rpx;
	}
	.goods-item .right .hui{
		font-size: 24rpx;
		color: #b0b0b0;
	}
	.goods-item .right .line{text-decoration: line-through;}

	.top {
		display: flex;
		flex-direction: column;
		align-items: center;
		padding-top: 20rpx
	}

	.top .f1 {
		height: 60rpx;
		line-height: 60rpx;
		font-size: 24rpx;
	}

	.top .f2 {
		color: #101010;
		font-weight: bold;
		font-size: 46rpx;
		height: 70rpx;
		line-height: 70rpx
	}

	/* .top .f2 .t1 {
		font-size: 44rpx
	} */

	.top .f2 .t3 {
		font-size: 50rpx
	}

	.top .f3 {
		color: #FC5729;
		font-size: 26rpx;
		height: 70rpx;
		line-height: 70rpx
	}

	.paytype {
		padding: 0 20rpx;
	}

	.paytype .f1 {
		height: 100rpx;
		line-height: 100rpx;
		padding: 0 30rpx;
		color: #333333;
		font-weight: bold
	}

	.paytype .f2 {
		padding: 0 30rpx
	}

	.paytype .f2 .item {
		border-bottom: 1px solid #f5f5f5;
		height: 100rpx;
		display: flex;
		align-items: center
	}

	.paytype .f2 .item:last-child {
		border-bottom: 0
	}

	.paytype .f2 .item .t1 {
		flex: 1;
		display: flex;
		align-items: center;
		color: #222222;
		font-size: 30rpx;
		font-weight: bold
	}

	.paytype .f2 .item .t1 .img {
		width: 44rpx;
		height: 44rpx;
		margin-right: 40rpx
	}

	.paytype .f2 .item .radio {
		flex-shrink: 0;
		width: 36rpx;
		height: 36rpx;
		background: #FFFFFF;
		border: 3rpx solid #BFBFBF;
		border-radius: 50%;
		margin-right: 10rpx
	}

	.paytype .f2 .item .radio .radio-img {
		width: 100%;
		height: 100%
	}

	.btn {
		height: 90rpx;
		line-height: 90rpx;
		width: 85%;
		margin: 0 auto;
		border-radius: 10rpx;
		margin-top: 30rpx;
		color: #fff;
		font-size: 30rpx;
		font-weight: bold
	}

	.daifu-btn {
		background: #fc5729;
	}

	.op {
		width: 94%;
		margin: 20rpx 3%;
		display: flex;
		align-items: center;
		margin-top: 40rpx
	}

	.op .btn {
		flex: 1;
		height: 100rpx;
		line-height: 100rpx;
		background: #07C160;
		width: 90%;
		margin: 0 10rpx;
		border-radius: 10rpx;
		color: #fff;
		font-size: 28rpx;
		font-weight: bold;
		display: flex;
		align-items: center;
		justify-content: center
	}

	.op .btn .img {
		width: 48rpx;
		height: 48rpx;
		margin-right: 20rpx
	}

	.daifu_desc{padding: 30rpx;}
	.daifu_desc .title{font-size: 30rpx;color: #5E5E5E;font-weight: bold;padding: 10rpx 0;}
	.daifu_desc textarea{width: 100%; line-height: 46rpx;font-size: 24rpx;color: #222222;}
</style>
