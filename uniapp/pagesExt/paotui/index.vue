<template>
    <view style="width: 100%;height: 100%;">
        <block v-if="isload">
            <map class="map" :longitude="longitude" :latitude="latitude" scale="16" :controls="controls" :markers="markers" @regionchange="regionchange"></map>
            <view style="width: 700rpx;margin: 0 auto;border-radius: 12rpx;background-color: #fff;padding-bottom: 20rpx;margin-bottom: 40rpx;">
                <view style="width: 500rpx;margin: 0 auto;text-align: center;font-weight: bold;height: 120rpx;">
                    <view class="btn" @tap="changeBtntype" data-btntype="1">
                        <view class="btn1" :style="btntype == 1?'background-color:'+t('color1'):'background-color:#fff'">
                            <text :style="btntype == 1?'color:#fff':'color:#000'">帮我送</text>
                        </view>
                    </view>
                    <view class="btn" @tap="changeBtntype" data-btntype="2">
                        <view class="btn1" :style="btntype == 2?'background-color:'+t('color1'):'background-color:#fff'">
                            <text :style="btntype == 2?'color:#fff':'color:#000'">帮我取</text>
                        </view>
                    </view>
                </view>
                <view v-if="btntype == 1" style="width: 660rpx;margin: 0 auto;">
                    <view style="overflow: hidden;padding: 20rpx;" @tap="goto"
                        :data-url="'address?gotype=11&addressId='+take_id+'&area='+take_area+'&address='+take_address+'&longitude='+take_longitude+'&latitude='+take_latitude">
                        <view class="black" ></view>
                        <view style="float: left;margin-left: 20rpx;float: left;width: 530rpx;">
                            <view class="first_title">
                                {{take_area?take_area:'填写取件地址'}}
                            </view>
                            <view style="font-size: 30rpx;color: #666666;line-height: 50rpx;">
                                {{take_address}}
                            </view>
                            <view style="font-size: 24rpx;color: #666666;line-height: 50rpx;">
                                {{take_name}} {{take_tel}}
                            </view>
                        </view>
                        <view style="float: right;width: 30rpx;margin-top: 30rpx;">
                            <image :src="pre_url+'/static/img/arrowright.png'"  style="width: 30rpx;height: 30rpx;"></image>
                        </view>
                    </view>
                    <view class="red_view"  @tap="goto"
                        :data-url="'address?gotype=12&addressId='+send_id+'&area='+send_area+'&address='+send_address+'&longitude='+send_longitude+'&latitude='+send_latitude">
                        <view class="red"></view>
                        <view style="float: left;margin-left: 20rpx;float: left;width: 530rpx;">
                            <view class="first_title">
                                {{send_area?send_area:'填写收件地址'}}
                            </view>
                            <view style="font-size: 30rpx;color: #666666;line-height: 50rpx;">
                                {{send_address}}
                            </view>
                            <view style="font-size: 24rpx;color: #666666;line-height: 50rpx;">
                                {{send_name}} {{send_tel}}
                            </view>
                        </view>
                        <view style="float: right;width: 30rpx;margin-top: 30rpx;">
                            <image :src="pre_url+'/static/img/arrowright.png'"  style="width: 30rpx;height: 30rpx;"></image>
                        </view>
                    </view>
                </view>

                <view v-if="btntype == 2" style="width: 660rpx;margin: 0 auto;">
                    <view class="red_view" style="margin-top: 0;" @tap="goto"
                        :data-url="'address?gotype=11&addressId='+take_id+'&area='+take_area+'&address='+take_address+'&longitude='+take_longitude+'&latitude='+take_latitude">
                        <view class="red"></view>
                        <view style="float: left;margin-left: 20rpx;float: left;width: 530rpx;">
                            <view class="first_title">
                                {{take_area?take_area:'填写取件地址'}}
                            </view>
                            <view style="font-size: 30rpx;color: #666666;line-height: 50rpx;">
                                {{take_address}}
                            </view>
                            <view style="font-size: 24rpx;color: #666666;line-height: 50rpx;">
                                {{take_name}} {{take_tel}}
                            </view>
                        </view>
                        <view style="float: right;width: 30rpx;margin-top: 30rpx;">
                            <image :src="pre_url+'/static/img/arrowright.png'"  style="width: 30rpx;height: 30rpx;"></image>
                        </view>
                    </view>

                    <view style="overflow: hidden;padding: 20rpx;margin-top: 20rpx;" @tap="goto"
                        :data-url="'address?gotype=12&addressId='+send_id+'&area='+send_area+'&address='+send_address+'&longitude='+send_longitude+'&latitude='+send_latitude">
                        <view class="black" ></view>
                        <view style="float: left;margin-left: 20rpx;float: left;width: 530rpx;">
                            <view class="first_title">
                                {{send_area?send_area:'填写收件地址'}}
                            </view>
                            <view style="font-size: 30rpx;color: #666666;line-height: 50rpx;">
                                {{send_address}}
                            </view>
                            <view style="font-size: 24rpx;color: #666666;line-height: 50rpx;">
                                {{send_name}} {{send_tel}}
                            </view>
                        </view>
                        <view style="float: right;width: 30rpx;margin-top: 30rpx;">
                            <image :src="pre_url+'/static/img/arrowright.png'"  style="width: 30rpx;height: 30rpx;"></image>
                        </view>
                    </view>
                </view>
                <view style="width: 660rpx;margin: 0 auto;overflow: hidden;line-height: 80rpx;margin-top: 20rpx;">
                    <view style="width: 160rpx;float: left;">
                        物品名称<text style="color: red; ">*</text>：
                    </view>
                    <view style="width: 500rpx;float: left;">
                        <input @input="inputName" name="name" placeholder="物品名称(必填,30字以内)" maxlength="30" placeholder-style="line-height: 80rpx;height: 80rpx;" style="line-height: 80rpx;height: 80rpx;background-color: #f5f5f5;border-radius: 8rpx;padding:0 20rpx" value=""/>
                    </view>
                </view>
                <view style="width: 660rpx;margin: 0rpx auto;line-height: 80rpx;margin-top: 40rpx;">
                    <view style="width: 160rpx;float: left;">物品图片：</view>
                    <view style="width: 500rpx;float: left;position:relative;padding: 20rpx 0;">
                        <view v-if="pic" class="layui-imgbox" style="width: 200rpx;">
                            <view class="layui-imgbox-close" @tap="removeimg"  data-field="pic"><image style="display:block" :src="pre_url+'/static/img/ico-del.png'"></image></view>
                            <view class="layui-imgbox-img"><image :src="pic" @tap="previewImage" :data-url="pic" mode="widthFix"></image></view>
                        </view>
                        <view class="uploadbtn" :style="'background:url('+pre_url+'/static/img/shaitu_icon.png) no-repeat 60rpx;background-size:80rpx 80rpx;background-color:#F3F3F3;'" @tap="uploadimg" data-field="pic" data-pernum="1" v-if="!pic"></view>
                        <view style="line-height: 40rpx;color: #999;font-size: 28rpx;">不上传则使用默认图片</view>
                    </view>
                </view>
                <view style="width: 660rpx;margin: 0 auto;overflow: hidden;line-height: 80rpx;margin-top: 20rpx;">
                    <view style="width: 160rpx;float: left;">取件时间：</view>
                    <view @tap="openTime"  style="width: 500rpx;float: left;background-color: #f5f5f5;border-radius: 8rpx;padding-left: 10rpx;">
                        <block v-if="!dayTime">
                            立即取件
                        </block>
                        <block v-else>
                            {{dayTime}}
                        </block>
                    </view>
                </view>
                <view v-if="show_weight" style="width: 660rpx;margin: 0 auto;overflow: hidden;line-height: 80rpx;margin-top: 20rpx;">
                    <view style="width: 160rpx;float: left;">重量：</view>
                    <view style="width: 500rpx;float: left;line-height: 80rpx;overflow: hidden;">
                        <input @blur="inputWeight" type="number" name="weight" placeholder="重量" :value="weight" placeholder-style="line-height: 80rpx;height: 80rpx;"  style="float: left;width: 180rpx;line-height: 80rpx;height: 80rpx;background-color: #f5f5f5;border-radius: 8rpx;padding:0 20rpx" />
                        <view style="float: left;width: 80rpx;line-height: 80rpx;margin-left: 10rpx;"> 公斤</view>
                    </view>
                </view>
                <view style="width: 660rpx;margin: 0 auto;overflow: hidden;line-height: 80rpx;margin-top: 20rpx;">
                    <view style="width: 160rpx;float: left;">小费：</view>
                    <view style="width: 500rpx;float: left;line-height: 80rpx;overflow: hidden;">
                        <input @blur="inputTipfee" type="number" name="tip_fee" placeholder="小费" :value="tip_fee" placeholder-style="line-height: 80rpx;height: 80rpx;"  style="float: left;width: 180rpx;line-height: 80rpx;height: 80rpx;background-color: #f5f5f5;border-radius: 8rpx;padding:0 20rpx" />
                        <view style="float: left;width: 50rpx;line-height: 80rpx;margin-left: 10rpx;"> 元</view>
                    </view>
                </view>

                <view style="width: 660rpx;margin: 0 auto;overflow: hidden;line-height: 80rpx;margin-top: 20rpx;">
                    <view style="width: 160rpx;float: left;">备注：</view>
                    <view style="width: 500rpx;float: left;">
                        <textarea @input="inputRemark" name="remark" placeholder="备注(100字以内)" maxlength="100" placeholder-style="line-height: 80rpx;height: 80rpx;" style="line-height: 40rpx;background-color: #f5f5f5;border-radius: 8rpx;padding:20rpx;width: 500rpx;height: 200rpx;">
                        </textarea>
                    </view>
                </view>
            </view>

            <view style="width: 100%; height:120rpx;"></view>
            <view class="notabbarbot" style="width: 100%;background-color: #fff;position: fixed;bottom: 0;z-index: 98;border-top: 2rpx solid #ccc;">
                <view style="width: 700rpx;margin: 0 auto;overflow: hidden">
                    <view style="width:500rpx;float: left;line-height: 80rpx;margin: 20rpx 0;overflow: hidden;">
                        <view style="float: left;font-weight: bold;">
                            <text >跑腿费：</text>
                            <text style="color: #FF2F20;">￥{{price === ''?'--':price}}</text>
                        </view>
                        <image v-if="price !== ''" @tap="openFee" :src="pre_url+'/static/img/arrowdown.png'" class="lookMore"></image>
                    </view>
                    <view @tap="goOrder" class="goorder" :style="{color:'#fff',background:'linear-gradient(-90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'}" >
                        提交订单
                    </view>
                </view>
            </view>

            <view v-if="time_status" @tap="closeTime" style="position: fixed;top:0;width: 100%;height: 100%;background-color: #000;opacity: 0.45;z-index: 99;"></view>
            <view v-if="time_status" style="position: fixed;bottom: 0;width: 100%;height: 100%;background-color: #fff;z-index: 100;height: 650rpx;border-radius: 20rpx 20rpx 0 0;">
                <view style="height: 100rpx;line-height: 100rpx;width: 700rpx;margin: 0 auto;overflow: hidden;padding:0 20rpx;">
                    <text style="font-size: 32rpx;font-weight: bold;">取件时间</text>
                    <image @tap="closeTime" :src="pre_url+'/static/img/close.png'" style="float: right;width: 30rpx;height: 30rpx;margin-top: 30rpx;"></image>
                </view>
                <picker-view  indicator-style="height: 50px;" :value="timeindex" @change="bindChange" class="picker-view">
                    <picker-view-column v-if="dayList">
                        <view class="item" v-for="(item,index) in dayList" :key="index">{{item}}</view>
                    </picker-view-column>

                    <picker-view-column v-if="hourList">
                        <view class="item" v-for="(item,index) in hourList" :key="index">
                            {{item}}
                            <block v-if="item != '立即取件'">
                                点
                            </block>
                        </view>
                    </picker-view-column>

                    <picker-view-column v-if="minuteList">
                        <view class="item" v-for="(item,index) in minuteList" :key="index">
                            {{item}}
                            <block v-if="item != '立即取件'">
                                分
                            </block>
                        </view>
                    </picker-view-column>
                </picker-view>
                <view @tap="qdTime" class="goorder2" :style="'background:linear-gradient(90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'">
                    确定
                </view>
            </view>

            <view v-if="fee_status" @tap="closeFee" style="position: fixed;top:0;width: 100%;height: 100%;background-color: #000;opacity: 0.45;z-index: 90;"></view>
            <view v-if="fee_status" style="position: fixed;bottom: 120rpx;width: 100%;background-color: #fff;z-index: 100;border-radius: 20rpx 20rpx 0 0;">
                <view style="height: 100rpx;line-height: 100rpx;width: 700rpx;margin: 0 auto;overflow: hidden;padding:0 20rpx;">
                    <text style="font-size: 32rpx;font-weight: bold;">费用明细</text>
                    <image @tap="closeFee" :src="pre_url+'/static/img/close.png'" style="float: right;width: 30rpx;height: 30rpx;margin-top: 30rpx;"></image>
                </view>
                <view style="line-height: 80rpx;width: 700rpx;margin: 0 auto;overflow: hidden;padding:0 20rpx;">
                    <view v-if="distance_fee>0" style="overflow: hidden;">
                        <view style="float: left;">
                            距离费用
                            <text v-if="show_detail && distance" style="margin-left: 10rpx;color: #999;font-size: 24rpx;">
                                {{distance}}
                            </text>
                        </view>
                        <view style="float: right;color: #999;">
                            ￥{{distance_fee}}
                        </view>
                    </view>
                    <view v-if="weight_fee>0" style="overflow: hidden;">
                        <view style="float: left;">
                            重量费用
                            <text v-if="show_detail && weight>0" style="margin-left: 10rpx;color: #999;font-size: 24rpx;">
                                {{weight}}公斤
                            </text>
                        </view>
                        <view style="float: right;color: #999;">
                            ￥{{weight_fee}}
                        </view>
                    </view>
                    <view v-if="tip_fee>0" style="overflow: hidden;">
                        <view style="float: left;">
                            小费
                        </view>
                        <view style="float: right;color: #999;">
                            ￥{{tip_fee}}
                        </view>
                    </view>
                    <view v-if="time_fee>0" style="overflow: hidden;">
                        <view style="float: left;">
                            特殊时间段附加
                        </view>
                        <view style="float: right;color: #999;">
                            ￥{{time_fee}}
                        </view>
                    </view>
                    <view v-if="dt_fee>0" style="overflow: hidden;">
                        <view style="float: left;">
                            动态溢价
                        </view>
                        <view style="float: right;color: #999;">
                            ￥{{dt_fee}}
                        </view>
                    </view>
                </view>
                <view v-if="content" @tap="openContent" style="text-align: center;color:#999;line-height: 80rpx;">
                    查看计价规则
                </view>
            </view>

            <view v-if="content_status" style="position: fixed;top:0;width: 100%;height:100%;background-color: #fff;z-index: 100;">
                <view style="height: 100rpx;line-height: 100rpx;width: 750rpx;margin: 0 auto;overflow: hidden;padding:0 20rpx;">
                    <text style="font-size: 32rpx;font-weight: bold;">计价规则</text>
                    <view @tap="closeContent" style="float: right;width: 50rpx;height: 80rpx;overflow: hidden;">
                        <image  :src="pre_url+'/static/img/close.png'" style="float: right;width: 30rpx;height: 30rpx;margin-top: 30rpx;"></image>
                    </view>

                </view>
                <view style="width: 750rpx;margin: 0 auto;padding:0 20rpx;">
                    <parse :content="content"></parse>
                </view>
            </view>
        </block>
        <view @tap="goto" data-url="/pages/index/index" data-opentype="reLaunch" class="back_index" :style="'background:'+t('color1')" >
            首页
        </view>
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
                nodata: false,
                menuindex: -1,
                pre_url: app.globalData.pre_url,

                btntype:1,

                show_detail:false,
                show_weight:false,
                content    :'',

                time_status:false,
                fee_status :false,
                content_status:false,

                name:'',//物品名称
                pic:'',
                //取件地址
                take_id:0,
                take_area:'',
                take_address:'',
                take_name:'请填写联系人',
                take_tel:'',
                take_longitude:'',
                take_latitude:'',

                //收件地址
                send_id:0,
                send_area:'',
                send_address:'',
                send_name:'请填写联系人',
                send_tel:'',
                send_longitude:'',
                send_latitude:'',

                longitude: '',
                latitude: '',
                controls:[],
                markers:[],

                dayList:'',
                hourList:'',
                minuteList:'',

                weight:1,
                tip_fee:0,

                timeindex:[0,0,0],
                dayVal:'',
                hourVal:'',
                minuteVal:'',
                dayTime:'',
                remark:'',

                price:'',
                distance:0,
                distance_fee:0,
                weight_fee:0,
                time_fee:0,
                dt_fee:0,
                ret_gotype:1
            };
        },
        onLoad: function(opt) {
            this.opt = app.getopts(opt);
            this.getdata();
        },
        onShow:function(opt){
            var that = this;
            var pages = getCurrentPages(); //获取加载的页面
            var currentPage = pages[pages.length - 1]; //获取当前页面的对象
            //console.log(currentPage)
            if(currentPage && currentPage.$vm.ret_id){
                if(currentPage.$vm.ret_gotype == 11){
                    that.ret_gotype  = 11;
                    that.take_id     = currentPage.$vm.ret_id;
                    that.take_area   = currentPage.$vm.ret_area;
                    that.take_address= currentPage.$vm.ret_address;
                    that.take_name   = currentPage.$vm.ret_name;
                    that.take_tel    = currentPage.$vm.ret_tel;
                    that.take_longitude = currentPage.$vm.ret_longitude;
                    that.take_latitude  = currentPage.$vm.ret_latitude;

                    if(that.btntype == 1){
                        that.longitude = currentPage.$vm.ret_longitude;
                        that.latitude  = currentPage.$vm.ret_latitude;
                    }
                }else{
                    that.ret_gotype  = 12;
                    that.send_id     = currentPage.$vm.ret_id;
                    that.send_area   = currentPage.$vm.ret_area;
                    that.send_address= currentPage.$vm.ret_address;
                    that.send_name   = currentPage.$vm.ret_name;
                    that.send_tel    = currentPage.$vm.ret_tel;
                    that.send_longitude = currentPage.$vm.ret_longitude;
                    that.send_latitude  = currentPage.$vm.ret_latitude;

                    if(that.btntype == 2){
                        that.longitude = currentPage.$vm.ret_longitude;
                        that.latitude  = currentPage.$vm.ret_latitude;
                    }
                }
            }
            if(that.take_longitude && that.take_latitude && that.send_longitude && that.send_latitude){
                that.countPrice();
            }
        },
        methods: {
            getdata:function(){
                var that = this;
                app.post('ApiPaotui/index', {id:0}, function (res) {
                	if(res.status == 1){
                        that.getLocations();
                        that.isload = true;
                        var data = res.data;

                        if(data.show_detail){
                            that.show_detail = data.show_detail;
                        }
                        if(data.show_weight){
                            that.show_weight = data.show_weight;
                        }
                        if(data.content){
                            that.content = data.content;
                        }
                        if(data.dayList && data.dayList.length>0){
                            that.dayList = data.dayList;
                        }
                        if(data.hourList && data.dayList.length>0){
                            that.hourList = data.hourList;
                        }
                        if(data.minuteList && data.dayList.length>0){
                            that.minuteList = data.minuteList;
                        }
                        if(data.pic){
                            that.pic = data.pic;
                        }
                    }else{
                        app.alert(res.msg);
                    }
                });
            },
            getLocations: function() {
                var that = this;
                app.getLocation(function (res) {
                    var latitude   = res.latitude;
                    var longitude  = res.longitude;
                    that.longitude = longitude;
                    that.latitude  = latitude;
                    that.markers = [{
                    	id:0,
                    	latitude:latitude,
                    	longitude:longitude,
                    	iconPath: `${that.pre_url}/static/img/peisong/marker_kehu.png`,
                    	width:'44',
                    	height:'54'
                    }]
                    if(latitude && longitude){
                        if(!that.ret_gotype){
                            that.getAddress(latitude,longitude);
                        }
                    }
                },function(res){
                    app.alert("未获取到定位信息")
                });
    		},
            regionchange:function(e){
                var that = this;
                var latitude   = that.latitude;
                var longitude  = that.longitude;
                if(e.type == 'end'){
                    var location = e.detail.centerLocation;
                    if(location.latitude && location.longitude){
                        that.markers = [{
                        	id:0,
                        	latitude:location.latitude,
                        	longitude:location.longitude,
                        	iconPath: `${that.pre_url}/static/img/peisong/marker_kehu.png`,
                        	width:'44',
                        	height:'54'
                        }]
                        if(that.ret_gotype ==1){
                            console.log(111)
                            that.getAddress(location.latitude,location.longitude);
                        }
                        if(that.ret_gotype != 0 && that.ret_gotype !=1){
                            that.ret_gotype = 0;
                        }else if(that.ret_gotype == 0){
                            that.ret_gotype = 1;
                        }else{
                            that.ret_gotype = 1;
                        }
                    }
                }else if(e.detail.type == 'end'){
                    var location = e.detail.centerLocation;
                    if(location.latitude && location.longitude){
                        that.markers = [{
                        	id:0,
                        	latitude:location.latitude,
                        	longitude:location.longitude,
                        	iconPath: `${that.pre_url}/static/img/peisong/marker_kehu.png`,
                        	width:'44',
                        	height:'54'
                        }]
                        if(!that.ret_gotype){
                            that.getAddress(location.latitude,location.longitude);
                        }
                        if(that.ret_gotype != 0 && that.ret_gotype !=1){
                            that.ret_gotype = 0;
                        }else if(that.ret_gotype == 0){
                            that.ret_gotype = 1;
                        }else{
                            that.ret_gotype = 1;
                        }
                    }
                }

            },
            getAddress:function(latitude,longitude){
                var that = this;
                app.post('ApiPaotui/get_address', {latitude:latitude,longitude:longitude}, function (res) {
                	if(res.status == 1){
                        var data = res.data;
                        if(that.btntype == 1){
                            that.take_id        = 0;
                            that.take_tel       = '';
                            that.take_name      = '请填写联系人';
                            that.take_area      = data.address;
                            that.take_address   = data.formatted_addresses;
                            that.take_latitude  = data.latitude;
                            that.take_longitude = data.longitude;
                        }
                        if(that.btntype == 2 ){
                            that.send_id        = 0;
                            that.send_tel       = '';
                            that.send_name      = '请填写联系人';
                            that.send_area      = data.address;
                            that.send_address   = data.formatted_addresses;
                            that.send_latitude  = data.latitude;
                            that.send_longitude = data.longitude;
                        }
                        if(that.take_longitude && that.take_latitude && that.send_longitude && that.send_latitude){
                            that.countPrice();
                        }

                    }else{
                        if(that.btntype == 1 ){
                            that.take_id        = 0;
                            that.take_tel       = '';
                            that.take_name      = '请填写联系人';
                            that.take_area      = '';
                            that.take_address   = '';
                            that.take_latitude  = '';
                            that.take_longitude = '';
                        }
                        if(that.btntype == 2){
                            that.send_id        = 0;
                            that.send_tel       = '';
                            that.send_name      = '请填写联系人';
                            that.send_area      = '';
                            that.send_address   = '';
                            that.send_latitude  = '';
                            that.send_longitude = '';
                        }
                        app.alert(res.msg)
                    }
                });
            },
            bindChange: function (e) {

                var that = this;
                var timeindex     = that.timeindex;

                var now_timeindex = e.detail.value;
                console.log(now_timeindex)
                if(timeindex[0] != now_timeindex[0]){
                    that.timeindex  = [now_timeindex[0],0,0];
                    const dayVal    = that.dayList[now_timeindex[0]];
                    app.post('ApiPaotui/get_timelist', {dayVal:dayVal}, function (res) {
                        if(res.status == 1){
                            var data = res.data;
                            if(data.hourList && data.hourList.length>0){
                                console.log(data.hourList)
                                that.hourList = data.hourList;
                            }
                            if(data.minuteList && data.minuteList.length>0){
                                that.minuteList = data.minuteList;
                            }
                        }else{
                            app.alert(res.msg);
                        }
                    });

                }else{
                    that.timeindex = now_timeindex;
                }

            },
            qdTime:function(e){
                var that = this;
                const timeindex = that.timeindex;

                const dayVal    = that.dayList[timeindex[0]];
                const hourVal   = that.hourList[timeindex[1]];
                const minuteVal = that.minuteList[timeindex[2]];

                if(hourVal == '立即取件' || minuteVal == '立即取件'){
                    that.dayVal   = '';
                    that.hourVal  = '';
                    that.minuteVal= '';

                    that.dayTime  = '';
                }else{
                    that.dayVal   = dayVal;
                    that.hourVal  = hourVal;
                    that.minuteVal= minuteVal;
                    that.dayTime  = dayVal+' '+hourVal+':'+minuteVal;
                }

                that.time_status = false;
            },
            changeBtntype:function(e){
                var that = this;
                that.btntype = e.currentTarget.dataset.btntype;

                console.log()
                var take_id        = that.send_id;
                var take_tel       = that.send_tel;
                var take_name      = that.send_name;
                var take_area      = that.send_area;
                var take_address   = that.send_address;
                var take_latitude  = that.send_latitude;
                var take_longitude = that.send_longitude;

                var send_id        = that.take_id;
                var send_tel       = that.take_tel;
                var send_name      = that.take_name;
                var send_area      = that.take_area;
                var send_address   = that.take_address;
                var send_latitude  = that.take_latitude;
                var send_longitude = that.take_longitude;

                that.take_id        = take_id;
                that.take_tel       = take_tel;
                that.take_name      = take_name;
                that.take_area      = take_area;
                that.take_address   = take_address;
                that.take_latitude  = take_latitude;
                that.take_longitude = take_longitude;

                that.send_id        = send_id;
                that.send_tel       = send_tel;
                that.send_name      = send_name;
                that.send_area      = send_area;
                that.send_address   = send_address;
                that.send_latitude  = send_latitude;
                that.send_longitude = send_longitude;

                console.log(that)
            },
            inputName:function(e){
                var that = this;
                that.name = e.detail.value;
            },
            inputRemark:function(e){
                var that = this;
                that.remark = e.detail.value;
            },
            inputTipfee:function(e){
                var that = this;
                that.tip_fee = e.detail.value;
                that.countPrice();
            },
            inputWeight:function(e){
                var that = this;
                that.weight = e.detail.value;
                that.countPrice();
            },
            openTime:function(){
                this.time_status = true;
            },
            closeTime:function(){
                this.time_status = false;
            },
            openFee:function(){
                this.fee_status = true;
            },
            closeFee:function(){
                this.fee_status = false;
            },
            openContent:function(){
                this.content_status = true;
            },
            closeContent:function(){
                this.content_status = false;
            },
            countPrice:function(){
                var that = this;
                if(that.take_longitude && that.take_latitude && that.send_longitude && that.send_latitude){
                    const data = {
                        btntype   : that.btntype,
                        //取件地址
                        take_longitude  : that.take_longitude,
                        take_latitude   : that.take_latitude,
                        //收件地址
                        send_longitude : that.send_longitude,
                        send_latitude  : that.send_latitude,

                        weight    : that.weight,
                        tip_fee   : that.tip_fee,

                        dayVal    : that.dayVal,
                        hourVal   : that.hourVal,
                        minuteVal : that.minuteVal,
                    }
                    app.post('ApiPaotui/count_price', data, function (res) {
                    	app.showLoading(false);
                        if(res.status == 1){
                            that.price = res.price;
                            that.distance_fee = res.distance_fee;
                            that.distance     = res.distance;
                            that.weight_fee   = res.weight_fee;
                            that.tip_fee      = res.tip_fee;
                            that.time_fee     = res.time_fee;
                            that.dt_fee       = res.dt_fee;
                        }else{
                            that.price = '';
                            app.alert(res.msg);
                        }
                    })
                }else{
                    that.price = '';
                }
            },
            goOrder:function(){
                var that = this;
                if(!that.take_id || !that.take_longitude || !that.take_latitude){
                    if(that.btntype == 1){
                        app.alert('请完善取件地址');
                    }else{
                        app.alert('请完善收件地址');
                    }
                    return;
                }
                if(!that.send_id || !that.send_longitude || !that.send_latitude){
                    if(that.btntype == 1){
                        app.alert('请完善收件地址');
                    }else{
                        app.alert('请完善取件地址');
                    }
                    return;
                }
                const data = {
                    btntype     : that.btntype,

                    name        : that.name,
                    pic         : that.pic,
                    //自己地址
                    take_id     : that.take_id,
                    take_area   : that.take_area,
                    take_address: that.take_address,
                    take_name   : that.take_name,
                    take_tel    : that.take_tel,
                    take_longitude: that.take_longitude,
                    take_latitude : that.take_latitude,

                    //物品地址
                    send_id     : that.send_id,
                    send_area   : that.send_area,
                    send_address: that.send_address,
                    send_name   : that.send_name,
                    send_tel    : that.send_tel,
                    send_longitude: that.send_longitude,
                    send_latitude : that.send_latitude,

                    weight    : that.weight,
                    tip_fee   : that.tip_fee,
                    dayVal    : that.dayVal,
                    hourVal   : that.hourVal,
                    minuteVal : that.minuteVal,
                    remark    : that.remark,

                }
                app.confirm('确定下单吗?', function () {
                	app.showLoading('提交中');
                	app.post('ApiPaotui/create', data, function (res) {
                		app.showLoading(false);
                        if(res.status == 1){
                            app.goto('/pagesExt/pay/pay?id=' + res.payorderid);
                        }else{
                            app.alert(res.msg);
                        }
                	})
                });
            },
            uploadimg:function(e){
            	var that = this;
            	var pernum = parseInt(e.currentTarget.dataset.pernum);
            	if(!pernum) pernum = 1;
            	app.chooseImage(function(urls){
            		that.pic = urls[0];
            	},pernum);
            },
            removeimg:function(e){
            	var that = this;
                that.pic = '';
            },
        }
    }
</script>

<style>
    page{
        width: 100%;
        height: 100%;
    }
    .map {
        width: 750rpx;
        margin: 0 auto;
        height: 500rpx;
    }
    .btn{
        width: 250rpx;display: inline-block;padding: 20rpx;line-height: 70rpx;
    }
    .btn1{
        border-radius: 70rpx;
    }
    .first_title{
        width: 100%;
        overflow: hidden;
        font-size: 34rpx;
        line-height: 40rpx;
    }
    .black{width: 16rpx;height: 16rpx;background-color: #000;border-radius: 50%;float: left;margin-top: 14rpx;}
    .red{width: 16rpx;height: 16rpx;background-color:#FF3A51;border-radius: 50%;float: left;margin-top: 14rpx;}
    .red_view{overflow: hidden;margin-top: 20rpx;background-color:#F6F6F6;border-radius: 12rpx;padding: 20rpx;}
    .goorder{width: 200rpx;float: right;text-align: center;line-height: 80rpx;border-radius: 8rpx;margin: 20rpx auto;color: #fff;}
    .goorder2{width: 700rpx;margin:0 auto;text-align: center;line-height: 100rpx;border-radius: 8rpx;margin: 20rpx auto;color: #fff;}
    .lookMore{width: 40rpx;height: 40rpx;float: left;border: 2rpx solid #eee;border-radius: 50%;padding: 10rpx;margin-top: 22rpx;margin-left:10rpx}

    .picker-view {
        width: 750rpx;
        height: 400rpx;
        margin-top: 20rpx;
        margin: 0 auto;
    }
    .item {
        height: 50px;
        line-height: 50px;
        align-items: center;
        justify-content: center;
        text-align: center;
    }
    .back_index{position: fixed;bottom: 200rpx;right: 0rpx;width: 100rpx;line-height: 100rpx;border-radius: 50%;text-align: center;color:#fff}
    .layui-imgbox{margin-right:16rpx;margin-bottom:10rpx;font-size:24rpx;position: relative;}
    .layui-imgbox-img{display: block;width:200rpx;height:200rpx;padding:2px;border: #d3d3d3 1px solid;background-color: #f6f6f6;overflow:hidden}
    .layui-imgbox-img>image{max-width:100%;}
    .layui-imgbox-repeat{position: absolute;display: block;width:32rpx;height:32rpx;line-height:28rpx;right: 2px;bottom:2px;color:#999;font-size:30rpx;background:#fff}
    .uploadbtn{position:relative;height:200rpx;width:200rpx}
</style>
