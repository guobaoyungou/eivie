<template>
<block v-if="isload">
<view class="container" :style="{backgroundColor:t('color1')}" >
	<view class="contentbox">
		<image :src="pre_url + '/static/imgsrc/rank.png'" mode="widthFix">

			<view class="content">
					<!-- <view class="top">
						<view class="t1">当前区域： {{areafenhong}}</view>
					</view>
					<view class="top" >
							<view class="label">区域筛选：</view>
						<view style="padding:0 10rpx;">
							<picker mode="selector"  :range="provincearray" @change="provincechange">
							      <view class="picker">
									{{areafenhong_province?areafenhong_province:'请选择省份'}}
							      </view>
							</picker>
						</view>
						-
						<view style="padding:0 10rpx;">
							<picker mode="selector"  :range="cityarray" @change="citychange">
								  <view class="picker">
									{{areafenhong_city?areafenhong_city:'城市'}}
								  </view>
							</picker>
						</view>
						-
						<view style="padding:0 10rpx;">
							<picker mode="selector"  :range="areaarray" @change="areachange">
								  <view class="picker">
									{{areafenhong_area?areafenhong_area:'县区'}}
								  </view>
							</picker>
						</view>
					</view> -->
				<view class="top">
					<view class="t1">数据统计日期： {{region_ctime}}</view>
				</view>
				 <view class="tabsort">
					<view v-if="inArray(1,show_type)" style="width: 30%;" :class="'t1 '+(1==sorttype?'on':'')" :data-sorttype = "1" @tap="sorttypeChange" >订单金额
						<view class="before" v-if="1==sorttype" :style="'border-bottom:1rpx solid '+t('color1')"></view>
					</view>
					<view  v-if="inArray(2,show_type)" style="width: 30%;"  :class="'t1 '+(2==sorttype?'on':'')":data-sorttype = "2" @tap="sorttypeChange">订单数量
						<view class="before" v-if="2==sorttype" :style="'border-bottom:1rpx solid '+t('color1')"></view>
					</view>
				</view>
				<view class="tab1">
					<block v-for="(item, index) in  rank_type" :key="index" >
						<view :class="'t1 '+(index==ranktype?'on':'')" :style="{color:(index==ranktype?t('color1'):'')}" :data-ranktype = "index" @tap="toranktype">		
						{{item}}
						<view class="before" v-if="index==ranktype" :style="'border-bottom:1rpx solid '+t('color1')"></view>
						
						</view>
					</block>
				</view>
				
				<view class="tab">
					<view class="t1">排名</view>
					<view class="t2">{{ranktype == 3?'用户':'区域名'}}</view>
					<view class="t3" :style="{color:t('color1')}" v-if="sorttype ==1">订单金额</view>
					<view class="t3" :style="{color:t('color1')}" v-if="sorttype ==2">订单数量</view>
				</view>
			
				<view class="itembox">	
					<block v-for="(item, index) in datalist" :key="index" >
					<view class="item">
							<view class="t1" v-if="index<3"><image :src="pre_url+ '/static/img/comrank'+index+'.png'"></view>
							<view class="t1" v-else>{{index+1}}</view>
							<view class="t2" v-if="ranktype ==3"><image :src="item.headimg">{{item.nickname}}</view>
							<view class="t2" v-else> <text style="margin-left: 32rpx;"></text> {{item.title}}</view>
							<text class="t3" v-if="sorttype ==1"> {{item.totalprice}}</text>
							<text class="t3" v-if="sorttype ==2"> {{item.num}}</text>
					</view>
					</block>
				</view>
			</view>
	</view>

	<nodata v-if="nodata"></nodata>
	<nomore v-if="nomore"></nomore>
	<loading v-if="loading"></loading>
	<dp-tabbar :opt="opt"></dp-tabbar>
	<popmsg ref="popmsg"></popmsg>
</view>
</block>
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
			pre_url:app.globalData.pre_url,
			nodata: false,
			nomore: false,
			datalist: [],
			textset:{},
			pagenum: 1,
			rank_type: ['省','市','县/区','团队'],
			sysset:[],
			ranktype:0,
			regiondata: '',
			items: [],
			provincedata:[],
			citydata:[],
			
			areafenhong_province:'',
			areafenhong_city:'',
			areafenhong_area:'',
			areafenhong:'',
			
			provinceid:'',
			cityid:'',
			areaid:'',
			areafenhong_place:'区域筛选',
			areashow:0,

			provincearray:[],
			cityarray:[],
			areaarray:[],
			rankname:'',
			sorttype:0,
			region_ctime:'',
			show_type:[],
			
    };
  },

  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		var that = this;
		this.getdata();
		app.get('ApiIndex/getCustom',{}, function (customs) {
			var url = app.globalData.pre_url+'/static/area.json';
			if(customs.data.includes('region_ranking')) {
				url = app.globalData.pre_url+'/static/area.json';
			}
			uni.request({
				url: url,
				data: {},
				method: 'GET',
				header: { 'content-type': 'application/json' },
				success: function(res2) {
					
					that.provincedata = res2.data;
					var provincearray = [];
					for(var i =0;i< res2.data.length;i++){
						provincearray.push(res2.data[i]['text']);
					}
				
					that.provincearray = provincearray;
					
				}
			});
		});
		
  },
	onPullDownRefresh: function () {
		this.getdata();
	},
  // onReachBottom: function () {
  //   if (!this.nodata && !this.nomore) {
  //     this.pagenum = this.pagenum + 1;
  //     this.getdata(true);
  //   }
  // },
  methods: {
    getdata: function (loadmore) {
		if(!loadmore){
			this.pagenum = 1;
			this.datalist = [];
		}
		var that = this;
		var pagenum = that.pagenum;
		that.loading = true;
		that.nodata = false;
		that.nomore = false;
		var ranktype = that.ranktype;	
		var sorttype = that.sorttype;
		app.post('ApiAgent/regionagentrank', {ranktype:ranktype,pagenum: pagenum,areafenhong_province:this.areafenhong_province,areafenhong_city:this.areafenhong_city,areafenhong_area:this.areafenhong_area,sorttype:sorttype}, function (res) {
			that.loading = false;
			if(res.status ==0){
				app.error(res.msg);
			}
			 var data = res.data.data;
			that.areafenhong = res.areafenhong;
			that.region_ctime = res.region_ctime;
			that.show_type = res.region_show_type;
			that.sorttype = that.sorttype?sorttype:that.show_type[0];
			console.log(that.show_type,'that.show_type');
			
			if(res.data.status==0){
					app.alert(res.msg)
			}
			if (pagenum == 1) {
				that.textset = app.globalData.textset;
				uni.setNavigationBarTitle({
					title: '区域代理排行'
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
		});
    },
	toranktype:function (e) {
		var that=this
		var ranktype = e.currentTarget.dataset.ranktype;
		that.rankname = that.rank_type[ranktype];
		console.log(that.rankname);
		that.ranktype = ranktype
		that.getdata()
	},
	sorttypeChange:function(e){
		var that=this
		var sorttype = e.currentTarget.dataset.sorttype;
		that.sorttype = sorttype;
		that.getdata();
	},
	provincechange:function(e){
		var key = e.detail.value;
		this.areafenhong_province = this.provincearray[key];
		var children = this.provincedata[key]['children'];
		this.citydata = children;
		var cityarray = [];
		for(var i=0;i<children.length;i++){
			cityarray.push(children[i]['text']);
		}
		this.cityarray =cityarray;
		this.areaarray = [];
		this.areafenhong_city = '';
		this.areafenhong_area  = '';
		this.getdata();
	},
	citychange:function(e){
		var key = e.detail.value;
		this.areafenhong_city = this.cityarray[key];
		var children = this.citydata[key]['children'];

		var areaarray = [];
		for(var i=0;i<children.length;i++){
			areaarray.push(children[i]['text']);
		}
		this.areaarray = areaarray;
		this.areafenhong_area  = '';
		this.getdata();
	},
	areachange:function(e){
		var key = e.detail.value;
		this.areafenhong_area = this.areaarray[key];
		this.getdata();
	},
  }
};
</script>
<style>
	
.container{ padding: 20rpx; background: #FC3B36;}
.contentbox{ border-radius: 20rpx; width: 100%;}
.contentbox image{ border-top-left-radius: 10rpx; border-top-right-radius: 10rpx; width: 100%; border:none; display: block;}

.content{ background: #fff; display: flex; align-items: center; flex-direction: column; }
.content .top{ background: #F4F5F9; width: 90%;  margin-top: 20rpx; border-radius: 10rpx; display: flex; height: 70rpx; line-height: 70rpx; padding-left: 20rpx; display: flex; align-items: center; }
.content .top .border{ margin-right: 10rpx; height: 30rpx; border-right: 1rpx solid #999; margin: 0 30rpx; }
.content .tab{ display: flex; width: 90%; text-align: left;  line-height: 70rpx; margin-top: 20rpx; color: #666;}
.content .tab .t1{ width: 25%;}
.content .tab .t2{ width: 50%;padding-left: 20rpx;}
.content .tab .t3{ width: 30%;}

.content .tab1{ display: flex; border-bottom: 1rpx solid #dedede; width: 90%; height: 100rpx; line-height: 98rpx;}
.content .tab1 .t1{ text-align: center;margin-right: 30rpx;width: 15%;}
.content .tab1 .t1.on{ color:red;}
.content .tabsort{display: flex;  width: 90%; height: 100rpx; line-height: 98rpx;}
.content .tabsort .t1{ text-align: center;margin-right: 30rpx;width: 15%;}
.content .tabsort .t1.on{ color:red;}

.content .itembox{width:96%;}
.content .item{width:100%; display:flex;padding:40rpx 20rpx;border-radius:8px;margin-top: 6rpx;align-items:center;}
.itembox .item:first-child{  background-image: linear-gradient(to right , #FFF3E5, #FFFFFC)}
.itembox .item:nth-child(2){ background-image: linear-gradient(to right , #DDECFF, #FFFFFC)}
.itembox .item:nth-child(3){background-image: linear-gradient(to right , #FFE1DE, #FFFFFC)}



.content .item image{ width: 80rpx; height: 80rpx; border-radius: 50%; margin-right: 20rpx; }
.content .item .t1{color:#000000;font-size:30rpx;width: 25%; }
.content .item .t2{color:#666666;font-size:24rpx; width: 60%; display: flex; align-items: center;}
.content .item .t3{ width: 30%; font-weight: bold;}


.data-empty{background:#fff}
.applydata{width: 100%;background: #fff;padding: 0 24rpx;color:#333;padding-top: 20rpx;}
.form-item{width: 100%;padding:10rpx 0;display:flex;align-items: center;}
.input-value{line-height: 60rpx !important;border:none !important;padding: 0 10rpx !important;}
.selected-list{padding: 0 !important;}
.placeholder{color: #424242 !important;}
.nodata{background: #fff !important;} 
</style>