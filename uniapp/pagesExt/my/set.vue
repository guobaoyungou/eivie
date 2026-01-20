<template>
<view class="container">
	<block v-if="isload">
		<view class="content">
			<view class="info-item" style="height:136rpx;line-height:136rpx">
				<view class="t1" style="flex:1;">头像</view>
				<image :src="userinfo.headimg" style="width:88rpx;height:88rpx;" @tap="uploadHeadimg"/>
				<image class="t3" :src="pre_url+'/static/img/arrowright.png'"/>
			</view>
			<view class="info-item" @tap="goto" data-url="setnickname">
				<view class="t1">昵称</view>
				<view class="t2">{{userinfo.nickname}}</view>
				<image class="t3" :src="pre_url+'/static/img/arrowright.png'"/>
			</view>
      <view class="info-item" v-if="userinfo.haslabel" style="height: auto;">
      	<view class="t1">标签</view>
        <view class="t2" style="display: block;line-height: 44rpx;">
          {{userinfo.labelnames}}
        </view>
      </view>
		</view>
		<view class="content">
			<view class="info-item" @tap="goto" data-url="setrealname">
				<view class="t1">姓名</view>
				<view class="t2">{{userinfo.realname}}</view>
				<image class="t3" :src="pre_url+'/static/img/arrowright.png'"/>
			</view>
			<view class="info-item" @tap="goto" data-url="setrealname">
				<view class="t1">身份证</view>
				<view class="t2">{{userinfo.usercard}}</view>
				<image class="t3" :src="pre_url+'/static/img/arrowright.png'"/>
			</view>
			<view class="info-item" @tap="goto" data-url="settel">
				<view class="t1">手机号</view>
				<view class="t2">{{userinfo.tel}}</view>
				<image class="t3" :src="pre_url+'/static/img/arrowright.png'"/>
			</view>
			<view class="info-item" @tap="goto" data-url="setsex">
				<text class="t1">性别</text>
				<text class="t2" v-if="userinfo.sex==1">男</text>
				<text class="t2" v-else-if="userinfo.sex==2">女</text>
				<text class="t2" v-else>未知</text>
				<image class="t3" :src="pre_url+'/static/img/arrowright.png'"/>
			</view>
			<view class="info-item" @tap="goto" data-url="setbirthday">
				<text class="t1">生日</text>
				<text class="t2">{{userinfo.birthday}}</text>
				<image class="t3" :src="pre_url+'/static/img/arrowright.png'"/>
			</view>
		</view>
		<view class="content" v-if="userinfo.set_receive_notice">
			<view class="info-item" v-if="getplatform() == 'mp'">
				<view class="t1" style="width: 290rpx;">消费通知</view>
				<view class="t2"><switch value="1" :checked="is_receive_finance_tmpl" @change="switchchange" data-type="tmpl"></switch></view>
			</view>

			<view class="info-item">
				<view class="t1" style="width: 290rpx;">消费通知（手机短信）</view>
				<view class="t2"><switch value="1" :checked="is_receive_finance_sms" @change="switchchange" data-type="sms"></switch></view>
			</view>
		</view>
		<view class="content" v-if="userinfo.set_alipay || userinfo.set_bank">
			<!-- <view class="info-item" @tap="goto" data-url="setweixin">
				<view class="t1">微信号</view>
				<view class="t2">{{userinfo.weixin}}</view>
				<image class="t3" :src="pre_url+'/static/img/arrowright.png'"/>
			</view> -->
			<view class="info-item" v-if="userinfo.set_alipay" @tap="goto" data-url="setaliaccount">
				<view class="t1">支付宝账号</view>
				<view class="t2">{{userinfo.aliaccount}}</view>
				<image class="t3" :src="pre_url+'/static/img/arrowright.png'"/>
			</view>
			<view class="info-item" v-if="userinfo.set_bank" @tap="goto" data-url="setbankinfo">
				<text class="t1">银行卡</text>
				<text class="t2">{{userinfo.bankname ? '已设置' : ''}}</text>
				<image class="t3" :src="pre_url+'/static/img/arrowright.png'"/>
			</view>
		</view>
		<view v-if="register_forms" class="content">
        	<block v-for="(item,index) in register_forms">
                <block v-if="item.key!='upload_file'">
                    <view  class="info-item" @tap="goto" :data-url="'setother?from=regist&index='+index">
                        <view class="t1">{{item.val1}}</view>
                        <view v-if="item.key!='upload' && item.key!='upload_file'" class="t2">{{item.content}}</view>
                        <view v-if="item.key=='upload'" class="t2" style="height: 90rpx;">
                            <image v-if="item.content" :src="item.content" style="height:70rpx;margin-top: 10rpx;" mode="heightFix" @tap.stop="previewImage" :data-url="item.content"></image>
                        </view>
                        <image class="t3" :src="pre_url+'/static/img/arrowright.png'"/>
                    </view>
                </block>
                <block v-else>
                    <!-- #ifdef !H5 && !MP-WEIXIN -->
                        <view  class="info-item" >
                            <view class="t1">{{item.val1}}</view>
                            <view v class="t2"  style="overflow: hidden;white-space: pre-wrap;word-wrap: break-word;color: #4786BC;">
                            	{{item.content}}
                            </view>
                        </view>
                    <!-- #endif -->
                    <!-- #ifdef H5 || MP-WEIXIN -->
                        <view  class="info-item" @tap="goto" :data-url="'setother?from=regist&index='+index">
                            <view class="t1">{{item.val1}}</view>
                            <view class="t2"  style="overflow: hidden;white-space: pre-wrap;word-wrap: break-word;color: #4786BC;">
                            	<button type="button" style="float: right; width: 120upx;" @tap.stop="download" :data-file="item.content" >查看</button>
                            </view>
                            <image class="t3" :src="pre_url+'/static/img/arrowright.png'"/>
                        </view>
                    <!-- #endif -->
                </block>
        	</block>
        </view>
        
        <view v-if="otherdata" class="content">
        	<block v-for="(item,index) in otherdata">
                <block v-if="item.key!='upload_file'">
                    <view  class="info-item" v-if="member_edit_switch == 1" @tap="goto" :data-url="'setother?from=set&index='+index">
                        <view class="t1">{{item.val1}}</view>
                        <view v-if="item.key!='upload' && item.key!='upload_file'" class="t2">{{item.content}}</view>
                        <view v-if="item.key=='upload'" class="t2" style="height: 90rpx;">
                            <image v-if="item.content" :src="item.content" style="height:70rpx;margin-top: 10rpx;" mode="heightFix" @tap.stop="previewImage" :data-url="item.content"></image>
                        </view>
                        <image class="t3" :src="pre_url+'/static/img/arrowright.png'"/>
                    </view>
					<view  class="info-item" v-else>
					    <view class="t1">{{item.val1}}</view>
					    <view v-if="item.key!='upload' && item.key!='upload_file'" class="t2">{{item.content}}</view>
					    <view v-if="item.key=='upload'" class="t2" style="height: 90rpx;">
					        <image v-if="item.content" :src="item.content" style="height:70rpx;margin-top: 10rpx;" mode="heightFix" @tap.stop="previewImage" :data-url="item.content"></image>
					    </view>
					</view>
                </block>
                <block v-else>
                    <!-- #ifdef !H5 && !MP-WEIXIN -->
                        <view  class="info-item" >
                            <view class="t1">{{item.val1}}</view>
                            <view v class="t2"  style="overflow: hidden;white-space: pre-wrap;word-wrap: break-word;color: #4786BC;">
                            	{{item.content}}
                            </view>
                        </view>
                    <!-- #endif -->
                    <!-- #ifdef H5 || MP-WEIXIN -->
                        <view  class="info-item" v-if="member_edit_switch == 1" @tap="goto" :data-url="'setother?from=set&index='+index">
                            <view class="t1">{{item.val1}}</view>
                            <view class="t2"  style="overflow: hidden;white-space: pre-wrap;word-wrap: break-word;color: #4786BC;">
                            	<button type="button" style="float: right; width: 120upx;" @tap.stop="download" :data-file="item.content" >查看</button>
                            </view>
                            <image class="t3" :src="pre_url+'/static/img/arrowright.png'"/>
                        </view>
						<view class="info-item" v-else>
						    <view class="t1">{{item.val1}}</view>
						    <view class="t2"  style="overflow: hidden;white-space: pre-wrap;word-wrap: break-word;color: #4786BC;">
						    	<button type="button" style="float: right; width: 120upx;" @tap.stop="download" :data-file="item.content" >查看</button>
						    </view>
						</view>
                    <!-- #endif -->
                </block>
        	</block>
        </view>

		<view class="content">
			<view class="info-item" @tap="goto" data-url="/pagesB/address/address">
				<view class="t1">收货地址</view>
				<view class="t2"></view>
				<image class="t3" :src="pre_url+'/static/img/arrowright.png'"/>
			</view>
		</view>
		<view class="content" v-if="userinfo.haspaypwd==1">
			<view class="info-item" @tap="goto" data-url="/pagesExt/my/paypwd">
				<view class="t1">修改支付密码</view>
				<view class="t2"></view>
				<image class="t3" :src="pre_url+'/static/img/arrowright.png'"/>
			</view>
		</view>
		<view class="content">
			<view class="info-item" @tap="goto" data-url="/pagesExt/my/setpwd">
				<view class="t1">修改密码</view>
				<view class="t2"></view>
				<image class="t3" :src="pre_url+'/static/img/arrowright.png'"/>
			</view>
		</view>
		<view class="content">
			<view class="info-item" @tap="switchAccounts">
				<view class="t1">切换账号</view>
				<view class="t2"></view>
				<image class="t3" :src="pre_url+'/static/img/arrowright.png'"/>
			</view>
		</view>
		<view class="content">
			<view class="info-item" @tap="logout">
				<view class="t1">退出登录</view>
				<view class="t2"></view>
				<image class="t3" :src="pre_url+'/static/img/arrowright.png'"/>
			</view>
		</view>
		<!-- #ifdef APP-PLUS -->
		<view class="content">
			<view class="info-item" @tap="delaccount">
				<view class="t1">注销账号</view>
				<view class="t2"></view>
				<image class="t3" :src="pre_url+'/static/img/arrowright.png'"/>
			</view>
		</view>
		<!-- #endif -->
	</block>
	<!--短信开始提示-->
	<view v-if="showTip" class="popalert" @tap.stop="hideTip2">
		<view class="moudle" >
			<view class="title">短信通知开启提示</view>
			<view class="minpricetip" >
				请询问商家是否支持短信通知，商家未储值短信费用则不会有任何通知
			</view>
			<view class="btn"  :style="{background:'linear-gradient(90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'}"  @tap="hideTip">我已知晓</view>
		</view>
	</view>
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
			userinfo:{},
            otherdata:'',
			register_forms:[],
			showTip:false,
			is_receive_finance_tmpl:false,
			is_receive_finance_sms:false,
			member_edit_switch:1, //会员编辑开关
			pre_url: app.globalData.pre_url,
    };
  },

  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.getdata();
  },
	onPullDownRefresh: function () {
		this.getdata();
	},
  methods: {
		getdata: function () {
			var that = this;
			that.loading = true;
			app.get('ApiMy/set', {}, function (data) {
				that.loading = false;
				that.userinfo = data.userinfo;
					if(data.otherdata){
						that.otherdata = data.otherdata;
					}
					if(data.register_forms){
						that.register_forms = data.register_forms;
					}
				if(that.userinfo.set_receive_notice){
					that.is_receive_finance_sms = that.userinfo.is_receive_finance_sms;
					that.is_receive_finance_tmpl = that.userinfo.is_receive_finance_tmpl;
				}
				if(data.member_edit_switch !== undefined){
					that.member_edit_switch = data.member_edit_switch
				}
				that.loaded();
			});
		},
		uploadHeadimg:function(){
			var that = this;
			app.chooseImage(function(urls){
				var headimg = urls[0];
				that.userinfo.headimg = headimg;
				app.post('ApiMy/setfield',{headimg:headimg});
			},1,'headimg')
		},
		delaccount:function(){
			app.confirm('注销账号后该账号下的所有数据都将删除并且无法恢复，确定要注销吗？',function(){
				app.showLoading('注销中');
				app.get('ApiMy/delaccount', {}, function (data) {
					app.showLoading(false);
					if(data.status == 1){
						app.alert(data.msg,function(){
							app.goto('/pages/index/index');
						});
					}else{
						app.alert(data.msg);
					}
				});
			})
		},
		logout:function(){
			var that = this;
			that.loading = true;
			app.get('ApiIndex/logout', {}, function (data) {
				app.showLoading(false);
				if(data.status == 0){
					app.alert(data.msg);
				}
			});
		},
        download:function(e){
            var that = this;
            var file = e.currentTarget.dataset.file;
			if(file == ''){
				return app.error('未上传文件');
			}
            // #ifdef H5
                window.location.href= file;
            // #endif
            
            // #ifdef MP-WEIXIN
            uni.downloadFile({
            	url: file, 
            	success: (res) => {
                    var filePath = res.tempFilePath;
            		if (res.statusCode === 200) {
            			uni.openDocument({
                          filePath: filePath,
                          showMenu: true,
                          success: function (res) {
                            console.log('打开文档成功');
                          }
                        });
            		}
            	}
            });
            // #endif
        },
		switchchange: function (e) {
		  var field = e.currentTarget.dataset.type;
		  var value = e.detail.value ? 1 : 0;
		  if(field=='sms'){
			  if(value ==1){
				  this.is_receive_finance_sms =false;
				  this.showTip = true;
				  return;
			  }
		  }
		  app.post('ApiMy/setFinanceNoticeSwitch', {
		    field: field,
		    value: value
		  }, function (data) {});
		},
		hideTip(){
			var that = this;
			var userinfo = this.userinfo;
			if(!userinfo.tel){
				app.error('请先绑定手机号');
				setTimeout(function(){
					app.goto('/pagesExt/my/settel')
				},2000)
			}else{
				app.post('ApiMy/setFinanceNoticeSwitch', {
				  field: 'sms',
				  value: 1
				}, function (data) {
					that.is_receive_finance_sms =true;
				});
			
				this.showTip = false;
			}
			
		},
		hideTip2(){
			this.showTip = false;
			this.is_receive_finance_sms = false;
		},
		switchAccounts:function(){
			//绑定商户 商户默认导航
			if(this.userinfo && this.userinfo.bind_business){
				let frompage = encodeURIComponent('/pages/my/usercenter?bid='+this.userinfo.bind_business);
				return app.goto('/pages/index/login?frompage='+frompage)
			}
			return app.goto('/pages/index/login');
		}
	}
};
</script>
<style>
.container{overflow: hidden;}
.content{width:94%;margin:20rpx 3%;background:#fff;border-radius:5px;padding:0 20rpx;}
.info-item{ display:flex;align-items:center;width: 100%; background: #fff;padding:0 3%;  border-bottom: 1px #f3f3f3 solid;height:96rpx;line-height:96rpx}
.info-item:last-child{border:none}
.info-item .t1{ width: 200rpx;color: #8B8B8B;font-weight:bold;height:96rpx;line-height:96rpx}
.info-item .t2{ color:#444444;text-align:right;flex:1;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:1;overflow:hidden;}
.info-item .t3{ width: 26rpx;height:26rpx;margin-left:20rpx}
switch{ transform: scale(0.7)}

.popalert{width:100%;height:100%;position:fixed;top:0;left:0;z-index:99;background:rgba(0,0,0,0.7);}
.popalert .moudle{width:80%;margin:0 auto;height:30%;margin-top:65%;background:#fff;color:#333;padding:5px 10px 50px 10px;position:relative;border-radius:2px;border-radius: 20rpx;}
.popalert .title{color: #000000;text-align: center;padding: 10rpx 0;font-size: 19px;font-weight: 700;}
.popalert .minpricetip{letter-spacing: 4rpx;line-height: 40rpx;padding: 20rpx 20rpx;}
.popalert .btn{position:absolute;z-index:9999;bottom:10px;left:0;right:0;margin:0 auto;text-align:center; width: 50%;height: 60rpx; line-height: 60rpx; color: #fff; border-radius: 8rpx;}
</style>