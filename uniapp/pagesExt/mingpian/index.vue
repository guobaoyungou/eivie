<template>
	<view v-if="isload">
		<view class="banner" :style="bgbtncolor == 1?'background:'+t('color1'):''"></view>
		<view class="page">
			<view class="data" :style="{'background':info.bgpic ? 'url('+info.bgpic+')' :info.bgcolor?info.bgcolor: '#fff','background-size':'100%'}" v-if="!mingpian_banner">
				<view class="data_info" :style="field_list2?'':'border:0;margin:0'">
					<img class="data_head" :src="info.headimg" alt=""/>
					<view>
						<view class="data_name">{{info.realname}}</view>
						<view class="data_text" v-if="info.touxian1">{{info.touxian1}}</view>
						<view class="data_text" v-if="info.touxian2">{{info.touxian2}}</view>
						<view class="data_text" v-if="info.touxian3">{{info.touxian3}}</view>
					</view>
				</view>
        <view @tap="gotourl" data-url="edit" v-if="info.mid == mid" class="data_tag iconfont iconbianjiwenjian" :style="bgbtncolor == 1?'color:'+t('color1'):''"/>

				<view class="data_list" v-for="(item,index) in field_list2">
					<img v-if="index == 'tel'" src="../static/images/tel.png" alt=""/>
					<img v-else-if="index == 'weixin' && item.isshow==1" :src="pre_url+'/static/img/weixin.png'" alt=""/>
					<img v-else-if="index == 'address'" src="../static/images/address.png" alt=""/>
					<text v-if="info[index]">{{info[index]}}</text>
				</view>
			</view>
			<view v-else class="swiper-view-content">
				<view class="data-view">
					<view class="data_info" :style="field_list2?'':'border:0;margin:0'">
						<img class="data_head" :src="info.headimg" alt=""/>
						<view>
							<view class="data_name">{{info.realname}}</view>
							<view class="data_text" v-if="info.touxian1">{{info.touxian1}}</view>
							<view class="data_text" v-if="info.touxian2">{{info.touxian2}}</view>
							<view class="data_text" v-if="info.touxian3">{{info.touxian3}}</view>
						</view>
					</view>
				  <view @tap="gotourl" data-url="edit" v-if="info.mid == mid" class="data_tag iconfont iconbianjiwenjian" :style="bgbtncolor == 1?'color:'+t('color1'):''"/>
					<view class="data_list" v-for="(item,index) in field_list2">
						<img v-if="index == 'tel'" src="../static/images/tel.png" alt=""/>
						<img v-else-if="index == 'weixin' && item.isshow==1" :src="pre_url+'/static/img/weixin.png'" alt=""/>
						<img v-else-if="index == 'address'" src="../static/images/address.png" alt=""/>
						<text v-if="info[index]">{{info[index]}}</text>
					</view>
				</view>
				<view class="swiper-view">
					<swiper class="swiper-class" autoplay :interval='3000' circular>
						<block v-for="(item,index) in info.bgpics">
							<swiper-item>
								<view class="swiper-item-img">
									<image :src="item"></image>
								</view>
							</swiper-item>
						</block>
					</swiper>
				</view>
			</view>

			<view class="module">
				<view class="module_item" @tap="addfavorite" v-if="mid != info.mid">
          <view class="module_img module_img3 iconfont iconicon-download" :style="bgbtncolor == 1?'color:'+t('color1'):''"/>
					<view class="module_text">存{{mingpiantext}}夹</view>
				</view>
				<view class="module_item" @tap="gotourl" data-url="favorite">
          <view class="module_img module_img3 iconfont icontongxunlutongxunbumingce" :style="bgbtncolor == 1?'color:'+t('color1'):''"/>
					<view class="module_text">{{mid != info.mid ? '我的'+mingpiantext+'夹' : mingpiantext+'夹'}}</view>
				</view>
				<view class="module_item" @tap="shareapp" v-if="getplatform() == 'app'">
					<view class="module_img iconfont iconwode-jiaoyouqun" :style="bgbtncolor == 1?'color:'+t('color1'):''"/>
					<view class="module_text">分享{{mingpiantext}}</view>
				</view>
				<view class="module_item" @tap="sharemp" v-else-if="getplatform() == 'mp'">
					<view class="module_img iconfont iconwode-jiaoyouqun" :style="bgbtncolor == 1?'color:'+t('color1'):''"/>
					<view class="module_text">分享{{mingpiantext}}</view>
				</view>
				<view class="module_item" @tap="sharemp" v-else-if="getplatform() == 'h5'">
					<view class="module_img iconfont iconwode-jiaoyouqun" :style="bgbtncolor == 1?'color:'+t('color1'):''"/>
					<view class="module_text">分享{{mingpiantext}}</view>
				</view>
				<button class="module_item" open-type="share" v-else>
					<view class="module_img iconfont iconwode-jiaoyouqun" :style="bgbtncolor == 1?'color:'+t('color1'):''"/>
					<view class="module_text">分享{{mingpiantext}}</view>
				</button>
				<view class="module_item" @tap="gotourl" :data-url="'readlog?id='+info.id" v-if="mid == info.mid" style="position: relative;">
					<view class="module_img module_img2 iconfont iconlishijilu" :style="bgbtncolor == 1?'color:'+t('color1'):''"/>
					<view class="module_text">谁看过</view>
          <view v-if="applyseenum && applyseenum>0" style="color: red;position: absolute;top: -10rpx;right: 20rpx;font-weight: bold;font-size: 24rpx;">{{applyseenum}}</view>
				</view>
				<view class="module_item" @tap="gotourl" :data-url="'favoritelog?id='+info.id" v-if="mid == info.mid">
					<view class="module_img iconfont iconicon_shoucangjia" :style="bgbtncolor == 1?'color:'+t('color1'):''"/>
					<view class="module_text">谁收藏了</view>
				</view>
				<!-- #ifdef APP-PLUS || MP-WEIXIN || MP-ALIPAY || MP-BAIDU -->
				<view class="module_item" @tap="addPhoneContact">
					<view class="module_img iconfont iconfangwen-baocun" :style="bgbtncolor == 1?'color:'+t('color1'):''"/>
					<view class="module_text">存通讯录</view>
				</view>
				<!-- #endif -->
				<view class="module_item" @tap="showPoster" v-if="open_haibao == 1 && mid == info.mid">
					<view class="module_img iconfont icona-huaban2721" :style="bgbtncolor == 1?'color:'+t('color1'):''"/>
					<view class="module_text">分享海报</view>
				</view>
			</view>
      <block v-if="showposter">
        <view class="posterDialog"></view>
        <view class="posterDialog" style="background: unset;z-index: 10;">
          <view class="main">
            <view class="close" @tap="posterDialogClose"><image class="img" :src="pre_url+'/static/img/close.png'"/></view>
            <view class="content">
              <image class="img" :src="posterpic" mode="widthFix" @tap="previewImage" :data-url="posterpic"></image>
            </view>
          </view>
        </view>
      </block>
			
			<view class="list">
				<view class="list_title">
					{{lianxitext}}
				</view>
				<view class="list_item" v-for="(item,index,idx) in field_list">
					<img class="list_img" :src="item.icon" alt=""/>
					<view class="list_lable">{{item.name}}</view>
					<view class="list_value" v-if="index == 'tel'" @tap="gotourl" :data-url="'tel:'+info[index]">{{info[index]}}</view>
					<view class="list_value" v-else-if="index == 'address' && info.longitude" @tap="openLocation" :data-latitude="info.latitude" :data-longitude="info.longitude" :data-address="info.address">{{info[index]}}</view>
          <view class="list_value" v-else-if="item.isadd && item.isadd == '1'">{{addfields?addfields[index]:''}}</view>
					<view class="list_value" v-else @tap="fuzhi" :data-content="info[index]"><text user-select="true" selectable="true">{{info[index]}}</text></view>
				</view>
        <view v-if="moreaddfields" class="moreaddfields" @tap="applysee">查看更多内容</view>
			</view>
			<view class="person">
				<view class="person_title">
					<view></view>
					<text>个人简介</text>
					<view></view>
				</view>
				<dp :pagecontent="pagecontent"></dp>
			</view>
			
			<view class="opt">
				<view class="opt_module">
					<view class="opt_btn" @tap="goto" data-url="/pages/index/index" data-opentype="reLaunch" :style="bgbtncolor == 1?'color:'+t('color1')+';background:rgba('+t('color1rgb')+',0.1)':''">回首页</view>
					<view class="opt_btn" @tap="gotourl" data-url="index" v-if="viewmymp" :style="bgbtncolor == 1?'background:'+t('color1'):''">查看我的{{mingpiantext}}</view>
					<view class="opt_btn" @tap="gotourl" data-url="edit" v-else :style="bgbtncolor == 1?'background:'+t('color1'):''">{{mid == info.mid ? '编辑'+mingpiantext : '创建自己的'+mingpiantext}}</view>
				</view>
			</view>
		</view>
    <view v-if='businessurl' @tap="goto" :data-url="businessurl" class="gobusiness" :style="bgbtncolor == 1?'background:'+t('color1'):''">
      进入主页
    </view>
		<wxxieyi></wxxieyi>
	</view>
</template>

<script>
var app = getApp();
export default {
  data() {
    return {
			isload:false,
			loading:false,
			pre_url:app.globalData.pre_url,
      info:{},
			pagecontent:[],
			field_list:[],
			field_list2:[],
			test:'',
			sharetitle:'',
			sharedesc:'',
			mid:'',
			viewmymp:false,
			posterpic:false,
			showposter:false,
			open_haibao:0,
      islogin:false,
      businessurl:'',
      mingpiantext:'名片',
      lianxitext:'联系信息',
      
      addfields:{},//联系信息增加的字段
      moreaddfields:false,//是否查看更多字段
      applyseenum:0,//申请查看更多字段的数量
      bgbtncolor:0,//背景|按钮颜色 0默认 1跟随系统
      mingpian_banner:false,//背景图轮播
    };
  },

  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.getdata();
  },
	onShareAppMessage:function(){
		return this._sharewx({title:this.sharetitle,link:'/pagesExt/mingpian/index?scene=id_'+this.info.id+'-pid_'+(this.info.mid || app.globalData.mid)});
	},
	onShareTimeline:function(){
		var sharewxdata = this._sharewx({title:this.sharetitle,link:'/pagesExt/mingpian/index?scene=id_'+this.info.id+'-pid_'+(this.info.mid || app.globalData.mid)});
		var query = (sharewxdata.path).split('?')[1]+'&seetype=circle';
		return {
			title: sharewxdata.title,
			imageUrl: sharewxdata.imageUrl,
			query: query
		}
	},
  methods: {
		getdata:function(){
			var that = this;
			var id = that.opt.id ? that.opt.id : '';
			that.loading = true;
			var huomacode = that.opt.huomacode??'';
			app.get('ApiMingpian/index',{id:id,scene:app.globalData.scene,huomacode:huomacode}, function (res) {
				that.loading = false;
        if(res.status == 1){
          that.mingpiantext = that.t('名片');
          that.lianxitext = that.t('联系信息');
          uni.setNavigationBarTitle({
          	title: that.mingpiantext+'详情'
          });

          that.info = res.info;
          that.mid = res.mid;
          that.viewmymp = res.viewmymp;
          that.open_haibao = res.open_haibao;
          that.field_list = res.field_list;
          that.field_list2 = res.field_list2;
          that.mingpian_banner = res.mingpian_banner;
          if(res.islogin){
            that.islogin = res.islogin;
          }
          if(res.businessurl){
            that.businessurl = res.businessurl;
          }
          if(res.bgbtncolor){
            that.bgbtncolor = res.bgbtncolor
          }
          that.pagecontent = res.pagecontent;
          var sharedesc = that.info.realname;
          if(that.info.touxian1) sharedesc += ' '+that.info.touxian1;
          if(that.info.touxian2) sharedesc += ','+that.info.touxian2;
          if(that.info.touxian3) sharedesc += ','+that.info.touxian3;
          if(res.addfields){
            that.addfields = res.addfields;
          }
          if(res.moreaddfields){
            that.moreaddfields = res.moreaddfields;
          }
          if(res.applyseenum){
            that.applyseenum = res.applyseenum;
          }
          that.sharedesc = sharedesc;
          that.sharetitle = that.info.sharetitle || '您好，这是我的'+that.mingpiantext+'，望惠存！';
          var sharelink = app.globalData.pre_url +'/h5/'+app.globalData.aid+'.html#/pagesExt/mingpian/index?scene=id_'+that.info.id+'-pid_' + (that.info.mid || app.globalData.mid);
          that.loaded({title:that.sharetitle,pic:that.info.headimg,desc:sharedesc,link:sharelink});
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
		favorite:function(){
			var that = this;
      if(!that.islogin){
        app.goto('/pages/index/login');
        return;
      }
			if(app.globalData.mid == that.info.mid){
				app.goto('favorite');
			}else{
				uni.showActionSheet({
					itemList: ['存入'+that.mingpiantext+'夹','查看'+that.mingpiantext+'夹'],
					success: function(res) {
						console.log(res.tapIndex)
						if (res.tapIndex == 0) {
							that.addfavorite();
						}else{
							app.goto('favorite');
						}
					}
				});
			}
		},
		addfavorite:function(){
			var that = this;
      if(!that.islogin){
        app.goto('/pages/index/login');
        return;
      }
      app.showLoading('保存中');
      app.post('ApiMingpian/addfavorite',{id:that.info.id},function(res){
        if(res.status== 1){
          app.success(res.msg);
        }else{
          app.error(res.msg);
        }
      })
		},
		async addPhoneContact(){
			var that = this;
      if(!that.islogin){
        app.goto('/pages/index/login');
        return;
      }
			// #ifdef APP-PLUS
			let result =  await app.$store.dispatch("requestPermissions",'READ_CONTACTS');
			if (result !== 1) return;
			let result2 =  await app.$store.dispatch("requestPermissions",'WRITE_CONTACTS');
			if (result2 !== 1) return;
			// #endif
			uni.addPhoneContact({
				firstName: that.info.realname,
				remark: that.info.touxian1,
				mobilePhoneNumber: that.info.tel,
				weChatNumber: that.info.weixin,
				success: function () {
					app.success('添加成功');
				},
				fail: function (res) {
					console.log(res)
					app.error('添加失败,请手动添加');
				}
			});
		},
		fuzhi:function(e){
			var content = e.currentTarget.dataset.content;
			var that = this;
      if(!that.islogin){
        app.goto('/pages/index/login');
        return;
      }
			uni.setClipboardData({
				data: content,
				success: function () {
					app.success('已复制到剪贴板');
				},
				fail:function(err){
					console.log(err)
					app.error('请长按文本内容复制');
				}
			});
		},	
		showPoster: function () {
			var that = this;
      if(!that.islogin){
        app.goto('/pages/index/login');
        return;
      }
			that.showposter = true;
			that.sharetypevisible = false;
			app.showLoading('生成海报中');
			app.post('ApiMingpian/getposter', {}, function (data) {
				app.showLoading(false);
				if (data.status == 0) {
					app.alert(data.msg);
				} else {
					that.posterpic = data.poster;
				}
			});
		},
		posterDialogClose: function () {
			this.showposter = false;
		},
		sharemp:function(){
      if(app.globalData.platform == 'mp'){
        app.error('点击右上角发送给好友或分享到朋友圈');
        this.sharetypevisible = false
      }else{
        let that = this;
        let shareLink = app.globalData.pre_url +'/h5/'+app.globalData.aid+'.html#/pagesExt/mingpian/index?scene=id_'+that.info.id+'-pid_' + (that.info.mid || app.globalData.mid);
        uni.setClipboardData({
        	data: shareLink,
        	success: function() {
        		uni.showToast({
        			title: '复制成功,快去分享吧！',
        			duration: 3000,
        			icon: 'none'
        		});
        	},
        	fail: function(err) {
        		uni.showToast({
        			title: '复制失败',
        			duration: 2000,
        			icon: 'none'
        		});
        	}
        });
      }
		},
		shareapp:function(){
			var that = this;
      if(!that.islogin){
        app.goto('/pages/index/login');
        return;
      }
			that.sharetypevisible = false;
			uni.showActionSheet({
        itemList: ['发送给微信好友', '分享到微信朋友圈'],
        success: function (res){
					if(res.tapIndex >= 0){
						var scene = 'WXSceneSession';
						if (res.tapIndex == 1) {
							scene = 'WXSenceTimeline';
						}
						var sharedata = {};
						sharedata.provider = 'weixin';
						sharedata.type = 0;
						sharedata.scene = scene;
						sharedata.title = that.info.sharetitle || '您好，这是我的'+that.mingpiantext+'，望惠存！';
						sharedata.summary = that.sharedesc;
						sharedata.href = app.globalData.pre_url +'/h5/'+app.globalData.aid+'.html#/pagesExt/mingpian/index?scene=id_'+that.info.id+'-pid_' + (that.info.mid || app.globalData.mid);
						sharedata.imageUrl = that.info.headimg;
						var sharelist = app.globalData.initdata.sharelist;
						if(sharelist){
							for(var i=0;i<sharelist.length;i++){
								if(sharelist[i]['indexurl'] == '/pagesExt/mingpian/index'){
									sharedata.title = sharelist[i].title;
									sharedata.summary = sharelist[i].desc;
									sharedata.imageUrl = sharelist[i].pic;
									if(sharelist[i].url){
										var sharelink = sharelist[i].url;
										if(sharelink.indexOf('/') === 0){
											sharelink = app.globalData.pre_url +'/h5/'+app.globalData.aid+'.html#'+ sharelink;
										}
										if(app.globalData.mid>0){
											 sharelink += (sharelink.indexOf('?') === -1 ? '?' : '&') + 'pid='+app.globalData.mid;
										}
										sharedata.href = sharelink;
									}
								}
							}
						}
						uni.share(sharedata);
					}
        }
      });
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
		},
    gotourl:function(e){
      var that = this;
      var gourl = e.currentTarget.dataset.url;
      if(that.islogin){
        app.goto(gourl);
      }else{
        app.goto('/pages/index/login');
      }
    },
    applysee:function(e){
      var that = this;
      app.confirm('查看更多内容需要对方同意，确定发送申请吗？',function(){
        app.showLoading();
        var id = that.opt.id ? that.opt.id : '';
        app.post('ApiMingpian/applySeeaddfields',{id:id}, function (res) {
          app.showLoading(false);
          if(res.status == 1){
            app.success(res.msg)
          }else{
            app.alert(res.msg);
          }
        })
      })
    }
	}
}
</script>

<style scoped>
	page{
		background: #F4F5F7;
	}
</style>
<style>
  @import "./iconfont.css";

	.banner{
		position: absolute;
		width: 100%;
		height: 300rpx;
		background: #4a8aff;
		/*border-radius: 0 0 20% 20%;*/
	}
	.page{
		padding: 70rpx 30rpx 0 30rpx;
	}
	.swiper-view-content{
		width: 100%;
		position: relative;
		box-shadow:2px 0px 10px rgba(0,0,0,0.5);
		border-radius: 12rpx;
		overflow: hidden;
	}
	.swiper-view-content .data-view{
		position: relative;
		width: 100%;
		padding: 30rpx;
		max-height:520rpx;
		z-index: 2
	}
	.swiper-view-content .swiper-view{
		width: 100%;
		height: 100%;
		position: absolute;
		top: 0;
		left: 0;
		z-index: 1
	}
	.swiper-view-content .swiper-view .swiper-class{
		width: 100;height: 100%;
	}
	.swiper-item-img{width: 100%;height: 100%;}
	.swiper-item-img image{width: 100%;height: 100%;}
	.data{
		position: relative;
		background: #FFFFFF;
		padding: 30rpx;
		border-radius: 12rpx;
		box-shadow:2px 0px 10px rgba(0,0,0,0.5);
		max-height:520rpx;
	}
	.data_info{
		display: flex;
		align-items: center;
		padding: 0 0 45rpx 0;
		border-bottom: 1rpx solid #eee;
		margin-bottom: 20rpx;
	}
	.data_head{
		width: 172rpx;
		height: 172rpx;
		border-radius: 50%;
		margin-right: 40rpx;
	}
	.data_name{
		font-size: 36rpx;
		font-family: Source Han Sans CN;
		font-weight: bold;
		color: #121212;
		padding-bottom: 10rpx;
	}
	.data_text{
		font-size: 24rpx;
		font-family: Alibaba PuHuiTi;
		font-weight: 400;
		color: #545556;
		padding-top: 15rpx;
	}
	.data_list{
		padding: 9rpx 0;
		font-size: 28rpx;
		font-family: Alibaba PuHuiTi;
		font-weight: 400;
		color: #8B9198;
		display: flex;
	}
	.data_list img{
		height: 30rpx;
		width: 30rpx;
		margin: 5rpx 30rpx 0 0;
		flex-shrink:0;
	}
	.data_tag{
		position: absolute;
		top: 50rpx;
		right: 50rpx;
		height: 60rpx;
		width: 60rpx;
    font-size: 60rpx;
    text-align: center;
    line-height: 60rpx;
	}
	
	.module{
		position: relative;
		padding: 30rpx 10rpx;
		margin: 25rpx 0 0 0;
		background: #fff;
		display: flex;
		border-radius: 12rpx;
		box-shadow:2px 0px 10px #ccc;
	}
	.module_item{
		flex: 1;
	}
	.module_img{
		height: 72rpx;
		width: 72rpx;
		display: block;
		margin: 0 auto;
    color:#4A84FF;
    font-size: 72rpx;
    text-align: center;
    line-height: 72rpx;
	}
  .module_img2{
    font-size: 54rpx;
  }
  .module_img3{
    font-size: 64rpx;
  }
	.module_text{
		font-size: 24rpx;
		text-align: center;
		font-family: Alibaba PuHuiTi;
		font-weight: 400;
		color: #8B9198;
		margin-top: 20rpx;
		line-height:30rpx;
	}
	
	.list{
		position: relative;
		padding: 40rpx;
		margin: 25rpx 0 0 0;
		background: #fff;
		border-radius: 12rpx;
		box-shadow:2px 0px 10px #ccc;
	}
	.list_title{
		font-size: 32rpx;
		font-family: Source Han Sans CN;
		font-weight: 500;
		color: #121212;
		padding-bottom:20rpx;
	}
	.list_item{
		display: flex;
		align-items: center;
		padding:22rpx 0;
	}
	.list_item:active{background:#f5f5f5}
	.list_img{
		height: 48rpx;
		width: 48rpx;
		flex-shrink: 0;
	}
	.list_lable{
		font-size: 30rpx;
		font-family: Alibaba PuHuiTi;
		font-weight: 500;
		color: #353535;
		flex-shrink: 0;
		padding: 0 50rpx 0 25rpx;
		width:180rpx;
	}
	.list_value{
		font-size: 28rpx;
		font-family: Alibaba PuHuiTi;
		font-weight: 400;
		color: #232323;
		min-width: 0;
		overflow: hidden;
		text-overflow: ellipsis;
		white-space: nowrap;
	}
	.person{
		position: relative;
		padding: 40rpx;
		margin: 25rpx 0 0 0;
		background: #fff;
		border-radius: 12rpx;
		box-shadow:2px 0px 10px #ccc;
	}
	.person_title{
		font-size: 32rpx;
		font-family: Source Han Sans CN;
		font-weight: 500;
		color: #121212;
		display: flex;
		justify-content: space-between;
		align-items: center;
	}
	.person_title view{
		width: 200rpx;
		height: 1rpx;
		background: #EEEEEE;
	}
	.person_data{
		margin-top: 30rpx;
	}
	.opt{
		position: relative;
		width: 100%;
		height: 190rpx;
	}
	.opt_module{
		width: 100%;
		height: 190rpx;
		display: flex;
		align-items: center;
		justify-content: space-between;
		box-sizing: border-box;
	}
	.opt_btn{
		width: 200rpx;
		height: 108rpx;
		background: #F2F6FF;
		font-size: 28rpx;
		font-family: PingFang SC;
		font-weight: bold;
		color: #4A84FF;
		display: flex;
		align-items: center;
		justify-content: center;
		border-radius: 54rpx;
		box-shadow: 0 0 10rpx #e0e0e0;
	}
	.opt_btn:last-child{
		width: 450rpx;
		height: 108rpx;
		background: #4A84FF;
		border-radius: 54rpx;
		font-size: 28rpx;
		font-family: PingFang SC;
		font-weight: bold;
		color: #FFFFFF;
	}
  .gobusiness{color: #fff;width: 200rpx;line-height: 70rpx;border-radius: 70rpx;text-align: center;position: fixed;bottom:200rpx;right: 30rpx;opacity: 0.65;}
  .moreaddfields{text-align: center;line-height: 70rpx;width: 220rpx;border: 2rpx solid #f1f1f1;border-radius: 6rpx;margin: 0 auto;}
</style>
