<template>
<view class="container">
	<block v-if="isload">
		<view class="topsearch flex-y-center">
			<view class="f1 flex-y-center">
				<image class="img" :src="pre_url+'/static/img/search_ico.png'"></image>
				<input :value="keyword" placeholder="输入姓名/手机号搜索" placeholder-style="font-size:24rpx;color:#C2C2C2" @confirm="searchConfirm" @input="searchChange"></input>
			</view>
		</view>
		<view class="order-tab">
			<view class="order-tab2">
				<view :class="'item ' + (curTopIndex == -1 ? 'on' : '')" @tap="switchTopTab" :data-index="-1" :data-id="0">全部<view class="after" :style="{background:t('color1')}"></view></view>
				<block v-for="(item, index) in clist" :key="index">
					<view :class="'item ' + (curTopIndex == index ? 'on' : '')" @tap="switchTopTab" :data-index="index" :data-id="item.id">{{item.name}}<view class="after" :style="{background:t('color1')}"></view></view>
				</block>
			</view>
		</view>
	
		<view v-for="(item, index) in datalist" :key="index" class="content flex" :data-id="item.id">
			<view class="f1" @click="goto" :data-url="'peodetail?id='+item.id" >
				<view class="headimg"><image :src="item.headimg" /></view>
				<view class="text1">	
					<text class="t1">{{item.realname}} </text>
					<text class="t2" v-if="item.typename" >{{item.typename}} </text>
					<view class="text2">{{item.jineng}}</view>
					<view class="text3 flex">
						<view class="t4">服务<text> {{item.totalnum}}</text> 次</view> <view class="t5">评分 <text>{{item.comment_score}}</text></view>
					</view>

				</view>	
			</view>
			<view>
				<view class="yuyue"  @click="goto" :data-url="'/yuyue/yuyue/peodetail?id='+item.id" :style="{background:t('color1')}">预约</view>
			</view>
		</view>
		<nodata v-if="nodata"></nodata>
		<nomore v-if="nomore"></nomore>
	</block>
	<loading v-if="loading"></loading>
	<dp-tabbar :opt="opt" @getmenuindex="getmenuindex"></dp-tabbar>
	<popmsg ref="popmsg"></popmsg>
	<wxxieyi></wxxieyi>
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
      keyword: '',
      datalist: [],
      type: "",
		nodata:false,
		 curTopIndex: -1,
		 index:0,
		 curCid:0,
		 nomore: false,
		 pagenum: 1,
		 clist:[],
		 pre_url:app.globalData.pre_url
    }
  },
  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.type = this.opt.type || '';
		this.getdata();
  },
	onPullDownRefresh: function () {
		this.getdata();
	},
	onReachBottom: function () {
	  if (!this.nodata && !this.nomore) {
	    this.pagenum = this.pagenum + 1;
	    this.getdatalist(true);
	  }
	},
  methods: {
		getdata:function(){
			var that = this;
			var nowcid = that.opt.cid;
			var bid = that.opt.bid || 0;
			if (!nowcid) nowcid = '';
			that.loading = true;
			app.get('ApiYuyue/peocategory', {cid: nowcid,bid:bid}, function (res) {
				that.loading = false;
				var data = res.data;
				that.clist = data;
				//that.curCid = data[0]['id'];
				if (nowcid) {
					for (var i = 0; i < data.length; i++) {
						if (data[i]['id'] == nowcid) {
							that.curTopIndex = i;
							that.curCid = nowcid;
							break;
						}
						var downcdata = data[i]['child'];
						var isget = 0;
						for (var j = 0; j < downcdata; j++) {
							if (downcdata[j]['id'] == nowcid) {
								that.curIndex = i;
								that.curIndex2 = j;
								that.curCid = nowcid;
								isget = 1;
								break;
							}
						}
						if (isget) break;
					}
				}
				that.loaded();
				//app.getLocation(function (res) {
					//var latitude = res.latitude;
					//var longitude = res.longitude;
					//that.longitude = longitude;
					//that.latitude = latitude;
					that.getdatalist();
			//	},
			//	function () {
				//	that.getdatalist();
			//	});

			});
			
		},
		getdatalist: function (loadmore) {
			if(!loadmore){
				this.pagenum = 1;
				this.datalist = [];
			}
			var that = this;
			var pagenum = that.pagenum;
			var cid = that.curCid;
			var bid = that.opt.bid ? that.opt.bid : '';
			var order = that.order;
		    var keyword = that.keyword;
			var field = that.field; 
			that.loading = true;
			that.nodata = false;
			that.nomore = false;
			var latitude = that.latitude;
			var longitude = that.longitude;
			app.post('ApiYuyue/selectpeople', {pagenum: pagenum,keyword: keyword,field: field,order: order,cid: cid,bid:bid,type:'list',longitude: longitude,latitude: latitude}, function (res) { 
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
		switchTopTab: function (e) {
		   var that = this;
		   var id = e.currentTarget.dataset.id;
		   var index = parseInt(e.currentTarget.dataset.index);
		   this.curTopIndex = index;
		   this.curIndex = -1;
		   this.curIndex2 = -1;
		   this.prolist = [];
		   this.nopro = 0;
		   this.curCid = id;
		   this.getdatalist();
		}, 
		searchChange: function (e) {
		  this.keyword = e.detail.value;
		},
		searchConfirm: function (e) {
		  var that = this;
		  var keyword = e.detail.value;
		  that.keyword = keyword;
		  that.getdata();
		}
  }
};
</script>
<style>
.topsearch{width:94%;margin:16rpx 3%;}
.topsearch .f1{height:60rpx;border-radius:30rpx;border:0;background-color:#fff;flex:1}
.topsearch .f1 .img{width:24rpx;height:24rpx;margin-left:10px}
.topsearch .f1 input{height:100%;flex:1;padding:0 20rpx;font-size:28rpx;color:#333;}

.search-navbar-item .iconshangla{position: absolute;top:-4rpx;padding: 0 6rpx;font-size: 20rpx;color:#7D7D7D}
.search-navbar-item .icondaoxu{position: absolute;top: 8rpx;padding: 0 6rpx;font-size: 20rpx;color:#7D7D7D}
.search-navbar-item .iconshaixuan{margin-left:10rpx;font-size:22rpx;color:#7d7d7d}
.search-history {padding: 24rpx 34rpx;}
.search-history .search-history-title {color: #666;}
.search-history .delete-search-history {float: right;padding: 15rpx 20rpx;margin-top: -15rpx;}
.search-history-list {padding: 24rpx 0 0 0;}
.search-history-list .search-history-item {display: inline-block;height: 50rpx;line-height: 50rpx;padding: 0 20rpx;margin: 0 10rpx 10rpx 0;background: #ddd;border-radius: 10rpx;font-size: 26rpx;}


.order-tab{display:flex;width:100%;overflow-x:scroll;border-bottom: 1px #f5f5f5 solid;background: #fff;padding:0 10rpx}
.order-tab2{display:flex;width:auto;min-width:100%}
.order-tab2 .item{width:20%;padding:0 20rpx;font-size:28rpx;font-weight:bold;text-align: center; color:#999999; height:80rpx; line-height:80rpx; overflow: hidden;position:relative;flex-shrink:0;flex-grow: 1;}
.order-tab2 .on{color:#222222;}
.order-tab2 .after{display:none;position:absolute;left:50%;margin-left:-20rpx;bottom:10rpx;height:6rpx;border-radius:1.5px;width:40rpx}
.order-tab2 .on .after{display:block}


.content{width:94%;margin:20rpx 3%;background:#fff;border-radius:5px;padding:20rpx 40rpx; justify-content: space-between;}
.content .f1{display:flex;align-items:center}
.content .f1 image{ width: 140rpx; height: 140rpx; border-radius: 10rpx;}
.content .f1 .t1{color:#2B2B2B;font-weight:bold;font-size:32rpx;margin-left:10rpx;}
.content .f1 .t2{color:#999999;font-size:28rpx; background: #E8E8F7;color:#7A83EC; margin-left: 10rpx; padding:3rpx 20rpx; font-size: 20rpx; border-radius: 18rpx;}
.content .f1 .t3{ margin-left:10rpx;display: block; height: 40rpx;line-height: 40rpx;}
.content .f2{color:#2b2b2b;font-size:26rpx;line-height:42rpx;padding-bottom:20rpx;}
.content .f3{height:96rpx;display:flex;align-items:center}
.content .radio{flex-shrink:0;width: 36rpx;height: 36rpx;background: #FFFFFF;border: 3rpx solid #BFBFBF;border-radius: 50%;}
.content .radio .radio-img{width:100%;height:100%}
.content .mrtxt{color:#2B2B2B;font-size:26rpx;margin-left:10rpx}


.text2{ margin-left: 10rpx; color:#999999; font-size: 20rpx;margin-top: 10rpx;}
.text3{ margin-left: 10rpx; color:#999999; font-size: 20rpx;margin-top: 10rpx;}
.text3 .t5{ margin-left: 20rpx;}
.text3 .t5 text{ color:#7A83EC}
.text3 .t4 text{ color:#7A83EC}
.yuyue{ background: #7A83EC; height: 40rpx; line-height: 40rpx; padding: 0 10rpx; color:#fff; border-radius:28rpx; width: 80rpx; font-size: 20rpx; text-align: center; margin-top: 20rpx;}
.text1{ margin-left: 10rpx;}
.container .btn-add{width:90%;max-width:700px;margin:0 auto;height:96rpx;line-height:96rpx;text-align:center;color:#fff;font-size:30rpx;font-weight:bold;border-radius:40rpx;position: fixed;left:0px;right:0;bottom:20rpx;}
</style>