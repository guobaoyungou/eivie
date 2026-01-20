<template>
<view class="container">
	<block v-if="isload">
		<view class="content">
			<view class="top flex">
				<view class="headimg"><image :src="data.avatar"> </view>
				<view class="right">
					<view class="t1"><text  class="bold">{{data.name}}</text><text>{{data.juli}}km</text></view>
					<view class="t3">
						<view class="t11"><text>服务单量：{{data.recentOrderTotalDesc}}</text></view>
						<view class="t11">服务状态：<text class="statusdesc">{{data.statusDesc}}</text></view>
						<view class="t11">最后上线时间：{{data.lastOnlineTime}} </view>
					</view>
				</view>
			</view>
			<view class="desc" v-if="data.desc">
				{{data.desc}}
			</view>
			<view class="list">
				<view class="tab">
					<view :class="'item ' + (curTopIndex == 0 ? 'on' : '') " @tap="switchTopTab" :data-index="0">服务项目 <view class="after" :style="{background:t('color1')}"></view></view>
				</view>
				<view v-if="curTopIndex==0 && item.price>0" v-for="(item, index) in datalist" :key="index" class="content2 flex" :data-id="item.id">
					<view class="f1" @click="goto"  :data-url="'product2?skillid='+item.skillId+'&masterid='+data.id" >
						<view class="headimg2"><image :src="set.pic" /></view>
						<view class="text1">	
							<view class="text2 flex">
									<text class="t1">{{item.firstCategoryName}} {{item.secondCategoryName}} </text>
									<view class="text3">
										<text class="t4"><text class="price">{{item.price}}{{item.unit}}</text> </text>
									</view>
							</view>	
							<view  class="textdesc"><text>{{set.desc}}</text></view>
							<view class="flex" style="justify-content: space-between;">
								<view style="font-size: 24rpx; margin: 20rpx 30rpx; ">服务类型：{{item.serviceType}}</view>
								<view class="yuyue" :style="{background:t('color1')}"  @click="goto" :data-url="'product2?skillid='+item.skillId+'&masterid='+data.id+'&masterName='">预约</view>
							</view>
						</view>	
					</view>				
				</view>	
			</view>
		</view>
		<nodata v-if="nodata"></nodata>
		<view style="height:140rpx"></view>
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

			datalist: [],
			type: "",
			keyword:'',
			nodata:false,
			curTopIndex:0,
			data:[],
			set:[]
			
    }
  },
  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.type = this.opt.type || '';
		this.getdata();
		var that = this;
		//if(that.latitude=='' && that.longitude==''){
			app.getLocation(function (res) {
				console.log(res)
				that.latitude = res.latitude;
				that.longitude = res.longitude;
				that.getdata();
			});
		//}
  },
	onPullDownRefresh: function () {

		this.getdata();
	},
  methods: {
		getdata:function(){
			var that = this;
			that.loading = true;
			that.nodata = false;
			var that = this;
			var id = this.opt.id || 0;
			var subid = this.opt.subid || 0;

			app.get('ApiYuyue2/peodetail', {id:id,subid:subid,longitude: that.longitude,latitude: that.latitude}, function (res) {
				that.loading = false;
				var data = res.data;
				that.data = data
				that.set = res.set
				that.datalist = data.skillApiVOList
				that.loaded();
			});
		},
		switchTopTab: function (e) {
		  var that = this;
		  var index = parseInt(e.currentTarget.dataset.index);
		  this.curTopIndex = index;
		  this.datalist = [];
		  this.getdatalist(true);
		},   
		getdatalist: function (loadmore) {
			if(!loadmore){
				this.pagenum = 1;
				this.datalist = [];
			}
			var that = this;
			var pagenum = that.pagenum;
			var id = that.opt.id ? that.opt.id : '';
			var order = that.order;
			var field = that.field; 
			that.loading = true;
			that.nodata = false;
			that.nomore = false;
			app.post('ApiYuyue/getdlist', {curTopIndex:that.curTopIndex,pagenum: pagenum,field: field,order: order,id:id}, function (res) { 
				that.loading = false;
				uni.stopPullDownRefresh();
				var data = res.data;
				if (data.length == 0) {
					console.log(pagenum);
					if(pagenum == 1){
						that.nodata = true;
					}else{
						that.nomore = true;
					}
				}
				var datalist = that.datalist;
				var newdata = datalist.concat(data);
				that.datalist = newdata;
			});
		 
		},
		scrolltolower: function () {
		     
			if (!this.nomore) {
		   
				this.pagenum = this.pagenum + 1;    
				this.getdatalist(true);
		 
			}
		 
		},
  }
};
</script>
<style>
.container{ background: #fff; padding: 30rpx;}
.headimg{ width: 240rpx; height: 240rpx;margin-right: 20rpx;}
.headimg image{ width: 240rpx; height: 240rpx;border-radius: 10rpx ; }
.content .top { width: 100%; } 
.content .top .fl{ width: 100%; }
.right{ width: 100%;}
.right .t1{ display: flex; color:#323232;  font-size: 36rpx; font-weight: bold; justify-content: space-between; margin-top: 10rpx; }
.right .t2{ color:#999;  font-size: 24rpx; }
.right .t3{ margin-top: 20rpx;color:#999;font-size: 24rpx; background: #F8FBFF; border-radius: 10rpx; line-height: 40rpx; padding: 20rpx;}
.right .t3 .bold{ font-size: 36rpx; color: #323232; font-weight: bold;margin-left: 4rpx;}
.right .t11{ font-size: 26rpx;}
.statusdesc{ color:#06A051; }

.desc{ color: #6d6e74; font-size: 26rpx; margin-top: 60rpx;}
.list .headimg2{ width: 220rpx; height: 220rpx;border-radius: 10rpx ; }
.tab{ margin-top: 80rpx; display: flex; }
.tab .item{ padding-right:20rpx; color: #323232;font-size: 28rpx; font-weight:bold; margin-right: 40rpx; line-height: 60rpx; overflow: hidden;position:relative; }
.tab .after{display:none;position:absolute;left:25%;margin-left:-20rpx;bottom:0rpx;height:6rpx;border-radius:1.5px;width:80rpx}
.tab .on .after{display:block}


.content2{width:100%;background:#fff;border-radius:5px; justify-content: space-between; margin-top: 40rpx; border-bottom: 1px solid #EEEEEE;}
.content2 .f1{display:flex;align-items:center; flex-wrap: wrap;}
.content2 .f1 image{ width: 210rpx; height: 210rpx; border-radius: 10rpx;}
.content2 .f1 .t1{color:#2B2B2B;font-weight:bold;font-size:28rpx;margin-left:10rpx; margin-top: 5rpx; }
.content2 .f1 .t2{color:#999999;font-size:28rpx; background: #E8E8F7;color:#7A83EC; margin-left: 10rpx; padding:3rpx 20rpx; font-size: 20rpx; border-radius: 18rpx;}
.content2 .f1 .t3{ margin-left:10rpx;display: block; height: 40rpx;line-height: 40rpx;}
.content2 .f2{color:#2b2b2b;font-size:26rpx;line-height:42rpx;padding-bottom:20rpx;}
.content2 .f3{height:96rpx;display:flex;align-items:center}
.content2 .radio{flex-shrink:0;width: 36rpx;height: 36rpx;background: #FFFFFF;border: 3rpx solid #BFBFBF;border-radius: 50%;}
.content2 .radio .radio-img{width:100%;height:100%}
.content2 .mrtxt{color:#2B2B2B;font-size:26rpx;margin-left:10rpx}
.list .text1{ width: 67%;}
.text2{ margin-left: 10rpx; color:#999999; font-size: 20rpx;margin-top: 10rpx; margin-bottom: 10rpx; justify-content: space-between; margin-right: 20rpx;}
.text3{ margin-left: 10rpx; color:#999999; font-size: 20rpx;margin-top: 10rpx;}
.text3 .t5{ margin-left: 20rpx;}
.text3 .t5 text{ color:#7A83EC}
.text3 .t4 text{ color:#FF5347}
.text3 .t4 { color:#FF5347}
.textdesc{ font-size: 20rpx; padding: 10rpx 20rpx; background:#F8FBFF; margin: 0 20rpx;}
.text3 .t4 .price{ font-weight: bold;font-size: 32rpx; line-height: 0rpx;}
.yuyue{ background: #7A83EC; width:126rpx;height: 50rpx; line-height: 50rpx; margin-top: 10rpx;; padding: 0 10rpx; color:#fff; 
border-radius:28rpx; ; font-size: 20rpx; text-align: center;  margin-bottom: 10rpx;}
</style>