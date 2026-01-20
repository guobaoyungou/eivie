<template>
<view class="container">
	<block v-if="isload">
		<block v-if="cartlist.length>0">
			<view class="cartmain">
				<block v-for="(item, index) in cartlist" :key="item.bid">
					<view class="item">
						<view class="btitle">
							<view @tap.stop="changeradio" :data-index="index" class="radio" :style="item.checked ? 'background:'+t('color1')+';border:0' : ''"><image class="radio-img" :src="pre_url+'/static/img/checkd.png'"/></view>
							<view class="btitle-name" @tap="goto" :data-url="item.bid==0?indexurl:'/pagesExt/business/index?id=' + item.business.id">{{item.business.name}}</view>
							<view class="flex1"> </view>
							<view class="btitle-del" @tap="cartdeleteb" :data-bid="item.bid"><image class="img" :src="pre_url+'/static/img/del.png'"/><text style="margin-left:10rpx">删除</text></view>
						</view>
						<view class="content" v-for="(item2,index2) in item.prolist" :key="index2">
							<view @tap.stop="changeradio2" :data-index="index" :data-index2="index2" class="radio" :style="item2.checked ? 'background:'+t('color1')+';border:0' : ''"><image class="radio-img" :src="pre_url+'/static/img/checkd.png'"/></view>
							<view class="proinfo" :style="(item.prolist).length == index2+1 ? 'border:0' : ''">
								<image :src="item2.ggpic?item2.ggpic:item2.product.pic" class="img" @tap="goto" :data-url="'/activity/scoreshop/product?id=' + item2.product.id"/>
								<view class="detail">
									<view class="title"><text>{{item2.product.name}}</text></view>
									<view class="desc"><text v-if="item2.product.ggname">规格：￥{{item2.product.ggname}}</text><text v-else>价值：￥{{item2.product.sell_price}}</text></view>
									<view class="price" :style="{color:t('color1')}"><text v-if="item2.product.money_price>0">￥{{item2.product.money_price}}+</text>{{item2.product.score_price}}{{t('积分')}}</view>

									<view class="addnum">
										<view class="minus" @tap.stop="gwcminus" :data-index="index" :data-index2="index2" :data-cartid="item2.id" :data-num="item2.num"><image class="img" :src="pre_url+'/static/img/cart-minus.png'" /></view>
										<input class="input" @tap.stop="" type="number" :value="item2.num" @blur="gwcinput" :data-max="item2.stock" :data-index="index" :data-index2="index2" :data-cartid="item2.id" :data-num="item2.num"></input>
										<view class="plus" @tap.stop="gwcplus" :data-index="index" :data-index2="index2" :data-max="item2.stock" :data-num="item2.num" :data-cartid="item2.id"><image class="img" :src="pre_url+'/static/img/cart-plus.png'"/></view>
									</view>
								</view>
								<view class="prodel" @tap.stop="cartdelete" :data-cartid="item2.id"><image class="prodel-img" :src="pre_url+'/static/img/del.png'"/></view>
							</view>
						</view>
					</view>
				</block>
			</view>
			
			<view style="height:auto;position:relative">
				<view style="width:100%;height:110rpx"></view>
				<view class="footer flex" :class="menuindex>-1?'tabbarbot':'notabbarbot'">
					<view class="text1">合计：</view>
					<view class="text2" :style="{color:t('color1')}"><text v-if="totalmoney>0">￥{{totalmoney}}+</text><text>{{totalscore}}{{t('积分')}}</text></view>
					<view class="flex1"></view>
					<view class="op" @tap="toOrder" :style="{background:'linear-gradient(-90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'}">去结算</view>
				</view>
			</view>

		</block>
		<block v-else>
			<view class="data-empty">
				<image :src="pre_url+'/static/img/cartnull.png'" class="data-empty-img" style="width:120rpx;height:120rpx"/>
				<view class="data-empty-text" style="margin-top:20rpx;font-size:24rpx">购物车空空如也~</view>
				<button style="width:400rpx;border:1px solid #ff6801;border-radius:6rpx;background:#ff6801;margin-top:20px;color:#fff" @tap="goto" :data-url="scoreshopindexurl">去选购</button>
			</view>
		</block>
	</block>
	<loading v-if="loading"></loading>
	<dp-tabbar :opt="opt" @getmenuindex="getmenuindex"></dp-tabbar>
	<popmsg ref="popmsg"></popmsg>
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
			initdata:{},
			pre_url:app.globalData.pre_url,
			scoreshopindexurl:'/activity/scoreshop/index',
			indexurl:app.globalData.indexurl,

			cartlist:[],
			totalscore:0,
      totalmoney: '0.00',
      selectedcount: 0,
    };
  },
  
  onLoad: function (opt) {
		this.opt = app.getopts(opt);
  },
	onShow:function(){
		this.getdata();
	},
	onPullDownRefresh: function () {
		this.getdata();
	},
  methods: {
    getdata: function (){
      var that = this;
			var bid = that.opt.bid ? that.opt.bid : '';
			if(bid){
				that.scoreshopindexurl = '/activity/scoreshop/index?bid='+bid;
			}
			that.loading = true;
      app.get('ApiScoreshop/cart', {bid:bid,isnew:1}, function (res) {
				that.loading = false;
        that.cartlist = res.cartlist;
        var cartlist = res.cartlist;
        for (var i in cartlist) {
					cartlist[i].checked = true;
        }
        that.calculate();
				that.loaded();
      });
    },
    calculate: function () {
      var that = this;
      var cartlist = that.cartlist;
      var totalmoney = 0.00;
      var totalscore = 0;
      var selectedcount = 0;
      for (var i in cartlist) {
				for(var j in cartlist[i].prolist){
					if(cartlist[i].prolist[j].checked){
						var thispro = cartlist[i].prolist[j];
						totalmoney += thispro.product.money_price * thispro.num;
						totalscore += thispro.product.score_price * thispro.num;
						selectedcount += thispro.num;
					}
				}
      }
      that.totalmoney = totalmoney.toFixed(2);
      that.totalscore = totalscore;
      that.selectedcount = selectedcount;
    },
    changeradio: function (e) {
			var that = this;
			var index = e.currentTarget.dataset.index;
			var cartlist = that.cartlist;
			var checked = cartlist[index].checked;
			if(checked){
				cartlist[index].checked = false;
			}else{
				cartlist[index].checked = true;
			}
			for(var i in cartlist[index].prolist){
				cartlist[index].prolist[i].checked = cartlist[index].checked;
			}
			that.cartlist = cartlist;
			that.calculate();
    },
    changeradio2: function (e) {
			var that = this;
			var index = e.currentTarget.dataset.index;
			var index2 = e.currentTarget.dataset.index2;
			var cartlist = that.cartlist;
			var checked = cartlist[index].prolist[index2].checked;
			if(checked){
				cartlist[index].prolist[index2].checked = false;
			}else{
				cartlist[index].prolist[index2].checked = true;
			}
			var isallchecked = true;
			for(var i in cartlist[index].prolist){
				if(cartlist[index].prolist[i].checked == false){
					isallchecked = false;
				}
			}
			if(isallchecked){
				cartlist[index].checked = true;
			}else{
				cartlist[index].checked = false;
			}
			that.cartlist = cartlist;
			that.calculate();
    },
    cartdelete: function (e) {
      var that = this;
      var id = e.currentTarget.dataset.cartid;
      app.confirm('确定要从购物车移除吗?', function () {
        app.post('ApiScoreshop/cartdelete', {id: id}, function (data) {
          app.success('操作成功');
          setTimeout(function () {
            that.getdata();
          }, 1000);
        });
      });
    },
		cartdeleteb:function(e){
			var that = this;
			var bid   = e.currentTarget.dataset.bid;
			app.confirm('确定要从购物车移除吗?', function () {
				app.post('ApiScoreshop/cartdelete', {bid: bid}, function (data) {
					app.success('操作成功');
					setTimeout(function () {
						that.getdata();
					}, 1000);
				});
			});
		},
    toOrder: function () {
      var that = this;
			var cartlist = that.cartlist;
			var ids = [];
			var prodata = [];
			for(var i in cartlist){
				for(var j in cartlist[i].prolist){
					if(cartlist[i].prolist[j].checked){
						var thispro = cartlist[i].prolist[j];
						var tmpprostr = thispro.product.id + ',' + thispro.num + ',' + thispro.ggid;
						prodata.push(tmpprostr);
					}
				}
			}
			if (prodata == undefined || prodata.length == 0) {
				app.error('请先选择产品');
				return;
			}
			app.goto('buy?&prodata=' + prodata.join('-'));
    },
    //加
    gwcplus: function (e) {
			var index  = parseInt(e.currentTarget.dataset.index);
			var index2 = parseInt(e.currentTarget.dataset.index2);
			var cartid = e.currentTarget.dataset.cartid;
			var num = parseInt(e.currentTarget.dataset.num);
			var maxnum = parseInt(e.currentTarget.dataset.max);
			if (num >= maxnum) {
					app.error('库存不足');
					return;
			}
			var cartlist = this.cartlist;
			cartlist[index].prolist[index2].num++;
			this.cartlist = cartlist
			this.calculate();
			var that = this;
			app.post('ApiScoreshop/cartChangenum', {id: cartid,num: num + 1}, function (data){
				if (data.status == 1) {
					//that.getdata();
				} else {
					app.error(data.msg);
					cartlist[index].prolist[index2].num--;
				}
			});
    },
    //减
    gwcminus: function (e) {
			var index = parseInt(e.currentTarget.dataset.index);
			var index2 = parseInt(e.currentTarget.dataset.index2);
			var cartid = e.currentTarget.dataset.cartid;
			var num = parseInt(e.currentTarget.dataset.num);
			if (num == 1) return;
			var maxnum = parseInt(e.currentTarget.dataset.max);
			var cartlist = this.cartlist;
			cartlist[index].prolist[index2].num--;
			this.cartlist = cartlist
			this.calculate();

			var that = this;
			app.post('ApiScoreshop/cartChangenum', {id: cartid,num: num - 1}, function (data) {
				if (data.status == 1) {
					//that.getdata();
				} else {
					app.error(data.msg);
					cartlist[index].prolist[index2].num++;
				}
			});
    },
    //输入
    gwcinput: function (e) {
			var index = parseInt(e.currentTarget.dataset.index);
			var index2 = parseInt(e.currentTarget.dataset.index2);
			var maxnum = parseInt(e.currentTarget.dataset.max);
			var cartid = e.currentTarget.dataset.cartid;
			var num = e.currentTarget.dataset.num;
			var newnum = parseInt(e.detail.value);
			if (num == newnum) return;
			if (newnum < 1) {
				app.error('最小数量为1');
				return;
			}
			if (newnum > maxnum) {
				app.error('库存不足');
				return;
			}
			var cartlist = this.cartlist;
			cartlist[index].prolist[index2].num = newnum;
			this.cartlist = cartlist
			this.calculate();
			
			var that = this;
			app.post('ApiScoreshop/cartChangenum', {id: cartid,num: newnum}, function (data) {
				if (data.status == 1) {
					//that.getdata();
				} else {
					app.error(data.msg);
				}
			});
    },
		addcart:function(){
			this.getdata();
		}
  }
};
</script>
<style>
.container{height:100%}
.cartmain .item {width: 94%;margin:20rpx 3%;background: #fff;border-radius:20rpx;padding:30rpx 3%;}
.cartmain .item .radio{flex-shrink:0;width: 32rpx;height: 32rpx;background: #FFFFFF;border: 2rpx solid #BFBFBF;border-radius: 50%;margin-right:30rpx}
.cartmain .item .radio .radio-img{width:100%;height:100%}

.cartmain .item .btitle{width:100%;display:flex;align-items:center;margin-bottom:30rpx}
.cartmain .item .btitle-name{color:#222222;font-weight:bold;font-size:28rpx;}
.cartmain .item .btitle-del{display:flex;align-items:center;color:#999999;font-size:24rpx;}
.cartmain .item .btitle-del .img{width:24rpx;height:24rpx}

.cartmain .item .content {width:100%;position: relative;display:flex;align-items:center;}
.cartmain .item .content .proinfo{flex:1;display:flex;padding:20rpx 0;border-bottom:1px solid #f2f2f2}
.cartmain .item .content .proinfo .img {width: 150rpx;height: 150rpx;}
.cartmain .item .content .detail {flex:1;margin-left:20rpx;height: 150rpx;position: relative;}
.cartmain .item .content .detail .title {color: #222222;font-weight:bold;font-size:28rpx;line-height:34rpx;height:68rpx;margin-bottom:0;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:2;overflow:hidden;}
.cartmain .item .content .detail .desc {height: 30rpx;line-height: 30rpx;color: #999;overflow: hidden;font-size: 20rpx;}
.cartmain .item .content .prodel{width:24rpx;height:24rpx;position:absolute;top:60rpx;right:0}
.cartmain .item .content .prodel-img{width:100%;height:100%}
.cartmain .item .content .price{height:60rpx;line-height:60rpx;font-size:28rpx;font-weight:bold;display:flex;align-items:center}
.cartmain .item .content .addnum {position: absolute;right: 0;bottom:0rpx;font-size: 30rpx;color: #666;width:auto;display:flex;align-items:center}
.cartmain .item .content .addnum .plus {width:65rpx;height:48rpx;background:#F6F8F7;display:flex;align-items:center;justify-content:center}
.cartmain .item .content .addnum .minus {width:65rpx;height:48rpx;background:#F6F8F7;display:flex;align-items:center;justify-content:center}
.cartmain .item .content .addnum .img{width:24rpx;height:24rpx}
.cartmain .item .content .addnum .i {padding: 0 20rpx;color:#2B2B2B;font-weight:bold;font-size:24rpx}
.cartmain .item .content .addnum .input{flex:1;width:50rpx;border:0;text-align:center;color:#2B2B2B;font-size:24rpx;margin: 0 15rpx;}

.cartmain .item .bottom{width: 94%;margin: 0 3%;border-top: 1px #e5e5e5 solid;padding: 10rpx 0px;overflow: hidden;color: #ccc;display:flex;align-items:center;justify-content:flex-end}
.cartmain .item .bottom .f1{display:flex;align-items:center;color:#333}
.cartmain .item .bottom .f1 image{width:40rpx;height:40rpx;border-radius:4px;margin-right:4px}
.cartmain .item .bottom .op {border: 1px #ff4246 solid;border-radius: 10rpx;color: #ff4246;padding: 0 10rpx;height: 46rpx;line-height: 46rpx;margin-left: 10rpx;}

.footer {width: 100%;background: #fff;margin-top: 5px;position: fixed;left: 0px;bottom: 0px;z-index:8;display:flex;align-items:center;padding:0 20rpx;border-top:1px solid #EFEFEF}
.footer .radio{flex-shrink:0;width: 32rpx;height: 32rpx;background: #FFFFFF;border: 2rpx solid #BFBFBF;border-radius: 50%;margin-right:10rpx}
.footer .radio .radio-img{width:100%;height:100%}
.footer .text0{color:#666666;font-size:24rpx;}
.footer .text1 {height: 110rpx;line-height: 110rpx;color:#444;font-weight:bold;font-size:24rpx;}
.footer .text2 {color: #F64D00;font-size: 36rpx;font-weight:bold}
.footer .text3 {color: #F64D00;font-size: 28rpx;font-weight:bold}
.footer .op{width: 216rpx;height: 80rpx;line-height:80rpx;border-radius: 6rpx;font-weight:bold;color:#fff;font-size:28rpx;text-align:center;margin-left:30rpx}

.xihuan{height: auto;overflow: hidden;display:flex;align-items:center;width:100%;padding:12rpx 160rpx}
.xihuan-line{height: auto; padding: 0; overflow: hidden;flex:1;height:0;border-top:1px solid #eee}
.xihuan-text{padding:0 32rpx;text-align:center;display:flex;align-items:center;justify-content:center}
.xihuan-text .txt{color:#111;font-size:30rpx}
.xihuan-text .img{text-align:center;width:36rpx;height:36rpx;margin-right:12rpx}

.prolist{width: 100%;height:auto;padding: 8rpx 20rpx;}

.data-empty {width: 100%; text-align: center; padding-top:100rpx;padding-bottom:100rpx}
.data-empty-img{ width: 300rpx; height: 300rpx; display: inline-block; }
.data-empty-text{ display: block; text-align: center; color: #999999; font-size:32rpx; width: 100%; margin-top: 30rpx; } 
</style>