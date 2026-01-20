<template>
<view class="container">
	<block v-if="isload">
		<view class="address-add flex-y-center" v-if="is_bar_table ==0">
			<view class="f1">桌台信息</view>
			<view class="f2 flex1" v-if="tableinfo.id">
				<view style="font-weight:bold;color:#111111;font-size:30rpx">{{tableinfo.name}}<text style="font-size:24rpx;font-weight:normal;color:#666;margin-left:10rpx">{{tableinfo.seat}}人桌</text></view>
			</view>
			<view v-else class="f2 flex1">请扫描桌台二维码</view>
			<image :src="pre_url+'/static/img/arrowright.png'" class="f3"></image>
		</view>
		<view class="header" style="margin-bottom: 25rpx;" v-if="is_bar_table ==1">
			<view class="header_title flex-y-center flex-bt">
				<view class="flex-y-center shop_title" >
					{{mdinfo.name}}
					<!-- <image :src="pre_url+'/static/img/arrowright.png'" class="header_detail"></image> -->
				</view>
				<!-- <image @tap="goSearch" class="header_serach" src="../../static/img/search_ico.png"></image> -->
			</view>
			<view class="header_address flex-y-center f1">
				<text>距离 {{mdjuli}}</text>
				<text v-if="mdinfo.address" style="margin-left: 10rpx;">| {{mdinfo.province}}{{mdinfo.city}}{{mdinfo.district}}{{mdinfo.address}}</text>
			</view>
		</view>
		<view v-for="(buydata, index) in allbuydata" :key="index" class="buydata">
			<view class="btitle"><image class="img" :src="pre_url+'/static/img/ico-shop.png'"/>{{buydata.business.name}}</view>
			<view class="bcontent">
				<view class="product">
					<view v-for="(item, index2) in buydata.prodata" :key="index2" class="item ">
						<view class="flex" style="position: relative;">
							<view class="img" @tap="goto" :data-url="'product?id=' + item.product.id"><image :src="item.product.pic"></image></view>
							<view class="info flex1">
								<view class="f1">{{item.product.name}}</view>
								<view class="f2" v-if="item.guige.name">规格：{{item.guige.name}}{{item.jldata.jltitle}} <text v-if="item.jldata.njltitle">（{{item.jldata.njltitle}})</text></view>
								<!--套餐中产品展示-->
								<view class="f2" v-if=" item.guige.ggtext && item.guige.ggtext.length > 0">
									<view v-for="(gitem,index) in item.guige.ggtext">{{gitem}}</view>
								</view>
								<view class="f3">
									<text style="font-weight:bold;">
										￥{{parseFloat(parseFloat(item.guige.sell_price)+parseFloat(item.jldata.jlprice)).toFixed(2)}} 
										<text v-if="item.product.product_type && item.product.product_type ==1" style="font-size: 20rpx;">/斤</text>	 
									</text>								
									<text style="padding-left:20rpx"> × {{item.num}}</text><text v-if="item.product.product_type && item.product.product_type ==1" style="font-size: 20rpx;">斤</text>
								</view>
								<view class="tip" v-if="item.product.select_type =='kx' || item.product.select_type =='bx'">
									<text v-if="item.product.select_type =='kx'">可选</text>
									<text v-if="item.product.select_type =='bx'">必选</text>
								</view>
								<!-- 删除按钮 -->
								<view  class="prodel" @tap="prodel" :data-key="index2" :data-bid="index" v-if="item.product.select_type =='kx' ">
									<image :src="pre_url+'/static/img/del.png'" class="img"/>删除
								</view>
								<!-- 删除按钮 -->
							</view>
						</view>
						
						<view class=" flex-y-center" style="color:#999999" >	
							<view v-if="item.ogid"><text v-if="item.remark">备注：{{item.remark}}</text></view>
							<view v-else class="flex-y-center">备注：<input  type="text" class="flex1"   placeholder="请输入您的口味或要求" @input="inputgoodsremark" :data-index="index" :data-index2="index2" placeholder-style="color:#cdcdcd;font-size:24rpx"></input></view>	
						</view>		
					</view>
				</view>
				<view class="price" v-if="ordertype == 'create_order' && is_bar_table==0 && change_people_number !=0 ">
					<text class="f1">就餐人数</text>
					<view class="f2" v-if="order && order.eattype ==1">{{order.renshu}}</view>
					<view v-else class="f2" @tap="showRenshuSelect" :data-bid="buydata.bid">{{buydata.renshu>0 ? buydata.renshu+'人' : '请选择人数'}}<text class="iconfont iconjiantou" style="color:#999;font-weight:normal"></text></view>
				</view>

				<view class="price">
					<text class="f1">商品金额</text>
					<text class="f2">¥{{buydata.product_price}}</text>
				</view>
				<view class="price" v-if="buydata.leveldk_money>0">
					<text class="f1">{{t('会员')}}折扣({{userinfo.discount}}折)</text>
					<text class="f2">-¥{{buydata.leveldk_money}}</text>
				</view>
				<view class="price" v-if="buydata.manjian_money>0">
					<text class="f1">满减活动</text>
					<text class="f2">-¥{{buydata.manjian_money}}</text>
				</view>
				<view class="price" v-if="ordertype == 'create_order' && is_bar_table ==0">
					<text class="f1">{{buydata.tea_fee_text}}</text>
					<text class="f2">+¥{{buydata.tea_fee * (buydata.renshu>0 ? buydata.renshu : 1)}}</text>
				</view>
				<view class="price" v-if="service_money>0">
					<text class="f1">服务费</text>
					<text class="f2">¥{{service_money}}</text>
				</view>
				<view class="price" v-if="ordertype == 'create_order'">
					<view class="f1">{{t('优惠券')}}</view>
					<view v-if="buydata.couponCount > 0" class="f2" @tap="showCouponList" :data-bid="buydata.bid"><text style="color:#fff;padding:4rpx 16rpx;font-weight:normal;border-radius:8rpx;font-size:24rpx" :style="{background:t('color1')}">{{buydata.couponrid!=0?buydata.couponList[buydata.couponkey].couponname:buydata.couponCount+'张可用'}}</text><text class="iconfont iconjiantou" style="color:#999;font-weight:normal"></text></view>
					<text class="f2" v-else style="color:#999">无可用{{t('优惠券')}}</text>
				</view>
				<view class="price" v-if="ordertype == 'create_order' && buydata.cuxiaoCount > 0">
					<view class="f1">促销活动</view>
					<view class="f2" @tap="showCuxiaoList" :data-bid="buydata.bid"><text style="color:#fff;padding:4rpx 16rpx;font-weight:normal;border-radius:8rpx;font-size:24rpx" :style="{background:t('color1')}">{{buydata.cuxiaoname?buydata.cuxiaoname:buydata.cuxiaoCount+'个可用'}}</text><text class="iconfont iconjiantou" style="color:#999;font-weight:normal"></text></view>
				</view>
				<view class="remark">
					<text class="f1">备注</text>
					<input type="text" class="flex1" placeholder="请输入您的口味或要求" @input="inputfield" data-field="message" :data-bid="buydata.bid" placeholder-style="color:#cdcdcd;font-size:28rpx"></input>
				</view>
			</view>
		</view>
		
		<view class="scoredk" v-if="ordertype == 'create_order' && userinfo.score2money>0 && (userinfo.scoremaxtype==0 || (userinfo.scoremaxtype==1 && userinfo.scoredkmaxmoney>0))">
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
		<view v-if="renshuvisible" class="popup__container">
			<view class="popup__overlay" @tap.stop="handleClickMask"></view>
			<view class="popup__modal">
				<view class="popup__title">
					<text class="popup__title-text">请选择就餐人数</text>
					<image :src="pre_url+'/static/img/close.png'" class="popup__close" style="width:36rpx;height:36rpx" @tap.stop="handleClickMask"/>
				</view>
				<view class="popup__content">
					<view class="cuxiao-desc">
						<view class="cuxiao-item" @tap="changerenshu" :data-id="item" v-for="item in [1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20]" :key="index">
							<view class="type-name"><text style="color:#333">{{item}}人</text></view>
							<view class="radio" :style="renshu==item ? 'background:'+t('color1')+';border:0' : ''"><image class="radio-img" :src="pre_url+'/static/img/checkd.png'"/></view>
						</view>
					</view>
					
					<view style="width:100%; height:120rpx;"></view>
					<view style="width:100%;position:absolute;bottom:0;padding:20rpx 5%;background:#fff">
						<view style="width:100%;height:80rpx;line-height:80rpx;border-radius:40rpx;text-align:center;color:#fff;" :style="{background:t('color1')}" @tap="chooseRenshu">确 定</view>
					</view>
				</view>
			</view>
		</view>
	</block>
	<loading v-if="loading"></loading>
	<dp-tabbar :opt="opt"></dp-tabbar>
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
			
      address: [],
      usescore: 0,
      scoredk_money: 0,
      totalprice: '0.00',
      couponvisible: false,
      cuxiaovisible: false,
			renshuvisible:false,
			renshu:1,
      bid: 0,
      nowbid: 0,
      needaddress: 1,
      linkman: '',
      tel: '',
			userinfo:{},
      manjian_money: 0,
      cxid: 0,
      latitude: "",
      longitude: "",
      allbuydata: "",
      alltotalprice: "",
      cuxiaoinfo: false,
			tableId:'',
			tableinfo:{},
			ordertype:'create_order',
		btype:0,//0购物车计算 1直接购买 
		is_bar_table:0,
		mdid:'',
		mdinfo:'',
		mdjuli:'',
		service_money:0,//服务费
		tmplids:[],
		fzcode:'',//活码code
		change_people_number:1,
		token:'',//发送socket的token
		isbook:0,
		bookid:'',
		orderid:'',
		pre_url: app.globalData.pre_url,
		order:[],//先吃后付的订单
		cuxiaoid:0//防止促销选择同一个造成折扣增加问题
    };
  },

  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.tableId = this.opt.tableId || '';
		this.isbook = this.opt.isbook || 0;
		this.bookid = this.opt.bookid || '';
		this.orderid = this.opt.orderid || '';
		if(this.opt){
			this.btype = this.opt.btype;
			this.bid = this.opt.bid;
			this.mdid =this.opt.mdid;
			this.renshu = this.opt.renshu;
		}
		var cachelongitude = app.getCache('user_current_longitude');
		var cachelatitude = app.getCache('user_current_latitude');
		if(cachelongitude && cachelatitude){
			this.latitude = cachelatitude
			this.longitude = cachelongitude
		}else{
			var that = this;
			app.getLocation(function(res) {
				that.latitude = res.latitude;
				that.longitude = res.longitude;
			});
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
			app.get('ApiRestaurantShop/buy', {prodata: that.opt.prodata,tableId:that.tableId,frompage:that.opt.frompage,jldata:that.opt.jldata,btype:that.btype,bid:that.bid,mdid:that.mdid,fzcode:that.opt.fzcode,isbook:that.isbook,bookid:that.bookid,orderid:that.orderid,renshu:that.renshu}, function (res) {
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
				that.tableinfo = res.tableinfo;
				that.userinfo = res.userinfo;
				that.allbuydata = res.allbuydata;
				that.scorebdkyf = res.scorebdkyf;
				that.ordertype = res.ordertype;
				that.is_bar_table = res.is_bar_table;
				if(that.tableinfo && that.tableinfo.pindan_status ==1){
					that.token = res.token;
				}
				if(that.is_bar_table ==1){
					that.mdinfo = res.mdinfo;
					var mdjuli = that.getDistance(that.longitude, that.latitude, that.mdinfo.longitude,
							that.mdinfo.latitude);
						that.mdjuli = mdjuli ? mdjuli + ' km' : '0 km'
				}
				that.tmplids = res.tmplids?res.tmplids:[];
				
				var bid = that.bid;
				if(that.ordertype =='create_order'){
					that.allbuydata[bid].renshu = that.renshu
				}
				that.change_people_number = res.change_people_number;
				that.order = res.order;
				that.calculatePrice();
				that.loaded();
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
	inputgoodsremark: function (e) {
	  var that = this;
	  var allbuydata = that.allbuydata;
	  var index = e.currentTarget.dataset.index;
	  var index2 = e.currentTarget.dataset.index2;

	  allbuydata[index]['prodata'][index2]['remark'] = e.detail.value;
	  this.allbuydata = allbuydata;
	  console.log(this.allbuydata);
	},
    //计算价格
    calculatePrice: function () {
      var that = this;
      var allbuydata = that.allbuydata;
      var alltotalprice = 0;
      for (var k in allbuydata) {
        var product_price = parseFloat(allbuydata[k].product_price);
        var leveldk_money = parseFloat(allbuydata[k].leveldk_money); //会员折扣
        var manjian_money = parseFloat(allbuydata[k].manjian_money); //满减活动
        var coupon_money = parseFloat(allbuydata[k].coupon_money); //-优惠券抵扣 
        var cuxiao_money = parseFloat(allbuydata[k].cuxiao_money); //+促销活动  
        var tea_fee = parseFloat(allbuydata[k].tea_fee) * (allbuydata[k].renshu ? allbuydata[k].renshu : 1); //+茶位费
       
        var totalprice = product_price + tea_fee - leveldk_money - manjian_money - coupon_money + cuxiao_money;
        if (totalprice < 0) totalprice = 0; //优惠券不抵扣运费

        allbuydata[k].totalprice = totalprice.toFixed(2);
        alltotalprice += totalprice;
      }

      if (that.usescore) {
        var scoredk_money = parseFloat(that.userinfo.scoredk_money); //-积分抵扣
      } else {
        var scoredk_money = 0;
      }

      var oldalltotalprice = alltotalprice;
      alltotalprice = alltotalprice - scoredk_money;
      if (alltotalprice < 0) alltotalprice = 0;

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
	  if(that.ordertype =='create_order'){
		var service_money =0;
		if(that.tableinfo && that.tableinfo.minprice > 0){
		  if(alltotalprice < that.tableinfo.minprice){
			  if(that.tableinfo.service_fee_type ==0){
				  service_money = that.tableinfo.service_fee;
			  }else{
				  service_money = (that.tableinfo.service_fee/100 *alltotalprice ).toFixed(2);
			  }
		  }
		}
		that.service_money = service_money;
		alltotalprice =   parseFloat(alltotalprice) + parseFloat(service_money)
	   }

      if (alltotalprice < 0) alltotalprice = 0;
      alltotalprice = (alltotalprice).toFixed(2);
      that.alltotalprice = alltotalprice;
      that.allbuydata = allbuydata;
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
		if(coupontype ==10){
			var totalprice = allbuydata[bid]['product_price'] + allbuydata[bid]['tea_fee'] - allbuydata[bid]['leveldk_money'] - allbuydata[bid]['manjian_money'];
			var coupon = couponList[couponkey];
			coupon_money =parseFloat( totalprice * (100-coupon['discount']) * 0.01).toFixed(2);
			allbuydata[bid].coupon_money = coupon_money;
		}else{
			allbuydata[bid].coupon_money = coupon_money;
		}
		
        this.allbuydata = allbuydata;
        this.couponvisible = false;
      }
			
      this.calculatePrice();
		},
    //提交并支付
    topay: function () {
		var that = this;
		var usescore = this.usescore;
		var frompage = that.opt.frompage ? that.opt.frompage : '';
		var allbuydata = that.allbuydata;
		var buydata = [];
		for (var i in allbuydata) {
			if(that.ordertype == 'create_order' && that.tableId > 0){
				if(allbuydata[i].renshu==0){
					app.error('请选择就餐人数');
				 return;
				}
			}
			var remark_arr = [];
			for(var p in allbuydata[i].prodata){
				 remark_arr.push(allbuydata[i].prodata[p]['remark']);
			}
			var ogid = [];
			for(var p in allbuydata[i].prodata){
				 ogid.push(allbuydata[i].prodata[p]['ogid']);
			}
			buydata.push({
				bid: allbuydata[i].bid,
				prodata: allbuydata[i].prodatastr,
				cuxiaoid: allbuydata[i].cuxiaoid,
				couponrid: allbuydata[i].couponrid,
				renshu: allbuydata[i].renshu,
				message: allbuydata[i].message,
				field1: allbuydata[i].field1,
				field2: allbuydata[i].field2,
				field3: allbuydata[i].field3,
				field4: allbuydata[i].field4,
				field5: allbuydata[i].field5,
				remark:remark_arr,
				ogid:ogid
			});
		}
			app.showLoading('提交中');
			var apiurl = 'ApiRestaurantShop/createOrder';
			if(that.ordertype == 'edit_order' && that.btype ==0){
				apiurl = 'ApiRestaurantShop/editOrder';
			}
			var eattype = that.order.eattype; //是先付还是后付
      app.post(apiurl, {frompage: frompage,buydata: buydata,tableid:that.tableId,usescore: usescore,jldata:that.opt.jldata,btype:that.btype,mdid:that.mdid,fzcode:that.opt.fzcode,isbook:that.isbook,bookid:that.bookid,orderid:that.orderid,eattype:eattype}, function (res) {
				app.showLoading(false);
				if (res.status == 0) {
				  //that.showsuccess(res.data.msg);
				  app.error(res.msg);
				  return;
				}
				//下单后 提醒同桌的人已下单
				if( that.tableinfo && that.tableinfo.pindan_status ==1){
					app.sendSocketMessage({
					  type: 'restaurant_shop_createorder',
					  token: that.token,
					  data: {aid:app.globalData.aid,bid:that.bid,tableid:that.tableId,msg:'',mid:app.globalData.mid}
					});
				}
				if(that.ordertype != 'edit_order' && that.tmplids.length > 0){
					// #ifdef MP-WEIXIN
					that.subscribeMessage(function () {
						if(frompage == 'admin')
							app.goto('/admin/restaurant/tableWaiterDetail?id=' + that.tableId, 'redirect');
						else {
							// if(res.pay_after !=1)
								app.goto('/pagesExt/pay/pay?id=' + res.payorderid, 'redirect');
							// else
							// 	app.goto('/restaurant/shop/orderlist', 'redirect');
								
						}
					 });
					 // #endif
					 // #ifdef MP-ALIPAY
					 var resdata = res;
					 my.requestSubscribeMessage({
					   //需要用户订阅的消息模板的id的集合
					   entityIds: that.tmplids,
					   success: (res) => {
					     // res.behavior=='subscribe'
					     console.log("接口调用成功的回调", res);
						 if(frompage == 'admin')
						 	app.goto('/admin/restaurant/tableWaiterDetail?id=' + that.tableId, 'redirect');
						 else {
						 	// if(resdata.pay_after !=1)
						 		app.goto('/pagesExt/pay/pay?id=' + resdata.payorderid, 'redirect');
						 	// else
						 	// 	app.goto('/restaurant/shop/orderlist', 'redirect');
						 		
						 }
					   },
					   fail: (res) => {
					     console.log("接口调用失败的回调", res); 
					   },
					   complete: (res) => {
					     console.log("接口调用结束的回调", res)
					   }
					 });
					 // #endif
				}else{
					if(frompage == 'admin')
						app.goto('/admin/restaurant/tableWaiterDetail?id=' + that.tableId, 'redirect');
					else {
						// if(res.pay_after !=1)
							app.goto('/pagesExt/pay/pay?id=' + res.payorderid, 'redirect');
						// else
						// 	app.goto('/restaurant/shop/orderlist', 'redirect');
							
					}
				}
				
      });
    },
	showRenshuSelect:function(e){
		this.renshuvisible = true;
		this.bid = e.currentTarget.dataset.bid;
	},
	changerenshu: function (e) {
	  var that = this;
	  that.renshu = e.currentTarget.dataset.id;
	},
    chooseRenshu: function () {
      var that = this;
      var allbuydata = that.allbuydata;
      var bid = that.bid;
		that.allbuydata[bid].renshu = that.renshu
		this.renshuvisible = false;
			console.log('changeProdataNumchangeProdataNum');
		this.changeProdataNum();
		
		that.calculatePrice();
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
    showCouponList: function (e) {
      this.couponvisible = true;
      this.bid = e.currentTarget.dataset.bid;
    },
    handleClickMask: function () {
      this.couponvisible = false;
      this.cuxiaovisible = false;
      this.renshuvisible = false;
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
      app.post("ApiRestaurantShop/getcuxiaoinfo", {id: cxid}, function (res) {
        that.cuxiaoinfo = res;
      });
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
          //满额打折 满件打折
          that.allbuydata[bid].cuxiao_money =cuxiao_money * -1;
		  if(cuxiao_leveldk_money > 0){
			  that.allbuydata[bid].leveldk_money =  parseFloat (parseFloat(that.allbuydata[bid].leveldk_money) - parseFloat(cuxiao_leveldk_money)).toFixed(2);
		  }

		   that.allbuydata[bid].product_price = parseFloat(parseFloat(product_price )).toFixed(2);
		  
        }else if (cxtype == 7) {//第二件折扣
			if(that.cuxiaoinfo.info.is_one_product ==1){//同一件
			    var prodata = allbuydata[bid].prodata;
				for(var i=0;i<prodata.length ; i++){
					if(prodata[i].product.package_is_cuxiao ==0 && prodata[i].product.product_type==2){
						continue;
					}
					if(prodata[i].num > 1){
						allbuydata[bid].cuxiao_money  = (1 - that.cuxiaoinfo.info['zhekou'] * 0.1) * (prodata[i].guige.sell_price) * -1
					}
				}
			}else{
				var this_min_money=0;
				var prodata = allbuydata[bid].prodata;
				
				for(var i=0;i<prodata.length ; i++){
					
					if(prodata[i].product.package_is_cuxiao ==0 && prodata[i].product.product_type==2){
						continue;
					}
					console.log(prodata[i].guige);
					if(prodata[i].guige.sell_price < this_min_money || this_min_money ==0){
						this_min_money = prodata[i].guige.sell_price;
					}
				}
				allbuydata[bid].cuxiao_money  =this_min_money *  (1 - that.cuxiaoinfo.info['zhekou'] * 0.1) * -1
				console.log(this_min_money,'this_min_money');
			}
		}
        allbuydata[bid].cuxiaoid = cxid;
        allbuydata[bid].cuxiaotype = cxtype;
        allbuydata[bid].cuxiaoname = that.cuxiaoinfo.info['name'];
      }
      this.allbuydata = allbuydata;
      this.cuxiaovisible = false;
      this.calculatePrice();
    },
	prodel(e){
		var key = e.currentTarget.dataset.key;
		console.log(key,'key');
		var bid = e.currentTarget.dataset.bid;
		var allbuydata = this.allbuydata;
		var thisallbuydata = allbuydata[bid];
		var prodata = thisallbuydata.prodata;
		var delprodata = prodata[key];
		//删除对应商品
		var newprodata =prodata.filter(function(item,index){
			return index !=key;
		});
		 
		 //重新计算商品总价
		 var product_price = 0;
		 var new_prodatastr_arr = [];
		 for(var p in newprodata){
			 var thiprodata = newprodata[p];
		 	 var this_productprice =parseFloat(parseFloat(thiprodata.guige.sell_price) * parseFloat(thiprodata.num) ).toFixed(2);
			  product_price =parseFloat(parseFloat( product_price) + parseFloat(this_productprice)).toFixed(2);
			 new_prodatastr_arr.push( thiprodata.product.id+','+thiprodata.guige.id+','+thiprodata.num+',' + thiprodata.carid);
		 }
		
		 //计算删除的折扣
		  var leveldiscount = 0;
		 if(thiprodata.join_member_disount !=0){
		 	leveldiscount = parseFloat (delprodata.guige.sell_price * delprodata.num * (1- this.userinfo.discount*0.1)).toFixed(2);
		 }  
		 allbuydata[bid].leveldk_money = parseFloat ( allbuydata[bid].leveldk_money - leveldiscount).toFixed(2);
		 //删除prodatastr
		allbuydata[bid].prodatastr = new_prodatastr_arr.join('-');
		 //重新赋值 商品信息 和 商品价格
		allbuydata[bid].prodata = newprodata;
		allbuydata[bid].product_price = product_price;
		this.allbuydata = allbuydata;
		this.calculatePrice();
	}
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

.header {
	position: relative;
	padding: 30rpx;
	
	width: 94%;
	margin: 0 3%;
	background: #fff;
	border-radius: 20rpx;
	margin-bottom: 21rpx;
}

.header_title {
	font-size: 28rpx;
	color: #333;
}
.header_address {
		font-size: 24rpx;
		color: #999;
		margin-top: 20rpx;
	}
.prodel{
	display: flex;
	align-items: center;
	justify-content: center;
	padding: 0 20rpx;
	height: 52rpx;
	position: absolute;
	top: 50%;
	right: -20rpx;
	border-radius: 26rpx 0 0 26rpx;
	background: #FFE8E1;
	color: #222222;
	font-size: 24rpx;
	font-weight: 700;
}
.prodel .img{
	width: 25rpx;
	height: 25rpx;
	margin: 0 6rpx;
}
.tip{position: absolute;right: -20rpx;top: -10rpx;height: 40rpx;line-height: 40rpx;;width: 80rpx;background: #ffab91;color: #fff;text-align: center;border-radius: 26rpx 0 0 26rpx;font-size: 22rpx}
</style>