<template>
<view class="container" v-if="isload">
	<view class="itemlist">
		<block v-for="(item,index) in datalist" :key="index">
			<view v-if="item.tablename=='zhaopin_qiuzhi'" class="item flex-s" @tap="goto" :data-url="'/zhaopin/qiuzhi/chat?bid='+item.bid+'&mid='+item.mid+'&id='+item.tableid+'&tbtype='+(item.isreply==1?2:1)+'&zid='+item.zid">
				<view class="headimg"><image :src="item.headimg"></image></view>
				<view class="info">
					<view class="title">{{item.nickname}}<text v-if="item.title" class="tips">-{{item.title}}</text></view>
					<view class="content">{{item.content}}</view>
				</view>
			</view>
			<view v-if="item.tablename=='zhaopin'" class="item flex-s" @tap="goto" :data-url="'/zhaopin/zhaopin/chat?bid='+item.bid+'&mid='+item.mid+'&id='+item.tableid+'&tbtype='+(item.isreply==1?2:1)+'&zid='+item.zid">
				<view class="headimg"><image :src="item.headimg"></image></view>
				<view class="info">
					<view class="title">{{item.nickname}}<text v-if="item.title" class="tips">-{{item.title}}</text></view>
					<view class="content">{{item.content}}</view>
				</view>
			</view>
		</block>
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

			nomore:false,
			nodata:false,
      keyword: '',
      pagenum: 1,
      datalist: [],
      st:0,
			type:2
    };
  },
  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.bid = this.opt.bid ? this.opt.bid : 0;
		this.st = this.opt.st ? this.opt.st : 0;
		this.type = this.opt.type ? this.opt.type : 2;
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
    getlist: function (loadmore) {
      var that = this;
			if(!loadmore){
				this.pagenum = 1;
				this.datalist = [];
			}
      var pagenum = that.pagenum;
      var keyword = that.keyword;
      app.post('ApiZhaopin/getChatListMy',{
				pagenum: pagenum,
				keyword:that.keyword,
				st:that.st,
				type:that.type
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
    }
		
  }
};
</script>
<style>
	@import "../common.css";
	page{background: #FFFFFF;}
	.itemlist{padding: 20rpx; }
	.itemlist .item{padding: 10rpx 0;}
	.itemlist .item .headimg image{width: 90rpx;height: 90rpx;border-radius: 16rpx;}
	.itemlist .item .info{padding-left: 20rpx;color: #9a9a9a;}
	.itemlist .item .title{font-size: 32rpx;font-weight: bold;color: #222222;}
	.itemlist .item .tips{font-size: 28rpx;color: #666666;font-weight: normal;}
</style>