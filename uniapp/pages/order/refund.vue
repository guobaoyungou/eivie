<template>
<view class="container">
	<block v-if="isload">
		<form @submit="formSubmit" @reset="formReset" report-submit="true">
			<view class="form-content">
				<view class="form-item">
					<text class="label">退款商品</text>
				</view>
				<view class="product">
					<view v-for="(item, index) in prolist" :key="index" class="content">
						<view @tap="goto" :data-url="'/pages/shop/product?id=' + item.proid">
							<image :src="item.pic"></image>
						</view>
						<view class="detail">
							<text class="t1">{{item.name}}</text>
							<text class="t2">{{item.ggname}}</text>
							<view class="t3"><text class="x1 flex1">￥{{item.sell_price}}</text>
							<!-- <text class="x2">×{{item.num}}</text> -->
							<view class="num-wrap">
								<view class="addnum">
									<view class="minus" @tap="gwcminus" :data-index="index" :data-ogid="item.id" :data-num="refundNum[index].num"><image class="img" src="/static/img/cart-minus.png"/></view>
									<input class="input" type="number" :value="refundNum[index].num" @blur="gwcinput" :data-index="index" :data-ogid="item.id" :data-max="item.num-item.refund_num" :data-num="refundNum[index].num"></input>
									<view class="plus" @tap="gwcplus" :data-index="index" :data-ogid="item.id" :data-max="item.num-item.refund_num" :data-num="refundNum[index].num"><image class="img" src="/static/img/cart-plus.png"/></view>
								</view>
								<view class="text-desc">申请数量：最多可申请{{item.canRefundNum}}件</view>
							</view>
							</view>
						</view>
					</view>
				</view>
			</view>
			<view class="form-content">
				<view class="form-item" v-if="opt.type == 'refund'" style="display:none">
					<text class="label">货物状态</text>
					<view class="input-item">
						<picker @change="pickerChange" :value="cindex" :range="cateArr" name="receive" style="height:80rpx;line-height:80rpx;border-bottom:1px solid #EEEEEE">
							<view class="picker">{{cindex==-1? '请选择' : cateArr[cindex]}}</view>
						</picker>
					</view>
				</view>
				<view class="form-item">
					<text class="label">退款原因</text>
					<view class="input-item"><textarea placeholder="请输入退款原因" placeholder-style="color:#999;" name="reason" @input="reasonInput"></textarea></view>
				</view>
				<view class="form-item">
					<text class="label">退款金额(元)</text>
					<view class="flex"><input name="money" @input="moneyInput" type="digit" :value="money" placeholder="请输入退款金额" placeholder-style="color:#999;"></input></view>
				</view>
				<view class="form-item flex-col">
					<view class="label">上传图片(最多三张)</view>
					<view id="content_picpreview" class="flex" style="flex-wrap:wrap;padding-top:20rpx">
						<view v-for="(item, index) in content_pic" :key="index" class="layui-imgbox">
							<view class="layui-imgbox-close" @tap="removeimg" :data-index="index" data-field="content_pic"><image src="/static/img/ico-del.png"></image></view>
							<view class="layui-imgbox-img"><image :src="item" @tap="previewImage" :data-url="item" mode="widthFix"></image></view>
						</view>
						<view class="uploadbtn" :style="'background:url('+pre_url+'/static/img/shaitu_icon.png) no-repeat 60rpx;background-size:80rpx 80rpx;background-color:#F3F3F3;'" @tap="uploadimg" data-field="content_pic" v-if="content_pic.length<3"></view>
					</view>
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
      totalprice: 0,
			order:{},
			detail: {},
			refundNum:[],
			prolist: [],
			content_pic:[],
			cindex:-1,
			cateArr:['未收到货','已收到货'],
			type:'',
			money:'',
			reason:'',
			tmplids:[],
			isloading:0,
			totalcanrefundnum:0,
    };
  },

  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.orderid = this.opt.orderid;
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
			app.post('ApiOrder/refundinit', {id: that.orderid}, function (res) {
				that.loading = false;
				if(res.status == 0) {
					app.alert(res.msg,function(){
						app.goback();retrun;
					})
				}
				that.tmplids = res.tmplids;
				that.detail = res.detail;
				that.totalprice = that.detail.returnTotalprice;
				that.money = (that.totalprice).toFixed(2);
				var temp = [];
				that.prolist = res.prolist;
				that.order = res.order;
				var totalcanrefundnum = 0;
				for(var i in that.prolist) {
					temp.push({ogid:that.prolist[i].id,num:that.prolist[i].canRefundNum})
					totalcanrefundnum += that.prolist[i].canRefundNum;
				}
				that.totalcanrefundnum = totalcanrefundnum;
				console.log(temp)
				that.refundNum = temp;
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
			var receive = that.cindex;
      var money = parseFloat(that.money);
			var refundNum = that.refundNum;
			var content_pic = that.content_pic;
			var refundtotal = 0;
			for(var i in refundNum) {
				refundtotal += refundNum[i].num;
			}
			if(refundtotal <= 0) {
				app.alert('请选择要退款的商品');
				return;
			}
			//if (receive == -1 && that.opt.type == 'refund') {
        //app.alert('请选择货物状态');
        //return;
      //}
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
      app.post('ApiOrder/refund', {orderid: orderid,reason: reason,money: money,content_pic:content_pic,receive:receive,refundNum:refundNum,type:that.type}, function (res) {
				app.showLoading(false);
        app.alert(res.msg);
        if (res.status == 1) {
          that.subscribeMessage(function () {
            setTimeout(function () {
              app.goto('detail?id='+that.orderid);
            }, 1000);
          });
        }else{
					that.isloading = 0;
        }
      });
    },
		//加
		gwcplus: function (e) {
		  var index = parseInt(e.currentTarget.dataset.index);
		  var maxnum = parseInt(e.currentTarget.dataset.max);
		  var ogid = e.currentTarget.dataset.ogid;
		  var num = parseInt(e.currentTarget.dataset.num);
		  if (num >= maxnum) {
		    return;
		  }
			var refundNum = this.refundNum;
			refundNum[index].num++;
			this.refundNum = refundNum
			this.calculate();
		},
		//减
		gwcminus: function (e) {
		  var index = parseInt(e.currentTarget.dataset.index);
		  var maxnum = parseInt(e.currentTarget.dataset.max);
		  var ogid = e.currentTarget.dataset.ogid;
		  var num = parseInt(e.currentTarget.dataset.num);
		  if (num == 0) return;
			var refundNum = this.refundNum;
			refundNum[index].num--;
			this.refundNum = refundNum
			this.calculate();
		},
		//输入
		gwcinput: function (e) {
		  var index = parseInt(e.currentTarget.dataset.index);
		  var maxnum = parseInt(e.currentTarget.dataset.max);
		  var ogid = e.currentTarget.dataset.ogid;
		  var num = parseInt(e.currentTarget.dataset.num);
		  var newnum = parseInt(e.detail.value);
		  console.log(num + '--' + newnum);
		  if (num == newnum) return;
		
		  if (newnum > maxnum) {
		    app.error('请输入正确数量');
		    return;
		  }
			var refundNum = this.refundNum;
			refundNum[index].num = newnum;
			this.refundNum = refundNum
			this.calculate();
		},
    calculate: function () {
			var that = this;
			var total = 0;
			var refundTotalNum = 0;
			for(var i in that.refundNum) {
				if(that.refundNum[i].num == that.prolist[i].num)
					total += parseFloat(that.prolist[i].real_totalprice);
				else {
					total += that.refundNum[i].num * parseFloat(that.prolist[i].real_totalprice) / that.prolist[i].num;
				}
				refundTotalNum += that.refundNum[i].num;
			}
			if(refundTotalNum == that.detail.totalNum || refundTotalNum == that.detail.canRefundNum) {
				total = that.detail.returnTotalprice;
			}
			console.log(total)
			total = parseFloat(total);
			if(total > that.detail.returnTotalprice) total = that.detail.returnTotalprice;		
			total = total.toFixed(2);
			that.totalprice = total;	
			that.money = total;	
		},
		moneyInput: function (e) {
			var newmoney = parseInt(e.detail.value);
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
		},
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
	.layui-imgbox-close{position: absolute;display: block;width:32rpx;height:32rpx;right:-16rpx;top:-16rpx;color:#999;font-size:32rpx;background:#fff}
	.layui-imgbox-close image{width:100%;height:100%}
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