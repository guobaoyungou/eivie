<template>
<view class="container">
	<block v-if="isload">
    <view class="content" v-if="queryform.desc">
		<view class="item" style="display: block;">
        <parse :content="queryform.desc" />
		</view>
    </view>
        <!-- #ifndef APP-PLUS -->
		<view class="topsearch flex-y-center" v-if="queryform.custom_search==0">
			<view class="f1 flex-y-center">
				<image class="img" :src="pre_url+'/static/img/search_ico.png'"></image>
				<input :value="keyword" placeholder="输入关键字搜索" placeholder-style="font-size:24rpx;color:#C2C2C2" @confirm="searchConfirm"></input>
			</view>
		</view>
		<view class="topsearch flex-y-center" v-if="queryform.custom_search==1">
			<view class="f1 flex-y-center" >
				<image class="img" :src="pre_url+'/static/img/search_ico.png'"></image>
				<input v-model="keyword" :placeholder="queryform.search_title" placeholder-style="font-size:24rpx;color:#C2C2C2" @confirm="searchConfirm">
				</input>
			</view>
			<text @click="searchKeyword" style="width: 80rpx;height: 40rpx;text-align: center;">确定</text> 
		</view>
        <!--  #endif -->
		<view class="content" id="datalist">
			<view class="item" @tap="goto" :data-url="'formdetail?id=' + item.id + '&op=view'" v-for="(item, index) in datalist" :key="index" :style="{background:item.background_color}">
				<view class="f1">
						<text class="t1" v-if="queryform.show_name==1">{{item.title}}</text>
            <block v-if="item.form0 && item.form0_key !='upload_file' && item.form0_show==1 ">
                <text class="t2" v-if="item.form0_key !='upload' && item.form0_key !='upload_pics' " >{{item.form0_val}}: {{item.form0}}</text>
                <view class="t2" v-else>
                  <block v-if="item.form0_key =='upload_pics'" v-for="(item2,index2) in item.form0" :key="index2">
                    <image :src="item2" style="width:50px;height:auto;margin-right: 10rpx;" mode="widthFix" @tap="previewImage" :data-url="item2"></image>
                  </block>
                  <block v-else>
                    <image :src="item.form0" style="width:50px;height:auto" mode="widthFix" @tap="previewImage" :data-url="item.form0"></image>
                  </block>
                </view>
            </block>
            <block v-if="item.form1 && item.form1_key !='upload_file' && item.form1_show==1">
                <text class="t2" v-if="item.form1_key !='upload' && item.form1_key !='upload_pics' " >{{item.form1_val}}: {{item.form1}}</text>
                <view class="t2" v-else>
                  <block v-if="item.form1_key =='upload_pics'" v-for="(item2,index2) in item.form1" :key="index2">
                    <image :src="item2" style="width:50px;height:auto;margin-right: 10rpx" mode="widthFix" @tap="previewImage" :data-url="item2"></image>
                  </block>
                  <block v-else>
                    <image :src="item.form1" style="width:50px;height:auto" mode="widthFix" @tap="previewImage" :data-url="item.form1"></image>
                  </block>
                </view>
            </block>
            <block v-if="item.form2 && item.form2_key !='upload_file' && item.form2_show==1">
                <text class="t2" v-if="item.form2_key !='upload' && item.form2_key !='upload_pics' " >{{item.form2_val}}: {{item.form2}}</text>
                <view class="t2" v-else>
                  <block v-if="item.form2_key =='upload_pics'" v-for="(item2,index2) in item.form2" :key="index2">
                    <image :src="item2" style="width:50px;height:auto;margin-right: 10rpx" mode="widthFix" @tap="previewImage" :data-url="item2"></image>
                  </block>
                  <block v-else>
                    <image :src="item.form2" style="width:50px;height:auto" mode="widthFix" @tap="previewImage" :data-url="item.form2"></image>
                  </block>
                </view>
            </block>
            <block v-if="item.form3 && item.form3_key !='upload_file' && item.form3_show==1">
                <text class="t2" v-if="item.form3_key !='upload' && item.form3_key !='upload_pics'" >{{item.form3_val}}: {{item.form3}}</text>
                <view class="t2" v-else>
                  <block v-if="item.form3_key =='upload_pics'" v-for="(item2,index2) in item.form3" :key="index2">
                    <image :src="item2" style="width:50px;height:auto;margin-right: 10rpx" mode="widthFix" @tap="previewImage" :data-url="item2"></image>
                  </block>
                  <block v-else>
                    <image :src="item.form3" style="width:50px;height:auto" mode="widthFix" @tap="previewImage" :data-url="item.form3"></image>
                  </block>
                </view>
            </block>
						<block  v-if="item.show_distance" @tap="openLocation" :data-latitude="item.adr_lat" :data-longitude="item.adr_lon">
							<view class="t2" >
								距离您{{item.distance}}
								<image :src="pre_url+'/static/img/b_addr.png'" style="width:26rpx;height:26rpx;margin-right:10rpx"/>
								点击导航
							</view> 
						 </block>
						<text class="t2" v-if="queryform.show_time==1">
							<text v-if="item.member && item.member.realname">{{item.member.realname}}</text>
							<text v-else>{{item.member.nickname}}</text>
							时间：{{item.createtime}}
						</text>
				</view>
			</view>
		</view>
	</block>
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

export default {
  data() {
    return {
			opt:{},
			loading:false,
      isload: false,
			menuindex:-1,

      st: 'all',
      datalist: [],
      pagenum: 1,
      nomore: false,
      nodata: false,
      keyword:'',
	  latitude:'',
	  longitude:'',
	  queryform:{},
	  pre_url: app.globalData.pre_url,
    };
  },
  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.st = this.opt.st || 'all';
		this.getdata();
  },
	onPullDownRefresh: function () {
		this.getdata();
	},
  onReachBottom: function () {
    if (!this.nodata && !this.nomore) {
      this.pagenum = this.pagenum + 1;
      this.getDataList(true);
    }
  },
  methods: {
    getdata: function (loadmore) {
			if(!loadmore){
				this.pagenum = 1;
				this.datalist = [];
			}
      var that = this;
      var pagenum = that.pagenum;
      var st = that.st;
			this.nodata = false;
			this.nomore = false;
			this.loading = true;
      app.post('ApiMy/formlog2', {st: st,pagenum: pagenum,id:that.opt.id,keyword:that.keyword,latitude:that.latitude,longitude:that.longitude}, function (res) {
				that.loading = false;
        var data = res.data;
		if(res.status == 0){
			app.error(res.msg);
			setTimeout(function () {
				app.goto(res.redirect_url);
			}, 3000);
			return;
		}
		var queryform = res.queryform;
		
		that.queryform = queryform;
		if(queryform.show_title==1){
			if(queryform.log_title){
				uni.setNavigationBarTitle({
					title: queryform.log_title
				});
			}
		}else{
			uni.setNavigationBarTitle({
				title: ''
			});
		}
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
      });
	  app.getLocation(function (res) {
	  	var latitude = res.latitude;
	  	var longitude = res.longitude;
	  	that.longitude = longitude;
	  	that.latitude = latitude;
	  	that.getDataList();
	  },
	  function () {
	  	that.getDataList();
	  });
    },
	getDataList:function(loadmore){
		if(!loadmore){
			this.pagenum = 1;
			this.datalist = [];
		}
		var that = this;
		var pagenum = that.pagenum;
		var st = that.st;
		this.nodata = false;
		this.nomore = false;
		this.loading = true;
		app.post('ApiMy/formlog2', {st: st,pagenum: pagenum,id:that.opt.id,keyword:that.keyword,latitude:that.latitude,longitude:that.longitude}, function (res) {
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
		});
	},
	searchConfirm:function(e){
	    this.keyword = e.detail.value;
	    this.getdata(false);
	},
	searchKeyword:function(e){
	    this.getdata(false);
	},
	openLocation:function(e){
			//console.log(e)
		var latitude = parseFloat(e.currentTarget.dataset.latitude)
		var longitude = parseFloat(e.currentTarget.dataset.longitude)
		var address = e.currentTarget.dataset.address
		uni.openLocation({
			 latitude:latitude,
			 longitude:longitude,
			 name:address,
			 scale: 13
		})		
	}
  }
}
</script>
<style>
    .topsearch{width:94%;margin:10rpx 3%;}
    .topsearch .f1{height:60rpx;border-radius:30rpx;border:0;background-color:#fff;flex:1}
    .topsearch .f1 .img{width:24rpx;height:24rpx;margin-left:10px}
    .topsearch .f1 input{height:100%;flex:1;padding:0 20rpx;font-size:28rpx;color:#333;}
	.content{ width:100%;margin:0;}
	.content .item{ width:94%;margin:20rpx 3%;;background:#fff;border-radius:16rpx;padding:30rpx 30rpx;display:flex;align-items:center;}
	.content .item:last-child{border:0}
	.content .item .f1{width:80%;display:flex;flex-direction:column}
	.content .item .f1 .t1{color:#000000;font-size:30rpx;word-break:break-all;overflow:hidden;text-overflow:ellipsis;}
	.content .item .f1 .t2{color:#666666;margin-top:10rpx}
	.content .item .f1 .t2 text { margin-right: 20rpx;}
	.content .item .f1 .t3{color:#666666}
	.content .item .f2{width:20%;font-size:32rpx;text-align:right}
	.content .item .f2 .t1{color:#03bc01}
	.content .item .f2 .t2{color:#000000}
	.content .item .f3{ flex:1;font-size:30rpx;text-align:right}
	.content .item .f3 .t1{color:#03bc01}
	.content .item .f3 .t2{color:#000000}
</style>