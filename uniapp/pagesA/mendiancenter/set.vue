<template>
<view class="container">
	<block v-if="isload">
		<view class="content">
			<view class="info-item" style="height:136rpx;line-height:136rpx">
				<view class="t1" style="flex:1;">头像</view>
				<image :src="mendian.pic" style="width:88rpx;height:88rpx;" @tap="uploadHeadimg"/>
			</view>
			<view class="info-item" @tap="goto" data-url="setnickname">
				<view class="t1">关联会员</view>
				<view class="t2">{{member.nickname}}</view>
			</view>
		</view>
		<view class="content">
			<view class="info-item" @tap="goto" data-url="setrealname">
				<view class="t1">{{t('门店')}}名称</view>
				<view class="t2">{{mendian.name}}</view>
			</view>
			<view class="info-item" @tap="goto" data-url="settel">
				<view class="t1">手机号</view>
				<view class="t2">{{mendian.tel}}</view>
			</view>
			<view class="info-item" @tap="goto" data-url="setsex">
				<text class="t1">社区名称</text>
				<view class="t2">{{mendian.xqname}}</view>
			</view>

		</view>
		
      
	</block>
	<loading v-if="loading"></loading>
	<dp-tabbar :opt="opt"></dp-tabbar>
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
			menuindex:-1,
			mendian:{},
			member:{}
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
			app.get('ApiMendianCenter/set', {}, function (data) {
				that.loading = false;
				that.mendian = data.mendian;
				that.member = data.member
				that.loaded();
			});
		},

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


</style>