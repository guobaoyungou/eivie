<template>
<view>
	<block v-if="isload">
		<view class="container2">
			<image :src="sysset.pic" style="width:100%;height:auto" mode="widthFix" v-if="sysset.pic" @tap="goto" :data-url="sysset.picurl"></image>
			<view class="navbox" v-if="clist.length>0">
				<block v-for="(item, index) in clist" :key="index">
				<view style="cursor:pointer" @tap="goto" :data-url="'ltlist?cid=' + item.id" class="nav_li">
					<image :src="item.pic"></image>
					<view>{{item.name}}</view>
				</view>
				</block>
			</view>
		</view>
		<view style="display:flex;width:100%;height:16rpx;background:#F7F7F7"></view>
		<view class="container2">
			<view class="listtitle">最新动态</view>
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
					<!-- 表单区域 -->
					<block v-if="item.form"> 
							<view class="dp-form-item" v-for="(subitem, sindex) in item.form.formcontent" :key="sindex" v-if="item.form['form'+sindex]">
								<block v-if="subitem.key!='upload' && subitem.key!='upload_pics'">
									<view>{{item.form['form'+sindex]}}</view>
								</block>
		<!-- 						<block v-if="subitem.key=='upload'">
									<view class="flex">
										<view class="dp-form-imgbox">
											<view class="dp-form-imgbox-img"><image class="image" :src="item.form['form'+sindex]" @click.stop="previewImage" :data-url="item.form['form'+sindex]" mode="aspectFit" /></view>
										</view>
									</view>
								</block> -->
						    <block v-if="subitem.key=='upload_pics' || subitem.key=='upload'">
						    	<view class="flex">
						    		<view v-for="(item2,index2) in item.form['form'+sindex]" :key="index2" class="dp-form-imgbox" v-if="item2">
						    			<view class="dp-form-imgbox-img">
												<image class="image" :src="item2" @click.stop="previewImage" :data-url="item2" mode="heightFix"/>
											</view>
						    		</view>
						    	</view>
						    </block>
							</view>
					</block>
					<view class="f1" v-if="item.luntan_content_ueditor"><rich-text :nodes="item.content" style="word-wrap: break-word;"></rich-text></view>
          <view class="f1" v-else><text style="white-space:pre-wrap;">{{item.content}}</text></view>
					<view class="f2" v-if="item.pics">
						<image v-for="(pic, idx) in item.pics" :key="idx" :src="pic"></image>
					</view>
					<video class="video" id="video" :src="item.video" v-if="item.video" @tap.stop="playvideo"></video>
				</view>
				<view class="phone" v-if="item.isshowphone">
					<view class="f1"><label class="t1">姓名：</label>{{item.name}}</view>
					<block v-if="isphoneother">
						<view class="f1" v-if="item.mobile"><label class="t1">手机号：</label>{{item.mobile}}</view>
						<view class="f1" v-if="item.qq"><label class="t1">QQ：</label>{{item.qq}}</view>
						<view class="f1" v-if="item.wechat"><label class="t1">微信号：</label>{{item.wechat}}</view>
					</block>
					<block v-else>
						<view class="f1"><label class="t1">手机号：</label>{{item.mobile}}</view>
					</block>
				</view>
				<view class="bot">
					<view class="f1"><image :src="pre_url+'/static/img/lt_read.png'" style="margin-top:0"></image>{{item.readcount}}</view>
					<view class="f1" style="margin-left:60rpx;"><image :src="pre_url+'/static/img/lt_pinglun.png'"></image>{{item.plcount}}</view>
                    <view class="f1" v-if="need_call && item.mobile" @tap.stop="callphone" :data-phone="item.mobile" style="margin-left:60rpx;">
                        <image :src="pre_url+'/static/img/mobile.png'" style="width: 30rpx;height: 30rpx;margin-top: 0;"></image>
                    </view>
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
		<view class="covermy-view flex-col" :class="menuindex>-1?'tabbarbot':'notabbarbot'">
			<view class="covermy" @tap="goto" data-url="pages/index/index"><image :src="pre_url+'/static/img/lt_gohome.png'"></image></view>
			<view class="covermy" v-if="sysset && sysset.sendcheck!=2" @tap="goto" data-url="fatie"><image :src="pre_url+'/static/img/lt_fatie2.png'"></image></view>
		</view>
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
			
			sysset:{},
      datalist: [],
			clist:[],
			title:'',
      pagenum: 1,
      keyword: '',
      nomore: false,
			nodata:false,
			picindex:0,
            need_call:false,
			is_back:0,
			scrollTop: '', // 滚动距离
			top: '', // 返回时距离顶部
			formcontent:[{key: "input"},{key: "textarea"},{key: "radio"},{key: "checkbox"},{key: "selector"},{key: "time"},{key: "date"},{key: "year"},{key: "region"},{key: "upload"},{key: "upload_pics"}],
			isphoneother: false,
    };
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
  onPageScroll: function (e) {
		if (!this.is_back) {
			var PageScroll = {
				page: this.pagenum,
				scrollTop: e.scrollTop
			}
			uni.setStorageSync('onPageScroll', JSON.stringify(PageScroll))
		}
	},
  onShow() {
	 if (this.is_back) {
		var that = this;
		var data = {}
		if (uni.getStorageSync('onPageScroll')) {
			data = JSON.parse(uni.getStorageSync('onPageScroll'));
		} else {
			data = {
				page: 1,
				scrollTop: 0,
			}
		}
		this.top = data.scrollTop;
		this.pagenum = 1;
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
      app.post('ApiLuntan/index', {pagenum: pagenum}, function (res) {
		that.loading = false;
        if(res.need_call){
            that.need_call = true;
        }
        var data = res.datalist;
        if (pagenum == 1) {
            uni.setNavigationBarTitle({
                title: res.title
            });
            that.datalist = res.datalist;
            that.sysset = res.sysset;
            that.title = res.title;
            that.clist = res.clist;
            that.isphoneother = res.isphoneother || 0;
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
		//阅读后返回调用，更新列表阅读数量
		if (that.is_back) {
			var timer = setTimeout(() => {
				var data = {}
				if (uni.getStorageSync('onPageScroll')) {
					data = JSON.parse(uni.getStorageSync('onPageScroll'))
				} else {
					data = {
						page: 1,
						scrollTop: 0
					}
				}

				if (that.page >= data.page) {
					uni.pageScrollTo({
						scrollTop: that.top,
					})
					clearTimeout(timer);
					that.is_back = false;
					uni.setStorageSync('onPageScroll', '');
				} else {
					that.pagenum++;
					that.is_back = true;
				}
			})
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
        callphone:function(e) {
        	var phone = e.currentTarget.dataset.phone;
        	uni.makePhoneCall({
        		phoneNumber: phone,
        		fail: function () {
        		}
        	});
        },
  }
};
</script>
<style>
.container2{width:100%;padding:20rpx;background:#fff;}
.navbox{background: #fff;height: auto;overflow: hidden;}
.nav_li{width:25%;text-align: center;box-sizing: border-box;padding:30rpx 0 10rpx;float: left;color:#222;font-size:24rpx}
.nav_li image{width:80rpx;height: 80rpx;margin-bottom:10rpx;}

.listtitle{width:100%;padding:0 24rpx;color:#222;font-weight:bold;font-size:32rpx;height:60rpx;line-height:60rpx}
.datalist{width:100%;padding:0 24rpx;}
.datalist .item{width:100%;display:flex;flex-direction:column;padding:24rpx 0;border-bottom:1px solid #f1f1f1}
.datalist .item .top{width:100%;display:flex;align-items:center}
.datalist .item .top .f1{width:80rpx;height:80rpx;border-radius:50%;margin-right:16rpx;}
.datalist .item .top .f2 .t1{color:#222;font-weight:bold;font-size:28rpx}
.datalist .item .top .f2 .t2{color:#bbb;font-size:24rpx}
.datalist .item .con{width:100%;padding:24rpx 0;display:flex;flex-direction:column;color:#000}
.datalist .item .con .f2{margin-top:10rpx;display:flex;flex-wrap:wrap}
.datalist .item .con .f2 image{width:200rpx;height:200rpx;margin-right:2%;margin-bottom:10rpx;border-radius:8rpx}
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
.covermy-view{position:fixed;z-index:99999;bottom:0;right:20rpx;width:126rpx;height: 250rpx;box-sizing:content-box;justify-content: space-between;margin-bottom: 140rpx;}
.covermy{width:126rpx;height:126rpx;box-sizing:content-box;}
.covermy image{width:100%;height:100%;}

.nomore-footer-tips{background:#fff!important}
.phone .f1{line-height: 60rpx;display: flex;}
.phone .f1 label{ color: #999; width: 120rpx;}
/*  */
.dp-form-item{width: 100%;display:flex;align-items: center;}
.dp-form-imgbox{margin-right:16rpx;font-size:24rpx;position: relative;}
.dp-form-imgbox-img{display: block;;overflow:hidden;}
.dp-form-imgbox-img>.image{width: 200rpx;height: 200rpx;margin-right: 2%;margin-bottom: 10rpx;border-radius: 8rpx;}
</style>