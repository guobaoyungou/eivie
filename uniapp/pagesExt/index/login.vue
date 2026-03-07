<template>
<view class="container">
	<block v-if="isload">
    <!-- s -->
    <view v-if="logintype>0" style="width:100%;height: 100%;">
      <view class="bg_div1" :style="loginset_data.bgtype==1?'background:'+loginset_data.bgcolor:'background:url('+loginset_data.bgimg+') no-repeat center;background-size:100% 100%'">
        <view style="overflow: hidden;">
          <view v-if="loginset_type==1" style="width: 700rpx;margin: 0 auto;">
            <view class="title1" :style="'color:'+loginset_data.titlecolor+';text-align: '+loginset_data.titletype">
              {{loginset_data.title}}
            </view>
            <view class="subhead1" v-if="loginset_data.subhead" :style="'color:'+loginset_data.subheadcolor+';text-align: '+loginset_data.titletype">
              {{loginset_data.subhead}}
            </view>
          </view>
          
          <view v-if="loginset_type==2" class="logo2">
              <img :src="loginset_data.logo" style="width: 100%;height: 100%;">
          </view>

          <view class="content_div1">
            <form @submit="formSubmit">
              <view class="card_div1" :style="'background:'+loginset_data.cardcolor">

                <view v-if="loginset_type==2">
                  <view class="title1" :style="'color:'+loginset_data.titlecolor+';text-align:'+loginset_data.titletype+';margin-top: 20rpx;font-size: 40rpx;'">
                    {{loginset_data.title}}
                  </view>
                  <view class="subhead1" v-if="loginset_data.subhead" :style="'color:'+loginset_data.subheadcolor+';text-align:'+loginset_data.titletype">
                    {{loginset_data.subhead}}
                  </view>
                </view>
                
                <view v-if="logintype==1 || logintype==2" class="tel1">
                   <input type="text" name="tel" value="" @input="telinput" class="input_val" placeholder="请输入手机号码" placeholder-style="color:#CACACA;line-height: 100rpx;"  />
                </view>
                <view v-if="logintype==1" class="tel1">
                   <input type="text" name="pwd" value="" :password="true"  placeholder="请输入密码" placeholder-style="color:#CACACA;line-height: 100rpx;" class="input_val"/>
                </view>

                <view v-if="logintype==2" class="tel1">
                  <input type="text" name="smscode" value="" placeholder="请输入验证码" placeholder-style="color:#CACACA;line-height: 100rpx;" class="inputcode" />
                  <text class="code1" :style="'color:'+loginset_data.codecolor" @tap="smscode">{{smsdjs||'获取验证码'}}</text>
                </view>
                <!-- logintype_2手机验证码登录 -->
                <view v-if="logintype_2 && logintype==2 && reg_invite_code && !parent" class="tel1">
                   <input type="text" name="yqcode" @input="yqinput" :value="yqcode" :placeholder="'请输入邀请人'+reg_invite_code_text" placeholder-style="color:#CACACA;line-height: 100rpx;" class="input_val"/>
                </view>
                <view v-if="reg_invite_code && parent && reg_invite_code_show == 1 && logintype!=6" class="tel1" style="display: flex;padding-top: 8rpx;align-items: center;">
                  <view style="white-space: nowrap;">邀请人：</view>
                  <image :src="parent.headimg" style="width: 80rpx; height: 80rpx;border-radius: 50%;margin-right: 20rpx;"></image> 
                  <view class="overflow_ellipsis">{{parent.nickname}} </view>
                </view>

                <view v-if="(logintype==1 || logintype==2 || logintype==3 || logintype==8 || logintype==9) && xystatus==1" class="xycss1">
                  <checkbox-group @change="isagreeChange" style="display: inline-block;position: relative;">
                      <checkbox style="transform: scale(0.6)"  value="1" :checked="isagree"/>
                      <text :style="'color:'+loginset_data.xytipcolor">{{loginset_data.xytipword}}</text>
											<view @click="promptRead" v-if="xyagree_type == 1 && !reading_completed" style="height:60rpx;width: 60rpx;position: absolute;top:0;"></view>
                  </checkbox-group>
                  <text @tap="showxieyiFun" :style="'color:'+loginset_data.xycolor">{{xyname}}</text>
                  <text @tap="showxieyiFun"  v-if="xyname2" :style="'color:'+loginset_data.xytipcolor">和</text>
                  <text @tap="showxieyiFun2" v-if="xyname2" :style="loginset_data.xycolor?'color:'+loginset_data.xycolor:'color:'+t('color1')">{{xyname2}}</text>
                </view>
                
                <view v-if="logintype==1 || logintype==2 || logintype==3 || logintype==8 || logintype==9" style="margin-top: 40rpx;">
                  <block  v-if="loginset_data.btntype==1">
                    <button v-if="logintype==3" @tap="authlogin" :data-type="alih5?1:0" class="btn1"  :style="'background:rgba('+t('color1rgb')+');color: '+loginset_data.btnwordcolor">
                      <block v-if="!alih5">
                        授权登录
                      </block>
                      <block v-else>
                        授权登录
                      </block>
                    </button>
										<button v-else-if="logintype==8" open-type="getPhoneNumber" @getphonenumber="wxtellogin" :data-type="0" class="btn1"  :style="'background:rgba('+t('color1rgb')+');color: '+loginset_data.btnwordcolor">
										    授权手机号登录
										</button>
										<button v-else-if="logintype==9" open-type="getPhoneNumber" @getphonenumber="alitellogin" :data-type="0" class="btn1"  :style="'background:rgba('+t('color1rgb')+');color: '+loginset_data.btnwordcolor">
										    授权手机号登录
										</button>
                    <button v-else class="btn1" :style="'background:rgba('+t('color1rgb')+');color: '+loginset_data.btnwordcolor" form-type="submit">登录</button>
                  </block>
                  <block  v-if="loginset_data.btntype==2">
                    <button v-if="logintype==3" @tap="authlogin" :data-type="alih5?1:0" class="btn1"  :style="'background-color:'+loginset_data.btncolor+';color:'+loginset_data.btnwordcolor">
                      <block v-if="!alih5">
                        授权登录
                      </block>
                      <block v-else>
                        授权登录
                      </block>
                    </button>
										<button v-else-if="logintype==8" open-type="getPhoneNumber" @getphonenumber="wxtellogin" :data-type="0" class="btn1"  :style="'background-color:'+loginset_data.btncolor+';color:'+loginset_data.btnwordcolor">
										    授权手机号登录
										</button>
										<button v-else-if="logintype==9" open-type="getPhoneNumber" @getphonenumber="alitellogin" :data-type="0" class="btn1"  :style="'background-color:'+loginset_data.btncolor+';color:'+loginset_data.btnwordcolor">
										    授权手机号登录
										</button>
                    <button v-else class="btn1" :style="'background-color:'+loginset_data.btncolor+';color:'+loginset_data.btnwordcolor" form-type="submit">登录</button>
                  </block>

                  <button class="btn1" @tap="noLogin" v-if="!login_mast" style="font-size: 28rpx;height: 50rpx;line-height: 50rpx;">暂不登录</button>

                  <button v-if="platform2 == 'ios' && logintype_4==true" class="ioslogin-btn" @tap="ioslogin" style="width:100%"><image :src="pre_url+'/static/img/apple.png'" />通过Apple登录</button>
                  <!-- #ifdef APP-PLUS -->
                  <button v-if="logintype_6==true" class="googlelogin-btn" @tap="googlelogin" style="width:100%">Google登录</button>
                  <!-- #endif -->
                  <!-- #ifdef H5 -->
                  <div v-if="logintype_6==true" class="googlelogin-btn2" id="googleloginBtn" data-shape="circle" style="width:100%">Google登录</div>
                  <!-- #endif -->
                  
                  <view v-if="logintype==1" style="line-height: 50rpx;float: 24rpx;overflow: hidden;">
                    <text @tap="goto" :data-url="'reg?frompage='+frompage" data-opentype="redirect" :style="'color: '+loginset_data.regpwdbtncolor+';float:left'">注册账号</text>
                    <text @tap="goto" data-url="getpwd" data-opentype="redirect" v-if="needsms" :style="'color: '+loginset_data.regpwdbtncolor+';float:right'">忘记密码</text>
                  </view>
                </view>
                
                <!-- 绑定手机号s -->
                <block v-if="logintype==4">
                    <!-- #ifdef MP-WEIXIN -->
                    <block  v-if="loginset_data.btntype==1">
                      <button open-type="getPhoneNumber" @getphonenumber="getPhoneNumber"  class="btn1" :style="'background:rgba('+t('color1rgb')+');color: '+loginset_data.btnwordcolor" >授权绑定手机号</button>
                    </block>
                    <block  v-if="loginset_data.btntype==2">
                      <button open-type="getPhoneNumber" @getphonenumber="getPhoneNumber"  class="btn1" :style="'background-color:'+loginset_data.btncolor+';color:'+loginset_data.btnwordcolor" >授权绑定手机号</button>
                    </block>
                    <button class="btn1" @tap="nobindregister" v-if="login_bind==1" style="background-color:#EEEEEE;font-size: 28rpx;">暂不绑定</button>
                    <!-- #endif -->
                    <!-- #ifdef MP-BAIDU -->
                    <block  v-if="loginset_data.btntype==1">
                      <button open-type="getPhoneNumber" @getphonenumber="getBaiduPhoneNumber"  class="btn1" :style="'background:rgba('+t('color1rgb')+');color: '+loginset_data.btnwordcolor" >授权绑定手机号</button>
                    </block>
                    <block  v-if="loginset_data.btntype==2">
                      <button open-type="getPhoneNumber" @getphonenumber="getBaiduPhoneNumber"  class="btn1" :style="'background-color:'+loginset_data.btncolor+';color:'+loginset_data.btnwordcolor" >授权绑定手机号</button>
                    </block>
                    <button class="btn1" @tap="nobindregister" v-if="login_bind==1" style="background-color:#EEEEEE;font-size: 28rpx;">暂不绑定</button>
                    <!-- #endif -->
                    <!-- #ifndef MP-WEIXIN || MP-BAIDU -->
                    <form @submit="bindregister">
                      <view style="font-size: 30rpx;font-weight: bold;line-height: 68rpx;">绑定手机号</view>
                      <view  class="tel1" style="margin-top: 30rpx;">
                         <input type="text" name="tel" value="" @input="telinput" class="input_val" placeholder="请输入手机号码" placeholder-style="color:#CACACA;line-height: 100rpx;"  />
                      </view>
                      <view  class="tel1">
                        <input type="text" name="smscode" value="" placeholder="请输入验证码" placeholder-style="color:#CACACA;line-height: 100rpx;" class="inputcode" />
                        <text class="code1" :style="'color:'+loginset_data.codecolor" @tap="smscode">{{smsdjs||'获取验证码'}}</text>
                      </view>
                      <block v-if="loginset_data.btntype==1">
                        <button class="btn1" form-type="submit" :style="'background:rgba('+t('color1rgb')+');color: '+loginset_data.btnwordcolor">确定</button>
                      </block>
                      <block v-if="loginset_data.btntype==2">
                        <button class="btn1" form-type="submit" :style="'background-color:'+loginset_data.btncolor+';color:'+loginset_data.btnwordcolor">确定</button>
                      </block>
                      <button class="btn1" @tap="nobindregister" v-if="login_bind==1" style="background-color:#EEEEEE ;font-size: 28rpx;">暂不绑定</button>
                    </form>
                    <!-- #endif -->
                </block>
                <!-- 绑定手机号e -->

                <!-- 设置头像昵称s -->
                <block v-if="logintype==5">
                  <form @submit="setnicknameregister">
                    <view style="font-size: 30rpx;font-weight: bold;line-height: 68rpx;">请设置头像昵称</view>
                    <view class="loginform" style="padding: 0;">
                      <!--  #ifdef MP-WEIXIN -->
                      <view class="form-item" style="height:120rpx;line-height:120rpx">
                        <view class="flex1">头像</view>
                        <button open-type="chooseAvatar" @chooseavatar="onChooseAvatar" style="width:100rpx;height:100rpx;" hover-class="none">
                          <image :src="headimg || default_headimg" style="width:100%;height:100%;border-radius:50%"></image>
                        </button> 
                      </view>
                      <view class="form-item" style="height:120rpx;line-height:120rpx">
                        <view class="flex1">昵称</view>
                        <input type="nickname" class="input" placeholder="请输入昵称" name="nickname" placeholder-style="font-size:30rpx;color:#B2B5BE" style="text-align:right"/>
                      </view>
                      <!-- #endif -->
											<!--  #ifdef MP-TOUTIAO -->
											<view class="form-item" style="height:120rpx;line-height:120rpx">
												<view class="flex1">头像</view>
												<button  @tap="ttgetUserinfo" style="width:100rpx;height:100rpx;">
													<image :src="headimg || default_headimg" style="width:100%;height:100%;border-radius:50%"></image>
												</button> 
											</view>
											<view class="form-item" style="height:120rpx;line-height:120rpx">
												<view class="flex1">昵称</view>
												<input @tap="ttgetUserinfo" type="nickname" class="input" :value="nickname" placeholder="请输入昵称" name="nickname" placeholder-style="font-size:30rpx;color:#B2B5BE" style="text-align:right"/>
											</view>
											<!-- #endif -->
                      <!--  #ifndef MP-WEIXIN  -->
											<view v-if="platform !='wx' && platform !='toutiao'">
												<view class="form-item" style="height:120rpx;line-height:120rpx" >
												<view class="flex1">头像</view>
												<image :src="headimg || default_headimg" style="width:100rpx;height:100rpx;border-radius:50%" @tap="uploadHeadimg"></image>
												</view>
												<view class="form-item">
												<view class="flex1">昵称</view>
												<input type="text" class="input" placeholder="请输入昵称" name="nickname" :value="nickname" placeholder-style="font-size:30rpx;color:#B2B5BE" style="text-align:right"/>
												</view>
											 </view>
                      <!-- #endif -->
                      <block v-if="loginset_data.btntype==1">
                        <button class="btn1" form-type="submit" :style="'background:rgba('+t('color1rgb')+');color: '+loginset_data.btnwordcolor">确定</button>
                      </block>
                      <block v-if="loginset_data.btntype==2">
                        <button class="btn1" form-type="submit" :style="'background-color:'+loginset_data.btncolor+';color:'+loginset_data.btnwordcolor">确定</button>
                      </block>
                      <button class="btn1" @tap="nosetnicknameregister" v-if="login_setnickname==1" style="background-color:#EEEEEE ;font-size: 28rpx;">暂不设置</button>
                    </view>
                  </form>
                </block>
                <!-- 设置头像昵称e -->
								
								<!-- 填写邀请码s -->
								<block v-if="logintype==6">
								  <form @submit="setRegisterInvite">
								    <view v-if="reg_invite_code && ((parent && reg_invite_code_show == 1) || !parent)" style="font-size: 30rpx;font-weight: bold;line-height: 68rpx;">请填写邀请码</view>
								    <view class="loginform" style="padding: 0;">
											<view v-if="reg_invite_code && !parent" class="form-item">
											   <input type="text" name="yqcode" @input="yqinput" :value="yqcode" :placeholder="'请输入邀请人'+reg_invite_code_text" placeholder-style="font-size:30rpx;color:#B2B5BE" class="input"/>
											</view>
											<view v-if="reg_invite_code && parent && reg_invite_code_show == 1" class="form-item" style="display: flex;padding-top: 8rpx;">
											  <view style="white-space: nowrap;">邀请人：</view>
											  <image :src="parent.headimg" style="width: 80rpx; height: 80rpx;border-radius: 50%;"></image> 
											  <view class="overflow_ellipsis">{{parent.nickname}} </view>
											</view>
								      <block v-if="loginset_data.btntype==1">
								        <button class="btn1" form-type="submit" :style="'background:rgba('+t('color1rgb')+');color: '+loginset_data.btnwordcolor">确定</button>
								      </block>
								      <block v-if="loginset_data.btntype==2">
								        <button class="btn1" form-type="submit" :style="'background-color:'+loginset_data.btncolor+';color:'+loginset_data.btnwordcolor">确定</button>
								      </block>
								      <button class="btn1" @tap="setRegisterInvitePass" v-if="reg_invite_code==1" style="background-color:#EEEEEE ;font-size: 28rpx;">暂不设置</button>
								    </view>
								  </form>
								</block>
								<!-- 填写邀请码e -->
                
                <!-- s -->
                <block v-if="logintype==1 || logintype==2 || ( logintype==3 && (logintype_8 || logintype_9))">
                  <view v-if="(logintype==1 && (logintype_2 || logintype_3 || logintype_8 || logintype_9)) || (logintype==2 && (logintype_1 || logintype_3 || logintype_8 || logintype_9)) || ( logintype==3 && (logintype_8 || logintype_9))" class="other_login">	
                    <view style="display: flex;width: 100%;" >
                      <view class="other_line"></view>
                      <view style="margin: 0 20rpx;color: #888888;white-space:nowrap;">其他登录方式</view>
                      <view  class="other_line"></view>
                    </view>
                    <view class="other_content">
                      <!-- #ifdef MP-BAIDU -->
                      <button v-if="logintype_3" @login="weixinlogin" open-type="login" style="width:50%;margin: 0 auto;">
                        <view style="width:100rpx;height:104rpx;margin: 0 auto;"><img :src="pre_url+'/static/img/login-'+platformimg+'.png'" style="width:100rpx;height:100rpx;"></view>
                        <view style="font-size: 24rpx;line-height: 40rpx;">{{platformname}}登录</view>
                      </button>
                      <!-- #endif -->
                      <!-- #ifndef MP-BAIDU -->
												<!-- #ifdef MP-WEIXIN -->
												<!-- 微信小程序登录 -->
												<button v-if="logintype_3 && platform == 'wx' && logintype!=3" @tap="weixinlogin" style="width:50%;margin: 0 auto;" hover-class="none">
													<view style="width:100rpx;height:104rpx;margin: 0 auto;"><img :src="wxformimgurl?wxformimgurl:pre_url+'/static/img/login-'+platformimg+'.png'" style="width:100rpx;height:100rpx;"></view>
													<view style="font-size: 24rpx;line-height: 40rpx;color: #888888;">{{wxtext || '授权登录'}}</view>
												</button>
												<!-- 微信小程序登录 end -->
												
												<!-- 授权手机号登录 -->
												<button v-if="logintype_8 && platform == 'wx'" open-type="getPhoneNumber" @getphonenumber="wxtellogin" style="width:50%;margin: 0 auto;" hover-class="none">
													<view style="width:100rpx;height:104rpx;margin: 0 auto;"><img :src="pre_url+'/static/img/login_tel_auth.png'" style="width:100rpx;height:100rpx;"></view>
													<view style="font-size: 24rpx;line-height: 40rpx;color: #888888;">授权手机号登录</view>
												</button>
												<!-- 授权手机号登录 end -->
												<!-- #endif -->
												<!-- #ifndef MP-WEIXIN -->
												<button v-if="logintype_3 && platform != 'wx' && logintype!=3" @tap="weixinlogin" style="width:50%;margin: 0 auto;" hover-class="none">
													<view style="width:100rpx;height:104rpx;margin: 0 auto;"><img :src="wxformimgurl?wxformimgurl:pre_url+'/static/img/login-'+platformimg+'.png'" style="width:100rpx;height:100rpx;"></view>
													<view style="font-size: 24rpx;line-height: 40rpx;color: #888888;">{{wxtext || platformname+'登录'}}</view>
												</button>
												<!-- #endif -->
												
												<!-- #ifdef H5 -->
												 <button v-if="logintype_7 && alih5" @tap="alih5login" style="width:50%;margin: 0 auto;" hover-class="none">
													<view style="width:100rpx;height:104rpx;margin: 0 auto;"><img :src="pre_url+'/static/img/login-alipay.png'" style="width:100rpx;height:100rpx;"></view>
													<view style="font-size: 24rpx;line-height: 40rpx;color: #888888;">支付宝登录</view>
												</button>
												<!-- #endif -->
                     
                      <!-- #endif -->
											<!-- #ifdef MP-ALIPAY -->
											<!-- 授权手机号登录 -->
											<button v-if="logintype_9 && platform == 'alipay'" open-type="getPhoneNumber" @getphonenumber="alitellogin" style="width:50%;margin: 0 auto;" hover-class="none">
											  <view style="width:100rpx;height:104rpx;margin: 0 auto;"><img :src="pre_url+'/static/img/login_tel_auth.png'" style="width:100rpx;height:100rpx;"></view>
											  <view style="font-size: 24rpx;color: #888888;line-height: 40rpx;">授权手机号登录</view>
											</button>
											<!-- 授权手机号登录 end -->
											<!-- #endif -->
											
                      <view v-if="logintype==1 && logintype_2" @tap="changelogintype" data-type="2" style="width:50%;margin: 0 auto;">
                        <view style="width:100rpx;height:104rpx;margin: 0 auto;"><img :src="pre_url+'/static/img/reg-tellogin.png'" style="width:100rpx;height:100rpx;"></view>
                        <view style="font-size: 24rpx;color: #888888;line-height: 40rpx;">手机号快捷登录</view>
                      </view>
                      <view v-if="logintype==2 && logintype_1" @tap="changelogintype" data-type="1" style="width:50%;margin: 0 auto;">
                        <view style="width:100rpx;height:104rpx;margin: 0 auto;"><img :src="pre_url+'/static/img/reg-tellogin.png'" style="width:100rpx;height:100rpx;"></view>
                        <view style="font-size: 24rpx;color: #888888;line-height: 40rpx;">密码登录</view>
                      </view>
                    </view>
                  </view>
                </block>
                <!-- e -->
                
              </view>
            </form>
          </view>
        </view>
      </view>
    </view>
    <!-- e -->

		<view v-if="showxieyi" class="xieyibox">
			<view class="xieyibox-content">
				<scroll-view scroll-y style="height: 100%;" @scrolltolower='xieyiTolower' class="myElement2">
					<parse :content="xycontent"></parse>
					<view style="width: 100%;height:1px;" class="myElement"></view>
				</scroll-view>
				<view class="xieyibut-view flex-y-center">
					<view class="but-class" style="background:#A9A9A9"  @tap="closeXieyi">不同意并退出</view>
					<view class="but-class" :style="{background:reading_completed ? 'linear-gradient(90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)':'#A9A9A9'}"  @tap="hidexieyi">已阅读并同意</view>
				</view>
			</view>
		</view>
		<view v-if="showxieyi2" class="xieyibox">
			<view class="xieyibox-content">
				<view style="overflow:scroll;height:100%;">
					<parse :content="xycontent2"></parse>
				</view>
				<view  class="xieyibut-view flex-y-center">
					<view class="but-class" style="background:#A9A9A9"  @tap="closeXieyi">不同意并退出</view>
					<view class="but-class" :style="{background:'linear-gradient(90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'}"  @tap="hidexieyi2">已阅读并同意</view>
				</view>
			</view>
		</view>

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
			opt:{},
			loading:false,
      isload: false,
			menuindex:-1,
			pre_url:app.globalData.pre_url,
			platform2:app.globalData.platform2,
			
			platform:'',
			platformname:'',
			platformimg:'weixin',
			logintype:0,//1注册登录，2手机验证码登录，3授权登录，4绑定手机号，5设置头像昵称，6输入邀请码
			logintype_1:true,//注册登录
			logintype_2:false,//手机验证码登录
			logintype_3:false,//授权登录
			logintype_4:false,//Apple登录
			logintype_6:false,//Google登录
      logintype_7:false,//支付宝内h5
			logintype_8:false,//微信小程序授权手机号登录
			logintype_9:false,//支付宝小程序授权手机号登录
			isioslogin:false,
			isgooglelogin:false,
			google_client_id:'',
			needsms:false,
			logo:'',
			name:'',
			xystatus:0,
			xyagree_type:0,
			xyname:'',
			xycontent:'',
			xyname2:'',
			xycontent2:'',
			showxieyi:false,
			showxieyi2:false,
			isagree:false,
      smsdjs: '',
			tel:'',
      hqing: 0,
			frompage:'/pages/my/usercenter',
			wxloginclick:false,
			iosloginclick:false,
			googleloginclick:false,
			login_bind:0,
			login_setnickname:0,
			login_mast:false,
			reg_invite_code:0,//邀请码 1开启 0关闭 2强制邀请 3指定会员
			reg_invite_code_text:'',//邀请码文字描述
			reg_invite_code_show:1,//有邀请人时登录、注册页面是否显示邀请码和邀请人
			yqcode:'',//邀请码
      parent:{},
			tmplids:[],
			default_headimg:app.globalData.pre_url + '/static/img/touxiang.png',
			headimg:'',
			nickname:'',
      loginset_type:0,
      loginset_data:'',

      alih5:false,
      ali_appid:'',
      alih5loginclick:false,
      rs_notlogin_to_business:'',//扫码点餐不登陆跳转商家首页
      reading_completed:false,
			checknickname:0,
			wxformimgurl:"",//设计登陆中的微信图标
			wxtext:'',//设计登陆中的微信登录文字
			wxtelloginclick:false,
			alitelloginclick:false,
			wxtellogininfo:''
    };
  },

  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		if(this.opt.frompage) {
			this.frompage = decodeURIComponent(this.opt.frompage);
		}
    if(app.globalData.qrcode){
      var frompage = '/pagesA/qrcode/index?code='+app.globalData.qrcode;
      this.frompage = decodeURIComponent(frompage);
    }
	 
		// #ifdef H5
		if (navigator.userAgent.indexOf('AlipayClient') > -1) {
		  this.alih5 = true;
		}
		// #endif
		if(this.opt.logintype) this.logintype = this.opt.logintype;
		if(this.opt.login_bind) this.login_bind = this.opt.login_bind;
		if(this.opt.checknickname) this.checknickname = this.opt.checknickname;
		this.getdata();
  },
	onShow:function() {
		if(app.globalData.platform=='wx' && app.globalData.hide_home_button==1){
			uni.hideHomeButton();
		}
	},
	onPullDownRefresh: function () {
		this.getdata();
	},
  methods: {
		getHeight() {
			let that = this;
			setTimeout(() => {
				let ceshixian = '';
				let heziheight = '';
				uni.createSelectorQuery().select('.myElement').boundingClientRect(rect => {
					ceshixian = rect.top;
				}).exec()
				uni.createSelectorQuery().select('.myElement2').boundingClientRect(rect => {
					heziheight = rect.height;
					ceshixian = ceshixian - rect.top;
					if(Number(ceshixian) <= Number(heziheight)){
						that.reading_completed = true;
					}
				}).exec()
			},800)
		},
		xieyiTolower(){
			this.reading_completed = true;
		},
		getdata: function () {
			var that = this;
			that.loading = true;
			app.get('ApiIndex/login', {pid:app.globalData.pid,checknickname:that.checknickname}, function (res) {
				that.loading = false;
				if(res.status == 0){
					app.alert(res.mg);return;
				}
				//暂不登录
				that.rs_notlogin_to_business = res.rs_notlogin_to_business;
        if(res.loginset_type){
          that.loginset_type = res.loginset_type
        }
        if(res.loginset_data){
          that.loginset_data = res.loginset_data
        }
				that.logintype_1 = res.logintype_1;
				that.logintype_2 = res.logintype_2;
				that.logintype_3 = res.logintype_3;
				// #ifdef APP-PLUS
				if(that.platform2 == 'ios'){
					if (plus.runtime.isApplicationExist({ pname: 'com.tencent.mm', action: 'weixin://' })) {
						console.log('已安装微信')
					}else{
						that.logintype_3 = false;
						console.log('未安装微信')
					}
				}
				// #endif
				that.logintype_4 = res.logintype_4;
				that.logintype_6 = res.logintype_6 || false;
        that.logintype_7 = res.logintype_7 || false;
				that.logintype_8 = res.logintype_8 || false;
				that.logintype_9 = res.logintype_9 || false;
				that.google_client_id = res.google_client_id || '';
				that.login_mast = res.login_mast;
				that.needsms = res.needsms;
        that.reg_invite_code = res.reg_invite_code;
        that.reg_invite_code_text = res.reg_invite_code_text;
				that.reg_invite_code_show = res.reg_invite_code_show;
        that.parent = res.parent;
				if(that.logintype==0){
					if(that.logintype_1){
						that.logintype = 1;//注册登录
					}else if(that.logintype_2){
						that.logintype = 2;//手机验证码登录
					}else if(that.logintype_3){
						that.logintype = 3;//授权登录
					}else if(that.logintype_7 && that.alih5 ){
            that.logintype = 3;
          }else if(that.logintype_8){
            that.logintype = 8;
          }else if(that.logintype_9){
            that.logintype = 9;
          }				
				}
				that.xystatus = res.xystatus;
				that.xyagree_type = res.xyagree_type ? res.xyagree_type:0;
				if(!that.xyagree_type) that.reading_completed = true;
				that.xyname = res.xyname;
				that.xycontent = res.xycontent;
				that.xyname2 = res.xyname2;
				that.xycontent2 = res.xycontent2;
				that.logo = res.logo;
				that.name = res.name;
				that.platform = res.platform;
				if(that.platform == 'mp' || that.platform == 'wx' || that.platform == 'app'){
					that.platformname = '微信';
					that.platformimg = 'weixin';
					//重置微信图标和文字
					if(that.loginset_data.wxicon !=''){
						that.wxformimgurl = that.loginset_data.wxicon
					}
					if(that.loginset_data.wxtext !=''){
						that.wxtext = that.loginset_data.wxtext
					}
				}
				if(that.platform == 'toutiao'){
					that.platformname = '抖音';
					that.platformimg = 'toutiao';
				}
				if(that.platform == 'alipay'){
					that.platformname = '支付宝';
					that.platformimg = 'alipay';
				}
				if(that.platform == 'qq'){
					that.platformname = 'QQ';
					that.platformimg = 'qq';
				}
				if(that.platform == 'baidu'){
					that.platformname = '百度';
					that.platformimg = 'baidu';
				}
        if(res.ali_appid){
          that.ali_appid = res.ali_appid
        }
		if(res.nickname)that.nickname = res.nickname;
		if(res.headimg)that.headimg = res.headimg;
		
				that.loaded();
				// #ifdef H5
				if(that.logintype_6){
					var hm = document.createElement('script');
					hm.src="https://accounts.google.com/gsi/client";
					document.body.appendChild(hm);
					setTimeout(function(){
						google.accounts.id.initialize({
							client_id: that.google_client_id,
							callback: function(response){
								console.log(response);
								var credential = response.credential;
								var base64Url = credential.split('.')[1];
								var base64 = base64Url.replace(/-/g,'+').replace(/_/g,'/');
								var jsonPayload = decodeURIComponent(
									window.atob(base64).split('').map(function(c){
										return '%'+('00'+c.charCodeAt(0).toString(16)).slice(-2);
									}).join('')
								);
								var resdata = JSON.parse(jsonPayload);
								resdata.openId = resdata.sub;
								console.log(resdata);
								app.showLoading('提交中');
								app.post('ApiIndex/googlelogin',{userInfo:resdata,pid:app.globalData.pid},function(res2){
									app.showLoading(false);
									console.log(res2);
									if (res2.status == 1) {
										app.success(res2.msg);
										setTimeout(function () {
											console.log('frompage')
											console.log(that.frompage)
											app.goto(that.frompage,'redirect');
										}, 1000);
									} else if (res2.status == 3) {
										that.logintype = 5;
										that.isioslogin = false;
										that.isgooglelogin = true;
										that.login_setnickname = res2.login_setnickname;
										that.login_bind = res2.login_bind
									} else if (res2.status == 2) {
										that.logintype = 4;
										that.isioslogin = false;
										that.isgooglelogin = true;
										that.login_bind = res2.login_bind
									} else {
										app.error(res2.msg);
									}
								});
							}
						});
						google.accounts.id.renderButton(
							document.getElementById("googleloginBtn"),
							{ theme: "outline", size: "large",width:'300'}  // customization attributes
						);
						google.accounts.id.prompt();
					},500);
				}else if(that.logintype_7 && that.alih5){
          const oScript = document.createElement('script');
          oScript.type = 'text/javascript';
          oScript.src = 'https://gw.alipayobjects.com/as/g/h5-lib/alipayjsapi/3.1.1/alipayjsapi.min.js';
          document.body.appendChild(oScript);
        }
				// #endif
			});
		},
    formSubmit: function (e) {
			var that = this;
      var formdata = e.detail.value;
      if (formdata.tel == ''){
        app.alert('请输入手机号');
        return;
      }
			if(!app.isPhone(formdata.tel)){
				return app.alert("请输入正确的手机号");
			}
			if(that.logintype == 1){
				if (formdata.pwd == '') {
					app.alert('请输入密码');
					return;
				}
			}
			if(that.logintype == 2){
				if (formdata.smscode == '') {
					app.alert('请输入短信验证码');
					return;
				}
			}

      if(that.logintype == 1 || that.logintype == 2){
        if (that.xystatus == 1 && !that.isagree) {
        	app.error('请先阅读并同意用户注册协议');
        	return false;
        }
      }

			if(that.logintype == 4){
				if (typeof(formdata.pwd) != 'undefined' && formdata.pwd == '') {
					app.alert('请输入密码');
					return;
				}
				if (typeof(formdata.smscode) != 'undefined' && formdata.smscode == '') {
					app.alert('请输入短信验证码');
					return;
				}
			}
			
			app.showLoading('提交中');
      app.post("ApiIndex/loginsub", {tel:formdata.tel,pwd:formdata.pwd,smscode:formdata.smscode,logintype:that.logintype,pid:app.globalData.pid,yqcode:formdata.yqcode,regbid:app.globalData.regbid,regsource:app.globalData.regsource,mdid:app.globalData.mdid}, function (res) {
				app.showLoading(false);
        if (res.status == 1) {
          app.success(res.msg);
					if(res.tmplids){
						that.tmplids = res.tmplids;
					}
					that.subscribeMessage(function () {
						setTimeout(function () {
							app.goto(that.frompage,'redirect');
							console.log(that.frompage,'001')
						}, 1000);
					});
        } else {
          app.error(res.msg);
        }
      });
    },
		getPhoneNumber: function (e) {
			var that = this
			console.log(e);
			if(e.detail.errMsg == "getPhoneNumber:fail user deny"){
				app.error('请同意授权获取手机号');return;
			}
			if(!e.detail.iv || !e.detail.encryptedData){
				app.error('请同意授权获取手机号');return;
			}
			wx.login({success (res1){
				console.log(res1);
				var code = res1.code;
				//用户允许授权
				app.post('ApiIndex/wxRegister',{ headimg:that.headimg,nickname:that.nickname,iv: e.detail.iv,encryptedData:e.detail.encryptedData,code:code,pid:app.globalData.pid,yqcode:that.yqcode,regbid:app.globalData.regbid,regsource:app.globalData.regsource,mdid:app.globalData.mdid},function(res2){
					if (res2.status == 1) {
						app.success(res2.msg);
						if(res2.tmplids){
							that.tmplids = res2.tmplids;
						}
						that.subscribeMessage(function () {
							setTimeout(function () {
								app.goto(that.frompage,'redirect');
							}, 1000);
						});
					} else {
						app.error(res2.msg);
					}
					return;
				})
			}});
		},
		setnicknameregister:function(e){
			//console.log(e);
			//return;
			this.nickname = e.detail.value.nickname;
			if(this.nickname == '' || this.headimg == ''){
				app.alert('请设置头像和昵称');
				return;
			}
			if(this.login_bind!=0){
				this.logintype = 4;
				this.isioslogin = false;
				this.isgooglelogin = false;
			}else{
				this.register(this.headimg,this.nickname,'','');
			}
		},
		nosetnicknameregister:function(){
			this.nickname = '';
			this.headimg = '';
			if(this.login_bind!=0){
				this.logintype = 4;
				this.isioslogin = false;
				this.isgooglelogin = false;
			}else{
				this.register('','','','');
			}
		},
		setRegisterInvite:function(e){
			console.log(e);
			//return;
			this.yqcode = e.detail.value.yqcode;
			if(this.yqcode == '' && !app.globalData.pid && this.reg_invite_code != 3){
				app.alert('请输入邀请码');
				return;
			}
			if(this.login_setnickname!=0){
				this.logintype = 5;
				this.isioslogin = false;
				this.isgooglelogin = false;
			}else{
				if(this.login_bind!=0){
					this.logintype = 4;
					this.isioslogin = false;
					this.isgooglelogin = false;
				}else{
					this.register(this.headimg,this.nickname,'','');
				}
			}
		},
		setRegisterInvitePass:function(){
			if(this.login_setnickname!=0){
				this.logintype = 5;
				this.isioslogin = false;
				this.isgooglelogin = false;
			}else{
				if(this.login_bind!=0){
					this.logintype = 4;
					this.isioslogin = false;
					this.isgooglelogin = false;
				}else{
					this.register('','','','');
				}				
			}
		},
		bindregister:function(e){
			var that = this;
			var formdata = e.detail.value;
      if (formdata.tel == ''){
        app.alert('请输入手机号');
        return;
      }
			if (formdata.smscode == '') {
				app.alert('请输入短信验证码');
				return;
			}
			that.register(this.headimg,this.nickname,formdata.tel,formdata.smscode);
		},
		nobindregister:function(){
			this.register(this.headimg,this.nickname,'','');
		},
		register:function(headimg,nickname,tel,smscode){
			var that = this;
			var url = '';
			if(that.platform == 'app') {
				url = 'ApiIndex/appwxRegister';
				if(that.isioslogin){
					url = 'ApiIndex/iosRegister';
				}
			} else if(that.platform=='mp' || that.platform=='h5') {
				url = 'ApiIndex/shouquanRegister';
			} else  {
				url = 'ApiIndex/'+that.platform+'Register';
			}
			if(that.isgooglelogin){
				url = 'ApiIndex/googleRegister';
			}
			app.post(url,{headimg:headimg,nickname:nickname,tel:tel,smscode:smscode,pid:app.globalData.pid,yqcode:that.yqcode,regbid:app.globalData.regbid,regsource:app.globalData.regsource,mdid:app.globalData.mdid},function(res2){
				if (res2.status == 1) {
					if(that.checknickname == 1){
						app.success('设置成功');
					}else{
						app.success(res2.msg);
					}
				
					if(res2.tmplids){
						that.tmplids = res2.tmplids;
					}
					that.subscribeMessage(function () {
						setTimeout(function () {
							app.goto(that.frompage,'redirect');
						}, 1000);
					});
				} else {
					app.error(res2.msg);
				}
				return;
			});
		},
		authlogin:function(e){
			var that = this;
      var type = e.currentTarget.dataset.type;
			if (that.xystatus == 1 && !that.isagree) {
				app.error('请先阅读并同意用户注册协议');
				return false;
			}
      if(!type){
        that.weixinlogin();
      }else{
        that.alih5login();
      }
		},
		weixinlogin:function(){
			var that = this;
			if (that.xystatus == 1 && !that.isagree) {
				that.showxieyi = true;
				that.wxloginclick = true;
				return;
			}
			console.log('weixinlogin')
			app.showLoading('授权中');
			that.wxloginclick = false;
			app.authlogin(function(res){
				console.log(res);
				app.showLoading(false);
				if (res.status == 1) {
					app.success(res.msg);
					setTimeout(function () {
						console.log('frompage')
						console.log(that.frompage)
						app.goto( that.frompage,'redirect');
					}, 1000);
					
				} else if (res.status == 4) {
					//填写邀请码
					that.logintype = 6;
					that.login_setnickname = res.login_setnickname;
					that.login_bind = res.login_bind;//1可选设置
					that.isioslogin = false;
					that.isgooglelogin = false;
				} else if (res.status == 3) {
					//设置头像昵称
					that.logintype = 5;
					that.login_setnickname = res.login_setnickname;
					that.login_bind = res.login_bind;//1可选设置
					that.isioslogin = false;
					that.isgooglelogin = false;
				} else if (res.status == 2) {
					//绑定手机
					that.logintype = 4;
					that.login_bind = res.login_bind;
					that.isioslogin = false;
					that.isgooglelogin = false;
				} else {
					app.error(res.msg);
				};
				app.showLoading(false);
			},{frompage:that.frompage,yqcode:that.yqcode});
		},
		ioslogin:function(){
			//#ifdef APP-PLUS
			var that = this;
			if (that.xystatus == 1 && !that.isagree) {
				that.showxieyi = true;
				that.iosloginclick = true;
				return false;
			}
			uni.login({  
				provider: 'apple',  
				success: function (loginRes) {  
					console.log(loginRes);
					// 登录成功  
					uni.getUserInfo({  
						provider: 'apple',  
						success(res) { 
							// 获取用户信息成功
							console.log(res)
							if(res.userInfo && res.userInfo.openId){
								app.post('ApiIndex/ioslogin',{userInfo:res.userInfo,pid:app.globalData.pid,regbid:app.globalData.regbid,regsource:app.globalData.regsource,mdid:app.globalData.mdid},function(res2){
									console.log(res2);
									if (res2.status == 1) {
										app.success(res2.msg);
										setTimeout(function () {
											console.log('frompage')
											console.log(that.frompage)
											app.goto(that.frompage,'redirect');
										}, 1000);
									} else if (res2.status == 3) {
										that.logintype = 5;
										that.isioslogin = true;
										that.isgooglelogin = false;
										that.login_setnickname = res2.login_setnickname
										that.login_bind = res2.login_bind
									} else if (res2.status == 2) {
										that.logintype = 4;
										that.isioslogin = true;
										that.isgooglelogin = false;
										that.login_bind = res2.login_bind
									} else {
										app.error(res2.msg);
									}
								});
							}
						}  
					})  
				},  
				fail: function (err) {  
					console.log(err);
					app.error('登录失败');
				}  
			});
			//#endif
		},
		googlelogin:function(){
			var that = this;
			if (that.xystatus == 1 && !that.isagree) {
				that.showxieyi = true;
				that.googleloginclick = true;
				return false;
			}
			// #ifdef APP-PLUS
			uni.login({  
				provider: 'google',  
				success: function (loginRes) {  
					console.log(loginRes);
					// 登录成功  
					uni.getUserInfo({  
						provider: 'google',  
						success(res) { 
							// 获取用户信息成功
							console.log(res)
							//alert(JSON.stringify(res));
							//if(res.userInfo && res.userInfo.openId){
								app.post('ApiIndex/googlelogin',{userInfo:res.userInfo,pid:app.globalData.pid,regbid:app.globalData.regbid,regsource:app.globalData.regsource,mdid:app.globalData.mdid},function(res2){
									console.log(res2);
									if (res2.status == 1) {
										app.success(res2.msg);
										setTimeout(function () {
											console.log('frompage')
											console.log(that.frompage)
											app.goto(that.frompage,'redirect');
										}, 1000);
									} else if (res2.status == 3) {
										that.logintype = 5;
										that.isioslogin = false;
										that.isgooglelogin = true;
										that.login_setnickname = res2.login_setnickname
										that.login_bind = res2.login_bind
									} else if (res2.status == 2) {
										that.logintype = 4;
										that.isioslogin = false;
										that.isgooglelogin = true;
										that.login_bind = res2.login_bind
									} else {
										app.error(res2.msg);
									}
								});
							//}
						}  
					})  
				},  
				fail: function (err) {  
					console.log(err);
					app.error('登录失败');
				}  
			});
			// #endif
		},
		changelogintype:function(e){
			var logintype = e.currentTarget.dataset.type
			this.logintype = logintype;
		},
		promptRead(){
			if(this.xyagree_type) app.error('请滑动到底部，阅读完协议！');
			this.showxieyi = true;
			this.$nextTick(() => {
				this.getHeight()
			})
		},
    isagreeChange: function (e) {
      var val = e.detail.value;
      if (val.length > 0) {
        this.isagree = true;
      } else {
        this.isagree = false;
      }
      console.log(this.isagree);
    },
    showxieyiFun: function () {
      this.showxieyi = true;
			this.$nextTick(() => {
				this.getHeight()
			})
    },
		// 不同意协议
		closeXieyi(){
			this.showxieyi = false;
			this.showxieyi2 = false;
			this.isagree = false;
		},
    hidexieyi: function () {
			if(!this.reading_completed) return app.error('请滑动到底部，阅读完协议！')
      this.showxieyi = false;
			this.isagree = true;
			if(this.wxloginclick){
				this.weixinlogin();
			}
			if(this.iosloginclick){
				this.ioslogin();
			}
			if(this.googleloginclick){
				this.googlelogin();
			}
      if(this.alih5loginclick){
        that.alih5login();
      }
			if(this.wxtelloginclick){
				this.wxtellogin();
			}
			if(this.alitelloginclick){
				this.alitellogin();
			}
    },
    showxieyiFun2: function () {
      this.showxieyi2 = true;
    },
    hidexieyi2: function () {
      this.showxieyi2 = false;
			this.isagree = true;
			if(this.wxloginclick){
				this.weixinlogin();
			}
			if(this.iosloginclick){
				this.ioslogin();
			}
			if(this.googleloginclick){
				this.googlelogin();
			}
      if(this.alih5loginclick){
        that.alih5login();
      }
			if(this.wxtelloginclick){
				this.wxtellogin();
			}
			if(this.alitelloginclick){
				this.alitellogin();
			}
    },
    telinput: function (e) {
      this.tel = e.detail.value
    },
		yqinput: function (e) {
      this.yqcode = e.detail.value
    },
		uploadHeadimg:function(){
			var that = this;
			uni.chooseImage({
				count: 1,
				sizeType: ['original', 'compressed'],
				sourceType: ['album', 'camera'],
				success: function(res) {
					var tempFilePaths = res.tempFilePaths;
					var tempFilePath = tempFilePaths[0];
					app.showLoading('上传中');
					uni.uploadFile({
						url: app.globalData.baseurl + 'ApiImageupload/uploadImg/aid/' + app.globalData.aid + '/platform/' + app.globalData.platform +'/session_id/' +app.globalData.session_id+'/isheadimg/1',
						filePath: tempFilePath,
						name: 'file',
						success: function(res) {
							if(typeof res.data == 'string'){
								var data = JSON.parse(res.data);
							}else{
								var data = res.data;
							}
							app.showLoading(false);
							if (data.status == 1) {
								that.headimg = data.url;
							} else {
								app.alert(data.msg);
							}
						},
						fail: function(res) {
							app.showLoading(false);
							app.alert(res.errMsg);
						}
					});
				},
				fail: function(res) { //alert(res.errMsg);
				}
			});
		},
		onChooseAvatar:function(e){
			// console.log(e)
			var that = this;
			app.showLoading('上传中');
			uni.uploadFile({
				url: app.globalData.baseurl + 'ApiImageupload/uploadImg/aid/' + app.globalData.aid + '/platform/' + app.globalData.platform +'/session_id/' +app.globalData.session_id+'/isheadimg/1',
				filePath: e.detail.avatarUrl,
				name: 'file',
				success: function(res) {
					app.showLoading(false);
					var data = JSON.parse(res.data);
					if (data.status == 1) {
						that.headimg = data.url;
					} else {
						app.alert(data.msg);
					}
				},
				fail: function(res) {
					app.showLoading(false);
					app.alert(res.errMsg);
				}
			});
		},
    smscode: function () {
      var that = this;
      if (that.hqing == 1) return;
      that.hqing = 1;
      var tel = that.tel;
      if (tel == '') {
        app.alert('请输入手机号码');
        that.hqing = 0;
        return false;
      }
      if (!app.isPhone(tel)) {
        app.alert("手机号码有误，请重填");
        that.hqing = 0;
        return false;
      }
			if(that.logintype == 1 || that.logintype == 2){
				if (that.xystatus == 1 && !that.isagree) {
					app.error('请先阅读并同意用户注册协议');
					that.hqing = 0;
					return false;
				}
			}
      app.post("ApiIndex/sendsms", {tel: tel}, function (data) {
        if (data.status != 1) {
          app.alert(data.msg);return;
        }
      });
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
    },
    alih5login:function(){
      // #ifdef H5
      var that = this;
      var ali_appid = that.ali_appid;
    
      if (that.xystatus == 1 && !that.isagree) {
      	that.showxieyi = true;
      	that.alih5loginclick = true;
      	return;
      }
      that.alih5loginclick = false;
      if(ali_appid){
        app.showLoading('登录中');
        ap.getAuthCode ({
            appId :  ali_appid ,
            scopes : ['auth_base'],
        },function(res){
           //var res = JSON.stringify(res);
            if(!res.error && res.authCode){
                app.post('ApiIndex/alipaylogin', {
                	code: res.authCode,
                	pid: app.globalData.pid,
                  platform:"h5"
                }, function(res2) {
                  app.showLoading(false);
                  
                	if (res2.status == 1) {
                		app.success(res2.msg);
                		setTimeout(function () {
                			console.log('frompage')
                			console.log(that.frompage)
                			app.goto(that.frompage,'redirect');
                		}, 1000);
                	} else if (res2.status == 3) {
                		that.logintype = 5;
                		that.isioslogin = true;
                		that.isgooglelogin = false;
                		that.login_setnickname = res2.login_setnickname
                		that.login_bind = res2.login_bind
                	} else if (res2.status == 2) {
                		that.logintype = 4;
                		that.isioslogin = true;
                		that.isgooglelogin = false;
                		that.login_bind = res2.login_bind
                	} else {
                		app.error(res2.msg);
                	}
                });
            }else{
              app.showLoading(false);
              
              if(res.errorMessage){
                app.alert(res.errorMessage);
              }else if(res.errorDesc){
                app.alert(res.errorDesc);
              }else{
                app.alert('授权出错');
              }
              return
            }
        });
      }else{
        app.alert('系统未配置支付宝参数');
        return
      }
      // #endif
    },
		ttgetUserinfo(){
			// #ifdef MP-TOUTIAO
			var that = this;
			tt.getUserProfile({
				success(res) {
					that.nickname = res.userInfo.nickName;
					that.headimg = res.userInfo.avatarUrl;
				},
				fail(res) {
					console.log("getUserProfile 调用失败", res);
				},
			});
      // #endif
		},
		noLogin(){
			var that = this;
			if(that.rs_notlogin_to_business==1){
				var url = that.frompage.split('?')[0];
				var params = app.getparams(that.frompage);
				if(url=='/restaurant/shop/index' && params.bid && params.bid > 0){
					app.goto('/pagesExt/business/index?id='+params.bid);return;
				}
			}
			that.goback();
		},
    getBaiduPhoneNumber: function (e) {
      // #ifdef MP-BAIDU
    	var that = this
    	console.log(e);
    	if(e.detail.errMsg == "getPhoneNumber:fail auth deny"){
    		app.error('请同意授权获取手机号');return;
    	}
    	if(!e.detail.iv || !e.detail.encryptedData){
    		app.error('请同意授权获取手机号');return;
    	}
      uni.getLoginCode({
      	success: res => {
      		console.log(res);
      		var code = res.code;
      		//用户允许授权
      		var postdata = { 
      		  headimg:that.headimg,
      		  nickname:that.nickname,
      		  code:code,
      		  iv: e.detail.iv,
      		  encryptedData:e.detail.encryptedData,
      		  pid:app.globalData.pid,
      		  yqcode:that.yqcode,
      		  regbid:app.globalData.regbid,
            regsource:app.globalData.regsource,
            mdid:app.globalData.mdid
      		}
      		app.post('ApiIndex/baiduRegister',postdata,function(res2){
      			if (res2.status == 1) {
      				app.success(res2.msg);
      				if(res2.tmplids){
      					that.tmplids = res2.tmplids;
      				}
      				that.subscribeMessage(function () {
      					setTimeout(function () {
      						app.goto(that.frompage,'redirect');
      					}, 1000);
      				});
      			} else {
      				app.error(res2.msg);
      			}
      			return;
      		})
      	},
      	fail: err => {
      		typeof callback == "function" && callback({
      			status: 0,
      			msg: err.errMsg
      		});
      	}
      });
      // #endif
    },
		// 微信小程序授权手机号登录
		wxtellogin: function (e) {
			// #ifdef MP-WEIXIN
			var that = this
			if(e && e.type=="getphonenumber"){
				that.wxtellogininfo = e
			}
			if(that.wxtellogininfo){
				var e = that.wxtellogininfo;
			}
			if(e.detail.errMsg == "getPhoneNumber:fail user deny"){
				app.error('请同意授权获取手机号');return;
			}
			if(!e.detail.iv || !e.detail.encryptedData){
				app.error('请同意授权获取手机号');return;
			}
			
			if (that.xystatus == 1 && !that.isagree) {
				that.showxieyi = true;
				that.wxtelloginclick = true;
				return;
			}
			that.wxtelloginclick = false;
			that.wxtellogininfo = '';
			
			app.showLoading('授权中');
			wx.login({success (res1){
				// console.log(res1);
				var code = res1.code;
				//用户允许授权
				app.post('ApiIndex/wxTelLogin',{iv: e.detail.iv,encryptedData:e.detail.encryptedData,code:code,pid:app.globalData.pid,yqcode:that.yqcode,regbid:app.globalData.regbid},function(res){
					app.showLoading(false);
					if (res.status == 1) {
						app.success(res.msg);
						setTimeout(function () {
							console.log('frompage')
							console.log(that.frompage)
							app.goto( that.frompage,'redirect');
						}, 1000);
					}else if (res.status == 4) {
						//填写邀请码
						that.logintype = 6;
						that.login_setnickname = res.login_setnickname;
						that.login_bind = res.login_bind;//1可选设置
						that.isioslogin = false;
						that.isgooglelogin = false;
					} else if (res.status == 3) {
						//设置头像昵称
						that.logintype = 5;
						that.login_setnickname = res.login_setnickname;
						that.login_bind = res.login_bind;//1可选设置
						that.isioslogin = false;
						that.isgooglelogin = false;
					}  else {
						app.error(res.msg);
					}
					return;
				})
			}});
			// #endif			
		},
		// 支付宝小程序授权手机号登录
		alitellogin:function(e){
			// #ifdef MP-ALIPAY
			var that = this;
			if(e && e.type=="getphonenumber"){
				that.wxtellogininfo = e
			}
			if(that.wxtellogininfo){
				var e = that.wxtellogininfo;
			}
			if(e.detail.errMsg != "getPhoneNumber:ok"){
				app.error('请同意授权获取手机号');return;
			}
			if(!e.detail.sign || !e.detail.encryptedData){
				app.error('请同意授权获取手机号');return;
			}
			
			if (that.xystatus == 1 && !that.isagree) {
				that.showxieyi = true;
				that.alitelloginclick = true;
				return;
			}
			that.alitelloginclick = false;
			
			app.showLoading('授权中');
			my.getAuthCode ({
					scopes : 'auth_base',
			},function(res){
				console.log(res);
				 //var res = JSON.stringify(res);
					if(!res.error && res.authCode){
							app.post('ApiIndex/aliTelLogin', {sign: e.detail.sign,encryptedData:e.detail.encryptedData,code: res.authCode,pid:app.globalData.pid,yqcode:that.yqcode,regbid:app.globalData.regbid}, function(res) {
								app.showLoading(false);
								if (res.status == 1) {
									app.success(res.msg);
									setTimeout(function () {
										console.log('frompage')
										console.log(that.frompage)
										app.goto( that.frompage,'redirect');
									}, 1000);
								}else if (res.status == 4) {
									//填写邀请码
									that.logintype = 6;
									that.login_setnickname = res.login_setnickname;
									that.login_bind = res.login_bind;//1可选设置
									that.isioslogin = false;
									that.isgooglelogin = false;
								} else if (res.status == 3) {
									//设置头像昵称
									that.logintype = 5;
									that.login_setnickname = res.login_setnickname;
									that.login_bind = res.login_bind;//1可选设置
									that.isioslogin = false;
									that.isgooglelogin = false;
								}  else {
									app.error(res.msg);
								}
							});
					}else{
						if(res.errorMessage){
							app.alert(res.errorMessage);
						}else if(res.errorDesc){
							app.alert(res.errorDesc);
						}else{
							app.alert('授权出错');
						}
						return
					}
			});
			// #endif
		},
  }
};
</script>

<style>
page{background:#ffffff;width: 100%;height:100%;}
.container{width:100%;height:100%;}

.text-center { text-align: center;}
.title{margin:70rpx 50rpx 50rpx 40rpx;height:60rpx;line-height:60rpx;font-size: 48rpx;font-weight: bold;color: #000000;}
.loginform{ width:100%;padding:0 50rpx;border-radius:5px;}
.loginform .form-item{display:flex;align-items:center;width:100%;border-bottom: 1px #ededed solid;height:88rpx;line-height:88rpx;border-bottom:1px solid #F0F3F6;margin-top:20rpx;background: #fff;border-radius: 8rpx;padding: 0 20rpx;}

.loginform .form-item .img{width:44rpx;height:44rpx;margin-right:30rpx}
.loginform .form-item .input{flex:1;color: #000;background: none;}
.loginform .form-item .code{font-size:30rpx}
.xieyi-item{display:flex;align-items:center;margin-top:30rpx}
.xieyi-item{font-size:24rpx;color:#B2B5BE}
.xieyi-item .checkbox{transform: scale(0.6);}

.authlogin{display:flex;flex-direction:column;align-items:center}
.authlogin-logo{width:180rpx;height:180rpx;margin-top:120rpx}
.authlogin-name{color:#999999;font-size:30rpx;margin-top:60rpx;}
.authlogin-btn{width:580rpx;height:96rpx;line-height:96rpx;background:#51B1F5;border-radius:48rpx;color:#fff;margin-top:100rpx}
.authlogin-btn2{width:580rpx;height:96rpx;line-height:96rpx;background:#EEEEEE;border-radius:48rpx;color:#A9A9A9;margin-top:20rpx}
.ioslogin-btn{width:580rpx;height:96rpx;line-height:96rpx;background:#fff;border-radius:48rpx;color:#fff;border:1px solid #555;color:#333;font-weight:bold;margin-top:30rpx;font-size:30rpx;display:flex;justify-content:center;align-items:center}
.ioslogin-btn image{width:26rpx;height:26rpx;margin-right:16rpx;}

.googlelogin-btn{width:580rpx;height:96rpx;line-height:96rpx;background:#fff;border-radius:48rpx;color:#fff;border:1px solid #555;color:#333;font-weight:bold;margin-top:30rpx;font-size:30rpx;display:flex;justify-content:center;align-items:center}

.googlelogin-btn2{margin-top:30rpx;display:flex;justify-content:center;align-items:center}

.xieyibox{width:100%;height:100%;position:fixed;top:0;left:0;z-index:99;background:rgba(0,0,0,0.7)}
.xieyibox-content{width:90%;margin:0 auto;/*  #ifdef  MP-TOUTIAO */height:60%;/*  #endif  *//*  #ifndef  MP-TOUTIAO */height:80%;/*  #endif  */margin-top:20%;background:#fff;color:#333;padding:5px 10px 50px 10px;position:relative;border-radius:2px;}
.xieyibox-content .xieyibut-view{height: 60rpx;position:absolute;z-index:9999;bottom:10px;left:0;right:0;margin:0 auto;justify-content: space-around;}
.xieyibox-content .xieyibut-view .but-class{text-align:center; width: auto;height: 60rpx; line-height: 60rpx; color: #fff; border-radius: 8rpx;padding:0rpx 25rpx;}

.bg_div1{width:100%;min-height: 100%;overflow: hidden;}
.content_div1{width: 700rpx; margin: 0 auto;margin-bottom: 60rpx;}
.title1{opacity: 1;font-size: 50rpx;font-weight: bold;line-height: 90rpx;text-align: left;margin-top: 80rpx;}
.subhead1{font-size: 28rpx;font-weight: 500;line-height: 40rpx;}
.card_div1{width: 100%;padding:40rpx;border-radius: 24rpx;margin-top: 40rpx;}
.tel1{width:100%;height:100rpx;border-radius: 100rpx;line-height: 100rpx;background-color: #F5F7FA;padding:0 40rpx;margin: 20rpx 0;margin-top: 30rpx;}
.code1{height: 100rpx;font-size: 24rpx;line-height: 100rpx;float: right;}
.btn1{width:100%;height:100rpx;border-radius: 100rpx;line-height: 100rpx;margin: 20rpx 0;text-align: center;font-weight: bold;color: #A9A9A9;font-size: 30rpx}
.other_line{width: 146rpx;height: 2rpx;background: #D8D8D8;margin-top: 20rpx;}
.logo2{width: 200rpx;height: 200rpx;margin: 0 auto;margin-top:40rpx;margin-bottom: 40rpx;border-radius: 12rpx;overflow: hidden;}
.inputcode{width:300rpx;height:100rpx;line-height: 100rpx;display: inline-block;background: none;}
.input_val{width:100%;height:100rpx;line-height: 100rpx;background: none;}
.xycss1{line-height: 60rpx;font-size: 24rpx;overflow: hidden;}
/* .other_login{width:420rpx;margin: 60rpx auto;} */
.other_login{width:500rpx;margin: 60rpx auto;}
.overflow_ellipsis{overflow: hidden;text-overflow: ellipsis;white-space: nowrap;width: 360rpx;}
.other_content{width: 100%;margin-top: 60rpx;text-align: center;display: flex;justify-content:center;}
</style>