<template>
<view class="container">
	<block v-if="isload">
		<dd-tab :itemdata="['收益明细','本金明细']" :itemst="['1','2']" :st="st" :isfixed="false" @changetab="changetab"></dd-tab>
		<block v-if="st==2">
			<view class="item" v-for="(item,index) in datalist" :key="index">
				<view class="item-body">
					<view>
						<!-- <view class="row"><text class="label">用户 ID：</text><text>{{item.id}}</text></view> -->
						<view class="row" ><text class="label">变后余额：</text><text>￥{{item.after}}</text></view>
						<view class="row" >
							<text class="label">变动金额：</text>
							<text class="t1" v-if="item.money>0">+{{item.money}}</text>
							<text class="t2" v-else>{{item.money}}</text>
						</view>						
						<view class="row"><text class="label">创建时间：</text><text>{{dateFormat(item.createtime)}}</text></view>
						<view class="row"><text class="label">备注：</text><text>{{item.remark}}</text></view>
					</view>
				</view>
			</view>
		</block>
		<block v-if="st==1">
		<view class="item" v-for="(item,index) in datalist" :key="index">
			<view class="item-header">
				<text class="t1">{{item.day}}天 {{item.rate}}%</text>
				<!-- <text class="t2" :style="'color:'+(item.status==1?'red':'green')">{{item.status_str}}</text> -->
				<text class="t2" :style="'background:'+(item.status==1?'#CACACA':'#7DD0BC')">{{item.status_str}}</text>
			</view>
			<view class="item-body">
				<view>
					<!-- <view class="row"><text class="label">用户 ID：</text><text>{{item.id}}</text></view> -->
					<view class="row" ><text class="label">起始本金：</text><text>￥{{item.start_money}}</text></view>
					<view class="row" ><text class="label">累计本金：</text><text>￥{{item.total_money}}</text></view>
					<block v-if="item.status == 1">
						<view class="row" ><text class="label">累计收益：</text><text>￥{{item.yuji_income}}</text></view>
						<view class="row" ><text class="label">到期收益：</text><text>￥{{item.daozhang_money}}</text></view>
					</block>
					<block v-else>
						<view class="row" ><text class="label">预计收益：</text><text>￥{{item.yuji_income}}</text></view>
					</block>
					<view class="row"><text class="label">创建时间：</text><text>{{dateFormat(item.createtime)}}</text></view>
					<view class="row"><text class="label">到期时间：</text><text>{{dateFormat(item.endtime)}}</text></view>
				</view>
			</view>
			<!-- <view class="item-bottom" v-if="item.status==0">
				<view class="opt-btn"  @tap="del(item.id)">删除</view>
			</view> -->
		</view>
		</block>
		<nodata v-if="nodata"></nodata>
		<nomore v-if="nomore"></nomore>
		<view style="height: 20rpx;"></view>
	
	</block>
	<loading v-if="loading"></loading>
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
			pre_url:app.globalData.pre_url,
			keyword: '',
			datalist: [],
			pagenum: 1,
			pagelimit: 15,
			nomore: false,
			type:'desc',
			st: 1,
			nodata:false,
			randt:'',
			menudata:{}

    }
  },

  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.st = this.opt.st || 1;
		this.getdata();
  },
	onPullDownRefresh: function () {
		this.getdata();
	},
	onReachBottom: function () {
	  if (!this.nodata && !this.nomore) {
	    this.pagenum = this.pagenum + 1;
	    this.getdata(true);
	  }
	},
  methods: {
		getdata:function(loadmore){
			var that = this;
			if(!loadmore){
				this.pagenum = 1;
				this.datalist = [];
			}
			var that = this;
			var st = that.st;
			var pagenum = that.pagenum;
			that.loading = true;
			that.nodata = false;
			that.nomore = false;
		  app.post('ApiCommissionLingqiantong/order', {st: st,keyword:that.keyword,pagenum: pagenum}, function (res) {
					that.loading = false;
			var data = res.data;
			if (pagenum == 1) {
					uni.setNavigationBarTitle({
						title: '收益明细'
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
		del:function(id){
			var ids = [id];
			var that = this;
			app.confirm('确定删除吗?',function(){
				app.showLoading('删除中...');
				app.post('ApiCommissionLingqiantong/del', {ids:ids}, function (res) {
					app.showLoading(false);
			
				    if(res.status==1){
					    app.success(res.msg);
						setTimeout(function(){
							that.getdata()
						},1000)
					}else{
						app.error(res.msg);
					}
				});
			})
			
		},

		handleClickMask: function() {
			this.boxShow = !this.boxShow;
		},

		disabledScroll: function (e) {
			return false;
		},	
		searchConfirm:function(e){
			this.keyword = e.detail.value;
			this.getdata(false);
		},
		changetab: function (st) {
		  this.st = st;
		  uni.pageScrollTo({
		    scrollTop: 0,
		    duration: 0
		  });
		  this.getdata();
		},
		
  }
}
</script>
<style>
	.pd30{padding: 0 30rpx;}
	.uni-header{border-bottom: 1px solid #dddddd;}
	.item-icon{width: 60rpx; height: 60rpx;margin-right: 10rpx; border-radius: 50%;}
	.item{width:94%;margin:10rpx 3%;background: #fff;border-radius: 16rpx;border: 1px solid #efefef; padding:20rpx 20rpx}
	.item-header{border-bottom: 2rpx dashed #dddddd;display: flex;align-items: center;padding:0rpx 10rpx 20rpx 10rpx;justify-content: space-between;}
	.item-header .t1{font-size: 32rpx;font-weight: bold;}
	.item-header .t2{color: #fff;width: 120rpx; height: 56rpx;line-height: 56rpx;display: flex;justify-content: center;align-items: center;}
	.item-body{padding: 20rpx 10rpx;display: flex;justify-content: space-between;align-items: center;color: #333;line-height: 50rpx;font-size: 28rpx;}
	.item-body .row{display: flex;align-items: center;}
	.item-body .label{color: #666;width: 140rpx;flex-shrink: 0;text-align: right;}

	.item-opt{display: flex;justify-content: flex-end;}
	.top-title{width: 100%; height: 46px;background: #fff;top: var(--window-top);z-index: 11;display: flex;justify-content: center;align-items: center;font-size: 32rpx;}
	.topsearch{width:94%;margin:20rpx 3%;}
	.topsearch .f1{height:70rpx;border-radius:50rpx;border:0;background-color:#fff;flex:1}
	.topsearch .f1 .img{width:24rpx;height:24rpx;margin-left:10px}
	.topsearch .f1 input{height:100%;flex:1;padding:0 20rpx;font-size:28rpx;color:#333;}
	.topsearch .add-btn{height: 60rpx;display: flex;justify-content: center;align-items: center;margin-left:20rpx;padding:0 30rpx;border-radius: 50rpx;background: #2979ff;color: #fff;}


</style>