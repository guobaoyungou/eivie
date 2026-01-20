<template>
<view class="container">
	<block v-if="isload">
		<view class="content" v-if="datalist && datalist.length>0">
			<view class="label">
				<text class="t1">报名列表</text>
			</view>
			<block v-for="(item, index) in datalist" :key="index">
				<view class="item">
					<view class="f1">
						<image :src="item.headimg"></image>
						<view class="t2">
							<text class="x1">{{item.nickname}}</text>
							<text class="x2">{{dateFormat(item.createtime,'Y-m-d H:i')}}</text>
							<view  v-for="items in item.formdata" :key="index">
								<text class="x1" style="margin-right:5px;">{{items[0]}}:</text>
								<view class="x1" v-if="items[2]=='upload'"><image :src="items[1]" style="width:400rpx;height:auto" mode="widthFix" @tap="previewImage" :data-url="items[1]"/></view>
								<text class="x1" v-else user-select="true" selectable="true">{{items[1]}}</text>
							</view>

						</view>
					</view>
				</view>
			</block>
		</view>
		
	</block>
	<nodata v-if="nodata"></nodata>
	<nomore v-if="nomore"></nomore>
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

      datalist: [],
      pagenum: 1,

			nodata: false,
			nomore: false,
			dialogShow: false,
			proid:''
    };
  },

  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.proid = this.opt.proid
		this.getdata();
  },
	onPullDownRefresh: function () {
		this.getdata();
	},
  onReachBottom: function () {
    if (!this.nodata && !this.nomore) {
      this.pagenum = this.pagenum + 1
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
      var proid = that.proid;
      var pagenum = that.pagenum;
			that.loading = true;
			that.nodata = false;
      that.nomore = false;
      app.post('ApiHuodongBaoming/proorderlist', {pagenum: pagenum,proid:proid}, function (res) {
				that.loading = false;
        var data = res.datalist;
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
    },

  }
};
</script>
<style>
.content{width:94%;margin:0 3%;border-radius:16rpx;background: #fff;margin-top: 20rpx;}
.content .label{display:flex;width: 100%;padding: 16rpx;color: #333;text-align: center;}
.content .label .t1{flex:1}

.content .item{width: 100%;padding: 32rpx;border-top: 1px #eaeaea solid;min-height: 112rpx;display:flex;align-items:center;}
.content .item image{width: 90rpx;height: 90rpx;border-radius:50%;}
.content .item .f1{display:flex;flex:1;align-items:center;}
.content .item .f1 .t2{display:flex;flex-direction:column;padding-left:20rpx}
.content .item .f1 .t2 .x1{color: #333;font-size:26rpx;}
.content .item .f1 .t2 .x2{color: #999;font-size:24rpx;}
</style>