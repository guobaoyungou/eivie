<template>
  <block v-if="isload">
    <view class="container">
      <!--自定义导航栏-->
      <view class="navigation" :class="{show: showCustomNav}">
				<view class="custom-nav" >
					<view class="nav-left-icon" @tap="goto" :data-url="'/pagesD/material/collect'">
						<image :src="pre_url+'/static/img/material/collect.png'"></image>
					</view>
					<view class="nav-log-box">
						<image class="nav-logo" :src="set.logo" mode="aspectFit"></image>
					</view>
				</view>
			</view>
      <!-- 轮播图-->
      <view class="swiper-container">
        <swiper class="swiper" :indicator-dots="true" :autoplay="true" :circular="true" :interval="5000" indicator-active-color="#ffffff" indicator-color="rgba(255,255,255,0.5)">
          <block v-if="set.pics && set.pics.length > 0" v-for="(item, index) in set.pics" :key="index">
            <swiper-item class="swiper-item">
              <image v-if="!isVideo(item)" :src="item" mode="aspectFill"></image>
              <video v-else :src="item" autoplay muted loop :controls="false" :show-play-btn="false" :show-fullscreen-btn="true" :show-center-play-btn="false" :enable-progress-gesture="false" object-fit="cover"></video>
            </swiper-item>
          </block>
        </swiper>
      </view>
      <view style="height: 450rpx"></view>
      <view class="content-container flex-bt" style="height: calc(150vh - (450rpx - 180rpx));">
        <view class="nav-left sticky">
          <block v-for="(item, parentIndex) in navlist" :key="parentIndex">
            <view class="nav-header">{{ item.name }}</view>
            <block v-for="(child, childIndex) in item.children" :key="childIndex">
              <view :class="'nav-items ' + (curParentIndex === parentIndex && curChildIndex === childIndex ? 'active' : '')" @click="switchRightTab" :data-id="child.id" :data-parent="parentIndex" :data-child="childIndex" :data-type="child.type">
                {{ child.name }}
              </view>
            </block>
          </block>
        </view>

        <view class="nav-right">
					<!-- 三级分类 -->
          <view class="classify-container">
            <view class="classify flex" v-if="classify && classify.length > 0">
              <block v-for="(item, index) in classify" :key="index">
                <view class="classify-item" :class="item.id === threeid ? 'active' : ''" @click="switchTab" :data-id="item.id" :data-type="item.type"> {{item.name}} </view>
              </block>
            </view>
          </view>

					<!-- 图片 -->
          <view class="material-columns" v-if="classtype == 1">
						<view class="material-item" v-for="(item,index) in datalist" :key="item.id" @click="goto" :data-url="'details?id='+item.id">
							<view class="material-pic">
								<image class="image" :src="item.url" mode="widthFix" />
							</view>
							<view class="material-name">{{item.name}}</view>
						</view>
					</view>
					<!-- 图文 -->
					<view class="imagetext" v-if="classtype == 4">
						<view class="imagetext-item" v-for="(item,index) in datalist" :key="item.id">
							<view style="padding: 20rpx;">
								<view class="top flex-bt">
									<view class="head flex">
										<image :src="set.logo" class="f1"></image>
										<view class="f2">
											<view class="t1">{{set.name}}</view>
											<view class="t2">{{item.createtime}}</view>
										</view>
									</view>
								</view>
								<view class="con">
									<view class="f1">
                    <text style="white-space:pre-wrap;font-size: 24rpx" :class="{'text-truncate': !item.showFullText}">{{item.content}}</text>
                  </view>
                  <view v-if="item.content && item.content.length > 100 && !item.showFullText" class="view-more" @tap="toggleFullText(index)">
                    查看全文
                  </view>
									<view class="f2" v-if="item.pics">
										<view class="pic-box"  v-for="(pic, idx) in item.pics" :key="idx">
											<image :src="pic" mode="widthFix" @tap="previewImageReset(item.pics,idx)"></image>
										</view>
									</view>
								</view>
							</view>
							<view class="bot flex-bt">
								<!-- 保存图片 -->
								<view class="f4" @tap.stop="savepics" :data-id="item.id" :data-index="index">
									<image :src="pre_url+'/static/img/material/download.png'"></image>
								</view>
								<!-- 复制 -->
								<view class="f4" @tap.stop="fuzhi" :data-index="index">
									<image :src="pre_url+'/static/img/material/copy.png'"></image>
								</view>
								<!-- 收藏 -->
								<view class="f4" @tap.stop="collect" :data-index="index" :data-id="item.id">
									<image :src="pre_url+'/static/img/material/collect_active.png'" v-if="item.is_collected == 1"></image>
									<image :src="pre_url+'/static/img/material/collect.png'" v-else></image>
								</view>
							</view>
						</view>
					</view>
					<!-- END 图文 -->
          <nomore text="————End————" v-if="nomore"></nomore>
          <nodata text="暂无相关素材" v-if="nodata"></nodata>
        </view>
      </view>

      <loading v-if="loading"></loading>
      <dp-tabbar :opt="opt" @getmenuindex="getmenuindex"></dp-tabbar>
      <popmsg ref="popmsg"></popmsg>
    </view>
  </block>
</template>

<script>
var app = getApp();
export default {
  data() {
    return {
      opt: {},
      loading: false,
      isload: false,
      pagenum: 1,
      nomore: false,
      nodata: false,
      navlist:[],
      datalist:[],
      curCid:0,
      curParentIndex:0,
      curChildIndex:0,
      classify:[],
			classtype:0,//图片
      threeid:0,
			set:[],
			pre_url: app.globalData.pre_url,
      showCustomNav: false,
      picindex:0,
			navigationMenu:{},
    };
  },
  onLoad: function(opt) {
    this.opt = app.getopts(opt);
    this.getdata();
		this.wxNavigationBarMenu();
  },
  onPullDownRefresh: function() {
    this.getdatalist();
  },
  onReachBottom: function () {
    if (!this.nodata && !this.nomore ) {
      this.pagenum = this.pagenum + 1;
      this.getdatalist(true);
    }
  },
  onPageScroll(e) {
    this.scrollTop = e.scrollTop;
    this.showCustomNav = this.scrollTop >= (450 - 180) / 2;
  },
  methods: {
    getdata: function() {
      var that = this;
      that.datalist = [];
      that.loading = true;
      app.get('ApiMaterial/getCategory', {}, function(res) {
        that.loading = false;
        var clist = res.data;
        if(clist[that.curParentIndex] && clist[that.curParentIndex].children.length > 0){
          that.curCid = clist[that.curParentIndex].children[that.curChildIndex].id;
          that.classtype = clist[that.curParentIndex].children[that.curChildIndex].type;
        }
        that.set = res.set;
        that.navlist = clist;
        that.loaded();
        that.getdatalist();
      });
    },

    getdatalist: function(loadmore) {
      if (!loadmore) {
        this.pagenum = 1;
        this.datalist = [];
      }
      var that = this;
      var pagenum = that.pagenum;

      that.nodata = false;
      that.nomore = false;
      app.post('ApiMaterial/getMaterialList', {cid:that.curCid,pagenum:that.pagenum,threeid:that.threeid}, function(res) {
        uni.stopPullDownRefresh();
        var data = res.data;
        if (data.length == 0) {
          if (pagenum == 1) {
            that.nodata = true;
          } else {
            that.nomore = true;
          }
        }
        var datalist = that.datalist;
        var newdata = datalist.concat(data);
        that.datalist = newdata;
      });
    },

    changetab: function(st) {
      this.st = st;
      uni.pageScrollTo({
        scrollTop: 0,
        duration: 0
      });
      this.getdata();
    },

    scrolltolower: function() {
      if (!this.nomore) {
        this.pagenum = this.pagenum + 1;
        this.getdatalist(true);
      }
    },

    // 切换分类
    switchRightTab: function(e) {
      var that = this;
      var id = e.currentTarget.dataset.id;
      var type = e.currentTarget.dataset.type;
      var parentIndex = parseInt(e.currentTarget.dataset.parent);
      var childIndex = parseInt(e.currentTarget.dataset.child);
			var clist = this.navlist;
      that.threeid = 0;
      that.classtype = type;
			if(clist[parentIndex] && clist[parentIndex].children.length > 0){
				that.classify = clist[parentIndex].children[childIndex].children;
        if(that.classify.length > 0){
          that.threeid = that.classify[0].id;
					that.classtype = that.classify[0].type;
        }
			}
      this.curParentIndex = parentIndex;
      this.curChildIndex = childIndex;
      this.nodata = false;
      this.curCid = id;
      this.pagenum = 1;
      this.datalist = [];
      this.nomore = false;
      this.getdatalist();
    },
    switchTab:function(e){
      const id = e.currentTarget.dataset.id;
      const type = e.currentTarget.dataset.type;
			this.classtype = type;
      this.activeThreeId = id;
      this.threeid = id;
      this.pagenum = 1;
      this.datalist = [];
      this.nomore = false;
      this.getdatalist();
    },
		fuzhi:function(e){
			var that = this;
			var index = e.currentTarget.dataset.index;
			var info = that.datalist[index];
			uni.setClipboardData({
				data: info.content,
				success: function () {
					app.success('复制文案成功');
				},
				fail:function(){
					app.error('请长按文本内容复制');
				}
			});
		},
    savepics:function(e){
      var that = this;
      var index = e.currentTarget.dataset.index;
      var item = this.datalist[index];
      var pics = item.pics;
      var total = pics.length;
      if (!pics || total === 0) {
        app.error('没有图片可保存');
        return;
      }
      if(app.globalData.platform == 'mp' || app.globalData.platform == 'h5'){
        app.error('请长按图片保存');return;
      }
      this.picindex = 0;
      app.showLoading(`[1/${total}]保存中...`);
      that.savpic(pics,total);
    },
    savpic:function(pics,total){
      var that = this;
      var picindex = this.picindex;
      if(picindex >= pics.length){
        app.showLoading(false);
        app.success('已保存到相册');
        return;
      }
      var pic = pics[picindex];
      uni.downloadFile({
        url: pic,
        success (res) {
          if (res.statusCode === 200) {
            uni.saveImageToPhotosAlbum({
              filePath: res.tempFilePath,
              success:function () {
                that.picindex++;
                app.showLoading(`[${that.picindex+1}/${total}]保存中...`);
                that.savpic(pics,total);
              },
              fail:function(){
                app.showLoading(false);
                app.error('保存失败');
              }
            })
          }
        },
        fail:function(){
          app.showLoading(false);
          app.error('下载失败');
        }
      });
    },
		collect: function(e) {
			var that = this;
			var id = e.currentTarget.dataset.id;
			var index = e.currentTarget.dataset.index;
			app.post('ApiMaterial/collect', {id: id}, function(res) {
				if(res.status == 1) {
					that.datalist[index].is_collected = res.data;
					app.success(res.msg);
				} else {
					app.error(res.msg);
				}
			});
		},
    isVideo(url) {
      if (!url) return false;
      const videoExtensions = ['.mp4', '.webm', '.ogg', '.mov'];
      return videoExtensions.some(ext => url.toLowerCase().endsWith(ext));
    },
		previewImageReset:function(pics,index){
			uni.previewImage({
				urls:pics,
				current:index
			});
		},
		wxNavigationBarMenu:function(){
			if(this.platform=='wx'){
				//胶囊菜单信息
				this.navigationMenu = wx.getMenuButtonBoundingClientRect()
			}
		},
    toggleFullText(index) {
      this.$set(this.datalist[index], 'showFullText', true);
    },
  }
};
</script>

<style>
page{height:100%}
.container{position: relative;width:100%;height:100%;max-width:640px;background-color:#F1F1F1;color:#5a5a5a;}
.navigation {position: fixed;left: 0;top: 0;width: 100%;height: 180rpx; background: #F1F1F1;max-width: 640px;z-index: 999;opacity: 0;}
.navigation.show { opacity: 1;}
.custom-nav {display: flex; align-items: center;justify-content: center;margin: 0 auto;margin-top: 60rpx;}
.nav-left-icon {position: absolute; left: 20rpx; width: 60rpx; height: 60rpx; display: flex; align-items: center; justify-content: center; background-color: #fff;border-radius: 50%;}
.nav-left-icon image {width: 30rpx;height: 30rpx;}
.custom-nav .nav-logo {height: 60rpx; width: 120rpx;}
.swiper-container{width:100%;height:450rpx;position: fixed;}
.swiper-container .swiper{height:450rpx;}
.swiper-container .swiper .swiper-item image{width:100%;height:100%}
.swiper-container .swiper .swiper-item video{width:100%;height:100%;display: block;}
.swiper-container .swiper .wx-swiper-dot{width:40rpx;height:4rpx;border-radius:3rpx;transition:all 0.3s}
.swiper-container .swiper .wx-swiper-dot-active{width:40rpx}
.content-container{display:flex;position:relative;background: #F1F1F1;padding-top:35rpx;}
.content-container .sticky{position:-webkit-sticky;position:sticky;top:0;align-self:flex-start;max-height:100vh;overflow-y:auto}
.content-container .nav-left{width:25%;background-color:#F1F1F1;padding-bottom: 180rpx;color: #6c6c6c;}
.content-container .nav-left .nav-header{padding:20rpx;font-size:24rpx;font-weight:bold;color:#000}
.content-container .nav-left .nav-header:first-child{padding-top: 0;}
.content-container .nav-left .nav-items{height:60rpx;line-height:60rpx;padding:0 30rpx;font-size:22rpx;overflow:hidden}
.content-container .nav-left .active{background-color:#fff;border-right:1px solid #000;color:#000;font-size:22rpx}
.content-container .nav-right{width:75%;padding: 0 20rpx;overflow-y: auto;position: relative; background-color:#F1F1F1;padding-bottom: 180rpx;}
.content-container .nav-right .classify-container {position: sticky;  top: 0;  z-index: 10;  background: #F1F1F1;}
.content-container .nav-right .classify{width: 100%;white-space: nowrap;overflow-x: auto;-webkit-overflow-scrolling: touch; padding-bottom: 20rpx;}
.content-container .nav-right .classify-item{display: inline-block;background-color: #fff;padding:10rpx 40rpx;margin-right:20rpx;font-size: 24rpx;flex-shrink: 0;}
.content-container .nav-right .classify-item.active {background-color: #000 !important;color: #fff !important;}
.material-columns{column-count:2;column-gap:16rpx;}
.material-item{break-inside:avoid;background-color:#fff;margin-bottom:20rpx;box-shadow:0 2rpx 10rpx rgba(0,0,0,0.1);overflow:hidden}
.material-pic .image{width:100%;display:block}
.material-name{padding:10rpx;font-size:22rpx;color:#333;}
.imagetext .imagetext-item{width:100%;display:flex;flex-direction:column;background: #fff;margin-bottom: 40rpx;box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);}
.imagetext .imagetext-item .top{width:100%;display:flex;align-items:center;line-height: 35rpx;}
.imagetext .imagetext-item .top .head .f1{width:70rpx;height:70rpx;border-radius:50%;margin-right:16rpx}
.imagetext .imagetext-item .top .head .f2 .t1{color:#222;font-size:28rpx}
.imagetext .imagetext-item .top .head .f2 .t2{color:#bbb;font-size:22rpx;}
.imagetext .imagetext-item .con{width:100%;padding:24rpx 0 45rpx;display:flex;flex-direction:column;color:#000;}
.imagetext .imagetext-item .con .f2{margin-top:10rpx;display:flex;flex-wrap:wrap;gap: 8rpx;}
.imagetext .imagetext-item .con .f2 .pic-box{width:31.5% !important;flex-shrink: 0;object-fit: cover;height: 160rpx;overflow: hidden;}
.imagetext .imagetext-item .con .f2 image{width: 100%;height: 100%;}
.imagetext .imagetext-item .bot{width:100%;display:flex;align-items:center;color:#222222;font-size:28rpx;padding: 20rpx 0;border-top:1px solid #f1f1f1;}
.imagetext .imagetext-item .bot .f1{display:flex;align-items:center;font-weight:bold}
.imagetext .imagetext-item .bot .f1 image{width:36rpx;height:36rpx;margin-right:16rpx;margin-top:2px}
.imagetext .imagetext-item .bot .f2{flex:1;}
.imagetext .imagetext-item .bot .f4{display:flex;align-items:center;justify-content: center;width: 33%}
.imagetext .imagetext-item .bot .f4 image{width:30rpx;height:30rpx;}
.text-truncate {display: -webkit-box;-webkit-line-clamp: 10;-webkit-box-orient: vertical;overflow: hidden; text-overflow: ellipsis;line-height: 40rpx;}
.view-more {color: #576b95;font-size: 26rpx;margin-stop: 20rpx;text-align: left;}

::-webkit-scrollbar {
  display: none;
  width: 0;
  height: 0;
  color: transparent;
}
.dp-tabbar {position: absolute !important;bottom: 0 !important;}
</style>