<template>
	<view class="container">
		<block v-if="isload">
      <view class="wc mendian_wc">
        <view style="width: 100%;">
          <view class="mendian_name">
            <view v-if="mendian_tip">{{mendian_tip}}</view>
            <view v-else style="display: flex;align-items: center;">
              <image :src="mendian?mendian.pic:''" style="width: 30rpx;height: 30rpx;border-radius: 4rpx;margin-right: 10rpx;"></image>
              <view style="width: 600rpx;text-overflow: ellipsis;white-space: nowrap;overflow: hidden;">{{mendian?mendian.name:'无门店'}}</view>
            </view>
          </view>
        </view>
        <view class="mendian_address" style="display: flex;justify-content: space-between;font-size: 24rpx;">
          <view style="color: #3A4463;">
            门店地址
          </view>
          <view @tap="openLocation" :data-latitude="mendian.latitude" :data-longitude="mendian.longitude" :data-company="mendian.name" :data-address="mendian.address" style="width: 500rpx;text-align:right;text-overflow: ellipsis;white-space: nowrap;overflow: hidden;">
            {{mendian?mendian.area+' '+mendian.address:'无'}}
            <image src="/static/xinxixie/right.png" style="width: 13rpx;height: 20rpx;margin-left: 10rpx;"></image>
          </view>
        </view>
        <view v-if="mendian&&mendian.tel" class="mendian_tel" >
          <view style="color: #3A4463;">
            电话咨询
          </view>
          <view @tap="callphone" :data-phone="mendian?mendian.tel:''" style="width: 500rpx;text-align:right;text-overflow: ellipsis;white-space: nowrap;overflow: hidden;">
            {{mendian?mendian.tel:'无'}}
            <image src="/static/xinxixie/right.png" style="width: 13rpx;height: 20rpx;margin-left: 10rpx;"></image>
          </view>
        </view>
      </view>
      <view v-for="(item, index) in list" :key="index" class="wc" style="margin-bottom: 20rpx;">
        <!-- s -->
      	<view style="display: flex;">
      		<view @tap="goto" :data-url="'/pages/shop/product?id=' + item.product.id" style="width: 130rpx;margin-right: 20rpx;" >
      			<image :src="item.pic" style="width: 130rpx;height: 130rpx;border-radius: 12rpx;"></image>
      		</view>
          <view style="width: 540rpx;">
            <view style="display: flex;">
              <view style="width: 380rpx;">
              	<view style="font-size: 28rpx;font-weight: bold;text-overflow: ellipsis;white-space: nowrap;overflow: hidden;">
                  {{item.name}}
                </view>
                <view style="font-size: 28rpx;font-weight: bold;">
                  x{{item.num}}
                </view>
              </view>
              <view style="flex: 1;"></view>
              <view style="width: 140rpx;text-align: right;word-wrap: break-word;">
                <text style="font-size: 24rpx;color:#3A4463">￥</text>
                <text style="font-size: 30rpx;color:#3A4463;font-weight: bold;">{{item.ggprice}}</text>
              </view>
            </view>
          </view>
      	</view> 
        <!-- e -->
      </view>
      
      <!-- 配送方式s -->
      <view class="wc" style="margin-bottom: 20rpx;">
        <view class="freight">
          <view class="f1">配送方式</view>
          <view class="freight-ul">
            <view style="width:100%;overflow-y:hidden;overflow-x:scroll;white-space: nowrap;">
              <block v-for="(item, idx2) in freightList" :key="idx2">
                <view class="freight-li" :style="freightkey==idx2?'color:'+t('color1')+';background:rgba('+t('color1rgb')+',0.2)':''"
                  @tap="changeFreight" :data-index="idx2">{{item.name}}
                </view>
              </block>
            </view>
          </view>
          <view class="freighttips" v-if="freightList[freightkey].isoutjuli==1">超出配送范围</view>
          <view class="freighttips" v-if="freightList[freightkey].desc">{{freightList[freightkey].desc}}</view>
        </view>
      </view>
      <view  v-if="freightList[freightkey].pstimeset==1" class="wc" style="margin-bottom: 20rpx;">
        <view class="price">
          <view class="f1">{{freightList[freightkey].pstype==1?'取货':'配送'}}时间</view>
          <view class="f2" @tap="choosePstime">
            {{pstimetext==''?'请选择时间':pstimetext}}<text class="iconfont iconjiantou"
              style="color:#999;font-weight:normal"></text>
          </view>
        </view>
      </view>
      <!-- 配送方式e -->
      <view v-if="needaddress==0" class="address-add">
      	<view class="linkitem">
      		<text class="f1">联 系 人：</text>
      		<input type="text" class="input" :value="linkman" placeholder="请输入您的姓名" @input="inputLinkman"
      			placeholder-style="color:#626262;font-size:28rpx" />
      	</view>
      	<view class="linkitem">
      		<text class="f1">联系电话：</text>
      		<input type="text" class="input" :value="tel" placeholder="请输入您的手机号" @input="inputTel"
      			placeholder-style="color:#626262;font-size:28rpx" />
      	</view>
      </view>
      <view v-else class="address-add" >
        <view class="flex-y-center" @tap="goto"
          :data-url="'/pagesB/address/'+(address.id ? 'address' : 'addressadd')+'?fromPage=buy&type=' + (havetongcheng==1?'1':'0')">
          <view class="f1">
            <image class="img" :src="pre_url+'/static/img/address.png'" />
          </view>
          <view class="f2 flex1" v-if="address.id">
            <view style="font-weight:bold;color:#111111;font-size:30rpx">{{address.name}} {{address.tel}} <text v-if="address.company">{{address.company}}</text></view>
            <view style="font-size:24rpx">{{address.area}} {{address.address}}</view>
          </view>
          <view v-else class="f2 flex1">请选择收货地址</view>
          <image :src="pre_url+'/static/img/arrowright.png'" class="f3"></image>
        </view>
      </view>
      <view class="wc" style="margin-bottom: 20rpx;">
        <view style="font-size: 28rpx;color: #3A4463;line-height: 50rpx;margin-top: 20rpx;">备注消息</view>
        <view>
          <input placeholder="请输入备注消息" @input="inputMessage" :data-index="index" placeholder-style="color:#969699;line-height:70rpx" style="width: 100%;height:70rpx;line-height:70rpx"/>
        </view>
      </view>
      <view class="wc" style="margin-bottom: 20rpx;">
        <view class="price">
        	<view class="f1">
          {{freightList[freightkey].freight_price_txt || '运费'}}
          </view>
        	<text class="f2">+¥{{freightList[freightkey].freight_price}}</text>
        </view>
        <view class="total_content">
          <view style="width: 170rpx;">
            商品总价
          </view>
          <view style="width: 600rpx;text-align: right;">
            ￥{{product_price}}
          </view>
        </view>
        <view class="total_content">
          <view style="width: 170rpx;">
            商品总数
          </view>
          <view style="width: 600rpx;text-align: right;">
            {{totalnum}}件
          </view>
        </view>
      </view>

      <view style="width: 100%; height:110rpx;"></view>
      <view class="footer flex notabbarbot">
        <view class="text1 flex1">
          <text style="margin-right: 10rpx;color: #3A4463;font-size: 26rpx;">合计</text>
          <text style="font-weight:bold;font-size:32rpx;color: #F55726;">{{alltotalprice}}</text>
          <text style="font-size: 24rpx;">元</text>
        </view>
        <button class="op" @tap="topay" :style="{background:'linear-gradient(-90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'}" :disabled="submitDisabled">
          提交订单</button>
      </view>
      
      <view v-if="pstimeDialogShow" class="popup__container">
      	<view class="popup__overlay" @tap.stop="hidePstimeDialog"></view>
      	<view class="popup__modal">
      		<view class="popup__title">
      			<text
      				class="popup__title-text">请选择{{freightList[freightkey].pstype==1?'取货':'配送'}}时间</text>
      			<image :src="pre_url+'/static/img/close.png'" class="popup__close" style="width:36rpx;height:36rpx"
      				@tap.stop="hidePstimeDialog" />
      		</view>
      		<view class="popup__content">
      			<view class="pstime-item"
      				v-for="(item, index) in freightList[freightkey].pstimeArr"
      				:key="index" @tap="pstimeRadioChange" :data-index="index">
      				<view class="flex1">{{item.title}}</view>
      				<view class="radio"
      					:style="freight_time==item.value ? 'background:'+t('color1')+';border:0' : ''">
      					<image class="radio-img" :src="pre_url+'/static/img/checkd.png'" />
      				</view>
      			</view>
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
        
        bid:0,
        mdid:0,
        mdname    : '',
        latitude  : '',
        longitude : '',

        encrypted_datas:'',
        codes:'',
        prodata:'',
        
        mendian : '',
        mendian_tip:'',
        
        list         : '',
        product_price: 0,
        totalnum     : 0,
        product_price: 0,
        alltotalprice: 0,
        
        freightList:[],
        freightkey:-1,
        freight_time:'',
        havetongcheng:0,
        needaddress:0,
        address:'',
        
        pstimetext:'',
        pstimeDialogShow: false,
        pstimeIndex     : -1,
        
        message:'',
        rid:0,
        latitude: "",
        longitude: "",
			};
		},
		onLoad: function(opt) {
      var that = this;
			that.opt = app.getopts(opt);
      that.bid   = that.opt.bid || 0;
      that.encrypted_datas= that.opt.encrypted_datas || '';
      that.codes          = that.opt.codes || '';
      that.prodata        = that.opt.prodata || '';

      var mdid =  app.getCache('mdid');
      if(mdid){
        that.mdid =  mdid
      }else{
        app.alert('请先选择门店',function(){
          app.goback();
        });
        return;
      }
      var mdname =  app.getCache('mdname');
      if(mdname){
        that.mdname =  mdname
      }
      var latitude =  app.getCache('latitude');
      if(latitude){
        that.latitude   = latitude;
      }
      var longitude = app.getCache('longitude');
      if(longitude){
        that.longitude = longitude;
      }
      this.getdata();
		},
		onShow:function(e){
      
		},
		onPullDownRefresh: function() {
			this.getdata();
		},
		methods: {
			getdata: function() {
				var that = this;
				that.loading = true;
				app.post('ApiDouyinGroupbuy/buy', {
          bid:that.bid,
          mdid:that.mdid,
					encrypted_datas: that.encrypted_datas,
          codes: that.codes,
          prodata:that.prodata,
				}, function(res) {
					that.loading = false;
					if (res.status == 1) {

						that.mendian     = res.mendian;
						that.mendian_tip = res.mendian_tip;
  
            that.list         = res.list;
            that.product_price   = res.product_price;
            that.totalnum     = res.totalnum;
            that.product_price= res.product_price;
            
            that.freightList = res.freightList;
            if(res.freightList){
              that.freightkey = 0;
            }
            that.havetongcheng = res.havetongcheng;
            that.address       = res.address;
            if(that.address && that.address.latitude){
            	that.latitude = that.address.latitude;
            	that.longitude = that.address.longitude;
            }
						that.calculatePrice();
						that.loaded();
					}else{
            if (res.msg) {
              app.alert(res.msg, function() {
                if (res.url) app.goto(res.url);
              });
            } else if (res.url) {
              app.goto(res.url);
            } else {
              app.alert('您无查看权限');
            }
          }
				});
			},
			inputLinkman: function(e) {
				this.linkman = e.detail.value;
			},
			inputTel: function(e) {
				this.tel = e.detail.value;
			},
			//计算价格
			calculatePrice: function() {
				var that     = this;
        var product_price     = parseFloat(that.product_price); //商品价格
        //算运费
        var freightdata   = that.freightList[that.freightkey];
        var freight_price = freightdata['freight_price'];
        if (freightdata.pstype != 1 && freightdata.pstype != 3 && freightdata.pstype != 4) {
        	that.needaddress = 1;
        }else{
          that.needaddress = 0;
        }
        var alltotalprice = 0;
        var alltotalprice = product_price+freight_price;
        if (alltotalprice <= 0) alltotalprice = 0; 
        alltotalprice = alltotalprice.toFixed(2);
        that.alltotalprice = alltotalprice;
			},
			//提交并支付
			topay: function(e) {
				var that = this;
        var list    = that.list;
        if(!list){
          app.error('没有可兑换商品');
          return false;
        }
        var len = list.length;
        if(len<=0){
          app.error('没有可兑换商品');
          return false;
        }

        var needaddress = that.needaddress;
        
        var addressid   = that.address && that.address.id?that.address.id:0;
        var linkman     = that.linkman;
        var tel         = that.tel;
        
        if (needaddress == 0) addressid = 0;
        if (needaddress == 1 && addressid == undefined) {
        	app.error('请选择收货地址');
        	return;
        }
        
        var freightkey = that.freightkey;
        if (that.freightList[freightkey].pstimeset == 1 && that.freight_time == ''){
          app.error('请选择' + (that.freightList[freightkey].pstype == 1 ? '取货' : '配送') + '时间');
          return;
        }

				app.showLoading('提交中');
				app.post('ApiDouyinGroupbuy/createOrder', {
          bid:that.bid,
          mdid:that.mdid,
          encrypted_datas: that.encrypted_datas,
          codes: that.codes,
          prodata:that.prodata,
          
          freight_id: that.freightList[freightkey].id,
          freight_time: that.freight_time,
          addressid: addressid,
          linkman  : linkman,
          tel      : tel,
          message  :that.message
				}, function(res){
					app.showLoading(false);
          if(res.status == 1){
            if(res.payorderid){
              app.goto('/pagesExt/pay/pay?id=' + res.payorderid,'redirectTo');
            }else{
              app.goto('/pagesExt/order/orderlist');
            }
          }else{
            if (res.msg) {
              app.alert(res.msg, function() {
                if (res.url) app.goto(res.url);
              });
            } else if (res.url) {
              app.goto(res.url);
            } else {
              app.alert('您无查看权限');
            }
          }
				});
			},
			showCouponList: function(e) {
				this.couponvisible = true;
			},
			handleClickMask: function() {
				this.couponvisible = false;
				this.invoiceShow   = false;
			},
      openLocation:function(e){
      	var latitude = parseFloat(e.currentTarget.dataset.latitude)
      	var longitude = parseFloat(e.currentTarget.dataset.longitude)
      	var address = e.currentTarget.dataset.address
      	uni.openLocation({
      	 latitude:latitude,
      	 longitude:longitude,
      	 name:address,
      	 scale: 13,
      	 address:address,
       })		
      },
      callphone:function(e) {
      	var phone = e.currentTarget.dataset.phone;
        if(phone){
          uni.makePhoneCall({
          	phoneNumber: phone,
          	fail: function () {
          	}
          });
        }
      },
      inputMessage:function(e){
      	var that = this;
        that.message   = e.detail.value;
      },
      changeFreight: function(e) {
      	var that = this;
      	var index = e.currentTarget.dataset.index;
      	var freightList = that.freightList;
      	that.freightkey = index;
      	that.calculatePrice();
      },
      choosePstime: function(e) {
      	var that = this;

      	var freightkey  = that.freightkey;
      	var freightList = that.freightList;
        
      	var freight   = freightList[freightkey];
      	var pstimeArr = freightList[freightkey].pstimeArr;
      	var itemlist = [];
      	for (var i = 0; i < pstimeArr.length; i++) {
      		itemlist.push(pstimeArr[i].title);
      	}
      	if (itemlist.length == 0) {
      		app.alert('当前没有可选' + (freightList[freightkey].pstype == 1 ? '取货' : '配送') + '时间段');
      		return;
      	}
      	that.pstimeDialogShow = true;
      	that.pstimeIndex = -1;
      },
      pstimeRadioChange: function(e) {
      	var that = this;

      	var pstimeIndex = e.currentTarget.dataset.index;
      	var freightkey  = that.freightkey;
      	var freightList = that.freightList;
        
      	var freight     = freightList[freightkey];
      	var pstimeArr   = freightList[freightkey].pstimeArr;
      	var choosepstime= pstimeArr[pstimeIndex];

      	that.pstimetext   = choosepstime.title;
      	that.freight_time = choosepstime.value;
      	that.pstimeDialogShow = false;
      },
      hidePstimeDialog: function() {
      	this.pstimeDialogShow = false;
      },
		}
	}
</script>

<style>
.container{overflow: hidden;}
.redBg{color:#fff;padding:4rpx 16rpx;font-weight:normal;border-radius:8rpx;font-size:24rpx; width: auto; display: inline-block; margin-top: 4rpx;}
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
.inputPrice {border: 1px solid #ddd; width: 200rpx; height: 40rpx; border-radius: 10rpx; padding: 0 4rpx;}

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
.footer .op {width: 300rpx;height: 80rpx;line-height: 80rpx;color: #fff;text-align: center;font-size: 30rpx;border-radius: 44rpx}
.footer .op[disabled] { background: #aaa !important; color: #666;}

.pstime-item {display: flex;border-bottom: 1px solid #f5f5f5;padding: 20rpx 30rpx;}
.pstime-item .radio {flex-shrink: 0;width: 32rpx;height: 32rpx;background: #FFFFFF;border: 2rpx solid #BFBFBF;border-radius: 50%;margin-right: 30rpx}
.pstime-item .radio .radio-img {width: 100%;height: 100%}

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

.btn{ height:80rpx;line-height: 80rpx;width:90%;margin:0 auto;border-radius:40rpx;margin-top:40rpx;color: #fff;font-size: 28rpx;font-weight:bold}
.invoiceBox .radio radio{transform: scale(0.8);}
.invoiceBox .radio:nth-child(2) { margin-left: 30rpx;}
.pdl10{padding-left: 10rpx;}

.wc{width: 710rpx;margin: 0 auto;background-color: #fff;border-radius: 8rpx;padding: 20rpx;}
.mendian_wc{position: relative;z-index: 9;width: 710rpx;border-radius: 12rpx;box-shadow: 0px 5px 10px 0px rgba(0, 0, 0, 0.12);margin-bottom: 20rpx;}
.mendian_left{overflow: hidden;display: flex;justify-content: space-between;border-bottom:2rpx solid #F5F5F7;}
.mendian_name{display: inline-block;font-size: 30rpx;font-weight: bold;text-overflow: ellipsis;white-space: nowrap;overflow: hidden;line-height: 60rpx;}
.mendian_tap{position: absolute;top:0;right: 0;background: linear-gradient(to right, #E8F0FF, #FCFDFF);text-align: center;font-size: 24rpx;width:180rpx ;line-height: 60rpx;color: #0D66FC;border-radius: 0 6rpx 0 30rpx;}
.mendian_address{line-height: 50rpx;white-space: pre-wrap;overflow: hidden;padding: 10rpx 0;color: #6E728B;font-size: 24rpx;}
.mendian_local{width: 22rpx;height: 22rpx;float: left;margin-top:14rpx;margin-right: 10rpx;}
.mendian_tel{overflow: hidden;color: #6E728B;line-height: 50rpx;display: flex;justify-content: space-between;font-size: 24rpx;}

.podata_content{line-height: 40rpx;font-size: 22rpx;border-radius: 6rpx;overflow: hidden;display: inline-block;margin: 4rpx 0;margin-right: 10rpx;}
.podata_name{display: inline-block;background-color: #266DFF;color: #fff;padding: 0 10rpx;}
.podata_price{display: inline-block;background-color: #E5EDFF;color: #266DFF;padding: 0 10rpx;}

.freight {width: 100%;padding: 20rpx 0;background: #fff;display: flex;flex-direction: column;}
.freight .f1 {color: #333;margin-bottom: 10rpx}
.freight .f2 {color: #111111;text-align: right;flex: 1}
.freight .f3 {width: 24rpx;height: 28rpx;}
.freighttips {color: red;font-size: 24rpx;}
.freight-ul {width: 100%;}
.freight-li {background: #F5F6F8;border-radius: 24rpx;color: #6C737F;font-size: 24rpx;line-height: 48rpx;padding: 0 28rpx;margin: 12rpx 10rpx 12rpx 0;display: inline-block;white-space: break-spaces;max-width: 610rpx;vertical-align: middle;}
.inputPrice {border: 1px solid #ddd; width: 200rpx; height: 40rpx; border-radius: 10rpx; padding: 0 4rpx;}

.price {width: 100%;padding: 20rpx 0;background: #fff;display: flex;align-items: center}
.price .f1 {color: #333}
.price .f2 {color: #111;font-weight: bold;text-align: right;flex: 1}
.price .f3 {width: 24rpx;height: 24rpx;}
.price .couponname{color:#fff;padding:4rpx 16rpx;font-weight:normal;border-radius:8rpx;font-size:24rpx;display:inline-block;margin:2rpx 0 2rpx 10rpx}

.address-add {width: 94%;margin: 20rpx 3%;background: #fff;border-radius: 20rpx;padding: 20rpx 3%;min-height: 140rpx;}
.address-add .f1 {margin-right: 20rpx}
.address-add .f1 .img {width: 66rpx;height: 66rpx;}
.address-add .f2 {color: #666;}
.address-add .f3 {width: 26rpx;height: 26rpx;}
.linkitem {width: 100%;padding: 1px 0;background: #fff;display: flex;align-items: center}.cf3 {width: 200rpx;height: 26rpx;display: block;
    text-align: right;}
.linkitem .f1 {width: 160rpx;color: #111111}
.linkitem .input {height: 50rpx;padding-left: 10rpx;color: #222222;font-weight: bold;font-size: 28rpx;flex: 1}

.total_content{display: flex;justify-content: space-between;color: #3A4463;line-height: 80rpx;}

.xieyibox{width:100%;height:100%;position:fixed;top:0;left:0;z-index:99;background:rgba(0,0,0,0.7)}
.xieyibox-content{width:90%;margin:0 auto;/*  #ifdef  MP-TOUTIAO */height:60%;/*  #endif  *//*  #ifndef  MP-TOUTIAO */height:80%;/*  #endif  */margin-top:20%;background:#fff;color:#333;padding:5px 10px 50px 10px;position:relative;border-radius:2px;}

@supports(bottom: env(safe-area-inset-bottom)) {
		.xieyi {
			padding-bottom: env(safe-area-inset-bottom);
		}
}

</style>
