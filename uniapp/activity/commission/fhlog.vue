<template>
<view class="container">
	<block v-if="isload">
    <view class="content">
      <view class="topsearch flex-y-center sx" v-if="mendian_member_levelup_fenhong">
        <text class="t1">日期筛选：</text>
        <view class="f2 flex" style="line-height:30px;">
          <picker mode="date" :value="startDate" @change="bindStartDateChange">
            <view class="picker">{{startDate}}</view>
          </picker>
          <view style="padding:0 10rpx;color:#222;font-weight:bold">至</view>
          <picker mode="date" :value="endDate" @change="bindEndDateChange">
            <view class="picker">{{endDate}}</view>
          </picker>
          <view class="t_date">
            <view v-if="startDate" class="x1" @tap="clearDate">清除</view>
          </view>
        </view>
      </view>
    </view>
		<view class="content">
			<view v-for="(item, index) in datalist" :key="index" class="item">
				<view class="f1">
						<text class="t1">{{item.remark}}</text>
						<text class="t2">{{dateFormat(item.createtime,'Y-m-d H:i')}}</text>
						<text class="t3" v-if="item.copies">份数：{{item.copies}}</text>
						<view class="x3"  v-if="show_from_member && item.frommid>0">来源{{t('会员')}}：<image :src="item.from_headimg"></image>{{item.from_nickname}}</view>
				</view>
				<view class="f2">
						<text class="t1" v-if="item.commission>0">+{{item.commission}}</text>
						<text class="t2" v-else>{{item.commission}}</text>
				</view>
			</view>
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
      datalist: [],
			textset:{},
      pagenum: 1,
      module: '',
      mdid: '',
      startDate: '-选择日期-',
      endDate: '-选择日期-',
      mendian_member_levelup_fenhong: false,
	  show_from_member:0,//是否显示来源会员信息
    };
  },

  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.st = this.opt.st || 0;
		this.module = this.opt.module || '';
		this.mdid = this.opt.mdid || '';
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
      var pagenum = that.pagenum;
      var date_start = that.startDate=='-选择日期-' ? '' : that.startDate;
      var date_end = that.endDate=='-选择日期-' ? '' : that.endDate;
			that.loading = true;
			that.nodata = false;
      that.nomore = false;
      app.post('ApiAgent/fhlog', {st: st,pagenum: pagenum,module:that.module,mdid:that.mdid,date_start:date_start,date_end:date_end}, function (res) {
				that.loading = false;
        var data = res.data;
        that.mendian_member_levelup_fenhong = res.mendian_member_levelup_fenhong;
        if (pagenum == 1) {
					that.textset = app.globalData.textset;
          that.datalist = data;
          if (data.length == 0) {
            that.nodata = true;
          }
		  that.show_from_member = res.show_from_member || 0;
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
    bindStartDateChange:function(e){
      if(this.endDate && this.endDate != '-选择日期-'){
        if(e.target.value > this.endDate){
          app.error('开始时间必须小于等于结束时间');return;
        }
        this.startDate = e.target.value
        this.getdata();
      }else {
        this.startDate = e.target.value
      }
    },
    bindEndDateChange:function(e){
      if(this.startDate && this.startDate != '-选择日期-'){
        if(this.startDate > e.target.value){
          app.error('结束时间必须大于等于开始时间');return;
        }
        this.endDate = e.target.value;
        this.getdata();
      }else {
        this.endDate = e.target.value;
      }
    },
    clearDate(){
      var that  = this;
      that.startDate = '-选择日期-';
      that.endDate = '-选择日期-';
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

.content{ width:94%;margin:20rpx 3%;}
.content .item{width:100%;background:#fff;margin:20rpx 0;padding:40rpx 30rpx;border-radius:8px;display:flex;align-items:center}
.content .item:last-child{border:0}
.content .item .f1{width:500rpx;display:flex;flex-direction:column}
.content .item .f1 .t1{color:#000000;font-size:30rpx;word-break:break-all;overflow:hidden;text-overflow:ellipsis;}
.content .item .f1 .t2{color:#666666;font-size:24rpx;margin-top:10rpx}
.content .item .f1 .t3{color:#666666;font-size:24rpx;margin-top:10rpx}
.content .item .f2{ flex:1;width:200rpx;font-size:36rpx;text-align:right}
.content .item .f2 .t1{color:#03bc01}
.content .item .f2 .t2{color:#000000}
.content .item .f3{ flex:1;width:200rpx;font-size:32rpx;text-align:right}
.content .item .f3 .t1{color:#03bc01}
.content .item .f3 .t2{color:#000000}
.topsearch {height:80rpx;border-radius:30rpx;border:0;background-color:#fff;flex:1}
.topsearch .f1 input{height:100%;flex:1;padding:0 20rpx;font-size:28rpx;color:#333;}
.t_date .x1{height:45rpx;line-height:40rpx;padding:0 10rpx;border:1px solid #ccc;border-radius:6rpx;font-size:22rpx;color:#666;margin: 8rpx 0 0 30rpx;}
.content .sx{padding:20rpx 16rpx; margin:0}

.content .item .f1 .x3{display:flex;align-items:center;margin-top:10rpx}
.content .item .f1 .x3 image{width:40rpx;height:40rpx;border-radius:50%;margin-right:4px}
</style>