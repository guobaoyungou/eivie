<template>
  <view style="position: relative;width: 100%;">
    <block v-if="isload">
      <view class="wc" style="position: relative;z-index: 9;margin-top: 20rpx;"> 
        <view style="overflow: hidden;border-bottom:2rpx solid #F5F5F7;">
          <view class="mendian_name">
            <text v-if="mendian_tip" @tap="getlocation">{{mendian_tip}}</text>
            <text v-else>{{mendian?mendian.name:'无门店'}}</text>
          </view>
          <view @tap="goto" :data-url="'selmendian?bid='+bid" class="mendian_tap" :style="{background:'linear-gradient(-90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'}">
            切换门店
            <image :src="pre_url+'/static/img/arrowright.png'" style="width:16rpx ;height: 20rpx;margin-left: 10rpx;"></image>
          </view>
        </view>
        <view @tap="openLocation" :data-latitude="mendian.latitude" :data-longitude="mendian.longitude" :data-company="mendian.name" :data-address="mendian.address" class="mendian_address">
          <image :src="pre_url+'/static/img/address3.png'" class="mendian_local"></image>
          {{mendian?mendian.area+' '+mendian.address:'无'}}
        </view>
        <view v-if="mendian&&mendian.tel" @tap="callphone" :data-phone="mendian?mendian.tel:''" style="overflow: hidden;color: #6E728B;line-height: 50rpx;padding: 20rpx 0;border-bottom:2rpx solid #F5F5F7;">
          <image :src="pre_url+'/static/img/peisong/tel2.png'" class="mendian_tel" ></image>
          {{mendian?mendian.tel:'无'}}
        </view>
      </view>
      <block v-if="products">
        <view  v-for="(item,index) in products" :index="index" :key="index" class="wc" style="margin-top: 20rpx;">
          <view style="width: 100%;line-height: 50rpx;border-bottom: 2rpx solid #eeepadding: 10rpx 0;display: flex;justify-content: space-between;">
            <view class="proname">{{item.name}}</view>
            <view v-if="item.ggid<=0" @tap="buydialogChange" :data-proid="item.id" :data-index="index" :style="'color:'+t('color1')">
              <text>请选规格</text>
            </view>
            <view v-else @tap="buydialogChange" :data-proid="item.id" :data-index="index" style="color:red">
              <text>重选规格</text>
            </view>
          </view>
          <view style="width: 100%;line-height: 50rpx;border-bottom: 2rpx solid #eee;padding: 10rpx 0;display: flex;justify-content: space-between;">
            <view class="proggname" >规格：{{item.ggname}}</view>
            <view v-if="item.ggpricetype == 1">
              ￥{{item.ggprice}}
            </view>
          </view>
          <view style="width: 100%;display: flex;justify-content: space-between;">
            <view style="margin-top: 10rpx;line-height:70rpx;display: flex;">
              数量：{{item.num}}
            </view>
            <view @tap="delpro" :data-index="index" style="margin-left:20rpx;margin-top: 12rpx;;max-width:160rpx;height:60rpx;line-height:60rpx;color:#333;background:#fff;border:2rpx solid #cdcdcd;border-radius:6rpx;text-align:center;padding: 0 20rpx;">
             删除
            </view>
          </view>
        </view>
      </block>
      <view class="wc" style="margin-top: 20rpx;display: flex;"> 
        <view @tap="opencb" style="width: 100%;height: 80rpx;line-height: 80rpx;border: 2rpx solid #eee;text-align: center;font-size: 30rpx;">
          继续验券
        </view>
      </view>
      <view v-if="step" style="width: 710rpx;margin: 0 auto;margin-top: 20rpx;">
        <image :src="step" mode="widthFix" style="width: 100%;height: auto;border-radius: 8rpx;"></image>
      </view>
      <buydialog v-if="buydialogShow" :proid="proid" :index="index" btntype="1" :showbuynum="false" :needaddcart="false" @buydialogChange="buydialogChange" :menuindex="menuindex" @addcart="addcart" ></buydialog>
      
      <view class="add_height" style="width: 100%;height: 130rpx;"></view>
      <view class="add_btn" style="position: fixed;bottom: 0;width: 100%;">
        <view v-if="products && products.length>0" @tap="gobuy" class="duihuan" :style="{background:'linear-gradient(-90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'}">
          确定兑换
        </view>
        <view v-else class="duihuan" style="background-color:#ccc">
          确定兑换
        </view>
      </view>
    </block>
    <uni-popup id="dialogCb" ref="dialogCb" type="dialog">
    	<view class="uni-popup-dialog">
    		<view class="uni-dialog-title">
    			<text class="uni-dialog-title-text">券信息输入</text>
    		</view>
    		<view class="uni-dialog-content">
    			<view style="width: 680rpx;"> 
    				<view class="flex-y-center flex-x-center" style="margin:20rpx 0rpx;">
    					<input type="text" placeholder="请输入抖音兑换码" :value="dycode" @input="setcbinfo" style="border: 1px #eee solid;padding: 0 10rpx;height:70rpx;line-height:70rpx;border-radius:4px;flex:1;font-size:28rpx;"/>
              <image  @tap="saoyisao" :src="pre_url+'/static/img/admin/saoyisao.png'" style="width: 80rpx;height: 80rpx;"></image>
    				</view>
    			</view>
    		</view>
    		<view class="uni-dialog-button-group">
    			<view class="uni-dialog-button" @click="dialogCbClose">
    				<text class="uni-dialog-button-text">取消</text>
    			</view>
    			<view class="uni-dialog-button uni-border-left" @tap="confirmdycode()">
    				<text class="uni-dialog-button-text uni-button-color">确定</text>
    			</view>
    		</view>
    	</view>
    </uni-popup>
    
    <dp-tabbar :opt="opt" @getmenuindex="getmenuindex"></dp-tabbar>
    <popmsg ref="popmsg"></popmsg>
    <loading v-if="loading"></loading>
    <wxxieyi></wxxieyi>
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
				pre_url: app.globalData.pre_url,
				platform: app.globalData.platform,
        statusBarHeight:40,
        bid:0,
        headpic:'',
        mdid:0,
        mdname    : '',
        latitude  : '',
        longitude : '',
        needlocation: false,
        
        mendian : '',
        mendian_tip:'',
        step:'',
        
        dycode:'',
        codes:[],
        encrypted_datas:[],
        certificate_ids:[],
        products:'',
        buydialogShow:false,
        proid:0,
        index:-1
			}
		},
		onLoad: function(opt) {
      var that = this;
			var opt  = app.getopts(opt);
      that.opt = opt;
      that.bid = opt.bid || 0;
      that.$refs.dialogCb.open();
		},
		onShow:function(opt){
      var that = this;
      var mdid =  app.getCache('mdid');
      if(mdid){
        that.mdid =  mdid
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
      that.getdata();
		},
		onPullDownRefresh: function(e) {
			this.getdata();
		},
		methods: {
			getdata: function() {
				var that = this;
        var latitude = that.latitude;
        var longitude = that.longitude;
				that.loading = true;
				app.post('ApiDouyinGroupbuy/index', {
          bid:that.bid,
          mdid:that.mdid,
					latitude: latitude,
					longitude: longitude,
				}, function(res) {
					that.loading = false;
					if (res.status == 1) {
            that.headpic     = res.headpic;
            that.mendian     = res.mendian;
            that.mendian_tip = res.mendian_tip;
            if(that.mendian){
              that.mdid =  that.mendian.id?that.mendian.id:0;
              app.setCache('mdid', that.mdid,60);
              
              that.mdname =  that.mendian.name?that.mendian.name:'';
              app.setCache('mdname', that.mdname,60);
            }
            if (!that.latitude|| !that.longitude) {
            	app.getLocation(function(res) {
            		that.latitude  = res.latitude;
            		that.longitude = res.longitude;
                app.setCache('latitude', res.latitude,60);
                app.setCache('longitude', res.longitude,60);
                if(that.latitude && that.longitude){
                  that.getdata();
                }
            	},function(res){
            		console.error(res);
            	});
            }
            that.step = res.step;
						that.loaded();
            
          } else {
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
      getlocation:function(){
        var that = this;
        app.getLocation(function(res) {
        	that.latitude  = res.latitude;
        	that.longitude = res.longitude;
          app.setCache('latitude', res.latitude,60);
          app.setCache('longitude', res.longitude,60);
          if(that.latitude && that.longitude){
            that.getdata();
          }
        },function(res){
        	console.error(res);
        });
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
      openLocation:function(e){
      	//console.log(e)
      	var latitude = parseFloat(e.currentTarget.dataset.latitude)
      	var longitude = parseFloat(e.currentTarget.dataset.longitude)
      	var address = e.currentTarget.dataset.address
      	uni.openLocation({
      	 latitude:latitude,
      	 longitude:longitude,
      	 name:address,
      	 scale: 13
       })		
      },
      opencb:function(){
        var that = this;
        that.$refs.dialogCb.open();
      },
      setcbinfo:function(e){
        var that = this;
        that.dycode = e.detail.value;
      },
      confirmdycode:function(code =''){
        var that  = this;
        var encrypted_datas = that.encrypted_datas;
        var codes = that.codes;
        if(code){
          var type   = 2;
          var dycode = code;
          //查询是否已存在
          if(encrypted_datas && encrypted_datas.length>0){
            var pos = encrypted_datas.indexOf(dycode);
            if(pos===0 || pos>0){
              app.alert('扫码重复');
              return;
            }
          }
        }else{
          var type   = 1;
          var dycode = that.dycode;
          //查询是否已存在
          if(codes && codes.length>0){
            var pos = codes.indexOf(dycode);
            if(pos===0 || pos>0){
              app.alert('团购券码重复');
              return;
            }
          }
        }

        if(!dycode){
          app.alert('请输入抖音兑换码');
          return;
        }
        app.showLoading('提交中');
        var data = {
          bid:that.bid,
          mdid:that.mdid,
          type:type,
          dycode: dycode,
          encrypted_datas:encrypted_datas,
          codes:codes,
          certificate_ids:that.certificate_ids,
        }
        app.post('ApiDouyinGroupbuy/getdycodeinfo',data, function (res) {
          app.showLoading(false);
          if(res.status == 1){
            that.codes           = res.codes;
            that.encrypted_datas = res.encrypted_datas;
            that.certificate_ids = res.certificate_ids;

            var products  = that.products;
            if(products && products.length>0){
              that.products = products.concat(res.products);
            }else{
              that.products = res.products;
            }
            that.$refs.dialogCb.close();
          }else{
            app.alert(res.msg);
            return;
          }
        });
      },
      dialogCbClose:function(){
        var that = this;
        that.$refs.dialogCb.close();
      },
      saoyisao: function (d) {
        var that = this;
        app.showLoading();
      	if(app.globalData.platform == 'h5'){
          app.showLoading(false);
      		app.alert('请使用微信扫一扫功能扫码核销');return;
      	}else if(app.globalData.platform == 'mp'){
      		var jweixin = require('jweixin-module');
      		jweixin.ready(function () {   //需在用户可能点击分享按钮前就先调用
      			jweixin.scanQRCode({
      				needResult: 1, // 默认为0，扫描结果由微信处理，1则直接返回扫描结果，
      				scanType: ["qrCode","barCode"], // 可以指定扫二维码还是一维码，默认二者都有
      				success: function (res) {
                app.showLoading(false);
      					var content = res.resultStr; // 当needResult 为 1 时，扫码返回的结果
                if(!content){
                  app.alert('扫码错误');
                  return;
                }
                that.confirmdycode(content);
      				},
      				fail:function(err){
                app.showLoading(false);
      					app.error(err.errMsg);
      				}
      			});
      		});
      	}else{
          
      		uni.scanCode({
      			success: function (res) {
              app.showLoading(false);
              console.log(res)
              var content = res.result;
              if(!content){
                app.alert('扫码错误');
                return;
              }
              that.confirmdycode(content);
      			},
      			fail:function(err){
              app.showLoading(false);
      				app.error(err.errMsg);
      			}
      		});
          
      	}
      },
      buydialogChange: function (e) {
        var that = this;
        if(e){
          that.proid = e.currentTarget.dataset.proid;
          that.index = e.currentTarget.dataset.index;
        }
      	that.buydialogShow = !that.buydialogShow;
      },
      addcart:function(e){
        var that = this;
        var proid = e.proid;
        var ggid  = e.ggid;
        var ggname= e.ggname;
        var ggprice   = e.ggprice;
        if(that.proid == proid && that.index>=0){
          var index = that.index;
          var products = that.products;
          if(products){
            products[index]['ggid']   = ggid;
            products[index]['ggname'] = ggname;
            products[index]['ggprice'] = ggprice;
          }
          that.products = products;
        }
      },
      gobuy:function(e){
        var that = this;
        var products = that.products;
        if(products && products.length>0){
          var encrypted_datas = that.encrypted_datas;
          var codes           = that.codes;
          
          if(encrypted_datas && encrypted_datas.length>0 || codes && codes.length>0){
            app.confirm('确定兑换吗?', function () {
              var len = products.length;
              var prodata = '';
              for(var i=0;i<len;i++){
                if(products[i]['ggid']==0){
                  app.alert('有商品规格未选择');
                  return;
                }
                if(prodata){
                  prodata += '-'+products[i]['id'] + ',' + products[i]['ggid'] + ',' + products[i]['certificate_id'];
                }else{
                  prodata += products[i]['id'] + ',' + products[i]['ggid'] + ',' + products[i]['certificate_id'];
                }
              }
              if(encrypted_datas && encrypted_datas.length>0){
                encrypted_datas = encrypted_datas.join(',');
              }else{
                encrypted_datas='';
              }
              if(codes && codes.length>0){
                codes = codes.join(',');
              }else{
                codes = '';
              }
              app.goto('buy?bid='+that.bid+'&encrypted_datas='+encrypted_datas+'&codes='+codes+'&prodata='+prodata);
            })
          }else{
            app.alert('无可兑换团券');
          }
        }else{
          app.alert('无可兑换商品');
        }
      },
      delpro:function(e){
        var that = this;
        var index = e.currentTarget.dataset.index;
        var products = that.products;
        if(products && products.length>0){
            var deli = -1;
            var len = products.length;
            for(var i=0;i<len;i++){
              if(i == index){
                deli = i;
                break;
              }
            }
            if(deli>=0){
              //删除
              products.splice(deli,1);
              that.products = products;
              if(products){
                that.deal_codes(products);
              }
            }
        }
      },
      deal_codes:function(products){
        var that = this;
        var codes           = [];
        var encrypted_datas = [];
        var certificate_ids = [];
        var len = products.length;
        for(var i=0;i<len;i++){
          if(products[i]['code']){
            var pos = codes.indexOf(products[i]['code']);
            if( pos == -1){
              codes.push(products[i]['code']);
            }
          }
          if(products[i]['encrypted_data']){
            var pos1 = encrypted_datas.indexOf(products[i]['encrypted_data']);
            if(pos1 == -1){
              encrypted_datas.push(products[i]['encrypted_data']);
            }
          }
          if(products[i]['certificate_id']){
            var pos2 = certificate_ids.indexOf(products[i]['certificate_id']);
            if(pos2 == -1){
              certificate_ids.push(products[i]['certificate_id']);
            }
          }
        }
        that.codes           = codes;
        that.encrypted_datas = encrypted_datas;
        that.certificate_ids = certificate_ids;
      }
	}
}
</script>
<style>
  .headbg{width: 100%;height: 530rpx;position: absolute;top:0;left: 0;}
  .headtitle{text-align: center;line-height: 80rpx;font-size:32rpx;font-weight: bold;color: #fff;}
  .wc{width: 710rpx;margin: 0 auto;background-color: #fff;border-radius: 8rpx;padding: 20rpx;}
  
  .mendian_name{display: inline-block;font-size: 30rpx;font-weight: bold;width: 510rpx;text-overflow: ellipsis;white-space: nowrap;overflow: hidden;line-height: 60rpx;}
  .mendian_tap{position: absolute;top:0;right: 0;text-align: center;font-size: 24rpx;width:180rpx ;line-height: 60rpx;color: #fff;border-radius: 0 6rpx 0 30rpx;}
  .mendian_address{line-height: 50rpx;white-space: pre-wrap;overflow: hidden;padding: 10rpx 0;color: #6E728B;}
  .mendian_local{width: 30rpx;height: 30rpx;float: left;margin-top: 6rpx;}
  .mendian_tel{width: 22rpx;height: 26rpx;float: left;margin-top:12rpx;margin-right: 10rpx;}

  .center_dh{width: 25%;line-height: 50rpx;text-align: center;}
  .center_img{width: 168rpx;height: 90rpx;}
  
  .uni-popup-dialog {width: 700rpx;border-radius: 5px;background-color: #fff;}
  .uni-dialog-title {display: flex;flex-direction: row;justify-content: center;padding-top: 15px;padding-bottom: 5px;}
  .uni-dialog-title-text {font-size: 16px;font-weight: 500;}
  .uni-dialog-content {display: flex;flex-direction: row;justify-content: center;align-items: center;padding: 5px 15px 15px 15px;}
  .uni-dialog-content-text {font-size: 14px;color: #6e6e6e;}
  .uni-dialog-button-group {display: flex;flex-direction: row;border-top-color: #f5f5f5;border-top-style: solid;border-top-width: 1px;}
  .uni-dialog-button {display: flex;flex: 1;flex-direction: row;justify-content: center;align-items: center;height: 45px;}
  .uni-border-left {border-left-color: #f0f0f0;border-left-style: solid;border-left-width: 1px;}
  .uni-dialog-button-text {font-size: 14px;}
  .uni-button-color {color: #007aff;}
  .proname{float: left;width: 500rpx;white-space:break-spaces;word-break: break-all;}
  .proggname{width: 500rpx;white-space:break-spaces;word-break: break-all;}
  
  .duihuan{width: 600rpx;margin:20rpx auto;line-height:80rpx;text-align: center;border-radius: 80rpx;border: 2rpx solid #eee;color: #fff;}
  @supports(bottom: env(safe-area-inset-bottom)) {
    .add_height {
      padding-bottom: env(safe-area-inset-bottom);
    }
    .add_btn {
      padding-bottom: env(safe-area-inset-bottom);
    }
  }
</style>
