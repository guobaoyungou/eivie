<template>
	<view style="height: 100%;background-color: #fff;">
		<block v-if="isload">
      <view style="width: 750rpx;height: 100%;">
        <view class="topsearch flex-y-center">
        	<view class="f1 flex-y-center">
        		<image class="img" :src="pre_url+'/static/img/search_ico.png'"></image>
        		<input :value="keyword" placeholder="搜索城市名称或拼音" placeholder-style="font-size:24rpx;color:#C2C2C2" @confirm="searchConfirm" @input="searchChange" @focus="searchFocus"></input>
        		<view v-if="searchshow" class="camera" @tap="closeSearch" :style="'background-image:url('+pre_url+'/static/img/ico-del.png)'"></view>
        	</view>
        </view>
        <view style="width: 100%;height:100rpx ;"></view>
        <view style="width: 100%;height:calc(100% - 100rpx) ;">
          <scroll-view v-if="letterlist && letterlist.length > 0" :scrollIntoView="intoviewid" :scrollWithAnimation="animation" scroll-y="true" style="width: 100%;height: 100%;" >
          
            <view id="letterpos" style="line-height: 80rpx;font-size:28rpx ;">
              <view class="title" style="display: flex;justify-content: space-between;">
                <view>当前城市</view>
                <view @tap="checkLocation" :style="'color:'+t('color1')">重新定位</view>
              </view>
              <view v-if="poslist && poslist.length > 0" style="width: 100%;padding: 0 20rpx;">
                  <view v-for="(item, index) in poslist" :key="index" @tap="selit" :data-name="item.name" :data-code="item.code" class="hotname" style="background-color: #af1e24;color: #fff;border:0">
                      {{item.name}}
                  </view>
              </view>
              <!-- <view v-else  style="width: 100%;padding: 0 20rpx;">
                  <view class="hotname" style="background-color: #af1e24;color: #fff;border:0">{{posdefault}}</view>
              </view> -->
            </view>
            
            <view v-if="hotlist && hotlist.length > 0" id="letterhot" style="line-height: 80rpx;font-size:28rpx ;">
              <view class="title">热门城市</view>
              <view v-if="hotlist && hotlist.length > 0" style="width: 100%;padding: 0 20rpx;">
                  <view v-for="(item, index) in hotlist" :key="index" @tap="selit" :data-name="item.name" :data-code="item.code" class="hotname">
                      {{item.name}}
                  </view>
              </view>
            </view>

            <view v-for="(item, index) in letterlist" :key="index" :id="'letter'+item.letter" style="line-height: 80rpx;font-size:28rpx ;">
              <view class="title">{{item.letter}}</view>
              <view v-if="item.childs && item.childs.length>0" style="width: 100%;padding: 0 20rpx;">
                <view v-for="(item2, index2) in item.childs" :key="index2" @tap="selit" :data-name="item2.name" :data-code="item2.code" class="hotname">
                  {{item2.name}}
                </view>
              </view>
            </view>
          </scroll-view>
        </view>
        <view style="position: fixed;right: 0;top:20%;width: 50rpx;text-align: center;font-size: 24rpx;line-height: 45rpx;">
          <scroll-view v-if="letterlist && letterlist.length > 0" :scrollWithAnimation="animation" scroll-y="true">
              <block v-for="(item, index) in letterlist" :key="index">
                  <view :style="index===currentActiveIndex?'color:'+t('color1'):''" @tap="toviewid" :data-letter="item.letter" :data-index="index" style="">
                    {{item.letter}}
                  </view>
              </block>
          </scroll-view>
        </view>
        
        <view v-if="searchshow" style="background-color: #fff;position: fixed;top:80rpx;left: 0;width: 100%;border-top: 2rpx solid #f1f1f1;line-height: 80rpx;" :style="{height:'calc(100% + 80rpx)'}">
          <view v-for="(item, index) in searchlist" :key="index" :id="'letter'+index" class="cityname">
            <view @tap="selit" :data-name="item.name" :data-code="item.code" class="cityname-item">{{item.name}} {{item.code}}</view>
          </view>
        </view>
      </view>

		</block>
		<loading v-if="loading"></loading>
		<dp-tabbar :opt="opt" @getmenuindex="getmenuindex"></dp-tabbar>
	</view>
</template>
<script>
	var app = getApp();
	export default {
		data() {
			return {
				opt: {},
				loading: false,
				isload: false,
				menuindex: -1,
				nomore: false,
				nodata: false,
        pre_url: app.globalData.pre_url,
        type: 'from',
				currentActiveIndex: 0,
				animation: true,
				intoviewid: "",
        
        stationlist:'',//全部站点
        hotlist:'',//热门站点
        letterlist:'',//字母关联站点
        //搜索
        searchshow:false,
        searchlist:'',
        //当前城市
        poslist:'',
        posdefault:'无',
        
        setstatus:false,
			};
		},
		onLoad: function(opt) {
			this.opt = app.getopts(opt);
      this.type = this.opt.type || 'from';
			this.getdata();
		},
		methods: {
      getdata: function () {
        var that = this;
      	that.loading = true;
        app.post('ApiHanglvfeike/getstation', {}, function (res) {
      		that.loading = false;
          if(res.status == 1){
            that.stationlist = res.stationlist|| [];
            //热门城市
            that.hotlist  = res.hotlist || [];
            //城市数据
            that.letterlist = res.letterlist || [];
            
            that.checkLocation();
            that.loaded();
          } else {
      			if (res.msg) {
      				app.alert(res.msg, function() {
      					if (res.url) app.goto(res.url);
      				});
      			} else if (res.url) {
      				app.goto(res.url);
      			} else {
      				app.alert('您无查看权限');
      			}
      		}
      
        });
      },
			toviewid: function(e) {
        var that = this;
        var index =  e.currentTarget.dataset.index;
				that.intoviewid = 'letter'+e.currentTarget.dataset.letter;
				that.currentActiveIndex = index;
			},
      searchFocus() {
        this.searchshow = true;
      },
      closeSearch(){
        this.searchshow = false;
      },
      searchChange(e) {
        var that = this;
        var val = e.detail.value;
        var stationlist = that.stationlist;
      	var searchlist = [];
        if(val){
          for (let i = 0; i < stationlist.length; i++) {
            if(stationlist[i]['name'].indexOf(val) !== -1 || stationlist[i]['namepy'].indexOf(val) !== -1 || stationlist[i]['namepy2'].indexOf(val) !== -1){
              searchlist.push(stationlist[i]);
            }
          }
        }
      	that.searchlist = searchlist;
      },
      checkLocation() {
        var that = this;
        var stationlist = that.stationlist;
        var poslist = [];
        that.loading = true;
        app.getLocation(function(res) {
          var latitude = res.latitude;
          var longitude = res.longitude;
          app.post('ApiAddress/getAreaByLocation', {latitude:latitude,longitude:longitude}, function(res2) {
          	if(res2.status==1){
              if(res2.city){
                var city = res2.city;
                var city1 = city.slice(0, -1);
                var city2 = city.slice(0, 2);
                for (let i = 0; i < stationlist.length; i++) {
                  if(stationlist[i]['name'].indexOf(city1) !== -1 || stationlist[i]['name'].indexOf(city2) !== -1){
                    poslist.push(stationlist[i]);
                  }
                }
              }
              that.poslist = poslist;
          	}else{
              that.posdefault = '获取位置失败';
              that.poslist = poslist;
            }
          })
        },function(res2){
          that.posdefault = '获取位置失败';
          that.poslist = poslist;
        });
        that.loading = false;
      },
      selit: function (e) {
        var that = this;
        var type = that.type;
        var name = e.currentTarget.dataset.name;
        var code = e.currentTarget.dataset.code;
        if(that.setstatus){
          return
        }
        that.setstatus = true;
        setTimeout(function() {
            let pages = getCurrentPages();
            if (pages.length >= 2) {
                //let curPage = pages[pages.length - 1]; // 当前页面
                let prePage = pages[pages.length - 2]; // 上一页面
                if(type == 'from'){
                  prePage.$vm.fromCityname= name;
                  prePage.$vm.fromCity    = code;
                }else{
                  prePage.$vm.toCityname= name;
                  prePage.$vm.toCity    = code;
                }
                uni.navigateBack();
            }
        }, 600);
      
      },
		}
	};
</script>
<style>
  page{height: 100%;}
  .title{padding: 0 20rpx;font-weight: bold;}
  .hotname{width: 160rpx;text-align: center;line-height: 70rpx;border: 2rpx solid #fff;border-radius: 70rpx;overflow: hidden;white-space:nowrap;text-overflow: ellipsis;background-color: #fff;display:inline-block;margin-right: 10rpx;border:2rpx solid #f1f1f1;}
  .cityname{background: #fff;padding: 0 20rpx;}
  .cityname-item{border-bottom: 2rpx solid #f1f1f1;}
  .cityname:last-child{border-bottom: 0rpx;}
  
  .topsearch{width:100%;padding:16rpx 20rpx;position: fixed;top: 0;left: 0;background-color: #fff;z-index: 10;}
  .topsearch .f1{height:60rpx;border-radius:30rpx;border:0;background-color:#f7f7f7;flex:1}
  .topsearch .f1 .img{width:24rpx;height:24rpx;margin-left:10px}
  .topsearch .f1 input{height:100%;flex:1;padding:0 20rpx;font-size:28rpx;color:#333;}
  .topsearch .f1 .camera {height:72rpx;width:72rpx;color: #666;border: 0px;padding: 0px;margin: 0px;background-position: center;background-repeat: no-repeat; background-size:40rpx;}
  .topsearch .search-btn{display:flex;align-items:center;color:#5a5a5a;font-size:30rpx;width:60rpx;text-align:center;margin-left:20rpx}
  .search-navbar {display: flex;text-align: center;align-items:center;padding:5rpx 0}
  .search-navbar-item {flex: 1;height: 70rpx;line-height: 70rpx;position: relative;font-size:28rpx;font-weight:bold;color:#323232}
</style>
