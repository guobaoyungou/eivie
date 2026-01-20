<template>
	<view class="container">
		<block v-if="isload">
			<form @submit="topay">
        <view v-if="needaddress==0" class="address-add">
          <view class="linkitem">
            <label style="color: red;" v-if="contact_require==1 || is_pingce"> * </label>
            <text class="f1">联 系 人：</text>
            <input type="text" class="input" :value="linkman" placeholder="请输入您的姓名" @input="inputLinkman"
              placeholder-style="color:#626262;font-size:28rpx" />
          </view>
          <!-- 性别 -->
          <view class="linkitem" v-if="is_pingce">
            <label style="color: red;" v-if="contact_require==1 || is_pingce"> * </label>
            <text class="f1">性 别：
            </text>
            <picker class="input" mode="selector" @change="bindPickerChangeSex"  :range="sexlist">
              <view v-if="gender"> {{gender}}</view>
              <view v-else>请选择</view>
            </picker>
          </view>
          <!-- 联系电话 -->
           
          <view class="linkitem">
            <label style="color: red;" v-if="contact_require==1 || is_pingce"> * </label>
            <text class="f1">联系电话：</text>
            <input type="text" class="input" :value="tel" placeholder="请输入您的手机号" @input="inputTel"
              placeholder-style="color:#626262;font-size:28rpx" />
          </view>
          <view v-if="worknum_status" class="linkitem" >
            <text class="f1">工号：</text>
            <input type="number" class="input" :value="worknum" :placeholder="worknumtip" @input="inputWorknum"
            placeholder-style="color:#626262;font-size:28rpx" />
          </view>
          <view v-if="is_pingce" class="linkitem" >
            <label style="color: red;" v-if="contact_require==1 || is_pingce"> * </label>
            <text class="f1">邮箱：</text>
            <input type="text" class="input" :value="pcemail" placeholder="请输入您的邮箱" @input="inputEmail"
            placeholder-style="color:#626262;font-size:28rpx" />
          </view>
          <view class="linkitem" v-if="is_pingce">
            <label style="color: red;" v-if="is_pingce"> * </label>

            <text class="f1">生日：</text>
              <picker class="input" mode="date"  style="padding-left: 10px;width: 90%;" :end="endDate"  @change="editorBindPickerChangeAge">
                <view v-if="age"> {{age}}</view>
                <view v-else>请选择</view>
              </picker>
              <text class="iconfont iconjiantou" style="color:#999;font-weight:normal"></text>
            </view>
          <view v-if="is_pingce" class="linkitem" >
            <label style="color: red;" v-if="is_pingce"> * </label>

            <text class="f1" >学校:</text>
            <input type="text" class="input"  placeholder="请输入您的学校" @input="inputSchool"
            placeholder-style="color:#626262;font-size:28rpx" />
          </view>
          <view v-if="is_pingce" class="linkitem" >
            <label style="color: red;" v-if="is_pingce"> * </label>
            <text class="f1" >专业：</text>
            <input type="text" class="input"   placeholder="请输入您的专业" @input="inputMajor"
            placeholder-style="color:#626262;font-size:28rpx" />
          </view>
          <view v-if="is_pingce" class="linkitem" >
            <label style="color: red;" v-if="is_pingce"> * </label>
            <text class="f1" >学历：</text>
            <input type="text" class="input"  placeholder="请输入您的学历" @input="inputEduction"
            placeholder-style="color:#626262;font-size:28rpx" />
          </view>
          <view class="linkitem" v-if="is_pingce">
            <label style="color: red;" v-if="is_pingce"> * </label>
            <text class="f1">入学年份</text>
              <picker class="input" mode="date" fields="year" style="padding-left: 10px;width: 90%;"  @change="editorBindPickerChangeEnrol">
                <view v-if="enrol">{{ enrol }}</view>								 
                <view v-else>请选择</view>
              </picker>
              <text class="iconfont iconjiantou" style="color:#999;font-weight:normal"></text>
            </view>
          <view v-if="is_pingce" class="linkitem" >
            <text class="f1" >班级：</text>
            <input type="text" class="input"  placeholder="请输入您的班级" @input="inputClassName"
            placeholder-style="color:#626262;font-size:28rpx" />
          </view>
           
        </view>
        <view v-else class="address-add flex-y-center">
          <view class="flex-y-center " @tap="goto" style="flex: 1;"
          :data-url="'/pagesB/address/'+(address.id ? 'address' : 'addressadd')+'?fromPage=buy&source=shop&type=' + (havetongcheng==1?'1':'0')">
            <image style="width: 66rpx;height: 66rpx;margin-right: 20rpx;" class="img" :src="pre_url+'/static/img/address.png'" />
          <view class="f2" v-if="address.id" style="flex: 1;">
            <view style="font-weight:bold;color:#111111;font-size:30rpx">{{address.name}} {{address.tel}} <text v-if="address.company">{{address.company}}</text></view>
            <view style="font-size:24rpx">{{address.area}} {{address.address}}</view>
          </view>
          <view v-else class="f2 flex1">请选择收货地址</view>
          <image :src="pre_url+'/static/img/arrowright.png'"  class="f3"></image>
          </view>
          <view v-if="worknum_status" class="linkitem" style="margin: 20rpx;line-height:50rpx ;">
            <text class="f1" style="width: 90rpx;margin-right: 0;">工号：</text>
            <input type="text" class="input" :value="worknum" :placeholder="worknumtip" @input="inputWorknum"
            placeholder-style="color:#626262;font-size:28rpx" />
          </view>
          <view v-if="needusercard" class="linkitem" style="margin: 20rpx;line-height:50rpx ;">
            <text class="f1" style="width: 140rpx;margin-right: 0;">身份证号：</text>
            <input type="text" class="input" :value="usercard" :placeholder="usercardtip" @input="inputWorknum"
            placeholder-style="color:#626262;font-size:28rpx" />
          </view>
        </view>

				<view v-for="(buydata, index) in allbuydata" :key="index" class="buydata">
					<view class="btitle">
						<image class="img" :src="pre_url+'/static/img/ico-shop.png'" />{{buydata.business.name}}
					</view>
					<view class="bcontent">
						<view class="product">
							<view v-for="(item, index2) in buydata.prodata" :key="index2">
								<view class="item flex">
									<view class="img" @tap="goto" :data-url="'/pages/shop/product?id=' + item.product.id">
										<image v-if="item.guige.pic" :src="item.guige.pic"></image>
										<image v-else-if="item.guige.ggpic_wholesale" :src="item.guige.ggpic_wholesale"></image>
										<image v-else :src="item.product.pic"></image>
									</view>
									<view class="info flex1">
										<view class="f1">{{item.product.name}}</view>
										<view class="f2">规格：{{item.guige.gg_group_title ? item.guige.gg_group_title:''}} {{item.guige.name}}</view>
										<view class="f3">
											<text style="padding-left:20rpx"> × {{item.num}}</text>
										</view>
									</view>
								</view> 
								
								<view class="glassinfo" v-if="item.product.product_type==1" @tap="showglass" :data-index="index" :data-index2="index2" :data-grid="item.product.has_glassrecord==1?item.product.glassrecord.id:0" :style="'background:rgba('+t('color1rgb')+',0.8);color:#FFF'">
									<view class="f1">
										视力档案
									</view>
									<view class="f2">
										<text>{{item.product.has_glassrecord==1?item.product.glassrecord.name:''}}</text>
										<image :src="pre_url+'/static/img/arrowright.png'" >
									</view>
								</view>
							</view>
						</view>
            
            <view class="freight">
              <view class="f1">配送方式</view>
              <view class="freight-ul">
                <view style="width:100%;overflow-y:hidden;overflow-x:scroll;white-space: nowrap;">
                  <block v-for="(item, idx2) in buydata.freightList" :key="idx2">
                    <view class="freight-li" :style="buydata.freightkey==idx2?'color:'+t('color1')+';background:rgba('+t('color1rgb')+',0.2)':''"
                      @tap="changeFreight" :data-keyid="index" :data-index="idx2">{{item.name}}
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
              <view class="f2" @tap="choosePstime" :data-keyid="index">
                {{buydata.pstimetext==''?'请选择时间':buydata.pstimetext}}<text class="iconfont iconjiantou"
                  style="color:#999;font-weight:normal"></text>
              </view>
            </view>

            <view class="storeitem" v-if="buydata.freightList[buydata.freightkey].pstype==1 && buydata.freightList[buydata.freightkey].isbusiness==1">
              <view class="panel">
                <view class="f1">取货地点</view>
              </view>
              <block v-for="(item, idx) in buydata.freightList[buydata.freightkey].storedata" :key="idx">
                <view class="radio-item" v-if="idx<5 || storeshowall==true" @tap="openLocation" :data-freightkey="buydata.freightkey" :data-storekey="idx" :data-keyid="index" :data-index="idx">
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
                      <view class="f2" @tap="openMendian" :data-keyid="index" v-if="buydata.freightList[buydata.freightkey].storedata.length > 0" 
                      :data-freightkey="buydata.freightkey"
                      :data-storekey="buydata.freightList[buydata.freightkey].storekey"><text
                        class="iconfont icondingwei"></text>
                        {{buydata.freightList[buydata.freightkey].storedata[buydata.freightList[buydata.freightkey].storekey].name}}
                      </view>
                      <view class="f2" v-else>暂无</view>
                  </view>
                </block>
                <block v-else>
                  <view class="panel">
                    <view class="f1">可使用门店</view>
                  </view>
                </block>
                <!-- 门店升级 社区团购 -->
                <block v-if="mendian_upgrade">		
                  <block v-for="(item, idx) in buydata.freightList[buydata.freightkey].storedata" :key="idx">
                    <view class="radio-item"  style="border:10rpx solid #f6f6f6;border-radius:10rpx;padding:10rpx" @tap="goto" data-url="/pagesB/mendianup/list">
                      <view class="headimg">
                        <image :src="item.headimg">
                      </view>
                      <view class="f1" style="display: flex;justify-content: space-between;align-items: center;">
                        <view>
                        <view>{{item.xqname}}</view>
                        <view v-if="item.address" class="flex-y-center" style="text-align:left;font-size:24rpx;color:#aaaaae;display: -webkit-box;-webkit-box-orient: vertical;-webkit-line-clamp:1;overflow: hidden;">领取地址：{{item.address}}</view>
                        </view>
                        <view><text class="iconfont iconjiantou" style="color:#999;font-weight:normal"></text></view>
                      </view>
                    </view>
                  </block>
                </block>
                <!--甘尔定制 不用选择门店，可在任意门店核销-->
                <block v-else-if="mendian_no_select">
                  <block v-for="(item, idx) in buydata.freightList[buydata.freightkey].storedata" :key="idx">
                    <view class="radio-item" :data-keyid="index" :data-index="idx" v-if="idx<5 || storeshowall==true">
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
                    <view class="radio-item" @tap.stop="choosestore" :data-keyid="index" :data-index="idx" v-if="idx<5 || storeshowall==true">
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
                 <view class="f2" @tap="openMendian" :data-keyid="index" v-if="buydata.freightList[buydata.freightkey].storedata.length > 0"
                   :data-freightkey="buydata.freightkey"
                   :data-storekey="buydata.freightList[buydata.freightkey].storekey"><text class="iconfont icondingwei"></text>{{buydata.freightList[buydata.freightkey].storedata[buydata.freightList[buydata.freightkey].storekey].name}}
                  </view>
                  <view class="f2" v-else>暂无</view>
                </view>
                <block v-for="(item, idx) in buydata.freightList[buydata.freightkey].storedata" :key="idx">
                  <view class="radio-item" @tap.stop="choosestore" :data-keyid="index" :data-index="idx" v-if="idx<5 || storeshowall==true">
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
                  <view class="f2" @tap="openMendian" :data-keyid="index" v-if="buydata.freightList[buydata.freightkey].storedata.length > 0"
                    :data-freightkey="buydata.freightkey"
                    :data-storekey="buydata.freightList[buydata.freightkey].storekey"><text class="iconfont icondingwei"></text>{{buydata.freightList[buydata.freightkey].storedata[buydata.freightList[buydata.freightkey].storekey].name}}
                  </view>
                  <view class="f2" v-else>暂无</view>
                </view>
                <block v-if="mendian_upgrade">
                  <!-- 门店配送 社区团购 -->
                  <block v-for="(item, idx) in buydata.freightList[buydata.freightkey].storedata" :key="idx">
                    <view class="radio-item"  style="border:10rpx solid #f6f6f6;border-radius:10rpx;padding:10rpx" @tap="goto" data-url="/pagesB/mendianup/list">
                      <view class="headimg">
                        <image :src="item.headimg">
                      </view>
                      <view class="f1" style="display: flex;justify-content: space-between;align-items: center;">
                        <view>
                        <view>{{item.xqname}}</view>
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
                    <view class="radio-item" @tap.stop="choosestore" :data-keyid="index" :data-index="idx" v-if="idx<5 || storeshowall==true">
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
            <view v-if="mendianShowType">
              <view class="storeitem" v-if="(buydata.freightList[buydata.freightkey].pstype==1 && buydata.freightList[buydata.freightkey].isbusiness!=1) || buydata.freightList[buydata.freightkey].pstype==5">
                <view class="panel">
                  <view v-if="buydata.freightList[buydata.freightkey].pstype==1" class="f1">取货地点</view>
                  <view v-if="buydata.freightList[buydata.freightkey].pstype==5" class="f1">配送门店</view>
                  <view class="f2" v-if="buydata.freightList[buydata.freightkey].storedata.length > 0" 
                  @tap="showstore" :data-storedata="buydata.freightList[buydata.freightkey].storedata" :data-storefreightkey="buydata.freightkey" :data-storekey="buydata.freightList[buydata.freightkey].storekey" :data-keyid="index"  :data-storekeyid="index">
                      <text>{{buydata.freightList[buydata.freightkey].storedata[buydata.freightList[buydata.freightkey].storekey].name}}</text>
                      <text class="iconfont iconjiantou" style="color:#999;font-weight:normal"></text>
                  </view>
                  <view class="f2" v-else>暂无</view>
                </view>
              </view>
            </view>

            <view class="price" v-if="buydata.freightList[buydata.freightkey].pstype==11">
              <view class="f1">选择物流</view>
              <view class="f2" @tap="showType11List" :data-keyid="index">
                <text>{{buydata.type11key?buydata.freightList[buydata.freightkey].type11pricedata[buydata.type11key-1].name:'请选择'}}</text><text
                  class="iconfont iconjiantou" style="color:#999;font-weight:normal"></text>
              </view>
            </view>
            
            <view style="display:none">{{test}}</view>
						<view class="form-item" v-for="(item,idx) in buydata.freightList[buydata.freightkey].formdata" :key="idx">
							<view class="label">{{item.val1}}<text v-if="item.val3==1" style="color:red"> *</text></view>
							<block v-if="item.key=='input'">
								<input type="text" :name="'form'+index+'_'+idx" class="input" :placeholder="item.val2" placeholder-style="font-size:28rpx"/>
							</block>
							<block v-if="item.key=='textarea'">
								<textarea :name="'form'+index+'_'+idx" class='textarea' :placeholder="item.val2" placeholder-style="font-size:28rpx"/>
							</block>
							<block v-if="item.key=='radio'">
								<radio-group class="radio-group" :name="'form'+index+'_'+idx">
									<label v-for="(item1,idx1) in item.val2" :key="item1.id" class="flex-y-center">
										<radio class="radio" :value="item1"/>{{item1}}
									</label>
								</radio-group>
							</block>
							<block v-if="item.key=='checkbox'">
								<checkbox-group :name="'form'+index+'_'+idx" class="checkbox-group">
									<label v-for="(item1,idx1) in item.val2" :key="item1.id" class="flex-y-center">
										<checkbox class="checkbox" :value="item1"/>{{item1}}
									</label>
								</checkbox-group>
							</block>
							<block v-if="item.key=='selector'">
								<view class="flex-x-bottom flex-y-center">
									<picker class="picker" mode="selector" :name="'form'+index+'_'+idx" :value="(buydata.editorFormdata[idx] || buydata.editorFormdata[idx]===0)?buydata.editorFormdata[idx]:''" :range="item.val2" @change="editorBindPickerChange" :data-keyid="index" :data-idx="idx">
										<view v-if="buydata.editorFormdata[idx] || buydata.editorFormdata[idx]===0"> {{item.val2[buydata.editorFormdata[idx]]}}</view>
										<view v-else>请选择</view>
									</picker>
									<text class="iconfont iconjiantou" style="color:#999;font-weight:normal"></text>
								</view>
							</block>
							<block v-if="item.key=='time'">
								<view class="flex-x-bottom flex-y-center">
									<picker class="picker" mode="time" :name="'form'+index+'_'+idx" :value="(buydata.editorFormdata[idx] || buydata.editorFormdata[idx]===0)?buydata.editorFormdata[idx]:''" :start="item.val2[0]" :end="item.val2[1]" :range="item.val2" @change="editorBindPickerChange" :data-keyid="index" :data-idx="idx">
										<view v-if="buydata.editorFormdata[idx]">{{buydata.editorFormdata[idx]}}</view>
										<view v-else>请选择</view>
									</picker>
									<text class="iconfont iconjiantou" style="color:#999;font-weight:normal"></text>
								</view>
							</block>
							<block v-if="item.key=='date'">
								<view class="flex-x-bottom flex-y-center">
									<picker class="picker" mode="date" :name="'form'+index+'_'+idx" :value="(buydata.editorFormdata[idx] || buydata.editorFormdata[idx]===0)?buydata.editorFormdata[idx]:''" :start="item.val2[0]" :end="item.val2[1]" :range="item.val2" @change="editorBindPickerChange" :data-keyid="index" :data-idx="idx">
										<view v-if="buydata.editorFormdata[idx]">{{buydata.editorFormdata[idx]}}</view>
										<view v-else>请选择</view>
									</picker>
									<text class="iconfont iconjiantou" style="color:#999;font-weight:normal"></text>
								</view>
							</block>
							<block v-if="item.key=='upload'">
								<input type="text" style="display:none" :name="'form'+index+'_'+idx" :value="buydata.editorFormdata[idx]"/>
								<view class="flex" style="flex-wrap:wrap;padding-top:20rpx">
									<view class="form-imgbox" v-if="buydata.editorFormdata[idx]">
										<view class="layui-imgbox-close" style="z-index: 2;" @tap="removeimg" :data-keyid="index" :data-idx="idx" data-type="pic"><image style="display:block" :src="pre_url+'/static/img/ico-del.png'"></image></view>
										<view class="form-imgbox-img"><image class="image" :src="buydata.editorFormdata[idx]" @click="previewImage" :data-url="buydata.editorFormdata[idx]" mode="aspectFit"/></view>
									</view>
									<view v-else class="form-uploadbtn" :style="{background:'url('+pre_url+'/static/img/shaitu_icon.png) no-repeat 50rpx',backgroundSize:'80rpx 80rpx',backgroundColor:'#F3F3F3'}" @click="editorChooseImage" :data-keyid="index" :data-idx="idx" data-type="pic"></view>
								</view>
							</block>
              <block v-if="item.key=='upload_pics'">
                <input type="text" style="display:none" :name="'form'+index+'_'+idx" :value="buydata.editorFormdata && buydata.editorFormdata[idx]?buydata.editorFormdata[idx].join(','):''" maxlength="-1"/>
                <view class="flex" style="flex-wrap:wrap;padding-top:20rpx">
                  <view v-for="(item2, index2) in buydata.editorFormdata[idx]" :key="index2" class="form-imgbox" >
                    <view class="layui-imgbox-close" @tap="removeimg" :data-keyid="index" :data-index="index2" data-type="pics" :data-idx="idx" :data-formidx="'form'+idx"><image :src="pre_url+'/static/img/ico-del.png'" class="image"></image></view>
                    <view class="form-imgbox-img" style="margin-bottom: 10rpx;"><image class="image" :src="item2" @click="previewImage" :data-url="item2" mode="aspectFit" :data-idx="idx"/></view>
                  </view>
                  <view class="form-uploadbtn" :style="{background:'url('+pre_url+'/static/img/shaitu_icon.png) no-repeat 50rpx',backgroundSize:'80rpx 80rpx',backgroundColor:'#F3F3F3'}" @click="editorChooseImages" :data-keyid="index" :data-idx="idx" :data-formidx="'form'+idx" data-type="pics"></view>
                </view>
              </block>
						</view>
					</view>
				</view>

				<view style="width: 100%; height:182rpx;"></view>
				<view class="footer flex notabbarbot">
					<button class="op" form-type="submit" :style="{background:'linear-gradient(-90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'}" :disabled="submitDisabled">
						提交
          </button>
				</view>
      </form>

			<view v-if="pstimeDialogShow" class="popup__container">
				<view class="popup__overlay" @tap.stop="hidePstimeDialog"></view>
				<view class="popup__modal">
					<view class="popup__title">
						<text
							class="popup__title-text">请选择{{allbuydata[nowkeyid].freightList[allbuydata[nowkeyid].freightkey].pstype==1?'取货':'配送'}}时间</text>
						<image :src="pre_url+'/static/img/close.png'" class="popup__close" style="width:36rpx;height:36rpx"
							@tap.stop="hidePstimeDialog" />
					</view>
					<view class="popup__content">
						<view class="pstime-item"
							v-for="(item, index) in allbuydata[nowkeyid].freightList[allbuydata[nowkeyid].freightkey].pstimeArr"
							:key="index" @tap="pstimeRadioChange" :data-index="index">
							<view class="flex1">{{item.title}}</view>
							<view class="radio"
								:style="allbuydata[nowkeyid].freight_time==item.value ? 'background:'+t('color1')+';border:0' : ''">
								<image class="radio-img" :src="pre_url+'/static/img/checkd.png'" />
							</view>
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
								v-for="(item, index) in allbuydata[keyid].freightList[allbuydata[keyid].freightkey].type11pricedata"
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
			
			<uni-popup id="dialogbusinessinfo" ref="dialogbusinessinfo" type="dialog">
				<view class="uni-popup-dialog">
					<view class="uni-dialog-title">
						<text class="uni-dialog-title-text">请再次确认下单门店</text>
					</view>
					<view class="uni-dialog-content">
						<view style="width:100%;text-align:left">
							<view style="font-weight:bold">{{allbuydata[keyid].business.name}}</view>
							<view style="font-size:24rpx;color:#888;padding:4rpx 0">{{allbuydata[keyid].business.address}}</view>
							<dp-map :params="{bgcolor:'#fff',margin_y:0,margin_x:0,padding_x:0,padding_y:0,height:'150',latitude:allbuydata[keyid].business.latitude,longitude:allbuydata[keyid].business.longitude,address:allbuydata[keyid].business.name}"></dp-map> 
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
                    <view class="f1" @tap.stop="openMendian" :data-keyid="storekeyid"  :data-freightkey="storefreightkey" :data-storekey="idx">
                      <view>
                        <text class="iconfont icondingwei"></text>
                      {{item.name}}
                      </view>
                      <view v-if="item.address" style="text-align:left;font-size:24rpx;color:#aaaaae;display: -webkit-box;-webkit-box-orient: vertical;-webkit-line-clamp:1;overflow: hidden;">
                      {{item.address}}
                      </view>
                    </view>
                    <view @tap.stop="choosestore" :data-keyid="storekeyid" :data-index="idx" style="display: flex;">
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

				pre_url:app.globalData.pre_url,
				test:'test',
				havetongcheng: 0,
				address: [],
				memberList: [],
				
				memberinfovisible:false,
				business_payconfirm:false,
				businessinfoConfirm:false,
				topayparams:{},
				selectmemberinfo:{},
        
        
        keyid: 0,
        nowkeyid: 0,
				needaddress: 1,
				linkman: '',
				tel: '',
				userinfo: {},
				pstimeDialogShow: false,
				pstimeIndex: -1,

				latitude: "",
				longitude: "",
				allbuydata: {},
				allbuydatawww: {},

				type11visible: false,
				type11key: -1,
				regiondata: '',
				items: [],
				editorFormdata:[],

				storeshowall:false,

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
				usdrate:0,
				hasglassproduct:0,
				business_selfscore:0,
				scoredkdataArr:{},
                
        moneydec:false,
        shop_num:0,//bid不同类型
        shop_bid:0,//最后一个bid

        worknum_status:false,
        worknum:'',
        worknumtip:'请输入您的工号',
        mendianShowType:0,// 商城购买页门店展示方式，1单条展示，点击弹窗展示列表可搜索
        storevisible:false,
        storedata:[],
        storefreightkey:0,
        storekey:0,
        
        storekeyid:0,
        storename:'',
				showweight:false,
				mendian_id:0,
				custom:[],
        
        freightkey_keyid:'',
				freightkey_index:'',
        
        ishand:0,
        hwset:'',
        showxieyi:false,
        isagree:false,
				fenqiData:[],

				mendian_upgrade:false,
				mendian_no_select:0,
				memberOtherShow:false,//选择会员
				teamMemberList:[],//选择团队会员

				teamMember:{},//选择的会员信息
				tmid:'',
				contact_require:0,
				ismultiselect:false,
				show_product_xieyi:0,
				isagree_pro:false,
				//来源直播间

        mustuseaddress:false,//必须使用地址
        needusercard:false,
        usercard:'',
        usercardtip:'请输入您的身份证号',
        xdjf: 0,

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
				sexlist:['男','女'],

			};
		},

		onLoad: function(opt) {
			this.opt = app.getopts(opt);
			var locationCache =  app.getLocationCache();
			if(locationCache.mendian_id){
				this.mendian_id = locationCache.mendian_id
			}

      this.payorderid = this.opt.payorderid || 0;
			this.getdata();
		},
		onShow:function(e){
			if(this.hasglassproduct==1){
				this.getglassrecord()
			}
			if(this.mendian_upgrade){
					var locationCache =  app.getLocationCache();
					if(locationCache.mendian_id){
						this.mendian_id = locationCache.mendian_id
					}
					this.getdata();
			}
		},
		onPullDownRefresh: function() {
			this.getdata();
		},
		methods: {
			getdata: function() {
				var that = this;
				that.loading = true;
				var jldata = that.opt.jldata?JSON.parse( that.opt.jldata):[];
				app.get('ApiOrder/takegiveorder', {
					payorderid:that.payorderid
				}, function(res) {
					that.loading = false;
					if (res.status != 1) {
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

					that.pstype3needAddress = res.pstype3needAddress;
					that.contact_require = res.contact_require;
					
					that.allbuydata = res.allbuydata;
					if(that.freightkey_keyid !== '') that.allbuydata[that.freightkey_keyid].freightkey = that.freightkey_index;

					that.business_payconfirm = res.business_payconfirm || false;
					that.allbuydatawww = JSON.parse(JSON.stringify(res.allbuydata));
					that.needLocation = res.needLocation;

					that.usdrate = res.usdrate
					that.hasglassproduct = res.hasglassproduct
	
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

					that.custom = res.custom;
					that.ismultiselect = res.ismultiselect

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
					that.loaded();
          that.calculatePrice();
					
					that.mendian_upgrade = res.mendian_upgrade
					that.mendian_no_select = res.mendian_no_select
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
											storedata.sort(function(a, b) {
												return a["juli"] - b["juli"];
											});
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
			inputfield: function(e) {
				var keyid = e.currentTarget.dataset.keyid;
				var field = e.currentTarget.dataset.field;
				allbuydata2[keyid][field] = e.detail.value;
				this.allbuydata2 = allbuydata2;
			},
			//选择收货地址
			chooseAddress: function() {
				app.goto('/pagesB/address/address?fromPage=buy&type=' + (this.havetongcheng == 1 ? '1' : '0'));
			},
			changeFreight: function(e) {
				var that = this;
				var allbuydata = that.allbuydata;
				var keyid = e.currentTarget.dataset.keyid;
				var index = e.currentTarget.dataset.index;
				var freightList = allbuydata[keyid].freightList;
				if(freightList[index].pstype==1 && freightList[index].storedata.length < 1) {
					app.error('无可自提门店');return;
				}
				if(freightList[index].pstype==5 && freightList[index].storedata.length < 1) {
					app.error('无可配送门店');return;
				}
				allbuydata[keyid].freightkey = index;
				that.freightkey_keyid = keyid;
				that.freightkey_index = index;
				that.allbuydata = allbuydata;
        that.calculatePrice();
				that.allbuydata[keyid].editorFormdata = [];
			},
			chooseFreight: function(e) {
				var that = this;
				var allbuydata = that.allbuydata;
				var keyid = e.currentTarget.dataset.keyid;
				var freightList = allbuydata[keyid].freightList;
				var itemlist = [];

				for (var i = 0; i < freightList.length; i++) {
					itemlist.push(freightList[i].name);
				}
				uni.showActionSheet({
					itemList: itemlist,
					success: function(res) {
						if (res.tapIndex >= 0) {
							allbuydata[keyid].freightkey = res.tapIndex;
							that.allbuydata = allbuydata;
              that.calculatePrice();
						}
					}
				});
			},
      //计算价格
      calculatePrice: function() {
      	var that = this;
      	var address = that.address;
      	var allbuydata = that.allbuydata;
      	var needaddress = 0;
        var worknum_status = false;

      	for (var k in allbuydata) {
          var freightdata = allbuydata[k].freightList[allbuydata[k].freightkey];;
          if (freightdata.pstype != 1 && freightdata.pstype != 3 && freightdata.pstype != 4) {
            needaddress = 1;
          }
          if(freightdata.pstype == 1 && freightdata.select_address_status == 1){
            needaddress = 1;
          }

          if(freightdata.worknum_status){
            worknum_status = true;
          }
      		if(that.pstype3needAddress && (freightdata.pstype == 3 || freightdata.pstype == 4 || freightdata.pstype == 5)) {
      			needaddress = 1;
      		}
      	}
      	that.needaddress    = that.mustuseaddress?1:needaddress;
      	that.worknum_status = worknum_status;
        //that.allbuydata = allbuydata;
      },
			choosePstime: function(e) {
				var that = this;
				var allbuydata = that.allbuydata;
				var keyid = e.currentTarget.dataset.keyid;
				var freightkey = allbuydata[keyid].freightkey;
				var freightList = allbuydata[keyid].freightList;
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
				that.nowkeyid = keyid;
				that.pstimeDialogShow = true;
				that.pstimeIndex = -1;
			},
			pstimeRadioChange: function(e) {
				var that = this;
				var allbuydata = that.allbuydata;
				var pstimeIndex = e.currentTarget.dataset.index;
			 
				// console.log(pstimeIndex)
				var nowkeyid = that.nowkeyid;
				var freightkey = allbuydata[nowkeyid].freightkey;
				var freightList = allbuydata[nowkeyid].freightList;
				var freight = freightList[freightkey];
				var pstimeArr = freightList[freightkey].pstimeArr;
				var choosepstime = pstimeArr[pstimeIndex];
				allbuydata[nowkeyid].pstimetext = choosepstime.title;
				allbuydata[nowkeyid].freight_time = choosepstime.value;
				that.allbuydata = allbuydata
				that.pstimeDialogShow = false;
			},
			hidePstimeDialog: function() {
				this.pstimeDialogShow = false;
			},
			choosestore: function(e) {
        var that = this
				var keyid    = e.currentTarget.dataset.keyid;
				var storekey   = e.currentTarget.dataset.index;
        that.storekey  = storekey;
				var allbuydata = that.allbuydata;
				var buydata    = allbuydata[keyid];
				var freightkey = buydata.freightkey
				allbuydata[keyid].freightList[freightkey].storekey = storekey
				that.allbuydata = allbuydata;
        that.closestore();
			},
			//提交并支付
			topay: function(e) {
			
				var that = this;
				var needaddress = that.needaddress;
				var addressid = this.address && this.address.id?this.address.id:0;

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

				var frompage = that.opt.frompage ? that.opt.frompage : '';
				var allbuydata = that.allbuydata;

        if (needaddress == 0) addressid = 0;
        if (needaddress == 1 && addressid == undefined) {
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

				var buydata = [];
				for (var i in allbuydata) {
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

          var formdata_fields = allbuydata[i].freightList[freightkey].formdata;
          var formdata = e.detail.value;
          var newformdata = {};
          var editorFormdata = allbuydata[i].editorFormdata;
          for (var j = 0; j < formdata_fields.length;j++){
            var thisfield = 'form'+i + '_' + j;
            if (formdata_fields[j].val3 == 1 && (formdata[thisfield] === '' || formdata[thisfield] === undefined || (formdata[thisfield] == null || formdata[thisfield].length==0))){
                app.alert(formdata_fields[j].val1+' 必填');return;
            }
            if (formdata_fields[j].key == 'selector') {
              if(formdata_fields[j].val3 == 1 && (Number.isNaN(formdata[thisfield]) || editorFormdata[j]==='' || editorFormdata[j]=='null' || editorFormdata[j]==undefined)){
                app.alert(formdata_fields[j].val1+' 必选');return;
              }
              formdata[thisfield] = formdata_fields[j].val2[editorFormdata[j]]
            }
            if(j > 0 && formdata_fields[j].val1 == '确认账号' && formdata_fields[j-1].val1 == '充值账号' && formdata[thisfield] != formdata['form'+i + '_' + (j-1)]){
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
          var freight_id  = allbuydata[i].freightList[freightkey].id;
          var freight_time= allbuydata[i].freight_time;

					var buydatatemp = {
            keyid:i,
            orderid: allbuydata[i].orderid,
            bid: allbuydata[i].bid,
						freight_id: freight_id,
						freight_time: freight_time,
						storeid: storeid,
						formdata:newformdata,
						type11key: type11key,
					};
					buydata.push(buydatatemp);
				}

				if(that.business_payconfirm && !that.businessinfoConfirm){
					that.$refs.dialogbusinessinfo.open();
					that.topayparams = e;
					return;
				}

				app.showLoading('提交中');
				app.post('ApiOrder/takegiveorder', {
          payorderid:that.payorderid,
					frompage: frompage,
					buydata: buydata,
					addressid: addressid,
					linkman: linkman,
					pcemail: pcemail,
					tel: tel,
					latitude:that.latitude,
					longitude:that.longitude,
					worknum:that.worknum,
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
				}, function(res) {
					app.showLoading(false);
					if (res.status == 1) {
              app.success(res.msg);
              setTimeout(function(){
                app.goback();
              },900)
					}else{
            app.error(res.msg);
            return;
          }
				});
			},
			changeNameType: function(e) {
				var that = this;
				var value = e.detail.value;
				that.name_type_select = value;
			},
			handleClickMask: function() {
				this.type11visible = false;
			},
			showType11List: function(e) {
				this.type11visible = true;
        this.keyid = e.currentTarget.dataset.keyid;
			},
			changetype11: function(e) {
				var that = this;
				var allbuydata = that.allbuydata;
				that.type11key = e.currentTarget.dataset.index;
			},
			chooseType11: function(e) {
				var that = this;
				var allbuydata = that.allbuydata;
				var keyid = that.keyid;
				var type11key = that.type11key;
				if (type11key == -1) {
					app.error('请选择物流');
					return;
				}
				allbuydata[keyid].type11key = type11key + 1;
				var freightkey = allbuydata[keyid].freightkey;
				var freightList = allbuydata[keyid].freightList;
				this.allbuydata = allbuydata;
				this.type11visible = false;
			},
			openMendian: function(e) {
				var allbuydata = this.allbuydata
				var keyid = e.currentTarget.dataset.keyid;
				var freightkey = e.currentTarget.dataset.freightkey;
				var storekey = e.currentTarget.dataset.storekey;
				var frightinfo = allbuydata[keyid].freightList[freightkey]
				var storeinfo = frightinfo.storedata[storekey];
				app.goto('mendian?id=' + storeinfo.id);
			},
			openLocation: function(e) {
				var allbuydata = this.allbuydata
				var keyid = e.currentTarget.dataset.keyid;
				var freightkey = e.currentTarget.dataset.freightkey;
				var storekey = e.currentTarget.dataset.storekey;
				var frightinfo = allbuydata[keyid].freightList[freightkey]
				var storeinfo = frightinfo.storedata[storekey];
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
        var keyid = e.currentTarget.dataset.keyid;
        var editorFormdata = that.allbuydata[keyid].editorFormdata;;
        if(!editorFormdata) editorFormdata = [];
        var type = e.currentTarget.dataset.type;
        app.chooseImage(function(data){
          editorFormdata[idx] = data[0];
          that.editorFormdata = editorFormdata
          that.allbuydata[keyid].editorFormdata = editorFormdata;
          that.test = Math.random();
        })
      },
      //多图上传，一次最多选8个
      editorChooseImages: function (e) {
        var that = this;
        var idx = e.currentTarget.dataset.idx;
        var keyid = e.currentTarget.dataset.keyid;
        var editorFormdata = that.allbuydata[keyid].editorFormdata;;
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
          that.allbuydata[keyid].editorFormdata = editorFormdata
          that.test = Math.random();
        },8)
      },
      removeimg:function(e){
        var that = this;
        var idx = e.currentTarget.dataset.idx;
        var keyid = e.currentTarget.dataset.keyid;
        var editorFormdata = this.editorFormdata;
        if(!editorFormdata) editorFormdata = [];
        var type  = e.currentTarget.dataset.type;
        var index = e.currentTarget.dataset.index;
        if(type == 'pics'){
          var pics = editorFormdata[idx]
          pics.splice(index,1);
          editorFormdata[idx] = pics;
          that.allbuydata[keyid].editorFormdata = editorFormdata
          that.test = Math.random();
        }else {
          editorFormdata[idx] = '';
          that.editorFormdata = editorFormdata
          that.test = Math.random();
          that.allbuydata[keyid].editorFormdata = that.editorFormdata;
        }
      },
			editorBindPickerChange:function(e){
				var that = this;
				var keyid = e.currentTarget.dataset.keyid;
				var idx = e.currentTarget.dataset.idx;
				var val = e.detail.value;
				var editorFormdata = that.allbuydata[keyid].editorFormdata;
				if(!editorFormdata) editorFormdata = [];
				editorFormdata[idx] = val;
				that.allbuydata[keyid].editorFormdata = editorFormdata;
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
			editorBindPickerChangeEnrol:function(e){
				var val = e.detail.value;
				console.log(val);
				this.enrol = val
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
			hideglass:function(e){
				var that = this
				that.isshowglass = false;
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
        var storekeyid    = e.currentTarget.dataset.storekeyid;
        that.storedata      = storedata?storedata:'';
        that.storekey       = storekey?storekey:0;
        that.storefreightkey= storefreightkey?storefreightkey:0;
        that.storekeyid   = storekeyid?storekeyid:0;
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
	}
}
</script>

<style>
.container{overflow: hidden;}
.redBg{color:#fff;padding:4rpx 16rpx;font-weight:normal;border-radius:8rpx;font-size:24rpx; width: auto; display: inline-block; margin-top: 4rpx;}
.address-add {width: 94%;margin: 20rpx 3%;background: #fff;border-radius: 20rpx;padding: 20rpx 3%;min-height: 140rpx;}
.address-add .f1 {margin-right: 20rpx}
.address-add .f1 .img {width: 66rpx;height: 66rpx;}
.address-add .f2 {color: #666;}
.address-add .f3 {width: 26rpx;height: 26rpx;}
.linkitem {width: 100%;padding: 5px 0;background: #fff;display: flex;align-items: center}.cf3 {width: 200rpx;height: 26rpx;display: block;
    text-align: right;}
.linkitem .f1 {width: 160rpx;color: #111111}
.linkitem .input {height: 50rpx;padding-left: 10rpx;color: #222222;font-size: 28rpx;flex: 1}
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
.freight-ul {width: 100%;}
.freight-li {background: #F5F6F8;border-radius: 24rpx;color: #6C737F;font-size: 24rpx;line-height: 48rpx;padding: 0 28rpx;margin: 12rpx 10rpx 12rpx 0;display: inline-block;white-space: break-spaces;max-width: 610rpx;vertical-align: middle;}
.inputPrice {border: 1px solid #ddd; width: 200rpx; height: 40rpx; border-radius: 10rpx; padding: 0 4rpx;}

.price {width: 100%;padding: 20rpx 0;background: #fff;display: flex;align-items: center}
.price .f1 {color: #333}
.price .f2 {color: #111;font-weight: bold;text-align: right;flex: 1}
.price .f3 {width: 24rpx;height: 24rpx;}
.price .couponname{color:#fff;padding:4rpx 16rpx;font-weight:normal;border-radius:8rpx;font-size:24rpx;display:inline-block;margin:2rpx 0 2rpx 10rpx}
.scoredk {width: 94%;margin: 0 3%;margin-bottom: 20rpx;border-radius: 20rpx;padding: 24rpx 20rpx;background: #fff;display: flex;align-items: center}
.scoredk .f1 {color: #333333}
.scoredk .f2 {color: #999999;text-align: right;flex: 1}
.remark {width: 100%;padding: 16rpx 0;background: #fff;display: flex;align-items: center}
.remark .f1 {color: #333;width: 200rpx}
.remark input {border: 0px solid #eee;height: 70rpx;padding-left: 10rpx;text-align: right}
.footer {width: 96%;background: #fff;margin-top: 5px;position: fixed;left: 0px;bottom: 0px;padding: 0 2%;display: flex;align-items: center;z-index: 8;box-sizing:content-box}
.footer .text1 {height: 110rpx;line-height: 110rpx;color: #2a2a2a;font-size: 30rpx;}
.footer .text1 text {color: #e94745;font-size: 32rpx;}
.footer .op {width: 90%;height: 80rpx;line-height: 80rpx;color: #fff;text-align: center;font-size: 30rpx;border-radius: 44rpx;margin: 40rpx auto}
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
</style>
