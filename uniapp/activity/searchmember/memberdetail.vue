<template>
<view>
	<block v-if="isload">
		<view class="orderinfo">
			<view class="item">
				<text class="t1">头像</text>
				<view class="t2"><image :src="member.headimg" style="width:80rpx;height:80rpx"></image></view>
			</view>
			<view class="item">
				<text class="t1">昵称</text>
				<text class="t2">{{member.nickname}}</text>
			</view>
			<view class="item">
				<text class="t1">地区</text>
				<text class="t2">{{member.province ? member.province : '' }}{{member.city ? member.city : '' }}</text>
			</view>
			<view class="item">
				<text class="t1">加入时间</text>
				<text class="t2">{{member.createtime}}</text>
			</view>
			<view class="item">
				<text class="t1">姓名</text>
				<text class="t2">{{member.realname ? member.realname : '' }}</text>
			</view>
			<view class="item">
				<text class="t1">电话</text>
				<text class="t2">{{member.tel ? member.tel : '' }}</text>
			</view>
			<view class="item">
				<text class="t1">{{t('余额')}}</text>
				<text class="t2">{{member.money}}</text>
			</view>
			<view class="item">
				<text class="t1">{{t('积分')}}</text>
				<text class="t2">{{member.score}}</text>
			</view>
			<view class="item">
				<text class="t1">等级</text>
				<text class="t2">{{member.levelname}}</text>
			</view>
			<view class="item" v-if="member.remark">
				<text class="t1">备注</text>
				<text class="t2">{{member.remark}}</text>
			</view>
		</view>
	</block>
	<popmsg ref="popmsg"></popmsg>
</view>
</template>

<script>
var app = getApp();

export default {
  data() {
    return {
      isload: false,
      member: "",
			index2:0,
			levelList:[],
			levelList2:[],
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
		getdata:function(){
			var that = this;
			that.loading = true;
			app.post('ApiSearchMember/memberdetail', {mid: that.opt.mid}, function (res) {
				that.loading = false;
				that.member = res.member;
				uni.setNavigationBarTitle({
					title: that.t('会员') + '信息'
				});

				var levelList2 = [];
				for(var i in res.levelList){
					levelList2.push(res.levelList[i].name);
				}
				that.levelList = res.levelList;
				that.levelList2 = levelList2;

				that.loaded();
			});
		},
  }
};
</script>
<style>

.address{ display:flex;align-items:center;width: 100%; padding: 20rpx 3%; background: #FFF;margin-bottom:20rpx;}
.address .img{width:60rpx}
.address image{width: 50rpx; height: 50rpx;}
.address .info{flex:1;display:flex;flex-direction:column;}
.address .info .t1{ font-weight:bold}

.product{width:100%; padding: 14rpx 3%;background: #FFF;}
.product .content{display:flex;position:relative;width: 100%; padding:16rpx 0px;border-bottom: 1px #e5e5e5 dashed;position:relative}
.product .content:last-child{ border-bottom: 0; }
.product .content image{ width: 140rpx; height: 140rpx;}
.product .content .detail{display:flex;flex-direction:column;margin-left:14rpx;flex:1}
.product .content .detail .t1{height: 60rpx;line-height: 30rpx;color: #000;}
.product .content .detail .t2{height: 46rpx;line-height: 46rpx;color: #999;overflow: hidden;font-size: 26rpx;}
.product .content .detail .t3{display:flex;height: 30rpx;line-height: 30rpx;color: #ff4246;}
.product .content .detail .x1{ flex:1}
.product .content .detail .x2{ width:100rpx;font-size:32rpx;text-align:right;margin-right:8rpx}
.product .content .comment{position:absolute;top:64rpx;right:10rpx;border: 1px #ffc702 solid; border-radius:10rpx;background:#fff; color: #ffc702;  padding: 0 10rpx; height: 46rpx; line-height: 46rpx;}

.orderinfo{ width:94%;margin:20rpx 3%;border-radius:16rpx;padding: 14rpx 3%;background: #FFF;}
.orderinfo .item{display:flex;width:100%;padding:20rpx 0;border-bottom:1px dashed #ededed;}
.orderinfo .item:last-child{ border-bottom: 0;}
.orderinfo .item .t1{width:200rpx;}
.orderinfo .item .t2{flex:1;text-align:right}
.orderinfo .item .red{color:red}

.bottom{ width: 100%; padding: 16rpx 20rpx;background: #fff; position: fixed; bottom: 0px; left: 0px;display:flex;justify-content:flex-end;align-items:center;}
.bottom .btn{ border-radius:10rpx; padding:10rpx 16rpx;margin-left: 10px;border: 1px #999 solid;}


</style>