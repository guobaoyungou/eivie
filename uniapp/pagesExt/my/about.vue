<template>
	<view style="text-align: center;margin-top: 25%;font-size: 30rpx;">
		{{info.address}}
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

	}
	
}
</script>

<style>
</style>
