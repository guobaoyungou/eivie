<template>
<view style="width:100%" v-if="isload">
	<view class="search-container">
		<dd-tab :itemdata="['全部','在线','已下架']" :itemst="['0','1','3']" :st="st" :isfixed="true" @changetab="changetab"></dd-tab>
	</view>
	<!-- <view class="topsearch flex-y-center">
		<view class="f1 flex-y-center">
			<image class="img" src="/static/img/search_ico.png"></image>
			<input :value="keyword" placeholder="输入关键字搜索" placeholder-style="font-size:24rpx;color:#C2C2C2" @confirm="searchConfirm"></input>
		</view>
	</view> -->
	<view class="qiuzhi-container">
		<view class="qiuzhi-itemlist">
			<view class="item" v-for="(item,index) in datalist" :key="item.id" @click="goto" :data-url="'/zhaopin/qiuzhi/detail?id='+item.id">
					<view class="item1 flex">	
							<view class="qiuzhi-pic">
								<text class="status" :class="item.top_feetype>0?'':'hide'">已置顶</text>
								<image class="image" :style="'filter: blur('+item.mohu+'px);-webkit-filter: blur('+item.mohu+'px);-moz-filter: blur('+item.mohu+'px)'"  :src="item.thumb" mode="aspectFit"/>
							</view>
							<view class="qiuzhi-info">
								<view class="p1">
								{{item.name}}
								<image v-if="item.sex==1" src="../../static/img/nan.png"></image>
								<image v-if="item.sex==2" src="../../static/img/nv.png"></image>
								</view>
								<view class="p2"><text>期望薪资：</text><text class="number">{{item.salary}}</text></view>
								<view class="p2">
									<text>期望岗位：</text>
									<text>{{item.cnames}}</text>
								</view>
								<view class="p2">
									<text>期望城市：</text>
									<text>{{item.area}}</text>
								</view>
							</view>
					</view>
					
					<view class="item2" v-if="item.top_feetype>0">
						置顶到期时间：{{item.top_endtime}}
					</view>
					<!-- <view class="item2 flex" v-if="item.tags && item.tags.length>0">
						<view class="tagitem" v-for="(wf,wk) in item.tags" :key="wk">{{wf}}</view>
					</view> -->
					<view class="option flex-e">
						<block v-if="item.status==1">
							<view class="btn" @tap.stop="offline" :data-id="item.id">下架</view>
							<view class="btn st1" @tap.stop="goto" :data-url="'top?id='+item.id">置顶</view>
						</block>
						<block v-if="item.status==0 || item.status==2 || item.status==3">
							<view class="btn st1" @tap.stop="del" :data-id="item.id">删除</view>
						</block>
					</view>
			</view>
		</view>
	</view>
	<view class="tosign" @tap="goto" data-url="qianyue" :style="{background:'linear-gradient(-90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)',color:'#ffffff'}">
		<view class="v">签约</view>
		<view class="v">技师</view>
	</view>
	<view class="tabbar">
		<view class="tabbar-bot"></view>
		<view class="tabbar-bar" style="background-color:#ffffff">
			<view class="tabbar-item">
				<!-- <view class="tabbar-image-box">
					<image class="tabbar-icon" :src="pre_url+'/static/img/peisong/order2.png'"></image>
				</view> -->
				<view class="tabbar-text active">我的求职</view>
			</view>
			<view @tap="goto" data-url="/zhaopin/qiuzhi/add" class="tabbar-item tabbar-item-add">
				<view class="tabbar-add-icon">
					<image class="tabbar-icon" :src="pre_url+'/static/img/shaitu_icon.png'"></image>
				</view>
			</view>
			<view @tap="goto" data-url="record"  class="tabbar-item">
				<!-- <view class="tabbar-image-box">
					<image class="tabbar-icon" :src="pre_url+'/static/img/peisong/orderwc.png'"></image>
				</view> -->
				<view class="tabbar-text">求职记录</view>
			</view>
		</view>
	</view>
	<nomore text="没有更多信息了" v-if="nomore"></nomore>
	<nodata text="没有查找到相关信息" v-if="nodata"></nodata>
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
			menuindex:-1,
			pre_url:app.globalData.pre_url,
			nomore:false,
			nodata:false,
      keyword: '',
      pagenum: 1,
      datalist: [],
      st:0
    };
  },
  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.bid = this.opt.bid ? this.opt.bid : 0;
		this.st = this.opt.st ? this.opt.st : 0;
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
			this.getlist();
			this.loaded();
		},
		searchConfirm:function(e){
			this.getlist(false);
			this.loaded();
		},
		changetab: function (st) {
		    this.pagenum = 1;
		    this.st = st;
				this.nodata = false;
				this.nomore = false;
		    this.datalist = [];
		    uni.pageScrollTo({
		      scrollTop: 0,
		      duration: 0
		    });
		    this.getlist(false);
		},
    getlist: function (loadmore) {
      var that = this;
			if(!loadmore){
				this.pagenum = 1;
				this.datalist = [];
			}
      var pagenum = that.pagenum;
      var keyword = that.keyword;
      app.post('ApiZhaopin/qiuzhiListMy',{
				pagenum: pagenum,
				keyword:that.keyword,
				st:that.st
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
		offline: function (e) {
		  var that = this;
		  var id = e.currentTarget.dataset.id;
		  app.confirm('确定下架该信息吗?', function () {
				app.showLoading('提交中');
		    app.post('ApiZhaopin/qiuzhiOffline', {id: id}, function (data) {
					app.showLoading(false);
		      app.success(data.msg);
		      setTimeout(function () {
		        that.getlist(false);
		      }, 1000);
		    });
		  });
		},
		del: function (e) {
		  var that = this;
		  var id = e.currentTarget.dataset.id;
		  app.confirm('确定删除该信息吗?', function () {
				app.showLoading('提交中');
		    app.post('ApiZhaopin/qiuzhiDel', {id: id}, function (data) {
					app.showLoading(false);
		      app.success(data.msg);
		      setTimeout(function () {
		        that.getlist(false);
		      }, 1000);
		    });
		  });
		},
  }
};
</script>
<style>
@import "../common.css";
.topsearch{width:94%;margin:16rpx 3%;margin-top: 100rpx;}
.topsearch .f1{height:60rpx;border-radius:30rpx;border:0;background-color:#fff;flex:1}
.topsearch .f1 .img{width:24rpx;height:24rpx;margin-left:10px}
.topsearch .f1 input{height:100%;flex:1;padding:0 20rpx;font-size:28rpx;color:#333;}
.qiuzhi-container {width: 100%;margin-top: 110rpx;font-size:26rpx;padding:0;}
.qiuzhi-itemlist{height: auto; position: relative;overflow: hidden; padding: 0px; }
.qiuzhi-itemlist .item{width:100%;display: inline-block;margin-bottom: 20rpx;background: #fff;padding: 20rpx;padding-top: 0;}
.qiuzhi-itemlist .item1{align-items: center;padding: 20rpx 0;}
.qiuzhi-itemlist .qiuzhi-pic {width: 160rpx;height:160rpx;overflow:hidden;background: #ffffff;position: relative;}
.qiuzhi-itemlist .qiuzhi-pic .image{max-width: 100%;max-height: 100%;border-radius:5px;vertical-align: middle;top: -34rpx;}
.qiuzhi-itemlist .qiuzhi-pic .image.mohu{filter: blur(10px);-webkit-filter: blur(10px);-moz-filter: blur(10px);}
.qiuzhi-itemlist .qiuzhi-pic .status{color: #FFFFFF;background:#b1b2b2;font-size: 24rpx;padding: 0 8rpx;position: relative;top: 0;z-index: 5;border-radius: 0 0 6rpx 0;}
.qiuzhi-itemlist .qiuzhi-pic .st1{background:#FF3A69;}
.qiuzhi-itemlist .qiuzhi-pic .st2{background:#3889f6;}
.qiuzhi-itemlist .qiuzhi-pic .status{color: #FFFFFF;background:#F05525;font-size: 20rpx;padding: 0 8rpx;position: relative;top: 0;z-index: 5;border-radius: 0 0 6rpx 0;}
.qiuzhi-itemlist .qiuzhi-pic .status.hide{opacity: 0;}
.qiuzhi-itemlist .qiuzhi-info {padding-left:20rpx;color: #999;font-size: 24rpx;}
.qiuzhi-itemlist .qiuzhi-info .p1 {color:#323232;font-weight:bold;font-size:28rpx;line-height:36rpx;margin-bottom:10rpx;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:2;overflow:hidden;}
.qiuzhi-itemlist .qiuzhi-info .p1 image{width: 30rpx;height:30rpx;vertical-align: text-bottom;}
.qiuzhi-itemlist .qiuzhi-info .number {color:#FF3A69;}
.qiuzhi-itemlist .qiuzhi-info .p2 {line-height: 40rpx;}

.qiuzhi-itemlist .item2{ border-top: 1rpx solid #f6f6f6;; padding-top: 14rpx; justify-content: flex-start; line-height: 36rpx; color: #6c6c6c; font-size: 24rpx;flex-wrap: wrap;}
.qiuzhi-itemlist .item2 .tagitem{background: #f4f7fe;text-align: center;padding: 2rpx 8rpx;margin-right: 8rpx;white-space: normal;}
.qiuzhi-itemlist .head  image{ width:42rpx ; height: 42rpx;  border-radius: 50%; vertical-align: middle; margin-right: 10rpx;}
.qiuzhi-itemlist .text2{ color:#FF3A69; width: 128rpx;height: 48rpx;border-radius: 24rpx 0px 0px 24rpx; text-align: center;background: linear-gradient(-90deg, rgba(255, 235, 240, 0.4) 0%, #FDE6EC 100%);}
.option .btn{min-width: 120rpx;text-align: center;border: 1rpx solid #e4e4e4;padding: 6rpx 10rpx;margin-left: 10rpx;color: #757575;font-size: 24rpx;}
.option .btn.st1{background: #F05525;color: #FFFFFF;border-color: #fcdacfdb;}

.tosign{width: 100rpx;height: 100rpx;background: #031028;color: #FFFFFF;position: fixed;bottom: 130rpx;right: 10rpx;display:flex;justify-content: center;align-items: center;
border-radius: 50%;flex-direction: column;text-align: center;font-size: 24rpx;}

</style>