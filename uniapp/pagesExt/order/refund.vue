<template>
<view class="container">
	<block v-if="isload">
		<form @submit="formSubmit" @reset="formReset" report-submit="true">
			<view class="form-content">
				<view class="form-item">
					<text class="label" >{{markname}}商品</text>
				</view>
				<view class="product">
					<view v-for="(item, index) in prolist" :key="index" class="content">
            <view v-if="opt.type == 'exchange'" @tap.stop="changeradio" :data-index="index" :data-proid="item.proid" :data-ogid="item.id" :data-sell_price="item.sell_price" class="radio" :style="item.checked ? 'background:'+t('color1')+';border:0' : ''"><image class="radio-img" :src="pre_url+'/static/img/checkd.png'"/></view>
						<view @tap="goto" :data-url="'/pages/shop/product?id=' + item.proid">
							<image :src="item.pic" class="productimg"></image>
						</view>
						<view class="detail">
							<text class="t1">{{item.name}}</text>
							<text class="t2">{{item.ggname}}</text>
							<view class="t3"><text class="x1 flex1">￥{{item.sell_price}}</text>
							<text class="x2" v-if="opt.type == 'exchange'">×{{item.num}}</text>
							<view class="num-wrap" v-if="opt.type != 'exchange'">
								<view class="addnum">
									<view class="minus" @tap="gwcminus" :data-index="index" :data-ogid="item.id" :data-num="refundNum[index].num"><image class="img" :src="pre_url+'/static/img/cart-minus.png'"/></view>
									<input class="input" type="number" :value="refundNum[index].num" @blur="gwcinput" :data-index="index" :data-ogid="item.id" :data-max="item.num-item.refund_num" :data-num="refundNum[index].num"></input>
									<view class="plus" @tap="gwcplus" :data-index="index" :data-ogid="item.id" :data-max="item.num-item.refund_num" :data-num="refundNum[index].num"><image class="img" :src="pre_url+'/static/img/cart-plus.png'"/></view>
								</view>
								<view class="text-desc" v-if="opt.type != 'exchange'">申请数量：最多可申请{{item.canRefundNum}}件</view>
							</view>
							</view>
						</view>
					</view>
				</view>
			</view>
			<view class="form-content">
				<view class="form-item" v-if="opt.type == 'refund'" style="display:none">
					<text class="label">货物状态</text>
					<view class="input-item">
						<picker @change="pickerChange" :value="cindex" :range="cateArr" name="receive" style="height:80rpx;line-height:80rpx;border-bottom:1px solid #EEEEEE">
							<view class="picker">{{cindex==-1? '请选择' : cateArr[cindex]}}</view>
						</picker>
					</view>
				</view>
<!--        <view class="form-item" v-if="opt.type == 'exchange' && newpro">-->
<!--          <text class="label">换新商品</text>-->
<!--          <view class="input-item" v-for="(item1, index1) in newpro" :key="index1">-->
<!--            <view @click.stop="buydialogChange" :data-proid="item1.proid">-->
<!--              <input type="text" :value="item1.ggname" :disabled="true" required placeholder="请选择需要更换的新商品" placeholder-style="color:#999;" style="margin-top: 10rpx;padding: 0 10rpx;pointer-events: none"></input>-->
<!--            </view>-->
<!--          </view>-->
<!--        </view>-->
        <view class="form-item" v-if="opt.type == 'exchange' && return_address.return_address">
          <text class="label">寄回地址</text>
          <view class="flex">
            <view  class="input-item" @tap="copy" :data-content="return_address.return_name+' '+return_address.return_tel + ' '+return_address.return_province+' '+return_address.return_city+' '+return_address.return_area+' '+return_address.return_address">{{return_address.return_name}} {{return_address.return_tel}} <br/> {{return_address.return_province}} {{return_address.return_city}} {{return_address.return_area}} {{return_address.return_address}}</view>
            <view class="btn-class" style="margin-left: 20rpx;width: 80rpx" @tap="copy" :data-content="return_address.return_name+' '+return_address.return_tel + ' '+return_address.return_province+' '+return_address.return_city+' '+return_address.return_area+' '+return_address.return_address">复制</view>
          </view>
        </view>
        <view class="form-item" v-if="opt.type == 'exchange'">
          <text class="label">填写快递单号</text>
          <view class="input-item" @tap="fahuo" :data-id="detail.id">
            <input type="text" :value="expressxians" :disabled="true" placeholder="请输入寄回快递单号" placeholder-style="color:#999;" style="margin-top: 10rpx;padding: 0 10rpx;pointer-events: none"></input>
          </view>
        </view>
        <block v-if="detail.wx_express_intracity && (opt.type == 'refund' || opt.type == 'return')">
          <view class="form-item">
            <text class="label">{{markname}}原因</text>
            <view class="input-item">
              <picker @change="wxtc_cancel_reasonChange" :value="wxtc_cancel_reason_index" :range="detail.wxtc_cancel_reason_arr" name="receive" style="height:80rpx;line-height:80rpx;border-bottom:1px solid #EEEEEE">
                <view class="picker">{{ detail.wxtc_cancel_reason_arr[wxtc_cancel_reason_index]}}</view>
              </picker>
            </view>
          </view>
          <view class="form-item">
            <text class="label">{{markname}}原因描述（选填）</text>
            <view class="input-item"><textarea placeholder="请输入原因" placeholder-style="color:#999;" name="reason" @input="reasonInput"></textarea></view>
          </view>
        </block>
        <block v-else>
          <view class="form-item">
            <text class="label">{{markname}}原因</text>
            <view class="input-item"><textarea placeholder="请输入原因" placeholder-style="color:#999;" name="reason" @input="reasonInput"></textarea></view>
          </view>
        </block>

				<view class="form-item" v-if="opt.type == 'refund' || opt.type == 'return'">
					<text class="label">退款金额(元)</text>
					<view class="flex"><input name="money" @input="moneyInput" type="digit" :value="money" placeholder="请输入退款金额" placeholder-style="color:#999;"></input></view>
					<view v-if="Number(detail.scoredkscore) > 0" :style="{color:t('color1'),fontSize:'26rpx',paddingTop:'20rpx'}">*若使用{{t('积分')}}抵扣，仅支持返还全额退款的订单，部分商品退款的订单，{{t('余额')}}将不予返还</view>
          <view v-if="detail.dec_money && detail.dec_money>0" :style="{color:t('color1'),fontSize:'26rpx',paddingTop:'20rpx'}">*{{t('余额')}}抵扣部分仅支持订单全部商品退款，部分商品退款的订单，不予返还，{{t('余额')}}抵扣：{{detail.dec_money}}元</view>
          <view class="form-item" v-if="detail.wx_express_intracity && detail.wxtc_deductfee" style="color: red">
            <view class="label">取消配送途中的订单，需要扣减违约金：{{detail.wxtc_deductfee}}元</view>
            <view class="label">扣减违约金后实际退款：{{sj_wxtc_deductfee}}元</view>
          </view>
				</view>
				<view class="form-item flex-col">
					<view class="label">上传图片(最多三张)</view>
					<view id="content_picpreview" class="flex" style="flex-wrap:wrap;padding-top:20rpx">
						<view v-for="(item, index) in content_pic" :key="index" class="layui-imgbox">
							<view class="layui-imgbox-close" @tap="removeimg" :data-index="index" data-field="content_pic"><image :src="pre_url+'/static/img/ico-del.png'"></image></view>
							<view class="layui-imgbox-img"><image :src="item" @tap="previewImage" :data-url="item" mode="widthFix"></image></view>
						</view>
						<view class="uploadbtn" :style="'background:url('+pre_url+'/static/img/shaitu_icon.png) no-repeat 60rpx;background-size:80rpx 80rpx;background-color:#F3F3F3;'" @tap="uploadimg" data-field="content_pic" v-if="content_pic.length<3"></view>
					</view>
				</view>
			</view>
			<button class="btn" @tap="formSubmit" :style="{background:t('color1')}">确定</button>
			<view style="padding-top:30rpx"></view>
		</form>
	</block>
  <!--选择新商品-->
  <buydialog v-if="buydialogShow" :proid="proid" @buydialogChange="buydialogChange" btntype="1" :needaddcart="false" :showbuynum="false" @addcart="afteraddcart" :menuindex="menuindex"></buydialog>
  <!--快递弹框-->
  <uni-popup id="dialogExpress" ref="dialogExpress" type="dialog">
    <view class="uni-popup-dialog">
      <view class="uni-dialog-title">
        <text class="uni-dialog-title-text">填写快递单号</text>
      </view>
      <view class="uni-dialog-content">
        <view>
          <view class="flex-y-center flex-x-center" style="margin:20rpx 20rpx">
            <text style="font-size:28rpx;color:#000">快递公司：</text>
            <picker @change="expresschange" :value="express_index" :range="expressdata" style="font-size:28rpx;border: 1px #eee solid;padding:10rpx;height:70rpx;border-radius:4px;flex:1">
              <view class="picker">{{expressdata[express_index]}}</view>
            </picker>
          </view>
          <view class="flex-y-center flex-x-center" style="margin:20rpx 20rpx;">
            <view style="font-size:28rpx;color:#555">快递单号：</view>
            <view class="danhao-input-view">
              <input type="text" v-model="express_no" placeholder="请输入快递单号" @input="setexpressno" style="border:none;outline:none;padding: 0 10rpx;height:70rpx;border-radius:4px;flex:1;font-size:28rpx;"/>
              <image :src="`${pre_url}/static/img/admin/saoyisao.png`" @click="saoyisao"></image>
            </view>
          </view>
        </view>
      </view>
      <view class="uni-dialog-button-group">
        <view class="uni-dialog-button" @click="dialogExpressClose">
          <text class="uni-dialog-button-text">取消</text>
        </view>
        <view class="uni-dialog-button uni-border-left" @click="refundExpress">
          <text class="uni-dialog-button-text uni-button-color">确定</text>
        </view>
      </view>
    </view>
  </uni-popup>
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
      orderid: '',
	    ogid:0,
      totalprice: 0,
			order:{},
			detail: {},
			refundNum:[],
			prolist: [],
			content_pic:[],
			cindex:-1,
			cateArr:['未收到货','已收到货'],
			type:'',
			money:'',
			reason:'',
			tmplids:[],
			isloading:0,
			totalcanrefundnum:0,
      markname:'退款',
      buydialogShow:false,
      proid:0,
      newpro: {},
      expressdata:[],
      express_index:0,
      express_no:'',
      express_orderid:0,
      expressxians:'',
      return_address:[],
      wxtc_cancel_reason_index:0,
      sj_wxtc_deductfee:0
    };
  },

  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.orderid = this.opt.orderid;
		this.ogid = typeof this.opt.ogid == "undefined"?0:this.opt.ogid;
		this.pre_url = app.globalData.pre_url;
		this.type = this.opt.type;
		if(this.type == 'return') {
			uni.setNavigationBarTitle({
			  title: '申请退货退款'
			});
      this.markname = '退款';
		}else if(this.type == 'exchange'){
      uni.setNavigationBarTitle({
        title: '申请换货'
      });
      this.markname = '换货';
    }
		this.getdata();
  },
	onPullDownRefresh: function () {
		this.getdata();
	},
  methods: {
		getdata: function () {
			var that = this;
			that.loading = true;
			app.post('ApiOrder/refundinit', {id: that.orderid, ogid:that.ogid,type:that.type}, function (res) {
				that.loading = false;
				if(res.status == 0) {
					app.alert(res.msg,function(){
						app.goback();return;
					})
				}
				that.tmplids = res.tmplids;
				that.detail = res.detail;
				that.totalprice = that.detail.returnTotalprice;
				that.money = (that.totalprice).toFixed(2);
        if(that.detail.wxtc_deductfee){
          that.sj_wxtc_deductfee = parseFloat(that.money) - parseFloat(that.detail.wxtc_deductfee);
          that.sj_wxtc_deductfee = (that.sj_wxtc_deductfee < 0) ? 0 : that.sj_wxtc_deductfee.toFixed(2);
        }
				var temp = [];
				that.prolist = res.prolist;
				that.order = res.order;
        that.expressdata = res.expressdata || [];
        that.return_address = res.return_address || [];
				var totalcanrefundnum = 0;
				for(var i in that.prolist) {
					temp.push({ogid:that.prolist[i].id,num:that.prolist[i].canRefundNum})
					totalcanrefundnum += that.prolist[i].canRefundNum;
				}
				that.totalcanrefundnum = totalcanrefundnum;
				console.log(temp)
				that.refundNum = temp;
				that.loaded();
			});
		},
		pickerChange: function (e) {
		  this.cindex = e.detail.value;
		},
    formSubmit: function () {
      var that = this;
			if(that.isloading) return;
      var orderid = that.orderid;
      var reason = that.reason;
			var receive = that.cindex;
      var money = parseFloat(that.money);
			var refundNum = that.refundNum;
			var content_pic = that.content_pic;
			var refundtotal = 0;
			for(var i in refundNum) {
				refundtotal += refundNum[i].num;
			}
			if(that.type != 'exchange' && (refundtotal <= 0)) {
				app.alert('请选择要退款的商品');
				return;
			}

      if(that.type == 'exchange'){
        if( Object.keys(that.newpro).length === 0){
          app.alert('请勾选要换货的商品');
          return;
        }
        // for(var i in that.newpro) {
        //   if(!that.newpro[i].ggname){
        //     app.alert('请选择要换新的商品');
        //     return;
        //   }
        // }
        if(!that.express_no || that.express_no == ''){
          app.alert('请填写快递单号');return;
        }
      }

			//if (receive == -1 && that.opt.type == 'refund') {
        //app.alert('请选择货物状态');
        //return;
      //}
      if (!that.detail.wx_express_intracity && reason == '') {
        app.alert('请填写'+that.markname+'原因');
        return;
      }

      if (that.type != 'exchange' && (money < 0 || money > parseFloat(that.totalprice))) {
        app.alert('退款金额有误');
        return;
      }

      var wxtc_cancel_reason = '';
      if(that.detail.wx_express_intracity){
        wxtc_cancel_reason = that.detail.wxtc_cancel_reason_arr[that.wxtc_cancel_reason_index];
      }

			that.isloading = 1;
			app.showLoading('提交中');
      var data = {
        orderid: orderid,
        reason: reason,
        money: money,
        content_pic:content_pic,
        receive:receive,
        refundNum:refundNum,
        type:that.type,
        newpro:that.newpro,
        express_no:that.express_no,
        express_com:that.expressdata[that.express_index],
        wxtc_cancel_reason:wxtc_cancel_reason || '',
        wxtc_deductfee:that.detail.wxtc_deductfee || 0,
      }
      if (that.type == 'exchange') {
        //换货
        var url = 'ApiOrder/exchange';
      }else {
        //退款
        var url = 'ApiOrder/refund';
      }
      app.post(url, data, function (res) {
				app.showLoading(false);
        app.alert(res.msg);
        if (res.status == 1) {
          that.subscribeMessage(function () {
            setTimeout(function () {
              app.goto('detail?id='+that.orderid);
            }, 1000);
          });
        }else{
					that.isloading = 0;
        }
      });
    },
		//加
		gwcplus: function (e) {
		  var index = parseInt(e.currentTarget.dataset.index);
		  var maxnum = parseInt(e.currentTarget.dataset.max);
		  var ogid = e.currentTarget.dataset.ogid;
		  var num = parseInt(e.currentTarget.dataset.num);
		  if (num >= maxnum) {
		    return;
		  }
			var refundNum = this.refundNum;
			refundNum[index].num++;
			this.refundNum = refundNum
			this.calculate();
		},
		//减
		gwcminus: function (e) {
		  var index = parseInt(e.currentTarget.dataset.index);
		  var maxnum = parseInt(e.currentTarget.dataset.max);
		  var ogid = e.currentTarget.dataset.ogid;
		  var num = parseInt(e.currentTarget.dataset.num);
		  if (num == 0) return;
			var refundNum = this.refundNum;
			refundNum[index].num--;
			this.refundNum = refundNum
			this.calculate();
		},
		//输入
		gwcinput: function (e) {
		  var index = parseInt(e.currentTarget.dataset.index);
		  var maxnum = parseInt(e.currentTarget.dataset.max);
		  var ogid = e.currentTarget.dataset.ogid;
		  var num = parseInt(e.currentTarget.dataset.num);
		  var newnum = parseInt(e.detail.value);
		  console.log(num + '--' + newnum);
		  if (num == newnum) return;

		  if (newnum > maxnum) {
		    app.error('请输入正确数量');
		    return;
		  }
			var refundNum = this.refundNum;
			refundNum[index].num = newnum;
			this.refundNum = refundNum
			this.calculate();
		},
    calculate: function () {
			var that = this;
			var total = 0;
			var refundTotalNum = 0;
			for(var i in that.refundNum) {
				if(that.refundNum[i].num == that.prolist[i].num)
					total += parseFloat(that.prolist[i].real_totalmoney);
				else {
					total += that.refundNum[i].num * parseFloat(that.prolist[i].real_totalmoney) / that.prolist[i].num;
				}
				refundTotalNum += that.refundNum[i].num;
			}
			if(refundTotalNum == that.detail.totalNum || refundTotalNum == that.detail.canRefundNum) {
				total = that.detail.returnTotalprice;
			}
			console.log(total)
			total = parseFloat(total);
			if(total > that.detail.returnTotalprice) total = that.detail.returnTotalprice;
			total = total.toFixed(2);
			that.totalprice = total;
			that.money = total;
      if(that.detail.wxtc_deductfee){
        that.sj_wxtc_deductfee = parseFloat(that.money) - parseFloat(that.detail.wxtc_deductfee);
        that.sj_wxtc_deductfee = (that.sj_wxtc_deductfee < 0) ? 0 : that.sj_wxtc_deductfee.toFixed(2);
      }
		},
		moneyInput: function (e) {
			var newmoney = parseFloat(e.detail.value);
			if (newmoney <= 0 || newmoney > parseFloat(this.totalprice)) {
			  app.error('最大退款金额:'+this.totalprice);
			  return;
			}
			this.money = newmoney;
      if(this.detail.wxtc_deductfee){
        this.sj_wxtc_deductfee = parseFloat(this.money) - parseFloat(this.detail.wxtc_deductfee);
        this.sj_wxtc_deductfee = (this.sj_wxtc_deductfee < 0) ? 0 : this.sj_wxtc_deductfee.toFixed(2);
      }
		},
		reasonInput: function (e) {
			this.reason = e.detail.value;
		},
		uploadimg:function(e){
			var that = this;
			var field= e.currentTarget.dataset.field
			var pics = that[field]
			if(!pics) pics = [];
			app.chooseImage(function(urls){
				for(var i=0;i<urls.length;i++){
					pics.push(urls[i]);
				}
			},1)
		},
		removeimg:function(e){
			var that = this;
			var index= e.currentTarget.dataset.index
			var field= e.currentTarget.dataset.field
			var pics = that[field]
			pics.splice(index,1)
		},
    //单选
    changeradio: function (e) {
      var that = this;
      var index = e.currentTarget.dataset.index;
      var proid = e.currentTarget.dataset.proid;
      var ogid = that.ogid = e.currentTarget.dataset.ogid;
      var sell_price = e.currentTarget.dataset.sell_price;
      var cartlist = that.prolist;
      var checked = cartlist[index].checked;
      if(checked){
        cartlist[index].checked = false;
        that.$delete(that.newpro, ogid);
      }else{
        cartlist[index].checked = true;
        that.newpro[ogid] = {proid:proid,ogid:ogid,sell_price:sell_price};
      }
      that.prolist = cartlist;
    },
    buydialogChange: function (e) {
      if(!this.buydialogShow){
        this.proid = e.currentTarget.dataset.proid;
      }
      this.buydialogShow = !this.buydialogShow;
    },
    afteraddcart: function (e) {
       console.log(e)
      if(Number(e.ggprice) != this.newpro[this.ogid].sell_price){
        app.error('只可更换和原订单相同价格的产品');
        return;
      }
      this.newpro[this.ogid].ggname = e.ggname;
      this.newpro[this.ogid].ggid = e.ggid;
    },
    fahuo:function(e){
      var that = this;
      that.express_orderid = e.currentTarget.dataset.id;
      that.$refs.dialogExpress.open();
    },
    expresschange:function(e){
      this.express_index = e.detail.value;
      if(this.express_no){
        this.expressxians = this.expressdata[this.express_index] + '：'+ this.express_no
      }
    },
    dialogExpressClose:function(){
      this.express_no = '';
      this.$refs.dialogExpress.close();
    },
    setexpressno:function(e){
      this.express_no =this.express_no = e.detail.value;
      if(this.express_no){
        this.expressxians = this.expressdata[this.express_index] + '：'+ this.express_no
      }
    },
    refundExpress: function () {
      this.$refs.dialogExpress.close();
    },
    copy:function(e){
      var content = e.currentTarget.dataset.content;
      if(content){
        uni.setClipboardData({
          data: content,
          success:function(){
            app.success('复制成功')
          }
        });
      }
    },
    saoyisao: function (d) {
      var that = this;
      if(app.globalData.platform == 'h5'){
        app.alert('请使用微信扫一扫功能扫码');return;
      }else if(app.globalData.platform == 'mp'){
        // #ifdef H5
        var jweixin = require('jweixin-module');
        jweixin.ready(function () {   //需在用户可能点击分享按钮前就先调用
          jweixin.scanQRCode({
            needResult: 1, // 默认为0，扫描结果由微信处理，1则直接返回扫描结果，
            scanType: ["qrCode","barCode"], // 可以指定扫二维码还是一维码，默认二者都有
            success: function (res) {
              let serialNumber = res.resultStr; // 当needResult 为 1 时，扫码返回的结果
              let serial = serialNumber.split(",");
              serialNumber = serial[serial.length-1];
              that.express_no = serialNumber;
            },
            fail:function(err){
              app.error(err.errMsg);
            }
          });
        });
        // #endif
      }else{
        // #ifndef H5
        uni.scanCode({
          success: function (res) {
            that.express_no = res.result;
          },
          fail:function(err){
            app.error(err.errMsg);
          }
        });
        // #endif
      }
    },
    wxtc_cancel_reasonChange: function (e) {
      this.wxtc_cancel_reason_index = e.detail.value;
    },
  }
};
</script>
<style>
	.num-wrap {position: absolute;right: 0;bottom:24rpx;}
	.num-wrap .text-desc { margin-bottom: -60rpx; color: #999; font-size: 24rpx; text-align: right;}
	.addnum {position: absolute;right: 0;bottom:0rpx;font-size: 30rpx;color: #666;width:auto;display:flex;align-items:center}
	.addnum .plus {width:48rpx;height:36rpx;background:#F6F8F7;display:flex;align-items:center;justify-content:center}
	.addnum .minus {width:48rpx;height:36rpx;background:#F6F8F7;display:flex;align-items:center;justify-content:center}
	.product .addnum .img{width:24rpx;height:24rpx}
	.addnum .i {padding: 0 20rpx;color:#2B2B2B;font-weight:bold;font-size:24rpx}
	.addnum .input{flex:1;width:70rpx;border:0;text-align:center;color:#2B2B2B;font-size:24rpx}

	.form-item4{width:100%;background: #fff; padding: 20rpx 20rpx;margin-top:1px}
	.form-item4 .label{ width:150rpx;}
	.layui-imgbox{margin-right:16rpx;margin-bottom:10rpx;font-size:24rpx;position: relative;}
	.layui-imgbox-img{display: block;width:200rpx;height:200rpx;padding:2px;border: #d3d3d3 1px solid;background-color: #f6f6f6;overflow:hidden}
	.layui-imgbox-img>image{max-width:100%;}
	.layui-imgbox-repeat{position: absolute;display: block;width:32rpx;height:32rpx;line-height:28rpx;right: 2px;bottom:2px;color:#999;font-size:30rpx;background:#fff}
	.uploadbtn{position:relative;height:200rpx;width:200rpx}

.product{width:94%;margin:0 3%;border-radius:8rpx;margin-top:16rpx;padding: 14rpx 3%;background: #FFF;}
.product .content{display:flex;position:relative;width: 100%; padding:16rpx 0px;border-bottom: 1px #e5e5e5 dashed; height: 196rpx;}
.product .content:last-child{ border-bottom: 0; }
.product .content .productimg{ width: 140rpx; height: 140rpx;}
.product .content .detail{display:flex;flex-direction:column;margin-left:14rpx;flex:1}
.product .content .detail .t1{font-size:26rpx;line-height:36rpx;height:72rpx;margin-bottom:10rpx;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:2;overflow:hidden;}
.product .content .detail .t2{height: 46rpx;line-height: 46rpx;color: #999;overflow: hidden;font-size: 26rpx;}
.product .content .detail .t3{display:flex;height:40rpx;line-height:40rpx;color: #ff4246; position: relative;}
.product .content .detail .x1{ flex:1}
.product .content .detail .x2{ width:100rpx;font-size:32rpx;text-align:right;margin-right:8rpx}
.product .content .comment{position:absolute;top:64rpx;right:10rpx;border: 1px #ffc702 solid; border-radius:10rpx;background:#fff; color: #ffc702;  padding: 0 10rpx; height: 46rpx; line-height: 46rpx;}
.product .content .comment2{position:absolute;top:64rpx;right:10rpx;border: 1px #ffc7c2 solid; border-radius:10rpx;background:#fff; color: #ffc7c2;  padding: 0 10rpx; height: 46rpx; line-height: 46rpx;}

.form-content{width:94%;margin:16rpx 3%;border-radius:10rpx;display:flex;flex-direction:column;background:#fff;overflow:hidden}
.form-item{ width:100%;padding: 32rpx 20rpx;}
.form-item .label{ width:100%;height:60rpx;line-height:60rpx}
.form-item .input-item{ width:100%;}
.form-item textarea{ width:100%;height:200rpx;border: 1px #eee solid;padding: 20rpx;}
.form-item input{ width:100%;border: 1px #f5f5f5 solid;padding: 10rpx;height:80rpx}
.form-item .mid{ height:80rpx;line-height:80rpx;padding:0 20rpx;}
.btn{ height:100rpx;line-height: 100rpx;width:90%;margin:0 auto;border-radius:50rpx;margin-top:50rpx;color: #fff;font-size: 30rpx;font-weight:bold}
.radio{flex-shrink:0;width: 32rpx;height: 32rpx;background: #FFFFFF;border: 2rpx solid #BFBFBF;border-radius: 50%;margin-right:30rpx;margin-top: 9%;}
.radio .radio-img{width:100%;height:100%}
.but-left-info .radio{flex-shrink:0;width: 32rpx;height: 32rpx;background: #FFFFFF;border: 2rpx solid #BFBFBF;border-radius: 50%;margin-right:10rpx}
.but-left-info .radio .radio-img{width:100%;height:100%}
.but-left-info .text0{color:#666666;font-size:24rpx;}
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
.danhao-input-view{border: 1px #eee solid;display: flex;align-items: center;flex: 1;}
.danhao-input-view image{width: 60rpx;height: 60rpx;}
  .btn-class{height:45rpx;line-height:45rpx;color:#333;background:#fff;border:1px solid #cdcdcd;border-radius:3px;text-align:center;padding: 0 15rpx;flex-shrink: 0;margin: 0 0 0 10rpx;font-size:24rpx;}
</style>
