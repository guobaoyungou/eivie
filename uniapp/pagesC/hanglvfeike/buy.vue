<template>
	<view class="page-view" v-if="isload">
    <view style="background-color: #fff;padding: 20rpx;">
      <view style="display: flex;align-items: center;margin: 25rpx 0;justify-content: space-between;">
        <view>
          <view style="font-weight: bold;font-size: 32rpx;">
            {{fromCityname}}-{{toCityname}} {{fromDate}} {{week}} {{flightsdata.levelname}}
          </view>
          <view style="margin-top: 10rpx;color: #999;">
            <block v-if="minSalePrice>0">票价<text :style="'margin:0 10rpx 0 2rpx;color:'+t('color1')">￥{{minSalePrice}}</text> </block>
            <block v-if="taxFee>0">机建<text :style="'margin:0 10rpx 0 2rpx;color:'+t('color1')">￥{{taxFee}}</text> </block>
            <block v-if="fuelFee>0">燃油<text :style="'margin:0 10rpx 0 2rpx;color:'+t('color1')">￥{{fuelFee}}</text></block>
          </view>
          <view v-if="guize && guize.ticketTimes" style="margin-top: 10rpx;color: #999;display: flex;align-items: center;">
            <image :src="pre_url+'/static/img/peisong/ps_time.png'" mode="widthFix" style="width: 30rpx;height: 30rpx;"></image>
            <view style="width: calc(100% - 40rpx);">
              {{guize.ticketTimes}}
            </view>
            
          </view>
        </view>
        <view @tap="dialogDetailOpen" style="width:100rpx ;display: flex;align-items: center;">
          <text style="color: #6598FF;margin-left: 10rpx;">详情</text><image :src="pre_url+'/static/img/arrowright.png'" style="width: 28rpx;height: 28rpx;"></image>
        </view>
      </view>

      <view v-if="guize" style="display: flex;align-items: center;margin: 25rpx 0;justify-content: space-between;border-top: 2rpx solid #f1f1f1;padding-top: 20rpx;">
        <view v-if="guize.changetip || guize.checkedluggage">
          <view v-if="guize.changetip" style="margin-top: 10rpx;color: #999;">{{guize.changetip}}</view>
          <view v-if="guize.checkedluggage" style="margin-top: 10rpx;color: #999;">{{guize.checkedluggage}}</view>
        </view>
        <view v-else>
          <view style="margin-top: 10rpx;color: #999;">点击右侧查看预定须知信息</view>
        </view>
        <view @tap="getguize" style="width:150rpx ;display: flex;align-items: center;">
          <text style="color: #6598FF;margin-left: 10rpx;">预定须知</text><image :src="pre_url+'/static/img/arrowright.png'" style="width: 28rpx;height: 28rpx;"></image>
        </view>
      </view>
    </view>
    <view style="background-color: #fff;border-radius: 10rpx 10rpx;padding: 20rpx;margin: 20rpx;">
      <view style="display: flex;justify-content: space-between;align-items: center;">
        <view style="font-weight: bold;font-size: 32rpx;">乘机人<text v-if="flightspricedata && flightspricedata.showstock" style="font-size: 24rpx;">(剩{{flightspricedata.stock}}张)</text></view>
        <view @tap="goto" data-url="user?type=choose" style="width: 170rpx;line-height: 70rpx;border-radius: 160rpx 160rpx;text-align: center;" :style="'color:'+t('color1')+';background:rgba('+t('color1rgb')+',0.3)'">
          添加旅客
        </view>
      </view>
      <view v-if="userdata && userdata.length>0" style="padding:20rpx 0;border-bottom: 2rpx solid #f1f1f1;">
        <view v-for="(item2, index) in userdata" :key="index" style="display: flex;align-items: center;">
          <view @tap="deluser" :data-index="index" style="height:34rpx;width:34rpx;">
            <image :src="pre_url+'/static/img/ico-del.png'" style="background:#fff;height:34rpx;width:34rpx;border-radius:14rpx"/>
          </view>
          <view style="margin-left: 20rpx;">
            <view style="font-weight: bold;">{{item2.name}}</view>
            <view style="color: #666;">{{item2.typename}} {{item2.usercard}}</view>
          </view>
        </view>
      </view>
      <view style="line-height: 80rpx;">
        <view style="display: flex;justify-content: space-between;align-items: center;">
          <view style="width: 150rpx;">联系人</view><input name="contacts" @input="inputContacts" :value="contacts" style="line-height: 80rpx;width: 100%;" placeholder="请输入姓名" placeholder-style="line-height: 80rpx;padding:0 10rpx">
        </view>
        <view style="display: flex;justify-content: space-between;align-items: center;">
          <view style="width: 150rpx;">联系电话</view><input name="mobile" @input="inputMobile" :value="mobile" style="line-height: 80rpx;width: 100%;" placeholder="请输入电话" placeholder-style="line-height: 80rpx;padding:0 10rpx">
        </view>
      </view>
    </view>
    
    <view v-if="invoice > 0" style="background-color: #fff;border-radius: 10rpx 10rpx;padding: 20rpx;margin: 20rpx;">
    	<view style="display: flex;justify-content: space-between;align-items: center;">
    	  <view style="font-weight: bold;font-size: 32rpx;">发票</view>
    	</view>
      <view style="line-height: 80rpx;">
        <view  style="display: flex;justify-content: space-between;align-items: center;">
          <view style="width: 150rpx;">开发票</view>
          <view @tap="showInvoice" :data-url="'/pages/shop/invoice?bid=' + bid + '&prodata=' + opt.prodata" style="display: flex;justify-content: flex-end;width: 100%;">
            <text class="iconfont iconjiantou" style="color:#999;font-weight:normal"></text>
          </view>
        </view>
      </view>
    </view>
    
    <view style="background-color: #fff;border-radius: 10rpx 10rpx;padding: 20rpx;margin: 20rpx;">
      <view style="display: flex;justify-content: space-between;align-items: center;">
        <view style="font-weight: bold;font-size: 32rpx;">费用明细</view>
        <view style="font-size: 26rpx;margin-top: 10rpx;color: #999;">价格实时变化，以提交支付时价格为准</view>
      </view>
      <view style="line-height: 80rpx;">
        <view style="display: flex;justify-content: space-between;align-items: center;">
          <view style="width: 150rpx;">票价</view>
          <view :style="'color:'+t('color1')">
              <text v-if="minSalePrice>0">+￥{{minSalePrice}} x {{minSalePricenum}}</text>
              <text v-if="minSalePrice1>0">+￥{{minSalePrice1}} x {{minSalePricenum1}}</text>
              <text v-if="minSalePrice2>0">+￥{{minSalePrice2}} x {{minSalePricenum2}}</text>
          </view>
        </view>
        <view v-if="taxFee>0 || taxFee1>0 || taxFee2>0" style="display: flex;justify-content: space-between;align-items: center;">
          <view style="width: 150rpx;">机建</view>
          <view :style="'color:'+t('color1')">
            <text v-if="taxFee>0">+￥{{taxFee}} x {{taxFeenum}}</text>
            <text v-if="taxFee1>0">+￥{{taxFee1}} x {{taxFeenum1}}</text>
            <text v-if="taxFee2>0">+￥{{taxFee2}} x {{taxFeenum2}}</text>
          </view>
        </view>
        <view v-if="fuelFee>0 || fuelFee1>0 || fuelFee2>0" style="display: flex;justify-content: space-between;align-items: center;">
          <view style="width: 150rpx;">燃油</view>
          <view :style="'color:'+t('color1')">
            <text v-if="fuelFee>0">+￥{{fuelFee}} x {{fuelFeenum}}</text>
            <text v-if="fuelFee1>0">+￥{{fuelFee1}} x {{fuelFeenum1}}</text>
            <text v-if="fuelFee2>0">+￥{{fuelFee2}} x {{fuelFeenum2}}</text>
          </view>
        </view>
        <view v-if="servefee>0" style="display: flex;justify-content: space-between;align-items: center;">
          <view style="width: 150rpx;">服务费</view>
          <view :style="'color:'+t('color1')">
            +￥{{servefee}}
          </view>
        </view>
      </view>
    </view>
    
    <view v-if="xieyidata && xieyidata.length>0" style="width: 100%; height:290rpx;"></view>
    <view v-else style="width: 100%; height:182rpx;"></view>
    <view class="footer  notabbarbot">
      <view v-if="xieyidata && xieyidata.length>0" class="xycss1">
        <checkbox-group @change="isagreeChange"  style="display: inline-block;">
            <checkbox style="transform: scale(0.6)"  value="1" :checked="isagree"/>
        </checkbox-group>
        <view style="min-width: 170rpx;">我已阅读并同意</view>
        <view v-if="xieyidata[0]" @tap="showXieyi(0)" style="text-decoration:underline;min-width: 150rpx;overflow: hidden;white-space: nowrap;text-overflow: ellipsis;" :style="'color:'+t('color1')">
          {{xieyidata[0].name}}
        </view>
        <view v-if="xieyidata[1]" style="color: #000;margin: 0 4rpx;">和</view>
        <view v-if="xieyidata[1]" @tap="showXieyi(1)" style="view-decoration:underline;min-width: 150rpx;overflow: hidden;white-space: nowrap;text-overflow: ellipsis;" :style="'color:'+t('color1')">
          {{xieyidata[1].name}}
        </view>
      </view>
      <view style="display: flex;align-items: center;">
        <view class="text1 flex1">总计：
          <text style="font-weight:bold;font-size:32rpx">￥{{totalprice}}</text>
        </view>
        <button @tap="topay" class="op"  :style="{background:'linear-gradient(-90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'}" >
          提交订单</button>
        </view>
    </view>
    
    <view v-if="showxieyi" class="xieyibox">
    	<view class="xieyibox-content">
    		<view style="overflow:scroll;height:100%;">
    			<parse :content="xycontent" @navigate="navigate"></parse>
    		</view>
    		<view style="position:absolute;z-index:9999;bottom:10px;left:0;right:0;margin:0 auto;text-align:center; width: 50%;height: 60rpx; line-height: 60rpx; color: #fff; border-radius: 8rpx;" :style="{background:'linear-gradient(90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'}"  @tap="hidexieyi">已阅读并同意</view>
    	</view>
    </view>
    
    <uni-popup id="dialogDetail" ref="dialogDetail" type="dialog">
      <view class="uni-popup-dialog">
        <view class="uni-dialog-title">
          <text class="uni-dialog-title-text">详情</text>
        </view>
        <view class="uni-dialog-content">
          <view>
            <view style="text-align: center;">
              <view style="display: flex;">
                <view v-if="flightsdata.airlinePic" style="width: 28rpx;height: 28rpx;">
                  <image :src="flightsdata.airlinePic" style="width: 100%;height: 100%;"></image>
                </view>
                <view style="width: calc(100% - 40rpx);font-size: 24rpx;">
                  {{flightsdata.airlineName}}{{flightsdata.no}} {{fromDate}} {{week}}
                </view>
              </view>
              <view >
                {{flightsdata.flightTime}}
              </view>
            </view>
            <view style="display: flex;justify-content: space-between;margin: 20rpx 0;font-size: 32rpx;font-weight: bold;">
                <view style="text-align: center;">
                  <view >{{flightsdata.departTime}}</view>
                  <view style="font-size: 24rpx;">{{flightsdata.deparAirportName}}{{flightsdata.departTerminal}}</view>
                </view>
                <view style="position: relative;">
                  <view v-if="flightsdata.stopname" class="stop-tag">停</view>
                  <image :src="pre_url+'/static/img/planeticket/jiantou.png'" mode="widthFix" style="width: 120rpx;"></image>
                  <view v-if="flightsdata.stopname" style="font-size: 24rpx;text-align: center;color: #676767;">{{flightsdata.stopname}}</view>
                </view>
                
                <view style="text-align: center;">
                  <view >{{flightsdata.arriveTime}}</view>
                  <view style="font-size: 24rpx;">{{flightsdata.arriveAirportName}}{{flightsdata.arriveTerminal}}</view>
                </view>
            </view>
            
            <view style="color: #999;text-align: center;">
                {{flightsdata.planeCName}} ({{flightsdata.planeTypeName}})
                <text v-if="flightsdata.mealsname">|{{flightsdata.mealsname}}</text>
            </view>
          </view>
         
        </view>
        <view class="uni-dialog-button-group">
          <view class="uni-dialog-button" @tap="dialogDetailClose">
            <text class="uni-dialog-button-text">关闭</text>
          </view>
        </view>
      </view>
    </uni-popup>
    
    <!-- 详情弹窗 -->
    <uni-popup id="popup" ref="popup" type="bottom" >
    	<view class="popup__content" style="bottom: 0;padding-top:0;padding-bottom:0; max-height: 86vh;background-color: #fff;border-radius: 16rpx 16rpx;overflow: hidden;">
    		<!-- <view class="popup-close" @click="popupdetailClose">
    			<image :src="`${pre_url}/static/img/hotel/popupClose.png`"></image>
    		</view> -->
        <dd-tab :itemdata="['产品说明','行李规定','退改规则']" :itemst="['0','1','2']" :st="st2" :showstatus="showstatus2" :isfixed="true" @changetab="changetab2"></dd-tab>
        <view style="width:100%;height:100rpx"></view>
    		<scroll-view :scrollIntoView="intoviewid" :scrollWithAnimation="true" scroll-y style="height: auto;max-height:calc(86vh - 130rpx);;">
          <view v-if="guize" style="padding:0 20rpx;">
            <view id="scrollid0" class="popup_title">产品说明</view>
            <view style="border: 2rpx solid #f1f1f1;border-radius: 8rpx 8rpx;">
                <view :style="'padding: 20rpx;background:rgba('+t('color1rgb')+',0.4)'">票价/其他说明</view>
                <view v-if="guize.baoxiaocontent" style="border-top: 2rpx solid #f1f1f1;">
                  <view style="display: flex;align-items: center;">
                    <view class="popup-item-title" >
                      报销凭证
                    </view>
                    <view class="popup-item-content">
                      {{guize.baoxiaocontent}}
                    </view>
                  </view>
                </view>
                <view v-if=" guize.pricecontent" style="border-top: 2rpx solid #f1f1f1;">
                  <view style="display: flex;align-items: center;">
                    <view class="popup-item-title" >
                      价格说明
                    </view>
                    <view class="popup-item-content">
                      {{guize.pricecontent}}
                    </view>
                  </view>
                </view>
            </view>
            
            <view id="scrollid1" class="popup_title">行李规定</view>
            <view style="border: 2rpx solid #f1f1f1;border-radius: 8rpx 8rpx;">
                <view :style="'padding: 20rpx;background:rgba('+t('color1rgb')+',0.4)'">托运/手提行李</view>
    
                <view v-if="guize.checkedluggage" style="display: flex;align-items: center;border-top: 2rpx solid #f1f1f1;">
                  <view class="popup-item-title" >
                    托运行李
                  </view>
                  <view class="popup-item-content">
                    {{guize.checkedluggage}}
                  </view>
                </view>
    
                <view v-if="guize.cabinluggage" style="display: flex;align-items: center;border-top: 2rpx solid #f1f1f1;">
                  <view class="popup-item-title" >
                    手提行李
                  </view>
                  <view class="popup-item-content">
                    {{guize.cabinluggage}}
                  </view>
                </view>
    
                <view v-if="guize.infantluggage" style="display: flex;align-items: center;border-top: 2rpx solid #f1f1f1;">
                  <view class="popup-item-title" >
                    婴儿行李
                  </view>
                  <view class="popup-item-content">
                    {{guize.infantluggage}}
                  </view>
                </view>
                
                <view v-if="guize.luggage" style="display: flex;align-items: center;border-top: 2rpx solid #f1f1f1;">
                  <view class="popup-item-title" >
                    其他说明
                  </view>
                  <view class="popup-item-content">
                    {{guize.luggage}}
                  </view>
                </view>
            </view>
            
            <view id="scrollid2" class="popup_title">退改规则</view>
            <view style="border: 2rpx solid #f1f1f1;border-radius: 8rpx 8rpx;">
                <view :style="'padding: 20rpx;background:rgba('+t('color1rgb')+',0.4)'">退改费用/规则</view>
    
                <view v-if="guize.refundStipulate" style="display: flex;align-items: center;border-top: 2rpx solid #f1f1f1;">
                  <view class="popup-item-title" >
                    退改费
                  </view>
                  <view class="popup-item-content">
                    <block v-if="guize.refundStipulate.rules && guize.refundStipulate.rules.length>0">
                      <view v-for="(item,index) in guize.refundStipulate.rules" :key="index">
                        {{item.txt}} ￥{{item.charge}}/人
                      </view>
                    </block>
                    <block v-else>
                      <view v-if="guize.refundStipulate.comment">{{guize.refundStipulate.comment}}</view>
                    </block>
                  </view>
                </view>
                <view v-if="guize.changeStipulate" style="display: flex;align-items: center;border-top: 2rpx solid #f1f1f1;">
                  <view class="popup-item-title" >
                    同舱改签票
                  </view>
                  <view class="popup-item-content">
                    <block v-if="guize.changeStipulate.rules && guize.changeStipulate.rules.length>0">
                      <view v-for="(item,index) in guize.changeStipulate.rules" :key="index">
                        {{item.txt}} ￥{{item.charge}}/人
                      </view>
                    </block>
                    <block v-else>
                      <view v-if="guize.changeStipulate.comment">{{guize.changeStipulate.comment}}</view>
                    </block>
                  </view>
                </view>
    
                <view v-if="guize.modifyStipulate" style="display: flex;align-items: center;border-top: 2rpx solid #f1f1f1;">
                  <view class="popup-item-title" >
                    签转
                  </view>
                  <view class="popup-item-content">
                    {{guize.modifyStipulate}}
                  </view>
                </view>
                <view v-if="guize.othercontent" style="display: flex;align-items: center;border-top: 2rpx solid #f1f1f1;">
                  <view class="popup-item-title" >
                    其他说明
                  </view>
                  <view class="popup-item-content">
                    {{guize.othercontent}}
                  </view>
                </view>
            </view>
          </view>
    			<view style="width: 100%;height: 10rpx"></view>
    		</scroll-view>
        
    	</view>
    </uni-popup>
    
    <view v-if="invoiceShow" class="popup__container">
    	<view class="popup__overlay" @tap.stop="handleClickMask"></view>
    	<view class="popup__modal">
    		<view class="popup__title">
    			<text class="popup__title-text">请填写开票信息</text>
    			<image :src="pre_url+'/static/img/close.png'" class="popup__close" style="width:36rpx;height:36rpx"
    				@tap.stop="handleClickMask" />
    		</view>
    		<view class="popup__content invoiceBox">
    			<form @submit="invoiceFormSubmit" @reset="formReset" report-submit="true">
    				<view class="orderinfo">
    					<view class="item">
    						<text class="t1">发票类型</text>
    						<view class="t2">
    								<radio-group class="radio-group" @change="changeOrderType" name="invoice_type">
    								<label class="radio" v-if="inArray(1,invoice_type)">
    									<radio value="1" :checked="invoice_type_select == 1 ? true : false"></radio>普通发票
    								</label>
    								<label class="radio" v-if="inArray(2,invoice_type)">
    									<radio value="2" :checked="invoice_type_select == 2 ? true : false"></radio>增值税专用发票
    								</label>
    								</radio-group>
    						 </view>
    					</view>
    					<view class="item">
    						<text class="t1">抬头类型</text>
    						<view class="t2">
    							<block v-if="inputDisabled">
    								<text v-if="invoicedata && invoicedata.name_type == 1">个人</text>
    								<text v-if="invoicedata && invoicedata.name_type == 2">公司</text>
    							</block>
    							<block v-else>
    								<radio-group class="radio-group" @change="changeNameType" name="name_type">
    								<label class="radio">
    									<radio value="1" :checked="name_type_select == 1 ? true : false" :disabled="name_type_personal_disabled ? true : false"></radio>个人
    								</label>
    								<label class="radio">
    									<radio value="2" :checked="name_type_select == 2 ? true : false"></radio>公司
    								</label>
    								</radio-group>
    							</block>
    						</view>
    					</view>
    					<view class="item">
    						<text class="t1">抬头名称</text>
    						<input class="t2" type="text" placeholder="抬头名称" placeholder-style="font-size:28rpx;color:#BBBBBB" name="invoice_name" :disabled="inputDisabled" :value="invoicedata ? invoicedata.invoice_name : ''" ></input>
    					</view>
    					<view class="item" v-if="name_type_select == 2">
    						<text class="t1">公司税号</text>
    						<input class="t2" type="text" placeholder="公司税号" placeholder-style="font-size:28rpx;color:#BBBBBB" name="tax_no" :disabled="inputDisabled" :value="invoicedata ? invoicedata.tax_no : ''"></input>
    					</view>
    					<view class="item" v-if="invoice_type_select == 2">
    						<text class="t1">注册地址</text>
    						<input class="t2" type="text" placeholder="注册地址" placeholder-style="font-size:28rpx;color:#BBBBBB" name="address" :disabled="inputDisabled" :value="invoicedata ? invoicedata.address : ''"></input>
    					</view>
    					<view class="item" v-if="invoice_type_select == 2">
    						<text class="t1">注册电话</text>
    						<input class="t2" type="text" placeholder="注册电话" placeholder-style="font-size:28rpx;color:#BBBBBB" name="tel" :disabled="inputDisabled" :value="invoicedata ? invoicedata.tel : ''"></input>
    					</view>
    					<view class="item" v-if="invoice_type_select == 2">
    						<text class="t1">开户银行</text>
    						<input class="t2" type="text" placeholder="开户银行" placeholder-style="font-size:28rpx;color:#BBBBBB" name="bank_name" :disabled="inputDisabled" :value="invoicedata ? invoicedata.bank_name : ''"></input>
    					</view>
    					<view class="item" v-if="invoice_type_select == 2">
    						<text class="t1">银行账号</text>
    						<input class="t2" type="text" placeholder="银行账号" placeholder-style="font-size:28rpx;color:#BBBBBB" name="bank_account" :disabled="inputDisabled" :value="invoicedata ? invoicedata.bank_account : ''"></input>
    					</view>
    					<view class="item">
    						<text class="t1">手机号</text>
    						<input class="t2" type="text" placeholder="接收电子发票手机号" placeholder-style="font-size:28rpx;color:#BBBBBB" name="mobile" :disabled="inputDisabled" :value="invoicedata ? invoicedata.mobile : ''"></input>
    					</view>
    					<view class="item">
    						<text class="t1">邮箱</text>
    						<input class="t2" type="text" placeholder="接收电子发票邮箱" placeholder-style="font-size:28rpx;color:#BBBBBB" name="email" :disabled="inputDisabled" :value="invoicedata ? invoicedata.email : ''"></input>
    					</view>
    				</view>
            <view style="display: flex;justify-content: space-between;">
              <button class="btn" form-type="reset" style="width: 45%;" :style="{color:t('color1'),border:'2rpx solid '+t('color1')}">重置</button>
              <button class="btn" form-type="submit" style="width: 45%;" :style="{background:t('color1')}">确定</button>
            </view>
    				<view style="padding-top:30rpx"></view>
    			</form>
    		</view>
    	</view>
    </view>
    
    <loading v-if="loading"></loading>
		<dp-tabbar :opt="opt"></dp-tabbar>
		<popmsg ref="popmsg"></popmsg>
	</view>
</template>

<script>
	var app = getApp();
	export default {
		data(){
			return{
				isload:false,
        nodata:false,
				pre_url:app.globalData.pre_url,
        menuindex: -1,
				timeIndex:1,
        
        flightsdata:'',
        flightspricedata:'',
        
        searchNo:'',//查询号
        flightNo:'',//航班号
        cabin:'',//舱位
        cabinNo:'',//舱位查询码
        
        fromCityname:'',//出发地名称
        toCityname:'',//目的地名称
        fromDate:'',
        week:'',
        
        discount:0,//折扣
        
        adtnum:0,//成人数量 用以计算婴儿票
        chdnum:0,//儿童数量
        infnum:0,//婴儿数量

        minSalePrice:0,//成人票价
        minSalePricenum:0,//成人票数量
        minSalePrice1:0,//儿童票价
        minSalePricenum1:0,//儿童票数量
        minSalePrice2:0,//婴儿票价
        minSalePricenum2:0,//婴儿票数量
        
        taxFee:0,//成人机建费
        taxFeenum:0,//成人机数量
        taxFee1:0,//儿童机建费
        taxFeenum1:0,//儿童机数量
        taxFee2:0,//婴儿机建费
        taxFeenum2:0,//婴儿机数量
        
        fuelFee:0,//成人燃油费
        fuelFeenum:0,//成人燃数量
        fuelFee1:0,//儿童燃油费
        fuelFeenum1:0,//儿童燃数量
        fuelFee2:0,//婴儿燃油费
        fuelFeenum2:0,//婴儿燃数量
        
        fdPrice : 0,//公布的运价
        fdPrice2 : 0,//公布的运价，用于计算儿童、婴儿票
  
        xieyidata:'',
        
        st:0,
        showstatus:[1,1],
        minprice:-1,
        minprice2:-1,
        
        st2:0,
        showstatus2:[1,1,1],
        intoviewid:'',
        guize:'',
        
        contacts:'',
        mobile:'',
        
        //计算服务费
        isvip:false,//是否是会员
        servetype : 0,
        serveprice: 0,
        serveprice:0,//服务费参数
        servefee:0,//服务费
        
        userdata:[],//旅客
        
        totalprice:0,
        isagree:false,
        showxieyi:false,
        xycontent:'',
        
        ispost:false,
        
        //发票
        invoice:false,
        invoiceShow:false,
        invoice_type:[],
        invoice_type_select:1,
        name_type_select:1,
        name_type_personal_disabled:false,
        invoicedata:'',
			}
		},
    onLoad: function (opt) {
      var that = this;
    	that.opt = app.getopts(opt);
      
      var flightsdata = app.getCache('flightsdata') || '';
      var flightspricedata = app.getCache('flightspricedata') || '';
      if(!flightsdata || !flightspricedata){
        app.error('数据已失效');
        setTimeout(function(){
         uni.navigateBack({ delta: 2 })
        },900)
        return;
      }
      that.flightsdata = flightsdata;
      that.flightspricedata = flightspricedata;
      
      that.searchNo= that.opt.searchNo || '';
      that.flightNo= flightsdata.no || '';
      
      that.fromCityname= that.opt.fromCityname || '';
      that.toCityname  = that.opt.toCityname || '';
      that.fromDate    = that.opt.fromDate || '';
      that.week        = that.opt.week || '';
      //flightspricedata
      that.cabin   = flightspricedata.cabin || '';
      that.cabinNo = flightspricedata.cabinNo || '';
      that.discount = flightspricedata.discount || 0;
      that.minSalePrice = flightspricedata.minSalePrice || 0;
      that.fdPrice = flightspricedata.fdPrice || 0;
      that.fdPrice2 = flightspricedata.fdPrice2 || 0;
      //flightsdata
      that.taxFee  = flightsdata.taxFee || 0;
      that.fuelFee = flightsdata.fuelFee || 0;
      
      that.getdata();
    },
    onShow:function(){
      var that = this;
      that.adtnum = that.chdnum = that.infnum = 0;
      var pages = getCurrentPages(); //获取加载的页面
      var currentPage = pages[pages.length - 1]; //获取当前页面的对象
      if(currentPage && currentPage.$vm.userdata){
        var userdata = currentPage.$vm.userdata;
        
        var num = userdata?userdata.length:0;
        if(num>0){
          for(var i=0;i<num;i++){
            if(userdata[i]['passengerType'] == 1){
              that.chdnum ++;
            }else if(userdata[i]['passengerType'] == 2){
              that.infnum ++;
            }else if(userdata[i]['passengerType'] == 0){
              that.adtnum ++;
            }
          }
        }
        that.userdata = userdata;
        that.calculatePrice();
      }
    },
		methods:{
      getdata: function () {
        var that = this;
      	that.nodata = false;
      	that.nomore = false;
      	that.loading = true;
        app.post('ApiHanglvfeike/buy', {fromDate:that.fromDate,searchNo:that.searchNo,flightNo:that.flightNo}, function (res) {
      		that.loading = false;
          if(res.status == 1){
            that.contacts = res.contacts || '';
            that.mobile = res.mobile || '';
            that.guize = res.guize || '';
            that.xieyidata = res.xieyidata || '';
            that.isvip = res.isvip || false;
            that.servetype  =  res.servetype || 0;
            that.serveprice =  res.serveprice || 0;
            that.invoice    = res.invoice;
            that.invoice_type = res.invoice_type
            that.calculatePrice();
            that.loaded();
          }else if(res.status == 2){
            app.error(res.msg);
            setTimeout(function(){
              uni.navigateBack({ delta: 2 })
            },900)
          }else {
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
      calculatePrice:function(){
        var that = this;
        //重置数据
        //票
        that.minSalePricenum=that.minSalePrice1=that.minSalePricenum1=that.minSalePrice2=that.minSalePricenum2 = 0;
        //机建
        that.taxFeenum=that.taxFee1=that.taxFeenum1=that.taxFee2=that.taxFeenum2 = 0;
        //燃油
        that.fuelFeenum=that.fuelFee1=that.fuelFeenum1=that.fuelFee2=that.fuelFeenum2 = 0;

        var totalprice = 0;
        
        var taxFee  = parseFloat(that.taxFee);//机建
        var fuelFee = parseFloat(that.fuelFee);//燃油
        
        //折扣后的最低售价
        var minSalePrice = parseFloat(that.minSalePrice);
        //折扣
        var discount = parseFloat(that.discount);

        //公布的运价，用于计算全票价格
        var fdPrice2 = parseFloat(that.fdPrice2);
        //计算全票价格
        var allprice = discount && discount>0?fdPrice2/discount*10:fdPrice2;
        allprice = Math.round(allprice);
        
        //儿童，全票价的50%
        var minSalePrice1 = allprice*0.5;
        minSalePrice1 = Math.round(minSalePrice1);
        //儿童，燃油费的50%
        var fuelFee1 = fuelFee*0.5;
        fuelFee1 = Math.round(fuelFee1);
        
        //婴儿，全票价的10%
        var minSalePrice2  = allprice*0.1;
        minSalePrice2 = Math.round(minSalePrice2);
        
        var userdata = that.userdata;
        var num = userdata?userdata.length:0;
        if(num>0){
          //成人
          if(that.adtnum>0){
            //票价 机建 燃油费人数
            that.minSalePricenum = that.taxFeenum = that.fuelFeenum = that.adtnum;

            totalprice += (minSalePrice + taxFee + fuelFee) * that.adtnum;
          }

          //儿童 全票价的50% ，免收机建费，燃油费的50%
          if(that.chdnum>0){
            that.minSalePrice1 = minSalePrice1;
            that.fuelFee1 = fuelFee1;
            that.minSalePricenum1 = that.fuelFeenum1 = that.chdnum;
            
            totalprice += (minSalePrice1 + fuelFee1) * that.chdnum ;
          }
          
          //婴儿数量，全票价的10% ，免收机建费，免收燃油费
          if(that.infnum>0){
              //婴儿超出成人数量，超出的购买儿童票
              //未超出的按儿童票买，全票价的10% ，免收机建费，免收燃油费
              var cha = that.infnum - that.adtnum;
              if(cha>0){
                  //儿童
                  that.minSalePrice1 = minSalePrice1;
                  that.fuelFee1 = fuelFee1;
                  that.minSalePricenum1 += cha;
                  that.fuelFeenum1 += cha;

                  totalprice += (minSalePrice1 + fuelFee1) * cha;
                  
                  //婴儿
                  that.minSalePrice2 = minSalePrice2;
                  that.minSalePricenum2 = that.adtnum;

                  totalprice += minSalePrice2 * that.adtnum;
              }else{
                //婴儿
                that.minSalePrice2 = minSalePrice2;
                that.minSalePricenum2 = that.infnum;

                totalprice += minSalePrice2 * that.infnum;
              }
          }
        }

        if(!that.isvip){
          //固定金额
          if(that.servetype == 1){
            var serveprice = that.serveprice;
          //比例
          }else{
            var serveprice = totalprice * that.serveprice/100;
          }
          serveprice = Math.round(serveprice*100)/100;
          totalprice += serveprice;
          that.servefee = serveprice;
        }
        
        totalprice = Math.round(totalprice*100)/100;
        if(totalprice<0) totalprice = 0;

        that.totalprice = totalprice;
      },
      toSelectDate(date,otherParam){
        var that = this;
        app.goto('/pagesExt/checkdate/checkDate?date='+date+'&dayin=0&dayin2='+that.showday+'&type=1&otherParam='+otherParam);
      },
      getguize: function () {
        var that = this;
      	that.nodata = false;
      	that.nomore = false;
      	that.loading = true;
        app.post('ApiHanglvfeike/guize', {searchNo:that.searchNo,flightNo:that.flightNo,cabinNo:that.cabinNo}, function (res) {
      		that.loading = false;
          if(res.status == 1){
            that.guize = res.guize;
            that.$refs.popup.open();
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
      popupdetailClose(){
      	this.$refs.popup.close();
      },
      changetab: function (e) {
        var st = e;
        this.st = st;
      },
      changetab2: function (e) {
        var st2 = e;
        this.st2 = st2;
        this.intoviewid = 'scrollid'+st2;
      },
      deluser:function(e){
        var that = this;
        var index  = e.currentTarget.dataset.index;
        var userdata = that.userdata;
        userdata.splice(index, 1);
        that.userdata = userdata;
        that.calculatePrice();
      },
      isagreeChange: function (e) {
        var val = e.detail.value;
        if (val.length > 0) {
          this.isagree = true;
        } else {
          this.isagree = false;
        }
      },
      showXieyi:function(index){
        var that = this;
        var xieyidata = that.xieyidata;
        that.xycontent = xieyidata[index].content;
        that.showxieyi = true;
      },
      hidexieyi: function () {
        this.showxieyi = false;
      	this.isagree = true;
      },
      dialogDetailOpen:function(){
        this.$refs.dialogDetail.open();
      },
      dialogDetailClose:function(){
        this.$refs.dialogDetail.close();
      },
      inputContacts:function(e){
        this.contacts = e.detail.value;
      },
      inputMobile:function(e){
        this.mobile = e.detail.value;
      },
      changeNameType: function(e) {
      	var that = this;
      	var value = e.detail.value;
      	that.name_type_select = value;
      },
      formReset:function(){
        var that = this;
        that.invoicedata = '';
        that.invoice_type_select = 1;
        that.name_type_select = 1;
        that.name_type_personal_disabled = false;
      },
      showInvoice: function(e) {
      	this.invoiceShow = true;
      },
      handleClickMask: function() {
      	this.invoiceShow = false;
      },
      changeOrderType: function(e) {
      	var that = this;
      	var value = e.detail.value;
      	if(value == 2) {
      		that.name_type_select = 2;
      		that.name_type_personal_disabled = true;
      	} else {
      		that.name_type_personal_disabled = false;
      	}
      	that.invoice_type_select = value;
      },
      invoiceFormSubmit: function (e) {
        var that = this;
      	var formdata = e.detail.value;
      	if(formdata.invoice_name == '') {
      		app.error('请填写抬头名称');
      		return;
      	}
      	if((formdata.name_type == 2 || formdata.invoice_type == 2) && formdata.tax_no == '') {
      		///^[A-Z0-9]{15}$|^[A-Z0-9]{17}$|^[A-Z0-9]{18}$|^[A-Z0-9]{20}$/
      		app.error('请填写公司税号');
      		return;
      	}
      	if(formdata.invoice_type == 2) {
      		if(formdata.address == '') {
      			app.error('请填写注册地址');
      			return;
      		}
      		if(formdata.tel == '') {
      			app.error('请填写注册电话');
      			return;
      		}
      		if(formdata.bank_name == '') {
      			app.error('请填写开户银行');
      			return;
      		}
      		if(formdata.bank_account == '') {
      			app.error('请填写银行账号');
      			return;
      		}
      	}
      	if (formdata.mobile != '') {
      		if(!app.isPhone(formdata.mobile)){
      			app.error("手机号码有误，请重填");
      			return;
      		}
      	}
      	if (formdata.email != '') {
      		if(!/^([A-Za-z0-9_\-\.])+\@([A-Za-z0-9_\-\.])+\.([A-Za-z]{2,4})$/.test(formdata.email)){
      			app.error("邮箱有误，请重填");
      			return;
      		}
      	}
      	if(formdata.mobile == '' && formdata.email == '') {
      		app.error("手机号和邮箱请填写其中一个");
      		return;
      	}
      	that.invoicedata = formdata;
      	that.invoiceShow = false;
      },
      topay:function(e) {
      	var that = this;
        var userdata = that.userdata;
        var len = userdata.length;
        if(!userdata || len<=0){
          app.alert('请添加旅客');
        }
        var userids = '';
        for(var i = 0;i<len;i++){
          if(userids){
            userids += ','+userdata[i]['id'];
          }else{
            userids = userdata[i]['id'];
          }
        }
        var xieyidata = that.xieyidata;
        if(xieyidata && xieyidata.length>0 && !that.isagree){
          app.alert('请先阅读并同意协议内容');return;
        }
        if(that.ispost) return;
        that.ispost = true;
        app.showLoading('提交中');
      	app.post('ApiHanglvfeike/createOrder', {
      		searchNo: that.searchNo,
          flightNo: that.flightNo,
      		cabin: that.cabin,
      		cabinNo: that.cabinNo,
          userids:userids,
      		contacts: that.contacts,
          mobile:that.mobile,
          invoicedata:that.invoicedata,
      	}, function(res) {
          app.showLoading(false);
          setTimeout(function(){
            that.ispost = false;
          },1000)
      		if (res.status == 1) {
      			app.goto('/pagesExt/pay/pay?id=' + res.payorderid);
      			return;
      		}else if (res.status == 3) {
      			app.alert(res.msg, function() {
      				app.goto(res.url);
      			});
      			return;
      		}else{
            app.error(res.msg);
            return;
          }
      	});
      }
		}
	}
</script>

<style>
  radio{transform:scale(.7);}
  checkbox{transform:scale(.7);}
	.page-view{background: #fbf6f6;width: 100%;height: 100vh;}
	.top-view{width: 100%;background: #fff;}
	.top-view .address-view{justify-content: center;}
	.top-view .address-view .address-text{font-size: 40rpx;color: #676767;flex: 1;}
	.top-view .address-view .fangxiang-icon{width: 50rpx;height:50rpx;margin: 50rpx 40rpx;}
	.top-view .address-view .fangxiang-icon image{width: 50rpx;height:50rpx;}
	.top-view .time-view{width: 100%;justify-content: space-between;padding-bottom: 5rpx;}
	.top-view .time-view .time-left-view{width: calc(100% - 130rpx);}
	.top-view .time-view .time-left-view .time-options{width: 120rpx;height: 130rpx;border-radius: 10rpx;justify-content: space-between;padding: 5rpx;}
	.top-view .time-view .time-left-view .time-options-active{background-color: #af1e24;color: #fff !important;}
	.top-view .time-view .time-left-view .time-options-active .time-title{color: #fff !important;}
	.top-view .time-view .time-left-view .time-options-active .time-num{color: #fff !important;}
	.time-options .time-title{width: 100%;text-align: center;font-size: 26rpx;color: #838383;}
	.time-options	.time-num{color: #333;font-size: 26rpx;width: 100%;text-align: center;}
	.top-view .time-view .time-right-view{width: 100rpx;height: 130rpx;box-shadow: -4px 0px 14px -14px rgba(0,0,0,.7);}
	.top-view .time-view .time-right-view .rili-icon{width: 46rpx;height: 46rpx;}
	.jipiao-list-view{width: 100%;}
	.jipiao-list-view .jipiao-options{background: #fff;width: 92%;margin: 10rpx auto;border-radius: 16rpx;padding: 25rpx;}
	.jipiao-list-view .jipiao-options .info-view{width: 100%;justify-content: space-between;align-items: center;}
	.jipiao-list-view .jipiao-options .info-view .info-touxiang{width: 50rpx;height: 50rpx;}
	.jipiao-list-view .jipiao-options .info-view .info-touxiang image{width: 100%;height: 100%;}
	.jipiao-list-view .jipiao-options .info-view .info-details-view{}
	.info-details-view .location-icon{width: 120rpx;position: relative;margin: 0rpx 25rpx 15rpx;}
	.info-details-view .location-icon image{width: 120rpx;}
	.info-details-view .location-icon .stop-tag{border: 1px #9c9c9c solid;position: absolute;bottom: -25rpx;left: 50%;transform: translateX(-50%);
	font-size: 24rpx;color: #7c7c7c;border-radius: 4rpx;padding: 0rpx 4rpx;white-space: nowrap;}
	.info-details-view .location-view{align-items: center;}
	.info-details-view .location-view .location-time{font-size: 44rpx;color: #333;font-weight: bold;}
	.info-details-view .location-view .location-name{font-size: 26rpx;color: #676767;margin-top: 5rpx;}
	.jipiao-list-view .jipiao-options .info-view .price-view{}
	.price-view .price-name{font-size: 26rpx;color: #353535;margin-top: 5rpx;}
	.price-view .price-num{color: #ff771b;font-weight: bold;}
	.jipiao-list-view .jipiao-options .jipiao-introduce{width: 100%;text-align: center;font-size: 26rpx;color: #676767;padding-top: 15rpx;}
	/* 共享售卖 */
	.shared-selling-view{border-radius: 16rpx;padding: 25rpx;width: 100%;margin-top: 15rpx;background: #f9f9f9;}
	.shared-selling-view .shared-title{width: 100%;padding-bottom: 15rpx;}
	.shared-selling-view .shared-title .feiji-icon{width: 28rpx;height: 28rpx;margin-right: 5rpx;}
	.shared-selling-view .shared-title .feiji-icon image{width: 100%;height: 100%;font-size: 28rpx;color: #676767;}
	.shared-selling-view .shared-title .title-text-view{white-space: nowrap;overflow: hidden;text-overflow: ellipsis;}
	.shared-selling-view .sell-options{width: 100%;border-bottom: 1px #eeefef solid;align-items: center;justify-content: space-between;padding: 20rpx 15rpx;}
	.shared-selling-view .sell-options .sell-left-view{justify-content: flex-start;}
	.sell-left-view .sell-touxiang{width: 28rpx;height: 28rpx;margin-right: 10rpx;}
	.sell-left-view .sell-touxiang image{width: 100%;height: 100%;}
	.sell-left-view .sell-name{font-size: 26rpx;color: #676767;}
	.shared-selling-view .sell-options .sell-price{font-size: 30rpx;color: #ff771b;}
	.shared-selling-view .sell-options .jiantou-icon{width: 28rpx;height: 28rpx;margin-left: 5rpx;}
	.shared-selling-view .more-view{width: 100%;align-items: center;justify-content: center;padding: 15rpx 0rpx 0rpx;font-size: 28rpx;color: #d6d6d6;}
	.shared-selling-view .more-view image{width: 40rpx;height: 40rpx;margin-left: 10rpx;}
  
  .popup__content{width: 100%;height:auto;position: relative;}
  .popup__content .popup-close{position: fixed;right: 20rpx;top: 20rpx;width: 56rpx;height: 56rpx;z-index: 11;}
  .popup__content .popup-close image{width: 100%;height: 100%;}
  .popup_title{font-size: 36rpx;font-weight: bold;line-height: 80rpx;}
  .popup-item-title{width: 180rpx;text-align: center;padding: 20rpx 10rpx;border-right: 2rpx solid #f1f1f1;}
  .popup-item-content{width: 160rpx;padding: 20rpx  10rpx;width: 100%;color:#999}
  
  .xycss1{line-height: 40rpx;font-size: 24rpx;overflow: hidden;padding: 20rpx 0;background: #fff;display: flex;align-items: center;border-bottom: 2rpx solid #f1f1f1;}
  .xieyibox{width:100%;height:100%;position:fixed;top:0;left:0;z-index:99;background:rgba(0,0,0,0.7)}
  .xieyibox-content{width:90%;margin:0 auto;/*  #ifdef  MP-TOUTIAO */height:60%;/*  #endif  *//*  #ifndef  MP-TOUTIAO */height:80%;/*  #endif  */margin-top:20%;background:#fff;color:#333;padding:5px 10px 50px 10px;position:relative;border-radius:2px;}
  .footer {width: 96%;background: #fff;margin-top: 5px;position: fixed;left: 0px;bottom: 0px;padding: 0 2%;z-index: 8;box-sizing:content-box}
  .footer .text1 {height: 110rpx;line-height: 110rpx;color: #2a2a2a;font-size: 30rpx;}
  .footer .text1 text {color: #e94745;font-size: 32rpx;}
  .footer .op {width: 200rpx;height: 80rpx;line-height: 80rpx;color: #fff;text-align: center;font-size: 30rpx;border-radius: 44rpx}
  .footer .op[disabled] { background: #aaa !important; color: #666;}
  .footerTop {bottom: 110rpx; display:inline-block;font-size:22rpx;height:44rpx;line-height:44rpx;padding:0 20rpx}
  
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
  
  .orderinfo{width:94%;margin:0 3%;border-radius:8rpx;margin-top:16rpx;padding: 14rpx 3%;background: #FFF;}
  .orderinfo .item{display:flex;width:100%;padding:20rpx 0;border-bottom:1px dashed #ededed;overflow:hidden}
  .orderinfo .item:last-child{ border-bottom: 0;}
  .orderinfo .item .t1{width:200rpx;flex-shrink:0}
  .orderinfo .item .t2{flex:1;text-align:right}
  .orderinfo .item .red{color:red}
  .btn{ height:80rpx;line-height: 80rpx;width:90%;margin:0 auto;border-radius:40rpx;margin-top:40rpx;color: #fff;font-size: 28rpx;font-weight:bold}
  .stop-tag{border: 1px #9c9c9c solid;position: absolute;top:0rpx;left: 50%;transform: translateX(-50%);
  font-size: 24rpx;color: #7c7c7c;border-radius: 4rpx;padding: 0rpx 4rpx;white-space: nowrap;}
</style>