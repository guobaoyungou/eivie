<template> 
  <view class="pyramid-container">
    <block v-if="isload">
      <view class="pyramid flex-col flex-y-center">
				<view class="pyramid-title-view">活动{{set.hdct==1 ? '已成团':'进行中'}}</view>
        <!-- 第一层 -->
        <view class="level level-1" v-if="datalist">
          <view class="user-container user-container1">
            <image class="avatar" :src="datalist.headimg" />
            <text class="contribution" v-if="yx_collage_jipin_optimize && set.gxz==1">贡献值：{{datalist.gxz}}</text>
            <text class="user-name1">{{datalist.nickname}}</text>
						<text class="user-name">团号：{{datalist.num}}</text>
            <text class="user-name" v-if="datalist.mid>0">ID：{{datalist.mid}}</text>
          </view>
        </view>
        <!-- 第一层与第二层之间的两个箭头 -->
        <view class="arrow-container" >
          <image class="arrow" :src="pre_url+'/static/img/left.png'" />
          <image class="arrow" :src="pre_url+'/static/img/right.png'"/>
        </view>
        <!-- 第二层 -->
        <view class="level level-2" >
          <view class="user-container user-container2" v-if="datalist2_a">
            <image class="avatar" :src="datalist2_a.headimg" />
            <text class="contribution" v-if="yx_collage_jipin_optimize && set.gxz==1">贡献值：{{datalist2_a.gxz}}</text>
            <text class="user-name1">{{datalist2_a.nickname}}</text>
						<text class="user-name">团号：{{datalist2_a.num}}</text>
            <text class="user-name" v-if="datalist2_a.mid>0">ID：{{datalist2_a.mid}}</text>
          </view>
          <view class="user-container zwk user-container2" v-else>
						<view class="k-avatar">空位</view>
					</view>
          <view class="user-container user-container2" v-if="datalist2_b">
            <image class="avatar" :src="datalist2_b.headimg" />
						<text class="contribution" v-if="yx_collage_jipin_optimize && set.gxz==1">贡献值：{{datalist2_b.gxz}}</text>
            <text class="user-name1">{{datalist2_b.nickname}}</text>
						<text class="user-name">团号：{{datalist2_b.num}}</text>
            <text class="user-name" v-if="datalist2_b.mid>0">ID：{{datalist2_b.mid}}</text>
          </view>
          <view class="user-container zwk user-container2" v-else>
						<view class="k-avatar">空位</view>
					</view>
        </view>

        <!-- 第二层与第三层之间的四个箭头 -->
        <view class="arrow-container" >
          <image class="arrow" :src="pre_url+'/static/img/left.png'" />
          <image class="arrow" :src="pre_url+'/static/img/right.png'" />
          <image class="arrow" :src="pre_url+'/static/img/left.png'" />
          <image class="arrow" :src="pre_url+'/static/img/right.png'" />
        </view>
        <!-- 第三层 -->
        <view class="level level-3">
          <view class="user-container user-container3" v-if="datalist3_a">
            <image class="avatar" :src="datalist3_a.headimg" />
						<text class="contribution" v-if="yx_collage_jipin_optimize && set.gxz==1">贡献值：{{datalist3_a.gxz}}</text>
            <text class="user-name1">{{datalist3_a.nickname}}</text>
						<text class="user-name">团号：{{datalist3_a.num}}</text>
            <text class="user-name" v-if="datalist3_a.mid>0">ID：{{datalist3_a.mid}}</text>
          </view>
          <view class="user-container zwk user-container3" v-else>
						<view class="k-avatar">空位</view>
					</view>
          <view class="user-container user-container3" v-if="datalist3_b">
            <image class="avatar" :src="datalist3_b.headimg" />
						<text class="contribution" v-if="yx_collage_jipin_optimize && set.gxz==1">贡献值：{{datalist3_b.gxz}}</text>
            <text class="user-name1">{{datalist3_b.nickname}}</text>
						<text class="user-name">团号：{{datalist3_b.num}}</text>
            <text class="user-name" v-if="datalist3_b.mid>0">ID：{{datalist3_b.mid}}</text>
          </view>
          <view class="user-container zwk user-container3" v-else>
						<view class="k-avatar">空位</view>
					</view>
          <view class="user-container user-container3" v-if="datalist3_c">
            <image class="avatar" :src="datalist3_c.headimg" />
						<text class="contribution" v-if="yx_collage_jipin_optimize && set.gxz==1">贡献值：{{datalist3_c.gxz}}</text>
            <text class="user-name1">{{datalist3_c.nickname}}</text>
						<text class="user-name">团号：{{datalist3_c.num}}</text>
            <text class="user-name" v-if="datalist3_c.mid>0">ID：{{datalist3_c.mid}}</text>
          </view>
          <view class="user-container zwk user-container3" v-else>
						<view class="k-avatar">空位</view>
					</view>
          <view class="user-container user-container3" v-if="datalist3_d">
            <image class="avatar" :src="datalist3_d.headimg" />
						<text class="contribution" v-if="yx_collage_jipin_optimize && set.gxz==1">贡献值：{{datalist3_d.gxz}}</text>
            <text class="user-name1">{{datalist3_d.nickname}}</text>
						<text class="user-name">团号：{{datalist3_d.num}}</text>
            <text class="user-name" v-if="datalist3_d.mid>0">ID：{{datalist3_d.mid}}</text>
          </view>
          <view class="user-container zwk user-container3" v-else>
						<view class="k-avatar">空位</view>
					</view>
        </view>
				<view class="de-img-view">
					<image :src="pre_url+'/static/img/jipinde.png'"></image>
				</view>
      </view>
			<!--  -->
			<view class="user-info-view flex-col" v-if="yx_collage_jipin_optimize">
				<view class="user-top-view flex flex-y-center">
					<view class="info-view flex flex-y-center" style="justify-content: space-between;" v-if="set.auto_new == 1">
						<view @click='changeTab(0)' :class="[tabIndex == 0 ? 'tab-options-active' : '' , 'tab-options']">活动说明</view>
						<view @click='changeTab(1)' :class="[tabIndex == 1 ? 'tab-options-active' : '' , 'tab-options']">{{set.replace_fungou_name?set.replace_fungou_name:'更换复购'}}</view>
					</view>
          <view class="info-view flex flex-y-center" style="justify-content: space-between;" v-else>
            <view style="width: 60%;margin-left: 21%" @click='changeTab(0)' :class="[tabIndex == 0 ? 'tab-options-active' : '' , 'tab-options']">活动说明</view>
          </view>
				</view>
				<!-- 活动说明 -->
				<view class="activity-description flex-col" v-if="tabIndex == 0">
					<view v-if="set.content">
            <rich-text :nodes="set.content" style="word-wrap: break-word;"></rich-text>
					</view>
          <view v-else style="text-align: center;display: block;color: #959494;margin-top: 20rpx">
            暂无数据~
          </view>
				</view>
				<view class="activity-list-view flex-col" v-else>
          <view v-if="shop_product.length > 0">
            <block v-for="(item,index) in shop_product">
              <view class="flex active-options" >
                <view class="active-image" @tap.stop="goto" :data-url="'pages/shop/product?id=' + item.id">
                  <image :src="item.pic"></image>
                </view>
                <view class="flex flex-y-center" style="flex: 1;">
                  <view class="active-info-list flex-col">
                    <view class="active-name" @tap.stop="goto" :data-url="'pages/shop/product?id=' + item.id">{{item.name}}</view>
                    <view class="active-price" @tap.stop="goto" :data-url="'pages/shop/product?id=' + item.id">￥{{item.sell_price}}元</view>
                    <view class="active-fun-view flex flex-y-center">
                      <block v-if="set.invite_land == 1">
                        <view class="fun-class invite-friends" @tap.stop="shareapp" v-if="getplatform() == 'app'" :data-id="item.collage_jipin_id" :data-name="item.collage_jipin_name" :data-pic="item.pic">邀请好友</view>
                        <view class="fun-class invite-friends" @tap.stop="sharemp" v-else-if="getplatform() == 'mp'" :data-id="item.collage_jipin_id" :data-name="item.collage_jipin_name" :data-pic="item.pic">邀请好友</view>
                        <view class="fun-class invite-friends" @tap.stop="sharemp" v-else-if="getplatform() == 'h5'" :data-id="item.collage_jipin_id" :data-name="item.collage_jipin_name" :data-pic="item.pic">邀请好友</view>
                        <button class="btn2 fun-class invite-friends" style="line-height: 32rpx" open-type="share" v-else :data-id="item.collage_jipin_id" :data-name="item.collage_jipin_name" :data-pic="item.pic">邀请好友</button>
                      </block>
                      <view v-if="set.invite_land == 0" class='fun-class invite-friends' @tap.stop="goto" :data-url="'/activity/commission/poster'">邀请好友</view>
                      <view class='fun-class view-details' style="margin-left: 20rpx;" @tap.stop="goto" :data-url="'pages/shop/product?id=' + item.id">查看详情</view>
                    </view>
                  </view>
                  <view class="select-view flex flex-y-center flex-x-center" @tap.stop="changeradio" :data-index="index" :data-proid="item.id" :data-oid="datalist.id" :data-status="datalist.status">
                    <view class="flex flex-y-center flex-x-center select-view-options">
                      <view v-if="item.isfg" class="tag-icon"></view>
                    </view>
                  </view>
                </view>
              </view>
            </block>
          </view>
          <view v-else class="flex active-options" style="text-align: center;display: block;color: #959494;">
            暂无数据~
          </view>
				</view>
			</view>
      <nodata v-if="nodata"></nodata>
      <nomore v-if="nomore"></nomore>
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
		  pre_url:app.globalData.pre_url,
      opt:{},
      loading:false,
      isload: false,
      menuindex:-1,
      st: 'all',
      datalist: [],
      datalist2_a: [],
      datalist2_b: [],
      datalist3_a: [],
      datalist3_b: [],
      datalist3_c: [],
      datalist3_d: [],
      pagenum: 1,
      mydeposit: 0,
      nodata: false,
      nomore: false,
      id:0,
			tabIndex:0,
			set: {},
      shop_product: {},
      shareTitle:'',
      shareDesc:'',
      sharePic:'',
      shareLink:'',
      yx_collage_jipin_optimize:false,
    };
  },
  onLoad: function (opt) {
    this.opt = app.getopts(opt);
    this.getdata();
  },
  methods: {
		changeTab(e){
			this.tabIndex = e;
		},
    getdata: function (loadmore) {
      if(!loadmore){
        this.pagenum = 1;
        this.datalist = [];
      }
      var that = this;
      that.nodata = false;
      that.nomore = false;
      that.loading = true;
      app.post('ApiCollageJipin/charts', {id: that.opt.id}, function (res) {
        that.loading = false;
        that.datalist = res.data;
        var data2 = res.data2;
        var data3 = res.data3;
        that.datalist = res.data;
        that.datalist2_a = data2.a || '';
        that.datalist2_b = data2.b || '';
        that.datalist3_a = data3.a || '';
        that.datalist3_b = data3.b || '';
        that.datalist3_c = data3.c || '';
        that.datalist3_d = data3.d || '';
        that.set = res.set;
        that.shop_product = res.shop_product;
        that.yx_collage_jipin_optimize = res.yx_collage_jipin_optimize;
        that.shareTitle = that.set.name ? that.set.name : '即拼';
        that.shareDesc = '';
        that.sharePic = that.set.pic ? that.set.pic : '';
        that.shareLink = app.globalData.pre_url +'/h5/'+app.globalData.aid+'.html#/pagesC/yingxiao/jipinRecord?scene=hdid_'+that.set.id;
        that.loaded();
      });
    },
    goback:function(){
      getApp().goback();
    },
    //单选
    changeradio: function (e) {
      var that = this;
      var proid = e.currentTarget.dataset.proid;
      var oid = e.currentTarget.dataset.oid;
      var status = e.currentTarget.dataset.status;
      if(status==1){
        app.alert('已成团不能更改复购商品');
        return;
      }

      var shop_product = that.shop_product;
      for(var i in shop_product){
        if(shop_product[i].id == proid){
          shop_product[i].isfg = true;
        }else {
          shop_product[i].isfg = false;
        }
      }
      that.shop_product = shop_product;

      //保存到后台
      app.post('ApiCollageJipin/updateFgpro', {oid: oid,proid:proid}, function (res) {

        if(res.status == 1){

        } else {
          app.alert(res.msg);
        }
        that.loaded();
      });
    },
    onShareAppMessage:function(){
      var tolink = '/pagesC/yingxiao/jipinRecord?scene=hdid_'+this.set.id;
      return this._sharewx({title:this.shareTitle,pic:this.sharePic,tolink:tolink});
    },
    onShareTimeline:function(){
      var tolink = '/pagesC/yingxiao/jipinRecord?scene=hdid_'+this.set.id;
      var sharewxdata = this._sharewx({title:this.shareTitle,pic:this.sharePic,tolink:tolink});
      var query = (sharewxdata.path).split('?')[1];

      return {
        title: sharewxdata.title,
        imageUrl: sharewxdata.imageUrl,
        query: query
      }
    },
    sharemp:function(){
      app.error('点击右上角三个点发送给好友或分享到朋友圈');
      this.sharetypevisible = false;

      // let that = this;
      // let shareLink = app.globalData.pre_url +'/h5/'+app.globalData.aid+'.html#/pagesC/yingxiao/jipinRecord?scene=hdid_'+that.set.id;
      // uni.setClipboardData({
      //   data: shareLink,
      //   success: function() {
      //     uni.showToast({
      //       title: '链接复制成功,快去分享吧！',
      //       duration: 3000,
      //       icon: 'none'
      //     });
      //   },
      //   fail: function(err) {
      //     uni.showToast({
      //       title: '复制失败',
      //       duration: 2000,
      //       icon: 'none'
      //     });
      //   }
      // });
    },
    shareapp:function(){
      var that = this;
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
            sharedata.title = that.shareTitle;
            sharedata.summary = that.shareDesc;
            sharedata.href = that.shareLink;
            sharedata.imageUrl = that.sharePic;

            uni.share(sharedata);
          }
        }
      });
    },
  }
};
</script> 

<style>  
.pyramid-container {width: 94%;margin: 0 auto;}
.zwk {width: 127rpx;color: #888;}
.text-center { margin-top: 80rpx;width:80%;border:1px solid #f5f5f5;border-radius:40rpx;height:80rpx;line-height:80rpx;background:#eb6d45;color:#fff;text-align:center;}
.user-container1{width: 100%; overflow: hidden; /* 隐藏溢出的内容 */ white-space: nowrap; /* 防止文本换行 */text-overflow: ellipsis; }
.user-container2{width: 50%; overflow: hidden; /* 隐藏溢出的内容 */ white-space: nowrap; /* 防止文本换行 */text-overflow: ellipsis; }
.user-container3{width: 25%; overflow: hidden; /* 隐藏溢出的内容 */ white-space: nowrap; /* 防止文本换行 */text-overflow: ellipsis; }
.user-name1{width: 100%; overflow: hidden; /* 隐藏溢出的内容 */ white-space: nowrap; /* 防止文本换行 */text-overflow: ellipsis;color: #000;font-size: 32rpx;
text-align: center;margin-top: 5rpx;font-weight: bold;}
.de-img-view{width: 100%;height: 55rpx;margin-top: 40rpx;}
.de-img-view image{width: 100%;height: 100%;}
.pyramid {
	width: 100%;
  margin-top: 80rpx; /* 页面的顶部边距 */
  border-radius: 36rpx;
  background: white; /* 内部背景可以设为白色，或根据需要自定义 */  
	position: relative;
	padding: 60rpx 20rpx 40rpx ;
}  
.pyramid-title-view{background-color: #3b3b3b;color: #fff001;font-size: 40rpx;font-weight: bold;padding: 18rpx 0rpx;border-radius: 46rpx;position: absolute;
left: 50%;top: -40rpx;transform: translateX(-50%);width: 380rpx;text-align: center;}
/* 定义层级的样式 */  
.level {  
  display: flex;  
  justify-content: space-around; /* 等距离布局 */  
  width: 100%; /* 让用户层占满整个父容器 */  
  margin-top: 20rpx; /* 每层之间的间距 */ 
}  

.user-container {  
  display: flex;  
  flex-direction: column; /* 垂直排列每个用户 */  
  align-items: center; /* 用户居中对齐 */  
}  

/* 头像样式 */  
.avatar {  
  width: 105rpx; /* 头像宽度设置为 100rpx */  
  height: 105rpx; /* 头像高度设置为 100rpx */  
  border-radius: 50%; /* 圆形头像 */  
}  
.k-avatar{width: 105rpx; height: 105rpx;border-radius: 50%;border: 1px #888 solid;text-align: center;line-height: 105rpx;font-size: 24rpx;}
/* 用户名文本样式 */  
.user-name {  
  text-align: center; /* 文本居中 */
  font-size: 24rpx;
	color: #959494;
}  

/* 箭头的容器样式 */  
.arrow-container {  
  display: flex;  
  justify-content: space-around; /* 等比例间隔 */  
  width: 100%; /* 让箭头层占满整个父容器 */ 
	margin-top: -25rpx;
}  

/* 箭头的样式 */  
.arrow {  
  width: 60rpx; /* 箭头宽度 */  
  height: 60rpx; /* 箭头高度 */  
}  
.contribution{font-size: 22rpx;color: #fff001;white-space: nowrap;background: #fb484c;padding: 5rpx 16rpx;border-radius: 20rpx;margin-top: 10rpx;text-align: center; }
/* 进行中&已成团 */
.user-info-view{background: #fff;margin-top: 30rpx;border-radius: 36rpx;padding: 30rpx;position: relative;overflow: hidden;}
.user-info-view .user-top-view{width: 100%;border-bottom: 1px #ebeaea solid;justify-content: space-between;padding-bottom: 25rpx;}
.user-info-view .user-top-view .info-view{flex: 1;}
.tab-options{width: 43%;text-align: center;padding: 12rpx;border-radius: 30rpx;background-color: #e3e3e3;color: #484848;font-size: 26rpx;}
.tab-options-active{background: #fb484c;color: #fff;}
.activity-list-view{width: 100%;margin-top: 10rpx;}
.activity-list-view .active-options{align-items: center;border-bottom: 1px #ebeaea solid;padding: 20rpx 0rpx;flex: 1;}
.activity-list-view .active-options .active-image{width: 200rpx;height: 200rpx;border-radius: 14rpx;overflow: hidden;}
.activity-list-view .active-options .active-image image{width: 100%;height: 100%;}
.activity-list-view .active-options .active-info-list{margin-left: 30rpx;flex: 1;height: 180rpx;justify-content: space-between;}
.active-info-list .active-name{font-size: 26rpx;color: #000;font-weight: bold;display: -webkit-box;-webkit-box-orient: vertical;
  overflow: hidden;text-overflow: ellipsis;-webkit-line-clamp: 2; /* 控制显示的行数 */}
.active-info-list .active-price{font-size: 26rpx;color: #fb484c;white-space: nowrap;font-weight: bold;margin-top: 5rpx;}
.active-info-list .active-fun-view{}
.active-info-list .active-fun-view .fun-class{font-size: 22rpx;padding: 5rpx 25rpx;border-radius: 20px;color: #fff;}
.active-info-list .active-fun-view .invite-friends{background: #fb484c;}
.active-info-list .active-fun-view .view-details{background-color: #3b3b3b;}
.activity-description{width: 100%;margin-top: 10rpx;}
.activity-text-view{width: 100%;border: 1px #ebeaea solid;border-radius: 30rpx;justify-content: flex-start;height: auto;margin-top: 10rpx;margin-bottom: 10rpx;}
.activity-text-view .title-view{border-radius:30rpx;background-color:#3b3b3b;color: #fff;width: 190rpx;text-align: center;height: 100%;padding: 12rpx 0rpx;font-size: 26rpx;}
.activity-text-view .info-text{flex: 1;margin-left: 30rpx;}
.activity-text-view .info-text .info-title-text{font-size: 26rpx;color: #000;font-weight: bold;}
.activity-text-view .info-text .neirong-text{color: #888;}
.info-title-text-last{font-size: 26rpx;color: #000;font-weight: bold;white-space: nowrap;}
.info-text-last{padding: 4rpx 0rpx;}
.select-view{height: 190rpx;border-left: 1px #d1d1d1 solid;margin-left: 25rpx;}
.select-view .select-view-options{width: 50rpx;height: 50rpx;border-radius: 50%;border: 1px #fb484c solid;margin-left: 30rpx;}
.select-view .select-view-options .tag-icon{background: #fb484c;width: 30rpx;height: 30rpx;border-radius: 50%;}
</style>