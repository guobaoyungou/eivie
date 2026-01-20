<template>
	<view v-if="isload">
     <view class="form-view">
         <view class="form-item">
            <view class="label">会员名称</view>
            <input placeholder="请输入会员名称" name="bizUserId" :value="bizUserId" @input="setField" data-field='bizUserId'  style="flex: 1;"/>
         </view>
         <view class="form-item">
           <view class="label">银行卡号</view>
           <input type="text" placeholder="请输入银行卡号" name="cardNo" :value="cardNo" @input="setField" data-field='cardNo' style="flex: 1;" />
         </view>
         <view class="form-item">
           <view class="label">真实姓名</view>
           <input type="text" placeholder="请输入真实姓名" name="name" :value="name" @input="setField" data-field='name' style="flex: 1;" />
         </view>
         <view class="form-item">
            <view class="label">身份证号</view>
            <input type="text" placeholder="请输入身份证号" name="identityNo" :value="identityNo" @input="setField" data-field='identityNo' style="flex: 1;"/>
         </view>
         <view class="form-item">
           <view class="label">手机号码</view>
           <input type="text" placeholder="请输入银行预留手机号码" name="cardPhone" :value="cardPhone" @input="setField" data-field='cardPhone' style="flex: 1;" />
         </view>
         <view class="form-item">
            <view class="label">验证码</view>
           <input type="text" placeholder="请输入验证码" name="verificationCode2" :value="verificationCode" @input="setField" data-field='verificationCode'/>
           <view class="code" :style="'color:'+t('color1')" @tap="sendSmscode">{{smsdjs||'获取验证码'}}</view>
         </view>
     </view>
		 <view class="but-view">
			 <button class="set-btn" @click="goReg" :style="{background:'linear-gradient(90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'}">注册</button>
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
				bizUserId:'',
				phone:'',
        
        hqing:0,
				smsdjs: '',
				signstatus:0,
        verificationCode:'',
        
        //实名认证
        name:'',
        identityNo:'',
        
        //银行卡号
        hqing2:0,
        cardNo:'',
        cardPhone:'',
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
				app.get('ApiMy/yunstUser', {}, function (res) {
					that.loading = false;
          if(res.status == 1){
            if(res.data){
              that.bizUserId = res.data.bizUserId || '';
              that.phone = res.data.phone || '';
              that.name  = res.data.name || '';
              that.identityNo = res.data.identityNo || '';
              that.cardNo     = res.data.cardNo || '';
              that.cardPhone  = res.data.cardPhone || '';
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
        var bizUserId = that.bizUserId;
        if (bizUserId == '') {
          app.alert('请输入会员名称');
          that.hqing = 0;
          return false;
        }
        
        var cardNo = that.cardNo;
        if (cardNo == '') {
          app.alert('请输入银行卡号');
          that.hqing = 0;
          return false;
        }
        
        var name = that.name;
        if (name == '') {
          app.alert('请输入真实姓名');
          that.hqing = 0;
          return false;
        }
        
        var identityNo = that.identityNo;
        if (identityNo == '') {
          app.alert('请输入身份证号码');
          that.hqing = 0;
          return false;
        }
        
        var cardPhone = that.cardPhone;
        if (cardPhone == '') {
          app.alert('请输入手机号码');
          that.hqing = 0;
          return false;
        }
        
        app.post("ApiMy/yunstApplyBindBankCardAndCreateMember", {bizUserId:bizUserId,cardNo:cardNo,name:name,identityNo:identityNo,cardPhone: cardPhone}, function (res) {
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
      goReg(){
        let that = this;
        var bizUserId = that.bizUserId;
        if (bizUserId == '') {
          app.alert('请输入会员名称');
          that.hqing = 0;
          return false;
        }
        
        var cardNo = that.cardNo;
        if (cardNo == '') {
          app.alert('请输入银行卡号');
          that.hqing = 0;
          return false;
        }
        
        var name = that.name;
        if (name == '') {
          app.alert('请输入真实姓名');
          that.hqing = 0;
          return false;
        }
        
        var identityNo = that.identityNo;
        if (identityNo == '') {
          app.alert('请输入身份证号码');
          that.hqing = 0;
          return false;
        }
        
        var cardPhone = that.cardPhone;
        if (cardPhone == '') {
          app.alert('请输入手机号码');
          that.hqing = 0;
          return false;
        }
        
        var verificationCode = that.verificationCode;
        if (verificationCode == '') {
          app.alert('请输入验证码');
          that.hqing = 0;
          return false;
        }
        app.confirm('确定提交？',function(){
          uni.showLoading({title:'提交中...'})
          app.post('ApiMy/yunstBindBankCardAndCreateMember',{bizUserId:bizUserId,cardNo:cardNo,name:name,identityNo:identityNo,cardPhone: cardPhone,verificationCode:that.verificationCode}, function(res){
            if(res.status == 1){
              uni.hideLoading();
              app.success(res.msg)
              that.signstatus = that.signstatus;
            }else{
              app.error(res.msg);
            }
          })
        })
      },
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
	.schedule-view .dashed-line{border: 1px #b3b3b3 dashed;width: 100rpx;margin-top: 20rpx;}
	.form-view{width:94%;margin: 20rpx auto;background:#fff;border-radius:10rpx;padding: 30rpx 20rpx;}
	.form-view .form-title{font-size: 32rpx;color: #333;}
	.form-view .form-item{display:flex;align-items:center;width:96%;height:98rpx;line-height:98rpx;margin: 0 auto;border-bottom: 2rpx solid #f1f1f1;}
	.form-item .label{color: #000;width:160rpx;font-size: 24rpx;}
	.success-view{width:94%;margin: 20rpx auto;background:#fff;border-radius:10rpx;padding: 50rpx 20rpx;align-items: center;}
	.success-view .icon-view{width: 90rpx;height: 90rpx;border-radius: 50%;background: #a9000e;display: flex;align-items: center;justify-content: center;}
	.success-view .icon-view image{width: 80rpx;height: 80rpx;}
	
	.but-view{margin:60rpx 5%;width:94%;margin: 50rpx auto;}
	.set-btn{width: 100%;height:96rpx;line-height:96rpx;border-radius:48rpx;color:#FFFFFF;font-weight:bold;}
</style>