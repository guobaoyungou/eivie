<template>
<view class="container">
	<block v-if="isload">
    <view style="width: 100%;height: 100%;" :style="{color:t('color1')}">
      <view class="content">
        <view style="height: 20rpx;line-height: 20rpx;"></view>

        <view class="mymoney" :style="'background-color:' +t('color1')">
          <view style="font-size: 36rpx;font-weight: bold;">
            {{userinfo.money}}
          </view>
          <view style="margin-top: 10rpx;">
            我的{{t('余额')}}
          </view>
          <view @tap="goto" data-url='/pagesExt/money/withdraw' class="tixian" :style="'color:'+t('color1')">
            我要提现
          </view>
        </view>
        
        <view class="mycombine" :style="'background-color:' +t('color1')">
          <view class="item">
            <view class="item-left">我的{{t('积分')}}</view>
            <view>{{userinfo.score}}</view>
          </view>
          <view class="item">
            <view class="item-left">我的{{t('佣金')}}</view>
            <view>{{userinfo.commission}}</view>
          </view>
          <view class="item">
            <view class="item-left">合成{{t('余额')}}数量</view>
            <view class="item-right">
              <input @input="inputMoney" type="number" placeholder="请输入要合成的数量"  placeholder-style="height: 60rpx;line-height: 60rpx;padding: 0 10rpx;" class="money" >
            </view>
          </view>
        </view>
        <view @tap="tocombine" class="btn" :style="'background-color:'+t('color1')">确认合成</view>
        <view @tap="goto" data-url='/pagesC/my/combinemoneylog' class="combinelog">合成记录</view>
      </view>
    </view>
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

      userinfo: {},
      money:0,
    };
  },

  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.getdata();
  },
	onPullDownRefresh: function () {
		this.getdata();
	},
  methods: {
		getdata:function(){
			var that = this;
			app.loading = true;
			app.get('ApiMoney/combinemoney', {}, function (res) {
				app.loading = false;
        uni.setNavigationBarTitle({
        	title: '合成'+that.t('余额')
        });
        if(res.status == 1){
          that.userinfo = res.userinfo;
          that.loaded();
        }else{
          app.alert(res.msg);
        }
			});
		},
    inputMoney:function(e){
      this.money = e.detail.value;
    },
    tocombine:function(){
      var that = this;
      if(that.money<=0){
        app.alert('数量必须大于0');return;
      }
      app.confirm('确定合成'+that.t('余额')+'？',function(){
        app.post('ApiMoney/combinemoney', {money:that.money}, function (res) {
          if(res.status == 1){
            app.success(res.msg)
            setTimeout(function(){
              that.getdata();
            },900)
          }else{
            app.alert(res.msg);
          }
        });
      })
    }
  }
};
</script>
<style>
  page{background-color: #fff;width: 100%;height: 100%;}
  .content{width: 92%;margin: 0 auto;}
  .mymoney{border-radius: 8rpx;text-align: center;padding: 40rpx 0 20rpx 0;height: 250rpx;color: #fff;}
  .tixian{margin-top: 20rpx;float: right;background-color: #fff;border-radius: 4rpx;text-align: center;width: 150rpx;height: 50rpx;line-height: 50rpx;margin-right: 20rpx;font-size: 26rpx;}
  .mycombine{border-radius: 8rpx;padding: 20rpx 20rpx 40rpx 20rpx;margin-top: 20rpx;color: #fff;}
  .mycombine .item{display: flex;justify-content:space-between;aligin-item:center;margin-top: 20rpx;}
  .mycombine .item-left{width: 200rpx;height: 60rpx;line-height: 60rpx;}
  .mycombine .item-right{flex: 1;background:#fff;height: 60rpx;line-height: 60rpx;border-radius: 2rpx;overflow: hidden;}
  .mycombine .money{width: 100%;height: 60rpx;line-height: 60rpx;padding: 0 10rpx;color: #000;}
  .btn{width: 100%;border-radius: 8rpx;background:#fff;text-align: center;line-height: 80rpx;margin-top: 30rpx;font-weight: bold;color: #fff;}
  .combinelog{text-align: center;width: 150rpx;margin: 20rpx auto;}
</style>