<template>
<view class="container">
	<block v-if="isload">
		<view style="position: fixed;width: 100%;top: 0;z-index: 100;">
			<dd-tab :itemdata="['排队中','已完成']" :itemst="['0','1']" :st="st" :isfixed="false" @changetab="changetab"></dd-tab>
		</view>
		<view style="width:100%;height:100rpx"></view>
		<view class="topsearch flex-y-center" >
			<view class="f1 flex-y-center">
				<image class="img" :src="pre_url+'/static/img/search_ico.png'"></image>
				<input :value="keyword" placeholder="输入关键字搜索" placeholder-style="font-size:24rpx;color:#C2C2C2" @confirm="searchConfirm"></input>
			</view>
		</view>
		<view class="order-content">
			<block v-for="(item, index) in datalist" :key="index">
				<view class="order-box">	
					<view class="head">
						<view class="f1">订单号：{{item.ordernum}}</view>
						<view class="flex1"></view>
						<text  class="st0">{{item.statusLabel}}</text>
					</view>
					<view class="content" style="border-bottom:none">
						<view class="detail">
							<text class="t1">商户名称：{{item.bname}}</text>
						</view>
						<view class="detail">
							<text class="t1">{{t('会员')}}信息：{{item.nickname}}(ID:{{item.mid}})</text>
						</view>
						<view class="detail">
							<text class="t1">{{t('会员')}}手机号：{{item.tel}}</text>
						</view>
						<view class="detail">
							消费金额：<text class="t1" :style="'color:'+t('color1')">{{item.ordermoney}}</text>
						</view>
						<view class="detail">
							排队金额：<text class="t1" :style="'color:'+t('color1')">{{item.money}}</text>
						</view>
						<view class="detail">
							{{t('已返金额')}}：<text class="t1" :style="'color:'+t('color1')">{{item.money_give}}</text>
						</view>
						<view class="detail" v-if="item.queue_no">
							当前排名：<text class="t1" :style="'color:'+t('color1')">{{item.queue_noLabel}}</text>
						</view>
						<view class="detail">
							<text class="t1">排队时间：{{item.createtimeFormat}}</text>
						</view>
						<view class="bottom" >
							<view  v-if="item.status == 0 && set.edit_money_status == 1" class="btn2" @click="editmoney" :data-id="item.id" :style="'background:'+t('color2')+';border:0'">更改排队金额</view>
							<view  v-if="item.status == 0 && item.show_changeno" class="btn2" @click="changeno" :data-id="item.id"  :style="'background:'+t('color2')+';border:0'">更改排队号</view>
							<view  v-if="item.status == 0" class="btn" @click="queueQuit" :data-id="item.id"  :style="'background:linear-gradient(90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'">退出</view>
						</view>
					</view>
				</view>
			</block>
		</view>
		<nomore v-if="nomore"></nomore>
		<nodata v-if="nodata"></nodata>
		<uni-popup id="changenoDialog" ref="changenoDialog" type="dialog">
			<uni-popup-dialog mode="input" title="更改排队号" value="" placeholder="请输入排队号" @confirm="changenoConfirm"></uni-popup-dialog>
		</uni-popup>
		<uni-popup id="editmoneyDialog" ref="editmoneyDialog" type="dialog">
			<uni-popup-dialog mode="input" title="更改排队金额" value="" placeholder="请输入排队金额" @confirm="editmoneyConfirm"></uni-popup-dialog>
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
      st: '0',
      datalist: [],
      pagenum: 1,
      nomore: false,
      nodata: false,
			keyword:'',
			changeid:'',
			set:[],
			pre_url:app.globalData.pre_url
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
	onNavigationBarSearchInputConfirmed:function(e){
		this.searchConfirm({detail:{value:e.text}});
	},
  methods: {
    changetab: function (st) {
			this.st = st;
      uni.pageScrollTo({
        scrollTop: 0,
        duration: 0
      });
      this.getdata();
    },
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
      app.post('ApiAdminQueueFree/index', {status: st,pagenum: pagenum,keyword:that.keyword}, function (res) {
				that.loading = false;
				that.set = res.set;
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
	queueQuit(e){
		let that = this;
		let id = e.currentTarget.dataset.id;
		
		uni.showModal({
			title: '提示',
			content: '确认退出排队吗？',
			success: function (res) {
				if (res.confirm) {
					that.loading = true;
					app.post('ApiAdminQueueFree/quit_queue', {id: id}, function (res) {
						that.loading = false;
						app.alert(res.msg);
						if(res.status == 1){
							that.getdata();
							return;
						}
					});
				} else if (res.cancel) {
				}
			}
		});
	},
	changeno(e){
		let id = e.currentTarget.dataset.id;
		this.changeid = id;
		this.$refs.changenoDialog.open();
	},
	changenoConfirm: function (done,value) {
		this.$refs.changenoDialog.close();
		var that = this;
		that.loading = true;
		app.post('ApiAdminQueueFree/changeno', {id:that.changeid,no:value}, function (data) {
			that.loading = false;
			if (data.status == 0) {
			  app.error(data.msg);
			  return;
			}
			app.success(data.msg);
			setTimeout(function () {
			  that.getdata();
			}, 1000);
		});
	},
	editmoney(e){
		let id = e.currentTarget.dataset.id;
		this.changeid = id;
		this.$refs.editmoneyDialog.open();
	},
	editmoneyConfirm:function(done,value){
		this.$refs.editmoneyDialog.close();
		var that = this;
		that.loading = true;
		app.post('ApiAdminQueueFree/editmoney', {id:that.changeid,money:value}, function (data) {
			that.loading = false;
			if (data.status == 0) {
			  app.error(data.msg);
			  return;
			}
			app.success(data.msg);
			setTimeout(function () {
			  that.getdata();
			}, 1000);
		});
	},
	searchConfirm: function (e) {
	  var that = this;
	  var keyword = e.detail.value;
	  that.keyword = keyword;
	  that.getdata();
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
.order-box .content {line-height: 180%;}
.btn{margin-left:20rpx; max-width:160rpx;height:55rpx;line-height:55rpx;color:#fff;border-radius:3px;text-align:center;padding: 0 25rpx;}
.btn2{margin-left:20rpx;height:55rpx;line-height:55rpx;color:#fff;background:#fff;border-radius:3px;text-align:center;padding: 0 10rpx;}
.bottom{display: flex;justify-content: flex-end;padding-bottom: 10rpx;}
</style>