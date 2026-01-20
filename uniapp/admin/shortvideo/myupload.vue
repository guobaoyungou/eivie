<template>
<view>
	<dd-search :isfixed="true" @getdata="getdata"></dd-search>

	<view class="container" id="datalist">
	<block v-for="(item, index) in datalist" :key="index">
		<view class="order-box">
			<view class="content" style="border-bottom:none">
				<view @tap="goto" :data-url="'/activity/shortvideo/detail?id=' + item.id">
					<image :src="item.coverimg"></image>
				</view>
				<view class="detail">
					<text class="t1">{{item.name}}</text>
					<view class="t2">播放量 {{item.view_num}}<text style="color:#a88;padding-left:20rpx">点赞数 {{item.zan_num}}</text></view>
					<text class="t3" v-if="item.status==2">驳回原因：{{item.reason}}</text>
				</view>
			</view>
			<view class="op">
				<text style="color:orange" class="flex1" v-if="item.status==0">待审核</text>
				<text style="color:red" class="flex1" v-if="item.status==2">未通过</text>
				<text style="color:green" class="flex1" v-if="item.status==1">已通过</text>
				<view class="btn2" @tap="todel" :data-id="item.id">删除</view>
			</view>
		</view>
	</block>
	</view>
	<nomore v-if="nomore"></nomore>
	<nodata v-if="nodata"></nodata>
	
	<view style="height:140rpx"></view>
	<view class="btn-add" :class="menuindex>-1?'tabbarbot':'notabbarbot3'" :style="'background:linear-gradient(90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'" @tap="goto" data-url="uploadvideo"><image :src="pre_url+'/static/img/add.png'" style="width:28rpx;height:28rpx;margin-right:6rpx"/>发布短视频</view>

	<popmsg ref="popmsg"></popmsg>
</view>
</template>

<script>
var app = getApp();

export default {
  data() {
    return {
      st: 'all',
      datalist: [],
      pagenum: 1,
      nomore: false,
      count0: 0,
      count1: 0,
      countall: 0,
      sclist: "",
			keyword: '',
      nodata: false,
      pre_url:app.globalData.pre_url,
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
    changetab: function (st) {
      var that = this;
      that.st = st;
      that.getdata();
    },
    getdata: function (loadmore,keyword) {
     if(!loadmore){
				this.pagenum = 1;
				this.datalist = [];
			}
      var that = this;
      var pagenum = that.pagenum;
      var st = that.st;
			if(typeof keyword =='undefined') {
				keyword = that.keyword;
			} else {
				that.keyword = keyword;
			}
			that.nodata = false;
			that.nomore = false;
			that.loading = true;
      app.post('ApiAdminShortvideo/myupload', {keyword:keyword,pagenum: pagenum,st: that.st}, function (res) {
        that.loading = false;
        var data = res.datalist;
        if (pagenum == 1){
					that.countall = res.countall;
					that.count0 = res.count0;
					that.count1 = res.count1;
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
    todel: function (e) {
      var that = this;
      var id = e.currentTarget.dataset.id;
      app.confirm('确定要删除该短视频吗?', function () {
        app.post('ApiAdminShortvideo/myuploaddel', {id: id}, function (res) {
          if (res.status == 1) {
            app.success(res.msg);
            that.getdata();
          } else {
            app.error(res.msg);
          }
        });
      });
    },
    setst: function (e) {
      var that = this;
      var id = e.currentTarget.dataset.id;
      var st = e.currentTarget.dataset.st;
      app.confirm('确定要' + (st == 0 ? '下架' : '上架') + '吗?', function () {
        app.post('ApiAdminShortvideo/setst', {st: st,id: id}, function (res) {
          if (res.status == 1) {
            app.success(res.msg);
            that.getdata();
          } else {
            app.error(res.msg);
          }
        });
      });
    }
  }
};
</script>
<style>
.container{ width:100%;margin-top:106rpx}
.order-box{ width: 94%;margin:0 3%;padding:3px 3%; background: #fff; margin-bottom:12rpx;border-radius:16rpx}

.order-box .content{display:flex;width: 100%; padding:16rpx 0px;border-bottom: 1px #e5e5e5 dashed;position:relative}
.order-box .content:last-child{ border-bottom: 0; }
.order-box .content image{ width: 140rpx; height: 140rpx;}
.order-box .content .detail{display:flex;flex-direction:column;margin-left:14rpx;flex:1}
.order-box .content .detail .t1{font-size:26rpx;min-height:50rpx;line-height:36rpx;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:2;overflow:hidden;}
.order-box .content .detail .t2{height:36rpx;line-height:36rpx;color: #999;overflow: hidden;font-size: 24rpx;}
.order-box .content .detail .t3{display:flex;height: 36rpx;line-height: 36rpx;color: #ff4246;font-size: 24rpx;}
.order-box .content .detail .x1{ font-size:30rpx;margin-right:5px}
.order-box .content .detail .x2{ font-size:24rpx;text-decoration:line-through;color:#999}

.order-box .bottom{ width:100%; padding:10rpx 0px; border-top: 1px #e5e5e5 solid; color: #555;}
.order-box .op{ display:flex;align-items:center;width:100%; padding:10rpx 0px; border-top: 1px #e5e5e5 solid; color: #555;}
.btn1{margin-left:20rpx;width:120rpx;height:60rpx;line-height:60rpx;color:#fff;border-radius:3px;text-align:center}
.btn2{margin-left:20rpx;width:120rpx;height:60rpx;line-height:60rpx;color:#333;background:#fff;border:1px solid #cdcdcd;border-radius:3px;text-align:center}

.btn-add{width:90%;max-width:700px;margin:0 auto;height:96rpx;line-height:96rpx;text-align:center;color:#fff;font-size:30rpx;font-weight:bold;border-radius:40rpx;position: fixed;left:0px;right:0;bottom:0;margin-bottom:20rpx;}
</style>