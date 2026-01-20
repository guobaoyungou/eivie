<template>
<view class="container">
	<block v-if="isload">
		<view style="width:100%;height:10rpx"></view>
		<view class="order-content">
			<block v-for="(item, index) in datalist" :key="index">
				<view class="order-box" @tap.stop="goto" :data-url="'orderdetail?id=' + item.id">
					<view class="head">
						<view>订单号：{{item.ordernum}}</view>
						<view class="flex1"></view>
						<text v-if="item.status==0 && item.start_study_time == ''" class="st3">待预约</text>
						<text v-if="item.status==0 && item.start_study_time != ''" class="st1">待上课</text>
						<text v-if="item.status==1" class="st3">上课中</text>
						<text v-if="item.status==2" class="st4">已完成</text>
						<text v-if="item.status==3" class="st4">已退款</text>
					</view>
					<view class="content" style="border-bottom:none">
						<view class="detail">
							<text class="t1">{{item.name}}</text>
							<text class="t2">{{t('教练')}}：{{item.workerinfo.realname}} {{item.workerinfo.dengji||''}}</text>
							<text class="t2" v-if="item.start_study_time">开课时间：{{item.start_study_time}}</text>
							<text class="t2" v-if="item.hx_time">结束时间：{{item.hx_time}}</text>
						</view>
						<view class="comment" v-if="item.comment">
							<text class="t2">{{t('教练')}}点评：{{item.comment}}</text>
						</view>
					</view>
					<view class="op">
						<view @tap="hexiao" :data-id="item.id" class="btn1" :style="{background:t('color1')}" v-if="item.status == 1">上课完成</view>
						<view @tap="cancel" :data-id="item.id" class="btn2" v-if="item.status == 0 && item.start_study_time != ''">取消</view>
					</view>
				</view>
			</block>
		</view>
		
		<nomore v-if="nomore"></nomore>
		<nodata v-if="nodata" text="暂无学习记录"></nodata>
	
		
	</block>
	<loading v-if="loading"></loading>
	<dp-tabbar :opt="opt"></dp-tabbar>
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

      datalist: [],
      pagenum: 1,
      nomore: false,
      nodata: false,
			keyword:'',
			comment:''
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
	  if (!this.nodata && !this.nomore) {
	    this.pagenum = this.pagenum + 1;
	    this.getdata(true);
	  }
	},
  methods: {
    getdata: function (loadmore) {
			if(!loadmore){
				this.pagenum = 1;
				this.datalist = [];
			}
      var that = this;
      var pagenum = that.pagenum;
			
			var orderid = 0;
			if(that.opt && that.opt.orderid){
				orderid = that.opt.orderid;
			}
			
			that.nodata = false;
			that.nomore = false;
			that.loading = true;
      app.post('ApiYueke/studyrecord', {orderid:orderid,pagenum: pagenum,keyword:that.keyword}, function (res) {
				that.loading = false;
        var data = res.datalist;
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
		searchConfirm:function(e){
			this.keyword = e.detail.value;
      this.getdata(false);
		},
		//取消
		cancel:function(e){
			var that = this;
			var id = e.currentTarget.dataset.id;
			app.confirm('确定要取消吗?', function () {
				app.showLoading('提交中');
			  app.post('ApiYueke/cancelyueke', {id:id}, function (data) {
					app.showLoading(false);
			    app.success(data.msg);
			    setTimeout(function () {
			      that.getdata();
			    }, 1000);
			  });
			});
		},
		hexiao:function(e){
			var that = this;
			var id = e.currentTarget.dataset.id;
			app.confirm('确定上课完成了吗?', function () {
				app.showLoading('提交中');
			  app.post('ApiYueke/hexiao', {id:id}, function (data) {
					app.showLoading(false);
			    app.success(data.msg);
			    setTimeout(function () {
			      that.getdata();
			    }, 1000);
			  });
			});
		}
  }
};
</script>
<style>
.container{ width:100%;}
.topsearch{width:94%;margin:10rpx 3%;}
.topsearch .f1{height:60rpx;border-radius:30rpx;border:0;background-color:#fff;flex:1}
.topsearch .f1 .img{width:24rpx;height:24rpx;margin-left:10px}
.topsearch .f1 input{height:100%;flex:1;padding:0 20rpx;font-size:28rpx;color:#333;}
.order-content{display:flex;flex-direction:column}
.order-box{ width: 94%;margin:10rpx 3%;padding:6rpx 3%; background: #fff;border-radius:8px}
.order-box .head{ display:flex;width:100%; border-bottom: 1px #f4f4f4 solid; height: 70rpx; line-height: 70rpx; overflow: hidden; color: #999;}
.order-box .head .f1{display:flex;align-items:center;color:#333}
.order-box .head .f1 image{width:34rpx;height:34rpx;margin-right:4px}
.order-box .head .st0{ width: 140rpx; color: #ff8758; text-align: right; }
.order-box .head .st1{ width: 140rpx; color: #ffc702; text-align: right; }
.order-box .head .st2{ width: 140rpx; color: #ff4246; text-align: right; }
.order-box .head .st3{ width: 140rpx; color: #999; text-align: right; }
.order-box .head .st4{ width: 140rpx; color: #bbb; text-align: right; }

.order-box .content{width: 100%; padding:16rpx 0px;border-bottom: 1px #f4f4f4 dashed;position:relative}
.order-box .content:last-child{ border-bottom: 0; }
.order-box .content image{ width: 140rpx; height: 140rpx;}
.order-box .content .detail{display:flex;flex-direction:column;margin-left:14rpx;flex:1}
.order-box .content .detail .t1{font-size:26rpx;line-height:36rpx;margin-bottom:10rpx;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:2;overflow:hidden;}
.order-box .content .detail .t2,.comment .t2{height: 36rpx;line-height: 36rpx;color: #666;overflow: hidden;font-size: 24rpx;margin-bottom: 10rpx;}

.order-box .op{ display:flex;justify-content:flex-end;align-items:center;width:100%; padding: 10rpx 0px; border-top: 1px #f4f4f4 solid; color: #555;}

.btn1{margin-left:20rpx;width:160rpx;height:60rpx;line-height:60rpx;color:#fff;border-radius:3px;text-align:center; font-size: 24rpx;}
.btn2{margin-left:20rpx;width:160rpx;height:60rpx;line-height:60rpx;color:#333;background:#fff;border:1px solid #cdcdcd;border-radius:3px;text-align:center;font-size: 24rpx;}

.uni-popup-dialog{width:300px;border-radius:5px;background-color:#fff}
.uni-dialog-title{display:flex;flex-direction:row;justify-content:center;padding-top:15px;padding-bottom:5px}
.uni-dialog-title-text{font-size:16px;font-weight:500}
.uni-dialog-content{display:flex;flex-direction:row;justify-content:center;align-items:center;padding:5px 15px 15px 15px}
.uni-dialog-button-group{display:flex;flex-direction:row;border-top-color:#f5f5f5;border-top-style:solid;border-top-width:1px}
.uni-dialog-button{display:flex;flex:1;flex-direction:row;justify-content:center;align-items:center;height:45px;cursor:pointer}
.uni-border-left{border-left-color:#f0f0f0;border-left-style:solid;border-left-width:1px}
.uni-dialog-button-text{font-size:14px}
.uni-button-color{color:#007aff}
.uni-dialog-input{flex:1;font-size:14px}
.comment{margin-left:14rpx;}
</style>