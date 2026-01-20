<template>
<view class="container">
	<block v-if="isload">
		<view class="topsearch flex-y-center">
			<view class="f1 flex-y-center">
				<image class="img" :src="pre_url+'/static/img/search_ico.png'"></image>
				<input :value="keyword" placeholder="输入昵称/姓名/手机号搜索" placeholder-style="font-size:24rpx;color:#C2C2C2" @confirm="searchConfirm" @input="searchChange"></input>
			</view>
		</view>
	
		<view class="topsearch flex-y-center" v-if="userlevel && userlevel.team_month_data==1">
			<text class="t1">日期筛选：</text>
			<view class="body_data" style="min-width: 200rpx;height: 100rpx;" @click="toCheckDate"> {{startDate?startDate:'点击选择日期'}}{{endDate?' 至 '+endDate:''}}
				<!-- <img class="body_detail" :src="pre_url+'/static/img/week/week_detail.png'" /> -->
			</view>
			<view class="t_date">
				<view v-if="startDate" class="x1" @tap="clearDate" >清除</view>
			</view>
		</view>
		<view class="topsearch flex-y-center" v-if="team_auth==1 && userlevel && userlevel.team_month_data==1">
			<text class="t1">身份筛选：</text>
			<view class="t2" @tap="chooseLevel">
				<input type="text" placeholder="请选择级别" name="checkLevelid" :value="checkLevelname" placeholder-style="font-size:28rpx;color: #686868;font-family: PingFang SC;" disabled="true">
			</view>
		</view>
		
		<view class="content" v-if="datalist && datalist.length>0">
			<view class="label">
				<text class="t1" >剩余数量：{{couponnum}} 张</text>
			</view>
			<block v-for="(item, index) in datalist" :key="index">
				<view class="item">
					<view class="f1" @click="toteam(item.id)">
						<image :src="item.headimg"></image>
						<view class="t2">
							<text class="x1">{{item.nickname}}</text>
							<text class="x2">{{item.createtime}}</text>
							<text class="x2">等级：{{item.levelname}}</text>
							<text class="x2" v-if="item.tel">手机号：{{item.tel}}</text>
							<text class="x2" v-if="item.pid">推荐人ID：{{item.pid}}</text>
						</view>
					</view>
					<view class="f2">
						<!-- <text class="t4" v-if="userlevel && userlevel.team_yeji==1">团队业绩：{{item.teamyeji}}</text>
						<text class="t4" v-if="userlevel && userlevel.team_self_yeji==1">个人业绩：{{item.selfyeji}}</text>
						<text class="t4" v-if="userlevel && userlevel.team_down_total==1">下级人数：{{item.team_down_total}} 人</text>
						<text class="t4" v-if="userlevel && userlevel.team_score==1">积分：{{item.score}}</text> -->
						<!-- <text class="t1">+{{item.commission}}</text> -->
						<!-- <text class='t2'>{{item.downcount}}个成员</text> -->
						
						<view class="t3">
							<button class="btn" :style="{background:'linear-gradient(270deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'}" @tap="showdialog" :data-id="item.id" >发放</button>
						</view>
					</view>
				</view>
			</block>
		</view>
		
		<view v-if="dialogShow" class="popup__container">
			<view class="popup__overlay" @tap.stop="showdialog"></view>
			<view class="popup__modal">
				<view class="popup__title">
					<text class="popup__title-text">优惠券发放</text>
					<image :src="pre_url+'/static/img/close.png'" class="popup__close" style="width:36rpx;height:36rpx"
						@tap.stop="showdialog" />
				</view>
				<view class="popup__content invoiceBox">
					<form @submit="sendCoupon" @reset="formReset" report-submit="true">
						<view class="orderinfo">
							<view class="item">
								<text class="t1">剩余数量</text>
								<view  class="t2" >
									{{couponnum}} 张
								</view>
							</view>
							<view class="item">
								<text class="t1">发放数量</text>
								<input class="t2" type="text" placeholder="请输入发放数量" placeholder-style="font-size:28rpx;color:#BBBBBB" name="send_num" :disabled="inputDisabled" value="" ></input>
							</view>
						</view>
						<button class="ff_btn" form-type="submit" :style="{background:t('color1')}">确定</button>
						<view style="padding-top:30rpx"></view>
					</form>
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
		couponid :0	,
		couponnum:0
    };
  },

  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.mid = this.opt.mid;
		this.couponid = this.opt.couponid;
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
	})
  	uni.pageScrollTo({
  	  scrollTop: 0,
  	  duration: 0
  	});
  	this.getdata();
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
		var keyword = that.keyword;
			that.loading = true;
			that.nodata = false;
		that.nomore = false;
		var mid = that.mid;
		var date_start = that.startDate;
		var date_end = that.endDate;
		var checkLevelid = that.checkLevelid;
		that.getXianxiaCount();
		  app.post('ApiCoupon/xianxiaCouponTeam', {st: st,pagenum: pagenum,keyword:keyword,mid:mid,date_start:date_start,date_end:date_end,checkLevelid:checkLevelid}, function (res) {
			that.loading = false;
			var data = res.datalist;
			
			if (pagenum == 1) {
				that.userinfo = res.userinfo;
				that.userlevel = res.userlevel;
				that.textset = app.globalData.textset;
				that.datalist = data;
				that.levelList = res.levelList;
				if(res.userlevel && res.userlevel.can_agent==2){
					that.tabdata = ['一级('+res.userinfo.myteamCount1+')','二级('+res.userinfo.myteamCount2+')'];
					that.tabitems = ['1','2'];
				}else if(res.userlevel && res.userlevel.can_agent==3){
					that.tabdata = ['一级('+res.userinfo.myteamCount1+')','二级('+res.userinfo.myteamCount2+')','三级('+res.userinfo.myteamCount3+')'];
					that.tabitems = ['1','2','3'];
				}
				if(res.team_auth){
					that.tabdata = that.tabdata.concat(['团队('+res.userinfo.myteamCount4+')'])
					that.tabitems = that.tabitems.concat(['4']);
				}
				that.team_auth = res.team_auth;
				that.allLevel = res.all_level;
				console.log(that.tabdata);
				
				if (data.length == 0) {
					that.nodata = true;
				}
				uni.setNavigationBarTitle({
					title: that.t('优惠券发放')
				});
				
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
		searchChange: function (e) {
		  this.keyword = e.detail.value;
		},
		searchConfirm: function (e) {
		  var that = this;
		  var keyword = e.detail.value;
		  that.keyword = keyword;
		  that.getdata();
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
      
		hideTimeDialog: function () {
		  this.levelDialogShow = false;
		},
		showdialog:function(e){
			this.tomid = e.currentTarget.dataset.id;
			console.log(this.tomid,'this.tomid');
			this.dialogShow = !this.dialogShow;
		},
		getXianxiaCount(){
			var that = this;
			app.post('ApiCoupon/getXianxiaCount', {couponid:that.couponid}, function (res) {
				that.couponnum = res.data;
			})
		},
		sendCoupon: function (e) {
		  var that = this;
			var formdata = e.detail.value;
			if(!that.tomid){
				app.error('请选择发放对象');
				return;
			}
			if(Number(formdata.send_num) > Number(that.couponnum)) {
				app.error('发放数量超出优惠券数量');
				return;
			}
			that.loading = true;
			app.post('ApiCoupon/sendXianxiaCoupon', {couponid:that.couponid,sendcount:formdata.send_num,tomid:that.tomid}, function (res) {
				that.loading = false;
				if(res.status ==1){
					app.alert(res.msg);
					that.dialogShow = !that.dialogShow;
					that.getdata();
				}else{
					app.alert(res.msg);
				}
			})
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

.content .item{width: 100%;padding: 32rpx;border-top: 1px #eaeaea solid;min-height: 112rpx;display:flex;align-items:center;}
.content .item:first-child{border:none}
.content .item image{width: 90rpx;height: 90rpx;border-radius:4px}
.content .item .f1{display:flex;flex:1;align-items:center;}
.content .item .f1 .t2{display:flex;flex-direction:column;padding-left:20rpx}
.content .item .f1 .t2 .x1{color: #333;font-size:26rpx;}
.content .item .f1 .t2 .x2{color: #999;font-size:24rpx;}

.content .item .f2{display:flex;flex-direction:column;width:250rpx;text-align:right;border-left:1px solid #eee}
.content .item .f2 .t1{ font-size: 40rpx;color: #666;height: 40rpx;line-height: 40rpx;}
.content .item .f2 .t2{ font-size: 28rpx;color: #999;height: 50rpx;line-height: 50rpx;}
.content .item .f2 .t3{ display:flex;justify-content:space-around;margin-top:10rpx; flex-wrap: wrap;}
.content .item .f2 .t3 .x1{height:40rpx;line-height:40rpx;padding:0 8rpx;border:1px solid #ccc;border-radius:6rpx;font-size:22rpx;color:#666;margin-top: 10rpx;}
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
	.t_date .x1{height:40rpx;line-height:40rpx;padding:0 8rpx;border:1px solid #ccc;border-radius:6rpx;font-size:22rpx;color:#666;margin-left: 10rpx;}
	.pstime-item{display:flex;border-bottom: 1px solid #f5f5f5;padding:20rpx 30rpx;}
	.pstime-item .radio{flex-shrink:0;width: 32rpx;height: 32rpx;background: #FFFFFF;border: 2rpx solid #BFBFBF;border-radius: 50%;margin-right:30rpx}
	.pstime-item .radio .radio-img{width:100%;height:100%}
	.btn{border-radius:28rpx;width:140rpx;height:56rpx;line-height:56rpx;color:#fff}
	
	.orderinfo{width:94%;margin:0 3%;border-radius:8rpx;margin-top:16rpx;padding: 14rpx 3%;background: #FFF;}
	.orderinfo .item{display:flex;width:100%;padding:20rpx 0;border-bottom:1px dashed #ededed;overflow:hidden}
	.orderinfo .item:last-child{ border-bottom: 0;}
	.orderinfo .item .t1{width:200rpx;flex-shrink:0}
	.orderinfo .item .t2{flex:1;text-align:right}
	.orderinfo .item .red{color:red}
	
	.ff_btn{ height:80rpx;line-height: 80rpx;width:90%;margin:0 auto;border-radius:40rpx;margin-top:40rpx;color: #fff;font-size: 28rpx;font-weight:bold}
</style>