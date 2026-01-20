<template>
  <view>
    <view class="nodata" v-if="type == 'normal'">
      <image class="nodata-img" :src="pic" />
      <view class="nodata-text">{{ text }}</view>
    </view>
    <view class="nodataS" v-if="type == 'small'">
      <image class="nodataS-img" :src="pic" />
      <view class="nodataS-text">{{ text }}</view>
    </view>
  </view>
</template>
<script>
export default {
  props: {
    pic: { default: getApp().globalData.pre_url+"/static/img/empty.png" },
    text: { default: "没有相关信息" },
		type: { default: "normal" },
  },
};
</script>
<style>
.nodata {
  width: 100%;
  text-align: center;
  padding-top: 100rpx;
  padding-bottom: 100rpx;
}
.nodata-img {
  width: 300rpx;
  height: 300rpx;
  display: inline-block;
}
.nodata-text {
  display: block;
  text-align: center;
  color: #999999;
  font-size: 28rpx;
  width: 100%;
  margin-top: 30rpx;
}

.nodataS {
  width: 100%;
  text-align: center;
  padding-bottom: 30rpx;
}
.nodataS-img {
  width: 250rpx;
  height: 250rpx;
  display: inline-block;
}
.nodataS-text {
  display: block;
  text-align: center;
  color: #999999;
  font-size: 28rpx;
  width: 100%;
}
</style>