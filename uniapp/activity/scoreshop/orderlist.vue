<template>
<view class="container">
	<block v-if="isload">
		<dd-tab :itemdata="itemdata" :itemst="itemst" :st="st" :isfixed="true" @changetab="changetab"></dd-tab>
		<view style="width:100%;height:100rpx"></view>
		<!-- #ifndef H5 || APP-PLUS -->
		<view class="topsearch flex-y-center">
			<view class="f1 flex-y-center">
				<image class="img" :src="pre_url+'/static/img/search_ico.png'"></image>
				<input :value="keyword" placeholder="输入关键字搜索" placeholder-style="font-size:24rpx;color:#C2C2C2" @confirm="searchConfirm"></input>
			</view>
		</view>
		<!--  #endif -->
		<view class="order-content">
			<block v-for="(item, index) in datalist" :key="index">
				<view class="order-box" @tap="goto" :data-url="'orderdetail?id=' + item.id">
					<view class="head">
						<view>订单号：{{item.ordernum}}</view>
						<view class="flex1"></view>
						<text v-if="item.status==0" class="st0">待付款</text>
						<text v-if="item.status==1 && item.freight_type!=1" class="st1">待发货</text>
						<text v-if="item.status==1 && item.freight_type==1" class="st1">待提货</text>
						<view v-if="item.status==2" class="st2"> <text v-if="item.type && item.type==1">等待发放</text><text v-else> 待收货</text></view>
						<text v-if="item.status==3" class="st3">已完成</text>
						<text v-if="item.status==4" class="st4">已关闭</text>
					</view>

					<block v-for="(item2, idx) in item.prolist" :key="idx">
						<view class="content" :style="idx+1==item.procount?'border-bottom:none':''">
							<view @tap.stop="goto" :data-url="'product?id=' + item2.proid">
								<image :src="item2.pic"></image>
							</view>
							<view class="detail">
								<text class="t1">{{item2.name}}</text>
								<text class="t2" v-if="item2.ggname" style="color:#666">规格：{{item2.ggname}}</text>
								<text class="t2" v-else>市场价￥{{item2.sell_price}}</text>
								<view class="t3"><text class="x1 flex1"><text v-if="item2.money_price>0">￥{{item2.money_price}}+</text>{{item2.score_price}}{{t('积分')}}</text><text class="x2">×{{item2.num}}</text></view>
							</view>
						</view>
					</block>
					<view class="bottom">
						<text>共计{{item.procount}}件商品 实付:￥{{item.totalprice}}</text>
						<text v-if="item.refund_status==1" style="color:red"> 退款中￥{{item.refund_money}}</text>
						<text v-if="item.refund_status==2" style="color:red"> 已退款￥{{item.refund_money}}</text>
						<text v-if="item.refund_status==3" style="color:red"> 退款申请已驳回</text>
					</view>
					<view class="op">
						<view @tap.stop="goto" :data-url="'orderdetail?id=' + item.id" class="btn2">详情</view>
						<block v-if="item.status==0">
							<view class="btn2" @tap.stop="toclose" :data-id="item.id">关闭订单</view>
							<view class="btn1" :style="{background:t('color1')}" @tap.stop="goto" :data-url="'/pagesExt/pay/pay?id=' + item.payorderid">去付款</view>
						</block>
						<block v-if="item.status==1 && show_refund == 1">
							<block v-if="item.paytypeid!='4'">
								<view v-if="item.refund_status==0 || item.refund_status==3" class="btn2" @tap.stop="goto" :data-url="'refund?orderid=' + item.id + '&price=' + item.totalprice + '&score=' + item.totalscore">申请退款</view>
							</block>
							<block v-else>
								<!-- <view class="btn2">{{codtxt}}</view> -->
							</block>
						</block>
						<block v-if="item.status==2">
							<block v-if="item.paytypeid!='4' && show_refund == 1">
								<view v-if="item.refund_status==0 || item.refund_status==3" class="btn2" @tap.stop="goto" :data-url="'refund?orderid=' + item.id + '&price=' + item.totalprice + '&score=' + item.totalscore">申请退款</view>
							</block>
							<block v-else>
							<!-- <view class="btn2">{{codtxt}}</view> -->
							</block>
							<view class="btn2" @tap.stop="logistics" :data-index="index" v-if="item.freight_type!=3 && item.freight_type!=4">查看物流</view>
							<view class="btn-wrapper" v-if="item.paytypeid!='4' && !item.type">
							<view class="btn1" :style="{background:t('color1')}" @tap.stop="orderCollect" :data-id="item.id" >确认收货</view>
								<view class="tips-container" :style="{background: 'rgba(' + (collect_reward_set.bgcolor || t('color1rgb')) + ', 0.7)',color: collect_reward_set.fontcolor || '#fff'}" v-if="collect_reward_set && collect_reward_set.prompt && item.is_collect_reward">
									<text class="tips-text">{{collect_reward_set.prompt}}</text>
									<view class="close-btn" @tap.stop="closeTips(index)">×</view>
									<view class="arrow-up" :style="{borderBottom:'12rpx solid rgba('+(collect_reward_set.bgcolor || t('color1rgb'))+', 0.7)'}"></view>
								</view>
							</view>
						</block>
						<block v-if="item.status==3 || item.status==4">
							<view class="btn2" @tap.stop="todel" :data-id="item.id">删除订单</view>
						</block>
					</view>
				</view>
			</block>
		</view>
		<nomore v-if="nomore"></nomore>
		<nodata v-if="nodata"></nodata>
    
    <uni-popup id="dialogSelectExpress" ref="dialogSelectExpress" type="dialog">
    	<view style="background:#fff;padding:20rpx 30rpx;border-radius:10rpx;width:600rpx" v-if="express_content">
    		<view class="sendexpress" v-for="(item, index) in express_content" :key="index" style="border-bottom: 1px solid #f5f5f5;padding:20rpx 0;">
    			<view class="sendexpress-item" @tap="goto" :data-url="'/pagesExt/order/logistics?express_com=' + item.express_com + '&express_no=' + item.express_no+ '&ordertype=' + item.ordertype+ '&orderid=' + item.orderid" style="display: flex;">
    				<view class="flex1" style="color:#121212">{{item.express_com}} - {{item.express_no}}</view>
    				<image :src="pre_url+'/static/img/arrowright.png'" style="width:30rpx;height:30rpx"/>
    			</view>
    			<view v-if="item.express_oglist" style="margin-top:20rpx">
    				<view class="oginfo-item" v-for="(item2, index2) in item.express_oglist" :key="index2" style="display: flex;align-items:center;margin-bottom:10rpx">
    					<image :src="item2.pic" style="width:50rpx;height:50rpx;margin-right:10rpx;flex-shrink:0"/>
    					<view class="flex1" style="color:#555">{{item2.name}}({{item2.ggname}})</view>
    				</view>
    			</view>
    		</view>
    	</view>
    </uni-popup>
		<uni-popup ref="popupReward" type='center'>
			<view class="popup-reward-content">
				<view class="popup-reward-price flex">
					<view class="price-num-text popup-color-text">
						<text>恭喜你获得</text>
						<text>{{collect_reward}}</text>
					</view>
					<view class="popup-reward-confirm" @click="popupRewardClose">确认</view>
				</view>
				<image :src="`${pre_url}/static/img/ordercollect/rewardbg.png`" mode="widthFix" />
			</view>
			<!-- 关闭弹窗按钮  -->
			<view class="popupMiandan-close" @click="popupRewardClose">
				<image :src="pre_url+'/static/img/close2.png'"></image>
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
			
      st: 'all',
      datalist: [],
      pagenum: 1,
      nodata: false,
      nomore: false,
      codtxt: "",
			keyword:'',
			show_refund:1, //显示退款入口
			itemdata:['全部','待付款','待发货','待收货','已完成','退款'],
			itemst:['all','0','1','2','3','10'],
			pre_url:app.globalData.pre_url,
      
      express_content:'',
			collect_reward_set:[],
			collect_reward:''
    };
  },

  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		if(this.opt && this.opt.st){
			this.st = this.opt.st;
		}
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
	onNavigationBarSearchInputConfirmed:function(e){
		this.searchConfirm({detail:{value:e.text}});
	},
  methods: {
    getdata: function (loadmore) {
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
      app.post('ApiScoreshop/orderlist', {st: st,pagenum: pagenum,keyword:that.keyword}, function (res) {
				that.loading = false;
        var data = res.datalist;
				//是否显示退款入口
				that.show_refund = res.show_refund;
				that.collect_reward_set = res.collect_reward_set;
				if(that.show_refund == 0){
					that.itemdata = ['全部','待付款','待发货','待收货','已完成'],
					that.itemst = ['all','0','1','2','3']
				}
        if (pagenum == 1) {
					that.datalist = data;
          if (data.length == 0) {
            that.nodata = true;
          }
					uni.setNavigationBarTitle({
						title: that.t('积分')+'兑换订单'
					});
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
    toclose: function (e) {
      var that = this;
      var orderid = e.currentTarget.dataset.id;
      app.confirm('确定要关闭该订单吗?', function () {
				app.showLoading('提交中');
        app.post('ApiScoreshop/closeOrder', {orderid: orderid}, function (data) {
					app.showLoading(false);
          app.success(data.msg);
          setTimeout(function () {
            that.getdata();
          }, 1000);
        });
      });
    },
    todel: function (e) {
      var that = this;
      var orderid = e.currentTarget.dataset.id;
      app.confirm('确定要删除该订单吗?', function () {
				app.showLoading('删除中');
        app.post('ApiScoreshop/delOrder', {orderid: orderid}, function (data) {
					app.showLoading(false);
          app.success(data.msg);
          setTimeout(function () {
            that.getdata();
          }, 1000);
        });
      });
    },
    orderCollect: function (e) {
      var that = this;
      var orderid = e.currentTarget.dataset.id;
      app.confirm('确定要收货吗?', function () {
        app.post('ApiScoreshop/orderCollect', {orderid: orderid}, function (data) {
					console.log(data.status == 1 && data.collect_reward);
					if(data.status == 1 && data.collect_reward){
						that.collect_reward = data.collect_reward;
						that.$refs.popupReward.open();
					}else{
						app.success(data.msg);
						setTimeout(function () {
							that.getdata();
						}, 1000);
					}
        });
      });
    },
		searchConfirm:function(e){
			this.keyword = e.detail.value;
      this.getdata(false);
		},
    logistics:function(e){
    	var index = e.currentTarget.dataset.index;
    	var orderinfo = this.datalist[index];
    	var express_com = orderinfo.express_com
    	var express_no = orderinfo.express_no
    	var express_content = orderinfo.express_content
    	var express_type = orderinfo.express_type|| '';
    	var prolist = orderinfo.prolist
    	if(!express_content){
    		app.goto('/pagesExt/order/logistics?express_com=' + express_com + '&express_no=' + express_no+'&type='+express_type);
    	}else{
    		express_content = JSON.parse(express_content);
    		for(var i in express_content){
    			if(express_content[i].express_ogids){
    				var express_ogids = (express_content[i].express_ogids).split(',');
    				var express_oglist = [];
    				for(var j in prolist){
    					if(app.inArray(prolist[j].id+'',express_ogids)){
    						express_oglist.push(prolist[j]);
    					}
    				}
    				express_content[i].express_oglist = express_oglist;
    			}
    		}
    		this.express_content = express_content;
    		this.$refs.dialogSelectExpress.open();
    	}
    },
		closeTips:function(index){
			this.$set(this.datalist[index], 'is_collect_reward', false);
		},
		popupRewardClose:function(){
			var that = this;
			that.$refs.popupReward.close();
			setTimeout(function () {
					that.getdata();
			}, 1000);
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

.order-box .content{display:flex;width: 100%; padding:16rpx 0px;border-bottom: 1px #f4f4f4 dashed;position:relative}
.order-box .content:last-child{ border-bottom: 0; }
.order-box .content image{ width: 140rpx; height: 140rpx;}
.order-box .content .detail{display:flex;flex-direction:column;margin-left:14rpx;flex:1}
.order-box .content .detail .t1{font-size:28rpx;line-height:36rpx;margin-bottom:10rpx;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:2;overflow:hidden;}
.order-box .content .detail .t2{height: 46rpx;line-height: 46rpx;color: #999;overflow: hidden;font-size: 26rpx;}
.order-box .content .detail .t3{display:flex;height:40rpx;line-height:40rpx;color: #ff4246;}
.order-box .content .detail .x1{ flex:1}
.order-box .content .detail .x2{ width:100rpx;font-size:32rpx;text-align:right;margin-right:8rpx}

.order-box .bottom{ width:100%; padding: 10rpx 0px; border-top: 1px #f4f4f4 solid; color: #555;}
.order-box .op{ display:flex;justify-content:flex-end;align-items:center;width:100%; padding: 10rpx 0px; border-top: 1px #f4f4f4 solid; color: #555;}

.btn1{margin-left:20rpx;width:160rpx;height:60rpx;line-height:60rpx;color:#fff;border-radius:3px;text-align:center}
.btn2{margin-left:20rpx;width:160rpx;height:60rpx;line-height:60rpx;color:#333;background:#fff;border:1px solid #cdcdcd;border-radius:3px;text-align:center}

/* 确认收获奖励tips */
.btn-wrapper{position:relative;display:inline-block}
.tips-container{position:absolute;top:100%;right:0;margin-top:20rpx;display:inline-block;background-color:#FF9900;color:white;padding:20rpx 40rpx 20rpx 20rpx;border-radius:10rpx;box-shadow:0 2px 6px rgba(0,0,0,0.15);max-width:500rpx;min-width:120rpx;width:max-content;width:-moz-max-content;width:-webkit-max-content;box-sizing:border-box;z-index:1}
.tips-text{display:block;white-space:normal;word-wrap:break-word;word-break:break-word;line-height:1.5;font-size:26rpx;overflow-wrap:break-word;padding-right:20rpx}
.close-btn{position:absolute;top:50%;right:10rpx;transform:translateY(-50%);font-weight:bold;cursor:pointer;font-size:32rpx;line-height:1;width:30rpx;height:30rpx;display:flex;align-items:center;justify-content:center}
.arrow-up{position:absolute;top:-12rpx;right:40rpx;width:0;height:0;border-left:12rpx solid transparent;border-right:12rpx solid transparent;border-bottom:12rpx solid #FF9900}
/* 确认收货奖励弹窗 */
.popup-reward-content{position: relative;display: flex;justify-content: center;}
.popupMiandan-close{border: 2px #fff solid;width: 60rpx;height: 60rpx;border-radius: 50%;display: flex;align-items: center;justify-content: center;position: absolute;top: 0;right: 0;}
.popupMiandan-close image{width: 80%;height: 80%;}
.popup-reward-content .popup-reward-price{width: 135px;position: absolute;top:30%;align-items: center;justify-content: center;z-index: 2;flex-direction: column;}
.popup-reward-content .popup-reward-price .price-num-text{width: 135px;height: 80px;font-size: 32rpx;text-align: center;color: #850b01;padding:20rpx 0;}
.popup-reward-content .popup-reward-price .price-num-text text{display: block;width: 100%;padding: 5rpx;}
.popup-reward-confirm{color: #FFFFFF;padding: 10rpx 60rpx;border: 1px solid #fff;border-radius: 10rpx;margin-top: 30rpx;background: rgba(255,255,255, 0.2);}
</style>