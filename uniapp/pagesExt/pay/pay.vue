<template>
<view class="container">
	<block v-if="isload">
		<view class="top">
			<view class="f1">需支付金额</view>
			<view class="f2" v-if="payorder.score==0">
				<text class="t1">￥</text><text class="t2">{{payorder.money}}</text>
				<text style="font-size:28rpx" v-if="!isNull(payorder.service_fee_money) && payorder.service_fee_money>0"> + {{payorder.service_fee_money}}{{t('服务费')}}</text>
				<text style="font-size:28rpx" v-if="!isNull(payorder.total_freezemoney_price) && payorder.total_freezemoney_price>0"> + {{payorder.total_freezemoney_price}}{{t('冻结资金')}}</text>
				<text style="font-size:28rpx" v-if="!isNull(payorder.gold_bean) && payorder.gold_bean>0"> + {{payorder.gold_bean}}{{t('金豆')}}</text>
			</view>
			<view class="f2" v-else-if="payorder.money>0 && payorder.score>0"><text class="t1">￥</text><text class="t2">{{payorder.money}}</text><text style="font-size:28rpx"> + {{payorder.score}}{{t('积分')}}</text></view>
			<view class="f2" v-else><text class="t3">{{payorder.score}}{{t('积分')}}</text></view>
			<view v-if="payorder.discountText" style="color:#F00">{{payorder.discountText}}</view>
      <view v-if="countDown>-1 && djs" style="color:#F00">剩余支付时间：{{djs}}</view>
			<view class="f3" @tap="goto" :data-url="detailurl" v-if="detailurl!=''">订单详情<text class="iconfont iconjiantou"></text></view>
		</view>
		<view class="paytype">
			<view class="f1">选择支付方式：</view>
			<!-- 支付金额为0时 -->
			<block v-if="(payorder.money==0 && payorder.score>0) || scorepay==1">
				<view class="f2">
					<view class="item" @tap.stop="changeradio" data-typeid="1">
						<view class="t1 flex">
							<image class="img" :src="pre_url+'/static/img/pay-money.png'"/>
							<view class="flex-col"><text>{{t('积分')}}支付</text><view style="font-size:22rpx;font-weight:normal">剩 余{{t('积分')}}<text style="color:#FC5729">{{userinfo.score}}</text></view></view>
						</view>
						<view class="radio" :style="typeid=='1' ? 'background:'+t('color1')+';border:0' : ''"><image class="radio-img" :src="pre_url+'/static/img/checkd.png'"/></view>
					</view>
				</view>
			</block>
			<!-- 支付金额不为0时 -->
			<block v-else>
				<view class="f2">
					<!-- 余额支付开关, 受商品设置影响product_moneypay 0关闭 1开启 2仅限余额 -->
					<block v-if="moneypay!=2">
						<view class="item" v-if="wxpay==1 && (wxpay_type==0 || wxpay_type==1 || wxpay_type==2 || wxpay_type==3 || wxpay_type==4 || wxpay_type==5 || wxpay_type==6 || wxpay_type==8 || wxpay_type==9 || wxpay_type==10)" @tap.stop="changeradio" data-typeid="2">
							<view class="t1"><image class="img" :src="pre_url+'/static/img/withdraw-weixin.png'"/>微信支付</view>
							<view class="radio" :style="(combines.wxpay == 0 && typeid=='2') || combines.wxpay == '2' ? 'background:'+t('color1')+';border:0' : ''"><image class="radio-img" :src="pre_url+'/static/img/checkd.png'"/></view>
						</view>
						<view class="item" v-if="wxpay==1 && wxpay_type==22" @tap.stop="changeradio" data-typeid="22">
							<view class="t1"><image class="img" :src="pre_url+'/static/img/withdraw-weixin.png'"/>微信支付</view>
							<view class="radio" :style="typeid=='22'? 'background:'+t('color1')+';border:0' : ''"><image class="radio-img" :src="pre_url+'/static/img/checkd.png'"/></view>
						</view>

						<view class="item" v-if="alipay==2" @tap.stop="changeradio" data-typeid="23">
							<view class="t1"><image class="img" :src="pre_url+'/static/img/withdraw-alipay.png'"/>支付宝支付</view>
							<view class="radio" :style="typeid=='23'? 'background:'+t('color1')+';border:0' : ''"><image class="radio-img" :src="pre_url+'/static/img/checkd.png'"/></view>
						</view>
											
						<!-- <view class="item" v-if="alipay==2" @tap.stop="changeradio" data-typeid="24">
							<view class="t1"><image class="img" :src="pre_url+'/static/img/pay-money.png'"/>银联支付</view>
							<view class="radio" :style="typeid=='24' ? 'background:'+t('color1')+';border:0' : ''"><image class="radio-img" :src="pre_url+'/static/img/checkd.png'"/></view>
						</view> -->

						<view class="item" v-if="alipay==1" @tap.stop="changeradio" data-typeid="3">
							<view class="t1"><image class="img" :src="pre_url+'/static/img/withdraw-alipay.png'"/>支付宝支付</view>
							<view class="radio" :style="(combines.alipay == 0 && typeid=='3') || combines.alipay == '3' ? 'background:'+t('color1')+';border:0' : ''"><image class="radio-img" :src="pre_url+'/static/img/checkd.png'"/></view>
						</view>

						<block v-if="more_alipay==1">
							<view class="item" v-for="(item,index) in more_alipay_data" @tap.stop="changeradio" :data-typeid="item.typeid">
								<view class="t1"><image class="img" :src="pre_url+'/static/img/withdraw-alipay.png'"/>{{item.name}}</view>
								<view class="radio" :style="(combines.alipay == 0 && typeid==item.typeid) || combines.alipay == item.typeid ? 'background:'+t('color1')+';border:0' : ''"><image class="radio-img" :src="pre_url+'/static/img/checkd.png'"/></view>
							</view>
						</block>
						
						<view class="item" v-if="paypal==1" @tap.stop="changeradio" data-typeid="51">
							<view class="t1"><image class="img" :src="pre_url+'/static/img/paypal.png'"/>PayPal支付</view>
							<view class="radio" :style="typeid=='51' ? 'background:'+t('color1')+';border:0' : ''"><image class="radio-img" :src="pre_url+'/static/img/checkd.png'"/></view>
						</view>
						<view class="item" v-if="adapay_union==1 && getplatform() == 'h5'" @tap.stop="changeradio" data-typeid="61">
							<view class="t1"><image class="img" :src="pre_url+'/static/img/withdraw-cash.png'"/>银联支付</view>
							<view class="radio" :style="typeid=='61' ? 'background:'+t('color1')+';border:0' : ''"><image class="radio-img" :src="pre_url+'/static/img/checkd.png'"/></view>
						</view>					
						<view class="item" v-if="baidupay==1" @tap.stop="changeradio" data-typeid="11">
							<view class="t1"><image class="img" :src="pre_url+'/static/img/pay-money.png'"/>在线支付</view>
							<view class="radio" :style="typeid=='11' ? 'background:'+t('color1')+';border:0' : ''"><image class="radio-img" :src="pre_url+'/static/img/checkd.png'"/></view>
						</view>
						<view class="item" v-if="toutiaopay==1" @tap.stop="changeradio" data-typeid="12">
							<view class="t1"><image class="img" :src="pre_url+'/static/img/pay-money.png'"/>在线支付</view>
							<view class="radio" :style="typeid=='12' ? 'background:'+t('color1')+';border:0' : ''"><image class="radio-img" :src="pre_url+'/static/img/checkd.png'"/></view>
						</view>
						<view class="item" v-if="overdraft_moneypay==1" @tap.stop="changeradio" data-typeid="38">
							<view class="t1 flex">
								<image class="img" :src="pre_url+'/static/img/overdraft_pay.png'"/>
								<view class="flex-col"><text>{{t('信用额度')}}</text><view style="font-size:22rpx;font-weight:normal">可用额度<text style="color:#FC5729">{{userinfo.overdraft_money}}</text></view></view>
							</view>
							<view class="radio" :style="typeid=='38' ? 'background:'+t('color1')+';border:0' : ''"><image class="radio-img" :src="pre_url+'/static/img/checkd.png'"/></view>
						</view>
            <view class="item" v-if="yunshanfuwxpay==1 && getplatform() == 'wx'" @tap.stop="changeradio" data-typeid="122">
            	<view class="t1"><image class="img" :src="pre_url+'/static/img/withdraw-yunshanfu.png'"/>云闪付支付</view>
            	<view class="radio" :style="typeid=='122' ? 'background:'+t('color1')+';border:0' : ''"><image class="radio-img" :src="pre_url+'/static/img/checkd.png'"/></view>
            </view>
						<view class="item" v-if="huifu_quickpay==1" @tap.stop="changeradio" data-typeid="63">
							<view class="t1"><image class="img" :src="pre_url+'/static/img/quick_pay.png'"/>汇付快捷支付</view>
							<view class="radio" :style="typeid=='63' ? 'background:'+t('color1')+';border:0' : ''"><image class="radio-img" :src="pre_url+'/static/img/checkd.png'"/></view>
						</view>
					</block>
					<view class="item" v-if="moneypay==1 || moneypay==2" @tap.stop="changeradio" data-typeid="1">
						<view class="t1 flex">
							<image class="img" :src="pre_url+'/static/img/pay-money.png'"/>
							<view class="flex-col"><text>{{t('余额')}}支付</text><view style="font-size:22rpx;font-weight:normal">可用余额<text style="color:#FC5729">￥{{userinfo.money}}</text></view></view>
						</view>
						<view class="radio" :style="(combines.moneypay == 0 && typeid=='1') || combines.moneypay == '1' ? 'background:'+t('color1')+';border:0' : ''"><image class="radio-img" :src="pre_url+'/static/img/checkd.png'"/></view>
					</view>
					<block v-if="moneypay!=2">
					<view class="item" v-if="xiaofeipay==1 " @tap.stop="changeradio" data-typeid="71">
						<view class="t1 flex">
							<image class="img" :src="pre_url+'/static/img/pay-money.png'"/>
							<view class="flex-col"><text>{{t('冻结佣金')}}支付</text><view style="font-size:22rpx;font-weight:normal">可用余额<text style="color:#FC5729">￥{{userinfo.xiaofei_money}}</text></view></view>
						</view>
						<view class="radio" :style="typeid=='71' ? 'background:'+t('color1')+';border:0' : ''"><image class="radio-img" :src="pre_url+'/static/img/checkd.png'"/></view>
					</view>
					<view class="item" v-if="xianjinpay==1" @tap.stop="changeradio" data-typeid="1">
						<view class="t1 flex">
								<image class="img" :src="pre_url+'/static/img/pay-money.png'"/>
								<view class="flex-col"><text>{{t('现金')}}支付</text><view style="font-size:22rpx;font-weight:normal">可用余额<text style="color:#FC5729">￥{{userinfo.xianjin}}</text></view></view>
						</view>
						<view class="radio" :style="typeid=='1' ? 'background:'+t('color1')+';border:0' : ''"><image class="radio-img" :src="pre_url+'/static/img/checkd.png'"/></view>
					</view>
					<view class="item" v-if="yuanbaopay==1" @tap.stop="changeradio" data-typeid="yuanbao" style="height: 130rpx;">
						<view class="t1 flex">
							<image class="img" :src="pre_url+'/static/img/pay-money.png'"/>
							<view class="flex-col">
                  <text>{{t('元宝')}}支付</text>
                  <text style="font-size:22rpx;font-weight:normal">
                      可用{{t('元宝')}}<text style="color:#FC5729">{{userinfo.yuanbao}}</text>
                  </text>
                  <text style="font-size:22rpx;font-weight:normal">
                      需支付<text style="color:#FC5729">{{yuanbao_msg}}</text>
                  </text>
              </view>
						</view>
						<view class="radio" :style="typeid=='yuanbao' ? 'background:'+t('color1')+';border:0' : ''"><image class="radio-img" :src="pre_url+'/static/img/checkd.png'"/></view>
					</view>
          <view v-if="sxpay_native == 1" class="item"  @tap.stop="changeradio" data-typeid="sxpay_native">
            <view class="t1 flex">
                <image class="img" :src="pre_url+'/static/img/pay-money.png'"/>
                <view class="flex-col"><text>聚合支付</text></view>
            </view>
            <view class="radio" :style="typeid=='sxpay_native' ? 'background:'+t('color1')+';border:0' : ''">
              <image class="radio-img" :src="pre_url+'/static/img/checkd.png'"/>
            </view>
          </view>
					</block>
					<!-- 新增支付都要放在moneypay!=2里面 -->
				</view>
			</block>
		</view>
    <view v-if="payorder.all_active_score && payorder.all_active_score>0" class="paytype">
    	<view class="f1" style="height: 40rpx;line-height: 40rpx;margin-top: 20rpx;">{{payorder.active_score_payshow_text || '预估获得'}}：</view>
      <view class="f2">
      	<view class="item">
      		<view class="t1 flex" style="flex-wrap: wrap;height: 50rpx;line-height: 50rpx;color:#FC5729">
            <block v-if="payorder.score_to_money_and_commission && payorder.score_to_money_and_commission == 1">
              <view v-if="payorder.remaining_score && payorder.remaining_score>0" style="margin-right: 20rpx;">{{payorder.remaining_score}}{{t('积分')}}</view>
              <view v-if="payorder.score_to_money && payorder.score_to_money>0" style="margin-right: 20rpx;">{{payorder.score_to_money}}{{t('余额')}}</view>
              <view v-if="payorder.score_to_commission && payorder.score_to_commission>0" >{{payorder.score_to_commission}}{{t('佣金')}}</view>
            </block>
            <block v-else>
              <view>{{payorder.all_active_score}}{{t('积分')}}</view>
            </block>
      		</view>
      	</view>
      </view>
    </view>
    <block v-if="combines.moneypay ==1 && (combines.wxpay !=0 || combines.alipay !=0)">
      <button class="btn" @tap="topay3" :style="{background:t('color1')}" v-if="typeid != '0' && liji_pay_button">立即支付</button>
      <button class="btn" @tap="topay3" data-wxpaytype="7" :style="{background:t('color1')}" v-if="typeid == '2' && wxpay_native_h5 && getplatform() == 'h5'">微信收款码</button>
    </block>
    <block v-else>
      <button class="btn" @tap="topay" :style="{background:t('color1')}" v-if="typeid != '0' && liji_pay_button">立即支付</button>
      <button class="btn" @tap="topay" data-wxpaytype="7" :style="{background:t('color1')}" v-if="typeid == '2' && wxpay_native_h5 && getplatform() == 'h5'">微信收款码</button>
    </block>
    
		<button class="btn" @tap="topay2" v-if="cancod==1" style="background:rgba(126,113,246,0.5);">{{codtxt}}<text style="font-size:24rpx" v-if="cod_frontmoney > 0">(需付定金￥{{cod_frontmoney}})</text></button>
		<button class="btn" @tap="topayTransfer" v-if="pay_transfer==1" :style="{background:t('color2')}" :data-text="t('转账汇款')">{{t('转账汇款')}}</button>
		<button class="btn" @tap="topayMonth" v-if="pay_month==1" :style="{background:t('color1')}">{{pay_month_txt}}</button>
		<block v-if="daifu">
			<button class="btn daifu-btn" @tap="todaifu" v-if="getplatform() == 'h5' || getplatform() == 'mp' || getplatform() == 'app'">
				{{daifu_txt}}
			</button>
			<button class="btn daifu-btn" open-type="share" v-else>
				{{daifu_txt}}
			</button>
		</block>
		<uni-popup id="dialogInput" ref="dialogInput" type="dialog">
			<uni-popup-dialog mode="password" title="支付密码" value="" placeholder="请输入支付密码" @confirm="getpwd"></uni-popup-dialog>
		</uni-popup>
		<!-- 广告位Start -->
		<block v-if="adlist.length>0">
			<view class="ad-box">
				<block v-for="(item,index) in adlist" :key="index">
					<view class="ad-item" v-if="item.pic" @tap="goto" :data-url="item.url"><image :src="item.pic" mode="widthFix"></view>
				</block>
			</view>
			<view style="height: 30rpx;"></view>
		</block>
		<!-- 广告位End -->
    
    <!-- 广告Start -->
    <block v-if="advertising">
      <view v-if="advertising.type == 1" class="ad-box">
        <view class="ad-item" @tap="goto" :data-url="advertising.pictourl"><image :src="advertising.pic" mode="widthFix"></view>
      </view>
      <view v-else class="ad-box">
        <dp-wxad :params="advertising"></dp-wxad>
      </view>
    </block>
    <!-- 广告End -->
    
		<block v-if="give_coupon_show">
			<view class="give-coupon flex-x-center flex-y-center">
				<view class='coupon-block'>
					<image :src="pre_url+'/static/img/coupon-top.png'" style="width:630rpx;height:330rpx;"></image>
					<view @tap="give_coupon_close" :data-url="give_coupon_close_url" class="coupon-del flex-x-center flex-y-center">
						<image :src="pre_url+'/static/img/coupon-del.png'"></image>
					</view>
					<view class="flex-x-center">
						<view class="coupon-info">
							<view class="flex-x-center coupon-get">获得{{give_coupon_num}}张{{t('优惠券')}}</view>
							<view style="background:#f5f5f5;padding:10rpx 0">
							<block v-for="(item,index) in give_coupon_list" :key="item.id">
								<block v-if="index < 3">
									<view class="coupon-coupon">
										<view :class="item.type==1?'pt_img1':'pt_img2'"></view>
										<view class="pt_left" :class="item.type==1?'':'bg2'">
											<view class="f1" v-if="item.type==1 || item.type==5"><text class="t0">￥</text><text class="t1">{{item.money}}</text></view>
											<view class="f1" v-if="item.type==3"><text class="t1">{{item.limit_count}}</text><text class="t2">次</text></view>
											<view class="f1" v-if="item.type==10"><text class="t1">{{item.discount/10}}</text><text class="t2">折</text></view>
											<block v-if="item.type!=1 && item.type!=3 && item.type!=5 && item.type!=10">
											<view class="f1">{{item.type_txt}}</view>
											</block>
											<view class="f2" v-if="item.type==1 || item.type==4 || item.type==5 || item.type==10">
												<text v-if="item.minprice>0">满{{item.minprice}}元可用</text>
												<text v-else>无门槛</text>
											</view>
										</view>
										<view class="pt_right">
											<view class="f1">
												<view class="t1">{{item.name}}</view>
												<view class="t2" v-if="item.type_txt">{{item.type_txt}}</view>
												<view class="t4" v-if="item.bid>0">适用商家：{{item.bname}}</view>
												<!-- <view class="t3">有效期至 {{item.yxqdate}}</view> -->
											</view>
										</view>
										<view class="coupon_num" v-if="item.givenum > 1">×{{item.givenum}}</view>
									</view>
								</block>
							</block>
							</view>
							<view @tap="goto" data-url="/pagesExt/coupon/mycoupon" class="flex-x-center coupon-btn">前往查看</view>
						</view>
					</view>
				</view>
			</view>
		</block>
		<!-- 支付后跳转链接为打开小程序的时候，H5显示此按钮 -->
		<uni-popup id="dialogOpenWeapp" ref="dialogOpenWeapp" type="dialog" :maskClick="false">
			<view style="background:#fff;padding:50rpx;position:relative;border-radius:20rpx">
				<view style="height:80px;line-height:80px;width:200px;margin:0 auto;font-size: 18px;text-align:center;font-weight:bold;text-align:center;color:#333">恭喜您支付成功</view>
				<!-- #ifdef H5 -->
				<wx-open-launch-weapp :username="payorder.payafter_username" :path="payorder.payafter_path">
					<script type="text/wxtag-template">
						<div style="background:#FD4A46;height:50px;line-height: 50px;width:200px;margin:0 auto;border-radius:5px;margin-top:15px;color: #fff;font-size: 15px;font-weight:bold;text-align:center">{{payorder.payafterbtntext}}</div>
					</script>
				</wx-open-launch-weapp>
				<!-- #endif -->
				<view style="height:50px;line-height: 50px;width:200px;margin:0 auto;border-radius:5px;color:#66f;font-size: 14px;text-align:center" @tap="goto" :data-url="detailurl">查看订单详情</view>
			</view>
		</uni-popup>

		<uni-popup id="dialogPayconfirm" ref="dialogPayconfirm" type="dialog" :maskClick="false">
			<uni-popup-dialog type="info" title="支付确认" content="是否已完成支付" @confirm="PayconfirmFun"></uni-popup-dialog>
		</uni-popup>
        
    <!-- 元宝s -->
    <view v-if="yuanbaopay==1 && open_pay" style="width: 100%;height: 100%;position: fixed;z-index: 10;background-color: #000;opacity: 0.45;top: 0;"></view>
    <view v-if="yuanbaopay==1 && open_pay" style="width: 90%;position: fixed;z-index: 11;left: 5%;top:25%;background-color:#fff ;">
        <view class="paytype">
            <view class="f2">
                <view class="item" v-if="wxpay==1 && (wxpay_type==0 || wxpay_type==1 || wxpay_type==2 || wxpay_type==3 || wxpay_type==4 || wxpay_type==10)" @tap.stop="changeradio" data-typeid="2">
                    <view class="t1"><image class="img" :src="pre_url+'/static/img/withdraw-weixin.png'"/>微信支付</view>
                    <view class="radio" :style="typeid=='2' ? 'background:'+t('color1')+';border:0' : ''"><image class="radio-img" :src="pre_url+'/static/img/checkd.png'"/></view>
                </view>
                <view class="item" v-if="wxpay==1 && wxpay_type==22" @tap.stop="changeradio" data-typeid="22">
                    <view class="t1"><image class="img" :src="pre_url+'/static/img/withdraw-weixin.png'"/>微信支付</view>
                    <view class="radio" :style="typeid=='22' ? 'background:'+t('color1')+';border:0' : ''"><image class="radio-img" :src="pre_url+'/static/img/checkd.png'"/></view>
                </view>
                <view class="item" v-if="alipay==2" @tap.stop="changeradio" data-typeid="23">
                    <view class="t1"><image class="img" :src="pre_url+'/static/img/withdraw-alipay.png'"/>支付宝支付</view>
                    <view class="radio" :style="typeid=='23' ? 'background:'+t('color1')+';border:0' : ''"><image class="radio-img" :src="pre_url+'/static/img/checkd.png'"/></view>
                </view>
                <view class="item" v-if="alipay==1" @tap.stop="changeradio" data-typeid="3">
                    <view class="t1"><image class="img" :src="pre_url+'/static/img/withdraw-alipay.png'"/>支付宝支付</view>
                    <view class="radio" :style="typeid=='3' ? 'background:'+t('color1')+';border:0' : ''"><image class="radio-img" :src="pre_url+'/static/img/checkd.png'"/></view>
                </view>
                
                <block v-if="more_alipay==1">
                  <view class="item" v-for="(item,index) in more_alipay_data" @tap.stop="changeradio" :data-typeid="item.typeid">
                    <view class="t1"><image class="img" :src="pre_url+'/static/img/withdraw-alipay.png'"/>{{item.name}}</view>
                    <view class="radio" :style="typeid==item.typeid ? 'background:'+t('color1')+';border:0' : ''"><image class="radio-img" :src="pre_url+'/static/img/checkd.png'"/></view>
                  </view>
                </block>
                
                <view class="item" v-if="baidupay==1" @tap.stop="changeradio" data-typeid="11">
                    <view class="t1"><image class="img" :src="pre_url+'/static/img/pay-money.png'"/>在线支付</view>
                    <view class="radio" :style="typeid=='11' ? 'background:'+t('color1')+';border:0' : ''"><image class="radio-img" :src="pre_url+'/static/img/checkd.png'"/></view>
                </view>
                <view class="item" v-if="toutiaopay==1" @tap.stop="changeradio" data-typeid="12">
                    <view class="t1"><image class="img" :src="pre_url+'/static/img/pay-money.png'"/>在线支付</view>
                    <view class="radio" :style="typeid=='12' ? 'background:'+t('color1')+';border:0' : ''"><image class="radio-img" :src="pre_url+'/static/img/checkd.png'"/></view>
                </view>
                <view class="item" v-if="moneypay==1" @tap.stop="changeradio" data-typeid="1">
                    <view class="t1 flex">
                        <image class="img" :src="pre_url+'/static/img/pay-money.png'"/>
                        <view class="flex-col"><text>{{t('余额')}}支付</text><view style="font-size:22rpx;font-weight:normal">可用余额<text style="color:#FC5729">￥{{userinfo.money}}</text></view></view>
                    </view>
                    <view class="radio" :style="typeid=='1' ? 'background:'+t('color1')+';border:0' : ''"><image class="radio-img" :src="pre_url+'/static/img/checkd.png'"/></view>
                </view>
								<view class="item" v-if="xiaofeipay==1" @tap.stop="changeradio" data-typeid="71">
										<view class="t1 flex">
												<image class="img" :src="pre_url+'/static/img/pay-money.png'"/>
												<view class="flex-col"><text>{{t('冻结佣金')}}支付</text><view style="font-size:22rpx;font-weight:normal">可用余额<text style="color:#FC5729">￥{{userinfo.xiaofei_money}}</text></view></view>
										</view>
										<view class="radio" :style="typeid=='71' ? 'background:'+t('color1')+';border:0' : ''"><image class="radio-img" :src="pre_url+'/static/img/checkd.png'"/></view>
								</view>
            </view>
        </view>
        <view style="overflow: hidden;width: 100%;">
            <view style="width: 300rpx;float: left;">
                <view>
                    <button class="btn" @tap="close_pay" style="margin-bottom: 20rpx;background-color: #999;">取消</button>
                </view>
            </view>
            <view style="width: 300rpx;float: right;">
                <view>
                    <button class="btn" @tap="topay" :style="{background:t('color1')}" v-if="typeid != '0'" style="margin-bottom: 20rpx;">确定</button>
                </view>
            </view>
        </view>
    </view>
    <!-- 元宝e -->
    <block v-if="invite_status && invite_free">
      <view @tap="closeInvite" style="width:100%;height: 100%;background-color: #000;position: fixed;opacity: 0.5;z-index: 99;top:0"></view>
      <view style="width: 700rpx;margin: 0 auto;position: fixed;top:10%;left: 25rpx;z-index: 100;">
          <view @tap="gotoInvite" style="background-color: #fff;border-radius: 20rpx;overflow: hidden;width: 100%;min-height: 700rpx;">
              <image :src="invite_free.pic" mode="widthFix" style="width: 100%;height: auto;"></image>
          </view>
          <view @tap="closeInvite" v-if="invite_status && invite_free" style="width: 80rpx;height: 80rpx;line-height: 80rpx;text-align: center;font-size: 30rpx;background-color: #fff;margin: 0 auto;border-radius: 50%;margin-top: 20rpx;">
              X
          </view>
      </view>
    </block>
		<!-- 付款前分享 -->
		<!-- #ifdef MP-WEIXIN || H5 -->
		<view class="posterShare" v-if="showposter">
			<view style="width: 708rpx;height: 764rpx;margin-top: 40rpx;">
				<image :src="pre_url+'/static/img/share_guide.png'" style="width: 100%;height: 100%;"></image>
				<view style="display: flex;justify-content: center;">
					<view class="posterButton"  @tap="posterDialogClose">确认</view>
				</view>
			</view>
		</view>
		<!-- #endif -->
    <uni-popup id="dialogInvitecashback" ref="dialogInvitecashback" type="dialog">
      <view class="uni-popup-dialog">
        <view class="uni-dialog-title">
          <text class="uni-dialog-title-text">邀请返现</text>
        </view>
        <view class="uni-dialog-content">
          <view style="line-height: 50rpx;">
            {{payorder.ictips}}
          </view>
        </view>
        <view class="uni-dialog-button-group">
          <view class="uni-dialog-button" @tap="dialogInvitecashbackClose">
            <text class="uni-dialog-button-text">取消</text>
          </view>
          <block>
            <view class="uni-dialog-button uni-border-left" @tap="goto" :data-url="'/pages/shop/product?id='+payorder.proid+'&sharetypevisible=true'">
              <text class="uni-dialog-button-text uni-button-color">分享</text>
            </view>
          </block>
        </view>
      </view>
    </uni-popup>
    <!-- 收款码弹框-->
    <uni-popup id="buildWxNativeH5" ref="buildWxNativeH5" type="dialog">
      <view class="uni-popup-dialog" style="text-align: center">
        <view class="uni-dialog-title">
          <text class="uni-dialog-title-text">扫码付款</text>
        </view>
        <image :src="pay_wx_qrcode_url" @tap="previewImage" :data-url="pay_wx_qrcode_url" class="img"/>
        <view class="uni-dialog-content">
          <view style="line-height: 50rpx;">请用{{qrcodename}}扫描二维码完成付款</view>
        </view>
        <view class="uni-dialog-button-group">
            <view class="uni-dialog-button uni-border-left" @tap="refreshTopay">
              <text class="uni-dialog-button-text uni-button-color">点击刷新二维码</text>
            </view>
        </view>
        <view class="uni-dialog-button-group">
          <view class="uni-dialog-button uni-border-left" @tap="PayconfirmFun2">
            <text class="uni-dialog-button-text uni-button-color">已完成支付</text>
          </view>
          <view class="uni-dialog-button" @tap="buildWxNativeH5Close">
            <text class="uni-dialog-button-text">取消支付</text>
          </view>
        </view>
      </view>
    </uni-popup>
    <!-- 赠好友s -->
    <uni-popup id="dialogGiveorder" ref="dialogGiveorder" type="dialog">
      <view class="uni-popup-dialog">
        <view class="uni-dialog-title">
          <text class="uni-dialog-title-text">赠好友</text>
        </view>
        <view class="uni-dialog-content">
          <view style="line-height: 50rpx;">
            {{giveordertitle}}
          </view>
        </view>
        <view style="width:560rpx ;margin: auto;">
          <image :src="giveorderpic" @tap="previewImage" :data-url="giveorderpic" class="img" style="width: 100%;"/>
        </view>
        
        <view class="uni-dialog-button-group">
          <view class="uni-dialog-button" @tap="dialogGiveorderClose">
            <text class="uni-dialog-button-text">取消</text>
          </view>
          <block>
            <view class="uni-dialog-button uni-border-left">
              <!-- #ifdef APP -->
                <view @tap="giveordershareapp" class="uni-dialog-button-text uni-button-color">分享好友</view>
              <!-- #endif -->

              <!-- #ifdef H5 -->
                <view @tap="giveordersharemp" class="uni-dialog-button-text uni-button-color">分享好友</view>
              <!-- #endif -->
              
              <!-- #ifndef H5 -->
                <button open-type="share" class="uni-dialog-button-text uni-button-color">分享好友</button>
              <!-- #endif -->
            </view>
          </block>
        </view>
      </view>
    </uni-popup>
    <!-- 赠好友e -->
	</block>
	<loading v-if="loading"></loading>
	<dp-tabbar :opt="opt"></dp-tabbar>
	<popmsg ref="popmsg"></popmsg>
</view>
</template>

<script>
var app = getApp();
var payInterval = null;//聚合支付定时请求
var countDownInterval = null;//支付倒计时
export default {
  data() {
    return {
			opt:{},
			loading:false,
      isload: false,
			menuindex:-1,
			pre_url:app.globalData.pre_url,
			
			detailurl:'',
			tourl:'',
			typeid:'0',
			wxpay:0,
			wxpay_type:0,
			alipay:0,
			baidupay:0,
			toutiaopay:0,
			moneypay:0,//余额支付开关, 受商品设置影响product_moneypay 0关闭 1开启 2仅限余额
			cancod:0,
			cod_frontmoney:0,
			cod_payorderid:0,
			overdraft_moneypay:0,
			daifu:0,
			daifu_txt:'好友代付',
			pay_month:0,
			pay_transfer:0,
			codtxt:'',
			pay_month_txt:'',
			give_coupon_list:[],
			give_coupon_num:0,
			userinfo:[],
			paypwd: '',
			hiddenmodalput: true,
			payorder: {},
			tmplids: [],
			give_coupon_show: false,
			give_coupon_close_url: "",
			more_alipay:0,
			more_alipay_data:[],
			paypal:0,
			dapay_union:0,
			//元宝支付
			yuanbao_money:0,//现金
			total_yuanbao:0,//总元宝
			yuanbao_msg:'',//元宝文字描述
			yuanbaopay:0,//是否开启元宝支付
			open_pay:false,//打开支付选项
			pay_type:'',//支付类型（新增）
			payResponseStatus:false,//支付回调是否执行，防止重复跳转
			
			invite_free:'',
			invite_status:false,
			free_tmplids:'',
			sharepic:app.globalData.initdata.logo,
			adlist:[],//广告位
      
      alih5pay:false,
      alih5:false,
      alipay_type:0,//支付宝普通模式
      ali_appid:'',
      alipayopenid:'',
      xiaofeipay:0,//冻结佣金钱包支付
      alipayPlugin:0,//支付宝交易组件
			is_pingce:0,
			is_maidan:0,
      
      ispost:0,//是否可以提交，防止过快点击次数 0可以，1不可以
      
      //是否开启余额和微信或支付组合支付
      iscombine:0,
      combines:{'moneypay':0,'wxpay':0,'alipay':0},
			share_payment:0, //付款前分享，0关闭，1开启
			showposter: false,
			share_product:[], //分享商品
      moneypay_lvprice_status:false,//会员价仅限余额支付
      pay_wx_qrcode_url:'',//微信收款二维码
      wxpay_native_h5:false,//是否开启微信收款吗
      wx_liji_pay:true,//后台控制h5端微信立即支付按钮状态
      liji_pay_button:true,//是否开启即支付按钮
      yunshanfuwxpay:0,//云闪付小程序支付
      usegiveorder:false,//是否使用赠送礼物功能
      giveordertitle:'',
      giveorderpic:'',
      huifu_quickpay:0,//汇付快捷支付
      xianjinpay:0,//使用现金账户支付
      
      qrcodename:'微信',//收款码名称
      refreshtopay:false,//是否刷新支付，重新生成支付参数
      topaydata:{},//发起支付的参数，用于刷新支付时使用
      sxpay_native:0,//是否开启随行付聚合支付 0：不开启 1：开启
      payIntervalStopnum:300,//定时执行计时停止次数，默认300次，十分钟
      payIntervalnum:0,//定时执行计时次数
      
      countDown:-1,//付款倒计时，默认为-1，不显示
      djs:'',//倒计时时间
      
      advertising:'',//广告
	  scorepay:0,//是否可使用积分支付 0否 1是 

    };
  },
	onShareAppMessage: function () {
		var that = this;
		//付款前分享
		if(!app.isEmpty(that.share_product)){
			//#ifdef MP-WEIXIN
			that.sharecallback();
			return {
				title: that.share_product.sharetitle,
				path: '/pages/shop/product?id=' + that.share_product.id,
				imageUrl: that.share_product.sharepic,
				desc:that.share_product.sharedesc
			};
			//#endif
		}
    
    if(that.usegiveorder){
      return {
      	title: that.giveordertitle,
      	path: '/pagesC/shop/takegiveorder?payorderid=' + that.payorder.id,
      	imageUrl: that.giveorderpic,
      	desc: that.giveordertitle,
      };
    }
		
    var title = '您有一份好友代付待查收，请尽快处理！';
    var sharepic  = that.sharepic;
    var sharelink = app.globalData.pre_url +'/h5/'+app.globalData.aid+'.html#/pagesExt/pay/daifu?scene=id_'+that.payorder.id;
    console.log({title:title,tolink:sharelink,pic:sharepic})
		var sharedata = this._sharewx({title:title,tolink:sharelink,pic:sharepic});
		return sharedata;
	},
	onShareTimeline:function(){
		var that = this;
		//付款前分享
		if(!app.isEmpty(that.share_product)){
			//#ifdef MP-WEIXIN
			that.sharecallback();
			return {
				title: that.share_product.sharetitle,
				path: '/pages/shop/product?id=' + that.share_product.id,
				imageUrl: that.share_product.sharepic,
				desc:that.share_product.sharedesc
			};
			//#endif
		}
    if(that.usegiveorder){
      return {
      	title: that.giveordertitle,
      	path: '/pagesC/shop/takegiveorder?payorderid=' + that.payorder.id,
      	imageUrl: that.giveorderpic,
      	desc: that.giveordertitle,
      };
    }
		
    var title = '您有一份好友代付待查收，请尽快处理！';
    var sharepic = that.sharepic;
    var sharelink = app.globalData.pre_url +'/h5/'+app.globalData.aid+'.html#/pagesExt/pay/daifu?scene=id_'+that.payorder.id;
		var sharewxdata = this._sharewx({title:title,tolink:sharelink,pic:sharepic});
		var query = (sharewxdata.path).split('?')[1]+'&seetype=circle';
		var link = app.globalData.pre_url +'/h5/'+app.globalData.aid+'.html#'+(sharewxdata.path).split('?')[0]+'&seetype=circle';
		return {
			title: sharewxdata.title,
			imageUrl: sharewxdata.imageUrl,
			query: query,
			link:link
		}
	},

  onLoad: function (opt) {
		this.opt = app.getopts(opt);
    // #ifdef H5
    if (navigator.userAgent.indexOf('AlipayClient') > -1) {
      this.alih5 = true;
    }
    // #endif
		if(this.opt.tourl) this.tourl = decodeURIComponent(this.opt.tourl);
		//this.getdata();
		if(this.opt.is_maidan) this.is_maidan = this.opt.is_maidan;
  },
	onShow: function () {
		var that = this;

    //是否可以用uni.getEnterOptionsSync函数，uni有版本限制vue3项目：uni-app 3.2.13+ 支持；vue2项目：uni-app 3.5.1+ 支持。
    var getEnterOptionsSync = true;
    //#ifndef MP-WEIXIN
    var systemInfo = uni.getWindowInfo();
    //判断uniapp版本
    var uni_version = systemInfo.uniRuntimeVersion;
    if(uni_version){
      var versionarr = uni_version.split('.');
      var len = versionarr.length;
      //HBuilderX 3.9前 uniRuntimeVersion三段式3.4.18 HBuilderX 3.9起，取消了字符串三段式版本，改为了数字方式，如3.91
      if(len>=3){
        if(versionarr[0]<3){
          getEnterOptionsSync = false
        }else if(versionarr[0]==3){
          if(versionarr[1]<5){
            getEnterOptionsSync = false
          }else if(versionarr[1]==5){
            if(versionarr[2]<1){
              getEnterOptionsSync = false
            }
          }
        } 
      }
    }
    // #endif
    
    if(getEnterOptionsSync){
      // #ifdef MP-WEIXIN
      let opt = wx.getEnterOptionsSync();
      // #endif
      //#ifndef MP-WEIXIN
      let opt = uni.getEnterOptionsSync();
      // #endif
      if (opt && opt.referrerInfo && opt.referrerInfo.extraData) {
      	let payStatus = opt.referrerInfo.extraData.payStatus;
      	//  payStatus: "0"=支付失败；
      	//  payStatus: "1"=支付成功；
      	//  payStatus: "3"=支付取消；
      	if(payStatus == '1'){
      		setTimeout(function () {
      		    if (that.give_coupon_list && Object.keys(that.give_coupon_list).length > 0) {
      		        that.give_coupon_show = true;
      		        that.give_coupon_close_url = that.tourl;
      		    } else {
      		        that.gotourl(that.tourl,'reLaunch');
      		    }
      		}, 1000);
      	}else if(payStatus == '0'){
      		app.alert('支付失败，请重试');
      	}else if(payStatus == '3'){
          //取消支付操作
          app.post('ApiPay/cancelpay', {orderid: that.opt.id,typeid: 0}, function (res) {});
        }
      }
    }
		this.getdata();
	},
	onPullDownRefresh: function () {
		this.getdata();
	},
  onUnload: function () {
    clearInterval(payInterval);
    clearInterval(countDownInterval);
  },
  methods: {
		getdata: function () {
			var that = this;
			that.loading = true;
			var thisurl = '';
			if(app.globalData.platform == 'mp' || app.globalData.platform == 'h5'){
				thisurl = location.href;
			}
			app.post('ApiPay/pay', {orderid: that.opt.id,is_maidan:that.is_maidan,thisurl:thisurl,tourl:that.tourl,scene:app.globalData.scene}, function (res) {
				that.loading = false;
				if (res.status == 0) {
					app.error(res.msg);
					if(res.url && !that.payResponseStatus){
						setTimeout(function(){ app.goto(res.url) },1000);
					}
					return;
				}
        that.is_pingce = res.is_pingce;
				that.wxpay = res.wxpay;
				that.wxpay_type = res.wxpay_type;
				that.alipay = res.alipay;
				that.baidupay = res.baidupay;
				that.toutiaopay = res.toutiaopay;
				that.cancod = res.cancod;
				that.codtxt = res.codtxt;
				that.cod_frontmoney = res.cod_frontmoney || 0;
				that.cod_payorderid = res.cod_payorderid || 0;
				that.daifu = res.daifu;
				that.daifu_txt = res.daifu_txt;
				that.pay_money = res.pay_money;
				that.pay_money_txt = res.pay_money_txt;
				that.moneypay = res.moneypay;
				that.overdraft_moneypay = res.overdraft_moneypay;
				that.xiaofeipay = res.xiaofeipay;
				that.pay_transfer = res.pay_transfer;
				that.pay_transfer_info = res.pay_transfer_info;
				that.pay_month = res.pay_month;
				that.pay_month_txt = res.pay_month_txt;
				that.payorder = res.payorder;
				that.userinfo = res.userinfo;
				that.tmplids = res.tmplids;
				that.give_coupon_list = res.give_coupon_list;
				if(that.give_coupon_list){
					that.give_coupon_num = 0;
					for(var i in that.give_coupon_list){
						that.give_coupon_num += that.give_coupon_list[i]['givenum'];
					}
				}
				that.detailurl = res.detailurl;
				that.tourl = res.tourl;
        
				that.paypal = res.paypal || 0;
				that.huifu_quickpay = res.huifu_quickpay || 0;
				that.adapay_union = res.adapay_union || 0;
				that.more_alipay = res.more_alipay;
				that.more_alipay_data = res.more_alipay_data || [];
				that.yuanbao_money = res.yuanbao_money;
				that.total_yuanbao = res.total_yuanbao;
				that.yuanbao_msg   = res.yuanbao_msg;
				that.yuanbaopay    = res.yuanbaopay;
				that.wxpay_native_h5 = res.wxpay_native_h5;
				that.wx_liji_pay = res.wx_liji_pay;
				that.xianjinpay = res.xianjinpay || 0;
				that.scorepay = res.scorepay || 0;
				if(that.wxpay){
					if(that.wxpay_type == 22){
						that.typeid = 22;	
					}else{
						that.typeid = 2;
					}
				}else if(that.alipay){
					that.typeid = 3;
					if(that.alipay == 2){
						that.typeid = 23;
					}
				}else if(that.moneypay){
					that.typeid = 1;
				}else if(that.more_alipay){
					that.typeid = that.more_alipay_data[0].typeid;
				}else if(that.baidupay){
					that.typeid = 11;
				}else if(that.toutiaopay){
					that.typeid = 12;
				}else if(that.xiaofeipay){
					that.typeid = 71;
				}else if(that.xianjinpay){
					that.typeid = 1;
				}
				if(that.payorder.money==0 && that.payorder.score>0){
					that.typeid = 1;
				}
				if(that.scorepay==1){
					that.typeid = 1;
				}
				if(res.invite_free){
						that.invite_free = res.invite_free;
				}
				if(res.free_tmplids){
						that.free_tmplids = res.free_tmplids;
				}
				if(res.share_payment){
					that.share_payment = res.share_payment;
				}
				if(res.yunshanfuwxpay){
					that.yunshanfuwxpay = res.yunshanfuwxpay;
				}
				//支付广告位
				if(res.adlist && res.adlist.length>0){
					that.adlist = res.adlist
				}
        if(res.alih5pay){
          if(that.alih5){
            that.wxpay = false
          }
          that.alih5pay = true;
          if(res.alipay_type){
            that.alipay_type = res.alipay_type;
            if(res.alipay_type == 3 && that.alih5){
              const oScript = document.createElement('script');
              oScript.type = 'text/javascript';
              oScript.src = 'https://gw.alipayobjects.com/as/g/h5-lib/alipayjsapi/3.1.1/alipayjsapi.min.js';
              document.body.appendChild(oScript);
            }
          }
          if(res.ali_appid){
            that.ali_appid = res.ali_appid
          }
          if(res.alipayopenid){
            that.alipayopenid   = res.alipayopenid;
          }
					if(res.alipayPlugin){
						that.alipayPlugin = res.alipayPlugin
					}
        }
        
        //余额和微信或支付宝组合支付
        if(res.iscombine && res.iscombine == 1){
          that.iscombine = 1;
          that.changeCombines(that.typeid);
        }
        
        //会员价仅限余额支付
        if(res.moneypay_lvprice_status){
          that.moneypay_lvprice_status = true;
          if(that.payorder.moneypaytypeid && that.payorder.moneypaytypeid>0){
            that.typeid = that.payorder.moneypaytypeid;
          }else{
            if(res.moneypay==1 || res.moneypay==2){
              that.typeid = 1;//默认余额支付
            }
          }
        }

        //判断h5端微信立即支付按钮是否显示
        if(!that.wx_liji_pay && that.typeid == 2 && app.globalData.platform == 'h5'){
          //如果后台配置是关闭并且是微信支付并且是h5端，则隐藏按钮
          that.liji_pay_button = false;
        }
        if(res.sxpay_native) that.sxpay_native = res.sxpay_native;//是否开启随行付聚合支付 0：不开启 1：开启
        if(res.payIntervalStopnum) that.payIntervalStopnum = res.payIntervalStopnum;//定时执行计时停止次数
        
        if(res.countDown){
          that.countDown = res.countDown;
          if (that.countDown > 0) {
          	countDownInterval = setInterval(function () {
          		that.countDown = that.countDown - 1;
          		that.getdjs();
          	}, 1000);
          }
        } 
        that.advertising = res.advertising;//广告
				that.loaded();

				//付款前分享
				if(!app.isEmpty(res.share_product)){
					that.share_product = res.share_product;
					var platform = app.getplatform()
					// #ifdef H5
					if(platform == 'mp'){
						var share = that.share_product
						that._sharemp({
							title:share.sharetitle,
							desc:share.sharedesc,
							link:app.globalData.pre_url + '/h5/' + app.globalData.aid + '.html#/pages/shop/product?id=' + share.id,
							pic:share.sharepic,
							callback:function(){
								that.sharecallback();
							}
						});
					}
					// #endif
				}
        
        //订单赠好友
        if(res.usegiveorder){
          that.usegiveorder  = res.usegiveorder;
          that.giveordertitle= res.giveordertitle;
          that.giveorderpic  = res.giveorderpic;
          that._sharemp({
          	title:that.giveordertitle,
          	desc:that.giveordertitle,
          	link:app.globalData.pre_url + '/h5/' + app.globalData.aid + '.html#/pagesC/shop/takegiveorder?payorderid=' + that.payorder.id,
          	pic:that.giveorderpic,
          });
        }
				
				if(that.opt && that.opt.paypal == 'success'){
					that.typeid = 51;
					app.showLoading('支付中');
          app.post('ApiPay/paypalRedirect', {orderid: that.opt.id,paymentId:that.opt.paymentId,PayerID:that.opt.PayerID}, function (res) {
						app.showLoading(false);
						if(res.status == 1){
							app.success(res.msg);
							that.subscribeMessage(function () {
								if(that.invite_free){
									that.invite_status = true;
								}else{
									setTimeout(function () {
										if (that.give_coupon_list && Object.keys(that.give_coupon_list).length > 0) {
											that.give_coupon_show = true;
											that.give_coupon_close_url = that.tourl;
										} else {
											//uni.navigateBack();
											that.gotourl(that.tourl,'reLaunch');
										}
									}, 1000);
								}
							});
						}else if (res.status == 0){
							app.error(res.msg);
						}
          });
				}
			});
		},
    getpwd: function (done, val) {
			this.paypwd = val;
			this.topay({currentTarget:{dataset:{typeid:1}}});
    },
    changeradio: function (e) {
      var that = this;
      var typeid = e.currentTarget.dataset.typeid;
      //会员价仅限余额支付
      if(that.moneypay_lvprice_status){
        if(typeid == 1){
          that.payorder['money'] = that.payorder['moneyprice'];
        }else{
          that.payorder['money'] = that.payorder['putongprice'];
        }
      }
      that.typeid = typeid;
      if(that.iscombine == 1){
        that.changeCombines(typeid)
      }

      //判断h5端微信立即支付按钮是否显示
      if(!that.wx_liji_pay && that.typeid == 2 && app.globalData.platform == 'h5'){
        //如果后台配置是关闭并且是微信支付并且是h5端，则隐藏按钮
        that.liji_pay_button = false;
      }else {
        that.liji_pay_button = true;
      }
    },
    topay: function (e) {
      console.log(e)
      var that = this;
			//#ifdef MP-WEIXIN || H5
			//付款前分享
			if(that.share_payment == 1 && that.payorder.share_payment_status == 0){
				that.showposter = true;
				return;
			}
			//#endif

      //是否可以提交
      if(that.ispost !=0) return;
      that.ispost = 1;
			that.payResponseStatus = false;
			
      var typeid = that.typeid;
      var orderid = this.payorder.id;
			if(e.currentTarget.dataset.isfrontpay == 1){
				var orderid = this.cod_payorderid;
			}
      
      if (typeid == 1 || typeid == 71 || typeid == 38) { //余额支付
        if(that.userinfo.haspwd && that.paypwd==''){
					that.$refs.dialogInput.open();
          that.ispost = 0;
          return;
        }
				var money_name = that.t('余额');
				if(typeid == 71){
					money_name = that.t('冻结佣金');
				}else if(typeid==38){
					money_name = that.t('信用额度');
				}
				if(that.payorder.money==0 && that.payorder.score>0){
					var money_name = that.t('积分');
				}
				if(that.scorepay==1){
					var money_name = that.t('积分');
				}
        app.confirm('确定用' + money_name + '支付吗?', function () {
          app.showLoading('提交中');
          app.post('ApiPay/pay', {op:'submit',orderid: orderid,is_maidan:that.is_maidan,typeid: typeid,paypwd: that.paypwd,pay_type:that.pay_type,combines:that.combines,scorepay:that.scorepay}, function (res) {
            app.showLoading(false);
            that.ispost = 0;
						if (res.status == 0) {
							that.paypwd = '';
							app.error(res.msg);
							return;
						}
						if (res.status == 2) {
							app.success(res.msg);
							that.subscribeMessage(function () {
									if(that.invite_free){
											that.invite_status = true;
									}else if(that.payorder.ictips){
										that.$refs.dialogInvitecashback.open();
									}else if(that.usegiveorder){
                    that.$refs.dialogGiveorder.open();
                  }else{
											setTimeout(function () {
													if (that.give_coupon_list && Object.keys(that.give_coupon_list).length > 0) {
															that.give_coupon_show = true;
															that.give_coupon_close_url = that.tourl;
													} else {
															that.gotourl(that.tourl,'reLaunch');
													}
											}, 1000);
									}
							});
							return;
						}
          });
        },function(){
          //取消支付操作
          app.post('ApiPay/cancelpay', {orderid: orderid,typeid: typeid}, function (res) {});
          setTimeout(function(){ that.ispost = 0; },1000);
        });
      } else if (typeid == 2) { //微信支付
        console.log('微信支付')
        var wxpaytype = e.currentTarget.dataset.wxpaytype || 0;
        app.showLoading('提交中');
        app.post('ApiPay/pay', {op:'submit',orderid: orderid,is_maidan:that.is_maidan,typeid: typeid,combines:that.combines,wxpay_type:wxpaytype}, function (res) {
          app.showLoading(false);
          that.ispost = 0;
          if (res.status == 0) {
            if (res.msg) {
              if (res.url){
                app.alert(res.msg, function() {
                	if (res.url) app.goto(res.url);
                });
              }else{
                app.error(res.msg);
              }
            } else if (res.url) {
            	app.goto(res.url);
            } else {
            	app.alert('您无查看权限');
            }
            return;
          }else if (res.status == 2) {
            //无需付款
            app.success(res.msg);
            that.subscribeMessage(function () {
                if(that.invite_free){
                    that.invite_status = true;
                }else if(that.payorder.ictips){
										that.$refs.dialogInvitecashback.open();
                }else if(that.usegiveorder){
                  that.$refs.dialogGiveorder.open();
                }else{
                  setTimeout(function () {
                    if (that.give_coupon_list && Object.keys(that.give_coupon_list).length > 0) {
                      that.give_coupon_show = true;
                      that.give_coupon_close_url = that.tourl;
                    } else {
                      that.gotourl(that.tourl,'reLaunch');
                    }
                  }, 1000);
                }
            });
            return;
          }else if (res.status == 3) { //跳转到指定页 可返回
              //弹出提示
              app.alert(res.msg, function() {
                that.ispost = 0;
                if (res.url) {
                  app.goto(res.url);
                }
              });
              return;
					}
          var opt = res.data;
          if(res.data.pay_wx_qrcode_url){
            //微信收款码
            that.pay_wx_qrcode_url = res.data.pay_wx_qrcode_url;
            that.$refs.buildWxNativeH5.open();
            return;
          }
          if (app.globalData.platform == 'wx') {
						// #ifdef MP-WEIXIN
						if(that.wxpay_type == 8){
							//b2b支付
							// #ifdef MP-WEIXIN
							console.log('wxpay b2b');
							wx.requestCommonPayment({
								signData: JSON.stringify(opt.signData),
								paySig: opt.paySig,
								signature: opt.signature,
								mode: 'retail_pay_goods',
								success(res) {
									app.success('付款完成');
									console.log('requestCommonPayment success', res)
									if(that.invite_free){
									    that.invite_status = true;
									}else if(that.payorder.ictips){
									  that.$refs.dialogInvitecashback.open();
									}else if(that.usegiveorder){
                    that.$refs.dialogGiveorder.open();
                  }else{
									    setTimeout(function () {
									        if (that.give_coupon_list && Object.keys(that.give_coupon_list).length > 0) {
									            that.give_coupon_show = true;
									            that.give_coupon_close_url = that.tourl;
									        } else {
									            that.gotourl(that.tourl,'reLaunch');
									        }
									    }, 1000);
									}
									return;
								},
								fail({ errMsg, errno }) {
									app.error(errMsg+' '+errno)
									console.error(errMsg, errno)
									return;
								},
							})
							// #endif
						}
						else if(that.payorder.type == 'shop' || that.wxpay_type == 2){
							//自定义版交易组件的小程序 或 2二级商户模式
							if(opt.orderInfo){
								console.log('requestOrderPayment1');
								wx.requestOrderPayment({
									'timeStamp': opt.timeStamp,
									'nonceStr': opt.nonceStr,
									'package': opt.package,
									'signType': opt.signType ? opt.signType : 'MD5',
									'paySign': opt.paySign,
									'orderInfo':opt.orderInfo,
									'success': function (res2) {
										that.payResponseStatus = true;
										app.success('付款完成');
										that.subscribeMessage(function () {
                      if(that.invite_free){
                          that.invite_status = true;
                      }else if(that.payorder.ictips){
                        that.$refs.dialogInvitecashback.open();
                      }else if(that.usegiveorder){
                        that.$refs.dialogGiveorder.open();
                      }else{
                          setTimeout(function () {
                              if (that.give_coupon_list && Object.keys(that.give_coupon_list).length > 0) {
                                  that.give_coupon_show = true;
                                  that.give_coupon_close_url = that.tourl;
                              } else {
                                  that.gotourl(that.tourl,'reLaunch');
                              }
                          }, 1000);
                      }
										});
									},
									'fail': function (res2) {
										//app.alert(JSON.stringify(res2))
									},
                  'complete':function(res2){
                    if(res2 && res2.errMsg && res2.errMsg == 'requestPayment:fail cancel'){
                      //取消支付操作
                      app.post('ApiPay/cancelpay', {orderid: orderid,typeid: typeid}, function (res3) {});
                    }
                  }
								});
							}else if(opt.sxpay && opt.path){
								//随行付
								uni.openEmbeddedMiniProgram({
									appId: opt.appId,
									path: opt.path,
									extraData: {},
									success(res) {
										console.log('随行付半屏小程序打开');
									}
								})
							}else if(opt.pay_huifu_preorder_embedded && opt.path){
                //汇付斗拱
                uni.openEmbeddedMiniProgram({
                  appId: opt.appId,
                  path: opt.path,
                  extraData: {},
                  success(res) {
                    console.log('汇付斗拱半屏小程序打开');
                  }
                })
              }else if(res.type =='qilin' && res.liteInfo){
								let liteInfo = res.liteInfo;
								uni.openEmbeddedMiniProgram({
									appId: liteInfo.appId,
									path: liteInfo.path,
									extraData: {},
									success(res) {
										console.log('麒麟半屏小程序打开');
									}
								})
							}else{
								console.log('requestOrderPayment2');
								wx.requestOrderPayment({
									'timeStamp': opt.timeStamp,
									'nonceStr': opt.nonceStr,
									'package': opt.package,
									'signType': opt.signType ? opt.signType : 'MD5',
									'paySign': opt.paySign,
									'success': function (res2) {
										that.payResponseStatus = true;
										app.success('付款完成');
										that.subscribeMessage(function () {
                      if(that.invite_free){
													console.log('invite_free');
                          that.invite_status = true;
                      }else if(that.payorder.ictips){
												console.log('ictips',that.payorder.ictips);
                        that.$refs.dialogInvitecashback.open();
                      }else if(that.usegiveorder){
                        that.$refs.dialogGiveorder.open();
                      }else{
                          setTimeout(function () {
                              if (that.give_coupon_list && Object.keys(that.give_coupon_list).length > 0) {
                                  that.give_coupon_show = true;
                                  that.give_coupon_close_url = that.tourl;
                              } else {
																console.log('tourl',that.tourl);
                                  that.gotourl(that.tourl,'reLaunch');
                              }
                          }, 1000);
                      }
										});
									},
									'fail': function (res2) {
										//app.alert(JSON.stringify(res2))
									},
                  'complete':function(res2){
                    if(res2 && res2.errMsg && res2.errMsg == 'requestPayment:fail cancel'){
                      //取消支付操作
                      app.post('ApiPay/cancelpay', {orderid: orderid,typeid: typeid}, function (res3) {});
                    }
                  }
								});
							}
						}
						else if(res.type =='qilin' && res.liteInfo){
							let liteInfo = res.liteInfo;
							uni.openEmbeddedMiniProgram({
								appId: liteInfo.appId,
								path: liteInfo.path,
								extraData: {},
								success(res) {
									console.log('麒麟半屏小程序打开');
								}
							})
						}
						else{
							//普通的微信支付
							if(opt.sxpay && opt.path){
								//随行付
								uni.openEmbeddedMiniProgram({
									appId: opt.appId,
									path: opt.path,
									extraData: {},
									success(res) {
										console.log('随行付半屏小程序打开');
									}
								})
							}else if(opt.pay_huifu_preorder_embedded && opt.path){
                //汇付斗拱
                uni.openEmbeddedMiniProgram({
                  appId: opt.appId,
                  path: opt.path,
                  extraData: {},
                  success(res) {
                    console.log('汇付斗拱半屏小程序打开');
                  }
                })
              }else{
								console.log('wxpay default 默认的微信支付');
								uni.requestPayment({
									'provider':'wxpay',
									'timeStamp': opt.timeStamp,
									'nonceStr': opt.nonceStr,
									'package': opt.package,
									'signType': opt.signType ? opt.signType : 'MD5',
									'paySign': opt.paySign,
									'success': function (res2) {
										that.payResponseStatus = true;
										app.success('付款完成');
										that.subscribeMessage(function () {
										  if(that.invite_free){
													console.log('invite_free');
										      that.invite_status = true;
										  }else if(that.payorder.ictips){
												console.log('ictips',that.payorder.ictips);
										    that.$refs.dialogInvitecashback.open();
										  }else if(that.usegiveorder){
                        that.$refs.dialogGiveorder.open();
                      }else{
										      setTimeout(function () {
										          if (that.give_coupon_list && Object.keys(that.give_coupon_list).length > 0) {
																	console.log('give_coupon_list',that.give_coupon_list);
										              that.give_coupon_show = true;
										              that.give_coupon_close_url = that.tourl;
										          } else {
																console.log('tourl',that.tourl);
										              that.gotourl(that.tourl,'reLaunch');
										          }
										      }, 1000);
										  }
										});
									},
									'fail': function (res2) {
										console.log(res2)
										//app.alert(JSON.stringify(res2))
									},
                  'complete':function(res2){
                    if(res2 && res2.errMsg && res2.errMsg == 'requestPayment:fail cancel'){
                      //取消支付操作
                      app.post('ApiPay/cancelpay', {orderid: orderid,typeid: typeid}, function (res3) {});
                    }
                  }
								});
							}
						}
						// #endif
					}else if(app.globalData.platform == 'mp'){
						// #ifdef H5
						function jsApiCall(){
							WeixinJSBridge.invoke('getBrandWCPayRequest',opt,function(res){
									if(res.err_msg == "get_brand_wcpay_request:ok" ) {
										app.success('付款完成');
										that.subscribeMessage(function () {
                        if(that.invite_free){
                            that.invite_status = true;
                        }else if(that.payorder.ictips){
                          that.$refs.dialogInvitecashback.open();
                        }else if(that.usegiveorder){
                          that.$refs.dialogGiveorder.open();
                        }else{
                            setTimeout(function () {
                                if (that.give_coupon_list && Object.keys(that.give_coupon_list).length > 0) {
                                    that.give_coupon_show = true;
                                    that.give_coupon_close_url = that.tourl;
                                } else {
                                    that.gotourl(that.tourl,'reLaunch');
                                }
                            }, 1000);
                        }
										});
									}else{
                    if(res && res.err_msg && res.err_msg == "get_brand_wcpay_request:cancel"){
                      //取消支付操作
                      app.post('ApiPay/cancelpay', {orderid: orderid,typeid: typeid}, function (res3) {});
                    }
									}
								}
							);
						}
						if (typeof WeixinJSBridge == "undefined"){
							if( document.addEventListener ){
								document.addEventListener('WeixinJSBridgeReady', jsApiCall, false);
							}else if (document.attachEvent){
								document.attachEvent('WeixinJSBridgeReady', jsApiCall); 
								document.attachEvent('onWeixinJSBridgeReady', jsApiCall);
							}
						}else{
							jsApiCall();
						}
						// #endif
					}else if(app.globalData.platform == 'h5'){
						// #ifdef H5
						if(that.wxpay_type == 5 ){//银盛支付
              	location.href = opt.wx_url;
            }else if(that.wxpay_type == 6){//通联支付
              if(res.data.wx_url){
                location.href = res.data.wx_url;
              }else if(res.data.imgurl){
                uni.previewImage({
                	current: res.data.imgurl, 
                	urls: res.data.imgurls 
                })
              }
            }else if(that.wxpay_type == 10){ //麒麟支付
							location.href = res.data.payUrl;
						}else{
              location.href = opt.wx_url + '&redirect_url='+encodeURIComponent(location.href.split('#')[0] + '#'+that.tourl);
            }
						// #endif
					}else if(app.globalData.platform == 'app'){
						console.log(opt)
						// #ifdef APP-PLUS
						if(opt.is_jump && opt.jump_link){
							console.log('跳转'+opt.jump_link);
							app.goto(opt.jump_link);
							return;
						}
						uni.requestPayment({
							'provider':'wxpay',
							'orderInfo': opt,
							'success': function (res2) {
                if(res2 && res2.errCode && res2.errCode == '-2'){
                  return;
                }
								app.success('付款完成');
								that.subscribeMessage(function () {
                  if(that.invite_free){
                      that.invite_status = true;
                  }else if(that.payorder.ictips){
                      that.$refs.dialogInvitecashback.open();
                  }else if(that.usegiveorder){
                    that.$refs.dialogGiveorder.open();
                  }else{
                      setTimeout(function () {
                          if (that.give_coupon_list && Object.keys(that.give_coupon_list).length > 0) {
                              that.give_coupon_show = true;
                              that.give_coupon_close_url = that.tourl;
                          } else {
                              that.gotourl(that.tourl,'reLaunch');
                          }
                      }, 1000);
                  }
								});
							},
							'fail': function (res2) {
								console.log(res2)
								//app.alert(JSON.stringify(res2))
							},
              'complete': function (res2) {
                if(res2 && res2.errCode && res2.errCode == '-2'){
                  //取消支付操作
                  app.post('ApiPay/cancelpay', {orderid: orderid,typeid: typeid}, function (res3) {});
                  return;
                }
              },
						});
						// #endif
					}else if(app.globalData.platform == 'qq'){
						// #ifdef MP-QQ
						qq.requestWxPayment({
							url: opt.wx_url,
							referer: opt.referer,
							success(res) {
								that.subscribeMessage(function () {
                  if(that.invite_free){
                      that.invite_status = true;
                  }else if(that.payorder.ictips){
                      that.$refs.dialogInvitecashback.open();
                  }else if(that.usegiveorder){
                    that.$refs.dialogGiveorder.open();
                  }else{
                      setTimeout(function () {
                          if (that.give_coupon_list && Object.keys(that.give_coupon_list).length > 0) {
                              that.give_coupon_show = true;
                              that.give_coupon_close_url = that.tourl;
                          } else {
                              that.gotourl(that.tourl,'reLaunch');
                          }
                      }, 1000);
                  }
								});
							},
							fail(res) {},
              complete(res) {
                if(res && res.errMsg && res.errMsg == 'requestPayment:fail cancel'){
                  //取消支付操作
                  app.post('ApiPay/cancelpay', {orderid: orderid,typeid: typeid}, function (res3) {});
                }
              },
						})
						// #endif
					}
				})
			} else if (typeid == 3 || (typeid >= 302 && typeid <= 330)) { //支付宝支付
        setTimeout(function(){ that.ispost = 0; },1000);
        if(that.alih5pay && that.alih5 && that.alipay_type == 3){
					console.log('alih5pay')
          if(!that.alipayopenid){
            if(that.ali_appid){
              var ali_appid = that.ali_appid;
              ap.getAuthCode ({
                  appId :  ali_appid ,
                  scopes : ['auth_base'],
              },function(res){
                 //var res = JSON.stringify(res);
                  if(!res.error && res.authCode){
                      app.post('ApiIndex/setalipayopenid', {
                      	code: res.authCode,
                        platform:"h5"
                      }, function(res) {
                        if(res.status == 1){
                          that.alipayopenid = res.openid;
                          
                          that.alih5_pay(orderid,typeid);
                        }else{
                          app.alert(res.msg);
                          return
                        }
                      })
                  }else{
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
          }else{
            that.alih5_pay(orderid,typeid);
          }

        }else{
					console.log('alih5pay2')
          if(app.globalData.platform == 'mp'){
            var gourl = '/pages/index/webView2?orderid='+orderid+'&typeid='+typeid+'&aid=' + app.globalData.aid + '&platform=' + app.globalData.platform + '&session_id=' + app.globalData.session_id;
            if(that.iscombine && that.combines.moneypay == 1){
              gourl += '&moneypay=1';
            }
            app.goto(gourl);
          	return ;
          }
					//支付宝交易组件
					if(that.alipayPlugin && app.globalData.platform == 'alipay'){
						//判断是否需要使用交易组件创建订单
						//#ifdef MP-ALIPAY
						if (my.canIUse('checkBeforeAddOrder')) {
							my.checkBeforeAddOrder({
								success({ requireOrder, sceneId, sourceId }) {
									that.alipaySubmit(orderid,typeid,requireOrder,sourceId)
								}
							})
						}
						//#endif
					}else{
						console.log('alipaySubmit before')
						that.alipaySubmit(orderid,typeid,0);
					}
        }
      } else if (typeid == '11') {
				// #ifdef MP-BAIDU
				app.showLoading('提交中');
				app.post('ApiPay/pay', {op:'submit',orderid: orderid,typeid: typeid}, function (res) {
					app.showLoading(false);
				  that.ispost = 0;
					swan.requestPolymerPayment({
						'orderInfo': res.orderInfo,
						'success': function (res2) {
							app.success('付款完成');
							that.subscribeMessage(function () {
				        if(that.invite_free){
				            that.invite_status = true;
				        }else if(that.payorder.ictips){
				          that.$refs.dialogInvitecashback.open();
				        }else if(that.usegiveorder){
                  that.$refs.dialogGiveorder.open();
                }else{
				            setTimeout(function () {
				                if (that.give_coupon_list && Object.keys(that.give_coupon_list).length > 0) {
				                    that.give_coupon_show = true;
				                    that.give_coupon_close_url = that.tourl;
				                } else {
				                    that.gotourl(that.tourl,'reLaunch');
				                }
				            }, 1000);
				        }
							});
						},
						'fail': function (res2) {
							if(res2.errCode!=2){
								app.alert(JSON.stringify(res2))
							}
						},
				    'complete': function (res2) {
				    	if(res2 && res2.errCode==2){
				    		  //取消支付操作
				    		  app.post('ApiPay/cancelpay', {orderid: orderid,typeid: typeid}, function (res3) {});
				    	}
				    }
					});
				});
				// #endif
      } else if (typeid == '12') {
				// #ifdef MP-TOUTIAO
				app.showLoading('提交中');
				app.post('ApiPay/pay', {op:'submit',orderid: orderid,typeid: typeid}, function (res) {
					app.showLoading(false);
				  that.ispost = 0;
					console.log(res.orderInfo);
					if(res.status == 0){
						app.alert(res.msg)
						return;
					}
					tt.pay({
						'service':5,
						'orderInfo': res.orderInfo,
						'success': function (res2) {
							if (res2.code === 0) {
								app.success('付款完成');
								that.subscribeMessage(function () {
				          if(that.invite_free){
				              that.invite_status = true;
				          }else if(that.payorder.ictips){
				            that.$refs.dialogInvitecashback.open();
				          }else if(that.usegiveorder){
                    that.$refs.dialogGiveorder.open();
                  }else{
				              setTimeout(function () {
				                  if (that.give_coupon_list && Object.keys(that.give_coupon_list).length > 0) {
				                      that.give_coupon_show = true;
				                      that.give_coupon_close_url = that.tourl;
				                  } else {
				                      that.gotourl(that.tourl,'reLaunch');
				                  }
				              }, 1000);
				          }
								});
							}else{
                if(res2 && res2.code==4){
                	  //取消支付操作
                	  app.post('ApiPay/cancelpay', {orderid: orderid,typeid: typeid}, function (res3) {});
                }
              }
						},
						'fail': function (res2) {
							app.alert(JSON.stringify(res2))
						}
					});
				});
				// #endif
			} else if (typeid == '22') {
				if (app.globalData.platform == 'wx') {
					// #ifdef MP-WEIXIN
					wx.login({
						success:function(res){
							if (res.code) {
								app.showLoading('提交中');
								app.post('ApiPay/getYunMpauthParams',{jscode: res.code},function(res){
									app.showLoading(false);
					        that.ispost = 0;
									app.post('https://showmoney.cn/scanpay/fixed/mpauth',res.params,function(res2){
										console.log(res2.sessionKey);
										app.post('ApiPay/getYunUnifiedParams',{orderid: orderid,sessionKey:res2.sessionKey},function(res3){
											app.post('https://showmoney.cn/scanpay/unified',res3.params,function(res4){
												if(res4.respcd == '09'){
													wx.requestPayment({
														timeStamp: res4.timeStamp,
														nonceStr: res4.nonceStr,
														package: res4.package,
														signType: res4.mpSignType,
														paySign: res4.mpSign,
														success: function success(result) {
															app.success('付款完成');
															that.subscribeMessage(function () {
					                        if(that.invite_free){
					                            that.invite_status = true;
					                        }else if(that.payorder.ictips){
					                          that.$refs.dialogInvitecashback.open();
					                        }else if(that.usegiveorder){
                                    that.$refs.dialogGiveorder.open();
                                  }else{
					                            setTimeout(function () {
					                                if (that.give_coupon_list && Object.keys(that.give_coupon_list).length > 0) {
					                                    that.give_coupon_show = true;
					                                    that.give_coupon_close_url = that.tourl;
					                                } else {
					                                    that.gotourl(that.tourl,'reLaunch');
					                                }
					                            }, 1000);
					                        }
															});
														},
														fail: function (res5) {
															//app.alert(JSON.stringify(res5))
														},
                            complete: function (res5){
                              if(res5 && res5.errMsg && res5.errMsg == 'requestPayment:fail cancel'){
                                //取消支付操作
                                app.post('ApiPay/cancelpay', {orderid: orderid,typeid: typeid}, function (res6) {});
                              }
                            },
													});
												}else{
													app.alert(res4.errorDetail);
												}
											})
										})
									})
								})
							} else {
					      that.ispost = 0;
								console.log('登录失败！' + res.errMsg)
							}
						},
					  fail:function(res){ 
					    that.ispost = 0; 
					  }
					});
					// #endif
				}else{
					// #ifndef MP-WEIXIN
					setTimeout(function(){ that.ispost = 0; },1000);
					var url = app.globalData.baseurl + 'ApiPay/pay'+'&aid=' + app.globalData.aid + '&platform=' + app.globalData.platform + '&session_id=' + app.globalData.session_id;
					url += '&op=submit&orderid='+orderid+'&typeid=22';
					location.href = url;
					// #endif
				}
			} else if (typeid == '23') {
				//var url = app.globalData.baseurl + 'ApiPay/pay'+'&aid=' + app.globalData.aid + '&platform=' + app.globalData.platform + '&session_id=' + app.globalData.session_id;
				//url += '&op=submit&orderid='+orderid+'&typeid=23';
				//location.href = url;
				setTimeout(function () {
					that.$refs.dialogPayconfirm.open();
          that.ispost = 0;
				}, 1000);
				app.goto('/pages/index/webView2?orderid='+orderid+'&typeid=23'+'&aid=' + app.globalData.aid + '&platform=' + app.globalData.platform + '&session_id=' + app.globalData.session_id);
				return ;
				// app.showLoading('提交中');
				// app.post('ApiPay/pay',{op:'submit',orderid: orderid,typeid: 23},function(res){
				// 	app.showLoading(false);
    //       that.ispost = 0;
				// 	console.log(res)
				// 	app.goto('url::'+res.url);
				// });
			} else if (typeid == '24') {
				//var url = app.globalData.baseurl + 'ApiPay/pay'+'&aid=' + app.globalData.aid + '&platform=' + app.globalData.platform + '&session_id=' + app.globalData.session_id;
				//url += '&op=submit&orderid='+orderid+'&typeid=23';
				//location.href = url;
        setTimeout(function(){ that.ispost = 0; },1000);
				app.goto('/pages/index/webView2?orderid='+orderid+'&typeid=24');
				return ;
				// app.showLoading('提交中');
				// app.post('ApiPay/pay',{op:'submit',orderid: orderid,typeid: 24},function(res){
				// 	app.showLoading(false);
				// that.ispost = 0;
				// 	console.log(res)
				// 	app.goto('url::'+res.url);
				// });
			}else if (typeid == 'yuanbao') { 
        //元宝支付
        var total_yuanbao = that.total_yuanbao-0;
        var u_yuanbao     = that.userinfo.yuanbao-0;
        if(total_yuanbao>u_yuanbao){
            app.alert(that.t('元宝')+'不足' );
            return;
        }
        that.open_pay = true;
        that.pay_type = 'yuanbao';
        that.ispost   = 0;
			} else if (typeid == '51') {
				app.showLoading('提交中');
				app.post('ApiPay/pay', {op:'submit',orderid: orderid,typeid: typeid}, function (res) {
					app.showLoading(false);
          that.ispost = 0;
					console.log(res);
					if(res.status == 1){
						if(app.globalData.platform == 'app'){
							const wv = plus.webview.create("","custom-webview",{
								top: uni.getWindowInfo().statusBarHeight + 44
							});
							wv.loadURL(res.data)
							var currentWebview = that.$scope.$getAppWebview();
							currentWebview.append(wv);
						}else{
							app.goto('url::'+res.data);
						}
					}else{
						app.alert(res.msg)
					}
				});
			}else if (typeid == '61') {
				app.showLoading('提交中');
				app.post('ApiPay/pay', {op:'submit',orderid: orderid,typeid: typeid}, function (res) {
					app.showLoading(false);
					that.ispost = 0;
					console.log(res);
					if(res.status == 1){
						if(app.globalData.platform == 'app'){
							// #ifdef APP-PLUS
							const wv = plus.webview.create("","custom-webview",{
								top: uni.getWindowInfo().statusBarHeight + 44
							});
							wv.loadURL(res.data)
							var currentWebview = that.$scope.$getAppWebview();
							currentWebview.append(wv);
							// #endif
						}else{
							  const div = document.createElement('div')
							  div.innerHTML = res.data //后台返回接收到的html数据
							  document.body.appendChild(div);
							  document.forms[0].submit();
						}
					}else{
						app.alert(res.msg)
					}
				});
			}else if (typeid == '63') {//汇付快捷支付
				app.post('ApiPay/pay', {op:'submit',orderid: orderid,typeid: typeid}, function (res) {
					app.showLoading(false);
					that.ispost = 0;
					
					if(res.status == 1){
						if(app.globalData.platform == 'mp' || app.globalData.platform == 'h5'){
							window.location.href = res.data.form_url;
						}else if( app.globalData.platform == 'app'){
							var tourl = res.data.form_url;
							console.log(tourl,'tourltourltourltourl');
							uni.navigateTo({
							  url:'/pages/index/webView?url='+ encodeURIComponent(tourl),
							})
						}
						return ;
					}else{
						app.alert(res.msg)
					}
				});
			} else if (typeid == '122' && app.globalData.platform == 'wx'){
        app.post('ApiPay/pay', {op:'submit',orderid: orderid,typeid: typeid}, function (res) {
          if(res.status === 0){
            that.ispost = 0;
            return app.alert(res.msg);
          }
          //检查 appId 和 path 是否有效
          if (!res.data.cqpMpAppId || !res.data.cqpMpPath) {
            return app.alert('小程序信息不完整');
          }
          //跳转到云闪付小程序
          uni.navigateToMiniProgram({
            appId: res.data.cqpMpAppId,
            path: res.data.cqpMpPath,
            success:function(e){
              that.subscribeMessage(function () {
                setTimeout(function () {
                  if (that.give_coupon_list && Object.keys(that.give_coupon_list).length > 0) {
                    that.give_coupon_show = true;
                    that.give_coupon_close_url = that.tourl;
                  } else {
                    that.gotourl(that.tourl,'reLaunch');
                  }
                }, 1000);
              });
              return;
            },
            fail:function(){
              that.ispost = 0;
            }
          })
        })
      }else if (typeid == 'sxpay_native') {//随行付聚合支付
        that.refreshtopay = true;
        that.topaydata = e;
        var alih5 = false;
        if(that.alih5pay && that.alih5){
          alih5 = true;
        }
				app.post('ApiPay/pay', {op:'submit',orderid: orderid,typeid: typeid,alih5:alih5}, function (res) {
					app.showLoading(false);
					that.ispost = 0;
					console.log(res)
					if(res.status == 1){
            var qrcodename = '';
            if(res.data.qrcodename){
              qrcodename = res.data.qrcodename;
            }
            that.qrcodename = qrcodename;
						that.pay_wx_qrcode_url = res.data.qrcode_url;
						that.$refs.buildWxNativeH5.open();
            
            payInterval = setInterval(function () {
            	that.payIntervalnum ++ ;
            	that.checkpay();
            }, 2000);
						return ;
					}else{
						app.alert(res.msg)
					}
				});
			}
		},
		alipaySubmit:function(orderid,typeid,alipayPluginOrder,sourceId){
			//如果开通了交易组件且有权限，则同步创建交易组件订单
			if(!alipayPluginOrder){
				alipayPluginOrder=0
			}
			if(!sourceId){
				sourceId=''
			}
			var that = this;
			app.showLoading('提交中');
			app.post('ApiPay/pay', {op:'submit',orderid: orderid,typeid: typeid,alipayPluginOrder:alipayPluginOrder,sourceId:sourceId,combines:that.combines}, function (res) {
			    console.log(res)
			    app.showLoading(false);
			    if (res.status == 0) {
			      if (res.msg) {
			        if (res.url){
			          app.alert(res.msg, function() {
			          	if (res.url) app.goto(res.url);
			          });
			        }else{
			          app.error(res.msg);
			        }
			      } else if (res.url) {
			      	app.goto(res.url);
			      } else {
			      	app.alert('您无查看权限');
			      }
			      return;
			    }
			    if (res.status == 2) {
			      //无需付款
			      app.success(res.msg);
			      that.subscribeMessage(function () {
			          if(that.invite_free){
			              that.invite_status = true;
			          }else{
			            setTimeout(function () {
			              if (that.give_coupon_list && Object.keys(that.give_coupon_list).length > 0) {
			                that.give_coupon_show = true;
			                that.give_coupon_close_url = that.tourl;
			              } else {
			                that.gotourl(that.tourl,'reLaunch');
			              }
			            }, 1000);
			          }
			      });
			      return;
			    }
			    var opt = res.data;
			  	if (app.globalData.platform == 'alipay') {
						// #ifdef MP-ALIPAY
			  		uni.requestPayment({
			  			'provider':'alipay',
			  			'orderInfo': opt.trade_no,
			  			'success': function (res2) {
			  				console.log(res2)
			  				if(res2.resultCode == '6001'){
			  					return;
			  				}
			  				app.success('付款完成');
			  				that.subscribeMessage(function () {
			            if(that.invite_free){
			                that.invite_status = true;
			            }else{
			                setTimeout(function () {
			                    if (that.give_coupon_list && Object.keys(that.give_coupon_list).length > 0) {
			                        that.give_coupon_show = true;
			                        that.give_coupon_close_url = that.tourl;
			                    } else {
			                        that.gotourl(that.tourl,'reLaunch');
			                    }
			                }, 1000);
			            }
			  				});
			  			},
			  			'fail': function (res2) {
			  				console.log(res2)
			  				app.alert(JSON.stringify(res2))
			  			},
              'complete': function (res2) {
			  				if(res2 && res2.resultCode && res2.resultCode == '6001'){
			  				  //取消支付操作
			  				  app.post('ApiPay/cancelpay', {orderid: orderid,typeid: typeid}, function (res3) {});
			  				}
			  			}
			  		});
						// #endif
			  	}else if(app.globalData.platform == 'mp' || app.globalData.platform == 'h5'){
						if(res.type && res.type=='adapay'){
							 window.location.href = res.data;
						}else if(res.type && res.type=='huifu'){
							 window.location.href = res.data.payurl;
						}else if(res.type && res.type=='allinpay'){
              if(res.data.payurl){
                window.location.href = res.data.payurl;
              }else if(res.data.imgurl){
                uni.previewImage({
                	current: res.data.imgurl, 
                	urls: res.data.imgurls 
                })
              }
            }else if(res.type && res.type=='qilin'){
							window.location.href = res.data.payUrl;
						}else{
							document.body.innerHTML = res.data;
							document.forms['alipaysubmit'].submit();
						}
			  	}else if(app.globalData.platform == 'app'){
						// #ifdef APP-PLUS
            if(opt.is_jump && opt.jump_link){
              console.log('跳转'+opt.wx_url);
              plus.runtime.openURL(opt.wx_url);
              return;
            }
			  		console.log('------------alipay----------')
			  		console.log(opt)
			  		console.log('------------alipay end----------')
			  		uni.requestPayment({
			  			'provider':'alipay',
			  			'orderInfo': opt,
			  			'success': function (res2) {
			  				console.log('------------success----------')
			  				console.log(res2)
                if(res2.resultCode == '6001'){
                	return;
                }
			  				app.success('付款完成');
			  				that.subscribeMessage(function () {
			              if(that.invite_free){
			                  that.invite_status = true;
			              }else{
			                  setTimeout(function () {
			                      if (that.give_coupon_list && Object.keys(that.give_coupon_list).length > 0) {
			                          that.give_coupon_show = true;
			                          that.give_coupon_close_url = that.tourl;
			                      } else {
			                          that.gotourl(that.tourl,'reLaunch');
			                      }
			                  }, 1000);
			              }
			  				});
			  			},
			  			'fail': function (res2) {
			  				console.log(res2)
			  				//app.alert(JSON.stringify(res2))
			  			},
              'complete': function (res2) {
			  				if(res2 && res2.resultCode && res2.resultCode == '6001'){
			  				  //取消支付操作
			  				  app.post('ApiPay/cancelpay', {orderid: orderid,typeid: typeid}, function (res3) {});
			  				}
			  			}
			  		});
						// #endif
			  	}
			  })
		},
		topay2:function(){
			var that = this;
			var orderid = this.payorder.id;
			if(this.cod_frontmoney > 0){
				this.topay({currentTarget:{dataset:{isfrontpay:1}}});
				return;
			}
			app.confirm('确定要' + that.codtxt + '吗?', function () {
				app.showLoading('提交中');
				app.post('ApiPay/pay', {op:'submit',orderid: orderid,typeid: 4}, function (res) {
					app.showLoading(false);
					if (res.status == 0) {
						app.error(res.msg);
						return;
					}
					if (res.status == 2) {
						//无需付款
						app.success(res.msg);
						that.subscribeMessage(function () {
							setTimeout(function () {
								that.gotourl(that.tourl,'reLaunch');
							}, 1000);
						});
						return;
					}
				});
			},function(){
        //取消支付操作
        app.post('ApiPay/cancelpay', {orderid: orderid,typeid: 4}, function (res) {});
      })
		},
		topayMonth:function(){
			var that = this;
			var orderid = this.payorder.id;
			app.confirm('确定要' + that.pay_month_txt + '支付吗?', function () {
				app.showLoading('提交中');
				app.post('ApiPay/pay', {op:'submit',orderid: orderid,typeid: 41}, function (res) {
					app.showLoading(false);
					if (res.status == 0) {
						app.error(res.msg);
						return;
					}
					if (res.status == 2) {
						//无需付款
						app.success(res.msg);
						that.subscribeMessage(function () {
							setTimeout(function () {
								that.gotourl(that.tourl,'reLaunch');
							}, 1000);
						});
						return;
					}
				});
			},function(){
        //取消支付操作
        app.post('ApiPay/cancelpay', {orderid: orderid,typeid: 41}, function (res) {});
      })
		},
		topayTransfer:function(e){
			var that = this;
			var orderid = this.payorder.id;
			app.confirm('确定要' + e.currentTarget.dataset.text + '吗?', function () {
				app.showLoading('提交中');
				app.post('ApiPay/pay', {op:'submit',orderid: orderid,typeid: 5}, function (res) {
					app.showLoading(false);
					
					if (res.status == 1) {
						//需审核付款
						app.success(res.msg);
						setTimeout(function () {
              app.goto(res.gotourl,'reLaunch');
						}, 1000);
						return;
					}else if (res.status == 2) {
						//无需付款
						app.success(res.msg);
						setTimeout(function () {
							that.gotourl('transfer?id='+orderid,'reLaunch');
						}, 1000);
						return;
					}else{
							app.error(res.msg);
							return;
					}
				});
			},function(){
        //取消支付操作
        app.post('ApiPay/cancelpay', {orderid: orderid,typeid: 5}, function (res) {});
      })
		},
		give_coupon_close:function(e){
			var that = this;
			var tourl = e.currentTarget.dataset.url;
			this.give_coupon_show = false;
			that.gotourl(tourl,'reLaunch');
		},
		gotourl:function(tourl, opentype){
			var that = this;
			if(app.globalData.platform == 'mp' || app.globalData.platform == 'h5'){
				if (tourl.indexOf('miniProgram::') === 0) {
					//其他小程序
					tourl = tourl.slice(13);
					var tourlArr = tourl.split('|');
					console.log(tourlArr)
					that.showOpenWeapp();
					return;
				}
				if (tourl.indexOf('/h5zb/client/main') === 0) {
					app.goback();return;
				}
			}
			if(that.is_pingce == 1){
			 that.paysuccessToUrl();
			 return;
			}
			app.goto(tourl, opentype);
		},
		showOpenWeapp:function(){
			this.$refs.dialogOpenWeapp.open();
		},
		paysuccessToUrl:function(){
			//  支付成功后查询订单详情
			app.get('ApiOrder/pingceOrder',{id:this.payorder.orderid},function (res) {
				app.goto(res.url, 'reLaunch');
			})
		},
		closeOpenWeapp:function(){
			this.$refs.dialogOpenWeapp.close();
		},
		PayconfirmFun:function(){
			this.gotourl(this.tourl,'reLaunch');
		},
    PayconfirmFun2:function(){
      var that = this;
      app.post('ApiPay/checkpay', {orderid: that.opt.id}, function (res) {
        if(res.status == 2){
          clearInterval(payInterval);
          that.gotourl(that.tourl,'reLaunch');
        }else if(res.status == 0){
          clearInterval(payInterval);
          app.alert(res.msg);
        }else{
          app.alert('订单未支付完成');
        }
      });
    	
    },
		close_pay:function(){
				var that = this;
				that.open_pay = false;
		},
		closeInvite:function(){
			 var that = this;
			 that.invite_status = false;
			 setTimeout(function () {
					 if (that.give_coupon_list && Object.keys(that.give_coupon_list).length > 0) {
							 that.give_coupon_show = true;
							 that.give_coupon_close_url = that.tourl;
					 } else {
							 that.gotourl(that.tourl,'reLaunch');
					 }
			 }, 1000);
		},
		gotoInvite:function(){
				var that = this;
				var free_tmplids = that.free_tmplids;
				if(free_tmplids && free_tmplids.length > 0){
					uni.requestSubscribeMessage({
						tmplIds: free_tmplids,
						success:function(res) {
							console.log(res)
						},
						fail:function(res){
							console.log(res)
						}
					})
				}
				app.goto('/pagesExt/invite_free/index','reLaunch')
		},
		todaifu:function(e){
			var that = this;
			var platform = app.getplatform()
			var id = that.payorder.id
			if(platform == 'mp' || platform == 'h5'){
				var sharelink = app.globalData.pre_url +'/h5/'+app.globalData.aid+'.html#/pagesExt/pay/daifu?scene=id_'+that.payorder.id;
				this._sharemp({title:"您有一份好友代付待查收，请尽快处理~",link:sharelink,pic:that.sharepic})
				app.error('点击右上角发送给好友或分享到朋友圈');
			}else if(platform == 'app'){
				// #ifdef APP-PLUS
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
							sharedata.title = '您的好友向您发出了代付请求';
							sharedata.summary = '您有一份好友代付待查收，请尽快处理~';
							sharedata.href = app.globalData.pre_url +'/h5/'+app.globalData.aid+'.html#/pagesExt/pay/daifu?scene=id_'+that.payorder.id;
							sharedata.imageUrl = '';
							uni.share(sharedata);
						}
					}
				});
				// #endif
			}else{
				app.error('该终端不支持此操作');
			}
		},
		alih5_pay:function(orderid,typeid){
			// #ifdef H5 || MP-ALIPAY || APP-PLUS
			var that = this;
			app.showLoading('提交中');
			app.post('ApiPay/pay', {op:'submit',orderid: orderid,typeid: typeid,alih5:true,combines:that.combines}, function (res) {
				console.log(res)
				app.showLoading(false);
				if (res.status == 0) {
					app.error(res.msg);
					return;
				}else if (res.status == 2) {
					//无需付款
					app.success(res.msg);
					that.subscribeMessage(function () {
							if(that.invite_free){
									that.invite_status = true;
							}else{
								setTimeout(function () {
									if (that.give_coupon_list && Object.keys(that.give_coupon_list).length > 0) {
										that.give_coupon_show = true;
										that.give_coupon_close_url = that.tourl;
									} else {
										that.gotourl(that.tourl,'reLaunch');
									}
								}, 1000);
							}
					});
					return;
				}
				var opt = res.data;
				ap.tradePay({
					// 调用统一收单交易创建接口（alipay.trade.create），获得返回字段支付宝交易号trade_no
					tradeNO: opt.trade_no,
					success: (res2) => {
						// ap.alert({
						//   content: JSON.stringify(res),
						// });
						console.log(res2)
						if(res2.resultCode == '6001'){
							return;
						}
						app.success('付款完成');
						that.subscribeMessage(function () {
							if(that.invite_free){
									that.invite_status = true;
							}else{
									setTimeout(function () {
											if (that.give_coupon_list && Object.keys(that.give_coupon_list).length > 0) {
													that.give_coupon_show = true;
													that.give_coupon_close_url = that.tourl;
											} else {
													that.gotourl(that.tourl,'reLaunch');
											}
									}, 1000);
							}
						});
					},
					fail: (res2) => {
						// ap.alert({
						//   content: JSON.stringify(res2),
						// });
					},
          complete: function (res2) {
            if(res2 && res2.resultCode && res2.resultCode == '6001'){
              //取消支付操作
              app.post('ApiPay/cancelpay', {orderid: orderid,typeid: typeid}, function (res3) {});
            }
          }
				});
			})
			// #endif
		},
    buildWxNativeH5Close:function(){
      var that = this;
      that.refreshtopay = false;
      that.topaydata = {};
      clearInterval(payInterval);
      that.$refs.buildWxNativeH5.close();
    },
		dialogInvitecashbackClose:function(){
			var that = this;
			that.$refs.dialogInvitecashback.close();
			setTimeout(function () {
					if (that.give_coupon_list && Object.keys(that.give_coupon_list).length > 0) {
							that.give_coupon_show = true;
							that.give_coupon_close_url = that.tourl;
					} else {
							that.gotourl(that.tourl,'reLaunch');
					}
			}, 900);
		},
    changeCombines:function(typeid){
      var that = this;
      //查询是否开启余额组合支付
      if(that.iscombine == 1){
        var combines = that.combines;
        if(typeid == 1){
          if(combines.moneypay == 0){
            combines.moneypay = typeid;
            that.typeid       = combines.wxpay?combines.wxpay:combines.alipay?combines.alipay:1;
          }else{
            combines.moneypay = 0;
            that.typeid       = combines.wxpay?combines.wxpay:combines.alipay?combines.alipay:0;
          }
        }else if(typeid == 2){
          if(combines.wxpay == 0){
            combines.wxpay = typeid;
            combines.alipay= 0;
          }else{
            combines.wxpay = 0;
            combines.alipay= 0;
            that.typeid    = combines.moneypay?combines.moneypay:0;
          }
        }else if(typeid == 3 || (typeid >= 302 && typeid <= 330)){
          if(combines.alipay == 0){
            combines.wxpay  = 0;
            combines.alipay = typeid;
          }else{
            combines.wxpay  = 0;
            combines.alipay = 0;
            that.typeid     = combines.moneypay?combines.moneypay:0;
          }
        }else{
          combines= {'moneypay':0,'wxpay':0,'alipay':0};
        }
        that.combines = combines;
      }
    },
    topay3:function(e){
      var that = this;
      var msg = '确定选择组合支付吗？组合支付将直接扣除'+that.t('余额')+'部分，剩余部分由其他支付方式支付。'
      app.confirm(msg,function(){
        that.topay(e)
      })
    },
		posterDialogClose: function() {
			this.showposter = false;
		},
		sharemp: function() {
			var that = this;
			var platform = app.getplatform()
			if(platform == 'mp'){
				if(!app.isEmpty(that.share_product)){
					var share = that.share_product
					this._sharemp({
						title:share.sharetitle,
						desc:share.sharedesc,
						link:app.globalData.pre_url + '/h5/' + app.globalData.aid + '.html#/pages/shop/product?id=' + share.id,
						pic:share.sharepic,
						callback:function(){
							that.sharecallback();
						}
					});
					app.error('点击右上角发送给好友或分享到朋友圈');
				}
			}
		},
		sharecallback:function(){
			var that = this;
			app.post('ApiPay/sharePaymentStatus', {orderid: that.payorder.id}, function (res) {
				if (res.status == 1) {
					that.showposter = false;
					that.share_payment = 0;
				}
			});
		},
    giveordersharemp:function(){
      if(app.globalData.platform == 'mp'){
        var msg = '复制链接成功，或点击右上角发送给好友';
      }else{
        var msg = '复制成功,快去分享吧';
      }
    	let that = this;
    	let shareLink = app.globalData.pre_url +'/h5/'+app.globalData.aid+'.html#/pagesC/shop/takegiveorder?scene=pid_' + app.globalData.mid+'&payorderid='+that.payorder.id+'&title=领礼物：'+that.giveordertitle;
    	uni.setClipboardData({
    		data: shareLink,
    		success: function() {
    			uni.showToast({
    				title: msg,
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
    },
    giveordershareapp:function(){
      var that = this;
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
    				sharedata.title   = that.giveordertitle;
    				sharedata.summary = '';
    				sharedata.href    = app.globalData.pre_url +'/h5/'+app.globalData.aid+'.html#/pagesC/shop/takegiveorder?scene=pid_' + app.globalData.mid+'&payorderid='+that.payorder.id+'&title=领礼物：'+that.giveordertitle;
    				sharedata.imageUrl= that.giveorderpic;
    				var sharelist = app.globalData.initdata.sharelist;
    				if(sharelist){
    					for(var i=0;i<sharelist.length;i++){
    						if(sharelist[i]['indexurl'] == app.globalData.initdata.indexurl){
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
    dialogGiveorderClose:function(){
    	var that = this;
    	that.$refs.dialogGiveorder.close();
    	setTimeout(function () {
    			if (that.give_coupon_list && Object.keys(that.give_coupon_list).length > 0) {
    					that.give_coupon_show = true;
    					that.give_coupon_close_url = that.tourl;
    			} else {
    					that.gotourl(that.tourl,'reLaunch');
    			}
    	}, 900);
    },
    refreshTopay:function(){
      this.topay(this.topaydata);
    },
    checkpay:function(){
      var that = this;
      //定时执行次数（默认0次）小于等于停止定时次数（默认600次）可请求检查
      if(that.payIntervalnum<=that.payIntervalStopnum){
        app.post('ApiPay/checkpay', {orderid: that.opt.id}, function (res) {
          if(res.status == 2){
            clearInterval(payInterval);
            that.gotourl(that.tourl,'reLaunch');
          }else if(res.status == 0){
            clearInterval(payInterval);
            app.alert(res.msg);
          }
        });
      }else{
        clearInterval(payInterval);
      }
    },
    getdjs: function () {
      var that = this;
      var totalsec = that.countDown;
    
      if (totalsec <= 0) {
        that.djs = '00时00分00秒';
        clearInterval(countDownInterval);
      } else {
        var houer = Math.floor(totalsec / 3600);
        var min = Math.floor((totalsec - houer * 3600) / 60);
        var sec = totalsec - houer * 3600 - min * 60;
        var djs  = houer>0?(houer < 10 ? '0' : '') + houer + '时':'';
            djs += min>0?(min < 10 ? '0' : '') + min + '分':'';
            djs += (sec < 10 ? '0' : '') + sec + '秒';
        that.djs = djs;
      }
    },
	}
}
</script>
<style>
.top{width:100%;display:flex;flex-direction:column;align-items:center;padding-top:60rpx}
.top .f1{height:60rpx;line-height:60rpx;color:#939393;font-size:24rpx;}
.top .f2{color:#101010;font-weight:bold;font-size:72rpx;height:120rpx;line-height:120rpx}
.top .f2 .t1{font-size:44rpx}
.top .f2 .t3{font-size:50rpx}
.top .f3{color:#FC5729;font-size:26rpx;height:70rpx;line-height:70rpx;display: flex;align-items: center;}
.paytype{width:94%;margin:20rpx 3% 80rpx 3%;border-radius:10rpx;display:flex;flex-direction:column;margin-top:20rpx;background:#fff}
.paytype .f1{height:100rpx;line-height:100rpx;padding:0 30rpx;color:#333333;font-weight:bold}

.paytype .f2{padding:0 30rpx}
.paytype .f2 .item{border-bottom:1px solid #f5f5f5;height:100rpx;display:flex;align-items:center}
.paytype .f2 .item:last-child{border-bottom:0}
.paytype .f2 .item .t1{flex:1;display:flex;align-items:center;color:#222222;font-size:30rpx;font-weight:bold}
.paytype .f2 .item .t1 .img{width:44rpx;height:44rpx;margin-right:40rpx}

.paytype .f2 .item .radio{flex-shrink:0;width: 36rpx;height: 36rpx;background: #FFFFFF;border: 3rpx solid #BFBFBF;border-radius: 50%;margin-right:10rpx}
.paytype .f2 .item .radio .radio-img{width:100%;height:100%}

.btn{ height:100rpx;line-height: 100rpx;width:90%;margin:0 auto;border-radius:10rpx;margin-top:30rpx;color: #fff;font-size: 30rpx;font-weight:bold}
.daifu-btn{background: #fc5729;}
.op{width:94%;margin:20rpx 3%;display:flex;align-items:center;margin-top:40rpx}
.op .btn{flex:1;height:100rpx;line-height:100rpx;background:#07C160;width:90%;margin:0 10rpx;border-radius:10rpx;color: #fff;font-size:28rpx;font-weight:bold;display:flex;align-items:center;justify-content:center}
.op .btn .img{width:48rpx;height:48rpx;margin-right:20rpx}
/* 广告位 */
.ad-box{width: 94%; margin: 30rpx 3% 0 3%;background: #FFFFFF;border-radius: 10rpx;padding: 20rpx;}
.ad-item{width: 100%;display: flex;justify-content: center;margin-bottom: 20rpx;border-radius: 10rpx;}
.ad-item image{border-radius: 12rpx;width: 100%;}
.ad-item:last-child{margin-bottom: 0;}
.give-coupon .coupon-coupon .pt_right{padding: 20rpx;}
.give-coupon .coupon-coupon .pt_right .f1 .t2{height: unset;line-height: unset;}
.give-coupon .coupon-coupon .pt_right .f1 .t4{ max-width: 310rpx; overflow: hidden;  text-overflow: ellipsis; white-space: nowrap;}
.uni-popup-dialog {width: 300px;border-radius: 5px;background-color: #fff;}
.uni-dialog-title {display: flex;flex-direction: row;justify-content: center;padding-top: 15px;padding-bottom: 5px;}
.uni-dialog-title-text {font-size: 16px;font-weight: 500;}
.uni-dialog-content {display: flex;flex-direction: row;justify-content: center;align-items: center;padding: 5px 15px 15px 15px;}
.uni-dialog-content-text {font-size: 14px;color: #6e6e6e;}
.uni-dialog-button-group {display: flex;flex-direction: row;border-top-color: #f5f5f5;border-top-style: solid;border-top-width: 1px;}
.uni-dialog-button {display: flex;flex: 1;flex-direction: row;justify-content: center;align-items: center;height: 45px;}
.uni-border-left {border-left-color: #f0f0f0;border-left-style: solid;border-left-width: 1px;}
.uni-dialog-button-text {font-size: 14px;}
.uni-button-color {color: #007aff;}


.posterShare { position: fixed;display: flex; z-index: 99; width: 750rpx; height: 100%; left: 0;top:0;justify-content: center;background: rgba(0,0,0,0.8); } 
.posterButton{width: 240rpx;height: 75rpx;line-height: 74rpx;text-align: center;border-radius: 50rpx;border: 1px solid #fff;color: #fff;font-weight: bold;letter-spacing: 15rpx;}
.uni-popup-dialog .img{width:80%}
</style>