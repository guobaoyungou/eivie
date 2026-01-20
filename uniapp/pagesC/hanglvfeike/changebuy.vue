<template>
	<view class="page-view" v-if="isload">
    <view style="background-color: #fff;padding: 20rpx;">
      <view style="display: flex;align-items: center;margin: 25rpx 0;justify-content: space-between;">
        <view>
          <view style="font-weight: bold;font-size: 32rpx;text-align: center;">
            {{changesdata.departAirport}}{{changesdata.departTerminal}} - {{changesdata.arriveAirport}}{{changesdata.arriveTerminal}} {{changeTime}} {{week}}
          </view>
        </view>
      </view>
    </view>
    <view style="background-color: #fff;border-radius: 10rpx 10rpx;padding: 20rpx;margin: 20rpx;">
      <view style="display: flex;justify-content: space-between;align-items: center;">
        <view style="font-weight: bold;font-size: 32rpx;">改签人员<text v-if="changesdata && changesdata.showstock" style="font-size: 24rpx;">(剩{{changesdata.stock}}张)</text></view>

      </view>
      <view v-if="userdata && userdata.length>0" style="padding:20rpx 0;">
        <view v-for="(item2, index) in userdata" :key="index" style="display: flex;align-items: center;" >
          <!-- <view @tap="deluser" :data-index="index" style="height:34rpx;width:34rpx;">
            <image :src="pre_url+'/static/img/ico-del.png'" style="background:#fff;height:34rpx;width:34rpx;border-radius:14rpx"/>
          </view> -->
          <view style="margin-left: 20rpx;">
            <view>{{item2.name}}</view>
            <view style="color: #999;">{{item2.typename}} {{item2.usercard}}</view>
          </view>
        </view>
      </view>
			<view style="line-height: 80rpx;">
			  <view style="display: flex;justify-content: space-between;align-items: center;">
			    <view style="width: 150rpx;">改签原因</view>
					<textarea @input="inputReason" placeholder="请输入改签原因" placeholder-style="color:#999;padding: 10rpx;" name="reason" style="width: 100%;border: 2rpx solid #f1f1f1;border-radius: 4rpx;padding: 10rpx;"></textarea>
			  </view>
			</view>
    </view>
    

    <view v-if="changesdata.showFee || changesdata.showchildFee"  style="background-color: #fff;border-radius: 10rpx 10rpx;padding: 20rpx;margin: 20rpx;">
      <view style="display: flex;justify-content: space-between;align-items: center;">
        <view style="font-weight: bold;font-size: 32rpx;">费用明细</view>
      </view>
      <view v-if="changesdata.showFee" style="line-height: 80rpx;">
        <view v-if="changesdata.gqFee>0" style="display: flex;justify-content: space-between;align-items: center;">
          <view style="width: 150rpx;">手续费</view>
          <view :style="'color:'+t('color1')">￥{{changesdata.gqFee}}/人</view>
        </view>
        <view v-if="changesdata.upgradeFee>0" style="display: flex;justify-content: space-between;align-items: center;">
          <view style="width: 150rpx;">升舱费</view>
          <view :style="'color:'+t('color1')">￥{{changesdata.upgradeFee}}/人</view>
        </view>
        <view v-if="changesdata.allFee>0" style="display: flex;justify-content: space-between;align-items: center;">
          <view style="width: 150rpx;">总收费</view>
          <view :style="'color:'+t('color1')">￥{{changesdata.allFee}}/人</view>
        </view>
      </view>
			
			<view v-if="changesdata.showchildFee" style="line-height: 80rpx;">
			  <view v-if="changesdata.childGqFee>0" style="display: flex;justify-content: space-between;align-items: center;">
			    <view style="width: 150rpx;">儿童手续费</view>
			    <view :style="'color:'+t('color1')">￥{{changesdata.childGqFee}}/人</view>
			  </view>
			  <view v-if="changesdata.childUpgradeFee>0" style="display: flex;justify-content: space-between;align-items: center;">
			    <view style="width: 150rpx;">儿童升舱费</view>
			    <view :style="'color:'+t('color1')">￥{{changesdata.childUpgradeFee}}/人</view>
			  </view>
			  <view v-if="changesdata.childAllFee>0" style="display: flex;justify-content: space-between;align-items: center;">
			    <view style="width: 150rpx;">儿童总费用</view>
			    <view :style="'color:'+t('color1')">￥{{changesdata.childAllFee}}/人</view>
			  </view>
			</view>
    </view>
    
    <view v-if="xieyidata && xieyidata.length>0" style="width: 100%; height:290rpx;"></view>
    <view v-else style="width: 100%; height:182rpx;"></view>
    <view class="footer  notabbarbot">
      <view style="display: flex;align-items: center;padding: 20rpx 0;">
        <!-- <view class="text1 flex1">总计：
          <text style="font-weight:bold;font-size:32rpx">￥{{totalprice}}</text>
        </view> -->
        <button @tap="topay" class="op" style="width: 50%;"  :style="{background:'linear-gradient(-90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'}" >
          确定申请改签
				</button>
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
        
				orderid : 0,
				ogids :'',
        changesdata:'',
				changeTime:'',
				code:'',
				week:'',

        userdata:[],//旅客

				gqFee:0,
				upgradeFee:0,
				allFee:0,

				childGqFee:0,
				childUpgradeFee:0,
				childAllFee:0,
				
				reason:'',
        totalprice:0,
        ispost:false,
			}
		},
    onLoad: function (opt) {
      var that = this;
    	that.opt = app.getopts(opt);
      
			that.orderid= that.opt.orderid || '';
			that.ogids = that.opt.ogids || '';
			that.changeTime = that.opt.changeTime || '';
			that.code = that.opt.code || '';
      var changesdata = app.getCache('changesdata') || '';
      if(!changesdata){
        app.error('数据已失效');
        setTimeout(function(){
         uni.navigateBack({ delta: 2 })
        },900)
        return;
      }
      that.changesdata = changesdata;
			
      that.gqFee = changesdata.gqFee || 0;
      that.upgradeFee = changesdata.upgradeFee || 0;
      that.allFee = changesdata.allFee || 0;
      
      that.childGqFee = changesdata.childGqFee || 0;
      that.childUpgradeFee = changesdata.childUpgradeFee || 0;
      that.childAllFee = changesdata.childAllFee || 0;

      that.getdata();
    },
		methods:{
      getdata: function () {
        var that = this;
      	that.nodata = false;
      	that.nomore = false;
      	that.loading = true;
        app.post('ApiHanglvfeike/buychange', {orderid:that.orderid,ogids:that.ogids,changeTime:that.changeTime}, function (res) {
      		that.loading = false;
          if(res.status == 1){
            that.userdata = res.userdata || '';
						that.week = res.week || '';
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
        totalprice = parseFloat(that.allFee) + parseFloat(that.childAllFee)
        
        var num = that.userdata?that.userdata.length:0;
        totalprice = totalprice*num;
        totalprice = Math.round(totalprice*100)/100;
        if(totalprice<0) totalprice = 0;

        that.totalprice = totalprice;
      },
			inputReason:function(e){
				this.reason = e.detail.value;
			},
      topay:function(e) {
      	var that = this;

        if(that.ispost) return;
        that.ispost = true;
        app.showLoading('提交中');
      	app.post('ApiHanglvfeike/createchangeOrder', {
      		orderid:that.orderid,
					ogids:that.ogids,
					changeTime:that.changeTime,
					uniqKey:that.changesdata.uniqKey,
					reason:that.reason,
					code:that.code
      	}, function(res) {
          app.showLoading(false);
          setTimeout(function(){
            that.ispost = false;
          },1000)
      		if (res.status == 1) {
            if(res.payorderid && res.payorderid>0){
              app.goto('/pagesExt/pay/pay?id=' + res.payorderid);
            }else{
              app.success(res.msg)
              setTimeout(function(){
                app.goto('/pagesC/hanglvfeike/orderlist','redirect');
              },900)
            }
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
      }
		}
	}
</script>

<style>
  radio{transform:scale(.7);}
  checkbox{transform:scale(.7);}
	.page-view{background: #fbf6f6;width: 100%;height: 100vh;}
	.top-view{width: 100%;background: #fff;}
	.top-view .address-view{justify-content: center;}
	.top-view .address-view .address-text{font-size: 40rpx;color: #676767;flex: 1;}
	.top-view .address-view .fangxiang-icon{width: 50rpx;height:50rpx;margin: 50rpx 40rpx;}
	.top-view .address-view .fangxiang-icon image{width: 50rpx;height:50rpx;}
	.top-view .time-view{width: 100%;justify-content: space-between;padding-bottom: 5rpx;}
	.top-view .time-view .time-left-view{width: calc(100% - 130rpx);}
	.top-view .time-view .time-left-view .time-options{width: 120rpx;height: 130rpx;border-radius: 10rpx;justify-content: space-between;padding: 5rpx;}
	.top-view .time-view .time-left-view .time-options-active{background-color: #af1e24;color: #fff !important;}
	.top-view .time-view .time-left-view .time-options-active .time-title{color: #fff !important;}
	.top-view .time-view .time-left-view .time-options-active .time-num{color: #fff !important;}
	.time-options .time-title{width: 100%;text-align: center;font-size: 26rpx;color: #838383;}
	.time-options	.time-num{color: #333;font-size: 26rpx;width: 100%;text-align: center;}
	.top-view .time-view .time-right-view{width: 100rpx;height: 130rpx;box-shadow: -4px 0px 14px -14px rgba(0,0,0,.7);}
	.top-view .time-view .time-right-view .rili-icon{width: 46rpx;height: 46rpx;}
	.jipiao-list-view{width: 100%;}
	.jipiao-list-view .jipiao-options{background: #fff;width: 92%;margin: 10rpx auto;border-radius: 16rpx;padding: 25rpx;}
	.jipiao-list-view .jipiao-options .info-view{width: 100%;justify-content: space-between;align-items: center;}
	.jipiao-list-view .jipiao-options .info-view .info-touxiang{width: 50rpx;height: 50rpx;}
	.jipiao-list-view .jipiao-options .info-view .info-touxiang image{width: 100%;height: 100%;}
	.jipiao-list-view .jipiao-options .info-view .info-details-view{}
	.info-details-view .location-icon{width: 120rpx;position: relative;margin: 0rpx 25rpx 15rpx;}
	.info-details-view .location-icon image{width: 120rpx;}
	.info-details-view .location-icon .stop-tag{border: 1px #9c9c9c solid;position: absolute;bottom: -25rpx;left: 50%;transform: translateX(-50%);
	font-size: 24rpx;color: #7c7c7c;border-radius: 4rpx;padding: 0rpx 4rpx;white-space: nowrap;}
	.info-details-view .location-view{align-items: center;}
	.info-details-view .location-view .location-time{font-size: 44rpx;color: #333;font-weight: bold;}
	.info-details-view .location-view .location-name{font-size: 26rpx;color: #676767;margin-top: 5rpx;}
	.jipiao-list-view .jipiao-options .info-view .price-view{}
	.price-view .price-name{font-size: 26rpx;color: #353535;margin-top: 5rpx;}
	.price-view .price-num{color: #ff771b;font-weight: bold;}
	.jipiao-list-view .jipiao-options .jipiao-introduce{width: 100%;text-align: center;font-size: 26rpx;color: #676767;padding-top: 15rpx;}
	/* 共享售卖 */
	.shared-selling-view{border-radius: 16rpx;padding: 25rpx;width: 100%;margin-top: 15rpx;background: #f9f9f9;}
	.shared-selling-view .shared-title{width: 100%;padding-bottom: 15rpx;}
	.shared-selling-view .shared-title .feiji-icon{width: 28rpx;height: 28rpx;margin-right: 5rpx;}
	.shared-selling-view .shared-title .feiji-icon image{width: 100%;height: 100%;font-size: 28rpx;color: #676767;}
	.shared-selling-view .shared-title .title-text-view{white-space: nowrap;overflow: hidden;text-overflow: ellipsis;}
	.shared-selling-view .sell-options{width: 100%;border-bottom: 1px #eeefef solid;align-items: center;justify-content: space-between;padding: 20rpx 15rpx;}
	.shared-selling-view .sell-options .sell-left-view{justify-content: flex-start;}
	.sell-left-view .sell-touxiang{width: 28rpx;height: 28rpx;margin-right: 10rpx;}
	.sell-left-view .sell-touxiang image{width: 100%;height: 100%;}
	.sell-left-view .sell-name{font-size: 26rpx;color: #676767;}
	.shared-selling-view .sell-options .sell-price{font-size: 30rpx;color: #ff771b;}
	.shared-selling-view .sell-options .jiantou-icon{width: 28rpx;height: 28rpx;margin-left: 5rpx;}
	.shared-selling-view .more-view{width: 100%;align-items: center;justify-content: center;padding: 15rpx 0rpx 0rpx;font-size: 28rpx;color: #d6d6d6;}
	.shared-selling-view .more-view image{width: 40rpx;height: 40rpx;margin-left: 10rpx;}
  
  .popup__content{width: 100%;height:auto;position: relative;}
  .popup__content .popup-close{position: fixed;right: 20rpx;top: 20rpx;width: 56rpx;height: 56rpx;z-index: 11;}
  .popup__content .popup-close image{width: 100%;height: 100%;}
  .popup_title{font-size: 36rpx;font-weight: bold;line-height: 80rpx;}
  .popup-item-title{width: 180rpx;text-align: center;padding: 20rpx 10rpx;border-right: 2rpx solid #f1f1f1;}
  .popup-item-content{width: 160rpx;padding: 20rpx  10rpx;width: 100%;color:#999}
  
  .xycss1{line-height: 60rpx;font-size: 24rpx;overflow: hidden;padding: 20rpx 0;background: #fff;display: flex;align-items: center;border-bottom: 2rpx solid #f1f1f1;}
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
</style>