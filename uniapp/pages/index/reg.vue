<template>
<view class="container">
	<block v-if="isload">
    <!-- s -->
    <view style="width:100%;height: 100%;">
      <view class="bg_div1" :style="loginset_data.bgtype==1?'background:'+loginset_data.bgcolor:'background:url('+loginset_data.bgimg+') no-repeat center;background-size:100% 100%'">
        <view style="overflow: hidden;">
          <view class="content_div1">
            <view class="card_div1" :style="'background:'+loginset_data.cardcolor">
              <block v-if="logintype!=4 && logintype!=5 && logintype!=6">
                <form @submit="formSubmit">
                  <view class="title1" :style="'color:'+loginset_data.titlecolor+';text-align:'+loginset_data.titletype+';margin-top: 20rpx;font-size: 40rpx;'">
                    注册账号
                  </view>
                  <view class="regform" style="margin-top: 20rpx;">
                  <!-- 系统注册S -->
                  <block v-if="!show_custom_field">
                    <view class="form-item">
                      <image :src="pre_url+'/static/img/reg-tel.png'" class="img"/>
                      <input type="text" class="input" :placeholder="tel_placeholder" placeholder-style="font-size:30rpx;color:#B2B5BE" name="tel" value="" @input="telinput"/>
                    </view>
                    <view class="form-item" v-if="needsms">
                      <image :src="pre_url+'/static/img/reg-code.png'" class="img"/>
                      <input type="text" class="input" placeholder="请输入验证码" placeholder-style="font-size:30rpx;color:#B2B5BE" name="smscode" value=""/>
                      <view class="code" :style="'color:'+loginset_data.codecolor" @tap="smscode">{{smsdjs||'获取验证码'}}</view>
                    </view>
                    <view class="form-item">
                      <image :src="pre_url+'/static/img/reg-pwd.png'" class="img"/>
                      <input type="text" class="input" placeholder="6-16位字母数字组合密码" placeholder-style="font-size:30rpx;color:#B2B5BE" name="pwd" value="" :password="true"/>
                    </view>
                    <view class="form-item">
                      <image :src="pre_url+'/static/img/reg-pwd.png'" class="img"/>
                      <input type="text" class="input" placeholder="再次输入登录密码" placeholder-style="font-size:30rpx;color:#B2B5BE" name="repwd" value="" :password="true"/>
                    </view>
                    <view class="form-item" v-if="reg_invite_code && !parent">
                      <image :src="pre_url+'/static/img/reg-yqcode.png'" class="img"/>
                      <input type="text" class="input" :placeholder="'请输入邀请人'+reg_invite_code_text" placeholder-style="font-size:30rpx;color:#B2B5BE" name="yqcode" @input="yqinput" :value="yqcode"/>
                    </view>
                    <view class="form-item" v-if="reg_invite_code && parent && reg_invite_code_show == 1" style="color:#666;">
                      <block v-if="reg_invite_code_type == 0 ">
                      邀请人：<image :src="parent.headimg" style="width: 80rpx; height: 80rpx;border-radius: 50%;"></image> {{parent.nickname}} 
                      </block>
                      <block v-else>
                      邀请码：{{parent.yqcode}} 
                      </block>
                    </view>
                  </block>
                  <!-- 系统注册E -->
                  <!-- 自定义注册S -->
                  <block v-if="show_custom_field">
                    <view class="dp-form-item">
                      <image v-if="showicon" :src="pre_url+'/static/img/reg-tel.png'" style="max-width: 60rpx;height: 60rpx;" mode="widthFix"></image>
                      <view class="label">手机号<text style="color:red"> * </text></view>
                      <input type="text" class="input" :placeholder="tel_placeholder" placeholder-style="font-size:30rpx;color:#B2B5BE" name="tel" value="" @input="telinput"/>
                    </view>
                    <view class="dp-form-item" v-if="needsms">
                      <image v-if="showicon" :src="pre_url+'/static/img/reg-code.png'" style="max-width: 60rpx;height: 60rpx;" mode="widthFix"></image>
                      <text class="label">验证码<text :style="'color:'+loginset_data.cardcolor"> * </text></text>
                      <view class="tel1" style="display: flex;">
                        <input type="text" class="input" placeholder="请输入验证码" placeholder-style="font-size:30rpx;color:#B2B5BE" name="smscode" value="" style="border: 0;padding: 0;"/>
                        <view class="code" :style="'color:'+loginset_data.codecolor" @tap="smscode">{{smsdjs||'获取验证码'}}</view>
                      </view>
                    </view>
                    <view class="dp-form-item">
                      <image v-if="showicon" :src="pre_url+'/static/img/reg-pwd.png'" style="max-width: 60rpx;height: 60rpx;" mode="widthFix"></image>
                      <view class="label">密码<text style="color:red"> * </text></view>
                      <input type="text" class="input" placeholder="6-16位字母数字组合密码" placeholder-style="font-size:30rpx;color:#B2B5BE" name="pwd" value="" :password="true"/>
                    </view>
                    <view class="dp-form-item">
                      <image v-if="showicon" :src="pre_url+'/static/img/reg-pwd.png'" style="max-width: 60rpx;height: 60rpx;" mode="widthFix"></image>
                      <!-- 跟随背景颜色展位保持对齐 -->
                      <view class="label">确认密码<text :style="'color:'+loginset_data.cardcolor"> * </text></view>
                      <input type="text" class="input" placeholder="再次输入登录密码" placeholder-style="font-size:30rpx;color:#B2B5BE" name="repwd" value="" :password="true"/>
                    </view>
                    <view class="dp-form-item" v-if="reg_invite_code && !parent">
                      <image v-if="showicon" :src="pre_url+'/static/img/reg-yqcode.png'" style="max-width: 60rpx;height: 60rpx;" mode="widthFix"></image>
                      <text class="label">邀请码<text :style="'color:'+loginset_data.cardcolor"> * </text></text>
                      <input type="text" class="input" :placeholder="'请输入邀请人'+reg_invite_code_text" placeholder-style="font-size:30rpx;color:#B2B5BE" name="yqcode" :value="yqcode"/>
                    </view>
                    <view class="dp-form-item" v-if="reg_invite_code && parent && reg_invite_code_show == 1" style="color:#666;">
                      <image v-if="showicon" :src="pre_url+'/static/img/reg-yqcode.png'" style="max-width: 60rpx;height: 60rpx;" mode="widthFix"></image>
                      <block v-if="reg_invite_code_type == 0 ">
												<view class="label">邀请人</view>
												<image :src="parent.headimg" style="width: 80rpx; height: 80rpx;border-radius: 50%;"></image> 
												<view class="overflow_ellipsis">{{parent.nickname}} </view>
                      </block>
                      <block v-else>
												<view class="label">邀请码</view>{{parent.yqcode}} 
                      </block>
                    </view>
                    <view class="custom_field" v-if="show_custom_field">
                      <view :class="'dp-form-item'" v-for="(item,idx) in formfields.content"  :key="idx">
                        <image v-if="item.val8 !== undefined && item.val8 !== null && showicon" :src="item.val8" style="max-width: 60rpx;height: 60rpx;" mode="widthFix"></image>
                        <view class="label">{{item.val1}}<text :style="{color:item.val3==1 ? 'red' : loginset_data.cardcolor}"> *</text></view>
                        <block v-if="item.key=='input' || item.key=='realname' || item.key=='usercard'">
                          <text v-if="item.val5" style="margin-right:10rpx">{{item.val5}}</text>
                          <input :type="item.input_type" :name="'form'+idx" class="input" :placeholder="item.val2" placeholder-style="font-size:30rpx;color:#B2B5BE" :value="custom_formdata['form'+idx]" @input="setfield" :data-formidx="'form'+idx"/>
                        </block>
                        <block v-if="item.key=='textarea'">
                          <textarea :name="'form'+idx" class='textarea' :placeholder="item.val2" placeholder-style="font-size:30rpx;color:#B2B5BE"  :value="custom_formdata['form'+idx]" @input="setfield" :data-formidx="'form'+idx"/>
                        </block>
                        <block v-if="item.key=='radio' || item.key=='sex'">
                          <radio-group class="flex" :name="'form'+idx" style="flex-wrap:wrap" @change="setfield" :data-formidx="'form'+idx">
                            <label v-for="(item1,idx1) in item.val2" :key="item1.id" class="flex-y-center">
                                <radio class="radio" :value="item1" style="transform: scale(0.8);" :checked="custom_formdata['form'+idx] && custom_formdata['form'+idx]==item1 ? true : false"/>{{item1}}
                            </label>
                          </radio-group>
                        </block>
                        <block v-if="item.key=='checkbox'">
                          <checkbox-group :name="'form'+idx" class="flex" style="flex-wrap:wrap" @change="setfield" :data-formidx="'form'+idx">
                            <label v-for="(item1,idx1) in item.val2" :key="item1.id" class="flex-y-center">
                              <checkbox class="checkbox" style="transform: scale(0.8);" :value="item1" :checked="custom_formdata['form'+idx] && inArray(item1,custom_formdata['form'+idx]) ? true : false"/>{{item1}}
                            </label>
                          </checkbox-group>
                        </block>
                        <block v-if="item.key=='selector'">
                          <picker class="picker" mode="selector" :name="'form'+idx" :value="editorFormdata[idx]" :range="item.val2" @change="editorBindPickerChange" :data-idx="idx" :data-formidx="'form'+idx">
                            <view v-if="editorFormdata[idx] || editorFormdata[idx]===0"> {{item.val2[editorFormdata[idx]]}}</view>
                            <view v-else style="color: #b2b5be;">请选择</view>
                          </picker>
                        </block>
                        <block v-if="item.key=='time'">
                          <picker class="picker" mode="time" :name="'form'+idx" :value="custom_formdata['form'+idx]" :start="item.val2[0]" :end="item.val2[1]" :range="item.val2" @change="editorBindPickerChange" :data-idx="idx" :data-formidx="'form'+idx">
                            <view v-if="editorFormdata[idx]">{{editorFormdata[idx]}}</view>
                            <view v-else style="color: #b2b5be;">请选择</view>
                          </picker>
                        </block>
                        <block v-if="item.key=='date' || item.key=='birthday'">
                          <picker class="picker" mode="date" :name="'form'+idx" :value="custom_formdata['form'+idx]" :start="item.val2[0]" :end="item.val2[1]" :range="item.val2" @change="editorBindPickerChange" :data-idx="idx" :data-formidx="'form'+idx">
                            <view v-if="editorFormdata[idx]">{{editorFormdata[idx]}}</view>
                            <view v-else style="color: #b2b5be;">请选择</view>
                          </picker>
                        </block>
                      
                        <block v-if="item.key=='region'">
                            <uni-data-picker :localdata="items" popup-title="请选择省市区" :placeholder="custom_formdata['form'+idx] || '请选择省市区'" @change="onchange" :data-formidx="'form'+idx"></uni-data-picker>
                            <input type="text" style="display:none" :name="'form'+idx" :value="regiondata ? regiondata : custom_formdata['form'+idx]"/>
                        </block>
                        <block v-if="item.key=='upload'">
                          <input type="text" style="display:none" :name="'form'+idx" :value="editorFormdata[idx]"/>
                          <view class="flex" style="flex-wrap:wrap;">
                            <view class="dp-form-imgbox" v-if="editorFormdata[idx]">
                              <view class="dp-form-imgbox-close" @tap="removeimg" :data-idx="idx" :data-formidx="'form'+idx"><image :src="pre_url+'/static/img/ico-del.png'" class="image"></image></view>
                              <view class="dp-form-imgbox-img"><image class="image" :src="editorFormdata[idx]" @click="previewImage" :data-url="editorFormdata[idx]" mode="widthFix" :data-idx="idx"/></view>
                            </view>
                            <view v-else class="dp-form-uploadbtn" :style="{background:'url('+pre_url+'/static/img/shaitu_icon.png) no-repeat 60rpx',backgroundSize:'80rpx 80rpx',backgroundColor:'#F3F3F3'}" @click="editorChooseImage" :data-idx="idx" :data-formidx="'form'+idx"></view>
                          </view>
                        </block>
												<!-- #ifdef H5 || MP-WEIXIN -->
												<block v-if="item.key=='upload_file'">
												  <input type="text" style="display:none" :name="'form'+idx" :value="editorFormdata[idx]"/>
												  <view class="flex" style="flex-wrap:wrap;">
												    <view class="dp-form-imgbox" v-if="editorFormdata[idx]">
												      <view class="dp-form-imgbox-close" @tap="removeimg" :data-idx="idx" :data-formidx="'form'+idx"><image :src="pre_url+'/static/img/ico-del.png'" class="image"></image></view>
												      <view class="dp-form-imgbox-img">已上传</view>
												    </view>
												    <view v-else class="dp-form-uploadbtn" :style="{background:'url('+pre_url+'/static/img/shaitu_icon.png) no-repeat 60rpx',backgroundSize:'80rpx 80rpx',backgroundColor:'#F3F3F3'}" @click="editorChooseFile" :data-idx="idx" :data-formidx="'form'+idx"></view>
												  </view>
												</block>
												<!-- #endif -->
                      </view>
                    </view>
                    <view style="display:none">{{test}}</view>
                  </block>
                  <!-- 自定义注册E -->
                  
                  <view v-if="xystatus==1" class="xycss1">
                    <checkbox-group @change="isagreeChange" style="display: inline-block;">
											<checkbox style="transform: scale(0.6)"  value="1" :checked="isagree"/>
											<text :style="'color:'+loginset_data.xytipcolor">{{loginset_data.xytipword}}</text>
                    </checkbox-group>
                    <text @tap="showxieyiFun" :style="'color:'+loginset_data.xycolor">{{xyname}}</text>
                    <text @tap="showxieyiFun"  v-if="xyname2" :style="'color:'+loginset_data.xytipcolor">和</text>
                    <text @tap="showxieyiFun2" v-if="xyname2" :style="loginset_data.xycolor?'color:'+loginset_data.xycolor:'color:'+t('color1')">{{xyname2}}</text>
                  </view>
                  
                  <block v-if="loginset_data.btntype==1">
                    <button class="btn1" :style="'background:rgba('+t('color1rgb')+');color: '+loginset_data.btnwordcolor" form-type="submit">注册</button>
                  </block>
                  <block v-if="loginset_data.btntype==2">
                    <button class="btn1" :style="'background-color:'+loginset_data.btncolor+';color:'+loginset_data.btnwordcolor" form-type="submit">注册</button>
                  </block>

                </view>
                </form>
                
                <view class="tologin" @tap="goto" data-url="login" data-opentype="redirect" :style="'color: '+loginset_data.regpwdbtncolor">已有账号? 前去登录</view>
                
                <block v-if="logintype_2 || logintype_3 || logintype_8 || logintype_9">
                  <view style="display: flex;width: 420rpx;margin: 60rpx auto;">
                    <view class="other_line"></view>
                    <view style="margin: 0 20rpx;color: #888888;">其他登录方式</view>
                    <view  class="other_line"></view>
                  </view>
                  <view class="othertype">
                    <view class="othertype-item" v-if="logintype_3" @tap="weixinlogin">
                      <image class="img" :src="pre_url+'/static/img/login-'+platformimg+'.png'"/>
                      <text class="txt" style="color: #888888;">授权登录</text>
                    </view>
                    <!-- #ifdef H5 -->
                    <view class="othertype-item" v-if="logintype_7 && alih5" @tap="alih5login">
                      <image class="img" :src="pre_url+'/static/img/login-alipay.png'"/>
                      <text class="txt" style="color: #888888;">支付宝登录</text>
                    </view>
                    <!-- #endif -->
										<!-- #ifdef MP-WEIXIN -->
										<!-- 授权手机号登录 -->
										<button v-if="logintype_8 && platform == 'wx'" open-type="getPhoneNumber" @getphonenumber="wxtellogin" style="width:50%;margin: 0 auto;" hover-class="none">
										  <view style="width:100rpx;height:104rpx;margin: 0 auto;"><img :src="pre_url+'/static/img/login_tel_auth.png'" style="width:88rpx;height:88rpx;"></view>
										  <view style="font-size: 24rpx;line-height: 40rpx;color: #888888;">授权手机号登录</view>
										</button>
										<!-- 授权手机号登录 end -->
										<!-- #endif -->
										
										<!-- #ifdef MP-ALIPAY -->
										<!-- 授权手机号登录 -->
										<button v-if="logintype_9 && platform == 'alipay'" open-type="getPhoneNumber" @getphonenumber="alitellogin" style="width:50%;margin: 0 auto;" hover-class="none">
										  <view style="width:100rpx;height:104rpx;margin: 0 auto;"><img :src="pre_url+'/static/img/login_tel_auth.png'" style="width:88rpx;height:88rpx;"></view>
										  <view style="font-size: 24rpx;color: #888888;line-height: 40rpx;">授权手机号登录</view>
										</button>
										<!-- 授权手机号登录 end -->
										<!-- #endif -->
                    <view class="othertype-item" v-if="logintype_2" @tap="goto" data-url="login?logintype=2" data-opentype="redirect">
                      <image class="img" :src="pre_url+'/static/img/reg-tellogin.png'"/>
                      <text class="txt" style="color: #888888;">手机号登录</text>
                    </view>
                  </view>
                </block>
              </block>

              <!-- 绑定手机号 -->
              <block v-if="logintype==4">
                  <!-- #ifdef MP-WEIXIN -->
                  <view class="authlogin">
                    <view class="logo2">
                        <img :src="loginset_data.logo" style="width: 100%;height: 100%;">
                    </view>
                    <view style="font-size: 30rpx;font-weight: bold;line-height: 68rpx;"> 授权登录{{name}}</view>
                    <button class="authlogin-btn" open-type="getPhoneNumber" @getphonenumber="getPhoneNumber" :style="{background:'linear-gradient(90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'}">授权绑定手机号</button>
                    <button class="authlogin-btn2" @tap="nobindregister" v-if="login_bind==1">暂不绑定</button>
                  </view>
                  <!-- #endif -->
                  <!-- #ifdef MP-BAIDU -->
                  <view class="authlogin">
                    <view class="logo2">
                        <img :src="loginset_data.logo" style="width: 100%;height: 100%;">
                    </view>
                    <view style="font-size: 30rpx;font-weight: bold;line-height: 68rpx;"> 授权登录{{name}}</view>
                    <button class="authlogin-btn" open-type="getPhoneNumber" @getphonenumber="getBaiduPhoneNumber" :style="{background:'linear-gradient(90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'}">授权绑定手机号</button>
                    <button class="authlogin-btn2" @tap="nobindregister" v-if="login_bind==1">暂不绑定</button>
                  </view>
                  <!-- #endif -->
                  <!-- #ifndef MP-WEIXIN || MP-BAIDU -->
                  <form @submit="bindregister">
                    <view style="font-size: 30rpx;font-weight: bold;line-height: 68rpx;">绑定手机号</view>
                    <view class="regform">
                      <view class="form-item">
                        <image :src="pre_url+'/static/img/reg-tel.png'" class="img"/>
                        <input type="text" class="input" placeholder="请输入手机号" placeholder-style="font-size:30rpx;color:#B2B5BE" name="tel" value="" @input="telinput"/>
                      </view>
                      <view class="form-item">
                        <image :src="pre_url+'/static/img/reg-code.png'" class="img"/>
                        <input type="text" class="input" placeholder="请输入验证码" placeholder-style="font-size:30rpx;color:#B2B5BE" name="smscode" value=""/>
                        <view class="code" :style="'color:'+loginset_data.codecolor" @tap="smscode">{{smsdjs||'获取验证码'}}</view>
                      </view>
                      <button class="form-btn" form-type="submit" :style="{background:'linear-gradient(90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'}">确定</button>
                      <button class="form-btn2" @tap="nobindregister" v-if="login_bind==1">暂不绑定</button>
                    </view>
                  </form>
                  <!-- #endif -->
              </block>
              
              <!-- 设置头像昵称 -->
              <block v-if="logintype==5">
                <form @submit="setnicknameregister" @reset="formReset">
                  <view style="font-size: 30rpx;font-weight: bold;line-height: 68rpx;">请设置头像昵称</view>
                  <view class="regform">
                    <!--  #ifdef MP-WEIXIN -->
                    <view class="form-item" style="height:120rpx;line-height:120rpx">
                      <view class="flex1">头像</view>
                      <button open-type="chooseAvatar" @chooseavatar="onChooseAvatar" style="width:100rpx;height:100rpx;">
                        <image :src="headimg || default_headimg" style="width:100%;height:100%;border-radius:50%"></image>
                      </button> 
                    </view>
                    <view class="form-item" style="height:120rpx;line-height:120rpx">
                      <view class="flex1">昵称</view>
                      <input type="nickname" class="input" placeholder="请输入昵称" name="nickname" placeholder-style="font-size:30rpx;color:#B2B5BE" style="text-align:right"/>
                    </view>
                    <!-- #endif -->
                    <!--  #ifndef MP-WEIXIN -->
                    <view class="form-item" style="height:120rpx;line-height:120rpx">
                      <view class="flex1">头像</view>
                      <image :src="headimg || default_headimg" style="width:100rpx;height:100rpx;border-radius:50%" @tap="uploadHeadimg"></image>
                    </view>
                    <view class="form-item">
                      <view class="flex1">昵称</view>
                      <input type="text" class="input" placeholder="请输入昵称" name="nickname" value="" placeholder-style="font-size:30rpx;color:#B2B5BE" style="text-align:right"/>
                    </view>
                    <!-- #endif -->
                    <button class="form-btn" form-type="submit" :style="{background:'linear-gradient(90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'}">确定</button>
                    <button class="form-btn2" @tap="nosetnicknameregister" v-if="login_setnickname==1">暂不设置</button>
                  </view>
                </form>
              </block>
							
							<!-- 填写邀请码s -->
							<block v-if="logintype==6">
							  <form @submit="setRegisterInvite">
							    <view v-if="reg_invite_code && ((parent && reg_invite_code_show == 1) || !parent)" style="font-size: 30rpx;font-weight: bold;line-height: 68rpx;">请填写邀请码</view>
							    <view class="loginform" style="padding: 0;">
										<view v-if="reg_invite_code && !parent" class="form-item">
										   <input type="text" name="yqcode" @input="yqinput" :value="yqcode" :placeholder="'请输入邀请人'+reg_invite_code_text" placeholder-style="font-size:30rpx;color:#B2B5BE" class="input"/>
										</view>
										<view v-if="reg_invite_code && parent && reg_invite_code_show == 1" class="form-item" style="display: flex;padding-top: 8rpx;align-items: center;">
										  <view style="white-space: nowrap;">邀请人：</view>
										  <image :src="parent.headimg" style="width: 80rpx; height: 80rpx;border-radius: 50%;margin-right: 20rpx;"></image> 
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
							
            </view>
          </view>
        </view>
      </view>
    </view>
    <!-- e -->
		<view v-if="showxieyi" class="xieyibox">
			<view class="xieyibox-content">
				<view style="overflow:scroll;height:100%;">
					<parse :content="xycontent" @navigate="navigate"></parse>
				</view>
				<view class="xieyibut-view flex-y-center">
					<view class="but-class" style="background:#A9A9A9"  @tap="closeXieyi">不同意并退出</view>
					<view class="but-class" :style="{background:'linear-gradient(90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'}"  @tap="hidexieyi">已阅读并同意</view>
				</view>
			</view>
		</view>

		<view v-if="showxieyi2" class="xieyibox">
			<view class="xieyibox-content">
				<view style="overflow:scroll;height:100%;">
					<parse :content="xycontent2" @navigate="navigate"></parse>
				</view>
				<view class="xieyibut-view flex-y-center">
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
			logintype:0,
			logintype_1:true,
			logintype_2:false,
			logintype_3:false,
      logintype_7:false,//支付宝内h5
			logintype_8:false,//微信小程序授权手机号登录
			logintype_9:false,//支付宝小程序授权手机号登录
			logo:'',
			name:'',
			xystatus:0,
			xyname:'',
			xycontent:'',
			xyname2:'',
			xycontent2:'',
			needsms:false,
			showxieyi:false,
			showxieyi2:false,
			isagree:false,
      smsdjs: '',
			tel:'',
      hqing: 0,
			frompage:'/pages/my/usercenter',
			wxloginclick:false,
			login_bind:0,
			login_setnickname:0,
      reg_invite_code:0,//邀请码 1开启 0关闭 2强制邀请
      reg_invite_code_text:'',//邀请码文字描述
			reg_invite_code_type:0,//类型 1邀请码 0手机号
			reg_invite_code_show:1,//有邀请人时登录、注册页面是否显示邀请码和邀请人
			yqcode:'',
      parent:{},
			//自定义表单Start
			has_custom:0,
			show_custom_field:false,
			regiondata:'',
			editorFormdata:{},
			test:'',
			formfields:[],
			custom_formdata:[],
			items: [],
			formvaldata:{},
			submitDisabled:false,
			//自定义表单End
			tmplids:[],
			default_headimg:app.globalData.pre_url + '/static/img/touxiang.png',
			headimg:'',
			nickname:'',
      loginset_type:0,
      loginset_data:'',
      
      alih5:false,
      ali_appid:'',
      alih5loginclick:false,
      tel_placeholder:'请输入手机号',
      showicon:0,//注册自定义是否显示图标
			set:{},
			wxtelloginclick:false,
			alitelloginclick:false,
			wxtellogininfo:''
    };
  },

  onLoad: function (opt) {
		this.opt = app.getopts(opt);
    // #ifdef H5
    if (navigator.userAgent.indexOf('AlipayClient') > -1) {
      this.alih5 = true;
    }
    // #endif
		if(this.opt.frompage) this.frompage = decodeURIComponent(this.opt.frompage);
    if(app.globalData.qrcode){
      var frompage = '/pagesA/qrcode/index?code='+app.globalData.qrcode;
      this.frompage = decodeURIComponent(frompage);
    }
		if(this.opt.logintype) this.logintype = this.opt.logintype;
		if(this.opt.login_bind) this.login_bind = this.opt.login_bind;
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
		// 不同意协议
		closeXieyi(){
			this.showxieyi = false;
			this.showxieyi2 = false;
			this.isagree = false;
		},
		getdata: function () {
			var that = this;
			that.loading = true;
			app.get('ApiIndex/reg', {pid:app.globalData.pid,regbid:app.globalData.regbid,regsource:app.globalData.regsource,wxregyqcode:app.globalData.wxregyqcode,mdid:app.globalData.mdid}, function (res) {
				that.loading = false;
				if(res.status == 0){
					app.alert(res.msg);return;
				}
        if(res.loginset_type){
          that.loginset_type = res.loginset_type
        }
        if(res.loginset_data){
          that.loginset_data = res.loginset_data
        }
				that.logintype_2 = res.logintype_2;
				that.logintype_3 = res.logintype_3;
				that.logintype_3 = res.logintype_3;
				// #ifdef APP-PLUS
				if(that.platform2 == 'ios'){
					if (plus.runtime.isApplicationExist({ pname: 'com.tencent.mm', action: 'weixin://' })) {
						
					}else{
						that.logintype_3 = false;
					}
				}
				// #endif
        that.logintype_7 = res.logintype_7 || false;
				that.logintype_8 = res.logintype_8 || false;
				that.logintype_9 = res.logintype_9 || false;

				that.xystatus = res.xystatus;
				that.xyname = res.xyname;
				that.xycontent = res.xycontent;
				that.xyname2 = res.xyname2;
				that.xycontent2 = res.xycontent2;
				that.logo = res.logo;
				that.name = res.name;
				that.needsms = res.needsms;
				that.platform = res.platform;
        that.reg_invite_code = res.reg_invite_code;
        that.reg_invite_code_text = res.reg_invite_code_text;
        that.reg_invite_code_type = res.reg_invite_code_type;
				that.reg_invite_code_show = res.reg_invite_code_show;
        that.parent = res.parent;
				if(that.platform == 'mp' || that.platform == 'wx' || that.platform == 'app'){
					that.platformname = '微信';
					that.platformimg = 'weixin';
				}
				if(that.platform == 'toutiao'){
					that.platformname = '快捷';
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
				if(res.tel_placeholder){
					that.tel_placeholder = res.tel_placeholder
				}
				
				//自定义表单
				if(res.has_custom){
					that.formfields = res.custom_form_field;
					that.has_custom = res.has_custom
					that.show_custom_field = true
					uni.request({
						url: app.globalData.pre_url+'/static/area.json',
						data: {},
						method: 'GET',
						header: { 'content-type': 'application/json' },
						success: function(res2) {
							that.items = res2.data
						}
					});
				}
				if(res.showicon){
					that.showicon = res.showicon;
				}
				
        if(res.ali_appid){
          that.ali_appid = res.ali_appid
        }
				that.loaded();
        // #ifdef H5
        if(that.logintype_7 && that.alih5){
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
				app.alert('请输入正确的手机号');
				return;
			}
	  
      if (formdata.pwd == '') {
        app.alert('请输入密码');
        return;
      }
      if (formdata.pwd.length < 6) {
        app.alert('新密码不小于6位');
        return;
      }
      if (formdata.repwd == '') {
        app.alert('请再次输入新密码');
        return;
      }
      if (formdata.pwd != formdata.repwd) {
        app.alert('两次密码不一致');
        return;
      }
			if(that.needsms){
				if (formdata.smscode == '') {
					app.alert('请输入短信验证码');
					return;
				}
			}else{
				formdata.smscode = '';
			}
			var postdata = {tel:formdata.tel,pwd:formdata.pwd,smscode:formdata.smscode,pid:app.globalData.pid,yqcode:formdata.yqcode,regbid:app.globalData.regbid,regsource:app.globalData.regsource,mdid:app.globalData.mdid}
			//如果有自定义表单则验证表单内容
			if(that.show_custom_field){
				var customformdata = {};
				var customData = that.checkCustomFormFields();
				if(!customData){
					return;
				}
				postdata['customformdata'] = customData
				postdata['customformid'] = that.formfields.id
			}
			
      if (that.xystatus == 1 && !that.isagree) {
        app.error('请先阅读并同意用户注册协议');
        return false;
      }
			app.showLoading('提交中');
      app.post("ApiIndex/regsub", postdata, function (data) {
				app.showLoading(false);
        if (data.status == 1) {
          app.success(data.msg);
					if(data.tmplids){
						that.tmplids = data.tmplids;
					}
					that.subscribeMessage(function () {
						setTimeout(function () {
							if(that.opt.fromapp==1 && data.toappurl){
								app.goto(data.toappurl,'redirect');
							}else{
								app.goto(that.frompage,'redirect');
							}
						}, 1000);
					});
        } else {
          app.error(data.msg);
        }
      });
    },
		getPhoneNumber: function (e) {
			var that = this
			if(e.detail.errMsg == "getPhoneNumber:fail user deny"){
				app.error('请同意授权获取手机号');return;
			}
			wx.login({success (res1){
				console.log(res1);
				var code = res1.code;
				//用户允许授权
				app.post('ApiIndex/wxRegister',{ headimg:that.headimg,nickname:that.nickname,iv: e.detail.iv,encryptedData:e.detail.encryptedData,code:code,pid:app.globalData.pid,regbid:app.globalData.regbid,regsource:app.globalData.regsource,mdid:app.globalData.mdid},function(res2){
					if (res2.status == 1) {
						app.success(res2.msg);
						setTimeout(function () {
							app.goto(that.frompage,'redirect');
						}, 1000);
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
			}else{
				this.register(this.headimg,this.nickname,'','');
			}
		},
		nosetnicknameregister:function(){
			this.nickname = '';
			this.headimg = '';
			if(this.login_bind!=0){
				this.logintype = 4;
			}else{
				this.register('','','','');
			}
		},
		setRegisterInvite:function(e){
			console.log(e);
			//return;
			this.yqcode = e.detail.value.yqcode;
			if(this.yqcode == '' && !app.globalData.pid){
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
			} else if(that.platform=='mp' || that.platform=='h5') {
				url = 'ApiIndex/shouquanRegister';
			} else {
				url = 'ApiIndex/'+that.platform+'Register';
			}
			app.post(url,{headimg:headimg,nickname:nickname,tel:tel,smscode:smscode,pid:app.globalData.pid,yqcode:that.yqcode,regbid:app.globalData.regbid,regsource:app.globalData.regsource,mdid:app.globalData.mdid},function(res2){
				if (res2.status == 1) {
					app.success(res2.msg);
					setTimeout(function () {
						app.goto(that.frompage,'redirect');
					}, 1000);
				} else {
					app.error(res2.msg);
				}
				return;
			});
		},
		weixinlogin:function(){
			var that = this;
			if (that.xystatus == 1 && !that.isagree) {
				that.showxieyi = true;
				that.wxloginclick = true;
				return;
			}
			that.wxloginclick = false;
			app.showLoading('授权中');
			app.authlogin(function(res){
				app.showLoading(false);
				if (res.status == 1) {
					app.success(res.msg);
					setTimeout(function () {
						app.goto(that.frompage,'redirect');
					}, 1000);
				} else if (res.status == 4) {
					//填写邀请码
					that.logintype = 6;
					that.login_setnickname = res.login_setnickname;
					that.login_bind = res.login_bind;//1可选设置
				} else if (res.status == 3) {
					that.logintype = 5;
					that.login_setnickname = res.login_setnickname
					that.login_bind = res.login_bind
				} else if (res.status == 2) {
					that.logintype = 4;
					that.login_bind = res.login_bind
				} else {
					app.error(res.msg);
				}
			},{frompage:that.frompage,yqcode:that.yqcode});
		},
		yqinput: function (e) {
		  this.yqcode = e.detail.value
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
    },
    hidexieyi: function () {
      this.showxieyi = false;
			this.isagree = true;
			if(this.wxloginclick){
				this.weixinlogin();
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
      if(this.alih5loginclick){
        that.alih5login();
      }
    },
    telinput: function (e) {
      this.tel = e.detail.value
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
							console.log(res)
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
				fail: function(res) { //alert(res.errMsg);
				}
			});
		},
		onChooseAvatar:function(e){
			console.log(e)
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
      app.post("ApiIndex/sendsms", {tel: tel}, function (data) {
        if (data.status != 1) {
          app.alert(data.msg);
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
		//自定义表单
		onchange(e) {
		  const value = e.detail.value
			this.regiondata = value[0].text + ',' + value[1].text + ',' + value[2].text;
		},
		setfield:function(e){
			var field = e.currentTarget.dataset.formidx;
			var value = e.detail.value;
			this.formvaldata[field] = value;
		},
		editorBindPickerChange:function(e){
			var that = this;
			var idx = e.currentTarget.dataset.idx;
			var val = e.detail.value;
			var editorFormdata = this.editorFormdata;
			
			if(!editorFormdata) editorFormdata = {};
			editorFormdata[idx] = val;
			that.editorFormdata = editorFormdata
			this.test = Math.random();
			var field = e.currentTarget.dataset.formidx;
			this.formvaldata[field] = val;
		},
		checkCustomFormFields:function(e){
			var that = this;
			var subdata = this.formvaldata;
			var formcontent = that.formfields.content;
			var formid = that.formfields.id;
			var formdata = {};
			for (var i = 0; i < formcontent.length;i++){
				// console.log(subdata['form' + i]);
				var value = subdata['form' + i];
				if (formcontent[i].key == 'region') {
						value = that.regiondata;					 
				}
				
				if ((formcontent[i].key == 'region' && value === '') && formcontent[i].val3 == 1 && (subdata['form' + i] === '' || subdata['form' + i] === null || subdata['form' + i] === undefined || subdata['form' + i].length==0)){
					 
					app.alert(formcontent[i].val1+' 必填');return false;
				}
				if (formcontent[i].key =='switch'){
						if (subdata['form' + i]==false){
								value = '否'
						}else{
								value = '是'
						}
				}
				if (formcontent[i].key == 'selector') {
						value = formcontent[i].val2[subdata['form' + i]]
				}
				if (formcontent[i].key == 'usercard' && subdata['form' + i]!='') {
					if(!app.isIdCard(subdata['form' + i])){
						app.alert(formcontent[i].val1+' 格式错误');return false;
					}
				}
				if (formcontent[i].key == 'input' && formcontent[i].val4 && subdata['form' + i]!==''){
					if(formcontent[i].val4 == '2'){ //手机号
						if (!app.isPhone(subdata['form' + i])) {
							app.alert(formcontent[i].val1+' 格式错误');return false;
						}
					}
					if(formcontent[i].val4 == '3'){ //身份证号
						if (!app.isIdCard(subdata['form' + i])) {
							app.alert(formcontent[i].val1+' 格式错误');return false;
						}
					}
					if(formcontent[i].val4 == '4'){ //邮箱
						if (!/^(.+)@(.+)$/.test(subdata['form' + i])) {
							app.alert(formcontent[i].val1+' 格式错误');return false;
						}
					}
				}
				formdata['form' + i] = value;
			}
			return formdata;
		},
		editorChooseImage: function (e) {
			var that = this;
			var idx = e.currentTarget.dataset.idx;
			var tplindex = e.currentTarget.dataset.tplindex;
			var editorFormdata = this.editorFormdata;
			if(!editorFormdata) editorFormdata = [];
			app.chooseImage(function(data){
				editorFormdata[idx] = data[0];
				console.log(editorFormdata)
				that.editorFormdata = editorFormdata
				that.test = Math.random();
		
				var field = e.currentTarget.dataset.formidx;
				that.formvaldata[field] = data[0];
		
			})
		},
		editorChooseFile: function (e) {
			var that = this;
			var idx = e.currentTarget.dataset.idx;
			var tplindex = e.currentTarget.dataset.tplindex;
			var editorFormdata = this.editorFormdata;
			if(!editorFormdata) editorFormdata = [];
			app.chooseFile(function(data){
				editorFormdata[idx] = data;
				console.log(editorFormdata)
				that.editorFormdata = editorFormdata
				that.test = Math.random();
		
				var field = e.currentTarget.dataset.formidx;
				that.formvaldata[field] = data;
		
			})
		},
		removeimg:function(e){
			var that = this;
			var idx = e.currentTarget.dataset.idx;
			var tplindex = e.currentTarget.dataset.tplindex;
			var field = e.currentTarget.dataset.formidx;
			var editorFormdata = this.editorFormdata;
			if(!editorFormdata) editorFormdata = [];
			editorFormdata[idx] = '';
			that.editorFormdata = editorFormdata
			that.test = Math.random();
			that.formvaldata[field] = '';
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
                  platform:"h5",
                  regbid:app.globalData.regbid,
                  regsource:app.globalData.regsource,
                  mdid:app.globalData.mdid
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
			console.log(e);
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
			
			app.showLoading('授权中');
			wx.login({success (res1){
				console.log(res1);
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
			// console.log(e);
			console.log(e);
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
				// console.log(res);
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
								return;
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
.title{margin:70rpx 50rpx 50rpx 40rpx;height:60rpx;line-height:60rpx;font-size: 48rpx;font-weight: bold;color: #000000;}
.regform{ width:100%;border-radius:5px;}
.regform .form-item{display:flex;align-items:center;width:100%;border-bottom: 1px #ededed solid;height:88rpx;line-height:88rpx;border-bottom:1px solid #F0F3F6;margin-top:20rpx;background: #fff;border-radius: 8rpx;padding: 0 20rpx;}
.regform .form-item:last-child{border:0}
.regform .form-item .img{width:44rpx;height:44rpx;margin-right:30rpx}
.regform .form-item .input{flex:1;color: #000;}
.regform .form-item .code{font-size:30rpx}
.regform .xieyi-item{display:flex;align-items:center;margin-top:50rpx}
.regform .xieyi-item{font-size:24rpx;color:#B2B5BE}
.regform .xieyi-item .checkbox{transform: scale(0.6);}
.regform .form-btn{margin-top:20rpx;width:100%;height:96rpx;line-height:96rpx;color:#fff;font-size:30rpx;border-radius: 48rpx;}
.regform .form-btn2{width:100%;height:80rpx;line-height:80rpx;background:#EEEEEE;border-radius:40rpx;color:#A9A9A9;margin-top:30rpx;font-size: 28rpx;}
.tologin{color:#737785;font-size:26rpx;display:flex;width:100%;margin-top:30rpx}

.othertip{height:auto;overflow: hidden;display:flex;align-items:center;width:580rpx;padding:20rpx 20rpx;margin:0 auto;margin-top:60rpx;}
.othertip-line{height: auto; padding: 0; overflow: hidden;flex:1;height:0;border-top:1px solid #F2F2F2}
.othertip-text{padding:0 32rpx;text-align:center;display:flex;align-items:center;justify-content:center}
.othertip-text .txt{color:#A3A3A3;font-size:22rpx}

.othertype{width:70%;margin:20rpx 15%;display:flex;justify-content:center;}
.othertype-item{width:50%;display:flex;flex-direction:column;align-items:center;}
.othertype-item .img{width:88rpx;height:88rpx;margin-bottom:20rpx}
.othertype-item .txt{color:#A3A3A3;font-size:24rpx}

.xieyibox{width:100%;height:100%;position:fixed;top:0;left:0;z-index:99;background:rgba(0,0,0,0.7)}
.xieyibox-content{width:90%;margin:0 auto;height:80%;margin-top:20%;background:#fff;color:#333;padding:5px 10px 50px 10px;position:relative;border-radius:2px}
.xieyibox-content .xieyibut-view{height: 60rpx;position:absolute;z-index:9999;bottom:10px;left:0;right:0;margin:0 auto;justify-content: space-around;}
.xieyibox-content .xieyibut-view .but-class{text-align:center; width: auto;height: 60rpx; line-height: 60rpx; color: #fff; border-radius: 8rpx;padding:0rpx 25rpx;}

.authlogin{display:flex;flex-direction:column;align-items:center}
.authlogin-logo{width:180rpx;height:180rpx;margin-top:120rpx}
.authlogin-name{color:#999999;font-size:30rpx;margin-top:60rpx;}
.authlogin-btn{width:580rpx;height:96rpx;line-height:96rpx;background:#51B1F5;border-radius:48rpx;color:#fff;margin-top:100rpx}
.authlogin-btn2{width:580rpx;height:96rpx;line-height:96rpx;background:#EEEEEE;border-radius:48rpx;color:#A9A9A9;margin-top:20rpx}


/* 自定义字段显示 */
.dp-form-item{width: 100%;display:flex;align-items: center;border-bottom:1px solid #F0F3F6;padding: 10rpx 0;}
/* .dp-form-item:last-child{border:0} */
.dp-form-item .label{line-height: 50rpx;width:156rpx;margin-right: 10px;flex-shrink:0;text-align: right;color: #666666;font-size: 28rpx;}
.dp-form-item .input{height: 88rpx;line-height: 88rpx;overflow: hidden;flex:1;border-radius:2px;}
.dp-form-item .textarea{height:180rpx;line-height:40rpx;overflow: hidden;flex:1;border:1px solid #eee;border-radius:2px;padding:8rpx}
.dp-form-item .radio{height: 88rpx;line-height: 88rpx;display:flex;align-items:center}
.dp-form-item .radio2{display:flex;align-items:center;}
.dp-form-item .radio .myradio{margin-right:10rpx;display:inline-block;border:1px solid #aaa;background:#fff;height:32rpx;width:32rpx;border-radius:50%}
.dp-form-item .checkbox{height: 88rpx;line-height: 88rpx;display:flex;align-items:center}
.dp-form-item .checkbox2{display:flex;align-items:center;height: 40rpx;line-height: 40rpx;}
.dp-form-item .checkbox .mycheckbox{margin-right:10rpx;display:inline-block;border:1px solid #aaa;background:#fff;height:32rpx;width:32rpx;border-radius:2px}
.dp-form-item .layui-form-switch{}
.dp-form-item .picker{height: 88rpx;line-height:88rpx;flex:1;}

.dp-form-item2{width: 100%;border-bottom: 1px #ededed solid;padding:10rpx 0px;display:flex;flex-direction:column;align-items: flex-start;}
.dp-form-item2:last-child{border:0}
.dp-form-item2 .label{height:88rpx;line-height: 88rpx;width:100%;margin-right: 10px;}
.dp-form-item2 .input{height: 88rpx;line-height: 88rpx;overflow: hidden;width:100%;border:1px solid #eee;padding:0 8rpx;border-radius:2px;background:#fff}
.dp-form-item2 .textarea{height:180rpx;line-height:40rpx;overflow: hidden;width:100%;border:1px solid #eee;border-radius:2px;padding:8rpx}
.dp-form-item2 .radio{height: 88rpx;line-height: 88rpx;display:flex;align-items:center;}
.dp-form-item2 .radio2{display:flex;align-items:center;}
.dp-form-item2 .radio .myradio{margin-right:10rpx;display:inline-block;border:1px solid #aaa;background:#fff;height:32rpx;width:32rpx;border-radius:50%}
.dp-form-item2 .checkbox{height: 88rpx;line-height: 88rpx;display:flex;align-items:center;}
.dp-form-item2 .checkbox2{display:flex;align-items:center;height: 40rpx;line-height: 40rpx;}
.dp-form-item2 .checkbox .mycheckbox{margin-right:10rpx;display:inline-block;border:1px solid #aaa;background:#fff;height:32rpx;width:32rpx;border-radius:2px}
.dp-form-item2 .layui-form-switch{}
.dp-form-item2 .picker{height: 88rpx;line-height:88rpx;flex:1;width:100%;}
.dp-form-uploadbtn{position:relative;height:200rpx;width:200rpx}

.dp-form-imgbox{margin-right:16rpx;margin-bottom:10rpx;font-size:24rpx;position: relative;}
.dp-form-imgbox-close{position: absolute;display: block;width:32rpx;height:32rpx;right:-16rpx;top:-16rpx;color:#999;font-size:32rpx;background:#fff}
.dp-form-imgbox-close .image{width:100%;height:100%}
.dp-form-imgbox-img{display: block;width:200rpx;height:200rpx;padding:2px;border: #d3d3d3 1px solid;background-color: #f6f6f6;overflow:hidden}
.dp-form-imgbox-img>.image{max-width:100%;}
.dp-form-imgbox-repeat{position: absolute;display: block;width:32rpx;height:32rpx;line-height:28rpx;right: 2px;bottom:2px;color:#999;font-size:30rpx;background:#fff}
.dp-form-uploadbtn{position:relative;height:200rpx;width:200rpx}

.textarea{}
.bg_div1{width:100%;min-height: 100%;overflow: hidden;}
.content_div1{width: 700rpx; margin: 0 auto;margin-bottom: 60rpx;}
.title1{opacity: 1;font-size: 50rpx;font-weight: bold;line-height: 90rpx;text-align: left;margin-top: 80rpx;}
.subhead1{font-size: 28rpx;font-weight: 500;line-height: 40rpx;}
.card_div1{width: 100%;padding:40rpx;border-radius: 24rpx;margin-top: 40rpx;}
.tel1{width:100%;height:88rpx;border-radius: 88rpx;line-height: 88rpx;background-color: #F5F7FA;padding:0 40rpx;margin: 20rpx 0;margin-top: 30rpx;}
.code1{height: 88rpx;font-size: 24rpx;line-height: 88rpx;float: right;}
.btn1{width:100%;height:88rpx;border-radius: 88rpx;line-height: 88rpx;margin: 20rpx 0;text-align: center;font-weight: bold;}
.other_line{width: 106rpx;height: 2rpx;background: #D8D8D8;margin-top: 20rpx;}
.logo2{width: 200rpx;height: 200rpx;margin: 0 auto;margin-top:40rpx;margin-bottom: 40rpx;border-radius: 12rpx;overflow: hidden;}
.inputcode{width:300rpx;height:88rpx;line-height: 88rpx;display: inline-block;}
.input_val{width:100%;height:88rpx;line-height: 88rpx;}
.xycss1{line-height: 60rpx;font-size: 24rpx;overflow: hidden;margin-top: 20rpx;}
.other_login{width:420rpx;margin: 60rpx auto;}
.overflow_ellipsis{overflow: hidden;text-overflow: ellipsis;white-space: nowrap;width: 380rpx;}
.other_content{overflow: hidden;width: 100%;margin-top: 60rpx;text-align: center;display: flex;justify-content:center;}
</style>