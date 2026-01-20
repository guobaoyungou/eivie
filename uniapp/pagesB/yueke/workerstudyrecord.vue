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
						<text v-if="item.status==4" class="st3">待预约</text>
						<text v-if="item.status==0 && item.start_study_time == ''" class="st3">待预约</text>
						<text v-if="item.status==0 && item.start_study_time != ''" class="st1">待上课</text>
						<text v-if="item.status==1" class="st0">上课中</text>
						<text v-if="item.status==2" class="st4">已完成</text>
						<text v-if="item.status==3" class="st4">已退款</text>
					</view>
					<view class="content" style="border-bottom:none">
						<view class="detail">
							<text class="t1">{{item.name}}</text>
							<text class="t2">顾客：{{item.member.nickname}}</text>
							<text class="t2" v-if="item.start_study_time">开课时间：{{item.start_study_time}}</text>
						</view>
						<view class="comment" v-if="item.comment">
							<text class="t2">{{t('教练')}}点评：{{item.comment}}</text>
						</view>
					</view>
					<view class="op">
						<view @tap="commentopen" class="btn1" :style="{background:t('color1')}" :data-id="item.id" v-if="item.status == 2 && item.comment == ''">点评</view>
						<view @tap="gotoclass"  class="btn2" :data-id="item.id" v-if="item.status == 0 && item.start_study_time != ''">开始上课</view>
						<view @tap.stop="goto" :data-url="'workeryueke?orderid=' + item.orderid + '&id='+ item.id" class="btn2" v-if="(item.status == 0 && item.start_study_time == '') || item.status ==4 ">约课</view>
					</view>
				</view>
			</block>
		</view>
		
		<nomore v-if="nomore"></nomore>
		<nodata v-if="nodata"></nodata>
		<!-- 评价弹窗 -->
		<uni-popup id="popupcomment" ref="popupcomment" type="dialog" :maskClick="false">
			<view class="uni-popup-dialog">
				<view class="uni-dialog-title">
					<text class="uni-dialog-title-text">点评</text>
				</view>
				<view class="uni-dialog-content" >
					<textarea class="uni-dialog-input" placeholder="请输入您的评价" @input="setcomment" v-model="comment"></textarea>
				</view>
				<view class="uni-dialog-button-group">
					<view class="uni-dialog-button" @tap="commentclose">
						<text class="uni-dialog-button-text">取消</text>
					</view>
					<view class="uni-dialog-button uni-border-left" @tap="subcomment">
						<text class="uni-dialog-button-text uni-button-color">确定</text>
					</view>
				</view>
			</view>
		</uni-popup>
		
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
			comment:'',
			thisid:''
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
      app.post('ApiYueke/workerstudyrecord', {orderid:orderid,pagenum: pagenum,keyword:that.keyword}, function (res) {
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
		commentopen:function(e){
			var id = e.currentTarget.dataset.id;
			this.thisid = id;
			this.$refs.popupcomment.open();
		},
		commentclose:function(){
			this.thisid = '';
			this.comment = '';
			this.$refs.popupcomment.close();
		},
		setcomment:function(e){
			this.comment = e.detail.value;
		},
		gotoclass:function(e){
			var that = this
			var id = e.currentTarget.dataset.id;
			
			app.post('ApiYueke/gotoclass',{id:id},function(res){
				if(res.status == 1){
					app.success(res.msg);
					setTimeout(function () {
						that.getdata();
					}, 1000)
					return;
				}
				return app.error(res.msg);
			})
		},
		subcomment:function(e){
			this.$refs.popupcomment.close();
			var that = this
			app.post('ApiYueke/setcomment', {id: that.thisid,comment:that.comment }, function (res) {
				//赋空值
				that.thisid = '';
				that.comment = '';
				
				app.success(res.msg);
				setTimeout(function () {
					that.getdata();
				}, 1000)
			})
		},
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
.order-box .head .st8{ width: 140rpx; color: #ff55ff; text-align: right; }

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