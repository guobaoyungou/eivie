<template>
<view class="container">
	<block v-if="isload">
		<view v-if="needaddress==0" class="address-add">
			<view class="linkitem">
				<text class="f1">联 系 人：</text>
				<input type="text" class="input" :value="linkman" placeholder="请输入您的姓名" @input="inputLinkman" placeholder-style="color:#626262;font-size:28rpx"/>
			</view>
			<view class="linkitem">
				<text class="f1">联系电话：</text>
				<input type="text" class="input" :value="tel" placeholder="请输入您的手机号" @input="inputTel" placeholder-style="color:#626262;font-size:28rpx"/>
			</view>
		</view>
		<view v-else class="address-add flex-y-center" @tap="goto" :data-url="'/pagesB/address/'+(address.id ? 'address' : 'addressadd')+'?fromPage=buy&type=' + (havetongcheng==1?'1':'0')">
			<view class="f1"><image class="img" :src="pre_url+'/static/img/address.png'" /></view>
			<view class="f2 flex1" v-if="address.id">
				<view style="font-weight:bold;color:#111111;font-size:30rpx">{{address.name}} {{address.tel}} <text v-if="address.company">{{address.company}}</text></view>
				<view style="font-size:24rpx">{{address.area}} {{address.address}}</view>
			</view>
			<view v-else class="f2 flex1">请选择收货地址</view>
			<image :src="pre_url+'/static/img/arrowright.png'" class="f3"></image>
		</view>
		<view v-for="(buydata, index) in allbuydata" :key="index" class="buydata">
			<view class="btitle"><image class="img" :src="pre_url+'/static/img/ico-shop.png'"/>{{buydata.business.name}}</view>
			<view class="bcontent">
				<view class="product">
					<view v-for="(item, index2) in buydata.prodata" :key="index2" class="item flex">
						<view class="img" @tap="goto" :data-url="'product?id=' + item.product.id"><image :src="item.product.pic"></image></view>
						<view class="info flex1">
							<view class="f1">{{item.product.name}}</view>
							<view class="f2">规格：{{item.guige.name}}<text v-if="!isNull(item.jldata)">{{item.jldata.jltitle}}</text></view>
							<view class="f3" >
								
								<text style="font-weight:bold;" v-if="!isNull(item.jldata)">￥{{parseFloat(parseFloat(item.guige.sell_price)+parseFloat(item.jldata.jlprice)).toFixed(2)}}</text>
								<text style="font-weight:bold;" v-else>￥{{parseFloat(item.guige.sell_price).toFixed(2)}}</text>
								
								<text style="padding-left:20rpx"> × {{item.num}}</text>
							</view>
						</view>
					</view>
				</view>
				<view class="freight">
					<view class="f1">配送方式</view>
					<view class="freight-ul">
						<view class="flex" style="width:100%;overflow-y:hidden;overflow-x:scroll;">
						 <block v-for="(item, idx2) in buydata.freightList" :key="idx2">
						 <view class="freight-li" :style="buydata.freightkey==idx2?'color:'+t('color1')+';background:rgba('+t('color1rgb')+',0.2)':''" @tap="changeFreight" :data-bid="buydata.bid" :data-index="idx2">{{item.name}}</view>
						 </block>
						</view>
					</view>
					<view class="freighttips" v-if="buydata.freightList[buydata.freightkey].minpriceset==1 && buydata.freightList[buydata.freightkey].minprice > 0 && buydata.freightList[buydata.freightkey].minprice > buydata.product_price">满{{buydata.freightList[buydata.freightkey].minprice}}元起送，还差{{(buydata.freightList[buydata.freightkey].minprice - buydata.product_price).toFixed(2)}}元</view>
					<view class="freighttips" v-if="buydata.freightList[buydata.freightkey].isoutjuli==1">超出配送范围</view>
				</view>

				<view class="price" v-if="buydata.freightList[buydata.freightkey].pstimeset==1">
					<view class="f1">{{buydata.freightList[buydata.freightkey].pstype==1?'取货':'配送'}}时间</view>
					<view class="f2" @tap="choosePstime" :data-bid="buydata.bid">{{buydata.pstimetext==''?'请选择时间':buydata.pstimetext}}<text class="iconfont iconjiantou" style="color:#999;font-weight:normal"></text></view>
				</view>
				<view class="storeitem" v-if="buydata.freightList[buydata.freightkey].pstype==1">
					<view class="panel">
						<view class="f1">取货地点</view>
						<view class="f2" @tap="openLocation" :data-bid="buydata.bid" :data-freightkey="buydata.freightkey" :data-storekey="buydata.freightList[buydata.freightkey].storekey"><text class="iconfont icondingwei"></text>{{buydata.freightList[buydata.freightkey].storedata[buydata.freightList[buydata.freightkey].storekey].name}}</view>
					</view>
					<block v-for="(item, idx) in buydata.freightList[buydata.freightkey].storedata" :key="idx" v-if="idx<5 || qhstoreshowall==true">
						<view class="radio-item" @tap.stop="choosestore" :data-bid="buydata.bid" :data-index="idx">
							<view class="f1">{{item.name}} </view>
							<text style="color:#f50;">{{item.juli}}</text>
							<view class="radio" :style="buydata.freightList[buydata.freightkey].storekey==idx ? 'background:'+t('color1')+';border:0' : ''"><image class="radio-img" :src="pre_url+'/static/img/checkd.png'"/></view>
						</view>
					</block>
						<view v-if="qhstoreshowall==false && (buydata.freightList[buydata.freightkey].storedata).length > 5" class="storeviewmore" @tap="doQhStoreShowAll">- 查看更多 - </view>
				</view>
				<view class="storeitem" v-if="buydata.freightList[buydata.freightkey].pstype==5">
					<view class="panel">
						<view class="f1">配送门店</view>
						<view class="f2" @tap="openMendian" :data-bid="buydata.bid"
							:data-freightkey="buydata.freightkey"
							:data-storekey="buydata.freightList[buydata.freightkey].storekey"><text
								class="iconfont icondingwei"></text>{{buydata.freightList[buydata.freightkey].storedata[buydata.freightList[buydata.freightkey].storekey].name}}
						</view>
					</view>
					<block v-for="(item, idx) in buydata.freightList[buydata.freightkey].storedata" :key="idx">
						<view class="radio-item" @tap.stop="choosestore" :data-bid="buydata.bid" :data-index="idx" v-if="idx<5 || storeshowall==true">
							<view class="f1">{{item.name}} </view>
							<text style="color:#f50;">{{item.juli}}</text>
							<view class="radio"
								:style="buydata.freightList[buydata.freightkey].storekey==idx ? 'background:'+t('color1')+';border:0' : ''">
								<image class="radio-img" :src="pre_url+'/static/img/checkd.png'" />
							</view>
						</view>
					</block>
					<view v-if="storeshowall==false && (buydata.freightList[buydata.freightkey].storedata).length > 5" class="storeviewmore" @tap="doQhStoreShowAll">- 查看更多 - </view>
				</view>

				<view class="price">
					<text class="f1">商品金额</text>
					<text class="f2">¥{{buydata.product_price}}</text>
				</view>
				<view class="price" v-if="buydata.leveldk_money>0">
					<text class="f1">{{t('会员')}}折扣({{userinfo.discount}}折)</text>
					<text class="f2">-¥{{buydata.leveldk_money}}</text>
				</view>
				<view class="price" v-if="buydata.pstime_discount_money>0">
					<text class="f1">优惠定折扣</text>
					<text class="f2">-¥{{buydata.pstime_discount_money}}</text>
				</view>
				<view class="price" v-if="buydata.manjian_money>0">
					<text class="f1">满减活动</text>
					<text class="f2">-¥{{buydata.manjian_money}}</text>
				</view>
				<view class="price">
					<text class="f1">{{buydata.freightList[buydata.freightkey].freight_price_txt || '运费'}}</text>
					<text class="f2">+¥{{buydata.freightList[buydata.freightkey].freight_price}}</text>
				</view>
				<view class="price" v-if="buydata.pack_fee>0">
					<text class="f1">打包费</text>
					<text class="f2">+¥{{buydata.pack_fee}}</text>
				</view>
				<view class="price">
					<view class="f1">{{t('优惠券')}}</view>
					<view v-if="buydata.couponCount > 0" class="f2" @tap="showCouponList" :data-bid="buydata.bid"><text style="color:#fff;padding:4rpx 16rpx;font-weight:normal;border-radius:8rpx;font-size:24rpx" :style="{background:t('color1')}">{{buydata.couponrid!=0?buydata.couponList[buydata.couponkey].couponname:buydata.couponCount+'张可用'}}</text><text class="iconfont iconjiantou" style="color:#999;font-weight:normal"></text></view>
					<text class="f2" v-else style="color:#999">无可用{{t('优惠券')}}</text>
				</view>
				<view class="price" v-if="buydata.cuxiaoCount > 0">
					<view class="f1">促销活动</view>
					<view class="f2" @tap="showCuxiaoList" :data-bid="buydata.bid"><text style="color:#fff;padding:4rpx 16rpx;font-weight:normal;border-radius:8rpx;font-size:24rpx" :style="{background:t('color1')}">{{buydata.cuxiaoname?buydata.cuxiaoname:buydata.cuxiaoCount+'个可用'}}</text><text class="iconfont iconjiantou" style="color:#999;font-weight:normal"></text></view>
				</view>
				<block v-for="(item,idx) in buydata.freightList[buydata.freightkey].field_list">
				<view class="remark" v-if="item.isshow==1">
					<text class="f1">{{item.name}}</text>
					<input type="text" class="flex1" :placeholder="item.tips || '请输入'+item.name" @input="inputfield" :data-field="idx" :data-bid="buydata.bid" placeholder-style="color:#cdcdcd;font-size:28rpx"></input>
				</view>
				</block>
			</view>
		</view>
		
		<view class="scoredk" v-if="userinfo.score2money>0 && (userinfo.scoremaxtype==0 || (userinfo.scoremaxtype==1 && userinfo.scoredkmaxmoney>0))">
			<checkbox-group @change="scoredk" class="flex" style="width:100%">
				<view class="f1">
					<view>{{userinfo.score*1}} {{t('积分')}}可抵扣 <text style="color:#e94745">{{userinfo.scoredk_money*1}}</text> 元</view>
					<view style="font-size:22rpx;color:#999" v-if="userinfo.scoremaxtype==0 && userinfo.scoredkmaxpercent > 0 && userinfo.scoredkmaxpercent<=100">最多可抵扣订单金额的{{userinfo.scoredkmaxpercent}}%</view>
					<view style="font-size:22rpx;color:#999" v-else-if="userinfo.scoremaxtype==1">最多可抵扣{{userinfo.scoredkmaxmoney}}元</view>
				</view>
				<view class="f2">使用{{t('积分')}}抵扣
					<checkbox value="1" style="margin-left:6px;transform:scale(.8)"></checkbox>
				</view>
			</checkbox-group>
		</view>
		<view style="width: 100%; height:182rpx;"></view>
		<view class="footer flex">
			<view class="text1 flex1">总计：
				<text style="font-weight:bold;font-size:36rpx">￥{{alltotalprice}}</text>
			</view>
			<view class="op" @tap="topay" :style="{background:'linear-gradient(-90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'}">提交订单</view>
		</view>

		<view v-if="couponvisible" class="popup__container">
			<view class="popup__overlay" @tap.stop="handleClickMask"></view>
			<view class="popup__modal coupon-modal">
				<view class="popup__title">
					<text class="popup__title-text">请选择{{t('优惠券')}}</text>
					<image :src="pre_url+'/static/img/close.png'" class="popup__close" style="width:36rpx;height:36rpx" @tap.stop="handleClickMask"/>
				</view>
				<view class="popup__content">
					<couponlist :couponlist="allbuydata[bid].couponList" :choosecoupon="true" :selectedrid="allbuydata[bid].couponrid" :bid="bid" @chooseCoupon="chooseCoupon"></couponlist>
				</view>
			</view>
		</view>

		<view v-if="pstimeDialogShow" class="popup__container">
			<view class="popup__overlay" @tap.stop="hidePstimeDialog"></view>
			<view class="popup__modal">
				<view class="popup__title">
					<text class="popup__title-text">请选择{{allbuydata[nowbid].freightList[allbuydata[nowbid].freightkey].pstype==1?'取货':'配送'}}时间</text>
					<image :src="pre_url+'/static/img/close.png'" class="popup__close" style="width:36rpx;height:36rpx" @tap.stop="hidePstimeDialog"/>
				</view>
				<view class="popup__content">
					<view class="flex flex-bt" style="padding: 10rpx 30rpx;" v-if="allbuydata[nowbid].freightList[allbuydata[nowbid].freightkey].discount_yuding_status && allbuydata[nowbid].freightList[allbuydata[nowbid].freightkey].pstimeArr[0].discount">
						<view class="" style="width: 60%;"></view>
						<view>优惠定</view>
						<view>原价定</view>
					</view>
					<view class="pstime-item flex-bt flex-y-center" style="ma" v-for="(item, index) in allbuydata[nowbid].freightList[allbuydata[nowbid].freightkey].pstimeArr" :key="index" @tap="pstimeRadioChange" :data-index="index" :data-isdiscount="0">
						<view class="" style="width: 60%;">{{item.title}}</view>
						
						<view class="discount_radio flex-y-center" :key="index" @tap.stop="pstimeRadioChange" :data-index="index" :data-isdiscount="1" v-if="allbuydata[nowbid].freightList[allbuydata[nowbid].freightkey].discount_yuding_status && item.discount">
							<text style="margin-right: 10rpx;">{{item.discount}}折</text>
							<view class="radio" :style="allbuydata[nowbid].freight_time==item.value && allbuydata[nowbid].pstime_discount_st==1 ? 'background:'+t('color1')+';border:0' : ''"  ><image class="radio-img" :src="pre_url+'/static/img/checkd.png'"/></view>
						</view>
						
						<view class="radio" :style="allbuydata[nowbid].freight_time==item.value && allbuydata[nowbid].pstime_discount_st ==0 ? 'background:'+t('color1')+';border:0' : ''"><image class="radio-img" :src="pre_url+'/static/img/checkd.png'"/></view>
					</view>
				</view>
			</view>
		</view>
		<view v-if="cuxiaovisible" class="popup__container">
			<view class="popup__overlay" @tap.stop="handleClickMask"></view>
			<view class="popup__modal">
				<view class="popup__title">
					<text class="popup__title-text">优惠促销</text>
					<image :src="pre_url+'/static/img/close.png'" class="popup__close" style="width:36rpx;height:36rpx" @tap.stop="handleClickMask"/>
				</view>
				<view class="popup__content">
					<view class="cuxiao-desc">
						<view class="cuxiao-item" @tap="changecx" data-id="0">
							<view class="type-name"><text style="color:#333">不使用促销</text></view>
							<view class="radio" :style="cxid==0 ? 'background:'+t('color1')+';border:0' : ''"><image class="radio-img" :src="pre_url+'/static/img/checkd.png'"/></view>
						</view>
						<view v-for="(item, index) in allbuydata[bid].cuxiaolist" :key="index" class="cuxiao-item" @tap="changecx" :data-id="item.id">
							<view class="type-name"><text style="border-radius:4px;border:1px solid #f05423;color: #ff550f;font-size:20rpx;padding:2px 5px">{{item.tip}}</text> <text style="color:#333;padding-left:20rpx">{{item.name}}</text></view>
							<view class="radio" :style="cxid==item.id ? 'background:'+t('color1')+';border:0' : ''"><image class="radio-img" :src="pre_url+'/static/img/checkd.png'"/></view>
						</view>
					</view>
					<view id="cxproinfo" v-if="cuxiaoinfo.product" style="padding:0 40rpx">
						<view class="product">
							<view class="item flex" style="background:#f5f5f5">
								<view class="img" @tap="goto" :data-url="'product?id=' + cuxiaoinfo.product.id"><image :src="cuxiaoinfo.product.pic"></image></view>
								<view class="info flex1">
									<view class="f1">{{cuxiaoinfo.product.name}}</view>
									<view class="f2">规格：{{cuxiaoinfo.guige.name}}</view>
									<view class="f3"><text style="font-weight:bold;">￥{{cuxiaoinfo.guige.sell_price}}</text><text style="padding-left:20rpx"> × 1</text></view>
								</view>
							</view>
						</view>
					</view>
					<view style="width:100%; height:120rpx;"></view>
					<view style="width:100%;position:absolute;bottom:0;padding:20rpx 5%;background:#fff">
						<view style="width:100%;height:80rpx;line-height:80rpx;border-radius:40rpx;text-align:center;color:#fff;" :style="{background:t('color1')}" @tap="chooseCuxiao">确 定</view>
					</view>
				</view>
			</view>
		</view>
		<view v-if="discount_xieyi_show" class="xieyibox">
			<view class="xieyibox-content">
				<view style="overflow:scroll;height:100%;">
					<parse :content="allbuydata[nowbid].freightList[allbuydata[nowbid].freightkey].discount_xieyi" @navigate="navigate"></parse>
				</view>
				<view style="position:absolute;z-index:9999;bottom:10px;left:7%;margin:0 auto;text-align:center; width: 30%;height: 60rpx; line-height: 60rpx; color: #fff; border-radius: 8rpx;background: #bdbdbd;"   @tap="canceldescClick">取消</view>
				<view style="position:absolute;z-index:9999;bottom:10px;right:7%;margin:0 auto;text-align:center; width: 50%;height: 60rpx; line-height: 60rpx; color: #fff; border-radius: 8rpx;" :style="{background:'linear-gradient(90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'}"  @tap="hidedescClick">已阅读并同意</view>
			</view>
		</view>
	</block>
	<loading v-if="loading"></loading>
	<dp-tabbar :opt="opt"></dp-tabbar>
	<wxxieyi></wxxieyi>
</view>
</template>

<script>
var app = getApp();

export default {
  data() {
    return {
			opt:{},
			loading:false,
      isload: false,
			menuindex:-1,
			
			havetongcheng:0,
      address: [],
      usescore: 0,
      scoredk_money: 0,
      totalprice: '0.00',
      couponvisible: false,
      cuxiaovisible: false,
      bid: 0,
      nowbid: 0,
      needaddress: 1,
      linkman: '',
      tel: '',
			userinfo:{},
      pstimeDialogShow: false,
      pstimeIndex: -1,
      manjian_money: 0,
      cxid: 0,
      latitude: "",
      longitude: "",
      allbuydata: "",
      alltotalprice: "",
      cuxiaoinfo: false,
	  btype:0,//0购物车计算 1直接购买 
	  qhstoreshowall:false,
	  pre_url:app.globalData.pre_url,
	  pstime_discount_st:0,//配送时间的状态
	  pstime_discount_xieyi:0,//配送时间折扣的弹窗
	  discount_xieyi_show:0,//配送时间折扣协议
	  freighttype:'',//选择的配送类型
	  cuxiaoid:0//防止促销选择同一个造成折扣增加问题
    };
  },

  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		if(this.opt){
			this.btype = this.opt.btype;
		}
		if(this.opt.freighttype){
			this.freighttype = this.opt.freighttype;
		}
		this.getdata();
  },
	onPullDownRefresh: function () {
		this.getdata();
	},
  methods: {
		getdata: function () {
			var that = this;
			that.loading = true;
			app.get('ApiRestaurantTakeaway/buy', {prodata: that.opt.prodata,jldata:that.opt.jldata,btype:that.btype,freighttype:that.freighttype}, function (res) {
				that.loading = false;
				if (res.status == 0) {
					if (res.msg) {
						app.alert(res.msg, function () {
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
				that.havetongcheng = res.havetongcheng;
				that.address = res.address;
				that.linkman = res.linkman;
				that.tel = res.tel;
				that.userinfo = res.userinfo;
				that.allbuydata = res.allbuydata;
				that.needLocation = res.needLocation;
				that.scorebdkyf = res.scorebdkyf;
				that.calculatePrice();
				that.loaded();

				if (res.needLocation == 1) {
					app.getLocation(function (res) {
						var latitude = res.latitude;
						var longitude = res.longitude;
						that.latitude = latitude;
						that.longitude = longitude;
						var allbuydata = that.allbuydata;
						for (var i in allbuydata) {
							var freightList = allbuydata[i].freightList;
							for (var j in freightList) {
								if (freightList[j].pstype == 1 || freightList[j].pstype == 5) {
									var storedata = freightList[j].storedata;
									if (storedata) {
										for (var x in storedata) {
											if (latitude && longitude && storedata[x].latitude && storedata[x].longitude) {
												var juli = that.getDistance(latitude, longitude, storedata[x].latitude, storedata[x].longitude);
												storedata[x].juli = juli;
											}
										}
										storedata.sort(function (a, b) {
											return a["juli"] - b["juli"];
										});
										for (var x in storedata) {
											if (storedata[x].juli) {
												storedata[x].juli = storedata[x].juli + '千米';
											}
										}
										allbuydata[i].freightList[j].storedata = storedata;
									}
								}
							}
						}
						that.allbuydata = allbuydata;
					});
				}
			});
		},
    //积分抵扣
    scoredk: function (e) {
      var usescore = e.detail.value[0];
      if (!usescore) usescore = 0;
      this.usescore = usescore;
      this.calculatePrice();
    },
    inputLinkman: function (e) {
      this.linkman = e.detail.value;
    },
    inputTel: function (e) {
      this.tel = e.detail.value;
    },
    inputfield: function (e) {
      var that = this;
      var allbuydata = that.allbuydata;
      var bid = e.currentTarget.dataset.bid;
			var field = e.currentTarget.dataset.field;
      allbuydata[bid][field] = e.detail.value;
      this.allbuydata = allbuydata;
    },
    //选择收货地址
    chooseAddress: function () {
      app.goto('/pagesB/address/address?fromPage=buy&type=' + (this.havetongcheng == 1 ? '1' : '0'));
    },
    //计算价格
    calculatePrice: function () {
      var that = this;
      var address = that.address;
      var allbuydata = that.allbuydata;
      var alltotalprice = 0;
      var allfreight_price = 0;
      var needaddress = 0;

      for (var k in allbuydata) {
        var product_price = parseFloat(allbuydata[k].product_price);
        var leveldk_money = parseFloat(allbuydata[k].leveldk_money); //会员折扣
        var manjian_money = parseFloat(allbuydata[k].manjian_money); //满减活动
        var coupon_money = parseFloat(allbuydata[k].coupon_money); //-优惠券抵扣 
        var cuxiao_money = parseFloat(allbuydata[k].cuxiao_money); //+促销活动  
        var pack_fee = parseFloat(allbuydata[k].pack_fee); //+打包费
		var pstime_discount_money = 0;
        //算运费
        var freightdata = allbuydata[k].freightList[allbuydata[k].freightkey];
        var freight_price = freightdata['freight_price'];
        if (freightdata.pstype != 1 && freightdata.pstype != 3 && freightdata.pstype != 4) {
          needaddress = 1;
        }
        if (allbuydata[k].coupontype == 4) {
          freight_price = 0;
          coupon_money = 0;
        }
		//优惠定，根据选择的时间计算折扣
		if(allbuydata[k].pstime_discount_st ==1 && freightdata.discount_yuding_status ==1 ){
			
			var pstime_discount = allbuydata[k].pstime_discount;//当前折扣
			
			var this_pstime_price = 0; //当前bid下总折扣金额
			if(freightdata.discount_join_type ==1){
				var categoryids = freightdata.categoryids.split(',');//配送方式中的参与分类
				var this_product_pstime_price = 0;//可参与的分类的商品金额
				for(var pk in allbuydata[k]['prodata']){
					var procategory = allbuydata[k]['prodata'][pk].product.cid;
					var this_product_price = allbuydata[k]['prodata'][pk].guige.sell_price * allbuydata[k]['prodata'][pk]['num'];//当前商品的总价
					var pro_category_arr = procategory.split(',');
					var is_pstime_discount = 0;
					for(var ck in pro_category_arr){
						if(app.inArray(pro_category_arr[ck],categoryids) ){
							is_pstime_discount = 1;
							break;
						}
					}
					//该分类是否打折
					if(is_pstime_discount){
						this_product_pstime_price =Number( this_product_price * (10 - pstime_discount)*0.1 )
						product_price -=this_product_pstime_price;
						this_pstime_price +=this_product_pstime_price;
					}
				}
				console.log(this_pstime_price,'this_pstime_price-category');
			}else{//全部
				this_pstime_price=Number(product_price * (10 - pstime_discount)*0.1 )
				product_price -= this_pstime_price
			}
			// pstime_discount_money +=this_pstime_price;
			allbuydata[k].pstime_discount_money =Number( this_pstime_price).toFixed(2);
		}
        var totalprice = product_price + pack_fee - leveldk_money - manjian_money - coupon_money + cuxiao_money;
        if (totalprice < 0) totalprice = 0; //优惠券不抵扣运费

        totalprice = totalprice + freight_price;
        allbuydata[k].freight_price = freight_price.toFixed(2);
        allbuydata[k].totalprice = totalprice.toFixed(2);
        alltotalprice += totalprice;
        allfreight_price += freight_price;
		//配送时间 计算折扣价格
      }
      that.needaddress = needaddress;

      if (that.usescore) {
        var scoredk_money = parseFloat(that.userinfo.scoredk_money); //-积分抵扣
      } else {
        var scoredk_money = 0;
      }

      var oldalltotalprice = alltotalprice;
      alltotalprice = alltotalprice - scoredk_money;
      if (alltotalprice < 0) alltotalprice = 0;

      if (that.scorebdkyf == '1' && scoredk_money > 0 && alltotalprice < allfreight_price) {
        //积分不抵扣运费
        alltotalprice = allfreight_price;
        scoredk_money = oldalltotalprice - allfreight_price;
      }
      var scoredkmaxpercent = parseFloat(that.userinfo.scoredkmaxpercent); //最大抵扣比例
      var scoremaxtype = parseInt(that.userinfo.scoremaxtype);
      var scoredkmaxmoney = parseFloat(that.userinfo.scoredkmaxmoney);

      if (scoremaxtype == 0 && scoredk_money > 0 && scoredkmaxpercent > 0 && scoredkmaxpercent <= 100 && scoredk_money > oldalltotalprice * scoredkmaxpercent * 0.01) {
        scoredk_money = oldalltotalprice * scoredkmaxpercent * 0.01;
        alltotalprice = oldalltotalprice - scoredk_money;
      } else if (scoremaxtype == 1 && scoredk_money > scoredkmaxmoney) {
        scoredk_money = scoredkmaxmoney;
        alltotalprice = oldalltotalprice - scoredk_money;
      }

      if (alltotalprice < 0) alltotalprice = 0;
      alltotalprice = alltotalprice.toFixed(2);
      that.alltotalprice = alltotalprice;
      that.allbuydata = allbuydata;
    },
    changeFreight: function (e) {
      var that = this;
      var allbuydata = that.allbuydata;
      var bid = e.currentTarget.dataset.bid;
      var index = e.currentTarget.dataset.index;
      var freightList = allbuydata[bid].freightList;
			console.log(allbuydata);
			console.log(freightList[index]);
			if(freightList[index].pstype==1 && freightList[index].storedata.length < 1) {
				app.error('无可自提门店');return;
			}
			if(freightList[index].pstype==5 && freightList[index].storedata.length < 1) {
				app.error('无可配送门店');return;
			}
			allbuydata[bid].freightkey = index;
			that.allbuydata = allbuydata;
			that.calculatePrice();
    },
    chooseFreight: function (e) {
      var that = this;
      var allbuydata = that.allbuydata;
      var bid = e.currentTarget.dataset.bid;
      console.log(bid);
      console.log(allbuydata);
      var freightList = allbuydata[bid].freightList;
      var itemlist = [];

      for (var i = 0; i < freightList.length; i++) {
        itemlist.push(freightList[i].name);
      }

      uni.showActionSheet({
        itemList: itemlist,
        success: function (res) {
					if(res.tapIndex >= 0){
						allbuydata[bid].freightkey = res.tapIndex;
						that.allbuydata = allbuydata;
						that.calculatePrice();
					}
        }
      });
    },
    choosePstime: function (e) {
      var that = this;
      var allbuydata = that.allbuydata;
      var bid = e.currentTarget.dataset.bid;
      var freightkey = allbuydata[bid].freightkey;
      var freightList = allbuydata[bid].freightList;
      var freight = freightList[freightkey];
      var pstimeArr = freightList[freightkey].pstimeArr;
      var itemlist = [];
      for (var i = 0; i < pstimeArr.length; i++) {
        itemlist.push(pstimeArr[i].title);
      }
      if (itemlist.length == 0) {
        app.alert('当前没有可选' + (freightList[freightkey].pstype == 1 ? '取货' : '配送') + '时间段');
        return;
      }
      that.nowbid = bid;
      that.pstimeDialogShow = true;
      that.pstimeIndex = -1;
    },
    pstimeRadioChange: function (e) {
		var that = this;
		var allbuydata = that.allbuydata;
		var pstimeIndex = e.currentTarget.dataset.index;
		var nowbid = that.nowbid;
		var freightkey = allbuydata[nowbid].freightkey;
		var freightList = allbuydata[nowbid].freightList;
		var freight = freightList[freightkey];
		var pstimeArr = freightList[freightkey].pstimeArr;
		var choosepstime = pstimeArr[pstimeIndex];
		allbuydata[nowbid].pstimetext = choosepstime.title;
		allbuydata[nowbid].freight_time = choosepstime.value;

		//配送时间的折扣
		var pstime_discount_st = e.currentTarget.dataset.isdiscount;
		allbuydata[nowbid].pstime_discount_st = pstime_discount_st;
		// that.pstime_discount_st = pstime_discount_st??0;
		//非折扣 直接隐藏弹窗
		if(pstime_discount_st ==1){
			that.pstimeIndex = pstimeIndex;
			allbuydata[nowbid].pstime_discount = choosepstime.discount;
			that.calculatePrice();
		}else{
			that.pstimeDialogShow = false;
			that.pstime_discount_xieyi = 0;
			allbuydata[nowbid].pstime_discount_money = 0;
			that.calculatePrice();
		}
		that.allbuydata = allbuydata;
    },
    hidePstimeDialog: function () {
		//选择了优惠定，且协议未同意
		var nowbid = this.nowbid;
		if(this.allbuydata[nowbid].pstime_discount_st ==1 && this.pstime_discount_xieyi==0){
			this.discount_xieyi_show = true;
			return false;
		}else{
			  this.pstimeDialogShow = false;
		}
		console.log( this.pstimeDialogShow,' this.pstimeDialogShow');

    },
	//隐藏协议
	canceldescClick:function(){
		this.discount_xieyi_show = false;
	},
	//同意
	hidedescClick(){
		this.pstime_discount_xieyi = 1;
		this.discount_xieyi_show = false;
		this.pstimeDialogShow = false;
	},
		chooseCoupon:function(e){
      var allbuydata = this.allbuydata;
			var bid = e.bid;
			var couponrid = e.rid;
      var couponkey = e.key;
			if (couponrid == allbuydata[bid].couponrid) {
        allbuydata[bid].couponkey = 0;
        allbuydata[bid].couponrid = 0;
        allbuydata[bid].coupontype = 1;
        allbuydata[bid].coupon_money = 0;
        this.allbuydata = allbuydata;
        this.couponvisible = false;
      } else {
        var couponList = allbuydata[bid].couponList;
        var coupon_money = couponList[couponkey]['couponmoney'];
        var coupontype = couponList[couponkey]['type'];
        allbuydata[bid].couponkey = couponkey;
        allbuydata[bid].couponrid = couponrid;
        allbuydata[bid].coupontype = coupontype;
        allbuydata[bid].coupon_money = coupon_money;
        this.allbuydata = allbuydata;
        this.couponvisible = false;
      }
      this.calculatePrice();
		},
    choosestore: function (e) {
      var bid = e.currentTarget.dataset.bid;
			var storekey = e.currentTarget.dataset.index;
			var allbuydata = this.allbuydata;
      var buydata = allbuydata[bid];
			var freightkey = buydata.freightkey
			allbuydata[bid].freightList[freightkey].storekey = storekey
      this.allbuydata = allbuydata;
    },
    //提交并支付
    topay: function () {
      var that = this;
      var needaddress = that.needaddress;
      var addressid = this.address.id;
      var linkman = this.linkman;
      var tel = this.tel;
      var usescore = this.usescore;
      var frompage = that.opt.frompage ? that.opt.frompage : '';
      var allbuydata = that.allbuydata;
      if (needaddress == 0) addressid = 0;

      if (needaddress == 1 && addressid == undefined) {
        app.error('请选择收货地址');
        return;
      }
      var buydata = [];
      for (var i in allbuydata) {
				var freightkey = allbuydata[i].freightkey;
        if (allbuydata[i].freightList[freightkey].pstimeset == 1 && allbuydata[i].freight_time == '') {
          app.error('请选择' + (allbuydata[i].freightList[freightkey].pstype == 1 ? '取货' : '配送') + '时间');
          return;
        }
				if (allbuydata[i].freightList[freightkey].pstype == 1 || allbuydata[i].freightList[freightkey].pstype == 5) {
					var storekey = allbuydata[i].freightList[freightkey].storekey;
					var storeid = allbuydata[i].freightList[freightkey].storedata[storekey].id;
				}else{
					var storeid = 0;
				}
			var thisbuydata={
				bid: allbuydata[i].bid,
				prodata: allbuydata[i].prodatastr,
				cuxiaoid: allbuydata[i].cuxiaoid,
				couponrid: allbuydata[i].couponrid,
				freight_id: allbuydata[i].freightList[freightkey].id,
				freight_time: allbuydata[i].freight_time,
				storeid: storeid,
				message: allbuydata[i].message,
				field1: allbuydata[i].field1,
				field2: allbuydata[i].field2,
				field3: allbuydata[i].field3,
				field4: allbuydata[i].field4,
				field5: allbuydata[i].field5,
			}
			if(allbuydata[i].pstime_discount_st ==1){
				thisbuydata.pstime_discount = allbuydata[i].pstime_discount;
			}
			console.log(thisbuydata,'thisbuydata');
			buydata.push(thisbuydata);
      }
			app.showLoading('提交中');
      app.post('ApiRestaurantTakeaway/createOrder', {frompage: frompage,buydata: buydata,addressid: addressid,linkman: linkman,tel: tel,usescore: usescore,jldata:that.opt.jldata,btype:that.btype}, function (res) {
				app.showLoading(false);
        if (res.status == 0) {
          //that.showsuccess(res.data.msg);
          app.error(res.msg);
          return;
        }
        app.goto('/pagesExt/pay/pay?id=' + res.payorderid, 'redirect');
      });
    },
    showCouponList: function (e) {
      this.couponvisible = true;
      this.bid = e.currentTarget.dataset.bid;
    },
    handleClickMask: function () {
      this.couponvisible = false;
      this.cuxiaovisible = false;
    },
    showCuxiaoList: function (e) {
      this.cuxiaovisible = true;
      this.bid = e.currentTarget.dataset.bid;
    },
    changecx: function (e) {
      var that = this;
      var cxid = e.currentTarget.dataset.id;
      console.log(cxid);
      that.cxid = cxid;
      if (cxid == 0) {
        that.cuxiaoinfo = false;
        return;
      }
      app.post("ApiRestaurantTakeaway/getcuxiaoinfo", {id: cxid}, function (res) {
        that.cuxiaoinfo = res;
      });
    },
	changeProdataNum(){
		var bid = this.bid;
		console.log('changeProdataNumchangeProdataNum');
		var allbuydata = this.allbuydata;
		var thisallbuydata = allbuydata[bid];
		var prodata = thisallbuydata.prodata;
		var new_prodatastr_arr = [];
		var product_price = 0;
		var need_leveldk_money = 0;
		for(var p in prodata){
			var thiprodata = prodata[p];
			if((thiprodata.product.select_type =='kx' || thiprodata.product.select_type =='bx') && thiprodata.product.follow ==1){
				thiprodata.num = this.renshu;
			}
			prodata[p] = thiprodata;
			var this_productprice =parseFloat(parseFloat(thiprodata.guige.sell_price) * parseFloat(thiprodata.num) ).toFixed(2);
			product_price =parseFloat(parseFloat( product_price) + parseFloat(this_productprice)).toFixed(2);
			
			new_prodatastr_arr.push( thiprodata.product.id+','+thiprodata.guige.id+','+thiprodata.num+',' + thiprodata.carid);
			
			if(thiprodata.product.join_member_disount =='1'){
				if(thiprodata.product.jl_is_discount==0 && thiprodata.product.njlprice>0){
					this_productprice =parseFloat(parseFloat(this_productprice) - parseFloat(thiprodata.product.njlprice)).toFixed(2);
				}
				need_leveldk_money = parseFloat(parseFloat(this_productprice) + parseFloat(need_leveldk_money)).toFixed(2);
			}else{
				console.log(thiprodata.product.lvprice,'thiprodata.product.lvprice');
				if(app.isNull(thiprodata.product.lvprice) || thiprodata.product.lvprice ==0){
					console.log(need_leveldk_money,'need_leveldk_money');
					need_leveldk_money = parseFloat(parseFloat(this_productprice) + parseFloat(need_leveldk_money)).toFixed(2);
				}
			}
		}
		allbuydata[bid].prodatastr = new_prodatastr_arr.join('-');
		allbuydata[bid].leveldk_money = parseFloat ( need_leveldk_money * (1- this.userinfo.discount*0.1)).toFixed(2);
		 //重新赋值 商品信息 和 商品价格
		 
		allbuydata[bid].prodata = prodata;
		allbuydata[bid].product_price = product_price;
		this.allbuydata = allbuydata;
		this.calculatePrice();
	},
    chooseCuxiao: function () {
      var that = this;
      var allbuydata = that.allbuydata;
      var bid = that.bid;
      var cxid = that.cxid;
	  if(that.cuxiaoid == cxid){
		this.cuxiaovisible = false;
		return;
	  }
	   that.cuxiaoid = cxid;//防止重复
      if (cxid == 0) {
        allbuydata[bid].cuxiaoid = '';
        allbuydata[bid].cuxiao_money = 0;
        allbuydata[bid].cuxiaoname = '不使用促销';
		that.changeProdataNum();
      } else {
        var cxtype = that.cuxiaoinfo.info.type;
				console.log(cxtype);
        if (cxtype == 1 || cxtype == 6) {
          //满额立减 满件立减
          allbuydata[bid].cuxiao_money = that.cuxiaoinfo.info['money'] * -1;
        } else if (cxtype == 2) {
          //满额赠送
          allbuydata[bid].cuxiao_money = 0;
        } else if (cxtype == 3) {
          //加价换购
          allbuydata[bid].cuxiao_money = that.cuxiaoinfo.info['money'];
        } else if (cxtype == 4 || cxtype == 5) {
			var product_price = parseFloat(allbuydata[bid].product_price);
					var leveldk_money = parseFloat(allbuydata[bid].leveldk_money); //会员折扣
					var manjian_money = parseFloat(allbuydata[bid].manjian_money); //满减活动
					var not_cuxiao_price = parseFloat(allbuydata[bid].not_cuxiao_price);
					
			//不参与促销的产品，参与会员折扣，折上折的原因
			//参与促销的产品，算出没个商品的促销金额
			var cuxiao_money = 0;
			var cuxiao_leveldk_money = 0;//不能参与的折上折 会员折扣 需要减去
			if(that.cuxiaoinfo.info.fwtype ==2){//指定菜品
				var productids = that.cuxiaoinfo.info.productids;
				if(productids !=''){
					var productids_arr = productids.split(',');//参与促销的产品
					var prodata = that.allbuydata[bid].prodata;
					var this_cuxiao_leveldk_money =0; 
					var need_leveldk_money = 0;
					for(var i=0;i<prodata.length ; i++){
						var thisprodata = prodata[i];
						var this_product = thisprodata.product;
						var this_guige = thisprodata.guige;
						var this_product_price = this_guige.sell_price * thisprodata.num;
						
						if(app.inArray(this_product.id,productids_arr)){
							var this_cuxiaomoney = (1 - that.cuxiaoinfo.info['zhekou'] * 0.1) * this_product_price;
							cuxiao_money =parseFloat (parseFloat(cuxiao_money) + parseFloat(this_cuxiaomoney)).toFixed(2);
							//计算需要折扣的
							if(thisprodata.product.join_member_disount =='1'){
								if(thisprodata.product.jl_is_discount==0 && thisprodata.product.njlprice>0){
									this_product_price =parseFloat(parseFloat(this_product_price) - parseFloat(thisprodata.product.njlprice)).toFixed(2);
								}
								need_leveldk_money = parseFloat(parseFloat(this_product_price) + parseFloat(need_leveldk_money)).toFixed(2);
							}else{
								if(app.isNull(thisprodata.product.lvprice) || thisprodata.product.lvprice ==0){
									need_leveldk_money = parseFloat(parseFloat(this_product_price) + parseFloat(need_leveldk_money)).toFixed(2);
								}
							}
							
						}
					}
					cuxiao_leveldk_money =parseFloat(need_leveldk_money * (1- this.userinfo.discount*0.1)).toFixed(2)
				}	
			}else if(that.cuxiaoinfo.info.fwtype ==1){
				//指定类目
				
				var prodata = that.allbuydata[bid].prodata;
				var categoryids = that.cuxiaoinfo.info.categoryids;//当前促销的类目id
					var need_leveldk_money = 0;
				if(!app.isNull(categoryids)){
					for(var i=0;i<prodata.length ; i++){
						var thisprodata = prodata[i];
						var this_product = thisprodata.product;
						var this_guige = thisprodata.guige;
						var this_product_price = this_guige.sell_price * thisprodata.num;
						
						var product_cids = this_product.cid.split(',');
						let intersection = product_cids.filter(value => categoryids.includes(value));
						
						if(intersection.length > 0){
							var this_cuxiaomoney = (1 - that.cuxiaoinfo.info['zhekou'] * 0.1) * this_product_price;
							cuxiao_money =parseFloat (parseFloat(cuxiao_money) + parseFloat(this_cuxiaomoney)).toFixed(2);
						if(thisprodata.product.join_member_disount =='1'){
							if(thisprodata.product.jl_is_discount==0 && thisprodata.product.njlprice>0){
								this_product_price =parseFloat(parseFloat(this_product_price) - parseFloat(thisprodata.product.njlprice)).toFixed(2);
							}
							need_leveldk_money = parseFloat(parseFloat(this_product_price) + parseFloat(need_leveldk_money)).toFixed(2);
						}else{
							if(app.isNull(thisprodata.product.lvprice) || thisprodata.product.lvprice ==0){
								need_leveldk_money = parseFloat(parseFloat(this_product_price) + parseFloat(need_leveldk_money)).toFixed(2);
							}
						}
						}
					}
				}
				console.log(need_leveldk_money,'need_leveldk_money');
				cuxiao_leveldk_money =parseFloat(need_leveldk_money * (1- this.userinfo.discount*0.1)).toFixed(2)
			
			}else{
				var prodata = that.allbuydata[bid].prodata;
				var need_leveldk_money = 0;
				for(var i=0;i<prodata.length ; i++){
					var thisprodata = prodata[i];
					var this_product = thisprodata.product;
					var this_guige = thisprodata.guige;
					var this_product_price = this_guige.sell_price * thisprodata.num;
					var this_cuxiaomoney = (1 - that.cuxiaoinfo.info['zhekou'] * 0.1) * this_product_price;
					cuxiao_money =parseFloat (parseFloat(cuxiao_money) + parseFloat(this_cuxiaomoney)).toFixed(2);
					if(thisprodata.product.join_member_disount =='1'){
						if(thisprodata.product.jl_is_discount==0 && thisprodata.product.njlprice>0){
							this_product_price =parseFloat(parseFloat(this_product_price) - parseFloat(thisprodata.product.njlprice)).toFixed(2);
						}
						need_leveldk_money = parseFloat(parseFloat(this_product_price) + parseFloat(need_leveldk_money)).toFixed(2);
					}else{
						if(app.isNull(thisprodata.product.lvprice) || thisprodata.product.lvprice ==0){
							need_leveldk_money = parseFloat(parseFloat(this_product_price) + parseFloat(need_leveldk_money)).toFixed(2);
						}
					}
				}
			
				console.log(need_leveldk_money,'need_leveldk_money');
				cuxiao_leveldk_money =parseFloat(need_leveldk_money * (1- this.userinfo.discount*0.1)).toFixed(2)
			}		
			that.allbuydata[bid].cuxiao_money =cuxiao_money * -1;
			if(cuxiao_leveldk_money > 0){
				that.allbuydata[bid].leveldk_money =  parseFloat (parseFloat(that.allbuydata[bid].leveldk_money) - parseFloat(cuxiao_leveldk_money)).toFixed(2);
			}
			 that.allbuydata[bid].product_price = parseFloat(parseFloat(product_price )).toFixed(2);
        }
        allbuydata[bid].cuxiaoid = cxid;
        allbuydata[bid].cuxiaotype = cxtype;
        allbuydata[bid].cuxiaoname = that.cuxiaoinfo.info['name'];
      }
      this.allbuydata = allbuydata;
      this.cuxiaovisible = false;
      this.calculatePrice();
    },
		openLocation:function(e){
			var allbuydata = this.allbuydata
			var bid = e.currentTarget.dataset.bid;
			var freightkey = e.currentTarget.dataset.freightkey;
			var storekey = e.currentTarget.dataset.storekey;
			var frightinfo = allbuydata[bid].freightList[freightkey]
			var storeinfo = frightinfo.storedata[storekey];
			console.log(storeinfo)
			var latitude = parseFloat(storeinfo.latitude);
			var longitude = parseFloat(storeinfo.longitude);
			var address = storeinfo.name;
			uni.openLocation({
			 latitude:latitude,
			 longitude:longitude,
			 name:address,
			 scale: 13
			})
		},
		doQhStoreShowAll:function(){
			this.qhstoreshowall = true;
		},
  }
}
</script>
<style>
.address-add{ width:94%;margin:20rpx 3%;background:#fff;border-radius:20rpx;padding: 20rpx 3%;min-height:140rpx;}
.address-add .f1{margin-right:20rpx}
.address-add .f1 .img{ width: 66rpx; height: 66rpx; }
.address-add .f2{ color: #666; }
.address-add .f3{ width: 26rpx; height: 26rpx;}

.linkitem{width: 100%;padding:1px 0;background: #fff;display:flex;align-items:center}
.linkitem .f1{width:160rpx;color:#111111}
.linkitem .input{height:50rpx;padding-left:10rpx;color:#222222;font-weight:bold;font-size:28rpx;flex:1}

.buydata{width:94%;margin:0 3%;background:#fff;margin-bottom:20rpx;border-radius:20rpx;}

.btitle{width:100%;padding:20rpx 20rpx;display:flex;align-items:center;color:#111111;font-weight:bold;font-size:30rpx}
.btitle .img{width:34rpx;height:34rpx;margin-right:10rpx}

.bcontent{width:100%;padding:0 20rpx}

.product{width:100%;border-bottom:1px solid #f4f4f4} 
.product .item{width:100%; padding:20rpx 0;background:#fff;border-bottom:1px #ededed dashed;}
.product .item:last-child{border:none}
.product .info{padding-left:20rpx;}
.product .info .f1{color: #222222;font-weight:bold;font-size:26rpx;line-height:36rpx;margin-bottom:10rpx;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:2;overflow:hidden;}
.product .info .f2{color: #999999; font-size:24rpx}
.product .info .f3{color: #FF4C4C; font-size:28rpx;display:flex;align-items:center;margin-top:10rpx}
.product image{ width:140rpx;height:140rpx}

.freight{width:100%;padding:20rpx 0;background:#fff;display:flex;flex-direction:column;}
.freight .f1{color:#333;margin-bottom:10rpx}
.freight .f2{color: #111111;text-align:right;flex:1}
.freight .f3{width: 24rpx;height:28rpx;}
.freighttips{color:red;font-size:24rpx;}

.freight-ul{width:100%;display:flex;}
.freight-li{flex-shrink:0;display:flex;background:#F5F6F8;border-radius:24rpx;color:#6C737F;font-size:24rpx;text-align: center;height:48rpx; line-height:48rpx;padding:0 28rpx;margin:12rpx 10rpx 12rpx 0}


.price{width:100%;padding:20rpx 0;background:#fff;display:flex;align-items:center}
.price .f1{color:#333}
.price .f2{ color:#111;font-weight:bold;text-align:right;flex:1}
.price .f3{width: 24rpx;height:24rpx;}

.scoredk{width:94%;margin:0 3%;margin-bottom:20rpx;border-radius:20rpx;padding:24rpx 20rpx; background: #fff;display:flex;align-items:center}
.scoredk .f1{color:#333333}
.scoredk .f2{ color: #999999;text-align:right;flex:1}

.remark{width: 100%;padding:16rpx 0;background: #fff;display:flex;align-items:center}
.remark .f1{color:#333;width:200rpx}
.remark input{ border:0px solid #eee;height:70rpx;padding-left:10rpx;text-align:right}

.footer {width: 100%;background: #fff;margin-top: 5px;position: fixed;left: 0px;bottom: 0px;padding:0 20rpx;display:flex;align-items:center;z-index:8}
.footer .text1 {height:110rpx;line-height:110rpx;color: #2a2a2a;font-size: 30rpx;}
.footer .text1  text{color: #e94745;font-size: 32rpx;}
.footer .op{width: 200rpx;height:80rpx;line-height:80rpx;color: #fff;text-align: center;font-size: 30rpx;border-radius:44rpx}

.storeitem{width: 100%;padding:20rpx 0;display:flex;flex-direction:column;color:#333}
.storeitem .panel{width: 100%;height:60rpx;line-height:60rpx;font-size:28rpx;color:#333;margin-bottom:10rpx;display:flex}
.storeitem .panel .f1{color:#333}
.storeitem .panel .f2{ color:#111;font-weight:bold;text-align:right;flex:1}
.storeitem .radio-item{display:flex;width:100%;color:#000;align-items: center;background:#fff;border-bottom:0 solid #eee;padding:8rpx 20rpx;}
.storeitem .radio-item:last-child{border:0}
.storeitem .radio-item .f1{color:#666;flex:1}
.storeitem .radio{flex-shrink:0;width: 32rpx;height: 32rpx;background: #FFFFFF;border: 2rpx solid #BFBFBF;border-radius: 50%;margin-left:30rpx}
.storeitem .radio .radio-img{width:100%;height:100%}

.pstime-item{display:flex;border-bottom: 1px solid #f5f5f5;padding:20rpx 30rpx;}
.pstime-item .radio{flex-shrink:0;width: 32rpx;height: 32rpx;background: #FFFFFF;border: 2rpx solid #BFBFBF;border-radius: 50%;margin-right:30rpx}
.pstime-item .radio .radio-img{width:100%;height:100%}

.cuxiao-desc{width:100%}
.cuxiao-item{display: flex;padding:0 40rpx 20rpx 40rpx;}
.cuxiao-item .type-name{font-size:28rpx; color: #49aa34;margin-bottom: 10rpx;flex:1}
.cuxiao-item .radio{flex-shrink:0;width: 32rpx;height: 32rpx;background: #FFFFFF;border: 2rpx solid #BFBFBF;border-radius: 50%;margin-right:30rpx}
.cuxiao-item .radio .radio-img{width:100%;height:100%}

.storeviewmore{width:100%;text-align:center;color:#889;height:40rpx;line-height:40rpx;margin-top:10rpx}
/* 优惠定 */
.xieyibox{width:100%;height:100%;position:fixed;top:0;left:0;z-index:99;background:rgba(0,0,0,0.7)}
.xieyibox-content{width:90%;margin:0 auto;height:30%;background:#fff;color:#333;padding:5px 10px 50px 10px;position:relative;border-radius:2px;top:60%}
.discount_radio{width: 120rpx;}
</style>