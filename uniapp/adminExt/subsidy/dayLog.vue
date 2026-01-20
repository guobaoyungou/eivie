<template>
	<view>
		<block v-if="isload">
		<view class="top-view flex-col" :style="{background:t('color1')}">
			<view class="points-view flex-col">
				<view class="points-title-view flex"><image :src="pre_url+'/static/img/jifen-F.png'" class="icon-class">我的{{t('返现积分')}}</image></view>
				<view class="points-num">{{total.subsidy_score}}</view>
			</view>
			<view class="points-view flex-col" style="padding-top: unset;">
				<view class="points-title-view flex" style="font-size: 32rpx;">累计贡献</view>
				<view class="points-num" style="font-size: 54rpx;">{{total.total_rangli || 0}}</view>
			</view>
			<!-- <view class="points-view flex-col" style="padding-top: unset;">
				<view class="points-title-view flex">
					<view class="points-num-rangli">累计贡献：{{total.total_rangli || 0}}</view>
				</view>
				
			</view> -->
			<view class="top-data-list flex flex-y-center">
				<view class="data-options flex-col">
					<view class="title-text">累计释放</view>
					<view class="num-text">{{total.commission || 0}}</view>
				</view>
				<view class="line-class"></view>
				<view class="data-options flex-col">
					<view class="title-text">今日释放</view>
					<view class="num-text">{{total.commission_today || 0}}</view>
				</view>
				<view class="line-class"></view>
				<view class="data-options flex-col">
					<view class="title-text">今日新增</view>
					<view class="num-text">{{total.today_add || 0}}</view>
				</view>
			</view>
		</view>
		<!-- 选项卡 -->
		<dd-tab :itemdata="[t('返现积分')+'明细','日汇总','释放记录']" :itemst="['1','0','2']" :st="st" :showstatus="showstatus" :ismoney="1" :isfixed="false" @changetab="changetab"></dd-tab>
			<view class="content" v-if="st==1 || st==2">
				<view class="topsearch flex-y-center sx">
					<text class="t1">期数筛选：</text>
					<view class="t2">
						<picker @change="chooseMonth" :range="release_item">
							<view class="uni-input" v-if="release_item[release_index]">{{release_item[release_index]}}</view>
							<view style="font-size:28rpx;color: #686868" v-else>请选择释放期数</view>
						</picker>
					</view>
				</view>
			</view>
		<view class="content" id="datalist">
			<block v-if="st==0">
			 <block v-for="(item, index) in datalist" :key="index"> 
				<view class="list-item" >
				  <view class="item-row">
					<view class="column">
					  <text>发放数量: {{item.score_total}}</text>
					  <text>剩余数量: {{item.score}}</text>
					</view>
					<view class="column">
					  <text>释放数量: {{item.have_release || 0}}</text>
					  <text>释放期数: {{item.release_num || 0}}</text>
					</view>
				  </view>
				  <!-- <view class="item-row highlight">
					<text v-if="item.status!=2">上期释放: {{item.last_circle_send}}</text>
					<text v-if="item.status==2">已完成</text>
				  </view> -->
				  <view class="item-row">
					<text class="time">{{item.createday}}</text>
					<block >
						<!-- <button @tap="goto" :data-url="'/pagesD/subsidy/subsidyScoreLog?day_id='+item.id" class="btn-mini2">查看明细</button> -->
					</block>
				  </view>
				</view>
			 </block>
			 </block>
			 <block v-if="st==1">
				 <block v-for="(item, index) in datalist" :key="index">
				   <view class="item">
				     <view class="f2" style="display: flex;justify-content: space-between;">
				       <view class="t1" style="color: #000;">
				         变动金额：
				         <text v-if="item.score>0" style="color: green;">+{{item.score}}</text>
				         <text v-else style="color: red;">{{item.score}}</text>
				       </view>
				     </view>
				     <view class="f1">
				       <view class="t2">{{item.remark}}</view>
				     </view>
				     <view class="f1">
				       <view class="t2">{{item.createtime}}</view>
				     </view>
				   </view>
				 </block>
			 </block>
			 <block v-if="st==2">
			   <view v-for="(item, index) in datalist" :key="index" class="item">
			     <view class="f1">
			         <text class="t1" style="color: green;">释放{{t('佣金')}}：+{{item.member_score_bonus}}元</text>
			 			  <text class="t1" style="color: red;">本期{{t('返现积分')}}：{{item.member_score}}</text>
			     </view>
			 		  <view class="f1">
			 		    <view class="t2">{{item.remark}}</view>
			 		  </view>
			 		  <view class="f1">
			 		    <view class="t2">{{item.createtime}}</view>
			 		  </view>
			   </view>
			 </block>
		 </view>
		<nodata v-if="nodata"></nodata>
		<nomore v-if="nomore"></nomore>
		</block>
		<loading v-if="loading"></loading>
		<dp-tabbar :opt="opt"></dp-tabbar>
		<popmsg ref="popmsg"></popmsg>
	</view>
</template>

<script>
	var app = getApp();
	export default{
		data(){
			return{
				isload:false,
				pre_url: app.globalData.pre_url,
				nodata:false,
				nomore:false,
				loading:false,
				menuindex:-1,
				datalist: [],
				pagenum: 1,
				total:{},
				is_withdraw:0,
				showstatus:[1,1,1],
				st:1,
				set:{},
				opt:{},
				release_item:[],
				release_index:0
			}
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
				var st = this.st;
				if(st==0){
					this.getday(loadmore)
				}else{
					this.getlog(loadmore)
				}
			},
		  getday: function (loadmore) {
				if(!loadmore){
					this.pagenum = 1;
					this.datalist = [];
				}
				var that = this;
				var pagenum = that.pagenum;
				var st = that.st;
				that.nodata = false;
				that.nomore = false;
				that.loading = true;
		    app.post('ApiAdminFinance/subsidyScoreDay', {st: st,pagenum: pagenum}, function (res) {
				that.loading = false;
				var data = res.datalist;
				if (pagenum == 1) {
					uni.setNavigationBarTitle({
						title: that.t('返现积分') + '记录'
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
		  getlog: function (loadmore) {
		  	if(!loadmore){
		  		this.pagenum = 1;
		  		this.datalist = [];
		  	}
		  	var that = this;
		  	var pagenum = that.pagenum;
		  	that.nodata = false;
		  	that.nomore = false;
		  	that.loading = true;
		  	app.post('ApiAdminFinance/subsidyScoreLog', {st: that.st,pagenum: pagenum,day_id:that.day_id,release_index:that.release_index}, function (res) {
		        that.loading = false;
		        var data = res.data;
		        that.myscore = res.myscore;
		  	  that.score_commission = res.score_commission;
		  	  that.remain_score = res.remain_score;
		  	  that.release_item = res.release_item;
		        if (pagenum == 1) {
		          that.textset = app.globalData.textset;
		          uni.setNavigationBarTitle({
		            title: that.t('返现积分') + '明细'
		          });
		  
		          that.datalist = data;
		          if (data.length == 0) {
		            that.nodata = true;
		          }
				  that.total = res.total;
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
			changetab: function (st) {
			  this.st = st;
			  uni.pageScrollTo({
			    scrollTop: 0,
			    duration: 0
			  });
			  this.getdata();
			},
			chooseMonth:function(e){
				this.release_index  = e.detail.value;
				this.getdata();
			},
		}
	}
</script>

<style>
	.top-view{width: 100%;background: #fb443e;align-items: center;}
	.points-view{padding: 40rpx 0rpx;width: 100%;text-align: center;}
	.points-view .points-title-view{font-size: 28rpx;color: #ecdd36;align-items: center;width: 100%;justify-content: center;}
	.points-view .points-title-view .icon-class{width: 24rpx;height: 24rpx;margin-right: 5rpx;}
	.points-view .points-num{font-size: 50rpx;color: #fff;margin-top: 10rpx;}
	.points-view .points-num-rangli{font-size: 54rpx;color: white;}
	.top-data-list{width: 100%;padding: 35rpx 0rpx;justify-content: center;}
	.top-data-list .data-options{text-align: center;max-width: 32%;width: auto;min-width: 30%;}
	.top-data-list .line-class{height: 50rpx;border-left: 1rpx #e5d734 solid;}
	.top-data-list .data-options .title-text{font-size: 20rpx;color: rgba(255, 255, 255, .8);font-weight: bold;white-space: nowrap;}
	.top-data-list .data-options .num-text{font-size: 34rpx;color: #ecdd36;margin-top: 15rpx;}
	.list-title-view{font-size: 28rpx;color: #828282;padding: 30rpx 20rpx;}
	.options-view{background: #fff;border-bottom: 1px #f6f6f6 solid;padding: 30rpx 20rpx;align-items: center;}
	.options-view .left-view{}
	.options-view .left-view .left-title{font-size: 26rpx;color: #333;font-weight: bold;white-space: nowrap;}
	.options-view .left-view .time-text{font-size: 24rpx;color: #828282;margin-top: 20rpx;}
	.price-view{font-size: 30rpx;color: #fb443e;}
	
	.tabs {
	  display: flex;
	  background-color: white;
	  margin-top: 20rpx;
	}
	.tab {
	  flex: 1;
	  text-align: center;
	  padding: 20rpx 0;
	  font-size: 28rpx;
	}
	.tab.active {
	  color: #ff4d4f;
	  border-bottom: 4rpx solid #ff4d4f;
	}
	
	.content {
	  margin-top: 20rpx;
	}
	.list-item {
	  background-color: white;
	  margin: 20rpx;
	  padding: 20rpx;
	  border-radius: 10rpx;
	}
	
	.item-row {
	  display: flex;
	  justify-content: space-between;
	  margin-bottom: 10rpx;
	  font-size: 30rpx;
	}
	
	.item-row:last-child {
	  align-items: center; /* 垂直居中对齐最后一行的内容 */
	}
	
	.column {
	  display: flex;
	  flex-direction: column;
	}
	
	.column text {
	  margin-bottom: 10rpx;
	}
	
	.highlight {
	  color: #52c41a;
	}
	
	.time {
	  color: #999;
	  font-size: 24rpx;
	  flex: 1; /* 让时间占据剩余空间 */
	}
	
	.btn-mini2, .btn-mini3 {
	  width: 140rpx;
	  height: 50rpx;
	  text-align: center;
	  border: 1px solid #e6e6e6;
	  border-radius: 10rpx;
	  display: inline-flex;
	  align-items: center;
	  justify-content: center;
	  font-size: 24rpx;
	  margin-left: auto; /* 将按钮推到右侧 */
	}
	
	.btn-mini2 {
	  background-color: #ff4d4f;
	  color: white;
	}
	
	.btn-mini3 {
	  background-color: #A5A5A5;
	  color: white;
	}
	
	.content{ width:94%;margin:0 3%;}
	.content .item{width:100%;margin:20rpx 0;background:#fff;border-radius:5px;padding:20rpx 20rpx;line-height: 40rpx;}
	.content .item .f1{flex:1;display:flex;flex-direction:column;margin: 20rpx auto;}
	.content .item .f1 .t1{color:#000000;font-size:30rpx;word-break:break-all;overflow:hidden;text-overflow:ellipsis;}
	.content .item .f1 .t2{color:#666666}
	.content .item .f1 .t3{color:#666666}
	.content .item .f2{ flex:1;font-size:32rpx;text-align:right}
	.content .item .f2 .t1{color:#03bc01}
	.content .item .f2 .t2{color:#000000}
	.content .item .f3{ flex:1;font-size:32rpx;text-align:right}
	.content .item .f3 .t1{color:#03bc01}
	.content .item .f3 .t2{color:#000000}
	.topsearch{width:100%;margin:20rpx 0;background:#fff;border-radius:5px;padding:20rpx 20rpx;line-height: 40rpx;}
	.topsearch .f1{height:60rpx;border-radius:30rpx;border:0;background-color:#fff;flex:1}
	.topsearch .f1 .img{width:24rpx;height:24rpx;margin-left:10px}
	.topsearch .f1 input{height:100%;flex:1;padding:0 20rpx;font-size:28rpx;color:#333;}
</style>