<template>
<view class="container">
	<block v-if="isload">
		<view class="box-title">我的消息</view>
		<view class="box flex-sb" @tap="goto" data-url="list?type=2">
			<view class="pic">
				<image src="../../static/img/clock.png"></image>
			</view>
			<view class="content">
				<view class="title">收到的招聘</view>
				<view class="tips">
					<text v-if="zhaopin==''">暂未收到招聘</text>
					<block v-else>
						<text v-if="zhaopin && zhaopin.hasnew">收到新的招聘信息</text>
						<text v-if="!zhaopin || zhaopin.hasnew==0">查看收到的招聘</text>
					</block>
				</view>
			</view>
			<view class="time">{{zhaopin?zhaopin.createtime:''}}</view>
		</view>
		<view class="box flex-sb" @tap="goto" data-url="list?type=1">
			<view class="pic">
				<image :src="pre_url+'/static/img/admin/order4.png'"></image>
			</view>
			<view class="content">
				<view class="title">收到的简历</view>
				<view class="tips">
					<text v-if="qiuzhi==''">暂未收到简历投送</text>
					<block v-else>
						<text v-if="qiuzhi.hasnew==1">收到新的简历投送</text>
						<text v-if="qiuzhi.hasnew==0">查看收到的简历</text>
					</block>
				</view>
			</view>
			<view class="time">{{qiuzhi?qiuzhi.createtime:''}}</view>
		</view>
		<!-- kefu -->
		<view class="box flex-sb" v-if="kfurl!='contact::'" @tap="goto" :data-url="kfurl">
			<view class="pic">
				<image src="/static/img/kefu.png"></image>
			</view>
			<view class="content">
				<view class="title">平台客服</view>
				<view class="tips">
					<text v-if="kefu==''">暂无平台信息</text>
					<block v-else>
						<text v-if="kefu.hasnew==1">收到新的平台信息</text>
						<text v-if="kefu.hasnew==0">查看平台信息</text>
					</block>
				</view>
			</view>
			<view class="time">{{kefu?kefu.createtime:''}}</view>
		</view>
		<button class="box flex-sb" v-else open-type="contact">
			<view class="pic">
				<image src="/static/img/kefu.png"></image>
			</view>
			<view class="content">
				<view class="title">平台客服</view>
				<view class="tips">
					<text v-if="kefu==''">暂无平台信息</text>
					<block v-else>
						<text v-if="kefu.hasnew==1">收到新的平台信息</text>
						<text v-if="kefu.hasnew==0">查看平台信息</text>
					</block>
				</view>
			</view>
			<view class="time">{{kefu?kefu.createtime:''}}</view>
		</button>
		<!-- kefu end -->
		<view class="box-title box-mg">我的私信</view>
		<view class="box flex-sb" v-for="(item,index) in datalist" :key="index"  @tap="godetail" :data-fmid="item.mid" :data-id="item.id" :data-tname="item.tablename" :data-tid="item.tableid">
			<view class="pic">
				<image :src="item.headimg"></image>
			</view>
			<view class="content">
				<view class="title">{{item.nickname}}</view>
				<view class="tips">
					{{item.content}}
				</view>
			</view>
			<view class="time">{{item.showtime}}</view>
		</view>
	</block>
	<loading v-if="loading"></loading>
	<nomore text="没有更多信息了" v-if="nomore"></nomore>
	<nodata text="没有查找到相关信息" v-if="nodata"></nodata>
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
			pre_url: app.globalData.pre_url,
			nomore:false,
			nodata:false,
			zhaopin:'',
			qiuzhi:'',
			kefu:'',
			datalist:[],
			pagenum:1,
			nomore:false,
			nodata:false,
			kfurl:''
    };
  },
  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.getdata();
  },
	onPullDownRefresh: function () {
		this.getdata();
	},
  onReachBottom: function () {
    if (!this.nodata && !this.nomore) {
      this.pagenum = this.pagenum + 1;
      this.getlist(true);
    }
  },
  methods: {
		getdata:function(){
			var that = this;
			app.post('ApiZhaopin/noticeIndex',{}, function (res) {
				that.zhaopin = res.zhaopin
				that.qiuzhi = res.qiuzhi
				that.kefu = res.kefu
				that.kfurl = app.globalData.initdata.kfurl
				var platform = app.globalData.platform
				if(platform=='app'){
					that.kfurl = '/pages/kefu/index'
				}
			});
			that.getlist(false)
			that.loaded();
		},
		getlist: function (loadmore) {
		  var that = this;
			if(!loadmore){
				this.pagenum = 1;
				this.datalist = [];
			}
		  var pagenum = that.pagenum;
		  var keyword = that.keyword;
		  app.post('ApiZhaopin/getChatlistMy',{
				pagenum: pagenum
			}, function (res) {
				that.loading = false;
		    var data = res.data;
		    if (pagenum == 1) {
		      that.datalist = data;
		      if (data.length == 0) {
		        that.nodata = true;
		      }
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
		godetail:function(e){
			var that = this;
			var tableid = e.currentTarget.dataset.tid;
			var tablename = e.currentTarget.dataset.tname;
			var id = e.currentTarget.dataset.id;
			var from_mid = e.currentTarget.dataset.fmid;
			var url = '';
			if(tablename=='zhaopin_qiuzhi'){
				 url = '/zhaopin/qiuzhi/chat?type=2&id='+tableid+'&tomid='+from_mid
			}else if(tablename=='zhaopin'){
				 url = '/zhaopin/zhaopin/chat?type=1&id='+tableid+'&tomid='+from_mid
			}
			if(url){
				app.goto(url,'redirect')
			}
		},
		
  }
};
</script>
<style>
	@import "../common.css";
	.container{padding: 0;}
	.box{background: #FFFFFF;padding: 20rpx 30rpx;border-bottom: 1rpx solid #efefef;}
	.box:last-child{border: none;}
	.box-mg{margin-top: 30rpx;}
	.box .content{flex: 1;align-self: flex-start;align-items: flex-start;text-align: left;}
	.box .title{font-size: 32rpx;font-weight: bold;line-height: 60rpx;}
	.box .pic{flex-shrink: 0;width: 120rpx;}
	.box .time{color: #CCCCCC;}
	.box .pic image{height: 100rpx;width: 100rpx;border-radius: 10rpx;}
	.box-title{padding: 30rpx 20rpx;font-size: 30rpx;border-bottom: 1rpx solid #EFEFEF;background: #FFFFFF;font-weight: bold;}
</style>