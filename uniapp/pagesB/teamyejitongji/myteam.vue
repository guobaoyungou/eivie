<template>
<view class="container">
	<block v-if="isload">
		<view class="topsearch flex-y-center">
			<view class="f1 flex-y-center">
				<image class="img" :src="pre_url+'/static/img/search_ico.png'"></image>
				<input :value="keyword" placeholder="输入昵称/姓名/手机号搜索" placeholder-style="font-size:24rpx;color:#C2C2C2" @confirm="searchConfirm" @input="searchChange"></input>
			</view>
		</view>
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
			<view class="yejilabel">
				<text class="t1" v-if="(userinfo.set_tongji.slje==2 || userinfo.set_tongji.slje==3) && userlevel.team_yeji==1">{{t('团队')}}总{{t( userinfo.set_tongji.yeji_name)}}：{{is_end==1?userinfo.team_yeji_total:'计算中'}} 元</text>
				<text class="t1" v-if="userinfo.set_tongji.slje==1 || userinfo.set_tongji.slje==3">商品总数量：{{is_end==1?userinfo.team_num_total:'计算中'}} </text>
			</view>
		</view>
		<view class="content" v-if="datalist && datalist.length>0">
			<view class="label" v-if="zt_member_limit != 0 && st == 1">
				<text class="t1">团队人数：{{zt_member_limit}}</text>
			</view>
			<view class="label">
				<text class="t1">成员信息</text>
				<text class="t2" v-if="userinfo.set_tongji.yeji_name">来自TA的{{t( userinfo.set_tongji.yeji_name)}}</text>
				<text class="t2" v-else>来自TA的{{t('佣金')}}</text>
			</view>
			<block v-for="(item, index) in datalist" :key="index">
				<view class="item">
					<view class="f1" @click="toteam(item.id)">
						<image :src="item.headimg"></image>
						<view class="t2">
							<text class="x1">{{item.nickname}}(ID:{{item.id}})</text>
							<text class="x2">{{item.createtime}}</text>
							<text class="x2">等级：{{item.levelname}}</text>
							<text class="x2" v-if="item.tel">手机号：{{item.tel}}</text>
							<text class="x2" v-if="item.pid">推荐人ID：{{item.pid}}</text>
						</view>
					</view>
					<view class="f2">
						<text class="t4" v-if="(userinfo.set_tongji.slje==2 || userinfo.set_tongji.slje==3)"  @tap="goto" :data-url="'/pagesB/teamyejitongji/goodsinfo?type=1&mid=' + item.id" style="color: #0F5EE5;font-size: 23rpx !important;">{{t('团队')}}{{t( userinfo.set_tongji.yeji_name)}}：{{item.teamyeji}}</text>
<!--						<text class="t4" v-if="userlevel && userlevel.team_self_yeji==1">个人业绩：{{item.selfyeji}}</text>-->
						<text class="t4" v-if="(userinfo.set_tongji.slje==1 || userinfo.set_tongji.slje==3) " @tap="goto" :data-url="'/pagesB/teamyejitongji/goodsinfo?type=0&mid=' + item.id" style="color: #0F5EE5;font-size: 23rpx !important;">商品数量：{{item.goodsnum}} </text>
<!--						<text class="t4" v-if="userlevel && userlevel.team_score==1">{{t('积分')}}：{{item.score}}11</text>-->
<!--						<text class="t1">+{{item.commission}}</text>-->
						<!-- <text class='t2'>{{item.downcount}}个成员</text> -->
						<view class="t3">
							<view v-if="userlevel && userlevel.team_givemoney==1" class="x1" @tap="givemoneyshow" :data-id="item.id">转{{t('余额')}}</view>
<!--							<view v-if="userlevel && userlevel.team_givescore==1" class="x1" @tap="givescoreshow" :data-id="item.id" >转{{t('积分')}}</view>-->
						</view>
					</view>
				</view>
			</block>
		</view>
		<uni-popup id="dialogmoneyInput" ref="dialogmoneyInput" type="dialog">
			<uni-popup-dialog mode="input" title="转账金额" value="" placeholder="请输入转账金额" @confirm="givemoney"></uni-popup-dialog>
		</uni-popup>
		<uni-popup id="dialogscoreInput" ref="dialogscoreInput" type="dialog">
			<uni-popup-dialog mode="input" title="转账数量" value="" placeholder="请输入转账数量" @confirm="givescore"></uni-popup-dialog>
		</uni-popup>
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
      app.post('ApiTeamYejiTongji/team', {st: st,pagenum: pagenum,keyword:keyword,mid:mid,date_start:date_start,date_end:date_end,checkLevelid:checkLevelid,month_search:month_search}, function (res) {
				that.loading = false;
        var data = res.datalist;
        if (pagenum == 1) {
					that.userinfo = res.userinfo;
					that.userlevel = res.userlevel;
					that.textset = app.globalData.textset;
					that.datalist = data;
					that.levelList = res.levelList;
					that.month_item = res.month_item;
					if(res.userlevel && res.userlevel.can_agent==2){
						that.tabdata = [that.t('一级')+'('+res.userinfo.myteamCount1+')',that.t('二级')+'('+res.userinfo.myteamCount2+')'];
						that.tabitems = ['1','2'];
					}else if(res.userlevel && res.userlevel.can_agent==3){
						that.tabdata = [that.t('一级')+'('+res.userinfo.myteamCount1+')',that.t('二级')+'('+res.userinfo.myteamCount2+')',that.t('三级')+'('+res.userinfo.myteamCount3+')'];
						that.tabitems = ['1','2','3'];
					}else{
						that.tabdata = [that.t('一级')+'('+res.userinfo.myteamCount1+')'];
						that.tabitems = ['1'];
					}
					if(res.team_auth){
						that.tabdata = that.tabdata.concat([that.t('团队')+'('+res.userinfo.myteamCount4+')'])
						that.tabitems = that.tabitems.concat(['4']);
					}
					that.team_auth = res.team_auth;
					that.allLevel = res.all_level;
				
					//自定义集合
					that.custom = res.custom;
					
					//会员等级直推人数
					if(res.userlevel && res.userlevel.zt_member_limit){
						that.zt_member_limit = res.userlevel.zt_member_limit
					}
					if (data.length == 0) {
						that.nodata = true;
					}

          if(that.userinfo.set_tongji && that.userinfo.set_tongji.yeji_name) {
            uni.setNavigationBarTitle({
              title: '团队' + that.userinfo.set_tongji.yeji_name
            });
          }

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
			var month_search = that.month_value;	
		   app.get('ApiTeamYejiTongji/get_team_yeji', {mid:mid,date_start:date_start,date_end:date_end,checkLevelid:checkLevelid,month_search:month_search}, function (res) {
				var data = res.data
				that.userinfo.team_yeji_total = data.team_yeji_total || 0;
				that.userinfo.team_num_total = data.team_num_total || 0;
				that.userinfo.team_miniyeji_total = data.team_miniyeji_total || 0;
				that.userinfo.next_up_ordermoney = data.next_up_ordermoney || 0;
				that.userinfo.now_month_yeji = data.now_month_yeji || 0;
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
			   member.teamyeji = data.teamyeji || 0;
			   member.selfyeji = data.selfyeji || 0;
			   member.goodsnum = data.goodsnum || 0;
			   member.team_down_total = data.team_down_total || 0;
			   datalist[i] = member;
			}
		}
		that.datalist = datalist;
		console.log('请求完成2'); // 处理响应
	},
	getyeji: function (mid) {
		var that = this;
		console.log('请求'+mid);
    var date_start = that.startDate;
    var date_end = that.endDate;
		return new Promise((resolve, reject) => {
		   app.get('ApiTeamYejiTongji/get_member_yeji', {mid:mid,date_start:date_start,date_end:date_end}, function (res) {
				var data = res.data
				resolve(data);
			});
		   
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
		givemoneyshow:function(e){
			var that = this;
			var id = e.currentTarget.dataset.id;
			that.tomid = id;
			that.$refs.dialogmoneyInput.open();
		},
		givescoreshow:function(e){
			var that = this;
			var id = e.currentTarget.dataset.id;
			that.tomid = id;
			that.$refs.dialogscoreInput.open();
		},
		givemoney:function(done, money){
			var that = this;
			var id = that.tomid;
			app.showLoading('提交中');
			app.post('ApiAgent/givemoney', {id:id,money:money}, function (res) {
				app.showLoading(false);
				if (res.status == 0) {
          app.error(res.msg);
        } else {
          app.success(res.msg);
					that.getdata();
					that.$refs.dialogmoneyInput.close();
				}
			})
		},
		givescore:function(done, score){
			var that = this;
			var id = that.tomid;
			app.showLoading('提交中');
			app.post('ApiAgent/givescore', {id:id,score:score}, function (res) {
				app.showLoading(false);
				if (res.status == 0) {
          app.error(res.msg);
        } else {
          app.success(res.msg);
					that.getdata();
					that.$refs.dialogscoreInput.close();
				}
			})
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
		changeLevel: function (e) {
			var that = this;
			var mid = that.tempMid;
			var levelId = e.currentTarget.dataset.id;
			var levelName = e.currentTarget.dataset.name;
			app.confirm('确定要升级为'+levelName+'吗?', function () {
				app.showLoading('提交中');
			  app.post('ApiAgent/levelUp', {mid: mid,levelId:levelId}, function (res) {
					app.showLoading(false);
					if (res.status == 0) {
					  app.error(res.msg);
					} else {
						app.success(res.msg);
						that.dialogShow = false;
						that.getdata();
					}
			  });
			});
		},
		toteam:function(mid){
			if(this.team_auth){
				uni.navigateTo({
					url:'/activity/commission/myteam?mid='+mid
				})
			}
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