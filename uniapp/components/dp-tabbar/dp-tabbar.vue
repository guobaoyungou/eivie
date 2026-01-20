<template>
<view v-if="currentIndex>-1">
	<view class="dp-tabbar" v-if="menudata.menustyle==1 && ((menudata.list).length==3 || (menudata.list).length==5)">
		<view class="dp-tabbar-bot"></view>
		<view v-if="(menudata.list).length==5" class="dp-tabbar-module">
			<view class="dp-tabbar-cut" :style="{backgroundColor:menudata.backgroundColor}"></view>
			<view class="dp-tabbar-sideL dp-tabbar-sideLP" :style="{backgroundColor:menudata.backgroundColor}">
				<view @click="goto" :data-url="item.pagePath" :data-index="index" :data-opentype="index!=0 && getplatform()=='baidu'?'':opentype" class="dp-tabbar-item" v-for="(item,index) in menudata.list" :key="item.id" v-if="index<2">
					<view class="dp-tabbar-image-box">
						<image v-if="currentIndex===index" class="dp-tabbar-icon" :src="item.selectedIconPath"></image>
						<image v-else class="dp-tabbar-icon" :src="item.iconPath"></image>
					</view>
					<view class="dp-tabbar-text" :style="{color:item.color}">{{item.text}}</view>
				</view>
			</view>
			<view @click="goto" :data-url="item.pagePath" :data-index="index" :data-opentype="index!=0 && getplatform()=='baidu'?'':opentype" v-for="(item,index) in menudata.list" :key="item.id" v-if="index==2" class="dp-tabbar-center" :style="'background-image: radial-gradient(circle at top, rgba(0,0,0,0) 55rpx, ' + menudata.backgroundColor +' 55rpx);'">
				<image :src="item.iconPath"></image>
				<view :style="{color:item.color}">{{item.text}}</view>
			</view>
			<view class="dp-tabbar-sideR dp-tabbar-sideRP" :style="{backgroundColor:menudata.backgroundColor}">
				<view @click="goto" :data-url="item.pagePath" :data-index="index" :data-opentype="index!=0 && getplatform()=='baidu'?'':opentype" class="dp-tabbar-item" v-for="(item,index) in menudata.list" :key="item.id" v-if="index>=3">
					<view class="dp-tabbar-image-box">
						<image v-if="currentIndex===index" class="dp-tabbar-icon" :src="item.selectedIconPath"></image>
						<image v-else class="dp-tabbar-icon" :src="item.iconPath"></image>
					</view>
					<view class="dp-tabbar-text" :style="{color:item.color}">{{item.text}}</view>
				</view>
			</view>
		</view>
		<view v-if="(menudata.list).length==3" class="dp-tabbar-module">
			<view class="dp-tabbar-cut" :style="{backgroundColor:menudata.backgroundColor}"></view>
			<view class="dp-tabbar-sideL" :style="{backgroundColor:menudata.backgroundColor}">
				<view @click="goto" :data-url="item.pagePath" :data-index="index" :data-opentype="index!=0 && getplatform()=='baidu'?'':opentype" class="dp-tabbar-item" v-for="(item,index) in menudata.list" :key="item.id" v-if="index<1">
					<view class="dp-tabbar-image-box">
						<image v-if="currentIndex===index" class="dp-tabbar-icon" :src="item.selectedIconPath"></image>
						<image v-else class="dp-tabbar-icon" :src="item.iconPath"></image>
					</view>
					<view class="dp-tabbar-text" :style="{color:item.color}">{{item.text}}</view>
				</view>
			</view>
			<view @click="goto" :data-url="item.pagePath" :data-index="index" :data-opentype="index!=0 && getplatform()=='baidu'?'':opentype" v-for="(item,index) in menudata.list" :key="item.id" v-if="index==1" class="dp-tabbar-center" :style="'background-image: radial-gradient(circle at top, rgba(0,0,0,0) 55rpx, ' + menudata.backgroundColor +' 55rpx);'">
				<image :src="item.iconPath"></image>
				<view :style="{color:item.color}">{{item.text}}</view>
			</view>
			<view class="dp-tabbar-sideR" :style="{backgroundColor:menudata.backgroundColor}">
				<view @click="goto" :data-url="item.pagePath" :data-index="index" :data-opentype="index!=0 && getplatform()=='baidu'?'':opentype" class="dp-tabbar-item" v-for="(item,index) in menudata.list" :key="item.id" v-if="index>=2">
					<view class="dp-tabbar-image-box">
						<image v-if="currentIndex===index" class="dp-tabbar-icon" :src="item.selectedIconPath"></image>
						<image v-else class="dp-tabbar-icon" :src="item.iconPath"></image>
					</view>
					<view class="dp-tabbar-text" :style="{color:item.color}">{{item.text}}</view>
				</view>
			</view>
		</view>
	</view>
	<view class="dp-tabbar" v-else>
		<view class="dp-tabbar-bot"></view>
		<view class="dp-tabbar-bar" :style="{backgroundColor:menudata.backgroundColor}">
			<view @click="goto" :data-url="item.pagePath" :data-index="index"
				:data-opentype="index!=0 && getplatform()=='baidu'?'':opentype" class="dp-tabbar-item" 
				v-for="(item,index) in menudata.list" :key="item.id">
				<view class="dp-tabbar-image-box">
					<image v-if="currentIndex===index" class="dp-tabbar-icon" :src="item.selectedIconPath"></image>
					<image v-else class="dp-tabbar-icon" :src="item.iconPath"></image>
				</view>
				<view class="dp-tabbar-text" :style="{color:item.color}">{{item.text}}</view>
			</view>
		</view>
	</view>
</view>
</template>
<script>
	var app = getApp();
	export default {
		data() {
			return {
				menudata: {},
				currentIndex: -1,
				i: 0,
				opentype: 'reLaunch',
			}
		},
		mounted: function() {
			var that = this
			that.settabbar();
			if (app.globalData.platform == 'toutiao') {
				setTimeout(function() {
					that.settabbar();
				}, 100)
			}
		},
		props: {
			opt: {}
		},
		methods: {
			settabbar: function() {
				var that = this
				if (!app.globalData.isinit && this.i < 100) {
					setTimeout(function() {
						that.i++;
						that.settabbar();
					}, 100)
				} else {
					var opentype = 'reLaunch';
					//var currenturl = app._url();

					var pages = getCurrentPages(); //获取加载的页面
					var currentPage = pages[pages.length - 1]; //获取当前页面的对象
					var currenturl = '/' + (currentPage.route ? currentPage.route : currentPage.__route__); //当前页面url 
					if (app.globalData.platform == 'baidu') {
						var opts = currentPage.options;
					} else {
						var opts = currentPage.$vm.opt;
					}
					if(opts && opts.id && opts.bid && currenturl != '/pagesExt/business/index'){
						currenturl += '?id=' + opts.id +'&bid=' + opts.bid
					}else if (opts && opts.id) {
						currenturl += '?id=' + opts.id
					} else if (opts && opts.cid) {
						currenturl += '?cid=' + opts.cid
					} else if (opts && opts.gid) {
						currenturl += '?gid=' + opts.gid
					} else if (opts && opts.bid) {
						currenturl += '?bid=' + opts.bid
					} else if(opts && opts.type && opts.type == 'firstbuy'){
						//首消界面 平台导航
						currenturl += '?type=' + opts.type
					}
					console.log(currenturl)
					if(!that.opt.defaultIndex){
						if(that.opt.reloadthispage && that.opt.reloadthispage ==1){
							var currentIndex = 0;
						}else{
							var currentIndex = -1;
						}
					}else{
						var currentIndex = that.opt.defaultIndex;
					}
					
					var hastabbar = false;
					var menudata = JSON.parse(JSON.stringify(app.globalData.initdata.menudata));
					var tablist = menudata['list'];
					var bindBusiness = 0; //绑定商户 商户默认导航
					if(app.globalData && app.globalData.initdata && app.globalData.initdata.bind_business){
						bindBusiness = app.globalData.initdata.bind_business;
					}
					for (var i = 0; i < tablist.length; i++) {
						if (tablist[i]['pagePath'] == currenturl && bindBusiness == 0) {
							currentIndex = i;
							hastabbar = true;
							menudata['list'][i].color = menudata['selectedColor']
						} else {
							menudata['list'][i].color = menudata['color']
						}
					}
					if (hastabbar == false) {
						var menu2data = JSON.parse(JSON.stringify(app.globalData.initdata.menu2data))
						if (menu2data.length > 0) {
							//首消界面 商户导航
							if(opts && opts.type == 'firstbuy' && opts.typebid){
								currenturl = currenturl.split('?')[0];
								currenturl += '?id=' + opts.typebid;
							}
							//绑定商户 商户默认导航
							if(bindBusiness > 0 && currenturl == '/pages/index/index'){
								currenturl = '/pagesExt/business/index?id='+bindBusiness;
							}
							for (var i in menu2data) {
								if (opts && opts.bid){
									menu2data[i].indexurl = (menu2data[i].indexurl).replace('[bid]', opts.bid);
								}else if(opts && opts.type == 'firstbuy' && opts.typebid){
									//首消界面 商户默认导航
									menu2data[i].indexurl = (menu2data[i].indexurl).replace('[bid]', opts.typebid);
								}else if(bindBusiness > 0){
									//绑定商户 商户默认导航
									menu2data[i].indexurl = (menu2data[i].indexurl).replace('[bid]', bindBusiness);
								}
								if(menu2data[i].indexurl.split('?')[0] == '/pagesA/livepay/mobile_recharge' || menu2data[i].indexurl.split('?')[0] == '/pagesA/livepay/ordinary_recharge' || menu2data[i].indexurl.split('?')[0] == '/pagesA/livepay/record_recharge'){
									menu2data[i].indexurl = menu2data[i].indexurl.split('?')[0];
								}
								if (menu2data[i].indexurl == currenturl) {
									hastabbar = true;
									currentIndex = 10;
									menudata = menu2data[i]
									for (var j in menudata.list) {
										if (opts && opts.bid){
											menudata.list[j].pagePath = (menudata.list[j].pagePath).replace('[bid]',opts.bid);
										}else if(opts && opts.type == 'firstbuy' && opts.typebid){
											//首消界面 商户默认导航
											menudata.list[j].pagePath = (menudata.list[j].pagePath).replace('[bid]',opts.typebid);
										}else if(bindBusiness > 0){
											//绑定商户 商户默认导航
											menudata.list[j].pagePath = (menudata.list[j].pagePath).replace('[bid]',bindBusiness);
										}
										if (menudata.list[j].pagePath == currenturl && menudata['selectedColor']) {
											menudata['list'][j].color = menudata['selectedColor'];
											if (menudata['list'][j]['selectedIconPath']) {
												menudata['list'][j].iconPath = menudata['list'][j]['selectedIconPath'];
											}
										} else if (menudata['color']) {
											menudata['list'][j].color = menudata['color'];
										}
									}
									opentype = '';
								}
							}
						}
					}
					that.opentype = opentype
					that.currentIndex = currentIndex
					that.menudata = menudata
					//app.globalData.currentIndex = currentIndex;
					console.log(currentIndex);
					that.$emit('getmenuindex', currentIndex)
				}
			},
		}
	}
</script>
<style scoped>
	.dp-tabbar {
		height: auto;
		position: relative;
	}
	.dp-tabbar-icon {
		width: 50rpx;
		height: 50rpx;
	}
	.dp-tabbar-bar {
		position: fixed;
		display: flex;
		align-items: center;
		flex-direction: row;
		width: 100%;
		height: 110rpx;
		bottom: 0;
		background: #fff;
		font-size: 24rpx;
		color: #999;
		border-top: 1px solid #efefef;
		z-index: 999999;
		box-sizing: content-box;
	}
	.dp-tabbar-item {
		flex: 1;
		text-align: center;
		overflow: hidden;
		align-items: center
	}
	.dp-tabbar-image-box {
		height: 54rpx;
		margin-bottom: 4rpx;
	}
	.dp-tabbar-text {
		line-height: 30rpx;
		font-size: 24rpx;
	}
	.dp-tabbar-bot {
		height: 110rpx;
		width: 100%;
		box-sizing: content-box
	}
	.dp-tabbar-module {
		position: fixed;
		display: flex;
		align-items: center;
		flex-direction: row;
		width: 100%;
		height: 110rpx;
		bottom: 0;
		font-size: 24rpx;
		color: #999;
		z-index: 999999;
		box-sizing: content-box;
	}
	.dp-tabbar-cut{
		position: absolute;
		height: 40%;
		width: 100%;
		top: 60%;
		background: #fff;
	}
	.dp-tabbar-sideL{
		position: relative;
		height: 100%;
		background: #fff;
		flex: 1;
		display: flex;
		align-items: center;
		border-radius: 0 28rpx 0 0;
	}
	.dp-tabbar-sideLP{
		padding-right: 40rpx;
	}
	.dp-tabbar-sideR{
		position: relative;
		height: 100%;
		background: #fff;
		flex: 1;
		display: flex;
		align-items: center;
		border-radius: 28rpx 0 0 0;
	}
	.dp-tabbar-sideRP{
		padding-left: 40rpx;
	}
	.dp-tabbar-center{
		position: relative;
		width: 104rpx;
		height: 100%;
		margin: 0 -2rpx;
	}
	.dp-tabbar-center image{
		position: absolute;
		top: -45rpx;
		left: 0;
		right: 0;
		margin: 0 auto;
		height: 90rpx;
		width: 90rpx;
		border-radius: 100rpx;
	}
	.dp-tabbar-center view{
		position: absolute;
		width: 100%;
		line-height: 1;
		top: 72rpx;
		text-align: center;
	}
	@supports(bottom: env(safe-area-inset-bottom)) {
		.dp-tabbar-bot {
			padding-bottom: env(safe-area-inset-bottom);
		}
		.dp-tabbar-bar {
			padding-bottom: env(safe-area-inset-bottom);
		}
		.dp-tabbar-module {
			padding-bottom: env(safe-area-inset-bottom);
		}
	}
</style>
