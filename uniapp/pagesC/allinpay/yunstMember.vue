<template>
	<view v-if="isload">
		 <view class="schedule-view flex-x-center">
			 <view class="schedule-options flex-col">
					<view :class="[signstatus>=1 ? 'active-class':'','num-text']">1</view>
					<view class="tips-text">注册会员</view>
			 </view>
			 <view class="dashed-line" :style="{border:`${signstatus}` == true ? '1px dashed #a9000e':'1px dashed #b3b3b3'}"></view>
			 <view class="schedule-options flex-col">
					<view :class="[signstatus>=2 ? 'active-class':'','num-text']">2</view>
					<view class="tips-text">绑定手机号</view>
			 </view>
       <view class="dashed-line" :style="{border:`${signstatus}` == true ? '1px dashed #a9000e':'1px dashed #b3b3b3'}"></view>
       <view class="schedule-options flex-col">
          <view :class="[signstatus>=3 ? 'active-class':'','num-text']">3</view>
          <view class="tips-text">实名认证</view>
       </view>
       <view class="dashed-line" :style="{border:`${signstatus}` == true ? '1px dashed #a9000e':'1px dashed #b3b3b3'}"></view>
       <view class="schedule-options flex-col">
          <view :class="[signstatus==4 ? 'active-class':'','num-text']">4</view>
          <view class="tips-text">绑定银行卡</view>
       </view>
		 </view>
		 <view class="form-view" v-if="signstatus==2">
         <view class="form-item">
           <view class="label">手机号</view>
           <input type="text" placeholder="请输入手机号" name="phone" :value="phone" @input="setField" data-field='phone' style="flex: 1;" />
         </view>
         <view class="form-item">
            <view class="label">验证码</view>
           <input type="text" placeholder="请输入验证码" name="verificationCode" :value="verificationCode" @input="setField" data-field='verificationCode'/>
           <view class="code" :style="'color:'+t('color1')" @tap="sendSmscode">{{smsdjs||'获取验证码'}}</view>
         </view>
		 </view>
     <view class="form-view" v-else-if="signstatus==3">
         <view class="form-item">
           <view class="label">真实姓名</view>
           <input type="text" placeholder="请输入真实姓名" name="name" :value="name" @input="setField" data-field='name' style="flex: 1;" />
         </view>
         <view class="form-item">
            <view class="label">身份证号</view>
            <input type="text" placeholder="请输入身份证号" name="identityNo" :value="identityNo" @input="setField" data-field='identityNo' style="flex: 1;"/>
         </view>
         <view style="color: grey;line-height: 70rpx;color: red;">请认真填写真实姓名、身份证号，绑定银行卡号时将会使用此信息</view>
     </view>
     <view class="form-view" v-else-if="signstatus==4">
         <view class="form-item">
           <view class="label">真实姓名</view>
           <input type="text" name="name" :value="name" disabled  placeholder="请输入真实姓名" style="flex: 1;" />
         </view>
         <view class="form-item">
            <view class="label">身份证号</view>
            <input type="text" name="identityNo" :value="identityNo" disabled placeholder="请输入身份证号"   style="flex: 1;"/>
         </view>
         <!-- <view class="form-item">
         		<text class="label">发卡机构</text>
         		<picker class="picker" mode="selector" name="bankname" value="0" :range="banklist" @change="bindBanknameChange">
         			<view v-if="bankname">{{bankname}}</view>
         			<view v-else>请选择开户行</view>
         		</picker>
         </view> -->
         <view class="form-item">
           <view class="label">银行卡号</view>
           <input type="text" placeholder="请输入银行卡号" name="cardNo" :value="cardNo" @input="setField" data-field='cardNo' style="flex: 1;" />
         </view>
         <view class="form-item">
           <view class="label">银行预留手机</view>
           <input type="text" placeholder="请输入银行预留手机" name="cardPhone" :value="cardPhone" @input="setField" data-field='cardPhone' style="flex: 1;" />
         </view>
         <view class="form-item" v-if="cardCheck != 8">
            <view class="label">验证码</view>
           <input type="text" placeholder="请输入验证码" name="verificationCode2" :value="verificationCode2" @input="setField" data-field='verificationCode2'/>
           <view class="code" :style="'color:'+t('color1')" @tap="sendSmscode2">{{smsdjs2||'获取验证码'}}</view>
         </view>
     </view>
		 <view class="form-view" v-if="signstatus==1">
			 <view class="form-item">
				 <view class="label">会员名称</view>
				 <input placeholder="请输入会员名称" name="bizUserId" :value="bizUserId" @input="setField" data-field='bizUserId'  style="flex: 1;"/>
			 </view>
       <view style="color: grey;line-height: 70rpx;color: red;">仅支持英文数字符号，不支持汉字</view>
		 </view>
		 <view class="but-view">
       <button v-if="signstatus ==2" class="set-btn" @click="goBandPhone" :style="{background:'linear-gradient(90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'}">确定绑定</button>
			  <button v-else-if="signstatus ==3" class="set-btn" @click="goRenzheng" :style="{background:'linear-gradient(90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'}">确定认证</button>
        <button v-else-if="signstatus ==4" class="set-btn" @click="goBindBankCard" :style="{background:'linear-gradient(90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'}">确定绑定</button>
			 <button v-else-if="signstatus ==1" class="set-btn" @click="getReg" :style="{background:'linear-gradient(90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'}">注册</button>
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
        smsdjs2: '',
        signstatus2:0,
        verificationCode2:'',
        cardCheck:7,//绑卡方式
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
            if(res.signstatus){
              that.signstatus = res.signstatus;
            }

            if(res.data){
              that.bizUserId = res.data.bizUserId || '';
              that.phone = res.data.phone || '';
              that.name  = res.data.name || '';
              that.identityNo = res.data.identityNo || '';
              that.cardNo     = res.data.cardNo || '';
              that.cardPhone  = res.data.cardPhone || '';
              that.cardCheck  = res.cardCheck || 7;
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
			getReg(){
				var that = this;
				if(!that.bizUserId) return app.error('请填写会员名称');
        app.confirm('确定提交？',function(){
          uni.showLoading({title:'提交中...'})
          app.post('ApiMy/yunstUser',{bizUserId:that.bizUserId}, function(res){
          	if(res.status == 1){
          		uni.hideLoading();
          		setTimeout(function(){
          		  that.signstatus = res.signstatus;
          		},800)
          	}else{
          		app.error(res.msg);
          	}
          })
        })
			},
      goRenzheng(){
        var that = this;
        if(!that.name) return app.error('请填写真实姓名');
        if(!that.identityNo) return app.error('请填写身份证号');
        app.confirm('确定提交？',function(){
          uni.showLoading({title:'提交中...'})
          app.post('ApiMy/yunstRenzheng',{name:that.name,identityNo:that.identityNo}, function(res){
            if(res.status == 1){
              uni.hideLoading();
              app.success(res.msg)
              setTimeout(function(){
                that.signstatus = res.signstatus;
              },800)
            }else{
              app.error(res.msg);
            }
          })
        })
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
                that.signstatus = res.signstatus;
              },800)
            }else{
              app.error(res.msg);
            }
          })
        })
      },
      sendSmscode2: function (type = 1) {
        var that = this;
        if (that.hqing2 == 1) return;
        that.hqing2 = 1;

        var cardNo = that.cardNo;
        if (cardNo == '') {
          app.alert('请输入银行卡号');
          that.hqing = 0;
          return false;
        }
        var cardPhone = that.cardPhone;
        if (cardPhone == '') {
          app.alert('请输入银行预留手机号');
          that.hqing = 0;
          return false;
        }
        app.post("ApiMy/yunstApplyBindBankCard", {cardNo: cardNo,cardPhone:cardPhone}, function (res) {
          if (res.status == 1) {
            app.success(res.msg)
            if(type != 8){
              var time = 120;
              var interval1 = setInterval(function () {
                time--;
                if (time < 0) {
                  that.smsdjs2 = '重新获取';
                  that.hqing2 = 0;
                  clearInterval(interval1);
                } else if (time >= 0) {
                  that.smsdjs2 = time + '秒';
                }
              }, 1000);
            }else{
              setTimeout(function(){
                app.goback();
              },800)
            }
            
          }else{
            that.hqing2 = 0;
            app.alert(res.msg);
          }
        });
      },
      goBindBankCard(){
        var that = this;
        var cardCheck = that.cardCheck;
        if(!that.cardNo) return app.error('请输入银行卡号');
        if(!that.cardPhone) return app.error('请输入银行预留手机号');
        if(cardCheck != 8){
          if(!that.verificationCode2) return app.error('请填写验证码');
        }
        app.confirm('确定提交？',function(){
          uni.showLoading({title:'提交中...'})
          if(cardCheck != 8){
            app.post('ApiMy/yunstBindBankCard',{cardNo:that.cardNo,cardPhone:that.cardPhone,verificationCode:that.verificationCode2}, function(res){
              if(res.status == 1){
                app.success(res.msg)
                setTimeout(function(){
                  app.goback();
                },800)
              }else{
                app.error(res.msg);
              }
              uni.hideLoading();
            })
          }else{
            that.sendSmscode2(8);
          }
          
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
</style>