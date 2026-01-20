<template>
<view class="container">
	<block v-if="isload">
		<block v-if="postercount > 1">
			<view class="arrowleft" @tap="changeposter" data-type="-1"><text class="iconfont iconjiantou" ></text></view>
			<view class="arrowright" @tap="changeposter" data-type="1"><text class="iconfont iconjiantou"></text></view>
		</block>
		<image :src="poster" class="sjew-img" @tap="previewImage" :data-url="poster" mode="widthFix"></image>
		<view class="sjew-box">
			<view class="sjew-tle flex-y-center" v-if="is_show_title">如何推荐好友拿{{t('佣金')}}</view>
			<view class="sjew-sp1"><text>{{guize}}</text></view>
		</view>
		<view class="sjew-he"></view>
		<view class="sjew-bottom" :class="menuindex>-1?'tabbarbot':'notabbarbot'">
			<view class="sjew-bottom-content">
				<view class="sjew-bot-a1" :style="{background:t('color2')}" @tap="shareapp" v-if="getplatform() == 'app'">分享链接</view>
				<view class="sjew-bot-a1" :style="{background:t('color2')}" @tap="sharemp" v-else-if="getplatform() == 'mp'">分享链接</view>
				<view class="sjew-bot-a1" :style="{background:t('color2')}" @tap="sharemp" v-else-if="getplatform() == 'h5'">分享链接</view>
				<button class="sjew-bot-a1" :style="{background:t('color2')}" open-type="share" v-else>分享链接</button>
				<view class="sjew-bot-a2" :style="{background:t('color1')}" @tap="previewImage" :data-url="poster">分享图片</view>
			</view>
		</view>
	</block>
	<loading v-if="loading"></loading>
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
			menuindex:-1,
			
			poster:'',
			guize:'',
			posterid:'',
			posterlist:[],
			postercount:1,
		shopset: [],
		is_show_title:1,
		shareTitle:'',
		sharePic:'',
		shareLink:''
    };
  },

  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.getdata();
		this.shareChange();
  },
	onPullDownRefresh: function () {
		this.getdata();
	},
  onShareAppMessage: function (res){
    var that = this;
    return {
      title: that.shareTitle,
      path: that.shareLink,
      imageUrl: that.sharePic
    };
  },
  methods: {
		shareChange(){
			var pages = getCurrentPages(); //获取加载的页面
			var currentPage = pages[pages.length - 1]; //获取当前页面的对象
			var currenturl = '/' + (currentPage.route ? currentPage.route : currentPage.__route__); //当前页面url 
			var query = ''
			var opt = this.opt;
			if(this.opt && this.opt.id){
				query+='?id='+this.opt.id
			}else if(this.opt && this.opt.cid){
				query+='?cid='+this.opt.cid
			}else if(this.opt && this.opt.gid){
				query+='?gid='+this.opt.gid
			}else if(this.opt && this.opt.bid){
				query+='?bid='+this.opt.bid
			}
			var scene = [];
			for(var i in opt){
				if(i != 'pid' && i != 'scene'){
					scene.push(i+'_'+opt[i]);
				}
			}
			if(app.globalData.mid){
				scene.push('pid_'+app.globalData.mid);
			}
			var scenes = scene.join('-');
			var currentpath = '/pages/index/index';
			if(scenes){
				currentpath = currentpath + "?scene="+scenes + '&t='+parseInt((new Date().getTime())/1000);
			}
			this.shareLink = currentpath;
			var currentfullurl = currenturl+query
			var sharelist = app.globalData.initdata.sharelist;
			if(sharelist){
				for(var i=0;i<sharelist.length;i++){
					if((sharelist[i]['is_rootpath']==1 && sharelist[i]['indexurl'] == currenturl) || (!sharelist[i]['is_rootpath'] && sharelist[i]['indexurl'] == currentfullurl)){
						this.shareTitle = sharelist[i].title;
						this.sharePic = sharelist[i].pic;
						this.shareLink = sharelist[i].url ? sharelist[i].url : currentpath; //分享链接，不填写代表首页
					}
				}
			}
		},
		getdata:function(){
			var that = this;
			var posterid = that.posterid;
			app.showLoading('海报生成中');
			app.get('ApiAgent/poster', {posterid:posterid}, function (res) {
				app.showLoading(false);
				if (res.status == 0) {
					app.alert(res.msg);
				}
				that.poster = res.poster;
				that.guize = res.guize;
				that.posterid = res.posterid;
				that.posterlist = res.posterlist;
				that.postercount = res.postercount;
				that.is_show_title = res.is_show_title;
				that.loaded({title:app.globalData.initdata.name,pic:app.globalData.initdata.logo,link:app.globalData.pre_url +'/h5/'+app.globalData.aid+'.html#'+ app.globalData.initdata.indexurl + '?scene=pid_' + app.globalData.mid});
			});
		},
    changeposter: function (e) {
			var type = parseInt(e.currentTarget.dataset.type)
      var that = this;
			var posterlist = that.posterlist;
			var posterid = that.posterid;
			var index = 0;
			for(var i in posterlist){
				if(posterlist[i].id == posterid){
					index = i;
				}
			}
			index = parseInt(index) + type;
			if(index == posterlist.length){
				index = 0;
			}
			if(index < 0){
				index = posterlist.length - 1;
			}
			that.posterid = posterlist[index].id;
			that.getdata();
    },
		sharemp:function(){
			if(app.globalData.platform == 'mp') return app.error('点击右上角发送给好友或分享到朋友圈');
			let that = this;
			let shareLink = app.globalData.pre_url +'/h5/'+app.globalData.aid+'.html#'+that.shareLink;
			uni.setClipboardData({
				data: shareLink,
				success: function() {
					uni.showToast({
						title: '复制成功,快去分享吧！',
						duration: 3000,
						icon: 'none'
					});
				},
				fail: function(err) {
					uni.showToast({
						title: '复制失败',
						duration: 2000,
						icon: 'none'
					});
				}
			});
		},
		shareapp:function(){
			uni.showActionSheet({
        itemList: ['发送给微信好友', '分享到微信朋友圈'],
        success: function (res){
					if(res.tapIndex >= 0){
						var scene = 'WXSceneSession';
						if (res.tapIndex == 1) {
							scene = 'WXSenceTimeline';
						}
						var sharedata = {};
						sharedata.provider = 'weixin';
						sharedata.type = 0;
						sharedata.scene = scene;
						sharedata.title = app.globalData.initdata.name;
						sharedata.summary = app.globalData.initdata.desc;
						sharedata.href = app.globalData.pre_url +'/h5/'+app.globalData.aid+'.html#'+that.shareLink+ '?scene=pid_' + app.globalData.mid;
						sharedata.imageUrl = app.globalData.initdata.logo;
						var sharelist = app.globalData.initdata.sharelist;
						if(sharelist){
							for(var i=0;i<sharelist.length;i++){
								if(sharelist[i]['indexurl'] == app.globalData.initdata.indexurl){
									sharedata.title = sharelist[i].title;
									sharedata.summary = sharelist[i].desc;
									sharedata.imageUrl = sharelist[i].pic;
									if(sharelist[i].url){
										var sharelink = sharelist[i].url;
										if(sharelink.indexOf('/') === 0){
											sharelink = app.globalData.pre_url +'/h5/'+app.globalData.aid+'.html#'+ sharelink;
										}
										if(app.globalData.mid>0){
											 sharelink += (sharelink.indexOf('?') === -1 ? '?' : '&') + 'pid='+app.globalData.mid;
										}
										sharedata.href = sharelink;
									}
								}
							}
						}
						uni.share(sharedata);
					}
        }
      });
		}
  }
};
</script>
<style>
.sjew-img{ width:100%; display: block;}
.sjew-box{ width:100%; background:#FFF; margin-bottom:120rpx;padding-bottom:10px;margin-top:20rpx}
.sjew-tle{ width:100%;padding:0 2%; border-bottom:1px #E6E6E6 solid;  height:100rpx;line-height:100rpx; color:#111;}
.sjew-ic1{width:46rpx;height:46rpx;display: block;margin-right:10rpx;}
.sjew-sp1{ width:100%; padding:0 20rpx; color:#666; margin-top:20rpx; }
.sjew-sp2{ width:78%; color:#999; margin-top:20rpx; }
.sjew-sp3{ width:96%; margin:20rpx 2%;background:#fe924a; padding:16rpx 2%; color:#fff;  box-sizing: border-box;}

.sjew-bottom{ width:94%; position:fixed; bottom:0px;height:110rpx;line-height:110rpx;border-top:1px #ececec solid;background:#fff;display:flex;padding:15rpx 3%;box-sizing:content-box}
.sjew-bottom-content{height:80rpx;line-height:80rpx;width:100%;border-radius:50rpx;display:flex;border-radius:45rpx;overflow:hidden}
.sjew-bot-a1{flex:1;height:80rpx;line-height:80rpx;text-align:center;color:#fff;}
.sjew-bot-a2{flex:1;height:80rpx;line-height:80rpx;text-align:center;color:#fff;}
.sjew-he{width: 100%;height:20rpx;}

.arrowleft{position:fixed;top:calc(var(--window-top) + 140rpx);display:flex;justify-content:center;z-index:8;transform: rotateY(180deg);background:rgba(0,0,0,0.3);color:#fff;border-radius:50%;left:40rpx;width:60rpx;height:60rpx;line-height:60rpx;text-align:center;}
.arrowright{position:fixed;top:calc(var(--window-top) + 140rpx);display:flex;justify-content:center;z-index:8;background:rgba(0,0,0,0.3);color:#fff;border-radius:50%;right:40rpx;width:60rpx;height:60rpx;line-height:60rpx;text-align:center}

</style>