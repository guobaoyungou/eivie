<template>
<view class="container">
	<block v-if="isload">
		<view class="search-container">
			<view class="search-navbar">
				<view @tap.stop="showFilter('has_job','状态')" class="search-navbar-item">
					<view class="text">{{has_job_text?has_job_text:'状态'}}</view><image class="down" src="../../static/img/arrowdown.png"></image>
				</view>
				<view @tap.stop="showFilter('job','岗位')" class="search-navbar-item">
					<view class="text">{{cnames?cnames:'岗位'}}</view><image class="down" src="../../static/img/arrowdown.png"></image>
				</view>
				<view @tap.stop="showFilter('salary','薪资')" class="search-navbar-item">
					<view class="text">{{salaryindex>-1?salarylist[salaryindex]:'薪资'}}</view><image class="down" src="../../static/img/arrowdown.png"></image>
				</view>
				<view @tap.stop="showFilter('sex','性别')" class="search-navbar-item">
					<view class="text">{{sex_text?sex_text:'性别'}}</view><image class="down" src="../../static/img/arrowdown.png"></image>
				</view>
				<!-- <view class="search-navbar-item flex-x-center flex-y-center" @click.stop="showFilter(0)">筛选 <text :class="'iconfont iconshaixuan ' + (showfilter?'active':'')"></text></view> -->
			</view>
		</view>
		<view class="product-container">
			<block v-if="datalist && datalist.length>0">
				<dp-qiuzhi-itemlist :data="datalist" :menuindex="menuindex"></dp-qiuzhi-itemlist>		
			</block>
			<nomore text="没有更多信息了" v-if="nomore"></nomore>
			<nodata text="没有查找到相关信息" v-if="nodata"></nodata>
			<loading v-if="loading"></loading>
		</view>
	
		<!-- 检索条件 Start -->
		<view v-if="isshowfilter" class="popup__container popup_filter">
			<view class="popup__overlay" @tap.stop="hideFilter"></view>
			<view class="popup__modal">
				<view class="popup__title">
					<!-- <text class="popup__title-text">请选择{{filterName}}</text>-->
					<view class="popup_cancel"></view> 
					<text class="popup__title-text">请选择{{filterName}}</text>
					<view class="popup_ok" @tap.stop="filterConfirm">确定</view>
				</view>
				<view class="popup__content">
					<view v-if="filterType=='job'" class="flex-sb filter-cid">
						<view class="cate-left">
							<block v-for="(item,index1) in categorylist" :key="index1">
								<view class="flex-s" @tap="changeCategory" :data-index="index1" :class="cindex==index1?'on':''"><text class="dot"></text><text>{{item.name}}</text></view>
							</block>
						</view>
						<view class="cate-right choose-box">
							<block v-for="(item,index2) in curcategory" :key="index2">
								<view class="choose-item" @tap="choosecid" :data-index="index2" :class="item.checked?'on':''">{{item.name}}</view>
							</block>
						</view>
					</view>
					<view v-if="filterType=='salary'">
						<block v-for="(item,index) in salarylist" :key="index">
							<view class="filter-selector" :class="salaryindex==index?'on':''" @tap="chooseSelector" :data-index="index" :data-filtertype="filterType">{{item}}</view>
						</block>
					</view>
					<!--  -->
					<view v-if="filterType=='has_job'">
						<view class="filter-selector" @tap="chooseFilter" :class="has_job==1?'on':''" :data-field="filterType" data-text="在职" data-value="1">在职</view>
						<view class="filter-selector" @tap="chooseFilter" :class="has_job==2?'on':''" :data-field="filterType" data-text="离职" data-value="2">离职</view>
					</view>
					<view v-if="filterType=='sex'">
						<view class="filter-selector" @tap="chooseFilter" :class="sex==1?'on':''" :data-field="filterType"  data-text="男" data-value="1">男</view>
						<view class="filter-selector" @tap="chooseFilter" :class="sex==2?'on':''" :data-field="filterType"  data-text="女" data-value="2">女</view>
					</view>
				</view>
				
				<view class="popup__bottom flex-sb">
					<view class="btn btn-reset" @tap="filterReset">重置</view>
					<view class="btn" @tap="filterConfirm" :style="{background:'linear-gradient(-90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)',color:'#ffffff'}">确定</view>
				</view>
			</view>
		</view>
		<!-- 检索条件 End -->
		<view class="tosign" @tap="goto" data-url="add" :style="{background:'linear-gradient(-90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)',color:'#ffffff'}">
			<view class="v">发布</view>
		</view>
	</block>
	<dp-tabbar :opt="opt" @getmenuindex="getmenuindex"></dp-tabbar>
	<loading v-if="loading"></loading>
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

			nomore:false,
			nodata:false,
      keyword: '',
      pagenum: 1,
      datalist: [],
      history_list: [],
      history_show: false,
      order: '',
			field:'',
			items:[],
  
      isshowfilter: false,
			filterType:'',
			filterName:'',
			cindex:-1,
			curcategory:[],
			cids:[],
			cnames:'',
			categorylist:[],
			salarylist:[],
			salaryindex:-1,
			
			/* 福利待遇 */
			welfarelist:[],
			welfareindex:[],
			welfarenames:'',
			/* 城市 */
			citylist:[],
			provinceindex:-1,
			cityindex:-1,
			area_name:'',
			showarea:'',
			sex:'',
			sex_text:'',
			has_job:'',
			has_job_text:'',
			latitude:'',
			longitude:''
    };
  },
  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.bid = this.opt.bid ? this.opt.bid : 0;
		//console.log(this.bid);
		if(this.opt.keyword) {
			this.keyword = this.opt.keyword;
		}
		this.opt.defaultIndex = 99;
		this.getdata();
  },
	onPullDownRefresh: function () {
		this.getdata();
	},
  onReachBottom: function () {
    if (!this.nodata && !this.nomore) {
      this.pagenum = this.pagenum + 1;
      this.getlist(true);
    }
  },
  methods: {
		getdata:function(){
			var that = this;
			that.pagenum = 1;
			that.datalist = [];
			var cid = that.opt.cid;
			var gid = that.opt.gid;
			var bid = that.opt.bid ? that.opt.bid : '';
			var cid2 = that.cid2;
			that.loading = true;
			app.get('ApiZhaopin/zhaopinSet', {}, function (res) {
				that.loading = false;
				var set = res.zhaopinset
				if(set){
					that.categorylist = res.category;
					that.salarylist = set.salary;
					if(that.categorylist.length>0){
						that.curcategory = that.categorylist[0]['child']
						that.cindex = 0
					}
					var welfare = set.welfare
					var welfarelist = [];
					if(welfare.length>0){
						for(var i in welfare){
							var wl = {}
							wl.name = welfare[i]
							wl.checked = 0
							welfarelist.push(wl)
						}
					}
					that.welfarelist = welfarelist
				}
				
				//地区加载
				uni.request({
					url: app.globalData.pre_url+'/static/area.json',
					data: {},
					method: 'GET',
					header: { 'content-type': 'application/json' },
					success: function(res2) {
						that.items = res2.data
						that.provinceindex = 0
						that.citylist = that.items[0].children
					}
				});
				that.loaded();
				that.getlist();
			});
		},
		searchConfirm:function(e){
			this.getlist(false)
		},
    getlist: function (loadmore) {
      var that = this;
			if(!loadmore){
				this.pagenum = 1;
				this.datalist = [];
			}
      var pagenum = that.pagenum;
      var keyword = that.keyword;
			var cachearea = app.getCache('user_current_area');
			var cachearea_show = app.getCache('user_current_area_show');
			var cachelongitude = app.getCache('user_current_longitude');
			var cachelatitude = app.getCache('user_current_latitude');
			//当前位置获取
			if(!cachearea || cachearea==-1){
				//初始化当前城市
				app.getLocation(function(res){
					var longitude = res.longitude;
					var latitude = res.latitude;
					app.get('ApiAddress/getAreaByLocation',{longitude:longitude,latitude:latitude},function(data){
						var area = [];
						var showarea = '';
						if(data.province){
							area.push(data.province)
							showarea = data.provice
						}
						if(that.showlevel>1 && data.city){
							area.push(data.city)
							showarea = data.city
						}
						if(that.showlevel>2 && data.district){
							area.push(data.district)
							showarea = data.district
						}
						that.area_name = area.join(',')
						that.showarea = showarea
						//全局缓存
						app.setCache('user_current_latitude',latitude)
						app.setCache('user_current_longitude',longitude)
						app.setCache('user_current_area',that.area_name)
						app.setCache('user_current_area_show',that.showarea)
					});
				});
			}else{
				that.area_name = cachearea
				that.showarea = cachearea_show
			}
			//位置end
      app.post('ApiZhaopin/qiuzhiList',{
				pagenum: pagenum,
				keyword:that.keyword,
				cids:that.cids,
				salary:that.salarylist[that.salaryindex],
				sex:that.sex,
				has_job:that.has_job,
				area:that.area_name
			}, function (res) {
				that.loading = false;
        var data = res.data;
        if (pagenum == 1) {
          that.datalist = data;
          if (data.length == 0) {
            that.nodata = true;
          }
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
		showFilter:function(type,name){
			var that = this
			that.filterType = type
			that.filterName = name
			that.isshowfilter = true
		},
		hideFilter:function(e){
			this.isshowfilter = false
		},
    searchChange: function (e) {
      this.keyword = e.detail.value;
    },
		filterConfirm(){
			this.getlist(false);
			this.isshowfilter = false
		},
		filterReset(){
			var that = this
			var list = [];
			if(that.filterType=='job'){
				var categorylist = that.categorylist
				for(var i in categorylist){
					for(var j in categorylist[i].child){
						categorylist[i]['child'][j].checked = 0
					}
				}
				that.categorylist = categorylist;
				that.cids = []
				that.cnames = ''
			}else if(that.filterType=='sex'){
				that.sex = ''
				that.sex_text = ''
			}else if(that.filterType=='salary'){
				that.salaryindex = -1
			}else if(that.filterType=='has_job'){
				that.has_job = ''
				that.has_job_text = ''
			}
			// that[field] = list
		},
		chooseFilter:function(e){
			var field = e.currentTarget.dataset.field
			var value = e.currentTarget.dataset.value
			var text = e.currentTarget.dataset.text
			this[field] = value
			this[field+'_text'] = text
		},
    //岗位种类选择
    changeCategory:function(e){
    	var cindex = e.currentTarget.dataset.index
    	this.cindex = cindex
    	this.curcategory = this.categorylist[cindex]['child']
    },
    choosecid:function(e){
    	var that = this
    	var index1 = that.cindex
    	var index2 = e.currentTarget.dataset.index
    	var categorylist = that.categorylist;
    	var category = categorylist[index1].child
    
    	if(category[index2].checked){
    		category[index2].checked = 0;
    	}else{
    		category[index2].checked = 1;
    	}
    	categorylist[index1].child = category
    	var cids = [];
    	var cnames = [];
    	for(var i in categorylist){
    		for(var j in categorylist[i].child){
    			if(categorylist[i]['child'][j].checked){
    				cids.push(categorylist[i]['child'][j].id)
    				cnames.push(categorylist[i]['child'][j].name)
    			}
    		}
    	}
    	that.categorylist = categorylist;
    	that.cnames = cnames.join(',')
    	that.cids = cids
    },
		chooseSelector:function(e){
			var that = this
			var type = e.currentTarget.dataset.filtertype;
			var index = e.currentTarget.dataset.index;
			var field = type+'index'
			that[field] = index
		},
		//岗位种类选择
		changeArea:function(e){
			var index = e.currentTarget.dataset.index
			this.provinceindex = index
			this.cityindex = 0
			this.citylist = this.items[index]['children']
		},
		//地区
		choosearea:function(e){
			var that = this
			var provinceindex = that.provinceindex
			var cityindex = e.currentTarget.dataset.index
			that.cityindex = cityindex
			var province = that.items[provinceindex].text
			var city = that.items[provinceindex]['children'][cityindex].text
			that.areaname = province+','+city
		},
  }
};
</script>
<style>
	@import "../common.css";
.search-container {position: fixed;width: 100%;background: #fff;z-index:9;top:var(--window-top)}
.topsearch{width:100%;padding:16rpx 20rpx;}
.topsearch .f1{height:60rpx;border-radius:30rpx;border:0;background-color:#f7f7f7;flex:1}
.topsearch .f1 .img{width:24rpx;height:24rpx;margin-left:10px}
.topsearch .f1 input{height:100%;flex:1;padding:0 20rpx;font-size:28rpx;color:#333;}
.topsearch .search-btn{display:flex;align-items:center;color:#5a5a5a;font-size:30rpx;width:60rpx;text-align:center;margin-left:20rpx}
.search-navbar {display: flex;text-align: center;align-items:center;padding:0 20rpx;border-top: 1rpx solid #f6f6f6;}
.search-navbar-item {height: 70rpx;line-height: 70rpx;position: relative;color:#323232;padding: 0 10rpx;
width: 25%;flex-shrink: 0;overflow: hidden;display: flex;justify-content: flex-start;align-items: center;flex-wrap: nowrap;white-space: nowrap;
}

.search-navbar-item .down{width: 26rpx; height: 26rpx;vertical-align: middle;margin-left: 6rpx;flex-shrink: 0;}
.search-navbar-item .text{overflow: hidden;white-space: nowrap;text-overflow: ellipsis;}

.search-filter{display: flex;flex-direction: column;text-align: left;width:100%;flex-wrap:wrap;padding:0;}
.filter-content-title{color:#999;font-size:28rpx;height:30rpx;line-height:30rpx;padding:0 30rpx;margin-top:30rpx;margin-bottom:10rpx}
.filter-title{color:#BBBBBB;font-size:32rpx;background:#F8F8F8;padding:20rpx 0 30rpx 20rpx;}
.search-filter-content{display: flex;flex-wrap:wrap;padding:10rpx 20rpx;}
.search-filter .filter-category{padding-left: 30rpx;padding-top: 10rpx;font-size: 32rpx;font-weight: bold;}
.search-filter-content .filter-item{background:#F4F4F4;border-radius:28rpx;color:#2B2B2B;margin:10rpx 10rpx;min-width:136rpx;height:56rpx;line-height:56rpx;text-align:center;font-size: 24rpx;padding:0 30rpx}
.search-filter-content .close{text-align: right;font-size:24rpx;color:#ff4544;width:100%;padding-right:20rpx}
.search-filter button .icon{margin-top:6rpx;height:54rpx;}
.search-filter-btn{display:flex;padding:30rpx 30rpx;justify-content: space-between;padding-bottom: 120rpx;}
.search-filter-btn .btn{width:240rpx;height:66rpx;line-height:66rpx;background:#fff;border:1px solid #e5e5e5;border-radius:33rpx;color:#2B2B2B;font-weight:bold;font-size:24rpx;text-align:center}
.search-filter-btn .btn2{width:240rpx;height:66rpx;line-height:66rpx;border-radius:33rpx;color:#fff;font-weight:bold;font-size:24rpx;text-align:center}

.product-container {width: 100%;margin-top: 90rpx;font-size:26rpx;padding:0;}

/* modal */
.popup__content{padding:0 20rpx;height: 440rpx;}
.popup__overlay{opacity: 0.5;}
.popup__modal{border-radius: 0;max-height: 640rpx;min-height: 640rpx;}
.popup__title{background: #f6f6f6;padding: 20rpx;display: flex;justify-content: space-between;align-items: center;}
.popup__title .popup_cancel, .popup__title .popup_ok{flex-shrink: 0;color: #007aff;font-size: 32rpx;}
.popup__title .popup__close{width: 24rpx;height: 24rpx;}

.popup__content .choose-box{display: flex;justify-content: space-between;flex-wrap: wrap;align-items: center;}
.popup__content .choose-box .choose-item.on{color: #FE924A;background:#fe924a30;}
.choose-box .choose-item{flex-shrink: 0;background: #F6F6F6;text-align: center;padding:10rpx;flex-wrap: nowrap;white-space: nowrap;overflow: hidden;text-overflow: ellipsis;}
.filter-cid .choose-box .choose-item{width: 48%;flex-shrink: 0;background: #F6F6F6;text-align: center;padding:16rpx;margin-bottom: 16rpx;}
.filter-welfare .choose-box{justify-content: flex-start;}
.filter-welfare .choose-box .choose-item{width: 165rpx;margin-top: 20rpx;margin-bottom: 0;margin-left: 10rpx;}
.filter-cid .cate-left{height: 540rpx;width: 20%;flex-shrink: 0;line-height: 70rpx;border-right: 1rpx solid #f6f6f6;margin-right: 20rpx;padding-right: 10rpx;}
.filter-cid .cate-left .dot{color: #FE924A;border: 3px solid #FE924A;border-radius: 50%;width: 10rpx;height: 10rpx;display: block;opacity: 0;margin-right: 10rpx;}
.filter-cid .cate-left view.on{color: #FE924A;font-weight: bold;}
.filter-cid .cate-left .flex-s{flex-wrap: nowrap;overflow: hidden;text-overflow: ellipsis;white-space: nowrap}
.filter-cid .cate-left view.on .dot{opacity: 1;}
.filter-cid .cate-right{flex: 1;align-self: flex-start;padding: 20rpx 0;}

.filter-selector{text-align: center;padding: 20rpx;border-bottom: 1rpx solid #f6f6f6;}
.filter-selector.on{color: #FE924A;font-weight: bold;}

.filter-area{line-height: 60rpx;}
.filter-area .on{color: #FE924A;font-weight: bold;}
.filter-area .province{width: 260rpx;flex-shrink: 0;overflow: hidden;white-space: nowrap;text-overflow: ellipsis;}
.filter-area .city{align-self: flex-start;}

.popup_filter .popup__bottom{padding: 10rpx 20rpx;position: absolute;bottom: 5rpx;width: 100%;}
.popup_filter .popup__bottom .btn{width: 48%;text-align: center;padding: 16rpx 20rpx;}
.popup_filter .popup__bottom .btn-reset{background: #aaaaaa;color: #FFFFFF;}

.tosign{width: 100rpx;height: 100rpx;background: #031028;color: #FFFFFF;position: fixed;bottom: 130rpx;right: 10rpx;display:flex;justify-content: center;align-items: center;
border-radius: 50%;flex-direction: column;text-align: center;font-size: 24rpx;}
</style>