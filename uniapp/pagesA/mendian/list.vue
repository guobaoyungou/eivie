<template>
<view>
	<block v-if="isload">
		<view class="tab">
			<view class="tab-item" :style="md_cid==0?'color:'+t('color1')+';background:rgba('+t('color1rgb')+',0.2)':''" :data-id="0" @tap="changeCategory">全部</view>
			<block  v-for="(item,index) in clist">
				
				<view class="tab-item" :style="md_cid==item.id?'color:'+t('color1')+';background:rgba('+t('color1rgb')+',0.2)':''" :data-id="item.id" @tap="changeCategory">{{item.name}}</view>
			</block>
		</view>
		<view class="topsearch flex-y-center">
			<view class="f1 flex-y-center">
				<image class="img" :src="pre_url+'/static/img/search_ico.png'"></image>
				<input :value="keyword" placeholder="搜索门店" placeholder-style="font-size:24rpx;color:#C2C2C2" @confirm="searchConfirm" ></input>
			</view>
			
		</view>
		<view class="storeitem" >
			<view class="radio-item" v-for="(item,index) in datalist" @tap="goto" :data-url="'/pages/shop/mendian?id='+item.id">
				<view class="f1">
					<view>
						<text class="iconfont icondingwei" ></text>
						{{item.name}}
					</view>
          <view v-if="item.tel" style="color: #aaaaae;font-size: 26rpx;margin-top: 20rpx;">
          	{{item.tel}}
          </view>
					<view @tap.stop="toMendian" :data-address="item.address" :data-longitude="item.longitude" :data-latitude="item.latitude"  class="address">
            <view class="address2">
              <text v-if="item.province !='北京市' || item.province !='上海市' || item.province !='重庆市' || item.province !='天津市' ">{{item.province}}</text>{{item.city}}{{item.district}}</text>
              <text v-if="item.address">{{item.address}}</text>
            </view>
            <image :src="pre_url+'/static/img/arrowright.png'" style="display: inline-block; width:26rpx; height: 26rpx"/>
          </view>
        </view>
        <text class="juli">{{item.distance}}</text>
			</view>
		</view>
	</block>
	<loading v-if="loading"></loading>
	<nodata v-if="nodata"></nodata>
	<nomore v-if="nomore"></nomore>
	<dp-tabbar :opt="opt" @getmenuindex="getmenuindex"></dp-tabbar>
	<popmsg ref="popmsg"></popmsg>
</view>
</template>

<script>
var app = getApp();
export default {
	data() {
		return {
			loading:false,
			isload: false,
			nomore:false,
			nodata:false,
			menuindex:-1,
			opt:{},
			pagenum: 1,
			datalist: [],
			keyword :'',
			md_cid:'',
			latitude:'',
			longitude:'',
			clist:[],
			pre_url: app.globalData.pre_url,
		}
		
	},
	onLoad: function (opt) {
		this.opt = app.getopts(opt);
		var latitude =  app.getLocationCache('latitude');
		var longitude= app.getLocationCache('longitude');
		var that = this;
		if(latitude==undefined || !longitude==undefined){
			app.getLocation(function(res) {
				that.latitude = res.latitude;
				that.longitude = res.longitude;
				that.getdata();
			});
		}
		this.getdata();
		this.getMendianCategory();
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
	methods: {
		getdata:function(loadmore){
			if(!loadmore){
				this.pagenum = 1;
				this.datalist = [];
			}
			var that = this;
			var pagenum = that.pagenum;
			var keyword = that.keyword;
			var cid = that.md_cid;
			 that.loading = true;
			that.nodata = false;
			that.nomore = false;
			app.get('ApiMendian/mendianlist', {pagenum: pagenum,keyword: keyword,cid: cid,longitude: that.longitude,latitude: that.latitude}, function (res) {
				that.loading = false;
				var data = res.data;
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
			
			})
		},
		getMendianCategory:function(){
			var that = this;
			app.get('ApiMendian/getMendianCategory', {bid:that.opt.bid}, function (res) {
				that.clist = res.data;
			})
		},
		searchConfirm: function (e) {
		  var that = this;
		  var keyword = e.detail.value;
		  that.keyword = keyword
		  that.getdata();
		},
		changeCategory:function(e){
			var id = e.currentTarget.dataset.id;
			console.log(e);
			this.md_cid = id;
			this.getdata();
		},
    toMendian:function(e){
    	var latitude = parseFloat(e.currentTarget.dataset.latitude);
    	var longitude = parseFloat(e.currentTarget.dataset.longitude);
    	var address = e.currentTarget.dataset.address;
    	if(!latitude || !longitude){
    		return;
    	}
    	uni.openLocation({
    	 latitude:latitude,
    	 longitude:longitude,
    	 name:address,
    	 scale: 13
    	})
    },
		
	}
}

</script>

<style>
	.topsearch{width:100%;padding:16rpx 20rpx;background: #fff;}
	.topsearch .f1{height:60rpx;border-radius:30rpx;border:0;background-color:#f7f7f7;flex:1}
	.topsearch .f1 .img{width:24rpx;height:24rpx;margin-left:10px}
	.topsearch .f1 input{height:100%;flex:1;padding:0 20rpx;font-size:28rpx;color:#333;}
	.topsearch .search-btn{display:flex;align-items:center;color:#5a5a5a;font-size:30rpx;width:60rpx;text-align:center;margin-left:20rpx}
	.storeitem{width: 100%;padding:0 0 20rpx 0;display: flex;flex-direction: column;color: #333;}
	.storeitem .radio-item {
	    display: flex;
	    width: 100%;
	    color: #000;
	    align-items: center;
	    background: #fff;
	    padding: 20rpx 20rpx;
	    border-bottom: 1px dotted #f1f1f1;
	}
	.storeitem .radio-item .f1 {
	    color: #333;
	    font-size: 30rpx;
	    flex: 1;
	}
	.storeitem .radio-item .f1 .address{
		text-align: left;
		font-size: 12px;
		color: #aaaaae;
    margin-top: 20rpx;
    display: flex;
    justify-content: space-between;
    align-items: center;
	}
  .storeitem .radio-item .f1 .address2{
  	display: -webkit-box;
  	-webkit-box-orient: vertical;
  	-webkit-line-clamp: 1;
  	overflow: hidden;
    width: 580rpx;
  }
	.storeitem .radio-item .juli{color: #f50}
	.iconfont {
	    font-family: "iconfont" !important;
	    font-size: 18px;
	    font-style: normal;
	    -webkit-font-smoothing: antialiased;
	    -moz-osx-font-smoothing: grayscale;
	}
	.tab{width: 100%;
		overflow-y: hidden;
		overflow-x: scroll;
		white-space: nowrap;
		padding: 10rpx 20rpx;
		background: #fff
	}
	.tab-item{
		background: #F5F6F8;
		border-radius: 24rpx;
		color: #6C737F;
		font-size: 24rpx;
		line-height: 48rpx;
		padding: 0 28rpx;
		margin: 12rpx 10rpx 12rpx 0;
		display: inline-block;
		white-space: break-spaces;
		max-width: 610rpx;
		vertical-align: middle;
		border: 2rpx solid #e0e0e0;
		padding:  0 20rpx;
	}
	
</style>