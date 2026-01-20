<template>
	<view class="body">
		<block v-if="isload">
			<!-- 选择主体 -->
			<view class="content">
				<view class="subject-item">
					<view class="header-subject">
						<view class="h1">选择商家主体</view>
						<view class="h2">根据你的证件，选择对应的主体</view>
					</view>
					<view class="item-box" @tap="selectSubject" :data-subject="1">
						<view class="t1">个体户</view>
						<view class="t2">需提供
							<text>法人身份证</text>
							<text>营业执照</text>
							<text>个人/对公银行卡</text>
						</view>
						<view class="grey">
							<view class="f1 square-dot">1-2个工作日内完成审批</view>
							<view class="f2 square-dot">收款不限额，仅支持绑定个人/对公账户</view>
						</view>
					</view>

					<view class="item-box" @tap="selectSubject" :data-subject="2">
						<view class="t1">企业</view>
						<view class="t2">需提供
							<text>法人身份证</text>
							<text>营业执照</text>
							<text>个人/对公银行卡</text>
						</view>
						<view class="grey">
							<view class="t3 square-dot">1-2个工作日内完成审批</view>
							<view class="t4 square-dot">收款不限额，仅支持绑定对公账户</view>
						</view>
					</view>
				</view>
			</view>
			<!-- END选择主体 -->
		</block>
		<loading v-if="loading"></loading>
		<popmsg ref="popmsg"></popmsg>
	</view>
</template>
<script>
	var app = getApp();

	export default {
		data() {
			return {
				opt: {},
				loading: false,
				isload: false,
				pre_url: app.globalData.pre_url,
				subject: 0 //主体
			}
		},
		onLoad: function(opt) {
			var that = this;
			var opt = app.getopts(opt);
			that.opt = opt;
			that.id = that.opt.id || 0
			that.getdata();
		},
		onPullDownRefresh: function() {
			this.getdata();
		},
		methods: {
			// getdata: function() {
			// 	var that = this;
			// 	var id = that.opt.id;
			// 	that.loading = true;
			// 	app.get('ApiAdminBusinessJinjian/applyInfo', { type:'wx' }, function(res) {
			// 		that.loading = false;
			// 		if (res.status == 1) {
			// 			that.loaded();
			// 		} else {
			// 			app.alert(res.msg);
			// 		}
			// 	});
			// },

			//选择主体
			selectSubject: function(e) {
				let subject = e.currentTarget.dataset.subject;
				return app.goto('wxjinjian?subject=' + subject,'redirect');
			}
		}
	}
</script>
<style>
	page{position:relative;width:100%;height:100%}
	.flex-sb{display:flex;justify-content:space-between;align-items:center}
	.header{height:200rpx;position:absolute;top:0;width:100%;padding-top:70rpx;text-align:center;font-weight:bold;font-size:32rpx}
	.container{position:absolute;width:100%;top:100rpx;border-radius:16rpx;padding-bottom:100rpx}
	.content{width:94%;margin:30rpx 3%}
	.subject-item .header-subject{padding:20rpx;text-align:center;line-height:70rpx}
	.square-dot{position:relative;display:inline-block}
	.square-dot::before{content:"";display:inline-block;width:8rpx;height:8rpx;background-color:#535353;margin-right:5px;vertical-align:middle}
	.subject-item .header-subject .h1{font-size:40rpx;font-weight:bold}
	.subject-item .header-subject .h2{font-size:28rpx;color:#999}
	.subject-item .item-box{background-color:#fff;margin:30rpx 3%;padding:40rpx 3%;border-radius:8px}
	.subject-item .item-box view{line-height:50rpx}
	.subject-item .item-box .t1{color:#000;font-size:32rpx;font-weight:bold}
	.subject-item .item-box .t2{color:#666;font-size:26rpx;padding: 15rpx 0;}
	.subject-item .item-box .t2 text{background-color:#E6FFE5;color:#11A923;margin-left:10rpx}
	
	.grey{color:#666}
</style>