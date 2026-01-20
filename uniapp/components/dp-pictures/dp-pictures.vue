<template>
<view class="dp-pictures" :style="{
	backgroundColor:params.bgcolor,
	margin:(params.margin_y*2.2)+'rpx '+(params.margin_x*2.2)+'rpx 0'
}">
	<view class="listitem2" v-if="params.style=='2'">
		<view class="listitem2-item" :style="{padding:params.padding_y*2.2+'rpx '+params.padding_x*2.2+'rpx'}" v-for="item in data" :key="item.id">
			<image class="item-image" show-menu-by-longpress='true' :src="item.imgurl" @click="goto" :data-url="item.hrefurl" mode="widthFix" v-if="item.gototype!=1"/>
			<image class="item-image" show-menu-by-longpress='true' :src="item.imgurl" @click="previewImage" :data-url="item.imgurl" mode="widthFix" v-else/>
		</view>
	</view>
	<view class="listitem3" v-if="params.style=='3'">
		<view class="listitem3-item" :style="{padding:(params.padding_y*2.2)+'rpx '+(params.padding_x*2.2)+'rpx'}" v-for="item in data" :key="item.id">
			<image class="item-image" show-menu-by-longpress='true' :src="item.imgurl" @click="goto" :data-url="item.hrefurl" mode="widthFix" v-if="item.gototype!=1"/>
			<image class="item-image" show-menu-by-longpress='true' :src="item.imgurl" @click="previewImage" :data-url="item.imgurl" mode="widthFix" v-else/>
		</view>
	</view>
	<view class="listitem4" v-if="params.style=='4'">
		<view class="listitem4-item" :style="{padding:(params.padding_y*2.2)+'rpx '+(params.padding_x*2.2)+'rpx'}" v-for="item in data" :key="item.id">
			<image class="item-image" show-menu-by-longpress='true' :src="item.imgurl" @click="goto" :data-url="item.hrefurl" mode="widthFix" v-if="item.gototype!=1"/>
			<image class="item-image" show-menu-by-longpress='true' :src="item.imgurl" @click="previewImage" :data-url="item.imgurl" mode="widthFix" v-else/>
		</view>
	</view>
	<view class="listitem5" v-if="params.style=='5'">
		<view class="listitem5-left" :style="{padding:(params.padding_y*2.2)+'rpx '+(params.padding_x*2.2)+'rpx'}">
			<image class="item-image" show-menu-by-longpress='true' :src="data[0]['imgurl']" @click="goto" :data-url="data[0]['hrefurl']" mode="widthFix" v-if="data[0]['gototype']!=1"/>
			<image class="item-image" show-menu-by-longpress='true' :src="data[0]['imgurl']" @click="previewImage" :data-url="data[0]['imgurl']" mode="widthFix" v-else/>
		</view>
		<view class="listitem5-right" v-if="data.length>2">
			<view class="listitem5-top" :style="{padding:(params.padding_y*2.2)+'rpx '+(params.padding_x*2.2)+'rpx'}">
				<image class="item-image" show-menu-by-longpress='true' :src="data[1]['imgurl']" @click="goto" :data-url="data[1]['hrefurl']" mode="widthFix" v-if="data[1]['gototype']!=1"/>
				<image class="item-image" show-menu-by-longpress='true' :src="data[1]['imgurl']" @click="previewImage" :data-url="data[1]['imgurl']" mode="widthFix" v-else/>
			</view>
			<view class="listitem5-bottom" v-if="data.length>3">
				<view class="listitem5-left" :style="{padding:(params.padding_y*2.2)+'rpx '+(params.padding_x*2.2)+'rpx'}">
					<image class="item-image" show-menu-by-longpress='true' :src="data[2]['imgurl']" @click="goto" :data-url="data[2]['hrefurl']" mode="widthFix" v-if="data[2]['gototype']!=1"/>
					<image class="item-image" show-menu-by-longpress='true' :src="data[2]['imgurl']" @click="previewImage" :data-url="data[2]['imgurl']" mode="widthFix" v-else/>
				</view>
				<view class="listitem5-right" :style="{padding:(params.padding_y*2.2)+'rpx '+(params.padding_x*2.2)+'rpx'}">
					<image class="item-image" show-menu-by-longpress='true' :src="data[3]['imgurl']" @click="goto" :data-url="data[3]['hrefurl']" mode="widthFix" v-if="data[3]['gototype']!=1"/>
					<image class="item-image" show-menu-by-longpress='true' :src="data[3]['imgurl']" @click="previewImage" :data-url="data[3]['imgurl']" mode="widthFix" v-else/>
				</view>
			</view>
			<view v-else class="listitem5-bottom" :style="{padding:(params.padding_y*2.2)+'rpx '+(params.padding_x*2.2)+'rpx'}">
				<image class="item-image" show-menu-by-longpress='true' :src="data[2]['imgurl']" @click="goto" :data-url="data[2]['hrefurl']" mode="widthFix" v-if="data[2]['gototype']!=1"/>
				<image class="item-image" show-menu-by-longpress='true' :src="data[2]['imgurl']" @click="previewImage" :data-url="data[2]['imgurl']" mode="widthFix" v-else/>
			</view>
		</view>
		<view v-else class="listitem5-right" :style="{padding:(params.padding_y*2.2)+'rpx '+(params.padding_x*2.2)+'rpx'}">
			<image class="item-image" show-menu-by-longpress='true' :src="data[1]['imgurl']" @click="goto" :data-url="data[1]['hrefurl']" mode="widthFix" v-if="data[1]['gototype']!=1"/>
			<image class="item-image" show-menu-by-longpress='true' :src="data[1]['imgurl']" @click="previewImage" :data-url="data[1]['imgurl']" mode="widthFix" v-else/>
		</view>
	</view>
	<view class="listitem6" v-if="params.style=='6'">
		<scroll-view scroll-x style="width: 100%;white-space: nowrap;">
			<view class="listitem6-item" :style="{padding:params.padding_y*2.2+'rpx '+params.padding_x*2.2+'rpx',width:params.imageWidth+'%',height:params.imageHeight*2.2+'rpx',borderRadius:params.imageRadius*2.2+'rpx',marginRight:params.imageRight+'%'}" v-for="item in data" :key="item.id">
				<image class="item-image" show-menu-by-longpress='true' :src="item.imgurl" @click="goto" :data-url="item.hrefurl" mode="scaleToFill" v-if="item.gototype!=1"/>
				<image class="item-image" show-menu-by-longpress='true' :src="item.imgurl" @click="previewImage" :data-url="item.imgurl" mode="widthFix" v-else/>
			</view>
		</scroll-view>
	</view>
</view>
</template>
<script>
	export default {
		props: {
			params:{},
			data:{}
		}
	}
</script>
<style>
.dp-pictures{height: auto; position: relative;}
.dp-pictures .item-image {display: block;max-width: 100%;max-height: 100%;width: 100%;height:auto; border: 0;padding: 0;outline: none;}
.dp-pictures .listitem2{width:100%;display:flex;flex-wrap:wrap}
.dp-pictures .listitem2-item{width:50%;}
.dp-pictures .listitem3{width:100%;display:flex;flex-wrap:wrap}
.dp-pictures .listitem3-item{width:33.33%;}
.dp-pictures .listitem4{width:100%;display:flex;flex-wrap:wrap}
.dp-pictures .listitem4-item{width:25%;}
.dp-pictures .listitem5{width:100%;display:flex;justify-content:center;align-items:center}
.dp-pictures .listitem5-left{width:50%;height:100%;}
.dp-pictures .listitem5-right{width:50%;height:100%}
.dp-pictures .listitem5-top{width:100%;height:50%}
.dp-pictures .listitem5-bottom{width:100%;height:50%;display:flex}
.dp-pictures .left-image {display:flex;width: 100%;height:100%}
.dp-pictures .right-image {display:flex;width: 100%;height:100%}
.dp-pictures .top-image {display:flex;width: 100%;height:100%}
.dp-pictures .bottom-image {display:flex;width: 100%;height:100%}
.dp-pictures .listitem6{width:100%;}
.dp-pictures .listitem6 .listitem6-item{display: inline-block;overflow: hidden;}
.dp-pictures .listitem6 .listitem6-item .item-image{display: block;max-width: 100%;max-height: 100%;width: 100%;height:100%; border: 0;padding: 0;outline: none}
</style>