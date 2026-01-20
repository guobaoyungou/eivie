<template>
	<view v-if="isload">
    <view class="schedule-view flex-x-center">
       <view class="schedule-options flex-col">
          <view :class="[signstatus>=1 ? 'active-class':'','num-text']">1</view>
          <view class="tips-text">签约户名</view>
       </view>
       <view class="dashed-line" :style="{border:`${signstatus}` == true ? '1px dashed #a9000e':'1px dashed #b3b3b3'}"></view>
      <view class="schedule-options flex-col">
         <view :class="[signstatus==2 ? 'active-class':'','num-text']">2</view>
         <view class="tips-text">申请签约</view>
      </view>
    </view>
    <block v-if="signstatus == 1">
      <view class="form-view">
        <view class="form-item" style="display: block;height: auto;border: 0;">
            <view style=" display: flex;align-items: center;">
             <view class="label">签约户名</view>
             <input placeholder="请输入签约户名" name="signAcctName" :value="info.signAcctName" @input="setField" data-field='signAcctName'  style="flex: 1;"/>
           </view>
           <view style="color: grey;line-height: 50rpx;color: red;">请填写真实姓名</view>
        </view>
      </view>
    </block>
     
		 <view class="but-view">
			 <button v-if="signstatus == 1" class="set-btn" @click="getReg" :style="{background:'linear-gradient(90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'}">下一步</button>
       <button v-if="signstatus == 2" class="set-btn" @click="goSigning" :style="{background:'linear-gradient(90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'}">去签约</button>
		 </view>
     <view style="width: 100%;height: 40rpx;"></view>
	</view>
</template>

<script>
	var app = getApp();
	export default {
		data(){
			return{
        loading:false,
        isload: false,
        pre_url:app.globalData.pre_url,
        opt:{},
        info:{
          signAcctName:'',
        },
        signstatus:1,
        allipayappid:'',
        gourl:''
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
				app.get('ApiMy/yunstUserSign', {}, function (res) {
					that.loading = false;
          if(res.status == 1){
            that.allipayappid = res.allipayappid;
            if(res.signstatus && res.signstatus == 2 && res.gourl){
              that.signstatus   = res.signstatus;
              that.gourl  = res.gourl;
            }else{
              if(res.info) that.info = res.info;
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
      pickerComproperty: function(e) {
        var that = this;
        var val = e.detail.value
        that.info.comproperty = val
        if(val == 1){
          that.info.acctType = 1;
        }
      },
      pickerAuthType: function(e) {
          this.info.authType = e.detail.value
      },
      pickerIdentityType: function(e) {
        var that = this;
        var identityTypeindex  = e.detail.value
        that.identityTypeindex = identityTypeindex;
        that.info.identityType = that.identityTypes[identityTypeindex]['id'];
      },
      pickerAcctType: function(e) {
          this.info.acctType = e.detail.value
      },
      setField: function (e) {
        var that = this;
        var field = e.currentTarget.dataset.field;
        that.info[field] = e.detail.value;
      },
			getReg(){
				var that = this;
        var info = that.info;
				if(!info.signAcctName) return app.error('请填写签约户名');
        app.confirm('确定提交？',function(){
          uni.showLoading({title:'提交中...'})
          app.post('ApiMy/yunstUserSign',{info:info}, function(res){
            uni.hideLoading();
          	if(res.status == 1){
              that.signstatus   = res.signstatus;
          		that.allipayappid = res.allipayappid;
              that.gourl        = res.gourl;
          	}else{
          		app.error(res.msg);
          	}
          })
        })
			},
      goSigning(){
        var that = this;
      	if(!that.gourl) return app.error('信息未提交成功');
        // #ifdef H5
          window.location.href = that.gourl;
          return;
        // #endif
        
        // #ifdef MP-WEIXIN
          uni.navigateToMiniProgram({
            appId: that.allipayappid,
            path: 'pages/merchantAddress/merchantAddress',
            envVersion: 'release', 
            extraData:{
              targetUrl:that.gourl
            },
            complete(res) {
              console.log(res)
            }
          })
          return;
        // #endif

        // #ifndef H5 || MP-WEIXIN 
          uni.navigateTo({
            url:'/pages/index/webView?url='+ encodeURIComponent(this.gourl),
          })
          return;
        // #endif
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
	.schedule-view .dashed-line{border: 1px #b3b3b3 dashed;width: 370rpx;margin-top: 20rpx;}
	.form-view{width:94%;margin: 20rpx auto;background:#fff;border-radius:10rpx;padding: 30rpx 20rpx;}
	.form-view .form-title{font-size: 32rpx;color: #333;}
	.form-view .form-item{display:flex;align-items:center;width:96%;height:98rpx;line-height:98rpx;margin: 0 auto;border-bottom: 2rpx solid #f1f1f1;}
	.form-item .label{color: #000;width:170rpx;font-size: 26rpx;}
  .picker{width: 100%;display: flex;justify-content: space-between;align-items: center;}
	.success-view{width:94%;margin: 20rpx auto;background:#fff;border-radius:10rpx;padding: 50rpx 20rpx;align-items: center;}
	.success-view .icon-view{width: 90rpx;height: 90rpx;border-radius: 50%;background: #a9000e;display: flex;align-items: center;justify-content: center;}
	.success-view .icon-view image{width: 80rpx;height: 80rpx;}
	
	.but-view{margin:60rpx 5%;width:94%;margin: 50rpx auto;}
	.set-btn{width: 100%;height:96rpx;line-height:96rpx;border-radius:48rpx;color:#FFFFFF;font-weight:bold;}
  
</style>