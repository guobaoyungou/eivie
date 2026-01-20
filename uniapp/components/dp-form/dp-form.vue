<template>
<view class="dp-form" :style="{
	color:params.color,
	backgroundColor:params.bgcolor,
	margin:(params.margin_y*2.2)+'rpx '+(params.margin_x*2.2)+'rpx',
  marginTop:(params.margin_top*2.2)+(params.margin_y*2.2)+'rpx',
	padding:(params.padding_y*2.2)+'rpx '+(params.padding_x*2.2)+'rpx',
	fontSize:(params.fontsize*2)+'rpx'
}">

	<view class="submember" v-if="data.submember_data && params.show_submember == 1">
		<view class="submember-title">报名会员已有：<text style="color: red">{{data.submember_data_sum}}</text>人</view>
		
		<view class="member-items" v-if="data.submember_data_sum > 0">
			<view class="member-item" v-for="(i,d) in data.submember_data" :key="d">
				<image :src="i.headimg || pre_url+'/static/img/touxiang.png'" class="avatar"></image>
			</view>
			<view class="member-item more" v-if="data.submember_data_sum > 20" @tap="goto" :data-url="'/pagesB/form/submember?id='+data.id">
				<view class="more-text">更多</view>
			</view>
		</view>
		<view class="empty" v-else>暂无报名会员</view>
	</view>
	<form @submit="editorFormSubmit" :data-formcontent="data.content" :data-tourl="params.hrefurl" :data-formid="data.id">
		<view style="display:none">{{test}}</view>
		
		<block v-if="xystatus==1 && xytitlePos=='top'">
			<view class="xycss1">
			  <checkbox-group @change="isagreeChange" style="display: inline-block;">
				  <checkbox style="transform: scale(0.6)"  value="1" :checked="isagree" :color="t('color1')"/>
			  </checkbox-group>
				<text>我已阅读并同意</text>
			  <text @tap="showxieyiFun" :style="{color:t('color1')}">{{xytitle}}</text>
			</view>
		</block>
		<!-- 注意有两个样式都要修改 -->
		<view :class="params.style==1?'dp-form-item':'dp-form-item2'" v-for="(item,idx) in data.content" :style="{borderColor:params.linecolor,background:item.bgcolor?item.bgcolor:'transparent'}" :key="item.id" v-if="!item.linkitem || item.linkshow">
			<block v-if="item.key=='separate'">
				<view class="dp-form-separate" :class="item.val8=='1'?'dp-form-blod':''">{{item.val1}}</view>
			</block>
			<view v-if="item.key!='separate'" class="label" :class="item.val8=='1'?'dp-form-blod':''">{{item.val1}}<text v-if="item.val3==1&&params.showmuststar" style="color:red"> *</text></view>
			<block v-if="item.key=='input'">
				<block v-if="params.style==1">
					<text v-if="item.val5" style="margin-right:10rpx">{{item.val5}}</text>
					<!-- #ifdef MP-WEIXIN || MP-ALIPAY -->
					<block v-if="item.val4==2 && item.val6==1">
						<input @focus="inputFocus" @blur="inputBlur" :type="(item.val4==1 || item.val4==2) ? 'digit' : 'text'" disabled="true" :name="'form'+idx" class="input disabled" :placeholder="item.val2" placeholder-style="font-size:28rpx" :style="{borderColor:params.inputbordercolor,'background-color':'#efefef'}" :value="formdata['form'+idx]" @input="setfield" :data-formidx="'form'+idx"/>
						<button class="authtel" :style="{backgroundColor:params.btnbgcolor,color:params.btncolor}"  open-type="getPhoneNumber" type="primary" @getphonenumber="getPhoneNumber" :data-idx="idx">一键填写</button>
					</block>
					<block v-else>
						<input adjust-position	@focus="inputFocus" @blur="inputBlur"	:type="(item.val4==1 || item.val4==2) ? 'digit' : 'text'"	readonly	:name="'form'+idx" 	class="input" :class="'form'+idx"	:placeholder="item.val2" 	placeholder-style="font-size:28rpx" :style="{borderColor:params.inputbordercolor,'background-color':params.inputbgcolor}" :value="formdata['form'+idx]" @input="setfield" :data-formidx="'form'+idx"/>
					</block>
					<!-- #endif -->
					<!-- #ifdef H5 || APP || MP-TOUTIAO || MP-BAIDU || MP-QQ -->
					<block>
						<input :type="(item.val4==1 || item.val4==2) ? 'digit' : 'text'" readonly :name="'form'+idx" class="input" :placeholder="item.val2" placeholder-style="font-size:28rpx" :style="{borderColor:params.inputbordercolor,'background-color':params.inputbgcolor}" :value="formdata['form'+idx]" @input="setfield" :data-formidx="'form'+idx"/>
					</block>
					<!-- #endif -->
				</block>
				
				<view v-if="params.style==2" class="value">
					<text v-if="item.val5" style="margin-right:10rpx">{{item.val5}}</text>
					<!-- #ifdef MP-WEIXIN || MP-ALIPAY -->
					<block v-if="item.val4==2 && item.val6==1">
						<input :type="(item.val4==1 || item.val4==2) ? 'digit' : 'text'" disabled="true" :name="'form'+idx" class="input disabled" :placeholder="item.val2" placeholder-style="font-size:28rpx" :style="{borderColor:params.inputbordercolor,'background-color':'#efefef'}" :value="formdata['form'+idx]" @input="setfield" :data-formidx="'form'+idx"/>
						<button class="authtel" :style="{backgroundColor:params.btnbgcolor,color:params.btncolor}"  open-type="getPhoneNumber" type="primary" @getphonenumber="getPhoneNumber" :data-idx="idx">一键填写</button>
					</block>
					<block v-else>
						<input adjust-position :type="(item.val4==1 || item.val4==2) ? 'digit' : 'text'" readonly :name="'form'+idx" class="input" :placeholder="item.val2" placeholder-style="font-size:28rpx" :style="{borderColor:params.inputbordercolor,'background-color':params.inputbgcolor}" :value="formdata['form'+idx]" @input="setfield" :data-formidx="'form'+idx"/>
					</block>
					<!-- #endif -->
					<!-- #ifdef H5 || APP || MP-TOUTIAO || MP-BAIDU || MP-QQ -->
					<block>
						<input :type="(item.val4==1 || item.val4==2) ? 'digit' : 'text'" readonly :name="'form'+idx" class="input" :placeholder="item.val2" placeholder-style="font-size:28rpx" :style="{borderColor:params.inputbordercolor,'background-color':params.inputbgcolor}" :value="formdata['form'+idx]" @input="setfield" :data-formidx="'form'+idx"/>
					</block>
					<!-- #endif -->
				</view>
			</block>
			<block v-if="item.key=='textarea'">
				<textarea :adjust-position="false" @focus="inputFocus"	 @blur="inputBlur" :name="'form'+idx" class='textarea' :class="'form'+idx" :placeholder="item.val2" placeholder-style="font-size:28rpx" :style="{borderColor:params.inputbordercolor,'background-color':params.inputbgcolor}" :value="formdata['form'+idx]" @input="setfield" :data-formidx="'form'+idx"/>
			</block>
			<block v-if="item.key=='radio'">
				<radio-group :name="'form'+idx" :class="item.val10=='1'?'rowalone':'flex'" style="flex-wrap:wrap" @change="setfield" :data-formidx="'form'+idx">
					<label v-for="(item1,idx1) in item.val2" :key="item1.id" class="flex-y-center" :class="[item.val11=='1'?'checkborder':'',item.val10=='1'?'':'rowmore']" :style="{borderColor:params.inputbordercolor,'background-color':params.inputbgcolor,padding:'0 10rpx',marginTop:'10rpx',borderRadius: '10rpx'}" @tap="selradio" :data-idx="idx" :data-index="idx1" :data-paymoney="data.radio_paymoney==1 && item.val19?item.val19[idx1]:0" :data-value="item1">
							<radio  class="radio" :value="item1" :checked="formdata['form'+idx] && formdata['form'+idx]==item1 ? true : false" />{{item1}}
					</label>
				</radio-group>
			</block>
			<block v-if="item.key=='checkbox'">
				<checkbox-group :name="'form'+idx" :class="item.val4=='1'?'rowalone':'flex'" style="flex-wrap:wrap" @change="setfield" :data-formidx="'form'+idx">
					<label v-for="(item1,idx1) in item.val2" :key="item1.id" class="flex-y-center" :class="[item.val9=='1'?'checkborder':'',item.val4=='1'?'':'rowmore']" :style="{borderColor:params.inputbordercolor,'background-color':params.inputbgcolor,padding:'0 10rpx',marginTop:'10rpx',borderRadius: '10rpx'}">
						<checkbox class="checkbox" :value="item1" :checked="formdata['form'+idx] && inArray(item1,formdata['form'+idx]) ? true : false"/>{{item1}}
					</label>
				</checkbox-group>
			</block>
			<block v-if="item.key=='selector'">
				<picker class="picker" mode="selector" :name="'form'+idx" :value="editorFormdata[idx]" :range="item.val2" @change="editorBindPickerChange" :data-idx="idx" :data-formidx="'form'+idx">
					<view class="flex-y-center flex-bt" v-if="editorFormdata[idx] || editorFormdata[idx]===0">
						<text>{{item.val2[editorFormdata[idx]]}}</text>
						<view class="arrow-area">
							<view class="input-arrow"></view>
						</view>
					</view>
					<view class="dp-form-normal flex-y-center flex-bt" v-else>
						<text>请选择</text>
						<view class="arrow-area">
							<view class="input-arrow"></view>
						</view>
					</view>
				</picker>
			</block>
			<block v-if="item.key=='time'">
				<picker class="picker" mode="time" :name="'form'+idx" :value="formdata['form'+idx]" :start="item.val2[0]" :end="item.val2[1]" :range="item.val2" @change="editorBindPickerChange" :data-idx="idx" :data-formidx="'form'+idx">
					<view class="flex-y-center flex-bt" v-if="editorFormdata[idx]">
						<text>{{editorFormdata[idx]}}</text>
						<view class="arrow-area">
							<view class="input-arrow"></view>
						</view>
					</view>
					<view class="dp-form-normal flex-y-center flex-bt" v-else>
						<text>请选择</text>
						<view class="arrow-area">
							<view class="input-arrow"></view>
						</view>
					</view>
				</picker>
			</block>
			<block v-if="item.key=='date'">
				<picker class="picker" mode="date" :name="'form'+idx" :value="formdata['form'+idx]" :start="item.val2[0]" :end="item.val2[1]" :range="item.val2" @change="editorBindPickerChange" :data-idx="idx" :data-formidx="'form'+idx">
					<view class="flex-y-center flex-bt" v-if="editorFormdata[idx]">
						<text>{{editorFormdata[idx]}}</text>
						<view class="arrow-area">
							<view class="input-arrow"></view>
						</view>
					</view>
					<view class="dp-form-normal flex-y-center flex-bt" v-else>
						<text>请选择</text>
						<view class="arrow-area">
							<view class="input-arrow"></view>
						</view>
					</view>
				</picker>
			</block>
			<block v-if="item.key=='year'">
				<picker class="picker" :name="'form'+idx" :value="formdata['form'+idx]" @change="yearChange" :data-idx="idx" :range="yearList" :data-formidx="'form'+idx">
					<view class="flex-y-center flex-bt" v-if="editorFormdata[idx]">
						<text>{{editorFormdata[idx]}}</text>
						<view class="arrow-area">
							<view class="input-arrow"></view>
						</view>
					</view>
					<view class="dp-form-normal flex-y-center flex-bt" v-else>
						<text>请选择</text>
						<view class="arrow-area">
							<view class="input-arrow"></view>
						</view>
					</view>
				</picker>
			</block>

			<block v-if="item.key=='region'">
				<uni-data-picker style="flex: 1;width: 100%;" :localdata="items" popup-title="请选择省市区" :placeholder="formdata['form'+idx] || '请选择省市区'" @change="onchange" :data-formidx="'form'+idx"></uni-data-picker>
				<!-- <picker class="picker" mode="region" :name="'form'+idx" value="" @change="editorBindPickerChange" :data-idx="idx">
					<view v-if="editorFormdata[idx]">{{editorFormdata[idx]}}</view> 
					<view v-else>请选择省市区</view>
				</picker> -->
				<input type="text" style="display:none" :name="'form'+idx" :value="regiondata ? regiondata : formdata['form'+idx]"/>
			</block>
			<block v-if="item.key=='upload'">
				<input type="text" style="display:none" :name="'form'+idx" :value="editorFormdata[idx]"/>
				<view class="flex" style="flex-wrap:wrap;padding-top:20rpx">
					<view class="dp-form-imgbox" v-if="editorFormdata[idx]">
						<view class="dp-form-imgbox-close" @tap="removeimg" :data-idx="idx" :data-formidx="'form'+idx"><image :src="pre_url+'/static/img/ico-del.png'" class="image"></image></view>
						<view class="dp-form-imgbox-img"><image class="image" :src="editorFormdata[idx]" @click="previewImage" :data-url="editorFormdata[idx]" mode="aspectFit" :data-idx="idx"/></view>
					</view>
					<view v-else class="dp-form-uploadbtn" :style="{background:'url('+pre_url+'/static/img/shaitu_icon.png) no-repeat 60rpx',backgroundSize:'80rpx 80rpx',backgroundColor:'#F3F3F3'}" @click="editorChooseImage" :data-idx="idx" :data-formidx="'form'+idx" data-type="pic"></view>
				</view>
			</block>
      <!-- #ifdef H5 || MP-WEIXIN -->
      <block v-if="item.key=='upload_file'">
        <input type="text" style="display:none" :name="'form'+idx" :value="editorFormdata[idx]"/>
        <view class="flex-y-center" style="flex-wrap:wrap;padding-top:20rpx">
          <view class="dp-form-imgbox" v-if="editorFormdata[idx]">
            <view class="dp-form-imgbox-close" @tap="removeimg" :data-idx="idx" :data-formidx="'form'+idx">
							<image :src="pre_url+'/static/img/ico-del.png'" class="image"></image>
						</view>
            <view  style="overflow: hidden;white-space: pre-wrap;word-wrap: break-word;color: #4786BC;width: 530rpx;" @tap="download" :data-file="editorFormdata[idx]" >
							文件已上传成功
						</view>
          </view>
          <view v-else class="dp-form-uploadbtn" :style="{background:'url('+pre_url+'/static/img/shaitu_icon.png) no-repeat 60rpx',backgroundSize:'80rpx 80rpx',backgroundColor:'#F3F3F3'}" @click="chooseFile" :data-idx="idx" :data-formidx="'form'+idx" style="margin-right:20rpx;"></view>
					<view v-if="item.val2" style="color:#999">{{item.val2}}</view>
        </view>
      </block>
      <!-- #endif -->
      <block v-if="item.key=='upload_video'">
        <input type="text" style="display:none" :name="'form'+idx" :value="editorFormdata[idx]"/>
        <view class="flex-y-center" style="flex-wrap:wrap;padding-top:20rpx">
          <view class="dp-form-imgbox" v-if="editorFormdata[idx]">
            <view class="dp-form-imgbox-close" @tap="removeimg" :data-idx="idx" :data-formidx="'form'+idx">
                <image :src="pre_url+'/static/img/ico-del.png'" class="image"></image>
            </view>
            <view  style="overflow: hidden;white-space: pre-wrap;word-wrap: break-word;color: #4786BC;width: 430rpx;">
                <video  :src="editorFormdata[idx]" style="width: 100%;"/></video>
            </view>
          </view>
          <view v-else class="dp-form-uploadbtn" :style="{background:'url('+pre_url+'/static/img/shaitu_icon.png) no-repeat 60rpx',backgroundSize:'80rpx 80rpx',backgroundColor:'#F3F3F3'}" @click="upVideo" :data-idx="idx" :data-formidx="'form'+idx" style="margin-right:20rpx;"></view>
					<view v-if="item.val2" style="color:#999">{{item.val2}}</view>
        </view>
      </block>
      <block v-if="item.key=='map'">
        <input type="text" style="display:none" :name="'form'+idx" :value="editorFormdata[idx]"/>
        <view class="flex-y-center" style="flex-wrap:wrap;padding-top:20rpx">
            <text class="flex1" style="text-align:right" :style="area ? '' : 'color:#BBBBBB'" @click="selectzuobiao" :data-idx="idx" :data-formidx="'form'+idx" >{{area ? area : '请选择您的位置'}}</text>
        </view>
      </block>
      <block v-if="item.key=='upload_pics'">
      	<input type="text" style="display:none" :name="'form'+idx" :value="editorFormdata && editorFormdata[idx]?editorFormdata[idx].join(','):''" maxlength="-1"/>
      	<view class="flex" style="flex-wrap:wrap;padding-top:20rpx">
      		<view v-for="(item2, index2) in editorFormdata[idx]" :key="index2" class="dp-form-imgbox" >
      			<view class="dp-form-imgbox-close" @tap="removeimg" :data-index="index2" data-type="pics" :data-idx="idx" :data-formidx="'form'+idx"><image :src="pre_url+'/static/img/ico-del.png'" class="image"></image></view>
      			<view class="dp-form-imgbox-img" style="margin-bottom: 10rpx;"><image class="image" :src="item2" @click="previewImage" :data-url="item2" mode="aspectFit" :data-idx="idx"/></view>
      		</view>
      		<view class="dp-form-uploadbtn" :style="{background:'url('+pre_url+'/static/img/shaitu_icon.png) no-repeat 60rpx',backgroundSize:'80rpx 80rpx',backgroundColor:'#F3F3F3',marginBottom: '10rpx'}" @click="editorChooseImage" :data-idx="idx" :data-formidx="'form'+idx" data-type="pics"></view>
      	</view>
      </block>
		</view>
		<block v-if="data.payset==1">
      
			<block v-if="data.is_other_fee==1">
        <view :class="params.style==1?'dp-form-item-fee':'dp-form-item-fee2'" style="border: none;">
          <view class="dp-form-label">费用明细</view>
          <view class="dp-form-feelist">
          <checkbox-group name="feelist">
            <view class="dp-fee-item" v-for="(item,index) in data.fee_items" :key="index">
              <view class="dp-fee-name">{{item.name}}</view>
              <view class="dp-fee-money">{{item.money}}元</view>
              <view class="dp-fee-check"><checkbox @click="feeChange" :data-index="index" :value="''+index" :checked="item.checked?true:false" :color="t('color1')" style="transform: scale(0.7);"></checkbox></view>
            </view>
          </checkbox-group>
          <!-- <view class="dp-fee-item sum">
            <view class="dp-fee-name" style="width:200rpx ;flex: unset;">费用明细合计：</view>
            <view class="dp-fee-money"><text>{{feetotal}}</text>元</view>
            <view class="dp-fee-check"></view>
          </view> -->
          </view>
        </view>
			</block>
      <block v-else>
        <view class="dp-form-item" v-if="data.priceedit==1 || (data.priceedit!=1 && data.price>0)" style="border: none;padding-left: 32rpx;">
          <text class="dp-form-label" style="font-weight: bold;width:200rpx ;">支付金额：</text>
          <input type="text" class="input" name="price" :value='data.price' v-if="data.priceedit==1" @input="setfield" data-formidx="price" style="font-weight: bold;"/>
          <text v-if="data.priceedit==0" style="font-weight: bold;">{{data.price}}</text>
          <text style="font-weight: bold;">元</text>
        </view>
      </block>
      <view v-if="data.is_other_fee==1 || (data.radio_paymoney==1 && radiopaymoney>0) || (data.select_paymoney==1 && selectpaymoney>0)" :class="params.style==1?'dp-form-item-fee':'dp-form-item-fee2'" style="border: none;">
        <view class="dp-form-feelist">
          <view class="dp-fee-item sum">
            <view class="dp-fee-name" style="width:200rpx ;flex: unset;">费用合计：</view>
            <view class="dp-fee-money">
              <text >{{totalprice}}</text>元
            </view>
            <view class="dp-fee-check"></view>
          </view>
        </view>
      </view>
		</block>
		<block v-if="xystatus==1 && xytitlePos=='bottom'">
			<view class="xycss1 dp-form-item">
			  <checkbox-group @change="isagreeChange" style="display: inline-block;">
				  <checkbox style="transform: scale(0.6)"  value="1" :checked="isagree" :color="t('color1')"/>
			  </checkbox-group>
				<text>我已阅读并同意</text>
			  <text @tap="showxieyiFun" :style="{color:t('color1')}">{{xytitle}}</text>
			</view>
		</block>
				
		<view v-if="showxieyi" class="xieyibox">
			<view class="xieyibox-content">
				<view style="overflow:scroll;height:100%;">
					<parse :content="xycontent" @navigate="navigate"></parse>
				</view>
				<view style="position:absolute;z-index:9999;bottom:10px;left:0;right:0;margin:0 auto;text-align:center; width: 50%;height: 60rpx; line-height: 60rpx; color: #fff; border-radius: 8rpx;" :style="{background:'linear-gradient(90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'}"  @tap="hidexieyi">{{agree_button}}</view>
			</view>
		</view>

    <!--合同签名-->
    <block v-if="data.is_ht && data.is_contract == 1 && data.is_contract_sign == 1">
      <view class="dp-form-separate button" style="margin-top: 20rpx;">
        <text  v-if="!htsignatureurl" style="padding: 25rpx 41%;" :style="{backgroundColor:params.btnbgcolor,color:params.btncolor,borderRadius:(params.btnradius*2.2)+'rpx',fontSize:(params.btnfontsize*2)+'rpx',}" @tap="goto" data-url="/pagesB/form/signature">在线签名</text>
        <text v-else style="padding: 25rpx 43%;color:green" :style="{backgroundColor:params.btnbgcolor,borderRadius:(params.btnradius*2.2)+'rpx',fontSize:(params.btnfontsize*2)+'rpx',}">已签名</text>
      </view>
    </block>

		<button @tap="editorFormSubmit" v-if="data != ''" class="dp-form-btn flex-xy-center" :style="{backgroundColor:params.btnbgcolor,border:'1px solid '+params.btnbordercolor,fontSize:(params.btnfontsize*2)+'rpx',color:params.btncolor,width:(params.btnwidth*2.2)+'rpx',height:(params.btnheight*2.2)+'rpx',lineHeight:(params.btnheight*2.2)+'rpx',borderRadius:(params.btnradius*2.2)+'rpx'}" :data-formcontent="data.content" :data-tourl="params.hrefurl" :data-formid="data.id">{{params.btntext}}</button>
		<view :style="{height:`${keyboardHeight}`+'px'}"></view>
	</form>
</view>
</template>
<script>
	var app = getApp();
	export default {
		data(){
			return {
				pre_url:getApp().globalData.pre_url,
				editorFormdata:[],
				test:'test',
				regiondata:'',
				items: [],
				tmplids: [],
				submitDisabled:false,
				formdata:{},
				formvaldata:{},
				authphone:'',
				platform:'',
				feetotal:0,
				yearList:[],
        area: '',
        adr_lon:'',
        adr_lat:'',
				timer:'',
				keyboardHeight:'0',
				xystatus:0,
				showxieyi:false,
				xytitle:'',
				xycontent:'',
				xytitlePos:'bottom',
				isagree:false,
				agree_button:'',
        mid:app.globalData.mid,
        radios:[],//单选集合
        radiokeys:[],//单选key集合
        radiopaymoney:0,//单选支付金额
        totalprice:0,//总费用合计
        
        selects:[],//下拉选择集合
        selectkeys:[],//下拉选择key集合
        selectpaymoney:0,//下拉选择支付金额
			}
		},
		props: {
			params:{},
			data:{},
			latitude:'',
			longitude:'',
      htsignatureurl:'',
		},
    updated:function(){
      this.calculatePrice();
    },
		mounted:function(){
			var that = this;
      //app.setCache(that.mid+'htsignatureurl','');
			let year = [];
            if (that.data.content) {
                for(let i=0;i<that.data.content.length;i++){
                	if(that.data.content[i].key=='year'){
                		for(let j=that.data.content[i].val2[0];j<=that.data.content[i].val2[1];j++){
                			year.push(j);
                		}
                	}
                }
            }
			
			this.yearList = year.reverse();
			that.platform = app.getplatform();
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
						that.items = res2.data
					}
				});
			});
			that.checkPayMoney()

			var pages = getCurrentPages(); //获取加载的页面
			var currentPage = pages[pages.length - 1]; //获取当前页面的对象
			var thispath = '/' + (currentPage.route ? currentPage.route : currentPage.__route__); //当前页面url 
			var opts = currentPage.$vm.opt;
			var fromrecord = 0;
			if(opts && opts.fromrecord){
				fromrecord = opts.fromrecord;
			}
			that.xystatus = that.data.show_agree;
			that.xycontent = that.data.agree_desc;
			that.xytitle = that.data.agree_title;
			that.agree_button = that.data.agree_button;
			that.xytitlePos = that.data.agree_title_pos;
			app.get('ApiForm/getlastformdata',{formid:that.data.id,fromrecord:fromrecord}, function (res) {
				if(res && res.status == 1 && res.data){
					var formcontent = that.data.content;
					var editorFormdata = [];
					var formvaldata = {};
					formvaldata.price = that.data.price
					for(var i in formcontent){
						var thisval = res.data['form'+i];
						if (formcontent[i].key == 'region') {
							that.regiondata = thisval;
						}
						if (formcontent[i].key == 'selector') {
							for(var j in formcontent[i].val2){
								if(formcontent[i].val2[j] == res.data['form'+i]){
									thisval = j;
								}
							}
						}
						if (formcontent[i].key == 'checkbox') {
							if(res.data['form'+i]){
								res.data['form'+i] = (res.data['form'+i]).split(',');
							}else{
								res.data['form'+i] = [];
							}
						}
						editorFormdata.push(thisval);
						formvaldata['form'+i] = thisval;

						if(formcontent[i].key == 'radio' || formcontent[i].key=='selector'){
							var linkitem = formcontent[i].val1 + '|' + formcontent[i].val2[thisval];
							console.log(linkitem)
							for(var j in that.data.content){
								var thislinkitem = that.data.content[j].linkitem;
								if(thislinkitem == linkitem){
									that.data.content[j].linkshow = true;
									that.test = Math.random();
								}else if(thislinkitem && thislinkitem.split('|')[0] == formcontent[i].val1){
									that.data.content[j].linkshow = false;
									that.test = Math.random();
								}
							}
						}

					}
					that.editorFormdata = editorFormdata;
					that.formvaldata = formvaldata;
					that.formdata = res.data;
				}else{
					var formvaldata = {};
					formvaldata.price = that.data.price;
					that.formvaldata = formvaldata;
				}
			})
		},
		methods:{
			// 失去焦点
			inputBlur(){
				this.$set(this,'keyboardHeight',0)
			},
			// 获取焦点
			inputFocus(event){
				//判断活动是否结束
				if(this.data.is_endtime == 1){
					app.alert('活动已经结束');return;
				}
				// if(this.props.data)
				if (this.timer) {
					clearTimeout(this.timer)
				}
				this.timer = setTimeout(() => {
					this.timer = null
					const height = event.detail.height; //键盘高度
					const formidx = event.target.dataset.formidx
					if(height === 0){
						this.scrollToInput(0);
						return;
					}
				try{
				const query = uni.createSelectorQuery().in(this);
					query.select(`.${formidx}`).boundingClientRect((res) => {
						const windowHeight = uni.getSystemInfoSync().windowHeight;
						// 除去键盘的剩余高度
						let restHeight = windowHeight - height;
						// 元素左下角坐标
						let bottom = res.bottom;
						// 只有当元素被软键盘覆盖的时候才上推页面
						if (bottom <= restHeight) return;
						// 现阶段需要滚动的大小
						let scrollTop = bottom - restHeight;
						this.scrollToInput(height, scrollTop);
					}).exec();
				} catch(err){console.log(err)}
				},300)
			},
			// 获取页面滚动条位置
			getScrollOffset() {
			  return new Promise((resolve) => {
			    try {
			     const query = uni.createSelectorQuery().in(this);
			        query.selectViewport().scrollOffset((res) => {
			          resolve(res.scrollTop);
			        }).exec();
			    } catch (error) {
			      resolve(0);
			    }
			  });
			},
			// 监听页面键盘弹起推动页面
			scrollToInput(height,scrollTop){
				this.$set(this,'keyboardHeight',height)
				 if (scrollTop) {
				    try {
				      this.getScrollOffset().then((lastScrollTop) => {
				        uni.pageScrollTo({
				          // 如果已经存在滚动，在此基础上继续滚
				          scrollTop: lastScrollTop ? lastScrollTop + scrollTop : scrollTop,
				          duration: 0,
				        });
				      });
				    } catch (error) {}
				  }
			},
			onchange(e) {
        const value = e.detail.value
				console.log(value[0].text + ',' + value[1].text + ',' + value[2].text)
				this.regiondata = value[0].text + ',' + value[1].text + ',' + value[2].text;
				//判断活动是否结束
				if(this.data.is_endtime == 1){
					app.alert('活动已经结束');return;
				}
      },
			setfield:function(e){
        var that = this;
				var field = e.currentTarget.dataset.formidx;
				var value = e.detail.value;
				that.formvaldata[field] = value;
				var idx = field.replace('form','');
				console.log(idx);
				var thiscontent = that.data.content[idx]
				//判断活动是否结束
				if(that.data.is_endtime == 1){
					app.alert('活动已经结束');return;
				}
        //如果是输入价格
        if(field == 'price'){
          //可自定义价格
          if(that.data.priceedit==1){
            that.data.price = value;
            that.calculatePrice();
          }
        }else{
          if(thiscontent.key == 'radio' || thiscontent.key=='selector'){
          	var linkitem = thiscontent.val1 + '|' + value;
          	console.log(linkitem)
          	for(var i in that.data.content){
          		var thislinkitem = that.data.content[i].linkitem;
          		if(thislinkitem == linkitem){
          			that.data.content[i].linkshow = true;
          			that.test = Math.random();
          		}else if(thislinkitem && thislinkitem.split('|')[0] == thiscontent.val1){
          			that.data.content[i].linkshow = false;
          			that.test = Math.random();
          		}
          	}
          }
        }
			},
			editorFormSubmit:function(e){
				var that = this;
				if (that.xystatus == 1 && !that.isagree) {
				  app.error('请先阅读并同意'+that.xytitle);
				  return false;
				}
				//判断活动是否结束
				if(that.data.is_endtime == 1){
					app.alert('活动已经结束');return;
				}

				if(this.submitDisabled && app.globalData.mid > 0) return ;
				//console.log('form发生了submit事件，携带数据为：', e.detail.value)
				var subdata = e.detail.value;
				var subdata = JSON.parse(JSON.stringify(this.formvaldata));
				console.log(subdata)
				var formcontent = e.currentTarget.dataset.formcontent;
				var formid = e.currentTarget.dataset.formid;
				var tourl = e.currentTarget.dataset.tourl;
				var formdata = new Array();
				for (var i = 0; i < formcontent.length;i++){
					//console.log(subdata['form' + i]);
					if (formcontent[i].key == 'region') {
							subdata['form' + i] = that.regiondata;
					}
					if (formcontent[i].key!='separate' && formcontent[i].val3 == 1 && (subdata['form' + i] === '' || subdata['form' + i] === null || subdata['form' + i] === undefined || subdata['form' + i].length==0)){
						if(formcontent[i].linkitem == '' || formcontent[i].linkshow){
							app.alert(formcontent[i].val1+' 必填');return;
						}
					}
					if (formcontent[i].key =='switch'){
							if (subdata['form' + i]==false){
									subdata['form' + i] = '否'
							}else{
									subdata['form' + i] = '是'
							}
					}
					if (formcontent[i].key == 'selector') {
							subdata['form' + i] = formcontent[i].val2[subdata['form' + i]]
					}
					if (formcontent[i].key == 'input' && formcontent[i].val4 && subdata['form' + i]!==''){
						if(formcontent[i].val4 == '2'){ //手机号
							if (!app.isPhone(subdata['form' + i])) {
								app.alert(formcontent[i].val1+' 格式错误');return;
							}
						}
						if(formcontent[i].val4 == '3'){ //身份证号
							if (!/(^\d{15}$)|(^\d{18}$)|(^\d{17}(\d|X|x)$)/.test(subdata['form' + i])) {
								app.alert(formcontent[i].val1+' 格式错误');return;
							}
						}
						if(formcontent[i].val4 == '4'){ //邮箱
							if (!/^(.+)@(.+)$/.test(subdata['form' + i])) {
								app.alert(formcontent[i].val1+' 格式错误');return;
							}
						}
					}
					formdata.push(subdata['form' + i])
				}
				
				//范围
				if(that.data.fanwei == 1 && (that.latitude == '' || that.longitude == '')) {
					app.alert('请定位您的位置或者刷新重试');return;
				}
				var feedata = [];
				if(that.data.payset==1){
          if(that.data.is_other_fee==1){
            var feeitems = that.data.fee_items
            var feenum = 0;
            var feetotal  = 0;
            for(let i in feeitems){
            	if(feeitems[i].checked){
            		feenum++;
            		feetotal = feetotal + parseFloat(feeitems[i].money);
            		feedata.push(feeitems[i])
            	}
            }
            if(feenum<1){
            	app.error('请选择费用明细');
            	return;
            }
          }
				}else{
          formdata.price = 0;
          subdata.price  = 0;
        }

        if(that.data.is_ht && that.data.is_contract == 1 && that.data.is_contract_sign == 1){

          //that.htsignatureurl = that.opt.htsignatureurl ?? '';
          console.log(that.htsignatureurl)
          if(!that.htsignatureurl || that.htsignatureurl == '' || that.htsignatureurl == undefined){
            app.alert('请先签名');return;
          }
        }

				//console.log(formdata);
				if(app.globalData.mid > 0){
					that.submitDisabled = true;
				}
				app.showLoading('提交中');

				var pages = getCurrentPages(); //获取加载的页面
				var currentPage = pages[pages.length - 1]; //获取当前页面的对象
				var thispath = '/' + (currentPage.route ? currentPage.route : currentPage.__route__); //当前页面url 
				var opts = currentPage.$vm.opt;

				var posturl = 'ApiForm/formsubmit';
				if(that.params.isquery == '1'){
					posturl = 'ApiForm/formquery';
				}
                
        var edit_id = 0;
        if(opts && opts.fromrecord && opts.type){
          if(opts.type == 'edit'){
                edit_id = opts.fromrecord;
            }
        }
        subdata.adr_lon = that.adr_lon;
        subdata.adr_lat = that.adr_lat;
        var data = {
          formid:formid,
          formdata:subdata,
          price:subdata.price,
          fromurl:thispath+'?id='+opts.id,
          latitude:that.latitude,
          longitude:that.longitude,
          edit_id:edit_id,
          feedata:feedata,
          feetotal:that.feetotal,
          radiopaymoney:that.radiopaymoney,
          selectpaymoney:that.selectpaymoney,
          htsignatureurl:that.htsignatureurl ?? '',
        }
				app.post(posturl,data,function(data){
					that.tmplids = data.tmplids;
					app.showLoading(false);
					if (data.status == 0) {
						//that.showsuccess(res.data.msg);
						setTimeout(function () {
							app.error(data.msg);
						}, 100)
						that.submitDisabled = false;
						return;
					}else if(data.status == 1) { //无需付款
						if(that.params.isquery == '1'){
							that.submitDisabled = false;
							app.goto(data.tourl);return;
						}
						that.subscribeMessage(function () {
							setTimeout(function () {
								app.success(data.msg);
							}, 100)
							setTimeout(function () {
								app.goto(tourl);
							}, 1000)
						});
						return;
					}else if(data.status==2){
						that.subscribeMessage(function () {
							setTimeout(function () {
								app.goto('/pagesExt/pay/pay?id='+data.payorderid+'&tourl='+tourl);
							}, 100);
						});
					}
					that.submitDisabled = false;
				});
			},
			editorChooseImage: function (e) {
				var that = this;
				var idx = e.currentTarget.dataset.idx;
				var tplindex = e.currentTarget.dataset.tplindex;
				var editorFormdata = this.editorFormdata;
				if(!editorFormdata) editorFormdata = [];
        //判断活动是否结束
        if(this.data.is_endtime == 1){
        	app.alert('活动已经结束');return;
        }
        var type = e.currentTarget.dataset.type;
				// 选择图片多选， 最多可以选择的图片张数，默认9
				let count = type == 'pics' ? 9 : 1; 
				app.chooseImage(function(data){
          if(type == 'pics'){
            var pics = editorFormdata[idx];
            if(!pics){
              pics = [];
            }
            for(var i=0;i<data.length;i++){
            	pics.push(data[i]);
            }
            console.log(pics)
            editorFormdata[idx] = pics;
            that.editorFormdata = editorFormdata
            console.log(editorFormdata[idx])
            console.log(editorFormdata)
            that.test = Math.random();
            var field = e.currentTarget.dataset.formidx;
            that.formvaldata[field] = pics;
          }else{
            editorFormdata[idx] = data[0];
            console.log(editorFormdata)
            that.editorFormdata = editorFormdata
            that.test = Math.random();
            
            var field = e.currentTarget.dataset.formidx;
            that.formvaldata[field] = data[0];
          }
				},count)
			},
			removeimg:function(e){
				var that = this;
				var idx = e.currentTarget.dataset.idx;
				var tplindex = e.currentTarget.dataset.tplindex;
				var field = e.currentTarget.dataset.formidx;
				var editorFormdata = this.editorFormdata;
        if(!editorFormdata) editorFormdata = [];
        
        var type  = e.currentTarget.dataset.type;
        var index = e.currentTarget.dataset.index;
        if(type == 'pics'){
          var pics = editorFormdata[idx]
          pics.splice(index,1);
          editorFormdata[idx] = pics;
          that.editorFormdata = editorFormdata
          that.test = Math.random();
          that.formvaldata[field] = pics;
        }else{
          editorFormdata[idx] = '';
          that.editorFormdata = editorFormdata
          that.test = Math.random();
          that.formvaldata[field] = '';
        }
			},
			yearChange:function(e){
				var idx = e.currentTarget.dataset.idx;
				var val = this.yearList[e.detail.value];
				var editorFormdata = this.editorFormdata;
				if(!editorFormdata) editorFormdata = [];
				editorFormdata[idx] = val;
				this.editorFormdata = editorFormdata;
				this.test = Math.random();
				var field = e.currentTarget.dataset.formidx;
				this.formvaldata[field] = val;
				//判断活动是否结束
				if(this.data.is_endtime == 1){
					app.alert('活动已经结束');return;
				}
			},
			editorBindPickerChange:function(e){
				var idx = e.currentTarget.dataset.idx;
				var tplindex = e.currentTarget.dataset.tplindex;
				var val = e.detail.value;
				var editorFormdata = this.editorFormdata;
				if(!editorFormdata) editorFormdata = [];
				editorFormdata[idx] = val;
				this.editorFormdata = editorFormdata
				this.test = Math.random();
				var field = e.currentTarget.dataset.formidx;
				this.formvaldata[field] = val;

				var idx = field.replace('form','');
				var thiscontent = this.data.content[idx]
				//判断活动是否结束
				if(this.data.is_endtime == 1){
					app.alert('活动已经结束');return;
				}
				//console.log(thiscontent);
				if(thiscontent.key == 'radio' || thiscontent.key=='selector'){
					var linkitem = thiscontent.val1 + '|' + thiscontent.val2[val];
					console.log(linkitem)
					for(var i in this.data.content){
						var thislinkitem = this.data.content[i].linkitem;
						if(thislinkitem == linkitem){
							this.data.content[i].linkshow = true;
							this.test = Math.random();
						}else if(thislinkitem && thislinkitem.split('|')[0] == thiscontent.val1){
							this.data.content[i].linkshow = false;
							this.test = Math.random();
						}
					}
          if(thiscontent.key=='selector'){
            var paymoney = thiscontent.val19[val] || 0;
            var value = thiscontent.val2[val] || '';
            this.dealSelectpaymoney(idx,val,paymoney,value)
          }
				}

			},
      dealSelectpaymoney:function(key,index,paymoney,value){

        var that = this;
        var selectkeys = that.selectkeys;
        var selects    = that.selects;
      
        //添加
        var data = {
          key:key,
          index:index,
          paymoney:paymoney,
          value:value
        }
        //查询是否已添加，存在则删除
        var pos = selectkeys && selectkeys.length>0?selectkeys.indexOf(key):-1;
        if(pos>=0){
          selectkeys.splice(pos, 1);
          selects.splice(pos, 1);
          if(selects && selects.length>0){
            selectkeys.push(key);
            selects.push(data);
          }else{
            selectkeys = [key];
            selects    = [data];
          }
        }else{
          if(selects && selects.length>0){
            selectkeys.push(key);
            selects.push(data);
          }else{
            selectkeys = [key];
            selects    = [data];
          }
        }
        var selectpaymoney = 0;
        if(selects){
          var len = selects.length;
          for(var i=0;i<len;i++){
            selectpaymoney += parseFloat(selects[i].paymoney);
          }
        }
        that.selectkeys     = selectkeys;
        that.selects        = selects;
        that.selectpaymoney = selectpaymoney;
        that.calculatePrice();
      },
			getPhoneNumber: function (e) {
				var that = this
				var idx = e.currentTarget.dataset.idx;
				var field = 'form'+idx;
				if(that.authphone){
					that.test = Math.random()
					that.formdata['form'+idx] = that.authphone;
					that.formvaldata[field] = that.authphone;
					return true;
				}
				console.log(e.detail);
				// #ifdef MP-WEIXIN
				if(e.detail.errMsg == "getPhoneNumber:fail user deny"){
					app.error('请同意授权获取手机号');return;
				}
				if(!e.detail.iv || !e.detail.encryptedData){
					app.error('请同意授权获取手机号');return;
				}
				wx.login({success (res1){
					console.log('res1')
					console.log(res1);
					var code = res1.code;
					//用户允许授权
					app.post('ApiIndex/authphone',{ iv: e.detail.iv,encryptedData:e.detail.encryptedData,code:code,pid:app.globalData.pid},function(res2){
						if (res2.status == 1) {
							that.authphone = res2.tel;
							that.test = Math.random()
							that.formdata['form'+idx] = that.authphone;
							that.formvaldata[field] = that.authphone;
						} else {
							app.error(res2.msg);
						}
						return;
					})
				}});
				// #endif
				
				// #ifdef MP-ALIPAY
				if(e.detail.errMsg == "getPhoneNumber:fail Error: 用户取消授权"){
					app.error('请同意授权获取手机号');return;
				}
				else if(e.detail.errMsg && e.detail.errorMessage){
					app.error(e.detail.errMsg);return;
				}
				if(e.detail.encryptedData && e.detail.encryptedData.subMsg){
					app.error(e.detail.encryptedData.subMsg);return;
				}
				if(!e.detail.sign || !e.detail.encryptedData){
					app.error('请同意授权获取手机号');return;
				}
				//https://opendocs.alipay.com/mini/api/getphonenumber
				app.post('ApiIndex/aliAuthphone',{ encryptedData:e.detail.encryptedData,pid:app.globalData.pid},function(res2){
					if (res2.status == 1) {
						that.authphone = res2.tel;
						that.test = Math.random()
						that.formdata['form'+idx] = that.authphone;
						that.formvaldata[field] = that.authphone;
					} else {
						app.error(res2.msg);
					}
					return;
				})

				// #endif
				
			},
      download:function(e){
          var that = this;
          var file = e.currentTarget.dataset.file;
          // #ifdef H5
              window.location.href= file;
          // #endif
          
          // #ifdef MP-WEIXIN
          uni.downloadFile({
            url: file, 
            success: (res) => {
              var filePath = res.tempFilePath;
              if (res.statusCode === 200) {
                uni.openDocument({
									filePath: filePath,
									showMenu: true,
									success: function (res) {
										console.log('打开文档成功');
									}
								});
              }
            }
          });
          // #endif
      },
      chooseFile:function(e){
          var that = this;
          var idx = e.currentTarget.dataset.idx;
          var field = e.currentTarget.dataset.formidx;
          
          var editorFormdata = this.editorFormdata;
          if(!editorFormdata) editorFormdata = [];
          
          var up_url = app.globalData.baseurl + 'ApiImageupload/uploadImg/aid/' + app.globalData.aid + '/platform/' + app.globalData.platform +'/session_id/' +app.globalData.session_id;
          //判断活动是否结束
					if(that.data.is_endtime == 1){
						app.alert('活动已经结束');return;
					}
          // #ifdef H5
          uni.chooseFile({
              count: 1, //默认100
              success: function (res) {
                  const tempFilePaths = res.tempFiles;

                  if(tempFilePaths[0].size > 0){
                    var maxsize = that.data.content[idx].val11;
                    if(maxsize){
                      maxsize = parseFloat(maxsize);
                      if(maxsize > 0 && maxsize * 1024 * 1024 < tempFilePaths[0].size){
                        app.alert('文件过大');return;
                      }
                    }
                  }
                  //for (var i = 0; i < tempFilePaths.length; i++) {
                    app.showLoading('上传中');
                    uni.uploadFile({
                      url: up_url,
                      filePath: tempFilePaths[0]['path'],
                      name: 'file',
                      success: function(res) {
                        app.showLoading(false);
                        var data = JSON.parse(res.data);
                        if (data.status == 1) {
                                  that.formvaldata[field] = data.url;
                                  
                                  editorFormdata[idx] = data.url;
                                  that.editorFormdata = editorFormdata;
                                  that.$set(that.editorFormdata, idx,data.url)
                        } else {
                          app.alert(data.msg);
                        }
                      },
                      fail: function(res) {
                        app.showLoading(false);
                        app.alert(res.errMsg);
                      }
                    });
                  //}
              }
          });
          // #endif
          // #ifdef MP-WEIXIN
              wx.chooseMessageFile({
                count: 1,
                //type: 'file',
                success (res) {
                  // tempFilePath可以作为 img 标签的 src 属性显示图片
                  const tempFilePaths = res.tempFiles
                  console.log(tempFilePaths);
                  
                  if(tempFilePaths[0].size > 0){
                    var maxsize = that.data.content[idx].val11;
                    if(maxsize){
                      maxsize = parseFloat(maxsize);
                      if(maxsize > 0 && maxsize * 1024 * 1024 < tempFilePaths[0].size){
                        app.alert('文件过大');return;
                      }
                    }
                  }
                 
                  //for (var i = 0; i < tempFilePaths.length; i++) {
                    app.showLoading('上传中');
                      console.log(tempFilePaths[0]);
                    uni.uploadFile({
                      url: up_url,
                      filePath: tempFilePaths[0]['path'],
                      name: 'file',
                      success: function(res) {
                        app.showLoading(false);
                        var data = JSON.parse(res.data);
                        if (data.status == 1) {
                                  that.formvaldata[field] = data.url;
                                  
                                  editorFormdata[idx] = data.url;
                                  that.editorFormdata = editorFormdata;
                                  that.$set(that.editorFormdata, idx,data.url)
                        } else {
                          app.alert(data.msg);
                        }
                      },
                      fail: function(res) {
                        app.showLoading(false);
                        app.alert(res.errMsg);
                      }
                    });
                  //}
                },
                complete(res){
                    console.log(res)
                }
              })
          // #endif
      },
      checkPayMoney:function(){
        var that = this
        var data = that.data
        var feetotal = 0;
        if(data && data.is_other_fee){
          var feeitmes = data.fee_items;
          for(let i in feeitmes){
            feetotal += parseFloat(feeitmes[i].money)
            feeitmes[i]['checked'] = false
          }
          that.data.fee_items = feeitmes
          that.feetotal = feetotal.toFixed(2)
        }
        that.calculatePrice();
      },
      feeChange:function(e){
        var that = this;
        var index = e.currentTarget.dataset.index
        var feeitems = that.data.fee_items
        if(feeitems[index].checked){
          feeitems[index].checked = false
        }else{
          feeitems[index].checked = true
        }
        var feetotal = 0;
        for(let i in feeitems){
          if(feeitems[i].checked){
            feetotal = feetotal + parseFloat(feeitems[i].money)
          }
        }
        that.feetotal = feetotal.toFixed(2);
        that.data.fee_items = feeitems;
        that.calculatePrice();
      },
      upVideo:function(e){
          var that = this;
          var that = this;
          var idx = e.currentTarget.dataset.idx;
          var field = e.currentTarget.dataset.formidx;
          //判断活动是否结束
					if(that.data.is_endtime == 1){
						app.alert('活动已经结束');return;
					}
          var editorFormdata = this.editorFormdata;
          if(!editorFormdata) editorFormdata = [];
          var up_url = app.globalData.baseurl + 'ApiImageupload/uploadImg/aid/' + app.globalData.aid + '/platform/' + app.globalData.platform +'/session_id/' +app.globalData.session_id;
          uni.chooseVideo({
            sourceType: ['camera', 'album'],
            success: function (res) {
              var path = res.tempFilePath;
              if(res.size > 0){
                var maxsize = that.data.content[idx].val11;
                if(maxsize){
                  maxsize = parseFloat(maxsize);
                  if(maxsize > 0 && maxsize * 1024 * 1024 < res.size){
                    app.alert('视频文件过大');return;
                  }
                }
              }

              app.showLoading('上传中');
              console.log(path );
              uni.uploadFile({
                url: up_url,
                filePath: path,
                name: 'file',
                success: function(res) {
                  app.showLoading(false);
                  var data = JSON.parse(res.data);
                  if (data.status == 1) {
                    that.formvaldata[field] = data.url;

                    editorFormdata[idx] = data.url;
                    that.editorFormdata = editorFormdata;
                    that.$set(that.editorFormdata, idx,data.url)
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
      selectzuobiao: function (e) {
        var that = this;
        var idx = e.currentTarget.dataset.idx;
        var field = 'form'+idx;
				//判断活动是否结束
				if(that.data.is_endtime == 1){
					app.alert('活动已经结束');return;
				}
        uni.chooseLocation({
          success: function (res) {
            console.log(res);
            that.area = res.address;
            // that.address = res.name;
            that.adr_lat = res.latitude;
            that.adr_lon = res.longitude;
            that.formdata['form'+idx] = res.address;
            that.formvaldata[field] = res.address;
          },
          fail: function (res) {
            console.log(res)
            if (res.errMsg == 'chooseLocation:fail auth deny') {
              //$.error('获取位置失败，请在设置中开启位置信息');
              app.confirm('获取位置失败，请在设置中开启位置信息', function () {
                uni.openSetting({});
              });
            }
          }
        });
      },
      isagreeChange: function (e) {
        var val = e.detail.value;
        if (val.length > 0) {
          this.isagree = true;
        } else {
          this.isagree = false;
        }
        console.log(this.isagree);
      },
      showxieyiFun: function () {
        this.showxieyi = true;
      },
      hidexieyi: function () {
        this.showxieyi = false;
        this.isagree = true;
      },
      selradio:function(e){
        var that = this;
        var key      = e.currentTarget.dataset.idx;
        var index    = e.currentTarget.dataset.index;
        var paymoney = parseFloat(e.currentTarget.dataset.paymoney);
        var value    = e.currentTarget.dataset.value;
        
        var radiokeys = that.radiokeys;
        var radios    = that.radios;

        //添加
        var data = {
          key:key,
          index:index,
          paymoney:paymoney,
          value:value
        }
        //查询是否已添加，存在则删除
        var pos = radiokeys && radiokeys.length>0?radiokeys.indexOf(key):-1;
        if(pos>=0){
          radiokeys.splice(pos, 1);
          radios.splice(pos, 1);
          if(radios && radios.length>0){
            radiokeys.push(key);
            radios.push(data);
          }else{
            radiokeys = [key];
            radios    = [data];
          }
        }else{
          if(radios && radios.length>0){
            radiokeys.push(key);
            radios.push(data);
          }else{
            radiokeys = [key];
            radios    = [data];
          }
        }
        var radiopaymoney = 0;
        if(radios){
          var len = radios.length;
          for(var i=0;i<len;i++){
            radiopaymoney += parseFloat(radios[i].paymoney);
          }
        }
        that.radiokeys     = radiokeys;
        that.radios        = radios;
        that.radiopaymoney = radiopaymoney;
        that.calculatePrice();
      },
      calculatePrice:function(){
        var that = this;
        var totalprice = 0;
        if(that.data.is_other_fee==1){
          totalprice += parseFloat(that.feetotal);
        }else{
          totalprice += that.data.price?parseFloat(that.data.price):0;
        }
        if(that.data.radio_paymoney==1) totalprice += parseFloat(that.radiopaymoney);
        if(that.data.select_paymoney==1) totalprice += parseFloat(that.selectpaymoney);
        that.totalprice = totalprice;
      }
		}
	}
</script>
<style>
.dp-form{height: auto; position: relative;overflow: hidden; padding: 10rpx 0px; background: #fff;}
.dp-form .radio{transform:scale(.7);}
.dp-form .checkbox{transform:scale(.7);}
.dp-form-item{width: 100%;border-bottom: 1px #ededed solid;padding:10rpx 10rpx;display:flex;align-items: center;}
.dp-form-item:last-child{border:0}
.dp-form-item .label{line-height: 70rpx;width:140rpx;margin-right: 10px;flex-shrink:0;text-align: right;}
.dp-form-item .input{height: 70rpx;line-height: 70rpx;overflow: hidden;flex:1;border: 1px solid #e5e5e5;border-radius: 5px;padding: 0 15rpx;background:#fff;flex: 1;}
.dp-form-item .textarea{height:180rpx;line-height:40rpx;overflow: hidden;flex:1;border:1px solid #eee;border-radius:5px;padding:15rpx}
/* #ifdef MP-ALIPAY */
.dp-form-item .radio{display:flex;align-items:center;margin: 12rpx 0rpx;}
/* #endif */
/* #ifndef MP-ALIPAY */
.dp-form-item .radio{height: 70rpx;line-height: 70rpx;display:flex;align-items:center;}
/* #endif */
.dp-form-item .radio2{display:flex;align-items:center;}
.dp-form-item .radio .myradio{margin-right:10rpx;display:inline-block;border:1px solid #aaa;background:#fff;height:32rpx;width:32rpx;border-radius:50%}
.dp-form-item .checkbox{height: 70rpx;line-height: 70rpx;display:flex;align-items:center}
.dp-form-item .checkbox2{display:flex;align-items:center;height: 40rpx;line-height: 40rpx;}
.dp-form-item .checkbox .mycheckbox{margin-right:10rpx;display:inline-block;border:1px solid #aaa;background:#fff;height:32rpx;width:32rpx;border-radius:2px}
.dp-form-item .layui-form-switch{}
.dp-form-item .picker{min-height: 70rpx;line-height:70rpx;flex:1;border: 1px solid #e5e5e5;border-radius: 5px;padding: 0 5px;}

.dp-form-item2{width: 100%;border-bottom: 1px #ededed solid;padding:10rpx 10rpx;display:flex;flex-direction:column;align-items: flex-start;}
.dp-form-item2:last-child{border:0}
.dp-form-item2 .label{line-height: 85rpx;width:100%;margin-right: 10px;}
.dp-form-item2 .value{display: flex;justify-content: flex-start;width: 100%;flex: 1;}
.dp-form-item2 .input{height: 70rpx;line-height: 70rpx;overflow: hidden;width:100%;border: 1px solid #e5e5e5;border-radius: 5px;padding: 0 15rpx;background:#fff;flex: 1;}
.dp-form-item2 .textarea{height:180rpx;line-height:40rpx;overflow: hidden;width:100%;border:1px solid #eee;border-radius:5px;padding:15rpx}
/* #ifdef MP-ALIPAY */
.dp-form-item2 .radio{display:flex;align-items:center;margin: 12rpx 0rpx;}
/* #endif */
/* #ifndef MP-ALIPAY */
.dp-form-item2 .radio{height: 70rpx;line-height: 70rpx;display:flex;align-items:center;}
/* #endif */
.dp-form-item2 .radio2{display:flex;align-items:center;}
.dp-form-item2 .radio .myradio{margin-right:10rpx;display:inline-block;border:1px solid #aaa;background:#fff;height:32rpx;width:32rpx;border-radius:50%}
.dp-form-item2 .checkbox{height: 70rpx;line-height: 70rpx;display:flex;align-items:center;}
.dp-form-item2 .checkbox2{display:flex;align-items:center;height: 40rpx;line-height: 40rpx;}
.dp-form-item2 .checkbox .mycheckbox{margin-right:10rpx;display:inline-block;border:1px solid #aaa;background:#fff;height:32rpx;width:32rpx;border-radius:2px}
.dp-form-item2 .layui-form-switch{}
.dp-form-item2 .picker{min-height: 70rpx;line-height:70rpx;flex:1;width:100%;border: 1px solid #e5e5e5;border-radius: 5px;padding: 0 5px;}
.dp-form-btn{margin: 0 auto;background: #ff4f4f;color: #fff;margin-top: 15px;text-align:center}
.dp-form-blod{font-weight: bold;}
.dp-form-imgbox{margin-right:16rpx;font-size:24rpx;position: relative;}
.dp-form-imgbox-close{position: absolute;display: block;width:32rpx;height:32rpx;right:-16rpx;top:-16rpx;color:#999;font-size:32rpx;background:#fff;z-index:9;border-radius:50%}
.dp-form-imgbox-close .image{width:100%;height:100%}
.dp-form-imgbox-img{display: block;width:200rpx;height:200rpx;padding:2px;border: #d3d3d3 1px solid;background-color: #f6f6f6;overflow:hidden;}
.dp-form-imgbox-img>.image{width:100%;height:100%}
.dp-form-imgbox-repeat{position: absolute;display: block;width:32rpx;height:32rpx;line-height:28rpx;right: 2px;bottom:2px;color:#999;font-size:30rpx;background:#fff}
.dp-form-uploadbtn{position:relative;height:200rpx;width:200rpx}
.dp-form-separate{width: 100%;padding: 20rpx;text-align: center;padding: 20rpx;font-size: 36rpx;font-weight: 500;color: #454545;}
.authtel{border-radius: 10rpx; height: 68rpx; line-height: 68rpx;margin-left: 20rpx;padding: 0 20rpx;}
.input.disabled{background: #EFEFEF;}
.dp-form-item-fee .dp-form-label{line-height: 70rpx;width:140rpx;text-align: right;}
.dp-form-feelist{flex: 1;padding-left: 32rpx;}
.dp-fee-item{display: flex;justify-content: flex-start;align-items: center;color: #a3a3a3;font-size: 24rpx;}
.dp-fee-item.sum{color: #222222;font-weight: bold;font-size: 28rpx;padding-top: 10rpx;}
.dp-form-label{flex-shrink: 0;}
.dp-fee-name{flex: 1;width: 60%;}
.dp-fee-money{width: 30%;flex-shrink: 0;}
.dp-fee-check{width: 10%;flex-shrink: 0;}

.dp-form-item-fee2 .dp-form-label{padding-top: 20rpx;}
.dp-form-item-fee2 .dp-form-feelist{flex: 1;padding: 4rpx 0;}

.dp-form-normal{color: grey;}
.arrow-area {
	position: relative;
	width: 20px;
	/* #ifndef APP-NVUE */
	display: flex;
	margin-left: auto;
	/* #endif */
	justify-content: center;
	transform: rotate(-45deg);
	transform-origin: center;
}
.input-arrow {width: 7px;height: 7px;border-left: 1px solid #999;border-bottom: 1px solid #999;}
.checkborder{border: 1px solid #dcdfe6;border-radius: 5px;margin-top: 15rpx;min-width: 300rpx;padding: 0 10rpx;}
.rowalone{width: 100%;}
.rowmore{margin-right: 20rpx;}

.xycss1{line-height: 60rpx;font-size: 24rpx;overflow: hidden;margin-top: 20rpx;}
.xieyibox{width:100%;height:100%;position:fixed;top:0;left:0;z-index:99;background:rgba(0,0,0,0.7)}
.xieyibox-content{width:90%;margin:0 auto;height:80%;margin-top:20%;background:#fff;color:#333;padding:5px 10px 50px 10px;position:relative;border-radius:2px}
.submember{margin-bottom: 20rpx;}
.submember-title {display: flex;height: 80rpx;line-height: 80rpx;align-items: center;font-size: 32rpx;}
.member-items {display: flex;align-items: center;flex-wrap: wrap;padding: 0 10rpx;}
.member-item{width: 16%;margin: 0 3rpx;text-align: center;}	
.avatar{width: 82rpx;height:82rpx;border-radius: 50%;}
.more-text{width: 82rpx;height: 82rpx;text-align: center;line-height: 80rpx;border-radius: 50%;font-size: 24rpx;background-color: rgba(0,0,0,.6);color: #fff;display: inline-block;}
.empty{width: 100%;padding: 30rpx;text-align: center;color: #909399;}
</style>