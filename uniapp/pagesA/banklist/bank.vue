<template>
<view class="container">
	<block v-if="isload">
		<view v-for="(item, index) in datalist" :key="index" class="content" @tap.stop="setdefault" :data-id="item.id">
			<view class="f1">
				<text class="t1">{{item.bankname}}</text>
				<text class="t2">{{item.bankcarduser}}</text>
				<text class="t2">{{item.bankcardnum}}</text>
				<text class="flex1"></text>
				<image class="t3" :src="pre_url+'/static/img/edit.png'" @tap.stop="goto" :data-url="'bankadd?id=' + item.id">
			</view>
			<view class="f2">{{item.area}} {{item.address}}</view>
			<view class="f3">
				<view class="flex-y-center">
					<view class="radio" :style="item.isdefault ? 'border:0;background:'+t('color1') : ''"><image class="radio-img" :src="pre_url+'/static/img/checkd.png'"/></view>
					<view class="mrtxt">{{item.isdefault ? '默认银行卡' : '设为默认'}}</view>
				</view>
				<view class="flex1"></view>
				<view class="del" :style="{color:t('color1')}" @tap.stop="del" :data-id="item.id">删除</view>
			</view>
		</view>
		<nodata v-if="nodata"></nodata>
		<view style="height:140rpx"></view>
		<view class="btn-add" :class="menuindex>-1?'tabbarbot':'notabbarbot3'" :style="'background:linear-gradient(90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'" @tap="goto" :data-url="'/pagesB/address/addressadd?type=' + type"><image :src="pre_url+'/static/img/add.png'" style="width:28rpx;height:28rpx;margin-right:6rpx"/>添加银行卡</view>
	</block>
	<loading v-if="loading"></loading>
	<dp-tabbar :opt="opt" @getmenuindex="getmenuindex"></dp-tabbar>
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

      datalist: [],
      type: "",
			keyword:'',
			nodata:false,
			pre_url:app.globalData.pre_url,
    }
  },
  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.type = this.opt.type || '';
		this.getdata();
  },
	onPullDownRefresh: function () {
		this.getdata();
	},
  methods: {
		getdata:function(){
			var that = this;
			that.loading = true;
			that.nodata = false;
			app.get('ApiBank/banklist', {type: that.opt.type,keyword:that.keyword}, function (res) {
				that.loading = false;
				var datalist = res.data;
				if (datalist.length == 0 && that.keyword == ''){
					uni.redirectTo({
						url: '/pagesA/banklist/bankadd?type=' + (that.opt.type || 0)
					});
				}else if(datalist.length == 0){
					that.datalist = datalist;
					that.nodata = true;
				}else{
					that.datalist = datalist;
				}
				that.loaded();
			});
		},
    //选择收货地址
    setdefault: function (e) {
      var that = this;
      var fromPage = this.opt.fromPage;
      var bankId = e.currentTarget.dataset.id;
      app.post('ApiBank/setdefault', {bankid: bankId}, function (data) {
        if (fromPage) {
          app.goback(true);
        } else {
          that.getdata();
        }
      });
    },
    del: function (e) {
      var that = this;
      var addressId = e.currentTarget.dataset.id;
      console.log(addressId);
      app.confirm('确定要删除此地址吗?', function () {
        app.post("ApiBank/del", {addressid: addressId}, function (res) {
          app.success(res.msg);
          that.getdata();
        });
      });
    },
    searchChange: function (e) {
      this.keyword = e.detail.value;
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
.topsearch{width:94%;margin:16rpx 3%;}
.topsearch .f1{height:60rpx;border-radius:30rpx;border:0;background-color:#fff;flex:1}
.topsearch .f1 .img{width:24rpx;height:24rpx;margin-left:10px}
.topsearch .f1 input{height:100%;flex:1;padding:0 20rpx;font-size:28rpx;color:#333;}

.content{width:94%;margin:20rpx 3%;background:#fff;border-radius:5px;padding:20rpx 40rpx;}
.content .f1{height:96rpx;line-height:96rpx;display:flex;align-items:center}
.content .f1 .t1{color:#2B2B2B;font-weight:bold;font-size:30rpx}
.content .f1 .t2{color:#999999;font-size:28rpx;margin-left:10rpx}
.content .f1 .t3{width:28rpx;height:28rpx}
.content .f2{color:#2b2b2b;font-size:26rpx;line-height:42rpx;padding-bottom:20rpx;border-bottom:1px solid #F2F2F2}
.content .f3{height:96rpx;display:flex;align-items:center}
.content .radio{flex-shrink:0;width: 36rpx;height: 36rpx;background: #FFFFFF;border: 3rpx solid #BFBFBF;border-radius: 50%;}
.content .radio .radio-img{width:100%;height:100%}
.content .mrtxt{color:#2B2B2B;font-size:26rpx;margin-left:10rpx}
.content .del{font-size:24rpx}

.container .btn-add{width:90%;max-width:700px;margin:0 auto;height:96rpx;line-height:96rpx;text-align:center;color:#fff;font-size:30rpx;font-weight:bold;border-radius:40rpx;position: fixed;left:0px;right:0;bottom:0;margin-bottom:20rpx;}
.container .btn-add2{width:43%;max-width:700px;margin:0 auto;height:96rpx;line-height:96rpx;text-align:center;color:#fff;font-size:30rpx;font-weight:bold;border-radius:40rpx;position: fixed;left:5%;bottom:0;margin-bottom:20rpx;}
.container .btn-add3{width:43%;max-width:700px;margin:0 auto;height:96rpx;line-height:96rpx;text-align:center;color:#fff;font-size:30rpx;font-weight:bold;border-radius:40rpx;position: fixed;right:5%;bottom:0;margin-bottom:20rpx;}
</style>