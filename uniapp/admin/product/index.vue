<template>
<view class="container">
	<dd-tab :itemdata="['全部('+countall+')','已上架('+count1+')','未上架('+count0+')']" :itemst="['all','1','0']" :st="st" :isfixed="true" @changetab="changetab"></dd-tab>
	<view style="width:100%;height:100rpx"></view>
	<!-- #ifndef H5 || APP-PLUS -->
	<view class="topsearch flex-y-center">
		<view class="f1 flex-y-center">
			<image class="img" :src="pre_url+'/static/img/search_ico.png'" ></image>
			<input :value="keyword" placeholder="输入关键字搜索" placeholder-style="font-size:24rpx;color:#C2C2C2" @confirm="searchConfirm"></input>
		</view>
		<view class="screen-view flex flex-y-center" @click="changeClistDialog">
			<view class="screen-view-text" :style="{color: cids.length > 0 ?  t('color1'):''}">分类筛选</view>
			<text class="iconfont iconshaixuan" :style="{color: cids.length > 0 ?  t('color1'):''}"></text>
		</view>
	</view>
	<!--  #endif -->
	<view class="order-content">
	<block v-for="(item, index) in datalist" :key="index">
		<view class="order-box">
			<view class="content" style="border-bottom:none">
				<view @tap="goto" :data-url="'/pages/shop/product?id=' + item.id">
					<image :src="item.pic"></image>
				</view>
				<view class="detail">
					<view class="t1">{{item.name}}
						<view  class="stockwarning"  v-if="item.isstock_warning==1">
								<image :src="pre_url+'/static/img/workorder/ts.png'" style="width:30rpx;height:30rpx" ></image> 库存不足
						</view>
					</view>
					<view class="t2">剩余：{{item.stock}} <text style="color:#a88;padding-left:20rpx">已售：{{item.sales}}</text></view>
					<view class="t3"><text class="x1">￥{{item.sell_price}}</text><text class="x2" v-if="item.market_price != null">￥{{item.market_price}}</text></view>
				</view>
			</view>
			<view class="op">
				<text style="color:red" class="flex1" v-if="!item.status || item.status==0">未上架</text>
				<text style="color:green" class="flex1" v-else>已上架</text>
				<text style="color:orange" class="flex1" v-if="!item.ischecked">待审核</text>
				<block v-if="item.plate_id!=0">
					<view v-if="manage_set && manage_set.status_product==1">
						<view class="btn1" :style="{background:t('color1')}" @tap="setst" data-st="1" :data-id="item.id" v-if="!item.status || item.status==0">上架</view>
						<view class="btn1" :style="{background:t('color2')}" @tap="setst" data-st="0" :data-id="item.id" v-else>下架</view>
					</view>
					<view v-if="manage_set && manage_set.stock_product==1">
						<view @tap="goto" :data-url="'editstock?id='+item.id" class="btn2">修改价格</view>
					</view>
				</block>
				<block v-else-if="mendian_usercenter==1">
					<view class="btn1" :style="{background:t('color1')}" @tap="setst" data-st="1" :data-id="item.id" v-if="!item.status || item.status==0">上架</view>
					<view class="btn1" :style="{background:t('color2')}" @tap="setst" data-st="0" :data-id="item.id" v-else>下架</view>
					<view @tap="goto" :data-url="'editstock?id='+item.id + '&cids=' + cids" class="btn2">修改库存</view>
				</block>
				<block v-else>
					<view class="btn1" :style="{background:t('color1')}" @tap="setst" data-st="1" :data-id="item.id" v-if="!item.status || item.status==0">上架</view>
					<view class="btn1" :style="{background:t('color2')}" @tap="setst" data-st="0" :data-id="item.id" v-else>下架</view>
					<view @tap="goto" :data-url="'edit?id='+item.id + '&cids=' + cids" class="btn2">编辑</view>
          <view class="btn2" @tap="tocopy" :data-id="item.id">复制</view>
					<view class="btn2" @tap="todel" :data-id="item.id">删除</view>
				</block>
			</view>
		</view>
	</block>
	</view>
	<view class="popup__container" v-if="clistshow">
		<view class="popup__overlay" @tap.stop="changeClistDialog"></view>
		<view class="popup__modal" style="max-height: 1400rpx;">
			<view class="popup__title">
				<text class="popup__title-text">请选择分类</text>
				<image :src="pre_url+'/static/img/close.png'" class="popup__close" style="width:36rpx;height:36rpx" @tap.stop="changeClistDialog"/>
			</view>
			<view class="popup__content">
				<block v-for="(item, index) in clist" :key="item.id">
					<view class="clist-item" @tap="cidsChange" :data-id="item.id">
						<view class="flex1">{{item.name}}</view>
						<view class="radio" :style="inArray(item.id,cids) ? 'background:'+t('color1')+';border:0' : ''"><image class="radio-img" :src="pre_url+'/static/img/checkd.png'"/></view>
					</view>
					<block v-for="(item2, index2) in item.child" :key="item2.id">
						<view class="clist-item" style="padding-left:80rpx" @tap="cidsChange" :data-id="item2.id">
							<view class="flex1" v-if="item.child.length-1==index2">└ {{item2.name}}</view>
							<view class="flex1" v-else>├ {{item2.name}}</view>
							<view class="radio" :style="inArray(item2.id,cids) ? 'background:'+t('color1')+';border:0' : ''"><image class="radio-img" :src="pre_url+'/static/img/checkd.png'"/></view>
						</view>
						<block v-for="(item3, index3) in item2.child" :key="item3.id">
						<view class="clist-item" style="padding-left:160rpx" @tap="cidsChange" :data-id="item3.id">
							<view class="flex1" v-if="item2.child.length-1==index3">└ {{item3.name}}</view>
							<view class="flex1" v-else>├ {{item3.name}}</view>
							<view class="radio" :style="inArray(item3.id,cids) ? 'background:'+t('color1')+';border:0' : ''"><image class="radio-img" :src="pre_url+'/static/img/checkd.png'"/></view>
						</view>
						</block>
					</block>
				</block>
			</view>
			<view class="popup_but_view flex">
				<view class="popup_but" :style="{borderColor:t('color1'),color:t('color1')}" @tap.stop="changeClistDialogReset">重置</view>
				<view class="popup_but" :style="{background:t('color1')}" @tap.stop="changeClistDialogsearch">确定</view>
			</view>
		</view>
	</view>
	<loading v-if="loading"></loading>
	<nomore v-if="nomore"></nomore>
	<nodata v-if="nodata"></nodata>
	<popmsg ref="popmsg"></popmsg>
</view>
</template>

<script>
var app = getApp();

export default {
  data() {
    return {
      st: 'all',
      datalist: [],
      pagenum: 1,
      nodata: false,
      nomore: false,
			loading:false,
      count0: 0,
      count1: 0,
      countall: 0,
      sclist: "",
			keyword: '',
			pre_url:app.globalData.pre_url,
			manage_set:[],
			clistshow:false,
			clist:[],
			cids:[],
			bid:0,
			mendian_usercenter:0,//是否是门店中心
    };
  },
  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		if(opt.cids) this.cids = opt.cids.split(",");
		this.getdata();
		this.getclist();
		// 判断H5|App 右上角角标展示
		this.pageMarker();
  },
	onPullDownRefresh: function () {
		this.getdata();
	},
  onReachBottom: function () {
    if (!this.nodata && !this.nomore) {
      this.pagenum = this.pagenum + 1;
      this.getdata(true);
    }
  },
	onNavigationBarSearchInputConfirmed:function(e){
		this.searchConfirm({detail:{value:e.text}});
	},
	onNavigationBarButtonTap(e) {
		if(e.index == 1){
			this.changeClistDialog()
		}
	},
  methods: {
		pageMarker(){
			// #ifdef H5
			if(this.cids.length > 0){
				document.querySelectorAll('.uni-page-head .uni-page-head-ft .uni-page-head-btn')[1].querySelector('.uni-btn-icon').style.color = this.t('color1');
			}else{
				document.querySelectorAll('.uni-page-head .uni-page-head-ft .uni-page-head-btn')[1].querySelector('.uni-btn-icon').style.color = 'rgb(0,0,0)';
			}
			// #endif
		},
		cidsChange:function(e){
			var clist = this.clist;
			var cids = this.cids;
			var cid = e.currentTarget.dataset.id;
			var newcids = [];
			var ischecked = false;
			for(var i in cids){
				if(cids[i] != cid){
					newcids.push(cids[i]);
				}else{
					ischecked = true;
				}
			}
			if(ischecked==false){
				newcids.push(cid);
			}
			this.cids = newcids;
			this.pageMarker();
		},
		// 获取分类列表
		getclist:function(){
			var that = this;
			var id = that.opt.id ? that.opt.id : '';
			app.get('ApiAdminProduct/edit',{id:id}, function (res) {
				if(res.bid > 0){
					that.clist = res.clist2;
				}else{
					that.clist = res.clist;
				}
			});
		},
		changeClistDialog:function(){
			this.clistshow = !this.clistshow;
		},
		changeClistDialogsearch(){
			this.clistshow = !this.clistshow;
			this.getdata();
		},
		changeClistDialogReset(){
			this.clistshow = !this.clistshow;
			this.cids = [];
			this.pageMarker();
			this.getdata();
		},
    changetab: function (st) {
      var that = this;
      that.st = st;
      that.getdata();
    },
    getdata: function (loadmore) {
     if(!loadmore){
				this.pagenum = 1;
				this.datalist = [];
			}
      var that = this;
      var pagenum = that.pagenum;
      var st = that.st;
			that.nodata = false;
			that.nomore = false;
			that.loading = true;
      app.post('ApiAdminProduct/index', {keyword:that.keyword,pagenum: pagenum,st: that.st,cids:that.cids}, function (res) {
        that.loading = false;
        var data = res.datalist;
        if (pagenum == 1){
					that.countall = res.countall;
					that.count0 = res.count0;
					that.count1 = res.count1;
					that.datalist = data;
					that.manage_set = res.manage_set;
					that.mendian_usercenter = res.mendian_usercenter;
          if (data.length == 0) {
            that.nodata = true;
          }
					that.loaded();
        }else{
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
    todel: function (e) {
      var that = this;
      var id = e.currentTarget.dataset.id;
      app.confirm('确定要删除该商品吗?', function () {
        app.post('ApiAdminProduct/del', {id: id}, function (res) {
          if (res.status == 1) {
            app.success(res.msg);
            that.getdata();
          } else {
            app.error(res.msg);
          }
        });
      });
    },
    tocopy: function (e) {
      var that = this;
      var id = e.currentTarget.dataset.id;
      app.confirm('确定要复制该商品吗?', function () {
        app.showLoading('复制中...');
        app.post('ApiAdminProduct/procopy', {id: id}, function (res) {
          app.showLoading(false);
          if (res.status == 1) {
            app.success(res.msg);
            that.getdata();
          } else {
            app.error(res.msg);
          }
        });
      });
    },
    setst: function (e) {
      var that = this;
      var id = e.currentTarget.dataset.id;
      var st = e.currentTarget.dataset.st;
      app.confirm('确定要' + (st == 0 ? '下架' : '上架') + '吗?', function () {
        app.post('ApiAdminProduct/setst', {st: st,id: id}, function (res) {
          if (res.status == 1) {
            app.success(res.msg);
            that.getdata();
          } else {
            app.error(res.msg);
          }
        });
      });
    },
		searchConfirm:function(e){
			this.keyword = e.detail.value;
      this.getdata(false);
		}
  }
};
</script>
<style>
.container{ width:100%;}
.topsearch{width:94%;margin:10rpx 3%;}
.topsearch .f1{height:60rpx;border-radius:30rpx;border:0;background-color:#fff;flex:1}
.topsearch .f1 .img{width:24rpx;height:24rpx;margin-left:10px}
.topsearch .f1 input{height:100%;flex:1;padding:0 20rpx;font-size:28rpx;color:#333;}
.topsearch .screen-view{font-size: 24rpx;color: #999;margin-left: 10rpx;}
.topsearch .screen-view-text{width: 120rpx;text-align: right;}
.topsearch .screen-view text{font-size: 23rpx;font-weight: bold;margin-left: 8rpx;margin-top: 3rpx;}
.topsearch .screen-view image{width: 22rpx;height: 22rpx;margin-left: 10rpx;transform: rotate(180deg);}
.order-content{display:flex;flex-direction:column}
.order-box{ width: 94%;margin:10rpx 3%;padding:6rpx 3%; background: #fff;border-radius:8px}

.order-box .content{display:flex;width: 100%; padding:16rpx 0px;border-bottom: 1px #e5e5e5 dashed;position:relative}
.order-box .content:last-child{ border-bottom: 0; }
.order-box .content image{ width: 140rpx; height: 140rpx;}
.order-box .content .detail{display:flex;flex-direction:column;margin-left:14rpx;flex:1}
.order-box .content .detail .t1{font-size:26rpx;min-height:50rpx;line-height:36rpx;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:2;overflow:hidden;}
.order-box .content .detail .t2{height:36rpx;line-height:36rpx;color: #999;overflow: hidden;font-size: 24rpx;}
.order-box .content .detail .t3{display:flex;height: 36rpx;line-height: 36rpx;color: #ff4246;}
.order-box .content .detail .x1{ font-size:30rpx;margin-right:5px}
.order-box .content .detail .x2{ font-size:24rpx;text-decoration:line-through;color:#999}

.order-box .bottom{ width:100%; padding:10rpx 0px; border-top: 1px #e5e5e5 solid; color: #555;}
.order-box .op{ display:flex;align-items:center;width:100%; padding:10rpx 0px; border-top: 1px #e5e5e5 solid; color: #555;}
.btn1{margin-left:20rpx;width:120rpx;height:60rpx;line-height:60rpx;color:#fff;border-radius:3px;text-align:center}
.btn2{margin-left:20rpx;width:120rpx;height:60rpx;line-height:60rpx;color:#333;background:#fff;border:1px solid #cdcdcd;border-radius:3px;text-align:center}

.stockwarning{ font-size:24rpx;color:red;  display:flex;  align-items:center}
.content .stockwarning image{ width:30rpx; height:30rpx; margin-right:10rpx}

.clist-item{display:flex;border-bottom: 1px solid #f5f5f5;padding:20rpx 30rpx;}
.radio{flex-shrink:0;width: 32rpx;height: 32rpx;background: #FFFFFF;border: 2rpx solid #BFBFBF;border-radius: 50%;margin-right:30rpx}
.radio .radio-img{width:100%;height:100%;display:block}
.popup_but_view{width: 92%;margin: 0 auto;align-items: center;justify-content: space-between;}
.popup_but{width: 48%;padding: 16rpx 0rpx;border-radius: 60rpx;text-align: center;font-size: 28rpx;color: #FFFFFF;border: 1px solid;box-sizing: border-box;}
/*  */
</style>