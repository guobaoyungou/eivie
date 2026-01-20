<template>
<view class="container">
	<block v-if="isload">
		<view class="content">
			<block v-if="st==0">
			<view v-for="(item, index) in datalist" :key="index" class="item">
				<view class="f1">
						<text class="t1">{{item.name}}</text>
						<text class="t2" v-if="set_tongji.slje==2 || set_tongji.slje==3">商品金额：{{item.real_totalprice}}元</text>
						<text class="t2" v-if="set_tongji.slje==1 || set_tongji.slje==3">商品数量：{{item.num}}</text>
				</view>
<!--				<view class="f2">-->
<!--						<text class="t1" v-if="item.money>0">+{{item.money}}</text>-->
<!--						<text class="t2" v-else>{{item.money}}</text>-->
<!--				</view>-->
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
			
      nodata: false,
      nomore: false,
      st: 0,
	  type:0,
      datalist: [],
      set_tongji: [],
			textset:{},
      pagenum: 1,
			set:{},
    };
  },

  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.st = this.opt.st || 0;
		this.type = this.opt.type || 0;
		var that = this;
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
				this.datalist = [];
			}
      var that = this;
      var st = that.st;
	  var type = that.type;
      var pagenum = that.pagenum;
			that.loading = true;
			that.nodata = false;
      that.nomore = false;
      app.post('ApiTeamYejiTongji/goodsInfo', {mid: this.opt.mid || 0,type:this.opt.type}, function (res) {
				that.loading = false;
        var data = res.data;
        if (pagenum == 1) {
					that.textset = app.globalData.textset;
					that.set = res.set;
					uni.setNavigationBarTitle({
						title: that.t('商品') + '明细'
					});
          that.datalist = data;
          that.set_tongji = res.set_tongji;
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
    changetab: function (st) {
      this.st = st;
      uni.pageScrollTo({
        scrollTop: 0,
        duration: 0
      });
      this.getdata();
    }
  }
};
</script>
<style>

.content{ width:94%;margin:20rpx 3%;}
.content .item{width:100%;background:#fff;margin:20rpx 0;padding:40rpx 30rpx;border-radius:8px;display:flex;align-items:center}
.content .item:last-child{border:0}
.content .item .f1{display:flex;flex-direction:column}
.content .item .f1 .t1{color:#000000;font-size:30rpx;word-break:break-all;overflow:hidden;text-overflow:ellipsis;}
.content .item .f1 .t2{color:#666666;font-size:24rpx;margin-top:10rpx}
.content .item .f1 .t3{color:#666666;font-size:24rpx;margin-top:10rpx}
.content .item .f2{ flex:1;width:200rpx;font-size:36rpx;text-align:right}
.content .item .f2 .t1{color:#03bc01}
.content .item .f2 .t2{color:#000000}
.content .item .f3{ flex:1;width:200rpx;font-size:32rpx;text-align:right}
.content .item .f3 .t1{color:#03bc01}
.content .item .f3 .t2{color:#000000}

.data-empty{background:#fff}
</style>