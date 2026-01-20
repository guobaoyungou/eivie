<template>
<view class="dp-menu" :style="{
	fontSize:params.fontsize*2+'rpx',
	backgroundColor:params.bgcolor,
	margin:params.margin_y*2.2+'rpx '+params.margin_x*2.2+'rpx 0',
	padding:params.padding_y*2.2+'rpx '+params.padding_x*2.2+'rpx',
	borderRadius:params.boxradius*2.2+'rpx'
}">
	<view style="padding-top:16rpx;">
		<view class="menu-title flex-bt">
			<view :style="{color:params.titlecolor,fontSize:params.titlesize*2+'rpx'}">{{params.title}}</view>
			<view class='positioning-class'>
				<uni-data-picker class="dp-header-picker" :localdata="arealist" popup-title="地区" @change="areachange" :placeholder="'地区'">
					<view>{{locationCache.address?locationCache.address:'北京市'}}</view>
				</uni-data-picker>
				<img style='width: 22rpx;height: 22rpx;margin-left: 6rpx;' src='https://v2d.diandashop.com/static/admin/img/down-icon.png' />
			</view>
		</view>
		<view class="swiper-item">
			<view v-for="(item,index) in optionsData" :key="index" class="menu-nav6" @click="gotoChange(item)" :style="{'backgroundColor':'#fafafa','border-radius':params.radius + 'px'}">
				<image :src="item.imgurl" :style="{width:params.iconsize*2+'rpx',height:params.iconsize*2+'rpx'}" :class="(item.is_open && item.is_open == 1) ? '':'grayscale'"></image>
				<view class="menu-text" :style="{color:item.color,height:params.fontheight*2+'rpx',lineHeight:params.fontheight*2+'rpx'}">{{item.text|| '按钮文字'}}</view>
				<view class="no-opened" v-if="!item.is_open" >暂未开通</view>
			</view>
		</view>
	</view>
	<loading v-if="loading"></loading>
</view>
</template>
<script>
	var app =getApp();
	export default {
		data(){
			return{
				loading:false,
				arealist:[],
				area:'',
				showlevel:2,
				locationCache:{
					latitude:'',
					longitude:'',
					area:'',
					address:'',
					poilist:[],
					loc_area_type:-1,
					loc_range_type:-1,
					loc_range:'',
					mendian_id:0,
					mendian_name:'',
					showlevel:2
				},
				latitude:'',
				longitude:'',
				optionsData:[],
				cacheExpireTime:10,//缓存过期时间10分钟
			}
    },
		props: {
			params:{},
			data:{}
		},
		mounted(){
			let that = this;
			that.locationCache  = app.getLocationCache();
			that.checkMode();
		},
		methods:{
			gotoChange(item){
				if(item.is_open && item.is_open == 1){
					app.goto(item.hrefurl.split('?')[0]+'?item='+encodeURIComponent(JSON.stringify(item))+'&address='+this.locationCache.address);
				}else{
					app.error("暂未开通此功能！")
				}
			},
			ApiLivepay(address){
				let that = this;
				that.loading = true;
				app.post('ApiLivepay/itemlist',{city_name:(address || '北京市')},function(res){
					that.loading = false;
					that.optionsData = [];
					that.data.forEach(item => {
						res.data.forEach(items => {
							if(items.type == item.type){
								item.is_open = items.is_open
							}
						})
					})
					that.optionsData = that.data;
				})
			},
			checkMode:function(){
				var that = this
				that.initCityAreaList();
				// #ifdef H5
				// console.log(that.locationCache,'checkMode')
				that.checkLocation();
				if(that.locationCache.mendian_id == 0 && that.locationCache.mendian_name == '' && that.locationCache.address == ''){
					that.checkLocation();
				}else{
					that.ApiLivepay(that.locationCache.address)
				}
				// #endif
				// #ifndef H5
				if(that.locationCache.mendian_id == 0 && that.locationCache.mendian_name == ''){
					that.checkLocation();
				}else{
					that.ApiLivepay(that.locationCache.address)
				}
				// #endif
			},
			checkLocation:function(){
				var that = this
				var locationCache = app.getLocationCache();
				// console.log(locationCache,'app.getLocationCache()')
				// #ifdef H5
				if(locationCache.address){
					that.locationCache.address = locationCache.address;
					that.ApiLivepay(that.locationCache.address);
				}
				// #endif
					var loc_area_type = 0;
					var loc_range_type = 0;
					var loc_range = 10;
					app.getLocation(function(res) {
						// console.log(res,'uni.getLocation')
						that.latitude = res.latitude;
						that.longitude = res.longitude;
						//如果从当前地址切到当前城市，则重新定位用户位置
						app.post('ApiAddress/getAreaByLocation', {latitude:that.latitude,longitude:that.longitude}, function(res) {
							// console.log(res,'getAreaByLocation')
							if(res.status==1){
								that.locationCache.loc_area_type = loc_area_type
								that.locationCache.loc_range_type = loc_range_type
								that.locationCache.loc_range = loc_range
								that.locationCache.latitude = that.latitude
								that.locationCache.longitude = that.longitude
								// that.locationCache.showlevel = that.showlevel
								if(loc_area_type==0){
									if(that.showlevel==2){
										that.locationCache.address = res.city
										that.locationCache.area = res.province+','+res.city
										if(that.locationCache.address == null){
											that.locationCache.address = '北京市';
										}
										that.ApiLivepay(that.locationCache.address)
									}else{
										that.locationCache.address = res.district
										that.locationCache.area = res.province+','+res.city+','+res.district
									}
									that.area = that.locationCache.area
									that.curent_address = that.locationCache.address
									app.setLocationCacheData(that.locationCache,that.cacheExpireTime)
								}else if(loc_area_type==1){
									that.locationCache.address = res.landmark
									that.locationCache.area = res.province+','+res.city+','+res.district
									that.area = that.locationCache.area
									that.curent_address = that.locationCache.address
									app.setLocationCacheData(that.locationCache,that.cacheExpireTime)
								}else{
									return;
								}
							}
						})
					},function(res){
						that.locationCache.address = '北京市';
						that.ApiLivepay(that.locationCache.address);
					})
			},
			areachange:function(e){
				var that = this
				const value = e.detail.value
				var area_name = [];
				var showarea = ''
				for(var i=0;i<that.showlevel;i++){
					area_name.push(value[i].text)
					showarea = value[i].text
				}
				that.area = area_name.join(',')
				that.curent_address = showarea
				//全局缓存
				that.locationCache.area = area_name.join(',')
				that.locationCache.address = showarea
					//获取地址中心地标
					app.post('ApiAddress/addressToZuobiao', {
						address:area_name.join('')
					}, function(resp) {
						that.loading = false
						if(resp.status==1){
							that.latitude = resp.latitude
							that.longitude = resp.longitude
							that.locationCache.latitude = resp.latitude;
							that.locationCache.longitude = resp.longitude;
							app.setLocationCacheData(that.locationCache,that.cacheExpireTime)
						}else{
							app.error('地址解析错误');
						}
					})
				that.ApiLivepay(that.locationCache.address)
			},
			initCityAreaList:function(){
				var that = this;
				//地区加载
				if(that.arealist.length==0){
					uni.request({
						url: app.globalData.pre_url+'/static/area.json',
						data: {},
						method: 'GET',
						header: { 'content-type': 'application/json' },
						success: function(res2) {
							if(that.showlevel<3){
								var newlist = [];
								var arealist = res2.data
								for(var i in arealist){
									var item1 = arealist[i]
									if(that.showlevel==2){
										var children = item1.children //市
										var newchildren = [];
										for(var j in children){
											var item2 = children[j]
											item2.children = []; //去掉三级-县的数据
											newchildren.push(item2)
										}
										item1.children = newchildren
									}else{
										item1.children = []; ////去掉二级-市的数据
									}
									newlist.push(item1)
								}
								that.arealist = newlist
							}else{
								that.arealist = res2.data
							}
						}
					});
				}
			},
		}
	}
</script>
<style>
.dp-header-picker .uni-data-tree-dialog{color: #333333;}
.dp-menu {height:auto;position:relative;padding-left:20rpx; padding-right:20rpx; background: #fff;}
.dp-menu .menu-title{width:100%;font-size:30rpx;color:#333333;font-weight:bold;padding:0 24rpx 32rpx 24rpx}
.dp-menu .swiper-item{display:flex;flex-wrap:wrap;flex-direction: row;height:auto;overflow: hidden;align-items: flex-start;}
.dp-menu .menu-nav6 {width:31%;text-align:center;padding:55rpx 0px 43rpx;margin: 1.1%;position: relative;}
.positioning-class{font-weight:normal;color: #666;font-size: 28rpx;display:flex;align-items:center;}
.grayscale{filter: grayscale(100%);}
.no-opened{color: #c5c5c5;font-size: 20rpx;width: 100%;position: absolute;bottom: 25rpx;}
</style>