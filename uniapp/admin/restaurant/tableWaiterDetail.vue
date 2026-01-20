<template><!-- todo 展示关联的预约信息 -->
	<view class="container">
		<block v-if="isload">
			<view class="head-bg">
				<h1 class="text-center">{{detail.name}}</h1>
				<h2 class="title flex" v-if="detail.status == 2 && order.status != 3"><image :src="pre_url+'/static/img/admin/fork.png'" class="image"></image><view>用餐中</view></h2>
				<h2 class="title flex" v-else-if="detail.status == 0"><image :src="pre_url+'/static/img/admin/fork.png'" class="image"></image><view>空闲中</view></h2>
				<h2 class="title flex" v-else-if="order.status == 3"><image :src="pre_url+'/static/img/admin/fork.png'" class="image"></image><view>已结算，待清台</view></h2>
				<h2 class="title flex" v-else-if="detail.status == 3"><image :src="pre_url+'/static/img/admin/fork.png'" class="image"></image><view>清台中</view></h2>
				<!-- <view class="text-center mt20">请在预定时间20分钟内到店。</view> -->
			</view>
			<form @submit="subform">
			<view class="card-view" v-if="detail.status == 0">
				<view class="card-wrap">
					<view class="card-title">餐桌信息</view>
					<view class="info-item">
						<view class="t1">桌台</view>
						<view class="t2">{{detail.name}}</view>
					</view>
					<view class="info-item">
						<view class="t1">座位数</view>
						<view class="t2">{{detail.seat}}</view>
					</view>
				</view>
				<view class="card-wrap">
					<view class="card-title ">用餐信息</view>
					<view class="info-item">
						<view class="t1">人数</view>
						<view class="t2">
							<picker @change="numChange" :value="nindex" :range="numArr" name="renshu">
								<view class="picker">{{numArr[nindex]}}</view>
							</picker>
						</view>
					</view>
					<view class="info-item">
						<view class="t1">顾客姓名</view>
						<view class="t2"><input type="text" data-name="linkman" name="linkman" placeholder="选填"></view>
					</view>
					<view class="info-item">
						<view class="t1">手机号</view>
						<view class="t2"><input type="number" data-name="tel" name="tel" placeholder="选填"></view>
					</view>
					<view class="info-item info-textarea">
						<view class=" remark" >
							<view class="t1">备注</view>
							<view class="">
							<textarea data-name="message" name="message" placeholder="如您有其他需求请填写" placeholder-style="color:#ABABABFF"></textarea>
							</view>
						</view>
					</view>
				</view>
			</view>
			<view class="btn-view button-sp-area">
				<!-- <button type="default" class="btn-default">取消</button> -->
				<button type="primary" form-type="submit" v-if="detail.status == 0">开始用餐</button>
			</view>
			</form>
			
			<view class="card-view" v-if="detail.status == 2">
				<view class="card-wrap">
					<view class="content">
						<!-- status  1已支付;2已发货,3已收货 -->
						 <view class="item" v-if="orderGoodsSum == 0 && order.status == 0" @tap="goto" :data-url="'/restaurant/shop/index?type=admin&tableId='+detail.id + '&bid=' + detail.bid + '&orderid=' + order.id">
								<image :src="pre_url+'/static/img/admin/dish.png'" class="image"/>
								<text class="t3">点餐</text>
						 </view>
						 <view class="item" v-if="orderGoodsSum > 0 && order.status == 0" @tap="goto" :data-url="'/restaurant/shop/index?type=admin&tableId='+detail.id + '&bid=' + detail.bid">
								<image :src="pre_url+'/static/img/admin/dish.png'" class="image"/>
								<text class="t3">加菜</text>
						 </view>
						 <view class="item" v-if="orderGoodsSum > 0 && order.status == 0"  @tap="toSettle" >
								<image :src="pre_url+'/static/img/admin/money.png'" class="image"/>
								<text class="t3">结算</text>
						 </view>
						 <view class="item" v-if="order.status == 0" @tap="goto" :data-url="'tableWaiter?operate=change&origin='+detail.id">
								<image :src="pre_url+'/static/img/admin/change.png'" class="image"/>
								<text class="t3">换桌</text>
						 </view>
						 <view class="item" @tap="clean">
								<image :src="pre_url+'/static/img/admin/clean.png'" class="image"/>
								<text class="t3">清台</text>
						 </view>
						 <view class="item" v-if="order.status == 0" @tap="close">
								<image :src="pre_url+'/static/img/admin/close.png'" class="image"/>
								<text class="t3">关闭</text>
						 </view>
					</view>
				</view>
				<view class="card-wrap">
					<view class="card-title">餐桌信息</view>
					<view class="info-item">
						<view class="t1">桌台</view>
						<view class="t2">{{detail.name}}</view>
					</view>
					<view class="info-item">
						<view class="t1">人数/座位数</view>
						<view class="t2">{{order.renshu}}/{{detail.seat}}</view>
					</view>
					<view class="info-item" v-if="order.tea_fee">
						<view class="t1">{{detail.tea_fee_text}}</view>
						<view class="t2">￥{{order.tea_fee}}</view>
					</view>
					<view class="info-item"  >
						<view class="t1">客户信息</view>
						<view class="t2"><text v-if="!isNull(order.linkman)">{{order.linkman}} </text> <text v-if="!isNull(order.tel)">{{order.tel}}</text></view>
					</view>
					<view class="info-item">
						<view class="t1">时间</view>
						<view class="t2">{{dateFormat(order.createtime)}}</view>
					</view>
					<view class="info-item info-textarea" v-if="!isNull(order.message)">
						<view class="t1">备注信息</view>
						<view class="t2">{{order.message}}</view>
					</view>
				</view>
				<view class="card-wrap" v-if="detail.timing_fee_type && detail.timing_fee_type > 0">
					<view class="card-title">计时收费</view>
					<image v-if="detail.is_start==0" class="img" :src="pre_url+'/static/img/admin/start.png'" @tap="startpause" :data-status='1'></image>
					<image v-else class="img" :src="pre_url+'/static/img/admin/pause.png'" @tap="startpause" :data-status='0'></image>
					
					<view class="info-item" style="justify-content: center;" v-if="detail.timing_log.length > 0" v-for="(item,index) in detail.timing_log ">
						<view class="t1">{{item.start_time}} ~ {{item.end_time}}</view>
						<view class="t2" style="flex: 0.3">{{item.num}} 分钟</view>
					</view>
					<view class="info-item info-textarea">
						<view class="t1">{{detail.timing_fee_text}}</view>
						<view class="t2">￥{{detail.timing_money}}</view>
					</view>
				</view>
				
				<view class="card-wrap card-goods" v-if="orderGoods.length > 0">
					<view class="flex">
						<view class="card-title">已点菜品({{orderGoodsSum}})</view>
						<view class="flex1"></view>
						<view class="btn-text" v-if="order.status ==0" @tap="goto" :data-url="'shoporderEdit?id='+order.id">修改菜品</view>
					</view>
					<view class="info-item" v-for="(item,index) in orderGoods" :key="index">
						<view class="t1">
							<view style="line-height: 60rpx;">{{item.name}}<text v-if="item.ggname">[{{item.ggname}}{{item.jltitle?item.jltitle:''}}]</text></view>
							
							<view v-if="(item.ggtext && item.ggtext.length)" class="flex-col">
								<block v-for="(item2,index) in item.ggtext">
									<text class="ggtext" >{{item2}}</text>
								</block>
							</view>
						</view>
						<view class="t2">x 
							<text >{{item.num}}<text style="font-size: 20rpx;"v-if="item.product_type && item.product_type ==1">斤</text></text> 
						</view>
						
						<view class="t3">￥{{item.real_totalprice}}</view>
					</view>
					<view class="info-item">
						<view class="t1">合计</view>
						<view class="t2" >x{{orderGoodsSum}}</view>
						<view class="t3">￥{{order.totalprice}}</view>
					</view>
				</view>
				<view class="card-wrap" v-else>
					<view class="info-item">
						<view class="t1">还未点菜</view>
					</view>
				</view>
			</view>
			<view class="btn-view button-sp-area mb">
				<button type="primary" @tap="cleanOver" v-if="detail.status == 3">清理完成</button>
			</view>
			<view class="btn-view button-sp-area mb">
				<button type="default" class="btn-default" @tap="goto" data-url="tableWaiter">返回餐桌列表</button>
			</view>
			
		</block>
		<!-- 查找会员 -->
		<uni-popup id="searchmember" ref="searchmember" type="dialog">
		  <view class="uni-popup-dialog">
		    <view class="uni-dialog-title" style="margin-bottom: 10rpx">
		      <text class="uni-dialog-title-text">下单会员</text>
		    </view>
		    <view class="sc-item flex-xy-center" >
		      <input type="text" class="uni-input" v-model="member_keyword"  placeholder="请输入手机号或会员编号"/>
			  <button class="btn-mini" @tap="searchMember">搜索</button>
		    </view>
			<view class="sc-item flex-y-center"  v-if="search_member && search_member.id > 0 ">
				 <view  class="head-img">
					  <image  :src="search_member.headimg"></image>
				 </view>
				 <view  style="margin-left: 20rpx;line-height: 50rpx;">
					 <view>{{search_member.nickname}}</view>
					  <view>{{t('会员')}}编号：{{search_member.id}}</view>
				 </view>
			 </view>
		    <view class="uni-dialog-button-group">
		      <view class="uni-dialog-button" @click="toWaiterPay"  >
		        <text class="uni-dialog-button-text uni-button-color">跳过</text>
		      </view>
		      <view class="uni-dialog-button uni-border-left"  @click="updateorder">
		        <text class="uni-dialog-button-text ">确定</text>
		      </view>
		    </view>
		  </view>
		</uni-popup>
		
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
				pre_url:app.globalData.pre_url,
				
				detail:{},
				order:{},
				orderGoods:[],
				business:{},
				nindex:0,
				orderGoodsSum:0,
				numArr:[1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20],
				member_keyword:'',//搜索会员关键词
				search_member:[],//搜索出来的会员信息
				sc_nodata:false//未查询到会员
			}
		},
		onLoad: function (opt) {
			this.opt = app.getopts(opt);
			this.getdata();
		},
		onShow:function(){
			this.getdata();
		},
		onPullDownRefresh: function () {
			this.getdata();
		},
		methods: {
			getdata: function () {
				var that = this;
				that.loading = true;
				app.get('ApiAdminRestaurantTable/detail', {id:that.opt.id}, function (res) {
					that.loading = false;
					if(res.status == 0){
						app.alert(res.msg,function(){
							app.goback();
						});return;
					}
					that.detail = res.info;
					that.order = res.order;
					that.orderGoods = res.order_goods;
					that.orderGoodsSum = res.order_goods_sum;
					
					that.loaded();
					//
				});
			},
			subform: function (e) {
				var that = this;
				var info = e.detail.value;
				info.tableId = that.opt.id;
				info.renshu = that.numArr[that.nindex]
				// console.log(info);return;
				if (info.renshu <= 0) {
					app.error('请选择人数');
					return false;
				}
				if (info.tableId == 0) {
					app.error('请选择餐桌');
					return false;
				}
				that.loading = true;
				app.post("ApiAdminRestaurantShopOrder/add", {info: info}, function (res) {
					that.loading = false;
					if(res.status == 0) {
						app.alert(res.msg);return;
					}
					app.alert(res.msg,function(){
						that.getdata();
					});
			  });
			},
			clean: function() {
				var that = this;
				var tableId = that.opt.id;
				that.loading = true;
				app.post("ApiAdminRestaurantTable/clean", {tableId: tableId}, function (res) {
					that.loading = false;
					if(res.status == 0) {
						app.alert(res.msg);return;
					}
					app.alert(res.msg,function(){
						that.getdata();
					});
				});
			},
			cleanOver: function() {
				var that = this;
				var tableId = that.opt.id;
				that.loading = true;
				app.post("ApiAdminRestaurantTable/cleanOver", {tableId: tableId}, function (res) {
					that.loading = false;
					if(res.status == 0) {
						app.alert(res.msg);return;
					}
					app.alert(res.msg,function(){
						that.getdata();
					});
				});
			},
			close:function(){
				var that = this;
				var tableId = that.opt.id;
				app.confirm('确定要关闭订单吗？关闭后会自动切换餐桌状态为空闲，请及时通知厨房取消相关菜品', function () {
					that.loading = true;
				  app.post('ApiAdminRestaurantTable/closeOrder', {tableId: tableId}, function (res) {
						that.loading = false;
						if(res.status == 0) {
							app.alert(res.msg);return;
						}
				    if (res.status == 1) {
				      app.success(res.msg);
				      that.getdata();
				    }
				  });
				});
			},	
			numChange: function (e) {
			  this.nindex = e.detail.value;
			},
			startpause:function(e){
				var that = this;
				var status = e.currentTarget.dataset.status;
				var tableId = that.opt.id;
				that.loading = true;
				app.post('ApiAdminRestaurantTable/timingPause', {tableid: tableId,type:status}, function (res) {
					that.loading = false;
					if(res.status == 0) {
						app.alert(res.msg);return;
					}
					if (res.status == 1) {
						app.success(res.msg);
						that.getdata();
					}
				});
			},
			toWaiterPay(){
				var that = this;
				that.$refs.searchmember.close();
				that.search_member = [];
				app.goto('tableWaiterPay?id='+that.detail.id);
			},
			toSettle(){
				var that = this;
				var detail = that.detail;
				var url = 'tableWaiterPay?id='+detail.id;
				var  order = that.order;
				//order.eattype ==1 ,1先吃后付 0先付后吃，发现手机管理下单时使用了管理员的mid，这里就先不判断 eattype了
				if(order.mid ==0){//
					//弹窗 输入会员手机号或者会员ID 进行确定是谁付款
					that.$refs.searchmember.open();
				}else{
					app.goto(url);
				}
			},
			searchMember(){
				//查找会会员
				var that = this;
				if(that.member_keyword==''){
					app.error('请输入手机号或会员编号');
					return;
				}
				that.loading = true;
				app.post('ApiAdminRestaurantShopOrder/searchMember', {keyword:that.member_keyword}, function (res) {
					that.loading = false;
					if(res.status == 0) {
						that.search_member = [];
						app.alert(res.msg);
						return;
					}
					if (res.status == 1) {
						that.search_member = res.data;
					}
				});
			},
			updateorder(){
				//查找会会员
				var that = this;
				if(!that.search_member || !that.search_member.id){
					app.alert('请确定下单会员');return;
				}
				that.$refs.searchmember.close();
				that.loading = true;
				app.post('ApiAdminRestaurantShopOrder/updateOrder', {mid:that.search_member.id,orderid:that.order.id}, function (res) {
					that.loading = false;
					if(res.status == 0) {
						app.alert(res.msg);return;
					}
					if (res.status == 1) {
						that.search_member = [];
						var url = 'tableWaiterPay?id='+that.detail.id;
						app.goto(url);	
					}
				});
			}
		}
	}
	
</script>

<style>
	.btn-text { color: #007AFF;}
	.mb {margin-bottom: 10rpx;}
	.text-center {text-align: center;}
	.container {padding-bottom: 20rpx;}
	.head-bg {width: 100%;height: 320rpx; background: linear-gradient(-90deg, #FFCF34 0%, #FFD75F 100%); color: #333;}
	.head-bg h1 { line-height: 100rpx; font-size: 42rpx;}
	.head-bg .title { align-items: center; width: 94%; margin: 0 auto;}
	.head-bg .image{ width:80rpx;height:80rpx; margin: 0 10rpx;}
	
	.card-wrap { background-color: #FFFFFF; border-radius: 10rpx;padding: 30rpx; margin: 30rpx auto 0; width: 94%;}
	.card-view{ margin-top: -140rpx; }
	.card-wrap .card-title {font-size: 34rpx; color: #333; font-weight: bold;}

	
.info-item{ display:flex;align-items:center;width: 100%; background: #fff; /* border-bottom: 1px #f3f3f3 solid; */line-height:70rpx}
.info-item:last-child{border:none}
.info-item .t1{ width: 200rpx;color: #8B8B8B;line-height:30rpx; height: auto;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:2;overflow:hidden;}
form .info-item .t1 {color: #333; font-size: 30rpx;}
.info-item .t2{ color:#444444;text-align:right;flex:1;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:1;overflow:hidden; padding-right: 10rpx;}
.info-item .t3{ }
.info-item .ggtext{width: 90%;padding-left: 10%;text-align: left;}
.card-goods .t1 {width: 60%;}
.card-goods .t2 {width: 8%; padding-right: 2%;}
.card-goods .t3 {width: 20%;}
.info-textarea { height: auto; line-height: 40rpx;}
.info-textarea textarea {height: 80rpx;}
.info-textarea .t2{display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp: unset;overflow: scroll;}

.btn-view { display: flex;justify-content: space-between; margin: 30rpx 0;}
.btn-view button{ width: 90%; border-radius: 10rpx;background: linear-gradient(-90deg, #F7D156 0%, #F9D873 100%); color: #333; font-weight: bold;}
.btn-view .btn-default {background: #FFFFFF;}

.content{ display:flex;width:100%;padding:0 0 10rpx 0;align-items:center;font-size:24rpx}
.content .item{padding:10rpx 0;flex:1;display:flex;flex-direction:column;align-items:center;position:relative}
.content .item .image{ width:80rpx;height:80rpx}
.content .item .iconfont{font-size:60rpx}
.content .item .t3{ padding-top:3px}
.content .item .t2{display:flex;align-items:center;justify-content:center;background: red;color: #fff;border-radius:50%;padding: 0 10rpx;position: absolute;top: 0px;right:20rpx;width:35rpx;height:35rpx;text-align:center;}

.card-wrap .img{width:80rpx;height:80rpx;margin: 0 auto;display: block;}
/* 弹窗样式 */
.uni-popup-dialog {width: 300px;border-radius: 5px;background-color: #fff;}
.uni-popup-dialog {width: 300px;border-radius: 5px;background-color: #fff;}
.uni-dialog-title {/* #ifndef APP-NVUE */display: flex;	/* #endif */flex-direction: row;justify-content: center;padding-top: 15px;padding-bottom: 5px;}
.uni-dialog-title-text {font-size: 16px;font-weight: 500;}
.uni-dialog-content {display: flex;flex-direction: column;justify-content: center;padding: 5px 15px 15px 15px;}
.uni-dialog-content-options{display: flex;align-items: center;justify-content: flex-start;padding: 10rpx 0rpx;}
.uni-dialog-content-text {font-size: 14px;color: #6e6e6e;text-align: right;width: 228rpx;white-space: nowrap;}
.uni-dialog-button-group {/* #ifndef APP-NVUE */display: flex;/* #endif */flex-direction: row;border-top-color: #f5f5f5;border-top-style: solid;border-top-width: 1px;}
.uni-dialog-button {/* #ifndef APP-NVUE */display: flex;/* #endif */flex: 1;flex-direction: row;justify-content: center;align-items: center;height: 45px;	/* #ifdef H5 */	cursor: pointer;/* #endif */}
.uni-border-left {border-left-color: #f0f0f0;border-left-style: solid;border-left-width: 1px;}
.uni-dialog-button-text {font-size: 14px;}
.uni-button-color {color: #007aff;}
.uni-input{
	border: 1px #eee solid;height:70rpx;border-radius:4px;flex:1;font-size:28rpx;padding:0 10rpx
}
.uni-popup-dialog .btn-mini {width: 100rpx;height: 70rpx;line-height: 70rpx;background: linear-gradient(-90deg, #F7D156 0%, #F9D873 100%);border-radius: 8rpx; color: #000000;margin-left: 10rpx;}
.sc-item{margin:20rpx 20rpx;height: 80rpx;}
.sc-item .head-img{width: 100rpx;height: 100rpx;border-radius: 50%;overflow: hidden;}
.sc-item .head-img image{width: 100%;height: 100%}
</style>
