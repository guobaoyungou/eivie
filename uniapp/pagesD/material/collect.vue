<template>
	<block v-if="isload">
	  <view class="container">
			<dd-tab :itemdata="['图片','图文']" :itemst="['1','4']" :st="st" @changetab="changetab"></dd-tab>
      <view class="item-list">
        <!-- 图片收藏 -->
        <view class="material-columns" v-if="st == 1">
          <view class="material-item" v-for="(item,index) in datalist" :key="item.id" @click="goto" :data-url="'details?id='+item.id">
            <view class="material-pic">
              <image class="image" :src="item.url" mode="widthFix" />
            </view>
            <view class="material-name">{{item.name}}</view>
          </view>
        </view>

        <!-- 图文收藏 -->
        <view class="imagetext" v-if="st == 4">
          <view class="imagetext-item" v-for="(item,index) in datalist" :key="item.id">
            <view style="padding: 20rpx;">
              <view class="top flex-bt">
                <view class="head flex">
                  <image :src="item.set_logo" class="f1"></image>
                  <view class="f2">
                    <view class="t1">{{item.set_name}}</view>
                    <view class="t2">{{item.createtime}}</view>
                  </view>
                </view>
              </view>
              <view class="con">
                <view class="f1"><text style="white-space:pre-wrap;font-size: 24rpx" :class="{'text-truncate': !item.showFullText}">{{item.content}}</text></view>
                <view v-if="item.content && item.content.length > 100 && !item.showFullText" class="view-more" @tap="toggleFullText(index)">
                  查看全文
                </view>
                <view class="f2" v-if="item.pics">
                  <view class="pic-box" v-for="(pic, idx) in item.pics" :key="idx">
                    <image :src="pic" mode="widthFix" @tap="previewImage" :data-url="pic"></image>
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
      </view>
      <nomore text="————End————" v-if="nomore"></nomore>
      <nodata text="暂无收藏内容" v-if="nodata"></nodata>
    </view>
	</block>
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
				st: 1,
				datalist: [],
				pagenum: 1,
				nomore: false,
				nodata: false,
				pre_url:app.globalData.pre_url,
			}
		},
		onLoad: function (opt) {
			this.opt = app.getopts(opt);
			this.getdata();
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
			changetab: function (st) {
			  this.st = st;
			  uni.pageScrollTo({
			    scrollTop: 0,
			    duration: 0
			  });
			  this.getdata();
			},
			getdata: function (loadmore) {
				if(!loadmore){
					this.pagenum = 1;
					this.datalist = [];
				}
			  var that = this;
			  var pagenum = that.pagenum;
			  var st = that.st;
				that.nodata = false;
				that.nomore = false;
				that.loading = true;
			  app.post('ApiMaterial/collectlist', {st: st,pagenum: pagenum}, function (res) {
					that.loading = false;
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
			  });
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
      toggleFullText(index) {
        this.$set(this.datalist[index], 'showFullText', true);
      },
		}
	}
</script>

<style>
.item-list{padding: 20rpx}
.item-list .material-columns {column-count:3;column-gap:20rpx;}
.item-list .material-item {position:relative;margin-bottom: 8rpx;break-inside: avoid;background-color:#fff;}
.item-list .material-item image{ width: 100%; height: 100%; }
.item-list .material-item .material-name{ padding:10rpx;font-size:24rpx;color:#333;text-overflow:ellipsis }

.imagetext .imagetext-item{width:100%;display:flex;flex-direction:column;background: #fff;margin-bottom: 40rpx;}
.imagetext .imagetext-item .top{width:100%;display:flex;align-items:center;line-height: 40rpx;}
.imagetext .imagetext-item .top .head .f1{width:70rpx;height:70rpx;border-radius:50%;margin-right:16rpx}
.imagetext .imagetext-item .top .head .f2 .t1{color:#222;font-size:28rpx}
.imagetext .imagetext-item .top .head .f2 .t2{color:#bbb;font-size:24rpx;font-weight: bold;}
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
.text-truncate {display: -webkit-box;-webkit-line-clamp: 10;-webkit-box-orient: vertical;overflow: hidden; text-overflow: ellipsis;}
.view-more {color: #576b95;font-size: 26rpx;margin-top: 20rpx;text-align: left;}
</style>
