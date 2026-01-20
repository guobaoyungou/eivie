<template>
	<view class="container">
		<view class="search-container">
			<view class="topsearch flex-y-center">
				<view class="f1 flex-y-center">
					<image class="img" :src="pre_url+'/static/img/search_ico.png'"></image>
					<input :value="keyword" placeholder="搜索" placeholder-style="font-size:24rpx;color:#C2C2C2" confirm-type="search"  @confirm="searchConfirm"></input>
				</view>
			</view>
		</view>
		<view class="content">
			<view v-for="(item, index) in datalist" :key="index" class="item">			
				<view :data-url="'posterpurchaseorder?orderid=' + item.id" @tap="goto" class="product-item2">
					<view class="head">
						<view class="f1">{{item.ordernum}}</view>
					</view>
					<view class="product-info">
						<view class="p1">{{item.title}}</view>
						<view class="p3" style="margin-top: 10rpx;">
							<text class="t2">数量：</text>
							<text class="t2">{{item.totalnum}}</text> 
						</view>
						<view class="p3">
							<text class="t2">有效天数：</text>
							<text class="t2">{{item.validity_day}}</text> 
						</view>
						<view class="p3">
							<text class="t2">失效日期：</text>
							<text class="t2">{{item.expiring_date}}</text> 
						</view>
						<view class="p3">
							<text class="t2">创建时间：</text>
							<text class="t2">{{item.createtime}}</text> 
						</view>
						<view class="p3">
							<text class="t2">备注：</text>
							<text class="t2">{{item.remark}}</text> 
						</view>
					</view>
					<view class="op">
						<view class="btn2" @tap.stop="purchaseOrderRemarkOpen" :data-id="item.id">备注</view>
						<view class="btn2" @tap.stop="todel" :data-id="item.id">删除记录</view>
						<view class="btn2" @tap.stop="purchaseOrderSetOpen" :data-id="item.id">修改有效期</view>
					</view>
				</view>
			</view>
		</view>
		<uni-popup id="purchaseOrderSet" ref="purchaseOrderSet" type="dialog" :mask-click="false">
			<view class="uni-popup-dialog">
				<view class="uni-dialog-title">
					<text class="uni-dialog-title-text">设置</text>
				</view>
				<view class="uni-dialog-content">
					<view>
						<view class="flex-y-center flex-x-center form-item">
							<view class="form-label">有效期：</view>
							<input type="number" class="text-input" :value="editday" @input="inputChange" />
							<view class="form-label" style="margin-left: 10rpx;">天</view>
						</view>
					</view>
				</view>
				<view class="uni-dialog-button-group">
					<view class="uni-dialog-button" @click="purchaseOrderSetClose">
						<text class="uni-dialog-button-text">取消</text>
					</view>
					<view class="uni-dialog-button uni-border-left" @click="toedit">
						<text class="uni-dialog-button-text uni-button-color">确定</text>
					</view>
				</view>
			</view>
		</uni-popup>
		<uni-popup id="purchaseOrderRemark" ref="purchaseOrderRemark" type="dialog" :mask-click="false">
			<view class="uni-popup-dialog">
				<view class="uni-dialog-title">
					<text class="uni-dialog-title-text">备注</text>
				</view>
				<view class="uni-dialog-content">
					<view>
						<view class="flex-y-center flex-x-center form-item">
							<input type="text" :value="remark" placeholder="请输入备注" @input="inputChangeRemark" />
						</view>
					</view>
				</view>
				<view class="uni-dialog-button-group">
					<view class="uni-dialog-button" @click="purchaseOrderRemarkClose">
						<text class="uni-dialog-button-text">取消</text>
					</view>
					<view class="uni-dialog-button uni-border-left" @click="toeditRemark">
						<text class="uni-dialog-button-text uni-button-color">确定</text>
					</view>
				</view>
			</view>
		</uni-popup>
		<nomore v-if="nomore"></nomore>
		<nodata v-if="nodata"></nodata>
		<loading v-if="loading"></loading>
		<dp-tabbar :opt="opt"></dp-tabbar>
		<popmsg ref="popmsg"></popmsg>
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

				datalist: [],
				pagenum: 1,
				nomore: false,
				nodata: false,
				mid: 0,
				editid:'',
				editday:'',
				remark:'',
				pre_url:app.globalData.pre_url,
			};
		},

		onLoad: function(opt) {
			this.opt = app.getopts(opt);
			this.mid = this.opt.mid || 0;
			this.getdata();
		},
		onPullDownRefresh: function() {
			this.getdata();
		},
		onReachBottom: function() {
			if (!this.nodata && !this.nomore) {
				this.pagenum = this.pagenum + 1;
				this.getdata(true);
			}
		},
		methods: {
			getdata: function(loadmore) {
				if (!loadmore) {
					this.pagenum = 1;
					this.datalist = [];
				}
				var that = this;
				var pagenum = that.pagenum;
				var st = that.st;
				var keyword = that.keyword;
				that.nodata = false;
				that.nomore = false;
				that.loading = true;
				app.post('ApiPurchaseOrder/shareRecord', {
					st: st,
					pagenum: pagenum,
					mid: that.mid,
					keyword: keyword
				}, function(res) {
					that.loading = false;
					var data = res.data;
					if (pagenum == 1) {
						that.datalist = data;
						if (data.length == 0) {
							that.nodata = true;
						}
						that.loaded();
					} else {
						if (data.length == 0) {
							that.nomore = true;
						} else {
							var datalist = that.datalist;
							var newdata = datalist.concat(data);
							that.datalist = newdata;
						}
					}
				});
			},
			searchChange: function (e) {
			  this.keyword = e.detail.value;
			},
			searchConfirm: function (e) {
			  var that = this;
			  var keyword = e.detail.value;
			  that.keyword = keyword;
			  that.getdata();
			},
			todel: function (e) {
			  var that = this;
			  var orderid = e.currentTarget.dataset.id;
			  app.confirm('确定要删除吗?', function () {
					app.showLoading('删除中');
			    app.post('ApiPurchaseOrder/del', {orderid: orderid}, function (data) {
						app.showLoading(false);
			      app.success(data.msg);
			      setTimeout(function () {
			        that.getdata();
			      }, 1000);
			    });
			  });
			},
			purchaseOrderSetOpen:function(e){
				this.editid = e.currentTarget.dataset.id;
				this.$refs.purchaseOrderSet.open();
			},
			purchaseOrderSetClose:function(e){
				this.editid = '';
				this.$refs.purchaseOrderSet.close();
			},
			purchaseOrderRemarkOpen:function(e){
				this.editid = e.currentTarget.dataset.id;
				this.$refs.purchaseOrderRemark.open();
			},
			purchaseOrderRemarkClose:function(e){
				this.editid = '';
				this.$refs.purchaseOrderRemark.close();
			},
			inputChange:function(e){
				this.editday = e.detail.value;
			},
			inputChangeRemark:function(e){
				this.remark = e.detail.value;
			},
			searchConfirm:function(e){
				this.keyword = e.detail.value;
			  this.getdata(false);
			},
			toedit:function(e){
				var that = this;
				that.$refs.purchaseOrderSet.close();
				var validity = that.validity;
				
				if(validity <= 0){
					return app.error('设置的有效期无效');
				}
				app.showLoading('修改中');
				
				app.post('ApiPurchaseOrder/updateValidityDay', {
					orderid: that.editid,
					validity_day:that.editday
				}, function(res) {
					app.showLoading(false);
					if(res.status == 1){
						that.editday = '';
						that.editid = '';
						app.success(res.msg);
						setTimeout(function () {
						  that.getdata();
						}, 1000);
					}else{
						return app.error(res.msg);
					}
				});
				
			},
			toeditRemark:function(e){
				var that = this;
				that.$refs.purchaseOrderRemark.close();
				app.showLoading('修改中');
				app.post('ApiPurchaseOrder/updateRemark', {
					orderid: that.editid,
					remark:that.remark
				}, function(res) {
					app.showLoading(false);
					if(res.status == 1){
						app.success(res.msg);
						setTimeout(function () {
						  that.getdata();
						}, 1000);
					}else{
						return app.error(res.msg);
					}
				});
			}
		}
	};
</script>
<style>
	.search-container{position:fixed;width:100%;background:#fff;z-index:9;top:var(--window-top)}
	.topsearch{width:100%;padding:16rpx 20rpx}
	.topsearch .f1{height:60rpx;border-radius:30rpx;border:0;background-color:#f7f7f7;flex:1}
	.topsearch .f1 .img{width:24rpx;height:24rpx;margin-left:10px}
	.topsearch .f1 input{height:100%;flex:1;padding:0 20rpx;font-size:28rpx;color:#333}
	.topsearch .search-btn{display:flex;align-items:center;color:#5a5a5a;font-size:30rpx;width:60rpx;text-align:center;margin-left:20rpx}
	.search-navbar{display:flex;text-align:center;align-items:center;padding:5rpx 0}
	.search-navbar-item{flex:1;height:70rpx;line-height:70rpx;position:relative;font-size:28rpx;font-weight:bold;color:#323232}
	.search-navbar-item .iconshangla{position:absolute;top:-4rpx;padding:0 6rpx;font-size:20rpx;color:#7D7D7D}
	.search-navbar-item .icondaoxu{position:absolute;top:8rpx;padding:0 6rpx;font-size:20rpx;color:#7D7D7D}
	.search-navbar-item .iconshaixuan{margin-left:10rpx;font-size:22rpx;color:#7d7d7d}
	.content{margin-top:115rpx}
	.content .head{display:flex;width:100%;border-bottom:1px #f4f4f4 solid;height:70rpx;line-height:70rpx;overflow:hidden;color:#999;justify-content:space-between}
	.content .head .f1{display:flex;align-items:center;color:#333}
	.content .head image{width:34rpx;height:34rpx;margin-right:4px}
	.content .head .st0{width:140rpx;color:#ff8758;text-align:right}
	.content .head .st1{width:140rpx;color:#ffc702;text-align:right}
	.content .head .st2{width:140rpx;color:#ff4246;text-align:right}
	.content .head .st3{width:140rpx;color:#999;text-align:right}
	.content .head .st4{width:140rpx;color:#bbb;text-align:right}
	.content .op{ display:flex;flex-wrap: wrap;justify-content:flex-end;align-items:center;width:100%; padding: 10rpx 0px; border-top: 1px #f4f4f4 solid; color: #555;}
	.btn1{margin-left:20rpx; margin-top: 10rpx;height:60rpx;line-height:60rpx;color:#fff;border-radius:3px;text-align:center;padding: 0 20rpx;}
	.btn2{margin-left:20rpx; margin-top: 10rpx;height:60rpx;line-height:60rpx;color:#333;background:#fff;border:1px solid #cdcdcd;border-radius:3px;text-align:center;padding: 0 20rpx;}
	.item{width:94%;margin:0 3%;padding:0 20rpx;background:#fff;margin-top:20rpx;border-radius:20rpx}
	.product-item2{padding:20rpx 10rpx;border-bottom:1px solid #E6E6E6}
	.product-item2 .product-pic{width:180rpx;height:180rpx;background:#ffffff;overflow:hidden}
	.product-item2 .product-pic image{width:100%;height:100%}
	.product-item2 .product-info{padding:20rpx 0}
	.product-item2 .product-info .p1{word-break:break-all;text-overflow:ellipsis;overflow:hidden;display:block;height:80rpx;line-height:40rpx;font-size:30rpx;color:#111111}
	.product-item2 .product-info .p2{font-size:32rpx;height:40rpx;line-height:40rpx}
	.product-item2 .product-info .p2 .t2{margin-left:10rpx;font-size:26rpx;color:#888}
	.product-item2 .product-info .p3{font-size:24rpx;line-height:50rpx;overflow:hidden}
	.product-item2 .product-info .p3 .t1{color:#aaa;font-size:24rpx}
	.product-item2 .product-info .p3 .t2{color:#888;font-size:24rpx}
	
	.uni-popup-dialog {width: 300px;border-radius: 5px;background-color: #fff;}
	.uni-dialog-title {display: flex;flex-direction: row;justify-content: center;padding-top: 15px;padding-bottom: 5px;}
	.uni-dialog-title-text {font-size: 16px;font-weight: 500;}
	.uni-dialog-content {display: flex;flex-direction: row;justify-content: center;align-items: center;padding: 5px 5px 15px 5px;}
	.uni-dialog-content-text {font-size: 14px;color: #6e6e6e;}
	.uni-dialog-content .form-item{margin:20rpx 20rpx;height: 80rpx;}
	.uni-dialog-content .form-label{font-size:28rpx;color:#555}
	.uni-dialog-content .text-input{border: 1px #eee solid;height:70rpx;border-radius:4px;flex:1;font-size:28rpx;width:120rpx;text-align: center;}
	.uni-dialog-button-group {display: flex;flex-direction: row;border-top-color: #f5f5f5;border-top-style: solid;border-top-width: 1px;}
	.uni-dialog-button {display: flex;flex: 1;flex-direction: row;justify-content: center;align-items: center;height: 45px;}
	.uni-border-left {border-left-color: #f0f0f0;border-left-style: solid;border-left-width: 1px;}
	.uni-dialog-button-text {font-size: 14px;}
	.uni-button-color {color: #007aff;}
	.danhao-input-view{border: 1px #eee solid;display: flex;align-items: center;flex: 1;}
	.danhao-input-view image{width: 60rpx;height: 60rpx;}
</style>