<template>
<view class="container">
	<block v-if="isload">
		<form @submit="subform">
			<view class="content">
				<view class="info-item">
				  <view class="t1">开课日期</view>
				  <view class="t2">
						<picker class="picker" mode="date" :value="date" :start="dateFormat('','Y-m-d')" @change="bindDateChange">
							<view v-if="date">{{date}}</view>
							<view v-else>请选择日期</view>
						</picker>
				  </view>
				  <image class="t3" :src="pre_url+'/static/img/arrowright.png'"/>
				</view>
				<view class="info-item">
				  <view class="t1">开课时间</view>
				  <view class="t2">
						<picker class="picker" mode="time" :value="time" @change="bindTimeChange">
							<view v-if="time">{{time}}</view>
							<view v-else>请选择时间</view>
						</picker>
				  </view>
				  <image class="t3" :src="pre_url+'/static/img/arrowright.png'"/>
				</view>
			</view>
			<view style="padding:30rpx 0">
				<button form-type="submit" class="set-btn" :style="{background:'linear-gradient(90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'}">立即提交</button>
			</view>
		</form>
		
	</block>
	<loading v-if="loading"></loading>
	<dp-tabbar :opt="opt"></dp-tabbar>
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
			time:'',
			date:'',
			pre_url: app.globalData.pre_url,
    };
  },

  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		if(this.opt.id){
			this.getdata();
		}
		this.loaded();
  },
  methods: {
		getdata: function (loadmore) {
		  var that = this;
			that.nodata = false;
			that.nomore = false;
			that.loading = true;
		  app.get('ApiYueke/yueke', {id:that.opt.id}, function (res) {
				that.loading = false;
				if(res.status == 1){
					if(res.data && res.data.date){
						that.date = res.data.date;
					}
					if(res.data && res.data.time){
						that.time = res.data.time;
					}
				}
		  });
		},
		subform: function (e) {
			var that = this;	
			var orderid = that.opt.orderid;
			var id = '';
			if(that.opt.id){
				id = that.opt.id;
			}
			if (that.time == '') {
				return app.error('请选择开课日期');
			}
			
			if(that.date == ''){
				return app.error('请选择开课日期');
			}
			app.showLoading('提交中');
			app.post("ApiYueke/yueke", {orderid:orderid,date:that.date,time:that.time,id:id}, function (res) {
				app.showLoading(false);
				if(res.status==1){
					app.success(res.msg)
					setTimeout(function () {
						app.goto('/pagesExt/yueke/workerorderlist')
					}, 1000)
				}else{
					return app.error(res.msg);
				}
		  });
		},
		bindDateChange: function(e) {
			this.date = e.detail.value
		},
		bindTimeChange: function(e) {
			this.time = e.detail.value
		},
  }
};
</script>
<style>
.content{width:94%;margin:20rpx 3%;background:#fff;border-radius:5px;padding:0 20rpx;}
.info-item{ display:flex;align-items:center;width: 100%; background: #fff;padding:0 3%;  border-bottom: 1px #f3f3f3 solid;height:120rpx;line-height:96rpx}
.info-item:last-child{border:none}
 .t1{ width: 200rpx;color: #333;font-weight:bold;height:96rpx;line-height:96rpx}
.info-item .t2{ color:#444444;text-align:right;flex:1;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:1;overflow:hidden;}
.info-item .t3{ width: 26rpx;height:26rpx;margin-left:20rpx}
.remark {
	height: 320rpx;padding:0 3%; 
}
.remark .t1 {width: 100%; flex: inherit;}
.remark textarea { height: 100px;}
.set-btn{width: 90%;margin:0 5%;height:96rpx;line-height:96rpx;font-size:34rpx;border-radius:10rpx;color:#FFFFFF;}
picker {height: 96rpx;}

.pstime-item{display:flex;border-bottom: 1px solid #f5f5f5;padding:20rpx 30rpx;}
.pstime-item .radio{flex-shrink:0;width: 32rpx;height: 32rpx;background: #FFFFFF;border: 2rpx solid #BFBFBF;border-radius: 50%;margin-right:30rpx}
.pstime-item .radio .radio-img{width:100%;height:100%}

</style>