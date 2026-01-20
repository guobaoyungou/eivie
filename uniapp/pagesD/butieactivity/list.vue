<template>
<view>
	<block v-if="isload">
		<view class="container">
			<view class="datalist">
				<block v-for="(item, index) in datalist" :key="index">
				<view class="list">
					<view class="collage-product" :class="'bc'+item.buy_status">
						<text class="status_str str2" v-if="item.buy_status==2 || item.buy_status==3">已完成</text>
						<text class="status_str str1" v-if="item.buy_status==1">已购买</text>
						<view class="product-pic">
							<image :src="item.pic" mode="aspectFit"></image>
						</view> 
						<view class="product-info">
							<view class="p1">{{item.name}}</view>
							<view class="p2">
								<view class="p2-d">{{item.start_date}}~{{item.end_date}}</view>
								<view class="p2-d p-rule" @tap.stop="showmemberstage(index)">
									<view><text v-if="item.member_stage.back_days">补贴{{item.member_stage.back_days}}天</text><text v-if="item.member_stage.back_everyday_money">每日补贴{{item.member_stage.back_everyday_money}}元<text v-if="item.tuiguangdata[0].tuiguang">,</text>推{{item.tuiguangdata[0].tuiguang}}人</text><text v-if="item.tuiguangdata[0].addday">加{{item.tuiguangdata[0].addday}}</text><text v-if="item.tuiguangdata[0].addmoney">天,每日多补贴{{item.tuiguangdata[0].addmoney}}元</text>
									</view>
									<view class="rule-more">更多<text class="iconfont iconjiantou" style="color:#999;font-weight:normal;font-size: 24rpx;"></text></view>
								</view>
								
							</view>
							<view class="p3" v-if="item.buy_status > 0" >
								<progress class="t1" :percent="item.progress" stroke-width="5" activeColor="#48c78d" backgroundColor="#c9c9c9" border-radius="20" />
								<view class="t2"><text>{{item.member_check.tuiguang_num}}/{{item.member_check.tuiguang_num_max}}人</text></view>
							</view>						
						</view>
					</view>
					<view  class="product-bottom">						
						<view class="btn t1" @tap="shareClick(item)">立即分享</view>
						<view class="btn t2" :data-url="'/pagesD/butieactivity/index?id=' + item.productids+'&butie_activity_id='+item.id" @tap="goto">我的分享</view>
						<view class="btn t3 buybtn-bc0" v-if="item.buy_status == 0" :data-url="'/pages/shop/product?id=' + item.productids" @tap="goto" >去购买</view>
						<view class="btn t3 buybtn-bc1" v-if="item.buy_status == 1" >去复购</view>
						<view class="btn t3 buybtn-bc2" v-if="item.buy_status == 2 || item.buy_status == 3" :data-url="'/pages/shop/product?id=' + item.productids" @tap="goto" >去复购</view>
					</view>
				</view>
				</block>
			</view>
			<uni-popup id="dialogTuiguang" ref="dialogTuiguang" type="dialog">
				<view class="dialog-main">
					<view class="dialog-header">{{member_stage.name}}</view>
					<view class="dialog-content">
						<view style="margin-bottom: 20rpx;"><text v-if="member_stage.back_days">补贴{{member_stage.back_days}}天</text><text v-if="member_stage.back_everyday_money">每日补贴{{member_stage.back_everyday_money}}元</text></view>
						<view class="rule-item" v-for="(item, index) in member_stage.tuiguangdata" :key="index">
							<text class="dot"></text>
							<view><text v-if="item.tuiguang">推广{{item.tuiguang}}人</text><text v-if="item.addday">加{{item.addday}}</text><text v-if="item.addmoney">天,每日多补贴{{item.addmoney}}元</text></view>
						</view>
						<view class="weekday">{{member_stage.week_day}}</view>
					</view>
				</view>
			</uni-popup>
			<nomore v-if="nomore"></nomore>
			<nodata v-if="nodata"></nodata>
		</view>
		
		<view v-if="sharetypevisible" class="popup__container">
			<view class="popup__overlay" @tap.stop="handleClickMask"></view>
			<view class="popup__modal" style="height:320rpx;min-height:320rpx">
				<view class="popup__content">
					<view class="sharetypecontent">
						<!-- #ifdef APP -->
						<view class="f1" @tap="shareapp">
							<image class="img" :src="pre_url+'/static/img/sharefriends.png'"/>
							<text class="t1">分享给好友</text>
						</view>
						<!-- #endif -->
						<!-- #ifdef H5 -->
						<view class="f1" @tap="sharemp" v-if="getplatform() == 'mp'">
							<image class="img" :src="pre_url+'/static/img/sharefriends.png'"/>
							<text class="t1">分享给好友</text>
						</view>
						<!-- #endif -->
						<!-- #ifndef H5 || APP -->
						<button class="f1" open-type="share" v-if="getplatform() != 'h5'">
							<image class="img" :src="pre_url+'/static/img/sharefriends.png'"/>
							<text class="t1">分享给好友</text>
						</button>
						<!-- #endif -->
						<view class="f2" @tap="showPoster">
							<image class="img" :src="pre_url+'/static/img/sharepic.png'"/>
							<text class="t1">生成分享图片</text>
						</view>
					</view>
				</view>
			</view>
		</view>
		<view class="posterDialog" v-if="showposter">
			<view class="main">
				<view class="close" @tap="posterDialogClose"><image class="img" :src="pre_url+'/static/img/close.png'"/></view>
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
			opt:{},
			loading:false,
			isload: false,
			menuindex:-1,
			pre_url:app.globalData.pre_url,

			  pagenum: 1,
			  st: '',
			  datalist: [],
			  member_stage: [],
			  nomore: false,
			nodata:false,
			posterpic: "",
			sharetypevisible: false,
			showposter: false
    };
  },
  onLoad: function (opt) {
    var that = this;
    var opt  = app.getopts(opt);
		that.opt = opt;
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
			var that = this;
			if(!loadmore){
				this.pagenum = 1;
				this.datalist = [];
			}
			var pagenum = that.pagenum;
      var st = that.st;
			that.loading = true;
			that.nodata = false;
      that.nomore = false;
      app.post('ApiButieActivity/list', {st: st,pagenum: pagenum,trid:that.trid}, function (res) {
				that.loading = false;
        if(res.status == 1){
          var data = res.datalist;
          if (pagenum == 1) {
          	that.pics = res.pics;
            that.datalist = data;
            if (data.length == 0) {
              that.nodata = true;
            }
          }else{
            if (data.length == 0) {
              that.nomore = true;
            } else {
              var datalist = that.datalist;
              var newdata = datalist.concat(data);
              that.datalist = newdata;
            }
          }
          that.loaded();
        }else{
          app.alert(res.msg);
        }
        
      });
		},
		showmemberstage :function(index){
			var that = this;
			that.member_stage = this.datalist[index];
			// console.log(that.member_stage);
			this.$refs.dialogTuiguang.open();
		},
		shareClick: function(item) {
			var that = this;
			that.member_stage = item
			console.log(that.member_stage);
			this.sharetypevisible = true;
		},
		handleClickMask: function() {
			this.sharetypevisible = false;
		},
		posterDialogClose: function() {
			this.showposter = false;
		},
		sharemp: function() {
			app.error('点击右上角发送给好友或分享到朋友圈');
			this.sharetypevisible = false
		},
		showPoster: function () {
			var that = this;
			that.showposter = true;
			that.sharetypevisible = false;
			app.showLoading('生成海报中');
			app.post('ApiShop/getposter', {proid: that.member_stage.productids}, function (data) {
				app.showLoading(false);
				if (data.status == 0) {
					app.alert(data.msg);
				} else {
					that.posterpic = data.poster;
				}
			});
		},
		shareapp: function() {
			var that = this;
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
						sharedata.title = that.product.name;
						//sharedata.summary = app.globalData.initdata.desc;
						sharedata.href = app.globalData.pre_url + '/h5/' + app.globalData.aid +
							'.html#/pagesD/butieactivity/list?scene=id_1-pid_' + app.globalData.mid;
						sharedata.imageUrl = that.product.pic;
						var sharelist = app.globalData.initdata.sharelist;
						if (sharelist) {
							for (var i = 0; i < sharelist.length; i++) {
								if (sharelist[i]['indexurl'] == '/pagesD/butieactivity/list') {
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
}
</script>
<style>
.swiper {width:94%;margin:0 3%;height: 350rpx;margin-top: 20rpx;border-radius:20rpx;overflow:hidden}
.swiper image {width: 100%;height: 350rpx;overflow: hidden;}

.category{width:94%;margin:0 3%;padding-top: 10px;padding-bottom: 10px;flex-direction:row;white-space: nowrap; display:flex;}
.category .item{width: 150rpx;display: inline-block; text-align: center;}
.category .item image{width: 80rpx;height: 80rpx;margin: 0 auto;border-radius: 50%;}
.category .item .t1{display: block;color: #666;}

.datalist{width:94%;margin:0 3%;}
.list {background: #fff;margin-top: 20rpx;border-radius: 16rpx;border: 1px solid #eee;}
.collage-product {display:flex;position: relative;padding: 20rpx;border-radius: 16rpx 16rpx  0 0;}
.collage-product .status_str{position: absolute;top: 0;right: 0rpx; border-radius:0 0 0 8rpx;font-size: 20rpx;padding: 6rpx 10rpx;}
.collage-product .status_str.str1{color: #48c78d;background: rgb(72, 199, 141, 0.3);}
.collage-product .status_str.str2{color: #999;background: #dbdbdb;}
.collage-product .product-pic {width: 150rpx;height: 150rpx; overflow:hidden;display: flex;justify-content: center;align-items: center;border-radius: 8rpx;/* background: rgb(0, 0, 0,0.3); */}
.collage-product .product-pic image{max-width: 100%;max-height: 100%;border-radius: 8rpx;}
.collage-product .product-info {flex:1;margin-left: 14rpx;}
.collage-product .product-info .p1 {color:#323232;font-weight:bold;font-size:32rpx;max-width: 400rpx;white-space: nowrap;text-overflow: ellipsis;overflow: hidden;}
.collage-product .product-info .p2{font-size: 26rpx; color: #888;line-height: 40rpx;}
.collage-product .product-info .p2 .t1{color: #f40;}
.collage-product .product-info .p2 .t2 {margin-left: 10rpx;font-size: 26rpx;color: #888;text-decoration: line-through;}
.collage-product .product-info .p3{display:flex;justify-content: space-between;;}
.collage-product .product-info .p3 .t1{display: flex; align-items: center;justify-content: center;flex:1; }
.collage-product .product-info .p3 .t2{flex-shrink: 0;margin-left: 20rpx;color: #888;}
.product-bottom { padding:20rpx;display:flex;  justify-content: flex-end;align-items: center;}
.product-bottom .btn{ border-radius: 8rpx;  height:60rpx; margin-left:10rpx; font-size: 24rpx;color: #333;background: #fff; min-width: 100rpx;border: 1px solid #eee;display: flex;justify-content: center;align-items: center;padding: 0 20rpx;}

/* 不同状态的背景色 */
.bc0 {
	background: rgb(51, 136, 255,0.1);
}
.bc1 {
	background: rgb(72, 199, 141, 0.1);
}
.bc2 {
	background: rgb(255, 146, 45, 0.1);
}
.bc3 {
  background: rgb(255, 146, 45, 0.1);
}
.product-bottom .btn.buybtn-bc1{ background: #dbdbdb; color: #666;}
.product-bottom .btn.buybtn-bc2{ background: #ff922d;color: #fff;}
.product-bottom .btn.buybtn-bc0{ background: #3388ff; color: #fff;}
.p-rule{display: flex; justify-content: space-between;font-size: 24rpx;}
.p-rule .rule-more{flex-shrink: 0;font-size: 22rpx;margin-left: 14rpx;}
.dialog-main{background: #fff;padding: 20rpx;width: 92vw;margin: 0 4vw;border-radius: 10rpx;min-height: 400rpx;}
.dialog-content{max-height: 70vh;overflow-y: scroll;}
.dialog-content .rule-item{display: flex;border-top: 1px solid #f5f5f5;padding:20rpx 0; align-items: center;color: #666;}
.dialog-content .rule-item:last-child{border-bottom: none;}
.rule-item .dot{width: 12rpx;height: 12rpx;flex-shrink: 0;background: #666;border-radius: 50%;margin-right: 20rpx;}
.dialog-header{font-size: 32rpx;text-align: center;font-weight: bold;}
.dialog-content .weekday{background: #f6f6f6;padding: 16rpx;border-radius: 8rpx;font-size: 24rpx;margin-top:10rpx;}
</style>