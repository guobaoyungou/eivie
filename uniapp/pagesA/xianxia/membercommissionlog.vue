<template>
<view class="container">
	<block v-if="isload">
		<dd-tab :itemdata="['待打款','待确定','完成','异议']" :itemst="['0','1','2','3']" :st="st" :isfixed="false" @changetab="changetab"></dd-tab>
		<view class="content" v-if="datalist && datalist.length>0">
			<block v-for="(item, index) in datalist" :key="index">
				<view class="item">
					<view class="title" style="line-height: 40rpx;">打款上级</view>
					<view class="f1" >
						<image :src="item.s_headimg"></image>
						<view class="t2" style="flex: 1;">
							<text class="x1">{{item.s_nickname}}(ID:{{item.mid}})</text>
							<text class="x1">等&nbsp;&nbsp;&nbsp;级：{{item.s_levelname}}</text>
							<text class="x1" v-if="item.s_tel">手机号：{{item.s_tel}}</text>
							<!-- <text class="x1">{{item.remark}}(ID:{{item.frommid}})</text> -->
						</view>
						
						<view class='t2' :style="'color:'+t('color1')">发放金额：{{item.commission}}</view>
					</view>
					<view class="f2">
						<!-- <text class="t4" v-if="userlevel && userlevel.team_yeji==1">团队业绩：{{item.teamyeji}}</text>
						<text class="t4" v-if="userlevel && userlevel.team_self_yeji==1">个人业绩：{{item.selfyeji}}</text>
						<text class="t4" v-if="userlevel && userlevel.team_down_total==1">下级人数：{{item.team_down_total}} 人</text>
						<text class="t4" v-if="userlevel && userlevel.team_score==1">积分：{{item.score}}</text> -->
						<!-- <text class="t1">+{{item.commission}}</text> -->
						<!-- <text class='t2' :style="'color:'+t('color1')">发放金额：100</text>-->
						<view class="t1">
							{{item.remark}}
						</view> 
				
						<button  class="btn" :style="{background:'linear-gradient(270deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'}" @tap="toSendCommission" :data-id="item.id" >查看凭证</button>
						
					</view>
				</view>
			</block>
		</view>
		
		<nomore v-if="nomore"></nomore>
		<nodata v-if="nodata"></nodata>
		
	</block>
	<view class="posterDialog" v-if="showsend">
		<view class="main">
			<view class="close" @tap="posterDialogClose"><image class="img" :src="pre_url+'/static/img/close.png'"/></view>
			<view class="content">
				<image class="img" :src="posterpic" mode="widthFix" @tap="previewImage" :data-url="posterpic"></image>
			</view>
		</view>
	</view>
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
		pre_url:app.globalData.pre_url,
		st: 0,
		datalist: [],
		pagenum: 1,
		nomore: false,
		nodata:false,
		checkednum:0,
		shareTitle:'',
		sharePic:'',
		shareDesc:'',
		shareLink:'',
		showsend:false,	
    };
  },

  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.st = this.opt.st || 0;
		this.getdata();
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
	onShareAppMessage:function(){
		return this._sharewx({title:this.shareTitle,pic:this.sharePic,desc:this.shareDesc,link:this.shareLink});
	},
	onShareTimeline:function(){
		var sharewxdata = this._sharewx({title:this.shareTitle,pic:this.sharePic,desc:this.shareDesc,link:this.shareLink});
		var query = (sharewxdata.path).split('?')[1]+'&seetype=circle';
		console.log(sharewxdata)
		console.log(query)
		return {
			title: sharewxdata.title,
			imageUrl: sharewxdata.imageUrl,
			query: query
		}
	},
  methods: {
    getdata: function (loadmore) {
		if(!loadmore){
			this.pagenum = 1;
			this.datalist = [];
		}
		var that = this;
		var pagenum = that.pagenum;
		var st = that.st;

		that.loading = true;
		that.nomore = false;
		that.nodata = false;
		app.post('ApiCoupon/xianxianCommissionLog', {st: st,pagenum: pagenum,type:'from'}, function (res) {
			that.loading = false;
			uni.setNavigationBarTitle({
				title: '会员系统'
			});
			var data = res.data;
		
			if (pagenum == 1) {
	
				that.datalist = data;
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
	toSendCommission:function(e){
		var id = e.currentTarget.dataset.id;
		app.goto('commissionloginfo?id='+id);
	},
    changetab: function (st) {
      this.st = st;
      uni.pageScrollTo({
        scrollTop: 0,
        duration: 0
      });
      this.getdata();
    },

		sharemp:function(){
			app.error('点击右上角发送给好友或分享到朋友圈');
			this.sharetypevisible = false
		},
		shareapp:function(){
			var that = this;
			that.sharetypevisible = false;
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
						sharedata.title = that.shareTitle;
						sharedata.summary = that.shareDesc;
						sharedata.href = that.shareLink;
						sharedata.imageUrl = that.sharePic;
						
						uni.share(sharedata);
					}
		    }
		  });
		},
  }
};
</script>
<style>
.content{width:94%;margin:0 3%;border-radius:16rpx;margin-top: 20rpx;}
.content .label{display:flex;width: 100%;padding: 16rpx;color: #333;}
.content .label .t1{flex:1}
.content .label .t2{ width:300rpx;text-align:right}

.content .item{width: 100%;padding: 10rpx 30rpx 30rpx 30rpx;min-height: 112rpx;lign-items:center;background: #fff;margin-top: 10rpx;}
.content .item .title{padding: 20rpx 0;margin-bottom: 20rpx;border-bottom: 1rpx solid #EEEEEE;color: #757575}

.content .item:first-child{border:none}
.content .item image{width: 90rpx;height: 90rpx;border-radius:4px}
.content .item .f1{display:flex;align-items:flex-start;}
.content .item .f1 .t2{display:flex;flex-direction:column;padding-left:20rpx;line-height: 45rpx;}
.content .item .f1 .t2 .x1{color: #333;font-size:26rpx;}
.content .item .f1 .t2 .x2{color: #999;font-size:24rpx;}

.content .item .f2{display:flex;justify-content: space-between;}
.content .item .f2 .t1{color: #333;font-size:28rpx;line-height: 60rpx;}
.btn{border-radius:28rpx;width:140rpx;height:56rpx;line-height:56rpx;color:#fff;font-size: 26rpx;margin: 0;}
	
</style>