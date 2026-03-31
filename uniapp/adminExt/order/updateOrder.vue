<template>
	<view>
	<view class="content" id="capture">
		<view class="item flex-col">
        <view class="flex-y-center input-view">
          <text class="input-title">联 系 人：</text>
          <input placeholder="请输入联系人的姓名" v-model="linkman" @input="linkmanInput"	placeholder-style="color:#626262;font-size:28rpx"/>
        </view>
        <view class="flex-y-center input-view">
          <text class="input-title">联系电话：</text>
          <input type="number" placeholder="请输入联系人的手机号" v-model="tel" @input="telInput"	placeholder-style="color:#626262;font-size:28rpx"/>
        </view>
      <view v-if="!inArray(order.freight_type,no_freight_type_arr)">
        <view class="flex-y-center">
          <text class="input-title">所在地区：</text>
          <view style="padding-left: 10rpx;">
            <uni-data-picker ref="unidatapicker" :localdata="items" :border="false" :placeholder="regiondata || '请选择省市区'" @change="regionchange"></uni-data-picker>
          </view>
        </view>
        <view class="flex-y-center input-view ">
          <text class="input-title">详细地址：</text>
          <input placeholder="请输入联系人的地址" v-model="address" @input="addressInput"	placeholder-style="color:#626262;font-size:28rpx" style="width: 100%;padding-left: 10rpx;"/>
        </view>
      </view>
      <view class="flex-y-center input-view">
        <text class="input-title">订单备注：</text>
        <input placeholder="请输入订单备注" v-model="orderNotes" @input=""	placeholder-style="color:#626262;font-size:28rpx;" style="flex:1" />
      </view>
		</view>
		<view class="item">
			<view class="title-view flex-y-center">
				<view>商品列表</view>
				<view class="but-class cxxz" :style="{background:'linear-gradient(-90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'}" @click="addshop">增加商品及规格</view>
			</view>
			<view class="product">
				<view v-for="(item, index2) in prodata" :key="index2">
					<view class="item flex">
						<view class="img">
							<image v-if="item.guige.pic" :src="item.guige.pic"></image>
							<image v-else :src="item.product.pic"></image>
						</view>
						<view class="info flex1">
							<view class="f1" style="height: 45px">
                <textarea name="content" style="border: 1px solid #ddd;width: 100%;height: 45px;border-radius: 10rpx;flex-grow: 1;" placeholder="商品名称" :value="item.guige.name" @input="inputName($event,index2)"></textarea>
              </view>
              <view class="modify-price flex-y-center">
                <view class="f2">规格：</view>
                <input type="text" :value="item.guige.ggname" class="inputPrice" style="width: 324rpx" @input="inputGgname($event,index2)">
              </view>
							<view class="modify-price flex-y-center" style="display: flex;justify-content: space-between;align-items: center;width: 100%;">
								<view class="f2">修改单价：</view>
								<input type="digit" :value="item.guige.sell_price" class="inputPrice" @input="inputPrice($event,index2)" style="margin-left: -26%;">
                <view class="but-class scc" :style="{background:'linear-gradient(-90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'}" @tap.stop="del" :data-index2="index2">删除</view>
							</view>
              <view class="modify-price flex-y-center">
                <view class="f2">修改数量：</view>
                <input type="digit" :value="item.num" class="inputPrice" @input="inputNum($event,index2)">
              </view>
							<view class="f3">
								<block><text style="font-weight:bold;">￥{{item.guige.sell_price ? item.guige.sell_price : '0.00'}}</text></block>
								<text style="padding-left:20rpx"> × {{item.num ? item.num : 0}}</text>
							</view>
              <view class="modify-price flex-y-center">
                <view class="f2">备注：</view>
                <input type="text" :value="item.guige.remark" class="inputPrice" style="width: 324rpx" @input="inputRemark($event,index2)">
              </view>
						</view>
					</view>
				</view>
			</view>

			<view class="flex-y-center input-view">
				<text class="input-title">运费金额：</text>
        <input type="text" placeholder="" :value="freight_price" @input="freight_priceInput"	placeholder-style="color:#626262;font-size:28rpx"/>
			</view>
      <view class="flex-y-center input-view">
        <text class="input-title">商品金额：</text>
        <input type="text" placeholder="" :value="priceCount"	disabled placeholder-style="color:#626262;font-size:28rpx"/>
      </view>
      <view class="flex-y-center input-view">
        <text class="input-title">会员折扣：</text>
        <input type="text" placeholder="" :value="leveldk_money" @input="leveldk_moneyInput" disabled	placeholder-style="color:#626262;font-size:28rpx"/>
      </view>
			<view class="flex-y-center input-view" style="justify-content: space-between;">
				<view class="flex-y-center">
					<text class="input-title">订单总价：</text>
          <input type="text" placeholder="" :value="totalprice" @input="totalpriceInput"	placeholder-style="color:#626262;font-size:28rpx"/>
				</view>
			</view>
		</view>
	<view style="width: 100%; height:182rpx;"></view>
	<view class="footer flex notabbarbot" v-if="order.status == 0">
		<view class="text1 flex1">总计：
			<block>
					<text style="font-weight:bold;font-size:36rpx">￥{{totalprice}}</text>
			</block>
		</view>
		<button class="op" :style="{background:'linear-gradient(-90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'}"  @click="topay">
			确定修改</button>
	</view>
	</view>
  <loading v-if="loading" ></loading>
  <dp-tabbar :opt="opt"></dp-tabbar>
  <popmsg ref="popmsg"></popmsg>
	</view>
</template>

<script>
	import order from "../huodongbaoming/order.vue";

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
					regiondata:'',
					orderid:'',
				},
				regiondata:'',
				items:[],
				orderNotes:'',
				buydata:{},
        order:{},
        orderGoods:{},
				storeshowall:true,
				freight_id:'',
				storeid:'',
        curindex:0,
        priceCount:0,
        frompage:'',
        id:0,
        freight_price:0,
        loading:false,
        opt:{},
        newpro:1,
        leveldk_money:0,
        no_freight_type_arr:[1],
        order:{},
        delCartId:[]
			}
		},
		onLoad(opt) {
			let that = this;
      that.opt = app.getopts(opt);
      that.mid = that.opt.mid ? that.opt.mid  : '';
      that.id = that.opt.id ? that.opt.id  : '';
      that.frompage = that.opt.frompage ? that.opt.frompage  : '';
      that.address = that.opt.addressData ? JSON.parse(that.opt.addressData)  : '';

      if(!that.id && that.address){
        that.id = that.address['orderid'];
      }

      if(that.frompage == 'updateOrder'){
        that.newpro = 1;
      }

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

			that.getData();
		},
		onShow(){
			let that = this;

		},
		computed:{
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
				this.userAddress['tel'] = event.detail.value|| '';
			},
			linkmanInput(event){
				this.userAddress['name'] = event.detail.value|| '';
			},
			addressInput(event){
				this.userAddress['address'] = event.detail.value || '';
			},
      freight_priceInput(event){
        this.freight_price = event.detail.value|| '';
        this.caculate();
      },
      discountInput(event){
        this.order.discount = event.detail.value|| '';
        this.caculate();
      },
      leveldk_moneyInput(event){
        this.leveldk_money = event.detail.value || '';
        this.caculate();
      },
      totalpriceInput(event){
        this.totalprice = event.detail.value|| '';
        // this.caculate();
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
      getData: function (mid) {
        var that = this;
        that.loading = true;
        app.post('ApiAdminOrder/shoporderdetail', {id: that.id,frompage:'updateOrder'}, function (res) {
          that.loading = false;
          if(res.status == 0){
            app.error(res.msg);
            setTimeout(function () {
              //app.goback();
            }, 1500)
            return;
          }else {
            var order = res.detail;
            var prolist = res.prolist;
            that.order = order;
            that.mid = order.mid;
            that.userAddress.tel = order.tel ? order.tel:'';
            that.userAddress.name = order.linkman || '';
            that.userAddress.address = order.area + order.address;
            that.userAddress.orderid = order.id || '';
            that.prodata = prolist;
            that.linkman = order.linkman || '';
            that.tel = order.tel || '';
            that.regiondata = order.area || '';
            that.userAddress['regiondata'] = order.area || '';
            that.address = order.address || '';
            that.priceCount = order.product_price || 0;
            that.totalprice = order.totalprice || 0;
            that.orderNotes = order.remark || '';
            that.merberInfo.id = order.mid || 0;
            that.merberInfo.tel = order.tel || '';
            that.merberInfo.realname = order.linkman || '';
            that.freight_price = order.freight_price || 0;
            that.leveldk_money = order.leveldk_money || 0;

            if(that.frompage == 'updateOrder'){
              that.getdatacart(that.mid);
            }
          }
        });
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
          if(that.frompage == 'updateOrder') {
            that.getdatacart(that.mid);
          }
			  });
			},

      del(e){
        var that = this;
        app.confirm('确认删除吗?',function(){
          var index2 = e.currentTarget.dataset.index2;
          that.prodata.splice(index2, 1);
          that.caculate();
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
						that.payTypeArr = Object.values(res.datalist);
						that.paytype = that.payTypeArr[0];
					}
				})
			},
			totalpricenblur(){
				// #ifndef H5
				this.totalpricefocus = true;
				// #endif
			},
      inputRemark(event,index){
        this.prodata[index].guige.remark = event.detail.value;
        this.prodata[index].remark = event.detail.value;
      },
      inputGgname(event,index){
        this.prodata[index].guige.ggname = event.detail.value;
        this.prodata[index].ggname = event.detail.value;
      },
      inputName(event,index){
        this.prodata[index].guige.name = event.detail.value;
        this.prodata[index].name = event.detail.value;
      },
			inputPrice(event,index){
				this.prodata[index].guige.sell_price = event.detail.value;
        this.caculate();
			},
      inputNum(event,index){
        this.prodata[index].guige.num = event.detail.value;
        this.prodata[index].num = event.detail.value;
        this.caculate();
      },
			getdatacart(id){
				let that = this;
				that.loading = true;
				app.post('ApiAdminOrderlr/cart', {mid:id}, function (res) {
					that.loading = false;
          // that.prodata = res.cartlist;
          // for(var j in that.prodata){
          //   that.prodata[j].guige.ggname = that.prodata[j].guige.name;
          //   that.prodata[j].guige.name = that.prodata[j].product.name;
          // }
					var cartlist = res.cartlist;

          for(var j in cartlist){
            cartlist[j].guige.ggname = cartlist[j].guige.name;
            cartlist[j].guige.name = cartlist[j].product.name;
            if(cartlist[j].num > 0){
              //清除购物车里已选择的商品
              that.delCartId.push(cartlist[j].id);
              console.log(that.delCartId);

              var oi =0;
              for(var ji in that.prodata){
                if(that.prodata[ji].guige.id == cartlist[j].guige.id){
                  var guigenum = that.prodata[ji].guige.num;
                  that.prodata[ji].guige.num = parseInt(guigenum) +  parseInt(cartlist[j].num)
                  that.prodata[ji].num = parseInt(guigenum) +  parseInt(cartlist[j].num)
                  oi = 1;
                  break;
                }
              }

              if(oi ==0 ){
                that.prodata.push(cartlist[j])
              }
            }
          }

          if(that.delCartId){
            that.setShow(id,that.delCartId,0);
          }

          that.caculate();
				});
			},
      delcart(mid,delCartId){
        let that = this;
        app.post('ApiAdminOrderlr/delcart', {mid:mid,delCartId:delCartId}, function (res) {
        });
      },
      setShow(mid,delCartId,show){
        let that = this;
        app.post('ApiAdminOrderlr/setShow', {mid:mid,delCartId:delCartId,show:show}, function (res) {
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
        let that = this;
        //app.confirm('当前页面数据如若有修改，请先确定修改后再去增加商品，否则选择完商品后跳回本页面数据将会被重置！',function(){
          app.goto('/admin/order/dkfastbuy?frompage=updateOrderShow&mid=' + that.merberInfo.id + '&addressData=' + JSON.stringify(that.userAddress))
        //})
			},
			//提交代付款订单 
			topay: function(e) {
				var that = this;
        var order = that.order;
				var mid = that.merberInfo.id
				var linkman = that.linkman;
				var tel = that.tel;
				var address = that.address;
				var freight = that.freight;
				var freightprice = that.freight_price || 0;
				var paycheck = that.paycheck;
				var totalprice = that.totalprice;
			  var goodsprice = that.goodsprice;
				var prodata = that.prodata;
				var paytype = that.paytype;
				var remark = that.orderNotes;
				var storeid = that.storeid;
				var order = order;
				var newpro = that.newpro;


        if (!mid) return app.error('请先选择会员');
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
				app.post('ApiAdminOrder/orderUpdate', {
					id: order.id,
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
			    goodsprice:that.priceCount,
          buydatastr:prodataIdArr.join('-'),
			    prodata:that.prodata,
          leveldk_money:that.leveldk_money,
					paytype:paytype,
					remark:remark,
					storeid:storeid,
          order:order,
          newpro:newpro,
				}, function(res) {
					app.showLoading(false);
					if (res.status == 0) {
						//that.showsuccess(res.data.msg);
						app.error(res.msg);
						return;
					}else{
            app.success(res.msg);
            //清除购物车里已选择的商品
            if(that.delCartId){
              that.delcart(mid,that.delCartId);
            }
            setTimeout(function () {
              app.goto('/admin/order/shoporder');
            }, 1500)
            return;
					}
				});
			},

      caculate:function(e){
        var that = this;
        var leveldk_money = 0;
        var product_price = 0;
        //var leveldk_money = that.leveldk_money ?? 0;
        var freight_price = that.freight_price ?? 0;

        for(var j in that.prodata){

          if(parseFloat(that.prodata[j].guige.sell_price) > 0 && that.prodata[j].num > 0){
            if(that.prodata[j].product.lvprice == 0){
              leveldk_money += that.prodata[j].guige.sell_price * (1-that.order.discount) * that.prodata[j].num;
            }
            product_price += parseFloat(that.prodata[j].guige.sell_price)*that.prodata[j].num;
          }
        }
        that.priceCount = parseFloat(product_price) ? parseFloat(product_price) :0;
        leveldk_money = parseFloat(leveldk_money)? parseFloat(leveldk_money) :0;
        var coupon_money = that.order.coupon_money ? parseFloat(that.order.coupon_money) : 0;
        var scoredk_money = that.order.scoredk_money? parseFloat(that.order.scoredk_money) : 0;
        var discount_money_admin = that.order.discount_money_admin ? parseFloat(that.order.discount_money_admin) : 0;
        var freight_price = parseFloat(freight_price) ? parseFloat(freight_price) : 0;
        var totalprice = product_price - leveldk_money - coupon_money
        if(that.order.dec_money){
          totalprice -= that.order.dec_money;
        }

        if(totalprice < 0 ) totalprice = 0;
        totalprice = parseFloat(totalprice) + freight_price - scoredk_money- discount_money_admin;

        if(totalprice < 0 ) totalprice = 0;

        that.leveldk_money = parseFloat(leveldk_money) ? parseFloat(leveldk_money.toFixed(2)) : 0;
        that.totalprice = parseFloat(totalprice) ? parseFloat(totalprice.toFixed(2)) : 0;
      }
		},
	}
</script>

<style>
	.headimg-mendian image{ width: 100rpx; height:100rpx; border-radius:10rpx;margin-right: 20rpx;}
	.content{width: 95%;margin: 0 auto;}
	.item{width: 100%;border-radius: 12rpx;background: #fff;margin-top: 20rpx;padding: 15rpx;justify-content: space-between;}
	.avat-img-view image{width: 100%;height: 100%;}
	.title-view{justify-content: space-between;padding: 15rpx 0rpx;}
	.title-view .but-class{width: 150rpx;height: 50rpx;line-height: 50rpx;color: #fff;text-align: center;font-size: 24rpx;border-radius:35rpx}
	.jiantou-img image{width: 100%;height: 100%;}
	.input-view{padding: 10rpx 0rpx;margin-bottom: 10rpx;}
	.input-view .input-title{width: 150rpx;white-space: nowrap;}
	.input-view .but-class{width: 100rpx;height: 50rpx;line-height: 50rpx;color: #fff;text-align: center;font-size: 24rpx;border-radius:35rpx}
	.product {width: 100%;border-bottom: 1px solid #f4f4f4;}
	.product .item {position: relative;width: 100%;padding: 20rpx 0;background: #fff;}
	.product .info {padding-left: 20rpx;}
	.product .info .f1 {color: #222222;font-weight: bold;font-size: 26rpx;line-height: 36rpx;margin-bottom: 10rpx;display: -webkit-box;-webkit-box-orient: vertical;-webkit-line-clamp: 2;overflow: hidden;}
	.product .info .f2 {color: #999999;font-size: 24rpx}
	.product .info .f3 {color: #FF4C4C;font-size: 28rpx;display: flex;align-items: center;margin-top: 10rpx}
	.product .info .modify-price{padding: 10rpx 0rpx;}
	.product image {width: 140rpx;height: 140rpx}
	.inputPrice {border: 1px solid #ddd; width: 200rpx; height: 40rpx; border-radius: 10rpx; padding: 0 4rpx;}
	.footer {width: 96%;background: #fff;margin-top: 5px;position: fixed;left: 0px;bottom: 0px;padding: 0 2%;display: flex;align-items: center;z-index: 8;box-sizing:content-box}
	.footer .text1 {height: 110rpx;line-height: 110rpx;color: #2a2a2a;font-size: 30rpx;}
	.footer .text1 text {color: #e94745;font-size: 32rpx;}
	.footer .op {width: 200rpx;height: 80rpx;line-height: 80rpx;color: #fff;text-align: center;font-size: 30rpx;border-radius: 44rpx}
	.footer .op[disabled] { background: #aaa !important; color: #666;}
  .cxxz{width: 200rpx !important;}
  .scc{border-radius:25px;padding: 0 15rpx;height: 50rpx;line-height: 50rpx;color: #fff;text-align: center;font-size: 24rpx;}
</style>