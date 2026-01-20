<template>
	<view v-if="isload">
		<view class="screen">
			<view class="screen_module">
				<scroll-view scroll-x="true" class="screen_content">
					<view v-for="(item,index) in datelist" @click="dateClick(index)" :key="index" class="screen_item" :class="dateIndex==index?'screen_active':''">
						<view class="screen_text">{{item.date}}</view>
						<view class="screen_text">{{item.week}}</view>
					</view>
				</scroll-view>
				<view @click="alertClick" class="screen_opt">
					<image src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAACAAAAAgCAYAAABzenr0AAAAAXNSR0IArs4c6QAAAgdJREFUWEftlz1vE0EQht9Z+R/QUNBjeWavIIgGkAglEhgK0oAEBfQkUMAvACFI6KEACRoo+IhEm0iQJgKK21nL9BQ0VLTWLVrrjJzg+3BI7Oa2Ot3OvPPs7MfsEubcaM7x0QD8kwER2QBw5oCmZlNVF8e1JwF8AnDqgAA+q+rpUoBOp3PCGPMewGEAb1R16X9gROQ1gMsAfmZZ1u31etulALHTWnsuhBAhWiGENe/9yl4gmHmViJYBDIio65z7uFuncBcw8zUieh4diGjFObc2DYS1djmEsBp9QgjXvfcvJvmXbkMRuQ3gUQ5x1Tn3qg6EtfZKCOFlbntHVR8X+VWeA8x8n4ju5iO54L1fL4Ng5vNE9CG3f+C9v1dmXwkQnUXkKYAbudCiqm5OEhWRuH3jNo7tmarerMpYLYAc4i2AiwB+GGO6aZp+GxdPkuRYlmVx4R4B8E5VL1UFH05tHaNo0263D7VarRjgJICvqnp83FdEvgBYALA1GAy6/X7/Vx3t2gBRLEmSo1mW9eO3qu7wFZEQ/xtj2mmafq8TfKoMjARHgYoAdv+vApkqA/laGI60AWgy0GRg7hkgoifOuVjrh63ofNj3c4CZbxHR6G7w98Y0M4A4ImY+S0QPAfweXTJnClBQiieekPs+BUWCTQbyB00sUjseHjObgqpARf1Tl+O9Biry+wPUJ/MhhuUwKAAAAABJRU5ErkJggg=="
						alt="" />
					<view>筛选</view>
				</view>
			</view>
		</view>
		<view v-for="(item,index) in datalist" :key="index" class="module">
			<view class="module_data" @tap="toProduct" :data-id = "item.id" >
				<image :src="item.pic" class="module_img" alt=""/>
				<view class="module_content">
					<view class="module_title">{{item.name}}</view>
					<view class="module_item" v-if="item.feature">{{item.feature}}</view>
					<!-- <view class="module_item"  v-if="item.cid ==2"> <view class="module_time">{{item.starttime}}~{{item.endtime}}</view></view> -->
					<view class="module_item" v-if="item.cid != 2 ">
					<text v-if="item.cid==1">租车</text><text v-else>包车</text>费用：<text class="txt-yel">￥{{item.sell_price}}</text> /天</view>
					<view class="module_item" v-else>拼车费用：<text class="txt-yel">￥{{item.sell_price}}</text> /人</view>
					<!-- <view class="module_item">所在城市：{{area}}</view> -->
				</view>
				<view v-if="item.cid ==2">
					<view class="module_btn module_end" @click.stop="feiyuyue" v-if="item.yystatus ==0 && item.leftnum > 0">非预约时间</view>
					<block v-else>
						<view class="module_btn" v-if="item.leftnum >0">预约</view>
						<view class="module_btn module_end" v-else>满员</view>
					</block>
				</view>
				<view v-else-if="item.cid ==1">
					<view class="module_btn " >租车</view>
				</view>
				<view v-else>
					<view class="module_btn " >包车</view>
				</view>
			</view>
			<view class="module_num" v-if="item.cid ==2">
				<view class="module_lable">
					<view>当前</view>
					<view>预约</view>
				</view>
				<view class="module_view">
					<block v-for="(item2,index2) in item.yyorderlist">
						<image :src="item2.headimg"/>
					</block>
				</view>
				<view class="module_tag" v-if="item.leftnum > 0">剩余{{item.leftnum}}个名额</view>
				<view class="module_tag module_end" v-else>满员</view>
			</view>
		</view>
		<view v-if="alertState" class="alert">
			<view @click="alertClick" class="alert_none"></view>
			<view class="alert_module">
				<view class="alert_opt">
					<view class="alert_table">
						<view class="alert_view" :class="filterType==0?'alert_active':''"  @tap.stop="changefilterType" :data-type="0">类别</view>
						<view class="alert_view" :class="filterType==1?'alert_active':''" @tap.stop="changefilterType" :data-type="1">时段</view>
					</view>
					<view class="alert_cancel" @tap.stop="filterClean">清空</view>
					<view class="alert_btn" @tap.stop="filterConfirm">确认</view>
				</view>
				<scroll-view scroll-y="true" class="alert_box" v-if="filterType==0">
					<view class="alert_item " :class="cachecid==item.id?'alert_current':''" v-for="(item,index) in clist" @tap.stop="changecid" :data-id="item.id">{{item.name}}</view>
				</scroll-view>
				<scroll-view scroll-y="true" class="alert_box" v-if="filterType==1">
					<view class="alert_item " :class="cachetimerange==index?'alert_current':''" v-for="(item,index) in timerangelist" @tap.stop="changetimerange" :data-index="index">{{item.rangestr}}</view>
				</scroll-view>
			</view>
		</view>
		<loading v-if="loading"></loading>
		<nomore v-if="nomore"></nomore>
		<nodata v-if="nodata"></nodata>
	</view>
</template>

<script>
var app = getApp();
export default {
	data() {
		return {
			opt:{},
			filterType:0,
			cachecid:'',
			cid:'',
			cachetimerange:-1,
			timerangeindex:-1,
			bid:'0',
			loading:false,
			isload: false,
			menuindex:-1,
			pagenum: 1,
			nomore: false,
			nodata: false,
			clist:[],
			timerangelist:[],
			datalist:[],
			datelist: [],
			dateIndex:-1,
			alertState: false,
			area:'',
			type:''
		}
	},
	onLoad: function(opt) {
		this.opt = app.getopts(opt);
		if(this.opt && this.opt.cid){
			this.cid = this.opt.cid;
			this.cachecid = this.cid;
		}
		if(this.opt && this.opt.bid) this.bid = this.opt.bid;

		this.area = app.getCache('user_current_area_show');
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
			that.pagenum = 1;
			that.datalist = [];
			app.get('ApiCarHailing/prolist', {bid:this.bid}, function (res) {
			  that.clist = res.clist;
			  that.datelist = res.datelist;
			  that.timerangelist = res.timerangelist;
				that.loaded();
				that.getdatalist();
			});
		},
		getdatalist: function (loadmore) {
			if(!loadmore){
				this.pagenum = 1;
				this.datalist = [];
			}
			var that = this;
			var pagenum = that.pagenum;
			var cid = that.cid;
			var bid = that.bid;
			var dateIndex = that.dateIndex;
			that.loading = true;
			that.nodata = false;
			that.nomore = false;
			var starttime = '';
			var endtime = '';
			var type = that.type;
			if(that.timerangeindex != -1){
				var timerange = that.timerangelist[that.timerangeindex];
				starttime = timerange.starttime;
				endtime = timerange.endtime;
			}
			app.post('ApiCarHailing/getprolist', {pagenum: pagenum,cid:cid,bid:bid,starttime:starttime,endtime:endtime,dateIndex:dateIndex,type:that.type}, function (res) { 
				that.loading = false;
				uni.stopPullDownRefresh();
				var data = res.data;
				if (data.length == 0) {
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
		dateClick(index) {
			this.dateIndex = index;
			this.getdatalist();
		},
		alertClick(){
			this.alertState?this.alertState=false:this.alertState=true;
		},
		changefilterType:function(e){
			this.filterType = e.currentTarget.dataset.type;
		},
		changecid:function(e){
			if(this.cachecid == e.currentTarget.dataset.id){
				this.cachecid = '';
			}else{
				this.cachecid = e.currentTarget.dataset.id;
			}
		},
		changetimerange:function(e){
			if(this.cachetimerange == e.currentTarget.dataset.index){
				this.cachetimerange = -1;
			}else{
				this.cachetimerange = e.currentTarget.dataset.index;
			}
		},
		filterConfirm:function(){
			// this.cid = this.cachecid;
			this.type = this.cachecid;
			this.timerangeindex = this.cachetimerange;
			this.alertState = false;
			this.getdatalist();
		},
		filterClean:function(){
			this.cachecid = '';
			this.cid = '';
			this.type = '';
			this.cachetimerange = -1;
			this.timerangeindex = -1;
			this.getdatalist();
			this.alertState = false;
		},
		toProduct(e){
			var id = e.currentTarget.dataset.id;
			var dateIndex = this.dateIndex;
			if(dateIndex < 0){
				app.error('请选择日期');
				return;
			}
			app.goto('product?id='+id+'&dateIndex='+dateIndex);
		},
		feiyuyue(){
			app.error('非预约时间');return;
		}
	}
}
</script>
<style>
	page {
		background: #f0f0f0;
	}
</style>
<style scoped>
	.screen {
		position: relative;
		height: 150rpx;
		z-index: 5;
	}

	.screen_module {
		position: fixed;
		width: 100%;
		height: 150rpx;
		top: 0;
		left: 0;
		padding: 0 30rpx;
		box-sizing: border-box;
		background: #f0f0f0;
		display: flex;
		align-items: center;
	}

	.screen_opt {
		margin: 0 0 0 10rpx;
		font-size: 26rpx;
		color: #333;
		flex-shrink: 0;
		font-size:24rpx;
	}

	.screen_opt image {
		height: 34rpx;
		width: 34rpx;
		display: block;
		margin: 10rpx auto 10rpx auto;
	}

	.screen_opt view {
		text-align: center;
	}

	.screen_content {
		flex: 1;
		width: 500rpx;
		white-space: nowrap;
	}

	.screen_item {
		height: 90rpx;
		width: 90rpx;
		display: inline-block;
		border-radius: 12rpx;
		color: #333;
		box-sizing: border-box;
	}

	.screen_text {
		font-size: 26rpx;
		text-align: center;
	}
	.screen_text:first-child{
		margin-top: 10rpx;
	}

	.screen_active {
		background: #454545;
		color: #fff;
	}
	
	.address{
		position: relative;
		width: 700rpx;
		height: 130rpx;
		border-radius: 20rpx;
		overflow: hidden;
		margin: 0 auto 30rpx auto;
	}
	.address_back{
		height: 100%;
		width: 100%;
	}
	.address_data{
		position: absolute;
		height: 100%;
		width: 100%;
		top: 0;
		left: 0;
		box-sizing: border-box;
		padding: 0 30rpx;
		display: flex;
		align-items: center;
		background: rgba(0, 0, 0, 0.4);
	}
	.address_content{
		flex: 1;
	}
	.address_title{
		font-size: 35rpx;
		color: #fff;
	}
	.address_text{
		font-size: 24rpx;
		color: #fff;
		margin-top: 10rpx;
	}
	.address_icon{
		height: 35rpx;
		width: 35rpx;
	}
	
	.module{
		position: relative;
		width: 700rpx;
		padding: 30rpx;
		box-sizing: border-box;
		border-radius: 20rpx;
		margin: 0 auto 30rpx auto;
		background: #fff;
	}
	.module_data{
		display: flex;
		align-items: center;
		margin-left: 5rpx;
	}
	.module_img{
		height: 130rpx;
		width: 130rpx;
		margin-right: 30rpx;
	}
	.module_content{
		flex: 1;
	}
	.txt-yel{color:#FF9900}
	.module_btn{
		height: 65rpx;
		padding: 0 40rpx;
		color: #fff;
		font-size: 28rpx;
		display: flex;
		align-items: center;
		justify-content: center;
		border-radius: 100rpx;
		background: #0993fe;
	}
	.module_title{
		font-size: 30rpx;
		color: #222;
	}
	.module_item{
		margin-top: 10rpx;
		color: #616161;
		display: flex;
		align-items: center;
		font-size: 28rpx;
	}
	.module_time{
		padding: 0 10rpx;
		height: 35rpx;
		line-height: 33rpx;
		font-size: 22rpx;
		color: #d55c5f;
		border: 1rpx solid #d55c5f;
	}
	.module_num{
		display: flex;
		align-items: center;
		margin-top: 20rpx;
	}
	.module_lable{
		font-size: 24rpx;
		color: #666;
		line-height: 24rpx;
		border-right: 1px solid #e0e0e0;
		padding: 0 15rpx 0 0;
		margin-right: 15rpx;
	}
	.module_view{
		display: flex;
		flex: 1;
		align-items: center;
	}
	.module_view image{
		height: 60rpx;
		width: 60rpx;
		border-radius: 100rpx;
		margin-right: 10rpx;
	}
	.module_tag{
		height: 50rpx;
		background: #fefae8;
		color: #b37e4b;
		font-size: 24rpx;
		padding: 0 10rpx;
		line-height: 50rpx;
	}
	.module_end{
		color: #999;
		background: #f0f0f0;
		margin-left: 5rpx;
		padding: 0 20rpx
	}
	
	.alert{
		position: fixed;
		height: 100%;
		width: 100%;
		top: 0;
		left: 0;
		z-index: 10;
		background: rgba(0, 0, 0, 0.7);
	}
	.alert_none{
		position: absolute;
		top: 0;
		left: 0;
		height: 100%;
		width: 100%;
	}
	.alert_module{
		position: absolute;
		width: 100%;
		box-sizing: border-box;
		bottom: 0;
		left: 0;
		padding: 30rpx;
		background: #fff;
	}
	.alert_opt{
		display: flex;
		align-items: center;
	}
	.alert_table{
		display: flex;
		flex: 1;
	}
	.alert_view{
		font-size: 35rpx;
		color: #333;
		padding-bottom: 5rpx;
		margin-right: 35rpx;
		border-bottom: 1px solid #fff;
	}
	.alert_active{
		color: #0993fe;
		border-color: #0993fe;
	}
	.alert_cancel{
		color: #999;
		font-size: 35rpx;
		background: #fff;
		padding: 5rpx 20rpx;
	}
	.alert_btn{
		color: #333;
		font-size: 35rpx;
		background: #fad450;
		padding: 5rpx 20rpx;
		border-radius: 10rpx;
	}
	.alert_box{
		height: 700rpx;
		margin-top: 50rpx;
	}
	.alert_item{
		background: #f7f7f7;
		color: #333;
		font-size: 26rpx;
		height: 90rpx;
		display: flex;
		border-radius: 10rpx;
		align-items: center;
		justify-content: center;
		margin-top: 30rpx;
	}
	.alert_current{
		border: 1px solid #fad450;
		background: #fefaeb;
	}
</style>
