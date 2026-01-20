<template>
  <view>
    <block v-if="isload">
      <view class="container">
        <!-- 播报 -->
        <view class="bobaobox" v-if="bobaolist && bobaolist.length>0">
          <swiper style="position:relative;height:54rpx;width:450rpx;" autoplay="true" :interval="5000" vertical="true">
            <swiper-item v-for="(item, index) in bobaolist" :key="index" @tap="goto" :data-url="item.tourl" class="flex-y-center">
              <view style="width:400rpx;white-space:nowrap;overflow:hidden;text-overflow: ellipsis;font-size:22rpx">
                <text style="padding-right:2px">第{{item.cycles}}期 中奖用户：</text>
                <text style="padding-right:4px">{{item.nickname}}</text>
              </view>
            </swiper-item>
          </swiper>
        </view>
        <!-- 活动规则 -->
        <button class="coverguize" @tap="changemaskrule" v-if="data.guize">活动规则</button>
        <view class="topbg">
          <image :src="data.bgpic" class="image" v-if="data.bgpic" />
          <image :src="pre_url + '/static/img/collage_teambg.png'" class="image" v-else />
        </view>

        <!-- 产品信息 -->
        <view :class="{'first-topbox': index === 0 }" class="topbox" @tap="goto" :data-url="'/pages/shop/product?id=' + item.id" v-for="(item,index) in data.product" :key="index">
          <view class="left">
            <image :src="item.pic"></image>
          </view>
          <view class="right">
            <view class="f1">{{item.name}}</view>
            <view class="f3" :style="{color:t('color1')}">
              <view class="t1">￥</view>
              <view class="t2">{{item.sell_price}}</view>
            </view>
          </view>
        </view>

        <!-- 剩余名额-->
        <view class="teambox" v-if="data.userlist">
          <view class="h2" :style="{background:'rgba('+t('color1rgb')+',0.2)',color:t('color1')}">第{{data.cycles}}期 开奖活动</view>
          <view class="t1" :style="'color:'+t('color1')+';text-align: center;'">{{data.custom_text}}</view>
          <view class="userlist">
            <view v-for="(item, index) in data.userlist" :key="index" class="item">
              <image :src="item.headimg?item.headimg:pre_url+'/static/img/wh.png'" class="f1"></image>
            </view>
          </view>
          <view class="join-text">
            <view>仅剩<text class="join-te1">{{data.surplus}}</text>个名额</view>
          </view>
          <button class="join-btn" @tap="shareClick" :style="{background:'linear-gradient(270deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'}">邀请好友下单</button>
        </view>

        <!-- 中奖信息-->
        <view class="teambox" v-if="winning">
          <view class="h2" :style="{background:'rgba('+t('color1rgb')+',0.2)',color:t('color1')}">第{{data.cycles}}期 开奖结果</view>
          <view class="winning" style="text-align: center;">
            <view>
              <image :src="winning.member.headimg" class="headimg"></image>
            </view>
            <view>
              {{winning.member.nickname}}
            </view>
          </view>
          <view class="tb0">
            <view class="tr">
              <view class="td">奖品图片</view>
              <view class="td">奖品名称 </view>
            </view>
            <view v-for="(v, k) in winning.award" :key="k" class="tr">
              <view class="td td2 flex-xy-center">
                <image :src="v.pic" v-if="v.pic" class="td-img" mode="widthFix"></image>
              </view>
              <view class="td td2" v-if="v.type == 3">{{v.name}}</view>
              <view class="td td2" v-else>{{v.name}}{{v.value}}</view>
            </view>
          </view>
        </view>
      </view>

      <view v-if="sharetypevisible" class="popup__container">
        <view class="popup__overlay" @tap.stop="handleClickMask"></view>
        <view class="popup__modal" style="height:320rpx;min-height:320rpx">
          <view class="popup__content">
            <view class="sharetypecontent">
              <view class="f1" @tap="shareapp" v-if="getplatform() == 'app'">
                <image class="img" :src="pre_url+'/static/img/sharefriends.png'" />
                <text class="t1">分享给好友</text>
              </view>
              <view class="f1" @tap="sharemp" v-else-if="getplatform() == 'mp'">
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
      <view class="posterDialog" v-if="showposter">
        <view class="main">
          <view class="close" @tap="posterDialogClose">
            <image class="img" :src="pre_url+'/static/img/close.png'" />
          </view>
          <view class="content">
            <image class="img" :src="posterpic" mode="widthFix" @tap="previewImage" :data-url="posterpic"></image>
          </view>
        </view>
      </view>
      <view id="mask-rule" v-if="showmaskrule">
        <view class="box-rule" :style="{background:t('color1')}">
          <view class="h2">活动规则说明</view>
          <view id="close-rule" @tap="changemaskrule" :style="'background-image:url('+pre_url+'/static/img/dzp/close.png);background-size:100%'"></view>
          <view class="con">
            <view class="text">
              <text decode="true" space="true">{{data.guize}}</text>
            </view>
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
        ks: '',
        userlist: [],
        sharetypevisible: false,
        showmaskrule: false,
        showposter: false,
        posterpic: "",
        hid: 0,
        bid: 0,
        data: [],
        cycles: 1,
        st: 0,
        bobaolist: [],
        product: '', //商品信息
        winning: '', //中奖信息
      };
    },

    onLoad: function(opt) {
      this.opt = app.getopts(opt);
      this.rid = this.opt.rid || 0;
      this.bid = this.opt.bid;
    },
    onShow: function(opt) {
      this.getdata();
    },
    onPullDownRefresh: function() {
      this.getdata();
    },
    onShareAppMessage: function() {
      var link = '/pages/shop/product?scene=id_' + this.product.id + '-pid_' + app.globalData.mid;
      return this._sharewx({
        title: this.product.sharetitle || this.product.name,
        pic: this.product.sharepic || this.product.pic,
        link: link
      });
    },
    onShareTimeline: function() {
      var link = '/pages/shop/product?scene=id_' + this.product.id + '-pid_' + app.globalData.mid;
      var sharewxdata = this._sharewx({
        title: this.product.sharetitle || this.product.name,
        pic: this.product.sharepic || this.product.pic,
        link: link
      });
      var query = (sharewxdata.path).split('?')[1] + '&seetype=circle';
      return {
        title: sharewxdata.title,
        imageUrl: sharewxdata.imageUrl,
        query: query
      }
    },
    methods: {
      getdata: function() {
        var that = this;
        that.loading = true;
        app.get('ApiManrenChoujiang/details', {
          bid: that.bid,
          rid: that.rid
        }, function(res) {
          that.loading = false;
          that.data = res.data;
          if (res.data.product) {
            that.product = res.data.product[0];
          }
          if (res.data.winning) {
            that.winning = res.data.winning;
          }
          that.bobaolist = res.bobaolist;
          that.loaded();
        });
      },
      shareClick: function() {
        this.sharetypevisible = true;
      },
      handleClickMask: function() {
        this.sharetypevisible = false;
      },
      changemaskrule: function() {
        this.showmaskrule = !this.showmaskrule;
      },
      showPoster: function() {
        var that = this;
        that.showposter = true;
        that.sharetypevisible = false;
        app.showLoading('生成海报中');
        app.post('ApiShop/getposter', {
          proid: that.product.id
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
      sharemp: function() {
        app.error('点击右上角发送给好友或分享到朋友圈');
        this.sharetypevisible = false
      },
      shareapp: function() {
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
              sharedata.title = that.product.sharetitle || that.product.name;
              sharedata.summary = that.product.sharedesc || that.product.sellpoint;
              sharedata.href = app.globalData.pre_url + '/h5/' + app.globalData.aid + '.html#/pages/shop/product?scene=id_' + that.product.id + '-pid_' + app.globalData.mid;
              sharedata.imageUrl = that.product.pic;
              var sharelist = app.globalData.initdata.sharelist;
              if (sharelist) {
                for (var i = 0; i < sharelist.length; i++) {
                  if (sharelist[i]['indexurl'] == '/pages/shop/product') {
                    sharedata.title = sharelist[i].title;
                    sharedata.summary = sharelist[i].desc;
                    sharedata.imageUrl = sharelist[i].pic;
                    if (sharelist[i].url) {
                      var sharelink = sharelist[i].url;
                      if (sharelink.indexOf('/') === 0) {
                        sharelink = app.globalData.pre_url + '/h5/' + app.globalData.aid + '.html#' + sharelink;
                      }
                      if (app.globalData.mid > 0) {
                        sharelink += (sharelink.indexOf('?') === -1 ? '?' : '&') + 'pid=' + app.globalData.mid;
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
      }
    }
  };
</script>
<style>
.topbg{width:100%;height:248rpx;position:relative;z-index:0}
.topbg .image{width:100%;height:100%}
.topbox{width:94%;margin:0 3%;background:#fff;border-radius:16rpx;padding:24rpx;display:flex;position:relative;z-index:1}
.topbox:first-of-type{margin-top:-140rpx}
.first-topbox{margin-top:-140rpx}
.topbox .left{flex-shrink:0;width:200rpx;height:200rpx}
.topbox .left image{width:100%;height:100%}
.topbox .right{flex:1;padding-left:20rpx;padding-right:20rpx;display:flex;flex-direction:column}
.topbox .right .f1{color:#32201B;height:80rpx;line-height:40rpx;font-size:30rpx;font-weight:bold;overflow:hidden}
.topbox .right .f2{display:flex;margin-top:10rpx}
.topbox .right .f2 .t1{display:flex;background:rgba(255,49,67,0.2);border-radius:20rpx;padding:0 20rpx;line-height:40rpx;color:#FF3143;font-size:24rpx}
.topbox .right .f3{display:flex;align-items:center;color:#FF3143;margin-top:40rpx}
.topbox .right .f3 .t1{font-size:28rpx}
.topbox .right .f3 .t2{font-size:40rpx;font-weight:bold;flex:1}
.topbox .right .f3 .t3{font-size:26rpx;font-weight:bold}
.teambox{width:94%;margin:0 3%;margin-top:20rpx;background:#fff;border-radius:16rpx;padding:24rpx;display:flex;flex-direction:column}
.userlist{width:100%;background:#fff;text-align:center;padding-top:40rpx;margin-top:20rpx}
.userlist .item{display:inline-block;width:120rpx;height:120rpx;position:relative}
.userlist .item .f1{width:100rpx;height:100rpx;border-radius:50%;border:1px #ffc32a solid}
.userlist .item .f2{background:#ffab33;border-radius:100rpx;padding:4rpx 16rpx;border:1px #fff solid;position:absolute;top:0px;left:-20rpx;color:#9f7200;font-size:30rpx}
.join-text{color:#000;padding:30rpx 0;font-size:36rpx;font-weight:600;background:#fff;text-align:center;width:100%}
.join-btn{width:90%;margin:20rpx 5%;background:linear-gradient(90deg,#FF3143 0%,#FE6748 100%);color:#fff;font-size:30rpx;height:80rpx;border-radius:40rpx}
.join-btn2{width:90%;margin:20rpx 5%;border:2rpx solid #FF3143;color:#FF3143;font-size:30rpx;height:80rpx;border-radius:40rpx}
.bobaobox{position:fixed;top:calc(var(--window-top) + 40rpx);left:20rpx;z-index:10;background:rgba(0,0,0,0.6);border-radius:30rpx;color:#fff;padding:0 10rpx}
.teambox .h2{margin:0 auto 30rpx auto;width:380rpx;height:60rpx;background-color:#f65647;text-align:center;line-height:60rpx;font-size:30rpx;color:#ffffff;border-radius:26rpx;letter-spacing:14rpx;font-weight:bold}
.tb0{width:100%;margin-bottom:6%;font-size:24rpx}
.tb0 .tr{width:100%;display:flex;border-bottom:1px solid #e4e4e4;align-items:center}
.tb0 .tr .td{width:50%;line-height:80rpx;text-align:center;font-weight:bold}
.tb0 .tr .td .td-img{width:80rpx;height:80rpx}
.tb0 .tr .td .td-img image{width:100%;height:100%}
.tb0 .tr .td2{padding:20rpx 0;text-align:center}
.headimg{width:100rpx;height:100rpx;border-radius:50%}
.coverguize{position:absolute;z-index:99999;cursor:pointer;display:flex;flex-direction:column;align-items:center;justify-content:center;overflow:hidden;z-index:9999;top:150rpx;right:0;color:#fff;background-color:rgba(17,17,17,0.3);font-size:26rpx;border-radius:30rpx 0px 0px 30rpx;width:55rpx;padding:0.3375rem 0.275rem;word-break:break-all;font-size:26rpx;height:auto;line-height:35rpx}
#mask-rule,#mask{position:fixed;left:0;top:0;z-index:99999;width:100%;height:100%;background-color:rgba(0,0,0,0.85)}
#mask-rule .box-rule{position:relative;margin:30% auto;padding-top:40rpx;width:90%;height:675rpx;border-radius:20rpx;background-color:#FE6748}
#mask-rule .box-rule .star{position:absolute;left:50%;top:-100rpx;margin-left:-130rpx;width:259rpx;height:87rpx}
#mask-rule .box-rule .h2{width:100%;text-align:center;line-height:34rpx;font-size:34rpx;font-weight:normal;color:#fff}
#mask-rule #close-rule{position:absolute;right:34rpx;top:38rpx;width:40rpx;height:40rpx}
#mask-rule .con{overflow:auto;position:relative;margin:40rpx auto;padding-right:15rpx;width:580rpx;height:82%;line-height:48rpx;font-size:26rpx;color:#fff}
#mask-rule .con .text{position:absolute;top:0;left:0;width:inherit;height:auto}
</style>