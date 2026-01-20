<template>
	<view class="container">
		<block v-if="isload">
			<form @submit="topay">
        <block v-if="!protype">
          <view v-if="sindex==1 || sindex==3" class="address-add">
          	<view class="linkitem">
          		<text class="f1">联 系 人：</text>
          		<input type="text" class="input" :value="linkman" placeholder="请输入您的姓名" @input="inputLinkman" placeholder-style="color:#626262;font-size:28rpx;"/>
          	</view>
          	<view class="linkitem">
          		<text class="f1">联系电话：</text>
          		<input type="text" class="input" :value="tel" placeholder="请输入您的手机号" @input="inputTel" placeholder-style="color:#626262;font-size:28rpx;"/>
          	</view>
          </view>
          <view v-else class="address-add flex-y-center" @tap="goto"
          	:data-url="'/pagesB/address/'+(address.id ? 'address' : 'addressadd')+'?fromPage=buy&type=1'">
          	<view class="f1">
          		<image class="img" :src="pre_url+'/static/img/address.png'" />
          	</view>
          	<view class="f2 flex1" v-if="address.id">
          		<view style="font-weight:bold;color:#111111;font-size:30rpx">{{address.name}} {{address.tel}} <text v-if="address.company">{{address.company}}</text></view>
          		<view style="font-size:24rpx">{{address.area}} {{address.address}}</view>
          	</view>
          	<view v-else class="f2 flex1">请选择您的地点</view>
          	<image :src="pre_url+'/static/img/arrowright.png'" class="f3"></image>
          </view>
        </block>
        
        <view v-if="sindex==3" class="address-add flex-y-center" @tap="goto" :data-url="'/pagesB/yuyue/selectbusiness?prodata='+prodata">
        	<view class="f1">
        		<image class="img" :src="pre_url+'/static/img/address.png'" />
        	</view>
        	<view class="f2 flex1" v-if="fwbusiness.id">
        		<view style="font-weight:bold;color:#111111;font-size:30rpx">{{fwbusiness.name}}</view>
        	</view>
        	<view v-else class="f2 flex1">请选择要去的商家</view>
        	<image :src="pre_url+'/static/img/arrowright.png'" class="f3"></image>
        </view>

				<view v-for="(buydata, index) in allbuydata" :key="index" class="buydata">	
					<view v-if="sindex==1" class="business-info">
						<view class="linkitem">
							<text class="f1">店铺名称：</text>
							<text class="f2">{{buydata.business.name}}</text>
						</view>
						<view class="linkitem">
							<text class="f1">店铺地址：</text>
							<text class="f2"><text v-if="buydata.business.province !='北京市' || buydata.business.province !='上海市' || buydata.business.province !='重庆市' || buydata.business.province !='天津市' ">{{buydata.business.province}}</text>
								{{buydata.business.city}}{{buydata.business.district}}{{buydata.business.address}}
							</text>
						</view>
					</view>
					
					<view class="bcontent">
						<view class="btitle" v-if="protype ==0">
							服务信息
						</view>
						<view class="product" v-if="protype ==0">
							<view v-for="(item, index2) in buydata.prodata" :key="index2" class="item flex">
								<view class="img" @tap="goto" :data-url="'product?id=' + item.product.id">
									<image v-if="item.guige.pic" :src="item.guige.pic"></image>
									<image v-else :src="item.product.pic"></image>
								</view>
								<view class="info flex1">
									<view class="f1">{{item.product.name}}</view>
									<view class="f2">{{item.guige.name}}</view>
									<view class="f3"><text style="font-weight:bold;">￥{{item.guige.sell_price}}</text><text
											style="padding-left:20rpx"> × {{item.num}}</text></view>
								</view>
							</view>
						</view>
						
						<view class="freight">
							<view class="f1">服务方式</view>
							<view class="freight-ul">
								<block v-for="(item, idx2) in fwtypelist" :key="idx2">
									<view class="freight-li"
										:style="item.key==sindex?'color:'+t('color1')+';background:rgba('+t('color1rgb')+',0.2)':''"
										@tap="selectFwtype" :data-index="item.key" >{{item.name}}
									</view>
								</block>
							</view>
						</view>
						
						<view class="price" v-if="protype ==1">
							<view class="f1">选择服务规格</view>
							<view class="f2" @tap="buydialogChange" data-btntype="2">{{ggname?ggname:'请选择服务规格'}}</view>
						</view>
						<view class="price" v-if="protype ==1">
							<view class="f1">购买数量</view>
							<view class="f2">{{num}}{{danwei}}</view>
						</view>
						<view class="price" >
							<view class="f1">预约时间</view>
							<view class="f2" v-if="isdate" @tap="chooseTime" ><text v-if="yydate">{{yydate}}</text><text v-else style="color:#999">请选择预约时间</text></view>
							<view class="f2" v-else>
              {{yydate}} 
              {{yydates_num>0?yydates_num+'个时间段':''}}
              </view>
						</view>

						<view class="price" v-if="buydata.fwpeople==1 || buydata.fwpeople==3 " >
							<view class="f1">服务人员</view>
							<view class="f2" v-if="!workerid || workerid == 0" @tap="gotopeople"
					:data-url="'selectpeople?prodata='+prodata+'&yydate='+yydate+'&sindex='+sindex+'&linkman='+linkman+'&tel='+tel" > 
								{{!isEmpty(buydata.fw)?buydata.fw.realname:'请选择人员'}}<text class="iconfont iconjiantou" style="color:#999;font-weight:normal"></text>
							</view>
              <view class="f2" v-else> 
              	{{!isEmpty(buydata.fw)?buydata.fw.realname:'请选择人员'}}
              </view>
						</view>
					</view>

          <view class="bcontent2" v-if="protype && protype == 1">
            <view v-if="sindex==2">
              <view class="price">
                <text class="f1">车辆位置</text>
                <view  class="f2" style="overflow: hidden;line-height: 60rpx;" @tap="locationSelect" >
                  <text>{{carlocat_name}}</text>
                  <image :src="pre_url+'/static/img/arrowright.png'" style="width:30rpx;height:30rpx;float: right;margin-top: 16rpx;"/>
                </view>
              </view>
              <view class="price">
                <text class="f1">停靠位置</text>
                <view  class="f2" style="overflow: hidden;line-height: 60rpx;padding-left:20rpx" >
                  <input type="text" @input="inputCarlocatStop" name="carlocat_stop" class="input" placeholder="请输入车辆停靠位置" :value="carlocat_stop"  placeholder-style="font-size:28rpx"/>
                </view>
              </view>
            </view>
          	<view class="price">
          		<text class="f1">车辆信息</text>
              <view  class="f2" style="overflow: hidden;line-height: 60rpx;" @tap="goto" :data-url="'/pagesA/yuyuecar/car'">
                <text>{{carinfor?carinfor.infor:''}}</text>
                <image :src="pre_url+'/static/img/arrowright.png'" style="width:30rpx;height:30rpx;float: right;margin-top: 16rpx;"/>
              </view>
          	</view>
          </view>

					<view class="bcontent2">
						<view class="price" v-if="buydata.leveldk_money>0">
							<text class="f1">{{t('会员')}}折扣({{userinfo.discount}}折)</text>
							<text class="f2">-¥{{buydata.leveldk_money}}</text>
						</view>
						<view class="price" v-if="yyset.iscoupon==1">
							<view class="f1">{{t('优惠券')}}</view>
							<view v-if="buydata.couponCount > 0" class="f2" @tap="showCouponList" :data-bid="buydata.bid">
								<text
									style="color:#fff;padding:4rpx 16rpx;font-weight:normal;border-radius:8rpx;font-size:24rpx"
									:style="{background:t('color1')}">{{buydata.couponrid!=0?buydata.couponList[buydata.couponkey].couponname:buydata.couponCount+'张可用'}}</text><text
									class="iconfont iconjiantou" style="color:#999;font-weight:normal"></text>
							</view>
							<text class="f2" v-else style="color:#999">无可用{{t('优惠券')}}</text>
						</view>

						<view class="price">
							<text class="f1">服务价格</text>
							<text class="f2">¥{{buydata.product_price}}</text>
						</view>
						<view v-if="buydata.prodata[0].product.balance>0" class="price">
							<text class="f1">应付定金</text>
							<text class="f2">¥{{buydata.sell_price}}</text>
						</view>
						
						<view class="price" v-if="buydata.coupontype==3">
							<text class="f1">计次卡</text>
							<text class="f2" style="color: red;">-{{buydata.product_price}}</text>
						</view>
							
							
						<view style="display:none">{{test}}</view>
						<view class="form-item" v-for="(item,idx) in buydata.formdata" :key="item.id">
							<view class="label">{{item.val1}}<text v-if="item.val3==1" style="color:red"> *</text></view>
							<block v-if="item.key=='input'">
								<input type="text" :name="'form'+buydata.bid+'_'+idx" class="input" :placeholder="item.val2" :value="buydata.editorFormdata[idx]" @input="inputChange" :data-idx="idx" :data-bid="buydata.bid" placeholder-style="font-size:28rpx"/>
							</block>
							<block v-if="item.key=='textarea'">
								<textarea :name="'form'+buydata.bid+'_'+idx" class='textarea' :placeholder="item.val2" placeholder-style="font-size:28rpx" @input="inputChange" :data-idx="idx" :data-bid="buydata.bid" :value="buydata.editorFormdata[idx]"/>
							</block>
							<block v-if="item.key=='radio'">
								<radio-group class="radio-group" :name="'form'+buydata.bid+'_'+idx" @change="checkboxChange" :data-idx="idx" :data-bid="buydata.bid">
									<label v-for="(item1,idx1) in item.val2" :key="item1.id" class="flex-y-center">
										<radio class="radio" :value="item1" :checked="buydata.editorFormdata && buydata.editorFormdata[idx] == item1 ? true : false"/>{{item1}}
									</label>
								</radio-group>
							</block>
							<block v-if="item.key=='checkbox'">
								<checkbox-group :name="'form'+buydata.bid+'_'+idx" class="checkbox-group" @change="checkboxChange" :data-idx="idx" :data-bid="buydata.bid">
									<label v-for="(item1,idx1) in item.val2" :key="item1.id" class="flex-y-center">
										<checkbox class="checkbox" :value="item1" :checked="buydata.editorFormdata && inArray(item1,buydata.editorFormdata[idx]) ? true : false"/>{{item1}}
									</label>
								</checkbox-group>
							</block>
							<block v-if="item.key=='selector'">
								<picker class="picker" mode="selector" :name="'form'+buydata.bid+'_'+idx" :value="buydata.editorFormdata[idx]" :range="item.val2" @change="editorBindPickerChange" :data-bid="buydata.bid" :data-idx="idx">
									<view v-if="buydata.editorFormdata[idx] || buydata.editorFormdata[idx]===0"> {{item.val2[buydata.editorFormdata[idx]]}}</view>
									<view v-else>请选择</view>
								</picker>
								<text class="iconfont iconjiantou" style="color:#999;font-weight:normal"></text>
							</block>
							<block v-if="item.key=='time'">
								<picker class="picker" mode="time" :name="'form'+buydata.bid+'_'+idx" :value="buydata.editorFormdata[idx]" :start="item.val2[0]" :end="item.val2[1]" :range="item.val2" @change="editorBindPickerChange" :data-bid="buydata.bid" :data-idx="idx">
									<view v-if="buydata.editorFormdata[idx]">{{buydata.editorFormdata[idx]}}</view>
									<view v-else>请选择</view>
								</picker>
								<text class="iconfont iconjiantou" style="color:#999;font-weight:normal"></text>
							</block>
							<block v-if="item.key=='date'">
								<picker class="picker" mode="date" :name="'form'+buydata.bid+'_'+idx" :value="buydata.editorFormdata[idx]" :start="item.val2[0]" :end="item.val2[1]" :range="item.val2" @change="editorBindPickerChange" :data-bid="buydata.bid" :data-idx="idx">
									<view v-if="buydata.editorFormdata[idx]">{{buydata.editorFormdata[idx]}}</view>
									<view v-else>请选择</view>
								</picker>
								<text class="iconfont iconjiantou" style="color:#999;font-weight:normal"></text>
							</block>
							<block v-if="item.key=='upload'">
								<input type="text" style="display:none" :name="'form'+buydata.bid+'_'+idx" :value="buydata.editorFormdata[idx]"/>
								<view class="flex" style="flex-wrap:wrap;padding-top:20rpx">
									<view class="form-imgbox" v-if="buydata.editorFormdata[idx]">
										<view class="layui-imgbox-close" style="z-index: 2;" @tap="removeimg" :data-bid="buydata.bid" :data-idx="idx"><image style="display:block" :src="pre_url+'/static/img/ico-del.png'"></image></view>
										<view class="form-imgbox-img"><image class="image" :src="buydata.editorFormdata[idx]" @click="previewImage" :data-url="buydata.editorFormdata[idx]" mode="aspectFit"/></view>
									</view>
									<view v-else class="form-uploadbtn" :style="{background:'url('+pre_url+'/static/img/shaitu_icon.png) no-repeat 50rpx',backgroundSize:'80rpx 80rpx',backgroundColor:'#F3F3F3'}" @click="editorChooseImage" :data-bid="buydata.bid" :data-idx="idx"></view>
								</view>
							</block>
              <block v-if="item.key=='upload_pics'">
              	<input type="text" style="display:none" :name="'form'+buydata.bid+'_'+idx" :value="buydata.editorFormdata && buydata.editorFormdata[idx]?buydata.editorFormdata[idx].join(','):''" maxlength="-1"/>
              	<view class="flex" style="flex-wrap:wrap;padding-top:20rpx">
              		<view v-for="(item2, index2) in buydata.editorFormdata[idx]" :key="index2" class="form-imgbox" >
              			<view class="layui-imgbox-close" style="z-index: 2;" @tap="removeimg" :data-index="index2" :data-bid="buydata.bid" :data-idx="idx" data-type="pics"><image :src="pre_url+'/static/img/ico-del.png'" class="image"></image></view>
              			<view class="form-imgbox-img"><image class="image" :src="item2" @click="previewImage" :data-url="item2" mode="aspectFit" :data-idx="idx"/></view>
              		</view>
              		<view class="form-uploadbtn" :style="{background:'url('+pre_url+'/static/img/shaitu_icon.png) no-repeat 50rpx',backgroundSize:'80rpx 80rpx',backgroundColor:'#F3F3F3'}" @click="editorChooseImage" :data-bid="buydata.bid" :data-idx="idx" data-type="pics"></view>
              	</view>
              </block>
              <block v-if="item.key=='upload_video'">
                <input type="text" style="display:none" :name="'form'+buydata.bid+'_'+idx" :value="buydata.editorFormdata[idx]"/>
                <view class="flex-y-center" style="flex-wrap:wrap;padding-top:20rpx;justify-content: flex-end;">
                  <view v-if="item.val2 && isNull(buydata.editorFormdata[idx])" style="color:#999;margin-right: 20rpx;">{{item.val2}}</view>
                  <view class="form-imgbox" v-if="buydata.editorFormdata[idx]">
                    <view class="layui-imgbox-close" style="z-index: 2;" @tap="removeimg" :data-bid="buydata.bid" :data-idx="idx">
                      <image :src="pre_url+'/static/img/ico-del.png'" class="image"></image>
                    </view>
                    <view style="overflow: hidden;white-space: pre-wrap;word-wrap: break-word;color: #4786BC;width: 430rpx;">
                      <video :src="buydata.editorFormdata[idx]" style="width: 100%;"/></video>
                    </view>
                  </view>
                  <view v-else class="form-uploadbtn" :style="{background:'url('+pre_url+'/static/img/shaitu_icon.png) no-repeat 50rpx',backgroundSize:'80rpx 80rpx',backgroundColor:'#F3F3F3'}" @click="upVideo" :data-bid="buydata.bid" :data-idx="idx" :data-formidx="'form'+idx" style="margin-right:20rpx;"></view>
                </view>
              </block>
            </view>
            <view class="draft-box" style="padding:20rpx 0" v-if="!isEmpty(buydata.formdata) && draft">
              <view class="btn" @tap="saveDraft" :data-bid="buydata.bid">保存草稿</view>
            </view>
					</view>
					
				</view>
        
				<view class="scoredk"
					v-if="userinfo.canscoredk && userinfo.score2money>0 && (userinfo.scoremaxtype==0 || (userinfo.scoremaxtype==1 && userinfo.scoredkmaxmoney>0))">
					<checkbox-group @change="scoredk" class="flex" style="width:100%">
						<view class="f1">
							<view>{{userinfo.score*1}} {{t('积分')}}可抵扣 <text
									style="color:#e94745">{{userinfo.scoredk_money*1}}</text> 元</view>
							<view style="font-size:22rpx;color:#999"
								v-if="userinfo.scoremaxtype==0 && userinfo.scoredkmaxpercent > 0 && userinfo.scoredkmaxpercent<=100">
								最多可抵扣订单金额的{{userinfo.scoredkmaxpercent}}%</view>
							<view style="font-size:22rpx;color:#999" v-else-if="userinfo.scoremaxtype==1">
								最多可抵扣{{userinfo.scoredkmaxmoney}}元</view>
						</view>
						<view class="f2">使用{{t('积分')}}抵扣
							<checkbox value="1" style="margin-left:6px;transform:scale(.8)"></checkbox>
						</view>
					</checkbox-group>
				</view>
				
				<view class="scoredk" v-if="userinfo.moneydec && userinfo.money_dec_money>0 ">
					<checkbox-group @change="moneydk" :data-bid="buydata.bid" :data-rate="userinfo.money_dec_money" class="flex" style="width:100%">
						<view class="f1">
							<view>
									{{t('余额')}}抵扣（余额：<text style="color:#e94745">{{userinfo.money?userinfo.money:0}}</text>元）
							</view>
							<view style="font-size:24rpx;color:#999" >
								1、选择此项提交订单时将直接扣除{{t('余额')}}
							</view>
							<view style="font-size:24rpx;color:#999" >
								2、最多可抵扣{{userinfo.money_dec_money}}元
							</view>
						</view>
						<view class="f2" style="font-weight:normal">
							使用{{t('余额')}}抵扣
							<checkbox value="1" style="margin-left:6px;transform:scale(.8)"></checkbox>
						</view>
					</checkbox-group>
				</view>

				<view style="width: 100%; height:182rpx;"></view>
				<view class="footer flex">
					<view class="text1 flex1">总计：
						<text style="font-weight:bold;font-size:36rpx">￥{{alltotalprice}}</text>
					</view>
					<button v-if="issubmit" class="op" style="background: #999;" >
						确认提交</button>
					<button v-else class="op" form-type="submit" :style="{background:'linear-gradient(-90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'}">
							确认提交</button>
				</view>
			</form>

			<view v-if="couponvisible" class="popup__container">
				<view class="popup__overlay" @tap.stop="handleClickMask"></view>
				<view class="popup__modal coupon-modal">
					<view class="popup__title">
						<text class="popup__title-text">请选择{{t('优惠券')}}</text>
						<image :src="pre_url+'/static/img/close.png'" class="popup__close" style="width:36rpx;height:36rpx"
							@tap.stop="handleClickMask" />
					</view>
					<view class="popup__content">
						<couponlist :couponlist="allbuydata[bid].couponList" :choosecoupon="true"
							:selectedrid="allbuydata[bid].couponrid" :bid="bid" @chooseCoupon="chooseCoupon">
						</couponlist>
					</view>
				</view>
			</view>
		</block>
		
		
		<view v-if="timeDialogShow" class="popup__container">
			<view class="popup__overlay" @tap.stop="hidetimeDialog"></view>
			<view class="popup__modal">
				<view class="popup__title">
					<text class="popup__title-text">请选择时间</text>
					<image :src="pre_url+'/static/img/close.png'" class="popup__close" style="width:36rpx;height:36rpx"
						@tap.stop="hidetimeDialog" />
				</view>
				<view class="order-tab">
					<view class="order-tab2">
						<block v-for="(item, index) in datelist" :key="index">
							<view  :class="'item ' + (curTopIndex == index ? 'on' : '')" @tap="switchTopTab" :data-index="index" :data-id="item.id" >
								<view class="datetext">{{item.weeks}}</view>
								<view class="datetext2">{{item.date}}</view>
								<view class="after"  :style="{background:t('color1')}"></view>
							</view>			
						</block>
				
					</view>
				</view>
				<view class="flex daydate">
					<block v-for="(item,index2) in timelist" :key="index2">
						<view :class="'date ' + ((timeindex==index2 && item.status==1) ? 'on' : '') + (item.status==0 ?'hui' : '') "  @tap="switchDateTab" :data-index2="index2" :data-status="item.status" :data-time="item.timeint"> {{item.time}}</view>						
					</block>
				</view>
				<view class="op">
					<button class="tobuy on" :style="{backgroundColor:t('color1')}" @tap="selectDate" >确 定</button>
				</view>
			</view>
		</view>
		
		<yybuydialog v-if="buydialogShow" :proid="proid" :btntype="btntype"  @currgg="currgg" @buydialogChange="buydialogChange" :menuindex="menuindex" @addcart="addcart" :isfuwu="isfuwu"  @tobuy="tobuy"></yybuydialog>
		
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
				pre_url:app.globalData.pre_url,
				test:'test',
				address: [],
				totalprice: '0.00',
				couponvisible: false,
				bid: 0,
				nowbid: 0,
				needaddress: 1,
				linkman: '',
				tel: '',
				userinfo: {},
				latitude: "",
				longitude: "",
				allbuydata: {},
				alltotalprice: "",
				type11visible: false,
				type11key: -1,
				regiondata: '',
				items: [],
				editorFormdata:[],
				sindex:'',
				prodata:'',
				yydate:'',
				yyset:'',
				issubmit:false,
				isdate:false,
				timeDialogShow: false,
				datelist:[],
				daydate:[],
				curTopIndex: 0,
				index:0,
				day: -1,
				days:'请选择服务时间',
				dates:'',
				num:0,
				timeindex:-1,
				startTime:0,
				selectDates:'',
				timelist:[],
				proid:'',
				
				protype:0,
				carlocat_name:'',
				carlocat_address:'',
				carlocat_latitude:'',
				carlocat_longitude:'',
				carlocat_stop:'',
				carinfor:'',
				
				buydialogShow: false,
				btntype:1,
				buydata:{},
				ggname:'',
				danwei:'次',
				yytime:'',
        
        workerid:0,//此参数用来判断是否从详情选择完服务人员进入
        yydates:'',//多时间段数组
        yydates_num:0,
        fwbusiness:'',//到商家户
        fwbid:0,
				tmplids:'',
				
				scoredk_money: 0,
				usescore: 0,
				draft:0, //表单保存到草稿
				moneyrate:false
			};
		},

		onLoad: function(opt) {
			this.opt = app.getopts(opt);
      if(this.opt.prodata){
        var prodata = decodeURIComponent(this.opt.prodata);
      }else{
        var prodata = '';
      }
      if(this.opt.yydate){
        var yydate = decodeURIComponent(this.opt.yydate);
      }else{
        var yydate = '';
      }
      if(this.opt.yydates){
        var yydates =decodeURIComponent( decodeURIComponent(this.opt.yydates));
      }else{
        var yydates = '';
      }

      var yydate_cache  = app.getCache('yydate');
      if(yydate_cache){
        yydate = yydate_cache;
      }

			this.prodata = prodata;
      this.yydate  = yydate;
			this.sindex  = opt.sindex;
			this.linkman = opt.linkman;
			this.tel = opt.tel;
      var workerid = opt.workerid || 0;
      if(workerid){
        this.workerid      = workerid;
        this.opt.worker_id = workerid;
      }

      if(yydates && yydates.length>0){
        this.yydates = JSON.parse(yydates);
        this.yydates_num = this.yydates.length;
      }
			this.getdata();
			
		},
    onShow:function(){
      var that = this;
      var pages = getCurrentPages(); //获取加载的页面
      var currentPage = pages[pages.length - 1]; //获取当前页面的对象
      if(currentPage && currentPage.$vm.fwbid){
          that.fwbusiness = {'id': currentPage.$vm.fwbid,'name':currentPage.$vm.fwbname};
          that.fwbid      = currentPage.$vm.fwbid
      }
    },
		onPullDownRefresh: function() {
			this.getdata();
		},
		methods: {
			getdata: function() {
				var that = this;
				that.loading = true;
				app.post('ApiYuyue/buy', {
					prodata: that.opt.prodata,
					worker_id:that.opt.worker_id,
          yydate:that.yydate,
          yydates:that.yydates
				}, function(res) {
				  if(res.status == 1){
						that.address = res.address;
						if(!that.linkman ){
							that.linkman = res.linkman;
						}
						if(!that.tel ){
							that.tel = res.tel;
						}
						that.userinfo = res.userinfo;
						that.yyset = res.yyset;
						that.allbuydata = res.allbuydata;
						that.fwtypelist = res.fwtypelist;
						if(!that.sindex ){
							that.sindex = that.fwtypelist[0].key;
						}
						if(res.protype){
							that.protype = res.protype;
						}
						
						if(res.carinfor){
							that.carinfor = res.carinfor
						}
						
						that.isdate = res.isdate
						that.datelist = res.datelist;
						//洗车的 直接取出规格信息
						if(res.protype ==1){
							var buydata = that.allbuydata[0];
							var prodata = buydata.prodata[0];
							var guige = prodata.guige;
							var product = prodata.product
							that.num = prodata.num;
							that.ggid = guige.id;
							that.proid = product.id;
							that.ggname = guige.name;
							that.danwei = product.danwei;
							that.opt.prodata = that.proid+','+that.ggid+','+that.num;
							if(!that.yydate){
								that.nowdate = that.datelist[0].year+that.datelist[0].date;
								app.get('ApiYuyue/isgetTime', { date:that.nowdate,proid:that.proid}, function (res) {
									that.timelist = res.data;
									that.selectTime();
								})
							}
						}
						if(res.tmplids){
							that.tmplids = res.tmplids;
						}
						if(res.userinfo.canscoredk){
							that.scoredk_money = res.userinfo.scoredk_money;
						}
						if(res.fw){
								that.worker_id = res.fw.id
						}
            if (res.allbuydata && res.allbuydata.length > 0) {
                const firstItem = res.allbuydata.find(item => item.editorFormdata);
                if (firstItem) {
                    that.editorFormdata = firstItem.editorFormdata;
                }
            }
            if(res.draft){
              that.draft = res.draft;
            }
						that.calculatePrice();
						that.loading = false;
						that.loaded();
				  }else{
						if (res.msg) {
							app.alert(res.msg, function() {
								if (res.url) {
									app.goto(res.url);
								} else {
									app.goback();
								}
							});
						} else if (res.url) {
							app.goto(res.url);
						} else {
							app.alert('您没有权限购买该商品');
						}
				  }
				});
			},
			selectTime:function(){
				var that = this;	
				for(var k=0;k<that.datelist.length;k++){
					that.curTopIndex = k;
					that.nowdate = that.datelist[k].year+that.datelist[k].date;
					var timelist = Object.values(that.timelist);
					var keylist = Object.keys(that.timelist);
					var selectedtime = '';
					for(var i=0;i < timelist.length;i++){
						if(k ==0 && timelist[i] &&  timelist[i].status ==1 && selectedtime==''){
							selectedtime = timelist[i].time;
							that.yydate = that.nowdate+' '+selectedtime;
							that.timeindex = i;
							return;
						}
						if(k > 0 && selectedtime==''){
							selectedtime = timelist[0].time;
							that.yydate = that.nowdate+' '+selectedtime;
							that.timeindex = i;
							return;
						}
					}
				}
			},
			buydialogChange: function (e) {
				var prodata = this.prodata.split(',');
				this.proid = prodata[0]
				if(!this.buydialogShow){
					this.btntype = e.currentTarget.dataset.btntype;
				}
				this.buydialogShow = !this.buydialogShow;
			},
			currgg: function (e) {
				console.log(e);
				var that = this
				that.ggname = e.ggname;
				that.ggid = e.ggid;
				that.proid = e.proid;
				that.num = e.num;
				that.opt.prodata = that.proid+','+that.ggid+','+that.num
				that.getdata();
			},
			chooseCoupon: function(e) {
				var allbuydata = this.allbuydata;
				var bid = e.bid;
				var couponrid = e.rid;
				var couponkey = e.key;
				if (couponrid == allbuydata[bid].couponrid) {
					allbuydata[bid].couponkey = 0;
					allbuydata[bid].couponrid = 0;
					allbuydata[bid].coupontype = 1;
					allbuydata[bid].coupon_money = 0;
					this.allbuydata = allbuydata;
					this.couponvisible = false;
				} else {
					var couponList = allbuydata[bid].couponList;
					var coupon_money = couponList[couponkey]['money'];
					var coupontype = couponList[couponkey]['type'];
					if(coupontype == 10){
						coupon_money = allbuydata[bid].sell_price * (100 - couponList[couponkey]['discount']) * 0.01;
					}
					allbuydata[bid].couponkey = couponkey;
					allbuydata[bid].couponrid = couponrid;
					allbuydata[bid].coupontype = coupontype;
					allbuydata[bid].coupon_money = coupon_money;
					this.allbuydata = allbuydata;
					this.couponvisible = false;
				}
				this.calculatePrice();
			},
			showCouponList: function(e) {
				this.couponvisible = true;
				this.bid = e.currentTarget.dataset.bid;
			},
			handleClickMask: function() {
				this.couponvisible = false;
			},
			//计算价格
			calculatePrice: function() {
				var that = this;
				var address = that.address;
				var allbuydata = that.allbuydata;
				var alltotalprice = 0;
				var needaddress = 0;
				for (var k in allbuydata) {
					var product_price = parseFloat(allbuydata[k].sell_price);
					var coupon_money = parseFloat(allbuydata[k].coupon_money); //-优惠券抵扣 
				  if(allbuydata[k].coupontype==3) coupon_money =  product_price
					var totalprice = product_price - coupon_money ;
					
					if (totalprice < 0) totalprice = 0; //优惠券不抵扣运费					
					 alltotalprice    += totalprice;
				}
				var oldalltotalprice = alltotalprice;
				
        if (that.userinfo.canscoredk && that.usescore) {
          // var oldalltotalprice = alltotalprice;
          var scoredk_money     = parseFloat(that.userinfo.scoredk_money); //-积分抵扣
          var scoredkmaxpercent = parseFloat(that.userinfo.scoredkmaxpercent); //最大抵扣比例
          var scoremaxtype      = parseInt(that.userinfo.scoremaxtype);//0按系统，1独立设置
          var scoredkmaxmoney   = parseFloat(that.userinfo.scoredkmaxmoney);
          
          if (scoremaxtype == 0 && scoredk_money > 0 && scoredkmaxpercent > 0 && scoredkmaxpercent <= 100 &&
          	scoredk_money > oldalltotalprice * scoredkmaxpercent * 0.01) {

          	scoredk_money = oldalltotalprice * scoredkmaxpercent * 0.01;
          	alltotalprice = oldalltotalprice - scoredk_money;
          } else if (scoremaxtype == 1 && scoredk_money > scoredkmaxmoney) {
          	scoredk_money = scoredkmaxmoney;
          	alltotalprice = oldalltotalprice - scoredk_money;
          }
        } else {
          var scoredk_money = 0;
        }
				if(that.userinfo.moneydec && that.userinfo.money_dec_money>0 && that.moneyrate){
					// var moneydk_money     = parseFloat(that.userinfo.money_dec_money);
					// alltotalprice = alltotalprice - moneydk_money;
					
					var moneydk_money     = parseFloat(that.userinfo.money_dec_money); //-余额抵扣
					var money_dec_rate = parseFloat(that.userinfo.money_dec_rate); //最大抵扣比例

					if ( moneydk_money > 0 && money_dec_rate > 0 && money_dec_rate < 100 &&
						moneydk_money > oldalltotalprice * money_dec_rate * 0.01) {
						moneydk_money = oldalltotalprice * money_dec_rate * 0.01;
					}
					
				}else{
					var moneydk_money = 0;
				}
				alltotalprice = alltotalprice - moneydk_money;
				that.needaddress = needaddress;
        
				var oldalltotalprice = alltotalprice;
				if (alltotalprice < 0) alltotalprice = 0;
				alltotalprice = alltotalprice.toFixed(2);
				that.alltotalprice = alltotalprice;
				that.allbuydata = allbuydata;
			},
			//积分抵扣
			scoredk: function (e) {
			  var usescore = e.detail.value[0];
			  if (!usescore) usescore = 0;
			  this.usescore = usescore;
			  this.calculatePrice();
			},
			//提交并支付
			topay: function(e) {
				var that = this;
				var addressid = this.address?this.address.id:0;
				var linkman = this.linkman?this.linkman:'';
				var tel = this.tel?this.tel:'';
				var worker_id = that.worker_id;
				var frompage = that.opt.frompage ? that.opt.frompage : '';
				var allbuydata = that.allbuydata;
				if (that.sindex == 0) {
					app.error('请选择服务方式');
					return;
				}
				if(!that.protype){
				  if(that.sindex==2 && addressid == undefined) {
					app.error('请选择地址');
					return;
				  }
				  if(that.sindex==1 && (!linkman || !tel)) {
					app.error('请填写联系人及联系电话');
					return;
				  }
				}
				// if (worker_id == 0) {
				// 	app.error('请选择服务人员');
				// 	return;
				// }
				var buydata = [];
				for (var i in allbuydata) {
					var formdata_fields = allbuydata[i].formdata;
					var formdata = e.detail.value;
					var newformdata = {};
					for (var j = 0; j < formdata_fields.length;j++){
						var thisfield = 'form'+allbuydata[i].bid + '_' + j;
						console.log(allbuydata[i]);
						if (formdata_fields[j].val3 == 1 && (formdata[thisfield] === '' || formdata[thisfield] === undefined || formdata[thisfield].length==0)){
								app.alert(formdata_fields[j].val1+' 必填');return;
						}
						if (formdata_fields[j].val3 == 1 && (formdata_fields[j].key == 'time' || formdata_fields[j].key == 'date') && (formdata[thisfield] === '' || formdata[thisfield] === undefined || formdata[thisfield] === '0' || formdata[thisfield].length==0)){
								app.alert(formdata_fields[j].val1+' 必填');return;
						}
						if (formdata_fields[j].key == 'selector') {
								formdata[thisfield] = formdata_fields[j].val2[formdata[thisfield]]
						}
						newformdata['form'+j] = formdata[thisfield];
					}
					//如果是洗车 重置 buydata
					var prodata = allbuydata[i].prodatastr;
					if(that.protype){
						var prodata = that.proid+','+that.ggid+','+that.num;
					}
					buydata.push({
						bid: allbuydata[i].bid,
						prodata: allbuydata[i].prodatastr,
						couponrid: allbuydata[i].couponrid,
						formdata:newformdata
					});
				}
				var remark =  this.remark;
				var yydate = that.yydate;
				var cardata = '';
				if(that.protype == 1){
					if(!that.ggid){
						app.error('请选择服务规格');
						return;
					}
					if(!that.yydate){
						app.error('请选择预约时间');
						return;
					}
					if(that.sindex == 2){
						if(!that.carlocat_latitude || !that.carlocat_longitude){
						 app.error('请选择车辆位置');
						 return;
						}
						if(!that.carlocat_stop) {
							app.error('请填写停靠位置');
							return;
						}
					}
					if(!that.carinfor || !that.carinfor.id) {
						app.error('请选择车辆信息');
						return;
					}
					cardata = {
						carid              : that.carinfor.id,
						carlocat_name      : that.carlocat_name,
						carlocat_address   : that.carlocat_address,
						carlocat_latitude  : that.carlocat_latitude,
						carlocat_longitude : that.carlocat_longitude,
						carlocat_stop      : that.carlocat_stop
					}
				}
				app.showLoading('提交中');
				app.post('ApiYuyue/createOrder', {
					frompage: frompage,
					buydata: buydata,
					addressid: addressid,
					linkman: linkman,
					tel: tel,
					remark:remark,
					yydate:that.yydate,
					worker_id:worker_id,
					fwtype:that.sindex,
					cardata:cardata,
          yydates:that.yydates,
          fwbid:that.fwbid,
					usescore:that.usescore,
					moneyrate:that.moneyrate,
				}, function(res) {
					app.showLoading(false);
	
					//app.error('订单编号：' +res.payorderid);
					if(res.status==1 && res.payorderid){
							that.	issubmit = true	
							app.goto('/pagesExt/pay/pay?id=' + res.payorderid,'redirectTo');
					}else if(res.status==1 && !res.payorderid){
							//预约成功提示
							if(that.yyset.yuyue_success){
								that.subscribeMessage(function () {
									app.alert(that.yyset.yuyue_success,function(){
										app.goto('/yuyue/yuyue/orderlist','redirect');
									});
								});
							}else{
								that.subscribeMessage(function () {
									app.goto('/yuyue/yuyue/orderlist','redirect');
								});
							}
					}else	if (res.status == 0) {
						//that.showsuccess(res.data.msg);
						if (res.msg) {
							app.alert(res.msg, function() {
								if (res.url) {
									app.goto(res.url);
								} else {
									// app.goback();
								}
							});
						} else if (res.url) {
							app.goto(res.url);
						} else {
							app.alert('您没有权限购买该商品');
						}
						return;
					}
				});
			},
			editorChooseImage: function (e) {
				var that = this;
				var bid = e.currentTarget.dataset.bid;
				var idx = e.currentTarget.dataset.idx;
				var type = e.currentTarget.dataset.type;
				var editorFormdata = that.allbuydata[bid].editorFormdata;
				if(!editorFormdata) editorFormdata = [];
				app.chooseImage(function(data){
          if(type == 'pics'){
            var pics = editorFormdata[idx];
            if(!pics){
              pics = [];
            }
          	for(var i=0;i<data.length;i++){
          		pics.push(data[i]);
          	}
            editorFormdata[idx] = pics;
          }else{
            editorFormdata[idx] = data[0];
          }
          that.editorFormdata = editorFormdata
					that.allbuydata[bid].editorFormdata = editorFormdata
					that.test = Math.random();
				})
			},
			removeimg:function(e){
				var that = this;
				var bid = e.currentTarget.dataset.bid;
				var idx = e.currentTarget.dataset.idx;
        var type = e.currentTarget.dataset.type;
        var index = e.currentTarget.dataset.index;
        if(type == 'pics'){
          var pics = that.editorFormdata[idx]
          pics.splice(index,1);
          that.editorFormdata[idx] = pics;
        }else{
          var pics = '';
          that.editorFormdata[idx] = pics;
        }
        that.$set(that.allbuydata[bid].editorFormdata,idx,pics)
			},
			editorBindPickerChange:function(e){
				var that = this;
				var bid = e.currentTarget.dataset.bid;
				var idx = e.currentTarget.dataset.idx;
				var val = e.detail.value;
				var editorFormdata = that.allbuydata[bid].editorFormdata;
				if(!editorFormdata) editorFormdata = [];
				editorFormdata[idx] = val;
				// console.log(editorFormdata)
				that.allbuydata[bid].editorFormdata = editorFormdata;
				that.test = Math.random();
			},
			//选择服务方式
			selectFwtype: function(e) {
				var that = this;
				var index = e.currentTarget.dataset.index;
				that.sindex = index
			},
			inputLinkman: function (e) {
				this.linkman = e.detail.value
			},
			inputTel: function (e) {
				this.tel = e.detail.value
			},
			//选择时间
			chooseTime: function(e) {
				var that = this;
				var prodata = that.prodata.split(',');
				that.proid = prodata[0]
				that.timeDialogShow = true;
				that.timeIndex = -1;
				var curTopIndex = that.datelist[0];
				that.nowdate = that.datelist[that.curTopIndex].year+that.datelist[that.curTopIndex].date;
				that.loading = true;
				app.get('ApiYuyue/isgetTime', { date:that.nowdate,proid:that.proid}, function (res) {
				  that.loading = false;
				  that.timelist = res.data;
				})
			},	
			switchTopTab: function (e) {
			  var that = this;
			  var id = e.currentTarget.dataset.id;
			  var index = parseInt(e.currentTarget.dataset.index);
			  this.curTopIndex = index;
			  that.days = that.datelist[index].year+that.datelist[index].date
			  that.nowdate = that.datelist[index].nowdate
			   // if(!that.dates ){ that.dates = that.daydate[0] }
			  this.curIndex = -1;
			  this.curIndex2 = -1;
			  //检测预约时间是否可预约
			  that.loading = true;

			  app.get('ApiYuyue/isgetTime', { date: that.days,proid:that.proid}, function (res) {
				  that.loading = false;
				  that.timelist = res.data;
			  })
				
			},
			switchDateTab: function (e) {
			  var that = this;
			  var index2 = parseInt(e.currentTarget.dataset.index2);
			  var timeint = e.currentTarget.dataset.time
			  var status = e.currentTarget.dataset.status
			  if(status==0){
					app.error('此时间不可选择');return;	
			  }
			  that.timeint = timeint
			  that.timeindex = index2
				//console.log(that.timelist);
			  that.starttime1 = that.timelist[index2].time
			  if(!that.days || that.days=='请选择服务时间'){ that.days = that.datelist[0].year + that.datelist[0].date }
			  that.selectDates = that.starttime1;
			},
			selectDate:function(e){
				var that=this
				if(that.timeindex >= 0 && that.timelist[that.timeindex].status==0){
						that.starttime1='';
				}
				if(!that.starttime1){
					app.error('请选择预约时间');return;
				}
				 var yydate = that.days+' '+that.selectDates
				 that.yydate = yydate
				 this.timeDialogShow = false;
			},
			hidetimeDialog: function() {
				this.timeDialogShow = false;
			},
			gotopeople:function(e){
					var that=this
					if(!that.yydate){
						app.error('请先选择预约时间');return;
					} 
					app.goto('selectpeople?prodata='+that.prodata+'&sindex='+that.sindex+'&linkman='+that.linkman+'&tel='+that.tel);
			},
      locationSelect:function(e){
        var that = this;
        uni.chooseLocation({
          success: function (res) {
            that.carlocat_name      = res.name;
            that.carlocat_address   = res.address;
            that.carlocat_latitude  = res.latitude;
            that.carlocat_longitude = res.longitude;
          }
        });
      },
      inputCarlocatStop:function(e){
        this.carlocat_stop = e.detail.value;
      },
			timestampToTime(timestamp) {
				console.log(timestamp);
			  var date = new Date(parseInt(timestamp));
			  var Y = date.getFullYear() + "年";
			  var M =
			    (date.getMonth() + 1 < 10
			      ? "0" + (date.getMonth() + 1)
			      : date.getMonth() + 1) + "月";
			  var D = (date.getDate() < 10 ? "0" + date.getDate() : date.getDate()) + " ";
			  var h = (date.getHours() < 10 ? "0" + date.getHours() : date.getHours()) + ":";
			  var m = (date.getMinutes() < 10 ? "0" + date.getMinutes() : date.getMinutes());
			  //var s = date.getSeconds();
				var date = Y + M + D + h + m ;
				this.yydate = date
			},
      inputChange: function (e) {
        var that = this;
        var idx = e.currentTarget.dataset.idx;
        var bid = e.currentTarget.dataset.bid;
        this.allbuydata[bid].editorFormdata[idx] = e.detail.value;
      },
      checkboxChange:function(e){
        var idx = e.currentTarget.dataset.idx;
        var bid = e.currentTarget.dataset.bid;
        var formtype = e.currentTarget.dataset.formtype;
        var value = e.detail.value;
        if (!value) value = 0; 
        this.allbuydata[bid].editorFormdata[idx] = value;
      },
      //上传视频
      upVideo:function(e){
          var that = this;
          var idx = e.currentTarget.dataset.idx;
          var bid = e.currentTarget.dataset.bid;
          var editorFormdata = that.editorFormdata;
          if(!editorFormdata) editorFormdata = [];
          var up_url = app.globalData.baseurl + 'ApiImageupload/uploadImg/aid/' + app.globalData.aid + '/platform/' + app.globalData.platform +'/session_id/' +app.globalData.session_id;
          uni.chooseVideo({
            sourceType: ['camera', 'album'],
            success: function (res) {
              var path = res.tempFilePath;
              if(res.size > 0){
                var maxsize = that.allbuydata[bid]['formdata'][idx].val4;
                if(maxsize){
                  maxsize = parseFloat(maxsize);
                  if(maxsize > 0 && maxsize * 1024 * 1024 < res.size){
                    app.alert('视频文件过大');return;
                  }
                }
              }
      
              app.showLoading('上传中');
              uni.uploadFile({
                url: up_url,
                filePath: path,
                name: 'file',
                success: function(res) {
                  app.showLoading(false);
                  var data = JSON.parse(res.data);
                  if (data.status == 1) {
                    editorFormdata[idx] = data.url;
                    that.editorFormdata = editorFormdata;
                    // that.allbuydata[bid].editorFormdata = editorFormdata;
                    that.$set(that.allbuydata[bid].editorFormdata,idx,data.url)
                  } else {
                    app.alert(data.msg);
                  }
                },
                fail: function(res) {
                  app.showLoading(false);
                  app.alert(res.errMsg);
                }
              });
            }
          });
      },
      //保存草稿
      saveDraft:function(e){
        var that = this;
        var bid = e.currentTarget.dataset.bid;
        var buydata = that.allbuydata[bid];
        console.log(buydata.editorFormdata);
        app.confirm('确定要保存吗?',function () {
          app.showLoading('提交中');
          app.post('ApiYuyue/saveFromDraft', {bid:bid,prodata:that.opt.prodata,formdata:buydata.editorFormdata}, function(res) {
            app.showLoading(false);
            if(res.status == 1){
              return app.success(res.msg);
            }
            return app.error(res.msg);
          })
        });
      },
			moneydk: function(e) {
				var that = this;
				var moneydec = that.userinfo.moneydec;
				if(moneydec){
						that.moneyrate = !that.moneyrate;
						this.calculatePrice();
				}
			},
		}
	}
</script>

<style>
	.redBg{color:#fff;padding:4rpx 16rpx;font-weight:normal;border-radius:8rpx;font-size:24rpx; width: auto; display: inline-block; margin-top: 4rpx;}
.address-add {width: 94%;margin: 20rpx 3%;background: #fff;border-radius: 20rpx;padding: 20rpx 3%;min-height: 140rpx;}
.address-add .f1 {margin-right: 20rpx}
.address-add .f1 .img {width: 66rpx;height: 66rpx;}
.address-add .f2 {color: #666;}
.address-add .f3 {width: 26rpx;height: 26rpx;}
.linkitem {width: 100%;padding: 1px 0;background: #fff;display: flex;align-items: center}.cf3 {width: 200rpx;height: 26rpx;display: block;
    text-align: right;}
.linkitem .f1 {width: 160rpx;color: #111111}
.linkitem .input {height: 50rpx;padding-left: 10rpx;color: #222222;font-weight: bold;font-size: 28rpx;flex: 1}
.buydata {width: 94%;margin: 0 3%;margin-bottom: 20rpx;}
.btitle {width: 100%;padding: 20rpx 20rpx;display: flex;align-items: center;color: #111111;font-weight: bold;font-size: 30rpx}
.btitle .img {width: 34rpx;height: 34rpx;margin-right: 10rpx}
.bcontent {width: 100%;padding: 0 20rpx;background: #fff;border-radius: 20rpx;}
.bcontent2 {width: 100%;padding: 0 20rpx; margin-top: 30rpx;background: #fff;border-radius: 20rpx;}
.product {width: 100%;border-bottom: 1px solid #f4f4f4}
.product .item {width: 100%;padding: 20rpx 0;background: #fff;border-bottom: 1px #ededed dashed;}
.product .item:last-child {border: none}
.product .info {padding-left: 20rpx;}
.product .info .f1 {color: #222222;font-weight: bold;font-size: 26rpx;line-height: 36rpx;margin-bottom: 10rpx;display: -webkit-box;-webkit-box-orient: vertical;-webkit-line-clamp: 2;overflow: hidden;}
.product .info .f2 {color: #999999;font-size: 24rpx}
.product .info .f3 {color: #FF4C4C;font-size: 28rpx;display: flex;align-items: center;margin-top: 10rpx}
.product image {width: 140rpx;height: 140rpx}
.freight {width: 100%;padding: 20rpx 0;background: #fff;display: flex;flex-direction: column;}
.freight .f1 {color: #333;margin-bottom: 10rpx}
.freight .f2 {color: #111111;text-align: right;flex: 1}
.freight .f3 {width: 24rpx;height: 28rpx;}
.freighttips {color: red;font-size: 24rpx;}
.freight-ul {width: 100%;display: flex;}
.freight-li {flex-shrink: 0;display: flex;background: #F5F6F8;border-radius: 24rpx;color: #6C737F;font-size: 24rpx;text-align: center;height: 48rpx;line-height: 48rpx;padding: 0 28rpx;margin: 12rpx 10rpx 12rpx 0}

.price {width: 100%;padding: 20rpx 0;background: #fff;display: flex;align-items: center}
.price .f1 {color: #333}
.price .f2 {color: #111;font-weight: bold;text-align: right;flex: 1}
.price .f3 {width: 24rpx;height: 24rpx;}
.scoredk {width: 94%;margin: 0 3%;margin-bottom: 20rpx;border-radius: 20rpx;padding: 24rpx 20rpx;background: #fff;display: flex;align-items: center}
.scoredk .f1 {color: #333333}
.scoredk .f2 {color: #999999;text-align: right;flex: 1}
.remark {width: 100%;padding: 16rpx 0;background: #fff;display: flex;align-items: center}
.remark .f1 {color: #333;width: 200rpx}
.remark input {border: 0px solid #eee;height: 70rpx;padding-left: 10rpx;text-align: right}
.footer {width: 100%;background: #fff;margin-top: 5px;position: fixed;left: 0px;bottom: 0px;padding: 0 20rpx;display: flex;align-items: center;z-index: 8}
.footer .text1 {height: 110rpx;line-height: 110rpx;color: #2a2a2a;font-size: 30rpx;}
.footer .text1 text {color: #e94745;font-size: 32rpx;}
.footer .op {width: 200rpx;height: 80rpx;line-height: 80rpx;color: #fff;text-align: center;font-size: 30rpx;border-radius: 44rpx}
.storeitem {width: 100%;padding: 20rpx 0;display: flex;flex-direction: column;color: #333}
.storeitem .panel {width: 100%;height: 60rpx;line-height: 60rpx;font-size: 28rpx;color: #333;margin-bottom: 10rpx;display: flex}
.storeitem .panel .f1 {color: #333}
.storeitem .panel .f2 {color: #111;font-weight: bold;text-align: right;flex: 1}
.storeitem .radio-item {display: flex;width: 100%;color: #000;align-items: center;background: #fff;border-bottom: 0 solid #eee;padding: 8rpx 20rpx;}
.storeitem .radio-item:last-child {border: 0}
.storeitem .radio-item .f1 {color: #666;flex: 1}
.storeitem .radio {flex-shrink: 0;width: 32rpx;height: 32rpx;background: #FFFFFF;border: 2rpx solid #BFBFBF;border-radius: 50%;margin-left: 30rpx}
.storeitem .radio .radio-img {width: 100%;height: 100%}
.pstime-item {display: flex;border-bottom: 1px solid #f5f5f5;padding: 20rpx 30rpx;}
.pstime-item .radio {flex-shrink: 0;width: 32rpx;height: 32rpx;background: #FFFFFF;border: 2rpx solid #BFBFBF;border-radius: 50%;margin-right: 30rpx}
.pstime-item .radio .radio-img {width: 100%;height: 100%}
.cuxiao-desc {width: 100%}
.cuxiao-item {display: flex;padding: 0 40rpx 20rpx 40rpx;}
.cuxiao-item .type-name {font-size: 28rpx;color: #49aa34;margin-bottom: 10rpx;flex: 1}
.cuxiao-item .radio {flex-shrink: 0;width: 32rpx;height: 32rpx;background: #FFFFFF;border: 2rpx solid #BFBFBF;border-radius: 50%;margin-right: 30rpx}
.cuxiao-item .radio .radio-img {width: 100%;height: 100%}

.form-item {width: 100%;padding: 16rpx 0;background: #fff;display: flex;align-items: center;justify-content:space-between}
.form-item .label {color: #333;width: 200rpx;flex-shrink:0}
.form-item .radio{transform:scale(.7);}
.form-item .checkbox{transform:scale(.7);}
.form-item .input {border:0px solid #eee;height: 70rpx;padding-left: 10rpx;text-align: right;flex:1}
.form-item .textarea{height:140rpx;line-height:40rpx;overflow: hidden;flex:1;border:1px solid #eee;border-radius:2px;padding:8rpx}
.form-item .radio-group{display:flex;flex-wrap:wrap;justify-content:flex-end}
.form-item .radio{height: 70rpx;line-height: 70rpx;display:flex;align-items:center}
.form-item .radio2{display:flex;align-items:center;}
.form-item .radio .myradio{margin-right:10rpx;display:inline-block;border:1px solid #aaa;background:#fff;height:32rpx;width:32rpx;border-radius:50%}
.form-item .checkbox-group{display:flex;flex-wrap:wrap;justify-content:flex-end}
.form-item .checkbox{height: 70rpx;line-height: 70rpx;display:flex;align-items:center}
.form-item .checkbox2{display:flex;align-items:center;height: 40rpx;line-height: 40rpx;}
.form-item .checkbox .mycheckbox{margin-right:10rpx;display:inline-block;border:1px solid #aaa;background:#fff;height:32rpx;width:32rpx;border-radius:2px}
.form-item .picker{height: 70rpx;line-height:70rpx;flex:1;text-align:right}

.form-imgbox{margin-right:16rpx;margin-bottom:10rpx;font-size:24rpx;position: relative;}
.form-imgbox-close{position: absolute;display: block;width:32rpx;height:32rpx;right:-16rpx;top:-16rpx;color:#999;font-size:32rpx;background:#fff}
.form-imgbox-close .image{width:100%;height:100%}
.form-imgbox-img{display: block;width:180rpx;height:180rpx;padding:2px;border: #d3d3d3 1px solid;background-color: #f6f6f6;overflow:hidden}
.form-imgbox-img>.image{width:100%;height:100%}
.form-imgbox-repeat{position: absolute;display: block;width:32rpx;height:32rpx;line-height:28rpx;right: 2px;bottom:2px;color:#999;font-size:30rpx;background:#fff}
.form-uploadbtn{position:relative;height:180rpx;width:180rpx;margin-right: 16rpx;margin-bottom:10rpx;}

.member_search{width:100%;padding:0 40rpx;display:flex;align-items:center}
.searchMemberButton{height:60rpx;background-color: #007AFF;border-radius: 10rpx;width: 160rpx;line-height: 60rpx;color: #fff;text-align: center;font-size: 28rpx;display: block;}
.memberlist{width:100%;padding:0 40rpx;height: auto;margin:20rpx auto;}
.memberitem{display:flex;align-items:center;border-bottom:1px solid #f5f5f5;padding:20rpx 0}
.memberitem image{display: block;height:100rpx;width:100rpx;margin-right:20rpx;}
.memberitem .t1{color:#333;font-weight:bold}
.memberitem .radio {flex-shrink: 0;width: 32rpx;height: 32rpx;background: #FFFFFF;border: 2rpx solid #BFBFBF;border-radius: 50%;margin-right: 30rpx}
.memberitem .radio .radio-img {width: 100%;height: 100%}

.checkMem{ display: inline-block; }
.checkMem p{ height: 30px; width: 100%; display: inline-block; }
.placeholder{  font-size: 26rpx;line-height: 80rpx;}
.selected-item span{ font-size: 26rpx !important;}
.orderinfo{width:94%;margin:0 3%;border-radius:8rpx;margin-top:16rpx;padding: 14rpx 3%;background: #FFF;}
.orderinfo .item{display:flex;width:100%;padding:20rpx 0;border-bottom:1px dashed #ededed;overflow:hidden}
.orderinfo .item:last-child{ border-bottom: 0;}
.orderinfo .item .t1{width:200rpx;flex-shrink:0}
.orderinfo .item .t2{flex:1;text-align:right}
.orderinfo .item .red{color:red}
.draft-box .btn{width:150rpx;color: #909090;font-weight: bold;margin: 0 auto;border: 1px solid #909090;text-align: center;padding: 8rpx;border-radius: 10rpx;}


/*时间范围*/
.datetab{ display: flex; border:1px solid red; width: 200rpx; text-align: center;}
.order-tab{ }
.order-tab2{display:flex;width:auto;min-width:100%}
.order-tab2 .item{width:auto;font-size:28rpx;font-weight:bold;text-align: center; color:#999999;overflow: hidden;flex-shrink:0;flex-grow: 1; display: flex; flex-direction: column; justify-content: center; align-items: center;}
.order-tab2 .item .datetext{ line-height: 60rpx; height:60rpx;}
.order-tab2 .item .datetext2{ line-height: 60rpx; height:60rpx;font-size: 22rpx;}
.order-tab2 .on{color:#222222;}
.order-tab2 .after{display:none;margin-left:-10rpx;bottom:5rpx;height:6rpx;border-radius:1.5px;width:70rpx}
.order-tab2 .on .after{display:block}
.daydate{ padding:20rpx; flex-wrap: wrap; overflow-y: scroll; height:400rpx; }
.daydate .date{ width:20%;text-align: center;line-height: 60rpx;height: 60rpx; margin-top: 30rpx;}
.daydate .on{ background:red; color:#fff;}
.daydate .hui{ border:1px solid #f0f0f0; background:#f0f0f0;border-radius: 5rpx;}
.tobuy{flex:1;height: 72rpx; line-height: 72rpx; color: #fff; border-radius: 0px; border: none;font-size:28rpx;font-weight:bold;width:90%;margin:20rpx 5%;border-radius:36rpx;}
.business-info  {width: 100%;margin: 20rpx 0;background: #fff;border-radius: 20rpx;padding: 20rpx 3%;min-height: 140rpx;}
.business-info .f1 {margin-right: 20rpx}
.business-info .linkitem {width: 100%;padding: 5px 0;background: #fff;display: flex;align-items: center}.cf3 {width: 200rpx;height: 26rpx;display: block;
    text-align: right;}
</style>
