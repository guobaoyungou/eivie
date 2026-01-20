<template>
<view >
	<block v-if="isload">
		<view class="container">
			<view class="enter">
				<view class="gobtn" @tap="goto" :data-url="'recordlog?rid='+detail.id" :style="{background:themeColor,color:'#FFF'}">评测详情</view>
			</view>
			<view class="box">
				<block v-if="health.type!=3">
					<view class="tag"  :style="{color:themeColor}">{{detail.score_tag}}</view>
					<view class="score" :style="{color:themeColor}">{{detail.score}}分</view>
				</block>
				<view class="child-result" v-if="detail.child_result.length>0">
					<view class="child-item" :style="'border:1rpx solid '+themeColor+''" v-for="(itemC,indexC) in detail.child_result">
						<view class="child-title">{{itemC.name}}</view>
						<view class="child-score txt1">
							<view class="score" :style="{color:themeColor}">{{itemC.score}}分</view>
							<view>{{itemC.score_tag}}</view>
						</view>
						<view class="txt1"><rich-text :nodes="itemC.score_desc"></rich-text></view>
					</view>
				</view>
				<view class="desc" v-if="health.type!=3">
					<view class="title">评测概述：</view>
					<view class="content"><rich-text :nodes="detail.score_desc"></rich-text></view>
				</view>
				<view class="desc">
					<view class="title">评测说明：</view>
					<view class="content">
						<rich-text :nodes="detail.desc"></rich-text>
					</view>
				</view>
			</view>
			<view class="box">
				<dp :pagecontent="pagecontent" :menuindex="menuindex" @getdata="getdata"></dp>
			</view>
		</view>
		<view style="height: 90rpx;"></view>
		<view class="bottom">
			<!-- 客户定制颜色 -->
			<block v-if="custom.PSQI">
				<button class="btn btn1"  @tap="goto" :data-url="'question?fid='+detail.fid">再测一次</button>
				<block>
					<button  class="btn btn2" @tap="shareapp" v-if="getplatform() == 'app'">
						邀请好友测一测
					</button>
					<button  class="btn btn2" @tap="sharemp" v-else-if="getplatform() == 'mp'">
						邀请好友测一测
					</button>
					<button  class="btn btn2" @tap="sharemp" v-else-if="getplatform() == 'h5'">
						邀请好友测一测
					</button>
					<button  class="btn btn2" open-type="share" v-else>
						邀请好友测一测
					</button>
				</block>
			</block>
			<!-- 主题色 -->
			<block v-else>
				<button class="btn btn1" :style="'background:rgba('+t('color1rgb')+',0.2);color:'+themeColor" @tap="goto" :data-url="'question?fid='+detail.fid">再测一次</button>
				<block>
					<button  class="btn" :style="{background:themeColor,color:'#FFF'}" @tap="shareapp" v-if="getplatform() == 'app'">
						邀请好友测一测
					</button>
					<button  class="btn" :style="{background:themeColor,color:'#FFF'}" @tap="sharemp" v-else-if="getplatform() == 'mp'">
						邀请好友测一测
					</button>
					<button  class="btn" :style="{background:themeColor,color:'#FFF'}" @tap="sharemp" v-else-if="getplatform() == 'h5'">
						邀请好友测一测
					</button>
					<button  class="btn" :style="{background:themeColor,color:'#FFF'}" open-type="share" v-else>
						邀请好友测一测
					</button>
				</block>
			</block>
		</view>
	</block>
	<loading v-if="loading"></loading>
	<popmsg ref="popmsg"></popmsg>
</view>
</template>

<script>
var app = getApp();
var interval = null;
export default {
	data() {
		return {
			opt:{},
			loading:false,
			isload: false,
			detail:[],
			pagecontent:[],
			id:0,
			menuindex:-1,
			health:{},
			custom:{},
			themeColor:''
		};
	},
	onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.id = this.opt.id || 0;
		this.getdata();
	},
	onPullDownRefresh: function () {
		this.getdata();
	},
	onUnload: function () {
	},
	onShareAppMessage:function(){
		var that = this;
		var shareLink = "/pagesA/health/main?id="+that.health.id
		return this._sharewx({title:that.health.name,pic:that.health.pic,desc:that.health.desc,tolink:shareLink});
	},
	onShareTimeline:function(){
		var shareLink = "/pagesA/health/main?id="+that.health.id
		var sharewxdata = this._sharewx({title:that.health.name,pic:that.health.pic,desc:that.health.desc,tolink:shareLink});
		var query = (sharewxdata.path).split('?')[1];
		return {
			title: sharewxdata.title,
			imageUrl: sharewxdata.imageUrl,
			query: query
		}
	},
	methods: {
		getdata:function(){
			var that = this;
			that.loading = true;
			app.post('ApiHealth/questionResult', {id:that.id}, function (res) {
				that.loading = false;
				if (res.status == 1) {
				  that.detail = res.detail
					that.pagecontent = res.pagecontent
					that.health = res.health
					that.custom = res.custom
					if(that.custom.PSQI){
						that.themeColor = '#229989'
					}else{
						that.themeColor = that.t('color1');
					}
					that.loaded();
				}else{
					app.alert(res.msg);
					return;
				}
			});
		},
		sharewx:function(){
			app.error('点击右上角发送给好友或分享到朋友圈');
		},
		sharemp:function(){
			app.error('点击右上角发送给好友或分享到朋友圈');
		},
		shareapp:function(){
			var that = this;
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
						sharedata.title = that.health.name;
						sharedata.summary = that.health.desc;
						sharedata.href = app.globalData.pre_url +'/h5/'+app.globalData.aid+'.html#/pagesA/health/main?scene=id_'+that.health.id+'-pid_' + app.globalData.mid;
						sharedata.imageUrl = that.health.pic;
						var sharelist = app.globalData.initdata.sharelist;
						if(sharelist){
							for(var i=0;i<sharelist.length;i++){
								if(sharelist[i]['indexurl'] == '/pagesA/health/main'){
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
		  })
		}
	}
};
</script>
<style>
.container{padding: 30rpx;}
.box{margin-bottom: 30rpx;border: 1rpx solid #F6F6F6;border-radius: 20rpx;background: #FFFFFF;padding: 30rpx;/* font-size: 12px; */ color: #666;}
.tag{font-size: 40rpx;font-weight: bold;text-align: center;}
.score{font-size: 32rpx;text-align: center;padding: 10rpx 0;}
.desc{margin-top: 20rpx;color: #666; }
.desc .title{line-height: 50rpx;/* font-size: 30rpx;*/font-weight: bold; color: #222222;}
.desc .content{line-height: 40rpx;}
.bottom{display: flex;justify-content: center;align-items: center;background: #f6f6f6;padding: 20rpx;position: fixed;bottom: 0;width: 100%;}

.bottom .btn{height: 70rpx;line-height: 70rpx;padding: 0 20rpx;border-radius: 16rpx;width: 45%;color: #ffffff;}
.btn1{background: #229989;}
.btn2{background: #09d1c8}
.enter{display: flex;justify-content: flex-end;font-size: 24rpx;}
.gobtn{width: 150rpx;height: 50rpx;line-height: 50rpx;text-align: center;right: 0;z-index: 999;border-radius: 40px 0 0 40px;margin-right: -30rpx;margin-bottom: 20rpx;}
.child-score{display: flex;justify-content: flex-start;align-items: center;}
.child-result{margin: 20rpx 0;}
.child-title{/* font-size: 30rpx;font-weight: bold; */}
.child-item{border: 1rpx solid #EEEEEE;padding: 20rpx;border-radius: 10rpx;margin-bottom: 20rpx;}
.child-score .score{margin-right: 10rpx;font-size: 28rpx;}
/* .child-result .txt1{font-size: 24rpx;} */
.content img{object-fit: contain;}
</style>