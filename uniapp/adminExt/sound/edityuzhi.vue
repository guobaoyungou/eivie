<template>
<view>
	<block v-if="isload">
		<form @submit="subform">
			<view class="form-box">
				<view class="form-item">
					<view class="f1">名称：</view>
					<view class="f2"><input type="text" name="name" :value="info.name" placeholder="请填写名称" placeholder-style="color:#888"></input></view>
				</view>
        <block v-if="showyuzhiinfo">
          <view class="form-item">
            <view class="f1">IotInstanceId：</view>
            <view class="f2"><input type="text" name="yuzhi_iotInstanceId" :value="info.yuzhi_iotInstanceId" placeholder="请填写IotInstanceId" placeholder-style="color:#888"></input></view>
          </view>
          <view class="form-item">
            <view class="f1">AccessKeyId：</view>
            <view class="f2"><input type="text" name="yuzhi_accessKeyId" :value="info.yuzhi_accessKeyId" placeholder="请填写AccessKeyId" placeholder-style="color:#888"></input></view>
          </view>
          <view class="form-item">
            <view class="f1">AccessKeySecret：</view>
            <view class="f2"><input type="text" name="yuzhi_accessSecret" :value="info.yuzhi_accessSecret" placeholder="请填写AccessKeySecret" placeholder-style="color:#888"></input></view>
          </view>	
          <view class="form-item">
            <view class="f1">ProductKey：</view>
            <view class="f2"><input type="text" name="yuzhi_productKey" :value="info.yuzhi_productKey" placeholder="请填写ProductKey" placeholder-style="color:#888"></input></view>
          </view>
          <!-- <view class="form-item">
            <view class="f1">DeviceName：</view>
            <view class="f2"><input type="text" name="yuzhi_deviceName" :value="info.yuzhi_deviceName" placeholder="请填写DeviceName" placeholder-style="color:#888"></input></view>
          </view> -->
          <view class="form-item">
            <view class="f1">RegionId：</view>
            <view class="f2"><input type="text" name="yuzhi_regionId" :value="info.yuzhi_regionId" placeholder="请填写RegionId" placeholder-style="color:#888"></input></view>
          </view>
          <view class="form-item">
            <view class="f1">3DES密钥：</view>
            <view class="f2"><input type="text" name="yuzhi_des3" :value="info.yuzhi_des3" placeholder="请填写3DES密钥" placeholder-style="color:#888"></input></view>
          </view>
        </block>

        <view class="form-item">
        	<view class="f1">设备号：</view>
        	<view class="f2"><input type="text" name="device_sn" :value="info.device_sn" placeholder="请填写设备号(DeviceName)" placeholder-style="color:#888"></input></view>
        </view>	

        <view class="form-item flex-col">
          <view class="f1">选择门店：</view>
          <view class="f2" style="line-height:50rpx;width: 100%;">
            <checkbox-group class="radio-group" name="mdid" @change="bindMendianChange">
              <label v-for="item in mendianArr" :key="item.id">
                <checkbox :value="''+item.id" :checked="inArray(item.id,info.mdids)?true:false"></checkbox> {{item.name}}
              </label>
            </checkbox-group>
          </view>
        </view>
        
        <view class="form-item flex-col">
          <view class="f1">播报订单：</view>
          <view class="f2" style="line-height:50rpx;width: 100%;">
            <checkbox-group class="radio-group" name="play_content" @change="bindMendianChange">
              <label><checkbox value="all" :checked="inArray('all',info.play_contents)?true:false"></checkbox> 全部</label>
              <label><checkbox value="maidan" :checked="inArray('maidan',info.play_contents)?true:false"></checkbox> 买单</label>
              <label><checkbox value="shop" :checked="inArray('shop',info.play_contents)?true:false"></checkbox> 商城</label>
              <label><checkbox value="collage" :checked="inArray('collage',info.play_contents)?true:false"></checkbox> 多人拼团</label>
            </checkbox-group>
          </view>
        </view>
        
        <view class="form-item" style="display: block;">
          <view style="display: flex;justify-content: space-between;">
            <view class="f1">买单付款播报：</view>
            <view class="f2"><input type="text" name="custom_content" :value="info.custom_content" placeholder="请填写买单付款播报" placeholder-style="color:#888"></input></view>
          </view>
          <view style="color: #999;line-height: 38rpx;margin-bottom: 20rpx;font-size:24rpx">[金额]、[实付金额]为动态数据，会自动替换；如播报多个动态数据中间使用“+”连接，如：收款[金额]+到账[实付金额]</view>
        </view>
        <view class="form-item" style="display: block;">
          <view style="display: flex;justify-content: space-between;">
            <view class="f1">商城订单播报：</view>
            <view class="f2"><input type="text" name="custom_content_shop" :value="info.custom_content_shop" placeholder="请填写商城订单播报" placeholder-style="color:#888"></input></view>
          </view>
          <view style="color: #999;line-height: 38rpx;margin-bottom: 20rpx;font-size:24rpx">[金额]、[实付金额]为动态数据，会自动替换；如播报多个动态数据中间使用“+”连接，如：收款[金额]+到账[实付金额]</view>
        </view>
        <view class="form-item" style="display: block;">
          <view style="display: flex;justify-content: space-between;">
            <view class="f1">拼团订单播报：</view>
            <view class="f2"><input type="text" name="custom_content_collage" :value="info.custom_content_collage" placeholder="请填写拼团订单播报" placeholder-style="color:#888"></input></view>
          </view>
          <view style="color: #999;line-height: 38rpx;margin-bottom: 20rpx;font-size:24rpx">[金额]、[实付金额]为动态数据，会自动替换；如播报多个动态数据中间使用“+”连接，如：收款[金额]+到账[实付金额]</view>
        </view>	
        <view class="form-item" style="display: block;">
          <view style="display: flex;justify-content: space-between;">
            <view class="f1">进入买单页播报：</view>
            <view class="f2"><input type="text" name="intomaidan_content" :value="info.intomaidan_content" placeholder="请填写进入买单页面播报" placeholder-style="color:#888"></input></view>
          </view>
          <view style="color: #999;line-height: 38rpx;margin-bottom: 20rpx;font-size:24rpx">自定义文字，如填写文字：点；不填写信息不播报</view>
        </view>
        <view class="form-item" style="display: block;">
          <view style="display: flex;justify-content: space-between;">
            <view class="f1">付款页取消支付播报：</view>
            <view class="f2"><input type="text" name="cancelpay_content" :value="info.cancelpay_content" placeholder="请填写付款页面取消支付播报" placeholder-style="color:#888"></input></view>
          </view>
          <view style="color: #999;line-height: 38rpx;margin-bottom: 20rpx;font-size:24rpx">自定义文字，如填写文字：顾客取消支付；不填写信息不播报</view>
        </view>
				<view class="form-item">
					<view>状态：</view>
					<view>
						<radio-group class="radio-group" name="status" @change="">
							<label><radio value="1" :checked="info.status==1?true:false"></radio> 显示</label> 
							<label><radio value="0" :checked="!info || info.status==0?true:false"></radio> 隐藏</label>
						</radio-group>
					</view>
				</view>
			</view>

			<button class="savebtn" :style="'background:linear-gradient(90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'" form-type="submit">提交</button>
			<button class="button text-btn" @tap="todel" v-if="info.id">删除</button>
			<view style="height:50rpx"></view>
		</form>
	</block>
	<loading v-if="loading"></loading>
</view>
</template>

<script>
var app = getApp();

export default {
  data() {
    return {
			isload:false,
			loading:false,
			pre_url:app.globalData.pre_url,
      info:{},
      mendianArr:{},
      showyuzhiinfo:false
		}
  },

  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.getdata();
  },
  methods: {
		getdata:function(){
			var that = this;
			var id = that.opt.id ? that.opt.id : '';
			that.loading = true;
			app.get('ApiAdminSound/edityuzhi',{id:id}, function (res) {
				that.loading = false;
        uni.setNavigationBarTitle({
        	title: '语智云喇叭'
        });
				that.info = res.info;
        that.showyuzhiinfo = res.showyuzhiinfo;
        that.mendianArr = res.mendianArr
				that.loaded();
			});
		},
    subform: function (e) {
      var that = this;
      var formdata = e.detail.value;
	  if(formdata.name.length == 0){
	  	app.alert('请填写名称');
	  	return;
	  }
	  if(that.subStatus){
	  	app.alert('请勿重复提交');
	  	return;
	  }
	  formdata.pid = that.cid;
	  that.subStatus = true
      var id = that.opt.id ? that.opt.id : '';
      app.post('ApiAdminSound/saveyuzhi', {id:id,info:formdata}, function (res) {
		  that.subStatus = false
        if (res.status == 0) {
          app.error(res.msg);
        } else {
          app.success(res.msg);
          setTimeout(function () {
            app.goback();
          }, 900);
        }
      });
    },
		todel: function (e) {
		  var that = this;
		  var id = that.opt.id ? that.opt.id : '';
		  app.confirm('确定要删除吗?', function () {
		    app.post('ApiAdminSound/del', {id: id}, function (res) {
		      if (res.status == 1) {
		        app.success(res.msg);
		        app.goback(true)
		      } else {
		        app.error(res.msg);
		      }
		    });
		  });
		},
		bindStatusChange:function(e){
			this.info.status = e.detail.value;
		},
  }
};
</script>
<style>
radio{transform: scale(0.6);}
checkbox{transform: scale(0.6);}
.form-box{ width:710rpx;margin:0 auto;padding:2rpx 24rpx 0 24rpx; background: #fff;border-radius: 10rpx;}
.form-item{ line-height: 80rpx; display: flex;justify-content: space-between;border-bottom:1px solid #eee }
.form-item .f1{color:#222;width:280rpx;flex-shrink:0}
.form-item .f2{display:flex;align-items:center;width:380rpx;}
.form-box .form-item:last-child{ border:none}
.form-box .flex-col{padding-bottom:20rpx}
.form-item input{ width: 100%; border: none;color:#111;font-size:28rpx; text-align: right}
.form-item textarea{ width:100%;min-height:200rpx;padding:20rpx 0;border: none;}
.form-item .upload_pic{ margin:50rpx 0;background: #F3F3F3;width:90rpx;height:90rpx; text-align: center  }
.form-item .upload_pic image{ width: 32rpx;height: 32rpx; }
.savebtn{ width: 90%; height:96rpx; line-height: 96rpx; text-align:center;border-radius:48rpx; color: #fff;font-weight:bold;margin: 0 5%; margin-top:60rpx; border: none; }

.clist-item{display:flex;border-bottom: 1px solid #f5f5f5;padding:20rpx 30rpx;}
.radio{flex-shrink:0;width: 32rpx;height: 32rpx;background: #FFFFFF;border: 2rpx solid #BFBFBF;border-radius: 50%;margin-right:30rpx}
.radio .radio-img{width:100%;height:100%;display:block}

.uploadbtn{position:relative;height:200rpx;width:200rpx}
</style>