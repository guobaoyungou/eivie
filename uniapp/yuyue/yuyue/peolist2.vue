<template>
<view class="container">
	<block v-if="isload">
		<view class="topbox">
			<view class="order-tab">
				<view class="order-tab2">
						<view :class="'item ' + (curIndex == -1 ? 'on' : '')" @tap="switchTopTab" data-index="-1" data-id="0">全部<view class="after" :style="{background:t('color1')}"></view></view>
					<block v-for="(item, index) in clist" :key="index">
						<view :class="'item ' + (curIndex == index ? 'on' : '')" @tap="switchTopTab" :data-index="index" :data-id="item.appid">{{item.name}}<view class="after" :style="{background:t('color1')}"></view></view>
					</block>
				</view>
			</view>
			<view class="classify-ul" v-if="curIndex!='-1'">
				<view class="libox flex" style="width:100%;">
				 <block v-for="(item, idx2) in clist[curIndex].child" :key="idx2">
				 <view class="classify-li" :style="curIndex2==idx2?'color:'+t('color1')+';background:rgba('+t('color1rgb')+',0.2)':''" @tap="changeCTab" :data-id="item.appid" :data-index="idx2">{{item.name}}</view>
				 </block>
				</view>
			</view>
		</view>
		
		<view class="content flex" :style="curIndex==-1?'top: 100rpx;':'top:'+toppx" >
			<view  v-for="(item, index) in datalist" :key="index" class="f1" @click="goto" :data-url="'peodetail2?id='+item.id" >
				<view class="headimg"><image :src="item.avatar" /></view>
				<view class="text1 flex">	
					<text class="t1">{{item.name}} </text>
					<text class="t3">{{item.distanceDesc}} </text>
				</view>
				<view class="text3 flex">
						<view class="t3" v-if="curIndex==-1">接单量:{{item.acceptOrderTotal}}  <text class="statusdesc">{{item.statusDesc}}</text></view>
						<text class="t3" v-else>{{item.priceDesc}} </text>
						<view class="yuyue"  @click="goto" :data-url="'/yuyue/yuyue/peodetail2?id='+item.id" :style="{background:t('color1')}">预约</view>
				</view>	
			</view>

		</view>
		<nomore v-if="nomore"></nomore>
		<nodata v-if="nodata"></nodata>
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
			keyword:'',
			nodata:false,
			index:0,
			curCid:0,
			curIndex: -1,
			curIndex2:0,
			cursubCid:0,
			latitude:'',
			longitude:'',
			clist:[],
			toppx:'100rpx'
    }
  },
  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.type = this.opt.type || '';
		this.getdata();
  },
	onPullDownRefresh: function () {
		this.getdatalist();
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
			app.get('ApiYuyue2/peocategory', {cid: nowcid,bid:bid}, function (res) {
				that.loading = false;
				var data = res.data;
				that.clist = data;
				that.curCid = data[0]['appid'];
				//that.cursubCid = data[0]['child'][0]['appid'];
				console.log(nowcid);
				if (nowcid) {
					for (var i = 0; i < data.length; i++) {
						if (data[i]['id'] == nowcid) {
							that.curIndex = i;
							that.curCid = nowcid;
							break;
						}
						var downcdata = data[i]['child'];
						var isget = 0;
						that.cursubCid = downcdata[0]['appid'];
						for (var j = 0; j < downcdata; j++) {
							if (downcdata[j]['id'] == nowcid) {
								that.curIndex = i;
								that.curIndex2 = j;
								that.cursubCid = nowcid;
								isget = 1;
								break;
							}
						}
						if (isget) break;
					}
				}
				that.loaded();
				if(app.globalData.platform=='h5'){
					that.getdatalist();
				}else{
					app.getLocation(function (res) {
						that.latitude = res.latitude;
						that.longitude = res.longitude;
						that.getdatalist();
					});
				}
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
			var st = 0;
			if(this.curIndex=='-1'){
					var st='all';
			}
			app.post('ApiYuyue2/selectpeople', {st:st,pagenum: pagenum,keyword: keyword,field: field,order: order,cid: cid,subCid:that.cursubCid,bid:bid,type:'list',latitude:that.latitude,longitude:that.longitude}, function (res) { 
				that.loading = false;
				var data = res.data;
				if (pagenum == 1) {
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
		scrolltolower: function () {
			if (!this.nomore) {
				this.pagenum = this.pagenum + 1;    
				this.getdatalist(true);
		 
			}
		 
		},
		//事件处理函数
		 switchTopTab: function (e) {
				var that = this;
				var id = e.currentTarget.dataset.id; 
				var index = parseInt(e.currentTarget.dataset.index); 
				this.curIndex = index;
				if(index!='-1'){
					this.curIndex2 = 0;
					this.nodata = false;
					this.curCid = id	;
					//console.log(this.clist);
					this.cursubCid = this.clist[index]['child'][0]['appid'];
					
					if(this.clist[index]['child'].length<5 && this.clist[index]['child'].length>=1){
						this.toppx='200rpx';
					} 
					if(this.clist[index]['child'].length>5 && this.clist[index]['child'].length<=10){
						 this.toppx='280rpx';
					}
					if(this.clist[index]['child'].length>10) {
						this.toppx='360rpx';
					}
				}
				this.pagenum = 1; 
				this.datalist = [];
				this.nomore = false;
				this.getdatalist();
		},
	changeCTab: function (e) {
		var that = this;
		var id = e.currentTarget.dataset.id;
		var index = parseInt(e.currentTarget.dataset.index);
		this.curIndex2 = index;
		this.nodata = false;
		this.cursubCid = id;
		this.pagenum = 1;
		this.datalist = [];
		this.nomore = false;
		this.getdatalist();
	},
	
		selectzuobiao: function () {
			console.log('selectzuobiao')
		  var that = this;
		  uni.chooseLocation({
		    success: function (res) {
		      console.log(res);
		      that.area = res.address;
		      that.address = res.name;
		      that.latitude = res.latitude;
		      that.longitude = res.longitude;
		    },
		    fail: function (res) {
					console.log(res)
		      if (res.errMsg == 'chooseLocation:fail auth deny') {
		        //$.error('获取位置失败，请在设置中开启位置信息');
		        app.confirm('获取位置失败，请在设置中开启位置信息', function () {
		          uni.openSetting({});
		        });
		      }
		    }
		  });
		},
  },
	
};
</script>
<style>
.topsearch{width:94%;margin:16rpx 3%;}
.topsearch .f1{height:60rpx;border-radius:30rpx;border:0;background-color:#fff;flex:1}
.topsearch .f1 .img{width:24rpx;height:24rpx;margin-left:10px}
.topsearch .f1 input{height:100%;flex:1;padding:0 20rpx;font-size:28rpx;color:#333;}

.topbox{ position: fixed; top: 0; z-index: 1000;} 

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


.content{width:100%;margin:20rpx 0; padding:0 20rpx;justify-content: space-between;  flex-wrap: wrap; position: absolute;}
.content .f1{align-items:center;width:48%;background:#fff;padding: 20rpx;border-radius:10rpx; margin-bottom: 20rpx;}
.content .f1 image{ width: 310rpx; height:  310rpx; border-radius: 10rpx;}
.content .f1 .t1{color:#2B2B2B;font-weight:bold;font-size:32rpx;margin-left:10rpx;}
.content .f1 .t2{color:#999999;font-size:24rpx; background: #E8E8F7;color:#7A83EC; margin-left: 10rpx; padding:3rpx 20rpx; font-size: 20rpx; border-radius: 18rpx;}
.content .f2{color:#2b2b2b;font-size:26rpx;line-height:42rpx;padding-bottom:20rpx;}
.content .f3{height:96rpx;display:flex;align-items:center}
.content .radio{flex-shrink:0;width: 36rpx;height: 36rpx;background: #FFFFFF;border: 3rpx solid #BFBFBF;border-radius: 50%;}
.content .radio .radio-img{width:100%;height:100%}
.content .mrtxt{color:#2B2B2B;font-size:26rpx;margin-left:10rpx}

.text1 .t3{ color:red}
.text2{ margin-left: 10rpx; color:#999999; font-size: 20rpx;margin-top: 10rpx;}
.text3{ margin-left: 10rpx; color:#999999; font-size: 20rpx;margin-top: 20rpx;justify-content: space-between; line-height: 40rpx;}
.text3 .t5{ margin-left: 20rpx;}
.text3 .t5 text{ color:#7A83EC}
.text3 .t4 text{ color:#7A83EC}
.text3 .t3{ line-height: 60rpx;}
.yuyue{ background: #7A83EC; height: 40rpx; line-height: 40rpx; padding: 0 10rpx; color:#fff; border-radius:28rpx; width: 80rpx; font-size: 20rpx; text-align: center; margin-top: 10rpx;}
.text1{ margin-left: 10rpx; justify-content: space-between; margin-top: 10rpx;}
.container .btn-add{width:90%;max-width:700px;margin:0 auto;height:96rpx;line-height:96rpx;text-align:center;color:#fff;font-size:30rpx;font-weight:bold;border-radius:40rpx;position: fixed;left:0px;right:0;bottom:20rpx;}

.classify-ul{width:100%;padding:10rpx; background: #fff;}
.libox{flex-wrap: wrap;}
.classify-li{background:#F5F6F8;border-radius:22rpx;color:#6C737F;font-size:20rpx;text-align: center;height:44rpx; line-height:44rpx;padding:0 28rpx;
margin:15rpx 10rpx 24rpx 0}
.statusdesc{ color:#06A051; margin-left: 10rpx; }

</style>