<template>
<view class="container">
	<block v-if="isload">
		<view class="search-container">
			<view class="topsearch flex-y-center">
				<view class="f1 flex-y-center">
					<image class="img" :src="pre_url+'/static/img/search_ico.png'"></image>
					<input :value="keyword" placeholder="搜索感兴趣的视频" placeholder-style="font-size:24rpx;color:#C2C2C2" @confirm="searchConfirm"></input>
				</view>
			</view>
			<dd-tab :itemdata="cnamelist" :itemst="cidlist" :st="st" :isfixed="false" @changetab="changetab" v-if="clist.length>0"></dd-tab>
		</view>
		<view class="content" :style="clist.length>0?'margin-top:190rpx':'margin-top:100rpx'">
			<block v-if="sysset.list_type == 1">
				<view v-for="(item, index) in datalist" :key="index" class="item2" @tap="goto"  :data-url="'detail?id=' + item.id +'&cid='+st">
					<view class="f1"><image class="image" :src="item.coverimg" mode="widthFix"/></view>
					<view class="f2">
						<view class="t1">{{item.name}}</view>
						<view class="t2">{{item.description}}</view>
						<view class="t3">播放量 {{item.view_num}} <text style="padding:0 20rpx">·</text> 点赞数 {{item.zan_num}}</view>
						<view class="t4"><image class="touxiang" :src="item.binfo.logo"/>{{item.binfo.name}}</view>
					</view>
				</view>
			</block>
			<block v-else>
				<view v-for="(item, index) in datalist" :key="index" class="item" :style="index%2==0?'margin-right:2%':''">
					<image class="ff" mode="widthFix" :src="item.coverimg" @tap="goto"  :data-url="'detail?id=' + item.id +'&cid='+st"></image>
					<view class="f2">
						<view class="t1"><image class="touxiang" :src="item.binfo.logo"/></view>
						<view class="t2"><image class="tubiao" :src="pre_url+'/static/img/shortvideo_playnum.png'"/>{{item.view_num}}</view>
						<view class="t3"><image class="tubiao" :src="pre_url+'/static/img/shortvideo_likenum.png'"/>{{item.zan_num}}</view>
					</view>
				</view>
			</block>
		</view>
		<nomore v-if="nomore"></nomore>
		<view class="item" style="display:block" v-if="nodata"><nodata></nodata></view>

		<view v-if="sysset.can_upload==1" class="covermy" :class="menuindex>-1?'tabbarbot':'notabbarbot'" @tap="goto" :data-url="'uploadvideo?bid='+bid"><image :src="pre_url+'/static/img/shortvideo_uploadbtn.png'"></image></view>
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
			
			bid:'',
      st: 'all',
			keyword:'',
      pagenum: 1,
			clist: [],
			binfo:{},
			cnamelist:[],
			cidlist:[],
      datalist: [],
      cid: 0,
      nomore: false,
			nodata:false,
			sysset:{},
    };
  },

  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.st = this.opt.st || 'all';
		this.bid = this.opt.bid || '';
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
  methods: {
		getdata: function (loadmore) {
			if(!loadmore){
				this.pagenum = 1;
				this.datalist = [];
			}
      var that = this;
      var pagenum = that.pagenum;
      var st = that.st;
			var keyword = that.keyword;
			that.nodata = false;
			that.nomore = false;
			that.loading = true;
      app.post('ApiShortvideo/index', {bid:that.bid,cid: st,pagenum: pagenum,keyword:keyword}, function (res) {
				that.loading = false;
        var data = res.datalist;
        if (pagenum == 1) {
					that.clist = res.clist
					that.sysset = res.sysset
					if((res.clist).length > 0){
						var cnamelist = [];
						var cidlist = [];
						cnamelist.push('全部');
						cidlist.push('all');
						for(var i in that.clist){
							cnamelist.push(that.clist[i].name);
							cidlist.push(that.clist[i].id);
						}
						that.cnamelist = cnamelist;
						that.cidlist = cidlist;
					}
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
    changetab: function (st) {
      this.st = st;
      uni.pageScrollTo({
        scrollTop: 0,
        duration: 0
      });
      this.getdata();
    },
    searchConfirm: function (e) {
      var that = this;
      var keyword = e.detail.value;
      that.keyword = keyword
      that.getdata();
    },
  }
};
</script>
<style>
page{background:#fff}
.search-container {position: fixed;width: 100%;background: #fff;z-index:9;top:var(--window-top)}
.topsearch{width:100%;padding:20rpx 20rpx 10rpx 20rpx;}
.topsearch .f1{height:60rpx;border-radius:30rpx;border:0;background-color:#f7f7f7;flex:1}
.topsearch .f1 .img{width:24rpx;height:24rpx;margin-left:10px}
.topsearch .f1 input{height:100%;flex:1;padding:0 20rpx;font-size:28rpx;color:#333;}

.navbg{text-align: center;height: 92rpx;line-height: 92rpx;font-size: 40rpx;color: #222; border-bottom: #eee 1rpx solid; background-color: #fff;}
.navbg:after {content: '';width: 160%;height:300rpx;position: absolute;left: -30%;top:0;border-radius: 0 0 50% 50%;z-index:1;}
.nav {width: 100%;position:fixed;z-index:10; top: 0; background-color: #fff;}
.nav>scroll-view {overflow: visible !important;padding-top:20rpx;padding-bottom:20rpx}
.nav .f1 {flex-grow: 0;flex-shrink: 0;display:flex;align-items:center;color:#222;position:relative;z-index:2}
.nav .f1 .item{flex-grow: 0;flex-shrink: 0;width:25%;text-align:center;padding:16rpx 0;opacity: 0.6;}
.nav .f1 .item .t1 {font-size:34rpx;font-weight:bold}
.nav .f1 .item .t2 {font-size:24rpx}
.nav .f1 .item.active {position: relative;color:#C68924;opacity:1;background-color: #FAF0E6; border-radius:20rpx ;}

.content{width:96%;margin-left:2%;position:relative;margin-top:100rpx; display:flex;flex-wrap:wrap}
.content .item{width:49%;height:500rpx;background:#fff;overflow:hidden;border-radius:8rpx;margin-bottom:20rpx;position:relative;background:#666}
.content .item .ff{width:100%;height:100%;display:block;}
.content .item .f2{position: absolute;bottom:20rpx;left:20rpx;display:flex;align-items:center;color:#fff;font-size:22rpx}
.content .item .f2 .t1{display:flex;align-items:center;text-shadow: 0px 6px 12px rgba(0, 0, 0, 0.12);}
.content .item .f2 .t2{display:flex;align-items:center;margin-left:30rpx;text-shadow: 0px 6px 12px rgba(0, 0, 0, 0.12);}
.content .item .f2 .t3{display:flex;align-items:center;margin-left:30rpx;text-shadow: 0px 6px 12px rgba(0, 0, 0, 0.12);}
.content .item .f2 .tubiao{display:block;height:28rpx;width:28rpx;margin-right:10rpx}
.content .item .f2 .touxiang{display:block;width:40rpx;height:40rpx;border-radius:50%;}

.content .item2{width:100%;background:#fff;display:flex;padding:20rpx 0;border-bottom:1px solid #f5f5f5}
.content .item2 .f1 {width:30%;height:0;overflow:hidden;background: #ffffff;padding-bottom:40%;position: relative;border-radius:4rpx;background:#999}
.content .item2 .f1 .image{position:absolute;top:0;left:0;width: 100%;height:auto}
.content .item2 .f2 {width: 70%;padding:6rpx 10rpx 5rpx 20rpx;position: relative;}
.content .item2 .f2 .t1 {color:#222222;font-weight:bold;font-size:30rpx;line-height:40rpx;margin-bottom:10rpx;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:2;overflow:hidden;}
.content .item2 .f2 .t2 {color:#666;font-size:26rpx;line-height:36rpx;margin-bottom:10rpx;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:2;overflow:hidden;}
.content .item2 .f2 .t3{color:#222;font-size:22rpx;color:#9C9C9C;margin-top:20rpx;}
.content .item2 .f2 .t4{display:flex;align-items:center;color:#515254;font-size:24rpx;position:absolute;bottom:10rpx;}
.content .item2 .f2 .t4 .touxiang{display:block;width:30rpx;height:30rpx;border-radius:50%;margin-right:10rpx;}

.covermy{position:fixed;z-index:99999;bottom:0;right:0;width:150rpx;height:150rpx;box-sizing:content-box}
.covermy image{width:100%;height:100%}
</style>