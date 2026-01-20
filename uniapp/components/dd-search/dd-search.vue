<template>
	<view class="topsearch flex-y-center" :style="{background:bgColorOut,position:isfixed?'fixed':''}" >
		<view class="f1 flex-y-center" :style="{background:bgColorIn}">
			<image class="img" :src="pre_url+'/static/img/search_ico.png'"></image>
			<input :value="keyword" :placeholder="placeholderText" placeholder-style="font-size:24rpx;color:#C2C2C2" @confirm="searchConfirm" @input="searchChange"></input>
		</view>
	</view>
</template>

<script>
var app = getApp();
	export default {
		name:"dd-search",
		data() {
			return {
				keyword:'',
				pre_url:app.globalData.pre_url,
			};
		},
		props: {
			isfixed:{default:false},
			placeholderText:{default:'请输入关键字搜索'},
			bgColorOut:{default:'#fff'},
			bgColorIn:{default:'#f6f6f6'},
			scroll:{default:false}
		},
		methods:{
			changetab:function(e){
				var st = e.currentTarget.dataset.st;
				this.$emit('changetab',st);
			},
			searchChange: function (e) {
				this.keyword = e.detail.value;
			},
			searchConfirm: function (e) {
				var keyword = e.detail.value;
				var loadmore = false;
				this.$emit('getdata',loadmore,keyword);
			}
		}
	}
</script>

<style>
.topsearch{padding: 16rpx 3% 8rpx; width: 100%;top:0; z-index: 11;}
.topsearch .f1{height:60rpx;border-radius:30rpx;border:0;flex:1}
.topsearch .f1 .img{width:24rpx;height:24rpx;margin-left:10px}
.topsearch .f1 input{height:100%;flex:1;padding:0 20rpx;font-size:28rpx;color:#333;}
</style>
