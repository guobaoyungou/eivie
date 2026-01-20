<template>
<view>
<block v-if="isload">
	<view class="topsearch flex-y-center">
		<view class="f1 flex-y-center">
			<image class="img" :src="pre_url+'/static/img/search_ico.png'"></image>
			<input :value="keyword" :placeholder="'输入'+t('会员')+'昵称搜索'" placeholder-style="font-size:24rpx;color:#C2C2C2" @confirm="searchConfirm" @input="searchChange"></input>
		</view>
	</view>
	<view class="content" v-if="datalist && datalist.length>0">
		<block v-for="(item, index) in datalist" :key="index">
			<view class="item" @tap="goto" :data-url="'message?mid=' + item.mid" @longpress="showdel" :data-mid="item.mid">
				<view class="f1">
					<image :src="item.headimg"></image>
					<view class="t2">
						<text class="x1">{{item.nickname}}</text>
						<text class="x2" v-if="item.msgtype=='image'">[图片]</text>
						<text class="x2" v-else-if="item.msgtype=='voice'">[语音]</text>
						<text class="x2" v-else-if="item.msgtype=='video'">[小视频]</text>
						<text class="x2" v-else-if="item.msgtype=='music'">[音乐]</text>
						<text class="x2" v-else-if="item.msgtype=='news'">[图文]</text>
						<text class="x2" v-else-if="item.msgtype=='link'">[链接]</text>
						<text class="x2" v-else-if="item.msgtype=='miniprogram'">[小程序]</text>
						<text class="x2" v-else-if="item.msgtype=='location'">[地理位置]</text>
						<text class="x2" v-else>{{item.content}}</text>
						<text class="x3">{{item.showtime}}</text>
					</view>
				</view>
				<view class="unread" v-if="item.noreadcount>0">{{item.noreadcount}}</view>
			</view>
		</block>
	</view>

	<nomore v-if="nomore"></nomore>
	<nodata v-if="nodata"></nodata>
	<view class="tabbar">
		<view class="tabbar-bot"></view>
		<view class="tabbar-bar" style="background-color:#ffffff;">
			<view @tap="goto" data-url="../member/index" data-opentype="reLaunch" class="tabbar-item" v-if="auth_data.member">
				<view class="tabbar-image-box">
					<image class="tabbar-icon" :src="pre_url+'/static/img/admin/member.png?v=1'"></image>
				</view>
				<view class="tabbar-text">{{t('会员')}}</view>
			</view>
			<view @tap="goto" data-url="../kefu/index" data-opentype="reLaunch" class="tabbar-item" v-if="auth_data.zixun">
				<view class="tabbar-image-box">
					<image class="tabbar-icon" :src="pre_url+'/static/img/admin/zixun2.png?v=1'"></image>
				</view>
				<view class="tabbar-text active">咨询</view>
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
	<popmsg ref="popmsg"></popmsg>
</block>
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

      st: 1,
      datalist: [],
      pagenum: 1,
      uid: 0,
      token: 0,
      keyword: '',
      nodata: false,
      nomore: false,
      auth_data: {},
    };
  },
  onUnload: function () {
  },
  onHide: function () {
  },
  onLoad: function () {
    this.getdata();
  },
  onPullDownRefresh: function () {
    this.getdata();
    uni.stopPullDownRefresh();
  },
  onReachBottom: function () {
    if (!this.nodata && !this.nomore) {
      this.pagenum = this.pagenum + 1;
      this.getdata(true);
    }
  },
  methods: {
		getdata:function(loadmore){
			if(!loadmore){
				this.pagenum = 1;
				this.datalist = [];
			}
			var that = this;
			var pagenum = this.pagenum;
			var datalist = this.datalist;
			var keyword = that.keyword;
			this.nomore = false;
			this.nodata = false;
			this.loading = true;
			app.get('ApiAdminKefu/index', {keyword:keyword,pagenum:pagenum,datalist:datalist}, function (res) {
				that.loading = false;
				if (res.status == 0){
					app.alert(res.msg);
					return;
				}
        var data = res.datalist;
				if (pagenum == 1){
					that.uid = res.uid;
					that.token = res.token;
					that.datalist = data;
					that.auth_data = res.auth_data;
          if (data.length == 0) {
            that.nodata = true;
          }
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
		sendSocketMessage:function(msg) {
			if (this.socketOpen) {
				uni.sendSocketMessage({
					data: JSON.stringify(msg)
				});
			} else {
				socketMsgQueue.push(msg);
			}
		},
    receiveMessage: function (data) {
			if(data.type == 'tokefu'){
				this.getdata();
				return true;
			}else{
				return false;
			}
    },
    showdel: function (e) {
      return;
      var that = this;
      var mid = e.currentTarget.dataset.mid;
      uni.showActionSheet({
        itemList: ['删除'],
        success(res) {
					if(res.tapIndex >= 0){
						app.post('ApiAdminKefu/del', {
							mid: mid
						}, function (d) {
							that.getdata();
						});
					}
        },
        fail(res) {}
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
.content .label{display:flex;width: 100%;padding: 16rpx;color: #666;}
.content .label .t1{flex:1}
.content .label .t2{ width:300rpx;text-align:right}

.content .item{width: 100%;padding:20rpx;border-top: 1px #e5e5e5 solid;min-height: 112rpx;display:flex;align-items:center;position:relative}
.content .item:first-child{border-top:0}
.content .item image{width:110rpx;height:110rpx;border-radius:10rpx;flex-shrink:0;}
.content .item .f1{display:flex;flex:1;overflow:hidden}
.content .item .f1 .t2{display:flex;flex-direction:column;padding-left:20rpx}
.content .item .f1 .t2 .x1{color: #666;font-size: 32rpx;}
.content .item .f1 .t2 .x2{color: #999;}
.content .item .f1 .t2 .x3{font-size:24rpx;color: #999;}
.content .item .unread{position:absolute;width:30rpx;height:30rpx;color:#fff;background:#ff5620;text-align:center;border-radius:50%;font-size:20rpx;top:8rpx;left:110rpx}

.content .item .f2{display:flex;flex-direction:column;width:200rpx;text-align:right;border-left:1px solid #eee}
.content .item .f2 .t1{ font-size: 40rpx;color: #666;height: 40rpx;line-height: 40rpx;}
.content .item .f2 .t2{ font-size: 28rpx;color: #999;height: 50rpx;line-height: 50rpx;}

</style>