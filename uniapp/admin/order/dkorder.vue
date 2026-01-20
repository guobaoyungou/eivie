<template>
	<view>
		<!-- #ifndef H5 -->
		<view class="navigation">
			<view class='navcontent' :style="{marginTop:navigationMenu.top+'px',width:(navigationMenu.right)+'px'}">
				<view class="header-location-top" :style="{height:navigationMenu.height+'px'}">
					<view class="header-back-but" @click="goBack">
						<image  :src="pre_url+'/static/img/admin/goback.png'"></image>
					</view>
					<view class="header-page-title">代客下单</view>
				</view>
			</view>
		</view>
		<!-- #endif -->
	<view class="content">
		<view class="itemfirst flex-y-center">
			<view class="itemfirst-options flex-y-center" @tap="goto" data-url="../member/index?type=''">
				<view class="flex-y-center">
					<view class="avat-img-view"><image :src="merberInfo.headimg ? merberInfo.headimg : `${pre_url}/static/img/touxiang.png`"></image></view>
					<view class="user-info" v-if="merberInfo.id">
						<view class="un-text">{{merberInfo.realname ? merberInfo.realname : merberInfo.nickname}}</view>
						<view class="tel-text">
							<text>id : </text>{{merberInfo.id}}
						</view>
					</view>
					<view class="user-info" v-else>
						 <text class="un-text">请选择会员</text>
					</view>
				</view>
				<view class="jiantou-img flex flex-y-center">
					<image :src="pre_url+'/static/img/arrowright.png'"></image>
				</view>
			</view>
			<view class="itemfirst-options flex-y-center" @tap="goto" data-url="/admin/order/addmember?type=0">
				<view>添加会员</view>
				<view class="jiantou-img flex flex-y-center">
					<image :src="pre_url+'/static/img/arrowright.png'"></image>
				</view>
			</view>
		</view>
		<view class="item flex-col">
			<view class="flex-y-center input-view"> 
				<text class="input-title">联 系 人：</text>
				<input placeholder="请输入联系人的姓名" v-model="linkman" @input="linkmanInput"	placeholder-style="color:#626262;font-size:28rpx"/>
			</view>
			<view class="flex-y-center input-view">
				<text class="input-title">联系电话：</text>
				<input type="number" placeholder="请输入联系人的手机号" v-model="tel" @input="telInput"	placeholder-style="color:#626262;font-size:28rpx"/>
			</view>
			<view class="flex-y-center input-view">
				<text class="input-title">所在地区：</text>
				<view>
					<uni-data-picker ref="unidatapicker" :localdata="items" :border="false" :placeholder="regiondata || '请选择省市区'" @change="regionchange"></uni-data-picker>
				</view>
			</view>
			<view class="flex-y-center input-view address-view">
				<text class="input-title">详细地址：</text>
				<view class="address-chose flex-y-center">
					<textarea placeholder="请输入联系人的地址" v-model="address" @input="addressInput"	placeholder-style="color:#626262;font-size:28rpx" style="width: 400rpx;height: 100rpx;" />
					<view class="but-class" :style="{background:'linear-gradient(-90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'}"  @tap="selectAddres()">
						选择
					</view>
				</view>
			</view>
		</view>
		<view class="item">
			<view class="title-view flex-y-center">
				<view>商品列表</view>
				<view class="but-class" :style="{background:'linear-gradient(-90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'}" @click="addshop">添加商品</view>
			</view>
			<view class="product">
				<view v-for="(item, index2) in prodata" :key="index2">
					<view class="item flex">
						<!-- @tap="goto" :data-url="'/pages/shop/product?id=' + item.product.id" -->
						<view class="img">
							<image v-if="item.guige.pic" :src="item.guige.pic"></image>
							<image v-else :src="item.product.pic"></image>
						</view>
						<view class="info flex1">
							<view class="f1">{{item.product.name}}</view>
							<view class="f2">规格：{{item.guige.name}}</view>
							<view class="modify-price flex-y-center">
								<view class="f2">修改单价：</view>
								<input type="digit" :value="item.guige.sell_price" class="inputPrice" @input="inputPrice($event,index2)">
							</view>
							<view class="f3">
								<block><text style="font-weight:bold;">￥{{item.guige.sell_price}}</text></block>
								<text style="padding-left:20rpx"> × {{item.num}}</text>
							</view>
						</view>
						<view class="del-view flex-y-center" @tap.stop="clearShopCartFn(item.id)" style="color:#999999;font-size:24rpx"><image :src="pre_url+'/static/img/del.png'" style="width:24rpx;height:24rpx;margin-right:6rpx"/></view>
					</view>
          <view class="glassinfo" v-if="item.product.product_type==1" @tap="showglass" :data-index="index2" :data-grid="item.product.has_glassrecord==1?item.product.glassrecord.id:0" :style="'background:rgba('+t('color1rgb')+',0.8);color:#FFF'">
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
			<view class="input-view" v-if="freightList.length"> 
				<view class="title-view flex-y-center">
					<text class="input-title">配送方式：</text> <!-- <input placeholder="请输入配送方式" v-model="freight" @input=""	placeholder-style="color:#626262;font-size:28rpx"/> -->
				</view>
				
				<view class="freight">
					<view class="freight-ul">
						<view style="width:100%;overflow-y:hidden;overflow-x:scroll;white-space: nowrap;">
							<block v-for="(item, index) in freightList" :key="idx2">
								<view class="freight-li"
									:style="freightkey==index ? 'color:'+t('color1')+';background:rgba('+t('color1rgb')+',0.2)':''"
									@tap="changeFreight(item,index)">{{item.name}}
								</view>
							</block>
						</view>
					</view>
<!-- 					<view class="freighttips"
						v-if="freightList[freightkey].minpriceset==1 && freightList[freightkey].minprice > 0 && freightList[freightkey].minprice*1 > product_price*1">
						满{{freightList[freightkey].minprice}}元起送，还差{{(freightList[freightkey].minprice - product_price).toFixed(2)}}元
					</view>
					<view class="freighttips" v-if="freightList[freightkey].isoutjuli==1">超出配送范围</view>
					<view class="freighttips" v-if="freightList[freightkey].desc">{{freightList[freightkey].desc}}</view> -->
				</view>
				<view class="freighttips" v-if="buydata.desc && buydata.pstype == 0">{{buydata.desc}}</view>
				<view class="freighttips" v-if="buydata.isoutjuli==1">超出配送范围</view>
				<view class="storeitem" v-if="buydata.storedata && buydata.storedata.length && buydata.pstype == 1">
					<view class="panel">
						<view class="f1">可使用门店</view>
					</view>
					<block>		
						<block v-for="(item, idx) in buydata.storedata" :key="idx">
							<view class="radio-item" @tap.stop="choosestore1" :data-bid="buydata.bid" :data-index="idx" v-if="idx<5 || storeshowall == false">
								<view class="f1">
									<view>{{item.name}}</view>
									<view v-if="item.address" class="flex-y-center" style="text-align:left;font-size:24rpx;color:#aaaaae;display: -webkit-box;-webkit-box-orient: vertical;-webkit-line-clamp:1;overflow: hidden;">{{item.address}}</view>
								</view>
								<text style="color:#f50;">{{item.juli}}</text>
                <view class="radio" :style="buydata.storekey==idx ? 'background:'+t('color1')+';border:0' : ''">
                  <image class="radio-img" :src="pre_url+'/static/img/checkd.png'" />
                </view>
							</view>
						</block>
            <view v-if="storeshowall && buydata.storedata.length >5" class="storeviewmore" @tap="doStoreShowAll">- 查看更多 - </view>
					</block>
				</view>

        <view class="price" v-if="buydata.pstimeset==1">
          <view class="f1">{{buydata.pstype==1?'取货':'配送'}}时间：</view>
          <view class="f2" @tap="choosePstime" :data-bid="groupBid">
            {{buydata.pstimetext==''?'请选择时间':buydata.pstimetext}}<text class="iconfont iconjiantou" style="color:#999;font-weight:normal"></text>
          </view>
        </view>

				<view class="storeitem" v-if="buydata.pstype==5">
				  <view class="panel">
				    <view class="f1">配送门店</view>
				    <view class="f2" v-if="buydata.storedata.length > 0">
							<text class="iconfont icondingwei"></text>{{buydata.storedata[buydata.storekey].name}}
				    </view>
				    <view class="f2" v-else>暂无</view>
				  </view>
				  <block v-for="(item, index) in buydata.storedata" :key="index">
				    <view class="radio-item" @tap.stop="choosestore(index)" v-if="storeshowall ? index < 5 :true">
				      <view class="f1">
				        <view>{{item.name}}</view>
				        <view v-if="item.address" style="text-align:left;font-size:24rpx;color:#aaaaae;display: -webkit-box;-webkit-box-orient: vertical;-webkit-line-clamp:1;overflow: hidden;">{{item.address}}</view>
				      </view>
				      <text style="color:#f50;">{{item.juli}}</text>
				      <view class="radio" :style="buydata.storekey == index ? 'background:'+t('color1')+';border:0' : ''">
				        <image class="radio-img" :src="pre_url+'/static/img/checkd.png'" />
				      </view>
				    </view>
				  </block>
				  <view class="storeviewmore" @tap="doStoreShowAll" v-if="buydata.storedata.length >5 && storeshowall">- 查看更多 - </view>
				</view>
			</view>
			<view class="flex-y-center input-view">
				<text class="input-title">支付方式：</text>
				<view class='picker-paytype flex1'>
					<picker @change="bindPickerChange" :value="payTypeIndex" :range="payTypeArr" class="picker-class">
						<view class="uni-input">{{payTypeArr[payTypeIndex]}}</view>
					</picker>
					<view class="jiantou-img flex flex-y-center">
						<image :src="pre_url+'/static/img/arrowright.png'"></image>
					</view>
				</view>
			</view>
<!-- 			<view class="flex-y-center input-view">
				<text class="input-title">配送费：</text>
				<input placeholder="请输入您的姓名" v-model="freightprice" :disabled="true" @input=""	placeholder-style="color:#626262;font-size:28rpx"/>
			</view> -->
			<view class="flex-y-center input-view">
				<text class="input-title">商品金额：</text>
				<text class="f2">¥{{priceCount}}</text>
			</view>
			<view class="flex-y-center input-view" style="justify-content: space-between;">
				<view class="flex-y-center">
					<text class="input-title">订单总价：</text>
					<input type="digit" placeholder="请输入订单总价" v-model="totalprice" :disabled="totalpricefocus" :focus="!totalpricefocus" @blur='totalpricenblur'	placeholder-style="color:#626262;font-size:28rpx" />
				</view>
				<!-- #ifndef H5 -->
				<view class="but-class" :style="{background:'linear-gradient(-90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'}"  @touchend.prevent="focusInput">
					修改
				</view>
				<!-- #endif -->
			</view>
			<view class="flex-y-center input-view">
				<text class="input-title">订单备注：</text>
				<input placeholder="请输入订单备注" v-model="orderNotes" @input=""	placeholder-style="color:#626262;font-size:28rpx;" style="flex:1" />
			</view>
<!-- 			<view class="freight">
				<view class="f1">配送方式</view>
				<view class="freight-ul">
					<view style="width:100%;overflow-y:hidden;overflow-x:scroll;white-space: nowrap;">
						<block v-for="(item, idx2) in freightList" :key="idx2">
							<view class="freight-li"
								:style="freightkey==idx2?'color:'+t('color1')+';background:rgba('+t('color1rgb')+',0.2)':''"
								@tap="changeFreight" :data-bid="bid" :data-index="idx2">{{item.name}}
							</view>
						</block>
					</view>
				</view>
			</view> -->
			<!-- <view>付款时间</view> -->
			<!-- <view>总金额</view> -->
		</view>
	<view style="width: 100%; height:182rpx;"></view>
	<view class="footer flex notabbarbot">
		<view class="text1 flex1">总计：
			<block>
					<text style="font-weight:bold;font-size:36rpx">￥{{totalprice}}</text>
			</block>
		</view>
		<button class="op" :style="{background:'linear-gradient(-90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'}"  @click="topay">
			提交订单</button>
	</view>
	<view v-if="dialogShow" class="popup__container">
		<view class="popup__overlay" @tap.stop="showdialog"></view>
		<view class="popup__modal" style="min-height: 450rpx;">
			<view class="popup__title">
				<text class="popup__title-text">提示</text>
				<image :src="pre_url+'/static/img/close.png'" class="popup__close" style="width:36rpx;height:36rpx"
					@tap.stop="showdialog" />
			</view>
			<view class="popup__content invoiceBox">
				<form @submit="sendCoupon" @reset="formReset" report-submit="true">
					<view class="orderinfo">
						下单成功！快去分享吧
					</view>
					<button class="ff_btn" open-type="share" :style="{background:t('color1')}" @click="shareBut">去分享</button>
				</form>
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
  <!-- 眼镜档案 -->

  <view v-if="pstimeDialogShow" class="popup__container">
    <view class="popup__overlay" @tap.stop="hidePstimeDialog"></view>
    <view class="popup__modal">
      <view class="popup__title">
        <text class="popup__title-text">请选择{{buydata.pstype==1?'取货':'配送'}}时间</text>
        <image :src="pre_url+'/static/img/close.png'" class="popup__close" style="width:36rpx;height:36rpx" @tap.stop="hidePstimeDialog" />
      </view>
      <view class="popup__content">
        <view class="pstime-item" v-for="(item, index) in buydata.pstimeArr" :key="index" @tap="pstimeRadioChange" :data-index="index">
          <view class="flex1">{{item.title}}</view>
          <view class="radio" :style="buydata.freight_time==item.value ? 'background:'+t('color1')+';border:0' : ''">
            <image class="radio-img" :src="pre_url+'/static/img/checkd.png'" />
          </view>
        </view>
      </view>
    </view>
  </view>
	</view>
	</view>
</template>

<script>
	const app = getApp();
	export default {
		data(){
			return{
				mid:'',
				pre_url:app.globalData.pre_url,
				merberInfo:{
					realname:'',
					tel:'',
					headimg:'',
					id:''
				},
				linkman:'',
				tel:'',
				prodata:[],
				freightList:[],
				freightkey:0,
				address:'',
				freight:'商家配送',
				pstype:1,
				goodsprice:'',
				totalprice:'',
				// #ifdef H5
				totalpricefocus:false,
				// #endif
				// #ifndef H5
				totalpricefocus:true,
				// #endif
				payTypeArr: [],
				payTypeIndex:0,
				paytype:'',
				dialogShow:false,
				onSharelink:'',
				navigationMenu:{},
				platform: app.globalData.platform,
				statusBarHeight: 20,
				userAddress:{
					tel:'',
					name:'',
					address:'',
					regiondata:''
				},
				regiondata:'',
				items:[],
				orderNotes:'',
				buydata:{},
				storeshowall:true,
				freight_id:'',
				storeid:'',
        isshowglass:false,
        glassrecordlist:[],
		grid:0,
        hasglassproduct:0,
        curindex:0,
        order_add_optimize:false,
        pstimeDialogShow: false,
        pstimeIndex: -1,
        allbuydata: {},
        storevisible:false,
			}
		},
		onLoad(opt) {
			let that = this;
			this.mid = opt.mid ? opt.mid  : '';
			this.userAddress = opt.addressData ? JSON.parse(opt.addressData):this.userAddress;
			if(opt.addressData){
				this.changeAddress(this.userAddress);
			}
			if(this.mid) {
				this.getMemberInfo(this.mid);
			}
			this.getpaytype(); //代客下单支付方式
			var sysinfo = uni.getSystemInfoSync();
			this.statusBarHeight = sysinfo.statusBarHeight;
			this.wxNavigationBarMenu();
			app.get('ApiIndex/getCustom',{}, function (customs) {
				var url = app.globalData.pre_url+'/static/area.json';
				if(customs.data.includes('plug_zhiming')) {
					url = app.globalData.pre_url+'/static/area_gaoxin.json';
				}
				uni.request({
					url: url,
					data: {},
					method: 'GET',
					header: { 'content-type': 'application/json' },
					success: function(res2) {
						that.items = res2.data
					}
				});
			});
		},
		onShow(){
			let that = this;
			uni.$once('dkPageOn',function(res){
				that.userAddress = res;
				that.changeAddress(res);
			})
      if(this.hasglassproduct==1){
      	this.getglassrecord()
      }
			if(this.mid) this.getdatacart(this.mid);
		},
		computed:{
			priceCount(){
				this.goodsprice = this.prodata.reduce((total,current) => total + (current.num * current.guige.sell_price),0);
				this.totalprice = (Number(this.goodsprice) + Number(0)).toFixed(2);
				let num = this.prodata.reduce((total,current) => total + (current.num * current.guige.sell_price),0);
				return num.toFixed(2)
			}
		},
		onShareAppMessage:function(){
			return this._sharewx({title:this.prodata[0].name,pic:this.prodata[0].product.pic,link:this.onSharelink});
		},
		methods:{
			doStoreShowAll:function(){
				this.storeshowall = false;
			},
			choosestore: function(e) {
				this.buydata.storekey = e;
				this.storeid = this.buydata.storedata[this.buydata.storekey].id;
			},
			changeFreight(item,index) {
				let that = this;
				let bid = that.mid;
				that.freightkey = index;
				that.buydata = that.freightList[index];
				that.freight_id = that.freightList[index].id;
				that.pstype = that.freightList[index].pstype;
				if(that.buydata.pstype == 5){
					that.storeid = that.buydata.storedata[0].id;
				}
			},
			focusInput(){
				let that = this;
				that.$nextTick(() => {
					that.totalpricefocus = false;
				})
			},
			telInput(event){
				this.userAddress['tel'] = event.detail.value;
			},
			linkmanInput(event){
				this.userAddress['name'] = event.detail.value;
			},
			addressInput(event){
				this.userAddress['address'] = event.detail.value;
			},
			regionchange(e) {
				const value = e.detail.value
				this.regiondata = value[0].text + '/' + value[1].text + '/' + value[2].text;
				this.userAddress['regiondata'] = this.regiondata;
			},
			goBack(){
				app.goto('/admin/index/index','reLaunch')
			},
			wxNavigationBarMenu:function(){
				if(this.platform=='wx'){
					//胶囊菜单信息
					this.navigationMenu = wx.getMenuButtonBoundingClientRect()
				}
			},
			getMemberInfo: function (mid) {
			  var that = this;
				that.loading = true;
			  app.post('ApiAdminMember/index', {id: mid,pagenum: '1'}, function (res) {
			    that.loading = false;
					let memberdata = {};
					if(res.datalist){ memberdata = res.datalist[0] };
					that.merberInfo = memberdata;
					if(!that.linkman){
						that.linkman = that.merberInfo.realname ? that.merberInfo.realname:'';
					}
					if(!that.tel){
						that.tel = that.merberInfo.tel ? that.merberInfo.tel:'';
					}
					that.mid = that.merberInfo.id;
					that.userAddress.tel = that.merberInfo.tel ? that.merberInfo.tel:that.tel;
					that.userAddress.name = that.merberInfo.realname ? that.merberInfo.realname:that.linkman;
					that.getdatacart(that.mid);
			  });
			},
			clearShopCartFn: function (id) {
			  var that = this;
				uni.showModal({
					title: '提示',
					content: '确认删除选购的商品吗？',
					success: function (res) {
						if (res.confirm) {
							app.post("ApiAdminOrderlr/cartdelete", {mid:that.mid,cartid:id}, function (res) {
							  that.getdatacart(that.mid)
							});
						} else if (res.cancel) {
						}
					}
				});
			},
			shareBut(){
				this.dialogShow = false;
			},
			showdialog(){
				this.dialogShow = !this.dialogShow;
			},
			bindPickerChange: function(e) {
			  this.payTypeIndex = e.detail.value;
				this.paytype = this.payTypeArr[this.payTypeIndex];
			},
			getpaytype(){
				let that = this;
				app.post('ApiAdminOrderlr/getpaytype',{},function(res){
					if(res.status == 1){
            var custom = res.custom;
						that.payTypeArr = Object.values(res.datalist);
						that.paytype = that.payTypeArr[0];
						that.order_add_optimize = custom.order_add_optimize || false;
					}
				})
			},
			totalpricenblur(){
				// #ifndef H5
				this.totalpricefocus = true;
				// #endif
			},
			inputPrice(event,index){
				this.prodata[index].guige.sell_price = event.detail.value;
			},
			getdatacart(id){
				let that = this;
				that.loading = true;
				app.post('ApiAdminOrderlr/cart', {mid:id}, function (res) {
					that.loading = false;
					that.prodata = res.cartlist;
					that.freightList = res.freightList;
					that.buydata = that.freightList[0];
					that.freight_id = that.freightList[0].id;
					that.hasglassproduct = res.hasglassproduct
				});
			},
			selectAddres(){
				if(!this.merberInfo.id) return  app.error('请先选择会员');
				app.goto('dkaddress?mid=' + this.merberInfo.id)
			},
			changeAddress(res){
				let that = this;
				if(res.type == 1){ //选择地址
					if(res.area){
						that.address = res.area + res.address;
					}else{
						that.address = res.address;
					}
				}else{ //添加地址
					that.address = res.address;
				}
				that.$nextTick(() => {
					that.$refs.unidatapicker.inputSelected = [];
				})
				that.regiondata = res.regiondata;
				that.linkman = res.name;
				that.tel = res.tel;
			},
			addshop(){
				if(!this.merberInfo.id) return  app.error('请先选择会员');
				app.goto('dkfastbuy?mid=' + this.merberInfo.id + '&addressData=' + JSON.stringify(this.userAddress))
			},
			//提交代付款订单 
			topay: function(e) {
				var that = this;
				var mid = that.merberInfo.id
				var linkman = that.linkman;
				var tel = that.tel;
				var address = that.address;
				var freight = that.freight;
				var freightprice = 0;
				var paycheck = that.paycheck;
				var totalprice = that.totalprice;
			  var goodsprice = that.goodsprice;
				var prodata = that.prodata;
				var paytype = that.paytype;
				var remark = that.orderNotes;
				var storeid = that.storeid;
				var freight_id = that.freight_id;
				var order_add_optimize = that.order_add_optimize;
				var freight_time = that.buydata.freight_time;

			  if (!mid) return app.error('请先选择会员');
			  if (!linkman && !order_add_optimize) return app.error('请输入联系人');
				if (!tel && !order_add_optimize) return app.error('请输入联系电话');
				if(that.pstype !=1){
					if (!that.regiondata && !order_add_optimize) return app.error('请先选择地区');
					if (!address && !order_add_optimize) return app.error('请输入地址');
				}				
				if(!that.prodata.length) return app.error('请添加商品');
				var province = that.regiondata.split('/')[0] || '';
				var city = that.regiondata.split('/')[1] || '';
				var district = that.regiondata.split('/')[2] || '';
				var prodataIdArr = [];
				for (var i = 0; i < prodata.length; i++) {
          let prodataIdStr = prodata[i].product.id + ',' + prodata[i].guige.id + ',' + prodata[i].num + ',' + prodata[i].guige.sell_price;
          if(prodata[i].product.glass_record_id && prodata[i].product.glass_record_id > 0){
            prodataIdStr += ',' + prodata[i].product.glass_record_id;
          }
				  prodataIdArr.push(prodataIdStr);
				}
				app.showLoading('提交中');
				app.post('ApiAdminOrderlr/createOrder', {
					mid: mid,
					linkman: linkman,
					tel: tel,
					address: address,
					province:province,
					city:city,
					district:district,
					// freight:freight,
					freightprice: freightprice,
					paycheck:'1',
					totalprice:totalprice,
			    goodsprice:that.goodsprice,
			    prodata:prodataIdArr.join('-'),
					paytype:paytype,
					remark:remark,
					storeid:storeid,
					freight_id:freight_id,
          freight_time:freight_time,
				}, function(res) {
					app.showLoading(false);
					if (res.status == 0) {
						//that.showsuccess(res.data.msg);
						app.error(res.msg);
						return;
					}else{
            if(order_add_optimize){
              uni.showModal({
                title: res.msg,
                cancelText:'继续',
                confirmText:'后台首页',
                success: function (ress) {
                  if (ress.confirm) {
                    app.goto('/admin/index/index','reLaunch');
                  } else if (ress.cancel) {
                    console.log('用户点击取消');
                  }
                }
              });
            }else {
              // #ifndef H5
              app.success('下单成功！');
              that.onSharelink = res.url;
              that.dialogShow = true;
              that.getMemberInfo(that.mid);
              // #endif
              // #ifdef H5
              let shareLink = app.globalData.pre_url +'/h5/'+app.globalData.aid+'.html#' + res.url;
              that.getMemberInfo(that.mid);
              uni.showModal({
                title: res.msg,
                content: '复制链接分享好友',
                confirmText:'复制分享',
                success: function (ress) {
                  if (ress.confirm) {
                    uni.setClipboardData({
                      data: shareLink,
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
                  } else if (res.cancel) {
                    console.log('用户点击取消');
                  }
                }
              });
              // #endif
            }
					}
				});
			},
      showglass:function(e){
      	var that = this
      	var grid = e.currentTarget.dataset.grid;
      	var index = e.currentTarget.dataset.index;
        
      	if(that.glassrecordlist.length<1){
      		//没有数据 就重新请求
      		that.getglassrecord();
      	}else{
      		that.isshowglass = true
      	}
      	
      	that.curindex = index
      	that.grid = grid
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
      hideglass:function(e){
      	var that = this
      	that.isshowglass = false;
      },
      chooseglass:function(e){
      	var that = this;
      	var gindex = e.detail.value;
      	var prodata = that.prodata;
      	var index = that.curindex;
      	var glassrecordlist = that.glassrecordlist;
      	var prodataArr = [];
      	var sid = glassrecordlist[gindex].id
        that.prodata[index].product.glass_record_id = sid;
        that.prodata[index].product.glassrecord = glassrecordlist[gindex];
        that.isshowglass = false;
      },
      choosePstime: function(e) {
        var that = this;
        var allbuydata = that.buydata;
        var bid = e.currentTarget.dataset.bid;
        var freightkey = that.freightkey;
        var freightList = allbuydata;
        var freight = freightList;
        var pstimeArr = freightList.pstimeArr;
        var itemlist = [];
        for (var i = 0; i < pstimeArr.length; i++) {
          itemlist.push(pstimeArr[i].title);
        }
        if (itemlist.length == 0) {
          app.alert('当前没有可选' + (freightList.pstype == 1 ? '取货' : '配送') + '时间段');
          return;
        }
        that.nowbid = bid;
        that.pstimeDialogShow = true;
        that.pstimeIndex = -1;
      },
      pstimeRadioChange: function(e) {
        var that = this;
        var buydata = that.buydata;
        var pstimeIndex = e.currentTarget.dataset.index;
        var nowbid = that.nowbid;
        var freightkey = that.freightkey;
        var freightList = buydata;
        var freight = freightList;
        var pstimeArr = freightList.pstimeArr;
        var choosepstime = pstimeArr[pstimeIndex];
        buydata.pstimetext = choosepstime.title;
        buydata.freight_time = choosepstime.value;
        that.buydata = buydata
        that.pstimeDialogShow = false;
      },
      hidePstimeDialog: function() {
        this.pstimeDialogShow = false;
      },
      choosestore1: function(e) {
        var that = this
        var bid        = e.currentTarget.dataset.bid;
        var storekey   = e.currentTarget.dataset.index;
        that.storekey  = storekey;
        var buydata = that.buydata;
        var buydata    = buydata;
        var freightkey = buydata.freightkey
        buydata.storekey = storekey
        that.storeid = buydata.storedata[storekey].id
        that.buydata = buydata;
        that.closestore();
      },
      closestore:function(e){
        var that = this;
        that.storevisible = false;
      },
		}
	}
</script>

<style>
	/* #ifdef H5 */
	/deep/ .input-value{padding: 0px 0px !important;color:#626262 !important;font-size:28rpx;}
	/deep/ .placeholder{color:#626262 !important;font-size:28rpx;}
	/* #endif */
	.headimg-mendian image{ width: 100rpx; height:100rpx; border-radius:10rpx;margin-right: 20rpx;}
	.data-v-31ccf324{padding: 0px 0px !important;color:#626262 !important;font-size:28rpx;}
	.content{width: 95%;margin: 0 auto;}
	.item{width: 100%;border-radius: 12rpx;background: #fff;margin-top: 20rpx;padding: 15rpx;justify-content: space-between;}
	.itemfirst{width: 100%;height:120rpx;margin-top: 20rpx;justify-content: space-between;}
	.itemfirst-options{width: 47%;height: 100%;border-radius: 12rpx;background: #fff;justify-content: space-between;padding: 0rpx 15rpx;}
	.avat-img-view {width: 80rpx;height:80rpx;border-radius: 50%;overflow: hidden;}
	.avat-img-view image{width: 100%;height: 100%;}
	.title-view{justify-content: space-between;padding: 15rpx 0rpx;}
	.title-view .but-class{width: 150rpx;height: 50rpx;line-height: 50rpx;color: #fff;text-align: center;font-size: 24rpx;border-radius:35rpx}
	.user-info{margin-left: 20rpx;}
	.user-info .un-text{font-size: 28rpx;color: rgba(34, 34, 34, 0.7);white-space: nowrap;overflow: hidden;text-overflow: ellipsis;width: 186rpx;} 
	.user-info .tel-text{font-size: 26rpx;color: rgba(34, 34, 34, 0.7);margin-top: 5rpx;}
	.jiantou-img{width: 24rpx;height: 24rpx;}
	.jiantou-img image{width: 100%;height: 100%;}
	.input-view{padding: 10rpx 0rpx;margin-bottom: 10rpx;}
	.input-view .picker-paytype{display: flex;align-items: center;justify-content: space-between;}
	.picker-class{width: 500rpx;}
	.input-view .input-title{width: 150rpx;white-space: nowrap;}
	.input-view .but-class{width: 100rpx;height: 50rpx;line-height: 50rpx;color: #fff;text-align: center;font-size: 24rpx;border-radius:35rpx}
	.address-view{display: flex;align-items: flex-start;}
	.address-chose{justify-content: space-between;width: 540rpx;display: flex;align-items: flex-start;}
	.address-plac{color:#626262;font-size:28rpx;}
	.product {width: 100%;border-bottom: 1px solid #f4f4f4;}
	.product .item {position: relative;width: 100%;padding: 20rpx 0;background: #fff;}
	.product .del-view{position: absolute;right: 10rpx;top: 50%;margin-top: -7px;padding: 10rpx;}
	.product .info {padding-left: 20rpx;}
	.product .info .f1 {color: #222222;font-weight: bold;font-size: 26rpx;line-height: 36rpx;margin-bottom: 10rpx;display: -webkit-box;-webkit-box-orient: vertical;-webkit-line-clamp: 2;overflow: hidden;}
	.product .info .f2 {color: #999999;font-size: 24rpx}
	.product .info .f3 {color: #FF4C4C;font-size: 28rpx;display: flex;align-items: center;margin-top: 10rpx}
	.product .info .modify-price{padding: 10rpx 0rpx;}
	.product image {width: 140rpx;height: 140rpx}
	.freight {width: 100%;padding: 10rpx 0;background: #fff;display: flex;flex-direction: column;}
	.freight .f1 {color: #333;margin-bottom: 10rpx}
	.freight .f2 {color: #111111;text-align: right;flex: 1}
	.freight .f3 {width: 24rpx;height: 28rpx;}
	.freighttips {color: red;font-size: 24rpx;}
	.freight-ul {width: 100%;}
	.freight-li {background: #F5F6F8;border-radius: 24rpx;color: #6C737F;font-size: 24rpx;line-height: 48rpx;padding: 0 28rpx;margin: 12rpx 10rpx 12rpx 0;display: inline-block;white-space: break-spaces;max-width: 610rpx;vertical-align: middle;}
	.inputPrice {border: 1px solid #ddd; width: 200rpx; height: 40rpx; border-radius: 10rpx; padding: 0 4rpx;}
	.footer {width: 96%;background: #fff;margin-top: 5px;position: fixed;left: 0px;bottom: 0px;padding: 0 2%;display: flex;align-items: center;z-index: 8;box-sizing:content-box}
	.footer .text1 {height: 110rpx;line-height: 110rpx;color: #2a2a2a;font-size: 30rpx;}
	.footer .text1 text {color: #e94745;font-size: 32rpx;}
	.footer .op {width: 200rpx;height: 80rpx;line-height: 80rpx;color: #fff;text-align: center;font-size: 30rpx;border-radius: 44rpx}
	.footer .op[disabled] { background: #aaa !important; color: #666;}
	.check-area{display: flex;align-items: center;position: fixed;left: 0px;bottom: 182rpx;width: 100%;padding: 10rpx 15rpx;}
	.orderinfo{width:94%;margin:0 3%;margin-top:16rpx;padding: 14rpx 3%;background: #FFF;}
	.ff_btn{ height:80rpx;line-height: 80rpx;width:90%;margin:0 auto;border-radius:40rpx;margin-top:40rpx;color: #fff;font-size: 28rpx;font-weight:bold}
	
	.navigation {width: 100%;padding-bottom:10px;overflow: hidden;}
	.navcontent {display: flex;align-items: center;padding-left: 10px;}
	.header-location-top{position: relative;display: flex;justify-content: center;align-items: center;flex:1;}
	.header-back-but{position: absolute;left:12rpx;display: flex;align-items: center;width: 35rpx;height: 35rpx;overflow: hidden;}
	.header-back-but image{width: 17rpx;height: 31rpx;} 
	.header-page-title{display: flex;flex: 1;align-items: center;justify-content: center;font-size: 34rpx;letter-spacing: 2rpx;}
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
	.storeviewmore{width:100%;text-align:center;color:#889;height:40rpx;line-height:40rpx;margin-top:10rpx}
  
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
  .price {width: 100%;padding: 20rpx 0;background: #fff;display: flex;align-items: center}
  .price .f1 {color: #333}
  .price .f2 {color: #111;font-weight: bold;text-align: right;flex: 1}
  .price .f3 {width: 24rpx;height: 24rpx;}
  .pstime-item {display: flex;border-bottom: 1px solid #f5f5f5;padding: 20rpx 30rpx;}
  .pstime-item .radio {flex-shrink: 0;width: 32rpx;height: 32rpx;background: #FFFFFF;border: 2rpx solid #BFBFBF;border-radius: 50%;margin-right: 30rpx}
  .pstime-item .radio .radio-img {width: 100%;height: 100%}
</style>