<template>
<view class="container">
	<block v-if="isload">
		<view class="content">
			<view class="topsearch flex-y-center sx" v-if="1">
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
    </view >
    <view class="content">
      <view class="label">
        <text class="t1">分销数据统计({{userinfo.nickname}} ID:{{userinfo.id}})</text>
      </view>
			<view class="yejilabel">
				<text class="t1" >订单总量：{{is_end?userinfo.order_num||0:'计算中'}} 单</text>
				<text class="t1" >销售金额：{{is_end?userinfo.teamyeji||0:'计算中'}} 元</text>
				<text class="t1" >团队人数：{{is_end?userinfo.team_down_total||0:'计算中'}} </text>
				<text class="t1" >上交金额：{{is_end?userinfo.submission_amount||0:'计算中'}} 元</text>
				<text class="t1" >差价：{{is_end?userinfo.differential||0:'计算中'}} 元</text>
				<text class="t1" >劳务推广：{{is_end?userinfo.labor||0:'计算中'}} 元</text>
			</view>
		</view>
    <view class="topsearch flex-y-center">
      <view class="f1 flex-y-center">
        <image class="img" :src="pre_url+'/static/img/search_ico.png'"></image>
        <input :value="keyword" placeholder="输入昵称/ID/手机号搜索" placeholder-style="font-size:24rpx;color:#C2C2C2" @confirm="searchConfirm" @input="searchChange"></input>
      </view>
    </view>
		<view class="content" v-if="datalist && datalist.length>0">
			<view class="label">
				<text class="t1">分销对账单</text>
			</view>
			<block v-for="(item, index) in datalist" :key="index">
				<view class="item">
					<view class="f1" @click="toteam(item.id)">
						<image :src="item.headimg"></image>
						<view class="t2">
							<text class="x1">{{item.nickname}}(ID:{{item.id}})</text>
							<text class="x2">等级：{{item.level_name}}</text>
							<text class="x2" v-if="item.tel">手机号：{{item.tel}}</text>
						</view>
					</view>
					<view class="f2">
					<view class="f2">
						<text class="t4" style="font-size: 23rpx !important;">订单量：{{item.order_num}} </text>
						<text class="t4" style="font-size: 23rpx !important;">销售金额：{{item.teamyeji}} </text>
						<text class="t4" style="font-size: 23rpx !important;">团队人数：{{item.team_down_total}} </text>
						<text class="t4" style="font-size: 23rpx !important;">上交金额：{{item.submission_amount}} </text>
						<text class="t4" style="font-size: 23rpx !important;">差价：{{item.differential}} </text>
						<text class="t4" v-if="item.labor != null" style="font-size: 23rpx !important;">劳务推广：{{item.labor}} </text>
					</view>
				  </view>
        </view>
			</block>
		</view>
    <view class="content" v-else>
      <view class="label">
        <text class="t1">分销对账单</text>
      </view>
      <block >
        <view class="item">
          <view class="live-box empty" ></view>
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
		st: 1,
		datalist: [],
		pagenum: 1,
		userlevel:{},
		userinfo:{},
		textset:{},
		levelList:{},
		keyword:'',
		tomid:'',
		tomoney:0,
		toscore:0,
		nodata: false,
		nomore: false,
		dialogShow: false,
		tempMid: '',
		tempLevelid: '',
		tempLevelsort: '',
		mid:0,
		range: [],
		tabdata:[],
		tabitems:[],
		startDate: '-选择日期-',
		endDate: '-选择日期-',
		pre_url: app.globalData.pre_url,
		team_auth:0,
		checkLevelid: 0,
		checkLevelname: '',
		levelDialogShow: false,
		allLevel:{},
		month_item:[],
		monthindex:-1,
		month_text:'当月团队总业绩',
		month_value:'',
		zt_member_limit:0, //直推名额数
		custom:{},
		is_end:0
    };
  },

  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.mid = this.opt.mid;
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
  onShow(){
  	var that  = this;
  	uni.$on('selectedDate',function(data){
			that.startDate = data.startStr.dateStr;
			that.endDate = data.endStr.dateStr;
			uni.pageScrollTo({
				scrollTop: 0,
				duration: 0
			});
			that.getdata();
		})
  },
  methods: {
    getdata: function (loadmore) {
			if(!loadmore){
				this.pagenum = 1;
				this.datalist = [];
				this.is_end = 0;
			}
      var that = this;
      var st = that.st;
      var pagenum = that.pagenum;
      var keyword = that.keyword;
			that.loading = true;
			that.nodata = false;
      that.nomore = false;
			var mid = that.mid;
			var date_start = that.startDate=='-选择日期-' ? '' : that.startDate;
			var date_end = that.endDate=='-选择日期-' ? '' : that.endDate;
			var checkLevelid = that.checkLevelid;
			var month_search = that.month_value;	
      app.post('ApiTransferOrderParentCheck/tongji', {st: st,pagenum: pagenum,keyword:keyword,mid:mid,date_start:date_start,date_end:date_end,checkLevelid:checkLevelid,month_search:month_search}, function (res) {
				that.loading = false;
        that.is_end = 1;
        if(res.status == 0){
          that.datalist = {};
          that.userinfo = {};
          //app.error(res.msg);return;
        }
        that.datalist = res.datalist;
        that.userinfo = res.userinfo;
        that.loaded();
      });
    },
    searchChange: function (e) {
      this.keyword = e.detail.value;
    },
    searchConfirm: function (e) {
      var that = this;
      var keyword = e.detail.value;
      that.keyword = keyword;
      that.getdata();
    },
		showDialog:function(e){
			let that = this;
			that.tempMid = e.currentTarget.dataset.id;
			that.tempLevelid = e.currentTarget.dataset.levelid;
			that.tempLevelsort = e.currentTarget.dataset.levelsort;
			this.dialogShow = !this.dialogShow
		},
		toteam:function(mid){
      uni.navigateTo({
        url:'/pagesC/transferorderparent/tongji?mid='+mid
      })
			return;
		},
		toCheckDate(){
			app.goto('../../pagesExt/checkdate/checkDate?ys=2&type=1&t_mode=5');
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
    callphone:function(e) {
      var phone = e.currentTarget.dataset.phone;
      uni.makePhoneCall({
        phoneNumber: phone,
        fail: function () {
        }
      });
    },
	  chooseLevel: function (e) {
    this.levelDialogShow = true;
		},
		hideTimeDialog: function () {
		  this.levelDialogShow = false;
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
	}
};
</script>
<style>

.topsearch{width:94%;margin:16rpx 3%;}
.topsearch .f1{height:60rpx;border-radius:30rpx;border:0;background-color:#fff;flex:1}
.topsearch .f1 .img{width:24rpx;height:24rpx;margin-left:10px}
.topsearch .f1 input{height:100%;flex:1;padding:0 20rpx;font-size:28rpx;color:#333;}

.content{width:94%;margin:0 3%;border-radius:16rpx;background: #fff;margin-top: 20rpx;}
.content .label{display:flex;width: 100%;padding: 16rpx;color: #333;}
.content .label .t1{flex:1}
.content .label .t2{ width:300rpx;text-align:right}

.content .item{width: 100%;padding:32rpx 20rpx;border-top: 1px #eaeaea solid;min-height: 112rpx;display:flex;align-items:center;}
.content .item image{width: 90rpx;height: 90rpx;border-radius:4px}
.content .item .f1{display:flex;flex:1;align-items:center;}
.content .item .f1 .t2{display:flex;flex-direction:column;padding-left:20rpx}
.content .item .f1 .t2 .x1{color: #333;font-size:26rpx;}
.content .item .f1 .t2 .x2{color: #999;font-size:24rpx;}
.content .item .f1 .t2 .x3{font-size:24rpx;}

.content .item .f2{display:flex;flex-direction:column;width:255rpx;border-left:1px solid #eee;text-align: right;}
.content .item .f2 .t1{ font-size: 40rpx;color: #666;height: 40rpx;line-height: 40rpx;}
.content .item .f2 .t2{ font-size: 28rpx;color: #999;height: 50rpx;line-height: 50rpx;}
.content .item .f2 .t3{ display:flex;justify-content:flex-end;margin-top:10rpx; flex-wrap: wrap;}
.content .item .f2 .t3 .x1{padding:8rpx;border:1px solid #ccc;border-radius:6rpx;font-size:22rpx;color:#666;margin-top: 10rpx;margin-left: 6rpx;}
.content .item .f2 .t4{ display:flex;margin-top:10rpx;margin-left: 10rpx;color: #666; flex-wrap: wrap;font-size:18rpx;}
.sheet-item {display: flex;align-items: center;padding:20rpx 30rpx;}
.sheet-item .item-img {width: 44rpx;height: 44rpx;}
.sheet-item .item-text {display: block;color: #333;height: 100%;padding: 20rpx;font-size: 32rpx;position: relative; width: 90%;}
.sheet-item .item-text:after {position: absolute;content: '';height: 1rpx;width: 100%;bottom: 0;left: 0;border-bottom: 1rpx solid #eee;}
.man-btn {
	line-height: 100rpx;
	text-align: center;
	background: #FFFFFF;
	font-size: 30rpx;
	color: #FF4015;
}
	
	.body_data {
		font-size: 28rpx;
		font-weight: normal;
		font-family: PingFang SC;
		font-weight: 500;
		color: #686868;
		display: flex;
		align-items: center;
		float: right;
		/* border: 1rpx solid #cac5c5;
		padding: 2px;
		margin-left: 5px; */
	}
	.body_detail {
		height: 35rpx;
		width: 35rpx;
		margin-left: 10rpx;
	}
	.t_date .x1{height:45rpx;line-height:40rpx;padding:0 10rpx;border:1px solid #ccc;border-radius:6rpx;font-size:22rpx;color:#666;margin: 8rpx 0 0 30rpx;}
	.pstime-item{display:flex;border-bottom: 1px solid #f5f5f5;padding:20rpx 30rpx;}
	.pstime-item .radio{flex-shrink:0;width: 32rpx;height: 32rpx;background: #FFFFFF;border: 2rpx solid #BFBFBF;border-radius: 50%;margin-right:30rpx}
	.pstime-item .radio .radio-img{width:100%;height:100%}
	.content .yejilabel{display:flex;width: 100%;padding: 16rpx;color: #333;justify-content: space-between;flex-wrap: wrap;line-height:60rpx;border-top:1rpx solid #f5f5f5}
	.content .yejilabel .t1{flex-shrink: 0;width: 50%;}
	.content .sx{padding:20rpx 16rpx; margin:0}
</style>