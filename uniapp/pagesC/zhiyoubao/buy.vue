<template>
	<view class="page-view" v-if="isload">
    <view class="wc">
      <view style="display: flex;">
        <view v-if="perform.pic" class="content-pic">
          <image :src="perform.pic" mode="widthFix" style="width: 100%;border-radius: 10rpx 10rpx;"></image>
        </view>
        <view style="margin-left: 10rpx;">
          <view class="content-title">{{perform.title}}</view>
          <view class="content-title2">日期：{{perform.performDate}}</view>
          <view class="content-title2">时间：{{perform.performTime}}</view>
        </view>
      </view>
    </view>
    
    <view class="wc">
      <view style="width: 100%;display: flex;justify-content: space-between;">
        <view style="width: 50%;">座位</view>
        <view style="width: 50%;">票价</view>
      </view>
      <block v-if="selseats">
        <view v-for="(item,index) in selseats" :key="index" style="width: 100%;padding: 20rpx 0;" :style="index>0?'border-top:2rpx dashed #ddd':''">
          <view style="display: flex;justify-content: space-between;">
            <view style="width: 50%;">{{item.fname}}</view>
            <view style="width: 50%;">{{item.price}}元</view>
          </view>
          <view style="line-height: 80rpx;">
            <view class="peisong-item" >
              <view style="width: 150rpx;">真实姓名</view><input @input="inputSeat" :data-index="index" data-field="realName" :value="item.realName" placeholder="请输入真实姓名" placeholder-style="line-height: 80rpx;height: 80rpx" class="peisong-item-input">
            </view>
            <view class="peisong-item">
            	<view style="width: 150rpx;">证件类型</view>
            	<picker @change="seatPickerChange" :data-index="index" :value="item.type" :range="typedata" style="width: 100%;line-height: 80rpx;height: 80rpx;border-bottom:2rpx solid #f1f1f1">
                <view style="display: flex;justify-content: flex-start;align-items: center;width: 100%;">
                  <view v-if="item.typename" style="display: inline-block;width: 100%;">{{item.typename}}</view>
                  <view v-else style="color:#BBBBBB;width: 100%;">请选择证件类型</view>
                  <image :src="pre_url+'/static/img/arrowright.png'" style="width:28rpx ;height: 28rpx;"></image>
                </view>
            	</picker>
            </view>
            <view class="peisong-item" >
              <view style="width: 150rpx;">证件号码</view><input  @input="inputSeat" :data-index="index" data-field="certNo" :value="item.certNo" placeholder="请输入证件号码" placeholder-style="line-height: 80rpx;height: 80rpx" class="peisong-item-input">
            </view>
          </view>
        </view>
      </block>
    </view>
    
    <view class="wc">
      <view class="peisong-item">
        <view style="font-weight: bold;font-size: 32rpx;">配送方式</view>
      </view>
      <view style="margin: 20rpx 0;">
        上门自取<text v-if="perform.addreass">：{{perform.addreass}}</text>
      </view>
      <view style="line-height: 80rpx;">
        <view class="peisong-item" >
          <view style="width: 150rpx;">联系人</view><input name="linkName" @input="inputLinkName" :value="linkName" style="line-height: 80rpx;width: 100%;" placeholder="请输入联系人姓名" placeholder-style="line-height: 80rpx;height: 80rpx" class="peisong-item-input">
        </view>
        <view class="peisong-item" >
          <view style="width: 150rpx;">联系电话</view><input name="tel" @input="inputTel" :value="tel" style="line-height: 80rpx;width: 100%;" placeholder="请输入联系人电话" placeholder-style="line-height: 80rpx;height: 80rpx" class="peisong-item-input">
        </view>
        <block v-if="needcert">
          <view class="peisong-item">
            <view style="width: 150rpx;">证件类型</view>
            <picker @change="bindPickerChange" :value="type" :range="typedata" style="width: 100%;line-height: 80rpx;height: 80rpx;border-bottom:2rpx solid #f1f1f1">
              <view style="display: flex;justify-content: flex-start;align-items: center;width: 100%;">
                <view v-if="typename" style="display: inline-block;width: 100%;">{{typename}}</view>
                <view v-else style="color:#BBBBBB;width: 100%;">请选择证件类型</view>
                <image :src="pre_url+'/static/img/arrowright.png'" style="width:28rpx ;height: 28rpx;"></image>
              </view>
            </picker>
          </view>
          <view class="peisong-item" >
            <view style="width: 150rpx;">证件号码</view><input name="certNo" @input="inputCertNo" :value="certNo" style="line-height: 80rpx;width: 100%;" placeholder="请输入联系人证件号码" placeholder-style="line-height: 80rpx;height: 80rpx" class="peisong-item-input">
          </view>
        </block>
      </view>
    </view>

    <view style="width: 100%; height:182rpx;"></view>
    <view class="footer  notabbarbot">

      <view style="display: flex;align-items: center;">
        <view class="text1 flex1">总计：
          <text style="font-weight:bold;font-size:32rpx">￥{{totalprice}}</text>
        </view>
        <button @tap="topay" class="op"  :style="{background:'linear-gradient(-90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'}" >
          提交订单</button>
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
		data(){
			return{
				isload:false,
        nodata:false,
				pre_url:app.globalData.pre_url,
        menuindex: -1,
				timeIndex:1,
        
        id:0,
        fids:'',

        perform:'',
        selseats:'',
        
        linkName:'',
        tel:'',
        typedata:['请选择','身份证'],
        type:1,
        typename:'身份证',
        certNo:'',
        needcert:false,
        
        totalprice:0,
        areaid:0,
			}
		},
    onLoad: function (opt) {
      var that = this;
    	that.opt = app.getopts(opt);
      
      that.id= that.opt.id || '';
      that.fids  = that.opt.fids || '';
      that.areaid = that.opt.areaid || 0;
      
      that.getdata();
    },
    onShow:function(){
    },
		methods:{
      getdata: function () {
        var that = this;
      	that.nodata = false;
      	that.nomore = false;
      	that.loading = true;
        app.post('ApiZhiyoubao/buy', {id:that.id,fids:that.fids,areaid:that.areaid}, function (res) {
      		that.loading = false;
          if(res.status == 1){
            that.perform  = res.perform;
            that.selseats = res.selseats;
            that.linkName = res.linkName || '';
            that.tel = res.tel || '';
            if(res.typedata) that.typedata = res.typedata;
            if(res.needcert) that.needcert = res.needcert;
            that.calculatePrice();
            that.loaded();
          }else if(res.status == 2){
            app.error(res.msg);
            setTimeout(function(){
              uni.navigateBack({ delta: 2 })
            },900)
          }else {
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
      calculatePrice:function(){
        var that = this;
        var totalprice = 0;
        var selseats = that.selseats;
        var len = selseats.length;
        for(var i=0;i<len;i++){
          totalprice += selseats[i]['price'];
        }
        totalprice = Math.round(totalprice*100)/100;
        if(totalprice<0) totalprice = 0;

        that.totalprice = totalprice;
      },
      inputSeat:function(e){
        var that = this;
        var val = e.detail.value;
        var index = e.currentTarget.dataset.index;
        var field = e.currentTarget.dataset.field;
        that.selseats[index][field] = val;
        
      },
      seatPickerChange: function (e) {
        var that = this;
        var val = e.detail.value;
        var index = e.currentTarget.dataset.index;
        that.selseats[index]['typename'] = that.typedata[val];
        that.selseats[index]['type'] = val;
      },
      inputLinkName:function(e){
        this.linkName = e.detail.value;
      },
      inputTel:function(e){
        this.tel = e.detail.value;
      },
      inputCertNo:function(e){
        this.certNo = e.detail.value;
      },
      bindPickerChange: function (e) {
        var that = this;
        var type = e.detail.value;
        that.typename = that.typedata[type];
        that.type = type;
      },
      topay:function(e) {
      	var that = this;

        if(that.ispost) return;
        that.ispost = true;
        app.showLoading('提交中');
      	app.post('ApiZhiyoubao/createOrder', {
          id:that.id,
          fids:that.fids,
      		linkName: that.linkName,
          tel:that.tel,
          certType:that.type,
          certNo:that.certNo,
          selseats:JSON.stringify(that.selseats),
          areaid:that.areaid
      	}, function(res) {
          app.showLoading(false);
          setTimeout(function(){
            that.ispost = false;
          },1000)
      		if (res.status == 1) {
      			app.goto('/pagesExt/pay/pay?id=' + res.payorderid);
      			return;
      		}else if (res.status == 3) {
      			app.alert(res.msg, function() {
      				app.goto(res.url);
      			});
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
  radio{transform:scale(.7);}
  checkbox{transform:scale(.7);}
	.page-view{width: 100%;height: 100vh;}

  .popup__content{width: 100%;height:auto;position: relative;}
  .popup__content .popup-close{position: fixed;right: 20rpx;top: 20rpx;width: 56rpx;height: 56rpx;z-index: 11;}
  .popup__content .popup-close image{width: 100%;height: 100%;}
  .popup_title{font-size: 36rpx;font-weight: bold;line-height: 80rpx;}
  .popup-item-title{width: 180rpx;text-align: center;padding: 20rpx 10rpx;border-right: 2rpx solid #f1f1f1;}
  .popup-item-content{width: 160rpx;padding: 20rpx  10rpx;width: 100%;color:#999}
  
  .xycss1{line-height: 40rpx;font-size: 24rpx;overflow: hidden;padding: 20rpx 0;background: #fff;display: flex;align-items: center;border-bottom: 2rpx solid #f1f1f1;}
  .xieyibox{width:100%;height:100%;position:fixed;top:0;left:0;z-index:99;background:rgba(0,0,0,0.7)}
  .xieyibox-content{width:90%;margin:0 auto;/*  #ifdef  MP-TOUTIAO */height:60%;/*  #endif  *//*  #ifndef  MP-TOUTIAO */height:80%;/*  #endif  */margin-top:20%;background:#fff;color:#333;padding:5px 10px 50px 10px;position:relative;border-radius:2px;}
  .footer {width: 96%;background: #fff;margin-top: 5px;position: fixed;left: 0px;bottom: 0px;padding: 0 2%;z-index: 8;box-sizing:content-box}
  .footer .text1 {height: 110rpx;line-height: 110rpx;color: #2a2a2a;font-size: 30rpx;}
  .footer .text1 text {color: #e94745;font-size: 32rpx;}
  .footer .op {width: 200rpx;height: 80rpx;line-height: 80rpx;color: #fff;text-align: center;font-size: 30rpx;border-radius: 44rpx}
  .footer .op[disabled] { background: #aaa !important; color: #666;}
  .footerTop {bottom: 110rpx; display:inline-block;font-size:22rpx;height:44rpx;line-height:44rpx;padding:0 20rpx}
  
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
  
  .orderinfo{width:94%;margin:0 3%;border-radius:8rpx;margin-top:16rpx;padding: 14rpx 3%;background: #FFF;}
  .orderinfo .item{display:flex;width:100%;padding:20rpx 0;border-bottom:1px dashed #ededed;overflow:hidden}
  .orderinfo .item:last-child{ border-bottom: 0;}
  .orderinfo .item .t1{width:200rpx;flex-shrink:0}
  .orderinfo .item .t2{flex:1;text-align:right}
  .orderinfo .item .red{color:red}
  .btn{ height:80rpx;line-height: 80rpx;width:90%;margin:0 auto;border-radius:40rpx;margin-top:40rpx;color: #fff;font-size: 28rpx;font-weight:bold}
  .stop-tag{border: 1px #9c9c9c solid;position: absolute;top:0rpx;left: 50%;transform: translateX(-50%);
  font-size: 24rpx;color: #7c7c7c;border-radius: 4rpx;padding: 0rpx 4rpx;white-space: nowrap;}
  
  .wc{width:95%;margin:0 auto;background-color: #fff;border-radius: 10rpx 10rpx;padding: 20rpx;margin-bottom: 20rpx;}
  .peisong-item{display: flex;justify-content: space-between;align-items: center;}
  
  .title2{color: #666;line-height: 40rpx;margin-top: 10rpx;}
  .content{background-color: #fff;border-radius: 8rpx;padding: 20rpx;margin-bottom: 20rpx;}
  .content-pic{width: 200rpx;max-height: 400rpx;border-radius: 4rpx;overflow: hidden;}
  .content-title{font-weight: bold;line-height: 50rpx;max-height: 100rpx;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:2;overflow:hidden;word-break: break-all;}
  .content-title2{color: #666;line-height: 40rpx;margin-top: 5rpx;}
  
  .peisong-item-input{line-height: 80rpx;width: 100%;height: 80rpx;border-bottom:2rpx solid #f1f1f1}
</style>