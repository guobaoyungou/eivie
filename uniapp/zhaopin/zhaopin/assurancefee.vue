<template>
<view class="container" v-if="isload">
	<view class="title"><image src="../../static/img/reg-code.png"></image>保证金缴纳</view>
	<form @submit="formSubmit">
		<view class="box top">
			<view class="form-item">
				<view class="form-label">认证信息</view>
				<view class="form-value">
					{{apply.company}}
				</view>
			</view>
			<view class="form-item">
				<view class="form-label">保证金</view>
				<view class="form-value flex-s">
						<!-- <view class="minus"><image class="img" src="/static/img/cart-minus.png" @tap="gwcminus" /></view> -->
						<input class="input" type="number" :value="totalprice" @input="feechange"></input>
						<view class="hui">元</view>
						<!-- <view class="plus"><image class="img" src="/static/img/cart-plus.png" @tap="gwcplus" /></view> -->
				</view>
			</view>
		</view>
		<view class="box tips">* 每{{set.assurance_per_fee}}元保证金可担保招聘一位签约技师</view>
		<view class="option flex-sb">
			<view class="sum">合计：<text :style="{color:t('color1')}">￥{{totalprice}}</text></view>
			<view><button class="btn"  form-type="submit" :style="{background:'linear-gradient(-90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)',color:'#ffffff'}">确 定</button></view>
		</view>
	</form>
	
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
			pre_url: app.globalData.pre_url,
			menuindex:-1,
			set:{},
			detail:{},
			formorder:{},
			feetype:1,
			duration:7,
			isbasearea:false,
			items:[],
			area:[],
			totalprice:0,
			city_num:1,
			unit_price:0,
			assurance:0,
			apply:{},
			tourl:''
    }
  },
  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		if(this.opt.tourl) this.tourl = decodeURIComponent(this.opt.tourl);
		this.getdata();
  },
	onPullDownRefresh: function () {
		this.getdata();
	},
  methods: {
		getdata:function(){
			var that = this;
			that.loading = true;
			app.get('ApiZhaopin/assuranceFee', {}, function (res) {
				that.loading = false;
				if(res.status==1){
					that.set = res.set
					that.apply = res.apply
					that.totalprice = res.set.assurance_fee
					// that.unit_price = res.set.top_fee
					// that.calculate()
					// //地区加载
					// uni.request({
					// 	url: app.globalData.pre_url+'/static/area.json',
					// 	data: {},
					// 	method: 'GET',
					// 	header: { 'content-type': 'application/json' },
					// 	success: function(res2) {
					// 		that.items = res2.data
					// 	}
					// });
					that.loaded()
				}else{
					app.alert(res.msg)
				}
			});
		},
		feechange:function(e){
			this.totalprice = e.detail.value
		},
		formSubmit: function (e) {
			var that = this;
		  var formdata = e.detail;
			
		  if (that.totalprice < that.set.assurance_fee){
		    app.alert('保证金最少缴纳'+that.set.assurance_fee+'元');
		    return;
		  }
			app.showLoading('提交中');
		  app.post("ApiZhaopin/assuranceFee", {apply_id:that.apply.id,totalprice:that.totalprice}, function (data) {
				app.showLoading(false);
		    if (data.status == 1) {
					if(that.tourl){
						var tourl = encodeURIComponent(that.tourl);
					}else{
						var tourl = encodeURIComponent('/zhaopin/zhaopin/assuranceorder')
					}
					app.goto('/pages/pay/pay?id=' + data.payorderid + '&tourl='+tourl);
		    } else {
		      app.error(data.msg);
		    }
		  });
		}
  }
}
</script>
<style>
@import "../common.css";
.title{background: #FFFFFF;padding: 30rpx; font-size: 36rpx;font-weight:bold;}
.title image{width: 46rpx;height: 46rpx;vertical-align: text-bottom;margin-right: 10rpx;}
.box{background:#FFFFFF;padding: 0 30rpx;margin-bottom: 20rpx;line-height: 50rpx;}
.box.tips{padding: 30rpx;color:#a4a4a4;}
.hui{color: #9a9a9a;}
.thumb{background: #FFFFFF;margin-bottom: 20rpx;width: 100%;max-height: 400rpx;overflow: hidden;display: flex;align-items: center;justify-content: center;}
.thumb image{max-height: 100%;}
.form-item{display: flex;justify-content: flex-start;align-items: center;border-bottom: 1rpx solid #f6f6f6; padding: 20rpx 0;}
.form-label{flex-shrink: 0;width: 180rpx;flex-wrap: wrap;font-weight: bold;/* text-align: justify;text-align-last: justify; */padding-right: 30rpx;}
.form-value{flex: 1;}
.form-value.radio label{display: block;}
.form-tips{color: #CCCCCC;font-size: 28rpx;padding: 20rpx 0;}
/* 行排列 */
.form-item-row{border-bottom: 1rpx solid #f6f6f6; padding: 20rpx 0;}
.form-item-row .form-label,.form-item-row .form-value{width: 100%;}
.form-value.textarea{padding:10rpx 4rpx;border-radius: 8rpx;color: #999;}
.form-value .down{width: 32rpx;height: 32rpx;vertical-align:middle;flex-shrink: 0;}
.tagitem{background: #f4f7fe;text-align: center;padding: 0 6rpx;margin-right: 8rpx;white-space: normal;font-size: 24rpx;color: #999;}
.title-box{padding: 30rpx;}
.title-box .title{font-size: 32rpx;font-weight: bold;}
.title-box .salary,.number{color: #FF3A69;}

.box .form-item:last-child{border: none;}
.option{position: fixed;bottom: 0;width: 100%;padding: 30rpx;background: #FFFFFF;}
.option .sum{font-weight: bold;font-size: 32rpx;}
.option .btn{width: 180rpx;text-align: center;background:#031028;color: #FFFFFF;display: block;line-height: 60rpx ;}

.addnum {font-size: 30rpx;color: #666;width:auto;display:flex;align-items:center;justify-content: flex-end;}
.addnum .plus {width:48rpx;height:36rpx;background:#F6F8F7;display:flex;align-items:center;justify-content:center}
.addnum .minus {width:48rpx;height:36rpx;background:#F6F8F7;display:flex;align-items:center;justify-content:center}
.addnum .img{width:24rpx;height:24rpx}
.addnum .i {padding: 0 20rpx;color:#2B2B2B;font-weight:bold;font-size:24rpx}
.addnum .input{flex:1;width:70rpx;border:0;text-align:center;color:#2B2B2B;font-size:24rpx}



/* modal */
	.popup__content{padding:10rpx 20rpx 50rpx 20rpx;}
	.popup__modal{border-radius: 0;max-height: 640rpx;min-height: 640rpx;}
	.popup__title{background: #f6f6f6;padding: 20rpx;}
	.popup__title .popup__close{width: 24rpx;height: 24rpx;}
	.popup__content .choose-main{padding-top: 20rpx;}
	.popup__content .choose-title{width: 100%;font-weight: bold;font-size: 32rpx;padding-left: 10rpx;}
	.popup__content .choose-box{display: flex;justify-content: flex-start;flex-wrap: wrap;align-items: center;}
	.popup__content .choose-box .choose-item{width: 49%;flex-shrink: 0;overflow: hidden;background: #F6F6F6;text-align: center;padding:16rpx;margin-bottom: 16rpx;}
	.popup__content .choose-box .choose-item.on{color: #FE924A;background:#fe924a30;}

	.popup_area .choose-box .choose-item{width: 165rpx;line-height: 36rpx;flex-wrap: nowrap;white-space: nowrap;overflow: hidden;text-overflow: ellipsis;overflow: hidden;margin-top: 20rpx;margin-bottom: 0;margin-left: 10rpx;}
	

</style>