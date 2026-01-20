<template>
	<view class="dd-tab" :style="isfixed?'position:fixed;':''" v-if="!scroll">
		<view v-for="(item,index) in itemdata" :key="index" class="item" :class="st==itemst[index]?'on':''" @tap="changetab" :data-st="itemst[index]">{{item}}<view class="after" :style="{background:color1?color1:t('color1')}"></view></view>
	</view>
	<view :style="isfixed?'position:fixed;':''" v-else class="dd-tab2">
		<scroll-view scroll-x="true">
		<view class="dd-tab2-content" v-if="ismoney">
			<view v-for="(item,index) in itemdata" v-if="showstatus[index]"  :key="index" class="item" :class="st==itemst[index]?'on':''" @tap="changetab" :data-st="itemst[index]">{{item}}<view class="after" :style="{background:color1?color1:t('color1')}"></view></view>
		</view>
		<view class="dd-tab2-content" v-else>
			<view v-for="(item,index) in itemdata"  :key="index" class="item" :class="st==itemst[index]?'on':''" @tap="changetab" :data-st="itemst[index]">{{item}}<view class="after" :style="{background:color1?color1:t('color1')}"></view></view>
		</view>
		</scroll-view>
	</view>
</template>
<script>
	export default {
		props: {
			isfixed:{default:false},
			itemdata:{default:{}},
			itemst:{default:{}},
			st:{default:0},
			color1:'',
			scroll:{default:true},
			showstatus:{default:()=>{}},
			ismoney:{default:0}
		},
		methods:{
			changetab:function(e){
				var st = e.currentTarget.dataset.st;
				this.$emit('changetab',st);
			}
		}
	}
</script>
<style>
.dd-tab{display:flex;width:100%;height:90rpx;background: #fff;top:var(--window-top);z-index:11;}
.dd-tab .item{flex:1;font-size:28rpx; text-align:center; color:#666; height: 90rpx; line-height: 90rpx;overflow: hidden;position:relative}
.dd-tab .item .after{display:none;position:absolute;left:50%;margin-left:-16rpx;bottom:10rpx;height:3px;border-radius:1.5px;width:32rpx}
.dd-tab .on{color: #323233;}
.dd-tab .on .after{display:block}
.dd-tab2{width:100%;height:90rpx;background: #fff;top:var(--window-top);z-index:11;}
.dd-tab2 scroll-view {overflow: auto}
.dd-tab2-content{flex-grow: 0;flex-shrink: 0;display:flex;align-items:center;flex-wrap:nowrap;color:#999999;position:relative;}
.dd-tab2-content .item{flex-grow:1;min-width:140rpx;flex-shrink: 0;height: 90rpx; line-height: 90rpx;text-align:center;position:relative;padding:0 14rpx}
.dd-tab2-content .item .after{display:none;position:absolute;left:50%;margin-left:-20rpx;bottom:10rpx;height:3px;border-radius:1.5px;width:40rpx}
.dd-tab2-content .on{color: #323233;}
.dd-tab2-content .on .after{display:block}
</style>