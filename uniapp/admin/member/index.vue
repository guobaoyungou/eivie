<template>
<view>
	<block v-if="isload">
		<view class="topsearch flex-y-center">
			<view class="f1 flex-y-center">
				<image class="img" :src="pre_url+'/static/img/search_ico.png'"></image>
				<input :value="keyword" placeholder="输入昵称/姓名/手机号搜索" placeholder-style="font-size:24rpx;color:#C2C2C2" @confirm="searchConfirm" @input="searchChange"></input>
			</view>
		</view>
		<view class="content" v-if="datalist && datalist.length>0">
			<view class="label">
				<text class="t1">{{t('会员')}}列表（共{{count}}人）</text>
				<view class="btn" @tap="goto" data-url="/admin/order/addmember?type=1"  v-if="is_add_member">添加会员</view>
				<view class="btn" v-if="!couponShow" @click="pushAll">全部推送</view>
			</view>
			<block v-for="(item, index) in datalist" :key="index">
				<view class="item">
					<view class="f1" @tap="goto" :data-url="'detail?mid=' + item.id">
						<image :src="item.headimg"></image>
						<view class="t2">
							<view v-if="dkopen && item.realname">
								{{item.realname}}
							</view>
							<view class="x1 flex-y-center">
								{{item.nickname}}
								<image style="margin-left:10rpx;width:40rpx;height:40rpx" :src="pre_url+'/static/img/nan2.png'" v-if="item.sex==1"></image>
								<image style="margin-left:10rpx;width:40rpx;height:40rpx" :src="pre_url+'/static/img/nv2.png'" v-if="item.sex==2"></image>
							</view>
							<block v-if="!dkopen">
								<text class="x2">最后访问：{{item.last_visittime}}</text>
								<text class="x2">加入时间：{{item.createtime}}</text>
								<text class="x2">{{item.province ? '' : item.province}}{{item.city ? '' : item.city}}</text>
								<text class="x2" v-if="item.remark" style="color:#a66;font-size:22rpx">{{item.remark}}</text>
							</block>
							<block v-if='item.tel && dkopen'>
								<text class="x2">手机号：{{item.tel}}</text>
							</block>
						</view>
					</view>
					<block v-if='couponShow'>
						<view class="f2" v-if="!dkopen && mendian_usercenter==0">
							<view class="btn" @tap="goto" :data-url="'detail?mid=' + item.id">详情</view>
							<view class="btn" @tap="goto" :data-url="'/admin/member/history?id='+item.id">足迹</view>
							<view v-if="item.can_login" class="btn" @tap="loginUser(item.id,item.tel)">登录</view>
						</view>
						<view class="f2" v-else>
							<view class="btn" @click="SelectMembers(item)">选择</view>
						</view>
					</block>
					<block v-else>
						<view class="f2">
							<view class="btn" @click="pushCoupons(item)">推送</view>
						</view>
					</block>
				</view>
			</block>
		</view>
		<nomore v-if="nomore"></nomore>
		<nodata v-if="nodata"></nodata>
		<view class="tabbar" v-if="!dkopen">
			<view class="tabbar-bot"></view>
			<view class="tabbar-bar" style="background-color:#ffffff;">
				<view @tap="goto" data-url="../member/index" data-opentype="reLaunch" class="tabbar-item" v-if="auth_data.member">
					<view class="tabbar-image-box">
						<image class="tabbar-icon" :src="pre_url+'/static/img/admin/member2.png?v=1'"></image>
					</view>
					<view class="tabbar-text active">{{t('会员')}}</view>
				</view>
				<view @tap="goto" data-url="../kefu/index" data-opentype="reLaunch" class="tabbar-item" v-if="auth_data.zixun">
					<view class="tabbar-image-box">
						<image class="tabbar-icon" :src="pre_url+'/static/img/admin/zixun.png?v=1'"></image>
					</view>
					<view class="tabbar-text">咨询</view>
				</view>
				<view @tap="goto" data-url="../finance/index" data-opentype="reLaunch" class="tabbar-item" v-if="auth_data.finance">
					<view class="tabbar-image-box">
						<image class="tabbar-icon" :src="pre_url+'/static/img/admin/finance.png?v=1'"></image>
					</view>
					<view class="tabbar-text">财务</view>
				</view>
				<view @tap="goto" data-url="../index/index" data-opentype="reLaunch" class="tabbar-item">
					<view class="tabbar-image-box">
						<image class="tabbar-icon" :src="pre_url+'/static/img/admin/my.png?v=1'"></image>
					</view>
					<view class="tabbar-text">我的</view>
				</view>
			</view>
		</view>
		<!-- 推送优惠券 -->
		<uni-popup id="popup" ref="popup" type="center">
			<view class="uni-popup-dialog">
				<view class="uni-dialog-title">
					<text class="uni-dialog-title-text" :class="['uni-popup__'+dialogType]">推送{{t('优惠券')}}</text>
				</view>
				<view class="uni-dialog-content">
					<view class="uni-dialog-content-options">
						<view class="uni-dialog-content-text">{{t('优惠券')}}名称：</view>
						<view style="word-break: break-all;">{{couponRes.name}}</view>
					</view>
					<view class="uni-dialog-content-options">
						<view class="uni-dialog-content-text">每人发送数量：</view>
						<input class="uni-dialog-input" v-model="sendingQuantity" type="text" :placeholder="placeholder">
					</view>
					<view class="uni-dialog-content-options">
						<view class="uni-dialog-content-text">共计发送人数：</view>
						<view>{{numberSenders}}</view>
					</view>
				</view>
				<view class="uni-dialog-button-group">
					<view class="uni-dialog-button" @click="closePopop">
						<text class="uni-dialog-button-text">取消</text>
					</view>
					<view class="uni-dialog-button uni-border-left" @click="SendCoupons">
						<text class="uni-dialog-button-text uni-button-color">发送</text>
					</view>
				</view>
			</view>
		</uni-popup>
	</block>
	<popmsg ref="popmsg"></popmsg>
	<loading v-if="loading"></loading>
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
			pre_url:app.globalData.pre_url,
      datalist: [],
      pagenum: 1,
      nomore: false,
			nodata:false,
      count: 0,
      keyword: '',
      auth_data: {},
			dkopen:false,
			member_tel:'',
			member_pwd:'',
			is_add_member:1,
			couponShow:true,
			couponRes:{},
			sendingQuantity:1,
			numberSenders:1,
			sendtype:0,
			giftGiver:[],
			numPush:0,
			mendian_usercenter:0,//是否门店中心
      update_oid:'',//修改的订单ID
    };
  },
  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.getdata();
		if(opt.type) this.dkopen = true;
		// 判断是餐饮优惠券||优惠券 ,推送功能
		if(opt.coupon || opt.restaurantCoupon){
			this.couponShow = false;
			this.couponRes = {name:opt.name,id:opt.id};
		}
    this.update_oid = this.opt.update_oid || '';
  },
	onPullDownRefresh: function () {
		this.getdata();
	},
  onReachBottom: function () {
    if (!this.nodata && !this.nomore) {
      this.pagenum = this.pagenum + 1;
      this.getdata(true);
    }
  },
  methods: {
		// 优惠券全部推送
		async pushAllChange(){
			let that = this;
			uni.showLoading({title:'正在推送...'});
			let pagenumA = that.pagenum++;
			app.post('ApiAdminMember/index', {keyword: '',pagenum: pagenumA}, function (res) {
				if(res.datalist.length){
					let IdArr = [];
					IdArr = res.datalist.map(item => item.id);
					let params = {
						sendtype:0,
						cpid:that.couponRes.id,
						persendnum:that.sendingQuantity,
						ids:IdArr
					}
					app.post('ApiAdminCoupon/send',params,function(res){
						if(res.status){
							that.pushAllChange();
							that.numPush = that.numPush + res.sucnum;
							uni.showLoading({title:`已推送${that.numPush}人`});
						}else{
							app.error(res.msg);
							setTimeout(() => {
								uni.navigateBack({
									delta:1
								})
							},300)
						}
					})	
				}else{
					that.$refs.popup.close();
					uni.hideLoading();
					app.success('推送完成');
					setTimeout(() => {
						uni.navigateBack({
							delta:1
						})
					},300)
				}
			})
		},
		pushAll(){
			this.sendtype = 1;
			this.numberSenders = this.count;
			this.giftGiver = [];
			this.$refs.popup.open();
		},
		SendCoupons(){
			let that = this;
			if(that.sendtype){
				// 全部推送
				that.pushAllChange();
			}else{
				that.loading = true;
				let params = {
					sendtype:that.sendtype,
					cpid:that.couponRes.id,
					persendnum:that.sendingQuantity,
					ids:that.giftGiver
				}
				app.post('ApiAdminCoupon/send',params,function(res){
					that.loading = false;
					if(res.status){
						app.success(res.msg);
						that.$refs.popup.close();
						setTimeout(() => {
							uni.navigateBack({
								delta:1
							})
						},300)
					}else{
						app.error(res.msg);
						setTimeout(() => {
							uni.navigateBack({
								delta:1
							})
						},300)
					}
				})
			}
		},
		closePopop(){
			this.$refs.popup.close();
		},
		// 推送优惠券
		pushCoupons(item){
			this.giftGiver = [];
			this.giftGiver.push(item.id);
			this.sendtype = 0;
			this.numberSenders = 1;
			this.sendingQuantity = 1;
			this.$refs.popup.open();
		},
		SelectMembers(item){
      var that = this;
      if(that.update_oid){
        that.updateMember(item.id);
      }else {
        app.goto('/admin/order/dkorder?mid='+item.id)
      }
		},
    //更换下单人
    updateMember:function(val){
      var that = this;
      var updateoid = that.update_oid;
      app.confirm('订单用户信息确定更换成此用户吗?',function(){
        app.showLoading('更换中...');
        app.post('ApiAdminOrder/updateMember', {id:updateoid,updatemid:val}, function (res) {
          app.showLoading(false);
          if(res.status==1){
            app.alert('更换成功', function () {
              //uni.navigateBack();
              app.goto('/admin/order/shoporder')
            });
            return;
          }else{
            app.alert(res.msg, function () {
              uni.navigateBack();
            });
            return;
          }
        })
      });
    },
    getdata: function (loadmore) {
			if(!loadmore){
				this.pagenum = 1;
				this.datalist = [];
			}
      var that = this;
			var pagenum = that.pagenum;
      var keyword = that.keyword;
			that.nodata = false;
			that.nomore = false;
			that.loading = true;
      app.post('ApiAdminMember/index', {keyword: keyword,pagenum: pagenum}, function (res) {
        that.loading = false;
				that.is_add_member = res.is_add_member ? res.is_add_member:0;
				that.mendian_usercenter = res.mendian_usercenter;
        var data = res.datalist;
        if (pagenum == 1) {
					that.datalist = data;
					that.count = res.count;
					that.auth_data = res.auth_data;
          if (data.length == 0) {
            that.nodata = true;
          }
					uni.setNavigationBarTitle({
						title: that.t('会员') + '列表'
					});
					that.loaded();
        }else{
          if (data.length == 0) {
            that.nomore = true;
          } else {
            var datalist = that.datalist;
            var newdata = datalist.concat(data);
            that.datalist = newdata;
          }
        }
      });
    },
    searchChange: function (e) {
      this.keyword = e.detail.value;
    },
    searchConfirm: function (e) {
      var that = this;
      var keyword = e.detail.value;
      that.keyword = keyword;
      that.getdata();
    },
	loginUser:function (id,tel){
		var str = id+tel
		app.post("ApiAdminMember/adminLoginUser", {mid:id,str:str}, function (res) {
			app.showLoading(false);
		    if (res.status == 1) {
		    app.success(res.msg);
				
					setTimeout(function () {
						app.goto('/pages/my/usercenter','redirect');
					}, 1000);
				
		  } else {
		    app.error(res.msg);
		  }
		});
	}
  }
};
</script>
<style>
@import "../common.css";
.topsearch{width:94%;margin:16rpx 3%;}
.topsearch .f1{height:60rpx;border-radius:30rpx;border:0;background-color:#fff;flex:1}
.topsearch .f1 .img{width:24rpx;height:24rpx;margin-left:10px}
.topsearch .f1 input{height:100%;flex:1;padding:0 20rpx;font-size:28rpx;color:#333;}

.content{width: 94%;margin:0 3%;background: #fff;border-radius:16rpx}
.content .label{display:flex;width: 100%;padding:24rpx 16rpx;color: #333;}
.content .label .t1{flex:1}
.content .label .t2{ width:300rpx;text-align:right}
.content .label .btn{ border-radius:8rpx; padding:3rpx 12rpx;margin-left: 10px;border: 1px #999 solid; text-align:center; font-size:28rpx;color:#333;}

.content .item{width: 100%;padding: 32rpx;border-top: 1px #e5e5e5 solid;min-height: 112rpx;display:flex;align-items:center;}
.content .item image{width:90rpx;height:90rpx;}
.content .item .f1{display:flex;flex:1}
.content .item .f1 .t2{display:flex;flex-direction:column;padding-left:20rpx}
.content .item .f1 .t2 .x1{color: #222;font-size:30rpx;}
.content .item .f1 .t2 .x2{color: #999;font-size:24rpx}

.content .item .f2{display:flex;flex-direction:column;width:auto;text-align:right;border-left:1px solid #e5e5e5}
.content .item .f2 .t1{ font-size: 40rpx;color: #666;height: 40rpx;line-height: 40rpx;}
.content .item .f2 .t2{ font-size: 28rpx;color: #999;height: 50rpx;line-height: 50rpx;}
.content .item .btn{ border-radius:8rpx; padding:3rpx 12rpx;margin-left: 10px;border: 1px #999 solid; text-align:center; font-size:28rpx;color:#333;}
.content .item .btn:nth-child(n+2) {margin-top: 10rpx;}
.popup__options{display: flex;align-items: center;justify-content: flex-start;padding-bottom: 15rpx;}
.popup__options .popup__options_text{width: 120rpx;text-align: right;}
.popup__but{font-size: 14px;color: #007aff;display: table;margin: 30rpx auto 30rpx;}

.uni-popup-dialog {width: 300px;border-radius: 5px;background-color: #fff;}
.uni-dialog-title {/* #ifndef APP-NVUE */display: flex;	/* #endif */flex-direction: row;justify-content: center;padding-top: 15px;padding-bottom: 5px;}
.uni-dialog-title-text {font-size: 16px;font-weight: 500;}
.uni-dialog-content {display: flex;flex-direction: column;justify-content: center;padding: 5px 15px 15px 15px;}
.uni-dialog-content-options{display: flex;align-items: center;justify-content: flex-start;padding: 10rpx 0rpx;}
.uni-dialog-content-text {font-size: 14px;color: #6e6e6e;text-align: right;width: 228rpx;white-space: nowrap;}
.uni-dialog-button-group {/* #ifndef APP-NVUE */display: flex;/* #endif */flex-direction: row;border-top-color: #f5f5f5;border-top-style: solid;border-top-width: 1px;}
.uni-dialog-button {/* #ifndef APP-NVUE */display: flex;/* #endif */flex: 1;flex-direction: row;justify-content: center;align-items: center;height: 45px;	/* #ifdef H5 */	cursor: pointer;/* #endif */}
.uni-border-left {border-left-color: #f0f0f0;border-left-style: solid;border-left-width: 1px;}
.uni-dialog-button-text {font-size: 14px;}
.uni-button-color {color: #007aff;}
.uni-dialog-input {	flex: 1;font-size: 14px;border: 1px #d1d1d1 solid;border-radius:5rpx;margin-right: 20rpx;padding-left: 10rpx;}
</style>