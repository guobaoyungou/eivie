<template>
<view>
	<block v-if="isload">
		<view class="form-box">
			<view class="form-item1">
				<view class="title">订单编号：{{order.ordernum}}</view>
				<view class="product">
					<view class="goods" v-for="(og,index) in oglist" :key="index">
						<view class="img"><image :src="og.pic"></image></view>
						<view class="info flex1">
							<view class="f1">{{og.name}}</view>
							<view class="f2">{{og.ggname}}</view>
							<view class="f3">￥{{og.sell_price}}</view>
						</view>
					</view>
				</view>
			</view>
		</view>
		<form @submit="formSubmit">
			<view class="form-box">
				<view class="form-item">
					<view class="f1">姓名</view>
					<view class="f2"><input type="text" name="linkman" :value="order.linkman"></input></view>
				</view>
				<view class="form-item">
					<view class="f1">手机号</view>
					<view class="f2"><input type="text" name="tel" :value="order.tel"  ></input></view>
				</view>
				<view class="form-item">
						<view class="f1">所在地区</view>
						<view class="f2"><uni-data-picker class="picker" :localdata="items" :border="false" :placeholder="regiondata || '请选择省市区'" @change="regionchange" style="display: flex;justify-content: right;"></uni-data-picker></view>
				</view>
				<view class="form-item">
					<view class="f1">详细地址</view>
					<view class="f2"><input type="text" name="address" :value="order.address" ></input></view>
				</view>
				<view class="form-item">
					<view class="f1">身份证号</view>
					<view class="f2"><input type="text" name="cardno" :value="order.cardno"></input></view>
				</view>
				<view class="form-item flex-col" style="border-bottom:0">
					<view class="f1">身份证正面</view>
					<view class="f2">
						<view class="layui-imgbox">
							<view class="layui-imgbox-img" @tap="uploadimg" data-field="card">
								<image :src="card==''?pre_url+'/static/img/idcard.png':card" mode="widthFix" ></image>
							</view>
						</view>
					</view>
				</view>
			</view>	
			<view class="form-box">
				<view class="form-item flex-col" style="border-bottom:0">
					<view class="f1">身份证反面</view>
					<view class="f2">
						<view class="layui-imgbox">
							<view class="layui-imgbox-img" @tap="uploadimg" data-field="cardf">
								<image :src="cardf==''?pre_url+'/static/img/idcard_back.png':cardf" mode="widthFix" ></image>
							</view>
						</view>
					</view>
				</view>
			</view>
			<view>
				<button class="savebtn"  form-type="submit" :style="'background:linear-gradient(90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'" >确 定</button>
			</view>
		</form>
		<view style="height:50rpx"></view>
	</block>
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
			info:{},
			order:{},
			oglist:{},
			orderid:0,
			card:'',
			cardf:'',
			items:[],
			regiondata: ''
    };
  },

  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.orderid = this.opt.orderid || 0;
		this.getdata();
  },
	onPullDownRefresh: function () {
		this.getdata();
	},
  methods: {
		getdata: function () {
			var that = this;
			that.loading = true;
			app.get('ApiOrder/uploadCard', {orderid: that.orderid}, function (res) {
				that.loading = false;
				if(res.status==1){
					that.order = res.order;
					that.oglist = res.oglist;
					that.regiondata = res.order.area2;
					that.cardf = res.order.card_back;
					that.card = res.order.card;
					that.loaded();
					var url = app.globalData.pre_url+'/static/area.json';
					uni.request({
						url: url,
						data: {},
						method: 'GET',
						header: { 'content-type': 'application/json' },
						success: function(res2) {
							that.items = res2.data
						}
					});
				}else{
					app.alert(res.msg);
				}
			});
		},
		regionchange(e) {
			const value = e.detail.value
			this.regiondata = value[0].text + '/' + value[1].text + '/' + value[2].text;
		},
    formSubmit: function (e) {
      var that = this;
      var formdata = e.detail.value;
      if (formdata.cardno == '') {
        app.error('请填写身份证号码');
        return;
      }
			if (!/(^\d{15}$)|(^\d{18}$)|(^\d{17}(\d|X|x)$)/.test(formdata.cardno)) {
				app.alert('身份证号格式错误');return;
			}
			if(that.card=='' || that.cardf==''){
				app.error('请上传身份证正反面');
				return;
			}
			formdata['card'] = that.card;
			formdata['cardf'] = that.cardf;
			formdata['area'] = that.regiondata;
			formdata['orderid'] = that.orderid;
			app.showLoading('提交中');
      app.post('ApiOrder/uploadcard', formdata, function (res) {
				app.showLoading(false);
				if(res.status==1){
					app.success(res.msg);
					setTimeout(function () {
					  app.goback(true);
					}, 2000);
				}else{
					app.error(res.msg);
				}
      });
    },
		uploadimg:function(e){
			var that = this;
			var field= e.currentTarget.dataset.field
			app.chooseImage(function(urls){
				for(var i=0;i<urls.length;i++){
					that[field] = urls[i];
				}
			},1)
		}
  }
};
</script>
<style>
.form-box{ padding:2rpx 24rpx 0 24rpx; background: #fff;margin: 24rpx;border-radius: 10rpx}
.form-box .form-item:last-child{ border:none}
.form-box .flex-col{padding-bottom:20rpx}

.form-item1{ width:100%;background: #fff;}
.form-item1 .title{ width:100%;height:60rpx;line-height:60rpx;border-bottom: 1px solid #eee;font-size: 30rpx;}
.product{ width: 100%; background: #fff;}
.goods{display: flex;padding:16rpx 0;border-bottom: 1px solid #eee;}
.goods:last-child{border-bottom: none;}
.product .info{padding-left:20rpx;}
.product .info .f2{color: #a4a4a4; font-size:24rpx}
.product .info .f3{color: #ff0d51; font-size:28rpx}
.product image{ width:140rpx;height:140rpx}

.form-item{ line-height: 100rpx; display: flex;justify-content: space-between; }
.form-item .f1{color:#222;width:160rpx;flex-shrink:0}
.form-item .f2{display:flex;align-items:center;flex: 1;}
.form-item .f2 input,.form-item .f2 .picker{color:#111;font-size:28rpx; text-align: right;background:#f5f5f5;width: 100%;height: 80%;padding: 0 20rpx;}
.form-item .f2 .picker{color: #222222;text-align: left;}
.savebtn{ width: 90%; height:96rpx; line-height: 96rpx; text-align:center;border-radius:48rpx; color: #fff;font-weight:bold;margin: 0 5%; margin-top:30rpx; border: none; }
.edit-btn{border: none;    width: 28%;    background-color: red;    font-size: 24rpx;    color: #fff;}
</style>
