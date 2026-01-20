<template>
<view class="container">
	<block v-if="isload">
	<view class="datalist">
		<view class="item">
			<view class="top">
				<image :src="detail.headimg" class="f1"></image>
				<view class="f2">
					<view class="covermy-view flex-col" :class="menuindex>-1?'tabbarbot':'notabbarbot'">
						<view class="covermy" @tap="goto" data-url="pages/index/index"><image :src="pre_url+'/static/img/lt_gohome.png'"></image></view>
						<view class="covermy" @tap="goto" data-url="fatie"><image :src="pre_url+'/static/img/lt_fatie2.png'"></image></view>
					</view>
					<view class="t1">{{detail.nickname}}</view>
					<view class="t2">{{detail.showtime}}</view>
				</view>
			</view>
			<view class="con previewImgContent">
				<!-- 表单区域 -->
				<block v-if="formdata"> 
						<view class="dp-form-item" v-for="(subitem, sindex) in formdata.formcontent" :key="sindex" v-if="formdata['form'+sindex]">
							<block v-if="subitem.key!='upload' && subitem.key!='upload_pics'">
								<view>{{formdata['form'+sindex]}}</view>
							</block>

					    <block v-if="subitem.key=='upload_pics' || subitem.key=='upload'">
					    	<view class="flex">
					    		<view v-for="(item2,index2) in formdata['form'+sindex]" :key="index2" class="dp-form-imgbox"  v-if="item2">
					    			<view class="dp-form-imgbox-img">
											<image class="image" :src="item2" @click="previewImage" :data-url="item2" mode="heightFix" />
										</view>
					    		</view>
					    	</view>
					    </block>
						</view>
				</block>
				<view class="f1" v-if="detail.luntan_content_ueditor"><rich-text :nodes="detail.content" style="word-wrap: break-word;"></rich-text></view>
        <view class="f1" v-else><text style="white-space:pre-wrap;" user-select="true" selectable="true">{{detail.content}}</text></view>
        <view class="f2" v-if="detail.pics">
					<image v-for="(pic, idx) in detail.pics" :key="idx" :src="pic" @tap="previewImage" :data-url="pic" :data-urls="detail.pics"></image>
				</view>
				<video class="video" id="video" :src="detail.video" v-if="detail.video"></video>
			</view>
			
			<view class="phone" v-if="detail.isshowphone">
				<view class="f1" v-if="detail.name"><label class="t1">姓名：</label>{{detail.name}}<view class="copy-btn" @click="copy" :data-text='detail.name'>复制</view></view>
				<view class="f1" v-if="detail.mobile"><label class="t1">手机号：</label>{{detail.mobile}}<view class="copy-btn" @click="copy" :data-text='detail.mobile'>复制</view></view>
			</view>
			
		</view>
		<view class="flex">
		<!-- 	<view style="color:#507DAF;font-size:28rpx;font-weight:800;height:60rpx;line-height:60rpx;margin-right:20rpx">点击右下角去曝光</view> -->
			<view v-if="detail.mid == mid" style="color:#507DAF;font-size:28rpx;height:60rpx;line-height:60rpx;margin-right:20rpx" @tap="deltie" :data-id="detail.id">删除</view>
			<view style="color:#aaa;font-size:28rpx;height:60rpx;line-height:60rpx;margin-right:20rpx">阅读 {{detail.readcount}}</view>
            <view class="f1" v-if="need_call && detail.mobile" @tap.stop="callphone" :data-phone="detail.mobile" style="margin-left:60rpx;height: 30px;line-height: 30px;overflow: hidden;">
                <image :src="pre_url+'/static/img/mobile.png'" style="width: 30rpx;height: 30rpx;float: left;margin-top: 12rpx;"></image>
                <text style="margin-left: 10rpx;">拨打电话</text>
            </view>
		</view>
	</view>

	<!--评论-->
	<view class="plbox">
		<view class="plbox_title"><text class="t1">评论</text><text>{{plcount}}</text></view>
		<view class="plbox_content" id="datalist">
			<block v-for="(item, idx) in datalist" :key="idx">
			<view class="item1 flex">
				<view class="f1 flex0"><image :src="item.headimg"></image></view>
				<view class="f2 flex-col">
					<text class="t1">{{item.nickname}}</text>
					<text class="t11">{{item.createtime}}</text>
					<view class="t2 plcontent"><parse :content="item.content" /></view>
					<block v-if="item.replylist.length>0">
					<view class="relist">
						<block v-for="(hfitem, index) in item.replylist" :key="index">
						<view class="item2">
							<view class="f1">{{hfitem.nickname}}<text class="t1">{{hfitem.createtime}}</text>
								<text v-if="hfitem.mid==mid" class="phuifu" style="font-size:20rpx;margin-left:20rpx;font-weight:normal" @tap="delplreply" :data-id="hfitem.id">删除</text>
							</view>
							<view class="f2 plcontent"><parse :content="hfitem.content" /></view>
						</view>
						 </block>
					</view>
          </block>
					<view class="t3 flex">
						<view class="flex1">
							<text v-if="pinglunstatus" class="phuifu" style="cursor:pointer" @tap="goto" :data-url="'pinglun?type=1&id=' + detail.id + '&hfid=' + item.id">回复</text>
							<text v-if="item.mid==mid" class="phuifu" style="cursor:pointer;margin-left:20rpx" @tap="delpinglun" :data-id="item.id">删除</text>
						</view>
						<view class="flex-y-center pzan" @tap="pzan" :data-id="item.id" :data-index="idx"><image :src="pre_url+'/static/img/lt_like' + (item.iszan==1?'2':'') + '.png'"></image>{{item.zan}}</view>
					</view>
				</view>
			</view>
      </block>
		</view>
	</view>
	<view style="height:100rpx"></view>
	<view class="pinglun" v-if="pinglunstatus">
		<view class="pinput" @tap="goto" :data-url="'pinglun?type=0&id=' + detail.id">发表评论</view>
		<!-- #ifdef H5 -->
		<view class="zan flex-y-center share" @tap="sharemp" v-if="getplatform() == 'mp'">
			<image :src="pre_url+'/static/img/share.png'"></image><text style="padding-left:2px">分享</text>
		</view>
		<!-- #endif -->
		<!-- #ifndef H5 -->
		<button class="zan flex-y-center share" open-type="share" v-if="getplatform() != 'h5'">
			<image :src="pre_url+'/static/img/share.png'"></image><text style="padding-left:2px">分享</text>
		</button>
		<!-- #endif -->
		<view class="zan flex-y-center" @tap="zan" :data-id="detail.id">
			<image :src="pre_url+'/static/img/lt_like' + (iszan?'2':'') + '.png'"></image><text style="padding-left:2px">{{detail.zan}}</text>
		</view>
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
			pre_url:app.globalData.pre_url,
      isload: false,
			menuindex:-1,
			
			detail:{},
      datalist: [],
      pagenum: 1,
      id: 0,
      nomore: false,
      iszan: "",
			title: "",
			sharepic: "",
			mid:'',
			need_call:false,
			pinglunstatus:true,
			formdata:{},
    };
  },
  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.id = this.opt.id || 0;
		var pages = getCurrentPages();
		var prevPage = pages[pages.length - 2];
		if(prevPage && prevPage.route=='activity/luntan/index'){
			prevPage.$vm.is_back = true;
		}
		this.getdata();
  },
	onShareAppMessage:function(){
		return this._sharewx({title:this.detail.content,pic:this.detail.pics[0]});
	},
	onShareTimeline:function(){
		var sharewxdata = this._sharewx({title:this.detail.content,pic:this.detail.pics[0]});
		var query = (sharewxdata.path).split('?')[1]+'&seetype=circle';
		console.log(sharewxdata)
		console.log(query)
		return {
			title: sharewxdata.title,
			imageUrl: sharewxdata.imageUrl,
			query: query
		}
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
			that.loading = true;
			that.nodata = false;
			that.nomore = false;
      app.post('ApiLuntan/detail', {pagenum: pagenum,id: that.id}, function (res) {
		that.loading = false;
        if(res.need_call){
            that.need_call = true;
        }
				that.pinglunstatus = res.pinglunstatus;
        var data = res.datalist;
				that.formdata = res.form
        if (pagenum == 1) {
            that.mid = res.mid;
            that.datalist = res.datalist;
            that.plcount = res.plcount;
            that.iszan = res.iszan;
            that.detail = res.detail;
          that.myscore = res.myscore;
          if (data.length == 0) {
            that.nodata = true;
          }
					that.loaded({title:res.detail.content,pic:res.detail.pics[0]});
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
    zan: function (e) {
      var that = this;
      var id = e.currentTarget.dataset.id;
      app.post("ApiLuntan/zan", {id: id}, function (res) {
        if (res.type == 0) {
          //取消点赞
          var iszan = 0;
        } else {
          var iszan = 1;
        }
        that.iszan = iszan;
				that.detail.zan = res.zancount;
      });
    },
    pzan: function (e) {
      var that = this;
      var id = e.currentTarget.dataset.id;
      var index = e.currentTarget.dataset.index;
      var datalist = that.datalist;
      app.post("ApiLuntan/pzan", {id: id}, function (res) {
        if (res.type == 0) {
          //取消点赞
          var iszan = 0;
        } else {
          var iszan = 1;
        }
        datalist[index].iszan = iszan;
        datalist[index].zan = res.zancount;
        that.datalist = datalist;
      });
    },
    showpinglun: function () {},
    deltie: function (e) {
      var that = this;
      var id = e.currentTarget.dataset.id;
      app.confirm('确定要删除吗?', function () {
        app.post("ApiLuntan/deltie", {id: id}, function (res) {
          app.success(res.msg);
          setTimeout(function () {
            app.goback(true);
          }, 1000);
        });
      });
    },
    delpinglun: function (e) {
      var that = this;
      var id = e.currentTarget.dataset.id;
      app.confirm('确定要删除吗?', function () {
        app.post("ApiLuntan/delpinglun", {id: id}, function (res) {
          app.success(res.msg);
          setTimeout(function () {
            that.onLoad();
          }, 1000);
        });
      });
    },
    delplreply: function (e) {
      var that = this;
      var id = e.currentTarget.dataset.id;
      app.confirm('确定要删除吗?', function () {
        app.post("ApiLuntan/delplreply", {id: id}, function (res) {
          app.success(res.msg);
          setTimeout(function () {
            that.onLoad();
          }, 1000);
        });
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
    sharemp:function(){
      app.error('点击右上角发送给好友或分享到朋友圈');
    }
  }
};
</script>
<style>
.datalist{width:100%;padding:0 24rpx;background:#fff}
.datalist .item{width:100%;display:flex;flex-direction:column;padding:24rpx 0;}
.datalist .item .top{width:100%;display:flex;align-items:center}
.datalist .item .top .f1{width:80rpx;height:80rpx;border-radius:50%;margin-right:16rpx}
.datalist .item .top .f2 .t1{color:#222;font-weight:bold;font-size:28rpx}
.datalist .item .top .f2 .t2{color:#bbb;font-size:24rpx}
.datalist .item .con{width:100%;padding:24rpx 0;display:flex;flex-direction:column;color:#000}
.datalist .item .con .f2{margin-top:10rpx;display:flex;flex-wrap:wrap}
.datalist .item .con .f2 image{width:200rpx;height:200rpx;margin-right:2%;margin-bottom:10rpx;border-radius:8rpx}
.datalist .item .con .video{width:80%;height:300rpx;margin-top:20rpx}

.pinglun{width:100%;max-width:750px;margin:0 auto;position:fixed;display:flex;align-items:center;bottom:0;left:0;right:0;height:100rpx;background:#fff;z-index:995;border-top:1px solid #f5f5f5;padding:0 20rpx;}
.pinglun .pinput{flex:1;color:#a5adb5;font-size:32rpx;padding:0;line-height:100rpx}
.pinglun .zan{padding:0 12rpx;line-height:100rpx;font-size:32rpx}
.pinglun .zan image{width:48rpx;height:48rpx;margin-right:16rpx}
.pinglun .buybtn{margin-left:16rpx;background:#31C88E;height:72rpx;line-height:72rpx;padding:0 20rpx;color:#fff;border-radius:6rpx}

.plbox{width:100%;padding:20rpx 20rpx;background:#fff;margin-top:10px}
.plbox_title{font-size:28rpx;height:60rpx;line-height:60rpx;margin-bottom:20rpx;color:#222;font-weight:bold}
.plbox_title .t1{margin-right:16rpx}
.plbox_content .plcontent{vertical-align: middle;color:#111}
.plbox_content .plcontent image{ width:44rpx;height:44rpx;vertical-align: inherit;}
.plbox_content .item1{width:100%;margin-bottom:20rpx}
.plbox_content .item1 .f1{width:80rpx;}
.plbox_content .item1 .f1 image{width:60rpx;height:60rpx;border-radius:50%}
.plbox_content .item1 .f2{flex:1}
.plbox_content .item1 .f2 .t1{color:#222;font-weight:bold}
.plbox_content .item1 .f2 .t11{color:#999999;font-size:20rpx}
.plbox_content .item1 .f2 .t2{color:#000;margin:10rpx 0;line-height:60rpx;}
.plbox_content .item1 .f2 .t3{color:#999;font-size:20rpx}
.plbox_content .item1 .f2 .pzan image{width:32rpx;height:32rpx;margin-right:16rpx}
.plbox_content .item1 .f2 .phuifu{color:#507DAF;font-size:24rpx}
.plbox_content .relist{width:100%;background:#F6F5F8;padding:4rpx 20rpx;margin-bottom:20rpx}
.plbox_content .relist .item2{font-size:24rpx;margin-bottom:10rpx}
.plbox_content .relist .item2 .f1{font-weight:bold;color:#222;width:100%}
.plbox_content .relist .item2 .f1 .t1{font-weight:normal;color:#999999;font-size:20rpx;padding-left:20rpx}
.covermy-view{position:fixed;z-index:99999;bottom:0;right:20rpx;width:126rpx;height: 250rpx;box-sizing:content-box;justify-content: space-between;margin-bottom: 140rpx;}
.covermy{width:126rpx;height:126rpx;box-sizing:content-box;}
.covermy image{width:100%;height:100%}

.phone .f1{line-height: 60rpx;display: flex;align-items: center;}
.phone .f1 label{ color: #999; width: 120rpx;}
.phone .f1 .copy-btn{height:45rpx;line-height:45rpx;color:#333;background:#fff;border:1px solid #cdcdcd;border-radius:3px;text-align:center;padding: 0 15rpx;flex-shrink: 0;margin: 0 0 0 20rpx;font-size:24rpx;}
/*  */
.dp-form-item{width: 100%;display:flex;align-items: center;}
.dp-form-imgbox{margin-right:16rpx;font-size:24rpx;position: relative;}
.dp-form-imgbox-img{display: block;;overflow:hidden;}
.dp-form-imgbox-img>.image{width: 200rpx;height: 200rpx;margin-right: 2%;margin-bottom: 10rpx;border-radius: 8rpx;}
</style>