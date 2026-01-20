<template>
<view class="container">
	<block>
		<form @submit="formSubmit">
		<view  class="content1" >
			<view class="top flex" >
				<view class="f1">
						<image :src="pre_url+'/static/img/exp_ji.png'"></image>
				</view>
				<view class="f2" v-if="address.id"  @tap="goto" data-url="address?fromPage=mail&mailtype=1">
						<view class="t1">{{address.name}} {{address.tel}}</view>
						<view class="t2">{{address.area}} {{address.address}} </view>
				</view>
				<view class="f2" v-else @tap="goto" data-url="addressadd?fromPage=mail&mailtype=1">
						<view class="t1">寄件人信息</view>
						<view class="t2">点击填写寄件地址,自动智能填写 </view>
				</view>
				<view class="f3" @tap="goto" data-url="address?fromPage=mail&mailtype=1" >
					<view><image :src="pre_url+'/static/img/exp_txl.png'"></image></view>
					<view class="t3" >地址薄</view>
				</view>
			</view>
			<view class="top2 flex">
				<view class="f1">
						<image :src="pre_url+'/static/img/exp_shou.png'"></image>
				</view>
				<view class="f2" v-if="address2.id"  @tap="goto" data-url="address?fromPage=mail&mailtype=2">
						<view class="t1">{{address2.name}} {{address2.tel}}</view>
						<view class="t2">{{address2.area}} {{address2.address}} </view>
				</view>
				<view class="f2" v-else @tap="goto" data-url="addressadd?fromPage=mail&mailtype=2">
						<view class="t1">收件人信息</view>
						<view class="t2">复制完整信息，自动智能填写</view>
				</view>
				<view class="f3" @tap="goto" data-url="address?fromPage=mail&mailtype=2">
					<view><image :src="pre_url+'/static/img/exp_txl.png'"></image></view>
					<view class="t3" >地址薄</view>
				</view>
			</view>
		</view>
		
		
	<view class="form">
		<view class="form-item">
			<text class="label">物品信息</text>
			<input class="input" type="text" placeholder="请输入物品信息" placeholder-style="font-size:28rpx;color:#BBBBBB" name="cargo" ></input>
		</view>
		<view class="form-item">
			<text class="label">预估重量</text>
			<view class="addnum">
				<view class="minus"><image class="img" :src="pre_url+'/static/img/cart-minus.png'" @tap="minus" /></view>
				<input class="input" type="number" :value="this.weight"></input>KG &nbsp;
				<view class="plus"><image class="img" :src="pre_url+'/static/img/cart-plus.png'" @tap="plus" /></view>
			</view>
		</view>
		<view class="form-item">
			<text class="label">选择配送公司</text>
			<picker v-if="expressdata" @change="expresschange" :value="express_index" :range="expressdata" style="font-size:28rpx;padding:10rpx;height:70rpx;border-radius:4px;flex:1">
					<view class="picker" style="height:70rpx;"  @tap="changeyl">{{expressdata[express_index]?expressdata[express_index]:'请选择配送公司'}}</view>
			</picker>
			<view v-else class="picker" style="height:70rpx;" @tap="changeyl">{{expressdata[express_index]?expressdata[express_index]:'请选择配送公司'}}</view>
			<image :src="pre_url+'/static/img/arrowright.png'"  class="icon">
		</view>
		<view class="form-item">
			<text class="label">期望上门时间</text>
			<view class="input"  @tap="choosePstime"  :data-bid="0">
				{{pstimetext==''?'请选择上门时间':pstimetext}}
			</view>
			<image :src="pre_url+'/static/img/arrowright.png'" class="icon">
		</view>

		<view class="form-item">
			<text class="label">给快递员留言</text>
			<input class="input" type="text" placeholder="选填" placeholder-style="font-size:28rpx;color:#BBBBBB" name="remark" ></input>
		</view>

	</view>
		<view  v-if="showxieyi"  class="xieyibox">
			<view class="xieyibox-content">
				<view style="overflow:scroll;height:100%;">
					<parse @navigate="navigate"></parse>
				</view>
				<view style="position:absolute;z-index:9999;bottom:10px;left:0;right:0;margin:0 auto;text-align:center; width: 50%;height: 60rpx; line-height: 60rpx; color: #fff; border-radius: 8rpx;"   @tap="hidexieyi">已阅读并同意</view>
			</view>
		</view>
		<view class="bottom flex">
				<view class="left">
						<view>
							<text class="t1" v-if="price">￥{{price}}</text>
							<text class="t1" v-else>￥--</text>
							<text class="t2">预估运费</text>
						</view>
						<view class="t3">注：费用以实际寄件为准</view>
				</view>
				<view>
					<button class="tobuy" :style="{background:t('color1')}" form-type="submit" >下单</button>
				</view>
		</view>
		</form>
	</block>
	
	<loading v-if="loading"></loading>
	<dp-tabbar :opt="opt"></dp-tabbar>
	<popmsg ref="popmsg"></popmsg>
	
	<view v-if="pstimeDialogShow" class="popup__container">
		<view class="popup__overlay" @tap.stop="hidePstimeDialog"></view>
		<view class="popup__modal">
			<view class="popup__title">
				<text
					class="popup__title-text">请选择上门时间</text>
				<image :src="pre_url+'/static/img/close.png'" class="popup__close" style="width:36rpx;height:36rpx"
					@tap.stop="hidePstimeDialog" />
			</view>
			<view class="popup__content">
				<view class="pstime-item"
					v-for="(item, index) in pstimeArr"
					:key="index" @tap="pstimeRadioChange" :data-index="index">
					<view class="flex1">{{item.title}}</view>
					<view class="radio"
						:style="sm_time==item.value ? 'background:'+t('color1')+';border:0' : ''">
						<image class="radio-img" :src="pre_url+'/static/img/checkd.png'" />
					</view>
				</view>
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
			
			showxieyi:false,
			isagree:false,
			tel:'',
			address: [],
			expressdata:[],
			express_index:-1,
			pstimeDialogShow: false,
			pstimetext:'',
			sm_time:'',
			address2:[],
			weight:1,
			price:0
    };
  },
	onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.getdata();
	},
	methods: {
		getdata:function(){
			var that = this;
			app.get('ApiExpress/getAddress', {}, function(res) {
					that.loading = false;
					if (res.status == 0) {
						if (res.msg) {
							app.alert(res.msg)
						}
					}
					//that.expressdata = res.data.expressdata;
					that.address = res.data.address;
					that.address2 = res.data.address2;
					that.addressid =that.address.id
					that.address2id =that.address2.id
					that.pstimeArr = res.data.pstimeArr;
					that.loaded();
			});
		},
		regionchange(e) {
			const value = e.detail.value
			console.log(value[0].text + ',' + value[1].text + ',' + value[2].text);
			this.regiondata = value[0].text + ',' + value[1].text + ',' + value[2].text
		},
		expresschange:function(e){
			var that=this
			that.express_index = e.detail.value;

		  this.getprice()
		},
		changeyl:function(e){
			var that=this
			if(!that.address2id){
				app.alert('请填写寄件地址');return;
			}
			app.get('ApiExpress/getYunli', { addressid:that.address2id}, function(res) {
				if (res.status == 1) {
						that.expressdata = res.data
				} else {
						app.error(res.msg);return;
				}
			})
		
		},
		getprice:function(e){
			var that = this;
			var company = that.expressdata[that.express_index];
			var weight = that.weight;
			var addressid = that.addressid;
			var address2id = that.address2id;
			app.post('ApiExpress/getPrice', {addressid: addressid,address2id: address2id,company:company,weight:weight
			}, function (res) {
				if (res.status == 1) {
					that.price = res.data.combos[0].price
				} else {
					that.price ='';
				}
			});
		},
		choosePstime: function(e) {
			var that = this;
			var bid = e.currentTarget.dataset.bid;
			var pstimeArr = that.pstimeArr;
			var itemlist = [];
			for (var i = 0; i < pstimeArr.length; i++) {
				itemlist.push(pstimeArr[i].title);
			}
			if (itemlist.length == 0) {
				app.alert('当前没有可选时间段');
				return;
			}
			that.nowbid = bid;
			that.pstimeDialogShow = true;
			that.pstimeIndex = -1;
		},
		pstimeRadioChange: function(e) {
			var that = this;
			var pstimeIndex = e.currentTarget.dataset.index;
			var choosepstime = that.pstimeArr[pstimeIndex];
			that.pstimetext = choosepstime.title;
		  that.sm_time = choosepstime.value;
			that.pstimeDialogShow = false;
		},
		hidePstimeDialog: function() {
			this.pstimeDialogShow = false;
		},
	  formSubmit: function (e) {
	    var that = this;
	    var formdata = e.detail.value;
	    var addressId = that.opt.id || '';
	    var cargo = formdata.cargo;
	    var company = that.expressdata[that.express_index];
	    var sm_time = that.sm_time;
			var addressid = that.addressid;
			var address2id = that.address2id;
			if(!addressid){
				app.error('请添加寄件地址');
				return;
			}
			if(!address2id){
				app.error('请添加收件地址');
				return;
			}
	    if (company == '' ) {
	      app.error('请先择配送公司');
	      return;
	    }
			if (sm_time == '' ) {
			  app.error('请先择上门时间');
			  return;
			}
			app.showLoading('提交中');
	    app.post('ApiExpress/createOrder', {type: that.type,addressid: addressid,address2id: address2id,cargo:cargo,company:company,
			sm_time:sm_time,remark:formdata.remark,weight:that.weight}, function (res) {
				app.showLoading(false);
	      if (res.status == 0) {
	        app.alert(res.msg);
	        return;
	      }
	      app.success('寄件成功');
	      setTimeout(function () {
	       //app.goback(true);
	      }, 1000);
	    });
	  },
		//减
		minus: function (e) {
		 var num = this.weight;
		 if(num>1){
					this.weight = num-1; 
		 }
		  this.getprice()
		},
		//加
		plus: function (e) {
		  var num = this.weight;
			this.weight = num+1;
			this.getprice()
		}
	}
}
</script>
<style>
.content1{ background: #fff; margin: 30rpx; padding: 30rpx; border-radius: 12rpx;}
.content1 .top2{ margin-top: 20px;border-top:1px solid #eee; padding-top: 30rpx;}
.content1 .f1{ height: 130rpx;  display: flex; align-items: center; margin-right:20rpx}
.content1 .f1 image{ width: 48rpx; height: 48rpx;line-height: 100rpx;}
.content1 .f2{ border-right:1px solid  #eee; width:80%;}
.content1 .f2 .t1{ color: #222; font-size: 32rpx; font-weight: bold;}
.content1 .f2 .t2{ color: #909090; font-size: 24rpx; line-height: 40rpx; margin-top: 20rpx; }
.content1 .f3{ width:180rpx; text-align: center; margin-top: 30rpx; }
.content1 .f3 image{ width: 32rpx; height: 32rpx;  }
.content1 .f3 .t3{ text-align: center;}

.form{ width:94%;margin:20rpx 3%;border-radius:5px;padding: 0 3%;background: #FFF;}
.form .form-item{display:flex;width:100%;height:98rpx;justify-content: space-between;}
.form-item:last-child{border:0}
.form-item .label{ color:#222;font-weight:bold;height: 60rpx; line-height: 60rpx; text-align:left;width:200rpx;padding-right:20rpx}
.form-item .input{ flex:1;height: 60rpx; line-height: 60rpx;text-align:right}
.xieyibox{width:100%;height:100%;position:fixed;top:0;left:0;z-index:99;background:rgba(0,0,0,0.7)}
.xieyibox-content{width:90%;margin:0 auto;height:80%;margin-top:20%;background:#fff;color:#333;padding:5px 10px 50px 10px;position:relative;border-radius:2px}

.bottom{ position: fixed; bottom: 0; background: #fff; width: 100%; padding: 30rpx; justify-content: space-between;}
.bottom .left .t1{ color: #FD4A46; font-size: 40rpx; font-weight: bold; }
.bottom .left .t2{ color: #BBB; font-size: 24rpx; margin-left: 20rpx;}
.bottom .left .t3{ color: #BBB; font-size: 20rpx; }
.bottom .tobuy{width:240rpx ; line-height: 72rpx;color: #fff; border-radius:40rpx; 
background-color: #007AFF; border: none;font-size:28rpx;font-weight:bold; height: 80rpx; }

.form-item .icon{ width:32rpx; height: 32rpx; margin-top: 20rpx; }
.picker{display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:1;overflow:hidden;text-align: right; line-height: 50rpx;}

.pstime-item {display: flex;border-bottom: 1px solid #f5f5f5;padding: 20rpx 30rpx;}
.pstime-item .radio {flex-shrink: 0;width: 32rpx;height: 32rpx;background: #FFFFFF;border: 2rpx solid #BFBFBF;border-radius: 50%;margin-right: 30rpx}
.pstime-item .radio .radio-img {width: 100%;height: 100%}
.addnum {font-size: 30rpx;color: #666;width:auto;display:flex;}
.form-item  .addnum input{ width: 80rpx; text-align: center; margin-top: -10rpx;}
.addnum .plus {width:48rpx;height:36rpx;background:#F6F8F7;display:flex;align-items:center;justify-content:center}
.addnum .minus {width:48rpx;height:36rpx;background:#F6F8F7;display:flex;align-items:center;justify-content:center}
.addnum .img{width:24rpx;height:24rpx}
.addnum .i {padding: 0 20rpx;color:#2B2B2B;font-weight:bold;font-size:24rpx}
</style>