<template>
<view class="container">
	<block v-if="isload">
		<dd-tab :itemdata="['未售出（'+sycount+')','已售出('+sendcount+')']" :itemst="['0','1']" :st="st" :isfixed="false" @changetab="changetab"></dd-tab>

		<view class="coupon-list">
			<view v-for="(item, index) in datalist" :key="index" class="coupon" @tap.stop="goto" :data-url="'coupondetail?rid=' + item.id" >
				
				<view class="pt_left">
					<view class="pt_left-content">
						<view class="f1" :style="{color:t('color1')}" v-if="item.type==1"><text class="t0">￥</text><text class="t1">{{item.money}}</text></view>
						<view class="f1" :style="{color:t('color1')}" v-if="item.type==10"><text class="t1">{{item.discount/10}}</text><text class="t0">折</text></view>
						<view class="f1" :style="{color:t('color1')}" v-if="item.type==3"><text class="t1">{{item.limit_count}}</text><text class="t2">次</text></view>
						<view class="f1" :style="{color:t('color1')}" v-if="item.type==5"><text class="t0">￥</text><text class="t1">{{item.money}}</text></view>
						<block v-if="item.type!=1 && item.type!=10 && item.type!=3 && item.type!=5">
							<view class="f1" :style="{color:t('color1')}">{{item.type_txt}}</view>
						</block>
						<view class="f2" :style="{color:t('color1')}" v-if="item.type==1 || item.type==4 || item.type==5 || item.type==10">
							<text v-if="item.minprice>0">满{{item.minprice}}元可用</text>
							<text v-else>无门槛</text>
						</view>
					</view>
				</view>
				<view class="pt_right">
					<view class="f1">
						<view class="t1">{{item.couponname}}</view>
						
						<text class="t2" :style="{background:'rgba('+t('color1rgb')+',0.1)',color:t('color1')}">{{item.type_txt}}</text>
						<text class="t2" v-if="!item.from_mid && (item.isgive == 1 || item.isgive == 2)" :style="{background:'rgba('+t('color2rgb')+',0.1)',color:t('color2')}">可赠送</text>
						<view class="count" ><text v-if="st==0">剩余</text><text v-else>数量</text>：{{item.count}} 张</view>
						<view class="t3" :style="item.bid>0?'margin-top:0':''">有效期至 {{item.endtime}}</view>
						<view class="t4" v-if="item.bid>0">适用商家：{{item.bname}}</view>
					</view>
					<block v-if="st==0">
							<button class="btn" :style="{background:'linear-gradient(270deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'}" @tap="goto" :data-url="'myteam?couponid='+item.couponid+'&count='+item.count">发放</button>
					</block>
					<image class="sygq" v-if="st==1" :src="pre_url+'/static/img/ysc.png'"></image>
					<image class="sygq" v-if="st==2" :src="pre_url+'/static/img/ygq.png'"></image>
				</view>
			</view>
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
			givecheckbox:false,
			checkednum:0,
			test:'',
			shareTitle:'',
			sharePic:'',
			shareDesc:'',
			shareLink:'',
			showsend:false,
			sendcount:0,
			sycount:0
			
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
      var bid = that.opt && (that.opt.bid || that.opt.bid === '0') ? that.opt.bid : '';
			that.loading = true;
			that.nomore = false;
			that.nodata = false;
      app.post('ApiCoupon/myXianxiaCoupon', {st: st,pagenum: pagenum,bid: bid}, function (res) {
				that.loading = false;
				uni.setNavigationBarTitle({
					title: '销售' + that.t('优惠券')
				});
        var data = res.data;
		that.sendcount = res.sendcount;
		that.sycount = res.sycount;
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
      });
    },
	sendCoupon:function(){
		this.showsend = !this.showsend;
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

.coupon-list{width:100%;padding:20rpx}
.coupon{width:100%;display:flex;margin-bottom:20rpx;border-radius:10rpx;overflow:hidden;align-items:center;position:relative;background: #fff;}
.coupon .pt_left{background: #fff;min-height:200rpx;color: #FFF;width:30%;display:flex;flex-direction:column;align-items:center;justify-content:center}
.coupon .pt_left-content{width:100%;height:100%;margin:30rpx 0;border-right:1px solid #EEEEEE;display:flex;flex-direction:column;align-items:center;justify-content:center}
.coupon .pt_left .f1{font-size:40rpx;font-weight:bold;text-align:center;}
.coupon .pt_left .t0{padding-right:0;}
.coupon .pt_left .t1{font-size:60rpx;}
.coupon .pt_left .t2{padding-left:10rpx;}
.coupon .pt_left .f2{font-size:20rpx;color:#4E535B;text-align:center;}
.coupon .pt_right{background: #fff;width:70%;display:flex;min-height:200rpx;text-align: left;padding:20rpx 20rpx;position:relative}
.coupon .pt_right .f1{flex-grow: 1;flex-shrink: 1;}
.coupon .pt_right .f1 .t1{font-size:28rpx;color:#2B2B2B;font-weight:bold;height:60rpx;line-height:60rpx;overflow:hidden}
.coupon .pt_right .f1 .t2{height:36rpx;line-height:36rpx;font-size:20rpx;font-weight:bold;padding:0 16rpx;border-radius:4rpx; margin-right: 16rpx;}
.coupon .pt_right .f1 .t2:last-child {margin-right: 0;}
.coupon .pt_right .f1 .t3{font-size:20rpx;color:#999999;height:46rpx;line-height:46rpx;}
.coupon .pt_right .f1 .t4{font-size:20rpx;color:#999999;height:46rpx;line-height:46rpx;max-width: 76%;text-overflow: ellipsis;overflow: hidden;white-space: nowrap;}
.coupon .pt_right .f1 .count{height: 50rpx;line-height: 55rpx;font-size: 28rpx;color: #FD4A46;}
.coupon .pt_right .btn{position:absolute;right:16rpx;top:49%;margin-top:-28rpx;border-radius:28rpx;width:140rpx;height:56rpx;line-height:56rpx;color:#fff}
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

</style>