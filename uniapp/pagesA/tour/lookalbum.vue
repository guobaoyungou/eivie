<template>
  <view>
    <block v-if="isload">
      <view style="width: 650rpx;margin: 0 auto;">
        <view class="form-item flex-col">
        	<view class="f2" style="flex-wrap:wrap">
        		<view v-for="(item, index) in pics" :key="index" class="layui-imgbox">
        			<view class="layui-imgbox-img"><image :src="item['album']" @tap="previewImage" :data-url="item['album']" mode="widthFix"></image></view>
        		</view>
        	</view>
        	<input type="text" hidden="true" name="pics" :value="pics?pics.join(','):''" maxlength="-1"></input>
        </view>
      </view>
      <nodata v-if="!pics"></nodata>
    </block>
    <dp-tabbar :opt="opt"></dp-tabbar>
  </view>
</template>

<script>
var app = getApp();
export default {
  data() {
    return {
        opt:{},
        levelid:0,
        loading:false,
        isload: false,
        menuindex:-1,
        tlid:0,
        pre_url:app.globalData.pre_url,

        orderid:0,
        pics:'',

    };
  },

  onLoad: function (opt) {
    var that = this;
    that.opt = app.getopts(opt);
    that.orderid    = that.opt.orderid?that.opt.orderid:0;
  },
  onShow:function(opt){
    var that = this;
    that.getdata();
  },
	onPullDownRefresh: function () {

	},
  onPullDownRefresh: function () {

  },
  methods: {
		getdata: function () {
			var that = this;
			that.loading = true;
      app.showLoading('加载中');
      app.post('ApiTour/lookalbum', {orderid:that.orderid}, function (res) {
        app.showLoading(false);
      	if (res.status == 1) {
          that.pics     = res.pics;
          that.loaded();
      	}else{
      		app.alert(res.msg);
      		return;
      	}
      });
		}
  }
};
</script>
<style>
page{width: 100%;}
.form-item{ line-height: 100rpx; display: flex;justify-content: space-between;margin-top:40rpx}
.form-item .f1{color:#222;width:200rpx;flex-shrink:0}
.form-item .f2{display:flex;align-items:center}
.form-item .upload_pic{ margin:50rpx 0;background: #F3F3F3;width:90rpx;height:90rpx; text-align: center  }
.form-item .upload_pic image{ width: 32rpx;height: 32rpx; }

.layui-imgbox{margin-right:16rpx;margin-bottom:10rpx;font-size:24rpx;position: relative;}
.layui-imgbox-img{display: block;width:200rpx;height:200rpx;padding:2px;border: #d3d3d3 1px solid;background-color: #f6f6f6;overflow:hidden}
.layui-imgbox-img>image{max-width:100%;}
.layui-imgbox-repeat{position: absolute;display: block;width:32rpx;height:32rpx;line-height:28rpx;right: 2px;bottom:2px;color:#999;font-size:30rpx;background:#fff}
.uploadbtn{position:relative;height:200rpx;width:200rpx;border: 2rpx dashed #ccc;}

.deal_css{width: 100rpx;height: 100rpx;border-radius: 50%;background-color: #fff;box-shadow: 2px 2px 8px #888888;line-height: 100rpx;text-align: center;}

</style>