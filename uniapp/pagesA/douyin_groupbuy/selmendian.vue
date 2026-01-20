<template>
  <view>
    <block v-if="isload">
      <view style="width: 100%;background-color: #fff;padding: 10rpx 0;">
        <view class="wc">
          <view class="head_mdname">
            <!-- <image src="/static/xinxixie/local.png" class="mendian_local"></image> -->
            当前门店：{{mdname}}
          </view>
          <view class="head_search">
            <image src="/static/xinxixie/search.png" class="search_img"></image>
            <input @input="searchdata" placeholder="搜索门店" placeholder-style="line-height: 80rpx;color:#8282A7" style="height: 80rpx;line-height: 80rpx;width: 100%;"/>
          </view>
        </view>
      </view>

      <view style="margin-top: 20rpx;">
        <block v-for="(item, index) in datalist" >
          <view @tap="selit" :data-mdid="item.id" :data-mdname="item.name" :data-mdlng="item.longitude" :data-mdlat="item.latitude" class="list_wc">
            <view class="list_img">
              <image :src="item.pic" style="width: 200rpx;height: 200rpx;"></image>
            </view>
            <view style="width: 440rpx;">
              <view class="list_title">
                {{item.name}}
              </view>
              <view class="list_local">
                <image src="/static/xinxixie/local2.png" class="local_img"></image>
                {{item.area}}{{item.address}}
              </view>
              
              <view v-if="item.tel" :data-phone="item.tel?item.tel:''" class="list_local" style="white-space: nowrap;text-overflow: ellipsis;overflow: hidden;">
                <image src="/static/xinxixie/tel2.png" class="local_img"></image>
                {{item.tel}}
              </view>
              <view style="margin-top:10rpx;font-size: 24rpx;">
                {{item.len}}
              </view>
              <view  @tap.stop="selit" :data-mdid="item.id" :data-mdname="item.name" :data-mdlng="item.longitude" :data-mdlat="item.latitude" style="width: 150rpx;line-height: 70rpx;text-align: center;color: #333;border-radius: 70rpx;position: absolute;right: 20rpx;bottom: 40rpx;border:2rpx solid #eee">
                选择
              </view>
            </view>
          </view>
        </block>
        <nomore v-if="nomore"></nomore>
        <nodata v-if="nodata"></nodata>
      </view>
    </block>
    <loading v-if="loading"></loading>
    <dp-tabbar :opt="opt"></dp-tabbar>
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
      pre_url:app.globalData.pre_url,
      nomore: false,
      nodata:false,
      pagenum: 1,
      
      keyword:'',
      datalist:'',
      bid:0,
      mdid:0,
      mdname    : '',
      latitude  : '',
      longitude : '',
      
    };
  },
  onLoad: function (opt) {
    var that = this;
    var opt  = app.getopts(opt);
    that.opt = opt;
    that.bid = opt.bid || 0;
    
    var mdname =  app.getCache('mdname');
    if(mdname){
      that.mdname =  mdname
    }else{
      that.mdname = '无';
    }
    var latitude =  app.getCache('latitude');
    if(latitude){
      that.latitude   = latitude;
    }
    var longitude = app.getCache('longitude');
    if(longitude){
      that.longitude = longitude;
    }
		this.getlocal(latitude,longitude);
  },
	onPullDownRefresh: function () {
		
	},
  onReachBottom: function () {
    if (!this.nodata && !this.nomore) {
      this.pagenum = this.pagenum + 1;
      this.getdata(true);
    }
  },
  methods: {
    getlocal:function(latitude,longitude){
      var that = this;
      if (!latitude || !longitude) {
      	app.getLocation(function(res) {
      		that.latitude  = res.latitude;
      		that.longitude = res.longitude;
          app.setCache('latitude', res.latitude,60);
          app.setCache('longitude', res.longitude,60);
      		that.getdata();
      	},function(res){
      		console.error(res);
      	});
      }else{
        that.getdata();
      }
    },
		getdata: function (loadmore = false) {
			if(!loadmore){
				this.pagenum = 1;
				this.datalist = [];
			}
		  var that = this;
		  var pagenum = that.pagenum;
			that.nodata = false;
			that.nomore = false;
			that.loading = true;
		  app.post('ApiDouyinGroupbuy/get_mendian', {bid:that.bid,pagenum: pagenum,latitude:that.latitude,longitude:that.longitude,keyword:that.keyword}, function (res) {
				that.loading = false;
        if(res.status == 1){
          var data = res.datalist;
          if (pagenum == 1) {
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
        } else {
        	if (res.msg) {
        		app.alert(res.msg);
        	}else {
        		app.alert('您无查看权限');
        	}
        }
		  });
		},
    searchdata:function(e){
      var that = this;
      that.keyword = e.detail.value;
      that.getdata();
    },
    selit:function(e){
      var that = this;
      var mdid    = e.currentTarget.dataset.mdid;
      app.setCache('mdid', mdid,60);
      that.mdid   = mdid;
      
      var mdname  = e.currentTarget.dataset.mdname;
      app.setCache('mdname', mdname,60);
      that.mdname = mdname;
      app.goback()
    },
    callphone:function(e) {
    	var phone = e.currentTarget.dataset.phone;
      if(phone){
        uni.makePhoneCall({
        	phoneNumber: phone,
        	fail: function () {
        	}
        });
      }
    },
  }
};
</script>

<style>
  .list_wc{width: 100%;background-color: #fff;padding: 10rpx 20rpx;margin-bottom: 2rpx;position: relative;line-height: 80rpx;display: flex;}
  .wc{width: 710rpx;margin: 0 auto;background-color: #fff;border-radius: 8rpx;padding: 20rpx;}
  .head_mdname{line-height: 40rpx;text-overflow: ellipsis;white-space: nowrap;overflow: hidden;margin-bottom: 20rpx;}
  .mendian_address{line-height: 50rpx;white-space: pre-wrap;overflow: hidden;color: #6E728B;}
  .mendian_local{width: 24rpx;height: 26rpx;float: left;margin-top:6rpx;margin-right: 10rpx;}
  .head_search{width: 100%;height: 80rpx;line-height: 80rpx;border-radius: 80rpx;background: #F5F7F9;padding:0 30rpx;overflow: hidden;display: flex;}
  .search_img{width: 32rpx;height: 32rpx;float: left;margin-top: 24rpx;margin-right: 10rpx;}
  .list_img{width: 200rpx;height: 200rpx;border-radius: 12rpx;overflow: hidden;margin-right: 20rpx;}
  .list_title{line-height: 40rpx;border-bottom: 10rpx;color: #3A4463;font-size: 30rpx;font-weight: bold;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:2;overflow:hidden;}
  .list_local{overflow: hidden;line-height: 40rpx;white-space: pre-wrap;font-size: 24rpx;color: #3A4463;;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:2;overflow:hidden}
  .local_img{width: 20rpx;height: 20rpx;float: left;margin-top:10rpx;margin-right: 10rpx;}
</style>