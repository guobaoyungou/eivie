<template>
<view>
	<block v-if="isload">

		<view class="content" v-if="datalist && datalist.length>0">
			<view class="label">
				<text class="t1">核销员列表（共{{count}}人）</text>
			</view>
			<block v-for="(item, index) in datalist" :key="index">
				<view class="item">
					<view class="f1">
						<image :src="item.headimg"></image>
						<view class="t2">
								{{item.nickname}}
						</view>
					</view>
					<view class="f2">
						已核销订单:<text style="color: red;">{{item.hxnum}}</text>
					</view>
					<view class="f3 btn2" @tap="delOrder" :data-id="item.id">删除</view>
				</view>
			</block>
		</view>
		<nomore v-if="nomore"></nomore>
		<nodata v-if="nodata"></nodata>
		
		<view class="btn-add" :style="{background:t('color1')}" @tap="goto" :data-url="'addhexiao?mdid='+mdid">添加核销员</view>
	</block>
	<popmsg ref="popmsg"></popmsg>
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
			pre_url:app.globalData.pre_url,

      datalist: [],
      pagenum: 1,
      nomore: false,
			nodata:false,
      count: 0,
      keyword: '',
      auth_data: {},
			dkopen:false,
    };
  },
  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.getdata();
		if(opt.type) this.dkopen = true;
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
      var keyword = that.keyword;
			that.nodata = false;
			that.nomore = false;
			that.loading = true;
      app.post('ApiMendianCenter/hexiaouser', {keyword: keyword,pagenum: pagenum}, function (res) {
        that.loading = false;
        var data = res.datalist;
        if (pagenum == 1) {
					that.datalist = data;
					that.count = res.count;
					that.mdid = res.mdid;
          if (data.length == 0) {
            that.nodata = true;
          }
					uni.setNavigationBarTitle({
						title: '核销员列表'
					});
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
		delOrder: function (e) {
			var that = this;
			var id = e.currentTarget.dataset.id

			app.confirm('确定要删除该核销员吗?', function () {
				app.post('ApiMendianCenter/del', {id:id}, function (data) {
					app.showLoading(false);
					app.success(data.msg);
					setTimeout(function () {
						that.getdata();
					}, 1000)
				});
			})
		},
  }
};
</script>
<style>
.topsearch{width:94%;margin:16rpx 3%;}
.topsearch .f1{height:60rpx;border-radius:30rpx;border:0;background-color:#fff;flex:1}
.topsearch .f1 .img{width:24rpx;height:24rpx;margin-left:10px}
.topsearch .f1 input{height:100%;flex:1;padding:0 20rpx;font-size:28rpx;color:#333;}

.content{width: 94%;margin:0 3%;background: #fff;border-radius:16rpx}
.content .label{display:flex;width: 100%;padding:24rpx 16rpx;color: #333;}
.content .label .t1{flex:1}
.content .label .t2{ width:300rpx;text-align:right}

.content .item{width: 100%;padding: 32rpx;border-top: 1px #e5e5e5 solid;min-height: 112rpx;display:flex;align-items:center;justify-content: space-between;}
.content .item image{width:90rpx;height:90rpx;}
.content .item .f1{display:flex; }
.content .item .f1 .t2{display:flex;padding-left:20rpx}
.content .item .f1 .t2 .x1{color: #222;font-size:30rpx;}
.content .item .f1 .t2 .x2{color: #999;font-size:24rpx}

.content .item .f2{display:flex;width:auto;text-align:center;}
.content .item .f2 .t1{ font-size: 40rpx;color: #666;height: 40rpx;line-height: 40rpx;}
.content .item .f2 .t2{ font-size: 28rpx;color: #999;height: 50rpx;line-height: 50rpx;}
.content .item .btn{ border-radius:8rpx; padding:3rpx 12rpx;margin-left: 10px;border: 1px #999 solid; text-align:center; font-size:28rpx;color:#333;}
.content .item .btn:nth-child(n+2) {margin-top: 10rpx;}
.btn-add{width:90%;max-width:700px;margin:0 auto;height:96rpx;line-height:96rpx;text-align:center;color:#fff;font-size:30rpx;font-weight:bold;border-radius:40rpx;position: fixed;left:0px;right:0;bottom:20rpx;}
.btn2{margin-left:20rpx;width:80rpx;height:60rpx;line-height:60rpx;color:#333;background:#fff;border:1px solid #cdcdcd;border-radius:3px;text-align:center}
</style>