<template>
<view class="container">
	<block v-if="isload">
		<view>
				<view class="search-container" :style="{background:t('color1')}">
					<view class="search-box">
						<picker style="font-size:28rpx;padding:10rpx;height:70rpx;border-radius:4px;flex:1;"  @change="expresschange" :value="express_index" :range="expressdata" >
							<view class="picker"  @change="expresschange" :value="express_index" :range="expressdata" >{{expressdata[express_index]?expressdata[express_index]:'请选择快递公司'}}</view>
						</picker>
						<image class="img" :src="pre_url+'/static/img/exp_xiala.png'"></image>
						<input class="search-text" :value="keyword"  @confirm="searchConfirm" @input="searchChange" placeholder="输入快递单号或扫码查询" placeholder-style="color:#fff;font-size:24rpx"/>
						<view class="set" @tap="saoyisao">
							<image :src="pre_url+'/static/img/ico-scan-white.png'" class="img"></image>
						</view>
					</view>
				</view>
				<view class="wrap">
						<view class="top_title flex">
								<view class="tab" @tap="goto" data-url="mail"><image :src="pre_url+'/static/img/exp_mail.png'"></image><text>我要寄件</text></view>
								<view class="tab"  @tap="goto" data-url="/pages/my/usercenter"><image :src="pre_url+'/static/img/exp_my.png'"></image><text>个人中心</text></view>	
						</view>		
						<view class="list_box">
								<view class="tab-box flex">
										<view  :class="'tab2 ' + (curTopIndex == 1 ? 'on' : '')" :data-index="1" @tap="switchTopTab" >最近查询</view>
										<view  :class="'tab2 ' + (curTopIndex == 2 ? 'on' : '')" :data-index="2" @tap="switchTopTab" >我的寄件</view>
								</view>
								<view class="list" v-for="(item, index) in datalist" :key="index" v-if="curTopIndex==1"  @tap="goto" :data-url="'logistics?com='+item.company+'&num='+item.num">
										<view class="text1 flex"><view>{{item.company}}:{{item.num}}<image class="fuzhi" :src="pre_url+'/static/img/exp_fuzhi.png'"></image></view><text class="t3">查</text></view>
										<view class="text2 flex">
												<view class="t1_box"><text>{{item.text}}</text></view>
										</view>
								</view>
								<view class="list" v-for="(item, index) in datalist" :key="index" v-if="curTopIndex==2" @tap="goto" :data-url="'kddetail?id='+item.id" >
										<view class="text1 flex"><view class="com">{{item.company}}</view><text class="t4">{{item.status}}</text></view>
										<view class="text2 flex">
												<view class="t2_box"><text >{{item.sendManPrintCity}}</text><text class="t2">{{item.sendManName}}</text></view>
												<view class="t2_box"><image class="jiantou"  :src="pre_url+'/static/img/jiantou2.png'"></image></view>
												<view class="t2_box"><text>{{item.recManPrintCity}}</text><text class="t2">{{item.recManName}}</text></view>
										</view>
								</view>
								<nodata v-if="nodata"></nodata>
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
			pagenum: 1,
			nomore: false,
			nodata:false,
			datalist:[],
			pre_url:app.globalData.pre_url,
			platform:'',
			platformname:'',
			platformimg:'weixin',
      smsdjs: '',
			tel:'',
      hqing: 0,
			frompage:'/pagesB/express/mail',
			address: [],
			expressdata:[],
			express_index:-1,
			express_no:'',
			pstimeDialogShow: false,
			pstimetext:'',
			sm_time:'',
			address2:[],

			keyword:'',
			curTopIndex:1,

    };
  },
	onLoad: function (opt) {
		var that=this
		this.opt = app.getopts(opt);
	  that.loaded();
		this.getdatalist();
		
	},
	methods: {
		getdatalist: function (loadmore) {
			if(!loadmore){
				this.pagenum = 1;
				this.datalist = [];
			}
			var that = this;  
			var pagenum = that.pagenum;
			var bid = that.opt.bid ? that.opt.bid : '';
			var order = that.order;
		  var keyword = that.keyword;
			var field = that.field; 
			that.loading = true;
			that.nodata = false;
			that.nomore = false;
			var type=that.curTopIndex
			app.post('ApiExpress/getLog', {pagenum: pagenum,keyword: keyword,field: field,order: order,bid:bid,type:type,}, function (res) { 
				that.loading = false;
				uni.stopPullDownRefresh();
				var data = res.data.datalist;
				if (data.length == 0) {
					if(pagenum == 1){
						that.nodata = true;
					}else{
						that.nomore = true;
					}
				}
				that.expressdata = res.data.expressdata
				var datalist = that.datalist;
				var newdata = datalist.concat(data);
				that.datalist = newdata;
			});
		},
		expresschange:function(e){
			var that=this
			that.express_index = e.detail.value;
			console.log(that.express_index);
		},

   searchChange: function (e) {
      this.keyword = e.detail.value;
    },
    searchConfirm: function (e) {
      var that = this;
      var keyword = e.detail.value;
      that.keyword = keyword;
		  var company = that.expressdata[that.express_index];
			app.goto('logistics?com=' +company+'&num='+keyword);
	
    },
	 
	  bindPickerChange: function (e) {
	    var val = e.detail.value;
	    this.regiondata = val;
	  },
	  setaddressxx: function (e) {
	    this.addressxx = e.detail.value;
	  },
		switchTopTab: function (e) {
		  var that = this;
		  var id = e.currentTarget.dataset.id;
		  var index = parseInt(e.currentTarget.dataset.index);
		  this.curTopIndex = index;
		  this.getdatalist();
		}, 
		saoyisao: function (d) {
		  var that = this;
			if(app.globalData.platform == 'h5'){
				app.alert('请使用微信扫一扫功能扫码核销');return;
			}else if(app.globalData.platform == 'mp'){
				// #ifdef H5
				var jweixin = require('jweixin-module');
				jweixin.ready(function () {   //需在用户可能点击分享按钮前就先调用
					jweixin.scanQRCode({
						needResult: 1, // 默认为0，扫描结果由微信处理，1则直接返回扫描结果，
						scanType: ["qrCode","barCode"], // 可以指定扫二维码还是一维码，默认二者都有
						success: function (res) {
							var content = res.resultStr; // 当needResult 为 1 时，扫码返回的结果
							if(result.includes('CODE_128')){
								var params = content.split(',')[1];
							}
							//location.href = content;
							app.goto('logistics?num='+params);
							//if(content.length == 18 && (/^\d+$/.test(content))){ //是十八位数字 付款码
							//	location.href = "{:url('shoukuan')}/aid/{$aid}/auth_code/"+content
							//}else{
							//	location.href = content;
							//}
						}
					});
				});
				// #endif
			}else{
				// #ifndef H5
				uni.scanCode({
					success: function (res) {
						console.log(res);
						var content = res.result;
						var params = content.split(',')[1];
						app.goto('logistics?num='+params);
					}
				});
				// #endif
			}
		}
	}
}
</script>
<style>
	@import "./common.css";
	.container{ width:100%;display:flex;flex-direction:column}
	.search-container {width: 100%;height:250rpx;padding: 20rpx 23rpx 20rpx 23rpx;background-color: #FC4343;position: relative;overflow: hidden;border-bottom:1px solid #f5f5f5}
	.search-box {margin-top:55rpx;display:flex;align-items:center;height:88rpx;border-radius:12rpx;border:0; background-color:rgba(247,247,247,0.2); flex:1; }
	.search-box .img{width:40rpx;height:40rpx;margin-right:20rpx;margin-left:30rpx}
	.search-box .search-text {font-size:26rpx;text-align:center;color:#fff;width: 100%;}
	
	.wrap{ background:#EDEDED; position: relative;top:-20rpx; padding:40rpx; border-radius: 24rpx;}
	.top_title{ height: 180rpx; line-height: 180rpx; background: #fff; }
	.top_title .tab{  width: 50%;display: flex; justify-content: center;}
	.top_title image{ width: 64rpx; height: 64rpx;  margin-top: 55rpx;}
	.top_title .tab text{ line-height: 180rpx; color: #222222; font-size: 28rpx; font-weight: bold; margin-left: 10rpx;}
	
	.tab-box{ margin-top: 60rpx; font-size: 28rpx; margin-bottom:60rpx;}
	.tab-box .tab2{ color:#666; margin-right: 30rpx;padding-bottom: 20rpx; }
	.tab-box .tab2.on{ color: #222222; border-bottom:4rpx solid #222;}
	
	.list{ background: #fff; border-radius:12rpx ; margin-top: 20rpx;}
	.list .text1{ border-bottom: 1px solid #eee; padding: 20rpx; justify-content: space-between;}
	.list .text1 .com{ font-size: 24rpx; color:#888888}
	.list .text1 .t4{font-size: 24rpx;color:#222222 }
	.list .text1 .fuzhi{ width:28rpx; height: 28rpx; margin-left: 20rpx;}
	.list .text1 .t3{ font-size:20rpx; color: #E2E2E2; border: 1px solid #E2E2E2;padding: 5rpx;}
	.list .text2 .jiantou{ width: 124rpx; height: 16rpx;}
	.list .text2{ justify-content: center; width:100%; padding: 40rpx; }
	.list .text2 .t1_box{ width: 100%; font-size: 24rpx; color:#222} 
	.list .text2 .t1_box .t2{ display: flex; justify-content: center; font-size: 24rpx;color:#888;  } 
	.list .text2 .t2_box{ width: 33.33%; text-align: center; font-size: 36rpx; color:#222} 
	.list .text2 .t2_box .t2{ display: flex; justify-content: center; font-size: 24rpx;color:#888; margin-top: 10rpx;} 

	.picker{width:90rpx;color:#fff;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:1;overflow:hidden;text-align: right; line-height: 50rpx;}

</style>