<template>
	<view v-if="isload">
    <view class="schedule-view flex-x-center">
       <view class="schedule-options flex-col">
          <view :class="[signstatus>=1 ? 'active-class':'','num-text']">1</view>
          <view class="tips-text">注册企业会员</view>
       </view>
       <view class="dashed-line" :style="{border:`${signstatus}` == true ? '1px dashed #a9000e':'1px dashed #b3b3b3'}"></view>
      <view class="schedule-options flex-col">
         <view :class="[signstatus==2 ? 'active-class':'','num-text']">2</view>
         <view class="tips-text">签约开户</view>
      </view>
    </view>
    <block v-if="signstatus == 1">
      <view class="form-view">
        <view class="form-item" style="display: block;height: auto;border: 0;">
            <view style=" display: flex;align-items: center;">
             <view class="label">会员名称</view>
             <input placeholder="请输入会员名称" name="bizUserId" :value="info.bizUserId" @input="setField" data-field='bizUserId'  style="flex: 1;"/>
           </view>
           <view style="color: grey;line-height: 50rpx;color: red;">仅支持英文数字符号，不支持汉字</view>
        </view>
        <view class="form-item">
           <view class="label">企业名称</view>
           <input placeholder="请输入企业名称" name="companyName" :value="info.companyName" @input="setField" data-field='companyName'  style="flex: 1;"/>
        </view>
      </view>

      <view v-if="showone" class="form-view" >
          <view class="form-item">
             <view class="label">企业地址</view>
             <input placeholder="请输入企业地址" name="companyAddress" :value="info.companyAddress" @input="setField" data-field='companyAddress'  style="flex: 1;"/>
          </view>
          <view class="form-item" style="display: block;height: auto;">
            <view style=" display: flex;align-items: center;">
              <view class="label">企业性质</view>
              <view  style="flex: 1;text-align: left;">
               <picker @change="pickerComproperty" :value="info.comproperty" :range="compropertys" style="width: 100%;">
                 <view class="picker">
                   <view>{{compropertys[info.comproperty]}}</view>
                   <image style="width: 40rpx;height: 40rpx;" :src="pre_url+'/static/img/arrowright.png'"></image>
                 </view>
               </picker>
              </view>
            </view>
             <view style="color: grey;line-height: 50rpx;color: red;width: 100%;">个体工商户性质，企业名称里不能含有‘公司’</view>
          </view>
          <view class="form-item" style="display: block;height: auto;">
            <view style=" display: flex;align-items: center;">
              <view class="label">认证类型</view>
              <view  style="flex: 1;text-align: left;">
               <picker @change="pickerAuthType" :value="info.authType" :range="authTypes" style="width: 100%;">
                 <view class="picker">
                    <view>{{authTypes[info.authType]}}</view>
                   <image style="width: 40rpx;height: 40rpx;" :src="pre_url+'/static/img/arrowright.png'"></image>
                 </view>
               </picker>
              </view>
            </view>
          </view>
          <view v-if="info.authType==2" class="form-item">
             <view class="label">统一社会信用</view>
             <input placeholder="请输入统一社会信用" name="uniCredit" :value="info.uniCredit" @input="setField" data-field='uniCredit'  style="flex: 1;"/>
          </view>
          <block v-if="info.authType==1">
            <view class="form-item">
               <view class="label">营业执照号</view>
               <input placeholder="请输入营业执照号" name="businessLicense" :value="info.businessLicense" @input="setField" data-field='businessLicense'  style="flex: 1;"/>
            </view>
            <view class="form-item">
               <view class="label">组织机构代码</view>
               <input placeholder="请输入组织机构代码" name="organizationCode" :value="info.organizationCode" @input="setField" data-field='organizationCode'  style="flex: 1;"/>
            </view>
            <view class="form-item">
               <view class="label">税务登记证</view>
               <input placeholder="请输入税务登记证" name="taxRegister" :value="info.taxRegister" @input="setField" data-field='taxRegister'  style="flex: 1;"/>
            </view>
          </block>
      </view>
      <view v-if="showtwo" class="form-view">
          <view class="form-item">
            <view class="label">银行预留手机</view>
            <input type="text" placeholder="请输入银行预留手机" name="phone" :value="info.phone" @input="setField" data-field='phone' style="flex: 1;" />
          </view>
          <view class="form-item">
            <view class="label">联系电话</view>
            <input type="text" placeholder="请输入联系电话" name="telephone" :value="info.telephone" @input="setField" data-field='telephone' style="flex: 1;" />
          </view>
          <view class="form-item">
            <view class="label">法人姓名</view>
            <input type="text" placeholder="请输入法人姓名" name="legalName" :value="info.legalName" @input="setField" data-field='legalName' style="flex: 1;" />
          </view>
          <view class="form-item" style="display: block;height: auto;">
            <view style=" display: flex;align-items: center;">
              <view class="label">法人证件类型</view>
              <view  style="flex: 1;text-align: left;">
               <picker @change="pickerIdentityType" :value="identityTypeindex" :range="identityTypes" range-key="name" style="width: 100%;">
                 <view class="picker">
                   <view>{{identityTypes[identityTypeindex]['name']}}</view>
                   <image style="width: 40rpx;height: 40rpx;" :src="pre_url+'/static/img/arrowright.png'"></image>
                 </view>
               </picker>
              </view>
            </view>
          </view>
          <view class="form-item">
            <view class="label">法人证件号码</view>
            <input type="text" placeholder="请输入法人证件号码" name="legalIds" :value="info.legalIds" @input="setField" data-field='legalIds' style="flex: 1;" />
          </view>
          <view class="form-item">
            <view class="label">法人手机号</view>
            <input type="text" placeholder="请输入法人手机号" name="legalPhone" :value="info.legalPhone" @input="setField" data-field='legalPhone' style="flex: 1;" />
          </view>
      </view>
      <view v-if="showthree" class="form-view">
          <view class="form-item" style="display: block;height: auto;">
            <view style=" display: flex;align-items: center;">
              <view class="label">账户类型</view>
              <view  style="flex: 1;text-align: left;">
               <picker @change="pickerAcctType" :value="info.acctType" :range="acctTypes" style="width: 100%;">
                 <view class="picker">
                    <view>{{acctTypes[info.acctType]}}</view>
                   <image style="width: 40rpx;height: 40rpx;" :src="pre_url+'/static/img/arrowright.png'"></image>
                 </view>
               </picker>
              </view>
            </view>
          </view>
          <view class="form-item">
            <view class="label">企业对公户</view>
            <input type="text" placeholder="请输入企业对公户" name="accountNo" :value="info.accountNo" @input="setField" data-field='accountNo' style="flex: 1;" />
          </view>
          <view class="form-item">
            <view class="label">开户行名称</view>
            <input type="text" placeholder="请输入开户行名称" name="parentBankName" :value="info.parentBankName" @input="setField" data-field='parentBankName' style="flex: 1;" />
          </view>
          <view class="form-item">
            <view class="label">开户行支行</view>
            <input type="text" placeholder="请输入开户行支行" name="bankName" :value="info.bankName" @input="setField" data-field='bankName' style="flex: 1;" />
          </view>
          <view class="form-item">
            <view class="label">支行行号</view>
            <input type="text" placeholder="请输入支行行号" name="unionBank" :value="info.unionBank" @input="setField" data-field='unionBank' style="flex: 1;" />
          </view>
      </view>

    </block>
     
		 <view class="but-view">
			 <button v-if="signstatus == 1" class="set-btn" @click="getReg" :style="{background:'linear-gradient(90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'}">注册</button>
       <button v-if="signstatus == 2 && showAnew" class="set-btn" @click="goAnew" :style="{border:'2rpx solid '+t('color1'),color:t('color1'),margin:'0 20rpx 0 0'}">重新申请</button>
       <button v-if="signstatus == 2" class="set-btn" @click="goSigning" :style="{background:'linear-gradient(90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'}">去开户</button>
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
          bizUserId:'',
          companyName:'',
          companyAddress:'',
          comproperty:1,
          authType:1,
          uniCredit:'',
          businessLicense:'',
          organizationCode:'',
          taxRegister:'',
          phone:'',
          telephone:'',
          legalName:'',
          identityType:1,
          legalIds:'',
          legalPhone:'',
          acctType:1,
          accountNo:'',
          parentBankName:'',
          bankName:'',
          unionBank:'',
        },
        compropertys:['请选择','企业','个体工商户','事业单位'],
        authTypes:['请选择','三证','一证'],
        identityTypeindex:0,
        identityTypes:[
          {id:1,name:'身份证'},{id:2,name:'身份证'},{id:3,name:'身份证'},
          {id:4,name:'身份证'},{id:5,name:'身份证'},{id:6,name:'身份证'},
          {id:7,name:'身份证'},{id:8,name:'身份证'},{id:9,name:'身份证'},
          {id:10,name:'身份证'},{id:11,name:'身份证'},{id:12,name:'身份证'},
          {id:99,name:'其它证件'}
        ],
        acctTypes:['对私','对公'],

        signstatus:1,
        regInviteAppid:'',
        regInviteLink:'',
        showAnew:false,
        showone:false,
        showtwo:false,
        showthree:false,
        bid:-1,
			}
		},
		onLoad: function (opt) {
      this.opt = app.getopts(opt);
      if(this.opt.bid === 0 || this.opt.bid === '0' || (this.opt.bid && this.opt.bid>0)){
        this.bid = this.opt.bid
      }
			this.getdata();
		},
		onPullDownRefresh: function () {
			this.getdata();
		},
		methods:{
			getdata: function () {
				var that = this;
				that.loading = true;
				app.get('ApiAdminFinance/yunstCompanyuser', {bid:that.bid}, function (res) {
					that.loading = false;
          if(res.status == 1){
            if(res.showAnew) that.showAnew = res.showAnew;
            if(res.info) that.info = res.info;
            if(res.compropertys) that.compropertys = res.compropertys;
            if(res.authTypes) that.authTypes = res.authTypes;
            if(res.identityTypes) that.identityTypes = res.identityTypes;
            if(res.acctTypes) that.acctTypes = res.acctTypes;
            if(res.signstatus && res.signstatus == 2 && res.regInviteLink){
              that.signstatus = res.signstatus;
              that.regInviteAppid = res.regInviteAppid;
              that.regInviteLink  = res.regInviteLink;
            }
            if(res.showone) that.showone = res.showone;
            if(res.showtwo) that.showtwo = res.showtwo;
            if(res.showthree) that.showthree = res.showthree;
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
				if(!info.bizUserId) return app.error('请填写会员名称');
        app.confirm('确定提交？',function(){
          uni.showLoading({title:'提交中...'})
          app.post('ApiAdminFinance/yunstCompanyuser',{info:info,bid:that.bid}, function(res){
            uni.hideLoading();
          	if(res.status == 1){
              that.signstatus     = res.signstatus;
          		that.regInviteAppid = res.regInviteAppid;
              that.regInviteLink  = res.regInviteLink;
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
          })
        })
			},
      goSigning(){
        var that = this;
      	if(!that.regInviteLink) return app.error('信息未提交成功');
        // #ifdef H5
          window.location.href = that.regInviteLink;
          return;
        // #endif
        
        // #ifdef MP-WEIXIN
          uni.navigateToMiniProgram({
            appId: that.regInviteAppid,
            path: 'pages/merchantAddress/merchantAddress',
            envVersion: 'release', 
            extraData:{
              targetUrl:that.regInviteLink
            },
            complete(res) {
              console.log(res)
            }
          })
          return;
        // #endif

        // #ifndef H5 || MP-WEIXIN 
          uni.navigateTo({
            url:'/pages/index/webView?url='+ encodeURIComponent(this.regInviteLink),
          })
          return;
        // #endif
      },
      goAnew:function(){
        var that = this;
        var info = that.info;
        app.confirm('确定重新申请？',function(){
          uni.showLoading({title:'提交中...'})
          app.post('ApiAdminFinance/yunstCompanyuserAnew',{}, function(res){
            uni.hideLoading();
          	if(res.status == 1){
              that.signstatus = 1;
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
	.schedule-view .dashed-line{border: 1px #b3b3b3 dashed;width: 370rpx;margin-top: 20rpx;}
	.form-view{width:94%;margin: 20rpx auto;background:#fff;border-radius:10rpx;padding: 30rpx 20rpx;}
	.form-view .form-title{font-size: 32rpx;color: #333;}
	.form-view .form-item{display:flex;align-items:center;width:96%;height:98rpx;line-height:98rpx;margin: 0 auto;border-bottom: 2rpx solid #f1f1f1;}
	.form-item .label{color: #000;width:170rpx;font-size: 26rpx;}
  .picker{width: 100%;display: flex;justify-content: space-between;align-items: center;}
	.success-view{width:94%;margin: 20rpx auto;background:#fff;border-radius:10rpx;padding: 50rpx 20rpx;align-items: center;}
	.success-view .icon-view{width: 90rpx;height: 90rpx;border-radius: 50%;background: #a9000e;display: flex;align-items: center;justify-content: center;}
	.success-view .icon-view image{width: 80rpx;height: 80rpx;}
	
	.but-view{margin:60rpx 5%;width:94%;margin: 50rpx auto;display: flex;justify-content: space-between;}
	.set-btn{width: 100%;height:96rpx;line-height:96rpx;border-radius:48rpx;color:#FFFFFF;font-weight:bold;}
  
</style>