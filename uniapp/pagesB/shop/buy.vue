<template>
	<view class="container">
		<block v-if="isload">
			<form @submit="topay">
        <block v-if="!usegiveorder">
          <!-- 顶部广告图 -->
          <image v-if="shop_buy_ad && createorder_toppic" :src="createorder_toppic" style="width:100%;height:auto" mode="widthFix"  @tap="goto" :data-url="createorder_toppic_tourl"></image>
          <view v-if="needaddress==0" class="address-add">
            <view class="linkitem">
              <view class="f1"><label class="redStar" v-if="contact_require==1 || is_pingce"> * </label>联系人</view>
              <input type="text" class="input" :value="linkman" placeholder="请输入您的姓名" @input="inputLinkman"
                placeholder-style="color:#626262;font-size:28rpx" />
            </view>
            <!-- 性别 -->
            <view class="linkitem" v-if="is_pingce">
              <view class="f1"><label class="redStar" v-if="contact_require==1 || is_pingce"> * </label>性 别</view>
              <picker class="input" mode="selector" @change="bindPickerChangeSex" :range="sexlist">
                <view v-if="gender"> {{gender}}</view>
                <view v-else>请选择</view>
              </picker>
            </view>
            <!-- 联系电话 -->
            <view class="linkitem">
							<view class="f1"><label class="redStar" v-if="contact_require==1 || is_pingce"> * </label>联系电话</view>
              <input type="text" class="input" :value="tel" placeholder="请输入您的手机号" @input="inputTel" placeholder-style="color:#626262;font-size:28rpx" />
            </view>
            <view v-if="worknum_status" class="linkitem">
             <view class="f1"><label class="redStar">  </label>工 号</view>
              <input type="number" class="input" :value="worknum" :placeholder="worknumtip" @input="inputWorknum" placeholder-style="color:#626262;font-size:28rpx" />
            </view>
            <view v-if="is_pingce" class="linkitem">
							<view class="f1"><label class="redStar" v-if="contact_require==1 || is_pingce"> * </label>邮 箱</view>
              <input type="text" class="input" :value="pcemail" placeholder="请输入您的邮箱" @input="inputEmail" placeholder-style="color:#626262;font-size:28rpx" />
            </view>
            <view class="linkitem" v-if="is_pingce">
							<view class="f1"><label class="redStar"> * </label>生 日</view>
							<picker class="input" mode="date" :end="endDate" @change="editorBindPickerChangeAge">
								<view v-if="age"> {{age}}</view>
								<view v-else>请选择</view>
							</picker>
							<text class="iconfont iconjiantou" style="color:#999;font-weight:normal"></text>
				    </view>
						<view v-if="is_pingce" class="linkitem">
							<view class="f1"><label class="redStar"> * </label>院 校</view>
							<input type="text" class="input" placeholder="请输入您的院校" @input="inputSchool" placeholder-style="color:#626262;font-size:28rpx" />
						</view>
						<view v-if="is_pingce" class="linkitem">
							<view class="f1"><label class="redStar">  </label>院 系</view>
							<input type="text" class="input" placeholder="请输入您的院系" @input="inputFaculties"	placeholder-style="color:#626262;font-size:28rpx" />
						</view>
						<view v-if="is_pingce" class="linkitem">
							<view class="f1"><label class="redStar"> * </label>专 业</view>
							<input type="text" class="input" placeholder="请输入您的专业" @input="inputMajor" placeholder-style="color:#626262;font-size:28rpx" />
						</view>
						<view v-if="is_pingce" class="linkitem">
							<view class="f1"><label class="redStar"> * </label>学 历</view>
							<picker class="input" mode="selector" @change="bindPickerChangeEdu"  :range="edulist">
								<view v-if="education"> {{education}}</view>
								<view v-else>请选择</view>
							</picker>
							<text class="iconfont iconjiantou" style="color:#999;font-weight:normal"></text>
						</view>
						<view class="linkitem" v-if="is_pingce">
							<view class="f1"><label class="redStar"> * </label>入学年份</view>
							<picker class="input" mode="date" fields="year" @change="editorBindPickerChangeEnrol">
								<view v-if="enrol">{{ enrol }}</view>								 
								<view v-else>请选择</view>
							</picker>
							<text class="iconfont iconjiantou" style="color:#999;font-weight:normal"></text>
						</view>
						<view v-if="is_pingce" class="linkitem">
							<view class="f1"><label class="redStar">  </label>班 级</view>
							<input type="text" class="input" placeholder="请输入您的班级" @input="inputClassName"	placeholder-style="color:#626262;font-size:28rpx" />
						</view>
					</view>
					<view v-else class="address-add flex-y-center">
						<view class="flex-y-center " @tap="goto" style="flex: 1;"
						:data-url="'/pagesB/address/'+(address.id ? 'address' : 'addressadd')+'?fromPage=buy&type=' + (havetongcheng==1?'1':'0')+'&isfloor='+isfloor">
							<image style="width: 66rpx;height: 66rpx;margin-right: 20rpx;" class="img" :src="pre_url+'/static/img/address.png'" />
						<view class="f2" v-if="address.id" style="flex: 1;">
							<view style="font-weight:bold;color:#111111;font-size:30rpx">{{address.name}} {{address.tel}} <text v-if="address.company">{{address.company}}</text></view>
							<view style="font-size:24rpx">{{address.area}} {{address.address}} 
								<text v-if="isfloor==1">
									<text v-if="address.floor ">{{address.floor}}层</text><text v-if="address.room">{{address.room}}号</text> 
								</text>
								
							</view>
						</view>
						<view v-else class="f2 flex1">请选择收货地址</view>
						<image :src="pre_url+'/static/img/arrowright.png'"  class="f3"></image>
						</view>
						<view v-if="worknum_status" class="linkitem" style="margin: 20rpx;line-height:50rpx ;">
							<text class="f1" style="width: 90rpx;margin-right: 0;">工号</text>
							<input type="text" class="input" :value="worknum" :placeholder="worknumtip" @input="inputWorknum"
							placeholder-style="color:#626262;font-size:28rpx" />
						</view>
						<view v-if="needusercard" class="linkitem" style="margin: 20rpx;line-height:50rpx ;">
							<text class="f1" style="width: 140rpx;margin-right: 0;">身份证号</text>
							<input type="text" class="input" :value="usercard" :placeholder="usercardtip" @input="inputWorknum"
							placeholder-style="color:#626262;font-size:28rpx" />
						</view>
					</view>

        </block>
				<!--代下级下单-->
				<view class="scoredk" v-if="show_other_order">
					<view class="price">
						<view class="f1">选择下单会员</view>
						<view class="f2 flex" @tap="showTeamMemberList" style="align-items: center;justify-content: flex-end">
						 <img style="width: 50rpx;height:50rpx;border-radius:50%;margin-right:10rpx" :src="teamMember.headimg" alt="">	<text>{{teamMember.id?teamMember.nickname+'(ID:'+teamMember.id+')':'请选择'}}</text><text class="iconfont iconjiantou" style="color:#999;font-weight:normal"></text>
						</view>
					</view>
				</view>
				<!--代下级下单end-->
				<view v-for="(buydata, groupBid) in allbuydata" :key="groupBid" class="buydata">
					<view class="btitle">
						<image class="img" :src="pre_url+'/static/img/ico-shop.png'" />{{buydata.business.name}}
					</view>
					<view class="bcontent">
						<view class="product">
							<view v-for="(item, index2) in buydata.prodata" :key="index2" style="border-bottom: 1px #ededed dashed;">
								<view class="item flex">
									<view class="img" @tap="goto" :data-url="'/pages/shop/product?id=' + item.product.id">
										<image v-if="item.guige.pic" :src="item.guige.pic"></image>
										<image v-else-if="item.guige.ggpic_wholesale" :src="item.guige.ggpic_wholesale"></image>
										<image v-else :src="item.product.pic"></image>
									</view>
									<view class="info flex1">
										<view class="f1">{{item.product.name}}</view>
										<view class="f2">规格：{{item.guige.gg_group_title ? item.guige.gg_group_title:''}} {{item.guige.name}}</view>
										<view class="f2" v-if="item.product.product_type==3">手工费：{{item.guige.hand_fee}}</view>
										<view class="f3">
											<block v-if="order_change_price"><input type="number" :value="item.guige.sell_price" :data-price="item.guige.sell_price" :data-index="groupBid" :data-index2="index2" class="inputPrice" @input="inputPrice"></block>
											<block v-else><text style="font-weight:bold;">￥{{item.guige.sell_price}}
											<text v-if="!isNull(item.guige.service_fee) && item.guige.service_fee > 0">+{{item.guige.service_fee}}{{t('服务费')}}</text>
											<text v-if="!isNull(item.guige.freezemoney_price) && item.guige.freezemoney_price > 0">+{{item.guige.freezemoney_price}}{{t('冻结资金')}}</text>
											</text></block>
											<text style="padding-left:20rpx"> × {{item.num}}</text>
										</view>
                    <view v-if="item.product.commission_memberset" class="form-item">
                     	<view class="label" style="width: 160rpx;">分销员ID<text style="color:red"> *</text></view>
                     	<input @input="inputCommissionmid" :data-bid="groupBid" :data-index="index2" type="text" placeholder="请填写分销员ID" :value="item.product.procommissionmid?item.product.procommissionmid:''" placeholder-style="font-size:28rpx" class="input input2" style="text-align: left;"/>
                    </view>
									</view>
								</view> 
								<view class="freight" v-if="item.product.weighttype>0 && item.product.weightlist.length>0">
									<view class="f1"> {{t('包装费')}}</view>
									<view class="freight-ul">
										<view style="width:100%;overflow-y:hidden;overflow-x:scroll;white-space: nowrap;">
											<block v-for="(item1, idx3) in item.product.weightlist" :key="idx3">
												<view class="freight-li"
													:style="item.product.weightkey==idx3?'color:'+t('color1')+';background:rgba('+t('color1rgb')+',0.2)':''"
													@tap="changeWeight" :data-bid="groupBid" :data-proindex='index2' :data-index="idx3">{{item1.name}}
												</view>
											</block>
										</view>
									</view>
								</view>
								<block v-if="item.product.product_type==1" >
									<view class="glassinfo" @tap="showglasscustom" v-if="item.product.glass_type == 2" :data-index="groupBid" :data-index2="index2" :data-gcid="item.product.has_glasscustom==1?item.product.glass_custom.id:0" :style="'background:rgba('+t('color1rgb')+',0.8);color:#FFF'">
										<view class="f1">
											定制参数
										</view>
										<view class="f2">
											<text>{{item.product.has_glasscustom==1?item.product.glass_custom.name:''}}</text>
											<image :src="pre_url+'/static/img/arrowright.png'" >
										</view>
									</view>
									<view class="glassinfo" @tap="showglass" v-else :data-index="groupBid" :data-index2="index2" :data-grid="item.product.has_glassrecord==1?item.product.glassrecord.id:0" :style="'background:rgba('+t('color1rgb')+',0.8);color:#FFF'">
										<view class="f1">
											视力档案
										</view>
										<view class="f2">
											<text>{{item.product.has_glassrecord==1?item.product.glassrecord.name:''}}</text>
											<image :src="pre_url+'/static/img/arrowright.png'" >
										</view>
									</view>
								</block>
								
								<!-- product_form表单 start-->
								<view class="">
									<view v-for="(formitem,fidx) in item.formdata" :key="formitem.id" class="form-item">
																	
									<view class="label">{{formitem.val1}}<text v-if="formitem.val3==1" style="color:red"> *</text></view>
									<block v-if="formitem.key=='input'">
										<input type="text" :name="'form'+buydata.bid+'_'+index2+'_'+fidx" class="input input2" :placeholder="formitem.val2" value="" placeholder-style="font-size:28rpx"/>
									</block>
									<block v-if="formitem.key=='textarea'">
										<textarea :name="'form'+buydata.bid+'_'+index2+'_'+fidx" class='textarea' :placeholder="formitem.val2" value="" placeholder-style="font-size:28rpx"/>
									</block>
									<block v-if="formitem.key=='radio'">
										<radio-group class="radio-group" :name="'form'+buydata.bid+'_'+index2+'_'+fidx"  :data-bid="groupBid" :data-idx="fidx" :data-index="index2" @change="setProductRatio">
											<label v-for="(item1,idx1) in formitem.val2" :key="item1.id" class="flex-y-center">
												<radio class="radio" :value="item1"/>{{item1}}
											</label>
										</radio-group>
									</block>
									<block v-if="formitem.key=='checkbox'">
										<checkbox-group :name="'form'+buydata.bid+'_'+index2+'_'+fidx"  class="checkbox-group" :data-bid="groupBid" :data-idx="fidx" :data-index="index2" @change="setProductCheckbox">
											<label v-for="(item1,idx1) in formitem.val2" :key="item1.id" class="flex-y-center">
												<checkbox class="checkbox" :value="item1"/>{{item1}}
											</label>
										</checkbox-group>
									</block>
									<block v-if="formitem.key=='selector'">
										<view class="flex-x-bottom flex-y-center">
											<picker class="picker" mode="selector" :name="'form'+buydata.bid+'_'+index2+'_'+fidx" value="" :range="formitem.val2" @change="productEditorBindPickerChange" :data-bid="groupBid" :data-idx="fidx" :data-index="index2">
												<view v-if="item.editorFormdata[fidx] || item.editorFormdata[fidx]===0"> {{formitem.val2[item.editorFormdata[fidx]]}}</view>
												<view v-else>请选择</view>
											</picker>
											<text class="iconfont iconjiantou" style="color:#999;font-weight:normal"></text>
										</view>
									</block>
									<block v-if="formitem.key=='time'">
										<view class="flex-x-bottom flex-y-center">
											<picker class="picker" mode="time" :name="'form'+buydata.bid+'_'+index2+'_'+fidx" value="" :start="formitem.val2[0]" :end="formitem.val2[1]" :range="formitem.val2" @change="productEditorBindPickerChange" :data-bid="groupBid" :data-idx="fidx" :data-index="index2">
												<view v-if="item.editorFormdata[fidx] "> {{item.editorFormdata[fidx]}}</view>
												<view v-else>请选择</view>
											</picker>
											<text class="iconfont iconjiantou" style="color:#999;font-weight:normal"></text>
										</view>
									</block>
									<block v-if="formitem.key=='date'">
										<view class="flex-x-bottom flex-y-center">
											<picker class="picker" mode="date" :name="'form'+buydata.bid+'_'+index2+'_'+fidx" value="" :start="formitem.val2[0]" :end="formitem.val2[1]" :range="formitem.val2" @change="productEditorBindPickerChange" :data-bid="groupBid" :data-idx="fidx" :data-index="index2">
												<view v-if="item.editorFormdata[fidx] "> {{item.editorFormdata[fidx]}}</view>
												<view v-else>请选择</view>
											</picker>
											<text class="iconfont iconjiantou" style="color:#999;font-weight:normal"></text>
										</view>
									</block>
									<block v-if="formitem.key=='year'">
										<view class="flex-x-bottom flex-y-center">
											<picker class="picker" :name="'form'+buydata.bid+'_'+index2+'_'+fidx" @change="yearChange" :data-bid="groupBid" :data-idx="fidx" :data-index="index2" :range="formitem.val2">
												<view v-if="item.editorFormdata[fidx] "> {{item.editorFormdata[fidx]}}</view>
												<view v-else>请选择</view>
											</picker>
											<text class="iconfont iconjiantou" style="color:#999;font-weight:normal"></text>
										</view>
									</block>
									<block v-if="formitem.key=='region'">
										<view class="flex-x-bottom flex-y-center">
										<uni-data-picker style="flex: 1;width: 100%;" :localdata="regionlist" popup-title="请选择省市区" :placeholder="'请选择省市区'" @change="productRegionChange( $event,groupBid,index2,fidx)" :data-bid="groupBid" :data-idx="fidx" :data-index="index2"></uni-data-picker>
									
										<input type="text" style="display:none;border:none" :name="'form'+buydata.bid+'_'+index2+'_'+fidx" :value="item.editorFormdata[fidx]"/>
										</view>
									</block>
									<block v-if="formitem.key=='upload'">
										<input type="text" style="display:none" :name="'form'+buydata.bid+'_'+index2+'_'+fidx" value=""/>
										<view class="flex" style="flex-wrap:wrap;padding-top:20rpx">
											<view class="form-imgbox" v-if="item.editorFormdata[fidx]">
												<view class="layui-imgbox-close" style="z-index: 2;" @tap="productFormdataRemoveimg" :data-bid="groupBid" :data-idx="fidx" data-type="pic"><image style="display:block;width: 100%;height:100%":src="pre_url+'/static/img/ico-del.png'"></image></view>
												<view class="form-imgbox-img"><image  class="image" :src="item.editorFormdata[fidx]" @click="previewImage" :data-url="item.editorFormdata[fidx]" mode="aspectFit"/></view>
											</view>
											<view v-else class="form-uploadbtn" :style="{background:'url('+pre_url+'/static/img/shaitu_icon.png) no-repeat 50rpx',backgroundSize:'80rpx 80rpx',backgroundColor:'#F3F3F3'}" @click="productEditorChooseImage" :data-bid="groupBid" :data-idx="fidx" :data-index="index2" data-type="pic"></view>
										</view>
									</block>
									<block v-if="formitem.key=='upload_pics'">
									  <input type="text" style="display:none" :name="'form'+buydata.bid+'_'+index2+'_'+fidx" :value="item.editorFormdata && item.editorFormdata[fidx]?item.editorFormdata[fidx].join(','):''" maxlength="-1"/>
									  <view class="flex" style="flex-wrap:wrap;padding-top:20rpx">
										<view v-for="(item2, index2) in item.editorFormdata[fidx]" :key="index2" class="form-imgbox" >
										  <view class="layui-imgbox-close" @tap="productFormdataRemoveimg" :data-bid="groupBid" :data-index="index2" data-type="pics" :data-idx="fidx" :data-formidx="'form'+idx"><image  style="display:block;width: 100%;height:100%":src="pre_url+'/static/img/ico-del.png'" class="image"></image></view>
										  <view class="form-imgbox-img" style="margin-bottom: 10rpx;"><image class="image" :src="item2" @click="previewImage" :data-url="item2" mode="aspectFit" :data-idx="fidx"/></view>
										</view>
										<view class="form-uploadbtn" :style="{background:'url('+pre_url+'/static/img/shaitu_icon.png) no-repeat 50rpx',backgroundSize:'80rpx 80rpx',backgroundColor:'#F3F3F3'}" @click="productEditorChooseImage" :data-bid="groupBid" :data-idx="fidx" :data-index="index2" :data-formidx="'form'+fidx" data-type="pics"></view>
									  </view>
									</block>
									
								</view>
								</view>
								<!-- product_form表单 end-->
								
							</view>
						</view>
            
            <block v-if="!usegiveorder">
              <view class="freight">
                <view class="f1">配送方式</view>
                <view class="freight-ul">
                  <view style="width:100%;overflow-y:hidden;overflow-x:scroll;white-space: nowrap;">
                    <block v-for="(item, idx2) in buydata.freightList" :key="idx2">
                      <view class="freight-li"
                        :style="buydata.freightkey==idx2?'color:'+t('color1')+';background:rgba('+t('color1rgb')+',0.2)':''"
                        @tap="changeFreight" :data-bid="groupBid" :data-index="idx2">{{item.name}}
                      </view>
                    </block>
                  </view>
                </view>
                <view class="freighttips"
                  v-if="buydata.freightList[buydata.freightkey].minpriceset==1 && buydata.freightList[buydata.freightkey].minprice > 0 && buydata.freightList[buydata.freightkey].minprice*1 > buydata.product_price*1">
                  满{{buydata.freightList[buydata.freightkey].minprice}}元起送，还差{{(buydata.freightList[buydata.freightkey].minprice - buydata.product_price).toFixed(2)}}元
                </view>
                <view class="freighttips" v-if="buydata.freightList[buydata.freightkey].isoutjuli==1">超出配送范围</view>
                <view class="freighttips" v-if="buydata.freightList[buydata.freightkey].desc">{{buydata.freightList[buydata.freightkey].desc}}</view>
              </view>

              <view class="price" v-if="buydata.freightList[buydata.freightkey].pstimeset==1">
                <view class="f1">{{buydata.freightList[buydata.freightkey].pstype==1?'取货':'配送'}}时间</view>
                <view class="f2" @tap="choosePstime" :data-bid="groupBid">
                  {{buydata.pstimetext==''?'请选择时间':buydata.pstimetext}}<text class="iconfont iconjiantou"
                    style="color:#999;font-weight:normal"></text>
                </view>
              </view>

              <view class="storeitem" v-if="buydata.freightList[buydata.freightkey].pstype==1 && buydata.freightList[buydata.freightkey].isbusiness==1">
                <view class="panel">
                  <view class="f1">取货地点</view>
                </view>
                <block v-for="(item, idx) in buydata.freightList[buydata.freightkey].storedata" :key="idx">
                  <view class="radio-item" v-if="idx<5 || storeshowall==true" @tap="openLocation" :data-freightkey="buydata.freightkey" :data-storekey="idx" :data-bid="groupBid" :data-index="idx">
                    <view class="f1">
                      <view>{{item.name}}</view>
                      <view v-if="item.address" style="text-align: left;font-size:24rpx;color:#aaaaae;display: -webkit-box;-webkit-box-orient: vertical;-webkit-line-clamp:1;overflow: hidden;">{{item.address}}</view>
                    </view>
                    <text style="color:#f50;">{{item.juli}}</text>
                  </view>
                </block>
                <view v-if="storeshowall==false && (buydata.freightList[buydata.freightkey].storedata).length > 5" class="storeviewmore" @tap="doStoreShowAll">- 查看更多 - </view>
              </view>
              
              <view v-if="!mendianShowType">
                <view class="storeitem" v-if="buydata.freightList[buydata.freightkey].pstype==1 && buydata.freightList[buydata.freightkey].isbusiness!=1">
                  <block v-if="mendian_no_select==0">
                    <view class="panel">
                      <view class="f1">取货地点</view>
                        <view class="f2" @tap="openMendian" :data-bid="buydata.bid" v-if="buydata.freightList[buydata.freightkey].storedata.length > 0" 
                        :data-freightkey="buydata.freightkey"
                        :data-storekey="buydata.freightList[buydata.freightkey].storekey"><text
                          class="iconfont icondingwei"></text>
                          {{buydata.freightList[buydata.freightkey].storedata[buydata.freightList[buydata.freightkey].storekey].name}}
                        </view>
												<view @click="gotoMendianList(buydata.freightList[buydata.freightkey].limit_mendianids,buydata.bid)" class="f2" v-else-if="buydata.freightList[buydata.freightkey].needchange == 1">切换<text class="iconfont iconjiantou" style="color:#999;font-weight:normal"></text></view>
                        <view class="f2" v-else>暂无</view>
                    </view>
                  </block>
                  <block v-else>
                    <view class="panel">
                      <view class="f1">可使用门店</view>
                    </view>
                  </block>
                  <!-- 门店升级 社区团购 -->
                  <block v-if="mendian_upgrade && buydata.bid==0">		
                    <block v-for="(item, idx) in buydata.freightList[buydata.freightkey].storedata" :key="idx">
                      <view class="radio-item"  style="border:10rpx solid #f6f6f6;border-radius:10rpx;padding:10rpx" @click="gotoMendianList(buydata.freightList[buydata.freightkey].limit_mendianids ? buydata.freightList[buydata.freightkey].limit_mendianids : '' ,buydata.bid)">
                        <view class="headimg">
													<image :src="item.pic ? item.pic : `${pre_url}/static/img/touxiang.png`"></image>
                        </view>
                        <view class="f1" style="display: flex;justify-content: space-between;align-items: center;">
                          <view>
                          <view>{{item.name?item.name:'无'}}</view>
                          <view v-if="item.address" class="flex-y-center" style="text-align:left;font-size:24rpx;color:#aaaaae;display: -webkit-box;-webkit-box-orient: vertical;-webkit-line-clamp:1;overflow: hidden;">领取地址：{{item.address}}</view>
                          </view>
                          <view v-if="mendian_change"><text class="iconfont iconjiantou" style="color:#999;font-weight:normal"></text></view>
                        </view>
                      </view>
                    </block>
                  </block>
                  <!--甘尔定制 不用选择门店，可在任意门店核销-->
                  <block v-else-if="mendian_no_select">
                    <block v-for="(item, idx) in buydata.freightList[buydata.freightkey].storedata" :key="idx">
                      <view class="radio-item" :data-bid="buydata.bid" :data-index="idx" v-if="idx<5 || storeshowall==true">
                        <view class="f1">
                          <view>{{item.name}}</view>
                          <view v-if="item.address" class="flex-y-center" style="text-align:left;font-size:24rpx;color:#aaaaae;display: -webkit-box;-webkit-box-orient: vertical;-webkit-line-clamp:1;overflow: hidden;">{{item.address}}</view>
                        </view>
                        <text style="color:#f50;">{{item.juli}}</text>
                      </view>
                    </block>
                  </block>
                  <!-- 默认门店自提样式 -->
                  <block v-else>
                    <block v-for="(item, idx) in buydata.freightList[buydata.freightkey].storedata" :key="idx">
                      <view class="radio-item" @tap.stop="choosestore" :data-bid="groupBid" :data-index="idx" v-if="idx<5 || storeshowall==true">
                        <view class="f1">
                          <view>{{item.name}}</view>
                          <view v-if="item.address" class="flex-y-center" style="text-align:left;font-size:24rpx;color:#aaaaae;display: -webkit-box;-webkit-box-orient: vertical;-webkit-line-clamp:1;overflow: hidden;">{{item.address}}</view>
                        </view>
                        <text style="color:#f50;">{{item.juli}}</text>
                        <view class="radio" :style="buydata.freightList[buydata.freightkey].storekey==idx ? 'background:'+t('color1')+';border:0' : ''">
                          <image class="radio-img" :src="pre_url+'/static/img/checkd.png'" />
                        </view>
                      </view>
                    </block>
                  </block>
                  
                  <view v-if="storeshowall==false && (buydata.freightList[buydata.freightkey].storedata).length > 5" class="storeviewmore" @tap="doStoreShowAll">- 查看更多 - </view>
                </view>
                              
                <!-- 同城配送选门店 -->
                <view class="storeitem" v-if="buydata.freightList[buydata.freightkey].pstype==2 && buydata.freightList[buydata.freightkey].storedata">
                  <view class="panel">
                    <view class="f1">门店</view>
                   <view class="f2" @tap="openMendian" :data-bid="groupBid" v-if="buydata.freightList[buydata.freightkey].storedata.length > 0"
                     :data-freightkey="buydata.freightkey"
                     :data-storekey="buydata.freightList[buydata.freightkey].storekey"><text class="iconfont icondingwei"></text>{{buydata.freightList[buydata.freightkey].storedata[buydata.freightList[buydata.freightkey].storekey].name}}
                    </view>
                    <view class="f2" v-else>暂无</view>
                  </view>
                  <block v-for="(item, idx) in buydata.freightList[buydata.freightkey].storedata" :key="idx">
                    <view class="radio-item" @tap.stop="choosestore" :data-bid="groupBid" :data-index="idx" v-if="idx<5 || storeshowall==true">
                      <view class="f1">
                        <view>{{item.name}}</view>
                        <view v-if="item.address" style="text-align:left;font-size:24rpx;color:#aaaaae;display: -webkit-box;-webkit-box-orient: vertical;-webkit-line-clamp:1;overflow: hidden;">{{item.address}}</view>
                      </view>
                      <text style="color:#f50;">{{item.juli}}</text>
                      <view class="radio" :style="buydata.freightList[buydata.freightkey].storekey==idx ? 'background:'+t('color1')+';border:0' : ''">
                        <image class="radio-img" :src="pre_url+'/static/img/checkd.png'" />
                      </view>
                    </view>
                  </block>
                  <view v-if="storeshowall==false && (buydata.freightList[buydata.freightkey].storedata).length > 5" class="storeviewmore" @tap="doStoreShowAll">- 查看更多 - </view>
                </view>
                <!-- 门店配送 -->
                <view class="storeitem" v-if="buydata.freightList[buydata.freightkey].pstype==5">
                  <view class="panel">
                    <view class="f1">配送{{t('门店')}}</view>
                    <view class="f2" @tap="openMendian" :data-bid="groupBid" v-if="buydata.freightList[buydata.freightkey].storedata.length > 0"
                      :data-freightkey="buydata.freightkey"
                      :data-storekey="buydata.freightList[buydata.freightkey].storekey"><text class="iconfont icondingwei"></text>{{buydata.freightList[buydata.freightkey].storedata[buydata.freightList[buydata.freightkey].storekey].name}}
                    </view>
                    <view class="f2" v-else>暂无</view>
                  </view>
                  <!-- 社区团购不支持多商户 -->
                  <block v-if="mendian_upgrade && groupBid == 0">
                    <!-- 门店配送 社区团购 -->
                    <block v-for="(item, idx) in buydata.freightList[buydata.freightkey].storedata" :key="idx">
                      <view class="radio-item"  style="border:10rpx solid #f6f6f6;border-radius:10rpx;padding:10rpx" @tap="goto" data-url="/pagesB/mendianup/list">
                        <view class="headimg">
                          <image :src="item.pic">
                        </view>
                        <view class="f1" style="display: flex;justify-content: space-between;align-items: center;">
                          <view>
                          <view>{{item.name}}</view>
                          <view v-if="item.address" class="flex-y-center" style="text-align:left;font-size:24rpx;color:#aaaaae;display: -webkit-box;-webkit-box-orient: vertical;-webkit-line-clamp:1;overflow: hidden;">地址：{{item.address}}</view>
                          </view>
                          <view><text class="iconfont iconjiantou" style="color:#999;font-weight:normal"></text></view>
                        </view>
                      </view>
                    </block>
                  </block>
                  <block v-else>
                    <!-- 门店配送 默认样式 -->
                    <block v-for="(item, idx) in buydata.freightList[buydata.freightkey].storedata" :key="idx">
                      <view class="radio-item" @tap.stop="choosestore" :data-bid="groupBid" :data-index="idx" v-if="idx<5 || storeshowall==true">
                        <view class="f1">
                          <view>{{item.name}}</view>
                          <view v-if="item.address" style="text-align:left;font-size:24rpx;color:#aaaaae;display: -webkit-box;-webkit-box-orient: vertical;-webkit-line-clamp:1;overflow: hidden;">{{item.address}}</view>
                        </view>
                        <text style="color:#f50;">{{item.juli}}</text>
                        <view class="radio" :style="buydata.freightList[buydata.freightkey].storekey==idx ? 'background:'+t('color1')+';border:0' : ''">
                          <image class="radio-img" :src="pre_url+'/static/img/checkd.png'" />
                        </view>
                      </view>
                    </block>
                    <view v-if="storeshowall==false && (buydata.freightList[buydata.freightkey].storedata).length > 5" class="storeviewmore" @tap="doStoreShowAll">- 查看更多 - </view>
                  </block>
                </view>
                
              </view>
              <!-- 门店展示方式，1单条展示，点击弹窗展示列表可搜索 -->
							<view v-if="mendianShowType">
                <view class="storeitem" v-if="(buydata.freightList[buydata.freightkey].pstype==1 && buydata.freightList[buydata.freightkey].isbusiness!=1) || buydata.freightList[buydata.freightkey].pstype==5">
                  <view class="panel">
                    <view v-if="buydata.freightList[buydata.freightkey].pstype==1" class="f1">取货地点</view>
                    <view v-if="buydata.freightList[buydata.freightkey].pstype==5" class="f1">配送门店</view>
                    <view class="f2" v-if="buydata.freightList[buydata.freightkey].storedata.length > 0" 
                    @tap="showstore" :data-storedata="buydata.freightList[buydata.freightkey].storedata" :data-storefreightkey="buydata.freightkey" :data-storekey="buydata.freightList[buydata.freightkey].storekey" :data-storebid="groupBid">
                        <text>{{buydata.freightList[buydata.freightkey].storedata[buydata.freightList[buydata.freightkey].storekey].name}}</text>
                        <text class="iconfont iconjiantou" style="color:#999;font-weight:normal"></text>
                    </view>
                    <view class="f2" v-else>暂无</view>
                  </view>
                </view>
              </view>

              <view class="price" v-if="buydata.freightList[buydata.freightkey].pstype==11">
                <view class="f1">选择物流</view>
                <view class="f2" @tap="showType11List" :data-bid="buydata.bid">
                  <text>{{buydata.type11key?buydata.freightList[buydata.freightkey].type11pricedata[buydata.type11key-1].name:'请选择'}}</text><text
                    class="iconfont iconjiantou" style="color:#999;font-weight:normal"></text>
                </view>
              </view>
            </block>

						<view class="price">
							<text class="f1">商品金额</text>
							<text class="f2">¥{{buydata.product_price}}</text>
						</view>
						<view class="price" v-if="!isNull(buydata.product_freezemoney_price) && buydata.product_freezemoney_price>0">
							<text class="f1">{{t('冻结资金')}}</text>
							<text class="f2">{{buydata.product_freezemoney_price}}</text>
						</view>
						<view class="price" v-if="buydata.leveldk_money>0 && buydata.leveldk_show">
							<text class="f1">{{t('会员')}}折扣({{userinfo.discount}}折)</text>
							<text class="f2">-¥{{buydata.leveldk_money}}</text>
						</view>
						<view class="price" v-if="buydata.manjian_money>0">
							<text class="f1">满减活动</text>
							<text class="f2">-¥{{buydata.manjian_money}}</text>
						</view>
						<view v-if="!usegiveorder" class="price">
							<view class="f1">{{buydata.freightList[buydata.freightkey].freight_price_txt || '运费'}}
							
							 <text v-if="buydata.freightList[buydata.freightkey].fwprice_type ==1 " style="color:#aaa;font-size:24rpx;">（商品总售价{{buydata.freightList[buydata.freightkey].fwprice_ratio}}%）</text>

                <!--满额/满件包邮-->
                <block v-if="full_piece_package">
                  <block v-if="buydata.freightList[buydata.freightkey].pstype!=1 && buydata.freightList[buydata.freightkey].freeset==1 && buydata.freightList[buydata.freightkey].fullpieceset == 1">
                    <text style="color:#aaa;font-size:24rpx;">（满{{buydata.freightList[buydata.freightkey].free_price}}元包邮/满{{buydata.freightList[buydata.freightkey].full_piece}}件包邮）</text>
                  </block>
                  <block v-else-if="buydata.freightList[buydata.freightkey].pstype!=1 && buydata.freightList[buydata.freightkey].freeset==1">
                    <text style="color:#aaa;font-size:24rpx;">（满{{buydata.freightList[buydata.freightkey].free_price}}元包邮）</text>
                  </block>
                  <block v-else-if="buydata.freightList[buydata.freightkey].pstype!=1 && buydata.freightList[buydata.freightkey].fullpieceset==1">
                    <text style="color:#aaa;font-size:24rpx;">（满{{buydata.freightList[buydata.freightkey].full_piece}}件包邮）</text>
                  </block>
                </block>
                <block v-else>
                  <text v-if="buydata.freightList[buydata.freightkey].pstype!=1 && buydata.freightList[buydata.freightkey].freeset==1" style="color:#aaa;font-size:24rpx;">（满{{buydata.freightList[buydata.freightkey].free_price}}元包邮）</text>
                </block>

              </view>
							<text class="f2">+¥{{buydata.freightList[buydata.freightkey].freight_price}}</text>
						</view>
						
						<view style="border-bottom:1px #ededed dashed;"  >
							<block v-if="buydata.freightList[buydata.freightkey].show_add_floor ==1">
								<view class="price" >
									<view class="f1 flex-y-center"> 电梯 <image class="title-icon" :src="`${pre_url}/static/img/admin/jieshiicon.png`" :data-bid="buydata.bid" @click="showExplain"></image></view>
									<view class="switch-view flex">
										<view class='switch-options' data-index="0" :data-bid="buydata.bid" @tap='diantiChange' :style="{background:buydata.freightList[buydata.freightkey].have_dianti == 0 ? `rgba(${t('color1rgb')},0.2)`:'',borderColor:buydata.freightList[buydata.freightkey].have_dianti == 0 ? t('color1'):'',color:buydata.freightList[buydata.freightkey].have_dianti == 0 ? t('color1'):''}">无电梯</view>
										<view class='switch-options' data-index="1" :data-bid="buydata.bid" @tap='diantiChange' :style="{background:buydata.freightList[buydata.freightkey].have_dianti == 1 ? `rgba(${t('color1rgb')},0.2)`:'',borderColor:buydata.freightList[buydata.freightkey].have_dianti == 1 ? t('color1'):'',color:buydata.freightList[buydata.freightkey].have_dianti == 1 ? t('color1'):''}">有电梯</view>
									</view>
								</view>
								<view class="price flex-bt" v-if="buydata.freightList[buydata.freightkey].have_dianti == 0">
									<text class="f1">楼层</text>
									<input type="number" class="input" :value="floor" placeholder="请输入楼层,例如6" :data-bid="buydata.bid" @input="inputFloornum" style="text-align: right;" placeholder-style="color:#626262;font-size:28rpx;text-align: right" />
								</view>
							
							</block>
							<!-- 上楼费 -->
							<view class="price" v-if="buydata.freightList[buydata.freightkey].up_floor_fee  > 0">
								<view class="f1 flex-y-center"> 上楼费 <image class="title-icon" :src="`${pre_url}/static/img/admin/jieshiicon.png`" :data-bid="buydata.bid" @click="showExplain"></image></view>
								<text class="f2" style="color: red;">¥{{ buydata.freightList[buydata.freightkey].up_floor_fee}}</text>
							</view>
						</view>
						<!-- 上楼费end -->
						
						<view class="price" v-if="buydata.weight_price>0">
							<text class="f1"> {{t('包装费')}}</text>
							<text class="f2">+¥{{buydata.weight_price}}</text>
						</view>
						
						<!--押金-->
						<view class="deposit" v-if="buydata.deposit_list && buydata.deposit_list.length > 0"> 
							<view v-for="(ditem, dindex) in buydata.deposit_list" style="margin-bottom: 20rpx;" >
								<view class="flex flex-bt" style="padding: 10rpx;" :data-index="dindex" :data-bid="groupBid" @tap="chooseDeposit">
									<view class="t1">【{{ditem.deposit_name}}】</view>
									<view class="radio" :style="inArray(dindex,buydata.deposit_list_key)? 'background:'+t('color1')+';border:0' : ''">
									  <image class="radio-img" :src="pre_url+'/static/img/checkd.png'" />
									</view>
								</view>
								<view class="flex flex-bt" style="padding: 10rpx; ">
									<view class="t2">您还需要支付{{t('押金')}}物品数量</view>
									<view style="color:red;"> × {{ditem.buy_num}}</view>
								</view>
								<view class="flex flex-bt" style="padding: 10rpx; 0">
									<view class="t2">支付{{t('押金')}}金额</view>
									<view style="color:red;">￥{{ditem.totalmoney}}</view>
								</view>
							</view>							
						</view>
						<!--押金end-->
						<view class="price">
							<view class="f1">{{t('优惠券')}}</view>
							<view v-if="buydata.couponCount > 0" class="f2" @tap="showCouponList" :data-bid="groupBid">
								<block v-if="(buydata.coupons).length>0">
									<text v-if="is_coupon_auto_multi">
										已使用{{(buydata.coupons).length}}张优惠券
									</text>
									<text v-else class="tag" :style="{background:t('color1')}" v-for="(item,index) in buydata.coupons">{{item.couponname}}</text>
								</block>
								<block v-else>
									<text class="tag" :style="{background:t('color1')}">{{buydata.couponCount+'张可用'}}</text>
								</block>
								<text class="iconfont iconjiantou" style="color:#999;font-weight:normal"></text>
							</view>
							<view class="f2" v-else>
								<text class="couponname" style="color:#999">无可用{{t('优惠券')}}</text>
							</view>
						</view>
						<view class="price" v-if="buydata.cuxiaoCount > 0">
							<view class="f1">促销活动</view>
							<view class="f2" @tap="showCuxiaoList" :data-bid="buydata.bid">
								<block v-if="buydata.cuxiaonameArr && buydata.cuxiaonameArr.length > 0">
									<view :style="{background:t('color1')}" class="tag" v-for="(item,index) in buydata.cuxiaonameArr" :key="index">{{item}}</view>
								</block>
								<block v-else>
									<text :style="{background:t('color1')}" class="tag">{{buydata.cuxiaoname?buydata.cuxiaoname:buydata.cuxiaoCount+'个可用'}}</text>
								</block>
							</view>
							<text class="iconfont iconjiantou" style="color:#999;font-weight:normal"></text>
						</view>
						<!-- 水票 -->
						<block v-if="buydata.water_use_ck_coupon_status ==1 ">
						<view class="price">
							<view class="f1">我的{{t('电子水票')}}</view>
							<view class="f2 flex-x-bottom">
								<view class="couponname">剩余{{buydata.water_couponCount+'张'}}</view>
								<!-- <view class="flex-y-center" :style="{color:t('color1')}" @tap="toBuyWaterCoupon"  :data-bid="groupBid"  :data-url="'/pagesD/deposit/couponlist'">去购买 
								<text class="iconfont iconjiantou" style="color:#999;font-weight:normal;margin-left: 15rpx;"></text></view> -->
							</view>
						</view>
						<view class="price">
							<view class="f1">{{t('电子水票')}}</view>
							<view v-if="buydata.water_couponCount > 0" class="f2 flex-y-center" @tap="showSpCouponList" :data-bid="groupBid">
								<view style="flex: 1;">
									<block v-if="(buydata.water_coupons).length>0">
										<text v-if="is_coupon_auto_multi">
											已使用{{(buydata.water_coupons).length}}张优惠券
										</text>
										<text v-else class="tag" :style="{background:t('color1')}" v-for="(item,index) in buydata.water_coupons">{{item.couponname}} x {{item.coupon_use_water_num}}</text>
									</block>
									<block v-else>
										<text class="tag" :style="{color:t('color1')}">{{buydata.water_couponCount+'张可用'}}</text>
									</block>
								</view>
								
								
								<text class="iconfont iconjiantou" style="color:#999;font-weight:normal"></text>
							</view>
							<view class="f2" v-else  @tap="toBuyWaterCoupon"  :data-bid="groupBid"  :data-url="'/pagesD/deposit/couponlist' ">
								<text :style="{color:t('color1')}">去购买</text>
								<text class="iconfont iconjiantou" style="color:#999;font-weight:normal"></text>
							</view>
						</view>
						</block>
						
						<view class="price" v-if="buydata.business.invoice > 0">
							<view class="f1">发票</view>
							<view class="f2" @tap="showInvoice" :data-url="'/pages/shop/invoice?bid=' + buydata.bid + '&prodata=' + opt.prodata" :data-bid="buydata.bid" :data-index="groupBid">
								<text style="font-size:24rpx" v-if="buydata.tempInvoice && buydata.tempInvoice.invoice_name">
								<text v-if="buydata.tempInvoice && buydata.tempInvoice.name_type == 1">个人 - </text>
								<text v-if="buydata.tempInvoice && buydata.tempInvoice.name_type == 2">公司 - </text>
								{{buydata.tempInvoice.invoice_name}}</text><text class="iconfont iconjiantou" style="color:#999;font-weight:normal"></text>
							</view>
						</view>
						<view class="price" v-if="buydata.business.invoice > 0 && buydata.business.invoice_rate > 0 && buydata.tempInvoice && buydata.tempInvoice.invoice_name">
							<text class="f1">发票费用</text>
							<text class="f2">¥{{buydata.invoice_price}}</text>
						</view>
						<view class="form-item" v-if="inArray('discount_code_zc',custom)">
							<view class="label">优惠代码</view>
								<input type="text" @input="inputCouponCode" :name="'discount_code'+buydata.bid" :data-bid="groupBid" :data-index="groupBid" class="input" placeholder="请输入优惠代码" placeholder-style="font-size:28rpx"/>
						</view>
						
						<view class="price"
							v-if="business_selfscore == 1 && scoredkdataArr[groupBid].score2money>0 && (scoredkdataArr[groupBid].scoremaxtype==0 || (scoredkdataArr[groupBid].scoremaxtype==1 && scoredkdataArr[buydata.bid].scoredkmaxmoney>0))">
							<checkbox-group @change="scoredk2" :data-bid="groupBid" class="flex" style="width:100%">
								<view class="f1">
									<view>{{scoredkdataArr[groupBid].score*1}} {{t('积分')}}可抵扣 <text
											style="color:#e94745">{{scoredkdataArr[groupBid].scoredk_money*1}}</text> 元</view>
									<view style="font-size:22rpx;color:#999"
										v-if="scoredkdataArr[groupBid].scoremaxtype==0 && scoredkdataArr[groupBid].scoredkmaxpercent > 0 && scoredkdataArr[groupBid].scoredkmaxpercent<=100">
                    最多可抵扣订单优惠后金额的{{scoredkdataArr[groupBid].scoredkmaxpercent}}%</view>
									<view style="font-size:22rpx;color:#999" v-else-if="scoredkdataArr[groupBid].scoremaxtype==1">
										最多可抵扣{{scoredkdataArr[groupBid].scoredkmaxmoney}}元</view>
								</view>
								<view class="f2" style="font-weight:normal">使用{{t('积分')}}抵扣
									<checkbox :key="`${scoreCheckboxKey}-1`" value="1" style="margin-left:6px;transform:scale(.8)"></checkbox>
								</view>
							</checkbox-group>
						</view>
                        
            <view class="price" v-if="moneydec &&shop_num>1 && userinfo.money>0 && ((!buydata.money_dec_type && buydata.money_dec_rate>0) || (buydata.money_dec_type ==1 && buydata.money_dec_money>0)) ">
              <checkbox-group @change="moneydk" :data-bid="groupBid" :data-type="buydata.money_dec_type" :data-rate="buydata.money_dec_rate" :data-money="buydata.money_dec_money" class="flex" style="width:100%">
                <view class="f1">
                  <view>
                      {{t('余额')}}抵扣（余额：<text style="color:#e94745">{{userinfo.money}}</text>元）
                  </view>
                  <view style="font-size:24rpx;color:#999" >
                    1、选择此项提交订单时将直接扣除{{t('余额')}}
                  </view>
                  <view style="font-size:24rpx;color:#999" >
                    <text v-if="buydata.money_dec_type && buydata.money_dec_type == 1">
                      2、最多可抵扣{{buydata.money_dec_money}}元
                    </text>
                    <text v-else>
                      2、最多可抵扣订单金额的{{buydata.money_dec_rate}}%
                    </text>
                  </view>
                </view>
                <view class="f2" style="font-weight:normal">
                  使用{{t('余额')}}抵扣
                  <checkbox value="1" style="margin-left:6px;transform:scale(.8)"></checkbox>
                </view>
              </checkbox-group>
            </view>
						<view style="display:none">{{test}}</view>
						<view class="form-item" v-for="(item,idx) in buydata.freightList[buydata.freightkey].formdata" :key="item.id">
							<view class="label">{{item.val1}}<text v-if="item.val3==1" style="color:red"> *</text></view>
							<block v-if="item.key=='input'">
								<input type="text" :name="'form'+buydata.bid+'_'+idx" class="input" :placeholder="item.val2" :value="item.val4?item.val4:''" placeholder-style="font-size:28rpx"/>
							</block>
							<block v-if="item.key=='textarea'">
								<textarea :name="'form'+buydata.bid+'_'+idx" class='textarea' :placeholder="item.val2" :value="item.val4?item.val4:''" placeholder-style="font-size:28rpx"/>
							</block>
							<block v-if="item.key=='radio'">
								<radio-group class="radio-group" :name="'form'+buydata.bid+'_'+idx">
									<label v-for="(item1,idx1) in item.val2" :key="item1.id" class="flex-y-center">
										<radio class="radio" :value="item1"/>{{item1}}
									</label>
								</radio-group>
							</block>
							<block v-if="item.key=='checkbox'">
								<checkbox-group :name="'form'+buydata.bid+'_'+idx" class="checkbox-group">
									<label v-for="(item1,idx1) in item.val2" :key="item1.id" class="flex-y-center">
										<checkbox class="checkbox" :value="item1"/>{{item1}}
									</label>
								</checkbox-group>
							</block>
							<block v-if="item.key=='selector'">
								<view class="flex-x-bottom flex-y-center">
									<picker class="picker" mode="selector" :name="'form'+buydata.bid+'_'+idx" :value="(buydata.editorFormdata[idx] || buydata.editorFormdata[idx]===0)?buydata.editorFormdata[idx]:''" :range="item.val2" @change="editorBindPickerChange" :data-bid="groupBid" :data-idx="idx">
										<view v-if="buydata.editorFormdata[idx] || buydata.editorFormdata[idx]===0"> {{item.val2[buydata.editorFormdata[idx]]}}</view>
										<view v-else>请选择</view>
									</picker>
									<text class="iconfont iconjiantou" style="color:#999;font-weight:normal"></text>
								</view>
							</block>
							<block v-if="item.key=='time'">
								<view class="flex-x-bottom flex-y-center">
									<picker class="picker" mode="time" :name="'form'+buydata.bid+'_'+idx" :value="(buydata.editorFormdata[idx] || buydata.editorFormdata[idx]===0)?buydata.editorFormdata[idx]:''" :start="item.val2[0]" :end="item.val2[1]" :range="item.val2" @change="editorBindPickerChange" :data-bid="groupBid" :data-idx="idx">
										<view v-if="buydata.editorFormdata[idx]">{{buydata.editorFormdata[idx]}}</view>
										<view v-else>请选择</view>
									</picker>
									<text class="iconfont iconjiantou" style="color:#999;font-weight:normal"></text>
								</view>
							</block>
							<block v-if="item.key=='date'">
								<view class="flex-x-bottom flex-y-center">
									<picker class="picker" mode="date" :name="'form'+buydata.bid+'_'+idx" :value="(buydata.editorFormdata[idx] || buydata.editorFormdata[idx]===0)?buydata.editorFormdata[idx]:''" :start="item.val2[0]" :end="item.val2[1]" :range="item.val2" @change="editorBindPickerChange" :data-bid="groupBid" :data-idx="idx">
										<view v-if="buydata.editorFormdata[idx]">{{buydata.editorFormdata[idx]}}</view>
										<view v-else>请选择</view>
									</picker>
									<text class="iconfont iconjiantou" style="color:#999;font-weight:normal"></text>
								</view>
							</block>
							<block v-if="item.key=='upload'">
								<input type="text" style="display:none" :name="'form'+buydata.bid+'_'+idx" :value="buydata.editorFormdata[idx]"/>
								<view class="flex" style="flex-wrap:wrap;padding-top:20rpx">
									<view class="form-imgbox" v-if="buydata.editorFormdata[idx]">
										<view class="layui-imgbox-close" style="z-index: 2;" @tap="removeimg" :data-bid="groupBid" :data-idx="idx" data-type="pic"><image style="display:block" :src="pre_url+'/static/img/ico-del.png'"></image></view>
										<view class="form-imgbox-img"><image class="image" :src="buydata.editorFormdata[idx]" @click="previewImage" :data-url="buydata.editorFormdata[idx]" mode="aspectFit"/></view>
									</view>
									<view v-else class="form-uploadbtn" :style="{background:'url('+pre_url+'/static/img/shaitu_icon.png) no-repeat 50rpx',backgroundSize:'80rpx 80rpx',backgroundColor:'#F3F3F3'}" @click="editorChooseImage" :data-bid="groupBid" :data-idx="idx" data-type="pic"></view>
								</view>
							</block>
              <block v-if="item.key=='upload_pics'">
                <input type="text" style="display:none" :name="'form'+buydata.bid+'_'+idx" :value="buydata.editorFormdata && buydata.editorFormdata[idx]?buydata.editorFormdata[idx].join(','):''" maxlength="-1"/>
                <view class="flex" style="flex-wrap:wrap;padding-top:20rpx">
                  <view v-for="(item2, index2) in buydata.editorFormdata[idx]" :key="index2" class="form-imgbox" >
                    <view class="layui-imgbox-close" @tap="removeimg" :data-bid="groupBid" :data-index="index2" data-type="pics" :data-idx="idx" :data-formidx="'form'+idx"><image :src="pre_url+'/static/img/ico-del.png'" class="image"></image></view>
                    <view class="form-imgbox-img" style="margin-bottom: 10rpx;"><image class="image" :src="item2" @click="previewImage" :data-url="item2" mode="aspectFit" :data-idx="idx"/></view>
                  </view>
                  <view class="form-uploadbtn" :style="{background:'url('+pre_url+'/static/img/shaitu_icon.png) no-repeat 50rpx',backgroundSize:'80rpx 80rpx',backgroundColor:'#F3F3F3'}" @click="editorChooseImages" :data-bid="groupBid" :data-idx="idx" :data-formidx="'form'+idx" data-type="pics"></view>
                </view>
              </block>
						</view>
					</view>
				</view>
			 
				<!-- 分期 -->
				<view class="buydata" v-if='fenqiData.length > 0'>
					<view class="fenqi-checkbox">
						<checkbox-group @change="checkFenqi" class="flex" style="width:100%;align-items: center;justify-content: space-between;">
							<view class="f1">
								分期支付
							</view>
							<view class="f2">使用分期支付
								<checkbox value="1" style="margin-left:6px;transform:scale(.8)"></checkbox>
							</view>
						</checkbox-group>
					</view>
					<view class="fenqi-list-view"  v-if='fenqi_type == 1'>
						<!-- <scroll-view scroll-x style="white-space: nowrap;width: 100%;"> -->
							<block v-for="(item,index) in fenqiData">
								<view class="fenqi-options">
									<view class="fenqi-num">
									<text>{{item.fenqi_num}}期</text>
									<text class="fenqi-bili" v-if="item.fenqi_num_ratio">支付比例{{item.fenqi_num_ratio}}%</text>
									</view>
									<view class='fenqi-give' v-if="item.fenqi_give_num">包含{{item.fenqi_give_num}}张{{t('优惠券')}}</view>
									<view class='fenqi-give' v-else>无赠送{{t('优惠券')}}</view>
								</view>
							</block>
						<!-- </scroll-view> -->
					</view>
				</view>
				<!-- 遍历结束 -->
				<view class="scoredk" v-if="buy_selectmember">
					<view class="price">
						<view class="f1">选择会员</view>
						<view class="f2" @tap="showMemberList">
							<text>{{checkMem.id?checkMem.nickname:'请选择'}}</text><text class="iconfont iconjiantou" style="color:#999;font-weight:normal"></text>
						</view>
					</view>
				</view>
				<!-- 积分抵扣 -->
				<view class="scoredk" v-if="!shop_buy_score_default_deduction && business_selfscore == 0 && userinfo.score2money>0 && (userinfo.scoremaxtype==0 || (userinfo.scoremaxtype==1 && userinfo.scoredkmaxmoney>0))">
					<checkbox-group @change="scoredk" class="flex" style="width:100%">
						<view class="f1">
							<view>{{userinfo.score*1}} {{t('积分')}}可抵扣 <text
									style="color:#e94745">{{userinfo.scoredk_money*1}}</text>元</view>
							<view style="font-size:22rpx;color:#999"
								v-if="userinfo.scoremaxtype==0 && userinfo.scoredkmaxpercent > 0 && userinfo.scoredkmaxpercent<=100">
                最多可抵扣订单优惠后金额的{{userinfo.scoredkmaxpercent}}%</view>
							<view style="font-size:22rpx;color:#999" v-else-if="userinfo.scoremaxtype==1">
								最多可抵扣{{userinfo.scoredkmaxmoney}}元</view>
						</view>
						<view class="f2">使用{{t('积分')}}抵扣
							<checkbox :key="`${scoreCheckboxKey}-2`" value="1" style="margin-left:6px;transform:scale(.8)"></checkbox>
						</view>
					</checkbox-group>
				</view>
				<!-- 余额抵扣 -->

        <view class="scoredk" v-if="moneydec && shop_num<=1 && userinfo.money>0 && (( !allbuydata[shop_bid].money_dec_type && allbuydata[shop_bid].money_dec_rate>0) || (allbuydata[shop_bid].money_dec_type == 1 && allbuydata[shop_bid].money_dec_money>0))">
          <checkbox-group @change="moneydk" :data-bid="shop_bid" :data-type="allbuydata[shop_bid].money_dec_type" :data-rate="allbuydata[shop_bid].money_dec_rate" :data-money="allbuydata[shop_bid].money_dec_money" class="flex" style="width:100%">
            <view class="f1">
              <view>
                  {{t('余额')}}抵扣（余额：<text style="color:#e94745">{{userinfo.money}}</text>元）
              </view>
              <view style="font-size:24rpx;color:#999" >
                1、选择此项提交订单时将直接扣除{{t('余额')}}
              </view>
              <view style="font-size:24rpx;color:#999" >
                <text v-if="allbuydata[shop_bid].money_dec_type &&allbuydata[shop_bid].money_dec_type == 1">
                  2、最多可抵扣{{allbuydata[shop_bid].money_dec_money}}元
                </text>
                <text v-else>
                  2、最多可抵扣订单金额的{{allbuydata[shop_bid].money_dec_rate}}%
                </text>
              </view>
            </view>
            <view class="f2" >
              使用{{t('余额')}}抵扣
              <checkbox value="1" style="margin-left:6px;transform:scale(.8)"></checkbox>
            </view>
          </checkbox-group>
        </view>
				<view class="scoredk" v-if="userinfo.tongzheng2money>0 && (userinfo.tongzhengmaxtype==0 || (userinfo.tongzhengmaxtype==1 && userinfo.tongzhengdkmaxmoney>0))">
					<checkbox-group @change="tongzhengdk" class="flex" style="width:100%">
						<view class="f1">
							<view>{{userinfo.tongzheng*1}} {{t('通证')}}可抵扣 <text
									style="color:#e94745">{{userinfo.tongzhengdk_money*1}}</text> 元</view>
							<view style="font-size:22rpx;color:#999"
								v-if="userinfo.tongzhengmaxtype==0 && userinfo.tongzhengdkmaxpercent > 0 && userinfo.tongzhengdkmaxpercent<100">
								最多可抵扣订单金额的{{userinfo.tongzhengdkmaxpercent}}%</view>
							<view style="font-size:22rpx;color:#999" v-else-if="userinfo.tongzhengmaxtype==1">
								最多可抵扣{{userinfo.tongzhengdkmaxmoney}}元</view>
						</view>
						<view class="f2">使用{{t('通证')}}抵扣
							<checkbox value="1" style="margin-left:6px;transform:scale(.8)"></checkbox>
						</view>
					</checkbox-group>
				</view>
				<!-- 金银值抵扣 -->
        <view v-if="goldsilverlist && goldsilverlist.length>0" style="background:#fff;width:94%;margin:auto;border-radius:20rpx;overflow: hidden;">
          <view class="scoredk flex" style="margin:0;margin-bottom: 0;width:100%;">
            <view class="f1">抵扣类型</view>
            <view class="f2">
              <radio-group class="radio-group flex" style="width: 100%;width:100%;justify-content:flex-end;">
                <view v-for="(item,index) in goldsilverlist">
                    <radio class="radio" :checked="goldsilverindex == index?true:false" @tap="goldsilverdk" :data-index="index" :data-type="item.type"  :data-value="item.value" :value="item.type" style="margin-left:6px;transform:scale(.8)"/>{{item.name}}
                </view>
              </radio-group>
            </view>
          </view>
          <view class="scoredk flex" style="margin:0;margin-bottom: 0;width:100%;">
            <text class="f1">抵扣金额</text>
            <text class="f2" style="color: #000;font-weight: bold;">-¥{{goldsilverdec}}</text>
          </view>
        </view>
        
        <!-- 抵扣金抵扣 -->
        <view class="scoredk" v-if="userinfo.dedamount>0 && userinfo.dedamount_dkmoney>0">
        	<checkbox-group @change="dedamountdk" class="flex" style="width:100%">
        		<view class="f1">
        			<view>{{userinfo.dedamount*1}} <text style="font-weight: bold;">抵扣金</text>可抵扣 
              <text style="color:#e94745">{{userinfo.dedamount_dkmoney*1}}</text>元</view>
              <view style="font-size:24rpx;color:#999" >
               选择此项提交订单时将直接扣除抵扣金
              </view>
        		</view>
        		<view class="f2"> 使用<text style="font-weight: bold;">抵扣金</text>抵扣
        			<checkbox value="1" :checked="usededamount" style="margin-left:6px;transform:scale(.8)"></checkbox>
        		</view>
        	</checkbox-group>
        </view>
        <!-- 抵扣金抵扣 -->
        
        <!-- 产品积分抵扣s -->
        <view class="scoredk" v-if="userinfo.shopscore>0 && userinfo.shopscoredk_money>0 && userinfo.shopscoredkmaxpercent>0">
        	<checkbox-group @change="shopscoredk" class="flex" style="width:100%">
        		<view class="f1">
        			<view>{{userinfo.shopscore*1}} {{t('产品积分')}}可抵扣 <text
        					style="color:#e94745">{{userinfo.shopscoredk_money*1}}</text>元</view>
        			<view style="font-size:22rpx;color:#999" v-if="userinfo.shopscoremaxtype==0">
                1、最多可抵扣订单金额的{{userinfo.shopscoredkmaxpercent<=100?userinfo.shopscoredkmaxpercent:'100'}}%
              </view>
        			<view style="font-size:22rpx;color:#999" v-else-if="userinfo.shopscoremaxtype==1">
        				1、最多可抵扣{{userinfo.shopscoredkmaxmoney}}元
              </view>
              <view style="font-size:24rpx;color:#999" >
                2、提交订单时将直接扣除{{t('产品积分')}}
              </view>
        		</view>
        		<view class="f2">使用{{t('产品积分')}}抵扣
        			<checkbox value="1" style="margin-left:6px;transform:scale(.8)"></checkbox>
        		</view>
        	</checkbox-group>
        </view>
        <!-- 产品积分抵扣e -->
        
        <!-- 赠送礼物 s-->
        <view class="scoredk" v-if="giveorder == 1" style="display: block;">
          <view style="display: flex;align-items: center;">
            <checkbox-group @change="changeusegiveorder" class="flex" style="width:100%;align-items: center;">
              <view class="f1">
                <view>赠好友</view>
                <view style="font-size:22rpx;color:#999">
                  选择赠好友后，需要在支付完后{{giveorder_validtime}}小时内分享给好友，并且需好友点击填写收货地址领取，否则超时会取消订单
                </view>
              </view>
              <view class="f2">
                <checkbox value="1" style="margin-left:6px;transform:scale(.8)"></checkbox>
              </view>
            </checkbox-group>
          </view>
          <view v-if="usegiveorder">
            <view class="form-item">
              <view class="label">分享标题</view>
                <input type="text" @input="inputGiveordertitle"class="input" placeholder="请输入分享标题" placeholder-style="font-size:28rpx"/>
            </view>
            <view class="form-item">
              <view class="label">分享图片</view>
              <view>
                <input type="text" style="display:none"/>
                <view class="flex" style="flex-wrap:wrap;padding-top:20rpx">
                  <view class="form-imgbox" v-if="giveorderpic">
                    <view class="layui-imgbox-close" style="z-index: 2;" @tap="removeimg2" data-index="0" data-field="giveorderpic"><image style="display:block" :src="pre_url+'/static/img/ico-del.png'"></image></view>
                    <view class="form-imgbox-img"><image class="image" :src="giveorderpic" @click="previewImage" :data-url="giveorderpic" mode="aspectFit"/></view>
                  </view>
                  <view v-else class="form-uploadbtn" :style="{background:'url('+pre_url+'/static/img/shaitu_icon.png) no-repeat 50rpx',backgroundSize:'80rpx 80rpx',backgroundColor:'#F3F3F3'}" @click="uploadimg2" data-field="giveorderpic"></view>
                </view>
              </view>
            </view>
          </view>
        </view>
        <!-- 赠送礼物end -->
        
				<!-- 手工活协议 -->
        <view v-if="ishand && hwset" class="xycss1">
          <checkbox-group @change="isagreeChange" style="display: inline-block;">
              <checkbox style="transform: scale(0.6)"  value="1" :checked="isagree"/>
              <text>我已阅读并同意</text>
          </checkbox-group>
          <text @tap="showxieyiFun" :style="'color:'+t('color1')">{{hwset.hwname}}</text>
        </view>
				<!-- 协议 -->
				<view v-if="show_product_xieyi && product_xieyi" class="xycss1">
					<checkbox-group @change="isagreeProChange" style="display: inline-block;">
							<checkbox style="transform: scale(0.6)"  value="1" :checked="isagree_pro"/>
							<text>我已阅读并同意</text>
					</checkbox-group>
					<text  v-for="(item, index) in product_xieyi" :key="index" @tap="showproductxieyi(index)" :style="'color:'+t('color1')">《{{item.name}}》</text>
				</view>

				<view v-if="shop_buy_score_default_deduction && business_selfscore == 0 && userinfo.score2money>0 && (userinfo.scoremaxtype==0 || (userinfo.scoremaxtype==1 && userinfo.scoredkmaxmoney>0))" style="width: 100%; height:260rpx;"></view>
				<view v-else style="width: 100%; height:182rpx;"></view>

				<view class="footer footerTop" :style="{background:'linear-gradient(90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)',color:'#FFF'}" v-if="order_discount_rand && order_discount_rand.status == 1">
						下单随机立减 {{order_discount_rand.money_min}}-{{order_discount_rand.money_max}}
				</view>

        <!-- 积分抵扣默认勾选-->
        <view class="footer footersocre" v-if="shop_buy_score_default_deduction && business_selfscore == 0 && userinfo.score2money>0 && (userinfo.scoremaxtype==0 || (userinfo.scoremaxtype==1 && userinfo.scoredkmaxmoney>0))">
          <checkbox-group @change="scoredk" class="flex" style="width:100%">
            <view class="f1" style="width: 93%;flex:1">
              <view>{{userinfo.score*1}} {{t('积分')}}可抵扣 <text
                  style="color:#e94745">{{userinfo.scoredk_money*1}}</text>元</view>
              <view style="font-size:22rpx;color:#999"
                    v-if="userinfo.scoremaxtype==0 && userinfo.scoredkmaxpercent > 0 && userinfo.scoredkmaxpercent<=100">
                最多可抵扣订单优惠后金额的{{userinfo.scoredkmaxpercent}}%</view>
              <view style="font-size:22rpx;color:#999" v-else-if="userinfo.scoremaxtype==1">
                最多可抵扣{{userinfo.scoredkmaxmoney}}元</view>
            </view>
            <view class="f2">使用{{t('积分')}}抵扣
              <checkbox :key="`${scoreCheckboxKey}-3`" value="1" :checked="default_deduction_checked" style="margin-left:6px;transform:scale(.8);text-align: right;flex:1"></checkbox>
            </view>
          </checkbox-group>
        </view>

        <view v-if="shopbuy_sign" class="footer flex" style="padding-bottom: calc(114rpx + env(safe-area-inset-bottom))">
          <view class="read-agreement flex flex-y-center" >
            <view class="flex flex-y-center">
              请先阅读并签订<view style="color:red"  @tap="showhetongFun">《{{sign_contract_name}}》</view>
            </view>
          </view>
        </view>
				<view class="footer flex notabbarbot">
					<view class="text1 flex1">总计：
						<block  v-if="showprice_dollar && usdalltotalprice">
						<text style="font-weight:bold;font-size:36rpx;margin-right: 10rpx;">${{usdalltotalprice}}</text>
						<text style="font-weight:bold;font-size:32rpx">￥{{alltotalprice}}</text>
						</block>
						<block v-else>
								<text style="font-weight:bold;font-size:36rpx" v-if="allservice_fee > 0">￥{{alltotalprice}} + {{allservice_fee}}{{t('服务费')}}</text>
								<text style="font-weight:bold;font-size:36rpx" v-else>￥{{alltotalprice}}</text>
								<text style="font-weight:bold;font-size:36rpx" v-if="allfreezemoney_price > 0"> + {{allfreezemoney_price}}{{t('冻结资金')}}</text>
						</block>
					</view>
					<button class="op" form-type="submit" :style="{background:'linear-gradient(-90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'}" :disabled="submitDisabled">
						提交订单</button>
				</view>
			</form>

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
											<text v-if="invoice && invoice.name_type == 1">个人</text>
											<text v-if="invoice && invoice.name_type == 2">公司</text>
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
									<input class="t2" type="text" placeholder="抬头名称" placeholder-style="font-size:28rpx;color:#BBBBBB" name="invoice_name" :disabled="inputDisabled" :value="invoice ? invoice.invoice_name : ''" ></input>
								</view>
								<view class="item" v-if="name_type_select == 2">
									<text class="t1">公司税号</text>
									<input class="t2" type="text" placeholder="公司税号" placeholder-style="font-size:28rpx;color:#BBBBBB" name="tax_no" :disabled="inputDisabled" :value="invoice ? invoice.tax_no : ''"></input>
								</view>
								<view class="item" v-if="invoice_type_select == 2">
									<text class="t1">注册地址</text>
									<input class="t2" type="text" placeholder="注册地址" placeholder-style="font-size:28rpx;color:#BBBBBB" name="address" :disabled="inputDisabled" :value="invoice ? invoice.address : ''"></input>
								</view>
								<view class="item" v-if="invoice_type_select == 2">
									<text class="t1">注册电话</text>
									<input class="t2" type="text" placeholder="注册电话" placeholder-style="font-size:28rpx;color:#BBBBBB" name="tel" :disabled="inputDisabled" :value="invoice ? invoice.tel : ''"></input>
								</view>
								<view class="item" v-if="invoice_type_select == 2">
									<text class="t1">开户银行</text>
									<input class="t2" type="text" placeholder="开户银行" placeholder-style="font-size:28rpx;color:#BBBBBB" name="bank_name" :disabled="inputDisabled" :value="invoice ? invoice.bank_name : ''"></input>
								</view>
								<view class="item" v-if="invoice_type_select == 2">
									<text class="t1">银行账号</text>
									<input class="t2" type="text" placeholder="银行账号" placeholder-style="font-size:28rpx;color:#BBBBBB" name="bank_account" :disabled="inputDisabled" :value="invoice ? invoice.bank_account : ''"></input>
								</view>
								<view class="item">
									<text class="t1">手机号</text>
									<input class="t2" type="text" placeholder="接收电子发票手机号" placeholder-style="font-size:28rpx;color:#BBBBBB" name="mobile" :disabled="inputDisabled" :value="invoice ? invoice.mobile : ''"></input>
								</view>
								<view class="item">
									<text class="t1">邮箱</text>
									<input class="t2" type="text" placeholder="接收电子发票邮箱" placeholder-style="font-size:28rpx;color:#BBBBBB" name="email" :disabled="inputDisabled" :value="invoice ? invoice.email : ''"></input>
								</view>
							</view>
							<button class="btn" form-type="submit" :style="{background:t('color1')}">确定</button>
							<view style="padding-top:30rpx"></view>
						</form>
					</view>
				</view>
			</view>
			
			<view v-if="couponvisible" class="popup__container">
				<view class="popup__overlay" @tap.stop="handleClickMask"></view>
				<view class="popup__modal coupon-modal">
					<view class="popup__title">
						<text class="popup__title-text">请选择{{t('优惠券')}}</text>
						<image :src="pre_url+'/static/img/close.png'" class="popup__close" style="width:36rpx;height:36rpx"
							@tap.stop="handleClickMask" />
					</view>
					<view style="text-align: center;color: red;font-size: 24rpx;" v-if="coupon_not_used_discount ==1">选择使用{{t('优惠券')}}时，{{t('会员')}}折扣不生效</view>
					<view class="popup__content">
						<couponlist :couponlist="allbuydata[bid].couponList" :choosecoupon="true"
							:selectedrids="allbuydata[bid].couponrids" :bid="bid" @chooseCoupon="chooseCoupon">
						</couponlist>
					</view>
				</view>
			</view>
			<!-- 水票 -->
			<view v-if="water_couponvisible" class="popup__container">
				<view class="popup__overlay" @tap.stop="handleClickMask"></view>
				<view class="popup__modal coupon-modal">
					<view class="popup__title flex-bt">
						<!-- <view class="popup__title-text" style="width: 70%;text-align: center;">请选择{{t('电子水票')}}</view> -->
						<view class="popup__title-text" style="margin-left: 10rpx;">请选择{{t('电子水票')}}</view>
					<!-- 	<image :src="pre_url+'/static/img/close.png'" class="popup__close" style="width:36rpx;height:36rpx"
							@tap.stop="handleClickMask" /> -->
							<view class="flex-y-center" :style="{color:t('color1')}" @tap="toBuyWaterCoupon"    :data-url="'/pagesD/deposit/couponlist?bid='+bid">去购买
							<text class="iconfont iconjiantou" style="color:#999;font-weight:normal;margin-left: 15rpx;"></text></view>
					</view>
					<view class="popup__content">
						<view class="couponlist"></view>
						<couponlist :couponlist="allbuydata[bid].water_couponList" :choosecoupon="true"
							:selectedrids="allbuydata[bid].water_couponrids" :bid="bid" @chooseWaterCoupon="chooseWaterCoupon" :choosetype="chooseWaterCoupon">
						</couponlist>
					</view>
				</view>
			</view>
			<!-- 水票end -->
			<view v-if="pstimeDialogShow" class="popup__container">
				<view class="popup__overlay" @tap.stop="hidePstimeDialog"></view>
				<view class="popup__modal">
					<view class="popup__title">
						<text
							class="popup__title-text">请选择{{allbuydata[nowbid].freightList[allbuydata[nowbid].freightkey].pstype==1?'取货':'配送'}}时间</text>
						<image :src="pre_url+'/static/img/close.png'" class="popup__close" style="width:36rpx;height:36rpx"
							@tap.stop="hidePstimeDialog" />
					</view>
					<view class="popup__content">
						<view class="pstime-item"
							v-for="(item, index) in allbuydata[nowbid].freightList[allbuydata[nowbid].freightkey].pstimeArr"
							:key="index" @tap="pstimeRadioChange" :data-index="index">
							<view class="flex1">{{item.title}}</view>
							<view class="radio"
								:style="allbuydata[nowbid].freight_time==item.value ? 'background:'+t('color1')+';border:0' : ''">
								<image class="radio-img" :src="pre_url+'/static/img/checkd.png'" />
							</view>
						</view>
					</view>
				</view>
			</view>
			
			<view v-if="cuxiaovisible" class="popup__container">
				<view class="popup__overlay" @tap.stop="handleClickMask"></view>
				<view class="popup__modal">
					<view class="popup__title">
						<text class="popup__title-text">优惠促销</text>
						<image :src="pre_url+'/static/img/close.png'" class="popup__close" style="width:36rpx;height:36rpx"
							@tap.stop="handleClickMask" />
					</view>
					<view class="popup__content">
						<view class="cuxiao-desc">
							<block v-if="multi_promotion">
								<view class="cuxiao-item" @tap="changecxMulti" data-id="0">
									<view class="type-name"><text style="color:#333">不使用促销</text></view>
									<view class="radio" :style="cxid === 0 ? 'background:'+t('color1')+';border:0' : ''">
										<image class="radio-img" :src="pre_url+'/static/img/checkd.png'" />
									</view>
								</view>
								<view v-for="(item, index) in allbuydata[bid].cuxiaolist" :key="index" class="cuxiao-item"
									@tap="changecxMulti" :data-id="item.id" :data-index="index">
									<view class="type-name">
										<text style="border-radius:4px;border:1px solid #f05423;color: #ff550f;font-size:20rpx;padding:2px 5px">{{item.tip}}</text>
										<text style="color:#333;padding-left:20rpx">{{item.name}}</text>
									</view>
									<view class="radio" :style="cxids.indexOf(item.id) !== -1 ? 'background:'+t('color1')+';border:0' : ''">
										<image class="radio-img" :src="pre_url+'/static/img/checkd.png'" />
									</view>
								</view>
							</block>
							<block v-else>
								<view class="cuxiao-item" @tap="changecx" data-id="0">
									<view class="type-name"><text style="color:#333">不使用促销</text></view>
									<view class="radio" :style="cxid == 0 ? 'background:'+t('color1')+';border:0' : ''">
										<image class="radio-img" :src="pre_url+'/static/img/checkd.png'" />
									</view>
								</view>
								<view v-for="(item, index) in allbuydata[bid].cuxiaolist" :key="index" class="cuxiao-item"
									@tap="changecx" :data-id="item.id" :data-index="index">
									<view class="type-name">
										<text style="border-radius:4px;border:1px solid #f05423;color: #ff550f;font-size:20rpx;padding:2px 5px">{{item.tip}}</text>
										<text style="color:#333;padding-left:20rpx">{{item.name}}</text>
									</view>
									<view class="radio" :style="cxid==item.id ? 'background:'+t('color1')+';border:0' : ''">
										<image class="radio-img" :src="pre_url+'/static/img/checkd.png'" />
									</view>
								</view>
							</block>
						</view>
						<view id="cxproinfo" v-if="cuxiaoinfo.product" style="padding:0 40rpx">
							<view class="product">
								<view class="item flex" style="background:#f5f5f5">
									<view class="img" @tap="goto"
										:data-url="'/pages/shop/product?id=' + cuxiaoinfo.product.id">
										<image :src="cuxiaoinfo.product.pic"></image>
									</view>
									<view class="info flex1">
										<view class="f1">{{cuxiaoinfo.product.name}}</view>
										<view class="f2">规格：{{cuxiaoinfo.guige.name}}</view>
										<view class="f3">
											<text style="font-weight:bold;">￥{{cuxiaoinfo.guige.sell_price}}</text>
											<text style="padding-left:20rpx"> × 1</text>
										</view>
									</view>
								</view>
							</view>
						</view>
						<view style="width:100%; height:120rpx;"></view>
						<view style="width:100%;position:absolute;bottom:0;padding:20rpx 5%;background:#fff">
							<view
								style="width:100%;height:80rpx;line-height:80rpx;border-radius:40rpx;text-align:center;color:#fff;"
								:style="{background:t('color1')}" @tap="chooseCuxiao">确 定</view>
						</view>
					</view>
				</view>
			</view>

			<view v-if="type11visible" class="popup__container">
				<view class="popup__overlay" @tap.stop="handleClickMask"></view>
				<view class="popup__modal">
					<view class="popup__title">
						<text class="popup__title-text">选择物流</text>
						<image :src="pre_url+'/static/img/close.png'" class="popup__close" style="width:36rpx;height:36rpx"
							@tap.stop="handleClickMask" />
					</view>
					<view class="popup__content">
						<view class="cuxiao-desc">
							<view
								v-for="(item, index) in allbuydata[bid].freightList[allbuydata[bid].freightkey].type11pricedata"
								:key="index" @tap="changetype11" :data-index="index" style="padding:0 30rpx 20rpx 40rpx"
								v-if="address.id && address.province==item.province && address.city==item.city && address.district==item.area">
								<view class="cuxiao-item" style="padding:0">
									<view class="type-name"><text
											style="color:#333;font-weight:bold;">{{item.name}}</text></view>
									<view class="radio"
										:style="type11key==index ? 'background:'+t('color1')+';border:0' : ''">
										<image class="radio-img" :src="pre_url+'/static/img/checkd.png'" />
									</view>
								</view>
								<view style="margin-left:20rpx">发货: {{item.send_address}} - {{item.send_tel}}</view>
								<view style="margin-left:20rpx">收货: {{item.receive_address}} - {{item.receive_tel}}
								</view>
							</view>
						</view>
						<view style="width:100%; height:120rpx;"></view>
						<view style="width:100%;position:absolute;bottom:0;padding:20rpx 5%;background:#fff">
							<view
								style="width:100%;height:80rpx;line-height:80rpx;border-radius:40rpx;text-align:center;color:#fff;"
								:style="{background:t('color1')}" @tap="chooseType11">确 定</view>
						</view>
					</view>
				</view>
			</view>
			
			<view v-if="membervisible" class="popup__container">
				<view class="popup__overlay" @tap.stop="handleClickMask"></view>
				<view class="popup__modal" style="height: 1100rpx;">
					<view class="popup__title">
						<text class="popup__title-text">请选择指定会员</text>
						<image :src="pre_url+'/static/img/close.png'" class="popup__close" style="width:36rpx;height:36rpx;"
							@tap.stop="handleClickMask" />
					</view>
					<view class="popup__content">
						<view class="member_search">
							<view style="width:130rpx;color:#333;flex-shrink:0">选择地区</view>
							<uni-data-picker :localdata="items" :border="false" :placeholder="regiondata || '请选择省市区'" @change="regionchange2" class="flex1" style="overflow:hidden"></uni-data-picker>
							<view class="searchMemberButton" @click="memberSearch" style="flex-shrink:0">检索用户</view>
						</view>
						<view class="memberlist">
							<view v-for="(item2,i) in memberList" :key="i" class="memberitem" @tap="checkMember" :data-info="item2">
								<image :src="item2.headimg" @tap.stop="showmemberinfo" :data-mid="item2.id"/>
								<view class="flex-col">
									<view class="t1" @tap.stop="showmemberinfo" :data-mid="item2.id">{{item2.nickname}}</view>
									<view>{{item2.province}} {{item2.city}} {{item2.area}}</view>
								</view>
								<view class="flex1"></view>
								<view class="radio" :style="checkMem.id==item2.id ? 'background:'+t('color1')+';border:0' : ''">
									<image class="radio-img" :src="pre_url+'/static/img/checkd.png'" />
								</view>
							</view>
						</view>
					</view>
				</view>
			</view>

			<view v-if="memberinfovisible" class="popup__container">
				<view class="popup__overlay" @tap.stop="memberinfoClickMask"></view>
				<view class="popup__modal" style="height: 1100rpx;">
					<view class="popup__title">
						<text class="popup__title-text">查看资料</text>
						<image :src="pre_url+'/static/img/close.png'" class="popup__close" style="width:36rpx;height:36rpx;"
							@tap.stop="memberinfoClickMask" />
					</view>
					<view class="popup__content">
						<view class="orderinfo">
							<view class="item" v-for="item in selectmemberinfo" :key="index">
								<text class="t1">{{item[0]}}</text>
								<view class="t2" v-if="item[2]=='upload'"><image :src="item[1]" style="width:400rpx;height:auto" mode="widthFix" @tap="previewImage" :data-url="item[1]"/></view>
								<text class="t2" v-else>{{item[1]}}</text>
							</view>
						</view>
					</view>
				</view>
			</view>
			
			<!-- 眼镜档案 -->
			<view v-if="isshowglass" class="popup__container glass_popup">
				<view class="popup__overlay" @tap.stop="hideglass"></view>
				<view class="popup__modal" style="height: 1100rpx;">
					<view class="popup__title">
						<text class="popup__title-text">视力档案</text>
						<image :src="pre_url+'/static/img/close.png'" class="popup__close" style="width:36rpx;height:36rpx;"
							@tap.stop="hideglass" />
					</view>
					<view class="popup__content">
						<radio-group @change="chooseglass">
						<block v-for="(item,index) in glassrecordlist" :key="index">
						<label>
							<view class="glassitem" :class="grid==item.id?'on':''">
									<view class="fc">
										<view class="radio"><radio :color="t('color1')" :checked="grid==item.id?true:false" :value="''+index" style="transform: scale(0.8);"></radio></view>
										<view class="gcontent">
											<view class="grow gtitle">{{item.name}} {{item.nickname?item.nickname:''}} {{item.check_time?item.check_time:''}} {{item.typetxt}}
												<text v-if="item.double_ipd==0"> {{item.ipd?' PD'+item.ipd:''}}</text>
												<text v-else> PD R{{item.ipd_right}} L{{item.ipd_left}}</text>
											</view>
											<view class="grow">
											R {{item.degress_right}}/{{item.ats_right}}*{{item.ats_zright}}  <text v-if="item.type==3" class="pdl10"> ADD+{{item.add_right?item.add_right:0}}</text>
											</view>
											<view class="grow">
												<text>L {{item.degress_left}}/{{item.ats_left}}*{{item.ats_zleft}} </text>  <text v-if="item.type==3" class="pdl10"> ADD+{{item.add_left?item.add_left:0}}</text>
											</view>
										</view>
										<view class="opt" @tap="goto" :data-url="'/pagesExt/glass/add?id='+item.id">编辑</view>
									</view>
									<view class="gremark" v-if="item.remark">备注：{{item.remark}}</view>
							</view>
						</label>
						</block>
						</radio-group>
						<view class="gr-add"><button class="gr-btn" :style="{background:t('color1')}" @tap="goto" data-url="/pagesExt/glass/add">新增档案</button></view>
					</view>
				</view>
			</view>
			<!-- 眼镜档案end -->
			<!-- 定制参数 -->
			<view v-if="isshowglasscustom" class="popup__container glass_popup">
				<view class="popup__overlay" @tap.stop="hideglasscustom"></view>
				<view class="popup__modal" style="height: 1100rpx;">
					<view class="popup__title">
						<text class="popup__title-text">定制参数</text>
						<image :src="pre_url+'/static/img/close.png'" class="popup__close" style="width:36rpx;height:36rpx;"
							@tap.stop="hideglasscustom" />
					</view>
					<view class="popup__content">
						<view class="glassitem" :class="grid==item.id?'on':''">
								<view class="fc">
									<view class="gcontent">
										<view class="grow flex-row" style="text-align: center;">
											<view style="width: 33%;"> </view>
											<view style="width: 33%;">R</view>
											<view style="width: 33%;">L</view> 
										</view>
										<view class="grow flex-row" style="text-align: center;">
											<view style="width: 33%;">球镜 </view>
											<view style="flex: 1;">{{glasscustom.sph_right}}</view>
											<view style="flex: 1;">{{glasscustom.sph_left}}</view> 
										</view>
										<view class="grow flex-row" style="text-align: center;">
											<view style="width: 33%;">柱镜 </view>
											<view style="flex: 1;">{{glasscustom.cyl_right}}</view>
											<view style="flex: 1;">{{glasscustom.cyl_left}}</view> 
										</view>
										<view class="grow flex-row" style="text-align: center;">
											<view style="width: 33%;">轴位 </view>
											<view style="flex: 1;">{{glasscustom.ax_right}}</view>
											<view style="flex: 1;">{{glasscustom.ax_left}}</view> 
										</view>
										<view class="grow flex-row" style="text-align: center;">
											<view style="width: 33%;">下加光 </view>
											<view style="flex: 1;">{{glasscustom.add_right}}</view>
											<view style="flex: 1;">{{glasscustom.add_left}}</view> 
										</view>
										<view class="grow flex-row" style="text-align: center;">
											<view style="width: 33%;">数量 </view>
											<view style="flex: 1;">{{glasscustom.qty_right}}</view>
											<view style="flex: 1;">{{glasscustom.qty_left}}</view> 
										</view>
									</view>
								</view>
								<view class="gremark" v-if="glasscustom.remark">备注：{{glasscustom.remark}}</view>
						</view>
					</view>
				</view>
			</view>
			<!-- 定制参数end -->
	
			<uni-popup id="dialogbusinessinfo" ref="dialogbusinessinfo" type="dialog">
				<view class="uni-popup-dialog">
					<view class="uni-dialog-title">
						<text class="uni-dialog-title-text">请再次确认下单门店</text>
					</view>
					<view class="uni-dialog-content">
						<view style="width:100%;text-align:left">
							<view style="font-weight:bold">{{allbuydata[bid].business.name}}</view>
							<view style="font-size:24rpx;color:#888;padding:4rpx 0">{{allbuydata[bid].business.address}}</view>
							<dp-map :params="{bgcolor:'#fff',margin_y:0,margin_x:0,padding_x:0,padding_y:0,height:'150',latitude:allbuydata[bid].business.latitude,longitude:allbuydata[bid].business.longitude,address:allbuydata[bid].business.name}"></dp-map> 
						</view>
					</view>
					<view class="uni-dialog-button-group">
						<view class="uni-dialog-button" @click="businessinfoClose">
							<text class="uni-dialog-button-text">取消</text>
						</view>
						<view class="uni-dialog-button uni-border-left" @click="businessinfoOk">
							<text class="uni-dialog-button-text uni-button-color">确定</text>
						</view>
					</view>
				</view>
			</uni-popup>
			
			<uni-popup id="subsidyShowModal" ref="subsidyShowModal" type="dialog">
				<view class="uni-popup-dialog">
					<view class="uni-dialog-title">
						<text class="uni-dialog-title-text">提示</text>
					</view>
					<view class="uni-dialog-content">
						<view style="width:100%;text-align:left">
							<view style="font-size:24rpx;color:#888;padding:4rpx 0">{{subsidy_tips}}</view>
						</view>
					</view>
					<view class="uni-dialog-button-group">
						<view class="uni-dialog-button" @click="subsidyShowModalSuccess(0)">
							<text class="uni-dialog-button-text">取消</text>
						</view>
						<view class="uni-dialog-button uni-border-left" @click="subsidyShowModalSuccess(1)">
							<text class="uni-dialog-button-text uni-button-color">继续提交</text>
						</view>
					</view>
				</view>
			</uni-popup>
      
      <view v-if="storevisible" class="popup__container">
      	<view class="popup__overlay" @tap.stop="closestore"></view>
      	<view class="popup__modal">
      		<view class="popup__title">
      			<text class="popup__title-text">选择门店</text>
      			<image :src="pre_url+'/static/img/close.png'" class="popup__close" style="width:36rpx;height:36rpx"
      				@tap.stop="closestore" />
      		</view>
      		<view class="popup__content invoiceBox">
              <view style="width: 100%;padding: 20rpx 40rpx;display: flex;">
                <view style="width: 100%;">
                  <input placeholder="搜索门店名称" @input="inputStorename" @confirm="searchStore" placeholder-style="height: 72rpx;line-height: 72rpx;" class="storeinput"/>
                </view>
                <view @tap="searchStore" class="storesearch" :style="{background:t('color1')}">
                  搜索
                </view>
              </view>
      				<scroll-view scroll-y class="storeitem" style="height: 600rpx;">
                <block v-for="(item, idx) in storedata" :key="idx">
                  <view class="radio-item" v-if="item.searchkey == idx" style="padding: 20rpx 40rpx;">
                    <view class="f1" @tap.stop="openMendian" :data-bid="storebid"  :data-freightkey="storefreightkey" :data-storekey="idx">
                      <view>
                        <text class="iconfont icondingwei"></text>
                      {{item.name}}
                      </view>
                      <view v-if="item.address" style="text-align:left;font-size:24rpx;color:#aaaaae;display: -webkit-box;-webkit-box-orient: vertical;-webkit-line-clamp:1;overflow: hidden;">
                      {{item.address}}
                      </view>
                    </view>
                    <view @tap.stop="choosestore" :data-bid="storebid" :data-index="idx" style="display: flex;">
                      <text style="color:#f50;">{{item.juli}}</text>
                      <view class="radio" :style="storekey==idx ? 'background:'+t('color1')+';border:0' : ''">
                        <image class="radio-img" :src="pre_url+'/static/img/checkd.png'" />
                      </view>
                    </view>
                  </view>
                </block>
      				</scroll-view>
      				<button class="btn" @tap.stop="closestore" :style="{background:t('color1')}">关闭</button>
      				<view style="padding-top:30rpx"></view>
      		</view>
      	</view>
      </view>
      <view v-if="showxieyi" class="xieyibox">
      	<view class="xieyibox-content">
      		<view style="overflow:scroll;height:100%;">
      			<parse :content="hwset.hwcontent" @navigate="navigate"></parse>
      		</view>
      		<view style="position:absolute;z-index:9999;bottom:10px;left:0;right:0;margin:0 auto;text-align:center; width: 50%;height: 60rpx; line-height: 60rpx; color: #fff; border-radius: 8rpx;" :style="{background:'linear-gradient(90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'}"  @tap="hidexieyi">已阅读并同意</view>
      	</view>
      </view>
	  <view v-if="showproxieyi" class="xieyibox">
	  	<view class="xieyibox-content">
	  		<view style="overflow:scroll;height:100%;">
	  			<parse :content="proxieyi_content" @navigate="navigate"></parse>
	  		</view>
	  		<view style="position:absolute;z-index:9999;bottom:10px;left:0;right:0;margin:0 auto;text-align:center; width: 50%;height: 60rpx; line-height: 60rpx; color: #fff; border-radius: 8rpx;" :style="{background:'linear-gradient(90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'}"  @tap="hideproxieyi">已阅读并同意</view>
	  	</view>
	  </view>
		<!-- 代下级下单 -->
		<view v-if="memberOtherShow" class="popup__container">
			<view class="popup__overlay" @tap.stop="handlMememberOther"></view>
			<view class="popup__modal" style="height: 1100rpx;">
				<view class="popup__title">
					<text class="popup__title-text">请选择会员</text>
					<image :src="pre_url+'/static/img/close.png'" class="popup__close" style="width:36rpx;height:36rpx;"
						@tap.stop="handlMememberOther" />
				</view>
				<view class="popup__content">
					<view class="member_search" style="justify-content: space-around">
						<input type="text" class="input" placeholder="请输入会员ID进行搜索" 
							placeholder-style="color:#626262;font-size:28rpx;" style="flex:0.9" v-model="tmid"/>
						<view class="searchMemberButton" @click="showTeamMemberList" :style="'background:'+t('color1')" style="flex-shrink:0">检索</view>
					</view>
					<view class="memberlist">
						<block v-if="teamMemberList.length > 0">
							<view v-for="(item2,i) in teamMemberList" :key="i" class="memberitem" @tap="checkTeamMember"
								:data-info="item2">
								<image :src="item2.headimg"  :data-mid="item2.id" />
								<view class="flex-col">
									<view class="t1" >{{item2.nickname}}
									</view>
									<view>ID：{{item2.id}}</view>
								</view>
								<view class="flex1"></view>
								<view class="radio"
									:style="teamMember.id==item2.id ? 'background:'+t('color1')+';border:0' : ''">
									<image class="radio-img" :src="pre_url+'/static/img/checkd.png'" />
								</view>
							</view>
						</block >
						<block v-else>
							<nodata text="没有查找到相关会员" ></nodata>
						</block>
					</view>
				</view>
			</view>
		</view>
		<!-- 代下级下单 end-->

    <view v-if="buy_message_prompt_status" class="xieyibox">
      <view class="xieyibox-content" style="padding: 15px 15px 50px 15px;margin-top: 42%;height: 38%;width: 85%;white-space: pre-wrap;border-radius:5px">
        <view style="overflow:scroll;height:100%;">
          <parse :content="buy_message_prompt_content"></parse>
        </view>
        <view style="position:absolute;z-index:9999;bottom:10px;left:0;right:0;margin:0 auto;text-align:center; width: 50%;height: 60rpx; line-height: 60rpx; color: #fff; border-radius: 8rpx;" :style="{background:'linear-gradient(90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'}"  @tap="hidetkts">知道了</view>
      </view>
    </view>
			<!-- 押金不足弹窗 -->
			<uni-popup ref="refPopup">
				<view class="popup-content flex flex-col" :style="{backgroundImage:`linear-gradient(rgba(${t('color1rgb')},0.2),#fff)`}">
					<view class="deposit-popup-img">
						<image :src="pre_url+'/static/img/deposit/buy.png'"></image>
					</view>
					<view class="deposit-title-view">抱歉 {{t('押金')}}不足！</view>
					<view class="deposit-tips-view">请勾选需要缴纳的{{t('押金')}}后提交订单</view>
					<view class="deposit-popup-but" :style="{background:t('color1')}" @click="depositClose">确认</view>
				</view>
			</uni-popup>
      <view v-if="shopbuy_sign_tk" class="xieyibox">
        <view class="xieyibox-content">
          <view style="overflow:scroll;height:100%;">
            <parse :content="sign_contract_content" @navigate="navigate"></parse>
          </view>
          <view class="xieyibut-view flex-y-center">
            <view class="but-class" style="background:#A9A9A9"  @tap="closehetong">不同意并退出</view>
            <view class="but-class" :style="{background:'linear-gradient(90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'}" @tap="goto" data-url="/pagesD/order/signature">同意去签字</view>
          </view>
        </view>
      </view>
		</block>
		<loading v-if="loading"></loading>
		<dp-tabbar :opt="opt"></dp-tabbar>
	<popmsg ref="popmsg"></popmsg>
	<wxxieyi></wxxieyi>
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
        scoredkChecked: 0,
				pre_url:app.globalData.pre_url,
				test:'test',
				havetongcheng: 0,
				address: [],
				memberList: [],
				checkMem:{},
				usescore: 0,
				scoredk_money: 0,
				totalprice: '0.00',
				couponvisible: false,
				cuxiaovisible: false,
				membervisible: false,
				memberinfovisible:false,
				business_payconfirm:false,
				businessinfoConfirm:false,
				topayparams:{},
				selectmemberinfo:{},
				bid: 0,
				nowbid: 0,
				needaddress: 1,
				linkman: '',
				tel: '',
				userinfo: {},
				pstimeDialogShow: false,
				pstimeIndex: -1,
				manjian_money: 0,
				cxid: 0,
				cxids: [],
				latitude: "",
				longitude: "",
				allbuydata: {},
				allbuydatawww: {},
				alltotalprice: "",
				cuxiaoinfo: false,
				cuxiaoList: {},
				type11visible: false,
				type11key: -1,
				regiondata: '',
				items: [],
				editorFormdata:[],
				buy_selectmember:false,
				multi_promotion:0,
				storeshowall:false,
				order_change_price:false,
				invoiceShow:false,
				invoice:{},
				invoice_type:[],
				invoice_type_select:1,
				name_type_select:1,
				name_type_personal_disabled:false,
				inputDisabled:false,
				submitDisabled:false,
				pstype3needAddress:false,
				isshowglass:false,
				glassrecordlist:[],
				grid:0,
				curindex:-1,
				curindex2:-1,
				showprice_dollar:false,
				usdrate:0,
				usdalltotalprice:0,
				hasglassproduct:0,
				business_selfscore:0,
				scoredkdataArr:{},
                
        moneydec:false,
        shop_num:0,//bid不同类型
        shop_bid:0,//最后一个bid
        moneydecArr:{},//抵扣金抵扣比例或抵扣金额
        moneydecArrType:{},//抵扣金抵扣类型 0：比例 1：金额
        worknum_status:false,
        worknum:'',
        worknumtip:'请输入您的工号',
        mendianShowType:0,// 商城购买页门店展示方式，1单条展示，点击弹窗展示列表可搜索
        storevisible:false,
        storedata:[],
        storefreightkey:0,
        storekey:0,
        storebid:0,
        storename:'',
				showweight:false,
				mendian_id:0,
				custom:[],
				discount_code_zc:'',
				freightkey_bid:'',
				freightkey_index:'',
				coupon_not_used_discount:0,
				order_discount_rand:{},
        
        ishand:0,
        hwset:'',
        showxieyi:false,
        isagree:false,
				fenqiData:[],
				fenqi_type:0,
				mendian_upgrade:false,
				mendian_change:true,
				mendian_no_select:0,
				memberOtherShow:false,//选择会员
				teamMemberList:[],//选择团队会员
				show_other_order:false,//控制显示选择会员
				teamMember:{},//选择的会员信息
				tmid:'',
				contact_require:0,
				ismultiselect:false,
				show_product_xieyi:0,
				product_xieyi:[],
				showproxieyi:0,
				proxieyi_content:'',
				isagree_pro:false,
				show_service_fee:false,
				allservice_fee:0, //总服务费
				devicedata:'',//商品柜商品组合
				
				//来源直播间
				roomid:0,
				usetongzheng:0,
        
        mustuseaddress:false,//必须使用地址
        needusercard:false,
        usercard:'',
        usercardtip:'请输入您的身份证号',
        goldsilverlist:'',
        goldsilverdec:0,
        goldsilvertype:0,
        goldsilverindex:0,
        xdjf: 0,
        full_piece_package:false,
				name:'',
				phone:'',
				pcemail:'',
				is_pingce:false,
				age:'',
				gender:0,
				school:'',
				major:'',
				education:'',
				enrol:'',
				class_name:'',
				faculties_name:'',//院系
				sexlist:['男','女'],
				edulist:['高中','中专','专升本','本科','双学位','硕士','博士','博士后','结业','肄业','进修生','访问学者','其他'],
        
        usededamount:false,//抵扣金
        
        //产品积分抵扣
        useshopscore: 0,
        shopscoredk_money: 0,
        is_coupon_auto_multi:0,//兑换券 自动多选
        
        giveorder:0,//是否开启赠送礼物功能 0:未开启 1：开启
        giveorder_validtime:0,//赠送有效时间
        usegiveorder:false,//是否使用赠送礼物功能
        giveordertitle:'',
        giveorderpic:'',
        shop_buy_ad:false,
        createorder_toppic:'',
        createorder_toppic_tourl:'',
				subsidy_tips:'', //国补提示文字
				subsidy_verify:0, //是否弹出国补提示框
				hasglasscustomproduct:0,
				gcid:0,
				glasscustom:[],
				isshowglasscustom:false,
				poshopid:0,//排队系统里的店铺ID参数，用于页面进入来源区别
				shop_buy_score_default_deduction:false,
				default_deduction_checked:0,
				allfreezemoney_price:0,//冻结资金支付
				huomacode:'',//活码code
				regionlist:[],//省市区列表
				dandu_price:false,
        buy_message_prompt_status:false,//下单弹框
        buy_message_prompt_content:'',//下单弹框内容
				water_couponvisible:false,//水票弹窗
				isfloor:0,//添加地址是否显示楼层

        //下单前签订合同
        shopbuy_sign_tk:false,
        shopbuy_sign:false,
        sign_contract_content:'',
        sign_contract_name:'',
        signatureurlShop:'',
            scoreCheckboxKey: ""

			};
		},

		onLoad: function(opt) {
			this.opt = app.getopts(opt);
			var locationCache =  app.getLocationCache();
			if(locationCache.mendian_id){
				this.mendian_id = locationCache.mendian_id
			}
			if(this.opt.devicedata){
				this.devicedata=this.opt.devicedata;
			}
			this.roomid = this.opt.roomid || 0;
      this.poshopid = this.opt.poshopid || 0;
			  if(this.opt.huomacode){
				this.huomacode = this.opt.huomacode;
			  }
			this.getdata();
		},
		onShow:function(e){
            this.scoreCheckboxKey = (new Date()).valueOf()
			if(this.hasglassproduct==1){
				this.getglassrecord()
			}
			if(this.mendian_upgrade){
					var locationCache =  app.getLocationCache();
					if(locationCache.mendian_id){
						this.mendian_id = locationCache.mendian_id
					}
					this.getdata();
			}else{
				this.getdata();
			}
			
		},
		onPullDownRefresh: function() {
			this.getdata();
		},
		mounted() {
			var that = this;
			app.get('ApiIndex/getCustom',{}, function (customs) {
				var url = app.globalData.pre_url+'/static/area.json';
				if(customs.data.includes('plug_zhiming')) {
					url = app.globalData.pre_url+'/static/area_gaoxin.json';
				}
				uni.request({
					url: app.globalData.pre_url+'/static/area.json',
					data: {},
					method: 'GET',
					header: { 'content-type': 'application/json' },
					success: function(res2) {
						that.regionlist = res2.data
						console.log(that.regionlist);
					}
				});
			});
		},
		methods: {
			getdata: function() {
				var that = this;
				that.loading = true;
				var jldata = that.opt.jldata?JSON.parse( that.opt.jldata):[];
				app.post('ApiShop/buy', {
					prodata: that.opt.prodata,
					mendian_id:that.mendian_id,
					devicedata:that.devicedata,
					jldata:jldata,
					huomacode:that.huomacode
				}, function(res) {
					that.loading = false;
					if (res.status == 0) {
						if (res.msg) {
							app.alert(res.msg, function() {
								if (res.url) {
									if(res.relaunch && res.relaunch==1){
										console.log('relaunch');
										app.goto(res.url,'reLaunch');
									
									}else{
										app.goto(res.url);
									}
									
								} else {
									app.goback();
								}
							});
						} else if (res.url) {
							app.goto(res.url);
						} else {
							app.alert('您没有权限购买该商品');
						}
						return;
					}
					that.fenqiData = res.fenqi_data ? res.fenqi_data:[];
					that.havetongcheng = res.havetongcheng;
					that.address = res.address;
					if(that.address && that.address.latitude){
						that.latitude = that.address.latitude;
						that.longitude = that.address.longitude;
					}
					that.is_pingce = res.is_pingce;
					that.linkman = res.linkman;
					that.tel = res.tel;
					that.userinfo = res.userinfo;
					that.buy_selectmember = res.buy_selectmember;
					that.order_change_price = res.order_change_price;
					that.pstype3needAddress = res.pstype3needAddress;
					//优惠不同享
					that.coupon_not_used_discount = res.coupon_not_used_discount;
					
					that.contact_require = res.contact_require;

					that.shopbuy_sign = res.shopbuy_sign || false;
					that.sign_contract_content = res.sign_contract_content || '';
					that.sign_contract_name = res.sign_contract_name || '';

					if(that.buy_selectmember){
						uni.request({
							url: app.globalData.pre_url+'/static/area2.json',
							data: {},
							method: 'GET',
							header: { 'content-type': 'application/json' },
							success: function(res2) {
								that.items = res2.data
							}
						})
					}
					that.allbuydata = res.allbuydata;
					
					if(that.freightkey_bid !== '') that.allbuydata[that.freightkey_bid].freightkey = that.freightkey_index;

					that.bid = res.bid;
					var freightList = that.allbuydata[that.bid].freightList
					var freightkey = that.allbuydata[that.bid].freightkey
					
					if(freightList[freightkey].show_add_floor || freightList[freightkey].up_floor_fee){
						that.isfloor = 1;			
					}
					that.business_payconfirm = res.business_payconfirm || false;
					that.allbuydatawww = JSON.parse(JSON.stringify(res.allbuydata));
					that.needLocation = res.needLocation;
					that.scorebdkyf = res.scorebdkyf;
					that.multi_promotion = res.multi_promotion;
					that.showprice_dollar = res.price_dollar
					that.usdrate = res.usdrate
					that.hasglassproduct = res.hasglassproduct
					if(res.hasglasscustomproduct){
						that.hasglasscustomproduct = res.hasglasscustomproduct
					}
					that.business_selfscore = res.business_selfscore || 0;
					that.scoredkdataArr = res.scoredkdataArr || {};
					that.order_discount_rand = res.order_discount_rand || {};
					that.show_other_order = res.show_other_order || false;
					that.full_piece_package = res.full_piece_package || false;
					that.shop_buy_score_default_deduction = res.shop_buy_score_default_deduction || false;
          if(that.shop_buy_score_default_deduction && res.shop_buy_score_default_deduction_status == 1){
            //积分抵扣默认选中
            that.default_deduction_checked = 1;
          }
					if(res.moneydec){
							that.moneydec = res.moneydec;
							var shop_num = 0;
							var shop_bid = 0;
							//获取对象数量
							for (var key in res.allbuydata){
									shop_num++
									shop_bid = key;
							}
							that.shop_num  = shop_num;
							that.shop_bid  = shop_bid;
					}
          if(res.worknum_status){
          		that.worknum_status = res.worknum_status;
          }
          if(res.worknumtip){
            that.worknumtip = res.worknumtip;
          }
          if(res.mendianShowType){
						  //商城购买页门店展示方式，1单条展示，点击弹窗展示列表可搜索
          		that.mendianShowType = res.mendianShowType;
          }
          if(res.ishand){
          		that.ishand = res.ishand;//手工商品
          }
          if(res.hwset){
          		that.hwset = res.hwset;//手工商品
          }
					that.custom = res.custom;
					that.ismultiselect = res.ismultiselect
					that.show_product_xieyi = res.show_product_xieyi;
					that.product_xieyi = res.product_xieyi;
          if(res.mustuseaddress){
            that.mustuseaddress = res.mustuseaddress
            that.needaddress    = 1;
          }
          if(res.needusercard){
          	that.needusercard = res.needusercard;
            if(res.usercard){
              that.usercard   = res.usercard;
            }
            if(res.usercardtip){
              that.usercardtip = res.usercardtip;
            }
          }
          if(res.goldsilverlist){
            that.goldsilverlist  = res.goldsilverlist;
            that.goldsilverindex = 1;
            that.goldsilvertype  = res.goldsilverlist[1]['type'];
          }
					if(res.usededamount){
						that.usededamount = res.usededamount;
					}
					if(res.is_coupon_auto_multi){
						that.is_coupon_auto_multi = res.is_coupon_auto_multi;
					}
          if(res.giveorder){//赠送礼物
            that.giveorder = res.giveorder;
            that.giveorder_validtime = res.giveorder_validtime;
          }
					if(res.subsidy_verify && res.subsidy_tips){
						that.subsidy_verify = res.subsidy_verify;
						that.subsidy_tips = res.subsidy_tips;
					}
					that.dandu_price = res.dandu_price;
					var cxid = res.cxid || 0;
					if(cxid){
						var cxindex = res.cxindex || 0;
						that.changecxdefault(cxid,cxindex)
					}
					

          //积分抵扣默认选中
          that.defaultscoredk();

					//自动选择抵扣券
					that.autoSelectCoupon();
					that.calculatePrice();
					that.loaded();
					
					// var allbuydata = that.allbuydata;
					// for (var i in allbuydata) {
					// 	allbuydata[i].tempInvoice = uni.getStorageSync('temp_invoice_' + allbuydata[i].bid);
					// }
					// that.allbuydata = allbuydata;
					that.mendian_upgrade = res.mendian_upgrade
					that.mendian_change = res.mendian_change
					that.mendian_no_select = res.mendian_no_select
					that.shop_buy_ad = res.shop_buy_ad || false;
					that.createorder_toppic = res.createorder_toppic || '';
					that.createorder_toppic_tourl = res.createorder_toppic_tourl || '';

          if(!app.getCache(that.opt.prodata+app.globalData.mid+'tk')){
            that.buy_message_prompt_status = res.buy_message_prompt_status;
            if( that.buy_message_prompt_status){
              that.buy_message_prompt_content = res.buy_message_prompt_content || '';
              app.setCache(that.opt.prodata+app.globalData.mid+'tk',1,10);
            }
          }

					var mendian_sort = res.mendian_sort || false;
					if (res.needLocation == 1) {
						app.getLocation(function(res) {
							var latitude = res.latitude;
							var longitude = res.longitude;
							that.latitude = latitude;
							that.longitude = longitude;
							var allbuydata = that.allbuydata;
							for (var i in allbuydata) {
								var freightList = allbuydata[i].freightList;
								for (var j in freightList) {
									if (freightList[j].pstype == 1 || freightList[j].pstype == 5) {
										var storedata = freightList[j].storedata;
										if (storedata) {
											for (var x in storedata) {
												if (latitude && longitude && storedata[x].latitude && storedata[x].longitude) {
													var juli = that.getDistance(latitude, longitude,storedata[x].latitude, storedata[x].longitude);
													storedata[x].juli = juli;
												}
											}
											if(mendian_sort == false){
												storedata.sort(function(a, b) {
													return a["juli"] - b["juli"];
												});
											}
											for (var x in storedata) {
												if (storedata[x].juli) {
													storedata[x].juli = storedata[x].juli + '千米';
												}
											}
											allbuydata[i].freightList[j].storedata = storedata;
										}
									}
								}
							}
							that.allbuydata = allbuydata;
						});
					}
				});

			},
			// 分期支付
			checkFenqi(e){
				this.fenqi_type = e.detail.value.length;
			},
      //积分抵扣默认勾选
      defaultscoredk: function() {
        this.usescore = this.default_deduction_checked;
        this.scoredkChecked = this.default_deduction_checked;
        this.sdjf = 1;
        this.calculatePrice();
      },
			//积分抵扣
			scoredk: function(e) {

				var usescore = e.detail.value[0];
				if (!usescore) usescore = 0;
				this.usescore = usescore;
				this.scoredkChecked = usescore;
				this.sdjf = 1;
				this.calculatePrice();
			},
			scoredk2:function(e){
				var usescore = e.detail.value[0];
				if (!usescore) usescore = 0;
				var thisbid = e.currentTarget.dataset.bid;
				this.scoredkdataArr[thisbid].usescore = usescore;
				//this.test = Math.random();
				this.calculatePrice();
			},
			//通证抵扣
			tongzhengdk: function(e) {
				console.log('选择通证抵扣');
				var usetongzheng = e.detail.value[0];
				if (!usetongzheng) usetongzheng = 0;
				this.usetongzheng = usetongzheng;
				this.calculatePrice();
			},
      goldsilverdk: function(e) {
      	console.log(e)
        var that = this;
        var userinfo = that.userinfo;
        that.goldsilverindex = e.currentTarget.dataset.index;
        that.goldsilvertype  = e.currentTarget.dataset.type;
      	this.calculatePrice();
      },
			inputLinkman: function(e) {
				this.linkman = e.detail.value;
			},
			inputTel: function(e) {
				this.tel = e.detail.value;
			},
			inputWorknum:function(e) {
				this.worknum = e.detail.value;
			},
			inputEmail:function(e){
				this.pcemail = e.detail.value;
			},
			inputSchool:function(e){
				this.school = e.detail.value;
			},
			inputMajor:function(e){
				this.major = e.detail.value;
			},
			inputEduction:function(e){
				this.education = e.detail.value;
			},
			
			inputClassName:function(e){
				this.class_name = e.detail.value;
			},
			inputFaculties:function(e){
				this.faculties_name = e.detail.value;
			},

			inputfield: function(e) {
				var bid = e.currentTarget.dataset.bid;
				var field = e.currentTarget.dataset.field;
				allbuydata2[bid][field] = e.detail.value;
				this.allbuydata2 = allbuydata2;
			},
			//选择收货地址
			chooseAddress: function() {
				app.goto('/pagesB/address/address?fromPage=buy&type=' + (this.havetongcheng == 1 ? '1' : '0'));
			},
			inputPrice: function(e) {
				var that = this;
				var index = e.currentTarget.dataset.index;
				var index2 = e.currentTarget.dataset.index2;
				var allbuydata = that.allbuydata;
				var allbuydatawww = that.allbuydatawww;
				var oldprice = allbuydatawww[index]['prodata'][index2].guige.sell_price;
				if(e.detail.value == '' || parseFloat(e.detail.value) < parseFloat(oldprice)) {
					that.submitDisabled = true;
					app.error('不能小于原价:'+oldprice);
					return;
				}
				that.submitDisabled = false;
				allbuydata[index]['prodata'][index2].guige.sell_price = e.detail.value;
				allbuydata[index]['product_price'] = (e.detail.value * allbuydata[index]['prodata'][index2].num).toFixed(2);
				// allbuydata[index].prodatastr = allbuydata[index].prodatastr
				that.allbuydata = allbuydata;
				that.calculatePrice();
			},
			//计算价格
			calculatePrice: function() {
				var that = this;
				var address = that.address;
				var allbuydata = that.allbuydata;
				var alltotalprice = 0;
				var allfreight_price = 0;
				var allupfloorfee = 0;//上楼费
				var needaddress = 0;
				var worknum_status = false;
				var allservice_fee = 0;
				var allfreezemoney_price = 0;//冻结资金支付
				if(that.moneydec){
				  var money_dec_type = 0;//余额抵扣 0：系统设置最大抵扣 1：商品单独设置最大抵扣金额
				  var userinfo = that.userinfo;
				  var usermoney= userinfo && userinfo.money?parseFloat(userinfo.money):0;
				}
				for (var k in allbuydata) {
					var product_price = parseFloat(allbuydata[k].product_price);
					var leveldk_money = parseFloat(allbuydata[k].leveldk_money); //会员折扣
					var manjian_money = parseFloat(allbuydata[k].manjian_money); //满减活动
					var coupon_money = parseFloat(allbuydata[k].coupon_money); //-优惠券抵扣 
					var water_coupon_money = allbuydata[k].water_coupon_money?parseFloat(allbuydata[k].water_coupon_money):0; //水票抵扣 
					var cuxiao_money = parseFloat(allbuydata[k].cuxiao_money); //+促销活动 
					var invoice_money = parseFloat(allbuydata[k].invoice_money); //+发票
					var service_fee_price = parseFloat(allbuydata[k].service_fee_price);
					var scoredk_money = 0;
					var tongzhengdk_money = 0;
					var freezemoney_price = parseFloat(allbuydata[k].product_freezemoney_price);//冻结资金支付
					var is_dandu = allbuydata[k].is_dandu || 0;
					var dandu_money = parseFloat(allbuydata[k].dandu_money);

					//算运费
					if(!that.usegiveorder){
						var freightdata = allbuydata[k].freightList[allbuydata[k].freightkey];
						var freight_price = freightdata['freight_price'];
						if (freightdata.pstype != 1 && freightdata.pstype != 3 && freightdata.pstype != 4) {
						  needaddress = 1;
						}
						if(freightdata.pstype == 1 && freightdata.select_address_status == 1){
						  needaddress = 1;
						}
					}else{
						//赠送好友，不填写配送费方式和不计算配送费
						var freightdata   = [];
						var freight_price = 0;
					}
					//上楼费
					var up_floor_fee = 0;
					if(!app.isNull(freightdata.up_floor_fee) && freightdata.up_floor_fee > 0){
						up_floor_fee = parseFloat(freightdata.up_floor_fee);
					}
					
					
					var weight_price=0;
					var product_price_discount_code=0;
					//优惠不同享
					allbuydata[k].leveldk_show =1;
				
					if(that.coupon_not_used_discount ==1 || allbuydata[k].not_used_discount==1){
						if(coupon_money > 0){//选择优惠券 不计算折扣
							leveldk_money = 0;
							allbuydata[k].leveldk_show =0;
						}else{
							leveldk_money =parseFloat(allbuydata[k].leveldk_money);
							allbuydata[k].leveldk_show =1;
						}
					}
					
					for(var j in allbuydata[k].prodata){
							if(allbuydata[k].prodata[j].product.weighttype>0 && allbuydata[k].prodata[j].product.weightlist.length>0){
								var weightkey = allbuydata[k].prodata[j].product.weightkey;
								//console.log(weightkey);
								weight_price += allbuydata[k].prodata[j].product.weightlist[weightkey].weight_price		
							}
							if(allbuydata[k].discount_code_zc_status){
								if(parseFloat(allbuydata[k].prodata[j].product.price_discount_code_zc) >0)
									product_price_discount_code += parseFloat(allbuydata[k].prodata[j].product.price_discount_code_zc)*allbuydata[k].prodata[j].num;
								else
									product_price_discount_code += parseFloat(allbuydata[k].prodata[j].guige.sell_price)*allbuydata[k].prodata[j].num
							}
					}
					if(product_price_discount_code > 0) product_price = product_price_discount_code;
					
					allbuydata[k].weight_price = weight_price;
					
					  if(freightdata.worknum_status){
						worknum_status = true;
					  }
					if(that.pstype3needAddress && (freightdata.pstype == 3 || freightdata.pstype == 4 || freightdata.pstype == 5)) {
						needaddress = 1;
					}
					if (allbuydata[k].coupontype == 4) {
						freight_price = 0;
					}
					if(is_dandu){
						var totalprice = dandu_money;
					}else{
						var totalprice = product_price - leveldk_money - manjian_money - coupon_money + cuxiao_money;
					}
				
					if(water_coupon_money && water_coupon_money > 0){
						totalprice = totalprice - water_coupon_money;
					}	
						
					if (totalprice < 0) totalprice = 0; //优惠券不抵扣运费
					
					totalprice = totalprice + freight_price + weight_price + up_floor_fee;
					
					allbuydata[k].freight_price = freight_price.toFixed(2);
					if(allbuydata[k].business.invoice && allbuydata[k].business.invoice_rate > 0 && allbuydata[k].tempInvoice){
						var invoice_money = totalprice * parseFloat(allbuydata[k].business.invoice_rate) / 100;
						allbuydata[k].invoice_money = invoice_money.toFixed(2);
						totalprice = totalprice + invoice_money;
					}
					if(that.business_selfscore == 1 && that.scoredkdataArr[k].usescore == 1){
						//business_selfscore 多商户积分
						var scoredkdata = that.scoredkdataArr[k];
						var oldtotalprice = totalprice;
						scoredk_money = parseFloat(scoredkdata.scoredk_money);
						if (scoredkdata.scorebdkyf == '1' && scoredk_money > 0 && totalprice < freight_price) {
							totalprice = freight_price;
							scoredk_money = totalprice - freight_price;
						}
						var scoredkmaxpercent = parseFloat(scoredkdata.scoredkmaxpercent); //最大抵扣比例
						var scoremaxtype = parseInt(scoredkdata.scoremaxtype);
						var scoredkmaxmoney = parseFloat(scoredkdata.scoredkmaxmoney);
						if (scoremaxtype == 0 && scoredk_money > 0 && scoredkmaxpercent > 0 && scoredkmaxpercent <= 100 &&
							scoredk_money > oldtotalprice * scoredkmaxpercent * 0.01) {
							scoredk_money = oldtotalprice * scoredkmaxpercent * 0.01;
							totalprice = oldtotalprice - scoredk_money;
						} else if (scoremaxtype == 1 && scoredk_money > scoredkmaxmoney) {
							scoredk_money = scoredkmaxmoney;
							totalprice = oldtotalprice - scoredk_money;
						}
					}
					totalprice = totalprice - scoredk_money;
          
          if(that.moneydec){
            var moneydecArrType= that.moneydecArrType;//抵扣类型
            if(moneydecArrType && moneydecArrType[k] && moneydecArrType[k] == 1){
              var moneydecType = 1;
              money_dec_type = 1;
            }else{
              var moneydecType = 0;
            }
            //如果商家数量大于1 或者 商家商品存在单独设置，则在这里处理
            if(that.shop_num>1 || (that.shop_num ==1 && money_dec_type ==1)){
              var moneydecArr = that.moneydecArr;//抵扣数据
              if(moneydecArr && moneydecArr[k] && moneydecArr[k]>0){
                var decrate  = 0;//抵扣比例 
                var decmoney = 0;//抵扣金额
                if(moneydecType == 0){//抵扣比例
                  decrate = moneydecArr[k];
                }else{//抵扣金额
                  decmoney= moneydecArr[k];
                }
                if(usermoney>0 && (decrate>0 || decmoney>0)){
                  var dec_money = totalprice*decrate/100+decmoney;
                  dec_money = Math.round(dec_money*100)/100;
                  if(dec_money>=usermoney){
                      dec_money = usermoney;
                      usermoney = 0;
                  }else{
                    usermoney -= dec_money;
                  }
                  totalprice -= dec_money;
                }
              }
            }
          }
					//计算押金金额
					if(allbuydata[k].deposit_list_key &&  allbuydata[k].deposit_list_key.length > 0){
						var deposit_key =  allbuydata[k].deposit_list_key;//选中押金的key的集合
						var deposit_list =  allbuydata[k].deposit_list;//押金列表
						var deposit_price = 0;
					
						for(var dek = 0;dek < deposit_key.length;dek++){
							//deposit_key[dek] 指的是选中的key值，例如 0
							var deposit = deposit_list[deposit_key[dek]];
							deposit_price = parseFloat (parseFloat(deposit_price) + parseFloat(deposit.totalmoney) ).toFixed(2);
						}
						deposit_price = parseFloat(deposit_price);
						totalprice =parseFloat(parseFloat(totalprice) + parseFloat(deposit_price) ).toFixed(2); 
					}
					if (totalprice < 0) totalprice = 0;
					allbuydata[k].totalprice =parseFloat( totalprice).toFixed(2);
					alltotalprice = parseFloat(alltotalprice)+ parseFloat(totalprice);
					allfreight_price += freight_price;

					allservice_fee+=service_fee_price;
					allfreezemoney_price += freezemoney_price;
				}
				
				that.needaddress    = that.mustuseaddress?1:needaddress;
				that.worknum_status = worknum_status;
				if (that.business_selfscore == 0 && that.usescore) {
					var scoredk_money = parseFloat(that.userinfo.scoredk_money); //-积分抵扣
				} else {
					var scoredk_money = 0;
				}
        
				var oldalltotalprice = alltotalprice;
				alltotalprice = alltotalprice - scoredk_money;
				if (alltotalprice < 0) alltotalprice = 0;

				if(that.usetongzheng){
					var tongzhengdk_money = parseFloat(that.userinfo.tongzhengdk_money); //-通证抵扣
					var tongzhengdkmaxpercent = parseFloat(that.userinfo.tongzhengdkmaxpercent); //最大抵扣比例
					var tongzhengmaxtype = parseInt(that.userinfo.tongzhengmaxtype);
					var tongzhengdkmaxmoney = parseFloat(that.userinfo.tongzhengdkmaxmoney);

					if (tongzhengmaxtype == 0 && tongzhengdk_money > 0 && tongzhengdkmaxpercent > 0 && tongzhengdkmaxpercent < 100 &&
						tongzhengdk_money > alltotalprice * tongzhengdkmaxpercent * 0.01) {
						tongzhengdk_money = alltotalprice * tongzhengdkmaxpercent * 0.01;
					} else if (tongzhengmaxtype == 1 && tongzhengdk_money > tongzhengdkmaxmoney) {
						tongzhengdk_money = tongzhengdkmaxmoney;
					}

					alltotalprice = alltotalprice - tongzhengdk_money;
					//tongzhengdk_money = parseFloat(that.userinfo.tongzhengdk_money);
				}

				if (that.scorebdkyf == '1' && scoredk_money > 0 && alltotalprice < allfreight_price) {
					//积分不抵扣运费
					alltotalprice = allfreight_price;
					scoredk_money = oldalltotalprice - allfreight_price;
				}

				var scoredkmaxpercent = parseFloat(that.userinfo.scoredkmaxpercent); //最大抵扣比例
				var scoremaxtype = parseInt(that.userinfo.scoremaxtype);//0按系统，1独立设置
				var scoredkmaxmoney = parseFloat(that.userinfo.scoredkmaxmoney);

				if (scoremaxtype == 0 && scoredk_money > 0 && scoredkmaxpercent > 0 && scoredkmaxpercent <= 100 &&
					scoredk_money > oldalltotalprice * scoredkmaxpercent * 0.01) {
					if (that.scorebdkyf == '1'){
						//积分不抵扣运费
						scoredk_money = (oldalltotalprice-allfreight_price) * scoredkmaxpercent * 0.01;
					}else{
						scoredk_money = oldalltotalprice * scoredkmaxpercent * 0.01;
					}

					alltotalprice = oldalltotalprice - scoredk_money;
				} else if (scoremaxtype == 1 && scoredk_money > scoredkmaxmoney) {
					scoredk_money = scoredkmaxmoney;
					alltotalprice = oldalltotalprice - scoredk_money;
				}
        if(that.moneydec){
          //如果仅一个商家，且商品无单独设置，则走这里
          if(that.shop_num==1 && money_dec_type ==0){
            var moneydecArr = that.moneydecArr;
            for (var k in allbuydata) {
              if(moneydecArr && moneydecArr[k] && moneydecArr[k]>0){
                var decrate  = moneydecArr[k];
                if(usermoney>0 && decrate>0){
                  var dec_money = alltotalprice*decrate/100;
                  dec_money = Math.round(dec_money*100)/100;
                  if(dec_money>= usermoney){
                    dec_money = usermoney;
                    usermoney = 0;
                  }else{
                    usermoney -= dec_money;
                  }
                  alltotalprice -= dec_money;
                }
              }
            }
          }
        }

        //如果使用抵扣金
        if(that.usededamount && that.userinfo['dedamount_dkmoney'] && that.userinfo['dedamount_dkmoney'] > 0 && alltotalprice>0){
          alltotalprice -= that.userinfo['dedamount_dkmoney'];
        }

        if(that.goldsilverlist){
          var goldsilverdec = that.goldsilverlist[that.goldsilverindex]['value'];
          if(goldsilverdec>=alltotalprice){
            goldsilverdec = alltotalprice;
          }
          alltotalprice -= goldsilverdec;
          that.goldsilverdec = goldsilverdec;
        }
        
        //产品积分抵扣
        if (that.useshopscore && alltotalprice>0 && that.userinfo.shopscore>0 && that.userinfo.shopscoredk_money>0 && that.userinfo.shopscoredkmaxpercent>0) {
          var shopscoredk_money     = parseFloat(that.userinfo.shopscoredk_money); //会员产品积分换算最大可抵扣数值

          var shopscoremaxtype = parseInt(that.userinfo.shopscoremaxtype);//兑换类型
          if (shopscoremaxtype == 0) {
            var shopscoredkmaxpercent = parseFloat(that.userinfo.shopscoredkmaxpercent); //最大抵扣比例
            var nowshopscoredk_money  = alltotalprice * shopscoredkmaxpercent * 0.01;//现在可最大抵扣数值
          } else{
            var nowshopscoredk_money = parseFloat(that.userinfo.shopscoredkmaxmoney); //现在可最大抵扣数值
          }

          if(nowshopscoredk_money>0){
            nowshopscoredk_money = nowshopscoredk_money.toFixed(2);
            if(nowshopscoredk_money<=shopscoredk_money){
              alltotalprice -= nowshopscoredk_money;
            }else{
              alltotalprice -= shopscoredk_money;
            }
          }
        }

				if (alltotalprice < 0) alltotalprice = 0;
				alltotalprice = alltotalprice.toFixed(2);
				that.alltotalprice = alltotalprice;
				that.allservice_fee = allservice_fee;
				that.allfreezemoney_price = allfreezemoney_price;
				if(that.showprice_dollar && that.usdrate){
						that.usdalltotalprice = (that.alltotalprice/that.usdrate).toFixed(2);
				}
				
				that.allbuydata = allbuydata;
			},
			changeFreight: function(e) {
				var that = this;
				var allbuydata = that.allbuydata;
				var bid = e.currentTarget.dataset.bid;
				var index = e.currentTarget.dataset.index;
				var freightList = allbuydata[bid].freightList;
				if(freightList[index].pstype==1 && freightList[index].storedata.length < 1) {
					if(freightList[index].needchange == 1){
						that.needchange = 1;
					}else{
						app.error('无可自提门店');return;
					}
				}
				if(freightList[index].pstype==5 && freightList[index].storedata.length < 1) {
					if(freightList[index].needchange == 1){
						that.needchange = 1;
					}else{
						app.error('无可配送门店');return;
					}
				}
				allbuydata[bid].freightkey = index;
				if(freightList[index].show_add_floor || freightList[index].up_floor_fee){
					this.isfloor = 1;
					console.log(this.isfloor ,'this.isfloor ');
				}
				this.freightkey_bid = bid;
				this.freightkey_index = index;
				that.allbuydata = allbuydata;
				that.calculatePrice();
				that.allbuydata[bid].editorFormdata = [];
			},
			chooseFreight: function(e) {
				var that = this;
				var allbuydata = that.allbuydata;
				var bid = e.currentTarget.dataset.bid;
				// console.log(bid);
				// console.log(allbuydata);
				var freightList = allbuydata[bid].freightList;
				var itemlist = [];

				for (var i = 0; i < freightList.length; i++) {
					itemlist.push(freightList[i].name);
				}

				uni.showActionSheet({
					itemList: itemlist,
					success: function(res) {
						if (res.tapIndex >= 0) {
							allbuydata[bid].freightkey = res.tapIndex;
							that.allbuydata = allbuydata;
							that.calculatePrice();
						}
					}
				});
			},
			changeWeight: function(e) {
				var that = this;
				var allbuydata = that.allbuydata;
				var bid = e.currentTarget.dataset.bid;
				var proindex = e.currentTarget.dataset.proindex;
				var index = e.currentTarget.dataset.index;
				
				var weightList = allbuydata[bid].prodata[proindex].product.weightlist;

				allbuydata[bid].prodata[proindex].product.weightkey = index;
				that.allbuydata = allbuydata;
	
				that.calculatePrice();
				that.allbuydata[bid].editorFormdata = [];
			},
			choosePstime: function(e) {
				var that = this;
				var allbuydata = that.allbuydata;
				var bid = e.currentTarget.dataset.bid;
				var freightkey = allbuydata[bid].freightkey;
				var freightList = allbuydata[bid].freightList;
				var freight = freightList[freightkey];
				var pstimeArr = freightList[freightkey].pstimeArr;
				var itemlist = [];
				for (var i = 0; i < pstimeArr.length; i++) {
					itemlist.push(pstimeArr[i].title);
				}
				if (itemlist.length == 0) {
					app.alert('当前没有可选' + (freightList[freightkey].pstype == 1 ? '取货' : '配送') + '时间段');
					return;
				}
				that.nowbid = bid;
				that.pstimeDialogShow = true;
				that.pstimeIndex = -1;
			},
			pstimeRadioChange: function(e) {
				var that = this;
				var allbuydata = that.allbuydata;
				var pstimeIndex = e.currentTarget.dataset.index;
			 
				// console.log(pstimeIndex)
				var nowbid = that.nowbid;
				var freightkey = allbuydata[nowbid].freightkey;
				var freightList = allbuydata[nowbid].freightList;
				var freight = freightList[freightkey];
				var pstimeArr = freightList[freightkey].pstimeArr;
				var choosepstime = pstimeArr[pstimeIndex];
				allbuydata[nowbid].pstimetext = choosepstime.title;
				allbuydata[nowbid].freight_time = choosepstime.value;
				that.allbuydata = allbuydata
				that.pstimeDialogShow = false;
			},
			hidePstimeDialog: function() {
				this.pstimeDialogShow = false;
			},
			autoSelectCoupon(){
				var allbuydata = this.allbuydata;
				
				for (var bid in allbuydata) {
					var couponlist = allbuydata[bid].couponList;
					var coupons = [];
					var coupon_money = 0;
					var  xianxia_proid = {};//线下券 {proid:num} {123:3,3,124:2}
					var couponrids  =[];
					for(var ci in couponlist){
						if(couponlist[ci]['type'] == 11){
							var is_checked = 0
							var proids =couponlist[ci]['productids']; 
							
							for(var i in allbuydata[bid]['prodata']){
								var product = allbuydata[bid]['prodata'][i].product;
								var pronum  = allbuydata[bid]['prodata'][i].num;
								var proid = product.id;
							
								if(app.inArray(proid,proids)){
									//如果不存在，存入
									if(!xianxia_proid[proid]){
										xianxia_proid[proid] = 0;	
									}
									if(xianxia_proid[proid] < pronum){
										var newnum = Number(xianxia_proid[proid])+1;
										xianxia_proid[proid] =newnum;
										coupon_money +=Number( allbuydata[bid]['prodata'][i]['guige']['sell_price']);
										is_checked = 1;
										coupons.push(couponlist[ci]);
										couponrids.push(couponlist[ci].id);
									
										break;
									}else{
										continue;
									}
								}
							}
							if(is_checked ==0){
								var thiscouponrids = [];
								var thiscoupons = [];
								for(var i in allbuydata[bid].couponrids){
									if(allbuydata[bid].couponrids[i] != couponrid){
										thiscoupons.push(couponlist[i]);
										thiscouponrids.push(couponlist[i].id);
									}
								}
								allbuydata[bid].couponrids = thiscouponrids;
								allbuydata[bid].coupons = thiscoupons;
						
							}
						}
						
					}
					allbuydata[bid].coupon_money = coupon_money;
					allbuydata[bid].coupons = coupons;
					allbuydata[bid].couponrids = couponrids;
				}
				this.allbuydata = allbuydata;
				this.calculatePrice();
			},
			chooseCoupon: function(e) {
				var allbuydata = this.allbuydata;
				var bid = e.bid;
				var couponrid = e.rid;
				var couponkey = e.key;
	
				var oldcoupons = allbuydata[bid].coupons;
				var oldcouponrids = allbuydata[bid].couponrids;
				var couponList = allbuydata[bid].couponList;
				var is_use_coupon = 1;
				if (app.inArray(couponrid,oldcouponrids)) {
					var coupons = [];
					var couponrids = [];
					for(var i in oldcoupons){
						if(oldcoupons[i].id != couponrid){
							coupons.push(oldcoupons[i]);
							couponrids.push(oldcoupons[i].id);
						}
					}
					is_use_coupon = 0;
				} else {
					coupons = oldcoupons;
					couponrids = oldcouponrids;
					if(allbuydata[bid].coupon_peruselimit > oldcouponrids.length){
						if(this.ismultiselect){
								if(oldcouponrids.length==1){
									if(coupons[0].is_multiselect==0 || coupons[0].type!=1){
										app.error('不可一起使用');
										return;
									}
								}
								if(oldcouponrids.length>0){
									if(couponList[couponkey].is_multiselect==0 || couponList[couponkey].type!=1){
										app.error('不可一起使用');
										return;
									}
								}
						}
						coupons.push(couponList[couponkey]);
						couponrids.push(couponList[couponkey].id);
					}else{
						if(allbuydata[bid].coupon_peruselimit > 1){
							app.error('最多只能选用'+allbuydata[bid].coupon_peruselimit+'张');
							return;
						}else{
							coupons = [couponList[couponkey]];
							couponrids = [couponrid];
						}
					}
				}
				allbuydata[bid].coupons = coupons;
				allbuydata[bid].couponrids = couponrids;
				//实际 抵扣金额已超过 实际产品金额，
				if(allbuydata[bid].coupon_money >= allbuydata[bid].product_price && is_use_coupon){
					var thiscouponrids = [];
					var thiscoupons = [];
					for(var i in allbuydata[bid].couponrids){
						if(allbuydata[bid].couponrids[i] != couponrid){
							thiscoupons.push(coupons[i]);
							thiscouponrids.push(coupons[i].id);
						}
					}
					allbuydata[bid].couponrids = thiscouponrids;
					allbuydata[bid].coupons = thiscoupons;
					app.error('已抵扣完成');
				}
				var coupon_money = 0;
				var coupontype = 1;
				var  not_used_discount = 0;
				var  xianxia_proid = {};//线下券 {proid:num} {123:3,3,124:2}
					console.log(coupons,'coupons');
				for(var i in coupons){
					not_used_discount = coupons[i]['not_select_coupon'];
					if(coupons[i]['type'] == 4){
						//运费券
						coupontype = 4;
					}else if(coupons[i]['type'] == 3){
						var proids =coupons[i]['productids'];
						 var is_checked = 0
						for(var k in allbuydata[bid]['prodata']){
							var product = allbuydata[bid]['prodata'][k].product;
							var pronum  = allbuydata[bid]['prodata'][k].num;
							var proid = product.id;
							var limit_count =  coupons[i]['limit_count'];//计次次数
							var limit_perday = coupons[i]['limit_perday'];//每日可使用次数
							var sy_limit_perday = coupons[i]['sy_limit_perday']??0;//每日剩余可使用次数
							
							var dknum = 0;
							if(app.inArray(proid,proids)){
								//产品数量 > 计次次数
								var sy_limit_count = limit_count - coupons[i]['used_count'] 
								if(pronum > sy_limit_count){
									dknum = sy_limit_count;
								}else{
									dknum = pronum;
								}
								//每日限制 开启
								if( limit_perday > 0){
									dknum = dknum > sy_limit_perday?sy_limit_perday:dknum;
								}
								
							}
							if(dknum > 0){
								var product_sell_price  = allbuydata[bid]['prodata'][k]['guige']['sell_price'];
								console.log(product_sell_price,'product_sell_price');
								if(this.userinfo.discount >0 && this.userinfo.discount < 10){
									product_sell_price =Number(product_sell_price * this.userinfo.discount*0.1 )
									console.log(product_sell_price,'product_sell_price');
								}
								coupon_money +=Number( product_sell_price * dknum);
								is_checked = 1;
							}
						}
						if(is_checked ==0){
							//取消选择
							var thiscouponrids = [];
							var thiscoupons = [];
							for(var i in allbuydata[bid].couponrids){
								if(allbuydata[bid].couponrids[i] != couponrid){
									thiscoupons.push(coupons[i]);
									thiscouponrids.push(coupons[i].id);
								}
							}
							allbuydata[bid].couponrids = thiscouponrids;
							allbuydata[bid].coupons = thiscoupons;
						}
					}else if(coupons[i]['type'] == 10){
						//折扣券
						coupon_money += coupons[i]['thistotalprice'] * (100-coupons[i]['discount']) * 0.01;
					}else if(coupons[i]['type'] == 11){//线下兑换券，对应商品全部兑换
						//统计已选优惠券和科兑换
						//判断该优惠券中绑定的产品是否在下面商品中，且已兑换xianxia_proid中的数量不足 
						var is_checked = 0
						var proids =coupons[i]['productids']; 
						for(var i in allbuydata[bid]['prodata']){
							var product = allbuydata[bid]['prodata'][i].product;
							var pronum  = allbuydata[bid]['prodata'][i].num;
							var proid = product.id;

							if(app.inArray(proid,proids)){
								//如果不存在，存入
									if(!xianxia_proid[proid]){
										xianxia_proid[proid] = 0;	
									}
									if(xianxia_proid[proid] < pronum){
										var newnum = Number(xianxia_proid[proid])+1;
										xianxia_proid[proid] =newnum;
										coupon_money +=Number( allbuydata[bid]['prodata'][i]['guige']['sell_price']);
										is_checked = 1;
										break;
									}else{
										continue;
									}
							}
						}
						//因为上面已加入了已选择的优惠券中，这里不能再抵扣的删除掉
						if(is_checked ==0){
							var thiscouponrids = [];
							var thiscoupons = [];
							for(var i in allbuydata[bid].couponrids){
								if(allbuydata[bid].couponrids[i] != couponrid){
									thiscoupons.push(coupons[i]);
									thiscouponrids.push(coupons[i].id);
								}
							}
							allbuydata[bid].couponrids = thiscouponrids;
							allbuydata[bid].coupons = thiscoupons;
							app.error('已抵扣完成');
							return;
						}
					}else{
						coupon_money += coupons[i]['money']
					}
					
				}
				if(not_used_discount==1){
					var title ='使用该'+this.t('优惠券')+'时，'+this.t('会员')+'折扣不生效';
					uni.showToast({
						title: title,
						icon: 'none',		
						duration: 3000,
						success: function(res) {
							
						}
					});
				}
				allbuydata[bid].not_used_discount = not_used_discount;
				allbuydata[bid].coupontype = coupontype;
				allbuydata[bid].coupon_money = coupon_money;
				this.allbuydata = allbuydata;
				if(allbuydata[bid].coupon_peruselimit <2){
					this.couponvisible = false;
				}
				this.calculatePrice();
			},
			choosestore: function(e) {
        var that = this
				var bid        = e.currentTarget.dataset.bid;
				var storekey   = e.currentTarget.dataset.index;
        that.storekey  = storekey;
				var allbuydata = that.allbuydata;
				var buydata    = allbuydata[bid];
				var freightkey = buydata.freightkey
				allbuydata[bid].freightList[freightkey].storekey = storekey
				that.allbuydata = allbuydata;
        that.closestore();
			},
			//提交并支付
			topay: function(e) {
			
				var that = this;
				var needaddress = that.needaddress;
				var addressid = this.address && this.address.id?this.address.id:0;
				var checkmemid = this.checkMem.id;
				var linkman = this.linkman;
				var pcemail = this.pcemail;
				var tel = this.tel;
				var age = this.age;
				var gender = this.gender;
				var school = this.school;
				var major = this.major;
				var education = that.education;
				var enrol = this.enrol;
				var class_name = this.class_name;
				var faculties_name = this.faculties_name;
				var usescore = this.usescore;
				var frompage = that.opt.frompage ? that.opt.frompage : '';
				var allbuydata = that.allbuydata;
        var moneydecArr = that.moneydecArr;
				var fenqi_type = that.fenqi_type;
				var teammid = that.teamMember?that.teamMember.id:0;
        if (that.ishand == 1 && that.hwset && !that.isagree) {
          app.error('请先阅读并同意协议');
          return false;
        }

        if(!that.signatureurlShop && that.shopbuy_sign){
          return app.error("请先签订"+that.sign_contract_name);
        }
        
        //赠送好友，无需填写地址
        var usefreight = true;
        if(!that.usegiveorder){
          if (needaddress == 0) addressid = 0;
          if (needaddress == 1 && (addressid == undefined || addressid <=0)) {
            app.error('请选择收货地址');
            return;
          }
          
          if(this.contact_require == 1 && (linkman.trim() == '' || tel.trim() == '') ){
            return app.error("请填写联系人信息");
          }
          if(this.is_pingce){
            if(pcemail.trim() == ''){
              return app.error("请填写邮箱");
            }
            if(!/^([A-Za-z0-9_\-\.])+\@([A-Za-z0-9_\-\.])+\.([A-Za-z]{2,4})$/.test(pcemail)){
              return app.error("邮箱有误，请重填");
            }
            if(age == ''){
              return app.error("请选择生日");
            }
            if(gender == 0){
              return app.error("请选择性别");
            }
            if(school.trim() == ''){
              return app.error("请填写学校");
            }
            if(major.trim() == ''){
              return app.error("请填写专业");
            }
            if(education.trim() == ''){
              return app.error("请填写学历");
            }
            if(enrol.trim() == ''){
              return app.error("请填写入学年份");
            }
          }
         
          if(tel.trim()!= '' && !app.isPhone(tel)){
            return app.error("请填写正确的手机号");
          }
        }else{
          usefreight = false;
        }

				var buydata = [];
				for (var i in allbuydata) {
          //赠送好友，无需填写地址
          if(usefreight){
            var freightkey = allbuydata[i].freightkey;
            if (allbuydata[i].freightList[freightkey].pstimeset == 1 && allbuydata[i].freight_time == '') {
              app.error('请选择' + (allbuydata[i].freightList[freightkey].pstype == 1 ? '取货' : '配送') + '时间');
              return;
            }
            if (allbuydata[i].freightList[freightkey].pstype == 1 || allbuydata[i].freightList[freightkey].pstype == 2 || allbuydata[i].freightList[freightkey].pstype == 5) {
              var storekey = allbuydata[i].freightList[freightkey].storekey;
              var storeid = typeof allbuydata[i].freightList[freightkey].storekey !='undefined' && allbuydata[i].freightList[freightkey].storedata.length>0  ? allbuydata[i].freightList[freightkey].storedata[storekey].id : 0;
            } else {
              var storeid = 0;
            }
            if (allbuydata[i].freightList[freightkey].pstype == 11) {
              var type11key = allbuydata[i].type11key;
              if (type11key == 0 || !type11key) {
                app.error('请选择物流');
                return;
              }
              type11key = type11key - 1;
            } else {
              var type11key = 0
            }
          }else{
             var freightkey= -1;
             var storekey  =-1;
             var storeid   = 0;
             var type11key = 0;
          }
          
					var weightids={};
          var procommissionmids = [];//商品分销员id集合
					for(var j in allbuydata[i].prodata){
              var prodata = allbuydata[i].prodata[j];
							if(prodata.product.weighttype>0 && prodata.product.weightlist.length>0){
								var weightkey = prodata.product.weightkey;
								weightids[j]= prodata.product.weightlist[weightkey].id;		
							}

              if(prodata.product.commission_memberset){
                if((!prodata.product.procommissionmid || prodata.product.procommissionmid<=0)){
                  app.error('商品：'+prodata.product.name+'必须填写一个分销员ID');
                  return;
                }
                procommissionmids.push(prodata.product.procommissionmid);
              }else{
                procommissionmids.push(0);
              }
					}
          
          //赠送好友，无需填写地址
          if(usefreight){
            var formdata_fields = allbuydata[i].freightList[freightkey].formdata;
            var formdata = e.detail.value;
            var newformdata = {};
            var editorFormdata = allbuydata[i].editorFormdata;
            for (var j = 0; j < formdata_fields.length;j++){
              var thisfield = 'form'+allbuydata[i].bid + '_' + j;
              if (formdata_fields[j].val3 == 1 && (formdata[thisfield] === '' || formdata[thisfield] === undefined || (formdata[thisfield] == null || formdata[thisfield].length==0))){
                  app.alert(formdata_fields[j].val1+' 必填');return;
              }
              if (formdata_fields[j].key == 'selector') {
                if(formdata_fields[j].val3 == 1 && (Number.isNaN(formdata[thisfield]) || editorFormdata[j]==='' || editorFormdata[j]=='null' || editorFormdata[j]==undefined)){
                  app.alert(formdata_fields[j].val1+' 必选');return;
                }
                // if(formdata_fields[j].val3 == 1 && Number.isNaN(formdata[thisfield])){
                // 	app.alert(formdata_fields[j].val1+' 必选');return;
                // }
                formdata[thisfield] = formdata_fields[j].val2[editorFormdata[j]]
              }
              if(j > 0 && formdata_fields[j].val1 == '确认账号' && formdata_fields[j-1].val1 == '充值账号' && formdata[thisfield] != formdata['form'+allbuydata[i].bid + '_' + (j-1)]){
                app.alert('两次输入账号不一致');return;
              }
              newformdata['form'+j] = formdata[thisfield];
            }
              
            if(formdata.name == ''){
              app.alert('请填写姓名');
              return;
            }
            if(formdata.phone == ''){
              app.alert('请填写手机号');
              return;
            }
            if(formdata.email == ''){
              app.alert('请填写邮箱');
              return;
            }
            var freight_id = allbuydata[i].freightList[freightkey].id;
            var freight_time = allbuydata[i].freight_time;
          }else{
            var formdata    = {'name':'','phone':''};
            var freight_id  = 0;
            var freight_time= '';
            var newformdata = {};
          }
					//product_from处理
					var thisprodata = allbuydata[i].prodata;
					var formdata = e.detail.value;
					var newproductformdata = {};//商品
					for(var p = 0; p < thisprodata.length;p++){
						var this_product_formdata = thisprodata[p].formdata;
						var this_product_editformdata = thisprodata[p].editorFormdata;//编辑的信息
						
						console.log(this_product_editformdata,'this_product_editformdata');
						if(this_product_formdata && this_product_formdata.length > 0){
							//pf是formdata的key
							var this_newformdata = {};
							for (var pf = 0; pf < this_product_formdata.length; pf++){
								var thisfield = 'form'+allbuydata[i].bid+ '_' + p + '_' + pf;
								
								if (this_product_formdata[pf].key != 'input' && this_product_formdata[pf].key != 'textarea' ) {
									
								  if(this_product_formdata[pf].val3 == 1 && ( this_product_editformdata[pf]==='' || this_product_editformdata[pf]==null || this_product_editformdata[pf]==undefined)){
									  console.log(this_product_editformdata[pf],'this_product_editformdata[pf]11111')
								    app.alert(this_product_formdata[pf].val1+' 必选');return;
								  }
								}else{
									
									 if(this_product_formdata[pf].val3 == 1 && (formdata[thisfield] === '' || formdata[thisfield] === undefined || (formdata[thisfield] == null || formdata[thisfield].length==0))){
									    app.alert(this_product_formdata[pf].val1+' 必填');return;
									}
								}
								
								if(this_product_formdata[pf].key != 'input' && this_product_formdata[pf].key != 'textarea'){
									this_newformdata['form'+pf] = this_product_editformdata[pf];
								}else{
									this_newformdata['form'+pf] = formdata[thisfield];
								}
							}
						}
						newproductformdata[thisprodata[p].product.id] = this_newformdata;
					}
					var couponrid = (allbuydata[i].couponrids).join(',');
					//水票
					var water_couponrid = '';
					if(allbuydata[i].water_couponrids){
						water_couponrid = (allbuydata[i].water_couponrids).join(',');
					}
				
					
					if(allbuydata[i].freightList[freightkey].show_add_floor && allbuydata[i].freightList[freightkey].have_dianti==0  && allbuydata[i].freightList[freightkey].floor <= 0){
						app.alert('请输入楼层');
						return;
					} 
					
					var buydatatemp = {
						bid: allbuydata[i].bid,
						bidGroup:i,
						prodata: allbuydata[i].prodatastr,
						cuxiaoid: allbuydata[i].cuxiaoid,
						couponrid: couponrid,
						water_couponrid:water_couponrid,//水票id
						freight_id: freight_id,
						freight_time: freight_time,
						storeid: storeid,
						formdata:newformdata,
						product_formdta:newproductformdata,
						type11key: type11key,
            moneydec_rate:moneydecArr[i]?moneydecArr[i]:0,
						weightids:weightids,
            procommissionmids:procommissionmids,//商品分销员id集合
					};
					if(allbuydata[i].jldata){
						buydatatemp.jldata = allbuydata[i].jldata;
					}
					
					if(that.order_change_price) {
						buydatatemp.prodataList = allbuydata[i].prodata;
					}
					if(allbuydata[i].business.invoice) {
						buydatatemp.invoice = allbuydata[i].tempInvoice;
					}
					if(that.business_selfscore == 1){
						buydatatemp.usescore = that.scoredkdataArr[i].usescore;
					}
					//押金判断
					if(allbuydata[i].deposit_list &&  allbuydata[i].deposit_list.length > 0){
						if(allbuydata[i].deposit_list.length != allbuydata[i].deposit_list_key.length){
							that.$refs.refPopup.open();
							return;
						}
						
					}
					if(allbuydata[i].freightList[freightkey].show_add_floor){
						buydatatemp.up_floor_data = {
							have_dianti:allbuydata[i].freightList[freightkey].have_dianti,
							floor:allbuydata[i].freightList[freightkey].floor
						};
					}
					buydata.push(buydatatemp);
				}

				if(that.business_payconfirm && !that.businessinfoConfirm){
					that.$refs.dialogbusinessinfo.open();
					that.topayparams = e;
					return;
				}
				
				if(that.subsidy_verify){
					that.$refs.subsidyShowModal.open();
					that.topayparams = e;
					return;
				}
				// name,phone,email必填
			 		
				app.showLoading('提交中');
				app.post('ApiShop/createOrder', {
					frompage: frompage,
					buydata: buydata,
					addressid: addressid,
					linkman: linkman,
					pcemail: pcemail,
					tel: tel,
					checkmemid:checkmemid,
					usescore: usescore,
					latitude:that.latitude,
					longitude:that.longitude,
					worknum:that.worknum,
					discount_code_zc:that.discount_code_zc,
					fenqi_type:fenqi_type,
					teammid:teammid,
					isagree_pro:that.isagree_pro,
					devicedata:that.devicedata,
					roomid:that.roomid,
					usetongzheng:that.usetongzheng,
					usercard:that.usercard,
					goldsilvertype:that.goldsilvertype,
					name:formdata && formdata.name?formdata.name:'',
					phone:formdata && formdata.phone?formdata.phone:'',
					email:that.email,
					age:that.age,
					gender:that.gender,
					school:that.school,
					major:that.major,
					education:that.education,
					enrol:that.enrol,
					class_name:that.class_name,
					faculties_name:that.faculties_name,
          usededamount:that.usededamount,
          useshopscore:that.useshopscore,
          usegiveorder:that.usegiveorder,
          giveordertitle:that.giveordertitle,
          giveorderpic:that.giveorderpic,
          poshopid:that.poshopid,
          signatureurlShop:that.signatureurlShop,
					huomacode:that.huomacode
				}, function(res) {
					app.showLoading(false);
					if (res.status == 0) {
						//that.showsuccess(res.data.msg);
						app.error(res.msg);
						return;
					}
          //清除下单弹框缓存
          app.removeCache(that.opt.prodata+app.globalData.mid+'tk');
					//app.error('订单编号：' +res.payorderid);
					if(res.payorderid)
						app.goto('/pagesExt/pay/pay?id=' + res.payorderid,'redirectTo');
				});
			},
			showCouponList: function(e) {
				this.couponvisible = true;
				this.bid = e.currentTarget.dataset.bid;
			},
			showInvoice: function(e) {
				this.invoiceShow = true;
				this.bid = e.currentTarget.dataset.bid;
				let index = e.currentTarget.dataset.index;
				this.invoice_type = this.allbuydata[index].business.invoice_type;
				this.invoice = this.allbuydata[index].tempInvoice;
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
			changeNameType: function(e) {
				var that = this;
				var value = e.detail.value;
				that.name_type_select = value;
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
				// console.log(formdata);
				var allbuydata = that.allbuydata;
				for (var i in allbuydata) {
					if(allbuydata[i].bid == that.bid)
						allbuydata[i].tempInvoice = formdata;
				}
				that.allbuydata = allbuydata;
				that.invoiceShow = false;
					// that.loading = true;
					// uni.setStorageSync('temp_invoice_' + that.opt.bid, formdata);
				that.calculatePrice();
			},
			handleClickMask: function() {
				this.couponvisible = false;
				this.cuxiaovisible = false;
				this.type11visible = false;
				this.membervisible = false;
				this.invoiceShow = false;
				this.water_couponvisible = false;
			},
			showCuxiaoList: function(e) {
				this.cuxiaovisible = true;
				this.bid = e.currentTarget.dataset.bid;
			},
			changecx: function(e) {
				var that = this;
				var cxid = e.currentTarget.dataset.id;
				var cxindex = e.currentTarget.dataset.index;
				that.cxid = cxid;
				if (cxid == 0) {
					that.cuxiaoinfo = false;
					return;
				}
				var cuxiaoinfo = that.allbuydata[that.bid].cuxiaolist[cxindex];
				app.post("ApiShop/getcuxiaoinfo", {
					id: cxid
				}, function(res) {
					if (cuxiaoinfo.type == 4 || cuxiaoinfo.type == 5) {
						res.cuxiaomoney = cuxiaoinfo.cuxiaomoney
					}
					if(that.dandu_price && cuxiaoinfo.type == 6 && cuxiaoinfo.fwtype == 2){
						res.dandumoney = cuxiaoinfo.dandumoney
						res.is_dandu = cuxiaoinfo.is_dandu
					}
					that.cuxiaoinfo = res;
				});
			},
			changecxdefault: function(cxid,cxindex) {
				var that = this;
				that.cxid = cxid;
				if (cxid == 0) {
					that.cuxiaoinfo = false;
					return;
				}
				var cuxiaoinfo = that.allbuydata[that.bid].cuxiaolist[cxindex];
				app.post("ApiShop/getcuxiaoinfo", {
					id: cxid
				}, function(res) {
					if (cuxiaoinfo.type == 4 || cuxiaoinfo.type == 5) {
						res.cuxiaomoney = cuxiaoinfo.cuxiaomoney
					}
					if(that.dandu_price && cuxiaoinfo.type == 6 && cuxiaoinfo.fwtype == 2){
						res.dandumoney = cuxiaoinfo.dandumoney
						res.is_dandu = cuxiaoinfo.is_dandu
					}
					that.cuxiaoinfo = res;

					that.chooseCuxiao()
				});
			},
			changecxMulti: function(e) {
				var that = this;
				var cxid = e.currentTarget.dataset.id;
				var cxindex = e.currentTarget.dataset.index;
				that.cuxiaoList.length = 0;
				if (cxid == 0) {
					that.cuxiaoinfo = false;
					that.cxids.length = 0;
					that.cxid = 0;
					return;
				}
				var index = that.cxids.indexOf(cxid);
				if(index === -1){
					that.cxids.push(cxid);
				} else {
					that.cxids.splice(index);
				}
				if(that.cxids.length == 0) {
					that.cxid = 0;
					that.cuxiaoinfo = false;
					return;
				}
				that.cxid = '';
				var cuxiaoinfo = that.allbuydata[that.bid].cuxiaolist[cxindex];
				app.showLoading();
				app.post("ApiShop/getcuxiaoinfo", {
					id: that.cxids
				}, function(res) {
					// if (cuxiaoinfo.type == 4 || cuxiaoinfo.type == 5) {
					// 	res.cuxiaomoney = cuxiaoinfo.cuxiaomoney
					// }
					app.showLoading(false);
					that.cuxiaoList = res;
				});
			},
			chooseCuxiao: function() {
				var that = this;
				var allbuydata = that.allbuydata;
				var bid = that.bid;
				var cxid = that.cxid;
				var cxids = that.cxids;
				if (cxid == 0 || cxid == '') {
					allbuydata[bid].cuxiaoid = '';
					allbuydata[bid].cuxiao_money = 0;
					allbuydata[bid].cuxiaoname = '不使用促销';
					allbuydata[bid].cuxiaonameArr = [];
					allbuydata[bid].dandu_money = 0;
					allbuydata[bid].is_dandu = 0;
				} else {
					allbuydata[bid].cuxiaoid = [];
					allbuydata[bid].cuxiao_money = 0;
					allbuydata[bid].cuxiaotype = [];
					allbuydata[bid].cuxiaonameArr = [];
					allbuydata[bid].dandu_money = 0;
					allbuydata[bid].is_dandu = 0;
					if(that.cuxiaoList.info && that.cuxiaoList.info.length > 0) {
						for (var i in that.cuxiaoList.info) {
							var cxtype = that.cuxiaoList.info[i].type;
							if (cxtype == 1 || cxtype == 6) {
								//满额立减 满件立减
								allbuydata[bid].cuxiao_money += that.cuxiaoList.info[i]['money'] * -1;
							} else if (cxtype == 2) {
								//满额赠送
								allbuydata[bid].cuxiao_money += 0;
							} else if (cxtype == 3) {
								//加价换购  27.8+15.964+41.4
								allbuydata[bid].cuxiao_money += that.cuxiaoList.info[i]['money'];
							} else if (cxtype == 4 || cxtype == 5) {
								//满额打折 满件打折
								var cuxiaoMoney = 0;
								for ( var y in that.allbuydata[bid].cuxiaolist) {
									if(that.cuxiaoList.info[i].id == that.allbuydata[bid].cuxiaolist[y].id) {
										cuxiaoMoney = that.allbuydata[bid].cuxiaolist[y].cuxiaomoney;
									}
								}
								allbuydata[bid].cuxiao_money += cuxiaoMoney * -1
							}
							allbuydata[bid].cuxiaoid.push(that.cuxiaoList.info[i].id);
							allbuydata[bid].cuxiaotype.push(cxtype);
							allbuydata[bid].cuxiaonameArr.push(that.cuxiaoList.info[i]['name']);
						}
					} else {
						var cxtype = that.cuxiaoinfo.info.type;
						if (cxtype == 1 || cxtype == 6) {
							//满额立减 满件立减
							allbuydata[bid].cuxiao_money = that.cuxiaoinfo.info['money'] * -1;
							var fwtype = that.cuxiaoinfo.info.fwtype;
							if(that.dandu_price && cxtype == 6 && fwtype == 2){
								allbuydata[bid].dandu_money = that.cuxiaoinfo.dandumoney;
								allbuydata[bid].is_dandu = that.cuxiaoinfo.is_dandu;
							}
						} else if (cxtype == 2) {
							//满额赠送
							allbuydata[bid].cuxiao_money = 0;
						} else if (cxtype == 3) {
							//加价换购  27.8+15.964+41.4
							allbuydata[bid].cuxiao_money = that.cuxiaoinfo.info['money'];
						} else if (cxtype == 4 || cxtype == 5) {
							//var product_price = parseFloat(allbuydata[bid].product_price);
							//var leveldk_money = parseFloat(allbuydata[bid].leveldk_money); //会员折扣
							//var manjian_money = parseFloat(allbuydata[bid].manjian_money); //满减活动
							//满额打折 满件打折
							//allbuydata[bid].cuxiao_money = (1 - that.cuxiaoinfo.info['zhekou'] * 0.1) * (product_price - leveldk_money - manjian_money) * -1;
							allbuydata[bid].cuxiao_money = that.cuxiaoinfo.cuxiaomoney * -1
						}
						allbuydata[bid].cuxiaoid = cxid;
						allbuydata[bid].cuxiaotype = cxtype;
						allbuydata[bid].cuxiaoname = that.cuxiaoinfo.info['name'];
					}
				}
				this.allbuydata = allbuydata;
				this.cuxiaovisible = false;
				this.calculatePrice();
			},
			showType11List: function(e) {
				this.type11visible = true;
				this.bid = e.currentTarget.dataset.bid;
			},
			changetype11: function(e) {
				var that = this;
				var allbuydata = that.allbuydata;
				var bid = that.bid;
				that.type11key = e.currentTarget.dataset.index;
				// console.log(that.type11key)
			},
			chooseType11: function(e) {
				var that = this;
				var allbuydata = that.allbuydata;
				var bid = that.bid;
				var type11key = that.type11key;
				if (type11key == -1) {
					app.error('请选择物流');
					return;
				}
				allbuydata[bid].type11key = type11key + 1;
				// console.log(allbuydata[bid].type11key)
				var freightkey = allbuydata[bid].freightkey;
				var freightList = allbuydata[bid].freightList;
				var freight_price = parseFloat(freightList[freightkey].type11pricedata[type11key].price);
				var product_price = parseFloat(allbuydata[bid].product_price);
				// console.log(freightList[freightkey].freeset);
				// console.log(parseFloat(freightList[freightkey].free_price));
				// console.log(product_price);
				if (freightList[freightkey].freeset == 1 && parseFloat(freightList[freightkey].free_price) <=
					product_price) {
					freight_price = 0;
				}
				allbuydata[bid].freightList[freightkey].freight_price = freight_price;

				this.allbuydata = allbuydata;
				this.type11visible = false;
				this.calculatePrice();
			},
			openMendian: function(e) {
				var allbuydata = this.allbuydata
				var bid = e.currentTarget.dataset.bid;
				var freightkey = e.currentTarget.dataset.freightkey;
				var storekey = e.currentTarget.dataset.storekey;
        // this.storekey = storekey?storekey:0;
				var frightinfo = allbuydata[bid].freightList[freightkey]
				var storeinfo = frightinfo.storedata[storekey];
				// console.log(storeinfo)
				app.goto('mendian?id=' + storeinfo.id);
			},
			openLocation: function(e) {
				var allbuydata = this.allbuydata
				var bid = e.currentTarget.dataset.bid;
				var freightkey = e.currentTarget.dataset.freightkey;
				var storekey = e.currentTarget.dataset.storekey;
        // this.storekey = storekey?storekey:0;
				var frightinfo = allbuydata[bid].freightList[freightkey]
				var storeinfo = frightinfo.storedata[storekey];
				// console.log(storeinfo)
				var latitude = parseFloat(storeinfo.latitude);
				var longitude = parseFloat(storeinfo.longitude);
				var address = storeinfo.name;
				// address 地址的详细说明  支付宝小程序必填
				uni.openLocation({
					latitude: latitude,
					longitude: longitude,
					name: address,
					scale: 13,
					address:address
				})
			},
      //单图上传
      editorChooseImage: function (e) {
        var that = this;
        var idx = e.currentTarget.dataset.idx;
        var bid = e.currentTarget.dataset.bid;
        var editorFormdata = that.allbuydata[bid].editorFormdata;;
        if(!editorFormdata) editorFormdata = [];
        var type = e.currentTarget.dataset.type;
        app.chooseImage(function(data){
          editorFormdata[idx] = data[0];
          // console.log(editorFormdata)
          that.editorFormdata = editorFormdata
          that.allbuydata[bid].editorFormdata = editorFormdata
          that.test = Math.random();
        })
      },
      //多图上传，一次最多选8个
      editorChooseImages: function (e) {
        var that = this;
        var idx = e.currentTarget.dataset.idx;
        var bid = e.currentTarget.dataset.bid;
        var editorFormdata = that.allbuydata[bid].editorFormdata;;
        if(!editorFormdata) editorFormdata = [];
        var type = e.currentTarget.dataset.type;
        app.chooseImage(function(data){
          var pics = editorFormdata[idx];
          if(!pics){
            pics = [];
          }
          for(var i=0;i<data.length;i++){
            pics.push(data[i]);
          }
          editorFormdata[idx] = pics;
          that.allbuydata[bid].editorFormdata = editorFormdata
          that.test = Math.random();
        },8)
      },
      removeimg:function(e){
        var that = this;
        var idx = e.currentTarget.dataset.idx;
        var bid = e.currentTarget.dataset.bid;
        var editorFormdata = this.editorFormdata;
        if(!editorFormdata) editorFormdata = [];
        var type  = e.currentTarget.dataset.type;
        var index = e.currentTarget.dataset.index;
        if(type == 'pics'){
          var pics = editorFormdata[idx]
          pics.splice(index,1);
          editorFormdata[idx] = pics;
          that.allbuydata[bid].editorFormdata = editorFormdata
          that.test = Math.random();
        }else {
          editorFormdata[idx] = '';
          that.editorFormdata = editorFormdata
          that.test = Math.random();
          that.allbuydata[bid].editorFormdata = that.editorFormdata;
        }
      },
			editorBindPickerChange:function(e){
				var that = this;
				var bid = e.currentTarget.dataset.bid;
				var idx = e.currentTarget.dataset.idx;
				var val = e.detail.value;
				var editorFormdata = that.allbuydata[bid].editorFormdata;
				if(!editorFormdata) editorFormdata = [];
				editorFormdata[idx] = val;
				that.allbuydata[bid].editorFormdata = editorFormdata;
				that.test = Math.random();
			},
			editorBindPickerChangeAge:function(e){
				var val = e.detail.value;
				this.age = val
			},
			bindPickerChangeSex:function(e){
				var val = e.detail.value;
				this.gender = val==0?'男':'女';
			},
			bindPickerChangeEdu:function(e){
				var val = e.detail.value;
				this.education = this.edulist[val];
			},
			editorBindPickerChangeEnrol:function(e){
				var val = e.detail.value;
				console.log(val);
				this.enrol = val
			},

			showMemberList: function(e) {
				this.membervisible = true;
			},
			regionchange2: function(e) {
				const value = e.detail.value
				// console.log(value[0].text + ',' + value[1].text + ',' + value[2].text);
				this.regiondata = value[0].text + ',' + value[1].text + ',' + value[2].text
			},
			memberSearch: function() {
				var that = this;
				// console.log(that.regiondata)
				app.post('ApiShop/memberSearch', {
					diqu: that.regiondata
				}, function(res) {
					app.showLoading(false);
					if (res.status == 0) {
						app.error(res.msg);
						return;
					}
					var data = res.memberList;
					that.memberList = data;
				});
			},
			checkMember: function(e) {
				var that = this;
				that.checkMem = e.currentTarget.dataset.info;
				this.membervisible = false;
			},
			showmemberinfo:function(e){
				var that = this;
				var mid = e.currentTarget.dataset.mid;
				app.showLoading('提交中');
				app.post('ApiShop/getmemberuplvinfo',{mid:mid}, function(res) {
					app.showLoading(false);
					if (res.status == 0) {
						app.error(res.msg);
						return;
					}
					that.selectmemberinfo = res.info;
					that.memberinfovisible = true;
				});
			},
			memberinfoClickMask:function(){
				this.memberinfovisible = false;
			},
			businessinfoClose:function(){
				this.$refs.dialogbusinessinfo.close();
			},
			businessinfoOk:function(){
				this.businessinfoConfirm = true;
				this.$refs.dialogbusinessinfo.close();
				this.topay(this.topayparams);
			},
			doStoreShowAll:function(){
				this.storeshowall = true;
			},
			showglass:function(e){
				var that = this
				var grid = e.currentTarget.dataset.grid;
				var index = e.currentTarget.dataset.index;
				var index2 = e.currentTarget.dataset.index2;
				// console.log(that.glassrecordlist)
				if(that.glassrecordlist.length<1){
					//没有数据 就重新请求
					that.getglassrecord();
				}else{
					that.isshowglass = true
				}
				
				that.curindex = index
				that.curindex2 = index2
				that.grid = grid
			},
			showglasscustom:function(e){
				var that = this
				var gcid = e.currentTarget.dataset.gcid;
				if(that.glasscustom.length == 0){
					//没有数据 就重新请求
					that.getglasscustom();
				}else{
					that.isshowglasscustom = true
				}
				that.gcid = gcid
			},
			getglassrecord:function(e){
				var that = this
				if(that.hasglassproduct==1){
					that.loading  = true;
					app.post('ApiGlass/myrecord', {pagenum:1,listrow:100}, function (res) {
						that.loading = false;
					  var datalist = res.data;
						that.glassrecordlist = datalist;
            that.isshowglass = true
					});
				}
				
			},
			getglasscustom:function(e){
				var that = this
				if(that.hasglasscustomproduct==1){
					that.loading  = true;
					app.post('ApiGlassCustom/myCustom', {pagenum:1,listrow:100,id:that.gcid}, function (res) {
						that.loading = false;
						if(res.data && res.data[0]){
							that.glasscustom = res.data[0];
						}
			      that.isshowglasscustom = true
					});
				}
			},
			hideglass:function(e){
				var that = this
				that.isshowglass = false;
			},
			hideglasscustom:function(e){
				var that = this
				that.isshowglasscustom = false;
			},
			chooseglass:function(e){
				var that = this;
				var gindex = e.detail.value;
				var allbuydata = that.allbuydata;
				var grid = that.grid;
				var index = that.curindex;
				var index2 = that.curindex2;
				var glassrecordlist = that.glassrecordlist;
				var product = allbuydata[index]['prodata'][index2].product
				var prodata = allbuydata[index]['prodata'];
				var prodataArr = [];
				var sid = glassrecordlist[gindex].id
				for(let i in prodata){
					var pid = prodata[i].product.id
					var guigeid = prodata[i].guige.id
					var num = prodata[i].num
					var pgrid = 0;
					if(prodata[i].product.has_glassrecord==1 && prodata[i].product.glassrecord){
						var pgrid = prodata[i].product.glassrecord.id
					}
					
					if(index2==i){
						if(grid==sid){
							product.glassrecord = {};
							product.has_glassrecord = 0
							that.grid = 0;
							pgrid = 0
						}else{
							product.glassrecord = glassrecordlist[gindex]
							product.has_glassrecord = 1
							that.grid = glassrecordlist[gindex].id
							pgrid = glassrecordlist[gindex].id
						}
					}
					var pchild = pid+','+guigeid+','+num+','+pgrid;
					prodataArr.push(pchild)
				}
				var prodatastr = prodataArr.join('-');								
				that.allbuydata[index]['prodata'][index2]['product'] = product;
				that.allbuydata[index]['prodatastr'] = prodatastr
				that.isshowglass = false;
			},
			moneydk: function(e) {
					var that = this;
					var moneydec = that.moneydec;
					if(moneydec){
							var bid         = e.currentTarget.dataset.bid;
              var type        = e.currentTarget.dataset.type;
              //设置积分抵扣类型 0：系统设置比例 1：商品存在单独设置固定金额
              var moneydecArrType = that.moneydecArrType;
              moneydecArrType[bid]= type;
              that.moneydecArrType= moneydecArrType;
              
							var rate        = e.currentTarget.dataset.rate-0;
              var money       = e.currentTarget.dataset.money-0;
							var moneydecArr = that.moneydecArr;
							if(moneydecArr[bid] && moneydecArr[bid]>0){
									moneydecArr[bid]= 0;
							}else{
                  if(type == 1){
                    moneydecArr[bid]= parseFloat(money);
                  }else{
                    moneydecArr[bid]= parseFloat(rate);
                  }
							}
							that.moneydecArr= moneydecArr;
							this.calculatePrice();
					}
			},
      showstore:function(e){
        var that = this;
        var storedata     = e.currentTarget.dataset.storedata;
        if(storedata && storedata.length>0){
          var len = storedata.length;
          for(var i=0;i<len;i++){
              storedata[i]['searchkey'] = i;
          }
        }
        var storefreightkey = e.currentTarget.dataset.storefreightkey;
        var storekey        = e.currentTarget.dataset.storekey;
        var storebid        = e.currentTarget.dataset.storebid;
        that.storedata      = storedata?storedata:'';
        that.storekey       = storekey?storekey:0;
        that.storefreightkey= storefreightkey?storefreightkey:0;
        that.storebid       = storebid?storebid:0;
        that.storevisible   = true;
      },
      closestore:function(e){
        var that = this;
        that.storevisible = false;
      },
      inputStorename:function(e){
        var that = this;
        that.storename = e.detail.value;
      },
      searchStore:function(){
        var that = this;
        var storename = that.storename;
        var storedata = that.storedata;
        if(!storename){
          if(storedata && storedata.length>0){
            var len = storedata.length;
            for(var i=0;i<len;i++){
                storedata[i]['searchkey'] = i;
            }
          }
        }else{
          if(storedata && storedata.length>0){
            var len = storedata.length;
            for(var i=0;i<len;i++){
              //查询位置
              var namestr = storedata[i]['name'];
              var pos  = namestr.indexOf(storename);
              if(pos>=0){
                storedata[i]['searchkey'] = i;
              }else{
                storedata[i]['searchkey'] = -1;
              }
            }
          }
        }
        that.storedata = storedata;
      },
			inputCouponCode: function(e) {
				var that = this;
				var index = e.currentTarget.dataset.index;
				var allbuydata = that.allbuydata;
				var allbuydatawww = that.allbuydatawww;
				that.discount_code_zc = e.detail.value;
				allbuydata[index].discount_code_zc_status = false;
				if(e.detail.value) {
					app.post("ApiShop/checkDiscountCodeZc", {
						discount_code_zc: e.detail.value
					}, function(res) {
						console.log(res)
						if(res.status == 1){
							allbuydata[index].discount_code_zc_status = true;
						}
						that.allbuydata = allbuydata;
						that.calculatePrice();
					});
				}else{
					that.allbuydata = allbuydata;
					that.calculatePrice();
				}
			},
      isagreeChange: function (e) {
        var val = e.detail.value;
        if (val.length > 0) {
          this.isagree = true;
        } else {
          this.isagree = false;
        }
      },
      showxieyiFun: function () {
        this.showxieyi = true;
      },
      hidexieyi: function () {
        this.showxieyi = false;
      	this.isagree = true;
      },
	  
	  showTeamMemberList:function(){
		  var that = this;
		  app.showLoading();
		  var tmid = that.tmid;
		  app.post('ApiShop/getTeamMemberList', {tmid:tmid}, function(res) {
		  	app.showLoading(false);
		  	if (res.status == 0) {
		  		app.error(res.msg);
		  		return;
		  	}
		  	that.teamMemberList = res.data;
				that.memberOtherShow = true;
		  });
	  },
	  handlMememberOther:function(){
		   this.memberOtherShow = false;
	  },
	  checkTeamMember: function(e) {
			var that = this;
			that.teamMember = e.currentTarget.dataset.info;
			this.memberOtherShow = false;
	  },
	  showproductxieyi:function(index){
		  var that = this;
		  var product_xieyi = that.product_xieyi;
		  that.proxieyi_content = product_xieyi[index].content;
		  that.showproxieyi = 1;
	  },
	  isagreeProChange: function (e) {
	    var val = e.detail.value;
	    if (val.length > 0) {
	      this.isagree_pro = true;
	    } else {
	      this.isagree_pro = false;
	    }
	  },
	  hideproxieyi:function(){
		  this.showproxieyi = 0;
		  this.isagree_pro = true;
	  },
    hidetkts:function(){
      this.buy_message_prompt_status = false;
    },
    dedamountdk:function(){
      this.usededamount = !this.usededamount;
      this.calculatePrice();
    },
    //产品积分抵扣
    shopscoredk: function(e) {
    	var useshopscore  = e.detail.value[0];
    	if (!useshopscore) useshopscore = 0;
    	this.useshopscore = useshopscore;
    	this.calculatePrice();
    },
    changeusegiveorder:function(){
      this.usegiveorder = !this.usegiveorder;
      this.calculatePrice();
    },
    inputGiveordertitle:function(e){
      this.giveordertitle = e.detail.value;
    },
    uploadimg2:function(e){
    	var that = this;
    	var pernum = parseInt(e.currentTarget.dataset.pernum);
    	if(!pernum) pernum = 1;
    	var field= e.currentTarget.dataset.field
    	var pics = that[field]
    	if(!pics) pics = [];
    	app.chooseImage(function(urls){
    		for(var i=0;i<urls.length;i++){
    			pics.push(urls[i]);
    		}
        if(field == 'giveorderpic') that.giveorderpic = pics[0];
    	},pernum);
    },
    removeimg2:function(e){
    	var that = this;
    	var index= e.currentTarget.dataset.index
    	var field= e.currentTarget.dataset.field
    	if(field == 'giveorderpic'){
    		that.giveorderpic = '';
    	}
    },
		gotoMendianList:function(limit_mendianids,bid){
			if(this.mendian_change == true){
				var url = '/pagesB/mendianup/list?bid='+bid
				if(limit_mendianids){
					url = url+'&needmdid='+limit_mendianids
				}

				app.goto(url)
			}
		},
		subsidyShowModalSuccess:function(e){
			this.$refs.subsidyShowModal.close();
			if(e == 1){
				this.subsidy_verify = 0;
				this.subsidy_tips = '';
				this.topay(this.topayparams);
			}
		},
		//product_form开始
		productEditorBindPickerChange:function(e){
			var that = this;
			var fidx = e.currentTarget.dataset.idx;//表单索引
			var index2 = e.currentTarget.dataset.index;//商品索引
			var bid = e.currentTarget.dataset.bid;//所在
			
			var val = e.detail.value;
			var editorFormdata = that.allbuydata[bid].prodata[index2].editorFormdata;
			if(!editorFormdata) editorFormdata = [];
			editorFormdata[fidx] = val;
	
			 that.allbuydata[bid].prodata[index2].editorFormdata =editorFormdata; //对应到每个产品中
			 console.log(that.allbuydata,'------');
			 that.test = Math.random();
		},
		setProductRatio(e){
			var that = this;
			var fidx = e.currentTarget.dataset.idx;//表单索引
			var index2 = e.currentTarget.dataset.index;//商品索引
			var bid = e.currentTarget.dataset.bid;//所在
			var editorFormdata = that.allbuydata[bid].prodata[index2].editorFormdata;
			var value = e.detail.value;
			if(!editorFormdata) editorFormdata = [];
			editorFormdata[fidx] = value;
			that.allbuydata[bid].prodata[index2].editorFormdata =editorFormdata; //对应到每个产品中
			console.log(value,'value');
			console.log(fidx,'fidx');
			
		},
		setProductCheckbox(e){
			var that = this;
			var fidx = e.currentTarget.dataset.idx;//表单索引
			var index2 = e.currentTarget.dataset.index;//商品索引
			var bid = e.currentTarget.dataset.bid;//所在
			var editorFormdata = that.allbuydata[bid].prodata[index2].editorFormdata;
			var value = e.detail.value;
			if(!editorFormdata) editorFormdata = [];
			editorFormdata[fidx] = value;
			that.allbuydata[bid].prodata[index2].editorFormdata =editorFormdata; //对应到每个产品中
		},
		yearChange:function(e){
			var that = this;
			var fidx = e.currentTarget.dataset.idx;//表单索引
			var index2 = e.currentTarget.dataset.index;//商品索引
			var bid = e.currentTarget.dataset.bid;//所在
			var val = e.detail.value;
			var thisformdata = that.allbuydata[bid].prodata[index2].formdata[fidx];
			
			var yearlist =thisformdata.val2;
			val = yearlist[val];
			
			var editorFormdata = that.allbuydata[bid].prodata[index2].editorFormdata;
			if(!editorFormdata) editorFormdata = [];
			editorFormdata[fidx] = val;	
			
			that.allbuydata[bid].prodata[index2].editorFormdata =editorFormdata; //对应到每个产品中
			that.test = Math.random();
		},
		productRegionChange(event,bid,index,fidx){

			var that = this;
			var thisformdata = that.allbuydata[bid].prodata[index].formdata[fidx];
	// 				console.log(e);
	 		const value = event.detail.value
	
			var region = value[0].text + ',' + value[1].text + ',' + value[2].text;
			var editorFormdata = that.allbuydata[bid].prodata[index].editorFormdata;
			if(!editorFormdata) editorFormdata = [];
			editorFormdata[fidx] = region;	
			that.allbuydata[bid].prodata[index].editorFormdata =editorFormdata; //对应到每个产品中
			console.log(that.allbuydata[bid].prodata[index].editorFormdata,'====');
			 that.test = Math.random();
		},
		productEditorChooseImage: function (e) {
			var that = this;
			var fidx = e.currentTarget.dataset.idx;//表单索引
			var index2 = e.currentTarget.dataset.index;//商品索引
			var bid = e.currentTarget.dataset.bid;//所在
			
			var editorFormdata = that.allbuydata[bid].prodata[index2].editorFormdata;
			console.log(editorFormdata,'editorFormdata');
			if(!editorFormdata) editorFormdata = [];
			var type = e.currentTarget.dataset.type;
			app.chooseImage(function(data){
			  if(type == 'pics'){
				   var pics = [];
				  if(editorFormdata && editorFormdata[fidx]){
					  pics = editorFormdata[fidx];
				  }
			    
			    if(!pics){
			      pics = [];
			    }
			    for(var i=0;i<data.length;i++){
			    	pics.push(data[i]);
			    }
			    console.log(pics)
			    editorFormdata[fidx] = pics;
				
				that.allbuydata[bid].prodata[index2].editorFormdata =editorFormdata; //对应到每个产品中
			    that.test = Math.random();
			  
			  }else{
			    editorFormdata[fidx] = data[0];
			    console.log(editorFormdata)
			    that.allbuydata[bid].prodata[index2].editorFormdata =editorFormdata; //对应到每个产品中
			    that.test = Math.random();
			  }
						
			
			})
			console.log( that.allbuydata);
		},
		productFormdataRemoveimg(e){
			var that = this;
			var fidx = e.currentTarget.dataset.idx;//表单索引
			var index2 = e.currentTarget.dataset.index;//商品索引
			var bid = e.currentTarget.dataset.bid;//所在
			var editorFormdata = that.allbuydata[bid].prodata[index2].editorFormdata;
			if(!editorFormdata) editorFormdata = [];
			if(type == 'pics'){
			  var pics = editorFormdata[fidx]
			  pics.splice(index,1);
			  editorFormdata[fidx] = pics;
			 that.allbuydata[bid].prodata[index2].editorFormdata =editorFormdata; 
			  that.test = Math.random();
			  that.formvaldata[field] = pics;
			}else{
			  editorFormdata[fidx] = '';
			 that.allbuydata[bid].prodata[index2].editorFormdata =editorFormdata; 
			  that.test = Math.random();
			 
			}
		},
		//product_form结束
		inputCommissionmid:function(e){
		  var that = this;
		  var val = e.detail.value;
		  var bid = e.currentTarget.dataset.bid;//所在
		  var index2 = e.currentTarget.dataset.index;//商品索引
		  that.allbuydata[bid].prodata[index2]['product']['procommissionmid'] = val;
		},
		//选择押金
		chooseDeposit(e){
			var that =this;
			var index = e.currentTarget.dataset.index;//索引
			var bid = e.currentTarget.dataset.bid;//所在
			var deposit_list_key = that.allbuydata[bid].deposit_list_key;
			console.log(deposit_list_key,'deposit_list_key');
			if(that.inArray(index,deposit_list_key)){
				var new_key = [];
				for(var i=0;i < deposit_list_key.length;i++ ){
					if(deposit_list_key[i] != index){
						new_key.push(deposit_list_key[i]);
					}
				}
				 that.allbuydata[bid].deposit_list_key = new_key;
			}else{
				deposit_list_key.push(index);
			}
			that.calculatePrice();
		},
		depositClose(){
			this.$refs.refPopup.close();
		},
		//上楼费操作
		showExplain(e){
			var that = this;
			var bid = e.currentTarget.dataset.bid;
			var allbuydata = that.allbuydata;
			var freightdata = allbuydata[bid].freightList[allbuydata[bid].freightkey];
			var up_floor_rule = freightdata.up_floor_rule; 
			uni.showModal({
				title: '',
				content: freightdata.up_floor_rule,
				showCancel:false
			});
		},
		diantiChange(e){
			var that = this;
			var bid = e.currentTarget.dataset.bid;
			var index = e.currentTarget.dataset.index;
				console.log(index,'index')
			var allbuydata = that.allbuydata;
			var freightdata = allbuydata[bid].freightList[allbuydata[bid].freightkey];
			allbuydata[bid].freightList[allbuydata[bid].freightkey]['have_dianti'] = index
			if(index ==1){
				var up_floor_fee  = 0;
				allbuydata[bid].freightList[allbuydata[bid].freightkey]['up_floor_fee'] = up_floor_fee;
			}
			that.allbuydata = allbuydata;
			that.calculatePrice();
		},
		inputFloornum(e){
			var that = this;
			var bid = e.currentTarget.dataset.bid;
			var floornum = e.detail.value;
			var allbuydata = that.allbuydata;
			var freightdata = allbuydata[bid].freightList[allbuydata[bid].freightkey];
			var up_floor_fee  = 0;
			
			if(floornum > 0){
				var allbuydata = that.allbuydata;
				var pronum = 0;
				for(var i in allbuydata[bid]['prodata']){
					 pronum  =pronum + parseInt(allbuydata[bid]['prodata'][i].num) ;
				}
				up_floor_fee =parseFloat( parseFloat( freightdata.up_floor_base_price) + parseFloat(freightdata.up_floor_each_add_price * (floornum - 1)* pronum)  ).toFixed(2);
				
			}
			allbuydata[bid].freightList[allbuydata[bid].freightkey]['up_floor_fee'] = up_floor_fee;
			allbuydata[bid].freightList[allbuydata[bid].freightkey]['floor'] = floornum;
			that.allbuydata = allbuydata;
			that.calculatePrice();
		},
		//选择水票
		chooseWaterCoupon(e){
				console.log('-----');
			var allbuydata = this.allbuydata;
		
			var bid = e.bid;
			var couponrid = e.rid;
			var couponkey = e.key;
			var oldcoupons = allbuydata[bid].water_coupons;
			var oldcouponrids = allbuydata[bid].water_couponrids;
			var couponList = allbuydata[bid].water_couponList;
			var is_use_coupon = 1;
			var oldcouponkeys = allbuydata[bid].water_couponkeys;
			
			if (app.inArray(couponrid,oldcouponrids)) {
				
				var coupons = [];
				var couponrids = [];
				var couponkeys = [];
				for(var i in oldcoupons){
					if(oldcoupons[i].id != couponrid){
						coupons.push(oldcoupons[i]);
						couponrids.push(oldcoupons[i].id);
						couponkeys.push(oldcouponkeys[i]);
					}
				}
			
				is_use_coupon = 0;
			}else {
				coupons = oldcoupons;
				couponrids = oldcouponrids;
				couponkeys = oldcouponkeys;
				//是否开启多选
				if(allbuydata[bid].water_ck_mutiselect){
					coupons.push(couponList[couponkey]);
					couponrids.push(couponList[couponkey].id);
					couponkeys.push(couponkey);
				}else{
					coupons = [couponList[couponkey]];
					couponrids = [couponrid];
					couponkeys=[couponkey];
				}
			}
			allbuydata[bid].water_coupons = coupons;
			allbuydata[bid].water_couponrids = couponrids;
			if(allbuydata[bid].water_coupon_money >= allbuydata[bid].product_price && is_use_coupon && allbuydata[bid].water_ck_mutiselect){
				var thiscouponrids = [];
				var thiscoupons = [];
				for(var i in allbuydata[bid].water_couponrids){
					if(allbuydata[bid].water_couponrids[i] != couponrid){
						thiscoupons.push(coupons[i]);
						thiscouponrids.push(coupons[i].id);
					}
				}
				allbuydata[bid].water_couponrids = thiscouponrids;
				allbuydata[bid].water_coupons = thiscoupons;
				app.error('已抵扣完成');
			}
			
			//选择第一个之后的券，都会重新计算，所以要清空一下
			for(var k in allbuydata[bid]['prodata']){
				allbuydata[bid]['prodata'][k].water_dk_num = 0;
			}	
			allbuydata[bid].water_couponkeys = couponkeys;	
			var coupon_money = 0;	
			console.log(couponkeys,'couponkeys');
			for(var i in coupons){
				var couponkey = couponkeys[i];
				console.log(couponkey,'couponkey');
				
				if(allbuydata[bid].water_couponList[couponkey].coupon_use_water_num ===undefined){
					allbuydata[bid].water_couponList[couponkey].coupon_use_water_num = 0;
				}
				 if(coupons[i]['type'] == 3){
					 var proids =coupons[i]['productids'];
					  var is_checked = 0
					  var limit_count =  coupons[i]['limit_count'];//计次次数
					  var limit_perday = coupons[i]['limit_perday'];//每日可使用次数
					  var sy_limit_perday = coupons[i]['sy_limit_perday']?coupons[i]['sy_limit_perday']:0;//每日剩余可使用次数
					  var coupon_use_water_num = 0;//当前券使用了几次
					for(var k in allbuydata[bid]['prodata']){
						var product = allbuydata[bid]['prodata'][k].product;
						var pronum  = allbuydata[bid]['prodata'][k].num;
						
						var proid = product.id;
					
						var dknum = 0;

						if(app.inArray(proid,proids)){
							//产品数量 > 计次次数
							var sy_limit_count = limit_count - coupons[i]['used_count'] 
							//还得计算抵扣的数量			
							console.log(coupon_use_water_num,'use_water_numuse_water_numuse_water_num');
							//商品已抵扣次数
							var water_dk_num = allbuydata[bid]['prodata'][k].water_dk_num?allbuydata[bid]['prodata'][k].water_dk_num:0;//当前商品，已抵扣数量，为后面几张优惠券计算使用，例如共5个，第一张券抵扣掉2个，剩余3个，2为这个值
							sy_limit_count = sy_limit_count - coupon_use_water_num ; //当前优惠券剩余次数= 本身剩余次数 - 上个商品已使用的数量
					
							var pronum2 =pronum; 
							pronum2 = pronum2 - water_dk_num;//剩余抵扣的商品数量 = 商品的数量 - 已抵扣的次数
						
							if(pronum2 > sy_limit_count){
								dknum = sy_limit_count;
							}else{
								dknum = pronum2;
							}
							//每日限制 开启
							if( limit_perday > 0){
								dknum = dknum > sy_limit_perday?sy_limit_perday:dknum;
							}
							
						}
						if(dknum > 0){
							var product_sell_price  = allbuydata[bid]['prodata'][k]['guige']['sell_price'];
							
							coupon_money +=Number( product_sell_price * dknum);
							is_checked = 1;
							coupon_use_water_num += dknum;
							//更新商品 一共使用了几次，如果次数和商品次数一样，就不再继续抵扣
							if(isNaN(allbuydata[bid]['prodata'][k].water_dk_num)){
								allbuydata[bid]['prodata'][k].water_dk_num = dknum;
							}else{
								allbuydata[bid]['prodata'][k].water_dk_num =parseInt( Number(allbuydata[bid]['prodata'][k].water_dk_num)+ Number( dknum)) ;
							}
							console.log(allbuydata,'allbuydata');
						}
						
					 }
					 if(is_checked ==0){
						//取消选择
						var thiscouponrids = [];
						var thiscoupons = [];
						for(var i in allbuydata[bid].water_couponrids){
							if(allbuydata[bid].water_couponrids[i] != couponrid){
								thiscoupons.push(coupons[i]);
								thiscouponrids.push(coupons[i].id);
							}
						}
						allbuydata[bid].water_couponrids = thiscouponrids;
						allbuydata[bid].water_coupons = thiscoupons;
					}					
					console.log(coupon_use_water_num,'coupon_use_water_numcoupon_use_water_numcoupon_use_water_numcoupon_use_water_num');
					allbuydata[bid].water_couponList[couponkey].coupon_use_water_num = coupon_use_water_num;
					console.log(allbuydata);
					console.log('-----------------------------------------');
				 }
			}
			console.log(coupon_money);
			allbuydata[bid].water_coupon_money = coupon_money;
			this.allbuydata = allbuydata;
			
			this.calculatePrice();
		},
		showSpCouponList: function(e) {
			this.water_couponvisible = true;
			this.bid = e.currentTarget.dataset.bid;
		},
		toBuyWaterCoupon(e){
			var url = e.currentTarget.dataset.url;
			var bid = e.currentTarget.dataset.bid;
		
			console.log(url)
			url =url + '?bid='+bid+'&tourl='+encodeURIComponent('/pagesB/shop/buy?prodata='+this.opt.prodata);
				console.log(url)
			app.goto(url);
		},
    // 不同意合同
    closehetong(){
      this.shopbuy_sign_tk = false;
    },
    showhetongFun: function () {
      var that=this
      this.shopbuy_sign_tk = true;
    },
    hidehetong: function () {
      var that=this
      this.shopbuy_sign_tk = false;
    },
	}
}
</script>

<style>
.container{overflow: hidden;}
.address-add {width: 94%;margin: 20rpx 3%;background: #fff;border-radius: 20rpx;padding: 20rpx 3%;min-height: 140rpx;}
.address-add .f1 {margin-right: 20rpx}
.address-add .f1 .img {width: 66rpx;height: 66rpx;}
.address-add .f2 {color: #666;}
.address-add .f3 {width: 26rpx;height: 26rpx;}
.linkitem {width: 100%;padding: 5px 0;background: #fff;display: flex;align-items: center}.cf3 {width: 200rpx;height: 26rpx;display: block;
    text-align: right;}
.linkitem .f1 {width: 130rpx;color: #111111;text-align:justify;text-align-last:justify;}
.linkitem .f1 .redStar {color:red; display:inline-block;width: 14rpx;}
.linkitem .input {height: 50rpx; line-height: 50rpx;padding-left: 10rpx;color: #222222;font-size: 28rpx;flex: 1}
.buydata {width: 94%;margin: 0 3%;background: #fff;margin-bottom: 20rpx;border-radius: 20rpx;display: flex;flex-direction: column;}
/* 分期 */
.fenqi-checkbox{width: 100%;padding: 24rpx 20rpx;background: #fff;display: flex;align-items: center;}
.fenqi-list-view{width: 100%;padding: 20rpx 20rpx;display: flex;align-items: center;justify-content: flex-start;flex-wrap: wrap;}
.fenqi-list-view .fenqi-options{width: 200rpx;display: inline-block;margin-right: 20rpx;background:#f6f6f6;border-radius:8rpx;padding: 10rpx 5rpx;margin-bottom: 20rpx;}
.fenqi-options .fenqi-num{font-size: 24rpx;color: #333;width: 100%;text-align: center;padding: 3rpx 0rpx;display: flex;align-items: center;justify-content: center;}
.fenqi-options .fenqi-num .fenqi-bili{font-size: 20rpx;color: #5b5b5b;margin-left: 10rpx;}
.fenqi-options .fenqi-give{font-size: 22rpx;color: #5b5b5b;width: 100%;text-align: center;padding: 3rpx 0rpx;}

.btitle {width: 100%;padding: 20rpx 20rpx;display: flex;align-items: center;color: #111111;font-weight: bold;font-size: 30rpx}
.btitle .img {width: 34rpx;height: 34rpx;margin-right: 10rpx}
.bcontent {width: 100%;padding: 0 20rpx}
.product {width: 100%;border-bottom: 1px solid #f4f4f4}
.product .item {width: 100%;padding: 20rpx 0;background: #fff;}
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
.freight-ul {width: 100%;}
.freight-li {background: #F5F6F8;border-radius: 24rpx;color: #6C737F;font-size: 24rpx;line-height: 48rpx;padding: 0 28rpx;margin: 12rpx 10rpx 12rpx 0;display: inline-block;white-space: break-spaces;max-width: 610rpx;vertical-align: middle;}
.inputPrice {border: 1px solid #ddd; width: 200rpx; height: 40rpx; border-radius: 10rpx; padding: 0 4rpx;}

.price {width: 100%;padding: 20rpx 0;background: #fff;display: flex;align-items: center}
.price .f1 {color: #333}
.price .f2 {color: #111;font-weight: bold;text-align: right;flex: 1}
.price .f3 {width: 24rpx;height: 24rpx;}
.price .couponname{color:#9E9E9E;padding:4rpx 16rpx;font-weight:normal;border-radius:8rpx;font-size:24rpx;display:inline-block;margin:2rpx 0 2rpx 10rpx}
.price .tag{color:#fff;padding:4rpx 16rpx;font-weight:normal;border-radius:8rpx;font-size:24rpx; width: auto; display: inline-block; margin-top: 4rpx;}

.scoredk {width: 94%;margin: 0 3%;margin-bottom: 20rpx;border-radius: 20rpx;padding: 24rpx 20rpx;background: #fff;display: flex;align-items: center}
.scoredk .f1 {color: #333333}
.scoredk .f2 {color: #999999;text-align: right;flex: 1}
.remark {width: 100%;padding: 16rpx 0;background: #fff;display: flex;align-items: center}
.remark .f1 {color: #333;width: 200rpx}
.remark input {border: 0px solid #eee;height: 70rpx;padding-left: 10rpx;text-align: right}
.footer {width: 96%;background: #fff;margin-top: 5px;position: fixed;left: 0px;bottom: 0px;padding: 0 2%;display: flex;align-items: center;z-index: 8;box-sizing:content-box}
.footer .text1 {height: 110rpx;line-height: 110rpx;color: #2a2a2a;font-size: 30rpx;}
.footer .text1 text {color: #e94745;font-size: 32rpx;}
.footer .op {width: 200rpx;height: 80rpx;line-height: 80rpx;color: #fff;text-align: center;font-size: 30rpx;border-radius: 44rpx}
.footer .op[disabled] { background: #aaa !important; color: #666;}
.footerTop {bottom: 110rpx; display:inline-block;font-size:22rpx;height:44rpx;line-height:44rpx;padding:0 20rpx}
.storeitem {width: 100%;padding: 20rpx 0;display: flex;flex-direction: column;color: #333}
.storeitem .panel {width: 100%;height: 60rpx;line-height: 60rpx;font-size: 28rpx;color: #333;margin-bottom: 10rpx;display: flex}
.storeitem .panel .f1 {color: #333}
.storeitem .panel .f2 {color: #111;font-weight: bold;text-align: right;flex: 1}
.storeitem .radio-item {display: flex;width: 100%;color: #000;align-items: center;background: #fff;padding:20rpx 20rpx;border-bottom:1px dotted #f1f1f1}
.storeitem .radio-item:last-child {border: 0}
.storeitem .radio-item .f1 {color: #333;font-size:30rpx;flex: 1}
.storeitem .headimg image{ width: 100rpx; height:100rpx; border-radius:10rpx;margin-right: 20rpx;}


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
.form-item .label {color: #333;width: 200rpx;flex-shrink:0;white-space: nowrap;}
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
.form-item .input2{border:1px solid #eee;}
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

.storeviewmore{width:100%;text-align:center;color:#889;height:40rpx;line-height:40rpx;margin-top:10rpx}

.btn{ height:80rpx;line-height: 80rpx;width:90%;margin:0 auto;border-radius:40rpx;margin-top:40rpx;color: #fff;font-size: 28rpx;font-weight:bold}
.invoiceBox .radio radio{transform: scale(0.8);}
.invoiceBox .radio:nth-child(2) { margin-left: 30rpx;}
.glassinfo{color: #333; padding:10rpx; border-radius: 10rpx;display: flex;justify-content: space-between;align-items: center;background: #f4f4f4;margin-top: 10rpx;font-size: 30rpx;}
.glassinfo .f2{display: flex;justify-content: flex-end;}
.glassinfo .f2 image{width: 32rpx;height: 36rpx;padding-top: 4rpx;}
.glassinfo .f1{font-weight: bold;}

.glass_popup .popup__content{max-height: 920rpx;}
.glass_popup .gr-add{margin-top: 30rpx;}
.glass_popup .gr-add .gr-btn{width: 240rpx;color: #FFF;border-radius: 10rpx;}
.glass_popup .popup__title{padding: 30rpx 0 0 0;}
.glassitem{background:#f7f7f7;border-radius: 10rpx;width: 94%;margin: 20rpx 3%;padding: 20rpx 0;}
.glassitem .fc{display: flex;align-items: center;}
.glassitem .gremark{padding: 0 20rpx;padding-left: 100rpx;font-size: 24rpx;color: #707070;}
.glassitem.on{background: #ffe6c8;}
.glassitem .radio{width: 80rpx;flex-shrink: 0;text-align: center;}
.glassitem .gcontent{flex:1;padding: 0 20rpx;}
.glassitem .grow{line-height: 46rpx;color: #545454;font-size: 24rpx;}
.glassitem .gtitle{font-size: 24rpx;color: #222222;}
.glassitem .bt{border-top:1px solid #e3e3e3}
.glassitem .opt{width: 80rpx;font-size: 26rpx;border: 1rpx solid #c5c5c5;border-radius: 6rpx;height: 50rpx;line-height: 50rpx;text-align: center;margin-right: 16rpx;}
.pdl10{padding-left: 10rpx;}

.uni-popup-dialog {width: 300px;border-radius: 5px;background-color: #fff;}
.uni-dialog-title {/* #ifndef APP-NVUE */display: flex;/* #endif */flex-direction: row;justify-content: center;padding-top: 15px;padding-bottom: 5px;}
.uni-dialog-title-text {font-size: 16px;font-weight: 500;}
.uni-dialog-content {/* #ifndef APP-NVUE */display: flex;/* #endif */flex-direction: row;justify-content: center;align-items: center;padding: 5px 15px 15px 15px;}
.uni-dialog-content-text {font-size: 14px;color: #6e6e6e;}
.uni-dialog-button-group {/* #ifndef APP-NVUE */display: flex;/* #endif */flex-direction: row;border-top-color: #f5f5f5;border-top-style: solid;border-top-width: 1px;}
.uni-dialog-button {/* #ifndef APP-NVUE */display: flex;/* #endif */flex: 1;flex-direction: row;justify-content: center;align-items: center;height: 45px;/* #ifdef H5 */cursor: pointer;/* #endif */}
.uni-border-left {border-left-color: #f0f0f0;border-left-style: solid;border-left-width: 1px;}
.uni-dialog-button-text {font-size: 14px;}
.uni-button-color {color: #007aff;}

.storesearch{width:200rpx;line-height: 72rpx;text-align: center;background-color: #000;color: #fff;border-radius:0 10rpx 10rpx 0;}
.storeinput{height: 72rpx;line-height: 72rpx;padding-left: 20rpx;background-color: #f1f1f1;border-radius: 10rpx 0 0 10rpx;width:100%;}

.xycss1{line-height: 60rpx;font-size: 24rpx;overflow: hidden;width: 94%;margin: 0 3%;margin-bottom: 20rpx;border-radius: 20rpx;padding: 24rpx 20rpx;background: #fff;}
.xieyibox{width:100%;height:100%;position:fixed;top:0;left:0;z-index:99;background:rgba(0,0,0,0.7)}
.xieyibox-content{width:90%;margin:0 auto;/*  #ifdef  MP-TOUTIAO */height:60%;/*  #endif  *//*  #ifndef  MP-TOUTIAO */height:80%;/*  #endif  */margin-top:20%;background:#fff;color:#333;padding:5px 10px 50px 10px;position:relative;border-radius:2px;}
 .room-form{width: 85%;padding: 10rpx 0rpx;}
 .room-form input{width: 100%; font-size: 24rpx;}
.footersocre{bottom:176rpx;background-color: #FFEEEC;color:#FF2731;padding: 10rpx 20rpx 12rpx 20rpx;font-size: 24rpx}
/* 押金 */
.deposit{
	width: 100%;
	padding: 20rpx 0;
	background: #fff;
	align-items: center;
}
.deposit .t1{color: #111;font-weight: bold;}
.deposit .t2{font-size: 24rpx;color: #616161;}
.deposit .radio {flex-shrink: 0;width: 32rpx;height: 32rpx;background: #FFFFFF;border: 2rpx solid #BFBFBF;border-radius: 50%;}
.deposit .radio .radio-img {width: 100%;height: 100%}

.popup-content{width: 480rpx;height:480rpx;background: #fff;border-radius: 40rpx;overflow: hidden;padding: 30rpx}
.popup-content .deposit-popup-img{width: 180rpx;height: 180rpx;margin: 0 auto;}
.popup-content .deposit-popup-img image{width: 100%;height: 100%;}
.popup-content .deposit-title-view{font-size: 30rpx;font-weight: bold;color: #000;letter-spacing: 3rpx;text-align: center;margin-top: 30rpx;}
.popup-content .deposit-tips-view{font-size: 24rpx;color: #999;text-align: center;margin-top: 10rpx;}
.popup-content .deposit-popup-but{width: 100%;font-size: 28rpx;padding: 18rpx;text-align: center;border-radius: 40rpx;letter-spacing: 2rpx;font-weight: bold;color: #fff;margin-top: 30rpx;}
/* 上楼费 */
.title-icon{width: 28rpx;height: 28rpx;margin-left: 10rpx;}
.switch-view{flex: 1; justify-content: flex-end;}
.switch-view .switch-options{width: 120rpx;text-align: center;font-size: 28rpx;color: #999;padding: 6rpx 0rpx;border-radius: 10rpx;overflow: hidden;
	margin-left: 20rpx;border: 1px solid;}
	.arrowright {width: 26rpx;height: 26rpx;}
.xieyibox{width:100%;height:100%;position:fixed;top:0;left:0;z-index:99;background:rgba(0,0,0,0.7)}
.xieyibox-content{width:90%;margin:0 auto;height:80%;margin-top:20%;background:#fff;color:#333;padding:5px 10px 50px 10px;position:relative;border-radius:2px}
.xieyibox-content .xieyibut-view{height: 60rpx;position:absolute;z-index:9999;bottom:10px;left:0;right:0;margin:0 auto;justify-content: space-around;}
.xieyibox-content .xieyibut-view .but-class{text-align:center; width: auto;height: 60rpx; line-height: 60rpx; color: #fff; border-radius: 8rpx;padding:0rpx 25rpx;}
.but-view{width: 100%;background: #fff;position: fixed;bottom: 0rpx;padding: 20rpx;z-index: 2;box-shadow: 0rpx 0rpx 10rpx 5rpx #ebebeb;}
.select-view{width: 32rpx;height: 32rpx;border-radius: 50%;border: 1px solid #D9D9DA;margin-right: 20rpx;}
.select-view image{width: 20rpx;height: 20rpx;}
.read-agreement .t1{ font-weight: bold; font-size: 34rpx;}

.select-view-active{width: 32rpx;height: 32rpx;border-radius: 50%;border: 1px solid #06D470;margin-right: 20rpx;background: #06D470;
  display: flex;align-items: center;justify-content: center;}
.select-view-active image{width: 20rpx;height: 20rpx;}
.read-agreement{width: 100%;border-bottom: 1px #e6e6e6 solid;justify-content: flex-start;padding-bottom: 20rpx;padding-top:20rpx;color: #59595D;font-size: 24rpx;}
</style>
