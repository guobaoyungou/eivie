<template>
<view class="container">
	<block v-if="isload">
		<form @submit="topay">
			<view v-if="needaddress==0" class="address-add">
				<view class="linkitem">
					<label style="color: red;" v-if="contact_require==1"> * </label><text class="f1">联 系 人：</text>
					<input type="text" class="input" :value="linkman" placeholder="请输入您的姓名" @input="inputLinkman" placeholder-style="color:#626262;font-size:28rpx"/>
				</view>
				<view class="linkitem">
					<label style="color: red;" v-if="contact_require==1"> * </label><text class="f1">联系电话：</text>
					<input type="text" class="input" :value="tel" placeholder="请输入您的手机号" @input="inputTel" placeholder-style="color:#626262;font-size:28rpx"/>
				</view>
			</view>
			<view v-else class="address-add flex-y-center" @tap="goto" :data-url="'/pagesB/address/address?fromPage=buy&type=' + (havetongcheng==1?'1':'0')">
				<view class="f1"><image class="img" :src="pre_url+'/static/img/address.png'" /></view>
				<view class="f2 flex1" v-if="address.name">
					<view style="font-weight:bold;color:#111111;font-size:30rpx">{{address.name}} {{address.tel}} <text v-if="address.company">{{address.company}}</text></view>
					<view style="font-size:24rpx">{{address.area}} {{address.address}}</view>
				</view>
				<view v-else class="f2 flex1">请选择收货地址</view>
				<image :src="pre_url+'/static/img/arrowright.png'" class="f3"/>
			</view>
			<view v-for="(buydata, groupBid) in allbuydata" :key="groupBid" class="buydata">
				<view class="btitle">
					<image class="img" :src="pre_url+'/static/img/ico-shop.png'" />{{buydata.business.name}}
				</view>
				<view class="bcontent">
					<view class="product">
						<view v-for="(item, index2) in buydata.prodata" :key="index2" class="item flex">
							<view class="img"><image class="img" :src="item.product.ggpic || item.product.pic"/></view>
							<view class="info flex1">
								<view class="f1">{{item.product.name}}</view>
								<view class="f2" v-if="item.product.ggname" style="color:#666">已选规格: {{item.product.ggname}}</view>
								<view class="f2" v-else>市场价: ￥{{item.product.sell_price}}</view>
								<view class="f3" :style="{color:t('color1')}">{{item.product.score_price}}{{t('金豆')}}<text v-if="item.product.money_price>0">+{{item.product.money_price}}元</text><text style="padding-left:10rpx"> × {{item.num}}</text></view>
							</view>
						</view>
					</view>
					<view class="freight">
						<view class="f1">配送方式</view>
						<view class="freight-ul">
							<view class="flex" style="width:100%;overflow-y:hidden;overflow-x:scroll;">
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
					</view>

					<view class="price" v-if="buydata.freightList[buydata.freightkey].pstimeset==1">
						<view class="f1">{{buydata.freightList[buydata.freightkey].pstype==1?'取货':'配送'}}时间</view>
						<view class="f2" @tap="choosePstime" :data-bid="groupBid">
							{{buydata.pstimetext==''?'请选择时间':buydata.pstimetext}}<text class="iconfont iconjiantou"
								style="color:#999;font-weight:normal"></text>
						</view>
					</view>
					<view class="storeitem" v-if="buydata.freightList[buydata.freightkey].pstype==1 && buydata.freightList[buydata.freightkey].isbusiness!=1">
						<view class="panel">
							<view class="f1">取货地点</view>
							<view class="f2" @tap="openMendian" :data-bid="groupBid" 
								:data-freightkey="buydata.freightkey"
								:data-storekey="buydata.freightList[buydata.freightkey].storekey"><text
									class="iconfont icondingwei"></text>{{buydata.freightList[buydata.freightkey].storedata[buydata.freightList[buydata.freightkey].storekey].name}}
							</view>
						</view>
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
						<view v-if="storeshowall==false && (buydata.freightList[buydata.freightkey].storedata).length > 5" class="storeviewmore" @tap="doStoreShowAll">- 查看更多 - </view>
					</view>
					<view class="storeitem" v-if="buydata.freightList[buydata.freightkey].pstype==1 && buydata.freightList[buydata.freightkey].isbusiness==1">
						<view class="panel">
							<view class="f1">取货地点</view>
						</view>
						<block v-for="(item, idx) in buydata.freightList[buydata.freightkey].storedata" :key="idx">
							<view class="radio-item" v-if="idx<5 || storeshowall==true" @tap="openLocation" :data-freightkey="buydata.freightkey" :data-storekey="idx" :data-bid="groupBid" :data-index="idx">
								<view class="f1">
									<view>{{item.name}}</view>
									<view v-if="item.address" class="flex-y-center" style="text-align:left;font-size:24rpx;color:#aaaaae;display: -webkit-box;-webkit-box-orient: vertical;-webkit-line-clamp:1;overflow: hidden;">{{item.address}}</view>
								</view>
								<text style="color:#f50;">{{item.juli}}</text>
							</view>
						</block>
						<view v-if="storeshowall==false && (buydata.freightList[buydata.freightkey].storedata).length > 5" class="storeviewmore" @tap="doStoreShowAll">- 查看更多 - </view>
					</view>
					<!-- 门店配送 -->
					<view class="storeitem" v-if="buydata.freightList[buydata.freightkey].pstype==5">
						<view class="panel">
							<view class="f1">配送门店</view>
							<view class="f2" @tap="openMendian" :data-bid="groupBid"
								:data-freightkey="buydata.freightkey"
								:data-storekey="buydata.freightList[buydata.freightkey].storekey"><text
									class="iconfont icondingwei"></text>{{buydata.freightList[buydata.freightkey].storedata[buydata.freightList[buydata.freightkey].storekey].name}}
							</view>
						</view>
						<block v-for="(item, idx) in buydata.freightList[buydata.freightkey].storedata" :key="idx">
							<view class="radio-item" @tap.stop="choosestore" :data-bid="groupBid" :data-index="idx" v-if="idx<5 || storeshowall==true">
								<view class="f1">
									<view>{{item.name}}</view>
									<view v-if="item.address" style="text-align:left;font-size:24rpx;color:#aaaaae;display: -webkit-box;-webkit-box-orient: vertical;-webkit-line-clamp:1;overflow: hidden;">{{item.address}}</view>
								</view>
								<text style="color:#f50;">{{item.juli}}</text>
								<view class="radio"
									:style="buydata.freightList[buydata.freightkey].storekey==idx ? 'background:'+t('color1')+';border:0' : ''">
									<image class="radio-img" :src="pre_url+'/static/img/checkd.png'" />
								</view>
							</view>
						</block>
						<view v-if="storeshowall==false && (buydata.freightList[buydata.freightkey].storedata).length > 5" class="storeviewmore" @tap="doStoreShowAll">- 查看更多 - </view>
					</view>

					<view class="price">
						<text class="f1">商品金额</text>
						<text class="f2">¥{{buydata.product_price}}</text>
					</view>
					<view class="price">
						<text class="f1">所需{{t('金豆')}}</text>
						<text class="f2">{{buydata.product_score}}</text>
					</view>
					<view class="price">
						<view class="f1">{{buydata.freightList[buydata.freightkey].freight_price_txt || '运费'}}<text v-if="buydata.freightList[buydata.freightkey].pstype!=1 && buydata.freightList[buydata.freightkey].freeset==1" style="color:#aaa;font-size:24rpx;">（满{{buydata.freightList[buydata.freightkey].free_price}}元包邮）</text></view>
						<text class="f2">+¥{{buydata.freightList[buydata.freightkey].freight_price}}</text>
					</view>
					
					<view style="display:none">{{test}}</view>
					<view class="form-item" v-for="(item,idx) in buydata.freightList[buydata.freightkey].formdata" :key="item.id">
						<view class="label">{{item.val1}}<text v-if="item.val3==1" style="color:red"> *</text></view>
						<block v-if="item.key=='input'">
							<input type="text" :name="'form'+groupBid+'_'+idx" class="input" :placeholder="item.val2" placeholder-style="font-size:28rpx"/>
						</block>
						<block v-if="item.key=='textarea'">
							<textarea :name="'form'+groupBid+'_'+idx" class='textarea' :placeholder="item.val2" placeholder-style="font-size:28rpx"/>
						</block>
						<block v-if="item.key=='radio'">
							<radio-group class="radio-group" :name="'form'+groupBid+'_'+idx">
								<label v-for="(item1,idx1) in item.val2" :key="item1.id" class="flex-y-center">
									<radio class="radio" :value="item1"/>{{item1}}
								</label>
							</radio-group>
						</block>
						<block v-if="item.key=='checkbox'">
							<checkbox-group :name="'form'+groupBid+'_'+idx" class="checkbox-group">
								<label v-for="(item1,idx1) in item.val2" :key="item1.id" class="flex-y-center">
									<checkbox class="checkbox" :value="item1"/>{{item1}}
								</label>
							</checkbox-group>
						</block>
						<block v-if="item.key=='selector'">
							<picker class="picker" mode="selector" :name="'form'+groupBid+'_'+idx" :value="item.val2[buydata.editorFormdata[idx]]" :range="item.val2" @change="editorBindPickerChange" :data-bid="groupBid" :data-idx="idx">
								<view v-if="buydata.editorFormdata && (buydata.editorFormdata[idx] || buydata.editorFormdata[idx]===0)"> {{item.val2[buydata.editorFormdata[idx]]}}</view>
								<view v-else>请选择</view>
							</picker>
							<text class="iconfont iconjiantou" style="color:#999;font-weight:normal"></text>
						</block>
						<block v-if="item.key=='time'">
							<picker class="picker" mode="time" :name="'form'+groupBid+'_'+idx" :value="buydata.editorFormdata[idx]" :start="item.val2[0]" :end="item.val2[1]" :range="item.val2" @change="editorBindPickerChange" :data-bid="groupBid" :data-idx="idx">
								<view v-if="buydata.editorFormdata && buydata.editorFormdata[idx]">{{buydata.editorFormdata[idx]}}</view>
								<view v-else>请选择</view>
							</picker>
							<text class="iconfont iconjiantou" style="color:#999;font-weight:normal"></text>
						</block>
						<block v-if="item.key=='date'">
							<picker class="picker" mode="date" :name="'form'+groupBid+'_'+idx" :value="buydata.editorFormdata[idx]" :start="item.val2[0]" :end="item.val2[1]" :range="item.val2" @change="editorBindPickerChange" :data-bid="groupBid" :data-idx="idx">
								<view v-if="buydata.editorFormdata && buydata.editorFormdata[idx]">{{buydata.editorFormdata[idx]}}</view>
								<view v-else>请选择</view>
							</picker>
							<text class="iconfont iconjiantou" style="color:#999;font-weight:normal"></text>
						</block>
						<block v-if="item.key=='upload' && buydata.editorFormdata">
							<input type="text" style="display:none" :name="'form'+groupBid+'_'+idx" :value="buydata.editorFormdata[idx]"/>
							<view class="flex" style="flex-wrap:wrap;padding-top:20rpx">
								<view class="form-imgbox" v-if="buydata.editorFormdata[idx]">
									<view class="layui-imgbox-close" style="z-index: 2;" @tap="removeimg" :data-bid="groupBid" :data-idx="idx"><image style="display:block" :src="pre_url+'/static/img/ico-del.png'"></image></view>
									<view class="form-imgbox-img"><image class="image" :src="buydata.editorFormdata[idx]" @click="previewImage" :data-url="buydata.editorFormdata[idx]" mode="aspectFit"/></view>
								</view>
								<view v-else class="form-uploadbtn" :style="{background:'url('+pre_url+'/static/img/shaitu_icon.png) no-repeat 50rpx',backgroundSize:'80rpx 80rpx',backgroundColor:'#F3F3F3'}" @click="editorChooseImage" :data-bid="groupBid" :data-idx="idx"></view>
							</view>
						</block>
            <block v-if="item.key=='upload_pics'">
              <input type="text" style="display:none" :name="'form'+groupBid+'_'+idx" :value="buydata.editorFormdata && buydata.editorFormdata[idx]?buydata.editorFormdata[idx].join(','):''" maxlength="-1"/>
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
			<view style="width:100%;height:calc(120rpx + env(safe-area-inset-bottom));"></view>
			<view class="footer flex notabbarbot">
				<view class="f1 flex1">总计：
					<text class="txt" style="font-weight:bold" :style="{color:t('color1')}" v-if="totalprice*1 > 0">￥{{totalprice}} + {{totalscore}}{{t('金豆')}}</text>
					<text class="txt" :style="{color:t('color1')}" v-else>{{totalscore}}{{t('金豆')}}</text>
				</view>
				<button class="op" form-type="submit" :style="{background:'linear-gradient(-90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'}">提交订单</button>
			</view>
		</form>

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
			opt:{},
			loading:false,
      isload: false,
			menuindex:-1,

			pre_url:app.globalData.pre_url,
			editorFormdata:[],
			test:'test',

      prolist: [],
      freightList: [],
      address: [],
      needaddress: 1,
      linkman: '',
      tel: '',
      freightkey: 0,
      freight_price: 0,
      pstimetext: '',
      freight_time: '',
      totalmoney: 0,
      totalscore: 0,
      totalweight: 0,
      totalnum: 1,
			totalprice:'0.00',
      storedata: [],
      storename: '',
      latitude: '',
      longitude: '',
      pstimeDialogShow: false,
      pstimeIndex: -1,
      havetongcheng: "",
			storeshowall:false,
			allbuydata: {},
			nowbid: 0,
			contact_require:0,
      othermid:0,
      
      mustuseaddress:false,//必须使用地址
    };
  },

  onLoad: function (opt) {
		this.opt = app.getopts(opt);
    this.othermid = app.globalData.othermid || 0;
		this.getdata();
  },
	onPullDownRefresh: function () {
		this.getdata();
	},
  methods: {
		getdata: function () {
			var that = this; //获取产品信息
			that.loading = true;
			app.get('ApiGoldBeanShop/buy', {prodata: that.opt.prodata,othermid:that.othermid}, function (res) {
				that.loading = false;
				if (res.status == 0) {
					app.alert(res.msg, function(){
						app.goback()
					});
					return;
				}
				that.totalmoney = res.totalmoney;
				that.totalscore = res.totalscore;
				that.havetongcheng = res.havetongcheng;
				that.address = res.address;
				that.linkman = res.linkman;
				that.tel = res.tel;
				that.allbuydata = res.allbuydata;
				that.contact_require = res.contact_require;
        if(res.mustuseaddress){
          that.mustuseaddress = res.mustuseaddress
          that.needaddress = 1;
        }
				that.calculatePrice();
				that.loaded();
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
    inputLinkman: function (e) {
      this.linkman = e.detail.value;
    },
    inputTel: function (e) {
      this.tel = e.detail.value;
    },
    //选择收货地址
    chooseAddress: function () {
      app.goto('/pagesB/address/address?fromPage=buy&type=' + (this.havetongcheng == 1 ? '1' : '0'));
    },
    //改变收货地址
    changeAddress: function () {
      var that = this;
      that.onLoad();
    },
		//计算价格
		calculatePrice: function() {
			var that = this;
			var address = that.address;
			var allbuydata = that.allbuydata;
			var alltotalprice = 0;
			var allfreight_price = 0;
			var needaddress = 0;
			for (var k in allbuydata) {
				var product_price = parseFloat(allbuydata[k].product_price);
				//算运费
				var freightdata = allbuydata[k].freightList[allbuydata[k].freightkey];
				var freight_price = freightdata['freight_price'];
				if (freightdata.pstype != 1 && freightdata.pstype != 3 && freightdata.pstype != 4) {
					needaddress = 1;
				}
				var totalprice = product_price + freight_price;
				if (totalprice < 0) totalprice = 0;
				alltotalprice += totalprice;
				allfreight_price += freight_price;

				allbuydata[k].totalprice = totalprice.toFixed(2);
				allbuydata[k].freight_price = freight_price.toFixed(2);
			}
			if (alltotalprice < 0) alltotalprice = 0;
			that.needaddress= that.mustuseaddress?1:needaddress;
			that.allbuydata = allbuydata;
			that.totalprice = alltotalprice.toFixed(2);
		},
		changeFreight: function(e) {
			var that = this;
			var allbuydata = that.allbuydata;
			var bid = e.currentTarget.dataset.bid;
			var index = e.currentTarget.dataset.index;
			var freightList = allbuydata[bid].freightList;
			if(freightList[index].pstype==1 && freightList[index].storedata.length < 1) {
				app.error('无可自提门店');return;
			}
			if(freightList[index].pstype==5 && freightList[index].storedata.length < 1) {
				app.error('无可配送门店');return;
			}
			allbuydata[bid].freightkey = index;
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
    hidePstimeDialog: function () {
      this.pstimeDialogShow = false
    },
		choosestore: function(e) {
			var bid = e.currentTarget.dataset.bid;
			var storekey = e.currentTarget.dataset.index;
			var allbuydata = this.allbuydata;
			var buydata = allbuydata[bid];
			var freightkey = buydata.freightkey
			allbuydata[bid].freightList[freightkey].storekey = storekey
			this.allbuydata = allbuydata;
		},
    //提交并支付
    topay: function (e) {
      var that = this;
      var addressid = this.address.id;
      var linkman = this.linkman;
      var tel = this.tel;
			var allbuydata = that.allbuydata;

      if(this.contact_require == 1 && (linkman.trim() == '' || tel.trim() == '')){
        return app.error("请填写联系人信息");
      }
      if(tel.trim()!= '' && !app.isPhone(tel)){
        return app.error("请填写正确的手机号");
      }
      var needaddress = that.needaddress;
      if (needaddress == 0) addressid = 0;
      if (needaddress == 1 && addressid == undefined) {
        app.error('请选择收货地址');
        return;
      }
      
			var buydata = [];
			for (var i in allbuydata) {
				var freightkey = allbuydata[i].freightkey;
				if (allbuydata[i].freightList[freightkey].pstimeset == 1 && allbuydata[i].freight_time == '') {
					app.error('请选择' + (allbuydata[i].freightList[freightkey].pstype == 1 ? '取货' : '配送') + '时间');
					return;
				}
				if (allbuydata[i].freightList[freightkey].pstype == 1 || allbuydata[i].freightList[freightkey].pstype == 5) {
					var storekey = allbuydata[i].freightList[freightkey].storekey;
					var storeid = allbuydata[i].freightList[freightkey].storedata[storekey].id;
				} else {
					var storeid = 0;
				}
				var formdata_fields = allbuydata[i].freightList[freightkey].formdata;
				var formdata = e.detail.value;
				var newformdata = {};
				for (var j = 0; j < formdata_fields.length;j++){
					var thisfield = 'form'+i + '_' + j;
					if (formdata_fields[j].val3 == 1 && (formdata[thisfield] === '' || formdata[thisfield] === undefined || formdata[thisfield].length==0)){
							app.alert(formdata_fields[j].val1+' 必填');return;
					}
					if (formdata_fields[j].key == 'selector') {
							formdata[thisfield] = formdata_fields[j].val2[formdata[thisfield]]
					}
					newformdata['form'+j] = formdata[thisfield];
				}
				
				var buydatatemp = {
					bid: allbuydata[i].bid,
          bidGroup:i,
					prodata: allbuydata[i].prodatastr,
					freight_id: allbuydata[i].freightList[freightkey].id,
					freight_time: allbuydata[i].freight_time,
					storeid: storeid,
					formdata:newformdata
				};
				buydata.push(buydatatemp);
			}
			app.showLoading('提交中');
      app.post('ApiGoldBeanShop/createOrder',{buydata:buydata,addressid:addressid,linkman:linkman,tel:tel,othermid:that.othermid}, function (res) {
				app.showLoading(false);
        if (res.status == 0) {
          app.error(res.msg);
          return;
        }
        app.goto('/pagesExt/pay/pay?id=' + res.payorderid,'redirectTo');
      });
    },
		openLocation:function(e){
			var freightkey = e.currentTarget.dataset.freightkey;
			var storekey = e.currentTarget.dataset.storekey;
			var frightinfo = this.freightList[freightkey]
			var storeinfo = frightinfo.storedata[storekey];
			var latitude = parseFloat(storeinfo.latitude);
			var longitude = parseFloat(storeinfo.longitude);
			var address = storeinfo.name;
			uni.openLocation({
			 latitude:latitude,
			 longitude:longitude,
			 name:address,
			 scale: 13
			})
		},
		openMendian: function(e) {
			var allbuydata = this.allbuydata
			var bid = e.currentTarget.dataset.bid;
			var freightkey = e.currentTarget.dataset.freightkey;
			var storekey = e.currentTarget.dataset.storekey;
			var frightinfo = allbuydata[bid].freightList[freightkey]
			var storeinfo = frightinfo.storedata[storekey];
			// console.log(storeinfo)
			app.goto('/pages/shop/mendian?id=' + storeinfo.id);
		},
		editorChooseImage: function (e) {
			var that = this;
			var bid = e.currentTarget.dataset.bid;
			var idx = e.currentTarget.dataset.idx;
			var tplindex = e.currentTarget.dataset.tplindex;
      var editorFormdata = that.allbuydata[bid].editorFormdata;;
			if(!editorFormdata) editorFormdata = [];
			app.chooseImage(function(data){
				editorFormdata[idx] = data[0];
				console.log(editorFormdata)
				that.editorFormdata = editorFormdata
				that.allbuydata[bid].editorFormdata = that.editorFormdata;
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
		doStoreShowAll:function(){
			this.storeshowall = true;
		},
		removeimg:function(e){
			var that = this;
			var bid = e.currentTarget.dataset.bid;
			var idx = e.currentTarget.dataset.idx;
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
        var pics = that.editorFormdata
        pics.splice(idx,1);
        that.editorFormdata = pics;
        that.allbuydata[bid].editorFormdata = that.editorFormdata;
      }
		},
  }
}
</script>
<style>
.address-add{ width:94%;margin:20rpx 3%;background:#fff;border-radius:20rpx;padding: 20rpx 3%;min-height:140rpx;}
.address-add .f1{margin-right:20rpx}
.address-add .f1 .img{ width: 66rpx; height: 66rpx; }
.address-add .f2{ color: #666; }
.address-add .f3{ width: 26rpx; height: 26rpx;}

.linkitem{width: 100%;padding:1px 0;background: #fff;display:flex;align-items:center}
.linkitem .f1{width:160rpx;color:#111111}
.linkitem .input{height:50rpx;padding-left:10rpx;color:#222222;font-weight:bold;font-size:28rpx;flex:1}

.buydata{width:94%;margin:0 3%;background:#fff;margin-bottom:20rpx;border-radius:20rpx;}

.btitle{width:100%;padding:20rpx 20rpx;display:flex;align-items:center;color:#111111;font-weight:bold;font-size:30rpx}
.btitle .img{width:34rpx;height:34rpx;margin-right:10rpx}

.bcontent{width:100%;padding:0 20rpx}

.product{width:100%;border-bottom:1px solid #f4f4f4} 
.product .item{width:100%; padding:20rpx 0;background:#fff;border-bottom:1px #ededed dashed;}
.product .item:last-child{border:none}
.product .info{padding-left:20rpx;}
.product .info .f1{color: #222222;font-weight:bold;font-size:26rpx;line-height:36rpx;margin-bottom:10rpx;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:2;overflow:hidden;}
.product .info .f2{color: #999999; font-size:24rpx}
.product .info .f3{color: #FF4C4C; font-size:28rpx;display:flex;align-items:center;margin-top:10rpx}
.product .img{ width:140rpx;height:140rpx}
.collage_icon{ color:#fe7203;border:1px solid #feccaa;display:flex;align-items:center;font-size:20rpx;padding:0 6rpx;margin-left:6rpx}

.freight{width:100%;padding:20rpx 0;background:#fff;display:flex;flex-direction:column;}
.freight .f1{color:#333;margin-bottom:10rpx}
.freight .f2{color: #111111;text-align:right;flex:1}
.freight .f3{width: 24rpx;height:28rpx;}
.freighttips{color:red;font-size:24rpx;}

.freight-ul{width:100%;display:flex;}
.freight-li{flex-shrink:0;display:flex;background:#F5F6F8;border-radius:24rpx;color:#6C737F;font-size:24rpx;text-align: center;height:48rpx; line-height:48rpx;padding:0 28rpx;margin:12rpx 10rpx 12rpx 0}


.price{width:100%;padding:20rpx 0;background:#fff;display:flex;align-items:center}
.price .f1{color:#333}
.price .f2{ color:#111;font-weight:bold;text-align:right;flex:1}
.price .f3{width: 24rpx;height:24rpx;}

.scoredk{width:94%;margin:0 3%;margin-bottom:20rpx;border-radius:20rpx;padding:24rpx 20rpx; background: #fff;display:flex;align-items:center}
.scoredk .f1{color:#333333}
.scoredk .f2{ color: #999999;text-align:right;flex:1}

.remark{width: 100%;padding:16rpx 0;background: #fff;display:flex;align-items:center}
.remark .f1{color:#333;width:200rpx}
.remark input{ border:0px solid #eee;height:70rpx;padding-left:10rpx;text-align:right}

.footer {width: 96%;background: #fff;margin-top: 5px;position: fixed;left: 0px;bottom: 0px;padding: 0 2%;display: flex;align-items: center;z-index: 8;box-sizing:content-box}
.footer .f1 {height:110rpx;line-height:110rpx;color: #2a2a2a;font-size: 30rpx;}
.footer .f1 .text{color: #e94745;font-size: 32rpx;}
.footer .op{width: 200rpx;height:80rpx;line-height:80rpx;color: #fff;text-align: center;font-size: 30rpx;border-radius:44rpx}

.storeitem{width: 100%;padding:20rpx 0;display:flex;flex-direction:column;color:#333}
.storeitem .panel{width: 100%;height:60rpx;line-height:60rpx;font-size:28rpx;color:#333;margin-bottom:10rpx;display:flex}
.storeitem .panel .f1{color:#333}
.storeitem .panel .f2{ color:#111;font-weight:bold;text-align:right;flex:1}
.storeitem .radio-item{display:flex;width:100%;color:#000;align-items: center;background:#fff;border-bottom:0 solid #eee;padding:8rpx 20rpx;}
.storeitem .radio-item:last-child{border:0}
.storeitem .radio-item .f1{color:#666;flex:1}
.storeitem .radio{flex-shrink:0;width: 32rpx;height: 32rpx;background: #FFFFFF;border: 2rpx solid #BFBFBF;border-radius: 50%;margin-left:30rpx}
.storeitem .radio .radio-img{width:100%;height:100%}

.pstime-item{display:flex;border-bottom: 1px solid #f5f5f5;padding:20rpx 30rpx;}
.pstime-item .radio{flex-shrink:0;width: 32rpx;height: 32rpx;background: #FFFFFF;border: 2rpx solid #BFBFBF;border-radius: 50%;margin-right:30rpx}
.pstime-item .radio .radio-img{width:100%;height:100%}

.cuxiao-desc{width:100%}
.cuxiao-item{display: flex;padding:0 40rpx 20rpx 40rpx;}
.cuxiao-item .type-name{font-size:28rpx; color: #49aa34;margin-bottom: 10rpx;flex:1}
.cuxiao-item .radio{flex-shrink:0;width: 32rpx;height: 32rpx;background: #FFFFFF;border: 2rpx solid #BFBFBF;border-radius: 50%;margin-right:30rpx}
.cuxiao-item .radio .radio-img{width:100%;height:100%}

.form-item {width: 100%;padding: 16rpx 0;background: #fff;display: flex;align-items: center;justify-content:space-between}
.form-item .label {color: #333;width: 200rpx;flex-shrink:0}
.form-item .radio{transform:scale(.7);}
.form-item .checkbox{transform:scale(.7);}
.form-item .input {border:0px solid #eee;height: 70rpx;padding-left: 10rpx;font-size:30rpx;text-align: right;flex:1}
.form-item .textarea{height:140rpx;line-height:40rpx;overflow: hidden;flex:1;font-size:30rpx;border:1px solid #eee;border-radius:2px;padding:8rpx}
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
.form-imgbox-img>.image{width: 100%;height: 100%;}
.form-imgbox-repeat{position: absolute;display: block;width:32rpx;height:32rpx;line-height:28rpx;right: 2px;bottom:2px;color:#999;font-size:30rpx;background:#fff}
.form-uploadbtn{position:relative;height:180rpx;width:180rpx;margin-right: 16rpx;margin-bottom:10rpx;}
.storeviewmore{width:100%;text-align:center;color:#889;height:40rpx;line-height:40rpx;margin-top:10rpx}
</style>