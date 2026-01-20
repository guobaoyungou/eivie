<template>
	<view>
		<block v-if="isload">
			<view class="screen-view">
				<view class="screen-view-left">
					<view class='screen-options'  :style="order!='suiji'?'background:rgba('+t('color1rgb')+',0.9);color:#fff':''"  @click="alertClick(1)">{{ordername}}<image :src="pre_url+'/static/img/arrowdown.png'"></image></view>
					<view class='screen-options' :style="wname1!='全城'?'background:rgba('+t('color1rgb')+',0.9);color:#fff':''" @click="alertClick(2)">{{wname1}}<image :src="pre_url+'/static/img/arrowdown.png'"></image></view>
					<view class='screen-options' :style="stars.length>0?'background:rgba('+t('color1rgb')+',0.9);color:#fff':''"  @click="alertClick(3)">{{starname}}{{stars.length?stars.length:''}}<image :src="pre_url+'/static/img/arrowdown.png'"></image></view>
					<view class='screen-options' :style="emptyroom!='状态'?'background:rgba('+t('color1rgb')+',0.9);color:#fff':''" @click="alertClick(5)">{{emptyroom}}<image :src="pre_url+'/static/img/arrowdown.png'"></image></view>
				</view>
				<view class="right-screen"  @click="alertClick(4)" :style="cateids.length>0?'color:'+t('color1'):''">
					筛选{{cateids.length?cateids.length:''}}<image :src="`${pre_url}/static/img/hotel/screenicon.png`"></image>
				</view>
			</view>
			<view v-if="alertState" @click="alertState=''" class="alert"></view>
			<view v-if="alertState=='1'" class="alert_module">
				<radio-group>
					<label class="sort flex-y-center flex-bt" @tap="sortChange" data-value="suiji" data-name="智能排序">
						<view>智能排序</view>
						<radio color="#fac428" class="sort_icon" :checked="order=='suiji'?true:false" />
					</label>
					<label class="sort flex-y-center flex-bt" @tap="sortChange" data-value="juli" data-name="距离优先">
						<view>距离优先</view>
						<radio color="#fac428" class="sort_icon"  :checked="order=='juli'?true:false"/>
					</label>
					<label class="sort flex-y-center flex-bt" @tap="sortChange" data-value="comment_haopercent" data-name="评价最高">
						<view>评价最高</view>
						<radio color="#fac428" class="sort_icon"  :checked="order=='comment_haopercent'?true:false"/>
					</label>
					<label class="sort flex-y-center flex-bt" @tap="sortChange" data-value="totalnum" data-name="销量优先">
						<view>销量优先</view>
						<radio color="#fac428" class="sort_icon" :checked="order=='totalnum'?true:false" />
					</label>
					<label class="sort flex-y-center flex-bt" @tap="sortChange" data-value="priceasc" data-name="低价优先">
						<view>低价优先</view>
						<radio color="#fac428" class="sort_icon" :checked="order=='priceasc'?true:false" />
					</label>
					<label class="sort flex-y-center flex-bt" @tap="sortChange" data-value="pricedesc" data-name="高价优先">
						<view>高价优先</view>
						<radio color="#fac428" class="sort_icon"  :checked="order=='pricedesc'?true:false"/>
					</label>
					<!--<label class="sort flex-y-center flex-bt">
						<view>手法优先</view>
						<radio color="#fac428" class="sort_icon" />
					</label>-->
				</radio-group>
			</view>
			<view v-if="alertState=='2'" class="alert_module">
				<radio-group>
					<label class="sort flex-y-center flex-bt"  @tap="whereChange" data-value='all' data-name="全城">
						<view>全城</view>
						<radio color="#fac428" class="sort_icon" :checked="wherevalue=='all'?true:false"/>
					</label>
					<label class="sort flex-y-center flex-bt" @tap="whereChange" data-value='1'  data-name="1km">
						<view>1km</view>
						<radio color="#fac428" class="sort_icon" :checked="wherevalue=='1'?true:false"/>
					</label>
					<label class="sort flex-y-center flex-bt" @tap="whereChange" data-value='3'  data-name="3km">
						<view>3km</view>
						<radio color="#fac428" class="sort_icon" :checked="wherevalue=='3'?true:false" />
					</label>
					<label class="sort flex-y-center flex-bt" @tap="whereChange" data-value='5'  data-name="5km">
						<view>5km</view>
						<radio color="#fac428" class="sort_icon" :checked="wherevalue=='5'?true:false"/>
					</label>
					<label class="sort flex-y-center flex-bt" @tap="whereChange" data-value='10'  data-name="10km">
						<view>10km</view>
						<radio color="#fac428" class="sort_icon" :checked="wherevalue=='10'?true:false"/>
					</label>
				</radio-group>
			</view>
			
			<view v-if="alertState=='3'" class="alert_module">
					<view class="alert_title">星级</view>
					<view class="flex flex-wp" >
						<block v-for="(item,index) in starlist">
							<view  :class="'alert_tag '" :style="inArray(index,stars) ? 'background:'+t('color1')+';border:0;color:#fff' : ''"  :data-value="index"  @tap="startypeChange">{{item}}</view>
						</block>
					</view>
					<view class="alert_opt flex-y-center flex-bt">
						<view class="alert_btn flex-xy-center" @tap="cancel">重置</view>
						<view @tap="submitSearch" class="alert_btn flex-xy-center" :style="'background:rgba('+t('color1rgb')+',0.8);color:#FFF'">确定</view>
					</view>
			</view>
			
			<view v-if="alertState=='4'" class="alert_module">
					<view class="alert_title">{{text['酒店']}}类型</view>
					<view class="flex flex-wp" >
						<block v-for="(item,index) in catelist">
							<view  :class="'alert_tag '" :style="inArray(item.id,cateids) ? 'background:'+t('color1')+';border:0;color:#fff' : ''"  :data-id="item.id"  @tap="catetypeChange">{{item.name}}</view>
						</block>
					</view>
					<view class="alert_opt flex-y-center flex-bt">
						<view class="alert_btn flex-xy-center" @tap="cancel">重置</view>
						<view @tap="submitSearch" class="alert_btn flex-xy-center" :style="'background:rgba('+t('color1rgb')+',0.8);color:#FFF'">确定</view>
					</view>
			</view>
			
			<view v-if="alertState=='5'" class="alert_module">
				<radio-group>
					<label class="sort flex-y-center flex-bt" @tap="emptyChange" data-value="0" data-name="状态">
						<view>全部</view>
						<radio color="#fac428" class="sort_icon" :checked="emptystatus=='0'?true:false" />
					</label>
					<label class="sort flex-y-center flex-bt" @tap="emptyChange" data-value="1" data-name="空房">
						<view>空房</view>
						<radio color="#fac428" class="sort_icon" :checked="emptystatus=='1'?true:false" />
					</label>
					<label class="sort flex-y-center flex-bt" @tap="emptyChange" data-value="2" data-name="已满">
						<view>已满</view>
						<radio color="#fac428" class="sort_icon"  :checked="emptystatus=='2'?true:false"/>
					</label>
				</radio-group>
			</view>
			
			<!--  -->
			<view class="hotels-list">
				<block v-for="(item,index) in datalist" >
					<view class="hotels-options" @tap="goto" :data-url="'hoteldetails?id='+item.id">
						<view class="hotel-img">
							<image :src="item.pic"></image>
						</view>
						<view class="hotel-info">
							<view class="hotel-title">{{item.name}}</view>
							<view class="hotel-address">{{item.address}}</view>
							<view class="hotel-characteristic">
								<block v-for="(items,indexs) in item.tag">
									<view class="characteristic-options" :style="'background:rgba('+t('color1rgb')+',0.05);color:'+tColor('color1')">{{items}}</view>
								</block>
							</view>
							<view class="hotel-but-view">
								<view class="make-info">
									<view class="hotel-price" :style="{color:t('color1')}" v-if="item.min_daymoney">
										<view class="hotel-price-num">{{item.min_daymoney}}{{moneyunit}}</view>
										<view>/晚起</view>
									</view>
									<view class="hotel-price" :style="{color:t('color1')}" v-else>
										<view>￥</view>
										<view class="hotel-price-num">{{item.min_price}}</view>
										<view>起</view>
									</view>
									<view class="hotel-text">{{item.sales}}人预定</view>
								</view>
								<view class="hotel-make"  :style="'background:rgba('+t('color1rgb')+',0.8);color:#FFF'">预约</view>
							</view>
						</view>
					</view>
				</block>
			</view>
			
			
			<nodata v-if="nodata"></nodata>
			<nomore v-if="nomore"></nomore>
			<loading v-if="loading"></loading>
		</block>
	</view>
</template>

<script>
	var app = getApp();
	export default {
		data(){
			return{
				isload: false,
				pre_url: app.globalData.pre_url,
				catelist:[],
				datalist:[],
				set:[],
				text:[],
				alertState: '',
				order:'suiji',
				ordername:"智能排序",
				wname1:'全城',
				starname:'星级',
				starlist:[],
				startype:0,
				startypename:'',
				stars:[],
				wherevalue:'all',
				latitude: '',
				longitude: '',
				loading:false,
				nodata:false,
				nomore: false,
				pagenum: 1,
				text:[],
				cateids:[],
				keyword:'',
				starttime:'',
				endtime:'',
				emptyroom:'状态',
				moneyunit:'元'
			}
		},
		onLoad(opt) {
			var that=this
			this.opt = app.getopts(opt);
			this.keyword = this.opt.keyword
			this.cateid = this.opt.cateid
			var newcateid = [];
			if( this.opt.cateid){
					newcateid.push(this.opt.cateid);
					that.cateids = newcateid
			}
			var cachelongitude = app.getCache('user_current_longitude');
			var cachelatitude = app.getCache('user_current_latitude');
			if(cachelongitude && cachelatitude){
				this.latitude = cachelatitude
				this.longitude = cachelongitude
			}else{
				var that = this;
				app.getLocation(function(res) {
					that.latitude = res.latitude;
					that.longitude = res.longitude;
					app.setCache('user_current_latitude',res.latitude)
					app.setCache('user_current_longitude',res.longitude)
				});
			}
			this.getdata();
		},
		onPullDownRefresh: function () {
			this.getdatalist();
		},
		onReachBottom: function () {
			var that=this
			if (!this.nodata && !this.nomore) {
				this.pagenum = this.pagenum + 1;
				this.getdatalist(true);
			}
		
		},
		methods:{
			
			getdata:function(e){
				var that=this
				app.post('ApiHotel/getsysset', {}, function (res) {
					//	console.log(res);
						if(res.status==1){
							that.set=res.set
							that.catelist = res.catelist
							that.text = res.text
							that.starlist = res.starlist

							var starttime = app.getCache('startTime');
							var endtime = app.getCache('endTime');
							if(!starttime){
									app.setCache('startTime',res.startday,30);
									starttime = app.getCache('startTime');
							}
							if(!endtime){
									app.setCache('endTime',res.endday,30);
									endtime = app.getCache('endTime');
							}
							that.starttime = starttime;
							that.endtime = endtime;
							that.moneyunit = res.moneyunit
							that.loaded();
							that.getdatalist()
						}
				})
			},
			getdatalist: function (loadmore) {
				if(!loadmore){
					this.pagenum = 1;
					this.datalist = [];
				}
				var that = this;
				var pagenum = that.pagenum;
				var bid = that.opt.bid ? that.opt.bid : '';
				var order = that.order;
			  var keyword = that.keyword;
				var field = that.field; 
				that.loading = true;
				that.nodata = false;
				that.nomore = false;
				var latitude = that.latitude;
				var longitude = that.longitude;
		
				
				app.post('ApiHotel/gethotels', {pagenum: pagenum,keyword: keyword,field: field,order: order,bid:bid,longitude: longitude,latitude: latitude,juli:that.wherevalue,stars:that.stars,cateids:that.cateids,starttime:that.starttime,endtime:that.endtime,emptystatus:that.emptystatus,type:'list'}, function (res) { 
					that.loading = false;
					var data = res.data;
					if (pagenum == 1) {
			      that.datalist = data;
			      if (data.length == 0) {
			        that.nodata = true;
			      }
			    }else{
			      if (data.length == 0) {
			        that.nomore = true;
			      } else {
			        var datalist = that.datalist;
			        var newdata = datalist.concat(data);
			        that.datalist = newdata;
			      }
			    }

				});
			},
			tabChange(index){
				this.tabindex = index;
			},
			alertClick(e) {
				var that=this
				console.log(e)
				if (that.alertState == e) {
					that.alertState = '';
				}else{
							that.alertState = e;
				}
			},
			cancel:function(e){
				var that=this
				that.stars=''
				that.cateids=''
			},
			submitSearch:function(){
				var that=this
				this.alertState = false;
				that.getdatalist()
			},
			sortChange:function(e){
				var that=this
				var value=e.currentTarget.dataset.value
				var ordername=e.currentTarget.dataset.name
				that.order = value
				that.ordername=ordername
				that.getdatalist()
				this.alertState=false
			},
			emptyChange:function(e){
				var that=this
				var value=e.currentTarget.dataset.value
				var emptyroom=e.currentTarget.dataset.name
				that.emptystatus = value
				that.emptyroom=emptyroom
				that.getdatalist()
				this.alertState=false
			},
			whereChange:function(e){
				var that=this
				var value=e.currentTarget.dataset.value
				var wname=e.currentTarget.dataset.name
				that.wherevalue = value
				that.wname1=wname
				that.getdatalist()
				this.alertState=false
			},
			startypeChange:function(e){
				var starlist = this.starlist;
				var starindex = e.currentTarget.dataset.value;
				var stars = this.stars;
				var newstars = [];
				var ischecked = false;
				for(var i in stars){
					if(stars[i] != starindex){
						newstars.push(stars[i]);
					}else{
						ischecked = true;
					}
				}
				if(ischecked==false){
					newstars.push(starindex);
				}
				this.stars = newstars;
			},
			catetypeChange:function(e){
				var catelist = this.catelist;
				var cid = e.currentTarget.dataset.id;
				var cateids = this.cateids;
				var newcates = [];
				var ischecked = false;
				for(var i in cateids){
					if(cateids[i] != cid){
						newcates.push(cateids[i]);
					}else{
						ischecked = true;
					}
				}
				if(ischecked==false){
					newcates.push(cid);
				}
				this.cateids = newcates;
			},
			tColor(text){
				let that = this;
				if(text=='color1'){
					if(app.globalData.initdata.color1 == undefined){
						let timer = setInterval(() => {
							that.tColor('color1')
						},1000)
						clearInterval(timer)
					}else{
						return app.globalData.initdata.color1;
					}
				}else if(text=='color2'){
					return app.globalData.initdata.color2;
				}else if(text=='color1rgb'){
					if(app.globalData.initdata.color1rgb == undefined){
						let timer = setInterval(() => {
							that.tColor('color1rgb')
						},1000)
						clearInterval(timer)
					}else{
						var color1rgb = app.globalData.initdata.color1rgb;
						return color1rgb['red']+','+color1rgb['green']+','+color1rgb['blue'];
					}
				}else if(text=='color2rgb'){
					var color2rgb = app.globalData.initdata.color2rgb;
					return color2rgb['red']+','+color2rgb['green']+','+color2rgb['blue'];
				}else{
					return app.globalData.initdata.textset[text] || text;
				}
			},
		}
	}
</script>

<style>
	.screen-view{width: 100%;display: flex;align-items: center;justify-content: space-between;background: #fff;padding: 30rpx 30rpx;position: sticky;top: 0;}
	.screen-view .screen-view-left{flex:1;display: flex;align-items: center;justify-content: flex-start;margin-right: 30rpx;}
	.screen-view .screen-view-left .screen-options{display: flex;align-items: center;justify-content: space-between;background: #F4F4F4;border-radius: 6px;color: #212121;
	font-size: 24rpx;padding: 12rpx 9rpx;margin-right: 10rpx;}
	.screen-view .screen-view-left .screen-options image{width: 16rpx;height: 16rpx;margin-left: 16rpx;}
	.screen-view .right-screen{display: flex;align-items: center;color: #212121;font-size: 24rpx;}
	.screen-view .right-screen image{width: 24rpx;height: 24rpx;margin-left: 20rpx;}
	/*  */
	.hotels-list{width: 96%;margin: 20rpx auto 0rpx;display: flex;align-items: center;justify-content: space-between;flex-direction:column;}
	.hotels-list .hotels-options{width: 100%;padding: 20rpx;display: flex;align-items: center;justify-content: space-between;border-radius: 8px;background: #FFFFFF;margin-bottom: 20rpx;}
	.hotels-list .hotels-options .hotel-img{width: 98px;height: 130px;border-radius: 15rpx;overflow: hidden;}
	.hotels-list .hotels-options .hotel-img image{width: 100%;height: 100%;}
	.hotels-list .hotels-options .hotel-info{flex: 1;padding-left: 20rpx;}
	.hotels-list .hotels-options .hotel-info .hotel-title{width: 100%;color: #343536;font-size: 30rpx;}
	.hotels-list .hotels-options .hotel-info .hotel-address{width: 100%;color: #7B8085;font-size: 24rpx;margin-top: 7rpx;}
	.hotels-list .hotels-options .hotel-info .hotel-characteristic{width: 100%;display: flex; flex-wrap: wrap; align-items: center;justify-content: flex-start;margin-top: 7rpx;}
	.hotels-list .hotels-options .hotel-info .hotel-characteristic .characteristic-options{font-size: 20rpx;padding: 7rpx 13rpx;flex-wrap: wrap;margin-right: 20rpx; margin-top: 6rpx;}
	.hotels-list .hotels-options .hotel-info .hotel-but-view{width: 100%;display: flex;align-items: center;justify-content: space-between;margin-top: 25rpx;}
	.hotels-list .hotels-options .hotel-info .hotel-but-view .make-info{display: flex;flex-direction: column;justify-content: flex-start;}
	.hotels-options .hotel-info .hotel-but-view .make-info .hotel-price{display: flex;align-items: center;justify-content: flex-start;font-size: 24rpx;}
	.hotel-info .hotel-but-view .make-info .hotel-price .hotel-price-num{font-size: 40rpx;font-weight: bold;padding: 0rpx 3rpx;}
	.hotels-options .hotel-info .hotel-but-view .make-info .hotel-text{color: #7B8085;font-size: 24rpx;margin-top: 15rpx;}
	.hotels-list .hotels-options .hotel-info .hotel-but-view .hotel-make{background: linear-gradient(90deg, #06D470 0%, #06D4B9 100%);width: 72px;height: 32px;line-height: 32px;
	text-align: center;border-radius: 36px;color: #FFFFFF;font-size: 28rpx;font-weight: bold;}
	
	
	.alert {	position: fixed;	height: 100%;	width: 100%;	top: 180rpx;	left: 0;	z-index: 5;	background: rgba(0, 0, 0, 0.5);}
	.alert_module {	position: absolute;width: 100%;	box-sizing: border-box;	padding: 0 50rpx 50rpx 50rpx;	top: 110rpx;	background: #fff;	border-radius: 0 0 30rpx 30rpx;z-index: 10;}
	.alert_title {font-size: 26rpx;	color: #333;	padding: 30rpx 0 0 0;	font-weight: bold; margin-bottom: 30rpx;}
	.alert_tag {	margin: 15rpx 15rpx 0 0;		background: #f5f5f5;	color: #333;	font-size: 24rpx;	padding: 10rpx 20rpx;}
	.alert_cut {width: 25rpx;height: 4rpx;margin: 0 10rpx;background: #f0f0f0;}
	.alert_input { width:30%;	text-align: center;margin: 15rpx 15rpx 15rpx 0;	height: 60rpx; line-height:60rpx}
	.alert_active {background: #fac428;	}
	.alert_opt {	margin-top: 50rpx;	}
	.alert_btn {	width: 40%;	height: 70rpx;	color: #333;	font-size: 26rpx;	border-radius: 100rpx;	background: #f5f5f5;}
	.alert_btn:last-child {	width: 330rpx;	height: 70rpx;color: #333;font-size: 26rpx;border-radius: 100rpx;background: #fac428;}
	.sort {padding: 20rpx 0;font-size: 26rpx;color: #333;}
	.sort_icon {color: #fac428;transform: scale(0.8);}
	.date {	padding: 20rpx;}
	.data_item {font-size: 26rpx;color: #999;text-align: center;}
	.data_active {color: #333;font-weight: bold;}
	.data_tag {	height: 6rpx;	width: 50rpx;	background: #fac428;margin: 5rpx auto 0 auto;}
	.time {margin: 15rpx 15rpx 0 0;	border-radius: 100rpx;color: #333;font-size: 24rpx;		padding: 10rpx 45rpx;	border: 1px solid #f0f0f0;}
	.time_active {background: #fac428;border: 1px solid #fac428;}
	
	
</style>