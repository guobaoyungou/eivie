<template>
<view class="container">
	<block v-if="isload">
		<block v-if="level_tab">
			<dd-tab :itemdata="tabdata" :itemst="tabitems" :st="st" :isfixed="false" @changetab="changetab" v-if="userlevel && userlevel.can_agent>=1"></dd-tab>
		</block>
		<view class="topsearch flex-y-center">
			<view class="f1 flex-y-center">
				<image class="img" :src="pre_url+'/static/img/search_ico.png'"></image>
				<input :value="keyword" placeholder="输入昵称/姓名/手机号搜索" placeholder-style="font-size:24rpx;color:#C2C2C2" @confirm="searchConfirm" @input="searchChange"></input>
			</view>
		</view>
		<view class="content">
      <view class="date-search" v-if="userlevel && userlevel.team_show_down_order">
        <view class="flex-bt">
          <view class="date-btn" :style="selectedDateRange == 1 ? 'color:#fff;background-color:'+t('color1') : ''" @click="setDate(1)">今日</view>
          <view class="date-btn" :style="selectedDateRange == 2 ? 'color:#fff;background-color:'+t('color1') : ''" @click="setDate(2)">近七日</view>
          <view class="date-btn" :style="selectedDateRange == 3 ? 'color:#fff;background-color:'+t('color1') : ''" @click="setDate(3)">近30日</view>
          <view class="date-btn" :style="selectedDateRange == 4 ? 'color:#fff;background-color:'+t('color1') : ''" @click="toSelectDate">自定义</view>
        </view>
        <view class="show-search-date" v-if="sdate && edate">{{sdate}} / {{edate}}</view>
      </view>
			<view class="topsearch flex-y-center sx" v-if="userlevel && userlevel.team_month_data==1">
				<text class="t1">日期筛选：</text>
				<view class="body_data" style="min-width: 200rpx;" @click="toCheckDate"> {{startDate?startDate:'点击选择日期'}}{{endDate?' 至 '+endDate:''}}
					<!-- <img class="body_detail" :src="pre_url+'/static/img/week/week_detail.png'" /> -->
				</view>
				<view class="t_date">
					<view v-if="startDate" class="x1" @tap="clearDate">清除</view>
				</view>
			</view>
			<view class="topsearch flex-y-center sx" v-if="(team_auth==1 && userlevel && userlevel.team_month_data==1) || !showlevel">
				<text class="t1" v-if="!showlevel">等级筛选：</text>
				<text class="t1" v-else>身份筛选：</text>
				<view class="t2" @tap="chooseLevel">
					<view class="uni-input" v-if="checkLevelname">{{checkLevelname}}</view>
					<view style="font-size:28rpx;color: #686868" v-else>请选择级别</view>
				</view>
			</view>
			<view class="topsearch flex-y-center sx" v-if="userinfo.month_yeji_show">
				<text class="t1">月份筛选：</text>
				<view class="t2">
					<picker @change="chooseMonth" :range="month_item">
						<view class="uni-input" v-if="month_item[monthindex]">{{month_item[monthindex]}}</view>
						<view style="font-size:28rpx;color: #686868" v-else>请选择月份</view>
					</picker>
				</view>
			</view>
		<!-- <view class="topsearch flex-y-center" v-if="userlevel && userlevel.team_month_data==1">
			<text class="t1">日期筛选：</text>
			<view class="example-body" style="width: 500rpx;">
				<uni-datetime-picker
					v-model="range"
					type="daterange"
					start=""
					end=""
					rangeSeparator="至"
				/>
			</view>
		</view> -->
			<view class="yejilabel">
				<block v-if="custom && custom.yeji_with_pronum">
					<!-- 定制：业绩显示个人和团队商品数量，其他不显示 -->
					<view class="t1" v-if="userlevel">个人业绩：{{is_end==1?userinfo.yeji_pronum:'计算中'}}，{{t('团队')}}业绩：{{is_end==1?userinfo.team_yeji_pronum:'计算中'}}</view>
				</block>
				<block v-else>
					<!-- 默认团队业绩 -->
					<view class="t1" v-if="userlevel">{{t('团队')}}业绩：{{is_end==1?userinfo.team_yeji_total:'计算中'}} 元</view>
					<view class="t1" v-if="userlevel && userinfo.miniyeji_show">小市场业绩：{{is_end==1?userinfo.team_miniyeji_total:'计算中'}}  元</view>
					<view class="t1" v-if="userlevel && userinfo.maxyeji_show">大市场业绩：{{is_end==1?userinfo.team_maxyeji_total:'计算中'}}  元</view>
					<view class="t1" style="width:100%" v-if="userinfo && userinfo.next_ordermoney_show">距升级{{t('团队业绩')}}：{{is_end==1?userinfo.next_up_ordermoney:'计算中'}} 元</view>
					<view class="t1" v-if="userlevel && userinfo.month_yeji_show">{{month_text}}：{{is_end==1?userinfo.now_month_yeji:'计算中'}} 元</view>
					<view class="t1" v-if="userlevel && userlevel.team_show_down_order">总成单量：{{userinfo.team_order_count}} 单</view>
					<view class="t1" v-if="userlevel && userlevel.team_show_down_order">总退款单数：{{userinfo.team_refund_order}} 单</view>
					<view class="t1" v-if="userlevel && userlevel.team_show_down_order">成交总额：{{userinfo.team_order_money}} 元</view>
					<view class="t1" v-if="userlevel && userlevel.team_yeji_zhitui">直推业绩：{{is_end==1?userinfo.team_yeji_zhitui:'计算中'}} 元</view>
					<view class="t1" v-if="userlevel && userlevel.team_yeji_jiantui">间推业绩：{{is_end==1?userinfo.team_yeji_jiantui:'计算中'}} 元</view>
					<view class="t1" v-if="userlevel && userlevel.team_yeji_current_month">当月团队业绩：{{is_end==1?userinfo.team_yeji_current_month:'计算中'}} 元</view>
				</block>
			</view>
		</view>

		<view class="content" v-if="datalist && datalist.length>0">
			<view class="label flex-bt">
				<text class="t1"   v-if="zt_member_limit != 0 && st == 1">直推名额：{{zt_member_limit}}</text>
				<button class="btn1" :style="{'backgroundColor':t('color1')}" @tap="goto" :data-url="'/pagesC/my/downshoporder'" v-if="userlevel.downorder_list">下级订单</button>
			</view>
			<view class="levelup-num" v-if="levelup_uesnum && levelup_uesnum.length > 0">
				<text class="t1">可升级数量</text>
<view class="flex list">
						<view class="num" v-for="(item,index) in levelup_uesnum" :key="index">{{item.name}}：{{item.num}} 个</view>
					</view>
			</view>
			
			<!-- 给下级升级 人数 -->
			<view class="label">
				<text class="t1">成员信息</text>
				<text class="t2">来自TA的{{t('佣金')}}</text>
			</view>
			<block v-for="(item, index) in datalist" :key="index">
				<view class="item">
					<view class="f1" @click="toteam(item.id)">
						<image :src="item.headimg" mode="widthFix"></image>
						<view class="t2">
							<text class="x1">{{item.nickname}}(ID:{{item.id}})</text>
							<block v-if="custom && custom.team_show_visittime">
								<text class="x2">加入时间：{{item.createtime}}</text>
								<text class="x2">最后访问：{{item.last_visittime}}</text>
							</block>
							<text v-else class="x2">{{item.createtime}}</text>
							<text class="x2">等级：{{item.levelname}}</text>
							<text class="x2" v-if="item.tel">手机号：{{item.tel}}</text>
							<text class="x2" v-if="item.pid">推荐人ID：{{item.pid}}</text>
								<block v-if="custom && custom.yx_gift_pack" >
								<text class="x3">礼包订单：{{item.packcount}}</text>
								<text class="x3" >订单金额：￥{{item.totalpackmoney}}</text>
							</block>
							<text class="x2" v-if="item.xianxia_sales">出券量：{{item.xianxia_sales}}组</text>
							<block v-if="item.custom_field" v-for="(v,k) in item.custom_field" :key="k">
								<view v-if="v.key == 'upload'">
									<text class="x2" >{{v.field}}：</text>
									<image :src="v.value" style="max-width: 60rpx;height: 60rpx;" mode="widthFix"></image>
								</view>
								<text class="x2" v-else>{{v.field}}：{{v.value}}</text>
							</block>
						</view>
					</view>
					<view class="f2">
						<text class="t4" v-if="userlevel && userlevel.team_yeji==1">{{t('团队')}}业绩：{{item.teamyeji}}</text>
						<text class="t4" v-if="userlevel && userlevel.team_self_yeji==1">个人业绩：{{item.selfyeji}}</text>
						<text class="t4" v-if="userlevel && userlevel.team_down_total==1">{{t('一级')}}人数：{{item.team_down_total}} 人</text>
						<text class="t4" v-if="userlevel && userlevel.team_score==1">{{t('积分')}}：{{item.score}}</text>
            <text class="t4" v-if="userlevel && userlevel.team_view_zhitui_member_num==1">{{t('一级')}}：{{item.team_view_zhitui_member_num}}人</text>
            <text class="t4" v-if="userlevel && userlevel.team_show_down_order==1" @tap.stop="toDown" :data-mid="item.id" style="text-decoration: underline;">成交订单：{{item.team_order_count}}(单)</text>
            <text class="t4" v-if="userlevel && userlevel.team_show_down_order==1">成交金额：{{item.team_order_money}}(元)</text>
						<text class="t1">+{{item.commission}}</text>
						<!-- <text class='t2'>{{item.downcount}}个成员</text> -->
						<view class="t3">
							<view v-if="userlevel && userlevel.team_givemoney==1" class="x1" @tap="givemoneyshow" :data-id="item.id">转{{t('余额')}}</view>
							<view v-if="userlevel && userlevel.team_givescore==1" class="x1" @tap="givescoreshow" :data-id="item.id" >转{{t('积分')}}</view>
							<view v-if="userlevel && userlevel.team_levelup==1" class="x1"   @tap="showDialog" :data-id="item.id" :data-levelid="item.levelid" :data-levelsort="item.levelsort">升级</view>
              <view v-if="userlevel && userlevel.team_shortvideo==1" class="x1" @tap="goto" :data-url="'/pagesA/shortvideo/looklog?mid=' + item.id">短视频记录</view>
              <view v-if="userlevel && userlevel.team_callphone==1 && item.tel" class="x1" @tap.stop="callphone" :data-phone="item.tel">拨打电话</view>
              <view v-if="userlevel && userlevel.team_view_down_to_down==1 && item.team_view_zhitui_member_num > 0" class="x1" @tap.stop="toDown" :data-mid="item.id">人员</view>
              <view v-if="custom && custom.team_member_history" class="x1" @tap.stop="goto" :data-url="'/pagesExt/my/history?mid='+item.id">查看足迹</view>
              <view v-if="userlevel && userlevel.team_update_member_info==1" class="x1" @tap.stop="goto" :data-url="'/pagesC/my/saveinfo?mid='+item.id">修改资料</view>
						<view v-if="item.can_change_pid==1" class="x1" @tap="changedown(item.id)" >链动换位</view>
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
		
		<view v-if="dialogShow" class="popup__container">
			<view class="popup__overlay" @tap.stop="showDialog"></view>
			<view class="popup__modal">
				<view class="popup__title">
					<text class="popup__title-text">升级</text>
					<image :src="pre_url+'/static/img/close.png'" class="popup__close" style="width:36rpx;height:36rpx" @tap.stop="showDialog"/>
				</view>
				<view class="popup__content">
					<view class="sheet-item" v-for="(item, index) in levelList" :key="index">
						<text class="item-text flex-item">{{item.name}}</text>
						<view class="flex1"></view><view @tap="changeLevel" :data-id="item.id" :data-name="item.name" v-if="item.id != tempLevelid && item.sort > tempLevelsort" :style="{'color':t('color1')}">选择</view><view v-else style="color: #ccc;">选择</view>
					</view>
				</view>
			</view>
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
		<!--换位弹窗-->
		<uni-popup id="dialogChangeDown" ref="dialogChangeDown" type="center" background-color='#fff' >
			<view class="popup_centent_view">
				<view class='popup_title_view-c'>链动换位</view>
				<view class="popup_input-view">
					<view class="options-view flex ">
						<view class="title-text" style="width: 30%;">选择会员：</view>
						<view class="flex flex-xy-center num-options">
							{{select_member.nickname}}(ID:{{select_member.id}})
						</view>
					</view>
					<view class="options-view flex ">
						<view class="title-text" style="width: 30%;">换位会员:</view>
						<view class="flex flex-xy-center num-options">
							<radio-group class="flex" name="new_down" style="flex-wrap:wrap" @change="olddownchange" >
								<label class="flex-y-center" v-for="(item,idx) in change_downs" >
									<radio class="radio" :value="item.mid" style="transform: scale(0.8);"/>{{item.nickname}}(ID：{{item.mid}})
								</label>
							</radio-group>
						</view>
					</view>
				</view>
				<view class="popup_but_view">
					<view class='poput_options_but' @tap="closechangedown()">取消</view>
					<view class='poput_options_but success' :style="{'backgroundColor':t('color1')}" @tap="tochange()">确认换位</view>
				</view>
			</view>
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
		startDate: '',
		endDate: '',
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
		is_end:0,
		levelup_uesnum:[],
		custom_field:[],
		change_mid:0,//要换位的下级会员
		select_member:{},//选择的下级会员
		change_downs:[],//当前会员已经脱离的下级
		old_down:0,
    sdate:'',//订单筛选时间 开始时间
    edate:'',//订单筛选时间 结束时间
    selectedDateRange:'',//选中时间筛选
    first_mid:0,//查看成交订单mid
		showlevel:true,
		level_tab:true
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
			var date_start = that.startDate;
			var date_end = that.endDate;
			var checkLevelid = that.checkLevelid;
			var month_search = that.month_value;	
			var first_mid = that.first_mid;	
      app.post('ApiAgent/team', {st: st,pagenum: pagenum,keyword:keyword,mid:mid,date_start:date_start,date_end:date_end,checkLevelid:checkLevelid,month_search:month_search,version:'2.6.3',first_mid:first_mid}, function (res) {
        var data = res.datalist;
        if (pagenum == 1) {
					that.userinfo = res.userinfo;
					that.userlevel = res.userlevel;
					that.textset = app.globalData.textset;
					that.datalist = data;
					that.levelList = res.levelList;
					that.month_item = res.month_item;
					that.levelup_uesnum = res.levelup_uesnum;
					console.log(that.levelup_uesnum,'that.levelup_uesnum');
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
					if(res.show_business){
						that.tabdata = that.tabdata.concat(['推荐商家'+'('+res.tjbusinessCount+')'])
						that.tabitems = that.tabitems.concat(['5']);
					}
					that.team_auth = res.team_auth;
					that.allLevel = res.all_level;
					that.showlevel = res.showlevel;
					that.level_tab = res.level_tab;
				
					//自定义集合
					that.custom = res.custom;
					
					//会员等级直推人数
					if(res.userlevel && res.userlevel.zt_member_limit){
						that.zt_member_limit = res.userlevel.zt_member_limit
					}
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
				that.loading = false;
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
		   app.get('ApiAgent/get_team_yeji', {mid:mid,date_start:date_start,date_end:date_end,checkLevelid:checkLevelid,month_search:month_search,sdate:that.sdate,edate:that.edate,first_mid:that.first_mid}, function (res) {
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
      var teamorder = 0;
      if(member.team_order_count=='计算中' || member.team_order_money=='计算中'){
        teamorder = 1;
      }
			if(member.teamyeji=='计算中' || teamorder){
			  var data = await that.getyeji(member.id);
        if(member.teamyeji == '计算中'){
          member.teamyeji = data.teamyeji || 0;
          member.selfyeji = data.selfyeji || 0;
          member.team_down_total = data.team_down_total || 0;
        }
			  
        if(teamorder){
          member.team_order_count = data.team_order_count || 0;
          member.team_order_money = data.team_order_money || 0;
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
			if(this.loading) return;
			this.st = st;
			if(st==5){
				app.goto('/pagesExt/commission/tjbusinessList');
				return;
			}
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
			that.startDate = '';
			that.endDate = '';
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
		levelRadioChange: function (e) {
		  var that = this;
		  that.checkLevelname = e.currentTarget.dataset.name;
		  that.checkLevelid = e.currentTarget.dataset.id;
		  that.levelDialogShow = false;
		  that.getdata();
		},
		chooseMonth:function(e){
			this.monthindex  = e.detail.value;
			this.month_value = this.month_item[this.monthindex]
			this.month_text =this.month_value +'团队总业绩';
			this.getdata();
		},
    //查看下级人员、查看下级成交订单
    toDown: function (e) {
      if(this.first_mid == 0){
        this.first_mid = this.userinfo.id;
      }
      app.goto('/activity/commission/myteam?mid='+e.currentTarget.dataset.mid+'&first_mid='+this.first_mid);
    },
	changedown:function(mid){
		var that = this;
		that.change_mid = mid;
		app.showLoading();
		app.get('ApiAgent/change_down_user', {now_down: mid}, function (data) {
			app.showLoading(false);
			if(data.status!=1){
				app.alert(data.msg);
				return;
			}
			that.select_member = data.member;
			that.change_downs = data.change_downs;
			that.$refs.dialogChangeDown.open();
		});
	},
	tochange:function(mid){
		var that = this;
		var select_member = that.select_member;
		var old_down = that.old_down;
		app.confirm('确认现会员ID:'+select_member.id+'与'+old_down+'换位?', function () {
			app.showLoading();
		  app.post('ApiAgent/change_down_user', {new_down: select_member.id,old_down:old_down}, function (data) {
			  if(data.status!=1){
				  app.error(data.msg);
				  return;
			  }
			app.showLoading(false);
			app.success(data.msg);
			that.old_down = 0;
			that.$refs.dialogChangeDown.close();
			setTimeout(function () {
			  that.getdata();
			}, 1000);
		  });
		});
		
	},
	closechangedown:function(){
		this.$refs.dialogChangeDown.close();
	},
	olddownchange:function(e){
		console.log(e);
		var old_down = e.detail.value;
		this.old_down = old_down;
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