<template>
<view class="container">
	<block v-if="isload">
		<form @submit="formSubmit" @reset="formReset" report-submit="true">
			<view class="form-content">
				<view class="form-item">
					<text class="label">退款课程</text>
				</view>
				<view class="product">
					<view class="content">
						<view @tap="goto" :data-url="'/pagesExt/yueke/product?id=' + detail.proid">
							<image :src="detail.propic"></image>
						</view>
						<view class="detail">
							<text class="t1">{{detail.proname}}</text>
							<text class="t2">{{detail.ggname}}</text>
							<view class="t3"><text class="x1 flex1">￥{{detail.sell_price}}</text>
							<view class="num-wrap">
								<view class="addnum">
									<view class="minus" @tap="gwcminus" :data-num="refundNum"><image class="img" :src="pre_url+'/static/img/cart-minus.png'"/></view>
									<input class="input" type="number" :value="refundNum" @blur="gwcinput" :data-max="detail.canRefundNum" :data-num="refundNum"></input>
									<view class="plus" @tap="gwcplus" :data-max="detail.canRefundNum" :data-num="refundNum"><image class="img" :src="pre_url+'/static/img/cart-plus.png'"/></view>
								</view>
								<view class="text-desc">申请数量：最多可申请{{detail.canRefundNum}}节</view>
							</view>
							</view>
						</view>
					</view>
				</view>
			</view>
			<view class="form-content">
				<view class="form-item">
					<text class="label">退款原因</text>
					<view class="input-item"><textarea placeholder="请输入退款原因" placeholder-style="color:#999;" name="reason" @input="reasonInput"></textarea></view>
				</view>
				<view class="form-item">
					<text class="label">退款金额(元)</text>
					<view class="flex"><input name="money" @input="moneyInput" type="digit" :value="money" placeholder="请输入退款金额" placeholder-style="color:#999;"></input></view>
				</view>
			</view>
			<button class="btn" @tap="formSubmit" :style="{background:t('color1')}">确定</button>
			<view style="padding-top:30rpx"></view>
		</form>
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

			pre_url:'',
      orderid: '',
			ogid:0,
      totalprice: 0,
			order:{},
			detail: {},
			refundNum:[],
			money:'',
			reason:'',
			isloading:0,
			totalcanrefundnum:0
    };
  },

  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.orderid = this.opt.orderid;
		this.ogid = typeof this.opt.ogid == "undefined"?0:this.opt.ogid;
		this.pre_url = app.globalData.pre_url;
		this.type = this.opt.type;
		if(this.type == 'return') {
			uni.setNavigationBarTitle({
			  title: '申请退货退款'
			});
		}
		this.getdata();
  },
	onPullDownRefresh: function () {
		this.getdata();
	},
  methods: {
		getdata: function () {
			var that = this;
			that.loading = true;
			app.post('ApiYueke/refundinit', {id: that.orderid}, function (res) {
				that.loading = false;
				if(res.status == 0) {
					app.alert(res.msg,function(){
						app.goback();return;
					})
				}
				that.detail = res.detail;
				that.refundNum = res.detail.canRefundNum;
				that.totalprice = that.detail.returnTotalprice;
				that.money = (that.totalprice).toFixed(2);
				that.loaded();
			});
		},
		pickerChange: function (e) {
		  this.cindex = e.detail.value;
		},
    formSubmit: function () {
      var that = this;
			if(that.isloading) return;
      var orderid = that.orderid;
      var reason = that.reason;
      var money = parseFloat(that.money);
			var refundNum = that.refundNum;
			var content_pic = that.content_pic;
			var refundtotal = 0;
			
			if(refundNum <= 0) {
				app.alert('请选择要退款的课程数量');
				return;
			}
	
      if (reason == '') {
        app.alert('请填写退款原因');
        return;
      }

      if (money < 0 || money > parseFloat(that.totalprice)) {
        app.alert('退款金额有误');
        return;
      }
			that.isloading = 1;
			app.showLoading('提交中');
      var data = {
        orderid: orderid,
        reason: reason,
        money: money,
        refundNum:refundNum
      }
      app.post('ApiYueke/refund', data, function (res) {
				app.showLoading(false);
        app.alert(res.msg);
        if (res.status == 1) {
          that.subscribeMessage(function () {
            setTimeout(function () {
              app.goto('/pagesExt/yueke/orderdetail?id='+that.orderid);
            }, 1000);
          });
        }else{
					that.isloading = 0;
        }
      });
    },
		//加
		gwcplus: function (e) {
		  var maxnum = parseInt(e.currentTarget.dataset.max);
		  var num = parseInt(e.currentTarget.dataset.num);
		  if (num >= maxnum) {
		    return;
		  }
			var refundNum = this.refundNum;
			refundNum++;
			this.refundNum = refundNum
			this.calculate();
		},
		//减
		gwcminus: function (e) {
		  var maxnum = parseInt(e.currentTarget.dataset.max);
		  var num = parseInt(e.currentTarget.dataset.num);
		  if (num == 0) return;
			var refundNum = this.refundNum;
			refundNum--;
			this.refundNum = refundNum
			this.calculate();
		},
		//输入
		gwcinput: function (e) {
		  var maxnum = parseInt(e.currentTarget.dataset.max);
		  var num = parseInt(e.currentTarget.dataset.num);
		  var newnum = parseInt(e.detail.value);
		  console.log(num + '--' + newnum);
		  if (num == newnum) return;

		  if (newnum > maxnum) {
		    app.error('请输入正确数量');
				this.refundNum = maxnum;
		    return;
		  }
			this.refundNum = newnum
			this.calculate();
		},
    calculate: function () {
			var that = this;
			var total = 0;
			var refundTotalNum = that.refundNum;
			if(refundTotalNum == that.detail.canRefundNum) {
				total = that.detail.returnTotalprice;
			}else{
				total = (that.detail.totalprice / that.detail.total_kecheng_num) * refundTotalNum;
			}
			total = parseFloat(total);
			if(total > that.detail.returnTotalprice) total = that.detail.returnTotalprice;
			total = total.toFixed(2);
			that.totalprice = total;
			that.money = total;
		},
		moneyInput: function (e) {
			var newmoney = parseFloat(e.detail.value);
			if (newmoney <= 0 || newmoney > parseFloat(this.totalprice)) {
			  app.error('最大退款金额:'+this.totalprice);
			  return;
			}
			this.money = newmoney;
		},
		reasonInput: function (e) {
			this.reason = e.detail.value;
		},
		uploadimg:function(e){
			var that = this;
			var field= e.currentTarget.dataset.field
			var pics = that[field]
			if(!pics) pics = [];
			app.chooseImage(function(urls){
				for(var i=0;i<urls.length;i++){
					pics.push(urls[i]);
				}
			},1)
		},
		removeimg:function(e){
			var that = this;
			var index= e.currentTarget.dataset.index
			var field= e.currentTarget.dataset.field
			var pics = that[field]
			pics.splice(index,1)
		}
  }
};
</script>
<style>
	.num-wrap {position: absolute;right: 0;bottom:24rpx;}
	.num-wrap .text-desc { margin-bottom: -60rpx; color: #999; font-size: 24rpx; text-align: right;}
	.addnum {position: absolute;right: 0;bottom:0rpx;font-size: 30rpx;color: #666;width:auto;display:flex;align-items:center}
	.addnum .plus {width:48rpx;height:36rpx;background:#F6F8F7;display:flex;align-items:center;justify-content:center}
	.addnum .minus {width:48rpx;height:36rpx;background:#F6F8F7;display:flex;align-items:center;justify-content:center}
	.product .addnum .img{width:24rpx;height:24rpx}
	.addnum .i {padding: 0 20rpx;color:#2B2B2B;font-weight:bold;font-size:24rpx}
	.addnum .input{flex:1;width:70rpx;border:0;text-align:center;color:#2B2B2B;font-size:24rpx}

	.form-item4{width:100%;background: #fff; padding: 20rpx 20rpx;margin-top:1px}
	.form-item4 .label{ width:150rpx;}
	.layui-imgbox{margin-right:16rpx;margin-bottom:10rpx;font-size:24rpx;position: relative;}
	.layui-imgbox-img{display: block;width:200rpx;height:200rpx;padding:2px;border: #d3d3d3 1px solid;background-color: #f6f6f6;overflow:hidden}
	.layui-imgbox-img>image{max-width:100%;}
	.layui-imgbox-repeat{position: absolute;display: block;width:32rpx;height:32rpx;line-height:28rpx;right: 2px;bottom:2px;color:#999;font-size:30rpx;background:#fff}
	.uploadbtn{position:relative;height:200rpx;width:200rpx}

.product{width:94%;margin:0 3%;border-radius:8rpx;margin-top:16rpx;padding: 14rpx 3%;background: #FFF;}
.product .content{display:flex;position:relative;width: 100%; padding:16rpx 0px;border-bottom: 1px #e5e5e5 dashed; height: 196rpx;}
.product .content:last-child{ border-bottom: 0; }
.product .content image{ width: 140rpx; height: 140rpx;}
.product .content .detail{display:flex;flex-direction:column;margin-left:14rpx;flex:1}
.product .content .detail .t1{font-size:26rpx;line-height:36rpx;height:72rpx;margin-bottom:10rpx;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:2;overflow:hidden;}
.product .content .detail .t2{height: 46rpx;line-height: 46rpx;color: #999;overflow: hidden;font-size: 26rpx;}
.product .content .detail .t3{display:flex;height:40rpx;line-height:40rpx;color: #ff4246; position: relative;}
.product .content .detail .x1{ flex:1}
.product .content .detail .x2{ width:100rpx;font-size:32rpx;text-align:right;margin-right:8rpx}
.product .content .comment{position:absolute;top:64rpx;right:10rpx;border: 1px #ffc702 solid; border-radius:10rpx;background:#fff; color: #ffc702;  padding: 0 10rpx; height: 46rpx; line-height: 46rpx;}
.product .content .comment2{position:absolute;top:64rpx;right:10rpx;border: 1px #ffc7c2 solid; border-radius:10rpx;background:#fff; color: #ffc7c2;  padding: 0 10rpx; height: 46rpx; line-height: 46rpx;}

.form-content{width:94%;margin:16rpx 3%;border-radius:10rpx;display:flex;flex-direction:column;background:#fff;overflow:hidden}
.form-item{ width:100%;padding: 32rpx 20rpx;}
.form-item .label{ width:100%;height:60rpx;line-height:60rpx}
.form-item .input-item{ width:100%;}
.form-item textarea{ width:100%;height:200rpx;border: 1px #eee solid;padding: 20rpx;}
.form-item input{ width:100%;border: 1px #f5f5f5 solid;padding: 10rpx;height:80rpx}
.form-item .mid{ height:80rpx;line-height:80rpx;padding:0 20rpx;}
.btn{ height:100rpx;line-height: 100rpx;width:90%;margin:0 auto;border-radius:50rpx;margin-top:50rpx;color: #fff;font-size: 30rpx;font-weight:bold}
</style>
