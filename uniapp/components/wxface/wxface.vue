<template>
<scroll-view class="wxface-dialog" id="facedialog" scroll-y="true">
<view class="wxface-face-item" @tap="selectface" :data-face="item" v-for="(item,index) in facelist" v-if="index!=24 && index!=35"><image class="wxface-face-image" :src="pre_url + '/static/chat/wxface/'+index+'.png'"></image></view></scroll-view>
</template>
<script>
	var app = getApp();
	export default {
		data(){
			return {
				pre_url:app.globalData.pre_url,
				facelist:['[微笑]','[撇嘴]','[色]','[发呆]','[得意]','[流泪]','[害羞]','[闭嘴]','[睡]','[大哭]','[尴尬]','[发怒]','[调皮]','[呲牙]','[惊讶]','[难过]','[酷]','[囧]','[抓狂]','[吐]','[偷笑]','[愉快]','[白眼]','[傲慢]','[吃饭]','[困]','[惊恐]','[流汗]','[憨笑]','[悠闲]','[奋斗]','[咒骂]','[疑问]','[嘘]','[晕]','[折磨]','[衰]','[骷髅]','[敲打]','[再见]','[擦汗]','[抠鼻]','[鼓掌]','[糗大了]','[坏笑]','[左哼哼]','[右哼哼]','[哈欠]','[鄙视]','[委屈]','[快哭了]','[阴险]','[亲亲]','[吓]','[可怜]','[菜刀]','[西瓜]','[啤酒]','[篮球]','[乒乓]','[咖啡]','[饭]','[猪头]','[玫瑰]','[凋谢]','[嘴唇]','[爱心]','[心碎]','[蛋糕]','[闪电]','[炸弹]','[刀]','[足球]','[瓢虫]','[便便]','[月亮]','[太阳]','[礼物]','[拥抱]','[强]','[弱]','[握手]','[胜利]','[抱拳]','[勾引]','[拳头]','[差劲]','[爱你]','[NO]','[OK]','[跳跳]','[发抖]','[怄火]','[转圈]']
			}
		},
		props: {
			params:{},
			data:{}
		},
		methods:{
			selectface:function(e){
				var face = e.currentTarget.dataset.face
				this.$emit('selectface',face);
			}
		}
	}
</script>
<style>

.wxface-dialog{width:700rpx;position: fixed; bottom: calc(env(safe-area-inset-bottom) + 120rpx); left:20rpx; right: 20rpx; margin: 0 auto;background:#fff;z-index:9;height:400rpx;padding: 20rpx;box-shadow: 4rpx 8rpx 16rpx 10rpx rgba(0,0,0,0.1);border-radius: 10rpx;}
.wxface-face{width:700rpx;position:relative; max-height:400rpx; overflow: auto; padding: 20rpx; font-size: 0;display:flex;flex-direction:row;flex-wrap:wrap}
.wxface-face-item{cursor: pointer; display: inline-block; vertical-align: bottom; padding:10rpx 4rpx; text-align: center; width:10%; -webkit-box-sizing: border-box !important; -moz-box-sizing: border-box !important; box-sizing: border-box !important;}
.wxface-face-image{width:44rpx; height: 44rpx;}

</style>