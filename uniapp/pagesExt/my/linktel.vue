<template>
	<view style="text-align: center;margin-top: 30%;font-size: 30rpx;">
		<view v-if="info.name_o || info.tel_o">
			<text>{{info.name_o}} </text>
			<text class="tel" @tap="calltel" :data-tel="info.tel" > {{info.tel_o}}</text>
		</view>
		<view style="margin-top: 40rpx;" v-if="!info.name_t || info.tel_t">
			<text>{{info.name_t}} </text>
			<text  class="tel" @tap="calltel" :data-tel="info.tel"> {{info.tel_t}}</text>
		</view>
		<view style="margin-top: 40rpx;" v-if="!info.name_th || info.tel_th">
			<text>{{info.name_th}} </text>
			<text  class="tel" @tap="calltel" :data-tel="info.tel"> {{info.tel_th}}</text>
		</view>
	</view>
</template>

<script>
var app = getApp();
export default {
	data() {
	return {
			info: [],
		}
	},
	onLoad: function (opt) {
		this.getdata(); 
	},
	methods: {
		getdata:function(){
			var that = this;
			app.get('ApiMy/getWebinfo',{},function (data){
				if(data){
					that.info=data
				}
			});
		},
		//打电话
		calltel: function (e) {
			var tel = e.currentTarget.dataset.tel;
			wx.makePhoneCall({
			      phoneNumber: tel
			})
		},
	}
}
</script>

<style>
page{backgroud:#fff}
.tel{display:inline-block;padding: 10rpx 6rpx;margin-left: 40rpx;}
</style>
