<template>
<view class="container" :style="{backgroundColor:pageinfo.bgcolor}">
	<dp :pagecontent="pagecontent" :menuindex="menuindex"></dp>
	<dp-guanggao :guanggaopic="guanggaopic" :guanggaourl="guanggaourl" :guanggaotype="guanggaotype" :param="guanggaoparam"></dp-guanggao>
	<view style="position:fixed;top:15vh;left:20rpx;z-index:991;background:rgba(0,0,0,0.6);border-radius:20rpx;color:#fff;padding:0 10rpx" v-if="oglist">
		<swiper style="position:relative;height:54rpx;width:450rpx;" autoplay="true" :interval="5000" vertical="true">
			<swiper-item v-for="(item, index) in oglist" :key="index" @tap="goto" :data-url="'/pages/shop/product?id=' + item.proid" class="flex-y-center">
				<image :src="item.headimg" style="width:40rpx;height:40rpx;border:1px solid rgba(255,255,255,0.7);border-radius:50%;margin-right:4px"></image>
				<div style="width:400rpx;white-space:nowrap;overflow:hidden;text-overflow: ellipsis;font-size:22rpx">{{item.nickname}} {{item.showtime}}购买了 {{item.name}}</div>
			</swiper-item>
		</swiper>
	</view>
	<dp-tabbar :opt="opt" @getmenuindex="getmenuindex"></dp-tabbar>
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
			id: 0,
			pageinfo: [],
			pagecontent: [],
			title: "",
			oglist: "", 
			guanggaopic: "",
			guanggaourl: "",
			guanggaotype: "1",
      guanggaoparam:{},
		}
	},
	onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.getdata();
	},
	onPullDownRefresh:function(e){
		this.getdata();
	},
	onPageScroll: function (e) {
		uni.$emit('onPageScroll',e);
	},
	methods: {
		getdata:function(){
			var that = this;
			var opt = this.opt
			var id = 0;
			if (opt && opt.id) {
			  id = opt.id;
			}
			that.loading = true;
			app.get('ApiBusiness/main', {id: id}, function (data) {
				that.loading = false;
			  if (data.status == 2) {
			    //付费查看
			    app.goto('/pagesExt/pay/pay?fromPage=index&id=' + data.payorderid + '&pageid=' + that.id, 'redirect');
			    return;
			  }
			  if (data.status == 1) {
			    var pagecontent = data.pagecontent;
					that.title = data.pageinfo.title;
					that.oglist = data.oglist;
					that.guanggaopic = data.guanggaopic;
					that.guanggaourl = data.guanggaourl;
					that.guanggaotype = data.guanggaotype;
          that.guanggaoparam = data.guanggaoparam;
					that.pageinfo = data.pageinfo;
					that.pagecontent = data.pagecontent;
			    uni.setNavigationBarTitle({
			      title: data.pageinfo.title
			    });
					that.loaded();
			  } else {
			    if (data.msg) {
			      app.alert(data.msg, function () {
			        if (data.url) app.goto(data.url);
			      });
			    } else if (data.url) {
			      app.goto(data.url);
			    } else {
			      app.alert('您无查看权限');
			    }
			  }
			});
		}
	}
}
</script>