<template>
<view class="container">
	<block v-if="isload">
		<dd-tab :itemdata="['全部','审核中','已审核','已通过','已驳回']" :itemst="['all','1','2','3','4']" :st="st" :isfixed="true" @changetab="changetab"></dd-tab>
		<view style="width:100%;height:100rpx"></view>

		<view class="order-content">
			<block v-for="(item, index) in datalist" :key="index">
				<view class="order-box" @tap="goto" :data-url="'refundDetail?id=' + item.id">
					<view class="head">
						<block v-if="item.binfo">
							<view class="f1" v-if="item.bid!=0" @tap.stop="goto" :data-url="'/pagesExt/business/index?id=' + item.bid"><image :src="pre_url+'/static/img/ico-shop.png'"></image> {{item.binfo.name}}</view>
							<view class="f1" v-else><image :src="item.binfo.logo" class="logo-row"></image> {{item.binfo.name}}</view>
						</block>
						<view class="flex1"></view>
						<text v-if="item.refund_status==0" class="st3">已取消</text>
						<text v-if="item.refund_status==1" class="st0">审核中</text>
						<text v-if="item.refund_status==2" class="st4" style="color: green;">已审核</text>
						<text v-if="item.refund_status==3" class="st2">已通过</text>
            <text v-if="item.refund_status==4" class="st2">已驳回</text>
					</view>
          
          <block v-for="(item2, idx) in item.refundgoods" :key="idx">
          	<view class="content":style="idx+1==item.procount?'border-bottom:none':''">
                <view class="detail">
                  <text class="t1">{{item2.fname}}</text>
                  <text class="t2">{{item2.realName}} {{item2.certNo}}</text>
                </view>
          	</view>
          </block>
          <view v-if="item.refund_status==3" class="bottom">
          	<text v-if="item.refund_status==3" style="color:red"> 已退款￥{{item.refund_money}}</text>
          </view>

					<view class="op">
						<view @tap.stop="goto" :data-url="'refundDetail?id=' + item.id" class="btn2">详情</view>
						<block v-if="item.refund_status==1">
							<view class="btn2" @tap.stop="toclose" :data-id="item.id">取消</view>
						</block>
					</view>
				</view>
			</block>
		</view>
		<nomore v-if="nomore"></nomore>
		<nodata v-if="nodata"></nodata>
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
      nomore: false,
			nodata:false,
			keyword:'',
			pre_url:app.globalData.pre_url,
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
      app.post('ApiZhiyoubao/refundList', {st: st,pagenum: pagenum,orderid:that.opt.orderid,keyword:that.keyword}, function (res) {
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
      var id = e.currentTarget.dataset.id;
      app.confirm('确定要取消该退款吗?', function () {
				app.showLoading('提交中');
        app.post('ApiZhiyoubao/refundOrderClose', {id: id}, function (data) {
					app.showLoading(false);
          app.success(data.msg);
          setTimeout(function () {
            that.getdata();
          }, 1000);
        });
      });
    },
		searchConfirm:function(e){
			this.keyword = e.detail.value;
      this.getdata(false);
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
.order-box .head .f1{display:flex;align-items:center;color:#333;}
.order-box .head image{width:34rpx;height:34rpx;margin-right:8rpx;}
.order-box .head .st0{ width: 140rpx; color: #ff8758; text-align: right; }
.order-box .head .st1{ width: 140rpx; color: #ffc702; text-align: right; }
.order-box .head .st2{ width: 220rpx; color: #ff4246; text-align: right; }
.order-box .head .st3{ width: 140rpx; color: #999; text-align: right; }
.order-box .head .st4{ width: 140rpx; color: #bbb; text-align: right; }

.order-box .content{width: 100%; padding:16rpx 0px;border-bottom: 1px #f4f4f4 dashed;position:relative;margin-bottom: 10rpx;}
.order-box .content:last-child{ border-bottom: 0; }
.order-box .content image{ width: 140rpx; height: 140rpx;}
.order-box .content .detail{display:flex;flex-direction:column;margin-left:14rpx;flex:1}
.order-box .content .detail .t1{font-size:26rpx;line-height:36rpx;margin-bottom:10rpx;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:2;overflow:hidden;}
.order-box .content .detail .t2{height: 46rpx;line-height: 46rpx;color: #999;overflow: hidden;font-size: 26rpx;}
.order-box .content .detail .t3{display:flex;height:40rpx;line-height:40rpx;color: #ff4246;}
.order-box .content .detail .x1{ flex:1}
.order-box .content .detail .x2{ width:100rpx;font-size:32rpx;text-align:right;margin-right:8rpx}

.order-box .bottom{ width:100%; padding: 10rpx 0px; border-top: 1px #f4f4f4 solid; color: #555;}
.order-box .op{ display:flex;justify-content:flex-end;align-items:center;width:100%; padding: 10rpx 0px; border-top: 1px #f4f4f4 solid; color: #555;}

.btn1{margin-left:20rpx;width:160rpx;height:60rpx;line-height:60rpx;color:#fff;border-radius:3px;text-align:center;font-size: 22rpx;}
.btn2{margin-left:20rpx;width:160rpx;height:60rpx;line-height:60rpx;color:#333;background:#fff;border:1px solid #cdcdcd;border-radius:3px;text-align:center;}
</style>