<template>
	<view class="container">
	  <block v-if="isload">
			<view class="flex flex-x-center statistics-view">
				<view class='flex-col flex-y-center statais-options'>
					<view class="top-num">{{platform_group_num}}</view>
					<view class="title-text">平台成团次数</view>
				</view>
				<view class='flex-col flex-y-center statais-options'>
					<view class="top-num">{{platform_cumulative}}</view>
					<view class="title-text">平台累计奖励</view>
				</view>
			</view>
			<view class="user-info-view flex-col">
				<view class="user-top-view flex flex-y-center">
					<view class="info-view flex flex-y-center">
						<view class="avatar-view">
							<image :src="member.headimg"></image>
						</view>
						<view style="padding-left: 20rpx;">
							<view class="user-name flex flex-y-center">
								<view>{{member.nickname}}</view>
								<view class="tuanzhang-tag flex flex-y-center" v-if="ktjl > 0">
									<image :src="pre_url+'/static/img/jipin_tuanzhang.png'" class="tuanzhang-icon" />
									团长
								</view>
							</view>
							<view class="reference-name" v-if="member.p_nickname">推荐人：{{member.p_nickname}}</view>
						</view>
					</view>
					<view class="withdraw-money" v-if="yx_collage_jipin_optimize && hdinfo.go_withdraw_name" @tap="goto" :data-url="hdinfo.go_withdraw_name_tourl">{{hdinfo.go_withdraw_name}}</view>
				</view>
				<view class="rewards-view flex flex-y-center">
					<view class="flex-col flex-y-center rewards-options">
						<view class="rewards-text">累计团长奖励(元)</view>
						<view class="rewards-price">{{group_cumulative}}</view>
					</view>
					<view class="flex-col flex-y-center rewards-options">
						<view class="rewards-text">累计成团(次)</view>
						<view class="rewards-price">{{group_num}}</view>
					</view>
					<view class="flex-col flex-y-center rewards-options">
						<view class="rewards-text">累计直推开团(次)</view>
						<view class="rewards-price">{{group_recommend_num}}</view>
					</view>
				</view>
				<view class="de-img-view">
					<image :src="pre_url+'/static/img/jipinde.png'"></image>
				</view>
				<!-- 暂无开团记录遮盖 -->
				<view class="mask-view" v-if="ktjl <= 0">
					暂无开团记录
				</view>
			</view>
			<view class="user-info-view flex-col">
				<view class="user-top-view flex flex-y-center">
					<view class="info-view flex flex-y-center">
						<view>
							参团活动产品
						</view>
					</view>
					<view class="activity-description" v-if="yx_collage_jipin_optimize && hdinfo.description_name" @tap="goto" :data-url="hdinfo.description_name_tourl">{{hdinfo.description_name}}</view>
				</view>
        <block v-if="product_list.length > 0">
				<view class="shop-list-view">
					<scroll-view scroll-x style="white-space: nowrap;width: 100%;">
						<block v-for="(item,index) in product_list">
							<view class="shop-view flex-col flex-y-center" @tap="goto" :data-url="'pages/shop/product?id=' + item.id">
								<view class="shop-image">
									<image :src="item.pic"></image>
								</view>
								<view class="shop-price-view">￥{{item.sell_price}}</view>
							</view>
						</block>
					</scroll-view>
				</view>
        </block>
        <block v-else>
          <view class="shop-list-view" style="text-align: center;display: block;color: #959494">
            {{product_jzai}}
          </view>
        </block>
			</view>
			<!--  -->
			<view class="user-info-view flex-col">
				<view class="user-top-view flex flex-y-center">
					<view class="info-view flex flex-y-center" style="justify-content: space-between;">
						<view @click='changeTab(0)' :class="[tabIndex == 0 ? 'tab-options-active' : '' , 'tab-options']">进行中</view>
						<view @click='changeTab(1)' :class="[tabIndex == 1 ? 'tab-options-active' : '' , 'tab-options']">已成团</view>
					</view>
				</view>
        <block v-if="tabIndex == 0">
          <view class="activity-list-view flex-col" v-if="progress_list.length > 0">
            <block v-for="(item,index) in progress_list">
              <view class="flex active-options" >
                <view class="active-image" @tap.stop="ckjpxq" :data-url="'jipinCharts?id=' + item.id" :data-opening_detail="item.opening_detail">
                  <image :src="item.pic"></image>
                </view>
                <view class="active-info-list flex-col" >
                  <view class="active-name" @tap.stop="ckjpxq" :data-url="'jipinCharts?id=' + item.id" :data-opening_detail="item.opening_detail">{{item.name}}</view>
                  <view class="active-jingdu" @tap.stop="ckjpxq" :data-url="'jipinCharts?id=' + item.id" :data-opening_detail="item.opening_detail">{{item.cantuan}}</view>
                  <view @tap.stop="ckjpxq" :data-url="'jipinCharts?id=' + item.id" :data-opening_detail="item.opening_detail">
                    <progress :percent="item.hc_offered_num" font-size='13' show-info activeColor='#fb484c' active stroke-width='8' border-radius='12' />
                  </view>
                  <view class="reward-amount" v-if="yx_collage_jipin_optimize && item.award_display" @tap.stop="ckjpxq" :data-url="'jipinCharts?id=' + item.id" :data-opening_detail="item.opening_detail">{{item.award_display}}</view>
                  <view class="active-fun-view flex flex-y-center">
                    <block v-if="hdinfo.invite_land == 1">
                      <view class="fun-class invite-friends" @tap.stop="shareapp" v-if="getplatform() == 'app'" :data-id="item.collage_jipin_id" :data-name="item.collage_jipin_name" :data-pic="item.pic">邀请好友</view>
                      <view class="fun-class invite-friends" @tap.stop="sharemp" v-else-if="getplatform() == 'mp'" :data-id="item.collage_jipin_id" :data-name="item.collage_jipin_name" :data-pic="item.pic">邀请好友</view>
                      <view class="fun-class invite-friends" @tap.stop="sharemp" v-else-if="getplatform() == 'h5'" :data-id="item.collage_jipin_id" :data-name="item.collage_jipin_name" :data-pic="item.pic">邀请好友</view>
                      <button class="btn2 fun-class invite-friends" style="line-height: 32rpx" open-type="share" v-else :data-id="item.collage_jipin_id" :data-name="item.collage_jipin_name" :data-pic="item.pic">邀请好友</button>
                    </block>
                    <view v-if="hdinfo.invite_land == 0" class='fun-class invite-friends' @tap.stop="goto" :data-url="'/activity/commission/poster'">邀请好友</view>
                    <view class='fun-class view-details' style="margin-left: 25rpx;" v-if="hdinfo.opening_detail == 1" @tap.stop="ckjpxq" :data-url="'jipinCharts?id=' + item.id" :data-opening_detail="item.opening_detail">查看详情</view>
                  </view>
                </view>
              </view>
            </block>
          </view>
          <view v-else class="activity-list-view flex-col">
            <view class="flex active-options" style="text-align: center;display: block;color: #959494">{{progress_jzai}}</view>
          </view>
        </block>
        <block v-if="tabIndex == 1">
          <view class="activity-list-view flex-col" v-if="clustering_list.length > 0">
            <block v-if="clustering_list.length > 0" v-for="(item,index) in clustering_list">
              <view class="flex active-options" >
                <view class="active-image" @tap.stop="ckjpxq" :data-url="'jipinCharts?id=' + item.id" :data-opening_detail="item.opening_detail">
                  <image :src="item.pic"></image>
                </view>
                <view class="active-info-list flex-col">
                  <view class="active-name" @tap.stop="ckjpxq" :data-url="'jipinCharts?id=' + item.id" :data-opening_detail="item.opening_detail">{{item.name}}</view>
                  <view class="active-jingdu" @tap.stop="ckjpxq" :data-url="'jipinCharts?id=' + item.id" :data-opening_detail="item.opening_detail">{{item.cantuan}}</view>
                  <view @tap.stop="ckjpxq" :data-url="'jipinCharts?id=' + item.id" :data-opening_detail="item.opening_detail">
                    <progress :percent="item.hc_offered_num" font-size='13' show-info activeColor='#fb484c' active stroke-width='8' border-radius='12' />
                  </view>
                  <view class="reward-amount" v-if="item.award_display" @tap.stop="ckjpxq" :data-url="'jipinCharts?id=' + item.id" :data-opening_detail="item.opening_detail">{{item.award_display}}</view>
                  <view class="active-fun-view flex flex-y-center">
                    <block v-if="hdinfo.invite_land == 1">
                      <view class="fun-class invite-friends" @tap.stop="shareapp" v-if="getplatform() == 'app'" :data-id="item.collage_jipin_id" :data-name="item.collage_jipin_name" :data-pic="item.pic">邀请好友</view>
                      <view class="fun-class invite-friends" @tap.stop="sharemp" v-else-if="getplatform() == 'mp'" :data-id="item.collage_jipin_id" :data-name="item.collage_jipin_name" :data-pic="item.pic">邀请好友</view>
                      <view class="fun-class invite-friends" @tap.stop="sharemp" v-else-if="getplatform() == 'h5'" :data-id="item.collage_jipin_id" :data-name="item.collage_jipin_name" :data-pic="item.pic">邀请好友</view>
                      <button class="btn2 fun-class invite-friends" style="line-height: 32rpx" open-type="share" v-else :data-id="item.collage_jipin_id" :data-name="item.collage_jipin_name" :data-pic="item.pic">邀请好友</button>
                    </block>
                    <view v-if="hdinfo.invite_land == 0" class='fun-class invite-friends' @tap.stop="goto" :data-url="'/activity/commission/poster'">邀请好友</view>
                    <view class='fun-class view-details' style="margin-left: 25rpx;" v-if="hdinfo.opening_detail == 1" @tap.stop="ckjpxq" :data-url="'jipinCharts?id=' + item.id" :data-opening_detail="item.opening_detail">查看详情</view>
                  </view>
                </view>
              </view>
            </block>
          </view>
          <view v-else class="activity-list-view flex-col">
            <view class="flex active-options" style="text-align: center;display: block;color: #959494">{{clustering_jzai}}</view>
          </view>
        </block>
			</view>
		</block>
		<loading v-if="loading"></loading>
		<dp-tabbar :opt="opt"></dp-tabbar>
		<popmsg ref="popmsg"></popmsg>
	</view>
</template>

<script>
	var app = getApp();
	export default{
		data(){
			return{
				isload:true,
				pre_url: app.globalData.pre_url,
				tabIndex:0,
        hdid:0,
        clustering_list: {},
        group_cumulative:0,
        group_num:0,
        is_group:0,
        platform_cumulative:0,
        platform_group_num:0,
        group_recommend_num:0,
        product_list:{},
        progress_list: {},
        member: {},
        hdinfo: {},
        opt:{},
        loading:false,
        clustering_jzai:'加载中...',
        progress_jzai:'加载中...',
        product_jzai:'加载中...',
        shareTitle:'',
        shareDesc:'',
        sharePic:'',
        shareLink:'',
        ktjl:'',
        yx_collage_jipin_optimize:false,
			}
		},
    onLoad: function (opt) {
      this.opt = app.getopts(opt);

      this.getdata();
    },
		methods:{
			changeTab(e){
				this.tabIndex = e;
			},
      getdata: function () {
        var that = this;
        that.hdid = that.opt.hdid || 0
        that.loading = true;
        app.get('ApiCollageJipin/jipinrecord', {hdid:that.hdid}, function (res) {
          that.loading = false;
          if (res.status == 0) {
            app.error(res.msg);
            return;
          } else if (res.status == 2) {
            that.errmsg = res.msg;
            setTimeout(function () {
              app.goto('index');
            }, 1000);
          } else {
            that.progress_list = res.progress_list;
            that.product_list = res.product_list;
            that.platform_group_num = res.platform_group_num;
            that.platform_cumulative = res.platform_cumulative;
            that.is_group = res.is_group;
            that.group_num = res.group_num;
            that.group_cumulative = res.group_cumulative;
            that.clustering_list = res.clustering_list;
            that.member = res.member;
            that.hdinfo = res.hdinfo;
            that.ktjl = res.ktjl;
            that.group_recommend_num = res.group_recommend_num;
            that.yx_collage_jipin_optimize = res.yx_collage_jipin_optimize;
            if(that.progress_list.length <= 0){
              that.progress_jzai = '暂无数据~'
            }
            if(that.clustering_list.length <= 0){
              that.clustering_jzai = '暂无数据~'
            }
            if(that.product_list.length <= 0){
              that.product_jzai = '暂无数据~'
            }

            // that.shareTitle = that.hdinfo.name ? that.hdinfo.name : '即拼';
            // that.shareDesc = '';
            // that.sharePic = that.hdinfo.pic ? that.hdinfo.pic : '';
            // that.shareLink = app.globalData.pre_url +'/h5/'+app.globalData.aid+'.html#/pagesC/yingxiao/jipinRecord?scene=hdid_'+that.hdinfo.id;
          }
          that.loaded();
        });
      },

      //分享
      onShareAppMessage:function(res){
        this.shareTitle = res.target.dataset.name || '即拼';
        this.sharePic = res.target.dataset.pic;
        this.shareLink = app.globalData.pre_url +'/h5/'+app.globalData.aid+'.html#/pagesC/yingxiao/jipinRecord?scene=hdid_'+res.target.dataset.id;
        return this._sharewx({title:this.shareTitle,pic:this.sharePic,link:this.shareLink});
      },
      onShareTimeline:function(res){
        this.shareTitle = res.target.dataset.name || '即拼';
        this.sharePic = res.target.dataset.pic;
        this.shareLink = app.globalData.pre_url +'/h5/'+app.globalData.aid+'.html#/pagesC/yingxiao/jipinRecord?scene=hdid_'+res.target.dataset.id;
        var sharewxdata = this._sharewx({title:this.shareTitle,pic:this.sharePic,link:this.shareLink});
        var query = (sharewxdata.path).split('?')[1];

        return {
          title: sharewxdata.title,
          imageUrl: sharewxdata.imageUrl,
          query: query
        }
      },
      sharemp:function(res){
        app.error('点击右上角三个点发送给好友或分享到朋友圈');
        this.sharetypevisible = false;
        // let that = this;
        // let shareLink = app.globalData.pre_url +'/h5/'+app.globalData.aid+'.html#/pagesC/yingxiao/jipinRecord?scene=hdid_'+res.target.dataset.id;
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
      shareapp:function(res){
        var that = this;
        that.shareTitle = res.currentTarget.dataset.name || '即拼';
        that.sharePic = res.currentTarget.dataset.pic;
        that.shareLink = app.globalData.pre_url +'/h5/'+app.globalData.aid+'.html#/pagesC/yingxiao/jipinRecord?scene=hdid_'+res.currentTarget.dataset.id;
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
      ckjpxq:function(res){
        var url = res.currentTarget.dataset.url;
        var opening_detail = res.currentTarget.dataset.opening_detail
        if(opening_detail != 1){
          return;
        }
        app.goto(url);
      }
		}
	}
</script>

<style>
	/* #ifdef H5 */
	/deep/ .uni-progress-bar{border-radius: 12px;overflow: hidden;}
	/* #endif */
	.container {width: 94%;margin: 0 auto;}
	.statistics-view{width: 100%;background: #fb484c;border-radius: 36rpx;padding: 20rpx 20rpx 30rpx;color: #fff;margin-top: 30rpx;overflow: hidden;}
	.statistics-view .statais-options{flex: 1;}
	.statistics-view .statais-options .top-num{font-weight: bold;font-size: 38rpx;margin-bottom: 10rpx;width: 100%;white-space: nowrap;text-align: center;}
	.statistics-view .statais-options .title-text{font-size: 24rpx;}
	.user-info-view{background: #fff;margin-top: 30rpx;border-radius: 36rpx;padding: 30rpx;position: relative;overflow: hidden;}
	.user-info-view .user-top-view{width: 100%;border-bottom: 1px #ebeaea solid;justify-content: space-between;padding-bottom: 25rpx;}
	.user-info-view .user-top-view .info-view{flex: 1;}
	.user-info-view .user-top-view .info-view .avatar-view{width: 90rpx;height: 90rpx;border-radius: 50%;overflow: hidden;}
	.user-info-view .user-top-view .info-view .avatar-view image{width: 100%;height: 100%;}
	.user-info-view .user-top-view .info-view .user-name{font-size: 32rpx;color: #000;align-items: center;}
	.user-info-view .user-top-view .info-view .user-name .tuanzhang-tag{font-size: 24rpx;color: #fff001;background: #fb484c;padding: 3rpx 15rpx;margin-left: 20rpx;
	border-radius: 20rpx;}
	.user-top-view .info-view .user-name .tuanzhang-tag .tuanzhang-icon{width: 18rpx;height: 21rpx;margin-right: 5rpx;}
	.user-info-view .user-top-view .info-view .reference-name{font-size: 24rpx;color: #959494;margin-top: 15rpx;}
	.user-info-view .user-top-view .withdraw-money{z-index:9;font-size: 24rpx;color: #fff;background: #959494;padding: 10rpx;border-radius: 30rpx 0rpx 0rpx 30rpx;position: absolute;
	right: -10rpx;top: 50rpx;width: 150rpx;text-align: center;}
	.user-info-view .rewards-view{width: 100%;padding: 30rpx 0rpx 0rpx;justify-content: space-between;padding-bottom: 20rpx;}
	.user-info-view .rewards-view .rewards-options{}
	.rewards-view .rewards-options .rewards-text{font-size: 24rpx;white-space: nowrap;color: #959494;font-weight: bold;}
	.rewards-view .rewards-options .rewards-price{background: #fb484c;font-size: 30rpx;font-weight: bold;color: #fff001;text-align: center;width: 100%;
	border-radius: 34rpx;margin-top: 10rpx;padding: 5rpx 0rpx;}
	.mask-view{width: 100%;background: rgba(0,0,0,.6);position: absolute;left: 0;bottom: 95rpx;height: 145rpx;text-align: center;font-size: 40rpx;color: #fb484c;
	line-height: 155rpx;font-weight: bold;}
	.de-img-view{width: 100%;height: 55rpx;margin-top: 10rpx;}
	.de-img-view image{width: 100%;height: 100%;}
	/* 参团活动产品 */
	.user-info-view .user-top-view .activity-description{z-index:9;font-size: 24rpx;color: #fff;background: #959494;padding: 10rpx;border-radius: 30rpx 0rpx 0rpx 30rpx;position: absolute;
	right: -10rpx;top: 25rpx;width: 150rpx;text-align: center;}
	.shop-list-view{width: 100%;margin-top: 30rpx;}
	.shop-list-view .shop-view{display: inline-block;margin-right: 20rpx;}
	.shop-list-view .shop-view .shop-image{width: 200rpx;height: 200rpx;border-radius: 14rpx;overflow: hidden;}
	.shop-list-view .shop-view .shop-image image{width: 100%;height: 100%;}
	.shop-list-view .shop-view .shop-price-view{width: 100%;text-align: center;padding: 10rpx;font-size: 26rpx;color: #272727;}
	/* 进行中&已成团 */
	.tab-options{width: 43%;text-align: center;padding: 12rpx;border-radius: 30rpx;background-color: #e3e3e3;color: #484848;font-size: 26rpx;}
	.tab-options-active{background: #fb484c;color: #fff;}
	.activity-list-view{width: 100%;margin-top: 10rpx;}
	.activity-list-view .active-options{align-items: center;border-bottom: 1px #ebeaea solid;padding: 20rpx 0rpx;}
	.activity-list-view .active-options .active-image{width: 200rpx;height: 200rpx;border-radius: 14rpx;overflow: hidden;}
	.activity-list-view .active-options .active-image image{width: 100%;height: 100%;}
	.activity-list-view .active-options .active-info-list{margin-left: 45rpx;flex: 1;}
	.active-info-list .active-name{font-size: 26rpx;color: #000;font-weight: bold;white-space: nowrap;overflow: hidden;text-overflow: ellipsis;}
	.active-info-list .active-jingdu{font-size: 24rpx;color: #959494;white-space: nowrap;overflow: hidden;text-overflow: ellipsis;margin-top: 10rpx;}
	.active-info-list .reward-amount{font-size: 24rpx;color: #fb484c;white-space: nowrap;}
	.active-info-list .active-fun-view{margin-top: 15rpx;}
	.active-info-list .active-fun-view .fun-class{font-size: 24rpx;padding: 7rpx 30rpx;border-radius: 20px;color: #fff;}
	.active-info-list .active-fun-view .invite-friends{background: #fb484c;}
	.active-info-list .active-fun-view .view-details{background-color: #3b3b3b;}
</style>