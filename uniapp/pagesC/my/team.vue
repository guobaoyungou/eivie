<template>
<view class="container">
	<block v-if="isload">
		<block v-if="level_tab">
			<dd-tab :itemdata="tabdata" :itemst="tabitems" :st="st" :isfixed="false" @changetab="changetab"></dd-tab>
		</block>
		<view class="topsearch flex-y-center" v-if="st==1">
			<view class="f1 flex-y-center">
				<image class="img" :src="pre_url+'/static/img/search_ico.png'"></image>
				<input :value="keyword" placeholder="输入昵称/姓名/手机号搜索" placeholder-style="font-size:24rpx;color:#C2C2C2" @confirm="searchConfirm" @input="searchChange"></input>
			</view>
		</view>
		<view class="topsearch flex-y-center" v-if="st==2 || st==3">
			<view class="f1 flex-y-center">
				<image class="img" :src="pre_url+'/static/img/search_ico.png'"></image>
				<input :value="keyword" placeholder="输入订单号搜索" placeholder-style="font-size:24rpx;color:#C2C2C2" @confirm="searchConfirm" @input="searchChange"></input>
			</view>
		</view>
		
		<view class="content" v-if="st==1">
			<view class="topsearch flex-y-center sx" v-if="userlevel && userlevel.team_month_data==1">
				<text class="t1">日期筛选：</text>
				<view class="body_data" style="min-width: 200rpx;" @click="toCheckDate"> {{startDate?startDate:'点击选择日期'}}{{endDate?' 至 '+endDate:''}}
					<!-- <img class="body_detail" :src="pre_url+'/static/img/week/week_detail.png'" /> -->
				</view>
				<view class="t_date">
					<view v-if="startDate" class="x1" @tap="clearDate">清除</view>
				</view>
			</view>
			<view class="topsearch flex-y-center sx" >
				<text class="t1" >等级筛选：</text>
				<view class="t2" @tap="chooseLevel">
					<view class="uni-input" v-if="checkLevelname">{{checkLevelname}}</view>
					<view style="font-size:28rpx;color: #686868" v-else>请选择级别</view>
				</view>
			</view>
			<view class="yejilabel">
				<block>
					<!-- 默认团队业绩 -->
					<view class="t1" v-if="userlevel">{{t('团队')}}业绩：{{is_end==1?userinfo.team_yeji_total:'计算中'}} 元</view>
				</block>
			</view>
		</view>
		<view class="content" v-if="st==3">
			<view class="topsearch flex-y-center sx" >
				<text class="t1" >代理区域：</text>
				<view class="t2">
					{{userinfo.areafenhong_adr}}
				</view>
			</view>
			
		</view>
		<view class="content" v-if="st==2 || st==3">
			
			<view class="date-search">
			  <view class="flex-bt">
			    <view class="date-btn" :style="selectedDateRange == 1 ? 'color:#fff;background-color:'+t('color1') : ''" @click="setDate(1)">今日</view>
			    <view class="date-btn" :style="selectedDateRange == 2 ? 'color:#fff;background-color:'+t('color1') : ''" @click="setDate(2)">近七日</view>
			    <view class="date-btn" :style="selectedDateRange == 3 ? 'color:#fff;background-color:'+t('color1') : ''" @click="setDate(3)">近30日</view>
			    <view class="date-btn" :style="selectedDateRange == 4 ? 'color:#fff;background-color:'+t('color1') : ''" @click="toSelectDate">自定义</view>
			  </view>
			  <view class="show-search-date" v-if="sdate && edate">{{sdate}} / {{edate}}</view>
			</view>
		</view>

		<view class="content" v-if="datalist && datalist.length>0 && st==1">
			<!-- 给下级升级 人数 -->
			<view class="label">
				<text class="t1">成员信息</text>
			</view>
			<block v-for="(item, index) in datalist" :key="index">
				<view class="item">
					<view class="f1" @click="toteam(item.id)">
						<image :src="item.headimg" mode="widthFix"></image>
						<view class="t2">
							<text class="x1">{{item.nickname}}(ID:{{item.id}})</text>
							<text class="x2">{{item.createtime}}</text>
							<text class="x2">等级：{{item.levelname}}</text>
							<text class="x2" v-if="item.tel">手机号：{{item.tel}}</text>
						</view>
					</view>
					<view class="f2">
						<text class="t4" v-if="userlevel && userlevel.team_yeji==1">{{t('团队')}}业绩：{{item.teamyeji}}</text>
						<text class="t4" v-if="userlevel && userlevel.team_self_yeji==1">个人业绩：{{item.selfyeji}}</text>
						<text class="t4" v-if="userlevel && userlevel.team_down_total==1">{{t('一级')}}人数：{{item.team_down_total}} 人</text>
					</view>
				</view>
			</block>
		</view>
		<view class="content" v-if="orderlist && orderlist.length>0 && (st==2 || st==3)">
			<!-- 给下级升级 人数 -->
			<view class="label">
				<text class="t1">订单信息</text>
				<text class="t2">分红</text>
			</view>
			<block v-for="(item, index) in orderlist" :key="index">
				<view class="item">
					<view class="f1">
						<view class="t2">
							<text class="x1">{{item.title}}</text>
							<text class="x2">订单编号：{{item.ordernum}}</text>
							<text class="x2">{{item.createtime}}</text>
						</view>
					</view>
					<view class="f2">
						<text class="t4">订单金额：{{item.totalprice}}</text>
						<text class="t1">+{{item.commission}}</text>
					</view>
				</view>
			</block>
		</view>
		<view v-if="levelDialogShow" class="popup__container">
			<view class="popup__overlay" @tap.stop="hideTimeDialog"></view>
			<view class="popup__modal">
				<view class="popup__title">
					<text class="popup__title-text">请选择级别</text>
					<image :src="pre_url+'/static/img/close.png'" class="popup__close" style="width:36rpx;height:36rpx" @tap.stop="hideTimeDialog"/>
				</view>
				<view class="popup__content">
					<view class="pstime-item" @tap="levelRadioChange" data-id="0" data-name="全部">
						<view class="flex1">全部</view>
						<view class="radio" :style="''"><image class="radio-img" :src="pre_url+'/static/img/checkd.png'"/></view>
					</view>
					<view class="pstime-item" v-for="(item, index) in allLevel" :key="index" @tap="levelRadioChange" :data-id="item.id" :data-name="item.name">
						<view class="flex1">{{item.name}}</view>
						<view class="radio" :style="''"><image class="radio-img" :src="pre_url+'/static/img/checkd.png'"/></view>
					</view>
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
		startDate: '',
		endDate: '',
		pre_url: app.globalData.pre_url,
		team_auth:0,
		checkLevelid: 0,
		checkLevelname: '',
		levelDialogShow: false,
		allLevel:{},
		is_end:0,
    sdate:'',//订单筛选时间 开始时间
    edate:'',//订单筛选时间 结束时间
    selectedDateRange:'',//选中时间筛选
    first_mid:0,//查看成交订单mid
		showlevel:true,
		level_tab:true,
		orderlist:[]
    };
  },

  onLoad: function (opt) {
	this.opt = app.getopts(opt);
	this.mid = this.opt.mid;
	if(this.opt.first_mid){
		this.first_mid = this.opt.first_mid;
	}
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
  	uni.$on('selectedDate',function(data,otherParam){
      if(otherParam && otherParam == 1){
        that.sdate =  data.startStr.dateStr;
        that.edate = data.endStr.dateStr;
      }else{
        that.startDate = data.startStr.dateStr;
        that.endDate = data.endStr.dateStr;
      }
			uni.pageScrollTo({
				scrollTop: 0,
				duration: 0
			});
			that.getdata();
		})
  },
  methods: {
	  getdata(loadmore){
		  var st = this.st;
		  if(st==2 || st==3){
			  this.getorderdata(loadmore);
		  }else{
			  this.getmemberdata(loadmore);
		  }
	  },
    getmemberdata: function (loadmore) {
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
			var date_start = that.startDate;
			var date_end = that.endDate;
			var checkLevelid = that.checkLevelid;
			var first_mid = that.first_mid;	
      app.post('ApiMy/myteam', {st: st,pagenum: pagenum,keyword:keyword,mid:mid,date_start:date_start,date_end:date_end,checkLevelid:checkLevelid,version:'2.6.3',first_mid:first_mid}, function (res) {
				that.loading = false;
        var data = res.datalist;
        if (pagenum == 1) {
			that.userinfo = res.userinfo;
			that.userlevel = res.userlevel;
			that.textset = app.globalData.textset;
			that.datalist = data;
			that.levelList = res.levelList;
			if(res.is_area==0){
				that.tabdata = [that.t('团队数据')+'('+res.userinfo.team_total+')','团队订单('+res.userinfo.team_order_total+')'];
				that.tabitems = ['1','2'];
			}else{
				that.tabdata = [that.t('团队数据')+'('+res.userinfo.team_total+')','团队订单('+res.userinfo.team_order_total+')','区域订单('+res.userinfo.area_order_total+')'];
				that.tabitems = ['1','2','3'];
			}
			
			that.team_auth = res.team_auth;
			that.allLevel = res.all_level;
			that.showlevel = res.showlevel;
			that.level_tab = res.level_tab;
			
			if (data.length == 0) {
				that.nodata = true;
			}
			uni.setNavigationBarTitle({
				title: that.t('我的团队')
			});

			that.loaded();
			//异步获取当前会员的业绩数据
			that.sequentialRequests();
        }else{
          if (data.length == 0) {
            that.nomore = true;
          } else {
            var datalist = that.datalist;
            var newdata = datalist.concat(data);
            that.datalist = newdata;
          }
        }
        // 异步获取团队会员的业绩
        that.sequentialRequests2();
      });
    },
	getorderdata: function (loadmore) {
		if(!loadmore){
			this.pagenum = 1;
			this.orderlist = [];
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
		var date_start = that.startDate;
		var date_end = that.endDate;
		var checkLevelid = that.checkLevelid;
		var first_mid = that.first_mid;	
		app.post('ApiMy/fhorder', {st: st,pagenum: pagenum,keyword:keyword,mid:mid,sdate:that.sdate,edate:that.edate}, function (res) {
			that.loading = false;
			var data = res.datalist;
			if (pagenum == 1) {
				that.orderlist = data;
				if (data.length == 0) {
					that.nodata = true;
				}
				uni.setNavigationBarTitle({
					title: that.t('我的团队')+'订单'
				});
		
				that.loaded();
			}else{
			  if (data.length == 0) {
				that.nomore = true;
			  } else {
				var orderlist = that.orderlist;
				var newdata = orderlist.concat(data);
				that.orderlist = newdata;
			  }
			}
		});
	},
	// 异步获取当前会员的业绩数据
	sequentialRequests:async function() {
		var that = this;
		await that.getdata2();
		that.is_end = 1;
		console.log('请求完成1'); // 处理响应
	},
	getdata2: function () {
		var that = this;
		return new Promise((resolve, reject) => {
			var mid = that.mid;
			var date_start = that.startDate;
			var date_end = that.endDate;
			var checkLevelid = that.checkLevelid;
		   app.get('ApiAgent/get_team_yeji', {mid:mid,date_start:date_start,date_end:date_end,checkLevelid:checkLevelid,sdate:that.sdate,edate:that.edate,first_mid:that.first_mid}, function (res) {
				var data = res.data
				that.userinfo.team_yeji_total = data.team_yeji_total || 0;
				that.userinfo.team_miniyeji_total = data.team_miniyeji_total || 0;
				that.userinfo.next_up_ordermoney = data.next_up_ordermoney || 0;
				that.userinfo.now_month_yeji = data.now_month_yeji || 0;
				that.userinfo.team_yeji_pronum = data.team_yeji_pronum || 0;
				that.userinfo.yeji_pronum = data.yeji_pronum || 0;
				if(data){
					that.userinfo = { ...that.userinfo, ...data };
				}
				resolve(data);
			});
		});
	},
	// 异步获取团队会员的业绩
	sequentialRequests2:async function() {
		var that = this;
		var datalist = that.datalist;
		for (let i = 0; i < datalist.length; i++) {
			var member = datalist[i];
			if(member.teamyeji=='计算中'){
			  var data = await that.getyeji(member.id);
				if(member.teamyeji == '计算中'){
				  member.teamyeji = data.teamyeji || 0;
				  member.selfyeji = data.selfyeji || 0;
				  member.team_down_total = data.team_down_total || 0;
				}
				datalist[i] = member;
			}
		}
		that.datalist = datalist;
		console.log('请求完成2'); // 处理响应
	},
	getyeji: function (mid) {
		var that = this;
		console.log('请求'+mid);
		return new Promise((resolve, reject) => {
		   app.get('ApiAgent/get_member_yeji', {mid:mid,sdate:that.sdate,edate:that.edate,first_mid:that.first_mid}, function (res) {
				var data = res.data
				resolve(data);
			});
		   
		});
	},
    changetab: function (st) {
		this.st = st;
		this.keyword = '';
		this.sdate = '';
		this.edate = '';
		this.selectedDateRange = '';
		uni.pageScrollTo({
			scrollTop: 0,
			duration: 0
		});
		this.getdata();
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
	toteam:function(mid){
		if(this.team_auth){
			uni.navigateTo({
				url:'/activity/commission/myteam?mid='+mid
			})
		}
		return;

	},

	chooseLevel: function (e) {
		this.levelDialogShow = true;
	},
	hideTimeDialog: function () {
	  this.levelDialogShow = false;
	},
	levelRadioChange: function (e) {
	  var that = this;
	  that.checkLevelname = e.currentTarget.dataset.name;
	  that.checkLevelid = e.currentTarget.dataset.id;
	  that.levelDialogShow = false;
	  that.getdata();
	},
	setDate(date) {
	  const currentDate = new Date();
	  this.selectedDateRange = date;
	
	  const formatDate = (date) => {
	    const year = date.getFullYear();
	    const month = String(date.getMonth() + 1).padStart(2, '0'); // 月份是从0开始的
	    const day = String(date.getDate()).padStart(2, '0');
	    return `${year}-${month}-${day}`;
	  };
	
	  if (date === 1) {
	    const today = formatDate(currentDate);
	    this.sdate = today;
	    this.edate = today;
	  } else if (date === 2) {
	    const sevenDaysAgo = new Date(currentDate.setDate(currentDate.getDate() - 6));
	    const today = formatDate(new Date());
	    this.sdate = formatDate(sevenDaysAgo);
	    this.edate = today;
	  } else if (date === 3) {
	    const thirtyDaysAgo = new Date(currentDate.setDate(currentDate.getDate() - 29));
	    const today = formatDate(new Date());
	    this.sdate = formatDate(thirtyDaysAgo);
	    this.edate = today;
	  }
	  
	  this.getdata();
	},
	toSelectDate(){
	  this.selectedDateRange = 4;
		app.goto('../../pagesExt/checkdate/checkDate?ys=2&type=1&t_mode=5&otherParam=1');
	},
	toCheckDate(){
		app.goto('../../pagesExt/checkdate/checkDate?ys=2&type=1&t_mode=5');
	},
	 clearDate(){
	  	var that  = this;
		that.startDate = '';
		that.endDate = '';
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

.content .item .f2{display:flex;flex-direction:column;width:200rpx;border-left:1px solid #eee;text-align: right;}
.content .item .f2 .t1{ font-size: 40rpx;color: #666;height: 40rpx;line-height: 40rpx;}
.content .item .f2 .t2{ font-size: 28rpx;color: #999;height: 50rpx;line-height: 50rpx;}
.content .item .f2 .t3{ display:flex;justify-content:flex-end;margin-top:10rpx; flex-wrap: wrap;}
.content .item .f2 .t3 .x1{padding:8rpx;border:1px solid #ccc;border-radius:6rpx;font-size:22rpx;color:#666;margin-top: 10rpx;margin-left: 6rpx;}
.content .item .f2 .t4{ display:flex;margin-top:10rpx;margin-left: 10rpx;color: #666; flex-wrap: wrap;font-size:18rpx;text-align: left}
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
	.t_date .x1{height:40rpx;line-height:40rpx;padding:0 8rpx;border:1px solid #ccc;border-radius:6rpx;font-size:22rpx;color:#666;margin-left: 10rpx;}
	.pstime-item{display:flex;border-bottom: 1px solid #f5f5f5;padding:20rpx 30rpx;}
	.pstime-item .radio{flex-shrink:0;width: 32rpx;height: 32rpx;background: #FFFFFF;border: 2rpx solid #BFBFBF;border-radius: 50%;margin-right:30rpx}
	.pstime-item .radio .radio-img{width:100%;height:100%}
	.content .yejilabel{/* display:flex; */width: 100%;padding: 16rpx;color: #333;justify-content: space-between;flex-wrap: wrap;line-height:60rpx;border-top:1rpx solid #f5f5f5}
	/* .content .yejilabel .t1{flex-shrink: 0;width: 50%;} */
	.content .sx{padding:20rpx 16rpx; margin:0}
	.levelup-num{padding:10rpx 20rpx}
	.levelup-num .list{flex-wrap: wrap;}
	.levelup-num .t1{font-weight: 700;}
	.num{padding: 10rpx;width: 50%;}
	
	.popup_centent_view{width: 90vw;background: #fff;border-radius: 15rpx;display: flex;flex-direction: column;align-items: center;justify-content: center;}
		.popup_centent_view .popup_title_view{width: 100%;padding: 30rpx 0rpx;color: #3D3D3D;font-size: 36rpx;text-align: center;}
		.popup_centent_view .popup_input-view{width: 90%;margin: 0 auto;padding: 40rpx 20rpx 0rpx;}
		.popup_centent_view .popup_input-view .options-view{padding: 30rpx 0rpx;}
		.popup_input-view .options-view .title-text{font-size: 32rpx;color: #000;}
		.popup_input-view .options-view .num-options{margin-left: 40rpx;}
		.popup_input-view .options-view .num-options .num-but{width: 40rpx;height:40rpx;border:2px #46DE99 solid;border-radius:10rpx;background:#fff;
			display: flex;align-items: center;justify-content: center;color: #46DE99;}
		.popup_input-view .options-view .num-options .num-input-view{width: 140rpx;height: 70rpx;background: #efefef;margin: 0rpx 30rpx;border-radius: 10rpx;}
		.options-view .num-options .num-input-view input{width: 100%;height: 100%;font-size: 32rpx;font-weight: bold;color: #000;}
		.popup_centent_view .popup_but_view{width: 90%;margin: 0 auto;display: flex;align-items: center;justify-content: space-around;padding: 30rpx;}
		.popup_centent_view .popup_but_view .poput_options_but{width: 190rpx;height: 72rpx;line-height: 72rpx;text-align: center;border-radius: 10rpx;
		font-size: 32rpx;color: #000;border: 1px  #DFDFDF solid;}
		.popup_centent_view .popup_but_view .success{background: #46DE99;color: #FFFFFF;border: unset;}
    .date-search{padding: 3%;}
    .date-btn{color: #999;width: 20.5%;text-align: center;padding: 10rpx;border-radius: 5rpx;}
    .show-search-date {margin-top: 20px;color: #333;text-align: center;}
	.btn1{margin-left:20rpx;height:45rpx;line-height:45rpx;color:#fff;border-radius:3px;text-align:center;padding: 0rpx 10rpx;}
</style>