<template>
	<view>
		<!-- rgba(${t('color1rgb')},0.8) -->
		<view class="banner-view" :style="{backgroundImage:`url(${pre_url}/static/img/watherbg.png)`,backgroundColor:`#89c3f6`}"></view>
		<view class="position-view">
			<view class="top-position-view flex flex-bt">
				<view class='flex flex-y-center'>
					<view class="add-icon">
						<image :src="pre_url+'/static/img/water/address_icon.png'"></image>
					</view>
					<view class="address-text">{{locationCache.street?locationCache.street:'北京市'}}</view>
<!--					<view class="xiala-icon flex flex-xy-center">-->
<!--						<image :src="pre_url+'/static/img/water/jiantou_xiala.png'"></image>-->
<!--					</view>-->
				</view>
				<view class='flex flex-y-center nearby-water' @tap="goto" :data-url="'/pagesD/water/stationList?bid='+bid" >
					<view>附近水站</view>
					<image class="more-icon" :src="pre_url+'/static/img/water/jiantou-bai.png'" style="margin-top: 3rpx;"></image>
				</view>
			</view>
			<view class="pos-options-view water-volume">
				<view class="volume-top-view flex flex-bt flex-y-center">
					<view class="flex-col">
						<view class="title-size-1">打水量</view>
						<view class="title-size-2">先选择打水量，再点击提交订单</view>
					</view>
					<view class="flex-y-center wenti-text" @tap="showLinkChange" :data-phone="linktel"><image class="wenti-icon" :src="pre_url+'/static/img/water/wenti.png'"></image>故障报修</view>
				</view>
				<view class="choose-volume-view flex" style="flex-wrap: wrap">
					<view v-for="option in waterOptions" :key="option.id" :class="[volumeIndex == option.id ? 'options-volume-active':'','flex-col flex-xy-center options-volume']" style="" @click='changeVolume(option.id)'>
						<view class='volume-num'>{{ option.dashui_sheng }}</view>
						<view class='price-text'>￥{{ option.dashui_amount }}</view>
					</view>
				</view>
			</view>
			<view class="pos-options-view coupon-view flex-bt" @tap="showCouponList">
				<view class="options-title">{{t('优惠券')}}</view>
        <block v-if="(coupons).length>0">
          <view class="couponname" :style="{background:t('color1')}" v-for="(item,index) in coupons">{{item.couponname}}</view>
<!--          <image class="more-icon" :src="pre_url+'/static/img/water/jiantou.png'" style="width: 24rpx;height: 24rpx;margin-left: 10rpx;"></image>-->
        </block>
        <block v-else>
          <view class="coupon-choose-view flex-y-center" v-if="couponNum > 0" >
            {{couponNum}}张可用<image class="more-icon" :src="pre_url+'/static/img/water/jiantou.png'" style="width: 24rpx;height: 24rpx;margin-left: 10rpx;"></image>
          </view>
          <view class="coupon-choose-view flex-y-center" v-else style="color:#999">无可用{{t('优惠券')}}</view>
        </block>

			</view>
			<view class="pos-options-view coupon-view flex-bt" v-if="userinfo.score2money>0">
        <checkbox-group @change="scoredk" class="flex" style="width:100%">
				<view class="options-title" style="margin-top: 6rpx">{{t('积分')}}抵扣</view>
				<view class="deduct-view flex flex-bt">
					<view class="flex flex-y-center" >
            <view class="deduct-assistance">共</view>
            <view class="deduct-price">{{userinfo.score}}</view>
            <view class="deduct-assistance">，可抵</view>
            <view class="deduct-price">{{userinfo.scoredk_money}}</view>
            <view class="deduct-assistance">元</view>
            <view class="deduct-assistance" @click="showExplain()"><image  class="title-icon" :src="`${pre_url}/static/img/admin/jieshiicon.png`"></image></view>
					</view>
					<view style="font-weight:normal">
						<checkbox value="1" style="transform:scale(0.8)"  :checked="usescore" ></checkbox>
					</view>
				</view>
        </checkbox-group>
			</view>
      <view v-if="pic_recharge" class="pos-options-view flex-col" style="margin-top: 20rpx;height: 127px;padding: 0" @tap="goto" :data-url="'/pagesExt/money/recharge'">
        <image :src="pre_url+'/static/img/water/recharge.png'" style="width: 100%"></image>
      </view>
			<view class="pos-options-view flex-col" style="margin-top: 20rpx;">
				<view class="options-title">温馨提示</view>
				<view class="parse-view">
					<parse :content="desc" ></parse>
				</view>
			</view>
		</view>
		<view style="width: 100%;height: calc(30rpx + env(safe-area-inset-bottom));"></view>
		<view class="bottom-but flex-bt" >
			<view class="left-view flex-col">
				<view class="flex flex-y-center">
					<view style="font-size: 24rpx;padding-right: 5rpx;">总价:</view>
					<view class="flex flex-y-center" style="color: #0074FE;font-weight: bold;">
						<view style="font-size: 26rpx;">￥</view>
						<view style="font-size: 36rpx;padding: 0rpx 5rpx;">{{show_price}}</view>
					</view>
				</view>
				<view class="discount-num">共优惠{{youhui}}元</view>
			</view>
			<view class="right-but flex flex-y-center " @click='topay'>
				提交订单
			</view>
		</view>
    <view class="posterDialog linkDialog" v-if="showLinkStatus">
      <view class="main">
        <view class="close" @tap="showLinkChange"><image class="img" :src="pre_url+'/static/img/close.png'"/></view>
        <view class="content">
          <view class="row">
            <view class="f1">联系电话</view>
            <view v-if="linktel" class="f2 flex-y-center flex-x-bottom" style="width: 100%;max-width: 470rpx;" @tap="goto" :data-url="'tel::'+linktel" :style="{color:t('color1')}">
              {{linktel}}
              <image :src="pre_url+'/static/img/copy.png'" class="copyicon" @tap.stop="copy" :data-text="linktel" v-if="linktel"></image>
            </view>
            <view v-else class="f2 flex-y-center flex-x-bottom" style="width: 100%;max-width: 470rpx;" >暂无联系电话</view>
          </view>
        </view>
      </view>
    </view>
    <view v-if="couponvisible" class="popup__container">
      <view class="popup__overlay" @tap.stop="handleClickMask"></view>
      <view class="popup__modal coupon-modal">
        <view class="popup__title">
          <text class="popup__title-text">请选择{{t('优惠券')}}</text>
          <image :src="pre_url+'/static/img/close.png'" class="popup__close" style="width:36rpx;height:36rpx"
                 @tap.stop="handleClickMask" />
        </view>
        <view class="popup__content">
          <couponlist :couponlist="couponList" :choosecoupon="true"
                      :selectedrids="couponrids" :bid="bid" @chooseCoupon="chooseCoupon">
          </couponlist>
        </view>
      </view>
    </view>
    <loading v-if="loading"></loading>
    <dp-tabbar :opt="opt"></dp-tabbar>
    <popmsg ref="popmsg"></popmsg>
    <wxxieyi></wxxieyi>
	</view>
</template>

<script>
	var app = getApp();
	export default{
		data(){
			return{

				volumeIndex:0,
        opt: {},
        loading: false,
        isload: false,
        menuindex: -1,
        platform: app.globalData.platform,
        pre_url: app.globalData.pre_url,
        selectedOption: null,
        waterOptions: [
        ],
        water_happyti_device_id:0,
        outlet_num:1,
        device_outlet: {},
        desc: '',
        couponNum: 0,
        couponList: [],
        couponrids: [],
        coupon_money: 0,
        coupons: 0,
        linktel: '',
        couponname: '',
        userinfo: {},
        usescore: 0,
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
          street:'',
          showlevel:2
        },
        latitude:'',
        longitude:'',
        couponvisible: false,
        bid: 0,
        totalprice: 0,
        youhui: 0,
        showLinkStatus: false,
        show_price:0,
        pic_recharge:0
			}
		},
    onLoad: function(opt) {
      this.opt = app.getopts(opt);
      this.water_happyti_device_id = this.opt.d;
      this.outlet_num = this.opt.o;

      if(!this.water_happyti_device_id){
        uni.showModal({
          title: '提示',
          showCancel:false,
          //content: '缺少设备编号参数',
          content: '请求已过期，请重新扫码',
          success: function (res) {
            if (res.confirm) {
              uni.navigateBack({
                delta:1,
                fail() {
                  uni.switchTab({
                    url:'/pages/index/index'
                  })
                }
              })
            }
          }
        });
        return;
      }
      if(!this.outlet_num){
        uni.showModal({
          title: '提示',
          showCancel:false,
          //content: '缺少出水口参数',
          content: '请求已过期，请重新扫码!',
          success: function (res) {
            if (res.confirm) {
              uni.navigateBack({
                delta:1,
                fail() {
                  uni.switchTab({
                    url:'/pages/index/index'
                  })
                }
              })
            }
          }
        });
        return;
      }

      let that = this;
      that.checkLocation();
      this.getdata();
    },
    onPullDownRefresh: function() {
      this.getdata();
    },
		methods:{
      checkLocation(){
        var that = this
        var locationCache = app.getLocationCache();
        // #ifdef H5
        if(locationCache.address){
          that.locationCache.address = locationCache.address;
        }
        // #endif
        var loc_area_type = 0;
        var loc_range_type = 0;
        var loc_range = 10;
        app.getLocation(function(res) {
          that.latitude = res.latitude;
          that.longitude = res.longitude;
          app.post('ApiAddress/getAreaByLocation', {latitude:that.latitude,longitude:that.longitude}, function(res) {
            if(res.status==1){
              that.locationCache.loc_area_type = loc_area_type
              that.locationCache.loc_range_type = loc_range_type
              that.locationCache.loc_range = loc_range
              that.locationCache.latitude = that.latitude
              that.locationCache.longitude = that.longitude
              that.locationCache.street = res.street
              // that.locationCache.showlevel = that.showlevel
              if(loc_area_type==0){
                if(that.showlevel==2){
                  that.locationCache.address = res.city
                  that.locationCache.street = res.street
                  that.locationCache.area = res.province+','+res.city
                  if(that.locationCache.address == null){
                    that.locationCache.address = '北京市';
                  }
                }else{
                  that.locationCache.address = res.district
                  that.locationCache.area = res.province+','+res.city+','+res.district
                  that.locationCache.street = res.street
                }
                that.area = that.locationCache.area
                that.curent_address = that.locationCache.address
                app.setLocationCacheData(that.locationCache,that.cacheExpireTime)
                that.locationCache.street = res.street
              }else if(loc_area_type==1){
                that.locationCache.address = res.landmark
                that.locationCache.area = res.province+','+res.city+','+res.district
                that.area = that.locationCache.area
                that.curent_address = that.locationCache.address
                app.setLocationCacheData(that.locationCache,that.cacheExpireTime)
                that.locationCache.street = res.street
              }else{
                return;
              }
            }
          })
        },function(res){
          that.locationCache.address = '北京市';
          that.locationCache.street = '北京市';
        })
      },
			changeVolume(index){
				this.volumeIndex = index;
        const selected = this.waterOptions.find(option => option.id === this.volumeIndex);
        this.show_price = selected.dashui_amount;
        this.totalprice = selected.dashui_amount;
        this.calculatePrice();
			},
      getdata: function() {
        var that = this;
        that.loading = true;
        app.get('ApiWaterHappyti/index', {water_happyti_device_id:this.water_happyti_device_id,outlet_num:this.outlet_num}, function(res) {
          that.loading = false;
          that.isload = true;
          if (res.status == 0) {
            app.error(res.msg);
            return;
          }
          that.waterOptions = res.price_combo;
          that.device_outlet = res.device_outlet;
          that.couponList = res.couponList;
          that.couponrids = res.couponrids;
          that.couponNum = res.couponNum;
          that.desc = res.desc;
          that.linktel = res.linktel;
          that.userinfo = res.userinfo;
          that.pic_recharge = res.pic_recharge;
          that.bid = res.bid;

          that.loaded();
        });
      },
      //提交并支付
      topay: function (e) {
        if (!this.volumeIndex) {
          uni.showToast({
            title: '请选择打水量',
            icon: 'none'
          });
          return;
        }

        const selected = this.waterOptions.find(option => option.id === this.volumeIndex);

        console.log(selected.dashui_amount);

        app.showLoading('提交中');

        app.post('ApiWaterHappyti/createOrder', {
          water_happyti_device_id: this.water_happyti_device_id,
          outlet_num: this.outlet_num,
          dashui_amount: selected.dashui_amount,
          couponrid: this.couponrid || 0,
          usescore: this.usescore || 0
        }, function (data) {
          app.showLoading(false);
          if (data.status == 0) {
            app.error(data.msg);
            return;
          }
          app.goto('/pagesExt/pay/pay?id=' + data.payorderid,'redirectTo');
        });
      },
      phone:function(e) {
        var phone = e.currentTarget.dataset.phone;
        uni.makePhoneCall({
          phoneNumber: phone,
          fail: function () {
          }
        });

      },

      //积分抵扣
      scoredk: function(e) {

        // if (!this.volumeIndex) {
        //   uni.showToast({
        //     title: '请选择打水量',
        //     icon: 'none'
        //   });
        //   return;
        // }

        var usescore = this.usescore;
        if(usescore == 0){
          usescore = 1;
        }else {
          usescore = 0;
        }
        this.usescore = usescore;
        this.calculatePrice();
      },
      showLinkChange: function () {
        this.showLinkStatus = !this.showLinkStatus;
      },
      //优惠券
      showCouponList: function(e) {
        if (!this.volumeIndex) {
          uni.showToast({
            title: '请选择打水量',
            icon: 'none'
          });
          return;
        }
        this.couponvisible = true;
        this.bid = e.currentTarget.dataset.bid;
      },
      handleClickMask: function() {
        this.couponvisible = false;
      },
      chooseCoupon: function(e) {
         // console.log(121212)
         // console.log(e)
        var bid = e.bid;
        var couponrid = e.rid;
        var couponkey = e.key;

        this.couponrid = '';

        var oldcoupons = [];//allbuydata[bid].coupons;
        var oldcouponrids = this.couponrids;
        var couponList = this.couponList;
        var totalprice =  this.totalprice;

        var is_use_coupon = 1;
        if (app.inArray(couponrid,oldcouponrids)) {
          var coupons = [];
          var couponrids = [];
          for(var i in oldcoupons){
            if(oldcoupons[i].id != couponrid){
              coupons.push(oldcoupons[i]);
              couponrids.push(oldcoupons[i].id);
            }
          }
          is_use_coupon = 0;
        } else {
          coupons = oldcoupons;
          couponrids = oldcouponrids;
          coupons = [couponList[couponkey]];
          couponrids = [couponrid];
        }
        this.coupons = coupons;
        this.couponrids = couponrids;

        var coupon_money = 0;
        var coupontype = 1;
        var  not_used_discount = 0;
        var  xianxia_proid = {};//线下券 {proid:num} {123:3,3,124:2}
        for(var i in coupons){
          not_used_discount = coupons[i]['not_select_coupon'];
          if(coupons[i]['type'] == 10){

            //折扣券
            coupon_money += totalprice * (100-coupons[i]['discount']) * 0.01;
          }else{
            coupon_money += coupons[i]['couponmoney']
          }
          this.couponrid = coupons[i]['id'] || '';
        }
        if(not_used_discount==1){
          var title ='使用该'+this.t('优惠券')+'时，'+this.t('会员')+'折扣不生效';
          uni.showToast({
            title: title,
            icon: 'none',
            duration: 3000,
            success: function(res) {

            }
          });
        }
        //console.log(this.coupons)
        this.coupon_money = coupon_money;


        this.couponvisible = false;
        this.calculatePrice();
      },
      //计算价格
      calculatePrice: function() {
        var that = this;

        var coupon_money = that.coupon_money;
        var alltotalprice = that.totalprice;
        that.userinfo.scoredkmaxmoney = that.totalprice;//积分最大抵扣数值


        //优惠券抵扣
        alltotalprice = alltotalprice - coupon_money;

        //产品积分抵扣
        if (alltotalprice>0 && that.userinfo.score>0 && that.userinfo.scoredk_money>0 && that.userinfo.scoredkmaxpercent>0 && that.usescore == 1) {

          var shopscoredk_money     = parseFloat(that.userinfo.scoredk_money); //会员产品积分换算最大可抵扣数值
          var shopscoremaxtype = parseInt(that.userinfo.scoremaxtype);//兑换类型
          if (shopscoremaxtype == 0) {
            var scoredkmaxpercent = parseFloat(that.userinfo.scoredkmaxpercent); //最大抵扣比例
            var nowshopscoredk_money  = alltotalprice * scoredkmaxpercent * 0.01;//现在可最大抵扣数值
          } else{
            var nowshopscoredk_money = parseFloat(that.userinfo.scoredkmaxmoney); //现在可最大抵扣数值
          }
          console.log(nowshopscoredk_money)
          if(nowshopscoredk_money>0){
            nowshopscoredk_money = nowshopscoredk_money.toFixed(2);
            if(nowshopscoredk_money<=shopscoredk_money){
              alltotalprice -= nowshopscoredk_money;
            }else{
              alltotalprice -= shopscoredk_money;
            }
          }
        }

        alltotalprice = alltotalprice.toFixed(2);
        if (alltotalprice < 0) alltotalprice = 0;

        that.show_price = alltotalprice;
        that.youhui = that.totalprice - alltotalprice;
        that.youhui = that.youhui.toFixed(2);

      },
      showExplain(n){
        uni.showModal({
          title: '解释说明',
          content: '最多可抵扣订单优惠后金额的'+ this.userinfo.scoredkmaxpercent+'%',
          showCancel:false
        });
      },
		}
	}
</script>

<style>
	.banner-view{width: 100%;height: 400rpx;background-repeat: no-repeat;background-size: cover;background-position: 0rpx -235rpx;}
	.position-view{position: relative;left:50%;top:-320rpx;width: 96%;transform: translateX(-50%);height: auto;}
	.position-view .top-position-view {width: 100%;padding: 20rpx;}
	.position-view .top-position-view .address-text{font-size: 30rpx;color: #fff;font-weight: bold;margin: 0rpx 10rpx;}
	.position-view .top-position-view  .add-icon{width: 50rpx;height: 50rpx;}
	.position-view .top-position-view  .add-icon image{width: 100%;height: 100%;}
	.position-view .top-position-view  .xiala-icon{width: 24rpx;height: 24rpx;}
	.position-view .top-position-view  .xiala-icon image{width: 100%;height: 70%;}
	.position-view .top-position-view .nearby-water{font-size: 26rpx;color: #fff;}
	.position-view .top-position-view .nearby-water .more-icon{width: 30rpx;height: 30rpx;margin-left: 5rpx;}
	.position-view .pos-options-view{background: #fff;width: 100%;height: auto;padding: 30rpx;border-radius: 30rpx;overflow: hidden;}
	.water-volume{}
	.volume-top-view{}
	.volume-top-view .wenti-text{font-size: 26rpx;color: #0074FE;}
	.volume-top-view .wenti-icon{width: 26rpx;height: 26rpx;margin-right: 10rpx;}
	.volume-top-view .title-size-1{font-size: 40rpx;color: #000;font-weight: bold;}
	.volume-top-view .title-size-2{font-size: 26rpx;color: #AAAAAA;margin-top: 20rpx;letter-spacing: 2rpx;}
	.choose-volume-view{align-items: center;justify-content: space-between;margin: 30rpx 0 0 0;width: 100%;}
	.choose-volume-view .options-volume{width: 31%;border-radius: 26rpx;height: 160rpx;border: 2px solid #E2DFDF;margin-bottom: 20rpx}
	.choose-volume-view .options-volume .volume-num{font-size: 36rpx;font-weight: bold;color: #1A1A1A;}
	.choose-volume-view .options-volume .price-text{font-size: 30rpx;color: #878889;margin-top: 25rpx}
	.choose-volume-view .options-volume-active{border-color: #0074FE !important;color: #0074FE !important;}
	.choose-volume-view .options-volume-active .volume-num{font-size: 36rpx;font-weight: bold;color: #0074FE;}
	.choose-volume-view .options-volume-active .price-text{font-size: 30rpx;color: #0074FE;margin-top: 25rpx}
	/*  */
	.coupon-view{margin-top: 20rpx;padding: 30rpx 0rpx;align-items: center;}
	.options-title{font-size: 28rpx;color: #000;font-weight: bold;letter-spacing: 2rpx;}
	.deduct-view{flex: 1;font-size: 26rpx;margin-left: 20rpx;}
	.deduct-view .deduct-assistance{color: rgba(0, 0, 0, 0.5)}
	.deduct-view .deduct-price{color: #000000;padding: 0rpx 10rpx;}
	/*  */
	.bottom-but{width: 100%;background: #fff;position: fixed;bottom: 0;padding: 30rpx; padding-bottom: calc(20rpx + env(safe-area-inset-bottom));}
	.bottom-but .right-but{background: linear-gradient(90deg, #065FFE 0%, #359BF7 100%);width: 48%;border-radius: 50px;height: 100rpx;color: #FFFFFF;font-size: 32rpx;
	font-weight: bold;text-align: center;justify-content: center;}
	.bottom-but .left-view{flex: 1;}
	.bottom-but .left-view .discount-num{font-size: 24rpx;color: rgba(34, 34, 34, 0.6);margin-top:5rpx;}
  .couponname{color:#fff;padding:4rpx 16rpx;font-weight:normal;border-radius:8rpx;font-size:24rpx;display:inline-block;margin:2rpx 0 2rpx 10rpx}
  .title-icon{width: 30rpx;height: 30rpx;margin: 10rpx 0 0 10rpx;}
  uni-checkbox .uni-checkbox-input     {
    border-radius: 3upx !important;
    color: #ffffff !important;
  }

  uni-checkbox .uni-checkbox-input.uni-checkbox-input-checked
  {
    color: #fff;
    border-color: rgb(0, 122, 255);
    background: rgb(0, 122, 255);
  }

  uni-checkbox .uni-checkbox-input.uni-checkbox-input-checked:after     {
    font-size: 18px;
  }
</style>