<template>
<view class="container">
	<block v-if="isload">
		<view class="content">
			<block v-if="st==0">
      <view class="item" v-if="ly_type == 6 && zhitui_num != '-1'">
        <view class="f1">
          <text class="t1">当月直推新人{{zhitui_num}}个</text>
        </view>
      </view>
      <view class="item" v-if="ly_type == 1">
        <view class="f1" v-if="weisj != '-1'">
          <text class="t1">您还差{{weisj}}个升级奖励升级<text v-if="level_name">到[{{level_name}}]</text>，请继续努力</text>
        </view>
        <view class="f1" v-if="yisj != '-1'">
          <text class="t1">恭喜您，您已升级成为[{{yisj}}]，剩下的钱自动转入爱心基金</text>
        </view>
      </view>
			<view v-for="(item, index) in datalist" :key="index" class="item">
				<view class="f1">
<!--          <text class="t3" v-if="item.after">变更后余额: {{item.after}}</text>-->
          <text class="t2">{{item.createtime}}</text>
          <text class="t1">{{item.remark}}</text>
				</view>
				<view class="f2">
          <text class="t1" v-if="item.money>0">+{{item.money}}</text>
          <text class="t2" v-else>{{item.money}}</text>
				</view>
			</view>
			</block>
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
      title_name:'爱心基金',
			canwithdraw:false,
			textset:{},
      st: 0,
      datalist: [],
      pagenum: 1,
			nodata:false,
      nomore: false,
      zhitui_num: '-1',
      weisj: '-1',
      yisj: '-1',
      level_name: '',
      ly_type:0,//来源 0爱心基金 1升级奖励 ...
    };
  },

  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.st = this.opt.st || 0;
    this.ly_type = this.opt.ly_type || 0;
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
      app.post('ApiMy/myOfflineSubsidiesLog', {st: st,pagenum: pagenum,ly_type:that.ly_type}, function (res) {
				that.loading = false;
        var data = res.data;
        var title_name = res.title_name;
        that.zhitui_num = res.zhitui_num;
        that.weisj = res.weisj;
        that.yisj = res.yisj;
        that.level_name = res.level_name;
        if (pagenum == 1) {
					that.textset = app.globalData.textset;
					uni.setNavigationBarTitle({
						title: title_name + '明细'
					});
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
		if(res.showstatus.length > 0){
			that.showstatus = res.showstatus;
		}
      });
    },
  }
};
</script>
<style>
.container{ width:100%;display:flex;flex-direction:column}
.content{ width:94%;margin:0 3% 20rpx 3%;}
.content .item{width:100%;background:#fff;margin:20rpx 0;padding:20rpx 20rpx;border-radius:8px;display:flex;align-items:center}
.content .item:last-child{border:0}
.content .item .f1{display:flex;flex-direction:column}
.content .item .f1 .t1{color:#000000;font-size:30rpx;word-break:break-all;overflow:hidden;text-overflow:ellipsis;}
.content .item .f1 .t2{color:#666666}
.content .item .f1 .t3{color:#666666}
.content .item .f2{ flex:1;font-size:36rpx;text-align:right}
.content .item .f2 .t1{color:#03bc01}
.content .item .f2 .t2{color:#000000}
.content .item .f3{ flex:1;font-size:32rpx;text-align:right}
.content .item .f3 .t1{color:#03bc01}
.content .item .f3 .t2{color:#000000}
.btn1{height:60rpx;line-height:60rpx;color:#fff;border-radius:3px;text-align:center;padding: 0 15rpx;float: right;font-size: 14px;margin-left: 10rpx}
.data-empty{background:#fff}
</style>