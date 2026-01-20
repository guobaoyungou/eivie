<template>
    <view style="width: 100%;height: 100%;background-color: #fff;">
        <block v-if="isload">
            <view style="width: 700rpx;margin: 0 auto;">
                <view class="topsearch flex-y-center" @tap="goto" data-url="list?cpid=0&displaytype=0">
                    <view class="f1 flex-y-center">
                        <image class="img" :src="pre_url+'/static/img/search_ico.png'"></image>
                        <input :value="keyword" placeholder="搜索感兴趣的帖子" placeholder-style="font-size:24rpx;color:#C2C2C2" @confirm="searchConfirm"></input>
                    </view>
                </view>
                <view v-if="clist && clist.length>0" class="list_view">
                    <block v-for="(item, index) in clist" :key="index">
                        <view @tap="goto" :data-url="'list?cpid=' + item.id+'&displaytype='+ item.display_type" class="list_content" :style="index%2==0?'margin-right:40rpx':''">
                            <view style="width: 330rpx;height: 330rpx;border-radius: 8rpx;overflow: hidden;">
                                <image :src="item.pic" mode="widthFix" style="width: 330rpx;height: 330rpx;"></image>
                            </view>
                            <view style="line-height: 60rpx;font-size: 28rpx;">{{item.name}}</view>
                        </view>
                    </block>
                    <nodata v-if="nodata"></nodata>
                </view>
                <nomore v-if="nomore"></nomore>
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
        pagenum: 1,
        nomore: false,
        nodata:false,
        
        clist:[],
        keyword: '',
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
      this.getdata(true);
    }
  },
  methods: {
    getdata: function (loadmore) {
        if(!loadmore){
            this.pagenum = 1;
            this.clist = [];
        }
        var that = this;
        var pagenum = that.pagenum;
        var keyword = that.keyword;
        that.nodata = false;
        that.nomore = false;
        that.loading = true;
        app.post('ApiLuntan/class', {pagenum: pagenum}, function (res) {
            that.loading = false;
            var data = res.data;
            if (pagenum == 1) {
                uni.setNavigationBarTitle({
                    title: res.title
                });
                that.clist = data;
                if (data.length == 0) {
                    that.nodata = true;
                }
                that.loaded();
            }else{
                if (data.length == 0) {
                    that.nomore = true;
                } else {
                    var clist = that.clist;
                    var newdata = clist.concat(data);
                    that.clist = newdata;
                }
            }
        });
    },
  }
};
</script>

<style>
    page{width: 100%;height: auto;min-height:100%;background-color: #fff;}
    .topsearch{width:100%;padding:20rpx 0rpx;margin-bottom:10rpx;margin-bottom:10rpx;background:#fff}
    .topsearch .f1{height:70rpx;border-radius:35rpx;border:0;background-color:#f5f5f5;flex:1}
    .topsearch .f1 image{width:30rpx;height:30rpx;margin-left:10px}
    .topsearch .f1 input{height:100%;flex:1;padding:0 20rpx;font-size:28rpx;color:#333;}
    .list_view{overflow: hidden;}
    .list_content{width: 330rpx;float: left;margin-top: 40rpx;}
</style>