<template>
<view class="container" :style="{backgroundColor:pageinfo.bgcolor}">
	<dp :menuindex="menuindex" :pagecontent="pagecontent" :isapplymendian="isapplymendian"></dp>
	
	<view class="qrcodebox" v-if="showthqrcode && thqrcode">
		<view class="qrcode"><image :src="thqrcode" @tap="previewImage" :data-url="thqrcode" ></view>
		<view class="qrcode-text">
			<view class="t1">向{{t('门店')}}出示二维码提货</view>
			<view class="t2" v-if="mendiantel" @tap.stop="callphone" :data-phone="mendiantel">{{t('门店')}}客服：{{mendiantel}}</view>
		</view>
	</view>
	
	<view v-if="showxieyi == 1" class="xieyibox">
		<view class="xieyibox-content">
			<view style="overflow:scroll;height:100%;">
				<parse :content="xycontent" @navigate="navigate"></parse>
			</view>
			<view style="position:absolute;z-index:9999;bottom:10px;left:0;right:0;margin:0 auto;text-align:center; width: 50%;height: 60rpx; line-height: 60rpx; color: #fff; border-radius: 8rpx;" :style="{background:'linear-gradient(90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'}"  @tap="hidexieyi">已阅读并同意</view>
		</view>
	</view>
  <uni-popup id="tencentqian" ref="tencentqian" type="dialog">
      <view class="uni-popup-dialog">
        <view class="uni-dialog-title">
          <text class="uni-dialog-title-text">{{xytitle}}</text>
        </view>
        <view class="uni-dialog-content">
          <view  style="overflow:scroll;max-height:100%;">
            <parse :content="xycontent" @navigate="navigate"></parse>
          </view>
        </view>
        <view class="uni-dialog-button-group">
          <view class="uni-dialog-button" @tap="tencentqianClose">
            <text class="uni-dialog-button-text">取消</text>
          </view>
          <view class="uni-dialog-button" :style="{color:t('color1')}" @tap="goto" data-url="/pagesD/tencent_qian/index?type=member_uplevel">
            <text class="uni-dialog-button-text">去签署</text>
          </view>
        </view>
      </view>
    </uni-popup>
	<!-- 红包区域 -->
	<uni-popup ref="popup" @change="" type="dialog">
		<view class="cl-popup">
			<view class="main">
				<image :src="`${pre_url}/static/img/popup-top.png`" mode="aspectFill" class="top" />
				<image :src="`${pre_url}/static/img/popup-icon.png`" mode="aspectFill" class="icon" />
				<image :src="`${pre_url}/static/img/popup-bottom.png`" mode="aspectFill" class="bottom" />
				<view class="content">
					<view class="price">
						<text class="num">{{hbmoney}}</text>
						<text class="unit">元</text>
					</view>
					<!-- 标题 -->
					<view class="title"> {{hbtext}} </view>
					<!-- 领取按钮 -->
					<view class="cl-button">
						<text @tap="hbsuccess">确定</text>
					</view>
				</view>
				<!-- 关闭弹窗按钮  -->
				<view class="hongbao-view-close" @click="hbclose">
					<image :src="pre_url+'/static/img/close2.png'"></image>
				</view>
			</view>
		</view>
	</uni-popup>

  <view v-if="copyright!=''" class="copyright" @tap="goto" :data-url="copyright_link">
    <block v-if="!copyrighttype">{{copyright}}</block>
    <block v-else-if="copyrighttype==1">
      <parse :content="copyright"></parse>
    </block>
  </view>

  <view v-if="advertising" style="width: 100%;background-color: #fff;margin: 20rpx 0;padding: 20rpx;">
    <block v-if="advertising.type == 1">
      <view class="ad-box">
      	<view class="ad-item" @tap="goto" :data-url="advertising.pictourl"><image :src="advertising.pic" mode="widthFix"></view>
      </view>
    </block>
    <block v-else>
      <dp-wxad :params="advertising"></dp-wxad>
    </block>
  </view>

	<dp-tabbar :opt="opt" @getmenuindex="getmenuindex"></dp-tabbar>
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
			pageinfo: [],
			pagecontent: [],
			copyright:'',
			copyright_link:'',
			xycontent:'',
			thqrcode:'',
			showthqrcode:'',
			isapplymendian:0,
			mendiantel:'',
			pre_url:app.globalData.pre_url,
			hbmoney:0,
			hbtext:'',
			hb_logid:0,
			menuindex: -1,
      
      advertising:'',//广告
      copyrighttype:0,//版权类型 0：默认类型 1：自定义类型
      
      showxieyi:0,//协议状态 0：不展示 1：升级协议模式 2：腾讯电子签模式
      xytitle:'',//协议标题
      xycontent:'',
		}
	},
	onShow:function() {
    if(app.globalData.platform=='wx' && app.globalData.hide_home_button==1){
      uni.hideHomeButton();
    }
	},
	onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.getdata(); 
	},
	onPullDownRefresh:function(e){
		this.getdata();
	},
	methods: {
		getdata:function(){
			var that = this;
			var id = 0;
			if (that.opt && that.opt.id) {
				id = that.opt.id;
			}
			that.loading = true;
			app.get('ApiMy/usercenter',{id: id},function (data){
				that.loading = false;
			  var pagecontent = data.pagecontent;
				that.pageinfo = data.pageinfo;
				that.pagecontent = data.pagecontent;
				that.copyright = data.copyright || '';
				that.copyright_link = data.copyright_link || '';
        that.copyrighttype = data.copyrighttype || 0;
				that.thqrcode = data.thqrcode || '';
				that.showthqrcode = data.showthqrcode || '';
				that.mendiantel = data.mendiantel || '';
				if(!app.isNull(data.pageinfo.title)){
					uni.setNavigationBarTitle({
						title: data.pageinfo.title
					});
				}
				if(data.isapplymendian){
					that.isapplymendian = data.isapplymendian;
				}
				var greenscore_hb = data.greenscore_hb;
				console.log(greenscore_hb);
				that.hbmoney = greenscore_hb.hbmoney;
				that.hbtext = greenscore_hb.hbtext;
				that.hb_logid = greenscore_hb.log_id
				if(greenscore_hb.show_hb){
					that.$refs.popup.open('center');
				}
        that.advertising = data.advertising;//广告
				that.loaded();
			});
			//获取升级协议
			if (app.globalData.mid > 0) {
        app.get('ApiMy/getUpAgree',{'needinit':0},function (res){
          if(res.data.uplv_agree){
            that.showxieyi = res.showxieyi || 0;
            if(that.showxieyi == 2){
              that.xytitle   = res.xytitle;//协议标题
              that.xycontent = res.xycontent;
              that.$refs.tencentqian.open();
            }else{
              that.xycontent = res.data.agree_content;
            }
          }
        });
      }
		},
    tencentqianClose:function(){
      var that = this;
      that.$refs.tencentqian.close();
    },
		hidexieyi: function () {
			var that = this
		  //同意升级
		  app.get('ApiMy/agreeUplv',{'needinit':0},function (data){
		  	console.log(data);
		  	if(data.status==1){
				that.showxieyi = false;
		  		that.getdata();
		  	}
		  });
		},
		// 领取红包
		hbsuccess() {
			var that = this;
			console.log('点击领取');
			that.loading = true;
			app.post('ApiGreenScore/quitHb', {log_id:that.hb_logid}, function (res) {
				that.loading = false;
				app.alert(res.msg);
				that.$refs.popup.close();
				return;
				
			});
		},
		hbclose(){
			var that = this;
			console.log('点击关闭');
			that.loading = true;
			app.post('ApiGreenScore/closeHb', {log_id:that.hb_logid}, function (res) {
				that.loading = false;
				that.$refs.popup.close();
			});
		},
		callphone:function(e) {
			var phone = e.currentTarget.dataset.phone;
			uni.makePhoneCall({
				phoneNumber: phone,
				fail: function () {
				}
			});
		},
	}
}
</script>
<style>
.container{width: 100%;min-height: 100vh;}
.xieyibox{width:100%;height:100%;position:fixed;top:0;left:0;z-index:99;background:rgba(0,0,0,0.7)}
.xieyibox-content{width:90%;margin:0 auto;height:80%;margin-top:10%;background:#fff;color:#333;padding:5px 10px 50px 10px;position:relative;border-radius:2px}

.qrcodebox{ background: #fff; border-radius: 20rpx; display: flex;align-items: center;justify-content: center;margin:30rpx;padding: 30rpx 30rpx;  }
.qrcodebox image{ width: 200rpx; height:200rpx;margin-right: 30rpx;}
.qrcodebox .qrcode-text .t1{ color: #2B9D4B;margin-bottom:20rpx}
/**红包相关**/

.cl-popup { position: relative; }
.cl-popup .main { position: relative; width: 580rpx; height: 770rpx; }
.cl-popup .hongbao-view-close { position: absolute; top: 10rpx; right: 10rpx; border: 2px #fff solid; width: 60rpx; height: 60rpx; border-radius: 50%; display: flex; align-items: center; justify-content: center;z-index: 9999; }
.cl-popup .hongbao-view-close image { width: 80%; height: 80%; }
.cl-popup .top { position: absolute; top: 0; width: 100%; height: 560rpx; }
.cl-popup .icon { position: absolute; top: 324rpx; left: calc(50% - 87rpx); width: 174rpx; height: 178rpx; z-index: 2; }
.cl-popup .bottom { position: absolute; bottom: 0; width: 100%; height: 434rpx; }
.cl-popup .content { display: flex; flex-direction: column; align-items: center; position: absolute; top: 0; left: 0; width: 100%; height: 100%; z-index: 5; }
.cl-popup .price { margin-top: 70rpx; margin-bottom: 300rpx; }
.cl-popup .num { font-size: 122rpx; font-weight: bold; color: #fc5c43; }
.cl-popup .unit { position: relative; left: 10rpx; bottom: 10rpx; font-size: 50rpx; font-weight: 500; color: #fc5c43; }
.cl-popup .title { margin-bottom: 40rpx; font-size: 28rpx; font-weight: 400; color: #ffe0be; }
.cl-popup .cl-button { width: 316rpx; height: 78rpx; background: linear-gradient(180deg, #fff7da 0%, #f3a160 100%); box-shadow: 0 3rpx 6rpx #d12200; border-radius: 50rpx; text-align: center; line-height: 78rpx;z-index:9999; }
.cl-popup .cl-button text { font-size: 32rpx; font-weight: bold; color: #f74d2e; }

/* 广告位 */
.ad-box{width: 100%;border-radius: 10rpx;}
.ad-item{width: 100%;display: flex;justify-content: center;margin-bottom: 20rpx;border-radius: 10rpx;}
.ad-item image{border-radius: 12rpx;width: 100%;}
.ad-item:last-child{margin-bottom: 0;}

.uni-popup-dialog {width: 300px;border-radius: 5px;background-color: #fff;}
.uni-dialog-title {display: flex;flex-direction: row;justify-content: center;padding-top: 15px;padding-bottom: 5px;}
.uni-dialog-title-text {font-size: 16px;font-weight: 500;}
.uni-dialog-content {display: flex;flex-direction: row;justify-content: center;align-items: center;padding: 5px 15px 15px 15px;}
.uni-dialog-content-text {font-size: 14px;color: #6e6e6e;}
.uni-dialog-button-group {display: flex;flex-direction: row;border-top-color: #f5f5f5;border-top-style: solid;border-top-width: 1px;}
.uni-dialog-button {display: flex;flex: 1;flex-direction: row;justify-content: center;align-items: center;height: 45px;}
.uni-border-left {border-left-color: #f0f0f0;border-left-style: solid;border-left-width: 1px;}
.uni-dialog-button-text {font-size: 14px;}
.uni-button-color {color: #007aff;}
</style>