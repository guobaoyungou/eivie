<template>
<view class="container">
	<block v-if="isload">
		<view class="search-container" :style="history_show?'height:100%;':''">
			
			<view class="search-history" v-show="history_show">
				<view class="topimg" v-if="info.banner" :style="'background-image:url(' + info.banner + ');background-size:100% 100%;'"></view>
				<view class="centerimg" @tap="uploadImg">
					<image v-if="info.image_search_pic" :src="info.image_search_pic"></image>
					<image v-else :src="pre_url + '/static/img/camera2.png'"></image>
					<view class="title">以图搜索</view>
				</view>
			</view>
		</view>
		<ksp-cropper mode="free" :width="200" :height="140" :maxWidth="1024" :maxHeight="1024" :url="imgurl" @cancel="oncancel" @ok="onok"></ksp-cropper>
	</block>
	<loading v-if="loading"></loading>
	<dp-tabbar :opt="opt" @getmenuindex="getmenuindex"></dp-tabbar>
	<popmsg ref="popmsg"></popmsg>
	<wxxieyi></wxxieyi>
</view>
</template>

<script>
import kspCropper from './ksp-cropper/ksp-cropper.vue';
var app = getApp();
export default {
	components: {
		kspCropper
	},
  data() {
    return {
			opt:{},
			loading:false,
      isload: false,
			menuindex:-1,
			pre_url:app.globalData.pre_url,

			nomore:false,
			nodata:false,
      keyword: '',
      pagenum: 1,
      datalist: [],
      history_list: [],
      history_show: true,
      order: '',
			field:'',
      oldcid: "",
      catchecid: "",
      catchegid: "",
      cid: "",
      gid: '',
			cid2:'',
      oldcid2: "",
      catchecid2: "",
      clist: [],
      clist2: [],
      glist: [],
      productlisttype: 'item2',
      showfilter: "",
			cpid:0,
			bid:0,
			
			info:{},
			imgurl:'',
			newimgpath:'',
			imageUrls:''
    };
  },
  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.oldcid = this.opt.cid || '';
		this.catchecid = this.opt.cid;
		this.cid = this.opt.cid;
		this.cid2 = this.opt.cid2 || '';
		this.oldcid2 = this.opt.cid2 || '';
		this.catchecid2 = this.opt.cid2;
		this.gid = this.opt.gid;
		this.cpid = this.opt.cpid || 0;
		this.bid = this.opt.bid ? this.opt.bid : 0;
		if(this.opt.keyword) {
			this.keyword = this.opt.keyword;
		}
		//console.log(this.bid);
		if(this.cpid > 0){
			uni.setNavigationBarTitle({
				title: '可用商品列表'
			});
		}

		this.getdata();
  },
	onPullDownRefresh: function () {
		this.getdata();
	},
  onReachBottom: function () {
    
  },
  methods: {
		onok(ev) {
			var that = this;
			this.imgurl = "";
			this.newimgpath = ev.path;
			console.log(this.newimgpath);
			// app.showLoading('上传中');
			that.loading = true;
			uni.uploadFile({
				url: app.globalData.baseurl + 'ApiImageupload/uploadImg/aid/' + app.globalData.aid + '/platform/' + app.globalData.platform + '/session_id/' +
					app.globalData.session_id,
				filePath: this.newimgpath,
				name: 'file',
				success: function(res) {
					console.log('ssss')
					var imageUrls;
					app.showLoading(false);
					that.loading = false;
					var data = JSON.parse(res.data);
						
					if (data.status == 1) {
						imageUrls = data.url;
						that.imageUrls = imageUrls;
						console.log('imageUrls');
						console.log(imageUrls);
						// that.getprolist(imageUrls);
						app.goto('imgsearchList?imgurl='+(imageUrls)+'&bid='+that.bid)
					} else {
						app.alert(data.msg);
					}
				},
				fail: function(res) {
					app.showLoading(false);
					that.loading = false;
					app.alert(res.errMsg);
				}
			});
		},
		oncancel() {
				// url设置为空，隐藏控件
				this.imgurl = "";
		},
		uploadImg:function(){
			var that = this;
			uni.showActionSheet({
				itemList: ['从微信聊天中选择', '从相册中选择', '拍照'],
				success: function (res) {
					if (!res.cancel) {
						if (res.tapIndex == 0) {
							that.chaHistoryUploadImg();
						} else if (res.tapIndex == 1) {
							that.photoUploadImg();
						}else if(res.tapIndex == 2){
							that.photograph();
						}
					}
				},
				fail: function (res) {
					console.log(res.errMsg);
				}
			});
		},
		chaHistoryUploadImg:function(){
			// #ifdef MP-WEIXIN
			var that = this;
			wx.chooseMessageFile({
			  count: 1,
				type: 'image',
			  success (res) {
			    // tempFilePath可以作为 img 标签的 src 属性显示图片
			    let tempFilePaths = res.tempFiles
					if(tempFilePaths){
						that.imgurl = tempFilePaths[0].path;
					}
			  },fail:function(error){
          console.log(error);
        }
			})
			// #endif
		},
		getdata:function(){
			var that = this;
			that.pagenum = 1;
			that.datalist = [];
			var cid = that.opt.cid;
			var gid = that.opt.gid;
			var bid = that.opt.bid ? that.opt.bid : 0;
			var cid2 = that.cid2;
			that.loading = true;
			app.get('ApiShop/imgsearch', {bid:bid}, function (res) {
				that.loading = false;
				if(res.status == 1) {
					that.info = res.info;
					that.loaded();
				} else {
					app.alert(res.msg);
				}
			});
		},
    photoUploadImg:function(){
      var that = this;
      uni.chooseImage({
        count: 1,
        sourceType: ['album'], //从相册选择
        success: function (res) {
					var urls = res.tempFilePaths;
					if(urls){
						var imgurl = urls[0];
						that.imgurl = imgurl;
					}
        },fail:function(error){
          console.log(error);
        }
      });
    },
    photograph:function(){
      var that = this;
      uni.chooseImage({
        count: 1,
        sourceType: ['camera'], //拍照
        success: function (res) {
          var urls = res.tempFilePaths;
          if(urls){
          	var imgurl = urls[0];
          	that.imgurl = imgurl;
          }
        },fail:function(error){
					console.log(error);
        }
      });
    }
  }
};
</script>
<style>
.search-container {position: fixed;width: 100%;background: #fff;z-index:9;top:var(--window-top)}
.search-navbar-img { height: 70rpx; text-align: center;background-color: #fff; border-radius: 20rpx; border-bottom-left-radius: 0; border-bottom-right-radius: 0;}
.search-navbar-img image { border: 3px solid #fff; border-radius: 20rpx; margin-top: -30rpx;width: 100rpx;height:100rpx;}
.search-navbar {display: flex;text-align: center;align-items:center;padding:5rpx 0;
    background-color: #fff;
    margin-bottom: 20rpx;
    border-radius: 20rpx; border-top-left-radius: 0; border-top-right-radius: 0;}
.search-navbar-item {flex: 1;height: 70rpx;line-height: 70rpx;position: relative;font-size:28rpx;font-weight:bold;color:#323232}

.search-navbar-item .iconshangla{position: absolute;top:-4rpx;padding: 0 6rpx;font-size: 20rpx;color:#7D7D7D}
.search-navbar-item .icondaoxu{position: absolute;top: 8rpx;padding: 0 6rpx;font-size: 20rpx;color:#7D7D7D}
.search-navbar-item .iconshaixuan{margin-left:10rpx;font-size:22rpx;color:#7d7d7d}

.filter-scroll-view{margin-top:var(--window-top)}
.search-filter{display: flex;flex-direction: column;text-align: left;width:100%;flex-wrap:wrap;padding:0;}
.filter-content-title{color:#999;font-size:28rpx;height:30rpx;line-height:30rpx;padding:0 30rpx;margin-top:30rpx;margin-bottom:10rpx}
.filter-title{color:#BBBBBB;font-size:32rpx;background:#F8F8F8;padding:60rpx 0 30rpx 20rpx;}
.search-filter-content{display: flex;flex-wrap:wrap;padding:10rpx 20rpx;}
.search-filter-content .filter-item{background:#F4F4F4;border-radius:28rpx;color:#2B2B2B;font-weight:bold;margin:10rpx 10rpx;min-width:140rpx;height:56rpx;line-height:56rpx;text-align:center;font-size: 24rpx;padding:0 30rpx}
.search-filter-content .close{text-align: right;font-size:24rpx;color:#ff4544;width:100%;padding-right:20rpx}
.search-filter button .icon{margin-top:6rpx;height:54rpx;}
.search-filter-btn{display:flex;padding:30rpx 30rpx;justify-content: space-between}
.search-filter-btn .btn{width:240rpx;height:66rpx;line-height:66rpx;background:#fff;border:1px solid #e5e5e5;border-radius:33rpx;color:#2B2B2B;font-weight:bold;font-size:24rpx;text-align:center}
.search-filter-btn .btn2{width:240rpx;height:66rpx;line-height:66rpx;border-radius:33rpx;color:#fff;font-weight:bold;font-size:24rpx;text-align:center}

.product-container {width: 100%;margin-top: 100rpx;font-size:26rpx;padding:0 24rpx}
.topimg {width:96%;height:316rpx;margin: auto;margin-top: 10rpx;}
.centerimg { width: 480rpx; margin: 80rpx auto 0; height: 340rpx; border: 2px dashed #ccc; border-radius: 20rpx;background-position: center;background-repeat: no-repeat; background-size:180rpx; text-align: center;}
.centerimg image{width: 200rpx;height: 200rpx; margin-top: 40rpx;}
.centerimg .title { font-size: 32rpx;}
</style>