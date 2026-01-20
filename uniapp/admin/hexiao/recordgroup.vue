<template>
<view>
	<block v-if="isload">
		<view class="topsearch flex-y-center">
			<view class="f1 flex-y-center">
				<image class="img" :src="pre_url+'/static/img/search_ico.png'"></image>
				<input :placeholder="'输入'+t('会员')+'昵称搜索'" v-model="keyword" placeholder-style="font-size:24rpx;color:#C2C2C2" @confirm="searchChange"></input>
			</view>
			<image class="close" :src="pre_url+'/static/img/close.png'" v-if="showsearch" @tap="hideSearch"></image>
		</view>
		<view class="content" v-if="yearlist.length>0 && !showsearch">
			<view v-for="(item, index) in yearlist" :key="index" class="yearitem">
				<view class="label" @tap="yearToggle" :data-index="index">
					<image v-if="item.isshow" :src="pre_url+'/static/img/location/down-dark.png'">
					<image v-if="!item.isshow" :src="pre_url+'/static/img/location/right-dark.png'">
					<text class="t1">{{item.name}}</text>
				</view>
				<view class="monthbox" v-if="item.isshow" v-for="(item1,index1) in item.monthlist" :key="index1">
					<view class="month-title" :class="item1.isshow?'on':''" @tap="monthToggle"  :data-yindex="index" :data-index="index1">
						<image :src="pre_url+'/static/img/arrowright.png'" v-if="!item1.isshow"></image>
						<image :src="pre_url+'/static/img/arrowdown.png'" style="height: 24rpx;width: 24rpx;margin-right: 6rpx;" v-if="item1.isshow"></image>
						<text>{{item1.name}}</text>
					</view>
					<view class="listitem" v-if="item1.isshow">
						<scroll-view class="classify-box" scroll-y="true"  @scrolltolower="scrolltolower" :data-yindex="index" :data-index="index1">
							<view v-for="(item2, index2) in item1.list" :key="index2" class="item">
								<view class="f1">
									<image class="t1" :src="item2.headimg"></image>
									<text class="t2">{{item2.nickname}}</text>
								</view>
								<view class="f2">
									<text class="t1" style="color:#000">{{item2.title}}</text>
									<text class="t2">{{dateFormat(item2.createtime)}}</text>
									<text class="t3">备注：{{item2.remark}}</text>
								</view>
							</view>
							<nomore v-if="nomore"></nomore>
						</scroll-view>
					</view>
				</view>
			</view>
		</view>
		<view class="searchbox content" v-if="showsearch">
			<view v-for="(item, index) in searchlist" :key="index" class="item">
				<view class="f1">
					<image class="t1" :src="item.headimg"></image>
					<text class="t2">{{item.nickname}}</text>
				</view>
				<view class="f2">
					<text class="t1" style="color:#000">{{item.title}}</text>
					<text class="t2">{{dateFormat(item.createtime)}}</text>
					<text class="t3">备注：{{item.remark}}</text>
				</view>
			</view>
			<nomore v-if="nomores"></nomore>
			<nomore v-if="nodatas"></nomore>
		</view>
	</block>
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
			pre_url:app.globalData.pre_url,
			keyword:'',
      st: 0,
			count:0,
      datalist: [],
      pagenum: 1,
      pagenums: 1,
      nodata: false,
      nomore: false,
			yearlist:[],
			year:'',
			month:'01',
			year_index:-1,
			month_index:-1,
			showsearch:false,
			searchlist:[],
			nodatas: false,
			nomores: false,
    };
  },
  
  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.getdata();
  },
	onPullDownRefresh: function () {
		this.getdata();
	},
  onReachBottom: function () {
		if(this.showsearch){
			if (!this.nodatas && !this.nomores) {
			  this.pagenums = this.pagenums + 1;
			  this.getsearchlist(true);
			}
		}
    return false
  },
	onNavigationBarSearchInputConfirmed:function(e){
		this.searchConfirm({detail:{value:e.text}});
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
      var keyword = that.keyword;
			that.nodata = false;
			that.nomore = false;
			that.loading = true;
      app.post('ApiAdminHexiao/recordGroup', {}, function (res) {
				that.loading = false;
				if(res.status==1){
					that.yearlist = res.yearlist
					if(that.yearlist.length>0){
						that.year = that.yearlist[0].val;
						that.year_index = 0;
						that.month = that.yearlist[0].monthlist[0].val;
						that.month_index = 0;
					}
					that.getlist();
					that.loaded();
				}
      });
    },
		getlist: function (loadmore) {
			if(!loadmore){
				this.pagenum = 1;
				this.datalist = [];
			}
		  var that = this;
		  var pagenum = that.pagenum;
		  var st = that.st;
		  var keyword = that.keyword;
			that.nodata = false;
			that.nomore = false;
			that.loading = true;
			console.log(this.year_index)
			console.log(this.month_index)
			if(that.year_index<0 || that.month_index<0){
				return false;
			}
		  app.post('ApiAdminHexiao/recordMonthList', {year:that.year,month:that.month}, function (res) {
				that.loading = false;
		    var data = res.data;
		    if (pagenum == 1){
					that.count = res.count;
					that.yearlist[that.year_index].monthlist[that.month_index].list = data;
		      if (data.length == 0) {
		        that.nodata = true;
		      }
					that.loaded();
		    }else{
		      if (data.length == 0) {
		        that.nomore = true;
		      } else {
		        var datalist = that.yearlist[that.year_index].monthlist[that.month_index];
		        var newdata = datalist.concat(data);
		        that.yearlist[that.year_index].monthlist[that.month_index].list = newdata;
		      }
		    }
		  });
		},
    yearToggle: function (e) {
      var index = e.currentTarget.dataset.index;
      this.year_index = index
			this.yearlist[index].isshow = !this.yearlist[index].isshow;
    },
		monthToggle: function (e) {
		  var index = e.currentTarget.dataset.index;
		  var year_index = e.currentTarget.dataset.yindex;
			var yearlist = this.yearlist;
			this.year_index = year_index
			this.month_index = index;
			this.year = this.yearlist[year_index].val
			this.month = this.yearlist[year_index].monthlist[index].val
			var preYearIsShow = this.yearlist[year_index].isshow
			var preMonthIsShow = this.yearlist[year_index].monthlist[index].isshow
			//不是该选项的 隐藏
			var newyearlist = [];
			for(var yi in yearlist){
				var monthlist = yearlist[yi].monthlist
				if(yi!=year_index){
					yearlist[yi].isshow = false;
					for(var i in monthlist){
						monthlist[i].isshow = false;
						monthlist[i].list = [];
					}
				}else{
					yearlist[yi].isshow = true;
					for(var i in monthlist){
						if(i==index){
							monthlist[i].isshow = !preMonthIsShow;
						}else{
							monthlist[i].isshow = false;
							monthlist[i].list = [];
						}
					}
				}
				yearlist[yi].monthlist = monthlist
			}
			this.yearlist = yearlist;
			if(preMonthIsShow==false){
				this.getlist();
			}
		},
    searchChange: function (e) {
			var that = this;
			if(this.keyword!=''){
				this.showsearch = true;
				this.getsearchlist()
			}else{
				this.showsearch = false
			}
    },
		getsearchlist: function (loadmore) {
			if(!loadmore){
				this.pagenums = 1;
				this.searchlist = [];
			}
		  var that = this;
		  var pagenum = that.pagenums;
		  var keyword = that.keyword;
			that.nodatas = false;
			that.nomores = false;
			that.loading = true;
		  app.post('ApiAdminHexiao/record', {keyword:keyword,pagenum: pagenum,type:1}, function (res) {
				that.loading = false;
		    var data = res.data;
		    if (pagenum == 1){
					that.searchlist = data;
		      if (data.length == 0) {
		        that.nodatas = true;
		      }
					that.loaded();
		    }else{
		      if (data.length == 0) {
		        that.nomores = true;
		      } else {
		        var datalist = that.searchlist;
		        var newdata = datalist.concat(data);
		        that.searchlist = newdata;
		      }
		    }
		  });
		},
		hideSearch:function(){
			this.showsearch = false;
		},
    searchConfirm: function (e) {
      var that = this;
      var keyword = e.detail.value;
      that.keyword = keyword;
      that.getdata();
    },
		scrolltolower: function (e) {
			var year_index = e.currentTarget.dataset.yindex;
			var index = e.currentTarget.dataset.index;
		 this.year = this.yearlist[year_index].val;
		 this.year_index = year_index;
		 this.month_index = index;
		 this.month = this.yearlist[year_index].monthlist[index].val;
			if (!this.nomore) {
		   
				this.pagenum = this.pagenum + 1;    
				this.getlist(true);
		 
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
.topsearch .close{width: 40rpx;height: 40rpx;flex-shrink: 0;margin-left: 10rpx;}

.content{width: 100%;}
.yearitem{border-bottom: 1rpx solid #E0E0E0;background: #fff;margin-bottom:20rpx;}
.content .label{display:flex;width: 100%;padding:24rpx 16rpx;color: #333;font-weight: bold;font-size: 30rpx;background: #fff;}

.content .label image{width: 32rpx;height: 32rpx;}
.monthbox{width: 94%;    margin: 0 3%;}
.monthbox .month-title{display: flex;align-items: center;border-top: 1rpx solid #E0E0E0;padding: 20rpx;}
.monthbox .month-title.on{background: #ebebeb;}
.month-title image{height: 30rpx;width: 30rpx;}
.content .item{ width:100%;padding:20rpx 20rpx;border-top: 1px #fff solid;display:flex;align-items:center;background: #F6F6F6;}
.content .item .f1{display:flex;flex-direction:column;margin-right:20rpx}
.content .item .f1 .t1{width:100rpx;height:100rpx;margin-bottom:10rpx;border-radius:50%;margin-left:20rpx}
.content .item .f1 .t2{color:#666666;text-align:center;width:140rpx;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:1;overflow:hidden;}
.content .item .f2{ flex:1;width:200rpx;font-size:30rpx;display:flex;flex-direction:column}
.content .item .f2 .t1{color:#03bc01;line-height:36rpx;font-size:28rpx;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:2;overflow:hidden;}
.content .item .f2 .t2{color:#999;height:40rpx;line-height:40rpx;font-size:24rpx}
.content .item .f2 .t3{color:#aaa;height:40rpx;line-height:40rpx;font-size:24rpx}
.classify-box{width: 100%;max-height:960rpx;overflow-y: scroll;}
.searchbox{background: #fff;padding: 30rpx;}
.searchbox .item{margin-bottom: 20rpx;}
</style>