<template>
    <view class="container">
        <block v-if="isload">
            <form @submit="formSubmit">
                <view class="form">
                    <view class="form-item">
                        <text class="label">姓 名</text>
                        <input class="input" type="text" placeholder="请输入姓名"
                            placeholder-style="font-size:28rpx;color:#BBBBBB" name="name" :value="name"></input>
                    </view>
                    <view class="form-item" v-if="showCompany">
                        <text class="label">公 司</text>
                        <input class="input" type="text" placeholder="请输入公司或单位名称"
                            placeholder-style="font-size:28rpx;color:#BBBBBB" name="company" :value="company"></input>
                    </view>
                    <view class="form-item">
                        <text class="label">手机号</text>
                        <input class="input" type="number" placeholder="请输入手机号"
                            placeholder-style="font-size:28rpx;color:#BBBBBB" name="tel" :value="tel"></input>
                    </view>
                    <view class="form-item">
                        <text class="label flex0">选择位置</text>
                        <text class="flex1" style="text-align:right" :style="area ? '' : 'color:#BBBBBB'"
                            @tap="selectzuobiao">{{area ? area : '请选择您的位置'}}</text>
                    </view>
                    <view class="form-item">
                        <text class="label">{{t('详细地址')}}</text>
                        <input class="input" type="text" :placeholder="'请输入'+t('详细地址')"
                            placeholder-style="font-size:28rpx;color:#BBBBBB" name="address" :value="address"></input>
                    </view>
                    <button class="savebtn" :style="'background:linear-gradient(90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'" form-type="submit">
                        保存并使用
                    </button>
                </view>
            </form>

            <view v-for="(item, index) in datalist" :key="index" class="content" @tap.stop="setdefault"
                :data-id="item.id" :data-name="item.name" :data-tel="item.tel" :data-area="item.area" :data-address="item.address" :data-longitude="item.longitude" :data-latitude="item.latitude">
                <view class="f1">
                    <text class="t1">{{item.name}}</text>
                    <text class="t2">{{item.tel}}</text>
                    <text class="t2" v-if="item.company">{{item.company}}</text>
                    <text class="flex1"></text>
                    <image class="t3" :src="pre_url+'/static/img/edit.png'" @tap.stop="goto"
                        :data-url="'/pagesB/address/addressadd?id=' + item.id + '&type=1'">
                </view>
                <view class="f2">{{item.area}} {{item.address}}</view>
            </view>
            <nodata v-if="nodata"></nodata>
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
                opt: {},
                loading: false,
                isload: false,
                menuindex: -1,
                
                addressId:0,
                name: '',
                tel: '',
                area: '',
                address: '',
                longitude: '',
                latitude: '',
                addressxx: '',
                company: '',
                showCompany: false,
                
                datalist: [],
                keyword:'',
                nodata:false,
                
                gotype:"",
                pre_url:app.globalData.pre_url,
            };
        },

        onLoad: function(opt) {
            var that = this;
            var opt  = app.getopts(opt);
            if(opt.gotype == 11){
                uni.setNavigationBarTitle({
                    title: '取件地址'
                });
                that.gotype = opt.gotype;
            }else if(opt.gotype == 12){
                uni.setNavigationBarTitle({
                    title: '收件地址'
                });
                that.gotype = opt.gotype;
            }
            if(opt.addressId){
                that.addressId = opt.addressId;
            }
            if(opt.area){
                that.area = opt.area;
            }
            if(opt.address){
                that.address = opt.address;
            }
            if(opt.longitude){
                that.longitude = opt.longitude;
            }
            if(opt.latitude){
                that.latitude = opt.latitude;
            }

            that.opt = opt;
            that.getdata();
        },
        onPullDownRefresh: function() {
            this.getdata();
        },
        methods: {
            getdata: function() {
                var that = this;
                var addressId = that.addressId;
                app.get('ApiIndex/getCustom', {}, function(customs) {
                    if (customs.data.includes('plug_xiongmao')) {
                        that.showCompany = true;
                    }
                });
                if (addressId && addressId>0) {
                    that.loading = true;
                    app.get('ApiAddress/addressadd', {
                        id: addressId,
                        type: 1
                    }, function(res) {
                        that.loading = false;
                        that.name = res.data.name;
                        that.tel = res.data.tel;
                        that.area = res.data.area;
                        that.address = res.data.address;
                        that.longitude = res.data.longitude;
                        that.latitude = res.data.latitude;
                        that.company = res.data.company;

                        that.loaded();
                    });
                } else {
                    that.loaded();
                }
                //获取常用地址
                that.getdata1();
            },
            selectzuobiao: function() {
                console.log('selectzuobiao')
                var that = this;
                uni.chooseLocation({
                    success: function(res) {
                        app.post('ApiPaotui/get_address', {latitude:res.latitude,longitude:res.longitude}, function (res2) {
                        	if(res2.status == 1){
                                that.area      = res2.data.address;
                                that.address   = res2.data.formatted_addresses;
                                that.latitude  = res.latitude;
                                that.longitude = res.longitude;
                            }else{
                                that.area      = '';
                                that.address   = '';
                                that.latitude  = '';
                                that.longitude = '';
                                app.alert(res2.msg);
                            }
                        });
                    },
                    fail: function(res) {
                        console.log(res)
                        if (res.errMsg == 'chooseLocation:fail auth deny') {
                            //$.error('获取位置失败，请在设置中开启位置信息');
                            app.confirm('获取位置失败，请在设置中开启位置信息', function() {
                                uni.openSetting({});
                            });
                        }
                    }
                });
            },
            formSubmit: function(e) {
                var that = this;
                var formdata = e.detail.value;
                var addressId = that.addressId;
                var name = formdata.name;
                var tel = formdata.tel;

                var area = that.area;
                if (area == '') {
                    app.error('请选择位置');
                    return;
                }

                var address = formdata.address;
                var longitude = that.longitude;
                var latitude = that.latitude;
                var company = formdata.company;

                if (name == '' || tel == '' || address == '') {
                    app.error('请填写完整信息');
                    return;
                }
                
                app.showLoading('提交中');
                app.post('ApiAddress/addressadd', {
                    type: 1,
                    source: 'paotui',
                    addressid: addressId,
                    name: name,
                    tel: tel,
                    area: area,
                    address: address,
                    latitude: latitude,
                    longitude: longitude,
                    company: company
                }, function(res) {
                    app.showLoading(false);
                    if (res.status == 1) {
                        app.success('保存成功');
                        setTimeout(function() {
                            let pages = getCurrentPages();
                            if (pages.length >= 2) {
                                //let curPage = pages[pages.length - 1]; // 当前页面
                                let prePage = pages[pages.length - 2]; // 上一页面
                                prePage.$vm.ret_gotype    = that.gotype;
                                prePage.$vm.ret_id        = res.addressid;
                                prePage.$vm.ret_name      = name;
                                prePage.$vm.ret_tel       = tel;
                                prePage.$vm.ret_area      = area;
                                prePage.$vm.ret_address   = address;
                                prePage.$vm.ret_latitude  = latitude;
                                prePage.$vm.ret_longitude = longitude;
                                uni.navigateBack();
                            }
                        }, 600);
                    }else{
                        app.alert(res.msg);
                        return;
                    }
                });
            },
            setaddressxx: function(e) {
                this.addressxx = e.detail.value;
            },
            getdata1: function() {
                var that = this;
          		that.loading = true;
                that.nodata = false;
                app.get('ApiAddress/address', {
                    type: 1,
                    keyword: that.keyword
                }, function(res) {
        			that.loading = false;
                    var datalist = res.data;
         			if (datalist.length == 0 && that.keyword == '') {

                    } else if (datalist.length == 0) {
                        that.datalist = datalist;
                        that.nodata = true;
                    } else {
        			    that.datalist = datalist;
                    }
                    that.loaded();
                });
            },
            //选择收货地址
            setdefault: function(e) {
                var that = this;
                var fromPage = this.opt.fromPage;
                var id = e.currentTarget.dataset.id;
                var name      = e.currentTarget.dataset.name;
                var tel       = e.currentTarget.dataset.tel;
                var area      = e.currentTarget.dataset.area;
                var address   = e.currentTarget.dataset.address;
                var latitude  = e.currentTarget.dataset.latitude;
                var longitude = e.currentTarget.dataset.longitude;
                app.post('ApiPaotui/get_address', {latitude:latitude,longitude:longitude}, function (res) {
                	if(res.status == 1){
                        setTimeout(function() {
                            let pages = getCurrentPages();
                            if (pages.length >= 2) {
                                //let curPage = pages[pages.length - 1]; // 当前页面
                                let prePage = pages[pages.length - 2]; // 上一页面
                                prePage.$vm.ret_gotype    = that.gotype;
                                prePage.$vm.ret_id        = id;
                                prePage.$vm.ret_name      = name;
                                prePage.$vm.ret_tel       = tel;
                                prePage.$vm.ret_area      = area;
                                prePage.$vm.ret_address   = address;
                                prePage.$vm.ret_latitude  = latitude;
                                prePage.$vm.ret_longitude = longitude;
                                uni.navigateBack();
                            }
                        }, 600);
                    }else{
                        app.alert(res.msg);
                    }
                });
            },
        }
    };
</script>
<style>
    .container {
        display: flex;
        flex-direction: column
    }

    .addfromwx {
        width: 94%;
        margin: 20rpx 3% 0 3%;
        border-radius: 5px;
        padding: 20rpx 3%;
        background: #FFF;
        display: flex;
        align-items: center;
        color: #666;
        font-size: 28rpx;
    }

    .addfromwx .img {
        width: 40rpx;
        height: 40rpx;
        margin-right: 20rpx;
    }

    .form {
        width: 94%;
        margin: 20rpx 3%;
        border-radius: 5px;
        padding: 0 3%;
        background: #FFF;
        overflow: hidden;
    }

    .form-item {
        display: flex;
        align-items: center;
        width: 100%;
        border-bottom: 1px #ededed solid;
        height: 98rpx;
    }

    .form-item:last-child {
        border: 0
    }

    .form-item .label {
        color: #8B8B8B;
        font-weight: bold;
        height: 60rpx;
        line-height: 60rpx;
        text-align: left;
        width: 160rpx;
        padding-right: 20rpx
    }

    .form-item .input {
        flex: 1;
        height: 60rpx;
        line-height: 60rpx;
        text-align: right
    }

    .savebtn {
        width: 100%;
        height: 96rpx;
        line-height: 96rpx;
        text-align: center;
        border-radius: 12rpx;
        color: #fff;
        font-weight: bold;
        margin: 30rpx 0;
        border: none;
    }
    .topsearch{width:94%;margin:16rpx 3%;}
    .topsearch .f1{height:60rpx;border-radius:30rpx;border:0;background-color:#fff;flex:1}
    .topsearch .f1 .img{width:24rpx;height:24rpx;margin-left:10px}
    .topsearch .f1 input{height:100%;flex:1;padding:0 20rpx;font-size:28rpx;color:#333;}
    
    .content{width:94%;margin:10rpx 3%;background:#fff;border-radius:5px;padding:20rpx 40rpx;}
    .content .f1{height:96rpx;line-height:96rpx;display:flex;align-items:center}
    .content .f1 .t1{color:#2B2B2B;font-weight:bold;font-size:30rpx}
    .content .f1 .t2{color:#999999;font-size:28rpx;margin-left:10rpx}
    .content .f1 .t3{width:28rpx;height:28rpx}
    .content .f2{color:#2b2b2b;font-size:26rpx;line-height:42rpx;padding-bottom:20rpx;}
    .content .f3{height:96rpx;display:flex;align-items:center}
    .content .radio{flex-shrink:0;width: 36rpx;height: 36rpx;background: #FFFFFF;border: 3rpx solid #BFBFBF;border-radius: 50%;}
    .content .radio .radio-img{width:100%;height:100%}
    .content .mrtxt{color:#2B2B2B;font-size:26rpx;margin-left:10rpx}
    .content .del{font-size:24rpx}
    
    .container .btn-add{width:90%;max-width:700px;margin:0 auto;height:96rpx;line-height:96rpx;text-align:center;color:#fff;font-size:30rpx;font-weight:bold;border-radius:40rpx;position: fixed;left:0px;right:0;bottom:0;margin-bottom:20rpx;}
    .container .btn-add2{width:43%;max-width:700px;margin:0 auto;height:96rpx;line-height:96rpx;text-align:center;color:#fff;font-size:30rpx;font-weight:bold;border-radius:40rpx;position: fixed;left:5%;bottom:0;margin-bottom:20rpx;}
    .container .btn-add3{width:43%;max-width:700px;margin:0 auto;height:96rpx;line-height:96rpx;text-align:center;color:#fff;font-size:30rpx;font-weight:bold;border-radius:40rpx;position: fixed;right:5%;bottom:0;margin-bottom:20rpx;}
    
</style>
