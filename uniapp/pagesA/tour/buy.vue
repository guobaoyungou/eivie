<template>
<view class="container">
	<block v-if="isload">
		<form @submit="topay">
		<view v-if="needaddress==0" class="address-add">
			<view class="linkitem">
				<text class="f1">联 系 人：</text>
				<input type="text" class="input" :value="linkman" placeholder="请输入您的姓名" @input="inputLinkman" placeholder-style="color:#626262;font-size:28rpx;"/>
			</view>
			<view class="linkitem">
				<text class="f1">联系电话：</text>
				<input type="text" class="input" :value="tel" placeholder="请输入您的手机号" @input="inputTel" placeholder-style="color:#626262;font-size:28rpx;"/>
			</view>
		</view>
		<view v-else class="address-add flex-y-center" @tap="goto" :data-url="'/pagesB/address/address?fromPage=buy&type=' + (havetongcheng==1?'1':'0')">
			<view class="f1"><image class="img" :src="pre_url+'/static/img/address.png'" /></view>
			<view class="f2 flex1" v-if="address.name">
				<view style="font-weight:bold;color:#111111;font-size:30rpx">{{address.name}} {{address.tel}} <text v-if="address.company">{{address.company}}</text></view>
				<view style="font-size:24rpx">{{address.area}} {{address.address}}</view>
			</view>
			<view v-else class="f2 flex1">请选择收货地址</view>
			<image :src="pre_url+'/static/img/arrowright.png'" class="f3"/>
		</view>
		<view class="buydata">
			<view class="bcontent">
				<view class="product">
					<view class="item flex">
						<view class="img">
							<image class="img" :src="product.pic"></image>
						</view>
						<view class="info flex1">
							<view class="f1">{{product.name}}</view>
							<view class="f3">￥{{product.sell_price}}</view>
						</view>
					</view>
				</view>
				<view class="freight">
					<view class="f1">配送方式</view>
					<view class="freight-ul">
						<view class="flex" style="width:100%;overflow-y:hidden;overflow-x:scroll;">
						 <block v-for="(item, idx2) in freightList" :key="idx2">
						 <view class="freight-li" :style="freightkey==idx2?'color:'+t('color1')+';background:rgba('+t('color1rgb')+',0.2)':''" @tap="changeFreight" :data-index="idx2">{{item.name}}</view>
						 </block>
						</view>
					</view>
					<view class="freighttips" v-if="freightList[freightkey].minpriceset==1 && freightList[freightkey].minprice > 0 && freightList[freightkey].minprice*1 > product_price*1">满{{freightList[freightkey].minprice}}元起送，还差{{(freightList[freightkey].minprice - product_price).toFixed(2)}}元</view>
					<view class="freighttips" v-if="freightList[freightkey].isoutjuli==1">超出配送范围</view>
				</view>
				<view class="price" v-if="freightList[freightkey].pstimeset==1">
					<view class="f1">{{freightList[freightkey].pstype==1?'取货':'配送'}}时间</view>
					<view class="f2" @tap="choosePstime">{{pstimetext==''?'请选择时间':pstimetext}}<text class="iconfont iconjiantou" style="color:#999;font-weight:normal"></text></view>
				</view>
				<view class="storeitem" v-if="freightList[freightkey].pstype==1">
					<view class="panel">
						<view class="f1">取货地点</view>
						<view class="f2" @tap="openMendian" :data-freightkey="freightkey" :data-storekey="freightList[freightkey].storekey"><text class="iconfont icondingwei"></text>{{freightList[freightkey].storedata[freightList[freightkey].storekey].name}}</view>
					</view>
					<block v-for="(item, idx) in freightList[freightkey].storedata" :key="idx">
						<view class="radio-item" @tap.stop="choosestore" :data-index="idx" v-if="idx<5 || storeshowall==true">
							<view class="f1">{{item.name}} </view>
							<text style="color:#f50;">{{item.juli}}</text>
							<view class="radio" :style="freightList[freightkey].storekey==idx ? 'background:'+t('color1')+';border:0' : ''"><image class="radio-img" :src="pre_url+'/static/img/checkd.png'"/></view>
						</view>
					</block>
					<view v-if="storeshowall==false && (freightList[freightkey].storedata).length > 5" class="storeviewmore" @tap="doStoreShowAll">- 查看更多 - </view>
				</view>
				<view class="price">
					<text class="f1">订单金额</text>
					<text class="f2">¥{{product_price}}</text>
				</view>

				<view class="price">
					<view class="f1"><text v-if="freightList[freightkey].pstype==1">服务费</text><text v-else>运费</text></view>
					<text class="f2">+¥{{freight_price}}</text>
				</view>

        <view class="price" v-if="!tour_auth" style="overflow: hidden;">
        	<text class="f1">优惠码</text>
        	<view class="f2" @tap="getDecprice" style="overflow: hidden;">
              <view style="font-weight: 400;background-color: #f1f1f1;width: 400rpx;line-height: 70rpx;border-radius: 4rpx;float: right;padding: 0 20rpx;">{{tour_code}}</view>
          </view>
          <view @tap="saoyisao" style="float: right;width: 120rpx;overflow: hidden;">
            <image :src="pre_url+'/static/img/ico-scan.png'" style="width: 50rpx;height: 50rpx;float: right;"></image>
          </view>
        </view>

        <view class="price" v-if="tour_code_decprice !== ''">
        	<text class="f1">优惠</text>
        	<text class="f2">
              -¥{{tour_code_decprice}}
          </text>
        </view>
        <view class="form-item" v-if="showemail">
        	<view class="label">邮箱</view>
          <input @input="inputEmail" :value="email" type="text" name="email" class="input" placeholder="请输入邮箱" placeholder-style="font-size:28rpx"/>
        </view>
				<view class="form-item" v-for="(item,idx) in freightList[freightkey].formdata" :key="item.id">
					<view class="label">{{item.val1}}<text v-if="item.val3==1" style="color:red"> *</text></view>
					<block v-if="item.key=='input'">
						<input type="text" :name="'form'+idx" class="input" :placeholder="item.val2" placeholder-style="font-size:28rpx"/>
					</block>
					<block v-if="item.key=='textarea'">
						<textarea :name="'form'+idx" class='textarea' :placeholder="item.val2" placeholder-style="font-size:28rpx"/>
					</block>
					<block v-if="item.key=='radio'">
						<radio-group class="radio-group" :name="'form'+idx">
							<label v-for="(item1,idx1) in item.val2" :key="item1.id" class="flex-y-center">
								<radio class="radio" :value="item1"/>{{item1}}
							</label>
						</radio-group>
					</block>
					<block v-if="item.key=='checkbox'">
						<checkbox-group :name="'form'+idx" class="checkbox-group">
							<label v-for="(item1,idx1) in item.val2" :key="item1.id" class="flex-y-center">
								<checkbox class="checkbox" :value="item1"/>{{item1}}
							</label>
						</checkbox-group>
					</block>
					<block v-if="item.key=='selector'">
						<picker class="picker" mode="selector" :name="'form'+idx" value="" :range="item.val2" @change="editorBindPickerChange" :data-idx="idx">
							<view v-if="editorFormdata[idx] || editorFormdata[idx]===0"> {{item.val2[editorFormdata[idx]]}}</view>
							<view v-else>请选择</view>
						</picker>
						<text class="iconfont iconjiantou" style="color:#999;font-weight:normal"></text>
					</block>
					<block v-if="item.key=='time'">
						<picker class="picker" mode="time" :name="'form'+idx" value="" :start="item.val2[0]" :end="item.val2[1]" :range="item.val2" @change="editorBindPickerChange" :data-idx="idx">
							<view v-if="editorFormdata[idx]">{{editorFormdata[idx]}}</view>
							<view v-else>请选择</view>
						</picker>
						<text class="iconfont iconjiantou" style="color:#999;font-weight:normal"></text>
					</block>
					<block v-if="item.key=='date'">
						<picker class="picker" mode="date" :name="'form'+idx" value="" :start="item.val2[0]" :end="item.val2[1]" :range="item.val2" @change="editorBindPickerChange" :data-idx="idx">
							<view v-if="editorFormdata[idx]">{{editorFormdata[idx]}}</view>
							<view v-else>请选择</view>
						</picker>
						<text class="iconfont iconjiantou" style="color:#999;font-weight:normal"></text>
					</block>
					<block v-if="item.key=='upload'">
						<input type="text" style="display:none" :name="'form'+idx" :value="editorFormdata[idx]"/>
						<view class="flex" style="flex-wrap:wrap;padding-top:20rpx">
							<view class="form-imgbox" v-if="editorFormdata[idx]">
								<view class="form-imgbox-img"><image class="image" :src="editorFormdata[idx]" @click="previewImage" :data-url="editorFormdata[idx]" mode="widthFix"/></view>
							</view>
							<view class="form-uploadbtn" :style="{background:'url('+pre_url+'/static/img/shaitu_icon.png) no-repeat 50rpx',backgroundSize:'80rpx 80rpx',backgroundColor:'#F3F3F3'}" @click="editorChooseImage" :data-idx="idx"></view>
						</view>
					</block>
				</view>
			</view>
		</view>

		<view style="width: 100%;height:calc(110rpx + env(safe-area-inset-bottom));"></view>
		<view class="footer flex notabbarbot">
			<view class="text1 flex1">总计：
				<text style="font-weight:bold;font-size:36rpx">￥{{totalprice}}</text>
			</view>
			<button class="op" form-type="submit" :style="{background:'linear-gradient(-90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'}">提交订单</button>
		</view>
		</form>

		<view v-if="pstimeDialogShow" class="popup__container">
			<view class="popup__overlay" @tap.stop="hidePstimeDialog"></view>
			<view class="popup__modal">
				<view class="popup__title">
					<text class="popup__title-text">请选择{{freightList[freightkey].pstype==1?'取货':'配送'}}时间</text>
					<image :src="pre_url+'/static/img/close.png'" class="popup__close" style="width:36rpx;height:36rpx" @tap.stop="hidePstimeDialog"/>
				</view>
				<view class="popup__content">
					<view class="pstime-item" v-for="(item, index) in freightList[freightkey].pstimeArr" :key="index" @tap="pstimeRadioChange" :data-index="index">
						<view class="flex1">{{item.title}}</view>
						<view class="radio" :style="freight_time==item.value ? 'background:'+t('color1')+';border:0' : ''"><image class="radio-img" :src="pre_url+'/static/img/checkd.png'"/></view>
					</view>
				</view>
			</view>
		</view>
    
    <uni-popup id="dialogExpress11" ref="dialogExpress11" type="dialog">
    	<view class="uni-popup-dialog">
    		<view class="uni-dialog-title">
    			<text class="uni-dialog-title-text">输入优惠码</text>
    		</view>
    		<view class="uni-dialog-content">
    			<view>
    				<view class="flex-y-center flex-x-center" style="margin:20rpx 20rpx;">
    					<view style="font-size:28rpx;color:#555">优惠码：</view>
    					<input type="text" placeholder="请输入优惠码" @input="inputCode" style="border: 1px #eee solid;padding: 10rpx;height:70rpx;border-radius:4px;flex:1;font-size:28rpx;"/>
    				</view>
    			</view>
    		</view>
    		<view class="uni-dialog-button-group">
    			<view class="uni-dialog-button" @click="dialogExpress11Close">
    				<text class="uni-dialog-button-text">取消</text>
    			</view>
    			<view class="uni-dialog-button uni-border-left" @click="confirmfahuo11">
    				<text class="uni-dialog-button-text uni-button-color">确定</text>
    			</view>
    		</view>
    	</view>
    </uni-popup>
  
	</block>
	<loading v-if="loading"></loading>
	<dp-tabbar :opt="opt"></dp-tabbar>
	<popmsg ref="popmsg"></popmsg>
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

			pre_url:app.globalData.pre_url,
			editorFormdata:[],
			test:'test',
		
			business:{},
      productList: [],
      freightList: [],

      address: [],
      needaddress: 1,
      linkman: '',
      tel: '',
      freightkey: 0,
      freight_price: 0,
      pstimetext: '',
      freight_time: '',
      usescore: 0,
      totalprice: '0.00',
      product_price: 0,

      coupon_money: 0,
      storedata: [],
      storeid: '',
      storename: '',
      latitude: '',
      longitude: '',
      isload: 0,
      leadermoney: 0,
      couponvisible: false,
      pstimeDialogShow: false,
      pstimeIndex: -1,
      product: "",
      guige: "",
      userinfo: "",
      buytype: "",
      scorebdkyf: "",
      totalnum: "",
      havetongcheng: "",
      weight: "",
      goodsnum: "",
      beizhu: "",
      couponkey: 0,
			storeshowall:false,
      trid:0,
      tour_code:'',
      tour_auth:false,
      tour_code:'请输入优惠码',
      tour_code_decprice:'',
      showemail:false,
      email:'',
      scan_trid:0
      
    };
  },

  onLoad: function (opt) {
		var that = this;
		var opt  = app.getopts(opt);
		if(opt && opt.trid){
		  that.trid = opt.trid;
		}else{
		  that.trid = app.globalData.trid
		}
		that.opt = opt;
		that.getdata();
  },
	onPullDownRefresh: function () {
		this.getdata();
	},
  methods: {
		getdata: function () {
			var that = this; //获取产品信息
			that.loading = true;
			app.get('ApiTour/buy', {proid: that.opt.proid,ggid: that.opt.ggid,num: that.opt.num,buytype: that.opt.buytype,trid: that.trid}, function (res) {
				that.loading = false;
				if (res.status == 0) {
					app.alert(res.msg, function(){
						app.goback()
					});
					return;
				}
				var product = res.product;
				var freightList = res.freightList;
				var userinfo = res.userinfo;

				that.product = product;
				that.freightList = freightList;
        
				that.userinfo = userinfo;

				that.address = res.address;

				that.totalnum = res.totalnum;
				that.havetongcheng = res.havetongcheng;
				that.linkman = res.linkman;
				that.tel = res.tel;
        that.showemail = res.showemail;

				var product_price = res.product_price;
				that.product_price = res.product_price;
        if(res.tour_auth){
          that.tour_auth = res.tour_auth;
        }
				that.calculatePrice();
				that.loaded();

				if (res.needLocation == 1) {
					app.getLocation(function (res) {
						var latitude = res.latitude;
						var longitude = res.longitude;
						for (var j in freightList) {
							if (freightList[j].pstype == 1) {
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
									freightList[j].storedata = storedata;
								}
							}
						}
						that.freightList = freightList;
					});
				}
			});
		},
    inputLinkman: function (e) {
      this.linkman = e.detail.value
    },
    inputTel: function (e) {
      this.tel = e.detail.value
    },
    //选择收货地址
    chooseAddress: function () {
      app.goto('/pagesB/address/address?fromPage=buy&type=' + (this.havetongcheng == 1 ? '1' : '0'));
    },
    //计算价格
    calculatePrice: function () {
      var that = this;
      var product_price = parseFloat(that.product_price); //+商品总价

      var address = that.address; //算运费
      var freightdata = that.freightList[that.freightkey];
      if (freightdata.pstype != 1 && freightdata.pstype != 3 && freightdata.pstype != 4) {
        var needaddress = 1;
      } else {
        var needaddress = 0;
      }
      that.needaddress = needaddress;
      var freight_price = freightdata.freight_price;

      var totalprice = product_price;
      if (totalprice < 0) totalprice = 0; //优惠券不抵扣运费

      var oldtotalprice = totalprice;

      totalprice = totalprice + freight_price-that.tour_code_decprice;

      if (totalprice < 0) totalprice = 0;
      freight_price = freight_price.toFixed(2);
      totalprice = totalprice.toFixed(2);
      that.totalprice = totalprice
			that.freight_price = freight_price;
    },
    //积分抵扣
    scoredk: function (e) {
      var usescore = e.detail.value[0];
      if (!usescore) usescore = 0;
      this.usescore = usescore;
      this.calculatePrice();
    },
    changeFreight: function (e) {
      var that = this;
      var index = e.currentTarget.dataset.index;
			this.freightkey = index;
			that.calculatePrice();
    },
    choosePstime: function () {
      var that = this;
      var freightkey = this.freightkey;
      var freightList = this.freightList;
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
      if (itemlist.length > 6) {
        that.pstimeDialogShow = true;
        that.pstimeIndex = -1;
      } else {
        uni.showActionSheet({
          itemList: itemlist,
          success: function (res) {
						if(res.tapIndex >= 0){
							var choosepstime = pstimeArr[res.tapIndex];
							that.pstimetext = choosepstime.title;
							that.freight_time = choosepstime.value;
						}
          }
        });
      }
    },
    pstimeRadioChange: function (e) {
      var pstimeIndex = e.currentTarget.dataset.index;
			var freightkey = this.freightkey;
      var freightList = this.freightList;
      var freight = freightList[freightkey];
      var pstimeArr = freightList[freightkey].pstimeArr;
      var choosepstime = pstimeArr[pstimeIndex];
      this.pstimetext = choosepstime.title;
			this.freight_time = choosepstime.value;
      this.pstimeDialogShow = false;
    },
    hidePstimeDialog: function () {
      this.pstimeDialogShow = false
    },
    choosestore: function (e) {
      var storekey = e.currentTarget.dataset.index;
			var freightkey = this.freightkey
			var freightList = this.freightList
			freightList[freightkey].storekey = storekey
      this.freightList = freightList;
    },
    //提交并支付
    topay: function (e) {
      var that = this;
      var buytype = this.buytype;
      var freightkey = this.freightkey;
      var freightid = this.freightList[freightkey].id;
      var prodata = this.opt.prodata;
      var addressid = this.address.id;
      var linkman = this.linkman;
      var tel = this.tel;
			if(this.freightList[freightkey].pstype==1){
				var storekey = this.freightList[freightkey].storekey
				var storeid = this.freightList[freightkey].storedata[storekey].id;
			}else{
				var storeid = 0;
			}
      var freight_time = that.freight_time;

      var needaddress = that.needaddress;
      if (needaddress == 0) addressid = 0;

      if (needaddress == 1 && addressid == undefined) {
        app.error('请选择收货地址');
        return;
      }

      if (this.freightList[freightkey].pstimeset == 1 && freight_time == '') {
        app.error('请选择' + (this.freightList[freightkey].pstype == 0 ? '配送' : '提货') + '时间');
        return;
      }

			var formdataSet = this.freightList[freightkey].formdata;
      var formdata = e.detail.value;
			var newformdata = {};
			for (var i = 0; i < formdataSet.length;i++){
				if (formdataSet[i].val3 == 1 && (formdata['form' + i] === '' || formdata['form' + i] === undefined || formdata['form' + i].length==0)){
						app.alert(formdataSet[i].val1+' 必填');return;
				}
				if (formdataSet[i].key == 'selector') {
						formdata['form' + i] = formdataSet[i].val2[formdata['form' + i]]
				}
				newformdata['form'+i] = formdata['form' + i];
			}

			app.showLoading('提交中');
      app.post('ApiTour/createOrder', {
        proid: that.opt.proid,
        num: 1,
        storeid: storeid,
        freightid: freightid,
        freight_time: freight_time,
        addressid: addressid,
        linkman: linkman,
        tel: tel,
				formdata: newformdata,
        trid:that.trid,
        tour_code:that.tour_code,
        email:that.email,
        scan_trid:that.scan_trid
      }, function (data) {
				app.showLoading(false);
        if (data.status == 0) {
          app.error(data.msg);
          return;
        }
        app.goto('/pagesExt/pay/pay?id=' + data.payorderid);
      });
    },
    showCouponList: function () {
      this.couponvisible = true;
    },
    handleClickMask: function () {
      this.couponvisible = false;
    },
		openMendian: function(e) {
			var freightkey = e.currentTarget.dataset.freightkey;
			var storekey = e.currentTarget.dataset.storekey;
			var frightinfo = this.freightList[freightkey]
			var storeinfo = frightinfo.storedata[storekey];
			console.log(storeinfo)
			app.goto('/pages/shop/mendian?id=' + storeinfo.id);
		},
		openLocation:function(e){
			var freightkey = e.currentTarget.dataset.freightkey;
			var storekey = e.currentTarget.dataset.storekey;
			var frightinfo = this.freightList[freightkey]
			var storeinfo = frightinfo.storedata[storekey];
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
		editorChooseImage: function (e) {
			var that = this;
			var idx = e.currentTarget.dataset.idx;
			var tplindex = e.currentTarget.dataset.tplindex;
			var editorFormdata = this.editorFormdata;
			if(!editorFormdata) editorFormdata = [];
			app.chooseImage(function(data){
				editorFormdata[idx] = data[0];
				console.log(editorFormdata)
				that.editorFormdata = editorFormdata
				that.test = Math.random();
			})
		},
		editorBindPickerChange:function(e){
			var idx = e.currentTarget.dataset.idx;
			var tplindex = e.currentTarget.dataset.tplindex;
			var val = e.detail.value;
			var editorFormdata = this.editorFormdata;
			if(!editorFormdata) editorFormdata = [];
			editorFormdata[idx] = val;
			console.log(editorFormdata)
			this.editorFormdata = editorFormdata
			this.test = Math.random();
		},
		doStoreShowAll:function(){
			this.storeshowall = true;
		},
    getDecprice:function(){
      this.$refs.dialogExpress11.open();
    },
    dialogExpress11Close:function(){
    	this.$refs.dialogExpress11.close();
    },
    inputCode:function(e){
      var tour_code = e.detail.value;
      if(tour_code){
        this.tour_code = e.detail.value;
      }else{
        this.tour_code = "请输入优惠码";
      }
    	
    },
    confirmfahuo11:function(){
    	var that = this
    	  app.showLoading('提交中');
    	  app.post('ApiTour/get_decprice', {proid:that.opt.proid,trid:that.trid,tour_code:that.tour_code}, function (res) {
    	  	app.showLoading(false);
    	  	if (res.status == 1) {
    	  		that.tour_code_decprice = res.tour_code_decprice;
            that.dialogExpress11Close();
            that.calculatePrice();
    	  	}else{
            that.tour_code = '请输入优惠码';
            that.tour_code_decprice = '';
    	  		app.alert(res.msg);
    	  		return;
    	  	}
    	  });
    },
    inputEmail:function(e){
      this.email = e.detail.value;
    },
    saoyisao: function (d) {
      var that = this;
    	if(app.globalData.platform == 'h5'){
    		app.alert('请使用微信扫一扫功能');return;
    	}else if(app.globalData.platform == 'mp'){
    		var jweixin = require('jweixin-module');
    		jweixin.ready(function () {   //需在用户可能点击分享按钮前就先调用
    			jweixin.scanQRCode({
    				needResult: 1, // 默认为0，扫描结果由微信处理，1则直接返回扫描结果，
    				scanType: ["qrCode","barCode"], // 可以指定扫二维码还是一维码，默认二者都有
    				success: function (res) {
              console.log(res)
    					var content = res.resultStr; // 当needResult 为 1 时，扫码返回的结果
              if(!content){
                app.alert('请扫描正确的二维码');
                return;
              }
    
              var pos = content.indexOf('=');
              if(pos == -1){
                app.alert('请扫描正确的二维码');
                return;
              }
              var scan_trid = content.split('=')[1]-0;
              if(isNaN(scan_trid) || scan_trid<=0 ){
                app.alert('请扫描正确的二维码');
                return;
              };
              app.showLoading('提交中');
              app.post('ApiTour/get_decprice', {proid:that.opt.proid,trid:that.trid,scan_trid:scan_trid,tour_code:that.tour_code}, function (res) {
              	app.showLoading(false);
              	if (res.status == 1) {
                  that.scan_trid = scan_trid;
                  that.tour_code = res.tour_code;
              		that.tour_code_decprice = res.tour_code_decprice;
                  that.dialogExpress11Close();
                  that.calculatePrice();
              	}else{
              		app.alert(res.msg);
              		return;
              	}
              });
    				}
    			});
    		});
    	}else if(app.globalData.platform == 'wx'){
    		uni.scanCode({
    			success: function (res) {
            console.log(res)
            var content = res.path?res.path:'';
            if(!content){
              app.alert('请扫描正确的二维码');
              return;
            }

            var pos_dh = -1;
            var pos_zy = -1;
            
            var pos_dh = content.indexOf('=');
            if(pos_dh == -1){
              //查询是否被转义了
              var pos_zy = content.split("%3D");
              if(pos_zy == -1){
                  app.alert('请扫描正确的二维码');
                  return;
              }
            }
            
            var scan_trid = 0;
            if(pos_dh>=0){
              //查询下划线
              var pos_xh = content.indexOf('_');
              if(pos_xh >=0){
                var items = content.split("_");
              }else{
                var items = content.split("=");
              }
              
              if (items) {
                var len   = items.length;
                scan_trid = items[len - 1];
              }
            }else{
              //查询下划线
              var pos_xh = content.indexOf('_');
              if(pos_xh >=0){
                var items = content.split("_");
              }else{
                var items = content.split("%3D");
              }
              
              if (items) {
                var len   = items.length;
                scan_trid = items[len - 1];
              }
            }

            if(isNaN(scan_trid) || scan_trid<=0){
              app.alert('请扫描正确的二维码');
              return;
            };

            app.showLoading('提交中');
            app.post('ApiTour/get_decprice', {proid:that.opt.proid,trid:that.trid,scan_trid:scan_trid,tour_code:that.tour_code}, function (res) {
            	app.showLoading(false);
            	if (res.status == 1) {
                that.scan_trid = scan_trid;
                that.tour_code = res.tour_code;
            		that.tour_code_decprice = res.tour_code_decprice;
                that.dialogExpress11Close();
                that.calculatePrice();
            	}else{
            		app.alert(res.msg);
            		return;
            	}
            });
            
    			}
    		});
    	}else{
    		uni.scanCode({
    			success: function (res) {
            console.log(res)
            var content = res.result;
            if(!content){
              var content = res.path?res.path:'';
              if(!content){
                app.alert('请扫描正确的二维码');
                return;
              }
            }

            var pos_dh = -1;
            var pos_zy = -1;
            
            var pos_dh = content.indexOf('=');
            if(pos_dh == -1){
              
              //查询是否被转义了
              var pos_zy = content.split("%3D");
              if(pos_zy == -1){

                //查询参数数值
                var content = res.path?res.path:'';
                if(!content){
                  app.alert('请扫描正确的二维码');
                  return;
                }

                var pos_dh = content.indexOf('=');
                if(pos_dh == -1){
                  
                  //查询是否被转义了
                  var pos_zy = content.split("%3D");
                  if(pos_zy == -1){
                    app.alert('请扫描正确的二维码');
                    return;
                  }

                }
                
              }
              
            }
            
            var scan_trid = 0;
            if(pos_dh>=0){
              //查询下划线
              var pos_xh = content.indexOf('_');
              if(pos_xh >=0){
                var items = content.split("_");
              }else{
                var items = content.split("=");
              }
              
              if (items) {
                var len   = items.length;
                scan_trid = items[len - 1];
              }
            }else{
              //查询下划线
              var pos_xh = content.indexOf('_');
              if(pos_xh >=0){
                var items = content.split("_");
              }else{
                var items = content.split("%3D");
              }
              
              if (items) {
                var len   = items.length;
                scan_trid = items[len - 1];
              }
            }

            if(isNaN(scan_trid) || scan_trid<=0){
              app.alert('请扫描正确的二维码');
              return;
            };

            app.showLoading('提交中');
            app.post('ApiTour/get_decprice', {proid:that.opt.proid,trid:that.trid,scan_trid:scan_trid,tour_code:that.tour_code}, function (res) {
            	app.showLoading(false);
            	if (res.status == 1) {
                that.scan_trid = scan_trid;
                that.tour_code = res.tour_code;
            		that.tour_code_decprice = res.tour_code_decprice;
                that.dialogExpress11Close();
                that.calculatePrice();
            	}else{
            		app.alert(res.msg);
            		return;
            	}
            });
            
    			}
    		});
    	}
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
.product .img{ width:140rpx;height:140rpx}
.collage_icon{ color:#fe7203;border:1px solid #feccaa;display:flex;align-items:center;font-size:20rpx;padding:0 6rpx;margin-left:6rpx}

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

.footer {width: 96%;background: #fff;margin-top: 5px;position: fixed;left: 0px;bottom: 0px;padding: 0 2%;display: flex;align-items: center;z-index: 8;box-sizing:content-box}
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


.form-item {width: 100%;padding: 16rpx 0;background: #fff;display: flex;align-items: center;justify-content:space-between}
.form-item .label {color: #333;width: 200rpx;flex-shrink:0}
.form-item .radio{transform:scale(.7);}
.form-item .checkbox{transform:scale(.7);}
.form-item .input {border:0px solid #eee;height: 70rpx;padding-left: 10rpx;text-align: right;flex:1}
.form-item .textarea{height:140rpx;line-height:40rpx;overflow: hidden;flex:1;border:1px solid #eee;border-radius:2px;padding:8rpx}
.form-item .radio-group{display:flex;flex-wrap:wrap;justify-content:flex-end}
.form-item .radio{height: 70rpx;line-height: 70rpx;display:flex;align-items:center}
.form-item .radio2{display:flex;align-items:center;}
.form-item .radio .myradio{margin-right:10rpx;display:inline-block;border:1px solid #aaa;background:#fff;height:32rpx;width:32rpx;border-radius:50%}
.form-item .checkbox-group{display:flex;flex-wrap:wrap;justify-content:flex-end}
.form-item .checkbox{height: 70rpx;line-height: 70rpx;display:flex;align-items:center}
.form-item .checkbox2{display:flex;align-items:center;height: 40rpx;line-height: 40rpx;}
.form-item .checkbox .mycheckbox{margin-right:10rpx;display:inline-block;border:1px solid #aaa;background:#fff;height:32rpx;width:32rpx;border-radius:2px}
.form-item .picker{height: 70rpx;line-height:70rpx;flex:1;text-align:right}

.form-imgbox{margin-right:16rpx;margin-bottom:10rpx;font-size:24rpx;position: relative;}
.form-imgbox-close{position: absolute;display: block;width:32rpx;height:32rpx;right:-16rpx;top:-16rpx;color:#999;font-size:32rpx;background:#fff}
.form-imgbox-close .image{width:100%;height:100%}
.form-imgbox-img{display: block;width:180rpx;height:180rpx;padding:2px;border: #d3d3d3 1px solid;background-color: #f6f6f6;overflow:hidden}
.form-imgbox-img>.image{max-width:100%;}
.form-imgbox-repeat{position: absolute;display: block;width:32rpx;height:32rpx;line-height:28rpx;right: 2px;bottom:2px;color:#999;font-size:30rpx;background:#fff}
.form-uploadbtn{position:relative;height:180rpx;width:180rpx}

.storeviewmore{width:100%;text-align:center;color:#889;height:40rpx;line-height:40rpx;margin-top:10rpx}

.uni-popup-dialog {width: 300px;border-radius: 5px;background-color: #fff;}
.uni-dialog-title {display: flex;flex-direction: row;justify-content: center;padding-top: 15px;padding-bottom: 5px;}
.uni-dialog-title-text {font-size: 16px;font-weight: 500;}
.uni-dialog-content {display: flex;flex-direction: row;justify-content: center;align-items: center;padding: 5px 15px 15px 15px;}
.uni-dialog-content-text {font-size: 14px;color: #6e6e6e;}
.uni-dialog-button-group {display: flex;flex-direction: row;border-top-color: #f5f5f5;border-top-style: solid;border-top-width: 1px;}
.uni-dialog-button {display: flex;flex: 1;flex-direction: row;justify-content: center;align-items: center;height: 45px;}
.uni-border-left {border-left-color: #f0f0f0;border-left-style: solid;border-left-width: 1px;}
.uni-dialog-button-text {font-size: 14px;}
.uni-button-color {color: #007aff;}
</style>