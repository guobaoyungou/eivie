<template>
<view class="container">
  <block v-if="isload">
    <form @submit="formSubmit" @reset="formReset" report-submit="true">
      
      <block v-if="ordergoods">
        <block v-for="(item, index) in ordergoods" :key="index">
          <view  class="form-content">
            <view style="color: #191919;font-weight: bold;line-height: 50rpx;width: 96%;margin: 0 2%;margin-top: 10rpx;">{{item.areaName}}</view>
            <view class="product">
              <block v-for="(item2, index2) in item.certs" :key="index2">
                <view class="content">
                  <view @tap="chooseit" :data-index="index" :data-index2="index2" :data-certid="item2.id" class="radio" :style="item2.issel ? 'border:0;background:'+t('color1') : ''">
                    <image class="radio-img" mode="widthFix" :src="pre_url+'/static/img/checkd.png'" style="width:100%;height:100%"/>
                  </view>
                  <view @tap="chooseit" :data-index="index" :data-index2="index2" :data-certid="item2.id" class="detail">
                    <text class="t1">{{item2.realName}} {{item2.certNo}}</text>
                    <!-- <text class="t2">{{item2.realName}} {{item2.certNo}}</text> -->
                  </view>
                </view>
              </block>
            </view>
          </view>
        </block>
      </block>
      
      <view class="form-item">
        <text class="label">退款原因</text>
        <view class="input-item"><textarea placeholder="请输入退款原因" placeholder-style="color:#999;padding: 10rpx;" name="reason" style="width: 100%;border: 2rpx solid #f1f1f1;border-radius: 4rpx;padding: 10rpx;"></textarea></view>
      </view>
      <view v-if="showpic" class="form-item flex-col">
        <view class="label">上传图片</view>
        <view id="pics" class="flex" style="flex-wrap:wrap;padding-top:20rpx">
          <view v-for="(item, index) in pics" :key="index" class="layui-imgbox">
            <view class="layui-imgbox-close" @tap="removeimg" :data-index="index" data-field="pics" ><image :src="pre_url+'/static/img/ico-del.png'"></image></view>
            <view class="layui-imgbox-img"><image :src="item" @tap="previewImage" :data-url="item" mode="widthFix"></image></view>
          </view>
          <view class="uploadbtn" :style="'background:url('+pre_url+'/static/img/shaitu_icon.png) no-repeat 60rpx;background-size:80rpx 80rpx;background-color:#F3F3F3;'" @tap="uploadimg" data-field="pics" v-if=" pics.length<3"></view>
        </view>
      </view>
      <button class="ref-btn" form-type="submit">确定</button>
    </form>
    <loading v-if="loading"></loading>
    <dp-tabbar :opt="opt"></dp-tabbar>
    <popmsg ref="popmsg"></popmsg>
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
      
      orderid: '',
      ordergoods:'',
      showpic:false,
      pics:[],
      
      selcertids:[]
    };
  },

  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.orderid = this.opt.orderid;
		this.totalprice = this.opt.price;
  },
  onShow: function () {
    var that = this;
    that.selcertids = [];
    that.getdata();
  },
	onPullDownRefresh: function () {
		this.getdata();
	},
  methods: {
		getdata: function () {
			var that = this;
			that.loading = true;
			app.post('ApiZhiyoubao/refundinit', {orderid:that.orderid}, function (res) {
				that.loading = false;
        if(res.status == 1){
          that.ordergoods = res.ordergoods;
          console.log(res.ordergoods)
          that.showpic = res.showpic;
          that.tmplids = res.tmplids;
          that.loaded();
        }else{
          app.alert(res.msg)
        }
			});
		},
    formSubmit: function (e) {
      var that = this;
      var orderid = that.orderid;
      var reason = e.detail.value.reason;
      if (reason == '') {
        app.alert('请填写退款原因');
        return;
      }
			app.showLoading('提交中');
      app.post('ApiZhiyoubao/refund', {orderid: orderid,reason: reason,pics:that.pics,certids:that.selcertids}, function (res) {
        app.showLoading(false);
        app.alert(res.msg);
        if (res.status == 1) {
          that.subscribeMessage(function () {
            setTimeout(function () {
              app.goback(true);
            }, 1000);
          });
        }
      });
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
    chooseit:function(e){
      var that = this;
      var index = e.currentTarget.dataset.index;
      var index2= e.currentTarget.dataset.index2;
      var certid= e.currentTarget.dataset.certid;
      var ordergoods = that.ordergoods;
      ordergoods[index]['certs'][index2]['issel'] = !ordergoods[index]['certs'][index2]['issel'];

      var selcertids = that.selcertids;
      var pos = selcertids.indexOf(certid);

      if(pos === 0|| pos>=1){
        selcertids.splice(pos,1);
      }else{
        selcertids.push(certid);
      }
      that.selcertids = selcertids;
    },
  }
};
</script>
<style>
.form-item{ width:100%;background: #fff; padding: 32rpx 20rpx;}
.form-item .label{ width:100%;height:60rpx;line-height:60rpx}
.form-item .input-item{ width:100%;}
.form-item input{ width:100%;border: 1px #eee solid;padding: 10rpx;height:80rpx; background-color: #EEEEEE;}
.form-item input{ width:100%;border: 1px #eee solid;padding: 10rpx;height:80rpx}
.form-item .mid{ height:80rpx;line-height:80rpx;padding:0 20rpx;}
.ref-btn{ width: 90%; margin: 0 5%; height: 40px; line-height: 40px; text-align: center; color: #fff; font-size: 16px; border-radius: 8px;border: none; background: #ff8758; }
.layui-imgbox{margin-right:16rpx;margin-bottom:10rpx;font-size:24rpx;position: relative;}
.layui-imgbox-img{display: block;width:200rpx;height:200rpx;padding:2px;border: #d3d3d3 1px solid;background-color: #f6f6f6;overflow:hidden}
.layui-imgbox-img>image{max-width:100%;}
.layui-imgbox-repeat{position: absolute;display: block;width:32rpx;height:32rpx;line-height:28rpx;right: 2px;bottom:2px;color:#999;font-size:30rpx;background:#fff}
.uploadbtn{position:relative;height:200rpx;width:200rpx}

.form-content{width:96%;margin:16rpx 2%;border-radius:10rpx;display:flex;flex-direction:column;background:#fff;overflow:hidden}
.product{width:96%;margin:0 2%;border-radius:8rpx;margin-top:16rpx;padding: 14rpx 3%;background: #FFF;}
.product .content{display:flex;position:relative;width: 100%; padding:16rpx 0px;border-bottom: 1px #e5e5e5 dashed;align-items: center;}
.product .content:last-child{ border-bottom: 0; }
.product .content image{ width: 140rpx; height: 140rpx;}
.product .content .detail{display:flex;flex-direction:column;margin-left:14rpx;flex:1}
.product .content .detail .t1{font-size:26rpx;line-height:36rpx;margin-bottom:10rpx;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:2;overflow:hidden;}
.product .content .detail .t2{height: 46rpx;line-height: 46rpx;color: #999;overflow: hidden;font-size: 26rpx;}
.product .content .detail .t3{display:flex;height:40rpx;line-height:40rpx;color: #ff4246; position: relative;}
.product .content .detail .x1{ flex:1}
.product .content .detail .x2{ width:100rpx;font-size:32rpx;text-align:right;margin-right:8rpx}
.product .content .comment{position:absolute;top:64rpx;right:10rpx;border: 1px #ffc702 solid; border-radius:10rpx;background:#fff; color: #ffc702;  padding: 0 10rpx; height: 46rpx; line-height: 46rpx;}
.product .content .comment2{position:absolute;top:64rpx;right:10rpx;border: 1px #ffc7c2 solid; border-radius:10rpx;background:#fff; color: #ffc7c2;  padding: 0 10rpx; height: 46rpx; line-height: 46rpx;}

.radio{flex-shrink:0;width: 32rpx;height: 32rpx;background: #FFFFFF;border: 2rpx solid #BFBFBF;border-radius: 50%;margin-right:30rpx;}
.radio .radio-img{width:100%;height:100%}
</style>