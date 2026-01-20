<template>
	<view>
		<block v-if="isload">
			<view class="container">
				<view class="header">
					<view class="flex-y-center">
						<image class="header_icon" :src="logo"></image>
						<view class="flex1">
							<view class="header_name">{{name}}</view>
							<view class="header_shop" v-if="mdlist.length>0">
								<text>选择门店:</text><text @tap="selectmd">{{mdlist[mdkey].name}}</text>
							</view>
							<view class="header_shop" v-if="activecoin_bili>0">
								<text>{{t('激活币')}}比例：</text><text>{{activecoin_bili}}%</text>
							</view>
							<view class="header_shop" v-if="newscore_ratio>0">
								<view class="ratio-list flex">
									<view class="ratio-label flex-y-center" :style="{color:t('color1'),borderColor:t('color1'), width: '220rpx'}">
										<view class="label" :style="{backgroundColor:t('color1'), width: '120rpx'}">{{t('新积分')}}</view>
										<view class="t1">{{newscore_ratio}}%</view>
									</view>
								</view>
							</view>
							<view class="header_shop" v-if="newscore_pack_ratio>0">
								<view class="ratio-list flex">
									<view class="ratio-label flex-y-center" :style="{color:t('color1'),borderColor:t('color1'), width: '220rpx'}">
										<view class="label" :style="{backgroundColor:t('color1'), width: '120rpx'}">{{t('加速包')}}</view>
										<view class="t1">{{newscore_pack_ratio}}%</view>
									</view>
								</view>
							</view>
						</view>
					</view>
				</view>
				<view class="page">
					<view class="page_module flex-y-center" @click="handleShowKey">
						<text class="page_tag">￥</text>
						<!-- <input class="page_price flex1" type="digit" @input="inputMoney" placeholder="请输入金额"></input> -->
						<view class="page_price flex-y-center">
							<text v-if="keyHidden&&!money" class="page_notice">请输入金额</text>
							<text v-if="money">{{ money }}</text>
							<view v-if="!keyHidden" class="page_cursor"></view>
						</view>
					</view>
					<view>
						<view class="info-box">
							<view class="dkdiv-item flex" v-if="have_login==1 && userinfo.discount>0 && userinfo.discount<10">
								<text class="f1">{{t('会员')}}折扣({{userinfo.discount*100/100}}折)</text>
								<text class="f2" style="color: #e94745;">-￥{{disprice}}</text>
							</view>
							<view class="dkdiv-item flex-y-center" v-if="itemList.length>0">
								<text class="f1">买单项目</text>
								<text class="f2" @tap="selectItemPopup" style="color:#e94745">{{itemIndex!=-1?itemList[itemIndex].name:'请选择项目'}}</text>
								<image class="f3" :src="pre_url+'/static/img/arrowright.png'"></image>
							</view>
							<view class="dkdiv-item flex-y-center" v-if="have_login==1">
								<text class="f1">{{t('优惠券')}}</text>
								<text class="f2" v-if="couponList.length>0" @tap="showCouponList" style="color:#e94745">{{couponrid!=0?couponList[couponkey].couponname:'请选择'+t('优惠券')}}</text>
								<text class="f2" v-else style="color:#999">无可用{{t('优惠券')}}</text>
								<image class="f3" :src="pre_url+'/static/img/arrowright.png'"></image>
							</view>
							<view class="dkdiv-item flex" v-if="have_login==1 && userinfo.scoredkmaxpercent > 0">
								<checkbox-group @change="scoredk" class="flex" style="width:100%">
									<view class="f1">
										<view>{{userinfo.score*1}} {{t('积分')}}可抵扣 <text
												style="color:#e94745">{{userinfo.dkmoney*1}}</text> 元</view>
										<view style="font-size:22rpx;color:#999"
											v-if="userinfo.scoredkmaxpercent > 0 && userinfo.scoredkmaxpercent<=100">
											最多可抵扣订单金额的{{userinfo.scoredkmaxpercent}}%</view>
									</view>
									<view class="f2">使用{{t('积分')}}抵扣
										<checkbox value="1" style="margin-left:6px;transform:scale(.8)"></checkbox>
									</view>
								</checkbox-group>
							</view>
              <view class="dkdiv-item flex" v-if="have_login==1 && moneydec && money_dec_rate>0 ">
                <checkbox-group @change="moneydk" :data-bid="bid" :data-rate="money_dec_rate" class="flex" style="width:100%">
                  <view class="f1">
                    <view>
                        {{t('余额')}}抵扣（余额：<text style="color:#e94745">{{userinfo.money?userinfo.money:0}}</text>元）
                    </view>
                    <view style="font-size:24rpx;color:#999" >
                      1、选择此项提交订单时将直接扣除{{t('余额')}}
                    </view>
                    <view style="font-size:24rpx;color:#999" >
                      2、最多可抵扣订单金额的{{money_dec_rate}}%
                    </view>
                  </view>
                  <view class="f2" style="font-weight:normal">
                    使用{{t('余额')}}抵扣
                    <checkbox value="1" style="margin-left:6px;transform:scale(.8)"></checkbox>
                  </view>
                </checkbox-group>
              </view>
			  <!--冻结资金抵扣 start-->
			  <view class="dkdiv-item flex" v-if="have_login==1 && freezemoneydec && freezemoney_dec_rate>0 ">
			    <checkbox-group @change="freezemoneydk" :data-bid="bid" :data-rate="freezemoney_dec_rate" class="flex" style="width:100%">
			      <view class="f1">
			        <view>
			            {{t('冻结资金')}}抵扣（余额：<text style="color:#e94745">{{userinfo.freezemoney?userinfo.freezemoney:0}}</text>元）
			        </view>
			        <view style="font-size:24rpx;color:#999" >
			          1、选择此项提交订单时将直接扣除{{t('冻结资金')}}
			        </view>
			        <view style="font-size:24rpx;color:#999" >
			          2、最多可抵扣订单金额的{{freezemoney_dec_rate}}%
			        </view>
			      </view>
			      <view class="f2" style="font-weight:normal">
			        使用{{t('冻结资金')}}抵扣
			        <checkbox value="1" style="margin-left:6px;transform:scale(.8)"></checkbox>
			      </view>
			    </checkbox-group>
			  </view>
			  <!--冻结资金抵扣 end-->
              <view class="dkdiv-item flex" v-if="have_login==1 && userinfo.dedamount>0 && userinfo.dedamount_maxdkpercent>0">
              	<checkbox-group @change="dedamountdk" class="flex" style="width:100%">
              		<view class="f1">
              			<view>{{userinfo.dedamount*1}} <text style="font-weight: bold;">抵扣金</text>可抵扣 <text
              					style="color:#e94745">{{userinfo.dedamount*1}}</text> 元</view>
                    <view style="font-size:22rpx;color:#999" v-if="userinfo.dedamount_maxdkpercent > 0 && userinfo.dedamount_maxdkpercent<100">
                  	最多可抵扣订单金额的{{userinfo.dedamount_maxdkpercent}}%
                    </view>
              			<view style="font-size:24rpx;color:#999" >
              			 选择此项点击付款时将直接扣除抵扣金
              			</view>
              		</view>
              		<view class="f2">使用<text style="font-weight: bold;">抵扣金</text>抵扣
              			<checkbox value="1" :checked="usededamount" style="margin-left:6px;transform:scale(.8)"></checkbox>
              		</view>
              	</checkbox-group>
              </view>
              <view class="dkdiv-item flex" v-if="have_login==1 && userinfo.shopscore>0 && userinfo.shopscoredk_money>0 && userinfo.shopscoredkmaxpercent>0 ">
                <checkbox-group @change="shopscoredk" class="flex" style="width:100%">
                  <view class="f1">
                    <view>
                      {{userinfo.shopscore*1}} {{t('产品积分')}}可抵扣 <text	style="color:#e94745">{{userinfo.shopscoredk_money*1}}</text>元
                    </view>
                    <view style="font-size:22rpx;color:#999" v-if="userinfo.shopscoremaxtype==0">
                      1、最多可抵扣订单金额的{{userinfo.shopscoredkmaxpercent<=100?userinfo.shopscoredkmaxpercent:'100'}}%
                    </view>
                    <view style="font-size:22rpx;color:#999" v-else-if="userinfo.shopscoremaxtype==1">
                    	1、最多可抵扣{{userinfo.shopscoredkmaxmoney}}元
                    </view>
                    <view style="font-size:24rpx;color:#999" >
                      2、提交订单时将直接扣除{{t('产品积分')}}
                    </view>
                  </view>
                  <view class="f2" style="font-weight:normal">
                    使用{{t('产品积分')}}抵扣
                    <checkbox value="1" style="margin-left:6px;transform:scale(.8)"></checkbox>
                  </view>
                </checkbox-group>
              </view>
              <view v-if="menudata" class="dp-menu" :style="{fontSize:'28rpx',backgroundColor:dhinfo.backgroundColor,padding:'20rpx 0'}">
              	<view style="padding-top:16rpx;">
              		<view class="swiper-item">
              			<view v-for="(item,index) in menudata" :key="index" class="menu-nav5" @click="goto" :data-url="item.link">
              				<image :src="item.pic" :style="{width:'80rpx',height:'80rpx'}"></image>
              				<view class="menu-text"  :style="{color:item.color,height:'28rpx',lineHeight:'28rpx'}">{{item.name|| '按钮文字'}}</view>
              			</view>
              		</view>
              	</view>
              </view>
							<view class="dkdiv-item flex flex-bt">
								<text class="t1">实付金额:</text>
								<text class="t2">￥{{paymoney}}</text>
							</view>
							<view class="remark-item">
								<text class="remark-txt" v-if="remark">{{remark}}</text>
								<text class="remark-btn" @tap="toggleRemarkModal" :style="{color:t('color1')}">{{remark==''?'添加备注':'修改'}}</text>
							</view>
							<view class="dkdiv-item flex flex-bt-tip" @tap="goto" :data-url="'/pages/index/login?frompage='+encodeURIComponent('/pages/maidan/pay?bid='+bid)" :style="{color:t('color1')}">{{login_tip}}</view>
							<view v-if="keyHidden" class="op">
								<view class="btn" @tap="topay" :style="{background:t('color1')}">去支付</view>
							</view>
						</view>
					</view>
					<view class="ad-box" v-if="adlist.length>0">
						<block v-for="(item,index) in adlist" :key="index">
							<view class="ad-item" v-if="item.pic" @tap="goto" :data-url="item.url"><image :src="item.pic" mode="widthFix"></view>
						</block>
					</view>
				</view>
			</view>

			<view v-if="have_login && couponvisible" class="popup__container">
				<view class="popup__overlay" @tap.stop="handleClickMask"></view>
				<view class="popup__modal coupon-modal">
					<view class="popup__title">
						<text class="popup__title-text">请选择{{t('优惠券')}}</text>
						<image :src="pre_url+'/static/img/close.png'" class="popup__close" style="width:36rpx;height:36rpx"
							@tap.stop="handleClickMask" />
					</view>
					<view class="popup__content">
						<couponlist :couponlist="couponList" :choosecoupon="true" :selectedrid="couponrid"
							@chooseCoupon="chooseCoupon"></couponlist>
					</view>
				</view>
			</view>

			<view v-if="!keyHidden" class="keyboard_page">
				<view @click="handleHiddenKey" class="keyboard_none"></view>
				<view class="keyboard_key hind_box" :class="menuindex>-1?'tabbarbot':'notabbarbot'">
					<image @click="handleHiddenKey" class="key-down" :src="pre_url+'/static/img/pack_up.png'" mode=""></image>
					<view class="key-box">
						<view class="number-box clearfix">
							<view v-for="(item,index) in KeyboardKeys" :key="index"
								:class="index === 9 ? 'key key-zero' : 'key'" hover-class="number-box-hover"
								@click="handleKey(item)">{{item}}</view>
						</view>
						<view class="btn-box">
							<!-- TODO: 需要替换成删除icon -->
							<view class="key" hover-class="number-box-hover" data-key="X" @click="handleKey('X')">×
							</view>
							<view :class="money ? 'key pay_btn' : 'key pay_btn pay-btn-display'"
								hover-class="pay-btn-hover" @tap="topay">付款</view>
						</view>
					</view>
				</view>
			</view>

			<view v-if="selectmdDialogShow" class="popup__container">
				<view class="popup__overlay" @tap.stop="hideSelectmdDialog"></view>
				<view class="popup__modal">
					<view class="popup__title">
						<text class="popup__title-text">请选择门店</text>
						<image :src="pre_url+'/static/img/close.png'" class="popup__close" style="width:36rpx;height:36rpx" @tap.stop="hideSelectmdDialog"/>
					</view>
					<view class="popup__content">
						<view class="pstime-item" v-for="(item, index) in mdlist" :key="index" @tap="selectmdRadioChange" :data-index="index">
							<view class="flex1">{{item.name}}</view>
							<view style="color:#999;font-size:24rpx;margin-right:10rpx">{{item.juli ? ' 距离:' + item.juli + '千米' : ''}}</view>
							<view class="radio" :style="index==mdkey ? 'background:'+t('color1')+';border:0' : ''"><image class="radio-img" :src="pre_url+'/static/img/checkd.png'"/></view>
						</view>
					</view>
				</view>
			</view>
			<!-- 备注 -->
			<view v-if="isshowremark" class="popup__container popup__remark">
				<view class="popup__overlay" @tap.stop="toggleRemarkModal"></view>
				<view class="popup__modal">
					<view class="popup__title">
						<text class="popup__title-text">备注信息</text>
						<image :src="pre_url+'/static/img/close.png'" class="popup__close" style="width:36rpx;height:36rpx" @tap.stop="toggleRemarkModal"/>
					</view>
					<view class="popup__content">
						<input  type="text" class="remark" v-model="remark" placeholder-style="font-size:26rpx;color:#999" placeholder="请填写备注信息"/>
					</view>
					<view class="popup__bottom">
						<button class="remark-confirm" @tap="remarkConfirm" :style="{background:t('color1')}">确定</button>
					</view>
				</view>
			</view>
			<view v-if="itemPopup" class="popup__container">
				<view class="popup__overlay" @tap.stop="selectItemPopup"></view>
				<view class="popup__modal">
					<view class="popup__title">
						<text class="popup__title-text">请选择项目</text>
						<image :src="pre_url+'/static/img/close.png'" class="popup__close" style="width:36rpx;height:36rpx" @tap.stop="selectItemPopup"/>
					</view>
					<view class="popup__content">
						<view class="pstime-item" v-for="(item, index) in itemList" :key="index" @tap="selectItem" :data-index="index">
							<view class="flex1">{{item.name}}</view>
							<view class="radio" :style="index==itemIndex ? 'background:'+t('color1')+';border:0' : ''">
								<image class="radio-img" :src="pre_url+'/static/img/checkd.png'"/>
							</view>
						</view>
					</view>
				</view>
			</view>
		</block>
		<loading v-if="loading"></loading>
		<dp-tabbar :opt="opt" @getmenuindex="getmenuindex"></dp-tabbar>
		<popmsg ref="popmsg"></popmsg>
		<wxxieyi></wxxieyi>
	</view>
</template>

<script>
	var app = getApp();

	export default {
		data() {
			return {
				pre_url: app.globalData.pre_url,
				opt: {},
				loading: false,
				isload: false,
				menuindex: -1,

				bid: 0,
        ymid: 0,
				hiddenmodalput: true,
				wxpayst: '',
				alipay: '',
				paypwd: '',
				moneypay: '',
				mdlist: "",
				name: "",
				userinfo: "",
				couponList: [],
				couponrid: 0,
				coupontype: 1,
				usescore: 0,
				money: '',
				disprice: 0,
				dkmoney: 0,
				couponmoney: 0,
				paymoney: 0,
				mdkey: 0,
				couponvisible: false,
				couponkey: 0,
				logo:"",

				KeyboardKeys: [1, 2, 3, 4, 5, 6, 7, 8, 9, 0, '.'],
				keyHidden: false,
				selectmdDialogShow: false,
				adlist:[],
        
        moneydec:false,
        money_dec_rate:0,
        moneyrate:false,
        ali_appid : '',
				//买单备注
				remark:'',
				isshowremark:false,
				have_login:1,
				login_tip:'',
        menudata:'',
        dhinfo:'',
        
        soundid:0,//云音响
        usededamount:false,//是否使用抵扣金抵扣
        itemList:[],
        itemIndex:-1,
        itemPopup:false,
        
        useshopscore:false,//使用产品积分
        sysset:{},
        ispost:false,
		freezemoneyrate:false,
		freezemoneydec:0,//可以使用冻结资金抵扣
		freezemoney_dec_rate:0,//可以使用冻结资金抵扣的比例
		activecoin_bili:0,//激活币比例
		newscore_ratio:0,//新积分比例
		newscore_pack_ratio:0,//新积分加速包
			};
		},

		onLoad: function(opt) {
			this.opt = app.getopts(opt);
			if(this.opt.bid){
				this.bid = this.opt.bid || 0;
			}else if(app.globalData.maidan_bid){
				this.bid = app.globalData.maidan_bid;
			}
			
			if(this.opt.ymid){
					this.ymid          = this.opt.ymid;
					app.globalData.pid = this.opt.ymid;
					uni.setStorageSync('pid', this.opt.ymid);
			}
      this.soundid = this.opt.soundid || 0;
			// 防止loaded覆盖copy
			app.globalData.isinit = false;
			this.getdata();
		},
		onShow:function(){
			if(app.globalData.platform=='wx' && app.globalData.hide_home_button==1){
			  uni.hideHomeButton();
			}
      //处理一些数据，如：未支付返回此页面时，余额抵扣需要返还等
      this.dealdata();
		},
		onPullDownRefresh: function() {
			this.getdata();
		},
		methods: {
			handleHiddenKey() {
				this.keyHidden = true;
			},
			// 显示键盘
			handleShowKey() {
				this.keyHidden = false;
			},
			// 键盘输入
			handleKey(key) {
				const that = this
				const {
					money
				} = this
				// 删除金额
				if (key === 'X') {
					if (money !== '') {
						const payMoney = money.slice(0, money.length - 1)
						that.money = payMoney
					}
				} else {
					// 添加金额
					const payMoney = money + key
					console.log(/^(\d+\.?\d{0,2})$/.test(payMoney), payMoney, 'payMoney')
					if (/^(\d+\.?\d{0,2})$/.test(payMoney)) {
						that.money = payMoney
					}
				}
				this.calculatePrice();
			},

			getdata: function() {
				var that = this; //获取产品信息
				that.loading = true;
				app.get('ApiMaidan/maidan', {
					bid: that.bid
				}, function(res) {
					that.loading = false;
					if (res.status == 0) {
						app.alert(res.msg, function() {
							app.goback();
						});
						return;
					}else if (res.status == 3) {
						app.alert(res.msg, function() {
							app.goto(res.url);
						});
						return;
					}
          if(res.pid){
            app.globalData.pid = res.pid;
            uni.setStorageSync('pid',res.pid);
          }
					that.ali_appid = res.ali_appid;
					//未登录的静默注册
					if(res.need_login==1){
						// #ifdef MP-ALIPAY
						that.alilogin();
						// #endif
						// #ifdef MP-WEIXIN
						that.wxlogin();
						// #endif
						// #ifdef H5
						if(app.globalData.platform && app.globalData.platform=='mp'){
							that.mplogin();
						}
						// #endif
						return;
					}
					var userinfo = res.userinfo;
					var couponList = res.couponList;
					var mdlist = res.mdlist;
					that.wxpayst = res.wxpayst;
					that.alipay = res.alipay;
					that.couponList = res.couponList;
					that.mdlist = res.mdlist;
					that.moneypay = res.moneypay;
					that.name = res.name;
					that.userinfo = res.userinfo;
					that.logo = res.logo;
					that.have_login = res.have_login;
					that.login_tip = res.login_tip;
					that.activecoin_bili = res.activecoin_bili || 0;
					that.newscore_ratio = res.newscore_ratio || 0;
					that.newscore_pack_ratio = res.newscore_pack_ratio || 0;
					if(res.adlist && res.adlist.length>0){
						that.adlist = res.adlist
						that.keyHidden = true
					}
          
          if(res.moneydec){
            that.money_dec_rate = res.money_dec_rate;
          	that.moneydec       = res.moneydec;
          }
		  if(res.freezemoneydec){
		    that.freezemoney_dec_rate = res.freezemoney_dec_rate;
		  	that.freezemoneydec       = res.freezemoneydec;
		  }
          if(res.menudata){
            that.menudata = res.menudata
            that.dhinfo   = res.dhinfo
          }
					if(res.usededamount){
						that.usededamount = res.usededamount;
					}
					if(res.itemList){
						that.itemList = res.itemList;
					}
					that.loaded();

					if(res.copyinfo && !app.globalData.copyinfo){
						app.globalData.copyinfo = res.copyinfo;
						console.log(res.copyinfo)
						uni.setClipboardData({
							data: res.copyinfo,
							showToast:false
						});
					}
					if (mdlist.length > 0) {
						if(that.opt.mdid){
								for (var i in mdlist) {
									if(mdlist[i].id==that.opt.mdid){
										that.mdkey = i;break; 
									}
								}
						}else if(userinfo.maidan_getlocation==1){
							app.getLocation(function(res) {
								var latitude = res.latitude;
								var longitude = res.longitude;
								var speed = res.speed;
								var accuracy = res.accuracy;
							
								for (var i in mdlist) {
									mdlist[i].juli = that.GetDistance(latitude, longitude, mdlist[i]
										.latitude, mdlist[i].longitude);
								}
							
								mdlist = mdlist.sort(that.compare('juli'));
								console.log(mdlist);
								that.mdlist = mdlist;
							});
						}
					}
					that.sysset = res.sysset;
					if(that.sysset.hasOwnProperty('show_mendian_popup') && that.sysset.show_mendian_popup ==1){
						if(that.mdlist.length > 1){
							that.selectmdDialogShow = true;
							that.handleHiddenKey();
						}
					}
				});
			},
			modalinput: function() {
				this.$refs.dialogInput.open()
			},
			//选择门店
			selectmd: function(e) {
				var that = this;
				var itemlist = [];
				var mdlist = this.mdlist;
				for (var i = 0; i < mdlist.length; i++) {
					itemlist.push(mdlist[i].name + (mdlist[i].juli ? ' 距离:' + mdlist[i].juli + '千米' : ''));
				}
				var showlength = 6;
				if(that.sysset.hasOwnProperty('show_mendian_popup') && that.sysset.show_mendian_popup ==1){
					showlength = 1;
				}
				if (itemlist.length > showlength) {
					that.selectmdDialogShow = true;
				} else {
					uni.showActionSheet({
						itemList: itemlist,
						success: function(res) {
							if (res.tapIndex >= 0) {
								that.mdkey = res.tapIndex;
							}
						}
					});
				}
			},
			selectmdRadioChange: function (e) {
				this.mdkey = e.currentTarget.dataset.index;
				this.selectmdDialogShow = false;
			},
			hideSelectmdDialog: function () {
				this.selectmdDialogShow = false
			},
			//积分抵扣
			scoredk: function(e) {
				var usescore = e.detail.value[0];
				if (!usescore) usescore = 0;
				this.usescore = usescore;
				this.calculatePrice();
			},
			inputMoney: function(e) {
				console.log(e);
				var money = e.detail.value;
				if (!money) money = 0;
				var money = parseFloat(money);
				if (money <= 0) money = 0;
				this.money = money;
				this.calculatePrice();
			},
			cancel: function() {
				this.hiddenmodalput = true;
			},
			//计算价格
			calculatePrice: function() {
				var that = this;

				var money = ''
				if (that.money == '') {
					money = 0;
				} else {
					money = parseFloat(that.money);
				}
				if (that.userinfo.discount > 0 && that.userinfo.discount < 10) {
					var disprice = Math.round(money * (1 - 0.1 * that.userinfo.discount) * 100) / 100; //-会员折扣
				} else {
					var disprice = 0;
				}
				var couponmoney = parseFloat(that.couponmoney); //-优惠券抵扣 
				if (that.usescore) {
					var dkmoney = parseFloat(that.userinfo.dkmoney); //-积分抵扣
				} else {
					var dkmoney = 0;
				}
				var scoredkmaxpercent = parseFloat(that.userinfo.scoredkmaxpercent); //最大抵扣比例
				if (dkmoney > 0 && scoredkmaxpercent >= 0 && scoredkmaxpercent <= 100 &&
					dkmoney > (money - disprice - couponmoney) * scoredkmaxpercent * 0.01) {
					dkmoney = (money - disprice - couponmoney) * scoredkmaxpercent * 0.01;
				}
  
				var paymoney = money - disprice - couponmoney - dkmoney; // 商品金额 - 会员折扣 - 优惠券抵扣 - 积分抵扣
        
        if(that.moneydec && that.moneyrate){
          var userinfo  = that.userinfo;
          var money     = userinfo && userinfo.money?parseFloat(userinfo.money):0;
          var rate      = that.money_dec_rate;
          if(userinfo && money>0 && rate>0){
              var dec_money  = paymoney*rate/100;
              dec_money      = dec_money.toFixed(2);
              if(dec_money>=money){
                  dec_money = money;
              }
              paymoney -= dec_money;
          }
        }
		//使用冻结资金抵扣
		if(that.freezemoneydec && that.freezemoneyrate){
		  var userinfo  = that.userinfo;
		  var freezemoney     = userinfo && userinfo.freezemoney?parseFloat(userinfo.freezemoney):0;
		  var rate      = that.freezemoney_dec_rate;
		  if(userinfo && freezemoney>0 && rate>0){
		      var dec_freezemoney  = paymoney*rate/100;
		      dec_freezemoney      = dec_freezemoney.toFixed(2);
		      if(dec_freezemoney>=money){
		          dec_freezemoney = money;
		      }
		      paymoney -= dec_freezemoney;
		  }
		}
        //如果使用抵扣金
        if(that.usededamount && paymoney>0){
          if(that.userinfo && that.userinfo['dedamount'] && that.userinfo['dedamount']>0 &&  that.userinfo['dedamount_maxdkpercent']>0){
              var dedamount_dkmoney  = paymoney*that.userinfo['dedamount_maxdkpercent']/100;
              if(dedamount_dkmoney>=that.userinfo['dedamount']){
                dedamount_dkmoney = that.userinfo['dedamount'];
              }
              if(dedamount_dkmoney>0){
                  paymoney -= dedamount_dkmoney;
              }
          }
        }
        
        //产品积分抵扣
        if (that.useshopscore && paymoney>0 && that.userinfo.shopscore>0 && that.userinfo.shopscoredk_money>0 && that.userinfo.shopscoredkmaxpercent>0) {
          var shopscoredk_money = parseFloat(that.userinfo.shopscoredk_money); //会员产品积分换算最大可抵扣数值
        
          var shopscoremaxtype = parseInt(that.userinfo.shopscoremaxtype);//兑换类型
          if (shopscoremaxtype == 0) {
            var shopscoredkmaxpercent = parseFloat(that.userinfo.shopscoredkmaxpercent); //最大抵扣比例
            var nowshopscoredk_money  = paymoney * shopscoredkmaxpercent * 0.01;//现在可最大抵扣数值
          } else{
            var nowshopscoredk_money = parseFloat(that.userinfo.shopscoredkmaxmoney); //现在可最大抵扣数值
          }
        
          if(nowshopscoredk_money>0){
            nowshopscoredk_money = nowshopscoredk_money.toFixed(2);
            if(nowshopscoredk_money<=shopscoredk_money){
              paymoney -= nowshopscoredk_money;
            }else{
              paymoney -= shopscoredk_money;
            }
          }
        }
				if (paymoney < 0) paymoney = 0;
				paymoney = paymoney.toFixed(2);
				that.paymoney = paymoney;
				that.disprice = disprice;
			},
			chooseCoupon: function(e) {
				var couponrid = e.rid;
				var couponkey = e.key;

				if (couponrid == this.couponrid) {
					this.couponkey = 0;
					this.couponrid = 0;
					this.coupontype = 1;
					this.couponmoney = 0;
					this.couponvisible = false;
				} else {
					var couponList = this.couponList;
					var couponmoney = couponList[couponkey]['money'];
					var coupontype = couponList[couponkey]['type'];
					if (coupontype == 4) {
						couponmoney = this.freightprice;
					} else if (coupontype == 10) {
						//折扣券
						var money = this.money;
						var disprice = this.disprice;
						var dkmoney = this.dkmoney;
						var thismoney = money - disprice  - dkmoney
						couponmoney = thismoney * (100 - couponList[couponkey]['discount']) * 0.01;
						console.log(couponmoney);
					}
					this.couponkey = couponkey;
					this.couponrid = couponrid;
					this.coupontype = coupontype;
					this.couponmoney = couponmoney;
					this.couponvisible = false;
				}
				this.calculatePrice();
			},
      moneydk: function(e) {
      		var that = this;
      		var moneydec = that.moneydec;
      		if(moneydec){
      				that.moneyrate = !that.moneyrate;
      				this.calculatePrice();
      		}
      },
	  freezemoneydk: function(e) {
	  		var that = this;
	  		var freezemoneydec = that.freezemoneydec;
	  		if(freezemoneydec){
				that.freezemoneyrate = !that.freezemoneyrate;
				this.calculatePrice();
	  		}
	  },
			topay: function(e) {
				if(app.globalData.copyinfo){
					console.log(app.globalData.copyinfo)
					uni.setClipboardData({
						data: app.globalData.copyinfo,
						showToast:false
					});
				}
				this.toPay();
			},
			toPay:app.Debounce(function(e) {
				var that = this;
				var money = that.money;
				var couponrid = that.couponrid;
				var usescore = that.usescore;

				if (that.mdlist.length > 0) {
					var mdid = that.mdlist[that.mdkey].id;
				} else {
					var mdid = 0;
				}
				var itemId = 0;
				if(that.itemList.length > 0 && that.itemIndex != -1){
					itemId = that.itemList[that.itemIndex].id;
				}
        if(that.ispost) return;
        that.ispost = true;
        app.showLoading('提交中');
				app.post('ApiMaidan/maidan', {
					bid: that.bid,
          ymid: that.ymid,
					money: money,
					couponrid: couponrid,
					usescore: usescore,
					mdid: mdid,
          moneyrate:that.moneyrate,
					remark:that.remark,
          soundid:that.soundid,
          usededamount:that.usededamount,
					itemid:itemId,
          useshopscore:that.useshopscore,
		  freezemoneyrate:that.freezemoneyrate,
				}, function(res) {
          app.showLoading(false);
          setTimeout(function(){
            that.ispost = false;
          },1000)
					if (res.status == 0) {
						app.error(res.msg);
						return;
					}else if (res.status == 3) {
						app.alert(res.msg, function() {
							app.goto(res.url);
						});
						return;
					}
					app.goto('/pagesExt/pay/pay?id=' + res.payorderid+'&is_maidan=1');
				});
			},1000),
			showCouponList: function() {
				this.couponvisible = true;
			},
			handleClickMask: function() {
				this.couponvisible = false;
			},
			GetDistance: function(lat1, lng1, lat2, lng2) {
				var radLat1 = lat1 * Math.PI / 180.0;
				var radLat2 = lat2 * Math.PI / 180.0;
				var a = radLat1 - radLat2;
				var b = lng1 * Math.PI / 180.0 - lng2 * Math.PI / 180.0;
				var s = 2 * Math.asin(Math.sqrt(Math.pow(Math.sin(a / 2), 2) + Math.cos(radLat1) * Math.cos(radLat2) *
					Math.pow(Math.sin(b / 2), 2)));
				s = s * 6378.137; // EARTH_RADIUS;
				s = Math.round(s * 100) / 100;
				return s;
			},
			compare: function(property) {
				return function(a, b) {
					var value1 = a[property];
					var value2 = b[property];
					return value1 - value2;
				};
			},
			wxlogin: function (){
				var that = this;
				wx.login({success (res1){
					console.log(res1);
					var code = res1.code;
					//用户允许授权
					app.post('ApiIndex/wxbaselogin',{ code:code,pid: app.globalData.pid,maidan:1},function(res2){
						if (res2.status == 1) {
							//app.success(res2.msg);
							that.getdata();
						} else {
							app.error(res2.msg);
						}
						return;
					})
			  }});
			},
			mplogin: function (){
				var that = this;
				var frompage = encodeURIComponent(app._fullurl());
				location.href = app.globalData.pre_url + '/index.php?s=ApiIndex/shouquan&aid=' 
				+ app.globalData.aid + '&session_id=' + app.globalData.session_id + '&pid=' + app.globalData.pid 
				+ '&authlogin=2&maidan=1&frompage=' + encodeURIComponent(frompage);
			},
			alilogin:function(){
        // #ifdef MP-ALIPAY
				var that = this;
				var ali_appid = that.ali_appid;

				if(ali_appid){
					my.getAuthCode ({
						appId :  ali_appid ,
						scopes : ['auth_base'],
					},function(res){
						//var res = JSON.stringify(res);
						if(!res.error && res.authCode){
						  app.post('ApiIndex/alipaylogin', {
							code: res.authCode,
							pid: app.globalData.pid,
							silent:1,
							maidan:1
							//platform:"h5"
						  }, function(res2) {

							if (res2.status!= 0) {
							  app.success(res2.msg);
							  that.getdata();
							}  else {
							  app.error(res2.msg);
							}
						  });
						}else{
						  app.showLoading(false);

						  if(res.errorMessage){
							app.alert(res.errorMessage);
						  }else if(res.errorDesc){
							app.alert(res.errorDesc);
						  }else{
							app.alert('授权出错');
						  }
						  return
						}
					});
				}else{
				  app.alert('系统未配置支付宝参数');
				  return
				}
        // #endif
			},
			toggleRemarkModal:function(){
				this.isshowremark = !this.isshowremark
			},
			remarkConfirm:function(){
				if(this.remark==''){
					app.error('请填写备注信息');
					return;
				}
				this.isshowremark = false;
			},
      dedamountdk:function(){
        this.usededamount = !this.usededamount;
        this.calculatePrice();
      },
			selectItemPopup: function() {
				this.itemPopup = !this.itemPopup;
			},
			selectItem:function(e){
				this.itemIndex = e.currentTarget.dataset.index;
				this.itemPopup = !this.itemPopup;
			},
      //产品积分抵扣
      shopscoredk: function(e) {
      	var useshopscore  = e.detail.value[0];
      	if (!useshopscore) useshopscore = 0;
      	this.useshopscore = useshopscore;
      	this.calculatePrice();
      },
      dealdata: function() {
        //处理一些数据，如：未支付返回此页面时，余额抵扣需要返还等
        var that = this;
        app.get('ApiMaidan/dealdata', {bid: that.bid}, function(res) {})
      }
		}
	}
</script>
<style>
	page {
		background: #f0f0f0;
	}

	.container {
		position: fixed;
		height: 100%;
		width: 100%;
		/* overflow: hidden; */
		overflow-y: scroll;
		z-index: 5;
	}

	.header {
		position: relative;
		padding: 30rpx;
	}

	.header_text {
		font-size: 24rpx;
		color: #666;
	}

	.header_name {
		font-size: 36rpx;
		color: #333;
		font-weight: bold;
	}

	.header_icon {
		position: relative;
		height: 85rpx;
		width: 85rpx;
		margin-right: 20rpx;
		border-radius: 10rpx;
	}

	.header_shop {
		font-size: 28rpx;
		color: #333;
		margin-top: 10rpx;
	}

	.page {
		position: relative;
		padding: 20rpx 50rpx 20rpx 50rpx;
		border-radius: 30rpx 30rpx 0 0;
		background: #fff;
		box-sizing: border-box;
		width: 100%;
		min-height: calc(100% - 150rpx);
	}

	.page_title {
		font-size: 24rpx;
		color: #333;
	}

	.page_module {
		position: relative;
		height: 125rpx;
		border-bottom: 1px solid #f0f0f0;
	}

	.page_notice {
		color: #999;
		font-size: 32rpx;
		font-weight: normal;
	}

	.page_tag {
		font-size: 58rpx;
		color: #333;
		font-weight: bold;
	}

	.page_price {
		margin-left: 20rpx;
		font-size: 54rpx;
		color: #333;
		font-weight: bold;
	}

	.page_cursor {
		width: 4rpx;
		height: 70rpx;
		background: #1AAD19;
		border-radius: 6rpx;
		animation: twinkling 1.5s infinite;
	}

	@keyframes twinkling {
		0% {
			opacity: 0;
		}

		90% {
			opacity: .8;
		}

		100% {
			opacity: 1;
		}
	}

	.info-box {
		position: relative;
		background: #fff;
	}

	.info-item {
		display: flex;
		align-items: center;
		border-bottom: 1px #f3f3f3 solid;
	}

	.info-item:last-child {
		border: none
	}

	.info-item .t1 {
		width: 200rpx;
		height: 120rpx;
		line-height: 120rpx;
		color: #000;
	}

	.info-item .t2 {
		height: 120rpx;
		line-height: 120rpx;
		color: #000;
		text-align: right;
		flex: 1;
		font-size: 28rpx
	}

	.info-item .t2 input {
		height: 80rpx;
		line-height: 80rpx;
		border: 1px solid #f5f5f5;
		padding: 0 5px;
		width: 240rpx;
		font-size: 30rpx;
		margin-right: 10rpx
	}

	.dkdiv {
		margin-top: 20rpx
	}

	.dkdiv-item {
		width: 100%;
		padding: 30rpx 0;
		background: #fff;
		border-bottom: 1px #ededed solid;
	}

	.dkdiv-item:last-child {
		border: none;
	}

	.dkdiv-item .f1 {}

	.dkdiv-item .f2 {
		text-align: right;
		flex: 1
	}

	.dkdiv-item .f3 {
		width: 30rpx;
		height: 30rpx;
	}

	.fpay-btn {
		width: 90%;
		margin: 0 5%;
		height: 80rpx;
		line-height: 80rpx;
		margin-top: 40rpx;
		float: left;
		border-radius: 10rpx;
		color: #fff;
		background: #1aac19;
		border: none;
		font-size: 30rpx;
	}

	.fpay-btn2 {
		width: 90%;
		margin: 0 5%;
		height: 80rpx;
		line-height: 80rpx;
		margin-top: 20rpx;
		float: left;
		border-radius: 10rpx;
		color: #fff;
		background: #e2cc05;
		border: none;
		font-size: 30rpx;
	}

	.mendian {
		width: 90%;
		line-height: 60rpx;
		border-radius: 10rpx;
		padding: 30rpx 5%;
		height: 800rpx;
		overflow-y: scroll;
		border: none;
		border-radius: 5px;
		-webkit-animation-duration: .5s;
		animation-duration: .5s;
	}

	.mendian label {
		display: flex;
		align-items: center;
		border-bottom: 1px solid #f5f5f5;
		padding: 20rpx 0;
		color: #333
	}

	.mendian input {
		margin-right: 10rpx
	}

	.submit {
		text-align: center
	}

	.mendian button {
		padding: 20rpx 60rpx;
		border-radius: 40rpx;
		border: 0;
		margin-top: 20rpx;
		color: #fff;
		background: #31C88E
	}

	.i-as {
		position: fixed;
		width: 100%;
		box-sizing: border-box;
		left: 0;
		right: 0;
		bottom: 0;
		background: #f7f7f8;
		transform: translate3d(0, 100%, 0);
		transform-origin: center;
		transition: all .2s ease-in-out;
		z-index: 900;
		visibility: hidden
	}

	.i-as-show {
		transform: translate3d(0, 0, 0);
		visibility: visible
	}

	.i-as-mask {
		position: fixed;
		top: 0;
		left: 0;
		right: 0;
		bottom: 0;
		background: rgba(0, 0, 0, .7);
		z-index: 900;
		transition: all .2s ease-in-out;
		opacity: 0;
		visibility: hidden
	}

	.i-as-mask-show {
		opacity: 1;
		visibility: visible
	}

	.i-as-header {
		background: #fff;
		text-align: center;
		position: relative;
		font-size: 30rpx;
		color: #555;
		height: 80rpx;
		line-height: 80rpx
	}

	.i-as-header::after {
		content: '';
		position: absolute;
		top: 0;
		left: 0;
		width: 200%;
		height: 200%;
		transform: scale(.5);
		transform-origin: 0 0;
		pointer-events: none;
		box-sizing: border-box;
		border: 0 solid #e9eaec;
		border-bottom-width: 1px
	}

	.i-as-cancel {
		margin-top: 20rpx
	}

	.i-as-cancel button {
		border: 0
	}

	.i-as-cancel button::after {
		border: 0;
	}

	.i-as-content {
		height: 700rpx;
		width: 710rpx;
		margin: 20rpx;
	}


	.op {
		width: 96%;
		margin: 20rpx 2%;
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


	.keyboard_page {
		position: fixed;
		height: 100%;
		width: 100%;
		top: 0;
		left: 0;
		z-index: 999;
	}

	.keyboard_none {
		position: absolute;
		height: 100%;
		width: 100%;
		top: 0;
		left: 0;
	}

	.keyboard_key {
		position: fixed;
		left: 0;
		right: 0;
		bottom: 0;
		height: 0;
		z-index: 10;
		background: #f7f7f7;
		z-index: 9999999999;
		transition: height 0.3s;
		padding: 20rpx 0 0 0;
	}

	.hind_box {
		height: 515rpx;
	}

	.key-box {
		display: flex;
		padding-left: 16rpx;
		padding-bottom: 16rpx;
		padding-bottom: calc(16rpx + constant(safe-area-inset-bottom));
		padding-bottom: calc(16rpx + env(safe-area-inset-bottom));
	}
	
	.key-down{
		height: 50rpx;
		width: 50rpx;
		display: block;
		margin: 0 auto;
	}

	.number-box {
		flex: 3;
	}

	.number-box .key {
		float: left;
		margin: 16rpx 16rpx 0 0;
		width: calc(100% / 3 - 16rpx);
		height: 90rpx;
		border-radius: 10rpx;
		line-height: 90rpx;
		text-align: center;
		font-size: 40rpx;
		font-weight: bold;
		background-color: #fff;
	}

	.number-box .key.key-zero {
		width: calc((100% / 3) * 2 - 16rpx);
	}

	.keyboard .number-box-hover {
		/* 临时定义颜色 */
		background-color: #e1e1e1 !important;
	}

	.btn-box {
		flex: 1;
	}

	.btn-box .key {
		margin: 16rpx 16rpx 0 0;
		height: 90rpx;
		border-radius: 10rpx;
		line-height: 90rpx;
		text-align: center;
		font-size: 40rpx;
		font-weight: bold;
		background-color: #fff;
	}

	.btn-box .pay_btn {
		height: 298rpx;
		line-height: 298rpx;
		font-weight: normal;
		background-color: #1AAD19;
		color: #fff;
		font-size: 32rpx;
	}

	.btn-box .pay_btn.pay-btn-display {
		background-color: #9ED99D !important;
	}

	.btn-box .pay_btn.pay-btn-hover {
		background-color: #179B16;
	}
	.pstime-item {display: flex;border-bottom: 1px solid #f5f5f5;padding: 20rpx 30rpx;}
.pstime-item .radio {flex-shrink: 0;width: 32rpx;height: 32rpx;background: #FFFFFF;border: 2rpx solid #BFBFBF;border-radius: 50%;margin-right: 30rpx}
.pstime-item .radio .radio-img {width: 100%;height: 100%}

/* 广告位 */
.ad-box{width: 100%;padding-bottom: 20rpx;}
.ad-item{width: 100%;display: flex;justify-content: center;margin-bottom: 20rpx;border-radius: 12rpx;}
.ad-item image{max-width: 100%;border-radius: 10rpx;width: 100%;}

.flex-sb{display: flex;justify-content: space-between;flex: 1; align-items: center;}
.remark-btn{margin-left: 10rpx;}
.remark-item{padding-top: 10rpx;}
.remark-txt{color: #999;}
.popup__remark{bottom: 40%;left: 8%;width: 84%;}
.popup__remark .popup__overlay{opacity: 0.6;}
.popup__remark .popup__modal{border-radius: 20rpx;min-height: 360rpx;}
.popup__remark .remark{border: 1px solid #ececec;width: 84%;margin-left: 8%;padding: 20rpx;border-radius: 8rpx;height: 80rpx;line-height: 80rpx;background: #f6f6f6;}
.remark-confirm{width: 82%;margin-left: 9%;border-radius: 60rpx;height: 80rpx;line-height: 80rpx;color: #FFFFFF;margin-top: 20rpx;}
.flex-bt-tip{display: flex;flex-direction: row;justify-content: center;}

.dp-menu {height:auto;position:relative;padding-left:20rpx; padding-right:20rpx; background: #fff;border-bottom: 1px #ededed solid;}
.dp-menu .swiper-item{display:flex;flex-wrap:wrap;flex-direction: row;height:auto;overflow: hidden;align-items: flex-start;}
.dp-menu .menu-nav5 {width:20%;text-align:center;margin-bottom:16rpx;position:relative}
.dp-menu .menu-nav5 .menu-text{overflow: hidden;}

/*返利 和 积分显示*/
.ratio-list{padding-top: 10rpx;}
.ratio-label{height: 40rpx;border-radius: 10rpx;width:160rpx;border: 2rpx solid;margin-right:20rpx;}
.ratio-label .label{width: 55rpx ;height: 40rpx;line-height: 40rpx;border-radius: 10rpx 20rpx 5rpx 10rpx;color: #fff;text-align: center;}
.ratio-label .t1{text-align: center;width: 65%;font-size: 28rpx;}

</style>
