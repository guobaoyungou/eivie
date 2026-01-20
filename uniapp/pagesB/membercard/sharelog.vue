<template>
	<view class="container">
		<block v-if="isload">
	
	
			<view class="center2">
					<view class="item" v-if="datalist" v-for="(item,index) in datalist">
						<view class="headimg">
							<image  :src="item.headimg" />
							<view class="f1">
								<view class="t1">{{item.nickname}}</view>
								<view class="t2">{{dateFormat(item.createtime)}}</view>
							</view>
						</view>
						<view class="kkimg"><image :src="pre_url+'/static/img/membercard/kkimg.png'"></view>
					</view>
			</view>
			
		</block>		
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
			textset:{},
			data:{},
			nomore:false,
			nodata:false,
			datalist: [],
			pagenum: 1,

    }
  },
  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.getdata();
  },
	onPullDownRefresh: function () {
	  this.pagenum = 1;
	  this.datalist = [];
		this.getdata();
	},
	onReachBottom: function () {
	  if (!this.nodata && !this.nomore) {
	    this.pagenum = this.pagenum + 1;
	    this.getdata();
	  }
	},
	methods: {
		getdata: function () {
			var that = this; 
			that.loading = true;
		  var pagenum = that.pagenum;
			app.post('ApiMembercardCustom/sharelog', {id: that.opt.id,pagenum: pagenum}, function (res) {
				that.loading = false;
				uni.setNavigationBarTitle({
					title:  '我的奖励'
				});
				if(res.status==1){
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
					that.loaded();
				}else{
					if(res.msg){
							app.alert(res.msg);
					}
					setTimeout(function () {
						app.goto(res.url,'redirectTo');
					}, 1000);
				}
			});
		},
	}
}
</script>
<style>
	page{ background: #fff;}
	.center2{ margin: 30rpx 50rpx;}
	.center2 .title{ text-align: center; font-size:30rpx; font-weight:bold;margin:50rpx;}
	.center2 .item{ display: flex;position: relative;border-bottom:2rpx solid #F6F6F6;padding-bottom: 20rpx;}
	.center2 .headimg { display: flex;align-items: center;}
	.center2 .headimg .f1{ color: #333;}
	.center2 .headimg image{ width: 100rpx;height: 100rpx;border-radius: 50%;margin-right: 20rpx;}
	.center2 .kkimg{ position: absolute;right: 0;}
	.center2 .kkimg image{ width: 120rpx;height: 120rpx;}
	
	
	
</style>
