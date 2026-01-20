<template>
<view class="container">
	<block v-if="isload">
	<form @submit="formSubmit">
		
		<view class="content2">
			<!-- <view class="item2"><view class="f1">接收人手机号</view></view>
			<view class="item3"><view class="f2"><input class="input" type="number" name="mobile" value="" placeholder="请输入接收人手机号" placeholder-style="color:#999;font-size:36rpx" @input="mobileinput"></input></view></view>
			<view class="item4" style="height: 1rpx;">
			</view> -->
			<view class="item2"><view class="f1">接收人ID</view></view>
			<view class="item3">
				<view class="member-info" v-if="member_info.id">
					<view class="info-view flex-y-center">
						<image class="head-img" :src="member_info.headimg" v-if='member_info.headimg'></image>
						<image class="head-img" :src="pre_url+'/static/img/wxtx.png'" v-else></image>
						<view class="member-text-view">
							<view class="member-nickname">{{member_info.nickname}}</view>
							<view class="member-id">ID：{{member_info.id}}</view>
						</view>
					</view>
					<view class="query-button" :style="{color:t('color1')}" @click="switchMember">切换</view>
				</view>
				<view class="member-info" v-else>
					<input class="input" type="number" name="mid" :value="mid" placeholder="请输入对方ID" placeholder-style="color:#999;font-size:36rpx" @input="memberInput" @blur="changeQuery(mid)"></input>
					<!-- <view class="query-button" :style="{color:t('color1')}" @click="changeQuery(mid)">查询</view<> -->
				</view>
<!-- 				<view class="f2" v-if="mid==0">
					<input class="input" type="number" name="mid" :value='mid' placeholder="请输入接收人ID" placeholder-style="color:#999;font-size:36rpx"></input>
				</view> -->
<!-- 				<view class="f2" v-if="mid>0">
					{{mid}}
				</view> -->
			</view>
			<view class="item4" style="height: 1rpx;">
			</view>
			<view class="item2"><view class="f1">转赠数量</view></view>
			<view class="item3"><view class="f2"><input class="input" type="digit" name="integral" value="" placeholder="请输入转赠数量" placeholder-style="color:#999;font-size:36rpx" @input="moneyinput"></input></view></view>
			<view class="item2" v-if="paycheck"><view class="f1">支付密码</view></view>
			<view class="item3" v-if="paycheck">
				<view class="f2">
					  <input class="input" type="password" name="paypwd" value="" placeholder="请输入支付密码" placeholder-style="color:#999;font-size:36rpx" @input="getpwd"></input>
				</view>
			</view>
			<view class="item5">
				<view :class="mid>0?'redtxt':''">您的当前{{t('佣金')}}：{{mycommission}}，转赠后不可退回</view>
				<view v-if="set.commission_transfer_sxf_ratio > 0" class="redtxt">
          <block v-if="set.commission_transfer_sxf_type && set.commission_transfer_sxf_type == 1">扣除对方</block>
          <block v-else-if="!set.commission_transfer_sxf_type || set.commission_transfer_sxf_type == 0">扣除我方</block>
          转赠手续费{{set.commission_transfer_sxf_ratio}}%
        </view>
			</view>
			<view class="item4"  v-if="transfer_sxf == 1 && commission_sxf > 0">
				<text style="margin-right:10rpx">支付手续费：{{commission_sxf}}元 </text>
			</view>
		</view>
    <view class="withdrawtype" v-if="set.commission_transfer_sxf_type && set.commission_transfer_sxf_type == 2">
        <view class="f1">手续费扣除方式：</view>
        <view class="f2">
            <view class="item"  @tap.stop="changeradio" data-feetype="0">
                <view class="t1">扣除我方</view>
                <view class="radio" :style="feetype=='0' ? 'background:'+t('color1')+';border:0' : ''"><image class="radio-img" :src="pre_url+'/static/img/checkd.png'"/></view>
            </view>
            <view class="item"  @tap.stop="changeradio" data-feetype="1">
                <view class="t1">扣除对方</view>
                <view class="radio" :style="feetype=='1' ? 'background:'+t('color1')+';border:0' : ''"><image class="radio-img" :src="pre_url+'/static/img/checkd.png'"/></view>
            </view>
        </view>
    </view>
		<button class="btn" :style="{background:t('color1')}" form-type="submit">转赠</button>
		<view class='text-center' @tap="goto" data-url='/pages/my/usercenter'><text>返回{{t('会员')}}中心</text></view>
		<!-- #ifdef MP-WEIXIN -->
		<view class='text-center' @tap="showPoster('wx')" v-if="set && set.commission_transfer_wxqrcode"><text>生成转赠小程序码</text></view>
		<!-- #endif -->
		<view class='text-center' @tap="showPoster('mp')" v-if="set && set.commission_transfer_qrcode"><text>生成转赠二维码</text></view>
	</form>
	
	<view class="posterDialog" v-if="showposter">
		<view class="main">
			<view class="close" @tap="posterDialogClose"><image class="img" :src="pre_url+'/static/img/close.png'"/></view>
			<view class="content">
				<image class="img" :src="posterpic" mode="widthFix" @tap="previewImage" :data-url="posterpic"></image>
			</view>
		</view>
	</view>
	</block>
	<loading v-if="loading"></loading>
	<dp-tabbar :opt="opt"></dp-tabbar>
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
			
      userinfo: [],
      mycommission: 0,
			paycheck:false,
			paypwd: '',
			mid:'',
			sharetypevisible: false,
			showposter: false,
			transfer_sxf:0, //0:关闭 1：开启
			sxf_ratio:0,
			commission_sxf:0,
			posterpic: "",
			set:{},
			tourl:'/pages/my/usercenter',
			member_info:{},
			pre_url:app.globalData.pre_url,
      feetype:0
    };
  },

  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		console.log(this.opt);
		this.mid = this.opt.mid ? this.opt.mid : '';
		if(this.opt.tourl) this.tourl = decodeURIComponent(this.opt.tourl);
		var that = this;
		this.getdata();
  },
	onPullDownRefresh: function () {
		this.getdata();
	},
  methods: {
		switchMember(){
			this.member_info = {};
			this.mid = '';
		},
		memberInput(event){
			this.mid = event.detail.value;
		},
		changeQuery(mid){
			let that = this;
			if(!mid) return app.error('请输入会员ID');
			that.loading = true
			app.get('ApiMy/getMemberBase',{mid:that.mid},function (res) {
				that.loading = false
				if(res.status == 1){
					that.member_info = res.data;
				}else{
					app.error('未查询到此会员！');
				}
			});
		},
		getdata: function () {
			var that = this;
			that.loading = true
			app.get('ApiMy/commissionTransfer', {mid:that.mid}, function (res) {
				that.loading = false
				if(res.status == 0) {
					app.alert(res.msg);return;
				}
				if(res.status == 1) {
					that.mycommission = res.mycommission;
					that.set = res.set;
					that.transfer_sxf = res.transfer_sxf;
					that.sxf_ratio = res.sxf_ratio;
				}
				uni.setNavigationBarTitle({
					title: that.t('佣金') + '转赠'
				});
				if(res.paycheck==1){
					that.paycheck = true
				}
				that.loaded();
			});
		},
		showPoster: function (platform) {
			var that = this;
			that.sharetypevisible = false;
			app.showLoading('生成中');
			if(platform == 'mp'){
				var url = 'ApiMy/commissionTransferQrcode';
			}else{
				var url = 'ApiMy/commissionTransferWxqrcode';
			}
			app.post(url, {}, function (data) {
				app.showLoading(false);
				if (data.status == 0) {
					app.alert(data.msg);
				} else {
					that.showposter = true;
					that.posterpic = data.poster;
				}
			});
		},
		posterDialogClose: function () {
			var that = this;
			that.showposter = false;
		},
		
		mobileinput: function (e) {
		  var value = parseFloat(e.detail.value);
		},
		
    moneyinput: function (e) {
      var money = parseFloat(e.detail.value);
      if(this.transfer_sxf == 1){
				this.commission_sxf = (money * this.sxf_ratio).toFixed(2);
			}
    },
    changeradio: function (e) {
      var that = this;
      var feetype = e.currentTarget.dataset.feetype;
      that.feetype = feetype;
    },
		getpwd: function (e) {
			var that = this;
			var paypwd = e.detail.value;
			that.paypwd = paypwd;
		},
    formSubmit: function (e) {
      var that = this;
      var commission = parseFloat(e.detail.value.integral);
			if(that.mid && that.mid>0){
				var mid = parseInt(that.mid)
			}else{
				var mid = parseInt(e.detail.value.mid);
			}
			var mobile = e.detail.value.mobile;
			var paypwd = e.detail.value.paypwd;
			if (typeof(mobile) != 'undefined' && !app.isPhone(mobile)) {
			  app.error("手机号码有误，请重填");
			  return false;
			}
			if (typeof(mid) != 'undefined' && (mid == '' || isNaN(mid))) {
				app.error("请输入接收人ID");
				return false;
			}
			if(typeof(mid) != 'undefined' && mid == app.globalData.mid) {
				app.error("不能转赠给自己");
				return false;
			}
			if (this.paycheck && paypwd=='') {
				app.error("请输入支付密码");
				return false;
			}
      if (isNaN(commission) || commission <= 0) {
        app.error('数量必须大于0');
        return;
      }
			
			if (commission < 0) {
        app.error('数量必须大于0');return;
      } else if (commission > that.mycommission) {
        app.error(this.t('佣金') + '不足');return;
      }

			app.confirm('确定要赠送吗？', function(){
				app.showLoading();
				app.post('ApiMy/commissionTransfer', {integral: commission,mobile: mobile,mid:mid,paypwd:paypwd,feetype:that.feetype}, function (data) {
					app.showLoading(false);
				  if (data.status == 1) {
				    
            app.success(data.msg);
            that.subscribeMessage(function () {
              setTimeout(function () {
                app.goto(that.tourl);
              }, 1000);
            });
				  }else if(data.status == 2){
						app.goto('/pagesExt/pay/pay?id=' + data.payorderid);
					} else {
				    if (data.msg) {
				    	app.alert(data.msg, function() {
				    		if (data.url){
                  app.goto(data.url);
                  return;
                } 
                if(data.set_paypwd==1){
                		setTimeout(function () {
                      uni.navigateTo({
                        url:'/pagesExt/my/paypwd'
                      })
                		}, 2000);
                    return;
                }
				    	});
				    } else if (data.url) {
				    	app.goto(data.url);
				    } else {
				    	app.alert('您无查看权限');
				    }
				  }
				}, '提交中');
			})
    },
  }
};
</script>
<style>
.container{display:flex;flex-direction:column}
.content2{width:94%;margin:10rpx 3%;border-radius:10rpx;display:flex;flex-direction:column;background:#fff}
.content2 .item1{display:flex;width:100%;border-bottom:1px solid #F0F0F0;padding:0 30rpx}
.content2 .item1 .f1{flex:1;font-size:32rpx;color:#333333;font-weight:bold;height:120rpx;line-height:120rpx}
.content2 .item1 .f2{color:#FC4343;font-size:44rpx;font-weight:bold;height:120rpx;line-height:120rpx}

.content2 .item2{display:flex;width:100%;padding:0 30rpx;padding-top:10rpx}
.content2 .item2 .f1{height:80rpx;line-height:80rpx;color:#999999;font-size:28rpx}

.content2 .item3{display:flex;width:100%;padding:0 30rpx;padding-bottom:20rpx}
.content2 .item3 .f1{height:100rpx;line-height:100rpx;font-size:60rpx;color:#333333;font-weight:bold;margin-right:20rpx}
.content2 .item3 .f2{display:flex;align-items:center;font-size:36rpx;color:#333333;font-weight:bold}
.content2 .redtxt{color: #FC4343;display: flex;align-items: center;margin-top: 10rpx;}
.content2 .item3 .f2 .input{font-size:36rpx;height:100rpx;line-height:100rpx;}
.content2 .item3 .member-info{display:flex;align-items:center;flex: 1;}
.content2 .item3 .member-info .input{font-size:36rpx;height:100rpx;line-height:100rpx;width: 100%;color:#333333;font-weight:bold;}
.content2 .item3 .member-info .query-button{white-space: nowrap;font-size: 28rpx;border-radius: 8rpx;padding: 5rpx 8rpx;}
.content2 .item3 .member-info .info-view{flex: 1;}
.content2 .item3 .member-info .info-view .head-img{width: 90rpx;height: 90rpx;border-radius: 50%;overflow: hidden;}
.content2 .item3 .member-info .info-view .member-text-view{height: 90rpx;padding-left: 20rpx;display: flex;flex-direction: column;align-items: flex-start;justify-content: flex-start;}
.content2 .item3 .member-info .info-view .member-text-view .member-nickname{font-size: 28rpx;color: #333;font-weight: bold;}
.content2 .item3 .member-info .info-view .member-text-view .member-id{font-size: 24rpx;color: #999999;margin-top: 10rpx;}
.content2 .item4{display:flex;width:94%;margin:0 3%;border-top:1px solid #F0F0F0;height:100rpx;line-height:100rpx;color:#8C8C8C;font-size:28rpx}
.content2 .item5{display: flex;flex-direction: column;width:94%;margin:0 3%;border-top:1px solid #F0F0F0;line-height:40rpx;padding:30rpx 0;color:#8C8C8C;font-size:28rpx}

.text-center {text-align: center; line-height: 80rpx;}

.btn{ height:100rpx;line-height: 100rpx;width:90%;margin:0 auto;border-radius:50rpx;margin:40rpx auto;color: #fff;font-size: 30rpx;font-weight:bold;}

.posterDialog .content .img {
    width: 300rpx;
    height: 300rpx;
}

.withdrawtype{width:94%;margin:20rpx 3%;border-radius:10rpx;display:flex;flex-direction:column;margin-top:20rpx;background:#fff}
.withdrawtype .f1{height:100rpx;line-height:100rpx;padding:0 30rpx;color:#333333;font-weight:bold}
.withdrawtype .f2{padding:0 30rpx}
.withdrawtype .f2 .item{border-bottom:1px solid #f5f5f5;height:100rpx;display:flex;align-items:center}
.withdrawtype .f2 .item:last-child{border-bottom:0}
.withdrawtype .f2 .item .t1{flex:1;display:flex;align-items:center;color:#333}
.withdrawtype .f2 .item .t1 .img{width:44rpx;height:44rpx;margin-right:40rpx}
.withdrawtype .f2 .item .radio{flex-shrink:0;width: 36rpx;height: 36rpx;background: #FFFFFF;border: 3rpx solid #BFBFBF;border-radius: 50%;margin-right:10rpx}
.withdrawtype .f2 .item .radio .radio-img{width:100%;height:100%}
</style>