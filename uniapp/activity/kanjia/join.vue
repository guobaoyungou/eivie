<template>
<view>
	<block v-if="isload">
		<view class="container" :style="'background:url(' + pre_url + '/static/img/kanjia-bg.png) no-repeat;background-size:100% auto;'">
			<view class="topcontent">
				<image :src="pre_url + '/static/img/kanjia-hb.png'" class="hongbao"></image>
				<view class="userinfo">
					<view class="f1"><image :src="joinuserinfo.headimg"></image></view>
					<view class="f2">{{joinuserinfo.nickname}}</view>
				</view>
				<view class="content">
					<view class="title">〝我看中这个宝贝, 快帮我砍一刀〞</view>
					<view class="proinfo">
						<view class="f1">
							<image :src="product.pic" @tap="goto" :data-url="'product?id=' + product.id"></image>
						</view>
						<view class="f2">
							<view class="t1">{{product.name}}</view>
							<view class="t2">已砍走 <text style="color:#FF4C00">{{product.sales}}</text> 件</view>
							<view class="t3">¥<text class="x1">{{product.min_price}}</text> <text class="x2">¥{{product.sell_price}}</text></view>
						</view>
					</view>
					<view class="progressinfo">
						<view class="t0">已砍 <text class="x1">￥{{joininfo.yikan_price}}</text>，剩余 <text class="x2">￥{{joininfo.now_price}}</text></view>
						<view class="t2">
							<progress :percent="cut_percent" border-radius="3" activeColor="#FF3143" backgroundColor="#FFD1C9" active="true"></progress>
						</view>
						<view class="t3">
							<view class="x1">原价：<text style="color:#FF3143">￥{{product.sell_price}}</text></view>
							<view class="x2">最低砍至：<text style="color:#FF3143">￥{{product.min_price}}</text></view>
						</view>
					</view>
					<view class="op">
						<button @tap="shareClick" class="btn1" v-if="mid==joininfo.mid && joininfo.status==0" style="background: linear-gradient(90deg, #FF3143 0%, #FE6748 100%);">召唤好友帮我砍价</button>
						<button @tap="goto" class="btn1" style="background:#FC4343;width:320rpx" :data-url="'buy?joinid=' + joininfo.id" v-if="mid==joininfo.mid && (joininfo.status==1) && joininfo.isbuy==0">前去下单</button>
						<button @tap="goto" data-url="orderlist" class="btn1" style="background:#FC4343;width:320rpx" v-if="mid==joininfo.mid && joininfo.isbuy==1">查看订单</button>
						<button @tap="doKan" class="btn1" style="background:#FC4343;width:320rpx" v-if="mid!=joininfo.mid && iskan==0">帮他砍价</button>
						<button @tap="goto" :data-url="'product?id=' + product.id" class="btn1" style="background:#FC4343;width:320rpx" v-if="mid!=joininfo.mid && iskan==1">我也要参与</button>
						<button @tap="goto" :data-url="'helplist?joinid=' + joininfo.id" class="btn1">帮砍记录</button>
					</view>
					<view class="op" v-if="mid==joininfo.mid && (joininfo.status==0 && product.directbuy==1) && joininfo.isbuy==0" style="margin-top:20rpx">
						<button @tap="goto" class="btn1" style="background:linear-gradient(90deg,#FE6748  0%, #FF3143 100%);width:560rpx" :data-url="'buy?joinid=' + joininfo.id">现在就去下单</button>
					</view>
					<view class="lefttime">
						<view class="t1">距活动结束还剩：</view>
						<view class="t2">{{djs}}</view>
					</view>

				</view>
			</view>
		</view>

		
		<view v-if="sharetypevisible" class="popup__container">
			<view class="popup__overlay" @tap.stop="handleClickMask"></view>
			<view class="popup__modal" style="height:320rpx;min-height:320rpx">
				<!-- <view class="popup__title">
					<text class="popup__title-text">请选择分享方式</text>
					<image :src="pre_url+'/static/img/close.png'" class="popup__close" style="width:36rpx;height:36rpx" @tap.stop="hidePstimeDialog"/>
				</view> -->
				<view class="popup__content">
					<view class="sharetypecontent">
						<view class="f1" @tap="shareapp" v-if="getplatform() == 'app'">
							<image class="img" :src="pre_url+'/static/img/sharefriends.png'"/>
							<text class="t1">分享给好友</text>
						</view>
						<view class="f1" @tap="sharemp" v-else-if="getplatform() == 'mp'">
							<image class="img" :src="pre_url+'/static/img/sharefriends.png'"/>
							<text class="t1">分享给好友</text>
						</view>
					<!-- 	<view class="f1" @tap="sharemp" v-else-if="getplatform() == 'h5'">
							<image class="img" :src="pre_url+'/static/img/sharefriends.png'"/>
							<text class="t1">分享给好友</text>
						</view> -->
						<button class="f1" open-type="share" v-else-if="getplatform() != 'h5'">
							<image class="img" :src="pre_url+'/static/img/sharefriends.png'"/>
							<text class="t1">分享给好友</text>
						</button>
						<view class="f2" @tap="showPoster">
							<image class="img" :src="pre_url+'/static/img/sharepic.png'"/>
							<text class="t1">生成分享图片</text>
						</view>
					</view>
				</view>
			</view>
		</view>
		<view class="posterDialog" v-if="showposter">
			<view class="main">
				<view class="close" @tap="posterDialogClose"><image class="img" :src="pre_url+'/static/img/close.png'"/></view>
				<view class="content">
					<image class="img" :src="posterpic" mode="widthFix" @tap="previewImage" :data-url="posterpic"></image>
				</view>
			</view>
		</view>

		<view class="kanjiaDialog" v-if="showkanjia">
			<view class="main">
				<view class="content">
					<view class="bargainIco">
							<!-- <text class="bargainIcoPrice">{{helpinfo.cut_price}}</text> -->
							<image class="bargainIcoImg" :src="pre_url + '/static/img/bargainbg.png'"></image>
					</view>
					<block v-if="joininfo.mid == helpinfo.mid">
						<view class="bargainPrice">砍掉{{helpinfo.cut_price}}元</view>
						<text class="bargainText">您自己砍了第一刀</text>
					</block>
					<block v-else>
						<view class="bargainPrice">帮好友砍掉{{helpinfo.cut_price}}元</view>
						<view class="bargainText" v-if="helpinfo.givescore > 0">奖励您{{helpinfo.givescore}}{{t('积分')}}<text v-if="product.helpgive_ff==1">，好友买单后发放</text></view>
						<view class="bargainText" v-if="helpinfo.givemoney > 0">奖励您{{helpinfo.givemoney}}{{t('余额')}}<text v-if="product.helpgive_ff==1">，好友买单后发放</text></view>
					</block>
					<form @submit="hideKanjiaDialog" reportSubmit="true">
						<button class="bargainBtn SysBtn" form-type="submit" type="default">确定</button>
					</form>
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
var interval = null;

export default {
  data() {
    return {
			opt:{},
			loading:false,
      isload: false,
			menuindex:-1,
			
			pre_url:app.globalData.pre_url,
			product:{},
			mid:'',
			iskan:0,
      nowtime: "",
      djs: "",
      showkanjia: false,
      helpinfo: {},
      joininfo: {},
			joinuserinfo:{},
      cut_percent: "",
      sharetypevisible: false,
      showposter: false,
      posterpic: ""
    };
  },
  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.getdata();
  },
	onPullDownRefresh: function () {
		this.getdata();
	},
  onUnload: function () {
    clearInterval(interval);
  },
  onShareAppMessage: function () {
    var that = this;
    this.sharetypevisible = false;
		var thisurl = '/activity/kanjia/join?scene=pid_'+app.globalData.mid+'-proid_'+that.product.id+'-joinid_' + that.joininfo.id;
    console.log(thisurl);
    return {
      title: '快来帮我砍一刀~ ' + that.product.name,
      imageUrl: that.product.pics[0],
      path: thisurl
    };
  },
	onShareTimeline:function(){
    var that = this;
		var query = 'scene=pid_'+app.globalData.mid+'-proid_'+that.product.id+'-joinid_' + that.joininfo.id+'-seetype_circle';
		console.log(query);
		return {
			title: '快来帮我砍一刀~ ' + that.product.name,
			imageUrl: that.product.pics[0],
			query: query
		}
	},
  methods: {
		getdata: function () {
			var that = this;
			var proid = that.opt.proid;
			var joinid = that.opt.joinid ? that.opt.joinid : '';
			clearInterval(interval);
			that.loading = true;
			app.get('ApiKanjia/join', {proid: proid,joinid: joinid}, function (res) {
				that.loading = false;
				if (res.status == 0) {
					app.alert(res.msg);
					return;
				}
				that.mid = res.mid;
				that.product = res.product;
				that.joininfo = res.joininfo;
				that.joinuserinfo = res.joinuserinfo;
				that.nowtime = res.nowtime;
				that.cut_percent = res.cut_percent;
				that.iskan = res.iskan;
				var pagecontent = JSON.parse(res.product.detail);

				that.pagecontent = pagecontent
				interval = setInterval(function () {
					that.nowtime = that.nowtime + 1;
					that.getdjs();
				}, 1000);
				if (res.joininfo.helpnum == 0) {
					that.doKan();
				}
				var sharelink = app.globalData.pre_url +'/h5/'+app.globalData.aid+'.html#/activity/kanjia/join?scene=pid_'+app.globalData.mid+'-proid_'+that.product.id+'-joinid_' + that.joininfo.id + '&t='+parseInt((new Date().getTime())/1000);
				that.loaded({title:that.product.name,desc:'快来帮我砍一刀~ ',pic:that.product.pics[0],link:sharelink});
			});
		},
    getdjs: function () {
      var that = this;
      var totalsec = that.product.endtime * 1 - that.nowtime * 1;
      if (totalsec <= 0) {
        var djs = '00时00分00秒';
      } else {
        var date = Math.floor(totalsec / 86400);
        var houer = Math.floor((totalsec - date * 86400) / 3600);
        var min = Math.floor((totalsec - date * 86400 - houer * 3600) / 60);
        var sec = totalsec - date * 86400 - houer * 3600 - min * 60;
        var djs = (date > 0 ? date + '天' : '') + (houer < 10 ? '0' : '') + houer + '时' + (min < 10 ? '0' : '') + min + '分' + (sec < 10 ? '0' : '') + sec + '秒';
      }
      that.djs = djs;
    },
    doKan: function () {
      var that = this;
      var product = that.product;
      app.post('ApiKanjia/kanjiaKan', {joinid: that.joininfo.id}, function (res) {
        if (res.status == 0) {
          app.alert(res.msg);
          return;
        }
        var cut_percent = Math.round((product.sell_price * 1 - res.joininfo.now_price * 1) / (product.sell_price * 1 - product.min_price * 1) * 100);
        that.showkanjia = true;
        that.helpinfo = res.helpinfo;
        that.joininfo = res.joininfo;
        that.cut_percent = cut_percent;
        that.getdata();
      });
    },
    hideKanjiaDialog: function (e) {
      this.showkanjia = !this.showkanjia;
    },
    shareClick: function () {
      this.sharetypevisible = true;
    },
    handleClickMask: function () {
      this.sharetypevisible = false;
    },
    showPoster: function () {
      var that = this;
      that.showposter = true;
      that.sharetypevisible = false;
			app.showLoading('努力生成中');
      app.post('ApiKanjia/getJoinPoster', {proid: that.product.id,joinid: that.joininfo.id}, function (data) {
				app.showLoading(false);
        if (data.status == 0) {
          app.alert(data.msg);
        } else {
          that.posterpic = data.poster;
        }
      });
    },
    posterDialogClose: function () {
      this.showposter = false;
    },
		sharemp:function(){
			app.error('点击右上角发送给好友或分享到朋友圈');
			this.sharetypevisible = false
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
						sharedata.title = '快来帮我砍一刀~ ' + that.product.name;
						//sharedata.summary = app.globalData.initdata.desc;

						sharedata.href = app.globalData.pre_url +'/h5/'+app.globalData.aid+'.html#/activity/kanjia/join?scene=pid_'+app.globalData.mid+'-proid_'+that.product.id+'-joinid_' + that.joininfo.id + '&t='+parseInt((new Date().getTime())/1000);
						sharedata.imageUrl = that.product.pic;
						
						uni.share(sharedata);
					}
        }
      });
		}
  }
};
</script>
<style>
page{background: linear-gradient(0deg, #DF3939 0%, #FF2B49 100%);}
.container{padding-top:280rpx;display:flex;flex-direction:column}
.topcontent{background:#FFDCB4;border-radius:20rpx;width:94%;margin:0 3%;position:relative;padding:12rpx;margin-bottom:20rpx}
.topcontent .hongbao{position:absolute;top:-32rpx;right:20rpx;width:128rpx;height:88rpx}
.topcontent .userinfo{width:100%;display:flex;flex-direction:column;align-items:center;position:absolute;top:-60rpx}
.topcontent .userinfo .f1{width:126rpx;height:126rpx;background:#FA6251;border-radius:50%;padding:8rpx}
.topcontent .userinfo .f1 image{width:100%;height:100%;border-radius:50%;}
.topcontent .userinfo .f2{font-size:28rpx;color:#999999;margin-top:8rpx}
.topcontent .content{background:#fff;padding-top:92rpx;padding-bottom:20rpx;border-radius:20rpx;}
.topcontent .title{font-size:40rpx;color:#514544;font-weight:bold;padding:32rpx 0;text-align:center}
.topcontent .proinfo{width:100%;background:#F5F5F5;padding:16rpx;display:flex;}
.topcontent .proinfo .f1{width:240rpx;height:240rpx}
.topcontent .proinfo .f1 image{width:100%;height:100%}
.topcontent .proinfo .f2{flex:1;padding-left:20rpx}
.topcontent .proinfo .f2 .t1{width:100%;height:80rpx;line-height:40rpx;font-size:30rpx;color:#5A4742;font-weight:bold;overflow:hidden}
.topcontent .proinfo .f2 .t2{color:#999999;font-size:26rpx;margin-top:10rpx}
.topcontent .proinfo .f2 .t3{color:#FF4C00;margin-top:30rpx}
.topcontent .proinfo .f2 .t3 .x1{font-size:48rpx;font-weigth:bold;padding-right:10rpx}
.topcontent .proinfo .f2 .t3 .x2{font-size:24rpx;color:#999999;text-decoration: line-through;}

.topcontent .progressinfo{width:100%;padding:80rpx 40rpx;display:flex;flex-direction:column}
.topcontent .progressinfo .t0{width:100%;color:#222;font-size:30rpx;font-weight:bold;display:flex;text-align:center;justify-content:center}
.topcontent .progressinfo .t0 .x1{color:#FF1324}
.topcontent .progressinfo .t0 .x2{color:#FF3143}
.topcontent .progressinfo .t1{width:100%;color:#f60;font-size:36rpx;flex:auto;display:flex;}
.topcontent .progressinfo .t2{width:100%;padding:20rpx 0}
.topcontent .progressinfo .t3{width:100%;color:#222;font-size:28rpx;display:flex;}
.topcontent .progressinfo .t3 .x1{width:50%}
.topcontent .progressinfo .t3 .x2{width:50%;text-align:right}
.weui-progress{width:100%;}
.weui-progress__bar{height:16rpx;background-color:#FFD1C9;border-radius: 8rpx;}
.weui-progress__inner-bar{background-color:#FF3143;border-radius: 8rpx;}

.topcontent .op{margin:0 20rpx 40rpx 20rpx;display:flex;align-items:center;justify-content:center}
.topcontent .op button{height:80rpx;line-height:80rpx;font-size:30rpx;padding:0 40rpx;border-radius:40rpx;background:#FC9144;width:auto;color:#fff;margin:0 20rpx}
.topcontent .op button:after{border:0}

.topcontent .lefttime{margin-top:20rpx;border-top:1px solid #f5f5f5;color:#222;font-weight:bold;display:flex;text-align:center;justify-content:center;padding:20rpx 20rpx 0 20rpx}
.topcontent .lefttime .t2{color:#FF3143}


.op{margin:40rpx;display:flex;align-items:center;justify-content:center}
.op button{height:80rpx;line-height:80rpx;font-size:32rpx;padding:0 40rpx;border-radius:10rpx;background:#FC9144;width:auto;color:#fff}
.op button:after{border:0}

.kanjiaDialog{ position:fixed;z-index:901;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:999;top:0;left:0;align-items:center}
.kanjiaDialog .main{ width:70%;margin:auto;margin-top:50%;background:#fff;position:relative;border-radius:20rpx}
.kanjiaDialog .content{ width:100%;padding:30rpx 20rpx 30rpx 20rpx;color:#333;font-size:30rpx;text-align:center}
.kanjiaDialog .content .bargainIco {width: 200rpx;height: 200rpx;color: #ca2428;margin: 20rpx auto;position: relative;}
.kanjiaDialog .content .bargainIcoImg {width: 200rpx;height: 200rpx;}
.kanjiaDialog .content .bargainIcoPrice {width: 120rpx;height: 100rpx;transform: rotate(-16deg);position: absolute;top: 70rpx;left: 44rpx;}
.kanjiaDialog .content .bargainPrice {color: #ca2428;font-size: 14px;}
.kanjiaDialog .content .bargainText {font-size: 12px;margin: 4rpx 0 12rpx;}
.kanjiaDialog .content .bargainBtn {background: #f60;margin: 0 20rpx;color: #fff;padding: 10rpx 0;margin-top: 20rpx;height: 80rpx;line-height: 60rpx;border-radius: 0;}
.kanjiaDialog .content .bargainBtn::after {border: 0;}
</style>