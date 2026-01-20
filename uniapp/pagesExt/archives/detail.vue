<template>
<view>
	<block v-if="isload">
		<view class="container">
			<view class="header">
				<text class="title">{{detail.name}}</text>
				<view class="artinfo">
					<text class="t1">{{detail.createtime}}</text>
				</view>
				<view style="padding:8rpx 0">
					<dp :pagecontent="pagecontent"></dp>
				</view>
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
			pre_url:app.globalData.pre_url,

			detail:[],
			pagecontent: "",
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
			var id = that.opt.id;
			that.loading = true;
			app.get('ApiMemberArchives/detail', {id: id}, function (res) {
				that.loading = false;
				if (res.status == 1){
					that.detail = res.detail;
					that.pagecontent = res.pagecontent;
					uni.setNavigationBarTitle({
						title: res.detail.name
					});
				} else {
					app.alert(res.msg);
				}
				that.loaded();
			});
		},
  }
};
</script>
<style>
.header{ background-color: #fff;padding: 10rpx 20rpx 0 20rpx;position: relative;display:flex;flex-direction:column;}
.header .title{width:100%;font-size: 36rpx;color:#333;line-height: 1.4;margin:10rpx 0;margin-top:20rpx;font-weight:bold}
.header .artinfo{width:100%;font-size:28rpx;color: #8c8c8c;font-style: normal;overflow: hidden;display:flex;margin:10rpx 0;}
.header .artinfo .t1{padding-right:8rpx}
.header .artinfo .t2{color:#777;padding-right:8rpx}
.header .artinfo .t3{text-align:right;flex:1;}
.header .subname{width:100%;font-size:28rpx;color: #888;border:1px dotted #ddd;border-radius:10rpx;margin:10rpx 0;padding:10rpx}

</style>