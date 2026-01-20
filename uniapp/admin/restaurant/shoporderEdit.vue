<template>
<view>
	<block v-if="isload">
		<form @submit="subform">
			<view class="form-box">
				<view class="form-item">
					<view class="f1">桌台<text style="color:red"> </text></view>
					<view class="f2">{{info.tableName}}</view>
				</view>
				<view class="form-item">
					<view class="f1">订单号<text style="color:red"> </text></view>
					<view class="f2" >{{info.ordernum}}</view>
				</view>
			</view>

			<!-- 列表 -->
			<view class="form-box" v-for="(item,index) in ordergoods" :key="index">
				<view class="form-item" style="height:80rpx;line-height:80rpx">
					<view class="f1">菜品名称</view>
					<view class="f2 product-name" style="font-weight:bold">{{item.name}}</view>
				</view>
				<view class="form-item" style="height:80rpx;line-height:80rpx" v-if="item.ggname">
					<view class="f1">规格</view>
					<view class="f2">{{item.ggname}}</view>
				</view>
				<view class="form-item" style="line-height:80rpx" v-if="item.ggtext && item.ggtext.length">
					<view class="f1">套餐</view>
					<view class=" flex-col">
						<block v-for="(item2,index) in item.ggtext" >
							<text style="line-height: 40rpx;text-align: left;">{{item2}}</text>
						</block>
					</view>
				</view>
				<view class="form-item" style="height:80rpx;line-height:80rpx">
					<view class="f1">价格（元）</view>
					<view class="f2"><input type="text" @input="ginput" :data-index="index" data-field="sell_price" :name="'sell_price['+index+']'" :value="item.sell_price" placeholder="请填写销售价" placeholder-style="color:#888"></input></view>
				</view>
				<view class="form-item" style="height:80rpx;line-height:80rpx">
					<view class="f1" v-if="item.product_type && item.product_type ==1">重量</view>
					<view class="f1" v-else>数量</view>
					
					<view class="f2"  v-if="item.product_type && item.product_type ==1">
						<input type="text" @input="ginput" :data-index="index" data-field="num" :name="'num['+index+']'" :value="item.num" placeholder="请填写重量" placeholder-style="color:#888"></input>斤
						
					</view>
					
					<view class="f2"  v-else>
						<input type="text" @input="ginput" :data-index="index" data-field="num" :name="'num['+index+']'" :value="item.num" placeholder="请填写数量" placeholder-style="color:#888"></input>
						
					</view>
				</view>
				<!-- <view class="form-item">
					<view class="f1">规格图片</view>
					<view class="f2" style="flex-wrap:wrap;margin-top:20rpx;margin-bottom:20rpx">
						<view class="layui-imgbox" v-if="item.pic!=''">
							<view class="layui-imgbox-close" @tap="removeimg2" :data-index="index"><image style="display:block" :src="pre_url+'/static/img/ico-del.png'"></image></view>
							<view class="layui-imgbox-img"><image :src="item.pic" @tap="previewImage" :data-url="item.pic" mode="widthFix"></image></view>
						</view>
						<view class="uploadbtn" v-else :style="'background:url('+pre_url+'/static/img/shaitu_icon.png) no-repeat 60rpx;background-size:80rpx 80rpx;background-color:#F3F3F3;'" @tap="uploadimg2" :data-index="index"></view>
					</view>
				</view> -->
			</view>

			<view class="form-box">
				<view class="form-item">
					<view class="f1">菜品总价</view>
					<view class="f2"><input type="number" name="product_price" @input="input" disabled="true" data-field="product_price" :value="info.product_price"  placeholder-style="color:#888"></input></view>
				</view>
				<view class="form-item">
					<view class="f1">会员折扣</view>
					<view class="f2"><input type="digit" name="leveldk_money" @input="input" data-field="leveldk_money" :value="info.leveldk_money" placeholder-style="color:#888"></input></view>
				</view>
				<view class="form-item">
					<view class="f1">优惠券抵扣</view>
					<view class="f2"><input type="number" name="coupon_money" @input="input" data-field="coupon_money" :value="info.coupon_money"  placeholder-style="color:#888"></input></view>
				</view>
				<view class="form-item">
					<view class="f1">积分抵扣</view>
					<view class="f2"><input type="number" name="scoredk_money" @input="input" data-field="scoredk_money" :value="info.scoredk_money"  placeholder-style="color:#888"></input></view>
				</view>
				<view class="form-item">
					<view class="f1">实付款</view>
					<view class="f2"><input type="number" name="totalprice" @input="input" data-field="totalprice" :value="info.totalprice"  placeholder-style="color:#888"></input></view>
				</view>
			</view>
	
			<button class="savebtn" :style="'background:linear-gradient(90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'" form-type="submit">提交</button>
			<view style="height:50rpx"></view>
		</form>
		
	</block>
	<loading v-if="loading"></loading>
</view>
</template>

<script>
var app = getApp();

export default {
  data() {
    return {
			isload:false,
			loading:false,
			pre_url:app.globalData.pre_url,
      info:{},
			ordergoods:[]
    };
  },

  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.getdata();
  },
  methods: {
		getdata:function(){
			var that = this;
			var id = that.opt.id ? that.opt.id : '';
			that.loading = true;
			app.get('ApiAdminRestaurantShopOrder/edit',{id:id}, function (res) {
				that.loading = false;
				that.info = res.info;
				that.ordergoods = res.order_goods;
				that.loaded();
			});
		},
		ginput: function (e) {
			var that = this;
		  var value = e.detail.value;
			var index = e.currentTarget.dataset.index;
			var field = e.currentTarget.dataset.field;
			var ordergoods = that.ordergoods;
			var total = 0;
			if(value < 0 || value == '') {
				app.error('请输入正确的数值');return;
			}
			ordergoods[index][field] = value;
			for (var i in ordergoods) {
				if(ordergoods[i].type && ordergoods[i].type==1){
					total += ordergoods[i].weigh * ordergoods[i].sell_price;
				}else{
					total += ordergoods[i].num * ordergoods[i].sell_price;
				}
			}
			
			that.info.product_price = total;
			var totalprice = total - that.info.leveldk_money - that.info.coupon_money - that.info.scoredk_money;
			totalprice = parseFloat(totalprice);
			totalprice = totalprice.toFixed(2)
			if(totalprice < 0) {
				app.error('总金额不能小于0');return;
			}
			
			that.info.totalprice = totalprice;
		},
		input: function (e) {
			var that = this;
		  var value = e.detail.value;
			var field = e.currentTarget.dataset.field;
			if(value < 0 || value =='') {
				app.error('请输入正确的金额');return;
			}
			that.info[field] = value;
			
			var totalprice = that.info.product_price - that.info.leveldk_money - that.info.coupon_money - that.info.scoredk_money;
			totalprice = parseFloat(totalprice);
			totalprice = totalprice.toFixed(2)
			if(totalprice < 0) {
				app.error('总金额不能小于0');return;
			}
			that.info.totalprice = totalprice;
		},
    subform: function (e) {
      var that = this;
      var formdata = e.detail.value;
			if(that.info.totalprice < 0) {
				app.error('总金额不能小于0');return;
			}
      var id = that.opt.id ? that.opt.id : '';
      app.post('ApiAdminRestaurantShopOrder/edit', {id:id,info:formdata,goods:that.ordergoods}, function (res) {
        if (res.status == 0) {
          app.error(res.msg);
        } else {
          app.success(res.msg);
          setTimeout(function () {
            app.goto('tableWaiterDetail?id=' + that.info.tableid, 'redirect');
          }, 1000);
        }
      });
    },
  }
};
</script>
<style>
radio{transform: scale(0.6);}
checkbox{transform: scale(0.6);}
.form-box{ padding:2rpx 24rpx 0 24rpx; background: #fff;margin: 24rpx;border-radius: 10rpx}
.form-item{ line-height: 100rpx; display: flex;justify-content: space-between;border-bottom:1px solid #eee }
.form-item .f1{color:#222;width:200rpx;flex-shrink:0}
.form-item .f2{display:flex;align-items:center;}
.form-item .product-name {display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:1;overflow:hidden;}
.form-box .form-item:last-child{ border:none}
.form-box .flex-col{padding-bottom:20rpx}
.form-item input{ width: 100%; border: none;color:#111;font-size:28rpx; text-align: right}
.form-item textarea{ width:100%;min-height:200rpx;padding:20rpx 0;border: none;}
.form-item .upload_pic{ margin:50rpx 0;background: #F3F3F3;width:90rpx;height:90rpx; text-align: center  }
.form-item .upload_pic image{ width: 32rpx;height: 32rpx; }
.savebtn{ width: 90%; height:96rpx; line-height: 96rpx; text-align:center;border-radius:48rpx; color: #fff;font-weight:bold;margin: 0 5%; margin-top:60rpx; border: none; }

.ggtitle{height:60rpx;line-height:60rpx;color:#111;font-weight:bold;font-size:26rpx;display:flex;border-bottom:1px solid #f4f4f4}
.ggtitle .t1{width:200rpx;}
.ggcontent{line-height:60rpx;margin-top:10rpx;color:#111;font-size:26rpx;display:flex}
.ggcontent .t1{width:200rpx;display:flex;align-items:center;flex-shrink:0}
.ggcontent .t1 .edit{width:40rpx;height:40rpx}
.ggcontent .t2{display:flex;flex-wrap:wrap;align-items:center}
.ggcontent .ggname{background:#f55;color:#fff;height:40rpx;line-height:40rpx;padding:0 20rpx;border-radius:8rpx;margin-right:20rpx;margin-bottom:10rpx;font-size:24rpx;position:relative}
.ggcontent .ggname .close{position:absolute;top:-14rpx;right:-14rpx;background:#fff;height:28rpx;width:28rpx;border-radius:14rpx}
.ggcontent .ggnameadd{background:#ccc;font-size:36rpx;color:#fff;height:40rpx;line-height:40rpx;padding:0 20rpx;border-radius:8rpx;margin-right:20rpx;margin-left:10rpx;position:relative}
.ggcontent .ggadd{font-size:26rpx;color:#558}

.ggbox{line-height:50rpx;}


.layui-imgbox{margin-right:16rpx;margin-bottom:10rpx;font-size:24rpx;position: relative;}
.layui-imgbox-img{display: block;width:200rpx;height:200rpx;padding:2px;border: #d3d3d3 1px solid;background-color: #f6f6f6;overflow:hidden}
.layui-imgbox-img>image{max-width:100%;}
.layui-imgbox-repeat{position: absolute;display: block;width:32rpx;height:32rpx;line-height:28rpx;right: 2px;bottom:2px;color:#999;font-size:30rpx;background:#fff}
.uploadbtn{position:relative;height:200rpx;width:200rpx}


.clist-item{display:flex;border-bottom: 1px solid #f5f5f5;padding:20rpx 30rpx;}
.radio{flex-shrink:0;width: 32rpx;height: 32rpx;background: #FFFFFF;border: 2rpx solid #BFBFBF;border-radius: 50%;margin-right:30rpx}
.radio .radio-img{width:100%;height:100%;display:block}

.uni-button-color {color: #007aff;}
</style>