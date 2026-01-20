<template>
<view class="dp-search" :style="{
	backgroundColor:params.bgcolor,
	margin:(params.margin_y*2.2)+'rpx '+(params.margin_x*2.2)+'rpx',
	padding:(params.padding_y*2.2)+'rpx '+(params.padding_x*2.2)+'rpx'
}">
    <view style="display: flex;background: #fff;">
        <view  style="display: flex;align-items: center;justify-content: center;padding: 0 20rpx;">
            <!--搜索列表s-->
						<uni-data-picker :localdata="arealist" popup-title="地区" @change="areachange"  :placeholder="'地区'">
							<view :class="showarea?'':'hui'" style="display: flex;align-items: center;">
							<view style="flex-shrink: 0;">{{showarea?showarea:'地区'}}</view>
								<image style="width: 25rpx;flex-shrink: 0;margin-left: 10rpx;" mode="widthFix" :src="pre_url+'/static/img/hdsanjiao.png'"></image>
							</view>
            </uni-data-picker>
            <!--搜索列表e-->
        </view>
        <view class="dp-search-search flex1" :style="{borderColor:params.bordercolor,borderRadius:params.borderradius+'px'}">
        	<view class="dp-search-search-f1" :style="{backgroundImage:`url(${pre_url}/static/img/search_ico.png)`}"></view>
        	<view class="dp-search-search-f2">
        		<input class="dp-search-search-input" @confirm="searchgoto" @input="inputKeyword" :data-url="data_hrefurl" name="keyword" :placeholder="data_placeholder?data_placeholder:params.placeholder|| '输入关键字进行搜索s'" placeholder-style="color:#aaa;font-size:28rpx" :style="params.color?'color:'+params.color:''" />
        	</view>
        	<view class="dp-search-search-f3" v-if="params.image_search==1" @tap="goto" :data-url="data_hrefurl" :style="'background-image:url('+pre_url+'/static/img/camera.png)'"></view>
        </view>
        <view v-if="params.btn==1">
            <view  @tap="searchgoto" :data-url="data_hrefurl" style="width: 100rpx;text-align: center;line-height:72rpx;">搜索</view>
        </view>
    </view>
</view>
</template>
<script>
	var app =getApp();
	export default {
		data(){
			return {
				pre_url:getApp().globalData.pre_url,
					arealist:[],
					data_index:0,
					area_name:'',//类型名称
					showarea:'',//展示的区域信息，最小范围
					data_placeholder:'',//搜索提示
					data_hrefurl:'',
					keyword:'',
					showlevel:3
			}
		},
		props: {
			params: {},
			data: {}
		},
		mounted:function(){
				var that = this;
				that.showlevel = that.params.showlevel || 3;
				that.data_hrefurl     = that.params.hrefurl;
				
				//当前位置获取
				var cachearea = app.getCache('user_current_area');
				var cachearea_show = app.getCache('user_current_area_show');
				if(!cachearea || cachearea==-1){
					//初始化当前城市
					app.getLocation(function(res){
						var longitude = res.longitude;
						var latitude = res.latitude;
						app.get('ApiAddress/getAreaByLocation',{longitude:longitude,latitude:latitude},function(data){
							console.log('---location_area---')
							console.log(data)
							var area = [];
							var showarea = '';
							if(data.status==1){
								if(data.province){
									area.push(data.province)
									showarea = data.provice
								}
								if(that.showlevel>1 && data.city){
									area.push(data.city)
									showarea = data.city
								}
								if(that.showlevel>2 && data.district){
									area.push(data.district)
									showarea = data.district
								}
								that.area_name = area.join(',')
								that.showarea = showarea
								//全局缓存
								app.setCache('user_current_latitude',latitude)
								app.setCache('user_current_longitude',longitude)
								app.setCache('user_current_area',that.area_name)
								app.setCache('user_current_area_show',that.showarea)
							}
						});
					});
				}else{
					that.area_name = cachearea
					that.showarea = cachearea_show
				}
				//地区加载
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
		},
		methods:{
			searchgoto:function(e){
				var that = this;
				var keyword = that.keyword;
				var url = e.currentTarget.dataset.url;
				if (url.indexOf('?') > 0) {
					url += '&keyword='+keyword;
				}else{
					url += '?keyword='+keyword;
				}
				//如果需要定位检索，须在对应的页面，获取缓存值app.getCache('user_current_area')
				var opentype = e.currentTarget.dataset.opentype
				app.goto(url,opentype);
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
				that.area_name = area_name.join(',')
				that.showarea = showarea
				//全局缓存
				app.setCache('user_current_area',that.area_name)
				app.setCache('user_current_area_show',that.showarea)
			},
			inputKeyword:function(e){
					var that = this;
					that.keyword = e.detail.value;
			}
		}
	}
</script>
<style>
.dp-search {padding:20rpx;height: auto; position: relative;}
.dp-search-search {height:72rpx;background: #fff;border: 1px solid #c0c0c0;border-radius: 6rpx;overflow: hidden;display:flex}
.dp-search-search-f1 {height:72rpx;width:72rpx;color: #666;border: 0px;padding: 0px;margin: 0px;background-size:30rpx;background-position: center;background-repeat: no-repeat;}
.dp-search-search-f2{height: 72rpx;flex:1}
.dp-search-search-f3 {height:72rpx;width:72rpx;color: #666;border: 0px;padding: 0px;margin: 0px;background-position: center;background-repeat: no-repeat; background-size:40rpx;}
.dp-search-search-input {height:72rpx;width: 100%;border: 0px;padding: 0px;margin: 0px;outline: none;color: #666;}
.dp-search .hui{color: #aaa;}
</style>
