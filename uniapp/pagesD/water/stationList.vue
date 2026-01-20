<template>
	<view>
		<!-- rgba(${t('color1rgb')},0.8) -->
		<view class="banner-view" :style="{backgroundImage:`url(${pre_url}/static/img/watherbg.png)`,backgroundColor:`#89c3f6`}"></view>
		<view class="position-view">
			<view class="top-position-view flex flex-bt">
				<view class='flex flex-y-center'>
					<view class="add-icon">
						<image :src="pre_url+'/static/img/water/address_icon.png'"></image>
					</view>
					<view class="address-text">{{locationCache.street?locationCache.street:'定位中'}}</view>
					<view class="xiala-icon flex flex-xy-center">
						<image :src="pre_url+'/static/img/water/jiantou_xiala.png'"></image>
					</view>
				</view>
			</view>
			<view class="pos-options-view list-view">
				<block v-for="(item,index) in datalist" :key="index">
					<view class="list-options-view flex flex-y-center flex-bt">
						<view class="flex-col left-view">
							<view class="title-text flex flex-y-center">{{item.location ? item.location : '净水机'}} <view class="juli-tisp" v-if="index == 0 && item.juli">距离最近</view></view>
							<view class="tisp-text">{{item.location ? item.location : '净水机'}}</view>
						</view>
						<view class="right-icon flex-col flex-xy-center" v-if="item.juli">
							<image class="dizhi-icon" :src="pre_url+'/static/img/water/dizhi.png'"></image>
							<view class="dizhi-text">{{item.juli ? item.juli : ''}}</view>
						</view>
					</view>
				</block>
			</view>
		</view>
    <nodata v-if="nodata"></nodata>
    <nomore v-if="nomore"></nomore>
    <loading v-if="loading"></loading>
    <dp-tabbar :opt="opt"></dp-tabbar>
    <popmsg ref="popmsg"></popmsg>
    <wxxieyi></wxxieyi>
	</view>
</template>

<script>
	var app = getApp();
	export default{
		data(){
			return{
				pre_url:app.globalData.pre_url,
        opt: {},
        loading: false,
        isload: false,
        nodata:false,
        nomore:false,
        pagenum: 1,
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
          street:'',
          showlevel:2
        },
        latitude:'',
        longitude:'',
        bid:0,
        datalist:[],
      }
    },
    onLoad: function(opt) {
      let that = this;
      that.opt = app.getopts(opt);
      that.bid = that.opt.bid || 0;
      that.checkLocation();

    },
    onPullDownRefresh: function () {
      this.getdata();
    },
    onReachBottom: function () {
      if (!this.nodata && !this.nomore) {
        this.pagenum = this.pagenum + 1;
        this.getdata(true);
      }
    },
		methods:{

      checkLocation(){
        var that = this
        var locationCache = app.getLocationCache();
        // #ifdef H5
        if(locationCache.address){
          that.locationCache.address = locationCache.address;
        }
        // #endif
        var loc_area_type = 0;
        var loc_range_type = 0;
        var loc_range = 10;
        app.getLocation(function(res) {
          that.latitude = res.latitude;
          that.longitude = res.longitude;
          that.getdata();
          app.post('ApiAddress/getAreaByLocation', {latitude:that.latitude,longitude:that.longitude}, function(res) {
            if(res.status==1){
              that.locationCache.loc_area_type = loc_area_type
              that.locationCache.loc_range_type = loc_range_type
              that.locationCache.loc_range = loc_range
              that.locationCache.latitude = that.latitude
              that.locationCache.longitude = that.longitude
              that.locationCache.street = res.street
              // that.locationCache.showlevel = that.showlevel
              if(loc_area_type==0){
                if(that.showlevel==2){
                  that.locationCache.address = res.city
                  that.locationCache.street = res.street
                  that.locationCache.area = res.province+','+res.city
                  if(that.locationCache.address == null){
                    that.locationCache.address = '北京市';
                  }
                }else{
                  that.locationCache.address = res.district
                  that.locationCache.area = res.province+','+res.city+','+res.district
                  that.locationCache.street = res.street
                }
                that.area = that.locationCache.area
                that.curent_address = that.locationCache.address
                app.setLocationCacheData(that.locationCache,that.cacheExpireTime)
                that.locationCache.street = res.street
              }else if(loc_area_type==1){
                that.locationCache.address = res.landmark
                that.locationCache.area = res.province+','+res.city+','+res.district
                that.area = that.locationCache.area
                that.curent_address = that.locationCache.address
                app.setLocationCacheData(that.locationCache,that.cacheExpireTime)
                that.locationCache.street = res.street
              }else{
                return;
              }
            }
          })
        },function(res){
          that.locationCache.address = '北京市';
          that.locationCache.street = '北京市';
        })
      },
      getdata: function() {
        var that = this;
        that.loading = true;
        app.post('ApiWaterHappyti/getStationList', {latitude:that.latitude,longitude:that.longitude,bid:that.bid,pagenum: that.pagenum}, function(res) {
          that.loading = false;
          that.isload = true;
          if (res.status == 0) {
            app.error(res.msg);
            return;
          }

          var data = res.data;
          if (that.pagenum == 1) {
            that.datalist = data;
            if (data.length == 0) {
              that.nodata = true;
            }
            that.loaded();
          }else{
            if (data.length == 0) {
              that.nomore = true;
            } else {
              var datalist = that.datalist;
              var newdata = datalist.concat(data);
              that.datalist = newdata;
            }
          }

          that.loaded();
        });
      },
		}
	}
</script>

<style>
	.banner-view{width: 100%;height: 400rpx;background-repeat: no-repeat;background-size: cover;background-position: 0rpx -235rpx;}
	.position-view{position: relative;left:50%;top:-320rpx;width: 96%;transform: translateX(-50%);height: auto;}
	.position-view .top-position-view {width: 100%;padding: 20rpx;}
	.position-view .top-position-view .address-text{font-size: 30rpx;color: #fff;font-weight: bold;margin: 0rpx 10rpx;}
	.position-view .top-position-view  .add-icon{width: 50rpx;height: 50rpx;}
	.position-view .top-position-view  .add-icon image{width: 100%;height: 100%;}
	.position-view .top-position-view  .xiala-icon{width: 24rpx;height: 24rpx;}
	.position-view .top-position-view  .xiala-icon image{width: 100%;height: 70%;}
	.position-view .pos-options-view{background: #fff;width: 100%;height: auto;padding: 30rpx;border-radius: 30rpx;overflow: hidden;}
	.list-options-view{width: 100%;border-bottom: 1px #EEEEEE solid;padding: 30rpx 0rpx;}
	.list-options-view .left-view{flex: 1;}
	.list-options-view .left-view .title-text{font-size: 30rpx;font-weight: bold;letter-spacing: 1rpx;color: #333333;width: 500rpx;white-space: nowrap;overflow: hidden;text-overflow: ellipsis;}
	.list-options-view .left-view .title-text .juli-tisp{background: rgba(204, 226, 252, 0.5);color: #0074FE;padding: 4rpx 8rpx;border-radius: 5rpx;font-size: 20rpx;font-weight: normal;margin-left: 10rpx;}
	.list-options-view .left-view .tisp-text{color: #999999;font-size: 12px;margin-top: 15rpx;width: 500rpx;white-space: nowrap;overflow: hidden;text-overflow: ellipsis;}
	.list-options-view .right-icon .dizhi-icon{width: 70rpx;height: 70rpx;}
	.list-options-view .right-icon .dizhi-text{color: #566B81;font-size: 24rpx;margin-top: 10rpx;}
</style>