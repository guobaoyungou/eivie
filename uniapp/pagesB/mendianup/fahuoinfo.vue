<template>
<view class="container">
	<block v-if="isload">
		<view class="content">
			<view v-for="(item, index) in datalist" :key="index" class="item">
				<view class="lists">
						<view class="t1">{{item.name}}</view>
						<view>
							<view class="t2">规格: {{item.ggname}}</view>
							<view class="t3 flex">
								<text>件数: {{item.num}}</text>
								<text v-if="item.refund_num && item.refund_num > 0">退款件数: {{item.refund_num}}</text>
							</view>
						</view>
				</view>
			</view>
		</view>
		<nomore v-if="nomore"></nomore>
		<nodata v-if="nodata"></nodata>
		<view class="bottom">
			<view v-if="datainfo.status==0" class="btn2" @tap="shouhuo">收货</view>
		</view>
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
			oid:0,
			loading:false,
      isload: false,
			datainfo:[],
      datalist: [],
			nodata:false,
      nomore: false
    };
  },

  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.oid = this.opt.oid;
		this.getdata();
  },
	onPullDownRefresh: function () {
		this.getdata(true);
	},
  onReachBottom: function () {
    if (!this.nodata && !this.nomore) {
      this.getdata(true);
    }
  },
  methods: {
    getdata: function (loadmore) {
			if(!loadmore){
				this.datalist = [];
			}
      var that = this;
			that.nodata = false;
			that.nomore = false;
			that.loading = true;
      app.post('ApiMendianCenter/jianhuodaninfo', {oid: that.oid}, function (res) {

				that.loading = false;
        var data = res.data;
				that.datainfo = data;
				that.datalist = data.lists;
				if (data.lists.length == 0) {
					that.nodata = true;
				}
				that.loaded();
      });
    },
		shouhuo: function (oid) {
		  var that = this;
			var oid = oid;
			app.confirm('确定要收货吗?', function () {
				app.showLoading('收货中');
				app.get('ApiMendianCenter/shouhuoall', {oid:that.oid}, function (res) {
					if(res.status==1){
						app.success(res.msg);
						setTimeout(function () {
							app.goback(true);
						}, 1000);
					}else{
						app.error(res.msg)
					}
				});
			})
		}
  }
};
</script>
<style>
.container{ width:100%;display:flex;flex-direction:column}
.content{width: 94%;margin:0 3%;background: #fff;border-radius:16rpx;margin-bottom: 100rpx;}
.content .item{ width:100%;padding:10rpx 10rpx;border-top: 1px #f5f5f5 solid;display:flex;align-items:center;font-size: 26rpx;}
.content .item .lists{width: 100%;}
.content .item .lists .t1{width:100%}
.content .item .lists .t2{color:#666666;padding: 10rpx 0;}
.content .item .lists .t3{color:#aaa;}
.content .item .lists .t3 text{width: 50%;text-align: left;display: block;}
.bottom{ width: 100%;height:92rpx;padding: 0 20rpx;background: #fff; position: fixed; bottom: 0px;left: 0px;display:flex;justify-content:flex-end;align-items:center;}

.btn2{margin-left:20rpx;margin-top: 10rpx;width:160rpx;height:60rpx;line-height:60rpx;color:#333;background:#fff;border:1px solid #cdcdcd;border-radius:3px;text-align:center}
</style>