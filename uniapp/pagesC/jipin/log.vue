<template>
  <view class="container">
    <block v-if="isload">
			<view class="topfix">
				<view class="tongji flex-y-center "  :style="{background:color1}">
					<view>
						<view class="price">{{total_num}}</view>
						<view>总成团次数</view>
					</view>
					<view>
						<view class="price">{{total_commission}}</view>
						<view>成团奖励金额</view>
					</view>
				</view>
				<dd-tab :itemdata="['进行中','已成团']" :itemst="['0','1']" :st="st" :isfixed="false" @changetab="changetab"></dd-tab>
			</view>

      <view style="margin-top:300rpx"></view>
      <view class="coupon-list">
        <view v-for="(item, index) in datalist" :key="index" class="coupon" @tap.stop="goto" :data-url="'detail?tid=' + item.id">
          <view class="pt_left">
            <view class="pt_left-content">
              <image v-if="item.pic" :src="item.pic" mode="widthFix" style="width: 100%;max-height: 200rpx;border-radius: 4rpx;" />
            </view>
          </view>
          <view style="width: 2rpx;height: 100rpx;background-color: #eee;"></view>
          <view class="pt_right">
            <view class="title" >{{item.name}}</view>
            <view class="t3" style="font-size: 24rpx;padding: 20rpx 0;">
              <view class="shortcontent" v-if="st == 0">{{item.tuannum}}号团，已拼{{item.num}}人，还差{{item.unnum}}人成团</view>
              <view class="shortcontent" v-else>{{item.tuannum}}号团，已拼{{item.num}}人</view>
            </view>
            <view class="p2" style="padding-bottom: 20rpx;" >
              <progress :percent="item.percent" stroke-width="6" border-radius="10" :show-info="true" :font-size="14" activeColor="#fb743b" class="my-progress" />
            </view>
            <button v-if="st == 0" @tap.stop="shareClick" :data-index="index" :data-proid="item.proid" :data-pic="item.pic" class="btn" :style="{background:'linear-gradient(270deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'}">邀请好友</button>
          </view>
        </view>
      </view>
      <nomore v-if="nomore"></nomore>
      <nodata v-if="nodata"></nodata>

      <view v-if="sharetypevisible" class="popup__container">
        <view class="popup__overlay" @tap.stop="handleClickMask"></view>
        <view class="popup__modal" style="height:320rpx;min-height:320rpx">
          <view class="popup__content">
            <view class="sharetypecontent">
              <view class="f1" @tap="shareapp" v-if="getplatform() == 'app'">
                <image class="img" :src="pre_url+'/static/img/sharefriends.png'" />
                <text class="t1">分享给好友</text>
              </view>
              <view class="f1" @tap="sharemp" v-else-if="getplatform() == 'mp' || getplatform() == 'h5'">
                <image class="img" :src="pre_url+'/static/img/sharefriends.png'" />
                <text class="t1">分享给好友</text>
              </view>
              <button class="f1" open-type="share" v-else-if="getplatform() != 'h5'">
                <image class="img" :src="pre_url+'/static/img/sharefriends.png'" />
                <text class="t1">分享给好友</text>
              </button>
              <view class="f2" @tap="showPoster">
                <image class="img" :src="pre_url+'/static/img/sharepic.png'" />
                <text class="t1">生成分享图片</text>
              </view>
            </view>
          </view>
        </view>
      </view>

      <view class="posterDialog" v-if="showposter" style="z-index: 99;">
        <view class="main">
          <view class="close" @tap="posterDialogClose">
            <image class="img" :src="pre_url+'/static/img/close.png'" />
          </view>
          <view class="content">
            <image class="img" :src="posterpic" mode="widthFix" @tap="previewImage" :data-url="posterpic"></image>
          </view>
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
        opt: {},
        loading: false,
        isload: false,
        menuindex: -1,
        pre_url: app.globalData.pre_url,
        st: 0,
        datalist: [],
        pagenum: 1,
        nomore: false,
        nodata: false,
        product: [],
        shareTitle: '',
        sharePic: '',
        shareDesc: '',
        shareLink: '',
        sharetypevisible: false,
        posterpic: "",
        showposter: false,
				color1:'rgb(253, 74, 70)',
				total_num:0,
				total_commission:0
      };
    },

    onLoad: function(opt) {
      this.opt = app.getopts(opt);
      this.st = this.opt.st || 0;
      this.getdata();
    },
    onPullDownRefresh: function() {
      this.getdata();
    },
    onReachBottom: function() {
      if (!this.nodata && !this.nomore) {
        this.pagenum = this.pagenum + 1;
        this.getdata(true);
      }
    },
    onShareAppMessage: function() {
      return this._sharewx({
        title: this.shareTitle,
        pic: this.sharePic,
        desc: this.shareDesc,
        link: this.shareLink
      });
    },
    onShareTimeline: function() {
      var sharewxdata = this._sharewx({
        title: this.shareTitle,
        pic: this.sharePic,
        desc: this.shareDesc,
        link: this.shareLink
      });
      var query = (sharewxdata.path).split('?')[1] + '&seetype=circle';
      return {
        title: sharewxdata.title,
        imageUrl: sharewxdata.imageUrl,
        query: query
      }
    },
    methods: {
      getdata: function(loadmore) {
        if (!loadmore) {
          this.pagenum = 1;
          this.datalist = [];
        }
        var that = this;
        var pagenum = that.pagenum;
        var st = that.st;
        that.loading = true;
        that.nomore = false;
        that.nodata = false;
        app.post('ApiJipinLog/list', {
          st: st,
          pagenum: pagenum
        }, function(res) {
          that.loading = false;
          if (res.status == 1) {
            var data = res.data;
						that.total_num = res.total_num;
						that.total_commission = res.total_commission;
						that.product = res.product;
            if (pagenum == 1) {
              that.datalist = data;
              if (data.length == 0) {
                that.nodata = true;
              }
              that.loaded();
            } else {
              if (data.length == 0) {
                that.nomore = true;
              } else {
                var datalist = that.datalist;
                var newdata = datalist.concat(data);
                that.datalist = newdata;
              }
            }
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
      changetab: function(st) {
        this.st = st;
        uni.pageScrollTo({
          scrollTop: 0,
          duration: 0
        });
        this.getdata();
      },
      shareClick: function(e) {
        var that = this;
        var index = e.currentTarget.dataset.index;
        var info = that.datalist[index];
        var product = that.datalist[index].product;
        if (product) {
          that.product = product;
          that.shareTitle = info.name || product.name;
          that.sharePic = info.pic || product.pic;
          that.shareDesc =  product.name;

          if (app.globalData.platform == 'h5' || app.globalData.platform == 'mp' || app.globalData.platform == 'app') {
            that.shareLink = app.globalData.pre_url + '/h5/' + app.globalData.aid + '.html#/pages/shop/product?scene=id_' + product.proid + '-pid_' + app.globalData.mid;
          } else {
            that.shareLink = '/pages/shop/product?scene=id_' + product.proid + '-pid_' + app.globalData.mid;
          }
          that.loaded({
            title: that.shareTitle,
            pic: that.sharePic,
            desc: that.shareDesc,
            link: that.shareLink
          });
          that.sharetypevisible = true;
        }
      },
      handleClickMask: function() {
        this.sharetypevisible = false
      },
      sharemp: function() {
        let that = this;
        uni.setClipboardData({
          data: that.shareLink,
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
        this.sharetypevisible = false
      },
      shareapp: function() {
        // #ifdef APP-PLUS
        var that = this;
        that.sharetypevisible = false;
        uni.showActionSheet({
          itemList: ['发送给微信好友', '分享到微信朋友圈'],
          success: function(res) {
            if (res.tapIndex >= 0) {
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
        // #endif
      },
      showPoster: function() {
        var that = this;
        that.posterpic = "";
        that.showposter = true;
        that.sharetypevisible = false;
        app.showLoading('生成海报中');
        app.post('ApiShop/getposter', {
          proid: that.product.proid
        }, function(data) {
          app.showLoading(false);
          if (data.status == 0) {
            app.alert(data.msg);
          } else {
            that.posterpic = data.poster;
          }
        });
      },
      posterDialogClose: function() {
        this.showposter = false;
      },
    }
  };
</script>
<style>
.coupon-list{width:710rpx;margin:0 auto;margin-top:10rpx}
.coupon{width:100%;display:flex;margin-bottom:20rpx;border-radius:10rpx;overflow:hidden;align-items:center;position:relative;background:#fff}
.coupon .pt_left{background:#fff;min-height:200rpx;color:#FFF;width:210rpx;display:flex;flex-direction:column;align-items:center;justify-content:center}
.coupon .pt_left-content{width:150rpx;overflow:hidden;padding:20rpx 0}
.coupon .pt_left .f1{font-size:40rpx;font-weight:bold;text-align:center}
.coupon .pt_left .t1{font-size:60rpx}
.coupon .pt_right{background:#fff;width:490rpx;min-height:200rpx;text-align:left;padding:10rpx 10rpx 10rpx 20rpx}
.coupon .pt_right .title{font-size:28rpx;color:#2B2B2B;font-weight:bold;max-height:72rpx;line-height:36rpx;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:2;overflow:hidden}
.coupon .pt_right .t3{font-size:20rpx;color:#999999;line-height:46rpx}
.coupon .pt_right .btn{border-radius:28rpx;height:56rpx;line-height:56rpx;color:#fff}
.giveopbox{position:fixed;bottom:0;left:0;width:100%}
.btn-give{width:90%;margin:30rpx 5%;height:96rpx;line-height:96rpx;text-align:center;color:#fff;font-size:30rpx;font-weight:bold;border-radius:48rpx}
.shortcontent{word-break:break-all;text-overflow:ellipsis;overflow:hidden;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:2;line-height:30rpx}
.my-progress{color:#999999}
/* 累计奖励 */
.topfix{width: 100%;position:relative;position:fixed;background: #f9f9f9;top:var(--window-top);z-index:11;}
.tongji{width: 94%;margin: 30rpx 3% 0 3%;padding: 30rpx;border-radius: 20rpx; background-color: #F2350D;height: 160rpx;color: #fff;position: relative;justify-content: space-evenly;text-align: center;}
.tongji .price{font-size: 40rpx;font-weight: 700;}


.toplabel{width: 100%;background: #f9f9f9;padding: 20rpx 20rpx;border-bottom: 1px #e3e3e3 solid;display:flex;}

</style>