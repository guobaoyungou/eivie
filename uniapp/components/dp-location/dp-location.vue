<template>
<view class="dp-search" :style="{
	backgroundColor:params.bgcolor,
	margin:(params.margin_y*2.2)+'rpx '+(params.margin_x*2.2)+'rpx',
	padding:(params.padding_y*2.2)+'rpx '+(params.padding_x*2.2)+'rpx',
	borderColor:params.bordercolor,
	borderRadius:params.borderradius+'px',
	color:params.color,
	borderStyle:'solid',
	borderWidth:'1px'
}">
    <block>
    	<view class="dp-header-location-box">
    		<!-- 显示定位：城市|地标 Start -->
    		<!-- 当前城市 -->
    		<view v-if="sysset.mode==2 && sysset.loc_area_type==0" class="dp-header-location">
					<uni-data-picker class="dp-header-picker" :class="params.showsearch==1?'dp-header-address-widthfixed':'dp-header-address'" :localdata="arealist" popup-title="地区" @change="areachange"  :placeholder="'地区'">
						<view>{{locationCache.address?locationCache.address:'请选择定位'}}</view>
					</uni-data-picker>
    			<view class="dp-header-more"><text class="iconfont iconjiantou" style="font-size: 24rpx;"></text></view>
    		</view>
    		<!-- 当前地址（商圈地址等） -->
    		<view v-if="sysset.mode==2 && sysset.loc_area_type==1" class="dp-header-location">
    			<view class="flex-y-center" @tap="showNearbyBox">
    				<view :class="params.showsearch==1?'dp-header-address-widthfixed':'dp-header-address'">{{locationCache.address?locationCache.address:'请选择定位'}}</view>
    				<view class="dp-header-more"><text class="iconfont iconjiantou" style="font-size: 24rpx;"></text></view>
    			</view>
    		</view>
				<view v-if="sysset.mode==3" class="dp-header-location">
					<view class="flex-y-center" @tap="showMendianModal">
						<view :class="params.showsearch==1?'dp-header-address-widthfixed':'dp-header-address'">{{locationCache.mendian_name?locationCache.mendian_name:'请选择门店'}}</view>
						<view class="dp-header-more"><text class="iconfont iconjiantou" style="font-size: 24rpx;"></text></view>
					</view>
				</view>
				
				<view class="dp-location-search" v-if="params.showsearch==1">
					<input type="text" v-model="keyword" :placeholder="params.placeholder" placeholder-style="color:#c0c0c0;font-size:24rpx" style="flex: 1;"  @confirm="searchgoto" :data-url="params.hrefurl">
					<image class="dp-location-search-icon" :src="pre_url+'/static/img/search.png'"  @tap="searchgoto" :data-url="params.hrefurl"></image>
				</view>
				<view class="dp-location-iconlist" v-if="params.showicon==1 && data.length>0">
					<view class="dp-location-icon" v-for="(item,index) in data" :key="index" @tap="goto" :data-url="item.hrefurl">
						<image :src="item.imgurl"></image>
					</view>
				</view>
    	</view>
    </block>
    <!-- 显示定位：城市|地标 End -->
		<!-- modal start-->
		<!-- 附近商圈地址 -->
		<view v-if="sysset.mode==2 && sysset.loc_area_type==1 && show_nearbyarea" class="dp-location-modal">
			<view :style="{height:(34+statusBarHeight)+'px'}" v-if='homeNavigationCustom != 0'></view>
			<view class="dp-location-modal-content">
				<view class="dp-header-nearby-box">
					<view class="dp-header-nearby-body">
						<view class="dp-header-nearby-search">
							<view class="dp-header-nearby-close" @tap="closeNearbyBox"><image :src="pre_url+'/static/img/location/close-dark.png'"></image></view>
							<view class="dp-header-nearby-input" >
								<input type="text" class="input" placeholder="商圈/大厦/住宅" placeholder-style="font-size:26rpx" :value="placekeyword" @input="placekeywordInput" @confirm="searchPlace"/>
								<button class="searchbtn" :style="{borderColor:t('color1'),color:'#FFF',backgroundColor:t('color1')}" @tap="searchPlace">搜索</button>
							</view>
						</view>
						<view class="dp-suggestion-box" v-if="suggestionplacelist.length>0">
							<block v-for="(item,index) in suggestionplacelist" :key="index">
								<view class="dp-suggestion-place" @tap="chooseSuggestionAddress" :data-index="index">
									<view class="flex-y-center">
										<image :src="pre_url+'/static/img/address3.png'"></image>
										<text class="s-title">{{item.title}}</text>
									</view>
									<view class="s-info flex-y-center">
										<text class="s-area">{{item.city}} {{item.district}} </text>
										<text class="s-address">{{item.address}}</text>
									</view>
								</view>
							</block>
						</view>
						<view class="dp-header-nearby-content flex-bt">
							<view>已选：{{curent_address}}</view>
							<view class="flex-xy-center" @tap="refreshAddress">
								<image class="dp-header-nearby-imgicon" :src="pre_url+'/static/img/location/location-dark.png'">
								<text class="dp-header-nearby-tip">重新定位</text>
							</view>
						</view>
						<view class="dp-header-nearby-content" style="margin-top: 20rpx;">
							<view class="dp-header-nearby-title flex-y-center">
								<image class="dp-header-nearby-imgicon" :src="pre_url+'/static/img/location/home-dark.png'"></image>
								<text>我的地址</text>
							</view>
							<view class="dp-header-nearby-list">
								<view class="dp-header-nearby-info" v-for="(item,index) in myaddresslist" :key="index" v-if="index>3?(isshowalladdress?1==1:1==2):1==1" @tap="chooseMyAddress" :data-index="index">
										<view class="">{{item.address}}</view>
										<view class="dp-header-nearby-txt">{{item.name}} {{item.tel}}</view>
								</view>
							</view>
							<view class="dp-header-nearby-all flex-y-center" @tap="showAllAddress">
								<block v-if="myaddresslist.length>0">
									<text>{{isshowalladdress?'收起全部地址':'展开更多地址'}} </text><image :src="pre_url+'/static/img/location/'+(isshowalladdress?'up-grey.png':'down-grey.png')"></image>
								</block>
								<text v-else>-暂无地址-</text>
							</view>
						</view>
						<!-- 附近地址 -->
						<view class="dp-header-nearby-content" style="margin-top: 20rpx;">
							<view class="dp-header-nearby-title flex-y-center">
								<image class="dp-header-nearby-imgicon" :src="pre_url+'/static/img/location/address-dark.png'"></image>
								<text>附近地址</text>
							</view>
							<view class="dp-header-nearby-list">
								<view class="dp-header-nearby-info"  v-for="(item,index) in nearbyplacelist" :key="index"  @tap="chooseNearbyAddress" :data-index="index">
									<view class="">{{item.title}}</view>
									<view class="dp-header-nearby-txt">{{item.address}}</view>
								</view>
							</view>
						</view>
					</view>
				</view>
				<view class="dp-header-location-bottom flex-xy-center" :style="{color:t('color1')}" @tap="addMyAddress">
					<text class="dp-location-add-address">+</text><text style="padding-top: 10rpx;">新增收货地址</text>
				</view>
			</view>
		</view>
		<!-- 附近商圈地址 -->
		<!-- 门店选择start -->
		<view v-if="sysset.mode==3 && isshowmendianmodal" class="dp-location-modal dp-location-modal-mendian">
			<view class="dp-location-modal-content">
				<view class="popup__container popup_mendian" v-if="isshowmendianmodal" style="z-index: 999999;">
					<view class="popup__overlay" @tap.stop="hideMendianModal"></view>
					<view class="popup__modal">
						<view class="popup__title">
							<text class="popup__title-text">请选择门店</text>
							<image :src="pre_url+'/static/img/close.png'" class="popup__close" style="width:36rpx;height:36rpx" @tap.stop="hideMendianModal"/>
						</view>
						<view class="popup__content">
							<block v-for="(item,index) in mendianlist" :key="index">
								<view class="mendian-info" @tap="changeMendian" :data-index="index" :data-id="item.id" :style="{background:(item.id==locationCache.mendian_id?'rgba('+t('color1rgb')+',0.1)':'')}">
									<view class="b1"><image :src="item.pic"></image></view>
									<view class="b2">
										<view class="t1">{{item.name}}</view>
										<view class="t2 flex-y-center">
											<view class="mendian-distance">{{item.distance}}</view>
											<block v-if="item.address || item.area">
												<view class="line" v-if="item.distance"> </view>
												<view class="mendian-address"> {{item.address?item.address:item.area}}</view>
											</block>
										</view>
									</view>
								</view>
							</block>
						</view>
					</view>
				</view>
			</view>
		</view>
		<!-- 门店选择end -->
		<!-- modal end -->
</view>
</template>
<script>
	var app =getApp();
	export default {
		data(){
			return {
				pre_url:getApp().globalData.pre_url,
				mid:app.globalData.mid,
				data_placeholder:'',//搜索提示
				data_hrefurl:'',
				keyword:'',
				showsearch:2,
				
				//定位模式
				sysset:{},
				latitude:'',
				longitude:'',
				showlevel:2,
				curent_address:'',//当前位置: 省市县或者地标
				arealist:[],
				area:'',//地址拼接 北京,北京市,朝阳区
				show_nearbyarea:false,
				ischangeaddress:false,
				nearbyplacelist:[],
				myaddresslist:[],
				isshowalladdress:false,
				placekeyword:'',
				suggestionplacelist:[],
				
				//门店模式 显示最近的一个门店
				mendianid:0,
				mendian:{},
				mendianlist:[],
				mendianindex:-1,
				isshowmendianmodal:false,
				needRefreshMyaddress:false,
				headericonlist:[],
				cacheExpireTime:10,//缓存过期时间10分钟
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
				statusBarHeight: 20,
				homeNavigationCustom: app.globalData.homeNavigationCustom,
				shengzhixiaxian:{},//省直辖县级行政区划、自治区直辖县级行政区划下的第一个市
			}
		},
		props: {
			params: {},
			data: {}
		},
		mounted:function(){
				var that = this;
				var sysinfo = uni.getSystemInfoSync();
				that.statusBarHeight = sysinfo.statusBarHeight;
				that.showlevel = that.params.showlevel || 2;
				that.locationCache  = app.getLocationCache();
				that.checkMode();
		},
		methods:{
			getdata:function(){
				this.checkAreaByShowlevel();
				this.$emit('getdata');
			},
			searchgoto:function(e){
				var that = this;
				var keyword = that.keyword;
				var url = e.currentTarget.dataset.url;
				if (url.indexOf('?') > 0) {
						url += '&keyword='+keyword;
				}else{
						url += '?keyword='+keyword;
				}
				var opentype = e.currentTarget.dataset.opentype
				app.goto(url,opentype);
			},
			checkMode:function(){
				var that = this
				app.get('ApiIndex/checkMode', {}, function(res) {
					that.sysset = res.sysset
					if(that.sysset.mode==2){
						app.setLocationCache('mendian_id',0)
						app.setLocationCache('mendian_name','')
						if(that.sysset.loc_area_type==0){
							that.initCityAreaList()
						}
						that.checkLocation()
					}else if(that.sysset.mode==3){
						that.area = ''
						app.setLocationCache('area','')
						app.setLocationCache('address','')
						that.checkLocation()
					}
				})
			},
			//头部定位start
			checkLocation:function(){
				var that = this
				var locationCache = app.getLocationCache();
				if(that.sysset.mode==2){
					var loc_area_type = that.sysset.loc_area_type;
					var loc_range_type = that.sysset.loc_range_type;
					var loc_range = that.sysset.loc_range;
					var cache_loc_area_type = locationCache.loc_area_type;
					var cache_loc_range_type = locationCache.loc_range_type;
					var cache_loc_range = locationCache.loc_range;
					var cachearea = locationCache.area;
					var cacheshowarea = locationCache.address;
					//缓存为空 或 显示城市和当前地址切换 或 同城和自定义范围切换 或 显示距离发生变化
					if(!cacheshowarea || (loc_area_type==0 && locationCache.showlevel!=that.showlevel) || (cacheshowarea && (cache_loc_area_type!=loc_area_type || cache_loc_range_type!=loc_range_type || cache_loc_range!=loc_range))){
							app.getLocation(function(res) {
								that.latitude = res.latitude;
								that.longitude = res.longitude;
								//如果从当前地址切到当前城市，则重新定位用户位置
								app.post('ApiAddress/getAreaByLocation', {latitude:that.latitude,longitude:that.longitude}, function(res) {
									if(res.status==1){
										that.locationCache.loc_area_type = loc_area_type
										that.locationCache.loc_range_type = loc_range_type
										that.locationCache.loc_range = loc_range
										that.locationCache.latitude = that.latitude
										that.locationCache.longitude = that.longitude
										that.locationCache.showlevel = that.showlevel
										if(loc_area_type==0){
											if(that.showlevel==1){
												that.locationCache.address = res.province
												that.locationCache.area = res.province
											}else if(that.showlevel==2){
												that.locationCache.address = res.city
												that.locationCache.area = res.province+','+res.city
											}else{
												that.locationCache.address = res.district
												that.locationCache.area = res.province+','+res.city+','+res.district
											}
											that.area = that.locationCache.area
											that.curent_address = that.locationCache.address
											app.setLocationCacheData(that.locationCache,that.cacheExpireTime)
											that.getdata()
										}else if(loc_area_type==1){
											that.locationCache.address = res.landmark
											that.locationCache.area = res.province+','+res.city+','+res.district
											that.area = that.locationCache.area
											that.curent_address = that.locationCache.address
											app.setLocationCacheData(that.locationCache,that.cacheExpireTime)
											that.refreshNearbyPlace();
											that.getdata()
										}else{
											return;
										}
									}
								})
							})
					}else{
						that.area = that.locationCache.area
						that.curent_address = that.locationCache.address
						that.latitude = that.locationCache.latitude
						that.longitude = that.locationCache.longitude
					}
				}else if(that.sysset.mode==3){
					if(!locationCache.latitude || !locationCache.longitude || !locationCache.mendian_id){
							app.getLocation(function(res) {
								that.latitude = res.latitude;
								that.longitude = res.longitude;
								var mendian_id = 0;
								var mendian_isinit = 0;
								if(locationCache.mendian_id){
									mendian_id = locationCache.mendian_id
								}
								if(locationCache.mendian_isinit){
									mendian_isinit = locationCache.mendian_isinit
								}
								app.post('ApiMendian/getNearByMendian', {latitude:that.latitude,longitude:that.longitude,mendian_id:mendian_id,mendian_isinit:mendian_isinit}, function(data) {
									if(data.status==1){
										 that.locationCache.latitude = that.latitude;
										 that.locationCache.longitude = that.longitude
										 that.locationCache.mendian_id = data.mendian.id
										 that.locationCache.mendian_name = data.mendian.name
										 that.locationCache.mendian_isinit = 0
										 app.setLocationCacheData(that.locationCache,that.cacheExpireTime)
										 if(data.mendian_id!=mendian_id){
											 that.getdata();
										 }
									}
								})
						})
					}
				}
			},
			checkAreaByShowlevel:function(){
				var that  = this
				if(that.sysset.mode==2 && that.sysset.loc_range_type==0){
					var locationCache = app.getLocationCache();
					if(locationCache && locationCache.area){
						var area = '';
						var areaArr = locationCache.area.split(',');
						var showlevel = locationCache.showlevel?locationCache.showlevel:that.showlevel
						if(showlevel==1 && areaArr.length>0){
							area = areaArr[0]
						}else if(showlevel==2 && areaArr.length>1){
							area = areaArr[0] + ','+areaArr[1]
						}else if(showlevel==3 && areaArr.length>2){
							area = areaArr[0] + ','+areaArr[1] + ','+areaArr[2]
						}
						that.locationCache.area = area;
						app.setLocationCache('area',area,that.cacheExpireTime)
					}
				}
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
											//获取 [自治区直辖县级行政区划]等特殊地区下的第一个市 start
											if(item2.text && (item2.text == '自治区直辖县级行政区划' || item2.text == '省直辖县级行政区划')){
												if (item2.children.length > 0) {
													that.shengzhixiaxian[item2.value] = item2.children[0]['text'];
												}
											}
											//end
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
			areachange:function(e){
				var that = this
				const value = e.detail.value
				var area_name = [];
				var showarea = ''
				var firstareaname = ''; //[自治区直辖县级行政区划]等特殊地区下的第一个市；
				for(var i=0;i<that.showlevel;i++){
					area_name.push(value[i].text)
					showarea = value[i].text
					if(that.shengzhixiaxian.hasOwnProperty(value[i].value)){
						firstareaname = that.shengzhixiaxian[value[i].value];
					}
				}
				
				that.area = area_name.join(',')
				that.curent_address = showarea
				//全局缓存
				that.locationCache.area = area_name.join(',')
				that.locationCache.address = showarea
			
				if(that.sysset.loc_area_type==0){
					//拼接市区
					if(firstareaname){
						area_name.push(firstareaname)
					}
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
							that.getdata();
						}else{
							app.error('地址解析错误');
						}
					})
				}
			},
			closeNearbyBox:function(){
				this.show_nearbyarea = false
			},
			showNearbyBox:function(){
				var that = this
				this.show_nearbyarea = true
				this.placekeyword = ''
				this.suggestionplacelist = []
				var locationCache = app.getLocationCache();
				var nearbylist = locationCache.poilist
				if(!nearbylist){
					nearbylist = [];
				}
				if(nearbylist && nearbylist.length>0){
					this.nearbyplacelist = nearbylist
				}else{
					that.refreshNearbyPlace()
				}
				//获取我的收货地址
				if(app.globalData.mid){
					that.loading = true
					that.getMyAddress()
				}
			},
			changeAddress:function(){
				this.ischangeaddress = true
			},
			addMyAddress:function(e){
				this.needRefreshMyaddress = true;
				this.show_nearbyarea = false
				app.goto("/pagesB/address/addressadd?type=1")
			},
			getMyAddress:function(){
				var that = this;
				that.loading = true
				app.post('ApiAddress/address', {
					type:1
				}, function(resp) {
					that.loading = false
					if(resp.status==1){
						that.myaddresslist = resp.data
					}
				})
			},
			cancelChangeAddress:function(){
				this.ischangeaddress = false
			},
			refreshAddress:function(e){
				var that = this
				that.loading = true
				app.getLocation(function(res) {
					var latitude = res.latitude;
					var longitude = res.longitude;
					//请求当前地址[取商圈地址]
					app.post('ApiAddress/getAreaByLocation', {
						latitude: latitude,
						longitude: longitude,
						type:1
					}, function(resp) {
						that.loading = false
						if(resp.status==1){
							var data = resp.data
							
							that.latitude = latitude
							that.longitude = longitude
							that.curent_address = data.address_reference.landmark
							that.nearbyplacelist = data.pois
							
							that.locationCache.area = data.address_component.province+','+data.address_component.city+','+data.address_component.district
							that.locationCache.address = data.address_reference.landmark
							that.locationCache.latitude = latitude
							that.locationCache.longitude = longitude
							that.locationCache.poilist = data.pois
							app.setLocationCacheData(that.locationCache,that.cacheExpireTime)
							that.getdata()
							that.show_nearbyarea = false
						}
					})
				},function(res){
					console.error(res);
				});
			},
			showAllAddress:function(){
				this.isshowalladdress = this.isshowalladdress?false:true
			},
			chooseMyAddress:function(e){
				var that = this
				var index = e.currentTarget.dataset.index
				var info = that.myaddresslist[index]
				that.curent_address = info.address
				that.latitude = info.latitude
				that.longitude = info.longitude
				
				that.locationCache.area = info.province+','+info.city+','+info.district
				that.locationCache.address = info.address
				that.locationCache.latitude = info.latitude
				that.locationCache.longitude = info.longitude
				that.locationCache.poilist = info.pois
				that.area = that.locationCache.area
				
				app.setLocationCacheData(that.locationCache,that.cacheExpireTime)
				that.refreshNearbyPlace();
				that.getdata()
				that.show_nearbyarea = false
			},
			chooseNearbyAddress:function(e){
				var that = this
				var index = e.currentTarget.dataset.index
				var info = that.nearbyplacelist[index]
				that.curent_address = info.title
				that.latitude = info.location.lat
				that.longitude = info.location.lng
				that.locationCache.area = info.ad_info.province+','+info.ad_info.city+','+info.ad_info.district
				that.locationCache.address = that.curent_address
				that.locationCache.latitude = that.latitude
				that.locationCache.longitude = that.longitude
				app.setLocationCacheData(that.locationCache,that.cacheExpireTime)
				that.refreshNearbyPlace();
				that.getdata()
				that.show_nearbyarea = false
			},
			chooseSuggestionAddress:function(e){
				var that = this
				var index = e.currentTarget.dataset.index
				var info = that.suggestionplacelist[index]
				that.curent_address = info.title
				that.latitude = info.location.lat
				that.longitude = info.location.lng
				that.locationCache.area = info.province+','+info.city+','+info.district
				that.locationCache.address = that.curent_address
				that.locationCache.latitude = that.latitude
				that.locationCache.longitude = that.longitude
				app.setLocationCacheData(that.locationCache,that.cacheExpireTime)
				that.refreshNearbyPlace();
				that.getdata()
				that.show_nearbyarea = false
			},
			refreshNearbyPlace:function(latitude='',longitude=''){
				var that = this
				if(latitude=='' && longitude==''){
					latitude = that.latitude
					longitude = that.longitude
				}
				if(latitude && longitude){
					that.loading = true;
					app.post('ApiAddress/getAreaByLocation', {
						latitude: latitude,
						longitude: longitude,
						type:1
					}, function(resp) {
						that.loading = false
						if(resp.status==1){
							var data = resp.data
							that.nearbyplacelist = data.pois
							app.setLocationCache('poilist',that.nearbyplacelist,that.cacheExpireTime)
						}
					})
				}
			},
			placekeywordInput:function(e){
				this.placekeyword = e.detail.value
			},
			searchPlace:function(e){
				var that = this
				if(that.placekeyword==''){
					that.suggestionplacelist = []
					return;
				}
				var locationCacheData = app.getLocationCache();
		
				var region = '';
				if(locationCacheData && locationCacheData.area){
					var areaArr = locationCacheData.area.split(',')
					if(areaArr.length==2){
						region = areaArr[1]
					}else if(areaArr.length==3){
						region = areaArr[2]
					}else if(areaArr.length==1){
						region = areaArr[0]
					}
				}
				that.loading = true
				app.post('ApiAddress/suggestionPlace', {
					latitude: locationCacheData.latitude,
					longitude: locationCacheData.longitude,
					region:region,
					keyword:that.placekeyword
				}, function(resp) {
					that.loading = false
					if(resp.status==1){
						that.suggestionplacelist = resp.data
					}
				})
			},
			//头部定位end
			
			//门店模式start
			showMendianModal:function(){
				var that = this;
				if(that.mendianlist.length>0){
					that.isshowmendianmodal = true
				}else{
					app.post('ApiMendian/mendianlist', {
						latitude: app.getLocationCache('latitude'),
						longitude: app.getLocationCache('longitude'),
					}, function(resp) {
						that.loading = false
						if(resp.status==1){
							that.mendianlist = resp.data
							that.isshowmendianmodal = true
						}else{
							app.error(resp.msg);
						}
					})
				}
			},
			hideMendianModal:function(){
				this.isshowmendianmodal = false
			},
			changeMendian:function(e){
				var that = this;
				// var mendianid = e.currentTarget.dataset.id;
				var index = e.currentTarget.dataset.index;
				var mendianinfo = that.mendianlist[index]
				app.setLocationCache('mendian_id',mendianinfo.id)
				app.setLocationCache('mendian_name',mendianinfo.name)
				app.setLocationCache('mendian_isinit',0)
				var locationCache = app.getLocationCache()
				that.locationCache = locationCache
				var mendian = {};
				mendian.id= mendianinfo.id;
				mendian.name = mendianinfo.name
				mendian.area = mendianinfo.area
				mendian.address = mendianinfo.address
				mendian.distance = mendianinfo.distance
				locationCache.mendian = mendian
				app.setLocationCache('mendian',mendian,that.cacheExpireTime)
				that.isshowmendianmodal = false
				that.getdata()
			},
			//门店模式end
		}
	}
</script>
<style>
.dp-header-location{display: flex;align-items: center;}
.dp-header-mendian-box{padding: 14rpx 20rpx;}
.dp-header-mendian{display: flex;align-items: center;}
.dp-header-mendian-address{font-size: 24rpx;color: #999;height: 30rpx;flex-wrap: nowrap;margin-top: 6rpx;line-height: 30rpx;align-self: flex-start;padding-left: 6rpx;}
.dp-header-mendian-address .f1{max-width: 80%;text-overflow: ellipsis;white-space:nowrap;overflow: hidden;}
.dp-header-mendian-address .f2{flex-shrink: 0;padding-left: 16rpx;}
.dp-header-mendian .header-icon{width: 28rpx;height: 28rpx;}
.dp-header-location-search {
	width: 150px;
	height: 32px;
	background: #f2f2f2;
	border-radius: 16px;
	color: #232323;
	flex: 1;
	display: flex;
	align-items: center;
	justify-content: space-between;
	font-size: 14px
}
.dp-location-search{flex:1;background: #FFFFFF;height: 70rpx;border-radius: 10rpx;padding: 0 20rpx;display: flex; align-items: center;margin-left: 6rpx;}
.dp-header-location-search input{flex: 1;display: inline-block;font-size: 24rpx;}
.dp-location-search-icon{width: 30rpx;height: 30rpx;}
.dp-header-location-search image {
	width: 14px;
	height: 15px;
	margin-right: 6px
}
.dp-header-address-widthfixed{max-width: 150rpx;overflow: hidden;text-overflow: ellipsis;white-space: nowrap;}
.dp-header-location-title{width: 160px;text-align: center;font-weight: bold;}
.dp-location-iconlist{display: flex;flex-shrink: 0;align-items: center;justify-content: flex-end;}
.dp-location-icon{width: 36rpx; height: 36rpx;margin-left: 2px;}
.dp-location-icon image{width:100%;height: 100%;}
.dp-header-icon{width: 34rpx;height: 34rpx;}
.dp-header-more{margin-left: 4px;}
.dp-header-picker .uni-data-tree-dialog{color: #333333;}

.dp-location-modal {
		position: fixed;
		height: 100%;
		width: 100%;
		top: 0;
		left: 0;
		align-items: flex-end;
		margin: 0;
		background: #f6f6f6;
		z-index: 1900000;
	}
	.dp-location-modal-mendian{background: rgba(0, 0, 0, 0.5);}

	.dp-location-modal-content {
		width: 100%;
		height: 100%;
		overflow-y: auto;
	}
.dp-header-nearby-box{background-color: #f6f6f6;}
.dp-header-nearby-search{padding:20rpx;display: flex;align-items: center;}
.dp-header-nearby-close image{width: 36rpx; height: 36rpx;}
.dp-header-nearby-input{background-color: #FFFFFF;border-radius: 50rpx;margin-left: 10rpx;height: 70rpx;line-height: 70rpx;flex: 1;display: flex;align-items: center;justify-content: space-between;padding-left: 40rpx;color: #222222;}
.dp-header-nearby-input .input{flex:1}
.dp-header-nearby-input .searchbtn{border: #f6f6f6 1rpx solid;height: 50rpx;line-height: 50rpx;width: 90rpx;margin: 6rpx 10rpx;border-radius: 50rpx;font-size: 24rpx;display: flex;align-items: center;justify-content: center;}
.dp-header-nearby-content{background: #FFFFFF;padding: 20rpx;color: #333;}
.dp-header-nearby-imgicon{width: 30rpx;height: 30rpx;margin-right: 10rpx;}
.dp-header-nearby-title{font-weight: bold;}
.dp-header-nearby-list{}
.dp-header-nearby-info{padding: 20rpx 0 20rpx 40rpx;line-height: 40rpx;border-bottom: 1rpx solid #f6f6f6;}
.dp-header-nearby-info:last-child{border: none;}
.dp-header-nearby-txt{font-size: 24rpx;color: #888;}
.dp-header-nearby-tip{color: #707070;font-weight: normal;font-size: 26rpx;}
.dp-header-nearby-all{padding-top: 20rpx;color: #707070;font-size: 26rpx;padding-left: 40rpx;}
.dp-header-nearby-all image{width: 28rpx; height: 28rpx;margin-left: 10rpx;}
.dp-header-location-bottom{background: #FFFFFF;position: fixed;bottom: 0;width: 100%;height: 90rpx;border-top: 1rpx solid #f6f6f6;padding-bottom: 8rpx;line-height: 90rpx;}
.dp-location-add-address{font-size: 42rpx;font-weight: bold;padding-right: 6rpx; line-height: 90rpx;}
.dp-header-nearby-body{padding-bottom: 110rpx;}
.dp-suggestion-box{background: #FFFFFF;padding: 20rpx;color: #333;}
.dp-suggestion-place{padding: 20rpx;border-bottom: 1rpx solid #f6f6f6;z-index: 9999;}
.dp-suggestion-place image{width: 40rpx;height: 40rpx;}
.dp-suggestion-place .s-title{font-size: 30rpx;}
.dp-suggestion-place .s-info{padding-top: 10rpx;font-size: 24rpx;padding-left: 40rpx;}
.dp-suggestion-place .s-area{flex-shrink: 0;padding-right: 8rpx;}
.dp-suggestion-place .s-address{color: #797979;}
.dp-header-location-box{font-size: 28rpx;display: flex;align-items: center;justify-content: space-between;}


/* 门店 */
.popup_mendian .popup__content{padding: 0 20rpx;}
.popup_mendian .popup__modal{min-height: auto;}
.popup_mendian .mendian-info{display: flex;align-items: center;width: 100%;background:#F6F6F6;padding: 20rpx; margin-bottom: 20rpx;border-radius: 6rpx;}
.popup_mendian .mendian-info .b1{background-color: #fbfbfb;}
.popup_mendian .mendian-info .b1 image{height: 100rpx;width:100rpx;border-radius: 6rpx;border: 1px solid #e8e8e8;}
.popup_mendian .mendian-info .b2{flex:1;line-height: 38rpx;margin-left: 20rpx;overflow: hidden;}
.popup_mendian .mendian-info .b2 .t1{padding-bottom: 10rpx;}
.popup_mendian .mendian-info .b2 .t2{font-size: 24rpx;color: #999;}
.popup_mendian .mendian-info .b3{display: flex;justify-content: flex-end;flex-shrink: 0;padding-left: 20rpx;}
.popup_mendian .mendian-info .b3 image{width: 40rpx;height: 40rpx;}
.popup_mendian .mendian-info .tag{padding:0 10rpx;margin-right: 10rpx;display: inline-block;font-size: 22rpx;border-radius: 8rpx;flex-shrink: 0;}
.popup_mendian .mendian-info .mendian-address{text-overflow: ellipsis;flex:1;width: 300rpx;white-space: nowrap;}
.popup_mendian .mendian-info .line{border-right: 1rpx solid #999;width: 10rpx;flex-shrink: 0;height: 16rpx;padding-left:10rpx;margin-right: 12rpx;}
.popup_mendian .mendian-info .mendian-distance{color: #3b3b3b;font-weight: 600;flex-shrink: 0;}
</style>
