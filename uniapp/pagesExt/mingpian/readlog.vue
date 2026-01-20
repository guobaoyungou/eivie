<template>
<view class="container">
	<block v-if="isload">
		<view class="recordList">
			<view v-for="(item, index) in datalist" :key="index" class="ListItem">
				<image class="firendAvatar" :src="item.headimg"></image>
				<view class="info">
					<text class="styleText">{{item.nickname}}</text>
					<text>于 {{dateFormat(item.createtime,'Y-m-d H:i')}} 查看了我的{{mingpiantext}}</text>
          <view v-if="item.applysee" style="display: flex;justify-content: space-between;align-items: center;margin-top: 20rpx;">
            <view>申请查看更多内容</view>
            <view style="display: flex;width: 230rpx;justify-content: space-between;align-items: center;">
              <view v-if="item.applysee == -1">已驳回</view>
              <view v-if="item.applysee == 2" style="color: green;">已通过</view>
              <view v-if="item.applysee == 1" class="checkaddfields" @tap="goto" :data-url="'/pagesB/mingpian/checkapplylog?id='+item.id">去审核</view>
              <view v-if="item.applysee == 2" class="checkaddfields" @tap="goto" :data-url="'/pagesB/mingpian/checkapplylog?id='+item.id">去编辑</view>
            </view>
          </view>
				</view>
			</view>
		</view>
		<nomore v-if="nomore"></nomore>
		<nodata v-if="nodata"></nodata>
	</block>
	<loading v-if="loading"></loading>
	<dp-tabbar :opt="opt"></dp-tabbar>
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
			
			isload:true,
      pagenum: 1,
      st: '',
      datalist: [],
      nomore: false,
			nodata:false,
      mingpiantext:'名片',
    };
  },

  onLoad: function (opt) {
		this.opt = app.getopts(opt);
  },
  onShow:function(){
    this.getdata();
  },
	onPullDownRefresh: function () {
		this.getdata(true);
	},
  onReachBottom: function () {
    if (!this.nodata && !this.nomore) {
      this.pagenum = this.pagenum + 1;
      this.getdata(true);
    }
  },
  methods: {
    changetab: function (e) {
      var st = e.currentTarget.dataset.st;
      this.st = st;
      uni.pageScrollTo({
        scrollTop: 0,
        duration: 0
      });
      this.getdata();
    },
    getdata: function (loadmore) {
			if(!loadmore){
				this.pagenum = 1;
				this.datalist = [];
			}
      var that = this;
      var st = that.st;
      var pagenum = that.pagenum;
			that.loading = true;
			that.nomore = false;
			that.nodata = false;
      app.post('ApiMingpian/readlog', {pagenum: pagenum,id: that.opt.id}, function (res) {
				that.loading = false;
        that.mingpiantext = that.t('名片');
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
    }
  }
};
</script>
<style>
page {
background: #fff;}
.recordList .ListItem {
margin: 0 40rpx;
font-size:26rpx;
border-bottom: 1px solid #eee;
padding: 40rpx 0;display:flex;alitn-items:center
}


.ListItem .firendAvatar {
float: left;
width:80rpx;
height:80rpx;
border-radius: 50%;
margin-right: 20rpx;
}


.ListItem .styleText {
color: #dbb243;
font-size:28rpx;}
.ListItem .info{display:flex;flex-direction:column;}

.checkaddfields{text-align: center;line-height: 70rpx;width: 140rpx;border: 2rpx solid #f1f1f1;border-radius: 6rpx;margin: 0 auto;}
</style>