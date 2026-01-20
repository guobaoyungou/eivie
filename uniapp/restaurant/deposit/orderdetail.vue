<template>
<view class="container">
	<block v-if="isload">
		<view class="topbg"></view>
		<view class="order-content">
			<block v-for="(item, index) in datalist" :key="index">
				<view class="order-box" @tap="goto" :data-url="'orderlog?id=' + item.id">
					<view class="pic">
						<image :src="item.pic" class="img"/>
					</view>
					<view class="detail">
						<text class="t1">{{item.name}}</text>
						<text class="t2">数量：{{item.num}}</text>
						<text class="t2">存入时间：{{dateFormat(item.createtime)}}</text>
					</view>
					<view v-if="item.status==0" class="takeout st0">审核中</view>
					<view v-if="item.status==1" class="takeout" @tap.stop="takeout" :data-orderid="item.id" :data-num="item.num"><image :src="pre_url+'/static/img/deposit_takeout.png'" class="img"/>取出</view>
					<view v-if="item.status==2" class="takeout st2" :data-orderid="item.id">已取走</view>
					<view v-if="item.status==3" class="takeout st3" :data-orderid="item.id">未通过</view>
					<view v-if="item.status==4" class="takeout st3">已过期</view>
				</view>
			</block>
		</view>
		<nomore v-if="nomore"></nomore>
		<nodata v-if="nodata"></nodata>

		
		<view class="bottom">
			<view class="btn2" v-if="sysset.is_use_table ==1"  @tap="allTakeOperate" data-orderid="0">一键取出</view>
			<view class="btn2" v-else  @tap="takeouts" data-orderid="0">一键取出</view>
			<view class="btn1" :style="{background:t('color1')}" @tap="goto" :data-url="'add?bid='+bid+'&isusetable='+sysset.is_use_table">我要寄存</view>
		</view>
	</block>
	<loading v-if="loading"></loading>
	<dp-tabbar :opt="opt"></dp-tabbar>
	
	<!-- 弹框 -->
	<view v-if="boxShow" class="" @touchmove.stop.prevent="disabledScroll">
		<view class="popup__overlay" @tap.stop="handleClickMask"></view>
		<view class="popup__modal">
			<view class="popup__title">
				<text class="popup__title-text">请输入取出数量</text>
				<image :src="pre_url+'/static/img/close.png'" class="popup__close" style="width:36rpx;height:36rpx"
					@tap.stop="handleClickMask" />
			</view>
			<view class="popup__content takeoutBox">
				<form @submit="formSubmit" @reset="formReset" report-submit="true">
					<view class="orderinfo">
						
						<view class="item">
							<text class="t1">取出数量</text>
							<input class="t2" type="text" placeholder="请输入要取出的数量" placeholder-style="font-size:28rpx;color:#BBBBBB" name="numbers" :value="num"></input>
						</view>
					</view>
					<button class="btn" form-type="submit" :style="{background:t('color1')}">确定</button>
					
				</form>
			</view>
		</view>
	</view>
	<!-- 新一键取出 -->
	<view v-if="allTakeShow" class="" >
		<view class="popup__overlay" @tap.stop="allTakeOperate"></view>
		<view class="popup__modal">
			<view class="popup__title">
				<text class="popup__title-text">一键取出</text>
				<image :src="pre_url+'/static/img/close.png'" class="popup__close" style="width:36rpx;height:36rpx"
					@tap.stop="handleClickMask" />
			</view>
			<view class="popup__content takeoutBox">
					<view class="orderinfo">
						<view class="item" >
							<text class="t1">选择桌台</text>
						</view>
						<view style="max-height: 40vh;overflow: hidden;overflow-y: auto;">
							<block v-for="(item, index) in tablelist" :key="item.id">
								<view class="clist-item" @tap="tableChange" :data-id="item.id" :data-name="item.name">
									<view class="flex1">{{item.name}}</view>
									<view class="radio" :style="item.id == tableid ? 'background:'+t('color1')+';border:0' : ''"><image class="radio-img" :src="pre_url+'/static/img/checkd.png'"/></view>
								</view>
							</block>
						</view>
						
					</view>
					<button class="btn" @tap="allTakeSubmit" :style="{background:t('color1')}">确定</button>
			
			</view>
		</view>
	</view>
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

      datalist: [],
      pagenum: 1,
	    boxShow:false,
      nomore: false,
			nodata:false,
			bid:0,
			orderid:0,
			num:1,
			sysset:{},
			allTakeShow:false,//一键取出弹窗
			tablelist:[],
			tableid:'0'
    };
  },

  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		if(this.opt && this.opt.bid){
			this.bid = this.opt.bid;
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
      app.post('ApiRestaurantDeposit/orderdetail', {st: st,pagenum: pagenum,bid:that.opt.bid}, function (res) {
				that.loading = false;
				that.sysset = res.sysset;
				that.tablelist = res.tablelist??[];
				console.log(that.tablelist);
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
		handleClickMask: function() {	
			this.boxShow = !this.boxShow;
		},
    takeout: function (e) {
       var that = this;
       this.orderid = e.currentTarget.dataset.orderid;
			 this.boxShow = true; //显示弹框
			 this.num = e.currentTarget.dataset.num;
    },
		disabledScroll: function (e) {
			return false;
		},
	
		takeouts: function (e) {
			var that = this;
			var orderid = e.currentTarget.dataset.orderid;
			app.confirm('确定要全部取出吗?', function () {
				app.post('ApiRestaurantDeposit/takeout', {bid:that.bid,orderid: orderid}, function (data) {
						if(data.status== 0){
							app.alert(data.msg);return;
						}
					app.success(data.msg);
					setTimeout(function () {
						that.getdata();
					}, 1000);
				});
			});
		},
	allTakeOperate(){
		this.allTakeShow = !this.allTakeShow;
	},
	allTakeSubmit(){
		var that = this;
		var formdata = e.detail.value;
	},
	tableChange:function(e){
		var that = this;
		var tableid = e.currentTarget.dataset.id;
		that.tableid = tableid;
	},
	allTakeSubmit(){
		var that = this;
		var orderid = 0;
		var tableid = that.tableid;
		that.loading = true;
		app.confirm('确定要全部取出吗?', function () {
			app.post('ApiRestaurantDeposit/takeout', {bid:that.bid,orderid: orderid,tableid:tableid}, function (data) {
				that.loading = false;
				that.allTakeShow = false;
				if(data.status== 0){
					app.alert(data.msg);return;
				}
				app.success(data.msg);
				setTimeout(function () {
					that.getdata();
				}, 1000);
			});
		});
	},
	changetablelistDialog(){
		this.tablelistshow = !this.tablelistshow;
	},
	formSubmit: function (e) {
		 var that = this;
		 var formdata = e.detail.value;
		 //alert(formdata.numbers);
		
		app.post('ApiRestaurantDeposit/takeout', {bid:that.bid,orderid:that.orderid,numbers:formdata.numbers}, function (data) {
			if(data.status== 0){
				app.alert(data.msg);return;
			}
		  app.success(data.msg);
		  setTimeout(function () {
			  that.boxShow = false; //隐藏弹框
				that.getdata();
		  }, 1000);
		});
	},
  }
};
</script>
<style>
.container{ width:100%;}
.topbg{width: 94%;margin:20rpx 3%;border-radius:8rpx;overflow:hidden}
.topbg .img{width:100%;height:auto}
.order-content{display:flex;flex-direction:column}
.order-box{ width: 94%;margin:0 3%;margin-bottom:20rpx;padding:10rpx 0 10rpx 20rpx; background: #fff;border-radius:8px;display:flex;position:relative}
.order-box .pic{ width: 120rpx; height: 120rpx;}
.order-box .pic .img{ width: 120rpx; height: 120rpx;}
.order-box .detail{display:flex;flex-direction:column;margin-left:20rpx;flex:1;margin-top:6rpx}
.order-box .detail .t1{font-size:28rpx;font-weight:bold;height:40rpx;line-height:40rpx;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:1;overflow:hidden;}
.order-box .detail .t2{height: 36rpx;line-height: 36rpx;color: #999;overflow: hidden;font-size: 22rpx;}
.order-box .detail .t3{display:flex;height:40rpx;line-height:40rpx;color: #ff4246;}
.order-box .takeout{display:flex;align-items:center;justify-content:center;padding:0 24rpx;height:52rpx;position:absolute;top:50%;margin-top:-26rpx;right:0;border-radius:26rpx 0 0 26rpx;background:#FFE8E1;color:#222222;font-size:24rpx;font-weight:bold}
.order-box .takeout .img{width:28rpx;height:28rpx;margin-right:6rpx}
.order-box .takeout.st0{color:#f55}
.order-box .takeout.st2{background:#F7F7F7;color:#BBBBBB}
.order-box .takeout.st3{background:#F7F7F7;color:#888}

.orderinfo{width:94%;margin:0 3%;border-radius:8rpx;margin-top:16rpx;padding: 14rpx 3%;background: #FFF;}
.orderinfo .item{display:flex;width:100%;padding:20rpx 0;border-bottom:0px dashed #ededed;overflow:hidden}
.orderinfo .item{ border-bottom: 0;}
.orderinfo .item .t1{width:200rpx;flex-shrink:0}
.orderinfo .item .t2{flex:1;text-align:right}
.orderinfo .item .red{color:red}

.bottom{ width: 100%; padding: 16rpx 20rpx;background: #fff; position: fixed; bottom: 0px;left: 0px;display:flex;justify-content:flex-between;align-items:center;
box-shadow: 0px 10px 15px 0px rgba(0, 0, 0, 0.06);}
.btn1{margin-left:20rpx;width:370rpx;height:88rpx;line-height:88rpx;color:#fff;border-radius:44rpx;text-align:center;font-weight:bold}
.btn2{margin-left:20rpx;width:280rpx;height:88rpx;line-height:88rpx;color:#333;background:#fff;border:1px solid #cdcdcd;font-weight:bold;border-radius:44rpx;text-align:center}

.takeoutBox .btn {border-radius:44rpx; margin: 0 auto; width: 96%; color: #FFF;}
.takeoutBox { padding-bottom: 30rpx;}

.popup__modal{ min-height: 0;position: fixed;} 
/* 选择桌台 */
.clist-item{display:flex;border-bottom: 1px solid #f5f5f5;padding:20rpx 30rpx;}
.radio{flex-shrink:0;width: 32rpx;height: 32rpx;background: #FFFFFF;border: 2rpx solid #BFBFBF;border-radius: 50%;margin-right:30rpx}
.radio .radio-img{width:100%;height:100%;display:block}
</style>