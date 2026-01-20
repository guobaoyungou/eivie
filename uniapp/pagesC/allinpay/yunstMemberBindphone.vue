<template>
	<view v-if="isload">
		 <view class="form-view">
         <view class="form-item">
           <view class="label">手机号</view>
           <input type="text" placeholder="请输入手机号" name="phone" :value="phone" @input="setField" data-field='phone' style="flex: 1;" />
         </view>
         <view class="form-item">
            <view class="label">验证码</view>
           <input type="text" style="max-width:300rpx ;" placeholder="请输入验证码" name="verificationCode" :value="verificationCode" @input="setField" data-field='verificationCode'/>
           <view class="code" :style="'color:'+t('color1')" @tap="sendSmscode">{{smsdjs||'获取验证码'}}</view>
         </view>
		 </view>
		 <view class="but-view">
       <button class="set-btn" @click="goBandPhone" :style="{background:'linear-gradient(90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'}">确定绑定</button>
		 </view>
	</view>
</template>

<script>
	var app = getApp();
	export default {
		data(){
			return{
        loading:false,
        isload: false,
        opt:{},
				phone:'',
        
        hqing:0,
				smsdjs: '',
				signstatus:0,
        verificationCode:'',

			}
		},
		onLoad: function (opt) {
      this.opt = app.getopts(opt);
			this.getdata();
		},
		onPullDownRefresh: function () {
			this.getdata();
		},
		methods:{
			getdata: function () {
				var that = this;
				that.loading = true;
				app.get('ApiMy/yunstUser', {type:'bindphone'}, function (res) {
					that.loading = false;
          if(res.status == 1){
            if(res.data){
              that.phone = res.data.phone || '';
            }
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
      setField: function (e) {
        var that = this;
        var field = e.currentTarget.dataset.field;
        that[field] = e.detail.value;
      },
      sendSmscode: function () {
        var that = this;
        if (that.hqing == 1) return;
        that.hqing = 1;
        var phone = that.phone;
        if (phone == '') {
          app.alert('请输入手机号码');
          that.hqing = 0;
          return false;
        }
        if (!app.isPhone(phone)) {
          app.alert("手机号码有误，请重填");
          that.hqing = 0;
          return false;
        }
        app.post("ApiMy/yunstSendSmsCode", {phone: phone}, function (res) {
          if (res.status == 1) {
            app.success(res.msg)
            var time = 120;
            var interval1 = setInterval(function () {
              time--;
              if (time < 0) {
                that.smsdjs = '重新获取';
                that.hqing = 0;
                clearInterval(interval1);
              } else if (time >= 0) {
                that.smsdjs = time + '秒';
              }
            }, 1000);
          }else{
            that.hqing = 0;
            app.alert(res.msg);
          }
        });
      },
      goBandPhone(){
        var that = this;
        if(!that.phone) return app.error('请填写手机号');
        if(!that.verificationCode) return app.error('请填写验证码');
        app.confirm('确定提交？',function(){
          uni.showLoading({title:'提交中...'})
          app.post('ApiMy/yunstBindPhone',{phone:that.phone,verificationCode:that.verificationCode}, function(res){
            if(res.status == 1){
              uni.hideLoading();
              app.success(res.msg)
              setTimeout(function(){
                app.goback();
              },800)
            }else{
              app.error(res.msg);
            }
          })
        })
      }
		}
	}
</script>

<style>
	.content-text{width:94%;margin: 0 auto;padding: 30rpx 10rpx;font-size: 26rpx;color: #e60000;font-weight: bold;}
	.schedule-view{width:94%;margin: 0 auto;border-radius:10rpx;background:#fff;align-items: flex-start;padding: 40rpx 0rpx;}
	.schedule-view .schedule-options{align-items: center;}
	.schedule-view .schedule-options .active-class{background: #a9000e !important;}
	.schedule-view .schedule-options .num-text{width: 40rpx;height: 40rpx;text-align: center;line-height: 40rpx;background: #cbcbcb;color: #fff;border-radius: 50%;font-size: 24rpx;}
	.schedule-view .schedule-options .tips-text{font-size: 26rpx;color: #666;margin-top: 10rpx;}
	.schedule-view .dashed-line{border: 1px #b3b3b3 dashed;width: 70rpx;margin-top: 20rpx;}
	.form-view{width:94%;margin: 20rpx auto;background:#fff;border-radius:10rpx;padding: 30rpx 20rpx;}
	.form-view .form-title{font-size: 32rpx;color: #333;}
	.form-view .form-item{display:flex;align-items:center;width:96%;height:98rpx;line-height:98rpx;margin: 0 auto;border-bottom: 2rpx solid #f1f1f1;}
	.form-item .label{color: #000;width:160rpx;font-size: 24rpx;}
	.success-view{width:94%;margin: 20rpx auto;background:#fff;border-radius:10rpx;padding: 50rpx 20rpx;align-items: center;}
	.success-view .icon-view{width: 90rpx;height: 90rpx;border-radius: 50%;background: #a9000e;display: flex;align-items: center;justify-content: center;}
	.success-view .icon-view image{width: 80rpx;height: 80rpx;}
	
	.but-view{margin:60rpx 5%;width:94%;margin: 50rpx auto;}
	.set-btn{width: 100%;height:96rpx;line-height:96rpx;border-radius:48rpx;color:#FFFFFF;font-weight:bold;}
  .code{line-height: 40rpx;}
</style>