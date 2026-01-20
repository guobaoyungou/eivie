<template>
    <view class="container">
        <block v-if="isload">
            <form @submit="formSubmit" @reset="formReset">
            <view class="title">配送员登录</view>
            <view class="loginform">
                <view class="form-item">
                    <image src="/static/img/reg-tel.png" class="img"/>
                    <input type="text" class="input" placeholder="请输入登录账号" placeholder-style="font-size:30rpx;color:#B2B5BE" name="username" value=""/>
                </view>
                <view class="form-item">
                    <image src="/static/img/reg-pwd.png" class="img"/>
                    <input type="text" class="input" placeholder="请输入密码" placeholder-style="font-size:30rpx;color:#B2B5BE" name="password" value="" :password="true"/>
                </view>
                <view class="form-item">
                        <image src="/static/img/reg-code.png" class="img"/>
                        <input type="text" class="input" placeholder="请输入验证码" placeholder-style="font-size:30rpx;color:#B2B5BE" name="captcha" value=""/>
                        <image @tap="regetcaptcha" :src="pre_url+'/?s=/ApiIndex/captcha&aid='+aid+'&session_id='+session_id+'&t='+randt" style="width:240rpx;height:80rpx"/>
                    </view>
                <button class="form-btn" :style="{background:'linear-gradient(90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'}" form-type="submit">登录</button>
            </view>
            </form>
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
        opt:{},
        loading:false,
        isload: false,
        menuindex:-1,
        pre_url:app.globalData.pre_url,
        aid:app.globalData.aid,
        session_id:app.globalData.session_id,
        captcha_src:'',
        randt:'',
    }
  },
    onLoad: function (opt) {
        this.opt = app.getopts(opt);
        this.getdata();
    },
    onPullDownRefresh: function () {
        this.getdata();
    },
    methods: {
        getdata:function(){
            var that = this;
            that.loading = true;
            app.get("ApiXixie/login", {}, function (res) {
                that.loading = false;
                that.loaded();
            });
        },
        formSubmit: function (e) {
            var that = this;
            var formdata = e.detail.value;
            if (formdata.username == ''){
                app.alert('请输入账号');
                return;
            }
            if (formdata.password == '') {
                app.alert('请输入密码');
                return;
            }
            if (formdata.captcha == '') {
                app.alert('请输入验证码');
                return;
            }

            app.showLoading('提交中');
            app.post("ApiXixie/login", {username:formdata.username,password:formdata.password,captcha:formdata.captcha}, function (res) {
                app.showLoading(false);
                if (res.status == 1) {
                    app.success(res.msg);
                        setTimeout(function () {
                        app.goto('psorderlist','reLaunch');
                    }, 1000);
                } else {
                    app.error(res.msg);
                    if(res.status==2){
                        that.randt = that.randt+'1';
                    }
                }
            });
        },
        regetcaptcha:function(){
            this.randt = this.randt+'1';
        }
    }
};
</script>

<style>
page{background:#ffffff}
.container{width:100%;}
.title{margin:70rpx 50rpx 50rpx 40rpx;height:60rpx;line-height:60rpx;font-size: 48rpx;font-weight: bold;color: #000000;}
.loginform{ width:100%;padding:0 50rpx;border-radius:5px;background: #FFF;}
.loginform .form-item{display:flex;align-items:center;width:100%;border-bottom: 1px #ededed solid;height:88rpx;line-height:88rpx;border-bottom:1px solid #F0F3F6;margin-top:20rpx}
.loginform .form-item:last-child{border:0}
.loginform .form-item .img{width:44rpx;height:44rpx;margin-right:30rpx}
.loginform .form-item .input{flex:1;color: #000;}
.loginform .form-item .code{font-size:30rpx}
.loginform .form-btn{margin-top:60rpx;width:100%;height:96rpx;line-height:96rpx;color:#fff;font-size:30rpx;border-radius: 48rpx;}

</style>