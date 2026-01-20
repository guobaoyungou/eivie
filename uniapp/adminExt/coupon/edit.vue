<template>
<view>
	<block v-if="isload">
		<form @submit="subform">
      <view class="form-box">
        <view class="form-item flex-col" v-if="pageType">
          <view class="f1">{{t('优惠券')}}类型</view>
          <view class="f2">
            <radio-group name="type" @change="bindTypeChange">
							<view class="radio-group-view">
								<label><radio value="1" :checked="info.type==1?true:false"></radio> 代金券</label>
								<label><radio value="10" :checked="!info || info.type==10?true:false"></radio> 折扣券</label>
								<label><radio value="2" :checked="info.type==2?true:false"></radio> 礼品券</label>
								<label><radio value="3" :checked="info.type==3?true:false"></radio> 计次券</label>
								<label><radio value="4" :checked="info.type==4?true:false"></radio> 运费抵扣券</label>
							</view>
            </radio-group>
          </view>
        </view>
        <view class="form-item">
          <view class="f1" v-if="pageType">{{t('优惠券')}}名称<text style="color:red"> *</text></view>
					<view class="f1" v-else>优惠券名称<text style="color:red"> *</text></view>
          <view class="f2"><input type="text" name="name" :value="info.name" placeholder="请填写名称" placeholder-style="color:#888"></input></view>
        </view>
				<block v-if="pageType">
					<block v-if="info.type == 1 || info.type == 10 || info.type == 20 || info.type == 11">
					  <view class="form-item" v-if="info.type!=10 && info.type!=11">
					    <view class="f1">优惠金额(元)<text style="color:red"> *</text></view>
					    <view class="f2"><input type="digit" name="money" :value="info.money" placeholder="请填写优惠金额(元)" placeholder-style="color:#888"></input></view>
					  </view>
					  <view class="form-item" v-if="info.type==10 ">
					    <view class="f1">折扣比例<text style="color:red"> *</text></view>
					    <view class="f2"><input type="digit" name="discount" :value="info.discount" placeholder="例如9折则填写90" placeholder-style="color:#888" style="margin-right: 10rpx;"></input>%</view>
					  </view>
					  <view class="form-item" v-if="info.type!=11 ">
					    <view class="f1" style="width: 228rpx">最低消费金额(元)<text style="color:red"> </text></view>
					    <view class="f2"><input type="text" name="minprice" :value="info.minprice" placeholder="请填写最低消费金额(元)" placeholder-style="color:#888"></input></view>
					  </view>
					  <view class="form-item flex-col">
					    <view class="f1">适用范围</view>
					    <view class="f2">
					      <radio-group class="radio-group" name="fwtype" @change="scopeApplication">
									<view class="radio-group-view">
										<label><radio value="0" :checked="info.fwtype==0?true:false"></radio> 所有商品</label>
										<label><radio value="1" :checked="info.fwtype==1?true:false"></radio> 指定类目</label>
										<label v-if="bid"><radio value="6" :checked="info.fwtype==6?true:false"></radio> 指定商家类目</label>
										<label><radio value="2" :checked="info.fwtype==2?true:false"></radio> 指定商品</label>
										<label v-if="restaurant"><radio value="3" :checked="info.fwtype==3?true:false"></radio> 指定菜品</label>
										<label v-if="auth.yuyue"><radio value="4" :checked="info.fwtype==4?true:false"></radio> 指定服务商品</label>
									</view>
					      </radio-group>
					    </view>
					  </view>
						<!-- 指定类目 -->
						<view class="form-item" v-if="info.fwtype==1 || info.fwtype==6">
							<view class="flex flex-col addshow-list-view">
								<view class="flex title-view">
									<view>指定类目</view>
									<view class="but-class" :style="{background:'linear-gradient(-90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'}" @click="addshopClass(info.fwtype)">添加类目</view>
								</view>
								<view class="product" v-if="categorydata.length">
									<block v-for="(item, index2) in categorydata" :key="index2">
										<view class="item flex">
											<view class="img-view">
												<image v-if="item.pic" :src="item.pic"></image>
												<view v-else class="img-view-empty"></view>
											</view>
											<view class="info">
												<view class="f1">{{item.name}}</view>
												<view></view>
											</view>
											<view class="del-view flex-y-center" @tap.stop="clearShopClass(item.id)"><image :src="pre_url+'/static/img/del.png'" style="width:24rpx;height:24rpx;margin-right:6rpx"/></view>
										</view> 
									</block>
								</view>
							</view>
						</view>
						<!-- 指定商家类目 -->
						<!-- 指定商品 -->
						<view class="form-item" v-if="info.fwtype==2">
							<view class="flex flex-col addshow-list-view">
								<view class="flex title-view">
									<view>指定商品列表</view>
									<view class="but-class" :style="{background:'linear-gradient(-90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'}" @click="addshop(0)">添加商品</view>
								</view>
								<view class="product" v-if="productdata.length">
									<block v-for="(item, index2) in productdata" :key="index2">
										<view class="item flex">
											<view class="img-view">
												<image v-if="item.pic" :src="item.pic"></image>
												<view v-else class="img-view-empty"></view>
											</view>
											<view class="info">
												<view class="f1">{{item.name}}</view>
												<view></view>
											</view>
											<view class="del-view flex-y-center" @tap.stop="clearShopCartFn(item.id,0)" style="color:#999999;font-size:24rpx"><image :src="pre_url+'/static/img/del.png'" style="width:24rpx;height:24rpx;margin-right:6rpx"/></view>
										</view> 
									</block>
								</view>
							</view>
						</view>
						<!-- 指定菜品 -->
						<!-- 指定服务商品 -->
						<view class="form-item" v-if="info.fwtype==4">
							<view class="flex flex-col addshow-list-view">
								<view class="flex title-view">
									<view>指定服务商品列表</view>
									<view class="but-class" :style="{background:'linear-gradient(-90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'}" @click="addshopProgive(0)">添加商品</view>
								</view>
								<view class="product" v-if="yuyue_product.length">
									<block v-for="(item, index2) in yuyue_product" :key="index2">
										<view class="item flex">
											<view class="img-view">
												<image v-if="item.pic" :src="item.pic"></image>
												<view v-else class="img-view-empty"></view>
											</view>
											<view class="info">
												<view class="f1">{{item.name}}</view>
											</view>
											<view class="del-view flex-y-center" @tap.stop="clearShopCartFn2(item.id,0)" style="color:#999999;font-size:24rpx"><image :src="pre_url+'/static/img/del.png'" style="width:24rpx;height:24rpx;margin-right:6rpx"/></view>
										</view> 
									</block>
								</view>
							</view>
						</view>
					</block>
				</block>
				<block v-else>
				  <view class="form-item" v-if="info.type != 51">
				    <view class="f1">优惠金额(元)<text style="color:red"> *</text></view>
				    <view class="f2"><input type="digit" name="money" :value="info.money" placeholder="请填写优惠金额(元)" placeholder-style="color:#888"></input></view>
				  </view>
				  <view class="form-item" v-if="info.type == 10">
				    <view class="f1">折扣比例<text style="color:red"> *</text></view>
				    <view class="f2"><input type="digit" name="discount" :value="info.discount" placeholder="例如9折则填写90" placeholder-style="color:#888"></input>%</view>
				  </view>
				  <view class="form-item" v-if="info.type != 51">
				    <view class="f1" style="width: 228rpx">最低消费金额(元)<text style="color:red"> </text></view>
				    <view class="f2"><input type="text" name="minprice" :value="info.minprice" placeholder="请填写最低消费金额(元)" placeholder-style="color:#888"></input></view>
				  </view>
				  <view class="form-item flex-col">
				    <view class="f1">适用范围<text style="color:red"> *</text></view>
				    <view class="f2">
				      <radio-group class="radio-group" name="fwtype" @change="scopeApplicationRes">
				        <label><radio value="0" :checked="info.fwtype==0?true:false"></radio>全场通用</label>
				        <label style="margin-left: 20rpx;"><radio value="1" :checked="info.fwtype==1?true:false"></radio> 指定类目</label>
				        <label style="margin-left: 20rpx;"><radio value="2" :checked="info.fwtype==2?true:false"></radio> 指定菜品</label>  
				      </radio-group>
				    </view>
				  </view>
					<!-- 指定类目-餐饮 -->
					<view class="form-item" v-if="info.fwtype==1">
						<view class="flex flex-col addshow-list-view">
							<view class="flex title-view">
								<view>指定类目</view>
								<view class="but-class" :style="{background:'linear-gradient(-90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'}" @click="restaurantClass()">添加类目</view>
							</view>
							<view class="product" v-if="categorydata.length">
								<block v-for="(item, index2) in categorydata" :key="index2">
									<view class="item flex"  style="align-items: center;">
										<view class="info"  style="height: 40rpx;">
											<view class="f1">{{item.name}}</view>
										</view>
										<view class="del-view-class flex-y-center" @tap.stop="clearShopClass(item.id)"><image :src="pre_url+'/static/img/del.png'" style="width:24rpx;height:24rpx;margin-right:6rpx"/></view>
									</view> 
								</block>
							</view>
						</view>
					</view>
					<!-- 指定菜品-餐饮 -->
					<view class="form-item" v-if="info.fwtype==2">
						<view class="flex flex-col addshow-list-view">
							<view class="flex title-view">
								<view>指定菜品</view>
								<view class="but-class" :style="{background:'linear-gradient(-90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'}" @click="restaurantShop()">添加菜品</view>
							</view>
							<view class="product" v-if="productdata.length">
								<block v-for="(item, index2) in productdata" :key="index2">
									<view class="item flex">
										<view class="img-view">
											<image v-if="item.pic" :src="item.pic"></image>
											<view v-else class="img-view-empty"></view>
										</view>
										<view class="info">
											<view class="f1">{{item.name}}</view>
											<view></view>
										</view>
										<view class="del-view flex-y-center" @tap.stop="clearRestaurant(item.id)" style="color:#999999;font-size:24rpx"><image :src="pre_url+'/static/img/del.png'" style="width:24rpx;height:24rpx;margin-right:6rpx"/></view>
									</view> 
								</block>
							</view>
						</view>
					</view>
				</block>
        <block v-if=" info.type == 3">
          <view class="form-item">
            <view class="f1">可用次数<text style="color:red"></text></view>
            <view class="f2"><input type="text" name="limit_count" :value="info.limit_count" placeholder="每张券可以使用多少次" placeholder-style="color:#888"></input></view>
          </view>
          <view class="form-item">
            <view class="f1">每天可用<text style="color:red"></text></view>
            <view class="f2"><input type="text" name="limit_perday" :value="info.limit_perday" placeholder="每张券每天可以用多少次" placeholder-style="color:#888"></input></view>
          </view>
        </block>
        <view class="form-item flex-col">
          <view class="f1">使用说明<text style="color:red"> </text></view>
         <textarea :value="info.usetips" name="usetips" placeholder="请输入使用说明"></textarea>
        </view>
      </view>
      <view class="form-box">
        <view class="form-item flex-col">
          <view class="f1">有效期<text style="color:red"> </text></view>
          <view class="f2">
            <radio-group class="radio-group" name="yxqtype" @change="bindYxqtypeChange">
							<view class="radio-group-view">
								<label><radio value="1" :checked="info.yxqtype==1?true:false"></radio> 固定时间范围</label>
								<label><radio value="2" :checked="info.yxqtype==2?true:false"></radio> 领取后时长</label>
								<label><radio value="3" :checked="info.yxqtype==3?true:false"></radio> 领取后时长（次日起）</label>
							</view>
            </radio-group>
          </view>
        </view>
        <view class="form-item flex-col" v-if="info.yxqtype == 1">
          <view class="f1">有效期时间</view>
          <view class="f2" style="line-height:30px">
            <picker mode="date" :value="start_time1" @change="bindStartTime1Change">
              <view class="picker">{{start_time1}}</view>
            </picker>
            <picker mode="time" :value="start_time2" @change="bindStartTime2Change">
              <view class="picker" style="padding-left:10rpx">{{start_time2}}</view>
            </picker>
            <view style="padding:0 10rpx;color:#222;font-weight:bold">到</view>
            <picker mode="date" :value="end_time1" @change="bindEndTime1Change">
              <view class="picker">{{end_time1}}</view>
            </picker>
            <picker mode="time" :value="end_time2" @change="bindEndTime2Change">
              <view class="picker" style="padding-left:10rpx">{{end_time2}}</view>
            </picker>
          </view>
        </view>
        <view class="form-item" v-if="info.yxqtype==2">
          <view class="f1" style="width: 228rpx">领取后几天有效<text style="color:red"> *</text></view>
          <view class="f2"><input type="text" name="yxqdate2" :value="info.yxqdate" placeholder="领取后几天有效(天)" placeholder-style="color:#888"></input></view>
        </view>
        <view class="form-item" v-if="info.yxqtype==3">
          <view class="f1" style="width: 228rpx">领取后几天有效<text style="color:red"> *</text></view>
          <view class="f2"><input type="text" name="yxqdate3" :value="info.yxqdate" placeholder="次日0点开始计算有效期" placeholder-style="color:#888"></input></view>
        </view>
        <view class="form-item flex-col">
          <view class="f1" v-if="pageType">领取条件</view>
					<view class="f1" v-else>参与条件</view>
          <view class="f2" style="line-height:30px">
            <checkbox-group class="radio-group" name="gettj"  @change="collectionConditions">
              <label><checkbox value="-1" :checked="inArray('-1',info.gettj)?true:false"></checkbox> 所有人</label>
              <label><checkbox value="0" :checked="inArray('0',info.gettj)?true:false"></checkbox> 关注用户</label>
              <label v-for="(item,index) in memberlevel" :key="item.id">
								<checkbox :value="item.id" :checked="inArray(item.id,info.gettj)?true:false"></checkbox>
								{{item.name}}
							</label>
            </checkbox-group>
          </view>
        </view>
        <view class="form-item">
          <view class="f1" style="width: 228rpx">所需金额(元)<text style="color:red"> </text></view>
          <view class="f2"><input type="text" name="price" :value="info.price" placeholder="需要消耗多少钱购买(元)" placeholder-style="color:#888"></input></view>
        </view>
        <view class="form-item">
          <view class="f1" style="width: 228rpx">所需积分<text style="color:red"> </text></view>
          <view class="f2"><input type="text" name="score" :value="info.score" placeholder="需要消耗多少积分兑换" placeholder-style="color:#888"></input></view>
        </view>
        <view class="form-item">
          <view class="f1" style="width: 228rpx">库存<text style="color:red"> </text></view>
          <view class="f2"><input type="text" name="stock" :value="info.stock" placeholder="" placeholder-style="color:#888"></input></view>
        </view>
        <view class="form-item">
          <view class="f1" style="width: 228rpx">每人可领取数<text style="color:red"> </text></view>
          <view class="f2"><input type="text" name="perlimit" :value="info.perlimit" placeholder="每人最多可领取多少张" placeholder-style="color:#888"></input></view>
        </view>
        <view class="form-item">
          <view class="f1" style="width: 228rpx">开始时间<text style="color:red"> </text></view>
          <view class="f2"><picker mode="date" :value="starttime" @change="bindStartTimeChange">
            <view class="picker">{{starttime}}</view>
          </picker>
            <picker mode="time" :value="starttime2" @change="bindStartTimeChange2">
              <view class="picker" style="padding-left:10rpx">{{starttime2}}</view>
            </picker></view>
        </view>
        <view class="form-item">
          <view class="f1" style="width: 228rpx">结束时间<text style="color:red"> </text></view>
          <view class="f2"><picker mode="date" :value="endtime" @change="bindEndTimeChange">
            <view class="picker">{{endtime}}</view>
          </picker>
            <picker mode="time" :value="endtime2" @change="bindEndTimeChange2">
              <view class="picker" style="padding-left:10rpx">{{endtime2}}</view>
            </picker></view>
        </view>
        <view class="form-item">
          <view class="f1" style="width: 228rpx">序号<text style="color:red"> </text></view>
          <view class="f2"><input type="text" name="sort" :value="info.sort" placeholder="用于排序,越大越靠前" placeholder-style="color:#888"></input></view>
        </view>
      </view>
      <view class="form-box">
        <view class="form-item" v-if="pageType">
          <view class="f1">领券中心显示<text style="color:red"> </text></view>
          <view class="f2">
            <radio-group class="radio-group" name="tolist" @change="couponCenterChange">
              <label><radio value="1" :checked="info.tolist==1?true:false"></radio> 是</label>
              <label style="margin-left: 20rpx;"><radio value="0" :checked="info.tolist==0?true:false"></radio> 否</label>
            </radio-group>
          </view>
        </view>
        <view class="form-item flex-col" v-if="info.tolist==1 && pageType">
          <view class="f1">显示条件</view>
          <view class="f2" style="line-height:30px">
            <checkbox-group class="radio-group" name="showtj" @change="displayConditions">
              <label><checkbox value="-1" :checked="inArray('-1',info.showtj)?true:false"></checkbox> 所有人</label>
              <label><checkbox value="0" :checked="inArray('0',info.showtj)?true:false"></checkbox> 关注用户</label>
              <label v-for="item in memberlevel" :key="item.id"><checkbox :value="''+item.id" :checked="inArray(item.id,info.showtj)?true:false"></checkbox> {{item.name}}</label>
            </checkbox-group>
          </view>
        </view>
        <view class="form-item" v-if="!pageType">
          <view class="f1">可直接领取<text style="color:red"> </text></view>
          <view class="f2">
            <radio-group class="radio-group" name="tolist" @change="couponCenterChange">
              <label><radio value="1" :checked="info.tolist==1?true:false"></radio> 是</label>
              <label style="margin-left: 20rpx;"><radio value="0" :checked="info.tolist==0?true:false"></radio> 否</label>
            </radio-group>
          </view>
        </view>
        <view class="form-item flex-col">
          <view class="f1">使用范围<text style="color:red"> </text></view>
          <view class="f2">
            <radio-group class="radio-group" name="isgive" @change="bindStatusChange">
              <label><radio value="0" :checked="info.isgive==0?true:false"></radio> 仅自用</label>
              <label style="margin-left: 20rpx;"><radio value="1" :checked="info.isgive==1?true:false"></radio> 自用+转赠</label>
              <label style="margin-left: 20rpx;"><radio value="2" :checked="info.isgive==2?true:false"></radio> 仅转赠</label>
            </radio-group>
          </view>
        </view>
				<view class="form-item">
				  <view class="f1">支付后赠送<text style="color:red"> </text></view>
				  <view class="f2">
				    <radio-group class="radio-group" name="paygive" @change="bindPaygiveChange">
				      <label><radio value="0" :checked="info.paygive==0?true:false"></radio> 关闭</label>
				      <label style="margin-left: 20rpx;"><radio value="1" :checked="info.paygive==1?true:false"></radio> 开启</label>
				    </radio-group>
				  </view>
				</view>
        <view class="form-item flex-col" v-if="info.paygive == 1">
          <view class="f1">支付赠送场景</view>
          <view class="f2" style="line-height:30px" v-if='pageType'>
            <checkbox-group class="radio-group" name="paygive_scene" @change="paymentGiftChange">
              <label><checkbox value="shop" :checked="inArray('shop',info.paygive_scene)?true:false"></checkbox> 商城</label>
              <label><checkbox value="scoreshop" :checked="inArray('scoreshop',info.paygive_scene)?true:false"></checkbox> 兑换</label>
              <label><checkbox value="collage" :checked="inArray('collage',info.paygive_scene)?true:false"></checkbox> 拼团</label>
              <label><checkbox value="kanjia" :checked="inArray('kanjia',info.paygive_scene)?true:false"></checkbox> 砍价</label>
              <label><checkbox value="seckill" :checked="inArray('seckill',info.paygive_scene)?true:false"></checkbox> 秒杀</label>
              <label><checkbox value="tuangou" :checked="inArray('tuangou',info.paygive_scene)?true:false"></checkbox> 团购</label>
              <label><checkbox value="lucky_collage" :checked="inArray('lucky_collage',info.paygive_scene)?true:false"></checkbox> {{t('幸运拼团')}}</label>
              <label><checkbox value="recharge" :checked="inArray('recharge',info.paygive_scene)?true:false"></checkbox> 充值</label>
              <label><checkbox value="maidan" :checked="inArray('maidan',info.paygive_scene)?true:false"></checkbox> 买单收款</label>
            </checkbox-group>
          </view>
					<view class="f2" style="line-height:30px" v-else>
					  <checkbox-group class="radio-group" name="paygive_scene" @change="paymentGiftChange">
							<label><checkbox value="restaurant" :checked="inArray('restaurant',info.paygive_scene)?true:false"></checkbox> 下单</label>
							<label><checkbox value="recharge" :checked="inArray('recharge',info.paygive_scene)?true:false"></checkbox> 充值</label>
					  </checkbox-group>
					</view>
        </view>
        <view class="form-item" v-if="info.paygive == 1">
          <view class="f1">支付金额范围</view>
          <view class="amount-range-view flex">
						<input type="text" name="paygive_minprice" :value="info.paygive_minprice" placeholder="请输入金额" placeholder-style="color:#888" style="text-align: right;" />
						<view style="padding: 0px 20rpx;">-</view>
						<input type="text" name="paygive_maxprice" :value="info.paygive_maxprice" placeholder="请输入金额" placeholder-style="color:#888" />元
					</view>
        </view>
        <view class="form-item" v-if="pageType">
          <view class="f1">购买商品赠送<text style="color:red"> </text></view>
          <view class="f2">
            <radio-group class="radio-group" name="buyprogive" @change="bindBuyprogiveChange">
              <label><radio value="0" :checked="info.buyprogive==0?true:false"></radio> 关闭</label>
              <label style="margin-left: 20rpx;"><radio value="1" :checked="info.buyprogive==1?true:false"></radio> 开启</label>
            </radio-group>
          </view>
        </view>
				<view class="form-item" v-if="info.buyprogive == 1">
					<view class="flex flex-col addshow-list-view">
						<view class="flex title-view">
							<view>商品列表</view>
							<view class="but-class" :style="{background:'linear-gradient(-90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'}" @click="addshop(1)">添加商品</view>
						</view>
						<view class="product" v-if="giftProductsList.length">
							<block v-for="(item, index2) in giftProductsList" :key="index2">
								<view class="item flex">
									<view class="img-view">
										<image v-if="item.pic" :src="item.pic"></image>
										<view v-else class="img-view-empty"></view>
									</view>
									<view class="info">
										<view class="f1">{{item.name}}</view>
										<view class="modify-price flex-y-center">
											<view class="f2">赠送数量：</view>
											<input type="digit" v-model="item.give_num" class="inputPrice" @input="inputNumChange($event,0)">
										</view>
									</view>
									<view class="del-view flex-y-center" @tap.stop="clearShopCartFn(item.id,1)" style="color:#999999;font-size:24rpx"><image :src="pre_url+'/static/img/del.png'" style="width:24rpx;height:24rpx;margin-right:6rpx"/></view>
								</view> 
							</block>
						</view>
					</view>
				</view>
        <view class="form-item" v-if="pageType">
          <view class="f1" style="width: 250rpx">购买服务商品赠送<text style="color:red"> </text></view>
          <view class="f2">
            <radio-group class="radio-group" name="buyyuyueprogive" @change="bindBuyyuyueprogiveChange">
              <label><radio value="0" :checked="info.buyyuyueprogive==0?true:false"></radio> 关闭</label>
              <label style="margin-left: 20rpx;"><radio value="1" :checked="info.buyyuyueprogive==1?true:false"></radio> 开启</label>
            </radio-group>
          </view>
        </view>
				<view class="form-item" v-if="info.buyyuyueprogive == 1">
					<view class="flex flex-col addshow-list-view">
						<view class="flex title-view">
							<view>服务商品列表</view>
							<view class="but-class" :style="{background:'linear-gradient(-90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'}" @click="addshopProgive(1)">添加商品</view>
						</view>
						<view class="product" v-if="giftProductsLists.length">
							<block v-for="(item, index2) in giftProductsLists" :key="index2">
								<view class="item flex">
									<view class="img-view">
										<image v-if="item.pic" :src="item.pic"></image>
										<view v-else class="img-view-empty"></view>
									</view>
									<view class="info">
										<view class="f1">{{item.name}}</view>
										<view class="modify-price flex-y-center">
											<view class="f2">赠送数量：</view>
											<input type="digit" v-model="item.give_num" class="inputPrice" @input="inputNumChange($event,1)">
										</view>
									</view>
									<view class="del-view flex-y-center" @tap.stop="clearShopCartFn2(item.id,1)" style="color:#999999;font-size:24rpx"><image :src="pre_url+'/static/img/del.png'" style="width:24rpx;height:24rpx;margin-right:6rpx"/></view>
								</view> 
							</block>
						</view>
					</view>
				</view>
      </view>
      <view class="form-box" v-if="pageType">
        <view class="form-item">
          <view class="f1" style="width: 228rpx">字体颜色<text style="color:red"> </text></view>
          <view class="f2"><input type="text" name="font_color" :value="info.font_color" placeholder="如：#2B2B2B" placeholder-style="color:#888"></input></view>
        </view>
        <view class="form-item">
          <view class="f1" style="width: 228rpx">标题颜色<text style="color:red"> </text></view>
          <view class="f2"><input type="text" name="title_color" :value="info.title_color" placeholder="如：#2B2B2B" placeholder-style="color:#888"></input></view>
        </view>
        <view class="form-item">
          <view class="f1" style="width: 228rpx">背景颜色<text style="color:red"> </text></view>
          <view class="f2"><input type="text" name="bg_color" :value="info.bg_color" placeholder="如：#FFFFFF" placeholder-style="color:#888"></input></view>
        </view>
      </view>
			<!-- 编辑 & 添加 -->
			<button class="savebtn" :style="'background:linear-gradient(90deg,'+tColor('color1')+' 0%,rgba('+tColor('color1rgb')+',0.8) 100%)'" form-type="submit">提交</button>
			<view style="height:50rpx"></view>
		</form>
	</block>
	<loading v-if="loading"></loading>
</view>
</template>
<script>
var app = getApp();
export default {
  data() {
    return {
			isload:true,
			loading:false,
			pre_url:app.globalData.pre_url,
      info:{},
			memberlevel:[],
			pic:[],
			start_time1:'-选择日期-',
			start_time2:'-选择时间-',
			end_time1:'-选择日期-',
			end_time2:'-选择时间-',
			endtime:'-选择日期-',
			endtime2:'-选择时间-',
			starttime:'-选择日期-',
			starttime2:'-选择时间-',
			product_showset:0,
			showtjArr:['-1'],
      zhif:0,
			giftProductsList:[],
			giftServiceList:[],
			buyproGiveNum:[],
			giftProductsLists:[],
			pageType:false,
			bid:0,
			auth:[],
			restaurant:false,
			editType:'',
			productdata:[],//适用范围-指定商品
			addshopType:0,//添加商品类型
			yuyue_product:[],//适用范围-指定服务商品
			addfuwushopType:0,
			categorydata:[],//适用范围-指定类目
    };
  },

  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.editType = opt.type;
		if(opt.type == 1){ //优惠券详情
			this.pageType = true;
			this.getdata('ApiAdminCoupon/edit',opt.type);
			this.subformUrl = 'ApiAdminCoupon/save';
		}else if(opt.type == 0){  //餐饮优惠券详情
			this.getdata('ApiAdminRestaurantCoupon/edit',opt.type);
			this.subformUrl = 'ApiAdminRestaurantCoupon/save';
		}else if(opt.type == 2){ //添加优惠券详情
			this.pageType = true;
			this.getdata('ApiAdminCoupon/edit',opt.type);
			this.subformUrl = 'ApiAdminCoupon/save';
			let params = {
				type:'1',
				gettj:['-1'],
				yxqtype:'1',
				fwtype:0,
				tolist:1,
				isgive:0,
				paygive:0,
				buyprogive:0,
				buyyuyueprogive:0,
				stock:100,
				perlimit:1,
				showtj:[-1]
			}
			this.info = params;
			this.initTime();
		}else if(opt.type == 3){ //添加餐饮优惠券详情
			this.getdata('ApiAdminRestaurantCoupon/edit',opt.type);
			this.subformUrl = 'ApiAdminRestaurantCoupon/save';
			let params = {
				type:5,
				gettj:['-1'],
				fwtype:0,
				yxqtype:'1',
				tolist:'1',
				isgive:0,
				paygive:0,
				buyprogive:0,
				buyyuyueprogive:0,
				stock:'100',
				perlimit:'1',
				showtj:[-1]
			}
			this.info = params;
			this.initTime();
		}
		if(opt.type == 1 || opt.type == 2){
			uni.setNavigationBarTitle({
				title: this.t('优惠券')+'设置'
			});
		}else{
			uni.setNavigationBarTitle({
				title: '餐饮优惠券设置'
			});
		}
  },
	onShow() {
		uni.$off();
		let that = this;
		uni.$once('shopDataEmit',function(e){
			if(that.addshopType){
				setTimeout(() => {
					let nowArr = that.giftProductsList.map(item => item.id);
					if(nowArr.includes(e.id)) {
						setTimeout(() => {
							uni.showToast({icon:'none',title:'该商品已添加过了'}) ;
						},600);
						return;
					}
					that.giftProductsList.push(e);
					let idArr = that.giftProductsList.map(item => item.id);
					that.info.buyproids = idArr.join(',');
					that.info.buypro_give_num = that.giftProductsList.map(item => item.give_num);
				})
			}else{
				//指定商品列表
				setTimeout(() => {
					let nowArr = that.productdata.map(item => item.id);
					if(nowArr.includes(e.id)) {
						setTimeout(() => {
							uni.showToast({icon:'none',title:'该商品已添加过了'}) ;
						},600);
						return;
					}
					that.productdata.push(e);
					let idArr = that.productdata.map(item => item.id);
					that.info.productids = idArr.join(',');
				})
			}
		})
		uni.$once('shopDataEmitS',function(e){
			if(that.addfuwushopType){
				let nowArr = that.giftProductsLists.map(item => item.id);
				if(nowArr.includes(e.id)) {
					setTimeout(() => {
						uni.showToast({icon:'none',title:'该商品已添加过了'}) ;
					},600)
					return;
				}
				that.giftProductsLists.push(e);
				let idArr = that.giftProductsLists.map(item => item.id);
				that.info.buyyuyueproids = idArr.join(',');
				that.info.buyyuyuepro_give_num = that.giftProductsLists.map(item => item.give_num);
			}else{
				let nowArr = that.yuyue_product.map(item => item.id);
				if(nowArr.includes(e.id)) {
					setTimeout(() => {
						uni.showToast({icon:'none',title:'该服务商品已添加过了'}) ;
					},600)
					return;
				}
				that.yuyue_product.push(e);
			}
		})
		// 普通优惠券--添加分类
		uni.$once('shopDataClass',function(e){
			let nowArr = that.categorydata.map(item => item.id);
			if(nowArr.includes(e.id)) {
				setTimeout(() => {
					uni.showToast({icon:'none',title:'该分类已添加过了'}) ;
				},600)
				return;
			}
			that.categorydata.push(e);
			let idArr = that.categorydata.map(item => item.id);
			that.info.categoryids = idArr.join(',');
		})
	},	
	onUnload(){
		
	},
  methods: {
		// 修改商品数量事件
		inputNumChange(e,type){
			let that = this;
			if(type == 0){ //购买赠送商品列表
				that.info.buypro_give_num = that.giftProductsList.map(item => item.give_num);
			}else if(type == 1){ //购买赠送服务商品列表
				that.info.buyyuyuepro_give_num = that.giftProductsLists.map(item => item.give_num);
			}
		},
			tColor(text){
					let that = this;
					if(text=='color1'){
						if(app.globalData.initdata.color1 == undefined){
							let timer = setInterval(() => {
								that.tColor('color1')
							},1000)
							clearInterval(timer)
						}else{
							return app.globalData.initdata.color1;
						}
					}else if(text=='color2'){
						return app.globalData.initdata.color2;
					}else if(text=='color1rgb'){
						if(app.globalData.initdata.color1rgb == undefined){
							setTimeout(() => {
								that.tColor('color1rgb')
							},1000)
						}else{
							var color1rgb = app.globalData.initdata.color1rgb;
							return color1rgb['red']+','+color1rgb['green']+','+color1rgb['blue'];
						}
					}else if(text=='color2rgb'){
						var color2rgb = app.globalData.initdata.color2rgb;
						return color2rgb['red']+','+color2rgb['green']+','+color2rgb['blue'];
					}else{
						return app.globalData.initdata.textset[text] || text;
					}
			},
		// 显示条件
		displayConditions(e){
			this.info.showtj = e.detail.value;
		},
		// 支付赠送场景
		paymentGiftChange(e){
			this.info.paygive_scene = e.detail.value;
		},
		// 领取条件
		collectionConditions:function(e){
			this.info.gettj = e.detail.value;
		},
		// 初始化时间
		initTime(){
			let dateww = new Date();
			let startTime = app.dateFormat(dateww.getTime()/1000);
			this.start_time1 = startTime.split(' ')[0];
			this.start_time2 = this.end_time2 = this.starttime2 = this.endtime2 = '00:00:00';
			let date = new Date(dateww);
			date.setDate(date.getDate() + 7); 
			let endTime = app.dateFormat(date.getTime()/1000);
			this.end_time1 = endTime.split(' ')[0];
			this.starttime = startTime.split(' ')[0];
			this.endtime = endTime.split(' ')[0];
			// 有效时间期
			this.info.yxqtime = this.start_time1 + ' ' + this.start_time2 + ' ~ ' + this.end_time1 + ' ' + this.end_time2;
			this.info.starttime = this.starttime + ' ' + this.starttime2;
			this.info.endtime = this.endtime + ' ' + this.endtime2;
		},
		// 添加菜品
		restaurantShop(){
			uni.navigateTo({
				url:'/admin/restaurant/product/index?coupon=1'+'&bid='+this.bid
			})
		},
		// 添加类目-普通优惠券
		addshopClass(type){
			if(type == 1){
				uni.navigateTo({
					url:'category'
				})
			}else{
				uni.navigateTo({
					url:'category?bid=' + this.bid
				})
			}

		},
		// 添加菜品分类
		restaurantClass(){
			uni.navigateTo({
				url:'/admin/restaurant/category/index?coupont=1'+'&bid=' + this.bid
			})
		},
		// 删除菜品
		clearRestaurant(id){
			var that = this;
			uni.showModal({
				title: '提示',
				content: '确认删除菜品吗？',
				success: function (res) {
					if (res.confirm) {
						let ArrarList  = that.productdata.filter(item => item.id != id);
						that.productdata = ArrarList;
						let idArr = that.productdata.map(item => item.id);
						that.info.productids = idArr.join(',');
					} else if (res.cancel) {
					}
				}
			});
		},
		// 删除指定类目-餐饮-普通
		clearShopClass(id){
			var that = this;
			uni.showModal({
				title: '提示',
				content: '确认删除分类吗？',
				success: function (res) {
					if (res.confirm) {
						let ArrarList  = that.categorydata.filter(item => item.id != id);
						that.categorydata = ArrarList;
						let idArr = that.categorydata.map(item => item.id);
						that.info.categoryids = idArr.join(',');
					} else if (res.cancel) {
					}
				}
			});
		},
		// 适用范围-餐饮
		scopeApplicationRes(e){
			this.info.fwtype = e.detail.value;
		},
		// 适用范围
		scopeApplication(e){
			this.info.fwtype = e.detail.value;
		},
		clearShopCartFn: function (id,type) {
		  var that = this;
			uni.showModal({
				title: '提示',
				content: '确认删除商品吗？',
				success: function (res) {
					if (res.confirm) {
						if(type){
							let ArrarList  = that.giftProductsList.filter(item => item.id != id);
							that.giftProductsList = ArrarList;
						}else{
							let ArrarList  = that.productdata.filter(item => item.id != id);
							that.productdata = ArrarList;
						}
					} else if (res.cancel) {
					}
				}
			});
		},
		clearShopCartFn2: function (id,type) {
		  var that = this;
			uni.showModal({
				title: '提示',
				content: '确认删除商品吗？',
				success: function (res) {
					if (res.confirm) {
						if(type){
							let ArrarList  = that.giftProductsLists.filter(item => item.id != id);
							that.giftProductsLists = ArrarList;
						}else{
							let ArrarList  = that.yuyue_product.filter(item => item.id != id);
							that.yuyue_product = ArrarList;
						}
					} else if (res.cancel) {
					}
				}
			});
		},
		// 添加服务商品
		addshopProgive(type){
			this.addfuwushopType = type;
			uni.navigateTo({
				url:'prolist?bid=' + this.bid
			})
		},
		// 添加普通商品
		addshop(type){
			this.addshopType = type;
			uni.navigateTo({
				url:'/admin/order/dkfastbuy?coupon=1'+'&bid='+this.bid
			})
		},
		getdata:function(URL,type){
			var that = this;
			var id = that.opt.id ? that.opt.id : '';
			that.loading = true;
			app.get(URL,{id:id}, function (res) {
				that.loading = false;
				if(type == 2 || type == 3){ //添加优惠券
				}else{
					that.info = res.info;
					// 固定时间范围
					if(that.info.yxqtime){
						let yxqtime_time = that.info.yxqtime.split('~');
						let start_times = yxqtime_time[0];
						that.start_time1 = start_times.split(' ')[0];
						that.start_time2 = start_times.split(' ')[1];
						let end_times = yxqtime_time[1];
						that.end_time1 = end_times.split(' ')[1];
						that.end_time2 = end_times.split(' ')[2];
					}
					// 开始时间 & 结束时间
					if(that.info.starttime){
						that.starttime = that.info.starttime.split(' ')[0];
						that.starttime2 = that.info.starttime.split(' ')[1];
					}
					if(that.info.endtime){
						that.endtime = that.info.endtime.split(' ')[0];
						that.endtime2 = that.info.endtime.split(' ')[1];
					}
					// 商品赠送列表
					if(res.productdata2){
						that.giftProductsList = res.productdata2;
						that.buyproGiveNum = res.info.buypro_give_num;
						that.giftProductsList.forEach((item,index) => {
							item.give_num = that.buyproGiveNum[index]
						});
					}
					// 服务商品列表
					if(res.yuyue_products2){
						that.giftProductsLists = res.yuyue_products2;
						// that.buyproGiveNum = res.info.buyyuyuepro_give_num;
						that.giftProductsLists.forEach((item,index) => {
							item.give_num = res.info.buyyuyuepro_give_num[index]
						});
					}
				}
				// 其他判断条件
				that.bid = res.bid ? res.bid:0;
				that.auth = res.auth;
				that.restaurant = res.restaurant;
				// 领取条件
				that.memberlevel = res.memberlevel;
				that.showtjArr = that.info.showtj
				// 适用范围-指定商品
				if(res.productdata){
					that.productdata = res.productdata
				}
				// 适用范围-指定服务商品
				if(res.yuyue_product){
					that.yuyue_product = res.yuyue_product
				}
				// 适用范围-指定类目
				if(res.categorydata){
					that.categorydata = res.categorydata
				}
				that.loaded();
			});
		},
    subform: function (e) {
      var that = this;
      var formdata = e.detail.value;
			if(!formdata.name) return app.alert('请填写名称');
			switch(that.info.type){ //必填条件判断优惠券类型
				case '1':
				if(!formdata.money) return app.alert('请填写金额');
				break;
			}
      var id = that.opt.id ? that.opt.id : '';
			formdata.id = id;
			if(this.editType == 3){
				formdata.type = this.info.type;
			}
			that.info.name = formdata.name;
			that.info.money = formdata.money;
			that.info.yxqtime = that.start_time1 + ' ' + that.start_time2 + ' ~ ' + that.end_time1 + ' ' + that.end_time2;
			that.info.starttime = that.starttime + ' ' + that.starttime2;
			that.info.endtime = that.endtime + ' ' + that.endtime2;
			that.info.usetips = formdata.usetips;
			that.info.price =  formdata.price || 0;
			that.info.score = formdata.score || 0;
			that.info.sort = formdata.sort;
			that.info.perlimit = formdata.perlimit;
			that.info.minprice = formdata.minprice || 0;
			that.info.stock = formdata.stock;
			that.info.yxqdate2 = formdata.yxqdate2;
			that.info.yxqdate3 = formdata.yxqdate3;
			that.info.font_color = formdata.font_color;
			that.info.title_color = formdata.title_color;
			that.info.bg_color = formdata.bg_color;
			that.info.paygive_minprice = formdata.paygive_minprice || 0;
			that.info.paygive_maxprice = formdata.paygive_maxprice || 9999;
			that.info.limit_count = formdata.limit_count;
			that.info.limit_perday = formdata.limit_perday || 0;
			that.info.discount = formdata.discount;
      app.post(that.subformUrl, {info:that.info}, function (res) {
        if (res.status == 1) {
					app.success(res.msg);
					setTimeout(function () {
					 uni.navigateBack({
						 delta:1
					 })
					}, 1000);
        } else {
					app.error(res.msg);
        }
      });
    },
		bindBuyyuyueprogiveChange(e){
			this.info.buyyuyueprogive = e.detail.value;
		},
		bindBuyprogiveChange(e){
			this.info.buyprogive = e.detail.value;
		},
    bindYxqtypeChange:function(e){
      this.$set(this.info,'yxqtype',e.detail.value);
    },
		couponCenterChange:function(e){
			this.info.tolist = e.detail.value
		},
		bindStatusChange:function(e){ //使用范围
      this.info.isgive = e.detail.value;
		},
		bindStartTimeChange:function(e){
			this.starttime = e.target.value
		},
		bindStartTime1Change:function(e){
			this.start_time1 = e.target.value
		},
		bindStartTimeChange2:function(e){
			this.starttime2 = e.target.value
		},
		bindEndTimeChange:function(e){
			this.endtime = e.target.value
		},
		bindEndTimeChange2:function(e){
			this.endtime2 = e.target.value
		},
		bindStartTime2Change:function(e){
			this.start_time2 = e.target.value
		},
		bindEndTime1Change:function(e){
			this.end_time1 = e.target.value
		},
		bindEndTime2Change:function(e){
			this.end_time2 = e.target.value
		},
    bindPaygiveChange:function(e){
      this.$set(this.info,'paygive',e.detail.value);
    },
		// 选择优惠券类型
    bindTypeChange:function(e){
			switch(e.detail.value){
				case '3': //计次券默认参数
				this.$set(this.info,'limit_count',1);
				this.$set(this.info,'limit_perday',0);
				break;
			}
      this.$set(this.info,'type',e.detail.value);
    },
  }
};
</script>
<style>
radio{transform: scale(0.6);}
checkbox{transform: scale(0.6);}
.form-box{ padding:2rpx 20rpx 0 20rpx; background: #fff;margin: 20rpx;border-radius: 10rpx}
.form-item{ line-height: 100rpx; display: flex;justify-content: space-between;border-bottom:1px solid #eee }
.form-item .f1{color:#222;width:200rpx;flex-shrink:0}
.form-item .f2{display:flex;align-items:center}
.form-box .form-item:last-child{ border:none}
.form-box .flex-col{padding-bottom:20rpx}
.form-item input{ width: 100%; border: none;color:#111;font-size:28rpx; text-align: right}
.form-item textarea{ width:100%;min-height:100rpx;border: none;}
.form-item .upload_pic{ margin:50rpx 0;background: #F3F3F3;width:90rpx;height:90rpx; text-align: center  }
.form-item .upload_pic image{ width: 32rpx;height: 32rpx; }
.form-item .but-class{width: 150rpx;height: 50rpx;line-height: 50rpx;color: #fff;text-align: center;font-size: 24rpx;border-radius:35rpx;background: #999;}
.savebtn{ width: 90%; height:96rpx; line-height: 96rpx; text-align:center;border-radius:48rpx; color: #fff;font-weight:bold;margin: 0 5%; margin-top:60rpx; border: none;background: red;}
.addshow-list-view{width: 100%;}
.addshow-list-view .title-view{flex: 1;justify-content: space-between;align-items: center;}
.addshow-list-view .product {width: 100%;}
.addshow-list-view .product .item {position: relative;width: 100%;padding: 0rpx 0 20rpx;align-items: center;}
.addshow-list-view .product .img-view{width: 140rpx;height: 140rpx;border-radius: 10rpx;overflow: hidden;}
.addshow-list-view .product .img-view .img-view-empty{width: 100%;height: 100%;background: #eee;}
.addshow-list-view .product .img-view image {width: 100%;height: 100%;}
.addshow-list-view .product .info .modify-price{padding: 0rpx 0rpx;}
.product .info .modify-price .inputPrice {border: 1px solid #ddd; width: 200rpx; height: 40rpx; border-radius: 10rpx; padding: 0 4rpx;text-align: left;}
.addshow-list-view .product .del-view{position: absolute;right: 10rpx;top: 50%;margin-top: -7px;padding: 10rpx;}
.addshow-list-view .product .del-view-class{padding: 10rpx;color:#999999;font-size:24rpx}
.addshow-list-view .product .info {padding-left: 20rpx;flex: 1;height:140rpx;}
.addshow-list-view .product .info .f1 {color: #222222;font-weight: bold;font-size: 24rpx;line-height: 32rpx;display: -webkit-box;-webkit-box-orient: vertical;-webkit-line-clamp: 2;overflow: hidden;
width: 96%;}
.addshow-list-view .product .info .f2 {color: #999999;font-size: 24rpx;white-space: nowrap;}
.amount-range-view{align-items: center;justify-content: flex-end;}
.amount-range-view input{text-align: left;}
.radio-group-view{display: flex;align-items: center;flex-wrap: wrap;justify-content: flex-start;}
.radio-group-view label{white-space: nowrap;margin-right: 20rpx;}
</style>