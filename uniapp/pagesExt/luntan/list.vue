<template>
<view style="width: 100%;height: 100%;">
	<block v-if="isload">
      <view v-if="cpid>0" style="width: 100%;background: #fff;padding: 20rpx 0;overflow: hidden;">
        <view v-for="(item, index) in clist" :key="index" style="display: inline-block;line-height: 70rpx;padding:10rpx 20rpx;">
            <view @tap="changeCid" :data-cid="item.id" class="cname" :style="cid == item.id?'color:#fff;background-color:'+t('color1'):'color:#000;background-color:#f4f4f4'">
                {{item.name}}
            </view>
        </view >
      </view>
		<view class="topsearch flex-y-center">
            <view class="f1 flex-y-center">
                <image class="img" :src="pre_url+'/static/img/search_ico.png'"></image>
                <input :value="keyword" placeholder="搜索感兴趣的帖子" placeholder-style="font-size:24rpx;color:#C2C2C2" @confirm="searchConfirm"></input>
            </view>
        </view>
        <view class="search-history" v-show="history_show">
        	<view>
        		<text class="search-history-title">最近搜索</text>
        		<view class="delete-search-history" @tap="deleteSearchHistory">
        			<image :src="pre_url+'/static/img/del.png'" style="width:36rpx;height:36rpx"/>
        		</view>
        	</view>
        	<view class="search-history-list">
        		<view v-for="(item, index) in history_list" :key="index" class="search-history-item" :data-value="item" @tap="historyClick">{{item}}
        		</view>
        		<view v-if="!history_list || history_list.length==0" class="flex-y-center"><image :src="pre_url+'/static/img/tanhao.png'" style="width:36rpx;height:36rpx;margin-right:10rpx"/>暂无记录		</view>
        	</view>
        </view>
    
		<view v-if="datalist && datalist.length>0" class="container2" style="border-top: 10rpx solid #f4f4f4;">
			<view class="datalist">
			<block v-for="(item, index) in datalist" :key="index">
				<view class="item" @tap="goto" :data-url="'detail?id=' + item.id">
					<view class="top">
						<image :src="item.headimg" class="f1"></image>
						<view class="f2">
							<view class="t1">{{item.nickname}}</view>
							<view class="t2">{{item.showtime}}</view>
						</view>
					</view>
					<view class="con">
						<view class="f1"><text style="white-space:pre-wrap;">{{item.content}}</text></view>
						<view class="f2" v-if="item.pics">
							<image v-for="(pic, idx) in item.pics" :key="idx" :src="pic" mode="widthFix" style="height:auto"></image>
						</view>
						<video class="video" id="video" :src="item.video" v-if="item.video" @tap.stop="playvideo"></video>
					</view>
					<view class="bot">
						<view class="f1"><image :src="pre_url+'/static/img/lt_read.png'" style="margin-top:0"></image>{{item.readcount}}</view>
						<view class="f1" style="margin-left:60rpx;"><image :src="pre_url+'/static/img/lt_pinglun.png'"></image>{{item.plcount}}</view>
						<view class="f2"></view>
						<view class="f4" @tap.stop="savecontent" :data-id="item.id" :data-index="index" v-if="sysset.cansave"><image :src="pre_url+'/static/img/lt_save.png'"></image>保存</view>
						<view class="f3" @tap.stop="zan" :data-id="item.id" :data-index="index"><image :src="pre_url+'/static/img/lt_like' + (item['iszan']==0?'':'2') + '.png'"></image>{{item.zan}}</view>
					</view>
				</view>
			</block>
			<nomore v-if="nomore"></nomore>
			<nodata v-if="nodata"></nodata>
			</view>
		</view>
		<view class="covermy" :class="menuindex>-1?'tabbarbot':'notabbarbot'" @tap="goto" :data-url="'/activity/luntan/fatie?cid=' + cpid+'&displaytype=' +displaytype"><image :src="pre_url+'/static/img/lt_fatie.png'"></image></view>
	</block>
	<loading v-if="loading"></loading>
	<dp-tabbar :opt="opt" @getmenuindex="getmenuindex"></dp-tabbar>
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
        pre_url:app.globalData.pre_url,
        pagenum: 1,
        keyword: '',
        nomore: false,
        nodata: false,
        cpid:0,
        sysset:{},
        cid:'',
        datalist: [],
        clist:[],
        displaytype:0,
        history_show:false,
        history_list:[]
    };
  },

  onLoad: function (opt) {
        var that = this;
        var opt  = app.getopts(opt);
        
        that.opt = opt;
        
        var cpid = opt.cpid;
        if(cpid>0){
            that.cpid =cpid;
        }else{
            that.history_list = app.getCache('search_luntan_history');
            that.history_show = true;
        }
        
        that.cid = opt.cid || '';
        that.displaytype = opt.displaytype || '';
		that.getdata();
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
    getdata: function (loadmore) {
        if(!loadmore){
            this.pagenum = 1;
            this.datalist = [];
        }
        var that = this;
        var pagenum = that.pagenum;
        var keyword = that.keyword;
        that.nodata = false;
        that.nomore = false;
        that.loading = true;
        
        app.post('ApiLuntan/list', {pid:that.cpid,cid: that.cid,pagenum: pagenum,keyword:keyword,display_type:that.displaytype}, function (res) {
            that.loading = false;
            that.loaddingmore = false;
            if(res.status == 1){
                var data   = res.datalist;
                
                if(res.history_show){
                    that.history_show = res.history_show;
                }
                if (pagenum == 1) {
                    uni.setNavigationBarTitle({
                        title: res.title
                    });
                    that.pernum = res.pernum;
                    that.datalist = res.datalist;
                    that.sysset = res.sysset;
                    that.title = res.title;
                    if (data && data.length == 0) {
                        that.nodata = true;
                    }
                    that.clist = res.clist;
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
            }else{
                app.alert(res.msg)
            }
            
      });
    },
        zan: function (e) {
            var that = this;
            var id = e.currentTarget.dataset.id;
            var index = e.currentTarget.dataset.index;
            var datalist = that.datalist;
            app.post("ApiLuntan/zan", {id: id}, function (res) {
                if (res.type == 0) {
                    //取消点赞
                    datalist[index].iszan = 0;
                    datalist[index].zan = datalist[index].zan - 1;
                } else {
                    datalist[index].iszan = 1;
                    datalist[index].zan = datalist[index].zan + 1;
                }
                that.datalist = datalist;
            });
        },
		savecontent:function(e){
			var that = this;
			var index = e.currentTarget.dataset.index;
			var info = that.datalist[index];
			that.fuzhi(info.content,function(){
				that.savpic(info.pics,function(){
					that.savevideo(info.video);
				});
			})
		},
		fuzhi:function(content,callback){
			if(!content){
				typeof callback == 'function' && callback();
				return;
			}
			var that = this;
			uni.setClipboardData({
				data: content,
				success: function () {
					app.success('已复制到剪贴板');
					setTimeout(function(){
					typeof callback == 'function' && callback();
					},500)
				},
				fail:function(){
					app.error('请长按文本内容复制');
					setTimeout(function(){
						typeof callback == 'function' && callback();
					},500)
				}
			});
		},
		savpic:function(pics,callback){
			if(!pics){
				typeof callback == 'function' && callback();
				return;
			}
			if(app.globalData.platform == 'mp' || app.globalData.platform == 'h5'){
				app.error('请长按图片保存');return;
			}
			this.picindex = 0;
			this.savpic2(pics);
			typeof callback == 'function' && callback();
		},
		savpic2:function(pics){
			var that = this;
			var picindex = this.picindex;
			if(picindex >= pics.length){
				app.showLoading(false);
				app.success('已保存到相册');
				return;
			}
			var pic = pics[picindex];
			app.showLoading('图片保存中');
			uni.downloadFile({
				url: pic,
				success (res) {
					if (res.statusCode === 200) {
						uni.saveImageToPhotosAlbum({
							filePath: res.tempFilePath,
							success:function () {
								that.picindex++;
								that.savpic2(pics);
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
		savevideo:function(video){
			if(!video) return;
			app.showLoading('视频下载中');
			uni.downloadFile({
				url: video,
				success (res) {
					if (res.statusCode === 200) {
						uni.saveVideoToPhotosAlbum({
							filePath: res.tempFilePath,
							success:function () {
								app.showLoading(false);
								app.success('视频保存成功');
							},
							fail:function(){
								app.showLoading(false);
								app.error('视频保存失败');
							}
						})
					}
				},
				fail:function(){
					app.showLoading(false);
					app.error('视频下载失败!');
				}
			});
		},
        playvideo: function () {},
        changeCid:function(e){
            var that = this;
            that.cid = e.currentTarget.dataset.cid;
            that.getdata()
        },
        deleteSearchHistory: function () {
          var that = this;
          that.history_list = null;
          app.removeCache("search_luntan_history");
        },
        searchConfirm: function (e) {
            var that = this;
            var keyword = e.detail.value;
            that.keyword = keyword
            if(that.cpid >0){
                that.history_show = false;
                that.searchlog();
            }else{
                if(keyword){
                    if(that.history_show){
                        that.addHistory();
                    }
                    that.history_show = false;
                    that.searchlog();
                }else{
                    that.pagenum = 1;
                    that.datalist = [];
                    that.history_show = true;
                }
            }
        },
        searchlog: function () {
            var that = this;
            that.pagenum = 1;
            that.datalist = [];
            that.getdata();
        },
        addHistory: function () {
          var that = this;
          var keyword = that.keyword;
          console.log(11)
          console.log(keyword)
          if (app.isNull(keyword)) return;
          console.log(22)
          var historylist = app.getCache('search_luntan_history');
          
          if (app.isNull(historylist)) historylist = [];
          console.log(33)

          historylist.unshift(keyword);
          
          var newhistorylist = [];
          for (var i in historylist) {
            if (historylist[i] != keyword || i == 0) {
                newhistorylist.push(historylist[i]);
            }
          }
          console.log(34)
          if (newhistorylist.length > 5) newhistorylist.splice(5, 1);
          console.log(newhistorylist)
          app.setCache('search_luntan_history', newhistorylist);
          that.history_list = newhistorylist
        },
        historyClick: function (e){
            var that = this;
            var keyword = e.currentTarget.dataset.value;
            if (keyword.length == 0) return;
            that.keyword = keyword;
            that.history_show = false;
            that.searchlog();
        },
  }
};
</script>
<style>
page{width: 100%;height: 100%;background-color: #fff;}
.container2{width:100%;padding:20rpx;background:#fff;}

.topsearch{width:100%;padding:20rpx 20rpx;margin-bottom:10rpx;margin-bottom:10rpx;background:#fff}
.topsearch .f1{height:70rpx;border-radius:35rpx;border:0;background-color:#f5f5f5;flex:1}
.topsearch .f1 image{width:30rpx;height:30rpx;margin-left:10px}
.topsearch .f1 input{height:100%;flex:1;padding:0 20rpx;font-size:28rpx;color:#333;}
.search-history {padding: 24rpx 34rpx;}
.search-history .search-history-title {color: #666;}
.search-history .delete-search-history {float: right;padding: 15rpx 20rpx;margin-top: -15rpx;}
.search-history-list {padding: 24rpx 0 0 0;}
.search-history-list .search-history-item {display: inline-block;height: 50rpx;line-height: 50rpx;padding: 0 20rpx;margin: 0 10rpx 10rpx 0;background: #ddd;border-radius: 10rpx;font-size: 26rpx;}

.navbox{background: #fff;height: auto;overflow: hidden;}
.nav_li{width:25%;text-align: center;box-sizing: border-box;padding:30rpx 0 10rpx;float: left;color:#222;font-size:24rpx}
.nav_li image{width:80rpx;height: 80rpx;margin-bottom:10rpx;}

.listtitle{width:100%;padding:0 24rpx;color:#222;font-weight:bold;font-size:32rpx;height:60rpx;line-height:60rpx}
.datalist{width:100%;padding:0 24rpx;}
.datalist .item{width:100%;display:flex;flex-direction:column;padding:24rpx 0;border-bottom:1px solid #f1f1f1}
.datalist .item .top{width:100%;display:flex;align-items:center}
.datalist .item .top .f1{width:80rpx;height:80rpx;border-radius:50%;margin-right:16rpx}
.datalist .item .top .f2 .t1{color:#222;font-weight:bold;font-size:28rpx}
.datalist .item .top .f2 .t2{color:#bbb;font-size:24rpx}
.datalist .item .con{width:100%;padding:24rpx 0;display:flex;flex-direction:column;color:#000}
.datalist .item .con .f2{margin-top:10rpx;display:flex;flex-wrap:wrap}
.datalist .item .con .f2 image{width:31%;margin-right:2%;margin-bottom:10rpx;border-radius:8rpx}
.datalist .item .con .video{width:80%;height:300rpx;margin-top:20rpx}
.datalist .item .bot{width:100%;display:flex;align-items:center;color:#222222;font-size:28rpx}
.datalist .item .bot .f1{display:flex;align-items:center;font-weight:bold}
.datalist .item .bot .f1 image{width:36rpx;height:36rpx;margin-right:16rpx;margin-top:2px}
.datalist .item .bot .f2{flex:1;}
.datalist .item .bot .f3{display:flex;align-items:center;font-weight:bold}
.datalist .item .bot .f3 image{width:40rpx;height:40rpx;margin-right:16rpx}
.datalist .item .bot .f4{display:flex;align-items:center;margin-right:30rpx}
.datalist .item .bot .f4 image{width:40rpx;height:40rpx;margin-right:10rpx}
.datalist .item .bot .btn2{color:#fff;background:#FE1A29;border:1px solid #FE1A29;padding:6rpx 40rpx;font-size:24rpx;border-radius:40rpx;margin-left:16rpx}

.covermy{position:fixed;z-index:99999;bottom:0;right:0;width:130rpx;height:130rpx;box-sizing:content-box}
.covermy image{width:100%;height:100%}

.nomore-footer-tips{background:#fff!important}

.cname{padding: 0 24rpx;border-radius: 8rpx;font-size: 28rpx;}
</style>