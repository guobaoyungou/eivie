<template>
<view class="container">
	<block v-if="isload">
		<view class="order-content">
			<checkbox-group class="radio-group" name="gettj" @change="wifiprintChexbox">
			<block v-for="(item, index) in datalist" :key="index">
				<view class="order-box" @tap.stop="gotodetail(item.id)">
					<view class="head">
						<view  class="f1" style="display: flex;align-items: center;">
							<view @tap.stop="changeradio" :data-index="index"  class="radio" :style="item.checked ? 'background:'+t('color1')+';border:0' : ''"><image class="radio-img" :src="pre_url+'/static/img/checkd.png'"/></view>
							
							<view class="f22">
								<view>订单号：{{item.ordernum}}</view>
								<view style="font-size: 24rpx;">提货人：{{item.linkman}}  手机号：{{item.tel}}  </view>
								<view class="t3" style="font-size: 24rpx;">{{item.createtime}}</view>
							</view>
						</view>
						<view class="f2">
							<text v-if="item.status==0" class="st0">待付款</text>
							<text v-if="item.status==1" class="st1">待发货</text>
							<text v-if="item.status==2" class="st2">待收货</text>
							<text v-if="item.status==3" class="st3">已完成</text>
							<text v-if="item.status==4" class="st4">已关闭</text>
							<text v-if="item.status==8" class="st8">待提货</text>
						</view>
					</view>

					<block v-for="(item2, idx) in item.prolist" :key="idx">
						<view class="content" :style="idx+1==item.procount?'border-bottom:none':''">
							<view @tap.stop="goto" :data-url="'/pages/shop/product?id=' + item2.proid">
								<image :src="item2.pic"></image>
							</view>
							<view class="detail">
								<text class="t1">{{item2.name}}</text>
								<text class="t2">{{item2.ggname}}</text>
								<text class="t2">共{{item2.num}}件 </text>
							</view>
						</view>
					</block>
	
				</view>
			</block>
			</checkbox-group>
		</view>
		
		<view style="height:auto;position:relative">
			<view style="width:100%;height:110rpx"></view>
			<view class="footer flex" :class="menuindex>-1?'tabbarbot':'notabbarbot'">
				<view @tap.stop="changeradioAll" class="radio" :style="allchecked ? 'background:'+t('color1')+';border:0' : ''"><image class="radio-img" :src="pre_url+'/static/img/checkd.png'"/></view>
				<view @tap.stop="changeradioAll" class="text0">全选</view>
				<view class="flex1"></view>
				<view class="text1">合计：</view>
				<view class="text2">{{selectedcount}}</view>
				<view class="op" :style="{background:'linear-gradient(-90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'}" @tap="confirm">确认提货</view>
			</view>
		</view>
		
		<nomore v-if="nomore"></nomore>
		<nodata v-if="nodata"></nodata>
	</block>
	<loading v-if="loading"></loading>
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
      codtxt: "",
			keyword:"",
			mid:'',
			allchecked:true,
			selectedcount:0,
			pre_url:app.globalData.pre_url,
			type:'',
			co:'',
			ismendian:0
    };
  },
  onLoad: function (opt) {
		this.opt = app.getopts(opt);

		if(this.opt && this.opt.st){
			this.st = this.opt.st;
		}
		if(this.opt && this.opt.mid){
			this.mid = this.opt.mid;
		}
		if(this.opt && this.opt.type){
			this.type = this.opt.type;
		}
		if(this.opt && this.opt.co){
			this.co = this.opt.co;
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
			var mid = that.mid
			var type = that.type
			var code = that.co
			that.nodata = false;
			that.nomore = false;
			that.loading = true;
      app.post('ApiMendianup/shoporder', {mid:mid,keyword:that.keyword,st: st,pagenum: pagenum,type:type,code:code}, function (res) {
				that.loading = false;
				that.wifiprintAuth = res.wifiprintAuth;
        var data = res.datalist;
				if(res.status==0){
					app.alert(res.msg,function(){
						app.goto('/pages/index/index');
					});
				}
				that.ismendian = res.ismendian;
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
				that.calculate();
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
		searchConfirm:function(e){
			this.keyword = e.detail.value;
			this.getdata(false);
		},
		confirm:function(e){
			var that=this
			var datalist = that.datalist;
			var ids = [];
			var ogdata = [];
			for(var i in datalist){
				if(datalist[i].checked){
					var thispro = datalist[i];
					ogdata.push(thispro.id);
				}
			}
			if (ogdata == undefined || ogdata.length == 0) {
			  app.error('请先选择订单');
			  return;
			}  
			var type = that.type
			var code = that.co
			that.loading = true;
			app.post('ApiMendianup/hexiao', {  orderids:ogdata,type:type,code:code }, function (res) {
					that.loading = false;
					if(res.status==1){
						app.success('核销成功')
						that.getdata()
					}else{
						app.error('核销失败')
					}
			})
		},
		changeradio: function (e) {
				var that = this;
				var index = e.currentTarget.dataset.index;
				var datalist = that.datalist;
				var checked = datalist[index].checked;
				if(checked){
						datalist[index].checked = false;
				}else{
						datalist[index].checked = true;
				}
				that.datalist = datalist;
				that.calculate();
		},
		changeradioAll:function(){
			var that = this;
			var datalist = that.datalist;
			var allchecked = that.allchecked
			for(var i in datalist){
				datalist[i].checked = allchecked ? false : true;
			}
			that.datalist = datalist;
			that.allchecked = allchecked ? false : true;
			that.calculate();
		},
		calculate: function () {
			var that = this;
			var datalist = that.datalist;
			var ids = [];
			var selectedcount = 0;
			for(var i in datalist){
					if(datalist[i].checked){
						selectedcount += 1;
					}
			}
			that.selectedcount = selectedcount;
		},
		gotodetail: function (id) {
			if(this.ismendian){
				app.goto("orderdetail?id=" + id)
			}
		}
	
  }
};
</script>
<style>
.container{ width:100%}
.order-box .head .radio{flex-shrink:0;width: 32rpx;height: 32rpx;background: #FFFFFF;border: 2rpx solid #BFBFBF;border-radius: 50%;}
.order-box .head .radio .radio-img{width:100%;height:100%}

.order-content{display:flex;flex-direction:column}
.order-box{ width: 94%;margin:10rpx 3%;padding:0rpx 3%; background: #fff;border-radius:8px}
.order-box .head{ display:flex;width:100%; border-bottom: 1px #f4f4f4 solid; overflow: hidden; color: #999; padding:20rpx 0;justify-content: space-between;align-items: center;}
.order-box .head .f1{color:#333;line-height: 36rpx;}
.order-box .head .f22{ margin-left: 20rpx;}

.order-box .head .t3{ color: #999;}
.order-box .head .st0{ width: 140rpx; color: #ff8758; text-align: right; }
.order-box .head .st1{ width: 140rpx; color: #ffc702; text-align: right; }
.order-box .head .st2{ width: 140rpx; color: #ff4246; text-align: right; }
.order-box .head .st3{ width: 140rpx; color: #999; text-align: right; }
.order-box .head .st4{ width: 140rpx; color: #bbb; text-align: right; }
.order-box .head .st8{ width: 140rpx; color: #ff55ff; text-align: right; }

.order-box .content{display:flex;width: 100%; padding:16rpx 0px;border-bottom: 1px #f4f4f4 dashed;position:relative;align-items: center;}
.order-box .content:last-child{ border-bottom: 0; }
.order-box .content image{ width: 140rpx; height: 140rpx;}
.order-box .content .detail{display:flex;flex-direction:column;margin-left:14rpx;flex:1}
.order-box .content .detail .t1{font-size:26rpx;line-height:36rpx;margin-bottom:10rpx;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:2;overflow:hidden;}
.order-box .content .detail .t2{height: 46rpx;line-height: 46rpx;color: #999;overflow: hidden;font-size: 26rpx;}
.order-box .content .detail .t3{display:flex;height:40rpx;line-height:40rpx;color: #ff4246;}
.order-box .content .detail .x1{ flex:1}
.order-box .content .detail .x2{ width:110rpx;font-size:32rpx;text-align:right;margin-right:8rpx}

.order-box .bottom{ width:100%; padding: 10rpx 0px; border-top: 1px #f4f4f4 solid; color: #555;}
.order-box .op{ display:flex;justify-content:flex-end;align-items:center;width:100%; padding: 10rpx 0px; border-top: 1px #f4f4f4 solid; color: #555;}

.btn1{margin-left:20rpx;width:160rpx;height:60rpx;line-height:60rpx;color:#fff;border-radius:3px;text-align:center}
.btn2{margin-left:20rpx;width:160rpx;height:60rpx;line-height:60rpx;color:#333;background:#fff;border:1px solid #cdcdcd;border-radius:3px;text-align:center}
.tgr{font-size: 24rpx;}
.pdl10{padding-left: 10rpx;}

.footer {width: 100%;background: #fff;margin-top: 5px;position: fixed;left: 0px;bottom: 0px;z-index:8;display:flex;align-items:center;padding:0 20rpx;border-top:1px solid #EFEFEF}
.footer .radio{flex-shrink:0;width: 32rpx;height: 32rpx;background: #FFFFFF;border: 2rpx solid #BFBFBF;border-radius: 50%;margin-right:10rpx}
.footer .radio .radio-img{width:100%;height:100%}
.footer .text0{color:#666666;font-size:24rpx;}
.footer .text1 {height: 110rpx;line-height: 110rpx;color:#444;font-weight:bold;font-size:24rpx;}
.footer .text2 {color: #F64D00;font-size: 36rpx;font-weight:bold}
.footer .text3 {color: #F64D00;font-size: 28rpx;font-weight:bold}
.footer .op{width: 180rpx;height: 70rpx;line-height:70rpx;border-radius: 50rpx;font-weight:bold;color:#fff;font-size:28rpx;text-align:center;margin-left:30rpx}
</style>