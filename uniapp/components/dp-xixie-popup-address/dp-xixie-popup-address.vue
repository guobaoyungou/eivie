<template>
    <view>
        <view @tap="close_address" style="width: 100%;height: 100%;background-color: #000;position: fixed;top:0;opacity: 0.45;z-index: 9999999;"></view>
        <view class="pa_view">
            <view @tap="close_address" class="pa_close">
                x
            </view>
            <view style="text-align: center;font-size: 30rpx;font-weight: bold;">请完善收货信息</view>
            <view style="display: flex;border-bottom: 2rpx solid #F6F7F9;">
                <view style="width: 140rpx;">所在地区</view>
                <view style="width: 410rpx;overflow: hidden;white-space: nowrap;">
                    <text v-if="area">{{area}}</text>
                    <text v-else style="color:#808080">所在地区</text>
                </view>
                <view @tap='selzb' style="width: 90rpx;overflow: hidden;">
                    <text :style="'color:'+t('color1')">切换</text>
                    <image src="/static/img/arrowright.png" style="width: 30rpx;height: 30rpx;float: right;margin-top: 24rpx;"></image>
                </view>
            </view>
            <view style="display: flex;border-bottom: 2rpx solid #F6F7F9;">
                <view style="width: 140rpx;">详细地址</view>
                <view style="width: 500rpx;overflow: hidden;white-space: nowrap;">
                    <input @input="inputVal" data-name="address" :value="address" placeholder="街道门牌,楼层等信息" placeholder-style="height:80rpx;line-height:80rpx;color:#808080" style="height:80rpx;line-height:80rpx;"/>
                </view>
            </view>
            <view style="display: flex;border-bottom: 2rpx solid #F6F7F9;">
                <view style="width: 140rpx;">姓名</view>
                <view style="width: 500rpx;overflow: hidden;white-space: nowrap;">
                    <input @input="inputVal" data-name="name" :value="name" placeholder="收货人姓名" placeholder-style="height:80rpx;line-height:80rpx;color:#808080" style="height:80rpx;line-height:80rpx;"/>
                </view>
            </view>
            <view style="display: flex;border-bottom: 2rpx solid #F6F7F9;">
                <view style="width: 140rpx;">电话</view>
                <!-- #ifdef  !MP-WEIXIN -->
                    <input @input="inputVal" data-name="tel" :value="tel" placeholder="收货人手机号" placeholder-style="height:80rpx;line-height:80rpx;color:#808080" style="height:80rpx;line-height:80rpx;"/>
                <!-- #endif -->
                <!-- #ifdef  MP-WEIXIN -->
                <view style="width: 360rpx;overflow: hidden;white-space: nowrap;">
                    <text v-if="tel">{{tel}}</text>
                    <text v-else style="color:#808080">收货人手机号</text>
                </view>
                <button class="pa_wx" :style="'color:'+t('color1')+';border:2rpx solid '+t('color1')" open-type="getPhoneNumber" @getphonenumber="getPhoneNumber">微信授权</button>
                <!-- #endif -->
            </view>
            <view @tap="saveAddress" class="pa_save" :style="{background:t('color1')}">
                保存并使用
            </view>
        </view>
    </view>
</template>
<script>
    var app = getApp();
	export default {
		data(){
			return {
                name :"",
                tel  :"",
                province :"",
                city     :"",
                district :"",
                area :"",
                address:"",
                latitude :"",
                longitude:"",
			}
		},
		props: {
            xixie_login:{default:false},
            xixie_location:{default:false},
            address_latitude :{default:''},
            address_longitude:{default:''},
            code:{default:''}
		},
        mounted:function(){
            this.getdata();
        },
		methods: {
            getdata:function(){
                var that = this;
                if(that.address_latitude && that.address_longitude){
                    that.latitude  = that.address_latitude;
                    that.longitude = that.address_longitude;
                    //获取省市
                    that.getAreaMendain();
                }else{
                    if(that.xixie_location){
                        app.getLocation(function(res) {
                            that.latitude  = res.latitude;
                            that.longitude = res.longitude;
                            //获取省市
                            that.getAreaMendain();
                        });  
                    }
                }
            },
            close_address: function() {
            	this.$emit('changePopupAddress',false);
            },
            selzb: function () {
                var that = this;
                if(!that.xixie_login){
                    var frompage = encodeURIComponent('/pages/index/index');
                    app.goto('/pages/index/login?frompage='+frompage);
                }else{
                    uni.chooseLocation({
                        success: function (res) {
                            that.area      = res.address;
                            that.latitude  = res.latitude;
                            that.longitude = res.longitude;
                            //获取省市
                            that.getAreaMendain(1);
                        },
                        fail: function (res) {
                            if (res.errMsg == 'chooseLocation:fail auth deny') {
                                //$.error('获取位置失败，请在设置中开启位置信息');
                                app.confirm('获取位置失败，请在设置中开启位置信息', function () {
                                    uni.openSetting({});
                                });
                            }
                        }
                    });
                }
            },
            inputVal:function(e){
                var that = this;
                if(!that.xixie_login){
                    var frompage = encodeURIComponent('/pages/index/index');
                    app.goto('/pages/index/login?frompage=' + frompage);
                }else{
                    var val  = e.detail.value;
                    var name = e.currentTarget.dataset.name;
                    that[name] = val;
                }
            },
            getPhoneNumber: function (e) {
            	var that = this;
                if(!that.xixie_login){
                    var frompage = encodeURIComponent('/pages/index/index');
                    app.goto('/pages/index/login?frompage=' + frompage);
                }else{
                    if(e.detail.errMsg == "getPhoneNumber:fail user deny"){
                        app.error('请同意授权获取手机号');return;
                    }
                    if(!e.detail.iv || !e.detail.encryptedData){
                        app.error('请同意授权获取手机号');return;
                    }
                    if(that.code){//用户允许授权
                        app.post('ApiIndex/bind_wxtel',{iv: e.detail.iv,encryptedData:e.detail.encryptedData,code:that.code},function(res){
                            if (res.status == 1) {
                                that.tel = res.data;
                            } else {
                                app.error(res.msg);
                            }
                            return;
                        })
                    }else{
                        wx.login({
                            success: function(res1){
                                that.code = res1.code
                                app.post('ApiIndex/bind_wxtel',{iv: e.detail.iv,encryptedData:e.detail.encryptedData,code:res1.code},function(res){
                                    if (res.status == 1) {
                                        that.tel = res.data;
                                    } else {
                                        app.error(res.msg);
                                    }
                                    return;
                                })
                            }
                        })
                    }
                    
                }
            },
            saveAddress:function(){
                var that = this;
                if(!that.xixie_login){
                    var frompage = encodeURIComponent('/pages/index/index');
                    app.goto('/pages/index/login?frompage=' + frompage);
                }else{
                    
                    if(!that.area){
                        app.alert('请选择所属区域');
                        return;
                    }
                    // if(!that.address){
                    //     app.alert('请填写详细地址');
                    //     return;
                    // }
                    if(!that.name){
                        app.alert('请填写姓名');
                        return;
                    }
                    if(!that.tel){
                        app.alert('请授权获取手机号');
                        return;
                    }
                    
                    var data = {
                        name      : that.name ,
                        tel       : that.tel,
                        province  : that.province,
                        city      : that.city,
                        district  : that.district,
                        area      : that.area,
                        address   : that.address,
                        latitude  : that.latitude,
                        longitude : that.longitude,
                        company   : '',
                        type      : 1
                    }
                    //验证是否登录
                    app.post('ApiAddress/addressadd', data, function (res) {
                        if (res.status == 1) {
                            app.success(res.msg);
                            that.$emit('changePopupAddress',false);
                            that.$emit('getdata');
                        } else {
                            if (res.msg) {
                                app.alert(res.msg);
                            } else {
                                app.alert('您无查看权限');
                            }
                        }
                    });
                }
            },
            getAreaMendain:function(type = 0){
                var that = this;
                var latitude   = that.latitude;
                var longitude  = that.longitude;
                app.post('ApiIndex/get_area_mendain', {latitude:latitude,longitude:longitude}, function (res) {
                    if (res.status == 1) {
                        if(res.data){
                            var data = res.data;
                            if(data.mendian){
                                that.$emit('setMendianData',data.mendian);
                            }
                            if(type == 0){
                                that.area = data.address;
                            }
                            that.province= data.province;
                            that.city    = data.city;
                            that.district= data.district;
                        }
                    } else {
                        if (res.msg) {
                            app.alert(res.msg);
                        } else {
                            app.alert('您无查看权限');
                        }
                  }
                });
            },
		}
	}
</script>
<style>
    .pa_close{float: right;width: 40rpx;height: 40rpx;border-radius: 50%;text-align: center;line-height: 30rpx;color: #A3A3A3;border: 2rpx solid #A3A3A3;}
    .pa_view{color:#303133;line-height: 80rpx;width: 700rpx;position: fixed;left: 25rpx;background-color: #fff;border-radius: 12rpx;z-index: 9999999;top:20%;padding:40rpx 20rpx}
    .pa_wx{width: 140rpx;height: 60rpx;line-height: 54rpx;text-align: center;border-radius: 40rpx;font-size: 24rpx;margin-top: 10rpx;}
    .pa_save{width: 400rpx;height: 80rpx;line-height: 80rpx;text-align: center;color: #fff;border-radius: 8rpx;margin: 0 auto;margin-top:40rpx;}
</style>