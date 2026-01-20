<template>
<view class="container" style="min-height:100vh" :style="{background:info.color1}">
	<block v-if="isload">
		<view class="banner"><image :src="info.banner" mode="widthFix"></image></view>
		<view class="box1">
			<view class="item"><view class="t1" :style="{color:info.color2}">{{info.joinnum}}</view><view class="t2">参与人数</view></view>
			<view class="item"><view class="t1" :style="{color:info.color2}">{{info.helpnum}}</view><view class="t2">累计投票</view></view>
			<view class="item"><view class="t1" :style="{color:info.color2}">{{info.readcount}}</view><view class="t2">访问次数</view></view>
		</view>
		<view class="box2">
			<view class="title"><text style="color:#999;font-weight:normal;padding-right:20rpx"> —— </text> 活动规则 <text style="color:#999;font-weight:normal;padding-left:20rpx"> —— </text></view>
			<parse :content="info.guize" />
		</view>
		<view style="width:100%;height:60rpx"></view>

		
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
			aid:app.globalData.aid,
			session_id:app.globalData.session_id,
			captcha:'',
			randt:'',
			nowjoinid:0,
			smscode:'',
      smsdjs: '',
			smstel:'',
      hqing: 0,
			
      datalist: [],
      pagenum: 1,
      nomore: false,
      nodata: false,
			
      info: {},
			nowtime:0,
      djsday: '00',
      djshour: '00',
      djsmin: '00',
      djssec: '00',
			keyword:'',
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
		return;
    if (!this.nodata && !this.nomore) {
      this.pagenum = this.pagenum + 1;
      this.getdata(true);
    }
  },
  onUnload: function (loadmore) {
    clearInterval(interval);
  },
  onShareAppMessage: function () {
		var that = this;
		var title = that.info.sharetitle ? that.info.sharetitle : that.info.name;
		var sharepic = that.info.sharepic ? that.info.sharepic : that.info.banner;
		var sharelink = that.info.sharelink ? that.info.sharelink : '';
		var sharedesc = that.info.sharedesc ? that.info.sharedesc : '';
		return this._sharewx({title:title,desc:sharedesc,link:sharelink,pic:sharepic});
  },
	onShareTimeline:function(){
		var that = this;
		var title = that.info.sharetitle ? that.info.sharetitle : that.info.name;
		var sharepic = that.info.sharepic ? that.info.sharepic : that.info.banner;
		var sharelink = that.info.sharelink ? that.info.sharelink : '';
		var sharedesc = that.info.sharedesc ? that.info.sharedesc : '';
		var sharewxdata = this._sharewx({title:title,desc:sharedesc,link:sharelink,pic:sharepic});
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
			that.loading = true;
			that.nodata = false;
			that.nomore = false;
      app.post('ApiToupiao/index', {id:that.opt.id,pagenum:pagenum,keyword:that.keyword}, function (res) {
				that.loading = false;

				if (pagenum == 1) {
					that.datalist = res.datalist;
          if ((that.datalist).length == 0) {
            that.nodata = true;
          }
					that.info = res.info;
					that.nowtime = res.nowtime;
					uni.setNavigationBarTitle({
						title: that.info.name
					});
					var title = that.info.sharetitle ? that.info.sharetitle : that.info.name;
					var sharepic = that.info.sharepic ? that.info.sharepic : that.info.banner;
					var sharelink = that.info.sharelink ? that.info.sharelink : '';
					var sharedesc = that.info.sharedesc ? that.info.sharedesc : '';
					that.loaded({title:title,desc:sharedesc,link:sharelink,pic:sharepic});

					clearInterval(interval);
					interval = setInterval(function () {
						that.nowtime = that.nowtime + 1;
						that.getdjs();
					}, 1000);
					
					that.loaded();
        }else{
          if ((res.datalist).length == 0) {
            that.nomore = true;
          } else {
            var datalist = that.datalist;
            var newdata = datalist.concat(res.datalist);
            that.datalist = newdata;
          }
        }
      });
    },
    getdjs: function () {
      var that = this;
      if (that.info.starttime * 1 > that.nowtime * 1) {
        var totalsec = that.info.starttime * 1 - that.nowtime * 1;
      } else {
        var totalsec = that.info.endtime * 1 - that.nowtime * 1;
      }
      if (totalsec <= 0) {
        that.djsday = '00';
        that.djshour = '00';
        that.djsmin = '00';
        that.djssec = '00';
      } else {
        var date = Math.floor(totalsec / 86400);
        var houer = Math.floor((totalsec - date * 86400) / 3600);
        var min = Math.floor((totalsec - date * 86400 - houer * 3600) / 60);
        var sec = totalsec - date * 86400 - houer * 3600 - min * 60;
        var djsday = (date < 10 ? '0' : '') + date;
        var djshour = (houer < 10 ? '0' : '') + houer;
        var djsmin = (min < 10 ? '0' : '') + min;
        var djssec = (sec < 10 ? '0' : '') + sec;
        that.djsday = djsday;
        that.djshour = djshour;
        that.djsmin = djsmin;
        that.djssec = djssec;
      }
    },
  }
}
</script>
<style>

.banner{width:100%;}
.banner image{width:100%;height:auto}

.box1{width:94%;margin-left:3%;border-radius:12rpx;background:#fff;padding:60rpx 10rpx;display:flex;align-items:center;position:relative;z-index:12;margin-top:-160rpx}
.box1 .item{flex:1;display:flex;flex-direction:column;align-items:center;}
.box1 .item .t1{font-size:48rpx;font-weight:bold}
.box1 .item .t2{font-size:24rpx;color:#778899;margin-top:10rpx}
.box2{width:94%;margin-left:3%;border-radius:12rpx;background:#fff;padding:20rpx 20rpx;display:flex;flex-direction:column;align-items:center;margin-top:20rpx}
.box2 .title{width:100%;text-align:center;font-size:32rpx;color:#222222;font-weight:bold;height:100rpx;line-height:100rpx;margin-bottom:20rpx}


</style>