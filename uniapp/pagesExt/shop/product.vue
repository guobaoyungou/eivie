<template>
<view class="container">
	<block v-if="isload">
		<block v-if="sysset.showgzts">
			<view style="width:100%;height:88rpx"> </view>
			<view class="follow_topbar">
				<view class="headimg"><image :src="sysset.logo"/></view>
				<view class="info">
					<view class="i">欢迎进入 <text :style="{color:t('color1')}">{{sysset.name}}</text></view>
					<view class="i">关注公众号享更多专属服务</view>
				</view>
				<view class="sub" @tap="showsubqrcode" :style="{'background-color':t('color1')}">立即关注</view>
			</view>
			<uni-popup id="qrcodeDialog" ref="qrcodeDialog" type="dialog">
				<view class="qrcodebox">
					<image :src="sysset.qrcode" @tap="previewImage" :data-url="sysset.qrcode" class="img"/>
					<view class="txt">长按识别二维码关注</view>
					<view class="close" @tap="closesubqrcode">
						<image :src="pre_url+'/static/img/close2.png'" style="width:100%;height:100%"/>
					</view>
				</view>
			</uni-popup>
		</block>

		<view style="position:fixed;top:15vh;left:20rpx;z-index:9;background:rgba(0,0,0,0.6);border-radius:20rpx;color:#fff;padding:0 10rpx" v-if="bboglist.length>0">
			<swiper style="position:relative;height:54rpx;width:350rpx;" :autoplay="true" :interval="5000" :vertical="true">
				<swiper-item v-for="(item, index) in bboglist" :key="index" @tap="goto" :data-url="'/pages/shop/product?id=' + item.proid" class="flex-y-center">
					<image :src="item.headimg" style="width:40rpx;height:40rpx;border:1px solid rgba(255,255,255,0.7);border-radius:50%;margin-right:4px"/>
					<view style="width:300rpx;white-space:nowrap;overflow:hidden;text-overflow: ellipsis;font-size:22rpx">{{item.nickname}} {{item.showtime}}购买了该商品</view>
				</swiper-item>
			</swiper>
		</view>

		<view class="toptabbar_tab" v-if="showtoptabbar==1 && toptabbar_show==1">
			<view class="item" :class="toptabbar_index==0?'on':''" :style="{color:toptabbar_index==0?t('color1'):'#333'}" @tap="changetoptab" data-index="0">商品<view class="after" :style="{background:t('color1')}"></view></view>
			<view class="item" :class="toptabbar_index==1?'on':''" :style="{color:toptabbar_index==1?t('color1'):'#333'}" @tap="changetoptab" data-index="1">评价<view class="after" :style="{background:t('color1')}"></view></view>
			<view class="item" :class="toptabbar_index==2?'on':''" :style="{color:toptabbar_index==2?t('color1'):'#333'}" @tap="changetoptab" data-index="2">详情<view class="after" :style="{background:t('color1')}"></view></view>
			<view class="item" v-if="tjdatalist.length > 0" :class="toptabbar_index==3?'on':''" :style="{color:toptabbar_index==3?t('color1'):'#333'}" @tap="changetoptab" data-index="3">推荐<view class="after" :style="{background:t('color1')}"></view></view>
		</view>

		<scroll-view @scroll="scroll" :scrollIntoView="scrollToViewId" :scrollTop="scrollTop" :scroll-y="true" style="height:100%;overflow:scroll">
		
		<view id="scroll_view_tab0">
			<!-- 定制：如果没有主图和视频，则商品头图不显示，页面整体上移 -->
			<block v-if="shopset.show_header_pic !== 0 ">
				<view class="swiper-container" v-if="isplay==0">
					<swiper class="swiper" :indicator-dots="false" :autoplay="true" :interval="5000" @change="swiperChange"   :current="current" :style="{ height: swiperHeight + 'px' }">
						<block v-for="(item, index) in product.pics" :key="index">
							<swiper-item class="swiper-item">
								<view v-if="show_image == 1" class="filter-background"></view>
								<view class="swiper-item-view" :id="'content-wrap' + index" :style="{ height: swiperHeight + 'px' }">
									<image class="img" :src="item" mode="widthFix" @tap="previewImage" :data-urls="product.pics" :data-url="item" @load="loadImg"  />
									<view v-if="show_image == 1" class="lock-image" @tap="gotolevelup">
											<image :src="pre_url+'/static/img/lock.png'" mode=""></image>
									</view>
								</view>
							</swiper-item>
						</block>
					</swiper>
					<view class="imageCount" v-if="product.diypics" @tap="goto" :data-url="'/pagesExt/shop/diylight?id='+product.id" style="bottom: 92rpx; width: 140rpx;">自助试灯</view>
					<view class="imageCount">{{current+1}}/{{(product.pics).length}}</view>
					<block v-if="product.video && show_image == 0">
						<block v-if="product.video_type==1" >
							<view v-if="product.video_feedtype==1"  class="wxfeedvideo">
								<view class="videop"><image class="playicon" :src="pre_url+'/static/img/video.png'"/><view class="txt">播放视频</view></view>
								<!-- #ifdef MP-WEIXIN  -->
								<channel-video class="feedvideo" :feed-token="product.video_feedtoken" :feed-id="product.video_feedid" :finder-user-name="product.video_finderuser" ></channel-video>
								<!-- #endif -->
							</view>
							<view v-if="product.video_feedtype==0" @tap="goto" :data-url="product.video" class="provideo">
								<image :src="pre_url+'/static/img/video.png'"/><view class="txt">查看视频</view>
							</view>
						</block>
						<view v-if="product.video_type==0" class="provideo" @tap="payvideo">
							<image :src="pre_url+'/static/img/video.png'"/><view class="txt">{{product.video_duration}}</view>
						</view>
					</block>
				</view>
			</block>
			<view class="videobox" v-if="isplay==1">
				<video :autoplay="true" class="video" id="video" :initial-time="initialTime" :src="product.video" :muted="mutedStatus" :show-mute-btn="showMuteBtnStatus"></video>
				<view class="parsevideo" @tap="parsevideo">退出播放</view>
			</view>
			<view class="cuxiaopoint cuxiaoitem" v-if="showtoptabbar==1 && couponlist.length>0" style="background:#fff;padding:0 16rpx">
				<view class="f1" @tap="showcuxiaodetail">
					<view v-for="(item, index) in couponlist" :key="index" class="t" :style="{background:'rgba('+t('color1rgb')+',0.1)',color:t('color1')}"><text class="t0" style="padding:0 6px">券</text><text class="t1">{{item.name}}</text></view>
				</view>
				<view class="f2" @tap="showcuxiaodetail">
					<image :src="pre_url+'/static/img/arrow-point.png'" mode="widthFix"/>
				</view>
			</view>

			<!-- 广告 -->
			<view style="background:#fff;width:100%;height:auto;padding:20rpx 20rpx 0" v-if="shopset.detail_guangao1">
				<image :src="shopset.detail_guangao1" style="width:100%;height:auto" mode="widthFix" v-if="shopset.detail_guangao1" @tap="showgg1Dialog"/>
			</view>
			<uni-popup id="gg1Dialog" ref="gg1Dialog" type="dialog" v-if="shopset.detail_guangao1 && shopset.detail_guangao1_t">
				<image :src="shopset.detail_guangao1_t" @tap="previewImage" :data-url="shopset.detail_guangao1_t" class="img" mode="widthFix" style="width:600rpx;height:auto;border-radius:10rpx;"/>
				<view class="ggdiaplog_close" @tap="closegg1Dialog">
					<image :src="pre_url+'/static/img/close2.png'" style="width:100%;height:100%"/>
				</view>
			</uni-popup>
			
			<view class="header">
				<block v-if="!custom.product_detail_special || (custom.product_detail_special && shopset.show_product_name)">
					<block v-if="product.price_type != 1 || product.min_price > 0">
						<view v-if="sysset.price_show_type=='0' || !sysset.price_show_type ">
							<!-- 定制的显示方式：字号 颜色等 -->
							<view v-if="custom.show_price">
								<view class="price_share custom_price" v-if="shopset.hide_cost==0 || shopset.hide_sellprice==0">
									<view>
										<view class="price price-row2" v-if="shopset.hide_cost==0" :style="{color:shopset.cost_color?shopset.cost_color:t('color1')}"><text :style="{color:shopset.cost_color?shopset.cost_color:t('color1')}"><text class="custom_price_tag">{{shopset.cost_name?shopset.cost_name:'成本价：￥'}}</text>{{product.cost_price}}</text></view>
										<view class="price price-row1" v-if="shopset.hide_sellprice==0">
											
											<view class="flex-s" v-if="sell_price" :style="{color:shopset.sellprice_color?shopset.sellprice_color:t('color1')}">
												<text class="custom_price_tag" >{{shopset.sellprice_name?shopset.sellprice_name:'￥'}}</text>{{sell_price}}</text>
                        <text v-if="product.price_show && product.price_show_text" style="margin: 0 15rpx;font-size: 24rpx;font-weight: 400;">{{product.price_show_text}}</text>
											</view>
											<view class="flex-s" v-else :style="{color:shopset.sellprice_color?shopset.sellprice_color:t('color1')}">
												<text class="custom_price_tag" >{{shopset.sellprice_name?shopset.sellprice_name:'￥'}}</text>{{product.min_price}}<text v-if="product.max_price!=product.min_price">-{{product.max_price}}</text><text v-if="product.product_unit">/{{product.product_unit}}</text>
                        <text v-if="product.price_show && product.price_show_text" style="margin: 0 15rpx;font-size: 24rpx;font-weight: 400;">{{product.price_show_text}}</text>
											</view>
											<view class="market_price" v-if="product.market_price*1 > product.sell_price*1">￥{{product.market_price}}<text v-if="product.max_price!=product.min_price">起</text></view>
										</view>
									</view>
									<block v-if="!custom.product_commission_desc">
										<view class="share" @tap="shareClick"><image class="img" :src="pre_url+'/static/img/share.png'"/><text class="txt">分享</text></view>
									</block>
								</view>
							</view>
							<!-- 系统默认显示方式 -->
							<view v-else>
								<view class="price_share">
									<!-- 价格 -->
									<view class="price">
										<view class="f1 flex-s" :style="{color:t('color1')}">
											<block v-if="showprice_dollar && product.usdmin_price !=0">
												<view style="margin-right:20rpx;"><text style="font-size:36rpx">$</text>{{product.usdmin_price}}<text v-if="product.usdmax_price!=product.usdmin_price">-{{product.usdmax_price}}</text></view>
												<view style="font-size: 40rpx;"><text style="font-size:32rpx">￥</text>{{product.min_price}}<text v-if="product.max_price!=product.min_price">-{{product.max_price}}</text></view>
                        <text v-if="product.price_show && product.price_show_text" style="margin: 0 15rpx;font-size: 24rpx;font-weight: 400;">{{product.price_show_text}}</text>
											</block>
											<block v-else>
												<block  v-if="custom.product_guige_showtype && shopset.show_guigetype==2">
													<text style="font-size:36rpx">￥</text>{{sell_price}}
												</block>
												<block v-else>
													<text style="font-size:36rpx">￥</text>{{product.min_price}}
													<text v-if="product.max_price!=product.min_price">-{{product.max_price}}</text>	
													<text v-if="product.product_unit">/{{product.product_unit}}</text>
                          <text v-if="product.price_show && product.price_show_text" style="margin: 0 15rpx;font-size: 24rpx;font-weight: 400;">{{product.price_show_text}}</text>
													<text v-if="!isNull(product.min_service_fee) && product.service_fee_switch && product.service_fee > 0" style="font-size: 38rpx;">+{{product.min_service_fee}}
													<text v-if="!isNull(product.max_service_fee) && product.max_service_fee!=product.min_service_fee">-{{product.max_service_fee}}</text>{{t('服务费')}}</text>
													<text v-if="!isNull(product.freezemoney_price) && product.freezemoney_price>0">+{{product.freezemoney_price}}{{t('冻结资金')}}</text>
												</block>
											</block>
											<block v-if="show_money_price">
												<view class="moneyprice" :style="'color:'+t('color1')+';background:rgba('+t('color1rgb')+',0.2)'">{{t('余额')}}</view>	
											</block>
										</view>
										<!-- 营销标签 -->
										<view class="yingxiao_tag" v-if="product.yingxiao_tag && product.yingxiao_tag.content">
											
											{{product.yingxiao_tag.content}}
											<view class="jiantou"></view>
										</view>
										<!-- 营销标签 end-->
										<view class="f2" v-if="product.market_price*1 > product.sell_price*1">￥{{product.market_price}}<text v-if="product.max_price!=product.min_price">起</text></view>
                    <!--成本价范围-->
                    <view class="f2" v-if="product.product_show_costprice_fanwei == 1" style="text-decoration:none !important;">￥{{product.cost_price_fanwei}}</view>
									</view>
									<view class="share" @tap="shareClick"><image class="img" :src="pre_url+'/static/img/share.png'"/><text class="txt">分享</text></view>
								</view>
							</view>
              <!-- 商品处显示会员价 -->
              <view v-if="product.price_show && product.price_show == 1" style="line-height: 50rpx;">
                <text style="font-size:30rpx">￥{{product.sell_putongprice}}</text>
              </view>
              <view v-if="product.priceshows && product.priceshows.length>0">
                <view v-for="(item,index) in product.priceshows" style="line-height: 50rpx;">
                  <text style="font-size:30rpx">￥{{item.sell_price}}</text>
                  <text style="margin-left: 15rpx;font-size: 24rpx;font-weight: 400;">{{item.price_show_text}}</text>
                </view>
              </view>
						</view>
						<view v-if="product.ictips" :style="'color:'+t('color1')+';line-height: 50rpx;'">{{product.ictips}}</view>
						<view v-if="sysset.price_show_type=='2' || sysset.price_show_type=='1'">
							<view v-if="product.is_vip=='1'">
								<view class="flex" v-if="product.lvprice == '1'">
									<view class="member flex" :style="'border-color:' + t('color1')">
										<view :style="{background:t('color1')}" class="member_lable flex-y-center">{{product.level_name}}</view>
										<view :style="'color:' + t('color1')" class="member_value">
											<text :style="product.lvprice == '1'?'font-size:36rpx':'font-size:26rpx'">￥</text>
											<text :style="product.lvprice == '1'?'font-size:50rpx':'font-size:26rpx'">{{product.sell_price || 0}}</text>
										</view>
									</view>
								</view>
								<view class="price_share" style="height: auto;">
									<view class="price">
										<view class="f1 flex-s" :style="{color:t('color1')}">
											<block v-if="showprice_dollar &&usdmin_price">
												<view style="margin-right:20rpx;"><text style="font-size:30rpx" v-if="product.usdmin_price">$</text>{{product.usdmin_price}}<text v-if="product.usdmax_price!=product.usdmin_price">-{{product.usdmax_price}}</text></view>
												<view style="font-size: 44rpx;"><text style="font-size:22rpx">￥</text>{{product.min_price}}<text v-if="product.max_price!=product.min_price">-{{product.max_price}}</text></view>
											</block>
											<block v-else>
												<text>
													<text :style="product.lvprice == '1'?'font-size:26rpx':'font-size:36rpx'">￥</text>
													<text :style="product.lvprice == '1'?'font-size:26rpx':'font-size:50rpx'">{{product.min_price}}</text>
												</text>
											</block>
											<block v-if="show_money_price">
												<view class="moneyprice" :style="'color:'+t('color1')+';background:rgba('+t('color1rgb')+',0.2)'">{{t('余额')}}</view>	
											</block>
										</view>
										<!-- <view class="f2" v-if="product.market_price*1 > product.sell_price*1">￥{{product.market_price}}<text v-if="product.max_price!=product.min_price">起</text></view> -->
									</view>
									<view class="share" @tap="shareClick"><image class="img" :src="pre_url+'/static/img/share.png'"/><text class="txt">分享</text></view>
								</view>
							</view>
							<view v-if="product.is_vip=='0'">
								<view class="price_share">
									<view class="price">
										<view class="f1 flex-s" :style="{color:t('color1')}">
											<block v-if="showprice_dollar">
												<view style="margin-right:20rpx;"><text style="font-size:36rpx">$</text>{{product.usdmin_price}}<text v-if="product.usdmax_price!=product.usdmin_price">-{{product.usdmax_price}}</text></view>
												<view>
													<text style="font-size:32rpx">￥</text>
													<text style="font-size: 40rpx;">{{product.min_price}}</text>
													<text v-if="product.max_price!=product.min_price">-{{product.max_price}}</text></view>
											</block>
											<block v-else>
												<text>
													<text style="font-size:36rpx">￥</text>
													<text style="font-size:50rpx">{{product.min_price}}</text>
												</text>
											</block>
											<block v-if="show_money_price">
												<view class="moneyprice" :style="'color:'+t('color1')+';background:rgba('+t('color1rgb')+',0.2)'">{{t('余额')}}</view>	
											</block>
										</view>
										<view class="f2" v-if="product.market_price*1 > product.sell_price*1">￥{{product.market_price}}<text v-if="product.max_price!=product.min_price">起</text></view>
                    <!--成本价范围-->
                    <view class="f2" v-if="product.product_show_costprice_fanwei == 1" style="text-decoration:none !important;">￥{{product.cost_price_fanwei}}</view>
									</view>
									<view class="share" @tap="shareClick"><image class="img" :src="pre_url+'/static/img/share.png'"/><text class="txt">分享</text></view>
								</view>
								<view class="flex" v-if="sysset.price_show_type=='2' &&  product.lvprice ==1 ">
									<view class="member flex" :style="'border-color:' + t('color1')">
										<view :style="{background:t('color1')}" class="member_lable flex-y-center">{{product.level_name}}</view>
										<view :style="'color:' + t('color1')" class="member_value">
											<text style="font-size:26rpx">￥</text>
											<text style="font-size:26rpx">{{product.vip_price}}</text>
										</view>
									</view>
								</view>
							</view>
						</view>
						
						<view class="sales_stock" v-if="product.yuanbao" style="margin: 0;font-size: 26rpx;margin-bottom: 10rpx;">
							<view class="f2">元宝价：{{product.yuanbao}}</view>
						</view>
						
						<view v-if="product.mangfan_status && product.mangfan_status==1" :style="{'color':product.mangfan_text_color}">{{product.mangfan_text}}</view>
						<view class="title">
              <view v-if="product.categoryDetailpics" style="display: flex;flex-wrap: wrap;">
                <block v-for="(item,index) in product.categoryDetailpics" >
                  <image :src="item" mode="heightFix" style="height:80rpx;margin-right: 10rpx;margin-bottom: 10rpx;"></image>
                </block>
              </view>
              <block v-if="product.labels && product.labels.length>0 ">
                <view v-for="(item,index) in product.labels" class="shop_label" :style="'color:'+product.labelcolor+';background-color:'+product.labelbgcolor">
                {{item.name}}
                </view>
              </block>
              {{product.name}}
            </view>
					</block>
					<block v-else>
						<view v-if="product.xunjia_text" class="price_share">
							<view class="price">
								<view class="f1" :style="{color:t('color1')}">
									<text style="font-size:36rpx">询价</text>
								</view>
							</view>
						</view>
						<view class="price_share">
							<view class="title" style="display:block">
                <block v-if="product.labels && product.labels.length>0 ">
                  <view v-for="(item,index) in product.labels" class="shop_label" :style="'color:'+product.labelcolor+';background-color:'+product.labelbgcolor">
                  {{item.name}}
                  </view>
                </block>
                {{product.name}}
              </view>
							<view class="share" @tap="shareClick"><image class="img" :src="pre_url+'/static/img/share.png'"/><text class="txt">分享</text></view>
						</view>
					</block>
				</block>
				
				<view class="sellpoint" v-if="product.sellpoint"  @tap="copy1" >{{product.sellpoint}}</view>
				<view class="sales_stock" v-if="shopset.hide_sales != 1 || shopset.hide_stock != 1">
					<view class="f1" v-if="shopset.hide_sales != 1">销量：{{product.sales}} </view>
					<view class="f2" v-if="shopset.hide_stock != 1">库存：{{product.stock}}</view>
				</view>
				<view class="ratio-list flex">
					<view class="ratio-label flex-y-center" v-if="custom.yx_new_score && product.newscore_ratio > 0"  :style="{color:t('color1'),borderColor:t('color1'), width: '220rpx'}">
						<view class="label" :style="{backgroundColor:t('color1'), width: '120rpx'}">{{t('新积分')}}</view>
						<view class="t1">{{product.newscore_ratio}}%</view>
					</view>
					<view class="ratio-label flex-y-center" v-if="custom.yx_new_score_speed_pack && product.newscore_pack_ratio > 0"  :style="{color:t('color1'),borderColor:t('color1'), width: '220rpx'}">
						<view class="label" :style="{backgroundColor:t('color1'), width: '120rpx'}">{{t('加速包')}}</view>
						<view class="t1">{{product.newscore_pack_ratio}}%</view>
					</view>
				</view>
				<block v-if="custom.commission_max_times_status==0">
					<block v-if="custom.product_commission_desc && shopset.commission_desc && shopset.showcommission==1">
						<view style="display: flex;">
							<view class="share" @tap="shareClick" style="display: flex; justify-content: center;align-items: center;   ">
								<image class="img" :src="pre_url+'/static/img/share.png'" style="width: 30rpx;" mode="widthFix"/><text style="margin:0 10rpx">分享</text></view>
								<view class="commission" :style="{background:'rgba('+t('color1rgb')+',0.1)',color:t('color1')}">	{{shopset.commission_desc}}</view>
						</view>
					</block>	
				 
					<block v-else>
						<view class="commission" :style="{background:'rgba('+t('color1rgb')+',0.1)',color:t('color1')}" v-if="shopset.showcommission==1 && (product.commission > 0 || product.commissionScore > 0) && showjiesheng==0">
								分享好友购买预计可得{{t('佣金')}}：
								<block v-if="product.commission > 0"><text style="font-weight:bold;padding:0 2px">{{product.commission}}</text>{{product.commission_desc}}</block>
								<block v-if="product.commission > 0 && product.commissionScore > 0">+</block>
								<block v-if="product.commissionScore > 0"><text style="font-weight:bold;padding:0 2px">{{product.commissionScore}}</text>{{product.commission_desc_score}}</block>
						</view>
					</block>
				</block>
				<block v-if="custom.commission_max_times_status && (product.commission_total1 > 0 || product.commission_total2 > 0)">
					<view class="commission" :style="{background:'rgba('+t('color1rgb')+',0.1)',color:t('color1')}">
							分享好友购买预计可得
							<block v-if="product.commission_total1 > 0"><text style="font-weight:bold;padding:0 2px">{{product.commission_total1}}</text>一级{{t('佣金')}}</block>
							<block v-if="product.commission_total1 > 0 && product.commission_total2 > 0">+</block>
							<block v-if="product.commission_total2 > 0"><text style="font-weight:bold;padding:0 2px">{{product.commission_total2}}</text>二级{{t('佣金')}}</block>
					</view>
				</block>
				<block v-if="custom.active_coin && product.give_active_coin > 0">
					<view class="commission" :style="{background:'rgba('+t('color1rgb')+',0.1)',color:t('color1')}" >
						购买预计可得{{t('激活币')}}：
						<text style="font-weight:bold;padding:0 2px">{{product.give_active_coin}}</text>
					</view>
				</block>
				<block v-if="custom.yx_farm && product.give_farm_seed > 0">
					<view class="commission" :style="{background:'rgba('+t('color1rgb')+',0.1)',color:t('color1')}" >
						购买预计可得{{farm_textset['种子']}}：
						<text style="font-weight:bold;padding:0 2px">{{product.give_farm_seed}}</text>
					</view>
				</block>
        <block v-if="custom.member_goldmoney_silvermoney && (product.givegoldmoney > 0 || product.givesilvermoney > 0)">
        	<view class="commission" :style="{background:'rgba('+t('color1rgb')+',0.1)',color:t('color1'),display:'inline-block',width:'auto'}">
        			下单购买预计可得
        			<block v-if="product.givesilvermoney > 0"><text style="font-weight:bold;padding:0 2px">{{product.givesilvermoney}}</text>{{t('银值')}}</block>
        			<block v-if="product.givegoldmoney > 0 && product.givesilvermoney > 0">+</block>
        			<block v-if="product.givegoldmoney > 0"><text style="font-weight:bold;padding:0 2px">{{product.givegoldmoney}}</text>{{t('金值')}}</block>
        	</view>
        </block>
				<view style="margin:20rpx 0;color:#333;font-size:22rpx" v-if="product.balance_price > 0">首付款金额：{{product.advance_price}}元，尾款金额：{{product.balance_price}}元</view>
				<view style="margin:20rpx 0;color:#666;font-size:22rpx" v-if="product.buyselect_commission > 0">下单被选奖励预计可得{{t('佣金')}}：<text style="font-weight:bold;padding:0 2px">{{product.buyselect_commission}}</text>元</view>

				<view class="upsavemoney" :style="{background:'linear-gradient(90deg, rgb(255, 180, 153) 0%, #ffcaa8 100%)',color:'#653a2b'}" v-if="product.upsavemoney > 0">
					<view class="flex1">升级到 {{product.nextlevelname}} 预计可节省<text style="font-weight:bold;padding:0 2px;color:#ca4312">{{product.upsavemoney}}</text>元</view>
					<view style="margin-left:20rpx;font-weight:bold;display:flex;align-items:center;color:#ca4312" @tap="goto" data-url="/pagesExt/my/levelup">立即升级<image :src="pre_url+'/static/img/arrowright2.png'" style="width:30rpx;height:30rpx"/></view>
				</view>
				<view class="upsavemoney" :style="{background:'linear-gradient(90deg, rgb(255, 180, 153) 0%, #ffcaa8 100%)',color:'#653a2b'}" v-if="product.upsavemoney2 > 0">
					<view class="flex1">升级到 {{product.nextlevelname2}} 预计可节省<text style="font-weight:bold;padding:0 2px;color:#ca4312">{{product.upsavemoney2}}</text>元</view>
					<view style="margin-left:20rpx;font-weight:bold;display:flex;align-items:center;color:#ca4312" @tap="goto" data-url="/pagesExt/my/levelup">立即升级<image :src="pre_url+'/static/img/arrowright2.png'" style="width:30rpx;height:30rpx"/></view>
				</view>
			</view>
			<!-- 分期 -->
			<view class="choose-fenqi" v-if="product.product_type == 5">
				<view class="f0">分期</view>
				<view class="fenqi-info-view">
					<view class="commission-fenqi" :style="{background:'rgba('+t('color1rgb')+',0.1)',color:t('color1')}">不分期购买商品包含{{product.fenqigive_couponnum}}张{{t('优惠券')}}</view>
					<view class="fenqi-list-view">
						<scroll-view scroll-x style="white-space: nowrap;width: 100%;">
							<block v-for="(item,index) in product.fenqi_data">
								<view class="fenqi-options">
									<view class="fenqi-num">
										<text>{{item.fenqi_num}}期</text>
										<text class="fenqi-bili" v-if="item.fenqi_num_ratio">支付比例{{item.fenqi_num_ratio}}%</text>
									</view>
									<view class='fenqi-give' v-if="item.fenqi_give_num">包含{{item.fenqi_give_num}}张{{t('优惠券')}}</view>
									<view class='fenqi-give' v-else>无赠送{{t('优惠券')}}</view>
								</view>
							</block>
						</scroll-view>
					</view>
				</view>
			</view>
			<!-- 推广标签 -->
			<view class="promotion-tag" v-if="shopset && shopset.show_promotion_tag">
				<view class="promotion-tag-box" :style="{color:t('color1')}">
					<view class="pro-tag" v-if="product && product.tag_commission_rate">{{t('佣金')}}率：<text>{{product.tag_commission_rate}}</text></view>
					<view class="pro-tag" v-if="product && product.tag_commission">预估{{t('佣金')}}：<text>￥{{product.tag_commission}}</text></view>
					<view class="pro-tag" v-if="product && product.tag_upgrade_money">{{t('升级多赚')}}：<text>￥{{product.tag_upgrade_money}}</text></view>
				</view>
			</view>
			<!-- END 推广标签 -->
			<block v-if="custom.product_guige_showtype && shopset.show_guigetype==2">
				<buydialog-show :proid="product.id" :btntype="btntype" @changeGuige="changeGuige" :menuindex="menuindex" @addcart="addcart" ></buydialog-show>
			</block>
			<block v-else>
				<block  v-if="(!custom.product_detail_special || (custom.product_detail_special && shopset.show_guige)) && dgprodata ==''">
					<view class="choose" @tap="buydialogChange" data-btntype="2">
						<view class="f0">规格</view>
						<view class="f1 flex1">
							<block v-if="product.price_type == 1">查看规格</block>
							<block v-else>请选择商品规格及数量</block>
							</view>
						<image class="f2" :src="pre_url+'/static/img/arrowright.png'"/>
					</view>
				</block>
			</block>
			<view class="cuxiaodiv" v-if="product.givescore">
				<view class="cuxiaopoint">
					<view class="f0">送{{t('积分')}}</view>
					<view class="f1" style="font-size:26rpx">购买可得{{t('积分')}}{{product.givescore}}个</view>
				</view>
			</view>
			<view class="cuxiaodiv" v-if="product.give_commission_max > 0">
				<view class="cuxiaopoint">
					<view class="f0">送{{t('佣金上限')}}</view>
					<view class="f1" style="font-size:26rpx">购买可得{{t('佣金上限')}}{{product.give_commission_max}}</view>
				</view>
			</view>
			<view class="cuxiaodiv" v-if="cuxiaolist.length>0 || couponlist.length>0 || fuwulist.length>0 || product.discount_tips!=''">
				<view class="fuwupoint cuxiaoitem" v-if="fuwulist.length>0">
					<view class="f0">服务</view>
					<view class="f1" @tap="showfuwudetail">
						<view class="t" v-for="(item, index) in fuwulist" :key="index">{{item.name}}</view>
					</view>
					<view class="f2" @tap="showfuwudetail">
						<image :src="pre_url+'/static/img/arrow-point.png'" mode="widthFix"/>
					</view>
				</view>
				<view class="cuxiaopoint cuxiaoitem" v-if="cuxiaolist.length>0">
					<view class="f0">促销</view>
					<view class="f1" @tap="showcuxiaodetail">
						<view v-for="(item, index) in cuxiaolist" :key="index" class="t" :style="{background:'rgba('+t('color1rgb')+',0.1)',color:t('color1')}"><text class="t0">{{item.tip}}</text><text class="t1">{{item.name}}</text></view>
					</view>
					<view class="f2" @tap="showcuxiaodetail">
						<image :src="pre_url+'/static/img/arrow-point.png'" mode="widthFix"/>
					</view>
				</view>
				<view class="cuxiaopoint cuxiaoitem" v-if="product.discount_tips!=''">
					<view class="f0">折扣</view>
					<view class="f1" style="padding-left:10rpx">{{product.discount_tips}}</view>
					<view class="f2" @tap="goto" data-url="/pagesExt/my/levelinfo">
						<image :src="pre_url+'/static/img/arrow-point.png'" mode="widthFix"/>
					</view>
				</view>
				<view class="cuxiaopoint cuxiaoitem" v-if="couponlist.length>0 && showtoptabbar==0">
					<view class="f0">优惠</view>
					<view class="f1" @tap="showcuxiaodetail">
						<view v-for="(item, index) in couponlist" :key="index" class="t" :style="{background:'rgba('+t('color1rgb')+',0.1)',color:t('color1')}"><text class="t0" style="padding:0 6px">券</text><text class="t1">{{item.name}}</text></view>
					</view>
					<view class="f2" @tap="showcuxiaodetail">
						<image :src="pre_url+'/static/img/arrow-point.png'" mode="widthFix"/>
					</view>
				</view>
			</view>
			<view v-if="showfuwudialog" class="popup__container">
				<view class="popup__overlay" @tap.stop="hidefuwudetail"></view>
				<view class="popup__modal">
						<view class="popup__title">
							<text class="popup__title-text">服务</text>
							<image :src="pre_url+'/static/img/close.png'" class="popup__close" style="width:36rpx;height:36rpx" @tap.stop="hidefuwudetail"/>
						</view>
						<view class="popup__content">
							<view v-for="(item, index) in fuwulist" :key="index" class="service-item">
								<view class="fuwudialog-content">
									<view class="f1">{{item.name}}</view>
									<text class="f2">{{item.desc}}</text>
								</view>
							</view>
						</view>
				</view>
			</view>
			<view v-if="showcuxiaodialog" class="popup__container">
				<view class="popup__overlay" @tap.stop="hidecuxiaodetail"></view>
				<view class="popup__modal coupon-modal">
						<view class="popup__title">
							<text class="popup__title-text">优惠促销</text>
							<image :src="pre_url+'/static/img/close.png'" class="popup__close" style="width:36rpx;height:36rpx" @tap.stop="hidecuxiaodetail"/>
						</view>
						<view class="popup__content">
							<view v-for="(item, index) in cuxiaolist" :key="index" class="service-item">
								<view class="suffix">
									<view class="type-name"><text style="border-radius:4px;border:1px solid #f05423;color: #ff550f;font-size:20rpx;padding:2px 5px">{{item.tip}}</text> <text style="color:#333;margin-left:20rpx">{{item.name}}</text></view>
								</view>
							</view>
							<couponlist :couponlist="couponlist" @getcoupon="getcoupon"></couponlist>
						</view>
				</view>
			</view>

			<view style="width:100%;height:auto;padding:20rpx 0 0" v-if="shopset.detail_guangao2">
				<image :src="shopset.detail_guangao2" style="width:100%;height:auto" mode="widthFix" v-if="shopset.detail_guangao2" @tap="showgg2Dialog"/>
			</view>
			<uni-popup id="gg2Dialog" ref="gg2Dialog" type="dialog" v-if="shopset.detail_guangao2 && shopset.detail_guangao2_t">
				<image :src="shopset.detail_guangao2_t" @tap="previewImage" :data-url="shopset.detail_guangao2_t" class="img" mode="widthFix" style="width:600rpx;height:auto;border-radius:10rpx;"/>
				<view class="ggdiaplog_close" @tap="closegg2Dialog">
					<image :src="pre_url+'/static/img/close2.png'" style="width:100%;height:100%"/>
				</view>
			</uni-popup>
		</view>

		<view id="scroll_view_tab1">

			<view class="commentbox" v-if="commentposition==0 && shopset.comment==1 && commentcount > 0">
				<view class="title">
					<view class="f1">评价({{commentcount}})</view>
					<view class="f2" @tap="goto" :data-url="'/pagesB/shop/commentlist?proid=' + product.id">好评率 <text :style="{color:t('color1')}">{{product.comment_haopercent}}%</text><image style="width:32rpx;height:32rpx;" :src="pre_url+'/static/img/arrowright.png'"/></view>
				</view>
				<view class="comment">
					<view class="item" v-if="commentlist.length>0">
						<view class="f1">
							<image class="t1" :src="commentlist[0].headimg"/>
							<view class="t2">{{commentlist[0].nickname}}</view>
							<view class="flex1"></view>
							<view class="t3"><image class="img" v-for="(item2,index2) in [0,1,2,3,4]" :key="index2"  :src="pre_url+'/static/img/star' + (commentlist[0].score>item2?'2native':'') + '.png'"/></view>
						</view>
						<view class="f2">
							<text class="t1">{{commentlist[0].content}}</text>
							<view class="t2">
								<block v-if="commentlist[0].content_pic!=''">
									<block v-for="(itemp, index) in commentlist[0].content_pic" :key="index">
										<view @tap="previewImage" :data-url="itemp" :data-urls="commentlist[0].content_pic">
											<image :src="itemp" mode="widthFix"/>
										</view>
									</block>
								</block>
							</view>
						</view>
						<view class="f3" @tap="goto" :data-url="'/pagesB/shop/commentlist?proid=' + product.id">查看全部评价</view>
					</view>
					<view v-else class="nocomment">暂无评价~</view>
				</view>
			</view>

		</view>

		<view id="scroll_view_tab2">
			<view v-if="product.choujiang && product.choujiang.custom_text">
				<view class="choujiangtext" :style="'background:'+product.choujiang.text_bgcolor+';color:'+product.choujiang.text_color">{{product.choujiang.custom_text}}</view>
			</view>
			<view class="shop" v-if="shopset.showjd==1 && business">
				<image :src="business.logo" class="p1"/>
				<view class="p2 flex1">
					<view class="t1">{{business.name}}</view>
					<view class="t2">{{business.desc}}</view>
				</view>
				<button class="p4" :style="{background:'linear-gradient(90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'}" @tap="goto" :data-url="product.bid==0?'/pages/index/index':'/pagesExt/business/index?id='+product.bid">进入店铺</button>
			</view>
			<!-- 自提商品附近门店S -->
			<view v-if="showNearbyMendian && mendianids.length>0 && latitude && longitude " class="nearby-mendian-box">
				<view class="nearby-mendian-title">
					<view class="t1">附近{{t('门店')}}<text v-if="mendianids.length>1">（{{mendianids.length}}家）</text></view>
					<view class="t2" @tap="goto" :data-url="'/pagesExt/business/mendian?bid='+product.bid+'&proid='+product.id"><text>{{mendianids.length>1?'全部':'查看'}}{{t('门店')}}</text><image :src="pre_url+'/static/img/arrowright.png'"></image></view>
				</view>
				<view class="nearby-mendian-info">
					<view class="b1" @tap="goto" :data-url="'/pages/shop/mendian?id='+mendian.id"><image :src="mendian.pic"></image></view>
					<view class="b2">
						<view class="t1" @tap="goto" :data-url="'/pages/shop/mendian?id='+mendian.id">{{mendian.name}}</view>
						<view class="t2 flex-y-center">
							<block v-if="mendian.distance">
								<view :style="{background:'rgba('+t('color1rgb')+',0.1)',color:t('color1')}" class="nearby-tag">最近</view> 
								<view class="mendian-distance">{{mendian.distance}} </view>
							</block>
							<view class="mendian-address">{{mendian.address?mendian.address:mendian.area}}</view>
						</view>
					</view>
					<view class="b3">
						<view @tap="callMendian" :data-tel="mendian.tel"><image :src="pre_url+'/static/img/location/tel.png'"></image></view>
						<!-- #ifndef MP-ALIPAY-->
						<view @tap="toMendian" :data-address="mendian.address" :data-longitude="mendian.longitude" :data-latitude="mendian.latitude"><image :src="pre_url+'/static/img/location/daohang.png'"></image></view>
						<!-- #endif -->
					</view>
				</view>
			</view>
			<!-- #ifdef MP-ALIPAY -->
				<!-- 支付宝先授权再定位 -->
				<view class="cuxiaodiv" v-if="showNearbyMendian && (longitude =='' || latitude =='')">
					<view class="cuxiaopoint">
						<view class="f0">定位服务未授权，授权后查看附近门店</view>
						<button class="shouquan" :style="{background:'linear-gradient(90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'}" @tap="toLocation">授权</button>
					</view>
				</view>
			<!-- #endif -->
			<!-- 自提商品附近门店E -->
			
			<block v-if="!isEmpty(product.paramdata)">
			<view class="detail_title"><view class="t1"></view><view class="t2"></view><view class="t0">商品参数</view><view class="t2"></view><view class="t1"></view></view>
			<view style="background:#fff;padding:20rpx 40rpx;" class="paraminfo">
				<view v-for="(item, index) in product.paramdata" class="paramitem">
					<view class="f1">{{index}}</view>
					<view class="f2">{{item}}</view>
				</view>
			</view>
			</block>
			<view v-if="shopset.prodetailtitle_type && shopset.prodetailtitle_type==1" class="detail_title"><view class="t1"></view><view class="t2"></view><view class="t0">{{shopset.prodetailtitle_value}}</view><view class="t2"></view><view class="t1"></view></view>
			<view v-else-if="shopset.prodetailtitle_type && shopset.prodetailtitle_type==2" class="detail_title"><image :src="shopset.prodetailtitle_value" mode="heightFix" style="height:60rpx"/></view>
			<view v-else-if="shopset.prodetailtitle_type && shopset.prodetailtitle_type==3"></view>
			<view v-else class="detail_title"><view class="t1"></view><view class="t2"></view>
			<view class="t0">商品描述</view><view class="t2"></view><view class="t1"></view></view>
			<view class="detail">
				<dp :pagecontent="pagecontent"></dp>
				<image v-if="bottomImg" class="bottomimg" :src="bottomImg" mode="widthFix" />
			</view>

		</view>
		<view class="commentbox" v-if="commentposition==1">
			<view class="title">
				<view class="f1">评价({{commentcount}}) <image v-if="shopset.product_comment==1" class="addcommentimg" @tap.stop="goto" :data-url="'/pagesA/shop/productComment?proid='+product.id" :src="pre_url+'/static/img/edit1.png'"></view>
				<view class="f2" @tap="goto" :data-url="'/pagesB/shop/commentlist?proid=' + product.id">好评率 <text :style="{color:t('color1')}">{{product.comment_haopercent}}%</text><image style="width:32rpx;height:32rpx;" :src="pre_url+'/static/img/arrowright.png'"/></view>
			</view>
			<view class="comment">
				<view class="item" v-if="commentlist.length>0">
					<view class="f1">
						<image class="t1" :src="commentlist[0].headimg"/>
						<view class="t2">{{commentlist[0].nickname}}</view>
						<view class="flex1"></view>
						<view class="t3"><image class="img" v-for="(item2,index2) in [0,1,2,3,4]" :key="index2"  :src="pre_url+'/static/img/star' + (commentlist[0].score>item2?'2native':'') + '.png'"/></view>
					</view>
					<view class="f2">
						<text class="t1">{{commentlist[0].content}}</text>
						<view class="t2">
							<block v-if="commentlist[0].content_pic!=''">
								<block v-for="(itemp, index) in commentlist[0].content_pic" :key="index">
									<view @tap="previewImage" :data-url="itemp" :data-urls="commentlist[0].content_pic">
										<image :src="itemp" mode="widthFix"/>
									</view>
								</block>
							</block>
						</view>
					</view>
					<view class="f3" @tap="goto" :data-url="'/pagesB/shop/commentlist?proid=' + product.id">查看全部评价</view>
				</view>
				<view v-else class="nocomment">暂无评价~</view>
			</view>
		</view>
		<view id="scroll_view_tab3">

			<view v-if="tjdatalist.length > 0">
				<view class="xihuan">
					<view class="xihuan-line"></view>
					<view class="xihuan-text">
						<image :src="pre_url+'/static/img/xihuan.png'" class="img"/>
						<text class="txt">为您推荐</text>
					</view>
					<view class="xihuan-line"></view>
				</view>
				<view class="prolist">
					<dp-product-item :data="tjdatalist" @addcart="addcart" :menuindex="menuindex"></dp-product-item>
				</view>
			</view>

		</view>

		<view style="width:100%;height:140rpx;"></view>

		</scroll-view>
		<block  v-if="!custom.product_detail_special || (custom.product_detail_special && shopset.show_option_group)">
		<view class="bottombar flex-row" :class="menuindex>-1?'tabbarbot':'notabbarbot'" v-if="product.status==1&&!showcuxiaodialog&&!showfuwudialog">
			<view class="f1 flex" v-if="shopdetail_menudataList">
				<block v-for="(item,index) in shopdetail_menudataList" v-if="item.isShow == 1">
					<block v-if='item.menuType == 1'>
						<block v-if="item.useSystem == 1">
							<view class="item" @tap="goto" :data-url="kfurl" v-if="kfurl!='contact::'">
								<image class="img"
									:src="item.iconPath ? item.iconPath:pre_url+'/static/img/kefu.png'" />
								<view class="t1">{{item.text ? item.text:'客服'}}</view>
							</view>
							<button class="item" v-else open-type="contact" show-message-card="true">
								<image class="img"
									:src="item.iconPath ? item.iconPath:pre_url+'/static/img/kefu.png'" />
								<view class="t1">{{item.text ? item.text:'客服'}}</view>
							</button>
						</block>
						<block v-else>
							<button class="item" open-type="contact"  v-if="item.pagePath == 'contact::'" show-message-card="true">
								<image class="img"
									:src="item.iconPath ? item.iconPath:pre_url+'/static/img/kefu.png'" />
								<view class="t1">{{item.text ? item.text:'客服'}}</view>
							</button>
							<view class="item" @tap="addfavorite2(item)" v-else>
								<image class="img"
									:src="item.iconPath ? item.iconPath:pre_url+'/static/img/kefu.png'" />
								<view class="t1">{{item.text ? item.text:'客服'}}</view>
							</view>
						</block>
					</block>
					<block v-if='item.menuType == 2'>
						<view class="item" @tap="addfavorite2(item)">
							<image class="img"
								:src="item.iconPath ? item.iconPath:pre_url+'/static/img/gwc.png'" />
							<view class="t1">{{item.text ? item.text:'购物车'}}</view>
							<view class="cartnum" v-if="cartnum>0" :style="{background:'rgba('+t('color1rgb')+',0.8)'}">{{cartnum}}
							</view>
						</view>
					</block>
					<block v-if='item.menuType == 3'>
						<view class="item" @tap="addfavorite2(item)">
							<image class="img" :src="item.iconPath ? item.iconPath:pre_url+'/static/img/shoucang.png'"	v-if='!isfavorite' />
							<image class="img" :src="item.selectedIconPath ? item.selectedIconPath:pre_url+'/static/img/shoucang.png'"	v-else />
							<view class="t1" v-if='item.selectedtext && item.text'>{{isfavorite ? item.selectedtext:item.text}}</view>
							<view class="t1" v-else>{{isfavorite?'已收藏':'收藏'}}</view>
						</view>
					</block>
					<block v-if='item.menuType == 4'>
						<view class="item" @tap="addfavorite2(item)">
							<image class="img" :src="item.iconPath" />
							<view class="t1">{{item.text}}</view>
						</view>
					</block>
				</block>
			</view>
			<!-- 为空数据默认展示 -->
			<view class="f1" v-else>
				<view class="item" @tap="goto" :data-url="kfurl" v-if="kfurl!='contact::'">
					<image class="img" :src="pre_url+'/static/img/kefu.png'"/>
					<view class="t1">客服</view>
				</view>
				<button class="item" v-else open-type="contact" show-message-card="true">
					<image class="img" :src="pre_url+'/static/img/kefu.png'"/>
					<view class="t1">客服</view>
				</button>
				<block v-if="custom.shop_gwc_name">
					<view class="item flex1" @tap="goto" data-url="/pages/shop/cart" v-if="shopset.gwc_showst==1">
						<image class="img" :src="pre_url+'/static/img/gwc.png'"/>
						<view class="t1">{{shopset.gwc_name?shopset.gwc_name:'购物车'}}</view>
						<view class="cartnum" v-if="cartnum>0" :style="{background:'rgba('+t('color1rgb')+',0.8)'}">{{cartnum}}</view>
					</view>
				</block>
				<block v-else>
					<view class="item flex1" @tap="goto" data-url="/pages/shop/cart">
						<image class="img" :src="pre_url+'/static/img/gwc.png'"/>
						<view class="t1">购物车</view>
						<view class="cartnum" v-if="cartnum>0" :style="{background:'rgba('+t('color1rgb')+',0.8)'}">{{cartnum}}</view>
					</view>
				</block>
				<view class="item" @tap="addfavorite">
					<image class="img" :src="pre_url+'/static/img/shoucang.png'"/>
					<view class="t1">{{isfavorite?'已收藏':'收藏'}}</view>
				</view>
			</view>
			
			<view class="op2" v-if="showjiesheng==1">
				<view class="tocart2" :style="{background:t('color2')}" @tap="shareClick"><text>分享赚钱</text><text style="font-size:24rpx">赚￥{{product.commission}}</text></view>
					<block v-if="!product.buybtn_link_url">
							<view class="tobuy2" :style="{background:t('color1')}" @tap="buydialogChange" data-btntype="2">
									<text>{{product.buybtn_name?product.buybtn_name:"立即购买"}}</text>
									<text style="font-size:24rpx" v-if="product.jiesheng_money > 0">省￥{{product.jiesheng_money}}</text>
							</view>
					</block>
					<block v-else>
							<view class="tobuy2" :style="{background:t('color1')}" @tap="goto" :data-url="product.buybtn_link_url">
									<text>{{product.buybtn_name?product.buybtn_name:"立即购买"}}</text>
									<text style="font-size:24rpx" v-if="product.jiesheng_money > 0">省￥{{product.jiesheng_money}}</text>
							</view>
					</block>
			</view>
			<view class="op" v-else-if="is_member_auto_addlogin==1">
				<view class="tobuy flex-x-center flex-y-center" :style="{background:t('color1')}" @tap="buydialogChange" data-btntype="3">
						{{product.buybtn_name?product.buybtn_name:"立即购买"}}
				</view>
			</view>
      <view class="op" v-else-if="poshopid && poshopid>0">
      	<view class="tobuy flex-x-center flex-y-center" :style="{background:t('color1')}" @tap="buydialogChange" data-btntype="2">
      			{{product.buybtn_name?product.buybtn_name:"立即购买"}}
      	</view>
      </view>
			<view class="op" v-else>
				<block v-if="product.price_type == 1">
					<view v-if="!custom.product_xunjia_btn" class="tobuy flex-x-center flex-y-center" :style="{background:t('color1')}" @tap="showLinkChange" data-btntype="2">{{product.xunjia_text?product.xunjia_text:'联系TA'}}</view>
					<block v-if="custom.product_xunjia_btn && product.show_xunjia_btn=='1'">
						<view v-if="product.xunjia_btn_url" class="tobuy flex-x-center flex-y-center" :style="{background:product.xunjia_btn_bgcolor?product.xunjia_btn_bgcolor:t('color1'),color:product.xunjia_btn_color?product.xunjia_btn_color:'#FFF'}" @tap="goto" :data-url="product.xunjia_btn_url">{{product.xunjia_text?product.xunjia_text:'联系TA'}}</view>
						<view v-else class="tobuy flex-x-center flex-y-center" :style="{background:product.xunjia_btn_bgcolor?product.xunjia_btn_bgcolor:t('color1'),color:product.xunjia_btn_color?product.xunjia_btn_color:'#FFF'}" @tap="showLinkChange" data-btntype="2">{{product.xunjia_text?product.xunjia_text:'联系TA'}}</view>
					</block>
				</block>
				<block v-else>
					<block v-if="custom.product_guige_showtype==1 && shopset.show_guigetype==2">
						<view class="tocart flex-x-center flex-y-center" :style="{background:t('color2')}" @tap="addcart2" data-btntype="1" v-if="product.freighttype!=3 && product.freighttype!=4 && product.product_type != 9">加入{{shopset.gwc_name?shopset.gwc_name:"购物车"}}</view>
					</block>
					<block v-else>
						<block v-if="!product.addcart_link_url">
							<!-- 商品柜有数据隐藏 -->
							<block v-if="dgprodata ==''">
								<view class="tocart flex-x-center flex-y-center" :style="{background:t('color2')}" @tap="buydialogChange" data-btntype="1" v-if="product.freighttype!=3 && product.freighttype!=4 && product.product_type != 9">{{product.addcart_name?product.addcart_name:"加入购物车"}}</view>
							</block>
						</block>
						<block v-else>
								<view class="tocart flex-x-center flex-y-center" :style="{background:t('color2')}" @tap="goto" :data-url="product.addcart_link_url" v-if="product.freighttype!=3 && product.freighttype!=4 && product.product_type != 9">{{product.addcart_name?product.addcart_name:"加入购物车"}}</view>
						</block>
					</block>	
					
					<view class="tobuy flex-x-center flex-y-center" :style="{background:t('color1')}" @tap="buydialogChange" data-btntype="2" v-if=" product.shop_yuding &&  product.stock <= 0 && product.yuding_stock > 0 ">预定</view>
					<block v-else>
							<block v-if="custom.product_guige_showtype==1 && shopset.show_guigetype==2">
								<view class="tobuy flex-x-center flex-y-center" :style="{background:t('color1')}" @tap="tobuy" >立即购买</view>
							</block>	
							<block v-else>	
								<block v-if="!product.buybtn_link_url">
										<!-- 商品柜购买按钮 -->
										<block v-if="dgprodata !='' && devicedata !=''">
											<view class="tobuy flex-x-center flex-y-center" :style="{background:t('color1')}" @click="goto" :data-url="'/pagesB/shop/buy?prodata='+dgprodata+'&devicedata='+devicedata">
													{{product.buybtn_name?product.buybtn_name:"立即购买"}}
											</view>
										</block>
										
										<block v-else>
											<view class="tobuy flex-x-center flex-y-center" :style="{background:t('color1')}" @tap="buydialogChange" data-btntype="2">
													{{product.buybtn_name?product.buybtn_name:"立即购买"}}
											</view>
										</block>
										
								</block>
								<block v-else>
										<view class="tobuy flex-x-center flex-y-center" :style="{background:t('color1')}" @tap="goto" :data-url="product.buybtn_link_url">
												{{product.buybtn_name?product.buybtn_name:"立即购买"}}
										</view>
								</block>
							</block>
					</block>
				</block>
			</view>
		</view>
		</block>
		<block v-if="product.product_type == 4 || product.product_type == 6">
			<block v-if="JSON.parse(product.guigedata).length == 2 && product.product_type != 6">
				<buydialog-pifa v-if="buydialogShow" :proid="product.id" :btntype="btntype" :poshopid="poshopid" @buydialogChange="buydialogChange" @showLinkChange="showLinkChange" :menuindex="menuindex" @addcart="addcart" />
			</block>
			<block v-else>
				<buydialog-pifa2 v-if="buydialogShow" :proid="product.id" :btntype="btntype" :poshopid="poshopid" @buydialogChange="buydialogChange" @showLinkChange="showLinkChange" :menuindex="menuindex" @addcart="addcart" />
			</block>
		</block>
		<block v-else>
			<buydialog v-if="buydialogShow" :proid="product.id" :btntype="btntype" :poshopid="poshopid" @buydialogChange="buydialogChange" @showLinkChange="showLinkChange" :menuindex="menuindex" @addcart="addcart"></buydialog>
		</block>
		<!-- 采购单 选择规格弹窗-->
		<block>
			<buydialog-purchase v-if="purchaseOrderShow" :proid="product.id" @buydialogChange="purchaseorder" :menuindex="menuindex"></buydialog-purchase>
		</block>
		<!-- END 采购单 选择规格弹窗 -->
		<view class="scrolltop" v-show="scrolltopshow" @tap="changetoptab" data-index="0"><image class="image" :src="pre_url+'/static/img/gotop.png'"/></view>
		
		<view v-if="sharetypevisible" class="popup__container">
			<view class="popup__overlay" @tap.stop="handleClickMask"></view>
			<view class="popup__modal" style="height:320rpx;min-height:320rpx">
				<!-- <view class="popup__title">
					<text class="popup__title-text">请选择分享方式</text>
					<image :src="pre_url+'/static/img/close.png'" class="popup__close" style="width:36rpx;height:36rpx" @tap.stop="hidePstimeDialog"/>
				</view> -->
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
						<!-- #ifdef MP-WEIXIN -->
						<view class="f1" @tap="shareScheme" v-if="xcx_scheme">
							<image class="img" :src="pre_url+'/static/img/weixin.png'"/>
							<text class="t1">小程序链接</text>
						</view>
						<!-- #endif -->
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
		
		<view class="posterDialog schemeDialog" v-if="showScheme">
			<view class="main">
				<view class="close" @tap="schemeDialogClose"><image class="img" :src="pre_url+'/static/img/close.png'"/></view>
				<view class="schemecon">
					<view style="line-height: 60rpx;">{{product.name}} </view>
					<view >购买链接：<text style="color: #00A0E9;">{{schemeurl}}</text></view>
					<view class="copybtn" :style="{background:'linear-gradient(90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'}"  @tap.stop="copy" :data-text="product.name+'购买链接：'+schemeurl"> 一键复制 </view>
				</view>
			</view>
		</view>
		
		<view class="posterDialog linkDialog" v-if="showLinkStatus">
			<view class="main">
				<view class="close" @tap="showLinkChange"><image class="img" :src="pre_url+'/static/img/close.png'"/></view>
				<view class="content">
					<view class="title">{{sysset.name}}</view>
					<view class="row" v-if="product.bid > 0">
						<view class="f1" style="width: 150rpx;">店铺名称</view>
						<view class="f2 flex-y-center flex-x-bottom" style="font-size: 28rpx;width: 100%;max-width: 470rpx;display: flex;" @tap="goto" :data-url="'/pagesExt/business/index?id='+product.bid">
              <view style="width: 100%;white-space: nowrap;text-overflow: ellipsis;overflow: hidden;">{{business.name}}</view>
              <view style="flex: 1;"></view>
							<image :src="pre_url+'/static/img/arrowright.png'" class="image"/>
						</view>
					</view>
					<view class="row" v-if="business.tel">
						<view class="f1" style="width: 150rpx;">联系电话</view>
						<view class="f2 flex-y-center flex-x-bottom" style="width: 100%;max-width: 470rpx;" @tap="goto" :data-url="'tel::'+business.tel" :style="{color:t('color1')}">
              {{business.tel}}
              <image :src="pre_url+'/static/img/copy.png'" class="copyicon" @tap.stop="copy" :data-text="business.tel"></image>
            </view>
					</view>
				</view>
			</view>
		</view>
		<!-- 采购单详情页 -->
		<view class="suspension" v-if="shopset && shopset.show_purchase_order == 1">
				<view @tap="purchaseorder" class="suspension-purchase">
					<text class="suspension-text">加入</text>
					<text class="suspension-text">采购单</text>
				</view>
		</view>
	</block>
	<!-- 眼镜批发 -->
	<uni-popup ref="glassesPupup" type="bottom" :safeArea='false' v-if="product.guige_show_type == 1 && JSON.parse(product.guigedata).length == 2">
		<view class="glasses-pupup-view"  v-if="show_guige_type == 1">
			<view class="glasses-pupup-close" @tap="glassesPopupClose">
				<image :src="pre_url+'/static/img/close.png'" />
			</view>
			<!-- 商品信息 -->
			<view class="glasses-pupup-product flex">
				<view class="glasses-product-image">
					<image :src="nowguigeProduct.pic || product.pic" @tap="previewImage" :data-url="nowguigeProduct.pic || product.pic" mode="scaleToFill"></image>
				</view>
				<view class="glasses-product-info flex flex-col">
					<view class="glasses-product-name">{{product.name}}</view>
					<view class="glasses-product-price flex" :style="{color:t('color1')}">
						<view style="font-weight: bold;font-size: 32rpx;">￥</view>{{nowguigeProduct.sell_price}}
					</view>
					<!-- <view class="glasses-product-tag" :style="{background: 'rgba(' +t('color1rgb') + ',0.4)',color:t('color1')}">
						{{nowguigeProduct.name}}
					</view> -->
				</view>
			</view>
			<!-- 规格类目 -->
			<view class="glasses-product-class flex flex-col">
				<view class="g-productClass-top flex">
					<view class="left-view">
						<view class="left-view-text flex flex-col">
							<view class="left-view-name">{{guigedata[1].title}}</view>
							<!-- <view style="font-size: 20rpx;">(c)</view> -->
						</view>
					</view>
					<view class="right-view flex">
						<scroll-view class="scroll-view-class" :scroll-x='true' :scroll-left="scrollLeft" scroll-with-animation @scrolltoupper='scrollToupper'>
							<block v-for="(xItem,xIndex) in guigedata[1].items" :key="xIndex">
								<view :class="[xIndex == classIndex ? 'right-view-active':'','right-view-options flex']" @click="classChange(xIndex)">
									<view>{{xItem.title}}</view>
									<view class="options-tag" v-if="xItem.num > 0">{{xItem.num}}</view>
								</view>
							</block>
						</scroll-view>
					</view>
					<view class="right-view-but flex" @click="slideClass">
						<image :src="pre_url+'/static/img/arrowright.png'"></image>
					</view>
				</view>
				<view class="g-productClass-bottom flex">
					<view class="left-view flex">
						<view class="select-view flex" @tap="changeradioAll">
							<image class="select-view-image" :src="pre_url+'/static/img/duihao.png'"></image>
						</view>
						<view class="left-view-class flex">
							<view class="title-text">{{guigedata[0].title}}</view>
							<!-- <view class='left-view-sku'>(s)</view> -->
						</view>
					</view>
					<view class="right-view flex">
						<view class="right-view-bg"></view>
					</view>
				</view>
				<!-- 规格展示区域 -->
				<view class="glasses-sku-view flex flex-col">
					<scroll-view scroll-y style="width: 100%;height:100%;">
						<block v-for="(item,index) in guigelist[ksk]" :key="index">
							<view class='glasses-sku-options flex'>
								<view class='left-sku-view flex' @tap="changeradio" :data-index="index">
									<view :class="[item.checked ? 'select-view select-view-active flex' : 'select-view flex']">
										<image class="select-view-image" :src="pre_url+'/static/img/duihao.png'"></image>
									</view>
									<view :class="[item.checked ? 'left-view-active':'', 'left-view-class flex']">{{item.x_name}}</view>
								</view>
								<view class="right-sku-view flex">
									<view class="right-view-bg flex" @tap="changeradio" :data-index="index">
										<view :class="[item.checked ? 'right-view-num-active':'', item.num >0 && item.checked ? 'right-view-num-active2':'','right-view-num flex']">
											{{item.y_name}}
										</view>
									</view>
									<view class="right-view-inventory">
										库存：{{item.stock}}
									</view>
									<view class="right-count-view flex">
										<view class="but-class" @tap="gwcminus(item,index)">-</view>
										<view class="input-view">
											<input class="input-class" type="number" :value="item.num" @blur="gwcinput($event,item,index)"/>
										</view>
										<view class="but-class" @tap="gwcplus(item,index)">+</view>
									</view>
								</view>
							</view>
						</block>
					</scroll-view>
				</view>
				<!-- 底部按钮 -->
				<view class="glasses-bottom-but flex glasses-bottomclass">
					<view class='bottom-but-class' :style="{background:t('color2')}" @tap="addcart2">加入购物车</view>
					<view class='bottom-but-class' :style="{background:t('color1')}" @tap="tobuy">立即购买</view>
				</view>
			</view>
		</view>
	</uni-popup>
	<loading v-if="loading"></loading>
	<dp-tabbar :opt="opt" @getmenuindex="getmenuindex"></dp-tabbar>
	<popmsg ref="popmsg"></popmsg>
	<wxxieyi></wxxieyi>
</view>
</template>

<script>
var app = getApp();
var interval = null;

export default {
	data() {
		return {
			opt:{},
			loading:false,
      isload: false,
			menuindex:-1,
			pre_url: app.globalData.pre_url,
			textset:{},
			isload:false,
			buydialogShow: false,
			btntype:1,
			isfavorite: false,
			current: 0,
			isplay: 0,
			showcuxiaodialog: false,
			showfuwudialog:false,
			business: "",
			product: [],
			cartnum: "",
			commentlist: "",
			commentcount: "",
			cuxiaolist: "",
			couponlist: "",
			fuwulist: [],
			pagecontent: "",
			shopset: {},
			sysset:{},
			custom:{},
			title: "",
			bboglist: "",
			sharepic: "",
			sharetypevisible: false,
			showposter: false,
			posterpic: "",
			scrolltopshow: false,
			kfurl:'',
			showLinkStatus:false,
			showjiesheng:0,
			is_member_auto_addlogin:0,
			tjdatalist:[],
			showtoptabbar:0,
			toptabbar_show:0,
			toptabbar_index:0,
      scrollToViewId: "",
			scrollTop:0,
			scrolltab0Height:0,
			scrolltab1Height:0,
			scrolltab2Height:0,
			scrolltab3Height:0,
			xcx_scheme:false,
			showScheme:false,
			schemeurl:'',
			showprice_dollar:false,
			show_money_price:false,
			//自提商品门店显示
			showNearbyMendian:false,
			longitude: '',
			latitude: '',
			mendianids:[],
			mendian:{},
			mendian_id:0,
			//自提商品门店显示
			commentposition:0,//评论显示位置
			swiperHeight: '',
			sell_price:0,
			isloadAd:0,
			shopdetail_menudataList:[],
			bottomImg:'',//公共底部图片
			dgprodata:'',//商品柜带购买参数
			devicedata:'',//设备组合信息
			purchaseOrderShow:false, //采购单
			show_image:0, //产品详情大图限制观看
			scrollLeft:0,
			classIndex:0,
			guigelist:{},
			guigedata:{},
			ksk:0, //默认选择第一个规格数组
			nowguigeProduct:{}, //默认规格
			gwcnum:0,
			show_guige_type:0,//显示规格
      
      poshopid:0,//排队系统里的店铺ID参数，用于页面进入来源区别
      farm_textset:[],//农场自定义文字
      initialTime:0,//视频初始播放的位置，单位（s）
      mutedStatus:false,//视频是否静音播放
      showMuteBtnStatus:false,//视频是否显示静音按钮
		};
	},
  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.mendian_id = app.getCache('user_selected_mendianid')
    if(this.opt.sharetypevisible){
      this.sharetypevisible = true;
    }
		if(this.opt.dgprodata ){
			this.dgprodata = this.opt.dgprodata;
		}
		if(this.opt.devicedata){
			this.devicedata = this.opt.devicedata;
		}
    this.poshopid = this.opt.poshopid || 0;
		this.getdata();
	},
	onShow:function(e){
		if(this.product.product_type==1){
			uni.$emit('getglassrecord');
		}
	},
	onPullDownRefresh: function () {
		this.getdata();
	},
	onShareAppMessage:function(){
		return this._sharewx({title:this.product.sharetitle || this.product.name,pic:this.product.sharepic || this.product.pic});
	},
	onShareTimeline:function(){
		var sharewxdata = this._sharewx({title:this.product.sharetitle || this.product.name,pic:this.product.sharepic || this.product.pic});
		var query = (sharewxdata.path).split('?')[1]+'&seetype=circle';
		return {
			title: sharewxdata.title,
			imageUrl: sharewxdata.imageUrl,
			query: query
		}
	},
	onUnload: function () {
		clearInterval(interval);
	},

	methods: {
		glassesPopupClose(){
			this.ksk = 0;
			this.$refs.glassesPupup.close();
		},
		classChange(index){
		this.ksk = index;
			this.classIndex = index;
		},
		// 眼镜-滑动到最左边
		scrollToupper(){
			this.scrollLeft = 0;
		},
		// 眼镜-规格滑动
		slideClass(){
			this.scrollLeft += 89;
		},
		showLinkChange: function () {
			this.showLinkStatus = !this.showLinkStatus;
		},
		getdata:function(){
			var that = this;
			var id = this.opt.id || 0;
			that.loading = true;
			var devicedata = that.devicedata
			app.get('ApiShop/product', {id: id,longitude:that.longitude,latitude:that.latitude,mendian_id:that.mendian_id,devicedata:devicedata,poshopid:that.poshopid}, function (res) {
				that.loading = false;
				if (res.status == 0) {
					app.alert(res.msg,function(){
						app.goback();
					});
					return;
				}
				that.textset = app.globalData.textset;
				var product = res.product;
				var pagecontent = JSON.parse(product.detail);
				if(res.shopdetail_menudata){
					that.shopdetail_menudataList = res.shopdetail_menudata.list;
					that.bottomImg = res.shopdetail_menudata.bottomImg;
				}else{
					that.shopdetail_menudataList = false;
					that.bottomImg = '';
				}
				that.business = res.business;
				that.product = product;
				that.cartnum = res.cartnum;
				that.commentlist = res.commentlist;
				that.commentcount = res.commentcount;
				that.cuxiaolist = res.cuxiaolist;
				that.couponlist = res.couponlist;
				that.fuwulist = res.fuwulist;
				that.pagecontent = pagecontent;
				if(res.shopset) that.shopset = res.shopset;
				if(res.custom) that.custom = res.custom;
				that.sysset = res.sysset;
				that.title = product.name;
				that.isfavorite = res.isfavorite;
				that.showjiesheng = res.showjiesheng || 0;
				that.is_member_auto_addlogin = res.is_member_auto_addlogin || 0;
				that.tjdatalist = res.tjdatalist || [];
				that.showtoptabbar = res.showtoptabbar || 0;
				that.bboglist = res.bboglist;
				that.sharepic = product.pics[0];
			 
				that.xcx_scheme = res.xcx_scheme
				that.showprice_dollar = res.showprice_dollar
				that.show_money_price = res.show_money_price;
				that.commentposition = res.commentposition;
				that.farm_textset = res.farm_textset;
			
				//图片限制观看
				if(that.opt && that.opt.show_image != 0){
					if(res.show_image){
						that.show_image = res.show_image;
					}
				}
				uni.setNavigationBarTitle({
					title: product.name
				});
				if(that.product.can_ziti){
					that.showNearbyMendian = true;
					if(res.bindmendianids.length>0){
						that.mendian = res.mendian
						that.mendianids = res.bindmendianids
					}
				}
				
				that.kfurl = '/pages/kefu/index?bid='+product.bid;
				if(app.globalData.initdata.kfurl != ''){
					that.kfurl = app.globalData.initdata.kfurl;
				}
				if(that.business && that.business.kfurl){
					that.kfurl = that.business.kfurl;
				}
				if(that.product.product_type==1){
					//初始化时清空档案
					app.setCache('_glass_record_id','');
				}
        if(res.autoplay_video){
          if(app.globalData.platform != 'mp'){
            that.initialTime = 0;
            that.mutedStatus = that.showMuteBtnStatus = true;//静音播放
            that.payvideo();
          }
        }
				if(app.globalData.platform == 'wx' && that.product.rewardedvideoad && wx.createRewardedVideoAd){
					if(that.isloadAd == 0){
						that.isloadAd = 1;
						app.showLoading();
						if(!app.globalData.rewardedVideoAd[that.product.rewardedvideoad]){
							app.globalData.rewardedVideoAd[that.product.rewardedvideoad] = wx.createRewardedVideoAd({ adUnitId: that.product.rewardedvideoad});
						}
						var rewardedVideoAd = app.globalData.rewardedVideoAd[that.product.rewardedvideoad];
						rewardedVideoAd.load().then(() => {app.showLoading(false);rewardedVideoAd.show();}).catch(err => { app.alert('加载失败');});
						rewardedVideoAd.onError((err) => {
							app.showLoading(false);
							//app.alert(err.errMsg);
							console.log('onError event emit', err)
							rewardedVideoAd.offLoad()
							rewardedVideoAd.offClose();
							that.loaded({title:product.sharetitle || product.name,pic:product.sharepic || product.pic,desc:product.sharedesc || product.sellpoint});
						});
						rewardedVideoAd.onClose(res => {
							app.globalData.rewardedVideoAd[that.product.rewardedvideoad] = null;
							if (res && res.isEnded) {
								//app.alert('播放结束 发放奖励');
								that.loaded({title:product.sharetitle || product.name,pic:product.sharepic || product.pic,desc:product.sharedesc || product.sellpoint});
							} else {
								//console.log('播放中途退出，不发奖励');
								//that.loaded({title:product.sharetitle || product.name,pic:product.sharepic || product.pic,desc:product.sharedesc || product.sellpoint});
							}
							rewardedVideoAd.offLoad()
							rewardedVideoAd.offClose();
						});
					}
				}else{
					that.loaded({title:product.sharetitle || product.name,pic:product.sharepic || product.pic,desc:product.sharedesc || product.sellpoint});
				}
				
				//未登录的静默注册
				if(res.need_login==1){
					// #ifdef MP-ALIPAY
					that.alilogin();
					// #endif
					// #ifdef MP-WEIXIN
					that.wxlogin();
					// #endif
					
					that.autoaddlogin();
					
				}
				
				//需要定位
				//#ifndef MP-ALIPAY
				if(res.needlocation){
					app.getLocation(function(res) {
						that.latitude = res.latitude;
						that.longitude = res.longitude;
						that.getdata()
					},function(error){
						console.log(error)
					})
				}
				//#endif
				setTimeout(function(){
					let view0 = uni.createSelectorQuery().in(that).select('#scroll_view_tab0')
					view0.fields({
						size: true,//是否返回节点尺寸（width height）
						rect: false,//是否返回节点的 scrollLeft scrollTop，节点必须是 scroll-view 或者 viewport
						scrollOffset: false,//是否返回节点的 scrollLeft scrollTop，节点必须是 scroll-view 或者 viewport
					}, (res) => {
						if(res)
						that.scrolltab0Height = res.height
					}).exec();
					let view1 = uni.createSelectorQuery().in(that).select('#scroll_view_tab1')
					view1.fields({
						size: true,//是否返回节点尺寸（width height）
						rect: false,//是否返回节点的 scrollLeft scrollTop，节点必须是 scroll-view 或者 viewport
						scrollOffset: false,//是否返回节点的 scrollLeft scrollTop，节点必须是 scroll-view 或者 viewport
					}, (res) => {
						if(res)
						that.scrolltab1Height = res.height
					}).exec();
					let view2 = uni.createSelectorQuery().in(that).select('#scroll_view_tab2')
					view2.fields({
						size: true,//是否返回节点尺寸（width height）
						rect: false,//是否返回节点的 scrollLeft scrollTop，节点必须是 scroll-view 或者 viewport
						scrollOffset: false,//是否返回节点的 scrollLeft scrollTop，节点必须是 scroll-view 或者 viewport
					}, (res) => {
						if(res)
						that.scrolltab2Height = res.height
					}).exec();
				},500)
			});
		},
	  loadImg() {
			this.getCurrentSwiperHeight('.img');
		},
		swiperChange: function (e) {
			var that = this;
			that.current = e.detail.current;
			// 禁止错误滑动事件
			if(!e.detail.source) return that.current = 0;
			//动态设置swiper的高度，使用nextTick延时设置
			this.$nextTick(() => {
			  this.getCurrentSwiperHeight('.img');
			});
		},
		// 动态获取内容高度
	  getCurrentSwiperHeight(element) {
				// #ifndef MP-ALIPAY
				let query = uni.createSelectorQuery().in(this);
				query.selectAll(element).boundingClientRect();
				var imgList = this.product.pics;
	      query.exec((res) => {
	        // 切换到其他页面swiper的change事件仍会触发，这时获取的高度会是0，会导致回到使用swiper组件的页面不显示了
	        if (imgList.length && res[0][this.current].height) {
	          this.swiperHeight = res[0][this.current].height;
	        }
	      });	
				// #endif
				// #ifdef MP-ALIPAY
				var imgList = this.product.pics;
				my.createSelectorQuery().select(element).boundingClientRect().exec((ret) => {
					if (imgList.length && ret[this.current].height) {
					  this.swiperHeight = ret[this.current].height;
					}
					});
				// #endif
		},
		payvideo: function () {
			this.isplay = 1;
			uni.createVideoContext('video').play();
		},
		parsevideo: function () {
			this.isplay = 0;
			uni.createVideoContext('video').stop();
		},
		buydialogChange: function (e) {
			if(this.product && this.product.guige_show_type == 1){
				this.showGuigeType();
				return;
			}
			if(!this.buydialogShow){
				this.btntype = e.currentTarget.dataset.btntype
			}
			this.buydialogShow = !this.buydialogShow;
		},
		//收藏操作
		addfavorite: function (item) {
			var that = this;
			var proid = that.product.id;
			app.post('ApiShop/addfavorite', {proid: proid,type: 'shop'}, function (data) {
				if (data.status == 1) {
					that.isfavorite = !that.isfavorite;
				}
				app.success(data.msg);
			});
		},
		//收藏操作
		addfavorite2: function (item) {
			var that = this;
			if(item.pagePath == 'addfavorite::'){
				var proid = that.product.id;
				app.post('ApiShop/addfavorite', {proid: proid,type: 'shop'}, function (data) {
					if (data.status == 1) {
						that.isfavorite = !that.isfavorite;
					}
					app.success(data.msg);
				});
			}else{
				let url = '';
				if(item.menuType == 2) {
					if(item.pagePath){url = item.pagePath}else{url = '/pages/shop/cart'}
				}
				if(item.menuType == 1) {
					if(item.pagePath){url = item.pagePath}else{url = 'pages/kefu/index'}
				}
				if(item.menuType == 3 || item.menuType == 4){
					url = item.pagePath
				}
				// 判断是否为基础页面
				if(url == '/pages/shop/cart') return app.goto(url);
				if(url == '/pages/my/usercenter') return app.goto(url);
				if(url == '/pages/shop/classify') return app.goto(url);
				if(url == '/pages/shop/prolist') return app.goto(url);
				if(url == '/pages/index/index') return app.goto(url);
				if(url.split('?')[1] && (url.split('?')[1].split('=')[0] == 'bid')){
					app.goto(url)
				}else{
					if(url.indexOf('tel:') === 0){
						app.goto(url);
						return;
					}
					app.goto(url + '?bid=' + that.product.bid)
				}
			}
		},
		shareClick: function () {
			this.sharetypevisible = true;
		},
		handleClickMask: function () {
			this.sharetypevisible = false
		},
		showPoster: function () {
			var that = this;
			that.showposter = true;
			that.sharetypevisible = false;
			app.showLoading('生成海报中');
			app.post('ApiShop/getposter', {proid: that.product.id}, function (data) {
				app.showLoading(false);
				if (data.status == 0) {
					app.alert(data.msg);
				} else {
					that.posterpic = data.poster;
				}
			});
		},
		
		shareScheme: function () {
			var that = this;
			app.showLoading();
			app.post('ApiShop/getwxScheme', {proid: that.product.id}, function (data) {
				app.showLoading(false);
				if (data.status == 0) {
					app.alert(data.msg);
				} else {
						that.showScheme = true;
						that.schemeurl=data.openlink
				}
			});
		},
		
		schemeDialogClose: function () {
			this.showScheme = false;
		},
		
		posterDialogClose: function () {
			this.showposter = false;
		},
		showfuwudetail: function () {
			this.showfuwudialog = true;
		},
		hidefuwudetail: function () {
			this.showfuwudialog = false
		},
		showcuxiaodetail: function () {
			this.showcuxiaodialog = true;
		},
		hidecuxiaodetail: function () {
			this.showcuxiaodialog = false
		},
		getcoupon:function(){
			this.showcuxiaodialog = false;
			this.getdata();
		},
		onPageScroll: function (e) {
			uni.$emit('onPageScroll',e);
			//var that = this;
			//var scrollY = e.scrollTop;     
			//if (scrollY > 200) {
			//	that.scrolltopshow = true;
			//}
			//if(scrollY < 150) {
			//	that.scrolltopshow = false
			//}
			//if (scrollY > 100) {
			//	that.toptabbar_show = true;
			//}
			//if(scrollY < 50) {
			//	that.toptabbar_show = false
			//}
		},
		changetoptab:function(e){
			var index = e.currentTarget.dataset.index;
			this.scrollToViewId = 'scroll_view_tab'+index;
			this.toptabbar_index = index;
			if(index == 0) this.scrollTop = 0;
			console.log(index);
		},
		scroll:function(e){
			var scrollTop = e.detail.scrollTop;
			//console.log(e)
			var that = this;
			if (scrollTop > 200) {
				that.scrolltopshow = true;
			}
			if(scrollTop < 150) {
				that.scrolltopshow = false
			}
			if (scrollTop > 100) {
				that.toptabbar_show = true;
			}
			if(scrollTop < 50) {
				that.toptabbar_show = false
			}
			var height0 = that.scrolltab0Height;
			var height1 = that.scrolltab0Height + that.scrolltab1Height;
			var height2 = that.scrolltab0Height + that.scrolltab1Height + that.scrolltab2Height;
			//var height3 = that.scrolltab0Height + that.scrolltab1Height + that.scrolltab2Height + that.scrolltab3Height;
			if(scrollTop >=0 && scrollTop < height0){
				//this.scrollToViewId = 'scroll_view_tab0';
				this.toptabbar_index = 0;
			}else if(scrollTop >= height0 && scrollTop < height1){
				//this.scrollToViewId = 'scroll_view_tab1';
				this.toptabbar_index = 1;
			}else if(scrollTop >= height1 && scrollTop < height2){
				//this.scrollToViewId = 'scroll_view_tab2';
				this.toptabbar_index = 2;
			}else if(scrollTop >= height2){
				//this.scrollToViewId = 'scroll_view_tab3';
				this.toptabbar_index = 3;
			}
		},
		sharemp:function(){
			app.error('点击右上角发送给好友或分享到朋友圈');
			this.sharetypevisible = false
		},
		shareapp:function(){
			// #ifdef APP
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
						sharedata.title = that.product.sharetitle || that.product.name;
						sharedata.summary = that.product.sharedesc || that.product.sellpoint;
						sharedata.href = app.globalData.pre_url +'/h5/'+app.globalData.aid+'.html#/pages/shop/product?scene=id_'+that.product.id+'-pid_' + app.globalData.mid;
						sharedata.imageUrl = that.product.pic;
						var sharelist = app.globalData.initdata.sharelist;
						if(sharelist){
							for(var i=0;i<sharelist.length;i++){
								if(sharelist[i]['indexurl'] == '/pages/shop/product'){
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
			// #endif
		},
		callMendian:function(e){
			var tel = e.currentTarget.dataset.tel;
			uni.makePhoneCall({
				phoneNumber: tel,
				fail: function () {
				}
			});
		},
		toMendian:function(e){
			var latitude = parseFloat(e.currentTarget.dataset.latitude);
			var longitude = parseFloat(e.currentTarget.dataset.longitude);
			var address = e.currentTarget.dataset.address;
			if(!latitude || !longitude){
				return;
			}
			uni.openLocation({
			 latitude:latitude,
			 longitude:longitude,
			 name:address,
			 scale: 13
			})
		},
		changeGuige:function(e){
			 this.gwcnum = e.num
			 this.ggid = e.ggid
			 this.stock = e.stock
			 this.product.stock = this.stock
			 this.sell_price= e.sell_price
		},
		//加入购物车操作
		addcart2: function () {
			var that = this;
			var num = that.gwcnum;
			var proid = that.product.id;
			var ggid = that.ggid
			var stock = that.stock;
			if(that.product && that.product.guige_show_type == 1){
				that.guigeTypebuy('cart');
				return;
			}
			if (num < 1) num = 1;
			if (stock < num) {
				app.error('库存不足');
				return;
			}				
			app.post('ApiShop/addcart', {proid: proid,ggid: ggid,num: num}, function (res) {
				that.cartnum = that.cartnum + num;
				if (res.status == 1) {
					app.success('添加成功');
				} else {
					app.error(res.msg);
				}
			});
		},
		tobuy: function (e) {
			var that = this;
			var proid = that.product.id;
			var ggid = that.ggid;
			var stock = that.stock;
			var num = that.gwcnum;
			if(that.product && that.product.guige_show_type == 1){
				that.guigeTypebuy('buy');
				return; 
			}
			if (num < 1) num = 1;
			if ((stock < num && !that.product.shop_yuding) || (that.product.shop_yuding && stock < num && that.product.yuding_stock < num) ) {
				app.error('库存不足');
				return;
			}
			var prodata = proid + ',' + ggid + ',' + num;
      var tourl = '/pagesB/shop/buy?prodata=' + prodata;
      if(that.poshopid && that.poshopid>0) tourl += '&poshopid='+that.poshopid;
			app.goto(tourl);
		},
		showsubqrcode:function(){
			this.$refs.qrcodeDialog.open();
		},
		closesubqrcode:function(){
			this.$refs.qrcodeDialog.close();
		},
		addcart:function(e){
			console.log(e)
			this.cartnum = this.cartnum + e.num;
		},
		showgg1Dialog:function(){
			this.$refs.gg1Dialog.open();
		},
		closegg1Dialog:function(){
			this.$refs.gg1Dialog.close();
		},
		showgg2Dialog:function(){
			this.$refs.gg2Dialog.open();
		},
		closegg2Dialog:function(){
			this.$refs.gg2Dialog.close();
		},
		autoaddlogin: function (){
				var that = this;
				app.post('ApiIndex/autoaddlogin',{ },function(res2){
					if (res2.status == 1) {
						//app.success(res2.msg);
						//that.getdata();
					} else {
						app.error(res2.msg);
					}
					return;
				})
			},
		wxlogin: function (){
				var that = this;
				wx.login({success (res1){
					console.log(res1);
					var code = res1.code;
					//用户允许授权
					app.post('ApiIndex/wxbaselogin',{ code:code,pid: app.globalData.pid,},function(res2){
						if (res2.status == 1) {
							//app.success(res2.msg);
							//that.getdata();
						} else {
							app.error(res2.msg);
						}
						return;
					})
			  }});
			},
			alilogin:function(){
		// #ifdef MP-ALIPAY
				var that = this;
				var ali_appid = that.ali_appid;
		
				if(ali_appid){
					my.getAuthCode ({
						appId :  ali_appid ,
						scopes : ['auth_base'],
					},function(res){
						//var res = JSON.stringify(res);
						if(!res.error && res.authCode){
						  app.post('ApiIndex/alipaylogin', {
							code: res.authCode,
							pid: app.globalData.pid,
							silent:1
							//platform:"h5"
						  }, function(res2) {
		
							if (res2.status!= 0) {
							  app.success(res2.msg);
							  //that.getdata();
							}  else {
							  app.error(res2.msg);
							}
						  });
						}else{
						  app.showLoading(false);
		
						  if(res.errorMessage){
							app.alert(res.errorMessage);
						  }else if(res.errorDesc){
							app.alert(res.errorDesc);
						  }else{
							app.alert('授权出错');
						  }
						  return
						}
					});
				}else{
				  app.alert('系统未配置支付宝参数');
				  return
				}
		// #endif
			},
			copy1: function(e) {
				var that=this
				var text = that.product.name+'\n\n'+that.product.sellpoint;
				uni.setClipboardData({
					data: text,
					success: function () {
						getApp().error('复制成功')
					}
				});
			},
			//采购单
			purchaseorder: function (e) {
				this.purchaseOrderShow = !this.purchaseOrderShow;
			},
			gotolevelup:function(){
				if (!app.globalData.mid) {
					var frompage = encodeURIComponent(app._fullurl());
					return app.goto('/pages/index/login?frompage=' + frompage, 'reLaunch');
				}
				return app.goto('/pagesExt/my/levelup');
			},
			toLocation(){	
				//#ifdef MP-ALIPAY
				var that = this;
				app.getLocation(function(res) {
					that.latitude = res.latitude;
					that.longitude = res.longitude;
					that.getdata()
				},function(error){
					console.log(error)
				})
				//#endif
			},
			showGuigeType:function(){
				var that = this;
				that.loading = true;
				app.post('ApiShop/getproductdetail',{id:that.opt.id,reset:1},function(res){
					that.loading = false;
					if(res.status != 1){
						app.alert(res.msg)
						return;
					}
					
					that.nowguige = res.guigelist[that.ksk];
					that.nowguigeProduct = that.nowguige[res.ks];
					
					that.guigelist = res.guigelist;
					that.guigedata = res.guigedata;
					if(that.nowguigeProduct.limit_start > 0)
						that.gwcnum = that.nowguigeProduct.limit_start;
					else
						that.gwcnum = that.nowguigeProduct.limit_start;
					that.guigedata[1].items.map(item => item.num = 0); //向2规格加入初始值
					that.show_guige_type = 1;
					that.$refs.glassesPupup.open();
				});
			},
			changeradio:function(e){
				var that = this;
				var index = e.currentTarget.dataset.index;
				var ksk = that.ksk;
				var gg = that.guigelist[ksk][index];
				if(gg.checked == true && gg.num > 0){
					//取消选中时
					that.guigedata[1].items[ksk].num -= gg.num;
					that.guigelist[ksk][index].num = 0;
				}else if(gg.checked == false && gg.num > 0){
					that.guigedata[1].items[ksk].num += gg.num;
				}
				that.guigelist[ksk][index].checked = !gg.checked;
			},
			changeradioAll:function(e){
				var that = this;
				var ksk = that.ksk;
				Object.values(that.guigelist[ksk]).forEach(item => {
					item.checked = !item.checked;
				});
			},
			gwcplus: function (e,index) {
				let gwcnum = e.num + 1;
				let ksk = this.ksk;
				if (gwcnum > this.guigelist[ksk][index].stock) {
					app.error('库存不足');
					return 1;
				}
				if (this.product.perlimitdan > 0 && gwcnum > this.product.perlimitdan) {
					app.error('每单限购'+this.product.perlimitdan+'件');
					return 1;
				}
				
				this.guigelist[ksk][index].checked = true;
				this.guigelist[ksk][index].num = gwcnum;
				this.guigedata[1].items[ksk].num += 1;
			},
			gwcminus: function (e,index) {
				if(!e.num) return;
				let gwcnum = e.num - 1;
				let ksk = this.ksk;
				if(gwcnum <= 0){
					gwcnum = 0;
					this.guigelist[ksk][index].checked = false;
				}
				this.guigelist[ksk][index].num = gwcnum;
				this.guigedata[1].items[ksk].num -= 1;
			},
			gwcinput: function (e,item,index) {
				let ksk = this.ksk;
				let gwcnum = parseInt(e.detail.value);
				if(gwcnum > item.stock) {
					gwcnum = item.stock;
				}
				if(gwcnum <= 0){
					gwcnum = 0;
					this.guigelist[ksk][index].checked = false;
				}else{
					this.guigelist[ksk][index].checked = true;
				}
				this.guigelist[ksk][index].num = gwcnum;
				this.guigedata[1].items[ksk].num += gwcnum;
			},
			guigeTypebuy:function(type="buy"){
				var that = this;
				var prodatagg = [];
				var proid = that.product.id;
				that.guigelist.forEach(function(subArray) {
					Object.values(subArray).forEach(function(item) {
							if (item.num > 0 && item.checked) {
									prodatagg.push(item);
							}
					});
				});
				if(!prodatagg.length) return app.error("数量不能为0");
				let thisprodata = [];
				let totalNum = 0;
				for (var i = 0; i < prodatagg.length; i++) {
					totalNum += prodatagg[i].num;
					thisprodata.push(proid + ',' + prodatagg[i].id + ',' + prodatagg[i].num);
				}
				//起售判断
				if(that.product.limit_start > 0 && totalNum < that.product.limit_start){
					return app.error('该商品' + that.product.limit_start + '件起售');
				}
				thisprodata = thisprodata.join('-');
				//购买
				if(type == 'buy'){
					app.goto('/pages/shop/buy?prodata=' + thisprodata);
					return;
				}
				//添加到购物车
				app.post('ApiShop/addcartmore', {prodata: thisprodata,glass_record_id:''}, function (res) {
					if (res.status == 1) {
						app.success('添加成功');
					} else {
						app.error(res.msg);
					}
					that.glassesPopupClose()
				});
			}
	},
	
};
</script>
<style scoped>
page {position: relative;width: 100%;}
.container{height:100%}
.follow_topbar {height:88rpx; width:100%;max-width:640px; background:rgba(0,0,0,0.8); position:fixed; top:0; z-index:13;}
.follow_topbar .headimg {height:64rpx; width:64rpx; margin:6px; float:left;}
.follow_topbar .headimg image {height:64rpx; width:64rpx;}
.follow_topbar .info {height:56rpx; padding:16rpx 0;}
.follow_topbar .info .i {height:28rpx; line-height:28rpx; color:#ccc; font-size:24rpx;} 
.follow_topbar .info {height:80rpx; float:left;}
.follow_topbar .sub {height:48rpx; width:auto; background:#FC4343; padding:0 20rpx; margin:20rpx 16rpx 20rpx 0; float:right; font-size:24rpx; color:#fff; line-height:52rpx; border-radius:6rpx;}
.qrcodebox{background:#fff;padding:50rpx;position:relative;border-radius:20rpx}
.qrcodebox .img{width:400rpx;height:400rpx}
.qrcodebox .txt{color:#666;margin-top:20rpx;font-size:26rpx;text-align:center}
.qrcodebox .close{width:50rpx;height:50rpx;position:absolute;bottom:-100rpx;left:50%;margin-left:-25rpx;border:1px solid rgba(255,255,255,0.5);border-radius:50%;padding:8rpx}

.swiper-container{position:relative;overflow: hidden;}
.swiper {width: 100%;height: 750rpx;overflow: hidden;}
.swiper-item-view{width: 100%;height: 750rpx;}
.swiper .img {width: 100%;height: 750rpx;overflow: hidden;}

.imageCount {width:100rpx;height:50rpx;background-color: rgba(0, 0, 0, 0.3);border-radius:40rpx;line-height:50rpx;color:#fff;text-align:center;font-size:26rpx;position:absolute;right:13px;bottom:20rpx;}

.provideo{background:rgba(255,255,255,0.7);width:190rpx;height:54rpx;padding:0 20rpx 0 4rpx;border-radius:27rpx;position:absolute;bottom:30rpx;left:50%;margin-left:-80rpx;display:flex;align-items:center;justify-content:space-between}
.provideo image,.playicon{width:50rpx;height:50rpx;}
.provideo .txt{flex:1;text-align:center;padding-left:10rpx;font-size:24rpx;color:#333}
.wxfeedvideo{background:rgba(255,255,255,0.7);width:190rpx;height:54rpx;padding:0 20rpx 0 4rpx;border-radius:27rpx;position:absolute;bottom:30rpx;left:50%;margin-left:-80rpx;}
.wxfeedvideo .videop{display:flex;align-items:center;justify-content:space-between}
.wxfeedvideo .feedvideo{position: relative;height: 54rpx;width: 100%;top: -54rpx;z-index: 9999;width: 200rpx;opacity: 0;}
.videobox{width:100%;height:750rpx;text-align:center;background:#000}
.videobox .video{width:100%;height:650rpx;}
.videobox .parsevideo{margin:0 auto;margin-top:20rpx;height:40rpx;line-height:40rpx;color:#333;background:#ccc;width:140rpx;border-radius:25rpx;font-size:24rpx}

.header {width: 100%;padding: 20rpx 3%;background: #fff;}
.header .price_share{width:100%;min-height:100rpx;display:flex;align-items:center;justify-content:space-between}
.header .price_share .price{display:flex;align-items:flex-end}
.header .price_share .price .f1{font-size:50rpx;color:#51B539;font-weight:bold}
.header .price_share .price .f2{font-size:26rpx;color:#C2C2C2;text-decoration:line-through;margin-left:30rpx;padding-bottom:5px}
.header .price_share .share{display:flex;flex-direction:column;align-items:center;justify-content:center;min-width: 60rpx;}
.header .price_share .share .img{width:32rpx;height:32rpx;margin-bottom:2px}
.header .price_share .share .txt{color:#333333;font-size:20rpx}
.header .title {color:#000000;font-size:32rpx;line-height:42rpx;font-weight:bold;}
.header .price_share .title { display:flex;align-items:flex-end;}
.header .sellpoint{font-size:28rpx;color: #666;padding-top:20rpx;}
.header .sales_stock{display:flex;justify-content:space-between;height:60rpx;line-height:60rpx;margin-top:30rpx;font-size:24rpx;color:#777777}
.header .commission{display:inline-block;margin-top:20rpx;margin-bottom:10rpx;border-radius:10rpx;font-size:20rpx;height:44rpx;line-height:44rpx;padding:0 20rpx}
.header .upsavemoney{display:flex;align-items:center;margin-top:20rpx;margin-bottom:10rpx;border-radius:10rpx;font-size:28rpx;height:70rpx;padding:0 20rpx}
/* 定制的价格字体和颜色 */
.header .custom_price .price{font-size: 32rpx;}
.header .custom_price .market_price{font-size:26rpx;color:#C2C2C2;text-decoration:line-through;margin-left:20rpx;}
.header .custom_price .custom_price_tag{font-size: 20rpx;}

/* 分期 */
.choose-fenqi{width: 100%;display: flex;align-items:center;background: #fff;margin-top: 20rpx; height: auto;padding: 25rpx 3%; color: #333;}
.choose-fenqi .f0{color:#555;font-weight:bold;font-size:24rpx;padding-right:30rpx;white-space: nowrap;}
.choose-fenqi .fenqi-info-view{width: 88%;}
.choose-fenqi .fenqi-info-view .commission-fenqi{display: inline-block;margin-bottom:30rpx;border-radius:10rpx;font-size:20rpx;height:44rpx;line-height:44rpx;padding:0 20rpx;}
.choose-fenqi .fenqi-info-view .fenqi-list-view{width: 100%;}
.fenqi-info-view .fenqi-list-view .fenqi-options{width: 200rpx;display: inline-block;margin-right: 20rpx;background:#f6f6f6;border-radius:8rpx;padding: 8rpx 0rpx;}
.fenqi-options .fenqi-num{font-size: 24rpx;color: #333;width: 100%;text-align: center;padding: 3rpx 0rpx;display: flex;align-items: center;justify-content: center;}
.fenqi-options .fenqi-num .fenqi-bili{font-size: 20rpx;color: #5b5b5b;margin-left: 10rpx;}
.fenqi-options .fenqi-give{font-size: 22rpx;color: #5b5b5b;width: 100%;text-align: center;padding: 3rpx 0rpx;}



.choose{ display:flex;align-items:center;width: 100%; background: #fff;  margin-top: 20rpx; height: 88rpx; line-height: 88rpx;padding: 0 3%; color: #333; }
.choose .f0{color:#555;font-weight:bold;height:32rpx;font-size:24rpx;padding-right:30rpx;display:flex;justify-content:center;align-items:center}
.choose .f2{ width: 32rpx; height: 32rpx;}

.cuxiaodiv{background:#fff;margin-top:20rpx;padding:0 3%;}
.cuxiaodiv .cuxiaopoint .shouquan{height:55rpx;line-height:55rpx;color:#FFFFFF;border-radius:32rpx;margin-left:20rpx;flex-shrink:0;padding:0 20rpx;font-size:24rpx;font-weight:bold}
.fuwupoint{width:100%;font-size:24rpx;color:#333;height:88rpx;line-height:88rpx;padding:12rpx 0;display:flex;align-items:center}
.fuwupoint .f0{color:#555;font-weight:bold;height:32rpx;font-size:24rpx;padding-right:30rpx;display:flex;justify-content:center;align-items:center}
.fuwupoint .f1{margin-right:20rpx;flex:1;display:flex;flex-wrap:nowrap;overflow:hidden}
.fuwupoint .f1 .t{ padding:4rpx 20rpx 4rpx 0;color:#777;flex-shrink:0}
.fuwupoint .f1 .t:before{content: "";display: inline-block;vertical-align: middle;	margin-top: -4rpx;margin-right: 10rpx;	width: 24rpx;	height: 24rpx;	background: url('data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABgAAAAYBAMAAAASWSDLAAAABGdBTUEAALGPC/xhBQAAAAFzUkdCAK7OHOkAAAAwUExURUdwTOU5O+Q5POU5POQ4O+U4PN80P+M4O+Q4O+Q4POQ5POQ4OuQ4O+Q4O+I4PuQ5PJxkAycAAAAPdFJOUwAf+VSoeAvzws7ka7miLboUzckAAADJSURBVBjTY2BgYGCMWVR5VIABDBid/gPBFwjP/JOzQKKtfjGIzf3fEUSJ/N8AJO21Iao3fQbqqA+AcLi/CzCwfGGAAn8HBnlFMIttBoP4R4b4C2BOzk8G3q8M5w3AnPsLGZj/MKwHW8b6/QED4y8G/QQQx14ZSHwCcWYkMOtvAHOAyvqnPf8KcuMvkAGZP9eDjAQaEO/AwDb/D0gj0GiQpRnTQIYIfUR1DopDGexVIZygz8ieC4B6WyzRBOJtBkZ/pAABBZUWOKgAispF5e7ibycAAAAASUVORK5CYII=') no-repeat;background-size: 24rpx auto;}
.fuwupoint .f2{flex-shrink:0;display:flex;align-items:center;width:32rpx;height: 32rpx;}
.fuwupoint .f2 .img{width:32rpx;height:32rpx;}
.fuwudialog-content{font-size:24rpx}
.fuwudialog-content .f1{color:#333;height:80rpx;line-height:80rpx;font-weight:bold}
.fuwudialog-content .f1:before{content: "";display: inline-block;vertical-align: middle;	margin-top: -4rpx;margin-right: 10rpx;	width: 24rpx;	height: 24rpx;	background: url('data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABgAAAAYBAMAAAASWSDLAAAABGdBTUEAALGPC/xhBQAAAAFzUkdCAK7OHOkAAAAwUExURUdwTOU5O+Q5POU5POQ4O+U4PN80P+M4O+Q4O+Q4POQ5POQ4OuQ4O+Q4O+I4PuQ5PJxkAycAAAAPdFJOUwAf+VSoeAvzws7ka7miLboUzckAAADJSURBVBjTY2BgYGCMWVR5VIABDBid/gPBFwjP/JOzQKKtfjGIzf3fEUSJ/N8AJO21Iao3fQbqqA+AcLi/CzCwfGGAAn8HBnlFMIttBoP4R4b4C2BOzk8G3q8M5w3AnPsLGZj/MKwHW8b6/QED4y8G/QQQx14ZSHwCcWYkMOtvAHOAyvqnPf8KcuMvkAGZP9eDjAQaEO/AwDb/D0gj0GiQpRnTQIYIfUR1DopDGexVIZygz8ieC4B6WyzRBOJtBkZ/pAABBZUWOKgAispF5e7ibycAAAAASUVORK5CYII=') no-repeat;background-size: 24rpx auto;}
.fuwudialog-content .f2{color:#777}

.cuxiaopoint{width:100%;font-size:24rpx;color:#333;height:88rpx;line-height:88rpx;padding:12rpx 0;display:flex;align-items:center}
.cuxiaopoint .f0{color:#555;font-weight:bold;height:32rpx;font-size:24rpx;padding-right:20rpx;display:flex;justify-content:center;align-items:center}
.cuxiaopoint .f1{margin-right:20rpx;flex:1;display:flex;flex-wrap:nowrap;overflow:hidden}
.cuxiaopoint .f1 .t{margin-left:10rpx;border-radius:3px;font-size:24rpx;height:40rpx;line-height:40rpx;padding-right:10rpx;flex-shrink:0;overflow:hidden}
.cuxiaopoint .f1 .t0{display:inline-block;padding:0 5px;}
.cuxiaopoint .f1 .t1{padding:0 4px}
.cuxiaopoint .f2{flex-shrink:0;display:flex;align-items:center;width:32rpx;height: 32rpx;}
.cuxiaopoint .f2 .img{width:32rpx;height:32rpx;}
.cuxiaodiv .cuxiaoitem{border-bottom:1px solid #E6E6E6;}
.cuxiaodiv .cuxiaoitem:last-child{border-bottom:0}

.popup__container{position: fixed;bottom: 0;left: 0;right: 0;width:100%;height:auto;z-index:10;background:#fff}
.popup__overlay{position: fixed;bottom: 0;left: 0;right: 0;width:100%;height: 100%;z-index: 11;opacity:0.3;background:#000}
.popup__modal{width: 100%;position: absolute;bottom: 0;color: #3d4145;overflow-x: hidden;overflow-y: hidden;opacity:1;padding-bottom:20rpx;background: #fff;border-radius:20rpx 20rpx 0 0;z-index:12;min-height:600rpx;max-height:1000rpx;}
.popup__title{text-align: center;padding:30rpx;position: relative;position:relative}
.popup__title-text{font-size:32rpx}
.popup__close{position:absolute;top:34rpx;right:34rpx}
.popup__content{width:100%;max-height:880rpx;overflow-y:scroll;padding:20rpx 0;}
.service-item{display: flex;padding:0 40rpx 20rpx 40rpx;}
.service-item .prefix{padding-top: 2px;}
.service-item .suffix{padding-left: 10rpx;}
.service-item .suffix .type-name{font-size:28rpx; color: #49aa34;margin-bottom: 10rpx;}


.shop{display:flex;align-items:center;width: 100%; background: #fff;  margin-top: 20rpx; padding: 20rpx 3%;position: relative; min-height: 100rpx;}
.shop .p1{width:90rpx;height:90rpx;border-radius:6rpx;flex-shrink:0}
.shop .p2{padding-left:10rpx}
.shop .p2 .t1{width: 100%;height:40rpx;line-height:40rpx;overflow: hidden;color: #111;font-weight:bold;font-size:30rpx;}
.shop .p2 .t2{width: 100%;height:30rpx;line-height:30rpx;overflow: hidden;color: #999;font-size:24rpx;margin-top:8rpx}
.shop .p4{height:64rpx;line-height:64rpx;color:#FFFFFF;border-radius:32rpx;margin-left:20rpx;flex-shrink:0;padding:0 30rpx;font-size:24rpx;font-weight:bold}

.detail{min-height:200rpx;}

.detail_title{width:100%;display:flex;align-items:center;justify-content:center;margin-top:60rpx;margin-bottom:30rpx}
.detail_title .t0{font-size:28rpx;font-weight:bold;color:#222222;margin:0 20rpx}
.detail_title .t1{width:12rpx;height:12rpx;background:rgba(253, 74, 70, 0.2);transform:rotate(45deg);margin:0 4rpx;margin-top:6rpx}
.detail_title .t2{width:18rpx;height:18rpx;background:rgba(253, 74, 70, 0.4);transform:rotate(45deg);margin:0 4rpx}

.commentbox{width:100%;background:#fff;padding:0 3%;margin-top:20rpx}
.commentbox .title{height:90rpx;line-height:90rpx;border-bottom:1px solid #DDDDDD;display:flex}
.commentbox .title .f1{flex:1;color:#111111;font-weight:bold;font-size:30rpx;display: flex;align-items: center;}
.commentbox .title .f2{color:#333;font-weight:bold;font-size:28rpx;display:flex;align-items:center}
.commentbox .nocomment{height:100rpx;line-height:100rpx}

.comment{display:flex;flex-direction:column;min-height:200rpx;}
.comment .item{background-color:#fff;padding:10rpx 20rpx;display:flex;flex-direction:column;}
.comment .item .f1{display:flex;width:100%;align-items:center;padding:10rpx 0;}
.comment .item .f1 .t1{width:70rpx;height:70rpx;border-radius:50%;}
.comment .item .f1 .t2{padding-left:10rpx;color:#333;font-weight:bold;font-size:30rpx;}
.comment .item .f1 .t3{text-align:right;}
.comment .item .f1 .t3 .img{width:24rpx;height:24rpx;margin-left:10rpx}
.comment .item .score{ font-size: 24rpx;color:#f99716;}
.comment .item .score image{ width: 140rpx; height: 50rpx; vertical-align: middle;  margin-bottom:6rpx; margin-right: 6rpx;}
.comment .item .f2{display:flex;flex-direction:column;width:100%;padding:10rpx 0;}
.comment .item .f2 .t1{color:#333;font-size:28rpx;}
.comment .item .f2 .t2{display:flex;width:100%}
.comment .item .f2 .t2 image{width:100rpx;height:100rpx;margin:10rpx;}
.comment .item .f2 .t3{color:#aaa;font-size:24rpx;}
.comment .item .f3{margin:20rpx auto;padding:0 30rpx;height:60rpx;line-height:60rpx;border:1px solid #E6E6E6;border-radius:30rpx;color:#111111;font-weight:bold;font-size:26rpx}

.addcommentimg{height:36rpx;width: 36rpx;margin-left: 10rpx;}

.bottombar{ width: 94%; position: fixed;bottom: 0px; left: 0px; background: #fff;display:flex;height:100rpx;padding:0 4% 0 2%;align-items:center;box-sizing:content-box}
.bottombar .f1{display:flex;align-items:center;margin-right:15rpx;}
.bottombar .f1 .item{display:flex;flex-direction:column;align-items:center;width:82rpx;position:relative;}
.bottombar .f1 .item .img{ width:44rpx;height:44rpx}
.bottombar .f1 .item .t1{font-size:18rpx;color:#222222;height:30rpx;line-height:30rpx;margin-top:6rpx}
.bottombar .op{border-radius:36rpx;overflow:hidden;display:flex;flex: 1;}
.bottombar .tocart{flex:1;height:72rpx; line-height: 72rpx;color: #fff; border-radius: 0px; border: none;font-size:28rpx;font-weight:bold}
.bottombar .tobuy{flex:1;height: 72rpx; line-height: 72rpx;color: #fff; border-radius: 0px; border: none;font-size:28rpx;font-weight:bold}
.bottombar .cartnum{position:absolute;right:4rpx;top:-4rpx;color:#fff;border-radius:50%;width:32rpx;height:32rpx;line-height:32rpx;text-align:center;font-size:22rpx;}

.bottombar .op2{width:60%;overflow:hidden;display:flex;}
.bottombar .tocart2{ flex:1;height: 80rpx;border-radius:10rpx;color: #fff; background: #fa938a; font-size: 28rpx;display:flex;flex-direction:column;align-items:center;justify-content:center;margin-right:10rpx;}
.bottombar .tobuy2{ flex:1; height: 80rpx;border-radius:10rpx;color: #fff; background: #df2e24; font-size:28rpx;display:flex;flex-direction:column;align-items:center;justify-content:center}

.paramitem{display:flex;border-bottom:1px solid #f5f5f5;padding:20rpx}
.paramitem .f1{width:30%;color:#666}
.paramitem .f2{color:#333}
.paramitem:last-child{border-bottom:0}

.xihuan{height: auto;overflow: hidden;display:flex;align-items:center;width:100%;padding:20rpx 160rpx;margin-top:20rpx}
.xihuan-line{height: auto; padding: 0; overflow: hidden;flex:1;height:0;border-top:1px solid #eee}
.xihuan-text{padding:0 32rpx;text-align:center;display:flex;align-items:center;justify-content:center}
.xihuan-text .txt{color:#111;font-size:30rpx}
.xihuan-text .img{text-align:center;width:36rpx;height:36rpx;margin-right:12rpx}
.prolist{width: 100%;height:auto;padding: 8rpx 20rpx;}

.toptabbar_tab{display:flex;width:100%;height:90rpx;background: #fff;top:var(--window-top);z-index:11;position:fixed;border-bottom:1px solid #f3f3f3}
.toptabbar_tab .item{flex:1;font-size:28rpx; text-align:center; color:#666; height: 90rpx; line-height: 90rpx;overflow: hidden;position:relative}
.toptabbar_tab .item .after{display:none;position:absolute;left:50%;margin-left:-16rpx;bottom:10rpx;height:3px;border-radius:1.5px;width:32rpx}
.toptabbar_tab .on{color: #323233;}
.toptabbar_tab .on .after{display:block}

.scrolltop{position:fixed;bottom:160rpx;right:20rpx;width:60rpx;height:60rpx;background:rgba(0,0,0,0.4);color:#fff;border-radius:50%;padding:12rpx 10rpx 8rpx 10rpx;z-index:9;}
.scrolltop .image{width:100%;height:100%;}

.ggdiaplog_close{width:50rpx;height:50rpx;position:absolute;bottom:-100rpx;left:50%;margin-left:-25rpx;border:1px solid rgba(255,255,255,0.5);border-radius:50%;padding:8rpx}

.schemeDialog {background:rgba(0,0,0,0.4);z-index:12;}
.schemeDialog .main{ position: absolute;top:30%}
.schemecon{padding: 40rpx 30rpx; }
.copybtn{ text-align: center; margin-top: 30rpx; padding:15rpx 20rpx; border-radius: 50rpx; color:#fff}

.member{position: relative;border-radius: 8rpx;border: 1rpx solid #fd4a46;overflow: hidden;box-sizing: content-box;}
.member_lable{height: 100%;font-size: 22rpx;color: #fff;background: #fd4a46;padding: 0 15rpx;}
.member_value{padding: 0 15rpx;font-size: 30rpx;font-weight: bold;color: #fd4a46;}
.flex-s{display: flex;justify-content: flex-start;align-items: center;}
.moneyprice{font-size: 26rpx;font-weight: 400;padding: 4rpx 10rpx;min-width: 90rpx;text-align: center;margin-left: 10rpx;border-radius: 20rpx;}

/*附近门店S*/
.nearby-mendian-box{margin:20rpx 0; background: #FFFFFF;width: 100%;padding: 20rpx;}
.nearby-mendian-title{display: flex;justify-content: space-between;align-items: center;}
.nearby-mendian-title .t1{font-size: 30rpx;font-weight: bold;}
.nearby-mendian-title .t2{color: #999;font-size: 26rpx;}
.nearby-mendian-title .t2 image{height: 26rpx;width: 26rpx;vertical-align: middle;}
.nearby-mendian-info{display: flex;align-items: center;width: 100%;margin-top: 20rpx;}
.mendian-info .b1{background-color: #fbfbfb;}
.nearby-mendian-info .b1 image{height: 90rpx;width:90rpx;border-radius: 6rpx;border: 1px solid #e8e8e8;}
.nearby-mendian-info .b2{flex:1;line-height: 38rpx;margin-left: 10rpx;max-width: 70%;overflow: hidden;}
.nearby-mendian-info .b2 .t1{padding-bottom: 10rpx;}
.nearby-mendian-info .b2 .t2{font-size: 24rpx;color: #999;}
.nearby-mendian-info .b3{display: flex;justify-content: flex-end;flex-shrink: 0;padding-left: 10rpx;width: 130rpx;}
.nearby-mendian-info .b3 image{width: 40rpx;height: 40rpx;margin-right: 16rpx;}
.nearby-mendian-info .nearby-tag{padding:0 10rpx;margin-right: 10rpx;display: inline-block;font-size: 22rpx;border-radius: 8rpx;flex-shrink: 0;}
.nearby-mendian-info .mendian-distance{flex-shrink: 0;margin-right: 10rpx;}
.nearby-mendian-info .mendian-address{white-space: nowrap;text-overflow: ellipsis;max-width: 80%;}
.pd10{padding-left: 10rpx;}
/*附近门店E*/
.bottomimg{width: 100%;}
.shop_label{display: inline-block;padding: 0rpx 8rpx;border-radius: 8rpx;margin-right: 10rpx;height: 38rpx;line-height: 38rpx;font-size: 24rpx;font-weight: 400;}
/* 采购单悬浮框 */
.suspension{position: fixed;right: 10rpx; bottom: 280rpx;width: 100rpx; height: 100rpx; background-color: rgba(0, 0, 0, 0.3); color: white;border-radius: 100%;display: flex;justify-content: center;align-items: center; z-index: 10;}
.suspension .suspension-purchase{text-align: center;}
.suspension .suspension-text{display: block;font-size: 24rpx;padding: 2rpx;}
/* 模糊图片的样式 */
.filter-background {position: absolute;left: 0;top: 0;width: 100%;height: 100%;backdrop-filter: blur(8px);z-index: 1;}
.lock-image{position: absolute; top: 50%;left: 50%;transform: translate(-50%, -50%);	z-index: 10;}
.lock-image image{width: 200rpx;height: 200rpx;}
/* 营销标签 */
.yingxiao_tag{text-align: center;color: #fff;border-radius: 8rpx;background:#FA3218;position: relative;padding: 5rpx 30rpx; margin-left: 40rpx;margin-bottom: 10rpx;}
.yingxiao_tag .jiantou{width: 0;height: 0;border-top: 10rpx solid transparent;border-bottom: 10rpx solid transparent;border-right: 10rpx solid #FA3218;position: absolute;left: -10rpx;top: 15rpx;}
.choujiangtext{line-height: 40rpx;padding: 20rpx 0;width: 98%;text-align: center;border-radius: 8rpx;margin:0 auto}
/* 眼镜 */
.glasses-pupup-view{width: 100%;background: #fff;border-radius: 30rpx 30rpx 0rpx 0rpx;padding-bottom: constant(safe-area-inset-bottom);padding-bottom: env(safe-area-inset-bottom);
position: relative;max-height: 94vh;padding-top: 20rpx;}
.glasses-pupup-close{position:absolute;top:25rpx;right:25rpx;width: 38rpx;height: 38rpx;}
.glasses-pupup-close image{width: 100%;height: 100%;}
.glasses-pupup-product{width: 100%;justify-content: flex-start;align-items: center;padding: 0rpx 20rpx;}
.glasses-product-image{width: 189rpx;height: 189rpx;border-radius: 4rpx;overflow: hidden;}
.glasses-product-image image{width: 100%;height: 100%;}
.glasses-product-info{flex: 1;padding-left: 20rpx;}
.glasses-product-name{font-size: 26rpx;color: #212121;display: -webkit-box;-webkit-box-orient: vertical;overflow: hidden;text-overflow: ellipsis;-webkit-line-clamp: 2; /* 控制显示的行数 */}
.glasses-product-price{align-items: center;justify-content: flex-start;font-size: 26rpx;padding-top: 10rpx;}
.glasses-product-tag{font-size: 20rpx;display: inline-block;border-radius: 4rpx;width: min-content;padding:4rpx 6rpx;}
.glasses-product-class{width:100%;padding-top: 20rpx;height:calc(76vh + env(safe-area-inset-bottom));}
.g-productClass-top{width: 100%;height: 120rpx;}
.g-productClass-top .left-view{width: 26%;height: 120rpx;position: relative;background: linear-gradient(29deg, transparent, transparent 48%, #f2f2ff 50%, #f2f2ff 100%);}
.g-productClass-top .left-view .left-view-text{color: #4840db;text-align: right;padding-top: 10rpx;}
.g-productClass-top .left-view .left-view-text .left-view-name{font-size: 30rpx;}
.g-productClass-top .right-view{background: #f2f2ff;height: 120rpx;align-items: center;width: 69%;}
.g-productClass-top .right-view .scroll-view-class{width: 100%;white-space: nowrap;height: 120rpx;}
.g-productClass-top .right-view .right-view-options{display: inline-block;line-height:125rpx;align-items: center;font-size: 28rpx;color: #000;
width: 165rpx;text-align: center;box-sizing: border-box;position: relative;}
.g-productClass-top .right-view .right-view-options .options-tag{width: auto;border-radius: 18rpx;text-align: center;line-height: 36rpx;
background: #ff0000;color: #fff;font-size: 22rpx;position: absolute;top: 10rpx;right: 10rpx;padding:0rpx 12rpx;}
.g-productClass-top .right-view .right-view-active{border-bottom:2px #4840db solid;color: #4840db;}
.g-productClass-top .right-view-but{background: #f2f2ff;height: 120rpx;align-items: center;width: 5%;}
.g-productClass-top .right-view-but image{width: 100%;height:45%;}
.g-productClass-bottom{width: 100%;height: 80rpx;justify-content: space-between;}
.g-productClass-bottom .left-view{width: 26%;align-items: center;justify-content: flex-end;}
.g-productClass-bottom .left-view .select-view{width: 35rpx;height: 35rpx;border: 1px #d8d8d8 solid;border-radius: 50%;align-items: center;justify-content: center;
margin-right: 15rpx;}
.g-productClass-bottom .left-view .select-view .select-view-image{width: 93%;height: 93%;}
.g-productClass-bottom .left-view .select-view-active{background-color: #3930d8;}
.g-productClass-bottom .left-view .left-view-class{align-items: center;width: 125rpx;justify-content: flex-start;padding-left: 20rpx;}
.g-productClass-bottom .left-view .left-view-class .title-text{font-size: 26rpx;color: #000;white-space: nowrap;}
.g-productClass-bottom .left-view .left-view-class .left-view-sku{font-size: 20rpx;color: #666;padding-left: 8rpx;padding-top: 5rpx;}
.g-productClass-bottom .right-view{width: 74%;align-items: center;justify-content: flex-start;}
.g-productClass-bottom .right-view .right-view-bg{background: #f2f2ff;width: 165rpx;height: 100%;}
/* #ifdef H5 */
.glasses-sku-view{width: 100%;max-height:calc(54vh + env(safe-area-inset-bottom));min-height: 300rpx;height: auto;}
/* #endif */
/* #ifndef H5*/
.glasses-sku-view{width: 100%;max-height:calc(47vh + env(safe-area-inset-bottom));min-height: 300rpx;height: auto;}
/* #endif */
.glasses-sku-options{width: 100%;height: 80rpx;align-items: center;}
.glasses-sku-options .left-sku-view{width: 26%;align-items: center;justify-content: flex-end;}
.glasses-sku-options .left-sku-view .select-view{width: 35rpx;height: 35rpx;border: 1px #d8d8d8 solid;border-radius: 50%;align-items: center;justify-content: center;
margin-right: 15rpx;}
.glasses-sku-options .left-sku-view .select-view .select-view-image{width: 93%;height: 93%;}
.glasses-sku-options .left-sku-view .select-view-active{background-color: #3930d8;}
.glasses-sku-options .left-sku-view .left-view-class{width: 125rpx;height:58rpx;text-align: left;font-size: 26rpx;color: #000;white-space: nowrap;
line-height: 58rpx;padding-left: 20rpx;}
.glasses-sku-options .left-sku-view .left-view-active{border-radius: 30rpx 0rpx 0rpx 30rpx;background: #3930d8;color: #fff;}
.glasses-sku-options .right-sku-view{width: 74%;align-items: center;justify-content: flex-start;height: 100%;}
.glasses-sku-options .right-sku-view .right-view-bg{background: #f2f2ff;width: 165rpx;height: 100%;line-height:80rpx;text-align: center;align-items: center;}
.right-sku-view .right-view-bg .right-view-num{border-radius: 0rpx 30rpx 30rpx 0rpx;width: 100%;height: 58rpx;align-items: center;text-align: center;justify-content: center;color: #666;}
.right-sku-view .right-view-bg .right-view-num-active{border: 1px #3930d8 solid;color: #3930d8;}
.right-sku-view .right-view-bg .right-view-num-active2{background:#3930d8 ;color: #fff;}
.right-sku-view .right-view-bg .right-view-bg-not{width: 100%;text-align: center;font-size: 26rpx;color: #333333;}
.glasses-sku-options .right-sku-view .right-view-inventory{width: 140rpx;height: 100%;line-height:80rpx;text-align: center;color: #000;font-size: 18rpx;white-space: nowrap;}
.right-count-view{flex:1;align-items: center;justify-content: center;}
.right-count-view .but-class{width: 50rpx;height: 50rpx;text-align:center;line-height: 45rpx;border: 1px #d8d8d8 solid;border-radius: 4rpx;font-size: 40rpx;}
.right-count-view .input-view{width: 134rpx;height: 50rpx;border: 1px #d8d8d8 solid;border-radius: 4rpx;margin: 0rpx 6rpx;}
.right-count-view .input-view .input-class{width: 100%;height: 50rpx;font-size: 24rpx;color: #666;text-align: center;}
.glasses-bottom-but{width: 96%;height: 90rpx;border-radius: 50rpx;margin: 20rpx auto 20rpx;align-items: center;justify-content: space-between;overflow: hidden;}
.glasses-bottom-but .bottom-but-class{flex: 1;height: 90rpx;line-height: 90rpx;color: #fff;border-radius: 0px;border: none;font-size: 28rpx;font-weight: bold;text-align: center;}
.promotion-tag{background: #fff; padding: 20rpx 0; display: flex; justify-content: center;margin-top: 20rpx;font-size: 24rpx;}
.promotion-tag-box{  display: flex;display: inline-flex;align-items: center;padding: 10rpx 20rpx;border: 1px solid #FCCE9A;border-radius: 100rpx;margin: 0 auto;background: #fff;}
.pro-tag {padding: 0 20rpx;border-right: 1px solid #FCCE9A;font-weight: bold;white-space: nowrap;}
.pro-tag:last-child { border-right: none; padding-right: 0;}

/*返利 和 积分显示*/
.ratio-list{padding-top: 10rpx;}
.ratio-label{height: 40rpx;border-radius: 10rpx;width:160rpx;border: 2rpx solid;margin-right:20rpx;}
.ratio-label .label{width: 55rpx ;height: 40rpx;line-height: 40rpx;border-radius: 10rpx 20rpx 5rpx 10rpx;color: #fff;text-align: center;}
.ratio-label .t1{text-align: center;width: 65%;font-size: 28rpx;}
</style>