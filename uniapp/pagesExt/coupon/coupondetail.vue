<template>
<view class="container">
	<block v-if="isload">
		<view class="couponbg" :style="{background:'linear-gradient(90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'}"></view>
		<view class="orderinfo" :style="{backgroundColor:coupon.bg_color} ">
			<block v-if="record.id"><!-- 已领取的券查看详情 -->
				<view class="topitem">
					<view class="f1" :style="{color:t('color1')}" v-if="record.type==1"><text style="font-size:32rpx">￥</text><text class="t1">{{record.money}}</text></view>
					<view class="f1" :style="{color:t('color1')}" v-else-if="record.type==10"><text class="t1">{{(record.discount/10).toFixed(2)}}</text><text style="font-size:32rpx">折</text></view>
					<view class="f1" :style="{color:t('color1')}" v-else-if="record.type==2">礼品券</view>
					<view class="f1" :style="{color:t('color1')}" v-else-if="record.type==3"><text class="t1">{{record.limit_count}}</text><text class="t2">次</text></view>
					<view class="f1" :style="{color:t('color1')}" v-else-if="record.type==4">抵运费</view>
					<view class="f1" :style="{color:t('color1')}" v-else-if="record.type==5">餐饮券</view>
					<view class="f1" :style="{color:t('color1')}" v-else-if="record.type==20">券包</view>
					<view class="f2">
						<view class="t1" :style="{color:coupon.title_color}">{{record.couponname}}</view>
						<view class="t2" v-if="record.type==1 || record.type==4 || record.type==5">
							<text :style="{color:coupon.font_color}" v-if="record.minprice>0">满{{record.minprice}}元可用</text>
							<text :style="{color:coupon.font_color}" v-else>无门槛</text>
						</view>
						<!-- 类型 -->
						<view class="t2" :style="{color:coupon.font_color ? coupon.font_color : '#2B2B2B'}" v-if="record.type==1">代金券<text v-if="coupon.isgive == 1 || coupon.isgive == 2">（可赠送）</text></view>
						<view class="t2" :style="{color:coupon.font_color ? coupon.font_color : '#2B2B2B'}" v-if="record.type==10">折扣券<text v-if="coupon.isgive == 1 || coupon.isgive == 2">（可赠送）</text></view>
						<view class="t2" :style="{color:coupon.font_color ? coupon.font_color : '#2B2B2B'}" v-if="record.type==2">礼品券<text v-if="coupon.isgive == 1 || coupon.isgive == 2 ">（可赠送）</text></view>
						<view class="t2" :style="{color:coupon.font_color ? coupon.font_color : '#2B2B2B'}" v-if="record.type==3">计次券<text v-if="coupon.isgive == 1 || coupon.isgive == 2">（可赠送）</text></view>
						<view class="t2" :style="{color:coupon.font_color ? coupon.font_color : '#2B2B2B'}" v-if="record.type==4">运费抵扣券<text v-if="coupon.isgive == 1 || coupon.isgive == 2">（可赠送）</text></view>
						<view class="t2" :style="{color:coupon.font_color ? coupon.font_color : '#2B2B2B'}" v-if="record.type==5">餐饮券<text v-if="coupon.isgive == 1 || coupon.isgive == 2">（可赠送）</text></view>
						<view class="t2" :style="{color:coupon.font_color ? coupon.font_color : '#2B2B2B'}" v-if="record.type==20">券包<text v-if="coupon.isgive == 1 || coupon.isgive == 2">（可赠送）</text></view>
					</view>
				</view>
				<view class="item" v-if="coupon.bid!=0">
					<text class="t1" :style="{color:coupon.title_color}">适用商家</text>
					<text class="t2" :style="{color:coupon.font_color}">{{coupon.bname}}</text>
				</view>
				<view class="item" v-if="record.type==3 && record.status==1" >
					<text class="t1" :style="{color:coupon.title_color}">次数</text>
					<view class="flex-x-start" style="align-items: center;" :style="{color:coupon.title_color}">
            共计<view :style="{color:coupon.font_color}">{{record.limit_count}}</view>次  剩余<view :style="{color:t('color1')}" @tap="goto" :data-url="'/pagesExt/coupon/record?id='+record.id">{{record.surplus_count}}</view>次
            <view v-if="record && record.showHexiaolog" @tap="goto" :data-url="'/pagesD/my/couponHxRecord?id='+record.id" style="border: 2rpx solid #ddd;border-radius: 8rpx;width: 160rpx;margin-left: 20rpx;text-align: center;line-height: 50rpx;">更多记录</view>
          </view>
        </view>
				<!-- 使用范围仅转赠的不显示核销码 -->
				<view class="item flex-col" v-if="record.status==0 && record.hexiaoqr && coupon.isgive!=2 && (!coupon.dk_type )">
					
					<!-- 餐饮券和定制的折扣券存在 手机端和pc收银台，免费券只有pc，其它的只有手机 -->
					<view class="flex flex-bt" v-if="(coupon.type ==5 || coupon.type ==10) && record.cashdesk_hexiaoqr">
						<text class="t1" @click="changeqrcode('nomal')" style="text-align: center;" :style="{color:qrcodetype=='nomal'?'red':''}" >核销码</text>		
						<text class="t1" @click="changeqrcode('cashdesk')" style="text-align: center;" :style="{color:qrcodetype=='cashdesk'?'red':''}" >收银核销码</text>
					</view>
					<!-- 餐饮收银台免费券，只有收银台能使用 -->
					<view class="flex flex-bt" v-else-if="coupon.type ==51">
						<text class="t1" :style="{color:coupon.title_color}" >收银核销码</text>		
					</view>
					<!-- 其他普通券 -->
					<view class="flex flex-bt" v-else>
						<text class="t1" :style="{color:coupon.title_color}" >核销码</text>		
					</view>
					<!-- 餐饮券，手机端和pc收银台，免费券只有pc，其它的只有手机 end -->
					<view style="margin-bottom: 20rpx;" v-if="record.type==3">
						<view class="flex-x-center" :style="{color:coupon.title_color}">共计<view :style="{color:coupon.font_color}">{{record.limit_count}}</view>次  剩余<view :style="{color:t('color1')}" @tap="goto" :data-url="'/pagesExt/coupon/record?crid='+record.id">{{record.surplus_count}}</view>次</view>
					</view>
					<view style="margin-bottom: 20rpx;" v-if="record.packrid && record.num && record.num>0">
						<view class="flex-x-center" :style="{color:coupon.title_color}">共计<view :style="{color:coupon.font_color}">{{record.num}}</view>张  剩余<view :style="{color:t('color1')}" @tap="goto" :data-url="'/pagesExt/coupon/record?crid='+record.id">{{record.num-record.usenum}}</view>张</view>
					</view>
					<view class="flex-x-center" >
						<image :src="hexiaoqr" style="width:250rpx;height:250rpx" @tap="previewImage" :data-url="hexiaoqr"></image>
					</view>
					<!-- 免费券或者是切换为收银台券码时 显示该提示语 -->
					<block v-if="coupon.type == 51 || qrcodetype =='cashdesk'">
						<text class="flex-x-center" :style="{color:coupon.title_color,fontWeight:'700'}" >券码：{{record.code}}</text>
						<text class="flex-x-center" :style="{color:coupon.title_color}">(仅限店内收银台使用)</text>				
					</block>
					<text class="flex-x-center" :style="{color:coupon.title_color}">到店使用时请出示核销码进行核销</text>
          <view v-if="record && record.showHexiaolog" style="display: flex;align-items: center;line-height: 60rpx;margin-top: 20rpx;">
            <view>核销记录：<text style="color: red;font-weight: bold;">{{record.hexiaotime}}</text></view>
            <view @tap="goto" :data-url="'/pagesD/my/couponHxRecord?id='+record.id" style="border: 2rpx solid #ddd;border-radius: 8rpx;width: 160rpx;margin-left: 20rpx;text-align: center;">更多记录</view>
          </view>
				</view>
				<view class="item">
					<text class="t1" :style="{color:coupon.title_color}">领取时间</text>
					<text class="t2" :style="{color:coupon.font_color}">{{record.createtime}}</text>
				</view>
				<block v-if="record.status==1">
				<view class="item">
					<text class="t1" :style="{color:coupon.title_color}">使用时间</text>
					<text class="t2" :style="{color:coupon.font_color}">{{record.usetime}}</text>
				</view>
				</block>
				
				<view v-if="record.type!=20" class="item flex-col">
					<text class="t1" :style="{color:coupon.title_color}">有效期</text>
					<text class="t2" :style="{color:coupon.font_color}">{{record.starttime}} 至 {{record.endtime}}</text>
				</view>
        <view v-if="coupon.coupon_use_interval_time" class="item flex-col">
          <text class="t1" :style="{color:coupon.title_color}">使用间隔时间</text>
          <text class="t2" :style="{color:coupon.font_color}">{{coupon.coupon_use_interval_time}}</text>
        </view>
				<view class="item flex-col" v-if="coupon.pack_coupon_list.length > 0">
					<text class="t1" :style="{color:coupon.title_color}">包含</text>
					<view>
					  <view class="coupon-list">
					  	<view v-for="(item2, index2) in record.pack_coupon_list" :key="item2.id" class="coupon" @tap.stop="goto" :data-url="'coupondetail?id=' + item2.id">
					  		<view class="pt_left">
					  			<view class="pt_left-content">
					  				<view class="f1" :style="{color:t('color1')}" v-if="item2.type==1"><text class="t0">￥</text><text class="t1" :style="{color:t('color1')}">{{item2.money}}</text></view>
					  				<view class="f1" :style="{color:t('color1')}" v-if="item2.type==10"><text class="t1" :style="{color:t('color1')}">{{item2.discount/10}}</text><text class="t2">折</text></view>
					  				<view class="f1" :style="{color:t('color1')}" v-if="item2.type==3"><text class="t1" :style="{color:t('color1')}">{{item2.limit_count}}</text><text class="t2">次</text></view>
					  				<view class="f1" :style="{color:t('color1')}" v-if="item2.type==5"><text class="t0">￥</text><text class="t1" :style="{color:t('color1')}">{{item2.money}}</text></view>
					  				<block v-if="item2.type!=1 && item2.type!=10 && item2.type!=3 && item2.type!=5">
					  					<view class="f1" :style="{color:t('color1')}">{{item2.type_txt}}</view>
					  				</block>
					  				<view class="f2" :style="{color:t('color1')}" v-if="item2.type==1 || item2.type==10 || item2.type==4 || item2.type==5 || item2.type==10">
					  					<text v-if="item2.minprice>0">满{{item2.minprice}}元可用</text>
					  					<text v-else>无门槛</text>
					  				</view>
					  			</view>
					  		</view>
					  		<view class="pt_right" style="position: relative;">
					  			<view class="f1">
					  				<view class="t1">{{item2.couponname}}</view>
					  				<text class="t2" :style="{background:'rgba('+t('color1rgb')+',0.1)',color:t('color1')}">{{item2.type_txt}}</text>
					  				<view class="t3" :style="item2.bid>0?'margin-top:0':'margin-top:10rpx'">有效期至 {{item2.endtime}}</view>
					  				<view class="t4" v-if="item2.bid>0">适用商家：{{item2.bname}}</view>
					  			</view>
                  <view style="position: absolute;top: 40%;right: 10rpx;" :style="{color:t('color1')}">
                    x {{item2.num}} 张
                  </view>
					  		</view>

					  	</view>
					  </view>
					</view>
				</view>
				<view class="item flex-col">
					<text class="t1" :style="{color:coupon.title_color}">使用说明</text>
					<view class="t2" :style="{color:coupon.font_color}">{{coupon.usetips}}</view>
				</view>
			</block>
			<!-- 未领取的券查看详情 -->
			<block v-else>
				<view class="topitem">
					<view class="f1" :style="{color:t('color1')}" v-if="coupon.type==1"><text style="font-size:32rpx">￥</text><text class="t1">{{coupon.money}}</text></view>
					<view class="f1" :style="{color:t('color1')}" v-if="coupon.type==10"><text class="t1">{{coupon.discount/10}}</text><text style="font-size:32rpx">折</text></view>
					<view class="f1" :style="{color:t('color1')}" v-else-if="coupon.type==2">礼品券</view>
					<view class="f1" :style="{color:t('color1')}" v-else-if="coupon.type==3"><text class="t1">{{coupon.limit_count}}</text><text class="t2">次</text></view>
					<view class="f1" :style="{color:t('color1')}" v-else-if="coupon.type==4">抵运费</view>
					<view class="f1" :style="{color:t('color1')}" v-else-if="coupon.type==5">餐饮券</view>
					<view class="f1" :style="{color:t('color1')}" v-else-if="coupon.type==20">券包</view>
					<view class="f2">
						<view class="t1" :style="{color:coupon.title_color}">{{coupon.name}}</view>
						<view class="t2" v-if="coupon.type==1 || coupon.type==4 || coupon.type==5">
							<text :style="{color:coupon.font_color ? coupon.font_color:'#2B2B2B' }"  v-if="coupon.minprice>0">满{{coupon.minprice}}元可用</text>
							<text :style="{color:coupon.font_color ? coupon.font_color:'#2B2B2B' }"  v-else>无门槛</text>
						</view>
						<!-- 类型 -->
						<text class="t2" :style="{color:coupon.font_color ? coupon.font_color : '#2B2B2B'}" v-if="coupon.type==1">代金券</text>
						<text class="t2" :style="{color:coupon.font_color ? coupon.font_color : '#2B2B2B'}" v-if="coupon.type==10">折扣券</text>
						<text class="t2" :style="{color:coupon.font_color ? coupon.font_color : '#2B2B2B'}" v-if="coupon.type==2">礼品券</text>
						<text class="t2" :style="{color:coupon.font_color ? coupon.font_color : '#2B2B2B'}" v-if="coupon.type==3">计次券</text>
						<text class="t2" :style="{color:coupon.font_color ? coupon.font_color : '#2B2B2B'}" v-if="coupon.type==4">运费抵扣券</text>
						<text class="t2" :style="{color:coupon.font_color ? coupon.font_color : '#2B2B2B'}" v-if="coupon.type==5">餐饮券</text>
						<text class="t2" :style="{color:coupon.font_color ? coupon.font_color : '#2B2B2B'}" v-if="coupon.type==20">券包</text>
					</view>
				</view>

				<view class="item" v-if="coupon.bid!=0">
					<text class="t1" :style="{color:coupon.title_color}">适用商家</text>
					<text class="t2" :style="{color:coupon.font_color}">{{coupon.bname}}</text>
				</view>
				<view class="item" v-if="coupon.house_status">
					<text class="t1" :style="{color:coupon.title_color}">领取限制</text>
					<text class="t2" :style="{color:coupon.font_color}">一户仅限一次</text>
				</view>
				<block v-if="coupon.type==3">
				<view class="item">
					<text class="t1" :style="{color:coupon.title_color}">共计次数</text>
					<text class="t2" :style="{color:coupon.font_color}">{{coupon.limit_count}}次</text>
				</view>
				<block v-if="coupon.limit_perday>0">
				<view class="item">
					<text class="t1" :style="{color:coupon.title_color}">每天限制使用</text>
					<text class="t2" :style="{color:coupon.font_color}">{{coupon.limit_perday}}次</text>
				</view>
				</block>
				</block>
				<block v-if="coupon.use_tongzheng==1">
					<view class="item">
						<text class="t1" :style="{color:coupon.title_color}">所需{{t('通证')}}</text>
						<text class="t2" :style="{color:coupon.font_color}">{{coupon.tongzheng}}{{t('通证')}}</text>
					</view>
				</block>
				<block v-if="coupon.price>0">
				<view class="item">
					<text class="t1" :style="{color:coupon.title_color}">所需金额</text>
					<text class="t2" :style="{color:coupon.font_color}">￥{{coupon.price}}</text>
				</view>
				</block>
				<block v-if="coupon.score>0">
				<view class="item">
					<text class="t1" :style="{color:coupon.title_color}">所需{{t('积分')}}</text>
					<text class="t2" :style="{color:coupon.font_color}">{{coupon.score}}{{t('积分')}}</text>
				</view>
				</block>
				<view class="item">
					<text class="t1" :style="{color:coupon.title_color}">活动时间</text>
					<text class="t2" :style="{color:coupon.font_color}">{{coupon.starttime}} ~ {{coupon.endtime}}</text>
				</view>
				<view class="item" v-if="coupon.type != 20">
					<text class="t1" :style="{color:coupon.title_color}">有效期</text>
					<block v-if="coupon.yxqtype==1">
					<text class="t2" :style="{color:coupon.font_color}">{{coupon.yxqtime}}</text>
					</block>
					<block v-else-if="coupon.yxqtype==2">
					<text class="t2" :style="{color:coupon.font_color}">领取后{{coupon.yxqdate}}天内有效</text>
					</block>
					<block v-else-if="coupon.yxqtype==3">
					<text class="t2" :style="{color:coupon.font_color}">领取后{{coupon.yxqdate}}天内有效（次日0点生效）</text>
					</block>
				</view>
				<view class="item" v-if="coupon.pack_coupon_list.length > 0">
					<text class="t1" :style="{color:coupon.title_color}">包含</text>
					<view>
            <view class="coupon-list">
            	<view v-for="(item2, index2) in coupon.pack_coupon_list" :key="item2.id" class="coupon" @tap.stop="goto" :data-url="'coupondetail?id=' + item2.id">
            		<view class="pt_left">
            			<view class="pt_left-content">
            				<view class="f1" :style="{color:t('color1')}" v-if="item2.type==1"><text class="t0">￥</text><text class="t1" :style="{color:t('color1')}">{{item2.money}}</text></view>
            				<view class="f1" :style="{color:t('color1')}" v-if="item2.type==10"><text class="t1" :style="{color:t('color1')}">{{item2.discount/10}}</text><text class="t2">折</text></view>
            				<view class="f1" :style="{color:t('color1')}" v-if="item2.type==3"><text class="t1" :style="{color:t('color1')}">{{item2.limit_count}}</text><text class="t2">次</text></view>
            				<view class="f1" :style="{color:t('color1')}" v-if="item2.type==5"><text class="t0">￥</text><text class="t1" :style="{color:t('color1')}">{{item2.money}}</text></view>
            				<block v-if="item2.type!=1 && item2.type!=10 && item2.type!=3 && item2.type!=5">
            					<view class="f1" :style="{color:t('color1')}">{{item2.type_txt}}</view>
            				</block>
            				<view class="f2" :style="{color:t('color1')}" v-if="item2.type==1 || item2.type==10 || item2.type==4 || item2.type==5 || item2.type==10">
            					<text v-if="item2.minprice>0">满{{item2.minprice}}元可用</text>
            					<text v-else>无门槛</text>
            				</view>
            			</view>
            		</view>
            		<view class="pt_right" style="position: relative;">
                  <view class="f1">
                    <view class="t1">{{item2.name}}</view>
                    <text class="t2" :style="{background:'rgba('+t('color1rgb')+',0.1)',color:t('color1')}">{{item2.type_txt}}</text>
                    <view class="t4" v-if="item2.house_status">一户仅限一次</view>
                    <view class="t3" :style="item2.bid>0?'margin-top:0':'margin-top:10rpx'">有效期至 {{item2.yxqdate}}</view>
                    <view class="t4" v-if="item2.bid>0">适用商家：{{item2.bname}}</view>
                  </view>
                  <button class="btn" v-if="item2.stock<=0" style="background:#9d9d9d">已抢光了</button>
                  <block v-else-if="item2.is_birthday_coupon==1 && item2.birthday_coupon_status > 0">
                    <button class="btn" v-if="item2.birthday_coupon_status==1" style="background:#9d9d9d">不可领取</button>
                    <button class="btn" v-else :style="{background:'linear-gradient(270deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'}"  @tap.stop="goto" :data-url="'/pagesExt/my/setbirthday'"   :data-key="index">设置生日</button>
                  </block>

                  <view style="position: absolute;top: 40%;right: 10rpx;" :style="{color:t('color1')}">
                    x {{item2.packnum}} 张
                  </view>
            		</view>
            	</view>
            </view>
          </view>
				</view>
        <view v-if="coupon.coupon_use_interval_time" class="item flex-col">
          <text class="t1" :style="{color:coupon.title_color}">使用间隔时间</text>
          <text class="t2" :style="{color:coupon.font_color}">{{coupon.coupon_use_interval_time}}</text>
        </view>
				<view class="item">
					<text class="t1" :style="{color:coupon.title_color}">使用说明</text>
					<view class="t2" :style="{color:coupon.font_color}">{{coupon.usetips}}</view>
				</view>
			</block>
		</view>
		<bloack v-if="coupon.use_tongzheng==1">
			<view v-if="!record.id" class="btn-add" :style="{background:'linear-gradient(90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'}" @tap="getcouponbytongzheng" :data-id="coupon.id" :data-price="coupon.price" :data-tongzheng="coupon.tongzheng">立即兑换</view>
		</bloack>
		<bloack v-if="coupon.is_birthday_coupon==1 && coupon.birthday_coupon_status > 0">
		
			<view v-if="coupon.birthday_coupon_status==3" class="btn-add" :style="{background:'linear-gradient(90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'}" @tap.stop="goto" :data-url="'/pagesExt/my/setbirthday'">设置生日</view>
		</bloack>	
		<block v-else>
		<view v-if="!record.id" class="btn-add" :style="{background:'linear-gradient(90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'}" @tap="getcoupon" :data-id="coupon.id" :data-price="coupon.price" :data-score="coupon.score">{{coupon.price>0?'立即购买':(coupon.score>0?'立即兑换':'立即领取')}}</view>
		</block>
		<block v-if="mid == record.mid">
			<!-- 自用+转赠 -->
			<block v-if="coupon.isgive != 2">
				<block v-if="record.id && (coupon.type==1 || coupon.type==10) && record.status==0">
					<view class="btn-add" :style="{background:'linear-gradient(90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'}" v-if="inArray(coupon.fwtype,[0,1,2])" @tap.stop="goto" :data-url="'/pages/shop/prolist?cpid='+record.couponid+(coupon.bid?'&bid='+coupon.bid:'')">去使用</view>
					<view class="btn-add" :style="{background:'linear-gradient(90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'}" v-if="coupon.fwtype == 4" @tap.stop="goto" :data-url="'/activity/yuyue/prolist?cpid=' + record.couponid+(coupon.bid?'&bid='+coupon.bid:'')">去使用</view>
				</block>
				<view class="btn-add" :style="{background:'linear-gradient(90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'}" v-if="record.id && coupon.type==3 && record.status==0 && record.yuyue_proid > 0" @tap.stop="goto" :data-url="'/activity/yuyue/product?id=' + record.yuyue_proid">去预约</view>
				<view class="btn-add" :style="{background:'linear-gradient(90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'}" v-if="record.id && coupon.type==20 && record.status==0" @tap.stop="getcouponpack" :data-id="coupon.id">使用</view>
			</block>
			<block v-if="record.id && record.status==0 && !record.from_mid && (coupon.isgive == 1 || coupon.isgive == 2)">
        <button v-if="coupon_transfer" class="btn-add"  @tap="couponTransferOpen" style="margin-bottom: 20rpx;background-color: #fff;" :style="{border:'2rpx solid '+t('color2'),color:t('color2')}" >直接转增</button>
				<view class="btn-add" @tap="shareapp" v-if="getplatform() == 'app'" :style="{background:'linear-gradient(90deg,'+t('color2')+' 0%,rgba('+t('color2rgb')+',0.8) 100%)'}" :data-id="record.id">转赠好友</view>
				<view class="btn-add" @tap="sharemp" v-else-if="getplatform() == 'mp'" :style="{background:'linear-gradient(90deg,'+t('color2')+' 0%,rgba('+t('color2rgb')+',0.8) 100%)'}" :data-id="record.id">转赠好友</view>
				<view class="btn-add" @tap="sharemp" v-else-if="getplatform() == 'h5'" :style="{background:'linear-gradient(90deg,'+t('color2')+' 0%,rgba('+t('color2rgb')+',0.8) 100%)'}" :data-id="record.id">转赠好友</view>
				<button class="btn-add" open-type="share" v-else :style="{background:'linear-gradient(90deg,'+t('color2')+' 0%,rgba('+t('color2rgb')+',0.8) 100%)'}" :data-id="record.id">转赠好友</button>
			</block>
		</block>
		<block v-else>
			<view v-if="(coupon.isgive == 1 || coupon.isgive == 2) && opt.pid == record.mid && opt.pid > 0" class="btn-add" @tap="receiveCoupon" :style="{background:'linear-gradient(90deg,'+t('color2')+' 0%,rgba('+t('color2rgb')+',0.8) 100%)'}" :data-id="record.id">立即领取</view>
		</block>
		
		<view class='text-center' @tap="goto" data-url='/pages/index/index' style="margin-top: 40rpx; line-height: 60rpx;"><text>返回首页</text></view>
    
    <uni-popup id="couponTransfer" ref="couponTransfer" type="dialog">
      <view class="uni-popup-dialog">
        <view class="uni-dialog-title">
          <text class="uni-dialog-title-text">直接转增</text>
        </view>
        <view class="uni-dialog-content">
          <view style="padding: 0 20rpx;width: 100%;line-height: 60rpx;">
            <view style="margin-bottom: 20rpx;display: flex;">
              对方的ID：<input @input="transferInput" @blur="changeQuery" data-field='transfermid' class="input" type="number" :value="transfermid" placeholder="请输入对方ID" placeholder-style="color:#999;font-size:32rpx;height:60rpx;line-height: 60rpx;" style="border-bottom: 2rpx solid #f1f1f1;width: 460rpx;height:60rpx;line-height: 60rpx;"></input>
            </view>
            <view style="margin-bottom: 20rpx;display: flex;">
              或手机号：<input @input="transferInput" @blur="changeQuery" data-field='transfertel' class="input" type="number" :value="transfertel" placeholder="请输入对方手机号" placeholder-style="color:#999;font-size:32rpx;height:60rpx;line-height: 60rpx;" style="border-bottom: 2rpx solid #f1f1f1;width: 460rpx;height:60rpx;line-height: 60rpx;"></input>
            </view>
            <view style="margin-bottom: 20rpx;display: flex;">
              <view>对方详情：</view>
              <view v-if="transfer_memberinfo.id" style="display: flex;">
                <image class="head-img" :src="transfer_memberinfo.headimg" v-if='transfer_memberinfo.headimg'></image>
                <image class="head-img" :src="pre_url+'/static/img/wxtx.png'" v-else></image>
                <view class="member-text-view">
                	<view class="member-nickname">{{transfer_memberinfo.nickname}}</view>
                	<view class="member-id">ID：{{transfer_memberinfo.id}}</view>
                </view>
              </view>
              <view v-else>无</view>
            </view>
            <view style="margin-bottom: 20rpx;display: flex;">
              赠送数量：<input @input="transferInput" @blur="changeQuery" data-field='transfernum' class="input" type="number" :value="transfernum" placeholder="请输入赠送数量" placeholder-style="color:#999;font-size:32rpx;height:60rpx;line-height: 60rpx;" style="border-bottom: 2rpx solid #f1f1f1;width: 460rpx;height:60rpx;line-height: 60rpx;"></input>
            </view>
            <view style="margin-bottom: 20rpx;display: flex;">
              同类型可转增的{{t('优惠券')}}总数量：{{transfercannum}}张
            </view>
          </view>
        </view>
        <view class="uni-dialog-button-group">
          <view class="uni-dialog-button" @tap="couponTransferClose">
            <text class="uni-dialog-button-text">取消</text>
          </view>
          <view class="uni-dialog-button" :style="{color:t('color1')}" @tap="couponTransferConfirm">
            <text class="uni-dialog-button-text">确定</text>
          </view>
        </view>
      </view>
    </uni-popup>
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

			textset:{},
			record:{},
			coupon:{},
			shareTitle:'',
			sharePic:'',
			shareDesc:'',
			shareLink:'',
			mid:0,
			qrcodetype:'nomal',
			hexiaoqr:'',//二维码链接
      
      coupon_transfer:0,//是否可以直接转增用户
      transfer_memberinfo:{},//转增用户信息
      transfermid:'',//转增用户ID
      transfertel:'',//转增用户手机号
      transfernum:1,//转增数量
      transfercannum:1,//同种可转增的优惠券数量
		}
  },

  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.getdata();
  },
	onPullDownRefresh: function () {
		this.getdata();
	},
	onShareAppMessage:function(){
		return this._sharewx({title:this.shareTitle,pic:this.sharePic,desc:this.shareDesc,link:this.shareLink});
	},
	onShareTimeline:function(){
		var sharewxdata = this._sharewx({title:this.shareTitle,pic:this.sharePic,desc:this.shareDesc,link:this.shareLink});
		var query = (sharewxdata.path).split('?')[1]+'&seetype=circle';
		console.log(sharewxdata)
		console.log(query)
		return {
			title: sharewxdata.title,
			imageUrl: sharewxdata.imageUrl,
			query: query
		}
	},
  methods: {
		getdata: function () {
			var that = this;
			that.loading = true;
			app.get('ApiCoupon/coupondetail', {rid: that.opt.rid,id: that.opt.id}, function (res) {
				that.loading = false;
				that.textset = app.globalData.textset;
				uni.setNavigationBarTitle({
					title: that.t('优惠券') + '详情'
				});
				if(res.status == 0) {
						app.error(res.msg);return;
				}
				if(!res.coupon.id) {
					app.alert(that.t('优惠券')+'不存在');return;
				}
				that.mid = app.globalData.mid;
				that.record = res.record;
				that.coupon = res.coupon;
        
        that.coupon_transfer = res.coupon_transfer || 0;//是否可以直接转增用户
        that.transfercannum  = res.transfercannum || 0;//同种可转增的优惠券数量
        if(that.transfercannum<=0){
          that.coupon_transfer = that.transfernum = 0;
        }

				that.shareTitle = '送你一张'+that.t('优惠券')+'，点击领取';
				that.shareDesc = that.coupon.name;
				that.sharePic = app.globalData.initdata.logo;
				that.shareLink = app.globalData.pre_url +'/h5/'+app.globalData.aid+'.html#/pagesExt/coupon/coupondetail?scene=id_'+that.coupon.id+'-pid_' + app.globalData.mid+'-rid_' + that.record.id;
				that.hexiaoqr = that.record.hexiaoqr;
				if(that.record.type==51){
					//免费券只显示收银台码
					that.hexiaoqr = that.record.cashdesk_hexiaoqr;
				}
				that.loaded({title:that.shareTitle,pic:that.sharePic,desc:that.shareDesc,link:that.shareLink});
			});
		},

		getcoupon:function(e){
			var that = this;
			var couponinfo = that.coupon;
			if (app.globalData.platform == 'wx' && couponinfo.rewardedvideoad && wx.createRewardedVideoAd) {
				app.showLoading();
				if(!app.globalData.rewardedVideoAd[couponinfo.rewardedvideoad]){
					app.globalData.rewardedVideoAd[couponinfo.rewardedvideoad] = wx.createRewardedVideoAd({ adUnitId: couponinfo.rewardedvideoad});
				}
				var rewardedVideoAd = app.globalData.rewardedVideoAd[couponinfo.rewardedvideoad];
				rewardedVideoAd.load().then(() => {app.showLoading(false);rewardedVideoAd.show();}).catch(err => { app.alert('加载失败');});
				rewardedVideoAd.onError((err) => {
					app.showLoading(false);
					app.alert(err.errMsg);
					console.log('onError event emit', err)
					rewardedVideoAd.offLoad()
					rewardedVideoAd.offClose();
				});
				rewardedVideoAd.onClose(res => {
					app.globalData.rewardedVideoAd[couponinfo.rewardedvideoad] = null;
					if (res && res.isEnded) {
						//app.alert('播放结束 发放奖励');
						that.getcouponconfirm(e);
					} else {
						console.log('播放中途退出，不发奖励');
					}
					rewardedVideoAd.offLoad()
					rewardedVideoAd.offClose();
				});
			}else{
				that.getcouponconfirm(e);
			}
		},
    getcouponconfirm: function (e) {
			var that = this;
			var datalist = that.datalist;
			var id = e.currentTarget.dataset.id;
			var score = parseInt(e.currentTarget.dataset.score);
			var price = e.currentTarget.dataset.price;

			if (price > 0) {
				app.post('ApiCoupon/buycoupon', {id: id}, function (res) {
					if(res.status == 0) {
							app.error(res.msg);
					} else {
						app.goto('/pagesExt/pay/pay?id=' + res.payorderid);
					}
				})
				return;
			}
			var key = e.currentTarget.dataset.key;
			if (score > 0) {
				app.confirm('确定要消耗' + score + '' + that.t('积分') + '兑换吗?', function () {
					app.showLoading('兑换中');
					app.post('ApiCoupon/getcoupon', {id: id}, function (data) {
						app.showLoading(false);
						if (data.status == 0) {
							app.error(data.msg);
						} else {
							app.success(data.msg);
							setTimeout(function(){
								app.goto('mycoupon');
							},1000)
						}
					});
				});
			} else {
				app.showLoading('领取中');
				app.post('ApiCoupon/getcoupon', {id: id}, function (data) {
					app.showLoading(false);
					if (data.status == 0) {
						app.error(data.msg);
					} else {
						app.success(data.msg);
						setTimeout(function(){
							app.goto('mycoupon');
						},1000)
					}
				});
			}
    },
		getcouponpack: function (e) {
			var that = this;
			var id = e.currentTarget.dataset.id;
			app.confirm('确定要使用吗?使用后领取卡券到账户，不可转赠', function () {
				app.showLoading('领取中');
				app.post('ApiCoupon/getcouponpack', {id: id}, function (data) {
					app.showLoading(false);
					if (data.status == 0) {
						app.error(data.msg);
					} else {
						app.success(data.msg);
						setTimeout(function(){
							app.goto('mycoupon');
						},1000)
					}
				});
			});
    },
		receiveCoupon:function(e){
			var that = this;
			var couponinfo = that.coupon;
			if (app.globalData.platform == 'wx' && couponinfo.rewardedvideoad && wx.createRewardedVideoAd) {
				app.showLoading();
				if(!app.globalData.rewardedVideoAd[couponinfo.rewardedvideoad]){
					app.globalData.rewardedVideoAd[couponinfo.rewardedvideoad] = wx.createRewardedVideoAd({ adUnitId: couponinfo.rewardedvideoad});
				}
				var rewardedVideoAd = app.globalData.rewardedVideoAd[couponinfo.rewardedvideoad];
				rewardedVideoAd.load().then(() => {app.showLoading(false);rewardedVideoAd.show();}).catch(err => { app.alert('加载失败');});
				rewardedVideoAd.onError((err) => {
					app.showLoading(false);
					app.alert(err.errMsg);
					console.log('onError event emit', err)
					rewardedVideoAd.offLoad()
					rewardedVideoAd.offClose();
				});
				rewardedVideoAd.onClose(res => {
					app.globalData.rewardedVideoAd[couponinfo.rewardedvideoad] = null;
					if (res && res.isEnded) {
						//app.alert('播放结束 发放奖励');
						that.receiveCouponConfirm(e);
					} else {
						console.log('播放中途退出，不下发奖励');
					}
					rewardedVideoAd.offLoad()
					rewardedVideoAd.offClose();
				});
			}else{
				that.receiveCouponConfirm(e);
			}
		},
		receiveCouponConfirm:function(e){
			var that = this;
			var datalist = that.datalist;
			var rid = that.record.id;
			var id = that.coupon.id;
			app.showLoading('领取中');
			app.post('ApiCoupon/receiveCoupon', {id: id,rid:rid}, function (data) {
				app.showLoading(false);
				if (data.status == 0) {
					app.error(data.msg);
				} else {
					app.success(data.msg);
					that.getdata();
				}
			});
		},
		
		sharemp:function(){
			// app.error('点击右上角发送给好友或分享到朋友圈');
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
						sharedata.title = '送你一张优惠券，点击领取';
						sharedata.summary = that.shareDesc;
						sharedata.href = that.shareLink;
						sharedata.imageUrl = that.sharePic;
						
						uni.share(sharedata);
					}
		    }
		  });
		},
		changeqrcode(type){
			var that = this;
			that.qrcodetype = type
			var hexiaoqr = that.record.hexiaoqr;
			if(type =='cashdesk'){
				hexiaoqr = that.record.cashdesk_hexiaoqr;
			}else{
				hexiaoqr = that.record.hexiaoqr;
			}
			that.hexiaoqr = hexiaoqr;
		},
		getcouponbytongzheng: function (e) {
			var that = this;
			var datalist = that.datalist;
			var id = e.currentTarget.dataset.id;
			var tongzheng = parseInt(e.currentTarget.dataset.tongzheng);
			var price = e.currentTarget.dataset.price;
			var key = e.currentTarget.dataset.key;
			if (tongzheng > 0) {
				app.confirm('确定要消耗' + tongzheng + '' + that.t('通证') + '兑换吗?', function () {
					app.showLoading('兑换中');
					app.post('ApiCoupon/getcoupon', {id: id}, function (data) {
						app.showLoading(false);
						if (data.status == 0) {
							app.error(data.msg);
						} else {
							app.success(data.msg);
							setTimeout(function(){
								app.goto('mycoupon');
							},1000)
						}
					});
				});
			} else {
				app.showLoading('领取中');
				app.post('ApiCoupon/getcoupon', {id: id}, function (data) {
					app.showLoading(false);
					if (data.status == 0) {
						app.error(data.msg);
					} else {
						app.success(data.msg);
						setTimeout(function(){
							app.goto('mycoupon');
						},1000)
					}
				});
			}
		},
    couponTransferOpen:function(){
      this.$refs.couponTransfer.open();
    },
    couponTransferConfirm:function(){
      var that = this;
      if(that.transfernum<=0){
        app.error('请填写要赠送的数量');return;
      }
      app.confirm('确定直接转增给此用户吗？',function(){
        app.showLoading();
        app.post('ApiCoupon/couponTransfer', {mid:that.transfer_memberinfo.id,id:that.opt.rid,type:'one',transfernum:that.transfernum}, function (data) {
          app.showLoading(false);
          if (data.status == 1) {
            app.success(data.msg);
            setTimeout(function () {
              that.$refs.couponTransfer.close();
              app.goback();
            }, 1000);
          }else {
            app.error(data.msg);
            return;
          }
        }, '提交中');
      })
    
    },
    changeQuery(){
    	let that = this;
    	if(!that.transfermid && !that.transfertel){
        that.transfer_memberinfo = {};
        return app.error('请输入对方ID或对方手机号');
      }
      if(!that.transfernum || that.transfernum<=0){
        return app.error('请输入转增数量');
      }
    	that.loading = true
    	app.get('ApiMy/getMemberBase',{mid:that.transfermid,tel:that.transfertel},function (res) {
    		that.loading = false
    		if(res.status == 1){
    			that.transfer_memberinfo = res.data;
    		}else{
          that.transfer_memberinfo = {};
    			app.error('未查询到此会员！');
    		}
    	});
    },
    couponTransferClose:function(){
    	this.$refs.couponTransfer.close();
    },
    transferInput(e){
      var that = this;
      var field  = e.currentTarget.dataset.field;
    	that[field]= e.detail.value;
    },
  }
};
</script>
<style>
.container{display:flex;flex-direction:column; padding-bottom: 30rpx;}
.couponbg{width:100%;height:500rpx;}
.orderinfo{ width:94%;margin:-400rpx 3% 20rpx 3%;border-radius:8px;padding:14rpx 3%;background: #FFF;color:#333;}
.orderinfo .topitem{display:flex;padding:24rpx 40rpx;align-items:center;border-bottom:2px dashed #E5E5E5;position:relative}
.orderinfo .topitem .f1{font-size:50rpx;font-weight:bold;white-space: nowrap;}
.orderinfo .topitem .f1 .t1{font-size:60rpx;}
.orderinfo .topitem .f1 .t2{font-size:40rpx;}
.orderinfo .topitem .f2{margin-left:40rpx}
.orderinfo .topitem .f2 .t1{font-size:36rpx;color:#2B2B2B;font-weight:bold;word-break: break-all;}
.orderinfo .topitem .f2 .t2{font-size:24rpx;color:#999999;height:50rpx;line-height:50rpx}
.orderinfo .item{display:flex;flex-direction:column;width:100%;padding:0 40rpx;margin-top:38rpx}
.orderinfo .item:last-child{ border-bottom: 0;}
.orderinfo .item .t1{width:200rpx;color:#2B2B2B;font-weight:bold;font-size:30rpx;height:60rpx;line-height:60rpx}
.orderinfo .item .t2{font-size:28rpx;height:auto;line-height:40rpx;white-space:pre-wrap;}
.orderinfo .item .red{color:red}
.flex-x-start{display: flex;justify-content: flex-start;}
.text-center { text-align: center;}
.btn-add{width:90%;margin:30rpx 5%;height:96rpx; line-height:96rpx; text-align:center;color: #fff;font-size:30rpx;font-weight:bold;border-radius:48rpx;}

.coupon-list{width:100%;padding:20rpx 0}
.coupon{width:100%;display:flex;margin-bottom:20rpx;border-radius:10rpx;overflow:hidden;border: 2rpx solid #f1f1f1;}
.coupon .pt_left{background: #fff;min-height:200rpx;color: #FFF;width:30%;display:flex;flex-direction:column;align-items:center;justify-content:center}
.coupon .pt_left-content{width:100%;height:100%;margin:30rpx 0;border-right:1px solid #EEEEEE;display:flex;flex-direction:column;align-items:center;justify-content:center}
.coupon .pt_left .f1{font-size:40rpx;font-weight:bold;text-align:center;}
.coupon .pt_left .t0{padding-right:0;}
.coupon .pt_left .t1{font-size:60rpx;}
.coupon .pt_left .t2{padding-left:10rpx;}
.coupon .pt_left .f2{font-size:20rpx;color:#4E535B;text-align:center;}
.coupon .pt_right{background: #fff;width:70%;display:flex;min-height:200rpx;text-align: left;padding:20rpx 20rpx;position:relative}
.coupon .pt_right .f1{flex-grow: 1;flex-shrink: 1;}
.coupon .pt_right .f1 .t1{font-size:28rpx;color:#2B2B2B;font-weight:bold;height:60rpx;line-height:60rpx;overflow:hidden}
.coupon .pt_right .f1 .t2{height:36rpx;line-height:36rpx;font-size:20rpx;font-weight:bold;padding:0 16rpx;border-radius:4rpx}
.coupon .pt_right .f1 .t3{font-size:20rpx;color:#999999;height:46rpx;line-height:46rpx;}
.coupon .pt_right .f1 .t4{font-size:20rpx;color:#999999;height:46rpx;line-height:46rpx;max-width: 76%;text-overflow: ellipsis;overflow: hidden;white-space: nowrap;}
.coupon .pt_right .btn{position:absolute;right:16rpx;top:49%;margin-top:-28rpx;border-radius:28rpx;width:150rpx;height:56rpx;line-height:56rpx;color:#fff}
.coupon .pt_right .sygq{position:absolute;right:30rpx;top:50%;margin-top:-50rpx;width:100rpx;height:100rpx;}

.uni-popup-dialog {width: 720rpx;border-radius: 10rpx;background-color: #fff;}
.uni-dialog-title {display: flex;flex-direction: row;justify-content: center;padding-top: 30rpx;padding-bottom: 10rpx;}
.uni-dialog-title-text {font-size: 32rpx;font-weight: 500;}
.uni-dialog-content {display: flex;flex-direction: row;justify-content: center;align-items: center;padding: 5px 15px 15px 15px;}
.uni-dialog-content-text {font-size: 14px;color: #6e6e6e;}
.uni-dialog-button-group {display: flex;flex-direction: row;border-top-color: #f5f5f5;border-top-style: solid;border-top-width: 1px;}
.uni-dialog-button {display: flex;flex: 1;flex-direction: row;justify-content: center;align-items: center;height: 45px;}
.uni-border-left {border-left-color: #f0f0f0;border-left-style: solid;border-left-width: 1px;}
.uni-dialog-button-text {font-size: 14px;}
.uni-button-color {color: #007aff;}

.head-img{width: 90rpx;height: 90rpx;border-radius: 50%;overflow: hidden;}
.member-text-view{height: 90rpx;padding-left: 20rpx;display: flex;flex-direction: column;align-items: flex-start;justify-content: flex-start;}
.member-text-view .member-nickname{font-size: 28rpx;color: #333;font-weight: bold;}
.member-text-view .member-id{font-size: 24rpx;color: #999999;margin-top: 10rpx;}
</style>