<template>
<view>
	<block v-if="isload">
		<form @submit="subform">
      <view class="form-box">
        <view class="form-item">
					<view class="f1">用户ID<text style="color:red"> *</text></view>
          <view class="f2"><input type="text" name="mid" :value="info.mid" placeholder="请填写用户ID" placeholder-style="color:#888"></input></view>
        </view>
        <view class="form-item">
          <view class="f1">剩余天数<text style="color:red"> *</text></view>
          <view class="f2"><input type="text" name="day" :value="info.day" placeholder="请填写剩余天数" placeholder-style="color:#888"></input></view>
        </view>
        <view class="form-item" >
          <view class="flex flex-col addshow-list-view">
            <view class="flex title-view">
              <view>适用滤芯<text style="color:red"> *</text></view>
              <view class="but-class" v-if="!giftProductsList.length" :style="'background:linear-gradient(90deg,'+tColor('color1')+' 0%,rgba('+tColor('color1rgb')+',0.8) 100%)'" @click="addshop(1)">选择商品</view>
            </view>
            <view class="product" v-if="giftProductsList.length">
              <block v-for="(item, index2) in giftProductsList" :key="index2">
                <view class="item flex">
                  <view class="img-view">
                    <image v-if="item.pic" :src="item.pic"></image>
                    <view v-else class="img-view-empty"></view>
                  </view>
                  <view class="info">
                    <view class="f1">{{item.name}}</view>
                  </view>
                  <view class="del-view flex-y-center" @tap.stop="clearShopCartFn(item.id,1)" style="color:#999999;font-size:24rpx"><image :src="pre_url+'/static/img/del.png'" style="width:24rpx;height:24rpx;margin-right:6rpx"/></view>
                </view>
              </block>
            </view>
          </view>
        </view>
      </view>
			<!-- 编辑 & 添加 -->
			<button class="savebtn" :style="'background:linear-gradient(90deg,'+tColor('color1')+' 0%,rgba('+tColor('color1rgb')+',0.8) 100%)'" form-type="submit">提交</button>
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
			isload:true,
			loading:false,
			pre_url:app.globalData.pre_url,
      info:{},
			memberlevel:[],
			pic:[],
			product_showset:0,
			showtjArr:['-1'],
      zhif:0,
			giftProductsList:[],
			giftServiceList:[],
			buyproGiveNum:[],
			giftProductsLists:[],
			pageType:false,
			bid:0,
			auth:[],
			restaurant:false,
			editType:'',
			proid:0,
    };
  },

  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.editType = opt.type;
  },
	onShow() {
    uni.$off();
    let that = this;
    uni.$once('shopDataEmit',function(e){
      that.proid = e.id;
      setTimeout(() => {
        let nowArr = that.giftProductsList.map(item => item.id);
        if(nowArr.includes(e.id)) {
          setTimeout(() => {
            uni.showToast({icon:'none',title:'该商品已添加过了'}) ;
          },600);
          return;
        }
        that.giftProductsList.push(e);
        let idArr = that.giftProductsList.map(item => item.id);
        that.info.buyproids = idArr.join(',');
        that.info.buypro_give_num = that.giftProductsList.map(item => item.give_num);
      })
    })
	},	
	onUnload(){
		
	},
  methods: {
    tColor(text){
        let that = this;
        if(text=='color1'){
          if(app.globalData.initdata.color1 == undefined){
            let timer = setInterval(() => {
              that.tColor('color1')
            },1000)
            clearInterval(timer)
          }else{
            return app.globalData.initdata.color1;
          }
        }else if(text=='color2'){
          return app.globalData.initdata.color2;
        }else if(text=='color1rgb'){
          if(app.globalData.initdata.color1rgb == undefined){
            setTimeout(() => {
              that.tColor('color1rgb')
            },1000)
          }else{
            var color1rgb = app.globalData.initdata.color1rgb;
            return color1rgb['red']+','+color1rgb['green']+','+color1rgb['blue'];
          }
        }else if(text=='color2rgb'){
          var color2rgb = app.globalData.initdata.color2rgb;
          return color2rgb['red']+','+color2rgb['green']+','+color2rgb['blue'];
        }else{
          return app.globalData.initdata.textset[text] || text;
        }
    },
		clearShopCartFn: function (id,type) {
		  var that = this;
			uni.showModal({
				title: '提示',
				content: '确认删除商品吗？',
				success: function (res) {
					if (res.confirm) {
						if(type){
							let ArrarList  = that.giftProductsList.filter(item => item.id != id);
							that.giftProductsList = ArrarList;
						}else{
							let ArrarList  = that.productdata.filter(item => item.id != id);
							that.productdata = ArrarList;
						}
					} else if (res.cancel) {
					}
          that.proid = 0;
				}
			});
		},
    // 添加普通商品
    addshop(type){
      this.addshopType = type;
      uni.navigateTo({
        url:'/admin/order/dkfastbuy?bid=0&coupon=1'
      })
    },
		getdata:function(URL,type){
			var that = this;
			var id = that.opt.id ? that.opt.id : '';
			that.loading = true;
			app.get(URL,{id:id}, function (res) {
				that.loading = false;
				that.loaded();
			});
		},
    subform: function (e) {
      var that = this;
      var formdata = e.detail.value;
      formdata.proid = that.proid;
			if(!formdata.mid) return app.alert('请填写用户ID');
			if(!formdata.day) return app.alert('请填写剩余天数');
			if(!formdata.proid) return app.alert('请选择适用滤芯');
      that.loading = true;
      app.post('ApiAdminProduct/savelvxin', {formdata}, function (res) {
        that.loading = false;
        if (res.status == 1) {
					app.success(res.msg);
					setTimeout(function () {
            that.info = {};
            let ArrarList  = that.giftProductsList.filter(item => item.id != that.proid);
            that.giftProductsList = ArrarList;
            that.proid = 0;
            that.loaded();
					}, 1000);
        } else {
					app.error(res.msg);
        }
      });
    },
		bindStartTime2Change:function(e){
			this.start_time2 = e.target.value
		},
		bindEndTime1Change:function(e){
			this.end_time1 = e.target.value
		},
		bindEndTime2Change:function(e){
			this.end_time2 = e.target.value
		},
  }
};
</script>
<style>
radio{transform: scale(0.6);}
checkbox{transform: scale(0.6);}
.form-box{ padding:2rpx 20rpx 0 20rpx; background: #fff;margin: 20rpx;border-radius: 10rpx}
.form-item{ line-height: 100rpx; display: flex;justify-content: space-between;border-bottom:1px solid #eee }
.form-item .f1{color:#222;width:200rpx;flex-shrink:0}
.form-item .f2{display:flex;align-items:center}
.form-box .form-item:last-child{ border:none}
.form-box .flex-col{padding-bottom:20rpx}
.form-item input{ width: 100%; border: none;color:#111;font-size:28rpx; text-align: right}
.form-item textarea{ width:100%;min-height:100rpx;border: none;}
.form-item .upload_pic{ margin:50rpx 0;background: #F3F3F3;width:90rpx;height:90rpx; text-align: center  }
.form-item .upload_pic image{ width: 32rpx;height: 32rpx; }
.form-item .but-class{width: 150rpx;height: 50rpx;line-height: 50rpx;color: #fff;text-align: center;font-size: 24rpx;border-radius:35rpx;background: red;}
.savebtn{ width: 90%; height:96rpx; line-height: 96rpx; text-align:center;border-radius:48rpx; color: #fff;font-weight:bold;margin: 0 5%; margin-top:60rpx; border: none;background: red;}
.addshow-list-view{width: 100%;}
.addshow-list-view .title-view{flex: 1;justify-content: space-between;align-items: center;}
.addshow-list-view .product {width: 100%;}
.addshow-list-view .product .item {position: relative;width: 100%;padding: 0rpx 0 20rpx;align-items: center;}
.addshow-list-view .product .img-view{width: 140rpx;height: 140rpx;border-radius: 10rpx;overflow: hidden;}
.addshow-list-view .product .img-view .img-view-empty{width: 100%;height: 100%;background: #eee;}
.addshow-list-view .product .img-view image {width: 100%;height: 100%;}
.addshow-list-view .product .info .modify-price{padding: 0rpx 0rpx;}
.product .info .modify-price .inputPrice {border: 1px solid #ddd; width: 200rpx; height: 40rpx; border-radius: 10rpx; padding: 0 4rpx;text-align: left;}
.addshow-list-view .product .del-view{position: absolute;right: 10rpx;top: 50%;margin-top: -7px;padding: 10rpx;}
.addshow-list-view .product .del-view-class{padding: 10rpx;color:#999999;font-size:24rpx}
.addshow-list-view .product .info {padding-left: 20rpx;flex: 1;height:140rpx;}
.addshow-list-view .product .info .f1 {color: #222222;font-weight: bold;font-size: 24rpx;line-height: 32rpx;display: -webkit-box;-webkit-box-orient: vertical;-webkit-line-clamp: 2;overflow: hidden;
width: 96%;}
.addshow-list-view .product .info .f2 {color: #999999;font-size: 24rpx;white-space: nowrap;}
.amount-range-view{align-items: center;justify-content: flex-end;}
.amount-range-view input{text-align: left;}
.radio-group-view{display: flex;align-items: center;flex-wrap: wrap;justify-content: flex-start;}
.radio-group-view label{white-space: nowrap;margin-right: 20rpx;}
</style>