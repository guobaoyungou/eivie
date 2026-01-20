<template>
<view>
	<block v-if="isload">

		<view class="content" v-if="datalist && datalist.length>0">
			<view class="label">
				<text class="t1">{{t('门店')}}列表（共{{count}}个）</text>
			</view>
			<block v-for="(item, index) in datalist" :key="index">
				<view class="item" @click="changeMendian(item.id)">
					<view class="f1">
						<image :src="item.pic"></image>
						<view class="t2" >
								<text>{{item.name}}</text>
								<text class="x2">{{item.address}}</text>
						</view>
					</view>
				</view>
			</block>
		</view>
		<nomore v-if="nomore"></nomore>
		<nodata v-if="nodata"></nodata>
		
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
			mdid:0
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
			that.nodata = false;
			that.nomore = false;
			that.loading = true;
      app.post('ApiMendianCenter/myMendianList', {pagenum: pagenum}, function (res) {
        that.loading = false;
        var data = res.datalist;
        if (pagenum == 1) {
					that.datalist = data;
					that.count = res.count;
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
		changeMendian: function (mdid) {
			var that = this;
			if(mdid){
				that.loading = true;
				app.post('ApiMendianCenter/changeMendian', {mdid: mdid}, function (res) {
				  that.loading = false;
				  if (res.status == 1) {
						console.log(11111111)
						that.mdid = res.mdid;
				  	app.goto('/pagesA/mendiancenter/my?mdid='+that.mdid);
				  } else {
				    if (res.msg) {
				      app.alert(res.msg, function() {
				        if (res.url) app.goto(res.url);
				      });
				    } else if (res.url) {
				      app.goto(res.url);
				    } else {
				      app.alert('您无查看权限');
				    }
				  }
				});
			}
		}
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
.content .item .f1 .t2{display:flex;padding-left:20rpx;flex-direction: column;}
.content .item .f1 .t2 text{margin-bottom: 10rpx;}
.content .item .f1 .t2 .x2{color: #999;font-size:24rpx}

</style>