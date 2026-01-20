<template>
<view class="container">
	<block v-if="isload">
		<view class="content">
			<view class="info-item" style="height:136rpx;line-height:136rpx">
				<view class="t1" style="flex:1;">头像</view>
				<image :src="userinfo.headimg" style="width:88rpx;height:88rpx;" @tap="uploadHeadimg"/>
				<image class="t3" :src="pre_url+'/static/img/arrowright.png'"/>
			</view>
			<view class="info-item" @tap="goto" :data-url="'/pagesExt/my/setnickname?mid='+userinfo.id">
				<view class="t1">昵称</view>
				<view class="t2">{{userinfo.nickname}}</view>
				<image class="t3" :src="pre_url+'/static/img/arrowright.png'"/>
			</view>
		</view>
		<view class="content">
			<view class="info-item" @tap="goto" :data-url="'/pagesExt/my/setrealname?mid='+userinfo.id">
				<view class="t1">姓名</view>
				<view class="t2">{{userinfo.realname}}</view>
				<image class="t3" :src="pre_url+'/static/img/arrowright.png'"/>
			</view>
			<view class="info-item" @tap="goto" :data-url="'/pagesExt/my/setsex?mid='+userinfo.id">
				<text class="t1">性别</text>
				<text class="t2" v-if="userinfo.sex==1">男</text>
				<text class="t2" v-else-if="userinfo.sex==2">女</text>
				<text class="t2" v-else>未知</text>
				<image class="t3" :src="pre_url+'/static/img/arrowright.png'"/>
			</view>
			<view class="info-item" @tap="goto" :data-url="'/pagesExt/my/setbirthday?mid='+userinfo.id">
				<text class="t1">生日</text>
				<text class="t2">{{userinfo.birthday}}</text>
				<image class="t3" :src="pre_url+'/static/img/arrowright.png'"/>
			</view>
		</view>
		<view v-if="register_forms" class="content">
			<block v-for="(item,index) in register_forms">
				<block v-if="item.key!='upload_file'">
					<view  class="info-item" @tap="goto" :data-url="'/pagesExt/my/setother?from=regist&index='+index+'&mid='+userinfo.id">
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
						<view  class="info-item" @tap="goto" :data-url="'/pagesExt/my/setother?from=regist&index='+index">
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
			userinfo:{},
			register_forms:[],
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
			app.post('ApiMy/teamMemberinfo', {mid:that.opt.mid}, function (data) {
				that.loading = false;
				that.userinfo = data.userinfo;
				if(data.register_forms){
					that.register_forms = data.register_forms;
				}
				that.loaded();
			});
		},
		uploadHeadimg:function(){
			var that = this;
			app.chooseImage(function(urls){
				var headimg = urls[0];
				that.userinfo.headimg = headimg;
				app.post('ApiMy/setfield',{headimg:headimg,mid:that.opt.mid});
			},1,'headimg')
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
switch{ transform: scale(0.7)}

.popalert{width:100%;height:100%;position:fixed;top:0;left:0;z-index:99;background:rgba(0,0,0,0.7);}
.popalert .moudle{width:80%;margin:0 auto;height:30%;margin-top:65%;background:#fff;color:#333;padding:5px 10px 50px 10px;position:relative;border-radius:2px;border-radius: 20rpx;}
.popalert .title{color: #000000;text-align: center;padding: 10rpx 0;font-size: 19px;font-weight: 700;}
.popalert .minpricetip{letter-spacing: 4rpx;line-height: 40rpx;padding: 20rpx 20rpx;}
.popalert .btn{position:absolute;z-index:9999;bottom:10px;left:0;right:0;margin:0 auto;text-align:center; width: 50%;height: 60rpx; line-height: 60rpx; color: #fff; border-radius: 8rpx;}
</style>