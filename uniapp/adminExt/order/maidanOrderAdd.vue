<template>
  <view>
    <block v-if="isload">
      <view class="container">
        <view class="page">
          <view class="dkdiv-item flex" style="padding: 0;border: 0">
            <view class="f1"><span style="color: red">*</span>订单金额</view>
          </view>
          <view class="page_module flex-y-center">
            <text class="page_tag">￥</text>
            <view class="page_price flex-y-center" style="font-size: 40rpx;margin-left: 5rpx">
              <input class="page_price flex1" type="digit"  style="font-weight: bold;text-align: left !important;" @input="inputMoney" placeholder="请输入订单金额"></input>
            </view>
          </view>
          <view>
            <view class="info-box">
              <view class="dkdiv-item flex" style="border: 0">
                <view class="f1"><span style="color: red">*</span>{{t('会员')}}手机号/ID</view>
                <input class="page_price flex1" type="digit" @blur="inputTel" placeholder="请输入手机号/ID"></input>
              </view>
              <view v-if="member_info">
                <view class="dkdiv-item flex flex-bt" style="border: 0">
                  <view style="display: flex;justify-content: center;align-items: center;">
                    <view class="t1">{{t('会员')}}信息</view>
                  </view>
                  <view style="display: flex;justify-content: center;align-items: center;">
                    <image class="header_icon" :src="member_info.headimg"></image>
                    <text class="t2">{{member_info.nickname}}(ID:{{member_info.id}})</text>
                  </view>
                </view>
                <view class="dkdiv-item flex flex-bt" v-if="member_info.p_member">
                  <view style="display: flex;justify-content: center;align-items: center;">
                    <text class="t1">邀请人信息</text>
                  </view>
                  <view style="display: flex;justify-content: center;align-items: center;">
                    <image class="header_icon" :src="member_info.p_member.headimg"></image>
                    <text class="t2">{{member_info.p_member.nickname}}(ID:{{member_info.p_member.id}})</text>
                  </view>
                </view>
              </view>


              <view class="dkdiv-item flex flex-bt" v-if="newscoredata" style="border-top: 1px #ededed solid; ">
                <view style="">
                  <view class="t2">{{t('会员')}}预估可得{{t('新积分')}}：{{newscoredata.newscore_m ? newscoredata.newscore_m : 0}}</view>
                  <view class="t2">邀请人预估可得{{t('佣金')}}：{{member_info.p_member ? parent1commission : '无邀请人信息'}}</view>
                  <view class="t2">商家预估可得{{t('新积分')}}：{{newscoredata.newscore_b ? newscoredata.newscore_b : 0}}</view>
                </view>
              </view>

              <view class="dkdiv-item flex-y-center" >
                <view class="f1"><span style="color: red">*</span>有无发票</view>
                <view class="f2">
                  <radio-group class="radio-group" name="tolist" @change="invoiceChange">
                    <label><radio value="1" :checked="invoice==1?true:false"></radio> 有</label>
                    <label style="margin-left: 20rpx;"><radio value="0" :checked="invoice==0?true:false"></radio> 无</label>
                  </radio-group>
                </view>
              </view>

              <view class="dkdiv-item flex-y-center" style="justify-content: space-between;">
                <view class="form-item flex-col">
                  <view class="label f1"><span style="color: red">*</span>买卖双方合同<sapn style="color: #5d5c5c;font-size: 12px">(最多三张，仅支持jpg，png格式)</sapn></view>
                  <view id="content_picpreview" class="flex" style="flex-wrap:wrap;padding-top:20rpx">
                    <view v-for="(item, index) in contract_pic" :key="index" class="layui-imgbox" >
                      <view class="layui-imgbox-close" @tap="removeimg2" :data-index="index" data-field="contract_pic"><image :src="pre_url+'/static/img/ico-del.png'"></image></view>
                      <view class="layui-imgbox-img"><image :src="item" @tap="previewImage" :data-url="item" mode="widthFix"></image></view>
                    </view>
                    <view class="uploadbtn" :style="'background:url('+pre_url+'/static/img/shaitu_icon.png) no-repeat 60rpx;background-size:80rpx 80rpx;background-color:#F3F3F3;'" @tap="uploadimg2" data-field="contract_pic" v-if="contract_pic.length<3"></view>
                  </view>
                </view>
              </view>

              <view class="dkdiv-item flex-y-center" style="justify-content: space-between;">
                <view class="form-item flex-col">
                  <view class="label f1"><span style="color: red">*</span>买方付款凭证<sapn style="color: #5d5c5c;font-size: 12px">(最多三张，仅支持jpg，png格式)</sapn></view>
                  <view id="content_picpreview" class="flex" style="flex-wrap:wrap;padding-top:20rpx">
                    <view v-for="(item, index) in payment_voucher_pic" :key="index" class="layui-imgbox">
                      <view class="layui-imgbox-close" @tap="removeimg3" :data-index="index" data-field="payment_voucher_pic"><image :src="pre_url+'/static/img/ico-del.png'"></image></view>
                      <view class="layui-imgbox-img"><image :src="item" @tap="previewImage" :data-url="item" mode="widthFix"></image></view>
                    </view>
                    <view class="uploadbtn" :style="'background:url('+pre_url+'/static/img/shaitu_icon.png) no-repeat 60rpx;background-size:80rpx 80rpx;background-color:#F3F3F3;'" @tap="uploadimg3" data-field="payment_voucher_pic" v-if="payment_voucher_pic.length<3"></view>
                  </view>
                </view>
              </view>

              <view class="dkdiv-item flex-y-center" style="justify-content: space-between;" v-if="invoice==1">
                <view class="form-item flex-col">
                  <view class="label f1"><span style="color: red">*</span>买卖双方发票<sapn style="color: #5d5c5c;font-size: 12px">(最多三张，仅支持jpg，png格式)</sapn></view>
                  <view id="content_picpreview" class="flex" style="flex-wrap:wrap;padding-top:20rpx">
                    <view v-for="(item, index) in invoice_pic" :key="index" class="layui-imgbox">
                      <view class="layui-imgbox-close" @tap="removeimg4" :data-index="index" data-field="invoice_pic"><image :src="pre_url+'/static/img/ico-del.png'"></image></view>
                      <view class="layui-imgbox-img"><image :src="item" @tap="previewImage" :data-url="item" mode="widthFix"></image></view>
                    </view>
                    <view class="uploadbtn" :style="'background:url('+pre_url+'/static/img/shaitu_icon.png) no-repeat 60rpx;background-size:80rpx 80rpx;background-color:#F3F3F3;'" @tap="uploadimg4" data-field="invoice_pic" v-if="invoice_pic.length<3"></view>
                  </view>
                </view>
              </view>

              <view class="dkdiv-item flex-y-center" >
                <view class="f1"><span style="color: red">*</span>发货方式</view>
                <view class="f2">
                  <radio-group class="radio-group" name="tolist" @change="shippingMethodChange">
                    <label><radio value="1" :checked="shipping_method==1?true:false"></radio> 物流单号</label>
                    <label style="margin-left: 20rpx;"><radio value="0" :checked="shipping_method==0?true:false"></radio> 非物流方式发货</label>
                  </radio-group>
                </view>
              </view>
              <view class="dkdiv-item flex-y-center" style="border: 0" v-if="shipping_method == 1">
                <view class="f1"><span style="color: red">*</span>快递单号</view>
                <view class="f2" style="flex-wrap:wrap;margin-top:20rpx;margin-bottom:20rpx;margin-left: 130px">
                  <input class="page_price flex1" type="digit" @input="inputExpressNo" placeholder="请输入快递单号"></input>
                </view>
              </view>

              <view>
                <view class="form-item flex-col">
                  <view class="label f1"><span style="color: red">*</span>发货凭证<sapn style="color: #5d5c5c;font-size: 12px">(最多三张，仅支持jpg，png格式)</sapn></view>
                  <view id="content_picpreview" class="flex" style="flex-wrap:wrap;padding-top:20rpx">
                    <view v-for="(item, index) in delivery_voucher_pic" :key="index" class="layui-imgbox">
                      <view class="layui-imgbox-close" @tap="removeimg5" :data-index="index" data-field="delivery_voucher_pic"><image :src="pre_url+'/static/img/ico-del.png'"></image></view>
                      <view class="layui-imgbox-img"><image :src="item" @tap="previewImage" :data-url="item" mode="widthFix"></image></view>
                    </view>
                    <view class="uploadbtn" :style="'background:url('+pre_url+'/static/img/shaitu_icon.png) no-repeat 60rpx;background-size:80rpx 80rpx;background-color:#F3F3F3;'" @tap="uploadimg5" data-field="delivery_voucher_pic" v-if="delivery_voucher_pic.length<3"></view>
                  </view>
                </view>
              </view>

              <view class="dkdiv-item flex-y-center">
                <view class="f1"><span style="color: red">*</span>通知手机</view>
                <view class="f2" style="flex-wrap:wrap;margin-top:20rpx;margin-bottom:20rpx">
                  <input class="page_price flex1" type="digit" @input="inputNoticeTel" placeholder="请输入接收审核的通知手机号"></input>
                </view>
              </view>

              <view class="dkdiv-item flex-y-center" style="justify-content: space-between;">
                <view class="t1"><span style="color: red">*</span>支付金额：</view>
                <view class="t2" style="text-align: right">￥{{paymoney}}</view>
              </view>
              <view v-if="true" class="op">
                <view class="btn" @tap="toPay" :style="{background:t('color1')}">提交订单，前往汇款</view>
              </view>
            </view>
          </view>
        </view>
      </view>
    </block>
    <loading v-if="loading"></loading>
    <nomore v-if="nomore"></nomore>
    <nodata v-if="nodata"></nodata>
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
      nomore: false,
      nodata: false,
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
      contract_pic:[],
      invoice_pic:[],
      payment_voucher_pic:[],
      sysset:{},
      member_info:'',
      shipping_method:1,
      notice_tel:'',
      invoice:1,
      express_no:'',
      delivery_voucher_pic:[],
      newscoredata:'',
      newscore_ratio_business:0,
      parent1commission:0,
      payorderid:0,
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

  },
  onPullDownRefresh: function() {
    this.getdata();
  },
  methods: {

    uploadimg2:function(e){
      var that = this;
      var field= e.currentTarget.dataset.field
      var contract_pic = that[field]
      if(!contract_pic) contract_pic = [];
      app.chooseImage(function(urls){
        for(var i=0;i<urls.length;i++){
          contract_pic.push(urls[i]);
        }
      },1)
    },
    removeimg2:function(e){
      var that = this;
      var index= e.currentTarget.dataset.index
      var field= e.currentTarget.dataset.field
      var contract_pic = that[field]
      contract_pic.splice(index,1)
    },

    uploadimg3:function(e){
      var that = this;
      var field= e.currentTarget.dataset.field
      var payment_voucher_pic = that[field]
      if(!payment_voucher_pic) payment_voucher_pic = [];
      app.chooseImage(function(urls){
        for(var i=0;i<urls.length;i++){
          payment_voucher_pic.push(urls[i]);
        }
      },1)
    },
    removeimg3:function(e){
      var that = this;
      var index= e.currentTarget.dataset.index
      var field= e.currentTarget.dataset.field
      var payment_voucher_pic = that[field]
      payment_voucher_pic.splice(index,1)
    },

    uploadimg4:function(e){
      var that = this;
      var field= e.currentTarget.dataset.field
      var invoice_pic = that[field]
      if(!invoice_pic) invoice_pic = [];
      app.chooseImage(function(urls){
        for(var i=0;i<urls.length;i++){
          invoice_pic.push(urls[i]);
        }
      },1)
    },
    removeimg4:function(e){
      var that = this;
      var index= e.currentTarget.dataset.index
      var field= e.currentTarget.dataset.field
      var invoice_pic = that[field]
      invoice_pic.splice(index,1)
    },

    uploadimg5:function(e){
      var that = this;
      var field= e.currentTarget.dataset.field
      var delivery_voucher_pic = that[field]
      if(!delivery_voucher_pic) delivery_voucher_pic = [];
      app.chooseImage(function(urls){
        for(var i=0;i<urls.length;i++){
          delivery_voucher_pic.push(urls[i]);
        }
      },1)
    },
    removeimg5:function(e){
      var that = this;
      var index= e.currentTarget.dataset.index
      var field= e.currentTarget.dataset.field
      var delivery_voucher_pic = that[field]
      delivery_voucher_pic.splice(index,1)
    },


    inputTel: function(e) {
      var that = this;
      var tel = e.detail.value;
      var field = {};
      if(tel.length==11) {
        field = {tel:tel}
      }else {
        field = {mid:tel}
      }
      that.loading = true;
      app.get('ApiMy/getMemberBase',field,function (res) {
        that.loading = false;
        if(res.status == 1){
          that.member_info = res.data;
        }else {
          that.member_info = '';
          app.error('用户信息不存在');
          return;
        }
        that.getPredictNewScore();
      });
    },

    shippingMethodChange:function(e){
      this.shipping_method = e.detail.value
    },

    inputNoticeTel: function(e) {
      var that = this;
      that.notice_tel = e.detail.value;
    },

    invoiceChange: function(e) {
      var that = this;
      that.invoice = e.detail.value;
    },

    inputExpressNo: function(e) {
      var that = this;
      that.express_no = e.detail.value;
    },

    inputMoney: function(e) {
      var that = this;
      var money = e.detail.value;
      if (!money) money = 0;
      var money = parseFloat(money);
      if (money <= 0) money = 0;
      that.money = money;

      that.getPredictNewScore();
    },

    getPredictNewScore:function(){
      var that = this;
      var mid = that.member_info.id || 0;
      that.loading = true;
      app.get('ApiMaidan/getPredictNewScore',{bid:that.bid,money:that.money,mid:mid},function (res) {
        that.loading = false;
        var resdata = res.data;
        if(res.status == 1){
          that.newscoredata = resdata;
          that.newscore_ratio_business = resdata.newscore_ratio_business || 0;
          that.parent1commission = resdata.parent1commission || 0;
        }else {
          that.newscoredata = '';
        }
        that.calculatePrice();
      });
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
        that.newscore_ratio_business = res.newscore_ratio_business || 0;
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

    cancel: function() {
      this.hiddenmodalput = true;
    },
    //计算价格
    calculatePrice: function() {
      var that = this;

      var newscore_ratio_business = that.newscore_ratio_business;

      var money = ''
      if (that.money == '') {
        money = 0;
      } else {
        money = parseFloat(that.money);
      }

      var paymoney = money;
      if(newscore_ratio_business > 0){
        paymoney = money * newscore_ratio_business;
      }
      // console.log(newscore_ratio_business);
      // console.log(money);
      // console.log(paymoney);

      if (paymoney < 0) paymoney = 0;
      paymoney = paymoney.toFixed(2);
      that.paymoney = paymoney;
    },

    toPay:function(e) {
        var that = this;
        var money = that.money;
        if(money<=0){
          app.error('订单金额必须大于0');
          return;
        }

        if (that.mdlist.length > 0) {
          var mdid = that.mdlist[that.mdkey].id;
        } else {
          var mdid = 0;
        }
        var itemId = 0;
        var mid = that.member_info.id || 0;
        if(!mid || mid ==0){
          app.error('未获取到会员信息');
          return;
        }

        if(!mid || mid ==0){
          app.error('未获取到会员信息');
          return;
        }

        if(!that.contract_pic){
          app.error('请上传买卖双方合同');
          return;
        }

        if(!that.payment_voucher_pic){
          app.error('请上传买方付款凭证');
          return;
        }

        if(that.invoice== 1 && !that.invoice_pic){
          app.error('请上传买卖双方发票');
          return;
        }

        if(that.shipping_method== 1 && !that.express_no){
          app.error('请填写快递单号');
          return;
        }

        if(!that.delivery_voucher_pic){
          app.error('请上传发货凭证');
          return;
        }

        if(!that.notice_tel){
          app.error('请填写通知手机号');
          return;
        }

      app.confirm('确定提交吗?', function () {
        if(that.ispost) return;
        that.ispost = true;
        app.showLoading('提交中');
        app.post('ApiMaidan/maidan_paytransfer', {
          bid: that.bid,
          paymoney: that.paymoney,
          money: money,
          mdid: mdid,
          mid:mid,
          invoice:that.invoice,
          contract_pic:that.contract_pic,
          payment_voucher_pic:that.payment_voucher_pic,
          invoice_pic:that.invoice_pic,
          shipping_method:that.shipping_method,
          express_no:that.express_no,
          delivery_voucher_pic:that.delivery_voucher_pic,
          notice_tel:that.notice_tel,
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
          //app.goto('/pagesExt/pay/pay?id=' + res.payorderid+'&is_maidan=1');

          that.payorderid = res.payorderid;
          that.topayTransfer();
        });
      });
    },

    topayTransfer:function(e){
      var that = this;
      var orderid = that.payorderid;
      app.showLoading('提交中');
      app.post('ApiPay/pay', {op:'submit',orderid: orderid,typeid: 5}, function (res) {
        app.showLoading(false);

        if (res.status == 1) {
          //需审核付款
          app.success(res.msg);
          setTimeout(function () {
            app.goto(res.gotourl,'reLaunch');
          }, 1000);
          return;
        }else if (res.status == 2) {
          //无需付款
          app.success(res.msg);
          setTimeout(function () {
            app.goto('/pagesExt/pay/transfer?id='+orderid+'&paytransfer=1','reLaunch');
          }, 1000);
          return;
        }else{
          app.error(res.msg);
          return;
        }
      });
    },

  }
}
</script>
<style>
.container {
  position: fixed;
  height: 100%;
  width: 100%;
  /* overflow: hidden; */
  overflow-y: scroll;
  z-index: 5;
}

.header_icon {
  position: relative;
  height: 85rpx;
  width: 85rpx;
  margin-right: 20rpx;
  border-radius: 44rpx;
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

.page_module {
  position: relative;
  height: 125rpx;
  border-bottom: 1px solid #f0f0f0;
}


.page_tag {
  font-size: 58rpx;
  color: #333;
  font-weight: bold;
}

.page_price {
  margin-left: 20rpx;
  //font-size: 30rpx;
  color: #333;
  //font-weight: bold;
  text-align: right;
}

.info-box {
  position: relative;
  background: #fff;
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

.uploadbtn{position:relative;height:200rpx;width:200rpx}
.layui-imgbox{position: relative;margin-top: 10px}
</style>
