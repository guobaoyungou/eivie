<template>
<view class="container">
	<block v-if="isload">
		<form @submit="topay">
		<view  class="address-add" v-if="product.product_thali_school == 1">
			<view class="linkitem">
				<text class="f1" style="margin: 0">学生姓名：</text>
				<input type="text" class="input" :value="address.product_thali_student_name" disabled placeholder-style="color:#626262;font-size:28rpx;"/>
			</view>
			<view class="linkitem">
				<text class="f1" style="margin: 0">学校信息：</text>
				<input type="text" class="input" :value="address.product_thali_school" disabled placeholder-style="color:#626262;font-size:28rpx;"/>
			</view>
		</view>
		<view  class="address-add flex-y-center" @tap="goto" :data-url="'/pagesB/address/address?fromPage=product_thali_buy&type=' + (havetongcheng==1?'1':'0')">
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
          <view class="f2" style="font-weight:bold;color:#111111;font-size:30rpx;padding-top: 10px;overflow: hidden;text-overflow: ellipsis;white-space:nowrap;">{{product.title}}</view>
					<view class="item flex"  v-for="(item,index) in productlist">
						<view class="img">
							<image class="img" v-if="item.pic" :src="item.pic"></image>
							<image class="img" v-else :src="item.pic"></image>
						</view>
						<view class="info flex1">
							<view class="f1" style="height: 35px;overflow: hidden;text-overflow: ellipsis;">{{item.name}}</view>
              <view class="f1"></view>
              <view class="f1"></view>
              <view class="f2" style="color: red">￥{{item.sell_price}} &nbsp;x&nbsp; {{item.num}}</view>
						</view>
					</view>
				</view>
        <view class="form-item" v-if="freightList" v-for="(item,idx) in freightList[freightkey].formdata" :key="item.id">
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
                <view class="layui-imgbox-close" style="z-index: 2;" @tap="removeimg" :data-idx="idx"><image style="display:block" :src="pre_url+'/static/img/ico-del.png'"></image></view>
                <view class="form-imgbox-img"><image class="image" :src="editorFormdata[idx]" @click="previewImage" :data-url="editorFormdata[idx]" mode="aspectFit"/></view>
              </view>
              <view v-else class="form-uploadbtn" :style="{background:'url('+pre_url+'/static/img/shaitu_icon.png) no-repeat 50rpx',backgroundSize:'80rpx 80rpx',backgroundColor:'#F3F3F3'}" @click="editorChooseImage" :data-idx="idx"></view>
            </view>
          </block>
        </view>
			</view>
		</view>
		<view style="width: 100%;height:calc(110rpx + env(safe-area-inset-bottom));"></view>
		<view class="footer flex notabbarbot">
			<view class="text1 flex1">总计：
        <text style="font-weight:bold;font-size:36rpx">￥{{totalprice}}<text v-if="product.deliver_type==1" style="font-size: 12px">(运费:{{product.freight}})</text></text>
			</view>
			<button class="op" form-type="submit" :style="{background:'linear-gradient(-90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'}">提交订单</button>
		</view>
		</form>
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
      couponList: [],
      couponrid: 0,
      coupontype: 1,
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
      leveldk_money: 0,
      scoredk_money: 0,
      coupon_money: 0,
      storedata: [],
      storeid: '',
      storename: '',
      latitude: '',
      longitude: '',
      leadermoney: 0,
      couponvisible: false,
      pstimeDialogShow: false,
      pstimeIndex: -1,
      product: "",
      productlist: "",
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
      guige_data:[],
      xdproids:'',
      guige_num:'',
    };
  },

  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.getdata();
  },
	onPullDownRefresh: function () {
		this.getdata();
	},
  methods: {
		getdata: function () {
			var that = this; //获取产品信息
			that.loading = true;
			app.get('ApiProductThali/buy', {id: that.opt.id,xzproids: that.opt.xzproids,guige_num:that.opt.guige_num}, function (res) {
				that.loading = false;
				that.isload = true;
				if (res.status == 0) {
					app.alert(res.msg, function(){
						app.goback()
					});
					return;
				}
				var product = res.product;
				var productlist = res.productlist;
				var freightList = res.freightList;
				that.product = product;
				that.productlist = productlist;
				that.freightList = freightList;
				that.totalprice = res.totalprice;
        that.address = res.address;
        that.xdproids = res.xdproids;
				that.totalnum = res.totalnum;
				that.havetongcheng = res.havetongcheng;
				that.linkman = res.linkman;
				that.tel = res.tel;
				that.loaded();
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
      var leadermoney = parseFloat(that.leadermoney); //-团长优惠
      var leveldk_money = parseFloat(that.leveldk_money); //-会员折扣
      var coupon_money = parseFloat(that.coupon_money); //-优惠券抵扣 
      var address = that.address; //算运费
      var freightdata = that.freightList[that.freightkey];
      if (freightdata.pstype != 1 && freightdata.pstype != 3 && freightdata.pstype != 4) {
        var needaddress = 1;
      } else {
        var needaddress = 0;
      }
      that.needaddress = needaddress;
      var freight_price = freightdata.freight_price;
      if (that.coupontype == 4) {
        freight_price = 0;
        coupon_money = 0;
      }
      var totalprice = product_price - leadermoney - leveldk_money - coupon_money;
      if (totalprice < 0) totalprice = 0; //优惠券不抵扣运费
      totalprice += freight_price
      
      var oldtotalprice = totalprice;
      var scoredk_money = 0;
      var scoredk_money2= 0;
      if (that.usescore) {
        scoredk_money2 = parseFloat(that.scoredk_money); //-积分抵扣
        if (that.scorebdkyf == '1' && scoredk_money2 > 0 ) {
          //积分不抵扣运费
          oldtotalprice -= freight_price;
          if(scoredk_money2 > oldtotalprice) scoredk_money2 = oldtotalprice;
        }else{
          if(scoredk_money2 > oldtotalprice) scoredk_money2 = oldtotalprice;
        }
      }

      var scoredkmaxpercent = parseFloat(that.userinfo.scoredkmaxpercent); //最大抵扣比例
      if (scoredk_money2 > 0 && scoredkmaxpercent > 0 && scoredkmaxpercent <= 100 ) {
        scoredk_money = oldtotalprice * scoredkmaxpercent * 0.01;
      }
      if(scoredk_money>scoredk_money2){
        scoredk_money = scoredk_money2;
      }
      totalprice = totalprice - scoredk_money;

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
    chooseCoupon: function (e) {
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
        this.couponkey = couponkey;
        this.couponrid = couponrid;
        this.coupontype = coupontype;
        this.coupon_money = coupon_money;
        this.couponvisible = false;
      }
      this.calculatePrice();
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
      var addressid = this.address.id;
      var linkman = this.linkman;
      var tel = this.tel;
      var usescore = this.usescore;
      var couponrid = this.couponrid;
      var freightkey = this.freightkey;
      var needaddress = that.needaddress;
      if (needaddress == 0) addressid = 0;
      var freightid = this.freightList[freightkey].id;

      if (needaddress == 1 && addressid == undefined) {
        app.error('请选择收货地址');
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
      app.post('ApiProductThali/createOrder', {
        proid: that.opt.id,
        guige_num: that.opt.guige_num,
        num_total: that.opt.num_total,
        couponrid: couponrid,
        addressid: addressid,
        usescore: usescore,
        linkman: linkman,
        tel: tel,
        xdproids: that.xdproids,
        formdata: newformdata,
        freightid: freightid
      }, function (data) {
			app.showLoading(false);
        if (data.status == 0) {
          app.error(data.msg);
          return;
        }
        app.goto('/pagesExt/pay/pay?id=' + data.payorderid,'redirectTo');
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
		removeimg:function(e){
			var that = this;
			var idx = e.currentTarget.dataset.idx;
			var pics = that.editorFormdata
			pics.splice(idx,1);
			that.editorFormdata = pics;
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
.linkitem .input{height:50rpx;color:#222222;font-weight:bold;font-size:28rpx;flex:1}

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
.form-imgbox-img>.image{width:100%;height:100%}
.form-imgbox-repeat{position: absolute;display: block;width:32rpx;height:32rpx;line-height:28rpx;right: 2px;bottom:2px;color:#999;font-size:30rpx;background:#fff}
.form-uploadbtn{position:relative;height:180rpx;width:180rpx;margin-right: 16rpx;margin-bottom:10rpx;}

.storeviewmore{width:100%;text-align:center;color:#889;height:40rpx;line-height:40rpx;margin-top:10rpx}
</style>