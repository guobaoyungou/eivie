<template>
  <view>
    <block v-if="isload">
      <view class="head" :style="'background: '+color1"></view>
      <view style="width: 700rpx;margin: 0 auto;background-color: #fff;;padding: 20rpx;margin-top: -60rpx;border-radius: 12rpx;">
        <view class="content_css" >
          <view class="left">
            价格倍率：
          </view>
          <view class="right">
            <input name='rate' placeholder="请输入" placeholder-style="line-height:80rpx;height:80rpx" style="line-height:80rpx;height:80rpx" v-model="rate"/>
          </view>
        </view>
				<view style="color: #666;line-height: 180%;">
					说明:
					<view>1.请输入大于0的倍数，倍数支持小数点后1位</view>
					<view>2.调整的商品价格倍数是在商品的价格基础上进行的。</view>
				</view>
        
      </view>
      <button class="btn" @tap="postdata" :style="'background: '+color1">提交</button>
      
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
        loading:false,
        isload: false,
        menuindex:-1,
        tlid:0,
        pre_url:app.globalData.pre_url,

        rate:'',
        color1:'',
    };
  },

  onLoad: function (opt) {
		uni.setNavigationBarTitle({
			title: '价格倍率'
		});
    var that = this;
		var opt = app.getopts(opt);

    that.opt = opt;
    that.color1 = app.t('color1');
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
      app.get('ApiAgent/priceRate', {}, function (res) {
      	if (res.status == 1) {
          var data = res;
          
          if(!that.color1 && res._initdata){
            that.color1 = res._initdata.color1;
          }
          if(data.price_rate){
            that.rate = data.price_rate;
          }

          that.loaded();
      	}else{
      		app.alert(res.msg);
      		return;
      	}
      });
		},
    postdata:function(e){
    	var that = this
      var rate = that.rate;
      if(!rate || rate < 0){
        app.alert('请输入正确的数值');
        return;
      }
			app.showLoading('提交中');
			app.post('ApiAgent/priceRate', {rate:that.rate}, function (res) {
				app.showLoading(false);
				if (res.status == 1) {
					app.success(res.msg);
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
page{background:#f1f1f1;width: 100%;height: 100%;}
.head{width: 100%;height: 160rpx;}
.content_css{overflow: hidden;line-height: 80rpx;border-bottom: 2rpx solid #E7E7E7;}
.left{width: 180rpx;float:left}
.right{width: 480rpx;float:right;line-height:80rpx;height:80rpx}
.scan{background-color:#fff;height: 54rpx;width: 54rpx;float: right;padding: 2rpx;overflow: hidden;margin-top: 10rpx;margin-right: 20rpx;}
.btn{width: 400rpx;color: #fff;line-height: 100rpx;height: 100rpx;text-align: center;margin:0 auto;margin-top: 60rpx;border-radius: 100rpx;}
.content_css2{border: 0;border-top: 2rpx solid #E7E7E7;}

</style>