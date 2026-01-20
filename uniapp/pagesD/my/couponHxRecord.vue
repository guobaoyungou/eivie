<template>
<view>
	<block v-if="isload">
		<view class="content" v-if="datalist && datalist.length>0">
			<view v-for="(item, index) in datalist" :key="index" class="item">
        <view class="f1">
        	{{item.createtime}}
        </view>
        <view class="f1">
        	{{item.un}}
        </view>
			</view>
		</view>
		<nodata v-if="nodata"></nodata>
		<nomore v-if="nomore"></nomore>
	</block>
	<loading v-if="loading"></loading>
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
			
			id:0,
      datalist: [],
      pagenum: 1,
      nodata: false,
      nomore: false,
      pre_url:app.globalData.pre_url,
    };
  },
  
  onLoad: function (opt) {
		this.opt = app.getopts(opt);
    this.id = this.opt.id || 0;
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
	onNavigationBarSearchInputConfirmed:function(e){
		this.searchConfirm({detail:{value:e.text}});
	},
  methods: {
    getdata: function (loadmore) {
			if(!loadmore){
				this.pagenum = 1;
				this.datalist = [];
			}
      var that = this;
      var pagenum = that.pagenum;
			that.nodata = false;
			that.nomore = false;
			that.loading = true;
      app.post('ApiMy/couponHexiaolog', {id:that.id,pagenum: pagenum}, function (res) {
				that.loading = false;
        var data = res.data;
        if (pagenum == 1){
					that.count = res.count;
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
.content{width: 94%;margin:0 3%;}
.content .label{display:flex;width: 100%;padding:24rpx 16rpx;color: #333;}
.content .label .t1{flex:1}
.content .label .t2{ width:300rpx;text-align:right}

.content .item{ width:100%;padding:20rpx 20rpx;border-top: 1px #f5f5f5 solid;background: #fff;border-radius:8rpx;display:flex;justify-content: space-between;margin-bottom: 20rpx;}
.content .item .f1{display:flex;flex-direction:column;margin-right:20rpx;}

</style>