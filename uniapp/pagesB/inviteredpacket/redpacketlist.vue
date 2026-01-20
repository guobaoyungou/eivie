<template>
<view class="container">
	<block v-if="isload">
		<dd-tab :itemdata="['待拆红包','已拆红包','失效红包']" :itemst="['0','1','-1']" :st="st" :isfixed="false" @changetab="changetab"></dd-tab>
		<view class="coupon-list">
			<view v-for="(item, index) in datalist" :key="index" class="coupon" @tap.stop="goto" :data-url="'redpacketdetail?rplid=' + item.id">
				<view class="pt_left">
					<view class="pt_left-content">
            <image v-if="item.pic" :src="item.pic" mode="widthFix" style="width: 100%;max-height: 200rpx;border-radius: 4rpx;"/>
					</view>
				</view>
        <view style="width: 2rpx;height: 100rpx;background-color: #eee;"></view>
				<view class="pt_right">
					<view class="f1">
            <view class="t1" :style="{color:t('color1')}"><text class="t0">￥</text><text style="font-size: 50rpx;font-weight: bold;">{{item.redpacket}}</text></view>
						<view v-if="item.shortcontent" class="t3" style="font-size: 24rpx;">
              <view>使用说明：</view>
              <view class="shortcontent">{{item.shortcontent}}</view>
            </view>
						<view class="t3">有效期至 {{item.cuttime}}</view>
					</view>
					<block v-if="st==0">
							<button @tap.stop="shareClick" :data-id="item.id" :data-redpacketid="item.redpacketid" :data-pic="item.pic"  class="btn" :style="{background:'linear-gradient(270deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'}">立即分享</button>
					</block>
				</view>
			</view>
		</view>
		<nomore v-if="nomore"></nomore>
		<nodata v-if="nodata"></nodata>
    
    <view v-if="sharetypevisible" class="popup__container">
    	<view class="popup__overlay" @tap.stop="handleClickMask"></view>
    	<view class="popup__modal" style="height:320rpx;min-height:320rpx">
    		<view class="popup__content">
    			<view class="sharetypecontent">
    				<view class="f1" @tap="shareapp" v-if="getplatform() == 'app'">
    					<image class="img" :src="pre_url+'/static/img/sharefriends.png'"/>
    					<text class="t1">分享给好友</text>
    				</view>
    				<view class="f1" @tap="sharemp" v-else-if="getplatform() == 'mp' || getplatform() == 'h5'">
    					<image class="img" :src="pre_url+'/static/img/sharefriends.png'"/>
    					<text class="t1">分享给好友</text>
    				</view>
    				<button class="f1" open-type="share" v-else-if="getplatform() != 'h5'">
    					<image class="img" :src="pre_url+'/static/img/sharefriends.png'"/>
    					<text class="t1">分享给好友</text>
    				</button>
    			</view>
    		</view>
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
			pre_url:app.globalData.pre_url,
			
      st: 0,
      datalist: [],
      pagenum: 1,
      nomore: false,
			nodata:false,
			givecheckbox:false,
			checkednum:0,

			shareTitle:'',
			sharePic:'',
			shareDesc:'',
			shareLink:'',
      sharetypevisible: false,
      showposter: false,
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
      app.post('ApiInviteRedpacket/myredpacket', {st: st,pagenum: pagenum}, function (res) {
				that.loading = false;
        if(res.status == 1){
          var data = res.data;
          if (pagenum == 1) {
          	that.checkednum = 0;
          	that.pics = res.pics;
          	that.clist = res.clist;
          	that.givecheckbox = res.givecheckbox;
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
        } else {
          if (res.msg) {
            app.alert(res.msg, function() {
              if (res.url) app.goto(res.url);
            });
          } else if (res.url) {
            app.goto(res.url);
          } else {
            app.alert('您无查看权限');
          }
        }
      });
    },
    changetab: function (st) {
      this.st = st;
      uni.pageScrollTo({
        scrollTop: 0,
        duration: 0
      });
      this.getdata();
    },
    shareClick: function (e) {
      var that = this;
      var id  = e.currentTarget.dataset.id;
      var rid = e.currentTarget.dataset.redpacketid;
      var pic = e.currentTarget.dataset.pic;
      that.shareTitle = '送你一个红包';
      that.shareDesc  = '点击前往查看领取';
      that.sharePic   = pic?pic:app.globalData.initdata.logo;
      if(app.globalData.platform == 'h5' || app.globalData.platform == 'mp' || app.globalData.platform == 'app'){
      	that.shareLink = app.globalData.pre_url +'/h5/'+app.globalData.aid+'.html#/pagesB/inviteredpacket/redpacketdetail?scene=rplid_'+id+'-rid_' + rid+'-pid_' + app.globalData.mid;
      }else{
      	that.shareLink = '/pagesB/inviteredpacket/redpacketdetail?scene=rplid_'+id+'-rid_' + rid+'-pid_' + app.globalData.mid;
      }
      that.loaded({title:that.shareTitle,pic:that.sharePic,desc:that.shareDesc,link:that.shareLink});
    	that.sharetypevisible = true;
    },
    handleClickMask: function () {
    	this.sharetypevisible = false
    },
    sharemp:function(){
    	let that = this;
    	uni.setClipboardData({
    		data: that.shareLink,
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
    	this.sharetypevisible = false
    },
		shareapp:function(){
      // #ifdef APP-PLUS
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
						sharedata.summary  = that.shareDesc;
						sharedata.href     = that.shareLink;
						sharedata.imageUrl = that.sharePic;
						uni.share(sharedata);
					}
		    }
		  });
      // #endif
		},
  }
};
</script>
<style>

.coupon-list{width:710rpx;margin: 0 auto;margin-top: 10rpx;}
.coupon{width:100%;display:flex;margin-bottom:20rpx;border-radius:10rpx;overflow:hidden;align-items:center;position:relative;background: #fff;}
.coupon .pt_left{background: #fff;min-height:200rpx;color: #FFF;width:210rpx;display:flex;flex-direction:column;align-items:center;justify-content:center;}
.coupon .pt_left-content{width:150rpx;overflow: hidden;padding: 20rpx 0;}
.coupon .pt_left .f1{font-size:40rpx;font-weight:bold;text-align:center;}
.coupon .pt_left .t0{padding-right:0;}
.coupon .pt_left .t1{font-size:60rpx;}
.coupon .pt_left .t2{padding-left:10rpx;}
.coupon .pt_left .f2{font-size:20rpx;color:#4E535B;text-align:center;}
.coupon .pt_right{background: #fff;width:490rpx;display:flex;min-height:200rpx;text-align: left;padding:20rpx 10rpx 20rpx 20rpx;align-items: center;justify-content: space-between;}
.coupon .pt_right .f1{flex-grow: 1;flex-shrink: 1;width: 300rpx;}
.coupon .pt_right .f1 .t1{font-size:28rpx;color:#2B2B2B;font-weight:bold;height:60rpx;line-height:60rpx;overflow:hidden}
.coupon .pt_right .f1 .t2{height:36rpx;line-height:36rpx;font-size:20rpx;font-weight:bold;padding:0 16rpx;border-radius:4rpx; margin-right: 16rpx;}
.coupon .pt_right .f1 .t2:last-child {margin-right: 0;}
.coupon .pt_right .f1 .t3{font-size:20rpx;color:#999999;line-height:46rpx;}
.coupon .pt_right .f1 .t4{font-size:20rpx;color:#999999;height:46rpx;line-height:46rpx;max-width: 76%;text-overflow: ellipsis;overflow: hidden;white-space: nowrap;}
.coupon .pt_right .btn{border-radius:28rpx;width:140rpx;height:56rpx;line-height:56rpx;color:#fff}
.coupon .pt_right .sygq{position:absolute;right:30rpx;top:50%;margin-top:-50rpx;width:100rpx;height:100rpx;}

.coupon .pt_left.bg3{background:#ffffff;color:#b9b9b9!important}
.coupon .pt_right.bg3 .t1{color:#b9b9b9!important}
.coupon .pt_right.bg3 .t3{color:#b9b9b9!important}
.coupon .pt_right.bg3 .t4{color:#999999!important}

.coupon .radiobox{position:absolute;left:0;padding:20rpx}
.coupon .radio{flex-shrink:0;width: 32rpx;height: 32rpx;background: #FFFFFF;border: 2rpx solid #BFBFBF;border-radius: 50%;}
.coupon .radio .radio-img{width:100%;height:100%}
.giveopbox{position:fixed;bottom:0;left:0;width:100%;}
.btn-give{width:90%;margin:30rpx 5%;height:96rpx; line-height:96rpx; text-align:center;color: #fff;font-size:30rpx;font-weight:bold;border-radius:48rpx;}
.shortcontent{word-break: break-all;text-overflow: ellipsis;overflow: hidden;display: -webkit-box;-webkit-box-orient: vertical;-webkit-line-clamp: 2;line-height: 30rpx;}
</style>