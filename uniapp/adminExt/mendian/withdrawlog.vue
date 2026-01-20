<template>
<view class="container">
	<block v-if="isload">
		<dd-tab :itemdata="['全部','待审核','已审核','已驳回']" :itemst="['all','0','1','2']" :st="st" @changetab="changetab"></dd-tab>
		
		<view class="content">
			<view v-for="(item, index) in datalist" :key="index" class="item" @tap="goto" :data-url="'withdrawdetail?id='+item.id"> 
				<view class="headimg">
					<image :src="item.headimg" />
				</view>
				<view class="f1">
						<text class="t1">社区名称：{{item.xqname}}</text>
						<view class="t3">提现金额：<text class="t3_3"  :style="{color:t('color1')}" >{{item.money}}</text>元</view>
						<text class="t2">{{dateFormat(item.createtime,'Y-m-d H:i')}}</text>
				</view>
				<view class="op">
					<view class="st0" v-if="item.status==0">待审核</view>
					<view class="st1" v-if="item.status==1">已审核</view>
					<view class="st2" v-if="item.status==2">已驳回</view>
					<view class="st3" v-if="item.status==3">已打款</view>
				</view>
				<button  :data-id="item.id" class="btn" :style="'background:rgba('+t('color1rgb')+',0.9)'" >查看详情</button>
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
			
			canwithdraw:false,
			textset:{},
      st: 'all',
      datalist: [],
      pagenum: 1,
			nodata:false,
      nomore: false
    };
  },

  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.st = this.opt.st || 0;
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
    getdata: function (loadmore) {
			if(!loadmore){
				this.pagenum = 1;
				this.datalist = [];
			}
      var that = this;
      var pagenum = that.pagenum;
      var st = that.st;
			that.nodata = false;
			that.nomore = false;
			that.loading = true;
      app.post('ApiAdminMendian/withdrawlog', {st: st,pagenum: pagenum}, function (res) {
				that.loading = false;
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
    },
    changetab: function (st) {
      this.st = st;
      uni.pageScrollTo({
        scrollTop: 0,
        duration: 0
      });
      this.getdata();
    },
  }
};
</script>
<style>
.container{ width:100%;display:flex;flex-direction:column}
.content{ width:94%;margin:0 3% 20rpx 3%;}
.content .item{width:100%;background:#fff;margin:20rpx 0;padding:20rpx 20rpx;border-radius:8px;display:flex;align-items:center; position: relative;}
.content .item:last-child{border:0}
.content .item .headimg image{ width: 100rpx; height:100rpx;border-radius: 10rpx;margin-right:10rpx ;}

.content .item .f1{width:500rpx;display:flex;flex-direction:column}
.content .item .f1 .t1{color:#000000;font-size:26rpx;word-break:break-all;overflow:hidden;text-overflow:ellipsis;}
.content .item .f1 .t2{color:#666666; font-size:24rpx}
.content .item .f1 .t3{ margin: 10rpx 0;}
.content .item .f1 .t3_3{font-size: 30rpx;font-weight: bold;}
.content .item .op { position: absolute;right: 30rpx; top:20rpx}
.content .item .op .st1{ color: #219241; }
.content .item .op .st0{ color: #F7C952; }
.content .item .op .st2{ color: #FD5C58; }
.content .item .op .st3{ color: #219241; }

.content .item .btn{ position: absolute; bottom:20rpx; right: 20rpx;width: 120rpx;border-radius: 60rpx;height: 50rpx;line-height: 50rpx;color: #fff;font-size: 24rpx;}

.data-empty{background:#fff}
</style>