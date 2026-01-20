<template>
<view class="dp-richtext" :style="{
	backgroundColor:params.bgcolor,
	margin:(params.margin_y*2.2)+'rpx '+(params.margin_x*2.2)+'rpx 0',
	padding:(params.padding_y*2.2)+'rpx '+(params.padding_x*2.2)+'rpx'
}">
		<!-- <rich-text style="background-color:{{params.bgcolor}}" nodes='{{content}}'></rich-text> -->
		<!-- <template is="wxParse" data="{{wxParseData:content}}"/> -->
		<!-- <parser html="{{content}}" /> -->
    <block v-if="!richtype || richtype==0">
      <parse  :content="content" @navigate="navigate"></parse>
    </block>
		<block v-else>
      <!-- 处理文章采集 -->
      <view v-if="richtype == 5" style="-ms-text-size-adjust: 100%;-webkit-text-size-adjust: 100%;padding: 0;box-sizing: ;-webkit-box-sizing: ;word-wrap: ;width: 100%;margin: 0;verflow-x: hidden;">
        <!-- #ifdef MP-WEIXIN -->
            <rich-text v-if="richtype == 5" :nodes="content"></rich-text>
        <!-- #endif -->
        <!-- #ifndef MP-WEIXIN -->
          <parse  v-if="richtype == 5" :content="content" @navigate="navigate"></parse>
        <!-- #endif -->
      </view>
		</block>
	</view>
</template>
<script>
	export default {
		props: {
			params:{},
			data:{},
			content:{},
      richtype:{default:0},
      richurl:{default:''},
		}
	}
</script>
<style>
.dp-richtext{height: auto; position: relative;text-align:justify;display:block;word-wrap: break-word;overflow: hidden;font-size:32rpx}
</style>